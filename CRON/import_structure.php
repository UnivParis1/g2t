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
		exit;
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
				$code_struct = trim($ligne_element[0]);
				$nom_long_struct = trim($ligne_element[1]);
				$nom_court_struct = trim($ligne_element[2]);
				$parent_struct = trim($ligne_element[3]);
				$resp_struct = trim($ligne_element[4]);
				$date_cloture = trim($ligne_element[5]);
				if (is_null($date_cloture) or $date_cloture=="")
					$date_cloture='2999-12-31';
				echo "code_struct = $code_struct   nom_long_struct=$nom_long_struct   nom_court_struct=$nom_court_struct   parent_struct=$parent_struct   resp_struct=$resp_struct date_cloture=$date_cloture\n";

				$sql = "SELECT * FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
				$query = mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT STRUCTURE => $erreur_requete \n";
				if (mysql_num_rows($query) == 0) // Structure manquante
				{
					$sql = sprintf("INSERT INTO STRUCTURE(STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID,DATECLOTURE) VALUES('%s','%s','%s','%s','%s','%s')",
							$fonctions->my_real_escape_utf8($code_struct),$fonctions->my_real_escape_utf8($nom_long_struct),$fonctions->my_real_escape_utf8($nom_court_struct),$fonctions->my_real_escape_utf8($parent_struct),$fonctions->my_real_escape_utf8($resp_struct),$fonctions->my_real_escape_utf8($date_cloture));
				}
				else
				{
					$sql = sprintf("UPDATE STRUCTURE SET NOMLONG='%s',NOMCOURT='%s',STRUCTUREIDPARENT='%s',RESPONSABLEID='%s', DATECLOTURE='%s' WHERE STRUCTUREID='%s'",
							$fonctions->my_real_escape_utf8($nom_long_struct),$fonctions->my_real_escape_utf8($nom_court_struct),$fonctions->my_real_escape_utf8($parent_struct),$fonctions->my_real_escape_utf8($resp_struct),$fonctions->my_real_escape_utf8($date_cloture),$fonctions->my_real_escape_utf8($code_struct));
					echo $sql."\n";
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