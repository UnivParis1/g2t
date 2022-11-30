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
    
    $user = new agent($dbcon);
    $user->load($userid);
    
    $current_tab = 'tab_1';
    if (isset($_POST['current_tab']))
    {
        $current_tab = $_POST['current_tab'];
    }
    
    require ("includes/menu.php");
 
    //echo "<br>" . print_r($_POST,true) . "<br>";
    echo "<br>";
    
?>
    <form name='form_parametrage' id='form_parametrage' method='post' >

    <div class="tabs">
        <span <?php if ($current_tab == 'tab_1') echo " class='tab_active' "; ?> data-tab-value="#tab_1">Congés</span>
        <span <?php if ($current_tab == 'tab_2') echo " class='tab_active' "; ?> data-tab-value="#tab_2">CET</span>
        <span <?php if ($current_tab == 'tab_3') echo " class='tab_active' "; ?> data-tab-value="#tab_3">Télétravail</span>
    </div>
  
    <div class="tab-content">
<!--         
        ########################################################
        #                                                      #
        # ICI COMMENCE LE CONTENU ET LA GESTION DU 1ER ONGLET  #
        #                                                      #
        ########################################################
-->        

        <div class="tabs__tab <?php if ($current_tab == 'tab_1') echo " active "; ?>" id="tab_1" data-tab-info>
<?php 
    $msg_erreur = "";
    $periodeid = $fonctions->anneeref();
    $datefausse = false;
    
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
    
    if (($date_fin=="") and ($date_debut==""))
    {
        if (isset($_POST['valid_periode']) and count($cancel)==0) // Si on a posté des dates vides et que c'est pas une annulation => il y a un problème
        {
            $erreur = 'La date de début et la date de fin sont vides.';
            $msg_erreur .= $erreur . "<br/>";
            error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
        }
        $datefausse = true;
    }
    elseif (($date_debut=="") xor ($date_fin==""))
    {
        // On a une des deux dates mais pas les deux
        $erreur = 'La date de début ou la date de fin est vide.';
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    }
    elseif ((!$fonctions->verifiedate($date_debut)) and ($date_debut!=""))
    {
        // La date de début n'est pas une date valide
        $erreur = "La date de début n'est pas une date valide.";
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    }
    elseif ((!$fonctions->verifiedate($date_fin)) and ($date_fin!=""))
    {
        // La date de fin n'est pas une date valide
        $erreur = "La date de fin n'est pas une date valide.";
        //echo "Erreur = $erreur";
        $msg_erreur .= $erreur . "<br/>";
        error_log(basename(__FILE__) . " PeriodeId : inconnue : " . $fonctions->stripAccents($erreur));
        $datefausse = true;
    }
    
    if (!$datefausse)
    {
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        $periodeid = $fonctions->anneeref($fonctions->formatdate($date_debut));
        if ($datedebutdb > $datefindb)
        {
            $erreur = "Il y a une incohérence entre la date de début et la date de fin.";
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
    
    // S'il n'y a pas de problème de date
    if ($datefausse==false)
    {
        // On sauvegarde la nouvelle période
        //echo "On va sauvegarder la valeur.....<br>";
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        $periodeid = $fonctions->anneeref($fonctions->formatdate($date_debut));
        $periode = new periodeobligatoire($dbcon);
        //echo "Periodeid = $periodeid <br>";
        $periode->load($periodeid);
        $periode->ajouterperiode($datedebutdb, $datefindb);
        $periode->store();
    }
    
    if (count($cancel)>0)
    {
        foreach ($cancel as $key => $valeur)
        {
            $valeur = explode('-', $key);
            //echo "Key = $key <br>";
            $elementanneeref = $fonctions->anneeref($fonctions->formatdate($valeur[0]));
            $periode = new periodeobligatoire($dbcon);
            $periode->load($elementanneeref);
            $periode->supprimerperiode($valeur[0],$valeur[1]);
        }
        $periode->store();
    }
    
    /////////////////////////////////////////////
    // Mise à jour du nombre de jours de congés
    if (isset($_POST['valid_nbjours']))
    {
        $anneeconge = trim($_POST['anneeconge']);
        $nbjoursannuel = trim($_POST['nbjoursannuel']);
        //echo "<br>anneeconge = $anneeconge    nbjoursannuel = $nbjoursannuel <br>";
        if (!is_numeric($nbjoursannuel) || !is_int($nbjoursannuel+0) || $nbjoursannuel < 0)
        {
            $msg_erreur = $msg_erreur . "Le nombre de jours de congés annuels doit être un entier positif. <br>";
        }
        else
        {
            $constantename = "NBJOURS" . $anneeconge;
            $msg_erreur = $fonctions->enregistredbconstante($constantename, $nbjoursannuel);

/*            
            if (!$fonctions->testexistdbconstante($constantename))
            {
                $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES('$constantename','$nbjoursannuel')";
            }
            else
            {
                $sql = "UPDATE CONSTANTES SET VALEUR = '$nbjoursannuel' WHERE NOM = '$constantename'";
                
            }
            //var_dump($sql);
            mysqli_query($dbcon, $sql);
            $msg_erreur = mysqli_error($dbcon);
*/            
       }
    }
    
    /////////////////////////////////////
    // Synchronisation des jours fériés
    if (isset($_POST['valid_synchroferies']))
    {
        $tabannees = array();
        if (isset($_POST['anneesynchro']))
        {
            $tabannees = $_POST['anneesynchro'];
        }
        $tabferies = array();
        $return = $fonctions->synchronisationjoursferies($tabannees,$tabferies);
        if ($return != '')
        {
            $erreur = "L'intégration des jours fériés a échoué : " . $return . ".";
            //echo "Erreur = $erreur";
            $msg_erreur .= $erreur . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
        }
        else
        {
            // Tout s'est bien passé.
            // var_dump($tabferies);
        }
    }
    
    
    if ($msg_erreur!="")
    {
        //echo "Erreur => " . $msg_erreur . "<br><br>";
        echo $fonctions->showmessage(fonctions::MSGERROR, "$msg_erreur");
    }
    else
    {
        if (count($cancel)>0)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont supprimées.");
        }
        if (isset($_POST['valid_periode']) or isset($_POST['valid_nbjours']))
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont enregistrées.");
        }
        if (isset($_POST['valid_synchroferies']))
        {
            $stringannee = "";
            $separateur = "";
            foreach($tabannees as $key => $annee)
            {
                if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; elseif (strlen($stringannee)>0) $separateur = ', ';
                //if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; else $separateur = ', ';
                $stringannee = $stringannee . $separateur . $annee . '/' . ($annee+1);
            }
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les jours fériés pour " . trim($stringannee) . " ont été inportés.");
        }
    }
    
    /////////////////////////////////////////////////////////////
    // Affichage des périodes de fermeture de l'établissement
    $anneeref = $fonctions->anneeref();
    $nbanneeaffichee = 3;
    $liste=array();
    for ($index=$nbanneeaffichee; $index>=0; $index--)
    {
        $periode = new periodeobligatoire($dbcon);
        $liste = array_merge($periode->load($anneeref-$index),$liste);
    }
    ksort($liste);
    //var_dump ($liste);
        
        
    // On crée l'entete du tableau et on affiche chaque période enregistrée
    echo "<form name='selectperiode'  method='post' >";
    echo "<br>Période de fermeture de l'établissement : <br>";
    echo "<table class='tableausimple'>";
    echo "<tr><td class='titresimple'>Annee référence</td><td class='titresimple'>Date début</td><td class='titresimple'>Date fin</td><td class='titresimple'>Supprimer</td></tr>";
    if (count($liste)>0)
    {
        foreach($liste as $key => $dateelement)
        {
            $elementanneeref = $fonctions->anneeref($fonctions->formatdate($dateelement["datedebut"]));
            echo "<tr><td class='cellulesimple'>" . $elementanneeref . "/" . ($elementanneeref+1) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datedebut"]) . "</td><td class='cellulesimple'>" . $fonctions->formatdate($dateelement["datefin"]) . "</td><td class='cellulesimple'><center><input type='checkbox' name=cancel[" . $key . "] value='yes' /></center></td></tr>";
        }
    }
    echo "<tr><td class='cellulesimple'>Nouvelle période : </td>";
    
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
    			minperiode='<?php echo $fonctions->formatdate(($anneeref-$nbanneeaffichee+1) . $fonctions->debutperiode()); ?>'
    			maxperiode='<?php echo $fonctions->formatdate(($anneeref+1) . $fonctions->finperiode()); ?>'></td>
    		<td class='cellulesimple'><input class="calendrier" type=text name=date_fin
    			id=<?php echo $calendrierid_fin ?> size=10
    			minperiode='<?php echo $fonctions->formatdate(($anneeref-$nbanneeaffichee+1)); ?>'
    			maxperiode='<?php echo $fonctions->formatdate(($anneeref+1) . $fonctions->finperiode()); ?>'></td>
            
