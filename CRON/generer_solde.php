<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');
	
	$fonctions = new fonctions($dbcon);

	require_once("../html/class/agent.php");
	require_once("../html/class/structure.php");
	require_once("../html/class/solde.php");
	require_once("../html/class/demande.php");
	require_once("../html/class/planning.php");
	require_once("../html/class/planningelement.php");
	require_once("../html/class/declarationTP.php");
	require_once("../html/class/fpdf.php");
	require_once("../html/class/cet.php");
	require_once("../html/class/affectation.php");
	require_once("../html/class/complement.php");
	

	//		Recherche de tous les services avec un gestionnaire
	// 		Pour chaque service => Recup�ration des agents du service
	//		G�n�ration du PDF => Sauvegarde 
	// 		Envoi par mail du fichier PDF

	$jour = date('j');
	$mois = date('m');
	$annee = date('Y');
	
	//$mois=9;
	
	$mois = ($mois - 1);
	if ($mois == 0)
	{
		$mois = 12;
		$annee = ($annee - 1);
	}
	$mois = str_pad($mois,2,"0",STR_PAD_LEFT);
	
	$datedebut = "01/" . $mois . "/" . $annee;
	$datefin = $fonctions->nbr_jours_dans_mois($mois, $annee) . "/" . $mois . "/" . $annee;
	$anneeref = $fonctions->anneeref($datedebut); 
	echo "Date debut = $datedebut   Date fin = $datefin  anneeref = $anneeref\n";
	
//	if ($jour == 1)  // Premier jour du mois
//	{
		$sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID!='' AND NOT ISNULL(GESTIONNAIREID)";
		$query=mysql_query ($sql, $dbcon);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "generer_solde (SELECT) : " . $erreur . "<br>";
		while ($result = mysql_fetch_row($query))
		{
			$cronmail = new agent($dbcon);
			$cronmail->load("-1");
		
			$struct = new structure($dbcon);
			$struct->load("$result[0]");
			echo "G�n�ration du PDF pour la structure " . $struct->nomcourt() . "\n";
			$tablisteagent = $struct->agentlist($datedebut, $datefin, 'n'); 
			if (!is_null($tablisteagent))
			{
				$pdf=new FPDF();
				$pdf->Open();
				foreach ($tablisteagent as $key => $agent)
				{
					echo "Agent = " . $agent->identitecomplete() . "\n";
					$agent->soldecongespdf($anneeref, FALSE,$pdf,TRUE);
					$agent->demandeslistepdf($anneeref . $fonctions->debutperiode(),($anneeref+1) . $fonctions->finperiode(),$pdf,FALSE);
				}
				$filename= dirname(__FILE__) . '/../html/pdf/solde_' . $struct->nomcourt() . '_' . date('Ymd') . ".pdf";
				$pdf->Output($filename,'F');   // F = file
				$gest = $struct->gestionnaire();
				$cronmail->sendmail($gest , 'R�capitulatif des cong�s pour la structure ' . $struct->nomcourt(),"Veuillez trouver ci-joint le r�capitulatif des cong�s pour la structure " . $struct->nomcourt() . " � la date du ". date("d/m/Y") .".\n",$filename);
			}
		}
		echo "Fin de la g�n�ration .... \n";
//	}
//	else
//	{
//		echo "On est pas la bonne date...";
//	}


?>