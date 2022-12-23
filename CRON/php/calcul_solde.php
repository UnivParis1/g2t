<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);
    
    echo "Début du calcul des soldes " . date("d/m/Y H:i:s") . "\n";
    
    $sql = "SELECT AGENTID,NOM,PRENOM FROM AGENT ORDER BY AGENTID";
    $query_agent = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "SELECT FROM AGENT => $erreur_requete \n";
    
    // echo "Avant deb / fin periode \n";
    $date_deb_period = $fonctions->anneeref() . $fonctions->debutperiode();
    $date_fin_period = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
    
    // echo "Avant Nbre jours periode... \n";
    $nbre_jour_periode = $fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
    // echo "Avant Nbre jours offert... \n";
    $nbr_jrs_offert = 0;
    $dbconstante = "NBJOURS" . substr($date_deb_period, 0, 4);
    if ($fonctions->testexistdbconstante($dbconstante))  $nbr_jrs_offert = $fonctions->liredbconstante($dbconstante);
    
    // echo "Avant le 1er while \n";
    while ($result = mysqli_fetch_row($query_agent)) {
        // !!!!!!! ATTENTION : Les 4 lignes suivantes permettent de ne tester qu'un seul dossier !!!!
        /*
        if ($result[0]!='13825')
        {
            continue;
        }
        */
        // !!!!!!! FIN du test d'un seul dossier !!!!
        
        $agentid = $result[0];
        $agentinfo = $result[1] . " " . $result[2];
        
        $agent = new agent($dbcon);
        $agent->load($agentid);
        echo "###############################################################\n";
        echo "On est sur l'agent : " . $agent->identitecomplete() . " (id = $agentid) \n";
        $solde = $agent->calculsoldeannuel($fonctions->anneeref(),true, false, true); // On calcule le solde de l'année courante + on met à jour le solde en base + on n'ecrit pas les traces d'exécution + on les affiche
        echo "Le solde annuel de l'agent " . $agent->identitecomplete() . " (id = " . $agent->agentid() . ") pour l'annee " .  $fonctions->anneeref() . "-" . ($fonctions->anneeref()+1)  . " est de $solde jours.\n";
    }
    echo "Fin du calcul des soldes " . date("d/m/Y H:i:s") . "\n";

?>