<?php             
    echo "<td class='cellulesimple'></td></tr>";
    echo "</table>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_1'>";
    echo "<br>";
    echo "<input type='submit' name='valid_periode' value='Soumettre' >";
    echo "</form>";
    
    /////////////////////////////////////////////////////////
    // Affichage de la gestion du nombre de jours de congés
    echo "<br><br>";
    $nbanneeaafficher = 2;
    $anneeref = $fonctions->anneeref();
    echo "<form name='nbjoursform'  method='post' >";
    echo "Nombre de jours de congés annuels : <br>";
    echo "<table>";
    echo "<tr><td><select name='anneeconge' id='anneeconge'>";
    for ($index=0; $index<$nbanneeaafficher; $index++)
    {
        echo "<option value='" . ($anneeref+$index)  ."'>" . ($anneeref+$index) . "/" . ($anneeref+$index+1) . "</option>";
    }
    echo "</td>";
    echo "<td><input type=text id='nbjoursannuel' name='nbjoursannuel' value='' maxlength='3' size='4'></td>";
    echo "</tr>";
    echo "</table>";
    for ($index=0; $index<$nbanneeaafficher; $index++)
    {
        if ($fonctions->testexistdbconstante('NBJOURS' . $anneeref+$index))
        {
            $valeurconstante = $fonctions->liredbconstante('NBJOURS' . $anneeref+$index);
            echo "Le nombre de jours de congés pour " . ($anneeref+$index) . "/" . ($anneeref+$index+1) . " est de : $valeurconstante <br>";
        }
    }
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_1'>";
    echo "<br>";
    echo "<input type='submit' name='valid_nbjours' value='Soumettre' >";
    echo "</form>";

    ///////////////////////////////////////////////////////////////
    // Affichage de la synchronisation des jours de congés
    echo "<br><br>";
    $anneeref = $fonctions->anneeref();
    $nbanneeaimporter = 2;
    for ($index = 0 ; $index<$nbanneeaimporter ; $index++)
    {
        $tabannees[$index] = $anneeref+$index;
    }
    //$tabannees = array($fonctions->anneeref(), $fonctions->anneeref()+1, $fonctions->anneeref()+2);
    echo "<form name='synchroferiesform'  method='post' >";
    $stringannee = "";
    $separateur = "";
    foreach($tabannees as $key => $annee)
    {
        if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; elseif (strlen($stringannee)>0) $separateur = ', ';
        $stringannee = $stringannee . $separateur . $annee . '/' . ($annee+1);
        echo "<input type='hidden' name='anneesynchro[$key]' value='" . $annee . "'>";
    }
    echo "Intégration des jours fériés dans G2T (" . $stringannee . ") : <br>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_1'>";
    echo "<br>";
    echo "<input type='submit' name='valid_synchroferies' value='Soumettre' >";
    echo "</form>";
    
