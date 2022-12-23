<?php
    require_once ('CAS.php');
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
    
/*    
    // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
    $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
    if ($CASuserId===false)
    {
        // Ce n'est pas un administrateur
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        echo "<script>alert('Accès réservé aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
        //        header('Location: index.php');
        exit();
    }
*/ 
    
    $CASAdminId = $fonctions->CASuserisG2TAdmin($uid);
    if ((!$user->estprofilrh()) and ($CASAdminId===false))
    {
        // Ce n'est pas un agents RH ni un administrateur
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas un gestionnaire RH ni un administrateur");
        echo "<script>alert('Accès réservé aux agents RH ou aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
        //        header('Location: index.php');
        exit();
        
    }
    
    $current_tab = '';
    if (isset($_POST['current_tab']))
    {
        $current_tab = $_POST['current_tab'];
    }
    
    require ("includes/menu.php");
 
    //echo "<br>" . print_r($_POST,true) . "<br>";
    echo "<br>";

    
    /////////////////////////////////////////////////////////////////////
    // On traite les données postées sur l'onglet des CONGES           //
    /////////////////////////////////////////////////////////////////////
    if ($current_tab == 'tab_conges')
    {
        $msg_erreur = "";
        $periodeid = $fonctions->anneeref();
        $datefausse = false;
        $cancel = array();
        
        if (isset($_POST['valid_periode']))
        {
            $date_debut = "";
            if (isset($_POST['date_debut']))
            {
                $date_debut = $_POST['date_debut'] . "";
            }
            $date_fin = "";
            if (isset($_POST['date_fin']))
            {
                $date_fin = $_POST['date_fin'] . "";
            }
            if (isset($_POST['cancel']))
            {
                $cancel = $_POST['cancel'];
            }
            
            if (($date_fin=="") and ($date_debut==""))
            {
                if (isset($_POST['valid_periode']) and count($cancel)==0) // Si on a posté des dates vides et que c'est pas une annulation => il y a un problème
                {
                    $erreur = 'La date de début et la date de fin sont vides.';
                    $msg_erreur .= $erreur . "<br/>";
                    error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
                }
                $datefausse = true;
            }
            elseif (($date_debut=="") xor ($date_fin==""))
            {
                // On a une des deux dates mais pas les deux
                $erreur = 'La date de début ou la date de fin est vide.';
                //echo "Erreur = $erreur";
                $msg_erreur .= $erreur . "<br/>";
                error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
                $datefausse = true;
            }
            elseif ((!$fonctions->verifiedate($date_debut)) and ($date_debut!=""))
            {
                // La date de début n'est pas une date valide
                $erreur = "La date de début n'est pas une date valide.";
                //echo "Erreur = $erreur";
                $msg_erreur .= $erreur . "<br/>";
                error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
                $datefausse = true;
            }
            elseif ((!$fonctions->verifiedate($date_fin)) and ($date_fin!=""))
            {
                // La date de fin n'est pas une date valide
                $erreur = "La date de fin n'est pas une date valide.";
                //echo "Erreur = $erreur";
                $msg_erreur .= $erreur . "<br/>";
                error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
                $datefausse = true;
            }
            
            if (!$datefausse)
            {
                $datedebutdb = $fonctions->formatdatedb($date_debut);
                $datefindb = $fonctions->formatdatedb($date_fin);
                $periodeid = $fonctions->anneeref($fonctions->formatdate($date_debut));
                if ($datedebutdb > $datefindb)
                {
                    $erreur = "Il y a une incohérence entre la date de début et la date de fin.";
                    //echo "Erreur = $erreur";
                    $msg_erreur .= $erreur . "<br/>";
                    error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
                    $datefausse = true;
                }
                elseif (!is_null($periodeid))
                {
                    //echo "datedebutdb = $datedebutdb   debut période = " . ($periodeid . $fonctions->debutperiode()) . "<br>";
                    //echo "datefindb = $datefindb   fin période = " . (($periodeid+1) . $fonctions->finperiode()) . "<br>";
                    if ($datedebutdb<($periodeid . $fonctions->debutperiode()) or $datefindb>(($periodeid+1) . $fonctions->finperiode()))
                    {
                        $erreur = "La date de début ou la date de fin est en dehors de la période : " . $fonctions->formatdate($periodeid . $fonctions->debutperiode()) . "->" . $fonctions->formatdate(($periodeid+1) . $fonctions->finperiode()) . ".";
                        //echo "Erreur = $erreur";
                        $msg_erreur .= $erreur . "<br/>";
                        error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
                        $datefausse = true;
                    }
                }
                
            }
            
            // S'il n'y a pas de problème de date
            if ($datefausse==false)
            {
                // On sauvegarde la nouvelle période
                //echo "On va sauvegarder la valeur.....<br>";
                $datedebutdb = $fonctions->formatdatedb($date_debut);
                $datefindb = $fonctions->formatdatedb($date_fin);
                $periodeid = $fonctions->anneeref($fonctions->formatdate($date_debut));
                $periode = new periodeobligatoire($dbcon);
                //echo "Periodeid = $periodeid <br>";
                $periode->load($periodeid);
                $periode->ajouterperiode($datedebutdb, $datefindb);
                $periode->store();
            }
            
            if (count($cancel)>0)
            {
                foreach ($cancel as $key => $valeur)
                {
                    $valeur = explode('-', $key);
                    //echo "Key = $key <br>";
                    $elementanneeref = $fonctions->anneeref($fonctions->formatdate($valeur[0]));
                    $periode = new periodeobligatoire($dbcon);
                    $periode->load($elementanneeref);
                    $periode->supprimerperiode($valeur[0],$valeur[1]);
                }
                $periode->store();
            }
        }
        
        /////////////////////////////////////////////
        // Mise à jour du nombre de jours de congés
        if (isset($_POST['valid_nbjours']))
        {
            $anneeconge = trim($_POST['anneeconge']);
            $nbjoursannuel = trim($_POST['nbjoursannuel']);
            //echo "<br>anneeconge = $anneeconge    nbjoursannuel = $nbjoursannuel <br>";
            if (!is_numeric($nbjoursannuel) || !is_int($nbjoursannuel+0) || $nbjoursannuel < 0)
            {
                $msg_erreur = $msg_erreur . "Le nombre de jours de congés annuels doit être un entier positif. <br>";
            }
            else
            {
                $constantename = "NBJOURS" . $anneeconge;
                $msg_erreur = $fonctions->enregistredbconstante($constantename, $nbjoursannuel);
            }
        }
        
        /////////////////////////////////////////////
        // Mise à jour de la date des reports de congés
        if (isset($_POST['valid_report']))
        {
            $jourreport = trim($_POST['jourreport']);
            $moisreport = trim($_POST['moisreport']);
            $finreport = $moisreport . $jourreport;
            //        $testdate = substr($finreport,2) . '/' . substr($finreport,0,2) . '/' . date('Y');
            $testdate = $jourreport . "/" . $moisreport . "/" . date('Y');
            //var_dump($testdate);
            if (!$fonctions->verifiedate($testdate))
            {
                $msg_erreur = $msg_erreur . "La date de fin de report des congés n'est pas une date valide. <br>";
            }
            else
            {
                $constantename = "FIN_REPORT";
                $msg_erreur = $fonctions->enregistredbconstante($constantename, $finreport);
            }
        }
        
        
        /////////////////////////////////////
        // Synchronisation des jours fériés
        if (isset($_POST['valid_synchroferies']))
        {
            $tabannees = array();
            if (isset($_POST['anneesynchro']))
            {
                $tabannees = $_POST['anneesynchro'];
            }
            $tabferies = array();
            $return = $fonctions->synchronisationjoursferies($tabannees,$tabferies);
            if ($return != '')
            {
                $erreur = "L'intégration des jours fériés a échoué : " . $return . ".";
                //echo "Erreur = $erreur";
                $msg_erreur .= $erreur . "<br/>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                // Tout s'est bien passé.
                // var_dump($tabferies);
            }
        }
        
        
        if ($msg_erreur!="")
        {
            //echo "Erreur => " . $msg_erreur . "<br><br>";
            echo $fonctions->showmessage(fonctions::MSGERROR, "$msg_erreur");
        }
        else
        {
            if (count($cancel)>0)
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont supprimées.");
            }
            if (isset($_POST['valid_periode']) or isset($_POST['valid_nbjours']) or isset($_POST['valid_report']))
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données ont été enregistrées.");
            }
            if (isset($_POST['valid_synchroferies']))
            {
                $stringannee = "";
                $separateur = "";
                foreach($tabannees as $key => $annee)
                {
                    if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; elseif (strlen($stringannee)>0) $separateur = ', ';
                    //if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; else $separateur = ', ';
                    $stringannee = $stringannee . $separateur . $annee . '/' . ($annee+1);
                }
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les jours fériés pour " . trim($stringannee) . " ont été inportés.");
            }
        }
    }
    
    /////////////////////////////////////////////////////////////////////
    // On traite les données postées sur l'onglet des CET              //
    /////////////////////////////////////////////////////////////////////
    if ($current_tab == 'tab_cet')
    {
        $msgerror = '';
        
        // PARAMETRAGE DU CALENDRIER D'ALIMENTATION
        //if (isset($_POST['valider_cal_alim']))
        $datecampagnealimupdate = false;
        if (isset($_POST['date_debut_alim']) and isset($_POST['date_fin_alim']))
        {
            if ($fonctions->verifiedate($_POST['date_debut_alim']) and $fonctions->verifiedate($_POST['date_fin_alim']))
            {
                $datedebutalim = $fonctions->formatdatedb($_POST['date_debut_alim']);
                $datefinalim = $fonctions->formatdatedb($_POST['date_fin_alim']);
                if ($datefinalim < $datedebutalim)
                {
                    $msgerror = $msgerror . "Alimentation CET : Il y a une incohérence dans les dates (date début > date fin). <br>";
                    //echo "Incohérence dates (date début > date fin). <br>";
                }
                else
                {
                    $fonctions->debutalimcet($datedebutalim);
                    $fonctions->finalimcet($datefinalim);
                    $datecampagnealimupdate = true;
                }
            }
            else
            {
                $msgerror = $msgerror . "Au moins une des dates de l'intervalle d'alimentation n'est pas valide. <br>";
                //echo "Au moins une des dates de l'intervalle d'alimentation n'est pas valide. <br>";
            }
        }
        
        // PARAMETRAGE DU CALENDRIER DE DROIT D'OPTION
        //if (isset($_POST['valider_cal_option']))
        $datecampagneoptionupdate = false;
        if (isset($_POST['date_debut_option']) and isset($_POST['date_fin_option']))
        {
            if ($fonctions->verifiedate($_POST['date_debut_option']) and $fonctions->verifiedate($_POST['date_fin_option']))
            {
                $datedebutopt = $fonctions->formatdatedb($_POST['date_debut_option']);
                $datefinopt = $fonctions->formatdatedb($_POST['date_fin_option']);
                if ($datefinopt < $datedebutopt)
                {
                    $msgerror = $msgerror . "Droit d'option CET : Il y a une incohérence dans les dates (date début > date fin). <br>";
                    //echo "Incohérence dates (date début > date fin). <br>";
                }
                else
                {
                    $fonctions->debutoptioncet($datedebutopt);
                    $fonctions->finoptioncet($datefinopt);
                    $datecampagneoptionupdate = true;
                }
            }
            else
            {
                $msgerror = $msgerror . "Au moins une des dates de l'intervalle d'option n'est pas valide. <br>";
                //echo "Au moins une des dates de l'intervalle d'option n'est pas valide. <br>";
            }
        }
        
        //if (isset($_POST['valider_param_plafond']))
        $plafondupdate = false;
        $plafondreferenceupdate=false;
        if (isset($_POST['plafondcet']))
        {
            $constantename = 'PLAFONDCET';
            $plafondcet = trim($_POST['plafondcet']);
            if (!is_numeric($plafondcet) || !is_int($plafondcet+0) || $plafondcet < 0)
            {
                $msgerror = $msgerror . "Le nombre de jours maximum doit être un entier positif. <br>";
                //echo "Le nombre de jours maximum doit être un entier positif. <br>";
            }
            else
            {
                $erreur = $fonctions->enregistredbconstante($constantename, $plafondcet);
                if (strlen($erreur)>0)
                {
                    if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                    $msgerror = $msgerror . $erreur;
                }
                else
                {
                    $plafondupdate = true;
                }
            }
            $constantename = 'PLAFONDREFERENCECET';
            $plafondreferencecet = trim($_POST['plafondreferencecet']);
            if (!is_numeric($plafondreferencecet) || !is_int($plafondreferencecet+0) || $plafondreferencecet < 0)
            {
                $msgerror = $msgerror . "Le plafond de référence doit être un entier positif. <br>";
                //echo "Le nombre de jours maximum doit être un entier positif. <br>";
            }
            else
            {
                $erreur = $fonctions->enregistredbconstante($constantename, $plafondreferencecet);
                if (strlen($erreur)>0)
                {
                    if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                    $msgerror = $msgerror . $erreur;
                }
                else
                {
                    $plafondreferenceupdate = true;
                }
            }
        }
        
        $supprok = false;
        $signataireupdate = false;
        if (isset($_POST['valider_signataire_cet']))
        {
            $msgerror = "";
            $constantename = 'CETSIGNATAIRE';
            if (isset($_POST['supprsignataire']))
            {
                $tabsuppr = $_POST['supprsignataire'];
                $signataireliste = '';
                if ($fonctions->testexistdbconstante($constantename))
                {
                    $signataireliste = $fonctions->liredbconstante($constantename);
                }
                $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
                foreach($tabsuppr as $niveau => $infos)
                {
                    foreach($infos as $key => $valeur)
                    {
                        //var_dump($niveau);
                        //var_dump($key);
                        unset($tabsignataire[$niveau][$key]);
                    }
                }
                $stringsignataire = $fonctions->cetsignatairetostring($tabsignataire);
                $erreur = $fonctions->enregistredbconstante($constantename, $stringsignataire);
                if (strlen($erreur)>0)
                {
                    if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                    $msgerror = $msgerror . $erreur;
                }
                else
                {
                    $supprok = true;
                }
            }
            
            $newlevelsignataire = '';
            $newtypesignataire = '';
            $newidsignataire = '';
            $structureid='';
            if (isset($_POST['newlevelsignataire']))
            {
                $newlevelsignataire = trim($_POST['newlevelsignataire']);
            }
            if (isset($_POST['newtypesignataire']))
            {
                $newtypesignataire = trim($_POST['newtypesignataire']);
            }
            if (isset($_POST['newidsignataire']) and $newtypesignataire==cet::SIGNATAIRE_AGENT)
            {
                $newidsignataire = trim($_POST['newidsignataire']);
            }
            if (isset($_POST['structureid']) and ($newtypesignataire==cet::SIGNATAIRE_STRUCTURE or $newtypesignataire==cet::SIGNATAIRE_RESPONSABLE))
            {
                $structureid = trim($_POST['structureid']);
            }
            if (isset($_POST['specialuserid']) and $newtypesignataire==cet::SIGNATAIRE_SPECIAL)
            {
                $newidsignataire = trim($_POST['specialuserid']);
            }
            
            //var_dump($newlevelsignataire);
            //var_dump($newtypesignataire);
            //var_dump($newidsignataire);
            
            if ($newidsignataire == '' and $structureid == '' and !isset($_POST['supprsignataire']))
            {
                // On n'a pas les infos nécessaires => Error
                if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                $stringerror = "Vous avez sélectionné le type de signataire " . cet::SIGNATAIRE_LIBELLE[$newtypesignataire] . " mais vous n'avez pas renseigné ";
                if ($newtypesignataire==cet::SIGNATAIRE_AGENT)
                {
                    $stringerror = $stringerror . " d'agent.";
                }
                elseif ($newtypesignataire==cet::SIGNATAIRE_STRUCTURE or $newtypesignataire==cet::SIGNATAIRE_RESPONSABLE)
                {
                    $stringerror = $stringerror . "de structure.";
                }
                elseif ($newtypesignataire==cet::SIGNATAIRE_SPECIAL)
                {
                    $stringerror = $stringerror . "d'utilisateur spécial.";
                }
                if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                $msgerror = $msgerror . $stringerror;
            }
            elseif ($newidsignataire != '' or $structureid !='')
            {
                $constantename = 'CETSIGNATAIRE';
                $signataireliste = '';
                if ($fonctions->testexistdbconstante($constantename))
                {
                    $signataireliste = $fonctions->liredbconstante($constantename);
                }
                $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
                //var_dump($tabsignataire);
                if ($newtypesignataire == cet::SIGNATAIRE_AGENT or $newtypesignataire == cet::SIGNATAIRE_SPECIAL)
                {
                    $tabsignataire = $fonctions->cetsignataireaddtoarray($newlevelsignataire,$newtypesignataire,$newidsignataire,$tabsignataire);
                }
                elseif ($newtypesignataire == cet::SIGNATAIRE_STRUCTURE or $newtypesignataire == cet::SIGNATAIRE_RESPONSABLE)
                {
                    $tabsignataire = $fonctions->cetsignataireaddtoarray($newlevelsignataire,$newtypesignataire,$structureid,$tabsignataire);
                }
                else
                {
                    if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                    $msgerror = $msgerror . "Le type de signataire $newtypesignataire n'est pas géré !";
                }
                //var_dump($tabsignataire);
                $stringsignataire = $fonctions->cetsignatairetostring($tabsignataire);
                //var_dump($stringsignataire);
                
                $erreur = $fonctions->enregistredbconstante($constantename, $stringsignataire);
                if (strlen($erreur)>0)
                {
                    if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                    $msgerror = $msgerror . $erreur;
                }
                else
                {
                    $signataireupdate = true;
                }
            }
        }
        
        if ($msgerror != '')
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, $msgerror);
        }
        if ($plafondupdate or $datecampagneoptionupdate or $datecampagnealimupdate or $signataireupdate or $plafondreferenceupdate)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont enregistrées");
        }
        if ($supprok)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données ont été supprimées");
        }
    }
    
    /////////////////////////////////////////////////////////////////////
    // On traite les données postées sur l'onglet des TELETRAVAIL      //
    /////////////////////////////////////////////////////////////////////
    if ($current_tab == 'tab_teletravail')
    {
        $erreur = "";
        if (isset($_POST['modification']))
        {
            $modifdate = false;
            $suppression = false;
            $tabdebut = array();
            if (isset($_POST["date_debut_indem"])) $tabdebut = $_POST["date_debut_indem"];
            $tabfin = array();
            if (isset($_POST["date_fin_indem"])) $tabfin = $_POST["date_fin_indem"];
            $tabmontant = array();
            if (isset($_POST["montant"])) $tabmontant = $_POST["montant"];
            
            foreach ($tabdebut as $key => $indemdebutsaisie)
            {
                $indemfinsaisie = $tabfin[$key];
                $infos = explode("_",$key);
                $indemdebutkey = $infos[0];
                $indemfinkey = $infos[1];
                if (($fonctions->formatdatedb($indemdebutsaisie)<$fonctions->formatdatedb($indemdebutkey)) or ($fonctions->formatdatedb($indemfinsaisie)>$fonctions->formatdatedb($indemfinkey)))
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "Il n'est pas possible d'avancer la date de début ou de repousser la date de fin d'une indemnité.";!
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
                elseif (($fonctions->formatdatedb($indemdebutsaisie)!=$fonctions->formatdatedb($indemdebutkey)) or ($fonctions->formatdatedb($indemfinsaisie)!=$fonctions->formatdatedb($indemfinkey)))
                {
                    if ($fonctions->formatdatedb($indemdebutsaisie)>$fonctions->formatdatedb($indemfinsaisie))
                    {
                        if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                        $erreur = $erreur . "La date de début est supérieure à la date de fin de l'indemnité.";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                    }
                    else
                    {
                        $modifdate = true;
                    }
                }
                
            }
            
            if (strlen($erreur)==0)
            {
                $datastring = "";
                foreach ($tabdebut as $key => $indemdebutsaisie)
                {
                    $indemfinsaisie = $tabfin[$key];
                    $montant = $tabmontant[$key];
                    if (strlen($datastring)>0) $datastring = $datastring . ";";
                    $datastring = $datastring . $fonctions->formatdatedb($indemdebutsaisie) . '|' . $fonctions->formatdatedb($indemfinsaisie) . '|' . floatval(str_replace(',','.',$montant));
                }
                $constante = "INDEMNITETELETRAVAIL";
                $fonctions->enregistredbconstante($constante, $datastring);
                /*
                 $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
                 $query = mysqli_query($dbcon, $update);
                 */
            }
            
            
            $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
            $cancelarray = array();
            if (isset($_POST["cancelindem"]) and strlen($erreur)==0)
            {
                $cancelarray = $_POST["cancelindem"];
                // On va réorganiser les indemnités par date de début pour quelles soit dans l'ordre chronologique
                $newtabindem = array();
                foreach ($tabindem as $indem)
                {
                    $newtabindem[$fonctions->formatdatedb($indem['datedebut']) . "_" . $fonctions->formatdatedb($indem['datefin']) . "_" . str_replace(',','.',$indem['montant'])] = $indem;
                }
                unset ($tabindem);
                $tabindem = $newtabindem;
                foreach($cancelarray as $keyvalue)
                {
                    if (isset($tabindem[$keyvalue]))
                    {
                        unset($tabindem[$keyvalue]);
                        $suppression = true;
                    }
                    else
                    {
                        if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                        $erreur = $erreur . "Vous ne pouvez pas supprimer une indemnité que vous venez de modifier.";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                    }
                }
                $datastring = "";
                foreach ($tabindem as $indem)
                {
                    if (strlen($datastring)>0) $datastring = $datastring . ";";
                    $datastring = $datastring . $indem['datedebut'] . '|' . $indem['datefin'] . '|' . str_replace('.',',',$indem['montant']);
                }
                $constante = "INDEMNITETELETRAVAIL";
                $fonctions->enregistredbconstante($constante, $datastring);
                
                /*
                 $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
                 $query = mysqli_query($dbcon, $update);
                 */
            }
            elseif (!isset($_POST["cancelindem"]) and !$modifdate and strlen($erreur)==0)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Aucune indemnité n'est selectionnée pour suppression.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            
            if ($erreur != '')
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
            }
            if ($modifdate)
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont modifiées.");
            }
            if ($suppression)
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont supprimées.");
            }
        }
        
        if (isset($_POST['creation_indem']))
        {
            $datedebutindem = trim($_POST['date_debut_newindem']['newindem']);
            $datefinindem = trim($_POST['date_fin_newindem']['newindem']);
            $montantindem = trim(str_replace(',','.',$_POST['montantnew']));
            
            $dateok = true;
            if (!$fonctions->verifiedate($datedebutindem))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début de l'indemnité n'est pas correcte ou définie.";
                $dateok = false;
            }
            if (!$fonctions->verifiedate($datefinindem))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de fin de l'indemnité n'est pas correcte ou définie.";
                $dateok = false;
            }
            if ($dateok and $fonctions->formatdatedb($datedebutindem)>$fonctions->formatdatedb($datefinindem))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début est supérieure à la date de fin de l'indemnité.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
            if ($dateok)
            {
                // On va vérifier que les conventions ne se chevauchent pas
                $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
                $datedebutindem = $fonctions->formatdatedb($datedebutindem);
                $datefinindem = $fonctions->formatdatedb($datefinindem);
                foreach ($tabindem as $indem)
                {
                    // S'il y a chevauchement
                    if (($datedebutindem <= $indem["datedebut"] and $datefinindem >= $indem["datedebut"])
                        or ($datedebutindem <= $indem["datefin"] and $datefinindem >= $indem["datefin"])
                        or ($datedebutindem <= $indem["datedebut"] and $datefinindem >= $indem["datefin"])
                        or ($datedebutindem >= $indem["datedebut"] and $datefinindem <= $indem["datefin"]))
                    {
                        $dateok = false;
                        if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                        $erreur = $erreur . "La date de début et la date de fin de l'indemnité chevauche une indemnité existante.<br>Veuillez modifier les indemnités existantes.";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                        break; // On sort de la boucle car on a trouvé au moins un chevauchement
                    }
                }
            }
            if (!is_numeric($montantindem) || $montantindem < 0)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Le montant de l'indemnité doit être un nombre positif. <br>";
                $dateok = false;
            }
            
            if ($dateok)
            {
                //echo "On va sauvegarder les infos !<br>";
                // On ajoute l'indemnité dans le tableau existant
                $datedebutindem = $fonctions->formatdatedb($datedebutindem);
                $datefinindem = $fonctions->formatdatedb($datefinindem);
                unset ($indem);
                $indem = array('datedebut' => $datedebutindem, 'datefin' => $datefinindem , 'montant' => $montantindem);
                $tabindem[] = $indem;
                // On va réorganiser les indemnités par date de début pour quelles soit dans l'ordre chronologique
                $newtabindem = array();
                foreach ($tabindem as $indem)
                {
                    $newtabindem[$fonctions->formatdatedb($indem['datedebut'])] = $indem;
                }
                unset ($tabindem);
                $tabindem = $newtabindem;
                ksort($tabindem);
                //var_dump($tabindem);
                $datastring = "";
                foreach ($tabindem as $indem)
                {
                    if (strlen($datastring)>0) $datastring = $datastring . ";";
                    $datastring = $datastring . $indem['datedebut'] . '|' . $indem['datefin'] . '|' . str_replace('.',',',$indem['montant']);
                }
                $constante = "INDEMNITETELETRAVAIL";
                $fonctions->enregistredbconstante($constante, $datastring);
                /*
                 $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
                 $query = mysqli_query($dbcon, $update);
                 */
            }
            
            if ($erreur != '')
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
            }
            else
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont enregistrées");
            }
        }
    }
    
    //////////////////////////////////////////////////////////////////////////////
    // On traite les données postées sur l'onglet des UTILISATEURS SPECIAUX     //
    //////////////////////////////////////////////////////////////////////////////
    if ($current_tab == 'tab_utilisateurs')
    {
        if (isset($_POST['valid_specialuser']))
        {
            $rhcancel = array();
            if (isset($_POST['rhcancel']))
            {
                $rhcancel = $_POST['rhcancel'];
                foreach ($rhcancel as $rhagentid => $rhvalue)
                {
                    $rhagent = new agent($dbcon);
                    if (strcasecmp($rhvalue,'yes')==0 and  $rhagent->load($rhagentid))
                    {
                        $rhagent->enregistreprofilrh(array());
                    }
                    unset($rhagent);
                }
            }
                
//            $newprofilerh = trim($_POST['newprofilRH']);
            $newprofileCET = '';
            $newprofilCONGES = '';
            $newprofilTELETRAVAIL = '';
            if (isset($_POST['newprofilCET'])) $newprofileCET = trim($_POST['newprofilCET']);
            if (isset($_POST['newprofilCONGES'])) $newprofilCONGES = trim($_POST['newprofilCONGES']);
            if (isset($_POST['newprofilTELETRAVAIL'])) $newprofilTELETRAVAIL = trim($_POST['newprofilTELETRAVAIL']);
            $newiduserrh = trim($_POST['newiduserrh']);
            
            $tabprofilrh = array();
            if (strpos($newprofileCET, agent::PROFIL_RHCET)!==false)
            {
                $tabprofilrh[] = agent::PROFIL_RHCET;
            }
            if (strpos($newprofilCONGES, agent::PROFIL_RHCONGE)!==false)
            {
                $tabprofilrh[] = agent::PROFIL_RHCONGE;
            }
            if (strpos($newprofilTELETRAVAIL, agent::PROFIL_RHTELETRAVAIL)!==false)
            {
                $tabprofilrh[] = agent::PROFIL_RHTELETRAVAIL;
            }
            $rhagent = new agent($dbcon);
            if ($rhagent->load($newiduserrh))
            {
                $rhagent->enregistreprofilrh($tabprofilrh);
            }
            
            $nomcronuser = $_POST['nomcronuser'];
            $prenomcronuser = $_POST['prenomcronuser'];
            $mailcronuser = $_POST['mailcronuser'];
            $cronuser = new agent($dbcon);
            $cronuser->nom($nomcronuser);
            $cronuser->prenom($prenomcronuser);
            $cronuser->mail($mailcronuser);
            $cronuser->store(SPECIAL_USER_IDCRONUSER);

            $nomlisterhuser = $_POST['nomlisterhuser'];
            $prenomlisterhuser = $_POST['prenomlisterhuser'];
            $maillisterhuser = $_POST['maillisterhuser'];
            $listerhuser = new agent($dbcon);
            $listerhuser->nom($nomlisterhuser);
            $listerhuser->prenom($prenomlisterhuser);
            $listerhuser->mail($maillisterhuser);
            $listerhuser->store(SPECIAL_USER_IDLISTERHUSER);
        }
    }
    
    /////////////////////////////////////////////////////////////////////
    // On traite les données postées sur l'onglet d'ADMINISTRATION     //
    /////////////////////////////////////////////////////////////////////
    if ($current_tab == 'tab_admin')
    {
        $msg_erreur = "";
        
        // Si on est en train d'enregistrer des modifications
        if (isset($_POST['modif_adminform']))
        {
            $modelealim = trim($_POST['modelealim']);
            $modeleoption = trim($_POST['modeleoption']);
            if (!is_numeric($modelealim) || !is_int($modelealim+0) || $modelealim < 0)
            {
                $msg_erreur = $msg_erreur . "Le modèle eSignature de l'alimentation CET doit être un entier positif.";
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            else
            {
                $constantename = "IDMODELALIMCET";
                $msg_erreur = $msg_erreur . $fonctions->enregistredbconstante($constantename, $modelealim);
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            if (!is_numeric($modeleoption) || !is_int($modeleoption+0) || $modeleoption < 0)
            {
                $msg_erreur = $msg_erreur . "Le modèle eSignature du droit d'option sur CET doit être un entier positif.";
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            else
            {
                $constantename = "IDMODELOPTIONCET";
                $msg_erreur = $msg_erreur . $fonctions->enregistredbconstante($constantename, $modeleoption);
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            $jourdebutperiode = trim($_POST['jourdebutperiode']);
            $moisdebutperiode = trim($_POST['moisdebutperiode']);
            $debutperiode = $moisdebutperiode . $jourdebutperiode;
            $testdate = $jourdebutperiode . "/" . $moisdebutperiode . "/" . date('Y');
            //var_dump($testdate);
            if (!$fonctions->verifiedate($testdate))
            {
                $msg_erreur = $msg_erreur . "La date de début de la période des congés n'est pas une date valide.";
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            else
            {
                $constantename = "DEBUTPERIODE";
                $msg_erreur = $msg_erreur . $fonctions->enregistredbconstante($constantename, $debutperiode);
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
                $finperiode =  date('Y') . $moisdebutperiode . $jourdebutperiode ;
                $timestamp = strtotime($finperiode);
                $finperiode = date("md", strtotime("-1days", $timestamp)); // On passe à  la veille mais on ne récupère que le mois et l'année
                //var_dump($finperiode);
                $constantename = "FINPERIODE";
                $msg_erreur = $msg_erreur . $fonctions->enregistredbconstante($constantename, $finperiode);
                if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            }
            
            $limite_conge_periode = trim($_POST['limite_conge_periode']);
            $constantename = "LIMITE_CONGE_PERIODE";
            $msg_erreur = $msg_erreur . $fonctions->enregistredbconstante($constantename, $limite_conge_periode);
            if (strlen($msg_erreur)>0) $msg_erreur = $msg_erreur . '<br>';
            
            if ($msg_erreur!="")
            {
                //var_dump($msg_erreur);
                echo $fonctions->showmessage(fonctions::MSGERROR, "$msg_erreur");
            }
            else
            {
                echo $fonctions->showmessage(fonctions::MSGINFO, "Les données ont été enregistrées.");
            }
        }
    }
    
    
    // On initialise l'onglet par défaut si sa valeur n'est pas définie
    if (trim($current_tab)=='') $current_tab = 'tab_conges';
    
?>
    <form name='form_parametrage' id='form_parametrage' method='post' >

    <div class="tabs">
<?php 
    // Si l'utilisateur a le profil agent::PROFIL_RHCONGE ==> On affiche l'onglet de gestion des congés
    if ($user->estprofilrh(agent::PROFIL_RHCONGE) or $CASAdminId!==false)
    {
        echo "<span";
        if ($current_tab == 'tab_conges') echo " class='tab_active' ";
        echo " data-tab-value='#tab_conges'>Congés</span>";
    }
    
    // Si l'utilisateur a le profil agent::PROFIL_RHCET ==> On affiche l'onglet de gestion des CET
    if ($user->estprofilrh(agent::PROFIL_RHCET) or $CASAdminId!==false)
    {
        echo "<span";
        if ($current_tab == 'tab_cet') echo " class='tab_active' ";
        echo " data-tab-value='#tab_cet'>CET</span>";
    }
    
    // On affiche l'onglet de gestion du télétravail
    echo "<span";
    if ($current_tab == 'tab_teletravail') echo " class='tab_active' ";
    echo " data-tab-value='#tab_teletravail'>Télétravail</span>";
    
    // Si l'utilisateur a le profil agent::PROFIL_RHCET ET le profil agent::PROFIL_RHCONGE ET le profil agent::PROFIL_RHTELETRAVAIL ==> On affiche l'onglet de gestion des utilisateurs spséciaux
    if (($user->estprofilrh(agent::PROFIL_RHCONGE) and $user->estprofilrh(agent::PROFIL_RHCET) and $user->estprofilrh(agent::PROFIL_RHTELETRAVAIL)) or $CASAdminId!==false)
    {
        echo "<span";
        if ($current_tab == 'tab_utilisateurs') echo " class='tab_active' ";
        echo " data-tab-value='#tab_utilisateurs'>Utilisateurs spéciaux</span>";
    }
    
    // Si l'utilisateur CAS (donc le vrai utilisateur) est un administrateur ==> On affiche l'onglet d'administration
    if ($CASAdminId!==false)
    {
        echo "<span";
        if ($current_tab == 'tab_admin') echo " class='tab_active' "; 
        echo " data-tab-value='#tab_admin'>Administration</span>";  
    }
?>        
    </div>
  
    <div class="tab-content">
<!--         
        #############################################################
        #                                                           #
        # ICI COMMENCE LE CONTENU DE L'ONGLET CONGES                #
        #                                                           #
        #############################################################
-->        

        <div class="tabs__tab <?php if ($current_tab == 'tab_conges') echo " active "; ?>" id="tab_conges" data-tab-info>
<?php 

    /////////////////////////////////////////////////////////////
    // Affichage des périodes de fermeture de l'établissement
    $anneeref = $fonctions->anneeref();
    $nbanneeaffichee = 3;
    $liste=array();
    for ($index=$nbanneeaffichee; $index>=0; $index--)
    {
        $periode = new periodeobligatoire($dbcon);
        $liste = array_merge($periode->load($anneeref-$index),$liste);
    }
    ksort($liste);
    //var_dump ($liste);
        
        
    // On crée l'entete du tableau et on affiche chaque période enregistrée
    echo "<form name='selectperiode'  method='post' >";
    echo "<br>Période de fermeture de l'établissement : <br>";
    echo "<table class='tableausimple'>";
    echo "<tr><td class='titresimple'>Année référence</td><td class='titresimple'>Date début</td><td class='titresimple'>Date fin</td><td class='titresimple'>Supprimer</td></tr>";
    if (count($liste)>0)
    {
        foreach($liste as $key => $dateelement)
        {
            $elementanneeref = $fonctions->anneeref($fonctions->formatdate($dateelement["datedebut"]));
            echo "<tr><td class='cellulesimple'>" . $elementanneeref . "/" . ($elementanneeref+1) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datedebut"]) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datefin"]) . "</td><td class='cellulesimple'><center><input type='checkbox' name=cancel[" . $key . "] value='yes' /></center></td></tr>";
        }
    }
    echo "<tr><td class='cellulesimple'>Nouvelle période : </td>";
    
    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
    $calendrierid_deb = "date_debut";
    $calendrierid_fin = "date_fin";
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_deb . '" ).datepicker({minDate: $( "#' . $calendrierid_deb . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_deb . '").change(function () {
        			$("#' . $calendrierid_fin . '").datepicker("destroy");
        			$("#' . $calendrierid_fin . '").datepicker({minDate: $("#' . $calendrierid_deb . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
        	});
        });
        </script>
    ';
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_fin . '" ).datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_fin . '").change(function () {
        			$("#' . $calendrierid_deb . '").datepicker("destroy");
        			$("#' . $calendrierid_deb . '").datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin . '").datepicker("getDate")});
        	});
        });
        </script>
    ';
