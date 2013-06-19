<?php

class structure {
	private $dbconnect = null;
	private $structureid = null;
	private $nomlong = null;
	private $nomcourt = null;
	private $parentid = null;
	private $responsableid = null;
	private $gestionnaireid = null;
	private $affichesousstruct = null; // permet d'afficher les agents des sous structures
	private $affichetoutagent = null; // permet d'afficher le planning de la structure pour tous les agents de la stucture
	
	private $fonctions = null;
	
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Structure->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function load($structureid)
	{
		if (is_null($this->structureid))
		{
			$sql = "SELECT STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID,GESTIONNAIREID,AFFICHESOUSSTRUCT,AFFICHEPLANNINGTOUTAGENT FROM STRUCTURE WHERE STRUCTUREID='" . $structureid . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Structure->Load (STRUCTURE) : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
			{
				//echo "Structure->Load (STRUCTURE) : Structure $structureid non trouvé <br>";
	 			$this->nomcourt = "$structureid";
 				$this->nomlong = "Structure inconnue";
				return;
			}
			$result = mysql_fetch_row($query);
 			$this->structureid = "$result[0]";
 			$this->nomlong = "$result[1]";
 			$this->nomcourt = "$result[2]";
 			$this->parentid = "$result[3]";
 			$this->responsableid = "$result[4]";
 			$this->gestionnaireid = "$result[5]";
			$this->affichesousstruct = "$result[6]";
 			$this->affichetoutagent = "$result[7]";
		}
	}
	
	function id()
	{
		return $this->structureid;
	}
	
	function nomlong($name = null)
	{
		if (is_null($name))
		{
			if (is_null($this->nomlong))
				echo "Structure->nomlong : Le nom de la structure n'est pas défini !!! <br>";
			else
				return $this->nomlong;
		}
		else
			$this->nomlong = $name;
	}

	function nomcourt($name = null)
	{
		if (is_null($name))
		{
			if (is_null($this->nomcourt))
				echo "Structure->nomcourt : Le nom de la structure n'est pas défini !!! <br>";
			else
				return $this->nomcourt;
		}
		else
			$this->nomcourt = $name;
	}
	
	function affichetoutagent($affiche = null)
	{
		if (is_null($affiche))
		{
			if (is_null($this->affichetoutagent))
				echo "Structure->affichetoutagent : Le parametre affichetoutagent de la structure n'est pas défini !!! <br>";
			else
				return $this->affichetoutagent;
		}
		else
			$this->affichetoutagent = $affiche;
	}
	
	
	function sousstructure($sousstruct = null)
	{
		if (is_null($sousstruct))
		{
			if (is_null($this->affichesousstruct))
				echo "Structure->sousstructure : Le parametre sousstructure de la structure n'est pas défini !!! <br>";
			else
				return $this->affichesousstruct;
		}
		else
			$this->affichesousstruct = $sousstruct;
	}
	
	function structurefille()
	{
		$structureliste = null;
		if (!is_null($this->structureid))
		{
			$sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT='" . $this->structureid . "'"; 
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Structure->structurefille : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
			{
				//echo "Structure->structurefille : La structure $this->structureid n'a pas de structure fille<br>";
			}
			while ($result = mysql_fetch_row($query))
			{
				$structure = new structure($this->dbconnect);
				$structure->load("$result[0]");
				$structureliste[$structure->id()] = $structure;
				unset($structure);
			}
			return $structureliste;
		}		
	}
	
