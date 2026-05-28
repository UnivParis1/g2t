<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.8" />

<link rel="icon" type="image/x-icon" href="favicon.ico"></link>
<?php    
    global $dbcon;
    global $uid;
    $WSGROUPURL = $fonctions->liredbconstante("WSGROUPURL");
    // echo "<br><br>WSGROUPURL = $WSGROUPURL <br>";
?>
<title>G2T
<?php 
    if (defined('TYPE_ENVIRONNEMENT') and strcasecmp((string)TYPE_ENVIRONNEMENT,'PROD')!=0)
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

    function showwaitingimg()
    {
        let waiting_img_caption = document.getElementById('waiting_img_caption');
        if (waiting_img_caption)
        {
            waiting_img_caption.hidden=false;
        }
        let waiting_img = document.getElementById('waiting_img');
        if (waiting_img)
        {
            waiting_img.hidden=false;
        }
        let waiting_div = document.getElementById('waiting_div');
        if (waiting_div)
        {
            waiting_div.hidden=false;
        }
    }

    function hiddewaitingimg()
    {

        let updatefinished = true;
        let divtocompletecollection = document.querySelectorAll('.divtocomplete');
        if (divtocompletecollection)
        {
            divtocompletecollection.forEach((divtocomplete) => { /* console.log( divtocomplete.id + " " + divtocomplete.hasChildNodes() ); */ if (divtocomplete.hasChildNodes() == false) { updatefinished = false; }} );
        }

        if (updatefinished)
        {
            // On va cacher le div de l'attente
            let waiting_img_caption = document.getElementById('waiting_img_caption');
            if (waiting_img_caption)
            {
                waiting_img_caption.hidden=true;
            }
            let waiting_img = document.getElementById('waiting_img');
            if (waiting_img)
            {
                waiting_img.hidden=true;
            }
            let waiting_div = document.getElementById('waiting_div');
            if (waiting_div)
            {
                waiting_div.hidden=true;
            }
        }
    }

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

    function frenchdate_to_isoformat(datestring)
    {
        datestring = datestring.trim();
        if (datestring == "")
        {
            return datestring;
        }
        if (!dateIsValid(datestring))
        {
            return false;
        }
        return datestring.replace(/^(\d{2})(.)(\d{2})(.)(\d{4})$/g,"$5-$3-$1");
    }

    function dbdate_to_frenchformat(datestring)
    {
        datestring = datestring.trim();

        const regex = /^(\d{4})(\d{2})(\d{2})$/;
        if (datestring.match(regex) === null) {
            return false;
        }
        let frenchdate = datestring.replace(/^(\d{4})(\d{2})(\d{2})$/g,"$3/$2/$1");
        if (!dateIsValid(frenchdate))
        {
            return false;
        }
        return frenchdate;
    }

    function dateIsValid(dateStr) {
        // Syntawxe : Utilisation d'un littéral d'expression régulière, qui consiste en un modèle entouré de barres obliques
        const regex = /^\d{2}\/\d{2}\/\d{4}$/;

        if (dateStr.match(regex) === null) {
            return false;
        }

        const [day, month, year] = dateStr.split('/');
        const isoFormattedStr = `${year}-${month}-${day}`;
        const date = new Date(isoFormattedStr);
        const timestamp = date.getTime();

        if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
            return false;
        }
        return date.toISOString().startsWith(isoFormattedStr);
    }

    function sort_table_init(tablename,column_number)
    {
        let table = document.getElementById(tablename);
        if (table)
        {
            let thlist = table.querySelector('.entete').querySelectorAll('th');
            thlist.forEach(th => { 
                                     if (th.querySelector('.sortindicator')!==null) 
                                     { 
                                        th.addEventListener('click', sortcolumn) 
                                        th.asc = true
                                     }
                                 });
            if (column_number < thlist.length)
            {
                thlist[column_number].click(); // On simule le clic sur la colonne column_number pour faire afficher la flêche
            }
            else
            {
                console.log("Attention : La colonne " + column_number.toString() + " ne fait pas partie du tableau " + tablename);
            }
        }
        else
        {
            console.log("Attention : La table " + tablename + " n'existe pas dans le document");
        }
    }

    function sortcolumn()
    {
        let th = this;

        const currentsortindicator = th.querySelector('.sortindicator')
        if (currentsortindicator!==null)
        {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
        
            if (currentsortindicator.innerText.trim().length>0)
            {
                th.asc = !th.asc
            }
        
            Array.from(tbody.querySelectorAll('tr'))
                .sort(comparer(Array.from(th.parentNode.children).indexOf(th), th.asc))
                .forEach(tr => tbody.appendChild(tr) );
        
            for (var thindex = 0 ; thindex < table.querySelector('.entete').querySelectorAll('th').length; thindex++)
            {
                if (th.parentNode.children[thindex]!==null)
                {
                    var thsortindicator = th.parentNode.children[thindex].querySelector('.sortindicator');
                    if (thsortindicator!==null)
                    {
                        thsortindicator.innerText = ' ';
                    }
                }
            }
        
            if (currentsortindicator!==null)
            {
                if (th.asc)
                {
                    currentsortindicator.innerHTML = '&darr;'; // flêhe qui descend
                }
                else
                {
                    currentsortindicator.innerHTML = '&uarr;'; // flêche qui monte
                }
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
        // console.log('hide_teletravail : id du tableau => ' + tableau.id);
    	var checkboxvalue = document.activeElement.checked;
        for (var indexcellule = 0; indexcellule < tableau.querySelectorAll('.teletravail').length; indexcellule++)
        {
            // console.log('hide_teletravail : Index de la cellule = ' + indexcellule);
            var currenttd = tableau.querySelectorAll('.teletravail')[indexcellule];
            // console.log('hide_teletravail : currenttd id = ' + currenttd.id);


            // ATTENTION : On TRIM la classe exclusion car il ne faut pas les espaces quand on vérifie si la classe est là
            // Soit on a demander à le masquer, soit c'est une date exclue (<=> classe exclusion)
            if (checkboxvalue || currenttd.classList.contains('<?php echo trim(planningelement::HTML_CLASS_EXCLUSION); ?>'))  
            {
                // console.log('hide_teletravail : Suppression de la couleur => Couleur actuelle = ' + currenttd.bgColor);
                // C'est du télétravail et on doit le masquer ou la date est exclue
                currenttd.bgColor = '<?php echo planningelement::COULEUR_VIDE ?>';
                // console.log('hide_teletravail : Suppression de la couleur => Nouvelle couleur = ' + currenttd.bgColor);
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
                //console.log('hide_teletravail : Réactivation de la couleur => Couleur actuelle = ' + currenttd.bgColor);
                currenttd.bgColor = '<?php echo "$couleur"  ?>';
                //console.log('hide_teletravail : Réactivation de la couleur => Nouvelle couleur = ' + currenttd.bgColor);
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
        let motifinput;
        // Si l'index n'est pas un nombre alors on doit l'encadrer par des guillemets
        if (isNaN(index))
        {
            motifinput = document.getElementById('motif["' +  index + '"]');
        }
        else
        {
            motifinput = document.getElementById('motif[' +  index + ']');
        }
        //alert('Motif id = ' + motifinput.id);
        validdemandemotif(motifinput,index);
    };
	
    var validdemandemotif = function (motif, index)
    {
        // console.log('validdemandemotif : ' + index);
        let select;
        // Si l'index n'est pas un nombre alors on doit l'encadrer par des guillemets
        if (isNaN(index))
        {
            select = document.getElementById('statut["' +  index + '"]');
        }
        else
        {
            select = document.getElementById('statut[' +  index + ']');
        }
        //console.log("function validdemandemotif => " + Date.now());
        //console.log('Select id = ' + select.id + ' value = ' + select.value);
        if (select.value == '<?php echo demande::DEMANDE_REFUSE; ?>')
        {
            //alert ('Select value = ' + select.value);
            motif.disabled = false;
            //alert ('checked');
            //console.log ('Valeur motif = ' + motif.value);
            if (motif.value == '')
            {
                motif.style.backgroundColor = '#f5b7b1';
            }
            else
            {
                //console.log("Le motif n'est pas nul");
                motif.style.backgroundColor = '';
                motif.classList.remove("commentobligatoirebackground");
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
<!--  
<link rel='stylesheet' type='text/css' href='css-g2t/arborescence.css?<?php echo filemtime('css-g2t/g2t.css') ?>' media='all'></link> 
-->

<!------------------------------------
<link rel="stylesheet" type="text/css" href="style/jquery-ui.css" media="screen"></link>
------------------------------->
</head>

<body class="bodyhtml"> 

    <!-- Fenètre modale -->
    <div id="divmodal" class="divmodal">
        <div class="questiondialog divmodelcontent" > 
            <div class="divmodalheader">
                <h2>
<?php
                $typearray = array('question','suppression','ajout', 'error');
                foreach ($typearray as $type)
                {
                    $path = $fonctions->imagepath() . "/" . $type . "_logo.png";
                    if (file_exists($path))
                    {
                        list($width, $height, $imagetype) = getimagesize("$path");
                        $typeimage = image_type_to_extension($imagetype,false);
                        if ($typeimage===false) // Si on n'a pas pu déterminé le type d'image => On récupère l'extension du fichier
                        {
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("imagetype = $imagetype => extension non définie"));
                            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
                        }
                        $data = file_get_contents($path);
                        $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
                        echo "<img class='img". $type ." imagedialog' id='img". $type ."' name='img". $type ."' src='" . $base64 . "' hidden>";
                    }
                }
                echo "&nbsp;";
                $esignature = new esignature($dbcon);

?>
                <label id='labelmodalheader' name='labelmodalheader' >Modal Header</label>
                </h2>
            </div>

            <p>
                <label id='questionlabeltext' >Question label text.</label>
            </p>
            <form name='modifiercircuit'  method='post'>
                <div id='divselecttype' hidden>
                    Type de signataire : 
                    <select id='newrecipienttype' name='newrecipienttype' onchange='addreciepientchangetype();'>
                        <option value=''><?php echo "--- Sélectionnez un type de signataire ---"; ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_DEMANDEUR; ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_DEMANDEUR); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESPONSABLE ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESPONSABLE); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESPONSABLE2 ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESPONSABLE2); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESP_BRANCHE ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESP_BRANCHE); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_DIRECTEUR ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_DIRECTEUR); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_AGENT ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_AGENT); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESP_STRUCT ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESP_STRUCT); ?></option>
                    </select>
                </div>
                <div id='divagentid' name='divagentid' hidden>
                    <br>
                    Identifiant de l'intervenant :
                    <input id="usersignataire" name="usersignataire" placeholder="Nom et/ou prenom" autofocus/>
                    <input type='hidden' id="newidsignataire" name="newidsignataire" class='usersignataire' />
                    <script>
                        $( "#usersignataire" ).autocompleteUser(
                            '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                                wsParams: { filter_eduPersonAffiliation: "employee|staff" } });
                    </script>
                </div>
                <div id='divstructid' name='divstructid' hidden>
                    <br>
                    Structure de l'intervenant :
                    <select size='1' id='newstructureid' name='newstructureid' class='selectstructure' value=''>
                        <option value=''>----- Veuillez sélectionner la structure -----</option>
                    </select>
                </div>
                <div id='divreportid' name ='divreportid' hidden>
                    <br>
                    Action à réaliser :
                    <select id='reportchoice'>
                        <option value=''>Ne pas reporter</option>
                    </select>
                    <br>
                    <input type='hidden' id='indexjroblig' name='indexjroblig' />
                    <p class='centeraligntext nomargin' id='labelreport' name='labelreport' hidden>
                    </p>
                </div>
                <div id='divmotif' name='divmotif' class='centeraligntext' hidden>
                    <textarea name="motiftextarea" id="motiftextarea" class='motiftextarea' rows="5" cols="60"></textarea>
                </div>
            </form>
            <menu class='nopadding centeraligntext'>
                <button id="questionconfirmBtn" value="" class='g2tbouton g2tvalidebouton'>Ok</button>  
                <button id="questioncancelBtn" value="cancel" class='g2tbouton g2tannulerbouton'>Annuler</button>
            </menu>
        </div>
    </div>

    
    <script>
        // Récupération des objets de la fenêtre modale
        var divmodal = document.getElementById("divmodal");
        var divmodalconfirmBtn = divmodal.querySelector('#questionconfirmBtn');
        var divmodallabeltext = divmodal.querySelector('#questionlabeltext');
        var divmodalcancelBtn = divmodal.querySelector('#questioncancelBtn'); 
        var divstructid = divmodal.querySelector('#divstructid');
        var divreportid = divmodal.querySelector('#divreportid');
        var divagentid = divmodal.querySelector('#divagentid');
        var divmotif = divmodal.querySelector('#divmotif');
        var divselecttype = divmodal.querySelector('#divselecttype');
        var labelmodalheader = divmodal.querySelector('#labelmodalheader');
        var imgmodallist = divmodal.querySelectorAll(".imagedialog")
        var newidsignataire = divmodal.querySelector('#newidsignataire');
        var newstructureid = divmodal.querySelector('#newstructureid');
        var selecttype = divmodal.querySelector('#newrecipienttype');
        var usersignataire = divmodal.querySelector('#usersignataire');
        var reportselect = divmodal.querySelector('#reportchoice');
        var labelreport = divmodal.querySelector('#labelreport');
        var indexjroblig = divmodal.querySelector('#indexjroblig');
        var motiftextarea = divmodal.querySelector('#motiftextarea');

        // Si l'affichage change le contrôle qui a focus (voir checktextlength), on doit rendre le focus après que la fenêtre modale soit fermée
        var previousfocuscontrol = null;


        function masquerimgmodal(exception = "")
        {
            for (let indeximg = 0 ; indeximg < imgmodallist.length ; indeximg++)
            {
                imgmodallist[indeximg].hidden = true;
                if (imgmodallist[indeximg].name == 'img' + exception)
                {
                    imgmodallist[indeximg].hidden = false;
                }
            }
            divmodallabeltext.parentElement.classList.remove('centeraligntext');
            divmodalcancelBtn.classList.remove('g2tannulerbouton');
            divmodalcancelBtn.classList.remove('g2tokbouton');
            divmodalconfirmBtn.classList.remove('g2tvalidebouton');

            divstructid.hidden = true;
            divreportid.hidden = true;
            divagentid.hidden = true;
            divmotif.hidden = true;
            divselecttype.hidden = true;
            labelreport.hidden = true;
            labelreport.innerHTML = '';
            labelreport.classList.remove("warnbackgroundtext");
        }

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
            let labelrestanttext = document.getElementById(labelrestantname);
            //console.log("function checktextlength => " + Date.now());
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
                if (divmodal)
                {
                    masquerimgmodal('error');
                    divstructid.hidden = true;
                    divagentid.hidden = true;
                    divselecttype.hidden = true;
                    divreportid.hidden = true;
                    divmotif.hidden = true;
                    labelmodalheader.innerHTML = 'Longueur du texte';
                    divmodalcancelBtn.textContent = "Ok";
                    divmodalcancelBtn.hidden = false;
                    divmodalcancelBtn.classList.add('g2tokbouton');
                    divmodalconfirmBtn.hidden = true;
                    divmodallabeltext.parentElement.classList.add('centeraligntext');
                    divmodallabeltext.innerHTML = 'Votre texte ne doit pas dépasser '+maxlength+' caractères!';
                    divmodal.style.display = "block";
                    previousfocuscontrol = textarea;
                    divmodalcancelBtn.focus();
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
                var lines = text.split('/\r|\r\n|\n/');
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

                if (divmodal)
                {
                    masquerimgmodal('error');
                    divstructid.hidden = true;
                    divagentid.hidden = true;
                    divselecttype.hidden = true;
                    divreportid.hidden = true;
                    divmotif.hidden = true;
                    labelmodalheader.innerHTML = 'Longueur du texte';
                    divmodalcancelBtn.textContent = "Ok";
                    divmodalcancelBtn.hidden = false;
                    divmodalcancelBtn.classList.add('g2tokbouton');
                    divmodalconfirmBtn.hidden = true;
                    divmodallabeltext.parentElement.classList.add('centeraligntext');
                    divmodallabeltext.innerHTML = 'Votre texte ne doit pas contenir plus de ' + maxRows + ' ligne(s).';
                    divmodal.style.display = "block";
                    previousfocuscontrol = textarea;
                    divmodalcancelBtn.focus();
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

        divmodalcancelBtn.onclick = function() 
        {
            divmodal.style.display = "none";
            if (previousfocuscontrol)
            {
                previousfocuscontrol.focus();
                previousfocuscontrol = null;
            }
            masquerimgmodal();
            return false;
        }

        async function showagentplanning(params, divtoupdate)
        {
            if (!divtoupdate)
            {
                console.log("Impossible d'identifier le div à mettre à jour.");
                return;
            }

            let fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";
            try {
                // postparams = { method: "POST",
                //                headers: {
                //                          'Accept': 'application/json',
                //                          'Content-Type': 'application/json'
                //                         },
                //                 body: JSON.stringify(params)
                //              };
                // var reponse = await fetch(fullWSURL,postparams);

                var reponse = await fetch(fullWSURL + "?" + new URLSearchParams(params).toString());
                var data = await reponse.json();
            } 
            catch (exception) 
            {
                var statutinfo = "Erreur WS - méthode : <?php echo agent::WS_METHODE_PLANNING; ?> - " + reponse.status + " " + reponse.statusText;
                console.log(statutinfo);
                hiddewaitingimg();
                return;
            }

            // L'appel du WS s'est bien passé => On a eu une réponse (ok ou pas mais on a une réponse)
            if (reponse.ok !== true)
            {
                var statutinfo = "Erreur WS - méthode : <?php echo agent::WS_METHODE_PLANNING; ?> - " + reponse.status + " " + reponse.statusText;
                console.log(statutinfo);
            }
            else 
            {
                if (data.status.toUpperCase()=='OK')
                {
                    var statutinfo = "OK";
                }
                else
                {
                    var statutinfo = "KO => " + data.description;
                }
                // console.log("Retour du WS => " + statutinfo);
                divtoupdate.innerHTML = data.html;
            }
            hiddewaitingimg();
        }

        async function showstructureplanning(params, divtoupdate)
        {
            if (!divtoupdate)
            {
                console.log("Impossible d'identifier le div à mettre à jour.");
                return;
            }

            let fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/structureWS.php";
            try {
                // postparams = { method: "POST",
                //                headers: {
                //                          'Accept': 'application/json',
                //                          'Content-Type': 'application/json'
                //                         },
                //                 body: JSON.stringify(params)
                //              };
                // var reponse = await fetch(fullWSURL,postparams);

                var reponse = await fetch(fullWSURL + "?" + new URLSearchParams(params).toString());
                var data = await reponse.json();
            } 
            catch (exception) 
            {
                var statutinfo = "Erreur WS - méthode : <?php echo structure::WS_METHODE_PLANNING; ?> - ";
                if (reponse)
                {
                    statutinfo = statutinfo + reponse.status + " " + reponse.statusText;
                }
                console.log(statutinfo);
                hiddewaitingimg();
                return;
            }

            // L'appel du WS s'est bien passé => On a eu une réponse (ok ou pas mais on a une réponse)
            if (reponse.ok !== true)
            {
                var statutinfo = "Erreur WS - méthode : <?php echo structure::WS_METHODE_PLANNING; ?> - " + reponse.status + " " + reponse.statusText;
                console.log(statutinfo);
            }
            else 
            {
                if (data.status.toUpperCase()=='OK')
                {
                    var statutinfo = "OK";
                }
                else
                {
                    var statutinfo = "KO => " + data.description;
                }
                // console.log("Retour du WS => " + statutinfo);

                if (params['showallstructure'] && params['showallstructure'].toUpperCase() == 'O' && data.html.trim() != "")
                {
                    // On ajoute la checkbox pour afficher tous les agents de la structure "racine"
                    data.html = data.html + "<br>";
                    data.html = data.html + "<form name='form_showroot' id='form_showroot' method='post'>";
                    data.html = data.html + "<input type='hidden' name='indexmois' value='" + params['indexmois'] + "' />";
                    data.html = data.html + "<input type='hidden' name='userid' value='" + params['userid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='mode' value='" + params['mode'] + "' />";
                    data.html = data.html + "<input type='hidden' name='previous' value='" + params['previoustxt'] + "' />";
                    data.html = data.html + "<input type='hidden' name='rootid' value='" + params['rootid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='structureid' value='" + params['structureid'] + "' />";
                    data.html = data.html + "<input type='checkbox' id='check_showroot' name='check_showroot' onclick='this.form.submit()' ";
                    if (params['check_showroot'] && params['check_showroot'].toUpperCase() == 'O')
                    {
                        data.html = data.html + " checked ";
                    }
                    data.html = data.html + "/>";
                    //echo "Voir l'intégralité du planning de la structure \"racine\" => " . $structparent->nomcourt();
                    data.html = data.html + "Voir l'intégralité du planning de la structure <b>" + params['rootnomcourt'] + "</b>";
                    data.html = data.html + "</form>";
                }
                
                if (params['teletravailexport'] && params['teletravailexport'].toUpperCase() == 'O' && data.html.trim() != "")
                {
                    data.html = data.html + "<br>";
                    data.html = data.html + "<form name='form_teletravailPDF' id='form_teletravailPDF' method='post' action='affiche_pdf.php' target='_blank'>";
                    data.html = data.html + "<input type='hidden' name='indexmois' value='" + params['indexmois'] + "' />";
                    data.html = data.html + "<input type='hidden' name='userid' value='" + params['userid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='mode' value='" + params['mode'] + "' />";
                    data.html = data.html + "<input type='hidden' name='previous' value='" + params['previoustxt'] + "' />";
                    data.html = data.html + "<input type='hidden' name='structureid' value='" + params['structureid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='datedebut' value='<?php echo htmlspecialchars((date('Y')-1) . '1001'); ?>' />"; // Date de début du dernier trimestre de l'année d'avant
                    data.html = data.html + "<input type='hidden' name='datefin' value='<?php echo htmlspecialchars((date('Y')-1) . '1231'); ?>' />";  // Date de fin du dernier trimestre de l'année d'avant
                    
                    data.html = data.html + "Afficher le document 'télétravail' pour la structure " + params['structurenomlong'] +  " (" + params['structurenomcourt'] + ")<br>";
                    data.html = data.html + "<input type='submit' name='teletravailPDF' id='teletravailPDF' class='g2tbouton g2tdocumentbouton g2tboutonwidthauto' value='Afficher un PDF'/>";
                    data.html = data.html + "</form>";

                    data.html = data.html + "<form name='form_teletravailmail' id='form_teletravailmail' method='post'>";
                    data.html = data.html + "<input type='hidden' name='indexmois' value='" + params['indexmois'] + "' />";
                    data.html = data.html + "<input type='hidden' name='userid' value='" + params['userid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='mode' value='" + params['mode'] + "' />";
                    data.html = data.html + "<input type='hidden' name='previous' value='" + params['previoustxt'] + "' />";
                    data.html = data.html + "<input type='hidden' name='structureid' value='" + params['structureid'] + "' />";
                    data.html = data.html + "<input type='hidden' name='datedebut' value='<?php echo htmlspecialchars((date('Y')-1) . '1001'); ?>' />"; // Date de début du dernier trimestre de l'année d'avant
                    data.html = data.html + "<input type='hidden' name='datefin' value='<?php echo htmlspecialchars((date('Y')-1) . '1231'); ?>' />";  // Date de fin du dernier trimestre de l'année d'avant
                    
                    data.html = data.html + "Envoyer par mail le document 'télétravail' pour la structure " + params['structurenomlong'] +  " (" + params['structurenomcourt'] + ")<br>";
                    data.html = data.html + "<input type='submit' name='teletravailmail' id='teletravailmail' class='g2tbouton g2tenvoibouton g2tboutonwidthauto' value='Envoyer un PDF'/>";
                    data.html = data.html + "</form>";
                }

                divtoupdate.innerHTML = data.html;
                sort_table_init('struct_plan_' + params['structureid'] ,0);
            }
            hiddewaitingimg();
        }

    </script>

<?php
    function addwaitingimgdiv()
    {
        global $fonctions;

        $path = $fonctions->imagepath() . "/chargement.gif";
        list($width, $height, $imagetype) = getimagesize("$path");
        $typeimage = image_type_to_extension($imagetype,false);
        if ($typeimage===false) // Si on n'a pas pu déterminé le type d'image => On récupère l'extension du fichier
        {
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("imagetype = $imagetype => extension non définie"));
            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
        }
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
        // echo "<div id='waiting_div' class='waiting_div' ><img id='waiting_img' class='waiting_img' src='" . $base64 . "' height='$height' width='$width' ></div>";
        echo "<div id='waiting_div' class='waiting_div' >
                <figure class='waiting_img'>
                    <img id='waiting_img' src='" . $base64 . "' height='$height' width='$width' >
                    <figcaption id='waiting_img_caption' class='boldtext fontsize18 centeraligntext' >Veuillez patienter</figcaption>
                </figure>
              </div>";
    }

    // Obsolete => Déplacée dans la class fonctions
    // function triparprofondeurabsolue($struct1, $struct2)
    // {
    //     if ($struct1->profondeurabsolue()==$struct2->profondeurabsolue())
    //     {
    //         return 0;
    //     }
    //     return ($struct1->profondeurabsolue() < $struct2->profondeurabsolue()) ? -1 : 1;
    // }

    // On chrge le "vrai" utilisateur de l'application (Celui du ticket CAS)
    $realuser = new agent($dbcon);
    $realuserid = $fonctions->useridfromCAS($uid);
    if ($realuserid !== false)
    {
        $realuser->load($realuserid);
    }
        
    // On verifie que la personne est autorisé à ce connecter à G2T ou qu'elle est administrateur
    if (!$realuser->isG2tUser() and !$realuser->estadministrateur())
    {
        $errlog = "Vous n'êtes pas autorisé à vous connecter à cette application.";
        $errlog = $errlog . "<br>";
        $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";
        
        echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

        $techlog = "Informations techniques :";
        $techlog = $techlog . "<br><ul>";
        $techlog = $techlog . "<li>Identité de l'utilisateur : " . $realuser->identitecomplete() . " (identifiant = " . $realuser->agentid() . ")</li>";
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
    if (strcasecmp((string)$maintenance, 'n') != 0 or strcasecmp((string)$synchro, 'n') != 0) {
        if ($realuser->estadministrateur()) // Si un administrateur est connecté
        {
            if (strcasecmp((string)$maintenance, 'n') != 0)
            {
                echo "<div class='redtext fontsize25 centeraligntext' ><B><U>ATTENTION : LE MODE MAINTENANCE EST ACTIV&Eacute; -- APPLICATION EN MAINTENANCE</U></B></div><BR>";
            }
            if (strcasecmp((string)$synchro, 'n') != 0)
            {
                echo "<div class='redtext fontsize25 centeraligntext'><B><U>ATTENTION : LE MODE SYNCHRONISATION EST ACTIV&Eacute; -- APPLICATION EN COURS DE SYNCHRO</U></B></div><BR>";
            }
        }
        else // C'est un utilisateur simple => Affichage de la page de maintenance
        {
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("L'utilisateur " . $realuser->identitecomplete() . " a essaye d'acceder a l'environnement alors qu'il est en maintenance."));
?>            
            <img alt="Maintenance" width="144" height="79" src="data:image/gif;base64, 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" >
            <br>L'application de gestion des congés est en maintenance, elle sera bientôt à nouveau en ligne.
            <br>Veuillez nous excuser pour la gêne occasionnée.
            </body>
            </html>
<?php
            exit();
        }
    }

    if (($user->agentid() != $realuser->agentid()) and $realuser->estadministrateur())
    {
        echo "<div class='centeraligntext'><label class='redtext fontsize25'><B><U>ATTENTION : VOUS VOUS &Ecirc;TES SUBSTITU&Eacute; &Agrave; UNE AUTRE PERSONNE</U></B></label><br><label>" . $user->identitecomplete() . " (Agent Id = " . $user->agentid() . ")</label></div><BR>";
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
            //$agentstructure->estbibliotheque("0");  // Ce n'est pas une bibliothèque par défaut
        }
    } 
    else 
    {
        $agentstructure->affichetoutagent("n");
        //$agentstructure->estbibliotheque("0");  // Ce n'est pas une bibliothèque par défaut
    }


?>

<link rel="stylesheet" type="text/css" href="css-g2t/menubar-navigation.css?<?php echo filemtime('css-g2t/menubar-navigation.css') ?>" media="all"></link>
<script src="javascript/menubar-navigation.js?<?php echo filemtime('javascript/menubar-navigation.js') ?>"></script>

<?php
    // On affiche le pagepath s'il existe dans le $_POST sinon on essaie de le déterminer
    if (isset($_POST['pagepath']))
    {
        echo "<label class='headerpagepath'>" . $_POST['pagepath'] . "</label>";
    }
    else
    {
        // echo "<label class='headerpagepath'>MENU AGENT - Accueil</label>";
        echo "<label class='headerpagepath'></label>";
    }
?>

<br> <br>

<div name="mainmenunew" id="mainmenunew" class="mainmenu">
    <nav aria-label="G2T Menu Principal">
        <ul class="menubar-navigation niveau1" role="menubar" aria-label="G2T Menu Principal">
<!---------------------------------------------------
                  Menu Agent  
---------------------------------------------------->
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#agent">
                    MENU AGENT
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="agent" class="niveau2">
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'index.php'; ?>
                        <form name='accueil' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Accueil</a>
                    </li>
<?php
    if (!$agentstructure->estbibliotheque())
    {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_planning.php'; ?>
                        <form name='planning' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de l'agent</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'etablir_demande.php'; ?>
                        <form name='dem_conge' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="typedemande" value="conges">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé</a>
                    </li>
<?php                                    
    }
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'etablir_demande.php'; ?>
                        <form name='dem_absence' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="typedemande" value="absence">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande d'absence</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_demande.php'; ?>
                        <form name='agent_gest_demandes' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des demandes</a>
                    </li>
<?php
    if (!$agentstructure->estbibliotheque())
    {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'saisir_tpspartiel.php'; ?>
                        <form name='agent_tpspartiel' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_AGENT; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des temps partiels</a>
                    </li>
<?php                                    
    }
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_teletravail.php'; ?>
                        <form name='agent_gest_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des conventions de télétravail</a>
                    </li>
<?php
    if (!$agentstructure->estbibliotheque())
    {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'deplacer_teletravail.php'; ?>
                        <form name='agent_depla_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_AGENT; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification d'un jour de télétravail</a>
                    </li>
<?php
    }
    if (strcasecmp((string)$agentstructure->affichetoutagent(), "o") == 0 and !$agentstructure->estbibliotheque()) 
    {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'structure_planning.php'; ?>
                        <form name='agent_struct_planning' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type="hidden" name="mode" value="<?php echo MODE_AGENT; ?>">
                            <input type="hidden" name="previous" value="no">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de la structure</a>
                    </li>
<?php
    }
?>	
<?php
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
        $fincet = date('Ymd',strtotime('+3 month',strtotime($fonctions->liredbconstante($constante))));
    }
    if (date("Ymd")>=$debutcet and date("Ymd")<=$fincet and !$agentstructure->estbibliotheque())
    {    
?>  
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gerer_alimentationCET.php'; ?>
                        <form name='alim_cet' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Alimentation du CET</a>
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
        $fincet = date('Ymd',strtotime('+3 month',strtotime($fonctions->liredbconstante($constante))));
    }
    if (date("Ymd")>=$debutcet and date("Ymd")<=$fincet and !$agentstructure->estbibliotheque())
    {    
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gerer_optionCET.php'; ?>
                        <form name='option_cet' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="agentid" value="<?php echo $user->agentid(); ?>"> 
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Droit d'option sur CET</a>
                    </li>
<?php
    }
    $dbconstante = 'URL_G2TMANUEL';
    $urlg2tmanuel = '';
    if ($fonctions->testexistdbconstante($dbconstante)) { $urlg2tmanuel = trim($fonctions->liredbconstante($dbconstante)); }
    if (trim($urlg2tmanuel)!='')
    {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <form name='agent_aide' method='get' TARGET=_BLANK action="<?php echo $urlg2tmanuel; ?>">
                        </form> 
                        <a role="menuitem" href="<?php echo $urlg2tmanuel; ?>" onclick="return false;">Manuel utilisateur</a>
<!--                        <a role="menuitem" href="<?php echo $urlg2tmanuel; ?>" onclick="this.parentNode.click(); return false;">Manuel utilisateur</a> -->
                    </li>
<?php
    }
?>
                </ul>
            </li>
<!---------------------------------------------------
                  Menu Consultant  
---------------------------------------------------->
<?php
    if ($user->estconsultant()) 
    {
?>
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#consultant">
                    MENU CONSULTANT
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="consultant" class="niveau2">
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'valider_demande.php'; ?>
                        <form name='consult_valid_conge' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_CONSULTANT; ?>"> 
                            <input type="hidden" name="previous" value="no">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Avis sur des demandes en attente</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'structure_planning.php'; ?>
                        <form name='consult_struct_planning' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_CONSULTANT; ?>">
                            <input type="hidden" name="previous" value="no">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning</a>
                    </li>
                </ul>
            </li>
<?php
    }
?>
<!---------------------------------------------------
                  Menu Responsable  
---------------------------------------------------->
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
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#responsable">
                    MENU RESPONSABLE
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="Responsable" class="niveau2">
<?php
        if (!$estrespdebibliotheque)
        {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_dossier.php'; ?>
                        <form name='resp_parametre' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="action" value="modif"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage des agents et des structures</a>
                    </li>
<?php
        }
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_teletravail.php'; ?>
                        <form name='resp_gest_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des conventions de télétravail</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#anneecourante">
                            Gestion de l'année en cours
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="anneecourante" class="niveau3">  <!-- class="niveau3" -->
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'structure_planning.php'; ?>
                                <form name='resp_struct_planning' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de la structure</a>
                            </li>
<?php
        }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_demande.php'; ?>
                                <form name='resp_valid_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des demandes en attente</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_demande.php'; ?>
                                <form name='resp_gest_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Annulation de congé ou d'absence</a>
                            </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'deplacer_teletravail.php'; ?>
                                <form name='resp_depla_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des modifications de jours de télétravail</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='resp_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges">
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé pour un agent</a>
                            </li>
<?php
        }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='resp_absence' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="absence"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande d'absence pour un agent</a>
                            </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'ajouter_conges.php'; ?>
                                <form name='resp_ajout_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des jours de récupération pour un agent</a>
                            </li>
<?php
            // Si on est 6 mois avant la fin de la période ==> On peut saisir des jours par anticipation
            $datetemp = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("-6month", $timestamp)); // On remonte de 6 mois
            if (date("Ymd") > $datetemp) 
            {
?>				
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='resp_conge_anticipe' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges"> 
                                    <input type="hidden" name="congeanticipe" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé par anticipation pour un agent</a>
                            </li>
<?php
            }
        }
?>								
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_tpspartiel.php'; ?>
                                <form name='resp_valid_tpspartiel' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des temps partiels</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'saisir_tpspartiel.php'; ?>
                                <form name='resp_tpspartiel' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'un temps partiel pour un agent</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_solde.php'; ?>
                                <form name='resp_aff_solde' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>">
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage du solde des agents de la structure</a>
                            </li>
<?php
        }
