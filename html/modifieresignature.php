<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
    // Initialisation de l'utilisateur
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

    // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
    $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
    if ($CASuserId===false)
    {
        // Ce n'est pas un administrateur
        // On regarde si l'agent a un profil RH
        $userid = $fonctions->useridfromCAS($uid);
        $user = new agent($dbcon);
        $user->load($userid);
        
        if (!$user->estprofilrh())
        {
            error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
            echo "<script>alert('Accès réservé aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
            exit();
        }
    }
    else
    {
        $user = new agent($dbcon);
        $user->load($userid);
    }
    
    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        if (! is_numeric($agentid)) {
            $agentid = null;
            $agent = null;
        } else {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } else {
        $agentid = null;
        $agent = null;
    }

    $esignatureradioid = null;
    if (isset($_POST["esignatureradioid"]))
    {
        $esignatureradioid = $_POST["esignatureradioid"];
    }

    $listeaction = array();
    $agentreplace = array();
    $replace = array();
    if (isset($_POST["listeaction"]))
    {
        $listeaction = $_POST["listeaction"];
    }
    if (isset($_POST["agentreplace"]))
    {
        $agentreplace = $_POST["agentreplace"];
    }
    if (isset($_POST["replace"]))
    {
        $replace = $_POST["replace"];
    }

    $stepnumber = '';
    $agentsearch = '';
    $agentmail = '';
    if (isset($_POST["stepnumber"]))
    {
        $stepnumber = $_POST["stepnumber"];
    }
    if (isset($_POST["agentsearch"]))
    {
        $agentsearch = $_POST["agentsearch"];
    }
    if (isset($_POST["agentmail"]))
    {
        $agentmail = $_POST["agentmail"];
    }

    require ("includes/menu.php");
    global $WSGROUPURL;

    // var_dump($_POST);

