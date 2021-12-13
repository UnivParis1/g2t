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
*/
    
    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");
    //echo "<br><br><br>"; print_r($_POST); echo "<br>";
    
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    if (isset($_POST["previous"]))
        $previoustxt = $_POST["previous"];
    else
        $previoustxt = null;
    if (strcasecmp($previoustxt, "yes") == 0)
        $previous = 1;
    else
        $previous = 0;

    if (isset($_POST["indexmois"]))
        $indexmois = $_POST["indexmois"];
    else
        $indexmois = null;
    // echo "indexmois = $indexmois <br>";
    if (is_null($indexmois) or $indexmois == "")
        $indexmois = date("m");
    $indexmois = str_pad($indexmois, 2, "0", STR_PAD_LEFT);
    // echo "indexmois (apres) = $indexmois <br>";
    $annee = $fonctions->anneeref() - $previous;
    // echo "annee = $annee <br>";
    $debutperiode = $fonctions->debutperiode();
    // echo "debut periode = $debutperiode <br>";
    $moisdebutperiode = date("m", strtotime($fonctions->formatdatedb(date("Y") . $debutperiode)));
    // echo "moisdebutperiode = $moisdebutperiode <br>";
    if ($indexmois < $moisdebutperiode)
        $annee ++;
    // echo "annee (apres) = $annee <br>";

    if (isset($_POST["mode"]))
        $mode = $_POST["mode"]; // Mode = resp ou agent
    else
        $mode = "resp";

    $date_selected = '';
    if (isset($_POST["date_selected"]))
        $date_selected = $_POST["date_selected"];

    $moment_selected = '';
    if (isset($_POST["moment_selected"]))
        $moment_selected = $_POST["moment_selected"];
            
    $agentid_selected = '';
    if (isset($_POST['agentid_selected']))
        $agentid_selected = $_POST['agentid_selected'];
        
    if ($date_selected != "" and $moment_selected != "" and $agentid_selected != "")
    {
        $complement = new complement($dbcon);
        $agent = new agent($dbcon);
        $agent->load($agentid_selected);
        $listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
        if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)===false)
        {   // On n'a pas trouvé la date dans la liste
            $complement->complementid('TT_EXCLU_' . $fonctions->formatdatedb($date_selected));
            $complement->harpegeid($agentid_selected);
            $complement->valeur($fonctions->formatdatedb($date_selected));  // . "|" . $moment_selected;
            $complement->store();
        }
        else
        {   // La date est dans liste des exclusions
            $erreur = $agent->supprjourteletravailexclu($date_selected);
            echo "<br>$erreur<br>";
        }
    }

    $planningelement = new planningelement($dbcon);
    $planningelement->type('teletrav');
    $couleur = $planningelement->couleur();
    
?>
<script>
	var dbclick_element = function(elementid, agentid, date,moment)
	{
		var element = document.getElementById(elementid);
		var identiteagent = element.closest(".ligneplanning").firstChild.innerText;
		var tableau = element.closest("table");
		if (tableau.classList.contains('teletravail_hidden'))
		{
			// Si la classe teletravail_hidden est définie dans le tableau => On ne peut pas modifier une journée de télétravail
			alert ('L\'affichage du télétravail est désactivé.');
			return;
		}
		//alert ('Active element = ' + document.activeElement.innerHTML + '   elementid = ' + elementid);
		if (element.bgColor == '<?php echo $couleur ?>') // C'est un teletravail à annuler
		{
    		if (confirm ('Supprimer le télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?'))
    		{
    			//alert('Le télétravail du ' + date + ' est supprimé.');
    			
    			var input = document.getElementById('date_selected');
    			input.value = date;
    			var input = document.getElementById('moment_selected');
    			input.value = moment;
    			var input = document.getElementById('agentid_selected');
    			input.value = agentid;
    			var submit_form = document.getElementById('select_mois');
    			submit_form.submit();
    
    		}
    		else
    		{
    			//alert('Le télétravail du ' + date + ' est maintenu.');
    		}
    	}
    	else if (element.bgColor == '<?php echo planningelement::COULEUR_VIDE ?>') // C'est un teletravail déjà annulé
    	{
    		if (confirm ('Réactiver le télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?'))
    		{
    			var input = document.getElementById('date_selected');
    			input.value = date;
    			var input = document.getElementById('moment_selected');
    			input.value = moment;
    			var input = document.getElementById('agentid_selected');
    			input.value = agentid;
    			var submit_form = document.getElementById('select_mois');
    			submit_form.submit();
    		}
    		else
    		{
    			//alert('Pas de réactivation du télétravail du ' + date + '.');
    		}
    	}
	};


</script>

