<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'envoi des mail des alertes de reliquats " . date("d/m/Y H:i:s") . "\n";

    $force = false;
    if (isset($argv[1])) {
        if ($argv[1] == 'force')
            $force = true;
    }
    
    /////////////////////////////////////////////////
    // Nombre de rappel d'utilisation des reliquats
    $nbrerappel = 2;
    /////////////////////////////////////////////////

    // On crée un tableau contenant les dates de rappel
    // Le 1er jour du mois de la fin de repport puis tous les 01 des mois précédents
    $arrayrappel = array();
    $datefinreport = $fonctions->formatdatedb(($fonctions->anneeref() + 1) . $fonctions->liredbconstante("FIN_REPORT"));
    $datefinreport = substr($datefinreport,0,6)  ."01";
    $arrayrappel[0] = $datefinreport;
    for ($index=1 ; $index<$nbrerappel ; $index++)
    {
        $timestampfin = strtotime($datefinreport);
        $datefinreport = date("Ymd", strtotime("-1day", $timestampfin));
        // datefinreport = Le dernier jour du mois précédent la fin du report
        $datefinreport = substr($datefinreport,0,6)  ."01";
        $arrayrappel[$index] = $datefinreport;
    }
    $datefinreport = $fonctions->formatdatedb(($fonctions->anneeref() + 1) . $fonctions->liredbconstante("FIN_REPORT"));

    // Si on veut forcer l'execution, on ajoute la date courante dans la liste des dates de rappel
    if ($force)
    {
        $arrayrappel[] = date('Ymd');;
    }
    
    
    echo "La date du jour est : $date \n";
    echo "Liste des dates de rappel : \n";
    print_r($arrayrappel);
    echo "La date de fin de report est $datefinreport\n";

    if (in_array($date, $arrayrappel))
    {
        echo "C'est donc une date de rappel \n";
        // L'agent a un solde de congés de reliquat > 0 et il en a pris
        // Cela permet d'éviter d'envoyer des mails aux agents qui ne sont pas dans G2T.
        $anneeref = substr(($fonctions->anneeref() - 1),2,2);
        $sql = "SELECT AGENTID FROM SOLDE WHERE DROITAQUIS - DROITPRIS > 0 AND DROITPRIS > 0  AND TYPEABSENCEID = 'ann" . $anneeref . "'";
        //$sql = "SELECT AGENTID FROM SOLDE WHERE DROITAQUIS - DROITPRIS > 0 AND DROITPRIS > 0  AND TYPEABSENCEID = 'ann" . $anneeref . "' AND AGENTID=9328";
        //echo "SQL des soldes = $sql \n";
        $query = mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "SELECT AGENTID => $erreur_requete \n";

        // Création de l'agent CRON G2T
        $agentcron = new agent($dbcon);
        // -1 est le code pour l'agent CRON dans G2T
        $agentcron->load('-1');
    
        while ($result = mysqli_fetch_row($query)) {
            $agent = new agent($dbcon);
            $agent->load($result[0]);
            echo "On travaille sur l'agent " . $agent->identitecomplete() . " (ID=" . $agent->agentid() . ") \n";
            $affectationlist = $agent->affectationliste($date, $date);
            if (! is_null($affectationlist))
            // Il y a une affectation en cours
            {
                if (count($affectationlist) > 0)
                // Le tableau des affectations n'est pas vide (<==> En théorie ca ne sert à rien mais plus fiable)
                {
                    // L'agent est bien affecté à la date du jour
                    $complement = new complement($dbcon);
                    $complement->load($agent->agentid(), "REPORTACTIF");
                    // Si le complement n'est pas initialisé (NULL ou "") alors on active le report
                    if (strcasecmp((string)$complement->valeur(), "O") == 0) // or strlen($complement->valeur()) == 0)
                        $reportactif = true;
                    else
                        $reportactif = FALSE;

                    if ($reportactif)
                    {
                        $solde = new solde($dbcon);
                        $msgerreur = null;
                        $msgerreur = $solde->load($agent->agentid(), "ann" . $anneeref);
                        if ((!is_null($msgerreur)) or $msgerreur <>"")
                        {
                            echo "Oups ! Problème dans le chargement du solde ann" . $anneeref  . " pour l'agent " . $agent->agentid() . " : $msgerreur \n";
                        }
                        else
                        {
                            $solderestant = $solde->droitaquis() - $solde->droitpris();
                            echo "Tout est ok... On peut envoyer le mail ==> Solde restant = $solderestant \n";
                            $corpsdumail="Il vous reste " . $solderestant  . " jours sur votre solde de congés annuels " . ($fonctions->anneeref() - 1) . "/" . $fonctions->anneeref() . ".\n";
                            $corpsdumail=$corpsdumail . "Vous devez impérativement les consommer avant le " . $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->liredbconstante("FIN_REPORT")) . ".\n";
                            $corpsdumail=$corpsdumail . "Dans la cas contraire, votre reliquat sera définitivement perdu.\n";
                            $corpsdumail=$corpsdumail . "\n";

                            $agentcron->sendmail($agent, "Alerte : Rappel sur l'utilisation des reliquats", $corpsdumail, null, null, true);
                        }
                    }

                }
            }
        }
    }

    echo "Fin de l'envoi des mail des alertes de reliquats " . date("d/m/Y H:i:s") . "\n";
?>