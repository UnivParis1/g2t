<?php

class affectation {

	private $id = null;
	private $agentid = null;
	private $structureid = null;
	private $datedebut = null;
	private $datefin = null;
	private $numerateurquotite = null;
	private $denominateurquotite = null;
	private $datecreation = null;
	private $datemodif = null;
	private $idharpege = null;
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

	function load($idharpege = null)
	{
		if (is_null($idharpege))
		{
			echo "Affectation->Load : l'identifiant de l'affectation est NULL <br>";
		}
		else
		{
			$sql = "SELECT NO_SEQ_AFFECTATION,NO_DOSSIER_PERS,C_STRUCTURE,D_DEB_AFFECTATION,D_FIN_AFFECTATION,NUM_QUOT_AFFECTATION,DEN_QUOT_AFFECTATION,D_CREATION,D_MODIFICATION,ID_AFFECTATION
FROM HARP_AFFECTATION
WHERE ID_AFFECTATION='" . $idharpege . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Affectation->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Affectation->Load : Affectation $idharpege non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->id = "$result[0]";
			$this->agentid = "$result[1]";
			$this->structureid = "$result[2]";
			$this->datedebut = "$result[3]";
			$this->datefin = "$result[4]";
			//echo "Avant affectation qutotite <br>";
			$this->numerateurquotite = "$result[5]";
			$this->denominateurquotite = "$result[6]";
			//echo "Apres affectation qutotite <br>";
			$this->datecreation = "$result[7]";
			$this->datemodif = "$result[8]";
			$this->idharpege = "$result[9]";
				
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
			$sql = "SELECT ID_AFFECTATION FROM HARP_AFFECTATION WHERE (D_DEB_AFFECTATION <= '"  .  $date . "' AND ('" . $date . "' <= D_FIN_AFFECTATION OR D_FIN_AFFECTATION = '0000-00-00')) AND NO_DOSSIER_PERS =" . $agentid;
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
	
	function id()
	{
		if (is_null($this->id))
			echo "Affectation->id : L'Id n'est pas défini !!! <br>";
		else
			return $this->id;
	}
	
	function idharpege()
	{
		if (is_null($this->idharpege))
			echo "Affectation->idharpege : L'IdHarpege n'est pas défini !!! <br>";
		else
			return $this->idharpege;
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
	
	function datecreation()
	{
		if (is_null($this->datecreation))
			echo "Affectation->datecreation : La date de création n'est pas définie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datecreation);
	}
	
	function datemodif()
	{
		if (is_null($this->datemodif))
			echo "Affectation->datemodif : La date de modification n'est pas définie !!! <br>";
		else
			return $this->fonctions->formatdate($this->datemodif);
	}
	
}
?>