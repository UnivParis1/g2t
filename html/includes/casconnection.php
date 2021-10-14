<?php
    require_once ('./class/fonctions.php');
    require_once ('./includes/dbconnection.php');
    $fonctions = new fonctions($dbcon);
    // Parametres pour connexion CAS
    $CAS_SERVER = $fonctions->liredbconstante("CASSERVER");
    $CAS_PORT = 443;
    $CAS_PATH = $fonctions->liredbconstante("CASPATH");
    
    phpCAS::client(CAS_VERSION_2_0, $CAS_SERVER, $CAS_PORT, $CAS_PATH, true);
    
    // phpCAS::setDebug("D:\Apache\logs\phpcas.log");
    // phpCAS::setFixedServiceURL("http://mod11.parc.univ-paris1.fr/ReturnURL.html");
    phpCAS::setNoCasServerValidation();
    phpCAS::handleLogoutRequests(false);
    if (! phpCAS::isAuthenticated()) {
        // Recuperation de l'uid
        phpCAS::forceAuthentication();
    }
    $uid = phpCAS::getUser();
    // echo "uid= $uid <br>";
?>