?>
    <script>

        function clearfield(event, ui)
        {
            var activeelement = document.activeElement;
            var closestform = activeelement.closest("form");
            var agentmail = document.getElementById("agentmail"); // closestform.getElementById("agentmail");

            if (agentmail)
            {
                agentmail.value = '';
            }
            return false;
        }

        function selectedaddagent(event, ui)
        {
            var form = $(this).closest("form");
            var selectedInput = document.activeElement;
            if (ui)
            {
                form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
                form.find("[class='" + selectedInput.name + "']").val (ui.item.value);
            }
        }


        function checkaction(intervenantid, esignatureid)
        {
            var activeelement = document.activeElement;
            //console.debug(intervenantid);
            //console.debug(esignatureid);
            // Si les deux ne sont pas vides, on est dans le remplacement d'un agent
            if (String(intervenantid) != '' && String(esignatureid) != '')
            {
                //console.debug("Je suis dans le cas 1");
                var currentselect = document.getElementById('listeaction[' + intervenantid + ']');
                var closestform = activeelement.closest("form");
                var divlist = closestform.getElementsByTagName("div");
                for (let index = 0; index < divlist.length; index++) 
                {
                    var currentdiv = divlist[index];
                    currentdiv.hidden = true;
                }
                var selectinform = closestform.getElementsByTagName('select');
                for (let index = 0; index < selectinform.length; index++) 
                {
                    console.debug(selectinform[index].name +  "   " + currentselect.name);
                    if (selectinform[index].name != currentselect.name)
                    {
                        selectinform[index].selectedIndex = 0;
                    }
                }

                if (currentselect)
                {
                    var value = currentselect.value;
                    if (value == 'remove')
                    {
                        console.debug('On va supprimer l\'intervenant : ' + intervenantid);
                        click_element(intervenantid);

                    }
                    else if (value == 'replace')
                    {
                        console.debug('On va remplacer l\'intervenant : ' + intervenantid);
                        var infosdiv = document.getElementById('div_' + intervenantid);
                        infosdiv.hidden = false;
                    }
                    else
                    {
                        console.debug('Oups, je me suis perdu !');
                    }
                }
            }
            // On vérifie qu'on a appuyé sur le bouton addrecipientbutton
            else if (activeelement.id == 'addrecipientbutton')
            {
                var stepnumber = document.getElementById('stepnumber');
                var agentmail = document.getElementById('agentmail');
                if (stepnumber && agentmail)
                {
                    if (agentmail.value != '' && stepnumber.value != '')
                    {
                        click_element(activeelement.id);
                    }
                    else
                    {
                        masquerimgmodal('error');
                        console.debug("Il manque au moins une donnée");

                        divstructid.hidden = true;
                        divagentid.hidden = true;
                        divselecttype.hidden = true;
                        labelmodalheader.innerHTML = 'Données manquantes';
                        divmodalcancelBtn.textContent = "Ok";
                        divmodalcancelBtn.hidden = false;
                        divmodalcancelBtn.classList.add('g2tokbouton');
                        divmodalcancelBtn.focus();
                        divmodalconfirmBtn.hidden = true;
                        divmodallabeltext.parentElement.classList.add('centeraligntext');
                        divmodallabeltext.innerHTML = 'Vous devez choisir une étape et un agent.';
                        divmodal.style.display = "block";
                    }
                }
            }
        }

        divmodalcancelBtn.onclick = function() 
        {
            if (divmodalconfirmBtn.hidden)
            {
                masquerimgmodal();
                divmodal.style.display = "none";
                return false;
            }
            else
            {
                var activeelementid = divmodalcancelBtn.getAttribute('active-elementid');
                var activeelement = document.getElementById(activeelementid)
                //console.debug(activeelement.value);
                // On remet le choix à "non défini" pour l'élément courant
                activeelement.selectedIndex = 0;
                divmodal.style.display = "none";
                return false;
            }
        }

        divmodalconfirmBtn.onclick = function()
        {
            divmodal.style.display = "none";
            var activeelementid = divmodalconfirmBtn.getAttribute('active-elementid');
            var activeelement = document.getElementById(activeelementid)
            //console.debug(activeelement.name);
            //console.debug(activeelement.value);
            var closestform = activeelement.closest("form");
            //console.debug(closestform.name)
            closestform.submit();
        }

        var click_element = function(elementid)
        {
            masquerimgmodal('question');
            var activeelement = document.activeElement;

            // activeelement => 
            //  . c'est la dropdown list si on supprime un intervenant
            //  . c'est le bouton de validation de la substitution si on remplace un agent dans une étape d'un circuit
            //  . c'est le bouton de validation de l'ajout si on ajoute un agent dans une étape d'un circuit
            if (activeelement.value == 'remove')
            {
                var currenttr = activeelement.closest("tr");
                var nomagent = currenttr.getElementsByClassName('identiteagent')[0].innerHTML;
                var numetape = currenttr.getElementsByClassName('numetape')[0].getAttribute('data-tip');

                divmodallabeltext.innerHTML = 'Confirmez vous la suppression de l\'intervenant ' +  nomagent + ' dans l\'étape ' + numetape + ' ? ';
            }
            else if (activeelement.id == 'replacerecipientbutton')
            {
                var currenttr = activeelement.closest("tr");
                var nomagent = currenttr.getElementsByClassName('identiteagent')[0].innerHTML;
                var numetape = currenttr.getElementsByClassName('numetape')[0].getAttribute('data-tip');
                var replaceagent = currenttr.getElementsByClassName('replaceagent')[0].value;

                divmodallabeltext.innerHTML = 'Confirmez vous le remplacement de ' + nomagent + ' par ' + replaceagent + ' dans l\'étape ' + numetape + ' ? ';
            }
            else if (activeelement.id == 'addrecipientbutton')
            {
                var currentform = activeelement.closest("form");
                var numetape = currentform.getElementsByClassName('stepnumber')[0].value;
                var nomagent = currentform.getElementsByClassName('addagent')[0].value;
                divmodallabeltext.innerHTML = 'Confirmez vous l\'ajout de ' + nomagent + ' dans l\'étape ' + numetape + ' ? ';
            }
            else
            {
                exit();
            }
            divstructid.hidden = true;
            divagentid.hidden = true;
            divselecttype.hidden = true;
            labelmodalheader.innerHTML = 'Confirmation';
            divmodallabeltext.parentElement.classList.add('centeraligntext');
            divmodalcancelBtn.textContent = "Non";
            divmodalcancelBtn.classList.add("g2tannulerbouton");
            divmodalcancelBtn.setAttribute('active-elementid',activeelement.id);
            divmodalcancelBtn.hidden = false;
            divmodalconfirmBtn.textContent = "Oui";
            divmodalconfirmBtn.classList.add("g2tvalidebouton");
            divmodalconfirmBtn.setAttribute('active-elementid',activeelement.id);
            divmodalconfirmBtn.hidden = false;
            divmodal.style.display = "block";
        };
    </script>