?>
                        </ul>
                    </li>
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#anneeprecedente">
                            Gestion de l'année précédente
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="anneeprecedente" class="niveau3">  <!-- class="niveau3" -->
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'structure_planning.php'; ?>
                                <form name='resp_struct_planning_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de la structure</a>
                            </li>
<?php
        }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_demande.php'; ?>
                                <form name='resp_valid_conge_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des demandes en attente</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_demande.php'; ?>
                                <form name='resp_gest_conge_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="responsableid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Annulation de congé ou d'absence</a>
                            </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='resp_conge_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé pour un agent</a>
                            </li>
<?php
        }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='resp_absence_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="absence"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande d'absence pour un agent</a>
                            </li>
<?php
        if (!$estrespdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_solde.php'; ?>
                                <form name='resp_aff_solde_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RESPONSABLE; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage du solde des agents de la structure</a>
                            </li>
<?php
        }
?>
                        </ul>
                    </li>
                </ul>
            </li>
<?php
    }
?>

<!------------------------------------------------------------
                Menu Gestionnaire
------------------------------------------------------------->
<?php
    if ($user->estgestionnaire()) 
    {
        $structgestliste = $user->structgestliste();
        $estgestdebibliotheque = true;
        foreach ((array)$structgestliste as $struct)
        {
            if (!$struct->estbibliotheque())
            {
                $estgestdebibliotheque = false;
                break;
            }
        }
?>
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#gestionnaire">
                    MENU GESTIONNAIRE
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="Gestionnaire" class="niveau2">
<?php
        if (!$estgestdebibliotheque)
        {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_dossier.php'; ?>
                        <form name='gest_parametre_modif' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="action" value="modif"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage des agents et des structures</a>
                    </li>
<?php
        }
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_teletravail.php'; ?>
                        <form name='gest_gest_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des conventions de télétravail</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#gestanneecourante">
                            Gestion de l'année en cours
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="gestanneecourante" class="niveau3">   <!-- class="niveau3" -->
<?php
        if (!$estgestdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'structure_planning.php'; ?>
                                <form name='gest_struct_planning' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de la structure</a>
                            </li>
<?php
            $structgestliste = $user->structgestliste();
            $code = null;
            foreach ((array)$structgestliste as $structure)
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
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='gest_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges">
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé pour un responsable</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='gest_absence' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="absence"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande d'absence pour un responsable</a>
                            </li>
<?php
            }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_demande.php'; ?>
                                <form name='gest_valid_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des demandes en attente</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_demande.php'; ?>
                                <form name='gest_gest_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="gestionnaireid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Annulation de congé ou d'absence</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_tpspartiel.php'; ?>
                                <form name='gest_valid_tpspartiel' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des temps partiels</a>
                            </li>

                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'deplacer_teletravail.php'; ?>
                                <form name='gest_depla_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des modifications de jours de télétravail</a>
                            </li>
<?php
        }