?>
    		<td class='cellulesimple'><input class="calendrier" type=text name=date_debut
    			id=<?php echo $calendrierid_deb ?> size=10
    			minperiode='<?php echo $fonctions->formatdate(($anneeref-$nbanneeaffichee+1) . $fonctions->debutperiode()); ?>'
    			maxperiode='<?php echo $fonctions->formatdate(($anneeref+1) . $fonctions->finperiode()); ?>'></td>
    		<td class='cellulesimple'><input class="calendrier" type=text name=date_fin
    			id=<?php echo $calendrierid_fin ?> size=10
    			minperiode='<?php echo $fonctions->formatdate(($anneeref-$nbanneeaffichee+1) . $fonctions->debutperiode()); ?>'
    			maxperiode='<?php echo $fonctions->formatdate(($anneeref+1) . $fonctions->finperiode()); ?>'></td>
            
<?php             
    echo "<td class='cellulesimple'></td></tr>";
    echo "</table>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_conges'>";
    echo "<br>";
    echo "<input type='submit' name='valid_periode' value='Soumettre' >";
    echo "</form>";
    
    /////////////////////////////////////////////////////////
    // Affichage de la gestion du nombre de jours de congés
    echo "<br><br>";
    $nbanneeaafficher = 2;
    $anneeref = $fonctions->anneeref();
    echo "<form name='nbjoursform'  method='post' >";
    echo "Nombre de jours de congés annuels : <br>";
    echo "<table>";
    echo "<tr><td><select name='anneeconge' id='anneeconge'>";
    for ($index=0; $index<$nbanneeaafficher; $index++)
    {
        echo "<option value='" . ($anneeref+$index)  ."'>" . ($anneeref+$index) . "/" . ($anneeref+$index+1) . "</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "<td><input type=text id='nbjoursannuel' name='nbjoursannuel' value='' maxlength='3' size='4'></td>";
    echo "</tr>";
    echo "</table>";
    for ($index=0; $index<$nbanneeaafficher; $index++)
    {
        if ($fonctions->testexistdbconstante('NBJOURS' . ($anneeref+$index)))
        {
            $valeurconstante = $fonctions->liredbconstante('NBJOURS' . ($anneeref+$index));
            echo "Le nombre de jours de congés pour " . ($anneeref+$index) . "/" . ($anneeref+$index+1) . " est de : $valeurconstante <br>";
        }
        else
        {
            echo "Le nombre de jours de congés pour " . ($anneeref+$index) . "/" . ($anneeref+$index+1) . " n'est pas défini<br>";
        }
    }
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_conges'>";
    echo "<br>";
    echo "<input type='submit' name='valid_nbjours' value='Soumettre' >";
    echo "</form>";
    
    /////////////////////////////////////////////////////////
    // Affichage de la date de fin de report des congés
    echo "<br>";
    echo "<form name='reportform'  method='post' >";
    $dbconstante = 'FIN_REPORT';
    $finreport = '';
    if ($fonctions->testexistdbconstante($dbconstante))  $finreport = $fonctions->liredbconstante($dbconstante);
    $jourreport = substr($finreport,2);
    $moisreport = substr($finreport,0,2);
    echo "<table><tr>";
    echo "<td>Date de fin de report des congés : "; //<input type='text' name='finreport' value='$finreport'></td>";
    echo "<select name='jourreport' id='jourreport'>";
    for ($index=1; $index<=31; $index++)
    {
        $selecttext = '';
        if ($index == ($jourreport+0)) $selecttext=' selected ';
        echo "<option value='" . str_pad($index,  2, "0",  STR_PAD_LEFT) ."' $selecttext>" . str_pad($index,  2, "0",  STR_PAD_LEFT) . "</option>";
    }
    echo "</select>";
    echo "<select name='moisreport' id='moisreport'>";
    for ($index=1; $index<=12; $index++)
    {
        $selecttext = '';
        if ($index == ($moisreport+0)) $selecttext=' selected ';
        echo "<option value='" . str_pad($index,  2, "0",  STR_PAD_LEFT) ."' $selecttext>" . $fonctions->nommoisparindex($index) . "</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr></table>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_conges'>";
    echo "<br>";
    echo "<input type='submit' name='valid_report' value='Soumettre' >";
    echo "</form>";
    
    ///////////////////////////////////////////////////////////////
    // Affichage de la synchronisation des jours de congés
    echo "<br><br>";
    $anneeref = $fonctions->anneeref();
    $nbanneeaimporter = 2;
    for ($index = 0 ; $index<$nbanneeaimporter ; $index++)
    {
        $tabannees[$index] = $anneeref+$index;
    }
    //$tabannees = array($fonctions->anneeref(), $fonctions->anneeref()+1, $fonctions->anneeref()+2);
    echo "<form name='synchroferiesform'  method='post' >";
    $stringannee = "";
    $separateur = "";
    foreach($tabannees as $key => $annee)
    {
        if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; elseif (strlen($stringannee)>0) $separateur = ', ';
        $stringannee = $stringannee . $separateur . $annee . '/' . ($annee+1);
        echo "<input type='hidden' name='anneesynchro[$key]' value='" . $annee . "'>";
    }
    echo "Intégration des jours fériés dans G2T (" . $stringannee . ") : <br>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_conges'>";
    echo "<br>";
    echo "<input type='submit' name='valid_synchroferies' value='Soumettre' >";
    echo "</form>";
    
?>
        </div>
        
<!--         
        ###########################################################
        #                                                         #
        # ICI COMMENCE LE CONTENU DE L'ONGLET CET                 #
        #                                                         #
        ###########################################################
-->        
        <div class="tabs__tab <?php if ($current_tab == 'tab_cet') echo " active "; ?>" id="tab_cet" data-tab-info>
<?php 

    $msgerror = '';
    
    $constantename = 'CETSIGNATAIRE';
    $signataireliste = '';
    $tabsignataire = array();
    if ($fonctions->testexistdbconstante($constantename))
    {
        $signataireliste = $fonctions->liredbconstante($constantename);
    }
    $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
    $disablebuttonsubmit = "";
    if (!isset($tabsignataire[3]) or !isset($tabsignataire[4]) or !isset($tabsignataire[5]))
    {
        echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible de modifier les dates de campagnes CET <br> car tous les niveaux du circuit ne sont pas définis.");
        $disablebuttonsubmit = " disabled ";
    }
    
    
    
    $constantename = 'PLAFONDCET';
    $plafondparam = 0;
    if ($fonctions->testexistdbconstante($constantename)) $plafondparam = $fonctions->liredbconstante($constantename);
    
    $constantename = 'PLAFONDREFERENCECET';
    $plafondreferencecet = 45;
    if ($fonctions->testexistdbconstante($constantename)) $plafondreferencecet = $fonctions->liredbconstante($constantename);
    

?>
            <form name="frm_param_cet" method="post">
            
                <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
                	<br>Paramétrage du calendrier de la campagne d'alimentation du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutalimcet()).' - '.$fonctions->formatdate($fonctions->finalimcet());?>)
                	<table>
        	        	<tr>
        		       		<td style='padding-left: 30px;'>Date d'ouverture de la campagne d'alimentation :</td>

<?php
    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
    $calendrierid_deb_alim = "date_debut_alim";
    $calendrierid_fin_alim = "date_fin_alim";
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_deb_alim . '" ).datepicker({minDate: $( "#' . $calendrierid_deb_alim . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb_alim . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_deb_alim . '").change(function () {
        			$("#' . $calendrierid_fin_alim . '").datepicker("destroy");
        			$("#' . $calendrierid_fin_alim . '").datepicker({minDate: $("#' . $calendrierid_deb_alim . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin_alim . '" ).attr("maxperiode")});
        	});
        });
        </script>
    ';
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_fin_alim . '" ).datepicker({minDate: $( "#' . $calendrierid_fin_alim . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin_alim . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_fin_alim . '").change(function () {
        			$("#' . $calendrierid_deb_alim . '").datepicker("destroy");
        			$("#' . $calendrierid_deb_alim . '").datepicker({minDate: $( "#' . $calendrierid_fin_alim . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin_alim . '").datepicker("getDate")});
        	});
        });
        </script>
    ';
    
