<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.8" />

<?php    
    $WSGROUPURL = $fonctions->liredbconstante("WSGROUPURL");
    // echo "<br><br>WSGROUPURL = $WSGROUPURL <br>";
?>
<title>G2T
<?php 
    if (defined('TYPE_ENVIRONNEMENT') and strcasecmp(TYPE_ENVIRONNEMENT,'PROD')!=0)
    { 
        echo " - " . strtoupper(TYPE_ENVIRONNEMENT); 
    }

    if (isset($user) and (is_a($user, 'agent')) and ($user->agentid()."" <> "")) 
    { 
        echo " - " . $user->identitecomplete(); 
    } 
?></title>

<!-----------------------------------
-- JQuery-ui CSS local
------------------------------------->
<link rel="stylesheet" href="jquery-ui/jquery-ui.css?<?php echo filemtime('jquery-ui/jquery-ui.css') ?>" type="text/css" media="all"></link>
<!-----------------------------------
-- JQuery-ui CSS de l'établissement
<link rel="stylesheet" href="<?php echo "$WSGROUPURL" ?>/web-widget/jquery-ui.css" type="text/css" media="all"></link>
<link rel="stylesheet" href="<?php echo "$WSGROUPURL" ?>/web-widget/ui.theme.css" type="text/css" media="all"></link>
------------------------------------->
<!-----------------------------------
-- JQuery-ui JS local
------------------------------------->
<script src="jquery-ui/jquery.js"></script>
<script src="jquery-ui/jquery-ui.js"></script>

<!-----------------------------------
-- AutocompleteUser CSS + JS de l'établissement
------------------------------------->
<script src="<?php echo "$WSGROUPURL"?>/web-widget/kraaden.github.io-autocomplete.js"></script>
<link rel="stylesheet" href="<?php echo "$WSGROUPURL"?>/web-widget/autocompleteUser.css" type="text/css" media="all"></link>
<script src="<?php echo "$WSGROUPURL"?>/web-widget/autocompleteUser.js"></script>

<!-----------------------
<script type="text/javascript">
    function montre(id)
    {
        var d = document.getElementById(id);
        for (var i = 1; i<=10; i++)
        {
            if (document.getElementById('smenuprincipal'+i))
            {
                document.getElementById('smenuprincipal'+i).style.display='none';
            }
        }
        if (d)
        {
            d.style.display='block';
        }
    }

    function cache(id, e)
    {
        var toEl;
        var d = document.getElementById(id);
        if (window.event)
            toEl = window.event.toElement;
        else if (e.relatedTarget)
            toEl = e.relatedTarget;
        if ( d != toEl && !estcontenupar(toEl, d) )
            d.style.display="none";
    }

// retourne true si oNode est contenu par oCont (conteneur)
    function estcontenupar(oNode, oCont)
    {
        if (!oNode)
            return; // ignore les alt-tab lors du hovering (empêche les erreurs)
        while ( oNode.parentNode )
        {
            oNode = oNode.parentNode;
            if ( oNode == oCont )
                return true;
        }
        return false;
    }

/* Demande d'affichage d'une fenetre au niveau du front office */
    function ouvrirFenetrePlan(url, nom) 
    {
        window.open(url, nom, "width=520,height=500,scrollbars=yes, status=yes");
    }

</script>
----------------------------->
<script type="text/javascript">window.bandeau_ENT={current:'g2t'};</script>
<script type="text/javascript" src="https://esup-data.univ-paris1.fr/esup/outils/postMessage-resize-iframe-in-parent.js"></script>

<!-------------------------------------------
<script src="javascripts/jquery-1.8.3.js"></script>
<script src="javascripts/jquery-ui.js"></script>
-------------------------------------------->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#structureid').select2();
        $('#esignatureid').select2();
        $('.listeagentg2t').select2();
        $('.selectstructure').select2();

        // On force le focus sur l'input de recherche lorsqu'on clique sur l'ouverture de la combo d'un select2
        var select2containerlist = document.getElementsByClassName("select2-container");
        // console.log(select2containerlist);
        if (select2containerlist)
        {
            for (let index = 0; index < select2containerlist.length; index++)
            {
                let select2container = select2containerlist[index];
                // console.log(select2container.classList);
                select2container.addEventListener('click',select2setfocus);
            }
        }
    
    });

    function select2setfocus()
    {
        // console.log('select2setfocus inside');
        var inputselect2list = document.getElementsByClassName("select2-search__field");
        if (inputselect2list)
        {
            var inputselect2 = inputselect2list[0];
            if (inputselect2)
            {
                inputselect2.focus();
            }
        }
    }

</script>

<script>

    var completionAgent = function (event, ui)
    {
        // NB: this event is called before the selected value is set in the "input"
        var form = $(this).closest("form");
        var selectedInput = document.activeElement;
        form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
        form.find("[class='" + selectedInput.name + "']").val (ui.item.value);
        return false;
    };
    
<?php 
    if (is_null($user) or is_null($user->agentid())) {
        echo "PROBLEME : L'utilisateur n'est pas renseigné ==> objet \$user!!!! <br>";
        exit();
    }

    $planningelement = new planningelement($dbcon);
    $planningelement->type('teletrav');
    $couleur = $planningelement->couleur();
    //echo "couleur = $couleur <br>";
    
    // On récupère le libellé du télétravail
    $libelleteletrav = 'Télétravail';
    if (defined('TABCOULEURPLANNINGELEMENT'))
    {
        if (isset(TABCOULEURPLANNINGELEMENT['teletrav']['libelle']))
        {
            $libelleteletrav = TABCOULEURPLANNINGELEMENT['teletrav']['libelle'];
            //echo "<br>libelleteletrav est dans le tableau <br>";
        }
    }
