<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
    echo "\nDébut de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);


    $datelimite = $fonctions->formatdatedb(date("Y-m-d", strtotime("-2 month")));
    error_log(basename(__FILE__) . $fonctions->stripAccents(" Date limite = " . $datelimite));
    $tabconvention = array();
    $tabconvention = array_merge($tabconvention,$fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE,$datelimite));
    $tabconvention = array_merge($tabconvention,$fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_VALIDE, $datelimite));
    error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre de conventions trouvées = " . count($tabconvention)));

    $nbconvetionstraitees = 0;
    if (count($tabconvention)>0)
    {
        foreach($tabconvention as $teletravail)
        {
            if (trim($teletravail->esignatureid()) == "" and $teletravail->statut() == teletravail::TELETRAVAIL_ATTENTE and $teletravail->statutresponsable() == teletravail::TELETRAVAIL_ATTENTE)
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Actualisation des signataires de la convention télétravail " . $teletravail->teletravailid()));
                error_log(basename(__FILE__) . $fonctions->stripAccents(" L'ancienne liste des signataires => " . implode(',',$teletravail->listeidresponsable())));
                // La valeur TRUE force l'actualisation des id des responsables en fonction du paramétrage de la structure.
                $teletravail->listeidresponsable(true);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" La nouvelle liste des signataires => " . implode(',',$teletravail->listeidresponsable())));
                $teletravail->store();
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Enregistrement terminé"));
            }
            else if (trim($teletravail->esignatureid())!= "")
            {
                // Si la date de fin est récente (moins de 2 mois) ou dans le futur
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Date de fin de la convention : " . $fonctions->formatdatedb($teletravail->datefin())));
                if ($fonctions->formatdatedb($teletravail->datefin()) >= $datelimite)
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" On va synchro la convention esignatureid = " . $teletravail->esignatureid() . " Date de fin : " . $teletravail->datefin()));
                    $result_json = $fonctions->synchroniseconventionteletravail($teletravail->esignatureid());
                    if ($result_json['status']=='Error')
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" On a rencontré une erreur => On break"));
                        break;
                    }
                    $nbconvetionstraitees++;
                }
                // La convention est dans le passée (elle est terminée depuis plus de 2 mois) => Pas de synchronisation
                else
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La convention de télétravail " . $teletravail->esignatureid() . " est déjà terminée (date fin : " . $teletravail->datefin() . ") donc pas de synchronisation nécessaire."));
                }
            }
        }
    }
    error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre de conventions synchronisées : $nbconvetionstraitees "));
    echo "Fin de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
        
    echo "Envoi du mail de rappel aux agents suivants qui doivent signer la convention \n";
    
    $cronagent = new agent($dbcon);
    if (!$cronagent->load(SPECIAL_USER_IDCRONUSER))
    {
        echo "Impossible de charger l'utilisateur CRON";
    }
    else
    {
        $tabdestinataireesignature = array();
        $tabdestinataireg2t = array();
        $datelimite = $fonctions->formatdatedb(date("Y-m-d", strtotime("-2 month")));
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Date limite = " . $datelimite));
        $tabconvention = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE,$datelimite);
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre de conventions trouvées = " . count($tabconvention)));
        $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
        foreach($tabconvention as $convention)
        {
            $esignatureid = $convention->esignatureid();
            // On a un identifiant eSignature
            if ($esignatureid <>'' and $esignatureid>0)
            {
                echo "La convention (eSignatureid = $esignatureid) est dans eSignature => On récupère les informations \n";

                $esignature = new esignature($dbcon);
                $response = $esignature->get_signrequest($esignatureid);

                if (is_string($response))
                {
                    echo "Une erreur s'est produite dans la récupération des informations : $response \n";
                    continue;
                }

                // Le premier currentstepnumber commence à 0 !!!
                $currentstepnumber = $esignature->get_signrequest_currentstep($esignatureid);
                if (is_string($currentstepnumber))
                {
                    echo "Une erreur s'est produite dans la récupération de l'étape courante : $currentstepnumber \n";
                    continue;
                }

                $recipientlist = $esignature->get_signrequest_recipients($esignatureid);
                if (is_string($recipientlist))
                {
                    echo "Une erreur s'est produite dans la récupération des signataires : $recipientlist \n";
                    continue;
                }
                
                $nbworkflowsteps = count($recipientlist);
                echo "nbworkflowsteps = $nbworkflowsteps \n";
                if ($currentstepnumber < $nbworkflowsteps -2)  // On ne traite pas les deux derniers niveaux de signature
                {
                    $currentstep = $recipientlist[$currentstepnumber]; // L'index comence à 0
                    foreach($currentstep as $esignaturerecipient)
                    {
                        $destinataire = new agent($dbcon);
                        if (!$destinataire->loadbyemail($esignaturerecipient->mail))
                        {
                            echo "Envoi impossible au destinataire " . $esignaturerecipient->mail . "\n";
                        }
                        else
                        {
                            echo "Le destinataire est : " . $destinataire->identitecomplete() . " \n";
                            $tabdestinataireesignature[$destinataire->agentid()] = $destinataire;
                        }
                    }
                }
            }
            elseif ($convention->statutresponsable() == teletravail::TELETRAVAIL_ATTENTE)
            {
                echo "Le responsable n'a pas complete la convention. \n";
                $agent = new agent($dbcon);
                $agent->load($convention->agentid());

                if (count($convention->listeidresponsable())>0)
                {
                    foreach($convention->listeidresponsable() as $respid)
                    {
                        $responsable = new agent($dbcon);
                        $responsable->load($respid);
                        $tabdestinataireg2t[$responsable->agentid()] = $responsable;
                    }
                }
                else
                {
                    $responsable = $agent->getsignataire(null,$structresp);
                    if (is_null($responsable) or $responsable===false)
                    {
                        echo "On n'envoie pas de rappel au responsable de l'agent => car il n'est pas défini \n";
                    }
                    else
                    {
                        echo "On envoie un rappel au responsable de l'agent => Responsable = " . $responsable->identitecomplete() . " \n";
                        $tabdestinataireg2t[$responsable->agentid()] = $responsable;
                    }
                    ////////////////////////////
                    // Dans le cas d'une délégation, le responsable peut quand même vouloir recevoir les demandes
                    // On regarde donc s'il y a une délégation dans structure du responsable (obtenu avec agent::getsignataire)
                    if (!is_null($structresp))
                    {
                        $delegation = $structresp->getdelegation(true);
                        if ($fonctions->convertvaluetobool($delegation->continuesendtoresp))
                        {
                            $responsable = $structresp->responsablesiham();
                            if (is_null($responsable) or $responsable===false)
                            {
                                echo "On n'envoie pas de rappel au responsable SIHAM de la structure car il n'est pas défini \n";
                            }
                            else
                            {
                                echo "On envoie un rappel au responsable SIHAM de la structure de l'agent => Responsable = " . $responsable->identitecomplete() . " \n";
                                $tabdestinataireg2t[$responsable->agentid()] = $responsable;
                            }
                        }
                    }
                    else
                    {
                        echo "La structure est inconnue => Pas de recherche de délégation \n";
                    }
                }
            }
            else
            {
                echo "Pas d'identifiant eSignature et le statut du responsable n'est pas 'en attente' (convention " . $convention->teletravailid()  . ") => Pas de traitement \n";
            }
        }
        
        foreach($tabdestinataireg2t as $destinataire)
        {
            echo "On va envoyer un mail au responsable " . $destinataire->identitecomplete() . " car il n'a pas complete la convention dans G2T.\n";
            $cronagent->sendmail($destinataire,
                                 "Convention de télétravail à compléter dans G2T", 
                                 "Vous avez une ou plusieurs conventions de télétravail à compléter dans G2T.<br>Vous pouvez les consulter dans votre menu 'Responsable' ou 'Gestionnaire'.<br>"
                                 );
        }
        foreach($tabdestinataireesignature as $destinataire)
        {
            echo "On va envoyer un mail a l'agent " . $destinataire->identitecomplete() . " car il n'a pas signe/vise une convention dans eSignature ($eSignature_url).\n";
            $cronagent->sendmail($destinataire->mail(),
                                 "Convention de télétravail à signer/viser dans eSignature", 
                                 "Vous avez une ou plusieurs conventions de télétravail à signer/viser dans eSignature.<br>Vous pouvez les consulter directement à l'adresse suivante : <a href='$eSignature_url'>$eSignature_url</a>.<br><br>Cordialement.<br>" . $cronagent->identitecomplete() . "<br>"
                                 );
        }
        
    }
    echo "Fin de l'envoi du mail de rappel aux agents suivants qui doivent signer la convention \n";

    echo "Envoi du mail de rappel aux responsables qui doivent valider des déplacements/annulations de jours de télétravail \n";
    $tabdestinataireg2t = array();
    $cronagent = new agent($dbcon);
    if (!$cronagent->load(SPECIAL_USER_IDCRONUSER))
    {
        echo "Impossible de charger l'utilisateur CRON";
    }
    else
    {
        $ttexceptionliste = $fonctions->listettexceptionavecstatut(ttexception::STATUT_ENATTENTE, $fonctions->anneeref() . $fonctions->debutperiode());
        foreach($ttexceptionliste as $ttexception)
        {
            $agent = new agent($dbcon);
            if ($agent->load($ttexception->agentid))
            {
                $signataire = $agent->getsignataire();
                if ($signataire !== false)
                {
                    $tabdestinataireg2t[$signataire->agentid()] = $signataire;
                }
            }
        }
        foreach($tabdestinataireg2t as $destinataire)
        {
            echo "On va envoyer un mail au signataire " . $destinataire->identitecomplete() . " car il n'a pas validé/refusé une demande de modification du télétravail.\n";
            $cronagent->sendmail($destinataire,
                                 "Modification d'une occurrence de télétravail en attente", 
                                 "Vous avez une ou plusieurs demandes de modification de télétravail en attente dans G2T.<br>Vous pouvez les consulter dans votre menu 'Responsable' ou 'Gestionnaire'.<br>"
                                );
        }
    }
    echo "Fin de l'envoi du mail de rappel aux responsables qui doivent valider des déplacements/annulations de jours de télétravail \n";

?>