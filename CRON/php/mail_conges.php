<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";

    // On selectionne les demandes en attente de validation qui débutent il y a moins de 2 ans (année en cours et année précédente) mais qui ne sont pas postérieure à la période en cours (< Anneeref +1 + debut_période)
    // Les demandes plus anciennes ne sont pas remontées car le responsable/gestionnaire ne peut plus les valider.
    $sql = "SELECT DEMANDEID FROM DEMANDE WHERE STATUT = '" . demande::DEMANDE_ATTENTE . "' AND DATEDEBUT >='" . ($fonctions->anneeref() - 1) . $fonctions->debutperiode() . "' AND DATEDEBUT < '" . ($fonctions->anneeref() + 1) . $fonctions->debutperiode() . "'";
    // echo "SQL des demandes = $sql \n";
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "SELECT DEMANDEID => $erreur_requete \n";

    $arraystruct = array();
    $mail_gest = array();
    $mail_resp = array();
    $arraydemandeur = array();

    $codeinterne = null;
    
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
        $demande = new demande($dbcon);
        $demande->load($result[0]);

        // On récupère le demandeur
        $demandeur = $demande->agent();
        echo "Le demandeur de la demande " . $demande->id() . " est " . $demandeur->identitecomplete() . " (Agentid = " . $demandeur->agentid() . ")\n";
        // On récupère la liste des affectation du demandeur à la date du jour
        $affliste = $demandeur->affectationliste($fonctions->formatdatedb($date), $fonctions->formatdatedb($date));

        /*
         * echo "Liste des affectations : \n";
         * print_r($affliste);
         * echo "\n";
         */

        if (is_null($affliste) or count($affliste) == 0) {
            // Si le demandeur n'est plus affecté ==> On ne traite pas sa demande
            echo "L'agent " . $demandeur->identitecomplete() . " n'a pas (ou plus) d'affectation => On ignore \n";
            continue;
        }
        // On conserve la première affectation (et sans doute la seule)
        $affectation = current($affliste);

        echo "l'affectation de l'agent " . $demandeur->identitecomplete() . " est : " . $affectation->structureid() . "\n";
        
        if ($affectation->structureid() . "" == "") 
        {
            // Si la structure du demandeur n'est plus définie ==> On ne traite pas sa demande
            echo "La structure d'affectation de l'agent " . $demandeur->identitecomplete() . " n'a pas définie => On ignore \n";
            if (!is_null($cronuser) and !is_null($drhuser))
            {
                echo "CRON G2T envoie le mail à la DRH (" . $drhuser->mail() . ") pour ajouter l'affectation de l'agent. \n";
                $cronuser->sendmail($drhuser,"Un agent a une demande de congés/d'absence mais pas d'affectation dans G2T","L'agent " . $demandeur->identitecomplete() . " (" . $demandeur->agentid() . ") a une demande de congés/d'absence mais n'a pas d'affectation à une structure dans G2T.
Cela est généralement dû à une affectation fonctionnelle manquante dans le dossier RH de l'agent.
Merci de contrôler son dossier.\n");
            }
            continue;
        }
        
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());

        // Si ce n'est pas le responsable de la structure qui a fait la demande
        // => C'est un agent
        // On regarde à qui on doit envoyer la demande de congés pour sa structure
        $responsable = $structure->responsable();
        if (is_null($responsable))
        {
            // S'il n'y a pas de responsable => 
            //  - Dans le cas d'un agent on ne peut pas prévenir le responsable
            //  - Dans le cas du responsable, on est incapable de déterminer que c'est lui le responsable
            // En théorie ça ne se produit jamais car il y a tjrs un responsable (au pire G2T CRON)
            echo "Pas de responsable de structure (id : " . $affectation->structureid() . "), pas d'envoi de mail. \n";
            // On va envoyer un message à la DRH afin d'indiquer qu'un agent a une demande en attente, mais pas de responsable
            if (!is_null($cronuser) and !is_null($drhuser))
            {
                if (!in_array($demandeur->agentid(), $arraydemandeur))
                {
                    // CRON G2T envoie le mail à la DRH pour information
                    echo "CRON G2T envoie le mail à la DRH (" . $drhuser->mail() . ") pour information\n";
                    $cronuser->sendmail($drhuser,"Un agent a une demande de congés/d'absence mais pas de responsable","L'agent " . $demandeur->identitecomplete() . " (" . $demandeur->agentid() . ") a une demande de congés/d'absence mais n'a pas de responsable dans G2T.
Cela est généralement dû à une affectation fonctionnelle manquante dans le dossier RH de l'agent.
Merci de contrôler son dossier.\n");
                    $arraydemandeur[] = $demandeur->agentid();
                }
            }
        }
        else {
            // Si l'affectation correspondant à la demande est commencée => Sinon on ne dit rien !!!
            if ($fonctions->formatdatedb($affectation->datedebut()) <= $fonctions->formatdatedb($date)) 
            {
                if ($affectation->agentid() != $responsable->agentid()) 
                {
                    // On est dans le cas d'une demande d'un agent
                    $destinatairemail = $structure->agent_envoyer_a($codeinterne);
                    if (! is_null($destinatairemail))
                    {
                        if ($codeinterne == structure::MAIL_AGENT_ENVOI_GEST_COURANT) // On envoie le mail au gestionnaire service courant
                        {
                            if (isset($mail_gest[$destinatairemail->agentid()]))
                                $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] . $demande->id() . ',' ;
                            else
                                $mail_gest[$destinatairemail->agentid()] = $demande->id() . ',';
                        }
                        else // On envoie le mail au responsable service courant
                        {
                            if (isset($mail_resp[$destinatairemail->agentid()]))
                                $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_resp[$destinatairemail->agentid()] = $demande->id() . ',';
                            
                            if ($destinatairemail->agentid() == $cronuser->agentid()) // Si le destinataire est G2T CRON => problème de déclaration de responsable dans le structure => Mail à la DRH
                            {
                                if (!in_array($structure->id(), $arraystruct))
                                {
                                    echo "CRON G2T envoie le mail a la DRH (" . $drhuser->mail() . ") pour signaler que la structure " . $structure->nomcourt() . " n'a pas de responsable \n";
                                    $cronuser->sendmail($drhuser,"Pas de responsable défini pour une structure","La structure " . $structure->nomlong() . " (" . $structure->nomcourt()  . ") n'a pas de responsable dans G2T, alors que des demandes de congés sont sasies.
Cela est généralement dû à une fonction manquante dans le dossier RH du responsable.
Merci de contrôler le dossier RH du responsable.\n");
                                    $arraystruct[] = $structure->id();
                                }
                            }
                        }
                    }
                    else
                    {
                        echo "Impossible de trouver le destinataire du mail d'une demande agent \n";
                    }
                }
                else 
                {
                    // On est dans le cas d'une demande d'un responsable de la structure
                    $destinatairemail = $structure->resp_envoyer_a($codeinterne);
                    if (! is_null($destinatairemail)) 
                    {
                        switch ($codeinterne)
                        {
                            case structure::MAIL_RESP_ENVOI_RESP_PARENT :
                                $typesignataire = 'responsable';
                                $structureparent = $structure->parentstructure();
                                break;
                            case structure::MAIL_RESP_ENVOI_GEST_PARENT :
                                $typesignataire = 'gestionnaire';
                                $structureparent = $structure->parentstructure();
                                break;
                            case structure::MAIL_RESP_ENVOI_GEST_COURANT :
                                $typesignataire = 'gestionnaire';
                                $structureparent = $structure;
                                break;
                        }
                        // echo "destinatairemailid = " . $destinatairemail->agentid() . "\n";
                        if ($codeinterne == structure::MAIL_RESP_ENVOI_GEST_PARENT or $codeinterne == structure::MAIL_RESP_ENVOI_GEST_COURANT) // 2=Gestionnaire service parent 3=Gestionnaire service courant
                        {
                            if (isset($mail_gest[$destinatairemail->agentid()]))
                                $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_gest[$destinatairemail->agentid()] = $demande->id() . ',';
                        } 
                        else // Responsable service parent
                        {
                            if (isset($mail_resp[$destinatairemail->agentid()]))
                                $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_resp[$destinatairemail->agentid()] = $demande->id() . ',';
                        }
                        if ($destinatairemail->agentid() == $cronuser->agentid()) // Si le destinataire est G2T CRON => problème de déclaration de responsable dans le structure => Mail à la DRH
                        {
                            if (!in_array($structure->id(), $arraystruct))
                            {                                
                                echo "CRON G2T envoie le mail a la DRH (" . $drhuser->mail() . ") pour signaler que la structure " . $structure->nomcourt() . " n'a pas de responsable \n";
                                $corpsmail = "Le responsable de la structure " . $structure->nomlong() . " (" . $structure->nomcourt()  . ") a une demande à valider. Le signataire doit être le $typesignataire de la structure " . $structureparent->nomlong() . " (" . $structureparent->nomcourt()  . "). Cependant celui-ci n'est pas défini.
Cela est généralement dû à une fonction manquante dans le dossier RH du responsable.
Dans le cas d'un gestionnaire, il faut que le responsable de la structure modifie le paramétrage de la structure dans G2T (Menu responsable/Paramétrage des dossiers et des structures).
Merci de contrôler le dossier RH du responsable ou le gestionnaire saisi dans G2T.\n";
                                /*
                                $corpsmail = "La structure " . $structure->nomlong() . " (" . $structure->nomcourt()  . ") n'a pas de responsable dans G2T, alors que des demandes de congés sont sasies.
Cela est généralement dû à une fonction manquante dans le dossier RH du responsable.
Merci de contrôler le dossier RH du responsable.\n";
                                */
                                $cronuser->sendmail($drhuser,"Pas de responsable défini pour une structure",$corpsmail);
                                $arraystruct[] = $structure->id();
                            }
                        }
                    }
                    else
                    {
                        echo "Impossible de trouver le destinataire du mail d'une demande responsable \n";
                    }
                }
            }
        }
        unset($demande);
        unset($structure);
        unset($declarationliste);
        unset($declaration);
        unset($affectation);
        unset($affliste);
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
    foreach ($mail_resp as $agentid => $listedemande) {
        $responsable = new agent($dbcon);
        $responsable->load($agentid);
        $nbredemande = substr_count($listedemande, ',');
        echo "Avant le sendmail mail (Responsable) = " . $responsable->mail() . " (" . $responsable->identitecomplete() . " agentid = " . $responsable->agentid() . ") : Il y a $nbredemande demandes en attente \n";

        $agentcron->sendmail($responsable, "Demandes de congés ou d'autorisations d'absence en attente", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Responsable'.\n", null);
        unset($responsable);
    }
    foreach ($mail_gest as $agentid => $listedemande) {
        $gestionnaire = new agent($dbcon);
        $gestionnaire->load($agentid);
        $nbredemande = substr_count($listedemande, ',');
        echo "Avant le sendmail mail (Gestionnaire) = " . $gestionnaire->mail() . " (" . $gestionnaire->identitecomplete() . " agentid = " . $gestionnaire->agentid() . ") : Il y a $nbredemande demandes en attente \n";

        $agentcron->sendmail($gestionnaire, "Demandes de congés ou d'autorisations d'absence en attente", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Gestionnaire'.\n", null);
        unset($gestionnaire);
    }
    unset($agentcron);
    echo "Fin de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";

?>