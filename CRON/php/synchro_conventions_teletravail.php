<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    echo "\nDébut de la synchronisation des conventions de télétravail " . date("d/m/Y H:i:s") . "\n";
    $fonctions = new fonctions($dbcon);


    $tabconvention = array();
    $tabconvention = array_merge($tabconvention,$fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE));
    $tabconvention = array_merge($tabconvention,$fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_VALIDE));
    error_log(basename(__FILE__) . $fonctions->stripAccents(" Nombre de conventions trouvées = " . count($tabconvention)));

    $nbconvetionstraitees = 0;
    if (count($tabconvention)>0)
    {
        $datelimite = $fonctions->formatdatedb(date("Y-m-d", strtotime("-2 month")));
        foreach($tabconvention as $teletravail)
        {
            if (trim($teletravail->esignatureid())!= "")
            {
                // Si la date de fin est récente (moins de 2 mois) ou dans le futur
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Date limite = " . $datelimite));
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
        $tabconvention = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE);
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
                }

                // Le premier currentstepnumber commence à 0 !!!
                $currentstepnumber = $esignature->get_signrequest_currentstep($esignatureid);
                if (is_string($currentstepnumber))
                {
                    echo "Une erreur s'est produite dans la récupération de l'étape courante : $currentstepnumber \n";
                }

                $recipientlist = $esignature->get_signrequest_recipients($esignatureid);
                if (is_string($recipientlist))
                {
                    echo "Une erreur s'est produite dans la récupération des signataires : $recipientlist \n";
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
                                 "Vous avez une ou plusieurs conventions de télétravail à compléter dans G2T.\nVous pouvez les consulter dans votre menu 'Responsable' ou 'Gestionnaire'.\n"
                                 );
        }
        foreach($tabdestinataireesignature as $destinataire)
        {
            echo "On va envoyer un mail a l'agent " . $destinataire->identitecomplete() . " car il n'a pas signe/vise une convention dans eSignature ($eSignature_url).\n";
            $cronagent->sendmail($destinataire->mail(),
                                 "Convention de télétravail à signer/viser dans eSignature", 
                                 "Vous avez une ou plusieurs conventions de télétravail à signer/viser dans eSignature.\nVous pouvez les consulter directement à l'adresse suivante : <a href='$eSignature_url'>$eSignature_url</a>.\n\nCordialement.\n" . $cronagent->identitecomplete() . "\n"
//                                 "Vous avez une ou plusieurs conventions de télétravail à signer/viser dans eSignature.\nVous pouvez les consulter directement à l'adresse suivante : $eSignature_url.\n\nCordialement.\n" . $cronagent->identitecomplete() . "\n"
                                 );
        }
        
    }
    echo "Fin de l'envoi du mail de rappel aux agents suivants qui doivent signer la convention \n";
	
?>