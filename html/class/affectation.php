<?php

class affectation {

	private $affectationid = null;
	private $agentid = null;
	private $datedebut = null;
	private $datefin = null;
	private $datemodif = null;
	private $structureid = null;
	private $numerateurquotite = null;
	private $denominateurquotite = null;
	private $obsolete = null;
	private $dbconnect = null;

	private $fonctions = null;

	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Affectation->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}

	function load($idaffectation = null)
	{
		if (is_null($idaffectation))
		{
			echo "Affectation->Load : l'identifiant de l'affectation est NULL <br>";
		}
		else
		{
			$sql = "SELECT AFFECTATIONID,HARPEGEID,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE
FROM AFFECTATION
WHERE AFFECTATIONID='" . $idaffectation . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Affectation->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Affectation->Load : Affectation $idaffectation non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->affectationid = "$result[0]";
			$this->agentid = "$result[1]";
			$this->datedebut = "$result[2]";
			$this->datefin = "$result[3]";
			//echo "Avant affectation qutotite <br>";
			$this->datemodif = "$result[4]";
			$this->structureid = "$result[5]";
			$this->numerateurquotite = "$result[6]";
			$this->denominateurquotite = "$result[7]";
			$this->obsolete = "$result[8]";				
		}
	}
	
	function loadbydate($date = null, $agentid = null)
	{
   	if (is_null($date))
   		$date = $this->fonctions->formatdatedb(date("d/m/Y"));
   	else 
   		$date = $this->fonctions->formatdatedb($date);
   	
		if (is_null($agentid))
		{
			echo "Affectation->Loadbydate : l'agentId est NULL <br>";
		}
		else
		{
			$sql = "SELECT AFFECTATIONID FROM AFFECTATION WHERE (DATEDEBUT <= '"  .  $date . "' AND ('" . $date . "' <= DATEFIN OR DATEFIN = '0000-00-00')) AND HARPEGEID ='" . $agentid . "' AND OBSOLETE='N'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Affectation->Loadbydate : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Affectation->Loadbydate : Agent " . $agentid . "n'a pas d'affectation pour la date "  .  $this->fonctions->formatdate($date) . " <br>";
			$result = mysql_fetch_row($query);
			$this->load("$result[0]");
		}
	}
	
	function affectationid()
	{
		if (is_null($this->affectationid))
			echo "Affectation->id : L'Id n'est pas défini !!! <br>";
		else
			return $this->affectationid;
	}
	
	function agentid()
	{
		if (is_null($this->agentid))
			echo "Affectation->agentid : L'Id de l'agent n'est pas défini !!! <br>";
		else
			return $this->agentid;
	}
	
	function structureid()
	{
		if (is_null($this->structureid))
			echo "Affectation->structureid : L'Id de la structure n'est pas défini !!! <br>";
		else
			return $this->structureid;
	}
	
	function datedebut()
	{
		if (is_null($this->datedebut))
			echo "Affectation->datedebut : La date de début n'est pas définie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datedebut);
	}

	function datefin()
	{
		if (is_null($this->datefin))
			echo "Affectation->datefin : La date de fin n'est pas définie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datefin);
	}
	
	function quotite()
	{
		if (is_null($this->numerateurquotite) or is_null($this->denominateurquotite))
			echo "Affectation->quotite : La quotité n'est pas définie !!! <br>";
		else
			if ("$this->denominateurquotite" == "100")
				return "$this->numerateurquotite%";
			else
				return "$this->numerateurquotite / $this->denominateurquotite";
			
	}
	
	function numquotite()
	{
		if (is_null($this->numerateurquotite)) 
			echo "Affectation->numquotite : Le numérateur de la quotité n'est pas définie !!! <br>";
		else
			return $this->numerateurquotite;
	}
	
	function denumquotite()
	{
		if (is_null($this->denominateurquotite)) 
			echo "Affectation->numquotite : Le denumérateur de la quotité n'est pas définie !!! <br>";
		else
			return $this->denominateurquotite;
	}
	
	function quotitevaleur()
	{
		$equation = $this->quotite();
		$equation = preg_replace("/[^0-9+\-.*\/()%]/","",$equation);       
		$equation = preg_replace("/([+-])([0-9]+)(%)/","*(1\$1.\$2)",$equation);
		// you could use str_replace on this next line
		// if you really, really want to fine-tune this equation
		$equation = preg_replace("/([0-9]+)(%)/",".\$1",$equation);
		if ( $equation == "" )
			$return = 0;
		else
			eval("\$return=" . $equation . ";" );
		return $return;
	}
	
	function datemodif()
	{
		if (is_null($this->datemodif))
			echo "Affectation->datemodif : La date de modification n'est pas définie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datemodif);
	}
	
	function declarationTPliste($datedebut,$datefin)
	{
		//echo "Je suis dans la affectation->declarationTPliste <br>";
		$declarationliste = null;
		$sql = "SELECT SUBQUERY.DECLARATIONID FROM ((SELECT DECLARATIONID,DATEDEBUT FROM DECLARATIONTP WHERE AFFECTATIONID = '" . $this->affectationid . "' AND DATEDEBUT<'" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN)";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT DECLARATIONID,DATEDEBUT FROM DECLARATIONTP WHERE AFFECTATIONID='" . $this->affectationid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT DECLARATIONID,DATEDEBUT FROM DECLARATIONTP WHERE AFFECTATIONID='" . $this->affectationid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN)) AS SUBQUERY";
		$sql = $sql . " ORDER BY SUBQUERY.DATEDEBUT";

		//echo "affectation->declarationTPliste SQL = $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Agent->declarationTPliste : " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
		{
			//echo "Affectation->declarationTPliste : L'affectation $this->affectationid n'a pas de déclaration de TP entre $datedebut et $datefin <br>";
		}
		while ($result = mysql_fetch_row($query))
		{
			//echo "declarationTPliste => Dans le while <br>";
			$declarationTP = new declarationTP($this->dbconnect);
			//echo "avant le load... <br>";
			$declarationTP->load("$result[0]");
			//echo "Avant l'ajout dans le tableau <br>";
			$declarationliste[] = $declarationTP;
			//echo "Avant le unset...<br>";
			unset($declarationTP);
		}
		//print_r ($declarationliste) ; echo "<br>";
		return $declarationliste;
	}
	
	
	function html($affiche_declaTP = false, $pour_modif = false)
	{
		$agent= new agent($this->dbconnect);
		$agent->load($this->agentid());
		
		$structure = new structure($this->dbconnect);
		$structure->load($this->structureid());
		
 		$htmltext = "Tableau des temps partiel pour " . $agent->identitecomplete() . "<br>";
 		$htmltext = $htmltext . "<div id='planning'>";
 		$htmltext = $htmltext . "<table class='tableausimple'>";
 		$htmltext = $htmltext . "<tr><td class='titresimple'>Date début</td><td class='titresimple'>Date fin</td><td class='titresimple'>Structure</td><td class='titresimple'>Quotité</td>";
		$htmltext = $htmltext . "</tr>";
		$htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $this->datedebut() . "</td><td class='cellulesimple'>" . $this->datefin() . "</td><td class='cellulesimple'>" . $structure->nomlong() . "</td><td class='cellulesimple'>" . $this->quotite() . "</td></tr>";
		$htmltext = $htmltext ."</table><br>";
 		$htmltext = $htmltext . "<table class='tableausimple'>";
 		$htmltext = $htmltext . "<tr><td class='titresimple'>Date demande</td><td class='titresimple'>Date début</td><td class='titresimple'>Date fin</td><td class='titresimple'>Statut</td><td class='titresimple'>Répartition du temps partiel</td>";
// 		if ($pour_modif)
//				$htmltext = $htmltext . "<td class='titresimple'>Annuler</td>";
		$htmltext = $htmltext . "</tr>";

		if ($affiche_declaTP)
		{
			$declarationliste = $this->declarationTPliste($this->datedebut(),$this->datefin());

			if (!is_null($declarationliste))
			{
		 		foreach ($declarationliste as $key => $declaration)
		 		{
		 			if (strcasecmp($declaration->statut(),"r")!=0)
			 			$htmltext = $htmltext . $declaration->html($pour_modif); 
		 		}
			}
			else
			{
				//echo "Pas de déclaration de TP pour l'affectation " . $this->affectationid() . "<br>";
			}
		}		
		$htmltext = $htmltext ."</table>";
		$htmltext = $htmltext ."</div>";
		return $htmltext;
	}
}
?>