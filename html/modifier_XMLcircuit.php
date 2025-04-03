<?php

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
        exit();
    }
    
    // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
    $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
    if ($CASuserId===false)
    {
        // Ce n'est pas un administrateur
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        echo "<script>alert('Accès réservé aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
        exit();
    }
    
    $user = new agent($dbcon);
    $user->load($userid);


    $removesignatairepath = "";
    if (isset($_POST["removesignatairepath"]))
    {
        $removesignatairepath = $_POST["removesignatairepath"];
    }

    $filename = '';
    if (isset($_POST["xmlfilename"]))
    {
        $filename = $_POST["xmlfilename"];
    }

    $addsignatairepath = '';
    if (isset($_POST["addsignatairepath"]))
    {
        $addsignatairepath = $_POST["addsignatairepath"];
    }
    $addsignatairetype =null;
    if (isset($_POST["addsignatairetype"]))
    {
        $addsignatairetype = $_POST["addsignatairetype"];
    }
    $addsignataireid = '';
    if (isset($_POST["addsignataireid"]))
    {
        $addsignataireid = $_POST["addsignataireid"];
    }
    
    $circuitpath = '';
    if (isset($_POST["circuitpath"]))
    {
        $circuitpath = $_POST["circuitpath"];
    }

    $nbsignataire = 0;
    if (isset($_POST["nbsignataire"]))
    {
        $nbsignataire = $_POST["nbsignataire"];
    }
    
    require ("includes/menu.php");

    //var_dump($_POST);

    if ($addsignatairepath!='')
    {
        if ($filename!='')
        {
            $xmldom = new DOMDocument();
            // Permet de conserver une indentation/structuration dans le fichier XML
            $xmldom->preserveWhiteSpace = false;
            $xmldom->formatOutput = true;
        
            // On cherche le fichier XML représentant le circuit
            if (!file_exists($filename))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'existe pas.");
                exit;
            }
        
            // On charge le document XML
            $xmldom->validateOnParse = true;
            $valid = @$xmldom->load($filename);
            if (!$valid)
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "La syntaxe du fichier " . basename($filename) . " n'est pas correcte => Vérifiez la DTD.");
                exit;
            }
        
            // On valide la syntaxe du fichier XML avec la DTD 
            $valid = @$xmldom->validate();
            if (!$valid)
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'est pas un fichier XML valide => Vérifiez la DTD.");
                exit;
            }
        
            // Attention : Le DOM doit être chargé au moment de la création du DOMXPath
            // Sinon, il ne trouve aucun noeux
            $xmlpath = new DOMXPath($xmldom);
            $signataireid= "";
            if ($addsignatairetype == '')
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Vous n'avez pas sélectionné le type de signataire.");
            }
            elseif (!is_numeric($addsignataireid) and $addsignatairetype == esignature::TYPESIGNATAIRE_AGENT)
            {
                $signataire = $fonctions->createldapagentfromuid($addsignataireid);
                if ($signataire !== false)
                {
                    $signataireid = $signataire->agentid();
                }
            }
            elseif ($addsignatairetype == esignature::TYPESIGNATAIRE_RESP_STRUCT)
            {
                $signataireid = $addsignataireid;
            }

            if ($signataireid == "" and in_array($addsignatairetype, array(esignature::TYPESIGNATAIRE_AGENT, esignature::TYPESIGNATAIRE_RESP_STRUCT)))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "L'identifiant du signataire (agent ou structure) est vide.");
            }
            elseif (strlen($addsignatairetype . "") > 0)
            {
                $rootnode = $xmlpath->query($addsignatairepath)[0];
                $newnode = $xmldom->createElement("SIGNATAIRE",$signataireid);
                $newnode->setAttribute("TYPESIGNATAIRE",$addsignatairetype);

                $signatairelist = $xmlpath->query("SIGNATAIRE",$rootnode);
                $dejadedans = false;
                foreach($signatairelist as $signataire)
                {
                    if ($signataire->isEqualNode($newnode))
                    {
                        $dejadedans = true;
                        break;
                    }
                }

                if ($dejadedans)
                {
                    echo $fonctions->showmessage(fonctions::MSGWARNING, "Le signataire existe déjà dans ce niveau.");
                }
                else
                {
                    $rootnode->appendChild($newnode);
                    $valid = @$xmldom->validate();
                    if (!$valid)
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " ne respecte pas la DTD.\nSauvegarde impossible.");
                    }
                    else
                    {
                        $xmldom->normalizeDocument();
                        if ($xmldom->save($filename) === false)
                        {
                            echo $fonctions->showmessage(fonctions::MSGERROR, "Problème lors de l'enregistrement des modifications.");
                        }
                    }
                }
            }
        }
    }

    if ($removesignatairepath != '')
    {
        if ($filename!='')
        {
            $xmldom = new DOMDocument();
            // Permet de conserver une indentation/structuration dans le fichier XML
            $xmldom->preserveWhiteSpace = false;
            $xmldom->formatOutput = true;
        
            // On cherche le fichier XML représentant le circuit
            if (!file_exists($filename))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'existe pas.");
                exit;
            }
      
            // On charge le document XML
            $xmldom->validateOnParse = true;
            $valid = @$xmldom->load($filename);
            if (!$valid)
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "La syntaxe du fichier " . basename($filename) . " n'est pas correcte => Vérifiez la DTD.");
                exit;
            }
      
            // On valide la syntaxe du fichier XML avec la DTD 
            $valid = @$xmldom->validate();
            if (!$valid)
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'est pas un fichier XML valide => Vérifiez la DTD.");
                exit;
            }
        
            // Attention : Le DOM doit être chargé au moment de la création du DOMXPath
            // Sinon, il ne trouve aucun noeux
            $xmlpath = new DOMXPath($xmldom);

            $nodetoremove = $xmlpath->query($removesignatairepath)[0];
            //var_dump($nodetoremove);
            if (!is_null($nodetoremove))
            {
                $circuit = $xmlpath->query("$circuitpath")[0];
                //var_dump($nbsignataire);
                //var_dump(count($xmlpath->query('ETAPE/SIGNATAIRE',$circuit)));
                if ($nbsignataire == count($xmlpath->query('ETAPE/SIGNATAIRE',$circuit)))
                {
                    $nodetoremove->remove();
                    $valid = @$xmldom->validate();
                    if (!$valid)
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " ne respecte pas la DTD.\nSauvegarde impossible.");
                    }
                    else
                    {
                        $xmldom->normalizeDocument();
                        if ($xmldom->save($filename) === false)
                        {
                            echo $fonctions->showmessage(fonctions::MSGERROR, "Problème lors de l'enregistrement des modifications.");
                        }
                    }
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGWARNING, "Impossible de supprimer le signataire.");
                }
            }   
        }
    }

