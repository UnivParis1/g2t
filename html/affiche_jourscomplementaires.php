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
    $currentanneeref = substr($fonctions->anneeref()-1, 2, 2);
    for ($anneeref=$currentanneeref;$anneeref<=($currentanneeref+1);$anneeref++)
    {
        $agentlist = $fonctions->listeagentsavecjourscomplementaires($anneeref); // Complémentaire 2023/2024
        //var_dump($agentlist);
        $agentlistparstructure = array();
        foreach ((array)$agentlist as $agentid => $identite)
        {
            $agent = new agent($dbcon);
            $agent->load($agentid);
            $agentlistparstructure[$agent->structureid()][$agentid] = $agent;
        }
        //var_dump($agentlistparstructure);
/*
        echo "<HR>";

        foreach($agentlistparstructure as $codestruct => $agentlist)
        {
            $structure = new structure($dbcon);
            if (!$structure->load($codestruct))
            {
                // En théorie pas nécéssaire
                $structure->nomlong('INCONNUE');
                $structure->nomcourt('INCONNUE');
            }
            echo "Nom de la structure : " . $structure->nomlong() . "<br>";
            foreach($agentlist as $agentid => $agent)
            {
                $solde = new solde($dbcon);
                $solde->load($agentid, 'sup' . $anneeref);
                echo "L'agent " . $agent->identitecomplete() . " a " . $solde->droitaquis() . " acquis jours complémenentares - Il lui en reste " . ($solde->droitaquis() - $solde->droitpris()) . "<br>";
                $listecommentaireconge = $agent->listecommentaireconge('sup' . $anneeref);
                echo "<ul>";
                foreach($listecommentaireconge as $commentaireconge)
                {
                    echo "&nbsp;&nbsp;&nbsp;<li>Motif = " . $commentaireconge->commentaire . " nbjours = " . $commentaireconge->nbjoursajoute . "</li>";
                }
                echo "</ul>";
            }
        }
*/
        $premierestructure = true;
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
            $nbcolonne = 5;
            $htmltext = $htmltext . "<tr><td class='titresimple' colspan=$nbcolonne align=center>Congés complémentaires 20" . $anneeref ."/20" . ($anneeref+1) . " pour " . $structure->nomlong() . "</td></tr>";
            $htmltext = $htmltext . "<tr align=center>"
                    . "<td class='cellulesimple'>Identifiant de l'agent</td>"
                    . "<td class='cellulesimple'>Identité de l'agent</td>"
                    . "<td class='cellulesimple'>Nombre de jours acquis</td>"
                    . "<td class='cellulesimple'>Nombre de jours restants</td>"
                    . "<td class='cellulesimple'>Motif</td>";
            
            $htmltext = $htmltext . "</tr>";
                
            foreach($agentlist as $agentid => $agent)
            {
                $solde = new solde($dbcon);
                $solde->load($agentid, 'sup' . $anneeref);
                $htmltext = $htmltext . "<tr align=center>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $agent->agentid() . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $agent->identitecomplete() . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $solde->droitaquis() . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . ($solde->droitaquis() - $solde->droitpris()) . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple' align=left>";                                
                $listecommentaireconge = $agent->listecommentaireconge('sup' . $anneeref);
                $htmltext = $htmltext . "<ul>";
                foreach($listecommentaireconge as $commentaireconge)
                {
                    $auteur = new agent($dbcon);
                    $auteurtxt = "";
                    if ($commentaireconge->auteurid!="" and $auteur->load($commentaireconge->auteurid))
                    {
                        $auteurtxt = " (Par " . $auteur->identitecomplete() . ")";
                    }
                    $htmltext = $htmltext . "<li>" . $commentaireconge->commentaire . $auteurtxt . " (" . $commentaireconge->nbjoursajoute . " jours)</li>";
                }
                $htmltext = $htmltext . "</ul>";
                $htmltext = $htmltext . "</td></tr>";
            }
        }
        if (!$premierestructure)
        {
            $htmltext = $htmltext . "</table>";
            $htmltext = $htmltext . "<br>";
        }
    }
    echo $htmltext;

?>
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