?>

    var hide_teletravail = function (nomtableau, id_hidden_input ="")
    {
        //alert ('Plouf !');
        var tableau = document.getElementById(nomtableau);
        //alert (tableau.id);
    	var checkboxvalue = document.activeElement.checked;
        for (var indexcellule = 0; indexcellule < tableau.querySelectorAll('.teletravail').length; indexcellule++)
        {
            //alert(indexcellule);
            var currenttd = tableau.querySelectorAll('.teletravail')[indexcellule];

            if (checkboxvalue || currenttd.classList.contains('<?php 
               // ATTENTION : On TRIM la classe exclusion car il ne faut pas les espaces quand on vérifie si la classe est là
               echo trim(planningelement::HTML_CLASS_EXCLUSION); 
            ?>'))  // Soit on a demander à le masquer, soit c'est une date exclue (<=> classe exclusion)
            {
                //alert('Suppression de la couleur');
                // C'est du télétravail et on doit le masquer ou la date est exclue
                currenttd.bgColor = '<?php echo planningelement::COULEUR_VIDE ?>';
                // On ajoute la classe hidde_tip afin de masquer la bulle d'information => voir CSS
                //currenttd.classList.add('hidde_tip');
                if (currenttd.getElementsByTagName('span').length>0)
                { 
                    var currentspan = currenttd.getElementsByTagName('span')[0];
                    currentspan.classList.add('remove-teletravail');
                    var datatip = currentspan.getAttribute('data-tip');
                    currentspan.setAttribute('data-svg',datatip);
                    datatip = datatip.split(':')[0].trim();
                    // alert ('Span data-tip = ' + currentspan.getAttribute('data-tip'));
                    currentspan.setAttribute('data-tip',datatip);
                }
            }
            else
            {
                //alert('On remet la couleur');
                // C'est du télétravail et on doit le montrer
                currenttd.bgColor = '<?php echo "$couleur"  ?>';
                // On supprime la classe hidde_tip afin d'autoriser l'affichage de la bulle d'information => voir CSS
                //currenttd.classList.remove('hidde_tip');
                if (currenttd.getElementsByTagName('span').length>0)
                { 
                    var currentspan = currenttd.getElementsByTagName('span')[0];
                    currentspan.classList.remove('remove-teletravail');
                    //alert ('Span HTML = ' + currentspan.data-tip);
                    var datatip = currentspan.getAttribute('data-tip');
                    var datasvg = currentspan.getAttribute('data-svg');
                    if (datasvg.toString!=='')
                    {
                        currentspan.setAttribute('data-tip',datasvg);
                    }
                    else  // Pas de sauvegarde du data_tip
                    {
                        datatip = datatip.split(':')[0].trim();
                        datatip = datatip.concat(' : <?php echo "$libelleteletrav";  ?>'); 
                        // alert ('Span data-tip = ' + currentspan.getAttribute('data-tip'));
                        currentspan.setAttribute('data-tip',datatip);
                    }
                    currentspan.removeAttribute('data-svg');
                }
            }    
        }
        if (id_hidden_input != "")
        {
            var hidden_input = document.getElementById(id_hidden_input);
            if (checkboxvalue)
            {
                    hidden_input.value='on';
            }
            else
            {
                    hidden_input.value='off';
            }
        }
        if (checkboxvalue)
        {
            tableau.classList.add('<?php echo planningelement::JAVA_CLASS_TELETRAVAIL_HIDDEN; ?>');
        }
        else
        {
            tableau.classList.remove('<?php echo planningelement::JAVA_CLASS_TELETRAVAIL_HIDDEN; ?>');
        }
    };

    var demandestatutchange = function (select, index)
    {
    	//alert('Index = ' + index);
        const motifinput = document.getElementById('motif[' +  index + ']');
        //alert('Motif id = ' + motifinput.id);
        validdemandemotif(motifinput,index);
    };
	
    var validdemandemotif = function (motif, index)
    {
        const select = document.getElementById('statut[' +  index + ']');
        //alert ('Select id = ' + select.id);
        if (select.value == '<?php echo demande::DEMANDE_REFUSE; ?>')
        {
            //alert ('Select value = ' + select.value);
            motif.disabled = false;
            //alert ('checked');
            if (motif.value == '')
            {
                motif.style.backgroundColor = '#f5b7b1';
            }
            else
            {
                motif.style.backgroundColor = '';
            }
        }
        else
        {
            motif.disabled = true;
            //alert ('no checked');
            motif.style.backgroundColor = '';
        }
    };

    const getCellValue = (tr, idx) =>
    {
        // Si on a un time dans le td, alors on trie sur l'attribut datetime
        // ==> utilisé dans le tri des demandes dans l'écran d'annulation d'une demande
        if (tr.children[idx].querySelector('time')!==null) 
        {
            return tr.children[idx].querySelector('time').getAttribute('datetime');
        }
        // Si on a un element de class 'agentidentite' dans le td, alors on trie sur l'attribut 'agentidentite'
        // ==> utilisé dans le tri des agents dans le planning d'une strucuture
        else if (tr.children[idx].querySelector('.agentidentite')!==null) 
        {
            //alert ('InnerText = ' + tr.children[idx].querySelector('.agentidentite').innerText);
            return tr.children[idx].querySelector('.agentidentite').innerText;
        }
/*
        // Si on a un span dans le td, alors on trie sur l'attribut span
        // ==> Non utilisé pour le moment mais conservé comme exemple
        else if (tr.children[idx].querySelector('span')!==null) 
        {
            //alert ('InnerText = ' + tr.children[idx].querySelector('span').innerText);
            return tr.children[idx].querySelector('span').innerText;
        }
*/
        else
        {
            return tr.children[idx].innerText || tr.children[idx].textContent;
        }
    };
                
    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
        )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

</script>

<!-- On rend la CSS "dynamique" en lui passant en paramètre le timestamp Unix de dernière modification du fichier -->
<!-- Donc à chaque changement de CSS, on force le chargement de la nouvelle CSS -->
<link rel="stylesheet" type="text/css" href="css-g2t/g2t.css?<?php echo filemtime('css-g2t/g2t.css') ?>" media="all"></link>
<!------------------------------------
<link rel="stylesheet" type="text/css" 
    href="style/jquery-ui.css?<? php echo filemtime('style/jquery-ui.css')  ?>" media="screen">
</link>
------------------------------->
</head>

<body class="bodyhtml"> 

    <!-- Toutes les informations sur la boite de dialogue personnalisée en HTML --> 
    <!-- sont sur le lien https://developer.mozilla.org/fr/docs/Web/HTML/Element/dialog -->

    <dialog id="warningdialog" class="warningdialog">
        <form method="dialog">
            <p>
<?php
                $type = 'warning';
                $path = $fonctions->imagepath() . "/" . $type . "_logo.png";
                $typeimage = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
                echo "<img class='img". $type ." imagedialog' src='" . $base64 . "'>&nbsp;"; 
?>
                <label id='warninglabeltext'>Votre texte ne doit pas dépasser XXXX caractères! :</label>
            </p>
            <menu>
                <center>
                    <button value="cancel" class='javaokbutton'>Ok</button>
                </center>
            </menu>
        </form>
    </dialog> 
        
    <script>
        const warningdialog = document.getElementById('warningdialog');

        warningdialog.addEventListener('close', function onClosewarning() 
        {
            if (warningdialog.returnValue==='cancel')
            {
                return false;
            }
        });
    </script>

    <dialog id="confirmdialog" class="questiondialog">
        <form method="dialog">
            <p>
<?php
                $type = 'question';
                $path = $fonctions->imagepath() . "/" . $type . "_logo.png";
                $typeimage = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
                echo "<img class='img". $type ." imagedialog' src='" . $base64 . "'>&nbsp;"; 
?>
                <label id='questionlabeltext'>Confirmez vous cette action ?</label>
            </p>
            <menu>
                <center>
                    <button id="questionconfirmBtn" value="" class='javaconfirmbutton'>Ok</button>
                    <button id="questioncancelBtn" value="cancel" class='javacancelbutton'>Annuler</button>
                </center>
            </menu>
        </form>
    </dialog>

    <dialog id="reportdialog" class="questiondialog">
        <form method="dialog">
            <p>
<?php
                $type = 'question';
                $path = $fonctions->imagepath() . "/" . $type . "_logo.png";
                $typeimage = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
                echo "<img class='img". $type ." imagedialog' src='" . $base64 . "'>&nbsp;";
?>
                <label id='reportlabeltext'>Action à réaliser :</label>
                <select id='reportchoice' hidden='hidden'>
                    <option value=''>Ne pas reporter</option>
                </select>
            </p>
            <menu>
                <center>
                    <button id="reportconfirmBtn" value="" class='javaconfirmbutton'>Ok</button>
                    <button id="reportcancelBtn" value="cancel" class='javacancelbutton'>Annuler</button>
                </center>
            </menu>
        </form>
    </dialog>
    
    <script>

        var calculateContentHeight = function( ta, scanAmount ) {
            var origHeight = ta.style.height,
                height = ta.offsetHeight,
                scrollHeight = ta.scrollHeight,
                overflow = ta.style.overflow;
            /// only bother if the ta is bigger than content
            if ( height >= scrollHeight ) 
            {
                /// check that our browser supports changing dimension
                /// calculations mid-way through a function call...
                ta.style.height = (height + scanAmount) + 'px';
                /// because the scrollbar can cause calculation problems
                ta.style.overflow = 'hidden';
                /// by checking that scrollHeight has updated
                if ( scrollHeight < ta.scrollHeight ) 
                {
                    /// now try and scan the ta's height downwards
                    /// until scrollHeight becomes larger than height
                    while (ta.offsetHeight >= ta.scrollHeight) 
                    {
                        ta.style.height = (height -= scanAmount)+'px';
                    }
                    /// be more specific to get the exact height
                    while (ta.offsetHeight < ta.scrollHeight) 
                    {
                        ta.style.height = (height++)+'px';
                    }
                }
                /// reset the ta back to it's original height
                ta.style.height = origHeight;
                /// put the overflow back
                ta.style.overflow = overflow;
                return height;
            } 
            else 
            {
                return scrollHeight;
            }
        };

        function calculateHeight(textarea) 
        {
            var ta = textarea;
            style = (window.getComputedStyle) ? window.getComputedStyle(ta) : ta.currentStyle;
            
            //alert('la hauteur : ' + style.lineHeight);

            // This will get the line-height only if it is set in the css,
            // otherwise it's "normal"
            taLineHeight = parseInt(style.lineHeight, 10);
            //alert ('taLineHeight = ' + taLineHeight);
            
            if (isNaN(taLineHeight))
            {
                return -1;
            }
            
            // Get the scroll height of the textarea
            taHeight = calculateContentHeight(ta, taLineHeight);
            // calculate the number of lines
            numberOfLines = Math.ceil(taHeight / taLineHeight);

            return numberOfLines;
        };
    
    
    
        function checktextlength(textarea, maxlength, labelrestantname)
        {
            let warningdialog = document.getElementById('warningdialog');
            let warninglabeltext = warningdialog.querySelector('#warninglabeltext'); // document.getElementById('warninglabeltext');
            let labelrestanttext = document.getElementById(labelrestantname);
            if (textarea.value.length > maxlength) 
            {
                let position = textarea.selectionStart;
                let texte = textarea.value;
                
                //alert ('position = ' + position);
                textarea.value = texte.substr(0, position - 1) + texte.substr(position, texte.length);
                textarea.selectionStart = position-1;
                textarea.selectionEnd = position-1;

                if (labelrestanttext)
                {
                    labelrestanttext.innerHTML = 0;
                }
                if (warningdialog!=null &&  typeof warningdialog.showModal === "function") 
                {
                    warninglabeltext.innerHTML = 'Votre texte ne doit pas dépasser '+maxlength+' caractères!';
                    warningdialog.showModal();
                    return false;
                } 
                else
                {
                    alert('Votre texte ne doit pas dépasser '+maxlength+' caractères!');
                    return false;
                }
            }
            if (labelrestanttext)
            {
                labelrestanttext.innerHTML = maxlength - textarea.value.length;
            }

            var style = (window.getComputedStyle) ? window.getComputedStyle(textarea) : textarea.currentStyle;
            //alert('style.height = ' + style.height + '  le parse = ' + parseInt(style.height, 10));
            if (isNaN(parseInt(style.height, 10)))
            {
                //alert('je force la height');
                textarea.style.height = parseInt(style.lineHeight, 10) * parseInt(textarea.getAttribute('rows')) + 'px';
                //alert('et ça vaut : '+ style.height);
            }
            
            var count = calculateHeight(textarea);
            // alert ('count = ' + count);
            if (count < 0)
            {
                var text = textarea.value;   
                var lines = text.split(/\r|\r\n|\n/);
                count = lines.length;
            }
            var maxRows = parseInt(textarea.getAttribute('rows'));
            // alert('maxRows = ' + maxRows);
            if (count > maxRows)
            {
                //alert('Count = '+count+ '  maxRows = '+maxRows);
                let position = textarea.selectionStart;
                let texte = textarea.value;
                
                //alert ('position = ' + position);
                textarea.value = texte.substr(0, position - 1) + texte.substr(position, texte.length);
                textarea.selectionStart = position-1;
                textarea.selectionEnd = position-1;

                if (warningdialog!=null &&  typeof warningdialog.showModal === "function") 
                {
                    warninglabeltext.innerHTML = 'Votre texte ne doit pas contenir plus de ' + maxRows + ' ligne(s).';
                    warningdialog.showModal();
                    return false;
                } 
                else
                {
                    alert('Votre texte ne doit pas contenir plus de ' + maxRows + ' ligne(s).');
                    return false;
                }
            }
            return true;
        };
    </script>

<?php

    function triparprofondeurabsolue($struct1, $struct2)
    {
        if ($struct1->profondeurabsolue()==$struct2->profondeurabsolue())
        {
            return 0;
        }
        return ($struct1->profondeurabsolue() < $struct2->profondeurabsolue()) ? -1 : 1;
    }

    /****************************
     * 
     * @deprecated
     * 
     ****************************/
    function affichestructureliste($structure, $niveau = 0)
    {
        global $dbcon;
        global $structureid;
        global $fonctions;
        global $showall;
        
        trigger_error('Method ' . __METHOD__ . ' is deprecated => use fonctions::afficherlistestructureindentee method instead', E_USER_DEPRECATED);

        // $fonctions = new fonctions($dbcon);
        if ($showall or ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))) {
            echo "<option value='" . $structure->id() . "'";
            if ($structure->id() == $structureid) {
                echo " selected ";
            }
            if ($fonctions->formatdatedb($structure->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                echo " class='redtext' ";
            }
            echo ">";
            //echo str_pad('', strlen('&nbsp;')*4*$structure->profondeurrelative(), '&nbsp;', STR_PAD_LEFT);
            for ($cpt = 0; $cpt < $niveau; $cpt ++) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            
            echo " - " . $structure->nomlong() . " (" . $structure->nomcourt() . ")";
            echo "</option>";
            
            $sousstruclist = $structure->structurefille();
            foreach ((array) $sousstruclist as $keystruct => $soustruct) {
                affichestructureliste($soustruct, $niveau + 1);
            }
        }
    }
    
    // On chrge le "vrai" utilisateur de l'application (Celui du ticket CAS)
    $realuser = new agent($dbcon);
    $realuserid = $fonctions->useridfromCAS($uid);
    if ($realuserid !== false)
    {
        $realuser->load($realuserid);
    }
        
    // On verifie que la personne est dans le groupe G2T du LDAP
    if (!$realuser->isG2tUser())
    {
        $LDAP_GROUP_NAME = $fonctions->liredbconstante("LDAPGROUPNAME");
        
        $errlog = "Vous n'êtes pas autorisé à vous connecter à cette application.";
        $errlog = $errlog . "<br>";
        $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";
        
        echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

        $techlog = "Informations techniques :";
        $techlog = $techlog . "<br><ul>";
        $techlog = $techlog . "<li>Identité de l'utilisateur : " . $realuser->identitecomplete() . " (identifiant = " . $realuser->agentid() . ")</li>";
        $techlog = $techlog . "<li>Groupe LDAP recherché : $LDAP_GROUP_NAME </li>";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($techlog)));
        
        $errlog = "<h3>Plusieurs raisons peuvent être à l'origine de cette limitation d'accès :";
        $errlog = $errlog . "<br><ul>";
        $errlog = $errlog . "<li>Vous êtes affecté à une structure qui n'est pas encore paramétrée pour utiliser G2T.</li>";
        $errlog = $errlog . "<li>Vous êtes un agent BIATSS qui n'a pas/plus d'affectation fonctionnelle dans SIHAM.</li>";
        $errlog = $errlog . "<li>Vous êtes un agent contractuel dont le contrat n'est pas saisi ou renouvelé dans SIHAM.</li>";
        $errlog = $errlog . "<li>Vous êtes un agent hébergé et votre situation administrative n'est plus valide dans SIHAM.</li>";
        $errlog = $errlog . "<li>Vous n'êtes pas/plus personnel de Paris 1 Panthéon-Sorbonne.</li>";
        $errlog = $errlog . "</ul></h3><br>";
        $errlog = $errlog . "<hr>";
        echo $errlog;
        echo $techlog;
        exit();
        
    }
    
    // Si on est en mode "MAINTENANCE"
    $constante = 'MAINTENANCE';
    $maintenance = $fonctions->liredbconstante($constante);
    $constante = 'SYNCHRONISATION';
    $synchro = 'n';
    if ($fonctions->testexistdbconstante($constante))
    {
        $synchro = $fonctions->liredbconstante($constante);
    }
    if (strcasecmp($maintenance, 'n') != 0 or strcasecmp($synchro, 'n') != 0) {
        if ($realuser->estadministrateur()) // Si un administrateur est connecté
        {
            if (strcasecmp($maintenance, 'n') != 0)
            {
                echo "<CENTER><div class='redtext fontsize25' ><B><U>ATTENTION : LE MODE MAINTENANCE EST ACTIV&Eacute; -- APPLICATION EN MAINTENANCE</U></B></div></CENTER><BR>";
            }
            if (strcasecmp($synchro, 'n') != 0)
            {
                echo "<CENTER><div class='redtext fontsize25'><B><U>ATTENTION : LE MODE SYNCHRONISATION EST ACTIV&Eacute; -- APPLICATION EN COURS DE SYNCHRO</U></B></div></CENTER><BR>";
            }
        }
        else // C'est un utilisateur simple => Affichage de la page de maintenance
        {
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("L'utilisateur " . $realuser->identitecomplete() . " a essaye d'acceder a l'environnement alors qu'il est en maintenance."));
            echo "<img width=144 height=79 src='https://ent-data.univ-paris1.fr/esup/canal/maintenance/maintenance.gif' align=left hspace=12>";
            echo "L'application de gestion des congés est en maintenance, elle sera bientôt à nouveau en ligne.<br>Veuillez nous excuser pour la gêne occasionnée.";
            echo "</body></html>";
            exit();
        }
    }

    if (($user->agentid() != $realuser->agentid()) and $realuser->estadministrateur())
    {
        echo "<CENTER><div class='redtext fontsize25'><B><U>ATTENTION : VOUS VOUS &Ecirc;TES SUBSTITU&Eacute; &Agrave; UNE AUTRE PERSONNE</U></B></div>" . $user->identitecomplete() . " (Agent Id = " . $user->agentid() . ")</CENTER><BR>";
    }
    
    $arraystructpartielle = array();
    // $arraystructpartielle = array_merge($arraystructpartielle,array('SC4_3','IU1_3','IU4_3','IU21_4','IU22_4','IU24_4','IU25_4','IU23_4','IU2C_4','IU2D_4'));
    
    // **********************************************************************************
    // Pas de mise en production de cette fonctionnalité pour le moment
    //$arraystructpartielle = array_merge($arraystructpartielle,array('IU3_3','UR028_4','UR02C_4','UR031_4','UR035_4','UR038_4','UR032_4','UR048_4','UR083_4','UR084_4','UR03C_4','UR082_4','UR097_4','UR099_4','UR094_4','UR09A_4','UR09E_4','UR09B_4','UR09C_4','UR09G_4','UR098_4','UR093_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('UR102_4','UR104_4','UR109_4','UR101_4','UR115_4','UR152_4','UR211_4','UR272_4','UR274_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('EDO04_4','EDO08_4','EDO06_4','EDO02_4','EDO09_4','EDO05_4','EDO10_4','EDO03_4'));
    //$arraystructpartielle = array_merge($arraystructpartielle,array('UF04SI_4','UF04_3','UF09_3','UF109_4','UF10_3','UF11T_4','UF11_3','UF21_3','UF27_3','UF27T_4'));
    // **********************************************************************************
    
    // $arraystructpartielle = array_merge($arraystructpartielle,array('DGHA_4'));
    $arraystructpartielle = array_map('strtoupper', $arraystructpartielle);
    
    $affectationarray = $realuser->affectationliste(date("d/m/Y"), date("d/m/Y"));
    $hidemenu = '';
    $structurepartielle = false;
    if (is_array($affectationarray))
    { // S'il y a une affectation
        $affectation = current($affectationarray);
        
        //echo "Code structure = " . $affectation->structureid() . "    Liste structure : " . print_r($arraystructpartielle,true) . "<br><br>";
        
        if (in_array(strtoupper($affectation->structureid()), $arraystructpartielle))
        {
            $structurepartielle = true;
            $hidemenu = " class='hiddenelement' ";
        }
    } 
    if ($structurepartielle == true)
    {
        // L'accès est partiel
    }
    
    unset($arraystructpartielle);
    unset($affectationarray);
    unset($affectation);
    
    $affectationliste = $user->affectationliste(date("Ymd"), date("Ymd"));
    $agentstructure = new structure($dbcon);
    if (is_array($affectationliste)) {
        $affectation = reset($affectationliste);
        $structureid = $affectation->structureid();
        if ($agentstructure->load($structureid) == false)
        {
            $agentstructure->affichetoutagent("n"); // Si impossible de charger la structure => On force la valeur à 'n'
            $agentstructure->estbibliotheque("0");  // Ce n'est pas une bibliothèque par défaut
        }
    } 
    else 
    {
        $agentstructure->affichetoutagent("n");
        $agentstructure->estbibliotheque("0");  // Ce n'est pas une bibliothèque par défaut
    }


