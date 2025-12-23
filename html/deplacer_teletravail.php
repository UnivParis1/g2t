<?php
    // require_once ('CAS.php');
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
//        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");
    require_once (dirname(__FILE__,1) . "/includes/modif_teletravail.php");

    // var_dump($_POST);

    $mode = MODE_AGENT;
    if (isset($_POST["mode"]) and trim($_POST["mode"]) != '')
    {
        $mode = $_POST["mode"];
    }

    $date_selected = '';
    if (isset($_POST["date_selected"]))
    {
        $date_selected = $_POST["date_selected"];
    }
    
    $moment_selected = '';
    if (isset($_POST["moment_selected"]))
    {
        $moment_selected = $_POST["moment_selected"];
    }
    
    $agentid_selected = '';
    if (isset($_POST['agentid_selected']))
    {
        $agentid_selected = $_POST['agentid_selected'];
    }
            
    $action = '';
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];
    }
    
    $report_date = '';
    if (isset($_POST['report_date']))
    {
        $report_date = $_POST['report_date'];
    }
        
    $report_moment = '';
    if (isset($_POST['report_moment']))
    {
        $report_moment = $_POST['report_moment'];
    }

    $typeconvention = '';
    if (isset($_POST['typeconvention']))
    {
        $typeconvention = $_POST['typeconvention'];
    }

    $agentid = "";
    if (isset($_POST['agentid']))
    {
        $agentid = trim($_POST['agentid'] . '');
    }    

    $statutarray = array();
    if (isset($_POST['statut']))
    {
        $statutarray = $_POST['statut'];
    }
    $motifarray = array();
    if (isset($_POST['motif']))
    {
        $motifarray = $_POST['motif'];
    }

    $fullagentlist = array();
    if (isset($_POST['fullagentlist']))
    {
        $fullagentlist = explode(',',$_POST['fullagentlist']);
    }

    //////////////////////////////////////////////////////////////////////////////////
    // Le responsable a modifié le statut d'une demande d'adaptation de télétravail => On est en forcément en mode RESPONSABLE
    if (isset($_POST['savemodif']) and $mode==MODE_RESPONSABLE)
    {
        $error = '';
        $info = '';
        foreach ($statutarray as $ttexceptionkey => $statut)
        {
            // Si le statut est en attente => On ne fait rien
            if ($statut == ttexception::STATUT_ENATTENTE)
            {
                continue;
            }

            $tempttexceptionkey = str_replace('"', '',$ttexceptionkey);
            $idarray = explode("_", $tempttexceptionkey);
            $tempagentid = $idarray[0];
            $ttexceptionid = $idarray[1];
            // var_dump($ttexceptionkey);
            // var_dump($tempttexceptionkey);
            // var_dump($ttexceptionid);
            // var_dump($tempagentid);

/*
            // On récupère l'agentid et l'id de l'exception car le ttexceptionkey est de la forme 
            // <agentid><ttexceptionid> où ttexceptionid est de la forme YYYYMMDDP où P = 0, 1 ou 2.
            // => Donc la longueur de ttexceptionid est connue (= 9 caractères)
            // Le reste c'est le numéro de l'agent.
            $ttexceptionid = substr($ttexceptionkey,-9);
            $tempagentid = substr_replace($ttexceptionkey,"",-9);
            // var_dump($ttexceptionkey);
            // var_dump($ttexceptionid);
            // var_dump($tempagentid);
*/
            $agent = new agent($dbcon);
            $agent->load($tempagentid);

            $ttexception = new ttexception;
            $ttexception->infosfromid($ttexceptionid);
            $ttexceptionliste = $fonctions->listejoursteletravailexclus($tempagentid,$ttexception->dateorigine, $ttexception->momentorigine,$ttexception->dateorigine, $ttexception->momentorigine,FALSE);
            // var_dump($ttexceptionliste);
            if ($statut == ttexception::STATUT_VALIDE)
            {
                $tmperror = $fonctions->ajoutjoursteletravailexclus($tempagentid,$ttexceptionliste[0]->dateorigine, $ttexceptionliste[0]->momentorigine,$ttexceptionliste[0]->dateremplacement, $ttexceptionliste[0]->momentremplacement,$statut);
                if (is_string($tmperror) and ($tmperror . '' != ''))
                {
                    $error = $error . '<br>' . $tmperror;
                }
                else
                {
                    $info = $info . "<br>" . "La demande de déplacement de télétravail du " . $fonctions->formatdate($ttexceptionliste[0]->dateorigine) . " " . $fonctions->nommoment($ttexceptionliste[0]->momentorigine) . " a été validée pour " . $agent->identitecomplete()  . ".";
                    modiftt_envoyermailagent($ttexceptionliste[0], ttexception::ACTION_VALIDE);
                }
            }
            elseif ($statut == ttexception::STATUT_REFUSE)
            {
                $motif = '';
                if (isset($motifarray[$ttexceptionkey]))
                {
                    $motif = trim($motifarray[$ttexceptionkey] . '');
                    // var_dump($motif);
                }
                if (strlen($motif . '' != ''))
                {
                    // var_dump($ttexceptionliste);
                    $tmperror = $fonctions->supprjourteletravailexclu($tempagentid,$ttexceptionliste[0]->dateorigine, $ttexceptionliste[0]->momentorigine);
                    if ($tmperror . '' != '')
                    {
                        $error = $error . '<br>' . $tmperror;
                    }
                    else
                    {
                        $info = $info . "<br>" . "La demande de déplacement de télétravail du " . $fonctions->formatdate($ttexceptionliste[0]->dateorigine) . " " . $fonctions->nommoment($ttexceptionliste[0]->momentorigine) . " a été refusée pour " . $agent->identitecomplete()  . ".";
                        $ttexceptionliste[0]->motif = $motif;
                        modiftt_envoyermailagent($ttexceptionliste[0], ttexception::ACTION_REFUSE);
                    }
                }
                else // Le motif est vide ! Ca ne devrait pas arriver mais on fait quand même le test
                {
                    $error = $error . '<br>' . "Le motif n'a pas été saisi pour le refus du " . $fonctions->formatdate($ttexceptionliste[0]->dateorigine) . " " . $fonctions->nommoment($ttexceptionliste[0]->momentorigine) . " pour " . $agent->identitecomplete()  . ".";
                }
            }
        }
        if ($error . '' != '')
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, "Une erreur s'est produite lors de la mise à jour des exceptions de télétravail<br>" . $error);
        }
        if ($info . '' != '')
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, $info);
        }

    }
    //////////////////////////////////////////////////////////////////////
    // Un agent a demandé a agir sur une instance de télétravail à partir du planning de l'agent
    elseif ($agentid_selected != "" and $date_selected != "" and $moment_selected != "" )
    {
        // var_dump ("report_date = " . $report_date);
        $complement = new complement($dbcon);
        $agent = new agent($dbcon);
        $agent->load($agentid_selected);
        if ($action == ttexception::ACTION_DESACTIVE)
        {   // On fait une désactivation de la date
            // var_dump('on desactive');
            // $listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //var_dump($listeexclusion);
            // if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)===false)
            
            // Si on doit déplacer la journée complète, on doit mettre à vide le moment sélectionné et le moment de destination
            if ($report_moment!==fonctions::MOMENT_MATIN and $report_moment!==fonctions::MOMENT_APRESMIDI)
            {
                $report_moment = '';
                $moment_selected = '';
            }
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected,$statut);
            // var_dump("date_selected = $date_selected");
            // var_dump("moment_selected = $moment_selected");
            // var_dump("exclusion = $exclusion");
            if ($exclusion===false)
            {   // On n'a pas trouvé la date dans la liste
                $reportpossible = true;
                if ($report_date != '')
                {
                    $planning = new planning($dbcon);
                    $planning->load($agentid_selected, $report_date, $report_date, true, true, true);
                    $planningelementliste = $planning->planning();
                    // ATTENTION :
                    // On doit vérifier que la date cible n'est pas une journée de télétravail exclue
                    // Donc pour chaque planningelement on regarde si la classe HTML_CLASS_EXCLUSION est dans les htmlextraclass
                    if ($report_moment==fonctions::MOMENT_MATIN)
                    {
                        $planningelement = current($planningelementliste);
                        $reportpossible = ($planningelement->type()=='' and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE));
                    }
                    elseif ($report_moment==fonctions::MOMENT_APRESMIDI)
                    {
                        $planningelement = next($planningelementliste);
                        $reportpossible = ($planningelement->type()=='' and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) and !str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE));
                    }
                    else
                    {
                        foreach ($planning->planning() as $planningelement)
                        {
                            if ($planningelement->type()!='' or str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION) or str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE))
                            {
                                $reportpossible = false;
                                break;
                            }
                        }
                    }
                }
                // var_dump($reportpossible);
                if ($reportpossible)
                {
                    // var_dump("On va faire le complément");
                    $tmperror = $fonctions->ajoutjoursteletravailexclus($agentid_selected, $date_selected, $moment_selected, $report_date, $report_moment,ttexception::STATUT_ENATTENTE);
                    if (is_string($tmperror) and ($tmperror . '' != ''))
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR, $tmperror);
                    }
                    else
                    {
                        if (trim($report_date) != '')
                        {
                            echo $fonctions->showmessage(fonctions::MSGINFO,"La journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est déplacée au " . $fonctions->formatdate($report_date) . ".");
                            modiftt_envoyermailreponsable($tmperror,ttexception::ACTION_DEPLACEMENT);
                        }
                        else
                        {
                            echo $fonctions->showmessage(fonctions::MSGINFO,"La suppression de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                            modiftt_envoyermailreponsable($tmperror,ttexception::ACTION_SUPPRIME);
                        }
                    }
                }
                else if (str_contains($planningelement->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION))
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de déplacer la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " : La date souhaitée (le " . $fonctions->formatdate($report_date) . ") est un jour de télétravail déplacé.");
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de déplacer la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " : La date souhaitée (le " . $fonctions->formatdate($report_date) . ") n'est pas disponible.");
                }
            }
            else
            {
                //echo "On demande une désactivation alors que la date est déjà désactivé. On ne fait rien. <br>";
            }
        }
        elseif ($action == ttexception::ACTION_REACTIVE)
        {   // On fait une réactivation
            //$listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)!==false)
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected, $statut);
            // var_dump("exclusion = " . $exclusion);
            if ($exclusion!==false)
            {   // On a trouvé la date dans la liste
                // var_dump("On n'a pas trouvé la date dans les exclusions");
                $erreur = $agent->supprjourteletravailexclu($date_selected, $moment_selected);
                // var_dump("Erreur = XXXX" . $erreur . "XXXX");
                if (strlen(trim($erreur))==0)
                {
                    echo $fonctions->showmessage(fonctions::MSGINFO,"La réactivation de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                    // $ttexception = new ttexception;
                    // $ttexception->agentid = $agent->agentid();
                    // $ttexception->dateorigine = $date_selected;
                    // $ttexception->momentorigine = $moment_selected;
                    modiftt_envoyermailreponsable($exclusion,ttexception::ACTION_REACTIVE);

                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de réactiver la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete()  . " : $erreur ");                    
                }
            }
            else
            {
                // var_dump ("On demande une réactivation alors que la date n'est pas désactivé. On ne fait rien.");
            }
        }
    }

    $datedebut = $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode());
    if (strcasecmp((string)$fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) 
    {
        $datefin = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("+1month", $timestamp)); // On passe au mois suivant
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("-1days", $timestamp)); // On passe à la veille
    } 
    else 
    {
        $datefin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
    }

    if ($mode == MODE_AGENT)
    {
        addwaitingimgdiv();
        echo "<br>Planning de l'agent " . $user->civilite() . " " . $user->nom() . " " . $user->prenom() . " <br>";
        echo "<br>";
        echo "Pour déplacer ou supprimer une journée de télétravail, veuillez double-cliquer sur une journée de télétravail.<br>";
        echo "Pour annuler une demande de déplacement ou de suppression d'une journée de télétravail, vous devez double-cliquer sur la date initiale du télétravail.<br>";
        echo "<br>";
        //echo $user->planninghtml($datedebut, $datefin,false,true,true);
        //var_dump ("$datedebut, $datefin");
        // Div qui sera mis à jour avec le retour HTML du WS
        echo "<div id='planningagent_" . $user->agentid() . "' class='divtocomplete'></div>";
        echo "<br>";
        echo "<br>";
        echo "<form name='change_form' id='change_form' method='post' class='centeraligntext'>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
        echo "<input type='hidden' name='mode' value='" . $mode . "' />";
        echo "<input type='hidden' name='date_selected' id='date_selected' value='' />";
        echo "<input type='hidden' name='moment_selected' id='moment_selected' value='' />";
        echo "<input type='hidden' name='agentid_selected' id='agentid_selected' value='' />";
        echo "<input type='hidden' name='report_date' id='report_date' value='' />";
        echo "<input type='hidden' name='report_moment' id='report_moment' value='' />";
        echo "<input type='hidden' name='typeconvention' id='typeconvention' value='' />";
        echo "<input type='hidden' name='action' id='action' value='' />";
        echo "<input type='hidden' name='agentid' id='agentid' value='$agentid' />";
        echo "</form>";

?>
        <script>
            // Initialisation des paramètres pour l'affichage du planning de l'agent
            var params = {  "methode" : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                            "mode" : "<?php echo $mode; ?>",
                            "agentid" : "<?php echo $user->agentid(); ?>", 
                            "datedebut" : "<?php echo $fonctions->formatdatedb($datedebut); ?>" , 
                            "datefin" : "<?php echo $fonctions->formatdatedb($datefin); ?>",
                            "clickable" : 'N',
                            "dbclickable" : 'O',
                            "showpdflink" : 'N',
                            "includeteletravail" : 'O'
                        };
            // Appel de la fonction asynchrone 
            showagentplanning(params,document.getElementById('planningagent_<?php echo $user->agentid(); ?>'));
        </script>
<?php
    }
    else if ($mode == MODE_RESPONSABLE or $mode == MODE_GESTION) // On est en mode RESPONSABLE ou en mode GESTION
    {
        if ($mode == MODE_RESPONSABLE)
        {
            // echo "Mode Responsable ...... <br>";
            $agentlistefull = $user->listeagentenresponsabilite(date("d/m/Y"), date("d/m/Y"));
        }
        else
        {
            // echo "Mode Gestionnaire ...... <br>";
            $agentlistefull = $user->listeagentengestion(date("d/m/Y"), date("d/m/Y"));
        }
            
        // Il faut trier les agents par structure puis par ordre alphabétique
        $tmpagentlistefull = array();
        foreach ($agentlistefull as $keyagent => $membre)
        {
            // Astuce : On ajoute la longueur du nom pour que le plus petit soit en premier
            $tmpagentlistefull[strlen($membre->structureid()) . '#' . $membre->structureid()][$keyagent] = $membre;
        }
        // On trie les structures par ordre naturel
        ksort($tmpagentlistefull,SORT_NATURAL);
        $agentlistefull = array();
        foreach ($tmpagentlistefull as $tmpstruct)
        {
            // On trie les agents par ordre naturel
            ksort($tmpstruct,SORT_STRING);
            $agentlistefull = array_merge($agentlistefull,$tmpstruct);
        }
            
        if (count($agentlistefull)==0)
        {
            if ($mode == MODE_RESPONSABLE)
            {
                echo "Vous n'avez aucun agent en responsabilité ou vous n'êtes pas autorisé(e) à modifier des demandes de jours de télétravail.<br>";
            }
            else
            {
                echo "Vous n'avez aucun agent en gestion ou vous n'êtes pas autorisé(e) à modifier des demandes de jours de télétravail.<br>";
            }
            $selectagentbutton = false;
            $displaysubmit = false;
        }
        else
        {
            $tmpstruct = null;
            $fullagentlist = array();
            echo "<form name='selectagent_form' id='selectagent_form' method='post' >";
            echo "<SELECT class='listeagentg2t' size='1' id='agentid' name='agentid' style='width: 350px;'>";
            foreach ($agentlistefull as $keyagent => $membre) 
            {
                if (!$membre->estutilisateurspecial())
                {
                    if (is_null($tmpstruct))
                    {
                        echo "<OPTION value='all'>Tous les agents</OPTION>";
                        $tmpstruct = new structure($dbcon);
                        $tmpstruct->load($membre->structureid());
                        echo "<optgroup label='" . $tmpstruct->nomcourt() . "'>";
                    }
                    elseif ($tmpstruct->id()!=$membre->structureid())
                    {
                        $tmpstruct = new structure($dbcon);
                        $tmpstruct->load($membre->structureid());
                        echo "</optgroup>";
                        echo "<optgroup label='" . $tmpstruct->nomcourt() . "'>";
                    }
                    echo "<OPTION value='" . $membre->agentid() . "'";
                    if ($agentid == $membre->agentid())
                    {
                        echo " selected ";
                    }
                    echo ">" . $membre->identitecomplete(true) . "</OPTION>";
                    $fullagentlist[$membre->agentid()] = $membre->agentid();
                    $selectagentbutton = true;
                    $displaysubmit = false;
                }
            }

            if (!is_null($tmpstruct))
            {
                echo "</optgroup>";
            }
            echo "</SELECT>";
            echo "<br>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
            echo "<input type='hidden' name='mode' value='" . $mode . "' />";
            echo "<input type='hidden' name='fullagentlist' value='" . implode(",",$fullagentlist)  . "' />";
            if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
            echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' />";
            echo "</form>";
            echo "<br><br>";
        }

        if ($agentid != '')
        {
            if ($agentid != 'all')
            {
                $fullagentlist = array($agentid);
            }

            $aumoinsunagent = false;
            foreach ($fullagentlist as $tempagentid)
            {
                $agent = new agent($dbcon);
                $agent->load($tempagentid);

                $datedebut = $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode());
                $datefin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
                $ttexceptionliste = $fonctions->listejoursteletravailexclus($tempagentid,$datedebut, fonctions::MOMENT_MATIN, $datefin, fonctions::MOMENT_APRESMIDI, false); // $agent->teletravaildeplaceliste($datedebut, $datefin);
                // var_dump($ttexceptionliste);
                $htmltext = "";
                $premiereligne = true;
                $taillemaxcommentaire = 250;
                echo "<form name='modifstatut_form' id='modifstatut_form' method='post' >";
                foreach($ttexceptionliste as $ttexception)
                {
                    if ($ttexception->statut == ttexception::STATUT_ENATTENTE)
                    {
                        if ($premiereligne)
                        {
                            $htmltext = $htmltext . "<table class='tableausimple' width='100%'>";
                            $htmltext = $htmltext . "	<thead>";
                            $htmltext = $htmltext . "		<tr>";
                            $htmltext = $htmltext . "			<th scope='col' class='titresimple' colspan='4' align='center'>Demandes à valider pour " . $agent->identitecomplete() . "</th>";
                            $htmltext = $htmltext . "		</tr>";
                            $htmltext = $htmltext . "		<tr align='center'>";
                            $htmltext = $htmltext . "			<th scope='col' class='cellulesimple'>Date d'origine</th>";
                            $htmltext = $htmltext . "			<th scope='col' class='cellulesimple'>Date du report</th>";
                            $htmltext = $htmltext . "			<th scope='col' class='cellulesimple'>Etat de la demande</th>";
                            $htmltext = $htmltext . "			<th scope='col' class='cellulesimple'>Motif (obligatoire si la demande est refusée) - maximum $taillemaxcommentaire caractères</th>";
                            $htmltext = $htmltext . "		</tr>";
                            $htmltext = $htmltext . "	</thead>";
                            $htmltext = $htmltext . "	<tbody>";
                            $premiereligne = false;
                        }

                        $htmltext = $htmltext . "		<tr align='center' class='bulleinfo'>";
                        $htmltext = $htmltext . "			<td class='cellulesimple'>" . $fonctions->nomjour($ttexception->dateorigine) . " " . $fonctions->formatdate($ttexception->dateorigine) . "  " . $fonctions->nommoment($ttexception->momentorigine) . "</td>";
                        $htmltext = $htmltext . "			<td class='cellulesimple'>";
                        if ($ttexception->dateremplacement . '' != '')
                        {
                            $nomdujour = $fonctions->nomjour($ttexception->dateremplacement);
                            $htmltext = $htmltext . $nomdujour . " " . $fonctions->formatdate($ttexception->dateremplacement) . "  " . $fonctions->nommoment($ttexception->momentremplacement);
                            $agentstruct = new structure($dbcon);
                            $indexjroblig = "";
                            if ($agentstruct->load($agent->structureid()))
                            {
                                $indexjroblig = $agentstruct->jourpresenceobligatoire();
                            }
                            if ($indexjroblig != '' and $nomdujour == $fonctions->nomjourparindex($indexjroblig))
                            {
                                $htmltext = $htmltext . "<p class='centeraligntext nomargin warnbackgroundtext'>Attention : Jour de présence obligatoire.</p>";
                            }
                        }
                        else
                        {
                            $htmltext = $htmltext . "Non reportée";
                        }
                        $htmltext = $htmltext . "			</td>";
                        $htmltext = $htmltext . "			<td class='cellulesimple'>";
                        $idelement = $tempagentid . '_' . $ttexception->id();
                        $htmltext = $htmltext . "				<select name='statut[\"$idelement\"]' id='statut[\"$idelement\"]' onchange='demandestatutchange(this,\"$idelement\");'>";
                        $htmltext = $htmltext . "                   <option value='" . ttexception::STATUT_ENATTENTE . "' ";
                        if (isset($statutarray[$idelement]) and $statutarray[$idelement] == ttexception::STATUT_ENATTENTE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(ttexception::STATUT_ENATTENTE)  . "</option>";
                        $htmltext = $htmltext . "                   <option value='" . ttexception::STATUT_VALIDE . "' ";
                        if (isset($statutarray[$idelement]) and $statutarray[$idelement] == ttexception::STATUT_VALIDE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(ttexception::STATUT_VALIDE)  . "</option>";
                        $htmltext = $htmltext . "                   <option value='" . ttexception::STATUT_REFUSE . "' ";
                        if (isset($statutarray[$idelement]) and $statutarray[$idelement] == ttexception::STATUT_REFUSE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(ttexception::STATUT_REFUSE)  . "</option>";
                        $htmltext = $htmltext . "				</select>";
                        $htmltext = $htmltext . "			</td>";
                        $htmltext = $htmltext . "			<td class='cellulesimple'>";

                        $textareastyle = " class='commenttextarea";
                        $disabletext = " disabled ";
                        if (isset($statutarray[$idelement]) and $statutarray[$idelement] == ttexception::STATUT_REFUSE)
                        {
                            $textareastyle = $textareastyle . " commentobligatoirebackground";
                            $disabletext = "";
                        }
                        $textareastyle = $textareastyle . "'";


                        $htmltext = $htmltext . "				<textarea name='motif[\"$idelement\"]' id='motif[\"$idelement\"]' rows='2' cols='130' $textareastyle oninput='checktextlength(this,$taillemaxcommentaire); validdemandemotif(this,\"$idelement\");' $disabletext></textarea>";
                        $htmltext = $htmltext . "			</td>";
                        $htmltext = $htmltext . "		</tr>";
                    }
                }
                if (!$premiereligne)
                {
                    $htmltext = $htmltext . "	</tbody>";
                    $htmltext = $htmltext . "</table>";
                    $htmltext = $htmltext . "<br>";
                    $aumoinsunagent = true;
                }
                if ($htmltext == '' and $agentid != 'all')
                {
                    $htmltext = "<br>Cet agent n'a pas de demande de déplacement de télétravail <br>";
                }
                echo $htmltext;
            }
            if ($aumoinsunagent == true)
            {
                $htmltext = '';
                $htmltext = $htmltext . "<br>";
                $htmltext = $htmltext . "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
                $htmltext = $htmltext . "<input type='hidden' name='mode' value='" . $mode . "' />";
                $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $agentid . "' />";
                $htmltext = $htmltext . "<input type='hidden' name='fullagentlist' value='" . implode(",",$fullagentlist)  . "' />";
                if (isset($_POST['pagepath'])) $htmltext = $htmltext . "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
                $htmltext = $htmltext . "<input type='submit' id='savemodif' name='savemodif' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
                echo $htmltext;
            }

            echo "</form>";
            echo "<br><br>";
        }

    }
?>

</body>
</html>
