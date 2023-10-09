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

//    ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");

    $path = $fonctions->imagepath() . "/chargement.gif";
    list($width, $height) = getimagesize("$path");
    $typeimage = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
    echo "<div id='waiting_div' class='waiting_div' ><img id='waiting_img' src='" . $base64 . "' height='$height' width='$width' ></div>";
    // On force l'affichage de l'image d'attente en vidant le cache PHP vers le navigateur

    if (ob_get_contents()!==false)
    {
        ob_end_flush();
        @ob_flush();
        flush();
        ob_start();
    }
    // Fin du forçage de l'affichage de l'image d'attente
    
    
    $htmltext = "";

    // On récupère la liste des agents en télétravail à la date du jour
    $agentlist = $fonctions->listeagentteletravail(date("d/m/Y"),date("d/m/Y"),false);
    //var_dump($agentlist);
    $agentlistparstructure = array();
    // On classe ces agents par structure
    foreach ((array)$agentlist as $agentid)
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);
        $agentlistparstructure[trim($agent->structureid()."")][$agentid] = $agent;
    }
    //var_dump($agentlistparstructure);

    $premierestructure = true;
    $javavalue = "";
    // Pour chaque structure, on récupère la liste des agents de cette structure avec une convention de télétravail
    foreach($agentlistparstructure as $codestruct => $agentlist)
    {
        $structure = new structure($dbcon);
        if (!$structure->load($codestruct))
        {
            // En théorie pas nécéssaire
            $structure->nomlong('STRUCTURE INCONNUE');
            $structure->nomcourt('INCONNUE');
        }

        if ($premierestructure)
        {
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $premierestructure = false;
        }

        $premiereconventionstructure = true;
        $nbconvention = 0;
        // Pour chaque agent
        foreach($agentlist as $agentid => $agent)
        {
            // On récupère la liste des conventions de télétravail de cet agent
            $teletravailliste = $agent->teletravailliste(date("d/m/Y"),date("d/m/Y"));
            // Pour toutes les conventions récupérées
            foreach ((array)$teletravailliste as $teletravailid)
            {
                $teletravail = new teletravail($dbcon);
                $teletravail->load($teletravailid);
                // Si la convention est validée on l'affiche
                if ($teletravail->statut()==teletravail::TELETRAVAIL_VALIDE)
                {

                    if ($premiereconventionstructure)
                    {
                        $nbcolonne = 6;
                        $htmltext = $htmltext . "<tr><td class='titresimple' colspan=$nbcolonne align=center>Convention de télétravail pour " . $structure->nomlong() . " (Structure Id = $codestruct ) <label id='label_$codestruct'></label></td></tr>";
                        $htmltext = $htmltext . "<tr align=center>"
                                . "<td class='cellulesimple'>Identifiant de l'agent</td>"
                                . "<td class='cellulesimple'>Identité de l'agent</td>"
                                . "<td class='cellulesimple'>Type de convention</td>"
                                . "<td class='cellulesimple'>Date de début</td>"
                                . "<td class='cellulesimple'>Date de fin</td>"
                                . "<td class='cellulesimple'>Jours en télétravail</td>";
                        $htmltext = $htmltext . "</tr>";
                        $premiereconventionstructure = false;
                    }
                    $htmltext = $htmltext . "<tr align=center>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $agent->agentid() . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $agent->identitecomplete() . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $teletravail->libelletypeconvention() . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datedebut()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datefin()) . "</td>";
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . $teletravail->libelletabteletravail() . "</td>";
                    $htmltext = $htmltext . "</tr>";
                    $nbconvention++;
                }
            }
        }
        // On construit le tableau associatif code_libelle => nbre_convention pour pouvoir ensuite mettre à jour les libellés dans un javascript
        if ($javavalue != "")
        {
            $javavalue = $javavalue . ",";
        }
        $javavalue = $javavalue . "'label_$codestruct' : '$nbconvention'";
    }
    if (!$premierestructure)
    {
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "<br>";
    }
    echo $htmltext;

?>
    <script>
        // Script permettant de modifier le nombre de conventions dans les labels
        var infoconvention = { <?php echo $javavalue; ?> };
        for (var labelid in infoconvention)
        {
            var structlabel = document.getElementById(labelid);
            if (structlabel)
            {
                structlabel.innerHTML = 'Nombre de conventions : ' + infoconvention[labelid];
            }
        }
    </script>

    <script>
        window.addEventListener("load", (event) => {
            var waiting_img = document.getElementById('waiting_img');
            if (waiting_img)
            {
                waiting_img.hidden=true;
            }
            var waiting_div = document.getElementById('waiting_div');
            if (waiting_div)
            {
                waiting_div.hidden=true;
            }
        });
    </script>    


</body>
</html>