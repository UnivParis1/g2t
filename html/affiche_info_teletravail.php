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

/*    
    // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
    $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
    if ($CASuserId===false)
    {
        // Ce n'est pas un administrateur
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        echo "<script>alert('Accès réservé aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
        //        header('Location: index.php');
        exit();
    }
*/
    
    $user = new agent($dbcon);
    $user->load($userid);
    
    if (isset($_POST["agentid"]))
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        if (! is_numeric($agentid)) 
        {
            $agentid = null;
            $agent = null;
        }
    }
    else
        $agentid = null;
    
    $mois = date('m');
    if (isset($_POST["trimestre"]))
    {
        $trimestre = $_POST["trimestre"];
    }
    else
    {
        if ($mois<=3)
        {
            // 1er trimestre
            $trimestre = 1;
        }
        elseif ($mois<=6)
        {
            // 2e trimestre
            $trimestre = 2;
        }
        elseif ($mois<=9)
        {
            // 3e trimestre
            $trimestre = 3;
        }
        else
        {
            // 4e trimestre
            $trimestre = 4;
        }
    }
    
    if (isset($_POST["annee"]))
    {
        $annee = $_POST["annee"];
    }
    else
    {
        $annee = date('Y');
    }
    
    require ("includes/menu.php");

//    var_dump($_POST);
    
    echo "<form name='form_teletravail_interval' id='form_teletravail_interval' method='post' >";
    echo "Sélectionnez la période souhaitée : ";
    
    echo "<div>";
    echo "<select name='trimestre' id='trimestre'>";
    echo "<option value='1'";
    if ($trimestre==1) echo " selected "; else echo " ";
    echo ">1er trimestre</option>";
    echo "<option value='2'";
    if ($trimestre==2) echo " selected "; else echo " ";
    echo ">2e trimestre</option>";
    echo "<option value='3'";
    if ($trimestre==3) echo " selected "; else echo " ";
    echo ">3e trimestre</option>";
    echo "<option value='4'";
    if ($trimestre==4) echo " selected "; else echo " ";
    echo ">4e trimestre</option>";
    echo "</select>";
    
    echo "&nbsp;&nbsp;";
    echo "<select name='annee' id='annee'>";
    $anneebase = date('Y');
    for ($index = $anneebase-1 ; $index <= $anneebase+1; $index++)
    {
        echo "<option value='$index'";
        if ($index==$annee) echo " selected "; else echo " ";
        echo ">$index</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "<br>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='submit' value='Soumettre' name='selection_trim'/>";
    echo "</form>";

    
    if (isset($_POST['selection_trim']))
    {
        $numtrimestre = $_POST['trimestre'];
        $annee = $_POST['annee'];
        switch ($numtrimestre)
        {
            case 1 :
                $libelle = "1er trimestre";
                $datedebut = "01/01/$annee";
                $datefin = "31/03/$annee";
                break;
            case 2 :
                $libelle = "2e trimestre";
                $datedebut = "01/04/$annee";
                $datefin = "30/06/$annee";
                break;
            case 3 :
                $libelle = "3e trimestre";
                $datedebut = "01/07/$annee";
                $datefin = "30/09/$annee";
                break;
            case 4 :
                $libelle = "4e trimestre";
                $datedebut = "01/10/$annee";
                $datefin = "31/12/$annee";
                break;
        }
        echo "<br><br>";
        
/*        
        // On récupère le montant de l'indemnité TELETRAVAIL dans la base
        $montant_teletravail = $fonctions->liredbconstante('MONTANT_TELETRAVAIL');
        if ($montant_teletravail == '')
        {
            $montant_teletravail = '2.50';
        }
*/        
        echo "<br>Synthèse du nombre de jours de congés pour les agents ayant une convention de télétravail au <label id='id_trimestre'>$libelle $annee</label><br>"; 
        $agentlist = $fonctions->listeagentteletravail($datedebut, $datefin,true);
        /////////////////////////////////////////////////////////////////
        /////////// LIGNE DE TEST ///////////////////////////////////////
        //$agentlist = array('975');
        /////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////
        echo "<table class='tableausimple' id='table_teletravail'>";
        echo "<tr>
                 <td class='titresimple'>Numéro siham</td>
                 <td class='titresimple'>Nom agent</td>
                 <td class='titresimple'>Prénom agent</td>
                 <td class='titresimple'>Structure</td>
                 <td class='titresimple'>Direction</td>
                 <td class='titresimple'>Nombre de jours</td>
                 <td class='titresimple'>Répartition</td>";
//                 <td class='titresimple'>Nombre de jours annuels</td>
        echo "   <td class='titresimple'>Calcul du montant</td>
                 <td class='titresimple'>Montant à payer</td>
              </tr>";
        foreach ((array)$agentlist as $agentid)
        {
            $agent = new agent($dbcon);
            if (!$agent->load($agentid))
            {
                echo "Agent non connu de la base G2T.... <br>";
            }
            else
            {
                
                // On regarde si dans la période l'agent a une position administrative ==> Il est en activité
                // Ca permet d'exclure les agents qui ne sont plus "agent de l'établissement" dans le trimestre édité
                $historique = $agent->historiquesituationadmin($datedebut, $datefin);
                // Soit il n'y en pas ou il n'y en a qu'un et c'est la fin d'activité 'FINAC' ou détachement 'DET01'
                if (count($historique)==0 or (count($historique)==1 and in_array($historique[0]['positionadmin'],array('FINAC','DET01'))==true))
                {
                    // On ne traite pas l'agent car il n'était pas en activité durant le trimestre .
                    continue;
                }
                
                $tabrepartition = array();
                $infoindemnite = array();
                $planning = new planning($dbcon);
                $nbjrsteletravail = $planning->nbjoursteletravail($agent->agentid(), $datedebut, $datefin,true,$tabrepartition,$infoindemnite);
                unset($planning);
                
                foreach ($tabrepartition as $key => $value)
                {
                    $newkey = html_entity_decode(TABCOULEURPLANNINGELEMENT[$key]["libelle"]);
                    $tabrepartition["$newkey"]=$value;
                    unset($tabrepartition[$key]);
                }
//                $repartitionteletravail = implode(" / ", $tabrepartition);
                $repartitionteletravail = http_build_query($tabrepartition, '', '<br>');
                $repartitionteletravail = urldecode($repartitionteletravail);
                
                //$nbjrsteletravailannuel = 0;
                //$planning = new planning($dbcon);
                //$nbjrsteletravailannuel = $planning->nbjoursteletravail($agent->agentid(), "01/01/$annee", $datefin,false,$tabrepartition);
                //unset($planning);
                
                //var_dump($tabrepartition);
 /*               
                $planning = $agent->planning($datedebut, $datefin,true,false);
                //var_dump($planning);
                $nbjrsteletravail = 0;
                foreach ($planning->planning() as $element)
                {
                    //echo "<br>Le type est : " . $element->type();
                    if (in_array($element->type(),array('teletrav', 'teleetab', 'telegouv', 'telesante'))) // Si c'est un télétravail => On compte +0.5 (car un element pour matin et un element pour apres-midi)
                    {
                        $nbjrsteletravail = $nbjrsteletravail + 0.5;
                    }
                }
*/                
                
                $nomstruct = "Non définie";
                $nomdirection = "Non définie";
/*                 
                $agentstruct = new structure($dbcon);
                if ($agentstruct->load($agent->structureid()))
                {
                    $nomstruct = $agentstruct->nomcourt();
                }
                else
                {
                    $historique = $agent->historiqueaffectation($datedebut, $datefin);
                    // On récupère la dernière affectation de l'agent comprise dans les dates
                    if (count($historique) > 0)
                    {
                        $structid = $historique[count($historique)-1]['structureid'];
                        $agentstruct = new structure($dbcon);
                        if ($agentstruct->load($structid))
                        {
                            $nomstruct = $agentstruct->nomcourt();
                        }
                    }
                }
*/                
                $historique = $agent->historiqueaffectation($datedebut, $datefin);
                // On récupère la dernière affectation de l'agent comprise dans les dates
                if (count($historique) > 0)
                {
                    $structid = $historique[count($historique)-1]['structureid'];
                    $agentstruct = new structure($dbcon);
                    if ($agentstruct->load($structid))
                    {
                        $nomstruct = $agentstruct->nomcourt();
                        $nomdirection = $agentstruct->structureenglobante()->nomcourt();
                    }
                    else
                    {
                        $nomstruct = "Inconnue (id=$structid)";
                        $nomdirection = "Inconnue";
                    }
                }
                
                
                echo "<tr>
                          <td class='cellulesimple'>" . $agent->sihamid()  . "</td>
                          <td class='cellulesimple'>" . $agent->nom() . "</td>
                          <td class='cellulesimple'>" . $agent->prenom() . "</td>
                          <td class='cellulesimple'>" . $nomstruct . "</td>
                          <td class='cellulesimple'>" . $nomdirection . "</td>
                          <td class='cellulesimple'>" . str_replace('.', ',', $nbjrsteletravail) . "</td>
                          <td class='cellulesimple'><center>" . str_replace('.', ',', $repartitionteletravail) . "</center></td>";
//                          <td class='cellulesimple'><center>" . str_replace('.', ',', $nbjrsteletravailannuel) . "</center></td>
//                echo "    <td class='cellulesimple'><center>" . round($nbjrsteletravail,0,PHP_ROUND_HALF_DOWN) . " x " . str_replace('.',',',$montant_teletravail) . " € = " . "</center></td>
//                          <td class='cellulesimple'>" . str_replace('.', ',', round($nbjrsteletravail,0,PHP_ROUND_HALF_DOWN)*str_replace(',','.',$montant_teletravail)) . " €</td>
                $infodisplay = "";
                $montantdisplay = 0;
                                
                foreach ($infoindemnite as $montant => $nbjrs)
                {
                    if ($infodisplay != "")
                    {
                        $infodisplay = $infodisplay . " + ";
                    }
                    $infodisplay =  $infodisplay . "$nbjrs x $montant €";
                    $montantdisplay = $montantdisplay + (round($nbjrs,0,PHP_ROUND_HALF_DOWN)*$montant);
                }
                echo "    <td class='cellulesimple'><center>" . str_replace('.', ',', $infodisplay) . "</center></td>
                          <td class='cellulesimple'>" . str_replace('.', ',', $montantdisplay) . " €</td>
                      </tr>";
            }
        }
        echo "</table>";
?>

<script>
	function teletravail_export_excel()
	{
		var fichiercontenu = '';
		var tableau = document.getElementById('table_teletravail');
		for (let numligne = 0 ; numligne < tableau.rows.length ; numligne++)
	    {
	        var ligne = tableau.rows[numligne];
	    	var lignefichier  = ''
	    	for (let numcellule = 0 ; numcellule < ligne.cells.length ; numcellule++)
	    	{
	    		var cellule = ligne.cells[numcellule];
	    		cellulevalue = cellule.innerText.replaceAll('€','').normalize("NFD").replace(/[\u0300-\u036f]/g, "") + ';';
	   			cellulevalue = cellulevalue.replace(/[\n\r]/g, ' / ');
				//lignefichier = lignefichier + cellule.innerText.replaceAll('€','').replaceAll('é','e').replaceAll('à','a') + ';';
				// On enlèves les caractères accentués
				//lignefichier = lignefichier + cellule.innerText.replaceAll('€','').normalize("NFD").replace(/[\u0300-\u036f]/g, "") + ';';
				lignefichier = lignefichier + cellulevalue;
	    	}
	    	fichiercontenu = fichiercontenu + lignefichier + '\n';
	    }

		var trimestre = document.getElementById('id_trimestre').innerText.replaceAll(' ', '_');
        var a = document.createElement('a');
        a.href = 'data:attachment/text,' + encodeURIComponent(fichiercontenu);
        a.target = '_blank';
        a.download = 'Télétravail_' + trimestre + '.csv';
        document.body.appendChild(a);
        a.click();

	}
</script>


<?php 
        echo "<br><button onclick='teletravail_export_excel()'>Export vers Excel</button>";
    }
?>
</body>
</html>