?>
<?php
        if (!$estgestdebibliotheque)
        {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_solde.php'; ?>
                                <form name='gest_aff_solde' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage du solde des agents de la structure</a>
                            </li>
<?php
        }
?>
                        </ul>
                    </li>
<?php
        if (!$estgestdebibliotheque)
        {
?>
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#gestanneeprecedente">
                            Gestion de l'année précédente
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="gestanneeprecedente" class="niveau3">  <!-- class="niveau3" -->
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'structure_planning.php'; ?>
                                <form name='gest_struct_planning_previous' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Planning de la structure</a>
                            </li>
<?php
            $structgestliste = $user->structgestliste();
            $code = null;
            foreach ((array)$structgestliste as $structure)
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
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='gest_conge_prev' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges">
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congé pour un responsable</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='gest_absence_prev' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="gestionnaire" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="absence"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande d'absence pour un responsable</a>
                            </li>
<?php
            }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_demande.php'; ?>
                                <form name='gest_valid_conge_prev' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des demandes en attente</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_solde.php'; ?>
                                <form name='gest_aff_solde_ant' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_GESTION; ?>"> 
                                    <input type="hidden" name="previous" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage du solde des agents de la structure</a>
                            </li>
                        </ul>
                    </li>
<?php
        }
?>
                </ul>
            </li>
