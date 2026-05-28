<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
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
    
        
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    $mode = null;
    if (isset($_POST["mode"]))
    {
       $mode = $_POST["mode"];
    }

    if (isset($_POST["agentid"]))
    {
       $agentid = $_POST["agentid"];
       if (! is_numeric($agentid)) {
           $agentid = $fonctions->useridfromCAS($agentid);
           if ($agentid === false)
           {
               $agentid = null;
           }
       }

       if (! is_numeric($agentid)) {
           $agentid = null;
           $agent = null;
       }
    }
    else
    {
       $agentid = null;
    }

    if (is_null($agentid) or $agentid == "")
    {
        $noagentset = TRUE;
    }
    else {
        // echo "AGENTID = " . $agentid . "<br>";
        $agent = new agent($dbcon);
        $agent->load($agentid);
        $noagentset = FALSE;
    }
    // echo "avant chargement respo <br>";
    $responsableid = null;
    $noresponsableset = TRUE;
    $responsable = new agent($dbcon);
    if (isset($_POST["responsableid"])) {
        //echo "responsableid = " . $responsableid . "<br>";
        $responsableid = $_POST["responsableid"];
        if (! is_null($responsableid) and $responsableid != "") {
            //echo "Je load le responsable...<br>";
            $responsable = new agent($dbcon);
            $responsable->load($responsableid);
            $noresponsableset = FALSE;
            $mode = MODE_RESPONSABLE;
        }
    }
    if (isset($_POST["gestionnaireid"])) {
        //echo "gestionnaireid = " . $gestionnaireid . "<br>";
        $responsableid = $_POST["gestionnaireid"];
        if (! is_null($responsableid) and $responsableid != "") {
            //echo "Je load le responsable...<br>";
            $responsable = new agent($dbcon);
            $responsable->load($responsableid);
            $noresponsableset = FALSE;
            $mode = MODE_GESTION;
        }
    }
    
    if (isset($_POST["previous"]))
    {
        $previoustxt = $_POST["previous"];
    }
    else
    {
        $previoustxt = null;
    }
    if ($fonctions->convertvaluetobool($previoustxt)) // (strcasecmp((string)$previoustxt, "yes") == 0)
    {
        $previous = 1;
    }
    else
    {
        $previous = 0;
    }

    $inputmotif = '';
    if (isset($_POST["inputmotif"]))
    {
        $inputmotif =$_POST["inputmotif"];
    }

    // echo "Avant le include <br>";
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml"><br>';

    // echo "POST = "; print_r($_POST); echo "<br>";
    // echo "FILE = "; print_r($_FILES); echo "<br>";

    $cancelbutton = array();
    if (isset($_POST["cancelbutton"]))
    {
        $cancelbutton = $_POST["cancelbutton"];
    }
    foreach ($cancelbutton as $demandeid => $value) 
    {
        //var_dump("On parcourt les demandes bouton : " . $demandeid);
        $demande = new demande($dbcon);
        $demande->load($demandeid);
        
        $demandeur = $demande->agent();
        $resp = $demandeur->getsignataire();
        if (is_null($resp) or ($resp===false))
        {
            $errlog = "Aucun responsable défini pour " . $demandeur->identitecomplete() . " : Impossible de demander l'annulation.";
            echo $fonctions->showmessage(fonctions::MSGERROR, "$errlog");
        }
        else
        {
            $datetorepostmail = $demande->datemailrelanceannulation();
            if ($datetorepostmail > date('Y-m-d'))
            {
                // On a déjà envoyé un message il y a moins de XX jours => On ne refait rien
                // Cela evite les renvois de mails suite au refresh de pages de navigateurs (touche F5)
            }
            else
            {
                $demandeur->sendmail($resp,"Demande d'annulation d'une demande", "Merci de bien vouloir annuler ma demande de congés ou d'absence établie le " . $fonctions->formatdate($demande->date_demande()) . " :<br>"
                . "<ul>"
                . "<li>Début : " . $fonctions->formatdate($demande->datedebut()) . " " . $fonctions->nommoment($demande->moment_debut()) . "</li>"
                . "<li>Fin : " . $fonctions->formatdate($demande->datefin()) . " " . $fonctions->nommoment($demande->moment_fin()) . "</li>"
                . "<li>Nombre de jours : " . $demande->nbrejrsdemande() . "</li>"
                . "<li>Type de demande : " . $demande->typelibelle() . "</li>"
                . "</ul><br>"
                . "Motif : " . htmlentities($inputmotif) . " <br>");
                $demande->datemailannulation(date('d/m/Y'));
                $demande->store();
                echo $fonctions->showmessage(fonctions::MSGINFO, "La demande d'annulation a été envoyée à " . $resp->identitecomplete());
            }
        }
    }


    $cancelarray = array();
    if (isset($_POST["cancel"]))
    {
        $cancelarray = $_POST["cancel"];
    }

    foreach ($cancelarray as $demandeid => $value) {
        // echo "demandeid = $demandeid value = $value <br>";
            $motif = "";
            if (isset($_POST["motif"][$demandeid]))
            {
                $motif = $_POST["motif"][$demandeid];
            }
            // echo "Motif = $motif";
            $demande = new demande($dbcon);
            // echo "cleelement = $cleelement demandeid = $demandeid <br>";
            $demande->load($demandeid);
            $demande->motifrefus($motif);
            if (strcasecmp((string)$demande->statut(), demande::DEMANDE_VALIDE) == 0 and $motif == "") {
                $errlog = "Le motif de l'annulation est obligatoire.";
                echo $fonctions->showmessage(fonctions::MSGERROR, "$errlog");
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } else {
                $demande->statut(demande::DEMANDE_ANNULE);
                $msgerreur = "";
                $msgerreur = $demande->store();
                if ($msgerreur != "") {
                    $errlog = "Pas de sauvegarde car " . $msgerreur;
                    echo $fonctions->showmessage(fonctions::MSGERROR, "$errlog");
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                } else {
                    unset($demande);
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    if (is_null($responsableid) == false) // Il y a un responsable ==> On envoie le mail
                    {
                        $pdffilename = $demande->pdf($user->agentid());
                        $agentdemande = $demande->agent();
                        $ics = null;
                        $ics = $demande->ics($agentdemande->mail());
                        $corpmail = "Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . ".<br><br>";
                        // $corpmail = $corpmail . "Pensez à supprimer manuellement l'évènement dans votre agenda.\n";
                        $user->sendmail($agentdemande, "Annulation d'une demande de congés ou d'absence", $corpmail, $pdffilename, $ics);
                    }
                    else
                    {
                        // On est dans le cas où c'est l'agent qui supprime sa propre demande
                        // On met à jour le calendar car la demande est annulée
                        $agentdemande = $demande->agent();
                        $ics = null;
                        $ics = $demande->ics($agentdemande->mail());
                        $agentdemande->updatecalendar($ics,true);
                        //echo "On vient de mettre le calendrier à jour....<br>";
                    }
                    if (strcasecmp((string)$demande->type(), "cet") == 0) // Si c'est une demande prise sur un CET => On envoie un mail au gestionnaire RH de CET
                    {
                        // Si on n'est pas en mode responsable envoi du mail au gestionnaire RH.... (Sinon c'est l'agent qui a annulé sa propre demande => donc pas d'envoi)
                        if (is_null($responsableid) == false) {
                            $arrayagentrh = $fonctions->listeprofilrh(agent::PROFIL_RHCET); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                            foreach ($arrayagentrh as $gestrh) {
                                $corpmail = "Une demande de congés a été " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . " sur le CET de " . $agent->identitecomplete() . ".<br>";
                                $corpmail = $corpmail . "<br>";
                                $corpmail = $corpmail . "Détail de la demande :<br>";
                                $corpmail = $corpmail . "- Date de début : " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . "<br>";
                                $corpmail = $corpmail . "- Date de fin : " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "<br>";
                                $corpmail = $corpmail . "Nombre de jours demandés : " . $demande->nbrejrsdemande() . "<br>";
                                // $corpmail = $corpmail . "La demande est actuellement en attente de validation.\n";
                                $user->sendmail($gestrh, "Changement de statut d'une demande de congés sur CET", $corpmail);
                            }
                        }
                    }

                    error_log($fonctions->stripAccents("Sauvegarde la demande " . $demande->id() . " avec le statut " . $fonctions->demandestatutlibelle($demande->statut())));
                    echo $fonctions->showmessage(fonctions::MSGINFO,"Votre demande a bien été annulée.");
                    
                }
            }
//        }
    }


    // Si on a un justificatif qui a été posté 
    if (isset($_FILES) and count($_FILES)>0)
    {
        // On parcourt toutes les données
        foreach ($_FILES as $key => $fileinfos)
        {
            $msg_erreur = '';
            // Si la clé contient 'justificatif_' => c'est donc un justificatif qui est uploadé
            if (stripos($key,'justificatif_')!==false)
            {
                // On a uploadé un fichier si le code est != 4 (code 4 => Pas de fichier uploadé)
                if ($_FILES[$key]['error'] != 4)
                {
                    $demandeid = explode('_',$key)[1];
                    // Le numéro de la demande est la partie droite après le '_'
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    if ($_FILES[$key]['error'] != 0)
                    {
                        $msg_erreur = $msg_erreur . $fonctions->getfileuploaderror($_FILES[$key]['error']) . '<br>';
                    }
                    elseif (is_uploaded_file($_FILES[$key]['tmp_name'])) 
                    {
                        $mime_type = mime_content_type($_FILES[$key]['tmp_name']);
                        if (! in_array($mime_type, ALLOWED_FILE_TYPES)) 
                        {
                            // Pas le bon type MINE
                            $msg_erreur = $msg_erreur . "Le format du fichier justificatif n'est pas supporté.";
                        }
                    }
                    if ($msg_erreur != '')
                    {
                        error_log($fonctions->stripAccents("Impossible de modifier le justificatif de la demande " . $demande->id() . " : " . $msg_erreur));
                        echo $fonctions->showmessage(fonctions::MSGINFO,"Impossible de modifier le justificatif de la demande " . $demande->id() . "<br>" . $msg_erreur);
                    }
                    else
                    {
                        $demande->justiftmpfilename($_FILES[$key]['tmp_name']);
                        $demande->store();

                        if ($demande->statut() == demande::DEMANDE_VALID_RH)
                        {
                            $drhuser = new agent($dbcon);
                            if ($drhuser->load(SPECIAL_USER_IDLISTERHUSER))
                            {
                                $mailbody = "Je vous informe que je viens d'ajouter/modifier le justificatif de ma demande du " . $fonctions->formatdate($demande->datedebut()) . ' au ' . $fonctions->formatdate($demande->datefin()) . ".\n";
                                $user->sendmail($drhuser, "Ajout/modification d'un justificatif", $mailbody);
                            }
                        }
                        error_log($fonctions->stripAccents("Le justificatif de la demande " . $demande->id() . " a été modifié."));
                        echo $fonctions->showmessage(fonctions::MSGINFO,"Le justificatif de la demande " . $demande->id() . " a été modifié.");
                    }
                }
            }
        }
    }
    


    $debut = $fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode());
    // Si on est dans le mode "previous" alors on dit que la date de fin est l'année courante
    if ($previous == 1)
    {
        $fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
    }
    elseif (strcasecmp((string)$fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0)
    {
        $fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
    }
    else
    {
        $fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
    }
    
    // echo "Debut = $debut fin = $fin <br>";
    // echo "structure->id() = " . $structure->id() . "<br>";
    //echo "noresponsableset = $noresponsableset <br> mode = $mode <br>";
    $displaysubmit = true;
    $selectagentbutton = false;
    echo "<form name='frm_gest_demande' id='frm_gest_demande' method='post' enctype='multipart/form-data'>";
    if ($noresponsableset and (is_null($mode) or $mode == '')) {
        // => C'est un agent qui veut gérer ses demandes
        //echo "Pas de responsable.... C'est un agent qui veut gérer ses demandes<br>";
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->agentid(), MODE_AGENT, null);
        if ($htmltext != "")
        {
            echo $htmltext;
            // Les annulations en mode agent sont gérés par des boutons donc pas besoin de "submit"
            $displaysubmit = false;
        }
        else
        {
            echo "<p class='centeraligntext'>L'agent " . $agent->identitecomplete(true) . " n'a aucun congé à annuler pour la période de référence en cours.</p><br>";
            $displaysubmit = false;
        }
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    } 
    elseif ($noagentset) 
    {
        if ($mode == MODE_RESPONSABLE or $mode == MODE_GESTION)
        {
            // => On est en mode "responsable" mais aucun agent n'est sélectionné
            // echo "Avant le chargement structure responsable <br>";
            if ($mode == MODE_RESPONSABLE)
            {
                $agentlistefull = $responsable->listeagentenresponsabilite(date("d/m/Y"), date("d/m/Y"));
            }
            else // $mode == gest
            {
                // Attention : En mode GEST, le responsable est le gestionnaire (!! Pas top !!)
                $agentlistefull = $responsable->listeagentengestion(date("d/m/Y"), date("d/m/Y"));
            }
            
            // Il faut trier les agents par structure puis par ordre alphabétique
            $tmpagentlistefull = array();
            foreach ($agentlistefull as $keyagent => $membre)
            {
                // Astuce : On ajoute la longueur du nom pour que le plus petit soit en premier
                $tmpagentlistefull[strlen($membre->structureid()) . '#' . $membre->structureid()][$keyagent] = $membre;
            }
            // On trie les structures par ordre naturel
            ksort($tmpagentlistefull,SORT_NATURAL);
            $agentlistefull = array();
            foreach ($tmpagentlistefull as $tmpstruct)
            {
                // On trie les agents par ordre naturel
                ksort($tmpstruct,SORT_STRING);
                $agentlistefull = array_merge($agentlistefull,$tmpstruct);
            }
            
            //ksort($agentlistefull);
            //echo "<br>"; print_r($agentlistefull); echo "<br>";
            if (count($agentlistefull)==0)
            {
                echo "Vous n'avez aucun agent en gestion ou vous n'êtes pas autorisé(e) à modifier des demandes de congés.<br>";
                $selectagentbutton = false;
                $displaysubmit = false;
            }
            else
            {
                $tmpstruct = null;
                echo "<SELECT class='listeagentg2t' size='1' id='agentid' name='agentid' style='width: 350px;'>";
                foreach ($agentlistefull as $keyagent => $membre) 
                {
                    if (!$membre->estutilisateurspecial())
                    {
    //                    echo "<OPTION value='" . $membre->agentid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
                        if (is_null($tmpstruct))
                        {
                            echo "<OPTION value=''>--- Sélectionnez un agent ---</OPTION>";
                            $tmpstruct = new structure($dbcon);
                            $tmpstruct->load($membre->structureid());
                            echo "<optgroup label='" . $tmpstruct->nomcourt() . "'>";
                        }
                        elseif ($tmpstruct->id()!=$membre->structureid())
                        {
                            $tmpstruct = new structure($dbcon);
                            $tmpstruct->load($membre->structureid());
                            echo "</optgroup>";
                            echo "<optgroup label='" . $tmpstruct->nomcourt() . "'>";
                        }
                        echo "<OPTION value='" . $membre->agentid() . "'>" . $membre->identitecomplete(true) . "</OPTION>";
                        $selectagentbutton = true;
                        $displaysubmit = false;
                    }
                }
                if (!is_null($tmpstruct))
                {
                    echo "</optgroup>";
                }
                echo "</SELECT>";
            }
            echo "<br>";
        }
        else // $mode = MODE_RH
        {
            echo "Personne à rechercher : <br>";
            echo "<form name='selectagentcet'  method='post' >";

            $agentsliste = $fonctions->listeagentsg2t(true,false);
            echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid' style='width: 350px;'>";
            echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
            foreach ($agentsliste as $key => $identite)
            {
                echo "<option value='$key'>$identite</option>";
                $selectagentbutton = true;
                $displaysubmit = false;
            }
            echo "</select>";
            echo "<br>";
        }
    } elseif ($mode == MODE_RESPONSABLE or $mode == MODE_GESTION) {
        // => On est en mode "reponsable" et un agent est sélectionné
        //echo "Avant le mode responsable <br>";
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->agentid(), MODE_RESPONSABLE, null);
        if ($htmltext != "")
        {
            echo $htmltext;
        }
        else
        {
            echo "<p class='centeraligntext'>L'agent " . $agent->identitecomplete(true) . " n'a aucun congé à annuler pour la période de référence en cours.</p><br>";
            $displaysubmit = false;
        }
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    }
    else
    {
        // On est en mode rh et un agent est sélectionné
        // On élargie de période de début de recherche des demandes de CET pour l'agent à -2 ans.
        //echo "Mode RH <br>";
        $debut = $fonctions->formatdate(($fonctions->anneeref() - 2) . $fonctions->debutperiode());
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->agentid(), MODE_RESPONSABLE, 'cet');
        if ($htmltext != "")
        {
            echo $htmltext;
        }
        else
        {
            echo "<p class='centeraligntext'>L'agent " . $agent->identitecomplete(true) . " n'a aucune demande de congés sur CET à annuler pour la période de référence en cours.</p><br>";
            $displaysubmit = false;
        }
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";

    }

    if ($responsableid != "")
    {
        if ($mode == MODE_RESPONSABLE)
        {
            echo "<input type='hidden' name='responsableid' value='" . $responsableid . "'>";
        }
        elseif ($mode == MODE_GESTION)
        {
            echo "<input type='hidden' name='gestionnaireid' value='" . $responsableid . "'>";
        }
    }
    echo "<input type='hidden' name='userid' value='" . $userid . "'>";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
    echo "<input type='hidden' name='mode' value='" . $mode . "'>";
    if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
    if ($selectagentbutton)
    {
        echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' />";
    }
    else if ($displaysubmit)
    {
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
    }
    echo "</form>";

