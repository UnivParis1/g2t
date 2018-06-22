<?php

	require_once('CAS.php');
	include './includes/casconnection.php';

	if (isset($_POST["userid"]))
		$userid = $_POST["userid"];
	else
		$userid = null;
	if (is_null($userid) or ($userid==""))
	{
		error_log (basename(__FILE__)  . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}
	
	require_once("./class/agent.php");
	require_once("./class/structure.php");
	require_once("./class/solde.php");
	require_once("./class/demande.php");
	require_once("./class/planning.php");
	require_once("./class/planningelement.php");
	require_once("./class/declarationTP.php");
//	require_once("./class/autodeclaration.php");
//	require_once("./class/dossier.php");
	require_once("./class/tcpdf/tcpdf.php");
	require_once("./class/cet.php");
	require_once("./class/affectation.php");
	require_once("./class/complement.php");
		
	$user = new agent($dbcon);
	$user->load($userid);

	if (isset($_POST["agentid"]))
	{
		$agentid = $_POST["agentid"];
		if (!is_numeric($agentid))
		{
			$LDAP_SERVER=$fonctions->liredbconstante("LDAPSERVER");
			$LDAP_BIND_LOGIN=$fonctions->liredbconstante("LDAPLOGIN");
			$LDAP_BIND_PASS=$fonctions->liredbconstante("LDAPPASSWD");
			$LDAP_SEARCH_BASE=$fonctions->liredbconstante("LDAPSEARCHBASE");
			$LDAP_CODE_AGENT_ATTR=$fonctions->liredbconstante("LDAPATTRIBUTE");
			$con_ldap=ldap_connect($LDAP_SERVER);
			ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			$r=ldap_bind($con_ldap, $LDAP_BIND_LOGIN,$LDAP_BIND_PASS);
			$filtre="(uid=" . $agentid . ")";
			$dn=$LDAP_SEARCH_BASE;
			$restriction=array("$LDAP_CODE_AGENT_ATTR");
			$sr=ldap_search ($con_ldap,$dn,$filtre,$restriction);
			$info=ldap_get_entries($con_ldap,$sr);
			//echo "Le numÃ©ro HARPEGE de l'agent sÃ©lectionnÃ© est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
			if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
			{
				$agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
			}
		}
		
		if (!is_numeric($agentid))
		{
			$agentid=null;
			$agent = null;
		}
		else
		{
			$agent = new agent($dbcon);
			$agent->load($agentid);
		}
	}
	else
	{
		$agentid = null;
		$agent = null;
	}
	
	$mode = null;
	if (isset($_POST["mode"]))
		$mode = $_POST["mode"];
	
	$nbr_jours_cet = null;
	if (isset($_POST["nbr_jours_cet"]))
		$nbr_jours_cet = str_ireplace(",", ".", $_POST["nbr_jours_cet"]);
	
	if (isset($_POST["nbrejoursdispo"]))
		$nbrejoursdispo = $_POST["nbrejoursdispo"];
	else
		$nbrejoursdispo = null;
	if (isset($_POST["typeretrait"]))
		$typeretrait = $_POST["typeretrait"];
	else
		$typeretrait = null;
	
	$ajoutcet = null;
	if (isset($_POST["ajoutcet"]))
		$ajoutcet = $_POST["ajoutcet"];
	$retraitcet = null;
	if (isset($_POST["retraitcet"]))
		$retraitcet = $_POST["retraitcet"];
	$utilisationcet = null;
	if (isset($_POST["utilisationcet"]))
		$utilisationcet = $_POST["utilisationcet"];
	
	$mutation = null;
	if (isset($_POST["mutation"]))
	{
		if ($_POST["mutation"]=="mutation")
			$mutation=true;
		else
			$mutation=false;
		
	}
		
	$msg_erreur = "";
	
	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
	echo "<br>";
		
	//print_r($_POST); echo "<br>";
	
	if (!is_null($nbr_jours_cet))
	{
		if (!is_null($utilisationcet))
		{
			if ($nbr_jours_cet == 0 or $nbr_jours_cet == "")
			{
				$errlog = "Le nombre de jours saisi est vide ou il est égal à 0 ";
				$msg_erreur .= $errlog."<br/>";
				error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			}
			echo "floatval = " . (floatval($nbr_jours_cet)*2) . "    Intval = " . (intval($nbr_jours_cet*2)) . " <br>";
			if ((floatval($nbr_jours_cet)*2) != (intval($nbr_jours_cet*2)))  // Si le $nbr_jours_cet * 2 n'est pas un nombre entier => problème 
			{
				$errlog = "Le nombre de jours saisi doit être entier ou en demi-journée ";
				$msg_erreur .= $errlog."<br/>";
				error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			}
		}
		if (($nbr_jours_cet <= 0 or $nbr_jours_cet == "") and is_null($utilisationcet))
		{
			$errlog = "Le nombre de jours saisi est vide ou il est inférieur ou égal à 0 ";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
		elseif ((intval($nbr_jours_cet) != $nbr_jours_cet) and is_null($utilisationcet)) // Dans le cas d'une utilisation de CET (dépot d'un congé) on a droit aux 1/2 jrs 
		{
			$errlog = "Le nombre de jours à ajouter au CET doit être un nombre entier.";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
		elseif (!is_null($ajoutcet))
		{
			$soldeannuel = new solde($dbcon);
			// On charge le solde de congés Annuel pour vérifier les règles de gestions de prise du CET
			$msg_erreur = $msg_erreur .  $soldeannuel->load($agentid, "ann" . substr(($fonctions->anneeref()-1),2,2));
			//echo "msg_erreur = " . $msg_erreur . "<br>";
			if ($msg_erreur == "")
			{
// --------------------------------------------------------------------
// --------- Suppression du bloc avec contrôle --------------------------
/*
				// Si le nombre de jours demande est <= solde de l'année de ref et que l'on demande moins de jours de le nombre de jour dispo
				/// C'est un peu 2 fois le meme test .... A simplifier
				if ($soldeannuel->solde() >= $nbr_jours_cet and $nbrejoursdispo >= $nbr_jours_cet)
				{
					//echo "Avant le new cet (1) <br>";
					$cet = new cet($dbcon);
					// On regarde s'il existe deja un CET
					$msg_erreur = $msg_erreur . $cet->load($agentid);
					//echo "Apres le load cet (1) <br>";
					if ($msg_erreur != "")
					{
						// On force $msg_erreur à "" car on se moque de savoir quel est l'erreur
						$msg_erreur = "";
						echo "Il n'y a pas de CET <br>";
						error_log(basename(__FILE__)." uid : ".$agentid." : Il n'y a pas de CET");
						unset ($cet);
						$cet = new cet($dbcon);
						$cet->agentid($agentid);
						$cet->cumultotal($nbr_jours_cet);
						$cet->cumulannuel($fonctions->anneeref(),$nbr_jours_cet);
						//echo "Avant le store <br>";
						$msg_erreur = $cet->store();
						//echo "Apres le store <br>";
					}
					else
					{
						// La variable $msg_erreur est "" ==> Il n'y a pas eu de probleme
						//echo "Il y a un CET <br>";
						$cumul = ($cet->cumulannuel($fonctions->anneeref()));
						$cumul = $cumul + $nbr_jours_cet;
						// On ne peut pas mettre plus de 25 jours par an sur le CET.
						// 20 jours obligatoires
						// Base de calcul = 45 jours
						// ==> 45 - 20 = 25 jours maxi
						if ($cumul > 25) {
							$errlog = "Le nombre de jour de cumul annuel est supérieur à 25. Vous ne pouvez pas mettre autant de jours dans le CET. ";
							$msg_erreur .= $errlog."<br/>";
							error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
						}
						else
						{
							$cet->cumulannuel($fonctions->anneeref(),$cumul);
							$cumul = ($cet->cumultotal());
							$cumul = $cumul + $nbr_jours_cet;
							$cet->cumultotal($cumul);
							//echo "Avant le store <br>";
							$msg_erreur = $cet->store();
							//echo "Apres le store <br>";
						}
					}
					// Si tout s'est bien passé dans le store du CET
					if ($msg_erreur == "")
					{
						$tempsolde = ($soldeannuel->droitpris());
						$tempsolde = $tempsolde + $nbr_jours_cet;
						$soldeannuel->droitpris(($tempsolde));
						$msg_erreur = $msg_erreur . $soldeannuel->store();
						$agent->ajoutecommentaireconge("ann" . substr(($fonctions->anneeref()-1),2,2), ($nbr_jours_cet*-1),"Retrait de jours pour alimentation CET");
						// Envoi d'un mail à l'agent !
						//echo "Avant le pdf <br>";
						$cet = new cet($dbcon);
						$msg_erreur = $msg_erreur . $cet->load($agentid);
						$pdffilename = $cet->pdf($userid,TRUE);
						//echo "Avant l'envoi de mail <br>";
						$user->sendmail($agent,"Alimentation du CET","Votre CET vient d'être alimenté.", $pdffilename);
						//echo "Apres l'envoi de mail <br>";
					}
				}
				elseif ($nbrejoursdispo < $nbr_jours_cet) {
					$errlog = "Vos droits à CET sont insuffisants : Demandé " . $nbr_jours_cet . " jour(s)   Autorisé : " . $nbrejoursdispo . " jour(s)";
					$msg_erreur .= $errlog."<br/>";
					error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
				}
				else
				{
					$errlog = "Le solde de jour de congé annuel est insuffisant : Demande " . $nbr_jours_cet . "   Disponible : " . ($soldeannuel->solde());
					$msg_erreur .= $errlog."<br/>";
					error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
				}
*/
// ------ Fin suppression bloc avec controle ----------------------
// --------------------------------------------------------------
				// Si le solde de congés est suffisant.......
				if ($soldeannuel->solde() >= $nbr_jours_cet)
				{				//echo "Avant le new cet (1) <br>";
					$cet = new cet($dbcon);
					// On regarde s'il existe deja un CET
					$msg_erreur = $msg_erreur . $cet->load($agentid);
					//echo "Apres le load cet (1) <br>";
					if ($msg_erreur != "")
					{
						// On force $msg_erreur à "" car on se moque de savoir quel est l'erreur
						$msg_erreur = "";
						echo "Il n'y a pas de CET <br>";
						error_log(basename(__FILE__)." uid : ".$agentid." : Il n'y a pas de CET");
						unset ($cet);
						// On crée un nouveau CET que l'on instancie avec les valeurs courantes
						$cet = new cet($dbcon);
						$cet->agentid($agentid);
						$cet->cumultotal($nbr_jours_cet);
						$cet->cumulannuel($fonctions->anneeref(),$nbr_jours_cet);
						//echo "Avant le store <br>";
						$msg_erreur = $cet->store();
						//echo "Apres le store <br>";
					}
					else
					{
						// La variable $msg_erreur est "" ==> Il n'y a pas eu de probleme
						//echo "Il y a un CET <br>";
						$cumul = ($cet->cumulannuel($fonctions->anneeref()));
						$cumul = $cumul + $nbr_jours_cet;
						// On ne peut pas mettre plus de 25 jours par an sur le CET.
						// 20 jours obligatoires
						// Base de calcul = 45 jours
						// ==> 45 - 20 = 25 jours maxi
						if ($cumul > 25) {
							$errlog = "Le nombre de jour de cumul annuel est supérieur à 25. Vous ne pouvez pas mettre autant de jours dans le CET. ";
							$msg_erreur .= $errlog."<br/>";
							error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
						}
						else
						{
							$cet->cumulannuel($fonctions->anneeref(),$cumul);
							$cumul = ($cet->cumultotal());
							$cumul = $cumul + $nbr_jours_cet;
							$cet->cumultotal($cumul);
							//echo "Avant le store <br>";
							$msg_erreur = $cet->store();
							//echo "Apres le store <br>";
						}
					}
					// Si tout s'est bien passé dans le store du CET (création d'un nouveau CET ou ajout de jour dans un CET existant)
					if ($msg_erreur == "")
					{
						$tempsolde = ($soldeannuel->droitpris());
						$tempsolde = $tempsolde + $nbr_jours_cet;
						$soldeannuel->droitpris(($tempsolde));
						$msg_erreur = $msg_erreur . $soldeannuel->store();
						$agent->ajoutecommentaireconge("ann" . substr(($fonctions->anneeref()-1),2,2), ($nbr_jours_cet*-1),"Retrait de jours pour alimentation CET");
						// Envoi d'un mail à l'agent !
						//echo "Avant le pdf <br>";
						$cet = new cet($dbcon);
						$msg_erreur = $msg_erreur . $cet->load($agentid);
						$pdffilename = $cet->pdf($userid,TRUE);
						//echo "Avant l'envoi de mail <br>";
						$user->sendmail($agent,"Alimentation du CET","Votre CET vient d'être alimenté.", $pdffilename);
						//echo "Apres l'envoi de mail <br>";
					}
				}
				else
				{
					$errlog = "Le solde est insuffisant : Vous avez demandé " . $nbr_jours_cet . " jour(s) alors qu'il n'y a que " . ($soldeannuel->solde()) . " jour(s) de disponible sur '" . $soldeannuel->typelibelle() . "'.";
					$msg_erreur .= $errlog."<br/>";
					error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
				}
			}
			
		}
		elseif (!is_null($retraitcet))
		{
//			echo "Je suis dans une indemnisation de CET  => $nbr_jours_cet jours à retirer sur $nbrejoursdispo jour à retirer du CET maximum !!!<br>";
//			echo "Le type de retrait est : " . $_POST["typeretrait"] . "<br>";
			$cet = new cet($dbcon);
			$msg_erreur = $cet->load($agentid);
			if ($msg_erreur == "")
			{
				if (($cet->cumultotal()) >= $nbr_jours_cet)
				{
					$droit_cet = ($cet->jrspris());
					$droit_cet = $droit_cet + $nbr_jours_cet;
					$cet->jrspris(($droit_cet));
					$msg_erreur = $cet->store();
					if ($msg_erreur == "")
					{
						$msg_erreur = $agent->ajoutecommentaireconge("cet", ($nbr_jours_cet*-1),"Retrait de jours - Motif : " . $typeretrait);
						if ($nbr_jours_cet > 1)
							$detail = $nbr_jours_cet . " jours vous ont été retirés du CET au motif : " . $typeretrait;
						else 
							$detail = $nbr_jours_cet . " jour vous a été retiré du CET au motif : " . $typeretrait;
						unset ($cet);
						$cet = new cet($dbcon);
						$msg_erreur = $msg_erreur . $cet->load($agentid);
						$pdffilename = $cet->pdf($userid,FALSE,$detail);
						//echo "Avant l'envoi de mail <br>";
						$user->sendmail($agent,"Alimentation du CET","Votre CET vient d'être modifié.", $pdffilename);
					}
				}
				else
				{
					$msg_erreur = $msg_erreur . "Vos droits à CET sont insuffisants : Demandé " . $nbr_jours_cet . " jour(s)   Autorisé : " . $nbrejoursdispo . " jour(s)<br>";
				}
			}
			
		}
		elseif (!is_null($utilisationcet) and ($msg_erreur == ""))
		{
			// On va déduire le nombre de jours du solde du CET
			//echo "utilisationcet = $utilisationcet <br>";
			$solde = new solde($dbcon);
			$msg_erreur = $solde->load($agentid,'cet');
			//echo "msg_erreur = $msg_erreur <br>";
			if ($msg_erreur == "")
			{
				//echo "Calcul du solde restant .... => " . ($solde->droitaquis()-$solde->droitpris()) . "  nbr_jours_cet = $nbr_jours_cet <br>"; 
				if (($solde->droitaquis()-$solde->droitpris()) >= $nbr_jours_cet)
				{
					$nvsoldepris = $solde->droitpris() + $nbr_jours_cet;
					//echo "nvsoldepris = $nvsoldepris <br>";
					$solde->droitpris($nvsoldepris); 
					$msg_erreur = $solde->store();
					if ($msg_erreur == "")
					{
						if ($nbr_jours_cet < -1) // inférieur à -1 
							$detail = abs($nbr_jours_cet) . " jours vous ont été recrédités sur votre CET au motif : Annulation d'une demande de congé sur le solde du CET";
						elseif ($nbr_jours_cet < 0) // compris entre -1 et 0
							$detail = abs($nbr_jours_cet) . " jour vous a été recrédité sur votre CET au motif : Annulation d'une demande de congé sur le solde du CET";
						elseif ($nbr_jours_cet > 1) // Supérieur à 1
							$detail = $nbr_jours_cet . " jours vous ont été retirés du CET au motif : Utilisation du CET pour poser un congé";
						else // Compris entre 0 et 1
							$detail = $nbr_jours_cet . " jour vous a été retiré du CET au motif : Utilisation du CET pour poser un congé";
						$user->sendmail($agent,"Utilisation du CET pour poser des congés", "Le solde de votre CET vient d'être modifié.\n $detail");
					}
					else
					{
						echo "msg_erreur (après le store) = $msg_erreur <br>";
					}
				}
				else
				{
					$msg_erreur = $msg_erreur . "Vos droits à CET sont insuffisants : Demandé " . $nbr_jours_cet . " jour(s)   Autorisé : " . ($solde->droitaquis()-$solde->droitpris()) . " jour(s)<br>";
				}
			}
		}
		elseif ($msg_erreur == "")
		{
			$errlog = "Je ne sais pas ce que je fais ici => Ni un retrait, ni un ajout !!!!!";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$errlog);
		}
	}
	
	if ($msg_erreur != "")
	{
		echo "<p style='color: red'>" . $msg_erreur . "</p><br>";
		error_log(basename(__FILE__)." ".$msg_erreur);
		$msg_erreur = "";
	}
		
	if (strcasecmp($mode, "gestrh")==0)
	{
		echo "Personne à rechercher : <br>";
		echo "<form name='selectagentcet'  method='post' >";
		echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
		if (isset($_POST["agent"])) 
			echo $_POST["agent"]; 
		echo "' size=40 />";
		echo "<input type='hidden' id='agentid' name='agentid' value='"; 
		if (isset($_POST["agentid"])) 
			echo $_POST["agentid"]; 
		echo "' class='agent' /> ";
		?>
		<script>
		    	$("#agent").autocompleteUser(
		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
	   </script>
		<?php 			
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		echo "<input type='submit' value='Soumettre' >";
		echo "</form>";
		echo "<br>";
		echo "<br>";
	}
/*	
	if (is_null($agent))
	{
		if (strcasecmp($mode,"gest")==0)
			$structureliste=$user->structgestliste();
		else
			$structureliste=$user->structrespliste();
				
		echo "<form name='selectagentcet'  method='post' >";
		echo "<SELECT name='agentid'>";
		foreach ($structureliste as $structkey => $structure)
		{
			//$agentliste=$user->structure()->agentlist();
			echo "<optgroup label='". $structure->nomcourt() . "'>";
			$agentliste=$structure->agentlist($fonctions->anneeref() . $fonctions->debutperiode(), ($fonctions->anneeref()+1) . $fonctions->finperiode());
			if (is_array($agentliste))
			{
				foreach ($agentliste as $keyagent => $membre)
				{
					echo "<OPTION value='" . $membre->harpegeid() .  "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom()  . "</OPTION>";
				}
			}
			echo "</optgroup>";
		}
		echo "</SELECT>";
				
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		echo "<input type='submit' value='Soumettre' >";
		echo "</form>";
		
	}
	else
*/
	if (!is_null($agent))
	{
		//echo "On a choisit un agent <br>";
		$msg_bloquant = "";
		$soldeliste = $agent->soldecongesliste(($fonctions->anneeref()-1),$msg_bloquant);
		//echo "Apres le solde Liste<br>";
		$nbrejourspris = 0;
		// On initialise le nombre de jour annuel pris à 45 pour que plus tard 45 - 45 = 0 => Pas possibilité de poser de CET. 
		$nbrejoursprisannuel = 45;
		foreach ($soldeliste as $keysolde => $solde)
		{
			//echo "ann" . substr(($fonctions->anneeref()-1),2,2) . "<br>";
			//echo "keySolde = " . $keysolde . "   Solde->Type = " . $solde->typecode() . "<br>";

			// On mémorise le nombre de jours pris sur le conges annuels annXX
			// On s'en sert pour savoir combien de jour on peut mettre sur le CET....
			if (strcasecmp($solde->typeabsenceid(),"ann" . substr(($fonctions->anneeref()-1),2,2))==0)
			{
				$nbrejoursprisannuel = $solde->droitpris();
				if ($mutation==true)
				{
					$nbrejoursprisannuel = $nbrejoursprisannuel + $fonctions->liredbconstante("NBJOURS". ($fonctions->anneeref()-1)) - $solde->droitaquis();
					$nbrejourspris = $nbrejourspris + $fonctions->liredbconstante("NBJOURS". ($fonctions->anneeref()-1)) - $solde->droitaquis();
				}
			}
				
			// On exclu le CET du nombre de jour pris
			if (strcasecmp($solde->typeabsenceid(),"cet")!=0)
				$nbrejourspris = $nbrejourspris + $solde->droitpris();
		}
		
		
		
		//$nbrejourspris = ($nbrejourspris/2);
		//echo "nbrejourspris = " . $nbrejourspris . "<br>";
		//echo "nbrejoursprisannuel = " . $nbrejoursprisannuel . "<br>";
		/////// ATTENTION : SI LE NOMBRE DE JOUR PRIS EST > 45
		///////		ALORS ON LE FIXE A 45 
		if ($nbrejoursprisannuel > 45)
			$nbrejoursprisannuel = 45;
		
		// L'agent doit avoir pris 20 jours de congés durant l'année de référence
		if ($nbrejourspris < 20)
		{
			$msg_bloquant = $msg_bloquant . "L'agent ". $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pris le nombre minimum de jours sur l'année de référence " . ($fonctions->anneeref()-1)  . "<br>";
		}
		if ($msg_bloquant == "")
			// Le nombre de jour disponible pour le CET est 45 - le nombre de jours pris (ATTENTION On calcule sur les congés annuels => type annXX)
			// Le nombre 45 est la base générale de congé  de la fonction publique
			$nbrejoursdispo = 45 - $nbrejoursprisannuel;  // $nbrejourspris;
		else 
		{
			$nbrejoursdispo = 0;
		}
		$cet = new cet($dbcon);
		$msg_erreur_load = $cet->load($agentid);
		$msg_erreur = $msg_bloquant . $msg_erreur . $msg_erreur_load;
		
		if ($msg_erreur == "")
		{
			// Pas d'erreur lors du chargement du CET
			echo "Le CET de l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " est actuellement : <br>";
			//echo "Date du début du CET : ". $cet->datedebut() . "<br>";
			echo "Sur l'année " .  ($fonctions->anneeref()-1) . "/" . $fonctions->anneeref()  . ", " . $agent->identitecomplete() . " a cumulé " . ($cet->cumulannuel($fonctions->anneeref())) . " jour(s) <br>";
			echo "Le solde de CET est de " . (($cet->cumultotal()-$cet->jrspris())) . " jour(s)<br>";

		}

		$affectationliste = $agent->affectationliste(($fonctions->anneeref()-1) . $fonctions->debutperiode(), ($fonctions->anneeref()-1) . $fonctions->debutperiode());
		if (is_null($affectationliste) and (is_null($mutation)))
		{
			echo "<br>";
			echo "<span style='border:solid 1px black; background:lightgray; width:600px; display:block;'>";
			echo "<form name='frm_ajoutcet'  method='post' >";
			echo "<input type='checkbox' name='mutation' value='mutation'>Cochez cette case et validez si la personne est issue d'une mutation.<br>";
			echo "<br>";
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
			echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
			echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
			echo "<input type='hidden' name='ajoutcet' value='yes'>";
			echo "<input type='hidden' name='mode' value='" . $mode ."'>";
			echo "<input type='submit' value='Soumettre' >";
			echo "</form>";
			echo "</span>";
		}
		
		
		if ($msg_erreur_load != "")
		{
			// Il y a eu une erreur sur le chargement du CET ==> On met l'objet cet à NULL
			$cet = null;
			echo "<p style='color: red'>" . $msg_erreur . "</p>";
			error_log(basename(__FILE__)." ".$fonctions->stripAccents($msg_erreur));
		}
		if ($msg_bloquant != "") {
			$errlog = "Impossible de saisir un CET pour cet agent.";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$errlog);
		}
/*		
		if ($nbrejoursdispo > 0 ) {
			$errlog = "Vous avez le droit de mettre $nbrejoursdispo jour(s) dans le CET de l'agent.";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$errlog);
		}
		else {
			$errlog = "Vous n'avez pas le droit d'ajouter de jours dans le CET de l'agent (nombre de jours disponibles = $nbrejoursdispo).";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$errlog);
		}
*/		
		echo "<br>";
/*
		if (!is_null($cet))
		{
			echo "<span style='border:solid 1px black; background:lightgrey; width:600px; display:block;'>";
			echo "<form name='frm_utilisationcet'  method='post' >";
			if ($mutation==true)
			{
				echo "<input type='hidden' name='mutation' value='mutation'>";
			}
			elseif ($mutation==false)
			{
				echo "<input type='hidden' name='mutation' value=''>";
			}
			
			echo "Nombre de jours de CET utilisés/restitués comme congés : <input type=text name=nbr_jours_cet id=nbr_jours_cet size=3 >";
			echo "<br>";
			echo "<B>ATTENTION :</B> A n'utiliser que dans le cas d'une utilisation du solde de CET comme congés ou de l'annulation d'une demande de congés sur le solde du CET<br>";
			echo "Mettre un nombre positif pour utiliser des jours du CET.<br>Mettre un nombre négatif pour restituer des jours au CET.<br>";
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
			echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
			echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
			echo "<input type='hidden' name='utilisationcet' value='yes'>";
			echo "<input type='hidden' name='mode' value='" . $mode ."'>";
			if ($msg_bloquant == "")
				echo "<input type='submit' value='Soumettre' >";
			echo "</form>";
			echo "</span>";
		}
*/		
		echo "<br>";
		echo "<span style='border:solid 1px black; background:lightgreen; width:600px; display:block;'>";
		echo "<form name='frm_ajoutcet'  method='post' >";
		if ($mutation==true)
		{
			echo "<input type='hidden' name='mutation' value='mutation'>";
		}
		elseif ($mutation==false)
		{
			echo "<input type='hidden' name='mutation' value=''>";
		}
			
		echo "Nombre de jours à ajouter au CET : <input type=text name=nbr_jours_cet id=nbr_jours_cet size=3 >";
		echo "<br>";
		echo "<B>ATTENTION :</B> A n'utiliser que dans le cas d'une alimentation du CET à partir des reliquats<br>";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
		echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
		echo "<input type='hidden' name='ajoutcet' value='yes'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		if ($msg_bloquant == "")
			echo "<input type='submit' value='Soumettre' >";
		echo "</form>";
		echo "</span>";
		
		echo 'Avant le test null(CET) <br>';
		
		if (!is_null($cet))
		{
			// Seuls les jours au delà de 20 jours de CET peuvent être indemnisés ou ajoutés à la RAFP
			echo 'Cumul total = ' . $cet->cumultotal() . '  JrsPris =  ' . $cet->jrspris() . '<br>';
			if ((($cet->cumultotal()-$cet->jrspris())) > 20)
			{
				echo "<br>";
				echo "<span style='border:solid 1px black; background:lightsteelblue; width:600px; display:block;'>";
				echo "<form name='frm_retraitcet'  method='post' >";
				echo "Nombre de jours à retirer au CET : <input type=text name=nbr_jours_cet id=nbr_jours_cet size=3 > <br>";
				// Calcul du nombre de jours disponibles en retrait du CET
				//echo "cet->cumultotal() = " .  $cet->cumultotal() . "<br>";
				
				$nbrejoursdispo = ((($cet->cumultotal()-$cet->jrspris()))-20);
				echo "<br>Le nombre de jours maximum à retirer est : " . $nbrejoursdispo . " jours <br>";
				
				echo "Indiquer le type de retrait : ";
				echo "<select name='typeretrait'>";
				echo "<OPTION value='Indemnisation'>Indemnisation</OPTION>";
				echo "<OPTION value='Prise en compte au sein de la RAFP'>Prise en compte au sein de la RAFP</OPTION>";
				echo "</select>";
				echo "<br>";
				echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
				echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
				echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
				echo "<input type='hidden' name='retraitcet' value='yes'>";
				echo "<input type='hidden' name='mode' value='" . $mode ."'>";

				if ($mutation==true)
				{
					echo "<input type='hidden' name='mutation' value='mutation'>";
				}
				elseif ($mutation==false)
				{
					echo "<input type='hidden' name='mutation' value=''>";
				}
				
				if ($msg_bloquant == "")
					echo "<input type='submit' value='Soumettre' >";
				echo "</form>";
				echo "</span>";
			}
		}
		// Affichage du solde de l'année précédente
		echo $agent->soldecongeshtml($fonctions->anneeref()-1);
		// Affichage du solde de l'année en cours
		echo $agent->soldecongeshtml($fonctions->anneeref());
		// On affiche les commentaires pour avoir l'historique
		echo $agent->affichecommentairecongehtml();
	}
	


?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

