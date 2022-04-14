<?php

class planning
{

    private $listeelement = null;

    private $dbconnect = null;

    private $datedebut = null;

    private $datefin = null;

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

    function load($agentid, $datedebut, $datefin, $includeteletravail = false, $includecongeabsence = true)
    {
        //$this->fonctions->time_elapsed("Planning : Début de la fonction load", 10, true);

        //$this->fonctions->time_elapsed("Planning : Avant le load agent", 11, true);
        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        //$this->fonctions->time_elapsed("Planning : Après le load agent", 11);
        
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
        
        //$this->fonctions->time_elapsed("Planning : Avant le foreach affectationliste", 11, true);
        foreach ((array) $affectationliste as $affectation) {
            $declarationTPliste = $affectation->declarationTPliste($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin));
            $fulldeclarationTPliste[$affectation->affectationid()] = $declarationTPliste;
        }
        //$this->fonctions->time_elapsed("Planning : Après le foreach affectationliste", 11);
        if (is_array($affectationliste))
            $affectation = reset($affectationliste); // On récupère la première affectation
        
        /*
         * echo "Affectationliste = " . print_r($affectationliste,true) . "<br>";
         * echo "----------------------------------------------------------------------------------<br>";
         * echo "fulldeclarationTPliste = " . print_r($fulldeclarationTPliste,true) . "<br>";
         */
        //$this->fonctions->time_elapsed("Planning : Avant le for index=0", 11, true);
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
            $element->moment("m");
            
            if (strpos($jrs_feries, ";" . $datetemp . ";")) {
                // echo "C'est un jour férié = $datetemp <br>";
                $element->type("ferie");
                $element->info("jour férié");
            } elseif ((date("w", strtotime($datetemp)) == 0) or (date("w", strtotime($datetemp)) == 6)) {
                $element->type("WE");
                $element->info("week-end");
            }            // On est dans le cas ou aucune déclaration de TP n'est faite
            elseif (is_null($declarationTP)) {
                $element->type("nondec");
                $element->info("Période non déclarée");
            }            // On est dans le cas ou le statut n'est pas validé => C'est comme si on avait rien fait !!!
            elseif (strcasecmp($declarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) != 0) {
                $element->type("nondec");
                $element->info("Période non déclarée");
            } elseif ($declarationTP->enTP($element->date(), $element->moment())) {
                $element->type("tppar");
                $element->info("Temps partiel");
            } else {
                // Ici c'est une case blanche vide !! Il ne se passe rien
                $element->type("");
                $element->info("");
            }
            $element->agentid($agentid);
            $this->listeelement[$datetemp . "m"] = $element;
            
            // L'apres-midi du jour en cours de traitement
            unset($element);
            $element = new planningelement($this->dbconnect);
            $element->date($this->fonctions->formatdate($datetemp));
            $element->moment("a");
            if (strpos($jrs_feries, ";" . $datetemp . ";")) {
                // echo "C'est un jour férié = $datetemp <br>";
                $element->type("ferie");
                $element->info("jour férié");
            } elseif ((date("w", strtotime($datetemp)) == 0) or (date("w", strtotime($datetemp)) == 6)) {
                $element->type("WE");
                $element->info("week-end");
            } elseif (is_null($declarationTP)) {
                $element->type("nondec");
                $element->info("Période non déclarée");
            }            // On est dans le cas ou le statut n'est pas validé => C'est comme si on avait rien fait !!!
            elseif (strcasecmp($declarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) != 0) {
                $element->type("nondec");
                $element->info("Période non déclarée");
            } elseif ($declarationTP->enTP($element->date(), $element->moment())) {
                $element->type("tppar");
                $element->info("Temps partiel");
            } else {
                // Ici c'est une case blanche vide !! Il ne se passe rien
                $element->type("");
                $element->info("");
            }
            
