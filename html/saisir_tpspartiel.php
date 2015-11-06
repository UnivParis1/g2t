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
		$agentid = null;
		
	if (isset($_POST["mode"]))
		$mode = $_POST["mode"];
	else
		$mode = null;
	

	$msg_erreur = "";
	
	require ("includes/menu.php");
?>
<!-- 
	<script src="javascripts/jquery-1.8.3.js"></script>
	<script src="javascripts//jquery-ui.js"></script>
 
 	<script>
		$(function()
		{
			$( ".calendrier" ).datepicker();
		});
	</script>
-->
<?php	
	//echo '<html><body class="bodyhtml">';

	// Récupération de l'affectation correspondant à la déclaration TP en cours
	$affectation = null;
	if (isset($_POST["affectationid"]))
	{
		$affectationid = $_POST["affectationid"];
		$affectation = new affectation($dbcon);
		$affectation->load($affectationid);
	}
	else 
		$affectationid = null;
		
	if (isset($_POST["nbredemiTP"]))
		$nbredemiTP = $_POST["nbredemiTP"];
	else 
		$nbredemiTP = null;
		
	if (isset($_POST["nocheckquotite"]))
		$nocheckquotite = $_POST["nocheckquotite"];
	else
		$nocheckquotite = null;

	
	
	$datefausse = false;
	// Récupération de la date de début
	if (isset($_POST["date_debut"]))
	{
		$date_debut = $_POST["date_debut"];
		if (is_null($date_debut) or $date_debut == "" or !$fonctions->verifiedate($date_debut))
		{
			$errlog = "La date de début n'est pas initialisée ou est incorrecte (JJ/MM/AAAA) !!!";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			$datefausse = TRUE;
		}
	}
	else
	{
		$date_debut = null;
		$datefausse = TRUE;
	}
	
	// Récupération de la date de fin
	if (isset($_POST["date_fin"]))
	{
		$date_fin = $_POST["date_fin"];
		if (is_null($date_fin) or $date_fin == "" or !$fonctions->verifiedate($date_fin))
		{
			$errlog = "La date de fin n'est pas initialisée ou est incorrecte (JJ/MM/AAAA) !!! ";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			$datefausse = TRUE;
		}
	}
	else
	{
		$date_fin = null;
		$datefausse = TRUE;
	}
	
	if ($msg_erreur == "" and !$datefausse)
	{
		$datedebutdb = $fonctions->formatdatedb($date_debut);
		$datefindb = $fonctions->formatdatedb($date_fin);
		if ($datedebutdb > $datefindb)
		{
			$errlog = "Il y a une incohérence entre la date de début et la date de fin !!! ";
			$msg_erreur .= $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
		if (is_null($affectation)) {
			$errlog = "Affectation est NULL alors que ca ne devrait pas !!!!";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
		elseif (($datedebutdb < ($fonctions->formatdatedb($affectation->datedebut()))) or ($datefindb > ($fonctions->formatdatedb($affectation->datefin()))))
		{
			$errlog = "Vous ne pouvez pas faire de déclaration en dehors de la période d'affectation";
			$msg_erreur .= $errlog;
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
	}
	
	//echo "_POST => "; print_r ($_POST); echo "<br>";
	
	// On regarde si on a annulé une déclaration de TP
	if (isset($_POST["declaannule"]))
	{
		$tabdeclaannule = $_POST["declaannule"];
		foreach ($tabdeclaannule as $key => $valeur)
		{
			$declaration = new declarationTP($dbcon);
			$declaration->load($key);
			$declaration->statut("r");
			$declaration->store();
		}
	}

	// On verifie qu'il y a autant de case à cocher marquées que de jour de TP a saisir
	if ($nbredemiTP != "" and !$datefausse)
	{
		$nbsemaineimpaire=0;
		$nbsemainepaire=0;
		if (isset($_POST["elmtcheckbox"]))
			$checkboxarray = $_POST["elmtcheckbox"];
		//echo "checkboxarray = " ; print_r($checkboxarray); echo "<br>";
		$tabTP = array_fill(0, 20, "0");
		for ($index=0 ; $index<10 ; $index++)
		{
			if (array_key_exists($index, $checkboxarray))
			{
				$nbsemainepaire++;
				$tabTP[$index] = "1";
			}
		}
		for ($index=10 ; $index<20 ; $index++)
		{
			if (array_key_exists($index, $checkboxarray))
			{
				$nbsemaineimpaire++;
				$tabTP[$index] = "1";
			}
		}
		//echo "nbsemainepaire = $nbsemainepaire   nbsemaineimpaire = $nbsemaineimpaire    nbredemiTP =$nbredemiTP  <br> ";
		// Si la case à cocher nocheckquotite n'est pas cochée on vérifie la répartition de la quotité
		// <=> Si elle est cochée on ne fait pas de test de répartition
		if ($nocheckquotite != 'yes')
		{
			if ($nbsemainepaire != $nbredemiTP or $nbsemaineimpaire != $nbredemiTP)
			{
				$errlog = "Vous devez saisir $nbredemiTP demie(s) journée(s) pour les semaines paires et impaires";
				$msg_erreur .= $errlog;
				error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			}
		}
		else {
			$errlog = "La fonction 'Pas de contrôle de la quotité' est activée... Aucun contrôle n'est réalisé.";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}
	}
	
	//echo "Apres le check nbredemiTP <br>";
	if ($msg_erreur == "" and !$datefausse)
	{
		// On est sur que les données sont ok
		$declarationliste = $affectation->declarationTPliste($date_debut,$date_fin) ;
		// On regarde s'il y a une declaration de TP qui inclue la date de debut !!!
		$msg = "";
		if (!is_null($declarationliste))
		{
			//echo "Il y a potentiellement chevauchement entre des declarations !!!! <br>";
			foreach ($declarationliste as $key => $declaration)
			{
				if (strcasecmp($declaration->statut(),"r")!=0)
				{
					// Si la date de fin de l'ancienne est après la date de debut de la nouvelle 
					$msg = "";
					// Nouvelle    [--------------]
					// Ancienne            [------------------]
					// ===>        [--------------][----------]
					if (($fonctions->formatdatedb($date_fin) >= $fonctions->formatdatedb($declaration->datedebut()))
						and ($fonctions->formatdatedb($date_debut) <= $fonctions->formatdatedb($declaration->datedebut())))
					{
						//echo "----- CAS 1 ------<br>";
						//echo "formatdb fin = " . $fonctions->formatdatedb($date_fin) . "<br>";
						$timestamp = strtotime($fonctions->formatdatedb($date_fin));
						//echo "Avant nvlle date <br>";
						$nvlledatedebut = date("Ymd", strtotime("+1days", $timestamp ));  // On passe au jour d'avant (donc la veille)
						//echo "nvlledatedebut = $nvlledatedebut <br>";
						$declaration->datedebut($fonctions->formatdate($nvlledatedebut));
						if (strcasecmp($declaration->statut(),"r")!=0)
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
							$msg_erreur .= $errlog."<br/>";
							error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
							$msg_erreur = $msg_erreur . $msg;
						}
					}
					$msg = "";
					// Nouvelle                [--------------]
					// Ancienne    [------------------]
					// ===>        [----------][--------------]
					if (($fonctions->formatdatedb($date_debut) <= $fonctions->formatdatedb($declaration->datefin()))
					   and ($fonctions->formatdatedb($date_fin) >= $fonctions->formatdatedb($declaration->datefin())))
					{
						//echo "----- CAS 2 ------<br>";
						//echo "formatdb debut = " . $fonctions->formatdatedb($date_debut) . "<br>";
						$timestamp = strtotime($fonctions->formatdatedb($date_debut));
						//echo "Avant nvlle date <br>";
						$nvlledatefin = date("Ymd", strtotime("-1days", $timestamp ));  // On passe au jour d'après (donc le lendemain)
						//echo "nvlledatefin = $nvlledatefin <br>";
						$declaration->datefin($fonctions->formatdate($nvlledatefin));
						if (strcasecmp($declaration->statut(),"r")!=0)
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
							$msg_erreur .= $errlog."<br/>";
							error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
							$msg_erreur = $msg_erreur . $msg;
						}
					}
					$msg = "";
					// Nouvelle              [----------]
					// Ancienne    [--------------------------]
					// ===>        [--------][----------]
					if (($fonctions->formatdatedb($date_debut) >= $fonctions->formatdatedb($declaration->datedebut()))
						and ($fonctions->formatdatedb($date_fin) <= $fonctions->formatdatedb($declaration->datefin())))
					{
						//echo "----- CAS 3 ------<br>";
						//echo "formatdb debut = " . $fonctions->formatdatedb($date_debut) . "<br>";
						$timestamp = strtotime($fonctions->formatdatedb($date_debut));
						//echo "Avant nvlle date <br>";
						$nvlledatefin = date("Ymd", strtotime("-1days", $timestamp ));  // On passe au jour d'après (donc le lendemain)
						//echo "nvlledatefin = $nvlledatefin <br>";
						$declaration->datefin($fonctions->formatdate($nvlledatefin));
						if (strcasecmp($declaration->statut(),"r")!=0)
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$errlog = "Il y a chevauchement entre la nouvelle déclaration et une ancienne déclaration !!!!";
							$msg_erreur .= $errlog."<br/>";
							error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
							$msg_erreur = $msg_erreur . $msg;
						}
					}
					$msg = "";
					// Ancienne    ]-------------[
					// => Annulation
					if ($fonctions->formatdatedb($declaration->datedebut()) > $fonctions->formatdatedb($declaration->datefin()))
					{
						//echo "----- CAS 4 ------<br>";
						//echo "La date de début de la declaration est apres la date de fin !!!! <br>";
						$declaration->statut("r");
						$msg = $declaration->store();
						if ($msg != "")
							$msg_erreur = $msg_erreur . $msg;
					}
				}
				unset($declaration);
			}
		}
		unset($declarationliste);

/*
 		$declarationliste = $affectation->declarationTPliste($date_fin,$date_fin) ;
		// On regarde s'il y a une declaration de TP qui inclue la date de fin !!!
		$msg = "";
		if (!is_null($declarationliste))
		{
			//echo "Il y a chevauchement entre la nouvelle declaration et une ancienne declaration !!!! <br>";
			$declaration = reset($declarationliste);
			//echo "formatdb = " . $fonctions->formatdatedb($date_fin) . "<br>";
			$timestamp = strtotime($fonctions->formatdatedb($date_fin));
			//echo "Avant nvlle date <br>";
			$nvlledatedebut = date("Ymd", strtotime("+1days", $timestamp ));  // On passe au jour suivant (donc le lendemain)
			//echo "nvlledatedebut = $nvlledatedebut <br>";
			$declaration->datedebut($fonctions->formatdate($nvlledatedebut));
			//echo "Nvlle date de fin dans l'objet = " .$autodecla->datedebut() . "<br>";
			if ($declaration->statut() != "r")
			{
				$msg_erreur = $msg_erreur . "Il y a chevauchement entre la nouvelle declaration et une ancienne declaration !!!! <br>";
				$msg = $declaration->store();
			}
			//echo "Apres le store de l'ID " . $autodecla->id() .  "... <br>";
			if ($msg != "")
				$msg_erreur = $msg_erreur . $msg;
		}
		unset($declaration);
		unset($declarationliste);
*/
		
		// On va enregistrer la nouvelle déclaration de TP

		//echo "Avant le new... <br>";
		$declaration = new declarationTP($dbcon);
		$declaration->datedebut($date_debut);
		$declaration->datefin($date_fin);
		//echo "Avant le initTP <br>";
		$declaration->tabtpspartiel(implode($tabTP));
		$declaration->affectationid($affectationid);
		$declaration->statut("a");
		//echo "Avant le Store <br>";
		$msg = $declaration->store();
		if ($msg != "")
			$msg_erreur = $msg_erreur . $msg;
		else {
			$errlog = "La déclaration de temps partiel est bien enregistrée.";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
			echo "<font color='green'>".$errlog."</font><br/><br/>";
		}

//		$timestamp = strtotime($fonctions->formatdatedb($date_debut));
//		$nvlledatefin = date("d/m/Y", strtotime("+1year", $timestamp ));  // On ajoute un an pour chercher les éventuelles demandes faites après la fin de période
//		$demandeliste = $agent->demandesliste($date_debut , $nvlledatefin );
//		if (count($demandeliste) != 0)
//		{
//			$afficheheader = TRUE;
//			foreach ($demandeliste as $demandekey => $demande)
//			{
//				if (($demande->statut() != "r") and ($fonctions->formatdatedb($demande->datedebut())>=($fonctions->anneeref() . $fonctions->debutperiode())))
//				{
//					if ($afficheheader)
//					{
//						//echo "Il y a des demandes de congés qu'il faudra supprimer !!!!! <br>";
//						$msg_erreur = $msg_erreur . "Il y a des demandes de congés qu'il faudra supprimer !!!!! <br>";
//						$afficheheader = FALSE;
//					}	
//					$msg_erreur = $msg_erreur . " La demande du " . $demande->date_demande() . " pour la période du " . $demande->datedebut() . " au "  . $demande->datefin()   . " <br>";
//					$demande->statut("r");
//					$demande->motifrefus("Changement d'autodéclaration");
//					//echo "Avant le store...<br>";
//					//print_r($demande); echo "<br>";
//					$demande->store();
//					//echo "Apres le store...<br>";
//
//					// DANS LE CAS DU MODE RESPONSABLE 
//					// => 1 mail à l'agent  => expéditeur = $user et destinataire = $agent
//					// => 1 mail au responsable => expéditeur = $user et destinataire = $user
//					if ($mode=="resp")
//					{
//						//echo "Avant le PDF... <br>";
//						$pdffilename = $demande->pdf($user->harpegeid());
//						// Mail à l'agent
//						//echo "Avant le mail à l'agent... <br>";
//						$user->sendmail($agent,"Annulation d'une demande (Changement d'autodéclaration)","Votre autodéclaration a été modifiée par " . $user->civilite() . " " . $user->nom() .  " "  . $user->prenom() . "!! Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						// Mail au responsable (donc c'est un automail)
//						//echo "Avant le mail AU RESP... <br>";
//						$user->sendmail($user,"Annulation d'une demande (Changement d'autodéclaration)","Vous venez de changer l'autodéclaration de l'agent "  . $agent->civilite() . " " . $agent->nom() .  " "  . $agent->prenom() . " !! Sa demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						//echo "Apres mail resp...<br>";
//					}
//					// DANS LE CAS DU MODE AGENT
//					// => 1 mail à l'agent  => expéditeur = $agent et destinataire = $agent
//					// => 1 mail au responsable => expéditeur = $agent et destinataire = $resp récup a partir de la strcture
//					else
//					{
//						$pdffilename = $demande->pdf($agent->harpegeid());
//						$agent->sendmail($agent,"Annulation d'une demande (Changement d'autodéclaration)","Vous avez changé d'autodéclaration !! Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						$resp = $agent->structure()->responsable();
//						$agent->sendmail($resp,"Annulation d'une demande (Changement d'autodéclaration)","L'agent "  . $agent->civilite() . " " . $agent->nom() .  " "  . $agent->prenom() . " a changé d'autodéclaration !! Sa demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//					}
//					
//				}
//			}
//		}

	}
	
	if ($agentid == "")
	{
		echo "<form name='autodeclarationforagent'  method='post' >";

		$structureliste = $user->structrespliste();
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
		
			
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='mode' value='" . $mode ."'>";
		echo "<input type='submit' value='Valider' >";
		echo "</form>";
		
	}
	else
	{
		//echo "Avant le new agent <br>";
		$agent = new agent($dbcon);
		//echo "Avant le load...<br>";
		$agent->load($agentid);
		//echo "Avant le dossieractif<br>";
		//$dossier = $agent->dossieractif();
		//echo "apres le dossier actif <br>";
		$debut_interval = $fonctions->anneeref() . $fonctions->debutperiode();
		$fin_interval = ($fonctions->anneeref()+1) . $fonctions->finperiode();
		$affectationliste = $agent->affectationliste($debut_interval,$fin_interval);
		$affectation = new affectation($dbcon);
		$tppossible = false;
		if (is_array($affectationliste))
		{
			foreach ($affectationliste as $key => $affectation)
			{
				//echo "juste dans le for .... Quotite = " . $affectation->quotite() . "<br>";
				if ($affectation->quotite() != "100%")
				{
					//echo "La quotité != 100% ==> Je peux poser un tps partiel <br>";
					$tppossible = true;
					break;
				}
			}
		}
		if (!$tppossible)
		{
			$errlog = "Vous n'avez aucune affectation à temps partiel entre le " . $fonctions->formatdate($debut_interval) . " et le " . $fonctions->formatdate($fin_interval);
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." uid : ".$agentid." : ".$fonctions->stripAccents($errlog));
		}	
		else
		{
//			echo "<b><br>C'est moche => Présentation à revoir !!! </b><br><br>";


			echo "<br/>";
			if ($msg_erreur <> "")  {
				echo "<P style='color: red'>" . $msg_erreur . " </P>";
				error_log(basename(__FILE__)." uid : ".$agentid." : ".$msg_erreur);
			}
			echo "<br/>";
			
			// $affectationliste = liste des affectations de l'agent pour la période
			foreach ($affectationliste as $key => $affectation)
			{
				if ($affectation->quotite() != "100%")
				{
					echo "<form name='frm_saisir_tpspartiel_" . $affectation->affectationid() . "' method='post' >";
					echo "<input type='hidden' name='affectationid' value='" . $affectation->affectationid() ."'>";
					echo $affectation->html(true,false,$mode);
	
					echo "<br>Nouvelle déclaration de temps partiel<br>";
					echo "<table>";
					echo "<tr>";
					echo"<td>Date de début de la période :</td>";
					echo "<td width=1px><input class='calendrier' type=text name=date_debut id=date_debut_" . $affectation->affectationid()  . " size=10 ></td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td>Date de fin de la période :</td>";
					echo "<td width=1px><input class='calendrier' type=text name=date_fin id=date_fin_" . $affectation->affectationid()  . " size=10 ></td>";
					echo "</tr>";
					echo "</table>";
					$nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
					//echo "nbredemiTP = " . $nbredemiTP . "<br>";
					echo "<br>Vous devez poser des jours de temps partiel (Nombre de demie-journées par semaine = $nbredemiTP )<br>";
					
					echo "<div id='planning'>";
					echo "<table class='tableau'>";
					$declaration = new declarationTP($dbcon);
					$declaration->tabtpspartiel(str_repeat("0", 20));
					echo $declaration->tabtpspartielhtml(true);
					echo "</table>";
					echo "</div>";
	
					echo "<br>";
					if (strcasecmp($mode,"resp")==0)
					{
						echo "<br>";	
						echo "<input type='checkbox' name='nocheckquotite' value='yes'> Ne pas vérifier la répartition des jours de temps partiel... <br>";
						echo "Cette fonction permet, par exemple, de saisir 3 jours de TP une semaine et 2 jours la semaine suivante pour une personne à 50% <br>";
						echo "<font color='red'><b>ATTENTION : </font></b>Cette fonction est à utiliser avec prudence... Il convient de vérifier manuellement que la répartion est correcte.<br>";
					}
					echo "<input type='hidden' name='nbredemiTP' value='" . $nbredemiTP ."'>";
					echo "<input type='hidden' name='userid' value='" . $userid ."'>";
					echo "<input type='hidden' name='agentid' value='" . $agentid ."'>";
					echo "<input type='hidden' name='mode' value='" . $mode ."'>";
					echo "<input type='submit' value='Valider' />";
					
					echo "</form>";
					echo "<br>";
				}
			}
		}
	}

?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
 </body></html>

