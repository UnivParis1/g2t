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

	if (isset($_POST["agentid"]))
	{
		$agentid = $_POST["agentid"];
		$agent = new agent($dbcon);
		$agent->load($agentid);
	}
	else
	{
		$agentid = null;
		$agent = null;
	}

	$nbr_jours_conges = null;
	$commentaire_supp = null;
	if (isset($_POST["nbr_jours_conges"]))
		$nbr_jours_conges = $_POST["nbr_jours_conges"];
	if (isset($_POST["commentaire_supp"]))
		$commentaire_supp = $_POST["commentaire_supp"];
	
	$msg_erreur = "";
	
	require ("includes/menu.php");
	//echo '<html><body class="bodyhtml">';
	echo "<br>";
	
	if ($agentid == "")
	{
		echo "<form name='selectagentcongessupp'  method='post' >";

		$structureliste = $user->structrespliste();
		//echo "Liste de structure = "; print_r($structureliste); echo "<br>";
		$agentlistefull = array();
		foreach ($structureliste as $structure)
		{
			$agentliste=$structure->agentlist(date("d/m/Y"), date("d/m/Y"));
			//echo "Liste de agents = "; print_r($agentliste); echo "<br>";
			$agentlistefull = array_merge((array)$agentlistefull, (array)$agentliste);
			//echo "fin du select <br>";
		}
		ksort($agentlistefull);
		echo "<SELECT name='agentid'>";
		foreach ($agentlistefull as $keyagent => $membre)
		{
			echo "<OPTION value='" . $membre->harpegeid() .  "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom()  . "</OPTION>";
		}
		echo "</SELECT>";
		echo "<br>";

		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='submit' value='Valider' >";
		echo "</form>";
	}
	else
	{
		if (!is_null($nbr_jours_conges))
		{
			// On a cliqué sur le bouton validé ==> On va vérifier la saisie
			$nbr_jours_conges = str_replace(",", ".", $nbr_jours_conges);
			//echo "nbr_jours_conges = $nbr_jours_conges <br>";
			if ($nbr_jours_conges == "" or $nbr_jours_conges <= 0) 
			{
				$msg_erreur = $msg_erreur . "Vous n'avez pas saisi le nombre de jours à ajouter ou il est inférieur ou égal à 0 <br>";
			}
			if ($commentaire_supp == "")
			{
				$msg_erreur = $msg_erreur . "Vous n'avez pas saisi de commentaire. Celui-ci est obligatoire <br>";
			}
			if ($msg_erreur == "")
			{
				$solde = new solde($dbcon);
				$annee = substr($fonctions->anneeref(), 2, 2);
				$lib_sup = "sup$annee";
				//echo "lib_sup = $lib_sup <br>";
				$erreur = $solde->load($agentid, $lib_sup);
				//echo "Erreur = $erreur <br>";
				if ($erreur != "")
				{
					unset ($solde);
					$solde = new solde($dbcon);
					$msg_erreur = $msg_erreur . $solde->creersolde($lib_sup,$agentid);
					//echo "msg_erreur = $msg_erreur <br>";
					$msg_erreur = $msg_erreur . $solde->load($agentid, $lib_sup);
					//echo "msg_erreur = $msg_erreur <br>";
				}
				$commentaire_supp = $commentaire_supp . " (par " . $user->prenom() . " " . $user->nom() . ")";
				$nouv_solde = ($solde->droitaquis() + $nbr_jours_conges);
				$solde->droitaquis($nouv_solde);
				$msg_erreur = $msg_erreur . $solde->store();
				$msg_erreur = $msg_erreur . $agent->ajoutecommentaireconge($lib_sup,$nbr_jours_conges,$commentaire_supp);
			   //echo "msg_erreur = $msg_erreur <br>";
			}
			if ($msg_erreur != "")
				echo "<P style='color: red'>Les jours supplémentaires n'ont pas été enregistrés... ==> MOTIF : ".  $msg_erreur . "</P>";
			elseif (!is_null($solde))
				echo "<P style='color: green'>Les jours supplémentaires ont été enregistrés... Nouveau solde = " . $solde->droitaquis() . "</P>";
		}
		else
		{
			// On est au premier affichage de l'écran apres la selection de l'agent ==> Pas de control de saisi
			echo "<P style='color: red'>Le motif de l'ajout est obligatoire</P><br>";
		}

		echo "Ajout de jours de congés supplémentaires pour l'agent : " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom()  . "<br>";
		echo "<form name='frm_ajoutconge'  method='post' >";
	// 	echo "Sélectionnez l'agent auquel vous voullez ajouter des jours supplémentaires : ";
	// 	$agentliste=$user->structure()->agentlist();
	// 	echo "<SELECT name='agentid'>";
	// 	foreach ($agentliste as $keyagent => $membre)
	// 	{
	// 		echo "<OPTION value='" . $membre->id() .  "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom()  . "</OPTION>";
	// 	}
	// 	echo "</SELECT>";
		
	// 	echo "<br>";
		
		echo "Nombre de jours supplémentaires à ajouter : <input type=text name=nbr_jours_conges id=nbr_jours_conges size=3 >";
		echo "<br>";
		echo "Motif (Obligatoire) : <input type=text name=commentaire_supp id=commentaire_supp size=25 >";
		echo "<br>";
		
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
		echo "<input type='submit' value='Valider' >";
		echo "</form>";
	}

?>

<!-- 
<a href=".">Retour à la page d'accueil</a> 
 -->
</body></html>