?>
    <!-- Fenètre modale -->
    <div id="newrecipientmodal" class="divmodal">
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
                <div id='divselecttype'>
                    Type de signataire : 
                    <select id='newrecipienttype' name='newrecipienttype' onchange='addreciepientchangetype();'>
                        <option value=''><?php echo "--- Sélectionnez un type de signataire ---"; ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_DEMANDEUR; ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_DEMANDEUR); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESPONSABLE ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESPONSABLE); ?></option>
                        <option value='<?php echo esignature::TYPESIGNATAIRE_RESPONSABLE2 ?>'><?php echo $esignature->typesignatairelibelle(esignature::TYPESIGNATAIRE_RESPONSABLE2); ?></option>
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
<?php
                        $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
                        $query = mysqli_query($dbcon, $sql);
                        $erreur = mysqli_error($dbcon);
                        if ($erreur != "") {
                            $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
                            echo $errlog . "<br/>";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                        $structureid=null;

                        $structliste = array();
                        while ($result = mysqli_fetch_row($query)) 
                        {
                            $struct = new structure($dbcon);
                            $struct->load($result[0]);
                            $structliste[$result[0]] = $struct;
                            $structliste = $structliste + (array)$struct->structurefille(true,0);
                        }
                        $fonctions->afficherlistestructureindentee($structliste,false, null);
                        unset($structliste);
?>
                    </select>
                </div>
            </form>
            <menu>
                <center>
                    <button id="questionconfirmBtn" value="" class='g2tbouton g2tvalidebouton'>Ok</button>  <!-- javaconfirmbutton -->
                    <button id="questioncancelBtn" value="cancel" class='g2tbouton g2tannulerbouton'>Annuler</button> <!-- javacancelbutton -->
                </center>
            </menu>
        </div>
    </div>


<script>
    // Récupération des objets de la fenêtre modale
    var newrecipientmodal = document.getElementById("newrecipientmodal");
    var newrecipientconfirmBtn = newrecipientmodal.querySelector('#questionconfirmBtn');
    var newrecipientlabeltext = newrecipientmodal.querySelector('#questionlabeltext');
    var newrecipientcancelBtn = newrecipientmodal.querySelector('#questioncancelBtn'); 
    var divstructid = newrecipientmodal.querySelector('#divstructid');
    var divagentid = newrecipientmodal.querySelector('#divagentid');
    var divselecttype = newrecipientmodal.querySelector('#divselecttype');
    var labelmodalheader = newrecipientmodal.querySelector('#labelmodalheader');
    var imgmodallist = newrecipientmodal.querySelectorAll(".imagedialog")
    var newidsignataire = newrecipientmodal.querySelector('#newidsignataire');
    var newstructureid = newrecipientmodal.querySelector('#newstructureid');
    var selecttype = newrecipientmodal.querySelector('#newrecipienttype');
    var usersignataire = newrecipientmodal.querySelector('#usersignataire');

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
        newrecipientlabeltext.parentElement.classList.remove('centeraligntext');
        newrecipientcancelBtn.classList.remove('g2tannulerbouton');
        newrecipientcancelBtn.classList.remove('g2tokbouton');
        newrecipientconfirmBtn.classList.remove('g2tvalidebouton');
    }

    newrecipientcancelBtn.onclick = function() 
    {
        var inputsignatairepath = document.getElementById('removesignatairepath');
        inputsignatairepath.value = '';
        var inputsignatairepath = document.getElementById('addsignatairepath');
        inputsignatairepath.value = '';
        masquerimgmodal();
        newrecipientmodal.style.display = "none";
        return false;
    }

    newrecipientconfirmBtn.onclick = function() 
    {
        newrecipientmodal.style.display = "none";
        masquerimgmodal();

        // Si le div pour selectionner le type d'ajout n'est pas visible => On est en suppression
        if (divselecttype.hidden == true)
        {
            var inputsignatairepath = document.getElementById('removesignatairepath');
            var closestform = inputsignatairepath.closest("form");
            closestform.submit();
        }
        // Sinon on est en ajout
        else
        {
            var inputsignatairepath = document.getElementById('addsignatairepath');
            var closestform = inputsignatairepath.closest("form");
            var addsignatairetype = closestform.querySelector('#addsignatairetype');
            addsignatairetype.value = selecttype.value;

            var addsignataireid = closestform.querySelector('#addsignataireid');
            var divagentid = newidsignataire.closest("div");
            var divstructid = newstructureid.closest("div");
            // On doit vérifier si le DIV de l'agentid est visible ou pas
            if (!divagentid.hidden)
            {
                addsignataireid.value = newidsignataire.value;
            }
            // On doit vérifier si le DIV de la structureid est visible ou pas
            else if (!divstructid.hidden)
            {
                addsignataireid.value = newstructureid.value;
            }
            else
            {
                addsignataireid.value = '';
            }
            closestform.submit();
        }
    }

    function addreciepientchangetype()
    {
        var currentselect = document.activeElement;
        if (currentselect.value == '<?php echo esignature::TYPESIGNATAIRE_AGENT; ?>' )
        {
            divagentid.hidden = false;
            divstructid.hidden = !divagentid.hidden;
        }
        else if (currentselect.value == '<?php echo esignature::TYPESIGNATAIRE_RESP_STRUCT; ?>')
        {
            divagentid.hidden = true;
            divstructid.hidden = !divagentid.hidden;
        }
        else
        {
            divagentid.hidden = true;
            divstructid.hidden = divagentid.hidden;
        }
    }

    function confirmdeletesignataire(elementid)
    {
        var activeelement = document.getElementById(elementid);
        divstructid.hidden = true;
        divagentid.hidden = true;
        divselecttype.hidden = true;
        labelmodalheader.innerHTML = 'Suppression d\'un signataire';

        if (!activeelement.classList.contains("XMLsignataire"))
        {
            return;
        }
        
        var idetape = elementid.split("/");
        idetape.pop(); // Supprime le dernier élément du tableau 
        idetape = idetape.join("/");
        var currentetape = document.getElementById(idetape);
        var numetape = currentetape.getAttribute('data-etape');

        var xmlsignatairelist = currentetape.parentElement.getElementsByClassName('XMLsignataire');
        if (xmlsignatairelist.length <= 1)
        {
            masquerimgmodal('error');
            newrecipientcancelBtn.textContent = "Ok";
            newrecipientcancelBtn.hidden = false;
            newrecipientcancelBtn.classList.add('g2tokbouton');
            newrecipientconfirmBtn.hidden = true;
            newrecipientlabeltext.parentElement.classList.add('centeraligntext');
            newrecipientlabeltext.innerHTML = 'Il n\'y a qu\'un seul signataire dans l\'étape ' + numetape + '<br>Vous ne pouvez pas le supprimer';
        }
        else
        {
            masquerimgmodal('suppression');
            var attributelist = activeelement.parentElement.getElementsByClassName("XMLAttribute");
            var typetexte = '';
            var idtexte = '';
            for (var index = 0 ; index < attributelist.length ; index++)
            {
                var datatext = attributelist[index].getAttribute('data-text');
                var dataname = attributelist[index].getAttribute('data-name');
                if (datatext != null && datatext != undefined && datatext != '')
                {
                    typetexte = datatext.toLowerCase() + " ";
                }
                if (dataname != null && dataname != undefined && dataname != '')
                {
                    idtexte = '(' + dataname + ') ';
                }
            }

            newrecipientlabeltext.innerHTML = 'Confirmez vous la suppression d\'' + typetexte + idtexte + 'dans l\'étape ' + numetape + ' ? ';

            var input = document.getElementById('removesignatairepath');
            input.value = elementid

            newrecipientcancelBtn.textContent = "Non";
            newrecipientcancelBtn.hidden = false;
            newrecipientcancelBtn.classList.add('g2tannulerbouton');
            newrecipientconfirmBtn.textContent = "Oui";
            newrecipientconfirmBtn.hidden = false;
            newrecipientconfirmBtn.classList.add('g2tvalidebouton');
        }
        newrecipientmodal.style.display = "block";
    }

    function confirmaddsignataire(elementid)
    {
        masquerimgmodal('ajout');
        var currentetape = document.getElementById(elementid);
        var numetape = currentetape.getAttribute('data-etape');
        newrecipientlabeltext.innerHTML = 'Veuillez indiquer les informations du nouveau signataire dans l\'étape ' + numetape + ' ? ';
        var input = document.getElementById('addsignatairepath');
        input.value = elementid;

        divstructid.hidden = true;
        divagentid.hidden = true;
        divselecttype.hidden = false;
        labelmodalheader.innerHTML = 'Ajout d\'un signataire';

        newrecipientcancelBtn.textContent = "Annuler";
        newrecipientcancelBtn.hidden = false;
        newrecipientcancelBtn.classList.add('g2tannulerbouton');
        newrecipientconfirmBtn.textContent = "Enregistrer";
        newrecipientconfirmBtn.hidden = false;
        newrecipientconfirmBtn.classList.add('g2tvalidebouton');

        selecttype.selectedIndex = 0;
        usersignataire.value = '';
        newidsignataire.value = '';
        newstructureid.selectnewstructid = 0;
        newrecipientmodal.style.display = "block";
    }

    function initDossierDeplierJs(id)
    {
        var oArbo = document.getElementById(id),
            aDossier = oArbo.getElementsByTagName('span');
        for(var i = 0; i <aDossier.length; i++)
        {
            var oUl = aDossier[i].parentNode.getElementsByTagName('ul')
            if(oUl.length == 0)
            {
                continue;
            }
            if (aDossier[i].classList.contains("XMLsignataire") == false)
            {
                aDossier[i].addEventListener('click',function(oEvent)
                {
                    var oBt = oEvent.currentTarget,
                        sClass="show",
                        bHasClass= oBt.classList.contains(sClass);
                    if (bHasClass)
                    {
                        oBt.classList.remove(sClass);
                    }
                    else
                    {
                        oBt.classList.add(sClass);
                    }
                });
            }
        }
    }

    document.addEventListener('DOMContentLoaded',function()
    {
        var rootid = 'divcircuit';
        var rootnode = document.getElementById(rootid);
        if (rootnode)
        {
            initDossierDeplierJs(rootid);

            var rootnode = document.getElementById(rootid);
            var etapeliste = rootnode.querySelectorAll(".XMLEtape");
<?php
            if ($removesignatairepath != "")
            {
                $pathelement = explode("/",$removesignatairepath, -1);
                echo "var selectedetape = '" . implode('/',$pathelement) . "';";
            }
            elseif ($addsignatairepath != "")
            {
                echo "var selectedetape = '$addsignatairepath';";
            }
            else
            {
                echo "var selectedetape = '';";
            }
?>
            for (var index=0 ; index < etapeliste.length ; index++)
            {
                if (selectedetape != etapeliste[index].id)
                {
                    etapeliste[index].click();
                }
            }
        }
    });
