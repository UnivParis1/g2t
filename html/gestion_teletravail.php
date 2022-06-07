<?php

    require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");
    
    $userid = null;
    if (isset($_POST["userid"]))
    {
        // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
        $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
        if ($CASuserId!==false)
        {
            // On a l'agentid de l'agent => C'est un administrateur donc on peut forcer le userid avec la valeur du POST
            $userid = $_POST["userid"];
        }
        else
        {
            $userid = $fonctions->useridfromCAS($uid);
            if ($userid === false)
            {
                $userid = null;
            }
        }
    }
    
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }
    

    $user = new agent($dbcon);
    $user->load($userid);
    
    if (isset($_POST["agentid"]))
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        
        if (! is_numeric($agentid)) {
            $agentid = null;
            $agent = null;
        }
    }
    else
        $agentid = null;
        
        
    require ("includes/menu.php");
    
    $cancelteletravailarray = null;
    if (isset($_POST["cancel"])) // Tableau des id des conventions à désactiver
    {
        $cancelteletravailarray = $_POST["cancel"];
    }
    
    $datedebutconv = null;
    if (isset($_POST["date_debut_conv"])) // Tableau des id des conventions avec les dates de début
    {
        $datedebutconv = $_POST["date_debut_conv"];
    }
    $datefinconv = null;
    if (isset($_POST["date_fin_conv"])) // Tableau des id des conventions avec les dates de fin
    {
        $datefinconv = $_POST["date_fin_conv"];
    }
    
    
    //echo "<br>" . print_r($_POST, true) . "<br><br>";
    
    //echo "$cancelteletravailarray = ";
    //var_export($cancelteletravailarray);
    //echo "<br>";
    
    $erreur = '';
    $info = '';
    
    if (isset($_POST["modification"]))  // On a cliqué sur le bouton "annulation"
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);
        //echo "On va annuler des conventions de télétravail.<br>";
        foreach ((array)$cancelteletravailarray as $cancelteletravailid)
        {
            //echo "cancelteletravailid = $cancelteletravailid <br>";
            $teletravail = new teletravail($dbcon);
            $return = $teletravail->load($cancelteletravailid);
            if (!$return)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Erreur dans le chargement de la convention $cancelteletravailid pour annulation : " . $return;
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                $teletravail->statut(teletravail::STATUT_INACTIVE);
                //echo "<br>";
                //var_dump($teletravail);
                //echo "<br>";
                $erreur = $teletravail->store();
                if ($erreur != "")
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "Erreur dans la sauvegarde du changement de statut de la convention $cancelteletravailid : " . $erreur;
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
                else
                {
                    $info = $info . "La suppression de la convention $cancelteletravailid a été enregistrée.";
                }
            }
        }
        // On va modifier les dates des conventions de télétravail
        foreach ((array)$datedebutconv as $idconv => $datedebut)
        {
            $datefin = $datefinconv[$idconv];
            if (!$fonctions->verifiedate($datedebut) or !$fonctions->verifiedate($datefin))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début ou de fin de la convention $idconv n'est pas correcte.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif ($fonctions->formatdatedb($datedebut)>$fonctions->formatdatedb($datefin))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début est supérieure à la date de fin de la convention $idconv.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                //echo "La convention $idconv a pour nouvelle date de début $datedebut et pour nouvelle date de fin $datefin <br>";
                $teletravail = new teletravail($dbcon);
                $return = $teletravail->load($idconv);
                if (!$return)
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "Erreur dans le chargement de la convention $idconv pour modification : " . $return;
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
                elseif ($teletravail->statut() == teletravail::STATUT_ACTIVE)
                {
                    if ($fonctions->formatdatedb($teletravail->datedebut()) != $fonctions->formatdatedb($datedebut) or $fonctions->formatdatedb($teletravail->datefin()) != $fonctions->formatdatedb($datefin))
                    {
                        $liste = $agent->teletravailliste($datedebut, $datefin);
                        foreach ($liste as $conventionid)
                        {
                            $teletravailverif = new teletravail($dbcon);
                            $teletravailverif->load($conventionid);
                            if ($teletravailverif->statut() == $teletravailverif::STATUT_ACTIVE and $conventionid != $idconv)
                            {
                                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                                $erreur = $erreur . "Erreur : La date de début ou de fin de la convention de télétravail $idconv chevauche une convention existante (id = $conventionid).";
                                break;  // On a trouver au moins une convention active qui chevauge !
                            }
                        }
                        if ($erreur == '')
                        {
                            $teletravail->datedebut($datedebut);
                            $teletravail->datefin($datefin);
                            $erreur = $teletravail->store();
                            if ($erreur != "")
                            {
                                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                                $erreur = $erreur . "Erreur dans la sauvegarde du changement de date de la convention $idconv : " . $erreur;
                                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                            }
                            else
                            {
                                $info = $info . "La modification de la convention $idconv a été enregistrée.";
                            }
                        }
                    }
                    else
                    {
                        // La date de début et de fin sont les mêmes => On ne fait rien
                    }
                    
                }
            }
        }
    }
    

    if (isset($_POST["creation"]))  // On a cliqué sur le bouton "creation"
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);
        //echo "On va creer une convention de télétravail.<br>";
        //Array ( [date_debut] => Array ( [9328] => 01/12/2021 ) [date_fin] => Array ( [9328] => 05/12/2021 ) [jours] => Array ( [0] => 2 [1] => 3 ) [userid] => 9328 [agentid] => 9328 [creation] => Soumettre ) 
        $datedebutteletravail = null;
        if (isset($_POST["date_debut"][$agent->agentid()]))
        {
            $datedebutteletravail = $_POST["date_debut"][$agent->agentid()];
        }
        $datefinteletravail = null;
        if (isset($_POST["date_fin"][$agent->agentid()]))
        {
            $datefinteletravail = $_POST["date_fin"][$agent->agentid()];
        }
        $jours = null;
        if (isset($_POST["jours"]))
        {
            $jours = $_POST["jours"];
        }
        
        $tabteletravail = str_pad('',14,'0');
        //echo "tabteletravail = $tabteletravail <br>";
        foreach((array)$jours as $numjour) // numjour => [1-7] où 1 = lundi
        {
            $numjour = $numjour - 1;   // $numjour = l'index du talbeau 0 = lundi
            $numjour = $numjour * 2;
            $gauche = substr($tabteletravail,0,$numjour);
            $droite = substr($tabteletravail,$numjour+1);
            $tabteletravail = $gauche . '1' . $droite;
            $numjour = $numjour + 1;   
            $gauche = substr($tabteletravail,0,$numjour);
            $droite = substr($tabteletravail,$numjour+1);
            $tabteletravail = $gauche . '1' . $droite;
        }
        //echo "tabteletravail = $tabteletravail <br>";
        $dateok = true;
        if (!$fonctions->verifiedate($datedebutteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if (!$fonctions->verifiedate($datefinteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de fin de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if ($dateok and $fonctions->formatdatedb($datedebutteletravail)>$fonctions->formatdatedb($datefinteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début est supérieure à la date de fin de la convention.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $dateok = false;
        }
        
        if (str_pad('',14,'0') == $tabteletravail)
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Aucun jour de télétravail sélectionné.";
        }
        if ($dateok)
        {
            $liste = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
            foreach ($liste as $conventionid)
            {
                $teletravail = new teletravail($dbcon);
                $teletravail->load($conventionid);
                if ($teletravail->statut() == teletravail::STATUT_ACTIVE)
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "La nouvelle convention de télétravail chevauche une convention existante (id = $conventionid).";
                    break;  // On a trouver au moins une convention active qui chevauge !
                }
            }
        }
        if ($erreur == '')
        {
            $teletravail = new teletravail($dbcon);
            $teletravail->datedebut($datedebutteletravail);
            $teletravail->datefin($datefinteletravail);
            $teletravail->tabteletravail($tabteletravail);
            $teletravail->agentid($agent->agentid());
            $erreur = $teletravail->store();
            if ($erreur != "")
            {
                $erreur = "Erreur dans la sauvegarde dans la création de la convention : <br>" . $erreur;
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                $info = "La création de la convention est réussie.";
                $erreur = "";
            }
        }
    }
    
    if ($erreur != "")
    {
        $erreur = $erreur . "<br>La convention de télétravail n'a pas pu être enregistrée.";
        echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
    }
    if ($info != "")
    {
        echo $fonctions->showmessage(fonctions::MSGINFO, $info);
    }
    echo "<br><br>";

    if (is_null($agentid))
    {
        echo "<form name='demandeforagent'  method='post' action='gestion_teletravail.php'>";
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentcet'  method='post' >";
        
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
        echo "' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='";
        echo "' class='agent' /> ";
        ?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
    	<?php
        echo "<br>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    }
    elseif (!is_null($agentid))
    {
    	$agent = new agent($dbcon);
    	$agent->load($agentid);
    	$teletravailliste = $agent->teletravailliste('01/01/1900', '31/12/2100'); // On va récupérer toutes les demandes de télétravail de l'agent pour les afficher
    	if (count($teletravailliste) > 0)
    	{
    	    echo "<form name='form_teletravail_delete' id='form_teletravail_delete' method='post' >";
    	    echo "<table class='tableausimple' id='listeteletravail'>";
    	    echo "<tr><center><td class='titresimple'>Identifiant</td>
                      <td class='titresimple'>Date début</td>
                      <td class='titresimple'>Date fin</td>
                      <td class='titresimple' id ='convstatut'>Statut</td>
                      <td class='titresimple'>Répartition du télétravail</td>
                      <td class='titresimple'>Annuler</td>
                  </center></tr>";
    	    foreach($teletravailliste as $teletravailid)
    	    {
    	        $teletravail = new teletravail($dbcon);
    	        $teletravail->load($teletravailid);
    	        $datedebutteletravail = $fonctions->formatdate($teletravail->datedebut());
    	        $datefinteletravail = $fonctions->formatdate($teletravail->datefin());
    	        $calendrierid_deb = "date_debut_conv";
    	        $calendrierid_fin = "date_fin_conv";
    	        //echo "<tr><td class='cellulesimple'>" . $teletravail->teletravailid() . "</td><td class='cellulesimple'><input type='text' name='debut[]' value='" . $fonctions->formatdate($teletravail->datedebut()) . "'></td><td class='cellulesimple'><input type='text' name='fin[]' value='" . $fonctions->formatdate($teletravail->datefin()) . "'></td><td class='cellulesimple'>" . $teletravail->statut() . "</td><td class='cellulesimple'><button type='submit' value='" . $teletravail->teletravailid() ."' name='cancel[]' " . (($teletravail->statut() == teletravail::STATUT_INACTIVE) ? "disabled='disabled' ":" ") . ">Annuler</button>" . "</td></tr>";
    	        echo "<tr><td class='cellulesimple'><center>" . $teletravail->teletravailid() . "</center></td>";
    	        //echo "    <td class='cellulesimple'><center>" . $fonctions->formatdate($teletravail->datedebut()) . "</center></td>";
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').change(function () {
        		$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker("destroy");
        		$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
 
	       	$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').change(function () {
       			$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker("destroy");
       			$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php     	        
    	        echo "    <td class='cellulesimple'><center>";
    	        if ($teletravail->statut() == teletravail::STATUT_ACTIVE)
    	        {
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $teletravailid . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $teletravailid .']'?> size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datedebutteletravail ?>'>
<?php
    	        }
    	        else
    	        {
    	            echo $fonctions->formatdate($teletravail->datedebut());
    	        }
    	        echo "</center></td>";
    	        echo "    <td class='cellulesimple'><center>";
    	        if ($teletravail->statut() == teletravail::STATUT_ACTIVE)
    	        {
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+4 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datefinteletravail ?>'>
<?php 
    	        }
    	        else
    	        {
    	            echo $fonctions->formatdate($teletravail->datefin());
    	        }
                echo "</center></td>";
    	        //echo "    <td class='cellulesimple'><center>" . $fonctions->formatdate($teletravail->datefin()) . "</center></td>";
                echo "    <td class='cellulesimple convstatut'><center>" . $teletravail->statut() . "</center></td>";
    	        $somme = 0;
    	        $htmltext = "";
    	        echo "    <td class='cellulesimple'><center>";
    	        for ($index = 0 ; $index < strlen($teletravail->tabteletravail()) ; $index ++)
    	        {
    	            $demijrs = substr($teletravail->tabteletravail(),$index,1);
    	            if ($demijrs>0) // Si dans le tableau la valeur est > 0
    	            {
    	                if (($index % 2) == 0)  // Si c'est le matin => On ajoute 1 à la somme
    	                    $somme = $somme + 1;
    	                elseif (($index % 2) == 1)  // Si c'est l'après-midi => On ajoute 2 à la somme
    	                    $somme = $somme + 2;
    	            }
    	            if (($index % 2) == 1)
    	            {
    	                if ($somme > 0) // Si pas de télétravail => On affiche rien
    	                {
                            if ($somme == 1)  // Que le matin
                                $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment("m"); // => intdiv($index,2)+1 car pour PHP 0 = dimanche et nous 0 = lundi
    	                    elseif ($somme == 2) // Que l'après-midi
    	                       $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment("a");
    	                    elseif ($somme == 3) // Toute la journée
    	                       $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1);
    	                    else // Là, on ne sait pas !!
    	                       $htmltext = $htmltext . "Problème => index = $index  demijrs = $demijrs   somme = $somme";
     	                    
    	                    $htmltext = $htmltext . ", ";
   	                    }
     	                $somme = 0;
   	                }
    	        }
    	        echo substr($htmltext, 0, strlen($htmltext)-2);
    	        echo "    </center></td>";
                echo "    <td class='cellulesimple'><center><input type='checkbox' value='" . $teletravail->teletravailid()  .  "' id='" . $teletravail->teletravailid()  .  "' name='cancel[]' " . (($teletravail->statut() == teletravail::STATUT_INACTIVE) ? "disabled='disabled' ":" ") . ">" . "</center></td>
                      </tr>";
    	    }
            echo "</table>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    	    echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
    	    echo "<input type='submit' value='Soumettre' name='modification'/>";
    	    echo "</form>";
            echo "<br>";
            echo "<input type='checkbox' id='hide' name='hide' onclick='hide_inactive();'>Masquer les conventions inactives</input><br>";
?>
	<script>
		function hide_inactive()
		{
			//alert ("Plouf !");
		    var tableau = document.getElementById('listeteletravail');
		    //alert (tableau.id);
		    for (var i = 1; i < tableau.querySelectorAll('tr').length; i++)
		    {
		        //alert(i);
		        var currenttr = tableau.querySelectorAll('tr')[i];

		        //alert(currenttr.innerHTML);
		        var statutcase = currenttr.getElementsByClassName('convstatut')[0]; //getElementById('convstatut');

		        //alert (statutcase.innerHTML);
		        
		        if (statutcase.innerText == '<?php echo teletravail::STATUT_INACTIVE ?>')
		        {
    		        var checkboxvalue = document.getElementById('hide').checked;
    		        if (checkboxvalue)
    		        {
    		        	//alert ('on masque.');
    		        	currenttr.style.display = "none";
    		        }
    		        else
    		        {
    		        	//alert ('on affiche.');
    		        	currenttr.style.display = "table-row";
    		        }
		        }
		    }
		}
		//document.getElementById('hide').click();
	</script>
<?php 
    	}
    	else
    	{
    	    echo "<br>Pas de convention de télétravail saisie dans l'application<br>"; 
    	}
    	
    	$datedebutteletravail = "";
    	$datefinteletravail = "";
    	$calendrierid_deb = "date_debut";
    	$calendrierid_fin = "date_fin";
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php
    	echo "<br><br>";
    	echo "Création d'une nouvelle convention de télétravail pour : " . $agent->identitecomplete()  . " <br>";
    	echo "<form name='form_teletravail_creation' id='form_teletravail_creation' method='post' >";
    	echo "Date de début de la convention télétravail : ";
    	if ($fonctions->verifiedate($datedebutteletravail)) {
    	    $datefindeleg = $fonctions->formatdate($datedebutteletravail);
    	}
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $agent->agentid() . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $agent->agentid() .']'?> size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datedebutteletravail ?>'>
<?php
    	echo "<br>";
    	echo "Date de fin de la convention télétravail : ";
        if ($fonctions->verifiedate($datefinteletravail)) {
            $datefinteletravail = $fonctions->formatdate($datefinteletravail);
        }      
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+4 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datefinteletravail ?>'>
<?php

    	echo "<br>";
    	echo "Jours de télétravail : ";
    	echo "<table class='tableausimple'>";
	    echo "<tr><center>
                  <td class='cellulesimple'><input type='checkbox' value='1' id='creation_1' name='jours[]'>Lundi</input></td>
                  <td class='cellulesimple'><input type='checkbox' value='2' id='creation_2' name='jours[]'>Mardi</input></td>
                  <td class='cellulesimple'><input type='checkbox' value='3' id='creation_3' name='jours[]'>Mercredi</input></td>
                  <td class='cellulesimple'><input type='checkbox' value='4' id='creation_4' name='jours[]'>Jeudi</input></td>
                  <td class='cellulesimple'><input type='checkbox' value='5' id='creation_5' name='jours[]'>Vendredi</input></td>
              </center></tr>";
        echo "</table>";
	    echo "<br>";
	    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
	    echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
	    echo "<input type='submit' value='Soumettre'  name='creation'/>";
	    echo "</form>";
    }
    

?>
</body>
</html>