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
    
    
    $esignatureid = null;
    $currentoption = null;
    $currentalim = null;
    $error = "";
    if (isset($_POST["esignatureid"]))
    {
        $valeur = explode('|',$_POST["esignatureid"]);
        $esignatureid = $valeur[1];
        if (strcasecmp((string)$valeur[0],'opt')==0)  // Si c'est une option
        {
            $fonctions->synchroniseoptionCET($esignatureid);

            $currentoption = new optionCET($dbcon);
            $currentoption->load($esignatureid);
        }
        elseif (strcasecmp((string)$valeur[0],'alim')==0) // Si c'est une alimentation
        {
            $fonctions->synchronisealimentationCET($esignatureid);
            
            $currentalim = new alimentationCET($dbcon);
            $currentalim->load($esignatureid);
        }
        else
        {
            $esignatureid = null;
            $error = "Impossible de déterminer si c'est une option ou une alimentation.<br><br>";
        }
    }
    
    $anneecampagne = $fonctions->anneeref();
    if (isset($_POST["anneecampagne"]))
    {
        $anneecampagne = $_POST["anneecampagne"];
    }
        
    $user = new agent($dbcon);
    $user->load($userid);
    
    require ("includes/menu.php");
    $path = $fonctions->imagepath() . "/chargement.gif";
    list($width, $height, $imagetype) = getimagesize("$path");
    $typeimage = image_type_to_extension($imagetype,false);
    if ($typeimage===false) // Si on n'a pas pu déterminé le type d'image => On récupère l'extension du fichier
    {
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents("imagetype = $imagetype => extension non définie"));
        $typeimage = pathinfo($path, PATHINFO_EXTENSION);
    }

    $data = file_get_contents($path);
    $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
    echo "<div id='waiting_div' class='waiting_div' ><img id='waiting_img'  class='waiting_img' src='" . $base64 . "' height='$height' width='$width' ></div>";
    // On force l'affichage de l'image d'attente en vidant le cache PHP vers le navigateur
    if (ob_get_contents()!==false)
    {
        ob_end_flush();
        @ob_flush();
        flush();
        ob_start();
    }
    // Fin du forçage de l'affichage de l'image d'attente
    
    //echo "<br>" . print_r($_POST,true) . "<br>";

    echo $fonctions->showmessage(fonctions::MSGERROR, $error);
    echo "<form name='demandeesignatureid'  method='post' action='affiche_demandeCET.php' >";
    echo "Période de la campagne CET : <br>";
    $anneeref = $fonctions->anneeref();
    echo "<select size='1' name='anneecampagne' id='anneecampagne'>";
    for ($annee = $anneeref-3 ; $annee <= $anneeref ; $annee++ )
    {
        echo "<option value='" . $annee . "' ";
        if ($anneecampagne == $annee)
        {
            echo " selected='selected' ";
        }
        echo ">" . $annee . "/" . ($annee+1) . "</option>";
    }
    echo "</select>";
    echo "<br>";
    
    
    echo "Numéro eSignature à afficher : <br>";    
    echo "<select size='1' name='esignatureid' id='esignatureid'>";
    echo "<optgroup label='Demandes d&apos;alimentation'>";
//    echo "<option value='Demande alimentation' disabled>Demande d'alimentation</option>";
    $alimCETliste = $fonctions->get_alimCET_liste('ann' . substr($anneecampagne-1,2,2),array(),false);
    //echo "On a récup <br>";
    foreach ($alimCETliste as $alimid => $externalid)
    {
        //echo "Dans la boucle alim <br>";
        if (trim($externalid . "") != "")
        {
            $alimCET = new alimentationCET($dbcon);
            $alimCET->load(null,$alimid);
            //echo "Apres le load alim <br>";
            echo "<option value='alim|" . $externalid . "' ";
            if ($externalid == $esignatureid)
            {
                echo " selected='selected' ";
            }
            $demandeur = new agent($dbcon);
            $demandeur->load($alimCET->agentid());
            echo ">" . $alimCET->esignatureid() . " => " . $demandeur->identitecomplete() . " (Statut = " . $alimCET->statut()  . ")</option>";
        }
    }
    $optionCETliste = $fonctions->get_optionCET_liste($anneecampagne,array(),false);
    echo "<optgroup label='Demandes d&apos;option'>";