<?php
    }
?>
<!------------------------------------------------------------
                Menu Gestion RH
------------------------------------------------------------->
<?php
    if ($user->estprofilrh()) 
    {
?>
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#gestionrh">
                    MENU GESTION RH
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="GestionRH" class="niveau2">
<?php
                // PROFIL RH ==> GESTIONNAIRE RH DE CET / GESTIONNAIRE RH DE CONGES / GESTIONNAIRE RH DE TELETRAVAIL
                if ($user->estprofilrh(agent::PROFIL_RHCET) or $user->estprofilrh(agent::PROFIL_RHCONGE) or $user->estprofilrh(agent::PROFIL_RHTELETRAVAIL)) 
                {
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_delegation.php'; ?>
                        <form name='rh_gest_deleg' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des délégations sur les structures</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_structure.php'; ?>
                        <form name='rh_struct_gest' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage des structures</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'modifieresignature.php'; ?>
                        <form name='rh_modifcircuitesign' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification d'un circuit eSignature pour un agent</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'modifier_XMLcircuit.php'; ?>
                        <form name='rh_modifier_XMLcircuit' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification des circuits eSignature</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'saisir_tpspartiel.php'; ?>
                        <form name='resp_tpspartiel' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                            <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form>
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'un temps partiel pour un agent</a>
                    </li>


<?php 
                    if ($user->estprofilrh(agent::PROFIL_RHTELETRAVAIL))
                    {
?>					
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#teletravail">
                            Gestion du télétravail
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="teletravail" class="niveau3">  <!-- class="niveau3" -->
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_teletravail.php'; ?>
                                <form name='rh_gest_teletravail_noesignature' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type="hidden" name="noesignature" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des conventions de télétravail<br>(hors eSignature)</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_teletravail.php'; ?>
                                <form name='rh_gest_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des conventions de télétravail<br>(avec eSignature)</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_info_teletravail.php'; ?>
                                <form name='rh_affiche_info_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Nombre de jours de télétravail</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'suivi_teletravail.php'; ?>
                                <form name='rh_suivi_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Suivi de l'avancement des demandes de télétravail</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_teletravail.php'; ?>
                                <form name='rh_affiche_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage des conventions de télétravail par structure</a>
                            </li>
                        </ul>
                    </li>
<?php 
                    } // Fin du test si utilisateur est PROFIL_RHTELETRAVAIL
                    if ($user->estprofilrh(agent::PROFIL_RHCET))
                    {
?>					
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#cet">
                            Gestion des CET
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="cet" class="niveau3">  <!-- class="niveau3" -->
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'utilisation_cet.php'; ?>
                                <form name='gestrh_utilisationcet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des congés sur CET</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gerer_cet.php'; ?>
                                <form name='gestrh_gestcet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion d'un CET</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gerer_cet_hors_esignature.php'; ?>
                                <form name='gestrh_gestcet_hors_esignature' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion d'un CET (hors eSignature)</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'creer_cet.php'; ?>
                                <form name='gestrh_creercet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Reprise d'un CET existant</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gerer_alimentationCET.php'; ?>
                                <form name='rh_alimentation_cet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Alimentation du CET</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gerer_optionCET.php'; ?>
                                <form name='rh_option_cet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Droit d'option sur CET</a>
                            </li>
                        </ul>
                    </li>
<?php 
                    } // Fin du test si utilisateur est PROFIL_RHCET
                    if ($user->estprofilrh(agent::PROFIL_RHCONGE))
                    {
?>
                    <li role="none">
                        <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#conges">
                            Gestion des congés
                            <svg xmlns="http://www.w3.org/2000/svg" class="right" width="9" height="12" viewBox="0 0 9 12">
                                <polygon points="0 1, 0 11, 8 6"></polygon>
                            </svg>
                        </a>
                        <ul role="menu" aria-label="conges" class="niveau3">  <!-- class="niveau3" -->
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='rh_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type="hidden" name="rh_mode" value="yes">
                                    <input type="hidden" name="show_cet" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congés (hors CET)</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'etablir_demande.php'; ?>
                                <form name='rh_conge_cet' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="responsable" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="typedemande" value="conges"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type="hidden" name="rh_mode" value="yes">
                                    <input type="hidden" name="show_cet" value="yes">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Saisie d'une demande de congés sur CET</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_demande.php'; ?>
                                <form name='rh_gest_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Annulation de congés imputés sur le CET</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_info_agent.php'; ?>
                                <form name='affiche_info_agent' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Consultation des congés d'un agent</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'modif_solde.php'; ?>
                                <form name='modif_solde' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">					
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification du solde de congés d'un agent</a>
                            </li>
<?php
                    $dbconstante = "FONCTIONCONGSUP";
                    $congessuppfonction = 'n';
                    if ($fonctions->testexistdbconstante($dbconstante)) { $congessuppfonction = $fonctions->liredbconstante($dbconstante); }
                    // Si la fonction de demande de validation par la DRH n'est pas activée => On fait comme d'habitude
                    if ($fonctions->convertvaluetobool($congessuppfonction))
                    {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_jourscomplementaires.php'; ?>
                                <form name='rh_valid_congesup' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des jours de récupération</a>
                            </li>
<?php
                    }
                    $dbconstante = "FONCTIONABSENCE";
                    $absencefonction = 'n';
                    if ($fonctions->testexistdbconstante($dbconstante)) { $absencefonction = $fonctions->liredbconstante($dbconstante); }
                    // Si la fonction de demande de validation des absences par la DRH n'est pas activée => On fait comme d'habitude
                    if ($fonctions->convertvaluetobool($absencefonction))
                    {
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'valider_demande.php'; ?>
                                <form name='rh_valid_demande' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Validation des autorisations d'absence</a>
                            </li>
<?php
                    }
?>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'ajouter_conges.php'; ?>
                                <form name='rh_ajout_conge' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des jours de récupération pour un agent</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'controlrecuperation.php'; ?>
                                <form name='rh_controlrecup' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Contrôle des recupérations</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_solde.php'; ?>
                                <form name='rh_aff_solde' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>"> 
                                    <input type="hidden" name="mode" value="<?php echo MODE_RH; ?>"> 
                                    <input type="hidden" name="previous" value="no">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form>
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage du solde des agents d'une structure</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'affiche_jourscomplementaires.php'; ?>
                                <form name='rh_affiche_jourscomplementaires' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage des jours complémentaires</a>
                            </li>
                            <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                                <?php $destpagename = 'gestion_periodeobligatoire.php'; ?>
                                <form name='rh_gestperiodeoblig' method='post' action="<?php echo "$destpagename"; ?>">
                                    <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                                    <input type='hidden' class='pagepath' name='pagepath' value=''>
                                </form> 
                                <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des périodes obligatoires</a>
                            </li>
                        </ul>
                    </li>
<?php
                    } // Fin du test si utilisateur est PROFIL_RHCONGE
?>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_inputfiles.php'; ?>
                        <form name='rh_affiche_inputfiles' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage des données d'interface</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'g2t_param.php'; ?>
                        <form name='rh_affiche_g2t_param' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage</a>
                    </li>
                </ul>
<?php
                }
?>
            </li>
<?php
    }
