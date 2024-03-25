<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $erreur_curl = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
    // On indique par défaut que tout s'est bien passé
    $result_json = array('status' => 'Ok', 'description' => '');
        
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
            if (array_key_exists("esignatureid", $_GET)) // Retourne les informations liées à une convention de télétravail
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
                    // On crée la réponse JSon correspondant au télétravail
                    $result_json = $fonctions->teletravailjsonresponse($teletravail);
                }
            }
            elseif (array_key_exists("signRequestId", $_GET))  // Synchronisation d'une demande G2T avec le statut de eSignature
            {
                $esignatureid = $_GET["signRequestId"];
                if ("$esignatureid" == "")
                {
                    $erreur = "Le paramètre esignature n'est pas renseigné.";
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" ERROR => " . $erreur));
                }
                else
                {
                    $result_json = $fonctions->synchroniseconventionteletravail($esignatureid);
                }
            }
            elseif (array_key_exists("status", $_GET))  // Synchronisation des demandes de convention télétravail avec le statut indiqué dans G2T
            {
                $statutdemandeliste = $_GET["status"];
                $statutdemandetab = explode(",",$statutdemandeliste);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va synchroniser les conventions de télétravail avec le statut = " . print_r($statutdemandetab,true)));
                if (count($statutdemandetab)==0)
                {
                    $erreur = "Le paramètre status n'est pas renseigné.";
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" ERROR => " . $erreur));
                }
                else
                {
//                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant appel de listeconventionteletravailavecstatut => statut = " . teletravail::TELETRAVAIL_ATTENTE));
                    $tabconvention = array();
                    foreach($statutdemandetab as $statutdemande)
                    {
                        $tabconvention = array_merge($tabconvention,$fonctions->listeconventionteletravailavecstatut($statutdemande));
                    }
//                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Apres appel de listeconventionteletravailavecstatut => statut = " . teletravail::TELETRAVAIL_ATTENTE));
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre de conventions trouvées = " . count($tabconvention)));
                    if (count($tabconvention)==0)
                    {
                        $result_json = array('status' => 'Ok', 'description' => 'Aucune convention à synchroniser');
                    }
                    else
                    {
                        foreach($tabconvention as $teletravail)
                        {
                            if (trim($teletravail->esignatureid())!= "")
                            {
                                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va synchro la convention esignatureid = " . $teletravail->esignatureid()));
                                $result_json = $fonctions->synchroniseconventionteletravail($teletravail->esignatureid());
    //                            error_log(basename(__FILE__) . $fonctions->stripAccents(" result_json = " . print_r($result_json,true)));
                                if ($result_json['status']=='Error')
                                {
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On break"));
                                    break;
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