?>
        	    			<br>
                			<td width=1px><input class="calendrier" type=text name=date_debut_alim
                				id=<?php echo $calendrierid_deb_alim ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->debutalimcet()) ?>'></td>
        	    		</tr>
            			<tr>
            				<td style='padding-left: 30px;'>Date de fermeture de la campagne d'alimentation :</td>
                			<td width=1px><input class="calendrier" type=text name=date_fin_alim
                				id=<?php echo $calendrierid_fin_alim ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->finalimcet()) ?>'></td>
        	    		</tr>
            		</table>

<!--    AFFICHAGE DU PARAMETRAGE DU DROIT D'OPTION -->
        			<br><br>
        	        <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
                	<br>Paramétrage du calendrier de la campagne de droit d'option du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutoptioncet()).' - '.$fonctions->formatdate($fonctions->finoptioncet());?>)
                	<table>
                		<tr>
                			<td style='padding-left: 30px;'>Date d'ouverture de la campagne de droit d'option :</td>
                		
<?php
    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
    $calendrierid_deb_option = "date_debut_option";
    $calendrierid_fin_option = "date_fin_option";
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_deb_option . '" ).datepicker({minDate: $( "#' . $calendrierid_deb_option . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb_option . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_deb_option . '").change(function () {
        			$("#' . $calendrierid_fin_option . '").datepicker("destroy");
        			$("#' . $calendrierid_fin_option . '").datepicker({minDate: $("#' . $calendrierid_deb_option . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin_option . '" ).attr("maxperiode")});
        	});
        });
        </script>
    ';
    echo '
        <script>
        $(function()
        {
        	$( "#' . $calendrierid_fin_option . '" ).datepicker({minDate: $( "#' . $calendrierid_fin_option . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin_option . '" ).attr("maxperiode")});
        	$( "#' . $calendrierid_fin_option . '").change(function () {
        			$("#' . $calendrierid_deb_option . '").datepicker("destroy");
        			$("#' . $calendrierid_deb_option . '").datepicker({minDate: $( "#' . $calendrierid_fin_option . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin_option . '").datepicker("getDate")});
        	});
        });
        </script>
    ';
    
