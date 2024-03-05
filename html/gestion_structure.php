<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

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
    // echo '<html><body class="bodyhtml">';

    $structureid = null;
    if (isset($_POST["structureid"]))
    {
        $structureid = $_POST["structureid"];
    }

    $mode = '';
    if (isset($_POST["mode"]))
    {
        $mode = $_POST["mode"];
    }

    $arrayinfouser = null;
    if (isset($_POST["infouser"]))
    {
        $arrayinfouser = $_POST["infouser"];
    }

    $gestionnaireliste = array();
    if (isset($_POST["gestion"]))
    {
        $gestionnaireliste = $_POST["gestion"];
    }

    $responsableliste = array();
    if (isset($_POST["resp"]))
    {
        $responsableliste = $_POST["resp"];
    }
       
    $showall = false;
    if (isset($_POST['showall']) and $_POST['showall'] == 'true') {
        $showall = true;
    }
    
    $showallsubstruct = false;
    if (isset($_POST['showallsubstruct']) and $_POST['showallsubstruct'] == 'true') {
        $showallsubstruct = true;
    }
    

    // print_r ($_POST); echo "<br>";

    // echo "Responsable Liste = " . print_r($responsableliste,true) . "<br>";
    // echo "Gestionnaire Liste = " . print_r($gestionnaireliste,true) . "<br>";

    if (! is_null($structureid)) {

        // echo "Super on check !!!!<br>";
 
        // On parcours touts les gestionnaires - mais on pourrait prendre les responsables
        // ATTENTION : $gestionnaireid contient UID de l'agent et non son numéro AGENT si celui ci est modifié !!!
        foreach ($gestionnaireliste as $structid => $gestionnaireid) {
            // echo "On boucle sur les gestionnaires....<br>";
            $structure = new structure($dbcon);
            $structure->load($structid);

            // On modifie les codes des envois de mail pour les agents et les responsables
            $structure->resp_envoyer_a($_POST["resp_mail"][$structid], true);
            $structure->agent_envoyer_a($_POST["agent_mail"][$structid], true);

            // On va chercher dans le LDAP la correspondance UID => AGENTID
            //echo "\$responsableliste[$structid] est soit un uid soit un numéro agent : ". $responsableliste[$structid] . " <br>";
            if (! is_numeric($responsableliste[$structid]))
            {
                // On va chercher dans le LDAP la correspondance UID => AGENTID
                $agentid = $fonctions->useridfromCAS($responsableliste[$structid]);
                if ($agentid === false)
                {
                    $agentid = null;
                }
            }
            else
            {
                $agentid = $responsableliste[$structid];
            }
                        
            // Si le agentid n'est pas vide ou null
            if ($agentid != '' and (! is_null($agentid))) {
                // echo "On fixe le responsable !!!!<br>";
                $errlog = "On fixe le responsable de la structure " . $structure->nomcourt() . " à $agentid";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                $structure->responsable($agentid);
            }

            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfouser[$structid]) == "") {
                $structure->gestionnaire("");
            } else {
                // On va chercher dans le LDAP la correspondance UID => AGENTID
                
                //echo "\$gestionnaireid est soit un uid soit un numéro agent : ". $gestionnaireid . " <br>";
                if (! is_numeric($gestionnaireid))
                {
                    // On va chercher dans le LDAP la correspondance UID => AGENTID
                    //$agentid = $fonctions->useridfromCAS($gestionnaireid);
                    //if ($agentid === false)
                    //{
                    //    $agentid = null;
                    //}
                    $agentgest = $fonctions->createldapagentfromuid($gestionnaireid);
                    if ($agentgest===false)
                    {
                        $agentid = null;
                    }
                    else
                    {
                        $agentid = $agentgest->agentid();
                    }
                }
                else
                {
                    //$agentid = $gestionnaireid;
                    $agentgest = $fonctions->createldapagentfromagentid($gestionnaireid);
                    if ($agentgest===false)
                    {
                        $agentid = null;
                    }
                    else
                    {
                        $agentid = $agentgest->agentid();
                    }
                }
                
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) 
                {
                    // echo "On fixe le gestionnaire !!!!<br>";
                    $errlog = "On fixe le gestionnaire de la structure " . $structure->nomcourt() . " à $agentid";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    $structure->gestionnaire($agentid);
                }
            }

            $msgerreur = $structure->store();
            // echo "Apres le store <br>";

            if ($msgerreur != "") {
                $errlog = "Pas de sauvegarde car " . $msgerreur;
                echo $fonctions->showmessage(fonctions::MSGERROR, "$errlog");
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } else {
                // Tout s'est bien passé
            }
        }
    }

    $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "") {
        $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
        echo $errlog . "<br/>";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    echo "<form name='selectstructure'  method='post' >";
    $structliste = array();
    while ($result = mysqli_fetch_row($query)) 
    {
        $struct = new structure($dbcon);
        $struct->load($result[0]);
        $structliste[$result[0]] = $struct;
        $structliste = $structliste + (array)$struct->structurefille(true,0);
    }
    echo "<select size='1' id='structureid' name='structureid'>";
    $fonctions->afficherlistestructureindentee($structliste,$showall,$structureid);
    unset($structliste);
    echo "</select>";

    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='hidden' name='mode' value='" . $mode . "'>";
    echo "<br><input type='checkbox' name='showall' value='true'";
    if ($showall == true)
    {
        echo " checked ";
    }
    echo ">Afficher les structures fermées<br>";
    echo "<input type='checkbox' name='showallsubstruct' value='true'";
    if ($showallsubstruct == true)
    {
        echo " checked ";
    }
    echo ">Afficher toutes les sous-structures<br>";
    echo " <input type='submit' name= 'Valid_struct' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";
    echo "<br>";

    $structureliste = array();
    if (! is_null($structureid)) {

        // echo "Le structureid = $structureid <br>";
        $structure = new structure($dbcon);
        $structure->load($structureid);
        // On ajoute la structure courante au tableau
        $structureliste[$structure->id()] = $structure;
        $structureliste = $structureliste + (array)$structure->structurefille($showallsubstruct,0);
//        // On trie par la clé => La clé de la structure parente est plus petite (car 3 lettres) donc elle est en tete du tableau !!
//        foreach($structureliste as $keystruc => $struct)
//        {
//            $structureliste[str_replace("_"," ",$keystruc)] = $struct;
//            unset($structureliste[$keystruc]);
//        }
//        ksort($structureliste,SORT_STRING);
//        var_dump ("Le tableau des structures filles : "); foreach ($structureliste as $keystruc => $struct) {var_dump("Key = $keystruc  Profondeur = " . $struct->profondeurrelative()); }

        echo "<form name='paramstructure' id='paramstructure' method='post' >";
        foreach ($structureliste as $keystruc => $struct) {

            // echo "REsponsable = " . $struct->responsable()->identitecomplete() . "<br>";
            // $agentliste = $structure->agentlist(date('Ymd'),date('Ymd'),'o');
            // echo "<br> agentliste="; print_r((array)$agentliste); echo "<br>";

            // echo "Date cloture (Structure : " . $struct->id() . ") = " . $struct->datecloture() . "<br>";
            // echo "On est dans la boucle => " . $struct->nomlong() ."<br>";
            if ($fonctions->formatdatedb($struct->datecloture()) >= $fonctions->formatdatedb(date("Ymd")) or ($showall == true)) {
                echo "<table style='margin-left: " . 30*$struct->profondeurrelative() . "px; border: black;border-left-style: solid;border-width: 2px; padding-left: 10px;'>";
                $gestionnaire = $struct->gestionnaire();
                // echo "Apres recup du gestionnaire.... <br>";
                

                // On va récupérer le premiers agents de la structure et demander à LDAP s'il sont dans le group users.g2t
                $agentliste = $struct->agentlist(date("d/m/Y"), date("d/m/Y"),'n');  // On ne veut pas les agents des sous-structures
                $infoagent = "";
                $nbprobleme = 0;
                $sign = '&#128077;';
                // Pour chaque agent de la structure, on regarde si c'est un G2Tuser
                foreach ((array)$agentliste as $structagent)
                {
//                    if ($structagent->agentid() > 0)
//                    {
                        if (!$structagent->isG2tUser())
                        {
                            $nbprobleme = $nbprobleme + 1;

                            $infoagent = $infoagent . "L'agent " . $structagent->identitecomplete() . " n'est pas un utilisateur G2T valide.<br>";
                            $sign = "&#9888;";
                        }
                        else
                        {
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Pour la structure " . $struct->nomcourt() . " " . $struct->nomlong()  . " : L'agent " . $structagent->identitecomplete() . " est ok."));
                        }
//                    }
                }
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Pour la structure " . $struct->nomcourt() . " " . $struct->nomlong()  . " : J'ai " . count((array)$agentliste) . " agents dans la structure et $nbprobleme sont erronés."));
                if (count((array)$agentliste) == $nbprobleme)
                {
                    $infoagent = "Aucun agent n'est dans un groupe valide.<br>";
                    $sign = "&#128711;";
                }
                
                echo "<tr>";
                // echo "Avant l'affichage du nom...<br>";
                echo "<td align=center class='titresimple'><span data-tip=" . chr(34) . $struct->nomcompletcet(true,true) . chr(34) . ">" . $struct->nomcourt() . " (" . $struct->id() . ") - " . $struct->nomlong() . " - Responsable G2T : " . $struct->responsablesiham()->identitecomplete() . " ";
                echo "<b class='symbolegestionstruct'>&nbsp;$sign</b>";
                // echo "Apres affichage du nom... <br>";
                if ($showall)
                {
                    echo "(Date fermeture : " . $struct->datecloture() . ") ";
                }
                echo "</span>";
                    
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align=center>Gestionnaire G2T : ";
                echo "<input id='infouser[" . $struct->id() . "]' name='infouser[" . $struct->id() . "]' placeholder='Nom et/ou prenom' value='";
                $style = '';
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->identitecomplete();
                    if (!$gestionnaire->isG2tUser())
                    {
                        $style = " class='kobackgroundtext' ";
                    }
                }
                echo "' size=40 $style />";
                //
                echo "<input type='hidden' id='gestion[" . $struct->id() . "]' name='gestion[" . $struct->id() . "]' value='";
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->agentid();
                }
                echo "' class='infouser[" . $struct->id() . "]' /> ";
?>
			    <script>
    		    	$('[id="<?php echo "infouser[". $struct->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { showExtendedInfo: 0, filter_eduPersonAffiliation: "employee|researcher" } });
    	   		</script>

<?php
                // echo "Avant recup du responsable <br>";
                $responsable = $struct->responsablesiham();
                // echo "Apres recup du responsable <br>";
                echo " &nbsp; Responsable G2T : ";
                echo "<input id='responsableinfo[" . $struct->id() . "]' name='responsableinfo[" . $struct->id() . "]' placeholder='Nom et/ou prenom' value='" . $responsable->identitecomplete() . "' ";
                $style = '';
                if (!$responsable->isG2tUser())
                {
                    $style = " class='kobackgroundtext' ";
                }
                echo " size=40 $style/>";
                //
                echo "<input type='hidden' id='resp[" . $struct->id() . "]' name='resp[" . $struct->id() . "]' value='" . $responsable->agentid() . "' class='responsableinfo[" . $struct->id() . "]' /> ";
?>
    			<script>
    		    	$('[id="<?php echo "responsableinfo[". $struct->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { showExtendedInfo: 0, filter_eduPersonAffiliation: "employee" } });
    	   		</script>

<?php
                echo "</td>";
                echo "</tr>";

                // Si il y a une délégation ==> On l'affiche
                // Delegation <=> Le responsable SIHAM n'est pas le responable retourné par la fonction "responsable"
                if ($struct->responsable()->agentid() != $responsable->agentid()) {
                    echo "<tr>";
                    echo "<td>";
                    $delegation = $struct->getdelegation();
                    $delegueid = $delegation->delegationuserid;
                    $datedebutdeleg = $delegation->datedebutdeleg;
                    $datefindeleg = $delegation->datefindeleg;
                    $continuesendtoresp = $delegation->continuesendtoresp;

                    $delegue = new agent($dbcon);
                    $delegue->load($delegueid);
                    $info = "Il exite une délégation : " . $delegue->identitecomplete() . " depuis le $datedebutdeleg jusqu'au $datefindeleg."; 
                    echo $fonctions->showmessage(fonctions::MSGWARNING, $info);
                    echo "</td>";
                    echo "</tr>";
                }
                $struct->agent_envoyer_a($codeinterne);
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés des agents au : ";
                echo "<SELECT id='agent_mail[" . $struct->id() . "]' name='agent_mail[" . $struct->id() . "]' size='1'>";
//                echo "<OPTION value=1";
                echo "<OPTION value=" . structure::MAIL_AGENT_ENVOI_RESP_COURANT;
                if ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT)
                {
                    echo " selected='selected' ";
                }
                echo ">Responsable G2T du service " . $struct->nomcourt() . "</OPTION>";
//                echo "<OPTION value=2";
                echo "<OPTION value=" . structure::MAIL_AGENT_ENVOI_GEST_COURANT;
                if ($codeinterne == structure::MAIL_AGENT_ENVOI_GEST_COURANT)
                {
                    echo " selected='selected' ";
                }
                echo ">Gestionnaire G2T du service " . $struct->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "&nbsp; <label id='agent_send_identity[" . $struct->id() . "]' ></label>";
                echo "</td>";
                echo "</tr>";

                $parentstruct = null;
                $parentstruct = $struct->parentstructure();
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés du responsable G2T au : ";
                echo "<SELECT id='resp_mail[" . $struct->id() . "]' name='resp_mail[" . $struct->id() . "]' size='1'>";
                if (! is_null($parentstruct)) {
                    $struct->resp_envoyer_a($codeinterne);
//                    echo "<OPTION value=1";
                    echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_RESP_PARENT;
                    if ($codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT)
                    {
                        echo " selected='selected' ";
                    }
                    echo ">Responsable G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
//                    echo "<OPTION value=2";
                    echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_GEST_PARENT;
                    if ($codeinterne == structure::MAIL_RESP_ENVOI_GEST_PARENT)
                    {
                        echo " selected='selected' ";
                    }
                    echo ">Gestionnaire G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
                }
//                echo "<OPTION value=3";
                echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_GEST_COURANT;
                if ($codeinterne == structure::MAIL_RESP_ENVOI_GEST_COURANT)
                {
                    echo " selected='selected' ";
                }
                echo ">Gestionnaire G2T du service " . $struct->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "&nbsp; <label id='resp_send_identity[" . $struct->id() . "]' ></label>";
                echo "</td>";
                echo "</tr>";
                if ($infoagent != "")
                {
                    echo "<tr><td><b><div class='infogeststruct'>$infoagent</div></b></td></tr>";
                }
                echo "<tr><td height=15></td></tr>";
                echo "</table>";
            }

?>
<script>
    function user_mode_change_<?php echo $struct->id(); ?>()
    {
        //alert('User mode change');
        var select_tag = document.getElementById('agent_mail[<?php echo $struct->id()?>]');
        var agent_send_identity = document.getElementById('agent_send_identity[<?php echo $struct->id(); ?>]');
        //var currentvalue = select_tag.selectedIndex+1;
        //console.log(currentvalue);
        if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_AGENT_ENVOI_RESP_COURANT; ?>)
        {
            agent_send_identity.innerHTML = '<?php echo $struct->responsable()->identitecomplete();  ?>';
        }
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_AGENT_ENVOI_GEST_COURANT; ?>)
        {
            agent_send_identity.innerHTML = '<?php if (!is_null($struct->gestionnaire())) { echo $struct->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        else
        {
            alert ('Index inconnu !!');
        }
    }

    function resp_mode_change_<?php echo $struct->id(); ?>()
    {
        //alert('Resp mode change');
        var select_tag = document.getElementById('resp_mail[<?php echo $struct->id()?>]');
        var resp_send_identity = document.getElementById('resp_send_identity[<?php echo $struct->id(); ?>]');
        //var currentvalue = select_tag.selectedIndex+1;
        //console.log(currentvalue);
        if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_RESP_PARENT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($parentstruct)) { echo $parentstruct->responsable()->identitecomplete(); }  else { echo 'Non défini'; }?>';
        }
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_GEST_PARENT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($parentstruct) and !is_null($parentstruct->gestionnaire())) { echo $parentstruct->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_GEST_COURANT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($struct->gestionnaire())) { echo $struct->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        else
        {
            alert ('Index inconnu !!');
        }
    }
    var select_tag = document.getElementById('agent_mail[<?php echo $struct->id()?>]');
    if (select_tag)
    {
        select_tag.addEventListener('change', () =>
            user_mode_change_<?php echo $struct->id(); ?>()
            );
        var e = new Event("change");
        select_tag.dispatchEvent(e);
    }
    var select_tag = document.getElementById('resp_mail[<?php echo $struct->id()?>]');
    if (select_tag)
    {
        select_tag.addEventListener('change', () =>
            resp_mode_change_<?php echo $struct->id(); ?>()
            );
        var e = new Event("change");
        select_tag.dispatchEvent(e);
    }
</script>
<?php

        }
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' name='structureid' value='" . $structureid . "'>";
        echo "<input type='submit' name= 'Modif_struct' class='g2tbouton g2tvalidebouton' value='Enregistrer' >";
        echo "</form>";
        echo "<br>";
    }
    
    if ($mode == 'gestrh')
    {
?>
        <script>
            var formcreationteletravail = document.getElementById('paramstructure');
            if (formcreationteletravail)
            {
                // console.log ('La form est trouvée');
                for (var champ=0; champ < formcreationteletravail.elements.length; champ++) 
                {
                    // console.log (formcreationteletravail.elements[champ].name);
                    formcreationteletravail.elements[champ].disabled = true;
                }
            }

        </script>
<?php
    }
?>


<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

