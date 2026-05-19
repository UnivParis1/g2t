<?php

    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
    $fonctions = new fonctions($dbcon);

    echo "\nDébut de la saisie des congés en période obligatoire " . date("d/m/Y H:i:s") . "\n";

    $cronuser = new agent($dbcon);
    $cronuser->load(SPECIAL_USER_IDCRONUSER);
    $periode = new periodeobligatoire($dbcon);
    $listeperiode = $periode->load($fonctions->anneeref());

    // Si on a des périodes obligatoires déclarées
    if (count((array)$listeperiode)>0)
    {
        $typeabsenceid = 'ann' . substr($fonctions->anneeref(), -2 , 2);
        $typeabsenceanticipeid = 'ann' . (substr($fonctions->anneeref()+1, -2 , 2));
        $listeagent = $fonctions->listeagentsavecaffectation();
        //////////////////////////////////////
        //$listeagent = array('9328' => "Pascal COMTE", "13825" => "YAEL KTORZA");
        //////////////////////////////////////
        foreach($listeagent as $agentid => $identite)
        {
            $logtexte = "###############################################";
            //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
            echo $logtexte . " \n";
            $logtexte = "Traitement de l'agent $agentid ($identite) ";
            echo $logtexte . " \n";

            $agent = new agent($dbcon);
            if (!$agent->load($agentid))
            {
                $logtexte = "Erreur lors du chargement de l'agent => On l'ignore ";
                echo $logtexte . " \n";
                continue;
            }

            foreach((array)$listeperiode as $periode)
            {
                $returndesc = '';
                $agent->forceperiodeobligatoire($periode,false,$returndesc);

                echo $returndesc;
            }
        }
    }

    echo "\nFin de la saisie des congés en période obligatoire " . date("d/m/Y H:i:s") . "\n";


?>