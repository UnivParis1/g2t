<?php

class declarationTP {
	
	private $declarationid = null;
	private $affectationid = null;
	private $tabtpspartiel = null;
	private $datedemande = null;
	private $datedebut = null;
	private $datefin = null;
	private $datestatut = null;
	private $statut = null;
	
	private $fonctions = null;
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "DeclarationTP->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function load($id = null)
	{
		if (is_null($id))
		{
			echo "DeclarationTP->Load : l'identifiant de la déclarationTP est NULL <br>";
		}
		else
		{
			$sql = "SELECT DECLARATIONID,AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT
FROM DECLARATIONTP
WHERE DECLARATIONID=" . $id;
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "DeclarationTP->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "DeclarationTP->Load : DeclarationTP $id non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->declarationid = "$result[0]";
			$this->affectationid = "$result[1]";
			$this->tabtpspartiel = "$result[2]";
			$this->datedemande = "$result[3]";
			$this->datedebut = "$result[4]";
			$this->datefin = "$result[5]";
			$this->datestatut = "$result[6]";
			$this->statut = "$result[7]";
		}
	}
	

	function declarationTPid()
	{
		if (is_null($this->declarationid))
			echo "DeclarationTP->id : L'Id n'est pas défini !!! <br>";
		else
			return $this->declarationid;
	}

