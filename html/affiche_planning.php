<?php

	require_once('./CAS/CAS.php');
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

	require ("includes/menu.php");
	echo '<html><body class="bodyhtml">';

	echo "<br>Planning de l'agent " . $user->civilite() . " "  . $user->nom() . " " . $user->prenom() . " <br>";
	
	if ($fonctions->liredbconstante("LIMITE_CONGE_PERIODE") == "n")
	{
		$datetemp = ($fonctions->anneeref()+1) . $fonctions->finperiode();
		$timestamp = strtotime($datetemp);
		$datetemp = date("Ymd", strtotime("+1month", $timestamp ));  // On passe au mois suivant
		$timestamp = strtotime($datetemp);
		$datetemp = date("Ymd", strtotime("-1days", $timestamp ));  // On passe à la veille
		echo $user->planninghtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$datetemp);
	}
	else
		echo $user->planninghtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()));
	
?>
</body></html>