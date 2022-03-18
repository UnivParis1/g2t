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
    
    $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
    $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
    $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
    $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
    $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
    $con_ldap = ldap_connect($LDAP_SERVER);
    ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
    $LDAP_UID_AGENT_ATTR = $fonctions->liredbconstante("LDAP_AGENT_UID_ATTR");
    $filtre = "($LDAP_UID_AGENT_ATTR=$uid)";
    $dn = $LDAP_SEARCH_BASE;
    $restriction = array(
        "$LDAP_CODE_AGENT_ATTR"
    );
    $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
    $info = ldap_get_entries($con_ldap, $sr);
    // echo "Le numéro AGENT de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
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
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "") {
            $errlog = "Erreur activation/desactivation mode maintenance : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
    }

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';

    // echo "POST = "; print_r($_POST); echo "<br>";
    $etat = $fonctions->liredbconstante("MAINTENANCE");
    

?>

<br>
Gestion du mode maintenance...
<br>
<br>
<form name='maintenance_mode' method='post'>
    <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
    <INPUT type="radio" name="maintenance" value="on" <?php if (strcasecmp($etat,'n')==0) echo 'checked ' ?>> Activer le mode maintenance <br> 
    <INPUT type="radio" name="maintenance" value="off" <?php if (strcasecmp($etat,'o')==0) echo 'checked ' ?>> Désactiver le mode maintenance <br> 
    <br> 
    <input type='submit' value='Soumettre'>
</form>

</body>
</html>
