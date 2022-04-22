<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.8" />

<?php    
    $WSGROUPURL = $fonctions->liredbconstante("WSGROUPURL");
    // echo "<br><br>WSGROUPURL = $WSGROUPURL <br>";
?>

<link rel="stylesheet"
	href="<?php echo "$WSGROUPURL"?>/web-widget/jquery-ui.css"
	type="text/css" media="all"></link>
<link rel="stylesheet"
	href="<?php echo "$WSGROUPURL"?>/web-widget/ui.theme.css"
	type="text/css" media="all"></link>
<link rel="stylesheet"
	href="<?php echo "$WSGROUPURL"?>/web-widget/autocompleteUser.css"
	type="text/css" media="all"></link>
<script type="text/javascript">
<?php
    if (is_null($user) or is_null($user->agentid())) {
        echo "PROBLEME : L'utilisateur n'est pas renseigné ==> objet \$user!!!! <br>";
        exit();
    }

?>

	function montre(id)
	{
		var d = document.getElementById(id);
		for (var i = 1; i<=10; i++)
		{
			if (document.getElementById('smenuprincipal'+i))
			{
				document.getElementById('smenuprincipal'+i).style.display='none';
			}
		}
		if (d)
		{
			d.style.display='block';
		}
	}

	function cache(id, e)
	{
		var toEl;
		var d = document.getElementById(id);
		if (window.event)
			toEl = window.event.toElement;
		else if (e.relatedTarget)
			toEl = e.relatedTarget;
		if ( d != toEl && !estcontenupar(toEl, d) )
			d.style.display="none";
	}

// retourne true si oNode est contenu par oCont (conteneur)
	function estcontenupar(oNode, oCont)
	{
		if (!oNode)
			return; // ignore les alt-tab lors du hovering (empêche les erreurs)
		while ( oNode.parentNode )
		{
			oNode = oNode.parentNode;
			if ( oNode == oCont )
				return true;
		}
		return false;
	}

/* Demande d'affichage d'une fenetre au niveau du front office */
	function ouvrirFenetrePlan(url, nom) 
	{
   	window.open(url, nom, "width=520,height=500,scrollbars=yes, status=yes");
	}

</script>


<script type="text/javascript">window.bandeau_ENT={current:'g2t'};</script>
<script type="text/javascript"
	src="https://esup-data.univ-paris1.fr/esup/outils/postMessage-resize-iframe-in-parent.js"></script>
<script src="javascripts/jquery-1.8.3.js"></script>
<script src="javascripts/jquery-ui.js"></script>

<link
	href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css"
	rel="stylesheet" />
<script
	src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

<script>$(document).ready(function() {
    $('#structureid').select2();
    $('#esignatureid').select2();
});</script>
<!--   
	<script>
		$(function()
		{
			$( ".calendrier" ).datepicker({minDate: $( ".calendrier" ).attr("minperiode"), maxDate: $( ".calendrier" ).attr("maxperiode")});
		});
	</script>
 -->

<!--  
   <script src="<?php echo "$WSGROUPURL"?>/web-widget/jquery-1.7.2.min.js"></script>
   <script src="<?php echo "$WSGROUPURL"?>/web-widget/jquery-ui-1.8.21.custom.min.js"></script>
 -->
<script src="<?php echo "$WSGROUPURL"?>/web-widget/autocompleteUser.js"></script>


<script>
    var completionAgent = function (event, ui)
    {
		// NB: this event is called before the selected value is set in the "input"
		var form = $(this).closest("form");
		var selectedInput = document.activeElement;
		form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
		form.find("[class='" + selectedInput.name + "']").val (ui.item.value);


		return false;
    };

<?php 
    $planningelement = new planningelement($dbcon);
    $planningelement->type('teletrav');
    $couleur = $planningelement->couleur();
    //echo "couleur = $couleur <br>";
