<?php
    require_once ('CAS.php');
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
    
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    $previoustxt = null;
    if (isset($_POST["previous"]))
    {
        $previoustxt = $_POST["previous"];
    }

    if (strcasecmp($previoustxt, "yes") == 0)
    {
        $previous = 1;
    }
    else
    {
        $previous = 0;
    }
    
    $indexmois = null;
    if (isset($_POST["indexmois"]))
    {
        $indexmois = $_POST["indexmois"];
    }

    if (is_null($indexmois) or $indexmois == "")
    {
        $indexmois = date("m");
    }
    $indexmois = str_pad($indexmois, 2, "0", STR_PAD_LEFT);
    // echo "indexmois (apres) = $indexmois <br>";
    $annee = $fonctions->anneeref() - $previous;
    // echo "annee = $annee <br>";
    $debutperiode = $fonctions->debutperiode();
    // echo "debut periode = $debutperiode <br>";
    $moisdebutperiode = date("m", strtotime($fonctions->formatdatedb(date("Y") . $debutperiode)));
    // echo "moisdebutperiode = $moisdebutperiode <br>";
    
    if ($indexmois < $moisdebutperiode)
    {
        $annee ++;
    }
    // echo "annee (apres) = $annee <br>";
                                    
    $mode = "resp";
    if (isset($_POST["mode"]))
    {
        $mode = $_POST["mode"]; // Mode = resp ou agent
    }
                                            
    $date_selected = '';
    if (isset($_POST["date_selected"]))
    {
        $date_selected = $_POST["date_selected"];
    }
    
    $moment_selected = '';
    if (isset($_POST["moment_selected"]))
    {
        $moment_selected = $_POST["moment_selected"];
    }
    
    $agentid_selected = '';
    if (isset($_POST['agentid_selected']))
    {
        $agentid_selected = $_POST['agentid_selected'];
    }
            
    $action = '';
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];
    }
    
    $report_date = '';
    if (isset($_POST['report_date']))
    {
        $report_date = $_POST['report_date'];
    }
        
    $report_moment = '';
    if (isset($_POST['report_moment']))
    {
        $report_moment = $_POST['report_moment'];
    }

    $rootstruct = '';
    if (isset($_POST['rootid']))
    {
        $rootstruct = $_POST['rootid'];
    }
            
    $check_showroot = 'off';
    if (isset($_POST['check_showroot']))
    {
        $check_showroot = $_POST['check_showroot'];
    }
                
    $structureid = '';
    if (isset($_POST['structureid']))
    {
        $structureid = $_POST['structureid'];
    }
    
    $typeconvention = '';
    if (isset($_POST['typeconvention']))
    {
        $typeconvention = $_POST['typeconvention'];
    }
    
    
    if (isset($_POST['datedebut']))
    {
        $datedebut = $_POST['datedebut'];
    }
    
    if (isset($_POST['datefin']))
    {
        $datefin = $_POST['datefin'];
    }
            
    require ("includes/menu.php");
