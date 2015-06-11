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
	require_once("../html/class/tcpdf/tcpdf.php");
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


	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/har_affectations_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
	}
	else
	{
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$affectation = null;
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
				if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000")) 
					$datefin = "9999-12-31";
				$datemodif = $ligne_element[5];
				$structureid = $ligne_element[6];
				$numquotite = $ligne_element[7];
				$denomquotite = $ligne_element[8];
				echo "affectationid = $affectationid   harpegeid=$harpegeid   numcontrat=$numcontrat   datemodif=$datemodif \n";
	
				$sql = sprintf("SELECT DATEMODIFICATION,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE FROM AFFECTATION WHERE AFFECTATIONID='%s'",$fonctions->my_real_escape_utf8($affectationid));
	//			if ($harpegeid == '9328')
	//				echo "sql (SELECT) = $sql \n";
				$query_aff = mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT AFFECTATION => $erreur_requete \n";
				// -------------------------------
				// Affectation manquante
				// -------------------------------
				if (mysql_num_rows($query_aff) == 0) 
				{
					echo "On est dans le cas ou l'affectation est manquante : $affectationid \n";
					//echo "Date de fin de l'affectation  => $datefin \n";
					if (("$datefin" == "") or ("$datefin" == "0000-00-00") or ("$datefin" == "00000000") or ("$datefin" == "00/00/0000"))
						$datefin = "9999-12-31";
					$sql = sprintf("INSERT INTO AFFECTATION(AFFECTATIONID,HARPEGEID,NUMCONTRAT,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE)
										VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
							$fonctions->my_real_escape_utf8($affectationid),
							$fonctions->my_real_escape_utf8($harpegeid),
							$fonctions->my_real_escape_utf8($numcontrat),
							$fonctions->my_real_escape_utf8($datedebut),
							$fonctions->my_real_escape_utf8($datefin),
							$fonctions->my_real_escape_utf8($datemodif),
							$fonctions->my_real_escape_utf8($structureid),
							$fonctions->my_real_escape_utf8($numquotite),
							$fonctions->my_real_escape_utf8($denomquotite),
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
						if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000")) 
							$datefin = "9999-12-31";
						//echo "datefin = $datefin \n";
						$declarationTP->datefin($datefin);
						$declarationTP->statut("v");
						$erreur = $declarationTP->store();
						if ($erreur!="")
							echo "Erreur dans la declarationTP->store : " . $erreur . "\n";
					}
				}
				// -------------------------------
				// L'affectation existe déja dans la base!!!
				// -------------------------------
				else	
				{
					// Pour chaque affectation, on regarde si la date de fin = 00/00/0000
					// Dans ce cas, on change en 31/12/9999
					echo "On est dans le cas ou l'affectation existe : $affectationid \n";
					$affectation = new affectation($dbcon);
					$affectation->load($affectationid);
					//echo "affectation->datefin()  = " . $affectation->datefin() . " \n";
					if (($affectation->datefin() == "") or ($affectation->datefin() == "00/00/0000"))
					{
						echo "Detection d'une affectation $affectationid avec date de fin = 000000000 \n";
						$datefin = "9999-12-31";
						$sql="UPDATE AFFECTATION SET DATEFIN='" . $datefin . "' WHERE AFFECTATIONID='" . $affectationid . "'";
						mysql_query($sql);
						$erreur_requete=mysql_error();
						if ($erreur_requete!="")
							echo "UPDATE AFFECTATION SET DATEFIN => $erreur_requete \n";
							
					} 
					$affectation = null;
					$res_aff = mysql_fetch_row($query_aff);
					//echo "res_aff[0]=$res_aff[0]   datemodif =$datemodif \n";
					// Si on a modifié quelque chose dans l'affectation
					
					if ($fonctions->formatdatedb($datemodif) != $fonctions->formatdatedb($res_aff[0]))
					{
						$affectation = new affectation($dbcon);
						$affectation->load($affectationid);
						// -------------------------------
						// On a changé la quotité de l'affectation
						// -------------------------------
						if (($affectation->numquotite() != $numquotite) or ($affectation->denumquotite() != $denomquotite))
						{
							echo "Cas Changement de quotite Ancienne " . $affectation->quotite() . "  numquotite =  $numquotite   denomquotite = $denomquotite \n";
							$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($affectation->datefin()));
							if (!is_null($declarationliste))
							{
								// Pour chaque declaration => On les annule
								foreach ($declarationliste as $declaration)
								{
									$msg = "";
									if (strcasecmp($declaration->statut(),"r")!=0)
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
								echo "La nvlle quotité est à 100% \n";
								$declarationTP = new declarationTP($dbcon);
								$declarationTP->affectationid($affectationid);
								$declarationTP->tabtpspartiel(str_repeat("0", 20));
								//echo "datedebut = $datedebut \n";
								$declarationTP->datedebut($datedebut);
								//echo "Datefin de la declaration TP  = $datefin \n";
								if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000")) 
									$datefin = "9999-12-31";
								//echo "datefin = $datefin \n";
								$declarationTP->datefin($datefin);
								$declarationTP->statut("v");
								$erreur = $declarationTP->store();
								if ($erreur!="")
									echo "Erreur dans la déclarationTP->store : " . $erreur . "\n";
							}
							else 
							{
								// Quotité != 100% donc on ne crée pas de declaration TP
								echo "La nvlle quotité n'est pas 100% \n";
							}
						}
						// -------------------------------
						// La quotite n'a pas change et on est a 100%
						// ------------------------------- 
						elseif ($numquotite == $denomquotite)
						{
							echo "Cas où on est à 100% \n";
							// Si on a modifié la durée de l'affectation
							// Alors on doit modifier la durée de la declaration de TP à 100% 
							//echo "datedebut = $datedebut   affectation->datedebut() = " . $affectation->datedebut() . "   datefin = $datefin   affectation->datefin() = " . $affectation->datefin() . "\n";
							
							//echo "datefin = $datefin length(datefin) = " . strlen($datefin)  ."  et Affectation->Datefin=" . $affectation->datefin() . "\n";
							//if (is_null($datefin)) echo "datefin est null   "; else echo "datefin NOT null   ";
							//if (is_null($affectation->datefin())) echo "affectation->datefin() est null   "; else echo "affectation->datefin() NOT null   ";
							//echo "\n";
							
							if (($fonctions->formatdatedb($datedebut) != $fonctions->formatdatedb($affectation->datedebut())) 
						      or ($fonctions->formatdatedb($datefin) != $fonctions->formatdatedb($affectation->datefin())))
						   {
								echo "Cas où on modifie la durée de l'affectation\n";
								$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($affectation->datefin()));
								if (!is_null($declarationliste))
								{
									foreach ($declarationliste as $declarationTP)
									{
										if (strcasecmp($declarationTP->statut(),"r")!=0)
										{
											$declarationTP->datedebut($datedebut);
											if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000")) 
												$datefin = "9999-12-31";
											$declarationTP->datefin($datefin);
											$erreur = $declarationTP->store();
											if ($erreur!="")
												echo "Erreur dans la declarationTP->store (changement duree temp complet): " . $erreur . "\n";
										}
									}
								}
							}
						}
						// -------------------------------
						// La quotite n'a pas change et on n'est pas à 100% => C'est un TP
						// -------------------------------
						elseif ($numquotite != $denomquotite)
						{
							echo "Cas où on est à temps partiel \n";
							//echo "affectation debut = " . $affectation->datedebut() . "\n";
							//echo "affectation fin = " . $affectation->datefin() . "\n";
							// Si on a repousser le début de l'affectation
							//echo "Avant le test Cas où on repousse le début de l'affectation \n";
							if ($fonctions->formatdatedb($datedebut) > $fonctions->formatdatedb($affectation->datedebut()))
							{
								echo "Cas où on repousse le début de l'affectation \n";
								$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()),$fonctions->formatdate($datedebut));
								if (!is_null($declarationliste))
								{
									foreach ($declarationliste as $declaration)
									{
										$msg = "";
										if (strcasecmp($declaration->statut(),"r")!=0)
										{
											// Si la nvlle date de debut est apres la date de fin => On annule la declaration
											if ($fonctions->formatdatedb($datedebut) > $fonctions->formatdatedb($declaration->datefin()))
												$declaration->statut("r");
											else 
												$declaration->datedebut($datedebut);
											
											$msg = $declaration->store();
										}
										if ($msg != "")
											echo "Erreur dans le store de la déclaration (repousse date début) " . $declaration->declarationTPid() . " : $msg \n";
									}
								}
							}
							// Si on a avancer la fin de l'affectation
							//echo "Avant le test Cas ou on avance la date de fin \n";
							//echo "datefin = $datefin \n";
							if ($fonctions->formatdatedb($datefin) < $fonctions->formatdatedb($affectation->datefin()))
							{
								echo "Cas où on avance la date de fin \n";
								$declarationliste = $affectation->declarationTPliste($fonctions->formatdate($datefin),$fonctions->formatdate($affectation->datefin()));
								if (!is_null($declarationliste))
								{
									foreach ($declarationliste as $declaration)
									{
										echo "Déclaration en cours => "; print_r($declaration); echo " \n";
										$msg = "";
										if (strcasecmp($declaration->statut(),"r")!=0)
										{
											// Si la nvlle date de fin est avant la date de début => On annule la declaration
											if ($fonctions->formatdatedb($datefin) < $fonctions->formatdatedb($declaration->datedebut()))
												$declaration->statut("r");
											else 
												$declaration->datefin($datefin);
											
											$msg = $declaration->store();
										}
										if ($msg != "")
											echo "Erreur dans le store de la declaration (avance date fin) " . $declaration->declarationTPid() . " : $msg \n";
									}
								}
							}
						}
						// Si Quotite <> alors envoyer un mail
						// Si date fin <> alors envoyer un mail
						// Faire l'update de la ligne
						echo "On update l'affectation (identifiant = " . $affectationid . ")\n";
						$sql = sprintf("UPDATE AFFECTATION SET HARPEGEID='%s',NUMCONTRAT='%s',DATEDEBUT='%s',DATEFIN='%s',DATEMODIFICATION='%s',STRUCTUREID='%s',NUMQUOTITE='%s',DENOMQUOTITE='%s',OBSOLETE='%s' WHERE AFFECTATIONID='%s'",
								$fonctions->my_real_escape_utf8($harpegeid),
								$fonctions->my_real_escape_utf8($numcontrat),
								$fonctions->my_real_escape_utf8($datedebut),
								$fonctions->my_real_escape_utf8($datefin),
								$fonctions->my_real_escape_utf8($datemodif),
								$fonctions->my_real_escape_utf8($structureid),
								$fonctions->my_real_escape_utf8($numquotite),
								$fonctions->my_real_escape_utf8($denomquotite),
								'N',
								$fonctions->my_real_escape_utf8($affectationid));
						//if ($harpegeid == '9328')
						//	echo "sql = $sql \n";
						mysql_query($sql);
						$erreur_requete=mysql_error();
						if ($erreur_requete!="")
							echo "UPDATE AFFECTATION => $erreur_requete \n";
					}
					else 
					{
						$sql = sprintf("UPDATE AFFECTATION SET OBSOLETE='N' WHERE AFFECTATIONID='%s'",
							$fonctions->my_real_escape_utf8($affectationid));
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
		$sql = "SELECT AFFECTATION.AFFECTATIONID,AFFECTATION.HARPEGEID FROM AFFECTATION,DECLARATIONTP ";
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
			echo "ATTENTION : Il y a des affectations obsoletes \n";
			while ($result = mysql_fetch_row($query))
			{
				// On recherche si une affectation avec les mêmes critères existe
				echo "On regarde s'il y a une affectation identique pour l'ancienne affectation " . $result[0] . " (HarpegeId = " . $result[1]  . ") : ";
				$sql = "SELECT AFFNEW.AFFECTATIONID ";
				$sql = $sql . " FROM AFFECTATION AFFNEW, AFFECTATION AFFOLD ";
				$sql = $sql . " WHERE AFFNEW.DATEDEBUT = AFFOLD.DATEDEBUT ";
				$sql = $sql . "   AND (AFFNEW.DATEFIN = AFFOLD.DATEFIN OR AFFOLD.DATEFIN >= '" . date('Y-m-d') . "') "; // AFFOLD.DATEFIN = '9999-12-31') ";
				$sql = $sql . "   AND AFFNEW.STRUCTUREID = AFFOLD.STRUCTUREID ";
				$sql = $sql . "   AND AFFNEW.NUMQUOTITE = AFFOLD.NUMQUOTITE ";
				$sql = $sql . "   AND AFFNEW.DENOMQUOTITE = AFFOLD.DENOMQUOTITE ";
				$sql = $sql . "   AND AFFNEW.OBSOLETE = 'N' ";
				$sql = $sql . "   AND AFFNEW.AFFECTATIONID != AFFOLD.AFFECTATIONID ";
				$sql = $sql . "   AND AFFNEW.HARPEGEID = AFFOLD.HARPEGEID ";
				$sql = $sql . "   AND AFFOLD.AFFECTATIONID = " . $result[0] ;
				$query2 = mysql_query($sql,$dbcon);
				//echo "SQL = " . $sql . "\n";
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT AFFECTATION OBSOLETE => $erreur_requete \n";
				if (mysql_num_rows($query2) > 0) // Il y a une affectations nouvelles avec les mêmes critères qu'une ancienne
				{
					$result2 = mysql_fetch_row($query2);
					echo "OUI => nouvelle affectation = " . $result2[0]  . "\n";
					$affnew = new affectation($dbcon);
					$affold = new affectation($dbcon);
					$affnew->load($result2[0]);  // On charge la nouvelle affectation
					$affold->load($result[0]);   // On charge l'ancienne affectation
					//echo "Avant le test 100% quotité \n";
					if ($affold->numquotite() != $affold->denumquotite()) // Si ce n'est pas une affectation à 100%, on va cloner les demandes de TP associées à l'ancienne Affectation
					{
						//echo "On charge les declarationTP Old \n ";
						$declarationliste = $affold->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
						$oldTP = new $declarationTP($dbcon);
						foreach ($declarationliste as $oldTP)
						{ 
							//echo "On va cloner les nouvelle declarationTP \n";
							$newTP = new declarationTP($dbcon);
							$newTP->affectationid($affnew->affectationid());
							$newTP->tabtpspartiel($oldTP->tabtpspartiel());
							$newTP->datedebut($oldTP->datedebut());
							$newTP->datefin($oldTP->datefin());
							$newTP->statut($oldTP->statut());
							// $newTP->datedemande($oldTP->datedemande()); => Initialisé dans la fonction STORE
							// $newTP->datestatut($oldTP->datestatut()); => Initialisé dans la fonction STORE
							//echo "Avant le store ... \n"; 
							$erreur = $newTP->store();
							if ($erreur != "")
							{
								echo "ERREUR DANS LE STORE (CLONE DU TP " . $oldTP->declarationTPid() . ") => $erreur \n";
							}
						}
					}
					// On a maintenant les TP qui sont déclarés comme dans l'ancienne affectation
					// On va les recharger 
					$affnew = new affectation($dbcon);
					$affold = new affectation($dbcon);
					//echo "Avant le load affnew ..... \n";
					$affnew->load($result2[0]);  // On charge la nouvelle affectation
					//echo "Avant le load affold ..... \n";
					$affold->load($result[0]);   // On charge l'ancienne affectation
					//echo "Avant le declartationTP pour Old \n";
					$olddeclarationliste = $affold->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
					//echo "Avant le declartationTP pour New \n";
					$newdeclarationliste = $affnew->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
					//echo "olddeclarationliste = "; print_r($olddeclarationliste); echo "\n";
					//echo "newdeclarationliste = "; print_r($newdeclarationliste); echo "\n";
					if (!is_null($olddeclarationliste))
					{
						$indexnewTP = 0;
						foreach ($olddeclarationliste as $oldTP)
						{ 
							
							$newTP = $newdeclarationliste[$indexnewTP];
							//echo "newTP->declarationTPid() = " . $newTP->declarationTPid() . "    oldTP->declarationTPid() = " . $oldTP->declarationTPid() . "\n";
							// On va maintenant raccrocher les anciennes demandes de congés à la nouvelle declarationTP
							$sql = "UPDATE DEMANDEDECLARATIONTP SET DECLARATIONID = " . $newTP->declarationTPid() . " WHERE DECLARATIONID = " . $oldTP->declarationTPid() . "  ";
							$sql = $sql . " AND DEMANDEID IN (SELECT DEMANDEID FROM DEMANDE WHERE DATEFIN <= '" . $fonctions->formatdatedb($newTP->datefin())  . "')"; 
							//echo "SQL (UPDATE DEMANDEDECLARATIONTP....) = " . $sql . "\n";
							$result_update = mysql_query($sql,$dbcon);
							$erreur_requete=mysql_error();
							$nbreligne = mysql_affected_rows(); //  => Savoir combien de lignes ont été modifiées
							echo "\tIl y a $nbreligne demandes de congés qui ont été déplacées. \n";
							if ($erreur_requete!="")
							{
								echo "ERREUR DANS LE DEPLACEMENT DES DEMANDE => Ancien TP.ID=" . $oldTP->declarationTPid() . "  Nouveau TP.ID=" . $newTP->declarationTPid() . "\n"; 
							}
							$indexnewTP = $indexnewTP + 1;
									
						}
					} 
				}
				else
				{
					echo "NON \n";
/*
					// On cherche pour chaque demandes de l'ancienne affectation/declarationTP si une déclaration de TP la couvre avec la même quotité
					$sql = "SELECT AGENT.HARPEGEID,AGENT.NOM, DEMANDE.DEMANDEID, DEMANDE.TYPEABSENCEID,DEMANDE.DATEDEBUT,DEMANDE.MOMENTDEBUT,DEMANDE.DATEFIN,DEMANDE.MOMENTFIN,DEMANDE.STATUT ";
					$sql = $sql . "FROM AGENT,AFFECTATION,DECLARATIONTP,DEMANDEDECLARATIONTP,DEMANDE "; 
					$sql = $sql . "WHERE DEMANDE.STATUT = 'v' ";
  					$sql = $sql . "AND DEMANDE.DEMANDEID=DEMANDEDECLARATIONTP.DEMANDEID ";
  					$sql = $sql . "AND DEMANDEDECLARATIONTP.DECLARATIONID=DECLARATIONTP.DECLARATIONID ";
  					$sql = $sql . "AND DECLARATIONTP.AFFECTATIONID=AFFECTATION.AFFECTATIONID ";
  					$sql = $sql . "AND AFFECTATION.HARPEGEID=AGENT.HARPEGEID ";
  					$sql = $sql . "AND AFFECTATION.AFFECTATIONID = " . $result[0];
  					$query2 = mysql_query($sql,$dbcon);
  					$erreur_requete=mysql_error();
  					if ($erreur_requete!="")
  						echo "SELECT DEMANDE  => $erreur_requete \n";
  					if (mysql_num_rows($query2) > 0) // Il y a des demandes qui sont orphelines
  					{
  						while ($result2 = mysql_fetch_row($query2))
  						{
  							
  						}
  					}
*/  							
							
				}
				
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
		else 
		{
			echo "Pas d'affectation obsolete.... \n";
		}
		
	}	

	echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";

?>