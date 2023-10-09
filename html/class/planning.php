<?php

use Fpdf\Fpdf as FPDF;

class planning
{

    private $listeelement = null;

    private $dbconnect = null;

    private $datedebut = null;

    private $datefin = null;
    
    private $agent = null;

    private $fonctions = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "Planning->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($agentid, $datedebut, $datefin, $includeteletravail = false, $includecongeabsence = true, $includeabsenceteletravail = false)
    {

        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        
        $this->agent = $agent;
        
        // Par défaut, un agent ne travaille pas le samedi
        $travailsamedi = false;
        if (method_exists($agent,'travailsamedi'))
        {
            // Si la méthode existe, alors on regarde quelle est sa valeur
            $travailsamedi = $agent->travailsamedi();
        }
        // Par défaut, un agent ne travaille pas le dimanche
        $travaildimanche = false;
        if (method_exists($agent,'travaildimanche'))
        {
            // Si la méthode existe, alors on regarde quelle est sa valeur
            $travaildimanche = $agent->travaildimanche();
        }
        
        $this->datedebut = $datedebut;
        $this->datefin = $datefin;
        
        $jrs_feries = $this->fonctions->jourferier();
        
        // echo "Jours fériés = " . $jrs_feries . "<br>";
        
        unset($listeelement);
        $autodeclaration = null;
        $affectation = null;
        $fulldeclarationTPliste = null;
        
        $nbre_jour = $this->fonctions->nbjours_deux_dates($datedebut, $datefin);
        
        $datetemp = $this->fonctions->formatdatedb($datedebut);
        // echo "datetemp= $datetemp <br>";
        // On boucle sur tous les jours
        $declarationTP = null;
        // echo "Début For : " . date("d/m/Y H:i:s") . "<br>";
        
        $fulldeclarationTPliste = array();
        $ignoremissinggstructure = false;
        //if ($includeteletravail)
        //{
        //    $ignoremissinggstructure = true;
        //}
        $affectationliste = $agent->affectationliste($datedebut, $datefin, $ignoremissinggstructure);
        
        foreach ((array) $affectationliste as $affectation) {
            $declarationTPliste = $affectation->declarationTPliste($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin));
            $fulldeclarationTPliste[$affectation->affectationid()] = $declarationTPliste;
        }
        if (is_array($affectationliste))
            $affectation = reset($affectationliste); // On récupère la première affectation
        
