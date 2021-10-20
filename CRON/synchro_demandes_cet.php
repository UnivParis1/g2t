<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);    

    $anneeref = $fonctions->anneeref();
    $typeconge = $fonctions->typeCongeAlimCET();
	$fonctions->synchroGlobaleCETeSignature($typeconge, $anneeref);

?>