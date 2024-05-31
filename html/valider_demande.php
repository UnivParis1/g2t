<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    $userid = null;
    if (isset($_POST["userid"]))
    {
        // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
        $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
        if ($CASuserId!==false)
        {
            // On a l'agentid de l'agent => C'est un administrateur donc on peut forcer le userid avec la valeur du POST
            $userid = $_POST["userid"];
        }
        else
        {
            $userid = $fonctions->useridfromCAS($uid);
            if ($userid === false)
            {
                $userid = null;
            }
        }
    }

    if (is_null($userid) or ($userid == ""))
    {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
//        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["previous"]))
        $previoustxt = $_POST["previous"];
    else
        $previoustxt = null;
    if ($previoustxt == 'yes')
        $previous = 1;
    else
        $previous = 0;

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    //$longueurmaxmotif = $fonctions->logueurmaxcolonne('DEMANDE','MOTIFREFUS');


    // Récupération du mode => resp ou gestion
    $mode = $_POST["mode"];
    if (is_null($mode) or $mode == "") {
        $mode = MODE_RESPONSABLE;
        echo "Le mode n'est pas précisé ==> on met le mode responsable <br>";
    }

    $statutliste = null;
    $motifliste = null;
    if (isset($_POST['statut'])) {
        $statutliste = $_POST['statut'];
    }
    if (isset($_POST['motif'])) {
        $motifliste = $_POST['motif'];
    }

/*
     echo "_POST = "; print_r($_POST); echo "<br>";
     var_dump($statutliste);
     var_dump($motifliste);
*/

    if (is_array($statutliste))
    {
        if (strcasecmp($mode,MODE_CONSULTANT)==0)
        {
            $cron = new agent($dbcon);
            $cron->load(SPECIAL_USER_IDCRONUSER);
            foreach ($statutliste as $demandeid => $statut)
            {
                $demandemodifiee = false;
                if (strcasecmp($statut, demande::DEMANDE_VALIDE) == 0 )
                {
                    //var_dump("La demande $demandeid est validée => On va l'enregistrer");
                    $demandecomplement  = new demandecomplement($dbcon);
                    $demandecomplement->demandeid($demandeid);
                    $demandecomplement->complementid(demandecomplement::DEMANDE_AVIS_STATUT_LABEL);
                    $demandecomplement->valeur($statut);
                    $demandecomplement->store();
                    $demandemodifiee = true;
                }
                elseif (strcasecmp($statut, demande::DEMANDE_REFUSE) == 0 )
                {
                    $motif = '';
                    if (isset($motifliste["$demandeid"]))
                    {
                        $motif = $motifliste["$demandeid"];
                    }
                    if ((strcasecmp($statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($statut, demande::DEMANDE_ANNULE) == 0) and $motif == "")
                    {
                        $errlog = "Le motif du refus est obligatoire.";
                        echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    } 
                    else 
                    {
                        //var_dump("La demande $demandeid est refusée => On va l'enregistrer");                    
                        $demandecomplement  = new demandecomplement($dbcon);
                        $demandecomplement->demandeid($demandeid);
                        $demandecomplement->complementid(demandecomplement::DEMANDE_AVIS_STATUT_LABEL);
                        $demandecomplement->valeur($statut);
                        $demandecomplement->store();
                        $demandecomplement  = new demandecomplement($dbcon);
                        $demandecomplement->demandeid($demandeid);
                        $demandecomplement->complementid(demandecomplement::DEMANDE_AVIS_MOTIF_LABEL);
                        $demandecomplement->valeur($motif);
                        $demandecomplement->store();
                        $demandemodifiee = true;
                    }
                }
                if ($demandemodifiee)
                {
                    // On envoie un mail au signataire des demanes de l'agent 
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    $agent = new agent($dbcon);
                    $agent->load($demande->agentid());
                    $signataire = $agent->getsignataire();
                    
                    $corpmail = "Un avis a été déposé par " . $user->identitecomplete() . " sur la demande suivante :\n";
                    $corpmail = $corpmail . "\n";
                    $corpmail = $corpmail . "Demandeur : " . $agent->identitecomplete() . "\n";
                    $corpmail = $corpmail . "Type de demande : " . $demande->typelibelle()  . "\n";
                    $corpmail = $corpmail . "Début : " . $fonctions->formatdate($demande->datedebut()) .  " " . $fonctions->nommoment($demande->moment_debut()) . "\n";
                    $corpmail = $corpmail . "Fin : " . $fonctions->formatdate($demande->datefin()) .  " " . $fonctions->nommoment($demande->moment_fin()) . "\n";
                    $corpmail = $corpmail . "\n";
                    
                    $cron->sendmail($signataire, "Un avis a été déposé sur une demande de congés ou d'absence", $corpmail);
                }
            }
        }
        else
        {
            $cronuser = new agent($dbcon);
            $cronuser->load(SPECIAL_USER_IDCRONUSER);
            foreach ($statutliste as $demandeid => $statut)
            {
                //echo "Le statut est $statut <br>";
                if (strcasecmp($statut, demande::DEMANDE_AVIS) == 0 )
                {
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    $complement = new complement($dbcon);
                    $complement->load($demande->agent()->agentid(),complement::AVIS_CONGES_LABEL);
                    if ($complement->agentid()==$demande->agent()->agentid() and $complement->valeur()!="")
                    {
                        $consultant = new agent($dbcon);
                        if ($consultant->load($complement->valeur()))
                        {
                            $corpmail = $fonctions->mailbody_avis($demande);
                            if ($corpmail!='')
                            {
                                $cronuser->sendmail($consultant, "Demande d'avis sur une demande de congés ou d'absence" , $corpmail);
                            }
                        }
                    }
                    // On repasse le statut de la demande à "EN ATTENTE" pour éviter que l'on reposte une demande d'avis
                    $_POST['statut'][$demandeid] = demande::DEMANDE_ATTENTE;
                    $statutliste[$demandeid] = demande::DEMANDE_ATTENTE;
                }
                elseif (strcasecmp($statut, demande::DEMANDE_ATTENTE) != 0 )
                {
                    //var_dump("On est après le test statut");
                    $motif = '';
                    if (isset($motifliste["$demandeid"]))
                    {
                        $motif = $motifliste["$demandeid"];
                    }
                    $demande = new demande($dbcon);
                    // echo "cleelement = $cleelement demandeid = $demandeid <br>";
                    $demande->load($demandeid);
                    if ($statut == demande::DEMANDE_REFUSE)
                    {
                        $demande->motifrefus($motif);
                    }
                    if ($demande->statut() == $statut)
                    {
                        // Pas de changement de statut de la demande => On ne sauvegarde rien !!!
                        $errlog = "Le statut de la demande est inchangé, donc pas de sauvegarde.";
                        echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    }
                    else
                    {
                        $demande->statut($statut);
        //                if (strcasecmp($statut, demande::DEMANDE_REFUSE) == 0 and $motif == "") {
                        if ((strcasecmp($statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($statut, demande::DEMANDE_ANNULE) == 0) and $motif == "")
                        {
                            $errlog = "Le motif du refus est obligatoire.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        } else {
                            $msgerreur = "";
                            $msgerreur = $demande->store();
                            if ($msgerreur != "")
                            {
                                echo $fonctions->showmessage(fonctions::MSGERROR, "Pas de sauvegarde car " . $msgerreur);
                            }
                            else {
                                $ics = null;
                                $pdffilename[0] = $demande->pdf($user->agentid());
                                $agent = $demande->agent();
                                //echo "<br>Le statut de la demande est : $statut <br><br>";
                                if ((strcasecmp($statut, demande::DEMANDE_VALIDE) == 0) or (strcmp($statut, demande::DEMANDE_ANNULE) == 0)) {
                                    $ics = $demande->ics($agent->mail());
                                }
                                elseif ((strcmp($statut, demande::DEMANDE_REFUSE) == 0)) {
                                    // On refuse une demande => On doit mettre à jour l'agenda car la demande est en statut "TENTATIVE"
                                    $ics = $demande->ics($agent->mail());
                                }
                                $corpmail = "Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . ".";

                                if (strcasecmp($demande->type(), "cet") == 0 and strcasecmp($statut, demande::DEMANDE_VALIDE) == 0) // Si c'est une demande prise sur un CET et qu'elle est validée => On joint le PDF d'utilisation du CET en congés
                                {
                                    /*
                                    // On remplace les '\' par des '/' et on cherche la position du dernier '/'
                                    $position = strrpos(str_replace('\\', '/', $pdffilename[0]), '/');
                                    // La base du chemin PDF est donc la sous-chaine du nom du fichier PDF de la demande !!
                                    $basepdfpath = substr($pdffilename[0], 0, $position);
                                    // On ajoute le fichier PDF d'utilisation du CET en congés
                                    $pdffilename[1] = $basepdfpath . '/../../documents/Utilisation_CET_Conges.pdf';
                                    */

                                    // On ajoute le fichier PDF d'utilisation du CET en congés
                                    $pdffilename[1] = $fonctions->documentpath() . '/' . DOC_USAGE_CET;
                                    $corpmail = $corpmail . "\n\nVous devez retourner par mail le document " . basename($pdffilename[1]) . "  rempli et signé à :\n";
                                    $arrayagentrh = $fonctions->listeprofilrh(agent::PROFIL_RHCET); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                                    foreach ($arrayagentrh as $gestrh) {
                                        $corpmail = $corpmail . $gestrh->identitecomplete() . " : " . $gestrh->mail() . "\n";
                                    }
                                }

                                $user->sendmail($agent, "Modification d'une demande de congés ou d'absence", $corpmail, $pdffilename, $ics);
                                // Si c'est une demande prise sur un CET et qu'elle est validée => On envoie un mail au gestionnaire RH de CET
                                if (strcasecmp($demande->type(), "cet") == 0 and strcasecmp($statut, demande::DEMANDE_VALIDE) == 0)
                                {
                                    $arrayagentrh = $fonctions->listeprofilrh(agent::PROFIL_RHCET); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                                    foreach ($arrayagentrh as $gestrh) {
                                        $corpmail = "Une demande de congés a été " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . " sur le CET de " . $agent->identitecomplete() . ".\n";
                                        $corpmail = $corpmail . "\n";
                                        $corpmail = $corpmail . "Détail de la demande :\n";
                                        $corpmail = $corpmail . "- Date de début : " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . "\n";
                                        $corpmail = $corpmail . "- Date de fin : " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "\n";
                                        $corpmail = $corpmail . "Nombre de jours demandés : " . $demande->nbrejrsdemande() . "\n";
                                        // $corpmail = $corpmail . "La demande est actuellement en attente de validation.\n";
                                        $user->sendmail($gestrh, "Changement de statut d'une demande de congés sur CET", $corpmail);
                                    }
                                }
                                // Si c'est une demande de type télétravail HC raison médical  et qu'elle est validée => On envoie un mail au gestionnaire RH de CET
                                elseif (strcasecmp($demande->type(), "telesante") == 0 and strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0)
                                {
                                    $arrayagentrh = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // Profil = 2 ==> GESTIONNAIRE RH CONGE
                                    foreach ($arrayagentrh as $gestrh) {
                                        $corpmail = "Une demande d'absence de type 'Télétravail pour raison de santé' a été " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . " pour " . $agent->identitecomplete() . ".\n";
                                        $corpmail = $corpmail . "\n";
                                        $corpmail = $corpmail . "Détail de la demande :\n";
                                        $corpmail = $corpmail . "- Date de début : " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . "\n";
                                        $corpmail = $corpmail . "- Date de fin : " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "\n";
                                        $corpmail = $corpmail . "Nombre de jours demandés : " . $demande->nbrejrsdemande() . "\n";
                                        // $corpmail = $corpmail . "La demande est actuellement en attente de validation.\n";
                                        $user->sendmail($gestrh, "Changement de statut d'une demande de 'Télétravail pour raison de santé'", $corpmail);
                                    }
                                }

                                error_log("Sauvegarde la demande " . $demande->id() . " avec le statut " . $fonctions->demandestatutlibelle($demande->statut()));
                            }
                        }
                    }
                }
            }
        }
    }

    echo "Changez l'état de chacune des demandes en \"Validée\" ou \"Refusée\", puis enregistrez les modifications en cliquant sur le bouton \"Enregistrer\" <br>Laissez l'état des demandes à \"En attente\" si vous ne souhaitez pas faire de modification.<br><U>Attention :</U> La saisie du motif est obligatoire dans le cas d'un refus.<br><br>";

    if ($user->estresponsable() and (strcasecmp($mode, MODE_RESPONSABLE) == 0)) {
        $listestruct = $user->structrespliste();
        $listestruct = $fonctions->enleverstructuresinclues_demandes($listestruct);
        if (is_array($listestruct))
        {
            uasort($listestruct,"triparprofondeurabsolue");
        }
        // print_r($listestruct); echo "<br>";
        echo "<form name='frm_validation_conge'  method='post' >";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        foreach ($listestruct as $key => $structure) {
            $aumoinsunedemande = False;
            $cleelement = $structure->id();

            //if ($user->agentid() == '937') ////// PATCH MONIQUE LIER - Ticket GLPI 145258
            if (strcasecmp($structure->respaffdemandesousstruct(),'o')==0)  // Si on doit gérer les demandes de congés/afficher le solde des agents des structures inclues
            {
                if ($structure->isincluded() and $structure->parentstructure()->responsable()->agentid()==$user->agentid())
                {
                     continue;
                }
                $agentliste = $user->listeagentenresponsabilite(date("d/m/Y"), date("d/m/Y"),$structure);
            }
            else
            {
                // echo "validsousstruct = XXXXX" . $validsousstruct . "XXXXX <br>";
                
                $agentliste = $user->listeagentenresponsabilite(date("d/m/Y"), date("d/m/Y"),$structure);
            }

            echo "<center><p>Liste des demandes des agents de " . $structure->nomlong() . " (" . $structure->nomcourt() . ")</p></center>";
            echo "<form name='frm_validation_conge'  method='post' >";
            ////$validsousstruct = strtolower($structure->respvalidsousstruct());
            ////// echo "validsousstruct = XXXXX" . $validsousstruct . "XXXXX <br>";
            ////$agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"), $validsousstruct);
            if (is_array($agentliste)) {
                foreach ($agentliste as $membrekey => $membre) {
                    // echo "boucle => " .$membre->nom() . "<br>";
                    $debut = $fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode());
                    $fin = $fonctions->formatdate(($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode());

                    // Si on est dans l'année courante et si on ne limite pas les conges a la periode =>
                    // On doit afficher les congés qui sont dans la période suivante
                    if ((strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) and ($previous == 0))
                    {
                        $fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
                    }
                    // echo "Debut = $debut fin = $fin <br>";
                    // echo "structure->id() = " . $structure->id() . "<br>";
                    // echo "Membre = " . $membre->nom() . "<br>";

                    // echo $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->id(),null, $cleelement);
                    //$htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut, $fin, $user->agentid(), $structure->id(), $cleelement);
                    $htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut, $fin, $user->agentid(),$mode);
                    if ($htmltodisplay != "") {
                        echo $htmltodisplay;
                        echo "<br>";
                        $aumoinsunedemande = TRUE;
                    }
                }
            }            
            if (! $aumoinsunedemande) {
                echo "Aucune demande en attente pour cette structure...<br>";
            }
        }
        echo "<input type='hidden' name='mode' value='" . $mode . "' />";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
        echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
        echo "<br>";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        echo "</form>";
    } elseif (! $user->estresponsable() and (strcasecmp($mode, MODE_RESPONSABLE) == 0)) {
        echo "Vous n'êtes pas responsable, vous ne pouvez pas valider les demandes de congés/d'absence <br>";
    }

    if ($user->estgestionnaire() and (strcasecmp($mode, MODE_GESTION) == 0)) {
        echo "<form name='frm_validation_conge'  method='post' >";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        $listestruct = $user->structgestliste();
        // On récupère la liste des structures où l'agent (donc le gestionnaire) gère les congés (des agents et/ou du responsable)
        $listegeststruct = $user->structgestcongeliste();
        //foreach((array)$listegeststruct as $tmpstruct) { var_dump($tmpstruct->id() . ' ' . $tmpstruct->nomcourt()); }
        $listestruct = array_merge((array)$listestruct,(array)$listegeststruct);
        if (is_array($listestruct))
        {
            uasort($listestruct,"triparprofondeurabsolue");
        }
        foreach ($listestruct as $key => $structure)
        {
            echo "<center><p>Liste des agents de <b>" . $structure->nomlong() . " (" . $structure->nomcourt() . ")</b></p></center>";
            $aumoinsunedemande = FALSE;
            $cleelement = $structure->id();
            // Si le gestionnaire ne doit pas gérer les agents et qu'il n'est pas destinataire des notifications des demandes de congés
            if (strcasecmp($structure->gestvalidagent(),'n')==0
                    and array_key_exists($structure->id(),(array)$listegeststruct)===false)
            {
                // Il n'est pas autorisé à voir les agents de cette structure
                echo "Vous n'êtes pas autorisé(e) à modifier les demandes sur la structure <b>" . $structure->nomcourt() . "</b><br>";
            }
            else
            {
                $gestionnaire = $structure->gestionnaire();
                if (is_null($gestionnaire))
                {
                    // Si on n'a pas de gestionnaire => On charge l'utilisateur CRON comme gestionnaire
                    $gestionnaire = new agent($dbcon);
                    $gestionnaire->load(SPECIAL_USER_IDCRONUSER);
                }

                $agentliste = $gestionnaire->listeagentengestion(date("d/m/Y"), date("d/m/Y"), $structure);
                                
                foreach ((array)$agentliste as $membrekey => $membre) 
                {
                    // echo "boucle => " .$membre->nom() . "<br>";
                    $debut = $fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode());
                    // Si on est en mode "previous" alors on considère que la fin est l'année courante
                    if ($previous == 1)
                    {
                        $fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
                    }
                    // Si on ne limite pas les congés a la date de fin de la période, il faut prendre plus large que la fin de période
                    // On prend la fin de période + 1 an (soit 2 ans par rapport a l'année de référence)
                    elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0)
                    {
                        $fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
                    }
                    else
                    {
                        $fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
                    }
                    // echo "Debut = $debut fin = $fin <br>";
                    // echo "structure->id() = " . $structure->id() . "<br>";
                    // echo "Membre = " . $membre->nom() . "<br>";

                    // echo $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->agentid(),$structure->id(), $cleelement);
                    // -------------------------------------------------------------
                    // Dans le mode GESTIONNAIRE on ne passe pas le code du gestionnaire ($user->agentid()) car il doit pouvoir valider ses propres congés ??
                    // $htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut , $fin, $user->agentid(),$structure->id(), $cleelement);
                    // $htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut, $fin, null, $structure->id(), $cleelement);
                    $htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut, $fin, null,$mode);
                    // -------------------------------------------------------------
                    // echo "htmltodisplay = $htmltodisplay <br>";
                    if ($htmltodisplay != "") {
                        echo $htmltodisplay;
                        echo "<br>";
                        $aumoinsunedemande = true;
                    }
                }
                if (! $aumoinsunedemande) {
                    echo "Aucune demande en attente pour cette structure...<br>";
                }
            }
        }

        echo "<input type='hidden' name='mode' value='" . $mode . "' />";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
        echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
        echo "<br>";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        echo "</form>";
    } elseif (! $user->estgestionnaire() and (strcasecmp($mode, MODE_GESTION) == 0)) {
        echo "Vous n'êtes pas gestionnaire, vous ne pouvez pas valdier les demandes de congés/d'absence <br>";
    }
    
    //var_dump ("mode = $mode");
    if (strcasecmp($mode, MODE_CONSULTANT) == 0) {
        echo "<form name='frm_validation_conge'  method='post' >";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        echo "<center><p>Liste des agents avec une demande d'avis en attente</p></center>";

        // On récupère la liste des agents ou l'utilisateur est consultant
        $agentconsultliste = $user->agentconsultantliste();

        $debut = $fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode());
        $fin = $fonctions->formatdate(($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode());

        // Si on est dans l'année courante et si on ne limite pas les conges a la periode =>
        // On doit afficher les congés qui sont dans la période suivante
        if ((strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) and ($previous == 0))
        {
            $fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
        }
        
        foreach ($agentconsultliste as $membre)
        {
            //var_dump("Membre = " . $membre->identitecomplete());
            $htmltodisplay = $membre->demandeslistehtmlpourvalidation($debut, $fin, null,$mode);
            if ($htmltodisplay . "" != "")
            {
                echo "$htmltodisplay";
                echo "<br>";
            }
        }
        
        echo "<input type='hidden' name='mode' value='" . $mode . "' />";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
        echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
        echo "<br>";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        echo "</form>";

        
    }

?>
<br>
<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>