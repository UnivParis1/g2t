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
    
    if (is_null($userid) or ($userid == "")) 
    {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["agentid"])) 
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }

        if (! is_numeric($agentid)) 
        {
            $agentid = null;
            $agent = null;
        } 
        else 
        {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } 
    else 
    {
        $agentid = null;
        $agent = null;
    }

    $longueurmaxmotif = $fonctions->logueurmaxcolonne('COMPLEMENT','VALEUR');

    $congessuppaverifier = null;
    if (isset($_POST["statut"]))
    {
        $congessuppaverifier = $_POST["statut"];
    }

    $motif = null;
    if (isset($_POST["motif"]))
    {
        $motif = $_POST["motif"];
    }


    $selectall = 'no';
    if (isset($_POST["selectall"]))
    {
        $selectall = $_POST["selectall"];
    }

    $cronuser = new agent($dbcon);
    $cronuser->load(SPECIAL_USER_IDCRONUSER);

    require ("includes/menu.php");
    echo "<br>";
    //echo "<br>"; print_r($_POST); echo "<br><br>";

    if (!is_null($congessuppaverifier))
    {
        foreach($congessuppaverifier as $commentaireid => $valeur)
        {
            $msg_erreur = "";
            // Si le statut du commentaire est "en attente" => On ne doit rien faire dessus
            if ($valeur == demande::DEMANDE_ATTENTE)
            {
                //var_dump("commentaireid = " . $commentaireid . " => valeur = $valeur");
                // Si le statut du commentaire est DEMANDE_ATTENTE => On ne fait rien
                continue;
            }
            elseif ($valeur == demande::DEMANDE_REFUSE)
            {
                //on charge le commentaire congés avec l'id
                $commentaire = $fonctions->lirecommentaire($commentaireid);
                if (!is_null($commentaire))
                {
                    $currentagent = new agent($dbcon);
                    $currentagent->load($commentaire->agentid);
                    // Si pas de motif saisi, on ne peut pas enregistrer le REFUS
                    if (!isset($motif[$commentaireid]) or trim($motif[$commentaireid]==""))
                    {
                        $msg_erreur = "La saisie d'un motif du refus est obligatoire pour la demande de récupération de " . $currentagent->identitecomplete(); //. "<br>Motif : " . $commentaire->commentaire;
                    }
                    else
                    {
                        $idcomplement = complement::REFUSRH_CONGES_SUP_LABEL . $commentaire->commentaireid;
                        $complement = new complement($dbcon);
                        $complement->load($commentaire->agentid,$idcomplement);
                        // Si le complément REFUSRH_CONGES_SUP_LABEL existe déjà, on ne traite pas le commentaire
                        if ($complement->agentid()==$commentaire->agentid)
                        {
                            $msg_erreur = $msg_erreur . "Impossible de refuser l'ajout de " . $commentaire->nbjoursajoute . " jour(s) sur le solde " . $commentaire->libelleabsence . " pour l'agent " . $currentagent->identitecomplete() . " : Il semble déjà supprimée.";
                        }
                        else
                        {
                            // On va supprimer le complément AVISRH_CONGES_SUP_LABEL => Il n'y a plus d'avis à demander à la RH
                            $idcomplement = complement::AVISRH_CONGES_SUP_LABEL . $commentaire->commentaireid;
                            $complement = new complement($dbcon);
                            $msg_erreur = $msg_erreur . $complement->delete($commentaire->agentid, $idcomplement);
                            // On va créer le nouveau complément REFUSRH_CONGES_SUP_LABEL => Il contiendra le motif du refus
                            $complement = new complement($dbcon);
                            $complement->valeur(trim($motif[$commentaireid]));
                            $complement->agentid($commentaire->agentid);
                            $idcomplement = complement::REFUSRH_CONGES_SUP_LABEL . $commentaire->commentaireid;
                            $complement->complementid($idcomplement);
                            $msg_erreur = $msg_erreur . $complement->store();
                            // Si pas d'erreur, on va envoyer le mail au responsable et à l'agent
                            if ($msg_erreur=='')
                            {
                                if ($commentaire->typeabsenceid == recuperation::RECUP_ID)
                                {
                                    $recup = new recuperation($dbcon);
                                    $recup->load($commentaire->agentid,date('d/m/Y'));
                                    $solde = $recup->getsolde();
                                }
                                else
                                {
                                    $solde = new solde($dbcon);
                                    $errorload = $solde->load($commentaire->agentid, $commentaire->typeabsenceid);
                                    if ($errorload . "" != "")
                                    {
                                        $solde->droitaquis(0);
                                        $solde->droitpris(0);
                                        $solde->typeabsenceid($commentaire->typeabsenceid);
                                    }
                                }
        
                                $corpmail = $user->identitecomplete() . " vient de refuser l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération.<br>";
                                $corpmail = $corpmail . "La raison de ce refus est : <br>" . trim($motif[$commentaireid]) . ".<br><br>";
                                $corpmail = $corpmail . "Suite à ce refus, votre solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                                $cronuser->sendmail($currentagent, "Refus d'ajout de jours de récupération", $corpmail);
        
                                // Envoi du mail au demandeur
                                $currentsignataire = new agent($dbcon);
                                $erreur = $currentsignataire->load($commentaire->auteurid);
                                if ($erreur !== false)
                                {
                                    $corpmail = $user->identitecomplete() . " vient de refuser l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération à " . $currentagent->identitecomplete() . ".<br>";
                                    $corpmail = $corpmail . "La raison de ce refus est : <br>" . trim($motif[$commentaireid]) . ".<br><br>";
                                    $corpmail = $corpmail . "Suite à ce refus, le solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                                    $cronuser->sendmail($currentsignataire, "Refus d'ajout de jours de récupération", $corpmail);
                                }
        
                                // Envoi du mail au signataire de l'agent => Si ce n'est pas le demandeur
                                $currentsignataire = $currentagent->getsignataire();
                                // Si currentsignataire n'est pas à false => On a un signataire
                                // On vérifie que le signataire n'est pas l'auteur pour éviter l'envoie en double du mail
                                if ($currentsignataire !== false and $currentsignataire->agentid()!=$commentaire->auteurid)
                                {
                                    $corpmail = $user->identitecomplete() . " vient de refuser l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération à " . $currentagent->identitecomplete() . ".<br>";
                                    $corpmail = $corpmail . "La raison de ce refus est : <br>" . trim($motif[$commentaireid]) . ".<br><br>";
                                    $corpmail = $corpmail . "Suite à ce refus, le solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                                    $cronuser->sendmail($currentsignataire, "Refus d'ajout de jours de récupération", $corpmail);
                                }
                            }
                        }
                    }
                }
            }
            // Si le statut du commentaire est "validée" => On doit valider le commentaire et alimenter le solde
            elseif ($valeur == demande::DEMANDE_VALIDE)
            {
                //on charge le commentaire congés avec l'id
                $commentaire = $fonctions->lirecommentaire($commentaireid);
                if (!is_null($commentaire))
                {
                    $currentagent = new agent($dbcon);
                    $currentagent->load($commentaire->agentid);

                    $idcomplement = complement::AVISRH_CONGES_SUP_LABEL . $commentaire->commentaireid;
                    $complement = new complement($dbcon);
                    $complement->load($commentaire->agentid,$idcomplement);
                    // Si le complément n'existe pas/plus => On ne doit pas traiter le commentaire
                    if ($complement->agentid()==$commentaire->agentid)
                    {
                        if ($commentaire->typeabsenceid != recuperation::RECUP_ID)
                        {
                            $solde = new solde($dbcon);
                            $msg_erreur = $msg_erreur . $solde->load($commentaire->agentid, $commentaire->typeabsenceid);
                            if ($msg_erreur != "") 
                            {
                                unset($solde);
                                $solde = new solde($dbcon);
                                $msg_erreur = $msg_erreur . $solde->creersolde($commentaire->typeabsenceid, $commentaire->agentid);
                                $msg_erreur = $msg_erreur . $solde->load($commentaire->agentid, $commentaire->typeabsenceid);
                            }
                            if ($msg_erreur == "")
                            {
                                $nouv_solde = ($solde->droitaquis() + $commentaire->nbjoursajoute);
                                $solde->droitaquis($nouv_solde);
                                $msg_erreur = $msg_erreur . $solde->store();
                            }
                        }
                        if ($msg_erreur == "")
                        {
                            $complement = new complement($dbcon);
                            $msg_erreur = $complement->delete($commentaire->agentid, $idcomplement);
                        }
                        // Si tout s'est bien passé, on modifie la date d'ajout du commentaire
                        if ($msg_erreur == "")
                        {
                            $commentaire->dateajout = date('d/m/Y');
                            $msg_erreur = $currentagent->modifiercommentaireconge($commentaire);
                        }
                    }
                    else
                    {
                        $msg_erreur = $msg_erreur . "Impossible de valider l'ajout de " . $commentaire->nbjoursajoute . " jour(s) sur le solde " . $commentaire->libelleabsence . " pour l'agent " . $currentagent->identitecomplete() . ". Elle semble déjà validée.";
                    }
                    if ($msg_erreur == "")
                    {
                        if ($commentaire->typeabsenceid == recuperation::RECUP_ID)
                        {
                            $recup = new recuperation($dbcon);
                            $recup->load($commentaire->agentid,date('d/m/Y'));
                            $solde = $recup->getsolde();
                        }
                        else
                        {
                            $solde = new solde($dbcon);
                            $solde->load($commentaire->agentid, $commentaire->typeabsenceid);
                        }
                        // Envoi du mail à l'agent
                        $commentaire = $fonctions->lirecommentaire($commentaireid);
                        $dbconstante = 'VALIDRECUP';
                        // Durée de validité des récupérations
                        $validrecup = '2';
                        if ($fonctions->testexistdbconstante($dbconstante)) { $validrecup = $fonctions->liredbconstante($dbconstante); }

                        //$findatevalidite = date('d/m/Y',strtotime('+' . $validrecup . ' month',strtotime($fonctions->formatdatedb($commentaire->dateajout))));
                        $findatevalidite = $fonctions->finvaliditerecuperation($commentaire->dateajout,$commentaire->typeabsenceid);
                        $findatevalidite = $fonctions->formatdate($findatevalidite);

                        $corpmail = $user->identitecomplete() . " vient de valider l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération.<br>";
                        $corpmail = $corpmail . "Pour rappel, le motif de cet ajout est : <br>" . $commentaire->commentaire . ".<br><br>";
                        $corpmail = $corpmail . "<b>IMPORTANT</b> : La fin de validité de cette récupération est le $findatevalidite.<br><br>";
                        $corpmail = $corpmail . "Suite à cette validation, votre solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                        $cronuser->sendmail($currentagent, "Validation d'ajout de jours de récupération", $corpmail);

                        // Envoi du mail au demandeur
                        $currentsignataire = new agent($dbcon);
                        $erreur = $currentsignataire->load($commentaire->auteurid);
                        if ($erreur !== false)
                        {
                            $corpmail = $user->identitecomplete() . " vient de valider l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération à " . $currentagent->identitecomplete() . "..<br>";
                            $corpmail = $corpmail . "Pour rappel, le motif de cet ajout est : <br>" . $commentaire->commentaire . ".<br><br>";
                            $corpmail = $corpmail . "<b>IMPORTANT</b> : La fin de validité de cette récupération est le $findatevalidite.<br><br>";
                            $corpmail = $corpmail . "Suite à cette validation, le solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                            $cronuser->sendmail($currentsignataire, "Validation d'ajout de jours de récupération", $corpmail);
                        }

                        // Envoi du mail au signataire de l'agent => Si ce n'est pas le demandeur
                        $currentsignataire = $currentagent->getsignataire();
                        // Si currentsignataire n'est pas à false => On a un signataire
                        // On vérifie que le signataire n'est pas l'auteur pour éviter l'envoie en double du mail
                        if ($currentsignataire !== false and $currentsignataire->agentid()!=$commentaire->auteurid)
                        {
                            $corpmail = $user->identitecomplete() . " vient de valider l'ajout de " . $commentaire->nbjoursajoute . " jour(s) de récupération à " . $currentagent->identitecomplete() . "..<br>";
                            $corpmail = $corpmail . "Pour rappel, le motif de cet ajout est : <br>" . $commentaire->commentaire . ".<br><br>";
                            $corpmail = $corpmail . "Suite à cette validation, le solde de jours de récupération est de " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).<br>";
                            $cronuser->sendmail($currentsignataire, "Validation d'ajout de jours de récupération", $corpmail);
                        }
                    }
                }
            }
            if ($msg_erreur!="")
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, $msg_erreur);
                $msg_erreur = "";
            }
        }
    }


    //echo "Validation des jours complémentaires par la DRH<br><br>";
    echo "Validation des jours de récupération par la DRH<br><br>";

    echo "Personne à rechercher : <br>";
    echo "<form name='selectagentcet'  method='post' >";

    $agentsliste = $fonctions->listeagentsg2t(true,false);
    echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
    echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
    foreach ($agentsliste as $key => $identite)
    {
        echo "<option value='$key'>$identite</option>";
    }
    echo "</select>";
    
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "<input type='hidden' name='selectall' value='no'>";
    echo "</form>";
    echo "<form name='selectall'  method='post' >";
    echo "<br>";
    echo "<br>";
    if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton g2tboutonwidthauto' value='Tout afficher' >";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' name='selectall' value='yes'>";
    echo "</form>";
    echo "<br>";
    echo "<br>";

    if (!is_null($agent) or $selectall=='yes') 
    {
        $datedebut = "01/01/2017";

        if (!is_null($agent))
        {
            $listecongessupp = $agent->congessuppaverifier($datedebut);
        }
        else
        {
            $listecongessupp = $fonctions->congessuppaverifier($datedebut);
        }
        // Afficher la liste des congessupp à valider => Les objets commentaireconges
        $htmltext = '';
        $previousagentid = "";

        foreach ($listecongessupp as $commentaire) 
        {
            if ($previousagentid != $commentaire->agentid)
            {
                $currentagent = new agent($dbcon);
                $currentagent->load($commentaire->agentid);
                $previousagentid = $commentaire->agentid;
                if ($htmltext != '')
                {
                    $htmltext = $htmltext . "</table>";
                    $htmltext = $htmltext . "<br>";
                }
                $htmltext = $htmltext . "<table class='tableausimple'>";
                $htmltext = $htmltext . "<thead>";
                //$htmltext = $htmltext . "<tr class='titresimple'><th colspan=6 align=center>Demande de congés supplémentaires à traiter pour " . $currentagent->identitecomplete() . " (id : $commentaire->agentid) </th></tr>";
                $htmltext = $htmltext . "<tr class='titresimple'><th colspan=7 align=center>Demande de récupération à traiter pour " . $currentagent->identitecomplete() . " (id : $commentaire->agentid) </th></tr>";
                $htmltext = $htmltext . "<tr class='entete' align=center>
                                            <th class='cellulesimple'>Demandeur</th>
                                            <th class='cellulesimple'>Date ajout</th>
                                            <th class='cellulesimple'>Libellé</th>
                                            <th class='cellulesimple'>Nbre jours</th>
                                            <th class='cellulesimple'>Motif</th>
                                            <th class='cellulesimple'>Statut</th>
                                            <th class='cellulesimple'>Motif (obligatoire si la demande est refusée) - maximum $longueurmaxmotif caractères</th>
                                         </tr>";
                $htmltext = $htmltext . "</thead>";
            }
            $demandeur = new agent($dbcon);
            $demandeur->load($commentaire->auteurid);
            $htmltext = $htmltext . "<tr class='element bulleinfo' align=center>";
            $htmltext = $htmltext . "<td class='cellulesimple'>" . $demandeur->identitecomplete() . "</td>";
            $htmltext = $htmltext . "<td class='cellulesimple'>" . $fonctions->formatdate($commentaire->dateajout). "</td>";
            $htmltext = $htmltext . "<td class='cellulesimple'>" . $commentaire->libelleabsence . "</td>";
            $htmltext = $htmltext . "<td class='cellulesimple'>" . $commentaire->nbjoursajoute . "</td>";

            $datatitle = '';
            if (strlen($commentaire->commentaire) != 0) 
            {
                $datatitle = " data-title=" . chr(34) . htmlentities($fonctions->ajoute_crlf($commentaire->commentaire,60)) . chr(34);  
            }
            $htmltext = $htmltext . "<td class='cellulesimple ' $datatitle >";  //cellulemultiligne
            $htmltext = $htmltext . htmlentities($fonctions->tronque_chaine($commentaire->commentaire,50));
            $htmltext = $htmltext . "</td>";   


//                $htmltext = $htmltext . "<td class='cellulemultiligne'>" . $commentaire->commentaire . "</td>";
            //$htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=congessuppaverifier[" . $commentaire->commentaireid . "] value='yes' /></td>";
            $htmltext = $htmltext . "<td class='cellulesimple'>";
            //$htmltext = $htmltext . "   <select name='congessuppaverifier[" . $commentaire->commentaireid . "]' id='congessuppaverifier[" . $commentaire->commentaireid . "]' />";
            $htmltext = $htmltext . "   <select name='statut[" . $commentaire->commentaireid . "]' id='statut[" . $commentaire->commentaireid . "]' onchange='demandestatutchange(this," . $commentaire->commentaireid . ");'>";
            $htmltext = $htmltext . "       <option value='" . demande::DEMANDE_ATTENTE . "'";
            if (isset($congessuppaverifier[$commentaire->commentaireid]) and $congessuppaverifier[$commentaire->commentaireid]==demande::DEMANDE_ATTENTE)
            {
                $htmltext = $htmltext . " selected ";
            }
            $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(demande::DEMANDE_ATTENTE) . "</option>";
            $htmltext = $htmltext . "       <option value='" . demande::DEMANDE_VALIDE . "'"; 
            if (isset($congessuppaverifier[$commentaire->commentaireid]) and $congessuppaverifier[$commentaire->commentaireid]==demande::DEMANDE_VALIDE)
            {
                $htmltext = $htmltext . " selected ";
            }
            $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(demande::DEMANDE_VALIDE) . "</option>";
            $htmltext = $htmltext . "       <option value='" . demande::DEMANDE_REFUSE . "'";
            if (isset($congessuppaverifier[$commentaire->commentaireid]) and $congessuppaverifier[$commentaire->commentaireid]==demande::DEMANDE_REFUSE)
            {
                $htmltext = $htmltext . " selected ";
            }
            $htmltext = $htmltext . ">" . $fonctions->demandestatutlibelle(demande::DEMANDE_REFUSE) . "</option>";
            $htmltext = $htmltext . "   </select>";
            $htmltext = $htmltext . "</td>";

            $textareastyle = " class='commenttextarea";
            $disabletext = " disabled ";
            if (isset($congessuppaverifier[$commentaire->commentaireid]) and $congessuppaverifier[$commentaire->commentaireid] == demande::DEMANDE_REFUSE ) //and $motif[$commentaire->commentaireid]=="")
            {
                //var_dump("Je passe là");
                $textareastyle = $textareastyle . " commentobligatoirebackground";
                $disabletext = "";
            }
            $textareastyle = $textareastyle . "'";

            //$htmltext = $htmltext . "<td class='cellulesimple'>";
            //$htmltext = $htmltext . "</td>";
            $htmltext = $htmltext . "   <td class='cellulesimple'>"
                                  . "      <textarea name='motif[" . $commentaire->commentaireid . "]' id='motif[" . $commentaire->commentaireid . "]' rows='2' cols='80' $textareastyle oninput='checktextlength(this,$longueurmaxmotif); validdemandemotif(this," . $commentaire->commentaireid . ");' $disabletext>";
            if (isset($motif[$commentaire->commentaireid]))
            {
                $htmltext = $htmltext . $motif[$commentaire->commentaireid]; ///$commentaire->motifrefus;
            }
            $htmltext = $htmltext . trim(" </textarea>")
                                  . "   </td>";
            $htmltext = $htmltext . "</tr>";

        }
        if ($htmltext != '')
        {
            $htmltext = $htmltext . "</table>";
            $htmltext = $htmltext . "<br>";
        }

        // Si on valide => Il faut supprimer le complément ($complement->delete($agentid, ID_COMPLEMENT)) qui est attaché 
        // Il faut impacter le solde + envoyer le mail (=> voir ajouter congés)
        echo "<form name='frm_validcongessupp'  method='post' >";
        //echo "Validation d'un ajout de jours complémentaires :<br><br>";
        echo "Validation d'un ajout de jours de récupération :<br><br>";
        echo $htmltext;
        echo "<br><br>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        if (!is_null($agent))
        {
            echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
        }
        echo "<input type='hidden' name='selectall' value='" . $selectall . "'>";
        if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
        if (strlen(trim($htmltext))>0)
        {
            echo "<input type='submit' name='button_valid' class='g2tbouton g2tvalidebouton' value='Enregistrer' >";
        }
        echo "</form>";
        echo "<br>";

    }
?>

</body>
</html>

