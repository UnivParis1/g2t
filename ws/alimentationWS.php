<?php
    require_once ('../html/includes/dbconnection.php');
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

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $eSignature_url = "https://esignature-test.univ-paris1.fr";

    
    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    $statutvalide = array('PREPA' => alimentationCET::STATUT_PREPARE, 'COURS' => alimentationCET::STATUT_EN_COURS, 'REFUS' => alimentationCET::STATUT_REFUSE, 'SIGNE' => alimentationCET::STATUT_VALIDE, 'ABAND' => alimentationCET::STATUT_ABANDONNE);
    
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'POST': // Modifie le statut d'une demande d'alimentation
            $erreur = "Le mode POST n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
 
/*            
            $esignatureid = "";
            $status = "";
            $reason = "";
            
            if (array_key_exists("esignatureid", $_POST))
                $esignatureid = $_POST["esignatureid"];
            if (array_key_exists("status", $_POST))
                $status = $_POST["status"];
            if (array_key_exists("reason", $_POST))
                $reason = $_POST["reason"];

            switch (strtolower($status))
            {
                //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                case 'draft' :
                case 'pending' :
                case 'signed' :
                case 'checked' :
                    $status = "En cours";
                    break;
                case 'refused':
                    $status = "Refusée";
                    break;
                case 'completed' :
                case 'exported' :
                case 'archived' :
                case 'cleaned' :
                    $status = "Signée";
                    break;
                case 'deleted' :
                case 'canceled' :
                    $status = "Abandonnée";
                    break;
                default :
                    $status = $status;
            }

            $status = mb_strtolower("$status", 'UTF-8');
            if ("$esignatureid" == "")
            {
                $erreur = "Le paramètre esignature n'est pas renseigné.";
            }
            else if ("$status" == "")
            {
                $erreur = "Le paramètre status n'est pas renseigné.";
            }
            else if (!in_array($status, $statutvalide))
            {
                $erreur = "Statut invalide => $status. Liste des statuts valides => " . implode(", ", $statutvalide);
            }
            else if ((array_search($status,$statutvalide ) == 'REFUS') and ($reason == ""))
            {
                $erreur = "La demande doit passer au statut '$status' mais le motif est vide";
            }
*/
/*            
            if ($status == 'Terminee')
            {
                $cet = new cet($dbcon);
                $cet->load();
                $cet->cumultotal($nbjrs + $cet->cumultotal()) ; 
                //$cet->store;
                
                $solde = new solde($dbcon);
                $solde->load($agentid,'ann' . $anneref);
                $solde->droitpris($solde->droitpris() + $nbjrs);
                //$solde->store;
                
                // Ajouter dans la table des commentaires la trace de l'opération
            }

*/
/*            
            if ($erreur == "")
            {
                $alimentationCET = new alimentationCET($dbcon);
                $erreur = $alimentationCET->load($esignatureid);
            }
            if ($erreur == "")
            {
                $alimentationCET->statut($status);
                $alimentationCET->motif($reason);
                $erreur = $alimentationCET->store();
            }
            if ($erreur != "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la modification de la demande " . $esignatureid . " => Erreur = " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Modification OK de la demande " . $esignatureid . " => Erreur = " . $erreur));
                $result_json = array('status' => 'Ok', 'description' => $erreur);
            }
*/

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
                    $valeur_a = $alimentationCET->valeur_a();
                    $valeur_b = $alimentationCET->valeur_b();
                    $valeur_c = $alimentationCET->valeur_c();
                    $valeur_d = $alimentationCET->valeur_d();
                    $valeur_e = $alimentationCET->valeur_e();
                    $valeur_f = $alimentationCET->valeur_f();
                    $valeur_g = $alimentationCET->valeur_g();
                    $information_A = array('name' => "A", 'description' => "Solde du CET avant versement", 'value' => $valeur_a);
                    $information_B = array('name' => "B", 'description' => "Droits à congés (en jours) au titre de l’année de référence", 'value' => $valeur_b);
                    $information_C = array('name' => "C", 'description' => "Nombre de jours de congés utilisés au titre de l’année de référence", 'value' => $valeur_c);
                    $information_D = array('name' => "D", 'description' => "Solde de jours de congés non pris au titre de l’année de référence", 'value' => $valeur_d);
                    $information_E = array('name' => "E", 'description' => "Nombre de jours de congés reportés sur l’année suivante", 'value' => $valeur_e);
                    $information_F = array('name' => "F", 'description' => "Alimentation du CET", 'value' => $valeur_f);
                    $information_G = array('name' => "G", 'description' => "Solde du CET après versement", 'value' => $valeur_g);
                    
                    $agent = new agent($dbcon);
                    $agent->load($alimentationCET->agentid());
                    $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                    if (count(array($affectationliste)) > 0)
                    {
                        $affectation = current($affectationliste);
                        $structure = new structure($dbcon);
                        $structure->load($affectation->structureid());
                    }
                    
                    $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = '" .  $alimentationCET->typeconges()  . "'";
                    $query = mysqli_query($dbcon, $sql);
                    $erreur = mysqli_error($dbcon);
                    if ($erreur != "")
                    {
                        $errlog = "Problème SQL dans le chargement de l'année de reférence : " . $erreur;
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    }
                    elseif (mysqli_num_rows($query) == 0)
                    {
                        //echo "<br>load => pas de ligne dans la base de données<br>";
                        $errlog = "Impossible de déterminer l'année de référence pour le type " . $alimentationCET->typeconges();
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    }
                    else
                    {
                        $result = mysqli_fetch_row($query);
                        $anneeref = "Année universitaire " . $result["0"] . "/" . ($result["0"]+1);
                    }
                    
                    
                    if ($errlog != "")
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid . " => Erreur = " . $errlog));
                        $result_json = array('status' => 'Error', 'description' => $errlog);
                    }
                    else
                    {
                        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                        if (count(array($affectationliste)) > 0)
                        {
                            $affectation = new affectation($dbcon);
                            $affectation = current($affectationliste);
                            $affectation->quotite();
                            $infosLdap = $agent->getInfoDocCet();
                            $nameStructComplete = $structure->nomcompletcet();
                            $agent = array('uid' => $agent->harpegeid(),
                                'email' => $agent->mail(),
                                'name' => $agent->nom(),
                                'firstname' => $agent->prenom(),
                                'service' => array('name' => $nameStructComplete,
                                                   'id' => $structure->id(),
                                                   'addr' => $infosLdap['postaladdress'],
                                                   'type' => $structure->typestruct()),
                                'ref_year' => $anneeref,
                                'activity' => $affectation->quotite() == '100%' ? 'Temps complet' : $affectation->quotite(),
                                'corps' => $agent->typepopulation()
                            );
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Lecture OK des infos de la demande " . $esignatureid . " => Erreur = "));
                            $result_json = array('agent' => $agent, 'informations' => array($information_A, $information_B, $information_C, $information_D, $information_E, $information_F, $information_G));
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
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
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On va modifier le statut de la demande =>  " . $esignatureid));
/*                
                    if (array_key_exists("status",$_GET))
                        $status = $_GET["status"];
                    if (array_key_exists("reason",$_GET))
                        $reason = $_GET["reason"];
*/
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
                    if ($error != "")
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
                    }
                    //echo "<br>" . print_r($json,true) . "<br>";
                    $response = json_decode($json, true);
                    $current_status = $response['form_current_status'];
    
                    $alimentationCET = new alimentationCET($dbcon);
                    $validation = $alimentationCET::STATUT_INCONNU;
                    if (isset($response['form_data_accepte']))
                    {
                        if ($response['form_data_accepte']=='on')
                            $validation = $alimentationCET::STATUT_VALIDE;
                    }
                    if (isset($response['form_data_refuse']))
                    {
                        if ($response['form_data_refuse']=='on')
                            $validation = $alimentationCET::STATUT_REFUSE;
                    }
                    
                    switch (strtolower($current_status))
                    {
                        //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                        case 'draft' :
                        case 'pending' :
                        case 'signed' :
                        case 'checked' :
                            $status = $alimentationCET::STATUT_EN_COURS;
                            break;
                        case 'refused':
                            $status = $alimentationCET::STATUT_REFUSE;
                            break;
                        case 'completed' :
                        case 'exported' :
                        case 'archived' :
                        case 'cleaned' :
                            if ($validation == $alimentationCET::STATUT_VALIDE)
                                $status = $alimentationCET::STATUT_VALIDE;
                            elseif ($validation == $alimentationCET::STATUT_REFUSE)
                                $status = $alimentationCET::STATUT_REFUSE;
                            else
                                $status = $alimentationCET::STATUT_INCONNU;
                            break;
                        case 'deleted' :
                        case 'canceled' :
                            $status = $alimentationCET::STATUT_ABANDONNE;
                            break;
                        default :
                            $status = $alimentationCET::STATUT_INCONNU;
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
                        }
                        else  // Le statut de la demande n'est pas signée
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $alimentationCET->agentid()));
                        }
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Mise à jour de la demande d'alimentation du CET $esignatureid de l'agent " . $alimentationCET->agentid()));
                        $alimentationCET->statut($status);
                        $alimentationCET->motif($reason);
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
