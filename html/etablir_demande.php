<?php
    require_once ('CAS.php');
    include './includes/casconnection.php';

    // Initialisation de l'utilisateur
    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    // echo "Userid = " . $userid;
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
    require_once ("./class/periodeobligatoire.php");
    require_once ("./class/optionCET.php");
    require_once ("./class/alimentationCET.php");
*/
    
    $user = new agent($dbcon);
    $user->load($userid);

    // Récupération de l'agent reponsable...
    if (isset($_POST["responsable"])) {
        $responsableid = $_POST["responsable"];
        $responsable = new agent($dbcon);
        $responsable->load($responsableid);
    } else {
        $responsableid = null;
        $responsable = null;
    }

    // Si passé en paramètre : Soit 'conges', soit 'absence'
    // permet d'afficher la page en mode 'demande d'absence' ou en mode 'demande de conges'
    if (isset($_POST["typedemande"])) {
        $typedemande = $_POST["typedemande"];
    } else {
        $typedemande = "conges";
        // $typedemande = "absence";
        // echo "Le type de page n'est pas renseigné... On le fixe à " . $typedemande . "<br>";
    }


    $previous = 0;
    if (isset($_POST["rh_mode"]))
    {
        $rh_mode = $_POST["rh_mode"];
        $rh_annee_previous = 2;
    }
    else
    {
        $rh_mode = 'no';
        $rh_annee_previous = 0;
    }
    // Si on est en mode RH on fixe $previous à $rh_annee_previous
    if (strcasecmp($rh_mode, "yes") == 0)
    {
        $previous = $rh_annee_previous;
    }

    if (isset($_POST["previous"]))
        $previoustxt = $_POST["previous"];
    else
        $previoustxt = null;
    if (strcasecmp($previoustxt, "yes") == 0)
        $previous = 1;

    //echo "<br>previous => $previous <br>";

    if (isset($_POST["agentid"]))
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) {
            $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
            $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
            $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
            $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
            $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "(uid=" . $agentid . ")";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array(
                "$LDAP_CODE_AGENT_ATTR"
            );
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            // echo "Le numéro HARPEGE de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
            if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
            }
        }

        if (! is_numeric($agentid)) {
            $agentid = null;
            $agent = null;
        }
    }
    else
        $agentid = null;

    $agent = new agent($dbcon);
    // echo "agentid = " . $agentid . "<br>";
    if ((is_null($agentid) or $agentid == "") and is_null($responsable)) {
        // echo "L'agent n'est pas passé en paramètre.... Récupération de l'agent à partir du ticket CAS <br>";
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $filtre = "(uid=$uid)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array(
            "$LDAP_CODE_AGENT_ATTR"
        );
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        // echo "Le numéro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
        $agent->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
    } elseif ((! is_null($agentid)) and $agentid != "")
        $agent->load($agentid);
    else
        $agent = null;

    //echo "<br>"; print_r($_POST); echo "<br>";

    $datefausse = FALSE;
    $masquerboutonvalider = FALSE;
    $msg_erreur = "";
    $erreurCET = '';
    

    // Récupération de la date de début
    if (isset($_POST["date_debut"])) {
        $date_debut = $_POST["date_debut"];
        // echo "date_debut = $date_debut <br>";
        // echo "fonctions->verifiedate(date_debut) = " . $fonctions->verifiedate($date_debut) . "<br>";
        if ($date_debut == "" or ! $fonctions->verifiedate($date_debut)) // is_null($date_debut) or
        {
            // Echo "La date est fausse !!!! <br>";
            $errlog = "La date de début n'est pas initialisée ou est incorrecte !!! <br/>";
            $msg_erreur .= $errlog;
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            $datefausse = TRUE;
        } else {
            // Récupération du moment de début
            if (isset($_POST["deb_mataprem"]))
                $deb_mataprem = $_POST["deb_mataprem"];
            else
                $deb_mataprem = null;
            if (is_null($deb_mataprem) or $deb_mataprem == "") {
                $errlog = "Le moment de début n'est pas initialisé !!! ";
                $msg_erreur .= $errlog . "<br/>";
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            }
            // date de début antérieure à la période
            if ($fonctions->formatdatedb($date_debut) < ($fonctions->anneeref() - $previous) . $fonctions->debutperiode()) {
                $errlog = "La date de début ne doit pas être antérieure au début de la période !!!";
                $msg_erreur .= $errlog . "<br/>";
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            }
        }
    } else {
        $date_debut = null;
        $datefausse = TRUE;
    }

    // Récupération de la date de fin
    if (isset($_POST["date_fin"])) {
        // echo "date_fin = $date_fin <br>";
        // echo "fonctions->verifiedate(date_fin) = " . $fonctions->verifiedate($date_fin) . "<br>";
        $date_fin = $_POST["date_fin"];
        if ($date_fin == "" or ! $fonctions->verifiedate($date_fin)) // is_null($date_fin) or
        {
            $errlog = "La date de fin n'est pas initialisée ou est incorrecte !!! ";
            $msg_erreur .= $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            $datefausse = TRUE;
        } else {
            // Récupération du moment de fin
            if (isset($_POST["fin_mataprem"]))
                $fin_mataprem = $_POST["fin_mataprem"];
            else
                $fin_mataprem = null;
            if (is_null($fin_mataprem) or (strcasecmp($fin_mataprem, "m") != 0 and strcasecmp($fin_mataprem, "a") != 0)) {
                $errlog = "Le moment de fin n'est pas initialisé !!!";
                $msg_erreur .= $errlog . "<br/>";
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            }
        }
    } else {
        $date_fin = null;
        $datefausse = TRUE;
    }

    if ($msg_erreur == "" and ! $datefausse) {
        $datedebutdb = $fonctions->formatdatedb($date_debut);
        $datefindb = $fonctions->formatdatedb($date_fin);
        if ($datedebutdb > $datefindb or ($datedebutdb == $datefindb and $deb_mataprem == 'a' and $fin_mataprem == 'm')) {
            $errlog = "Il y a une incohérence entre la date de début et la date de fin !!! ";
            $msg_erreur .= $errlog . "<br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            $datefausse = true;
        }
    }

    // # Récupération du type de l'absence (annuel, CET, ...)
    if (isset($_POST["listetype"])) {
        $listetype = $_POST["listetype"];
        $anneeref = $fonctions->congesanneeref($listetype);
        if ($anneeref != '' and ! $datefausse) {
            // On ajoute 2 car un congés 2014 est valable jusqu'en Mars 2016 => soit 2 ans de plus !!!
            $datelimite = ($anneeref + 2) . $fonctions->liredbconstante('FIN_REPORT');
            // echo "Date limite report = $datelimite <br>";

            // ------------------------------------------------------------------------------------
            // A décommenter pour empécher le reliquat d'être pris après la date de fin du report
            $datefindb = $fonctions->formatdatedb($date_fin);
            if ($datefindb > $datelimite)
            // ------------------------------------------------------------------------------------

            // ------------------------------------------------------------------------------------
            // A décommenter pour autoriser le reliquat à être pris après la fin du report
            // $datedebutdb = $fonctions->formatdatedb($date_debut);
            // ATTENTION : Pour l'année en cours on accepte que le debut soit postérieur au report
            // if (($datedebutdb > $datelimite) and (($anneeref + 2) != substr($datedebutdb, 0, 4)))
            // ------------------------------------------------------------------------------------
            {
                $errlog = "Le type de congés utilisé n'est pas valide pour la période demandée ! ";
                $msg_erreur .= $errlog . "<br/>";
            }
        }
    } else
        $listetype = null;
    if ((is_null($listetype) or $listetype == "") and ($msg_erreur == "" and ! $datefausse)) {
        // echo "Le type de demande n'est pas initialisé !!! <br>";
        $errlog = "Le type de demande n'est pas initialisé ! ";
        $msg_erreur .= $errlog . "<br/>";
        error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
    }

    // # Récupération du commentaire (s'il existe)
    $commentaire = "";
    if (isset($_POST["commentaire"]))
        $commentaire = trim($_POST["commentaire"]);
    if (! is_null($responsable) and $commentaire == "") {
        $errlog = "Le commentaire dans la saisie est obligatoire !!! ";
        $msg_erreur .= $errlog . "<br/>";
        error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
    } elseif ($commentaire == "" and $listetype == 'spec') {
        $errlog = "Le commentaire dans la saisie est obligatoire pour ce type d'absence ($listetype)!!! ";
        $msg_erreur .= $errlog . "<br/>";
        error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
    }

    // echo "Le commentaire vaut : " . $commentaire . "<br>";
    //echo "<br>datefausse : $datefausse => ";  if ($datefausse) echo "True"; else echo "False";  echo "<BR>";

    if (isset($_POST["congeanticipe"]))
        $congeanticipe = $_POST["congeanticipe"];
    else
        $congeanticipe = null;

    // # On regarde si le dossier est complet pour la période demandée ==> Si pas !! Pas de saisie possible
    if (! is_null($agent) and ! $datefausse) {
        if (! $agent->dossiercomplet($date_debut, $date_fin)) {
            $errlog = "Le dossier est incomplet sur la période $date_debut -> $date_fin ==> Vous ne pouvez pas établir de demande !!! ";
            $msg_erreur .= "<b>" . $errlog . "</b><br/>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($errlog));
            // $masquerboutonvalider = TRUE;
        }
    }

    require ("includes/menu.php");
    ?>
    <script type="text/javascript">
    	// fonction pour le click gauche
    	function planning_lclick(date,moment)
    	{
    		//alert("planning_click => " + date + "  "  + moment);
    		document.getElementById("date_debut").value = date;

    		if (moment.toLowerCase() =="m")
    			document.frm_demande_conge["deb_mataprem"][0].checked = true;
    		else
    			document.frm_demande_conge["deb_mataprem"][1].checked = true;
    	}
    	// fonction pour le click droit
    	function planning_rclick(date,moment)
    	{
    		//alert("planning_click => " + date + "  "  + moment);
    		document.getElementById("date_fin").value = date;

    		if (moment.toLowerCase() =="m")
    			document.frm_demande_conge["fin_mataprem"][0].checked = true;
    		else
    			document.frm_demande_conge["fin_mataprem"][1].checked = true;
    	}
    	</script>
    <!--
    	<script src="javascripts/jquery-1.8.3.js"></script>
    	<script src="javascripts//jquery-ui.js"></script>
     -->
    <?php

    // echo '<html><body class="bodyhtml">';

    // ###############################################################
    // # Affichage
    // ###############################################################

    //echo "msg_erreur 1 = " .$msg_erreur ." <br>";
    
    
    if (is_null($agent)) {
        echo "<form name='demandeforagent'  method='post' action='etablir_demande.php'>";
        if ($rh_mode=='yes')
        {
            echo "Personne à rechercher : <br>";
            echo "<form name='selectagentcet'  method='post' >";

            echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
            echo "' size=40 />";
            echo "<input type='hidden' id='agentid' name='agentid' value='";
            echo "' class='agent' /> ";
            ?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
    	<?php
        }
        else
        {
            $structureliste = $responsable->structrespliste();
            // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
            $agentlistefull = array();
            foreach ($structureliste as $structure) {
                $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
                // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
                $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                // echo "fin du select <br>";
                $structurefille = $structure->structurefille();
                foreach ((array) $structurefille as $structure) {
                    $responsable = $structure->responsable();
                    if ($responsable->harpegeid() != '-1') {
                        $agentlistefull[$responsable->nom() . " " . $responsable->prenom() . " " . $responsable->harpegeid()] = $responsable;
                    }
                }
            }
            if (isset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->harpegeid()])) {
                unset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->harpegeid()]);
            }
            ksort($agentlistefull);
            echo "<SELECT name='agentid'>";
            foreach ($agentlistefull as $keyagent => $membre) {
                echo "<OPTION value='" . $membre->harpegeid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
            }
            echo "</SELECT>";
        }
        echo "<br>";

        echo "<input type='hidden' name='typedemande' value='" . $typedemande . "'>";
        echo "<input type='hidden' name='responsable' value='" . $responsable->harpegeid() . "'>";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='congeanticipe' value='" . $congeanticipe . "'>";
        echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
        echo "<input type='hidden' name='rh_mode' value='" . $rh_mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    } else {

        if (strcasecmp($typedemande, "conges") == 0) {
            $periode = new periodeobligatoire($dbcon);
            $liste = $periode->load($fonctions->anneeref());
            if (count($liste) > 0)
            {
                echo "<font color=#FF0000><center>";
                echo "<div class='niveau1' style='width: 700px; padding-top:10px; padding-bottom:10px;border: 3px solid #888B8A ;background: #E5EAE9;'><b>RAPPEL : </b>Les périodes de fermeture obligatoire de l'établissement sont les suivantes : <ul>";
                foreach ($liste as $element)
                {
                    echo "<li style='text-align: left;' >Du " . $fonctions->formatdate($element["datedebut"]) . " au " . $fonctions->formatdate($element["datefin"]) . "</li>";
                }
                echo "</ul>";
                echo "Veuillez penser à poser vos congés en conséquence.";
                echo "</div></center>";
                echo "</font>";
                echo "<br><br>";
            }

/*
            echo "<font color=#FF0000><center>";
            echo "<div class='niveau1' style='width: 700px; padding-top:10px; padding-bottom:10px;border: 3px solid #888B8A ; text-align: center;background: #E5EAE9;'><b>IMPORTANT : </b>Veuillez noter que l'utilisation des reliquats 2019-2020 a été prolongée exceptionnellement jusqu'au 30 juin 2021, en raison de la crise sanitaire, et non jusqu'au 31 mars 2021.<br></div>";
            echo "</center></font>";
            echo "<br>";
*/

            echo "Demande de congés pour " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br/>";
            $solde = new solde($dbcon);
            $codecongeanticipe = "ann" . substr($fonctions->anneeref() + 1 - $previous, 2);
            $result = $solde->load($agent->harpegeid(), $codecongeanticipe);
            if ($congeanticipe != "") {
                // On pose un congé par anticipation
                // - Vérifier que l'utilisateur est responsable (ou pas !!!)
                // - Vérifier que le solde du congé annuel est = 0
                // - Afficher le congé annuel de l'année de ref + 1
                if ($result != "") {
                    $result = $solde->creersolde($codecongeanticipe, $agent->harpegeid());
                    if ($result != "") {
                        $msg_erreur = $msg_erreur . "<br/><b>" . $result . "</b>";
                        $msg_erreur = $msg_erreur . "<b>Contactez l'administrateur pour qu'il crée le type de congés...</b><br/>";
                        $masquerboutonvalider = TRUE; // Empêche le bouton de s'afficher !!!
                    } else
                        $msg_erreur = $msg_erreur . "<br/><P style='color: green'>Création du solde de congés " . $codecongeanticipe . " pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "</P><br/>";
                } else {
                    // echo "Avant solde liste... <br>";
                    $soldeliste = $agent->soldecongesliste($fonctions->anneeref() - $previous);
                    // echo "Avant le for each <br>";
                    foreach ($soldeliste as $keysolde => $solde) {
                        if (strcasecmp($solde->typeabsenceid(), "ann") == 0 . substr(($fonctions->anneeref() - $previous), 2)) {
                            if ($solde->solde() != 0) {
                                $msg_erreur = $msg_erreur . "<br/><b>Impossible de poser un congé par anticipation. Il reste " . $solde->solde() . " jours de congés à poser pour " . $solde->typelibelle() . "</b><br/>";
                                $masquerboutonvalider = TRUE; // Empêche le bouton de s'afficher !!!
                            }
                        }
                    }
                    // echo "Apres le foreach <br>";
                }
            }
        } else {
            echo "Demande d'autorisation d'absence pour " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
        }
        // echo "Date fausse (1) = " . $datefausse . "<br>";

        //echo "msg_erreur 2 = " .$msg_erreur ." <br>";

        if (! $datefausse) {
            $planning = new planning($dbcon);
            // echo "Date fin = " . $date_fin . "<br>";
            // echo "Date de fin (db) = " . $fonctions->formatdatedb($date_fin) . "<br>";
            // echo "Annee ref + 1 = " . ($fonctions->anneeref()+1) . "<br>";
            // echo "Fin de période = ". $fonctions->finperiode() . "<br>";
            // echo "LIMITE CONGE = " . $fonctions->liredbconstante("LIMITE_CONGE_PERIODE") . "<br>";

            if (strcasecmp($rh_mode, "yes") == 0)
            {
                // On est en mode "RH" donc on ignore la présence/absence de l'agent
                //echo 'On est en mode "RH" donc on ignore la présence/absence de l agent <br>';
                $ignoreabsenceautodecla = TRUE;
            }

            // Si la date de fin est supérieur à la date de début et que l'on accepte que ca déborde
            // on fait un traitement spécial <=> pas de vérification des autodéclarations
            elseif ($fonctions->formatdatedb($date_fin) > ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode() and strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) {
                // Si la date de fin est supérieure d'un mois à la date de fin de période ==> On refuse
                // ==> On n'accepte que de déborder d'un mois
                $datetemp = ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode();
                $timestamp = strtotime($datetemp);
                $datetemp = date("Ymd", strtotime("+1month", $timestamp)); // On passe au mois suivant
                if ($fonctions->formatdatedb($date_fin) > $datetemp) {
                    $msg_erreur = $msg_erreur . "La date de fin est trop loin - en dehors de la période (1 mois)  <br>";
                    $ignoreabsenceautodecla = FALSE;
                } else
                    $ignoreabsenceautodecla = TRUE;
            } else
                $ignoreabsenceautodecla = FALSE;

            // Echo "Avant le est present .... <br>";
            $present = $planning->agentpresent($agent->harpegeid(), $date_debut, $deb_mataprem, $date_fin, $fin_mataprem, $ignoreabsenceautodecla);
            if (! $present)
                $msg_erreur = $msg_erreur . $agent->prenom() . "  " . $agent->nom() . " n'est pas présent durant la période du $date_debut au $date_fin......!!! <br>";
        }

        // echo "Date fausse (2) = " . $datefausse . "<br>";
        //echo "msg_erreur 3 = " .$msg_erreur. " <br>";

        if ($msg_erreur != "" or $datefausse) {

            echo "<P style='color: red'><B><FONT SIZE='5pt'>";
            if ($msg_erreur != "" and isset($_POST["valider"])) {
                echo "Votre demande n'a pas été enregistrée... <BR>";
            }
            echo $msg_erreur . " </B></FONT></P>";
            error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($msg_erreur));
            // echo "J'ai print le message d'erreur pasautodeclaration = $masquerboutonvalider <br>";
        } elseif (! $datefausse) {
            // On recherche les declarations de TP relatives à cette demande
            $affectationliste = $agent->affectationliste($date_debut, $date_fin);
            if (! is_null($affectationliste)) {

                $declarationTPliste = array();
                foreach ($affectationliste as $affectation) {
                    // On recupère la première affectation
                    // $affectation = new affectation($dbcon);
                    // $affectation = reset($affectationliste);
                    // echo "Datedebut = $date_debut, Date fin = $date_fin <br>";
                    $declarationTPliste = array_merge((array) $declarationTPliste, (array) $affectation->declarationTPliste($date_debut, $date_fin));
                }
                // echo "declarationTPliste = "; print_r($declarationTPliste); echo "<br>";
            }

            // echo "Je vais sauver la demande <br>";
            unset($demande);
            $demande = new demande($dbcon);
            // $demande->agent($agent->harpegeid());
            // $demande->structure($agent->structure()->id());
            $demande->type($listetype);
            $demande->datedebut($date_debut);
            $demande->datefin($date_fin);
            $demande->moment_debut($deb_mataprem);
            $demande->moment_fin($fin_mataprem);
            $demande->commentaire($commentaire);
            if ($congeanticipe != "")
                $ignoresoldeinsuffisant = TRUE;
            else
                $ignoresoldeinsuffisant = FALSE;
            // echo "demande->nbredemijrs_demande() AVANT = " . $demande->nbredemijrs_demande() . "<br>";
            $resultat = $demande->store($declarationTPliste, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
            // echo "demande->nbredemijrs_demande() APRES = " . $demande->nbredemijrs_demande() . "<br>";
            if ($resultat == "") {
                // Si on est en mode "responsable" alors la demande doit être validée automatiquement
                if (! is_null($responsable)) {
                    // Insertion code pour validation de la demande automatique....
                    $demandeid = $demande->id();
                    unset($demande);
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    $demande->statut("v");
                    $msgerreur = "";
                    $msgerreur = $demande->store();
                    if ($msgerreur != "")
                        echo "<p style='color: red'>Pas de validation automatique de la demande car " . $msgerreur . "</p><br>";
                    else {
                        $ics = null;
                        $pdffilename[0] = $demande->pdf($user->harpegeid());
                        $agent = $demande->agent();
                        $ics = $demande->ics($agent->mail());
                        $corpmail = "Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . ".";
                        if (strcasecmp($demande->type(), "cet") == 0 and strcasecmp($demande->statut(), "v") == 0) // Si c'est une demande prise sur un CET et qu'elle est validée => On joint le PDF d'utilisation du CET en congés
                        {
                            // On remplace les '\' par des '/' et on cherche la position du dernier '/'
                            $position = strrpos(str_replace('\\', '/', $pdffilename[0]), '/');
                            // La base du chemin PDF est donc la sous-chaine du nom du fichier PDF de la demande !!
                            $basepdfpath = substr($pdffilename[0], 0, $position);
                            // On ajoute le fichier PDF d'utilisation du CET en congés
                            $pdffilename[1] = $basepdfpath . '/../../documents/Utilisation_CET_Conges.pdf';
                            $corpmail = $corpmail . "\n\nVous devez retourner par mail le document " . basename($pdffilename[1]) . "  rempli et signé à :\n";
                            $arrayagentrh = $fonctions->listeprofilrh("1"); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                            foreach ($arrayagentrh as $gestrh) {
                                $corpmail = $corpmail . $gestrh->identitecomplete() . " : " . $gestrh->mail() . "\n";
                            }
                        }
                        $user->sendmail($agent, "Modification d'une demande de congés ou d'absence", $corpmail, $pdffilename, $ics);

                        if (strcasecmp($demande->type(), "cet") == 0 and strcasecmp($demande->statut(), "v") == 0) // Si c'est une demande prise sur un CET et qu'elle est validée => On envoie un mail au gestionnaire RH de CET
                        {
                            $arrayagentrh = $fonctions->listeprofilrh("1"); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                            foreach ($arrayagentrh as $gestrh) {
                                $corpmail = "Une demande de congés a été " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . " sur le CET de " . $agent->identitecomplete() . ".\n";
                                $corpmail = $corpmail . "\n";
                                $corpmail = $corpmail . "Détail de la demande :\n";
                                $corpmail = $corpmail . "- Date de début : " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . "\n";
                                $corpmail = $corpmail . "- Date de fin : " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "\n";
                                $corpmail = $corpmail . "Nombre de jours demandés : " . $demande->nbrejrsdemande() . "\n";
                                // $corpmail = $corpmail . "La demande est actuellement en attente de validation.\n";
                                $user->sendmail($gestrh, "Changement de statut d'une demande de congés sur CET", $corpmail);
                            }
                        }
                    }
                }
                else
                {
                    // On met la tentative dans l'agenda de l'agent
                    $demandeid = $demande->id();
                    unset($demande);
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    $agent = $demande->agent();
                    
                    $ics = $demande->ics($agent->mail());
                    //echo "ics = " . $ics . "<br><br>";
                    $errormsg = $agent->updatecalendar($ics);
                    //echo "errormsg = $errormsg <br>";
                }
                $msgstore = "Votre demande a été enregistrée... ==> ";
                if (strcasecmp($typedemande, "conges") == 0) {
                    if (($demande->nbrejrsdemande()) > 1) {
                        $msgstore .= $demande->nbrejrsdemande() . " jours vous seront decomptés (" . $demande->typelibelle() . ")";
                    } else {
                        $msgstore .= $demande->nbrejrsdemande() . " jour vous sera decompté (" . $demande->typelibelle() . ")";
                    }
                } else {
                    $msgstore .= "Vous serez absent durant " . $demande->nbrejrsdemande() . " jour(s)";
                }
                echo "<P style='color: green'>" . $msgstore . " sous réserve du respect des règles de gestion.</P>";
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($msgstore));
            } else {
                $msgstore = "Votre demande n'a pas été enregistrée... ==> MOTIF : " . $resultat;
                error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($msgstore));
                echo "<P style='color: red'><B><FONT SIZE='5pt'>" . $msgstore . " </B></FONT></P>";
            }
        }
        echo "<span style='border:solid 1px black; background:orange; width:600px; display:block;'>";
        echo "<P style='color: black'>";
        echo "Les situations particulières (notamment liées à des problèmes de santé) ne font pas l'objet d'un suivi dans G2T. Vous devez pour ces cas précis vous rapprocher de votre chef de service.<br>";
        echo "</P>";
        echo "</span>";

        ?>
    <form name="frm_demande_conge" method="post">

    	<input type="hidden" name="agentid"
    		value="<?php echo $agent->harpegeid(); ?>">

    	<table>
    		<tr>
    			<td>Date de début de la demande :</td>
    <?php
        // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
        $calendrierid_deb = "date_debut";
        $calendrierid_fin = "date_fin";
        echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_deb . '" ).datepicker({minDate: $( "#' . $calendrierid_deb . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_deb . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_deb . '").change(function () {
    			$("#' . $calendrierid_fin . '").datepicker("destroy");
    			$("#' . $calendrierid_fin . '").datepicker({minDate: $("#' . $calendrierid_deb . '").datepicker("getDate"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
    	});
    });
    </script>
    ';
        echo '
    <script>
    $(function()
    {
    	$( "#' . $calendrierid_fin . '" ).datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $( "#' . $calendrierid_fin . '" ).attr("maxperiode")});
    	$( "#' . $calendrierid_fin . '").change(function () {
    			$("#' . $calendrierid_deb . '").datepicker("destroy");
    			$("#' . $calendrierid_deb . '").datepicker({minDate: $( "#' . $calendrierid_fin . '" ).attr("minperiode"), maxDate: $("#' . $calendrierid_fin . '").datepicker("getDate")});
    	});
    });
    </script>
    ';
        
        // Calcul de la date de debut minimale pour le calendrier de début
        if ($rh_mode == 'yes') 
        {
            $minperiode_debut =  $fonctions->formatdate($fonctions->anneeref()-$rh_annee_previous . $fonctions->debutperiode()); 
        }
        else 
        {
            $minperiode_debut = $fonctions->formatdate($fonctions->anneeref()-$previous . $fonctions->debutperiode());
        }
        
        // Calcul de la date de fin maximale pour le calendrier de début
        if ($rh_mode == 'yes')
        {
            $maxperiode_debut = $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode());
        }
        elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0) 
        { 
            $indexmois = substr($fonctions->debutperiode(),0,2); 
            $nbrejrsmois = $fonctions->nbr_jours_dans_mois($indexmois,($fonctions->anneeref()+1-$previous)); 
            $maxperiode_debut = $fonctions->formatdate(($fonctions->anneeref()+1-$previous).$indexmois.$nbrejrsmois); 
        } 
        else 
        {
            $maxperiode_debut = $fonctions->formatdate($fonctions->anneeref()+1-$previous . $fonctions->finperiode()); 
        }
        
        // Calcul de la date de debut minimale pour le calendrier de fin
        if ($rh_mode == 'yes') 
        {
            $minperiode_fin = $fonctions->formatdate($fonctions->anneeref()-$rh_annee_previous . $fonctions->debutperiode()); 
        }
        else
        {
            $minperiode_fin = $fonctions->formatdate($fonctions->anneeref()-$previous . $fonctions->debutperiode()); 
        }
        
        // Calcul de la date de fin maximale pour le calendrier de fin
        if ($rh_mode == 'yes')
        {
            $maxperiode_fin = $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode());
        }
        elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"),"n")==0) 
        { 
            $indexmois = substr($fonctions->debutperiode(),0,2);
            $nbrejrsmois = $fonctions->nbr_jours_dans_mois($indexmois,($fonctions->anneeref()+1-$previous)); 
            $maxperiode_fin = $fonctions->formatdate(($fonctions->anneeref()+1-$previous).$indexmois.$nbrejrsmois); 
        } 
        else 
        { 
            $maxperiode_fin = $fonctions->formatdate($fonctions->anneeref()+1-$previous . $fonctions->finperiode());
        }
        
        ?>
    			<br>
    			<td width=1px><input class="calendrier" type=text name=date_debut
    				id=<?php echo $calendrierid_deb ?> size=10
    				minperiode='<?php echo "$minperiode_debut"; ?>'
    				maxperiode='<?php echo "$maxperiode_debut"; ?>'></td>
    			<td align="left"><input type='radio' name='deb_mataprem' value='m'
    				checked>Matin <input type='radio' name='deb_mataprem' value='a'>Après-midi</td>
    		</tr>
    		<tr>
    			<td>Date de fin de la demande :</td>
    			<td width=1px><input class="calendrier" type=text name=date_fin
    				id=<?php echo $calendrierid_fin ?> size=10
    				minperiode='<?php echo "$minperiode_fin"; ?>'
    				maxperiode='<?php echo "$maxperiode_fin"; ?>'></td>
    			<td align="left"><input type='radio' name='fin_mataprem' value='m'>Matin
    				<input type='radio' name='fin_mataprem' value='a' checked>Après-midi</td>
    		</tr>
    		<tr>
    			<td>Type de congé :</td>
    			<td colspan="2">
    <?php
        if (strcasecmp($typedemande, "conges") == 0) {
            // echo "congesanticipe = " . $congeanticipe . "<br>";
            // C'est une demande par anticipation
            if ($congeanticipe != "") {
                $solde = new solde($dbcon);
                $solde->typeabsenceid("ann" . substr(($fonctions->anneeref() + 1 - $previous), 2));
                echo "<select name='listetype'>";
                echo "<OPTION value='" . $solde->typeabsenceid() . "'>" . $solde->typelibelle() . "</OPTION>";
                echo "</select>";
                echo "<input type='hidden' name='typedemande' value='conges' ?>";
            } else {
                $soldeliste = $agent->soldecongesliste($fonctions->anneeref() - $previous);
                // Si on est dans l'année précédente, on peut poser des congés avec le solde de l'année future
                // Exemple : On peut poser des congés en Aout 2015/2016, avec le solde 2016/2017 (s'il existe <=> S'il est calculé)
                if ($previous != 0) {
                    $soldelisteannee = $agent->soldecongesliste($fonctions->anneeref());
                    $soldeliste = array_merge((array) $soldeliste, (array) $soldelisteannee);
                }
                // print_r ($soldeliste); echo "<br>";
                if (! is_null($soldeliste)) {
                    echo "<select name='listetype'>";
                    $nbretype = 0;
                    foreach ($soldeliste as $keysolde => $solde) {
                        if ($solde->solde() > 0) {
                            if ($rh_mode == 'yes' and $solde->typeabsenceid() != 'cet')
                            {
                                // On n'affiche pas le solde de congés car on est en mode RH et seul le CET est affiché
                            }
                            else
                            {
                            	// si l'agent a une demande d'alimentation ou un droit d'option en cours sur son CET il ne peut utiliser ni ses reliquats ni son CET
                            	$optionCET = new optionCET($dbcon);
                            	$alimentationCET = new alimentationCET($dbcon);
                            	if (sizeof($agent->getDemandesOption('', array($optionCET::STATUT_EN_COURS, $optionCET::STATUT_PREPARE))) != 0)
                            	{
                            		if ($solde->typeabsenceid() == 'cet')
                            		{
                            			$erreurCET .= "Vous ne pouvez pas utiliser votre solde CET car vous avez une demande de droit d'option sur CET en cours. <br>";
                            		}
                            		else 
                            		{
                            			echo "<OPTION value='" . $solde->typeabsenceid() . "'>" . $solde->typelibelle() . "</OPTION>";
                            			$nbretype = $nbretype + 1;
                            		}
                            	}
                            	elseif (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
                            	{
                            		if ($solde->typeabsenceid() == 'cet')
                            		{
                            			$erreurCET .= "Vous ne pouvez pas utiliser votre solde CET car vous avez une demande d'alimentation du CET en cours. <br>";
                            		}
                            		elseif ($fonctions->congesanneeref($solde->typeabsenceid())+0 == $fonctions->anneeref()-1)
                            		{
                            			$erreurCET .= "Vous ne pouvez pas utiliser vos reliquats ".($fonctions->anneeref()-1)."/".$fonctions->anneeref()." car vous avez une demande d'alimentation du CET en cours. <br>";
                            		}
                            		else
                            		{
                            			echo "<OPTION value='" . $solde->typeabsenceid() . "'>" . $solde->typelibelle() . "</OPTION>";
                            			$nbretype = $nbretype + 1;
                            		}
                            	}
                            	else 
                            	{
                            		echo "<OPTION value='" . $solde->typeabsenceid() . "'>" . $solde->typelibelle() . "</OPTION>";
                                	$nbretype = $nbretype + 1;
                            	}
                            }
                        }
                    }
                    echo "</select>";
                    // echo "nbretype = $nbretype <br>";
                    if ($nbretype == 0)
                        $masquerboutonvalider = true;
                }
                echo "<input type='hidden' name='typedemande' value='conges' ?>";
            }
        } else {
            echo "<SELECT name='listetype'>";
            $listecateg = $fonctions->listecategorieabsence();
            echo "<OPTION value=''></OPTION>";
            foreach ($listecateg as $keycateg => $nomcateg) {
                echo "<optgroup label='" . str_replace("_", " ", $nomcateg) . "'>";
                $listeabs = $fonctions->listeabsence($keycateg);
                foreach ($listeabs as $keyabs => $nomabs)
                    echo "<OPTION value='" . $keyabs . "'>" . $nomabs . "</OPTION>";
                echo "</optgroup>";
            }
            echo "</SELECT>";
            echo "<br>";

            echo "<input type='hidden' name='typedemande' value='absence' ?>";
        }
        ?>
    			</td>
    		</tr>
    	</table>
    <?php
        echo "<br>";
        if (! is_null($responsable)) {
            echo "Commentaire (obligatoire) : <br>";
            echo "<input type='hidden' name='responsable' value='" . $responsableid . "'>";
            echo "<textarea rows='4' cols='60' name='commentaire'></textarea> <br>";
            echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() . "'>";
            echo "<br>";
        } elseif (strcasecmp($typedemande, "conges") != 0) {
            echo "Commentaire (obligatoire pour les 'Absences autorisées par l'établissement', sinon facultatif) : <br>";
            echo "<textarea rows='4' cols='60' name='commentaire'></textarea> <br>";
            echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() . "'>";
            echo "<br>";
        }

        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='congeanticipe' value='" . $congeanticipe . "'>";
        echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
        echo "<input type='hidden' name='rh_mode' value='" . $rh_mode . "'>";
        if ($erreurCET != '')
        {
        	echo $erreurCET."<br>";
        }
        if (! $masquerboutonvalider)
            echo "<input type='submit' name='valider' id='valider' value='Soumettre' />";
        echo "<br><br>";
        ?>
    	</form>

    <?php
        // echo "Date_debut = $date_debut date_fin= $date_fin <br>";
        // echo "Debut periode = " . $fonctions->debutperiode() . "<br>";
        // echo "Fin periode = " . $fonctions->finperiode() . "<br>";
        // echo "Annee ref = " . $fonctions->anneeref() . "<br>";
        // echo "debut = " . $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()) . " fin =" . $fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()) . "<br>";
        if ($rh_mode == 'yes')
        {
            for ($index=$rh_annee_previous; $index>=0; $index--)
            {
                echo $agent->planninghtml($fonctions->formatdate(($fonctions->anneeref() - $index) . $fonctions->debutperiode()), $fonctions->formatdate(($fonctions->anneeref() + 1 - $index) . $fonctions->finperiode()), TRUE, FALSE,true);
                echo "<br>";
            }

        }
        elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) {
            $datetemp = ($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode();
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("+1month", $timestamp)); // On passe au mois suivant
            $timestamp = strtotime($datetemp);
            $datetemp = date("Ymd", strtotime("-1days", $timestamp)); // On passe à la veille
            echo $agent->planninghtml($fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode()), $datetemp, TRUE,true,true);
        } else
            echo $agent->planninghtml($fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode()), $fonctions->formatdate(($fonctions->anneeref() + 1 - $previous) . $fonctions->finperiode()), TRUE,true, true);

        echo $agent->soldecongeshtml($fonctions->anneeref() - $previous);
        echo $agent->affichecommentairecongehtml();
        echo "<br>";
    }
?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

