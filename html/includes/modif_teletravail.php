<?php
    $planningelement = new planningelement($dbcon);
    $planningelement->type('teletrav');
    $couleur = $planningelement->couleur();


    function modiftt_envoyermailreponsable(ttexception $ttexception, string $action)
    {
        global $fonctions;
        global $dbcon;
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents('modiftt_envoyermailreponsable : Id => ' . $ttexception->id() . " Action => $action  Agentid = " . $ttexception->agentid));

        static $cronuser = null;
        if (is_null($cronuser))
        {
            $cronuser = new agent($dbcon);
            if (!$cronuser->load(SPECIAL_USER_IDCRONUSER))
            {
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents('Erreur lors du chargement du cronuser => envoi de mail impossible'));
                $cronuser = null;
                return;
            }
        }

        $agent = new agent($dbcon);
        $agent->load($ttexception->agentid);
        $responsable = $agent->getsignataire();
        $corpsmail = "";
        if ($responsable!==false)
        {
            switch ($action)
            {
                case ttexception::ACTION_DEPLACEMENT :
                    $corpsmail = $corpsmail . mb_convert_case($agent->identitecomplete(), MB_CASE_TITLE) . " vient de demander le déplacement de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " vers le " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    $corpsmail = $corpsmail . "Merci valider ou refuser cette demande, dans les meilleurs délais.<br>";
                    break;
                case ttexception::ACTION_SUPPRIME :
                    $corpsmail = $corpsmail . mb_convert_case($agent->identitecomplete(), MB_CASE_TITLE) . " vient de demander la suppression de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . ".<br>";
                    $corpsmail = $corpsmail . "Merci valider ou refuser cette demande, dans les meilleurs délais.<br>";
                    break;
                case ttexception::ACTION_REACTIVE :
                    $corpsmail = $corpsmail . mb_convert_case($agent->identitecomplete(), MB_CASE_TITLE) . " vient de réactiver l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " qui avait fait l'objet d'une demande ";
                    if (trim($ttexception->dateremplacement . '') != '')
                    {
                        $corpsmail = $corpsmail . " de déplacement au " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    }
                    else
                    {
                        $corpsmail = $corpsmail . " de suppression.<br>";
                    }
                    break;
            }
            $cronuser->sendmail($responsable, "Modification d'une occurrence de télétravail", $corpsmail,null,null,false);
        }
    }

    function modiftt_envoyermailagent(ttexception $ttexception, string $action)
    {
        global $fonctions;
        global $dbcon;
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents('modiftt_envoyermailagent : Id => ' . $ttexception->id() . " Action => $action   Agentid = " . $ttexception->agentid));

        static $cronuser = null;
        if (is_null($cronuser))
        {
            $cronuser = new agent($dbcon);
            if (!$cronuser->load(SPECIAL_USER_IDCRONUSER))
            {
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents('Erreur lors du chargement du cronuser => envoi de mail impossible'));
                $cronuser = null;
                return;
            }
        }

        $agent = new agent($dbcon);
        $agent->load($ttexception->agentid);
        $responsable = $agent->getsignataire();
        if ($responsable!==false)
        {
            $corpsmail = "";
            switch ($action)
            {
                case ttexception::ACTION_VALIDE :
                    if (trim($ttexception->dateremplacement . '') != '')
                    {
                        $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de valider la demande de déplacement de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " vers le " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    }
                    else
                    {
                        $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de valider la demande de suppression de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . ".<br>";
                    }
                    break;
                case ttexception::ACTION_REFUSE :
                    if (trim($ttexception->dateremplacement . '') != '')
                    {
                        $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de refuser la demande de déplacement de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " vers le " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    }
                    else
                    {
                        $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de refuser la demande de suppression de l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " <br>";
                    }
                    $corpsmail = $corpsmail . "<br>Le motif est : <br>" . $ttexception->motif . ".<br>";
                    break;
                case ttexception::ACTION_DEPLACEMENT :
                    $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de déplacer l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " vers le " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    break;
                case ttexception::ACTION_SUPPRIME :
                    $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de supprimer l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . ".<br>";
                    break;
                case ttexception::ACTION_REACTIVE :
                    $corpsmail = $corpsmail . mb_convert_case($responsable->identitecomplete(), MB_CASE_TITLE) . " vient de réactiver l'occurrence de télétravail du " . $fonctions->formatdate($ttexception->dateorigine) . " " . $fonctions->nommoment($ttexception->momentorigine) . " qui avait fait l'objet d'une demande";
                    if (trim($ttexception->dateremplacement . '') != '')
                    {
                        $corpsmail = $corpsmail . " de déplacement au " . $fonctions->formatdate($ttexception->dateremplacement) . " " . $fonctions->nommoment($ttexception->momentremplacement) . ".<br>";
                    }
                    else
                    {
                        $corpsmail = $corpsmail . " de suppression.<br>";
                    }
                    break;
            }
            $cronuser->sendmail($agent, "Modification d'une occurrence de télétravail", $corpsmail,null,null,false);
        }

    }