</script>

<?php

    function affichagerecursif(DOMNode $node, string $idparent)
    {
        global $fonctions;
        global $dbcon;

        $esignature = new esignature($dbcon);

        if ($node->localName == 'SIGNATAIRE')
        {
            $nodepath = $node->getNodePath();
            $typesignataire = trim($node->attributes->getNamedItem('TYPESIGNATAIRE')->nodeValue);
            $nodevalue = $node->nodeValue;
            echo "<li>";
            echo "<span id='$nodepath' class='XMLsignataire' data-nodepath='$nodepath' onclick='confirmdeletesignataire(\"$nodepath\");'>" . $node->localName . "</span>";
            echo "<ul>";
            $typesignatairelibelle = $esignature->typesignatairelibelle($typesignataire);

            $dataname = '';
            $dataid = '';
            $fullname = htmlspecialchars($typesignatairelibelle);
            if ($typesignataire == esignature::TYPESIGNATAIRE_AGENT )
            {
                $agent = $fonctions->createldapagentfromagentid($nodevalue,false);
                $dataname = htmlspecialchars($agent->identitecomplete());
                $dataid = $nodevalue;
                $fullname = $fullname . " : " . htmlspecialchars($agent->identitecomplete());
            }
            else if ($typesignataire == esignature::TYPESIGNATAIRE_RESP_STRUCT)
            {
                $structure = new structure($dbcon);
                $structure->load($nodevalue);
                $dataname = htmlspecialchars($structure->nomcourt());
                $dataid = $nodevalue;
                $fullname = $fullname . " : " . htmlspecialchars($structure->nomlong() . " (" . $structure->nomcourt() . ")");
            }

            echo "<li>";
            echo "<span class='XMLAttribute' data-type='$typesignataire' data-text='" . htmlspecialchars($esignature->typesignatairelibelle($typesignataire,true)) . "' ";
            if ($dataid != '')
            {
                echo " data-id='$dataid' ";
            }
            if ($dataname != '')
            {
                echo " data-name='$dataname' ";
            }
            echo ">" . $fullname . "</span>";
            echo "</li>";
            echo "</ul>";
            echo "</li>";
        }
        else
        {
            $extrainfos = "";
            $etapeinfos = "";
            $datatitle = "";
            if ($node->localName == 'CIRCUIT')
            {
                $extrainfos = $extrainfos . ' ' . trim($node->attributes->getNamedItem('DESCRIPTION')->nodeValue);
            }
            elseif ($node->localName == 'ETAPE')
            {
                $numetape = trim($node->attributes->getNamedItem('NUMERO')->nodeValue);
                $etapeinfos = " data-etape='" . trim($numetape) . "' class='XMLEtape' ";
                $extrainfos = $extrainfos . ' ' . $numetape . " ";
                $extrainfos = $extrainfos . ' => ' . trim($node->attributes->getNamedItem('DESCRIPTION')->nodeValue);

                $datatitle = $datatitle . 'Type de signature : ' . $esignature->typesignaturelibelle(trim($node->attributes->getNamedItem('TYPESIGNATURE')->nodeValue));
                $datatitle = $datatitle . chr(13) . 'L\'étape est obligatoire : ' . $fonctions->ouinonlibelle(trim($node->attributes->getNamedItem('OBLIGATOIRE')->nodeValue));
                $datatitle = $datatitle . chr(13) . 'Tous les signatataires doivent signer : ' . $fonctions->ouinonlibelle(trim($node->attributes->getNamedItem('TOUTESIGNATURE')->nodeValue));
                $datatitle = $datatitle . chr(13) . 'Un document doit être joint à la signature : ' . $fonctions->ouinonlibelle(trim($node->attributes->getNamedItem('PIECEJOINTEOBLIGATOIRE')->nodeValue));
                $datatitle = " <label class='XMLinfoetape' data-title='" . htmlentities($datatitle) . "'>&#128712;</label>";
            }
            $nodepath = $node->getNodePath();
            echo "<li>";
            echo "<span id='$nodepath' $etapeinfos>" . $node->localName . " $extrainfos</span>$datatitle";
            echo "<ul>";

            $signatairetrouve = false;
            foreach($node->childNodes as $key => $childnode)
            {
                if ($childnode->nodeType !== XML_TEXT_NODE)
                {
                    affichagerecursif($childnode, $idparent . '_' . $key);
                    if ($childnode->localName == 'SIGNATAIRE')
                    {
                        $signatairetrouve = true;
                    }
                }
            }
            if ($signatairetrouve)
            {
                $idparent = $nodepath . '/new';
                echo "<li>";
                echo "<span id='$idparent' class='XMLAddSignataire' onclick='confirmaddsignataire(\"$nodepath\");'>Ajouter un signataire dans l'étape $numetape</span>";
                echo "</li>";
            }
            echo "</ul>";
            echo "</li>";
        }
        return;
    }

    $xmldom = new DOMDocument();
    // $xmldom->preserveWhiteSpace = false;
    // $xmldom->formatOutput = true;

    $XMLfiles = array('Circuit_Teletravail.xml' => "Circuit télétravail",
                      'Circuit_CET.xml' => "Circuit CET");

    echo "<form name='selectcircuit' id='selectcircuit' method='post'>";
    echo "Sélectionnez un circuit pour le modifier : ";
    echo "<select id='circuitpath' name='circuitpath'>";
    echo "<option value=''>--- Sélectionnez un circuit ---</option>";
    foreach($XMLfiles as $XMLfilename => $description)
    {
        // On cherche le fichier XML représentant le circuit
        $XMLfilename = $fonctions->documentpath() . "/" . $XMLfilename;
        if (!file_exists($XMLfilename))
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($XMLfilename) . " n'existe pas.");
            exit;
        }

        // On charge le document XML
        $xmldom->validateOnParse = true;
        $valid = @$xmldom->load($XMLfilename);
        if (!$valid)
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, "La syntaxe du fichier " . basename($XMLfilename) . " n'est pas correcte => Vérifiez la DTD.");
            exit;
        }

        // On valide la syntaxe du fichier XML avec la DTD 
        $valid = @$xmldom->validate();
        if (!$valid)
        {
            echo $fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($XMLfilename) . " n'est pas un fichier XML valide => Vérifiez la DTD.");
            exit;
        }
        echo "<optgroup label='$description' filepath='$XMLfilename'>";
        // Attention : Le DOM doit être chargé au moment de la création du DOMXPath
        // Sinon, il ne trouve aucun noeux
        $xmlpath = new DOMXPath($xmldom);

        $xmldom->normalizeDocument();

        $rootnode = $xmlpath->query('CIRCUITS')[0];

        $circuitlist = $xmlpath->query('CIRCUIT', $rootnode);
        foreach($circuitlist as $key => $circuit)
        {
            $selected = '';
            if ($circuitpath == $circuit->getNodePath())
            {
                $selected = ' selected ';
            }
            echo "<option value='" . $circuit->getNodePath()  ."' $selected >" . trim($circuit->attributes->getNamedItem('DESCRIPTION')->nodeValue) . "</option>";
        }
    }
    echo "</select>";