?>


<div id="mainmenu">
    <ul class="niveau1">
<!--        <li onclick="">MENU AGENT -->
            <li>MENU AGENT
            <ul class="niveau2">
                <li onclick='document.accueil.submit();'>
                    <form name='accueil' method='post' action="index.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.accueil.submit();">Accueil</a>
                </li>
<?php
    if (!$agentstructure->estbibliotheque())
    {
?>
                <li onclick='document.planning.submit();' <?php echo $hidemenu; ?> >
                    <form name='planning' method='post' action="affiche_planning.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form>
                    <a href="javascript:document.planning.submit();">Planning de l'agent</a>
                </li>
                <li onclick='document.dem_conge.submit();' <?php echo $hidemenu; ?> >
                    <form name='dem_conge' method='post' action="etablir_demande.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="typedemande" value="conges">
                    </form> 
                    <a href="javascript:document.dem_conge.submit();">Saisir une demande de congé</a>
                </li>
                <li onclick='document.dem_absence.submit();'>
                    <form name='dem_absence' method='post' action="etablir_demande.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="typedemande" value="absence">
                    </form>
<!--
                    <a href="javascript:document.dem_absence.submit();">Saisir une demande d'absence ou de télétravail</a>
-->
                    <a href="javascript:document.dem_absence.submit();">Saisir une demande d'absence</a>
                </li>
                <li onclick='document.agentannulation.submit();'>
                    <form name='agentannulation' method='post' action="gestion_demande.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>">
                    </form>
                    <a href="javascript:document.agentannulation.submit();">Annulation de demandes</a>
                </li>
<?php                                    
    }
