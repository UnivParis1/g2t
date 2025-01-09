<?php
    include '../html/includes/casconnection.php';
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);

    function change_exception_periode()
    {
        global $dbcon;
        global $fonctions;

        static $rhuser = null;

        $agentid = null;
        $periodeid = null;
        $newvalue = null;
        if (array_key_exists("agentid", $_POST)) // Si le numéro de l'agent est défini
        {
            $agentid = $_POST["agentid"];
        }
        if (array_key_exists("periodeid", $_POST)) // Si l'identifiant de la période est défini
        {
            $periodeid = $_POST["periodeid"];
        }
        if (array_key_exists("newvalue", $_POST)) // Si la nouvelle valeur du flag est définie
        {
            $newvalue = $_POST["newvalue"];
        }

        if (is_null($agentid) or is_null($periodeid) or is_null($newvalue))
        {
            $erreur = "Impossible d'indentifier l'agent ou la période ou la nouvelle valeur (agentid = $agentid periodeid = $periodeid newvalue = $newvalue)";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
        }
        else
        {
            if (is_null($rhuser))
            {
                $rhuser = new agent($dbcon);
                $rhuser->load(SPECIAL_USER_IDLISTERHUSER);
            }

            $periodeobligatoire = new periodeobligatoire($dbcon);
            $periode = $periodeobligatoire->loadperiodefromid($periodeid);
            $agent = new agent($dbcon);
            $agent->load($agentid);

            // On a tous les paramètres ! On peut travailler
            $complement = new complement($dbcon);
            if ($fonctions->convertvaluetobool($newvalue))
            {
                // On active l'exception
                $complement->agentid($agentid);
                $complement->complementid("EXCEPT_PER_" . $periodeid);
                $complement->valeur('O');
                $complement->store();
                //var_dump("Je viens de sauvegarder");
                $corpsmail = "Une exception à la saisie obligatoire de congés sur la période du " . $fonctions->formatdate($periode["datedebut"]) . " au " . $fonctions->formatdate($periode["datefin"]) . " vient d'être ajoutée.<br>" .
                             "Vous pouvez donc annuler votre demande de congés sur cette période, si elle existe et reposer vos congés selon votre besoin.<br>";
                             "Pour plus d'informations, veuilez contacter le service de la DRH : " . $rhuser->mail() . "<br><br>";
            }
            else
            {
                // On désactive l'exception
                $complement->delete($agentid, "EXCEPT_PER_" . $periodeid);
                $corpsmail = "Une exception à la saisie obligatoire de congés sur la période du " . $fonctions->formatdate($periode["datedebut"]) . " au " . $fonctions->formatdate($periode["datefin"]) . " vient d'être supprimée.<br>" .
                             "Vous devez donc saisir, si ce n'est pas déjà fait, une (ou plusieurs) demande(s) de congés couvrant la totalité de cette période.<br>";
                             "Pour plus d'informations, veuilez contacter le service de la DRH : " . $rhuser->mail() . "<br><br>";
            }

            error_log(basename(__FILE__) . $fonctions->stripAccents(" Envoi du mail d'information à l'agent"));
            $rhuser->sendmail($agent,"Modification d'une exception pour une période obligatoire", $corpsmail);

            $coderetour = $agent->forceperiodeobligatoire($periode,true,$description);
            $result_json = array('status' => $coderetour, 'description' => $description);
        }
        return $result_json;
    }

    function send_mail()
    {
        global $dbcon;
        global $fonctions;

        $expediteurid = null;
        $destinataireid = null;
        $mailbody = null;
        if (array_key_exists("expediteurid", $_POST)) // Id de l'agent expéditeur du mail
        {
            $expediteurid = $_POST["expediteurid"];
        }
        if (array_key_exists("destinataireid", $_POST)) // Id de l'agent destinataire du mail
        {
            $destinataireid = $_POST["destinataireid"];
        }
        if (array_key_exists("corpsmail", $_POST)) // texte qui sera envoyé dans le mail
        {
            $mailbody = $_POST["corpsmail"];
        }
        if (is_null($expediteurid) or is_null($destinataireid) or is_null($mailbody))
        {
            $erreur = "Impossible d'envoyer un mail (expediteurid = $expediteurid destinataireid = $destinataireid corpsmail = $mailbody)";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
        }
        else
        {
            $expediteur = new agent($dbcon);
            $destinataire = new agent($dbcon);
            if (!$expediteur->load($expediteurid))
            {
                $erreur = "Impossible de charger l'agent expéditeur $expediteurid";
                $result_json = array('status' => 'Error', 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            }
            else if (!$destinataire->load($destinataireid))
            {
                $erreur = "Impossible de charger l'agent destinataire $expediteurid";
                $result_json = array('status' => 'Error', 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            }
            else
            {
                $expediteur->sendmail($destinataire,"Rappel sur les périodes de congés obligatoires.","$mailbody");
                $erreur = "";
                $result_json = array('status' => 'Ok', 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST (après sendmail à " . $destinataire->mail() . ") => Pas d'erreur"));
            }
        }
        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant le retour => " . $result_json["status"]));
        return $result_json;
    }

    function onoff_animation()
    {
        global $dbcon;
        global $fonctions;

        //error_log(basename(__FILE__) . $fonctions->stripAccents("Debut du WS force_periode"));
        $agentid = null;
        $display_flag = null;
        if (array_key_exists("agentid", $_POST)) // Id de l'agent
        {
            $agentid = $_POST["agentid"];
        }
        if (array_key_exists("display", $_POST)) // Date de début de la période obligatoire
        {
            $display_flag = $_POST["display"];
        }
        if (is_null($agentid) or is_null($display_flag))
        {
            $erreur = "Impossible d'activer/désactiver les animations ' (agentid = $agentid display_flag = $display_flag)";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
        }
        else
        {
            $complement = new complement($dbcon);
            if ($fonctions->convertvaluetobool($display_flag)) // Les animations doivent être affichées => On supprime le flag du complément
            {
                $complement->delete($agentid,complement::SHOW_ANIMATION);
            }
            else
            {
                $complement->agentid($agentid);
                $complement->complementid(complement::SHOW_ANIMATION);
                $complement->valeur($display_flag);
                $complement->store();
            }
            $erreur = "";
            $result_json = array('status' => 'Ok', 'description' => $erreur);
        }
        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant le retour => " . $result_json["status"]));
        return $result_json;
    }

    function force_periode()
    {
        global $dbcon;
        global $fonctions;

        //error_log(basename(__FILE__) . $fonctions->stripAccents("Debut du WS force_periode"));
        $agentid = null;
        $datedebutdb = null;
        $datefindb = null;
        $periodeid = null;
        if (array_key_exists("agentid", $_POST)) // Id de l'agent
        {
            $agentid = $_POST["agentid"];
        }
        if (array_key_exists("datedebut", $_POST)) // Date de début de la période obligatoire
        {
            $datedebutdb = $_POST["datedebut"];
        }
        if (array_key_exists("datefin", $_POST)) // Date de fin de la période obligatoire
        {
            $datefindb = $_POST["datefin"];
        }
        if (array_key_exists("periodeid", $_POST)) // Id de la période obligatoire
        {
            $periodeid = $_POST["periodeid"];
        }
        if (is_null($agentid) or is_null($datedebutdb) or is_null($datefindb) or is_null($periodeid))
        {
            $erreur = "Impossible de poser la période obligatorie (agentid = $agentid datedebutdb = $datedebutdb datefindb = $datefindb periodeid = $periodeid)";
            $result_json = array('status' => agent::CHECK_PERIODE_ERREUR, 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
        }
        else
        {
            $agent = new agent($dbcon);
            if (!$agent->load($agentid))
            {
                $erreur = "Impossible de récupérer l'agent $agentid";
                $result_json = array('status' => agent::CHECK_PERIODE_ERREUR, 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            }
            else
            {
                $periodeoblig = new periodeobligatoire($dbcon);
                $periode = $periodeoblig->loadperiodefromid($periodeid);
                $returndesc = '';
                $returncode = $agent->forceperiodeobligatoire($periode,false,$returndesc);
                if (in_array($returncode, array(agent::CHECK_PERIODE_AJOUTEE , agent::CHECK_PERIODE_COUVERTE, agent::CHECK_PERIODE_EXCEPTION)))
                {
                    $erreur = "";
                    $result_json = array('status' => $returncode, 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST (après forceperiodeobligatoire pour " . $agent->identitecomplete() . ") : Returncode = $returncode => Pas d'erreur"));
                }
                else
                {
                    $erreur = "Impossible de forcer la période pour $agentid : $returndesc" ;
                    $result_json = array('status' => agent::CHECK_PERIODE_ERREUR, 'description' => $erreur);
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
                }
            }

        }
        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant le retour => " . $result_json["status"]));
        return $result_json;

    }

    $errlog = '';
    $erreur = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');

    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'POST': // Modifie le statut d'une exception sur une période

            $authproblem = false;
            if (!isset($uid))
            {
                $authproblem = true;
                error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID n'est pas défini !"));
            }
            else
            {
                $userid = $fonctions->CASuserisG2TAdmin($uid);
                if ($userid!==false)
                {
                    // C'est un administrateur => C'est Ok
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID est un administrateur => C'est ok !"));
                }
                else
                {
                    // Ce n'est pas un administrateur donc on regarde si il a un profile RH !
                    $userid = $fonctions->useridfromCAS($uid);
                    if ($userid === false)
                    {
                        // Ce n'est pas un utilisateur G2T donc erreur !
                        $authproblem = true;
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID n'est pas un utilisateur G2T !"));
                    }
                    else
                    {   // On va charger l'utilisateur
                        $user = new agent($dbcon);
                        if (!$user->load($userid))
                        {
                            // On a eu un problème lors du chargement de l'utilisateur
                            $authproblem = true;
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" Impossible de charger l'utilisateur $userid !"));
                        }
                        elseif (!$user->estprofilrh(agent::PROFIL_RHCONGE))
                        {
                            // Il n'a pas le bon profile => erreur !
                            $authproblem = true;
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" L'utilisateur $userid n'a pas le bon profile RH !"));
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $fonctions->stripAccents(" L'utilisateur $userid a le bon profile RH => C'est ok !"));
                        }
                    }
                }
        
            }

            if ($authproblem)
            {
                $erreur = "Vous n'êtes pas autorisé à utiliser ce service.";
                $result_json = array('status' => 'Error', 'description' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel du WS en mode POST => Erreur = " . $erreur)));
                break;
            }

            $methode = null;
            if (array_key_exists("methode", $_POST)) // Si le numéro de l'agent est défini
            {
                $methode = $_POST["methode"];
                switch ($methode)
                {
                    case agent::WS_METHODE_EXCEPTION_PERIODE : 
                        $result_json = change_exception_periode();
                        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour du changement exception periode => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                        break;
                    case agent::WS_METHODE_SEND_MAIL :
                        $result_json = send_mail();
                        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour de l'envoi de mail => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                        break;
                    case agent::WS_METHODE_FORCE_PERIODE :
                        $result_json = force_periode();
                        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour du forçage des périodes => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                        break;
                    case agent::WS_METHODE_ONOFF_ANIMATION :
                        $result_json = onoff_animation();
                        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour de l'activation/désactivation animation => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                        break;
                    default:
                        $erreur = "La méthode du WS n'est pas définie ou mal définie.";
                        $result_json = array('status' => 'Error', 'description' => $erreur);
                        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel du WS en mode POST => Erreur = " . $erreur)));
                        break;
                }
            }
            break;
        case 'GET':
            $erreur = "Le mode GET n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode GET => Erreur = " . $erreur));
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