//    echo "<br><br><br>"; print_r($_POST); echo "<br>";

    
    if (isset($_POST['teletravailmail']))
    {
        // On va générer le PDF et l'envoyer par mail au responsable
        //echo "On génère le PDF par mail.";
        $structure = new structure($dbcon);
        $structure->load($structureid);
        $pdffilename = $structure->teletravailpdf($datedebut,$datefin,true);
        $cronuser = new agent($dbcon);
        $cronuser->load(SPECIAL_USER_IDCRONUSER);
        $cronuser->sendmail($user,'Synthèse annuelle - télétravail pour ' . $structure->nomlong(), "Vous trouverez ci-joint le document de synthèse du télétravail pour les agents de la structure " . $structure->nomlong(),$pdffilename);
        echo $fonctions->showmessage(fonctions::MSGINFO, "Le document PDF vous a été envoyé.");
        unset($cronuser);
        unset($structure);
    }
    
    echo "<br>";

    if ($date_selected != "" and $moment_selected != "" and $agentid_selected != "")
    {
        // var_dump ("report_date = " . $report_date);
        $complement = new complement($dbcon);
        $agent = new agent($dbcon);
        $agent->load($agentid_selected);
        if ($action == 'desactive')
        {   // On fait une désactivation de la date
            // var_dump('on desactive');
            // $listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //var_dump($listeexclusion);
            // if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)===false)
            
            // Si on doit déplacer la journée complète, on doit mettre à vide le moment sélectionné et le moment de destination
            if ($report_moment!==fonctions::MOMENT_MATIN and $report_moment!==fonctions::MOMENT_APRESMIDI)
            {
                $report_moment = '';
                $moment_selected = '';
            }
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected);
            if ($exclusion===false)
            {   // On n'a pas trouvé la date dans la liste
                $reportpossible = true;
                if ($report_date != '')
                {
                    $planning = new planning($dbcon);
                    $planning->load($agentid_selected, $report_date, $report_date, true, true, true);
                    $planningelementliste = $planning->planning();
                    if ($report_moment==fonctions::MOMENT_MATIN)
                    {
                        $planningelement = current($planningelementliste);
                        $reportpossible = ($planningelement->type()=='');
                    }
                    elseif ($report_moment==fonctions::MOMENT_APRESMIDI)
                    {
                        $planningelement = next($planningelementliste);
                        $reportpossible = ($planningelement->type()=='');                        
                    }
                    else
                    {
                        foreach ($planning->planning() as $planningelement)
                        {
                            if ($planningelement->type()!='')
                            {
                                $reportpossible = false;
                                break;
                            }
                        }
                    }
                }
                // var_dump($reportpossible);
                if ($reportpossible)
                {
                    // var_dump("On va faire le complément");
                    $erreur = $fonctions->ajoutjoursteletravailexclus($agentid_selected, $date_selected, $moment_selected, $report_date, $report_moment);
                    // var_dump("erreur = " . $erreur);
                    if (trim($report_date) != '')
                    {
                        echo $fonctions->showmessage(fonctions::MSGINFO,"La journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est déplacée au " . $fonctions->formatdate($report_date) . ".");
                    }
                    else
                    {
                        echo $fonctions->showmessage(fonctions::MSGINFO,"La suppression de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                    }
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de déplacer la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " : La date souhaitée (le " . $fonctions->formatdate($report_date) . ") n'est pas disponible.");
                }
            }
            else
            {
                //echo "On demande une désactivation alors que la date est déjà désactivé. On ne fait rien. <br>";
            }
        }
        elseif ($action == 'reactive')
        {   // On fait une réactivation
            //$listeexclusion = $agent->listejoursteletravailexclus($date_selected, $date_selected);
            //if (array_search($fonctions->formatdatedb($date_selected),(array)$listeexclusion)!==false)
            
            $exclusion = $agent->estjourteletravailexclu($date_selected, $moment_selected);
            // var_dump("exclusion = " . $exclusion);
            if ($exclusion!==false)
            {   // On a trouvé la date dans la liste
                // var_dump("On n'a pas trouvé la date dans les exclusions");
                $erreur = $agent->supprjourteletravailexclu($date_selected, $moment_selected);
                // var_dump("Erreur = XXXX" . $erreur . "XXXX");
                if (strlen(trim($erreur))==0)
                {
                    echo $fonctions->showmessage(fonctions::MSGINFO,"La réactivation de la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete() . " est enregistrée.");
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR,"Impossible de réactiver la journée de télétravail du " . $fonctions->formatdate($date_selected) . " pour l'agent " . $agent->identitecomplete()  . " : $erreur ");                    
                }
            }
            else
            {
                // var_dump ("On demande une réactivation alors que la date n'est pas désactivé. On ne fait rien.");
            }
        }
    }

    $planningelement = new planningelement($dbcon);
    $planningelement->type('teletrav');
    $couleur = $planningelement->couleur();
    
?>
        <!-- Toutes les informations sur la boite de dialogue personnalisée en HTML --> 
        <!-- sont sur le lien https://developer.mozilla.org/fr/docs/Web/HTML/Element/dialog -->

        <dialog id="reportdialog" class="questiondialog">
          <form method="dialog">
            <p>
