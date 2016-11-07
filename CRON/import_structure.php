<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');
	
	$fonctions = new fonctions($dbcon);
	$date=date("Ymd");

	echo "Début de l'import des structures " . date("d/m/Y H:i:s") . "\n" ;

	// On regarde si le fichier des fonctions est present
	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_fonctions_$date.dat";
	if (file_exists($filename))
	{
		$separateur='|';
		// On ouvre le fichier des fonctions et on charge le contenu dans un tableau
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$ligne = fgets($fp); // lecture du contenu de la ligne
			if (trim($ligne)!="")
			{
				$ligne_element = explode($separateur,$ligne);
				if (count($ligne_element)==0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
				{
					// On doit arréter tout !!!!
					echo "#######################################################";
					echo "ALERTE : Le format du fichier $filename n'est pas correct !!! => Erreur dans la ligne $ligne \n";
					echo "#######################################################";
					fclose($fp);
					exit;
				}
				$harpegeid = trim($ligne_element[0]);
				$code_fonction = trim($ligne_element[1]);
				$libelle_fctn_cours = trim($ligne_element[2]);
				$libelle_fctn_long = trim($ligne_element[3]);
				$code_struct = trim($ligne_element[4]);
				
				if ($code_struct != "")
				{
					$tabfonctions[$code_struct]["#". intval("$code_fonction")] = $harpegeid;
				}
			}
		}
		fclose($fp);
	}
	else
	{
		echo "Le fichier des fonctions $filename n'existe pas ....\n";
		$tabfonctions = array();
	}
	
	echo "tabfonctions = " . print_r($tabfonctions,true) . " \n";
	
	
	// On parcourt le fichier des structures
	// 	Si la structure n'existe pas
	//			on insert la structure
	// 	Sinon
	//			on update les infos
	

	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_structures_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
		exit;
	}
	else
	{
		$separateur=';';
		// Vérification que le fichier d'entree est bien conforme 
		// => On le lit en entier et on vérifie qu'un séparateur est bien présent sur chaque ligne non vide...
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$ligne = fgets($fp); // lecture du contenu de la ligne
			if (trim($ligne)!="")
			{
				$ligne_element = explode($separateur,$ligne);
				if (count($ligne_element)==0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
				{
					// On doit arréter tout !!!!
					echo "#######################################################";
					echo "ALERTE : Le format du fichier $filename n'est pas correct !!! => Erreur dans la ligne $ligne \n";
					echo "#######################################################";
					fclose($fp);
					exit;
				}
			}
		}
		fclose($fp);
		
		// Initialisation du LDAP
		$LDAP_SERVER=$fonctions->liredbconstante("LDAPSERVER");
		$LDAP_BIND_LOGIN=$fonctions->liredbconstante("LDAPLOGIN");
		$LDAP_BIND_PASS=$fonctions->liredbconstante("LDAPPASSWD");
		$LDAP_SEARCH_BASE="ou=structures,dc=univ-paris1,dc=fr"; //$fonctions->liredbconstante("LDAPSEARCHBASE");
		$LDAP_CODE_STRUCT_ATTR="supanncodeentite";   //$fonctions->liredbconstante("LDAPATTRIBUTE");
		$con_ldap=ldap_connect($LDAP_SERVER);
		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		$r=ldap_bind($con_ldap, $LDAP_BIND_LOGIN,$LDAP_BIND_PASS);
		
		$fp = fopen("$filename","r");
		while (!feof($fp))
		{
			$ligne = fgets($fp); // lecture du contenu de la ligne du fichier des structures
			if (trim($ligne)!="")
			{
		//		echo "Ligne = $ligne \n";
				$ligne_element = explode($separateur,$ligne);
				echo "---------------------------------------------------\n";
				$code_struct = trim($ligne_element[0]);
				$nom_long_struct = trim($ligne_element[1]);
				$nom_court_struct = trim($ligne_element[2]);
				$parent_struct = trim($ligne_element[3]);
				
				if (array_key_exists("#1", (array)$tabfonctions[$code_struct]))
					$codefonction = "1"; // Président d'université
				elseif (array_key_exists("#1447", (array)$tabfonctions[$code_struct]))
					$codefonction = "1447"; // Directeur général des services
				elseif (array_key_exists("#1044", (array)$tabfonctions[$code_struct]))
					$codefonction = "1044"; // Agent comptable
				elseif (array_key_exists("#1521", (array)$tabfonctions[$code_struct]))
					$codefonction = "1521"; // Chef de service
				elseif (array_key_exists("#1522", (array)$tabfonctions[$code_struct]))
					$codefonction = "1522"; // Directeur(ice)
				elseif (array_key_exists("#1615", (array)$tabfonctions[$code_struct]))
					$codefonction = "1615"; // Chef de département
				elseif (array_key_exists("#1860", (array)$tabfonctions[$code_struct]))
					$codefonction = "1860"; // Chef d'atelier
				elseif (array_key_exists("#1087", (array)$tabfonctions[$code_struct]))
					$codefonction = "1087"; // Responsable Administratif de Composante
				elseif (array_key_exists("#41", (array)$tabfonctions[$code_struct]))
					$codefonction = "41"; // Dir. de services communs d'universités
				elseif (array_key_exists("#1016", (array)$tabfonctions[$code_struct]))
					$codefonction = "1016"; // Dir. éco. Inst. Uni - Hors arrêté 13/9/90
				elseif (array_key_exists("#1529", (array)$tabfonctions[$code_struct]))
					$codefonction = "1529"; // Directeur(ice) d'institut
				elseif (array_key_exists("#1530", (array)$tabfonctions[$code_struct]))
					$codefonction = "1530"; // Directeur(ice) d'UMR
				elseif (array_key_exists("#1532", (array)$tabfonctions[$code_struct]))
					$codefonction = "1532"; // Directeur(ice) de laboratoire
				elseif (array_key_exists("#38", (array)$tabfonctions[$code_struct]))
					$codefonction = "38"; // Dir. d'UFR
				elseif (array_key_exists("#1525", (array)$tabfonctions[$code_struct]))
					$codefonction = "1525"; // Directeur adjoint
				elseif (array_key_exists("#1523", (array)$tabfonctions[$code_struct]))
					$codefonction = "1523"; // Adjoint(e)
				else
					$codefonction = "";
				
				if ($codefonction != "")
				{
					echo "On a une fonction $codefonction pour la structure $nom_long_struct / $nom_court_struct  ($code_struct) \n";
					$resp_struct = $tabfonctions[$code_struct]["#". intval("$codefonction")];
				}
				else
				{
					echo "Pas de fonction pour la structure $nom_long_struct / $nom_court_struct  ($code_struct) dans le fichier des fonctions\n";
					echo "On recupere le responsable de la structure s'il est defini dans le fichier des structures\n";
					$resp_struct = trim($ligne_element[4]);
					// Si pas de responsable défini dans le fichier de structure
					if ($resp_struct == "")
					{
						// On regarde si un responsable de la structure est défini dans la table STRUCTURE
						// => Si oui, on ne le change pas.... Soit c'est un ancien responsable, soit il a été forcé à la main
						// à partir de l'insterface de gestion des structures
						echo "Recherche du responsable dans la table des structures\n";
						$sql = "SELECT RESPONSABLEID FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
						$query = mysql_query($sql);
						$erreur_requete=mysql_error();
						if ($erreur_requete!="")
							echo "SELECT STRUCTURE pour responsable => $erreur_requete \n";
						if (mysql_num_rows($query) > 0) // La structure existe bien déjà dans la base....
						{
							$result = mysql_fetch_row($query);
							$resp_struct = trim($result[0]);
						}
						// Si on arrive ici, c'est vraiment qu'on n'a aucune information nulle part !!!
						if ($resp_struct == "")
						{
							echo "On fixe le responsable à CRON_G2T pour la structure $nom_long_struct / $nom_court_struct  ($code_struct) \n";
							$resp_struct = '-1';
						}
					}
				}
				echo "Le code du responsable est : $resp_struct \n";
				$date_cloture = trim($ligne_element[5]);
				if (is_null($date_cloture) or $date_cloture=="")
					$date_cloture='2999-12-31';
				//echo "code_struct = $code_struct   nom_long_struct=$nom_long_struct   nom_court_struct=$nom_court_struct   parent_struct=$parent_struct   resp_struct=$resp_struct date_cloture=$date_cloture\n";

				$sql = "SELECT * FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
				$query = mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT STRUCTURE => $erreur_requete \n";
				if (mysql_num_rows($query) == 0) // Structure manquante
				{
					echo "Création d'une nouvelle structure : $nom_long_struct (Id = $code_struct) \n";
					$sql = sprintf("INSERT INTO STRUCTURE(STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID,DATECLOTURE) VALUES('%s','%s','%s','%s','%s','%s')",
							$fonctions->my_real_escape_utf8($code_struct),$fonctions->my_real_escape_utf8($nom_long_struct),$fonctions->my_real_escape_utf8($nom_court_struct),$fonctions->my_real_escape_utf8($parent_struct),$fonctions->my_real_escape_utf8($resp_struct),$fonctions->my_real_escape_utf8($date_cloture));
				}
				else
				{
					echo "Mise à jour d'une structure : $nom_long_struct (Id = $code_struct) \n";
					$sql = sprintf("UPDATE STRUCTURE SET NOMLONG='%s',NOMCOURT='%s',STRUCTUREIDPARENT='%s',RESPONSABLEID='%s', DATECLOTURE='%s' WHERE STRUCTUREID='%s'",
							$fonctions->my_real_escape_utf8($nom_long_struct),$fonctions->my_real_escape_utf8($nom_court_struct),$fonctions->my_real_escape_utf8($parent_struct),$fonctions->my_real_escape_utf8($resp_struct),$fonctions->my_real_escape_utf8($date_cloture),$fonctions->my_real_escape_utf8($code_struct));
					//echo $sql."\n";
				}
				mysql_query($sql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
				{
					echo "INSERT/UPDATE STRUCTURE => $erreur_requete \n";
					echo "sql = $sql \n";
				}
				// On regarde dans le LDAP s'il y a une correspondance avec une vieille strcture
				// On cherche le code de l'ancienne structure avec le filtre supannRefId={SIHAM.UO} + Code Nvelle (par exemple : supannRefId={SIHAM.UO}DGHA_4)
				// Si une correspondance existe :
				//		On charge la vieille structure de G2T => supannCodeEntite: DGHA
				// 		Si la structure est ouverte => date de cloture > '20151231'
				//			On recopie les informations structurantes dans la nouvelle structure :
				//				- GESTIONNAIREID varchar(10) 
				//				- AFFICHESOUSSTRUCT varchar(1) 
				//				- AFFICHEPLANNINGTOUTAGENT varchar(1) 
				//				- DEST_MAIL_RESPONSABLE varchar(1) 
				//				- DEST_MAIL_AGENT varchar(1) 
				//				- AFFICHERESPSOUSSTRUCT varchar(1)
				//			On sauvegarde la nouvelle structure
				//			On ferme l'ancienne avec la date du '20151231'
				//			On sauvegarde l'ancienne structure
				//		Fin Si (structure ouverte)
				//	Fin Si (Correspondance existe)
				$filtre="supannRefId={SIHAM.UO}" . $code_struct;
				$dn=$LDAP_SEARCH_BASE;
				$restriction=array("$LDAP_CODE_STRUCT_ATTR");
				$sr=ldap_search ($con_ldap,$dn,$filtre,$restriction);
				$info=ldap_get_entries($con_ldap,$sr);
				//echo "Info = " . print_r($info,true) . "\n";
				$oldstructid = $info[0]["$LDAP_CODE_STRUCT_ATTR"][0];
				echo "L'identifiant de l'ancienne structure est : " . $oldstructid . " correspondant à la nouvelle structure : $code_struct \n";
				
				$oldsql  = "SELECT STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID,GESTIONNAIREID,AFFICHESOUSSTRUCT,
						           AFFICHEPLANNINGTOUTAGENT,DEST_MAIL_RESPONSABLE,DEST_MAIL_AGENT,DATECLOTURE,AFFICHERESPSOUSSTRUCT 
						    FROM STRUCTURE
						    WHERE STRUCTUREID = '$oldstructid' ";
				$oldquery = mysql_query($oldsql);
				$erreur_requete=mysql_error();
				if ($erreur_requete!="")
					echo "SELECT OLD STRUCTURE => $erreur_requete \n";
				if (mysql_num_rows($oldquery) == 0) // Structure manquante
				{
					echo "Pas de correspondance avec l'ancienne structure $oldstructid \n";
				}
				else
				{
					$result = mysql_fetch_row($oldquery);
					if ($fonctions->formatdatedb($result[10]) > "20151231") // Si l'ancienne structuture n'est pas fermée
					{
						$sql = "UPDATE STRUCTURE 
						        SET GESTIONNAIREID ='$result[5]', 
						            AFFICHESOUSSTRUCT = '$result[6]', 
						            AFFICHEPLANNINGTOUTAGENT = '$result[7]', 
						            DEST_MAIL_RESPONSABLE = '$result[8]', 
						            DEST_MAIL_AGENT = '$result[9]', 
						            AFFICHERESPSOUSSTRUCT = '$result[11]' 
						        WHERE STRUCTUREID = '$code_struct'";
						if (substr($code_struct,0,3) == 'DGH')
						{
							//echo "SQL complement new struct = $sql \n";
						}
						mysql_query($sql);
						$erreur_requete=mysql_error();
						if ($erreur_requete!="")
						{
							echo "UPDATE STRUCTURE (migration) => $erreur_requete \n";
							echo "sql = $sql \n";
						}
						else
						{
							$sql = "UPDATE STRUCTURE SET DATECLOTURE = '20151231' WHERE STRUCTUREID = '$oldstructid'";
							mysql_query($sql);
							$erreur_requete=mysql_error();
							if ($erreur_requete!="")
							{
								echo "UPDATE STRUCTURE (cloture) => $erreur_requete \n";
								echo "sql = $sql \n";
							}
							else
							{
								echo "==> Fermeture de l'ancienne structure '$oldstructid' à la date du 31/12/2015\n";
							}
						}
					}
					else
					{
						echo "L'ancienne structure $oldstructid est déja fermée => Pas de récupération de données \n";
					}
				}
			}
		}
		fclose($fp);
	}
	echo "Fin de l'import des structures " . date("d/m/Y H:i:s") . "\n";

?>