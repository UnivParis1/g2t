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

    $mode = null;
    if (isset($_POST["mode"]))
        $mode = $_POST["mode"];

    $cetaverifier = null;
    if (isset($_POST["CETaverifier"]))
        $cetaverifier = $_POST["CETaverifier"];

    $selectall = 'no';
    if (isset($_POST["selectall"]))
        $selectall = $_POST["selectall"];

    $msg_erreur = "";

    require ("includes/menu.php");
    echo '<html><body class="bodyhtml">';
    echo "<br>";

    //print_r($_POST); echo "<br>";

    if (is_null($cetaverifier) == false) {
        foreach ($cetaverifier as $demandeid => $valeur) {
            $demande = new demande($dbcon);
            $demande->load($demandeid);

            $agentid = $demande->agent()->agentid();
            //echo "Demande chargée = " . $demande->id() . " pour l'agent $agentid et le statut = " . $demande->statut() . "<br>";
            $solde = new solde($dbcon);

            $complement = new complement($dbcon);
            $complement->load($agentid, 'DEM_CET_' . $demande->id());

            // La demande est validée et il n'y a pas de complément (<=> Le statut du complément est vide) => on doit déduire les jours du solde du CET et mettre le complément (Statut = demande::DEMANDE_VALIDE comme la demande)
            if ($demande->statut() == demande::DEMANDE_VALIDE and $complement->valeur() != $demande->statut()) {
                $solde->load($agentid, 'cet');
                // echo "Solde chargé = " . $solde->droitpris() . "<br>";
                $droitpris = $solde->droitpris();
                $droitpris = $droitpris + $demande->nbrejrsdemande();
                $solde->droitpris($droitpris);

                unset($complement);
                $complement = new complement($dbcon);
                $complement->agentid($agentid);
                $complement->complementid('DEM_CET_' . $demande->id());
                $complement->valeur($demande->statut());

                $msg_erreur = $msg_erreur . $solde->store();
                // echo "Apres store solde.... <br>";
                $msg_erreur = $msg_erreur . $complement->store();
                // echo "Apres store complement.... <br>";
            }        // La demande est annulée et que le statut du complément est <> du statut de la demande => On doit recréditer les jours de CET et désactiver le complément (statut = 'R' comme la demande)
            elseif ($demande->statut() == demande::DEMANDE_ANNULE and $complement->valeur() != $demande->statut() and $complement->valeur() != "") {
                $solde->load($agentid, 'cet');
                $droitpris = $solde->droitpris();
                $droitpris = $droitpris - $demande->nbrejrsdemande();
                $solde->droitpris($droitpris);

                unset($complement);
                $complement = new complement($dbcon);
                $complement->load($agentid, 'DEM_CET_' . $demande->id());
                $complement->valeur($demande->statut());

                $msg_erreur = $msg_erreur . $solde->store();
                // echo "Apres store solde.... <br>";
                $msg_erreur = $msg_erreur . $complement->store();
                // echo "Apres store complement.... <br>";
            }
            if ($msg_erreur != "") 
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Agent = $agentid ==> Demande = " . $demande->id() . " : " . $msg_erreur);
                //echo "<p style='color: red'>Agent = $agentid ==> Demande = " . $demande->id() . " : " . $msg_erreur . "</p><br>";
                error_log(basename(__FILE__) . " " . $msg_erreur);
                $msg_erreur = "";
            } else {
                echo "La prise en compte de la demande " . $demande->id() . " est maintenant correctement enregistrée.... <br><br>";
            }
        }
    }

    if (strcasecmp($mode, "gestrh") == 0) {
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentcet'  method='post' >";
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
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "<input type='hidden' name='selectall' value='no'>";
        echo "</form>";
        echo "<form name='selectall'  method='post' >";
        echo "<br>";
        echo "<br>";
        echo "<input type='submit' value='Tout afficher' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' name='selectall' value='yes'>";
        echo "</form>";
        echo "<br>";
        echo "<br>";
    }

    if (!is_null($agent) or $selectall=='yes') {
        // echo "On a choisit un agent <br>";
        $msg_bloquant = "";

        $jour = date('j');
        $mois = date('m');
        $annee = date('Y');
        $mois = ($mois - 6);
        if ($mois <= 0) {
            $mois = 12 + $mois; // ATTENTION : Mois est négatif donc on doit additionner
            $annee = ($annee - 1);
        }
        $mois = str_pad($mois, 2, "0", STR_PAD_LEFT);
        $datedebut = "01/" . $mois . "/" . $annee;

        $datedebut = "01/01/2017";

        if ($selectall == 'no')
        {
            $cetliste = $agent->CETaverifier($datedebut);
        }
        else
        {
            $cetliste = $fonctions->CETaverifier($datedebut);
        }

        if (count($cetliste) == 0) // Si pas d'élément....
        {
            if ($selectall == 'no')
                echo "Aucune demande de CET n'est à contrôler pour l'agent " . $agent->identitecomplete() . " <br>";
            else
                echo "Aucune demande de CET n'est à contrôler sur l'établissement. <br>";

        } else {
            echo "<form name='frm_utilisationcet'  method='post' >";
            echo "<br>";

            echo "Sélectionnez les demandes à mettre à jour : <br>";

            if ($selectall == 'no')
            {
                $htmltext = "";
                // $htmltext = $htmltext . "<center>";
                $htmltext = $htmltext . "<table class='tableausimple'>";
                $htmltext = $htmltext . "<tr><td class='titresimple' colspan=6 align=center>Demande de congés de CET à traiter pour " . $agent->identitecomplete() . " (id : $agentid) </td></tr>";
                $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Ident. demande</td><td class='cellulesimple'>Date début</td><td class='cellulesimple'>Date fin</td><td class='cellulesimple'>Nbre jours</td><td class='cellulesimple'>Statut</td><td class='cellulesimple'>Traiter</td></tr>";
                foreach ($cetliste as $demande) {
                    $htmltext = $htmltext . "<tr align=center>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->id() . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->datedebut() . ' ' . $fonctions->nommoment($demande->moment_debut()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->datefin() . ' ' . $fonctions->nommoment($demande->moment_fin()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->nbrejrsdemande() . "jour(s)</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $fonctions->demandestatutlibelle($demande->statut()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=CETaverifier[" . $demande->id() . "] value='yes' /></td>";
                    $htmltext = $htmltext . "</tr>";
                }
                $htmltext = $htmltext . "</table>";
                // $htmltext = $htmltext . "</center>";
                $htmltext = $htmltext . "<br>";
            }
            else
            {
                $htmltext = "";
                foreach ($cetliste as $demande)
                {
                    $newagent = false;
                    if (is_null($agent))
                    {
                        $agent = $demande->agent();
                        $newagent = true;
                    }
                    elseif ($agent->agentid() <> $demande->agent()->agentid())
                    {
                        $agent = $demande->agent();
                        $newagent = true;
                    }
                    if ($newagent == true)
                    {
                        if ($htmltext <> "")
                        {
                            $htmltext = $htmltext . "</table>";
                            // Affichage du solde de l'année précédente
                            // echo $agent->soldecongeshtml($fonctions->anneeref()-1);
                            // Affichage du solde de l'année en cours
                            //echo $agent->soldecongeshtml($fonctions->anneeref());
                            // On affiche les commentaires pour avoir l'historique
                            // echo $agent->affichecommentairecongehtml();
                            $htmltext = $htmltext . "<br><br>";
                        }
                        $htmltext = $htmltext . "<table class='tableausimple'>";
                        $htmltext = $htmltext . "<tr><td class='titresimple' colspan=6 align=center>Demande de congés de CET à traiter pour " . $agent->identitecomplete() . " (id : " . $agent->agentid() . ")</td></tr>";
                        $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Ident. demande</td><td class='cellulesimple'>Date début</td><td class='cellulesimple'>Date fin</td><td class='cellulesimple'>Nbre jours</td><td class='cellulesimple'>Statut</td><td class='cellulesimple'>Traiter</td></tr>";
                    }
                    $htmltext = $htmltext . "<tr align=center>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->id() . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->datedebut() . ' ' . $fonctions->nommoment($demande->moment_debut()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->datefin() . ' ' . $fonctions->nommoment($demande->moment_fin()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $demande->nbrejrsdemande() . "jour(s)</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $fonctions->demandestatutlibelle($demande->statut()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=CETaverifier[" . $demande->id() . "] value='yes' /></td>";
                    $htmltext = $htmltext . "</tr>";
                }
                $htmltext = $htmltext . "</table>";
                // $htmltext = $htmltext . "</center>";
                $htmltext = $htmltext . "<br>";
                // Affichage du solde de l'année précédente
                // echo $agent->soldecongeshtml($fonctions->anneeref()-1);
                // Affichage du solde de l'année en cours
                //echo $agent->soldecongeshtml($fonctions->anneeref());
                // On affiche les commentaires pour avoir l'historique
                // echo $agent->affichecommentairecongehtml();
            }
            echo "$htmltext";
            /*
             * echo "<select name='CETaverifier'>";
             * foreach ($cetliste as $demande)
             * {
             * echo "<option value='" . $demande->id() . "'>Début = " . $demande->datedebut() . ' ' . $fonctions->nommoment($demande->moment_debut()) . ' ';
             * echo "Fin = " . $demande->datefin() . ' ' . $fonctions->nommoment($demande->moment_fin()) . ' ';
             * echo "Nbre jours = " . $demande->nbrejrsdemande() . "jour(s) ";
             * echo "Statut = " . $fonctions->demandestatutlibelle($demande->statut());
             * echo "</option>";
             * }
             * echo "</select>";
             */

            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
            echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
            echo "<input type='hidden' name='mode' value='" . $mode . "'>";
            echo "<input type='hidden' name='selectall' value='". $selectall ."'>";
            echo "<br>";
            echo "<input type='submit' value='Soumettre' >";
            echo "</form>";
        }

    }

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

