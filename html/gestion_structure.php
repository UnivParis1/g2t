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

	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
		
	if (isset($_POST["structureid"]))
		$structureid = $_POST["structureid"];
	else
		$structureid = null;
		
	if (isset($_POST["gestion"]))
		$gestionnaireliste =  $_POST["gestion"];
	else
		$gestionnaireliste = array();
	
	if (isset($_POST["resp"]))
		$responsableliste =  $_POST["resp"];
	else
		$responsableliste = array();
	//print_r ($_POST); echo "<br>";
	
	if (!is_null($structureid))
	{
		// On parcours touts les gestionnaires - mais on pourrait prendre les responsables
		foreach ($gestionnaireliste as $structid => $gestionnaireid)
		{
			$structure = new structure($dbcon);
			//echo "Avant le load <br>";
			$structure->load($structid);
			
			// On modifie les codes des envois de mail pour les agents et les responsables
			$structure->resp_envoyer_a($_POST["resp_mail"][$structid],true);
			$structure->agent_envoyer_a($_POST["agent_mail"][$structid],true);
			
			$structure->responsable($responsableliste[$structid]);
			$structure->gestionnaire($gestionnaireid);
			$msgerreur = $structure->store();
			//echo "Apres le store <br>";
				
			if ($msgerreur != "")
				echo "<p style='color: red'>Pas de sauvegarde car " . $msgerreur . "</p><br>";
			else
			{
				// Tout c'est bien passé
			}
		}
		
	}

	$sql="SELECT STRUCTUREID,NOMCOURT,NOMLONG FROM STRUCTURE WHERE length(STRUCTUREID)<='3' ORDER BY NOMCOURT"; //NOMLONG
	$query=mysql_query ($sql,$dbcon);
	$erreur=mysql_error();
	if ($erreur != "")
		echo "Gestion Structure : " . $erreur . "<br>";
	echo "<form name='selectstructure'  method='post' >";
	echo "<select name='structureid'>";
	while ($result = mysql_fetch_row($query))
	{
		echo "<option  value='" . $result[0] .  "'";
		if ($result[0] == $structureid)
			echo " selected ";
		echo ">" . $result[2] . " (" . $result[1] . ")" . "</option>";
	}
	echo "</select>";
	echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
	echo " <input type='submit' name= 'Valid_struct' value='Valider' >";
	echo "</form>";
	echo "<br>";
	
	if (!is_null($structureid))
	{
		$structure = new structure($dbcon);
		$structure->load($structureid);

		// On utilise la liste des structures filles pour afficher la structure courante et les structures filles
		$structureliste = $structure->structurefille();
		// On ajoute la structure courante au tableau
		$structureliste[$structureid] = $structure;
		// On trie par la clé => La clé de la structure parente est plus petite (car 3 lettres) donc elle est en tete du tableau !!
		ksort($structureliste);
		//print_r($structureliste); echo "<br>";
		
		echo "<form name='paramstructure'  method='post' >";
		echo "<table>";
		foreach ($structureliste as $keystruc => $struct)
		{
			
			echo "<tr>";
			echo "<td align=center class='titresimple'>" . $struct->nomcourt() . " " . $struct->nomlong() .  " - Responsable : " . $struct->responsable()->civilite() . " " . $struct->responsable()->nom() . " ". $struct->responsable()->prenom() . "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align=center>Gestionnaire : ";
			echo "<select name=gestion[". $struct->id() ."]>";
			$agentliste = $structure->agentlist(date('Ymd'),date('Ymd'),TRUE); 
			$gestionnaire = $struct->gestionnaire();
			
			foreach ($agentliste as $key => $agent)
			{
				echo "<option  value='" . $agent->harpegeid() .  "'";
				if ($agent->harpegeid() == $gestionnaire->harpegeid())
					echo " selected ";
				echo ">" . $agent->civilite() . " " . $agent->nom() . " ". $agent->prenom()  . "</option>";
			}
			echo "</select>";
			echo " &nbsp; Direction : ";
			echo "<select name=resp[". $struct->id() . "]>";
			$responsable = $struct->responsable();
			foreach ($agentliste as $key => $agent)
			{
				echo "<option  value='" . $agent->harpegeid() .  "'";
				if ($agent->harpegeid() == $responsable->harpegeid())
					echo " selected ";
				echo ">" . $agent->civilite() . " " . $agent->nom() . " ". $agent->prenom()  . "</option>";
			}
			
			echo "</select>";
			echo "</td>";
			echo "</tr>";

			$struct->agent_envoyer_a($codeinterne);
			echo "<tr>";
			echo "<td>";
			echo "Envoyer les demandes de congés des agents au : ";
			echo "<SELECT name='agent_mail[" . $struct->id() . "]' size='1'>";
			echo "<OPTION value=1";
			if ($codeinterne==1) echo " selected='selected' ";
			echo ">Responsable du service " . $struct->nomcourt() . "</OPTION>";
			echo "<OPTION value=2";
			if ($codeinterne==2) echo " selected='selected' ";
			echo ">Gestionnaire du service " . $struct->nomcourt() . "</OPTION>";
			echo "</SELECT>";
			echo "</td>";
			echo "</tr>";
			
			$parentstruct = null;
			$parentstruct = $struct->parentstructure();
			$struct->resp_envoyer_a($codeinterne);
			echo "<tr>";
			echo "<td>";
			echo "Envoyer les demandes de congés du responsable au : ";
			echo "<SELECT name='resp_mail[" . $struct->id() . "]' size='1'>";
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
			echo ">Gestionnaire du service " . $struct->nomcourt() . "</OPTION>";
			echo "</SELECT>";
			echo "</td>";
			echo "</tr>";
			echo "<tr><td height=15></td></tr>";
		}
		echo "</table>";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='structureid' value='" . $structureid ."'>";
		echo "<input type='submit' name= 'Modif_struct' value='Enregistrer les modifications' >";
		echo "</form>";
		echo "<br>";
	}
	
?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
-->
</body></html>

