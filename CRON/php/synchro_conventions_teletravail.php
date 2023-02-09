<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    echo "Début de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);

    $full_g2t_ws_url = $fonctions->get_g2t_ws_url() . "/teletravailWS.php";
    $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
    
    // On appelle le WS G2T en GET pour demander à G2T de mettre à jour la demande
    $curl = curl_init();
    $params_string = "";
    
    $paramWS = "status=" . teletravail::TELETRAVAIL_ATTENTE . "," . teletravail::TELETRAVAIL_VALIDE;
    echo "Les paramètres du WS $full_g2t_ws_url sont : $paramWS \n";
    
    $opts = [
        CURLOPT_URL => $full_g2t_ws_url . "?" . $paramWS,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_PROXY => ''
    ];
    curl_setopt_array($curl, $opts);
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_PROXY, '');
    //echo "<br>CURLOPT_PROXY => " . curl_getinfo($curl,CURLOPT_PROXY) . "<br><br>";
    $json = curl_exec($curl);
    $error = curl_error ($curl);
    curl_close($curl);
    if ($error != "")
    {
        echo "Erreur Curl (synchro convention teletravail) = " . $error . "\n";
        exit();
    }
    //echo "<br>Le json (synchro_g2t_eSignature) " . print_r($json,true) . "<br>";
    $response = json_decode($json, true);
    //echo "<br>La reponse (synchro_g2t_eSignature) " . print_r($response,true) . "<br>";
    if (isset($response['description']))
    {
        echo "Fin du traitement du WS " . $response['status'] . " - " . $response['description'] . "\n";
//        error_log(basename(__FILE__) . $fonctions->stripAccents(" La réponse du WS $full_g2t_ws_url est => " . $response['status'] . " - " . $response['description'] ));
    }
    else
    {
        echo "La réponse du WS n'est pas conforme : " . var_export($response, true) . "\n";
//        error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du webservice G2T non conforme (URL WS G2T = $full_g2t_ws_url) => Erreur : " . var_export($response, true) ));
    }
    
    
    echo "Fin de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
	
?>