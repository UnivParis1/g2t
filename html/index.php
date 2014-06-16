<?php
	require_once('CAS.php');
	require_once("./class/fonctions.php");
	require_once('./includes/dbconnection.php');
 
	$fonctions = new fonctions($dbcon);
	
	// when using a reverse proxy, HTTP_X_FORWARDED_HOST is handled by phpCAS, but it can't know the case proxy is https but real server is http
	$_SERVER['HTTPS'] = true;
	$_SERVER['SERVER_PORT'] = 443;
	
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

	

	// Initialisation de l'utilisateur
	$userid = null;
	if (isset($_POST["userid"]))
		$userid = $_POST["userid"];
	$user = new agent($dbcon);
	
	if (is_null($userid) or $userid == "")
	{
		//echo "L'agent n'est pas passé en paramètre.... Récupération de l'agent à partir du ticket CAS <br>";
		$LDAP_SERVER=$fonctions->liredbconstante("LDAPSERVER");
		$LDAP_BIND_LOGIN=$fonctions->liredbconstante("LDAPLOGIN");
		$LDAP_BIND_PASS=$fonctions->liredbconstante("LDAPPASSWD");
		$LDAP_SEARCH_BASE=$fonctions->liredbconstante("LDAPSEARCHBASE");
		$LDAP_CODE_AGENT_ATTR=$fonctions->liredbconstante("LDAPATTRIBUTE");
		$con_ldap=ldap_connect($LDAP_SERVER);
		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		$r=ldap_bind($con_ldap, $LDAP_BIND_LOGIN,$LDAP_BIND_PASS);
		$filtre="(uid=$uid)";
		$dn=$LDAP_SEARCH_BASE;
		$restriction=array("$LDAP_CODE_AGENT_ATTR");
		$sr=ldap_search ($con_ldap,$dn,$filtre,$restriction);
		$info=ldap_get_entries($con_ldap,$sr);
		//echo "Le numéro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
		if (!$user->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
		{
			echo "<br><font color=#FF0000>Vous n'êtes pas authorisé à vous connecter à cette application...</font>";
			return;
		}
		$_SESSION['phpCAS']['harpegeid'] = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
		//echo "Je viens de set le param - index.php<br>";
		//echo "Avant le recup user-> id";
		$userid = $user->harpegeid();
		//echo "Apres le recup user-> id";
	}
	else
	{
		if (!$user->load($userid))
		{
			echo "<br><font color=#FF0000>Vous n'êtes pas authorisé à vous connecter à cette application...</font>";
			return;
		}
	}

	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
	
	//echo "Date du jour = " . date("d/m/Y") . "<br>";
	$affectationliste = $user->affectationliste(date("d/m/Y"), date("d/m/Y"));

	echo "<br>Bonjour " . $user->identitecomplete() . " : <br>";
	if (!is_null($affectationliste))
	{
		$affectation = reset($affectationliste);
		//$affectation = $affectationliste[0];
		$structure = new structure($dbcon);
		$structure->load($affectation->structureid());
		echo $structure->nomlong();
	}
	else
		echo "Pas d'affectation actuellement => Pas de structure";

	//   $tempstructid = $user->structure()->id();
	echo "<br><br>";


/*	
 	echo "<br>";
 	if ($user->dossiercomplet($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode())))
 		echo "Le dossier est complet <br>";
 	else
 		echo "Le dossier est INcomplet <br>";
*/
/* 	
 	$structure = new structure($dbcon);
 	$structure->load("DGH");
 	$structure->sousstructure("o");
 	echo "Liste des agents de la structure " . $structure->nomlong() . " : <br>";
 	if (!is_null($structure))
 	{
 		$agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
 		$agent = new agent($dbcon);
 		foreach ($agentliste as $key => $agent)
 		{
 			$affectationliste = $agent->affectationliste(date("d/m/Y"),date("d/m/Y"));
			$affectation = reset($affectationliste);
 			//$affectation = $affectationliste[0];
 			unset($structure);
 			$structure = new structure($dbcon);
 			$structure->load($affectation->structureid());
 			echo "L'agent " . $agent->identitecomplete() . " est dans la strcuture " . $structure->nomlong() . "<br>";
 		}
 	}
*/
/* 	
 	$structure = new structure($dbcon);
 	$structure->load("DGHC");
 	$structure->sousstructure("o");
 	echo "<br>Planning de la structure " . $structure->nomlong() . " :<br>";
 	echo $structure->planninghtml("03/2013");
*/	
	echo $user->soldecongeshtml($fonctions->anneeref());
	
	echo $user->affichecommentairecongehtml();
	echo $user->demandeslistehtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()));

?>
</body></html>