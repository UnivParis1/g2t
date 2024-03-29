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

    $mode = $_POST["mode"];

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    // echo "_POST = "; print_r($_POST); echo "<br>";
    $statutliste = null;
    if (isset($_POST['statut'])) {
        $statutliste = $_POST['statut'];
    }

    if (is_array($statutliste)) {
        foreach ($statutliste as $declarationid => $statut) {
            if (strcasecmp($statut, declarationTP::DECLARATIONTP_ATTENTE) != 0 and $statut != "") {
                $declaration = new declarationTP($dbcon);
                // echo "Avant le load... <br>";
                $declaration->load($declarationid);
                // echo "Apres le load... <br>";
                if ($declaration->statut() == $statut)
                {
                    // Pas de changement de statut de la demande => On ne sauvegarde rien !!!
                    $errlog = "Le statut de la demande de temps partiel est inchangé, donc pas de sauvegarde.";
                    echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                }
                else
                {
                    $declaration->statut($statut);
                    // echo "Avant le store <br>";
                    $msgerreur = "";
                    $msgerreur = $declaration->store();
                    // echo "Apres le store <br>";
                    if ($msgerreur != "") {
                        $errlog = "Pas de sauvegarde car " . $msgerreur;
                        echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    } else {
                        $pdffilename = $declaration->pdf($user->agentid());
                        // $agentid = $declaration->agent();
                        // $agent = new agent($dbcon);
                        // $agent->load($agentid);
                        $user->sendmail($declaration->agent(), "Validation d'un temps-partiel", "La demande de temps-partiel du " . $declaration->datedebut() . " au " . $declaration->datefin() . " est " . mb_strtolower($fonctions->declarationTPstatutlibelle($declaration->statut()),'UTF-8') . ".", $pdffilename);
                        error_log("Sauvegarde du temps-partiel " . $declaration->declarationTPid() . " avec le statut " . $declaration->statut());
                    }
                }
            }
        }
    }

    $structlist = null;
    if (strcasecmp($mode, "resp") == 0) {
        $structlist = $user->structrespliste();
    }

    if (strcasecmp($mode, "gestion") == 0) {
        $structlist = $user->structgestliste();
    }
    if (is_array($structlist))
    {
        uasort($structlist,"triparprofondeurabsolue");
    }

    if (is_array($structlist)) {
        echo "Remarque : Les personnes affectées à temps plein ne sont pas affichées dans cet écran.<br><br>";
        foreach ($structlist as $keystruct => $structure) {
            $agentlist = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
            if (strcasecmp($mode, "resp") == 0) // Si on est en mode responsable, on charge aussi les responsables des sous structures
            {  
                $sousstructliste = $structure->structurefille();
                foreach((array) $sousstructliste as $sousstruct)
                {
                    $respsousstruct = $sousstruct->responsable();
                    if ($respsousstruct->agentid() != "" and $respsousstruct->agentid() > "0")
                    {
                        $agentlist[$respsousstruct->nom() . " " . $respsousstruct->prenom() . " " . $respsousstruct->agentid()] = $respsousstruct;
                    }
                }
            }
            if (! is_array($agentlist)) {
                continue;
            }
            echo "<form name='frm_validation_autodecla'  method='post' >";
            echo "<table class='tableausimple'>";
            echo "<tr><td class='titresimple' colspan=6 >La structure est : " . $structure->nomlong() . "</td></tr>";
            echo "<tr align=center><td class='cellulesimple'>Nom de l'agent</td><td class='cellulesimple'>Date de la demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td>Etat de la demande</td><td class='cellulesimple'>Jours de temps partiel</td></tr>";
            foreach ($agentlist as $key => $membre) {
                $affectationliste = $membre->affectationliste($fonctions->anneeref() . $fonctions->debutperiode(), ($fonctions->anneeref() + 1) . $fonctions->finperiode());
                if (is_array($affectationliste)) {
                    foreach ($affectationliste as $key => $affectation) {
                        // echo "quotitevaleur=" . $affectation->quotitevaleur() . "   Quotite=" . $affectation->quotite() . "   <br> " ;
                        // echo "Calcul = " . round($affectation->quotite(),2) . "<br>";
                        if ($affectation->quotitevaleur() < 1) {  // Les affectations à 100% ne sont pas affichées
                            // BugFix : Ticket GLPI 76387
                            // On met +99 et non +1, afin de permettre aux demandes futures de s'afficher (année de référence + 99 ans)
                            $declaTPliste = $affectation->declarationTPliste($fonctions->anneeref() . $fonctions->debutperiode(), ($fonctions->anneeref() + 99) . $fonctions->finperiode());
                            if (is_array($declaTPliste)) {
                                foreach ($declaTPliste as $declaration) {
                                    if (strcasecmp($declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0)
                                        echo $declaration->html(TRUE, $structure->id());
                                }
                            }
                        }
                    }
                }
            }
            echo "</table>";
            echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
            echo "<input type='hidden' name='mode' value='" . $mode . "' />";
            echo "</form>";
            echo "<br>";
        }
    }

    echo "<br>";

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