?>

    var hide_teletravail = function (nomtableau, id_hidden_input ="")
    {
		//alert ('Plouf !');
	    var tableau = document.getElementById(nomtableau);
	    //alert (tableau.id);
    	var checkboxvalue = document.activeElement.checked;
		for (var indexcellule = 0; indexcellule < tableau.querySelectorAll('.teletravail').length; indexcellule++)
		{
            //alert(indexcellule);
            var currenttd = tableau.querySelectorAll('.teletravail')[indexcellule];
            if (checkboxvalue || currenttd.classList.contains('exclusion'))  // Soit on a demander à le masquer, soit c'est une date exclue (<=> classe exclusion)
            {
                //alert('Suppression de la couleur');
                // C'est du télétravail et on doit le masquer ou la date est exclue
                currenttd.bgColor = '<?php echo planningelement::COULEUR_VIDE ?>';
                // On ajoute la classe hidde_tip afin de masquer la bulle d'information => voir CSS
                currenttd.classList.add('hidde_tip');
            }
            else
            {
                //alert('On remet la couleur');
                // C'est du télétravail et on doit le montrer
                currenttd.bgColor = '<?php echo "$couleur"  ?>';
                // On supprime la classe hidde_tip afin d'autoriser l'affichage de la bulle d'information => voir CSS
                currenttd.classList.remove('hidde_tip');
            }    
        }
        if (id_hidden_input != "")
        {
        	var hidden_input = document.getElementById(id_hidden_input);
        	if (checkboxvalue)
        	{
        		hidden_input.value='on';
       		}
       		else
       		{
       			hidden_input.value='off';
       		}
        }
        if (checkboxvalue)
        {
			tableau.classList.add('teletravail_hidden');
        }
        else
        {
        	tableau.classList.remove('teletravail_hidden');
        }
	};


</script>

<!-- On rend la CSS "dynamique" en lui passant en paramètre le timestamp Unix de dernière modification du fichier -->
<!-- Donc à chaque changement de CSS, on force le chargement de la nouvelle CSS -->
<link rel="stylesheet" type="text/css"
	href="style/style.css?<?php echo filemtime('style/style.css')  ?>"
	media="screen"></link>
<link rel="stylesheet" type="text/css" href="style/jquery-ui.css?<?php echo filemtime('style/jquery-ui.css')  ?>"
	media="screen"></link>
</head>

<body class="bodyhtml"> 