?>
                <li onclick='document.agent_tpspartiel.submit();'>
                    <form name='agent_tpspartiel' method='post' action="saisir_tpspartiel.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="mode" value="agent">
                    </form>
                    <a href="javascript:document.agent_tpspartiel.submit();">Gestion des temps partiels</a>
                </li>
                <li onclick='document.agent_gest_teletravail.submit();'>
                    <form name='agent_gest_teletravail' method='post' action="gestion_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="mode" value="">
                    </form>
                    <a href="javascript:document.agent_gest_teletravail.submit();">Gestion des conventions de télétravail</a>
                </li>
<?php
    if (strcasecmp($agentstructure->affichetoutagent(), "o") == 0 and !$agentstructure->estbibliotheque()) 
    // if ($user->structure()->affichetoutagent() == "o")
    {
?>
                <li onclick='document.agent_struct_planning.submit();' <?php echo $hidemenu; ?> >
                    <form name='agent_struct_planning' method='post' action="structure_planning.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                        <input type="hidden" name="mode" value="agent">
                        <input type="hidden" name="previous" value="no">
                    </form>
                    <a href="javascript:document.agent_struct_planning.submit();">Planning de la structure</a>
                </li>
<?php
    }
?>	
<?php
    // Si la date limite d'utilisation des reliquats est dépassée on n'affiche pas l'alimentation et le droit d'option sur CET
    //$constante = 'FIN_REPORT';
    //if ($fonctions->testexistdbconstante($constante))
    //{
    //    $res = $fonctions->liredbconstante($constante);
    //    $datereliq = ($fonctions->anneeref()+1).$res;
    //    if (date("Ymd") <= $datereliq) 
    //    {

    $constante = 'DEBUTALIMCET';
    $debutcet = '19000101';
    if ($fonctions->testexistdbconstante($constante))
    {
        $debutcet = $fonctions->liredbconstante($constante);
    }
    $constante = 'FINALIMCET';
    $fincet = '19000101';
    if ($fonctions->testexistdbconstante($constante))
    {
        //$fincet = $fonctions->liredbconstante($constante);
        $fincet = date('Ymd',strtotime('+3 month',strtotime($fonctions->liredbconstante($constante))));
        //var_dump("fincet ALIM = $fincet");
    }
    if (date("Ymd")>=$debutcet and date("Ymd")<=$fincet and !$agentstructure->estbibliotheque())
    {    

?>  
                <li onclick='document.alim_cet.submit();'>
                    <form name='alim_cet' method='post' action="gerer_alimentationCET.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                    </form>
                    <a href="javascript:document.alim_cet.submit();">Alimentation du CET</a>
                </li>
<?php
    }
    $constante = 'DEBUTOPTIONCET';
    $debutcet = '19000101';
    if ($fonctions->testexistdbconstante($constante))
    {
        $debutcet = $fonctions->liredbconstante($constante);
    }
    $constante = 'FINOPTIONCET';
    $fincet = '19000101';
    if ($fonctions->testexistdbconstante($constante))
    {
        //$fincet = $fonctions->liredbconstante($constante);
        $fincet = date('Ymd',strtotime('+3 month',strtotime($fonctions->liredbconstante($constante))));
        //var_dump("fincet OPTION = $fincet");
    }
    if (date("Ymd")>=$debutcet and date("Ymd")<=$fincet and !$agentstructure->estbibliotheque())
    {    

?>
                <li onclick='document.option_cet.submit();'>
                    <form name='option_cet' method='post' action="gerer_optionCET.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                    </form>
                    <a href="javascript:document.option_cet.submit();">Droit d'option sur CET</a>
                </li>
<?php
    }
    $dbconstante = 'URL_G2TMANUEL';
    $urlg2tmanuel = '';
    if ($fonctions->testexistdbconstante($dbconstante)) { $urlg2tmanuel = trim($fonctions->liredbconstante($dbconstante)); }
    if (trim($urlg2tmanuel)!='')
    {
?>
                <li onclick='document.agent_aide.submit();'>
                    <form name='agent_aide' method='get' TARGET=_BLANK action="<?php echo $urlg2tmanuel; ?>">
                    </form> 
                    <a href="javascript:document.agent_aide.submit();">Manuel utilisateur</a>
                </li>
<?php
    }
