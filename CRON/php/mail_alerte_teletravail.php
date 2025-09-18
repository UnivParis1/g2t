<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);

    $date = date("Y-m-d");

    echo "\nDébut de l'envoi des mail des alertes de fin de convention de télétravail " . date("d/m/Y H:i:s") . "\n";
    
    echo "La date du jour est : $date \n";
    $datefinconvention_1mois = date("Y-m-d",strtotime($date."+ 1 months"));
    $datefinconvention_2mois = date("Y-m-d",strtotime($date."+ 2 months"));
    echo "Les dates de fin des conventions à tester sont $datefinconvention_1mois et $datefinconvention_2mois \n";
    
    $listeagent = $fonctions->listeagentteletravail($datefinconvention_1mois,$datefinconvention_1mois,false);
    $listeagent = array_merge((array)$listeagent,(array)$fonctions->listeagentteletravail($datefinconvention_2mois,$datefinconvention_2mois,false));
    $listeagent = array_unique($listeagent);
    
    foreach ((array)$listeagent as $agentid)
    {
        // Création de l'agent CRON G2T
        $agentcron = new agent($dbcon);
        // le code pour l'agent CRON dans G2T
        $agentcron->load(SPECIAL_USER_IDCRONUSER);
        
        $agent = new agent($dbcon);
        if ($agent->load($agentid)) // Si le chargement de l'agent est ok
        {
            $teletravailliste = $agent->teletravailliste($datefinconvention_1mois,$datefinconvention_1mois);
            $teletravailliste = array_merge((array)$teletravailliste,(array)$agent->teletravailliste($datefinconvention_2mois,$datefinconvention_2mois));
            $teletravailliste = array_unique($teletravailliste);
            foreach ((array)$teletravailliste as $conventionid)
            {
                $convention = new teletravail($dbcon);
                if ($convention->load($conventionid))
                {
                    
                    $force = false;
//                    if ($agentid == 9328)
//                    {
//                        $force = true;
//                    }
                    if (($convention->statut()==teletravail::TELETRAVAIL_VALIDE 
                        and ($fonctions->formatdatedb($convention->datefin()) == $fonctions->formatdatedb($datefinconvention_1mois)
                             or $fonctions->formatdatedb($convention->datefin()) == $fonctions->formatdatedb($datefinconvention_2mois)))
                       or $force)
                    {
                        $timestamp = strtotime($fonctions->formatdatedb($convention->datefin()));
                        $lendemain = date("d/m/Y", strtotime("+1 day", $timestamp)); // On passe au lendemain de la date de fin de la convention
                        
                        $teletravaillistesuivante = $agent->teletravailliste($lendemain,'31/12/2999');
                        $conventionsuivantetrouvee = false;
                        foreach ($teletravaillistesuivante as $conventionsuivanteid)
                        {
                            $conventionsuivante = new teletravail($dbcon);
                            if ($conventionsuivante->load($conventionsuivanteid) and in_array($conventionsuivante->statut(),array(teletravail::TELETRAVAIL_ATTENTE,teletravail::TELETRAVAIL_VALIDE)))
                            {
                                $conventionsuivantetrouvee = true;
                                break;
                            }
                        }

                        echo "convention teletravail Id = $conventionid  Statut = " . $convention->statut() . " Agent = $agentid  date fin = " . $fonctions->formatdate($convention->datefin()) . " : ";
                        // Si on n'a pas trouvé de convention après la convention qui lève l'alerte => On envoi un mail à l'agent
                        if (!$conventionsuivantetrouvee)
                        {
                            // On doit envoyer un mail de rappel à l'agent !
                            echo "Tout est ok... On peut envoyer le mail \n";
                            $corpsdumail="Votre convention de télétravail arrive bientôt à son terme.<br>";
                            $corpsdumail=$corpsdumail . "Si vous souhaitez renouveler votre convention de télétravail, veuillez prendre contact avec le service de la DRH afin de connaitre les modalités.<br>";
                            $corpsdumail=$corpsdumail . "Dans le cas contraire, vous ne serez plus en télétravail après le " . $fonctions->formatdate($convention->datefin()) . ".<br>";
                            $corpsdumail=$corpsdumail . "<br>";
                            //echo "Avant l'envoi \n";
                            $agentcron->sendmail($agent, "Alerte : Fin de votre convention de télétravail", $corpsdumail, null, null, false);
                            //echo "Apres l'envoi \n";
                        }
                        else
                        {
                            echo "convention $conventionsuivanteid après (début : " . $fonctions->formatdate($conventionsuivante->datedebut()) . " Statut : " . $conventionsuivante->statut() . ") => Pas de mail \n";
                        }
                    }
                    else
                    {
                        // echo "Pas d'envoi de mail => Critères non satisfait \n";
                    }
                }
                else
                {
                    echo "Problème de chargement de la convention teletravail $conventionid \n";
                }
            }
        }
        else
        {
            echo "Problème de chargement de l'agent $agentid \n";
        }
    }
    echo "Fin de l'envoi des mail des alertes de fin de convention de télétravail " . date("d/m/Y H:i:s") . "\n";
?>