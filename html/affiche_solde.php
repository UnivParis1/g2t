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
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");

    echo "<br>";

    //echo "<br>" . print_r($_POST,true) . "<br>";

    ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
    $mode = $_POST["mode"];
    if ($mode == "")
    {
        $mode = MODE_RESPONSABLE;
    }

    $structureid = null;
    if (isset($_POST["structureid"]))
    {
        $structureid = $_POST["structureid"];
    }

    $previous = "";
    if (isset($_POST["previous"]))
    {
        $previous = $_POST["previous"];
    }
    if ($previous == 'yes')
    {
        $previous = 1;
    }
    else
    {
        $previous = 0;
    }
    
    $agentselect = '';
    if (isset($_POST["agentselect"]))
    {
        $agentselect = $_POST["agentselect"];
    }

    if ($mode == MODE_RH)
    {
        $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "") {
            $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
        echo "<form name='selectstructure'  method='post' >";

        $structliste = array();
        while ($result = mysqli_fetch_row($query)) 
        {
            $struct = new structure($dbcon);
            $struct->load($result[0]);
            $structliste[$result[0]] = $struct;
            $structliste = $structliste + (array)$struct->structurefille(true,0);
        }
        echo "<select size='1' id='structureid' name='structureid'>";
        $fonctions->afficherlistestructureindentee($structliste, false, $structureid);
        unset($structliste);
        echo "</select>";

        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='previous' value='no'>";
        echo "<input type='submit' name= 'Valid_struct' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "<br>";

        if (!is_null($structureid))
        {
            $struct = new structure($dbcon);
            $struct->load($structureid);
            echo "<br>";
            echo "Solde des agents de la structure : " . $struct->nomlong() . " (" . $struct->nomcourt() . ") <br>";
            $annerecherche = ($fonctions->anneeref() - $previous);
            $agentliste = $struct->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'n');
            if (is_array($agentliste)) {
                foreach ($agentliste as $agentkey => $agent) {
                    // echo "Annee ref = " . $fonctions->anneeref();
                    // echo " debut = " . $fonctions->debutperiode();
                    // echo " Annee ref +1 = " . ($fonctions->anneeref()+1);
                    // echo " Fin = " . $fonctions->finperiode();
                    // echo "Previous = " . $previous ;
                    echo $agent->soldecongeshtml(($fonctions->anneeref() - $previous), TRUE);

                }
            }
        }
        echo "<br>";

    }
    elseif (strcasecmp((string)$mode, MODE_RESPONSABLE) == 0) 
    {
        $structureliste = $user->structrespliste();
        //var_dump("Avant appel enleverstructuresinclues_soldes");
        $structureliste = $fonctions->enleverstructuresinclues_soldes($structureliste);
        if (is_array($structureliste))
        {
            // uasort($structureliste,"triparprofondeurabsolue");
            uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
        }
        
        //var_dump("Apres appel enleverstructuresinclues_soldes");
        echo "Veuillez selectionner un agent :<br>";
        echo "<form name='formselect'  method='post'>";
        echo "<select id='agentselect' name='agentselect' class='listeagentg2t' size='1' style='width: 350px;'>";
        echo "<option value=''>Tous les agents</option>";
        foreach ($structureliste as $structkey => $structure)
        {
//            error_log(basename(__FILE__) . " : La structure est " . $structure->nomcourt());
            
            $annerecherche = ($fonctions->anneeref() - $previous);

            if (strcasecmp((string)$structure->respaffsoldesousstruct(),'o')==0)  // Si on doit afficher le solde des agents des structures inclues
            {
                if ($structure->isincluded() and $structure->parentstructure()->responsable()->agentid()==$user->agentid())
                {
                     continue;
                }
                $agentliste = $user->listeagentenresponsabilite($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure, agent::FILTRE_SOLDE);
            }
            else
            {
                // echo "validsousstruct = XXXXX" . $validsousstruct . "XXXXX <br>";

                $agentliste = $user->listeagentenresponsabilite($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure, agent::FILTRE_SOLDE);
            }
            ksort($agentliste);
                        
//            error_log(basename(__FILE__) . " : La liste des agents est " . print_r($agentliste,true));
            if (is_array($agentliste))
            {
                echo "<optgroup label='". $structure->nomcourt() ."'>";
                foreach ($agentliste as $agentkey => $agent)
                {
                    echo "<option value='" . $agent->agentid() . "'";
                    if ($agentselect == $agent->agentid())
                    {
                        echo " selected ";
                    }
                    echo ">" . $agent->identitecomplete(true) . "</option>";
                }
                echo "</optgroup>";
            }
        }
        echo "</select>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='previous' value='";
        if ($previous==1)
        {
            echo 'yes';
        }
        else
        {
            echo 'no';
        }
        echo "'>";
        echo "<br>";
        echo "<input type='submit' name= 'valid_agent' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
        // Ligne de séparation
        echo "<hr>";
        
        if (isset($_POST['valid_agent']))
        {
            addwaitingimgdiv();

            if ($agentselect == '')
            {
                $structureliste = $user->structrespliste();
                $structureliste = $fonctions->enleverstructuresinclues_soldes($structureliste);
                if (is_array($structureliste))
                {
                    // uasort($structureliste,"triparprofondeurabsolue");
                    uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
                }
                foreach ($structureliste as $structkey => $structure) 
                {
                    echo "<br>";
                    //$fonctions->time_elapsed("Début affichage structure " . $structure->nomcourt(), __METHOD__, true);
                    //echo "Solde des agents de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                    $annerecherche = ($fonctions->anneeref() - $previous);
                    
                    //if ($user->agentid() == '937') ////// PATCH MONIQUE LIER - Ticket GLPI 145258
                    if (strcasecmp((string)$structure->respaffsoldesousstruct(),'o')==0)  // Si on doit afficher le solde des agents des structures inclues 
                    {
                        if ($structure->isincluded() and $structure->parentstructure()->responsable()->agentid()==$user->agentid())
                        {
                                continue;
                        }
                        $agentliste = $user->listeagentenresponsabilite($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure, agent::FILTRE_SOLDE);
                        //$agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'o');
                    }
                    else
                    {
                        $agentliste = $user->listeagentenresponsabilite($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure, agent::FILTRE_SOLDE);
                        //$agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure->respaffsoldesousstruct());
                    }
                    ksort($agentliste);

                    //$agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()));
                    // $agentliste = $structure->agentlist(date("d/m/").$annerecherche,date("d/m/").$annerecherche);
        
                    if (is_array($agentliste))
                    {
                           echo "Solde des agents de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                    }

                    echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
                    echo "<input type='hidden' name='userpdf' value='no'>";
                    // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
                    echo "<input type='hidden' name='anneeref' value='" . $annerecherche . "'>";
                    $listeagent = "";
                    // echo "Avant le foreach <br>";
                    if (is_array($agentliste)) 
                    {
                        foreach ($agentliste as $agentkey => $agent) 
                        {
                            $listeagent = $listeagent . "," . $agent->agentid();
                        }
                    }
                    // echo "listeagent = $listeagent <br>";
                    echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";
                    echo "<input type='hidden' name='typepdf' value='listedemande'>";
                    echo "</form>";
                    echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
                    echo "<br>";
        
                    if (is_array($agentliste)) 
                    {
                        foreach ($agentliste as $agentkey => $agent) 
                        {
                            //$fonctions->time_elapsed("Avant l'affichage de l'agent " . $agent->identitecomplete(), __METHOD__, true);
                            // echo "Annee ref = " . $fonctions->anneeref();
                            // echo " debut = " . $fonctions->debutperiode();
                            // echo " Annee ref +1 = " . ($fonctions->anneeref()+1);
                            // echo " Fin = " . $fonctions->finperiode();
                            // echo "Previous = " . $previous ;
                            echo $agent->soldecongeshtml(($fonctions->anneeref() - $previous), TRUE);
                            if ($previous == 0)
                            {
                                echo $agent->affichecommentairecongehtml(true);
                            }
                            echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);

                            echo "<div id='planningagent_" . $agent->agentid() . "' class='divtocomplete'></div>";
?>
                            <script>
                                // Initialisation des paramètres pour l'affichage du planning de l'agent
                                var params = {  "methode" : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                                                "mode" : "<?php echo $mode; ?>",
                                                "agentid" : "<?php echo $agent->agentid(); ?>", 
                                                "datedebut" : "<?php echo ($fonctions->anneeref() - $previous) . $fonctions->debutperiode(); ?>" , 
                                                "datefin" : "<?php echo ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(); ?>",
                                                "clickable" : 'N',
                                                "showpdflink" : 'N',
                                                "includeteletravail" : 'O'
                                             }
                                // Appel de la fonction asynchrone 
                                showagentplanning(params,document.getElementById('planningagent_<?php echo $agent->agentid(); ?>'));
                            </script>
<?php                            
                            //echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
                            
                            //$fonctions->time_elapsed("Après l'affichage de l'agent " . $agent->identitecomplete(),__METHOD__);
                            // Ligne de sÃ©paration entre les agents
                            echo "<hr>";
                        }
                    }
                }
            }
            else
            {
                $agent = new agent($dbcon);
                $agent->load($agentselect);
                echo "<br>";
                echo "Solde de l'agent " . $agent->identitecomplete() . " : <br>";
                echo "<form name='listedemandepdf_" . $agent->agentid() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
                echo "<input type='hidden' name='userpdf' value='no'>";
                echo "<input type='hidden' name='anneeref' value='" . $annerecherche . "'>";
                echo "<input type='hidden' name='listeagent' value='" . $agent->agentid() . "'>";
                echo "<input type='hidden' name='typepdf' value='listedemande'>";
                echo "</form>";
                echo "<a href='javascript:document.listedemandepdf_" . $agent->agentid() . ".submit();'>Liste des demandes en PDF</a>";
                echo "<br>";
                echo $agent->soldecongeshtml(($fonctions->anneeref() - $previous), TRUE);
                if ($previous == 0)
                {
                    echo $agent->affichecommentairecongehtml(true);
                }
                echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);

                echo "<div id='planningagent_" . $agent->agentid() . "' class='divtocomplete'></div>";
?>
                <script>
                    // Initialisation des paramètres pour l'affichage du planning de l'agent
                    var params = {  "methode" : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                                    "mode" : "<?php echo $mode; ?>",
                                    "agentid" : "<?php echo $agent->agentid(); ?>", 
                                    "datedebut" : "<?php echo ($fonctions->anneeref() - $previous) . $fonctions->debutperiode(); ?>" , 
                                    "datefin" : "<?php echo ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(); ?>",
                                    "clickable" : 'N',
                                    "showpdflink" : 'N',
                                    "includeteletravail" : 'O'
                                    }
                    // Appel de la fonction asynchrone 
                    showagentplanning(params,document.getElementById('planningagent_<?php echo $agent->agentid(); ?>'));
                </script>
<?php                            
                //echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
            }
            echo "<br>"; 
        }
    } 
    else // On est en mode gestionnaire
    {
        echo "Veuillez selectionner un agent :<br>";
        echo "<form name='formselect'  method='post'>";
        echo "<select id='agentselect' name='agentselect' class='listeagentg2t' size='1' style='width: 350px;'>";
        echo "<option value=''>Tous les agents</option>";
        $structureliste = $user->structgestliste();
        if (is_array($structureliste))
        {
            // uasort($structureliste,"triparprofondeurabsolue");
            uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
        }
        foreach ($structureliste as $structkey => $structure)
        {
            $annerecherche = ($fonctions->anneeref() - $previous);
            $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'n');
            //$agentliste = $user->listeagentengestion($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure);
            
            if (is_array($agentliste))
            {
                ksort($agentliste);
                echo "<optgroup label='". $structure->nomcourt() ."'>";
                foreach ($agentliste as $agentkey => $agent)
                {
                    echo "<option value='" . $agent->agentid() . "'";
                    if ($agentselect == $agent->agentid())
                    {
                        echo " selected ";
                    }
                    echo ">" . $agent->identitecomplete(true) . "</option>";
                }
                echo "</optgroup>";
            }
        }
        echo "</select>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='previous' value='";
        if ($previous==1)
        {
            echo 'yes';
        }
        else
        {
            echo 'no';
        }
        echo "'>";
        echo "<br>";
        echo "<input type='submit' name= 'valid_agent' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
        // Ligne de séparation
        echo "<hr>";
                
        if (isset($_POST['valid_agent']))
        {
            addwaitingimgdiv();

            if ($agentselect == '')
            {
                $structureliste = $user->structgestliste();
                if (is_array($structureliste))
                {
                    // uasort($structureliste,"triparprofondeurabsolue");
                    uasort($structureliste,array($fonctions,"triparprofondeurabsolue"));
                }
                foreach ($structureliste as $structkey => $structure) {
                    echo "<br>";
                    echo "Solde des agents de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                    $annerecherche = ($fonctions->anneeref() - $previous);
                    $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'n');
                    //$agentliste = $user->listeagentengestion($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),$structure);

                    // $agentliste = $structure->agentlist(date("d/m/").$annerecherche,date("d/m/").$annerecherche);
                    // $agentliste = $structure->agentlist(date("d/m/Y"),date("d/m/Y"));
        
                    // echo "agentliste="; print_r($agentliste); echo "<br>";
                    echo "<form name='listedemandepdf_" . $structure->id() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
                    echo "<input type='hidden' name='userpdf' value='no'>";
                    // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
                    echo "<input type='hidden' name='anneeref' value='" . $annerecherche . "'>";
                    $listeagent = "";
                    // echo "Avant le foreach <br>";
                    if (is_array($agentliste)) {
                        foreach ($agentliste as $agentkey => $agent) {
                            $listeagent = $listeagent . "," . $agent->agentid();
                        }
                    }
                    // echo "listeagent = $listeagent <br>";
                    // echo "agentliste Apres ="; print_r($agentliste); echo "<br>";
        
                    echo "<input type='hidden' name='listeagent' value='" . $listeagent . "'>";
                    echo "<input type='hidden' name='typepdf' value='listedemande'>";
                    echo "</form>";
                    echo "<a href='javascript:document.listedemandepdf_" . $structure->id() . ".submit();'>Liste des demandes en PDF</a>";
                    echo "<br>";
        
                    if (is_array($agentliste)) {
                        ksort($agentliste);
                        foreach ($agentliste as $agentkey => $agent) {
                            // echo "NOM de l'agent = " . $agent->nom() . "<br>";
                            echo $agent->soldecongeshtml($fonctions->anneeref() - $previous, TRUE);
                            if ($previous == 0)
                            {
                                echo $agent->affichecommentairecongehtml(true);
                            }
                            // echo "fonctions->anneeref() . fonctions->debutperiode() = " . $fonctions->anneeref() . $fonctions->debutperiode() . "<br>";
                            echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);

                            echo "<div id='planningagent_" . $agent->agentid() . "' class='divtocomplete'></div>";
?>
                            <script>
                                // Initialisation des paramètres pour l'affichage du planning de l'agent
                                var params = {  "methode" : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                                                "mode" : "<?php echo $mode; ?>",
                                                "agentid" : "<?php echo $agent->agentid(); ?>", 
                                                "structureid" : "<?php echo $structure->id() ?>",
                                                "datedebut" : "<?php echo ($fonctions->anneeref() - $previous) . $fonctions->debutperiode(); ?>" , 
                                                "datefin" : "<?php echo ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(); ?>",
                                                "clickable" : 'N',
                                                "showpdflink" : 'N',
                                                "includeteletravail" : 'O'
                                             }
                                // Appel de la fonction asynchrone 
                                showagentplanning(params,document.getElementById('planningagent_<?php echo $agent->agentid(); ?>'));
                            </script>
<?php                            
                            //echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);

                            echo "<hr>";
                        }
                    }
                    echo "<br>";
                }
            }
            else
            {
                $agent = new agent($dbcon);
                $agent->load($agentselect);
                echo "<br>";
                echo "Solde de l'agent " . $agent->identitecomplete() . " : <br>";
                echo "<form name='listedemandepdf_" . $agent->agentid() . "'  method='post' action='affiche_pdf.php' target='_blank'>";
                echo "<input type='hidden' name='userpdf' value='no'>";
                echo "<input type='hidden' name='anneeref' value='" . $annerecherche . "'>";
                echo "<input type='hidden' name='listeagent' value='" . $agent->agentid() . "'>";
                echo "<input type='hidden' name='typepdf' value='listedemande'>";
                echo "</form>";
                echo "<a href='javascript:document.listedemandepdf_" . $agent->agentid() . ".submit();'>Liste des demandes en PDF</a>";
                echo "<br>";
                echo $agent->soldecongeshtml(($fonctions->anneeref() - $previous), TRUE);
                if ($previous == 0)
                    echo $agent->affichecommentairecongehtml(true);
                    echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);

                echo "<div id='planningagent_" . $agent->agentid() . "' class='divtocomplete'></div>";