?>
                			<br>
                			<td width=1px><input class="calendrier" type=text name=date_debut_option
                				id=<?php echo $calendrierid_deb_option ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->debutoptioncet()) ?>'></td>
        	    		</tr>
        	    		<tr>
            				<td style='padding-left: 30px;'>Date de fermeture de la campagne de droit d'option :</td>
            				<td width=1px><input class="calendrier" type=text name=date_fin_option
            					id=<?php echo $calendrierid_fin_option ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->finoptioncet()) ?>'></td>
        	    		</tr>
        	    	</table>
        	 		<br><br>
            		Nombre de jours maximum sur CET : <input type='text' name='plafondcet' value='<?php echo $plafondparam;?>'>
            		<br>
            		Plafond de référence pour alimenter le CET : <input type='text' name=plafondreferencecet value='<?php echo $plafondreferencecet;?>'>
            		<br><br>
                    <input type='hidden' id='current_tab' name='current_tab' value='tab_cet'>
                    <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
            		<input type='submit' name='valider_param_cet' id='valider_param_cet' value='Soumettre' <?php echo $disablebuttonsubmit;?>/>
            </form>
            <br><br>
            <form name="frm_param_cet" method="post">
            
            <table class='tableausimple' id='listeindemnite'>
            	<tr><center>
            		<td class='titresimple'>Niveau du signataire</td>
                    <td class='titresimple'>Type de signataire</td>
                    <td class='titresimple'>Signataire</td>
                    <td class='titresimple'>Supprimer</td>
            	</center></tr>
            	<tr><center>
            		<td class='cellulesimple'><center>Niveau 1</center></td>
            		<td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[1]; ?></center></td>
            		<td class='cellulesimple'><center>Agent demandeur</center></td>
            		<td class='cellulesimple'><center></center></td>
            	</center></tr>
            	<tr><center>
            		<td class='cellulesimple'><center>Niveau 2</center></td>
            		<td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[3]; ?></center></td>
            		<td class='cellulesimple'><center>Structure de l'agent</center></td>
            		<td class='cellulesimple'><center></center></td>
            	</center></tr>