<?php
        $type = 'question';
        $path = $fonctions->imagepath() . "/" . $type . "_logo.png";
        $typeimage = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
        echo "<img class='img". $type ." imagedialog' src='" . $base64 . "'>&nbsp;"; // style='vertical-align:middle; width:50px;height:50px;'

?>
                <label id='labeltext'>Action à réaliser :</label>
                <select id='reportchoice' hidden='hidden'>
                    <option value=''>Ne pas reporter</option>
                </select>
            </p>
            <menu><center>
              <button id="confirmBtn" value="" style="width:100px;">Ok</button>
              <button id="cancelBtn" value="cancel" style="width:100px;">Annuler</button>
            </center></menu>
          </form>
        </dialog>    
<script>
    
        let selectEl = document.getElementById('reportchoice');
        let confirmBtn = document.getElementById('confirmBtn');
        let reportdialog = document.getElementById('reportdialog');
        let labeltext = document.getElementById('labeltext');
        let cancelBtn = document.getElementById('cancelBtn');        
        
        selectEl.addEventListener('change', function onSelect(e) {
          confirmBtn.value = selectEl.value;
        });

        reportdialog.addEventListener('close', function onClose() {
            if (reportdialog.returnValue!=='cancel')
            {
                var report_info = reportdialog.returnValue.split('_');
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
                
                var submit_form = document.getElementById('select_mois');
                submit_form.submit();
            }
            else
            {
                for (cpt=(selectEl.options.length-1) ; cpt>=0 ; cpt--)
                {
                    if (selectEl.item(cpt).value!=='')
                    {
                        selectEl.remove(cpt);
                    }
                }
                
                var input = document.getElementById('date_selected');
                input.value = '';
                var input = document.getElementById('moment_selected');
                input.value = '';
                var input = document.getElementById('agentid_selected');
                input.value = '';
                var input = document.getElementById('action');
                input.value = '';
                var input = document.getElementById('typeconvention');
                input.value = '';
                // alert ('La modification est annulée par l\'utilisateur.');
            }
        });
        
	var dbclick_element = function(elementid, agentid, date,moment,typeconvention)
	{
            var element = document.getElementById(elementid);
            var identiteagent = element.closest(".ligneplanning").firstChild.innerText;
            var tableau = element.closest("table");

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
            
            // alert ('Matin couleur = ' + matin.bgColor);
            // alert ('Après-midi couleur = ' + apresmidi.bgColor);
            
            if ((matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>')) 
             && (apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>')))
            {
                // alert ('Matin et après-midi sont du télétravail');
                deplacement = 'jour';
            }
            else if (matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || matin.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>'))
            {
                // alert ('Matin est du télétravail mais pas après-midi');
                deplacement = '<?php echo fonctions::MOMENT_MATIN; ?>';
            }
            else if (apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL); ?>') || apresmidi.classList.contains('<?php echo trim(planningelement::HTML_CLASS_TELETRAVAIL_HIDDEN); ?>'))
            {
                // alert ('Après-midi est du télétravail mais pas matin');
                deplacement = '<?php echo fonctions::MOMENT_APRESMIDI; ?>';
            }
            else
            {
                // alert ('Ni le matin, ni l\'après-midi n\'est un jour de télétravail => Problème');
                return;
            }
            
            if (tableau.classList.contains('<?php echo planningelement::JAVA_CLASS_TELETRAVAIL_HIDDEN; ?>'))
            {
                // Si la classe teletravail_hidden est définie dans le tableau => On ne peut pas modifier une journée de télétravail
                //alert ('L\'affichage du télétravail est désactivé.');
                //return;
                if (typeof reportdialog.showModal === "function") {
                    labeltext.innerHTML = 'Impossible de déplacer ou d\'annuler un jour de télétravail car l\'affichage du télétravail est désactivé.';
                    selectEl.hidden = true;
                    cancelBtn.textContent = "Ok";
                    confirmBtn.hidden = true;
                    reportdialog.showModal();
                }        
            }
/*            
*            /////////////////////////////////////////////////////////////
*            // Il est maintenant autorisé de déplacer des jours de télétravail sur convention médical
*            /////////////////////////////////////////////////////////////
*            else if (typeconvention.toString()==='<?php echo teletravail::CODE_CONVENTION_MEDICAL  ?>')
*            {
*                //alert ('Impossible de déplacer ou d\'annuler un jour de télétravail sur convention médicale.');
*                //return;
*                if (typeof reportdialog.showModal === "function") {
*                    labeltext.innerHTML = 'Impossible de déplacer ou d\'annuler un jour de télétravail sur convention médicale.';
*                    selectEl.hidden = true;
*                    cancelBtn.textContent = "Ok";
*                    confirmBtn.hidden = true;
*                    reportdialog.showModal();
*                }        
*            }
*/
            else if (element.bgColor == '<?php echo $couleur ?>') // C'est un teletravail à annuler/déplacer
            {
                <?php
                $reportteletravail = 'n';
                $constantename = 'REPORTTELETRAVAIL';
                if ($fonctions->testexistdbconstante($constantename))
                {
                    $reportteletravail = $fonctions->liredbconstante($constantename);
                }
                if (strcasecmp($reportteletravail, "o") == 0) // Si on active le report du télétravail
                {
                ?>
                    if (typeof reportdialog.showModal === "function") {
                        if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                        {
                            labeltext.innerHTML = 'Que souhaitez vous faire de la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + '<br><br>Action à réaliser :';
                        }
                        else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                        {
                            labeltext.innerHTML = 'Que souhaitez vous faire de la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + '<br><br>Action à réaliser :';
                        }
                        else
                        {
                            labeltext.innerHTML = 'Que souhaitez vous faire de la journée de télétravail du ' + date + ' pour l\'agent ' + identiteagent + '<br><br>Action à réaliser :';
                        }
                        selectEl.hidden = false;
                        cancelBtn.textContent = "Annuler";
                        confirmBtn.hidden = false;
                        
                        var input = document.getElementById('date_selected');
                        input.value = date;
                        var input = document.getElementById('moment_selected');
                        input.value = moment;
                        var input = document.getElementById('agentid_selected');
                        input.value = agentid;
                        var input = document.getElementById('action');
                        input.value = 'desactive';
                        var input = document.getElementById('typeconvention');
                        input.value = typeconvention;

                        for (cpt=(selectEl.options.length-1) ; cpt>=0 ; cpt--)
                        {
                            if (selectEl.item(cpt).value!=='')
                            {
                                selectEl.remove(cpt);
                            }
                        }
                        // alert('date = ' + date);
                        var jrs="dimanche,lundi,mardi,mercredi,jeudi,vendredi,samedi".split(",");
                        // On calcule la date du lundi de la semaine courante
                        
                        var elementdate = date.split('/'); 
                        var currentdate = new Date(elementdate[2], elementdate[1]-1, elementdate[0]);  // on fourni le format YYYY, MM, DD !! Le mois de janvier est 0
                        //alert('currentdate = ' + currentdate.toLocaleDateString());
                        var dateref = new Date(currentdate.getFullYear(), currentdate.getMonth(),currentdate.getDate()-(currentdate.getDay()-1));
                        // dateref correspond au lundi de la semaine courante
                        //alert('dateref (Lundi de la semaine) = ' + dateref.toLocaleDateString());
                        for (cpt=1 ; cpt <= 7 ; cpt++)
                        {
                            var frenchdate = dateref.getDate().toString().padStart(2, '0') + '/' + (dateref.getMonth()+1).toString().padStart(2, '0') + '/' + dateref.getFullYear();
                            // alert(frenchdate);
                            //alert('On traitre le ' + dateref + ' et frenchdate = ' + frenchdate);
                            if (dateref.getDay()>0 && dateref.getDay()<6)
                            {
                                if (deplacement==='jour')
                                {
                                    if (frenchdate.toString()!==date.toString())
                                    {
                                        var newoption = document.createElement("option");
                                        newoption.value = frenchdate + '_all';
                                        newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate;
                                        selectEl.add(newoption, null);
                                    }
                                }
                                else
                                {
                                    if (frenchdate.toString()!==date.toString() || (frenchdate.toString()===date.toString() && deplacement !== '<?php echo fonctions::MOMENT_MATIN; ?>'))
                                    {
                                        var newoption = document.createElement("option");
                                        newoption.value = frenchdate + '_' + '<?php echo fonctions::MOMENT_MATIN; // echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?>';
                                        newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?>';
                                        selectEl.add(newoption, null);
                                    }
                                    if (frenchdate.toString()!==date.toString() || (frenchdate.toString()===date.toString() && deplacement !== '<?php echo fonctions::MOMENT_APRESMIDI; ?>'))
                                    {
                                        var newoption = document.createElement("option");
                                        newoption.value = frenchdate + '_' + '<?php echo fonctions::MOMENT_APRESMIDI; //echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?>';
                                        newoption.text = "Reporter au " + jrs[dateref.getDay()] + " " + frenchdate + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?>';
                                        selectEl.add(newoption, null);
                                    }
                                }
                            }
                            else  // On est un samedi ou un dimanche 
                            {
                                break; // On sort de la boucle (car report uniquement sur la semaine en cours)
                            }
                            //alert('frenchdate (avant le suivant)= ' + frenchdate);
                            var elementdate = frenchdate.split('/');
                            var currentdate = new Date(elementdate[2], elementdate[1]-1, elementdate[0]);  // on fourni le format YYYY, MM, DD !! Le mois de janvier est 0
                            var dateref = new Date(currentdate.getFullYear(), currentdate.getMonth(),currentdate.getDate()+1);
                            // dateref correspond au jour suivant
                            //alert('dateref (<=> jour suivant) = ' + dateref.toLocaleDateString());
                        }
                        reportdialog.showModal();
                    } else {
                        console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
                    }
                <?php
                }
                else
                {
                ?>
                    if (typeof reportdialog.showModal === "function") {
                        if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                        {
                            labeltext.innerHTML = 'Supprimer la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                        {
                            labeltext.innerHTML = 'Supprimer la demie-journée de télétravail du ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else
                        {
                            labeltext.innerHTML = 'Supprimer la journée de télétravail du ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                        }
                        selectEl.hidden = true;
                        cancelBtn.textContent = "Annuler";
                        confirmBtn.hidden = false;
                        
                        var input = document.getElementById('date_selected');
                        input.value = date;
                        var input = document.getElementById('moment_selected');
                        input.value = moment;
                        var input = document.getElementById('agentid_selected');
                        input.value = agentid;
                        var input = document.getElementById('action');
                        input.value = 'desactive';
                        var input = document.getElementById('typeconvention');
                        input.value = typeconvention;
                        reportdialog.showModal();
                    } else {
                        console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
                    }
                <?php
                }
                ?>
            }
            else if (element.bgColor == '<?php echo planningelement::COULEUR_VIDE ?>') // C'est un teletravail déjà annulé
            {
                    if (typeof reportdialog.showModal === "function") {
                        if (deplacement === '<?php echo fonctions::MOMENT_MATIN; ?>')
                        {
                            labeltext.innerHTML = 'Réactiver le télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_MATIN); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else if (deplacement === '<?php echo fonctions::MOMENT_APRESMIDI; ?>')
                        {
                            labeltext.innerHTML = 'Réactiver le télétravail de la demie-journée du : ' + date + ' <?php echo $fonctions->nommoment(fonctions::MOMENT_APRESMIDI); ?> pour l\'agent ' + identiteagent + ' ?';
                        }
                        else
                        {
                            labeltext.innerHTML = 'Réactiver le télétravail de la journée du : ' + date + ' pour l\'agent ' + identiteagent + ' ?';
                        }
                        selectEl.hidden = true;
                        
                        var input = document.getElementById('date_selected');
                        input.value = date;
                        var input = document.getElementById('moment_selected');
                        input.value = moment;
                        var input = document.getElementById('agentid_selected');
                        input.value = agentid;
                        var input = document.getElementById('action');
                        input.value = 'reactive';
                        var input = document.getElementById('typeconvention');
                        input.value = typeconvention;
                        reportdialog.showModal();
                    } else {
                        console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
                    }
            }
	};


</script>

<?php 

    echo "<form name='select_mois' id='select_mois' method='post'>";
    echo "<center><select name='indexmois'>";

    // On reprend le mois de début de période
    $index = $moisdebutperiode;
    // L'année c'est l'année de référence
    $anneemois = $fonctions->anneeref() - $previous;
    // echo "index = $index <br>";
    for ($indexcpt = 1; $indexcpt <= 12; $indexcpt ++) {
        echo "<option value='$index'";
        if ($index == $indexmois)
            echo " selected ";
        echo ">" . $fonctions->nommois("01/" . str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . date("Y")) . "  " . $anneemois . "</option>";
        // On calcule le modulo
        $index = ($index % 12) + 1;
        // Si le mois est > 12 ou égal à 1 alors c'est qu'on est passé à l'année suivante
        if ($index > 12 or $index == 1)
            $anneemois = $anneemois + 1;
    }

    echo "</select>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
    echo "<input type='hidden' name='date_selected' id='date_selected' value='' />";
    echo "<input type='hidden' name='moment_selected' id='moment_selected' value='' />";
    echo "<input type='hidden' name='agentid_selected' id='agentid_selected' value='' />";
    echo "<input type='hidden' name='report_date' id='report_date' value='' />";
    echo "<input type='hidden' name='report_moment' id='report_moment' value='' />";
    echo "<input type='hidden' name='typeconvention' id='typeconvention' value='' />";
    echo "<input type='hidden' name='action' id='action' value='' />";
    echo "<input type='submit' value='Soumettre' /></center>";
    echo "</form>";
    
    if (strcasecmp($mode, "resp") == 0) 
    {
        $structureliste = $user->structrespliste();
        foreach ($structureliste as $structkey => $structure) 
        {
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
            {
                // echo "structureid = $structureid    structure->id() = " . $structure->id() . "   rootstruct = $rootstruct <br>";
                if ($structureid == $structure->id() and $rootstruct <> '')
                {
                    unset($structureliste["$structkey"]);
                    $structure = $structure->structureenglobante();
                }
                $structureliste = array_merge($structureliste, array($structure->id() => $structure));
                // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
            } 
            else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        
        foreach ($structureliste as $structkey => $structure) 
        {
            // Vérification que la structure n'est pas fermée => En théorie c'est déjà fait avant donc ne sert à rien
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                echo "<br>";
                //echo "Le code de la structure : " . $structure->id() . "<br>";
                if ($structure->responsable()->agentid() == $user->agentid())
                {
                    $planninggris = false;
                }
                else
                {
                    $planninggris = true;
                }
                
                
                $planninghtml = $structure->planninghtml($indexmois . "/" . $annee,'o',$planninggris,true,true);
                echo $planninghtml;
                
                //$structparent = $structure->structureenglobante();
                if ($structure->responsable()->agentid() == $user->agentid() and !$structure->isincluded() and trim($planninghtml) != "")
                {
                    echo "<br>";
                                        
                    echo "<form name='form_teletravailPDF' id='form_teletravailPDF' method='post' action='affiche_pdf.php' target='_blank'>";
                    echo "<input type='hidden' name='indexmois' value='" . $indexmois  . "' />";
                    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
                    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
                    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
                    echo "<input type='hidden' name='structureid' value='" . $structure->id() .  "' />";
                    echo "<input type='hidden' name='datedebut' value='" . (date('Y')-1) . '1001' . "' />"; // Date de début du dernier trimestre de l'année d'avant
                    echo "<input type='hidden' name='datefin' value='" . (date('Y')-1) . '1231' .  "' />";  // Date de fin du dernier trimestre de l'année d'avant
                    
                    //echo "Afficher le document 'télétravail' pour la structure " . $structure->nomlong() . " (du " . $fonctions->formatdate($datedebut) . " au " . $fonctions->formatdate($datefin)  . ")<br>";
                    echo "Afficher le document 'télétravail' pour la structure " . $structure->nomlong() . " (" . $structure->nomcourt() . ")<br>";
                    echo "<input type='submit' name='teletravailPDF' id='teletravailPDF' value='Afficher un PDF'/>";
                    echo "</form>";

                    echo "<form name='form_teletravailmail' id='form_teletravailmail' method='post'>";
                    echo "<input type='hidden' name='indexmois' value='" . $indexmois  . "' />";
                    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
                    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
                    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
                    echo "<input type='hidden' name='structureid' value='" . $structure->id() .  "' />";
                    echo "<input type='hidden' name='datedebut' value='" . (date('Y')-1) . '1001' . "' />"; // Date de début du dernier trimestre de l'année d'avant
                    echo "<input type='hidden' name='datefin' value='" . (date('Y')-1) . '1231' .  "' />";  // Date de fin du dernier trimestre de l'année d'avant
                    
                    //echo "Envoyer par mail le document 'télétravail' pour la structure " . $structure->nomlong() . " (du " . $fonctions->formatdate($datedebut) . " au " . $fonctions->formatdate($datefin)  . ")<br>";
                    echo "Envoyer par mail le document 'télétravail' pour la structure " . $structure->nomlong() . " (" . $structure->nomcourt() . ")<br>";
                    echo "<input type='submit' name='teletravailmail' id='teletravailmail' value='Envoyer un PDF'/>";
                    echo "</form>";
                }
            }
        }
        
        
        
/*
        $structincluelist = $fonctions->listestructurenoninclue();
        echo "Liste des id de structures non inclue :" ;
        var_dump($structincluelist);
        echo "<br>";
*/
    } elseif (strcasecmp($mode, "gestion") == 0) {
        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure)
        {
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                //echo "structureid = $structureid    structure->id() = " . $structure->id() . "   rootstruct = $rootstruct <br>";
                if ($structureid == $structure->id() and $rootstruct <> '')
                {
                    unset($structureliste["$structkey"]);
                    $structure = $structure->structureenglobante();
                }
                $structureliste = array_merge($structureliste, array($structure->id() => $structure));
                // Remarque : Le tableau ne contiendra pas de doublon, car la clé est le code de la structure !!!
            }
            else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "<br>StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure)
        {
            // Vérification que la structure n'est pas fermée => En théorie c'est déjà fait avant donc ne sert à rien
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))
            {
                echo "<br>";
                //echo "Le code de la structure : " . $structure->id() . "<br>";
                if ($structure->gestionnaire()->agentid() == $user->agentid())
                {
                    $planninggris = false;
                }
                else
                {
                    $planninggris = true;
                }
                $planninghtml = $structure->planninghtml($indexmois . "/" . $annee,'o',$planninggris,true,true);
                echo $planninghtml;
                $structparent = $structure->structureenglobante();

/*                
                if (trim($planninghtml) != "" and $structkey <> $structparent->id())
                {
                    // On ajoute la checkbox pour afficher tous les agents de la structure "racine"
                    echo "<br>";
                    echo "<form name='form_showroot' id='form_showroot' method='post'>";
                    echo "<input type='hidden' name='indexmois' value='" . $indexmois  . "' />";
                    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
                    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
                    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
                    echo "<input type='hidden' name='rootid' value='" . $structparent->id() .  "' />";
                    echo "<input type='hidden' name='structureid' value='" . $structure->id() .  "' />";
                    
                    echo "<input type='checkbox' id='check_showroot' name='check_showroot' onclick='this.form.submit()' ";
                    if ($check_showroot == 'on' and $structureid == $structkey)
                        echo " checked ";
                    echo "/>";
                    echo "Voir le planning de la structure \"racine\" => " . $structparent->nomcourt();
                    echo "</form>";
                }
*/
            }
        }
            
/*        
        
        
        
        foreach ($structureliste as $structkey => $structure) {
            // Si la structure est ouverte => On la garde
            if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                if (strcasecmp($structure->sousstructure(), "o") == 0) {
                    $sousstructliste = $structure->structurefille();
                    foreach ((array) $sousstructliste as $key => $struct) {
                        // Si la structure est fermée.... On la supprime de la liste
                        if ($fonctions->formatdatedb($struct->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                            // echo "Index = " . array_search($struct, $sousstructliste) . " Key = " . $key . "<br>";
                            // echo "<br>sousstructliste AVANT = "; print_r($sousstructliste); echo "<br>";
                            unset($sousstructliste["$key"]);
                            // echo "<br>sousstructliste APRES = "; print_r($sousstructliste); echo "<br>";
                        }
                    }
                    $structureliste = array_merge($structureliste, (array) $sousstructliste);
                    // Remarque : Le tableau ne contiendra pas de doublon, car la clÃ© est le code de la structure !!!
                }
            } else // La strcuture est fermée... Donc on la supprime de la liste.
            {
                // echo " structkey = " . $structkey . "<br>";
                unset($structureliste["$structkey"]);
            }
        }
        // echo "StructureListe = "; print_r($structureliste); echo "<br>";
        foreach ($structureliste as $structkey => $structure) {
            echo "<br>";
            echo $structure->planninghtml($indexmois . "/" . $annee,null,false,true);
        }

        $structureliste = $user->structgestliste();
        foreach ($structureliste as $structkey => $structure) {
            if (strcasecmp($structure->afficherespsousstruct(), "o") == 0) {
                echo "<br>";
                echo $structure->planningresponsablesousstructhtml($indexmois . "/" . $annee,true);
            }
        }
*/
    } 
    else 
    {
/*       

        $affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));        
        foreach ($affectationliste as $affectkey => $affectation) 
        {
*/            
        $affstructureid = $user->structureid();
        if ($affstructureid . "" != "")
        {
            $structure = new structure($dbcon);
            $structure->load($affstructureid);
            $showsousstruct = 'n';
            if (strcasecmp($structure->affichetoutagent(), "o") == 0)
            {
                // Rappel : 
                //      structureid => Id de la structure d'affectation de l'agent (récupéré du POST)
                //      affstructureid => Id de la structure d'affectation de l'agent
                //      rootstruct => Id de la strucuture racine
                //echo "structureid = $structureid    affstructureid = $affstructureid   rootstruct = $rootstruct <br>";
                // Si on a coché la case 'voir la structure root  et si rootstruct <> '' ==> On veut afficher la structure Root
                if ($rootstruct <> '' and $check_showroot == 'on')
                {
                    unset($structure);
                    $structure = new structure($dbcon);
                    $structure->load($rootstruct);
                    $showsousstruct = 'o';
                }
                
                echo "<br>";
                // echo "Planning de la structure : " . $structure->nomlong() . " (" . $structure->nomcourt() . ") <br>";
                $planninghtml =  $structure->planninghtml($indexmois . "/" . $annee, $showsousstruct, true,true); // 'n' => l'agent ne doit pas voir les conges des sous-structures (si autorisé) + Pas de télétravail sinon visuellement c'est trompeur
                echo $planninghtml;
                $structparent = $structure->structureenglobante();
                
                if (trim($planninghtml) != "") // and $structure->id() <> $rootstruct)
                {
                    // On ajoute la checkbox pour afficher tous les agents de la structure "racine"
                    echo "<br>";
                    echo "<form name='form_showroot' id='form_showroot' method='post'>";
                    echo "<input type='hidden' name='indexmois' value='" . $indexmois  . "' />";
                    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "' />";
                    echo "<input type='hidden' name='mode' value='" . $mode . "' />";
                    echo "<input type='hidden' name='previous' value='" . $previoustxt . "' />";
                    echo "<input type='hidden' name='rootid' value='" . $structparent->id() .  "' />";
                    echo "<input type='hidden' name='structureid' value='" . $affstructureid .  "' />";
                    
                    echo "<input type='checkbox' id='check_showroot' name='check_showroot' onclick='this.form.submit()' ";
                    if ($check_showroot == 'on')
                        echo " checked ";
                    echo "/>";
                    echo "Voir l'intégralité du planning de la structure \"racine\" => " . $structparent->nomcourt();
                    echo "</form>";
                }
            }
        }
    }

    unset($strucuture);
?>

</body>
</html>