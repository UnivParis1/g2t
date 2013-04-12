<?php

class solde {
	private $dbconnect = null;
	private $agentid = null;
	private $typeabsenceid = null;
	private $droitaquis = null;
	private $droitpris = null;
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Solde->construct : La connexion a la base de donnée est NULL !!!<br>";
		}
	}
	
/*	function load($soldeid)
	{
		// Fonction qui ne sert plus !!!!
		
		
//		if (is_null($this->$soldeid))
		if (!isset($this->$soldeid))
		{
			$sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE TYPEABSENCEID='" . $soldeid . "'";
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
*/	

	function load($agentid = null, $typecongeid = null)
	{
		if (is_null($agentid) or is_null($typecongeid))
			echo "Solde->loadbytypeagent : L'agent ou le type de conge est NULL... <br>";
	   else
	   {
			$sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE TYPEABSENCEID='" . $typecongeid . "' AND HARPEGEID='" . $agentid  ."'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
			{
				$agent = new agent($this->dbconnect);
				$agent ->load($agentid);
				//echo "Solde->loadbytypeagent : Solde type = $typecongeid  agent = $agentid non trouvé <br>";
				return "Le solde de congés pour le type $typecongeid n'est pas déclaré pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " <br>";
			}
			$result = mysql_fetch_row($query);
			$this->agentid = "$result[0]";
			$this->typeabsenceid = "$result[1]";
			$this->droitaquis = "$result[2]";
			$this->droitpris = "$result[3]";
	   }
	}
	
	function droitaquis($droitaquis = null)
	{
		if (is_null($droitaquis))
		{
			if (is_null($this->droitaquis))
				echo "Solde->droitaquis : Les droits aquis ne sont pas définis !!! <br>";
			else
				return number_format($this->droitaquis,1);
		}
		else
			$this->droitaquis = $droitaquis;		
	}

	function droitpris($droitpris = null)
	{
		if (is_null($droitpris))
		{
			if (is_null($this->droitpris))
				echo "Solde->droitpris : Les droits pris ne sont pas définis !!! <br>";
			else
				return number_format($this->droitpris,1);
		}
		else
			$this->droitpris = $droitpris;
	}
	
	function solde()
	{
		return number_format($this->droitaquis - $this->droitpris,1);
	}

	function typeabsenceid($typeid = null)
	{
		if (is_null($typeid))
		{
			if (is_null($this->typeabsenceid))
				echo "Solde->typeabsenceid : Les types de congés n'est pas définis !!! <br>";
			else
				return $this->typeabsenceid;
		}
		else
			$this->typeabsenceid = $typeid;		
	}
	
	function typelibelle()
	{
		if (is_null($this->typeabsenceid))
			echo "Solde->typelibelle : Les type de congés n'est pas définis !!! <br>";
		else
		{
			$sql = "SELECT LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID='" . $this->typeabsenceid . "'";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->typelibelle : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Solde->typelibelle : Libellé du solde $this->typeabsenceid non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->typelibelle = "$result[0]";
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
		$sql = "SELECT COUNT(DEMANDE.DEMANDEID) FROM DEMANDE, DECLARATIONTP, AFFECTATION, DEMANDEDECLARATIONTP
WHERE DEMANDE.TYPEABSENCEID='" . $this->typeabsenceid  ."'
AND DEMANDE.DEMANDEID = DEMANDEDECLARATIONTP.DEMANDEID
AND DEMANDEDECLARATIONTP.DECLARATIONID = DECLARATIONTP.DECLARATIONID
AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID
AND AFFECTATION.HARPEGEID='" . $this->agentid  . "'
AND DEMANDE.STATUT='a';";
		
		//echo "Solde->demandeenattente SQL : $sql <br>";
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
			$sql = "INSERT INTO SOLDE(HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES('" . $codeagent . "','" . $codeconge . "','0','0')";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Solde->creersolde : " . $erreur . "<br>";
			return $erreur;
		}
	}

	function store()
	{
		if (!is_null($this->harpegeid) and (!is_null($this->typeabsenceid)))
		{
			$sql = "UPDATE SOLDE SET DROITACQUIS='" . $this->droitaquis() . "',DROITPRIS='" . $this->droitpris()   . "' WHERE HARPEGEID='" . $this->harpegeid . "' AND TYPEABSENCEID='" . $this->typeabsenceid . "'";
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