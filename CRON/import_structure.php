<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des structures " . date("d/m/Y H:i:s") . "\n" ;

	// On parcourt le fichier des structures
	// 	Si la structure n'existe pas
	//			on insert la structure
	// 	Sinon
	//			on update les infos

	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/har_structures_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
	}
	else
	{
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$ligne = fgets($fp); // lecture du contenu de la ligne
			if (trim($ligne)!="")
			{
		//		echo "Ligne = $ligne \n";
				$ligne_element = explode(";",$ligne);
				$code_struct = $ligne_element[0];
				$nom_long_struct = $ligne_element[1];
				$nom_court_struct = $ligne_element[2];
				$parent_struct = $ligne_element[3];
				$resp_struct = $ligne_element[4];
				echo "code_struct = $code_struct   nom_long_struct=$nom_long_struct   nom_court_struct=$nom_court_struct   parent_struct=$parent_struct   resp_struct=$resp_struct \n";
	
				$sql = "SELECT * FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
				$query = mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT STRUCTURE => $erreur_requete \n";
				if (mysql_num_rows($query) == 0) // Structure manquante
				{
					$sql = sprintf("INSERT INTO STRUCTURE(STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID) VALUES('%s','%s','%s','%s','%s')",
							mysql_real_escape_string($code_struct),mysql_real_escape_string($nom_long_struct),mysql_real_escape_string($nom_court_struct),mysql_real_escape_string($parent_struct),mysql_real_escape_string($resp_struct));
				}
				else
				{
					$sql = sprintf("UPDATE STRUCTURE SET NOMLONG='%s',NOMCOURT='%s',STRUCTUREIDPARENT='%s',RESPONSABLEID='%s' WHERE STRUCTUREID='%s'",
							mysql_real_escape_string($nom_long_struct),mysql_real_escape_string($nom_court_struct),mysql_real_escape_string($parent_struct),mysql_real_escape_string($resp_struct),mysql_real_escape_string($code_struct));
				}
				mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
				{
					echo "INSERT/UPDATE STRUCTURE => $erreur_requete \n";
					echo "sql = $sql \n";
				}
			}
		}
		fclose($fp);
	}
	echo "Fin de l'import des structures " . date("d/m/Y H:i:s") . "\n";

?>