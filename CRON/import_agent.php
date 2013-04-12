<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des agents " . date("d/m/Y H:i:s") . "\n" ;

	// On vide la table des agents pour la recharger complètement
	$sql = "DELETE FROM AGENT";
	mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "DELETE AGENT => $erreur_requete \n";

	// On charge la table des agents avec le fichier
	$filename = "../INPUT_FILES_V3/har_agents_$date.dat";
	$load_affect=mysql_query("LOAD DATA LOCAL INFILE '$filename' INTO TABLE AGENT CHARACTER SET LATIN1 FIELDS TERMINATED BY '#'");
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "LOAD AGENT FROM FILE => $erreur_requete \n";

	echo "Fin de l'import des agents " . date("d/m/Y H:i:s") . "\n";
?>