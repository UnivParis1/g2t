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
    // echo "Le numÃ©ro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
    $adminuser = new agent($dbcon);
    $adminuser->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
    if (! $adminuser->estadministrateur()) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        header('Location: index.php');
        exit();
    }
    
    $user = new agent($dbcon);
    $user->load($userid);
    
    if (isset($_POST["maintenance"])) {
        if ($_POST["maintenance"] == "on") {
            // Update Mode maintenance à 'o'
            $sql = "UPDATE CONSTANTES SET VALEUR = 'o' WHERE NOM = 'MAINTENANCE'";
        } else {
            // Update Mode maintenance à 'n'
            $sql = "UPDATE CONSTANTES SET VALEUR = 'n' WHERE NOM = 'MAINTENANCE'";
        }
        $query = mysql_query($sql);
        $erreur = mysql_error();
        if ($erreur != "") {
            $errlog = "Erreur activation/desactivation mode maintenance : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    
    // echo "POST = "; print_r($_POST); echo "<br>";

?>

<br>
Gestion du mode maintenance...
<br>
<br>
<form name='maintenance_mode' method='post'>
<?php echo "<input type='hidden' name='userid' value='" . $user->harpegeid() ."'>";  ?>
<INPUT type="radio" name="maintenance" value="on"> Activer le mode
	maintenance <br> <INPUT type="radio" name="maintenance" value="off">
	Désactiver le mode maintenance <br> <br> <input type='submit'
		value='Soumettre'>
</form>

</body>
</html>
