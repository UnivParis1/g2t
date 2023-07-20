<?php

    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    define("SYNCHRO_ACTIF", "actif");
    define("SYNCHRO_INACTIF", "inactif");
    
    $fonctions = new fonctions($dbcon);
    
    echo "Debut du switch synchronisation " . date("d/m/Y H:i:s") . "\n";
    
    $constante = "SYNCHRONISATION";
    $valeur = '';
    if (isset($argv[1])) {
        if (strcasecmp($argv[1],SYNCHRO_ACTIF)==0 or strcasecmp($argv[1],SYNCHRO_INACTIF)==0)
        {
            echo "On recupere la valeur du parametre : " . $argv[1] . "\n";
            if (strcasecmp($argv[1],SYNCHRO_ACTIF)==0)
            {
                $valeur = 'o';
            }
            elseif (strcasecmp($argv[1],SYNCHRO_INACTIF)==0)
            {
                $valeur = 'n';
            }
        }
        else
        {
            echo "La valeur du parametre n'est pas reconnue (" . $argv[1] . ")=> On l'ignore \n";
        }
    }
    if ($valeur=='')
    {
        $valeur = 'n';
        if ($fonctions->testexistdbconstante($constante))
        {
            $valeur = $fonctions->liredbconstante($constante);
        }
        echo "La valeur courante de la synchronisation est $valeur \n";
        if (strcasecmp($valeur,"n")==0) // C'est n ou N
        {
            $valeur = "o";
        }
        else
        {
            $valeur = "n";        
        }
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