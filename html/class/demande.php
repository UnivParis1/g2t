<?php

class demande {
	
	private $demandeid = null;
	private $typeabsenceid = null;
	private $datedebut = null;
	private $datefin = null;
	private $momentdebut = null;
	private $momentfin = null;
	private $commentaire = null;
	private $nbrejrsdemande = null;
	private $datedemande = null;
	private $datestatut = null;
	private $statut = null;
	private $motifrefus = null;
	private $dbconnect = null;

	// Utilisé lors de la sauvegarde !!
	private $ancienstatut = null;
	private $agent = null;

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
			$sql = "SELECT DEMANDEID,TYPEABSENCEID,DATEDEBUT,MOMENTDEBUT,DATEFIN,MOMENTFIN,COMMENTAIRE,NBREJRSDEMANDE,DATEDEMANDE,DATESTATUT,STATUT,MOTIFREFUS
FROM DEMANDE WHERE DEMANDEID= '" . $demandeid . "'";
			//echo "Demande load sql = $sql <br>";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->Load : Demande $demandeid non trouvée <br>";
			$result = mysql_fetch_row($query);
			$this->demandeid = "$result[0]";
			$this->typeabsenceid = "$result[1]";
			$this->datedebut = "$result[2]";
			$this->momentdebut = "$result[3]";
			$this->datefin = "$result[4]";
			$this->momentfin = "$result[5]";
			$this->commentaire = str_replace("'","''",$result[6]);
			$this->nbrejrsdemande = "$result[7]";
			$this->datedemande = "$result[8]";
			$this->datestatut = "$result[9]";
			$this->statut = "$result[10]";
			$this->motifrefus = str_replace("'","''",$result[11]);
			
