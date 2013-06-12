<?php

class complement {

	private $harpegeid = null;
	private $complementid = null;
	private $valeur = null;
	private $statut = null;
	private $datedebut = null;
	private $datefin = null;
	
	private $dbconnect = null;
	private $fonctions = null;
	
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Complement->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function load($harpegeid, $complementid)
	{
		$sql = "SELECT HARPEGEID,COMPLEMENTID,VALEUR FROM COMPLEMENT WHERE HARPEGEID='$harpegeid' AND COMPLEMENTID='$complementid'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Complement->Load : " . $erreur . "<br>";
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
	
	function harpegeid($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->harpegeid))
				echo "Complement->harpegeid : L'Id de l'agent n'est pas défini !!! <br>";
			else
				return $this->harpegeid;
		}
		else
			$this->harpegeid = $agentid;
	}
	
	function complementid($complementid = null)
	{
		if (is_null($complementid))
		{
			if (is_null($this->complementid))
				echo "Complement->complementid : L'Id du complement n'est pas défini !!! <br>";
			else
				return $this->complementid;
		}
		else
			$this->complementid = $complementid;
	}

	function valeur($valeur = null)
	{
		if (is_null($valeur))
		{
			if (is_null($this->valeur))
				echo "Complement->valeur : La valeur du complement n'est pas définie !!! <br>";
			else
				return $this->valeur;
		}
		else
			$this->valeur = $valeur;
	}
	
}

?>