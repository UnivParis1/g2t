<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n" ;

	// On vide la table des absences HARPEGE pour la recharger complètement
	$sql = "DELETE FROM HARPABSENCE";
	mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "DELETE HARPABSENCE => $erreur_requete \n";

	// On charge la table des absences HARPEGE avec le fichier
	$filename = "../INPUT_FILES_V3/har_absence_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
	}
	else
	{
		$load_affect=mysql_query("LOAD DATA LOCAL INFILE '$filename' INTO TABLE HARPABSENCE CHARACTER SET LATIN1 FIELDS TERMINATED BY ';'");
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "LOAD HARPABSENCE FROM FILE => $erreur_requete \n";
	}
	echo "Fin de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n";

?>