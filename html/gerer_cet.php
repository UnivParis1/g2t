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
		$agent = new agent($dbcon);
		$agent->load($agentid);
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
		$nbr_jours_cet = $_POST["nbr_jours_cet"];
	
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
		if ($nbr_jours_cet <= 0 or $nbr_jours_cet == "") {
			$errlog = "Vous n'avez pas saisi le nombre de jours à ajouter ou il est inférieur ou égal à 0 ";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
		elseif (intval($nbr_jours_cet) != $nbr_jours_cet) {
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
				else {
					$errlog = "Le solde de jour de congé annuel est insuffisant : Demande " . $nbr_jours_cet . "   Disponible : " . ($soldeannuel->solde());
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
		else 
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
		echo "<input type='submit' value='Valider' >";
		echo "</form>";
		
	}
	else
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
		$msg_erreur = $msg_bloquant . $msg_erreur . $cet->load($agentid);
		
		if ($msg_erreur == "")
		{
			// Pas d'erreur lors du chargement du CET
			echo "Le CET de l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " est actuellement : <br>";
			echo "Date du début du CET : ". $cet->datedebut() . "<br>";
			echo "Sur l'année " .  ($fonctions->anneeref()-1) . "/" . $fonctions->anneeref()  . ", il a cumulé " . ($cet->cumulannuel($fonctions->anneeref())) . " jour(s) <br>";
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
			echo "<input type='submit' value='Valider' >";
			echo "</form>";
			echo "</span>";
		}
		
		
		if ($msg_erreur != "")
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
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
		echo "<input type='hidden' name='nbrejoursdispo' value='" . $nbrejoursdispo . "'>";
		echo "<input type='hidden' name='ajoutcet' value='yes'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		if ($msg_bloquant == "")
			echo "<input type='submit' value='Valider' >";
		echo "</form>";
		echo "</span>";
		
		if (!is_null($cet))
		{
			// Seuls les jours au delà de 20 jours de CET peuvent être indemnisés ou ajoutés à la RAFP
			if ((($cet->cumultotal()-$cet->jrspris())) > 20)
			{
				echo "<br>";
				echo "<span style='border:solid 1px black; background:lightsteelblue; width:600px; display:block;'>";
				echo "<form name='frm_retraitcet'  method='post' >";
				echo "Nombre de jours à retirer au CET : <input type=text name=nbr_jours_cet id=nbr_jours_cet size=3 >";
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
					echo "<input type='submit' value='Valider' >";
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