?>
        </div>
        
<!--         
        ########################################################
        #                                                      #
        # ICI COMMENCE LE CONTENU ET LA GESTION DU 2E ONGLET   #
        #                                                      #
        ########################################################
-->        
        <div class="tabs__tab <?php if ($current_tab == 'tab_2') echo " active "; ?>" id="tab_2" data-tab-info>
<?php 
    $msgerror = '';
    
    // PARAMETRAGE DU CALENDRIER D'ALIMENTATION
    //if (isset($_POST['valider_cal_alim']))
    $datecampagnealimupdate = false;
    if (isset($_POST['date_debut_alim']) and isset($_POST['date_fin_alim']))
    {
        if ($fonctions->verifiedate($_POST['date_debut_alim']) and $fonctions->verifiedate($_POST['date_fin_alim']))
        {
            $datedebutalim = $fonctions->formatdatedb($_POST['date_debut_alim']);
            $datefinalim = $fonctions->formatdatedb($_POST['date_fin_alim']);
            if ($datefinalim < $datedebutalim)
            {
                $msgerror = $msgerror . "Alimentation CET : Il y a une incohérence dans les dates (date début > date fin). <br>";
                //echo "Incohérence dates (date début > date fin). <br>";
            }
            else
            {
                $fonctions->debutalimcet($datedebutalim);
                $fonctions->finalimcet($datefinalim);
                $datecampagnealimupdate = true;
            }
        }
        else
        {
            $msgerror = $msgerror . "Au moins une des dates de l'intervalle d'alimentation n'est pas valide. <br>";
            //echo "Au moins une des dates de l'intervalle d'alimentation n'est pas valide. <br>";
        }
    }

    // PARAMETRAGE DU CALENDRIER DE DROIT D'OPTION
    //if (isset($_POST['valider_cal_option']))
    $datecampagneoptionupdate = false;
    if (isset($_POST['date_debut_option']) and isset($_POST['date_fin_option']))
    {
        if ($fonctions->verifiedate($_POST['date_debut_option']) and $fonctions->verifiedate($_POST['date_fin_option']))
        {
            $datedebutopt = $fonctions->formatdatedb($_POST['date_debut_option']);
            $datefinopt = $fonctions->formatdatedb($_POST['date_fin_option']);
            if ($datefinopt < $datedebutopt)
            {
                $msgerror = $msgerror . "Droit d'option CET : Il y a une incohérence dans les dates (date début > date fin). <br>";
                //echo "Incohérence dates (date début > date fin). <br>";
            }
            else
            {
                $fonctions->debutoptioncet($datedebutopt);
                $fonctions->finoptioncet($datefinopt);
                $datecampagneoptionupdate = true;
            }
        }
        else
        {
            $msgerror = $msgerror . "Au moins une des dates de l'intervalle d'option n'est pas valide. <br>";
            //echo "Au moins une des dates de l'intervalle d'option n'est pas valide. <br>";
        }
    }

    //if (isset($_POST['valider_param_plafond']))
    $plafondupdate = false;
    if (isset($_POST['plafondcet']))
    {
        $constantename = 'PLAFONDCET';
        $plafondcet = $_POST['plafondcet'];
        if (!is_numeric($plafondcet) || !is_int($plafondcet+0) || $plafondcet < 0)
        {
            $msgerror = $msgerror . "Le nombre de jours maximum doit être un entier positif. <br>";
            //echo "Le nombre de jours maximum doit être un entier positif. <br>";
        }
        else
        {
            $erreur = $fonctions->enregistredbconstante($constantename, $plafondcet);
/*            
            if (!$fonctions->testexistdbconstante($constantename))
            {
                $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES('$constantename','$plafondcet')";
            }
            else
            {
                $sql = "UPDATE CONSTANTES SET VALEUR = '$plafondcet' WHERE NOM = '$constantename'";
                
            }
            //var_dump($sql);
            mysqli_query($dbcon, $sql);
            $erreur = mysqli_error($dbcon);
*/            
            if (strlen($erreur)>0)
            {
                if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                $msgerror = $msgerror . $erreur;
            }
            else
            {
                $plafondupdate = true;
            }
        }
    }
    
    $supprok = false;
    $signataireupdate = false;
    if (isset($_POST['valider_signataire_cet']))
    {
        $msgerror = "";
        $constantename = 'CETSIGNATAIRE';
        if (isset($_POST['supprsignataire']))
        {
            $tabsuppr = $_POST['supprsignataire'];
            $signataireliste = '';
            if ($fonctions->testexistdbconstante($constantename))
            {
                $signataireliste = $fonctions->liredbconstante($constantename);
            }
            $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
            foreach($tabsuppr as $niveau => $infos)
            {
                foreach($infos as $key => $valeur)
                {
                    //var_dump($niveau);
                    //var_dump($key);
                    unset($tabsignataire[$niveau][$key]);
                }
            }
            $stringsignataire = $fonctions->cetsignatairetostring($tabsignataire);
            $erreur = $fonctions->enregistredbconstante($constantename, $stringsignataire);
/*            
            if (!$fonctions->testexistdbconstante($constantename))
            {
                $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES('$constantename','$stringsignataire')";
            }
            else
            {
                $sql = "UPDATE CONSTANTES SET VALEUR = '$stringsignataire' WHERE NOM = '$constantename'";
                
            }
            //var_dump($sql);
            mysqli_query($dbcon, $sql);
            $erreur = mysqli_error($dbcon);
*/            
            if (strlen($erreur)>0)
            {
                if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                $msgerror = $msgerror . $erreur;
            }
            else
            {
                $supprok = true;
            }
        }
        
        $newlevelsignataire = '';
        $newtypesignataire = '';
        $newidsignataire = '';
        $structureid='';
        if (isset($_POST['newlevelsignataire']))
        {
            $newlevelsignataire = trim($_POST['newlevelsignataire']);
        }
        if (isset($_POST['newtypesignataire']))
        {
            $newtypesignataire = trim($_POST['newtypesignataire']);
        }
        if (isset($_POST['newidsignataire']) and $newtypesignataire==cet::SIGNATAIRE_AGENT)
        {
            $newidsignataire = trim($_POST['newidsignataire']);
        }
        if (isset($_POST['structureid']) and ($newtypesignataire==cet::SIGNATAIRE_STRUCTURE or $newtypesignataire==cet::SIGNATAIRE_RESPONSABLE))
        {
            $structureid = trim($_POST['structureid']);
        }
        
        //var_dump($newlevelsignataire);
        //var_dump($newtypesignataire);
        //var_dump($newidsignataire);
        
        if ($newidsignataire == '' and $structureid == '' and !isset($_POST['supprsignataire']))
        {
            // On n'a pas les infos nécessaires => Error
            if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
            $stringerror = "Vous avez sélectionné le type de signataire " . cet::SIGNATAIRE_LIBELLE[$newtypesignataire] . " mais vous n'avez pas saisi ";
            if ($newtypesignataire==cet::SIGNATAIRE_AGENT)
            {
                $stringerror = $stringerror . " d'agent.";
            }
            else
            {
                $stringerror = $stringerror . "de structure.";
            }
            $msgerror = $msgerror . $stringerror;
        }
        elseif ($newidsignataire != '' or $structureid !='')
        {
            $constantename = 'CETSIGNATAIRE';
            $signataireliste = '';
            if ($fonctions->testexistdbconstante($constantename))
            {
                $signataireliste = $fonctions->liredbconstante($constantename);
            }
            $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
            //var_dump($tabsignataire);
            if ($newtypesignataire == cet::SIGNATAIRE_AGENT)
            {
                $tabsignataire = $fonctions->cetsignataireaddtoarray($newlevelsignataire,$newtypesignataire,$newidsignataire,$tabsignataire);
            }
            else
            {
                $tabsignataire = $fonctions->cetsignataireaddtoarray($newlevelsignataire,$newtypesignataire,$structureid,$tabsignataire);
            }
            //var_dump($tabsignataire);
            $stringsignataire = $fonctions->cetsignatairetostring($tabsignataire);
            //var_dump($stringsignataire);

            $erreur = $fonctions->enregistredbconstante($constantename, $stringsignataire);
/*            
            if (!$fonctions->testexistdbconstante($constantename))
            {
                $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES('$constantename','$stringsignataire')";
            }
            else
            {
                $sql = "UPDATE CONSTANTES SET VALEUR = '$stringsignataire' WHERE NOM = '$constantename'";
                
            }
            //var_dump($sql);
            mysqli_query($dbcon, $sql);
            $erreur = mysqli_error($dbcon);
*/            
            if (strlen($erreur)>0)
            {
                if (strlen($msgerror)>0) $msgerror = $msgerror . "<br>";
                $msgerror = $msgerror . $erreur;
            }
            else
            {
                $signataireupdate = true;
            }
        }
    }
    
    if ($msgerror != '')
    {
        echo $fonctions->showmessage(fonctions::MSGERROR, $msgerror);
    }
    if ($plafondupdate or $datecampagneoptionupdate or $datecampagnealimupdate or $signataireupdate )
    {
        echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont enregistrées");
    }
    if ($supprok)
    {
        echo $fonctions->showmessage(fonctions::MSGINFO, "Les données ont été supprimées");
    }

    $msgerror = '';
    
    $constantename = 'CETSIGNATAIRE';
    $signataireliste = '';
    $tabsignataire = array();
    if ($fonctions->testexistdbconstante($constantename))
    {
        $signataireliste = $fonctions->liredbconstante($constantename);
    }
    $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
    $disablebuttonsubmit = "";
    if (!isset($tabsignataire[3]) or !isset($tabsignataire[4]) or !isset($tabsignataire[5]))
    {
        echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible de modifier les dates de campagnes CET <br> car tous les niveaux du circuit ne sont pas définis.");
        $disablebuttonsubmit = " disabled ";
    }
    
    
    
    $constantename = 'PLAFONDCET';
    $plafondparam = 0;
    if ($fonctions->testexistdbconstante($constantename)) $plafondparam = $fonctions->liredbconstante($constantename);