        /*
         * echo "Affectationliste = " . print_r($affectationliste,true) . "<br>";
         * echo "----------------------------------------------------------------------------------<br>";
         * echo "fulldeclarationTPliste = " . print_r($fulldeclarationTPliste,true) . "<br>";
         */
        for ($index = 0; $index <= $nbre_jour - 1; $index ++) {
            // echo "datetemp= $datetemp <br>";
            
            // S'il y a des affectations dans la liste et que l'affectation courante est null, je cherche s'il y en a une correspondant à la date du jour....
            if (is_array($affectationliste) and (is_null($affectation))) {
                $affectation = null;
                $declarationTP = null;
                // On recherche s'il y a une affectation correspondant au jour courant
                foreach ($affectationliste as $tempaffectation) {
                    if (($this->fonctions->formatdatedb($tempaffectation->datedebut()) <= $this->fonctions->formatdatedb($datetemp)) and ($this->fonctions->formatdatedb($tempaffectation->datefin()) >= $this->fonctions->formatdatedb($datetemp))) {
                        // C'est la bonne affectation !
                        $affectation = $tempaffectation;
                        break;
                    }
                }
            }            // Si on a déja une affectation
            elseif (! is_null($affectation)) {
                // On regarde si l'affectation est terminée.....
                if ($this->fonctions->formatdatedb($affectation->datefin()) < $this->fonctions->formatdatedb($datetemp)) {
                    // On supprime l'affectation terminée (pour optimiser les boucles foreach...)
                    unset($affectationliste[$affectation->affectationid()]);
                    // Oui elle ai terminée => On remet tout à 0
                    $affectation = null;
                    $declarationTP = null;
                    // On recherche s'il y a une affectation correspondant au jour courant
                    foreach ($affectationliste as $tempaffectation) {
                        if (($this->fonctions->formatdatedb($tempaffectation->datedebut()) <= $this->fonctions->formatdatedb($datetemp)) and ($this->fonctions->formatdatedb($tempaffectation->datefin()) >= $this->fonctions->formatdatedb($datetemp))) {
                            // C'est la bonne affectation !
                            $affectation = $tempaffectation;
                            break;
                        }
                    }
                }
            }
            // Si on a déjà une declaration de TP <=> On a pas changé d'affectation
            if (! is_null($declarationTP)) {
                // On regarde si la déclaration de TP est toujours valide
                if ($this->fonctions->formatdatedb($declarationTP->datefin()) < $this->fonctions->formatdatedb($datetemp)) {
                    // Non elle n'est plus valide => On la met à null
                    $declarationTP = null;
                }
            }
            
            // Si on a une affectation courante (soit parce que c'est la même qu'au tour d'avant, soit on vient de la charger à partir de la liste 'affectationliste'
            if (! is_null($affectation)) {
                // On récupère la liste des declaration de TP pour cette affectation
                $declarationTPliste = $fulldeclarationTPliste[$affectation->affectationid()];
                // On recherche s'il y a une declaration de TP correspondant au jour courant
                foreach ((array) $declarationTPliste as $tempdeclarationTP) {
                    if (($this->fonctions->formatdatedb($tempdeclarationTP->datedebut()) <= $this->fonctions->formatdatedb($datetemp)) and ($this->fonctions->formatdatedb($tempdeclarationTP->datefin()) >= $this->fonctions->formatdatedb($datetemp))) {
                        // Si la déclaration de TP est validée
                        if (strcasecmp($tempdeclarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) == 0) {
                            // C'est la bonne declaration de TP !
                            $declarationTP = $tempdeclarationTP;
                            break;
                        }
                    }
                }
            }
                        
            // Le matin du jour en cours de traitement
            $element = new planningelement($this->dbconnect);
            $element->date($this->fonctions->formatdate($datetemp));
            $element->moment(fonctions::MOMENT_MATIN);
            
            if (strpos($jrs_feries, ";" . $datetemp . ";")) {
                // echo "C'est un jour férié = $datetemp <br>";
                $element->type("ferie");
                $element->info("jour férié");
            } 
            elseif (date("w", strtotime($datetemp)) == 0 and !$travaildimanche)  /* dimanche */
            {
                $element->type("WE");
                $element->info("week-end");
            }
            elseif (date("w", strtotime($datetemp)) == 6 and !$travailsamedi) /* Samedi */
            {
                $element->type("WE");
                $element->info("week-end");
            }            // On est dans le cas ou aucune déclaration de TP n'est faite
            elseif (is_null($declarationTP)) 
            {
                $element->type("nondec");
                $element->info("Période non déclarée");
            }            // On est dans le cas ou le statut n'est pas validé => C'est comme si on avait rien fait !!!
            elseif (strcasecmp($declarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) != 0) 
            {
                $element->type("nondec");
                $element->info("Période non déclarée");
            } 
            elseif ($declarationTP->enTP($element->date(), $element->moment())) 
            {
                $element->type("tppar");
                $element->info("Temps partiel");
            } 
            else 
            {
                // Ici c'est une case blanche vide !! Il ne se passe rien
                $element->type("");
                $element->info("");
            }
            $element->agentid($agentid);
            $this->listeelement[$datetemp . fonctions::MOMENT_MATIN] = $element;
            
            // L'apres-midi du jour en cours de traitement
            unset($element);
            $element = new planningelement($this->dbconnect);
            $element->date($this->fonctions->formatdate($datetemp));
            $element->moment(fonctions::MOMENT_APRESMIDI);
            if (strpos($jrs_feries, ";" . $datetemp . ";")) 
            {
                // echo "C'est un jour férié = $datetemp <br>";
                $element->type("ferie");
                $element->info("jour férié");
            } 
            elseif (date("w", strtotime($datetemp)) == 0 and !$travaildimanche)   /* dimanche */
            {
                $element->type("WE");
                $element->info("week-end");
            }
            elseif (date("w", strtotime($datetemp)) == 6 and !$travailsamedi)  /* Samedi */
            {
                $element->type("WE");
                $element->info("week-end");
            } 
            elseif (is_null($declarationTP)) 
            {
                $element->type("nondec");
                $element->info("Période non déclarée");
            }            // On est dans le cas ou le statut n'est pas validé => C'est comme si on avait rien fait !!!
            elseif (strcasecmp($declarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) != 0) 
            {
                $element->type("nondec");
                $element->info("Période non déclarée");
            } 
            elseif ($declarationTP->enTP($element->date(), $element->moment())) 
            {
                $element->type("tppar");
                $element->info("Temps partiel");
            } 
            else 
            {
                // Ici c'est une case blanche vide !! Il ne se passe rien
                $element->type("");
                $element->info("");
            }
            
            $element->agentid($agentid);
            $this->listeelement[$datetemp . fonctions::MOMENT_APRESMIDI] = $element;
            unset($element);
            // echo "datetemp = " . strtotime($datetemp) . "<br>";
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
                                                                           // echo "On passe à la date : " .$datetemp . "( " . strtotime($datetemp) . ") <br>";
        }
        
        // echo "Nbre d'élément = " . count($this->listeelement);
        // echo " " . date("H:i:s") . "<br>";
        // echo "Planning->Load : fulldeclarationTPliste = "; print_r($fulldeclarationTPliste); echo "<br>";
        