			$this->ancienstatut = $this->statut;
		}
	}

	function id()
	{
		return $this->demandeid;
	}
	
	function type($typeid = null)
	{
		if (is_null($typeid))
		{
			if (is_null($this->typeabsenceid))
				echo "Demande->type : Le type de demande n'est pas défini !!! <br>";
			else
				return $this->typeabsenceid;
		}
		else
			$this->typeabsenceid = $typeid;
	}
	
	function typelibelle()
	{
		if (is_null($this->typeabsenceid))
			echo "Demande->typelibelle : Le type de demande n'est pas défini !!! <br>";
		else
		{
			$sql = "SELECT LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID='" . $this->typeabsenceid . "'";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->typdemande : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->typdemande : Libellé du type de demande $this->typeabsenceid non trouvé <br>";
			$result = mysql_fetch_row($query);
			return "$result[0]";
		}
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

	function moment_debut($moment_deb = null)
	{
		if (is_null($moment_deb))
		{
			if (is_null($this->momentdebut))
				echo "Demande->moment_debut : La demie-journée de début n'est pas définie !!! <br>";
			else
			{
				if ($this->momentdebut == 'm')
					return "m";
					//return "matin";
				elseif ($this->momentdebut == 'a') 
					return "a";
					//return "après-midi";
				else 
					echo "Demande->moment_debut : le moment de début n'est pas connu [momentdebut = $this->momentdebut] !!! <br>";
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->momentdebut = $moment_deb;
			else
				echo "Demande->moment_debut : Impossible de modifier la demie-journée de début si la demande est enregistrée !!! <br>";
		}
	}

	function moment_fin($moment_fin = null)
	{
		if (is_null($moment_fin))
		{
			if (is_null($this->momentfin))
				echo "Demande->moment_fin : La demie-journée de fin n'est pas définie !!! <br>";
			else
			{
				if ($this->momentfin == 'm')
					return "m";
					//return "matin";
				elseif ($this->momentfin == 'a')
					return "a";
					//return "après-midi";
				else
					echo "Demande->moment_fin : la demie-journée n'est pas connu [momentfin = $this->momentfin] !!! <br>";
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->momentfin = $moment_fin;
			else
				echo "Demande->moment_fin : Impossible de modifier la demie-journée de fin si la demande est enregistrée !!! <br>";
		}
	}
	
	function commentaire($comment = null)
	{
		if (is_null($comment))
			return str_replace("''","'",$this->commentaire);
		else
			$this->commentaire = str_replace("'","''",$comment);

	}

	function nbrejrsdemande($nbrejrs = null)
	{
		if (is_null($nbrejrs))
		{
			if (is_null($this->nbrejrsdemande))
				echo "Demande->nbrejrsdemande : Le nombre de jours demandés n'est pas défini !!! <br>";
			else
			{
				return (float)$this->nbrejrsdemande; // number_format($this->nbrejrsdemande,1);
			}
		}
		else
		{
			if (is_null($this->demandeid))
				$this->nbrejrsdemande = $nbrejrs;
			else
				echo "Demande->nbrejrsdemande : Impossible de modifier le nombre de jours si la demande est enregistrée !!! <br>";
		}
	}
	
	function date_demande()
	{
		if (is_null($this->demandeid))
			echo "Demande->date_demande : La demande n'est pas enregistrée, donc pas de date de demande !!! <br>";
		else
			return $this->fonctions->formatdate($this->datedemande);
	}

	function datestatut()
	{
		if (is_null($this->demandeid))
			echo "Demande->datestatut : La demande n'est pas enregistrée, donc pas de date de statut !!! <br>";
		else
			return $this->fonctions->formatdate($this->datestatut);
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->demandeid))
				echo "Demande->statut : La demande n'est pas enregistrée, donc pas de statut !!! <br>";
			else
			{
				if (strcasecmp($this->statut,'v')==0 or (strcasecmp($this->statut,'a')==0 or strcasecmp($this->statut,'r')==0))
					return $this->statut;
				else
					echo "Demande->statut : le statut n'est pas connu [statut = $this->statut] !!! <br>";
			}
		}
		else
		{
			if (strcasecmp($this->statut,'a')==0 or (strcasecmp($this->statut,'v')==0 and strcasecmp($statut,'r')==0))
			{
				$this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
				$this->statut = $statut;
			}
			else
				echo "Le statut actuel est : " . $this->statut . " ===> Impossible de le passer au statut : " . $statut . "<br>";
		}
	}
	