<?php
    // On vérifie que la personne connectée (la vraie personne avec le compte LDAP) est administrateur de l'appli
    // On n'utilise pas la variable $user car dans le cas de la subtitution (se faire passer pour...) on ne serait plus admin
    $adminuser = new agent($dbcon);
    if (! isset($_SESSION['phpCAS']['agentid'])) {
        $uid = phpCAS::getUser();
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
        $filtre = "($LDAP_UID_AGENT_ATTR=$uid)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array(
            "$LDAP_CODE_AGENT_ATTR"
        );
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        $_SESSION['phpCAS']['agentid'] = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
        // echo "Je viens de set le param - menu.php<br>";
    }
    $adminuser->load($_SESSION['phpCAS']['agentid']);
    // echo "Le numéro Agent de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";

    $arraystructpartielle = array();
    // $arraystructpartielle = array_merge($arraystructpartielle,array('SC4_3','IU1_3','IU4_3','IU21_4','IU22_4','IU24_4','IU25_4','IU23_4','IU2C_4','IU2D_4'));
    
    // **********************************************************************************
    // Pas de mise en production de cette fonctionnalité pour le moment
    //$arraystructpartielle = array_merge($arraystructpartielle,array('IU3_3','UR028_4','UR02C_4','UR031_4','UR035_4','UR038_4','UR032_4','UR048_4','UR083_4','UR084_4','UR03C_4','UR082_4','UR097_4','UR099_4','UR094_4','UR09A_4','UR09E_4','UR09B_4','UR09C_4','UR09G_4','UR098_4','UR093_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('UR102_4','UR104_4','UR109_4','UR101_4','UR115_4','UR152_4','UR211_4','UR272_4','UR274_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('EDO04_4','EDO08_4','EDO06_4','EDO02_4','EDO09_4','EDO05_4','EDO10_4','EDO03_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('UF04SI_4','UF04_3','UF09_3','UF109_4','UF10_3','UF11T_4','UF11_3','UF21_3','UF27_3','UF27T_4'));
    // **********************************************************************************
    
    // $arraystructpartielle = array_merge($arraystructpartielle,array('DGHA_4'));
    $arraystructpartielle = array_map('strtoupper', $arraystructpartielle);
    
    $affectationarray = $adminuser->affectationliste(date("d/m/Y"), date("d/m/Y"));
    $hidemenu = '';
    $structurepartielle = false;
    if (is_array($affectationarray))
    { // S'il y a une affectation
        $affectation = current($affectationarray);
        
        //echo "Code structure = " . $affectation->structureid() . "    Liste structure : " . print_r($arraystructpartielle,true) . "<br><br>";
        
        if (in_array(strtoupper($affectation->structureid()), $arraystructpartielle))
        {
            $structurepartielle = true;
            $hidemenu = ' style="display:none;" ';
        }
    } 
    

    // On verifie que la personne est dans le groupe G2T du LDAP
    $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
    $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
    $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
    $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
    $LDAP_MEMBER_ATTR = $fonctions->liredbconstante("LDAPMEMBERATTR");
    $LDAP_GROUP_NAME = $fonctions->liredbconstante("LDAPGROUPNAME");
    // Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
    // Si l'utilisateur est dans une strucuture a accès partiel ==> On ne vérifie pas s'il est membre du groupe LDAP
    if ((trim("$LDAP_MEMBER_ATTR") != "" and trim("$LDAP_GROUP_NAME") != "") and ($structurepartielle == false)) {
        $uid = phpCAS::getUser();
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
        $filtre = "($LDAP_UID_AGENT_ATTR=$uid)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array(
            "$LDAP_MEMBER_ATTR"
        );
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        
        // echo "<br>Info = " . print_r($info,true) . "<br>";
        // Si l'utilisateur est au moins dans un groupe
        if (isset($info[0][$restriction[0]])) {
            if (in_array("$LDAP_GROUP_NAME", $info[0][$restriction[0]])) {
                // L'utilisateur est dans le groupe recherché, on peut continuer
                // echo "Yes !! Il est dedans !!! <br>";
            } else {
                $errlog = "Le groupe $LDAP_GROUP_NAME n'est pas défini pour l'utilisateur " . $adminuser->identitecomplete() . " (identifiant = " . $adminuser->agentid() . ") !!!";
                echo "$errlog<br>";
                echo "<br><font color=#FF0000>Vous n'êtes pas autorisé à vous connecter à cette application...</font>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                exit();
            }
        }    // Pas de groupe pour cet utilisateur => On doit s'arréter
        else {
            $errlog = "L'utilisateur " . $adminuser->identitecomplete() . " (identifiant = " . $adminuser->agentid() . ") ne fait parti d'aucun groupe LDAP....";
            echo "$errlog <br>";
            echo "<br><font color=#FF0000>Vous n'êtes pas autorisé à vous connecter à cette application...</font>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            exit();
        }
    }
    // Si on est en mode "MAINTENANCE"
    if (strcasecmp($fonctions->liredbconstante('MAINTENANCE'), 'n') != 0) {
        if ($adminuser->estadministrateur()) // Si un administrateur est connecté
        {
            echo "<P><CENTER><FONT SIZE='5pt' COLOR='#FF0000'><B><U>ATTENTION : LE MODE MAINTENANCE EST ACTIVE -- APPLICATION EN MAINTENANCE !!!</B></U></FONT></CENTER></P><BR>";
        } else // C'est un utilisateur simple => Affichage de la page de maintenance
        {
            echo "<img width=144 height=79 src='https://ent-data.univ-paris1.fr/esup/canal/maintenance/maintenance.gif' align=left hspace=12>";
            echo "L'application de gestion des congés est en maintenance, elle sera bientôt à nouveau en ligne.<br>Veuillez nous excuser pour la gêne occasionnée.";
            echo "</body></html>";
            exit();
        }
    }
    
    if ($user->agentid() != $_SESSION['phpCAS']['agentid'])
        echo "<P><CENTER><FONT SIZE='5pt' COLOR='#FF0000'><B><U>ATTENTION : VOUS VOUS ETES SUBSTITUE A UNE AUTRE PERSONNE !!!</B></U></FONT><BR>" . $user->identitecomplete() . " (Agent Id = " . $user->agentid() . ")</CENTER></P><BR>";
            
    if ($structurepartielle == true)
    {
        //echo "<P><CENTER><FONT SIZE='5pt' COLOR='#FF0000'><B><U>ATTENTION : Vous avez un accès partiel à l'application G2T !!!</B></U></FONT><BR></CENTER></P><BR>";
    }
    
//     $affectationarray = $user->affectationliste(date("d/m/Y"), date("d/m/Y"));
//     if (is_array($affectationarray)) 
//     { // S'il y a une affectation
//         $affectation = current($affectationarray);    
        
//         //echo "Code structure = " . $affectation->structureid() . "    Liste structure : " . print_r($arraystructpartielle,true) . "<br><br>";
        
//         if (in_array(strtoupper($affectation->structureid()), $arraystructpartielle))
//         {
//             $hidemenu = ' style="display:none;" ';
//         }
//     }
    
    unset($arraystructpartielle);
    unset($affectationarray);
    unset($affectation);

