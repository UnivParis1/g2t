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
        header('Location: index.php');
        exit();
    }

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
    require_once ("./class/alimentationCET.php");
    require_once ("./class/optionCET.php");
*/
    
    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["agentid"])) {
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
            $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
            $filtre = "($LDAP_UID_AGENT_ATTR=$agentid)";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array(
                "$LDAP_CODE_AGENT_ATTR"
            );
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            // echo "Le numéro AGENT de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
            if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
            }
        }

        if (! is_numeric($agentid)) {
            $agentid = null;
            $agent = null;
        } else {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } else {
        $agentid = null;
        $agent = null;
    }

    $mode = null;
    if (isset($_POST["mode"]))
        $mode = $_POST["mode"];

    $nbr_jours_cet = null;
    if (isset($_POST["nbr_jours_cet"]))
        $nbr_jours_cet = str_ireplace(",", ".", $_POST["nbr_jours_cet"]);

    if (isset($_POST["nbrejoursdispo"]))
        $nbrejoursdispo = $_POST["nbrejoursdispo"];
    else
        $nbrejoursdispo = null;

    if (isset($_POST["typeretrait"]))
        $typeretrait = $_POST["typeretrait"];
    else
        $typeretrait = null;

    $ajoutcet = null;
    if (isset($_POST["ajoutcet"]))
        $ajoutcet = $_POST["ajoutcet"];

    $retraitcet = null;
    if (isset($_POST["retraitcet"]))
        $retraitcet = $_POST["retraitcet"];

    $nocheck = 'no';
    if (isset($_POST["nocheck"]))
        $nocheck = $_POST["nocheck"];


    $msg_erreur = "";

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    // print_r($_POST); echo "<br><br>";

    if (strcasecmp($mode, "gestrh") == 0) {
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentcet'  method='post' >";
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
        if (isset($_POST["agent"]))
            echo $_POST["agent"];
        echo "' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='";
        if (isset($_POST["agentid"]))
            echo $_POST["agentid"];
        echo "' class='agent' /> ";
        ?>
        <script>
/*    		    	$("#agent").autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
*/
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
    	<?php
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
        echo "<br>";
        echo "<br>";
        echo "<form name='allagentcet'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Tous les agents' >";
        echo "</form>";
        echo "<br>";
        echo "<br>";
    }
    
    $alimid = null;
    if (isset($_POST["alimid"]))
    {
        $alimid = $_POST["alimid"];
    }
    
    $optionid = null;
    if (isset($_POST["optionid"]))
    {
        $optionid = $_POST["optionid"];
    }
    
    if (!is_null($alimid))
    {
        $alimcet = new alimentationCET($dbcon);
        $alimcet->load($alimid);
        $alimcet->storepdf();
        $alimcet = null;
    }
    if (!is_null($optionid))
    {
        $optioncet = new optionCET($dbcon);
        $optioncet->load($optionid);
        $optioncet->storepdf();
        $optioncet = null;
    }
    
    
    echo "Liste des demandes d'alimentation de CET : <br>";
    if (! is_null($agent)) 
    {
        $alimCETliste = $agent->getDemandesAlim('ann' . substr($fonctions->anneeref()-1,2,2));     //getDemandesOption
    }
    else
    {
        $alimCETliste = $fonctions->get_alimCET_liste('ann' . substr($fonctions->anneeref()-1,2,2));
    }
    //var_dump($alimCETliste);
    $htmltext = '';
    foreach ($alimCETliste as $esignatureid)
    {
        if ($htmltext == '')
        {
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Agent</td><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Type de demande</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td><td class='titresimple'>PDF</td>";
            $htmltext = $htmltext . "</tr>";
        }
        
        $alimcet = new alimentationCET($dbcon);
        $alimcet->load($esignatureid);
        if (!is_null($agent))
        {
            $agentalim = $agent;
        }
        else
        {
            $agentalim = new agent($dbcon);
            $agentalim->load($alimcet->agentid());
        }
        
        if (($alimcet->statut() == alimentationCET::STATUT_EN_COURS) or ($alimcet->statut() == alimentationCET::STATUT_PREPARE))
        {
            $statut = $alimcet->statut() . '<br>';
            
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');

            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $alimcet->esignatureid(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $json = curl_exec($curl);
            $error = curl_error ($curl);
            curl_close($curl);
            if ($error != "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
            }
            $response = json_decode($json, true);
            $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
            $statut = $statut . "En attente de : ";
            foreach ((array)$currentstep['recipients'] as $recipient)
            {
                $statut = $statut . "<br>" . $recipient['user']['firstname'] . " " . $recipient['user']['name'];
            }
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentalim->identitecomplete() . "</td><td class='cellulesimple'>" . $alimcet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $statut . "</td><td class='cellulesimple'>" . $fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>";
        }
        else
        {
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentalim->identitecomplete() . "</td><td class='cellulesimple'>" . $alimcet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $alimcet->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>";
        }
        $htmltext = $htmltext . "<td class='cellulesimple'><form name='alim_" . $alimcet->esignatureid() . "'  method='post' >";
        $htmltext = $htmltext . "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='mode' value='" . $mode . "'>";
        if (isset($_POST["agentid"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $_POST["agentid"] . "'>";
        }
        if (isset($_POST["agent"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agent' value='" . $_POST["agent"] . "'>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='alimid' value='" . $alimcet->esignatureid() . "'>";
        $htmltext = $htmltext . "<input type='submit' name='alim_" . $alimcet->esignatureid()  . "' value='Générer le PDF'";
        if ($alimcet->statut() != alimentationCET::STATUT_VALIDE and $alimcet->statut() != alimentationCET::STATUT_REFUSE)
        {
            $htmltext = $htmltext . " disabled='disabled' ";
        }
        $htmltext = $htmltext . ">";
        $htmltext = $htmltext . "</form></td>";
        $htmltext = $htmltext . "</tr>";
    }
    $htmltext = $htmltext . "</table><br>";
    echo $htmltext;
    //var_dump($alimCETliste);
    echo "<br><br>";

    echo "Liste des demandes d'option sur CET : <br>";
    if (! is_null($agent))
    {
        $optionCETliste = $agent->getDemandesOption($fonctions->anneeref());
    }
    else
    {
        $optionCETliste = $fonctions->get_optionCET_liste($fonctions->anneeref());
    }

    $htmltext = '';
    foreach ($optionCETliste as $esignatureid)
    {
        $optioncet = new optionCET($dbcon);
        $optioncet->load($esignatureid);
        if ($htmltext == '')
        {
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Agent</td><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Année de référence</td><td class='titresimple'>RAFP</td><td class='titresimple'>Indemnisation</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td><td class='titresimple'>PDF</td>";
            $htmltext = $htmltext . "</tr>";
        }
        
        if (!is_null($agent))
        {
            $agentoption = $agent;
        }
        else
        {
            $agentoption = new agent($dbcon);
            $agentoption->load($optioncet->agentid());
        }
        
        if (($optioncet->statut() == optionCET::STATUT_EN_COURS) or ($optioncet->statut() == optionCET::STATUT_PREPARE))
        {
            $statut = $optioncet->statut() . '<br>';
            
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
            
            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $optioncet->esignatureid(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $json = curl_exec($curl);
            $error = curl_error ($curl);
            curl_close($curl);
            if ($error != "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
            }
            $response = json_decode($json, true);
            $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
            $statut = $statut . "En attente de : ";
            foreach ((array)$currentstep['recipients'] as $recipient)
            {
                $statut = $statut . "<br>" . $recipient['user']['firstname'] . " " . $recipient['user']['name'];
            }
            
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentoption->identitecomplete() . "</td><td class='cellulesimple'>" . $optioncet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $optioncet->anneeref() . "</td><td class='cellulesimple'>" . $optioncet->valeur_i() . "</td><td class='cellulesimple'>" . $optioncet->valeur_j() . "</td><td class='cellulesimple'>" . $statut . "</td><td class='cellulesimple'>" . $fonctions->formatdate($optioncet->datestatut()) . "</td><td class='cellulesimple'>" . $optioncet->motif() . "</td><td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td>";
        }
        else
        {
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentoption->identitecomplete() . "</td><td class='cellulesimple'>" . $optioncet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $optioncet->anneeref() . "</td><td class='cellulesimple'>" . $optioncet->valeur_i() . "</td><td class='cellulesimple'>" . $optioncet->valeur_j() . "</td><td class='cellulesimple'>" . $optioncet->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($optioncet->datestatut()) . "</td><td class='cellulesimple'>" . $optioncet->motif() . "</td><td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td>";
        }
        $htmltext = $htmltext . "<td class='cellulesimple'><form name='option_" . $optioncet->esignatureid() . "'  method='post' >";
        $htmltext = $htmltext . "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='mode' value='" . $mode . "'>";
        if (isset($_POST["agentid"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $_POST["agentid"] . "'>";
        }
        if (isset($_POST["agent"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agent' value='" . $_POST["agent"] . "'>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='optionid' value='" . $optioncet->esignatureid() . "'>";
        $htmltext = $htmltext . "<input type='submit' name='option_" . $optioncet->esignatureid()  . "' value='Générer le PDF' ";
        if ($optioncet->statut() != optioncet::STATUT_VALIDE and $optioncet->statut() != optionCET::STATUT_REFUSE )
        {
            $htmltext = $htmltext . " disabled='disabled' ";
        }
        $htmltext = $htmltext . ">";
        $htmltext = $htmltext . "</form></td>";
        $htmltext = $htmltext . "</tr>";
    }
    $htmltext = $htmltext . "</table><br>";
    echo $htmltext;
 
    //var_dump($optionCETliste);
    echo "<br><br>";
    
 ?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

