<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    // Initialisation de l'utilisateur
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
    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        $agent = new agent($dbcon);
        $agent->load($agentid);
    } else {
        $agentid = null;
        $agent = null;
    }
*/

    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        if (! is_numeric($agentid)) {
            $agentid = null;
            $agent = null;
        } else {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } else {
        $agentid = null;
        $agent = null;
    }
    
    
    $nbr_jours_conges = null;
    $commentaire_supp = null;
    $ancienacquis_supp = null;
    $remove_array = null;
    $nbrecommentairesupp = null;
    $mode = MODE_RESPONSABLE;
    if (isset($_POST["nbr_jours_conges"]))
    {
        $nbr_jours_conges = $_POST["nbr_jours_conges"];
    }
    if (isset($_POST["commentaire_supp"]))
    {
        $commentaire_supp = $_POST["commentaire_supp"];
    }
    if (isset($_POST["ancienacquis_supp"]))
    {
        $ancienacquis_supp = $_POST["ancienacquis_supp"];
    }
    if (isset($_POST["mode"]))
    {
        $mode = $_POST["mode"];
    }
    if (isset($_POST["remove_compl_id"]))
    {
        $remove_array = $_POST["remove_compl_id"];
    }
     if (isset($_POST["nbrecommentairesupp"]))
    {
        $nbrecommentairesupp = $_POST["nbrecommentairesupp"];
    }

    $msg_erreur = "";
    $annee = substr($fonctions->anneeref(), 2, 2);
    $lib_sup = recuperation::RECUP_ID; // "sup$annee";

    $cronuser = new agent($dbcon);
    $cronuser->load(SPECIAL_USER_IDCRONUSER);

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    //echo "POST = " . print_r($_POST,true) . "<br>";
    echo "<br>";

    
    $longueurmaxcommentaire = $fonctions->logueurmaxcolonne('COMMENTAIRECONGE','COMMENTAIRE');
    
    if (is_array($remove_array))
    {
        if (!is_null($agent))
        {
            $erreur == '';
            if ($lib_sup != recuperation::RECUP_ID)
            {
                $solde = new solde($dbcon);
                $solde->load($agentid,$lib_sup);
                if ($solde->droitaquis() != $ancienacquis_supp)
                {
                    $erreur = "Le solde de droit acquis n'est pas cohérent (Ancien droit acquis : $ancienacquis_supp Droit acquis en base : " . $solde->droitaquis().")";
                    echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
            }
            if ($erreur == '')
            {
                foreach ($remove_array as $id => $value)
                {
                //echo "L'id est $id <br>";
                    $erreur = $agent->supprcongesupplementaire($id, $user);
                    if ($erreur != '')
                    {
                        //$erreur = "Impossible de supprimer l'ajout de congés supplémentaires (id = $id) : " . $erreur;
                        $erreur = "Impossible de supprimer l'ajout de jours de récupération (id = $id) : " . $erreur;
                        echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                    }
                    else
                    {
                        //$erreur = "La demande d'ajout de congés supplémentaires a bien été supprimée (id = $id).";
                        $erreur = "La demande de récupération a bien été supprimée (id = $id).";
                        echo $fonctions->showmessage(fonctions::MSGINFO, $erreur);
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                        if ($lib_sup != recuperation::RECUP_ID)
                        {
                            $ancien_solde = $solde->droitaquis() - $solde->droitpris();
                            $solde = new solde($dbcon);
                            $solde->load($agentid,$lib_sup);
                        }
                        else
                        {
                            $recup = new recuperation($dbcon);
                            $recup->load($agentid,date('d/m/Y'));
                            $solde = $recup->getsolde();
                        }
                        $nouveau_solde = $solde->droitaquis() - $solde->droitpris();
                        // Envoi du mail à l'agent
                        //$corpmail = $user->identitecomplete() . " vient d'annuler un ajout de jour(s) complémentaire(s).\n";
                        //$corpmail = $corpmail . "Votre solde de jours complémentaires est maintenant de : " . $nouveau_solde . " jour(s).\n";
                        //$cronuser->sendmail($agent, "Annulation de jours complémentaires", $corpmail);
                        $corpmail = $user->identitecomplete() . " vient d'annuler un ajout de jour(s) de récupération.\n";
                        $corpmail = $corpmail . "Votre solde de jours de récupération est maintenant de : " . $nouveau_solde . " jour(s).\n";
                        $cronuser->sendmail($agent, "Annulation de jours de récupération", $corpmail);

                        // Envoi du mail à tous les agents RHCONGE
                        $agentrhlist = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // Le profil 2 est le profil de gestion des congés
                        foreach ($agentrhlist as $agentrh) 
                        {
                            //$corpmail = $user->identitecomplete() . " vient d'annuler un ajout de jour(s) complémentaire(s) à " . $agent->identitecomplete() . ".\n";
                            //$corpmail = $corpmail . "Le solde de jours complémentaires est maintenant de : " . $nouveau_solde . " jour(s).\n";
                            //$cronuser->sendmail($agentrh, "Annulation de jours complémentaires pour " . $agent->identitecomplete(), $corpmail);
                            $corpmail = $user->identitecomplete() . " vient d'annuler un ajout de jour(s) de récupération à " . $agent->identitecomplete() . ".\n";
                            $corpmail = $corpmail . "Le solde de jours de récupération est maintenant de : " . $nouveau_solde . " jour(s).\n";
                            $cronuser->sendmail($agentrh, "Annulation de jours de récupération pour " . $agent->identitecomplete(), $corpmail);
                        }

                    }
                }
            }
        }
        else
        {
            //echo "L'agent n'est pas défini ...<br>";
        }
    }
    else
    {
        //echo "Ce n'est pas un tableau<br>";
    }
    
    if ($agentid == "" and strcasecmp($mode, MODE_RH) == 0) // Si on est en mode gestrh et qu'aucun agent n'est selectionné
    {
        echo "<form name='selectagentcongessupp'  method='post' >";
        
        $agentsliste = $fonctions->listeagentsg2t(true,false);
        echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
        echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
        foreach ($agentsliste as $key => $identite)
        {
            echo "<option value='$key'>$identite</option>";
        }
        echo "</select>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
    }
    elseif ($agentid == "") // On est pas en mode rh ==> Donc on est en mode MODE_RESPONSABLE
    {
        echo "<form name='selectagentcongessupp'  method='post' >";
        
        $agentlistefull = $user->listeagentenresponsabilite(date("d/m/Y"), date("d/m/Y"));
        ksort($agentlistefull);
        echo "<SELECT class='listeagentg2t' size='1' id='agentid' name='agentid' style='width: 350px;'>";
        echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
        foreach ($agentlistefull as $keyagent => $membre) 
        {
            if (!$membre->estutilisateurspecial())
            {
                echo "<OPTION value='" . $membre->agentid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
            }
        }
        echo "</SELECT>";
        echo "<br>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>"; 
    } 
    else 
    {
        if (! is_null($nbr_jours_conges)) 
        {
            // On a cliqué sur le bouton validé ==> On va vérifier la saisie
            $nbr_jours_conges = str_replace(",", ".", $nbr_jours_conges);
            if (! is_numeric($nbr_jours_conges))
            {
                $nbr_jours_conges = 0;
            }
            // echo "nbr_jours_conges = $nbr_jours_conges <br>";
            if ($nbr_jours_conges == "" or $nbr_jours_conges <= 0) {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi le nombre de jours à ajouter ou il est inférieur ou égal à 0 ou ce n'est pas une valeur nunérique.<br>";
            }
            if ($commentaire_supp == "") {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi de commentaire. Celui-ci est obligatoire <br>";
            }
            if ($msg_erreur == "") 
            {
                if ($lib_sup != recuperation::RECUP_ID)
                {
                    $solde = new solde($dbcon);
                    // echo "lib_sup = $lib_sup <br>";
                    $erreur = $solde->load($agentid, $lib_sup);
                    // echo "Erreur = $erreur <br>";
                    if ($erreur != "") {
                        unset($solde);
                        $solde = new solde($dbcon);
                        $msg_erreur = $msg_erreur . $solde->creersolde($lib_sup, $agentid);
                        // echo "msg_erreur = $msg_erreur <br>";
                        $msg_erreur = $msg_erreur . $solde->load($agentid, $lib_sup);
                        // echo "msg_erreur = $msg_erreur <br>";
                    }
                }
                $commentaire_supp_complet = $commentaire_supp ; //. " (par " . $user->prenom() . " " . $user->nom() . ")";
                $listcongessupp = $agent->listecommentaireconge($lib_sup);
                if ($lib_sup == recuperation::RECUP_ID or ($ancienacquis_supp == $solde->droitaquis() and count($listcongessupp)==$nbrecommentairesupp))
                {
                    if ($lib_sup != recuperation::RECUP_ID) 
                    {
                        $nouv_solde = ($solde->droitaquis() + $nbr_jours_conges);
                        $solde->droitaquis($nouv_solde);
                    }
                    $dbconstante = "FONCTIONCONGSUP";
                    $congessuppfonction = 'n';
                    if ($fonctions->testexistdbconstante($dbconstante)) { $congessuppfonction = $fonctions->liredbconstante($dbconstante); }
                    // Si la fonction de demande de validation par la DRH n'est pas activée => On fait comme d'habitude
                    if (!$fonctions->convertvaluetobool($congessuppfonction))
                    {
                        if ($lib_sup != recuperation::RECUP_ID) 
                        {
                            $msg_erreur = $msg_erreur . $solde->store();
                        }
                        $msg_erreur = $msg_erreur . $agent->ajoutecommentaireconge($lib_sup, $nbr_jours_conges, $commentaire_supp_complet,$userid,$commentaireid);
                    }
                    else
                    {
                        // Si la fonction est activée => On sauvegarde le commentaire et on récupère le numéro du commentaire
                        $msg_erreur = $msg_erreur . $agent->ajoutecommentaireconge($lib_sup, $nbr_jours_conges, $commentaire_supp_complet,$userid,$commentaireid);
                        // On construit un complément pour indiquer que cette demande doit être vérifiée par la DRH
                        $complement = new complement($dbcon);
                        $complement->agentid($agentid);
                        $complement->complementid(complement::AVISRH_CONGES_SUP_LABEL . $commentaireid);
                        $complement->valeur('TODO');
                        $complement->store();
                    }
                    if ($msg_erreur=="" and $lib_sup == recuperation::RECUP_ID)
                    {
                        $recup = new recuperation($dbcon);
                        $recup->load($agentid,date('d/m/Y'));
                        $solde = $recup->getsolde();
                    }
                }
                else
                {
//                    $msg_erreur = "Le solde de droit acquis n'est pas cohérent (Ancien droit acquis : $ancienacquis_supp Droit acquis en base : " . $solde->droitaquis().")";
                    $msg_erreur = "Une incohérence a été détectée. Aucune opération n'est réalisée.";
                }
                // echo "msg_erreur = $msg_erreur <br>";
            }
            if ($msg_erreur != "") {
                $errlog = "Les jours de récupération n'ont pas été enregistrés... ==> MOTIF : " . $msg_erreur;
                echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } 
            elseif (! is_null($solde)) 
            {
                $dbconstante = "FONCTIONCONGSUP";
                $congessuppfonction = 'n';
                if ($fonctions->testexistdbconstante($dbconstante)) { $congessuppfonction = $fonctions->liredbconstante($dbconstante); }
                // Si la fonction de demande de validation par la DRH n'est pas activée => On affiche le nouveau solde
                if (!$fonctions->convertvaluetobool($congessuppfonction))
                {
                    //$errlog = "L'ajout de jours complémentaires a été enregistré.<br>Le nouveau solde de " . $agent->identitecomplete() . " est maintenant de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).";
                    $errlog = "L'ajout de jours de récupération a été enregistré.<br>Le nouveau solde de " . $agent->identitecomplete() . " est maintenant de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).";
                    echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));

                    // Envoi du mail à l'agent
                    //$corpmail = $user->identitecomplete() . " vient de vous ajouter $nbr_jours_conges jour(s) complémentaire(s).\n";
                    //$corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                    //$corpmail = $corpmail . "Votre solde de jours complémentaires est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                    //$cronuser->sendmail($agent, "Ajout de jours complémentaires", $corpmail);
                    $commentaireconge = $fonctions->lirecommentaire($commentaireid);
                    //$dbconstante = 'VALIDRECUP';
                    //$validrecup = '2';
                    //if ($fonctions->testexistdbconstante($dbconstante)) { $validrecup = $fonctions->liredbconstante($dbconstante); }
                    //$findatevalidite = date('d/m/Y',strtotime('+' . $validrecup . ' month',strtotime($fonctions->formatdatedb($commentaireconge->dateajout))));

                    $findatevalidite = $fonctions->finvaliditerecuperation($commentaireconge->dateajout);

                    $corpmail = $user->identitecomplete() . " vient de vous ajouter $nbr_jours_conges jour(s) de récupération.\n";
                    $corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                    $corpmail = $corpmail . "<b>IMPORTANT</b> : La durée de validité de cette récupération est de " . $validrecup . " mois (fin de validité : $findatevalidite).\n\n";
                    $corpmail = $corpmail . "Votre solde de jours de récupération est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                    $cronuser->sendmail($agent, "Ajout de jours de récupération", $corpmail);

                    // Envoi du mail à tous les agents RHCONGE
                    $agentrhlist = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // Le profil 2 est le profil de gestion des congés
                    foreach ($agentrhlist as $agentrh) {
                        //$corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) complémentaire(s) à " . $agent->identitecomplete() . ".\n";
                        //$corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                        //$corpmail = $corpmail . "Le solde de jours complémentaires est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                        //$cronuser->sendmail($agentrh, "Ajout de jours complémentaires pour " . $agent->identitecomplete(), $corpmail);
                        $corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) de récupération à " . $agent->identitecomplete() . ".\n";
                        $corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                        $corpmail = $corpmail . "<b>IMPORTANT</b> : La durée de validité de cette récupération est de " . $validrecup . " mois (fin de validité : $findatevalidite).\n\n";
                        $corpmail = $corpmail . "Le solde de jours de récupération est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                        $cronuser->sendmail($agentrh, "Ajout de jours de récupération pour " . $agent->identitecomplete(), $corpmail);
                    }
                }
                else
                {
                    //$errlog = "La demande d'ajout de jours complémentaires a été enregistrée.<br>Elle doit maintenant être validée par la Direction des Ressources Humaines.";
                    $errlog = "La demande d'ajout de jours de récupération a été enregistrée.<br>Elle doit maintenant être validée par la Direction des Ressources Humaines.";
                    echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));

                    // Envoi du mail à l'agent
                    //$corpmail = $user->identitecomplete() . " vient de vous ajouter $nbr_jours_conges jour(s) complémentaire(s).\n";
                    //$corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                    //$corpmail = $corpmail . "Cette demande de jours complémentaires doit maintenant être validée par la Direction des Ressources Humaines.\n";
                    //$corpmail = $corpmail . "Suite à cette validation, votre solde de jours compléméntaires sera actualisé.\nDans l'intervalle, votre solde est inchangé.\n";
                    //$cronuser->sendmail($agent, "Ajout de jours complémentaires", $corpmail);
                    $corpmail = $user->identitecomplete() . " vient de vous ajouter $nbr_jours_conges jour(s) de récupération.\n";
                    $corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                    $corpmail = $corpmail . "Cette demande de jours de récupération doit maintenant être validée par la Direction des Ressources Humaines.\n";
                    $corpmail = $corpmail . "Suite à cette validation, votre solde de jours de récupération sera actualisé.\nDans l'intervalle, votre solde est inchangé.\n";
                    $cronuser->sendmail($agent, "Ajout de jours de récupération", $corpmail);

                    // Envoi du mail à tous les agents RHCONGE
                    $agentrhlist = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // Le profil 2 est le profil de gestion des congés
                    foreach ($agentrhlist as $agentrh) {
                        //$corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) complémentaire(s) à " . $agent->identitecomplete() . ".\n";
                        //$corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                        //$corpmail = $corpmail . "Vous devez maintenant valider cette demande d'ajout à partir du menu 'Gestion RH/Gestion des congés/Validation des jours complémentaires'.\n";
                        //$cronuser->sendmail($agentrh, "Ajout de jours complémentaires en attente de validation pour " . $agent->identitecomplete(), $corpmail);
                        $corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) de récupération à " . $agent->identitecomplete() . ".\n";
                        $corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                        $corpmail = $corpmail . "Vous devez maintenant valider cette demande d'ajout à partir du menu 'Gestion RH/Gestion des congés/Validation des jours de récupération'.\n";
                        $cronuser->sendmail($agentrh, "Ajout de jours de récupération en attente de validation pour " . $agent->identitecomplete(), $corpmail);
                    }

                }
                $nbr_jours_conges = null;
                $commentaire_supp = null;
            }
        } 
        else
        {
            // On est au premier affichage de l'écran apres la selection de l'agent ==> Pas de control de saisi
        }

        // On charge le solde de congés complémentaires afin de pouvoir poster le nombre de jours déjà aquis ==> Objectif : Empécher le double post (F5 du navigateur)
        if ($lib_sup != recuperation::RECUP_ID)
        {
            $solde = new solde($dbcon);
            // echo "lib_sup = $lib_sup <br>";
            $erreur = $solde->load($agentid, $lib_sup);
            // echo "Erreur = $erreur <br>";
            if ($erreur != "") {
                unset($solde);
                $solde = new solde($dbcon);
                $msg_erreur = $solde->creersolde($lib_sup, $agentid);
                // echo "msg_erreur = $msg_erreur <br>";
                $msg_erreur = $msg_erreur . $solde->load($agentid, $lib_sup);
                // echo "msg_erreur = $msg_erreur <br>";
                if ($msg_erreur <> "")
                {
                    $msg_erreur = "Erreur lors du chargement du solde de congés complémentaires $lib_sup : " . $msg_erreur;
                    echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                }
            }
        }
        else
        {
            $recup = new recuperation($dbcon);
            $recup->load($agentid,date('d/m/Y'));
            $solde = $recup->getsolde();
        }
        $listcongessupp = $agent->listecommentaireconge($lib_sup);

        echo "<span class='ajoutcongesbloc'>";
        //echo "Ajout de jours de congés complémentaires pour l'agent : " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
        echo "Ajout de jours de récupération pour l'agent : " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
        echo "<br>";
        echo "Le solde de " . strtolower($solde->typelibelle()) . " est actuellement de " . ($solde->droitaquis()-$solde->droitpris()) . " jour(s) <br>";
        echo "<form name='frm_ajoutconge'  method='post' >";
        echo "<br>";
        //echo "Nombre de jours complémentaires à ajouter : <input required type='text' name='nbr_jours_conges' id='nbr_jours_conges' size=3 value='$nbr_jours_conges'>";
        echo "Nombre de jours de récupération à ajouter : <input required type='text' name='nbr_jours_conges' id='nbr_jours_conges' size=3 value='$nbr_jours_conges'>";
        echo "<br>";
        echo "<b class='redtext'>Motif (Obligatoire) - maximum $longueurmaxcommentaire caractères  - Reste : <label id='motifrestant'>$longueurmaxcommentaire</label> car.) : </b><br>";