        if ($includecongeabsence or $includeabsenceteletravail)
        {
            $demandeliste = $agent->demandesliste($datedebut, $datefin);
            $demande = new demande($this->dbconnect);
            foreach ((array) $demandeliste as $demandeid => $demande) 
            {
                // Si on ne demande que les absences de type télétravail
                if (!$includecongeabsence and $includeabsenceteletravail)
                {
                    // Si le parent de l'absence n'est pas de type 'teletravHC' => On ne le traite pas
                    if (TABCOULEURPLANNINGELEMENT[$demande->type()]['parentid'] != 'teletravHC')
                    {
                        continue;
                    }
                }
                
                if (($demande->statut() == demande::DEMANDE_VALIDE) or ($demande->statut() == demande::DEMANDE_ATTENTE)) {
                    $demandedatedeb = $this->fonctions->formatdate($demande->datedebut());
                    $demandedatefin = $this->fonctions->formatdate($demande->datefin());
                    $demandemomentdebut = $demande->moment_debut();
                    $demandemomentfin = $demande->moment_fin();
                    $datetemp = $this->fonctions->formatdatedb($demandedatedeb);
                    $demandetempmoment = $demandemomentdebut;
                    
                    // echo "demandedatedeb = $demandedatedeb demandedatefin = $demandedatefin demandemomentdebut=$demandemomentdebut demandemomentfin = $demandemomentfin datetemp =$datetemp <br>";
                    // echo "fonctions->formatdatedb(demandedatefin) = " . $this->fonctions->formatdatedb($demandedatefin) . "<br>";
                    while ($datetemp <= $this->fonctions->formatdatedb($demandedatefin)) {
                        // echo "demandetempmoment = $demandetempmoment datetemp = $datetemp <br>";
                        if ($datetemp >= $this->fonctions->formatdatedb($datedebut) and $datetemp <= $this->fonctions->formatdatedb($datefin)) {
                            // echo "demandemomentdebut = $demandemomentdebut <br>";
                            if ($datetemp == $this->fonctions->formatdatedb($demandedatedeb) and $demandetempmoment != $demandemomentdebut)
                                $demandetempmoment = "";
                            // echo "demandetempmoment (apres le if - matin)= " . $demandetempmoment . "<br>";
                            if ($demandetempmoment == fonctions::MOMENT_MATIN) {
                                // echo "Avant le new planningElement (bloc 'm') <br>";
                                unset($element);
                                $element = new planningelement($this->dbconnect);
                                $element->date($this->fonctions->formatdate($datetemp));
                                $element->moment(fonctions::MOMENT_MATIN);
                                $element->type($demande->type());
                                $element->statut($demande->statut());
                                $element->info($demande->typelibelle()); // motifrefus()
                                $element->agentid($agentid);
                                // echo "<br>Je set (matin) le demande id => " . $demande->id() ."<br>";
                                $element->demandeid($demande->id());
                                $element->demande($demande);
                                // echo "<br>Je l'ai fixé (matin) demande id => " . $element->demandeid() . "<br>";
                                // echo "Planning->load : Type = " . $result[2] . " Info = " . $result[15] . "<br>";
                                // echo "Planning->load : Type (element) = " . $element->type() . " Info (element) = " . $element->info() . "<br>";
                                // $element->couleur($result[16]); ==> La couleur est gérée par l'element du planning
                                // echo "Le type de l'élément courant est : " . $this->listeelement[$datetemp . $demandetempmoment]->type() . "<br>";
                                if (! array_key_exists($datetemp . $demandetempmoment, $this->listeelement))
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                elseif ($this->listeelement[$datetemp . $demandetempmoment]->type() == "" or strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                    // Si la période n'est pas déclarée, on affiche l'element de demande de congés, mais on efface son id de demande car on ne sait pas recalculer le nombre de jours
                                    if (strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                        $element->demandeid("");
                                        // On reset l'objet demande de l'élément
                                        $element->demande("");
                                    }
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                }
                                $demandetempmoment = fonctions::MOMENT_APRESMIDI;
                                unset($element);
                                // echo "Fin du traitement du demandetempmoment = 'matin' <br>";
                            }
                            // echo "datetemp = $datetemp demandedatefin = " . $this->fonctions->formatdatedb($demandedatefin) . " demandetempmoment = $demandetempmoment demandemomentfin = $demandemomentfin <br>";
                            if ($datetemp == $this->fonctions->formatdatedb($demandedatefin) and $demandetempmoment != $demandemomentfin)
                                $demandetempmoment = "";
                            // echo "demandetempmoment (apres le if - apres-midi)= " . $demandetempmoment . "<br>";
                                if ($demandetempmoment == fonctions::MOMENT_APRESMIDI) {
                                // echo "Avant le new planningElement (bloc 'a') <br>";
                                unset($element);
                                $element = new planningelement($this->dbconnect);
                                $element->date($this->fonctions->formatdate($datetemp));
                                $element->moment(fonctions::MOMENT_APRESMIDI);
                                $element->type($demande->type());
                                $element->statut($demande->statut());
                                $element->info($demande->typelibelle()); // motifrefus()
                                $element->agentid($agentid);
                                // echo "<br>Je set (apres midi) le demande id => " . $demande->id() ."<br>";
                                $element->demandeid($demande->id());
                                $element->demande($demande);
                                // echo "<br>Je l'ai fixé (apres midi) demande id => " . $element->demandeid() . "<br>";
                                // $element->couleur($result[16]); ==> La couleur est gérée par l'element du planning
                                if (! array_key_exists($datetemp . $demandetempmoment, $this->listeelement))
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                elseif ($this->listeelement[$datetemp . $demandetempmoment]->type() == "" or strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                    // Si la période n'est pas déclarée, on affiche l'element de demande de congés, mais on efface son id de demande car on ne sait pas recalculer le nombre de jours
                                    if (strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                        $element->demandeid("");
                                        // On reset l'objet demande de l'élément
                                        $element->demande("");
                                    }
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                }
                                unset($element);
                                // echo "Fin du traitement du demandetempmoment = 'après-midi' <br>";
                            }
                        }
                        $demandetempmoment = fonctions::MOMENT_MATIN;
                        // echo "la date apres le strtotime 1 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                        $timestamp = strtotime($datetemp);
                        $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
                                                                                       // echo "la date apres le strtotime 2 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                    }
                }
            }
        }
                
        // echo "<br><br>fin 1er while => "; print_r ($this->listeelement); echo "<br>";
        // echo "Fin premier while ... <br>";
        
        $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN,TYPEABSENCE
                FROM ABSENCERH
                WHERE AGENTID = ?
                  AND ((DATEDEBUT <= '" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN >='" . $this->fonctions->formatdatedb($datedebut) . "')
                    OR (DATEFIN >= '" . $this->fonctions->formatdatedb($datefin) . "' AND DATEDEBUT <='" . $this->fonctions->formatdatedb($datefin) . "')
                    OR (DATEDEBUT >= '" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN <= '" . $this->fonctions->formatdatedb($datefin) . "'))";
        // echo "SQL = $sql <br>";
        $params = array($agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Planning->load (ABSENCERH) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Planning->load (ABSENCERH) : Pas de congé pour cette agent dans la période demandée <br>";
        }
        // echo "Avant le while 2 <br>";
        while ($result = mysqli_fetch_row($query)) {
            $demandedatedeb = $this->fonctions->formatdate($result[1]);
            $demandedatefin = $this->fonctions->formatdate($result[2]);
            $demandemomentdebut = fonctions::MOMENT_MATIN;
            $demandemomentfin = fonctions::MOMENT_APRESMIDI;
            $datetemp = $this->fonctions->formatdatedb($demandedatedeb);
            $demandetempmoment = $demandemomentdebut;
            while ($datetemp <= $this->fonctions->formatdatedb($demandedatefin)) {
                // echo "Dans le petit while <br>";
                if ($datetemp >= $this->fonctions->formatdatedb($datedebut) and $datetemp <= $this->fonctions->formatdatedb($datefin)) {
                    // echo "Avant le if == m... <br>";
                    if ($demandetempmoment == fonctions::MOMENT_MATIN) {
                        $element = new planningelement($this->dbconnect);
                        // echo "avant le element date <br>";
                        $element->date($this->fonctions->formatdate($datetemp));
                        $element->moment($demandetempmoment);
                        $element->type("harp"); // ==> Le type de congé est fixé - Ce sont des congés RH
                        $element->info("$result[3]");
                        $element->agentid($agentid);
                        // $element->couleur($result[16]); ==> La couleur est gérée par l'element du planning
                        // echo "avant le if interne ==> DateTemp = " . $datetemp . " demandetempmoment = " . $demandetempmoment . " <br>";
                        if (! array_key_exists($datetemp . $demandetempmoment, $this->listeelement))
                            $this->listeelement[$datetemp . $demandetempmoment] = $element;
                        elseif ($this->listeelement[$datetemp . $demandetempmoment]->type() == "" or $this->fonctions->estunconge($this->listeelement[$datetemp . $demandetempmoment]->type()))
                            $this->listeelement[$datetemp . $demandetempmoment] = $element;
                        // echo "apres le if interne <br>";
                        $demandetempmoment = fonctions::MOMENT_APRESMIDI;
                        unset($element);
                    }
                    // echo "Avant le if ==a <br>";
                    if ($demandetempmoment == fonctions::MOMENT_APRESMIDI) {
                        $element = new planningelement($this->dbconnect);
                        $element->date($this->fonctions->formatdate($datetemp));
                        $element->moment($demandetempmoment);
                        $element->type("harp"); // ==> Le type de congé est fixé - Ce sont des congés RH
                        $element->info("$result[3]");
                        $element->agentid($agentid);
                        // $element->couleur($result[16]); ==> La couleur est gérée par l'element du planning
                        if (! array_key_exists($datetemp . $demandetempmoment, $this->listeelement))
                            $this->listeelement[$datetemp . $demandetempmoment] = $element;
                        elseif ($this->listeelement[$datetemp . $demandetempmoment]->type() == "" or $this->fonctions->estunconge($this->listeelement[$datetemp . $demandetempmoment]->type()))
                            $this->listeelement[$datetemp . $demandetempmoment] = $element;
                        $demandetempmoment = fonctions::MOMENT_MATIN;
                        unset($element);
                    }
                }
                // echo "Apres le while petit <br>";
                $timestamp = strtotime($datetemp);
                $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
            }
        }
        
        if ($includeteletravail)
        {
            $datedebutdb = $this->fonctions->formatdatedb($datedebut);
            $datefindb = $this->fonctions->formatdatedb($datefin);
            $teletravailliste = $agent->teletravailliste($datedebutdb,$datefindb);
            $fulldatetheorique = array();
            foreach ((array)$teletravailliste as $teletravailid)
            {
                $teletravail = new teletravail($this->dbconnect);
                $teletravail->load($teletravailid);
                if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE)
                {
                    $fulldatetheorique = array_merge($fulldatetheorique,$teletravail->datetheorique($datedebutdb,$datefindb));
                }
            }
            
            foreach ($fulldatetheorique as $arraydate)
            {
                $element = $this->getelement($arraydate[0], $arraydate[1]);
                if (!is_null($element))
                {
                    if ($element->type() == '')
                    {
                        if (!$this->fonctions->estjourteletravailexclu($agentid,$arraydate[0],$arraydate[1]))
                        {
                            $element->type('teletrav');
                            $element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL);
                            if ($arraydate[2]!==teletravail::CODE_CONVENTION_MEDICAL)
                            {
                                if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$element->type()]['libelle']))
                                {
                                    $element->info(TABCOULEURPLANNINGELEMENT[$element->type()]['libelle']);
                                }
                            }
                            else
                            {
                                if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$element->type()]['libelle']))
                                {
                                    $element->info(TABCOULEURPLANNINGELEMENT[$element->type()]['libelle'] . '  pour raison médicale');
                                }
                            }
                            $element->typeconvention($arraydate[2]); // On ajoute le type de convention de télétravail
                        }
                        else // L'élement est un jour de télétravail mais il est exclu => il ne s'affichera pas en rose dans le planning
                        {
                            $element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_EXCLUSION);
                        }
                    }
                    else
                    {
                        // C'est en théorie une 1/2 journée de télétravail, mais il y a quelque chose à la place
                        // => On indique quand même que c'est un jour de télétravail théorique
                        $element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN);

                    }
                }
            }
            $listplaningelement = $this->planning();
            foreach ($listplaningelement as $element)
            {
                $deplace = $this->fonctions->estjourteletravaildeplace($agentid, $element->date(), $element->moment());
                if ($element->type() == '' and $deplace !== false)
                {
                    $element->type('teletrav');
                    if ($deplace->momentorigine == '')
                    {
                        $element->info('Journée de télétravail déplacée du ' . $this->fonctions->formatdate($deplace->dateorigine));                        
                    }
                    else
                    {
                        $element->info('Demie journée de télétravail déplacée du ' . $this->fonctions->formatdate($deplace->dateorigine) . ' ' . $this->fonctions->nommoment($deplace->momentorigine));
                    }
                    // L'élement est un jour de télétravail mais il est deplace => il n'est pas clicable dans le planning 
                    $element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_DEPLACE);
                }
            }
        }
        
        
        
        // echo "Fin de la procédure Load <br>";
        
        // echo "<br>Liste des éléments = " . print_r($this->listeelement,true) . "<br>";
        
        return $this->listeelement;
    }

    function datedebut()
    {
        return $this->datedebut;
    }

    function datefin()
    {
        return $this->datefin;
    }
    
    function agent()
    {
        return $this->agent;
    }

    function planning()
    {
        if (is_null($this->listeelement)) {
            $errlog = "Planning->planning : Pas de planning défini !!!!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else
            return $this->listeelement;
    }

    function getelement($date, $moment)
    {
        $element = null;
        if (is_null($this->listeelement))
        {
            $errlog = "Planning->getelement : Pas de planning défini !!!!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        else
        {
            $date = $this->fonctions->formatdatedb($date);
            if (isset($this->listeelement[$date . $moment]))
            {
                $element = $this->listeelement[$date . $moment];
            }
        }
        return $element;
    }
    
    
    function planninghtml($agentid, $datedebut, $datefin, $clickable = FALSE, $showpdflink = TRUE, $noiretblanc = FALSE, $includeteletravail = FALSE)
    {
        //$this->fonctions->time_elapsed("Début de la fonction planninghtml", __METHOD__, true);
        // echo "datedebut = $datedebut datefin = $datefin <br>";
        // $this->listeelement = null;
        if (is_null($this->listeelement)) {
            // echo "Début chargement : " . date("d/m/Y H:i:s") . "<br>";
            $this->load($agentid, $datedebut, $datefin, $includeteletravail);
            // echo "Fin chargement : " . date("d/m/Y H:i:s") . "<br>";
        }
        
        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ($listecateg as $keycateg => $nomcateg)
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
        //var_dump($listeabs);
        
        $htmltext = "";
        $htmltext = $htmltext . "<div id='planning'>";
        $htmltext = $htmltext . "<table class='tableau' id='tab_agent_" . $agentid . "_" . $this->fonctions->formatdatedb($datedebut) ."'>";
        $month = date("m", strtotime($this->fonctions->formatdatedb($datedebut)));
        $currentmonth = "";
        $htmltext = $htmltext . "<tr class='entete'><td>Mois</td>";
        for ($indexjrs = 0; $indexjrs < 31; $indexjrs ++) {
            // echo "indexjrs = $indexjrs <br>";
            $htmltext = $htmltext . "<td colspan='2'>" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</td>";
        }
        $htmltext = $htmltext . "</tr>";
        
        $elementlegende = array();
        foreach ($this->listeelement as $key => $planningelement) {
            $month = date("m", strtotime($this->fonctions->formatdatedb($planningelement->date())));
            
            // echo "month = $month monthfin = $monthfin currentmonth = $currentmonth <br>";
            if ($month != $currentmonth) {
                $monthname = $this->fonctions->nommois($planningelement->date()) . " " . date("Y", strtotime($this->fonctions->formatdatedb($planningelement->date())));
                if ($currentmonth != "")
                    $htmltext = $htmltext . "</tr>\n<tr class='ligneplanning'>";
                else
                    $htmltext = $htmltext . "\n<tr class='ligneplanning'>";
                $htmltext = $htmltext . "<td>" . $monthname . "</td>";
                
                $currentmonth = $month;
            }
            $htmltext = $htmltext . $planningelement->html($clickable, null, $noiretblanc);

            if (!in_array($planningelement->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
            {
                if (array_key_exists($planningelement->type(),$listeabs))
                {
                    // Si c'est une absence dans la catégorie "télétravail hors convention"
                    if (strcmp($planningelement->parenttype(),'teletravHC')==0)
                    {
                        $elementlegende[$planningelement->parenttype()] = $planningelement->parenttype();
                    }
                    else // C'est une absence d'un autre type => Donc de type absence
                    {
                        //echo "Le type de l'élément = " . $planningelement->type() . "<br>";
                        $elementlegende['abs'] = 'abs';
                    }
                }
                else
                {
                    $elementlegende[$planningelement->type()] = $planningelement->type();
                }
            }
        }
        $htmltext = $htmltext . "</tr>";
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "</div>";
        // echo "fin de plannig->planninghtml <br>";
        
        $tempdate = $this->fonctions->formatdatedb($datedebut);
        $tempannee = substr($tempdate, 0, 4);
        
        //var_dump($elementlegende);
        
        // echo "Avant affichage legende <br>";
        if ($noiretblanc == false) {
            $htmltext = $htmltext . $this->fonctions->legendehtml($tempannee, $includeteletravail,$elementlegende);
        }
        // echo "Apres affichage legende <br>";
        $htmltext = $htmltext . "<br>";
        
        //$htmltext = $htmltext . "<br>";
        $htmltext = $htmltext . "<form name='userplanningpdf_" . $agentid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
        if ($includeteletravail and !$noiretblanc)
        {
            $htmltext = $htmltext . "<input type='checkbox' id='hide_teletravail_". $agentid . "' name='hide_teletravail_". $agentid . "' onclick='hide_teletravail(\"tab_agent_" . $agentid . "_" . $this->fonctions->formatdatedb($datedebut) ."\",\"hidden_input_teletravail_". $agentid . "\");' >Masquer le télétravail</input>";
            $htmltext = $htmltext . "<br><br>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='previous' value='no'>";
        $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . $tempannee . "'>";
        if ($includeteletravail)
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='yes'>";
         else
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='no'>";
        $htmltext = $htmltext . "</form>";
        $htmltext = $htmltext . "<form name='userpreviousplanningpdf_" . $agentid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
        $htmltext = $htmltext . "<input type='hidden' name='hide_teletravail_". $agentid . "' id='hidden_input_teletravail_". $agentid . "' value='off'>";
        $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='previous' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . ($tempannee - 1) . "'>";
        if ($includeteletravail)
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='yes'>";
        else
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='no'>";
        $htmltext = $htmltext . "</form>";
                
        
        if ($showpdflink == TRUE) {
            $htmltext = $htmltext . "<a href='javascript:document.userplanningpdf_" . $agentid . ".submit();'>Planning en PDF</a>";
            $htmltext = $htmltext . "<br>";
            $htmltext = $htmltext . "<a href='javascript:document.userpreviousplanningpdf_" . $agentid . ".submit();'>Planning en PDF (année précédente)</a>";
        }
        
        //$this->fonctions->time_elapsed("Fin de la fonction planninghtml", __METHOD__);
        return $htmltext;
    }

    function agentpresent($agentid, $datedebut, $momentdebut, $datefin, $momentfin, $ignoreabsenceautodecla = FALSE)
    {
        // echo "Avant le load du planning => $agentid $datedebut $momentdebut $datefin $momentfin <br>";
        $listeelement = $this->load($agentid, $datedebut, $datefin);
        // echo "Apres le load <br>";
        $paslepremier = FALSE;
        $pasledernier = FALSE;
        if (strcasecmp($momentdebut, fonctions::MOMENT_MATIN) != 0)
            $paslepremier = TRUE;
            if (strcasecmp($momentfin, fonctions::MOMENT_APRESMIDI) != 0)
            $pasledernier = TRUE;
        $index = 0;
        foreach ($listeelement as $key => $element) {
            $pasdetraitement = FALSE;
            if ($index == 0 and $paslepremier)
                $pasdetraitement = TRUE;
            if ($index == (count($listeelement) - 1) and $pasledernier)
                $pasdetraitement = TRUE;
            if (! $pasdetraitement) {
                // echo "element->type() = " . $element->type() . "<br>";
                if ($element->type() == "" or strcasecmp($element->type(), "WE") == 0 or strcasecmp($element->type(), "ferie") == 0 or strcasecmp($element->type(), "tppar") == 0) {
                    // On ne fait rien si c'est vide, un WE, un jour férié ou un temp partiel
                } elseif ($ignoreabsenceautodecla == TRUE and strcasecmp($element->type(), "nondec") == 0) {
                    // On ne fait rien car on doit ignorer le fait que l'autodéclaration n'est pas faite
                } else {
                    // echo "L'element " . $element->date() . " " . $element->moment() . " est de type : " . $element->type() . " ==> On sort (ABSENT) <br>";
                    return FALSE;
                }
            }
            $index ++;
        }
        return TRUE;
    }

    function nbrejourtravaille($agentid, $datedebut, $momentdebut, $datefin, $momentfin, $ignoreabsenceautodecla = FALSE)
    {
        $listeelement = $this->load($agentid, $datedebut, $datefin);
        $paslepremier = FALSE;
        $pasledernier = FALSE;
        if (strcasecmp($momentdebut, fonctions::MOMENT_MATIN) != 0) {
            $paslepremier = TRUE;
            // echo "On fixe paslepremier <br>";
        }
        if (strcasecmp($momentfin, fonctions::MOMENT_APRESMIDI) != 0) {
            $pasledernier = TRUE;
            // echo "On fixe pasledernier <br>";
        }
        $index = 0;
        $nbredemijour = 0;
        foreach ((array) $listeelement as $key => $element) {
            $pasdetraitement = FALSE;
            if ($index == 0 and $paslepremier) {
                $pasdetraitement = TRUE;
                // echo "pas de traitement du premier !! <br>";
            }
            // echo "Index = ". $index . "<br>";
            // echo "count($listeelement) = " . count($listeelement) . "<br>";
            // echo "key = " . $key . "<br>";
            if ($index == (count($listeelement) - 1) and $pasledernier) {
                $pasdetraitement = TRUE;
                // echo "pas de traitement du dernier !! <br>";
            }
            if (! $pasdetraitement) {
                // echo "On traite l'élément... Type =: " . $element->type() . " <br>";
                if ($element->type() == "") {
                    // On ajoute 1 car "rien de prévu ce jour là" donc c'est un jour ou l'agent travail
                    $nbredemijour ++;
                } elseif ($ignoreabsenceautodecla == TRUE and strcasecmp($element->type(), "nondec") == 0) {
                    // On ajoute 1 car "pas d'autodeclaration et on doit l'ignorer" donc c'est un jour ou l'agent travail
                    $nbredemijour ++;
                } else {
                    // On ne fait rien car le jour n'est pas travaillé et dispo
                }
            }
            // echo "nbredemijour =" . $nbredemijour . "<br>";
            $index ++;
        }
        return $nbredemijour / 2;
    }

    function pdf($agentid, $datedebut, $datefin, $noiretblanc = FALSE, $includeteletravail = FALSE)
    {
        
        // echo "Début fonction PDF <br>";
        if (is_null($this->listeelement))
            $this->load($agentid, $datedebut, $datefin, $includeteletravail);
        
        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        
        // echo "Apres le load <br>";
        $pdf=new FPDF();
        //$pdf = new TCPDF();
        //$pdf->SetHeaderData('', 0, '', '', array(
        //    0,
        //    0,
        //    0
        //), array(
        //    255,
        //    255,
        //    255
        //));
        // $pdf->Open();
        $pdf->AddPage('L');
        // echo "Apres le addpage <br>";
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 10, 5, 60, 20);
        $pdf->Image($this->fonctions->imagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
        $pdf->SetFont('helvetica', 'B', 15, '', true);
        $pdf->Ln(15);
        
        /*
         * /////////////////////////////////////////////////////////////////
         * $affectationliste = $agent->affectationliste($datedebut, $datefin);
         * foreach ($affectationliste as $key => $affectation)
         * {
         * $structure = new structure($this->dbconnect);
         * $structure->load($affectation->structureid());
         * $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() .")";
         * $pdf->Cell(60,10,'Service : '. $nomstructure);
         * $pdf->Ln();
         * }
         */
        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y')); // On récupère l'affectation courante
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : affectationliste = " . print_r($affectationliste,true)));
        if (is_array($affectationliste)) {
            // echo "affectationliste = " . print_r($affectationliste, true) . "<br>";
            $affectation = reset($affectationliste); // ATTENTION : Reset permet de récupérer le premier élément du tableau => On ne connait pas la clé
            $structure = new structure($this->dbconnect);
//            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : Avant le load structure "));
            $structure->load($affectation->structureid());
            $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
//            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : structure full name = $nomstructure"));
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $nomstructure));
            $pdf->Ln();
        }
        $pdf->Ln(10);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Planning de  : ' . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom()));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10, '', true);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Edité le ' . date("d/m/Y")));
        $pdf->Ln(10);
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : On a affiché les données du user = " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() ));
        
        // echo "Avant le planning <br>";
        
        // ///création du planning suivant le tableau généré
        // /Création des entetes de colones contenant les 31 jours/////
        
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : Avant le decode"));
        $pdf->Cell(30, 5, $this->fonctions->utf8_decode(""), 1, 0, 'C');
        for ($index = 1; $index <= 31; $index ++) {
            $pdf->Cell(8, 5, $this->fonctions->utf8_decode($index), 1, 0, 'C');
        }
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : Après le decode"));
        $pdf->Ln(5);

        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ($listecateg as $keycateg => $nomcateg)
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : Après récup listeabs = " . print_r($listeabs,true)));
        
        
        // echo "Avant le tableau <br>";
        // //boucle sur chaque mois du tableau
        $month = date("m", strtotime($this->fonctions->formatdatedb($datedebut)));
        $currentmonth = "";
        $elementlegende = array();
        foreach ($this->listeelement as $key => $planningelement) {
            // echo "avant le month = <br>";
            $month = date("m", strtotime($this->fonctions->formatdatedb($planningelement->date())));
            
            // echo "month = $month currentmonth = $currentmonth <br>";
            
            if ($month != $currentmonth) {
                $monthname = $this->fonctions->nommois($planningelement->date()) . " " . date("Y", strtotime($this->fonctions->formatdatedb($planningelement->date())));
                if ($currentmonth != "")
                    $pdf->Ln(5);
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode($monthname), 1, 0, 'C');
                
                $currentmonth = $month;
            }
            // echo "avant le list... <br>";
            // -------------------------------------------
            // Convertir les couleur HTML en RGB
            // -------------------------------------------
            list ($col_part1, $col_part2, $col_part3) = $this->fonctions->html2rgb($planningelement->couleur($noiretblanc));
            $pdf->SetFillColor($col_part1, $col_part2, $col_part3);
            if (strcasecmp($planningelement->moment(), fonctions::MOMENT_MATIN) != 0)
                $pdf->Cell(4, 5, $this->fonctions->utf8_decode(""), 'TBR', 0, 'C', 1);
            else
                $pdf->Cell(4, 5, $this->fonctions->utf8_decode(""), 'TBL', 0, 'C', 1);
            // echo "Apres les demies-cellules <br>";

            if (!in_array($planningelement->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
            {
                if (array_key_exists($planningelement->type(),$listeabs))
                {
                    // Si c'est une absence dans la catégorie "télétravail hors convention"
                    if (strcmp($planningelement->parenttype(),'teletravHC')==0)
                    {
                        $elementlegende[$planningelement->parenttype()] = $planningelement->parenttype();
                    }
                    else // C'est une absence d'un autre type => Donc de type absence
                    {
                        //echo "Le type de l'élément = " . $planningelement->type() . "<br>";
                        $elementlegende['abs'] = 'abs';
                    }
                }
                else
                {
                    $elementlegende[$planningelement->type()] = $planningelement->type();
                }
            }
        }
//        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Planning->pdf : Après le for"));
        
        // ///MISE EN PLACE DES LEGENDES DU PLANNING
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 7, '', true);
        $pdf->SetTextColor(0);
        // ////Mise en place de la légende couleurs pour les congés
        
        // echo "Avant legende <br>";
        $anneeref = date("Y", strtotime($this->fonctions->formatdatedb($datedebut)));
        $this->fonctions->legendepdf($pdf,$anneeref,$includeteletravail,$elementlegende);
        // echo "Apres legende <br>";
        
        $pdf->Ln(8);
        ob_end_clean();
        $pdf->Output("","planning_agent.pdf");
        // $pdf->Output('demande_pdf/autodeclaration_num'.$ID_AUTODECLARATION.'.pdf');
    }
    
    function nbjoursteletravail($agentid, $datedebut, $datefin, $reel = true, &$tabrepartition = array(), &$tabinfoindemnite = array())
    {
        $elementliste = $this->load($agentid, $datedebut, $datefin, true, $reel, true);
        $nbjoursteletravail = 0;
        
        // On initialise le tableau avec les éléments de télétravail 
        $tabrepartition["teletrav"] = 0;
        foreach (TABCOULEURPLANNINGELEMENT as $id => $element)
        {
            if ($element['parentid'] == 'teletravHC') 
            {
                $tabrepartition[$id] = 0;
            }
        }
        
        $tabindem = $this->fonctions->listeindemniteteletravail($datedebut, $datefin);
        $indextabindem = 0;
/*        
        if ($agentid == '9328')
        {
            var_dump($tabindem);
        }
*/        
        foreach ($elementliste as $element)
        {
            //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Le type parent de l'élément est " . $element->parenttype()));
            if (strcasecmp($element->type(), "teletrav")==0 or strcasecmp($element->parenttype(), "teletravHC")==0 )
            {
                $nbjoursteletravail = $nbjoursteletravail + 0.5;
                $tabrepartition[$element->type()] = $tabrepartition[$element->type()] + 0.5;
                
                $elementdate = $this->fonctions->formatdatedb($element->date());
                $montant = '0';
                $logtext = "";
                if ($tabindem[$indextabindem]["datefin"]<$elementdate)
                {
                    if (count($tabindem)>($indextabindem+1))
                    {
//                        $logtext = $logtext . " Cas 3";
                        $indextabindem ++;
                    }
                    else
                    {
//                        $logtext = $logtext . " Cas 2";
                        $montant = '0';
                    }
                }
                if ($tabindem[$indextabindem]["datedebut"]>$elementdate)
                {
//                    $logtext = $logtext . " Cas 1";
                    $montant = '0';
                }
                elseif (($tabindem[$indextabindem]["datedebut"]<=$elementdate) and ($tabindem[$indextabindem]["datefin"]>=$elementdate))
                {
//                    $logtext = $logtext . " Cas recup montant";
                    $montant = str_replace(',','.',$tabindem[$indextabindem]["montant"]);
                }
/*                
                if ($agentid == '9328')
                {
                    var_dump($logtext);
                    var_dump($elementdate);
                    var_dump($indextabindem);
                    var_dump($tabindem[$indextabindem]["datedebut"]);
                    var_dump($tabindem[$indextabindem]["datefin"]);
                    var_dump($montant);
                }
*/                
                if (!isset($tabinfoindemnite["$montant"]))
                {
                    $tabinfoindemnite["$montant"] = 0;
                }
                $tabinfoindemnite["$montant"] = $tabinfoindemnite["$montant"] + 0.5; // On a une 1/2 journée de plus au montant indiqué
            }
            
        }
        
        //var_dump($tabrepartition);
        return $nbjoursteletravail;
    }
    
}

?>