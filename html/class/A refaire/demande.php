<?php

class demande {
	
	private $demandeid = null;
	private $structureid = null;
	private $typdemande = null;
	private $agentid = null;
	private $datedebut = null;
	private $datefin = null;
	private $demijrs_debut = null;
	private $demijrs_fin = null;
	private $commentaire = null;
	private $nbredemijrs_demande = null;
	private $datedemande = null;
	private $datevalidation = null;
	private $decision = null;
	private $statutdemande = null;
	private $motifrefus = null;
	private $dbconnect = null;
	private $ancienstatut = null;

	private $fonctions = null;

	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Demande->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function load($demandeid)
	{
//		if (is_null($this->$demandeid))
		if (!isset($this->$demandeid))
		{
			$sql = "SELECT ID_CONGE_POSE,ID_STRUCTURE,COD_TYP_CONGE,CODE,D_DEB_CONGE,D_FIN_CONGE,DEMI_JRS_DEB,DEMI_JRS_FIN,
COMMENTAIRE,NBR_DEMI_JRS_DEMANDE,D_DEMANDE,D_VALIDATION,DECISION,STATUT_CONGE,MOTIF_REFUS 
FROM CONGE_POSE WHERE ID_CONGE_POSE = '" . $demandeid . "'";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->Load : Demande $demandeid non trouvée <br>";
			$result = mysql_fetch_row($query);
			$this->demandeid = "$result[0]";
			$this->structureid = "$result[1]";
			$this->typdemande = "$result[2]";
			$this->agentid = "$result[3]";
			$this->datedebut = "$result[4]";
			$this->datefin = "$result[5]";
			$this->demijrs_debut = "$result[6]";
			$this->demijrs_fin = "$result[7]";
			$this->commentaire = str_replace("'","''",$result[8]);
			$this->nbredemijrs_demande = "$result[9]";
			$this->datedemande = "$result[10]";
			$this->datevalidation = "$result[11]";
			$this->decision = "$result[12]";
			$this->statutdemande = "$result[13]";
			$this->motifrefus = str_replace("'","''",$result[14]);
			
			$this->ancienstatut = $this->statutdemande;
		}
	}

	function id()
	{
		return $this->demandeid;
	}
	
	function structure($structureid = null)
	{
		if (is_null($structureid))
		{
			if (is_null($this->structureid))
				echo "Demande->structure : La structure n'est pas définie !!! <br>";
			else
			{
				$structure = new structure($this->dbconnect);
				$structure->load("$this->structureid");
				return $structure;
			}
		}
		else
			$this->structureid = $structureid;
	}
	
	function type($typeid = null)
	{
		if (is_null($typeid))
		{
			if (is_null($this->typdemande))
				echo "Demande->type : Le type de demande n'est pas défini !!! <br>";
			else
				return $this->typdemande;
		}
		else
			$this->typdemande = $typeid;
	}
	
	function typelibelle()
	{
		if (is_null($this->typdemande))
			echo "Demande->typelibelle : Le type de demande n'est pas défini !!! <br>";
		else
		{
			$sql = "SELECT COD_TYP_CONGE,LL_TYPE_CONGE FROM TYPE_CONGE WHERE COD_TYP_CONGE='" . $this->typdemande . "'";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->typdemande : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->typdemande : Libellé du type de demande $this->typdemande non trouvé <br>";
			$result = mysql_fetch_row($query);
			return "$result[1]";
		}
	}

