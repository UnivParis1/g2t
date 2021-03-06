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

    // // Initialisation de l'utilisateur
    // $agentid = $_POST["agentid"];
    // $agent = new agent($dbcon);
    // if (is_null($agentid) or $agentid == "")
    // {
    // //echo "L'agent n'est pas passé en paramètre.... Récupération de l'agent à partir du ticket CAS <br>";
    // $LDAP_SERVER=$fonctions->liredbconstante("LDAPSERVER");
    // $LDAP_BIND_LOGIN=$fonctions->liredbconstante("LDAPLOGIN");
    // $LDAP_BIND_PASS=$fonctions->liredbconstante("LDAPPASSWD");
    // $LDAP_SEARCH_BASE=$fonctions->liredbconstante("LDAPSEARCHBASE");
    // $LDAP_CODE_AGENT_ATTR=$fonctions->liredbconstante("LDAPATTRIBUTE");
    // $con_ldap=ldap_connect($LDAP_SERVER);
    // ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    // $r=ldap_bind($con_ldap, $LDAP_BIND_LOGIN,$LDAP_BIND_PASS);
    // $filtre="(uid=$uid)";
    // $dn=$LDAP_SEARCH_BASE;
    // $restriction=array("$LDAP_CODE_AGENT_ATTR");
    // $sr=ldap_search ($con_ldap,$dn,$filtre,$restriction);
    // $info=ldap_get_entries($con_ldap,$sr);
    // //echo "Le numéro HARPEGE de l'utilisateur est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
    // $agent->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]);
    // }
    // else
    // $agent->load($agentid);

    $mode = null;
    if (isset($_POST["mode"]))
       $mode = $_POST["mode"];



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

    if (is_null($agentid) or $agentid == "")
        $noagentset = TRUE;
    else {
        // echo "AGENTID = " . $agentid . "<br>";
        $agent = new agent($dbcon);
        $agent->load($agentid);
        $noagentset = FALSE;
    }
    // echo "avant chargement respo <br>";
    $responsableid = null;
    $noresponsableset = TRUE;
    if (isset($_POST["responsableid"])) {
        // echo "responsableid = " . $responsableid . "<br>";
        $responsableid = $_POST["responsableid"];
        if (! is_null($responsableid) and $responsableid != "") {
            // echo "Je load le responsable...<br>";
            $responsable = new agent($dbcon);
            $responsable->load($responsableid);
            $noresponsableset = FALSE;
            $mode='resp';
        }
    }
    if (isset($_POST["gestionnaireid"])) {
        // echo "gestionnaireid = " . $gestionnaireid . "<br>";
        $responsableid = $_POST["gestionnaireid"];
        if (! is_null($responsableid) and $responsableid != "") {
            // echo "Je load le responsable...<br>";
            $responsable = new agent($dbcon);
            $responsable->load($responsableid);
            $noresponsableset = FALSE;
            $mode='gest';
        }
    }
    
    if (isset($_POST["previous"]))
        $previoustxt = $_POST["previous"];
    else
        $previoustxt = null;
    if (strcasecmp($previoustxt, "yes") == 0)
        $previous = 1;
    else
        $previous = 0;

    // echo "Avant le include <br>";
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml"><br>';

    //echo "POST = "; print_r($_POST); echo "<br>";

    $cancelarray = array();
    if (isset($_POST["cancel"]))
        $cancelarray = $_POST["cancel"];

    foreach ($cancelarray as $demandeid => $value) {
        // echo "demandeid = $demandeid value = $value <br>";
        if (strcasecmp($value, "yes") == 0) {
            $motif = "";
            if (isset($_POST["motif"][$demandeid]))
                $motif = $_POST["motif"][$demandeid];
            // echo "Motif = $motif";
            $demande = new demande($dbcon);
            // echo "cleelement = $cleelement demandeid = $demandeid <br>";
            $demande->load($demandeid);
            $demande->motifrefus($motif);
            if (strcasecmp($demande->statut(), "v") == 0 and $motif == "") {
                $errlog = "Le motif du refus est obligatoire !!!!";
                echo "<p style='color: red'>" . $errlog . "</p><br/>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } else {
                $demande->statut("R");
                $msgerreur = "";
                $msgerreur = $demande->store();
                if ($msgerreur != "") {
                    $errlog = "Pas de sauvegarde car " . $msgerreur;
                    echo "<p style='color: red'>" . $errlog . "</p><br/>";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                } else {
                    unset($demande);
                    $demande = new demande($dbcon);
                    $demande->load($demandeid);
                    if (is_null($responsableid) == false) // Il y a un responsable ==> On envoie le mail
                    {
                        $pdffilename = $demande->pdf($user->harpegeid());
                        $agent = $demande->agent();
                        $ics = null;
                        $ics = $demande->ics($agent->mail());
                        $corpmail = "Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . mb_strtolower($fonctions->demandestatutlibelle($demande->statut()), 'UTF-8') . ".\n\n";
                        // $corpmail = $corpmail . "Pensez à supprimer manuellement l'évènement dans votre agenda.\n";
                        $user->sendmail($agent, "Annulation d'une demande de congés ou d'absence", $corpmail, $pdffilename, $ics);
                    }
                    if (strcasecmp($demande->type(), "cet") == 0) // Si c'est une demande prise sur un CET => On envoie un mail au gestionnaire RH de CET
                    {
                        // Si on n'est pas en mode responsable envoi du mail au gestionnaire RH.... (Sinon c'est l'agent qui a annulé sa propre demande => donc pas d'envoi)
                        if (is_null($responsableid) == false) {
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

                    // echo "<p style='color: green'>Super ca marche la sauvegarde !!!</p><br>";
                    error_log($fonctions->stripAccents("Sauvegarde la demande " . $demande->id() . " avec le statut " . $fonctions->demandestatutlibelle($demande->statut())));
                    echo "<p style='color: green'>Votre demande a bien été annulée!!!</p><br>";
                }
            }
        }
    }

    $debut = $fonctions->formatdate(($fonctions->anneeref() - $previous) . $fonctions->debutperiode());
    // Si on est dans le mode "previous" alors on dit que la date de fin est l'année courante
    if ($previous == 1)
        $fin = $fonctions->formatdate($fonctions->anneeref() . $fonctions->finperiode());
    elseif (strcasecmp($fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0)
        $fin = $fonctions->formatdate(($fonctions->anneeref() + 2) . $fonctions->finperiode());
    else
        $fin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());

    // echo "Debut = $debut fin = $fin <br>";
    // echo "structure->id() = " . $structure->id() . "<br>";

    echo "<form name='frm_gest_demande'  method='post' >";
    if ($noresponsableset and is_null($mode)) {
        // => C'est un agent qui veut gérer ses demandes
        // echo "Pas de responsable.... <br>";
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->harpegeid(), "agent", null);
        if ($htmltext != "")
            echo $htmltext;
        else
            echo "<center>L'agent " . $agent->civilite() . "  " . $agent->nom() . " " . $agent->prenom() . " n'a aucun congé à annuler pour la période de référence en cours.</center><br>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    } elseif ($noagentset) {

        if ($mode == 'resp' or $mode == 'gest')
        {
            // => On est en mode "responsable" mais aucun agent n'est sélectionné
            // echo "Avant le chargement structure responsable <br>";
            if ($mode == 'resp')
            {
                $structureliste = $responsable->structrespliste();
                // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
                $agentlistefull = array();
                foreach ($structureliste as $structure) {
                    $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
                    // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
                    $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                    // echo "fin du select <br>";
                    $structfille = $structure->structurefille();
                    if (! is_null($structfille)) {
                        foreach ($structfille as $fille) {
                            if ($fonctions->formatdatedb($fille->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {
                                $agentliste = null;
                                $respfille = $fille->responsable();
                                $agentliste[$respfille->nom() . " " . $respfille->prenom() . " " . $respfille->harpegeid()] = $respfille;
                                $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                            }
                        }
                    }
                }
            }
            else // $mode == gest
            {
                $structureliste = $responsable->structgestliste();
                $agentlistefull = array();
                foreach ($structureliste as $structure) 
                {
                    $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"),'n');
                    // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
                    $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                }
            }
            ksort($agentlistefull);
            //echo "<br>"; print_r($agentlistefull); echo "<br>";
            if (isset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->harpegeid()])) {
                unset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->harpegeid()]);
            }
            echo "<SELECT name='agentid'>";
            foreach ($agentlistefull as $keyagent => $membre) {
                echo "<OPTION value='" . $membre->harpegeid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
            }
            echo "</SELECT>";
            echo "<br>";
        }
        else // $mode = 'rh'
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
    } elseif ($mode == 'resp' or $mode == 'gest') {
        // => On est en mode "reponsable" et un agent est sélectionné
        //echo "Avant le mode responsable <br>";
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->harpegeid(), "resp", null);
        if ($htmltext != "")
            echo $htmltext;
        else
            echo "<center>L'agent " . $agent->civilite() . "  " . $agent->nom() . " " . $agent->prenom() . " n'a aucun congé à annuler pour la période de référence en cours.</center><br>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    }
    else
    {
        // On est en mode rh et un agent est sélectionné
        // On élargie de période de début de recherche des demades de CET pour l'agent à -2 ans.
        //echo "Mode RH <br>";
        $debut = $fonctions->formatdate(($fonctions->anneeref() - 2) . $fonctions->debutperiode());
        $htmltext = $agent->demandeslistehtmlpourgestion($debut, $fin, $user->harpegeid(), "resp", 'cet');
        if ($htmltext != "")
            echo $htmltext;
        else
            echo "<center>L'agent " . $agent->civilite() . "  " . $agent->nom() . " " . $agent->prenom() . " n'a aucune demande de congés sur CET à annuler pour la période de référence en cours.</center><br>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";

    }

    if ($responsableid != "")
    {
        if ($mode == 'resp')
            echo "<input type='hidden' name='responsableid' value='" . $responsableid . "'>";
        elseif ($mode == 'gest')
            echo "<input type='hidden' name='gestionnaireid' value='" . $responsableid . "'>";
    }
    echo "<input type='hidden' name='userid' value='" . $userid . "'>";
    echo "<input type='hidden' name='previous' value='" . $previoustxt . "'>";
    echo "<input type='hidden' name='mode' value='" . $mode . "'>";
    echo "<input type='submit' value='Soumettre' />";

    echo "</form>"

?>

<br>
<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

