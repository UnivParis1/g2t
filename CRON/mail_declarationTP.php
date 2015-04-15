<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	require_once("../html/class/agent.php");
	require_once("../html/class/structure.php");
	require_once("../html/class/solde.php");
	require_once("../html/class/demande.php");
	require_once("../html/class/planning.php");
	require_once("../html/class/planningelement.php");
	require_once("../html/class/declarationTP.php");
//	require_once("./class/autodeclaration.php");
//	require_once("./class/dossier.php");
	require_once("../html/class/tcpdf/tcpdf.php");
	require_once("../html/class/cet.php");
	require_once("../html/class/affectation.php");
	require_once("../html/class/complement.php");
	
	$fonctions = new fonctions($dbcon);

	$date=date("Ymd");

	echo "Début de l'envoi des mail de déclaration de TP " . date("d/m/Y H:i:s") . "\n" ;

	// On selectionne les demandes en attente de validation 
	$sql = "SELECT DECLARATIONID FROM DECLARATIONTP WHERE STATUT = 'a'";
	$query=mysql_query ($sql,$dbcon);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "SELECT DEMANDEID => $erreur_requete \n";
		
	$arraystruct = array();

	while ($result = mysql_fetch_row($query))
	{
		$declaration = new declarationTP($dbcon);
		$declaration->load($result[0]);
		$affectation = new affectation($dbcon);
		$affectation->load($declaration->affectationid());
		
		$structure = new structure($dbcon);
		$structure->load($affectation->structureid());
		
		// Si ce n'est pas le responsable de la structure qui a fait la demande
		$responsable = $structure->responsable();
		if (!is_null($responsable))
		{
			if ($affectation->agentid() != $responsable()->harpegeid())
			{
				// On ajoute la demande dans la structure de la demande
				$structureid = $structure->id();
				if (isset ($arraystruct[$structureid]))
					$arraystruct[$structureid] = $arraystruct[$structureid]+1;
				else 
					$arraystruct[$structureid] = 1;
			}
			// C'est le responsable de la structure qui a fait la demande
			else
			{
				// On le met dans la structure parente (si elle existe)
				$parentstructid = $structure->parentstructure()->id();
				if (isset($arraystruct[$parentstructid]))
					$arraystruct[$parentstructid] = $arraystruct[$parentstructid]+1;
				else 
					$arraystruct[$parentstructid] = 1;
				
			}
		}
		unset ($demande);
		unset ($structure);
		unset ($declarationliste);
		unset ($declaration);
		unset ($affectation);
		unset ($responsable);
	}
	
	echo "arraystruct="; print_r($arraystruct); echo "\n";
	// Création de l'agent CRON G2T
	$agentcron = new agent($dbcon);
	// -1 est le code pour l'agent CRON dans G2T
	$agentcron->load('-1');
	foreach($arraystruct as $structureid => $nbredemande)
	{
		$structure = new structure($dbcon);
		$structure->load($structureid);
		//echo "Avant le load du responsable\n";
		$responsable = $structure->responsable();
		if (!is_null($responsable))
		{
			echo "Avant le sendmail mail=" . $responsable->mail() ." Structure=" . $structureid  ." \n";
			
			$agentcron->sendmail($responsable,"Des demandes de temps partiel sont en attentes","Il y a $nbredemande demande(s) de temps-partiel en attente de validation.\nMerci de bien vouloir les valider dès que possible.\n",null);
		}
		unset ($structure);
		unset ($responsable);
	}
	unset ($agentcron);
	echo "Fin de l'envoi des mail de déclaration de TP " . date("d/m/Y H:i:s") . "\n";

?>