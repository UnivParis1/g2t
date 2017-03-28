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

	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
	echo "<br>";

	ini_set('max_execution_time', 300); //300 seconds = 5 minutes
	$mode = $_POST["mode"];
	if ($mode == "")
		$mode = "resp";

	$previous = "";
	if (isset($_POST["previous"]))
		$previous = $_POST["previous"];
	if ($previous == 'yes')
		$previous = 1;
	else
		$previous = 0;

	if (strcasecmp($mode,"resp")==0)
	{
		$structureliste = $user->structrespliste();
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo "Solde des agents de la structure : " . $structure->nomlong() . " ("   . $structure->nomcourt() . ") <br>";
			$annerecherche = ($fonctions->anneeref()-$previous);
			$agentliste = $structure->agentlist($fonctions->formatdate($annerecherche.$fonctions->debutperiode()),$fonctions->formatdate(($annerecherche+1).$fonctions->finperiode()));
			//$agentliste = $structure->agentlist(date("d/m/").$annerecherche,date("d/m/").$annerecherche);

			echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
			echo "<input type='hidden' name='userpdf' value='no'>";
			//$htmltext = $htmltext .    "<input type='hidden' name='previous' value='" . $_POST["previous"]  . "'>";
			echo "<input type='hidden' name='anneeref' value='" . $annerecherche ."'>";
			$listeagent = "";
			//echo "Avant le foreach <br>";
			if (is_array($agentliste))
			{
				foreach ($agentliste as $agentkey => $agent)
				{
					$listeagent = $listeagent . "," . $agent->harpegeid();
				}
			}
			//echo "listeagent = $listeagent <br>";
			echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";
			echo "<input type='hidden' name='typepdf' value='listedemande'>";
			echo "</form>";
			echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
			echo "<br>";

			if (is_array($agentliste))
			{
				foreach ($agentliste as $agentkey => $agent)
				{
	 				 //echo "Annee ref = " . $fonctions->anneeref();
	 				 //echo " debut =  " . $fonctions->debutperiode();
	 				 //echo " Annee ref +1 = " . ($fonctions->anneeref()+1);
	 				 //echo " Fin = " . $fonctions->finperiode();
	 				 //echo "Previous = " . $previous ;
					echo $agent->soldecongeshtml(($fonctions->anneeref()-$previous),TRUE);
					if ($previous == 0)
						echo $agent->affichecommentairecongehtml(true);
					echo $agent->demandeslistehtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),$structure->id(),FALSE);
					echo $agent->planninghtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),FALSE,FALSE);

					// Ligne de sÃ©paration entre les agents
					echo "<hr>";
				}
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
			$annerecherche = ($fonctions->anneeref()-$previous);
			$agentliste = $structure->agentlist($fonctions->formatdate($annerecherche.$fonctions->debutperiode()),$fonctions->formatdate(($annerecherche+1).$fonctions->finperiode()));
			//$agentliste = $structure->agentlist(date("d/m/").$annerecherche,date("d/m/").$annerecherche);
			//$agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"));

			//echo "agentliste="; print_r($agentliste); echo "<br>";
			echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
			echo "<input type='hidden' name='userpdf' value='no'>";
			//$htmltext = $htmltext .    "<input type='hidden' name='previous' value='" . $_POST["previous"]  . "'>";
			echo "<input type='hidden' name='anneeref' value='" . $annerecherche ."'>";
			$listeagent = "";
			//echo "Avant le foreach <br>";
			if (is_array($agentliste))
			{
				foreach ($agentliste as $agentkey => $agent)
				{
					$listeagent = $listeagent . "," . $agent->harpegeid();
				}
			}
			//echo "listeagent = $listeagent <br>";
			//echo "agentliste Apres ="; print_r($agentliste); echo "<br>";


			echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";
			echo "<input type='hidden' name='typepdf' value='listedemande'>";
			echo "</form>";
			echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
			echo "<br>";

			if (is_array($agentliste))
			{
				foreach ($agentliste as $agentkey => $agent)
				{
					//echo "NOM de l'agent = " . $agent->nom() . "<br>";
					echo $agent->soldecongeshtml($fonctions->anneeref()-$previous,TRUE);
					if ($previous == 0)
						echo $agent->affichecommentairecongehtml(true);
//				echo "fonctions->anneeref() . fonctions->debutperiode() = " . $fonctions->anneeref() . $fonctions->debutperiode() . "<br>";
					echo $agent->demandeslistehtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),$structure->id(),FALSE);
					echo $agent->planninghtml(($fonctions->anneeref()-$previous) . $fonctions->debutperiode(), ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode(),FALSE,FALSE);
					echo "<hr>";
				}
			}
			echo "<br>";
		}

	}

?>

<!--
	<a href=".">Retour Ã  la page d'accueil</a>
-->
</body></html>

