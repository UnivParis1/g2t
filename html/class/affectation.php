<?php

/**
  * Affectation
  * Definition of an nomination
  * 
  * @package     G2T
  * @category    classes
  * @author     Pascal COMTE
  * @version    none
  */
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

   /**
         * @param object $db the mysql connection
         * @return 
   */
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Affectation->construct : La connexion a la base de donn�e est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}

   /**
         * @param string $idaffectation the nomination identifier
         * @return 
   */
	function load($idaffectation = null)
	{
		if (is_null($idaffectation))
		{
			echo "Affectation->Load : l'identifiant de l'affectation est NULL <br>";
			return false;
		}
		else
		{
			$sql = "SELECT AFFECTATIONID,HARPEGEID,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE
FROM AFFECTATION
WHERE AFFECTATIONID='" . $idaffectation . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
			{
				echo "Affectation->Load : " . $erreur . "<br>";
				return false;
			}
			if (mysql_num_rows($query) == 0)
			{
				echo "Affectation->Load : Affectation $idaffectation non trouv� <br>";
				return false;
			}
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
		return true;
	}
	
   /**
         * @param string $date optional date to search the nomination. Current date if not set
         * @param string $agentid optional agent identifier (harpege)
         * @return string the nomination identifier
   */
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
	
   /**
         * @param 
         * @return string the identifier of the current nomination
   */
	function affectationid()
	{
		if (is_null($this->affectationid))
			echo "Affectation->id : L'Id n'est pas d�fini !!! <br>";
		else
			return $this->affectationid;
	}
	
   /**
         * @param 
         * @return string the agent identifier (harpege) for the current nomination
   */
	function agentid()
	{
		if (is_null($this->agentid))
			echo "Affectation->agentid : L'Id de l'agent n'est pas d�fini !!! <br>";
		else
			return $this->agentid;
	}
	
   /**
         * @param 
         * @return string the structure identifier for the current nomination 
   */
	function structureid()
	{
		if (is_null($this->structureid))
			echo "Affectation->structureid : L'Id de la structure n'est pas d�fini !!! <br>";
		else
			return $this->structureid;
	}
	
   /**
         * @param 
         * @return string the starting date of the current nomination 
   */
	function datedebut()
	{
		if (is_null($this->datedebut))
			echo "Affectation->datedebut : La date de d�but n'est pas d�finie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datedebut);
	}

   /**
         * @param 
         * @return string the end date of the current nomination 
   */
	function datefin()
	{
		if (is_null($this->datefin))
			echo "Affectation->datefin : La date de fin n'est pas d�finie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datefin);
	}
	
   /**
         * @param 
         * @return string the quota of the current nomination 
   */
	function quotite()
	{
		if (is_null($this->numerateurquotite) or is_null($this->denominateurquotite))
			echo "Affectation->quotite : La quotit� n'est pas d�finie !!! <br>";
		else
			if ("$this->denominateurquotite" == "100")
				return "$this->numerateurquotite%";
			else
				return "$this->numerateurquotite / $this->denominateurquotite";
			
	}
	
   /**
         * @param 
         * @return integer the numerator of quota of the current nomination 
   */
	function numquotite()
	{
		if (is_null($this->numerateurquotite)) 
			echo "Affectation->numquotite : Le num�rateur de la quotit� n'est pas d�finie !!! <br>";
		else
			return $this->numerateurquotite;
	}
	
   /**
         * @param 
         * @return integer the denumerator of quota of the current nomination 
   */
	function denumquotite()
	{
		if (is_null($this->denominateurquotite)) 
			echo "Affectation->numquotite : Le denum�rateur de la quotit� n'est pas d�finie !!! <br>";
		else
			return $this->denominateurquotite;
	}
	
   /**
         * @param 
         * @return float the quota value of the current nomination 
   */
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
	
   /**
         * @param 
         * @return string the last modification date 
   */
	function datemodif()
	{
		if (is_null($this->datemodif))
			echo "Affectation->datemodif : La date de modification n'est pas d�finie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datemodif);
	}
	
   /**
         * @param string $datedebut the beginning date to search part time
         * @param string $datefin the end date to search part time
         * @return array list of part time declaration object
   */
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
			//echo "Affectation->declarationTPliste : L'affectation $this->affectationid n'a pas de d�claration de TP entre $datedebut et $datefin <br>";
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
	
	
   /**
         * @param boolean $affiche_declaTP optional if true display the part time declaration
         * @param boolean $pour_modif optional if true and $affiche_declaTP=true, display the part time declaration in edit mode
         * @param boolean $mode optional if set to "resp" and $affiche_declaTP=true, display all part time declaration. if set to "agent" and  $affiche_declaTP=true, display only part time declaration that are in current period
         * @return string HTML text of nomination
   */
	function html($affiche_declaTP = false, $pour_modif = false, $mode = "agent")
	{
		$agent= new agent($this->dbconnect);
		$agent->load($this->agentid());
		
		$structure = new structure($this->dbconnect);
		$structure->load($this->structureid());
		
 		$htmltext = "Tableau des temps partiel pour " . $agent->identitecomplete() . "<br>";
 		$htmltext = $htmltext . "<div id='planning'>";
 		$htmltext = $htmltext . "<table class='tableausimple'>";
 		$htmltext = $htmltext . "<tr><td class='titresimple'>Date d�but</td><td class='titresimple'>Date fin</td><td class='titresimple'>Structure</td><td class='titresimple'>Quotit�</td>";
		$htmltext = $htmltext . "</tr>";
		$htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $this->datedebut() . "</td><td class='cellulesimple'>" . $this->datefin() . "</td><td class='cellulesimple'>" . $structure->nomlong() . "</td><td class='cellulesimple'>" . $this->quotite() . "</td></tr>";
		$htmltext = $htmltext ."</table><br>";
 		$htmltext = $htmltext . "<table class='tableausimple'>";
 		$htmltext = $htmltext . "<tr><td class='titresimple'>Date demande</td><td class='titresimple'>Date d�but</td><td class='titresimple'>Date fin</td><td class='titresimple'>Statut</td><td class='titresimple'>R�partition du temps partiel</td>";
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
		 			// Si on est en mode "resp" (responsable de service) on affiche toutes les d�clarations de TP
		 			// qui sont li�s � cette affectation
		 			if (($this->fonctions->formatdatedb($declaration->datefin()) >= ($this->fonctions->anneeref() . $this->fonctions->debutperiode())) or strcasecmp($mode, "resp")==0)
		 			{
			 			if (strcasecmp($declaration->statut(),"r")!=0)
				 			$htmltext = $htmltext . $declaration->html($pour_modif);
		 			} 
		 		}
			}
			else
			{
				//echo "Pas de d�claration de TP pour l'affectation " . $this->affectationid() . "<br>";
			}
		}		
		$htmltext = $htmltext ."</table>";
		$htmltext = $htmltext ."</div>";
		return $htmltext;
	}
}
?>