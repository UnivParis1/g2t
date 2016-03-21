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

	$filename = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_structures_$date.dat";
	if (!file_exists($filename))
	{
		echo "Le fichier $filename n'existe pas !!! \n";
		exit;
	}
	else
	{
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
				echo "L'identifiant de l'ancienne structure est : " . $oldstructid . " correspondant à la nouvelle strucutre : $code_struct \n";
				
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