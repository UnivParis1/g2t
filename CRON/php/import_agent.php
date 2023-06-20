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
/*                
                $nom = str_replace("\'", "'", trim($ligne_element[2]));
                $prenom = str_replace("\'", "'", trim($ligne_element[3]));
*/
                $nom = trim($ligne_element[2]);
                $prenom = trim($ligne_element[3]);
                $adressemail = trim($ligne_element[4]);
                $typepop = trim($ligne_element[5]);
                echo "agentid = $agentid   civilite=$civilite   nom=$nom   prenom=$prenom   adressemail=$adressemail  typepop=$typepop  \n";
                
                $agent = new agent($dbcon);
                $agent->civilite($civilite);
                $agent->nom($nom);
                $agent->prenom($prenom);
                $agent->mail($adressemail);
                $agent->typepopulation($typepop);
                $agent->structureid(''); // On force sa structure à 'vide' car elle sera initialisée plus tard
                if (!$agent->store($agentid))
                {
                    echo "INSERT/UPDATE AGENT => Une erreur s'est produit dans la mise à jour/l'insertion des agents \n"; 
                }
            }
        }
        fclose($fp);
    }

    echo "Verification de l'existance des utilisateurs speciaux \n";
    $agent = new agent($dbcon);
    if (!$agent->existe(SPECIAL_USER_IDCRONUSER))
    {
        // Ajout manuel de l'agent CRON-G2T
        $agent->nom('CRON');
        $agent->prenom('G2T');
        $agent->mail('noreply-g2t@univ-paris1.fr');
        $agent->store(SPECIAL_USER_IDCRONUSER);
    }
    $agent = new agent($dbcon);
    if (!$agent->existe(SPECIAL_USER_IDLISTERHUSER))
    {
        // Ajout manuel de l'agent GESTION TEMPS
        $agent->nom('DIFFUSION');
        $agent->prenom('RH');
        $agent->mail('noreply@univ-paris1.fr');
        $agent->store(SPECIAL_USER_IDLISTERHUSER);
    }
    $agent = new agent($dbcon);
    if (!$agent->existe(SPECIAL_USER_IDLISTERHTELETRAVAIL))
    {
        // Ajout manuel de l'agent GESTION TEMPS
        $agent->nom('TELETRAVAIL');
        $agent->prenom('RH');
        $agent->mail('noreply@univ-paris1.fr');
        $agent->store(SPECIAL_USER_IDLISTERHTELETRAVAIL);
    }

    $agent = new agent($dbcon);
    echo "Modification du profil RH de l'utilisateur " . constant('SPECIAL_USER_IDLISTERHUSER') . " \n";
    $agent->load(SPECIAL_USER_IDLISTERHUSER);
    $tabprofilrh = array();
    $tabprofilrh[] = agent::PROFIL_RHCET;
    $tabprofilrh[] = agent::PROFIL_RHCONGE;
    //$tabprofilrh[] = agent::PROFIL_RHTELETRAVAIL;
    $agent->enregistreprofilrh($tabprofilrh);
    
    $agent = new agent($dbcon);
    echo "Modification du profil RH de l'utilisateur " . constant('SPECIAL_USER_IDLISTERHTELETRAVAIL') . " \n";
    $agent->load(SPECIAL_USER_IDLISTERHTELETRAVAIL);
    $tabprofilrh = array();
    //$tabprofilrh[] = agent::PROFIL_RHCET;
    //$tabprofilrh[] = agent::PROFIL_RHCONGE;
    $tabprofilrh[] = agent::PROFIL_RHTELETRAVAIL;
    $agent->enregistreprofilrh($tabprofilrh);

    echo "Fin de l'import des agents " . date("d/m/Y H:i:s") . "\n";
?>