<?php
    ini_set('max_execution_time', '1200'); //300 seconds = 5 minutes  1200 seconds = 20 minutes
    header('X-Accel-Buffering: no'); // pour nginx
    header("Content-Type: text/html");
    
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
    require_once ('./includes/dbconnection.php');
    require_once ('./class/fonctions.php');
    require_once ('./class/agent.php');
    require_once ('./class/structure.php');
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
    require_once ("./class/periodeobligatoire.php");
    require_once ("./class/alimentationCET.php");
    require_once ("./class/optionCET.php");
*/
    
    $user = new agent($dbcon);
    $user->load($userid);

/*
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
        }
    }
    else
    {
        $agentid = null;
    }
*/
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";
    
    
    //$sql = "SELECT DISTINCT SOLDE.AGENTID, NOM, PRENOM FROM SOLDE, AGENT WHERE TYPEABSENCEID IN ('ann20', 'ann21') AND SOLDE.AGENTID = AGENT.AGENTID";
    //$sql = "SELECT DISTINCT SOLDE.AGENTID, NOM, PRENOM FROM SOLDE, AGENT WHERE TYPEABSENCEID IN ('ann20', 'ann21') AND SOLDE.AGENTID = AGENT.AGENTID AND AGENT.AGENTID IN ('9328','3715','19803', '24606', '13825','90223')";

    $sql = "SELECT DISTINCT SUB1.AGENTID, NOM, PRENOM
            FROM AGENT,((SELECT AGENTID 
                           FROM SOLDE S1 
                           WHERE S1.TYPEABSENCEID = 'ann20'
                             AND S1.DROITAQUIS <> 0
                        )
                        UNION
                        (SELECT AGENTID 
                           FROM SOLDE S2
                           WHERE S2.TYPEABSENCEID = 'ann21'
                             AND S2.DROITAQUIS <> 0
                        )) SUB1
            WHERE SUB1.AGENTID = AGENT.AGENTID
              -- AND NOM BETWEEN 'A' AND 'G'
            ORDER BY NOM, PRENOM";
    
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Affiche_Info_conges : Erreur dans la lecture en base => " . $erreur;
        echo "$errlog \n";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    ob_start();
    if (($numligne = mysqli_num_rows($query)) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Affiche_Info_conges : Aucune ligne dans la base de données correspondante";
        echo "$errlog \n";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    else
    {
        echo "Il va y avoir $numligne ligne(s) dans le tableau <br>";
    }

    $count = 0;
    echo "<table class='tableausimple' >";
    echo "<tr><td class='titresimple'>Matricule</td><td class='titresimple'>Identité agent</td><td class='titresimple'>Droit 2020/2021</td><td class='titresimple'>Solde 2020/2021 au 31/08/2021</td><td class='titresimple'>Congés 2020/2021 entre le 01/09 et le 31/12</td><td class='titresimple'>Solde 2021/2022</td><td class='titresimple'>Droit 2021/2022 pris</td></tr>";
    ob_flush();
    flush();
    while ($result = mysqli_fetch_row($query)) 
    {
        $count ++;
        $agent = new agent($dbcon);
        $agent->load($result[0]);
        //echo "Identité = " . $agent->identitecomplete() ." <br>";
        $solde2020 = new solde($dbcon); 
        $solde2021 = new solde($dbcon);
        $error = $solde2020->load($agent->agentid(),'ann20');
        //echo "error = XXXX" . $error . "XXXX <br>";
        if ($error != "")
        {
            $solde2020->droitaquis(0);
        }
        $error = $solde2021->load($agent->agentid(),'ann21');
        //echo "error = YYYY" . $error . "YYYY <br>";
        if ( $error != "")
        {
            $solde2021->droitaquis(0);
        }
        
        //echo "Aquis 2020 = " . $solde2020->droitaquis() . "   Aquis 2021 = " . $solde2021->droitaquis() . "<br>";
        if (floatval($solde2020->droitaquis()) == 0 and floatval($solde2021->droitaquis()) == 0)
        {
            continue;
        }
        
        echo "<tr class='element'>";
        echo "<td class='cellulesimple'>UP1" . str_pad($agent->agentid(),9,'0', STR_PAD_LEFT) . "</td><td class='cellulesimple'>" . $agent->identitecomplete() . "</td><td class='cellulesimple'>" . $solde2020->droitaquis()  ."</td>"; 
        $nbjrsconsommes = $agent->getNbJoursConsommés('2020', '20190901', '20210831');
//        echo "nbjrsconsommes 2020 = $nbjrsconsommes <br>";
        echo "<td class='cellulesimple'>" . ($solde2020->droitaquis() - $nbjrsconsommes) . "</td>";
        $nbjrsconsommes = $agent->getNbJoursConsommés('2020', '20210901', '20211231');
//        echo "nbjrsconsommes 2020 (post 01/09) = $nbjrsconsommes <br>";
        echo "<td class='cellulesimple'>" . $nbjrsconsommes . "</td>";
        echo "<td class='cellulesimple'>" . $solde2021->droitaquis() . "</td>";
        $nbjrsconsommes = $agent->getNbJoursConsommés('2021', '20210101', '20211231');
//        echo "nbjrsconsommes 2021 = $nbjrsconsommes <br>";
        echo "<td class='cellulesimple'>" . $nbjrsconsommes . "</td>";
        echo "</tr>";
        unset($solde2020);
        unset($solde2021);
        unset($agent);
        ob_flush();
        flush();
    }
    echo "</table>";
    echo "<br>";
    ob_flush();
    flush();
    ob_end_flush();
    
    
?>

</body>
</html>

