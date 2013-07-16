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
		
	$user = new agent($dbcon);
	$user->load($userid);

	$action = $_POST["action"];  // Action = lecture ou modif
	if (is_null($action) or $action == "")
		$action = 'lecture';
	$mode = $_POST["mode"];  // Action = gestion ou resp
	if (is_null($action) or $action == "")
		$action = 'resp';
	
	//echo "Apres le chargement du user !!! <br>";
	require ("includes/menu.php");
	
?>
	<script src="javascripts/jquery-1.8.3.js"></script>
	<script src="javascripts//jquery-ui.js"></script>
	<script>
		$(function()
		{
			$( ".calendrier" ).datepicker();
		});
	</script>
<?php	
		
	//echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	//print_r ( $_POST); echo "<br>";
	
	$reportlist = null;
	if (isset($_POST['report']))
		$reportlist = $_POST['report'];
		
	$cumultotallist = null;
	if (isset($_POST['cumultotal']))
		$cumultotallist = $_POST['cumultotal'];
		
	$datedebutcetlist= null;
	if (isset($_POST['datedebutcet']))
		$datedebutcetlist = $_POST['datedebutcet'];
	
	if (is_array($reportlist))
	{
		foreach ($reportlist as $harpegeid => $reportvalue)
		{
			$complement = new complement($dbcon);
			$complement->complementid('REPORTACTIF');
			$complement->harpegeid($harpegeid);
			$complement->valeur($reportvalue);
			$complement->store();
		}
	}
	
	if (is_array($cumultotallist))
	{
		foreach ($cumultotallist as $harpegeid => $cumultotal)
		{
			if (isset($datedebutcetlist[$harpegeid]))
			{
				if ($fonctions->verifiedate($datedebutcetlist[$harpegeid]))
				{
					$cet = new cet($dbcon);
					$cet->cumultotal($cumultotal);
					$cet->agentid($harpegeid);
					$cet->datedebut($datedebutcetlist[$harpegeid]);
					$cet->store();
				}
			}
		}
	}
	
	$displaysousstructlist = null;
	if (isset($_POST["displaysousstruct"]))
		$displaysousstructlist = $_POST["displaysousstruct"];
	if (is_array($displaysousstructlist))
	{
		foreach ($displaysousstructlist as $structureid => $valeur)
		{
			$structureid = str_replace("'", "", $structureid);
			$structure = new structure($dbcon);
			$structure->load($structureid);
			$structure->sousstructure($valeur);
			$structure->store();
		}
	}
		
	$displayallagentlist = null;
	if (isset($_POST["displaysousstruct"]))
		$displayallagentlist = $_POST["displayallagent"];
	if (is_array($displayallagentlist))
	{
		foreach ($displayallagentlist as $structureid => $valeur)
		{
			$structureid = str_replace("'", "", $structureid);
			$structure = new structure($dbcon);
			$structure->load($structureid);
			$structure->affichetoutagent($valeur);
			$structure->store();
		}
	}
		
	echo "<br>";
	echo "<form name='frm_dossier'  method='post' >";
	if ($mode == 'resp')
		$structliste = $user->structrespliste();
	if ($mode == 'gestion')
		$structliste = $user->structgestliste();
	//print_r($structliste); echo "<br>";
	foreach ($structliste as $key => $structure)
	{
		if ($mode == 'resp')
			echo $structure->dossierhtml(($action == 'modif'),$userid);
		else
			echo $structure->dossierhtml(($action == 'modif'));

		echo "Autoriser la consultation du planning de toute les structures filles à tous les agents de cette structure : ";
		if ($action == 'modif' )
		{
			echo "<select name=displaysousstruct['" . $structure->id() . "']>";
			echo "<option value='o'"; if (strcasecmp($structure->sousstructure(), "o")==0) echo " selected "; echo ">Oui</option>";
			echo "<option value='n'"; if (strcasecmp($structure->sousstructure(), "n")==0) echo " selected "; echo ">Non</option>";
			echo "</select>";
		}
		else 
			echo $fonctions->ouinonlibelle($structure->sousstructure());
		echo "<br>";
		echo "Autoriser la consultation du planning de la structure à tous les agents de celle-ci : ";
		if ($action == 'modif' )
		{
			echo "<select name=displayallagent['" . $structure->id() . "']>";
			echo "<option value='o'"; if (strcasecmp($structure->affichetoutagent(),"o")==0) echo " selected "; echo ">Oui</option>";
			echo "<option value='n'"; if (strcasecmp($structure->affichetoutagent(),"n")==0) echo " selected "; echo ">Non</option>";
			echo "</select>";
		}
		else
			echo $fonctions->ouinonlibelle($structure->affichetoutagent());
		echo "<br><br><br>";
	}	
	
	echo "<input type='hidden' name='userid' value=" . $user->harpegeid() .">";
	echo "<input type='hidden' name='action' value=" . $action .">";
	echo "<input type='hidden' name='mode' value='" . $mode ."'>";
	
	if ($action == 'modif') 
		echo "<input type='submit' value='Valider' />";
	echo "</form>";
	
?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