?>
    <script>
        $('#circuitpath').change(function ()
        {
            var filepath=$('#circuitpath :selected').parent().attr('filepath');
            var currentform = document.querySelector("#selectcircuit");
            var xmlfilename = currentform.querySelector('#xmlfilename');
            xmlfilename.value = filepath;
            currentform.submit();
        });
    </script>
<?php
    echo "<input type='hidden' id='xmlfilename' name='xmlfilename' value='$filename' >";
    echo "<input type='hidden' id='userid' name='userid' value='$userid'>";
    echo "<input type='submit' name='btnselectcircuit' id='btnselectcircuit' class='g2tbouton g2tsuivantbouton' value='Suivant' hidden>";
    echo "</form>";
    echo "<br>";
    if ($circuitpath != '' and $filename != '')
    {
        $xmldom = new DOMDocument();
        // $xmldom->preserveWhiteSpace = false;
        // $xmldom->formatOutput = true;
        @$xmldom->load($filename);
        $xmlpath = new DOMXPath($xmldom);

        $circuit = $xmlpath->query($circuitpath)[0];
        echo "<form name='modiferXML' method='post'>";
        // ATTENTION : Le <span> dans le <span XMLCircuit> permet de faire afficher l'icône 'dossier' => voir la CSS
        echo "<br>Cliquez sur une ligne '<span class='XMLCircuit'><span>ETAPE</span></span>' pour afficher/masquer le détail de celle-ci.";
        echo "<br>Cliquez sur une ligne '<span class='XMLsignataire'>SIGNATAIRE</span>' pour supprimer le signataire.";
        echo "<br>Cliquez sur la ligne '<span class='XMLAddSignataire'>Ajouter un signataire...</span>' pour ajouter un signataire à l'étape courante.";
        echo "<br><br>";
        echo "<div class='XMLCircuit' id='divcircuit'><ul>";
        affichagerecursif($circuit, '0');
        echo "</ul></div>";
        echo "<input type='hidden' id='userid' name='userid' value='$userid'>";
        echo "<input type='hidden' id='removesignatairepath' name='removesignatairepath' value=''>";
        echo "<input type='hidden' id='circuitpath' name='circuitpath' value='$circuitpath'>";
        echo "<input type='hidden' id='addsignatairepath' name='addsignatairepath' value=''>";
        echo "<input type='hidden' id='addsignatairetype' name='addsignatairetype' value=''>";
        echo "<input type='hidden' id='addsignataireid' name='addsignataireid' value=''>";
        echo "<input type='hidden' id='xmlfilename' name='xmlfilename' value='$filename' >";
        $nbsignataire = count($xmlpath->query('ETAPE/SIGNATAIRE',$circuit));
        echo "<input type='hidden' id='nbsignataire' name='nbsignataire' value='$nbsignataire'>";
        echo "</form>";
    }


?>

</body>
</html>