	function agentlist($datedebut, $datefin, $sousstrucuture = null)
	{
		$agentliste=null;
		if (($this->affichesousstruct == 'o' and $sousstrucuture != 'n' ) or ($sousstrucuture == 'o'))
		{
			$structliste = $this->structurefille();
			if (!is_null($structliste))
			{
				foreach ($structliste as $key => $structure)
				{
					$agentliste = array_merge((array)$agentliste, $structure->agentlist($datedebut,$datefin,'o'));
				}
			}
 		}
 		
 		//echo "Liste finale des agents : <br>";
 		//print_r($this->agentliste); echo "<br>";
 
 		$sql = "SELECT SUBREQ.HARPEGEID FROM ((SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID = '" . $this->structureid . "' AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN OR DATEFIN='0000-00-00'))";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
		$sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";

		//echo "SQL (agentlist) = $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Structure->agentlist : " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
			echo "Structure->agentlist : La structure $this->structureid n'a pas d'agent<br>";
		//echo "Avant le while...<br>";
		while ($result = mysql_fetch_row($query))
		{
			$agent = new agent($this->dbconnect);
			//echo "Apres le new et avant le load =" . $result[0]  . "<br>";
			$agent->load("$result[0]");
			//echo "Apres le load...<br>";
			$agentliste[$agent->nom() . " " . $agent->prenom() . " " . $agent->harpegeid()] = $agent;
			//echo "Apres la mise dans le tableau <br>";
			unset($agent);
		}
		ksort($agentliste);
		return $agentliste;
	}
			
	
	function parentstructure()
	{
		if (is_null($this->parentid))
			echo "Structure->parentstructure : La structure parente n'est pas définie !!! <br>";
		else
		{
			$parentstruct = new structure($this->dbconnect);
			$parentstruct->load("$this->parentid");
			return $parentstruct;
		}
	}

	function responsable($respid = null)
	{
		if (is_null($respid))
		{
			if (is_null($this->responsableid))
				echo "Structure->Responsable : Le responsable de la structure n'est pas défini !!! <br>";
			else
			{
				$responsable = new agent($this->dbconnect);
				$responsable->load("$this->responsableid");
				return $responsable;
			}
		}
		else
			$this->responsableid = $respid;
	}

	function gestionnaire($gestid= null)
	{
		if (is_null($gestid))
		{
			if (is_null($this->gestionnaireid))
				echo "Structure->Gestionnaire : Le gestionnaire de la structure n'est pas défini !!! <br>";
			else
			{
				$gestionnaire = new agent($this->dbconnect);
				$gestionnaire->load("$this->gestionnaireid");
				return $gestionnaire;
			}
		}
		else
			$this->gestionnaireid = $gestid;
	}
	
	function planning($mois_annee_debut, $mois_annee_fin)
	{
		if (is_null($mois_annee_debut) or is_null($mois_annee_fin))
			echo "Structure->planning : Au moins un des paramètres est non défini (null)  <br>";

		$fulldatedebut = "01/" . $mois_annee_debut;
		$tempfulldatefindb = $this->fonctions->formatdatedb("01/" .  $mois_annee_fin);
		$timestampfin = strtotime($tempfulldatefindb);
		//echo "timestampfin = $timestampfin    <br>";
		$fulldatefin =  date("Ym",strtotime("+1month", $timestampfin )) . "01";
		//echo "fulldatefin = $fulldatefin     <br>";
		$timestampfin = strtotime($fulldatefin);
		//echo "timestampfin = $timestampfin    <br>";
		$fulldatefin =  date("d/m/Y",strtotime("-1day", $timestampfin ));
		//echo "fulldatefin (en lisible)= $fulldatefin     <br>";
			
		$listeagent = $this->agentlist($fulldatedebut, $fulldatefin);
		foreach ($listeagent as $key => $agent)
		{
			//echo "structure -> planning : Interval du planning a charger pour l'agent : "  . $agent->nom() . " " . $agent->prenom()  ." = " . $fulldatedebut . " --> " .  $fulldatefin . "<br>";
			$planningservice[$agent->harpegeid()] = $agent->planning($fulldatedebut, $fulldatefin);
			//echo "structure -> planning : Apres planning de ". $agent->nom() . " " . $agent->prenom() . "<br>";
		} 
		return $planningservice;
	}
	
