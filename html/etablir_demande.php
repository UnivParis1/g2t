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
	//echo "Userid = " . $userid;
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

	// R�cup�ration de l'agent reponsable...
	if (isset($_POST["responsable"]))
	{
		$responsableid = $_POST["responsable"];
		$responsable = new agent($dbcon);
		$responsable->load($responsableid);
	}
	else
	{
		$responsableid = null;
		$responsable = null;
	}
	
	// Si pass� en param�tre : Soit 'conges', soit 'absence'
	// permet d'afficher la page en mode 'demande d'absence' ou en mode 'demande de conges'
	if (isset($_POST["typedemande"]))
	{
		$typedemande = $_POST["typedemande"];
	}
	else
	{
		$typedemande = "conges";
		//$typedemande = "absence";
		//echo "Le type de page n'est pas renseign�... On le fixe � " .  $typedemande . "<br>";
	}
	
	if (isset($_POST["previous"]))
		$previoustxt = $_POST["previous"];
	else
		$previoustxt = null;
	if (strcasecmp($previoustxt,"yes")==0)
		$previous=1;
	else
		$previous=0;

	
	if (isset($_POST["agentid"]))
		$agentid = $_POST["agentid"];
	else
		$agentid = null;
	$agent = new agent($dbcon);
	//echo "agentid = " . $agentid . "<br>";
	if ((is_null($agentid) or $agentid == "") and is_null($responsable))
	{
		//echo "L'agent n'est pas pass� en param�tre.... R�cup�ration de l'agent � partir du ticket CAS <br>";
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
		$agent->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
	}
	elseif ((!is_null($agentid)) and $agentid != "")
		$agent->load($agentid);
	else
		$agent = null;
	//print_r($_POST); echo "<br>";

	$datefausse = FALSE;
	$masquerboutonvalider = FALSE;
	$msg_erreur = "";
	
	// R�cup�ration de la date de d�but
	if (isset($_POST["date_debut"]))
	{
		$date_debut = $_POST["date_debut"];
		//echo "date_debut = $date_debut <br>";
		//echo "fonctions->verifiedate(date_debut) = " . $fonctions->verifiedate($date_debut) . "<br>";
		if ($date_debut == "" or !$fonctions->verifiedate($date_debut))  //is_null($date_debut) or 
		{
			//Echo "La date est fausse !!!! <br>";
			$msg_erreur = $msg_erreur . "La date de d�but n'est pas initialis�e ou est incorrecte (JJ/MM/AAAA) !!! <br>";
			$datefausse = TRUE;
		}
		else
		{
			// R�cup�ration du moment de d�but
			if (isset($_POST["deb_mataprem"]))
				$deb_mataprem = $_POST["deb_mataprem"];
			else
				$deb_mataprem = null;
			if (is_null($deb_mataprem) or $deb_mataprem == "")
			{
				$msg_erreur = $msg_erreur . "Le moment de d�but n'est pas initialis� !!! <br>";
			}
		}
	}
	else
	{ 
		$date_debut = null;
		$datefausse = TRUE;
	}
	
	// R�cup�ration de la date de fin
	if (isset($_POST["date_fin"]))
	{
		//echo "date_fin = $date_fin <br>";
		//echo "fonctions->verifiedate(date_fin) = " . $fonctions->verifiedate($date_fin) . "<br>";
		$date_fin = $_POST["date_fin"];
		if ($date_fin == "" or !$fonctions->verifiedate($date_fin))  //is_null($date_fin) or 
		{
			$msg_erreur = $msg_erreur . "La date de fin n'est pas initialis�e ou est incorrecte !!! <br>";
			$datefausse = TRUE;
		}
		else
		{
			// R�cup�ration du moment de fin
			if (isset($_POST["fin_mataprem"]))
				$fin_mataprem = $_POST["fin_mataprem"];
			else
				$fin_mataprem = null;
			if (is_null($fin_mataprem) or (strcasecmp($fin_mataprem,"m")!=0 and strcasecmp($fin_mataprem,"a")!=0))
			{
				$msg_erreur = $msg_erreur . "Le moment de fin n'est pas initialis� !!! <br>";
			}
		}
	}
	else
	{
		$date_fin = null;
		$datefausse = TRUE;
	}
	
	if ($msg_erreur == "" and !$datefausse)
	{
		$datedebutdb = $fonctions->formatdatedb($date_debut);
		$datefindb = $fonctions->formatdatedb($date_fin);
		if ($datedebutdb > $datefindb or ($datedebutdb == $datefindb and $deb_mataprem == 'a' and $fin_mataprem == 'm'))
		{
			$msg_erreur = $msg_erreur . "Il y a une incoh�rence entre la date de d�but et la date de fin !!! <br>";
		}
	}

	## R�cup�ration du type de l'absence (annuel, CET, ...)
	if (isset($_POST["listetype"]))
		$listetype = $_POST["listetype"];
	else
		$listetype =null;
	if (is_null($listetype) or $listetype== "")
	{
		//echo "Le type de demande n'est pas initialis� !!! <br>";
	}
	
	## R�cup�ration du commentaire (s'il existe)
	$commentaire = "";
	if (isset($_POST["commentaire"]))
		$commentaire = $_POST["commentaire"];
	if (!is_null($responsable) and $commentaire == "")
	{
		$msg_erreur = $msg_erreur . "Le commentaire dans la saisie est obligatoire !!! <br>";	
	}
	//echo "Le commentaire vaut : " . $commentaire . "<br>";
	
	if (isset($_POST["congeanticipe"]))
		$congeanticipe = $_POST["congeanticipe"];
	else 
		$congeanticipe = null;
	
	## On regarde si le dossier est complet pour la p�riode demand�e ==> Si pas !! Pas de saisie possible
	if (!is_null($agent) and !$datefausse)
	{
		if (!$agent->dossiercomplet($date_debut,$date_fin))
		{
			$msg_erreur = $msg_erreur . "<br><b>Le dossier est incomplet sur la p�riode $date_debut -> $date_fin ==> Vous ne pouvez pas �tablir de demande !!! </b><br>";
			//$masquerboutonvalider = TRUE;
		}
	}
	
	require ("includes/menu.php");
