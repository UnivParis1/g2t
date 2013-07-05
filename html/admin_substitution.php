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
	
	
	// Initialisation de l'utilisateur
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
	//echo "Le num�ro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
	$adminuser = new agent($dbcon);
	$adminuser->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
	if (!$adminuser->estadministrateur())
	{
		error_log (basename(__FILE__)  . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
		header('Location: index.php');
		exit();
	}
	
	
	$user = new agent($dbcon);
	$user->load($userid);
	

	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';

//	echo "<br>Planning de l'agent " . $user->civilite() . " "  . $user->nom() . " " . $user->prenom() . " <br>";
	
?>
<br>
<form name='subst_agent' method='post' action='index.php'>
   <input id="userid" name="userid" placeholder="Nom et/ou prenom" />

   <script>
    $( "#userid" ).autocompleteUser(
       'https://ticetest.univ-paris1.fr/wsgroups/searchUserCAS', { wantedAttr: "supannEmpId",
                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } }
    );
   </script>


<br> 
<input type='text' name='userid' >

<input type='submit' value='Se faire passer pour...'>
</form>

</body></html>