	function planninghtml($mois_annee_debut)   // Le format doit être MM/YYYY
	{
		//echo "Je debute planninghtml <br>";
		$planningservice = $this->planning($mois_annee_debut, $mois_annee_debut);
		
		//echo "Apres le chargement du planning du service <br>";
		$htmltext = "";
		$htmltext = $htmltext . "<div id='structplanning'>";
		$htmltext = $htmltext . "<table class='tableau'>";
		
		$titre_a_ajouter = TRUE;
		foreach ($planningservice as $agentid => $planning)
		{
 			if ($titre_a_ajouter)
 			{
				$htmltext = $htmltext . "<tr class='entete_mois'><td class='titresimple' colspan=" . (count($planningservice[$agentid]->planning()) + 1) .  " align=center ><font color=#BF3021>Gestion des dossiers pour la structure " .  $this->nomlong() . " (" . $this->nomcourt() .  ")</font></td></tr>";
 				$monthname = $this->fonctions->nommois("01/" .  $mois_annee_debut) . " " . date("Y",strtotime($this->fonctions->formatdatedb("01/" .  $mois_annee_debut)));
				//echo "Nom du mois = " . $monthname . "<br>";
  				$htmltext = $htmltext . "<tr class='entete_mois'><td colspan='" . (count($planningservice[$agentid]->planning()) + 1) .  "'>" .  $monthname  . "</td></tr>";
 				//echo "Nbre de jour = " . count($planningservice[$agentid]->planning()) . "<br>";
 				$htmltext = $htmltext . "<tr class='entete'><td>Agent</td>";
 				for ($indexjrs=0; $indexjrs<(count($planningservice[$agentid]->planning())/2); $indexjrs++)
 				{
 					//echo "indexjrs = $indexjrs <br>";
 					$nomjour = $this->fonctions->nomjour(str_pad(($indexjrs + 1),2,"0",STR_PAD_LEFT) . "/" . $mois_annee_debut);
 					$titre = $nomjour . " " . str_pad(($indexjrs + 1),2,"0",STR_PAD_LEFT) . " " . $monthname;
 					$htmltext = $htmltext . "<td colspan='2' title='" . $titre . "'>" . str_pad(($indexjrs + 1),2,"0",STR_PAD_LEFT) . "</td>";
 				}
 				$htmltext = $htmltext . "</tr>";
  				$titre_a_ajouter = FALSE;
			}					
			
			//echo "Je charge l'agent $agentid <br>";
			$agent = new agent($this->dbconnect);
			$agent->load($agentid);
			//echo "l'agent $agentid est chargé ... <br>";
			$htmltext = $htmltext . "<tr class='ligneplanning'>";
			$htmltext = $htmltext ."<td>" . $agent->nom() . " " . $agent->prenom() . "</td>";
			//echo "Avant chargement des elements <br>";
			$listeelement = $planning->planning();
			//echo "Apres chargement des elements <br>";
			foreach ($listeelement as $keyelement => $element)
			{
				//echo "Boucle sur l'element <br>";
				$htmltext = $htmltext . $element->html();
			}
			//echo "Fin boucle sur les elements <br>";
		   $htmltext = $htmltext . "</tr>";
			
		}
		$htmltext = $htmltext . "</table>";
		$htmltext = $htmltext . "</div>";

		$htmltext = $htmltext . $this->fonctions->legendehtml();
		$htmltext = $htmltext . "<br>";
		$htmltext = $htmltext . "<form name='structplanningpdf_" . $this->structureid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
		$htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid ."'>";
		$htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
		$htmltext = $htmltext . "<input type='hidden' name='previous' value='no'>";
		$htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut  . "'>";
		$htmltext = $htmltext . "<a href='javascript:document.structplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF</a>";
		$htmltext = $htmltext . "</form>";
		
		$htmltext = $htmltext . "<form name='structpreviousplanningpdf_" . $this->structureid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
		$htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid ."'>";
		$htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
		$htmltext = $htmltext . "<input type='hidden' name='previous' value='yes'>";
		$htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut  . "'>";
		$htmltext = $htmltext . "<a href='javascript:document.structpreviousplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF (année précédente)</a>";
		$htmltext = $htmltext . "</form>";
		return $htmltext;
	}
	
