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

    // echo "<br><br><br>"; print_r($_POST); echo "<br>";

    require ("includes/menu.php");

    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    if (isset($_POST["previous"]))
        $previoustxt = $_POST["previous"];
    else
        $previoustxt = null;
    if (strcasecmp($previoustxt, "yes") == 0)
        $previous = 1;
    else
        $previous = 0;

    if (isset($_POST["indexmois"]))
        $indexmois = $_POST["indexmois"];
    else
        $indexmois = null;
    // echo "indexmois = $indexmois <br>";
    if (is_null($indexmois) or $indexmois == "")
        $indexmois = date("m");
    $indexmois = str_pad($indexmois, 2, "0", STR_PAD_LEFT);
    // echo "indexmois (apres) = $indexmois <br>";
    $annee = $fonctions->anneeref() - $previous;
    // echo "annee = $annee <br>";
    $debutperiode = $fonctions->debutperiode();
    // echo "debut periode = $debutperiode <br>";
    $moisdebutperiode = date("m", strtotime($fonctions->formatdatedb(date("Y") . $debutperiode)));
    // echo "moisdebutperiode = $moisdebutperiode <br>";
    if ($indexmois < $moisdebutperiode)
        $annee ++;
    // echo "annee (apres) = $annee <br>";

    if (isset($_POST["mode"]))
        $mode = $_POST["mode"]; // Mode = resp ou agent
    else
        $mode = "resp";

    echo "<form name='select_mois' method='post'>";
    echo "<center><select name='indexmois'>";

    // On reprend le mois de début de période
    $index = $moisdebutperiode;
    // L'année c'est l'année de référence
    $anneemois = $fonctions->anneeref() - $previous;
    // echo "index = $index <br>";
    for ($indexcpt = 1; $indexcpt <= 12; $indexcpt ++) {
        echo "<option value='$index'";
        if ($index == $indexmois)
            echo " selected ";
        echo ">" . $fonctions->nommois("01/" . str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . date("Y")) . "  " . $anneemois . "</option>";
        // On calcule le modulo
        $index = ($index % 12) + 1;
        // Si le mois est > 12 ou égal à 1 alors c'est qu'on est passé à l'année suivante
        if ($index > 12 or $index == 1)
            $anneemois = $anneemois + 1;
    }

    echo "</select>";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "' />";
    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
    echo "<input type='submit' value='Soumettre' /></center>";
    echo "</form>";

    if (strcasecmp($mode, "resp") == 0) {
        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure) {
            // Si la structure est ouverte => On la garde
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                if (strcasecmp($structure->sousstructure(), "o") == 0) {
                    $sousstructliste = $structure->structurefille();
                    foreach ((array) $sousstructliste as $key => $struct) {
                        // Si la structure est fermée.... On la supprime de la liste
                        if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                            // echo "Index = " . array_search($struct, $sousstructliste) . " Key = " . $key . "<br>";
                            // echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
                            unset($sousstructliste["$key"]);
                            // echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
                        }
                    }
                    // echo "<br>sousstructliste = "; print_r($sousstructliste); echo "<br>";
                    $structureliste = array_merge($structureliste, (array) $sousstructliste);
                    // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
                }
            } else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure) {
            echo "<br>";
            echo $structure->planninghtml($indexmois . "/" . $annee,null,false,true);
        }

        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure) {
            if (strcasecmp($structure->afficherespsousstruct(), "o") == 0) {
                echo "<br>";
                echo $structure->planningresponsablesousstructhtml($indexmois . "/" . $annee,true);
            }
        }
    } elseif (strcasecmp($mode, "gestion") == 0) {
        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure) {
            // Si la structure est ouverte => On la garde
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                if (strcasecmp($structure->sousstructure(), "o") == 0) {
                    $sousstructliste = $structure->structurefille();
                    foreach ((array) $sousstructliste as $key => $struct) {
                        // Si la structure est fermée.... On la supprime de la liste
                        if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                            // echo "Index = " . array_search($struct, $sousstructliste) . " Key = " . $key . "<br>";
                            // echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
                            unset($sousstructliste["$key"]);
                            // echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
                        }
                    }
                    $structureliste = array_merge($structureliste, (array) $sousstructliste);
                    // Remarque : Le tableau ne contiendra pas de doublon, car la clÃ© est le code de la structure !!!
                }
            } else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure) {
            echo "<br>";
            echo $structure->planninghtml($indexmois . "/" . $annee,null,false,true);
        }

        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure) {
            if (strcasecmp($structure->afficherespsousstruct(), "o") == 0) {
                echo "<br>";
                echo $structure->planningresponsablesousstructhtml($indexmois . "/" . $annee,true);
            }
        }
    } else {
        $affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));
        /*
         * if ($annee. $indexmois <= date('Ym'))
         * {
         * echo "<br><B><font SIZE='3pt' color=#FF0000>Attention : Des données ont été masquées en raison de restrictions d'accès....</font></B><br>";
         * echo "<font color=#FF0000>Les informations antérieures à la date du jour, ont été masquées.</font><br>";
         * }
         */
        foreach ($affectationliste as $affectkey => $affectation) {
            $structureid = $affectation->structureid();
            $structure = new structure($dbcon);
            $structure->load($structureid);
            if (strcasecmp($structure->affichetoutagent(), "o") == 0) {
                echo "<br>";
                // echo "Planning de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                echo $structure->planninghtml($indexmois . "/" . $annee, 'n', true,false); // 'n' car l'agent ne doit pas voir les conges des sous-structures (si autorisé) + Pas de télétravail sinon visuellement c'est trompeur
            }
        }
    }
    unset($strucuture);
?>

</body>
</html>