?>
            <form name="frm_param_cet" method="post">
            
                <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
                	<br>Paramétrage du calendrier de la campagne d'alimentation du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutalimcet()).' - '.$fonctions->formatdate($fonctions->finalimcet());?>)
                	<table>
        	        	<tr>
        		       		<td style='padding-left: 30px;'>Date d'ouverture de la campagne d'alimentation :</td>

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
                				id=<?php echo $calendrierid_deb_alim ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->debutalimcet()) ?>'></td>
        	    		</tr>
            			<tr>
            				<td style='padding-left: 30px;'>Date de fermeture de la campagne d'alimentation :</td>
                			<td width=1px><input class="calendrier" type=text name=date_fin_alim
                				id=<?php echo $calendrierid_fin_alim ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->finalimcet()) ?>'></td>
        	    		</tr>
            		</table>

<!--    AFFICHAGE DU PARAMETRAGE DU DROIT D'OPTION -->
        			<br><br>
        	        <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
                	<br>Paramétrage du calendrier de la campagne de droit d'option du CET (dates actuelles : <?php echo $fonctions->formatdate($fonctions->debutoptioncet()).' - '.$fonctions->formatdate($fonctions->finoptioncet());?>)
                	<table>
                		<tr>
                			<td style='padding-left: 30px;'>Date d'ouverture de la campagne de droit d'option :</td>
                		
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
                				id=<?php echo $calendrierid_deb_option ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->debutoptioncet()) ?>'></td>
        	    		</tr>
        	    		<tr>
            				<td style='padding-left: 30px;'>Date de fermeture de la campagne de droit d'option :</td>
            				<td width=1px><input class="calendrier" type=text name=date_fin_option
            					id=<?php echo $calendrierid_fin_option ?> size=10 value='<?php echo $fonctions->formatdate($fonctions->finoptioncet()) ?>'></td>
        	    		</tr>
        	    	</table>
        	 		<br><br>
            		Nombre de jours maximum sur CET : <input type='text' name='plafondcet' value='<?php echo $plafondparam;?>'>
            		<br><br>
                    <input type='hidden' id='current_tab' name='current_tab' value='tab_2'>
                    <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
            		<input type='submit' name='valider_param_cet' id='valider_param_cet' value='Soumettre' <?php echo $disablebuttonsubmit;?>/>
            </form>
            <br><br>
            <form name="frm_param_cet" method="post">
            
            <table class='tableausimple' id='listeindemnite'>
            	<tr><center>
            		<td class='titresimple'>Niveau du signataire</td>
                    <td class='titresimple'>Type de signataire</td>
                    <td class='titresimple'>Signataire</td>
                    <td class='titresimple'>Supprimer</td>
            	</center></tr>
            	<tr><center>
            		<td class='cellulesimple'><center>Niveau 1</center></td>
            		<td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[1]; ?></center></td>
            		<td class='cellulesimple'><center>Agent demandeur</center></td>
            		<td class='cellulesimple'><center></center></td>
            	</center></tr>
            	<tr><center>
            		<td class='cellulesimple'><center>Niveau 2</center></td>
            		<td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[3]; ?></center></td>
            		<td class='cellulesimple'><center>Structure de l'agent</center></td>
            		<td class='cellulesimple'><center></center></td>
            	</center></tr>
