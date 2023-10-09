<?php

    // require_once ('CAS.php');
    //require_once('../vendor/autoload.php');

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
    }
    $user = new agent($dbcon);
    if (is_null($userid) or $userid == "")
    {
        $userid = $fonctions->useridfromCAS($uid);
        if ($userid === false)
        {
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<link rel="stylesheet" type="text/css" href="style/style.css?' . filemtime('style/style.css') .'" media="screen"></link>';
            echo '</head>';

            $errlog = "<body class='bodyhtml'>Vous n'êtes pas autorisé à vous connecter à cette application.";
            $errlog = $errlog . "<br>";
            $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";

            echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

            $techlog = "Informations techniques :";
            $techlog = $techlog . "<br><ul>";
            $techlog = $techlog . "<li>Identité de l'utilisateur : " . $uid . " (identifiant = " . $userid . ")</li>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($techlog)));

            $errlog = "<h3>Plusieurs raisons peuvent être à l'origine de cette limitation d'accès :";
            $errlog = $errlog . "<br><ul>";
            $errlog = $errlog . "<li>Vous êtes affecté à une structure qui n'est pas encore paramétrée pour utiliser G2T.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent BIATSS qui n'a pas/plus d'affectation fonctionnelle dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent contractuel dont le contrat n'est pas saisi ou renouvelé dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent hébergé et votre situation administrative n'est plus valide dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous n'êtes pas/plus personnel de Paris 1 Panthéon-Sorbonne.</li>";
            $errlog = $errlog . "</ul></h3><br>";
            $errlog = $errlog . "<hr>";
            echo $errlog;
            echo $techlog;
            echo "</body>";
            exit();
        }
        // Si on est là, on est sûr que l'agent existe
        $user->load($userid);
    }
    else
    {
        // Si le userid est défini => On essaie de charger l'agent
        if (! $user->load($userid))
        {
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<link rel="stylesheet" type="text/css" href="style/style.css?' . filemtime('style/style.css') .'" media="screen"></link>';
            echo '</head>';

            $errlog = "<body class='bodyhtml'>Vous n'êtes pas autorisé à vous connecter à cette application.";
            $errlog = $errlog . "<br>";
            $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";

            echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

            $techlog = "Informations techniques :";
            $techlog = $techlog . "<br><ul>";
            $techlog = $techlog . "<li>Identité de l'utilisateur : " . $uid . " (identifiant = " . $userid . ")</li>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($techlog)));

            $errlog = "<h3>Plusieurs raisons peuvent être à l'origine de cette limitation d'accès :";
            $errlog = $errlog . "<br><ul>";
            $errlog = $errlog . "<li>Vous êtes affecté à une structure qui n'est pas encore paramétrée pour utiliser G2T.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent BIATSS qui n'a pas/plus d'affectation fonctionnelle dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent contractuel dont le contrat n'est pas saisi ou renouvelé dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent hébergé et votre situation administrative n'est plus valide dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous n'êtes pas/plus personnel de Paris 1 Panthéon-Sorbonne.</li>";
            $errlog = $errlog . "</ul></h3><br>";
            $errlog = $errlog . "<hr>";
            echo $errlog;
            echo $techlog;
            echo "</body>";
            exit();

        }
    }

    require ("includes/menu.php");

    $casversion = phpCAS::getVersion();
    $errlog = "Index.php => Version de CAS.php utilisée  : " . $casversion;
    //echo "<br><br>" . $errlog . "<br><br>";
    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));

/*
    echo "POST => " . print_r($_POST,true) . "<br>";
*/

    // echo "Date du jour = " . date("d/m/Y") . "<br>";
    $affectationliste = $user->affectationliste(date("d/m/Y"), date("d/m/Y"));

    echo "<br>Bonjour " . $user->identitecomplete() . " : <br>";
    if (! is_null($affectationliste)) {
        $affectation = reset($affectationliste);
        // $affectation = $affectationliste[0];
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());
        echo $structure->nomlong();
    } 
    else
    {
        echo "Pas d'affectation actuellement => Pas de structure";
    }

    // $tempstructid = $user->structure()->id();
    echo "<br><br>";


    $affectationliste = $user->affectationliste($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()));

    // L'agent a-t-il des affectation ?
    if (count((array)$affectationliste) >0)
    {
        // Pour chaque affectation
        foreach ((array) $affectationliste as $affectation)
        {
            // Si c'est un temps partiel, on verifie que le temps partiel est bien saisi et validé
            if ($affectation->quotitevaleur() < 1)
            {
                $datedebut = "29991231";  // La date de début est dans le futur
                $datefin = "19000101";    // La date de fin est dans le passé
                if ($fonctions->formatdatedb($affectation->datedebut()) < $datedebut)
                {
                    $datedebut = $fonctions->formatdatedb($affectation->datedebut());
                }
                if ($datefin < $fonctions->formatdatedb($affectation->datefin()))
                {
                    $datefin = $fonctions->formatdatedb($affectation->datefin());
                }
                if ($datedebut < $fonctions->anneeref() . $fonctions->debutperiode())
                {
                    $datedebut = $fonctions->anneeref() . $fonctions->debutperiode();
                }
                if ($datefin > ($fonctions->anneeref()+1) . $fonctions->finperiode())
                {
                    $datefin = ($fonctions->anneeref()+1) . $fonctions->finperiode();
                }

                //echo "datedebut = $datedebut    datefin = $datefin <br>";
                // On verifie que sur l'affectation en cours, il n'y a pas de période non déclaré.
                if (!$user->dossiercomplet($datedebut,$datefin))
                {
                    $msgerror = "";
                    $msgerror = $msgerror . "Il existe au moins une affection à temps partiel pour laquelle vous n'avez pas de déclaration validée.<br>";
                    $msgerror = $msgerror . "Vos déclarations de temps partiel doivent obligatoirement être validées afin de pouvoir poser des congés durant la  période correspondante.<br>";
                    $msgerror = $msgerror . "Votre planning contiendra donc des cases \"Période non déclarée\" lors de son affichage.<br>";
                    echo $fonctions->showmessage(fonctions::MSGWARNING, $msgerror);
                }
            }
        }
    }

    $periode = new periodeobligatoire($dbcon);
    $liste = $periode->load($fonctions->anneeref());
    if (count($liste) > 0)
    {
        echo "<center>";
        echo "<div class='niveau1' style='width: 700px; padding-top:10px; padding-bottom:10px;border: 3px solid #888B8A ;background: #E5EAE9;color: #FF0000;'><b>RAPPEL : </b>Les périodes de fermeture obligatoire de l'établissement sont les suivantes : <ul>";
        foreach ($liste as $element)
        {
            echo "<li style='text-align: left;' >Du " . $fonctions->formatdate($element["datedebut"]) . " au " . $fonctions->formatdate($element["datefin"]) . "</li>";
        }
        echo "</ul>";
        echo "Veuillez penser à poser vos congés en conséquence.";
        echo "</div></center>";
        echo "<br><br>";
    }
    echo $user->soldecongeshtml($fonctions->anneeref());

    echo $user->affichecommentairecongehtml();
    echo $user->demandeslistehtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()), $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode()));


?>
</body>
</html>
