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
        //header('Location: index.php');
        exit();
    }
    
    
    $esignatureactive = false;
    if ($fonctions->testexistdbconstante('ESIGNATURETELETRAVAIL')) 
    {
        $esignatureactive = $fonctions->liredbconstante('ESIGNATURETELETRAVAIL');
        if (strcasecmp($esignatureactive,'o')==0)
        {
            $esignatureactive = true;
        }
        else
        {
            $esignatureactive = false;
        }
    }
    
    
    // Pour l'entrée dans le menu 'gestion RH => hors eSignature'
    $noesignature = "";
    if (isset($_POST["noesignature"]))
    {
        $noesignature = $_POST["noesignature"];
    }
    if (strcasecmp($noesignature, 'yes')==0) // Si le flag $noesignature = yes => On force le $esignatureactive à false
    {
        $esignatureactive = false;
    }

    
    $user = new agent($dbcon);
    $user->load($userid);
    
    $mode='';
    if (isset($_POST["mode"]))
    {
       $mode = $_POST["mode"];
    }
    
    if ($mode=='')
    {
        $agentid = $userid;
    }
    elseif ($mode=='gestrh')
    {
        if (isset($_POST["agentid"]))
        {
            $agentid = $_POST["agentid"];
            if (! is_numeric($agentid)) {
                $agentid = $fonctions->useridfromCAS($agentid);
                if ($agentid === false)
                {
                    $agentid = null;
                }
            }
            
            if (! is_numeric($agentid)) {
                $agentid = null;
                $agent = null;
            }
        }
        else
        {
            $agentid = null;
        }
    }
    elseif ($mode=='resp' or $mode=='gestion')
    {
        $agentid = null;
/*
        if (isset($_POST["agentid"]))
        {
            $agentid = $_POST["agentid"];
            if (! is_numeric($agentid)) {
                $agentid = $fonctions->useridfromCAS($agentid);
                if ($agentid === false)
                {
                    $agentid = null;
                }
            }
            
            if (! is_numeric($agentid)) {
                $agentid = null;
                $agent = null;
            }
        }
        else
        {
            $agentid = null;
        }
 */
    }

        
    $cancelteletravailarray = null;
    if (isset($_POST["cancel"])) // Tableau des id des conventions à désactiver
    {
        $cancelteletravailarray = $_POST["cancel"];
    }
    
    $datedebutconv = null;
    if (isset($_POST["date_debut_conv"])) // Tableau des id des conventions avec les dates de début
    {
        $datedebutconv = $_POST["date_debut_conv"];
    }
    $datefinconv = null;
    if (isset($_POST["date_fin_conv"])) // Tableau des id des conventions avec les dates de fin
    {
        $datefinconv = $_POST["date_fin_conv"];
    }
    
    $motifmedical = array();
    if (isset($_POST["motifmedical"]))
    {
        $motifmedical = $_POST["motifmedical"];
    }
    
    $activitetele = "";
    if (isset($_POST["activitetele"]))
    {
        $activitetele = $_POST["activitetele"];
    }
    $periodeexclusion = "";
    if (isset($_POST["periodeexclusion"]))
    {
        $periodeexclusion = $_POST["periodeexclusion"];
    }
    $periodeadaptation = "";
    if (isset($_POST["periodeadaptation"]))
    {
        $periodeadaptation = $_POST["periodeadaptation"];
    }
    $motifrefus = "";
    if (isset($_POST["motifrefus"]))
    {
        $motifrefus = $_POST["motifrefus"];
    }
    $idconvention = "";
    if (isset($_POST["idconvention"]))
    {
        $idconvention = $_POST["idconvention"];
    }
    $statutresp = "";
    if (isset($_POST["statutresp"]))
    {
        $statutresp = $_POST["statutresp"];
    }
    
    
    
    require ("includes/menu.php");
    
    // echo "<br>" . print_r($_POST, true) . "<br><br>";
    
    //echo "$cancelteletravailarray = ";
    //var_export($cancelteletravailarray);
    //echo "<br>";
    
    $erreur = '';
    $info = '';
    $alerte = '';
    $nbjoursmaxteletravail = 0;
    $datedebutteletravail = null;
    $datefinteletravail = null;
    $typeconv = null;
    $tabteletravail = str_pad('',14,'0');
    $disablesubmit = false;
    $listeconventionchevauche = array();
    
    if ($fonctions->testexistdbconstante('NBJOURSMAXTELETRAVAIL')) 
    {
        $nbjoursmaxteletravail = $fonctions->liredbconstante('NBJOURSMAXTELETRAVAIL');
    }
    
    if (!is_null($agentid))
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);
    }

    // On vérifie que le circuit est correctement paramétré
    $params = array();
    $maxniveau = 0;
    $taberrorcheckmail = array();
    if (!is_null($agentid) and $esignatureactive)
    {
        //echo "Avant la vérification du circuit => numéro 1 <br>";
        $taberrorcheckmail = $fonctions->checksignataireteletravailliste($params,$agent,$maxniveau);
    }
    //var_dump($maxniveau);
    if (count($taberrorcheckmail) > 0)
    {
        // var_dump("errorcheckmail = $errorcheckmail");
        $errorcheckmailstr = '';
        foreach ($taberrorcheckmail as $errorcheckmail)
        {
            if (strlen($errorcheckmailstr)>0) { $errorcheckmailstr = $errorcheckmailstr . '<br>'; }
            $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
        }
        $erreur = "Impossible de créer une convention de télétravail car <br>$errorcheckmailstr";
        $disablesubmit = true;
    }
    elseif (!is_null($agentid))
    {
        $id_model = '';
        if ($esignatureactive)
        {
            $id_model = trim($fonctions->getidmodelteletravail($maxniveau,$agent));
        }
        //var_dump($id_model);
        if (trim($id_model) == '' and $esignatureactive)
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "Le modèle eSignature pour la création d'une convention télétravail n'a pas pu être déterminé.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $disablesubmit = true;
        }
        
    }
    
    if (count((array)$cancelteletravailarray)>0) // On a cliqué sur un bouton d'annulation pour annulé une convention
    {
        //echo "On va annuler des conventions de télétravail.<br>";
        foreach ((array)$cancelteletravailarray as $cancelteletravailid)
        {
            //echo "cancelteletravailid = $cancelteletravailid <br>";
            $teletravail = new teletravail($dbcon);
            $return = $teletravail->load($cancelteletravailid);
            if (!$return)
            {
                if (strlen($erreur)>0) {$erreur = $erreur . '<br>'; }
                $erreur = $erreur . "Erreur dans le chargement de la convention $cancelteletravailid pour annulation : " . $return;
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif ($teletravail->statut() == teletravail::TELETRAVAIL_ANNULE)
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "Impossible d'annuler la convention  télétravail $cancelteletravailid : Elle est déjà annulée.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
            }
            else
            {
                $return = '';
                if ($esignatureactive and $teletravail->statutresponsable()==teletravail::TELETRAVAIL_VALIDE)
                {
                    $return = $fonctions->deleteesignaturedocument($teletravail->esignatureid());
                }
                if (strlen($return)>0) // On a rencontré une erreur dans la suppression eSignature
                {
                    if (strlen($erreur)>0) {$erreur = $erreur . '<br>'; }
                    $erreur = $erreur . "Impossible d'annuler la convention  télétravail $cancelteletravailid : $return";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
                }
                else
                {
                    // Annulation dans G2T
                    $teletravail->statut(teletravail::TELETRAVAIL_ANNULE);
                    $return = $teletravail->store();
                    if ($return != "")
                    {
                        if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                        $erreur = $erreur . $return;
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
                    }
                    else
                    {
                        $info = $info . "L'annulation de la convention " . $teletravail->teletravailid() . " a été enregistrée.<br>";
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" On va envoyer un mail au responsable car on a annulé dans G2T une convention télétravail (id G2T = " . $teletravail->teletravailid() . ")"));
                        $demandeurid = $teletravail->agentid();
                        $demandeur = new agent($dbcon);
                        $demandeur->load($demandeurid);
                        $resp = $demandeur->getsignataire();
                        $cronuser = new agent($dbcon);
                        $cronuser->load(SPECIAL_USER_IDCRONUSER);
                        $cronuser->sendmail($resp,"Annulation/Refus d'une demande de télétravail - " . $demandeur->identitecomplete(), "Une demande de convention de télétravail pour " . $demandeur->identitecomplete() . " a été annulée/refusée.<br>"
                                . "Ceci est un message informatif. Vous n'avez aucune action à réaliser. <br>");
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Le mail au responsable (" . $resp->identitecomplete() . " " . $resp->mail() . ") a été envoyé (id G2T = " . $teletravail->teletravailid() . ")"));
                    }
                }
            }
        }
    }
    
    if (isset($_POST["modification"]))  // On a cliqué sur le bouton "modification" 
    {
        // On va modifier les dates des conventions de télétravail
        foreach ((array)$datedebutconv as $idconv => $datedebut)
        {
            $datefin = $datefinconv[$idconv];
            if (!$fonctions->verifiedate($datedebut) or !$fonctions->verifiedate($datefin))
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "La date de début ou de fin de la convention $idconv n'est pas correcte.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif ($fonctions->formatdatedb($datedebut)>$fonctions->formatdatedb($datefin))
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "La date de début est supérieure à la date de fin de la convention $idconv.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            else
            {
                //echo "La convention $idconv a pour nouvelle date de début $datedebut et pour nouvelle date de fin $datefin <br>";
                $teletravail = new teletravail($dbcon);
                $return = $teletravail->load($idconv);
                if (!$return)
                {
                    if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                    $erreur = $erreur . "Erreur dans le chargement de la convention $idconv pour modification : " . $return;
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                }
                elseif ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE)
                {
                    if ($fonctions->formatdatedb($teletravail->datedebut()) != $fonctions->formatdatedb($datedebut) or $fonctions->formatdatedb($teletravail->datefin()) != $fonctions->formatdatedb($datefin))
                    {
                        $liste = $agent->teletravailliste($datedebut, $datefin);
                        foreach ($liste as $conventionid)
                        {
                            $teletravailverif = new teletravail($dbcon);
                            $teletravailverif->load($conventionid);
                            if ($teletravailverif->statut() == teletravail::TELETRAVAIL_VALIDE and $conventionid != $idconv)
                            {
                                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                                $erreur = $erreur . "Erreur : La date de début ou de fin de la convention de télétravail $idconv chevauche une convention existante (id = $conventionid).";
                                break;  // On a trouver au moins une convention active qui chevauge !
                            }
                        }
                        if ($erreur == '')
                        {
                            $teletravail->datedebut($datedebut);
                            $teletravail->datefin($datefin);
                            $erreur = $teletravail->store();
                            if ($erreur != "")
                            {
                            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                                $erreur = $erreur . "Erreur dans la sauvegarde du changement de date de la convention $idconv : " . $erreur;
                                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                            }
                            else
                            {
                                $info = $info . "La modification de la convention $idconv a été enregistrée.";
                            }
                        }
                    }
                    else
                    {
                        // La date de début et de fin sont les mêmes => On ne fait rien
                    }
                }
            }
        }
    }
    
    if (isset($_POST['genererpdf']))
    {
        //genererpdf[" . $teletravail->teletravailid() . "]'
        foreach ($_POST['genererpdf'] as $teletravailid => $value)
        {
            $teletravail = new teletravail($dbcon);
            $teletravail->load($teletravailid);
            $teletravail->storepdf();
        }
    }
    

    if (isset($_POST["creation"]))  // On a cliqué sur le bouton "creation"
    {
        if (isset($_POST["nbrelignetableauconvention"]))
        {
            $nbrelignetableauconvention = $_POST["nbrelignetableauconvention"];
        }
        
        if (isset($_POST["date_debut"][$agent->agentid()]))
        {
            $datedebutteletravail = $_POST["date_debut"][$agent->agentid()];
        }
        if (isset($_POST["date_fin"][$agent->agentid()]))
        {
            $datefinteletravail = $_POST["date_fin"][$agent->agentid()];
        }
        $jours = null;
        if (isset($_POST["jours"]))
        {
            $jours = $_POST["jours"];
        }

        $demijours = null;
        if (isset($_POST["demijours"]))
        {
            $demijours = $_POST["demijours"];
        }
        
//        $datedebutminconv = date('d/m/Y');
        if ($esignatureactive)
        {
            $datedebutminconv = date('d/m/Y');
        }
        else
        {
            $datedebutminconv = date('d/m/Y',strtotime('-6 month'));
        }
        if (isset($_POST["datedebutminconv"]))
        {
            $datedebutminconv = $_POST["datedebutminconv"];
        }
        
        $datedebutmaxconv = date('d/m/') . (date('Y')+1);
        if (isset($_POST["datedebutmaxconv"]))
        {
            $datedebutmaxconv = $_POST["datedebutmaxconv"];
        }
//        $datefinminconv = date('d/m/Y');
        if ($esignatureactive)
        {
            $datefinminconv = date('d/m/Y');
        }
        else
        {
            $datefinminconv = date('d/m/Y',strtotime('-6 month'));
        }
        if (isset($_POST["datefinminconv"]))
        {
            $datefinminconv = $_POST["datefinminconv"];
        }
        $datefinmaxconv = date('d/m/') . (date('Y')+4);
        if (isset($_POST["datefinmaxconv"]))
        {
            $datefinmaxconv = $_POST["datefinmaxconv"];
        }
        
        // Le nombre maximal de jour de télétravail pour un temps complet est dans $nbjoursmaxteletravail
        $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail;
        if (isset($_POST["nbjoursmaxteletravailcalcule"]))
        {
            $nbjoursmaxteletravailcalcule = $_POST["nbjoursmaxteletravailcalcule"];
        }
        
        if (isset($_POST["typeconv"]))
        {
            $typeconv = $_POST["typeconv"];
        }
        
        //echo "tabteletravail = $tabteletravail <br>";
        if (!is_null($jours) and ($typeconv==teletravail::CODE_CONVENTION_INITIALE or $typeconv==teletravail::CODE_CONVENTION_RENOUVELLEMENT))
        {
            foreach((array)$jours as $numjour) // numjour => [1-7] où 1 = lundi
            {
                $numjour = $numjour - 1;   // $numjour = l'index du talbeau 0 = lundi
                $numjour = $numjour * 2;
                $gauche = substr($tabteletravail,0,$numjour);
                $droite = substr($tabteletravail,$numjour+1);
                $tabteletravail = $gauche . '1' . $droite;
                $numjour = $numjour + 1;   
                $gauche = substr($tabteletravail,0,$numjour);
                $droite = substr($tabteletravail,$numjour+1);
                $tabteletravail = $gauche . '1' . $droite;
            }
        }
        if (!is_null($demijours) and $typeconv==teletravail::CODE_CONVENTION_MEDICAL)
        {
            foreach((array)$demijours as $numdemijour) // numdemijour => [1-10] où 1 = lundi matin, 2 = lundi après-midi, ...
            {
                $numdemijour = $numdemijour - 1;   // $numdemijour = l'index du talbeau 0 = lundi matin
                $tabteletravail = substr_replace($tabteletravail,'1',$numdemijour,1);
            }
        }
        // Si on est en mode RH et que le tableau des jours n'esr pas défini et que le tableau des 1/2 journée est défini alors on prend le tableau des 1/2 journée
        // En fait en mode RH, il n'y a pas de controle. Donc on peut mettre autant de 1/2 journée que l'on veut.
         if ($mode=='gestrh' and is_null($jours) and !is_null($demijours))
        {
            foreach((array)$demijours as $numdemijour) // numdemijour => [1-10] où 1 = lundi matin, 2 = lundi après-midi, ...
            {
                $numdemijour = $numdemijour - 1;   // $numdemijour = l'index du talbeau 0 = lundi matin
                $tabteletravail = substr_replace($tabteletravail,'1',$numdemijour,1);
            }
            $nbjoursmaxteletravailcalcule = count($demijours);
        }
       
        
        //var_dump ("tabteletravail = $tabteletravail <br>");
        $dateok = true;
        
        $teletravailliste = $agent->teletravailliste('01/01/1900', '31/12/2100'); // On va récupérer toutes les demandes de télétravail de l'agent
        if (count($teletravailliste) > $nbrelignetableauconvention)
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "Il y a une incohérence sur la vérification des conventions existantes.";
            $dateok = false;
        }
        
        if ($typeconv . '' == '')
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "Vous n'avez pas sélectionné le type de convention de télétravail.";
            $dateok = false;
        }
        
        if (!$fonctions->verifiedate($datedebutteletravail))
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "La date de début de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if (!$fonctions->verifiedate($datefinteletravail))
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "La date de fin de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if ($dateok and $fonctions->formatdatedb($datedebutteletravail)>$fonctions->formatdatedb($datefinteletravail))
        {
            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
            $erreur = $erreur . "La date de début est supérieure à la date de fin de la convention.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $dateok = false;
        }
        
        if ($dateok)
        {
            if ($fonctions->formatdatedb($datedebutteletravail)<$fonctions->formatdatedb($datedebutminconv)
                or $fonctions->formatdatedb($datedebutteletravail)>$fonctions->formatdatedb($datedebutmaxconv))
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "La date de début de la convention n'est pas dans la période autorisée ($datedebutminconv -> $datedebutmaxconv).";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
            if ($fonctions->formatdatedb($datefinteletravail)<$fonctions->formatdatedb($datefinminconv)
                or $fonctions->formatdatedb($datefinteletravail)>$fonctions->formatdatedb($datefinmaxconv))
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "La date de fin de la convention n'est pas dans la période autorisée ($datefinminconv -> $datefinmaxconv).";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
        }
        
        // On va vérifier que la durée de la convention n'est pas supérieure à la durée maximale
        if ($dateok)
        {
            $dureemax = 0;
            switch ($typeconv)
            {
                case teletravail::CODE_CONVENTION_INITIALE :
                    $dureemax = 1;
                    break;
                case teletravail::CODE_CONVENTION_MEDICAL :
                    $dureemax = 5;
                    break;
                case teletravail::CODE_CONVENTION_RENOUVELLEMENT :
                    $dureemax = 1;
                    break;
            }
            $datedebuttimestamp = strtotime($fonctions->formatdatedb($datedebutteletravail));
            $datefinmaxi = date('Ymd', strtotime('+'.$dureemax.' year', $datedebuttimestamp ));
            // var_dump("datedebutteletravail = $datedebutteletravail");
            // var_dump("datefinmaxi = $datefinmaxi");
            
            
            if ($fonctions->formatdatedb($datefinteletravail)>$datefinmaxi)
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "La durée de la convention est supérieure à la durée maximale autorisée : $dureemax an(s).";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
        }
        
        $fulltabmotifmedical = array('0','0','0','0','0','0','0','0','0','0');
        if ($typeconv==teletravail::CODE_CONVENTION_MEDICAL)
        {
            $motifselected = false;
            for ($cpt=0; $cpt<count($fulltabmotifmedical) ; $cpt++)
            {
                if (isset($motifmedical[$cpt]))
                {
                    $fulltabmotifmedical[$cpt] = '1';
                    $motifselected = true;
                }
            }
            if (!$motifselected)
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "Aucun motif médical n'est sélectionné.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
            //var_dump($fulltabmotifmedical);
        }

        
        if (str_pad('',14,'0') == $tabteletravail)
        {
            if (strlen($erreur)>0) {$erreur = $erreur . '<br>'; }
            $erreur = $erreur . "Aucun jour de télétravail sélectionné.";
        }
        // On regarde si le nombre de 1/2 journée selectionné est conforme au temps partiel/temps complet de l'agent
        // (uniquement dans le cas d'une demande initiale / prolongation "normal"
        elseif ($typeconv==teletravail::CODE_CONVENTION_INITIALE or $typeconv==teletravail::CODE_CONVENTION_RENOUVELLEMENT)
        {
            // On compte le nombre de 1 dans le tableau
            $nbdemiejournee = substr_count($tabteletravail, '1');
                        
            // On divise par 2 le nombre de 1/2 journée pour trouver le nombre de journée
            if (($nbdemiejournee/2) > $nbjoursmaxteletravailcalcule)
            {
                if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                $erreur = $erreur . "Le nombre de jours de télétravail sélectionné dépasse le maximum autorisé.";
            }
        }

        //////////////////////////////////////////////////////////////
        if ($erreur=='')
        {
            $affectation = null;
            $affectationliste = $agent->affectationliste($datedebutteletravail, $datefinteletravail, true);
            if (count((array)$affectationliste)==0)
            {
                $erreur = $erreur . "Vous n'avez pas d'affectation entre le $datedebutteletravail et le $datefinteletravail. Impossible de déclarer une convention de télétravail.";
                $nbjoursmaxteletravailcalcule = 0;
                $disablesubmit = true;
            }
            else
            {
                $affectation = reset($affectationliste);
                $nbjoursmaxteletravailcalcule = $_POST["nbjoursmaxteletravailcalcule"];
                if ($nbjoursmaxteletravailcalcule == 0)
                {
                    $disablesubmit = true;
                }

                $declarationliste = $affectation->declarationTPliste($datedebutteletravail, $datefinteletravail);
                $declaration = null;
                if (! is_null($declarationliste)) 
                {
                    foreach ($declarationliste as $declaration)
                    {
                        if (strcasecmp((string)$declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0) 
                        {
                            // La première déclaration de TP non refusée qu'on trouve est la bonne
                            break;
                        }
                        $declaration = null;
                    }
                }

                if (is_null($declaration) and $agentstructure->estbibliotheque())
                {
                    $declaration = new declarationTP($dbcon);
                    $declaration->agentid($agent->agentid());
                    $declaration->tabtpspartiel(str_repeat("0", 20));
                    $declaration->statut(declarationTP::DECLARATIONTP_VALIDE);
                }

                // A ce niveau $declaration est soit NULL soit il vaut la declaration de TP active
                if (is_null($declaration) or $mode=='gestrh')
                {
                    if (is_null($declaration) and $mode!='gestrh')
                    {
                        $erreur = $erreur . "Vous n'avez pas de déclaration de temps partiel active entre le $datedebutteletravail et le $datefinteletravail.<br>Impossible de saisir une convention de télétravail";
                    }
                    elseif (is_null($declaration) and $mode=='gestrh')
                    {
                        $erreur = $erreur . "L'agent " . $agent->identitecomplete() . " pas de déclaration de temps partiel active entre le $datedebutteletravail et le $datefinteletravail.";
                    }
                }
            }
        }
        //////////////////////////////////////////////////////////////////////////////


        if ($dateok and $erreur=='')
        {
            $listeconventionchevauche = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
            foreach ($listeconventionchevauche as $keyconv => $conventionid)
            {
                $teletravailchevauche = new teletravail($dbcon);
                $teletravailchevauche->load($conventionid);
                if ($teletravailchevauche->statut()==teletravail::TELETRAVAIL_ANNULE or $teletravailchevauche->statut()==teletravail::TELETRAVAIL_REFUSE)
                {
                    unset ($listeconventionchevauche[$keyconv]);
                }
            }
            //var_dump($listeconventionchevauche);
            if (count($listeconventionchevauche)>0)
            {
                $alerte = $alerte . "Attention : Plusieurs conventions se chevauchent. Les dates seront automatiquement adapatées à la fin du circuit de validation si nécessaire.";
            }
        }

        if ($erreur == '')
        {
            $teletravail = new teletravail($dbcon);
            $teletravail->datedebut($datedebutteletravail);
            $teletravail->datefin($datefinteletravail);
            $teletravail->tabteletravail($tabteletravail);
            $teletravail->agentid($agent->agentid());
            $teletravail->typeconvention($typeconv);
            $teletravail->motifmedicalsante($fulltabmotifmedical[teletravail::MOTIF_MEDICAL_SANTE]);
            $teletravail->motifmedicalgrossesse($fulltabmotifmedical[teletravail::MOTIF_MEDICAL_GROSSESSE]);
            $teletravail->motifmedicalaidant($fulltabmotifmedical[teletravail::MOTIF_MEDICAL_AIDANT]);
 
            $teletravail->esignatureurl('');
            if ($esignatureactive)
            {
                //echo "eSignature est actif <br>";
                $teletravail->statut(teletravail::TELETRAVAIL_ATTENTE);
                $teletravail->statutresponsable(teletravail::TELETRAVAIL_ATTENTE);
            }
            else
            {
                //echo "eSignature est inactif <br>";
                $teletravail->statut(teletravail::TELETRAVAIL_VALIDE);
                $teletravail->statutresponsable('');  // A voir si on remplace par teletravail::TELETRAVAIL_VALIDE
            }
            $erreur = $teletravail->store();
            if ($erreur <> "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur (création) = " . $erreur ));
            }
            elseif($esignatureactive)
            {
                $agent->synchroteletravail();
                $responsable = $agent->getsignataire();
                if (!is_null($responsable) and $responsable!==false)
                {
                    $cron = new agent($dbcon);
                    $cron->load(SPECIAL_USER_IDCRONUSER);
                    $cron->sendmail($responsable,"Demande de télétravail - " . $agent->identitecomplete(),"Une demande de télétravail vient d'être réalisée pour " . $agent->identitecomplete() . "
Vous pouvez la compléter et valider/refuser la demande via le menu 'Responsable' ou 'Gestionnaire' de l'application G2T.\n");
                }
                else
                {
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Le responsable est null ou false => Pas d'envoi de mail au responsable de l'agent"));
                }
                $info = "La création de la convention est réussie.";
                $erreur = "";
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $info => Id G2T = " . $teletravail->teletravailid() ));
            }
        }
    }
    
    if (isset($_POST['but_resp_statut']))
    {
        if (trim($activitetele) == "" and $statutresp==teletravail::TELETRAVAIL_VALIDE)
        {
            $erreur = $erreur . "La description des activités télétravaillables est obligatoire";
            error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
        }
        elseif ($motifrefus == "" and $statutresp==teletravail::TELETRAVAIL_REFUSE)
        {
            $erreur = $erreur . "Le motif du refus est obligatoire";
            error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
        }
        elseif ($idconvention . '' == '')
        {
            // Aucune convention n'est saisie alors qu'on a appuyé sur le bouton de soumission => On ne fait rien
        }
        else
        {
            $teletravail = new teletravail($dbcon);
            $teletravail->load($idconvention);
            
            if ($teletravail->statutresponsable() == $statutresp and trim($statutresp) != '') // Sans doute un cas de re-post de la page
            {
                $erreur = $erreur . "La convention de télétravail a déjà été " . strtolower($fonctions->teletravailstatutlibelle($teletravail->statutresponsable())) ;
                error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                // On reset toutes les variables car la convention n'est plus disponible
                $periodeexclusion = "";
                $periodeadaptation = "";
                $motifrefus = "";
                $idconvention = "";
                $statutresp = "";
            }
            else
            {
                $teletravail->statutresponsable($statutresp);

                $agent = new agent($dbcon);
                $agent->load($teletravail->agentid());

                if ($statutresp==teletravail::TELETRAVAIL_VALIDE)
                {
                    $teletravail->periodeexclusion($periodeexclusion);
                    $teletravail->periodeadaptation($periodeadaptation);
                    $teletravail->activiteteletravail($activitetele);
                }
                elseif ($statutresp==teletravail::TELETRAVAIL_REFUSE)
                {
                    $teletravail->commentaire($motifrefus);
                    $teletravail->statut($statutresp);
                    $erreur = $teletravail->store();

                    $cron = new agent($dbcon);
                    $cron->load(SPECIAL_USER_IDCRONUSER);
                    $cron->sendmail($agent,"Changement de statut de votre demande de télétravail","Votre demande de télétravail a été " . strtolower($fonctions->teletravailstatutlibelle($teletravail->statut())) . " par " . $user->identitecomplete() . " au motif suivant :\n\n
    $motifrefus \n");
                }
                else
                {
                    $erreur = $erreur . "Le statut responsable de la demande de télétravail est inconnu ou n'a pas été sélectionné " . $statutresp;
                    error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                }
                if (trim($erreur)=="" and $statutresp==teletravail::TELETRAVAIL_VALIDE)
                {

                    /////////////////////////////////////////////////////
                    //// DEBUT SAUVETGARDE ESIGNATURE     ///////////////
                    /////////////////////////////////////////////////////

                    $agent_eppn = $agent->eppn();

                    // On récupère le mail de l'agent en cours
                    $agent_mail = $agent->mail(); // $agent->ldapmail();

    /*                
                    if (!is_null($agentid))
                    {
                        // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
                        $agent = new agent($dbcon);
                        $agent->load($agentid);
                        $agent_eppn = $agent->eppn();

                        // On récupère le mail de l'agent en cours
                        $agent_mail = $agent->mail(); // $agent->ldapmail();
                    }
    */
                    $eSignature_url = trim($fonctions->liredbconstante('ESIGNATUREURL'));
                    $full_g2t_ws_url = trim($fonctions->get_g2t_ws_url()) . "/teletravailWS.php";
                    $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);

                    $curl = curl_init();
                    // ----------------------------------------------------------------
                    // On force l'EPPN avec le compte système de eSignature
                    $agent_eppn = 'system';
                    //-----------------------------------------------------------------

                    $params = array
                    (
                        'eppn' => "$agent_eppn",
                        'targetEmails' => array
                        (
                            "$agent_mail"
                        ),
                        'targetUrl' => "$full_g2t_ws_url",
                        'targetUrls' => array("$full_g2t_ws_url"),
                        'formDatas' => "{}",
                        'title' => "Convention de télétravail de " . $agent->prenom() . " " . $agent->nom()
                    );

                    $taberrorcheckmail = array();
                    if ($esignatureactive)
                    {
                        //echo "Avant la vérification du circuit => numéro 2 <br>";
                        $taberrorcheckmail = $fonctions->checksignataireteletravailliste($params,$agent,$maxniveau);
                    }
                    if (count($taberrorcheckmail) > 0)
                    {
                        // var_dump("errorcheckmail = $errorcheckmail");
                        $errorcheckmailstr = '';
                        foreach ($taberrorcheckmail as $errorcheckmail)
                        {
                            if (strlen($errorcheckmailstr)>0) { $errorcheckmailstr = $errorcheckmailstr . '<br>'; }
                            $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
                        }
                        $erreur = "Impossible d'enregistrer la convention de télétravail car <br>$errorcheckmailstr";
                    }
                    else
                    {
                        $id_model = '';
                        if ($esignatureactive)
                        {
                            $id_model = trim($fonctions->getidmodelteletravail($maxniveau, $agent));
                        }
                        if (trim($id_model) == '' and $esignatureactive)
                        {
                            if (strlen($erreur)>0) { $erreur = $erreur . '<br>'; }
                            $erreur = $erreur . "Le modèle eSignature pour la création d'une convention télétravail n'a pas pu être déterminé.";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                        }
                        else
                        {
                            $walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk )
                            {
                                is_array( $item )
                                ? array_walk( $item, $walk, $key )
                                : $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
                            };
                            array_walk( $params, $walk );
                            $params_string = implode( '&', $output );
                            // var_dump ("Output = " . $params_string);

                            $opts = [
                                CURLOPT_URL => trim($eSignature_url) . '/ws/forms/' . trim($id_model)  . '/new',
                                CURLOPT_POST => true,
                                CURLOPT_POSTFIELDS => $params_string,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_SSL_VERIFYPEER => false
                            ];
                            curl_setopt_array($curl, $opts);
                            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                            $json = "";
                            $error = "";
                            if ($esignatureactive)
                            {
                                $json = curl_exec($curl);
                                $error = curl_error ($curl);
                            }
                            curl_close($curl);
                            if ($error != "")
                            {
                                echo $fonctions->showmessage(fonctions::MSGERROR, "Erreur Curl = " . $error);
                            }
                            //echo "<br>" . print_r($json,true) . "<br>";
                            //echo "<br>"; var_dump($json); echo "<br>";
                            if ($esignatureactive)
                            {
                                $id = json_decode($json, true);
                            }
                            else
                            {
                                $id = '';
                            }
                            error_log(basename(__FILE__) . " " . var_export($opts, true));
                            error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE CREATION CONVENTION -- " . var_export($id, true));
                            //var_dump($id);
                            if (is_array($id) and $esignatureactive)
                            {
                                $erreur = "La création de la convention dans eSignature a échoué => " . print_r($id,true);
                                error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                            }
                            elseif ("$id" < 0 and $esignatureactive)
                            {
                                $erreur =  "La création de la convention dans eSignature a échoué (numéro demande eSignature négatif = $id) !!==> Pas de sauvegarde de la demande de télétravail dans G2T.";
                                error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                            }
                            elseif ("$id" <> "" or !$esignatureactive)
                            {
                                //echo "Id de la nouvelle demande = " . $id . "<br>";
                                $teletravail->esignatureid($id);
                                if ($esignatureactive)
                                {
                                    $teletravail->esignatureurl($eSignature_url . "/user/signrequests/".$id);
                                    $teletravail->creationesignature(date('Ymd'));
                                }
                                else
                                {
                                    $teletravail->esignatureurl(''); 
                                }
                                $erreur = $teletravail->store();
                                $agent->synchroteletravail();
                                if ($erreur <> "")
                                {
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur (création) = " . $erreur ));
                                }
                                else
                                {
                                    $info = "La création de la convention est réussie.";
                                    $erreur = "";
                                    error_log(basename(__FILE__) . $fonctions->stripAccents(" $info => eSignatureid = " . $id ));
                                }
                            }
                            else
                            {
                                $erreur  = "La création de la convention de télétravail dans eSignature a échoué !!==> Pas de sauvegarde de la conention télétravail dans G2T.";
                                error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                            }
                        }
                    }
                    /////////////////////////////////////////////////////
                    //// FIN SAUVETGARDE ESIGNATURE       ///////////////
                    /////////////////////////////////////////////////////
                }
            }
        }
        // Si tout s'est bien passé, on réinitialise les valeurs
        if (trim($erreur)=='')
        {
            $periodeexclusion = "";
            $periodeadaptation = "";
            $motifrefus = "";
            $idconvention = "";
            $statutresp = "";
        }
    }
    
    $inputtypeconv = null;
    $inputdatedebut = null;
    $inputdatefin = null;
    $inputtabteletravail = null;
    $inputmotifmedical = null;
    $teletravailenattente = false;
    
    
    if ($erreur != "")
    {
        if (isset($teletravail))
        {
            $erreur = $erreur . "<br>La convention de télétravail n'a pas pu être enregistrée.";
        }
        echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
        $inputtypeconv = $typeconv;
        $inputdatedebut = $datedebutteletravail;
        $inputdatefin = $datefinteletravail;
        $inputtabteletravail = $tabteletravail;
        $inputmotifmedical = $motifmedical;
    }
    if ($alerte != "")
    {
        echo $fonctions->showmessage(fonctions::MSGWARNING, $alerte);
    }
    if ($info != "")
    {
        echo $fonctions->showmessage(fonctions::MSGINFO, $info);
    }
    echo "<br><br>";

    if (is_null($agentid) and ($mode!='resp' and $mode!='gestion'))
    {
        //echo "<form name='demandeforagent'  method='post' action='gestion_teletravail.php'>";
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentteletravail'  method='post' >";

        $agentsliste = $fonctions->listeagentsg2t();
        echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
        echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
        foreach ($agentsliste as $key => $identite)
        {
            echo "<option value='$key'>$identite</option>";
        }
        echo "</select>";
        
/*        
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='' class='agent' /> ";
?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
 <?php
 */
        echo "<br>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' id='noesignature'  name='noesignature' value='" . $noesignature . "'>";        
        echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
    }
    elseif (!is_null($agentid) and ($mode!='resp' and $mode!='gestion'))
    {
        $erreur = '';
        if ($esignatureactive)
        {
            $erreur = $agent->synchroteletravail();
        }
    	if ($erreur != "")
    	{
    	    echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible de synchroniser une ou plusieurs convention : $erreur");
    	    $disablesubmit = true;
    	}
        if ($mode!='gestrh' and !$esignatureactive)
        {
     	    $disablesubmit = true;
        }
    	$teletravailliste = $agent->teletravailliste('01/01/1900', '31/12/2100'); // On va récupérer toutes les demandes de télétravail de l'agent pour les afficher
    	if (count($teletravailliste) > 0)
    	{
            $displayPDFbutton = false;
            if ($mode=='gestrh')
            {
                $displayPDFbutton = true;
            }            
            
    	    $nbrelignetableauconvention=count($teletravailliste);
    	    echo "<form name='form_teletravail_delete' id='form_teletravail_delete' method='post' >";
    	    echo "<table class='tableausimple' id='listeteletravail'>";
    	    echo "<tr><center><td class='titresimple'>Identifiant</td>
                      <td class='titresimple'>Date début</td>
                      <td class='titresimple'>Date fin</td>
                      <td class='titresimple' id ='convstatut'>Statut</td>
                      <td class='titresimple'>Répartition du télétravail</td>";
            if ($esignatureactive)
            {
                echo "
                      <td class='titresimple'>Id. externe</td>
                      <td class='titresimple'>URL eSignature</td>
                 ";
            }
            if ($mode=='gestrh' or $esignatureactive)
            {
                echo "<td class='titresimple'>Annuler</td>";
            }
            if ($displayPDFbutton and $esignatureactive)
            {
                echo "<td class='titresimple'>Générer le PDF</td>";
            }
            echo "</center></tr>";
    	    foreach($teletravailliste as $teletravailid)
    	    {
    	        $teletravail = new teletravail($dbcon);
    	        $teletravail->load($teletravailid);
    	        $datedebutteletravail = $fonctions->formatdate($teletravail->datedebut());
    	        $datefinteletravail = $fonctions->formatdate($teletravail->datefin());
    	        $calendrierid_deb = "date_debut_conv";
    	        $calendrierid_fin = "date_fin_conv";
                
                if ($teletravail->statut()==teletravail::TELETRAVAIL_ATTENTE)
                {
                    $teletravailenattente = true;
                }
    	            	        
    	        $extraclass = "";
    	        $openspan = "";
    	        $closespan = "";
    	        $listeconventionchevauche = array();
    	        // Si la convention est déjà annulée ou refusée, elle ne peut pas chevaucher une autre convention
    	        if ($teletravail->statut()!=teletravail::TELETRAVAIL_ANNULE and $teletravail->statut()!=teletravail::TELETRAVAIL_REFUSE)
    	        {
        	        $listeconventionchevauche = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
        	        foreach ($listeconventionchevauche as $keyconv => $conventionid)
        	        {
        	            $teletravailchevauche = new teletravail($dbcon);
        	            $teletravailchevauche->load($conventionid);
        	            if ($teletravailchevauche->statut()==teletravail::TELETRAVAIL_ANNULE or $teletravailchevauche->statut()==teletravail::TELETRAVAIL_REFUSE)
        	            {
        	                // var_dump ("La convention $conventionid est annulée => je l'enlève de la liste");
        	                unset ($listeconventionchevauche[$keyconv]);
        	            }
        	        }
        	        if (count($listeconventionchevauche)>1) // On a forcément la convention actuelle dans la liste => On teste s'il y en a plus d'une
        	        {
        	            $openspan = "<span data-tip=" . chr(34) . "La convention chevauche au moins une autre convetion." . chr(34) . ">";
        	            $closespan = "</span>";
        	            $extraclass = ' celwarning resetfont ';
        	        }
    	        }
    	        //echo "<tr><td class='cellulesimple'>" . $teletravail->teletravailid() . "</td><td class='cellulesimple'><input type='text' name='debut[]' value='" . $fonctions->formatdate($teletravail->datedebut()) . "'></td><td class='cellulesimple'><input type='text' name='fin[]' value='" . $fonctions->formatdate($teletravail->datefin()) . "'></td><td class='cellulesimple'>" . $teletravail->statut() . "</td><td class='cellulesimple'><button type='submit' value='" . $teletravail->teletravailid() ."' name='cancel[]' " . (($teletravail->statut() == teletravail::TELETRAVAIL_ANNULE) ? "disabled='disabled' ":" ") . ">Annuler</button>" . "</td></tr>";
    	        echo "<tr><td class='cellulesimple $extraclass'> $openspan <center>" . $teletravail->teletravailid() . "</center> $closespan </td>";
    	        //echo "    <td class='cellulesimple'><center>" . $fonctions->formatdate($teletravail->datedebut()) . "</center></td>";
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').change(function () {
        		$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker("destroy");
        		$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("maxperiode")});
 
	       	$('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').change(function () {
       			$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker("destroy");
       			$('[id="<?php echo $calendrierid_deb . '[' . $teletravailid . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $teletravailid . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php     	        
    	        echo "    <td class='cellulesimple'><center>";
    	        if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE and $mode=='gestrh')
    	        {
                    // On peut modifier la date de début de la convention dans une période de 6 mois avant la date saisie
                    $datedebutminconv_tab = date("d/m/Y", strtotime("-6 month", strtotime($fonctions->formatdatedb($datedebutteletravail))));
                    
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $teletravailid . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $teletravailid .']'?> size=10
        	minperiode='<?php echo $datedebutminconv_tab; ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datedebutteletravail ?>'>
<?php
    	        }
    	        else
    	        {
    	            echo $fonctions->formatdate($teletravail->datedebut());
    	        }
    	        echo "</center></td>";
    	        echo "    <td class='cellulesimple'><center>";
    	        if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE and $mode=='gestrh')
    	        {
//                    $datefinminconv_tab = date("d/m/Y", strtotime("-6 month", strtotime($fonctions->formatdatedb($datefinteletravail))));
                    $datefinminconv_tab = $datedebutminconv_tab;
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	size=10
        	minperiode='<?php echo $datefinminconv_tab; ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+4 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datefinteletravail ?>'>
<?php 
    	        }
    	        else
    	        {
    	            echo $fonctions->formatdate($teletravail->datefin());
    	        }
                echo "</center></td>";
                $openspan = "";
                $closespan = "";
                if (strlen($teletravail->commentaire().'') != 0)
                {
                    $openspan = "<span data-tip=" . chr(34) . htmlentities($teletravail->commentaire()) . chr(34) . ">";
                    $closespan = "</span>";
                }
                echo "    <td class='cellulesimple convstatut' ><span class='convstatutvalue' hidden>" .  $teletravail->statut() . "</span><center>$openspan" . $fonctions->teletravailstatutlibelle($teletravail->statut()) . "$closespan</center></td>";
    	        echo "    <td class='cellulesimple'><center>";
                $htmltext = $teletravail->libelletabteletravail();
                $motifmedical = '';
                if ($teletravail->typeconvention()==teletravail::CODE_CONVENTION_MEDICAL)
                {
                    if (intval($teletravail->motifmedicalsante())>0)
                    {
                        $motifmedical = $motifmedical . "Raison de santé,";
                    }
                    if (intval($teletravail->motifmedicalgrossesse())>0)
                    {
                        $motifmedical = $motifmedical . " Grossesse,";
                    }
                    if (intval($teletravail->motifmedicalaidant())>0)
                    {
                        $motifmedical = $motifmedical . " Proche aidant,";
                    }                            
                }
                echo "<span data-tip=" . chr(34) . htmlentities($teletravail->libelletypeconvention());
                if (strlen(trim($motifmedical))>0)
                {
                    echo " (" . substr(trim($motifmedical),0,strlen($motifmedical)-1) . ")";
                }
                echo chr(34) . ">";
    	        echo $htmltext;
                echo " </span>";
    	        echo "    </center></td>";
                
                if ($esignatureactive)
                {
        	        echo "<td class='cellulesimple'>" . $teletravail->esignatureid() . "</td>";
        	        echo "<td class='cellulesimple'><a href='" . $teletravail->esignatureurl() . "' target='_blank'>".(($teletravail->statut() == teletravail::TELETRAVAIL_ANNULE) ? '':$teletravail->esignatureurl())."</a></td>";
                }
                if ($mode=='gestrh' or $esignatureactive)
                {
//                    echo "<td class='cellulesimple'><center><input type='checkbox' value='" . $teletravail->teletravailid() .  "' id='" . $teletravail->teletravailid()  .  "' name='cancel[]' ";
                    echo "<td class='cellulesimple'><center><button type='submit' value='" . $teletravail->teletravailid() .  "' id='" . $teletravail->teletravailid()  .  "' name='cancel[]' class='cancel g2tbouton g2tsupprbouton' ";
                    if ($mode=='gestrh' and in_array($teletravail->statut(), array(teletravail::TELETRAVAIL_ANNULE,teletravail::TELETRAVAIL_REFUSE))) // and $teletravail->statut()==teletravail::TELETRAVAIL_ANNULE)
                    {
                        echo " disabled='disabled' ";
                    }
                    elseif ($mode!='gestrh' and in_array($teletravail->statut(), array(teletravail::TELETRAVAIL_ANNULE,teletravail::TELETRAVAIL_REFUSE, teletravail::TELETRAVAIL_VALIDE)))
                    {
                        echo " disabled='disabled' ";
                    }
                    echo " onclick='if (this.tagname!=\"OK\") {click_element(\"" . $teletravail->teletravailid()  .  "\"); return false; }'>Annuler</button";
                    echo "></center></td>";
                }
                if ($displayPDFbutton and $esignatureactive)
                {
                    echo "<td class='cellulesimple'><center><input type='submit' value='Générer' name='genererpdf[" . $teletravail->teletravailid() . "]' class='g2tbouton g2tdocumentbouton'";
                    if (trim($teletravail->esignatureid())=='' or trim($teletravail->esignatureurl())=='' or $teletravail->statut() == teletravail::TELETRAVAIL_ANNULE)
                    {
                        echo " disabled='disabled' ";
                    }
                    echo "></center></td>";
                }
                echo "</tr>";
    	    }
            echo "</table>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    	    echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
    	    echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
            echo "<input type='hidden' id='noesignature'  name='noesignature' value='" . $noesignature . "'>";
    	    if (!$disablesubmit and ($mode != ''))
    	    {
    	       echo "<input type='submit' id='modification' name='modification' class='g2tbouton g2tvalidebouton g2tboutonwidthauto' value='Enr. modif. date' onclick='if (this.tagname!=\"OK\") {click_element(\"modification\"); return false; }'/>";
    	    }
    	    echo "</form>";

?>
        <script>
            var confirmdialog = document.getElementById('confirmdialog');
            /*
            var confirmBtn = document.getElementById('questionconfirmBtn');
            var labeltext = document.getElementById('questionlabeltext');
            var cancelBtn = document.getElementById('questioncancelBtn');        
            */
            var confirmBtn = confirmdialog.querySelector('#questionconfirmBtn');
            var labeltext = confirmdialog.querySelector('#questionlabeltext');
            var cancelBtn = confirmdialog.querySelector('#questioncancelBtn');        
           
            confirmdialog.addEventListener('close', function onClose() {
                if (confirmdialog.returnValue!=='cancel')
                {
                    // L'id du boutton en cours est dans la propertie value du bouton confirm
                    var submit_button = document.getElementById(confirmBtn.value);
                    submit_button.tagname = 'OK';
                    submit_button.click();
                }
            });

            var click_element = function(elementid)
            {
                if (typeof confirmdialog.showModal === "function") {
                    var submit_button = document.getElementById(elementid);
                    if (submit_button.classList.contains("cancelbutton"))
                    {
                        labeltext.innerHTML = 'Confirmez-vous l\'envoie de la requête d\'annulation pour cette demande auprès du responsable ?';
                    }
                    else if (submit_button.classList.contains("cancel"))
                    {
                        labeltext.innerHTML = 'Confirmez-vous l\'annulation de cette demande ? ';
                    }
                    else
                    {
                        labeltext.innerHTML = 'Confirmez-vous les modifications des dates ? ';
                    }
                    cancelBtn.textContent = "Non";
                    cancelBtn.hidden = false;
                    confirmBtn.textContent = "Oui";
                    confirmBtn.hidden = false;
                    confirmBtn.value = elementid;
                    confirmdialog.showModal();
                }        
                else {
                    console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
                }
            };
        </script>
<?php




            echo "<br>";
            echo "<input type='checkbox' id='hide' name='hide' onclick='hide_inactive();'>Masquer les conventions non validées</input><br>";
?>
	<script>
		function hide_inactive()
		{
			//alert ("Plouf !");
		    var tableau = document.getElementById('listeteletravail');
		    //alert (tableau.id);
		    for (var i = 1; i < tableau.querySelectorAll('tr').length; i++)
		    {
		        //alert(i);
		        var currenttr = tableau.querySelectorAll('tr')[i];

		        //alert(currenttr.innerHTML);
		        var statutcase = currenttr.getElementsByClassName('convstatut')[0]; //getElementById('convstatut');
		        //alert (statutcase.innerHTML);
		        
		        var spanstatut = statutcase.getElementsByClassName('convstatutvalue')[0];
		        //alert (spanstatut.innerText);
		        
		        var tabstatut = ['<?php echo teletravail::TELETRAVAIL_ANNULE ?>','<?php echo teletravail::TELETRAVAIL_ATTENTE ?>','<?php echo teletravail::TELETRAVAIL_REFUSE ?>'];
		        if (tabstatut.indexOf(spanstatut.innerText) !== -1) // La valeur existe dans le tableau
		        //if (statutcase.innerText == '<?php echo teletravail::TELETRAVAIL_ANNULE ?>')
		        {
    		        var checkboxvalue = document.getElementById('hide').checked;
    		        if (checkboxvalue)
    		        {
    		        	//alert ('on masque.');
    		        	currenttr.style.display = "none";
    		        }
    		        else
    		        {
    		        	//alert ('on affiche.');
    		        	currenttr.style.display = "table-row";
    		        }
		        }
		    }
		}
		//document.getElementById('hide').click();
	</script>
<?php
    	}
    	else
    	{
    	    $nbrelignetableauconvention=0;
    	    echo "<br>Pas de convention de télétravail saisie dans l'application<br>"; 
    	}
    	
    	$calendrierid_deb = "date_debut";
    	$calendrierid_fin = "date_fin";
?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	});
        });
    </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[' . $agent->agentid() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $agent->agentid() . "]" ?>"]').datepicker("getDate")});
        	});
        });
    </script>
