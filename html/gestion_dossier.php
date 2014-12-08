<?php
	
	require_once('CAS.php');
	include './includes/casconnection.php';

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
	require_once("./class/tcpdf/tcpdf.php");
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
<!-- 
	<script>
		$(function()
		{
			$( ".calendrier" ).datepicker();
		});
	</script>
 -->
<?php	
		
	//echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	//print_r ( $_POST); echo "<br>";
	
	$reportlist = null;
	if (isset($_POST['report']))
		$reportlist = $_POST['report'];
		
	$enfantmaladelist = null;
	if (isset($_POST['enfantmalade']))
		$enfantmaladelist = $_POST['enfantmalade'];
		
	$cumultotallist = null;
	if (isset($_POST['cumultotal']))
		$cumultotallist = $_POST['cumultotal'];

	$array_agent_mail = null;
	if (isset($_POST['agent_mail']))
		$array_agent_mail = $_POST['agent_mail'];
	$array_resp_mail = null;
	if (isset($_POST['resp_mail']))
		$array_resp_mail = $_POST['resp_mail'];
		
		
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
			unset($complement);
		}
	}
	
	$msgerreur = "";
	if (is_array($enfantmaladelist))
	{
		foreach ($enfantmaladelist as $harpegeid => $enfantmaladevalue)
		{
			//echo "strcasecmp(intval ... => " . strcasecmp(intval($enfantmaladevalue),$enfantmaladevalue) . "<br>";
			//echo "intval >=0 => " . (intval($enfantmaladevalue)>=0) . "<br>";
			if ((strcasecmp(intval($enfantmaladevalue),$enfantmaladevalue)==0) and (intval($enfantmaladevalue)>=0))  // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
			{
				$complement = new complement($dbcon);
				$complement->complementid('ENFANTMALADE');
				$complement->harpegeid($harpegeid);
				$complement->valeur(intval($enfantmaladevalue));
				$complement->store();
				unset($complement);
			}
			else
			{
				$agent = new agent($dbcon);
				$agent->load($harpegeid);
				$msgerreur = $msgerreur . "Le nombre de jour 'enfant malade' saisi n'est pas correct pour l'agent " . $agent->identitecomplete() . " <br>";
				unset($agent);
			}
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
	
	$arraygestionnaire = null;
	if (isset($_POST["gestion"]))
		$arraygestionnaire = $_POST["gestion"];
	if (is_array($arraygestionnaire))
	{
		foreach ($arraygestionnaire as $structureid => $valeur)
		{
			//$structureid = str_replace("'", "", $structureid);
			$structure = new structure($dbcon);
			$structure->load($structureid);
			$structure->gestionnaire($valeur);
			$structure->store();
		}
	}
	
	if (isset($array_agent_mail))
	{
		// On modifie les codes des envois de mail pour les agents et les responsables
		foreach ($array_agent_mail as $structkey => $codeinterne)
		$structure = new structure($dbcon);
		$structure->load($structkey);
		$structure->agent_envoyer_a($codeinterne,true);
	}
	if (isset($array_resp_mail))
	{
		// On modifie les codes des envois de mail pour les agents et les responsables
		foreach ($array_resp_mail as $structkey => $codeinterne)
		$structure = new structure($dbcon);
		$structure->load($structkey);
		$structure->resp_envoyer_a($codeinterne,true);
	}
		
	echo "<br>";
	if ($msgerreur != "") {
		error_log(basename(__FILE__)." ".$fonctions->stripAccents($msgerreur));
		echo "<B><P style='color: red'> $msgerreur </P></B>";
	}
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

		if ($mode == 'resp')
		{
			$structure->agent_envoyer_a($codeinterne);
			echo "<table>";
			echo "<tr>";
			echo "<td>";
			echo "Envoyer les demandes de congés des agents au : ";
			echo "<SELECT name='agent_mail[" . $structure->id() . "]' size='1'>";
			echo "<OPTION value=1";
			if ($codeinterne==1) echo " selected='selected' ";
			echo ">Responsable du service " . $structure->nomcourt() . "</OPTION>";
			echo "<OPTION value=2";
			if ($codeinterne==2) echo " selected='selected' ";
			echo ">Gestionnaire du service " . $structure->nomcourt() . "</OPTION>";
			echo "</SELECT>";
			echo "</td>";
			echo "</tr>";
			
			$parentstruct = null;
			$parentstruct = $structure->parentstructure();
			$structure->resp_envoyer_a($codeinterne);
			echo "<tr>";
			echo "<td>";
			echo "Envoyer les demandes de congés du responsable au : ";
			echo "<SELECT name='resp_mail[" . $structure->id() . "]' size='1'>";
			if (!is_null($parentstruct))
			{
				echo "<OPTION value=1";
				if ($codeinterne==1) echo " selected='selected' ";
				echo ">Responsable du service " . $parentstruct->nomcourt() . "</OPTION>";
				echo "<OPTION value=2";
				if ($codeinterne==2) echo " selected='selected' ";
				echo ">Gestionnaire du service " . $parentstruct->nomcourt() . "</OPTION>";
			}
			echo "<OPTION value=3";
			if ($codeinterne==3) echo " selected='selected' ";
			echo ">Gestionnaire du service " . $structure->nomcourt() . "</OPTION>";
			echo "</SELECT>";
			echo "</td>";
			echo "</tr>";
			$gestionnaire = $structure->gestionnaire();
			echo "\n<tr>";
			echo "<td>Nom du gestionnaire : ";
			echo "<input id='infouser[". $structure->id() ."]' name='infouser[". $structure->id() ."]' placeholder='Nom et/ou prenom' value='";
			if (!is_null($gestionnaire)) echo $gestionnaire->identitecomplete();
			echo "' size=40 />";
			//  
            echo "<input type='hidden' id='gestion[". $structure->id() ."]' name='gestion[". $structure->id() ."]' value='";
            if (!is_null($gestionnaire)) echo $gestionnaire->harpegeid();
            echo "' class='infouser[". $structure->id() ."]' /> ";
?>
	<script>
	    	$('[id="<?php echo "infouser[". $structure->id() ."]" ?>"]').autocompleteUser(
	  	       'https://wsgroups.univ-paris1.fr/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "supannEmpId",
	  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
   </script>
<?php 
			echo "</tr>";
			echo "<tr><td height=15></td></tr>";
			echo "</table>";	
		}
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

