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

	require ("includes/menu.php");
	echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	$mode = $_POST["mode"];
	if ($mode == "")
		$mode = "resp";
	
	$previous = $_POST["previous"];
	if ($previous == 'yes')
		$previous = 1;
	else
		$previous = 0;
	
	if ($mode == "resp")
	{
		$structureliste = $user->structrespliste();
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo "Solde des agents de la structure : " . $structure->nomlong() . " ("   . $structure->nomcourt() . ") <br>";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"));
			
			echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
			echo "<input type='hidden' name='userpdf' value='no'>";
			//$htmltext = $htmltext .    "<input type='hidden' name='previous' value='" . $_POST["previous"]  . "'>";
			echo "<input type='hidden' name='anneeref' value='" . ($fonctions->anneeref()-$previous) ."'>";
			$listeagent = "";
			//echo "Avant le foreach <br>";
			foreach ($agentliste as $agentkey => $agent)
			{
				$listeagent = $listeagent . "," . $agent->harpegeid(); 
			}
			//echo "listeagent = $listeagent <br>";
			echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";			
			echo "<input type='hidden' name='typepdf' value='listedemande'>";
			echo "</form>";
			echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
			echo "<br>";

			foreach ($agentliste as $agentkey => $agent)
			{
 				 //echo "Annee ref = " . $fonctions->anneeref();
 				 //echo " debut =  " . $fonctions->debutperiode();
 				 //echo " Annee ref +1 = " . ($fonctions->anneeref()+1);
 				 //echo " Fin = " . $fonctions->finperiode();
 				 //echo "Previous = " . $previous ;
				echo $agent->soldecongeshtml(($fonctions->anneeref()-$previous),TRUE);
				echo $agent->demandeslistehtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),$structure->id(),FALSE);
				echo $agent->planninghtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),FALSE,FALSE);

				// Ligne de séparation entre les agents
				echo "<hr>";
			}
			echo "<br>";
		}
	}
	else
	{
		$structureliste = $user->structgestliste();
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo "Solde des agents de la structure : " . $structure->nomlong() . " ("   . $structure->nomcourt() . ") <br>";
			$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"));

			echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
			echo "<input type='hidden' name='userpdf' value='no'>";
			//$htmltext = $htmltext .    "<input type='hidden' name='previous' value='" . $_POST["previous"]  . "'>";
			echo "<input type='hidden' name='anneeref' value='" . ($fonctions->anneeref()-$previous) ."'>";
			$listeagent = "";
			//echo "Avant le foreach <br>";
			foreach ($agentliste as $agentkey => $agent)
			{
				$listeagent = $listeagent . "," . $agent->harpegeid(); 
			}
			//echo "listeagent = $listeagent <br>";
			echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";			
			echo "<input type='hidden' name='typepdf' value='listedemande'>";
			echo "</form>";
			echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
			echo "<br>";
				
			foreach ($agentliste as $agentkey => $agent)
			{
				echo $agent->soldecongeshtml($fonctions->anneeref(),TRUE);
				echo $agent->demandeslistehtml($fonctions->anneeref() . $fonctions->debutperiode(), ($fonctions->anneeref()+1) . $fonctions->finperiode(),$structure->id(),FALSE);
				echo $agent->planninghtml($fonctions->anneeref() . $fonctions->debutperiode(), ($fonctions->anneeref()+1) . $fonctions->finperiode(),FALSE,FALSE);
				echo "<hr>";
			}
			echo "<br>";
		}
		
	}

?>

<!-- 
	<a href=".">Retour à la page d'accueil</a>
--> 
</body></html>

