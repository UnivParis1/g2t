<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);
    
    $date = date("Ymd");
    
    echo "Début de l'import des absences de l'application RH " . date("d/m/Y H:i:s") . "\n";
    
    // On charge la table des absences avec le fichier
    $filename = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_absence_$date.dat";
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
        
        // On vide la table des absences pour la recharger complètement
        $sql = "DELETE FROM ABSENCERH";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE ABSENCERH => $erreur_requete \n";
        
        $fp = fopen("$filename", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                $agentid = trim($ligne_element[0]);
                $datedebut = trim($ligne_element[1]);
                $datefin = trim($ligne_element[2]);
                $typeabsence = trim($ligne_element[3]);
                $datedebutformate = $fonctions->formatdatedb(str_replace('/','-',$datedebut));
                echo "agentid = $agentid   datedebut=$datedebut   datefin=$datefin   typeabsence=$typeabsence  datedebutformate = $datedebutformate \n";
                
                $agent = new agent($dbcon);
                if (!$agent->existe($agentid))
                {
                    // L'agent n'est pas dans la base => On n'intègre pas ses absences
                    echo "L'agent $agentid n'existe pas dans la base. On ne charge pas ses absences \n";
                    continue;
                }
                
                // Si c'est un congés bonifié et que la date de début est supérieure au 19/11/2021 ==> On ignore les congés bonifiés car "nouvelle version" (ticket GLPI 135729)
                if (stripos($fonctions->my_real_escape_utf8($typeabsence)," Bonifié ")!==false and ($datedebutformate >= "20211119"))
                {
                    echo "La demande de $typeabsence pour l'agent $agentid est un conge bonifie 'nouvelle version'. On ne charge pas cette absence \n";
                    continue;
                }
                
                $sql = sprintf("INSERT INTO ABSENCERH (AGENTID,DATEDEBUT,DATEFIN,TYPEABSENCE) VALUES('%s','%s	','%s','%s')", $fonctions->my_real_escape_utf8($agentid), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($typeabsence));
                
                mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "") {
                    echo "INSERT ABSENCERH => $erreur_requete \n";
                    echo "sql = $sql \n";
                }
            }
        }
        fclose($fp);
    }
    
    echo "Fin de l'import des absences de l'application RH " . date("d/m/Y H:i:s") . "\n";

?>