?>
    <script>
        divmodalcancelBtn.onclick = function() 
        {
            divmodal.style.display = "none";
            masquerimgmodal();
            for (cpt=(reportselect.options.length-1) ; cpt>=0 ; cpt--)
            {
                if (reportselect.item(cpt).value!=='')
                {
                    reportselect.remove(cpt);
                }
            }
            var input = document.getElementById('date_selected');
            if (input)
            {
                input.value = '';
            }
            var input = document.getElementById('moment_selected');
            if (input)
            {
                input.value = '';
            }
            var input = document.getElementById('agentid_selected');
            if (input)
            {
                input.value = '';
            }
            var input = document.getElementById('action');
            if (input)
            {
                input.value = '';
            }
            var input = document.getElementById('typeconvention');
            if (input)
            {
                input.value = '';
            }

            return false;
        }

        divmodalconfirmBtn.onclick = function()
        {
            divmodal.style.display = "none";

            var report_info = reportselect.value.split('_'); // <=> confirmBtn.value.split('_');
            console.log(report_info);
            var input = document.getElementById('report_date');
            input.value = report_info[0];
            var input = document.getElementById('report_moment');
            if (report_info.length>=2)
            {
                input.value = report_info[1];
            }
            else
            {
                input.value = '';
            }
            var submit_form = input.closest('form');
            submit_form.submit();
        }

        reportselect.addEventListener('change', function onSelect(e) {
            divmodalconfirmBtn.value = reportselect.value;
        });
        
        var dbclick_element = function(elementid, agentid, date,moment,typeconvention, reportteletravail)
        {
            // console.log('je suis dans dbclick_element ' + elementid );

            var element = document.getElementById(elementid);
            var tableau = element.closest("table");
            if (tableau.classList.contains('<?php echo planning::TYPE_STRUCTURE; ?>'))
            {
                var identiteagent = element.closest(".ligneplanning").firstChild.innerText;
            }
            else if (tableau.classList.contains('<?php echo planning::TYPE_AGENT; ?>'))
            {
                var identiteagent = tableau.getAttribute('data-agentname');
            }
            else
            {
                var identiteagent = '';
            }

            if (moment==='<?php echo fonctions::MOMENT_MATIN; ?>')
            {
                var matin = element;
                var apresmidi = element.nextElementSibling; // L'après-midi est le noeud suivant
            }
            else if (moment==='<?php echo fonctions::MOMENT_APRESMIDI; ?>')
            {
                var apresmidi = element;
                var matin = element.previousElementSibling; // Le matin est le noeud précédent
            }
            
            if ((matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>')) 
             && (apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>')))
            {
                deplacement = 'jour';
            }
            else if (matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>'))
            {
                deplacement = '<?php echo fonctions::MOMENT_MATIN; ?>';
            }
            else if (apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>'))
            {
                deplacement = '<?php echo fonctions::MOMENT_APRESMIDI; ?>';
            }
            else
            {
                return;
            }

            // console.log('dbclick_element : déplacement => ' + deplacement );
            // console.log('dbclick_element : couleur de element => ' + element.bgColor);
            // console.log('dbclick_element : Tag de element => ' + element.tagName);
            // console.log('dbclick_element : tableau => ' + tableau.id);

            if (tableau.classList.contains('<?php echo planningelement::JAVA_CLASS_TELETRAVAIL_HIDDEN; ?>'))
            {
                // Si la classe teletravail_hidden est définie dans le tableau => On ne peut pas modifier une journée de télétravail
                masquerimgmodal('error');
                labelmodalheader.innerHTML = 'Action non autorisée';
                divmodalcancelBtn.textContent = "Ok";
                divmodalcancelBtn.hidden = false;
                divmodalcancelBtn.classList.add('g2tokbouton');
                divmodalconfirmBtn.hidden = true;
                divmodallabeltext.parentElement.classList.add('centeraligntext');
                divmodallabeltext.innerHTML = 'Impossible de déplacer ou d\'annuler un jour de télétravail car l\'affichage du télétravail est désactivé.';
                divmodal.style.display = "block";
            }
/*            
*            /////////////////////////////////////////////////////////////
*            // Il est maintenant autorisé de déplacer des jours de télétravail sur convention médical
*            /////////////////////////////////////////////////////////////
*            else if (typeconvention.toString()==='<?php echo teletravail::CODE_CONVENTION_MEDICAL  ?>')
*            {
*                //alert ('Impossible de déplacer ou d\'annuler un jour de télétravail sur convention médicale.');
*                //return;
*                masquerimgmodal('error');
*                labelmodalheader.innerHTML = 'Action non autorisée';
*                divmodalcancelBtn.textContent = "Ok";
*                divmodalcancelBtn.hidden = false;
*                divmodalcancelBtn.classList.add('g2tokbouton');
*                divmodalconfirmBtn.hidden = true;
*                divmodallabeltext.parentElement.classList.add('centeraligntext');
*                divmodallabeltext.innerHTML = 'Impossible de déplacer ou d\'annuler un jour de télétravail sur convention médicale.';
*                divmodal.style.display = "block";
*                }        
*            }
*/


            // else if (element.bgColor == '<?php echo $couleur ?>') // C'est un teletravail à annuler/déplacer
            else if (element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') 
                  && !element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_DEPLACE); ?>') 
                  && !element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_EXCLUSION); ?>')
                  && !element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE); ?>'))
            {
                // console.log('dbclick_element : On veut déplacer un élément' );

                if (reportteletravail)
                {
                    masquerimgmodal('question');

                    if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Que souhaitez vous faire de la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent; // + '<br><br>Action à réaliser :';
                    }
                    else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Que souhaitez vous faire de la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent; // + '<br><br>Action à réaliser :';
                    }
                    else
                    {
                        divmodallabeltext.innerHTML = 'Que souhaitez vous faire de la journée de télétravail du ' + date + ' pour l\'agent ' + identiteagent; // + '<br><br>Action à réaliser :';
                    }
                    reportselect.hidden = false;
                    labelmodalheader.innerHTML = 'Déplacement d\'un télétravail';
                    divmodalcancelBtn.textContent = "Annuler";
                    divmodalcancelBtn.classList.add('g2tannulerbouton');
                    divmodalcancelBtn.hidden = false;
                    divmodalconfirmBtn.textContent = "Valider";
                    divmodalconfirmBtn.classList.add('g2tvalidebouton');
                    divmodalconfirmBtn.hidden = false;
                    divreportid.hidden = false;

                    var input = document.getElementById('date_selected');
                    input.value = date;
                    var input = document.getElementById('moment_selected');
                    input.value = moment;
                    var input = document.getElementById('agentid_selected');
                    input.value = agentid;
                    var input = document.getElementById('action');
                    input.value = '<?php echo ttexception::ACTION_DESACTIVE; ?>';
                    var input = document.getElementById('typeconvention');
                    input.value = typeconvention;

                    for (cpt=(reportselect.options.length-1) ; cpt>=0 ; cpt--)
                    {
                        if (reportselect.item(cpt).value!=='')
                        {
                            reportselect.remove(cpt);
                        }
                    }
                    var jrs="dimanche,lundi,mardi,mercredi,jeudi,vendredi,samedi".split(",");
                    // On calcule la date du lundi de la semaine courante
                    
                    var elementdate = date.split('/'); 
                    var currentdate = new Date(elementdate[2], elementdate[1]-1, elementdate[0]);  // on fourni le format YYYY, MM, DD !! Le mois de janvier est 0
                    var dateref = new Date(currentdate.getFullYear(), currentdate.getMonth(),currentdate.getDate()-(currentdate.getDay()-1));
                    // dateref correspond au lundi de la semaine courante
                    for (cpt=1 ; cpt <= 7 ; cpt++)
                    {
                        var frenchdate = dateref.getDate().toString().padStart(2, '0') + '/' + (dateref.getMonth()+1).toString().padStart(2, '0') + '/' + dateref.getFullYear();
                        if (dateref.getDay()>0 && dateref.getDay()<6)
                        {
                            if (deplacement==='jour')
                            {
                                if (frenchdate.toString()!==date.toString())
                                {
                                    var newoption = document.createElement("option");
                                    newoption.value = frenchdate + '_all';
                                    newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate;
                                    reportselect.add(newoption, null);
                                    //console.log("On ajoute " + newoption.text + " => nombre option = " + reportselect.options.length);
                                }
                            }
                            else
                            {
                                if (frenchdate.toString()!==date.toString() || (frenchdate.toString()===date.toString() && deplacement !== '<?php echo fonctions::MOMENT_MATIN; ?>'))
                                {
                                    var newoption = document.createElement("option");
                                    newoption.value = frenchdate + '_' + '<?php echo fonctions::MOMENT_MATIN; // echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?>';
                                    newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?>';
                                    reportselect.add(newoption, null);
                                    //console.log("On ajoute " + newoption.text + " => nombre option = " + reportselect.options.length);
                                }
                                if (frenchdate.toString()!==date.toString() || (frenchdate.toString()===date.toString() && deplacement !== '<?php echo fonctions::MOMENT_APRESMIDI; ?>'))
                                {
                                    var newoption = document.createElement("option");
                                    newoption.value = frenchdate + '_' + '<?php echo fonctions::MOMENT_APRESMIDI; //echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?>';
                                    newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?>';
                                    reportselect.add(newoption, null);
                                    //console.log("On ajoute " + newoption.text + " => nombre option = " + reportselect.options.length);
                                }
                            }
                        }
                        else  // On est un samedi ou un dimanche 
                        {
                            break; // On sort de la boucle (car report uniquement sur la semaine en cours)
                        }
                        var elementdate = frenchdate.split('/');
                        var currentdate = new Date(elementdate[2], elementdate[1]-1, elementdate[0]);  // on fourni le format YYYY, MM, DD !! Le mois de janvier est 0
                        var dateref = new Date(currentdate.getFullYear(), currentdate.getMonth(),currentdate.getDate()+1);
                        // dateref correspond au jour suivant
                    }
                    //console.log("On a combien d'options = " + reportselect.options.length);
                    divmodal.style.display = "block";
                }
                else
                {
                    masquerimgmodal('question');

                    if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Supprimer la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                    }
                    else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Supprimer la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                    }
                    else
                    {
                        divmodallabeltext.innerHTML = 'Supprimer la journée de télétravail du ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                    }
                    reportselect.hidden = true;
                    labelmodalheader.innerHTML = 'Suppression d\'un télétravail';
                    divmodalcancelBtn.textContent = "Annuler";
                    divmodalcancelBtn.classList.add('g2tannulerbouton');
                    divmodalcancelBtn.hidden = false;
                    divmodalconfirmBtn.textContent = "Valider";
                    divmodalconfirmBtn.classList.add('g2tvalidebouton');
                    divmodalconfirmBtn.hidden = false;
                    divreportid.hidden = false;
                    
                    var input = document.getElementById('date_selected');
                    input.value = date;
                    var input = document.getElementById('moment_selected');
                    input.value = moment;
                    var input = document.getElementById('agentid_selected');
                    input.value = agentid;
                    var input = document.getElementById('action');
                    input.value = '<?php echo ttexception::ACTION_DESACTIVE; ?>';
                    var input = document.getElementById('typeconvention');
                    input.value = typeconvention;
                    divmodal.style.display = "block";
                }
            }

            // else if (element.bgColor == '<?php echo planningelement::COULEUR_VIDE ?>') // C'est un teletravail déjà annulé => On veut le réactiver
            else if (
                  (element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') 
                  && element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_EXCLUSION); ?>')
                  && !element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_DEPLACE); ?>'))
               || (element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>')
                  && element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE); ?>'))
                    )
            {
                // Si la demande est en attente de validation => On affiche le message 'Réactiver....' sinon 'Annuler la demande....'
                if (!element.classList.contains('<?php echo trim(planningelement::HTML_CLASS_DEPLACEMENT_ENATTENTE); ?>'))
                {
                    // A partir du planning de l'agent on ne peut pas réactiver une journée de télétravail 
                    // => C'est le responsable qui peut le faire à partir du planning de la structure
                    if (tableau.classList.contains('<?php echo trim(planning::TYPE_AGENT); ?>'))
                    {
                        return;
                    }
                    if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Réactiver le télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                    }
                    else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                    {
                        divmodallabeltext.innerHTML = 'Réactiver le télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                    }
                    else
                    {
                        divmodallabeltext.innerHTML = 'Réactiver le télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                    }
                    labelmodalheader.innerHTML = 'Réactivation d\'un télétravail';
                }
                else
                {
                    datecible = element.getAttribute('data-depladatecible') || '';  // Permet de convertir un NULL en chaine vide
                    momentcible = element.getAttribute('data-deplamomentcible') || '';  // Permet de convertir un NULL en chaine vide
                    // console.log("dbclick_element : Datecible = " + datecible + "  momentCible = " + momentcible);
                    if (datecible != '')
                    {
                        divmodallabeltext.innerHTML = "Une demande de déplacement de télétravail vers le " + datecible + " " + momentcible + " est déjà enregistrée pour cette occurrence.<br>";
                        if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de déplacement du télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de déplacement du télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de déplacement du télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                        }
                        labelmodalheader.innerHTML = 'Annulation d\'une demande de modification';
                    }
                    else
                    {
                        divmodallabeltext.innerHTML = "Une demande de suppression de télétravail est déjà enregistrée pour cette occurrence.<br>";
                        if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de suppression du télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de suppression du télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else
                        {
                            divmodallabeltext.innerHTML = divmodallabeltext.innerHTML + 'Annuler la demande de suppression du télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                        }
                        labelmodalheader.innerHTML = 'Annulation d\'une demande de suppression';
                    }
                }
                masquerimgmodal('question');
                var input = document.getElementById('date_selected');
                input.value = date;
                var input = document.getElementById('moment_selected');
                input.value = moment;
                var input = document.getElementById('agentid_selected');
                input.value = agentid;
                var input = document.getElementById('action');
                input.value = '<?php echo ttexception::ACTION_REACTIVE; ?>';
                var input = document.getElementById('typeconvention');
                input.value = typeconvention;

                reportselect.hidden = true;
                divmodalcancelBtn.textContent = "Non";
                divmodalcancelBtn.hidden = false;
                divmodalcancelBtn.classList.add('g2tannulerbouton');
                divmodalconfirmBtn.textContent = "Oui";
                divmodalconfirmBtn.hidden = false;
                divmodalconfirmBtn.classList.add('g2tvalidebouton');
                divmodal.style.display = "block";
            }
	    };
    </script>
<?php
?>