?>
            </ul>
        </li>
    </ul>
<?php
    if ($user->estresponsable()) 
    {
        $structrespliste = $user->structrespliste();
        $estrespdebibliotheque = true;
        foreach ((array)$structrespliste as $struct)
        {
            if (!$struct->estbibliotheque())
            {
                $estrespdebibliotheque = false;
                break;
            }
        }
?> 
    <ul class="niveau1">
<!--        <li onclick="">MENU RESPONSABLE -->
            <li>MENU RESPONSABLE
            <ul class="niveau2">
<?php
        if (!$estrespdebibliotheque)
        {
?>
                <li onclick='document.resp_parametre.submit();'>
                    <form name='resp_parametre' method='post' action="gestion_dossier.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="action" value="modif"> 
                        <input type="hidden" name="mode" value="resp">
                    </form> 
                    <a href="javascript:document.resp_parametre.submit();">Paramétrage des dossiers et des structures</a>
                </li>
<?php
        }
?>
                <li onclick='document.resp_gest_teletravail.submit();'>
                    <form name='resp_gest_teletravail' method='post' action="gestion_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="mode" value="resp">
                    </form>
                    <a href="javascript:document.resp_gest_teletravail.submit();">Gestion des conventions de télétravail</a>
                </li>
                <li class="plus"><a>Gestion de l'année en cours</a>
                    <ul class="niveau3">
<?php
        if (!$estrespdebibliotheque)
        {
?>
                        <li onclick='document.resp_struct_planning.submit();'>
                            <form name='resp_struct_planning' method='post' action="structure_planning.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp"> <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.resp_struct_planning.submit();">Planning de la structure</a>
                        </li>
                        <li onclick='document.resp_valid_conge.submit();'>
                            <form name='resp_valid_conge' method='post' action="valider_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp"> 
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.resp_valid_conge.submit();">Validation des demandes en attente</a>
                        </li>
                        <li onclick='document.resp_gest_conge.submit();'>
                            <form name='resp_gest_conge' method='post' action="gestion_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.resp_gest_conge.submit();">Annulation de congé ou d'absence</a>
                        </li>
                        <li onclick='document.resp_conge.submit();'>
                            <form name='resp_conge' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges">
                                <input type="hidden" name="previous" value="no">
                            </form>
                            <a href="javascript:document.resp_conge.submit();">Saisir une demande de congé pour un agent</a>
                        </li>
                        <li onclick='document.resp_absence.submit();'>
                            <form name='resp_absence' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="absence"> 
                                <input type="hidden" name="previous" value="no">
                            </form> 
<!--
                            <a href="javascript:document.resp_absence.submit();">Saisir une demande d'absence ou de télétravail pour un agent</a>
-->
                            <a href="javascript:document.resp_absence.submit();">Saisir une demande d'absence pour un agent</a>
                        </li>
                        <li onclick='document.resp_ajout_conge.submit();'>
                            <form name='resp_ajout_conge' method='post' action="ajouter_conges.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="mode" value="resp"> 
                            </form> 
                            <a href="javascript:document.resp_ajout_conge.submit();">Gestion des jours supplémentaires pour un agent</a>
                        </li>
<?php
            // Si on est 6 mois avant la fin de la période ==> On peut saisir des jours par anticipation
            $datetemp = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("-6month", $timestamp)); // On remonte de 6 mois
                                                                        // echo "TimeStamp = " . $datetemp . "<br>";
            if (date("Ymd") > $datetemp) 
            {
?>				
                        <li onclick='document.resp_conge_anticipe.submit();'>
                            <form name='resp_conge_anticipe' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges"> 
                                <input type="hidden" name="congeanticipe" value="yes">
                            </form> 
                            <a href="javascript:document.resp_conge_anticipe.submit();">Saisir une demande de congé par anticipation pour un agent</a>
                        </li>
<?php
            }
        }
