<?php
    require_once ('CAS.php');
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
    $mode = 'resp';
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
    $msg_erreur = "";
    $annee = substr($fonctions->anneeref(), 2, 2);
    $lib_sup = "sup$annee";
    
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    //echo "POST = " . print_r($_POST,true) . "<br>";
    echo "<br>";

    
    $longueurmaxcommentaire = $fonctions->logueurmaxcolonne('COMMENTAIRECONGE','COMMENTAIRE');
    
    if (is_array($remove_array))
    {
        if (!is_null($agent))
        {
            $solde = new solde($dbcon);
            $solde->load($agentid,$lib_sup);
            if ($solde->droitaquis() != $ancienacquis_supp)
            {
                $erreur = "Le solde de droit acquis n'est pas cohérent (Ancien droit acquis : $ancienacquis_supp Droit acquis en base : " . $solde->droitaquis().")";
                echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);

//                    echo "<P style='color: red'>" . $erreur . "</P>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                foreach ($remove_array as $id => $value)
                {
                //echo "L'id est $id <br>";
                    $erreur = $agent->supprcongesupplementaire($id, $user);
                    if ($erreur != '')
                    {
                        $erreur = "Impossible de supprimer l'ajout de congés supplémentaires (id = $id) : " . $erreur;
                        echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
//                        echo "<P style='color: red'>" . $erreur . "</P>";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                    }
                    else
                    {
                        $erreur = "Suppression de l'ajout de congés supplémentaires (id = $id) : Ok";
                        echo $fonctions->showmessage(fonctions::MSGINFO, $erreur);
//                        echo "<P style='color: green'>" . $erreur . "</P>";
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
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
    
    if ($agentid == "" and strcasecmp($mode, "gestrh") == 0) // Si on est en mode gestrh et qu'aucun agent n'est selectionné
    {
        echo "<form name='selectagentcongessupp'  method='post' >";
/*        
        $agentsliste = $fonctions->listeagentsg2t();
        echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
        echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
        foreach ($agentsliste as $key => $identite)
        {
            echo "<option value='$key'>$identite</option>";
        }
        echo "</select>";
*/        

        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='' class='agent' /> ";
?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
<?php


        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    }
    elseif ($agentid == "") // On est pas en mode rh ==> Donc on est en mode "resp"
    {
        echo "<form name='selectagentcongessupp'  method='post' >";
        
        $structureliste = $user->structrespliste();
        // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
        $agentlistefull = array();
        foreach ($structureliste as $structure) {
            $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
            // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
            $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
            // echo "fin du select <br>";
            $structurefille = $structure->structurefille();
            foreach ((array) $structurefille as $structure) {
                $responsable = $structure->responsable();
                if ($responsable->agentid() != SPECIAL_USER_IDCRONUSER) {
                    $agentlistefull[$responsable->nom() . " " . $responsable->prenom() . " " . $responsable->agentid()] = $responsable;
                }
            }
        }
        if (isset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->agentid()])) {
            unset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->agentid()]);
        }
        ksort($agentlistefull);
        echo "<SELECT name='agentid'>";
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
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>"; 
    } else {
        if (! is_null($nbr_jours_conges)) {
            // On a cliqué sur le bouton validé ==> On va vérifier la saisie
            $nbr_jours_conges = str_replace(",", ".", $nbr_jours_conges);
            if (! is_numeric($nbr_jours_conges))
                $nbr_jours_conges = 0;
            // echo "nbr_jours_conges = $nbr_jours_conges <br>";
            if ($nbr_jours_conges == "" or $nbr_jours_conges <= 0) {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi le nombre de jours à ajouter ou il est inférieur ou égal à 0 ou ce n'est pas une valeur nunérique.<br>";
            }
            if ($commentaire_supp == "") {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi de commentaire. Celui-ci est obligatoire <br>";
            }
            if ($msg_erreur == "") {
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
                if ($ancienacquis_supp == $solde->droitaquis())
                {
                    $commentaire_supp_complet = $commentaire_supp ; //. " (par " . $user->prenom() . " " . $user->nom() . ")";
                    $nouv_solde = ($solde->droitaquis() + $nbr_jours_conges);
                    $solde->droitaquis($nouv_solde);
                    $msg_erreur = $msg_erreur . $solde->store();
                    $msg_erreur = $msg_erreur . $agent->ajoutecommentaireconge($lib_sup, $nbr_jours_conges, $commentaire_supp_complet,$userid);
                }
                else
                {
                    $msg_erreur = "Le solde de droit acquis n'est pas cohérent (Ancien droit acquis : $ancienacquis_supp Droit acquis en base : " . $solde->droitaquis().")";
                }
                // echo "msg_erreur = $msg_erreur <br>";
            }
            if ($msg_erreur != "") {
                $errlog = "Les jours supplémentaires n'ont pas été enregistrés... ==> MOTIF : " . $msg_erreur;
                echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
//                echo "<P style='color: red'>" . $errlog . "</P>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } elseif (! is_null($solde)) {
                $errlog = "Les jours supplémentaires ont été enregistrés... Nouveau solde = " . ($solde->droitaquis() - $solde->droitpris());
                echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
//                echo "<P style='color: green'>" . $errlog . "</P>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                $agentrhlist = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // Le profil 2 est le profil de gestion des congés
                foreach ($agentrhlist as $agentrh) {
                    $corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) complémentaire(s) à " . $agent->identitecomplete() . ".\n";
                    $corpmail = $corpmail . "Le motif de cet ajout est : \n" . $commentaire_supp . ".\n\n";
                    $corpmail = $corpmail . "Le solde de jours complémentaires est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                    $user->sendmail($agentrh, "Ajout de jours complémentaires pour " . $agent->identitecomplete(), $corpmail);
                }
                $nbr_jours_conges = null;
                $commentaire_supp = null;
            }
        } 
        else
        {
            // On est au premier affichage de l'écran apres la selection de l'agent ==> Pas de control de saisi
            //$errlog = "Le motif de l'ajout est obligatoire";
            //echo "<P style='color: red'>" . $errlog . "</P><br/>";
            //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }

        // On charge le solde de congés complémentaires afin de pouvoir poster le nombre de jours déjà aquis ==> Objectif : Empécher le double post (F5 du navigateur)
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
//                echo "<P style='color: red'>" . $errlog . "</P><br/>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            }
        }
        
        echo "<span style='border:solid 1px black; background:lightgreen; width:600px; display:block;'>";
        echo "Ajout de jours de congés complémentaires pour l'agent : " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
        echo "<br>";
        echo "Le solde de " . strtolower($solde->typelibelle()) . " est actuellement de " . ($solde->droitaquis()-$solde->droitpris()) . " jour(s) <br>";
        echo "<form name='frm_ajoutconge'  method='post' >";
        echo "<br>";
        echo "Nombre de jours complémentaires à ajouter : <input required type='text' name='nbr_jours_conges' id='nbr_jours_conges' size=3 value='$nbr_jours_conges'>";
        echo "<br>";
        echo "<b style='color: red'>Motif (Obligatoire) - maximum $longueurmaxcommentaire caractères  - Reste : <label id='motifrestant'>$longueurmaxcommentaire</label> car.) : </b>";
//        echo "<input type='text' name='commentaire_supp' id='commentaire_supp' size=80 oninput='checktextlength(this,$longueurmaxcommentaire,\"motifrestant\");' >";
        echo "<textarea required rows='4' cols='80' style='line-height:20px; resize: none;' name='commentaire_supp' id='commentaire_supp' oninput='checktextlength(this,$longueurmaxcommentaire,\"motifrestant\");' >$commentaire_supp</textarea>";
        echo "<br>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
        echo "<input type='hidden' name='ancienacquis_supp' value='" . $solde->droitaquis() . "'>";
        echo "<br>";
        echo "<input type='submit' value='Soumettre' name='button_ajout'>";
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
            echo "<span style='border:solid 1px black; background:lightsteelblue; width:900px; display:block;'>";
            echo "<form name='frm_supprconge'  method='post' >";
            echo "Annulation d'un ajout de jours complémentaires :<br><br>";
            echo $htmlcommentaire;
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
            echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
            echo "<input type='hidden' name='ancienacquis_supp' value='" . $solde->droitaquis() . "'>";
            echo "<input type='submit' value='Supprimer'  name='button_delete'>";
            echo "</form>";
            echo "<br>";
            echo "</span>";
        }
        echo "<br>";
    }

?>

</body>
</html>

