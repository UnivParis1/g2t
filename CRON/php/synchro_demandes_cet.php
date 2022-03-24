<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    echo "Début de la synchronisation des demandes Alim + Option CET " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);    

    $anneeref = $fonctions->anneeref();
    $typeconge = $fonctions->typeCongeAlimCET();
	$fonctions->synchroGlobaleCETeSignature($typeconge, $anneeref);

	echo "Fin de la synchronisation des demandes Alim + Option CET " . date("d/m/Y H:i:s") . "\n";
	
?>