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
	<script src="javascripts/jquery-1.8.3.js"></script>
	<script src="javascripts//jquery-ui.js"></script>
	<script>
		$(function()
		{
			$( ".calendrier" ).datepicker();
		});
	</script>
<?php	
	echo '<html><body class="bodyhtml">';

	// R�cup�ration de l'affectation correspondant � la d�claration TP en cours
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
	// R�cup�ration de la date de d�but
	if (isset($_POST["date_debut"]))
	{
		$date_debut = $_POST["date_debut"];
		if (is_null($date_debut) or $date_debut == "" or !$fonctions->verifiedate($date_debut))
		{
			$msg_erreur = $msg_erreur . "La date de d�but n'est pas initialis�e ou est incorrecte (JJ/MM/AAAA) !!! <br>";
			$datefausse = TRUE;
		}
	}
	else
	{
		$date_debut = null;
		$datefausse = TRUE;
	}
	
	// R�cup�ration de la date de fin
	if (isset($_POST["date_fin"]))
	{
		$date_fin = $_POST["date_fin"];
		if (is_null($date_fin) or $date_fin == "" or !$fonctions->verifiedate($date_fin))
		{
			$msg_erreur = $msg_erreur . "La date de fin n'est pas initialis�e ou est incorrecte (JJ/MM/AAAA) !!! <br>";
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
			$msg_erreur = $msg_erreur . "Il y a une incoh�rence entre la date de d�but et la date de fin !!! <br>";
		}
		if (is_null($affectation))
			echo "Affectation est NULL alors que ca ne devrait pas !!!!";
		elseif (($datedebutdb < ($fonctions->formatdatedb($affectation->datedebut()))) or ($datefindb > ($fonctions->formatdatedb($affectation->datefin()))))
		{
			$msg_erreur = $msg_erreur . "Vous ne pouvez pas faire de d�claration en dehors de la p�riode d'affectation <br>";
		}
	}
	
	//echo "_POST => "; print_r ($_POST); echo "<br>";
	
	// On regarde si on a annul� une d�claration de TP
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

	// On verifie qu'il y a autant de case � coch� marqu� que de jour de TP a saisir
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
		// Si la case � cocher nocheckquotite n'est pas coch� on v�rifie la r�partition de la quotit�
		// <=> Si elle est coch� on ne fait pas de test de r�partition
		if ($nocheckquotite != 'yes')
		{
			if ($nbsemainepaire != $nbredemiTP or $nbsemaineimpaire != $nbredemiTP)
			{
				$msg_erreur = $msg_erreur . "Vous devez saisir $nbredemiTP demie(s) journ�e(s) pour les semaines paires et impaires <br>";
			}
		}
		else
			echo "<br>La fonction 'Pas de controle de la quotit�' est activ�e... Aucun contr�le n'est r�alis�.<br>";  
	}
	
	//echo "Apres le check nbredemiTP <br>";
	if ($msg_erreur == "" and !$datefausse)
	{
		// On est sur que les donn�es sont ok
		$declarationliste = $affectation->declarationTPliste($date_debut,$date_fin) ;
		// On regarde s'il y a une declaration de TP qui inclue la date de debut !!!
		$msg = "";
		if (!is_null($declarationliste))
		{
			//echo "Il y a potentiellement chevauchement entre des declarations !!!! <br>";
			foreach ($declarationliste as $key => $declaration)
			{
				if ($declaration->statut() != "r")
				{
					// Si la date de fin de l'ancienne est apr�s la date de debut de la nouvelle 
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
						if ($declaration->statut() != "r")
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$msg_erreur = $msg_erreur . "Il y a chevauchement entre la nouvelle declaration et une ancienne declaration !!!! <br>";
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
						$nvlledatefin = date("Ymd", strtotime("-1days", $timestamp ));  // On passe au jour d'apr�s (donc le lendemain)
						//echo "nvlledatefin = $nvlledatefin <br>";
						$declaration->datefin($fonctions->formatdate($nvlledatefin));
						if ($declaration->statut() != "r")
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$msg_erreur = $msg_erreur . "Il y a chevauchement entre la nouvelle declaration et une ancienne declaration !!!! <br>";
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
						$nvlledatefin = date("Ymd", strtotime("-1days", $timestamp ));  // On passe au jour d'apr�s (donc le lendemain)
						//echo "nvlledatefin = $nvlledatefin <br>";
						$declaration->datefin($fonctions->formatdate($nvlledatefin));
						if ($declaration->statut() != "r")
							$msg = $declaration->store();
						//echo "Apres le store de l'ID " . $declaration->declarationTPid() .  "... <br>";
						if ($msg != "")
						{
							$msg_erreur = $msg_erreur . "Il y a chevauchement entre la nouvelle declaration et une ancienne declaration !!!! <br>";
							$msg_erreur = $msg_erreur . $msg;
						}
					}
					$msg = "";
					// Ancienne    ]-------------[
					// => Annulation
					if ($fonctions->formatdatedb($declaration->datedebut()) > $fonctions->formatdatedb($declaration->datefin()))
					{
						//echo "----- CAS 4 ------<br>";
						//echo "La date de d�but de la declaration est apres la date de fin !!!! <br>";
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
		
		// On va enregistrer la nouvelle d�claration de TP

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
		else
			echo "<font color='green'>La d�claration de temps partiel est bien enregistr�e.</font><br><br>";

//		$timestamp = strtotime($fonctions->formatdatedb($date_debut));
//		$nvlledatefin = date("d/m/Y", strtotime("+1year", $timestamp ));  // On ajoute un an pour chercher les �ventuelles demandes faites apr�s la fin de p�riode
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
//						//echo "Il y a des demandes de cong�s qu'il faudra supprimer !!!!! <br>";
//						$msg_erreur = $msg_erreur . "Il y a des demandes de cong�s qu'il faudra supprimer !!!!! <br>";
//						$afficheheader = FALSE;
//					}	
//					$msg_erreur = $msg_erreur . " La demande du " . $demande->date_demande() . " pour la p�riode du " . $demande->datedebut() . " au "  . $demande->datefin()   . " <br>";
//					$demande->statut("r");
//					$demande->motifrefus("Changement d'autod�claration");
//					//echo "Avant le store...<br>";
//					//print_r($demande); echo "<br>";
//					$demande->store();
//					//echo "Apres le store...<br>";
//
//					// DANS LE CAS DU MODE RESPONSABLE 
//					// => 1 mail � l'agent  => exp�diteur = $user et destinataire = $agent
//					// => 1 mail au responsable => exp�diteur = $user et destinataire = $user
//					if ($mode=="resp")
//					{
//						//echo "Avant le PDF... <br>";
//						$pdffilename = $demande->pdf($user->harpegeid());
//						// Mail � l'agent
//						//echo "Avant le mail � l'agent... <br>";
//						$user->sendmail($agent,"Annulation d'une demande (Changement d'autod�claration)","Votre autod�claration a �t� modifi�e par " . $user->civilite() . " " . $user->nom() .  " "  . $user->prenom() . "!! Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						// Mail au responsable (donc c'est un automail)
//						//echo "Avant le mail AU RESP... <br>";
//						$user->sendmail($user,"Annulation d'une demande (Changement d'autod�claration)","Vous venez de changer l'autod�claration de l'agent "  . $agent->civilite() . " " . $agent->nom() .  " "  . $agent->prenom() . " !! Sa demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						//echo "Apres mail resp...<br>";
//					}
//					// DANS LE CAS DU MODE AGENT
//					// => 1 mail � l'agent  => exp�diteur = $agent et destinataire = $agent
//					// => 1 mail au responsable => exp�diteur = $agent et destinataire = $resp r�cup a partir de la strcture
//					else
//					{
//						$pdffilename = $demande->pdf($agent->harpegeid());
//						$agent->sendmail($agent,"Annulation d'une demande (Changement d'autod�claration)","Vous avez chang� d'autod�claration !! Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
//						$resp = $agent->structure()->responsable();
//						$agent->sendmail($resp,"Annulation d'une demande (Changement d'autod�claration)","L'agent "  . $agent->civilite() . " " . $agent->nom() .  " "  . $agent->prenom() . " a chang� d'autod�claration !! Sa demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . $demande->statutlibelle() . ".", $pdffilename);
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
					//echo "La quotit� != 100% ==> Je peux poser un tps partiel <br>";
					$tppossible = true;
					break;
				}
			}
		}
		if (!$tppossible)
		{
			echo "Vous n'avez aucune affectation � temps partiel entre le " . $fonctions->formatdate($debut_interval) . " et le " . $fonctions->formatdate($fin_interval) . "<br>";
		}	
		else
		{
			echo "<b><br>C'est moche => Pr�sentation � revoir !!! </b><br><br>";


			echo "<br>";
			if ($msg_erreur <> "")
				echo "<P style='color: red'>" . $msg_erreur . " </P>";
			echo "<br>";
			
			// $affectationliste = liste des affectations de l'agent pour la p�riode
			foreach ($affectationliste as $key => $affectation)
			{
				echo "<form name='frm_saisir_tpspartiel_" . $affectation->affectationid() . "' method='post' >";
				echo "<input type='hidden' name='affectationid' value='" . $affectation->affectationid() ."'>";
				echo $affectation->html(true,false);

				echo "<br>Nouvelle d�claration de temps partiel<br>";
				echo "<table>";
				echo "<tr>";
				echo"<td>Date de d�but de la p�riode :</td>";
				echo "<td width=1px><input class='calendrier' type=text name=date_debut id=date_debut size=10 ></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td>Date de fin de la p�riode :</td>";
				echo "<td width=1px><input class='calendrier' type=text name=date_fin id=date_fin size=10 ></td>";
				echo "</tr>";
				echo "</table>";
				$nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
				//echo "nbredemiTP = " . $nbredemiTP . "<br>";
				echo "<br>Vous devez poser des jours de temps partiel (Nombre de demie-journ�es par semaine = $nbredemiTP )<br>";
				
				echo "<div id='planning'>";
				echo "<table class='tableau'>";
				$declaration = new declarationTP($dbcon);
				$declaration->tabtpspartiel(str_repeat("0", 20));
				echo $declaration->tabtpspartielhtml(true);
				echo "</table>";
				echo "</div>";

				echo "<br>";
				if ($mode == "resp")
				{
					echo "<br>";	
					echo "<input type='checkbox' name='nocheckquotite' value='yes'> Ne pas v�rifier la r�partition des jours de temps partiel... <br>";
					echo "Cette fonction permet, par exemple, de saisir 3 jours de TP une semaine et 2 jours la semaine suivante pour une personne � 50% <br>";
					echo "<font color='red'><b>ATTENTION : </font></b>Cette fonction est � utiliser avec prudence... Il convient de v�rifier manuellement que la r�partion est correcte.<br>";
				}
				echo "<input type='hidden' name='nbredemiTP' value='" . $nbredemiTP ."'>";
				echo "<input type='hidden' name='userid' value='" . $userid ."'>";
				echo "<input type='hidden' name='agentid' value='" . $agentid ."'>";
				echo "<input type='hidden' name='mode' value='" . $mode ."'>";
				echo "<input type='submit' value='Valider' />";
				
				echo "</form>";
			}
			
			
/*
			echo $agent->dossierhtml();
			echo "<br>";
			if ($msg_erreur <> "")
				echo "<P style='color: red'>" . $msg_erreur . " </P>";
			
			$autodeclalistvalide=$agent->autodeclarationssurperiode($dossier->datedebut(),$dossier->datefin(),'v');
			$autodeclalistattente=$agent->autodeclarationssurperiode($dossier->datedebut(),$dossier->datefin(),'a');
			$autodeclalist=array_merge((array)$autodeclalistvalide,(array)$autodeclalistattente);
			if (!is_null($autodeclalist))
			{
				echo "<table class='tableausimple'>";
				echo "<tr><td class='titresimple' style='background-color:#E5EAE9;'>";
				echo "ATTENTION : Il exite d�ja une autod�claration !!!<BR>";
				echo "Si des cong�s ont �t� pos� avec une quotit� diff�rente, alors ils seront supprim�s. Un r�capitulatif vous sera envoy� afin que vous puissiez red�clarer vos cong�s avec la bonne quotit�.<br>";
				echo "</td></tr>";
				echo "</table>";
				echo "<br>";
				echo "<table class='tableausimple' >";
				echo "<tr align=center><td class='cellulesimple'>Nom de l'agent</td><td class='cellulesimple'>Date de la demande</td><td class='cellulesimple'>Date de d�but</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Statut</td><td class='cellulesimple'>Jours de RTT</td></tr>";
				foreach ($autodeclalist as $keyautodecla => $autodecla)
				{
					if ($autodecla != "")
						echo $autodecla->html(FALSE);
				}
				echo "</table>";
				echo "<br>";
				
			}
			
			echo "<form name='frm_etabl_autodecla'  method='post' >";

			echo "<table>";
			echo "<tr>";
			echo"<td>Date de d�but de la p�riode :</td>";
			echo "<td width=1px><input class='calendrier' type=text name=date_debut id=date_debut size=10 ></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Date de fin de la p�riode :</td>";
			echo "<td width=1px><input class='calendrier' type=text name=date_fin id=date_fin size=10 ></td>";
			echo "</tr>";
			echo "</table>";
		
//			echo "Date de d�but de la p�riode : ";
//			echo "<input class='calendrier' type=text name=date_debut id=date_debut size=10 ><br>";
//			echo "<br>";
//			echo "Date de fin de la p�riode :" ;
//			echo "<input class='calendrier' type=text name=date_fin id=date_fin size=10 ><br>";
//			echo "<br>";
			
			
			if ($agent->quotite() != "100%")
			{
				$equation = $agent->quotite();
				$equation = preg_replace("/[^0-9+\-.*\/()%]/","",$equation);       
				$equation = preg_replace("/([+-])([0-9]+)(%)/","*(1\$1.\$2)",$equation);
				// you could use str_replace on this next line
				// if you really, really want to fine-tune this equation
				$equation = preg_replace("/([0-9]+)(%)/",".\$1",$equation);
				if ( $equation == "" )
					$return = 0;
				else
					eval("\$return=" . $equation . ";" );
				$nbredemiTP = (10 - ($return * 10));
				//echo "nbredemiTP = " . $nbredemiTP . "<br>";
				echo "<br>Vous devez poser des jours de temps partiel (Nombre de demie-journ�es par semaine = $nbredemiTP )<br>";
				echo "<table class='tableausimple'>";
				echo "<tr><td class='cellulesimple' colspan=2 ><center>Semaine impaire : </center></td></tr>";
				for ($index = 1; $index <= 7 ; $index++)
				{
					$nomjour = $fonctions->nomjourparindex($index);
					echo "<tr>";
					$moment = "matin";
					echo "<td class='cellulesimple' ><input type='checkbox' name='checkbox_id[" . $index   . "]' value='" . $index .  substr($moment,0,1) . "' >" . $nomjour . " " . $moment . "</td>"; 
					$moment = "apr�s-midi";
					echo "<td class='cellulesimple' ><input type='checkbox' name='checkbox_id[" . ($index + 7)   . "]' value='" . $index .  substr($moment,0,1) . "' >" . $nomjour . " " . $moment . "</td>";
					echo "</tr>";
				}
				echo "</table>";
				echo "<br>";
				echo "<table class='tableausimple'>";
				echo "<tr><td colspan=2 ><center>Semaine paire :</center></td></tr>";
				for ($index = 15; $index <= 21 ; $index++)
				{
					$nomjour = $fonctions->nomjourparindex($index);
					echo "<tr>";
					$moment = "matin";
					echo "<td class='cellulesimple' ><input type='checkbox' name='checkbox_id[" . $index   . "]' value='" . ($index - 14) .  substr($moment,0,1) . "' >" . $nomjour . " " . $moment . "</td>";
					$moment = "apr�s-midi";
					echo "<td class='cellulesimple' ><input type='checkbox' name='checkbox_id[" . ($index + 7)   . "]' value='" . ($index - 14) .  substr($moment,0,1) . "' >" . $nomjour . " " . $moment . "</td>";
					echo "</tr>";
				}
				echo "</table>";

				//echo "Mode = $mode <br>";
				if ($mode == "resp")
				{
					echo "<br>";	
					echo "<input type='checkbox' name='nocheckquotite' value='yes'> Ne pas v�rifier la r�partition des jours de temps partiel... <br>";
					echo "Cette fonction permet, par exemple, de saisir 3 jours de TP une semaine et 2 jours la semaine suivante pour une personne � 50% <br>";
					echo "<font color='red'><b>ATTENTION : </font></b>Cette fonction est � utiliser avec prudence... Il convient de v�rifier manuellement que la r�partion est correcte.<br>";
				}
			}

//			echo "<br>";
//			echo "Valider le dossier et enregistrer l'autod�claration ";
			echo "<br>";
			echo "<input type='hidden' name='userid' value='" . $userid ."'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid ."'>";
			echo "<input type='hidden' name='nbredemiTP' value='" . $nbredemiTP ."'>";
			echo "<input type='hidden' name='mode' value='" . $mode ."'>";
			echo "<input type='submit' value='Valider' />";
			
			echo "</form>";
	*/			
		}
	}

?>

<!-- 
<a href=".">Retour � la page d'accueil</a> 
-->
 </body></html>

