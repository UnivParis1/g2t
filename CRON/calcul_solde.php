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
       // !!!!!!! ATTENTION : Les 2 lignes suivantes permettent de ne tester qu'un seul dossier !!!!
       //if ($result[0]!='3008')
       //	continue;
       // !!!!!!! FIN du test d'un seul dossier !!!!
		
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
       		$datedebaffprec = date('Y-m-d',0);
       		$datefinaffprec = date('Y-m-d',0);
       		$duree_aff_ante_periode = 0;
			while ($result_aff = mysql_fetch_row($query_aff))
			{
				if ($result_aff[5]!="0") // Si c'est un contrat
				{
					$datedebutaff = $result_aff[1];
					$datearray = date_parse($result_aff[2]);
					$year = $datearray["year"];
					//echo "year = $year \n";
//					// Si la fin du contrat est dans plus de 2 ans, alors on raccourci la fin de contrat pour calculer le nombre de jour
//					if (($result_aff[2]=='0000-00-00') or ($year > ($fonctions->anneeref()  +2)))
//						$datefinaff = date("Y-m-d", strtotime("+1 year"));
                    // Si la fin du contrat est vide (0000-00-00) ou si la fin du contrat est postérieur à la fin de période => On force à la fin de période
                    //echo "Convertion date fin affectation : " . $fonctions->formatdatedb($result_aff[2]) . "\n";
                    //echo "Calcul fin période = " .($fonctions->anneeref()+1) . $fonctions->finperiode() . "\n";
                    if (($result_aff[2]=='0000-00-00') or ($fonctions->formatdatedb($result_aff[2])>($fonctions->anneeref()+2) . $fonctions->finperiode()))
                       $datefinaff = ($fonctions->anneeref()+2) . $fonctions->finperiode();
					else
						$datefinaff = $result_aff[2];
					$duree_aff = $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
					//echo "datedebutaff = $datedebutaff    datefinaff = $datefinaff\n";
					//echo "Numéro de contrat pour $agentid ($agentinfo) = $result_aff[5] Durée (en jours) = " . $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff)   ."\n";
//					echo "datedebutaff = $datedebutaff    datefinaff = $datefinaff\n";
					if (($datedebutaff != $datedebaffprec) && ($datefinaff != $datefinaffprec))
					{
						if ($datedebutaff == date("Y-m-d", strtotime("+1 day", strtotime($datefinaffprec))) ) 
						{
							$nbre_total_jours += $duree_aff;
							if ($fonctions->formatdatedb($datedebutaff) < $date_deb_period)
								if ($fonctions->formatdatedb($datefinaff) < $date_deb_period)
									$duree_aff_ante_periode += $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
								else
									$duree_aff_ante_periode += $fonctions->nbjours_deux_dates($datedebutaff, $date_deb_period) - 1;
						}
						else 
						{
							$nbre_total_jours = $duree_aff;
							$duree_aff_ante_periode = 0;
							if ($fonctions->formatdatedb($datedebutaff) < $date_deb_period)
								if ($fonctions->formatdatedb($datefinaff) < $date_deb_period)
									$duree_aff_ante_periode = $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
								else
									$duree_aff_ante_periode = $fonctions->nbjours_deux_dates($datedebutaff, $date_deb_period) - 1;
							if ($nbre_total_jours >= 365)
								$nbre_jours_manquants = 0;
							else 
								if (($fonctions->formatdatedb($datefinaff) >= $date_fin_period))
									$nbre_jours_manquants = 365;
						}
					}
//					echo "nbre_total_jours = $nbre_total_jours    duree_aff = $duree_aff     duree_aff_ante_periode = $duree_aff_ante_periode     pour $agentid ($agentinfo)\n";
					if ($fonctions->formatdatedb($datedebutaff) < $date_fin_period && $fonctions->formatdatedb($datefinaff) > $date_deb_period && $cas_general)
					{
						if ($duree_aff >= 365 || $duree_aff_ante_periode >= 365 || $nbre_total_jours - $duree_aff >= 365)
						{
							$cas_general = true;
						}
						else
						{
							$cas_general = false;
							// calcul du nombre de jours manquants pour obtenir une ancienneté d'1 an à partir de la date de début de période
							$nbre_jours_manquants = 365 - ($nbre_total_jours - $duree_aff) - $fonctions->nbjours_deux_dates($datedebutaff, $date_deb_period) + 1;
							if ($nbre_jours_manquants < 0)
								$nbre_jours_manquants = 0;
							//echo "nbre_jours_manquants = $nbre_jours_manquants \n";
						}
					}
				}
				else // Si on trouve une affectation sans contrat alors on est dans le cas général
				{
					// On vérifie qu'il n'y a pas de contrats sur la période avec ancienneté totale consécutive < 1 an
					if ($cas_general)
						$cas_general = true;
//					echo "CARRIERE datedebutaff = $result_aff[1]    datefinaff = $result_aff[2]    \n";
				}
				$datefinaffprec = $datefinaff;
				$datedebaffprec = $datedebutaff;
			}
       }

		//echo "nbre_total_jours = $nbre_total_jours pour $agentid ($agentinfo)\n";
      $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid'
		      AND OBSOLETE='N'
			  AND DATEDEBUT < '$date_fin_period'
			  AND DATEFIN > '$date_deb_period'
              ORDER BY DATEDEBUT";

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
			if (($fonctions->formatdatedb($datefinaff)>$fonctions->formatdatedb($date_fin_period)) || ($fonctions->formatdatedb($datefinaff)=='00000000'))
				$datefinaff = $date_fin_period;

			$quotite = $result[3] / $result[4];
			$nbre_jour_aff = $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff);
			if ($cas_general == false)
			{
				// 2.5j x 12 mois / 365 j = 0,082j de congés
				if ($result[5] == "0")
				{
					$nbr_jour_cont = 0;
					$nbre_jours_equ_titu = $nbre_jour_aff;
				}
				else
				{
					$nbr_jour_cont = min(array($nbre_jour_aff, $nbre_jours_manquants));
					$nbre_jours_equ_titu = 0;
					if ($nbr_jour_cont < $nbre_jour_aff)
						$nbre_jours_equ_titu = $nbre_jour_aff - $nbr_jour_cont;
				}
				$solde_agent = $solde_agent + ((((2.5*12)/365) * $nbr_jour_cont) + (($nbr_jrs_offert * $nbre_jours_equ_titu) / $nbre_jour_periode)) * $quotite;
				echo "Pas dans le cas général pour $agentid ($agentinfo) \n"; 
				//echo " nbre_jours_manquants = $nbre_jours_manquants \n";
				if ($nbre_jours_equ_titu > 0 || $nbre_jours_manquants == $nbre_jour_aff)
					$nbre_jours_manquants = 0;
				else 
					if ($nbre_jours_manquants > $nbre_jour_aff)
						$nbre_jours_manquants -= $nbre_jour_aff;
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