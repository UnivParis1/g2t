<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des agents " . date("d/m/Y H:i:s") . "\n" ;

	// On charge la table des agents avec le fichier
	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/har_agents_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
		exit;
	}
	else
	{
		// On vide la table des agents pour la recharger complètement
		$sql = "DELETE FROM AGENT";
		mysql_query($sql);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "DELETE AGENT => $erreur_requete \n";
	
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$ligne = fgets($fp); // lecture du contenu de la ligne
			if (trim($ligne)!="")
			{
				$ligne_element = explode("#",$ligne);
				$harpegeid = trim($ligne_element[0]);
				$civilite = trim($ligne_element[1]);
				$nom = str_replace("\'", "'", trim($ligne_element[2]));
				$prenom = str_replace("\'", "'", trim($ligne_element[3]));
				$adressemail = trim($ligne_element[4]);
				$typepop = trim($ligne_element[5]);
				echo "harpegeid = $harpegeid   civilite=$civilite   nom=$nom   prenom=$prenom   adressemail=$adressemail  typepop=$typepop  \n";
				$sql = sprintf("INSERT INTO AGENT(HARPEGEID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES('%s','%s','%s','%s','%s','%s')",
				$fonctions->my_real_escape_utf8($harpegeid),$fonctions->my_real_escape_utf8($civilite),$fonctions->my_real_escape_utf8($nom),$fonctions->my_real_escape_utf8($prenom),$fonctions->my_real_escape_utf8($adressemail),$fonctions->my_real_escape_utf8($typepop));

				mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
				{
					echo "INSERT AGENT => $erreur_requete \n";
					echo "sql = $sql \n";
				}
			}
		}
		fclose($fp);
	}

	// Ajout manuel de l'agent CRON-G2T avec un harpegeid = -1
	$sql = "INSERT INTO AGENT(HARPEGEID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES('-1','','CRON','G2T','noreply-g2t@univ-paris1.fr','')";
	mysql_query ($sql,$dbcon);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "INSERT INTO AGENT noreply-G2T => $erreur_requete \n";

	echo "Fin de l'import des agents " . date("d/m/Y H:i:s") . "\n";
?>