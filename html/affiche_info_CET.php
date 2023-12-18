<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    
    require_once ("./includes/all_g2t_classes.php");

    echo "<html>";
    echo "<head>";
?>
<link rel="stylesheet" type="text/css"	href="css-g2t/g2t.css?<?php echo filemtime('css-g2t/g2t.css')  ?>" media="screen"></link>
<?php
    echo "</head>";
    echo "<body class='siham_body'>";
    echo "<p class='siham_css'>";

    //echo "L'agent connecté est : " . $uid . "<br>";

    $user = new agent($dbcon);
    $userid = $fonctions->useridfromCAS($uid);
    if ($userid === false)
    {
        // Soit il n'est pas possible de récupérer les infos du LDAP
        // Soit l'utilisateur n'est pas dans la base G2T
        echo "Vous n'avez pas de CET";
        echo "</p>";
        exit();
    }
    // $userid = "7546";
    // echo "Le numéro SIHAM de l'agent connecté est : " . $userid . "<br>";

    $solde = new solde($dbcon);
    if ($solde->load($userid, 'cet') <> "")
    {
        echo "Vous n'avez pas de CET";
    }
    elseif (($solde->droitaquis() - $solde->droitpris()) > 1)
    {
        echo ($solde->droitaquis() - $solde->droitpris())  . " jours";
        // echo "Nombre de jours restant dans votre CET : " . ($solde->droitaquis() - $solde->droitpris())  . " jours.";
    }
    else
    {
        echo ($solde->droitaquis() - $solde->droitpris())  . " jour";
        //echo "Nombre de jours restant dans votre CET : " . ($solde->droitaquis() - $solde->droitpris())  . " jour.";
    }

    echo "</p>";
?>
</body>
</html>