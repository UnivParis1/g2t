<?php

	require_once('./CAS/CAS.php');
	require_once("./class/fonctions.php");
	require_once('./includes/dbconnection.php');
 
	$fonctions = new fonctions($dbcon);
	
	// Parametres pour connexion CAS
	$CAS_SERVER=$fonctions->liredbconstante("CASSERVER");
	$CAS_PORT=443;
	$CAS_PATH=$fonctions->liredbconstante("CASPATH");
	phpCAS::client(CAS_VERSION_2_0,$CAS_SERVER,$CAS_PORT,$CAS_PATH);
	//phpCAS::setDebug("D:\Apache\logs\phpcas.log");
	//      phpCAS::setFixedServiceURL("http://mod11.parc.univ-paris1.fr/ReturnURL.html");
	phpCAS::setNoCasServerValidation();
	// Recuperation de l'uid
	phpCAS::forceAuthentication();
	 
	$uid=phpCAS::getUser();
	//echo "UID de l'agent est : " . $uid . "<br>";
 
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
	require_once("./class/fpdf.php");
	require_once("./class/cet.php");
	require_once("./class/affectation.php");
	require_once("./class/complement.php");
		

	$user = new agent($dbcon);
	$user->load($userid);
	
	// 	// Initialisation de l'utilisateur
	// 	$agentid = $_POST["agentid"];
	// 	$agent = new agent($dbcon);
	// 	if (is_null($agentid) or $agentid == "")
		// 	{
		// 		//echo "L'agent n'est pas passé en paramètre.... Récupération de l'agent à partir du ticket CAS <br>";
		// 		$LDAP_SERVER=$fonctions->liredbconstante("LDAPSERVER");
		// 		$LDAP_BIND_LOGIN=$fonctions->liredbconstante("LDAPLOGIN");
		// 		$LDAP_BIND_PASS=$fonctions->liredbconstante("LDAPPASSWD");
		// 		$LDAP_SEARCH_BASE=$fonctions->liredbconstante("LDAPSEARCHBASE");
		// 		$LDAP_CODE_AGENT_ATTR=$fonctions->liredbconstante("LDAPATTRIBUTE");
		// 		$con_ldap=ldap_connect($LDAP_SERVER);
		// 		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		// 		$r=ldap_bind($con_ldap, $LDAP_BIND_LOGIN,$LDAP_BIND_PASS);
		// 		$filtre="(uid=$uid)";
		// 		$dn=$LDAP_SEARCH_BASE;
		// 		$restriction=array("$LDAP_CODE_AGENT_ATTR");
		// 		$sr=ldap_search ($con_ldap,$dn,$filtre,$restriction);
		// 		$info=ldap_get_entries($con_ldap,$sr);
		// 		//echo "Le numéro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
		// 		$agent->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
		// 	}
		// 	else
			// 		$agent->load($agentid);
	
	if (isset($_POST["agentid"]))
		$agentid = $_POST["agentid"];
	else
		$agentid = null;
	if (is_null($agentid) or $agentid == "")
		$noagentset = TRUE;
	else
	{
		//echo "AGENTID = " . $agentid . "<br>";
		$agent = new agent($dbcon);
		$agent->load($agentid);
		$noagentset = FALSE;
	}
	//echo "avant chargement respo <br>";
	$responsableid = null;
	$noresponsableset = TRUE;
	if (isset($_POST["responsableid"]))
	{
		//echo "responsableid = " . $responsableid . "<br>";
		$responsableid = $_POST["responsableid"];
		if (!is_null($responsableid) and $responsableid != "")
		{
			//echo "Je load le responsable...<br>";
			$responsable = new agent($dbcon);
			$responsable->load($responsableid);
			$noresponsableset = FALSE;
		}
	}
	
	if (isset($_POST["previous"]))
		$previoustxt = $_POST["previous"];
	else 
		$previoustxt = null;
	if ($previoustxt == "yes")
		$previous=1;
	else
		$previous=0;
	
	//echo "Avant le include <br>";
	require ("includes/menu.php");
	echo '<html><body class="bodyhtml">';

	//print_r($_POST); echo "<br>";
	
	foreach ($_POST as $key => $value)
	{
		//echo "key = $key     value = $value <br>";
		//echo "Substr => "  . substr($key, 0, strlen("statut_")) . "<br>";
		$position = strpos($key, "_cancel_");
		if ($position !==FALSE)
		{
			if ($value == "cancel")
			{
				$cleelement = substr($key,0,$position);
				$position = $position + strlen("_cancel_");
				$demandeid = substr($key,$position);    //str_replace("statut_", "", $key);
				if (isset($_POST[$cleelement . "_motif_" . $demandeid]))
					$motif = $_POST[$cleelement . "_motif_" . $demandeid];
				else
					$motif = "";
				$demande = new demande($dbcon);
				//echo "cleelement = $cleelement  demandeid = $demandeid  <br>";
				$demande->load($demandeid);
				$demande->motifrefus($motif);
				if ($demande->statut() == "v" and $motif == "")
					echo "<p style='color: red'>Le motif du refus est obligatoire !!!! </p><br>";
				else
				{
					$demande->statut("r");
					$msgerreur = $demande->store();
					if ($msgerreur != "")
						echo "<p style='color: red'>Pas de sauvegarde car " . $msgerreur . "</p><br>";
					else
					{
						$pdffilename = $demande->pdf($user->harpegeid());
						$agent = $demande->agent();
						$user->sendmail($agent,"Annulation d'une demande","Le statut de votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $this->fonctions->demandestatutlibelle($demande->statut()) . ".", $pdffilename);
						//echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
						error_log("Sauvegarde la demande " . $demande->id() . " avec le statut " . $this->fonctions->demandestatutlibelle($demande->statut()));
						echo "<p style='color: green'>Votre demande a bien été annulée!!!</p><br>";
					}
				}
			}
		}
	} 
		
	
	
	$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
	// Si on est dans le mode "previous" alors on dit que la date de fin est l'année courante
	if ($previous == 1)
		$fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
	elseif ($fonctions->liredbconstante("LIMITE_CONGE_PERIODE") == "n")
		$fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
	else
		$fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
		
	
	//echo "Debut = $debut     fin = $fin <br>";
	//echo "structure->id() = " . $structure->id() . "<br>";
	
	echo "<form name='frm_gest_demande'  method='post' >";
	if ($noresponsableset)
	{
		// => C'est un agent qui veut gérer ses demandes
		//echo "Pas de responsable.... <br>"; 
		$htmltext = $agent->demandeslistehtmlpourgestion($debut , $fin, $user->harpegeid(),"agent",null);
		if ($htmltext != "")
			echo $htmltext;
		else
			echo "<center>L'agent " . $agent->civilite() . "  " . $agent->nom() . " " . $agent->prenom() . " n'a aucun congé à annuler pour la période de référence en cours.</center><br>";
		echo "<input type='hidden' name='agentid' value='" . $agentid ."'>";
	}
	elseif ($noagentset)
	{
		// => On est en mode "responsable" mais aucun agent n'est sélectionné
		//echo "Avant le chargement structure responsable <br>";
		$structureliste = $responsable->structrespliste();
		//echo "Liste de structure = "; print_r($structureliste); echo "<br>";
		$agentlistefull = array();
		foreach ($structureliste as $structure)
		{
			$agentliste=$structure->agentlist(date("d/m/Y"), date("d/m/Y"));
			//echo "Liste de agents = "; print_r($agentliste); echo "<br>";
			$agentlistefull = array_merge((array)$agentlistefull, (array)$agentliste);
			//echo "fin du select <br>";
		}
		ksort($agentlistefull);
		echo "<SELECT name='agentid'>";
		foreach ($agentlistefull as $keyagent => $membre)
		{
			echo "<OPTION value='" . $membre->harpegeid() .  "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom()  . "</OPTION>";
		}
		echo "</SELECT>";
		echo "<br>";
	}
	else
	{
		// => On est en mode "reponsable" et un agent est sélectionné 
		//echo "Avant le mode responsable <br>";
		$htmltext = $agent->demandeslistehtmlpourgestion($debut , $fin, $user->harpegeid(),"resp",null);
		if ($htmltext != "")
			echo $htmltext;
		else
			echo "<center>L'agent " . $agent->civilite() . "  " . $agent->nom() . " " . $agent->prenom() . " n'a aucun congé à annuler pour la période de référence en cours.</center><br>";
		echo "<input type='hidden' name='agentid' value='" . $agentid ."'>";
	}

	if ($responsableid != "")
		echo "<input type='hidden' name='responsableid' value='" . $responsableid . "'>";
	echo "<input type='hidden' name='userid' value='" . $userid ."'>";
	echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
	echo "<input type='submit' value='Valider' />";
	
	echo "</form>"
?>

<br>
<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