	function dossierhtml($pourmodif = FALSE, $userid = NULL)
	{
		
		//echo "strucutre->dossierhtml : Non refaite !!!!! <br>";
		//return null;
		
		$htmltext = "<br>";
		$htmltext = "<table class='tableausimple'>";
		$htmltext = $htmltext . "<tr><td class='titresimple' colspan=4 align=center ><font color=#BF3021>Gestion des dossiers pour la structure " .  $this->nomlong() . " (" . $this->nomcourt() .  ")</font></td></tr>";
		$htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Agent</td><td class='cellulesimple'>Report des congés</td><td class='cellulesimple'>Nbre jours initial CET</td><td class='cellulesimple'>Date de début du CET</td></tr>";
		$agentliste = $this->agentlist(date('d/m/Y'),date('d/m/Y') , 'n');
		foreach ($agentliste as $key => $membre)
		{
			//echo "Structure->dossierhtml : Je suis dans l'agent " . $membre->nom() . "<br>";
			if ($membre->harpegeid() != $userid)
			{
				$htmltext = $htmltext . "<tr>";
				$htmltext = $htmltext . "<center><td class='cellulesimple' style='text-align:center;'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</td></center>";
				
				$complement = new complement($this->dbconnect);
				$complement->load($membre->harpegeid(), "REPORTACTIF");
				$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
				if ($pourmodif)
				{
					$htmltext = $htmltext . "<select name=report[" . $membre->harpegeid() . "]>";
					$htmltext = $htmltext . "<option value='n'"; if (strcasecmp($complement->valeur(),"n") == 0) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Non</option>";
					$htmltext = $htmltext . "<option value='o'"; if (strcasecmp($complement->valeur(),"o") == 0) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Oui</option>";
					$htmltext = $htmltext . "</select>";
				}
				else
					$htmltext = $htmltext . $this->fonctions->ouinonlibelle($complement->valeur());
				$htmltext = $htmltext . "</td></center>";
				unset($complement);

				$cet = new cet($this->dbconnect);
				$msg = $cet->load($membre->harpegeid());
				$cumultotal = "";
				$datedebut = "";
				if ($msg == "")
				{
					$cumultotal = $cet->cumultotal();
					$datedebut = $cet->datedebut();
				}
				unset($cet);
				// Si on ne modifie rien ou si il y a déja un CET => On affiche en mode lecture seule
				if (($msg == "") or (!$pourmodif))
				{
					$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $cumultotal . "</td></center>";
					$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $datedebut . "</td></center>";
				}
				else 
				{
					$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'><input type='text' name=cumultotal[" . $membre->harpegeid()  ."] value=''/></td></center>";
					$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'><input class='calendrier' type='text' name=datedebutcet[" . $membre->harpegeid()  ."] value=''/></td></center>";
				}
				$htmltext = $htmltext . "</tr>";
			}
		}
		$htmltext = $htmltext . "</table>";
		$htmltext = $htmltext . "<br>";

		return $htmltext;
	}
	
	function store()
	{
//		echo "structure->store : Non refaite !!!!! <br>";
//		return false;
		
		$sql = "UPDATE STRUCTURE SET AFFICHESOUSSTRUCT='" . $this->sousstructure() . "', AFFICHEPLANNINGTOUTAGENT='" . $this->affichetoutagent()   . "' WHERE STRUCTUREID='" . $this->id() . "'";
		//echo "SQL = " . $sql . "<br>";
		mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Structure->store (STRUCTURE - Sous struct + Affiche) : " . $erreur . "<br>";
		
		return;
		$sql = "UPDATE HARP_STRUCTURE SET CODE_RESPONSABLE='" . $this->responsableid .   "' WHERE C_STRUCTURE='" . $this->id() ."'";
		//echo "SQL = " . $sql . "<br>";
		mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Structure->store (HARP_STRUCTURE) : " . $erreur . "<br>";
	}
	