<?php
        $formhidden = "";
        $formdisabled = "";
        
    	echo "<br><HR id='separateurHR' class='barreseparation'><br>";
?>
    <script>
        var tabteletravail = document.getElementById('listeteletravail');
        if (tabteletravail)
        {
            var HRelement = document.getElementById('separateurHR');
            if (HRelement)
            {
                HRelement.style.width = tabteletravail.offsetWidth+'px';
                HRelement.style.visibility = "visible";
            }
        }
    </script>
<?php

        //echo "<br>inputtypeconv = $inputtypeconv <br>";
        if ($teletravailenattente)
        {
            $formhidden = "";
            $formdisabled = " disabled='disabled' ";
        }

        if (!$esignatureactive and $mode!='gestrh')
        {
            $formhidden = " hidden='hidden' ";
            $formdisabled = " disabled='disabled' ";
        }
    	echo "<form name='form_teletravail_creation' id='form_teletravail_creation' method='post' $formhidden $formdisabled>";
    	echo "Création d'une nouvelle demande de convention pour : " . $agent->identitecomplete()  . " <br>";    	
        if ($teletravailenattente)
        {
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande de télétravail en attente de validation.<br>Vous ne pouvez pas en saisir une nouvelle.<br>Vous devez attendre que le circuit de validation soit terminé ou annuler la demande en attente.");
        }
    	echo "Type de demande de convention télétravail : ";
    	echo "<select required id='typeconv' name='typeconv' onChange='displayhidewarning(this.value);'>";
    	echo "<option value=''>---- Sélectionnez le type de convention ----</option>";

        $demandeinitiale = true;
    	if (count($teletravailliste)>0)
    	{
            // On regarde s'il existe une demande déjà validée ou en attente de validation ==> Si oui, ce n'est pas une demande initiale
            foreach($teletravailliste as $teletravailid)
            {
    	        $teletravail = new teletravail($dbcon);
    	        $teletravail->load($teletravailid);
                if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE or $teletravail->statut() == teletravail::TELETRAVAIL_ATTENTE)
                {
                    $demandeinitiale = false;
                    break;
                }
            }
        }
        if ($demandeinitiale)
        {
            echo "<option value='" . teletravail::CODE_CONVENTION_INITIALE . "'";
            if ($inputtypeconv == teletravail::CODE_CONVENTION_INITIALE) { echo " selected "; }
            echo ">" . teletravail::TYPE_CONVENTION_INITIALE . "</option>";
    	}
    	else
    	{
    	    echo "<option value='" . teletravail::CODE_CONVENTION_RENOUVELLEMENT . "'";
            if ($inputtypeconv == teletravail::CODE_CONVENTION_RENOUVELLEMENT) { echo " selected "; }
            echo ">" . teletravail::TYPE_CONVENTION_RENOUVELLEMENT . "</option>";
    	}
    	
    	echo "<option value='" . teletravail::CODE_CONVENTION_MEDICAL . "'";
        if ($inputtypeconv == teletravail::CODE_CONVENTION_MEDICAL) { echo " selected "; }
    	echo ">" . teletravail::TYPE_CONVENTION_MEDICAL . "</option>";
    	
    	echo "</select>";
