<?php
    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/includes/g2t_ws_url.php');
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

    echo "Début de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";

    // On selectionne les demandes en attente de validation qui débutent il y a moins de 2 ans (année en cours et année précédente) mais qui ne sont pas postérieure à la période en cours (< Anneeref +1 + debut_période)
    // Les demandes plus anciennes ne sont pas remontées car le responsable/gestionnaire ne peut plus les valider.
    $sql = "SELECT DEMANDEID FROM DEMANDE WHERE STATUT = '" . demande::DEMANDE_ATTENTE . "' AND DATEDEBUT >='" . ($fonctions->anneeref() - 1) . $fonctions->debutperiode() . "' AND DATEDEBUT < '" . ($fonctions->anneeref() + 1) . $fonctions->debutperiode() . "'";
    // echo "SQL des demandes = $sql \n";
    $query = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "SELECT DEMANDEID => $erreur_requete \n";

    $arraystruct = array();
    $mail_gest = array();
    $mail_resp = array();

    $codeinterne = null;

    while ($result = mysqli_fetch_row($query)) {
        $demande = new demande($dbcon);
        $demande->load($result[0]);

        // On récupère le demandeur
        $demandeur = $demande->agent();
        echo "Le demandeur de la demande " . $demande->id() . " est " . $demandeur->identitecomplete() . " (Agentid = " . $demandeur->agentid() . ")\n";
        // On récupère la liste des affectation du demandeur à la date du jour
        $affliste = $demandeur->affectationliste($fonctions->formatdatedb($date), $fonctions->formatdatedb($date));

        /*
         * echo "Liste des affectations : \n";
         * print_r($affliste);
         * echo "\n";
         */

        if (is_null($affliste) or count($affliste) == 0) {
            // Si le demandeur n'est plus affecté ==> On ne traite pas sa demande
            echo "L'agent " . $demandeur->identitecomplete() . " n'a pas (ou plus) d'affectation => On ignore \n";
            continue;
        }
        // On conserve la première affectation (et sans doute la seule)
        $affectation = current($affliste);

        echo "l'affectation de l'agent " . $demandeur->identitecomplete() . " est : " . $affectation->structureid() . "\n";
        
        if ($affectation->structureid() . "" == "") 
        {
            // Si la structure du demandeur n'est plus définie ==> On ne traite pas sa demande
            echo "La structure d'affectation de l'agent " . $demandeur->identitecomplete() . " n'a pas définie => On ignore \n";
            continue;
        }
        
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());

        // Si ce n'est pas le responsable de la structure qui a fait la demande
        // => C'est un agent
        // On regarde à qui on doit envoyer la demande de congés pour sa structure
        $responsable = $structure->responsable();
        if (is_null($responsable))
            // S'il n'y a pas de responsable => 
            //  - Dans le cas d'un agent on ne peut pas prévenir le responsable
            //  - Dans le cas du responsable, on est incapable de déterminer que c'est lui le responsable
            // En théorie ça ne se produit jamais car il y a tjrs un responsable (au pire G2T CRON)
            echo "Pas de responsable de structure (id : " . $affectation->structureid() . "), pas d'envoi de mail. \n";
        else {
            // Si l'affectation correspondant à la demande est commencée => Sinon on ne dit rien !!!
            if ($fonctions->formatdatedb($affectation->datedebut()) <= $fonctions->formatdatedb($date)) 
            {
                if ($affectation->agentid() != $responsable->agentid()) 
                {
                    // On est dans le cas d'une demande d'un agent
                    $destinatairemail = $structure->agent_envoyer_a($codeinterne);
                    if (! is_null($destinatairemail))
                    {
                        if ($codeinterne == 2) // On envoie le mail au gestionnaire service courant
                        {
                            if (isset($mail_gest[$destinatairemail->agentid()]))
                                $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] . $demande->id() . ',' ;
                            else
                                $mail_gest[$destinatairemail->agentid()] = $demande->id() . ',';
                        } 
                        else // On envoie le mail au responsable service courant
                        {
                            if (isset($mail_resp[$destinatairemail->agentid()]))
                                $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_resp[$destinatairemail->agentid()] = $demande->id() . ',';
                        }
                    }
                    else
                    {
                        echo "Impossible de trouver le destinataire du mail d'une demande agent \n";
                    }
                }
                else 
                {
                    // On est dans le cas d'une demande d'un responsable de la structure
                    $destinatairemail = $structure->resp_envoyer_a($codeinterne);
                    if (! is_null($destinatairemail)) 
                    {
                        // echo "destinatairemailid = " . $destinatairemail->agentid() . "\n";
                        if ($codeinterne == 2 or $codeinterne == 3) // 2=Gestionnaire service parent 3=Gestionnaire service courant
                        {
                            if (isset($mail_gest[$destinatairemail->agentid()]))
                                $mail_gest[$destinatairemail->agentid()] = $mail_gest[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_gest[$destinatairemail->agentid()] = $demande->id() . ',';
                        } 
                        else // Responsable service parent
                        {
                            if (isset($mail_resp[$destinatairemail->agentid()]))
                                $mail_resp[$destinatairemail->agentid()] = $mail_resp[$destinatairemail->agentid()] . $demande->id() . ',';
                            else
                                $mail_resp[$destinatairemail->agentid()] = $demande->id() . ',';
                        }
                    }
                    else
                    {
                        echo "Impossible de trouver le destinataire du mail d'une demande responsable \n";
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
    foreach ($mail_resp as $agentid => $listedemande) {
        $responsable = new agent($dbcon);
        $responsable->load($agentid);
        $nbredemande = substr_count($listedemande, ',');
        echo "Avant le sendmail mail (Responsable) = " . $responsable->mail() . " (" . $responsable->identitecomplete() . " agentid = " . $responsable->agentid() . ") : Il y a $nbredemande demandes en attente \n";

        $agentcron->sendmail($responsable, "En tant que responsable de service, des demandes de congés ou d'autorisations d'absence sont en attente", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Responsable'.\n", null);
        unset($responsable);
    }
    foreach ($mail_gest as $agentid => $listedemande) {
        $gestionnaire = new agent($dbcon);
        $gestionnaire->load($agentid);
        $nbredemande = substr_count($listedemande, ',');
        echo "Avant le sendmail mail (Gestionnaire) = " . $gestionnaire->mail() . " (" . $gestionnaire->identitecomplete() . " agentid = " . $gestionnaire->agentid() . ") : Il y a $nbredemande demandes en attente \n";

        $agentcron->sendmail($gestionnaire, "En tant que gestionnaire de service, des demandes de congés ou d'autorisations d'absence sont en attente", "Il y a $nbredemande demande(s) de congés ou d'autorisation d'absence en attente de validation.\n Merci de bien vouloir les valider dès que possible à partir du menu 'Gestionnaire'.\n", null);
        unset($gestionnaire);
    }
    unset($agentcron);
    echo "Fin de l'envoi des mail de conges " . date("d/m/Y H:i:s") . "\n";

?>