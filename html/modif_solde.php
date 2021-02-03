<?php
require_once ('CAS.php');
include './includes/casconnection.php';

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
        require_once ("./class/fpdf/fpdf.php");
        require_once ("./class/cet.php");
        require_once ("./class/affectation.php");
        require_once ("./class/complement.php");

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
            } else {
                $agent = new agent($dbcon);
                $agent->load($agentid);
            }
        } else {
            $agentid = null;
            $agent = null;
        }

        $anneeref = $fonctions->anneeref()-1;
        if (isset($_POST["annee_ref"]))
        {
            $anneeref = $_POST["annee_ref"];
        }


        $msg_erreur = "";
        if (isset($_POST["solde"]))
        {
            $newsolde=$_POST["solde"];
            $newsolde = str_replace(",", ".", $newsolde);
            //echo "int => " . intval($newsolde * 2) . "   et l'autre => " . ($newsolde * 2) . "<br>";
            if (! is_numeric($newsolde))
            {
                $msg_erreur = "Vous n'avez pas saisi une valeur nunérique correcte.<br>";
                $newsolde = "";
            }
            elseif (intval($newsolde * 2) <> ($newsolde * 2)) // On vérifie que newsolde est soit un entier soit un multiple de 1/2 journée
            {
                $msg_erreur = "Vous ne pouvez saisir qu'un nombre entier ou un multiple de 1/2 journée.<br>";
                $newsolde = "";
            }
            elseif ($newsolde == "" or $newsolde < 0) {
                $msg_erreur = "Vous n'avez pas saisi de solde ou il est négatif.<br>";
            }
            else
            {
                $typeconges = "ann" . substr($anneeref,-2,2);
                $solde = new solde($dbcon);
                $msg_erreur = $solde->load($agent->harpegeid(),$typeconges);
                if ($msg_erreur=="")
                {
                    $anciensolde = $solde->solde();
                    if (floatval($anciensolde) <> floatval($newsolde))
                    {
                        $pris=$solde->droitpris();
                        $solde->droitaquis($pris + $newsolde);
                        $solde->store();

                        $agent->ajoutecommentaireconge($typeconges, $solde->solde()-$anciensolde, "Modification du solde par " . $user->identitecomplete() . " (Ancien solde = $anciensolde / Nouveau solde = " . $solde->solde() .")");
                    }
                }
            }
        }

        require ("includes/menu.php");
        // echo '<html><body class="bodyhtml">';
        echo "<br>";
        echo "<P style='color: red'>" . $msg_erreur . "</P>";

        //print_r($_POST); echo "<br>";

        $msg_erreur = "";
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
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<br><br>";
    echo "Période d'affichage : ";
    echo "<select name='annee_ref' id='annee_ref'>";
    for ($annee=$fonctions->anneeref()-1;$annee>=$fonctions->anneeref()-3;$annee--)
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
        echo "<br><br>";
        echo "<span style='border:solid 1px black; background:lightgreen; width:600px; display:block;'>";
        //echo "Informations sur les congés de " . $agent->identitecomplete() . "<br>";
        $solde = new solde($dbcon);
        $msg_erreur = $solde->load($agent->harpegeid(),"ann" . substr($anneeref,-2,2));
        if ($msg_erreur <> "")
        {
            echo $msg_erreur;
        }
        else
        {
            echo "<form name='submit_solde'  method='post' >";
            echo "<input type='hidden' id='agent' name='agent' value='" . $_POST["agent"] . "' class='agent' /> ";
            echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->harpegeid() . "' class='agent' /> ";
            echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
            echo "<input type='hidden' name='annee_ref' value='" . $_POST["annee_ref"] . "'>";
            echo "Le solde " . $solde->typelibelle() . " de l'agent " . $agent->identitecomplete() . " est de " . $solde->solde() . " jour(s)";
            echo "<br><br>";
            echo "Veuillez sasir le nouveau solde pour " . $solde->typelibelle() . " : ";
            echo "<input type='text' name='solde' value='" . $solde->solde() .  "'>";
            echo "<br><br>";
            echo "<input type='submit' value='Soumettre' >";
            echo "</form>";
        }
        echo "</span>";
    }
    ?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>