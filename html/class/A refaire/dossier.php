<?php

class dossier {
	
	private $id = null;
	private $agentid = null;
	private $cetactif = null;
	private $datedebutcet = null;
	private $reportactif = null;
	private $statut = null;
	private $datedebut = null;
	private $datefin = null;
	private $enfantmalade = null;
	private $dbconnect = null;
	
	private $fonctions = null;
	
	function __construct($db)
	{
		$this->dbconnect = $db;
		if (is_null($this->dbconnect))
		{
			echo "Dossier->construct : La connexion a la base de donnée est NULL !!!<br>";
      }
      $this->fonctions = new fonctions($db); 
   }
	
	
	function load($dossierid)
	{
		if (is_null($this->id))
		{
			$sql = "SELECT ID_ARTT_UTILISATEUR,CODE,CET,D_DEB_CET,REPORT,VALIDITE,D_DEB_PARAM,D_FIN_PARAM,DROIT_DEMI_JR_ENF_MALADE FROM ARTT_UTILISATEUR WHERE ID_ARTT_UTILISATEUR=" . $dossierid;
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "")
				echo "Dossier->Load : " . $erreur . "<br>";
			if (mysql_num_rows($query) == 0)
				echo "Dossier->Load : Dossier $dossierid non trouvé <br>";
			$result = mysql_fetch_row($query);
			$this->id = "$result[0]";
			$this->agentid = "$result[1]";
			$this->cetactif = "$result[2]";
			$this->datedebutcet = "$result[3]";
			$this->reportactif = "$result[4]";
			$this->statut = "$result[5]";
			$this->datedebut = "$result[6]";
			$this->datefin = "$result[7]";
			$this->enfantmalade = "$result[8]";
			if ($this->enfantmalade == "")
				$this->enfantmalade = 0;
		}		
	}
	
	function id()
	{
		if (is_null($this->id))
			echo "Dossier->id : L'Id n'est pas défini !!! <br>";
		else
			return $this->id;
	}
	
	function agentid($agentid = null)
	{
		if (is_null($agentid))
		{
			if (is_null($this->agentid))
				echo "Dossier->agentid : L'Id de l'agent n'est pas défini !!! <br>";
			else
				return $this->agentid;
		}
		else
			$this->agentid = $agentid;
	}
	
	function cetactif($estactif = null)
	{
		if (is_null($estactif))
		{
			if (is_null($this->cetactif))
				echo "Dossier->cetactif : Le statut du CET de l'agent n'est pas défini !!! <br>";
			else
				return ($this->cetactif == 'o');
		}
		else
			$this->cetactif = $estactif;
	}
	
	function datedebutcet($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datedebutcet))
				echo "Dossier->datedebutcet : La date du début du CET de l'agent n'est pas défini !!! <br>";
			else
				return $this->fonctions->formatdate($this->datedebutcet);
		}
		else
			$this->datedebutcet = $this->fonctions->formatdatedb($date);
	}
	
	function reportactif($estactif = null)
	{
		if (is_null($estactif))
		{
			if (is_null($this->reportactif))
				echo "Dossier->reportactif : Le statut du report de l'agent n'est pas défini !!! <br>";
			else
				return ($this->reportactif == 'o');
		}
		else
			$this->reportactif = $estactif;
	}
	
	function statut($statut = null)
	{
		if (is_null($statut))
		{
			if (is_null($this->statut))
				echo "Dossier->statut : Le statut du dossier de l'agent n'est pas défini !!! <br>";
			else
				return $this->statut;
		}
		else
			$this->statut = $statut;
	}

	function datedebut($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datedebut))
				echo "Dossier->datedebut : La date du début du dossier de l'agent n'est pas défini !!! <br>";
			else
				return $this->fonctions->formatdate($this->datedebut);
		}
		else
			$this->datedebut = $this->fonctions->formatdatedb($date);
	}
	
	function datefin($date = null)
	{
		if (is_null($date))
		{
			if (is_null($this->datefin))
				echo "Dossier->datefin : La date du fin du dossier de l'agent n'est pas défini !!! <br>";
			else
				return $this->fonctions->formatdate($this->datefin);
		}
		else
			$this->datefin = $this->fonctions->formatdatedb($date);
	}
	
	function enfantmalade($nbrejour = null)
	{
		
		if (is_null($nbrejour))
		{
			if (is_null($this->enfantmalade))
				echo "Dossier->enfantmalade : Le nombre de jour enfant_malade de l'agent n'est pas défini !!! <br>";
			else
				return $this->enfantmalade  / 2;
		}
		else
			$this->enfantmalade = $nbrejour * 2;
	}

	function html($pourmodif = FALSE, $structid = NULL)
	{
		$agent = new agent($this->dbconnect);
		$agent->load($this->agentid());
		
		$htmltext = "";
		$htmltext = $htmltext . "<tr>";
		$htmltext = $htmltext . "<center><td class='cellulesimple' style='text-align:center;'>" . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "</td></center>";
		$htmltext = $htmltext . "<input type='hidden' name='" .  $structid. "_" . $this->agentid() . "_dossierid' value='" . $this->id() ."'>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
		if (!$pourmodif or $this->statut()=="o")
		{
			$htmltext = $htmltext . $this->enfantmalade();
			if ($this->enfantmalade() > 1)
				$htmltext = $htmltext . " jours";
			else
				$htmltext = $htmltext . " jour";
		}
		else
		{
			//echo "Enfant malade pour " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom()  .  " = " . $this->enfantmalade() . "<br>";
			$htmltext = $htmltext . "<select name='" .  $structid. "_" . $this->agentid() . "_nbjrsenfant'>";
			$htmltext = $htmltext . "<option value='0'"; if ($this->enfantmalade() == "0") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">0 jour</option>";
			$htmltext = $htmltext . "<option value='6'"; if ($this->enfantmalade() == "6") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">6 jours</option>";
			$htmltext = $htmltext . "<option value='12'"; if ($this->enfantmalade() == "12") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . "'>12 jours</option>";
			$htmltext = $htmltext . "</select>";
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
		if (!$pourmodif or $this->statut()=="o")
		{		
			if ($this->statut() == "o")
				$htmltext = $htmltext . "Validé";
			else 
				$htmltext = $htmltext . "En attente";
		}
		else
		{
			$htmltext = $htmltext . "<select name='" .  $structid. "_" . $this->agentid() . "_statut'>";
			$htmltext = $htmltext . "<option value='n'"; if ($this->statut() == "n") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">En attente</option>";
			$htmltext = $htmltext . "<option value='o'"; if ($this->statut() == "o") $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Validé</option>";
			$htmltext = $htmltext . "</select>";
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $this->datedebut() . "</td></center>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $this->datefin() . "</td></center>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $agent->quotite() . "</td></center>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
		if (!$pourmodif)
		{
			if ($this->reportactif())
				$htmltext = $htmltext . "Oui";
			else
				$htmltext = $htmltext . "Non";
		}
		else
		{
			$htmltext = $htmltext . "<select name='" .  $structid. "_" . $this->agentid() . "_report'>";
			$htmltext = $htmltext . "<option value='o'"; if ($this->reportactif()) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Oui</option>";
			$htmltext = $htmltext . "<option value='n'"; if (!$this->reportactif()) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Non</option>";
			$htmltext = $htmltext . "</select>";
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
		if (!$pourmodif or $this->cetactif())
		{
			if ($this->cetactif())
				$htmltext = $htmltext . "Oui";
			else 
				$htmltext = $htmltext . "Non";
		}
		else
		{
			$htmltext = $htmltext . "<select name='" .  $structid. "_" . $this->agentid() . "_cetactif'>";
			$htmltext = $htmltext . "<option value='o'"; if ($this->cetactif()) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Oui</option>";
			$htmltext = $htmltext . "<option value='n'"; if (!$this->cetactif()) $htmltext = $htmltext . " selected ";    $htmltext = $htmltext . ">Non</option>";
			$htmltext = $htmltext . "</select>";
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
		if (!$pourmodif  or $this->cetactif())
		{
			if ($this->datedebutcet() != "00/00/0000")
				$htmltext = $htmltext . $this->datedebutcet();
			else 
				$htmltext = $htmltext . "";
		}
		else
		{
			if ($this->datedebutcet() != "00/00/0000")
				$htmltext = $htmltext . "<input class='calendrier' type='text' name='" .  $structid. "_" . $this->agentid() . "_datedebutcet' value='" . $this->datedebutcet() ."'/>";
			else 
				$htmltext = $htmltext . "<input class='calendrier' type='text' name='" .  $structid. "_" . $this->agentid() . "_datedebutcet' value=''/>";
		}
		$htmltext = $htmltext . "</td>";
		$htmltext = $htmltext . "</tr>";
		return $htmltext;
	}
	
	function store()
	{
		//echo "Date = " . date('Ymd') . "<br>";
		$sql = "UPDATE ARTT_UTILISATEUR SET DROIT_DEMI_JR_ENF_MALADE='" . $this->enfantmalade . "',VALIDITE='" . $this->statut . "',REPORT='" . $this->reportactif . "',CET='" . $this->cetactif . "',D_DEB_CET='" . $this->datedebutcet . "',D_VALIDATION='" . date('Ymd')  . "' WHERE ID_ARTT_UTILISATEUR ='" . $this->id   ."'";
		//echo $sql . "<br>";
		if ($this->cetactif == 'o' and !$this->fonctions->verifiedate($this->datedebutcet()))
			return "la date de début du CET n'est pas valide <br>";
		mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
			echo "Dossier->store : " . $erreur . "<br>";
	}

}

?>