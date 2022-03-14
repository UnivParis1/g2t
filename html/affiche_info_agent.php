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

    $anneeref = $fonctions->anneeref();
    if (isset($_POST["annee_ref"]))
    {
        $anneeref = $_POST["annee_ref"];
    }

    $msg_erreur = "";

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    //print_r($_POST); echo "<br>";


    echo "Personne à rechercher : <br>";
    echo "<form name='selectagent'  method='post' >";
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
/*
    	$("#agent").autocompleteUser(
  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
  	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
*/
        $("#agent").autocompleteUser(
           '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
        	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
    </script>
    <?php
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br><br>";
    echo "Période d'affichage : ";
    echo "<select name='annee_ref' id='annee_ref'>";
    for ($annee=$fonctions->anneeref();$annee>=$fonctions->anneeref()-3;$annee--)
    {
        echo "<option value='$annee'";
        if ($annee==$anneeref)
            echo " selected ";
        echo ">Année " . $annee . "/" . ($annee+1) . "</option>";
    }
    echo "</select>";
    echo "<br><br>";
    echo "<input type='submit' value='Soumettre' >";
    echo "</form>";

    if (!is_null($agent)) {
        echo "<br><br>Informations sur les congés de " . $agent->identitecomplete() . "<br>";
        echo $agent->soldecongeshtml($anneeref);
        echo $agent->demandeslistehtml($fonctions->formatdate($anneeref . $fonctions->debutperiode()), $fonctions->formatdate(($anneeref + 1) . $fonctions->finperiode()));
    }
    ?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>