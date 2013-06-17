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
		
	echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	//print_r ( $_POST); echo "<br>";
	
	foreach ($_POST as $key => $value)
	{
		//echo "key = $key     value = $value <br>";
		//echo "Substr => "  . substr($key, 0, strlen("statut_")) . "<br>";
		$position = strpos($key, "_dossierid");
		if ($position !==FALSE)
		{
			$dossierid=$value;
			//echo "Dossierid = " . $dossierid . "<br>";
			$structid = substr($key,0,strpos($key, "_"));
			//echo "Structure id = " . $structid . "<br>";
			$dossier = new dossier($dbcon);
			$dossier->load($dossierid);
			$enregistrement_necessaire=FALSE; 
			//echo "enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			$nbrejrsenfantmalade = $_POST[$structid . "_" . $dossier->agentid() . "_nbjrsenfant"];
			//echo "nbrejrsenfantmalade = " . $nbrejrsenfantmalade . "  dossier->enfantmalade() = " . $dossier->enfantmalade() . "<br>";
//			if ($nbrejrsenfantmalade  != "")
			if (($nbrejrsenfantmalade != $dossier->enfantmalade()) and ($nbrejrsenfantmalade  != ""))
			{
				$dossier->enfantmalade($nbrejrsenfantmalade);
				$enregistrement_necessaire = TRUE;
			}
			//echo "enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			$statut = $_POST[$structid . "_" . $dossier->agentid() . "_statut"];
			//echo "statut = " . $statut . "  dossier->statut() = " . $dossier->statut() . "<br>";
//			if ($statut  != "")
			if (($statut != $dossier->statut()) and ($statut != ""))
			{
				$dossier->statut($statut);
				$enregistrement_necessaire = TRUE;
			}
			//echo "enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			$report = $_POST[$structid . "_" . $dossier->agentid() . "_report"];
			//echo "report = " . $report . "   dossier->reportactif() = " . $dossier->reportactif() . "<br>";
//			if ($report  != "")
			if (($report != chr(ord('n') + $dossier->reportactif())) and ($report  != ""))
			{
				$dossier->reportactif($report);
				$enregistrement_necessaire = TRUE;
			}
			//echo "enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			$cetactif = $_POST[$structid . "_" . $dossier->agentid() . "_cetactif"];
			//echo "cetactif = " . $cetactif . "   dossier->cetactif() = " . $dossier->cetactif() . "<br>";
//			if ($cetactif  != "")
			if (($cetactif != chr(ord('n') + $dossier->cetactif())) and ($cetactif != ""))
			{
				$dossier->cetactif($cetactif);
				$enregistrement_necessaire = TRUE;
			}
			//echo "enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			$datedebcet = $_POST[$structid . "_" . $dossier->agentid() . "_datedebutcet"];
			//echo "datedebcet = " . $datedebcet . "    dossier->datedebutcet() = " . $dossier->datedebutcet() . "<br>";
//			if ($datedebcet  != "")
			if (($datedebcet != $dossier->datedebutcet()) and ($datedebcet != "") ) 
			{
				//echo "Je suis dans le if <br>";
				$dossier->datedebutcet($datedebcet);
				$enregistrement_necessaire = TRUE;
			}
			//echo "TEST A LA FIN : enregistrement_necessaire = " . $enregistrement_necessaire . "<br>";
			if ($enregistrement_necessaire == TRUE)
			{
				$msgerreur = $dossier->store();
				$agent = new agent($dbcon);
				$agent->load($dossier->agentid());
				if ($msgerreur != "")
				{
					echo "<font color='red'>Echec de l'engistrement du dossier de " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() ." : " . $msgerreur . "</font>";
				}
				else
				{
					echo "<font color='green'>Le dossier de " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() .  " est correctement enregistré.</font><br>";
				}
				unset($agent);

			}
		}
		$position = strpos($key, "_displaysousstruct");
		if ($position !==FALSE)
		{
			$structid = substr($key,0,$position);
			$structure = new structure($dbcon);
			$structure->load($structid);
			$structure->sousstructure($value);
			$structure->store();
		}
		$position = strpos($key, "_displayallagent");
		if ($position !==FALSE)
		{
			$structid = substr($key,0,$position);
			$structure = new structure($dbcon);
			$structure->load($structid);
			$structure->affichetoutagent($value);
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
		if ($mode == 'resp')
		{
			echo "<br>";
			echo "Autoriser la consultation du planning de toute les structures filles à tous les agents de cette structure : (TEM_CONSULT_TTE_STRUCT) ";
			echo "<select name='" . $structure->id()  . "_displaysousstruct'>";
			echo "<option value='o'"; if (strcasecmp($structure->sousstructure(), "o")) echo " selected "; echo ">Oui</option>";
			echo "<option value='n'"; if (strcasecmp($structure->sousstructure(), "n")) echo " selected "; echo ">Non</option>";
			echo "</select>";
			echo "<br>";
			echo "Autoriser la consultation du planning de la structure à tous les agents de celle-ci (AGT_PLN_STR) :";
			echo "<select name='" . $structure->id()  . "_displayallagent'>";
			echo "<option value='o'"; if (strcasecmp($structure->affichetoutagent(),"o")) echo " selected "; echo ">Oui</option>";
			echo "<option value='n'"; if (strcasecmp($structure->affichetoutagent(),"n")) echo " selected "; echo ">Non</option>";
			echo "</select>";
			echo "<br>";
		}
	}	
	
	echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
	echo "<input type='hidden' name='action' value='" . $action ."'>";
	echo "<input type='hidden' name='mode' value='" . $mode ."'>";
	
	if ($action == 'modif') 
		echo "<input type='submit' value='Valider' />";
	echo "</form>";
	
?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