<?php 
                $constantename = 'CETSIGNATAIRE';
                $signataireliste = '';
                $tabsignataire = array();
                if ($fonctions->testexistdbconstante($constantename))
                {
                    $signataireliste = $fonctions->liredbconstante($constantename);
                }

                $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
/*                
                if (!isset($tabsignataire['3']['1_' . constant('SPECIAL_USER_IDLISTERHUSER')]))
                {
                    $tabsignataire = $fonctions->cetsignataireaddtoarray('3', cet::SIGNATAIRE_AGENT, SPECIAL_USER_IDLISTERHUSER, $tabsignataire);
                    $signataireliste = $fonctions->cetsignatairetostring($tabsignataire);
                    $saveerror = $fonctions->enregistredbconstante($constantename, $signataireliste);
                    if ($saveerror != '')
                    {
                        $fonctions->showmessage(fonctions::MSGERROR,$saveerror);
                    }
                    $signataireliste = $fonctions->liredbconstante($constantename);
                    $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
                    //var_dump($tabsignataire);
                }
*/
                if (count($tabsignataire)>0)
                {
                    foreach ($tabsignataire as $niveau => $infosignataires)
                    {
                        foreach ($infosignataires as $idsignataire => $infosignataire)
                        {
?>            	
            				<tr>
                                <td class='cellulesimple'><center>Niveau <?php echo $niveau; ?></center></td>
                                <td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[$infosignataire[0]]; ?></center></td>
<?php               
                            if ($infosignataire[0]==cet::SIGNATAIRE_AGENT or $infosignataire[0]==cet::SIGNATAIRE_SPECIAL)
                            {
                                $spantext = '';
                                $extrastyle = '';
                                $agent = new agent($dbcon); 
                                if (!$agent->load($infosignataire[1]))
                                {
                                    $extrastyle = " celerror resetfont ";
                                    $spantext = '<span data-tip="Problème : L\'agent n\'est pas connu de G2T.">';
                                }
                                elseif (!$fonctions->mailexistedansldap($agent->mail()))
                                {
                                    $extrastyle = " celerror resetfont ";
                                    $spantext = '<span data-tip="Problème : L\'adresse mail de ' . $agent->identitecomplete() . ' n\'est pas connue de LDAP (' . $agent->mail() . ') => Envoi de mail impossible.">';
                                }
                                else
                                {
                                    $spantext = '<span data-tip="' . $agent->mail()  .'">';
                                }
?>                    
			                    <td class='cellulesimple <?php echo $extrastyle; ?>'><?php  echo $spantext; ?><center><?php echo $agent->identitecomplete(); ?></center></td>
<?php 
                            }
                            elseif ($infosignataire[0]==cet::SIGNATAIRE_RESPONSABLE)
                            {
                                $spantext = '';
                                $extrastyle = '';
                                $struct = new structure($dbcon); 
                                $struct->load($infosignataire[1]);
                                $responsable = $struct->responsable();
                                if (trim($responsable->agentid()) == '')
                                {
                                    $extrastyle = " celerror resetfont ";
                                    $spantext = '<span data-tip="Problème : Le responsable de la structure n\'est pas défini ou n\'est pas connu dans G2T.">';
                                }
/*                                
                                elseif ($responsable->estutilisateurspecial())
                                {
                                    $extrastyle = " celwarning resetfont ";
                                    $spantext = '<span data-tip="Problème : Le responsable de la structure est un utilisateur spécial => Envoi de mail impossible.">';
                                }
*/                                
                                elseif (!$fonctions->mailexistedansldap($responsable->mail()))
                                {
                                    $extrastyle = " celerror resetfont ";
                                    $spantext = '<span data-tip="Problème : L\'adresse mail du responsable de la structure (' . $responsable->identitecomplete() . ') n\'est pas connue de LDAP (' . $responsable->mail() . ') => Envoi de mail impossible.">';
                                }
                                else
                                {
                                    $spantext = '<span data-tip="Actuellement : ' . $responsable->identitecomplete()  .' (' . $responsable->mail() . ')">';
                                }
                                
?>
			                    <td class='cellulesimple <?php echo $extrastyle; ?>'  ><?php  echo $spantext; ?><center><?php echo $struct->nomlong() . " (" . $struct->nomcourt() . ")"; ?></center></td>
<?php 
                            }
                            elseif ($infosignataire[0]==cet::SIGNATAIRE_STRUCTURE)
                            {
?>
			                    <td class='cellulesimple'><center><?php $struct = new structure($dbcon); $struct->load($infosignataire[1]); echo $struct->nomlong() . " (" . $struct->nomcourt() . ")"; ?></center></td>
<?php 
                            }
                            else
                            {
?>
			                    <td class='cellulesimple'><center>ERREUR : Le type de signataire n'est pas géré (type : <?php echo $infosignataire[0]; ?>)!</center></td>
<?php 
                            }
                            $disablecheckbox = "";
/*
                            if ($infosignataire[1]==SPECIAL_USER_IDLISTERHUSER) // On ne peut pas supprimer "Gestion de Temps"
                            {
                                $disablecheckbox = ' disabled ';
                            }
*/                            
?>
			                    <td class='cellulesimple'><center><input type='checkbox' <?php echo $disablecheckbox; ?> id='supprsignataire[<?php echo $niveau; ?>][<?php echo $idsignataire; ?>]' name='supprsignataire[<?php echo $niveau; ?>][<?php echo $idsignataire; ?>]'</center></td>
			            	</tr>
<?php
                        }
                    }
                }
