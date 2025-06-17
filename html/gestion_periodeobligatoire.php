<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");
    ini_set('max_execution_time', 600); // 600 seconds = 10 minutes

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
            //        header('Location: index.php');
            exit();
        }
    }
    else
    {
        $user = new agent($dbcon);
        $user->load($userid);
    }

    require ("includes/menu.php");

    addwaitingimgdiv();

    // On force l'affichage de l'image d'attente en vidant le cache PHP vers le navigateur
    if (ob_get_contents()!==false)
    {
        //error_log(basename(__FILE__) . " : " . ob_get_contents());
        ob_end_flush();
        @ob_flush();
        flush();
        ob_start();
    }
    // Fin du forçage de l'affichage de l'image d'attente

    //echo "<br>". print_r($_POST, true) . "<br><br>";

    $structureid = null;
    if (isset($_POST["structureid"]))
    {
        $structureid = $_POST["structureid"];
    }

    $periodeid = null;
    if (isset($_POST["periodeid"]))
    {
        $periodeid = $_POST["periodeid"];
    }
    
    $structureliste = $fonctions->listestructurenoninclue();
    echo "<form name='mainform'  method='post' >";
    echo "<select size='1' id='structureid' name='structureid'>";
    echo "<option value=''>---- Afficher toutes les structures ----</option>";
    foreach((array)$structureliste as $currentstructid)
    {
        $structure = new structure($dbcon);
        $structure->load($currentstructid);

        $selected = '';
        if ($currentstructid == $structureid)
        {
            $selected = ' selected ';
        }

        echo "<option value='$currentstructid' $selected>" . $structure->nomlong() . " (" . $structure->nomcourt()  . ")</option>";
    }
    echo "</select>";


    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br>";
    $periodeobligatoire = new periodeobligatoire($dbcon);
    $periodeliste = $periodeobligatoire->load($fonctions->anneeref());

    //echo "<form id='dummyform' name='dummyform'>";
    echo "<br>Filtrer par période : <br>";
    echo "<select size='1' id='periodeid' name='periodeid' onchange='periodefilter();'>";
    echo "<option value='all'>--- Toutes les périodes ---</option>";

    $fullperiodeclass = '';
    foreach ((array)$periodeliste as $periode)
    {
        $selected = '';
        if ($periodeid == $periode["id"])
        {
            $selected = ' selected ';
        }
        echo "<option value='" . $periode["id"] . "' $selected >Période du " .  $fonctions->formatdate($periode["datedebut"]) . " au " . $fonctions->formatdate($periode["datefin"]) . "</option>";
        $fullperiodeclass = $fullperiodeclass . " " . $periode["id"] . " ";
    }
    echo "</select>";
    echo "<br>";
    echo " <input type='submit' name= 'Valid_struct' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    //echo "</form>";
    echo "<br>";
    echo "</form>";

    echo "<label class='redtext' id='errorlabel' name='errorlabel'></label>";
    echo "<br>";

    $resultliste = array();
    if (!is_null($structureid))
    {

        $structliste = array();
        if (trim($structureid) != '')
        {
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structliste[$structureid] = $structure; 
        }
        else
        {
            foreach((array)$structureliste as $currentstructid)
            {
                $structure = new structure($dbcon);
                $structure->load($currentstructid);
                $structliste[$currentstructid] = $structure;
            }
        }

        foreach ($structliste as $structure)
        {
            foreach ((array)$periodeliste as $periode)
            {
                $agentliste = $structure->agentlist($periode["datedebut"], $periode["datefin"], 'o');
                foreach((array)$agentliste as $agent)
                {
                    $coderetour = $agent->forceperiodeobligatoire($periode,true,$description);
                    $resultliste[$structure->id()][$agent->nom(). " " . $agent->prenom()][$periode["id"]]["agent"] = $agent;
                    $resultliste[$structure->id()][$agent->nom(). " " . $agent->prenom()][$periode["id"]]["periode"] = $periode;
                    $resultliste[$structure->id()][$agent->nom(). " " . $agent->prenom()][$periode["id"]]["description"] = $description;
                    $resultliste[$structure->id()][$agent->nom(). " " . $agent->prenom()][$periode["id"]]["coderetour"] = $coderetour;
                }
            }
            if (isset($resultliste[$structure->id()]))
            {
                ksort($resultliste[$structure->id()]);
            }
        }

        // Si j'ai au moins une structure
        if (count($resultliste)>0)
        {
            echo "Envoyer un mail de rappel à tous les agents affichés : ";
            echo "<input type='button' class='g2tbouton g2tenvoibouton globalmail' id='rappel_global' name='rappel_global' value='Rappel' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; click_element(this.id); return false;'/>";
            echo "<br>";
            echo "Poser les congés sur la/les période(s) affichée(s) pour tous les agents affichés : ";
            echo "<input type='button' class='g2tbouton g2tvalidebouton globalsave' id='save_global' name='save_global' value='Forcer' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; click_element(this.id); return false;'/>";
            echo "<br><br>";
        }

        foreach ($resultliste as $structid => $resultstruct)
        {
            $structure = new structure($dbcon);
            $structure->load($structid);

            echo "<table class='tableausimple tabsynthese' id='$structid' name='$structid'>";
            echo "<thead>";
            echo "   <tr class='titresimple'>"; 
            echo "      <th colspan=7>Congés sur les périodes obligatoires pour la structure <label>" . $structure->nomlong() . "</label> (" . $structure->nomcourt() . ") ";
            echo "         <input type='button' class='g2tbouton g2tenvoibouton structmail' structureid='$structid' id='rappel_struct_$structid' name='rappel_struct_$structid' value='Rappel' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; click_element(this.id); return false;'/>";
            echo "         <input type='button' class='g2tbouton g2tvalidebouton structsave' structureid='$structid' id='force_struct_$structid' name='$structid' id='force_struct_$structid' value='Forcer' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; click_element(this.id); return false;'/>";
            echo "      </th>";
            echo "   </tr>";
            echo "   <tr>"
                        . "<th class='cellulesimple' >Identité de l'agent</th>"
                        . "<th class='cellulesimple' >Début de la période</th>"
                        . "<th class='cellulesimple' >Fin de la période</th>"
                        . "<th class='cellulesimple' >Exception</th>"
                        . "<th class='cellulesimple' >Situation</th>"
                        . "<th class='cellulesimple' >Mail de rappel</th>"
                        . "<th class='cellulesimple' >Forcer les congés</th>"
                  . "</tr>";
            echo "</thead>";
            echo "<tbody>\n";
            foreach ($resultstruct as $resultagent)
            {
                $newagent = true;
                foreach ($resultagent as $resultperiode)
                {
                    $agent = $resultperiode["agent"];
                    $periode = $resultperiode["periode"];
                    $description = $resultperiode["description"];
                    $coderetour = $resultperiode["coderetour"];

                    echo "<tr class='" . $periode["id"] . "'>";
                    $newagentclass = '';
                    if ($newagent)
                    {
                        $newagentclass = ' newagent ';
                        $newagent = false;
                    }
                    echo "   <td agentid='" . $agent->agentid() . "' maximal-rowspan='" . count($resultagent) . "' rowspan='" . 1 . "' class='cellulesimple agentname $newagentclass '>" . $agent->identitecomplete(true) . " (Agentid : " . $agent->agentid() . ")</td>";
                    $extraclass = '';
                    $statutinfo = "";
                    if ($coderetour==agent::CHECK_PERIODE_COUVERTE)
                    {
                        $extraclass = ' okbackgroundtext ';
                        $statutinfo = "OK";
                    }
                    elseif ($coderetour==agent::CHECK_PERIODE_NONCOUVERTE)
                    {
                        $extraclass = ' kobackgroundtext ';
                        $statutinfo = "KO";
                    }
                    elseif ($coderetour==agent::CHECK_PERIODE_EXCEPTION)
                    {
                        $extraclass = ' warnbackgroundtext ';
                        $statutinfo = "OK";
                    }
                    else
                    {
                        $extraclass = ' redtext ';
                        $statutinfo = "Erreur lors de l'étude des congés : " . $description . "";
                    }
                    echo "   <td class='cellulesimple datedebut $extraclass ' datedbformat='" . $fonctions->formatdatedb($periode["datedebut"]) . "' >" . $fonctions->formatdate($periode["datedebut"]) . "</td>";
                    echo "   <td class='cellulesimple datefin $extraclass ' datedbformat='" . $fonctions->formatdatedb($periode["datefin"]) . "' >" . $fonctions->formatdate($periode["datefin"]) . "</td>";
                    $complement = new complement($dbcon);
                    $complement->load($agent->agentid(), "EXCEPT_PER_" . $periode["id"]);
                    $idexception = $agent->agentid() . "|" . $periode["id"];
                    echo "   <td class='cellulesimple $extraclass '>";
                    echo "      <form>";
                    echo "      <select id='exception[$idexception]' name='exception[$idexception]' onchange='modifieperiode(this, \"" . $agent->agentid() . "\", \"" . $periode["id"] . "\");'>";
                    echo "          <option value='o' ";
                    if ($complement->agentid() == $agent->agentid())
                    {
                        echo ' selected ';
                    }
                    echo "          >Oui</option>";
                    echo "          <option value='n' ";
                    if ($complement->agentid() != $agent->agentid())
                    {
                        echo ' selected ';
                    }
                    echo "          >Non</option>";
                    echo "      </select>";
                    echo "      </form>";
                    echo "   </td>";
                    echo "   <td class='cellulesimple statutinfo $extraclass'>" . $statutinfo . "</td>";
                    echo "   <td class='cellulesimple $extraclass'>";
                    echo "      <input type='button' class='g2tbouton g2tenvoibouton' id='rappel_" . $agent->agentid() . "_" . $periode["id"] . "' name='rappel_" . $agent->agentid() . "_" . $periode["id"] . "' value='Rappel' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; sendmail_agent(this);'/>";
                    echo "   </td>";
                    echo "   <td class='cellulesimple $extraclass'>";
                    echo "      <input type='button' class='g2tbouton g2tvalidebouton' id='force_" . $agent->agentid() . "_" . $periode["id"] . "' name='force_" . $agent->agentid() . "_" . $periode["id"] . "' value='Forcer' onclick='document.getElementById(\"errorlabel\").innerText = \"\"; poserconges_agent(this);'/>";
                    echo "   </td>";
                    echo "</tr>\n";
                }
            }
            echo "</tbody>";
            echo "</table>";
            echo "<br>";
        }
    }
    else
    {
        echo "</form>";
    }

