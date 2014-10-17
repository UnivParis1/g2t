<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n" ;

	// On vide la table des absences HARPEGE pour la recharger complÃ¨tement
	$sql = "DELETE FROM HARPABSENCE";
	mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "DELETE HARPABSENCE => $erreur_requete \n";

	// On charge la table des absences HARPEGE avec le fichier
	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/har_absence_$date.dat";
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
				$ligne_element = explode(";",$ligne);
				$harpegeid = $ligne_element[0];
				$datedebut = $ligne_element[1];
				$datefin = $ligne_element[2];
				$harptype = $ligne_element[3];
				echo "harpegeid = $harpegeid   datedebut=$datedebut   datefin=$datefin   harptype=$harptype   \n";
				$sql = sprintf("INSERT INTO HARPABSENCE (HARPEGEID,DATEDEBUT,DATEFIN,HARPTYPE) VALUES('%s','%s	','%s','%s')",
				$fonctions->my_real_escape_utf8($harpegeid),$fonctions->my_real_escape_utf8($datedebut),$fonctions->my_real_escape_utf8($datefin),$fonctions->my_real_escape_utf8($harptype));

				mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
				{
					echo "INSERT HARPABSENCE => $erreur_requete \n";
					echo "sql = $sql \n";
				}
			}
		}
		fclose($fp);
	}

	echo "Fin de l'import des absences HARPEGE " . date("d/m/Y H:i:s") . "\n";

?>