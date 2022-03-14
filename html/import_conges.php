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
    // echo "Le numÃ©ro AGENT de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
    $adminuser = new agent($dbcon);
    $adminuser->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
    if (! $adminuser->estadministrateur()) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    $liste_conges = "";
    if (isset($_POST["conge_liste"]))
        $liste_conges = $_POST["conge_liste"];

    require ("includes/menu.php");

    if ($liste_conges != "") {
        // echo "Liste conges = " . htmlentities($liste_conges) . "<br>";
        $tabligne = explode("\n", $liste_conges);
        foreach ($tabligne as $index => $ligne) {
            $ligne = trim($ligne);
            if ($ligne != "") {
                $element = "";
                $element = explode(";", $ligne);
                if (count($element) == 7) {
                    $msg_erreur = "";
                    unset($agent);
                    $agent = new agent($dbcon);
                    if ($agent->load($element[0]) == false)
                        $msg_erreur = $msg_erreur . "L'agent n'existe pas. ";
                    $listetype = $element[1];
                    if ($fonctions->estunconge($listetype) == false)
                        $msg_erreur = $msg_erreur . "$listetype n'est pas un type de congé valide. ";
                    $date_debut = $fonctions->formatdate($element[2]);
                    if ($fonctions->verifiedate($date_debut) == false)
                        $msg_erreur = $msg_erreur . "$date_debut n'est pas une date valide (début). ";
                    $deb_mataprem = $element[3];
                    if ($fonctions->nommoment($deb_mataprem) == "")
                        $msg_erreur = $msg_erreur . "$deb_mataprem n'est pas un moment valide (début). ";
                    $date_fin = $fonctions->formatdate($element[4]);
                    if ($fonctions->verifiedate($date_fin) == false)
                        $msg_erreur = $msg_erreur . "$date_fin n'est pas une date valide (fin). ";
                    $fin_mataprem = $element[5];
                    if ($fonctions->nommoment($deb_mataprem) == "")
                        $msg_erreur = $msg_erreur . "$fin_mataprem n'est pas un moment valide (fin). ";
                    $statut = $element[6];
                    //if ((strcasecmp($statut, 'v') != 0) and (strcmp($statut, 'r') != 0) and (strcmp($statut, 'R') != 0) and (strcasecmp($statut, 'a') != 0))
                    if ((strcasecmp($statut, demande::DEMANDE_VALIDE) != 0) and (strcmp($statut, demande::DEMANDE_ANNULE) != 0) and (strcmp($statut, demande::DEMANDE_REFUSE) != 0) and (strcasecmp($statut, demande::DEMANDE_ATTENTE) != 0))
                        $msg_erreur = $msg_erreur . "$statut n'est pas un statut valide. ";

                    if ($msg_erreur == "") {
                        echo "Demande de congés pour " . $agent->identitecomplete() . " du " . $date_debut . " au " . $date_fin . " : ";
                        // On recherche les declarations de TP relatives à cette demande
                        $affectationliste = $agent->affectationliste($date_debut, $date_fin, true);
                        //echo "affectationliste = "; print_r($affectationliste); echo "<br>";
                        $declarationTPliste = array();
                        if (! is_null($affectationliste)) {
                            foreach ((array)$affectationliste as $affectation) {
                                // On recupère la première affectation
                                // $affectation = new affectation($dbcon);
                                // $affectation = reset($affectationliste);
                                // echo "Datedebut = $date_debut, Date fin = $date_fin <br>";
                                //echo "Import_Congés : On va rechercher les declarationTPliste liées à l'affectation <br>";
                                $declarationTPliste = array_merge((array) $declarationTPliste, (array) $affectation->declarationTPliste($date_debut, $date_fin));
                            }
                            //echo "declarationTPliste = "; print_r($declarationTPliste); echo "<br>";
                        }

                        // echo "Je vais sauver la demande <br>";
                        unset($demande);
                        $demande = new demande($dbcon);
                        // $demande->agent($agent->agentid());
                        // $demande->structure($agent->structure()->id());
                        $demande->type($listetype);
                        $demande->datedebut($date_debut);
                        $demande->datefin($date_fin);
                        $demande->moment_debut($deb_mataprem);
                        $demande->moment_fin($fin_mataprem);
                        $demande->commentaire("Ajout manuel de la demande (par " . $user->identitecomplete() . ")");
                        $ignoreabsenceautodecla = true;
                        $ignoresoldeinsuffisant = false;
                        // echo "demande->nbredemijrs_demande() AVANT = " . $demande->nbredemijrs_demande() . "<br>";
                        //echo "Import_Congés : Avant le store de la demande....<br>";
                        $resultat = $demande->store($declarationTPliste, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                        //echo "Import_Congés : Apres le store de la demande....<br>";
                        
                        // echo "demande->nbredemijrs_demande() APRES = " . $demande->nbredemijrs_demande() . "<br>";
                        if ($resultat == "") {
                            $demandeid = $demande->id();
                            unset($demande);
                            $demande = new demande($dbcon);
                            $demande->load($demandeid);
                            $demande->statut($statut);
                            $resultat = $demande->store();
                            if ($resultat == "")
                                echo "<font color='green'>Ok</font><br>";
                            else
                                echo "<font color='red'>Echec (Chgmt statut) => $resultat</font><br>";
                        } else {
                            echo "<font color='red'>Echec (ligne " . ($index + 1) . ") => $resultat</font><br>";
                        }
                    } else {
                        echo "<font color='red'>Erreur(s) détectée(s) dans la ligne " . ($index + 1) . " : $msg_erreur </font><br>";
                    }
                } else {
                    echo "<font color='red'>Mauvaise structure dans la ligne " . ($index + 1) . " </font><br>";
                }
            }
        }
        echo "<br>";
    }

    ?>

    <br>
    Listez dans la zone ci-dessous les demandes à ajouter :
    <br>
    Format des demandes :
    <br>
    AGENTID;CODETYPE_CONGE;DATE_DEBUT;MOMENT_DEBUT;DATE_FIN;MOMENT_FIN;STATUT
    <br>
    Exemple :
    <br>
    1234;ann13;10/11/2013;m;20/11/2013;a;v
    <br>
    <br>
    <form name='postconge_liste' method='post'>

    	<textarea name="conge_liste" cols="60" rows="20"><?php echo $liste_conges ?></textarea>
    <?php
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
?>
<input type='submit' value='Soumettre'>
</form>

<br>


</body>
</html>