	function agent($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->agentid))
				echo "Demande->agent : L'agent n'est pas défini !!! <br>";
			else
			{
				$agent = new agent($this->dbconnect);
				$agent->load("$this->agentid");
				return $agent;
			}
		}
		else
			$this->agentid = $agentid;
	}
	
	function datedebut($date_debut = null)
	{
		if (is_null($date_debut))
		{
			if (is_null($this->datedebut))
				echo "Demande->datedebut : La date de début n'est pas défini !!! <br>";
			else 
			{
				return $this->fonctions->formatdate($this->datedebut);
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->datedebut = $this->fonctions->formatdatedb($date_debut);
			else
				echo "Demande->datedebut : Impossible de modifier une date si la demande est enregistrée !!! <br>";
		}
	}

	function datefin($date_fin = null)
	{
		if (is_null($date_fin))
		{
			if (is_null($this->datefin))
				echo "Demande->datefin : La date de fin n'est pas défini !!! <br>";
			else 
			{
				return $this->fonctions->formatdate($this->datefin);
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->datefin = $this->fonctions->formatdatedb($date_fin);
			else
				echo "Demande->datefin : Impossible de modifier une date si la demande est enregistrée !!! <br>";
		}
	}

	function demijrs_debut($demijrs_deb = null)
	{
		if (is_null($demijrs_deb))
		{
			if (is_null($this->demijrs_debut))
				echo "Demande->demijrs_debut : La demie-journée de début n'est pas définie !!! <br>";
			else
			{
				if ($this->demijrs_debut == 'm')
					return "matin";
				elseif ($this->demijrs_debut == 'a') 
					return "après-midi";
				else 
					echo "Demande->demijrs_debut : la demie-journée n'est pas connu [demijrs_debut = $this->demijrs_debut] !!! <br>";
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->demijrs_debut = $demijrs_deb;
			else
				echo "Demande->demijrs_debut : Impossible de modifier la demie-journée de début si la demande est enregistrée !!! <br>";
		}
	}

	function demijrs_fin($demijrs_fin = null)
	{
		if (is_null($demijrs_fin))
		{
			if (is_null($this->demijrs_fin))
				echo "Demande->demijrs_fin : La demie-journée de fin n'est pas définie !!! <br>";
			else
			{
				if ($this->demijrs_fin == 'm')
					return "matin";
				elseif ($this->demijrs_fin == 'a')
					return "après-midi";
				else
					echo "Demande->demijrs_fin : la demie-journée n'est pas connu [demijrs_fin = $this->demijrs_fin] !!! <br>";
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->demijrs_fin = $demijrs_fin;
			else
				echo "Demande->demijrs_fin : Impossible de modifier la demie-journée de fin si la demande est enregistrée !!! <br>";
		}
	}
	
	function commentaire($comment = null)
	{
		if (is_null($comment))
			return str_replace("''","'",$this->commentaire);
		else
			$this->commentaire = str_replace("'","''",$comment);

	}

	function nbredemijrs_demande($nbredemijrs = null)
	{
		if (is_null($nbredemijrs))
		{
			if (is_null($this->nbredemijrs_demande))
				echo "Demande->nbredemijrs_demande : Le nombre de demie-journées demandées n'est pas défini !!! <br>";
			else
			{
				return $this->nbredemijrs_demande;
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->nbredemijrs_demande = $nbredemijrs;
			else
				echo "Demande->nbredemijrs_demande : Impossible de modifier le nombre de demie-journées si la demande est enregistrée !!! <br>";
		}
	}
	
	function date_demande()
	{
		if (is_null($this->demandeid))
			echo "Demande->date_demande : La demande n'est pas enregistrée, donc pas de date de demande !!! <br>";
		else
			return $this->fonctions->formatdate($this->datedemande);
	}

	function date_validation()
	{
		if (is_null($this->demandeid))
			echo "Demande->date_validation : La demande n'est pas enregistrée, donc pas de date de validation !!! <br>";
		else
			return $this->fonctions->formatdate($this->datevalidation);
	}
	
	function decision()
	{
		if (is_null($this->demandeid))
			echo "Demande->decision : La demande n'est pas enregistrée, donc pas de décision !!! <br>";
		else
			return $this->decision;
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->demandeid))
				echo "Demande->statut : La demande n'est pas enregistrée, donc pas de statut !!! <br>";
			else
			{
				if ($this->statutdemande == 'v' or $this->statutdemande == 'a' or $this->statutdemande == 'r')
					return $this->statutdemande;
				else
					echo "Demande->statut : le statut n'est pas connu [statut = $this->statutdemande] !!! <br>";
			}
		}
		else
		{
			if ($this->statutdemande == 'a' or ($this->statutdemande == 'v' and $statut=='r'))
			{
				$this->datevalidation = $this->fonctions->formatdatedb(date("d/m/Y"));
				$this->statutdemande = $statut;
				$this->decision = $statut;
			}
			else
				echo "Le statut actuel est : " . $this->statutdemande . " ===> Impossible de le passer au statut : " . $statut . "<br>";
		}
	}
	
	function statutlibelle()
	{
		if (is_null($this->demandeid))
			echo "Demande->statutlibelle : La demande n'est pas enregistrée, donc pas de statut !!! <br>";
		else
		{
			if ($this->statutdemande == 'v')
				return "Validé";
			elseif ($this->statutdemande == 'r')
				return "Refusé";
			elseif ($this->statutdemande == 'a')
				return "En attente";
			else
				echo "Demande->statutlibelle : le statut n'est pas connu [statut = $this->statutdemande] !!! <br>";
		}
	}

	function motifrefus($motif = null)
	{
		if (is_null($motif))
		{
			if (is_null($this->demandeid))
				echo "Demande->motifrefus : La demande n'est pas enregistrée, donc pas de motif de refus !!! <br>";
			else
				return str_replace("''","'",$this->motifrefus);
		}
		else
			$this->motifrefus = str_replace("'","''",$motif);
	}
	
	function store($ignoreabsenceautodecla = FALSE, $ignoresoldeinsuffisant = FALSE)
	{
		if (is_null($this->demandeid))
		{
			// On vérifie que le nombre de jour demandé est >= Nbre de jour restant (si c'est un conge !!)
			//echo "Demande->Store : typdemande=". $this->typdemande . "<br>";
			if ($this->fonctions->estunconge($this->typdemande))
			{
				//echo "C'est un congé... <br>";
				unset ($solde);
				$solde = new solde($this->dbconnect);
				$solde->loadbytypeagent($this->agentid,$this->typdemande );
			}

			//echo "datedemande = " . $this->datedemande;
			if (is_null($this->nbredemijrs_demande))
			{
				//echo "Le nbre jour est nul ==> On demande le nombre de jour <br>";
				$planning = new planning($this->dbconnect);
				//echo "this->agentid" . $this->agentid  . "<br>";
				//echo "this->fonctions->formatdate($this->datedebut) " .  $this->fonctions->formatdate($this->datedebut) . "<br>";
				//echo "this->demijrs_debut " . $this->demijrs_debut  . "<br>";
				//echo "this->fonctions->formatdate($this->datefin) " .  $this->fonctions->formatdate($this->datefin) . "<br>";
				//echo "this->demijrs_fin " . $this->demijrs_fin  . "<br>";
				//echo "ignoreabsenceautodecla " . $ignoreabsenceautodecla  . "<br>";

				$this->nbredemijrs_demande = $planning->nbrejourtravaille($this->agentid, $this->fonctions->formatdate($this->datedebut), $this->demijrs_debut, $this->fonctions->formatdate($this->datefin), $this->demijrs_fin, $ignoreabsenceautodecla) * 2;
				//echo "nbredemijrs_demande = " . $this->nbredemijrs_demande . "<br>";
			}
				
			if ($this->fonctions->estunconge($this->typdemande))
			{
				$nbjrrestant = 0;
				if (is_null($solde))
					echo "Demande->Store : Pas de solde pour le type de demande " . $this->typdemande . " et l'agent " . $this->agentid . "<br>";
				else
				{
					$nbjrrestant = $solde->droitaquis_demijrs() - $solde->droitpris_demijrs();
					//echo "solde->droitaquis_demijrs() - solde->droitpris_demijrs() ==> " . $solde->droitaquis_demijrs() . "  -  " . $solde->droitpris_demijrs() . "<br>";
				}
			}
			
			//echo "Nombre de jours restant = " . $nbjrrestant . "   nbredemijrs_demande = " .  $this->nbredemijrs_demande . " <br>";
			if (($nbjrrestant >= $this->nbredemijrs_demande) or (!$this->fonctions->estunconge($this->typdemande)) or ($ignoresoldeinsuffisant == TRUE))
			{
				if ($this->nbredemijrs_demande == 0)
					return "Le nombre de jour demandé est égal à 0. <br>";
				// On est dans le cas d'une création de demande
				$this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));

				$sql = "LOCK TABLES CONGE_POSE WRITE";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "SET AUTOCOMMIT = 0";
	 			mysql_query($sql,$this->dbconnect);
				$sql = "INSERT INTO CONGE_POSE(ID_STRUCTURE,COD_TYP_CONGE,CODE,D_DEB_CONGE,D_FIN_CONGE,DEMI_JRS_DEB,DEMI_JRS_FIN,COMMENTAIRE,NBR_DEMI_JRS_DEMANDE,D_DEMANDE,DECISION,STATUT_CONGE) ";
				$sql = $sql . "VALUES('" . $this->structureid . "','" . $this->typdemande . "',";
				$sql = $sql . "'" . $this->agentid . "','" . $this->fonctions->formatdatedb($this->datedebut) . "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->demijrs_debut . "',";
				$sql = $sql . "'" . $this->demijrs_fin . "','" . $this->commentaire . "','" . $this->nbredemijrs_demande . "','" . $this->fonctions->formatdatedb($this->datedemande)  . "','a','a')";
				//echo "SQL = " . $sql . "<br>";
	 			mysql_query($sql,$this->dbconnect);
	 			$erreur=mysql_error();
	 			if ($erreur != "")
	 				echo "Demande->store : " . $erreur . "<br>";
	 			$sql = "SELECT LAST_INSERT_ID()";
	 			$this->demandeid = mysql_query($sql,$this->dbconnect);
	 			$sql = "COMMIT";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "UNLOCK TABLES";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "SET AUTOCOMMIT = 1";
	 			mysql_query($sql,$this->dbconnect);

	 			// On decompte le nombre de 1/2journée que l'on vient de poser
				if ($this->fonctions->estunconge($this->typdemande))
				{
					$sql = "UPDATE SOLDE_CMPTE
					  		 SET DROIT_PRIS_DEMIE_JRS = DROIT_PRIS_DEMIE_JRS + " . $this->nbredemijrs_demande . "
							 WHERE COD_TYP_CONGE='" . $this->typdemande . "' AND CODE = '" . $this->agentid  . "'";				
					//echo "SQL = $sql  <br>";
					$query=mysql_query ($sql,$this->dbconnect);
					$erreur=mysql_error();
					if ($erreur != "")
						echo "Demande->store (SOLDE_CMPTE) : " . $erreur . "<br>";
				}
				$this->ancienstatut = "a";
			}
			else
				return "Nombre de jours insuffisants ==> Demandé = " . ($this->nbredemijrs_demande / 2) . " Solde restant : " . ($nbjrrestant  / 2) . " !!! <br>";
		}
		else
		{
			if ($this->ancienstatut == "r")
			{
				return "Impossible de changer le statut d'une demande 'refusée'!! <br>";
			}
			else
			{
				// On est dans le cas d'une modification de demande
				$sql = "UPDATE CONGE_POSE
						SET D_VALIDATION='" . $this->fonctions->formatdatedb($this->datevalidation) . "', DECISION='" . $this->decision . "'
						  , STATUT_CONGE='" . $this->statutdemande . "', MOTIF_REFUS='" . $this->motifrefus  . "'
						 WHERE ID_CONGE_POSE=" . $this->demandeid;				
				//echo "SQL = $sql  <br>";
	 			$query=mysql_query ($sql,$this->dbconnect);
	 			$erreur=mysql_error();
	 			if ($erreur != "")
	 				echo "Demande->store : " . $erreur . "<br>";
	 			if ($this->ancienstatut <> "r" and $this->statutdemande == "r")
	 			{
	 				// On recrédite le nombre de jours dans les congés....
	 				$sql = "UPDATE SOLDE_CMPTE
						  		 SET DROIT_PRIS_DEMIE_JRS = DROIT_PRIS_DEMIE_JRS - " . $this->nbredemijrs_demande . "
								 WHERE COD_TYP_CONGE='" . $this->typdemande . "' AND CODE = '" . $this->agentid  . "'";
	 				//echo "SQL = $sql  <br>";
	 				$query=mysql_query ($sql,$this->dbconnect);
	 				$erreur=mysql_error();
	 				if ($erreur != "")
	 					echo "Demande->store (Modif SOLDE_CMPTE) : " . $erreur . "<br>";
	 			}
			}
		}
		return "";
	}
	

	function pdf($valideurid)
	{
		//echo "Debut du PDF <br>";
		$pdf=new FPDF();
		//echo "Apres le new <br>";
		define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage();
		$pdf->Image('images/logo_papeterie.png',70,25,60,20);
		
		if (is_null($this->structureid) or $this->structureid=="")
		{
			//echo "Le code de la structure est vide...<br>";
			$agent=new agent($this->dbconnect);
			$agent->load($this->agentid);
			$this->structure($agent->structure()->id());
			//echo "Apres le load de la structure du responsable... <br>";
		}
		
		$pdf->SetFont('Arial','B',16);
		$pdf->Ln(70);
		$pdf->Cell(60,10,'Composante : '. $this->structure()->parentstructure()->nomlong() .' ('. $this->structure()->parentstructure()->nomcourt() .')' );
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		$pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		if ($this->fonctions->estunconge($this->type()))
			$typelib = " de congé ";
		else 
			$typelib = " d'autorisation d'absence ";
		$pdf->Cell(60,10,'Demande' . $typelib .  'N°'. $this->id() .' de ' . $this->agent()->civilite() . " " . $this->agent()->nom() . " " . $this->agent()->prenom() );
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		if($this->statut()=='v')
			$decision='validée';
		else
			$decision='refusée';
		
		$pdf->Cell(40,10,'Votre demande ' .  $typelib  . 'du '. $this->datedebut() .' '.$this->demijrs_debut().' au '.$this->datefin().' '.$this->demijrs_fin(). ' ');
		$pdf->Ln(10);
		$pdf->Cell(40,10,' a été '.$decision. ' par :');
		
		$pdf->Ln(10);
		
		$valideur = new agent($this->dbconnect);
		$valideur->load($valideurid);
		
		$pdf->Cell(40,10,' - '. $valideur->civilite() . " " . $valideur->nom() . " " . $valideur->prenom());
		$pdf->Ln(10);
		
		
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(40,10,'Date de dépot : '. $this->date_demande());
		$pdf->Ln(10);
		$pdf->Cell(40,10,'Date de validation : '.$this->date_validation());
		$pdf->Ln(10);
		if($this->statut()=='v')
		{
			if ($this->fonctions->estunconge($this->type()))
				$pdf->Cell(40,10,'Nombre de jour(s) comptabilisé(s) : '.($this->nbredemijrs_demande()/2));
		}
		else
		{
			//echo "Motif refus = " .$this->motifrefus() . "<br>";
			//echo "Motif refus (avec strreplace) = ". str_replace("''", "'", $this->motifrefus()) . "<br>";
			
			$pdf->Cell(40,10,'Motif du refus : ' . str_replace("''", "'", $this->motifrefus()));
		}
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		$pdf->Ln(10);
		$pdf->Cell(25,10,'');
		$pdf->Cell(60,10,'Solde en cours');
		$pdf->Ln(10);
		$pdf->SetFont('Arial','I',9);
		$pdf->Cell(25,10,'');
		$pdf->Cell(70,7,'Type de congé',1);
		$pdf->Cell(25,7,'Droit acquis',1);
		$pdf->Cell(25,7,'Droit pris',1);
		$pdf->Cell(25,7,'Solde actuel',1);
		$pdf->Ln();
		$pdf->SetFont('Arial','B',9);
		$pdf->Cell(25,10,'');

		$tabsolde = $this->agent()->soldecongesliste($this->fonctions->anneeref());
		foreach ($tabsolde as $key => $solde)
		{
			$pdf->Cell(70,7,$solde->typelibelle(),1);
			$pdf->Cell(25,7,(string)($solde->droitaquis_demijrs()/2),1);
			$pdf->Cell(25,7,(string)($solde->droitpris_demijrs()/2),1);
			$pdf->Cell(25,7,(string)($solde->solde_demijrs()/2),1);
			$pdf->Ln();
			$pdf->SetFont('Arial','B',9);
			$pdf->Cell(25,10,'');
		}
		
// 		//Positionnement à 1,5 cm du bas
// 		$pdf->SetY(-40);
// 		//Police Arial italique 8
// 		$pdf->SetFont('Arial','B',7);
// 		$pdf->Cell(190,1,'Université Panthéon-Sorbonne - Paris 1, 12 place du Panthéon, 75005 PARIS',0,0,'C');
		
		
		//$pdf->Output();
		$pdfname = './pdf/demande_num'.$this->id().'.pdf';
		//$pdfname = sys_get_temp_dir() . '/demande_num'.$this->id().'.pdf';
		//echo "Nom du PDF = " . $pdfname . "<br>";
		$pdf->Output($pdfname);
		return $pdfname;
		
	}
}

	
?>