//    echo "<option value='Demande option' disabled>Demande d'option</option>";
    foreach ($optionCETliste as $optionid => $externalid)
    {
        if (trim($externalid . "") != "")
        {
            $optionCET = new optionCET($dbcon);
            $optionCET->load(null,$optionid);
            echo "<option value='opt|" . $externalid . "' ";
            if ($externalid == $esignatureid)
            {
                echo " selected='selected' ";
            }
            $demandeur = new agent($dbcon);
            $demandeur->load($optionCET->agentid());
            echo ">" . $optionCET->esignatureid() . " => " . $demandeur->identitecomplete() . " (Statut = " . $optionCET->statut()  . ")</option>";
        }
    }
    echo "</select>";
    //echo "<input id='esignatureid' name='esignatureid' placeholder='Id. eSignature' value='$esignatureid' size=40 />";
    echo "<br>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";
    $optionCET = null;
    $alimCET = null;

    if (!is_null($esignatureid))
    {
        echo "Le numéro eSignatureid = $esignatureid <br>";

        $esignature = new esignature($dbcon);
        $response = $esignature->get_signrequest($esignatureid);

        if (is_string($response))
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $response"));
            echo "<br>$response <br><br>";
        }
        else
        {
            $creatorname = "";

            $creationdate = "";
            $esignature->get_signrequest_creationinfo($esignatureid, $creatorname, $creationdate);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Créateur : $creatorname"));
            echo "<br><br>Créateur : $creatorname <br>";
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Date de création : " . $creationdate));
            echo "Date de création : " . trim($creationdate) . '<br>'; 

            $currentstatus = $esignature->get_signrequest_status($esignatureid);
            $recipienttab = $esignature->get_signrequest_recipients($esignatureid);

            error_log(basename(__FILE__) . $fonctions->stripAccents(" Statut de la demande : " . $currentstatus));
            echo "Statut de la demande : " . $currentstatus . "<br>";
            echo "<br>";

            $nextstep = null;

            foreach($recipienttab as $numstep => $step)
            {
                $signedstep = false;
                echo "<B>Etape " . ($numstep+1) . " : </B><br>";
                foreach ($step as $esignaturerecipient)
                {
                    $datesignature = '';
                    $action = '';

                    // var_dump($esignaturerecipient);
                    // var_dump($signedrecipienttab["$numstep"]);

                    if  ($esignaturerecipient->hassigned)
                    {
                        echo " <span class='greentext'>";
                        $signedstep = true;

                        $datesignature = $esignaturerecipient->actiondate;
                        $action = $esignaturerecipient->action;
                    }

                    echo "&emsp;" . $esignaturerecipient->prenom . " " . $esignaturerecipient->nom . " (" . $esignaturerecipient->mail . ") $datesignature $action<br>";
                    if  ($esignaturerecipient->hassigned)
                    {
                        echo " </span>";
                    }
                }
                // Si c'est la première étape qui n'a pas de signature => On mémorise cette étape comme la prochaine étape attendue
                if ($signedstep==false and is_null($nextstep))
                {
                    $nextstep = $numstep;
                }
            }
            echo "<br>";
            echo "<B>En attente de l'étape : ";
            if ($currentstatus == 'pending' and !is_null($nextstep))
            {
                echo ($nextstep+1) . "</B><br>";
                $step = $recipienttab[$nextstep];
                foreach ($step as $esignaturerecipient)
                {
                    echo "&emsp;" . $esignaturerecipient->prenom . " " . $esignaturerecipient->nom . " (" . $esignaturerecipient->mail . ")<br>";
                }
            }
            else
            {
                echo "Pas d'étape en attente (circuit terminé)</B><br>";
            }
            echo "<br><br>";
        }
        
        echo "<B>Affichage des informations sur la demande dans la base G2T :</B><br>";
        if (!is_null($currentalim))
        {
            echo "&emsp;Valeur A = " . $currentalim->valeur_a() . "<br>";
            echo "&emsp;Valeur B = " . $currentalim->valeur_b() . "<br>";
            echo "&emsp;Valeur C = " . $currentalim->valeur_c() . "<br>";
            echo "&emsp;Valeur D = " . $currentalim->valeur_d() . "<br>";
            echo "&emsp;Valeur E = " . $currentalim->valeur_e() . "<br>";
            echo "&emsp;Valeur F = " . $currentalim->valeur_f() . "<br>";
            echo "&emsp;Valeur G = " . $currentalim->valeur_g() . "<br>";
            echo "&emsp;Motif du refus : " . $currentalim->motif() . "<br>";
        }
        else
        {
            echo "&emsp;Valeur A = " . $currentoption->valeur_a() . "<br>";
            echo "&emsp;Valeur G = " . $currentoption->valeur_g() . "<br>";
            echo "&emsp;Valeur H = " . $currentoption->valeur_h() . "<br>";
            echo "&emsp;Valeur I = " . $currentoption->valeur_i() . "<br>";
            echo "&emsp;Valeur J = " . $currentoption->valeur_j() . "<br>";
            echo "&emsp;Valeur K = " . $currentoption->valeur_k() . "<br>";
            echo "&emsp;Valeur L = " . $currentoption->valeur_l() . "<br>";
            echo "&emsp;Motif du refus : " . $currentoption->motif() . "<br>";
        }
        echo "<br><br>";
        
        // On appelle le WS eSignature pour récupérer le document correspondant à la demande

        $esignature = new esignature($dbcon);
        $pdf = '';
        $error = $esignature->get_signrequest_document($esignatureid, $pdf);
        if ($error != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $error"));
            echo $error . '<br><br>';
        }
        else
        {
            $encodage = base64_encode($pdf);
            
            echo "On affiche dans l'iFrame le document de la demande eSignature : $esignatureid <br><br>";
            echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
            echo "</iframe>";
            
        }
        
    }
    
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


