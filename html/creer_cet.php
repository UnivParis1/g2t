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
	
	$msg_erreur = "";
	$msg_bloquant = "";
	
	$mode = null;
	if (isset($_POST["mode"]))
		$mode = $_POST["mode"];
	
	$nbr_jours_cet = null;
	if (isset($_POST["nbr_jours_cet"]))
	{
		$nbr_jours_cet = $_POST["nbr_jours_cet"];
		if ((strcasecmp(intval($nbr_jours_cet),$nbr_jours_cet)==0) and (intval($nbr_jours_cet)>=0))  // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
		{
			// C'est correct
		}
		else
		{
			$msg_erreur = $msg_erreur . "Le nombre de jour saisi n'est pas correct. Veuillez saisir une valeur numérique, positive et entère.<br>";
			$nbr_jours_cet=null;
		}
	}
	
	$date_ouv_cet = null;
	if (isset($_POST["date_ouv_cet"]))
	{
		$date_ouv_cet = $_POST["date_ouv_cet"];
		if (!$fonctions->verifiedate($date_ouv_cet))
		{
			$msg_erreur = $msg_erreur .  "Le format de la date d'ouverture du CET est incorrect ou la date n'est pas valide....<br>";
			$date_ouv_cet = null;
		}
	}
	
	if (!is_null($date_ouv_cet) and !is_null($nbr_jours_cet))
	{
		// Les informations d'entrée sont bonnes.... On va créer le CET pour l'agent
		$cet = new cet($dbcon);
		if ($cet->load($agentid) != "")
		{
			unset($cet);
			$cet = new cet($dbcon);
			$cet->agentid($agentid);
			$cet->cumultotal($nbr_jours_cet);
			$cet->datedebut($date_ouv_cet);
			$erreur = $cet->store();
			if ($erreur != "")
			{
				$msg_erreur = $msg_erreur .  "Erreur lors de l'enregistrement du CET : $erreur <br>";
			}
			else  // La sauvegarde du CET est correcte.
			{
				$text = "Votre CET vient d'être repris dans l'application de gestion des congés.\n";
				$text = $text . "Le solde de votre CET est de $nbr_jours_cet jour(s). \n";
				/// ATTENTION : On doit recharger le CET pour avoir toutes les propriétés initialisées (Cet->jrspris,Cet->cumulannuel ,Cet->idtotal)
				unset($cet);
				$cet = new cet($dbcon);
				$cet->load($agentid);
				$pdffilename = $cet->pdf($userid,false);
				$user->sendmail($agent, "Création d'un CET avec reprise de solde", $text,$pdffilename);
			}
		}
		else
		{
			$msg_bloquant = "CET déja existant !!!! <br>";
		}
	}
		
	
	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	//echo "Les paramètres : " . print_r($_POST,true) . "<br>";
	
	echo "Cette page permet de créer un Compte Epargne Temps (CET) et de le créditer avec un solde non déduit des congés. <br>";
	echo "Elle doit être utilisée uniquement dans le cas où un agent arrive dans l'établissement et dispose déjà d'un CET. <br>";
	echo "<br>";
	echo "Si vous souhaitez transférer des reliquats de congés, vous devez utiliser la fonction 'Alimentation / Indemnisation des CET'.<br>";
	echo "<br>";
	
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
		  	       'https://wsgroups.univ-paris1.fr/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
	   </script>
		<?php 			
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		echo "<input type='submit' value='Valider' >";
		echo "</form>";
		echo "<br>";
		echo "<br>";
	}
	
	if ($msg_erreur != "" or $msg_bloquant != "")
	{
		$display = $msg_bloquant . "  " .$msg_erreur;
		echo "<p style='color: red'>$display</p>";
		error_log(basename(__FILE__)." ".$fonctions->stripAccents($display));
	}
	
	//echo "Agent = " . print_r($agent,true) . "<br>";
	
	if (!is_null($agent))
	{
		
		echo "<form name='frm_creercet'  method='post' >";
		$cet = new cet($dbcon);
		$msg_erreur = $cet->load($agent->harpegeid());
		// echo "Message erreur = " . $msg_erreur . "<br>";
		if ($msg_erreur == "") // Le CET existe déja
		{
			$msg_bloquant = "Le CET de l'agent " . $agent->identitecomplete() . " existe déjà... Impossible d'en créer un nouveau."; 
			echo "<p style='color: red'>" . $msg_bloquant . "</p>";
			error_log(basename(__FILE__)." ".$fonctions->stripAccents($msg_bloquant));
		}
		else 
		{
			echo "Date d'ouverture du CET pour l'agent ". $agent->identitecomplete() . " : <input type=text name=date_ouv_cet id=date_ouv_cet size=12 placeholder='JJ/MM/AAAA'>";
			echo "<br>";
			echo "Nombre de jours à créditer au CET de l'agent ". $agent->identitecomplete() . " : <input type=text name=nbr_jours_cet id=nbr_jours_cet size=3 >";
			echo "<br>";
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
			echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
			echo "<input type='hidden' name='agent' value='" . $agent->identitecomplete() ."'>";
//			echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
//			echo "<input type='hidden' name='ajoutcet' value='yes'>";
			echo "<input type='hidden' name='mode' value='" . $mode ."'>";
							
		}
		if ($msg_bloquant == "")
		{
			echo "<br><input type='submit' value='Valider' >";
		}
		else 
		{
			$cet = null;
		}
		echo "</form>";
	}

?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