//	function statutlibelle()
//	{
//		if (is_null($this->demandeid))
//			echo "Demande->statutlibelle : La demande n'est pas enregistrée, donc pas de statut !!! <br>";
//		else
//		{
//			if (strcasecmp($this->statut,'v') == 0)
//				return "Validée";
//			elseif (strcasecmp($this->statut,'r') == 0)
//				return "Refusée";
//			elseif (strcasecmp($this->statut,'a') == 0)
//				return "En attente";
//			else
//				echo "Demande->statutlibelle : le statut n'est pas connu [statut = $this->statut] !!! <br>";
//		}
//	}

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
	
	function declarationTPliste()
	{
		$sql = "SELECT DECLARATIONID FROM DEMANDEDECLARATIONTP WHERE DEMANDEID= '" . $this->demandeid . "'";
		//echo "Demande declarationTPListe sql = $sql <br>";
		$query=mysql_query ($sql,$this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Demande->declarationTPliste : " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
			echo "Demande->declarationTPliste : Pas de déclaration de TP pour la demande " . $this->demandeid . " <br>";
		$declaliste = null;
		while ($result = mysql_fetch_row($query))
		{
			$declaration = new declarationTP($this->dbconnect);
			$declaration->load($result[0]);
			$declaliste[] = $declaration;
			unset ($declaration);
		}
		return $declaliste;
	}
	
	function agent()
	{
		if (is_null($this->agent))
		{
			$sql = "SELECT HARPEGEID FROM AFFECTATION,DECLARATIONTP,DEMANDEDECLARATIONTP WHERE DEMANDEDECLARATIONTP.DEMANDEID='" . $this->demandeid . "'";
			$sql = $sql . " AND DEMANDEDECLARATIONTP.DECLARATIONID = DECLARATIONTP.DECLARATIONID ";
			$sql = $sql . " AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->agent : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->agent : Pas d'agent trouvé pour la demande " . $this->demandeid . " <br>";
			$result = mysql_fetch_row($query);
			$agent = new agent($this->dbconnect);
			$agent->load("$result[0]");
			$this->agent = $agent;
		}
		return $this->agent;
	}
	
	function store($declarationTPListe = null, $ignoreabsenceautodecla = FALSE, $ignoresoldeinsuffisant = FALSE)
	{
		//echo "Demande->store : En cours de réécriture !!!!! <br>";
		
	
		if (is_null($this->demandeid))
		{
			if (!is_array($declarationTPListe))
			{
				echo "Demande->Store : La liste des declarationsTP n'est pas un tableau <br>";
				return ;
			}
	
			$declarationTP = new declarationTP($this->dbconnect);
			$declarationTP = reset($declarationTPListe);
			$affectationid = $declarationTP->affectationid();
			$affectation = new affectation($this->dbconnect);
			$affectation->load($affectationid);
			
			// On vérifie que le nombre de jour demandé est >= Nbre de jour restant (si c'est un conge !!)
			//echo "Demande->Store : typdemande=". $this->typdemande . "<br>";
			if ($this->fonctions->estunconge($this->typeabsenceid))
			{
				//echo "C'est un congé... <br>";
				unset ($solde);
				$solde = new solde($this->dbconnect);
				$solde->load($affectation->agentid(),$this->typeabsenceid );
			}

			//echo "datedemande = " . $this->datedemande;
			if (is_null($this->nbrejrsdemande))
			{
				//echo "Le nbre jour est nul ==> On demande le nombre de jour <br>";
				$planning = new planning($this->dbconnect);
				//echo "this->agentid" . $this->agentid  . "<br>";
				//echo "this->fonctions->formatdate($this->datedebut) " .  $this->fonctions->formatdate($this->datedebut) . "<br>";
				//echo "this->demijrs_debut " . $this->demijrs_debut  . "<br>";
				//echo "this->fonctions->formatdate($this->datefin) " .  $this->fonctions->formatdate($this->datefin) . "<br>";
				//echo "this->demijrs_fin " . $this->demijrs_fin  . "<br>";
				//echo "ignoreabsenceautodecla " . $ignoreabsenceautodecla  . "<br>";

				$this->nbrejrsdemande = $planning->nbrejourtravaille($affectation->agentid(), $this->fonctions->formatdate($this->datedebut), $this->momentdebut, $this->fonctions->formatdate($this->datefin), $this->momentfin, $ignoreabsenceautodecla);
				//echo "nbredemijrs_demande = " . $this->nbredemijrs_demande . "<br>";
			}
				
			$nbjrrestant = 0;
			if ($this->fonctions->estunconge($this->typeabsenceid))
			{
				if (is_null($solde))
					echo "Demande->Store : Pas de solde pour le type de demande " . $this->typeabsenceid . " et l'agent " . $affectation->agentid() . "<br>";
				else
				{
					$nbjrrestant = $solde->droitaquis() - $solde->droitpris();
					//echo "solde->droitaquis_demijrs() - solde->droitpris_demijrs() ==> " . $solde->droitaquis_demijrs() . "  -  " . $solde->droitpris_demijrs() . "<br>";
				}
			}
			
			//echo "Nombre de jours restant = " . $nbjrrestant . "   nbredemijrs_demande = " .  $this->nbredemijrs_demande . " <br>";
			if (($nbjrrestant >= $this->nbrejrsdemande) or (!$this->fonctions->estunconge($this->typeabsenceid)) or ($ignoresoldeinsuffisant == TRUE))
			{
				if ($this->nbrejrsdemande == 0)
					return "Le nombre de jour demandé est égal à 0. <br>";
				// On est dans le cas d'une création de demande
				$this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));

				$sql = "LOCK TABLES DEMANDE WRITE";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "SET AUTOCOMMIT = 0";
	 			mysql_query($sql,$this->dbconnect);
				$sql = "INSERT INTO DEMANDE(TYPEABSENCEID,DATEDEBUT,MOMENTDEBUT,DATEFIN,MOMENTFIN,
				        COMMENTAIRE,NBREJRSDEMANDE,DATEDEMANDE,DATESTATUT,STATUT,MOTIFREFUS) ";
				$sql = $sql . "VALUES('" . $this->typeabsenceid . "','" . $this->fonctions->formatdatedb($this->datedebut) . "',";
				$sql = $sql . "'" . $this->momentdebut . "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->momentfin . "',";
				$sql = $sql . "'" . $this->commentaire . "',";
				$sql = $sql . "'" . $this->nbrejrsdemande . "','" . $this->fonctions->formatdatedb($this->datedemande) . "','','a','')";
				//echo "SQL = " . $sql . "<br>";
	 			mysql_query($sql,$this->dbconnect);
	 			$erreur=mysql_error();
	 			if ($erreur != "")
	 				echo "Demande->store : " . $erreur . "<br>";
//	 			$sql = "SELECT LAST_INSERT_ID()";
//	 			$toto = mysql_query($sql,$this->dbconnect);
//	 			echo "toto = "; print_r($toto); echo "   toto[1] = " . $toto[1]  . "<br>";
//	 			echo "toto(2) = $toto <br>";
//	 			echo "Dernier indice = " . mysql_insert_id($this->dbconnect) . "<br>";
				$this->demandeid = mysql_insert_id($this->dbconnect);
	 			//$this->demandeid
	 			$sql = "COMMIT";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "UNLOCK TABLES";
	 			mysql_query($sql,$this->dbconnect);
	 			$sql = "SET AUTOCOMMIT = 1";
	 			mysql_query($sql,$this->dbconnect);
	 			
	 			// On sauvegarde le lien entre la/les declaration(s) de TP et la demande
	 			foreach ($declarationTPListe as $key => $declaration)
	 			{
	 				$sql = "INSERT INTO DEMANDEDECLARATIONTP(DEMANDEID,DECLARATIONID) VALUES('" . $this->demandeid . "','" . $declaration->declarationTPid() ."')";
	 				//echo "sql = $sql <br>";
					mysql_query ($sql,$this->dbconnect);
					$erreur=mysql_error();
					if ($erreur != "")
						echo "Demande->store (DEMANDEDECLARATIONTP) : " . $erreur . "<br>";
	 			}

	 			// On decompte le nombre de jours que l'on vient de poser
				if ($this->fonctions->estunconge($this->typeabsenceid))
				{
					$sql = "UPDATE SOLDE
					  		 SET DROITPRIS = DROITPRIS + " . $this->nbrejrsdemande . "
							 WHERE TYPEABSENCEID='" . $this->typeabsenceid . "' AND HARPEGEID = '" . $affectation->agentid()  . "'";				
					//echo "SQL = $sql  <br>";
					mysql_query ($sql,$this->dbconnect);
					$erreur=mysql_error();
					if ($erreur != "")
						echo "Demande->store (SOLDE) : " . $erreur . "<br>";
				}
				$this->ancienstatut = "a";
			}
			else
				return "Nombre de jours insuffisants ==> Demandé = " . ($this->nbrejrsdemande) . " Solde restant : " . ($nbjrrestant) . " !!! <br>";
		}
		else
		{
		
			if (strcasecmp($this->ancienstatut,"r")==0)
			{
				return "Impossible de changer le statut d'une demande 'refusée'!! <br>";
			}
			else
			{
				// On est dans le cas d'une modification de demande
				$sql = "UPDATE DEMANDE
						SET DATESTATUT='" . $this->fonctions->formatdatedb($this->datestatut) . "'
						  , STATUT='" . $this->statut . "', MOTIFREFUS='" . $this->motifrefus  . "'
						 WHERE DEMANDEID=" . $this->demandeid;				
				//echo "SQL = $sql  <br>";
	 			$query=mysql_query ($sql,$this->dbconnect);
	 			$erreur=mysql_error();
	 			if ($erreur != "")
	 				echo "Demande->store : " . $erreur . "<br>";
	 			if (strcasecmp($this->ancienstatut,"r")!=0 and strcasecmp($this->statut,"r")==0)
	 			{
	 				// On recrédite le nombre de jours dans les congés....
	 				$sql = "UPDATE SOLDE
						  		 SET DROITPRIS = DROITPRIS - " . $this->nbrejrsdemande . "
								 WHERE TYPEABSENCEID='" . $this->typeabsenceid . "' AND HARPEGEID = '" . $this->agent()->harpegeid()  . "'";
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
		//if (!defined('FPDF_FONTPATH')) 
		//	define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage();
		$pdf->Image('../html/images/logo_papeterie.png',70,25,60,20);
		
//		if (is_null($this->structureid) or $this->structureid=="")
//		{
//			//echo "Le code de la structure est vide...<br>";
//			$agent=new agent($this->dbconnect);
//			$agent->load($this->agentid);
//			$this->structure($agent->structure()->id());
//			//echo "Apres le load de la structure du responsable... <br>";
//		}
		
		$pdf->SetFont('Arial','B',16);
		$pdf->Ln(70);
//		$pdf->Cell(60,10,'Composante : '. $this->structure()->parentstructure()->nomlong() .' ('. $this->structure()->parentstructure()->nomcourt() .')' );
//		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);

		$agent = $this->agent();
		$affectationliste = $agent->affectationliste($this->datedebut, $this->datefin);
		foreach ($affectationliste as $key => $affectation)
		{
			$structure = new structure($this->dbconnect);
			$structure->load($affectation->structureid());
			$nomstructure = $structure->nomlong() . " (" . $structure->nomcourt()  .")";
			$pdf->Cell(60,10,'Service : '. $nomstructure);
			$pdf->Ln();
		}
		

//		$pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
//		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		if ($this->fonctions->estunconge($this->typeabsenceid))
			$typelib = " de congé ";
		else 
			$typelib = " d'autorisation d'absence ";
		$pdf->Cell(60,10,'Demande' . $typelib .  'N°'. $this->id() .' de ' . $this->agent()->civilite() . " " . $this->agent()->nom() . " " . $this->agent()->prenom() );
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		$decision = strtolower($this->fonctions->demandestatutlibelle($this->statut()));
		
//		if($this->statut()=='v')
//			$decision='validée';
//		else
//			$decision='refusée';
		
		$pdf->Cell(40,10,'Votre demande ' .  $typelib  . 'du '. $this->datedebut() .' '. $this->fonctions->nommoment($this->momentdebut) . ' au '.$this->datefin().' '.$this->fonctions->nommoment($this->momentfin) . ' ');
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
		$pdf->Cell(40,10,'Date de validation : '.$this->datestatut());
		$pdf->Ln(10);
		if($this->statut()=='v')
		{
			if ($this->fonctions->estunconge($this->type()))
				$pdf->Cell(40,10,'Nombre de jour(s) comptabilisé(s) : '.($this->nbrejrsdemande()));
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

		$tabsolde = $agent->soldecongesliste($this->fonctions->anneeref());
		if (is_array($tabsolde))
		{
			foreach ($tabsolde as $key => $solde)
			{
				$pdf->Cell(70,7,$solde->typelibelle(),1);
				$pdf->Cell(25,7,(string)($solde->droitaquis()),1);
				$pdf->Cell(25,7,(string)($solde->droitpris()),1);
				$pdf->Cell(25,7,(string)($solde->solde()),1);
				$pdf->Ln();
				$pdf->SetFont('Arial','B',9);
				$pdf->Cell(25,10,'');
			}
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