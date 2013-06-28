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

	if (isset($_POST["previous"]))
		$previoustxt = $_POST["previous"];
	else
		$previoustxt = null;
	if ($previoustxt == 'yes')
		$previous = 1;
	else
		$previous = 0;
	
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
	
	require ("includes/menu.php");
	echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	// Récupération du mode => resp ou gestion
	$mode = $_POST["mode"];
	if (is_null($mode) or $mode == "")
	{
		$mode="resp";
		echo "Le mode n'est pas précisé ==> on met le mode responsable <br>";
	}
	//echo "_POST = "; print_r($_POST); echo "<br>";
	$statutliste = null;
	$motifliste = null;
	if (isset($_POST['statut']))
	{
		$statutliste = $_POST['statut'];
	}
	if (isset($_POST['motif']))
	{
		$motifliste = $_POST['motif'];
	}
	
	if (is_array($statutliste) and is_array($motifliste))
	{
		foreach ($statutliste as $demandeid => $statut)
		{
			if (strcasecmp($statut,"a")!=0)
			{
				$motif = $motifliste["$demandeid"];
				$demande = new demande($dbcon);
				//echo "cleelement = $cleelement  demandeid = $demandeid  <br>";
				$demande->load($demandeid);
				if ($statut == 'r')
					$demande->motifrefus($motif);
				$demande->statut($statut);
				if (strcasecmp($statut,"r")==0 and $motif == "")
					echo "<p style='color: red'>Le motif du refus est obligatoire !!!! </p><br>";
				else
				{
					$msgerreur = "";
					$msgerreur = $demande->store();
					if ($msgerreur != "")
						echo "<p style='color: red'>Pas de sauvegarde car " . $msgerreur . "</p><br>";
					else
					{
						$pdffilename = $demande->pdf($user->harpegeid());
						$agent = $demande->agent();
						$user->sendmail($agent,"Validation d'une demande de congés ou d'absence","Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . strtolower($fonctions->demandestatutlibelle($demande->statut())) . ".", $pdffilename);
						//echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
						error_log("Sauvegarde la demande " . $demande->id() . " avec le statut " . $fonctions->demandestatutlibelle($demande->statut()));
					}
				}
			}
		} 
	}
	
	if ($user->estresponsable() and (strcasecmp($mode,"resp")==0))
	{
		$listestruct = $user->structrespliste();
		//print_r($listestruct); echo "<br>";
		echo "<form name='frm_validation_conge'  method='post' >";
		foreach ($listestruct as $key => $structure)
		{
			$aumoinsunedemande = False;
			$cleelement = $structure->id();
			echo "<center><p>Tableau pour les agents de " .  $structure->nomlong() . " (" . $structure->nomcourt() .")</p></center>";
			echo "<form name='frm_validation_conge'  method='post' >";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"),'n');
			foreach ($agentliste as $membrekey => $membre)
			{
				//echo "boucle => " .$membre->nom() . "<br>";
				$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
				$fin = $fonctions->formatdate(($fonctions->anneeref()+1-$previous) . $fonctions->finperiode());
				//echo "Debut = $debut     fin = $fin <br>";
				//echo "structure->id() = " . $structure->id() . "<br>";
				//echo "Membre = " . $membre->nom() . "<br>";
				
				//echo $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->id(),null, $cleelement);
				$htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structure->id(), $cleelement);
				if ($htmltodisplay != "")
				{
					echo $htmltodisplay;
					echo "<br>";
					$aumoinsunedemande = TRUE;
				}	
			}
			$sousstructureliste=$structure->structurefille();
			if (is_array($sousstructureliste))
			{
				foreach ($sousstructureliste as $ssstructkey => $structfille)
				{
					$responsable = $structfille->responsable();
					$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
					$fin = $fonctions->formatdate(($fonctions->anneeref()+1-$previous) . $fonctions->finperiode());
					//echo $responsable->demandeslistehtmlpourvalidation($debut , $fin, $user->id(),null, $cleelement);
					$htmltodisplay =  $responsable->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structfille->id(), $cleelement);
					if ($htmltodisplay != "")
					{
						echo $htmltodisplay;
						echo "<br>";
						$aumoinsunedemande = TRUE;
					}	
									
				}
			}	
			if (!$aumoinsunedemande)
			{
				echo "Aucune demande en attente pour cette structure...<br>";
			}
		}
		echo "<input type='hidden' name='mode' value='" . $mode .   "' />";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid()  .   "' />";
		echo "<input type='hidden' name='previous' value='" . $previoustxt  .   "' />";
		echo "<br>";
		echo "<input type='submit' value='Valider' />";
		echo "</form>";
	}
	elseif (!$user->estresponsable() and (strcasecmp($mode,"resp")==0))
	{
		echo "Vous n'êtes pas responsable, vous ne pouvez pas valdier les demandes de congés/d'absence <br>";
	}

	if ($user->estgestionnaire() and (strcasecmp($mode,"gestion")==0))
	{
		echo "<form name='frm_validation_conge'  method='post' >";
		$listestruct = $user->structgestliste();
		foreach ($listestruct as $key => $structure)
		{
			$aumoinsunedemande = FALSE;
			$cleelement = $structure->id();
			echo "<center><p>Tableau pour les agents de " .  $structure->nomlong() . " (" . $structure->nomcourt() .")</p></center>";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"),'n');
			foreach ($agentliste as $membrekey => $membre)
			{
				//echo "boucle => " .$membre->nom() . "<br>";
				$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
				// Si on est en mode "previous" alors on considère que la fin est l'année courante
				if ($previous == 1)
					$fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
				// Si on ne limite pas les congès a la date de fin de la période, il faut prendre plus large que la fin de période
				// On prend la fin de période + 1 an (soit 2 ans par rapport a l'année de référence)
				elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0)
					$fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
				else
					$fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
				//echo "Debut = $debut     fin = $fin <br>";
				//echo "structure->id() = " . $structure->id() . "<br>";
				//echo "Membre = " . $membre->nom() . "<br>";
				
				//echo $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structure->id(), $cleelement);
				// -------------------------------------------------------------
				// Dans le mode GESTIONNAIRE on ne passe pas le code du gestionnaire ($user->harpegeid()) car il doit pouvoir valider ses propres congés ??
				//$htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structure->id(), $cleelement);
				$htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut , $fin, null,$structure->id(), $cleelement);
				// -------------------------------------------------------------
				//echo "htmltodisplay = $htmltodisplay <br>";
				if ($htmltodisplay != "")
				{
					echo $htmltodisplay;
					echo "<br>";
					$aumoinsunedemande = TRUE;
				}	
			}
			
			// A Voir si on affiche les structures filles lorsque l'on est Gestionnaire
