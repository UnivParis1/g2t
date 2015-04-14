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

	if (isset($_POST["previous"]))
		$previoustxt = $_POST["previous"];
	else
		$previoustxt = null;
	if ($previoustxt == 'yes')
		$previous = 1;
	else
		$previous = 0;
	
	
	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
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
				if (strcasecmp($statut,"r")==0 and $motif == "") {
					$errlog = "Le motif du refus est obligatoire !!!!";
					echo "<p style='color: red'>".$errlog."</p><br/>";
					error_log(basename(__FILE__)." ".$fonctions->stripAccents($errlog));
				}
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

	echo "Changez l'état de chacune des demandes en \"Validée\" ou \"Refusée\", puis enregistrez les modifications en cliquant sur le bouton \"Valider\" <br>Laissez l'état des demandes à \"En attente\" si vous ne souhaitez pas faire de modification.<br><U>Attention :</U> La saisie du motif est obligatoire dans le cas d'un refus.<br><br>";
	
	
	if ($user->estresponsable() and (strcasecmp($mode,"resp")==0))
	{
		$listestruct = $user->structrespliste();
		//print_r($listestruct); echo "<br>";
		echo "<form name='frm_validation_conge'  method='post' >";
		echo "<input type='submit' value='Valider' />";
		foreach ($listestruct as $key => $structure)
		{
			$aumoinsunedemande = False;
			$cleelement = $structure->id();
			echo "<center><p>Tableau pour les agents de " .  $structure->nomlong() . " (" . $structure->nomcourt() .")</p></center>";
			echo "<form name='frm_validation_conge'  method='post' >";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"),'n');
			if (is_array($agentliste))
			{
				foreach ($agentliste as $membrekey => $membre)
				{
					//echo "boucle => " .$membre->nom() . "<br>";
					$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
					$fin = $fonctions->formatdate(($fonctions->anneeref()+1-$previous) . $fonctions->finperiode());
	
					// Si on est dans l'année courante et si on ne limite pas les conges a la periode =>
					//		On doit afficher les congés qui sont dans la période suivante
					if ((strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0) and ($previous==0))
						$fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
					
	
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
		echo "<input type='submit' value='Valider' />";
		$listestruct = $user->structgestliste();
		foreach ($listestruct as $key => $structure)
		{
			$aumoinsunedemande = FALSE;
			$cleelement = $structure->id();
			echo "<center><p>Tableau pour les agents de " .  $structure->nomlong() . " (" . $structure->nomcourt() .")</p></center>";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"),'n');
			if (is_array($agentliste))
			{
				foreach ($agentliste as $membrekey => $membre)
				{
					//echo "boucle => " .$membre->nom() . "<br>";
					$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
					// Si on est en mode "previous" alors on considère que la fin est l'année courante
					if ($previous == 1)
						$fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
					// Si on ne limite pas les congés a la date de fin de la période, il faut prendre plus large que la fin de période
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
						// Si on ne limite pas les congés a la date de fin de la période, il faut prendre plus large que la fin de période
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
		
		$listestruct = $user->structgestcongeliste();
		//echo "<br>listestruct = "; print_r((array) $listestruct) ; echo "<br>";
		if (!is_null($listestruct))
		{
			foreach ($listestruct as $key => $structure)
			{
				$aumoinsunedemande = FALSE;
				$cleelement = $structure->id();
				echo "<center><p>Tableau pour le responsable de " .  $structure->nomlong() . " (" . $structure->nomcourt() .")</p></center>";
	
				$responsable = $structure->responsable();
				$debut = $fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode());
				$fin = $fonctions->formatdate(($fonctions->anneeref()+1-$previous) . $fonctions->finperiode());
				//echo $responsable->demandeslistehtmlpourvalidation($debut , $fin, $user->id(),null, $cleelement);
				$htmltodisplay =  $responsable->demandeslistehtmlpourvalidation($debut , $fin, $user->harpegeid(),$structure->id(), $cleelement);
				if ($htmltodisplay != "")
				{
					echo $htmltodisplay;
					echo "<br>";
					$aumoinsunedemande = TRUE;
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