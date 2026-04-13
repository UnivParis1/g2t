<?php

class disponibilite
{
    public $elementdebut = null;
    public $elementfin = null;
}

use Fpdf\Fpdf as FPDF;

class planning
{
    const TYPE_AGENT = 'agent';
    const TYPE_STRUCTURE = 'structure';

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

        // Timer pour le début du chargement => Permet de mesurer la performance (voir fin fonction)
        //$timerdebut = hrtime(true); 

        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        
        $this->agent = $agent;
        
        // // Par défaut, un agent ne travaille pas le samedi
        // $travailsamedi = false;
        // if (method_exists($agent,'travailsamedi'))
        // {
        //     // Si la méthode existe, alors on regarde quelle est sa valeur
        //     $travailsamedi = $agent->travailsamedi();
        // }
        // Par défaut, un agent ne travaille pas le dimanche
        // $travaildimanche = false;
        // if (method_exists($agent,'travaildimanche'))
        // {
        //     // Si la méthode existe, alors on regarde quelle est sa valeur
        //     $travaildimanche = $agent->travaildimanche();
        // }
        
        $this->datedebut = $datedebut;
        $this->datefin = $datefin;
        
        // Attention : La fonction retourne les jours fériés de l'année de référence et les "amplitudes" années après l'année de référence (par défaut 1 an)
        // On calcule donc l'amplitude dynamiquement
        $anneereffin = $this->fonctions->anneeref($datefin);
        $anneerefdebut = $this->fonctions->anneeref($datedebut);
        $amplitude = $anneereffin - $anneerefdebut;
        $jrs_feries = $this->fonctions->joursferies($anneerefdebut, $amplitude);
        ///////////////////////////////////
        // ATTENTON : La date des jours fériés est portée par la clé du tableau et non plus par la valeur => test à faire avec : isset($jrs_feries[$datecherchee])
        ///////////////////////////////////

        //echo "datedebut = $datedebut    datefin = $datefin  <br>"; echo "amplitude = $amplitude <br>"; print_r($jrs_feries); echo '<br>';
        
        unset($listeelement);
        $affectation = null;
        $fulldeclarationTPliste = null;
        
        $nbre_jour = $this->fonctions->nbjours_deux_dates($datedebut, $datefin);
        $datetemp = $this->fonctions->formatdatedb($datedebut);
        $declarationTP = null;
        $fulldeclarationTPliste = array();
        $ignoremissinggstructure = false;
        //if ($includeteletravail)
        //{
        //    $ignoremissinggstructure = true;
        //}
        $affectationliste = $agent->affectationliste($datedebut, $datefin, $ignoremissinggstructure);
        
