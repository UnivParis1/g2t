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
require_once ("../html/class/optionCET.php");

$fonctions = new fonctions($dbcon);
$errlog = '';
$erreur = '';
$eSignature_url = "https://esignature-test.univ-paris1.fr";


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
                 
                 $optionCET = new optionCET($dbcon);
                 $validation = $optionCET::STATUT_INCONNU;
                 if (isset($response['form_data_accepte']))
                 {
                     if ($response['form_data_accepte']=='on')
                         $validation = $optionCET::STATUT_VALIDE;
                 }
                 if (isset($response['form_data_refuse']))
                 {
                     if ($response['form_data_refuse']=='on')
                         $validation = $optionCET::STATUT_REFUSE;
                 }
                 
                 switch (strtolower($current_status))
                 {
                     //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                     case 'draft' :
                     case 'pending' :
                     case 'signed' :
                     case 'checked' :
                         $status = $optionCET::STATUT_EN_COURS;
                         break;
                     case 'refused':
                         $status = $optionCET::STATUT_REFUSE;
                         break;
                     case 'completed' :
                     case 'exported' :
                     case 'archived' :
                     case 'cleaned' :
                         if ($validation == $optionCET::STATUT_VALIDE)
                             $status = $optionCET::STATUT_VALIDE;
                         elseif ($validation == $optionCET::STATUT_REFUSE)
                            $status = $optionCET::STATUT_REFUSE;
                         else
                             $status = $optionCET::STATUT_INCONNU;
                         break;
                     case 'deleted' :
                     case 'canceled' :
                         $status = $optionCET::STATUT_ABANDONNE;
                         break;
                     default :
                         $status = $optionCET::STATUT_INCONNU;
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
                     //if ($status == mb_strtolower($optionCET::STATUT_VALIDE, 'UTF-8'))
                     error_log(basename(__FILE__) . $fonctions->stripAccents(" status = $status"));
                     error_log(basename(__FILE__) . $fonctions->stripAccents(" optionCET->statut() = " . $optionCET->statut()));
                     if (($status == $optionCET::STATUT_VALIDE) and ($optionCET->statut() == $optionCET::STATUT_EN_COURS or $optionCET->statut() == $optionCET::STATUT_PREPARE))
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
                             
                         }
                     }
                     else  // Le statut du droit d'option n'est pas validée
                     {
                         error_log(basename(__FILE__) . $fonctions->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $optionCET->agentid()));
                     }
                     error_log(basename(__FILE__) . $fonctions->stripAccents(" Mise à jour du droit d'option $esignatureid de l'agent " . $optionCET->agentid()));
                     $optionCET->statut($status);
                     $optionCET->motif($reason);
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