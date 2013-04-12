<?php
	if (is_null($user) or is_null($user->harpegeid()))
	{
		echo "PROBLEME : L'utilisateur n'est pas renseigné ==> objet \$user!!!! <br>";
		exit();
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr"> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /> 

<script type="text/javascript">
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
			return; // ignore les alt-tab lors du hovering (empÃªche les erreurs)
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

<!-- P1 INLINE PORTLET DEBUT -->
<!--
<script type="text/javascript">

	window.cssToLoadIfInsideIframe = "https://esup-data.univ-paris1.fr/esup/canal/css/g2t.css";

</script>
-->
<script type="text/javascript">window.bandeau_ENT={current:'g2t'};</script>
<script type="text/javascript" src="https://esup-data.univ-paris1.fr/esup/outils/postMessage-resize-iframe-in-parent.js">
</script>

<link rel="stylesheet" type="text/css" href="style/style.css" media="screen">
<link rel="stylesheet" type="text/css" href="style/jquery-ui.css" media="screen">
</head> 

<body> 




<div id="mainmenu">
	<ul class="niveau1">     
		<li>MENU AGENT 
			<ul class="niveau2"> 
				<li onclick='document.accueil.submit();'>
					<form name='accueil'  method='post' action="index.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
					</form>
					<a href="javascript:document.accueil.submit();">Accueil</a>
				</li>				
				<li onclick='document.planning.submit();'>
					<form name='planning'  method='post' action="affiche_planning.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
					</form>
					<a href="javascript:document.planning.submit();">Planning</a>
				</li>				
				<li onclick='document.agentannulation.submit();'>
					<form name='agentannulation'  method='post' action="gestion_demande.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="agentid" value="<?php echo $user->harpegeid(); ?>">
					</form>
					<a href="javascript:document.agentannulation.submit();">Annulation de demandes</a>
				</li>				
<?php
	if (false) 
//	if ($user->structure()->affichetoutagent() == "o")
	{
?>
				<li onclick='document.agent_struct_planning.submit();'>
					<form name='agent_struct_planning'  method='post' action="structure_planning.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="mode" value="agent">
					</form>
					<a href="javascript:document.agent_struct_planning.submit();">Plannings de la structure</a>
				</li>
<?php 		
	}
?>	
				<li onclick='document.dem_conge.submit();'>
					<form name='dem_conge'  method='post' action="etablir_demande.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="agentid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="typedemande" value="conges">
					</form>
					<a href="javascript:document.dem_conge.submit();">Etablir une demande de congé</a>
				</li>				
				<li onclick='document.dem_absence.submit();'>
					<form name='dem_absence'  method='post' action="etablir_demande.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="agentid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="typedemande" value="absence">
					</form>
					<a href="javascript:document.dem_absence.submit();">Etablir une demande d'autorisation d'absence</a>
				</li>				
				<li onclick='document.agent_autodeclaration.submit();'>
					<form name='agent_autodeclaration'  method='post' action="etablir_autodeclaration.php">
						<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="agentid" value="<?php echo $user->harpegeid(); ?>">
						<input type="hidden" name="mode" value="agent">						
					</form>
					<a href="javascript:document.agent_autodeclaration.submit();">Validation dossier, temps partiel</a>
				</li>
			</ul> 
		</li> 
	</ul>
<?php 
	if ($user->estresponsable())
	{
?> 
		<ul class="niveau1">     
			<li>MENU RESPONSABLE 
				<ul class="niveau2"> 
					<li class="plus">
						<a>Gestion de l'année en cours</a>
                  <ul class="niveau3">
							<li onclick='document.resp_gest_conge.submit();'>
								<form name='resp_gest_conge'  method='post' action="gestion_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="responsableid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="previous" value="no">
								</form>
								<a href="javascript:document.resp_gest_conge.submit();">Annulation de demandes</a>
							</li>
							<li onclick='document.resp_conge.submit();'>
								<form name='resp_conge'  method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="typedemande" value="conges">
									<input type="hidden" name="previous" value="no">
								</form>
								<a href="javascript:document.resp_conge.submit();">Etablir une demande de congé pour un agent</a>
							</li>
							<li onclick='document.resp_absence.submit();'>
								<form name='resp_absence'  method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="typedemande" value="absence">
									<input type="hidden" name="previous" value="no">
								</form>
								<a href="javascript:document.resp_absence.submit();">Etablir une demande d'absence pour un agent</a>
							</li>
							<li onclick='document.resp_valid_conge.submit();'>
								<form name='resp_valid_conge'  method='post' action="valider_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
									<input type="hidden" name="previous" value="no">
								</form>
								<a href="javascript:document.resp_valid_conge.submit();">Demandes en attente</a>
							</li>
							<li onclick='document.resp_valid_autodecla.submit();'>
								<form name='resp_valid_autodecla'  method='post' action="valider_autodeclaration.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
								</form>
								<a href="javascript:document.resp_valid_autodecla.submit();">Validation des autodéclarations</a>
							</li>				
							<li onclick='document.resp_autodeclaration.submit();'>
								<form name='resp_autodeclaration'  method='post' action="etablir_autodeclaration.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
								</form>
								<a href="javascript:document.resp_autodeclaration.submit();">Validation dossier, tps partiel pour un agent</a>
							</li>
							<li onclick='document.resp_gestcet.submit();'>
								<form name='resp_gestcet'  method='post' action="gerer_cet.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
								</form>
								<a href="javascript:document.resp_gestcet.submit();">Gestion du CET d'un agent</a>
							</li>
							<li onclick='document.resp_ajout_conge.submit();'>
								<form name='resp_ajout_conge'  method='post' action="ajouter_conges.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
								</form>
								<a href="javascript:document.resp_ajout_conge.submit();">Ajout de jours supplémentaires pour un agent</a>
							</li>
							<li onclick='document.resp_aff_solde.submit();'>
								<form name='resp_aff_solde'  method='post' action="affiche_solde.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
									<input type="hidden" name="previous" value="no">
								</form>
								<a href="javascript:document.resp_aff_solde.submit();">Affichage du solde des agents de la structure</a>
							
<?php
		// Si on est 3 mois avant la fin de la période ==> On peut saisir des jours par anticipation
		$datetemp = ($fonctions->anneeref()+1) . $fonctions->finperiode();
		$timestamp = strtotime($datetemp);
		$datetemp = date("Ymd", strtotime("-3month", $timestamp ));  // On remonte de 3 mois
		//echo "TimeStamp = " . $datetemp . "<br>";
		if (date("Ymd") > $datetemp)
		{
?>				
							<li onclick='document.resp_conge_anticipe.submit();'>
								<form name='resp_conge_anticipe'  method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="typedemande" value="conges">
									<input type="hidden" name="congeanticipe" value="yes">
								</form>
								<a href="javascript:document.resp_conge_anticipe.submit();">Etablir une demande de congé anticipé pour un agent</a>
							</li>
<?php
		} 
?>				
							
						</ul>
					</li>
					<li class="plus">
						<a>Gestion de l'année précédente</a>
                  <ul class="niveau3">
							<li onclick='document.resp_gest_conge_previous.submit();'>
								<form name='resp_gest_conge_previous'  method='post' action="gestion_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="responsableid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="previous" value="yes">
								</form>
								<a href="javascript:document.resp_gest_conge_previous.submit();">Annulation de demandes</a>
							</li>
							<li onclick='document.resp_conge_previous.submit();'>
								<form name='resp_conge_previous'  method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="typedemande" value="conges">
									<input type="hidden" name="previous" value="yes">
								</form>
								<a href="javascript:document.resp_conge_previous.submit();">Etablir une demande de congé pour un agent</a>
							</li>
							<li onclick='document.resp_absence_previous.submit();'>
								<form name='resp_absence_previous'  method='post' action="etablir_demande.php">
									<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="typedemande" value="absence">
									<input type="hidden" name="previous" value="yes">
								</form>
								<a href="javascript:document.resp_absence_previous.submit();">Etablir une demande d'absence pour un agent</a>
							</li>
							<li onclick='document.resp_valid_conge_previous.submit();'>
								<form name='resp_valid_conge_previous'  method='post' action="valider_demande.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
									<input type="hidden" name="previous" value="yes">
								</form>
								<a href="javascript:document.resp_valid_conge_previous.submit();">Demandes en attente</a>
							</li>
							<li onclick='document.resp_aff_solde_previous.submit();'>
								<form name='resp_aff_solde_previous'  method='post' action="affiche_solde.php">
									<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
									<input type="hidden" name="mode" value="resp">
									<input type="hidden" name="previous" value="yes">
								</form>
								<a href="javascript:document.resp_aff_solde_previous.submit();">Affichage du solde des agents de la structure</a>
							</li>	
						</ul>
					</li>
					<li onclick='document.resp_struct_planning.submit();'>
						<form name='resp_struct_planning'  method='post' action="structure_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="resp">
						</form>
						<a href="javascript:document.resp_struct_planning.submit();">Plannings de la structure</a>
					</li>
<!--   		
					<li>
						<form name='resp_valid_conge'  method='post' action="valider_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="resp">
							<input type="hidden" name="previous" value="no">
							</form>
						<a href="javascript:document.resp_valid_conge.submit();">Demandes en attente</a>
					</li>		
 -->		
					<li onclick='document.resp_parametre.submit();'>
						<form name='resp_parametre'  method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="action" value="modif">
							<input type="hidden" name="mode" value="resp">
						</form>
						<a href="javascript:document.resp_parametre.submit();">Paramétrage des dossiers et de la structure</a>
					</li>	
					
<!--  			
					<li>
						<a href="g2t_consult_modif_att.php?id_ses={$ID_SES}">Att [Pas fait]</a>
					</li>					
-->
<!-- 
					<li>
						<form name='resp_gest_conge'  method='post' action="gestion_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="responsableid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="previous" value="no">
						</form>
						<a href="javascript:document.resp_gest_conge.submit();">Annulation de demandes</a>
					</li>
					<li>
						<form name='resp_conge'  method='post' action="etablir_demande.php">
							<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="typedemande" value="conges">
							<input type="hidden" name="previous" value="no">
						</form>
						<a href="javascript:document.resp_conge.submit();">Etablir une demande de congé pour un agent</a>
					</li>				
					<li>
						<form name='resp_absence'  method='post' action="etablir_demande.php">
							<input type="hidden" name="responsable" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="typedemande" value="absence">
							<input type="hidden" name="previous" value="no">
						</form>
						<a href="javascript:document.resp_absence.submit();">Etablir une demande d'absence pour un agent</a>
					</li>
 -->
<!-- 
					<li>
						<a href="g2t_add_reliquats_foragent.php?id_ses={$ID_SES}">Ajout de reliquat pour un agent [Pas fait]</a>
					</li>
-->
				</ul> 
			</li> 
		</ul> 
<?php
	} 
	if ($user->estgestionnaire())
	{
?>
		<ul class="niveau1">     
			<li>MENU GESTIONNAIRE 
				<ul class="niveau2"> 
					<li onclick='document.gest_valid_conge.submit();'>
						<form name='gest_valid_conge'  method='post' action="valider_demande.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_valid_conge.submit();">Demandes en attente</a>
					</li>
					<li onclick='document.gest_aff_solde.submit();'>
						<form name='gest_aff_solde'  method='post' action="affiche_solde.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_aff_solde.submit();">Affichage du solde des agents de la structure</a>
					</li>	
					
<!--
					<li><a href="g2t_valid_absence_gestio.php?id_ses={$ID_SES}">Absences en attente de validation</a></li>
-->				
					<li onclick='document.gest_parametre.submit();'>
						<form name='gest_parametre'  method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="action" value="lecture">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_parametre.submit();">Affichage paramétrage des dossiers</a>
					</li>				
					<li onclick='document.gest_struct_planning.submit();'>
						<form name='gest_struct_planning'  method='post' action="structure_planning.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_struct_planning.submit();">Plannings de la structure</a>
					</li>
					<li onclick='document.gest_parametre_modif.submit();'>
						<form name='gest_parametre_modif'  method='post' action="gestion_dossier.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="action" value="modif">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_parametre_modif.submit();">Paramétrage des dossiers</a>
					</li>				
					<li onclick='document.gest_valid_autodecla.submit();'>
						<form name='gest_valid_autodecla'  method='post' action="valider_autodeclaration.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="gestion">
						</form>
						<a href="javascript:document.gest_valid_autodecla.submit();">Validation des autodéclarations</a>
					</li>
					<li onclick='document.gest_gestcet.submit();'>
						<form name='gest_gestcet'  method='post' action="gerer_cet.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
							<input type="hidden" name="mode" value="gest">
						</form>
						<a href="javascript:document.gest_gestcet.submit();">Gestion du CET d'un agent</a>
					</li>
				</ul> 
			</li> 
		</ul>
<?php
	} 
	if ($user->estadministrateur())
	{
?>
		<ul class="niveau1">     
			<li>MENU ADMINISTRATEUR 
				<ul class="niveau2"> 
					<li onclick='document.admin_struct_gest.submit();'>
						<form name='admin_struct_gest'  method='post' action="gestion_structure.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						</form>
						<a href="javascript:document.admin_struct_gest.submit();">Paramétrage des structures</a>
					</li>
					<li onclick='document.admin_info_agent.submit();'>
						<form name='admin_info_agent'  method='post' action="affiche_info_agent.php">
							<input type="hidden" name="userid" value="<?php echo $user->harpegeid(); ?>">
						</form>
						<a href="javascript:document.admin_info_agent.submit();">Affichage informations agent</a>
					</li>
<!-- 				
					<li>
						<a href="g2t_consult_stat_adm.php?id_ses={$ID_SES}">Tableau de bord [Pas fait]</a>
					</li>
					<li>
						<a href="g2t_planning_struct_adm.php?id_ses={$ID_SES}">Tous les plannings [Pas fait]</a>
					</li>
 -->
 				</ul> 
			</li> 
		</ul>  
<?php
	}
?> 
</div>
<br>
<br>
<br>
</body> 
</html>







