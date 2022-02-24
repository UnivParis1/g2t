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
    
    $periodeid = null;
    if (isset($_POST["periodeid"])) 
    {
        $periodeid = $_POST["periodeid"];
    }
    
    $date_debut = "";
    if (isset($_POST['date_debut']))
    {
        $date_debut = $_POST['date_debut'] . "";
    }
    $date_fin = "";
    if (isset($_POST['date_fin']))
    {
        $date_fin = $_POST['date_fin'] . "";
    }
    $cancel = array();
    if (isset($_POST['cancel']))
    {
        $cancel = $_POST['cancel'];
    }
    
    $extractfile = null;
    if (isset($_POST['extractbutton']))
    {
        $extractfile = $_POST['extractbutton'];
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
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    //echo "<br>" . print_r($_POST, true) . "<br>";
    echo "<br>";
    
    // Vérification des informations
    //echo "date_debut = XXX" . $date_debut ."XXX<br>";
    //echo "date_fin = XXX" . $date_fin ."XXX<br>";
    
    $msg_erreur = "";    
    
    // on a demandé l'extraction du fichier
    if (!is_null($extractfile))
    {
        $sql = "SELECT  DISTINCT  AGENT.AGENTID,AGENT.NOM, AGENT.PRENOM
                FROM AGENT,AFFECTATION
                WHERE AGENT.AGENTID = AFFECTATION.AGENTID
                  AND AFFECTATION.OBSOLETE = 'N'
                  AND AFFECTATION.DATEDEBUT<='" . $periodeid . $fonctions->debutperiode() . "'
                  AND AFFECTATION.DATEFIN>='" . ($periodeid+1) . $fonctions->finperiode() . "'
                  AND AFFECTATION.STRUCTUREID = 'DGHA_4'";
        //echo "SQL = $sql <br>";
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "")
        {
            $errlog = "Erreur lors du chargement des agents : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
        
        if (mysqli_num_rows($query) == 0)
        {
            echo "<br>Aucun agent ne correspond pas à la requète<br>";
        }
        else
        {
            $handle = fopen('./documents/extraction.csv',"w");
            $listeagent = array();
            while ($result = mysqli_fetch_row($query))
            {
                $listeagent[$result[0]] = $result[1] . " " . $result[2];
            }
            $periode = new periodeobligatoire($dbcon);
            $listeperiode = $periode->load($periodeid);
            foreach ($listeperiode as $element)
            {
                fwrite($handle,mb_convert_encoding("Période du " . $fonctions->formatdate($element['datedebut']) . " au " . $fonctions->formatdate($element['datefin']) ."\r\n","CP1252"));
                echo "<br><dd>Période du " . $fonctions->formatdate($element['datedebut']) . " au " . $fonctions->formatdate($element['datefin']) . "<br></dd>";
                // On parcours les agents
                foreach ($listeagent as $idagent => $identite)
                {
                    echo "L'agent " . $idagent . " ". $identite . " : ";
                    $agentnonconforme = false;
                    $planning  = new planning($dbcon);
                    $listeelement = $planning->load($idagent, $element['datedebut'], $element['datefin']);
                    foreach ($listeelement as $planelement)
                    {
                        // Si le type de l'élément est vide => il est présent
                        if ($planelement->type() == '')
                        {
                            $agentnonconforme = true;
                            break;
                        }
                        
                    }
                    if ($agentnonconforme)
                    {
                        fwrite($handle,mb_convert_encoding($idagent . ";" . $identite . ";" . "KO" . "\r\n","CP1252"));
                        echo "<B><font color='red'>KO sur la période du " . $fonctions->formatdate($element['datedebut']) . " au " . $fonctions->formatdate($element['datefin']) . "</font></B><br>";
                    }
                    else
                    {
                        fwrite($handle,mb_convert_encoding($idagent . ";" . $identite . ";" . "OK". "\r\n","CP1252"));
                        echo "<B><font color='green'>OK sur la période du " . $fonctions->formatdate($element['datedebut']) . " au " . $fonctions->formatdate($element['datefin']) . "</font></B><br>";
                    }
                    unset($listeelement);
                    unset($planning);
                    
                }
            }
            fclose($handle);
            //echo "<br><br>User = " . print_r($user, true) . "<br><br>";
            $user->sendmail($user,"Résultat de l'extraction", 
                "Bonjour,\r\nEn PJ le fichier généré pour l'extraction des agents sur la période du " . $periodeid . $fonctions->debutperiode() . " à " . ($periodeid+1) . $fonctions->finperiode() . ".",
                './documents/extraction.csv');
        }
        echo "<br><br>Fin du traitement.... <br><br>";
        
    }
    
    $datefausse = false;
    if (($date_fin=="") and ($date_debut==""))
    {
        $datefausse = true;
    }
    elseif (($date_debut=="") xor ($date_fin==""))
    {
        // On a une des deux dates mais pas les deux
        $erreur = 'La date de début ou la date de fin est vide....';
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    } 
    elseif ((!$fonctions->verifiedate($date_debut)) and ($date_debut!=""))
    {
        // La date de début n'est pas une date valide
        $erreur = "La date de début n'est pas une date valide....";
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    }
    elseif ((!$fonctions->verifiedate($date_fin)) and ($date_fin!=""))
    {
        // La date de fin n'est pas une date valide
        $erreur = "La date de fin n'est pas une date valide....";
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    }
    
    if (!$datefausse) 
    {
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        if ($datedebutdb > $datefindb) 
        {
            $erreur = "Il y a une incohérence entre la date de début et la date de fin !!! ";
            //echo "Erreur = $erreur";
            $msg_erreur .= $erreur . "<br/>";
            error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
            $datefausse = true;
        }
        elseif (!is_null($periodeid))
        {
            //echo "datedebutdb = $datedebutdb   debut période = " . ($periodeid . $fonctions->debutperiode()) . "<br>";
            //echo "datefindb = $datefindb   fin période = " . (($periodeid+1) . $fonctions->finperiode()) . "<br>";
            if ($datedebutdb<($periodeid . $fonctions->debutperiode()) or $datefindb>(($periodeid+1) . $fonctions->finperiode()))
            {
                $erreur = "La date de début ou la date de fin est en dehors de la période : " . $fonctions->formatdate($periodeid . $fonctions->debutperiode()) . "->" . $fonctions->formatdate(($periodeid+1) . $fonctions->finperiode()) . ".";
                //echo "Erreur = $erreur";
                $msg_erreur .= $erreur . "<br/>";
                error_log(basename(__FILE__) . " PeriodeId : " . $periodeid . " : " . $fonctions->stripAccents($erreur));
                $datefausse = true;
            }
        }
        
    }
    // Il reste à vérifier que la date de début et la date de fin sont bien dans la période 01/09/$periodeid -> 31/08/$periodeid
    if ($msg_erreur!="")
        echo "Erreur => " . $msg_erreur . "<br><br>";
    
    // S'il n'y a pas de problème de date et qu'on n'a pas demandé l'extraction du fichier (<=> $extractfile = null)
    if ($datefausse==false and is_null($extractfile))
    {
        // On sauvegarde la nouvelle période
        //echo "On va sauvegarder la valeur.....<br>";
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        $periode = new periodeobligatoire($dbcon);
        //echo "Periodeid = $periodeid <br>";
        $periode->load($periodeid);
        $periode->ajouterperiode($datedebutdb, $datefindb);
        $periode->store();
    }
    
    if (count($cancel)>0)
    {
        $periode = new periodeobligatoire($dbcon);
        $periode->load($periodeid);
        foreach ($cancel as $key => $valeur)
        {
            //echo "Key = $key <br>";
            $valeur = explode('-', $key);
            $periode->supprimerperiode($valeur[0],$valeur[1]);
        }
        $periode->store();
    }
    
    if (is_null($periodeid)) 
    {
        echo "<form name='selectperiode'  method='post' >";
        echo "<SELECT name='periodeid'>";
        $annee = $fonctions->anneeref();
        for ($cpt = 0 ; $cpt <=3 ; $cpt++ )
        {
            echo "<OPTION value='" . ($annee - $cpt) . "'>" . ($annee - $cpt) . "/" . ($annee - $cpt + 1)  . "</OPTION>";
        }
        echo "</SELECT>";
        echo "    ";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    } 
    else 
    {
        echo "La période sélectionnée est : $periodeid <br>";
        $periode = new periodeobligatoire($dbcon);
        $liste = $periode->load($periodeid);
        
        // On crée l'entete du tableau et on affiche chaque période enregistrée
        echo "<form name='selectperiode'  method='post' >";
        echo "<table class='tableausimple'>";
        echo "<tr><td class='cellulesimple'>Annee référence</td><td class='cellulesimple'>Date début</td><td class='cellulesimple'>Date fin</td><td class='cellulesimple'>Supprimer</td></tr>";
        if (count($liste)>0)
        {
            foreach($liste as $key => $dateelement)
            {
                echo "<tr><td class='cellulesimple'>" . $periodeid . "/" . ($periodeid+1) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datedebut"]) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datefin"]) . "</td><td class='cellulesimple'><center><input type='checkbox' name=cancel[" . $key . "] value='yes' /></center></td></tr>";
            }
        }
        echo "<tr><td class='cellulesimple'>" . $periodeid . "/" . ($periodeid+1) . "</td>";

        // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
        $calendrierid_deb = "date_debut";
        $calendrierid_fin = "date_fin";
        echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_deb . '" ).datepicker({minDate: $( "#' . $calendrierid_deb . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_deb . '").change(function () {
    			$("#' . $calendrierid_fin . '").datepicker("destroy");
    			$("#' . $calendrierid_fin . '").datepicker({minDate: $("#' . $calendrierid_deb . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
    	});
    });
    </script>
    ';
        echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_fin . '" ).datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_fin . '").change(function () {
    			$("#' . $calendrierid_deb . '").datepicker("destroy");
    			$("#' . $calendrierid_deb . '").datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin . '").datepicker("getDate")});
    	});
    });
    </script>
    ';