?>
<!------------------------------------------------------------
                Menu Administrateur
------------------------------------------------------------->
<?php
    if ($realuser->estadministrateur()) 
    {
?>
            <li role="none">
                <a role="menuitem" aria-haspopup="true" aria-expanded="false" href="#admin">
                    MENU ADMINISTRATEUR
                    <svg xmlns="http://www.w3.org/2000/svg" class="down" width="12" height="9" viewBox="0 0 12 9">
                        <polygon points="1 0, 11 0, 6 8"></polygon>
                    </svg>
                </a>
                <ul role="menu" aria-label="Administrateur" class="niveau2">
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'admin_maintenance.php'; ?>
                        <form name='admin_mode_maintenance' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Activation/désactivation maintenance</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_structure.php'; ?>
                        <form name='admin_struct_gest' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type="hidden" name="mode" value="">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage des structures</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'admin_substitution.php'; ?>
                        <form name='admin_subst_agent' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Se faire passer pour un autre agent</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'import_conges.php'; ?>
                        <form name='admin_import_conges' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Import des congés</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_demandeCET.php'; ?>
                        <form name='admin_affiche_demandeCET' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage d'une demande sur CET/eSignature</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_info_teletravail.php'; ?>
                        <form name='admin_affiche_info_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Nombre théorique de jours de télétravail</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'g2t_param.php'; ?>
                        <form name='admin_affiche_g2t_param' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Paramétrage</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'suivi_teletravail.php'; ?>
                        <form name='admin_suivi_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Suivi de l'avancement des demandes de télétravail</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_inputfiles.php'; ?>
                        <form name='admin_affiche_inputfiles' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage des données d'interface</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_jourscomplementaires.php'; ?>
                        <form name='admin_affiche_jourscomplementaires' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Affichage des jours complémentaires</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_teletravail.php'; ?>
                        <form name='admin_affiche_teletravail' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Liste des conventions de télétravail par structure</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'controlrecuperation.php'; ?>
                        <form name='admin_controlrecup' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Contrôle des recupérations</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'gestion_periodeobligatoire.php'; ?>
                        <form name='admin_gestperiodeoblig' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Gestion des périodes obligatoires</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'modifieresignature.php'; ?>
                        <form name='admin_modifcircuitesign' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification d'un circuit eSignature pour un agent</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'modifier_XMLcircuit.php'; ?>
                        <form name='admin_modifier_XMLcircuit' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Modification des circuits eSignature</a>
                    </li>
                    <li role="none" onclick="this.getElementsByTagName('form')[0].submit();">
                        <?php $destpagename = 'affiche_info_conges.php'; ?>
                        <form name='admin_solde_conges' method='post' action="<?php echo "$destpagename"; ?>">
                            <input type="hidden" name="userid" value="<?php echo $user->agentid(); ?>">
                            <input type='hidden' class='pagepath' name='pagepath' value=''>
                        </form> 
                        <a role="menuitem" href="<?php echo "$destpagename"; ?>" onclick="this.parentNode.click(); return false;">Synthèse des congés</a>
                    </li>
                </ul>
            </li>
<?php
	}
