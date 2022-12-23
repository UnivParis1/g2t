<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
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
    $arraydemandeur = array();
    $mail_gest = array();
    $mail_resp = array();
    $codeinterne = "";

    $cronuser = new agent($dbcon);
    if (!$cronuser->load(SPECIAL_USER_IDCRONUSER))   // Utilisateur CRON G2T
    {
        echo "Impossible de charger l'utilisateur CRON => Pas d'envoi de mail \n";
        $cronuser = null;
    }
    $drhuser = new agent($dbcon);
    if (!$drhuser->load(SPECIAL_USER_IDLISTERHUSER))  // Utilisateur Gestion de temps <=> DRH
    {
        echo "Impossible de charger l'utilisateur DRH => Pas d'envoi de mail \n";
        $drhuser = null;
    }
    
    while ($result = mysqli_fetch_row($query)) {
        $declaration = new declarationTP($dbcon);
        $declaration->load($result[0]);
        
        $agent = new agent($dbcon);
        $agent->load($declaration->agentid());

        if ($agent->structureid() == "")
        {
            // Si la structure du demandeur n'est plus définie ==> On ne traite pas sa demande
            echo "La structure d'affectation de l'agent " . $agent->identitecomplete() . " n'a pas définie => On ignore \n";
            if (!is_null($cronuser) and !is_null($drhuser))
            {
                echo "CRON G2T envoie le mail à la DRH (" . $drhuser->mail() . ") pour ajouter l'affectation de l'agent. \n";
                $cronuser->sendmail($drhuser,"Un agent a une demande de déclaration de temps partiel mais pas d'affectation dans G2T","L'agent " . $agent->identitecomplete() . " (" . $agent->agentid() . ") a une demande de déclaration de temps partiel mais n'a pas d'affectation à une structure dans G2T.
Cela est généralement dû à une affectation fonctionnelle manquante dans le dossier RH de l'agent.
Merci de contrôler son dossier.\n");
            }
            continue;
        }
        $structure = new structure($dbcon);
        $structure->load($agent->structureid());

        // Si aucun responsable de structure n'est défini on ne traite pas la déclaration de temps partiel 
        if (is_null($structure->responsable()))
        {
            echo "Pas de responsable de structure (id : " . $structure->id() . "), pas d'envoi de mail. \n";
            if (!is_null($cronuser) and !is_null($drhuser))
            {
                if (!in_array($agent->agentid(), $arraydemandeur))
                {
                    // CRON G2T envoie le mail à la DRH pour information
                    echo "CRON G2T envoie le mail à la DRH (" . $drhuser->mail() . ") pour information\n";
                    $cronuser->sendmail($drhuser,"Un agent a une demande de déclaration de temps partiel mais pas de responsable","L'agent " . $agent->identitecomplete() . " (" . $agent->agentid() . ") a une demande de déclaration de temps partiel mais n'a pas de responsable dans G2T.
Cela est généralement dû à une affectation fonctionnelle manquante dans le dossier RH de l'agent.
Merci de contrôler son dossier.\n");
                    $arraydemandeur[] = $agent->agentid();
                }
            }
        }
        else {
            // Si ce n'est pas le responsable de la structure qui a fait la demande
            // => C'est un agent
            // On regarde à qui on doit envoyer la demande de TP pour sa structure
            if ($agent->agentid() != $structure->responsable()->agentid()) 
            {
                // On est dans le cas d'une demande d'un agent
                $destinatairemail = $structure->agent_envoyer_a($codeinterne);
                if ($codeinterne == 2) // On envoie le mail au gestionnaire service courant
                {
                    if (isset($mail_gest[$destinatairemail->agentid()]))
                        $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] + 1;
                    else
                        $mail_gest[$destinatairemail->agentid()] = 1;
                } else // On envoie le mail au responsable service courant
                {
                    if (isset($mail_resp[$destinatairemail->agentid()]))
                        $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] + 1;
                    else
                        $mail_resp[$destinatairemail->agentid()] = 1;
                }
                
                if ($destinatairemail->agentid() == $cronuser->agentid()) // Si le destinataire est G2T CRON => problème de déclaration de responsable dans le structure => Mail à la DRH
                {
                    if (!in_array($structure->id(), $arraystruct))
                    {
                        echo "CRON G2T envoie le mail a la DRH (" . $drhuser->mail() . ") pour signaler que la structure " . $structure->nomcourt() . " n'a pas de responsable \n";
                        $cronuser->sendmail($drhuser,"Pas de responsable défini pour une structure","La structure " . $structure->nomlong() . " (" . $structure->nomcourt() . ") n'a pas de responsable dans G2T, alors que des déclarations de temps partiels sont sasies.
Cela est généralement dû à une fonction manquante dans le dossier RH du responsable.
Merci de contrôler le dossier RH du responsable.\n");
                        $arraystruct[] = $structure->id();
                    }
                }
            }        
            else 
            {
                // C'est le responsable de la structure qui a fait la demande
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
                    if ($destinatairemail->agentid() == $cronuser->agentid()) // Si le destinataire est G2T CRON => problème de déclaration de responsable dans le structure => Mail à la DRH
                    {
                        if (!in_array($structure->id(), $arraystruct))
                        {
                            echo "CRON G2T envoie le mail a la DRH (" . $drhuser->mail() . ") pour signaler que la structure " . $structure->nomcourt() . " n'a pas de responsable \n";
                            $cronuser->sendmail($drhuser,"Pas de responsable défini pour une structure","La structure " . $structure->nomlong() . " (" . $structure->nomcourt() . ") n'a pas de responsable dans G2T, alors que des déclarations de temps partiels sont sasies.
Cela est généralement dû à une fonction manquante dans le dossier RH du responsable.
Merci de contrôler le dossier RH du responsable.\n");
                            $arraystruct[] = $structure->id();
                        }
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
    // le code pour l'agent CRON dans G2T
    $agentcron->load(SPECIAL_USER_IDCRONUSER);

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