?>
    <script>

        // On utilise la fonction par défaut sur le cancel boutton => pas besoin de la redéclarer
        // divmodalcancelBtn.onclick = function() 
        // {
        //     masquerimgmodal();
        //     divmodal.style.display = "none";
        //     return false;
        // }

        motiftextarea.addEventListener("input", (event) => {
            if (motiftextarea.value.trim() == '')
            {
                divmodalconfirmBtn.disabled = true;
            }
            else
            {
                divmodalconfirmBtn.disabled = false;

            }
         })

        divmodalconfirmBtn.onclick = function()
        {
            // Si on demande le motif 
            // if (labelmodalheader.innerHTML == 'Motif')
            if (divmotif.hidden == false)
            {
                if (motiftextarea.value.trim() == '')
                {
                    alert("La saisie d'un motif est obligatoire.");
                    return;
                }
                let elementid = divmodalconfirmBtn.getAttribute('active-elementid');
                click_element(elementid,true);
            }
            // else if (labelmodalheader.innerHTML == 'Confirmation')
            else
            {
                // ATTENTION : 
                // La gestion est différente puisqu'on doit simuler l'appui sur le bouton et non le submit du formulaire
                // ------------------------------------------------------------------------------------------------------

                divmodal.style.display = "none";
                var activeelementid = divmodalconfirmBtn.getAttribute('active-elementid');
                //console.log(activeelementid);
                var submit_button = document.getElementById(activeelementid);

                // On récupère le motif directement depuis le textarea car il n'a pas été réinitialisé
                let motif = motiftextarea.value;
                // On vérifie que l'input n'est pas dans la page
                let inputmotif = document.getElementById('inputmotif');
                if (!inputmotif)
                {
                    // On ajoute dans le document un input caché contenant ce motif
                    inputmotif = document.createElement("input");
                    inputmotif.type = 'hidden';
                    inputmotif.name = 'inputmotif';
                    inputmotif.id = inputmotif.name;
                    submit_button.closest('form').appendChild(inputmotif);
                }
                // console.log(inputmotif);
                inputmotif.value = motif;

                submit_button.tagname = 'OK';
                submit_button.click();
            }
        }

        var click_element = function(elementid, tosubmit)
        {
            if (tosubmit)
            {
                masquerimgmodal('question');

                var submit_button = document.getElementById(elementid);

                if (submit_button.classList.contains("cancelbutton"))
                {
                    divmodallabeltext.innerHTML = 'Confirmez vous l\'envoi de la requête d\'annulation pour cette demande auprès du responsable ?';
                }
                else if (submit_button.classList.contains("cancel"))
                {
                    divmodallabeltext.innerHTML = 'Confirmez vous l\'annulation de cette demande ? ';
                }

                divstructid.hidden = true;
                divagentid.hidden = true;
                divselecttype.hidden = true;
                divmotif.hidden = true;
                labelmodalheader.innerHTML = 'Confirmation';
                divmodallabeltext.parentElement.classList.add('centeraligntext');
                divmodalcancelBtn.textContent = "Non";
                divmodalcancelBtn.classList.add("g2tannulerbouton");
                divmodalcancelBtn.setAttribute('active-elementid',elementid);
                divmodalcancelBtn.hidden = false;
                divmodalconfirmBtn.textContent = "Oui";
                divmodalconfirmBtn.classList.add("g2tvalidebouton");
                divmodalconfirmBtn.setAttribute('active-elementid',elementid);
                divmodalconfirmBtn.hidden = false;
                divmodalconfirmBtn.disabled = false;
                divmodal.style.display = "block";
            }
            else
            {
                masquerimgmodal('question');

                var submit_button = document.getElementById(elementid);

                if (submit_button.classList.contains("cancelbutton"))
                {
                    divmodallabeltext.innerHTML = 'Saisissez le motif de la demande d\'annulation';
                }
                else if (submit_button.classList.contains("cancel"))
                {
                    divmodallabeltext.innerHTML = 'Saisissez le motif de la demande d\'annulation';
                }

                divstructid.hidden = true;
                divagentid.hidden = true;
                divselecttype.hidden = true;
                divmotif.hidden = false;
                labelmodalheader.innerHTML = 'Motif';
                divmodallabeltext.parentElement.classList.add('centeraligntext');
                divmodalcancelBtn.textContent = "Annuler";
                divmodalcancelBtn.classList.add("g2tannulerbouton");
                divmodalcancelBtn.setAttribute('active-elementid',elementid);
                divmodalcancelBtn.hidden = false;
                divmodalconfirmBtn.textContent = "Ok";
                divmodalconfirmBtn.classList.add("g2tvalidebouton");
                divmodalconfirmBtn.setAttribute('active-elementid',elementid);
                divmodalconfirmBtn.hidden = false;
                divmodalconfirmBtn.disabled = true;
                motiftextarea.value = '';
                divmodal.style.display = "block";
            }
        };

    </script>
<?php
    
    
?>

<br>
<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

