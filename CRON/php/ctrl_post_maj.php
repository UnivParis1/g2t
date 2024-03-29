<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);

    echo "Début des controles post mise à jour de la base...." . date("d/m/Y H:i:s") . "\n";

    // récupération des gestionnaires RH des anomalies
    //$gestrhanolist = $fonctions->listeprofilrh(agent::PROFIL_RHANOMALIE); // 3 = Profil RHANOMALIE
    $gestrhanolist = $fonctions->listeprofilrh(agent::PROFIL_RHCONGE); // 3 = Profil RHCONGE

    $cron = new agent($dbcon);
    $cron->load(SPECIAL_USER_IDCRONUSER); // L'utilisateur CRON

    $tabadministrateur = array();
    $sql = "SELECT AGENTID FROM COMPLEMENT WHERE COMPLEMENTID = 'ESTADMIN' AND VALEUR = 'O'";
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        error_log(basename(__FILE__) . " " . $erreur_requete);
    while ($agentid = mysqli_fetch_row($query)) {
        $admin = new agent($dbcon);
        // echo "Avant le load \n";
        $admin->load($agentid[0]);
        // echo "Apres le load \n";
        $tabadministrateur[$admin->agentid()] = $admin;
        unset($admin);
    }

    // echo "Liste des admins : " . print_r($tabadministrateur,true) . "\n";

    echo "Recherche des soldes négatifs...\n";
    $sql = "SELECT AGENTID,SOLDE.TYPEABSENCEID,DROITAQUIS,DROITPRIS ,LIBELLE
    			FROM SOLDE , TYPEABSENCE
    			WHERE DROITPRIS > DROITAQUIS
    				  AND SOLDE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID
    	  			  AND SOLDE.TYPEABSENCEID IN (SELECT TYPEABSENCEID FROM TYPEABSENCE WHERE ANNEEREF IN ('" . $fonctions->anneeref() . "','" . ($fonctions->anneeref() - 1) . "'));";
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        error_log(basename(__FILE__) . " " . $erreur_requete);
    while ($agentid = mysqli_fetch_row($query)) // Des agents ont des soldes négatifs !!!
    {
        $agent = new agent($dbcon);
        $agent->load($agentid[0]);
        $affectationarray = $agent->affectationliste(date("d/m/Y"), date("d/m/Y"));
        if (is_array($affectationarray)) { // On envoie un mail aux responsable de la structure pour informer le solde négatif
            $struct = new structure($dbcon);
            $affectation = current($affectationarray);
            $struct->load($affectation->structureid());
            $corpmail = "L'application G2T a détecté que le solde de congés " . $agentid[4] . " pour l'agent " . $agent->identitecomplete() . " est négatif.\n";
            $corpmail = $corpmail . "Cette situation peut se présenter lors d'une modification de temps partiel ou lors d'un passage à temps partiel.\n";
            $corpmail = $corpmail . "\n";
            $corpmail = $corpmail . "Nous vous invitons donc prendre contact avec l'agent " . $agent->identitecomplete() . " afin de régulariser la situation.\n";
            $corpmail = $corpmail . "\n";
            // ////////////////////////////////////
            $cron->sendmail($struct->responsable(), "Solde de congés négatif pour l'agent " . $agent->identitecomplete(), $corpmail);
            // ////////////////////////////////////
            foreach ((array) $gestrhanolist as $gestrhano) {
                $cron->sendmail($gestrhano, "Solde de congés négatif pour l'agent " . $agent->identitecomplete(), $corpmail);
            }
        }
        unset($agent);
    }

    echo "Recherche des demandes de congés ou d'absences incohérentes...\n";

    // $datedebut = $fonctions->formatdate ( ($fonctions->anneeref () - 1) . $fonctions->debutperiode () );
    $datedebut = $fonctions->formatdate(($fonctions->anneeref()) . $fonctions->debutperiode());
    $datefin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());

    echo "Période => début  : $datedebut    fin : $datefin \n";

    $sql = "SELECT DISTINCT AFFECTATION.AGENTID
    				FROM AFFECTATION,AGENT
    				WHERE OBSOLETE = 'N'
    				  AND DATEFIN >= '" . date("Ymd") . "'
    				  AND AGENT.AGENTID = AFFECTATION.AGENTID
    				ORDER BY AFFECTATION.AGENTID"; // DATEMODIFICATION = " . date('Ymd');
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        error_log(basename(__FILE__) . " " . $erreur_requete);

    while ($agentid = mysqli_fetch_row($query)) // Des agents ont des affectations modifiées !!!
    {
        $agent = new agent($dbcon);
        $agent->load($agentid[0]);
        echo "Recherche pour l'agent : " . $agent->identitecomplete() . " (Id = " . $agent->agentid() . ")\n";
        $tabanalyse = $agent->controlecongesTP($datedebut, $datefin);
        $text = "";
        // echo "tabanalyse = " . print_r($tabanalyse,true) . "\n";
        foreach ($tabanalyse as $demandeid => $textanalyse) {
            $demande = new demande($dbcon);
            $demande->load($demandeid);
//            if (strcasecmp($demande->statut(), 'r') != 0) // Si la demande n'est pas annulée ou refusée !
            if (strcmp($demande->statut(), demande::DEMANDE_ANNULE) != 0 and strcmp($demande->statut(), demande::DEMANDE_REFUSE) != 0) // Si la demande n'est pas annulée et si elle n'est pas refusée !
            {
                $text .= " * Compte-rendu de l'analyse de la demande du " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . " au " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . "\n" . $textanalyse . "\n";
            }
            unset($demande);
        }
        if ($text != "") {
            $corpmail = "L'application G2T a détecté des incohérences entre le nombre de jours calculés au moment où la demande a été faite et le nombre de jours recalculé avec la situation actuelle de votre dossier dans l'application.\n";
            $corpmail = $corpmail . "Cette situation peut se présenter lors d'une modification de temps partiel, lors d'un passage à temps partiel ou à temps complet.\n";
            $corpmail = $corpmail . "Cette différence peut également se présenter si vous avez bénéficié d'un arrêt de travail (maladie) lors d'une période de congés.\n";
            $corpmail = $corpmail . "\n";
            $corpmail = $corpmail . "Afin de rectifier votre demande, vous devez demander, à votre responsable de service, d'annuler votre demande de congés.\n";
            $corpmail = $corpmail . "Il vous faudra ensuite recréer la demande, via l'application. Le nombre de jours correct sera alors calculé.\n";
            $corpmail = $corpmail . "\n";
            $corpmail = $corpmail . $text;
            echo "Corps du mail = " . $corpmail;
            $cron->sendmail($agent, "Incohérence dans une ou plusieurs demandes", $corpmail);
        }
    }

    echo "Fin des controles post mise à jour de la base...." . date("d/m/Y H:i:s") . "\n";

?>