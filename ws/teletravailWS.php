<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $erreur_curl = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
    
    
    function synchroniseconventionteletravail($esignatureid)
    {
        global $fonctions;
        global $eSignature_url;
        global $dbcon;
//        global $result_json;

        $status = "";
        $reason = "";
        $datesignatureresponsable = '19000101';
        
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
                    // On récupère les niveaux signés et si il y a plus de 2 signataires, on passe le statut à TELETRAVAIL_VALIDE
                    // car la convention commence dès que le responsable a signé.
                    $curl = curl_init();
                    $params_string = "";
                    $opts = [
                        CURLOPT_URL => $eSignature_url . '/ws/signrequests/audit-trail/' . $esignatureid,
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
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl (récup signataires) =>  " . $error));
                    }
                    $response = json_decode($json, true);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La réponse (recup signataires) => " . print_r($response,true)));
                    
                    if (isset($response['auditSteps']) and count($response['auditSteps'])>=2)
                    {
                        $datesignatureresponsable = $response['auditSteps'][1]['timeStampDate'];
                        $datesignatureresponsable = date('Ymd', strtotime($datesignatureresponsable));
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" La date de la signature du responsable est : " .$datesignatureresponsable));
                        $status = teletravail::TELETRAVAIL_VALIDE;
                        $identitéresponsable = $response['auditSteps'][1]['firstname'] . " " . $response['auditSteps'][1]['name'];
                        $reason = "Votre responsable " . $identitéresponsable . " a signé votre convention. Votre convention est active mais le circuit de validation n'est pas terminé.";
                    }
                    else
                    {
                        $status = teletravail::TELETRAVAIL_ATTENTE;
                    }
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
                if ($status == $teletravail->statut() and $reason==$teletravail->commentaire())
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La convention a déjà un statut $status (" . $fonctions->teletravailstatutlibelle($status) . "). On ne fait rien => Pas d'erreur"));
                    $erreur = '';
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                }
                else // if (in_array($statut, array(teletravail::TELETRAVAIL_REFUSE, teletravail::TELETRAVAIL_VALIDE))) // Si le statut dans eSignature est REFUSE ou VALIDE
                {
                    if ($fonctions->formatdatedb($datesignatureresponsable)>$fonctions->formatdatedb($teletravail->datedebut()))
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" On passe la date de début de la convention à $datesignatureresponsable - valeur actuelle : " . $teletravail->datedebut()));
                        $teletravail->datedebut($datesignatureresponsable);
                    }
                    if ($fonctions->formatdatedb($teletravail->datedebut())>$fonctions->formatdatedb($teletravail->datefin()) and $status <> teletravail::TELETRAVAIL_ANNULE )
                    {
                        $status = teletravail::TELETRAVAIL_ANNULE;
                        $reason = "Il y a une incohérence dans les dates de début et de fin => On force l'annulation de la convention.";
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" $reason"));
                    }
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
                        // On va regarder si d'autres conventions se chevauchent
                        $agentid = $teletravail->agentid();
                        $agent = new agent($dbcon);
                        $agent->load($agentid);
                        $currentconventionid=$teletravail->teletravailid();
                        $datedebutteletravail = $teletravail->datedebut();
                        $datefinteletravail = $teletravail->datefin();
                        $liste = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
                        foreach ($liste as $conventionid)
                        {
                            if ($currentconventionid <> $conventionid) // On ignore la convention qu'on vient de traiter
                            {
                                $teletravailmodif = new teletravail($dbcon);
                                $teletravailmodif->load($conventionid);
                                if (in_array($teletravailmodif->statut(),array(teletravail::TELETRAVAIL_VALIDE,teletravail::TELETRAVAIL_ATTENTE)))
                                {
                                    if ($teletravailmodif->datefin()>=$datedebutteletravail)
                                    {
                                        $veilledebut = date("d/m/Y", strtotime("-1 day", strtotime($fonctions->formatdatedb($datedebutteletravail))));
                                        //echo "datedebutteletravail = $datedebutteletravail <br>";
                                        //echo "veilledebut = $veilledebut <br>";
                                        $teletravailmodif->datefin($veilledebut);
                                        $teletravailmodif->commentaire("Modification de la date de fin de la convention suite à création d'une nouvelle convention.");
                                        //echo "date debut  = " . $fonctions->formatdatedb($teletravail->datedebut()) . "<br>";
                                        //echo "date fin  = " . $fonctions->formatdatedb($teletravail->datefin()) . "<br>";
                                        if ($fonctions->formatdatedb($teletravailmodif->datefin()) < $fonctions->formatdatedb($teletravailmodif->datedebut()))
                                        {
                                            $return = $fonctions->deleteesignaturedocument($teletravailmodif->esignatureid());
                                            if (strlen($return)>0) // On a rencontré une erreur dans la suppression eSignature
                                            {
                                                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                                                $erreur = $erreur . $return;
                                                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
                                            }
                                            //echo "On passe la convetion à ANNULE<br>";
                                            $teletravailmodif->statut(teletravail::TELETRAVAIL_ANNULE);
                                            //deleteesignaturedocument($teletravail);
                                        }
                                        //echo "La convention télétravail " . $teletravail->teletravailid() . " a un statut " . $teletravail->statut() . " ( " . $fonctions->teletravailstatutlibelle($teletravail->statut()) . " ) et une date de fin " . $teletravail->datefin() . "<br>";
                                        $teletravailmodif->store();
                                    }
/*                                    
                                    if (strlen($alerte)>0) $alerte = $alerte . '<br>';
                                    $alerte = $alerte . "La nouvelle convention de télétravail a modifié une convention existante (id = $conventionid).";
*/
                                }
                            }
                        }
                        if ($erreur <> '')
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de l'adaptation des conventions => Erreur = " . $erreur));
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Traitement ok de la modification du statut de la convention " . $currentconventionid . " => Pas d'erreur"));
                            $result_json = array('status' => 'Ok', 'description' => $erreur);
                        }
                    }
                }
            }
        }
        return $result_json;
    }
    
    
    
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
                    $result_json = synchroniseconventionteletravail($esignatureid);
                }
            }
            elseif (array_key_exists("status", $_GET))  // Synchronisation des demandes de convention télétravail avec le statut indiqué dans G2T
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" On va synchroniser les conventions de télétravail avec le statut = " . teletravail::TELETRAVAIL_ATTENTE));
                $statutdemande = $_GET["status"];
                if ("$statutdemande" == "")
                {
                    $erreur = "Le paramètre status n'est pas renseigné.";
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" ERROR => " . $erreur));
                }
                else
                {
//                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant appel de listeconventionteletravailavecstatut => statut = " . teletravail::TELETRAVAIL_ATTENTE));
                    $tabconvention = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE);
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
//                            error_log(basename(__FILE__) . $fonctions->stripAccents(" On va synchro la convention esignatureid = " . $teletravail->esignatureid()));
                            $result_json = synchroniseconventionteletravail($teletravail->esignatureid());
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