<?php 
                $constantename = 'CETSIGNATAIRE';
                $signataireliste = '';
                $tabsignataire = array();
                if ($fonctions->testexistdbconstante($constantename))
                {
                    $signataireliste = $fonctions->liredbconstante($constantename);
                }

                $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
                if (!isset($tabsignataire['3']['1_-2']))
                {
                    $tabsignataire = $fonctions->cetsignataireaddtoarray('3', cet::SIGNATAIRE_AGENT, "-2", $tabsignataire);
                    $signataireliste = $fonctions->cetsignatairetostring($tabsignataire);
                    $saveerror = $fonctions->enregistredbconstante($constantename, $signataireliste);
                    if ($saveerror != '')
                    {
                        $fonctions->showmessage(fonctions::MSGERROR,$saveerror);
                    }
                    $signataireliste = $fonctions->liredbconstante($constantename);
                    $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
                    //var_dump($tabsignataire);
                }

                if (count($tabsignataire)>0)
                {
                    foreach ($tabsignataire as $niveau => $infosignataires)
                    {
                        foreach ($infosignataires as $idsignataire => $infosignataire)
                        {
?>            	
				<tr>
                    <td class='cellulesimple'><center>Niveau <?php echo $niveau; ?></center></td>
                    <td class='cellulesimple'><center><?php echo cet::SIGNATAIRE_LIBELLE[$infosignataire[0]]; ?></center></td>
<?php               
                    if ($infosignataire[0]==cet::SIGNATAIRE_AGENT)
                    {
?>                    
                    <td class='cellulesimple'><center><?php $agent = new agent($dbcon); $agent->load($infosignataire[1]); echo $agent->identitecomplete() ?></center></td>
<?php 
                    }
                    else
                    {
?>
                    <td class='cellulesimple'><center><?php $struct = new structure($dbcon); $struct->load($infosignataire[1]); echo $struct->nomlong() . " (" . $struct->nomcourt() . ")"; ?></center></td>
<?php 
                    }
                    $disablecheckbox = "";
                    if ($infosignataire[1]=='-2') // On ne peut pas supprimer "Gestion de Temps"
                    {
                        $disablecheckbox = ' disabled ';
                    }
?>
                    <td class='cellulesimple'><center><input type='checkbox' <?php echo $disablecheckbox; ?> id='supprsignataire[<?php echo $niveau; ?>][<?php echo $idsignataire; ?>]' name='supprsignataire[<?php echo $niveau; ?>][<?php echo $idsignataire; ?>]'</center></td>
            	</tr>
<?php
                        }
                    }
                }