?>
<script>
    function displayhidewarning(valeur)
    {
/*
        const warningtext = document.getElementById('warningmedical');
        const tabttnormal = document.getElementById('tabttnormal');
        const tabttmedical = document.getElementById('tabttmedical');
        const labelmaxjrs = document.getElementById('labelmaxjrs');
        const labeljrsmedical = document.getElementById('labeljrsmedical');
 */
        var divttnormal = document.getElementById('divttnormal');
        var divttmedical = document.getElementById('divttmedical');
        
        if (divttnormal && divttmedical)
        {
            if (valeur=='<?php echo teletravail::CODE_CONVENTION_MEDICAL ?>')
            {
                divttnormal.hidden = true;
/*                
                warningtext.hidden = false;
                tabttnormal.hidden = true;
 */
            }
            else
            {
                divttnormal.hidden = false;
/*
                warningtext.hidden = true;
                tabttnormal.hidden = false;
 */
            }
            divttmedical.hidden = !divttnormal.hidden;
/*
            tabttmedical.hidden = !tabttnormal.hidden;
            labelmaxjrs.hidden = tabttnormal.hidden;
            labeljrsmedical.hidden = tabttmedical.hidden;
 */
        }
    }


    function dateIsValid(dateStr) {
        const regex = /^\d{2}\/\d{2}\/\d{4}$/;

        if (dateStr.match(regex) === null) {
            return false;
        }

        const [day, month, year] = dateStr.split('/');
        const isoFormattedStr = `${year}-${month}-${day}`;
        const date = new Date(isoFormattedStr);
        const timestamp = date.getTime();

        if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
            return false;
        }
        return date.toISOString().startsWith(isoFormattedStr);
    }



    function getnbjrsteletravail(id)
    {
        //console.log ('Activation de getnbjrsteletravail');
        var calendrierdeb = document.getElementById(id);
        
        if (calendrierdeb && dateIsValid(calendrierdeb.value))
        {
            let nbjoursmaxteletravailcalcule = document.getElementById('nbjoursmaxteletravailcalcule');
            
            const [day, month, year] = calendrierdeb.value.split('/');
            var keydate = year + "" + month + "" + day;
            var valuetodisplay = 0;
            for (const [key, value] of Object.entries(tabaffectation))
            {
                const [debut, fin] = key.split('-');
                // keydate => date saisie dans le calendrier
                if (debut <= keydate && keydate <= fin)
                {
                    console.log ('debut = ' + debut + '  fin = ' + fin +  '   keydate = ' + keydate + '   value = ' + value);
                    valuetodisplay = value;
                }
            }
            if (nbjoursmaxteletravailcalcule)
            {
                nbjoursmaxteletravailcalcule.value = valuetodisplay;
                // nbjoursmaxteletravailcalcule.innerHTML = valuetodisplay;
                verif_nbre_checkbox();
                let creationbtn = document.getElementById('creation');
                if (creationbtn && valuetodisplay > 0)
                {
                    //creationbtn.style.display = 'block' ;
                    creationbtn.hidden = false;
                }
            }
        }
    }

