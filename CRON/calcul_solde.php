<?php

	require_once("../html/class/fonctions.php");
	require_once('../html/includes/dbconnection.php');

	$fonctions = new fonctions($dbcon);

	echo "Début du calcul des soldes " . date("d/m/Y H:i:s") . "\n" ;

	$sql="SELECT HARPEGEID,NOM,PRENOM FROM AGENT ORDER BY HARPEGEID";
	$query_agent = mysql_query($sql);
	$erreur_requete=mysql_error();
	if ($erreur_requete!="")
		echo "SELECT FROM AGENT => $erreur_requete \n";

	//echo "Avant deb / fin periode \n";
	$date_deb_period = $fonctions->anneeref() . $fonctions->debutperiode();
	$date_fin_period = ($fonctions->anneeref()  +1) . $fonctions->finperiode();

	//echo "Avant Nbre jours periode... \n";
	$nbre_jour_periode = $fonctions->nbjours_deux_dates($date_deb_period,$date_fin_period);
	//echo "Avant Nbre jours offert... \n";
	$nbr_jrs_offert = $fonctions->liredbconstante("NBJOURS".substr($date_deb_period,0,4));

	//echo "Avant le 1er while \n";
	while ($result = mysql_fetch_row($query_agent))
	{
		$agentid=$result[0];
		$agentinfo = $result[1] . " " . $result[2];

		$solde_agent = 0;

		$sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid' AND OBSOLETE='N' ORDER BY DATEDEBUT";

		$query_aff = mysql_query($sql);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "SELECT FROM AFFECTATION (Full) => $erreur_requete \n";

		$cas_general = true;
      $nbre_total_jours = 0;
		if (mysql_num_rows($query_aff) != 0) // On a des d'affectations
      {
			while ($result_aff = mysql_fetch_row($query_aff))
			{
				if ($result_aff[5]!="0") // Si c'est un contrat
				{
					$datedebutaff = $result_aff[1];
					if ($result_aff[2]=='0000-00-00')
						$datefinaff = date("Y-m-d", strtotime("+1 year"));
					else
						$datefinaff = $result_aff[2];
					//echo "Numéro de contrat pour $agentid ($agentinfo) = $result_aff[5] Durée (en jours) = " . $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff)   ."\n";
					$nbre_total_jours += $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
					//echo "nbre_total_jours = $nbre_total_jours pour $agentid ($agentinfo)\n";
					if ($nbre_total_jours < 365)
						$cas_general = false;
					else
						$cas_general = true;
				}
				else // Si on trouve une affectation sans contrat alors on est dans le cas général
					$cas_general = true;
			}
      }

		$sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid'
		      AND OBSOLETE='N'
				AND (('$date_deb_period' <= DATEDEBUT AND DATEDEBUT < '$date_fin_period')
				 OR ('$date_deb_period' < DATEFIN AND DATEFIN <= '$date_fin_period')
				 OR (DATEDEBUT < '$date_deb_period' AND (DATEFIN > '$date_fin_period' OR DATEFIN = '00000000'))) ORDER BY DATEDEBUT";

		$query_aff = mysql_query($sql);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "SELECT FROM AFFECTATION => $erreur_requete \n";

		while ($result = mysql_fetch_row($query_aff))
		{
			if (is_null($result[1]))
				$datedebutaff = $date_deb_period;
			else
				$datedebutaff = $result[1];
			//echo "datedebutaff = $datedebutaff   date_deb_period=$date_deb_period \n";
			if ($fonctions->formatdatedb($datedebutaff)<$fonctions->formatdatedb($date_deb_period))
				$datedebutaff = $date_deb_period;

			if ($result[2]=='0000-00-00')
			{
				$datefinaff = $date_fin_period;
				//echo "La date de fin est null \n";
			}
			else
				$datefinaff = $result[2];
			//echo "datefinaff = $datefinaff   date_fin_period=$date_fin_period \n";
			if (($fonctions->formatdatedb($datefinaff)>$fonctions->formatdatedb($date_fin_period)) or ($fonctions->formatdatedb($datefinaff)=='00000000'))
				$datefinaff = $date_fin_period;

			$quotite = $result[3] / $result[4];
			$nbre_jour_aff = $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
			if ($cas_general == false)
			{
				// 2.5j x 12 mois / 365 j = 0,082j de congés
				$solde_agent = $solde_agent + (((2.5*12)/365) * $nbre_jour_aff);
				echo "Pas dans le cas général pour $agentid ($agentinfo) \n";
			}
			else
				$solde_agent = $solde_agent + (($nbr_jrs_offert * $nbre_jour_aff) / $nbre_jour_periode) * $quotite;
		}
		if ($solde_agent>0)
		{
			$partie_decimale = $solde_agent - floor($solde_agent);
			echo "Code Agent = $agentid ($agentinfo)    solde_agent = $solde_agent     partie_decimale =  $partie_decimale     entiere = " .  floor($solde_agent) . "          ";
			if ((float)$partie_decimale < (float)0.25)
				$solde_agent = floor($solde_agent);
			elseif ((float)($partie_decimale >= (float)0.25) && ((float)$partie_decimale < (float)0.75))
				$solde_agent = floor($solde_agent) + (float)0.5;
			else
				$solde_agent = floor($solde_agent) + (float)1;

			echo "apres traitement : $solde_agent \n";
		}

		$typeabsenceid = "ann" . substr($fonctions->anneeref(),2,2);
		$sql = "SELECT HARPEGEID,TYPEABSENCEID FROM SOLDE WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
		$query = mysql_query($sql);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "SELECT HARPEGEID,TYPEABSENCEID FROM CONGE => $erreur_requete \n";
		if (mysql_num_rows($query) != 0) // le type annXX existe déja => On le met à jour
			$sql = "UPDATE SOLDE SET DROITAQUIS='$solde_agent' WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
		else
			$sql = "INSERT INTO SOLDE(HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES('" . $agentid . "','" . $typeabsenceid . "','$solde_agent','0')";
		mysql_query($sql);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			echo "INSERT ou UPDATE CONGE => $erreur_requete \n";
	}
	echo "Fin du calcul des soldes " . date("d/m/Y H:i:s") . "\n" ;

?>