?>
	<script type="text/javascript">
	// fonction pour le click gauche
	function planning_lclick(date,moment)
	{
		//alert("planning_click => " + date + "  "  + moment);
		document.getElementById("date_debut").value = date;
		
		if (strcasecmp(moment,"m")==0)
			document.frm_demande_conge["deb_mataprem"][0].checked = true;
		else
			document.frm_demande_conge["deb_mataprem"][1].checked = true;
	}
	// fonction pour le click droit
	function planning_rclick(date,moment)
	{
		//alert("planning_click => " + date + "  "  + moment);
		document.getElementById("date_fin").value = date;
		
		if (strcasecmp(moment,"m")==0)
			document.frm_demande_conge["fin_mataprem"][0].checked = true;
		else
			document.frm_demande_conge["fin_mataprem"][1].checked = true;
	}
	</script>
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
	
	################################################################
	## Affichage
	################################################################
	
	if (is_null($agent))
	{
		echo "<form name='demandeforagent'  method='post' action='etablir_demande.php'>";
		$structureliste = $responsable->structrespliste();
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
			
		echo "<input type='hidden' name='typedemande' value='" . $typedemande . "'>";
		echo "<input type='hidden' name='responsable' value='" . $responsable->harpegeid() ."'>";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='congeanticipe' value='" . $congeanticipe  . "'>";
		echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
		echo "<input type='submit' value='Valider' >";
	   echo "</form>";
	}
	else
	{
		if (strcasecmp($typedemande,"conges")==0)
		{
			echo "Demande de cong�s pour " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
			$solde = new solde($dbcon);
			$codecongeanticipe =  "ann" . substr($fonctions->anneeref()+1-$previous, 2);
			$result = $solde->load($agent->harpegeid(),$codecongeanticipe);
			if ($congeanticipe != "")
			{
				// On pose un cong� par anticipation
				//		- V�rifier que l'utilisateur est responsable (ou pas !!!)
				//		- V�rifier que le solde du cong� annuel est = 0
				//		- Afficher le cong� annuel de l'ann�e de ref + 1
				if ($result != "")
				{
					$result = $solde->creersolde($codecongeanticipe,$agent->harpegeid()) ;
					if ($result != "")
					{
						$msg_erreur = $msg_erreur . "<br><b>" . $result . "</b>";
						$msg_erreur = $msg_erreur . "<b>Contactez l'administrateur pour qu'il cr�e le type de cong�s...</b><br>";
						$masquerboutonvalider = TRUE;  // Emp�che le bouton de s'afficher !!!
					}
					else
						$msg_erreur = $msg_erreur . "<br><P style='color: green'>Cr�ation du solde de cong�s " . $codecongeanticipe . " pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() ."</P><br>";
				}
				else
				{
					//echo "Avant solde liste... <br>";
					$soldeliste = $agent->soldecongesliste($fonctions->anneeref()-$previous);
					//echo "Avant le for each <br>";
					foreach ($soldeliste as $keysolde => $solde)
					{
						if (strcasecmp($solde->typeabsenceid(),"ann")==0 . substr(($fonctions->anneeref()-$previous), 2))
						{
							if ($solde->solde() != 0)
							{
								$msg_erreur = $msg_erreur . "<br><b>Impossible de poser un cong� par anticipation. Il reste " . $solde->solde() . " jours de cong�s � poser pour " .$solde->typelibelle() . "</b><br>";
								$masquerboutonvalider = TRUE;  // Emp�che le bouton de s'afficher !!!
							}
						}
					}
					//echo "Apres le foreach <br>";
				}
			}
		}
		else
		{
			echo "Demande d'autorisation d'absence pour " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
		}
		//echo "Date fausse (1) = " . $datefausse . "<br>";
		if (!$datefausse)
		{
			$planning = new planning($dbcon);
			//echo "Date fin = " . $date_fin . "<br>";
			//echo "Date de fin (db) = " . $fonctions->formatdatedb($date_fin) . "<br>";
			//echo "Annee ref + 1 = " . ($fonctions->anneeref()+1) . "<br>";
			//echo "Fin de p�riode = ". $fonctions->finperiode() . "<br>";
			//echo "LIMITE CONGE = " . $fonctions->liredbconstante("LIMITE_CONGE_PERIODE") . "<br>";
			
			// Si la date de fin est sup�rieur � la date de d�but et que l'on accepte que ca d�borde
			// on fait un traitement sp�cial <=> pas de v�rification des autod�clarations
			if ($fonctions->formatdatedb($date_fin) > ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode() 
					    and strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0)
			{
				// Si la date de fin est supp�rieure d'un mois � la date de fin de p�riode ==> On refuse
				// ==> On accepte que de d�border d'un mois
				$datetemp = ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode();
				$timestamp = strtotime($datetemp);
				$datetemp = date("Ymd", strtotime("+1month", $timestamp ));  // On passe au mois suivant
				if ($fonctions->formatdatedb($date_fin) > $datetemp)
				{
					$msg_erreur = $msg_erreur . "La date de fin est trop loin - en dehors de la p�riode (1 mois)  <br>";
					$ignoreabsenceautodecla = FALSE;
				}
				else
					$ignoreabsenceautodecla = TRUE;
			}
			else
				$ignoreabsenceautodecla = FALSE;
			//Echo "Avant le est present .... <br>";
			$present =$planning->agentpresent($agent->harpegeid(),$date_debut , $deb_mataprem, $date_fin, $fin_mataprem,$ignoreabsenceautodecla);
			if (!$present)
				$msg_erreur = $msg_erreur . $agent->prenom() . "  " . $agent->nom() . " n'est pas pr�sent durant la p�riode du $date_debut au $date_fin......!!! <br>";
		}
		
		//echo "Date fausse (2) = " . $datefausse . "<br>";
		if ($msg_erreur <> "" or $datefausse)
		{
			echo "<P style='color: red'>" . $msg_erreur . " </P>";
			//echo "J'ai print le message d'erreur pasautodeclaration = $masquerboutonvalider  <br>";
		}
		elseif (!$datefausse)
		{
			// On recherche les declarations de TP relatives � cette demande
			$affectationliste = $agent->affectationliste($date_debut, $date_fin);
			if (!is_null($affectationliste))
			{
				
				$declarationTPliste = array();
				foreach ($affectationliste as $affectation)
				{
					// On recup�re la premi�re affectation
	//				$affectation = new affectation($dbcon);
	//				$affectation = reset($affectationliste);
					//echo "Datedebut = $date_debut, Date fin = $date_fin <br>";
					$declarationTPliste = array_merge((array)$declarationTPliste,(array)$affectation->declarationTPliste($date_debut, $date_fin));
				}
				//echo "declarationTPliste = "; print_r($declarationTPliste); echo "<br>";
			}
			
			//echo "Je vais sauver la demande <br>";
			unset ($demande);
			$demande = new demande($dbcon);
			//$demande->agent($agent->harpegeid());
			//$demande->structure($agent->structure()->id());
			$demande->type($listetype);
			$demande->datedebut($date_debut);
			$demande->datefin($date_fin);
			$demande->moment_debut($deb_mataprem);
			$demande->moment_fin($fin_mataprem);
			$demande->commentaire($commentaire);
			if ($congeanticipe != "")
				$ignoresoldeinsuffisant = TRUE;
			else
				$ignoresoldeinsuffisant = FALSE;
			//echo "demande->nbredemijrs_demande() AVANT = " . $demande->nbredemijrs_demande() . "<br>";
			$resultat = $demande->store($declarationTPliste,$ignoreabsenceautodecla,$ignoresoldeinsuffisant);
			//echo "demande->nbredemijrs_demande() APRES = " . $demande->nbredemijrs_demande() . "<br>";
			if ($resultat == "")
			{
				echo "<P style='color: green'>Votre demande a �t� enregistr�e... ==> "; 
				if (strcasecmp($typedemande,"conges")==0)
				{
					if (($demande->nbrejrsdemande())>1)
						echo $demande->nbrejrsdemande() ." jours vous seront d�compt�s (" . $demande->typelibelle() .  ").";
					else
						echo $demande->nbrejrsdemande() ." jour vous sera d�compt� (" . $demande->typelibelle() .  ").";
				}
				else
					echo "Vous serez absent durant " . $demande->nbrejrsdemande() . " jour(s).";
				echo "</P>";
			}
			else
				echo "<P style='color: red'>Votre demande n'a pas �t� enregistr�e... ==> MOTIF : ".  $resultat . "</P>";
		}
		
		
?>
	<form name="frm_demande_conge"  method="post" >
	
	<input type="hidden" name="agentid" value="<?php echo $agent->harpegeid(); ?>">
	
	<table>
		<tr>
			<td>Date de d�but de la demande :</td>
			<td width=1px><input class="calendrier" type=text name=date_debut id=date_debut size=10 ></td>
			<td align="left"><input type='radio' name='deb_mataprem' value='m' checked >Matin <input type='radio' name='deb_mataprem' value='a'>Apr�s-midi</td>
		</tr>
		<tr>
			<td>Date de fin de la demande :</td>
			<td width=1px><input class="calendrier" type=text name=date_fin id=date_fin size=10 ></td>
			<td align="left"><input type='radio' name='fin_mataprem' value='m' >Matin <input type='radio' name='fin_mataprem' value='a' checked>Apr�s-midi</td>
		</tr>
		<tr>
			<td>Type de cong� : </td>
			<td colspan="2">

<!-- 
	Date de d�but de la demande :
	<input class="calendrier" type=text name=date_debut id=date_debut size=10 > <br>
	<input type='radio' name='deb_mataprem' value='m' checked >Matin
	<input type='radio' name='deb_mataprem' value='a'>Apr�s-midi
	<br>
	Date de fin de la demande :
	<input class="calendrier" type=text name=date_fin id=date_fin size=10 > <br>
	<input type='radio' name='fin_mataprem' value='m' >Matin
	<input type='radio' name='fin_mataprem' value='a' checked>Apr�s-midi
	<br>
 -->	
<?php
		if (strcasecmp($typedemande,"conges")==0)
		{
			//echo "congesanticipe = " . $congeanticipe . "<br>";
			// C'est une demande par anticipation
			if ($congeanticipe != "")
			{
				$solde = new solde($dbcon);
				$solde->typeabsenceid("ann" . substr(($fonctions->anneeref()+1-$previous), 2));
				echo "<select name='listetype'>";
				echo "<OPTION value='" . $solde->typeabsenceid() .  "'>" . $solde->typelibelle()  . "</OPTION>";
				echo "</select>";
				echo "<input type='hidden' name='typedemande' value='conges' ?>";
			}
			else
			{
				$soldeliste = $agent->soldecongesliste($fonctions->anneeref()-$previous);
				//print_r ($soldeliste); echo "<br>";
				if (!is_null($soldeliste))
				{
			   	echo "<select name='listetype'>";
					foreach ($soldeliste as $keysolde => $solde)
					{
						if ($solde->solde() != 0)
							echo "<OPTION value='" . $solde->typeabsenceid() .  "'>" . $solde->typelibelle()  . "</OPTION>";
					}
					echo "</select>";
				}
				echo "<input type='hidden' name='typedemande' value='conges' ?>";
			}
		}
		else
		{
			echo "<SELECT name='listetype'>";
	   	$listecateg = $fonctions->listecategorieabsence();
			foreach ($listecateg as $keycateg => $nomcateg)
			{
				echo "<optgroup label='" . str_replace("_", " ", $nomcateg) . "'>";
				$listeabs = $fonctions->listeabsence($keycateg);
				foreach ($listeabs as $keyabs => $nomabs)
					echo "<OPTION value='" . $keyabs .  "'>" . $nomabs  . "</OPTION>";
				echo "</optgroup>";
			}
			echo "</SELECT>";
			echo "<br>";
			
			echo "<input type='hidden' name='typedemande' value='absence' ?>";
		}
?>
			</td>
		</tr>
	</table>
<?php 
		echo "<br>";
		if (!is_null($responsable))
		{
			echo "Commentaire (obligatoire) : <br>";
			echo "<input type='hidden' name='responsable' value='" . $responsableid . "'>";
			echo "<textarea rows='4' cols='60' name='commentaire'></textarea> <br>";
			echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
			echo "<br>";
		}
		elseif (strcasecmp($typedemande,"conges")!=0) 
		{
			echo "Commentaire (facultatif) : <br>";
			echo "<textarea rows='4' cols='60' name='commentaire'></textarea> <br>";
			echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() ."'>";
			echo "<br>";
		}
		
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";
		echo "<input type='hidden' name='congeanticipe' value='" . $congeanticipe  . "'>";
		echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
		if (!$masquerboutonvalider)	
			echo "<input type='submit' value='Valider' />";
		echo "<br><br>";
?>
	</form>
	
<?php 
		//echo "Date_debut = $date_debut   date_fin= $date_fin <br>";
		//echo "Debut periode = " . $fonctions->debutperiode() . "<br>";
		//echo "Fin periode = " . $fonctions->finperiode() . "<br>";
		//echo "Annee ref = " . $fonctions->anneeref() . "<br>";
		//echo "debut = " . $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()) . "    fin =" . $fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()) . "<br>";
		if (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0)
		{
			$datetemp = ($fonctions->anneeref()+1-$previous) . $fonctions->finperiode();
			$timestamp = strtotime($datetemp);
			$datetemp = date("Ymd", strtotime("+1month", $timestamp ));  // On passe au mois suivant
			$timestamp = strtotime($datetemp);
			$datetemp = date("Ymd", strtotime("-1days", $timestamp ));  // On passe � la veille
			echo $agent->planninghtml($fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode()),$datetemp,TRUE);
		}
		else
			echo $agent->planninghtml($fonctions->formatdate(($fonctions->anneeref()-$previous) . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1-$previous) . $fonctions->finperiode()), TRUE);
		
		echo $agent->soldecongeshtml($fonctions->anneeref()-$previous);
		echo $agent->affichecommentairecongehtml();
		echo "<br>";
	}	
?>

<!-- 
<a href=".">Retour � la page d'accueil</a>
--> 
</body></html>

