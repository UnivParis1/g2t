<?php

class autodeclaration {
	
	private $id = null;
	private $agentid = null;
	private $tabrtt = null;
	private $statut = null;
	private $datedebut = null;
	private $datefin = null;
	private $datedemande = null;
	private $datestatut = null;
	
	private $fonctions = null;
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Autodeclaration->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function load($id = null)
	{
		if (is_null($id))
		{
			echo "Autodeclaration->Load : l'identifiant de l'autodéclaration est NULL <br>";
		}
		else
		{
			$sql = "SELECT ID_AUTODECLARATION,CODE,C_STRUCTURE,LMA1,MMA1,MEMA1,JMA1,VMA1,SMA1,DMA1,LMD1,MMD1,MEMD1,JMD1,VMD1,SMD1,DMD1,LMA2,MMA2,MEMA2,JMA2,VMA2,SMA2,DMA2,LMD2,MMD2,MEMD2,JMD2,VMD2,SMD2,DMD2,DEMIE_JRS_ATT1,DEMIE_JRS_ATT2,STATUT_DEMANDE,D_DEB_DCL,D_FIN_DCL,D_DEMANDE_DCL,D_VALID_DCL,PROFIL_DCL,TYPE_DCL
FROM AUTODECLARATION
WHERE ID_AUTODECLARATION=" . $id;
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Autodeclaration->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Autodeclaration->Load : Autodeclaration $id non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->id = "$result[0]";
			$this->agentid = "$result[1]";
			for ($index = 1; $index<=28 ; $index ++ )
				$this->tabrtt[$index] = $result[($index+2)];
			$this->statut = "$result[33]";
			$this->datedebut = "$result[34]";
			$this->datefin = "$result[35]";
			$this->datedemande = "$result[36]";
			$this->datestatut = "$result[37]";
		}
	}
	

	function id()
	{
		if (is_null($this->id))
			echo "Autodeclaration->id : L'Id n'est pas défini !!! <br>";
		else
			return $this->id;
	}

	function agentid($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->agentid))
				echo "Autodeclaration->agentid : L'Id de l'agent n'est pas défini !!! <br>";
			else
				return $this->agentid;
		}
		else
			$this->agentid = $agentid;
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->statut))
				echo "Autodeclaration->statut : Le statut n'est pas défini !!! <br>";
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
				echo "Autodeclaration->datedebut : La date de début n'est pas définie !!! <br>";
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
				echo "Autodeclaration->datefin : La date de fin n'est pas définie !!! <br>";
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
				echo "Autodeclaration->datedemande : La date de la demande n'est pas définie !!! <br>";
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
				echo "Autodeclaration->datestatut : La date de fin n'est pas définie !!! <br>";
			else
				return $this->fonctions->formatdate($this->datestatut);
		}
		else
			$this->datestatut = $this->fonctions->formatdatedb($date);
	}
	
	function initTP($tableauTP = null)
	{
		if (is_null($tableauTP))
		{
			echo "Autodeclaration->initTP : Le tableau des temps partiels n'est pas défini (NULL) !!! <br>";
		}
		else 
		{
			unset ($this->tabrtt);
			$this->tabrtt = array_fill(1, 28, "");
			foreach ($tableauTP as $key => $value)
				$this->tabrtt[$key] = "$value";
		}
		//echo "Autodeclaration->initTP : "; print_r($this->tabrtt); echo "<br>";
	}
	
	function enrtt($date = null, $moment = null)
	{
		if (is_null($date) or is_null($moment))
			echo "Autodeclaration->enrtt : Au moins un des paramètres n'est pas défini (NULL) !!! <br>";
		elseif (is_null($this->tabrtt))
			echo "Autodeclaration->enrtt : Le tableau des RTT n'est pas initialisé !!! <br>";
		
		//echo "Date = $date   moment = $moment  <br>";
		$datedb = $this->fonctions->formatdatedb($date);
		// recupération du numéro du jour ==> 0 dimanche ... 6 Samedi
		$numerojour = date("w",strtotime($datedb));
		if ($numerojour == 0)
		{
			// On force le dimanche a 7
			$numerojour = 7;
		}
		//echo "Numero jour = $numerojour <br>";
		// recupération du numéro de la semaine
		$numsemaine = date("W",strtotime($datedb));
		//echo "Numero de la semaine = $numsemaine <br>";
		$semainepaire = !(bool)($numsemaine % 2);
		if ($semainepaire)
		{
			//echo "Semaine paire <br>";
			$semaineindex = 1;
		}
		else
		{
			//echo "Semaine impaire <br>";
			$semaineindex = 0;
		}
		if ($moment == "m")
			$momentindex = 0;
		else 
			$momentindex = 1; 
		//echo "Momentindex = $momentindex <br>";
		$index = ($numerojour) + ($momentindex * 7 ) + ($semaineindex * 14) ; 
		//$index = ($numerojour-1) + ($momentindex * 7 ) + ($semaineindex * 14) + 1 ; 
		//print_r ($this->tabrtt); echo "<br>";
		//echo "index = $index   valeur = " .  $this->tabrtt[$index]  . "    strtotime ==> " . strtotime($this->tabrtt[$index]) . "<br>";
		// On converti le contenu du tableau en heure 
		$result = strtotime($this->tabrtt[$index]) . "";
		//echo "result = $result <br>";
		// Si le contenu est une heure ou si la case est vide ==> L'agent n'est pas en RTT (ou TP)
		if ($result != "" or $this->tabrtt[$index] == "")
		{
			//echo "Pas de RTT pour la date $date et le moment $moment <br>";
		   return FALSE;
		}
		// Si c'est pas une heure et que la case n'est pas vide ==> L'agent est en RTT (ou TP)
		else
		{
			//echo "Il y a une RTT pour la date $date et le moment $moment <br>";
			return TRUE;
		}
			
	}
	
	function store()
	{
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
				echo "Autodeclaration->store : " . $erreur . "<br>";
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
				echo "Autodeclaration->store : " . $erreur . "<br>";
		}
		return "";
	}
	
	function html($pourmodif = FALSE, $structid = NULL)
	{
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
}	

?>