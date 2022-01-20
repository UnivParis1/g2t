<?php
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');

    echo "Début de la synchronisation des demandes Alim + Option CET " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);    

    $anneeref = $fonctions->anneeref();
    $typeconge = $fonctions->typeCongeAlimCET();
	$fonctions->synchroGlobaleCETeSignature($typeconge, $anneeref);

	echo "Fin de la synchronisation des demandes Alim + Option CET " . date("d/m/Y H:i:s") . "\n";
	
?>