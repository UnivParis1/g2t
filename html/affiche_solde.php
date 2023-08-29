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
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

/*
    function affichestructureliste($structure, $niveau = 0)
    {
        global $dbcon;
        global $structureid;
        global $fonctions;
        global $showall;
        // $fonctions = new fonctions($dbcon);
        if ($showall or ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))) {
            echo "<option value='" . $structure->id() . "'";
            if ($structure->id() == $structureid) {
                echo " selected ";
            }
            if ($fonctions->formatdatedb($structure->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                echo " style='color:red;' ";
            }
            echo ">";
            for ($cpt = 0; $cpt < $niveau; $cpt ++) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            echo " - " . $structure->nomlong() . " (" . $structure->nomcourt() . ")";
            echo "</option>";

            $sousstruclist = $structure->structurefille();
            foreach ((array) $sousstruclist as $keystruct => $soustruct) {
                affichestructureliste($soustruct, $niveau + 1);
            }
        }
    }
*/

    //echo "<br>" . print_r($_POST,true) . "<br>";

    ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
    $mode = $_POST["mode"];
    if ($mode == "")
        $mode = "resp";

    if (isset($_POST["structureid"]))
        $structureid = $_POST["structureid"];
    else
        $structureid = null;

    $previous = "";
    if (isset($_POST["previous"]))
        $previous = $_POST["previous"];
    if ($previous == 'yes')
        $previous = 1;
    else
        $previous = 0;
    
    $agentselect = '';
    if (isset($_POST["agentselect"]))
        $agentselect = $_POST["agentselect"];

    if ($mode == 'rh')
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
        echo "<select size='1' id='structureid' name='structureid'>";
        while ($result = mysqli_fetch_row($query)) {
            $struct = new structure($dbcon);
            $struct->load($result[0]);
            affichestructureliste($struct, 0);
        }
        echo "</select>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo " <input type='submit' name= 'Valid_struct' value='Soumettre' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='previous' value='no'>";
        echo "<br>";

        if (!is_null($structureid))
        {
            $struct = new structure($dbcon);
            $struct->load($structureid);
            echo "<br>";
            echo "Solde des agents de la structure : " . $struct->nomlong() . " (" . $struct->nomcourt() . ") <br>";
            $annerecherche = ($fonctions->anneeref() - $previous);
            $agentliste = $struct->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()));
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
    elseif (strcasecmp($mode, "resp") == 0) 
    {
        echo "Veuillez selectionner un agent :<br>";
        echo "<form name='formselect'  method='post'>";
        echo "<select id='agentselect' name='agentselect'>";
        echo "<option value=''>Tous les agents</option>";
        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure)
        {
            $annerecherche = ($fonctions->anneeref() - $previous);
            $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()));
            if (is_array($agentliste))
            {
                echo "<optgroup label='". $structure->nomcourt() ."'>";
                foreach ($agentliste as $agentkey => $agent)
                {
                    echo "<option value='" . $agent->agentid() . "'";
                    if ($agentselect == $agent->agentid())
                        echo " selected ";
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
            echo 'yes';
        else
            echo 'no';
        echo "'>";
        echo "<br>";
        echo "<input type='submit' name= 'valid_agent' value='Soumettre' >";
        echo "</form>";
        // Ligne de séparation
        echo "<hr>";
        
        if (isset($_POST['valid_agent']))
        {
            if ($agentselect == '')
            {
                $structureliste = $user->structrespliste();
                foreach ($structureliste as $structkey => $structure) 
                {
                    echo "<br>";
                    //$fonctions->time_elapsed("Début affichage structure " . $structure->nomcourt(), __METHOD__, true);
                    //echo "Solde des agents de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                    $annerecherche = ($fonctions->anneeref() - $previous);
                    
                    if ($user->agentid() == '937') ////// PATCH MONIQUE LIER - Ticket GLPI 145258
                    {
                        if ($structure->isincluded() and $structure->parentstructure()->responsable()->agentid()==$user->agentid())
                        {
                                continue;
                        }
                        $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'o');
                    }
                    else
                    {
                        $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()));
                    }

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
                                echo $agent->affichecommentairecongehtml(true);
                            echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);
                            echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
                            
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
                    echo $agent->affichecommentairecongehtml(true);
                echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);
                echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
            }
            echo "<br>"; 
        }
    } 
    else 
    {
        echo "Veuillez selectionner un agent :<br>";
        echo "<form name='formselect'  method='post'>";
        echo "<select id='agentselect' name='agentselect'>";
        echo "<option value=''>Tous les agents</option>";
        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure)
        {
            $annerecherche = ($fonctions->anneeref() - $previous);
            $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'n');
            if (is_array($agentliste))
            {
                echo "<optgroup label='". $structure->nomcourt() ."'>";
                foreach ($agentliste as $agentkey => $agent)
                {
                    echo "<option value='" . $agent->agentid() . "'";
                    if ($agentselect == $agent->agentid())
                        echo " selected ";
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
            echo 'yes';
        else
            echo 'no';
        echo "'>";
        echo "<br>";
        echo "<input type='submit' name= 'valid_agent' value='Soumettre' >";
        echo "</form>";
        // Ligne de séparation
        echo "<hr>";
                
        if (isset($_POST['valid_agent']))
        {
            if ($agentselect == '')
            {
                $structureliste = $user->structgestliste();
                foreach ($structureliste as $structkey => $structure) {
                    echo "<br>";
                    echo "Solde des agents de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                    $annerecherche = ($fonctions->anneeref() - $previous);
                    $agentliste = $structure->agentlist($fonctions->formatdate($annerecherche . $fonctions->debutperiode()), $fonctions->formatdate(($annerecherche + 1) . $fonctions->finperiode()),'n');
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
                        foreach ($agentliste as $agentkey => $agent) {
                            // echo "NOM de l'agent = " . $agent->nom() . "<br>";
                            echo $agent->soldecongeshtml($fonctions->anneeref() - $previous, TRUE);
                            if ($previous == 0)
                                echo $agent->affichecommentairecongehtml(true);
                            // echo "fonctions->anneeref() . fonctions->debutperiode() = " . $fonctions->anneeref() . $fonctions->debutperiode() . "<br>";
                            echo $agent->demandeslistehtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), $structure->id(), FALSE);
                            echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
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
                    echo $agent->planninghtml(($fonctions->anneeref() - $previous) . $fonctions->debutperiode(), ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode(), FALSE, FALSE,true);
            }
            echo "<br>";
        }
    }

?>

<!--
	<a href=".">Retour Ã  la page d'accueil</a>
-->
</body>
</html>