/*   
    // La déclaration du tableau est déplacée dans le all_g2t_classes.php pour être pris en compte également dans les fichiers CRON
    // On va charger le tableau des couleurs de chaque élément du planning => Optimisation du tps
    // Voir la classe planningelement->couleur()
    $tabcouleurelement = $fonctions->typeabsencelistecomplete();
    define('TABCOULEURPLANNINGELEMENT', $tabcouleurelement);
    //var_dump(TABCOULEURPLANNINGELEMENT);
*/    
?>


<div id="mainmenu">
		<ul class="niveau1">
			<li onclick="">MENU AGENT
				<ul class="niveau2">
					<li onclick='document.accueil.submit();'>
						<form name='accueil' method='post' action="index.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.accueil.submit();">Accueil</a>
					</li>
					<li onclick='document.planning.submit();' <?php echo $hidemenu; ?> >
						<form name='planning' method='post' action="affiche_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form>
						<a href="javascript:document.planning.submit();">Planning</a>
					</li>
					<li onclick='document.agentannulation.submit();'>
						<form name='agentannulation' method='post' action="gestion_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>">
						</form>
						<a href="javascript:document.agentannulation.submit();">Annulation de demandes</a>
					</li>				
<?php
    $affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));
    $structure = new structure($dbcon);
    if (is_array($affectationliste)) {
        $affectation = reset($affectationliste);
        $structureid = $affectation->structureid();
        if ($structure->load($structureid) == false)
            $structure->affichetoutagent("n"); // Si impossible de charger la structure => On force la valeur à 'n'
    } else {
        $structure->affichetoutagent("n");
    }
    if (strcasecmp($structure->affichetoutagent(), "o") == 0) 
    // if ($user->structure()->affichetoutagent() == "o")
    {
?>
				    <li onclick='document.agent_struct_planning.submit();' <?php echo $hidemenu; ?> >
						<form name='agent_struct_planning' method='post' action="structure_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
							<input type="hidden" name="mode" value="agent">
							<input type="hidden" name="previous" value="no">
						</form>
						<a href="javascript:document.agent_struct_planning.submit();">Planning de la structure</a>
					</li>
<?php
    }
?>	
				   <li onclick='document.dem_conge.submit();' <?php echo $hidemenu; ?> >
						<form name='dem_conge' method='post' action="etablir_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="typedemande" value="conges">
						</form> 
						<a href="javascript:document.dem_conge.submit();">Saisir une demande de congé</a>
					</li>
					<li onclick='document.dem_absence.submit();'>
						<form name='dem_absence' method='post' action="etablir_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="typedemande" value="absence">
						</form>
						<a href="javascript:document.dem_absence.submit();">Saisir une demande d'absence ou de télétravail</a>
					</li>
					<li onclick='document.agent_tpspartiel.submit();'>
						<form name='agent_tpspartiel' method='post' action="saisir_tpspartiel.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="agent">
						</form>
						<a href="javascript:document.agent_tpspartiel.submit();">Gestion des temps partiels</a>
					</li>
<?php
    // Si la date limite d'utilisation des reliquats est dépassée on n'affiche pas l'alimentation et le droit d'option sur CET
	$sqldatereliq = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'FIN_REPORT'";
	$queryreliq = mysqli_query($dbcon, $sqldatereliq);
	$erreur = mysqli_error($dbcon);
	if ($erreur != "")
	{
		$errlog = "Problème SQL dans le chargement de la date limite d'utilisation du reliquat : " . $erreur;
		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
	}
	elseif ($res = mysqli_fetch_row($queryreliq))
	{
		$datereliq = ($fonctions->anneeref()+1).$res[0];
	    if (date("Ymd") <= $datereliq) {
?>  
<!--					
                    <li onclick='document.alim_cet_test.submit();'>
						<form name='alim_cet_test' method='post' action="gerer_alimentationCET_test.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
						</form>
						<a href="javascript:document.alim_cet_test.submit();">Alimentation du CET TEST</a>
					</li>
-->
					<li onclick='document.alim_cet.submit();'>
						<form name='alim_cet' method='post' action="gerer_alimentationCET.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
						</form>
						<a href="javascript:document.alim_cet.submit();">Alimentation du CET</a>
					</li>
					<li onclick='document.option_cet.submit();'>
						<form name='option_cet' method='post' action="gerer_optionCET.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
						</form>
						<a href="javascript:document.option_cet.submit();">Droit d'option sur CET</a>
					</li>
<?php
	    }
    }
?>
					<li onclick='document.agent_aide.submit();'>
						<form name='agent_aide' method='get' TARGET=_BLANK action="https://ent.univ-paris1.fr/assets/aide/canal/g2t.html">
						</form> 
						<a href="javascript:document.agent_aide.submit();">Manuel utilisateur</a>
					</li>
				</ul>
			</li>
		</ul>
