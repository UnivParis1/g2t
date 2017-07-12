<?php

/**
  * Complement
  * Definition of a complement
  * 
  * @package     G2T
  * @category    classes
  * @author     Pascal COMTE
  * @version    none
  */
class complement {

	private $harpegeid = null;
	private $complementid = null;
	private $valeur = null;
	private $statut = null;
	private $datedebut = null;
	private $datefin = null;
	
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
			$errlog = "Complement->construct : La connexion à la base de donnée est NULL !!!";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$fonctions->stripAccents($errlog));
		}
		$this->fonctions = new fonctions($db);
	}
	
   /**
         * @param string $harpegeid identifier of the agent (harpege)
         * @param string $complementid identifier of the complement 
         * @return 
   */
	function load($harpegeid, $complementid)
	{
		$sql = "SELECT HARPEGEID,COMPLEMENTID,VALEUR FROM COMPLEMENT WHERE HARPEGEID='$harpegeid' AND COMPLEMENTID='$complementid'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "") {
			$errlog = "Complement->Load : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
		}
		if (mysql_num_rows($query) != 0)
		{
			$result = mysql_fetch_row($query);
	 		$this->harpegeid = "$result[0]";
	 		$this->complementid = "$result[1]";
			$this->valeur = "$result[2]";
		}
		else 
		{
	 		$this->harpegeid = "";
	 		$this->complementid = "";
			$this->valeur = "";
			//echo "CET->Load : CET pour agent  $harpegeid et complement $complementid non trouvé <br>";
		}
	}
	
   /**
         * @param 
         * @return 
   */
	function store()
	{
		if (strlen($this->harpegeid) == 0 or strlen($this->complementid) == 0)
		{
			$errlog = "Complement->Store : Le numéro HARPEGE (" . $this->harpegeid . ")ou le code du complément (". $this->complementid  .") n'est pas initialisé";
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			return;
		}
		$sql = "DELETE FROM COMPLEMENT WHERE HARPEGEID='" . $this->harpegeid . "' AND COMPLEMENTID='" . $this->complementid . "'";
		//echo "SQL Complement->Store : $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "") {
			$errlog = "Complement->Store (DELETE) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
		}
		$sql = "INSERT INTO COMPLEMENT(HARPEGEID,COMPLEMENTID,VALEUR) VALUES('" . $this->harpegeid . "','" . $this->complementid . "','" . str_replace("'", "''", $this->valeur) . "')";
		//echo "SQL Complement->Store : $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "") {
			$errlog = "Complement->Store (INSERT) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
		}
		return $erreur;
	}
	
   /**
         * @param string $agentid identifier of the agent (harpege)
         * @return string the identifier of the agent if $harpegeid is not set
   */
	function harpegeid($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->harpegeid)) {
				$errlog = "Complement->harpegeid : L'Id de l'agent n'est pas défini !!!";
				echo $errlog."<br/>";
				error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			}
			else
				return $this->harpegeid;
		}
		else
			$this->harpegeid = $agentid;
	}
	
   /**
         * @param string $complementid identifier of the complement 
         * @return string the identifier of the complement if $complementid is not set
   */
	function complementid($complementid = null)
	{
		if (is_null($complementid))
		{
			if (is_null($this->complementid)) {
				$errlog = "Complement->complementid : L'Id du complément n'est pas défini !!!";
				echo $errlog."<br/>";
				error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			}
			else
				return $this->complementid;
		}
		else
			$this->complementid = $complementid;
	}

   /**
         * @param string $valeur value of the complement 
         * @return string the value of the complement if $valeur is not set
   */
	function valeur($valeur = null)
	{
		if (is_null($valeur))
		{
			if (is_null($this->valeur)) {
				$errlog = "Complement->valeur : La valeur du complément n'est pas définie !!!";
				echo $errlog."<br/>";
				error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			}
			else
				return $this->valeur;
		}
		else
			$this->valeur = $valeur;
	}
		
}

?>