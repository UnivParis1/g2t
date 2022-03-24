<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'import des agents " . date("d/m/Y H:i:s") . "\n";

    // On charge la table des agents avec le fichier
    $filename = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_agents_$date.dat";
    if (! file_exists($filename)) {
        echo "Le fichier $filename n'existe pas !!! \n";
        exit();
    } else {
        $separateur = '#';
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

/*        
        // On vide la table des agents pour la recharger complètement
        $sql = "DELETE FROM AGENT";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE AGENT => $erreur_requete \n";
*/
        $fp = fopen("$filename", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                $agentid = trim($ligne_element[0]);
                $civilite = trim($ligne_element[1]);
                $nom = str_replace("\'", "'", trim($ligne_element[2]));
                $prenom = str_replace("\'", "'", trim($ligne_element[3]));
                $adressemail = trim($ligne_element[4]);
                $typepop = trim($ligne_element[5]);
                echo "agentid = $agentid   civilite=$civilite   nom=$nom   prenom=$prenom   adressemail=$adressemail  typepop=$typepop  \n";
                
                $agent = new agent($dbcon);
                if ($agent->existe($agentid))
                {
                    // ATTENTION : Bien positionner la structure à vide si l'agent est déjà dans la base.
                    // Sinon il va ressortir comme affecté alors qu'il n'est plus là
                    $sql = sprintf("UPDATE AGENT SET CIVILITE='%s',NOM='%s',PRENOM='%s',ADRESSEMAIL='%s',TYPEPOPULATION='%s', STRUCTUREID='' WHERE AGENTID='%s'", 
                        $fonctions->my_real_escape_utf8($civilite), 
                        $fonctions->my_real_escape_utf8($nom), 
                        $fonctions->my_real_escape_utf8($prenom), 
                        $fonctions->my_real_escape_utf8($adressemail), 
                        $fonctions->my_real_escape_utf8($typepop),
                        $fonctions->my_real_escape_utf8($agentid));
                }
                else
                {
                    $sql = sprintf("INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES('%s','%s','%s','%s','%s','%s')", 
                        $fonctions->my_real_escape_utf8($agentid), 
                        $fonctions->my_real_escape_utf8($civilite), 
                        $fonctions->my_real_escape_utf8($nom), 
                        $fonctions->my_real_escape_utf8($prenom), 
                        $fonctions->my_real_escape_utf8($adressemail), 
                        $fonctions->my_real_escape_utf8($typepop));
                }

                mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "") {
                    echo "INSERT/UPDATE AGENT => $erreur_requete \n";
                    echo "sql = $sql \n";
                }
            }
        }
        fclose($fp);
    }

    $agent = new agent($dbcon);
    if (!$agent->existe('-1'))
    {
        // Ajout manuel de l'agent CRON-G2T avec un agentid = -1
        $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES('-1','','CRON','G2T','noreply-g2t@univ-paris1.fr','')";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "INSERT INTO AGENT CRON-G2T => $erreur_requete \n";
    }
    $agent = new agent($dbcon);
    if (!$agent->existe('-2'))
    {
        // Ajout manuel de l'agent GESTION TEMPS avec un agentid = -2
        $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES('-2','','Gestion','Temps','gestion.temps@univ-paris1.fr','')";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "INSERT INTO AGENT Gestion Temps => $erreur_requete \n";
    }

    echo "Fin de l'import des agents " . date("d/m/Y H:i:s") . "\n";
?>