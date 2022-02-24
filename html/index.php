<?php
    require_once ('CAS.php');
    include './includes/casconnection.php';

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
*/
    
    // Initialisation de l'utilisateur
    $userid = null;
    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    $user = new agent($dbcon);

    if (is_null($userid) or $userid == "") {
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
        // echo "Le numéro AGENT de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
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
        $_SESSION['phpCAS']['agentid'] = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
        $_SESSION['phpCAS']['dn'] = $info[0]["dn"];
        // echo "Je viens de set le param - index.php<br>";
        // echo "Avant le recup user-> id";
        $userid = $user->agentid();
        // echo "Apres le recup user-> id";
    } else {
        if (! $user->load($userid)) {
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '</head>';
            $errlog = "L'utilisateur " . $userid . " (Informations LDAP : " . $_SESSION['phpCAS']['dn'] . ") n'est pas référencé dans la base de donnée !!!";
            echo "$errlog<br>";
            echo "<br><font color=#FF0000>Vous n'êtes pas autorisé à vous connecter à cette application...</font>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            exit();
        }
    }

    require ("includes/menu.php");
    $casversion = phpCAS::getVersion();
    $errlog = "Index.php => Version de CAS.php utilisée  : " . $casversion;
    //echo "<br><br>" . $errlog . "<br><br>";
    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));

    // echo '<html><body class="bodyhtml">';

    // echo "Date du jour = " . date("d/m/Y") . "<br>";
    $affectationliste = $user->affectationliste(date("d/m/Y"), date("d/m/Y"));

    echo "<br>Bonjour " . $user->identitecomplete() . " : <br>";
    if (! is_null($affectationliste)) {
        $affectation = reset($affectationliste);
        // $affectation = $affectationliste[0];
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());
        echo $structure->nomlong();
    } else
        echo "Pas d'affectation actuellement => Pas de structure";

    // $tempstructid = $user->structure()->id();
    echo "<br><br>";


    $affectationliste = $user->affectationliste($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()));

    // L'agent a-t-il des affectation ?
    if (count((array)$affectationliste) >0)
    {
        // Pour chaque affectation
        foreach ((array) $affectationliste as $affectation)
        {
            // Si c'est un temps partiel, on verifie que le temps partiel est bien saisi et validé
            if ($affectation->quotitevaleur() < 1)
            {
                $datedebut = "29991231";  // La date de début est dans le futur
                $datefin = "19000101";    // La date de fin est dans le passé
                if ($fonctions->formatdatedb($affectation->datedebut()) < $datedebut)
                    $datedebut = $fonctions->formatdatedb($affectation->datedebut());
                if ($datefin < $fonctions->formatdatedb($affectation->datefin()))
                    $datefin = $fonctions->formatdatedb($affectation->datefin());
                if ($datedebut < $fonctions->anneeref() . $fonctions->debutperiode())
                    $datedebut = $fonctions->anneeref() . $fonctions->debutperiode();
                if ($datefin > ($fonctions->anneeref()+1) . $fonctions->finperiode())
                    $datefin = ($fonctions->anneeref()+1) . $fonctions->finperiode();

                //echo "datedebut = $datedebut    datefin = $datefin <br>";
                // On verifie que sur l'affectation en cours, il n'y a pas de période non déclaré.
                if (!$user->dossiercomplet($datedebut,$datefin))
                {
                    echo "<font color=#FF0000>";
                    echo "<b>ATTENTION : </b>Il existe au moins une affection à temps partiel pour laquelle vous n'avez pas de déclaration validée.<br>";
                    echo "Vos déclarations de temps partiel doivent obligatoirement être validées afin de pouvoir poser des congés durant la  période correspondante.<br>";
                    echo "Votre planning contiendra donc des cases \"Période non déclarée\" lors de son affichage.<br>";
                    echo "</font>";
                }
            }
        }
    }
    /*
     * $structure = new structure($dbcon);
     * $structure->load("DGH");
     * $structure->sousstructure("o");
     * echo "Liste des agents de la structure " . $structure->nomlong() . " : <br>";
     * if (!is_null($structure))
     * {
     * $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
     * $agent = new agent($dbcon);
     * foreach ($agentliste as $key => $agent)
     * {
     * $affectationliste = $agent->affectationliste(date("d/m/Y"),date("d/m/Y"));
     * $affectation = reset($affectationliste);
     * //$affectation = $affectationliste[0];
     * unset($structure);
     * $structure = new structure($dbcon);
     * $structure->load($affectation->structureid());
     * echo "L'agent " . $agent->identitecomplete() . " est dans la strcuture " . $structure->nomlong() . "<br>";
     * }
     * }
     */
    /*
     * $structure = new structure($dbcon);
     * $structure->load("DGHC");
     * $structure->sousstructure("o");
     * echo "<br>Planning de la structure " . $structure->nomlong() . " :<br>";
     * echo $structure->planninghtml("03/2013");
     */

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
    echo $user->soldecongeshtml($fonctions->anneeref());

    echo $user->affichecommentairecongehtml();
    echo $user->demandeslistehtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()), $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode()));
        
?>
</body>
</html>