	function affectationid($affectationid = null)
	{
		if (is_null($affectationid))
		{
			if (is_null($this->affectationid))
				echo "DeclarationTP->affectationid : L'Id de l'affectation n'est pas défini !!! <br>";
			else
				return $this->affectationid;
		}
		else
			$this->affectationid = $affectationid;
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->statut))
				echo "DeclarationTP->statut : Le statut n'est pas défini !!! <br>";
			else
				return $this->statut;
		}
		else
			$this->statut = $statut;
	}
	
	function datedebut($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datedebut))
				echo "DeclarationTP->datedebut : La date de début n'est pas définie !!! <br>";
			else
				return $this->fonctions->formatdate($this->datedebut);
		}
		else
			$this->datedebut = $this->fonctions->formatdatedb($date);
	}
	
	function datefin($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datefin))
				echo "DeclarationTP->datefin : La date de fin n'est pas définie !!! <br>";
			else
				return $this->fonctions->formatdate($this->datefin);
		}
		else
			$this->datefin = $this->fonctions->formatdatedb($date);
	}
	
	function datedemande($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datedemande))
				echo "DeclarationTP->datedemande : La date de la demande n'est pas définie !!! <br>";
			else
				return $this->fonctions->formatdate($this->datedemande);
		}
		else
			$this->datedemande = $this->fonctions->formatdatedb($date);
	}
	
	function datestatut($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datestatut))
				echo "DeclarationTP->datestatut : La date de fin n'est pas définie !!! <br>";
			else
				return $this->fonctions->formatdate($this->datestatut);
		}
		else
			$this->datestatut = $this->fonctions->formatdatedb($date);
	}
	
	function tabtpspartiel($tableauTP = null)
	{
		if (is_null($tableauTP))
		{
			echo "DeclarationTP->tabtpspartiel : Le tableau des temps partiels n'est pas défini (NULL) !!! <br>";
		}
		else 
		{
			$this->tabtpspartiel = $tableauTP;
		}
		//echo "DeclarationTP->initTP : "; print_r($this->tabrtt); echo "<br>";
	}
	
	function enTP($date = null, $moment = null)
	{
		if (is_null($date) or is_null($moment))
			echo "DeclarationTP->enTP : Au moins un des paramètres n'est pas défini (NULL) !!! <br>";
		elseif (is_null($this->tabtpspartiel))
			echo "DeclarationTP->enTP : Le tableau des RTT n'est pas initialisé !!! <br>";
		if (strlen($this->tabtpspartiel)<20)
			echo "DeclarationTP->enTP : Le tableau ne contient pas le nombre d'élément requis !!! <br>";

		$datedb = $this->fonctions->formatdatedb($date);
		// recupération du numéro du jour ==> 0 dimanche ... 6 Samedi
		$numerojour = date("w",strtotime($datedb));
		if ($numerojour == 0)
		{
			// On force le dimanche a 7
			$numerojour = 7;
		}
		//echo "Numero jour = $numerojour <br>";
		if ($numerojour >= 6)
		{
			//echo "Samedi ou Dimanche => Donc pas de TP <br>";
			return false;
		}
		// recupération du numéro de la semaine
		$numsemaine = date("W",strtotime($datedb));
		//echo "Numero de la semaine = $numsemaine <br>";
		$semainepaire = !(bool)($numsemaine % 2);
		if ($semainepaire)
		{
			//echo "Semaine paire <br>";
			$semaineindex = 0;
		}
		else
		{
			//echo "Semaine impaire <br>";
			$semaineindex = 1;
		}
		
		if ($moment == "m")
			$momentindex = 0;
		else 
			$momentindex = 1;
			 
		$index = (($numerojour - 1) * 2) + ($momentindex) + (10 * $semaineindex) ; 
		//echo "date =   $date    moment = $moment    index = $index   this->tabtpspartiel = " . $this->tabtpspartiel . "<br>";
		//echo "Le caractère= " . substr($this->tabtpspartiel, $index,1) . "<br>";
		if (substr($this->tabtpspartiel, $index,1) == "1")
		{
			//echo "Je return TRUE <br>";
			return true;
		}
		else
		{
			//echo "Je return FALSE <br>";
			return false;
		}
	}
	
	function store()
	{
		
		echo "DeclarationTP->store : non refaite !!!! <br>";
		return false;
		
		//echo "On teste le nbre de tabrtt = " . count($this->tabrtt) . "<br>";
		if (count($this->tabrtt) != 28)
			return "Les RTT ne sont pas initialisée. L'enregistrement est impossible. <br>";
		
		//echo "id est null ==> " . $this->id . "<br>";
		if (is_null($this->id))
		{
			$this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));

			$sql = "LOCK TABLES AUTODECLARATION WRITE";
			mysql_query($sql,$this->dbconnect);
			$sql = "SET AUTOCOMMIT = 0";
			mysql_query($sql,$this->dbconnect);
			$sql = "INSERT INTO AUTODECLARATION (ID_AUTODECLARATION,CODE,LMA1,MMA1,MEMA1,JMA1,VMA1,SMA1,DMA1,LMD1,MMD1,MEMD1,JMD1,VMD1,SMD1,DMD1,LMA2,MMA2,MEMA2,JMA2,VMA2,SMA2,DMA2,LMD2,MMD2,MEMD2,JMD2,VMD2,SMD2,DMD2,DEMIE_JRS_ATT1,STATUT_DEMANDE,D_DEB_DCL,D_FIN_DCL,D_DEMANDE_DCL,PROFIL_DCL)
			        VALUES ('','" . $this->agentid()  ."',";
			for ($tabindex = 1; $tabindex <= 28 ; $tabindex ++)
				$sql = $sql . "'" . $this->tabrtt[$tabindex] . "',";
			$sql = $sql . "'','a','" . $this->datedebut .  "','" . $this->datefin .  "','" . $this->datedemande ."','1')";
			//echo "SQL = $sql   <br>";
			mysql_query($sql,$this->dbconnect);
	 		$erreur=mysql_error();
			if ($erreur != "")
				echo "DeclarationTP->store : " . $erreur . "<br>";
			$sql = "SELECT LAST_INSERT_ID()";
			$this->id = mysql_query($sql,$this->dbconnect);
			$sql = "COMMIT";
			mysql_query($sql,$this->dbconnect);
			$sql = "UNLOCK TABLES";
			mysql_query($sql,$this->dbconnect);
			$sql = "SET AUTOCOMMIT = 1";
			mysql_query($sql,$this->dbconnect);
		}
		else
		{
			$this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
			$nomcol = array(1 => "LMA1","MMA1","MEMA1","JMA1","VMA1","SMA1","DMA1","LMD1","MMD1","MEMD1","JMD1","VMD1","SMD1","DMD1","LMA2","MMA2","MEMA2","JMA2","VMA2","SMA2","DMA2","LMD2","MMD2","MEMD2","JMD2","VMD2","SMD2","DMD2");
			// c'est une modification ...
			$sql = "UPDATE AUTODECLARATION  SET ";
			for ($index =1; $index <=28 ; $index++) 
					$sql = $sql . " " . $nomcol[$index] . "='" . $this->tabrtt[$index]  . "',";
			$sql = $sql . " STATUT_DEMANDE='" . $this->statut . "', D_DEB_DCL='" . $this->datedebut . "', D_FIN_DCL='" . $this->datefin  . "', D_VALID_DCL='" . $this->datestatut . "' ";
			$sql = $sql . "WHERE ID_AUTODECLARATION='" . $this->id() . "'";
			//echo "SQL = $sql   <br>";
			mysql_query($sql,$this->dbconnect);
	 		$erreur=mysql_error();
			if ($erreur != "")
				echo "DeclarationTP->store : " . $erreur . "<br>";
		}
		return "";
	}
	
	function html($pourmodif = FALSE, $structid = NULL)
	{
		echo "DeclarationTP->html : non refaite !!!! <br>";
		return false;
		
		
		$agent = new agent($this->dbconnect);
		$agent->load($this->agentid);

//		echo "Agent-> Nom = " . $agent->nom() . " " ;
//		print_r($this->tabrtt); echo "<br>";
		
		$htmltext = "";
		$htmltext = $htmltext . "<tr>";
		$htmltext = $htmltext . "<td class='cellulesimple'>" . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "</td>";
		$htmltext = $htmltext . "<input type='hidden' name='" .  $structid. "_" . $this->agentid() . "_autodeclaid_" . $this->id() . "' value='" . $this->id() ."'>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedemande() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedebut() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datefin() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >";
		if ($pourmodif and $this->statut() == "a")
		{
			// Affichager les selections !!!!
			$htmltext = $htmltext . "<select name='" .  $structid. "_" . $this->agentid() . "_statut_" . $this->id() . "'>";
			$htmltext = $htmltext . "<option value='a'"; if ($this->statut() == "a") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">En attente</option>";
			$htmltext = $htmltext . "<option value='v'"; if ($this->statut() == "v") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Validé</option>";
			$htmltext = $htmltext . "<option value='r"; if ($this->statut() == "r") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . "'>Refusé</option>";
			$htmltext = $htmltext . "</select>";
		}
		else
		{
			switch ($this->statut())
			{
				case "v":
					$htmltext = $htmltext .  "Validé";
					break;
				case "a":
					$htmltext = $htmltext .  "En attente";
					break;
				case "r":
					$htmltext = $htmltext .  "Refusé";
					break;
			}
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple'>";
		$tmphtml = "";
		for ($cpt = 0; $cpt <=13 ; $cpt++)
		{
			//echo "calcul $cpt = " . ($cpt + ((int)($cpt / 7) * 7)); 
			if ($cpt==0)
				$tmphtml = $tmphtml . "Semaine impaire : ";
			elseif ($cpt==7)
				$tmphtml = $tmphtml . "Semaine paire : ";
			$value = $this->tabrtt[($cpt + ((int)($cpt / 7) * 7))+1];
			// On regarde si c'est une heure ou si la cas est vide ==> L'agent est en TP ou RTT
			$result = strtotime($value) . "";
			if ($result == "" and $value != "")
			{
				$index = substr($value,0,1);
				$moment = substr($value,1,1);
				//echo "    Value = " .  $value  . "   Index = " . $index . "   moment = " . $moment . "<br>";
				$tmphtml = $tmphtml . $this->fonctions->nomjourparindex($index);
				if ($moment == "m")
					$tmphtml = $tmphtml . " matin";
				else
					$tmphtml = $tmphtml . " après-midi";
			}
			//echo "calcul v2 $cpt = " . ($cpt + ((int)($cpt / 7) * 7) + 7);
			$tmphtml = $tmphtml . " ";
			$value = $this->tabrtt[($cpt + ((int)($cpt / 7) * 7)  + 7)+1];
			$result = strtotime($value) . "";
			if ($result == "" and $value != "")
			{
				$index = substr($value,0,1);
				$moment = substr($value,1,1);
				//echo "      Value = " .  $value  . "   Index = " . $index . "   moment = " . $moment . "<br>";
				$tmphtml = $tmphtml . $this->fonctions->nomjourparindex($index);
				if ($moment == "m")
					$tmphtml = $tmphtml . " matin";
				else
					$tmphtml = $tmphtml . " après-midi";
			}
			if ($cpt == 6)
				$tmphtml = $tmphtml . "<br>";
			$tmphtml = $tmphtml . " ";
		}
		$htmltext = $htmltext . trim($tmphtml);
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "</tr>";
		
		return $htmltext;
	}
	
	function pdf($valideurid)
	{
		echo "DeclarationTP->pdf : non refaite !!!! <br>";
		return false;
		
		//echo "Avant le new <br>";
		$pdf = new FPDF();
		//echo "Avant le define <br>";
		define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage();
		$pdf->Image('images/logo_papeterie.png',70,25,60,20);
		//echo "Apres l'image... <br>";
		$pdf->SetFont('Arial','B',14);
		$pdf->Ln(50);
//		$pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
		$pdf->Ln(10);
		//echo "Avant le load agent <br>";
		$agent = new agent($this->dbconnect);
		$agent->load($this->agentid());
		//echo "Apres le load agent " .  $this->agentid()  . "<br>";
		$pdf->Cell(60,10,'Autodéclaration N°'.$this->id().' de '. $agent->civilite() . ' ' . $agent->nom() . ' '  . $agent->prenom());
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		//echo "Avant le test statut <br>";
		if($this->statut()=='v')
			$decision='validée';
		else
			$decision='refusée';
		
		$pdf->Cell(40,10,"L'autodéclaration que vous avez déposée le " . $this->datedemande() .' a été '.$decision.' le '. $this->datestatut());
		$pdf->Ln(10);
		//echo "Avant test quotité <br>";
		if ($agent->quotite() != "100%")
		{
			$pdf->Cell(60,10,'Récapitulatif de votre autodéclatation pour la période du '.$this->datedebut().' au '.$this->datefin().'.');
			$pdf->Ln(10);

			for ($cpt = 0; $cpt <=13 ; $cpt++)
			{
				//echo "calcul $cpt = " . ($cpt + ((int)($cpt / 7) * 7));
				if ($cpt==0)
				{
					$pdf->Cell(40,10,"Semaine impaire : ");
					$pdf->Ln(10);
				}
				elseif ($cpt==7)
				{
					$pdf->Cell(40,10,"Semaine paire : ");
					$pdf->Ln(10);
				}
				
				$value = $this->tabrtt[($cpt + ((int)($cpt / 7) * 7))+1];
				$result = strtotime($value) . "";
				if ($result == "" and $value != "")
				{
					$index = substr($value,0,1);
					$moment = substr($value,1,1);
					//echo "    Value = " .  $value  . "   Index = " . $index . "   moment = " . $moment . "<br>";
					if ($moment == "m")
					{
						$pdf->Cell(40,10, $this->fonctions->nomjourparindex($index) . " matin");
					}
					else
					{
						$pdf->Cell(40,10, $this->fonctions->nomjourparindex($index) . " après-midi");
					}
					$pdf->Ln(10);
				}
				//echo "calcul v2 $cpt = " . ($cpt + ((int)($cpt / 7) * 7) + 7);

				$value = $this->tabrtt[($cpt + ((int)($cpt / 7) * 7)  + 7)+1];
				$result = strtotime($value) . "";
				if ($result == "" and $value != "")
				{
					$index = substr($value,0,1);
					$moment = substr($value,1,1);
					//echo "      Value = " .  $value  . "   Index = " . $index . "   moment = " . $moment . "<br>";
					if ($moment == "m")
					{
						$pdf->Cell(40,10, $this->fonctions->nomjourparindex($index) . " matin");
					}
					else
					{
						$pdf->Cell(40,10, $this->fonctions->nomjourparindex($index) . " après-midi");
					}
					$pdf->Ln(10);
				}
				if ($cpt == 6)
					$pdf->Ln(10);
			}
		

			// $pdf->Cell(40,10,'Ajouter ici le tableau de récap pour les temps partiels');
		}
		
		$pdf->Ln(15);
//		$pdf->Cell(25,5,'TP:Demi-journée non travaillée pour un temps partiel    WE:Week end');
		$pdf->Ln(10);

		$pdfname = './pdf/autodeclaration_num'.$this->id().'.pdf';
		//$pdfname = sys_get_temp_dir() . '/autodeclaration_num'.$this->id().'.pdf';
		//echo "Nom du PDF = " . $pdfname . "<br>";
		$pdf->Output($pdfname);
		return $pdfname;
		
	}

	function demandesliste($debut_interval,$fin_interval)
	{
		$debut_interval = $this->fonctions->formatdatedb($debut_interval);
		$fin_interval = $this->fonctions->formatdatedb($fin_interval);
		$demande_liste = null;
		
		$sql = "SELECT DISTINCT DEMANDE.DEMANDEID FROM DEMANDEDECLARATIONTP,DEMANDE WHERE DEMANDEDECLARATIONTP.DECLARATIONID = '" . $this->declarationid . "' 
  				 AND DEMANDEDECLARATIONTP.DEMANDEID = DEMANDE.DEMANDEID
		       AND ((DATEDEBUT <= '" . $this->fonctions->formatdatedb($debut_interval) . "' AND DATEFIN >='" . $this->fonctions->formatdatedb($debut_interval) . "')
					OR (DATEFIN >= '" . $this->fonctions->formatdatedb($fin_interval) . "' AND DATEDEBUT <='" . $this->fonctions->formatdatedb($fin_interval) . "')
					OR (DATEDEBUT >= '" . $this->fonctions->formatdatedb($debut_interval) . "' AND DATEFIN <= '" . $this->fonctions->formatdatedb($fin_interval) . "'))
		ORDER BY DATEDEBUT";
		//echo "declarationTP->demandeliste SQL = $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "declarationTP->demandesliste : " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
		{
			//echo "declarationTP->demandesliste : Il n'y a pas de demande de congé/absence pour ce TP " . $this->declarationid . "<br>";
		}
		while ($result = mysql_fetch_row($query))
		{
			$demande = new demande($this->dbconnect);
			//echo "Agent->demandesliste : Avant le load " . $result[0]  . "<br>";
			$demande->load("$result[0]");
			//echo "Agent->demandesliste : Apres le load <br>";
			$demande_liste[$demande->id()] = $demande;
			unset($demande);
		}
		//echo "declarationTP->demandesliste : demande_liste = "; print_r($demande_liste); echo "<br>"; 
		return $demande_liste;
	}
	
}	

?>