?>
		<td class='cellulesimple'><input class="calendrier" type=text name=date_debut
			id=<?php echo $calendrierid_deb ?> size=10
			minperiode='<?php echo $fonctions->formatdate($periodeid . $fonctions->debutperiode()); ?>'
			maxperiode='<?php echo $fonctions->formatdate($periodeid+1 . $fonctions->finperiode()); ?>'></td>
		<td class='cellulesimple'><input class="calendrier" type=text name=date_fin
			id=<?php echo $calendrierid_fin ?> size=10
			minperiode='<?php echo $fonctions->formatdate($periodeid . $fonctions->debutperiode()); ?>'
			maxperiode='<?php echo $fonctions->formatdate($periodeid+1 . $fonctions->finperiode()); ?>'></td>
            
<?php             
        echo "<td class='cellulesimple'></td></tr>";
        echo "</table>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='periodeid' value='" . $periodeid . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
        
/*      Bouton pour générer le fichier de synthèse.... => Masqué pour le moment et requête d'extraction à reprendre.  
        echo "<br><br><br>";
        echo "<form name='extractfile'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='periodeid' value='" . $periodeid . "'>";
        echo "<input type='submit' name='extractbutton' value='Extraction' >";
        echo "</form>";
        
*/        
            
    }
?>
</body>
</html>
        