?>
    <script>
        // On appelle la fonction pour actualiser (filtrer) le tableau
        periodefilter();

        var nbrecongesaposer = 0;
        var nbmailaenvoyer = 0;

        // On utilise la fonction par défaut sur le cancel boutton => pas besoin de la redéclarer
        // divmodalcancelBtn.onclick = function() 
        // {
        //     masquerimgmodal();
        //     divmodal.style.display = "none";
        //     return false;
        // }

        divmodalconfirmBtn.onclick = function()
        {
            divmodal.style.display = "none";
            var activeelementid = divmodalconfirmBtn.getAttribute('active-elementid');
            var activeelement = document.getElementById(activeelementid)
            if (activeelement.classList.contains('structmail'))
            {
                var structid = activeelement.getAttribute("structureid");
                //console.log('Envoi du mail à tous les agents de la structure ' + structid);
                sendmail_struct(structid);
            }
            else if (activeelement.classList.contains('globalmail'))
            {
                //console.log('Envoi du mail à tous les agents');
                sendmail_global();
            }
            else if (activeelement.classList.contains('structsave'))
            {
                var structid = activeelement.getAttribute("structureid");
                //console.log('On pose les congés de tous les agents de la structure ' + structid);
                poserconges_struct(structid);
            }
            else if (activeelement.classList.contains('globalsave'))
            {
                //console.log('On pose les congés de tous les agents');
                poserconges_global();
            }
            else
            {
                alert('Le bouton n\'est pas reconnu');
            }
        }

        var click_element = function(elementid)
        {
            masquerimgmodal('question');

            var submit_button = document.getElementById(elementid);

            if (submit_button.classList.contains("cancel"))
            {
                divmodallabeltext.innerHTML = 'Confirmez vous l\'envoi des rappels à toutes les structures ? ';
            }
            else
            {
                if (submit_button.classList.contains('g2tenvoibouton'))
                {
                    var selectperiode = document.getElementById('periodeid');
                    var periodevalue = selectperiode.options[selectperiode.selectedIndex].text;
                    if (selectperiode.selectedIndex > 0)
                    {
                        periodevalue = 'la ' + periodevalue;
                    }
                    periodevalue = periodevalue.replaceAll("-","").toLowerCase().trim();
                    var structtext = '';
                    if (submit_button.classList.contains('structmail'))
                    {
                        var selectstruct = submit_button.closest("td,th").getElementsByTagName("label")[0];
                        var structname = selectstruct.innerText;
                        structtext = 'la structure "' + structname + '"';
                    }
                    else if (submit_button.classList.contains('globalmail'))
                    {
                        structtext = 'toutes les structures';
                    }
                    else
                    {
                        alert('Le type de bouton n\'est pas reconnu');
                        exit;
                    }
                    divmodallabeltext.innerHTML = 'Confirmez vous l\'envoi des rappels à ' + structtext + ' sur ' + periodevalue + ' ? ';
                }
                else if (submit_button.classList.contains('g2tvalidebouton'))
                {
                    var selectperiode = document.getElementById('periodeid');
                    var periodevalue = selectperiode.options[selectperiode.selectedIndex].text;
                    if (selectperiode.selectedIndex > 0)
                    {
                        periodevalue = 'la ' + periodevalue;
                    }
                    periodevalue = periodevalue.replaceAll("-","").toLowerCase().trim();
                    var structtext = '';
                    if (submit_button.classList.contains('structsave'))
                    {
                        var selectstruct = submit_button.closest("td,th").getElementsByTagName("label")[0];
                        var structname = selectstruct.innerText;
                        structtext = 'la structure "' + structname + '"';
                    }
                    else if (submit_button.classList.contains('globalsave'))
                    {
                        structtext = 'toutes les structures';
                    }
                    else
                    {
                        alert('Le type de bouton n\'est pas reconnu');
                        exit;
                    }
                    divmodallabeltext.innerHTML = 'Confirmez vous le dépot des congés pour les agents de ' + structtext + ' sur ' + periodevalue + ' ? ';
                }
                else
                {
                    alert("Type de bouton inconnu => Impossible de modifier le texte");
                    exit;
                }
            }
            divstructid.hidden = true;
            divagentid.hidden = true;
            divselecttype.hidden = true;
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
            divmodal.style.display = "block";
        };

        function sendmail_global()
        {
            var tabliste = document.getElementsByClassName("tabsynthese");
            //console.log('La liste est chargée');
            if (tabliste)
            {
                for (indextab = 0 ; indextab < tabliste.length ; indextab++)
                {
                    //console.log('Dans le for => ' + indextab);
                    var currenttab = tabliste[indextab];
                    var structid = currenttab.id;
                    //console.log('Avant appel sendmail_struct ' + structid);
                    sendmail_struct(structid);
                }
            }
        }

        function sendmail_struct(structid)
        {
            //console.log('sendmail_struct => ' + structid);
            //var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";

            var currenttab = document.getElementById(structid);
            if (currenttab)
            {
                var tabbodyliste = currenttab.getElementsByTagName("tbody");
                if (tabbodyliste)
                {
                    //console.log("J'ai un body");
                    for (indextabbody = 0 ; indextabbody < tabbodyliste.length ; indextabbody++)
                    {
                        var currenttabbody = tabbodyliste[indextabbody];
                        var trliste = currenttabbody.getElementsByTagName("tr");
                        if (trliste)
                        {
                            // On parcourt les TR => Pour chaque TR
                            for (indextr = 0 ; indextr < trliste.length ; indextr++)
                            {
                                var currenttr = trliste[indextr];
                                // Si le TR est visible (<=> hidden = false)
                                if (currenttr.hidden==false)
                                {
                                    // On récupère le bouton d'envoi de mail correspondant
                                    var agentsendmailbutton = currenttr.getElementsByClassName("g2tbouton g2tenvoibouton")[0];
                                    if (agentsendmailbutton)
                                    {
                                        // On envoie de mail pour l'agent courant
                                        sendmail_agent(agentsendmailbutton);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        function sendmail_agent(button)
        {
            //console.log('sendmail_struct => ' + structid);
            var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";

            var currentbutton = button;
            var currenttr = currentbutton.closest("tr");
            if (currenttr.hidden==false)
            {
                //console.log("CurrentTR Text = " + currenttr.innerText);
                var statutinfotd = currenttr.getElementsByClassName("statutinfo")[0];
                //console.log("Le statutinfotd est : " + statutinfotd.innerText.toUpperCase().trim());
                if (statutinfotd && statutinfotd.innerText.toUpperCase().trim()=='KO')
                {
                    var agentnametd = currenttr.getElementsByClassName("agentname")[0];
                    var datedebuttd = currenttr.getElementsByClassName("datedebut")[0];
                    var datefintd = currenttr.getElementsByClassName("datefin")[0];
                    var destinataireid = agentnametd.getAttribute("agentid");
                    var expediteurid = '<?php echo SPECIAL_USER_IDLISTERHUSER ?>'   //'<?php echo $userid ?>';
                    var mailbody = "Il vous est rappelé que vous devez poser vos congés sur la période obligatoire du " + datedebuttd.innerText + " au " +  datefintd.innerText + ".<br>"
                                + "Dans le cas contraire, vous devez signaler au service QVT de la DRH que vous souhaitez une exception.<br>"
                                + "Merci pour votre compréhension.<br><br>";

                    //console.log("Destinataire id = " + destinataireid);
                    nbmailaenvoyer++;
                    showwaitingimg();

                    $.post(fullWSURL , { methode : "<?php echo agent::WS_METHODE_SEND_MAIL; ?>", expediteurid: expediteurid, destinataireid: destinataireid, corpsmail: mailbody })
                        .done(function( data ) {
                            if (data.status.toUpperCase()=='OK')
                            {
                                var statutinfo = "OK";
                            }
                            else
                            {
                                var statutinfo = "KO => " + data.description;
                            }
                            //console.log("Retour du WS pour " + destinataireid + " => " + statutinfo);
                        })
                        .fail(function( xhr ) {
                            var statutinfo = "Erreur WS - méthode : <?php echo agent::WS_METHODE_SEND_MAIL; ?> - Agent : " + agentnametd.innerText + " : " + xhr.status + " " + xhr.statusText;
                            console.log(statutinfo);
                            var labelerror = document.getElementById("errorlabel");
                            if (labelerror.innerText.length > 0) { labelerror.innerHTML = labelerror.innerHTML + "<br>"; }
                            labelerror.innerHTML = labelerror.innerHTML + statutinfo;
                        })
                        .always(function() {
                            nbmailaenvoyer--;
                            if (nbmailaenvoyer==0)
                            {
                                hiddewaitingimg();
                            }
                        });
                }
            }
        }

        function poserconges_global()
        {
            var tabliste = document.getElementsByClassName("tabsynthese");
            //console.log('La liste est chargée');
            if (tabliste)
            {
                for (indextab = 0 ; indextab < tabliste.length ; indextab++)
                {
                    //console.log('Dans le for => ' + indextab);
                    var currenttab = tabliste[indextab];
                    var structid = currenttab.id;
                    //console.log('Avant appel sendmail_struct ' + structid);
                    poserconges_struct(structid);
                }
            }
        }

        function poserconges_struct(structid)
        {
            //console.log('sendmail_struct => ' + structid);
            //var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";

            var currenttab = document.getElementById(structid);
            if (currenttab)
            {
                var tabbodyliste = currenttab.getElementsByTagName("tbody");
                if (tabbodyliste)
                {
                    //console.log("J'ai un body");
                    for (indextabbody = 0 ; indextabbody < tabbodyliste.length ; indextabbody++)
                    {
                        var currenttabbody = tabbodyliste[indextabbody];
                        var trliste = currenttabbody.getElementsByTagName("tr");
                        if (trliste)
                        {
                            // On parcourt les TR => Pour chaque TR
                            for (indextr = 0 ; indextr < trliste.length ; indextr++)
                            {
                                var currenttr = trliste[indextr];
                                // Si le TR est visible (<=> hidden = false)
                                if (currenttr.hidden==false)
                                {
                                    // On récupère le bouton de dépot des congés correspondant
                                    var agentvalidebutton = currenttr.getElementsByClassName("g2tbouton g2tvalidebouton")[0];
                                    if (agentvalidebutton)
                                    {
                                        // On pose les congés pour l'agent courant
                                        poserconges_agent(agentvalidebutton);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        function poserconges_agent(button)
        {
            //console.log('poserconges_agent => ' + button.id);
            var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";

            var currentbutton = button;
            var currenttr = currentbutton.closest("tr");
            if (currenttr.hidden==false)
            {
                //console.log("CurrentTR Text = " + currenttr.innerText);
                var statutinfotd = currenttr.getElementsByClassName("statutinfo")[0];
                //console.log("Le statutinfotd est : " + statutinfotd.innerText.toUpperCase().trim());
                if (statutinfotd && statutinfotd.innerText.toUpperCase().trim()=='KO')
                {
                    var agentnametd = currenttr.getElementsByClassName("agentname")[0];
                    var agentid = agentnametd.getAttribute("agentid");
                    var datedebuttd = currenttr.getElementsByClassName("datedebut")[0];
                    var datedebut = datedebuttd.getAttribute("datedbformat");
                    var datefintd = currenttr.getElementsByClassName("datefin")[0];
                    var datefin = datefintd.getAttribute("datedbformat");
                    //console.log("Avant");
                    var periodeid = currenttr.className;
                    //console.log("Apres "  + periodeid);
                    nbrecongesaposer++;
                    showwaitingimg();

                    $.post(fullWSURL , { methode : "<?php echo agent::WS_METHODE_FORCE_PERIODE; ?>", agentid : agentid , periodeid : periodeid , datedebut : datedebut , datefin : datefin })
                        .done(function( data ) {
                            const statusok = ['<?php echo agent::CHECK_PERIODE_COUVERTE ?>', '<?php echo agent::CHECK_PERIODE_AJOUTEE ?>'];
                            if (statusok.includes(data.status))
                            {
                                var extraclass = 'okbackgroundtext';
                                var statutinfo = "OK";
                            }
                            else if (data.status=='<?php echo agent::CHECK_PERIODE_NONCOUVERTE ?>')
                            {
                                var extraclass = 'kobackgroundtext';
                                var statutinfo = "KO";
                            }
                            else if (data.status=='<?php echo agent::CHECK_PERIODE_EXCEPTION ?>')
                            {
                                var extraclass = 'warnbackgroundtext';
                                var statutinfo = "OK";
                            }
                            else
                            {
                                var extraclass = 'redtext';
                                var statutinfo = "Erreur lors de la pose du congé : " + data.description + "";
                            }
                            var currenttr = button.closest("tr");
                            if (currenttr)
                            {
                                //console.log(currenttr.innerText);
                                var tdliste = currenttr.getElementsByTagName("td");
                                for (indextd = 1 ; indextd < tdliste.length ; indextd++) // On commence à la 2e colonne (<=> index = 1)
                                {
                                    currenttd = tdliste[indextd];
                                    currenttd.classList.remove("okbackgroundtext","kobackgroundtext","warnbackgroundtext", "redtext" );
                                    currenttd.classList.add(extraclass);
                                    if (currenttd.classList.contains("statutinfo"))
                                    {
                                        currenttd.innerText = statutinfo;
                                    }
                                }
                            }

                        })
                        .fail(function( xhr ) {
                            var currenttr = button.closest("tr");
                            if (currenttr)
                            {
                                var tdliste = currenttr.getElementsByTagName("td");
                                var extraclass = 'redtext';
                                for (indextd = 1 ; indextd < tdliste.length ; indextd++) // On commence à la 2e colonne (<=> index = 1)
                                {
                                    currenttd = tdliste[indextd];
                                    currenttd.classList.remove("okbackgroundtext","kobackgroundtext","warnbackgroundtext", "redtext" );
                                    currenttd.classList.add(extraclass);
                                    if (currenttd.classList.contains("statutinfo"))
                                    {
                                        console.log("Erreur = " + xhr.status + " " + xhr.statusText);
                                        currenttd.innerText = "Erreur WS : " + xhr.status + " " + xhr.statusText + " => L'action n'a pas été enregistrée.";
                                    }
                                }
                            }
                        })
                        .always(function() {
                            nbrecongesaposer--;
                            if (nbrecongesaposer==0)
                            {
                                hiddewaitingimg();
                            }
                        });
                }
            }

        }

        function modifieperiode(selectobject, agentid, periodeid)
        {
            var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";
            var currentindx = selectobject.selectedIndex;
            var optionvalue = selectobject.options[currentindx].value;

            document.getElementById('errorlabel').innerText = '';
            showwaitingimg();
            $.post(fullWSURL , { methode : "<?php echo agent::WS_METHODE_EXCEPTION_PERIODE; ?>", agentid: agentid, periodeid: periodeid, newvalue: optionvalue })
                .done(function( data ) {
                    //console.log( "Data Loaded: " + data.status + " " + data.description);

                    if (data.status=='<?php echo agent::CHECK_PERIODE_COUVERTE ?>')
                    {
                        var extraclass = 'okbackgroundtext';
                        var statutinfo = "OK";
                    }
                    else if (data.status=='<?php echo agent::CHECK_PERIODE_NONCOUVERTE ?>')
                    {
                        var extraclass = 'kobackgroundtext';
                        var statutinfo = "KO";
                    }
                    else if (data.status=='<?php echo agent::CHECK_PERIODE_EXCEPTION ?>')
                    {
                        var extraclass = 'warnbackgroundtext';
                        var statutinfo = "OK";
                    }
                    else
                    {
                        var extraclass = 'redtext';
                        var statutinfo = "Erreur lors de l'étude des congés : " + data.description + "";
                    }
                    var currenttr = selectobject.closest("tr");
                    if (currenttr)
                    {
                        //console.log(currenttr.innerText);
                        var tdliste = currenttr.getElementsByTagName("td");
                        for (indextd = 1 ; indextd < tdliste.length ; indextd++) // On commence à la 2e colonne (<=> index = 1)
                        {
                            currenttd = tdliste[indextd];
                            currenttd.classList.remove("okbackgroundtext","kobackgroundtext","warnbackgroundtext", "redtext" );
                            currenttd.classList.add(extraclass);
                            if (currenttd.classList.contains("statutinfo"))
                            {
                                currenttd.innerText = statutinfo;
                            }
                        }
                    }
                })
                .fail(function( xhr ) {
                    var currenttr = selectobject.closest("tr");
                    if (currenttr)
                    {
                        var tdliste = currenttr.getElementsByTagName("td");
                        var extraclass = 'redtext';
                        for (indextd = 1 ; indextd < tdliste.length ; indextd++) // On commence à la 2e colonne (<=> index = 1)
                        {
                            currenttd = tdliste[indextd];
                            currenttd.classList.remove("okbackgroundtext","kobackgroundtext","warnbackgroundtext", "redtext" );
                            currenttd.classList.add(extraclass);
                            if (currenttd.classList.contains("statutinfo"))
                            {
                                console.log("Erreur = " + xhr.status + " " + xhr.statusText);
                                currenttd.innerText = "Erreur WS : " + xhr.status + " " + xhr.statusText + " => L'action n'a pas été enregistrée.";
                            }
                        }
                    }
                })
                .always(function() {
                    hiddewaitingimg();
                });
        }

        function periodefilter()
        {
            document.getElementById('errorlabel').innerText = '';
            var selectobject = document.getElementById("periodeid");
            if (selectobject)
            {
                var currentindx = selectobject.selectedIndex;
                var optionvalue = selectobject.options[currentindx].value;

                var tableauliste = document.getElementsByClassName("tabsynthese");
                if (tableauliste)
                {
                    for (indextab = 0 ; indextab < tableauliste.length ; indextab++)
                    {
                        var tabbodyliste = tableauliste[indextab].getElementsByTagName("tbody");
                        if (tabbodyliste)
                        {
                            for (indextabbody = 0 ; indextabbody < tabbodyliste.length ; indextabbody++)
                            {
                                var currenttabbody = tabbodyliste[indextabbody];
                                var trliste = currenttabbody.getElementsByTagName("tr");
                                if (trliste)
                                {
                                    // On parcourt les TR => Pour chaque TR
                                    for (indextr = 0 ; indextr < trliste.length ; indextr++)
                                    {
                                        var currenttr = trliste[indextr];
                                        // Si la ligne contient la période ou si on doit afficher toutes les périodes => On affiche la ligne
                                        if (currenttr.classList.contains(optionvalue) || optionvalue == 'all')
                                        {
                                            currenttr.hidden = false;
                                        }
                                        else // La ligne ne concerne pas la période demandée => On la masque
                                        {
                                            currenttr.hidden = true;
                                        }
                                    }
                                }

                                // On va traiter les cellule avec le nom de l'agent
                                var celullenomliste = currenttabbody.getElementsByClassName("agentname");
                                if (celullenomliste)
                                {
                                    for (indexnom = 0 ; indexnom < celullenomliste.length ; indexnom++)
                                    {
                                        currentnom = celullenomliste[indexnom];
                                        // Si on affiche toutes les périodes => On va agrandir (en hauteur) la première cellule nomagent et masquer les autres
                                        if (optionvalue == 'all')
                                        {
                                            if (currentnom.classList.contains('newagent')) // C'est la première fois qu'on affiche le nom de l'agent
                                            {
                                                // On agrandi (verticalement) la cellule sur le nombre maximale de période pour cet agent (=> attribut maximal-rowspan)
                                                currentnom.rowSpan = currentnom.getAttribute("maximal-rowspan");
                                            }
                                            else
                                            {
                                                // Ce n'est pas la première cellule avec le nom => On la masque
                                                currentnom.hidden = true;
                                            }
                                        }
                                        else // On n'affiche qu'une période => On remet tout comme au début car le masquage des informations est porté par la ligne TR 
                                        {
                                            currentnom.hidden = false;
                                            currentnom.rowSpan = 1;
                                        }
                                    }

                                }
                            }
                        }
                    }
                }
            }
        }
    </script>

    <script>
        window.addEventListener("load", (event) => { 
            hiddewaitingimg();
        });
    </script>    


</body>
</html>

