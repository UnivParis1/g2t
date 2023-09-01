<?php
    require_once (__DIR__ . '/../../vendor/autoload.php');
    require_once ('./class/fonctions.php');
    require_once ('./includes/dbconnection.php');
    $fonctions = new fonctions($dbcon);
    // Parametres pour connexion CAS
    $CAS_SERVER = $fonctions->liredbconstante("CASSERVER");
    $CAS_PORT = 443;
    $CAS_PATH = $fonctions->liredbconstante("CASPATH");
 
    // En partant de la constante G2T_URL on va définir l'URL du service CAS
    $array_url = parse_url(G2T_URL);
    $client_service_name = $array_url["scheme"] . '://' . $array_url["host"];
    if (isset($array_url["port"]) and $array_url["port"] != 0)
    {
        $client_service_name = $client_service_name . ":" . $array_url["port"];
    }
    
/******************************************************   
    //////////////////////////////////
    // On va construire l'URL du service pour CAS
    // On récuère le nom du serveur G2T
    $servername = $_SERVER['SERVER_NAME'];
    // Si on passe par un proxy ==> HTTP_X_FORWARDED_PROTO est défini dans le header (protocole utilisé entre le client et le proxy)
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
    {
        $serverprotocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    // Si la requète vient directement sur le serveur, on regarde si $_SERVER['HTTPS'] est défini
    else if (isset($_SERVER['HTTPS']))
    {
         $serverprotocol = "https";
    }
    // Sinon c'est de l'HTTP
    else
    {
        $serverprotocol = "http";
    }

    //Si on passe par un proxy => HTTP_X_FORWARDED_PORT est défini dans le header (port utilisé entre le client et le proxy)
    if (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
    {
        $serverport = $_SERVER['HTTP_X_FORWARDED_PORT'];
    }
    // Si la requête vient directement sur le serveur, on regarde si $_SERVER['SERVER_PORT'] est défini
    else if (isset($_SERVER['SERVER_PORT']))
    {
        // Le port pour parler au serveur est contenu dans la variable
        $serverport = $_SERVER['SERVER_PORT'];
    }
    // Si le protocole est en https => Le port par défaut est 443
    else if ($serverprotocol == "https")
    {
        $serverport = "443";
    }
    // Si c'est de l'HTTP ou si on n'a aucune information => Le port par défaut est 80
    else
    {
        $serverport = "80";
    }
    //echo "serverprotocol  = $serverprotocol   servername = $servername   serverport = $serverport <br>";
    $client_service_name = $serverprotocol . "://" . $servername . ":" . $serverport;
**********************************************************/ 
    
    $errlog = "Le CAS Service Name : " . $client_service_name;
    //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    
    // var_dump($client_service_name);
    phpCAS::client(CAS_VERSION_2_0, $CAS_SERVER, $CAS_PORT, $CAS_PATH,$client_service_name, true);

    phpCAS::setNoCasServerValidation();
    phpCAS::handleLogoutRequests(false);
    //if (! phpCAS::isAuthenticated()) 
//    if (!phpCAS::checkAuthentication())
//    {
        // Recuperation de l'uid
        phpCAS::forceAuthentication();
//    }
    $uid = phpCAS::getUser();
    // echo "uid = $uid <br>";
?>
