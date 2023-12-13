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

    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) {
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

    $anneeref = $fonctions->anneeref()-1;
    if (isset($_POST["annee_ref"]))
    {
        $anneeref = $_POST["annee_ref"];
    }


    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    $msg_erreur = "";
    $info = "";
    if (isset($_POST["newsolde"]))   //if (isset($_POST["solde"]))
    {
/*
        $newsolde=$_POST["solde"];
        $newsolde = str_replace(",", ".", $newsolde);
        //echo "int => " . intval($newsolde * 2) . "   et l'autre => " . ($newsolde * 2) . "<br>";
        if (! is_numeric($newsolde))
        {
            $msg_erreur = "Vous n'avez pas saisi une valeur nunérique correcte.<br>";
            $newsolde = "";
        }
        elseif (intval($newsolde * 2) <> ($newsolde * 2)) // On vérifie que newsolde est soit un entier soit un multiple de 1/2 journée
        {
            $msg_erreur = "Vous ne pouvez saisir qu'un nombre entier ou un multiple de 1/2 journée.<br>";
            $newsolde = "";
        }
        elseif ($newsolde == "" or $newsolde < 0) {
            $msg_erreur = "Vous n'avez pas saisi de solde ou il est négatif.<br>";
        }
        else
        {
            $typeconges = "ann" . substr($anneeref,-2,2);
            $solde = new solde($dbcon);
            $msg_erreur = $solde->load($agent->agentid(),$typeconges);
            if ($msg_erreur=="")
            {
                $anciensolde = $solde->solde();
                if (floatval($anciensolde) <> floatval($newsolde))
                {
                    $pris=$solde->droitpris();
                    $solde->droitaquis($pris + $newsolde);
                    $solde->store();
                    
                    $agent->ajoutecommentaireconge($typeconges, $solde->solde()-$anciensolde, "Modification du solde par " . $user->identitecomplete() . " (Ancien solde = $anciensolde / Nouveau solde = " . $solde->solde() .")");
                    $info = "La modification du solde est bien prise en compte.";
                }
            }
        }
*/
        $newacquis = $_POST["droitaquis"];
        $newpris = $_POST["droitpris"];
        if (! is_numeric($newacquis))
        {
            $msg_erreur = "Vous n'avez pas saisi une valeur nunérique correcte dans le champs 'Droit acquis'.<br>";
            $newacquis = "";
        }
        elseif (intval($newacquis * 2) <> ($newacquis * 2)) // On vérifie que $newacquis est soit un entier soit un multiple de 1/2 journée
        {
            $msg_erreur = "Vous ne pouvez saisir qu'un nombre entier ou un multiple de 1/2 journée dans le champs 'Droit acquis'.<br>";
            $newacquis = "";
        }
        elseif ($newacquis == "" or $newacquis < 0) {
            $msg_erreur = "Vous n'avez pas saisi de droit acquis ou il est négatif.<br>";
        }
        if (! is_numeric($newpris))
        {
            $msg_erreur = "Vous n'avez pas saisi une valeur nunérique correcte dans le champs 'Droit pris'.<br>";
            $newpris = "";
        }
        elseif (intval($newpris * 2) <> ($newpris * 2)) // On vérifie que $newacquis est soit un entier soit un multiple de 1/2 journée
        {
            $msg_erreur = "Vous ne pouvez saisir qu'un nombre entier ou un multiple de 1/2 journée dans le champs 'Droit pris'.<br>";
            $newpris = "";
        }
        elseif ($newpris == "" or $newpris < 0) {
            $msg_erreur = "Vous n'avez pas saisi de droit pris ou il est négatif.<br>";
        }
        if ($newpris != "" and $newacquis != "") // Si tout est ok
        {
            if ($newpris > $newacquis)
            {
                $msg_erreur = "La valeur dans le champs 'Droit pris' est plus grande que celle du champs 'Droit acquis'.<br>";
            }
            else
            {
                $typeconges = "ann" . substr($anneeref,-2,2);
                $solde = new solde($dbcon);
                $msg_erreur = $solde->load($agent->agentid(),$typeconges);
                if ($msg_erreur=="")
                {
                    $ancienacquis = $solde->droitaquis();
                    $ancienpris = $solde->droitpris();
                    $solde->droitaquis($newacquis);
                    $solde->droitpris($newpris);
                    $solde->store();
                    if (floatval($ancienacquis) <> floatval($newacquis))
                    {
                        $agent->ajoutecommentaireconge($typeconges, '0', "Modification du droit acquis (Ancien droit acquis = $ancienacquis / Nouveau droit acquis = " . $solde->droitaquis() .")", $userid);
                    }
                    if (floatval($ancienpris) <> floatval($newpris))
                    {
                        $agent->ajoutecommentaireconge($typeconges, '0', "Modification du droit pris (Ancien droit pris = $ancienpris / Nouveau droit pris = " . $solde->droitpris() .")", $userid);
                    }
                    $info = "La modification du droit acquis et/ou droit pris est bien prise en compte.";
                }
            }
            
        }
    }
    
    if (isset($_POST["calculdroit"]))
    {
        $droitacquis = $agent->calculsoldeannuel($anneeref, true, true, false);
        $info = "Les droits acquis $anneeref/" . ($anneeref+1) . " ont été recalculés pour " . $agent->identitecomplete()  . " => $droitacquis jour(s).";
    }
    
    echo $fonctions->showmessage(fonctions::MSGERROR, $msg_erreur);
    echo $fonctions->showmessage(fonctions::MSGINFO, $info);
    
    
    //print_r($_POST); echo "<br>";

    $msg_erreur = "";
    echo "Personne à rechercher : <br>";
    echo "<form name='selectagent'  method='post' >";
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
   
    echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
    if (isset($_POST["agent"]))
        echo $_POST["agent"];
    echo "' size=40 />";
    echo "<input type='hidden' id='agentid' name='agentid' value='";
    if (isset($_POST["agentid"]))
        echo $_POST["agentid"];
    echo "' class='agent' /> ";
?>
<script>
        $("#agent").autocompleteUser(
           '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
        	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
</script>
<?php


    
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br><br>";
    echo "Période d'affichage : ";
    echo "<select name='annee_ref' id='annee_ref'>";
    for ($annee=$fonctions->anneeref()-1;$annee>=$fonctions->anneeref()-3;$annee--)
    {
        echo "<option value='$annee'";
        if ($annee==$anneeref)
            echo " selected ";
        echo ">Année " . $annee . "/" . ($annee+1) . "</option>";
    }
    echo "</select>";
    echo "<br><br>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";

    if (!is_null($agent)) {
        echo "<br><br>";
        echo "Appuyez sur le bouton ci-dessous pour recalculer les droits acquis $anneeref/" . ($anneeref+1) . " de " . $agent->identitecomplete() . "<br>";
        echo "<form name='submit_calculdroit'  method='post' >";
        // echo "<input type='hidden' id='agent' name='agent' value='" . $_POST["agent"] . "' class='agent' /> ";
        echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "' class='agent' /> ";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='annee_ref' value='" . $_POST["annee_ref"] . "'>";
        echo "<input type='submit' id='calculdroit' name='calculdroit' class='g2tbouton g2tvalidebouton' value='Recalculer' >";
        echo "</form>";
        echo "<br><br>";
/*
        $solde_agent = ($agent->getQuotiteMoyPeriode($anneeref . $fonctions->debutperiode(), ($anneeref+1) . $fonctions->finperiode()) * $fonctions->liredbconstante("NBJOURS" . $anneeref))/100;
        $partie_decimale = $solde_agent - floor($solde_agent);
        if ((float) $partie_decimale < (float) 0.25)
            $solde_agent = floor($solde_agent);
        elseif ((float) ($partie_decimale >= (float) 0.25) && ((float) $partie_decimale < (float) 0.75))
            $solde_agent = floor($solde_agent) + (float) 0.5;
        else
            $solde_agent = floor($solde_agent) + (float) 1;
                
        echo "Le solde initial de l'agent pour $anneeref / " . ($anneeref+1) . " devrait être de : " . $solde_agent . "<br>";
*/        
        echo "<span class='ajoutcongesbloc'>";
        //echo "Informations sur les congés de " . $agent->identitecomplete() . "<br>";
        $solde = new solde($dbcon);
        $msg_erreur = $solde->load($agent->agentid(),"ann" . substr($anneeref,-2,2));
        if ($msg_erreur <> "")
        {
            echo $msg_erreur;
        }
        else
        {
            echo $agent->soldecongeshtml("$anneeref");
            echo "<br>";
            echo "<form name='submit_solde'  method='post' >";
            // echo "<input type='hidden' id='agent' name='agent' value='" . $_POST["agent"] . "' class='agent' /> ";
            echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "' class='agent' /> ";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
            echo "<input type='hidden' name='annee_ref' value='" . $_POST["annee_ref"] . "'>";
            echo "Le solde " . $solde->typelibelle() . " de l'agent " . $agent->identitecomplete() . " est de " . $solde->solde() . " jour(s)";
            echo "<br><br>";
            echo "Veuillez saisir le nouveau droit acquis pour " . $solde->typelibelle() . " : ";
            echo "<input type='text' name='droitaquis' value='" . $solde->droitaquis() .  "'>";
            echo "<br><br>";
            echo "Veuillez saisir le nouveau droit pris pour " . $solde->typelibelle() . " : ";
            echo "<input type='text' name='droitpris' value='" . $solde->droitpris() .  "'>";
            echo "<br><br>";
            echo "<input type='submit' id='newsolde' name='newsolde' class='g2tbouton g2tvalidebouton' value='Enregistrer' >";
            echo "</form>";
        }
        echo "</span>";
    }
?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>