?>
                <script>
                    // Initialisation des paramètres pour l'affichage du planning de l'agent
                    var params = {  "methode" : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                                    "mode" : "<?php echo $mode; ?>",
                                    "agentid" : "<?php echo $agent->agentid(); ?>", 
                                    "structureid" : "<?php echo $agent->structureid() ?>",
                                    "datedebut" : "<?php echo ($fonctions->anneeref() - $previous) . $fonctions->debutperiode(); ?>" , 
                                    "datefin" : "<?php echo ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(); ?>",
                                    "clickable" : 'N',
                                    "showpdflink" : 'N',
                                    "includeteletravail" : 'O'
                                    }
                    // Appel de la fonction asynchrone 
                    showagentplanning(params,document.getElementById('planningagent_<?php echo $agent->agentid(); ?>'));
                </script>
<?php                            
                // echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
            }
            echo "<br>";
        }
    }

?>

<script>
    // window.addEventListener("load", (event) => {
    //     var waiting_img = document.getElementById('waiting_img');
    //     if (waiting_img)
    //     {
    //         waiting_img.hidden=true;
    //     }
    //     var waiting_div = document.getElementById('waiting_div');
    //     if (waiting_div)
    //     {
    //         waiting_div.hidden=true;
    //     }
    // });
</script>

</body>
</html>

