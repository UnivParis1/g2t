<?php
    require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    
    require_once ("../html/class/agent.php");
    require_once ("../html/class/structure.php");
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("../html/class/tcpdf/tcpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
    
    $fonctions = new fonctions($dbcon);
    
    $date = date("Ymd");
    
    echo "Début de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";
    
    // On selectionne les demandes en attente de validation qui débutent il y a moins de 2 ans (année en cours et année précédente)
    // Les demandes plus anciennes ne sont pas remontées car le responsable/gestionnaire ne peut plus les valider.
    $sql = "SELECT DEMANDEID FROM DEMANDE WHERE STATUT = 'a' AND DATEDEBUT >='" . ($fonctions->anneeref() - 1) . $fonctions->debutperiode() . "'";
    // echo "SQL des demandes = $sql \n";
    $query = mysql_query($sql, $dbcon);
    $erreur_requete = mysql_error();
    if ($erreur_requete != "")
        echo "SELECT DEMANDEID => $erreur_requete \n";
    
    $arraystruct = array();
    $mail_gest = array();
    $mail_resp = array();
    
    while ($result = mysql_fetch_row($query)) {
        $demande = new demande($dbcon);
        $demande->load($result[0]);
        
        /*
         * $declarationliste = $demande->declarationTPliste();
         * $declaration = reset($declarationliste);
         * $affectation = new affectation($dbcon);
         * $affectation->load($declaration->affectationid());
         */
        
        // On récupère le demandeur
        $demandeur = $demande->agent();
        // On récupère la liste des affectation du demandeur à la date du jour
        $affliste = $demandeur->affectationliste($fonctions->formatdatedb($date), $fonctions->formatdatedb($date));
        
        /*
         * echo "Liste des affectations : \n";
         * print_r($affliste);
         * echo "\n";
         */
        
        if (is_null($affliste) or count($affliste) == 0) {
            // Si le demandeur n'est plus affecté ==> On ne traite pas sa demande
            continue;
        }
        // On conserve la première affectation (et sans doute la seule)
        $affectation = current($affliste);
        
        // echo "l'affectation de l'agent " . $demandeur->identitecomplete() . " est : " . print_r($affectation,true) . "\n";
        
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());
        
        // Si ce n'est pas le responsable de la structure qui a fait la demande
        // => C'est un agent
        // On regarde à qui on doit envoyer la demande de congés pour sa structure
        if (is_null($structure->responsable()))
            echo "Pas de responsable de structure(id : " . $affectation->structureid() . "), pas d'envoi de mail. \n";
        else {
            // Si l'affectation correspondant à la demande est commencée => Sinon on ne dit rien !!!
            if ($fonctions->formatdatedb($affectation->datedebut()) <= $fonctions->formatdatedb($date)) {
                if ($affectation->agentid() != $structure->responsable()->harpegeid()) {
                    $destinatairemail = $structure->agent_envoyer_a($codeinterne);
                    if ($codeinterne == 2) // Gestionnaire service courant
                    {
                        if (isset($mail_gest[$destinatairemail->harpegeid()]))
                            $mail_gest[$destinatairemail->harpegeid()] = $mail_gest[$destinatairemail->harpegeid()] + 1;
                        else
                            $mail_gest[$destinatairemail->harpegeid()] = 1;
                    } else // Responsable service courant
                    {
                        if (isset($mail_resp[$destinatairemail->harpegeid()]))
                            $mail_resp[$destinatairemail->harpegeid()] = $mail_resp[$destinatairemail->harpegeid()] + 1;
                        else
                            $mail_resp[$destinatairemail->harpegeid()] = 1;
                    }
                }            // C'est le responsable de la structure qui a fait la demande
                else {
                    $destinatairemail = $structure->resp_envoyer_a($codeinterne);
                    if (! is_null($destinatairemail)) {
                        // echo "destinatairemailid = " . $destinatairemail->harpegeid() . "\n";
                        if ($codeinterne == 2 or $codeinterne == 3) // 2=Gestionnaire service parent 3=Gestionnaire service courant
                        {
                            if (isset($mail_gest[$destinatairemail->harpegeid()]))
                                $mail_gest[$destinatairemail->harpegeid()] = $mail_gest[$destinatairemail->harpegeid()] + 1;
                            else
                                $mail_gest[$destinatairemail->harpegeid()] = 1;
                        } else // Responsable service parent
                        {
                            if (isset($mail_resp[$destinatairemail->harpegeid()]))
                                $mail_resp[$destinatairemail->harpegeid()] = $mail_resp[$destinatairemail->harpegeid()] + 1;
                            else
                                $mail_resp[$destinatairemail->harpegeid()] = 1;
                        }
                    }
                }
            }
        }
        unset($demande);
        unset($structure);
        unset($declarationliste);
        unset($declaration);
        unset($affectation);
        unset($affliste);
    }
    
    echo "mail_resp=";
    print_r($mail_resp);
    echo "\n";
    echo "mail_gest=";
    print_r($mail_gest);
    echo "\n";
    // Création de l'agent CRON G2T
    $agentcron = new agent($dbcon);
    // -1 est le code pour l'agent CRON dans G2T
    $agentcron->load('-1');
    foreach ($mail_resp as $agentid => $nbredemande) {
        $responsable = new agent($dbcon);
        $responsable->load($agentid);
        echo "Avant le sendmail mail (Responsable) = " . $responsable->mail() . " (" . $responsable->identitecomplete() . " Harpegeid = " . $responsable->harpegeid() . ") \n";
        
        $agentcron->sendmail($responsable, "En tant que responsable de service, des demandes de congés ou d'autorisations d'absence sont en attentes", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Responsable'.\n", null);
        unset($responsable);
    }
    foreach ($mail_gest as $agentid => $nbredemande) {
        $gestionnaire = new agent($dbcon);
        $gestionnaire->load($agentid);
        echo "Avant le sendmail mail (Gestionnaire) = " . $gestionnaire->mail() . " (" . $gestionnaire->identitecomplete() . " Harpegeid = " . $gestionnaire->harpegeid() . ") \n";
        
        $agentcron->sendmail($gestionnaire, "En tant que gestionnaire de service, des demandes de congés ou d'autorisations d'absence sont en attentes", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Gestionnaire'.\n", null);
        unset($gestionnaire);
    }
    unset($agentcron);
    echo "Fin de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";

?>