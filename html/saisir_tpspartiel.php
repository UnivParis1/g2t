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
        $agent = new agent($dbcon);
        $agent->load($agentid);
    } else
        $agentid = null;

    if (isset($_POST["mode"]))
        $mode = $_POST["mode"];
    else
        $mode = null;

    $msg_erreur = "";

    require ("includes/menu.php");
    ?>
    <!--
    	<script src="javascripts/jquery-1.8.3.js"></script>
    	<script src="javascripts//jquery-ui.js"></script>
    -->
    <?php
    // echo '<html><body class="bodyhtml">';

    // Récupération de l'affectation correspondant à la déclaration TP en cours
    $affectation = null;
    if (isset($_POST["affectationid"])) {
        $affectationid = $_POST["affectationid"];
        $affectation = new affectation($dbcon);
        //echo "Avant le load de l'affectation <br>";
        $affectation->load($affectationid,true);
        //echo "Après le load de l'affectation <br>";
    } else
        $affectationid = null;

    if (isset($_POST["nbredemiTP"]))
        $nbredemiTP = $_POST["nbredemiTP"];
    else
        $nbredemiTP = null;

    if (isset($_POST["nocheckquotite"]))
        $nocheckquotite = $_POST["nocheckquotite"];
    else
        $nocheckquotite = null;

    $datefausse = false;
    // Récupération de la date de début
    if (isset($_POST["date_debut"])) {
        $date_debut = $_POST["date_debut"];
        if (is_null($date_debut) or $date_debut == "" or ! $fonctions->verifiedate($date_debut)) {
            $errlog = "La date de début n'est pas initialisée ou est incorrecte (JJ/MM/AAAA) !!!";
            $msg_erreur .= $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            $datefausse = TRUE;
        }
    } else {
        $date_debut = null;
        $datefausse = TRUE;
    }

    // Récupération de la date de fin
    if (isset($_POST["date_fin"])) {
        $date_fin = $_POST["date_fin"];
        if (is_null($date_fin) or $date_fin == "" or ! $fonctions->verifiedate($date_fin)) {
            $errlog = "La date de fin n'est pas initialisée ou est incorrecte (JJ/MM/AAAA) !!! ";
            $msg_erreur .= $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            $datefausse = TRUE;
        }
    } else {
        $date_fin = null;
        $datefausse = TRUE;
    }

    if ($msg_erreur == "" and ! $datefausse) {
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        if ($datedebutdb > $datefindb) {
            $errlog = "Il y a une incohérence entre la date de début et la date de fin !!! ";
            $msg_erreur .= $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
        }
        if (is_null($affectation)) {
            $errlog = "Affectation est NULL alors que ca ne devrait pas !!!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
        } elseif (($datedebutdb < ($fonctions->formatdatedb($affectation->datedebut()))) or ($datefindb > ($fonctions->formatdatedb($affectation->datefin())))) {
            $errlog = "Vous ne pouvez pas faire de déclaration en dehors de la période d'affectation";
            $msg_erreur .= $errlog;
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
        }
    }

    // echo "_POST => "; print_r ($_POST); echo "<br>";

    // On regarde si on a annulé une déclaration de TP
    if (isset($_POST["declaannule"])) {
        $tabdeclaannule = $_POST["declaannule"];
        foreach ($tabdeclaannule as $key => $valeur) {
            $declaration = new declarationTP($dbcon);
            $declaration->load($key);
            $declaration->statut(declarationTP::DECLARATIONTP_REFUSE);
            $declaration->store();
        }
    }

    // On verifie qu'il y a autant de case à cocher marquées que de jour de TP a saisir
    if ($nbredemiTP != "" and ! $datefausse) {
        $nbsemaineimpaire = 0;
        $nbsemainepaire = 0;
        if (isset($_POST["elmtcheckbox"]))
            $checkboxarray = $_POST["elmtcheckbox"];
        // echo "checkboxarray = " ; print_r($checkboxarray); echo "<br>";
        $tabTP = array_fill(0, 20, "0");
        for ($index = 0; $index < 10; $index ++) {
            if (array_key_exists($index, $checkboxarray)) {
                $nbsemainepaire ++;
                $tabTP[$index] = "1";
            }
        }
        for ($index = 10; $index < 20; $index ++) {
            if (array_key_exists($index, $checkboxarray)) {
                $nbsemaineimpaire ++;
                $tabTP[$index] = "1";
            }
        }
        // echo "nbsemainepaire = $nbsemainepaire nbsemaineimpaire = $nbsemaineimpaire nbredemiTP =$nbredemiTP <br> ";
        // Si la case à cocher nocheckquotite n'est pas cochée on vérifie la répartition de la quotité
        // <=> Si elle est cochée on ne fait pas de test de répartition
        if ($nocheckquotite != 'yes') {
            if ($nbsemainepaire != $nbredemiTP or $nbsemaineimpaire != $nbredemiTP) {
                $errlog = "Vous devez saisir $nbredemiTP demie(s) journée(s) pour les semaines paires et impaires";
                $msg_erreur .= $errlog;
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            }
        } else {
            $errlog = "La fonction 'Pas de contrôle de la quotité' est activée... Aucun contrôle n'est réalisé.";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
        }
    }

    // echo "Apres le check nbredemiTP <br>";
    if ($msg_erreur == "" and ! $datefausse) {
        // On est sur que les données sont ok
        $declarationliste = $affectation->declarationTPliste($date_debut, $date_fin);
        // On regarde s'il y a une declaration de TP qui inclue la date de debut !!!
        $msg = "";
        if (! is_null($declarationliste)) {
            // echo "Il y a potentiellement chevauchement entre des declarations !!!! <br>";
            foreach ($declarationliste as $key => $declaration) {
                if (strcasecmp($declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0) {
                    // Si la date de fin de l'ancienne est après la date de debut de la nouvelle
                    $msg = "";
                    // Nouvelle [--------------]
                    // Ancienne [------------------]
                    // ===> [--------------][----------]
                    if (($fonctions->formatdatedb($date_fin) >= $fonctions->formatdatedb($declaration->datedebut())) and ($fonctions->formatdatedb($date_debut) <= $fonctions->formatdatedb($declaration->datedebut()))) {
                        // echo "----- CAS 1 ------<br>";
                        // echo "formatdb fin = " . $fonctions->formatdatedb($date_fin) . "<br>";
                        $timestamp = strtotime($fonctions->formatdatedb($date_fin));
                        // echo "Avant nvlle date <br>";
                        $nvlledatedebut = date("Ymd", strtotime("+1days", $timestamp)); // On passe au jour d'avant (donc la veille)
                                                                                         // echo "nvlledatedebut = $nvlledatedebut <br>";
                        $declaration->datedebut($fonctions->formatdate($nvlledatedebut));
                        if (strcasecmp($declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0)
                            $msg = $declaration->store();
                        // echo "Apres le store de l'ID " . $declaration->declarationTPid() . "... <br>";
                        if ($msg != "") {
                            $errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
                            $msg_erreur .= $errlog . "<br/>";
                            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
                            $msg_erreur = $msg_erreur . $msg;
                        }
                    }
                    $msg = "";
                    // Nouvelle [--------------]
                    // Ancienne [------------------]
                    // ===> [----------][--------------]
                    if (($fonctions->formatdatedb($date_debut) <= $fonctions->formatdatedb($declaration->datefin())) and ($fonctions->formatdatedb($date_fin) >= $fonctions->formatdatedb($declaration->datefin()))) {
                        // echo "----- CAS 2 ------<br>";
                        // echo "formatdb debut = " . $fonctions->formatdatedb($date_debut) . "<br>";
                        $timestamp = strtotime($fonctions->formatdatedb($date_debut));
                        // echo "Avant nvlle date <br>";
                        $nvlledatefin = date("Ymd", strtotime("-1days", $timestamp)); // On passe au jour d'après (donc le lendemain)
                                                                                       // echo "nvlledatefin = $nvlledatefin <br>";
                        $declaration->datefin($fonctions->formatdate($nvlledatefin));
                        if (strcasecmp($declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0)
                            $msg = $declaration->store();
                        // echo "Apres le store de l'ID " . $declaration->declarationTPid() . "... <br>";
                        if ($msg != "") {
                            $errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
                            $msg_erreur .= $errlog . "<br/>";
                            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
                            $msg_erreur = $msg_erreur . $msg;
                        }
                    }
                    $msg = "";
                    // Nouvelle [----------]
                    // Ancienne [--------------------------]
                    // ===> [--------][----------]
                    if (($fonctions->formatdatedb($date_debut) >= $fonctions->formatdatedb($declaration->datedebut())) and ($fonctions->formatdatedb($date_fin) <= $fonctions->formatdatedb($declaration->datefin()))) {
                        // echo "----- CAS 3 ------<br>";
                        // echo "formatdb debut = " . $fonctions->formatdatedb($date_debut) . "<br>";
                        $timestamp = strtotime($fonctions->formatdatedb($date_debut));
                        // echo "Avant nvlle date <br>";
                        $nvlledatefin = date("Ymd", strtotime("-1days", $timestamp)); // On passe au jour d'après (donc le lendemain)
                                                                                       // echo "nvlledatefin = $nvlledatefin <br>";
                        $declaration->datefin($fonctions->formatdate($nvlledatefin));
                        if (strcasecmp($declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0)
                            $msg = $declaration->store();
                        // echo "Apres le store de l'ID " . $declaration->declarationTPid() . "... <br>";
                        if ($msg != "") {
                            $errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
                            $msg_erreur .= $errlog . "<br/>";
                            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
                            $msg_erreur = $msg_erreur . $msg;
                        }
                    }
                    $msg = "";
                    // Ancienne ]-------------[
                    // => Annulation
                    if ($fonctions->formatdatedb($declaration->datedebut()) > $fonctions->formatdatedb($declaration->datefin())) {
                        // echo "----- CAS 4 ------<br>";
                        // echo "La date de début de la declaration est apres la date de fin !!!! <br>";
                        $declaration->statut(declarationTP::DECLARATIONTP_REFUSE);
                        $msg = $declaration->store();
                        if ($msg != "")
                            $msg_erreur = $msg_erreur . $msg;
                    }
                }
                unset($declaration);
            }
        }
        unset($declarationliste);

        // On va enregistrer la nouvelle déclaration de TP

        // echo "Avant le new... <br>";
        $declaration = new declarationTP($dbcon);
        $declaration->datedebut($date_debut);
        $declaration->datefin($date_fin);
        $declaration->agentid($agentid);
        $numquotiteligne = $affectation->numlignequotite();
        $declaration->numlignequotite($numquotiteligne);
        // echo "Avant le initTP <br>";
        $declaration->tabtpspartiel(implode($tabTP));
        $declaration->statut(declarationTP::DECLARATIONTP_ATTENTE);

/*
        echo "declaration avant l'enregistrement : ";
        var_dump($declaration);
*/        
        // echo "Avant le Store <br>";
        $msg = $declaration->store();
        if ($msg != "")
            $msg_erreur = $msg_erreur . $msg;
        else {
            $errlog = "La déclaration de temps partiel est bien enregistrée.";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
        }
    }

    if ($agentid == "") {
        echo "<form name='autodeclarationforagent'  method='post' >";

        $structureliste = $user->structrespliste();
        // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
        $agentlistefull = array();
        foreach ($structureliste as $structure) {
            $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
            // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
            $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
            // echo "fin du select <br>";
        }
        ksort($agentlistefull);
        echo "<SELECT name='agentid'>";
        foreach ($agentlistefull as $keyagent => $membre) {
            echo "<OPTION value='" . $membre->agentid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
        }
        echo "</SELECT>";
        echo "<br>";

        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    } else {
        // echo "Avant le new agent <br>";
        $agent = new agent($dbcon);
        // echo "Avant le load...<br>";
        $agent->load($agentid);
        // echo "Avant le dossieractif<br>";
        // $dossier = $agent->dossieractif();
        // echo "apres le dossier actif <br>";
        $debut_interval = $fonctions->anneeref() . $fonctions->debutperiode();
        $fin_interval = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        $affectationliste = $agent->affectationliste($debut_interval, $fin_interval);
        
        //var_dump($affectationliste);
        
        $affectation = new affectation($dbcon);
        $tppossible = false;
        if (is_array($affectationliste)) {
            foreach ($affectationliste as $key => $affectation) {
                // echo "juste dans le for .... Quotite = " . $affectation->quotite() . "<br>";
                if ($affectation->quotite() != "100%") {
                    // echo "La quotité != 100% ==> Je peux poser un tps partiel <br>";
                    $tppossible = true;
                    break;
                }
            }
        }
        if (! $tppossible) {
            $errlog = "Vous n'avez aucune affectation à temps partiel entre le " . $fonctions->formatdate($debut_interval) . " et le " . $fonctions->formatdate($fin_interval);
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
        } else {
            // echo "<b><br>C'est moche => Présentation à revoir !!! </b><br><br>";

            echo "<br/>";
            if ($msg_erreur != "") 
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, $msg_erreur);
                //echo "<P style='color: red'>" . $msg_erreur . " </P>";
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $msg_erreur);
            }
            echo "<br/>";

            // $affectationliste = liste des affectations de l'agent pour la période
            foreach ($affectationliste as $key => $affectation) {
                if ($affectation->quotite() != "100%") {
                    echo "<form name='frm_saisir_tpspartiel_" . $affectation->affectationid() . "' method='post' >";
                    echo "<input type='hidden' name='affectationid' value='" . $affectation->affectationid() . "'>";
                    //echo "Avant le affectation->html <br>";
                    echo $affectation->html(true, false, $mode);
                    //echo "Apres le affectation->html <br>";
                    echo "<br>Réaliser une nouvelle déclaration de temps partiel<br>";
                    echo "<table>";
                    echo "<tr>";
                    echo "<td>Date de début de la période :</td>";

                    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
                    $calendrierid_deb = "date_debut_" . $affectation->affectationid();
                    $calendrierid_fin = "date_fin_" . $affectation->affectationid();

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
                    echo "<td width=1px><input class='calendrier' type=text name=date_debut id=" . $calendrierid_deb . " size=10 minperiode='" . $affectation->datedebut() . "' maxperiode='" . $affectation->datefin() . "'></td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td>Date de fin de la période :</td>";
                    echo "<td width=1px><input class='calendrier' type=text name=date_fin id=" . $calendrierid_fin . " size=10 minperiode='" . $affectation->datedebut() . "' maxperiode='" . $affectation->datefin() . "'></td>";
                    echo "</tr>";
                    echo "</table>";
                    $nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
                    // echo "nbredemiTP = " . $nbredemiTP . "<br>";
                    if (strtoupper($agent->civilite()) == 'MME')
                        echo "<br>Veuillez cocher les demi-journées où vous êtes absente au titre du temps partiel.";
                    else    
                        echo "<br>Veuillez cocher les demi-journées où vous êtes absent au titre du temps partiel.";
                    echo "<br> Validez votre saisie en cliquant sur le bouton 'Soumettre'.";
                    echo "<br>Au regard de votre quotité de travail, vous devez cocher $nbredemiTP demi-journée(s) par semaine<br><br>";

                    echo "<div id='planning'>";
                    echo "<table class='tableau'>";
                    $declaration = new declarationTP($dbcon);
                    $declaration->tabtpspartiel(str_repeat("0", 20));
                    echo $declaration->tabtpspartielhtml(true);
                    echo "</table>";
                    echo "</div>";

                    echo "<br>";
                    if (strcasecmp($mode, "resp") == 0) {
                        echo "<br>";
                        echo "<input type='checkbox' name='nocheckquotite' value='yes'> Ne pas vérifier la répartition des jours de temps partiel. <br>";
                        echo "Cette fonction permet, par exemple, de saisir 3 jours de TP une semaine et 2 jours la semaine suivante pour une personne à 50% <br>";
                        echo "<b style='color:red'>ATTENTION : </b>Cette fonction est à utiliser avec prudence. Il convient de vérifier manuellement que la répartion est correcte.<br>";
                    }
                    echo "<input type='hidden' name='nbredemiTP' value='" . $nbredemiTP . "'>";
                    echo "<input type='hidden' name='userid' value='" . $userid . "'>";
                    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
                    echo "<input type='hidden' name='mode' value='" . $mode . "'>";
                    echo "<input type='submit' value='Soumettre' />";

                    echo "</form>";
                    echo "<br>";
                }
            }
        }
    }

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