</script>    
<?php
    	echo "<br>";
    	
        if ($esignatureactive)
        {
        	$datedebutminconv = date('d/m/Y');
        }
        else
        {
        	$datedebutminconv = date('d/m/Y',strtotime('-6 month'));
        }
    	$datedebutmaxconv = date('d/m/') . (date('Y')+1);
        if ($esignatureactive)
        {
        	$datefinminconv = date('d/m/Y');
        }
        else
        {
        	$datefinminconv = date('d/m/Y',strtotime('-6 month'));
        }
    	$datefinmaxconv = date('d/m/') . (date('Y')+2);

        echo "<table>";
        echo "<tr>";
    	echo "<td>Date de début : </td>";
        echo "<td><span class='largerfontsize' data-tip=" . chr(34) . htmlentities("Ceci est la date de début souhaitée. La date d'effet de la convention ne pourra être antérieure à la date de signature de tous les intervenants.") . chr(34) . "> &#9432; </span></td>";
    	if ($fonctions->verifiedate($inputdatedebut)) 
        {
    	    $inputdatedebut = $fonctions->formatdate($inputdatedebut);
    	}
        echo "<td>";
    ?>
        <input required class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $agent->agentid() . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $agent->agentid() .']'?> size=10
        	minperiode='<?php echo $fonctions->formatdate($datedebutminconv); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($datedebutmaxconv); ?>'
        	value='<?php echo $inputdatedebut ?>'
                onchange="getnbjrsteletravail('<?php echo $calendrierid_deb . '[' . $agent->agentid() .']'?>');">
    <?php
        echo "</td>";
    	echo "</tr>";
        echo "<tr>";
        echo "<td colspan=2>";
    	echo "Date de fin : ";
        echo "</td>";
    	if ($fonctions->verifiedate($inputdatefin)) {
    	    $inputdatefin = $fonctions->formatdate($inputdatefin);
        }
        echo "<td>";
    ?>
        <input required class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	size=10
        	minperiode='<?php echo $fonctions->formatdate($datefinminconv); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($datefinmaxconv); ?>'
        	value='<?php echo $inputdatefin ?>'>
    <?php
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        $teletravail = new teletravail($dbcon);
        if (strlen($inputtabteletravail . "")>0)
        {
            $teletravail->statut(teletravail::TELETRAVAIL_VALIDE); // important car sinon le télétravail n'est pas actif et donc c'est jamais télétravaillé
            $teletravail->tabteletravail($inputtabteletravail);
        }
        echo "<br>";
        
        $affectation = null;
        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('31/12/2100'), true);
        if (count((array)$affectationliste)==0)
        {
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous n'avez pas d'affectation actuellement. Impossible de déclarer une convention de télétravail.");
            $nbjoursmaxteletravailcalcule = 0;
            $disablesubmit = true;
        }
        else
        {
            //var_dump($affectationliste);
            // On construit le tableau javascript qui contient les quotité de l'agent
            $tabaffectation_java = "var tabaffectation = { ";
            $tabaffectation_value = "";
            foreach ($affectationliste as $affectation)
            {
                if ($tabaffectation_value != "")
                {
                    $tabaffectation_value = $tabaffectation_value . ", ";
                }
//                $tabaffectation_value = $tabaffectation_value . "['" . $fonctions->formatdatedb($affectation->datedebut()) . "'] : ";
                $tabaffectation_value = $tabaffectation_value . "['" . $fonctions->formatdatedb($affectation->datedebut()) . "-" . $fonctions->formatdatedb($affectation->datefin()) . "'] : ";
                if ($affectation->quotite()=='100%')
                {
                    $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail;
                }
                elseif ($affectation->quotitevaleur() == 0.8 or $affectation->quotitevaleur() == 0.9)
                {
                    $nbjoursmaxteletravailcalcule = 1;
                }
                else
                {
                    $nbjoursmaxteletravailcalcule = 0;
                }
                $tabaffectation_value = $tabaffectation_value . $nbjoursmaxteletravailcalcule . " ";
            }
            $tabaffectation_java = $tabaffectation_java . $tabaffectation_value . " } ;";
            echo "<script>" . $tabaffectation_java . "</script>";
            //var_dump($tabaffectation_java);
            $affectation = reset($affectationliste);
            
            if (!isset($_POST["nbjoursmaxteletravailcalcule"]))
            {

                if ($affectation->quotite()=='100%')
                {
                    $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail;
                }
                elseif ($affectation->quotitevaleur() == 0.8 or $affectation->quotitevaleur() == 0.9)
                {
                    $nbjoursmaxteletravailcalcule = 1;
                }
                else
                {
                    $nbjoursmaxteletravailcalcule = 0;
                }
            }
            else
            {
                $nbjoursmaxteletravailcalcule = $_POST["nbjoursmaxteletravailcalcule"];
            }
            if ($nbjoursmaxteletravailcalcule == 0)
            {
                $disablesubmit = true;
            }
            
            $declarationliste = $affectation->declarationTPliste(date('d/m/Y'), date('d/m/Y'));
            $declaration = null;
            if (! is_null($declarationliste)) 
            {
                foreach ($declarationliste as $declaration)
                {
                    if (strcasecmp((string)$declaration->statut(), declarationTP::DECLARATIONTP_REFUSE) != 0) 
                    {
                        // La première déclaration de TP non refusée qu'on trouve est la bonne
                        break;
                    }
                    $declaration = null;
                }
            }
            
            if (is_null($declaration) and $agentstructure->estbibliotheque())
            {
                $declaration = new declarationTP($dbcon);
                $declaration->agentid($agent->agentid());
                $declaration->tabtpspartiel(str_repeat("0", 20));
                $declaration->statut(declarationTP::DECLARATIONTP_VALIDE);
                //$declarationTP->datedebut($datedebut);
                //$declarationTP->datefin($datefin);
            }
            
            // A ce niveau $declaration est soit NULL soit il vaut la declaration de TP active
            if (is_null($declaration) or $mode=='gestrh')
            {
                if (is_null($declaration) and $mode!='gestrh')
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR, "Vous n'avez pas de déclaration de temps partiel active.<br>Impossible de saisir une convention de télétravail");
                    $nbjoursmaxteletravailcalcule = 0;
                    $disablesubmit = true;
                }
                elseif (is_null($declaration) and $mode=='gestrh')
                {
                    echo $fonctions->showmessage(fonctions::MSGWARNING, "L'agent " . $agent->identitecomplete() . " pas de déclaration de temps partiel active.");
                }
                if ($mode=='gestrh')
                {
                    $hidden = '';
                    if ($inputtypeconv != teletravail::CODE_CONVENTION_MEDICAL)
                    {
                        $hidden = " hidden='hidden' ";
                    }
                    echo "<div id='divttnormal'>";
                    echo "</div>";
                    echo "<div id='divttmedical' $hidden >";
                    echo "<span id='warningmedical' class='celinfo resetfont' ><br>Attention : Des documents complémentaires devront être fournis au moment de la signature de la convention de télétravail.</span><br>";
                    echo "Motif de la demande de télétravail pour raison médicale (obligatoire) : <br>";
                    $check = '';
                    $customstyle = "class='motifraisonmedicalteletravail'";
                    if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_SANTE]))
                    {
                        $check = ' checked ';
                    }
                    echo "<input type='checkbox' $check name='motifmedical[" . teletravail::MOTIF_MEDICAL_SANTE . "]' $customstyle>Raison de santé</input><br>";
                    $check = '';
                    if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_GROSSESSE]))
                    {
                        $check = ' checked ';
                    }
                    echo "<input type='checkbox' $check name='motifmedical[" . teletravail::MOTIF_MEDICAL_GROSSESSE . "]' $customstyle>Grossesse</input><br>";
                    $check = '';
                    if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_AIDANT]))
                    {
                        $check = ' checked ';
                    }
                    echo "<input type='checkbox' $check name='motifmedical[". teletravail::MOTIF_MEDICAL_AIDANT . "]' $customstyle>Proche aidant</input><br>";
                    echo "</div>";
                    echo "<table class='tableausimple' id='tabttnormal' ";
                    echo ">";
                    $moment = fonctions::MOMENT_MATIN;
                    $tabmatin = '';
                    $tabapresmidi = '';
                    for ($cpt=0 ; $cpt<10 ; $cpt ++)
                    {
                        //var_dump ("cpt = $cpt");
                        $indexjour = intdiv($cpt,2)+1;
                        //var_dump ("indexjour = $indexjour");
                        if ($indexjour >= 6)
                        {
                            break;
                        }
                        $fermeinput = "";
                        $fermespan = '';
                        $tmpcellule  = '';
                        $tmpcellule = $tmpcellule . "    <td class='cellulesimple' ";
                        if ($declaration->enTPindexjour($indexjour,$moment,true) or $declaration->enTPindexjour($indexjour,$moment,false))
                        {
                            $fermespan = "</span>";
                            $tmpcellule .= " style='background: " . TABCOULEURPLANNINGELEMENT['tppar']['couleur']  . " ;' >";
                            $tmpcellule .= "<span data-tip=" . chr(34) . htmlentities(TABCOULEURPLANNINGELEMENT['tppar']['libelle']) . chr(34);
                        }
                        else
                        {
                            $fermeinput = '</input>';
                            $tmpcellule = $tmpcellule . "><input type='checkbox' value='" . ($cpt+1) . "' id='creation_" . ($cpt+1) . "' name='demijours[]'";
                            if ($teletravail->estjourteletravaille($indexjour,$moment))
                            {
                                $tmpcellule = $tmpcellule . " checked ";
                            }
                        }
                        $tmpcellule = $tmpcellule . ">" . $fonctions->nomjourparindex($indexjour) . " " . $fonctions->nommoment($moment) . " $fermeinput $fermespan </td>";
                        if ($moment == fonctions::MOMENT_MATIN)
                        {
                            $tabmatin = $tabmatin . $tmpcellule;
                            $moment = fonctions::MOMENT_APRESMIDI;
                        }
                        else
                        {
                            $tabapresmidi = $tabapresmidi . $tmpcellule;
                            $moment = fonctions::MOMENT_MATIN;
                        }
                    }
                    echo "<tr><center>";
                    echo $tabmatin;
                    echo "</center></tr>";
                    echo "<tr><center>";
                    echo $tabapresmidi;
                    echo "</center></tr>";
                    echo "</table>";
                }
            }
            else
            {
                $hidden = '';
                if ($inputtypeconv == teletravail::CODE_CONVENTION_MEDICAL)
                {
                    $hidden = " hidden='hidden' ";
                }
                echo "<div id='divttnormal' $hidden>";
                echo "<br>";
                // Ci-dessous : Le tableau pour les temps complets et les TP 90% et 80%
                echo "<label id='labelmaxjrs' >Jours de télétravail : Vous pouvez déclarer jusqu'à <input type='text' class='noborder' size=1 readonly id='nbjoursmaxteletravailcalcule' name='nbjoursmaxteletravailcalcule' value='$nbjoursmaxteletravailcalcule'></input> jour(s) de télétravail.</label>";
                echo "<table class='tableausimple' id='tabttnormal' ";
                echo ">";
                echo "<tr><center>";
                for ($cpt=0 ; $cpt<10 ; $cpt ++)
                {
                    //var_dump ("cpt = $cpt");
                    $indexjour = intdiv($cpt,2)+1;
                    //var_dump ("indexjour = $indexjour");
                    if ($indexjour >= 6)
                    {
                        break;
                    }
                    $fermeinput = "";
                    $fermespan = '';
                    // Si l'agent n'est jamais en TP (semaine paire/impaire et matin/après-midi pour le jour courant)
                    // On affiche le jour complet pour le télétravail
                    echo "<td class='cellulesimple widthtd90' ";
                    if (!$declaration->enTPindexjour($indexjour,fonctions::MOMENT_MATIN,true) and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_MATIN,false)
                    and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_APRESMIDI,true) and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_APRESMIDI,false))
                    {
                        echo "><center><input class='checkbox_jours' type='checkbox' value='$indexjour' id='creation_$indexjour' name='jours[]' onclick='verif_nbre_checkbox();'";
                        if ($teletravail->estjourteletravaille($indexjour) and $inputtypeconv != teletravail::CODE_CONVENTION_MEDICAL) { echo " checked "; }
                        echo ">" . $fonctions->nomjourparindex($indexjour) . "</input></center></td>";
                    }
                    else
                    {
                        echo " style='background: " . TABCOULEURPLANNINGELEMENT['tppar']['couleur']  . " ;' >";
                        echo "<span data-tip=" . chr(34) . TABCOULEURPLANNINGELEMENT['tppar']['libelle'] . chr(34) . ">";
                        echo $fonctions->nomjourparindex($indexjour) . "</span></td>";
                    }
                    $cpt++;
                }
                echo "</center></tr>";
                echo "</table>";
                echo "</div>";
                // Ci-dessous le tableau pour les convention médicales
                //echo "<br>";
                //echo "Ci-dessous le tableau pour les convention médicales : <br>";
                $hidden = '';
                if ($inputtypeconv != teletravail::CODE_CONVENTION_MEDICAL)
                {
                    $hidden = " hidden='hidden' ";
                }              
                echo "<div id='divttmedical' $hidden >";
                echo "<span id='warningmedical' class='celinfo resetfont' ><br>Attention : Des documents complémentaires devront être fournis au moment de la signature de la convention de télétravail.</span><br>";
                echo "Motif de la demande de télétravail pour raison médicale (obligatoire) : <br>";
                $check = '';
                $customstyle = "class='motifraisonmedicalteletravail'";
                if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_SANTE]))
                {
                    $check = ' checked ';
                }
                echo "<input type='checkbox' $check name='motifmedical[" . teletravail::MOTIF_MEDICAL_SANTE . "]' $customstyle>Raison de santé</input><br>";
                $check = '';
                if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_GROSSESSE]))
                {
                    $check = ' checked ';
                }
                echo "<input type='checkbox' $check name='motifmedical[" . teletravail::MOTIF_MEDICAL_GROSSESSE . "]' $customstyle>Grossesse</input><br>";
                $check = '';
                if (isset($inputmotifmedical[teletravail::MOTIF_MEDICAL_AIDANT]))
                {
                    $check = ' checked ';
                }
                echo "<input type='checkbox' $check name='motifmedical[". teletravail::MOTIF_MEDICAL_AIDANT . "]' $customstyle>Proche aidant</input><br>";
                
                echo "<label id='labeljrsmedical' class='gestteletravaillabel' >Demie-journée de télétravail sur prescription médicale.</label>";
                $tableau_matin = "";
                $tableau_apresmidi = "";
                $moment = fonctions::MOMENT_MATIN;
                for ($cpt=0 ; $cpt<10 ; $cpt ++)
                {
                    $tableau_demiejour = '';
                    //var_dump ("cpt = $cpt");
                    $indexjour = intdiv($cpt,2)+1;
                    //var_dump ("indexjour = $indexjour");
                    if ($indexjour >= 6)
                    {
                        break;
                    }
                    $fermeinput = "";
                    $fermespan = '';
                    $tableau_demiejour .= "    <td class='cellulesimple' ";
                    if ($declaration->enTPindexjour($indexjour,$moment,true) or $declaration->enTPindexjour($indexjour,$moment,false))
                    {
                        $fermespan = "</span>";
                        $tableau_demiejour .= " style='background: " . TABCOULEURPLANNINGELEMENT['tppar']['couleur']  . " ;' >";
                        $tableau_demiejour .= "<span data-tip=" . chr(34) . TABCOULEURPLANNINGELEMENT['tppar']['libelle'] . chr(34);
                    }
                    else
                    {
                        $fermeinput  = "</input>";
                        $tableau_demiejour .= "><input type='checkbox' value='" . ($cpt+1) . "' id='creation_" . ($cpt+1) . "' name='demijours[]'";
                        if ($teletravail->estjourteletravaille($indexjour,$moment) and $inputtypeconv == teletravail::CODE_CONVENTION_MEDICAL)
                        {
                            $tableau_demiejour .= " checked ";
                        }
                    }
//                    $tableau_demiejour .= ">" . $fonctions->nomjourparindex($indexjour) . " " . $fonctions->nommoment($moment) . " $fermeinput $fermespan</td>";
                    $tableau_demiejour .= ">" . $fonctions->nommoment($moment) . " $fermeinput $fermespan</td>";
                    if ($moment == fonctions::MOMENT_MATIN)
                    {
                        $tableau_matin .= $tableau_demiejour;
                        $moment = fonctions::MOMENT_APRESMIDI;
                    }
                    else
                    {
                        $tableau_apresmidi .= $tableau_demiejour;
                        $moment = fonctions::MOMENT_MATIN;
                    }
                }
                echo "<table class='tableausimple'>";
                echo "<tr>";
                for ($cpt=1 ; $cpt <= 5 ; $cpt++)
                {
                    echo "<td class='titresimple'>" . $fonctions->nomjourparindex($cpt)  . "</td>";
                }
                echo "</tr>";
                echo "<tr>";
                echo $tableau_matin;
                echo "</tr><tr>";
                echo $tableau_apresmidi;
                echo "</tr></table>";
                echo "</div>";
            }
        }
        echo "<br>";