?> 
        </ul>
    </nav>
</div>

<script>
    var mainmenu = document.querySelector('.mainmenu');
    if (mainmenu)
    {
        var listesousmenu = mainmenu.querySelectorAll('[role=menuitem][aria-haspopup=true]');
        // ==> On a ici la liste des <a> de type menuitem et qui ont un sous-menu. Donc on va chercher s'il y a un formulaire dans le parent
        if (listesousmenu.length > 0)
        {
            for (let index = 0 ; index < listesousmenu.length ; index++)
            {
                let sousmenu = listesousmenu[index].parentNode;
                if (!sousmenu.querySelector('form'))
                {
                    sousmenu.hidden = true;
                    sousmenu.style.display = "none";
                }
            }
        }
    }

    // On construit tous les chemins des pages dans le menu (input hidden pagepath)
    function getpagepath(element)
    {
        if (element.classList.contains('pagepath'))
        {
            let link = element.parentElement.nextElementSibling;
            return getpagepath(link);
        }
        if (element.classList.contains('mainmenu'))
        {
            return '';
        }
        if (element.tagName.toUpperCase() == 'A')
        {
            let previouselement = element.parentElement;  
            let parentpath = getpagepath(previouselement);
            if (parentpath.trim().length > 0)
            {
                parentpath = parentpath + " &rarr; "; // ' - '
            }
            return  parentpath + element.outerText.trim();
        }
        else
        {
            let previouselement = element.previousElementSibling;
            if (!previouselement)
            {
                previouselement = element.parentElement;
            }
            return getpagepath(previouselement);
        }
    }

    let pagepathlist = mainmenu.getElementsByClassName('pagepath');
    for (let index = 0 ; index < pagepathlist.length ; index++)
    {
        let pagepath = pagepathlist[index];
        let pathvalue = getpagepath(pagepath);
        pagepath.value = pathvalue;
    }

    // Si on n'a pas trouvé le chemin de la page (<=> headerpagepath est vide) on cherche le chemin dans le menu de la page courante
    let headerpagepath = document.querySelector('.headerpagepath'); 
    if (headerpagepath.innerHTML.trim() == '')
    {
        let selector = "form[action='<?php echo basename($_SERVER["PHP_SELF"]); ?>']";
        let accueilform = document.querySelector('.mainmenu').querySelectorAll(selector)[0];
        if (accueilform)
        {
            headerpagepath.innerHTML = accueilform.querySelector('.pagepath').value;
        }
    }

