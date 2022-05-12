<?php
    require_once ('CAS.php');
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
        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';

    if (isset($_POST["structureid"]))
        $structureid = $_POST["structureid"];
    else
        $structureid = null;

    $arrayinfouser = null;
    if (isset($_POST["infouser"]))
        $arrayinfouser = $_POST["infouser"];

    if (isset($_POST["gestion"]))
        $gestionnaireliste = $_POST["gestion"];
    else
        $gestionnaireliste = array();

    if (isset($_POST["resp"]))
        $responsableliste = $_POST["resp"];
    else
        $responsableliste = array();

    $showall = false;
    if (isset($_POST['showall'])) {
        if ($_POST['showall'] == 'true')
            $showall = true;
    }

    // print_r ($_POST); echo "<br>";
    function affichestructureliste($structure, $niveau = 0)
    {
        global $dbcon;
        global $structureid;
        global $fonctions;
        global $showall;
        // $fonctions = new fonctions($dbcon);
        if ($showall or ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))) {
            echo "<option value='" . $structure->id() . "'";
            if ($structure->id() == $structureid) {
                echo " selected ";
            }
            if ($fonctions->formatdatedb($structure->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                echo " style='color:red;' ";
            }
            echo ">";
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

    // echo "Responsable Liste = " . print_r($responsableliste,true) . "<br>";
    // echo "Gestionnaire Liste = " . print_r($gestionnaireliste,true) . "<br>";

    if (! is_null($structureid)) {

        // echo "Super on check !!!!<br>";
        // Initialisation des infos LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        // echo "Connexion au LDAP => Ok ??<br>";
        // On parcours touts les gestionnaires - mais on pourrait prendre les responsables
        // ATTENTION : $gestionnaireid contient UID de l'agent et non son numéro AGENT si celui ci est modifié !!!
        foreach ($gestionnaireliste as $structid => $gestionnaireid) {
            // echo "On boucle sur les gestionnaires....<br>";
            $structure = new structure($dbcon);
            // echo "Avant le load <br>";
            $structure->load($structid);

            // On modifie les codes des envois de mail pour les agents et les responsables
            $structure->resp_envoyer_a($_POST["resp_mail"][$structid], true);
            $structure->agent_envoyer_a($_POST["agent_mail"][$structid], true);

            // On va chercher dans le LDAP la correspondance UID => AGENTID
            $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
            $filtre = "($LDAP_UID_AGENT_ATTR=" . $responsableliste[$structid] . ")";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array(
                "$LDAP_CODE_AGENT_ATTR"
            );
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            // echo "Le numéro AGENT du responsable est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " pour la structure " . $structure->nomlong() . "<br>";
            if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
                $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
            else
                $agentid = '';
            // Si le agentid n'est pas vide ou null
            if ($agentid != '' and (! is_null($agentid))) {
                // echo "On fixe le responsable !!!!<br>";
                $structure->responsable($agentid);
            }

            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfouser[$structid]) == "") {
                $structure->gestionnaire("");
            } else {
                // On va chercher dans le LDAP la correspondance UID => AGENTID
                $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
                $filtre = "($LDAP_UID_AGENT_ATTR=$gestionnaireid)";
                $dn = $LDAP_SEARCH_BASE;
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                // echo "Le numéro AGENT du gestionnaire est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " pour la structure " . $structure->nomlong() . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
                    $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                else
                    $agentid = '';
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) {
                    // echo "On fixe le gestionnaire !!!!<br>";
                    $structure->gestionnaire($agentid);
                }
            }

            $msgerreur = $structure->store();
            // echo "Apres le store <br>";

            if ($msgerreur != "") {
                $errlog = "Pas de sauvegarde car " . $msgerreur;
                echo "<p style='color: red'>" . $errlog . "</p><br>";
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
    echo "<select size='1' id='structureid' name='structureid'>";
    while ($result = mysqli_fetch_row($query)) {
        $struct = new structure($dbcon);
        $struct->load($result[0]);
        affichestructureliste($struct, 0);
    }
    echo "</select>";

    /*
     * $sql="SELECT STRUCTUREID,NOMCOURT,NOMLONG FROM STRUCTURE WHERE length(STRUCTUREID)<='3' ORDER BY NOMCOURT"; //NOMLONG
     * //$sql="SELECT STRUCTUREID,NOMCOURT,NOMLONG FROM STRUCTURE ORDER BY NOMCOURT"; //NOMLONG
     * $query=mysqli_query ($dbcon, $sql);
     * $erreur=mysqli_error($dbcon);
     * if ($erreur != "") {
     * $errlog = "Gestion Structure : " . $erreur;
     * echo $errlog."<br/>";
     * error_log(basename(__FILE__)." ".$fonctions->stripAccents($errlog));
     * }
     * echo "<form name='selectstructure' method='post' >";
     * echo "<select name='structureid'>";
     * while ($result = mysqli_fetch_row($query))
     * {
     * echo "<option value='" . $result[0] . "'";
     * if ($result[0] == $structureid)
     * echo " selected ";
     * echo ">" . $result[2] . " (" . $result[1] . ")" . "</option>";
     * }
     * echo "</select>";
     */

    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br><input type='checkbox' name='showall' value='true'";
    if ($showall == true)
        echo " checked ";
    echo ">Afficher les structures fermées<br>";
    echo " <input type='submit' name= 'Valid_struct' value='Soumettre' >";
    echo "</form>";
    echo "<br>";

    if (! is_null($structureid)) {

        // echo "Le structureid = $structureid <br>";
        $structure = new structure($dbcon);
        $structure->load($structureid);

        // On utilise la liste des structures filles pour afficher la structure courante et les structures filles
        $structureliste = $structure->structurefille();
        // On ajoute la structure courante au tableau
        // ATTENTION : On met la clé à -1 pour qu'elle soit la première lors du tri !!!!
        $structureliste[- 1] = $structure;
        // On trie par la clé => La clé de la structure parente est plus petite (car 3 lettres) donc elle est en tete du tableau !!
        ksort($structureliste);
        // echo "Le tableau des structures files : " . print_r($structureliste, true) . "<br>";

        echo "<form name='paramstructure'  method='post' >";
        echo "<table>";
        foreach ($structureliste as $keystruc => $struct) {

            // echo "REsponsable = " . $struct->responsable()->identitecomplete() . "<br>";
            // $agentliste = $structure->agentlist(date('Ymd'),date('Ymd'),'o');
            // echo "<br> agentliste="; print_r((array)$agentliste); echo "<br>";

            // echo "Date cloture (Structure : " . $struct->id() . ") = " . $struct->datecloture() . "<br>";
            // echo "On est dans la boucle => " . $struct->nomlong() ."<br>";
            if ($fonctions->formatdatedb($struct->datecloture()) >= $fonctions->formatdatedb(date("Ymd")) or ($showall == true)) {
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
                echo "<td align=center class='titresimple'><span data-tip=" . chr(34) . $struct->nomcompletcet(true,true) . chr(34) . ">" . $struct->nomcourt() . " (" . $struct->id() . ") - " . $struct->nomlong() . " - Responsable : " . $struct->responsablesiham()->identitecomplete() . " ";
                echo "<font size='3'>&nbsp;$sign</font>";
                // echo "Apres affichage du nom... <br>";
                if ($showall)
                    echo "(Date fermeture : " . $struct->datecloture() . ") ";

                echo "</span>";
                    
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align=center>Gestionnaire : ";
                echo "<input id='infouser[" . $struct->id() . "]' name='infouser[" . $struct->id() . "]' placeholder='Nom et/ou prenom' value='";
                $style = '';
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->identitecomplete();
                    if (!$gestionnaire->isG2tUser())
                    {
                        $style = " style='background-color : #f5b7b1 ;' ";
                    }
                }
                echo "' size=40 $style />";
                //
                echo "<input type='hidden' id='gestion[" . $struct->id() . "]' name='gestion[" . $struct->id() . "]' value='";
                if (! is_null($gestionnaire))
                    echo $gestionnaire->agentid();
                echo "' class='infouser[" . $struct->id() . "]' /> ";
                ?>
    <script>
    		    	$('[id="<?php echo "infouser[". $struct->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
    	   </script>

    <?php
                // echo "Avant recup du responsable <br>";
                $responsable = $struct->responsablesiham();
                // echo "Apres recup du responsable <br>";
                echo " &nbsp; Direction : ";
                echo "<input id='responsableinfo[" . $struct->id() . "]' name='responsableinfo[" . $struct->id() . "]' placeholder='Nom et/ou prenom' value='" . $responsable->identitecomplete() . "' ";
                $style = '';
                if (!$responsable->isG2tUser())
                {
                    $style = " style='background-color : #f5b7b1 ;' ";
                }
                echo " size=40 $style/>";
                //
                echo "<input type='hidden' id='resp[" . $struct->id() . "]' name='resp[" . $struct->id() . "]' value='" . $responsable->agentid() . "' class='responsableinfo[" . $struct->id() . "]' /> ";
                ?>
    <script>
    		    	$('[id="<?php echo "responsableinfo[". $struct->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
    	   </script>

    <?php
                echo "</td>";
                echo "</tr>";

                // Si il y a une délégation ==> On l'affiche
                // Delegation <=> Le responsable SIHAM n'est pas le responable retourné par la fonction "responsable"
                if ($struct->responsable()->agentid() != $responsable->agentid()) {
                    echo "<tr>";
                    echo "<td>";
                    $delegueid = "";
                    $datedebutdeleg = "";
                    $datefindeleg = "";
                    $struct->getdelegation($delegueid, $datedebutdeleg, $datefindeleg);
                    $delegue = new agent($dbcon);
                    $delegue->load($delegueid);

                    echo "<FONT SIZE='2pt' COLOR='#FF0000'><B>Il exite une délégation : " . $delegue->identitecomplete() . " depuis le $datedebutdeleg jusqu'au $datefindeleg </B></FONT><br>";
                    echo "</td>";
                    echo "</tr>";
                }
                $struct->agent_envoyer_a($codeinterne);
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés des agents au : ";
                echo "<SELECT name='agent_mail[" . $struct->id() . "]' size='1'>";
                echo "<OPTION value=1";
                if ($codeinterne == 1)
                    echo " selected='selected' ";
                echo ">Responsable du service " . $struct->nomcourt() . "</OPTION>";
                echo "<OPTION value=2";
                if ($codeinterne == 2)
                    echo " selected='selected' ";
                echo ">Gestionnaire du service " . $struct->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";

                $parentstruct = null;
                $parentstruct = $struct->parentstructure();
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés du responsable au : ";
                echo "<SELECT name='resp_mail[" . $struct->id() . "]' size='1'>";
                if (! is_null($parentstruct)) {
                    $struct->resp_envoyer_a($codeinterne);
                    echo "<OPTION value=1";
                    if ($codeinterne == 1)
                        echo " selected='selected' ";
                    echo ">Responsable du service " . $parentstruct->nomcourt() . "</OPTION>";
                    echo "<OPTION value=2";
                    if ($codeinterne == 2)
                        echo " selected='selected' ";
                    echo ">Gestionnaire du service " . $parentstruct->nomcourt() . "</OPTION>";
                }
                echo "<OPTION value=3";
                if ($codeinterne == 3)
                    echo " selected='selected' ";
                echo ">Gestionnaire du service " . $struct->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";
                if ($infoagent != "")
                {
                    echo "<tr><td><b><font color=' #6c3483 '>$infoagent</font></b></td></tr>";
                }
                echo "<tr><td height=15></td></tr>";
            }
        }
        echo "</table>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='structureid' value='" . $structureid . "'>";
        echo "<input type='submit' name= 'Modif_struct' value='Enregistrer les modifications' >";
        echo "</form>";
        echo "<br>";
    }

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

