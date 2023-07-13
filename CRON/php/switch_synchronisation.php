<?php

    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);
    
    echo "Début du switch synchronisation " . date("d/m/Y H:i:s") . "\n";

    $constante = "SYNCHRONISATION";
    $valeur = 'n';
    if ($fonctions->testexistdbconstante($constante))
    {
        $valeur = $fonctions->liredbconstante($constante);
    }
    echo "La valeur courante de la synchronisation est $valeur \n";
    if ($valeur=="n")
    {
        $valeur = "o";
    }
    else
    {
        $valeur = "n";        
    }
    echo "La nouvelle valeur de la synchronisation est $valeur \n";
    $erreur = $fonctions->enregistredbconstante($constante, $valeur);
    if ($erreur != "") 
    {
        $errlog = "Erreur activation/desactivation mode synchronisation : " . $erreur;
        echo $errlog . "<br/>";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }

    echo "Fin du switch synchronisation " . date("d/m/Y H:i:s") . "\n";

?>