<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    $userid = null;
    if (isset($_POST["userid"]))
    {
        // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
        $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
        if ($CASuserId!==false)
        {
            // On a l'agentid de l'agent => C'est un administrateur donc on peut forcer le userid avec la valeur du POST
            $userid = $_POST["userid"];
        }
        else
        {
            $userid = $fonctions->useridfromCAS($uid);
            if ($userid === false)
            {
                $userid = null;
            }
        }
    }
    
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    $previoustxt = null;
    if (isset($_POST["previous"]))
    {
        $previoustxt = $_POST["previous"];
    }

    if (strcasecmp((string)$previoustxt, "yes") == 0)
    {
        $previous = 1;
    }
    else
    {
        $previous = 0;
    }
    
    $indexmois = null;
    if (isset($_POST["indexmois"]))
    {
        $indexmois = $_POST["indexmois"];
    }

    if (is_null($indexmois) or $indexmois == "")
    {
        $indexmois = date("m");
    }
    $indexmois = str_pad($indexmois, 2, "0", STR_PAD_LEFT);
    // echo "indexmois (apres) = $indexmois <br>";
    $annee = $fonctions->anneeref() - $previous;
    // echo "annee = $annee <br>";
    $debutperiode = $fonctions->debutperiode();
    // echo "debut periode = $debutperiode <br>";
    $moisdebutperiode = date("m", strtotime($fonctions->formatdatedb(date("Y") . $debutperiode)));
    // echo "moisdebutperiode = $moisdebutperiode <br>";
    
    if ($indexmois < $moisdebutperiode)
    {
        $annee ++;
    }
    // echo "annee (apres) = $annee <br>";
                                    
    $mode = MODE_RESPONSABLE;
    if (isset($_POST["mode"]))
    {
        $mode = $_POST["mode"]; // Mode = resp ou agent ou consult
    }
                                            
    $date_selected = '';
    if (isset($_POST["date_selected"]))
    {
        $date_selected = $_POST["date_selected"];
    }
    
    $moment_selected = '';
    if (isset($_POST["moment_selected"]))
    {
        $moment_selected = $_POST["moment_selected"];
    }
    
    $agentid_selected = '';
    if (isset($_POST['agentid_selected']))
    {
        $agentid_selected = $_POST['agentid_selected'];
    }
            
    $action = '';
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];
    }
    
    $report_date = '';
    if (isset($_POST['report_date']))
    {
        $report_date = $_POST['report_date'];
    }
        
    $report_moment = '';
    if (isset($_POST['report_moment']))
    {
        $report_moment = $_POST['report_moment'];
    }

    $rootstruct = '';
    if (isset($_POST['rootid']))
    {
        $rootstruct = $_POST['rootid'];
    }
            
    $check_showroot = 'off';
    if (isset($_POST['check_showroot']))
    {
        $check_showroot = $_POST['check_showroot'];
    }
                
    $structureid = '';
    if (isset($_POST['structureid']))
    {
        $structureid = $_POST['structureid'];
    }
    
    $typeconvention = '';
    if (isset($_POST['typeconvention']))
    {
        $typeconvention = $_POST['typeconvention'];
    }
    
    
    if (isset($_POST['datedebut']))
    {
        $datedebut = $_POST['datedebut'];
    }
    
    if (isset($_POST['datefin']))
    {
        $datefin = $_POST['datefin'];
    }
            
    require ("includes/menu.php");
    require_once (dirname(__FILE__,1) . "/includes/modif_teletravail.php");
    
    //echo "<br><br><br>"; print_r($_POST); echo "<br>";


    if (isset($_POST['teletravailmail']))
    {
        // On va générer le PDF et l'envoyer par mail au responsable
        //echo "On génère le PDF par mail.";
        $structure = new structure($dbcon);
        $structure->load($structureid);
        $pdffilename = $structure->teletravailpdf($datedebut,$datefin,true);
        $cronuser = new agent($dbcon);
        $cronuser->load(SPECIAL_USER_IDCRONUSER);
        $cronuser->sendmail($user,'Synthèse annuelle - télétravail pour ' . $structure->nomlong(), "Vous trouverez ci-joint le document de synthèse du télétravail pour les agents de la structure " . $structure->nomlong(),$pdffilename);
        echo $fonctions->showmessage(fonctions::MSGINFO, "Le document PDF vous a été envoyé.");
        unset($cronuser);
        unset($structure);
    }
    
    echo "<br>";

    if ($date_selected != "" and $moment_selected != "" and $agentid_selected != "")
    {
        // var_dump ("report_date = " . $report_date);
        $complement = new complement($dbcon);
        $agent = new agent($dbcon);
        $agent->load($agentid_selected);
        if ($action == ttexception::ACTION_DESACTIVE)
        {   // On fait une désactivation de la date
            // var_dump('on desactive');
            // $listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //var_dump($listeexclusion);
            // if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)===false)
            
            // Si on doit déplacer la journée complète, on doit mettre à vide le moment sélectionné et le moment de destination
            if ($report_moment!==fonctions::MOMENT_MATIN and $report_moment!==fonctions::MOMENT_APRESMIDI)
            {
                $report_moment = '';
                $moment_selected = '';
            }
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected, $statut);
            // var_dump("date_selected = $date_selected");
            // var_dump("moment_selected = $moment_selected");
            // var_dump("exclusion = $exclusion");
            if ($exclusion===false)
            {   // On n'a pas trouvé la date dans la liste
                $reportpossible = true;
                if ($report_date != '')
                {
                    $planning = new planning($dbcon);
                    $planning->load($agentid_selected, $report_date, $report_date, true, true, true);
                    $planningelementliste = $planning->planning();
                    // ATTENTION :
                    // On doit vérifier que la date cible n'est pas une journée de télétravail exclue
                    // Donc pour chaque planningelement on regarde si la classe HTML_CLASS_EXCLUSION est dans les htmlextraclass
                    if ($report_moment==fonctions::MOMENT_MATIN)
                    {
                        $planningelement = current($planningelementliste);
                        $reportpossible = ($planningelement->type()=='' and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE));
                    }
                    elseif ($report_moment==fonctions::MOMENT_APRESMIDI)
                    {
                        $planningelement = next($planningelementliste);
                        $reportpossible = ($planningelement->type()=='' and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE));                        
                    }
                    else
                    {
                        foreach ($planning->planning() as $planningelement)
                        {
                            if ($planningelement->type()!='' or str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) or str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE))
                            {
                                $reportpossible = false;
                                break;
                            }
                        }
                    }
                }
                // var_dump($reportpossible);
                if ($reportpossible)
                {
                    // var_dump("On va faire le complément");
                    $tmperror = $fonctions->ajoutjoursteletravailexclus($agentid_selected, $date_selected, $moment_selected, $report_date, $report_moment,ttexception::STATUT_VALIDE);
                    if (is_string($tmperror) and ($tmperror . '' != ''))
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR, $tmperror);
                    }
                    else
                    {
                        if (trim($report_date) != '')
                        {
                            echo $fonctions->showmessage(fonctions::MSGINFO,"La journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est déplacée au " . $fonctions->formatdate($report_date) . ".");
                            modiftt_envoyermailagent($tmperror,ttexception::ACTION_DEPLACEMENT);
                        }
                        else
                        {
                            echo $fonctions->showmessage(fonctions::MSGINFO,"La suppression de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                            modiftt_envoyermailagent($tmperror,ttexception::ACTION_SUPPRIME);
                        }
                    }
                }
                else if (str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION))
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de déplacer la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " : La date souhaitée (le " . $fonctions->formatdate($report_date) . ") est un jour de télétravail déplacé.");
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de déplacer la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " : La date souhaitée (le " . $fonctions->formatdate($report_date) . ") n'est pas disponible.");
                }
            }
            else
            {
                //echo "On demande une désactivation alors que la date est déjà désactivé. On ne fait rien. <br>";
            }
        }
        elseif ($action == ttexception::ACTION_REACTIVE)
        {   // On fait une réactivation
            //$listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)!==false)
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected, $statut);
            // var_dump("exclusion = " . $exclusion);
            if ($exclusion!==false)
            {   // On a trouvé la date dans la liste
                // var_dump("On n'a pas trouvé la date dans les exclusions");
                $erreur = $agent->supprjourteletravailexclu($date_selected, $moment_selected);
                // var_dump("Erreur = XXXX" . $erreur . "XXXX");
                if (strlen(trim($erreur))==0)
                {
                    echo $fonctions->showmessage(fonctions::MSGINFO,"La réactivation de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                    // $ttexception = new ttexception;
                    // $ttexception->agentid = $agent->agentid();
                    // $ttexception->dateorigine = $date_selected;
                    // $ttexception->momentorigine = $moment_selected;
                    modiftt_envoyermailagent($exclusion,ttexception::ACTION_REACTIVE);
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de réactiver la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete()  . " : $erreur ");                    
                }
            }
            else
            {
                // var_dump ("On demande une réactivation alors que la date n'est pas désactivé. On ne fait rien.");
            }
        }
    }

    addwaitingimgdiv();

    $planninghtml = "dummy_string_to_keep_not_empty";

    echo "<form name='select_mois' id='select_mois' method='post' class='centeraligntext'>";
    echo "<select class='selectpadding' name='indexmois'>";

    // On reprend le mois de début de période
    $index = $moisdebutperiode;
    // L'année c'est l'année de référence
    $anneemois = $fonctions->anneeref() - $previous;
    // echo "index = $index <br>";
    for ($indexcpt = 1; $indexcpt <= 12; $indexcpt ++) {
        // Si on est en mode consultant ou agent et que la date calculée (annee + mois) est inférieure à la date du jour => on n'affiche pas
        
        if (in_array($mode,array(MODE_CONSULTANT,MODE_AGENT)) and ($anneemois . str_pad($index, 2, "0", STR_PAD_LEFT) < date("Ym")))
        {
            // On ne fait rien
        }
        else
        {
            echo "<option value='$index'";
            if ($index == $indexmois)
            {
                echo " selected ";
            }
            echo ">" . $fonctions->nommois("01/" . str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . date("Y")) . "  " . $anneemois . "</option>";
        }
        // On calcule le modulo
        $index = ($index % 12) + 1;
        // Si le mois est > 12 ou égal à 1 alors c'est qu'on est passé à l'année suivante
        if ($index > 12 or $index == 1)
        {
            $anneemois = $anneemois + 1;
        }
    }

    echo "</select>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
    echo "<input type='hidden' name='date_selected' id='date_selected' value='' />";
    echo "<input type='hidden' name='moment_selected' id='moment_selected' value='' />";
    echo "<input type='hidden' name='agentid_selected' id='agentid_selected' value='' />";
    echo "<input type='hidden' name='report_date' id='report_date' value='' />";
    echo "<input type='hidden' name='report_moment' id='report_moment' value='' />";
    echo "<input type='hidden' name='typeconvention' id='typeconvention' value='' />";
    echo "<input type='hidden' name='action' id='action' value='' />";
    echo "<input type='hidden' name='check_showroot' id='check_showroot' value='" . $check_showroot . "' />";
    echo "<input type='hidden' name='rootid' id='rootid' value='" . $rootstruct . "' />";
    if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Sélectionner'  /></center>";
    echo "</form>";
    
    if (strcasecmp((string)$mode, MODE_RESPONSABLE) == 0) 
    {
        $structureliste = $user->structrespliste();
        $structureliste = $fonctions->enleverstructuresinclues_planning($structureliste);
        if (is_array($structureliste))
        {
            // uasort($structureliste,"triparprofondeurabsolue");
            uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
        }
        foreach ($structureliste as $structkey => $structure) 
        {
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
            {
                // echo "structureid = $structureid    structure->id() = " . $structure->id() . "   rootstruct = $rootstruct <br>";
                if ($structureid == $structure->id() and $rootstruct <> '')
                {
                    unset($structureliste["$structkey"]);
                    $structure = $structure->structureenglobante();
                }
                $structureliste = array_merge($structureliste, array($structure->id() => $structure));
                // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
            } 
            else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        
        foreach ($structureliste as $structkey => $structure) 
        {
            // Vérification que la structure n'est pas fermée => En théorie c'est déjà fait avant donc ne sert à rien
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                echo "<br>";
                //echo "Le code de la structure : " . $structure->id() . "<br>";
                if ($structure->responsable()->agentid() == $user->agentid() or $structure->responsablesiham()->agentid() == $user->agentid())
                {
                    $planninggris = false;
                }
                else
                {
                    $planninggris = true;
                }
                
                echo "<div id='planningstruct_" . $structure->id() . "' class='divtocomplete'></div>";
?>
                <script>

                    // Initialisation des paramètres pour l'affichage du planning de la structure
                    var params = {  "methode" : "<?php echo structure::WS_METHODE_PLANNING; ?>", 
                                    "structureid" : "<?php echo $structure->id(); ?>", 
                                    "structurenomlong" : "<?php echo htmlspecialchars($structure->nomlong()); ?>", 
                                    "structurenomcourt" : "<?php echo htmlspecialchars($structure->nomcourt()); ?>", 
                                    "mois_annee_debut" : "<?php echo $indexmois . "/" . $annee; ?>" , 
                                    "indexmois" : "<?php echo $indexmois; ?>",
                                    "showsousstruct" : "<?php echo $structure->sousstructure(); ?>",
                                    "userid" : "<?php echo htmlspecialchars($user->agentid()); ?>",
                                    "mode" : "<?php echo htmlspecialchars($mode); ?>",
                                    "previoustxt" : "<?php echo htmlspecialchars($previoustxt); ?>",
                                    "noiretblanc" : '<?php echo $planninggris ?>',
                                    "includeteletravail" : 'O',
                                    "dbclickable" : 'O'
                                 };
<?php
                    // Si la structure n'est pas incluse et que le responsable est l'utilisateur courant
                    if ($structure->responsable()->agentid() == $user->agentid() and !$structure->isincluded())
                    {
?>
                        // On ajoute le paramètre pour permettre l'affichage des exports PDF et mail du télétravail
                        params['teletravailexport']  = 'O'
<?php
                    }
?>
                    // Appel de la fonction asynchrone 
                    showstructureplanning(params,document.getElementById('planningstruct_<?php echo $structure->id(); ?>'));

                </script>
<?php                
            }
        }
    } elseif (strcasecmp((string)$mode, MODE_GESTION) == 0) {
        $structureliste = $user->structgestliste();
        $structureliste = $fonctions->enleverstructuresinclues_planning($structureliste);
        if (is_array($structureliste))
        {
            // uasort($structureliste,"triparprofondeurabsolue");
            uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
        }
        foreach ($structureliste as $structkey => $structure)
        {
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                //echo "structureid = $structureid    structure->id() = " . $structure->id() . "   rootstruct = $rootstruct <br>";
                if ($structureid == $structure->id() and $rootstruct <> '')
                {
                    unset($structureliste["$structkey"]);
                    $structure = $structure->structureenglobante();
                }
                $structureliste = array_merge($structureliste, array($structure->id() => $structure));
                // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
            }
            else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure)
        {
            // Vérification que la structure n'est pas fermée => En théorie c'est déjà fait avant donc ne sert à rien
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                echo "<br>";
                //echo "Le code de la structure : " . $structure->id() . "<br>";
                if ($structure->gestionnaire()->agentid() == $user->agentid())
                {
                    $planninggris = false;
                }
                else
                {
                    $planninggris = true;
                }

                echo "<div id='planningstruct_" . $structure->id() . "' class='divtocomplete'></div>";
?>
                <script>
                    // Initialisation des paramètres pour l'affichage du planning de la structure
                    var params = {  "methode" : "<?php echo structure::WS_METHODE_PLANNING; ?>", 
                                    "structureid" : "<?php echo $structure->id(); ?>", 
                                    "structurenomlong" : "<?php echo htmlspecialchars($structure->nomlong()); ?>", 
                                    "structurenomcourt" : "<?php echo htmlspecialchars($structure->nomcourt()); ?>", 
                                    "mois_annee_debut" : "<?php echo $indexmois . "/" . $annee; ?>" , 
                                    "indexmois" : "<?php echo $indexmois; ?>",
                                    "showsousstruct" : "O",
                                    "userid" : "<?php echo htmlspecialchars($user->agentid()); ?>",
                                    "mode" : "<?php echo htmlspecialchars($mode); ?>",
                                    "previoustxt" : "<?php echo htmlspecialchars($previoustxt); ?>",
                                    "noiretblanc" : '<?php echo $planninggris ?>',
                                    "includeteletravail" : 'O',
                                    "dbclickable" : 'O'
                                 };
                    // Appel de la fonction asynchrone 
                    showstructureplanning(params,document.getElementById('planningstruct_<?php echo $structure->id(); ?>'));
                </script>
<?php                
                // $planninghtml = $structure->planninghtml($indexmois . "/" . $annee,'o',$planninggris,true,true);
                // echo $planninghtml;
                $structparent = $structure->structureenglobante();
            }
        }
    }
    elseif (strcasecmp((string)$mode, MODE_CONSULTANT) == 0)
    {
        //var_dump("Je suis en mode consultant");
        $structure = new structure($dbcon);
        $agentconsultliste = $user->agentconsultantliste(true);
        //var_dump($agentconsultliste);
        echo "<br>";
        echo "<div id='planningstruct_" . $structure->id() . "' class='divtocomplete'></div>";
?>
        <script>
            // Initialisation des paramètres pour l'affichage du planning de la structure
            var params = {  "methode" : "<?php echo structure::WS_METHODE_PLANNING; ?>", 
                            "structureid" : "<?php echo $structure->id(); ?>", 
                            "structurenomlong" : "<?php echo htmlspecialchars($structure->nomlong()); ?>", 
                            "structurenomcourt" : "<?php echo htmlspecialchars($structure->nomcourt()); ?>", 
                            "mois_annee_debut" : "<?php echo $indexmois . "/" . $annee; ?>" , 
                            "indexmois" : "<?php echo $indexmois; ?>",
                            "showsousstruct" : "N",
                            "userid" : "<?php echo htmlspecialchars($user->agentid()); ?>",
                            "mode" : "<?php echo htmlspecialchars($mode); ?>",
                            "previoustxt" : "<?php echo htmlspecialchars($previoustxt); ?>",
                            "noiretblanc" : 'O',
                            "includeteletravail" : 'O',
                            "dbclickable" : 'N',
                            "includecongeabsence" : 'O',
                            "agentlist" : <?php echo json_encode($agentconsultliste); ?>
                         };
            // Appel de la fonction asynchrone 
            showstructureplanning(params,document.getElementById('planningstruct_<?php echo $structure->id(); ?>'));
        </script>
<?php                


        // $planninghtml = $structure->planninghtml($indexmois . "/" . $annee,'n',true,true,false,true,$agentconsultliste);
        // echo $planninghtml;
    }
    else 
    {
        $affstructureid = $user->structureid();
        if ($affstructureid . "" != "")
        {
            $structure = new structure($dbcon);
            $structure->load($affstructureid);
            $showsousstruct = 'n';
            if (strcasecmp((string)$structure->affichetoutagent(), "o") == 0)
            {
                // Rappel : 
                //      structureid => Id de la structure d'affectation de l'agent (récupéré du POST)
                //      affstructureid => Id de la structure d'affectation de l'agent
                //      rootstruct => Id de la strucuture racine
                //echo "structureid = $structureid    affstructureid = $affstructureid   rootstruct = $rootstruct <br>";
                // Si on a coché la case 'voir la structure root  et si rootstruct <> '' ==> On veut afficher la structure Root
                if ($rootstruct <> '' and $check_showroot == 'on')
                {
                    unset($structure);
                    $structure = new structure($dbcon);
                    $structure->load($rootstruct);
                    $showsousstruct = 'o';
                }
                
                echo "<br>";
                // echo "Planning de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                echo "<div id='planningstruct_" . $structure->id() . "' class='divtocomplete'></div>";
?>
                <script>
                    // Initialisation des paramètres pour l'affichage du planning de la structure
                    var params = {  "methode" : "<?php echo structure::WS_METHODE_PLANNING; ?>", 
                                    "structureid" : "<?php echo $structure->id(); ?>", 
                                    "structurenomlong" : "<?php echo htmlspecialchars($structure->nomlong()); ?>", 
                                    "structurenomcourt" : "<?php echo htmlspecialchars($structure->nomcourt()); ?>", 
                                    "mois_annee_debut" : "<?php echo $indexmois . "/" . $annee; ?>" , 
                                    "indexmois" : "<?php echo $indexmois; ?>",
                                    "showsousstruct" : "<?php echo $showsousstruct; ?>",
                                    "userid" : "<?php echo htmlspecialchars($user->agentid()); ?>",
                                    "mode" : "<?php echo htmlspecialchars($mode); ?>",
                                    "previoustxt" : "<?php echo htmlspecialchars($previoustxt); ?>",
                                    "noiretblanc" : 'O',
                                    "includeteletravail" : 'O',
                                    "dbclickable" : 'N',
                                    "includecongeabsence" : 'O',
                                };
<?php
                    $structparent = $structure->structureenglobante();
                    if ($fonctions->convertvaluetobool($structparent->agentaffplanningdirection()) and $structparent->id() != $affstructureid)
                    {
?>
                        // On ajoute le paramètre pour permettre l'affichage de la case à cocher 'voir tout le planning de la structure root'
                        params['showallstructure']  = 'O';
                        params['rootid'] = "<?php echo $structparent->id(); ?>";
                        params['rootnomcourt'] = "<?php echo htmlspecialchars($structparent->nomcourt()); ?>";
                        params['check_showroot'] = '<?php if ($check_showroot == 'on') echo "O"; else echo "N"; ?>';
<?php
                    }
?>
                    // Appel de la fonction asynchrone 
                    showstructureplanning(params,document.getElementById('planningstruct_<?php echo $structure->id(); ?>'));
                </script>
<?php                
            }
        }
    }

    unset($structure);
?>
<!-- 
<script>
    window.addEventListener("load", (event) => {
        var waiting_img = document.getElementById('waiting_img');
        if (waiting_img)
        {
            waiting_img.hidden=true;
        }
        var waiting_div = document.getElementById('waiting_div');
        if (waiting_div)
        {
            waiting_div.hidden=true;
        }
    });
</script> -->

</body>
</html>