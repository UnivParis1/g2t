<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');

    
    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    $statutvalide = array('PREPA' => alimentationCET::STATUT_PREPARE, 'COURS' => alimentationCET::STATUT_EN_COURS, 'REFUS' => alimentationCET::STATUT_REFUSE, 'SIGNE' => alimentationCET::STATUT_VALIDE, 'ABAND' => alimentationCET::STATUT_ABANDONNE);
    
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'POST': // Modifie le statut d'une demande d'alimentation
            $erreur = "Le mode POST n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            break;
        case 'GET': 
            if (array_key_exists("esignatureid", $_GET)) // Retourne les informations liées à une demande d'alimentation
            {
                $esignatureid = $_GET["esignatureid"];
                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va retourner les infos de la demande " . $esignatureid));
                if ("$esignatureid" == "" )
                {
                    $erreur = "Le paramètre esignatureid n'est pas renseigné.";
                }
                else
                {
                    $alimentationCET = new alimentationCET($dbcon);
                    $erreur = $alimentationCET->load($esignatureid);
                }
                if ($erreur != "")
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid . " => Erreur = " . $erreur));
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                }
                else
                {
                    // On crée la réponse JSon correspondant à l'alimentation CET
                    $result_json = $fonctions->alimentationCETjsonresponse($alimentationCET);
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
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On va modifier le statut de la demande =>  " . $esignatureid));

                    ///////////////////////////////////////////////////////
                    //// A REVOIR !!!!
                    // if (isset($_GET["status"]))
                    // {
                    //     $current_status = $_GET["status"];
                    // }
                    // if (isset($_GET["comment"]))
                    // {
                    //     $reason = $_GET["comment"];
                    // }
                    //////////////////////////////////////////////////

                    $esignature = new esignature($dbcon);
                    $response3 = $esignature->get_signrequests($esignatureid);

                    if (is_string($response3))
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" $response3"));
                        $result_json = array('status' => 'Error', 'description' => $response3);
                    }
                    else
                    {
                        $esignature = new esignature($dbcon);
                        $response = $esignature->get_data($esignatureid);
                        
                        if (is_string($response))
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" $response"));
                            $result_json = array('status' => 'Error', 'description' => $response);    
                        }
                        else
                        {
                            if (isset($response3["parentSignBook"]["status"]))
                            {
                                $current_status = $response3["parentSignBook"]["status"];
                            }
                            else
                            {
                                $current_status = '';
                            }

                            // if (isset($response['form_completed_date']))
                            // {
                            //     $date_status = $response['form_completed_date'];
                            // }
                            // else
                            // {
                            //     $date_status = date("d/m/Y H:i:s");
                            // }

                            $alimentationCET = new alimentationCET($dbcon);
                            $validation = alimentationCET::STATUT_INCONNU;
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" On va faire la récupération des données."));
                            $decision_found = false;
                            foreach((array)$response as $key => $value)
                            {
                                //if (preg_match("/form_data_d.+cision/i",$key))
                                if (stristr(strtolower($key),"form_data_d")!==false and stristr(strtolower($key),"cision")!==false) //   preg_match("/form_data_d.+cision/i",$key))
                                {
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La clé $key correspond à la recherche."));
                                    $decision_found = true;
                                    if (strcasecmp((string)$value,'yes')==0)  // if ($response['form_data_decision'] == 'yes')
                                    {
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée form_data_decision vaut YES."));
                                        $validation = alimentationCET::STATUT_VALIDE;
                                        break;
                                    }
                                    elseif (strcasecmp((string)$value,'no')==0)  // elseif ($response['form_data_decision'] == 'no')
                                    {
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée form_data_decision vaut NO."));
                                        $validation = alimentationCET::STATUT_REFUSE;
                                        if (isset($response['form_data_motifrefus']))
                                        {
                                            error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée form_data_motifrefus existe."));
                                            $reason = $response['form_data_motifrefus'];
                                        }
                                        break;
                                    }
                                    else
                                    {
                                        $validation = alimentationCET::STATUT_INCONNU;
                                    }
                                }
                            }

                            switch (strtolower($current_status))
                            {
                                //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                                case 'draft' :
                                case 'pending' :
                                case 'signed' :
                                case 'checked' :
                                    $status = alimentationCET::STATUT_EN_COURS;
                                    break;
                                case 'refused':
                                    $status = alimentationCET::STATUT_REFUSE;
                                    // Récupération du commentaire d'esignature

                                    // if (isset($response3['comments'][0]['text']))
                                    // {
                                    //     $reason = $response3['comments'][0]['text'];
                                    // }
									if (isset($response3['comments']))
									{
										$reason = '';
										foreach ($response3['comments'] as $comment)
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
                                    if ($decision_found)
                                    {
                                        if ($validation == alimentationCET::STATUT_VALIDE)
                                        {
                                            $status = alimentationCET::STATUT_VALIDE;
                                        }
                                        elseif ($validation == alimentationCET::STATUT_REFUSE)
                                        {
                                            $status = alimentationCET::STATUT_REFUSE;
                                        }
                                        else
                                        {
                                            $status = alimentationCET::STATUT_INCONNU;
                                        }
                                    }
                                    else
                                    {
                                        $status = alimentationCET::STATUT_VALIDE;
                                    }
                                    break;
                                case 'deleted' : // TODO : Attention le document est dans la corbeille
                                case 'canceled' :
                                case '' :
                                    $status = alimentationCET::STATUT_ABANDONNE;
                                    break;
                                default :
                                    $status = alimentationCET::STATUT_INCONNU;
                                    break;
                            }
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Le status de la demande $esignatureid est : $status car la validation est : $validation "));
                            //$status = mb_strtolower("$status", 'UTF-8');

                            $erreur = $alimentationCET->load($esignatureid);
                            if ($erreur != "")
                            {
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid . " => Erreur = " . $erreur));
                                $result_json = array('status' => 'Error', 'description' => $erreur);
                            }
                            else
                            {
                                //if ($status == mb_strtolower($alimentationCET::STATUT_VALIDE, 'UTF-8'))
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" status = $status"));
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" alimentationCET->statut() = " . $alimentationCET->statut()));

                                // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
                                if ($status == $alimentationCET->statut())
                                {
                                    $erreur = '';
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La demande a déjà un statut $status. On ne fait rien => Pas d'erreur"));
                                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                                }
                                // Ajout d'un contrôle qui interdit de modifier le statut de la demande, les informations de solde si la demande est déjà VALIDE, ABANDONNE ou REFUSE
                                elseif ($alimentationCET->statut() <> alimentationCET::STATUT_VALIDE
                                    and $alimentationCET->statut() <> alimentationCET::STATUT_ABANDONNE
                                    and $alimentationCET->statut() <> alimentationCET::STATUT_REFUSE)
                                {
                                    if (($status == $alimentationCET::STATUT_VALIDE) and ($alimentationCET->statut() == $alimentationCET::STATUT_EN_COURS or $alimentationCET->statut() == $alimentationCET::STATUT_PREPARE))
                                    {
                                        $agent = new agent($dbcon);
                                        $agentid = $alimentationCET->agentid();
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" L'agent id =  " . $agentid ));
                                        $agent->load($agentid);
                                        $cet = new cet($dbcon);
                                        $erreur = $cet->load($agentid);
                                        if ($erreur <> '')
                                        {
                                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Pas de CET pour cet agent : " . $agent->identitecomplete() ." ! On le crée. "));
                                            unset($cet);
                                            $cet = new cet($dbcon);
                                            $cet->agentid($agentid);
                                            $cet->cumultotal('0');
                                            $cet->cumulannuel($fonctions->anneeref(),'0');
                                            $cet->datedebut('01/01/1900');   //date('d/m/Y'));
                                            $erreur = $cet->store();
                                            unset($cet);
                                            $cet = new cet($dbcon);
                                            $cet->load($agentid);
                                        }
                                        $cet->cumultotal( $alimentationCET->valeur_f() + $cet->cumultotal()) ;
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde du CET sera après enregistrement de " . $cet->cumultotal()));
                                        $cumulannuel = $cet->cumulannuel($fonctions->anneeref());
                                        $cumulannuel = $cumulannuel + $alimentationCET->valeur_f();
                                        $cet->cumulannuel($fonctions->anneeref(),$cumulannuel);
                                        $cet->store();

                                        $solde = new solde($dbcon);
                                        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Le type de congés est " . $alimentationCET->typeconges()));
                                        $solde->load($agentid, $alimentationCET->typeconges());
                                        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est avant : " . $solde->droitpris() . " et valeur_f = " . $alimentationCET->valeur_f()));
                                        $new_solde = $solde->droitpris()+$alimentationCET->valeur_f();
                                        $solde->droitpris($new_solde);
                                        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est après : " . $solde->droitpris()));
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde " . $solde->typelibelle() . " sera après enregistrement de " . ($solde->droitaquis() - $solde->droitpris())));
                                        $solde->store();

                                        // Ajouter dans la table des commentaires la trace de l'opération
                                        $agent->ajoutecommentaireconge($alimentationCET->typeconges(),($alimentationCET->valeur_f()*-1),"Retrait de jours pour alimentation CET");

                                        $erreur = $alimentationCET->storepdf();
                                        if ($erreur != '')
                                        {
                                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la récupération du PDF de la demande " . $esignatureid . " => Erreur = " . $erreur));
                                            $result_json = array('status' => 'Error', 'description' => $erreur);
                                        }
                                    }
                                    else  // Le statut de la demande n'est pas signée
                                    {
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $alimentationCET->agentid()));
                                    }

                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Mise à jour de la demande d'alimentation du CET $esignatureid de l'agent " . $alimentationCET->agentid()));
                                    $alimentationCET->statut($status);
                                    if ($status <> alimentationCET::STATUT_ABANDONNE)
                                    {
                                        $alimentationCET->motif($reason);
                                    }

                                    $erreur = $alimentationCET->store();
                                    if ($erreur != "")
                                    {
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de l'enregistrement de la demande " . $esignatureid . " => Erreur = " . $erreur));
                                        $result_json = array('status' => 'Error', 'description' => $erreur);
                                    }
                                    else
                                    {
                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Traitement OK de la demande " . $esignatureid . " => Pas d'erreur"));
                                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                                    }
                                }
                                else
                                {
                                    $erreur = "Incohérence lors de la modification du statut de la demande : La demande est " . $alimentationCET->statut() . " et on veut la passer $status";
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
                                    $result_json = array('status' => 'Error', 'description' => $erreur);
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
    
   
    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    echo json_encode($result_json);
    
?>
