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
                    $somme = 0;
                    $indexjour = 0;
                    $nbjoursdemande = (substr_count($teletravail->tabteletravail(),1)/2);  // On compte le nombre de 1 dans le tableau de télétravail et on divise par 2 (1 = 1/2 journée)
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
/*                            
                            if ($fonctions->testexistdbconstante('NBJOURSMAXTELETRAVAIL')) $nbjoursmaxteletravail = $fonctions->liredbconstante('NBJOURSMAXTELETRAVAIL');
                            $nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
                            $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail-($nbredemiTP*0.5);
                            if ($nbjoursmaxteletravailcalcule<0) $nbjoursmaxteletravailcalcule = 0;
*/                        
                            $information_typeconvention = array('name' => "typeconvention", 'description' => "Type de convention de télétravail", 'value' => $teletravail->libelletypeconvention($teletravail->typeconvention()), 'code' => $teletravail->typeconvention());
                            $information_nombrejours = array('name' => "nombrejours", 'description' => "Nombre de jours de télétravail demandé", 'value' => "$nbjoursdemande"); // "$nbjoursmaxteletravailcalcule");
                            $information_datedebut = array('name' => "datedebut", 'description' => "Date de début de la convention télétravail", 'value' => $fonctions->formatdate($teletravail->datedebut()));
                            $information_datefin = array('name' => "datefin", 'description' => "Date de fin de la convention télétravail", 'value' => $fonctions->formatdate($teletravail->datefin()));
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
                                    'addr' => strtoupper($infosLdap[LDAP_AGENT_PERSO_ADDRESS_ATTR].""),
                                    'type' => $structure->typestruct()),
                                'ref_year' => $anneeref,
                                'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                                'corps' => $agent->typepopulation(),
                                'rifseep' => $agent->fonctionRIFSEEP()
                            );
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Lecture OK des infos de convention télétravail " . $esignatureid . " => Pas d'erreur"));
//                            $result_json = array('agent' => $agent, 'infosconvention' => $information_typeconvention, 'informations' => array('nbjours' => $information_nombrejours, 'infosjours' => $information_jourteletravail));
                            $result_json = array('agent' => $agent, 
                                                 'infosconvention' => $information_typeconvention, 
                                                 'informations' => array_merge(array($information_nombrejours), array($information_datedebut), array($information_datefin), $information_jourteletravail));
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