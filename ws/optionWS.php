<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

/*
    require_once ('../html/class/fonctions.php');
    require_once ('../html/class/agent.php');
    require_once ('../html/class/structure.php');
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    require_once ("../html/class/fpdf/fpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
    require_once ("../html/class/periodeobligatoire.php");
    require_once ("../html/class/alimentationCET.php");
    require_once ("../html/class/optionCET.php");
*/
    
    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
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
                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va retourner les infos de le droit d'option " . $esignatureid));
                if ("$esignatureid" == "" )
                {
                    $erreur = "Le paramètre esignatureid n'est pas renseigné.";
                }
                else
                {
                    $optionCET = new optionCET($dbcon);
                    $erreur = $optionCET->load($esignatureid);
                }
                if ($erreur != "")
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                }
                else
                {
                    $valeur_a = $optionCET->valeur_a();
                    $valeur_g = $optionCET->valeur_g();
                    $valeur_h = $optionCET->valeur_h();
                    $valeur_i = $optionCET->valeur_i();
                    $valeur_j = $optionCET->valeur_j();
                    $valeur_k = $optionCET->valeur_k();
                    $valeur_l = $optionCET->valeur_l();
                    $information_A = array('name' => "A", 'description' => "Solde du CET avant versement", 'value' => $valeur_a);
                    $information_G = array('name' => "G", 'description' => "Solde du CET après versement", 'value' => $valeur_g);
                    $information_H = array('name' => "H", 'description' => "Nombre de jours dépassant le seuil de 15 jours", 'value' => $valeur_h);
                    $information_I = array('name' => "I", 'description' => "Nombre de jours à prendre en compte au titre du RAFP", 'value' => $valeur_i);
                    $information_J = array('name' => "J", 'description' => "Nombre de jours à indemniser", 'value' => $valeur_j);
                    $information_K = array('name' => "K", 'description' => "Nombre de jours à maintenir sur le CET sous forme de congés", 'value' => $valeur_k);
                    $information_L = array('name' => "L", 'description' => "Solde du CET après option", 'value' => $valeur_l);
                    
                    $agent = new agent($dbcon);
                    $agent->load($optionCET->agentid());
                    $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                    if (count(array($affectationliste)) > 0)
                    {
                        $affectation = current($affectationliste);
                        $structure = new structure($dbcon);
                        $structure->load($affectation->structureid());
                    }
                    
                    $anneeref = "Année universitaire " . $optionCET->anneeref() . "/" . ($optionCET->anneeref()+1);
                    
                    if ($errlog != "")
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid . " => Erreur = " . $errlog));
                        $result_json = array('status' => 'Error', 'description' => $errlog);
                    }
                    else
                    {
                        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                        if (count(array($affectationliste)) > 0)
                        {
                            $affectation = new affectation($dbcon);
                            $affectation = current($affectationliste);
                            $infosLdap = $agent->getInfoDocCet();
                            $nameStructComplete = $structure->nomcompletcet();
                            // quotité sur la période 01/09/N-1 - 31/08/N
                            $datedebut = ($fonctions->anneeref() - 1).$fonctions->debutperiode();
                            $datefin = $fonctions->anneeref().$fonctions->finperiode();
                            $quotite = round($agent->getQuotiteMoyPeriode($datedebut, $datefin), 0, PHP_ROUND_HALF_EVEN).'%';
                            
                            $agent = array('uid' => $agent->agentid(),
                                'email' => $agent->mail(),
                                'name' => $agent->nom(),
                                'firstname' => $agent->prenom(),
                                'service' => array('name' => $nameStructComplete,
                                    'id' => $structure->id(),
                                    'addr' => $infosLdap[LDAP_AGENT_ADDRESS_ATTR]."",
                                    'type' => $structure->typestruct()),
                                'ref_year' => $anneeref,
                                'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                                'corps' => $agent->typepopulation()
                            );
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Lecture OK des infos du droit d'option " . $esignatureid . " => Pas d'erreur"));
                            $result_json = array('agent' => $agent, 'informations' => array($information_A, $information_G, $information_H, $information_I, $information_J, $information_K, $information_L));
                            //error_log(basename(__FILE__) . $fonctions->stripAccents(" Le json resutat => " . print_r($result_json,true)));
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
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
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On va modifier le statut du droit d'option =>  " . $esignatureid));
                    /*
                     if (array_key_exists("status",$_GET))
                     $status = $_GET["status"];
                     if (array_key_exists("reason",$_GET))
                     $reason = $_GET["reason"];
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
                    }
                    //echo "<br>" . print_r($json,true) . "<br>";
                    
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du WS signrequests en json"));
                    error_log(basename(__FILE__) . " " . var_export($json,true));
                    $response3 = json_decode($json, true);
                    
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Réponse du WS signrequests"));
                    error_log(basename(__FILE__) . " " . var_export($response3,true));
 
                    // On appelle le WS eSignature pour récupérer les infos du document
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
	                        $result_json = array('status' => 'Error', 'description' => $erreur);
	                    }
	                    else // Tout va bien !
	                    {
	                        //echo "<br>" . print_r($json,true) . "<br>";
                                //////////////////////////////////////////////////////
                                // PATCH JSON GET-DATAS DE ESIGNATURE
                                $json=str_ireplace('"recipient":', '',$json);
                                $json=str_ireplace(',"action"', '',$json);
                                /////////////////////////////////////////////////////
	                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La réponse json (avant conversion) est : " . var_export($json,true)));
	                    	$response = json_decode($json, true);
	                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La dernière erreur JSON : " . json_last_error_msg()));
                                error_log(basename(__FILE__) . " " . var_export($response,true));
	                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La réponse json est : " . var_export($response,true)));
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre élément dans reponse = " .count((array)$response)));
	                        /*if (is_null($response))
	                        {
	                            $erreur = "La réponse json est NULL ==> On doit retourner une erreur ; la demande n'existe plus !";
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
	                            $result_json = array('status' => 'Error', 'description' => $erreur);
	                        }
	                        else*/if (isset($response['error']))
	                        {
	                            $erreur = "La réponse json est une erreur ==> On doit la retourner : " . $response['error'];
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
	                            $result_json = array('status' => 'Error', 'description' => $erreur);
	                        }
	                        else // Tout semble ok !
	                        {
	                            if (isset($response3["parentSignBook"]["status"]))
	                            {
	                                $current_status = $response3["parentSignBook"]["status"];
	                            }
	                            else
	                            {
	                                $current_status = '';
	                            }
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$current_status'"));
	                            $optionCET = new optionCET($dbcon);
	                            $validation = optionCET::STATUT_INCONNU;
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" On va faire la récupération des données."));
	                            foreach((array)$response as $key => $value)
	                            {
	                                //if (preg_match("/form_data_d.+cision/i",$key))
	                                if (stristr(strtolower($key),"form_data_d")!==false and stristr(strtolower($key),"cision")!==false) //   preg_match("/form_data_d.+cision/i",$key))
	                                {
	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La clé $key correspond à la recherche."));
	                                    if (strcasecmp($value,'yes')==0)  // if ($response['form_data_decision'] == 'yes')
	                                    {
	                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée $key vaut YES."));
	                                        $validation = optionCET::STATUT_VALIDE;
	                                        break;
	                                    }
	                                    elseif (strcasecmp($value,'no')==0)  // elseif ($response['form_data_decision'] == 'no')
	                                    {
	                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée $key vaut NO."));
	                                        $validation = optionCET::STATUT_REFUSE;
	                                        break;
	                                    }
	                                    else
	                                    {
	                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée $key est vide."));
	                                        $validation = optionCET::STATUT_INCONNU;
	                                    }
	                                }
	                            }
	                             
	                            if ($validation == optionCET::STATUT_REFUSE)
	                            {
	                                foreach((array)$response as $key => $value)
	                                {
	                                    if (preg_match("/form_data_motifrefus/i",$key))
	                                    {
	                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La donnée $key existe."));
	                                        $reason = $response[$key];
	                                        break;
	                                     }
	                                }
	                            }
	                             
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Après tous les tests on as : current_status = $current_status     validation = $validation     reason = $reason"));
	                             
	                            switch (strtolower($current_status))
	                            {
	                                //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
	                                case 'draft' :
	                                case 'pending' :
	                                case 'signed' :
	                                case 'checked' :
	                                    $status = optionCET::STATUT_EN_COURS;
	                                    break;
	                                case 'refused':
	                                    $status = optionCET::STATUT_REFUSE;
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
	                                    if ($validation == optionCET::STATUT_VALIDE)
	                                        $status = optionCET::STATUT_VALIDE;
	                                    elseif ($validation == optionCET::STATUT_REFUSE)
	                                       $status = optionCET::STATUT_REFUSE;
	                                    else
	                                       $status = optionCET::STATUT_INCONNU;
	                                    break;
	                                case 'deleted' :
	                                case 'canceled' :
	                                case '' :
	                                    $status = optionCET::STATUT_ABANDONNE;
	                                    break;
	                                default :
	                                    $status = optionCET::STATUT_INCONNU;
	                            }
	                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Le status du droit d'option $esignatureid est : $status car la validation est : $validation "));
	                            //$status = mb_strtolower("$status", 'UTF-8');
	                            
	                            $erreur = $optionCET->load($esignatureid);
	                            if ($erreur != "")
	                            {
	                                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
	                                $result_json = array('status' => 'Error', 'description' => $erreur);
	                            }
	                            else
	                            {
	                                error_log(basename(__FILE__) . $fonctions->stripAccents(" status = $status"));
	                                error_log(basename(__FILE__) . $fonctions->stripAccents(" optionCET->statut() = " . $optionCET->statut()));
	                                
	                                // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
	                                if ($status == $optionCET->statut())
	                                {
	                                    $erreur = '';
	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La demande a déjà un statut $status. On ne fait rien => Pas d'erreur"));
	                                    $result_json = array('status' => 'Ok', 'description' => $erreur);
	                                }
	                                // Ajout d'un contrôle qui interdit de modifier le statut de la demande, les informations de solde si la demande est déjà VALIDE, ABANDONNE ou REFUSE
	                                elseif ($optionCET->statut() <> optionCET::STATUT_VALIDE
	                                    and $optionCET->statut() <> optionCET::STATUT_ABANDONNE
	                                    and $optionCET->statut() <> optionCET::STATUT_REFUSE)
	                                {
	                                    if (($status == optionCET::STATUT_VALIDE) and ($optionCET->statut() == optionCET::STATUT_EN_COURS or $optionCET->statut() == optionCET::STATUT_PREPARE))
    	                                {
    	                                    $agent = new agent($dbcon);
    	                                    $agentid = $optionCET->agentid();
    	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" L'agent id =  " . $agentid ));
    	                                    $agent->load($agentid);
    	                                    $cet = new cet($dbcon);
    	                                    $erreur = $cet->load($agentid);
    	                                    if ($erreur <> '')
    	                                    {
    	                                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Pas de CET pour cet agent : " . $agent->identitecomplete() ." ! Ce n'est pas possible. "));
    	                                        $result_json = array('status' => 'Error', 'description' => 'Pas de CET pour cet agent :' . $erreur);
    	                                        unset($cet);
    	                                    }
    	                                    else
    	                                    {
    	                                       error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde du CET est avant enregistrement de " . ($cet->cumultotal() - $cet->jrspris())));
    	                                       // On ajuste le solde du CET et on marque dans l'historique 
    	                                       // On retranche le nombre de jours pour la RAFP
    	                                       if ($optionCET->valeur_i() > 0)
    	                                       {
    	                                           error_log(basename(__FILE__) . $fonctions->stripAccents(" L'agent : " . $agent->identitecomplete() ." met " . $optionCET->valeur_i() . " jours en RAFP. "));
    	                                           $cet->jrspris( $cet->jrspris() + $optionCET->valeur_i() ) ;
    	                                           // Ajouter dans la table des commentaires la trace de l'opération
    	                                           $agent->ajoutecommentaireconge('cet',($optionCET->valeur_i()*-1),"Prise en compte au titre de la RAFP");
    	                                       }
    	                                       
    	                                       // On retranche le nombre de jours pour l'indemnisation
    	                                       if ($optionCET->valeur_j() > 0)
    	                                       {
    	                                           error_log(basename(__FILE__) . $fonctions->stripAccents(" L'agent : " . $agent->identitecomplete() ." met " . $optionCET->valeur_j() . " jours en indemnisation. "));
    	                                           $cet->jrspris( $cet->jrspris() + $optionCET->valeur_j() ) ;
    	                                           // Ajouter dans la table des commentaires la trace de l'opération
    	                                           $agent->ajoutecommentaireconge('cet',($optionCET->valeur_j()*-1),"Prise en compte au titre de l'indemnistation");
    	                                       }
    	                                       
    	                                       // Nombre de jours à conserver dans le CET -- Juste pour info car cela ne modifie pas le solde du CET
    	                                       if ($optionCET->valeur_k() > 0)
    	                                       {
    	                                           error_log(basename(__FILE__) . $fonctions->stripAccents(" L'agent : " . $agent->identitecomplete() ." conserve " . $optionCET->valeur_k() . " jours dans son CET. "));
    	                                       }
    	                                       
    	                                       error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde du CET sera après enregistrement de " . ($cet->cumultotal() - $cet->jrspris())));
    	                                       $cet->store();
    	                                       
    	                                       $erreur = $optionCET->storepdf();
    	                                       if ($erreur != '')
    	                                       {
    	                                           error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la récupération du PDF de la demande " . $esignatureid . " => Erreur = " . $erreur));
    	                                           $result_json = array('status' => 'Error', 'description' => $erreur);
    	                                       }
    	                                                                    
    	                                    }
    	                                }
    	                                else  // Le statut du droit d'option n'est pas validée
    	                                {
    	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $optionCET->agentid()));
    	                                }
    
    	                                error_log(basename(__FILE__) . $fonctions->stripAccents(" Mise à jour du droit d'option $esignatureid de l'agent " . $optionCET->agentid()));
    	                                $optionCET->statut($status);
    	                                if ($status <> optionCET::STATUT_ABANDONNE)
    	                                {
    	                                   $optionCET->motif($reason);
    	                                }
    	                                $erreur = $optionCET->store();
    	                                
    	                                if ($erreur != "")
    	                                {
    	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de l'enregistrement du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
    	                                    $result_json = array('status' => 'Error', 'description' => $erreur);
    	                                }
    	                                else
    	                                {
    	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Traitement OK du droit d'option " . $esignatureid . " => Pas d'erreur"));
    	                                    $result_json = array('status' => 'Ok', 'description' => $erreur);
    	                                }
	                                }
	                                else
	                                {
	                                    $erreur = "Incohérence lors de la modification du statut de la demande : La demande est " . $optionCET->statut() . " et on veut la passer $status";
	                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
	                                    $result_json = array('status' => 'Error', 'description' => $erreur);
	                                }
	                            }
	                        }
	                    }
                    }
                    else
                    {
                    	$erreur = "Erreur dans eSignature : \n\t |  ".$json;
                    	error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
                    	$result_json = array('status' => 'Error', 'description' => $erreur);
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