?>
        <script>
            function verif_nbre_checkbox()
            {
                // console.log('event fired');
                var checkBoxlist = document.getElementsByClassName("checkbox_jours");
                var nbselected = 0;
                // console.log(checkBoxlist.length);
                for (let index = 0; index < checkBoxlist.length; index++) 
                {
                    var currentcheckbox = checkBoxlist[index];
                    if (currentcheckbox.checked)
                    {
                        nbselected++;
                    }
                }
                let nbjoursmaxteletravailcalcule = document.getElementById('nbjoursmaxteletravailcalcule');
                
                if (nbjoursmaxteletravailcalcule && nbjoursmaxteletravailcalcule.value == 0)
                {
                    for (let index = 0; index < checkBoxlist.length; index++) 
                    {
                        var currentcheckbox = checkBoxlist[index];
                        currentcheckbox.checked = false;
                        currentcheckbox.disabled=true;
                    }                    
                }
                else if (nbjoursmaxteletravailcalcule && (nbselected == nbjoursmaxteletravailcalcule.value))
                {
                    for (let index = 0; index < checkBoxlist.length; index++) 
                    {
                        var currentcheckbox = checkBoxlist[index];
                        if (!currentcheckbox.checked)
                        {
                            currentcheckbox.disabled=true;
                        }
                    }
                }
                else if (nbjoursmaxteletravailcalcule && (nbselected < nbjoursmaxteletravailcalcule.value))
                {
                    for (let index = 0; index < checkBoxlist.length; index++) 
                    {
                        var currentcheckbox = checkBoxlist[index];
                        currentcheckbox.disabled=false;
                    }
                }
                else if (nbjoursmaxteletravailcalcule && (nbselected > nbjoursmaxteletravailcalcule.value))
                {
                    alert ('Trop de cases cochées');
                    for (let index = 0; index < checkBoxlist.length; index++) 
                    {
                        var currentcheckbox = checkBoxlist[index];
                        currentcheckbox.checked = false;
                        currentcheckbox.disabled=false;
                    }
                }
            }
            verif_nbre_checkbox();
        </script>
