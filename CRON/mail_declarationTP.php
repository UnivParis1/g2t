<?php
    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');
/*    
    require_once ("../html/class/agent.php");
    require_once ("../html/class/structure.php");
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("../html/class/fpdf/fpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
*/
    
    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'envoi des mail de déclaration de TP " . date("d/m/Y H:i:s") . "\n";

    // On selectionne les demandes en attente de validation
    $sql = "SELECT DECLARATIONID FROM DECLARATIONTP WHERE STATUT = '" . declarationTP::DECLARATIONTP_ATTENTE . "' AND AGENTID IN (SELECT AGENTID FROM AGENT)";
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
    {
        echo "Erreur : SELECT DEMANDEID => $erreur_requete \n";
    }
    
    $arraystruct = array();
    $mail_gest = array();
    $mail_resp = array();
    $codeinterne = "";

    while ($result = mysqli_fetch_row($query)) {
        $declaration = new declarationTP($dbcon);
        $declaration->load($result[0]);
        
        $agent = new agent($dbcon);
        $agent->load($declaration->agentid());

        if ($agent->structureid() == "")
        {
            echo "Le structureid n'est pas défini pour l'agent " . $agent->identitecomplete() . ".... Donc impossible d'envoyer un mail au responsable \n";
            continue;
        }
        $structure = new structure($dbcon);
        $structure->load($agent->structureid());

        // Si ce n'est pas le responsable de la structure qui a fait la demande
        // => C'est un agent
        // On regarde à qui on doit envoyer la demande de TP pour sa structure
        if (is_null($structure->responsable()))
            echo "Pas de responsable de structure (id : " . $structure->id() . "), pas d'envoi de mail. \n";
        else {
            if ($agent->agentid() != $structure->responsable()->agentid()) {
                $destinatairemail = $structure->agent_envoyer_a($codeinterne);
                if ($codeinterne == 2) // Gestionnaire service courant
                {
                    if (isset($mail_gest[$destinatairemail->agentid()]))
                        $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] + 1;
                    else
                        $mail_gest[$destinatairemail->agentid()] = 1;
                } else // Responsable service courant
                {
                    if (isset($mail_resp[$destinatairemail->agentid()]))
                        $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] + 1;
                    else
                        $mail_resp[$destinatairemail->agentid()] = 1;
                }
            }        // C'est le responsable de la structure qui a fait la demande
            else {
                $destinatairemail = $structure->resp_envoyer_a($codeinterne);
                if (! is_null($destinatairemail)) {
                    // echo "destinatairemailid = " . $destinatairemail->agentid() . "\n";
                    if ($codeinterne == 2 or $codeinterne == 3) // 2=Gestionnaire service parent 3=Gestionnaire service courant
                    {
                        if (isset($mail_gest[$destinatairemail->agentid()]))
                            $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] + 1;
                        else
                            $mail_gest[$destinatairemail->agentid()] = 1;
                    } else // Responsable service parent
                    {
                        if (isset($mail_resp[$destinatairemail->agentid()]))
                            $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] + 1;
                        else
                            $mail_resp[$destinatairemail->agentid()] = 1;
                    }
                }
            }
        }
        unset($structure);
        unset($declaration);
        unset($agent);
    }

    echo "mail_resp=";
    print_r($mail_resp);
    echo "\n";
    echo "mail_gest=";
    print_r($mail_gest);
    echo "\n";
    // Création de l'agent CRON G2T
    $agentcron = new agent($dbcon);
    // -1 est le code pour l'agent CRON dans G2T
    $agentcron->load('-1');

    foreach ($mail_resp as $agentid => $nbredemande) {
        $responsable = new agent($dbcon);
        $responsable->load($agentid);
        echo "Avant le sendmail mail (Responsable) = " . $responsable->mail() . " (" . $responsable->identitecomplete() . " agentid = " . $responsable->agentid() . ") \n";

        $agentcron->sendmail($responsable, "Des demandes de temps partiel sont en attente", "Il y a $nbredemande demande(s) de temps-partiel en attente de validation.\nEn tant que responsable de structure, merci de bien vouloir les valider dès que possible.\n", null);
        unset($responsable);
    }
    foreach ($mail_gest as $agentid => $nbredemande) {
        $gestionnaire = new agent($dbcon);
        $gestionnaire->load($agentid);
        echo "Avant le sendmail mail (Gestionnaire) = " . $gestionnaire->mail() . " (" . $gestionnaire->identitecomplete() . " agentid = " . $gestionnaire->agentid() . ") \n";

        $agentcron->sendmail($gestionnaire, "Des demandes de temps partiel sont en attente", "Il y a $nbredemande demande(s) de temps-partiel en attente de validation.\nEn tant que gestionnaire de structure, merci de bien vouloir les valider dès que possible.\n", null);
        unset($gestionnaire);
    }

    unset($agentcron);
    echo "Fin de l'envoi des mail de déclaration de TP " . date("d/m/Y H:i:s") . "\n";

?>