<?php
    if (date('m') == 12 or (date('m') == 1 and date('d') < 16))
    {
        $path = $fonctions->etablissementimagepath() . "/Chapeau_Noel.png";
        list($width, $height, $imagetype) = getimagesize("$path");
        $typeimage = image_type_to_extension($imagetype,false);
        if ($typeimage===false) // Si on n'a pas pu déterminé le type d'image => On récupère l'extension du fichier
        {
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("imagetype = $imagetype => extension non définie"));
            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
        }
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
?>
        window.addEventListener("load", displayeventimg, true);

        function displayeventimg()
        {
            var mainmenu = document.querySelector('.mainmenu');
            if (mainmenu)
            {
                let imgnoel = document.createElement("img");
                imgnoel.style.display = 'block';
                imgnoel.style.position = 'absolute';
                imgnoel.src = '<?php echo $base64; ?>';
                imgnoel.style.margin = 0;
                imgnoel.style.padding = 0;
                imgnoel.style.width = '<?php echo $width; ?>px'; // '60px';
                imgnoel.style.height = '<?php echo $height; ?>px'; //imgnoel.style.width;
                document.body.appendChild(imgnoel);
                // var imgnoel = document.getElementById('imagenoel');
                // imgnoel.style.transform = 'rotate(25deg)';
                var menuniveau1 = mainmenu.querySelectorAll('.niveau1>li')
                if (menuniveau1 && menuniveau1.length>0)
                {
                    var lastniveau1 = menuniveau1[menuniveau1.length-1];
                    var bodyRect = document.body.getBoundingClientRect();
                    var elemRect = lastniveau1.getBoundingClientRect();
                    var imgnoelRect = imgnoel.getBoundingClientRect();
                    console.log(parseInt(imgnoelRect.width), parseInt(imgnoelRect.height));
                    var cornerY  = elemRect.top - bodyRect.top - parseInt(imgnoelRect.height/2);
                    var cornerX = elemRect.left - bodyRect.left + elemRect.width - parseInt(imgnoelRect.width/2);
                    // console.log(cornerY, cornerX);
                    console.log(parseInt(cornerY) + 'px');
                    imgnoel.style.top = parseInt(cornerY) + 'px';
                    console.log(parseInt(cornerX) + 'px');
                    imgnoel.style.left = parseInt(cornerX) + 'px';
                }
            }
        }
<?php
    }
?>

</script>
<br><br><br>

<?php
    // echo "<img id='imagenoel' class='imagenoel' src='" . $base64 . "' />";
    // echo "<img id='imagenoel' src='" . $base64 . "' style='display: none' />";
?>