	function pdf($mois_annee_debut)  // Le format doit être MM/YYYY
	{
		//echo "Avant le new PDF <br>";
		$pdf=new FPDF();
		define('FPDF_FONTPATH','fpdffont/');
		$pdf->Open();
		$pdf->AddPage('L');
		//echo "Apres le addpage <br>";
		$pdf->Image('images/logo_papeterie.png',10,5,60,20);
		$pdf->SetFont('Arial','B',15);
		$pdf->Ln(15);
		$pdf->Cell(60,10,'Service : '. $this->nomlong().' ('.$this->nomcourt() . ')' );
		$pdf->Ln(10);
		$pdf->Cell(60,10,'Planning du mois de : '. $this->fonctions->nommois("01/".$mois_annee_debut) . " " . substr($mois_annee_debut, 3));
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',11);
		$pdf->Cell(60,10,'Edité le '. date("d/m/Y"));
		$pdf->Ln(10);
	
		//echo "Avant le planning <br>";
		$planningservice = $this->planning($mois_annee_debut, $mois_annee_debut);
	
		/////création du planning suivant le tableau généré
		///Création des entetes de colones contenant les 31 jours/////
	
	

		$titre_a_ajouter = TRUE;
		foreach ($planningservice as $agentid => $planning)
		{
 			if ($titre_a_ajouter)
 			{
				$pdf->SetFont('Arial','B',8);
				$pdf->Cell(60,5,"",1,0,'C');
				for ($index=1; $index<=count($planningservice[$agentid]->planning())/2; $index++)
				{
					$pdf->Cell(6,5,$index,1,0,'C');
				}
				$pdf->Ln(5);
				$pdf->Cell(60,5,"",1,0,'C');
				for ($index=1; $index<=count($planningservice[$agentid]->planning())/2; $index++)
				{
					$pdf->Cell(6,5,substr($this->fonctions->nomjour(str_pad($index,2,"0",STR_PAD_LEFT) . "/" . $mois_annee_debut),0,2),1,0,'C');
				}
				$titre_a_ajouter = FALSE;
 			}
				
			//echo "Je charge l'agent $agentid <br>";
			$agent = new agent($this->dbconnect);
			$agent->load($agentid);
			//echo "l'agent $agentid est chargé ... <br>";
			$pdf->Ln(5);
			$pdf->SetFont('Arial','B',8);
			$pdf->Cell(60,5,$agent->nom() . " " . $agent->prenom(),1,0,'C');
			//echo "Avant chargement des elements <br>";
			$listeelement = $planning->planning();
			//echo "Apres chargement des elements <br>";
			foreach ($listeelement as $keyelement => $element)
			{
				list($col_part1,$col_part2,$col_part3)=$this->fonctions->html2rgb($element->couleur());
				$pdf->SetFillColor($col_part1,$col_part2,$col_part3);
				if ($element->moment() != "m")
					$pdf->Cell(3,5,"",'TBR',0,'C',1);
				else
					$pdf->Cell(3,5,"",'TBL',0,'C',1);
			}
		}
	
		/////MISE EN PLACE DES LEGENDES DU PLANNING
		$pdf->Ln(10);
		$pdf->SetFont('Arial','B',7);
		$pdf->SetTextColor(0);
		//////Mise en place de la légende couleurs pour les congés
	
		//echo "Avant legende <br>";
		$this->fonctions->legendepdf($pdf);
		//echo "Apres legende <br>";
	
		$pdf->Ln(8);
		$pdf->Output();
		// $pdf->Output('demande_pdf/autodeclaration_num'.$ID_AUTODECLARATION.'.pdf');
	
	
	}
	
	
}

?>