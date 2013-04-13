<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');
	require_once("../html/class/declarationTP.php");

	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'import des affectations " . date("d/m/Y H:i:s") . "\n" ;

/* ----------------------------------------------------------
	// On vide la table des absences HARPEGE pour la recharger complètement
	$sql = "DELETE FROM AFFECTATION";
	mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "DELETE AFFECTATION => $erreur_requete \n";
	// On charge la table des affectations HARPEGE avec le fichier
	$filename = "../INPUT_FILES_V3/har_affectations_$date.dat";
	$load_affect=mysql_query("LOAD DATA LOCAL INFILE '$filename' INTO TABLE AFFECTATION CHARACTER SET LATIN1 FIELDS TERMINATED BY ';'");
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "LOAD AFFECTATION FROM FILE => $erreur_requete \n";
---------------------------------------------------------
*/

	// On parcours chaque ligne du fichier
	// Si la date de modif est <> de la date de modif en base alors on regarde ce qui est modifié
	// 	Si DateFin plus petite => Ca se fini plus tard, donc on reduit le TP
	//		Si NumQuotite ou DenumQuotite ==>
	$sql = sprintf("UPDATE AFFECTATION SET OBSOLETE='O'");
	$query_aff = mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "UPDATE OBSOLETE AFFECTATION => $erreur_requete \n";


	$filename = "../INPUT_FILES_V3/har_affectations_$date.dat";
	$fp = fopen("$filename","r");
	while (!feof($fp))
	{
		$ligne = fgets($fp); // lecture du contenu de la ligne
		if (trim($ligne)!="")
		{
	//		echo "Ligne = $ligne \n";
			$ligne_element = explode(";",$ligne);
			$affectationid = $ligne_element[0];
			$harpegeid = $ligne_element[1];
			$numcontrat = $ligne_element[2];
			$datedebut = $ligne_element[3];
			$datefin = $ligne_element[4];
			$datemodif = $ligne_element[5];
			$structureid = $ligne_element[6];
			$numquotite = $ligne_element[7];
			$denomquotite = $ligne_element[8];
			//echo "affectationid = $affectationid   harpegeid=$harpegeid   numcontrat=$numcontrat   datemodif=$datemodif \n";

			$sql = sprintf("SELECT DATEMODIFICATION,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE FROM AFFECTATION WHERE AFFECTATIONID='%s'",mysql_real_escape_string($affectationid));
			$query_aff = mysql_query($sql);
			$erreur_requete=mysql_error();
			if ($erreur_requete!="")
				echo "SELECT AFFECTATION => $erreur_requete \n";
			if (mysql_num_rows($query_aff) == 0) // Affectation manquante
			{
				$sql = sprintf("INSERT INTO AFFECTATION(AFFECTATIONID,HARPEGEID,NUMCONTRAT,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE)
									VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
						mysql_real_escape_string($affectationid),
						mysql_real_escape_string($harpegeid),
						mysql_real_escape_string($numcontrat),
						mysql_real_escape_string($datedebut),
						mysql_real_escape_string($datefin),
						mysql_real_escape_string($datemodif),
						mysql_real_escape_string($structureid),
						mysql_real_escape_string($numquotite),
						mysql_real_escape_string($denomquotite),
						'N');
				mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "INSERT AFFECTATION => $erreur_requete \n";
					
				if ($numquotite == $denomquotite)
				{
					$declarationTP = new declarationTP($dbcon);
					$declarationTP->affectationid($affectationid);
					$declarationTP->tabtpspartiel(str_repeat("0", 20));
					//echo "datedebut = $datedebut \n";
					$declarationTP->datedebut($datedebut);
					if ("$datefin" == "")
						$datefin = "9999-12-31";
					//echo "datefin = $datefin \n";
					$declarationTP->datefin($datefin);
					$erreur = $declarationTP->store();
					if ($erreur!="")
						echo "Erreur dans la declarationTP->store : " . $erreur . "\n";
				}
			}
			else
			{
				$res_aff = mysql_fetch_row($query_aff);
				//echo "res_aff[0]=$res_aff[0]   datemodif =$datemodif \n";
				if ($fonctions->formatdatedb($datemodif) != $fonctions->formatdatedb($res_aff[0]))
				{
					// Si Quotite <> alors envoyer un mail
					// Si date fin <> alors envoyer un mail
					// Faire l'update de la ligne
					$sql = sprintf("UPDATE AFFECTATION SET HARPEGEID='%s',NUMCONTRAT='%s',DATEDEBUT='%s',DATEFIN='%s',DATEMODIFICATION='%s',STRUCTUREID='%s',NUMQUOTITE='%s',DENOMQUOTITE='%s',OBSOLETE='%s' WHERE AFFECTATIONID='%s'",
							mysql_real_escape_string($harpegeid),
							mysql_real_escape_string($numcontrat),
							mysql_real_escape_string($datedebut),
							mysql_real_escape_string($datefin),
							mysql_real_escape_string($datemodif),
							mysql_real_escape_string($structureid),
							mysql_real_escape_string($numquotite),
							mysql_real_escape_string($denomquotite),
							mysql_real_escape_string($affectationid),
							'N');
					mysql_query($sql);
					$erreur_requete=mysql_error();
					if ($erreur_requete!="")
						echo "UPDATE AFFECTATION => $erreur_requete \n";
				}
			}
		}

	}

	fclose($fp);

	echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";

?>