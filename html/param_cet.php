<?php

    require_once ('CAS.php');
    include './includes/casconnection.php';

    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
    
    require_once ("./includes/all_g2t_classes.php");
/*
    require_once ("./class/agent.php");    
    require_once ("./class/structure.php");
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
    require_once ("./class/periodeobligatoire.php");
*/
    
    $user = new agent($dbcon);
    $user->load($userid);
    require ("./includes/menu.php");
    
    // PARAMETRAGE DU CALENDRIER D'ALIMENTATION
    
    if (isset($_POST['valider_cal_alim']))
    {
    	$datedebutalim = $fonctions->formatdatedb($_POST['date_debut_alim']);
    	$datefinalim = $fonctions->formatdatedb($_POST['date_fin_alim']);
    	if ($datefinalim < $datedebutalim)
    	{
    		echo "Incohérence dates (date début > date fin). <br>";
    	}
    	else
    	{
    		$fonctions->debutalimcet($datedebutalim);
    		$fonctions->finalimcet($datefinalim);
    	}
    }
    
    // PARAMETRAGE DU CALENDRIER DE DROIT D'OPTION
    
    if (isset($_POST['valider_cal_option']))
    {
    	$datedebutopt = $fonctions->formatdatedb($_POST['date_debut_option']);
    	$datefinopt = $fonctions->formatdatedb($_POST['date_fin_option']);
    	if ($datefinopt < $datedebutopt)
    	{
    		echo "Incohérence dates (date début > date fin). <br>";
    	}
    	else
    	{
    		$fonctions->debutoptioncet($datedebutopt);
    		$fonctions->finoptioncet($datefinopt);
    	}
    }
    if (isset($_POST['valider_param_plafond']))
    {
    	$plafondcet = $_POST['plafondcet'];
    	if (!is_numeric($plafondcet) || !is_int($plafondcet+0) || $plafondcet < 0)
    		echo "Le nombre de jours maximum doit être un entier positif. <br>";
    	else 
    	{
    		$update = "UPDATE CONSTANTES SET VALEUR = $plafondcet WHERE NOM = 'PLAFONDCET'";
    		$query = mysqli_query($dbcon, $update);
    	}
    }
    $plafondparam = $fonctions->liredbconstante('PLAFONDCET');
    ?>

    <form name="frm_calendrier_alim" method="post">
    
        <input type='hidden' name='userid' value='<?php echo $user->harpegeid();?>'>
        		<br>Paramétrage du calendrier de la campagne d'alimentation du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutalimcet()).' - '.$fonctions->formatdate($fonctions->finalimcet());?>)<br>
        		<table>
        		<tr>
        		<td>Date d'ouverture de la campagne d'alimentation :</td>
        		<?php
        		// Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
    $calendrierid_deb_alim = "date_debut_alim";
    $calendrierid_fin_alim = "date_fin_alim";
    echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_deb_alim . '" ).datepicker({minDate: $( "#' . $calendrierid_deb_alim . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb_alim . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_deb_alim . '").change(function () {
    			$("#' . $calendrierid_fin_alim . '").datepicker("destroy");
    			$("#' . $calendrierid_fin_alim . '").datepicker({minDate: $("#' . $calendrierid_deb_alim . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin_alim . '" ).attr("maxperiode")});
    	});
    });
    </script>
    ';
    echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_fin_alim . '" ).datepicker({minDate: $( "#' . $calendrierid_fin_alim . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin_alim . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_fin_alim . '").change(function () {
    			$("#' . $calendrierid_deb_alim . '").datepicker("destroy");
    			$("#' . $calendrierid_deb_alim . '").datepicker({minDate: $( "#' . $calendrierid_fin_alim . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin_alim . '").datepicker("getDate")});
    	});
    });
    </script>
    ';
    
    ?>
    			<br>
    			<td width=1px><input class="calendrier" type=text name=date_debut_alim
    				id=<?php echo $calendrierid_deb_alim ?> size=10></td>
    		</tr>
    		<tr>
    			<td>Date de fermeture de la campagne d'alimentation :</td>
    			<td width=1px><input class="calendrier" type=text name=date_fin_alim
    				id=<?php echo $calendrierid_fin_alim ?> size=10></td>
    		</tr>
    	</table>
		<input type='submit' name='valider_cal_alim' id='valider_cal_alim' value='Soumettre' />
	</form>	
	
	<?php  // AFFICHAGE DU PARAMETRAGE DU DROIT D'OPTION ?>

    <form name="frm_calendrier_option" method="post">
    
        <input type='hidden' name='userid' value='<?php echo $user->harpegeid();?>'>
        		<br>Paramétrage du calendrier de la campagne de droit d'option du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutoptioncet()).' - '.$fonctions->formatdate($fonctions->finoptioncet());?>)<br>
        		<table>
        		<tr>
        		<td>Date d'ouverture de la campagne de droit d'option :</td>
        		<?php
        		// Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
    $calendrierid_deb_option = "date_debut_option";
    $calendrierid_fin_option = "date_fin_option";
    echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_deb_option . '" ).datepicker({minDate: $( "#' . $calendrierid_deb_option . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb_option . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_deb_option . '").change(function () {
    			$("#' . $calendrierid_fin_option . '").datepicker("destroy");
    			$("#' . $calendrierid_fin_option . '").datepicker({minDate: $("#' . $calendrierid_deb_option . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin_option . '" ).attr("maxperiode")});
    	});
    });
    </script>
    ';
    echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_fin_option . '" ).datepicker({minDate: $( "#' . $calendrierid_fin_option . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin_option . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_fin_option . '").change(function () {
    			$("#' . $calendrierid_deb_option . '").datepicker("destroy");
    			$("#' . $calendrierid_deb_option . '").datepicker({minDate: $( "#' . $calendrierid_fin_option . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin_option . '").datepicker("getDate")});
    	});
    });
    </script>
    ';
    
    ?>
    			<br>
    			<td width=1px><input class="calendrier" type=text name=date_debut_option
    				id=<?php echo $calendrierid_deb_option ?> size=10></td>
    		</tr>
    		<tr>
    			<td>Date de fermeture de la campagne de droit d'option :</td>
    			<td width=1px><input class="calendrier" type=text name=date_fin_option
    				id=<?php echo $calendrierid_fin_option ?> size=10></td>
    		</tr>
    	</table>
		<input type='submit' name='valider_cal_option' id='valider_cal_option' value='Soumettre' />
	</form>	
	<form name="frm_param_plafond_cet" method="post">
        <input type='hidden' name='userid' value='<?php echo $user->harpegeid();?>'>
		Nombre de jours maximum sur CET : <input type='text' name='plafondcet' value='<?php echo $plafondparam;?>'>
		<input type='submit' name='valider_param_plafond' id='valider_param_plafond' value='Soumettre' />
	</form>
</body>
</html>
        