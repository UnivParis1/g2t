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
	private $dbconnect = null;
	private $agent = null;
	private $ancienfin = null;
	private $anciendebut = null;
	
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
	
//	function statutlibelle()
//	{
//		if (is_null($this->declarationid))
//			echo "DeclarationTP->statutlibelle : La déclaration de TP n'est pas enregistrée, donc pas de statut !!! <br>";
//		else
//		{
//			if (strcasecmp($this->statut,'v') == 0)
//				return "Validée";
//			elseif (strcasecmp($this->statut,'r') == 0)
//				return "Refusée";
//			elseif (strcasecmp($this->statut,'a') == 0)
//				return "En attente";
//			else
//				echo "DeclarationTP->statutlibelle : le statut n'est pas connu [statut = $this->statut] !!! <br>";
//		}
//	}
	
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
		{
			if (is_null($this->anciendebut))
				$this->anciendebut = $this->datedebut;
			$this->datedebut = $this->fonctions->formatdatedb($date);
		}
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
		{
			if (is_null($this->ancienfin))
				$this->ancienfin = $this->datefin;
			$this->datefin = $this->fonctions->formatdatedb($date);
		}
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
			if (is_null($this->tabtpspartiel))
				echo "DeclarationTP->tabtpspartiel : Le tableau des temps partiels n'est pas défini (NULL) !!! <br>";
			else
				return $this->tabtpspartiel;
		}
		else 
		{
			$this->tabtpspartiel = $tableauTP;
		}
		//echo "DeclarationTP->initTP : "; print_r($this->tabrtt); echo "<br>";
	}
	
	function tabtpspartielhtml($pour_modif = false)
	{
		$htmltext = "";
		$htmltext = $htmltext . "<tr class='entete'>";
		$htmltext = $htmltext . "<td></td>";
 		for ($indexjrs=1; $indexjrs<6; $indexjrs++)
 		{
 		//echo "indexjrs = $indexjrs <br>";
 			$htmltext = $htmltext . "<td colspan='2' style='width:50px'>" . $this->fonctions->nomjourparindex($indexjrs) . "</td>";
		}
		$htmltext = $htmltext . "</tr>";
		$checkboxname = null;
		for ($semaine=0; $semaine <2 ; $semaine++)
		{
			$htmltext = $htmltext . "<tr class='ligneplanning'><td>Semaine ";
	 		if ($semaine==0)
	 			$htmltext = $htmltext . "paire</td>";
	 		else
	 			$htmltext = $htmltext . "impaire</td>";
					
			for ($indexelement=0 ; $indexelement<10 ; $indexelement++) 
			{
				unset($element);
				$element = new planningelement($this->dbconnect);
				if ($indexelement%2 == 0)
					$element->moment("m");
				else
					$element->moment("a");
				if ($pour_modif)
					$checkboxname = $indexelement + ($semaine * 10); //  $this->fonctions->nomjourparindex(((int)($indexelement/2))+1) . "_" . $element->moment() . "_" . $semaine;
				if (substr($this->tabtpspartiel(), $indexelement + ($semaine * 10),1) == 1)
				{
					$element->type("tppar");
					$element->info("Temps partiel");
				}
				else
				{
					$element->type("");
					$element->info("");
				} 
				$htmltext = $htmltext . $element->html(false,$checkboxname);
				unset($element);
			}
			$htmltext = $htmltext ."</tr>";
		}
		return $htmltext;
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
	
	function agent()
	{
		if (is_null($this->agent))
		{
			$sql = "SELECT HARPEGEID FROM AFFECTATION,DECLARATIONTP WHERE DECLARATIONTP.DECLARATIONID='" . $this->declarationTPid() ."'";
			$sql = $sql . " AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID";
			$query=mysql_query ($sql,$this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Demande->agent : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Demande->agent : Pas d'agent trouvé pour la déclaration de TP " . $this->declarationTPid() . " <br>";
			$result = mysql_fetch_row($query);
			$agent = new agent($this->dbconnect);
			$agent->load("$result[0]");
			$this->agent = $agent;
		}
		return $this->agent;
		
	}
	
	function store()
	{
		
//		echo "DeclarationTP->store : non refaite !!!! <br>";
//		return false;
		
		//echo "On teste le nbre de tabrtt = " . count($this->tabrtt) . "<br>";
		if (strlen($this->tabtpspartiel) != 20)
			return "Le tableau des temps partiels n'est pas initialisé. L'enregistrement est impossible. <br>";
		
		//echo "id est null ==> " . $this->id . "<br>";
		if (is_null($this->declarationid))
		{
			$this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));

			$sql = "LOCK TABLES DECLARATIONTP WRITE";
			mysql_query($sql,$this->dbconnect);
			$sql = "SET AUTOCOMMIT = 0";
			mysql_query($sql,$this->dbconnect);
			$sql = "INSERT INTO DECLARATIONTP (AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT) ";
			$sql = $sql . " VALUES ('" . $this->affectationid  . "','" . $this->tabtpspartiel  ."',";
			$sql = $sql . "'" . $this->datedemande ."','" . $this->fonctions->formatdatedb($this->datedebut) .  "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->datedemande ."','" . $this->statut . "')";
			//echo "SQL = $sql   <br>";
			mysql_query($sql,$this->dbconnect);
	 		$erreur=mysql_error();
			if ($erreur != "")
				echo "DeclarationTP->store : " . $erreur . "<br>";
//			$sql = "SELECT LAST_INSERT_ID()";
//			$this->id = mysql_query($sql,$this->dbconnect);
			$this->declarationid = mysql_insert_id($this->dbconnect);
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
			// c'est une modification ...
			$sql = "UPDATE DECLARATIONTP SET ";
			$sql = $sql . " STATUT='" . $this->statut . "', DATEDEBUT='" . $this->datedebut . "', DATEFIN='" . $this->datefin  . "', DATESTATUT='" . $this->datestatut . "' ";
			$sql = $sql . "WHERE DECLARATIONID='" . $this->declarationid . "'";
			//echo "SQL = $sql   <br>";
			mysql_query($sql,$this->dbconnect);
	 		$erreur=mysql_error();
			if ($erreur != "")
				echo "DeclarationTP->store : " . $erreur . "<br>";
			if (!is_null($this->anciendebut) or (!is_null($this->ancienfin)))
			{
//				echo "###############################<br>";
//				echo "###### WARNING !!!!! Il faut penser a supprimer les demandes qui ne sont plus dans la période de TP #######<br>";
//				echo "########################################<br>";
//				echo "#### CA NE MARCHE PAS !!!!!!! A VERIFIER !!!!<br>";
//				echo "###############################<br>";
				if (is_null($this->anciendebut))
					$debut= $this->datedebut();
				else
					$debut = $this->anciendebut;
				if (is_null($this->ancienfin))
					$fin= $this->datefin();
				else
					$fin = $this->ancienfin;
				echo "debut = " . $this->fonctions->formatdate($debut) . "   datedebut = " . $this->datedebut() . "<br>";
				echo "fin = " . $this->fonctions->formatdate($fin) . "   datefin = " . $this->datefin() . "<br>";
				$demandelistedebut = $this->demandesliste($this->fonctions->formatdate($debut),$this->datedebut());
				$demandelistefin = $this->demandesliste($this->datefin(),$this->fonctions->formatdate($fin));
				$demandeliste = array_merge((array)$demandelistedebut,(array)$demandelistefin);
				echo "demandeliste = "; print_r($demandeliste); echo "<br>"; 
				if (is_array($demandeliste))
				{
					foreach ($demandeliste as $key => $demande)
					{
						if ($demande->statut() != "r")
						{
							$demande->statut("r");
							$demande->motifrefus("Modification de la déclaration de temps partiel - " . $this->datedebut() . "->" . $this->datefin());
							$demande->datestatut($this->fonctions->formatdatedb(date("d/m/Y")));
							$msg = $demande->store();
							if ($msg != "" )
								echo "STORE de la demande apres modification d'une declaration TP : " . $msg . "<br>";
						}
					}
				}
			}
			if ($this->statut == "r")
			{
				//echo "###############################<br>";
				//echo "###### WARNING !!!!! Il faut penser a supprimer les demandes qui sont associées à cette déclaration de TP #######<br>";
				//echo "###############################<br>";
				$demandeliste = $this->demandesliste($this->datedebut(),$this->datefin());
				if (!is_null($demandeliste))
				{
					foreach ($demandeliste as $key => $demande)
					{
						if ($demande->statut() != "r")
						{
							$demande->statut("r");
							$demande->motifrefus("Annulation de la déclaration de temps partiel - " . $this->datedebut() . "->" . $this->datefin());
							$demande->datestatut($this->fonctions->formatdatedb(date("d/m/Y")));
							$msg = $demande->store();
							if ($msg != "" )
								echo "STORE de la demande apres suppression d'une declaration TP : " . $msg . "<br>";
						}
					}
					
				}
			}
		}
		return "";
	}
	
	function html($pourmodif = FALSE, $structid = NULL)
	{
//		echo "DeclarationTP->html : non refaite !!!! <br>";
//		return false;

		$htmltext = "";
		$htmltext = $htmltext . "<tr>";

		if ($pourmodif)
			$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->agent()->identitecomplete() . "</td>";

//			$htmltext = $htmltext . "<input type='hidden' name='" .  $structid. "_" . $this->agent()->harpegeid() . "_autodeclaid_" . $this->declarationTPid() . "' value='" . $this->declarationTPid() ."'>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedemande() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedebut() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datefin() . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' align=center >";
		if ($pourmodif and $this->statut() == "a")
		{
			// Affichager les selections !!!!
			$htmltext = $htmltext . "<select name='statut[" . $this->declarationTPid() . "]'>";
			$htmltext = $htmltext . "<option value='a'"; if ($this->statut() == "a") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle('a') . "</option>";
			$htmltext = $htmltext . "<option value='v'"; if ($this->statut() == "v") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle('v') . "</option>";
			$htmltext = $htmltext . "<option value='r"; if ($this->statut() == "r") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . "'>" . $this->fonctions->declarationTPstatutlibelle('r') . "</option>";
			$htmltext = $htmltext . "</select>";
		}
		else
		{
			$htmltext = $htmltext . $this->fonctions->declarationTPstatutlibelle($this->statut());
//			switch ($this->statut())
//			{
//				case "v":
//					$htmltext = $htmltext .  "Validé";
//					break;
//				case "a":
//					$htmltext = $htmltext .  "En attente";
//					break;
//				case "r":
//					$htmltext = $htmltext .  "Refusé";
//					break;
//			}
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple'>";
		
		$elementliste = array(10);
		
		//echo "Le tableau des TP = " . $this->tabtpspartiel . "<br>";
 		$htmltext = $htmltext . "<div id='planning'>";
		$htmltext = $htmltext . "<table class='tableau'>";
		$htmltext = $htmltext . $this->tabtpspartielhtml();
		$htmltext = $htmltext ."</table>";
		$htmltext = $htmltext ."</div>";
		
		$htmltext = $htmltext . "</td>";
/*
		if ($pourmodif)
		{
			$htmltext = $htmltext . "<td class='cellulesimple' align=center >";
			$htmltext = $htmltext . "<input type='checkbox' name='declaannule[" . $this->declarationTPid() ."]' value='1'>";
			$htmltext = $htmltext . "</td>";
		}
*/
		$htmltext = $htmltext .  "</tr>";
		return $htmltext;
	}
	
	function pdf($valideurid)
	{
//		echo "DeclarationTP->pdf : non refaite !!!! <br>";
//		return false;
		
		//echo "Avant le new <br>";
		$pdf = new FPDF();
		//echo "Avant le define <br>";
		if (!defined('FPDF_FONTPATH'))
			define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage();
		$pdf->Image('images/logo_papeterie.png',70,25,60,20);
		//echo "Apres l'image... <br>";
		$pdf->SetFont('Arial','B',14);
		$pdf->Ln(50);
//		$pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
		$pdf->Ln(10);
		$pdf->Cell(60,10,'Demande de temps partiel N°'.$this->declarationTPid().' de '. $this->agent()->identitecomplete());
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',12);
		//echo "Avant le test statut <br>";
		$decision = strtolower($this->fonctions->declarationTPstatutlibelle($this->statut()));
//		if($this->statut()=='v')
//			$decision='validée';
//		else
//			$decision='refusée';
		
		$pdf->Cell(40,10,"La demande de temps partiel que vous avez déposée le " . $this->datedemande() .' a été '.$decision.' le '. $this->datestatut());
		$pdf->Ln(10);
		//echo "Avant test quotité <br>";
		$pdf->Cell(60,10,'Récapitulatif de votre demande de temps partiel pour la période du '.$this->datedebut().' au '.$this->datefin().'.');
		$pdf->Ln(10);

		for ($cpt = 0; $cpt <20 ; $cpt++)
		{
			//echo "calcul $cpt = " . ($cpt + ((int)($cpt / 7) * 7));
			if ($cpt==0)
			{
				$pdf->Cell(40,10,"Semaine paire : ");
				$pdf->Ln(10);
				$indexjrs = 0;
			}
			elseif ($cpt==10)
			{
				$pdf->Ln(10);
				$pdf->Cell(40,10,"Semaine impaire : ");
				$pdf->Ln(10);
				$indexjrs = 0;
			}
			
			$value = $this->tabtpspartiel[$cpt];
			if ($cpt%2 == 0)
				$indexjrs = $indexjrs+1;
			if ($value == "1")
			{
				$moment = ($cpt%2);
				//echo "    Value = " .  $value  . "   Index = " . $index . "   moment = " . $moment . "<br>";
				if ($moment == "0")
				{
					$pdf->Cell(40,10, $this->fonctions->nomjourparindex($indexjrs) . " " . $this->fonctions->nommoment("m"));
				}
				else
				{
					$pdf->Cell(40,10, $this->fonctions->nomjourparindex($indexjrs) . " " . $this->fonctions->nommoment("a"));
				}
				$pdf->Ln(10);
			}
			//echo "calcul v2 $cpt = " . ($cpt + ((int)($cpt / 7) * 7) + 7);

		}
		

			// $pdf->Cell(40,10,'Ajouter ici le tableau de récap pour les temps partiels');
		
		$pdf->Ln(15);
//		$pdf->Cell(25,5,'TP:Demi-journée non travaillée pour un temps partiel    WE:Week end');
		$pdf->Ln(10);

		$pdfname = './pdf/declarationTP_num'.$this->declarationTPid().'.pdf';
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