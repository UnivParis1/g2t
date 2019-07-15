<?php
    require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    
    $fonctions = new fonctions($dbcon);
    
    $date = date("Ymd");
    
    echo "Début de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n";
    
    // On charge la table des absences HARPEGE avec le fichier
    $filename = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_absence_$date.dat";
    if (! file_exists($filename)) {
        echo "Le fichier $filename n'existe pas !!! \n";
        exit();
    } else {
        $separateur = ';';
        // Vérification que le fichier d'entree est bien conforme
        // => On le lit en entier et on vérifie qu'un séparateur est bien présent sur chaque ligne non vide...
        $fp = fopen("$filename", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                if (count($ligne_element) == 0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
                {
                    // On doit arréter tout !!!!
                    echo "#######################################################";
                    echo "ALERTE : Le format du fichier $filename n'est pas correct !!! => Erreur dans la ligne $ligne \n";
                    echo "#######################################################";
                    fclose($fp);
                    exit();
                }
            }
        }
        fclose($fp);
        
        // On vide la table des absences HARPEGE pour la recharger complètement
        $sql = "DELETE FROM HARPABSENCE";
        mysql_query($sql);
        $erreur_requete = mysql_error();
        if ($erreur_requete != "")
            echo "DELETE HARPABSENCE => $erreur_requete \n";
        
        $fp = fopen("$filename", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                $harpegeid = trim($ligne_element[0]);
                $datedebut = trim($ligne_element[1]);
                $datefin = trim($ligne_element[2]);
                $harptype = trim($ligne_element[3]);
                echo "harpegeid = $harpegeid   datedebut=$datedebut   datefin=$datefin   harptype=$harptype   \n";
                $sql = sprintf("INSERT INTO HARPABSENCE (HARPEGEID,DATEDEBUT,DATEFIN,HARPTYPE) VALUES('%s','%s	','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($harptype));
                
                mysql_query($sql);
                $erreur_requete = mysql_error();
                if ($erreur_requete != "") {
                    echo "INSERT HARPABSENCE => $erreur_requete \n";
                    echo "sql = $sql \n";
                }
            }
        }
        fclose($fp);
    }
    
    echo "Fin de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n";

?>