?>								
                        <li onclick='document.resp_valid_tpspartiel.submit();'>
                            <form name='resp_valid_tpspartiel' method='post' action="valider_tpspartiel.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp">
                            </form>
                            <a href="javascript:document.resp_valid_tpspartiel.submit();">Validation des temps partiels</a>
                        </li>
                        <li onclick='document.resp_tpspartiel.submit();'>
                            <form name='resp_tpspartiel' method='post' action="saisir_tpspartiel.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp">
                            </form> <a href="javascript:document.resp_tpspartiel.submit();">Saisir le temps partiel pour un agent</a>
                        </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                        <li onclick='document.resp_aff_solde.submit();'>
                            <form name='resp_aff_solde' method='post' action="affiche_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp">
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.resp_aff_solde.submit();">Affichage du solde des agents de la structure</a>
                        </li>
<?php
        }
?>
                    </ul>
                </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                <li class="plus"><a>Gestion de l'année précédente</a>
                    <ul class="niveau3">
                        <li onclick='document.resp_struct_planning_previous.submit();'>
                            <form name='resp_struct_planning_previous' method='post' action="structure_planning.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.resp_struct_planning_previous.submit();">Planning de la structure</a>
                        </li>
                        <li onclick='document.resp_valid_conge_previous.submit();'>
                            <form name='resp_valid_conge_previous' method='post' action="valider_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.resp_valid_conge_previous.submit();">Validation des demandes en attente</a>
                        </li>
                        <li onclick='document.resp_gest_conge_previous.submit();'>
                            <form name='resp_gest_conge_previous' method='post' action="gestion_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.resp_gest_conge_previous.submit();">Annulation de congé ou d'absence</a>
                        </li>
                        <li onclick='document.resp_conge_previous.submit();'>
                            <form name='resp_conge_previous' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.resp_conge_previous.submit();">Saisir une demande de congé pour un agent</a>
                        </li>
                        <li onclick='document.resp_absence_previous.submit();'>
                            <form name='resp_absence_previous' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="absence"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
<!--
                            <a href="javascript:document.resp_absence_previous.submit();">Saisir une demande d'absence ou de télétravail pour un agent</a>
-->
                            <a href="javascript:document.resp_absence_previous.submit();">Saisir une demande d'absence pour un agent</a>
                        </li>
                        <li onclick='document.resp_aff_solde_previous.submit();'>
                            <form name='resp_aff_solde_previous' method='post' action="affiche_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="resp"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.resp_aff_solde_previous.submit();">Affichage du solde des agents de la structure</a>
                        </li>
                    </ul>
                </li>
<?php
        }
    // Un agent responsable (sens strict) peut modifier le paramétrage de la structure
    // if ($user->estresponsable(false))
    // {
?> 
<?php
    // }
?>
            </ul>
        </li>
    </ul> 