/*
			$sousstructureliste=$structure->structurefille();
			if (is_array($sousstructureliste))
			{
				//echo "Je suis dans la boucle des sousstructures <br>";
				foreach ($sousstructureliste as $ssstructkey => $structfille)
				{
					//echo "Dans le echo des structFille... <br>";
					$agentliste = $structfille->agentlist(date("d/m/Y"),date("d/m/Y"),'n');
					foreach ($agentliste as $membrekey => $membre)
					{
						$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
	
						// Si on est en mode "previous" alors on considère que la fin est l'année courante
						if ($previous == 1)
							$fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
						// Si on ne limite pas les congès a la date de fin de la période, il faut prendre plus large que la fin de période
						// On prend la fin de période + 1 an (soit 2 ans par rapport a l'année de référence)
						elseif ($fonctions->liredbconstante("LIMITE_CONGE_PERIODE") == "n")
							$fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
						else
							$fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
						
						//echo $responsable->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structfille->id(), $cleelement);
						$htmltodisplay =  $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structfille->id(), $cleelement);
						if ($htmltodisplay != "")
						{
							echo $htmltodisplay;
							echo "<br>";
							$aumoinsunedemande = TRUE;
						}	
					}			
				}
			}
*/	
			if (!$aumoinsunedemande)
			{
				echo "Aucune demande en attente pour cette structure...<br>";
			}
			
		}
		echo "<input type='hidden' name='mode' value='" . $mode .   "' />";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid()  .   "' />";
		echo "<input type='hidden' name='previous' value='" . $previoustxt  .   "' />";
		echo "<br>";
		echo "<input type='submit' value='Valider' />";
		echo "</form>";
	}
	elseif (!$user->estgestionnaire() and (strcasecmp($mode,"gestion")==0))
	{
		echo "Vous n'êtes pas gestionnaire, vous ne pouvez pas valdier les demandes de congés/d'absence <br>";
	}
	
	
	
?>
<br>
<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>