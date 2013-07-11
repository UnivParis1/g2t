<?php

class cet {

	private $idannuel = null;
	private $idtotal = null;
	private $agentid = null;
	private $datedebut = null;
	private $cumulannuel = null;
	private $dbconnect = null;
	private $cumultotal = null;
	private $jrspris = null;
	 
	private $fonctions = null;

	 
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Cet->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function idannuel()
	{
		if (!is_null($this->idannuel))
			return $this->idannuel;
		else
			echo "Cet->idannuel : L'identifiant du CET annuel n'est pas initialisé !!! <br>";
	}
	
	function idtotal()
	{
		if (!is_null($this->idtotal))
			return $this->idtotal;
		else
			echo "Cet->idtotal : L'identifiant du CET total n'est pas initialisé !!! <br>";
	}
	
	function agentid($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->agentid))
				echo "Cet->agentid : L'Id de l'agent n'est pas défini !!! <br>";
			else
				return $this->agentid;
		}
		else
			$this->agentid = $agentid;
	}
	
	function datedebut($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datedebut))
				echo "Cet->datedebutcet : La date du début du CET de l'agent n'est pas défini !!! <br>";
			else
				return $this->fonctions->formatdate($this->datedebut);
		}
		else
			$this->datedebut = $this->fonctions->formatdatedb($date);
	}
	
	function cumulannuel($cumulannee = null)
	{
		if (is_null($cumulannee))
		{
			if (is_null($this->cumulannuel))
				echo "Cet->cumulannuel : Le cumul annuel du CET de l'agent n'est pas défini !!! <br>";
			else
				return $this->cumulannuel;
		}
		elseif (intval($cumulannee) == $cumulannee)
			$this->cumulannuel = $cumulannee;
		else 
			echo "Cet->cumulannuel : Le cumul annuel du CET de l'agent doit être un nombre entier <br>";
	}

	function cumultotal($cumultot = null)
	{
		if (is_null($cumultot))
		{
			if (is_null($this->cumultotal))
				echo "Cet->cumultotal : Le cumul total du CET de l'agent n'est pas défini !!! <br>";
			else
				return $this->cumultotal;
		}
		elseif (intval($cumultot) == $cumultot)
			$this->cumultotal = $cumultot;
		else 
			echo "Cet->cumultotal : Le cumul total du CET de l'agent doit être un nombre entier <br>";
	}
	
	function jrspris($nbrejrspris = null)
	{
		if (is_null($nbrejrspris))
		{
			if (is_null($this->jrspris))
				echo "Cet->jrspris : Le nombre de jours pris dans le CET de l'agent n'est pas défini !!! <br>";
			else
				return $this->jrspris;
		}
		elseif (intval($nbrejrspris) == $nbrejrspris)
			$this->jrspris = $nbrejrspris;
		else
			echo "Cet->jrspris : Le nombre de jours pris dans le CET de l'agent doit être un nombre entier <br>";
	}
	
	function load($agentid)
	{
		$msgerreur = "";
		if (is_null($agentid))
			return "Cet->load : Le code de l'agent est NULL <br>";
		
		$agent = new agent($this->dbconnect);
		$agent->load($agentid);
		// On charge le cumul annuel
		$sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE HARPEGEID = '" . $agentid   . "' AND TYPEABSENCEID ='cetcu'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Cet->Load (cetcu): " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
		{
			//echo "Cet->Load (cetcu) : Le cumul annuel pour l'agent $agentid non trouvé <br>";
			$msgerreur = $msgerreur . "Le cumul annuel pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pu être trouvé <br>";
		}

		$result = mysql_fetch_row($query);
		$this->agentid = $result[0];
		$this->idannuel = $result[1];
		$this->cumulannuel = $result[2];

		// On charge le solde du CET
		$sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE HARPEGEID = '" . $agentid   . "' AND TYPEABSENCEID ='cet'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "(cet): " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
		{
			//echo "Cet->Load (cet) : Le CET pour l'agent $agentid non trouvé <br>";
			$msgerreur = $msgerreur . "Le CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pu être trouvé <br>";
		}
		$result = mysql_fetch_row($query);
		$this->cumultotal = $result[2];
		$this->idtotal = $result[1];
		$this->jrspris = $result[3];
		
		// On charge la date de début du CET
		$complement = new complement($this->dbconnect);
		$complement->load($agentid, "DEBUTCET");
		if ($complement->harpegeid()=="")
		{
			//echo "Cet->Load (date début) : La date de début du CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " non trouvée <br>";
			$msgerreur = $msgerreur . "La date de début du CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pu être trouvée <br>";
			$this->datedebut = "";
		}
		else
			$this->datedebut = $this->fonctions->formatdate($complement->valeur());	
		//echo "Cet->Load : msgerreur = $msgerreur <br>";
		return $msgerreur;
	}
	
	function store()
	{
		//echo "cet->store : Pas encore fait !!!! <br>";
		//return;
		//echo "Avant le test idannuel <br>";
		//echo "this->idannuel = " . $this->idannuel . "<br>";
		$msgerreur = "";
		$solde = new solde($this->dbconnect);
		if (is_null($this->idannuel))
		{
			//echo "On va creer le solde <br>";
			if ($solde->load($this->agentid, 'cetcu') <> "")
				$solde->creersolde('cetcu',$this->agentid);
			// On recré un nouvel objet pour eviter les effets de bord eventuels
			unset ($solde);
			$solde = new solde($this->dbconnect);
		}
		//echo "On va recharger le solde... <br>";
		$solde->load($this->agentid, 'cetcu');
		$solde->droitaquis($this->cumulannuel);
		//echo "On va store le solde <br>";
		$msgerreur =  $msgerreur . $solde->store();
		
		unset($solde);
		$solde = new solde($this->dbconnect);
		//echo "Avant le test idtotal <br>";
		//echo "this->idtotal = " . $this->idtotal . "<br>";
		if (is_null($this->idtotal))
		{
			//echo "On va creer le solde <br>";
			$solde->creersolde('cet',$this->agentid);
			//echo "On va recharger le solde... <br>";
			
			$complement = new complement($this->dbconnect);
			$complement->harpegeid($this->agentid);
			$complement->complementid('DEBUTCET');
			if (!$this->fonctions->verifiedate($this->datedebut()))
			{
				//echo "CET->Store : Date début n'est pas une date => " . $this->datedebut() . "<br>";
				$this->datedebut = date("Ymd");
			}
			//echo "this->datedebut() = " . $this->datedebut() . "<br>";
			$complement->valeur($this->datedebut());
			$complement->store();
		}
		$solde->load($this->agentid, 'cet');

		$solde->droitaquis($this->cumultotal());
		$solde->droitpris($this->jrspris);
		//echo "On va store le solde <br>";
		$msgerreur =  $msgerreur . $solde->store();
		return $msgerreur; 
	}
	
	function pdf($responsableid, $ajoutmode = TRUE, $detail = null)
	{

		//echo "Avant le new agent <br>";
		$responsable = new agent($this->dbconnect);
		$responsable->load($responsableid);
		//echo "Apres le load...<br>";
		
		$pdf=new FPDF();
		//echo "Apres le new <br>";
		//define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage();
		$pdf->Image('images/logo_papeterie.png',70,25,60,20);
		
		//echo "Apres image <br>";
		$pdf->SetFont('Arial','B',16);
		$pdf->Ln(70);
		$pdf->SetFont('Arial','B',12);

		$agent=new agent($this->dbconnect);
		$agent->load($this->agentid);
		$affectationliste = $agent->affectationliste($this->fonctions->anneeref() . $this->fonctions->debutperiode(), ($this->fonctions->anneeref()+1) . $this->fonctions->finperiode()); 
		foreach ($affectationliste as $key => $affectation)
		{
			$structure = new structure($this->dbconnect);
			$structure->load($affectation->structureid());
			$nomstructure = $structure->nomlong() . " (" . $structure->nomcourt()  .")";
			$pdf->Cell(60,10,'Service : '. $nomstructure);
			$pdf->Ln();
		}
/*		
		$pdf->Cell(60,10,'Composante : '. $responsable->structure()->parentstructure()->nomlong() .' ('. $responsable->structure()->parentstructure()->nomcourt() .')' );
		$pdf->Ln(10);
		$pdf->Cell(60,10,'Service : '. $responsable->structure()->nomlong().' ('. $responsable->structure()->nomcourt() .')' );
		$pdf->Ln(10);
*/		
		if ($ajoutmode)
		{
			//echo "Apres le nom du service <br>";
			$pdf->Cell(40,10,'Votre "Compte Epargne Temps" (CET) vient d\'être alimenté.');
			$pdf->Ln(10);
			$pdf->Cell(40,10,'La date d\'ouverture de votre CET est : ' . $this->datedebut());
			$pdf->Ln(10);
			$pdf->Cell(40,10,'Le solde actuel de votre CET est : ' . (($this->cumultotal()-$this->jrspris())) . ' jour(s).');
			$pdf->Ln(10);
			$pdf->Cell(40,10,'Cette année, vous avez ajouté ' . ($this->cumulannuel()) . ' jour(s).');
		}
		else
		{
			$pdf->Cell(40,10,'Votre "Compte Epargne Temps" (CET) vient d\'être modifié.');
			$pdf->Ln(10);
			$pdf->Cell(40,10,$detail);
			$pdf->Ln(10);
			$pdf->Cell(40,10,'Le solde actuel de votre CET est : ' . ($this->cumultotal()-$this->jrspris()) . ' jour(s).');
		}
		//echo "Apres les textes <br>";
		$pdf->Ln(10);
		

		$pdf->Cell(40,10,$responsable->civilite() . " " . $responsable->nom() . " " . $responsable->prenom());
		$pdf->Ln(10);
		
		//echo "Nom du fichier....<br>";
		$pdfname = './pdf/modification_cet_num'.$this->idtotal(). '_' . date("Ydm")  . '.pdf';
		//echo "Avant le output... pdfname =   $pdfname <br>";
		$pdf->Output($pdfname);
		//echo "Avant le return. <br>";
		return $pdfname;
		
	}
}

?>