<?php
    }
    if ($user->estgestionnaire()) {
?>
    <ul class="niveau1">
<!--        <li onclick="">MENU GESTIONNAIRE -->
            <li>MENU GESTIONNAIRE
            <ul class="niveau2">
                <li onclick='document.gest_parametre_modif.submit();'>
                    <form name='gest_parametre_modif' method='post' action="gestion_dossier.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="action" value="modif"> 
                        <input type="hidden" name="mode" value="gestion">
                    </form>
                    <a href="javascript:document.gest_parametre_modif.submit();">Paramétrage des dossiers et des structures</a>
                </li>
                <li onclick='document.gest_gest_teletravail.submit();'>
                    <form name='gest_gest_teletravail' method='post' action="gestion_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                        <input type="hidden" name="mode" value="gestion">
                    </form>
                    <a href="javascript:document.gest_gest_teletravail.submit();">Gestion des conventions de télétravail</a>
                </li>
                <li class="plus"><a>Gestion de l'année en cours</a>
                    <ul class="niveau3">
                        <li onclick='document.gest_struct_planning.submit();'>
                            <form name='gest_struct_planning' method='post' action="structure_planning.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion"> 
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.gest_struct_planning.submit();">Planning de la structure</a>
                        </li>
<?php
    $structureliste = $user->structgestliste();
    $code = null;
    foreach ($structureliste as $structure)
    {
        $resp = $structure->resp_envoyer_a($code);
        if ($code ==structure::MAIL_RESP_ENVOI_GEST_COURANT) // 3 = Envoie des mails au gestionnaire de la structure courante
        {
            // On a au moins une structure qui match => On arrête la boucle
            break;
        }
    }
    if ($code == structure::MAIL_RESP_ENVOI_GEST_COURANT) 
    {
?>
                        <li onclick='document.gest_conge.submit();'>
                            <form name='gest_conge' method='post' action="etablir_demande.php">
                                <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges">
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.gest_conge.submit();">Saisir une demande de congé pour un responsable</a>
                        </li>
                        <li onclick='document.gest_absence.submit();'>
                            <form name='gest_absence' method='post' action="etablir_demande.php">
                                <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="absence"> 
                                <input type="hidden" name="previous" value="no">
                            </form>
<!--
                            <a href="javascript:document.gest_absence.submit();">Saisir une demande d'absence ou de télétravail pour un responsable</a>
-->
                            <a href="javascript:document.gest_absence.submit();">Saisir une demande d'absence pour un responsable</a>
                        </li>
<?php
    }
?>
                        <li onclick='document.gest_valid_conge.submit();'>
                            <form name='gest_valid_conge' method='post' action="valider_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion">
                            </form> 
                            <a href="javascript:document.gest_valid_conge.submit();">Validation des demandes en attente</a>
                        </li>
                        <li onclick='document.gest_gest_conge.submit();'>
                            <form name='gest_gest_conge' method='post' action="gestion_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="gestionnaireid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.gest_gest_conge.submit();">Annulation de congé ou d'absence</a>
                        </li>
                        <li onclick='document.gest_valid_tpspartiel.submit();'>
                            <form name='gest_valid_tpspartiel' method='post' action="valider_tpspartiel.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion">
                            </form> 
                            <a href="javascript:document.gest_valid_tpspartiel.submit();">Validation des temps partiels</a>
                        </li>
                        <li onclick='document.gest_aff_solde.submit();'>
                            <form name='gest_aff_solde' method='post' action="affiche_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion">
                            </form>
                            <a href="javascript:document.gest_aff_solde.submit();">Affichage du solde des agents de la structure</a>
                        </li>
                    </ul>
                </li>
                <li class="plus"><a>Gestion de l'année précédente</a>
                    <ul class="niveau3">
                        <li onclick='document.gest_struct_planning_previous.submit();'>
                            <form name='gest_struct_planning_previous' method='post' action="structure_planning.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.gest_struct_planning_previous.submit();">Planning de la structure</a>
                        </li>
<?php
    $structureliste = $user->structgestliste();
    $code = null;
    foreach ($structureliste as $structure)
    {
        $resp = $structure->resp_envoyer_a($code);
        if ($code == structure::MAIL_RESP_ENVOI_GEST_COURANT) // 3 = Envoie des mails au gestionnaire de la structure courante
        {
            // On a au moins une structure qui match => On arrête la boucle
            break;
        }
    }
    if ($code == structure::MAIL_RESP_ENVOI_GEST_COURANT) 
    {
?>
                        <li onclick='document.gest_conge_prev.submit();'>
                            <form name='gest_conge_prev' method='post' action="etablir_demande.php">
                                <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges">
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.gest_conge_prev.submit();">Saisir une demande de congé pour un responsable</a>
                        </li>
                        <li onclick='document.gest_absence_prev.submit();'>
                            <form name='gest_absence_prev' method='post' action="etablir_demande.php">
                                <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="absence"> 
                                <input type="hidden" name="previous" value="yes">
                            </form>
<!--
                            <a href="javascript:document.gest_absence_prev.submit();">Saisir une demande d'absence ou de télétravail pour un responsable</a>
-->
                            <a href="javascript:document.gest_absence_prev.submit();">Saisir une demande d'absence pour un responsable</a>
                        </li>
<?php
    }
?>
                        <li onclick='document.gest_valid_conge_prev.submit();'>
                            <form name='gest_valid_conge_prev' method='post' action="valider_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.gest_valid_conge_prev.submit();">Validation des demandes en attente</a>
                        </li>
                        <li onclick='document.gest_aff_solde_ant.submit();'>
                            <form name='gest_aff_solde_ant' method='post' action="affiche_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestion"> 
                                <input type="hidden" name="previous" value="yes">
                            </form> 
                            <a href="javascript:document.gest_aff_solde_ant.submit();">Affichage du solde des agents de la structure</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>
<?php
    }
    if ($user->estprofilrh()) {
?>
    <ul class="niveau1">
<!--        <li onclick="">MENU GESTION RH  -->
            <li>MENU GESTION RH
            <ul class="niveau2"> 
<?php
                // PROFIL RH ==> GESTIONNAIRE RH DE CET / GESTIONNAIRE RH DE CONGES / GESTIONNAIRE RH DE TELETRAVAIL
                if ($user->estprofilrh(agent::PROFIL_RHCET) or $user->estprofilrh(agent::PROFIL_RHCONGE) or $user->estprofilrh(agent::PROFIL_RHTELETRAVAIL)) 
                {
?>
                <li onclick='document.rh_gest_deleg.submit();'>
                    <form name='rh_gest_deleg' method='post' action="gestion_delegation.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form>
                    <a href="javascript:document.rh_gest_deleg.submit();">Gestion des délégations sur les structures</a>
                </li>
                <li onclick='document.rh_struct_gest.submit();'>
                    <form name='rh_struct_gest' method='post' action="gestion_structure.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                        <input type="hidden" name="mode" value="gestrh">
                    </form> 
                    <a href="javascript:document.rh_struct_gest.submit();">Paramétrage des structures</a>
                </li>
<?php 
                    if ($user->estprofilrh(agent::PROFIL_RHTELETRAVAIL))
                    {
?>					
                <li class="plus"><a>Gestion du télétravail</a>  <!-- Gestion du télétravail et paramétrage -->
                    <ul class="niveau3">
                        <li onclick='document.rh_gest_teletravail_noesignature.submit();'>
                            <form name='rh_gest_teletravail_noesignature' method='post' action="gestion_teletravail.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                                <input type="hidden" name="noesignature" value="yes">
                            </form>
                            <a href="javascript:document.rh_gest_teletravail_noesignature.submit();">Gestion des conventions de télétravail<br>(hors eSignature)</a>
                        </li>
                        <li onclick='document.rh_gest_teletravail.submit();'>
                            <form name='rh_gest_teletravail' method='post' action="gestion_teletravail.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                            </form>
                            <a href="javascript:document.rh_gest_teletravail.submit();">Gestion des conventions de télétravail<br>(avec eSignature)</a>
                        </li>
                        <li onclick='document.rh_affiche_info_teletravail.submit();'>
                            <form name='rh_affiche_info_teletravail' method='post' action="affiche_info_teletravail.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            </form> 
                            <a href="javascript:document.rh_affiche_info_teletravail.submit();">Nombre de jours de télétravail</a>
                        </li>
                        <li onclick='document.rh_suivi_teletravail.submit();'>
                            <form name='rh_suivi_teletravail' method='post' action="suivi_teletravail.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            </form> 
                            <a href="javascript:document.rh_suivi_teletravail.submit();">Suivi de l'avancement des demandes de télétravail</a>
                        </li>
                        <li onclick='document.rh_affiche_teletravail.submit();'>
                            <form name='rh_affiche_teletravail' method='post' action="affiche_teletravail.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            </form> 
                            <a href="javascript:document.rh_affiche_teletravail.submit();">Afficher les conventions de télétravail par structure</a>
                        </li>
                    </ul>
                </li>
<?php 
                    } // Fin du test si utilisateur est PROFIL_RHTELETRAVAIL
                    if ($user->estprofilrh(agent::PROFIL_RHCET))
                    {
?>					
                <li class="plus"><a>Gestion des CET</a>  <!-- Gestion des CET et paramétrage -->
                    <ul class="niveau3">
                        <li onclick='document.gestrh_utilisationcet.submit();'>
                            <form name='gestrh_utilisationcet' method='post' action="utilisation_cet.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                            </form> 
                            <a href="javascript:document.gestrh_utilisationcet.submit();">Validation des congés sur CET</a>
                        </li>
                        <li onclick='document.gestrh_gestcet.submit();'>
                            <form name='gestrh_gestcet' method='post' action="gerer_cet.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                            </form> 
                            <a href="javascript:document.gestrh_gestcet.submit();">Gestion d'un CET</a>
                        </li>
                        <li onclick='document.gestrh_gestcet_hors_esignature.submit();'>
                            <form name='gestrh_gestcet_hors_esignature' method='post' action="gerer_cet_hors_esignature.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                            </form> 
                            <a href="javascript:document.gestrh_gestcet_hors_esignature.submit();">Gestion d'un CET (hors eSignature)</a>
                        </li>
                        <li onclick='document.gestrh_creercet.submit();'>
                            <form name='gestrh_creercet' method='post' action="creer_cet.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="gestrh">
                            </form> 
                            <a href="javascript:document.gestrh_creercet.submit();">Reprise d'un CET existant</a>
                        </li>
                        <li onclick='document.rh_alimentation_cet.submit();'>
                            <form name='rh_alimentation_cet' method='post' action="gerer_alimentationCET.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="mode" value="rh">
                            </form>
                            <a href="javascript:document.rh_alimentation_cet.submit();">Alimentation du CET</a>
                        </li>          
                        <li onclick='document.rh_option_cet.submit();'>
                            <form name='rh_option_cet' method='post' action="gerer_optionCET.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="mode" value="rh">
                            </form>
                            <a href="javascript:document.rh_option_cet.submit();">Droit d'option sur CET</a>
                        </li>
                    </ul>
                </li>
<?php 
                    } // Fin du test si utilisateur est PROFIL_RHCET
                    if ($user->estprofilrh(agent::PROFIL_RHCONGE))
                    {
?>
                <li class="plus"><a>Gestion des congés</a>
                    <ul class="niveau3">
                        <li onclick='document.rh_conge.submit();'>
                            <form name='rh_conge' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges"> 
                                <input type="hidden" name="previous" value="no">
                                <input type="hidden" name="rh_mode" value="yes">
                                <input type="hidden" name="show_cet" value="no">
                            </form> 
                            <a href="javascript:document.rh_conge.submit();">Saisir une demande de congés (hors CET)</a>
                        </li>
                        <li onclick='document.rh_conge_cet.submit();'>
                            <form name='rh_conge_cet' method='post' action="etablir_demande.php">
                                <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="typedemande" value="conges"> 
                                <input type="hidden" name="previous" value="no">
                                <input type="hidden" name="rh_mode" value="yes">
                                <input type="hidden" name="show_cet" value="yes">
                            </form> 
                            <a href="javascript:document.rh_conge_cet.submit();">Saisir une demande de congés sur CET</a>
                        </li>
                        <li onclick='document.rh_gest_conge.submit();'>
                            <form name='rh_gest_conge' method='post' action="gestion_demande.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                <input type="hidden" name="mode" value="rh"> 
                                <input type="hidden" name="previous" value="no">
                            </form> 
                            <a href="javascript:document.rh_gest_conge.submit();">Annulation de congés imputés sur le CET</a>
                        </li>
                        <li onclick='document.affiche_info_agent.submit();'>
                            <form name='affiche_info_agent' method='post' action="affiche_info_agent.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
                            </form> 
                            <a href="javascript:document.affiche_info_agent.submit();">Consultation des congés d'un agent</a>
                        </li>
                        <li onclick='document.modif_solde.submit();'>
                            <form name='modif_solde' method='post' action="modif_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
                            </form> 
                            <a href="javascript:document.modif_solde.submit();">Modification du solde de congés d'un agent</a>
                        </li>
                        <li onclick='document.rh_ajout_conge.submit();'>
                            <form name='rh_ajout_conge' method='post' action="ajouter_conges.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type="hidden" name="mode" value="gestrh">
                            </form> 
                            <a href="javascript:document.rh_ajout_conge.submit();">Gestion des jours supplémentaires pour un agent</a>
                        </li>
                        <li onclick='document.rh_aff_solde.submit();'>
                            <form name='rh_aff_solde' method='post' action="affiche_solde.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                <input type="hidden" name="mode" value="rh"> 
                                <input type="hidden" name="previous" value="no">
                            </form>
                            <a href="javascript:document.rh_aff_solde.submit();">Affichage du solde des agents d'une structure</a>
                        </li>
                        <li onclick='document.rh_affiche_jourscomplementaires.submit();'>
                            <form name='rh_affiche_jourscomplementaires' method='post' action="affiche_jourscomplementaires.php">
                                <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            </form> 
                            <a href="javascript:document.rh_affiche_jourscomplementaires.submit();">Afficher les jours complémentaires</a>
                        </li>
                    </ul>
                </li>
<?php
                    } // Fin du test si utilisateur est PROFIL_RHCONGE
?>
                <li onclick='document.rh_affiche_inputfiles.submit();'>
                    <form name='rh_affiche_inputfiles' method='post' action="affiche_inputfiles.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.rh_affiche_inputfiles.submit();">Afficher les données d'interface</a>
                </li>
                <li onclick='document.rh_affiche_g2t_param.submit();'>
                    <form name='rh_affiche_g2t_param' method='post' action="g2t_param.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.rh_affiche_g2t_param.submit();">Paramétrage</a>
                </li>
<?php
        }
?>
            </ul>
        </li>
    </ul>
<?php
    }
    if ($realuser->estadministrateur()) {
?>
    <ul class="niveau1">
<!--        <li onclick="">MENU ADMINISTRATEUR -->
        <li>MENU ADMINISTRATEUR
            <ul class="niveau2">
                <li onclick='document.admin_mode_maintenance.submit();'>
                    <form name='admin_mode_maintenance' method='post' action="admin_maintenance.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_mode_maintenance.submit();">Activer/désactiver maintenance</a>
                </li>
                <li onclick='document.admin_struct_gest.submit();'>
                    <form name='admin_struct_gest' method='post' action="gestion_structure.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                        <input type="hidden" name="mode" value="">
                    </form> 
                    <a href="javascript:document.admin_struct_gest.submit();">Paramétrage des structures</a>
                </li>
                <li onclick='document.admin_subst_agent.submit();'>
                    <form name='admin_subst_agent' method='post' action="admin_substitution.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_subst_agent.submit();">Se faire passer pour un autre agent</a>
                </li>
                <li onclick='document.admin_import_conges.submit();'>
                    <form name='admin_import_conges' method='post' action="import_conges.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_import_conges.submit();">Importer des congés</a>
                </li>
                <li onclick='document.admin_solde_conges.submit();'>
                    <form name='admin_solde_conges' method='post' action="affiche_info_conges.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_solde_conges.submit();">Synthèse des congés</a>
                </li>
                <li onclick='document.admin_affiche_demandeCET.submit();'>
                    <form name='admin_affiche_demandeCET' method='post' action="affiche_demandeCET.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_demandeCET.submit();">Afficher une demande sur CET/eSignature</a>
                </li>
                <li onclick='document.admin_affiche_info_teletravail.submit();'>
                    <form name='admin_affiche_info_teletravail' method='post' action="affiche_info_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_info_teletravail.submit();">Nombre théorique de jours de télétravail</a>
                </li>
                <li onclick='document.admin_affiche_g2t_param.submit();'>
                    <form name='admin_affiche_g2t_param' method='post' action="g2t_param.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_g2t_param.submit();">Paramétrage</a>
                </li>
                <li onclick='document.admin_suivi_teletravail.submit();'>
                    <form name='admin_suivi_teletravail' method='post' action="suivi_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_suivi_teletravail.submit();">Suivi de l'avancement des demandes de télétravail</a>
                </li>
                <li onclick='document.admin_affiche_inputfiles.submit();'>
                    <form name='admin_affiche_inputfiles' method='post' action="affiche_inputfiles.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_inputfiles.submit();">Afficher les données d'interface</a>
                </li>
                <li onclick='document.admin_affiche_jourscomplementaires.submit();'>
                    <form name='admin_affiche_jourscomplementaires' method='post' action="affiche_jourscomplementaires.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_jourscomplementaires.submit();">Afficher les jours complémentaires</a>
                </li>
                <li onclick='document.admin_affiche_teletravail.submit();'>
                    <form name='admin_affiche_teletravail' method='post' action="affiche_teletravail.php">
                        <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                    </form> 
                    <a href="javascript:document.admin_affiche_teletravail.submit();">Afficher les conventions de télétravail par structure</a>
                </li>
            </ul>
        </li>
    </ul>  
<?php
	}
?> 

</div>
<br> <br> <br>