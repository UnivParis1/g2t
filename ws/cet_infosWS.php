<?php

    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    global $dbcon;
    global $uid;
    
    $fonctions = new fonctions($dbcon);
    $agent = new agent($dbcon);
    $basetext = "Nombre de jours dans le CET";
    $result_json = array();
        
    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    //$statutvalide = array('PREPA' => alimentationCET::STATUT_PREPARE, 'COURS' => alimentationCET::STATUT_EN_COURS, 'REFUS' => alimentationCET::STATUT_REFUSE, 'SIGNE' => alimentationCET::STATUT_VALIDE, 'ABAND' => alimentationCET::STATUT_ABANDONNE);
    
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'POST': // Méthode non supportée
            $erreur = "Le mode POST n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            break;
        case 'GET':
            $agentid = "";
            if (array_key_exists("uid", $_GET) and $agentid == "") // On verifie l'existance de l'UID de l'agent
            {
                $uid = $_GET["uid"];
                $agentid = $fonctions->useridfromCAS($uid);
                if ($agentid === false)
                {
                    $agentid = "";
                }
            }
            if (array_key_exists("email", $_GET) and $agentid == "") // On verifie l'existance du mail de l'agent
            {
                $email = $_GET["email"];
                if (!$agent->loadbyemail($email))
                {
                    $agentid = "";
                }
                else
                {
                    $agentid = $agent->agentid();
                }
            }
            if (array_key_exists("agentid", $_GET) and $agentid == "") // On verifie l'existance de l'ID de l'agent
            {
                $agentid = $_GET["agentid"];
            }
            
            if ($agentid == "" or !$agent->existe($agentid))
            {
                // Problème dans l'identification de l'agent => Erreur
                $erreur = "Impossible d'identifier l'agent dans G2T";
                $result_json = array('status' => 'Error', 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode GET => Erreur = " . $erreur));
            }
            else
            {
                $solde = new solde($dbcon);
                if ($solde->load($agentid, 'cet') <> "")
                {
                    // On indique que tout s'est bien passé mais pas de CET
                    $result_json = array('status' => 'Ok', 'description' => "Vous n'avez pas de CET");
                }
                elseif (($solde->droitaquis() - $solde->droitpris()) > 1)
                {
                    $result_json = array('status' => 'Ok', 'description' => "$basetext : " . ($solde->droitaquis() - $solde->droitpris())  . " jours");
                }
                else
                {
                    $result_json = array('status' => 'Ok', 'description' => "$basetext : " . ($solde->droitaquis() - $solde->droitpris())  . " jour");
                }
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode GET => Tout ok => " . print_r($result_json,true)));
            }
            
    }

    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // headers to tell that result is JSON
    header('Content-type: application/json');
    
    echo json_encode($result_json);
    
    
?>