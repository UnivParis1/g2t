<?php

class solde {
	private $dbconnect = null;
	private $soldeid = null;
	private $droitaquis_demijrs = null;
	private $droitpris_demijrs = null;
	private $typecode = null;
	private $typelibelle = null;
	private $agentid = null;

	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Solde->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
	}
	
	function load($soldeid)
	{
//		if (is_null($this->$soldeid))
		if (!isset($this->$soldeid))
		{
			$sql = "SELECT ID_SOLDE_CMPTE,DROIT_ACQUIS_DEMIE_JRS,DROIT_PRIS_DEMIE_JRS,COD_TYP_CONGE,CODE FROM SOLDE_CMPTE WHERE ID_SOLDE_CMPTE='" . $soldeid . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
			{
				//echo "Solde->Load : Solde $soldeidid non trouvé <br>";
				return "Le solde $soldeidid n'est pas trouvé <br>";
			}
			$result = mysql_fetch_row($query);
			$this->soldeid = "$result[0]";
			$this->droitaquis_demijrs = "$result[1]";
			$this->droitpris_demijrs = "$result[2]";
			$this->typecode = "$result[3]";
			$this->agentid = "$result[4]";
		}
	}
	
	function loadbytypeagent($agentid, $typecongeid)
	{
		if (is_null($agentid) or is_null($typecongeid))
			echo "Solde->loadbytypeagent : L'agent ou le type de conge est NULL... <br>";
	   else
	   {
			$sql = "SELECT ID_SOLDE_CMPTE,DROIT_ACQUIS_DEMIE_JRS,DROIT_PRIS_DEMIE_JRS,COD_TYP_CONGE,CODE FROM SOLDE_CMPTE WHERE CODE='" . $agentid . "' AND COD_TYP_CONGE = '" . $typecongeid . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->loadbytypeagent : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
			{
				$agent = new agent($this->dbconnect);
				$agent ->load($agentid);
				//echo "Solde->loadbytypeagent : Solde type = $typecongeid  agent = $agentid non trouvé <br>";
				return "Le solde de congés pour le type $typecongeid n'est pas déclaré pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " <br>";
			}
			$result = mysql_fetch_row($query);
			$this->soldeid = "$result[0]";
			$this->droitaquis_demijrs = "$result[1]";
			$this->droitpris_demijrs = "$result[2]";
			$this->typecode = "$result[3]";
			$this->agentid = "$result[4]";
	   }
	}
	
	function id()
	{
		return $this->soldeid;
	}

	function droitaquis_demijrs($droitaquis = null)
	{
		if (is_null($droitaquis))
		{
			if (is_null($this->droitaquis_demijrs))
				echo "Solde->droitaquis_demijrs : Les droits aquis ne sont pas définis !!! <br>";
			else
				return $this->droitaquis_demijrs;
		}
		else
			$this->droitaquis_demijrs = $droitaquis;		
	}

	function droitpris_demijrs($droitpris = null)
	{
		if (is_null($droitpris))
		{
			if (is_null($this->droitpris_demijrs))
				echo "Solde->droitpris_demijrs : Les droits pris ne sont pas définis !!! <br>";
			else
				return $this->droitpris_demijrs;
		}
		else
			$this->droitpris_demijrs = $droitpris;
	}
	
	function solde_demijrs()
	{
		return $this->droitaquis_demijrs - $this->droitpris_demijrs;
	}

	function typecode($type = null)
	{
		if (is_null($type))
		{
			if (is_null($this->typecode))
				echo "Solde->typecode : Les types de congés n'est pas définis !!! <br>";
			else
				return $this->typecode;
		}
		else
			$this->typecode = $type;		
	}
	
	function typelibelle()
	{
		if (is_null($this->typecode))
			echo "Solde->typelibelle : Les type de congés n'est pas définis !!! <br>";
		else
		{
			$sql = "SELECT COD_TYP_CONGE,LL_TYPE_CONGE FROM TYPE_CONGE WHERE COD_TYP_CONGE='" . $this->typecode . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->typelibelle : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Solde->typelibelle : Libellé du solde $this->typecode non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->typelibelle = "$result[1]";
		}
		return $this->typelibelle;
	}
	
	function agent()
	{
		if  (is_null($this->agentid))
			echo "Solde->agent : L'agent n'est pas définis !!! <br>";
		else
		{
			$agent=new agent($this->dbconnect);
			$agent->load($this->agentid);
			return $agent;
		}
	}
	
	function demandeenattente()
	{
		$sql = "SELECT COUNT(*) FROM CONGE_POSE WHERE CODE = '" . $this->agentid ."' AND COD_TYP_CONGE = '" . $this->typecode . "' AND DECISION = 'a'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Solde->demandeenattente : " . $erreur . "<br>";
		else
		{
			$result = mysql_fetch_row($query);
			return "$result[0]";
		}
	}

	function creersolde($codeconge = null, $codeagent = null)
	{
		if (is_null($codeconge))
			$msgerreur = $msgerreur . "Solde->creersolde : Le code de congé est NULL !!! <br>";
		if (is_null($codeagent))
			$msgerreur = $msgerreur . "Solde->creersolde : Le code de l'agent est NULL !!! <br>";
		if (is_null($codeconge) or is_null($codeagent))
		{
			return "Impossible de créer le solde pour l'agent !!! <br>" . $msgerreur;
		}
		else
		{
			$sql = "INSERT INTO SOLDE_CMPTE(DROIT_ACQUIS_DEMIE_JRS,DROIT_PRIS_DEMIE_JRS,COD_TYP_CONGE,CODE) VALUES('0','0','" . $codeconge . "','" . $codeagent . "')";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->creersolde : " . $erreur . "<br>";
			return $erreur;
		}
	}

	function store()
	{
		if (!is_null($this->soldeid))
		{
			$sql = "UPDATE SOLDE_CMPTE SET DROIT_ACQUIS_DEMIE_JRS='" . $this->droitaquis_demijrs() . "',DROIT_PRIS_DEMIE_JRS='" . $this->droitpris_demijrs()   . "'  WHERE ID_SOLDE_CMPTE='" . $this->soldeid . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->store : " . $erreur . "<br>";
			return $erreur;
		}
		else
			echo "Solde->store : La création d'un solde n'est pas possible ==> Utiliser la méthode 'creersolde' <br>";
	}
	
}

?>