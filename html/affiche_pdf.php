<?php
	header('Content-type: application/pdf; charset=utf-8');
	header('Content-Disposition: attachment; filename="downloaded.pdf"');

// header('Content-type: application/pdf');
//	header('Content-Type=application/octet-stream;charset=UTF-8');
//	header('Content-Disposition: attachment; filename="downloaded.pdf"');
	
	require_once("./class/fonctions.php");
	require_once('./includes/dbconnection.php');
	
	$fonctions = new fonctions($dbcon);
	
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
//	require_once("./class/fpdf.php");
	require_once("./class/cet.php");
	require_once("./class/affectation.php");
	require_once("./class/complement.php");
	
	//print_r($_POST); echo "<br>";
	//$agent = new agent($dbcon);

	ini_set('max_execution_time', 300); //300 seconds = 5 minutes
	
	$previous = 0;
	if (isset($_POST["previous"]))
		if (strcasecmp($_POST["previous"],"yes")==0)
			$previous = 1;
		else 
			$previous = 0;
	
	$anneeref = ($fonctions->anneeref()-$previous);
	if (isset($_POST["anneeref"]))
		if ($_POST["anneeref"] != "")
			$anneeref = $_POST["anneeref"];

	$listeagent = "";
	if (isset($_POST["listeagent"]))
		$listeagent = $_POST["listeagent"];
	$typepdf = "";
	if (isset($_POST["typepdf"]))
		$typepdf = $_POST["typepdf"];

	if ($typepdf == 'listedemande')
	{
		if ($listeagent != "")
		{
			//echo "Avant le split <br>";
			$tablisteagent = preg_split("/,/",$listeagent);
			//print_r($tablisteagent); echo "<br>";
			$pdf=new TCPDF();
			//define('FPDF_FONTPATH','fpdffont/');
			$pdf->Open();
			$pdf->SetHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
			foreach ($tablisteagent as $key => $agentid)
			{
				if ($agentid <> "")
				{ 
					$agent = new agent($dbcon);
					//echo "Agentid = " . $agentid . "<br>";
					$agent->load($agentid);
					//echo "Apres le load ...<br>";
					$agent->soldecongespdf($anneeref, FALSE,$pdf,TRUE);
					$agent->demandeslistepdf($anneeref . $fonctions->debutperiode(),($anneeref+1) . $fonctions->finperiode(),$pdf,FALSE);
				}
			}
			ob_end_clean();
			$pdf->Output();
		}
		else
		{
			//echo "Dans liste demande <br>";
			$agentid = $_POST["agentid"];
			$agent = new agent($dbcon);
			//echo "Agentid = " . $agentid . "<br>";
			$agent->load($agentid);
			//echo "Apres le load ...<br>";
			$agent->demandeslistepdf($anneeref . $fonctions->debutperiode(),($anneeref+1) . $fonctions->finperiode());
		}
	}
		
	
	if (isset($_POST["userpdf"]))
	{
		if (strcasecmp($_POST["userpdf"],"yes")==0)
		{
			$agentid = $_POST["agentid"];
			$planning = new planning($dbcon);
			$planning->pdf($agentid, $fonctions->formatdate($anneeref . $fonctions->debutperiode()),$fonctions->formatdate(($anneeref+1) . $fonctions->finperiode()));
		}
	}

	
	if (isset($_POST["structpdf"]))
	{
		if (strcasecmp($_POST["structpdf"],"yes")==0)
		{
			$structid = $_POST["structid"];
			$mois_annee = $_POST["mois_annee"];
			$noiretblanc = $_POST["noiretblanc"];
			if (strcasecmp($noiretblanc,"yes")==0)
				$noiretblanc = true;
			else
				$noiretblanc = false;
			$structure = new structure($dbcon);
			$structure->load($structid);
			
			// On décompose la date mois_annee en mois et année pour éventuellement soustraire un an
			// Puis on la reformate
			// Le format de la variable mois_annee est MM/YYYY (voir fonction structure::planninghtml)
			$mois_annee = substr($mois_annee,0,3) . (substr($mois_annee,3)-$previous);
			$structure->pdf(($mois_annee),$noiretblanc);
		}
	}	
?>