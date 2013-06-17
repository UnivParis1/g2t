<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	require_once("../html/class/agent.php");
	require_once("../html/class/structure.php");
	require_once("../html/class/solde.php");
	require_once("../html/class/demande.php");
	require_once("../html/class/planning.php");
	require_once("../html/class/planningelement.php");
	require_once("../html/class/declarationTP.php");
//	require_once("../html/class/autodeclaration.php");
//	require_once("../html/class/dossier.php");
	require_once("../html/class/fpdf.php");
	require_once("../html/class/cet.php");
	require_once("../html/class/affectation.php");
	require_once("../html/class/complement.php");
	
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
//			if ($harpegeid == '9328')
//				echo "sql (SELECT) = $sql \n";
			$query_aff = mysql_query($sql);
			$erreur_requete=mysql_error();
			if ($erreur_requete!="")
				echo "SELECT AFFECTATION => $erreur_requete \n";
			if (mysql_num_rows($query_aff) == 0) // Affectation manquante
			{
				//echo "Date de fin de l'affectation  => $datefin \n";
				if (("$datefin" == "") or ("$datefin" == "0000-00-00") or ("$datefin" == "00000000"))
					$datefin = "9999-12-31";
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
				
				//echo "Import_affectation => numquotite = $numquotite  denomquotite = $denomquotite \n"; 
				if ($numquotite == $denomquotite)
				{
					$declarationTP = new declarationTP($dbcon);
					$declarationTP->affectationid($affectationid);
					$declarationTP->tabtpspartiel(str_repeat("0", 20));
					//echo "datedebut = $datedebut \n";
					$declarationTP->datedebut($datedebut);
					//echo "Datefin de la declaration TP  = $datefin \n";
					if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000")) 
						$datefin = "9999-12-31";
					//echo "datefin = $datefin \n";
					$declarationTP->datefin($datefin);
					$declarationTP->statut("v");
					$erreur = $declarationTP->store();
					if ($erreur!="")
						echo "Erreur dans la declarationTP->store : " . $erreur . "\n";
				}
			}
			else	// L'affectation existe déja dans la base!!!
			{
				$res_aff = mysql_fetch_row($query_aff);
				//echo "res_aff[0]=$res_aff[0]   datemodif =$datemodif \n";
				// Si on a modifié quelque chose dans l'affectation
				if ($fonctions->formatdatedb($datemodif) != $fonctions->formatdatedb($res_aff[0]))
				{
					$affectation = new affectation($dbcon);
					$affectation->load($affectationid);
					// On a changé la quotité de l'affectation
					if (($affectation->numquotite() != $numquotite) or ($affectation->denumquotite() != $denomquotite))
					{
						echo "Cas Changement de quotite Ancienne " . $affectation->quotitevaleur() . "  numquotite =  $numquotite   denomquotite = $denomquotite \n";
						$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($affectation->datefin()));
						if (!is_null($declarationliste))
						{
							// Pour chaque declaration => On les annule
							foreach ($declarationliste as $declaration)
							{
								$msg = "";
								if ($declaration->statut() != "r")
								{
									$declaration->statut("r");
									$msg = $declaration->store();
								}
								if ($msg != "")
									echo "Erreur dans le store de la declaration (quotite) " . $declaration->declarationTPid() . " : $msg \n";
							}
						}
						// Si la quotité est à 100% on crée une déclaration de TP
						if ($numquotite == $denomquotite)
						{
							echo "La nvlle quotite est à 100% \n";
							$declarationTP = new declarationTP($dbcon);
							$declarationTP->affectationid($affectationid);
							$declarationTP->tabtpspartiel(str_repeat("0", 20));
							//echo "datedebut = $datedebut \n";
							$declarationTP->datedebut($datedebut);
							//echo "Datefin de la declaration TP  = $datefin \n";
							if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000")) 
								$datefin = "9999-12-31";
							//echo "datefin = $datefin \n";
							$declarationTP->datefin($datefin);
							$declarationTP->statut("v");
							$erreur = $declarationTP->store();
							if ($erreur!="")
								echo "Erreur dans la declarationTP->store : " . $erreur . "\n";
						}
						else 
						{
							// Quotité != 100% donc on ne crée pas de declaration TP
							echo "La nvlle quotite n'est pas 100%";
						}
					}
					// Si on est a 100% et qu'on a agrandi la durée de l'affectation
					// Alors on doit agrandir la durée de la declaration de TP à 100% (debut avant ou fin plus tard)
					if (($numquotite == $denomquotite) and 
					    (($fonctions->formatdatedb($datedebut) < $fonctions->formatdatedb($affectation->datedebut())) 
					      or ($fonctions->formatdatedb($datefin) > $fonctions->formatdatedb($affectation->datefin()))))
					{
						echo "Cas ou on agrandi la durée de l'affectation et on est à 100% \n";
						$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($affectation->datefin()));
						if (!is_null($declarationliste))
						{
							$declarationTP = reset($declarationliste);
							$declarationTP->datedebut($datedebut);
							if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000")) 
								$datefin = "9999-12-31";
							$declarationTP->datefin($datefin);
							$erreur = $declarationTP->store();
							if ($erreur!="")
								echo "Erreur dans la declarationTP->store (changement duree temp complet): " . $erreur . "\n";
							
						}
					}
					
					// Si on a repousser le début de l'affectation
					if ($fonctions->formatdatedb($datedebut) > $fonctions->formatdatedb($affectation->datedebut()))
					{
						echo "Cas ou on repousse le début de l'affectation \n";
						$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($datedebut));
						if (!is_null($declarationliste))
						{
							foreach ($declarationliste as $declaration)
							{
								$msg = "";
								if ($declaration->statut() != "r")
								{
									$declaration->statut("r");
									$msg = $declaration->store();
								}
								if ($msg != "")
									echo "Erreur dans le store de la declaration (date debut) " . $declaration->declarationTPid() . " : $msg \n";
							}
						}
					}
					// Si on a avancer la fin de l'affectation
					if ($fonctions->formatdatedb($datefin) < $fonctions->formatdatedb($affectation->datefin()))
					{
						echo "Cas ou on avance la date de fin \n";
						$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($datefin),$fonctions->formatdate($affectation->datefin()));
						if (!is_null($declarationliste))
						{
							foreach ($declarationliste as $declaration)
							{
								$msg = "";
								if ($declaration->statut() != "r")
								{
									$declaration->statut("r");
									$msg = $declaration->store();
								}
								if ($msg != "")
									echo "Erreur dans le store de la declaration (date fin) " . $declaration->declarationTPid() . " : $msg \n";
							}
						}
					}
					
					// Si Quotite <> alors envoyer un mail
					// Si date fin <> alors envoyer un mail
					// Faire l'update de la ligne
					echo "On update l'affectation \n";
					$sql = sprintf("UPDATE AFFECTATION SET HARPEGEID='%s',NUMCONTRAT='%s',DATEDEBUT='%s',DATEFIN='%s',DATEMODIFICATION='%s',STRUCTUREID='%s',NUMQUOTITE='%s',DENOMQUOTITE='%s',OBSOLETE='%s' WHERE AFFECTATIONID='%s'",
							mysql_real_escape_string($harpegeid),
							mysql_real_escape_string($numcontrat),
							mysql_real_escape_string($datedebut),
							mysql_real_escape_string($datefin),
							mysql_real_escape_string($datemodif),
							mysql_real_escape_string($structureid),
							mysql_real_escape_string($numquotite),
							mysql_real_escape_string($denomquotite),
							'N',
							mysql_real_escape_string($affectationid));
					if ($harpegeid == '9328')
						echo "sql = $sql \n";
					mysql_query($sql);
					$erreur_requete=mysql_error();
					if ($erreur_requete!="")
						echo "UPDATE AFFECTATION => $erreur_requete \n";
				}
				else 
				{
					$sql = sprintf("UPDATE AFFECTATION SET OBSOLETE='N' WHERE AFFECTATIONID='%s'",
						mysql_real_escape_string($affectationid));
//					if ($harpegeid == '9328')
//						echo "sql (Statut seul) = $sql \n";
					mysql_query($sql);
					$erreur_requete=mysql_error();
					if ($erreur_requete!="")
						echo "UPDATE AFFECTATION (Statut seul)=> $erreur_requete \n";
				}
			}
		}

	}

	fclose($fp);
	
	// Pour toutes les affectations obsolètes 
	// qui ont des déclarations non supprimées
	// on doit supprimer les déclarations de temps partiels => suppression des demandes
	$sql = "SELECT AFFECTATION.AFFECTATIONID FROM AFFECTATION,DECLARATIONTP ";
	$sql = $sql . " WHERE AFFECTATION.OBSOLETE='O'";
	$sql = $sql . "   AND AFFECTATION.AFFECTATIONID=DECLARATIONTP.AFFECTATIONID ";
	$sql = $sql . "   AND DECLARATIONTP.STATUT != 'r'";
	//echo "$sql (obsolete) = $sql \n";
	$query = mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "SELECT AFFECTATION OBSOLETE => $erreur_requete \n";
	if (mysql_num_rows($query) > 0) // Il y a des affectation obsoletes
	{
		while ($result = mysql_fetch_row($query))
		{
			unset($affectation);
			$affectation = new affectation($dbcon);
			$affectation->load($result[0]);
			$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()), $fonctions->formatdate($affectation->datefin()));
			if (!is_null($declarationliste))
			{
				foreach ($declarationliste as $declaration) 
				{
					$declaration->statut("r");
					$msg = $declaration->store();
					if ($msg != "")
						echo "Problème lors de la suppression de la déclaration " . $declaration->declarationTPid() . " : " . $msg . " \n";
				}
			}
		}
	}
	
	

	echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";

?>