<?php
//        echo "<input type='hidden' id='nbjoursmaxteletravailcalcule' name='nbjoursmaxteletravailcalcule' value='" . $nbjoursmaxteletravailcalcule . "'>";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
        echo "<input type='hidden' id='nbrelignetableauconvention' name='nbrelignetableauconvention' value='". $nbrelignetableauconvention . "'>";
        echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
        echo "<input type='hidden' id='datedebutminconv' name='datedebutminconv' value='" . $datedebutminconv . "'>";
        echo "<input type='hidden' id='datedebutmaxconv' name='datedebutmaxconv' value='" . $datedebutmaxconv . "'>";
        echo "<input type='hidden' id='datefinminconv' name='datefinminconv' value='" . $datefinminconv . "'>";
        echo "<input type='hidden' id='datefinmaxconv' name='datefinmaxconv' value='" . $datefinmaxconv . "'>";
        echo "<input type='hidden' id='noesignature'  name='noesignature' value='" . $noesignature . "'>";
        
        $hiddentext = '';
        if ($disablesubmit)
        {
            $hiddentext = " hidden='hidden' ";
        }
        echo "<input type='submit' id='creation' name='creation' class='g2tbouton g2tvalidebouton' value='Enregistrer' $hiddentext />";
        echo "</form>";        

        if (isset($formdisabled) and $formdisabled != '')
        {
?>
            <script>
                var formcreationteletravail = document.getElementById('form_teletravail_creation');
                if (formcreationteletravail)
                {
                    // console.log ('La form est trouvée');
                    for (var champ=0; champ < formcreationteletravail.elements.length; champ++) 
                    {
                        // console.log (formcreationteletravail.elements[champ].name);
                        formcreationteletravail.elements[champ].disabled = true;
                    }
                }
            </script>
<?php
        }
    }
    elseif ($mode=='resp' or $mode=='gestion')
    {
?>
        <script>
            function clearallinput()
            {
                var listeinput = document.getElementsByClassName('inputtoreset');
                for (let index = 0; index < listeinput.length; index++) 
                {
                    if (listeinput[index].classList.contains('inputtext'))
                    {
                        listeinput[index].value="";
                    }
                    else if (listeinput[index].classList.contains('inputselect'))
                    {
                        listeinput[index].selectedIndex = 0;
                    }
                    else
                    {
                        alert ('Je ne connais pas le type d\'element input : ' + listeinput[index].id);
                    }
                }
                var divstatutvalide = document.getElementById('divstatutvalide');
                var divstatutrefuse = document.getElementById('divstatutrefuse');
                divstatutvalide.hidden=true;
                divstatutrefuse.hidden=true;
            }
            
            function changecheckbox(idteletravail, noreset = false)
            {
                var checkbox = document.getElementById('selectteletravail[' + idteletravail + ']');
                var inputidconvention = document.getElementById('idconvention');
                var listeligne = document.getElementsByClassName('ligneteletravail');
                for (let index = 0; index < listeligne.length; index++) 
                {
                    //alert ('listeligne[index].id = ' + listeligne[index].id + '  idteletravail = ' + idteletravail);
                    var divmodifteletravail = document.getElementById('divmodifteletravail');
                    if (listeligne[index].id.toString() !== idteletravail.toString())
                    {
                        var lignecheckbox = document.getElementById('selectteletravail[' + listeligne[index].id + ']');
                        lignecheckbox.disabled = checkbox.checked;
                    }
                    else if (checkbox.checked)
                    {
                        listeligne[index].style.backgroundColor = 'orange';
                        divmodifteletravail.hidden = false;
                        //alert ('Faire le reset de toutes les zones de saisie');
                        if (noreset===false)
                        {
                            clearallinput();
                        }
                        inputidconvention.value = idteletravail;
                    }
                    else
                    {
                        listeligne[index].style.backgroundColor = 'initial';                        
                        divmodifteletravail.hidden = true;
                        inputidconvention.value = "";
                    }
                }
            }
            
            function showsubdiv(statutselected)
            {
                var divstatutvalide = document.getElementById('divstatutvalide');
                var divstatutrefuse = document.getElementById('divstatutrefuse');
                if (statutselected === '')
                {
                    divstatutvalide.hidden = true;
                    divstatutrefuse.hidden = true;
                }
                else if (statutselected === '<?php echo teletravail::TELETRAVAIL_VALIDE; ?>')
                {
                    divstatutvalide.hidden = false;
                    divstatutrefuse.hidden = !divstatutvalide.hidden;
                }
                else
                {
                    divstatutvalide.hidden = true;
                    divstatutrefuse.hidden = !divstatutvalide.hidden;
                }
                var activitetele = document.getElementById('activitetele');
                activitetele.disabled = divstatutvalide.hidden;
                checktextlength(activitetele,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','ACTIVITETELETRAVAIL'); ?>,"activitetelerestant");
                var periodeexclusion = document.getElementById('periodeexclusion');
                periodeexclusion.disabled = divstatutvalide.hidden;
                checktextlength(periodeexclusion,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEEXCLUSION'); ?>,"periodeexclusionrestant");
                var periodeadaptation = document.getElementById('periodeadaptation');
                periodeadaptation.disabled = divstatutvalide.hidden;
                checktextlength(periodeadaptation,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEADAPTATION'); ?>,"periodeadaptrestant");
                var motifrefus = document.getElementById('motifrefus');
                motifrefus.disabled = divstatutrefuse.hidden;
                checktextlength(motifrefus,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','COMMENTAIRE'); ?>,"motifrefusrestant");
            }
            
        </script>
<?php

        if ($mode=='resp')
        {
        //echo "<br>On est en mode responsable ==> On va modifier les demandes de télétravail de la structure dont on est responsable.<br>";
            $structliste = $user->structrespliste(true);
        }
        else
        {
            //var_dump('Je suis en mode gestionnaire');
            $structliste = $user->structgestliste();   
            //var_dump($structliste);
        }
        $agentliste = array();
        $teletravailtrouve = false;
        foreach ($structliste as $structure)
        {
            if ($mode=='resp')
            {
                $agentliste = $structure->agentlist(date('Ymd'), date('Ymd'), 'n');
                $structliste = $structure->structurefille();
                //$structliste = $this->structureinclue();
                if (! is_null($structliste)) {
                    foreach ($structliste as $key => $sousstruct) 
                    {
                        if ($fonctions->formatdatedb($sousstruct->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
                        {
                            $resp = $sousstruct->responsable();
                            $agentliste[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                        }
                    }
                }
            }
            else if ($mode=='gestion')
            {
                // On récupère les demandes des agents que si le gestionnaire signe aussi les congés des agents
                $codeinterne=null;
                $respsignataire = $structure->agent_envoyer_a($codeinterne,false);
                // Si le signataire du responsable est l'utilisateur courant
                if ($respsignataire->agentid() == $user->agentid())
                {
                    if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
                    {
                        foreach($structure->agentlist(date("d/m/Y"), date("d/m/Y")) as $membre)
                        {
                            $agentliste[$membre->nom() . " " . $membre->prenom() . " " . $membre->agentid()] = $membre;
                        }
                    }
                }
                
                // On récupère les demandes du responsable dont le gestionnaire signe les demandes 
                $codeinterne=null;                
                $respsignataire = $structure->resp_envoyer_a($codeinterne,false);
                // Si le signataire du responsable est l'utilisateur courant
                if ($respsignataire->agentid() == $user->agentid())
                {
                    if ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
                    {
                        $resp = $structure->responsable();
                        $agentliste[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                    }
                }
            }
            //var_dump($agentliste);
            
            foreach ((array)$agentliste as $agent)
            {
                if ($agent->agentid()!=$user->agentid() or $mode!='resp')
                {
                    $tabdemandeteletravail = $agent->listedemandeteletravailenattente();
                    foreach($tabdemandeteletravail as $teletravail)
                    {
                        if ($teletravailtrouve===false)
                        {
                            echo "<table class='tableausimple'>";
                            echo "<tr>";
                            echo "<td class='titresimple' colspan=6>Informations sur les demandes de télétravail pour la structure : " . $structure->nomlong()  . "</td>";
                            echo "</tr>";
                            echo "<tr>";
                            echo "<td class='cellulesimple'>Identité du demandeur</td>";
                            echo "<td class='cellulesimple'>Type de demande</td>";
                            echo "<td class='cellulesimple'>Date début</td>";
                            echo "<td class='cellulesimple'>Date fin</td>";
                            echo "<td class='cellulesimple'>Répartition souhaitée</td>";
                            echo "<td class='cellulesimple'>Compléter la demande</td>";
                            echo "</tr>";
                            $teletravailtrouve = true;
                        }
                        $debutspan = "";
                        $finspan = "";
                        $motifmedical = "";
                        if ($teletravail->typeconvention()==teletravail::CODE_CONVENTION_MEDICAL)
                        {
                            if (intval($teletravail->motifmedicalsante())>0)
                            {
                                $motifmedical = $motifmedical . "Raison de santé,";
                            }
                            if (intval($teletravail->motifmedicalgrossesse())>0)
                            {
                                $motifmedical = $motifmedical . " Grossesse,";
                            }
                            if (intval($teletravail->motifmedicalaidant())>0)
                            {
                                $motifmedical = $motifmedical . " Proche aidant,";
                            }
                            $motifmedical = trim($motifmedical);
                            $motifmedical = substr($motifmedical,0,strlen($motifmedical)-1);
                            $debutspan = "<span data-tip=" . chr(34) . htmlentities($motifmedical) . chr(34) . ">";
                            $finspan = "</span>";
                        }
                        echo "<tr class='ligneteletravail' id='" . $teletravail->teletravailid() . "'>";
                        echo "<td class='cellulesimple'>" . $agent->identitecomplete() . "</td>";
                        echo "<td class='cellulesimple'>$debutspan" . $teletravail->libelletypeconvention()  . "$finspan</td>";
                        echo "<td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datedebut())  . "</td>";
                        echo "<td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datefin())  . "</td>";
                        echo "<td class='cellulesimple'>" . $teletravail->libelletabteletravail()  . "</td>";
                        $checked = '';
                        if ($idconvention == $teletravail->teletravailid())
                        {
                            $checked = 'checked';
                        }
                        echo "<td class='cellulesimple'><input type='checkbox' id='selectteletravail[" . $teletravail->teletravailid() . "]' name='selectteletravail[" . $teletravail->teletravailid() . "]' onchange='changecheckbox(" . $teletravail->teletravailid() . ");' $checked></input></td>";
                        echo "</tr>";
                    }                    
                }
            }
        }
        if ($teletravailtrouve===true)
        {
            echo "</table>";
        }
        if (!$teletravailtrouve)
        {
            echo "<br>Aucune demande de convention de télétravail n'est en attente.<br>";
        }
        else
        {
            echo "<form name='frm_resp_statut' method='post'>";
            $hidden = '';
            if ($statutresp."" == "")
            {
                $hidden = "hidden='hidden'";
            }
            echo "<div  name='divmodifteletravail' id='divmodifteletravail' $hidden>";
            echo "<br>";
            echo "Statut de la demande : ";
            echo "<select required class='inputtoreset inputselect' id='statusresp' name='statutresp' onChange='showsubdiv(this.value);'>";  //
            $selected = '';
            if ($statutresp .'' == "")
            {
                $selected = 'selected';
            }
            echo "<option value=''>-- Sélectionnez le statut --</option>";
            $selected = '';
            if ($statutresp == teletravail::TELETRAVAIL_VALIDE)
            {
                $selected = 'selected';
            }
            echo "<option value='" . teletravail::TELETRAVAIL_VALIDE  . "' $selected>" . $fonctions->teletravailstatutlibelle(teletravail::TELETRAVAIL_VALIDE)  . "</option>";
            $selected = '';
            if ($statutresp == teletravail::TELETRAVAIL_REFUSE)
            {
                $selected = 'selected';
            }
            echo "<option value='" . teletravail::TELETRAVAIL_REFUSE  . "' $selected>" . $fonctions->teletravailstatutlibelle(teletravail::TELETRAVAIL_REFUSE)  . "</option>";
            echo "</select>";
            echo "<br>";
            $hidden = '';
            if ($statutresp."" != teletravail::TELETRAVAIL_VALIDE)
            {
                $hidden = "hidden='hidden'";
            }
            echo "<div id='divstatutvalide' $hidden >";
            echo $fonctions->showmessage(fonctions::MSGWARNING, "La saisie des activités télétravaillables est obligatoire.");
            $longueurmaxtexte = $fonctions->logueurmaxcolonne('TELETRAVAIL','ACTIVITETELETRAVAIL');
            echo "Liste des activités télétravaillables (maximum : $longueurmaxtexte caractères et 10 lignes - Reste : <label id='activitetelerestant'>$longueurmaxtexte</label> car.) : <br>";
            ///// ATTENTION : Dans le style du textarea, le height doit être égal à rows x line-height
            echo "<textarea required class='inputtoreset inputtext commenttextarea' rows='10' cols='200' name='activitetele' id='activitetele' oninput='checktextlength(this,$longueurmaxtexte,\"activitetelerestant\"); '>$activitetele</textarea> <br>";
            $longueurmaxtexte = $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEEXCLUSION');
            echo "<br>Liste des périodes d'exclusion du télétravail (maximum : $longueurmaxtexte caractères et 2 lignes - Reste : <label id='periodeexclusionrestant'>$longueurmaxtexte</label> car.) : ";
            echo "<br><strong>Laissez cette zone vide si l'agent n'est pas concerné.</strong>";
            echo "<br>";
            ///// ATTENTION : Dans le style du textarea, le height doit être égal à rows x line-height
            echo "<textarea class='inputtoreset inputtext commenttextarea' rows='2' cols='200' name='periodeexclusion' id='periodeexclusion' oninput='checktextlength(this,$longueurmaxtexte,\"periodeexclusionrestant\"); '>$periodeexclusion</textarea> <br>";
            $longueurmaxtexte = $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEADAPTATION');
            echo "<br>Durée de la période d'adaptation  (maximum : $longueurmaxtexte caractères et 1 ligne - Reste : <label id='periodeadaptrestant'>$longueurmaxtexte</label> car.): ";
            echo "<br><strong>Laissez cette zone vide si l'agent n'est pas concerné.</strong>";
            echo "<br>";
            ///// ATTENTION : Dans le style du textarea, le height doit être égal à rows x line-height
            echo "<textarea class='inputtoreset inputtext commenttextarea' rows='1' cols='200' name='periodeadaptation' id='periodeadaptation' oninput='checktextlength(this,$longueurmaxtexte,\"periodeadaptrestant\"); '>$periodeadaptation</textarea> <br>";
            echo "</div>";
            $hidden = '';
            if ($statutresp."" != teletravail::TELETRAVAIL_REFUSE)
            {
                $hidden = "hidden='hidden'";
            }
            echo "<div id='divstatutrefuse' $hidden >";
            echo $fonctions->showmessage(fonctions::MSGWARNING, "La saisie du motif est obligatoire.");
            $longueurmaxtexte = $fonctions->logueurmaxcolonne('TELETRAVAIL','COMMENTAIRE');
            echo "Motif du refus (maximum : $longueurmaxtexte caractères et 4 lignes - Reste : <label id='motifrefusrestant'>$longueurmaxtexte</label> car.) : <br>";
            echo "<textarea required class='inputtoreset inputtext commenttextarea' rows='4' cols='80' name='motifrefus' id='motifrefus' oninput='checktextlength(this,$longueurmaxtexte,\"motifrefusrestant\"); '>$motifrefus</textarea> <br>";
            echo "</div>";
            echo "<br>";
            echo "<br>";
            echo "</div>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'></input>";
            //echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
            echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'></input>";
            echo "<input type='hidden' id='idconvention' name='idconvention' value=''></input>";
            echo "<input type='hidden' id='noesignature'  name='noesignature' value='" . $noesignature . "'>";
            echo "<input type='submit' name='but_resp_statut' id='but_resp_statut' class='g2tbouton g2tvalidebouton' value='Enregistrer' ></input>";
            echo "</form>";
?>
            <script>
                var activitetele = document.getElementById('activitetele');
                checktextlength(activitetele,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','ACTIVITETELETRAVAIL'); ?>,"activitetelerestant");
                var periodeexclusion = document.getElementById('periodeexclusion');
                checktextlength(periodeexclusion,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEEXCLUSION'); ?>,"periodeexclusionrestant");
                var periodeadaptation = document.getElementById('periodeadaptation');
                checktextlength(periodeadaptation,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','PERIODEADAPTATION'); ?>,"periodeadaptrestant");
                var motifrefus = document.getElementById('motifrefus');
                checktextlength(motifrefus,<?php echo $fonctions->logueurmaxcolonne('TELETRAVAIL','COMMENTAIRE'); ?>,"motifrefusrestant");
            </script>
<?php
            if ($idconvention != "")
            {
                echo "<script>changecheckbox($idconvention,true);</script>";
                echo "<script>showsubdiv('" . $statutresp  . "');</script>";
            }
        }
    }
?>
</body>
</html>