<?php
    
    include_once 'mysql_compat.php';

    // Connexion à la base de données
    $db_host = 'localhost';
    $db_user = 'g2t';
    $db_pwd = 'xxx';
    $dbcon = mysqli_connect($db_host, $db_user, $db_pwd);
    if (! $dbcon) {
        echo "Impossible d'effectuer la connexion au serveur";
        exit();
    }
    mysqli_select_db($dbcon, "G2T-v3") or die("La sélection de la base a échoué");
    mysqli_query($dbcon, "set names utf8;");

?>