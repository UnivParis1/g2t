<?php
    // require_once ('CAS.php');
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
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");

    echo "<br>Liste des récupérations posées durant les périodes obligatoires<br><br>";

    $anneeref = $fonctions->anneeref();
    $periodeobligatoire = new periodeobligatoire($dbcon);

    $periodeliste = $periodeobligatoire->load($anneeref);

    $demandeliste = $fonctions->demandelistepartypeabsence(recuperation::SUPP_ID . substr($anneeref,2,2), $anneeref);
    $demandeliste = array_merge($demandeliste, $fonctions->demandelistepartypeabsence(recuperation::RECUP_ID, $anneeref));

    $demandeparagent = array();
    foreach ($demandeliste as $demande)
    {
        if ($demande->statut() != demande::DEMANDE_ANNULE and $demande->statut() != demande::DEMANDE_REFUSE)
        {
            //var_dump($demande);
            $periode = $periodeobligatoire->testsuperposeperiode($demande->datedebut(), $demande->datefin());
            if (!is_null($periode))
            {
                $demandeparagent[$demande->agentid()][$fonctions->formatdatedb($demande->datedebut()) . "-" . $fonctions->formatdatedb($demande->datefin())] = $demande;
            }
        }
    }

    if (count($demandeparagent)>0)
    {
        echo "<table class='tableausimple' id='table_recup'><tbody>";
        echo "<tr>
                 <td class='titresimple'>Ident. SIHAM</td>
                 <td class='titresimple'>Nom agent</td>
                 <td class='titresimple'>Prénom agent</td>
                 <td class='titresimple'>Structure</td>
                 <td class='titresimple'>Type de récupération</td>
                 <td class='titresimple'>Statut</td>
                 <td class='titresimple'>Nombre de jours</td>
                 <td class='titresimple'>Date début</td>
                 <td class='titresimple'>date fin</td>
                 <td class='titresimple'>Période obligatoire</td>
              </tr>";

        foreach($demandeparagent as $demandeagent)
        {
            // On trie le tableau des demandes par date de début et date de fin (qui composent la clé)
            ksort($demandeagent,SORT_STRING);
    
            $agent = reset($demandeagent)->agent();
            $structid = $agent->structureid();
            $agentstruct = new structure($dbcon);
            if ($agentstruct->load($structid))
            {
                $nomstruct = $agentstruct->nomcourt();
                $nomdirection = $agentstruct->structureenglobante()->nomcourt();
            }
            else
            {
                $nomstruct = "Inconnue (id=$structid)";
                $nomdirection = "Inconnue";
            }

            foreach($demandeagent as $demande)
            {
                $periode = $periodeobligatoire->testsuperposeperiode($demande->datedebut(), $demande->datefin());
                echo "<tr>
                    <td class='cellulesimple'>" . $agent->sihamid()  . "</td>
                    <td class='cellulesimple'>" . $agent->nom() . "</td>
                    <td class='cellulesimple'>" . $agent->prenom() . "</td>
                    <td class='cellulesimple'>" . $nomstruct . "</td>
                    <td class='cellulesimple'>" . $demande->typelibelle() . "</td>
                    <td class='cellulesimple'>" . $fonctions->demandestatutlibelle($demande->statut()) . "</td>
                    <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>
                    <td class='cellulesimple'>" . $demande->datedebut() . "</td>
                    <td class='cellulesimple'>" . $demande->datefin() . "</td>
                    <td class='cellulesimple'>" . $fonctions->formatdate($periode['datedebut']) . " au " . $fonctions->formatdate($periode['datefin']) . "</td>
                </tr>";
            }
        }
        echo "</tbody></table>";
    }

?>
</body>
</html>