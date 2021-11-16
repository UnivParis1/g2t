<?php
    require_once ('CAS.php');
    include './includes/casconnection.php';

    // Initialisation de l'utilisateur
    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }

    require_once ("./includes/all_g2t_classes.php");
/*
    require_once ("./class/agent.php");
    require_once ("./class/structure.php");
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
*/
    
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
        foreach ($reportlist as $harpegeid => $reportvalue) {
            $complement = new complement($dbcon);
            $complement->complementid('REPORTACTIF');
            $complement->harpegeid($harpegeid);
            $complement->valeur($reportvalue);
            $complement->store();
            unset($complement);
        }
    }

    $msgerreur = "";
    if (is_array($enfantmaladelist)) {
        foreach ($enfantmaladelist as $harpegeid => $enfantmaladevalue) {
            // echo "strcasecmp(intval ... => " . strcasecmp(intval($enfantmaladevalue),$enfantmaladevalue) . "<br>";
            // echo "intval >=0 => " . (intval($enfantmaladevalue)>=0) . "<br>";
            if ((strcasecmp(intval($enfantmaladevalue), $enfantmaladevalue) == 0) and (intval($enfantmaladevalue) >= 0)) // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
            {
                $complement = new complement($dbcon);
                $complement->complementid('ENFANTMALADE');
                $complement->harpegeid($harpegeid);
                $complement->valeur(intval($enfantmaladevalue));
                $complement->store();
                unset($complement);
            } else {
                $agent = new agent($dbcon);
                $agent->load($harpegeid);
                $msgerreur = $msgerreur . "Le nombre de jour 'Garde d'enfant' saisi n'est pas correct pour l'agent " . $agent->identitecomplete() . " <br>";
                unset($agent);
            }
        }
    }

    if (is_array($cumultotallist)) {
        foreach ($cumultotallist as $harpegeid => $cumultotal) {
            if (isset($datedebutcetlist[$harpegeid])) {
                if ($fonctions->verifiedate($datedebutcetlist[$harpegeid])) {
                    $cet = new cet($dbcon);
                    $cet->cumultotal($cumultotal);
                    $cet->agentid($harpegeid);
                    $cet->datedebut($datedebutcetlist[$harpegeid]);
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

        // Initialisation des infos LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);

        // ATTENTION : La $valeur est soit le HARPEGEID soit le UID si on vient de le modifier !!
        foreach ($arraygestionnaire as $structureid => $valeur) {
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfouser[$structureid]) == "") {
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->gestionnaire("");
                $structure->store();
            } else {
                // On va chercher dans le LDAP la correspondance UID => HARPEGEID
                $filtre = "(uid=" . $valeur . ")";
                $dn = $LDAP_SEARCH_BASE;
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                // echo "Le numéro HARPEGE du responsable est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " pour la structure " . $structure->nomlong() . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
                    $harpegeid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                else
                    $harpegeid = "";
                // Si le harpegeid n'est pas vide ou null
                if ($harpegeid != '' and (! is_null($harpegeid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    $structure->gestionnaire($harpegeid);
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

    if (is_array($arraydelegation)) {

        // Initialisation des infos LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);

        // ATTENTION : La $valeur est soit le HARPEGEID soit le UID si on vient de le modifier !!
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
                // On va chercher dans le LDAP la correspondance UID => HARPEGEID
                $filtre = "(uid=" . $valeur . ")";
                $dn = $LDAP_SEARCH_BASE;
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                // echo "Le numéro HARPEGE du responsable est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " pour la structure " . $structure->nomlong() . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                    $harpegeid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                } else {
                    $harpegeid = $valeur;
                }
                // echo "Harpegeid = $harpegeid <br>";
                // Si le harpegeid n'est pas vide ou null
                if ($harpegeid != '' and (! is_null($harpegeid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    // On ne peut pas mettre le responsable de la structure comme délégué
                    if ($harpegeid == $user->harpegeid()) {
                        // On récupère la liste des structures ou l'utilisateur est responsable (sens strict)
                        $structrespliste = $user->structrespliste(false);
                        // Si la structure courante est définie dans le tableau des structures
                        // On ne peut pas le mettre délégué
                        if (isset($structrespliste[$structureid])) {
                            $resp_est_delegue = true;
                        }
                    }

                    if ($resp_est_delegue) {
                        echo "<FONT SIZE='2pt' COLOR='#FF0000'><B>Vous ne pouvez pas saisir le responsable (" . $user->identitecomplete() . ") de la structure '" . $structure->nomlong() . "' comme délégué.</B><br>La délégation n'est pas enregistrée.</FONT><br>";
                    } else {
                        $datedebutdeleg = "";
                        if (isset($arraydatedebut[$structure->id()]))
                            $datedebutdeleg = $arraydatedebut[$structure->id()];
                        $datefindeleg = "";
                        if (isset($arraydatefin[$structure->id()]))
                            $datefindeleg = $arraydatefin[$structure->id()];

                        // echo "datedebutdeleg = $datedebutdeleg datefindeleg = $datefindeleg <br>";
                        if ($datedebutdeleg == "" or $datefindeleg == "") {
                            echo "<FONT SIZE='5pt' COLOR='#FF0000'><B>Un agent délégué est saisi, mais la date de début ou la date de fin de la période est vide !!!</B><br>La délégation n'est pas enregistrée.</FONT><br>";
                        } else {
                            // echo "On enregistre la delegation.... <br>";
                        	$structure->setdelegation($harpegeid, $datedebutdeleg, $datefindeleg, $userid);
                            $errlog = $user->identitecomplete() . " : Enregistrement d'une délégation sur " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $harpegeid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
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
        echo "<B><P style='color: red'> $msgerreur </P></B>";
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
        if (is_array($structure->agentlist(date('d/m/Y'), date('d/m/Y'), 'n'))) {
            if ($mode == 'resp')
                echo $structure->dossierhtml(($action == 'modif'), $userid);
            else
                echo $structure->dossierhtml(($action == 'modif'));

            echo "Autoriser l'affichage du planning tous les agents des sous-structures (responsable/gestionnaire) : ";
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
            echo "Autoriser l'affichage uniquement du planning des responsables des sous-structures (responsable/gestionnaire) : ";
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
            echo "Autoriser la validation des demandes d'une sous-structure par le responsable de la structure parente : ";
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
                echo "Autoriser la validation des demandes des agents par le gestionnaire : ";
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
                echo ">Responsable du service " . $structure->nomcourt() . "</OPTION>";
                echo "<OPTION value=2";
                if ($codeinterne == 2)
                    echo " selected='selected' ";
                echo ">Gestionnaire du service " . $structure->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";

                $parentstruct = null;
                $parentstruct = $structure->parentstructure();
                $structure->resp_envoyer_a($codeinterne);
                echo "<tr>";
                echo "<td>";
                echo "Envoyer les demandes de congés du responsable au : ";
                echo "<SELECT name='resp_mail[" . $structure->id() . "]' size='1'>";
                if (! is_null($parentstruct)) {
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
                echo ">Gestionnaire du service " . $structure->nomcourt() . "</OPTION>";
                echo "</SELECT>";
                echo "</td>";
                echo "</tr>";
                $gestionnaire = $structure->gestionnaire();
                echo "\n<tr>";
                echo "<td>Nom du gestionnaire : ";
                echo "<input id='infouser[" . $structure->id() . "]' name='infouser[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                if (! is_null($gestionnaire))
                    echo $gestionnaire->identitecomplete();
                echo "' size=40 />";
                //
                echo "<input type='hidden' id='gestion[" . $structure->id() . "]' name='gestion[" . $structure->id() . "]' value='";
                if (! is_null($gestionnaire))
                    echo $gestionnaire->harpegeid();
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
                        $delegationuser->load($delegationuserid);
                    }
                    echo "Délégation de responsabilité à ";
                    echo "<input id='infodelegation[" . $structure->id() . "]' name='infodelegation[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                    if (! is_null($delegationuser))
                        echo $delegationuser->identitecomplete();
                    echo "' size=40 />";

                    echo "<input type='hidden' id='delegation[" . $structure->id() . "]' name='delegation[" . $structure->id() . "]' value='";
                    if (! is_null($delegationuser))
                        echo $delegationuser->harpegeid();
                    echo "' class='infodelegation[" . $structure->id() . "]' /> ";
                    ?>
    <script>
        		    	$('[id="<?php echo "infodelegation[". $structure->id() ."]" ?>"]').autocompleteUser(
        		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
        		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
        	   </script>
    <?php

                    echo "</td>";
                    echo "<td>";
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

                    echo "Début de la période :";
                    if ($fonctions->verifiedate($datedebutdeleg)) {
                        $datedebutdeleg = $fonctions->formatdate($datedebutdeleg);
                    }
                    ?>
    <td width=1px><input class="calendrier" type=text
    	name=<?php echo $calendrierid_deb . '[' . $structure->id() . ']'?>
    	id=<?php echo $calendrierid_deb . '[' . $structure->id() .']'?> size=10
    	minperiode='<?php echo date("d/m/Y"); // $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datedebutdeleg ?>'></td>
    <?php
                    echo "</td>";
                    echo "<td>";
                    echo "Fin de la période :";
                    if ($fonctions->verifiedate($datefindeleg)) {
                        $datefindeleg = $fonctions->formatdate($datefindeleg);
                    }

                    ?>
    <td width=1px><input class="calendrier" type=text
    	name=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	id=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	size=10
    	minperiode='<?php echo date("d/m/Y"); //$fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datefindeleg ?>'></td>
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

    echo "<input type='hidden' name='userid' value=" . $user->harpegeid() . ">";
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

