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
    
    $esignatureid = null;
    if (isset($_POST["esignatureid"]))
        $esignatureid = $_POST["esignatureid"];
        
    $user = new agent($dbcon);
    $user->load($userid);
    
    require ("includes/menu.php");
    
    //echo "<br>" . print_r($_POST,true) . "<br>";

    echo "<form name='demandeesignatureid'  method='post' action='affiche_demandeCET.php'>";
    echo "Numéro eSignature à afficher : <br>";
    echo "<select name='esignatureid' id='esignatureid'>";
    echo "<optgroup label='Demandes d''alimentation'>";
//    echo "<option value='Demande alimentation' disabled>Demande d'alimentation</option>";
    $alimCETliste = $fonctions->get_alimCET_liste('ann' . substr($fonctions->anneeref()-1,2,2));
    //echo "On a récup <br>";
    foreach ($alimCETliste as $alimid)
    {
        //echo "Dans la boucle alim <br>";
        $alimCET = new alimentationCET($dbcon);
        $alimCET->load($alimid);
        //echo "Apres le load alim <br>";
        echo "<option value='" . $alimid . "' ";
        if ($alimid == $esignatureid)
        {
            echo " selected='selected' ";
        }
        $demandeur = new agent($dbcon);
        $demandeur->load($alimCET->agentid());
        echo ">" . $alimCET->esignatureid() . " => " . $demandeur->identitecomplete() . " (Statut = " . $alimCET->statut()  . ")</option>";
    }
    $optionCETliste = $fonctions->get_optionCET_liste($fonctions->anneeref());
    echo "<optgroup label='Demandes d''option'>";
//    echo "<option value='Demande option' disabled>Demande d'option</option>";
    foreach ($optionCETliste as $optionid)
    {
        $optionCET = new optionCET($dbcon);
        $optionCET->load($optionid);
        echo "<option value='" . $optionid . "' ";
        if ($optionid == $esignatureid)
        {
            echo " selected='selected' ";
        }
        $demandeur = new agent($dbcon);
        $demandeur->load($alimCET->agentid());
        echo ">" . $optionCET->esignatureid() . " => " . $demandeur->identitecomplete() . " (Statut = " . $optionCET->statut()  . ")</option>";
    }
    echo "</select>";
    //echo "<input id='esignatureid' name='esignatureid' placeholder='Id. eSignature' value='$esignatureid' size=40 />";
    echo "<br>";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<input type='submit' value='Soumettre' >";
    echo "</form>";

    if (!is_null($esignatureid))
    {
        echo "Le numéro eSignatureid = $esignatureid <br>";
        $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
        $error = '';
/*
        // On appelle le WS eSignature pour récupérer les infos du Workflow
        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/forms/get-datas/' . $esignatureid,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if (stristr(substr($json,0,20),'HTML') === false)
        {
            if ($error != "")
            {
                $erreur = "Erreur Curl =>  " . $error;
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
            }
            else // Tout va bien !
            {
                //echo "<br><pre>";
                //var_dump($json);
                //echo "</pre><br>";
                $response = json_decode($json, true);
                if (isset($response['error']))
                {
                    $erreur = "La réponse json est une erreur ==> On doit la retourner : " . $response['error'];
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
                }
                else // Tout est ok => on va récupérer les données du workflow
                {
                    echo "<br><pre>";
                    var_dump($response);
                    echo "</pre><br>";
                    // => A voir
                }
            }
        }
        else
        {
            $erreur = "Erreur dans eSignature : \n\t |  ".$json;
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
        }
*/        
        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if ($error != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
            echo "Erreur CURL (récup data) => $error <br>";
        }
        $response = json_decode($json, true);
/*
        echo "<br><pre>";
        var_dump($response);
        echo "</pre><br>";
*/
        if (is_null($response))
        {
            $erreur = "La réponse json est null => Demande introuvable ??";
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
            echo "<br>$erreur <br>";
        }
        elseif (isset($response['error']))
        {
            $erreur = "La réponse json est une erreur : " . $response['error'];
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
            echo "<br>$erreur <br>";
        }
        else // Tout est ok => on va récupérer les données du workflow
        {
            echo "<br><br>Créateur : " . $response["parentSignBook"]["createBy"]["firstname"] . " " . $response["parentSignBook"]["createBy"]["name"] . "<br>";
            echo "Date de création : " . date("d/m/Y H:i:s", substr($response["parentSignBook"]["createDate"],0,strlen($response["parentSignBook"]["createDate"])-3)) . "<br>";
            echo "Statut de la demande : " . $response["parentSignBook"]["status"] . "<br>";
            echo "<br>";
            foreach ($response["parentSignBook"]["liveWorkflow"]["liveWorkflowSteps"] as $numstep => $step)
            {
                echo "<B>Etape " . ($numstep+1) . " : </B><br>";
                foreach ($step["recipients"] as $esignatureuser)
                {
                    echo "&emsp;" . $esignatureuser["user"]["firstname"] . " " . $esignatureuser["user"]["name"] . " (" . $esignatureuser["user"]["email"] . ")<br>";
                }
            }
            echo "<br>";
            $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
            echo "<B>En attente de l'étape : " . $response['parentSignBook']['liveWorkflow']["currentStepNumber"] . "</B><br>";
            foreach ((array)$currentstep['recipients'] as $recipient)
            {
                echo "&emsp;" . $recipient['user']['firstname'] . " " . $recipient['user']['name'] . " (" . $recipient['user']["email"] . ")<br>";
                //echo "&emsp;Nom de l'étape : " . $currentstep['workflowStep']["description"] . "<br>";
            }
            echo "<br><br>";
        }
        
        // On appelle le WS eSignature pour récupérer le document correspondant à la demande
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/signrequests/get-last-file/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $pdf = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if ($error != "")
        {
            $error = "Erreur Curl (récup PDF) =>  " . $error;
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $error"));
            echo $error . '<br><br>';
        }
        if (stristr(substr($pdf,0,10),'PDF') === false)
        {
            $error = "Le WS n'a pas retourné un fichier PDF";
            $error = "Erreur Curl (récup PDF) =>  " . $error;
            error_log(basename(__FILE__) . $fonctions->stripAccents(" $error"));
            echo $error . '<br><br>';
        }
        
        if ($error == '')
        {
            $encodage = base64_encode($pdf);
            
            echo "On affiche dans l'iFrame le document de la demande eSignature : $esignatureid <br><br>";
            echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
            echo "</iframe>";
            
        }
        
    }
    
?>

</body>
</html>


