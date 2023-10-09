<?php
    
    require_once (dirname(__FILE__,3) . "/config/config.php");

    // Connexion à la base de données
    $dbcon = mysqli_connect(DB_HOST, DB_USER, DB_PWD);
    if (! $dbcon) {
        echo "Impossible d'effectuer la connexion au serveur";
        exit();
    }
    mysqli_select_db($dbcon, DB_NAME) or die("La sélection de la base a échoué");
    mysqli_query($dbcon, "set names utf8;");

?>