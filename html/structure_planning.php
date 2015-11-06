<?php

	require_once('CAS.php');
	include './includes/casconnection.php';

	if (isset($_POST["userid"]))
		$userid = $_POST["userid"];
	else
		$userid = null;
	if (is_null($userid) or ($userid==""))
	{
		error_log (basename(__FILE__)  . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}

	require_once("./class/agent.php");
	require_once("./class/structure.php");
	require_once("./class/solde.php");
	require_once("./class/demande.php");
	require_once("./class/planning.php");
	require_once("./class/planningelement.php");
	require_once("./class/declarationTP.php");
//	require_once("./class/autodeclaration.php");
//	require_once("./class/dossier.php");
	require_once("./class/tcpdf/tcpdf.php");
	require_once("./class/cet.php");
	require_once("./class/affectation.php");
	require_once("./class/complement.php");

	$user = new agent($dbcon);
	$user->load($userid);

	//echo "<br><br><br>"; print_r($_POST); echo "<br>";

	require ("includes/menu.php");

	//echo '<html><body class="bodyhtml">';
	echo "<br>";
	if (isset($_POST["indexmois"]))
		$indexmois = $_POST["indexmois"];
	else
		$indexmois = null;
	//echo "indexmois =  $indexmois <br>";
	if (is_null($indexmois) or $indexmois == "")
		$indexmois = date("m");
	$indexmois = str_pad($indexmois,2,"0",STR_PAD_LEFT);
	//echo "indexmois (apres) =  $indexmois <br>";
	$annee = $fonctions->anneeref();
	//echo "annee =  $annee <br>";
	$debutperiode = $fonctions->debutperiode();
	//echo "debut periode = $debutperiode <br>";
	$moisdebutperiode=date("m",strtotime($fonctions->formatdatedb(date("Y") . $debutperiode)));
	//echo "moisdebutperiode  = $moisdebutperiode <br>";
	if ($indexmois <  $moisdebutperiode)
		$annee ++;
	//echo "annee (apres) =  $annee <br>";

	if (isset($_POST["mode"]))
		$mode = $_POST["mode"]; // Mode = resp ou agent
	else
		$mode = "resp";

	echo "<form name='select_mois' method='post'>";
	echo "<center><select name='indexmois'>";
	for ($index=1 ; $index <= 12 ; $index ++)
	{
		echo "<option value='$index'";
		if ($index == $indexmois)
			echo " selected ";
		echo ">" . $fonctions->nommois("01/" . str_pad($index,2,"0",STR_PAD_LEFT) . "/" . date("Y")) . "</option>";
	}
	echo "</select>";
	echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "' />";
	echo "<input type='hidden' name='mode' value='" . $mode . "' />";
	echo "<input type='submit' value='Valider' /></center>";
	echo "</form>";

	if (strcasecmp($mode,"resp")==0)
	{
		$structureliste = $user->structrespliste();
		foreach ($structureliste as $structkey => $structure)
		{
			// Si la structure est ouverte => On la garde
			if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
			{
				if (strcasecmp($structure->sousstructure(), "o") ==0 )
				{
					$sousstructliste = $structure->structurefille();
					foreach ((array)$sousstructliste as $key => $struct)
					{
						// Si la structure est fermée.... On la supprime de la liste
						if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd")))
						{
							// echo "Index = " . array_search($struct, $sousstructliste) . "    Key = " . $key . "<br>";
							// echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
							unset ($sousstructliste["$key"]);
							// echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
						}
					}
					//echo "<br>sousstructliste = "; print_r($sousstructliste); echo "<br>";
					$structureliste = array_merge($structureliste, (array)$sousstructliste);
					// Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
				}
			}
			else // La strcuture est fermée... Donc on la supprime de la liste.
			{
				//echo "    structkey = " . $structkey . "<br>";
				unset ($structureliste["$structkey"]);
			}
		}
		//echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo $structure->planninghtml($indexmois . "/"  . $annee);
		}
		
		$structureliste = $user->structrespliste();
		foreach ($structureliste as $structkey => $structure)
		{
			if (strcasecmp($structure->afficherespsousstruct(), "o") ==0 )
			{
				echo "<br>";
				echo $structure->planningresponsablesousstructhtml($indexmois . "/"  . $annee);
			}
		}
	}
	elseif (strcasecmp($mode,"gestion")==0)
	{
		$structureliste = $user->structgestliste();
		foreach ($structureliste as $structkey => $structure)
		{
			// Si la structure est ouverte => On la garde
			if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
			{
				if (strcasecmp($structure->sousstructure(), "o") ==0 )
				{
					$sousstructliste = $structure->structurefille();
					foreach ((array)$sousstructliste as $key => $struct)
					{
						// Si la structure est fermée.... On la supprime de la liste
						if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd")))
						{
							// echo "Index = " . array_search($struct, $sousstructliste) . "    Key = " . $key . "<br>";
							// echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
							unset ($sousstructliste["$key"]);
							// echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
						}
					}
					$structureliste = array_merge($structureliste, (array)$sousstructliste);
					// Remarque : Le tableau ne contiendra pas de doublon, car la clÃ© est le code de la structure !!!
				}
			}
			else // La strcuture est fermée... Donc on la supprime de la liste.
			{
				//echo "    structkey = " . $structkey . "<br>";
				unset ($structureliste["$structkey"]);
			}
		}
		//echo "StructureListe = "; print_r($structureliste); echo "<br>";
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo $structure->planninghtml($indexmois . "/"  . $annee);
		}

		$structureliste = $user->structgestliste();
		foreach ($structureliste as $structkey => $structure)
		{
			if (strcasecmp($structure->afficherespsousstruct(), "o") ==0 )
			{
				echo "<br>";
				echo $structure->planningresponsablesousstructhtml($indexmois . "/"  . $annee);
			}
		}
	}
	else
	{
		$affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));
		foreach ($affectationliste as $affectkey => $affectation)
		{
			$structureid = $affectation->structureid();
			$structure = new structure($dbcon);
			$structure->load($structureid);
			if (strcasecmp($structure->affichetoutagent(), "o") == 0)
			{
				echo "<br>";
//				echo "Planning de la structure : " . $structure->nomlong() . " ("   . $structure->nomcourt() . ") <br>";
				echo $structure->planninghtml($indexmois . "/"  . $annee, 'n');  // 'n' car l'agent ne doit pas voir les conges des sous-structures (si autorisé)
			}
		}
	}
	unset($strucuture);
?>

</body></html>