//        echo "<input type='text' name='commentaire_supp' id='commentaire_supp' size=80 oninput='checktextlength(this,$longueurmaxcommentaire,\"motifrestant\");' >";
        echo "<textarea required rows='4' cols='80' class='commenttextarea' name='commentaire_supp' id='commentaire_supp' oninput='checktextlength(this,$longueurmaxcommentaire,\"motifrestant\");' >$commentaire_supp</textarea>";
        echo "<br>";
        echo "<label>Merci de préciser clairement le motif de cet ajout.<br><br>La direction des ressources humaines sera informée de cette action (nombre de jours + commentaire).<br>Elle pourra vous contacter en cas de nécessité.</label>";
        echo "<br>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
        echo "<input type='hidden' name='ancienacquis_supp' value='" . $solde->droitaquis() . "'>";
        echo "<input type='hidden' name='nbrecommentairesupp' value='" . count($listcongessupp) . "'>";
        echo "<br>";
        echo "<input type='submit' name='button_ajout' class='g2tbouton g2tvalidebouton' value='Enregistrer' >";
        echo "</form>";
        echo "<br>";
        echo "</span>";
        echo "<br><br>";
?>
        <script>
            var commentaire_supp = document.getElementById('commentaire_supp');
            if (commentaire_supp)
            {
                checktextlength(commentaire_supp,<?php echo $fonctions->logueurmaxcolonne('COMMENTAIRECONGE','COMMENTAIRE'); ?>,"motifrestant");
            }
        </script>
<?php
        $htmlcommentaire = $agent->affichecommentairecongehtml(true,$fonctions->anneeref(),true);
        if (trim($htmlcommentaire) != "")
        {
            echo "<span class='supprcongesbloc'>";
            echo "<form name='frm_supprconge'  method='post' >";
            //echo "Annulation d'un ajout de jours complémentaires :<br><br>";
            echo "Annulation d'un ajout de jours de récupération :<br><br>";
            echo $htmlcommentaire;
            echo "<label>La direction des ressources humaines sera informée de cette action.<br>Elle pourra vous contacter en cas de nécessité.</label>";
            echo "<br><br>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
            echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
            echo "<input type='hidden' name='ancienacquis_supp' value='" . $solde->droitaquis() . "'>";
            echo "<input type='hidden' name='nbrecommentairesupp' value='" . count($listcongessupp) . "'>";
            echo "<input type='submit' name='button_delete' class='cancel g2tbouton g2tsupprbouton' value='Supprimer' >";
            echo "</form>";
            echo "<br>";
            echo "</span>";
        }
        echo "<br>";
    }

?>

</body>
</html>