            $element->agentid($agentid);
            $this->listeelement[$datetemp . "a"] = $element;
            unset($element);
            // echo "datetemp = " . strtotime($datetemp) . "<br>";
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
                                                                           // echo "On passe à la date : " .$datetemp . "( " . strtotime($datetemp) . ") <br>";
        }
        //$this->fonctions->time_elapsed("Planning : Après le for index=0", 11);
        
        // echo "Nbre d'élément = " . count($this->listeelement);
        // echo " " . date("H:i:s") . "<br>";
        // echo "Planning->Load : fulldeclarationTPliste = "; print_r($fulldeclarationTPliste); echo "<br>";
        
        if ($includecongeabsence)
        {
            //$this->fonctions->time_elapsed("Planning : Avant le agent->demandeliste", 11, true);
            $demandeliste = $agent->demandesliste($datedebut, $datefin);
            //$this->fonctions->time_elapsed("Planning : Après le agent->demandeliste", 11);
            $demande = new demande($this->dbconnect);
            //$this->fonctions->time_elapsed("Planning : Avant le foreach demandeliste", 11, true);
            foreach ((array) $demandeliste as $demandeid => $demande) 
            {
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
                            if ($demandetempmoment == 'm') {
                                // echo "Avant le new planningElement (bloc 'm') <br>";
                                unset($element);
                                $element = new planningelement($this->dbconnect);
                                $element->date($this->fonctions->formatdate($datetemp));
                                $element->moment("m");
                                $element->type($demande->type());
                                $element->statut($demande->statut());
                                $element->info($demande->typelibelle()); // motifrefus()
                                $element->agentid($agentid);
                                // echo "<br>Je set (matin) le demande id => " . $demande->id() ."<br>";
                                $element->demandeid($demande->id());
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
                                    }
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                }
                                $demandetempmoment = 'a';
                                unset($element);
                                // echo "Fin du traitement du demandetempmoment = 'matin' <br>";
                            }
                            // echo "datetemp = $datetemp demandedatefin = " . $this->fonctions->formatdatedb($demandedatefin) . " demandetempmoment = $demandetempmoment demandemomentfin = $demandemomentfin <br>";
                            if ($datetemp == $this->fonctions->formatdatedb($demandedatefin) and $demandetempmoment != $demandemomentfin)
                                $demandetempmoment = "";
                            // echo "demandetempmoment (apres le if - apres-midi)= " . $demandetempmoment . "<br>";
                            if ($demandetempmoment == 'a') {
                                // echo "Avant le new planningElement (bloc 'a') <br>";
                                unset($element);
                                $element = new planningelement($this->dbconnect);
                                $element->date($this->fonctions->formatdate($datetemp));
                                $element->moment("a");
                                $element->type($demande->type());
                                $element->statut($demande->statut());
                                $element->info($demande->typelibelle()); // motifrefus()
                                $element->agentid($agentid);
                                // echo "<br>Je set (apres midi) le demande id => " . $demande->id() ."<br>";
                                $element->demandeid($demande->id());
                                // echo "<br>Je l'ai fixé (apres midi) demande id => " . $element->demandeid() . "<br>";
                                // $element->couleur($result[16]); ==> La couleur est gérée par l'element du planning
                                if (! array_key_exists($datetemp . $demandetempmoment, $this->listeelement))
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                elseif ($this->listeelement[$datetemp . $demandetempmoment]->type() == "" or strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                    // Si la période n'est pas déclarée, on affiche l'element de demande de congés, mais on efface son id de demande car on ne sait pas recalculer le nombre de jours
                                    if (strcasecmp($this->listeelement[$datetemp . $demandetempmoment]->type(), "nondec") == 0) {
                                        $element->demandeid("");
                                    }
                                    $this->listeelement[$datetemp . $demandetempmoment] = $element;
                                }
                                unset($element);
                                // echo "Fin du traitement du demandetempmoment = 'après-midi' <br>";
                            }
                        }
                        $demandetempmoment = 'm';
                        // echo "la date apres le strtotime 1 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                        $timestamp = strtotime($datetemp);
                        $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
                                                                                       // echo "la date apres le strtotime 2 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                    }
                }
            }
            //$this->fonctions->time_elapsed("Planning : Après le foreach demandeliste", 11);
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
        //$this->fonctions->time_elapsed("Planning : Avant le while sur AbsenceRH", 11, true);
        while ($result = mysqli_fetch_row($query)) {
            $demandedatedeb = $this->fonctions->formatdate($result[1]);
            $demandedatefin = $this->fonctions->formatdate($result[2]);
            $demandemomentdebut = 'm';
            $demandemomentfin = 'a';
            $datetemp = $this->fonctions->formatdatedb($demandedatedeb);
            $demandetempmoment = $demandemomentdebut;
            while ($datetemp <= $this->fonctions->formatdatedb($demandedatefin)) {
                // echo "Dans le petit while <br>";
                if ($datetemp >= $this->fonctions->formatdatedb($datedebut) and $datetemp <= $this->fonctions->formatdatedb($datefin)) {
                    // echo "Avant le if == m... <br>";
                    if ($demandetempmoment == 'm') {
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
                        $demandetempmoment = 'a';
                        unset($element);
                    }
                    // echo "Avant le if ==a <br>";
                    if ($demandetempmoment == 'a') {
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
                        $demandetempmoment = 'm';
                        unset($element);
                    }
                }
                // echo "Apres le while petit <br>";
                $timestamp = strtotime($datetemp);
                $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
            }
        }
        //$this->fonctions->time_elapsed("Planning : Après le while sur AbsenceRH", 11);
        
        if ($includeteletravail)
        {
            //$this->fonctions->time_elapsed("Planning : Avant le foreach télétravail", 11, true);
            $datedebutdb = $this->fonctions->formatdatedb($datedebut);
            $datefindb = $this->fonctions->formatdatedb($datefin);
            $teletravailliste = $agent->teletravailliste($datedebutdb,$datefindb);
            $fulldatetheorique = array();
            foreach ((array)$teletravailliste as $teletravailid)
            {
                $teletravail = new teletravail($this->dbconnect);
                $teletravail->load($teletravailid);
                if ($teletravail->statut() == teletravail::STATUT_ACTIVE)
                {
                    $fulldatetheorique = array_merge($fulldatetheorique,$teletravail->datetheorique($datedebutdb,$datefindb));
                }
            }
            foreach ($fulldatetheorique as $arraydate)
            {
                $element = $this->getelement($arraydate[0], $arraydate[1]);
                if (!is_null($element) and $element->type() == '')
                {
                    if (!$this->fonctions->estjourteletravailexclu($agentid,$arraydate[0]))
                    {
                        $element->type('teletrav');
                        $element->info('Télétravail');
                    }
                }
            }
            
/*            
            foreach ((array)$this->listeelement as $element)
            {
                if ($element->type() == '')
                {
                    if ($agent->estenteletravail($element->date(), $element->moment()))
                    {
                        $element->type('teletrav');
                        $element->info('Télétravail');
                    }
                }
                
            }
*/
            //$this->fonctions->time_elapsed("Planning : Après le foreach télétravail", 11);
        }
        
        
        
        // echo "Fin de la procédure Load <br>";
        
        // echo "<br>Liste des éléments = " . print_r($this->listeelement,true) . "<br>";
        //$this->fonctions->time_elapsed("Planning : Fin de la fonction load", 10);
        
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
        //$this->fonctions->time_elapsed("Planning : Début de la fonction planninghtml",1,true);
        // echo "datedebut = $datedebut datefin = $datefin <br>";
        // $this->listeelement = null;
        //$this->fonctions->time_elapsed("Planning : Avant le planning->load",2,true);
        if (is_null($this->listeelement)) {
            // echo "Début chargement : " . date("d/m/Y H:i:s") . "<br>";
            $this->load($agentid, $datedebut, $datefin, $includeteletravail);
            // echo "Fin chargement : " . date("d/m/Y H:i:s") . "<br>";
        }
        //$this->fonctions->time_elapsed("Planning : Après le planning->load",2);
        
        
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
        
        //$this->fonctions->time_elapsed("Planning : Avant le foreach listeelement => Nombre élement = " . count($this->listeelement),2,true);
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
        }
        //$this->fonctions->time_elapsed("Planning : Après le foreach listeelement",2);
        $htmltext = $htmltext . "</tr>";
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "</div>";
        // echo "fin de plannig->planninghtml <br>";
        
        $tempdate = $this->fonctions->formatdatedb($datedebut);
        $tempannee = substr($tempdate, 0, 4);
        
        // echo "Avant affichage legende <br>";
        //$this->fonctions->time_elapsed("Planning : Avant l'affichage de la légende",2,true);
        if ($noiretblanc == false) {
            $htmltext = $htmltext . $this->fonctions->legendehtml($tempannee, $includeteletravail);
        }
        //$this->fonctions->time_elapsed("Planning : Après l'affichage de la légende",2);
        // echo "Apres affichage legende <br>";
        $htmltext = $htmltext . "<br>";
        
        $htmltext = $htmltext . "<br>";
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
        
        //$this->fonctions->time_elapsed("Planning : Fin de la fonction planninghtml",1);
        return $htmltext;
    }

    function agentpresent($agentid, $datedebut, $momentdebut, $datefin, $momentfin, $ignoreabsenceautodecla = FALSE)
    {
        // echo "Avant le load du planning => $agentid $datedebut $momentdebut $datefin $momentfin <br>";
        $listeelement = $this->load($agentid, $datedebut, $datefin);
        // echo "Apres le load <br>";
        $paslepremier = FALSE;
        $pasledernier = FALSE;
        if (strcasecmp($momentdebut, "m") != 0)
            $paslepremier = TRUE;
        if (strcasecmp($momentfin, "a") != 0)
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
        if (strcasecmp($momentdebut, "m") != 0) {
            $paslepremier = TRUE;
            // echo "On fixe paslepremier <br>";
        }
        if (strcasecmp($momentfin, "a") != 0) {
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
        if (is_array($affectationliste)) {
            // echo "affectationliste = " . print_r($affectationliste, true) . "<br>";
            $affectation = reset($affectationliste); // ATTENTION : Reset permet de récupérer le premier élément du tableau => On ne connait pas la clé
            $structure = new structure($this->dbconnect);
            $structure->load($affectation->structureid());
            $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
            $pdf->Cell(60, 10, utf8_decode('Service : ' . $nomstructure));
            $pdf->Ln();
        }
        $pdf->Ln(10);
        $pdf->Cell(60, 10, utf8_decode('Planning de  : ' . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom()));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10, '', true);
        $pdf->Cell(60, 10, utf8_decode('Edité le ' . date("d/m/Y")));
        $pdf->Ln(10);
        
        // echo "Avant le planning <br>";
        
        // ///création du planning suivant le tableau généré
        // /Création des entetes de colones contenant les 31 jours/////
        
        $pdf->Cell(30, 5, utf8_decode(""), 1, 0, 'C');
        for ($index = 1; $index <= 31; $index ++) {
            $pdf->Cell(8, 5, utf8_decode($index), 1, 0, 'C');
        }
        $pdf->Ln(5);
        
        // echo "Avant le tableau <br>";
        // //boucle sur chaque mois du tableau
        $month = date("m", strtotime($this->fonctions->formatdatedb($datedebut)));
        $currentmonth = "";
        foreach ($this->listeelement as $key => $planningelement) {
            // echo "avant le month = <br>";
            $month = date("m", strtotime($this->fonctions->formatdatedb($planningelement->date())));
            
            // echo "month = $month currentmonth = $currentmonth <br>";
            
            if ($month != $currentmonth) {
                $monthname = $this->fonctions->nommois($planningelement->date()) . " " . date("Y", strtotime($this->fonctions->formatdatedb($planningelement->date())));
                if ($currentmonth != "")
                    $pdf->Ln(5);
                    $pdf->Cell(30, 5, utf8_decode($monthname), 1, 0, 'C');
                
                $currentmonth = $month;
            }
            // echo "avant le list... <br>";
            // -------------------------------------------
            // Convertir les couleur HTML en RGB
            // -------------------------------------------
            list ($col_part1, $col_part2, $col_part3) = $this->fonctions->html2rgb($planningelement->couleur($noiretblanc));
            $pdf->SetFillColor($col_part1, $col_part2, $col_part3);
            if (strcasecmp($planningelement->moment(), "m") != 0)
                $pdf->Cell(4, 5, utf8_decode(""), 'TBR', 0, 'C', 1);
            else
                $pdf->Cell(4, 5, utf8_decode(""), 'TBL', 0, 'C', 1);
            // echo "Apres les demies-cellules <br>";
        }
        
        // ///MISE EN PLACE DES LEGENDES DU PLANNING
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 7, '', true);
        $pdf->SetTextColor(0);
        // ////Mise en place de la légende couleurs pour les congés
        
        // echo "Avant legende <br>";
        $anneeref = date("Y", strtotime($this->fonctions->formatdatedb($datedebut)));
        $this->fonctions->legendepdf($pdf,$anneeref,$includeteletravail);
        // echo "Apres legende <br>";
        
        $pdf->Ln(8);
        ob_end_clean();
        $pdf->Output("","planning_agent.pdf");
        // $pdf->Output('demande_pdf/autodeclaration_num'.$ID_AUTODECLARATION.'.pdf');
    }
    
    function nbjoursteletravail($agentid, $datedebut, $datefin)
    {
        $elementliste = $this->load($agentid, $datedebut, $datefin, true);
        $nbjoursteletravail = 0;
        foreach ($elementliste as $element)
        {
            if ($element->type() == 'teletrav')
            {
                $nbjoursteletravail = $nbjoursteletravail + 0.5;
            }
        }
        return $nbjoursteletravail;
    }
    
}

?>