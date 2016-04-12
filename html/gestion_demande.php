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
	if (strcasecmp($previoustxt,"yes")==0)
		$previous=1;
	else
		$previous=0;
	
	//echo "Avant le include <br>";
	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml"><br>';

	//echo "POST = "; print_r($_POST); echo "<br>";
	
	$cancelarray = array();
	if (isset($_POST["cancel"]))
		$cancelarray = $_POST["cancel"];
	
	foreach ($cancelarray as $demandeid => $value)
	{
		//echo "demandeid = $demandeid     value = $value <br>";
		if (strcasecmp($value,"yes")==0)
		{
			$motif = "";
			if (isset($_POST["motif"][$demandeid]))
				$motif = $_POST["motif"][$demandeid];
			//echo "Motif = $motif";
			$demande = new demande($dbcon);
			//echo "cleelement = $cleelement  demandeid = $demandeid  <br>";
			$demande->load($demandeid);
			$demande->motifrefus($motif);
			if (strcasecmp($demande->statut(),"v")==0 and $motif == "") {
				$errlog = "Le motif du refus est obligatoire !!!!";
				echo "<p style='color: red'>".$errlog."</p><br/>";
				error_log(basename(__FILE__)." ".$fonctions->stripAccents($errlog));
			}
			else
			{
				$demande->statut("R");
				$msgerreur = "";
				$msgerreur = $demande->store();
				if ($msgerreur != "") {
					$errlog = "Pas de sauvegarde car " . $msgerreur;
					echo "<p style='color: red'>".$errlog."</p><br/>";
					error_log(basename(__FILE__)." ".$fonctions->stripAccents($errlog));
				}
				else
				{
					$pdffilename = $demande->pdf($user->harpegeid());
					$agent = $demande->agent();
					$user->sendmail($agent,"Annulation d'une demande de congés ou d'absence","Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . ".", $pdffilename);
					
					if (strcasecmp($demande->type(),"cet")==0) // Si c'est une demande prise sur un CET => On envoie un mail au gestionnaire RH de CET
					{
						$arrayagentrh = $fonctions->listeprofilrh("1");  // Profil = 1 ==> GESTIONNAIRE RH DE CET
						foreach ($arrayagentrh as $gestrh)
						{
							$corpmail = "Une demande de congés a été " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8')  . " sur le CET de " . $agent->identitecomplete() . ".\n";
							$corpmail = $corpmail . "\n";
							$corpmail = $corpmail . "Détail de la demande :\n";
							$corpmail = $corpmail . "- Date de début : ". $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . "\n";
							$corpmail = $corpmail . "- Date de fin : ". $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "\n";
							$corpmail = $corpmail . "Nombre de jours demandés : " . $demande->nbrejrsdemande() . "\n";
							//$corpmail = $corpmail . "La demande est actuellement en attente de validation.\n";
							$user->sendmail($gestrh,"Changement de statut d'une demande de congés sur CET",$corpmail);
						}
					}
						
					
					//echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
					error_log($fonctions->stripAccents("Sauvegarde la demande " . $demande->id() . " avec le statut " . $fonctions->demandestatutlibelle($demande->statut())));
					echo "<p style='color: green'>Votre demande a bien été annulée!!!</p><br>";
				}
			}
		}
	} 
		
	
	
	$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
	// Si on est dans le mode "previous" alors on dit que la date de fin est l'année courante
	if ($previous == 1)
		$fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
	elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0)
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
			$structfille=$structure->structurefille();
			if (!is_null($structfille))
			{
				foreach ($structfille as $fille)
				{
					if ($fonctions->formatdatedb($fille->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
					{
						$agentliste = null;
						$respfille = $fille->responsable();
						$agentliste[$respfille->nom() . " " . $respfille->prenom() . " " . $respfille->harpegeid()] = $respfille;
						$agentlistefull = array_merge((array)$agentlistefull, (array)$agentliste);
					}
				}
			}
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

