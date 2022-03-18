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
    
    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        $agent = new agent($dbcon);
        $agent->load($agentid);
    } else {
        $agentid = null;
        $agent = null;
    }

    $nbr_jours_conges = null;
    $commentaire_supp = null;
    if (isset($_POST["nbr_jours_conges"]))
        $nbr_jours_conges = $_POST["nbr_jours_conges"];
    if (isset($_POST["commentaire_supp"]))
        $commentaire_supp = $_POST["commentaire_supp"];

    $msg_erreur = "";

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    if ($agentid == "") {
        echo "<form name='selectagentcongessupp'  method='post' >";

        $structureliste = $user->structrespliste();
        // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
        $agentlistefull = array();
        foreach ($structureliste as $structure) {
            $agentliste = $structure->agentlist(date("d/m/Y"), date("d/m/Y"));
            // echo "Liste de agents = "; print_r($agentliste); echo "<br>";
            $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
            // echo "fin du select <br>";
            $structurefille = $structure->structurefille();
            foreach ((array) $structurefille as $structure) {
                $responsable = $structure->responsable();
                if ($responsable->agentid() != '-1') {
                    $agentlistefull[$responsable->nom() . " " . $responsable->prenom() . " " . $responsable->agentid()] = $responsable;
                }
            }
        }
        if (isset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->agentid()])) {
            unset($agentlistefull[$user->nom() . " " . $user->prenom() . " " . $user->agentid()]);
        }
        ksort($agentlistefull);
        echo "<SELECT name='agentid'>";
        foreach ($agentlistefull as $keyagent => $membre) {
            echo "<OPTION value='" . $membre->agentid() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
        }
        echo "</SELECT>";
        echo "<br>";

        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    } else {
        if (! is_null($nbr_jours_conges)) {
            // On a cliqué sur le bouton validé ==> On va vérifier la saisie
            $nbr_jours_conges = str_replace(",", ".", $nbr_jours_conges);
            if (! is_numeric($nbr_jours_conges))
                $nbr_jours_conges = 0;
            // echo "nbr_jours_conges = $nbr_jours_conges <br>";
            if ($nbr_jours_conges == "" or $nbr_jours_conges <= 0) {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi le nombre de jours à ajouter ou il est inférieur ou égal à 0 ou ce n'est pas une valeur nunérique.<br>";
            }
            if ($commentaire_supp == "") {
                $msg_erreur = $msg_erreur . "Vous n'avez pas saisi de commentaire. Celui-ci est obligatoire <br>";
            }
            if ($msg_erreur == "") {
                $solde = new solde($dbcon);
                $annee = substr($fonctions->anneeref(), 2, 2);
                $lib_sup = "sup$annee";
                // echo "lib_sup = $lib_sup <br>";
                $erreur = $solde->load($agentid, $lib_sup);
                // echo "Erreur = $erreur <br>";
                if ($erreur != "") {
                    unset($solde);
                    $solde = new solde($dbcon);
                    $msg_erreur = $msg_erreur . $solde->creersolde($lib_sup, $agentid);
                    // echo "msg_erreur = $msg_erreur <br>";
                    $msg_erreur = $msg_erreur . $solde->load($agentid, $lib_sup);
                    // echo "msg_erreur = $msg_erreur <br>";
                }
                $commentaire_supp_complet = $commentaire_supp . " (par " . $user->prenom() . " " . $user->nom() . ")";
                $nouv_solde = ($solde->droitaquis() + $nbr_jours_conges);
                $solde->droitaquis($nouv_solde);
                $msg_erreur = $msg_erreur . $solde->store();
                $msg_erreur = $msg_erreur . $agent->ajoutecommentaireconge($lib_sup, $nbr_jours_conges, $commentaire_supp_complet);
                // echo "msg_erreur = $msg_erreur <br>";
            }
            if ($msg_erreur != "") {
                $errlog = "Les jours supplémentaires n'ont pas été enregistrés... ==> MOTIF : " . $msg_erreur;
                echo "<P style='color: red'>" . $errlog . "</P>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            } elseif (! is_null($solde)) {
                $errlog = "Les jours supplémentaires ont été enregistrés... Nouveau solde = " . ($solde->droitaquis() - $solde->droitpris());
                echo "<P style='color: green'>" . $errlog . "</P>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                $agentrhlist = $fonctions->listeprofilrh("2"); // Le profil 2 est le profil de gestion des congés
                foreach ($agentrhlist as $agentrh) {
                    $corpmail = $user->identitecomplete() . " vient d'ajouter $nbr_jours_conges jour(s) complémentaire(s) à " . $agent->identitecomplete() . ".\n";
                    $corpmail = $corpmail . "Le motif de cet ajout est : " . $commentaire_supp . ".\n";
                    $corpmail = $corpmail . "Le solde de jours complémentaires est maintenant de : " . ($solde->droitaquis() - $solde->droitpris()) . " jour(s).\n";
                    $user->sendmail($agentrh, "Ajout de jours complémentaires pour " . $agent->identitecomplete(), $corpmail);
                }
            }
        } else {
            // On est au premier affichage de l'écran apres la selection de l'agent ==> Pas de control de saisi
            $errlog = "Le motif de l'ajout est obligatoire";
            echo "<P style='color: red'>" . $errlog . "</P><br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }

        echo "Ajout de jours de congés supplémentaires pour l'agent : " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . "<br>";
        echo "<form name='frm_ajoutconge'  method='post' >";
        // echo "Sélectionnez l'agent auquel vous voullez ajouter des jours supplémentaires : ";
        // $agentliste=$user->structure()->agentlist();
        // echo "<SELECT name='agentid'>";
        // foreach ($agentliste as $keyagent => $membre)
        // {
        // echo "<OPTION value='" . $membre->id() . "'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</OPTION>";
        // }
        // echo "</SELECT>";

        // echo "<br>";

        echo "Nombre de jours supplémentaires à ajouter : <input type=text name=nbr_jours_conges id=nbr_jours_conges size=3 >";
        echo "<br>";
        echo "Motif (Obligatoire) : <input type=text name=commentaire_supp id=commentaire_supp size=25 >";
        echo "<br>";

        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    }

?>

<!--
<a href=".">Retour à la page d'accueil</a>
 -->
</body>
</html>

