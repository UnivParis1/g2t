<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'import des agents " . date("d/m/Y H:i:s") . "\n";

    $filename = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_agents_$date.xml";
    if (! file_exists($filename)) {
        echo "Le fichier $filename n'existe pas !!! \n";
        exit();
    }
    else
    {
/*
        // On vide la table des agents pour la recharger complètement
        $sql = "DELETE FROM AGENT";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE AGENT => $erreur_requete \n";
*/
	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('AGENT');
	foreach ($agentnode as $node)
	{
            $agentid = trim($node->xpath('AGENTID')[0]);
            $civilite = trim($node->xpath('CIVIL')[0]);
            $nom = trim($node->xpath('NOM')[0]);
            $prenom = trim($node->xpath('PRENOM')[0]);
            $adressemail = trim($node->xpath('MAIL')[0]);
            $typepop = trim($node->xpath('CATEGORIE')[0]);

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