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
        header('Location: index.php');
        exit();
    }

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

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';

    echo "<br>Planning de l'agent " . $user->civilite() . " " . $user->nom() . " " . $user->prenom() . " <br>";

    $datedebut = $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode());
    if (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) {
        $datefin = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("+1month", $timestamp)); // On passe au mois suivant
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("-1days", $timestamp)); // On passe Ã  la veille
    } else {
        $datefin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
    }
    //$fonctions->time_elapsed("Avant l'affichage du planning", 0 , true);
    echo $user->planninghtml($datedebut, $datefin,false,true,true);
    echo "<br>";
    //$fonctions->time_elapsed("Après l'affichage du planning", 0);
    
    echo "<br>";

?>
</body>
</html>