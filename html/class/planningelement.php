<?php

class planningelement {

	const COULEUR_VIDE = '#FFFFFF';
	const COULEUR_WE = '#999999';
	const COULEUR_NON_DECL = '#775420';
	

	private $date = null;
	private $moment = null;  // 'm' pour matin / 'a' pour apr�s-midi
	private $typeelement = null; // WE, cong�, absence, f�ri�...
	private $info = null;
	private $couleur = null;
	private $dbconnect = null;
	private $statut = null;
	private $agentid = null;
	
	private $fonctions = null;
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "PlanningElement->construct : La connexion a la base de donn�e est NULL !!!<br>";
		}
		$this->fonctions = new fonctions($db);
	}
	
	function date($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->date))
				echo "PlanningElement->date : La date n'est pas d�finie !!! <br>";
			else
				return $this->fonctions->formatdate($this->date);
		}
		else
			$this->date = $this->fonctions->formatdatedb($date);
	}

	function moment($moment = null)
	{
		if (is_null($moment))
		{
			if (is_null($this->moment))
				echo "PlanningElement->moment : Le moment n'est pas d�fini !!! <br>";
			else
				return $this->moment;
		}
		else
			$this->moment = $moment;
	}

	function type($type = null)
	{
		if (is_null($type))
		{
			if (is_null($this->typeelement))
				echo "PlanningElement->type : Le type n'est pas d�fini !!! <br>";
			else
				return $this->typeelement;
		}
		else
			$this->typeelement = $type;
	}
	
	function info($info = null)
	{
		if (is_null($info))
		{
			if (is_null($this->info))
				echo "PlanningElement->info : L'info n'est pas d�fini !!! <br>";
			else
				return $this->info;
		}
		elseif (strcasecmp($this->statut,"a")!=0)
			$this->info = $info;
		elseif (strcasecmp($this->statut,"a")==0)
			$this->info = $this->info . "  " . $info;
		else 
		{
			//echo "PlanningElement->info : Le statut est 'a' ==> On ne modifie pas l'info <br>";
		}
	}
	
	function agentid($id = null)
	{
		if (is_null($id))
		{
			if (is_null($this->agentid))
				echo "PlanningElement->agentid : L'Id de l'agent n'est pas d�fini !!! <br>";
			else
				return $this->agentid;
		}
		else
			$this->agentid = $id;
		
	}
	
	function couleur()
	{
		if ($this->typeelement == "")
			return self::COULEUR_VIDE;
		elseif (strcasecmp($this->typeelement,"nondec")==0)
		   return self::COULEUR_NON_DECL;
		elseif (strcasecmp($this->typeelement,"WE")==0)
		   return self::COULEUR_WE;
		$sql = "SELECT TYPEABSENCEID,COULEUR FROM TYPEABSENCE WHERE TYPEABSENCEID = '" . $this->typeelement . "'";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "PlanningElement->couleur : " . $erreur . "<br>";
		if (mysql_num_rows($query) == 0)
			echo "PlanningElement->couleur : La couleur pour le type de cong� " . $this->typeelement . " non trouv�e <br>";
		$result = mysql_fetch_row($query);
		return $result[1];
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->statut))
				echo "PlanningElement->statut : Le statut n'est pas d�fini !!! <br>";
			else
				return $this->statut;
		}
		else
		{
			$this->statut = $statut;
		   if (strcasecmp($this->statut,"a")==0)
		   {
				$this->type("atten");
				$sql = "SELECT TYPEABSENCEID,LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID = '" . $this->typeelement . "'";
				$query=mysql_query ($sql, $this->dbconnect);
				$erreur=mysql_error();
				if ($erreur != "")
					echo "PlanningElement->statut : " . $erreur . "<br>";
				if (mysql_num_rows($query) == 0)
					echo "PlanningElement->statut : Le libelle pour le type de cong� " . $this->typeelement . " non trouv�e <br>";
				$result = mysql_fetch_row($query);
				$this->info = $result[1] . " : " . $this->info;
		   }
		}
	}
	
	function html($clickable = FALSE, $checkboxname = null)
	{
		$htmltext = "";
		//$htmltext = $htmltext ."<td class=celplanning style='border:1px solid black' bgcolor='" . $this->couleur() . "' title=\"" . $this->info()  . "\" ></td>";

/*
		$htmltext = $htmltext . "<form name='frm_" . $this->date . "_" . $this->moment . "'  method='post' >";
		$htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->agentid   . "'>";
		$htmltext = $htmltext . "<input type='hidden' name='date' value='" . $this->date . "'>";
		$htmltext = $htmltext . "<input type='hidden' name='moment' value='" . $this->moment . "'>";
		foreach ($_POST as $keypost => $valeurpost)
			$htmltext = $htmltext . "<input type='hidden' name='" . $keypost  ."' value='" . $valeurpost . "'>";
		if ($this->typeelement == "atten")
			$htmltext = $htmltext ."<a href='javascript:frm_" . $this->date . "_" . $this->moment . ".submit();'>";
			
			'" . $this->date() .  "'
*/		
		if ($clickable)
			$clickabletext = "oncontextmenu=\"planning_rclick('" . $this->date() .  "','" . $this->moment()  . "');return false;\" onclick=\"planning_lclick('" . $this->date() .  "','" . $this->moment()  . "')\" ";
		else
			$clickabletext = "";
		if (!is_null($checkboxname))
			$checkboxtext = "<input type='checkbox' name='elmtcheckbox[" . $checkboxname . "]' value='1'>";
		else
			$checkboxtext = "";
		
		if ($this->moment == 'm')
			$htmltext = $htmltext ."<td class='planningelement_matin' " . $clickabletext . "  bgcolor='" . $this->couleur() . "' title=\"" . $this->info()  . "\" >" . $checkboxtext ."</td>";
		else
			$htmltext = $htmltext ."<td class='planningelement_aprem' " . $clickabletext . "  bgcolor='" . $this->couleur() . "' title=\"" . $this->info()  . "\" >" . $checkboxtext ."</td>";
/*		
		if ($this->typeelement == "atten")
			$htmltext = $htmltext ."</a>";
		$htmltext = $htmltext . "</form>";
*/		
		return $htmltext;
	}
	
}

?>