<?php
    if ($agentmail != '' and $stepnumber != '')
    {
        $esignature = new esignature($dbcon);
        $recipientstab = $esignature->get_signrequest_recipients($esignatureradioid);
        if (is_string($recipientstab))
        {
            echo $fonctions->showmessage(fonctions::MSGERROR,$recipientstab);
        }
        else
        {
            $params_string = array();
            $emailstring = '';
            //var_dump($recipientstab);
            // Le stepindex commence à partir de 1 alors que l'index des recipientstab commence à 0
            foreach($recipientstab[($stepnumber-1)] as $recipient)
            {
                if (strlen($emailstring)>0) $emailstring = $emailstring . ',';
                $emailstring = $emailstring . '{"email" : "' . $recipient->mail . '"}';
            }
            if (strlen($emailstring)>0) $emailstring = $emailstring . ',';
            $emailstring = $emailstring . '{"email" : "' . strtolower($agentmail) . '"}';

            $params_string = array();
            $params_string['recipientWsDtosString'] = '[' . $emailstring . ']';
            $params_string['stepNumber'] = $stepnumber;
            //var_dump($params_string, $stepindex);
            //$error = 'TEST TEST';
            $error = $esignature->modify_recipient($esignatureradioid,$params_string);
            if ($error != '')
            {
                echo $fonctions->showmessage(fonctions::MSGERROR,$error);
            }
        }
    }

    if (count($listeaction)==count($agentreplace) and count($listeaction)==count($replace) and count($listeaction)>0)
    {
        $esignature = new esignature($dbcon);
        $recipientstab = $esignature->get_signrequest_recipients($esignatureradioid);
        if (is_string($recipientstab))
        {
            echo $fonctions->showmessage(fonctions::MSGERROR,$recipientstab);
        }
        else
        {
            foreach($listeaction as $key => $action)
            {
                $emailstring ='';
                $stepindex = '';
                if ($action=='remove' or $action=='replace')
                {
                    $actioninfos = explode('_',$key);
                    $stepindex = $actioninfos[0];
                    $agentmail = $actioninfos[1];
                    //var_dump($stepindex, $agentmail);
                    $params_string = array();
                    //var_dump($recipientstab);
                    // Le stepindex commence à partir de 1 alors que l'index des recipientstab commence à 0
                    foreach($recipientstab[($stepindex-1)] as $recipient)
                    {
                        if (strtolower($recipient->mail) != strtolower($agentmail))
                        {
                            if (strlen($emailstring)>0) $emailstring = $emailstring . ',';
                            $emailstring = $emailstring . '{"email" : "' . $recipient->mail . '"}';
                        }
                    }
                }
                // On a supprimé l'agent donc dans le cas d'un remplacement, on ajoute l'agent saisi par l'utilisateur
                if ($action=='replace')
                {
                    if (strlen($emailstring)>0) $emailstring = $emailstring . ',';
                    $emailstring = $emailstring . '{"email" : "' . strtolower($replace[$key]) . '"}';
                }
                if ($emailstring!="")
                {
                    $params_string = array();
                    $params_string['recipientWsDtosString'] = '[' . $emailstring . ']';
                    $params_string['stepNumber'] = $stepindex;
                    //var_dump($params_string, $stepindex);
                    //$error = 'TEST TEST';
                    $error = $esignature->modify_recipient($esignatureradioid,$params_string);
                    if ($error != '')
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR,$error);
                    }
                }
            }
        }
    }

    echo "<form name='selectagentesignature'  method='post' >";
    
    $agentsliste = $fonctions->listeagentsg2t(true,false);
    echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
    echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
    foreach ($agentsliste as $key => $identite)
    {
        if ($key == $agentid) $selected = ' selected '; else $selected = '';
        echo "<option value='$key' $selected>$identite</option>";
    }
    echo "</select>";
    
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    if (isset($_POST['pagepath'])) echo "<input type='hidden' name='pagepath' value='" . htmlspecialchars($_POST['pagepath']) . "'>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";
    echo "<br>";

    if ($agentid != "") // Si agent est selectionné
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);

        $teletravailidtab = $agent->teletravailliste(date("d/m/") . (date("Y")-1), date("d/m/") . (date("Y")+1));
        $alimentationidtab = $agent->getDemandesAlim('',array(alimentationCET::STATUT_EN_COURS));
        $optionidtab = $agent->getDemandesOption('',array(optionCET::STATUT_EN_COURS));

        foreach ($teletravailidtab as $index => $teletravailid)
        {
            $teletravail = new teletravail($dbcon);
            $teletravail->load($teletravailid);
            if ($teletravail->statut()==teletravail::TELETRAVAIL_ATTENTE and $teletravail->esignatureid()!='')
            {
                //var_dump($teletravail);
                $teletravailidtab[$index] = $teletravail;
            }
            else
            {
                unset($teletravailidtab[$index]);
            }
        }

        foreach ($alimentationidtab as $alimentationid => $esignatureid)
        {
            $alimentation = new alimentationCET($dbcon);
            $alimentation->load(null, $alimentationid);
            //var_dump($teletravail);
            $alimentationidtab[$alimentationid] = $alimentation;
        }

        foreach ($optionidtab as $optionid => $esignatureid)
        {
            $option = new optionCET($dbcon);
            $option->load(null, $optionid);
            //var_dump($option);
            $optionidtab[$optionid] = $option;
        }


        echo "<form name='form_esignature' id='form_esignature' method='post' >";
        echo "<table class='tableausimple' id='listeesignature'><thead>";
        echo "<tr>
            <th class='titresimple'>Type</th>
            <th class='titresimple'>Date de la demande</th>
            <th class='titresimple'>Statut</th>
            <th class='titresimple'>URL eSignature</th>
            <th class='titresimple'>Modifier</th>";
        echo "</tr>";
        echo "</thead><tbody>";
        $textformulaireaffichepdf = '';
        foreach (array_merge((array)$teletravailidtab, (array)$alimentationidtab, (array)$optionidtab) as $element)
        {
            $typeelement = '';
            $datedemande = '';
            $esignatureurl = '';
            if ($element instanceof alimentationCET)
            {
                $typeelement = 'Alimentation CET';
                $datedemande = $element->datecreation();
                $statut = $element->statut();
                $esignatureurl = $element->esignatureurl();
                $esignatureid = $element->esignatureid();
            }
            elseif ($element instanceof optionCET)
            {
                $typeelement = 'Option CET';
                $datedemande = $element->datecreation();
                $statut = $element->statut();
                $esignatureurl = $element->esignatureurl();
                $esignatureid = $element->esignatureid();
            }
            elseif  ($element instanceof teletravail)
            {
                $typeelement = 'Télétravail';
                $datedemande = $element->creationg2t();
                $statut = $fonctions->teletravailstatutlibelle($element->statut()); // $element->statut();
                $esignatureurl = $element->esignatureurl();
                $esignatureid = $element->esignatureid();
            }
           
            echo "<tr><td class='cellulesimple'>$typeelement</td>";
            echo "    <td class='cellulesimple centeraligntext'>" . $fonctions->formatdate($datedemande) . "</td>";
            echo "    <td class='cellulesimple'>$statut</td>";

            echo "    <td class='cellulesimple'>";
            // Attention : On est déjà dans un formulaire => On ne peut pas imbriquer les formulaires les uns dans les autres
            // => On construit le texte des formulaires et on l'affichera après avoir fermé le formulaire en cours
            $textformulaireaffichepdf = $textformulaireaffichepdf . 
                '<form name="showesignaturePDF_' . $esignatureid . '" method="post" action="affiche_pdf.php" target="_blank">' .
                '<input type="hidden" name="esignatureid" value="' . $esignatureid . '">' .
                '<input type="hidden" name="esignaturePDF" value="ok">' .
                '</form>';
            echo "<a href='affiche_pdf.php' target='_blank' onClick='document.forms[\"showesignaturePDF_" . $esignatureid . "\"].submit(); return false;'>". $esignatureurl ."</a></td>";

            if ($esignatureradioid == $esignatureid) $checked = 'checked'; else $checked = '';
            echo "    <td class='cellulesimple centeraligntext'><input type='radio' id='$esignatureid' name='esignatureradioid' value='$esignatureid' onchange='this.form.submit()' $checked/></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' id='agentid' name='agentid' value='$agentid' >";
        echo "</form>";
        // On vient de fermer le formulaire => On peut afficher les formulaires précédemment construits
        echo $textformulaireaffichepdf;
        echo "<br>";
        echo "<br>";

        if ($esignatureradioid . "" != '')
        {
            $esignature = new esignature($dbcon);
            $currentstep = $esignature->get_signrequest_currentstep($esignatureradioid);
            // La numérotation de currentstep commence à 0.
            //var_dump("L'étape courante est : $currentstep");

            $recipientstab = $esignature->get_signrequest_recipients($esignatureradioid);
            if (is_string($recipientstab))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR,$recipientstab);
            }
            $stepstatustab = $esignature->get_signrequest_stepstatus($esignatureradioid);
            if (is_string($stepstatustab))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR,$stepstatustab);
            }
            if (is_array($recipientstab) and is_array($stepstatustab))
            {
                // var_dump($recipientstab);
                // var_dump($stepstatustab);

                echo "<form name='form_recipients' id='form_recipients' method='post' >";
                echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
                echo "<input type='hidden' id='agentid' name='agentid' value='$agentid' >";
                echo "<input type='hidden' id='esignatureradioid' name='esignatureradioid' value='$esignatureradioid' >";
                echo "<table class='tableausimple' id='listerecipients'><thead>";
                echo "<tr><th scope='col' class='titresimple'>Etape</th>
                          <th scope='col' class='titresimple'>Identité intervenant</th>
                          <th scope='col' class='titresimple'>Action intervenant</th>
                          <th scope='col' class='titresimple'>Date intervention</th>
                          <th scope='col' class='titresimple'>Modification</th>";
                echo "</tr>";
                echo "</thead><tbody>";
                foreach($stepstatustab as $stepindex => $stepstatus)
                {
                    // ATTENTION : stepindex commence à 0 -> nbre d'étape -1
                    foreach($recipientstab[$stepindex] as $recipientindex => $recipient)
                    {
                        // Si l'étape $stepindex est inférieure à $currentstep alors on ne peut pas modifier les signataires
                        // car l'étape est déjà finie ! On positionne donc $recipient->hassigned à TRUE pour empécher la modification
                        if ($stepindex < $currentstep)
                        {
                            $recipient->hassigned = true;
                        }

                        echo "<tr class='bulleinfo'>";
                        echo "<th scope='row' class='cellulesimple numetape' data-tip=" . ($stepindex+1) . ">Etape " . ($stepindex+1) . "</th>";
                        echo "<td class='cellulesimple identiteagent'>" . $recipient->prenom . " " . $recipient->nom . "</td>";
                        echo "<td class='cellulesimple centeraligntext'>" . $recipient->action . "</td>";
                        echo "<td class='cellulesimple centeraligntext'>" . $recipient->actiondate . "</td>";
                        echo "<td class='cellulesimple'>";
                        if ($recipient->hassigned == false)
                        {
                            $idintervenant = ($stepindex+1) . "_" . $recipient->mail;
                            echo "<select class='' size='1' id='listeaction[" . $idintervenant . "]' name='listeaction[" . $idintervenant . "]' onChange='checkaction(\"$idintervenant\", \"$esignatureradioid\");'>";
                            echo "<option value=''>----- Veuillez sélectionner une action -----</option>";
                            $disabled = '';
                            if (count($recipientstab[$stepindex])==1)  // Si on a qu'un intervenant dans l'étape, on ne peut pas le supprimer
                            {
                                $disabled = ' disabled ';
                            }
                            echo "<option value='remove' $disabled>Supprimer l'intervenant " . $recipient->prenom . " " . $recipient->nom . "</option>";
                            echo "<option value='replace'>Remplacer l'intervenant " . $recipient->prenom . " " . $recipient->nom . "</option>";
                            echo "</select>";

                            echo "<div id='div_$idintervenant' hidden>";
                            echo "<input class='replaceagent' id='agentreplace[" . $idintervenant . "]' name='agentreplace[" . $idintervenant . "]' placeholder='Nom et/ou prenom' value='' size=40 />";
                            echo "<input type='hidden' id='replace[" . $idintervenant . "]' name='replace[" . $idintervenant . "]' value='' class='agentreplace[" . $idintervenant . "]' /> ";
?>
                            <script>
                                $('[id="<?php echo "agentreplace[" . $idintervenant . "]" ?>"]').autocompleteUser(
                                    '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "mail",
                          	                          wsParams: { filter_eduPersonAffiliation: "employee|staff" } });
                            </script>
<?php
                            echo "<input type='button' id='replacerecipientbutton' name='replacerecipientbutton' class='g2tbouton g2tvalidebouton' value='Enregistrer' onclick='click_element(\"$idintervenant\");'>";
                            echo "</div>";
                        }
                        else
                        {
                            echo "Aucune action possible";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                echo "</tbody></table>";
                echo "</form>";
                echo "<br><br>";

                echo "<form name='form_addrecipients' id='form_addrecipients' method='post' >";
                echo "Ajouter un intervenant dans une étape :<br>";
                echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
                echo "<input type='hidden' id='agentid' name='agentid' value='$agentid' >";
                echo "<input type='hidden' id='esignatureradioid' name='esignatureradioid' value='$esignatureradioid' >";
                echo "<select class='stepnumber' size='1' id='stepnumber' name='stepnumber'>";   // onChange='checkaction(\"\", \"\");'
                echo "<option value=''>----- Veuillez sélectionner une étape -----</option>";
                for ($index = 1; $index <= count($stepstatustab); $index++)
                {
                    echo "<option value='$index' >Etape $index</option>";
                }
                echo "</select>";
                echo "&nbsp;";
                echo "<input class='addagent' id='agentsearch' name='agentsearch' placeholder='Nom et/ou prenom' value='' size=40 onkeydown='clearfield();' />"; // onchange='checkaction(\"\", \"\");'
                echo "<input type='hidden' id='agentmail' name='agentmail' value='' class='agentsearch' /> ";
?>
                <script>
                    $('[id="agentsearch"]').autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { minLength : 4, disableEnterKey: true, noFetch : clearfield, select: selectedaddagent, wantedAttr: "mail",
                                            wsParams: { filter_eduPersonAffiliation: "employee|staff" } });
                </script>
<?php
                //echo "&nbsp;";
                echo "<input type='button' id='addrecipientbutton' name='addrecipientbutton' class='g2tbouton g2tvalidebouton' value='Enregistrer' onclick='checkaction(\"\", \"\");' >"; //  //onclick='click_element(\"addrecipientbutton\");'
                echo "</form>";
            }
        }
    }

?>

<br>
</body>
</html>


