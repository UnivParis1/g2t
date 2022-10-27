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
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    $action = $_POST["action"]; // Action = lecture ou modif
    if (is_null($action) or $action == "")
        $action = 'lecture';
    $mode = $_POST["mode"]; // Action = gestion ou resp
    if (is_null($action) or $action == "")
        $action = 'resp';

    // echo "Apres le chargement du user !!! <br>";
    require ("includes/menu.php");

    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    // print_r ( $_POST); echo "<br>";

    $reportlist = null;
    if (isset($_POST['report']))
        $reportlist = $_POST['report'];

    $enfantmaladelist = null;
    if (isset($_POST['enfantmalade']))
        $enfantmaladelist = $_POST['enfantmalade'];

    $cumultotallist = null;
    if (isset($_POST['cumultotal']))
        $cumultotallist = $_POST['cumultotal'];

    $array_agent_mail = null;
    if (isset($_POST['agent_mail']))
        $array_agent_mail = $_POST['agent_mail'];
    $array_resp_mail = null;
    if (isset($_POST['resp_mail']))
        $array_resp_mail = $_POST['resp_mail'];

    $datedebutcetlist = null;
    if (isset($_POST['datedebutcet']))
        $datedebutcetlist = $_POST['datedebutcet'];

    if (is_array($reportlist)) {
        foreach ($reportlist as $agentid => $reportvalue) {
            $complement = new complement($dbcon);
            $complement->complementid('REPORTACTIF');
            $complement->agentid($agentid);
            $complement->valeur($reportvalue);
            $complement->store();
            unset($complement);
        }
    }

    $msgerreur = "";
    if (is_array($enfantmaladelist)) {
        foreach ($enfantmaladelist as $agentid => $enfantmaladevalue) {
            // echo "strcasecmp(intval ... => " . strcasecmp(intval($enfantmaladevalue),$enfantmaladevalue) . "<br>";
            // echo "intval >=0 => " . (intval($enfantmaladevalue)>=0) . "<br>";
            if ((strcasecmp(intval($enfantmaladevalue), $enfantmaladevalue) == 0) and (intval($enfantmaladevalue) >= 0)) // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
            {
                $complement = new complement($dbcon);
                $complement->complementid('ENFANTMALADE');
                $complement->agentid($agentid);
                $complement->valeur(intval($enfantmaladevalue));
                $complement->store();
                unset($complement);
            } else {
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $msgerreur = $msgerreur . "Le nombre de jour 'Garde d'enfant' saisi n'est pas correct pour l'agent " . $agent->identitecomplete() . " <br>";
                unset($agent);
            }
        }
    }

    if (is_array($cumultotallist)) {
        foreach ($cumultotallist as $agentid => $cumultotal) {
            if (isset($datedebutcetlist[$agentid])) {
                if ($fonctions->verifiedate($datedebutcetlist[$agentid])) {
                    $cet = new cet($dbcon);
                    $cet->cumultotal($cumultotal);
                    $cet->agentid($agentid);
                    $cet->datedebut($datedebutcetlist[$agentid]);
                    $cet->store();
                }
            }
        }
    }

    $displaysousstructlist = null;
    if (isset($_POST["displaysousstruct"]))
        $displaysousstructlist = $_POST["displaysousstruct"];
    if (is_array($displaysousstructlist)) {
        foreach ($displaysousstructlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->sousstructure($valeur);
            $structure->store();
        }
    }

    $displayallagentlist = null;
    if (isset($_POST["displaysousstruct"]))
        $displayallagentlist = $_POST["displayallagent"];
    if (is_array($displayallagentlist)) {
        foreach ($displayallagentlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->affichetoutagent($valeur);
            $structure->store();
        }
    }

    $displayrespsousstructlist = null;
    if (isset($_POST["displayrespsousstruct"]))
        $displayrespsousstructlist = $_POST["displayrespsousstruct"];
    if (is_array($displayrespsousstructlist)) {
        foreach ($displayrespsousstructlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->afficherespsousstruct($valeur);
            $structure->store();
        }
    }

    $respvalidsousstructlist = null;
    if (isset($_POST["respvalidsousstruct"]))
        $respvalidsousstructlist = $_POST["respvalidsousstruct"];
    if (is_array($respvalidsousstructlist)) {
        foreach ($respvalidsousstructlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->respvalidsousstruct($valeur);
            $structure->store();
        }
    }

    $gestvalidagent = null;
    if (isset($_POST["gestvalidagent"]))
        $gestvalidagent = $_POST["gestvalidagent"];
    if (is_array($gestvalidagent)) {
        foreach ($gestvalidagent as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->gestvalidagent($valeur);
            $structure->store();
        }
    }

    $arraygestionnaire = null;
    if (isset($_POST["gestion"]))
        $arraygestionnaire = $_POST["gestion"];

    $arrayinfouser = null;
    if (isset($_POST["infouser"]))
        $arrayinfouser = $_POST["infouser"];

    if (is_array($arraygestionnaire)) {
        // ATTENTION : La $valeur est soit le AGENTID soit le UID si on vient de le modifier !!
        foreach ($arraygestionnaire as $structureid => $valeur) 
        {
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfouser[$structureid]) == "") {
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->gestionnaire("");
                $structure->store();
            } 
            else 
            {
                //echo "\$valeur est soit un uid soit un numéro agent : $valeur <br>";
                if (! is_numeric($valeur))
                {
                    $agentid = $fonctions->useridfromCAS($valeur);
                    if ($agentid === false)
                    {
                        $agentid = null;
                    }
                }
                else
                {
                    $agentid = $valeur;
                }
                //echo "Agentid pour le gestionnaire vaut : $agentid <br>";
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    $structure->gestionnaire($agentid);
                    $structure->store();
                }
            }
        }
    }

    // ///////////////////////////////////////////////////
    // ---- PARTIE GESTION DE LA DELEGATION -------- //
    // ///////////////////////////////////////////////////
    $arraydelegation = null;
    if (isset($_POST["delegation"]))
        $arraydelegation = $_POST["delegation"];

    $arrayinfodelegation = null;
    if (isset($_POST["infodelegation"]))
        $arrayinfodelegation = $_POST["infodelegation"];

    $arraydatedebut = null;
    if (isset($_POST["date_debut"]))
        $arraydatedebut = $_POST["date_debut"];

    $arraydatefin = null;
    if (isset($_POST["date_fin"]))
        $arraydatefin = $_POST["date_fin"];

    if (is_array($arraydelegation)) 
    {
        // ATTENTION : La $valeur est soit le AGENTID soit le UID si on vient de le modifier !!
        foreach ($arraydelegation as $structureid => $valeur) {
            $resp_est_delegue = false;
            // echo "dans le foreach <br>";
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfodelegation[$structureid]) == "") {
                // echo "On supprime la personne déléguée....<br>";
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->setdelegation("", "", "", $userid);
            } else {
                // echo "Dans le else avant le filtre LDAP <br>";
                //echo "\$valeur est soit un uid soit un numéro agent : $valeur <br>";
                if (! is_numeric($valeur))
                {
                    // On va chercher dans le LDAP la correspondance UID => AGENTID
                    $agentid = $fonctions->useridfromCAS($valeur);
                    if ($agentid === false)
                    {
                        $agentid = null;
                    }
                }
                else
                {
                    $agentid = $valeur;
                }
                //echo "agentid = $agentid <br>";
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    // On ne peut pas mettre le responsable de la structure comme délégué
                    if ($agentid == $user->agentid()) {
                        // On récupère la liste des structures ou l'utilisateur est responsable (sens strict)
                        $structrespliste = $user->structrespliste(false);
                        // Si la structure courante est définie dans le tableau des structures
                        // On ne peut pas le mettre délégué
                        if (isset($structrespliste[$structureid])) {
                            $resp_est_delegue = true;
                        }
                    }

                    if ($resp_est_delegue) 
                    {
                        $error = "Vous ne pouvez pas saisir le responsable (" . $user->identitecomplete() . ") de la structure '" . $structure->nomlong() . "' comme délégué.<br>La délégation n'est pas enregistrée.";
                        echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                    } else {
                        $datedebutdeleg = "";
                        if (isset($arraydatedebut[$structure->id()]))
                            $datedebutdeleg = $arraydatedebut[$structure->id()];
                        $datefindeleg = "";
                        if (isset($arraydatefin[$structure->id()]))
                            $datefindeleg = $arraydatefin[$structure->id()];

                        $structure->getdelegation($delegationuserid, $datedebutdelegbd, $datefindelegbd);
                        // echo "datedebutdeleg = $datedebutdeleg datefindeleg = $datefindeleg <br>";
                        if ($datedebutdeleg == "" or $datefindeleg == "") 
                        {
                            $error = "Un agent délégué est saisi, mais la date de début ou la date de fin de la période est vide.<br>La délégation n'est pas enregistrée.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                        } 
                        elseif (($delegationuserid == $agentid)
                            and ($fonctions->formatdate($datedebutdelegbd) == $fonctions->formatdate($datedebutdeleg))
                            and $fonctions->formatdate($datefindelegbd) == $fonctions->formatdate($datefindeleg))
                        {
                            // On a donné les mêmes paramétres que ceux de la base de données => On ne fait rien
                            $errlog = "Pas d'enregistrement de la délégation car les données sont identiques à celles en base de données pour " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $agentid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                        else 
                        {
                            // echo "On enregistre la delegation.... <br>";
                        	$structure->setdelegation($agentid, $datedebutdeleg, $datefindeleg, $userid);
                        	$errlog = "Enregistrement d'une délégation sur " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $agentid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                        	echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
                        	$errlog = $user->identitecomplete() . " : " . $errlog;
                            // echo $errlog."<br/>";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                    }
                }
            }
        }
    }

    if (isset($array_agent_mail)) {
        // On modifie les codes des envois de mail pour les agents et les responsables
        foreach ($array_agent_mail as $structkey => $codeinterne) {
            $structure = new structure($dbcon);
            $structure->load($structkey);
            $structure->agent_envoyer_a($codeinterne, true);
        }
    }
    if (isset($array_resp_mail)) {
        // On modifie les codes des envois de mail pour les agents et les responsables
        foreach ($array_resp_mail as $structkey => $codeinterne) {
            $structure = new structure($dbcon);
            $structure->load($structkey);
            $structure->resp_envoyer_a($codeinterne, true);
        }
    }

    echo "<br>";
    if ($msgerreur != "") {
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($msgerreur));
        echo $fonctions->showmessage(fonctions::MSGERROR, "$msgerreur");
        //echo "<B><P style='color: red'> $msgerreur </P></B>";
    }
    echo "<form name='frm_dossier'  method='post' >";
    if ($mode == 'resp') {
        $structliste = $user->structrespliste();
        $structrespliste = $user->structrespliste(false);
    }
    if ($mode == 'gestion') {
        $structliste = $user->structgestliste();
        $structrespliste = array();
    }
    // echo "Structure liste = "; print_r($structliste); echo "<br>";
    foreach ($structliste as $key => $structure) {
        
        $responsableliste = array();
        // On ajoute les responsables de structures filles
        if ($mode == 'resp')
        {
            $structurefilleliste = $structure->structurefille();
            if (is_array($structurefilleliste)) {
                foreach ($structurefilleliste as $key => $structurefille) {
                    if ($fonctions->formatdatedb($structurefille->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                        $respstructfille = $structurefille->responsable();
                        if ($respstructfille->agentid() != '-1') {
                            // La clé NOM + PRENOM + AGENTID permet de trier les éléments par ordre alphabétique
                            $responsableliste[$respstructfille->nom() . " " . $respstructfille->prenom() . " " . $respstructfille->agentid()] = $respstructfille;
                            // /$responsableliste[$responsable->agentid()] = $responsable;
                        }
                    }
                }
            }
        }
        
        if (is_array($structure->agentlist(date('d/m/Y'), date('d/m/Y'), 'n')) or count($responsableliste)>0) {
            if ($mode == 'resp')
                echo $structure->dossierhtml(($action == 'modif'), $userid);
            else
                echo $structure->dossierhtml(($action == 'modif'));

            echo "Autoriser l'affichage du planning à tous les agents des sous-structures (responsable G2T/gestionnaire G2T) : ";
            if ($action == 'modif') {
                echo "<select name=displaysousstruct['" . $structure->id() . "']>";
                echo "<option value='o'";
                if (strcasecmp($structure->sousstructure(), "o") == 0)
                    echo " selected ";
                echo ">Oui</option>";
                echo "<option value='n'";
                if (strcasecmp($structure->sousstructure(), "n") == 0)
                    echo " selected ";
                echo ">Non</option>";
                echo "</select>";
            } else
                echo $fonctions->ouinonlibelle($structure->sousstructure());

            echo "<br>";
            echo "Autoriser l'affichage uniquement du planning des responsables G2T des sous-structures (responsable G2T/gestionnaire G2T) : ";
            if ($action == 'modif') {
                echo "<select name=displayrespsousstruct['" . $structure->id() . "']>";
                echo "<option value='o'";
                if (strcasecmp($structure->afficherespsousstruct(), "o") == 0)
                    echo " selected ";
                echo ">Oui</option>";
                echo "<option value='n'";
                if (strcasecmp($structure->afficherespsousstruct(), "n") == 0)
                    echo " selected ";
                echo ">Non</option>";
                echo "</select>";
            } else
                echo $fonctions->ouinonlibelle($structure->afficherespsousstruct());

            echo "<br>";
            echo "Autoriser la consultation du planning de la structure à tous les agents de celle-ci : ";
            if ($action == 'modif') {
                echo "<select name=displayallagent['" . $structure->id() . "']>";
                echo "<option value='o'";
                if (strcasecmp($structure->affichetoutagent(), "o") == 0)
                    echo " selected ";
                echo ">Oui</option>";
                echo "<option value='n'";
                if (strcasecmp($structure->affichetoutagent(), "n") == 0)
                    echo " selected ";
                echo ">Non</option>";
                echo "</select>";
            } else
                echo $fonctions->ouinonlibelle($structure->affichetoutagent());

            echo "<br>";
            echo "Autoriser la validation des demandes d'une sous-structure par le responsable G2T de la structure parente : ";
            if ($action == 'modif') {
                echo "<select name=respvalidsousstruct['" . $structure->id() . "']>";
                echo "<option value='o'";
                if (strcasecmp($structure->respvalidsousstruct(), "o") == 0)
                    echo " selected ";
                echo ">Oui</option>";
                echo "<option value='n'";
                if (strcasecmp($structure->respvalidsousstruct(), "n") == 0)
                    echo " selected ";
                echo ">Non</option>";
                echo "</select>";
            } else
                echo $fonctions->ouinonlibelle($structure->respvalidsousstruct());

            if ($mode == 'resp') {
                echo "<br>";
                echo "Autoriser la validation des demandes des agents par le gestionnaire G2T : ";
                if ($action == 'modif') {
                    echo "<select name=gestvalidagent['" . $structure->id() . "']>";
                    echo "<option value='o'";
                    if (strcasecmp($structure->gestvalidagent(), "o") == 0)
                        echo " selected ";
                    echo ">Oui</option>";
                    echo "<option value='n'";
                    if (strcasecmp($structure->gestvalidagent(), "n") == 0)
                        echo " selected ";
                    echo ">Non</option>";
                    echo "</select>";
                } else
                    echo $fonctions->ouinonlibelle($structure->gestvalidagent());
            }
            if ($mode == 'resp') {
                $structure->agent_envoyer_a($codeinterne);
                echo "<table>";
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés des agents au : ";
                echo "<SELECT name='agent_mail[" . $structure->id() . "]' size='1'>";
                echo "<OPTION value=1";
                if ($codeinterne == 1)
                    echo " selected='selected' ";
                echo ">Responsable G2T du service " . $structure->nomcourt() . "</OPTION>";
                echo "<OPTION value=2";
                if ($codeinterne == 2)
                    echo " selected='selected' ";
                echo ">Gestionnaire G2T du service " . $structure->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";

                $parentstruct = null;
                $parentstruct = $structure->parentstructure();
                $structure->resp_envoyer_a($codeinterne);
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés du responsable G2T au : ";
                echo "<SELECT name='resp_mail[" . $structure->id() . "]' size='1'>";
                if (! is_null($parentstruct)) {
                    echo "<OPTION value=1";
                    if ($codeinterne == 1)
                        echo " selected='selected' ";
                    echo ">Responsable G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
                    echo "<OPTION value=2";
                    if ($codeinterne == 2)
                        echo " selected='selected' ";
                    echo ">Gestionnaire G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
                }
                echo "<OPTION value=3";
                if ($codeinterne == 3)
                    echo " selected='selected' ";
                echo ">Gestionnaire G2T du service " . $structure->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";
                $gestionnaire = $structure->gestionnaire();
                echo "\n<tr>";
                echo "<td>Nom du gestionnaire G2T : ";
                echo "<input id='infouser[" . $structure->id() . "]' name='infouser[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                $style = '';
                $extrainfo = '';
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->identitecomplete();
                    if (!$gestionnaire->isG2tUser())
                    {
                        $style = " style='background-color : #f5b7b1 ;' ";
                        $extrainfo = "<b><span style='color:red'> &#x1F828; Le gestionnaire défini n'a pas accès à l'application G2T. Veuillez le modifier ou contacter la DRH.</span></b>";
                    }
                }
                echo "' size=40 $style/>$extrainfo";
                //
                echo "<input type='hidden' id='gestion[" . $structure->id() . "]' name='gestion[" . $structure->id() . "]' value='";
                if (! is_null($gestionnaire))
                    echo $gestionnaire->agentid();
                echo "' class='infouser[" . $structure->id() . "]' /> ";
                ?>
    <script>
    		    	$('[id="<?php echo "infouser[". $structure->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
    	   </script>
    <?php
                echo "</tr>";

                // si la structure est dans la liste des structures ou l'agent est responsable (au sens strict)
                if (isset($structrespliste[$structure->id()])) {
                    echo "<tr><td>";

                    // $delegationuserid = "";
                    // $datedebutdeleg = "";
                    // $datefindeleg = "";
                    $structure->getdelegation($delegationuserid, $datedebutdeleg, $datefindeleg);

                    // echo "delegationuserid = $delegationuserid, datedebutdeleg = $datedebutdeleg, datefindeleg = $datefindeleg <br>";
                    $delegationuser = null;
                    if ($delegationuserid != "") {
                        $delegationuser = new agent($dbcon);
                        if (! $delegationuser->load($delegationuserid))
                        {
                            $erreur = "Impossible de charger la personne déléguée.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
                            $delegationuser = null;
                        }
                    }
                    echo "Délégation de responsabilité à ";
                    echo "<input id='infodelegation[" . $structure->id() . "]' name='infodelegation[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                    $style = '';
                    $extrainfo = '';
                    if (! is_null($delegationuser))
                    {
                        echo $delegationuser->identitecomplete();
                        if (!$delegationuser->isG2tUser())
                        {
                            $style = " style='background-color : #f5b7b1 ;' ";
                            $extrainfo = "<b><span style='color:red'> &#x1F828; Le délégué défini n'a pas accès à l'application G2T. Veuillez le modifier ou contacter la DRH.</span></b>";
                        }
                    }
                    echo "' size=40 $style/>$extrainfo";

                    echo "<input type='hidden' id='delegation[" . $structure->id() . "]' name='delegation[" . $structure->id() . "]' value='";
                    if (! is_null($delegationuser))
                        echo $delegationuser->agentid();
                    echo "' class='infodelegation[" . $structure->id() . "]' /> ";
                    ?>
    <script>
        		    	$('[id="<?php echo "infodelegation[". $structure->id() ."]" ?>"]').autocompleteUser(
        		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
        		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
        	   </script>
    <?php

                    echo "</td></tr>";
                    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
                    $calendrierid_deb = "date_debut";
                    $calendrierid_fin = "date_fin";
                    ?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	});
        });
        </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker("getDate")});
        	});
        });
        </script>
    <?php
                    echo "<tr><td style='padding-left:50px;'>";
                    echo "Début de la période de délégation :";
                    if ($fonctions->verifiedate($datedebutdeleg)) {
                        $datedebutdeleg = $fonctions->formatdate($datedebutdeleg);
                    }
                    ?>
    <input class="calendrier" type=text
    	name=<?php echo $calendrierid_deb . '[' . $structure->id() . ']'?>
    	id=<?php echo $calendrierid_deb . '[' . $structure->id() .']'?> size=10
    	minperiode='<?php echo date("d/m/Y"); // $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datedebutdeleg ?>'>
    <?php
                    echo "Fin de la période de délégation :";
                    if ($fonctions->verifiedate($datefindeleg)) {
                        $datefindeleg = $fonctions->formatdate($datefindeleg);
                    }

                    ?>
    <input class="calendrier" type=text
    	name=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	id=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	size=10
    	minperiode='<?php echo date("d/m/Y"); //$fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datefindeleg ?>'>
    <?php
                    echo "</td>";
                    echo "</tr>";
                }
                echo "<tr><td height=15></td></tr>";
                echo "</table>";
            }
            echo "<br><br><br>";
        }
    }

    echo "<input type='hidden' name='userid' value=" . $user->agentid() . ">";
    echo "<input type='hidden' name='action' value=" . $action . ">";
    echo "<input type='hidden' name='mode' value='" . $mode . "'>";

    if ($action == 'modif')
        echo "<input type='submit' value='Soumettre' />";
    echo "</form>";

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