        foreach ((array) $affectationliste as $affectation) 
        {
            $declarationTPliste = $affectation->declarationTPliste($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin),declarationTP::DECLARATIONTP_VALIDE);
            $fulldeclarationTPliste[$affectation->affectationid()] = $declarationTPliste;
        }
        if (is_array($affectationliste))
        {
            $affectation = reset($affectationliste); // On récupère la première affectation
        }
        
        $periodeoblig = null;
        /////////////////////////////////////////////////////////
        /// CREATION DES ELEMENTS ET INITIALISATION (WE, Fériés, non déclaré, temps partiel, périodes obligatoires)
        /////////////////////////////////////////////////////////
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
            if (! is_null($declarationTP)) 
            {
                // On regarde si la déclaration de TP est toujours valide
                if ($this->fonctions->formatdatedb($declarationTP->datefin()) < $this->fonctions->formatdatedb($datetemp)) 
                {
                    // Non elle n'est plus valide => On la met à null
                    $declarationTP = null;
                }
            }
            
            // Si on a une affectation courante (soit parce que c'est la même qu'au tour d'avant, soit on vient de la charger à partir de la liste 'affectationliste'
            if (! is_null($affectation)) 
            {
                // On récupère la liste des declaration de TP pour cette affectation
                $declarationTPliste = $fulldeclarationTPliste[$affectation->affectationid()];
                // On recherche s'il y a une declaration de TP correspondant au jour courant
                foreach ((array) $declarationTPliste as $tempdeclarationTP) 
                {
                    if (($this->fonctions->formatdatedb($tempdeclarationTP->datedebut()) <= $this->fonctions->formatdatedb($datetemp)) and ($this->fonctions->formatdatedb($tempdeclarationTP->datefin()) >= $this->fonctions->formatdatedb($datetemp))) {
                        // Si la déclaration de TP est validée
                        ////////////////////////
                        // Le test sur la validité de la déclaration de TP est inutile car on a filtré dans la select que les declarationTP::DECLARATIONTP_VALIDE
                        //if (strcasecmp((string)$tempdeclarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) == 0) {
                            // C'est la bonne declaration de TP !
                            $declarationTP = $tempdeclarationTP;
                            break;
                        //}
                    }
                }
            }

            $tabmoment = array(fonctions::MOMENT_MATIN,fonctions::MOMENT_APRESMIDI);
            $moment = reset($tabmoment);
            while ($moment !== false)
            {
                $element = new planningelement($this->dbconnect);
                $element->date($this->fonctions->formatdate($datetemp));
                $element->moment($moment);

                $travailsamedi = $agent->travailsamedi($datetemp);
                $travaildimanche = $agent->travaildimanche($datetemp);
               
                //if (in_array($datetemp,$jrs_feries))
                // On cherche si la clé existe et non plus la valeur
                if (isset($jrs_feries[$datetemp]))
                {
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
                }
                elseif (is_null($affectation)) // On est dans le cas ou l'agent ne travaille plus dans l'établissement
                {
                    // var_dump("element id = " . $element->id() . " et pas d'affectation");
                    $element->type("nondec");
                    $element->info("Sans activité"); // "Période non déclarée"
                    $extraclass = $element->htmlextraclass();
                    $element->htmlextraclass(trim($extraclass . " " . trim(planningelement::HTML_CLASS_SANSACTIVITE)));
                    // var_dump("element id = " . $element->id() . " :  htmlextraclass = " . $element->htmlextraclass());
                }
                elseif (is_null($declarationTP)) // On est dans le cas ou aucune déclaration de TP n'est faite
                {
                    $element->type("nondec");
                    $element->info("Période non déclarée");
                }            
                elseif (strcasecmp((string)$declarationTP->statut(), declarationTP::DECLARATIONTP_VALIDE) != 0) // On est dans le cas ou le statut n'est pas validé => C'est comme si on avait rien fait !!!
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
                //$this->listeelement[$datetemp . $moment] = $element;
                $this->listeelement[$element->id()] = $element;

                // On charge les périodes obligatoires
                if ($element->type()=='')
                //if (!in_array($element->type(), array("WE","ferie","tppar")))
                {
                    $anneeref = $this->fonctions->anneeref($element->date());
                    // Si les périodes obligatoires sont déjà chargées pour l'année de référence
                    if (is_null($periodeoblig) or $periodeoblig->anneeref()!=$anneeref)
                    {
                        unset($periodeoblig);
                        $periodeoblig = new periodeobligatoire($this->dbconnect);
                        $periodeoblig->load($anneeref);
                    }
                    if ($periodeoblig->testsuperposeperiode($element->date(),$element->date()))
                    {
                        $extraclass = $element->htmlextraclass();
                        $element->htmlextraclass(trim($extraclass . " " . trim(planningelement::HTML_CLASS_PERIODEOBLIGATOIRE)));
                    }
                }

                unset($element);
                // On passe au moment suivant dans le tableau ou false si on est au bout
                $moment = next($tabmoment);
            }
            
            // echo "datetemp = " . strtotime($datetemp) . "<br>";
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
            // echo "On passe à la date : " .$datetemp . "( " . strtotime($datetemp) . ") <br>";
        }
        
        //var_dump("element HTMLClass => " . $this->listeelement["20250102m"]->htmlextraclass());

        /////////////////////////////////////////////////////////
        /// INTEGRATION DES CONGES, ABSENCE, ABSENCERH
        /////////////////////////////////////////////////////////
        // On récupère les demandes d'absence, les congés et les absences de type "télétravail hors convention"
        if ($includecongeabsence or $includeabsenceteletravail)
        {
            $demandeliste = $agent->demandesliste($datedebut, $datefin);
        }
        // On fusionne le tableau précédent avec les absences RH (converties sous forme de demande typées 'harp')
        /////////////////////////////////////////////////////
        /////// IMPORTANT ///////////////////////////////////
        // LORS DE LA FUSION ON DOIT POSITIONNER LES ABSENCES RH EN PREMIER ET ENSUITE LES DEMANDE 'NORMALES'
        // SINON BUG LORS DE LA CONSTRUCTION DU PLANNING => LES ABSENCES RH NE SONT PAS CHARGEES
        /////////////////////////////////////////////////////
        $demandeliste = array_merge($agent->absencerhliste($datedebut, $datefin),(array)$demandeliste );
        foreach ((array) $demandeliste as $demande) 
        {
            // Si on ne demande que les absences de type télétravail hors convention
            if (!$includecongeabsence and $includeabsenceteletravail)
            {
                // Si le parent de l'absence n'est pas de type 'teletravHC' => On ne le traite pas
                if (TABCOULEURPLANNINGELEMENT[$demande->type()]['parentid'] != 'teletravHC')
                {
                    continue;
                }
            }
            
            if (($demande->statut() == demande::DEMANDE_VALIDE) or ($demande->statut() == demande::DEMANDE_ATTENTE) or ($demande->statut() == demande::DEMANDE_VALID_RH)) 
            {
                $demandedatedeb = $this->fonctions->formatdate($demande->datedebut());
                $demandedatefin = $this->fonctions->formatdate($demande->datefin());
                $demandemomentdebut = $demande->moment_debut();
                $demandemomentfin = $demande->moment_fin();
                $datetemp = $this->fonctions->formatdatedb($demandedatedeb);

                // Si la date de début de la demande est avant la période du planning, on la défini comme le début du planning
                if ($datetemp < $this->fonctions->formatdatedb($datedebut))
                {
                    $datetemp = $this->fonctions->formatdatedb($datedebut);
                    $demandemomentdebut = fonctions::MOMENT_MATIN;
                }
                // Si la date de fin de la demande est après la période du planning, on la défini comme la fin du planning
                if ($this->fonctions->formatdatedb($demandedatefin) > $this->fonctions->formatdatedb($datefin))
                {
                    $demandedatefin = $datefin;
                    $demandemomentfin = fonctions::MOMENT_APRESMIDI;
                }

                $tabmoment = array(fonctions::MOMENT_MATIN,fonctions::MOMENT_APRESMIDI);
                // On parcourt tous les jours entre datebebut et datefin de la demande
                while ($datetemp <= $this->fonctions->formatdatedb($demandedatefin)) 
                {
                    $moment = reset($tabmoment);
                    while ($moment !== false)
                    {
                        // On ne traite pas la 1ere matinée ou la dernière après-midi si elles ne sont pas inclues dans la demande
                        if (($datetemp == $this->fonctions->formatdatedb($demandedatedeb) and $demandemomentdebut != fonctions::MOMENT_MATIN and $moment == fonctions::MOMENT_MATIN)
                            or ($datetemp == $this->fonctions->formatdatedb($demandedatefin) and $demandemomentfin != fonctions::MOMENT_APRESMIDI and $moment == fonctions::MOMENT_APRESMIDI))
                        {
                            //var_dump("Je ne traite pas. datetemp = $datetemp   moment = $moment  datedebut = " . $this->fonctions->formatdatedb($demandedatedeb) . "   momentdebut = $demandemomentdebut   datefin = " . $this->fonctions->formatdatedb($demandedatefin) . "  momentfin = $demandemomentfin");
                        }
                        else
                        {
                            unset($element);
                            $element = new planningelement($this->dbconnect);
                            $element->date($this->fonctions->formatdate($datetemp));
                            $element->moment($moment);
                            $element->type($demande->type());
                            $element->statut($demande->statut());
                            // Si la demande est un type 'atten' (en attente de validation) et que le statut précise que c'est par la DRH (demande::DEMANDE_VALID_RH)
                            // ==> On force le type de l'élément à 'attenrh'
                            if (!$this->fonctions->estunconge($demande->type()) and $demande->statut()==demande::DEMANDE_VALID_RH)
                            {
                                $element->type('attenrh');
                            }

                            if ($demande->type()=='harp')
                            {
                                $element->info($demande->commentaire()); // motifrefus()
                            }
                            else
                            {
                                $element->info($demande->typelibelle()); // motifrefus()
                            }
                            // if ($element->statut() == demande::DEMANDE_VALID_RH)
                            // {
                            //     $element->info($element->info() . " - En attente de validation par la DRH");
                            // }
                            $element->agentid($agentid);
                            $element->demandeid($demande->id());
                            $element->demande($demande);

                            // On récupère les classes HTML de l'élément qui est déjà présent dans le planning (<=> listeelement)
                            if (array_key_exists($element->id(), $this->listeelement))
                            {
                                $element->htmlextraclass($this->listeelement[$element->id()]->htmlextraclass());
                            }

                            if (! array_key_exists($element->id(), $this->listeelement))
                            {
                                //$this->listeelement[$datetemp . $moment] = $element;
                                $this->listeelement[$element->id()] = $element;
                            }
                            elseif ($this->listeelement[$element->id()]->type() == "" or strcasecmp((string)$this->listeelement[$element->id()]->type(), "nondec") == 0) 
                            {
                                // Si la période n'est pas déclarée, on affiche l'element de demande de congés, mais on efface son id de demande car on ne sait pas recalculer le nombre de jours
                                if (strcasecmp((string)$this->listeelement[$element->id()]->type(), "nondec") == 0) 
                                {
                                    //var_dump("On vérifie le extraClass => " . $element->htmlextraclass());
                                    $extraclass = trim($element->htmlextraclass() . " " . trim(planningelement::HTML_CLASS_PERIODENONDECLA));
                                    //var_dump("Le nouvel extraClass = $extraclass");
                                    $element->htmlextraclass($extraclass);

                                    // $element->demandeid("");
                                    // // On reset l'objet demande de l'élément
                                    // $element->demande("");
                                }
                                // Si l'élément actuel est sans activité => On ne doit pas charger l'absence/le congé.... => Car l'agent ne travaille pas (pas en activité)
                                if (stripos(" " . $element->htmlextraclass() . " ",planningelement::HTML_CLASS_SANSACTIVITE)===false)
                                {
                                    $this->listeelement[$element->id()] = $element;
                                }
                            }
                        }
                        // On passe au moment suivant dans le tableau ou false si on est au bout
                        $moment = next($tabmoment);
                    }

                    // echo "la date apres le strtotime 1 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                    $timestamp = strtotime($datetemp);
                    $datetemp = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour suivant
                    // echo "la date apres le strtotime 2 = " . strtotime($datetemp) . " datetemp= " . $datetemp . "<br>";
                }
                unset($element);
            }
        }
        
        // var_dump("element HTMLClass => " . $this->listeelement["20250102m"]->htmlextraclass());

        /////////////////////////////////////////////////////////
        /// INTEGRATION DU TELETRAVAIL
        /////////////////////////////////////////////////////////
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
                // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Load boucle date théorique : Date = " . $arraydate[0] . " Moment = " . $arraydate[1]));
                $element = $this->getelement($arraydate[0], $arraydate[1]);
                if (!is_null($element))
                {
                    if ($element->type() == '')
                    {
                        // Si il n'y a pas d'exception ou si l'exception est en attente de validation
                        $ttexception = $this->fonctions->estjourteletravailexclu($agentid,$arraydate[0],$arraydate[1], $statut);
                        if ($ttexception===false or $statut == ttexception::STATUT_ENATTENTE)
                        {
                            // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Load : Agent = $agentid Date = " . $arraydate[0] . " Moment = " . $arraydate[1] . "  Statut = $statut"));
                            $element->type('teletrav');
                            $extraclass = $element->htmlextraclass();
                            $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_TELETRAVAIL);
                            //$element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL);
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
                            $infotmp = $element->info() . '';
                            if (trim($infotmp)!='' and $statut == ttexception::STATUT_ENATTENTE)
                            {
                                $ttexceptionlist = $this->fonctions->listejoursteletravailexclus($agentid,$arraydate[0],$element->moment(), $arraydate[0], $element->moment(),false);
                                $htmlextradata = $element->htmlextradata();
                                if ($ttexceptionlist[0]->dateremplacement . '' != '')
                                {
                                    $infotmp = $infotmp . " - Déplacement en attente de validation vers le " . $this->fonctions->formatdate($ttexceptionlist[0]->dateremplacement);
                                    $htmlextradata = $htmlextradata . " data-depladatecible=\"" . $this->fonctions->formatdate($ttexceptionlist[0]->dateremplacement) . "\" ";

                                    if ($ttexceptionlist[0]->momentremplacement . "" != "")
                                    {
                                        // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Planning::Load => le moment de remplacement = " . $ttexceptionlist[0]->momentremplacement));
                                        $infotmp = $infotmp . " " . $this->fonctions->nommoment($ttexceptionlist[0]->momentremplacement);
                                        $htmlextradata = $htmlextradata . " data-deplamomentcible=\"" . $this->fonctions->nommoment($ttexceptionlist[0]->momentremplacement) . "\" ";
                                    }
                                    else
                                    {
                                        $htmlextradata = $htmlextradata . " data-deplamomentcible=\"" . $this->fonctions->nommoment($ttexceptionlist[0]->momentremplacement) . "\" ";
                                    }
                                }
                                else
                                {
                                    $infotmp = $infotmp . " - Suppression en attente de validation";
                                }
                                $element->info($infotmp);
                                $extraclass = $element->htmlextraclass();
                                $element->htmlextradata($htmlextradata);
                                $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE);
                            }
                            $element->typeconvention($arraydate[2]); // On ajoute le type de convention de télétravail
                        }
                        else // L'élement est un jour de télétravail mais il est exclu => il ne s'affichera pas en rose dans le planning
                        {
                            $extraclass = $element->htmlextraclass();
                            $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_EXCLUSION);

                            // On va ajouter un texte pour expliquer que c'est une ancienne occurrence de télétravail qui a été déplacée/annulée.
                            $infotmp = $element->info() . '';
                            $htmlextradata = $element->htmlextradata();
                            if ($ttexception->momentorigine == '')
                            {
                                $infotmp = $infotmp . 'Journée de télétravail';
                            }
                            else
                            {
                                $infotmp = $infotmp . 'Demie journée de télétravail';
                            }

                            if ($ttexception->dateremplacement . '' != '')
                            {
                                $infotmp = $infotmp . " déplacée vers le " . $this->fonctions->formatdate($ttexception->dateremplacement);
                                $htmlextradata = $htmlextradata . " data-depladatecible=\"" . $this->fonctions->formatdate($ttexception->dateremplacement) . "\" ";

                                if ($ttexception->momentremplacement . "" != "")
                                {
                                    // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Planning::Load => le moment de remplacement = " . $ttexceptionlist[0]->momentremplacement));
                                    $infotmp = $infotmp . " " . $this->fonctions->nommoment($ttexception->momentremplacement);
                                    $htmlextradata = $htmlextradata . " data-deplamomentcible=\"" . $this->fonctions->nommoment($ttexception->momentremplacement) . "\" ";
                                }
                                else
                                {
                                    $htmlextradata = $htmlextradata . " data-deplamomentcible=\"" . $this->fonctions->nommoment($ttexception->momentremplacement) . "\" ";
                                }
                            }
                            else
                            {
                                $infotmp = $infotmp . " supprimée et non reportée";
                            }
                            $element->info($infotmp);

                            //$element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_EXCLUSION);
                        }
                    }
                    else
                    {
                        // C'est en théorie une 1/2 journée de télétravail, mais il y a quelque chose à la place
                        // => On indique quand même que c'est un jour de télétravail théorique
                        $extraclass = $element->htmlextraclass();
                        $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN);
    
                        //$element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN);
                    }
                }
            }

            $listplaningelement = $this->planning();
            $ttdeplaceliste = $agent->teletravaildeplaceliste($datedebut, $datefin);
            foreach ($ttdeplaceliste as $ttexception)
            {
                if ($ttexception->dateremplacement . '' == '')
                {
                    // Si  aucune date de remplacement n'est définie => On a supprimé le jour de télétravail donc on ne fait rien
                    continue;
                }

                // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Exception info : Statut = " . $ttexception->statut));
                if ($ttexception->statut == ttexception::STATUT_VALIDE)
                {
                    if ($ttexception->momentremplacement == '' or $ttexception->momentremplacement == fonctions::MOMENT_MATIN)
                    {
                        $momentremplacement = fonctions::MOMENT_MATIN;
                        $element = $listplaningelement[$this->fonctions->formatdatedb($ttexception->dateremplacement) . $momentremplacement];
                        if ($element->type() == '')
                        {
                            $element->type('teletrav');
                            if ($ttexception->momentorigine == '')
                            {
                                $element->info('Journée de télétravail déplacée du ' . $this->fonctions->formatdate($ttexception->dateorigine));                        
                            }
                            else
                            {
                                $element->info('Demie journée de télétravail déplacée du ' . $this->fonctions->formatdate($ttexception->dateorigine) . ' ' . $this->fonctions->nommoment($ttexception->momentorigine));
                            }
                            // L'élement est un jour de télétravail mais il est deplace => il n'est pas clicable dans le planning 
                            $extraclass = $element->htmlextraclass();
                            $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_DEPLACE);

                            //$element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_DEPLACE);
                        }
                    }
                    if ($ttexception->momentremplacement == '' or $ttexception->momentremplacement == fonctions::MOMENT_APRESMIDI)
                    {
                        $momentremplacement = fonctions::MOMENT_APRESMIDI;
                        $element = $listplaningelement[$this->fonctions->formatdatedb($ttexception->dateremplacement) . $momentremplacement];
                        if ($element->type() == '')
                        {
                            $element->type('teletrav');
                            if ($ttexception->momentorigine == '')
                            {
                                $element->info('Journée de télétravail déplacée du ' . $this->fonctions->formatdate($ttexception->dateorigine));                        
                            }
                            else
                            {
                                $element->info('Demie journée de télétravail déplacée du ' . $this->fonctions->formatdate($ttexception->dateorigine) . ' ' . $this->fonctions->nommoment($ttexception->momentorigine));
                            }
                            // L'élement est un jour de télétravail mais il est deplace => il n'est pas clicable dans le planning 
                            $extraclass = $element->htmlextraclass();
                            $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_DEPLACE);

                            //$element->htmlextraclass(planningelement::HTML_CLASS_TELETRAVAIL . ' ' . planningelement::HTML_CLASS_DEPLACE);
                        }
                    }
                }
                else
                {
                    if ($ttexception->momentremplacement == '' or $ttexception->momentremplacement == fonctions::MOMENT_MATIN)
                    {
                        $momentremplacement = fonctions::MOMENT_MATIN;
                        $element = $listplaningelement[$this->fonctions->formatdatedb($ttexception->dateremplacement) . $momentremplacement];
                        $extraclass = $element->htmlextraclass();
                        $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE);

                        $infotmp = $element->info() . '';
                        $infotmp = $infotmp . "Déplacement télétravail en attente de validation (" . $this->fonctions->formatdate($ttexception->dateorigine);
                        if ($ttexception->momentorigine . "" != "")
                        {
                            $infotmp = $infotmp . " " . $this->fonctions->nommoment($ttexception->momentorigine);
                        }
                        $infotmp = $infotmp . ")";
                        $element->info($infotmp);
                    }
                    if ($ttexception->momentremplacement == '' or $ttexception->momentremplacement == fonctions::MOMENT_APRESMIDI)
                    {
                        $momentremplacement = fonctions::MOMENT_APRESMIDI;
                        $element = $listplaningelement[$this->fonctions->formatdatedb($ttexception->dateremplacement) . $momentremplacement];
                        $extraclass = $element->htmlextraclass();
                        $element->htmlextraclass($extraclass . " " . planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE);

                        $infotmp = $element->info() . '';
                        $infotmp = $infotmp . "Déplacement télétravail en attente de validation (" . $this->fonctions->formatdate($ttexception->dateorigine);
                        if ($ttexception->momentorigine . "" != "")
                        {
                            $infotmp = $infotmp . " " . $this->fonctions->nommoment($ttexception->momentorigine);
                        }
                        $infotmp = $infotmp . ")";
                        $element->info($infotmp);
                    }
                }
            }
        }

        // Timer pour le fin du chargement => Permet de mesurer la performance (voir ci-dessous)
        //$timerfin = hrtime(true);
        //$eta=$timerfin-$timerdebut;
        //var_dump("Durée du chargement du planning : " . round($eta/1e+6) . " millisecondes.");          

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
        } 
        else
        {
            return $this->listeelement;
        }
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
    
    
    function planninghtml($agentid, $datedebut, $datefin, $clickable = FALSE, $showpdflink = TRUE, $noiretblanc = FALSE, $includeteletravail = FALSE, $dbclickable = false)
    {
        function adddummyelement(int $mois,int $annee)
        {
            global $fonctions;

            $nbjoursdansmois = $fonctions->nbr_jours_dans_mois($mois, $annee);
            if ($nbjoursdansmois < 31)
            {
                $colspan = 2*(31-$nbjoursdansmois);
                return "<td colspan=$colspan class='planningelement_matin planningelement_aprem dummyelement'><span data-tip='Date invalide'>&nbsp;</span></td>";
                // $htmltext = $htmltext . "<td colspan=$colspan class='planningelement_matin planningelement_aprem' style='background: repeating-linear-gradient(135deg, #000000, #000000 6px,#bdbf10 6px, #bdbf10 12px ) !important; '></td>";
            }
            return '';
        }

        $agent = new agent($this->dbconnect);
        $agent->load($agentid);

        //$this->fonctions->time_elapsed("Début de la fonction planninghtml", __METHOD__, true);
        // echo "datedebut = $datedebut datefin = $datefin <br>";
        // $this->listeelement = null;
        if (is_null($this->listeelement)) 
        {
            //$timerdebut = hrtime(true); 
            $this->load($agentid, $datedebut, $datefin, $includeteletravail);
            // $timerfin = hrtime(true);
            // $eta=$timerfin-$timerdebut;
            // var_dump("Durée du chargement : " . round($eta/1e+6) . " millisecondes.");          
        }
        
        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ((array)$listecateg as $keycateg => $nomcateg)
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
        //var_dump($listeabs);
        
        $agentstruct = new structure($this->dbconnect);
        $indexjroblig = "";
        if ($agentstruct->load($agent->structureid()))
        {
            $indexjroblig = $agentstruct->jourpresenceobligatoire();
        }

        $htmltext = "";
        $htmltext = $htmltext . "<div id='planning'>";
        $htmltext = $htmltext . "<table class='tableau " . self::TYPE_AGENT . "' id='tab_agent_" . $agentid . "_" . $this->fonctions->formatdatedb($datedebut) ."' data-agentname='". htmlentities($agent->identitecomplete()) . "' data-indexjroblig = '" . $indexjroblig . "'><thead>";
        $month = date("m", strtotime($this->fonctions->formatdatedb($datedebut)));
        $currentyear = date("Y", strtotime($this->fonctions->formatdatedb($datedebut)));
        $currentmonth = "";
        $htmltext = $htmltext . "<tr class='entete'><th scope='col'>Mois</th>";
        for ($indexjrs = 0; $indexjrs < 31; $indexjrs ++) 
        {
            // echo "indexjrs = $indexjrs <br>";
            $htmltext = $htmltext . "<th scope='col' colspan='2'>" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</th>";
        }
        $htmltext = $htmltext . "</tr>";
        $htmltext = $htmltext . "</thead><tbody>";
        $elementlegende = array();
        foreach ($this->listeelement as $key => $planningelement) 
        {
            $month = date("m", strtotime($this->fonctions->formatdatedb($planningelement->date())));
            
            // echo "month = $month monthfin = $monthfin currentmonth = $currentmonth <br>";
            if ($month != $currentmonth) 
            {
                if ($currentmonth != "")
                {
                    $htmltext = $htmltext . adddummyelement($currentmonth, $currentyear);
                    $htmltext = $htmltext . "</tr>\n<tr class='ligneplanning'>";
                }
                else
                {
                    $htmltext = $htmltext . "\n<tr class='ligneplanning'>";
                }
                if (intval($month) < intval($currentmonth))
                {
                    $currentyear = $currentyear+1;
                }
                // $monthname = $this->fonctions->nommois($planningelement->date()) . " " . date("Y", strtotime($this->fonctions->formatdatedb($planningelement->date())));
                $currentmonth = $month;
                $monthname = $this->fonctions->nommoisparindex($currentmonth) . " " . $currentyear; // $this->fonctions->nommois($planningelement->date()) . " " . $currentyear;
                $htmltext = $htmltext . "<th scope='row' class='leftaligntext'>" . $monthname . "</th>";
                // $currentyear = date("Y", strtotime($this->fonctions->formatdatedb($planningelement->date())));
            }

            $htmltext = $htmltext . $planningelement->html($clickable, null, $noiretblanc, $dbclickable);

            if (!in_array($planningelement->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
            {
                if (array_key_exists($planningelement->type(),$listeabs))
                {
                    // Si c'est une absence dans la catégorie "télétravail hors convention"
                    if (strcmp((string)$planningelement->parenttype(),'teletravHC')==0)
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

        // Si le dernier élement n'est pas le 31 => Il faut compléter le mois avec des cases DUMMY jusqu'au 31
        $htmltext = $htmltext . adddummyelement($currentmonth, $currentyear);

        $htmltext = $htmltext . "</tr>";
        $htmltext = $htmltext . "</tbody></table>";
        $htmltext = $htmltext . "</div>";
        // echo "fin de plannig->planninghtml <br>";
        
        $tempdate = $this->fonctions->formatdatedb($datedebut);
        $tempannee = substr($tempdate, 0, 4);
        
        //var_dump($elementlegende);
        
        // echo "Avant affichage legende <br>";
        if ($noiretblanc == false) 
        {
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
        {
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='yes'>";
        }
        else
        {
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='no'>";
        }
        $htmltext = $htmltext . "</form>";
        $htmltext = $htmltext . "<form name='userpreviousplanningpdf_" . $agentid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
        $htmltext = $htmltext . "<input type='hidden' name='hide_teletravail_". $agentid . "' id='hidden_input_teletravail_". $agentid . "' value='off'>";
        $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='previous' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . ($tempannee - 1) . "'>";
        if ($includeteletravail)
        {
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='yes'>";
        }
        else
        {
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='no'>";
        }
        $htmltext = $htmltext . "</form>";
                
        
        if ($showpdflink == TRUE) 
        {
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
        if (strcasecmp((string)$momentdebut, fonctions::MOMENT_MATIN) != 0)
            $paslepremier = TRUE;
            if (strcasecmp((string)$momentfin, fonctions::MOMENT_APRESMIDI) != 0)
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
                if ($element->type() == "" or strcasecmp((string)$element->type(), "WE") == 0 or strcasecmp((string)$element->type(), "ferie") == 0 or strcasecmp((string)$element->type(), "tppar") == 0) {
                    // On ne fait rien si c'est vide, un WE, un jour férié ou un temp partiel
                } elseif ($ignoreabsenceautodecla == TRUE and strcasecmp((string)$element->type(), "nondec") == 0) {
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
        // Si le nombre d'éléments du planning est 0 => On doit charger le planning
        // Sinon, il est déjà chargé
        if (count((array)$this->listeelement)==0)
        {
            //var_dump('Le planning est vide => On le charge');
            $listeelement = $this->load($agentid, $datedebut, $datefin);
        }
        else
        {
            $listeelement = $this->listeelement;
        }
        $paslepremier = FALSE;
        $pasledernier = FALSE;
        if (strcasecmp((string)$momentdebut, fonctions::MOMENT_MATIN) != 0) {
            $paslepremier = TRUE;
            // echo "On fixe paslepremier <br>";
        }
        if (strcasecmp((string)$momentfin, fonctions::MOMENT_APRESMIDI) != 0) {
            $pasledernier = TRUE;
            // echo "On fixe pasledernier <br>";
        }
        $index = 0;
        $nbredemijour = 0;
        // Tableau des types d'élément où l'agent doit être considéré comme disponible (<=> pas en congés, pas absent, pas en temps partiel, pas en WE, ....)
        $arraytypedispo = array('', 'teletrav');

        $datedebutdb = $this->fonctions->formatdatedb($datedebut);
        $datefindb = $this->fonctions->formatdatedb($datefin);
        foreach ((array) $listeelement as $key => $element) 
        {
            $pasdetraitement = FALSE;

            $elementdatedb = $this->fonctions->formatdatedb($element->date());
            // Si la date de l'élément n'est pas dans la période demandée (donc elementdate < datedebut ou elementdate > datefin)
            if ($elementdatedb < $datedebutdb or $elementdatedb > $datefindb)
            {
                $pasdetraitement = TRUE;
            }
            // Si on est sur le MATIN de la date de début mais qu'on doit l'exclure
            elseif ($elementdatedb == $datedebutdb and $element->moment() == fonctions::MOMENT_MATIN and $paslepremier) 
            {
                $pasdetraitement = TRUE;
            }
            // Si on est sur l'APRES-MIDI de la date de fin mais qu'on doit l'exclure
            elseif ($elementdatedb == $datefindb and $element->moment() == fonctions::MOMENT_APRESMIDI and $pasledernier) 
            {
                $pasdetraitement = TRUE;
            }

            // if ($index == 0 and $paslepremier) {
            //     $pasdetraitement = TRUE;
            //     // echo "pas de traitement du premier !! <br>";
            // }
            // // echo "Index = ". $index . "<br>";
            // // echo "count($listeelement) = " . count($listeelement) . "<br>";
            // // echo "key = " . $key . "<br>";
            // if ($index == (count($listeelement) - 1) and $pasledernier) {
            //     $pasdetraitement = TRUE;
            //     // echo "pas de traitement du dernier !! <br>";
            // }

            if (! $pasdetraitement) 
            {
                // echo "On traite l'élément... Type =: " . $element->type() . " <br>";
                //if ($element->type() == "") => Test incomplet car on doit prendre en compte le télétravail potentiellement chargé dans le planning
                if (in_array($element->type(), $arraytypedispo))
                {
                    // On ajoute 1 car "rien de prévu ce jour là" donc c'est un jour ou l'agent travail
                    $nbredemijour ++;
                } 
                elseif ($ignoreabsenceautodecla == TRUE and strcasecmp((string)$element->type(), "nondec") == 0) 
                {
                    // On vérifie que l'agent est en activité => Si non on ne doit pas compter cet élément
                    // On ajoute des espaces avant et après pour rechercher la constante
                    if (stripos(" " . $element->htmlextraclass() . " ",planningelement::HTML_CLASS_SANSACTIVITE)===false)
                    {
                        // On ajoute 1 car "pas d'autodeclaration et on doit l'ignorer" donc c'est un jour ou l'agent travail
                        $nbredemijour ++;
                    }
                } 
                else 
                {
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
        $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
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
        foreach ((array)$listecateg as $keycateg => $nomcateg)
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
            if (strcasecmp((string)$planningelement->moment(), fonctions::MOMENT_MATIN) != 0)
                $pdf->Cell(4, 5, $this->fonctions->utf8_decode(""), 'TBR', 0, 'C', 1);
            else
                $pdf->Cell(4, 5, $this->fonctions->utf8_decode(""), 'TBL', 0, 'C', 1);
            // echo "Apres les demies-cellules <br>";

            if (!in_array($planningelement->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
            {
                if (array_key_exists($planningelement->type(),$listeabs))
                {
                    // Si c'est une absence dans la catégorie "télétravail hors convention"
                    if (strcmp((string)$planningelement->parenttype(),'teletravHC')==0)
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
            if (strcasecmp((string)$element->type(), "teletrav")==0 or strcasecmp((string)$element->parenttype(), "teletravHC")==0 )
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

        foreach($tabrepartition as $type => $nbjours)
        {
            if ($nbjours == 0)
            {
                unset($tabrepartition[$type]);
            }
        }
        
        //var_dump($tabrepartition);
        return $nbjoursteletravail;
    }

    function listeperiodedispo($agentid, $datedebut, $momentdebut, $datefin, $momentfin, $ignoreabsenceautodecla = FALSE)
    {
        // Si le nombre d'éléments du planning est 0 => On doit charger le planning
        // Sinon, il est déjà chargé
        if (count((array)$this->listeelement)==0)
        {
            $listeelement = $this->load($agentid, $datedebut, $datefin);
        }
        else
        {
            $listeelement = $this->listeelement;
        }
        $paslepremier = FALSE;
        $pasledernier = FALSE;
        if (strcasecmp((string)$momentdebut, fonctions::MOMENT_MATIN) != 0) 
        {
            $paslepremier = TRUE;
        }
        if (strcasecmp((string)$momentfin, fonctions::MOMENT_APRESMIDI) != 0) 
        {
            $pasledernier = TRUE;
        }
        //$index = 0;
        // Tableau des disponibilités qu'on va retourner
        $listedispo = array();
        $dispo = new disponibilite;
        $priviouselement = null;
        // Tableau des types d'élément où l'agent doit être considéré comme disponible (<=> pas en congés, pas absent, pas en temps partiel, pas en WE, ....)
        $arraytypedispo = array('', 'teletrav');
        // Tableau des types d'élément où on doit ignorer la situation de l'agent => Il ne travaille pas à ce moment là
        $arraytypeignore = array("ferie","WE","tppar");
        $datedebutdb = $this->fonctions->formatdatedb($datedebut);
        $datefindb = $this->fonctions->formatdatedb($datefin);
        if ($ignoreabsenceautodecla)
        {
            // On ajoute le type "nondec"
            $arraytypeignore[] = "nondec";
        }
        foreach ((array) $listeelement as $key => $element) 
        {
            $pasdetraitement = FALSE;
            $elementdatedb = $this->fonctions->formatdatedb($element->date());
            // Si la date de l'élément n'est pas dans la période demandée (donc elementdate < datedebut ou elementdate > datefin)
            if ($elementdatedb < $datedebutdb or $elementdatedb > $datefindb)
            {
                $pasdetraitement = TRUE;
            }
            // Si on est sur le MATIN de la date de début mais qu'on doit l'exclure
            elseif ($elementdatedb == $datedebutdb and $element->moment() == fonctions::MOMENT_MATIN and $paslepremier) 
            {
                $pasdetraitement = TRUE;
            }
            // Si on est sur l'APRES-MIDI de la date de fin mais qu'on doit l'exclure
            elseif ($elementdatedb == $datefindb and $element->moment() == fonctions::MOMENT_APRESMIDI and $pasledernier) 
            {
                $pasdetraitement = TRUE;
            }
            // Si l'agent ne travaille pas; on doit ingorer cet élément et donc pas modifier la situation de l'agent
            elseif (in_array($element->type(), $arraytypeignore))
            {
                $pasdetraitement = TRUE;
            }
            if (! $pasdetraitement) 
            {
                //var_dump("Element date = " . $element->date() . " moment = " . $element->moment());
                // Si l'élement est dans la liste des types "dispo" et qu'on n'a pas d'élément de début => C'est le début d'une dispo
                if (in_array($element->type(), $arraytypedispo) and is_null($dispo->elementdebut))
                {
                    $dispo->elementdebut = $element;
                }
                // Si l'élement n'est pas dans la liste des types "dispo" et qu'on a un élément de début => C'est la fin de la dispo est l'élément précédent
                elseif (!in_array($element->type(), $arraytypedispo) and !is_null($dispo->elementdebut))
                {
                    $dispo->elementfin = $priviouselement;
                    $listedispo[] = $dispo;
                    $dispo = new disponibilite;
                }
                $priviouselement = $element;
            }
            //$index ++;
        }
        // Si on a un élément de début, mais qu'on a parcouru tout le planning => La fin de la disponibilité est le $previouselement
        if (!is_null($dispo->elementdebut))
        {
            $dispo->elementfin = $priviouselement;
            $listedispo[] = $dispo;
        }
        return $listedispo;
    }

    function calculdatefindemande($datedebut, $momentdebut, $nbjours)
    {
        $errlog = '';
        $datetemp = $this->fonctions->formatdatedb($datedebut);
        $listeelement = $this->planning();
        if (count((array)$listeelement)==0)
        {
            $errlog = "Le planning est vide. Pas de calcul possible";
            return $errlog;
        }

        if (!isset($listeelement[$datetemp . $momentdebut]))
        {
            $errlog = "La date de début n'est pas inclue dans le planning";
            return $errlog;
        }

        $elementdebutkey = $datetemp . $momentdebut;
        // On va déplacer le curseur interne du tableau sur l'élément de début
        // On sait qu'il existe parce qu'on a fait le test au dessus
        // On n'utilise pas la fonction $this->getelement() car on doit déplacer le curseur interne ce que ne fait pas la fonction
        $elementdebut = reset($listeelement);
        while (key($listeelement)!=$elementdebutkey)
        {
            $elementdebut = next($listeelement);
        }

        // Tableau des types d'élément où l'agent doit être considéré comme disponible (<=> pas en congés, pas absent, pas en temps partiel, pas en WE, ....)
        $arraytypedispo = array('', 'teletrav');
        // Tableau des types d'élément où on doit ignorer la situation de l'agent => Il ne travaille pas à ce moment là
        $arraytypeignore = array("ferie","WE","tppar");

        // Compteur indiquant le nombre de jours restant à trouver
        // ATTENTION : Si on commence un jour de type arraytypeignore => On ne doit pas enlever 1/2 journée
        //      Sinon, On a déjà trouvé une 1/2 journée
        if (!in_array($elementdebut->type(), $arraytypeignore))
        {
            $nbjoursrestant = $nbjours - 0.5;
        }
        $finrecherche = false;
        while (!$finrecherche)
        {
            $elementfin = next($listeelement);
            if ($elementfin===false)
            {
                // On est arrivé au bout du planning est on n'a pas trouver la durée necessaire
                $finrecherche = true;
                $errlog = "Le planning n'est pas assez long pour trouver $nbjours travaillés à partir du $datedebut";
            }
            elseif (in_array($elementfin->type(), $arraytypeignore))
            {
                // Ce n'est pas un jour travailler donc on ne fait rien
            }
            elseif (in_array($elementfin->type(), $arraytypedispo))
            {
                // L'agent est disponible => On peut poser un congés sur cet élément
                // Attention : Un élement du planning est une 1/2 journée => On n'a trouvé que 0.5 jour.
                $nbjoursrestant = $nbjoursrestant - 0.5;
            }
            // Si on a trouvé toute la durée demandée => Fin de la recherche et $elementfin contient l'élément de fin
            if ($nbjoursrestant==0)
            {
                $finrecherche = true;
            }
        }
        if ($errlog<>"")
        {
            return $errlog;
        }
        return $elementfin;
    }
    
}

?>