<?php
    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');
/*
    require_once ("../html/class/agent.php");
    require_once ("../html/class/structure.php");
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("../html/class/fpdf/fpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
*/
    
    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de l'envoi des mail des alertes de reliquats " . date("d/m/Y H:i:s") . "\n";

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
        $sql = "SELECT HARPEGEID FROM SOLDE WHERE DROITAQUIS - DROITPRIS > 0 AND DROITPRIS > 0  AND TYPEABSENCEID = 'ann" . $anneeref . "'";
        //$sql = "SELECT HARPEGEID FROM SOLDE WHERE DROITAQUIS - DROITPRIS > 0 AND DROITPRIS > 0  AND TYPEABSENCEID = 'ann" . $anneeref . "' AND HARPEGEID=9328";
        //echo "SQL des soldes = $sql \n";
        $query = mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "SELECT HARPEGEID => $erreur_requete \n";

        // Création de l'agent CRON G2T
        $agentcron = new agent($dbcon);
        // -1 est le code pour l'agent CRON dans G2T
        $agentcron->load('-1');
    
        while ($result = mysqli_fetch_row($query)) {
            $agent = new agent($dbcon);
            $agent->load($result[0]);
            echo "On travaille sur l'agent " . $agent->identitecomplete() . " (ID=" . $agent->harpegeid() . ") \n";
            $affectationlist = $agent->affectationliste($date, $date);
            if (! is_null($affectationlist))
            // Il y a une affectation en cours
            {
                if (count($affectationlist) > 0)
                // Le tableau des affectations n'est pas vide (<==> En théorie ca ne sert à rien mais plus fiable)
                {
                    // L'agent est bien affecté à la date du jour
                    $complement = new complement($dbcon);
                    $complement->load($agent->harpegeid(), "REPORTACTIF");
                    // Si le complement n'est pas initialisé (NULL ou "") alors on active le report
                    if (strcasecmp($complement->valeur(), "O") == 0) // or strlen($complement->valeur()) == 0)
                        $reportactif = true;
                    else
                        $reportactif = FALSE;

                    if ($reportactif)
                    {
                        $solde = new solde($dbcon);
                        $msgerreur = null;
                        $msgerreur = $solde->load($agent->harpegeid(), "ann" . $anneeref);
                        if ((!is_null($msgerreur)) or $msgerreur <>"")
                        {
                            echo "Oups ! Problème dans le chargement du solde ann" . $anneeref  . " pour l'agent " . $agent->harpegeid() . " : $msgerreur \n";
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