<?php

	require_once('CAS.php');
	require_once("./class/fonctions.php");
	require_once('./includes/dbconnection.php');
 
	$fonctions = new fonctions($dbcon);
	
	// Parametres pour connexion CAS
	$CAS_SERVER=$fonctions->liredbconstante("CASSERVER");
	$CAS_PORT=443;
	$CAS_PATH=$fonctions->liredbconstante("CASPATH");
	phpCAS::client(CAS_VERSION_2_0,$CAS_SERVER,$CAS_PORT,$CAS_PATH);
	//phpCAS::setDebug("D:\Apache\logs\phpcas.log");
	//      phpCAS::setFixedServiceURL("http://mod11.parc.univ-paris1.fr/ReturnURL.html");
	phpCAS::setNoCasServerValidation();
	// Recuperation de l'uid
	phpCAS::forceAuthentication();
	 
	$uid=phpCAS::getUser();
	//echo "UID de l'agent est : " . $uid . "<br>";
 
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
	require_once("./class/fpdf.php");
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
			if (strcasecmp($structure->sousstructure(), "o") ==0 )
			{
				$sousstructliste = $structure->structurefille();
				$structureliste = array_merge($structureliste, (array)$sousstructliste);
				// Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
			}
		}
		//echo "StructureListe = "; print_r($structureliste); echo "<br>";
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo $structure->planninghtml($indexmois . "/"  . $annee);
		}
	}
	elseif (strcasecmp($mode,"gestion")==0)
	{
		$structureliste = $user->structgestliste();
		foreach ($structureliste as $structkey => $structure)
		{
			if (strcasecmp($structure->sousstructure(), "o") ==0 )
			{
				$sousstructliste = $structure->structurefille();
				$structureliste = array_merge($structureliste, (array)$sousstructliste);
				// Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
			}
		}
		//echo "StructureListe = "; print_r($structureliste); echo "<br>";
		foreach ($structureliste as $structkey => $structure)
		{
			echo "<br>";
			echo $structure->planninghtml($indexmois . "/"  . $annee);
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
				echo $structure->planninghtml($indexmois . "/"  . $annee);
			}
		}
	}
	unset($strucuture);
?>

</body></html>