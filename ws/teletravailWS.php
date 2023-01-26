<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $erreur_curl = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
    
    
    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    //$statutvalide = array('PREPA' => alimentationCET::STATUT_PREPARE, 'COURS' => alimentationCET::STATUT_EN_COURS, 'REFUS' => alimentationCET::STATUT_REFUSE, 'SIGNE' => alimentationCET::STATUT_VALIDE, 'ABAND' => alimentationCET::STATUT_ABANDONNE);
    
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'POST': // Modifie le statut d'une demande d'alimentation
            $erreur = "Le mode POST n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            break;
        case 'GET':
            if (array_key_exists("esignatureid", $_GET)) // Retourne les informations liées à un droit d'option CET
            {
                $esignatureid = $_GET["esignatureid"];
                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va retourner les infos de la convention télétravail " . $esignatureid));
                $erreur = "";
                $teletravail = new teletravail($dbcon);
                if ("$esignatureid" == "" )
                {
                    $erreur = "Le paramètre esignatureid n'est pas renseigné.";
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la convention télétravail : " . $erreur));
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                }
                elseif (!$teletravail->loadbyesignatureid($esignatureid))
                {
                    $erreur = "Convention de télétravail $esignatureid non trouvée.";
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la convention télétravail " . $esignatureid));
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                }
                else
                {
                    $indexjour = 0;
                    $somme = 0;
                    $indexjour = 0;
                    for ($index = 0 ; $index < strlen($teletravail->tabteletravail()) ; $index ++)
                    {
                        $demijrs = substr($teletravail->tabteletravail(),$index,1);
                        if ($demijrs>0) // Si dans le tableau la valeur est > 0
                        {
                            if (($index % 2) == 0)  // Si c'est le matin => On ajoute 1 à la somme
                                $somme = $somme + 1;
                            elseif (($index % 2) == 1)  // Si c'est l'après-midi => On ajoute 2 à la somme
                                $somme = $somme + 2;
                        }
                        if (($index % 2) == 1)
                        {
                            if ($somme > 0) // Si pas de télétravail => On affiche rien
                            {
                                if ($somme == 1)  // Que le matin
                                    $infojour = $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment(fonctions::MOMENT_MATIN); // => intdiv($index,2)+1 car pour PHP 0 = dimanche et nous 0 = lundi
                                elseif ($somme == 2) // Que l'après-midi
                                    $infojour = $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment(fonctions::MOMENT_APRESMIDI);
                                elseif ($somme == 3) // Toute la journée
                                    $infojour = $fonctions->nomjourparindex(intdiv($index,2)+1) . " toute la journée";
                                else // Là, on ne sait pas !!
                                    $infojour = "Problème => index = $index  demijrs = $demijrs   somme = $somme";
                                
                                $indexjour++;
                                $information_jourteletravail[$indexjour] = array('name' => "jour" . $indexjour, 'description' => "Jour $indexjour de télétravail", 'value' => $infojour);
                            }
                            $somme = 0;
                        }
                    }
                    
                    $agent = new agent($dbcon);
                    if (!$agent->load($teletravail->agentid()))
                    {
                        $errlog = 'Agent inconnu';
                    }
                    else
                    {
                        // On calcule le nombre de jours de télétravail auquel l'agent à droit :
                        $affectation = null;
                        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y'));
                        if (count(array($affectationliste)) == 0)
                        {
                            $errlog = "Pas d'affectation pour cet agent";
                        }
                        else
                        {
                            $affectation = current($affectationliste);
                            if ($fonctions->testexistdbconstante('NBJOURSMAXTELETRAVAIL')) $nbjoursmaxteletravail = $fonctions->liredbconstante('NBJOURSMAXTELETRAVAIL');
                            $nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
                            $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail-($nbredemiTP*0.5);
                            if ($nbjoursmaxteletravailcalcule<0) $nbjoursmaxteletravailcalcule = 0;
                        
                            $information_typeconvention = array('name' => "typeconvention", 'description' => "Type de convention de télétravail", 'value' => $teletravail->typeconvention());
                            $information_nombrejours  = array('name' => "nombrejours", 'description' => "Nombre de jours de télétravail maximum", 'value' => "$nbjoursmaxteletravailcalcule");
                            $structure = new structure($dbcon);
                            if (!$structure->load($affectation->structureid()))
                            {
                                $errorlog = "Structure introuvable";
                            }
                        }
                    }
                    
                    $anneeref = "";
                    
                    if ($errlog != "")
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la convention télétravail " . $esignatureid . " => Erreur = " . $errlog));
                        $result_json = array('status' => 'Error', 'description' => $errlog);
                    }
                    else
                    {
                        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                        if (count(array($affectationliste)) > 0)
                        {
                            $affectation = new affectation($dbcon);
                            $affectation = current($affectationliste);
                            $infosLdap = $agent->getpersonnaladdress();
                            $nameStructComplete = $structure->nomcompletcet();
                            // quotité sur la période 01/09/N-1 - 31/08/N
                            $quotite = $affectation->quotite();
                            
                            $agent = array('uid' => $agent->agentid(),
                                'email' => $agent->mail(),
                                'name' => $agent->nom(),
                                'firstname' => $agent->prenom(),
                                'service' => array('name' => $nameStructComplete,
                                    'id' => $structure->id(),
                                    'addr' => $infosLdap[LDAP_AGENT_PERSO_ADDRESS_ATTR]."",
                                    'type' => $structure->typestruct()),
                                'ref_year' => $anneeref,
                                'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                                'corps' => $agent->typepopulation(),
                                'rifseep' => $agent->fonctionRIFSEEP()
                            );
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Lecture OK des infos de convention télétravail " . $esignatureid . " => Pas d'erreur"));
//                            $result_json = array('agent' => $agent, 'infosconvention' => $information_typeconvention, 'informations' => array('nbjours' => $information_nombrejours, 'infosjours' => $information_jourteletravail));
                            $result_json = array('agent' => $agent, 'infosconvention' => $information_typeconvention, 'informations' => array_merge(array($information_nombrejours), $information_jourteletravail));
                            //error_log(basename(__FILE__) . $fonctions->stripAccents(" Le json resutat => " . print_r($result_json,true)));
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de convention télétravail " . $esignatureid . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
                            $result_json = array('status' => 'Error', 'description' => "Impossible de déterminer la quotité de travail de l'agent.");
                        }
                    }
                }
            }
            elseif (array_key_exists("signRequestId", $_GET))  // Synchronisation d'une demande G2T avec le statut de eSignature
            {
                $status = "";
                $reason = "";
                $esignatureid = $_GET["signRequestId"];
                if ("$esignatureid" == "")
                {
                    $erreur = "Le paramètre esignature n'est pas renseigné.";
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" ERROR => " . $erreur));
                }
                else
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On va modifier le statut de la convention télétravail =>  " . $esignatureid));
                     
                    $curl = curl_init();
                    $params_string = "";
                    $opts = [
                        CURLOPT_URL => $eSignature_url . '/ws/signrequests/status/' . $esignatureid,
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
                        $erreur_curl = "Erreur dans eSignature (WS g2t) : ".$json;
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur_curl"));
                        // $result_json = array('status' => 'Error', 'description' => $erreur);
                        $status = teletravail::TELETRAVAIL_ANNULE;
                    }
                    else
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du WS signrequests en json"));
                        error_log(basename(__FILE__) . " " . var_export($json,true));
                        $current_status = str_replace("'", "", $json);  // json_decode($json, true);
                        
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du WS signrequests/status"));
                        error_log(basename(__FILE__) . " " . $current_status); // var_export($current_status,true));
                        
                        switch (strtolower($current_status))
                        {
                            //uploading, draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                            //           draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned, fully-deleted
                            case 'draft' :
                            case 'pending' :
                            case 'signed' :
                            case 'checked' :
                                $status = teletravail::TELETRAVAIL_ATTENTE;
                                break;
                            case 'refused':
                                $status = teletravail::TELETRAVAIL_REFUSE;
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$current_status' => On va chercher le commentaire"));
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
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl (récup commentaire) =>  " . $error));
                                }
                                $response = json_decode($json, true);
                                if (isset($response['comments']))
                                {
                                    $reason = '';
                                    foreach ($response['comments'] as $comment)
                                    {
                                        $reason = $reason . " " . $comment['text'];
                                    }
                                    $reason = trim($reason);
                                }                            
                                break;
                            case 'completed' :
                            case 'exported' :
                            case 'archived' :
                            case 'cleaned' :
                                $status = teletravail::TELETRAVAIL_VALIDE;
                                break;
                            case 'deleted' :
                            case 'canceled' :
                            case 'fully-deleted' :
                            case '' :
                                $status = teletravail::TELETRAVAIL_ANNULE;
                                break;
                            default :
                                $status = "";
                                $erreur = "";
                                $response = json_decode($current_status, true);
                                if (isset($response['error'])) $erreur = $response['error'];
                                $erreur = "Erreur dans la réponse de eSignature => eSignatureid = " . $esignatureid . " erreur => $erreur";
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" " . $erreur));
                                $result_json = array('status' => 'Error', 'description' => $erreur);
                                
                        }
                    }
                    if ($status <> '')
                    {
                        //$status = mb_strtolower("$status", 'UTF-8');
                        $teletravail = new teletravail($dbcon);
                        $erreur = $teletravail->loadbyesignatureid($esignatureid);
                        if ($erreur === false)
                        {
                            $erreur = "Erreur lors de la lecture des infos de la convention télétravail " . $esignatureid;
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" " . $erreur));
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" statut de la convention dans eSignature = $status -> " . $fonctions->teletravailstatutlibelle($status)));
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" teletravail->statut() = " . $teletravail->statut() . " -> " . $fonctions->teletravailstatutlibelle($teletravail->statut())));
                            
                            // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
                            if ($status == $teletravail->statut())
                            {
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" La convention a déjà un statut $status (" . $fonctions->teletravailstatutlibelle($status) . "). On ne fait rien => Pas d'erreur"));
                                $erreur = '';
                                $result_json = array('status' => 'Ok', 'description' => $erreur);
                            }
                            else // if (in_array($statut, array(teletravail::TELETRAVAIL_REFUSE, teletravail::TELETRAVAIL_VALIDE))) // Si le statut dans eSignature est REFUSE ou VALIDE
                            {
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" On passe le statut de la convention " . $esignatureid . " à $status (" . $fonctions->teletravailstatutlibelle($status) . ")"));
                                $teletravail->statut($status);
                                $teletravail->commentaire($reason);
                                $erreur = $teletravail->store();
                                if ($erreur != "")
                                {
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de l'enregistrement de la convention " . $esignatureid . " => Erreur = " . $erreur));
                                    $result_json = array('status' => 'Error', 'description' => $erreur);
                                }
                                else
                                {
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Traitement ok de la modification du statut de la convention " . $esignatureid . " => Pas d'erreur"));
                                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                $erreur = "Mauvais usage du WS mode GET => Les paramètres doivent être : signRequestId ou esignatureid";
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            break;
    }
    
    if ($erreur_curl <> '')
    {
        $result_json['status'] = 'Error';
        if (isset($result_json['description']))
        {
            $result_json['description'] = $result_json['description'] . " " . $erreur_curl;
        }
        else
        {
            $result_json['description'] = " " . $erreur_curl;
        }
    }
    
    
    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    
    //error_log(basename(__FILE__) . $fonctions->stripAccents(" " . print_r($result_json,true)));
    echo json_encode($result_json);
    


?>