?>
				<tr>
                    <td class='cellulesimple'><center>
                        <select name="newlevelsignataire" id="newlevelsignataire">
                            <option value="3">Niveau 3</option>
                            <option value="4">Niveau 4</option>
                            <option value="5">Niveau 5</option>
                        </select>
                    </center></td>
                    <td class='cellulesimple'><center>
                        <select name="newtypesignataire" id="newtypesignataire">
<?php 
                            foreach (cet::SIGNATAIRE_LIBELLE as $codesignataire => $libellesignataire)
                            {
                                echo "<option value='$codesignataire'>$libellesignataire</option>";

                            }
?>
                         </select>
                    </center></td>
                    <td class='cellulesimple'><center>
                    	<input id="usersignataire" name="usersignataire" placeholder="Nom et/ou prenom" autofocus/>
                    	<input type='hidden' id="newidsignataire" name="newidsignataire" class='usersignataire' />
                    	<script>
                    	    //var input_elt = $( ".token-autocomplete input" );
                      	    $( "#usersignataire" ).autocompleteUser(
                        	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "supannEmpId",
                      	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee|staff" } });
                    	</script>
                    </center>
                	<div id='div_structureid' hidden>  <!-- style=' width: 300px;' hidden>  -->
					<select size='1' id='structureid' name='structureid' style='width: 700px;' value=''>
					<option value=''>----- Veuillez sélectionner la structure -----</option>
<?php
                    $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
                    $query = mysqli_query($dbcon, $sql);
                    $erreur = mysqli_error($dbcon);
                    if ($erreur != "") {
                        $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
                        echo $errlog . "<br/>";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    }
                    $structureid=null;
                    while ($result = mysqli_fetch_row($query)) 
                    {
                        $struct = new structure($dbcon);
                        $struct->load($result[0]);
                        affichestructureliste($struct, 0);
                    }
?>
					</select>
                	</div>
                	<div id='div_specialuserid' hidden>  <!-- style=' width: 300px;' hidden>  -->
					<select size='1' id='specialuserid' name='specialuserid' value='' > <!--  style='width: 300px;' > -->
					<option value=''>----- Veuillez sélectionner un utilisateur spécial -----</option>
<?php 
                    $tab_specialuser = $fonctions->listeutilisateursspeciaux();
                    foreach ($tab_specialuser as $idspecial)
                    {
                        $specialuser = new agent($dbcon);
                        if ($specialuser->load($idspecial))
                        {
                            echo "<option value='$idspecial'>" . $specialuser->identitecomplete() . "</option>";
                        }
                    }
?>					
					</select>
					</div>
                    </td>
                    <td class='cellulesimple'><center></center></td>
            	</tr>
            </table>
            <script>
                var selectElement = document.getElementById('newtypesignataire');
                // alert('Plouf' + selectElement.value);
                selectElement.addEventListener('change', (event) => {
                  var idsignataire = document.getElementById('usersignataire');
                  var td_structureid = document.getElementById('td_structureid');
                  var div_structureid = document.getElementById('div_structureid');
                  var div_specialuserid = document.getElementById('div_specialuserid');
                  // alert('valeur de target = ' + event.target.value);
                  // alert('div_structureid => id  = ' + div_structureid);
                  // alert('div_specialuserid => id  = ' + div_specialuserid);
                  if (event.target.value==<?php echo cet::SIGNATAIRE_RESPONSABLE; ?> || event.target.value==<?php echo cet::SIGNATAIRE_STRUCTURE; ?>)
                  {
                     // alert ('On est dans un choix d\'un responsable de structure ou d\'une structure');
                     idsignataire.type='hidden';
                     div_structureid.removeAttribute("hidden");
                     div_specialuserid.setAttribute("hidden", "hidden");
                     
                  }
                  else if (event.target.value==<?php echo cet::SIGNATAIRE_AGENT; ?>)
                  {
                     // alert ('On est dans un choix d\'un agent unitaire');
                     idsignataire.type='text';
                     div_structureid.setAttribute("hidden", "hidden");
					 div_specialuserid.setAttribute("hidden", "hidden");
                  }
                  else if (event.target.value==<?php echo cet::SIGNATAIRE_SPECIAL; ?>)
                  {
                     // alert ('On est dans un choix d\'un utilisateur spécial');
                     idsignataire.type='hidden';
                     div_structureid.setAttribute("hidden", "hidden");
					 div_specialuserid.removeAttribute("hidden");
                  }
                  else
                  {
                     alert ('Erreur : Ce choix de type de signataire n\'est pas géré !');
                  }
                });   
            </script>
    		<br><br>
            <input type='hidden' id='current_tab' name='current_tab' value='tab_cet'>
            <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
    		<input type='submit' name='valider_signataire_cet' id='valider_signataire_cet' value='Soumettre' />
            
            </form>

        </div>

<!--         
        ###################################################################
        #                                                                 #
        # ICI COMMENCE LE CONTENU DE L'ONGLET TELETRAVAIL                 #
        #                                                                 #
        ###################################################################
-->        
        <div class="tabs__tab <?php if ($current_tab == 'tab_teletravail') echo " active "; ?>" id="tab_teletravail" data-tab-info>
<?php

    $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
    
    echo "<form name='form_indem_delete' id='form_indem_delete' method='post' >";
    echo "<br>Liste des indemnités : <br>";
    echo "<table class='tableausimple' id='listeindemnite'>";
    echo "<tr><center><td class='titresimple'>Date début</td>
                      <td class='titresimple'>Date fin</td>
                      <td class='titresimple'>Montant</td>
                      <td class='titresimple'>Annuler</td>";
    echo "</center></tr>";
    
    foreach ($tabindem as $indextabindem => $indem)
    {
    // $tabindem[$indextabindem]["datefin"]
    // $tabindem[$indextabindem]["datedebut"]
    //$montant = str_replace(',','.',$tabindem[$indextabindem]["montant"]);
        $calendrierid_deb = "date_debut_indem";
        $calendrierid_fin = "date_fin_indem";
        $indemid = $indem["datedebut"] . "_" . $indem["datefin"] . "_" . $indem["montant"];
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').change(function () {
        		$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker("destroy");
        		$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
 
	       	$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').change(function () {
       			$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker("destroy");
       			$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php     	        
        echo "<tr>";
        //echo "  <td class='cellulesimple'><center>" . $fonctions->formatdate($indem["datedebut"]) . "</center></td>";
        //echo "  <td class='cellulesimple'><center>" . $fonctions->formatdate($indem["datefin"]) . "</center></td>";
?>
    <td class='cellulesimple'><center>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $indemid . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $indemid .']'?> size=10
        	minperiode='01/01/1900'
        	maxperiode='31/12/2100'
        	value='<?php echo $fonctions->formatdate($indem["datedebut"]) ?>'>
    </center></td>
    
    <td class='cellulesimple'><center>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $indemid . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $indemid . ']' ?>
        	size=10
        	minperiode='01/01/1900'
        	maxperiode='31/12/2100'
        	value='<?php echo $fonctions->formatdate($indem["datefin"]) ?>'>
	</center></td>
<?php
        echo "  <td class='cellulesimple'><center>" . number_format($indem["montant"],2,",","") . " €</center><input type='hidden' name='montant[" . $indemid . "]' id='montant[" . $indemid . "]' value='" . number_format($indem["montant"],2,",","") . "'></td>";  // str_replace('.',',',$indem["montant"])
        echo "  <td class='cellulesimple'><center><input type='checkbox' value='" . $indemid . "' id='" . $indemid . "' name='cancelindem[]' ></center></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_teletravail'>";
    echo "<br><input type='submit' value='Soumettre' name='modification'/>";
    echo "</form>";

    $datedebutindem = "";
    $datefinindem = "";
    $calendrierid_deb = "date_debut_newindem";
    $calendrierid_fin = "date_fin_newindem";
    ?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php
    	echo "<br><br>";
      	echo "Création d'une nouvelle indemnité de télétravail : <br>";
        echo "<form name='form_indemnite_creation' id='form_indemnite_creation' method='post' >";
?>
        <table>
	        <tr>
    		    <td style='padding-left: 30px;'>Date de début de l'indemnité de télétravail : </td>
<?php         
        if ($fonctions->verifiedate($datedebutindem)) {
            $datedebutindem = $fonctions->formatdate($datedebutindem);
    	}
?>
				<td>
                    <input class="calendrier" type=text
                    	name=<?php echo $calendrierid_deb . '[newindem]'?>
                    	id=<?php echo $calendrierid_deb . '[newindem]'?> size=10
                    	minperiode='01/01/1900'
                    	maxperiode='31/12/2099'
                    	value='<?php echo $datedebutindem ?>'>
            	</td>
           </tr>
	       <tr>
    		    <td style='padding-left: 30px;'>Date de fin de l'indemnité de télétravail : </td>
<?php
    	if ($fonctions->verifiedate($datefinindem)) {
    	    $datefinindem = $fonctions->formatdate($datefinindem);
        }      
?>
				<td>
                    <input class="calendrier" type=text
                    	name=<?php echo $calendrierid_fin . '[newindem]' ?>
                    	id=<?php echo $calendrierid_fin . '[newindem]' ?>
                    	size=10
                    	minperiode='01/01/1900'
                    	maxperiode='31/12/2099'
                    	value='<?php echo $datefinindem ?>'>
            	</td>
           </tr>
		   <tr>
				<td style='padding-left: 30px;'>Montant de l'indemnité télétravail : </td>
				<td><input type='text' name='montantnew' value='' maxlength='5' size='5'></td>
		   </tr>
	   </table>
<?php
    	echo "<br>";
	    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
	    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_teletravail'>";
	    echo "<input type='submit' value='Soumettre'  name='creation_indem'/>";
	    echo "</form>";
    
?>            
        </div>
        
<!--         
        ###################################################################
        #                                                                 #
        # ICI COMMENCE LE CONTENU DE L'ONGLET UTILISATEURS SPECIAUX       #
        #                                                                 #
        ###################################################################
-->        

        <div class="tabs__tab <?php if ($current_tab == 'tab_utilisateurs') echo " active "; ?>" id="tab_utilisateurs" data-tab-info>
			<form name='selectagentrh'  method='post' >
    			<br>Liste des agents ayant accés au menu "Gestion RH" : <br>
    			<table class='tableausimple'>
    				<tr><td class='titresimple'>Identité de l'agent</td><td class='titresimple'>Rôle CET</td><td class='titresimple'>Rôle CONGES</td><td class='titresimple'>Rôle TELETRAVAIL</td><td class='titresimple'>Supprimer</td></tr>
<?php 
        $agentrhcetliste = $fonctions->listeprofilrh(agent::PROFIL_RHCET);
        $agentrhcongeliste = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE);
        $agentrhteletravailliste = $fonctions->listeprofilrh(agent::PROFIL_RHTELETRAVAIL);
        $agentrhliste = $agentrhcetliste + $agentrhcongeliste + $agentrhteletravailliste;  // On fusionne les tableaux sans les réindexer

        foreach($agentrhliste as $key => $agentrh)
        {
            echo "<tr><td class='cellulesimple'>" . $agentrh->identitecomplete() . "</td>";
            echo "<td class='cellulesimple'>";
            if (isset($agentrhcetliste[$agentrh->agentid()]))
            {
                echo "<center>&#x2714</center>";
            }
            echo "</td>";
            echo "<td class='cellulesimple'>";
            if (isset($agentrhcongeliste[$agentrh->agentid()]))
            {
                echo "<center>&#x2714</center>";
            }
            echo "</td>";
            echo "<td class='cellulesimple'>";
            if (isset($agentrhteletravailliste[$agentrh->agentid()]))
            {
                echo "<center>&#x2714</center>";
            }
            echo "</td>";
            $disabledtext = '';
            if ($agentrh->agentid()<0) $disabledtext  = " disabled ";
            echo "<td class='cellulesimple'><center><input type='checkbox' name=rhcancel[" . $key . "] value='yes' $disabledtext /></center></td>";
            echo "</tr>";
        }
?>        
                    <tr>
                        <td class='cellulesimple'>
                        	<input id="newuserrh" name="newuserrh" placeholder="Nom et/ou prenom" style='width: 300px;' autofocus/>
                        	<input type='hidden' id="newiduserrh" name="newiduserrh" class="newuserrh" />
                            <script>
                          	    $( "#newuserrh" ).autocompleteUser(
                            	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "supannEmpId",
                          	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee|staff" } });
                        	</script>
            
                        </td>
                        <td class='cellulesimple'><center>
                        	<input type='checkbox' id='newprofilCET' name='newprofilCET' value='<?php  echo agent::PROFIL_RHCET; ?>'></input>
                        </center></td>
                        <td class='cellulesimple'><center>
                        	<input type='checkbox' id='newprofilCONGES' name='newprofilCONGES' value='<?php  echo agent::PROFIL_RHCONGE; ?>'></input>
                        </center></td>
                        <td class='cellulesimple'><center>
                        	<input type='checkbox' id='newprofilTELETRAVAIL' name='newprofilTELETRAVAIL' value='<?php  echo agent::PROFIL_RHTELETRAVAIL; ?>'></input>
                        </center></td>
                        
<!--                         	
        					<select size='1' id='newprofilRH' name='newprofilRH'  value=''> 
	        					<option value=''>----- Veuillez sélectionner le profil Gestionnaire DRH -----</option>
	        					<option value='<?php echo agent::PROFIL_RHCET; ?>'><?php echo agent::PROFIL_RHCET; ?></option>
	        					<option value='<?php echo agent::PROFIL_RHCONGE; ?>'><?php echo agent::PROFIL_RHCONGE; ?></option>
	        					<option value='<?php echo agent::PROFIL_RHCET; ?> + <?php echo agent::PROFIL_RHCONGE; ?>'><?php echo agent::PROFIL_RHCET; ?> + <?php echo agent::PROFIL_RHCONGE; ?></option>
	        				</select>
 -->	        				
                        </td>
                        <td class='cellulesimple'>
                        </td>
                    </tr>
        		</table>
    			<br><br>
        	    Informations sur les utilisateurs spéciaux :
        	    <br>
<?php
            $cronuser = new agent($dbcon);
            if (!$agent->existe(SPECIAL_USER_IDCRONUSER))
            {
                $cronuser = new agent($dbcon);
                $cronuser->nom('CRON');
                $cronuser->prenom('G2T');
                $cronuser->mail('noreply@etablissement.fr');
                $cronuser->store(SPECIAL_USER_IDCRONUSER);
            }
            $cronuser->load(SPECIAL_USER_IDCRONUSER);
            
            $listerhuser = new agent($dbcon);
            if (!$agent->existe(SPECIAL_USER_IDLISTERHUSER))
            {
                $listerhuser = new agent($dbcon);
                $listerhuser->nom('DIFFUSION');
                $listerhuser->prenom('RH');
                $listerhuser->mail('noreply@etablissement.fr');
                $listerhuser->store(SPECIAL_USER_IDLISTERHUSER);
            }
            $listerhuser->load(SPECIAL_USER_IDLISTERHUSER);
            
            $dbconstante = "FORCE_AGENT_MAIL";
            $spantxt = '';
    		if ($fonctions->testexistdbconstante($dbconstante))
    		{
    		    $usermail = trim($fonctions->liredbconstante($dbconstante));
    		    if (strlen($usermail)>0)
    		    {
    		        $spantxt = '<span data-tip="L\'adresse mail utilisée sera : ' . $usermail  .'">';
    		    }
    		}

                
            ?>
    			<table class='tableausimple'>
        			<tr><td class='titresimple'>Utilité</td><td class='titresimple'>Nom</td><td class='titresimple'>Prénom</td><td class='titresimple'>Adresse mail de l'expéditeur</td></tr>
                    <tr>
                    	<td class='cellulesimple'><span data-tip="Utilisateur permettant l'envoi automatique de mails (synchronisation, rappels aux agents, ...) ">Utilisateur G2T</td>
                    	<td class='cellulesimple'><input type='text' name='nomcronuser' value='<?php echo $cronuser->nom() ?>' size=30 ></td>
                    	<td class='cellulesimple'><input type='text' name='prenomcronuser' value='<?php echo $cronuser->prenom() ?>' size=30 ></td>
                    	<td class='cellulesimple'><?php echo $spantxt; ?><input type='text' name='mailcronuser' value='<?php echo $cronuser->mailforspecialagent() ?>' size=60 ></td>
                	</tr>
                    <tr>
                    	<td class='cellulesimple'><span data-tip="Liste de diffusion RH pour informer un ensemble de personnes (CET, alertes sur des dossiers agents, ...)">Liste de diffusion RH</td>
                    	<td class='cellulesimple'><input type='text' name='nomlisterhuser' value='<?php echo $listerhuser->nom() ?>' size=30 ></td>
                    	<td class='cellulesimple'><input type='text' name='prenomlisterhuser' value='<?php echo $listerhuser->prenom() ?>' size=30 ></td>
                    	<td class='cellulesimple'><?php echo $spantxt; ?><input type='text' name='maillisterhuser' value='<?php echo $listerhuser->mailforspecialagent() ?>' size=60 ></td>
                	</tr>
    			</table>
		        <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
        		<input type='hidden' id='current_tab' name='current_tab' value='tab_utilisateurs'>
    			<br>
        		<input type='submit' name='valid_specialuser' value='Soumettre' >
    		</form>
        </div>

<!--         
        #####################################################################
        #                                                                   #
        # ICI COMMENCE LE CONTENU DE L'ONGLET ADMINISTRATION                #
        #                                                                   #
        #####################################################################
-->        
        <div class="tabs__tab <?php if ($current_tab == 'tab_admin') echo " active "; ?>" id="tab_admin" data-tab-info>
<?php 
        
        $dbconstante = 'IDMODELALIMCET';
        $modelealim = '';
        if ($fonctions->testexistdbconstante($dbconstante))  $modelealim = $fonctions->liredbconstante($dbconstante);
        $dbconstante = 'IDMODELOPTIONCET';
        $modeleoption = '';
        if ($fonctions->testexistdbconstante($dbconstante))  $modeleoption = $fonctions->liredbconstante($dbconstante);    
 
        $dbconstante = 'DEBUTPERIODE';
        $debutperiode = '';
        if ($fonctions->testexistdbconstante($dbconstante))  $debutperiode = $fonctions->liredbconstante($dbconstante);          
        $dbconstante = 'FINPERIODE';
        $finperiode = '';
        if ($fonctions->testexistdbconstante($dbconstante))  $finperiode = $fonctions->liredbconstante($dbconstante);
        
        $dbconstante = 'LIMITE_CONGE_PERIODE';
        $limitecongesperiode = 'o';
        if ($fonctions->testexistdbconstante($dbconstante))  $limitecongesperiode = $fonctions->liredbconstante($dbconstante);

?>
      	<br>
        <form name='form_administration' id='form_administration' method='post' >
        <table>
        <tr><td>Numéro du modèle eSignature pour l'alimentation du CET : <input type='text' name='modelealim' value='<?php echo $modelealim; ?>'></td></tr>
        <tr><td>Numéro du modèle eSignature pour le droit d'option sur CET : <input type='text' name='modeleoption' value='<?php echo $modeleoption; ?>'></td></tr>  
		<tr><td>Début de la période de dépot des congés annuels : 
<?php 
        $jourdebutperiode = substr($debutperiode,2);
        $moisdebutperiode = substr($debutperiode,0,2);
        $jourfinperiode = substr($finperiode,2);
        $moisfinperiode = substr($finperiode,0,2);
        echo "<select name='jourdebutperiode' id='jourdebutperiode'>";
        for ($index=1; $index<=31; $index++)
        {
            $selecttext = '';
            if ($index == ($jourdebutperiode+0)) $selecttext=' selected ';
            echo "<option value='" . str_pad($index,  2, "0",  STR_PAD_LEFT) ."' $selecttext>" . str_pad($index,  2, "0",  STR_PAD_LEFT) . "</option>";
        }
        echo "</select>";
        echo "<select name='moisdebutperiode' id='moisdebutperiode'>";
        for ($index=1; $index<=12; $index++)
        {
            $selecttext = '';
            if ($index == ($moisdebutperiode+0)) $selecttext=' selected ';
            echo "<option value='" . str_pad($index,  2, "0",  STR_PAD_LEFT) ."' $selecttext>" . $fonctions->nommoisparindex($index) . "</option>";
        }
        echo "</select>";
        echo "<br>Actuellement la période de référence est du " . $jourdebutperiode . '/' . $moisdebutperiode . " au " . $jourfinperiode . '/' . $moisfinperiode . "<br>";
?>
        </td></tr>
        <tr><td><span data-tip="Non = L'agent peut poser des jours de congés un mois au delà de la fin de la période de référence">Limiter la pose de congés à la période de référence : 
        <select name='limite_conge_periode' id='limite_conge_periode'>
	        <option value='o' <?php if (strcasecmp($limitecongesperiode, "n") != 0) echo " selected ";?> ><?php echo $fonctions->ouinonlibelle('o'); ?></option>
	        <option value='n' <?php if (strcasecmp($limitecongesperiode, "n") == 0) echo " selected ";?> ><?php echo $fonctions->ouinonlibelle('n'); ?></option>
        </select>
        </td></tr>
		</table>
		<br>
	    <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
	    <input type='hidden' id='current_tab' name='current_tab' value='tab_admin'>
	    <input type='submit' value='Soumettre'  name='modif_adminform'/>
	    </form>
	    <br>
	    <br>
<?php 

/*
        $configfilename = $fonctions->g2tbasepath() . '/config/config.php';
        $myfile = fopen($configfilename, "r") or die("Unable to open file!");
        $filecontent = fread($myfile,filesize($configfilename));
        fclose($myfile);
        
        $tokens = token_get_all($filecontent);
        
        foreach ($tokens as $token) {
            if (is_array($token)) {
                echo "Line {$token[2]}: ", token_name($token[0]), " ('{$token[1]}')<br>";;
            }
        }
*/
?>	    
		</div>
<!--         
        ########################################################
        #                                                      #
        # ICI SE TERMINE LE CONTENU DES ONGLETS                #
        #                                                      #
        ########################################################
-->        
    </div>
    <script type="text/javascript">
        const tabs = document.querySelectorAll('[data-tab-value]')
        const tabInfos = document.querySelectorAll('[data-tab-info]')
  
        tabs.forEach(tab => {        
            tab.addEventListener('click', () => {
            	tabs.forEach(onglet => {
	            	onglet.classList.remove('tab_active')
	            })
                tab.classList.add('tab_active');

                const target = document
                    .querySelector(tab.dataset.tabValue);
  
                tabInfos.forEach(tabInfo => {
                    tabInfo.classList.remove('active')
                })
                target.classList.add('active');
                //const current_tab_input = document.getElementById('current_tab');
                //current_tab_input.value = target.id;
            })
        })
    </script>
    
    <script type="text/javascript">
		setTimeout(HideMessage, 5000);
		
        function HideMessage()
        {
        	// alert ("plouf");
            const messages = document.querySelectorAll('.tabmessage');
			messages.forEach(
    			function(message) 
        		{
        			const errormsg = message.querySelectorAll('.celerror');
        			if (errormsg.length == 0)
        		    {
            			// alert ("plouf plouf");
              			message.style.display = "none";
          			}
        		}
			);
        }		
    </script>    

<!-- 
    <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
    <input type='hidden' id='current_tab' name='current_tab' value='tab_conges'>
    <input type='submit' value='Soumettre' name='selection'/>
-->
	</form>

</body>
</html>


    