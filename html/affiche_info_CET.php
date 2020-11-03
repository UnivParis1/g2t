<?php
    require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./class/agent.php");
    require_once ("./class/structure.php");
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("./class/tcpdf/tcpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
    
    
    echo "<html>";
    echo "<head>";
?>
<link rel="stylesheet" type="text/css"
	href="style/style.css?<?php echo filemtime('style/style.css')  ?>"
	media="screen"></link>
<?php 
    echo "</head>";
    echo "<body style='margin: 0 ; overflow: hidden'>";
    //echo "<p style='font-family: Verdana; font-size: 8pt; text-align: left; color: #616162; background: inherit; border-width: 0;'>";
    echo "<p class='siham_css'>";
    
    
    
    //echo "L'agent connecté est : " . $uid . "<br>";
    
    
    $user = new agent($dbcon);
    $userid = null;
    
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
    if (! $user->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '</head>';
        $errlog = "L'utilisateur " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " (Informations LDAP : " . $info[0]["dn"] . ") n'est pas référencé dans la base de donnée !!!";
        echo "$errlog<br>";
        echo "<br><font color=#FF0000>Vous n'êtes pas autorisé à vous connecter à cette application...</font>";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        exit();
    }
    $_SESSION['phpCAS']['harpegeid'] = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
    $_SESSION['phpCAS']['dn'] = $info[0]["dn"];
    // echo "Je viens de set le param - index.php<br>";
    // echo "Avant le recup user-> id";
    $userid = $user->harpegeid();
    // echo "Apres le recup user-> id";
    
//    $userid = "7546";
    
    
    //echo "Le numéro SIHAM de l'agent connecté est : " . $userid . "<br>";
    
    $solde = new solde($dbcon);
    if ($solde->load($userid, 'cet') <> "")
    {
        echo "Vous n'avez pas de CET";
    }
    elseif (($solde->droitaquis() - $solde->droitpris()) > 1)
    {
        echo ($solde->droitaquis() - $solde->droitpris())  . " jours";
        // echo "Nombre de jours restant dans votre CET : " . ($solde->droitaquis() - $solde->droitpris())  . " jours.";
    }
    else
    {
        echo ($solde->droitaquis() - $solde->droitpris())  . " jour";
        //echo "Nombre de jours restant dans votre CET : " . ($solde->droitaquis() - $solde->droitpris())  . " jour.";
    }
    
    echo "</p>";
?>
</body>
</html>