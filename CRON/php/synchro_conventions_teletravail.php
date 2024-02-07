<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    echo "Début de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);

    $full_g2t_ws_url = $fonctions->get_g2t_ws_url() . "/teletravailWS.php";
    $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
    
    // On appelle le WS G2T en GET pour demander à G2T de mettre à jour la demande
    $curl = curl_init();
    $params_string = "";
    
    $paramWS = "status=" . teletravail::TELETRAVAIL_ATTENTE . "," . teletravail::TELETRAVAIL_VALIDE;
    echo "Les paramètres du WS $full_g2t_ws_url sont : $paramWS \n";
    
    $opts = [
        CURLOPT_URL => $full_g2t_ws_url . "?" . $paramWS,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_PROXY => ''
    ];
    curl_setopt_array($curl, $opts);
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_PROXY, '');
    curl_setopt($curl, CURLOPT_TIMEOUT,500); // 500 seconds
    //echo "<br>CURLOPT_PROXY => " . curl_getinfo($curl,CURLOPT_PROXY) . "<br><br>";
    $json = curl_exec($curl);
    $error = curl_error ($curl);
    curl_close($curl);
    if ($error != "")
    {
        echo "Erreur Curl (synchro convention teletravail) = " . $error . "\n";
        exit();
    }
    //echo "<br>Le json (synchro_g2t_eSignature) " . print_r($json,true) . "<br>";
    $response = json_decode($json, true);
    //echo "<br>La reponse (synchro_g2t_eSignature) " . print_r($response,true) . "<br>";
    if (isset($response['description']))
    {
        echo "Fin du traitement du WS " . $response['status'] . " - " . $response['description'] . "\n";
//        error_log(basename(__FILE__) . $fonctions->stripAccents(" La réponse du WS $full_g2t_ws_url est => " . $response['status'] . " - " . $response['description'] ));
    }
    else
    {
        echo "La réponse du WS n'est pas conforme : " . var_export($response, true) . "\n";
//        error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du webservice G2T non conforme (URL WS G2T = $full_g2t_ws_url) => Erreur : " . var_export($response, true) ));
    }
    
    
    echo "Fin de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
        
    echo "Envoi du mail de rappel aux agents suivants qui doivent signer la convention \n";
    
    $cronagent = new agent($dbcon);
    if (!$cronagent->load(SPECIAL_USER_IDCRONUSER))
    {
        echo "Impossible de charger l'utilisateur CRON";
    }
    else
    {
        $tabdestinataireesignature = array();
        $tabdestinataireg2t = array();
        $tabconvention = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE);
        $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
        foreach($tabconvention as $convention)
        {
            $esignatureid = $convention->esignatureid();
            if ($esignatureid <>'' and $esignatureid>0)
            {
                echo "La convention (eSingatureid = $esignatureid) est dans eSignature => On récupère les informations \n";
                // On interroge le WS eSignature /ws/signrequests/{id}
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
                    error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl (récup niveau signature) =>  " . $error));
                }
                else
                {
                    $response = json_decode($json, true);
                    // parentSignBook->liveWorkflow->currentStepNumber

                    if (isset($response['parentSignBook']['liveWorkflow']['currentStepNumber']))
                    {
                        $currentstepnumber = $response['parentSignBook']['liveWorkflow']['currentStepNumber'];
                        echo "Le currentstepnumber = $currentstepnumber dans la convention $esignatureid \n";
                        if (isset($response['parentSignBook']['liveWorkflow']['liveWorkflowSteps']))
                        {
                            $liveworkflowsteps = $response['parentSignBook']['liveWorkflow']['liveWorkflowSteps'];
                            $nbworkflowsteps = count($liveworkflowsteps);
                            echo "nbworkflowsteps = $nbworkflowsteps \n";
                            if ($currentstepnumber <= $nbworkflowsteps -2)  // On ne traite pas les deux derniers niveaux de signature
                            {
                                $currentstep = $liveworkflowsteps[$currentstepnumber-1]; // L'index comence à 0
                                foreach($currentstep['recipients'] as $recipient)
                                {
                                    $recipientuser = $recipient['user'];
                                    echo "Recipient nom => " . $recipientuser['name'] . " " . $recipientuser['firstname'] . "   eppn = " . $recipientuser['eppn'] . "  mail = " . $recipientuser['email'] . " \n";
                                    $destinataire = new agent($dbcon);
                                    if (!$destinataire->loadbyemail($recipientuser['email']))
                                    {
                                        echo "Envoi impossible au destinataire " . $recipientuser['email'] . "\n";
                                    }
                                    else
                                    {
                                        echo "Le desitnataire est : " . $destinataire->identitecomplete() . " \n";
                                        $tabdestinataireesignature[$destinataire->agentid()] = $destinataire;
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        echo "Impossible de déterminer le currentstep \n";
                    }
                }
            }
            elseif ($convention->statutresponsable() == teletravail::TELETRAVAIL_ATTENTE)
            {
                echo "Le responsable n'a pas complete la convention. \n";
                $agent = new agent($dbcon);
                $agent->load($convention->agentid());
                $responsable = $agent->getsignataire();
/*                
                //$structure = new structure($dbcon);
                //$structure->load($agent->structureid());
                //if ($structure->responsable()->agentid() == $agent->agentid())
                //{
                //    $responsable = $structure->resp_envoyer_a($codeinterne);
                //}
                //else
                //{
                //    $responsable = $structure->agent_envoyer_a($codeinterne);
                //}
 */
                if (is_null($responsable) or $responsable===false)
                {
                    echo "On n'envoie pas de rappel au responsable de l'agent => car il n'est pas défini \n";
                }
                else
                {
                    echo "On envoie un rappel au responsable de l'agent => Reponsable = " . $responsable->identitecomplete() . " \n";
                    $tabdestinataireg2t[$responsable->agentid()] = $responsable;
                }
            }
            else
            {
                echo "Pas d'identifiant eSignature et le statut du responsable n'est pas 'en attente' (convention " . $convention->teletravailid()  . ") => Pas de traitement \n";
            }
        }
        
        foreach($tabdestinataireg2t as $destinataire)
        {
            echo "On va envoyer un mail au responsable " . $destinataire->identitecomplete() . " car il n'a pas complete la convention dans G2T.\n";
            $cronagent->sendmail($destinataire,
                                 "Convention de télétravail à compléter dans G2T", 
                                 "Vous avez une ou plusieurs conventions de télétravail à compléter dans G2T.\nVous pouvez les consulter dans votre menu 'Responsable' ou 'Gestionnaire'.\n"
                                 );
        }
        foreach($tabdestinataireesignature as $destinataire)
        {
            echo "On va envoyer un mail a l'agent " . $destinataire->identitecomplete() . " car il n'a pas signe/vise une convention dans eSignature ($eSignature_url).\n";
            $cronagent->sendmail($destinataire->mail(),
                                 "Convention de télétravail à signer/viser dans eSignature", 
                                 "Vous avez une ou plusieurs conventions de télétravail à signer/viser dans eSignature.\nVous pouvez les consulter directement à l'adresse suivante : <a href='$eSignature_url'>$eSignature_url</a>.\n\nCordialement.\n" . $cronagent->identitecomplete() . "\n"
//                                 "Vous avez une ou plusieurs conventions de télétravail à signer/viser dans eSignature.\nVous pouvez les consulter directement à l'adresse suivante : $eSignature_url.\n\nCordialement.\n" . $cronagent->identitecomplete() . "\n"
                                 );
        }
        
    }
    echo "Fin de l'envoi du mail de rappel aux agents suivants qui doivent signer la convention \n";
	
?>