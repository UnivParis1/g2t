<?php
    require_once ('CAS.php');
    include './includes/casconnection.php';

    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }

    require_once ("./includes/all_g2t_classes.php");
/*
    require_once ("./class/agent.php");
    require_once ("./class/structure.php");
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
*/
    
    $user = new agent($dbcon);
    $user->load($userid);

    $mode = $_POST["mode"];

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    // echo "SESSION = "; print_r($_SESSION); echo "<br>";
    // echo "_POST = "; print_r($_POST); echo "<br>";
    $statutliste = null;
    if (isset($_POST['statut'])) {
        $statutliste = $_POST['statut'];
    }

    if (is_array($statutliste)) {
        foreach ($statutliste as $declarationid => $statut) {
            if (strcasecmp($statut, "a") != 0 and $statut != "") {
                $declaration = new declarationTP($dbcon);
                // echo "Avant le load... <br>";
                $declaration->load($declarationid);
                // echo "Apres le load... <br>";
                $declaration->statut($statut);
                // echo "Avant le store <br>";
                $msgerreur = "";
                $msgerreur = $declaration->store();
                // echo "Apres le store <br>";
                if ($msgerreur != "") {
                    $errlog = "Pas de sauvegarde car " . $msgerreur;
                    echo "<p style='color: red'>" . $errlog . "</p><br/>";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                } else {
                    $pdffilename = $declaration->pdf($user->harpegeid());
                    // $agentid = $declaration->agent();
                    // $agent = new agent($dbcon);
                    // $agent->load($agentid);
                    $user->sendmail($declaration->agent(), "Validation d'un temps-partiel", "La demande de temps-partiel du " . $declaration->datedebut() . " au " . $declaration->datefin() . " est " . mb_strtolower($fonctions->declarationTPstatutlibelle($declaration->statut()),'UTF-8') . ".", $pdffilename);
                    // echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
                    error_log("Sauvegarde du temps-partiel " . $declaration->declarationTPid() . " avec le statut " . $declaration->statut());
                }
            }
        }
    }

    /*
     *
     * //print_r($_POST); echo "<br>";
     * foreach ($_POST as $key => $value)
     * {
     * //echo "key = $key value = $value <br>";
     * //echo "Substr => " . substr($key, 0, strlen("statut_")) . "<br>";
     * $position = strpos($key, "_autodeclaid");
     * if ($position !==FALSE)
     * {
     * //echo "On est dans un autodeclaid <br>";
     * $autodeclaid = $value;
     * $header = str_replace("_autodeclaid_". $autodeclaid, "", $key);
     * //echo "header = $header autodeclaid = $autodeclaid <br>";
     * $statut = $_POST[$header . "_statut_". $autodeclaid];
     * //echo "statut = $statut <br>";
     * if ($statut != "a" and $statut != "")
     * {
     * $autodecla = new autodeclaration($dbcon);
     * //echo "Avant le load... <br>";
     * $autodecla->load($autodeclaid);
     * //echo "Apres le load... <br>";
     * $autodecla->statut($statut);
     * //echo "Avant le store <br>";
     * $msgerreur = $autodecla->store();
     * //echo "Apres le store <br>";
     * if ($msgerreur != "")
     * echo "<p style='color: red'>Pas de sauvegarde car " . $msgerreur . "</p><br>";
     * else
     * {
     * $pdffilename = $autodecla->pdf($user->id());
     * $agentid = $autodecla->agentid();
     * $agent = new agent($dbcon);
     * $agent->load($agentid);
     * $user->sendmail($agent,"Validation d'une autodéclaration","Le statut de votre autodéclaration du " . $autodecla->datedebut() . " au " . $autodecla->datefin() . " est " . $autodecla->statut() . ".",$pdffilename);
     * //echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
     * error_log("Sauvegarde l'autodeclaration " . $autodecla->id() . " avec le statut " . $autodecla->statut());
     * }
     * }
     * }
     * }
     *
     */

    $structlist = null;
    if (strcasecmp($mode, "resp") == 0) {
        $structlist = $user->structrespliste();
    }

    if (strcasecmp($mode, "gestion") == 0) {
        $structlist = $user->structgestliste();
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
                    if ($respsousstruct->harpegeid() != "" and $respsousstruct->harpegeid() > "0")
                    {
                        $agentlist[$respsousstruct->nom() . " " . $respsousstruct->prenom() . " " . $respsousstruct->harpegeid()] = $respsousstruct;
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
                                    if (strcasecmp($declaration->statut(), "r") != 0)
                                        echo $declaration->html(TRUE, $structure->id());
                                }
                            }
                        }
                    }
                }
            }
            echo "</table>";
            echo "<input type='submit' value='Soumettre' />";
            echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "' />";
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