<?php
    if ($user->estresponsable()) {
?> 
		<ul class="niveau1">
			<li onclick="">MENU RESPONSABLE
				<ul class="niveau2">
					<li onclick='document.resp_parametre.submit();'>
						<form name='resp_parametre' method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="action" value="modif"> 
							<input type="hidden" name="mode" value="resp">
						</form> 
						<a href="javascript:document.resp_parametre.submit();">Paramétrage des dossiers et des structures</a>
					</li>	
					<li class="plus"><a>Gestion de l'année en cours</a>
						<ul class="niveau3">
							<li onclick='document.resp_gest_conge.submit();'>
								<form name='resp_gest_conge' method='post' action="gestion_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>">
									<input type="hidden" name="previous" value="no">
								</form> 
								<a href="javascript:document.resp_gest_conge.submit();">Annulation de congé ou d'absence</a>
							</li>
							<li onclick='document.resp_conge.submit();'>
								<form name='resp_conge' method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="typedemande" value="conges">
									<input type="hidden" name="previous" value="no">
								</form> <a href="javascript:document.resp_conge.submit();">Saisir une demande de congé pour un agent</a>
							</li>
							<li onclick='document.resp_absence.submit();'>
								<form name='resp_absence' method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="typedemande" value="absence"> 
									<input type="hidden" name="previous" value="no">
								</form> <a href="javascript:document.resp_absence.submit();">Saisir une demande d'absence ou de télétravail pour un agent</a>
							</li>
							<li onclick='document.resp_valid_conge.submit();'>
								<form name='resp_valid_conge' method='post' action="valider_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp"> 
									<input type="hidden" name="previous" value="no">
								</form> <a href="javascript:document.resp_valid_conge.submit();">Demandes en attente</a>
							</li>
							<li onclick='document.resp_valid_tpspartiel.submit();'>
								<form name='resp_valid_tpspartiel' method='post' action="valider_tpspartiel.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp">
								</form>
								<a href="javascript:document.resp_valid_tpspartiel.submit();">Validation des temps partiels</a>
							</li>
							<li onclick='document.resp_tpspartiel.submit();'>
								<form name='resp_tpspartiel' method='post' action="saisir_tpspartiel.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp">
								</form> <a href="javascript:document.resp_tpspartiel.submit();">Saisir le temps partiel pour un agent</a>
							</li>
<!-- 
							<li onclick='document.resp_gestcet.submit();'>
								<form name='resp_gestcet'  method='post' action="gerer_cet.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
									<input type="hidden" name="mode" value="resp">
								</form>
								<a href="javascript:document.resp_gestcet.submit();">Gestion du CET d'un agent</a>
							</li>
-->
							<li onclick='document.resp_ajout_conge.submit();'>
								<form name='resp_ajout_conge' method='post' action="ajouter_conges.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
								</form> 
								<a href="javascript:document.resp_ajout_conge.submit();">Ajout de jours supplémentaires pour un agent</a>
							</li>
							<li onclick='document.resp_aff_solde.submit();'>
								<form name='resp_aff_solde' method='post' action="affiche_solde.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp">
									<input type="hidden" name="previous" value="no">
								</form> 
								<a href="javascript:document.resp_aff_solde.submit();">Affichage du solde des agents de la structure</a>
							</li>
							<li onclick='document.resp_struct_planning.submit();'>
								<form name='resp_struct_planning' method='post' action="structure_planning.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp"> <input type="hidden" name="previous" value="no">
								</form> 
								<a href="javascript:document.resp_struct_planning.submit();">Planning de la structure</a>
							</li>
<?php
    // Si on est 6 mois avant la fin de la période ==> On peut saisir des jours par anticipation
    $datetemp = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
    $timestamp = strtotime($datetemp);
    $datetemp = date("Ymd", strtotime("-6month", $timestamp)); // On remonte de 6 mois
                                                                // echo "TimeStamp = " . $datetemp . "<br>";
    if (date("Ymd") > $datetemp) {
?>				
							<li onclick='document.resp_conge_anticipe.submit();'>
								<form name='resp_conge_anticipe' method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="typedemande" value="conges"> 
									<input type="hidden" name="congeanticipe" value="yes">
								</form> 
								<a href="javascript:document.resp_conge_anticipe.submit();">Saisir une demande de congé par anticipation pour un agent</a>
							</li>
<?php
    }
?>				
							
						</ul>
					</li>
					<li class="plus"><a>Gestion de l'année précédente</a>
						<ul class="niveau3">
							<li onclick='document.resp_gest_conge_previous.submit();'>
								<form name='resp_gest_conge_previous' method='post' action="gestion_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_gest_conge_previous.submit();">Annulation de congé ou d'absence</a>
							</li>
							<li onclick='document.resp_conge_previous.submit();'>
								<form name='resp_conge_previous' method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="typedemande" value="conges"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_conge_previous.submit();">Saisir une demande de congé pour un agent</a>
							</li>
							<li onclick='document.resp_absence_previous.submit();'>
								<form name='resp_absence_previous' method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="typedemande" value="absence"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_absence_previous.submit();">Saisir une demande d'absence ou de télétravail pour un agent</a>
							</li>
							<li onclick='document.resp_valid_conge_previous.submit();'>
								<form name='resp_valid_conge_previous' method='post' action="valider_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_valid_conge_previous.submit();">Demandes en attente</a>
							</li>
							<li onclick='document.resp_aff_solde_previous.submit();'>
								<form name='resp_aff_solde_previous' method='post' action="affiche_solde.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_aff_solde_previous.submit();">Affichage du solde des agents de la structure</a>
							</li>
							<li onclick='document.resp_struct_planning_previous.submit();'>
								<form name='resp_struct_planning_previous' method='post' action="structure_planning.php">
									<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
									<input type="hidden" name="mode" value="resp"> 
									<input type="hidden" name="previous" value="yes">
								</form> 
								<a href="javascript:document.resp_struct_planning_previous.submit();">Planning de la structure</a>
							</li>
						</ul>
					</li>
<?php
    // Un agent responsable (sens strict) peut modifier le paramétrage de la structure
    // if ($user->estresponsable(false))
    // {
?> 
<?php
    // }
?>
				</ul>
			</li>
		</ul> 
<?php
    }
    if ($user->estgestionnaire()) {
?>
		<ul class="niveau1">
			<li onclick="">MENU GESTIONNAIRE
				<ul class="niveau2">
					<li onclick='document.gest_valid_conge.submit();'>
						<form name='gest_valid_conge' method='post' action="valider_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion">
						</form> 
						<a href="javascript:document.gest_valid_conge.submit();">Demandes en attente</a>
					</li>
					<li onclick='document.gest_valid_conge_prev.submit();'>
						<form name='gest_valid_conge_prev' method='post' action="valider_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion"> 
							<input type="hidden" name="previous" value="yes">
						</form> 
						<a href="javascript:document.gest_valid_conge_prev.submit();">Demandes en attente (Année N-1)</a>
					</li>
					<li onclick='document.gest_gest_conge.submit();'>
						<form name='gest_gest_conge' method='post' action="gestion_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="gestionnaireid" value="<?php echo $user->agentid(); ?>">
							<input type="hidden" name="previous" value="no">
						</form> 
						<a href="javascript:document.gest_gest_conge.submit();">Annulation de congé ou d'absence</a>
					</li>
					<li onclick='document.gest_aff_solde.submit();'>
						<form name='gest_aff_solde' method='post' action="affiche_solde.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_aff_solde.submit();">Affichage du solde des agents de la structure</a>
					</li>
					<li onclick='document.gest_aff_solde_ant.submit();'>
						<form name='gest_aff_solde_ant' method='post' action="affiche_solde.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion"> 
							<input type="hidden" name="previous" value="yes">
						</form> 
						<a href="javascript:document.gest_aff_solde_ant.submit();">Affichage du solde des agents de la structure (année précéd.)</a>
					</li>
					<li onclick='document.gest_parametre.submit();'>
						<form name='gest_parametre' method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="action" value="lecture"> 
							<input type="hidden" name="mode" value="gestion">
						</form> <a href="javascript:document.gest_parametre.submit();">Affichage paramétrage des dossiers</a>
					</li>
					<li onclick='document.gest_struct_planning.submit();'>
						<form name='gest_struct_planning' method='post' action="structure_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion"> 
							<input type="hidden" name="previous" value="no">
						</form> 
						<a href="javascript:document.gest_struct_planning.submit();">Planning de la structure</a>
					</li>
					<li onclick='document.gest_struct_planning_previous.submit();'>
						<form name='gest_struct_planning_previous' method='post' action="structure_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion"> 
							<input type="hidden" name="previous" value="yes">
						</form> 
						<a href="javascript:document.gest_struct_planning_previous.submit();">Planning de la structure (année précéd.)</a>
					</li>
					<li onclick='document.gest_parametre_modif.submit();'>
						<form name='gest_parametre_modif' method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="action" value="modif"> 
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_parametre_modif.submit();">Paramétrage des dossiers et des structures</a>
					</li>
					<li onclick='document.gest_valid_tpspartiel.submit();'>
						<form name='gest_valid_tpspartiel' method='post' action="valider_tpspartiel.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
							<input type="hidden" name="mode" value="gestion">
						</form> 
						<a href="javascript:document.gest_valid_tpspartiel.submit();">Validation des temps partiels</a>
					</li>
<!-- 
					<li onclick='document.gest_gestcet.submit();'>
						<form name='gest_gestcet'  method='post' action="gerer_cet.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
							<input type="hidden" name="mode" value="gest">
						</form>
						<a href="javascript:document.gest_gestcet.submit();">Gestion du CET d'un agent</a>
					</li>
-->
				</ul>
			</li>
		</ul>
<?php
    }
    if ($user->estprofilrh()) {
?>
		<ul class="niveau1">
			<li onclick="">MENU GESTION RH
				<ul class="niveau2"> 
<?php
        if ($user->estprofilrh('1')) // PROFIL RH = 1 ==> GESTIONNAIRE RH DE CET
        {
?>
					<li onclick='document.rh_gest_periode.submit();'>
						<form name='rh_gest_periode' method='post' action="gestion_periodes.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
						</form>
						<a href="javascript:document.rh_gest_periode.submit();">Gestion des périodes de fermeture</a>
					</li>
					<li onclick='document.rh_gest_deleg.submit();'>
                        <form name='rh_gest_deleg' method='post' action="gestion_delegation.php">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                        </form>
                        <a href="javascript:document.rh_gest_deleg.submit();">Gestion des délégations sur les structures</a>
                    </li>
					<li onclick='document.rh_gest_teletravail.submit();'>
						<form name='rh_gest_teletravail' method='post' action="gestion_teletravail.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
						</form>
						<a href="javascript:document.rh_gest_teletravail.submit();">Gestion des conventions de télétravail</a>
					</li>
					<li onclick='document.rh_affiche_info_teletravail.submit();'>
						<form name='rh_affiche_info_teletravail' method='post' action="affiche_info_teletravail.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.rh_affiche_info_teletravail.submit();">Nombre de jours de télétravail</a>
					</li>
					<li class="plus"><a>Gestion des CET et paramétrage</a>
						<ul class="niveau3">
        					<li onclick='document.gestrh_utilisationcet.submit();'>
        						<form name='gestrh_utilisationcet' method='post' action="utilisation_cet.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="mode" value="gestrh">
        						</form> 
        						<a href="javascript:document.gestrh_utilisationcet.submit();">Validation des congés sur CET</a>
        					</li>
        					<li onclick='document.gestrh_gestcet.submit();'>
        						<form name='gestrh_gestcet' method='post' action="gerer_cet.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="mode" value="gestrh">
        						</form> 
        						<a href="javascript:document.gestrh_gestcet.submit();">Gestion d'un CET</a>
        					</li>
        					<li onclick='document.gestrh_gestcet_hors_esignature.submit();'>
        						<form name='gestrh_gestcet_hors_esignature' method='post' action="gerer_cet_hors_esignature.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="mode" value="gestrh">
        						</form> 
        						<a href="javascript:document.gestrh_gestcet_hors_esignature.submit();">Gestion d'un CET (hors eSignature)</a>
        					</li>
        					<li onclick='document.gestrh_creercet.submit();'>
        						<form name='gestrh_creercet' method='post' action="creer_cet.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="mode" value="gestrh">
        						</form> 
        						<a href="javascript:document.gestrh_creercet.submit();">Reprise d'un CET existant</a>
        					</li>
        					<li onclick='document.rh_param_cet.submit();'>
        						<form name='rh_param_cet' method='post' action="param_cet.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        						</form>
        						<a href="javascript:document.rh_param_cet.submit();">Paramétrage des campagnes CET</a>
        					</li>
<!--                     
        					<li onclick='document.rh_alimentation_cet_test.submit();'>
                                <form name='rh_alimentation_cet_test' method='post' action="gerer_alimentationCET_test.php">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="rh">
                                </form>
                                <a href="javascript:document.rh_alimentation_cet_test.submit();">Alimentation du CET TEST</a>
                            </li>   
-->                 
        					<li onclick='document.rh_alimentation_cet.submit();'>
                                <form name='rh_alimentation_cet' method='post' action="gerer_alimentationCET.php">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="rh">
                                </form>
                                <a href="javascript:document.rh_alimentation_cet.submit();">Alimentation du CET</a>
                            </li>          
        					<li onclick='document.rh_option_cet.submit();'>
                                <form name='rh_option_cet' method='post' action="gerer_optionCET.php">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="rh">
                                </form>
                                <a href="javascript:document.rh_option_cet.submit();">Droit d'option sur CET</a>
                            </li>
        				</ul>
    				</li>
					<li class="plus"><a>Gestion des congés</a>
						<ul class="niveau3">
        					<li onclick='document.rh_conge.submit();'>
        						<form name='rh_conge' method='post' action="etablir_demande.php">
        							<input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="typedemande" value="conges"> 
        							<input type="hidden" name="previous" value="no">
        							<input type="hidden" name="rh_mode" value="yes">
        						</form> 
        						<a href="javascript:document.rh_conge.submit();">Demande de congés imputés sur le CET</a>
        					</li>
        					<li onclick='document.rh_gest_conge.submit();'>
        						<form name='rh_gest_conge' method='post' action="gestion_demande.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
        							<input type="hidden" name="mode" value="rh"> 
        							<input type="hidden" name="previous" value="no">
        						</form> 
        						<a href="javascript:document.rh_gest_conge.submit();">Annulation de congés imputés sur le CET</a>
        					</li>
        					<li onclick='document.affiche_info_agent.submit();'>
        						<form name='affiche_info_agent' method='post' action="affiche_info_agent.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
        						</form> 
        						<a href="javascript:document.affiche_info_agent.submit();">Consultation des congés d'un agent</a>
        					</li>
        					<li onclick='document.modif_solde.submit();'>
        						<form name='modif_solde' method='post' action="modif_solde.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
        						</form> 
        						<a href="javascript:document.modif_solde.submit();">Modification du solde de congés d'un agent</a>
        					</li>
        					<li onclick='document.rh_aff_solde.submit();'>
        						<form name='rh_aff_solde' method='post' action="affiche_solde.php">
        							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
        							<input type="hidden" name="mode" value="rh"> 
        							<input type="hidden" name="previous" value="no">
        						</form>
        						<a href="javascript:document.rh_aff_solde.submit();">Affichage du solde des agents d'une structure</a>
        					</li>
    					</ul>
    				</li>
					
<?php
        }
?>
				</ul>
			</li>
		</ul>
<?php
    }
    if ($adminuser->estadministrateur()) {
?>
		<ul class="niveau1">
			<li onclick="">MENU ADMINISTRATEUR
				<ul class="niveau2">
					<li onclick='document.admin_mode_maintenance.submit();'>
						<form name='admin_mode_maintenance' method='post' action="admin_maintenance.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_mode_maintenance.submit();">Activer/désactiver maintenance</a>
					</li>
					<li onclick='document.admin_struct_gest.submit();'>
						<form name='admin_struct_gest' method='post' action="gestion_structure.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_struct_gest.submit();">Paramétrage des structures</a>
					</li>
<!-- 
					<li onclick='document.admin_info_agent.submit();'>
						<form name='admin_info_agent'  method='post' action="affiche_info_agent.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form>
						<a href="javascript:document.admin_info_agent.submit();">Affichage informations agent</a>
					</li>
-->
					<li onclick='document.admin_subst_agent.submit();'>
						<form name='admin_subst_agent' method='post' action="admin_substitution.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_subst_agent.submit();">Se faire passer pour un autre agent</a>
					</li>
					<li onclick='document.admin_import_conges.submit();'>
						<form name='admin_import_conges' method='post' action="import_conges.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_import_conges.submit();">Importer des congés</a>
					</li>
					<li onclick='document.admin_solde_conges.submit();'>
						<form name='admin_solde_conges' method='post' action="affiche_info_conges.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_solde_conges.submit();">Synthèse des congés</a>
					</li>
					<li onclick='document.admin_affiche_demandeCET.submit();'>
						<form name='admin_affiche_demandeCET' method='post' action="affiche_demandeCET.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_affiche_demandeCET.submit();">Afficher une demande sur CET/eSignature</a>
					</li>
					<li onclick='document.admin_affiche_info_teletravail.submit();'>
						<form name='admin_affiche_info_teletravail' method='post' action="affiche_info_teletravail.php">
							<input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
						</form> 
						<a href="javascript:document.admin_affiche_info_teletravail.submit();">Nombre de jours de télétravail</a>
					</li>
				</ul>
			</li>
		</ul>  
<?php
	}
?> 

</div>
<br> <br> <br>