?>
				<tr>
                    <td class='cellulesimple'><center>
                        <select name="newlevelsignataire" id="newlevelsignataire">
                            <option value="3">Niveau 3</option>
                            <option value="4">Niveau 4</option>
                            <option value="5">Niveau 5</option>
                        </select>
                    </center></td>
                    <td class='cellulesimple'><center>
                        <select name="newtypesignataire" id="newtypesignataire">
                            <option value="<?php echo cet::SIGNATAIRE_AGENT; ?>"><?php echo cet::SIGNATAIRE_LIBELLE[cet::SIGNATAIRE_AGENT]; ?></option>
                            <option value="<?php echo cet::SIGNATAIRE_STRUCTURE; ?>"><?php echo cet::SIGNATAIRE_LIBELLE[cet::SIGNATAIRE_STRUCTURE]; ?></option>
                            <option value="<?php echo cet::SIGNATAIRE_RESPONSABLE; ?>"><?php echo cet::SIGNATAIRE_LIBELLE[cet::SIGNATAIRE_RESPONSABLE]; ?></option>
                        </select>
                    </center></td>
                    <td class='cellulesimple'><center>
                    	<input id="user" name="user" placeholder="Nom et/ou prenom" autofocus/>
                    	<input type='hidden' id="newidsignataire" name="newidsignataire" class='user' />
                    	<script>
                    	    //var input_elt = $( ".token-autocomplete input" );
                      	    $( "#user" ).autocompleteUser(
                        	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "supannEmpId",
                      	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee|staff" } });
                    	</script>
<!--                     <input type='text' id='newidsignataire' name='newidsignataire' value=''>   -->
                    </center>
                	<div id='div_structureid' hidden>  <!-- style=' width: 300px;' hidden>  -->
					<select size='1' id='structureid' name='structureid' style=' width: 700px;' value=''>
					<option value=''>----- Veuillez sélectionner la structure -----</option>
<?php
$sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
//$sql = "SELECT STRUCTUREID,NOMLONG,NOMCOURT FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "") {
            $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
        $structureid=null;
        while ($result = mysqli_fetch_row($query)) 
        {
            $struct = new structure($dbcon);
            $struct->load($result[0]);
            affichestructureliste($struct, 0);
//              echo "<option value='$result[0]'>$result[1] ($result[2])</option>";
        }
?>
					</select>
                	</div>
                    </td>
                    <td class='cellulesimple'><center></center></td>
            	</tr>
            </table>
            <script>
                var selectElement = document.getElementById('newtypesignataire');
                //alert('Plouf' + selectElement.value);
                selectElement.addEventListener('change', (event) => {
                  var idsignataire = document.getElementById('user');
                  var td_structureid = document.getElementById('td_structureid');
                  //alert('valeur de target = ' + event.target.value);
                  if (event.target.value!=1)
                  {
                     //alert ('Différent de 1');
                     idsignataire.type='hidden';
                     div_structureid.removeAttribute("hidden");
                     
                  }
                  else
                  {
                     //alert ('Egal à 1');
                     idsignataire.type='text';
                     div_structureid.setAttribute("hidden", "hidden");
                  }
                });   
            </script>
    		<br><br>
            <input type='hidden' id='current_tab' name='current_tab' value='tab_2'>
            <input type='hidden' name='userid' value='<?php echo $user->agentid();?>'>
    		<input type='submit' name='valider_signataire_cet' id='valider_signataire_cet' value='Soumettre' />
            
            </form>

        </div>

<!--         
        ########################################################
        #                                                      #
        # ICI COMMENCE LE CONTENU ET LA GESTION DU 3E ONGLET   #
        #                                                      #
        ########################################################
-->        
        <div class="tabs__tab <?php if ($current_tab == 'tab_3') echo " active "; ?>" id="tab_3" data-tab-info>
<?php
    $erreur = "";
    if (isset($_POST['modification']))
    {
        $modifdate = false;
        $suppression = false;
        $tabdebut = array();
        if (isset($_POST["date_debut_indem"])) $tabdebut = $_POST["date_debut_indem"];
        $tabfin = array();
        if (isset($_POST["date_fin_indem"])) $tabfin = $_POST["date_fin_indem"];
        $tabmontant = array();
        if (isset($_POST["montant"])) $tabmontant = $_POST["montant"];
        
        foreach ($tabdebut as $key => $indemdebutsaisie)
        {
            $indemfinsaisie = $tabfin[$key];
            $infos = explode("_",$key);
            $indemdebutkey = $infos[0];
            $indemfinkey = $infos[1];
            if (($fonctions->formatdatedb($indemdebutsaisie)<$fonctions->formatdatedb($indemdebutkey)) or ($fonctions->formatdatedb($indemfinsaisie)>$fonctions->formatdatedb($indemfinkey)))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Il n'est pas possible d'avancer la date de début ou de repousser la date de fin d'une indemnité.";!
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif (($fonctions->formatdatedb($indemdebutsaisie)!=$fonctions->formatdatedb($indemdebutkey)) or ($fonctions->formatdatedb($indemfinsaisie)!=$fonctions->formatdatedb($indemfinkey)))
            {
                if ($fonctions->formatdatedb($indemdebutsaisie)>$fonctions->formatdatedb($indemfinsaisie))
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "La date de début est supérieure à la date de fin de l'indemnité.";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
                else
                {
                    $modifdate = true;
                }
            }
                
        }
        
        if (strlen($erreur)==0)
        {
            $datastring = "";
            foreach ($tabdebut as $key => $indemdebutsaisie)
            {
                $indemfinsaisie = $tabfin[$key];
                $montant = $tabmontant[$key];
                if (strlen($datastring)>0) $datastring = $datastring . ";";
                $datastring = $datastring . $fonctions->formatdatedb($indemdebutsaisie) . '|' . $fonctions->formatdatedb($indemfinsaisie) . '|' . floatval(str_replace(',','.',$montant));
            }
            $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
            $query = mysqli_query($dbcon, $update);
        }
        
        
        $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
        $cancelarray = array();
        if (isset($_POST["cancelindem"]) and strlen($erreur)==0)
        {
            $cancelarray = $_POST["cancelindem"];
            // On va réorganiser les indemnités par date de début pour quelles soit dans l'ordre chronologique
            $newtabindem = array();
            foreach ($tabindem as $indem)
            {
                $newtabindem[$fonctions->formatdatedb($indem['datedebut']) . "_" . $fonctions->formatdatedb($indem['datefin']) . "_" . str_replace(',','.',$indem['montant'])] = $indem;
            }
            unset ($tabindem);
            $tabindem = $newtabindem;
            foreach($cancelarray as $keyvalue)
            {
                if (isset($tabindem[$keyvalue]))
                {
                    unset($tabindem[$keyvalue]);
                    $suppression = true;
                }
                else
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "Vous ne pouvez pas supprimer une indemnité que vous venez de modifier.";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
            }
            $datastring = "";
            foreach ($tabindem as $indem)
            {
                if (strlen($datastring)>0) $datastring = $datastring . ";";
                $datastring = $datastring . $indem['datedebut'] . '|' . $indem['datefin'] . '|' . str_replace('.',',',$indem['montant']);
            }
            $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
            $query = mysqli_query($dbcon, $update);
        }
        elseif (!isset($_POST["cancelindem"]) and !$modifdate and strlen($erreur)==0)
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Aucune indemnité n'est selectionnée pour suppression.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
        }
        
        if ($erreur != '')
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
        }
        if ($modifdate)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont modifiées.");
        }
        if ($suppression)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont supprimées.");
        }
    }
    
    if (isset($_POST['creation_indem']))
    {
        $datedebutindem = trim($_POST['date_debut_newindem']['newindem']);
        $datefinindem = trim($_POST['date_fin_newindem']['newindem']);
        $montantindem = trim(str_replace(',','.',$_POST['montantnew']));
        
        $dateok = true;
        if (!$fonctions->verifiedate($datedebutindem))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début de l'indemnité n'est pas correcte ou définie.";
            $dateok = false;
        }
        if (!$fonctions->verifiedate($datefinindem))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de fin de l'indemnité n'est pas correcte ou définie.";
            $dateok = false;
        }
        if ($dateok and $fonctions->formatdatedb($datedebutindem)>$fonctions->formatdatedb($datefinindem))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début est supérieure à la date de fin de l'indemnité.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $dateok = false;
        }
        if ($dateok)
        {
            // On va vérifier que les conventions ne se chevauchent pas
            $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
            $datedebutindem = $fonctions->formatdatedb($datedebutindem);
            $datefinindem = $fonctions->formatdatedb($datefinindem);
            foreach ($tabindem as $indem) 
            {
                // S'il y a chevauchement
                if (($datedebutindem <= $indem["datedebut"] and $datefinindem >= $indem["datedebut"]) 
                 or ($datedebutindem <= $indem["datefin"] and $datefinindem >= $indem["datefin"])
                 or ($datedebutindem <= $indem["datedebut"] and $datefinindem >= $indem["datefin"])
                 or ($datedebutindem >= $indem["datedebut"] and $datefinindem <= $indem["datefin"]))
                {
                    $dateok = false;
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                    $erreur = $erreur . "La date de début et la date de fin de l'indemnité chevauche une indemnité existante.<br>Veuillez modifier les indemnités existantes.";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                    break; // On sort de la boucle car on a trouvé au moins un chevauchement
                }
            }
        }
        if (!is_numeric($montantindem) || $montantindem < 0)
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Le montant de l'indemnité doit être un nombre positif. <br>";
            $dateok = false;
        }
        
        if ($dateok)
        {
            //echo "On va sauvegarder les infos !<br>";
            // On ajoute l'indemnité dans le tableau existant
            $datedebutindem = $fonctions->formatdatedb($datedebutindem);
            $datefinindem = $fonctions->formatdatedb($datefinindem);
            unset ($indem);
            $indem = array('datedebut' => $datedebutindem, 'datefin' => $datefinindem , 'montant' => $montantindem);
            $tabindem[] = $indem;
            // On va réorganiser les indemnités par date de début pour quelles soit dans l'ordre chronologique
            $newtabindem = array();
            foreach ($tabindem as $indem)
            {
                $newtabindem[$fonctions->formatdatedb($indem['datedebut'])] = $indem;
            }
            unset ($tabindem);
            $tabindem = $newtabindem;
            ksort($tabindem);
            //var_dump($tabindem);
            $datastring = "";
            foreach ($tabindem as $indem)
            {
                if (strlen($datastring)>0) $datastring = $datastring . ";";
                $datastring = $datastring . $indem['datedebut'] . '|' . $indem['datefin'] . '|' . str_replace('.',',',$indem['montant']);
            }
            $update = "UPDATE CONSTANTES SET VALEUR = '$datastring' WHERE NOM = 'INDEMNITETELETRAVAIL'";
            $query = mysqli_query($dbcon, $update);
        }
        
        if ($erreur != '')
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
        }
        else
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Les données sont enregistrées");
        }
    }


    $tabindem = $fonctions->listeindemniteteletravail('01/01/1900', '31/12/2100'); // On récupère toutes les indemnités existantes dans la base de données
    
    echo "<form name='form_indem_delete' id='form_indem_delete' method='post' >";
    echo "<br>Liste des indemnités : <br>";
    echo "<table class='tableausimple' id='listeindemnite'>";
    echo "<tr><center><td class='titresimple'>Date début</td>
                      <td class='titresimple'>Date fin</td>
                      <td class='titresimple'>Montant</td>
                      <td class='titresimple'>Annuler</td>";
    echo "</center></tr>";
    
    foreach ($tabindem as $indextabindem => $indem)
    {
    // $tabindem[$indextabindem]["datefin"]
    // $tabindem[$indextabindem]["datedebut"]
    //$montant = str_replace(',','.',$tabindem[$indextabindem]["montant"]);
        $calendrierid_deb = "date_debut_indem";
        $calendrierid_fin = "date_fin_indem";
        $indemid = $indem["datedebut"] . "_" . $indem["datefin"] . "_" . $indem["montant"];
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').change(function () {
        		$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker("destroy");
        		$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("maxperiode")});
 
	       	$('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').change(function () {
       			$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker("destroy");
       			$('[id="<?php echo $calendrierid_deb . '[' . $indemid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $indemid . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php     	        
        echo "<tr>";
        //echo "  <td class='cellulesimple'><center>" . $fonctions->formatdate($indem["datedebut"]) . "</center></td>";
        //echo "  <td class='cellulesimple'><center>" . $fonctions->formatdate($indem["datefin"]) . "</center></td>";
?>
    <td class='cellulesimple'><center>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $indemid . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $indemid .']'?> size=10
        	minperiode='01/01/1900'
        	maxperiode='31/12/2100'
        	value='<?php echo $fonctions->formatdate($indem["datedebut"]) ?>'>
    </center></td>
    
    <td class='cellulesimple'><center>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $indemid . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $indemid . ']' ?>
        	size=10
        	minperiode='01/01/1900'
        	maxperiode='31/12/2100'
        	value='<?php echo $fonctions->formatdate($indem["datefin"]) ?>'>
	</center></td>
<?php
        echo "  <td class='cellulesimple'><center>" . number_format($indem["montant"],2,",","") . " €</center><input type='hidden' name='montant[" . $indemid . "]' id='montant[" . $indemid . "]' value='" . number_format($indem["montant"],2,",","") . "'></td>";  // str_replace('.',',',$indem["montant"])
        echo "  <td class='cellulesimple'><center><input type='checkbox' value='" . $indemid . "' id='" . $indemid . "' name='cancelindem[]' ></center></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_3'>";
    echo "<br><input type='submit' value='Soumettre' name='modification'/>";
    echo "</form>";

    $datedebutindem = "";
    $datefinindem = "";
    $calendrierid_deb = "date_debut_newindem";
    $calendrierid_fin = "date_fin_newindem";
    ?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[newindem]' ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[newindem]' ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php
    	echo "<br><br>";
      	echo "Création d'une nouvelle indemnité de télétravail : <br>";
        echo "<form name='form_indemnite_creation' id='form_indemnite_creation' method='post' >";
?>
        <table>
	        <tr>
    		    <td style='padding-left: 30px;'>Date de début de l'indemnité de télétravail : </td>
<?php         
        if ($fonctions->verifiedate($datedebutindem)) {
            $datedebutindem = $fonctions->formatdate($datedebutindem);
    	}
?>
				<td>
                    <input class="calendrier" type=text
                    	name=<?php echo $calendrierid_deb . '[newindem]'?>
                    	id=<?php echo $calendrierid_deb . '[newindem]'?> size=10
                    	minperiode='01/01/1900'
                    	maxperiode='31/12/2099'
                    	value='<?php echo $datedebutindem ?>'>
            	</td>
           </tr>
	       <tr>
    		    <td style='padding-left: 30px;'>Date de fin de l'indemnité de télétravail : </td>
<?php
    	if ($fonctions->verifiedate($datefinindem)) {
    	    $datefinindem = $fonctions->formatdate($datefinindem);
        }      
?>
				<td>
                    <input class="calendrier" type=text
                    	name=<?php echo $calendrierid_fin . '[newindem]' ?>
                    	id=<?php echo $calendrierid_fin . '[newindem]' ?>
                    	size=10
                    	minperiode='01/01/1900'
                    	maxperiode='31/12/2099'
                    	value='<?php echo $datefinindem ?>'>
            	</td>
           </tr>
		   <tr>
				<td style='padding-left: 30px;'>Montant de l'indemnité télétravail : </td>
				<td><input type='text' name='montantnew' value='' maxlength='5' size='5'></td>
		   </tr>
	   </table>
<?php
    	echo "<br>";
	    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
	    echo "<input type='hidden' id='current_tab' name='current_tab' value='tab_3'>";
	    echo "<input type='submit' value='Soumettre'  name='creation_indem'/>";
	    echo "</form>";
    
?>            
            
<!--         
        ########################################################
        #                                                      #
        # ICI SE TERMINE LA GESTION DES ONGLETS                #
        #                                                      #
        ########################################################
-->        
            
        </div>
    </div>
    <script type="text/javascript">
        const tabs = document.querySelectorAll('[data-tab-value]')
        const tabInfos = document.querySelectorAll('[data-tab-info]')
  
        tabs.forEach(tab => {        
            tab.addEventListener('click', () => {
            	tabs.forEach(onglet => {
	            	onglet.classList.remove('tab_active')
	            })
                tab.classList.add('tab_active');

                const target = document
                    .querySelector(tab.dataset.tabValue);
  
                tabInfos.forEach(tabInfo => {
                    tabInfo.classList.remove('active')
                })
                target.classList.add('active');
                //const current_tab_input = document.getElementById('current_tab');
                //current_tab_input.value = target.id;
            })
        })
    </script>    

<!-- 
    <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
    <input type='hidden' id='current_tab' name='current_tab' value='tab_1'>
    <input type='submit' value='Soumettre' name='selection'/>
-->
	</form>

</body>
</html>


    