<?php 

    echo "<form name='select_mois' id='select_mois' method='post'>";
    echo "<center><select name='indexmois'>";

    // On reprend le mois de début de période
    $index = $moisdebutperiode;
    // L'année c'est l'année de référence
    $anneemois = $fonctions->anneeref() - $previous;
    // echo "index = $index <br>";
    for ($indexcpt = 1; $indexcpt <= 12; $indexcpt ++) {
        echo "<option value='$index'";
        if ($index == $indexmois)
            echo " selected ";
        echo ">" . $fonctions->nommois("01/" . str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . date("Y")) . "  " . $anneemois . "</option>";
        // On calcule le modulo
        $index = ($index % 12) + 1;
        // Si le mois est > 12 ou égal à 1 alors c'est qu'on est passé à l'année suivante
        if ($index > 12 or $index == 1)
            $anneemois = $anneemois + 1;
    }

    echo "</select>";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "' />";
    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
    echo "<input type='hidden' name='date_selected' id='date_selected' value='' />";
    echo "<input type='hidden' name='moment_selected' id='moment_selected' value='' />";
    echo "<input type='hidden' name='agentid_selected' id='agentid_selected' value='' />";
    echo "<input type='submit' value='Soumettre' /></center>";
    echo "</form>";
    if (strcasecmp($mode, "resp") == 0) {
        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure) {
            // Si la structure est ouverte => On la garde
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                if (strcasecmp($structure->sousstructure(), "o") == 0) {
                    $sousstructliste = $structure->structurefille();
                    foreach ((array) $sousstructliste as $key => $struct) {
                        // Si la structure est fermée.... On la supprime de la liste
                        if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                            // echo "Index = " . array_search($struct, $sousstructliste) . " Key = " . $key . "<br>";
                            // echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
                            unset($sousstructliste["$key"]);
                            // echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
                        }
                    }
                    // echo "<br>sousstructliste = "; print_r($sousstructliste); echo "<br>";
                    $structureliste = array_merge($structureliste, (array) $sousstructliste);
                    // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
                }
            } else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure) {
            echo "<br>";
            echo $structure->planninghtml($indexmois . "/" . $annee,null,false,true,true);

            if ($structure->responsable()->harpegeid() == $user->harpegeid())
            {
                echo "<br>";
                echo "<form name='form_teletravailPDF' id='form_teletravailPDF' method='post' action='affiche_pdf.php' target='_blank'>";
                echo "<input type='hidden' name='indexmois' value='" . $indexmois  . "' />";
                echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "' />";
                echo "<input type='hidden' name='mode' value='" . $mode . "' />";
                echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
                echo "<input type='hidden' name='structureid' value='" . $structure->id() .  "' />";
                
                $currentyrear = date('Y');
                $currentmonth = date('m');
                if ($currentmonth >= 10)
                    $currentmonth = 7;
                elseif ($currentmonth >= 7)
                    $currentmonth = 4;
                elseif ($currentmonth >= 4)
                    $currentmonth = 1;
                else
                {
                    $currentmonth = 10;
                    $currentyrear = $currentyrear - 1;
                }
                $currentmonth = str_pad($currentmonth, 2, '0',STR_PAD_LEFT);
                $datedebut = $currentyrear . $currentmonth . '01';
                
                
                $currentyrear = date('Y');
                $currentmonth = date('m');
                if ($currentmonth >= 10)
                    $currentmonth = 9;
                elseif ($currentmonth >= 7)
                    $currentmonth = 6;
                elseif ($currentmonth >= 4)
                    $currentmonth = 3;
                else
                {
                    $currentmonth = 12;
                    $currentyrear = $currentyrear - 1;
                }
                $currentmonth = str_pad($currentmonth, 2, '0',STR_PAD_LEFT);
                $datefin = $currentyrear . $currentmonth . $fonctions->nbr_jours_dans_mois($currentmonth, $currentyrear);
                        
                
                echo "<input type='hidden' name='datedebut' value='" . $datedebut . "' />";
                echo "<input type='hidden' name='datefin' value='" . $datefin .  "' />";
                
                echo "Générer le document 'télétravail' du trimestre précédent pour la structure " . $structure->nomlong() . " (du " . $fonctions->formatdate($datedebut) . " au " . $fonctions->formatdate($datefin)  . ")<br>";
                echo "<input type='submit' name='teletravailPDF' />";
                echo "</form>";
            }
        }

        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure) {
            if (strcasecmp($structure->afficherespsousstruct(), "o") == 0) {
                echo "<br>";
                echo $structure->planningresponsablesousstructhtml($indexmois . "/" . $annee,true,false);
            }
        }
    } elseif (strcasecmp($mode, "gestion") == 0) {
        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure) {
            // Si la structure est ouverte => On la garde
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                if (strcasecmp($structure->sousstructure(), "o") == 0) {
                    $sousstructliste = $structure->structurefille();
                    foreach ((array) $sousstructliste as $key => $struct) {
                        // Si la structure est fermée.... On la supprime de la liste
                        if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                            // echo "Index = " . array_search($struct, $sousstructliste) . " Key = " . $key . "<br>";
                            // echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
                            unset($sousstructliste["$key"]);
                            // echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
                        }
                    }
                    $structureliste = array_merge($structureliste, (array) $sousstructliste);
                    // Remarque : Le tableau ne contiendra pas de doublon, car la clÃ© est le code de la structure !!!
                }
            } else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure) {
            echo "<br>";
            echo $structure->planninghtml($indexmois . "/" . $annee,null,false,true);
        }

        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure) {
            if (strcasecmp($structure->afficherespsousstruct(), "o") == 0) {
                echo "<br>";
                echo $structure->planningresponsablesousstructhtml($indexmois . "/" . $annee,true);
            }
        }
    } else {
        $affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));
        /*
         * if ($annee. $indexmois <= date('Ym'))
         * {
         * echo "<br><B><font SIZE='3pt' color=#FF0000>Attention : Des données ont été masquées en raison de restrictions d'accès....</font></B><br>";
         * echo "<font color=#FF0000>Les informations antérieures à la date du jour, ont été masquées.</font><br>";
         * }
         */
        foreach ($affectationliste as $affectkey => $affectation) {
            $structureid = $affectation->structureid();
            $structure = new structure($dbcon);
            $structure->load($structureid);
            if (strcasecmp($structure->affichetoutagent(), "o") == 0) {
                echo "<br>";
                // echo "Planning de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                echo $structure->planninghtml($indexmois . "/" . $annee, 'n', true,false); // 'n' car l'agent ne doit pas voir les conges des sous-structures (si autorisé) + Pas de télétravail sinon visuellement c'est trompeur
            }
        }
    }

    unset($strucuture);
?>

</body>
</html>