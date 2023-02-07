<?php
    require_once ('CAS.php');
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
            $agentid = null;
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
    
    require ("includes/menu.php");
    
    //echo "<br>" . print_r($_POST, true) . "<br><br>";
    
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
    if (!is_null($agentid))
    {
        $taberrorcheckmail = $fonctions->ckecksignataireteletravailliste($params,$agent,$maxniveau);
    }
    if (count($taberrorcheckmail) > 0)
    {
        // var_dump("errorcheckmail = $errorcheckmail");
        $errorcheckmailstr = '';
        foreach ($taberrorcheckmail as $errorcheckmail)
        {
            if (strlen($errorcheckmailstr)>0) $errorcheckmailstr = $errorcheckmailstr . '<br>';
            $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
        }
        $erreur = "Impossible de créer une convention de télétravail car <br>$errorcheckmailstr";
        $disablesubmit = true;
    }
    elseif (!is_null($agentid))
    {
        $id_model = trim($fonctions->getidmodelteletravail($maxniveau,$agent));
        //var_dump($id_model);
        if (trim($id_model) == '')
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Le modèle eSignature pour la création d'une convention télétravail n'a pas pu être déterminé.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $disablesubmit = true;
        }
        
    }
    
    if (isset($_POST["modification"]))  // On a cliqué sur le bouton "annulation"
    {
        //echo "On va annuler des conventions de télétravail.<br>";
        foreach ((array)$cancelteletravailarray as $cancelteletravailid)
        {
            //echo "cancelteletravailid = $cancelteletravailid <br>";
            $teletravail = new teletravail($dbcon);
            $return = $teletravail->load($cancelteletravailid);
            if (!$return)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Erreur dans le chargement de la convention $cancelteletravailid pour annulation : " . $return;
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif ($teletravail->statut() == teletravail::TELETRAVAIL_ANNULE)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Impossible d'annuler la convention  télétravail $cancelteletravailid : Elle est déjà annulée.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
            }
            else
            {
                $return = $fonctions->deleteesignaturedocument($teletravail->esignatureid());
                if (strlen($return)>0) // On a rencontré une erreur dans la suppression eSignature
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                        if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                        $erreur = $erreur . $return;
                        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
                    }
                    else
                    {
                        $info = $info . "L'annulation de la convention " . $teletravail->esignatureid() . " a été enregistrée.";
                    }
                }
                //deleteesignaturedocument($teletravail);
            }
        }
        // On va modifier les dates des conventions de télétravail
        foreach ((array)$datedebutconv as $idconv => $datedebut)
        {
            $datefin = $datefinconv[$idconv];
            if (!$fonctions->verifiedate($datedebut) or !$fonctions->verifiedate($datefin))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début ou de fin de la convention $idconv n'est pas correcte.";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            }
            elseif ($fonctions->formatdatedb($datedebut)>$fonctions->formatdatedb($datefin))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
        
        $datedebutminconv = date('d/m/Y');
        if (isset($_POST["datedebutminconv"]))
        {
            $datedebutminconv = $_POST["datedebutminconv"];
        }
        
        $datedebutmaxconv = date('d/m/') . (date('Y')+1);
        if (isset($_POST["datedebutmaxconv"]))
        {
            $datedebutmaxconv = $_POST["datedebutmaxconv"];
        }
        $datefinminconv = date('d/m/Y');
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
        if (!is_null($jours))
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
        if (!is_null($demijours))
        {
            foreach((array)$demijours as $numdemijour) // numdemijour => [1-10] où 1 = lundi matin, 2 = lundi après-midi, ...
            {
                $numdemijour = $numdemijour - 1;   // $numdemijour = l'index du talbeau 0 = lundi matin
                $tabteletravail = substr_replace($tabteletravail,'1',$numdemijour,1);
            }
        }
        
        //echo "tabteletravail = $tabteletravail <br>";
        $dateok = true;
        
        $teletravailliste = $agent->teletravailliste('01/01/1900', '31/12/2100'); // On va récupérer toutes les demandes de télétravail de l'agent
        if (count($teletravailliste) > $nbrelignetableauconvention)
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Il y a une incohérence sur la vérification des conventions existantes.";
            $dateok = false;
        }
        
        if ($typeconv . '' == '')
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Vous n'avez pas sélectionné le type de convention de télétravail.";
            $dateok = false;
        }
        
        if (!$fonctions->verifiedate($datedebutteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if (!$fonctions->verifiedate($datefinteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de fin de la convention n'est pas correcte ou définie.";
            $dateok = false;
        }
        if ($dateok and $fonctions->formatdatedb($datedebutteletravail)>$fonctions->formatdatedb($datefinteletravail))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "La date de début est supérieure à la date de fin de la convention.";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
            $dateok = false;
        }
        
        if ($dateok)
        {
            if ($fonctions->formatdatedb($datedebutteletravail)<$fonctions->formatdatedb($datedebutminconv)
                or $fonctions->formatdatedb($datedebutteletravail)>$fonctions->formatdatedb($datedebutmaxconv))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La date de début de la convention n'est pas dans la période autorisée ($datedebutminconv -> $datedebutmaxconv).";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
            if ($fonctions->formatdatedb($datefinteletravail)<$fonctions->formatdatedb($datefinminconv)
                or $fonctions->formatdatedb($datefinteletravail)>$fonctions->formatdatedb($datefinmaxconv))
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                    $dureemax = 1;
                    break;
                case teletravail::CODE_CONVENTION_RENOUVELLEMENT :
                    $dureemax = 2;
                    break;
            }
            $datedebuttimestamp = strtotime($fonctions->formatdatedb($datedebutteletravail));
            $datefinmaxi = date('Ymd', strtotime('+'.$dureemax.' year', $datedebuttimestamp ));
            // var_dump("datedebutteletravail = $datedebutteletravail");
            // var_dump("datefinmaxi = $datefinmaxi");
            
            
            if ($fonctions->formatdatedb($datefinteletravail)>$datefinmaxi)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "La durée de la convention est supérieure à la durée maximale autorisée : $dureemax an(s).";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($erreur));
                $dateok = false;
            }
        }

        
        if (str_pad('',14,'0') == $tabteletravail)
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Aucun jour de télétravail sélectionné.";
        }
        // On regarde si le nombre de 1/2 journée selectionné est conforme au temps partiel/temps complet de l'agent
        else
        {
            // On compte le nombre de 1 dans le tableau
            $nbdemiejournee = substr_count($tabteletravail, '1');
                        
            // On divise par 2 le nombre de 1/2 journée pour trouver le nombre de journée
            if (($nbdemiejournee/2) > $nbjoursmaxteletravailcalcule)
            {
                if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                $erreur = $erreur . "Le nombre de jours de télétravail sélectionné dépasse le maximum autorisé.";
            }
        }

        if ($dateok)
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
            if (count($listeconventionchevauche)>0)
            {
                $alerte = $alerte . "Attention : Plusieurs conventions se chevauchent. Elles seront adapatées au moment de la validation par le responsable.";
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
            
            if (!is_null($agentid))
            {
                // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $agent_eppn = $agent->eppn();
                
                // On récupère le mail de l'agent en cours
                $agent_mail = $agent->mail(); // $agent->ldapmail();
            }
            
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
                'formDatas' => "{}"
            );
                
            $taberrorcheckmail = $fonctions->ckecksignataireteletravailliste($params,$agent,$maxniveau);
            if (count($taberrorcheckmail) > 0)
            {
                // var_dump("errorcheckmail = $errorcheckmail");
                $errorcheckmailstr = '';
                foreach ($taberrorcheckmail as $errorcheckmail)
                {
                    if (strlen($errorcheckmailstr)>0) $errorcheckmailstr = $errorcheckmailstr . '<br>';
                    $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
                }
                $erreur = "Impossible d'enregistrer la convention de télétravail car <br>$errorcheckmailstr";
            }
            else
            {
                $id_model = trim($fonctions->getidmodelteletravail($maxniveau, $agent));
                if (trim($id_model) == '')
                {
                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
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
                    $json = curl_exec($curl);
                    $error = curl_error ($curl);
                    curl_close($curl);
                    if ($error != "")
                    {
                        echo $fonctions->showmessage(fonctions::MSGERROR, "Erreur Curl = " . $error);
                    }
                    //echo "<br>" . print_r($json,true) . "<br>";
                    //echo "<br>"; var_dump($json); echo "<br>";
                    $id = json_decode($json, true);
                    error_log(basename(__FILE__) . " " . var_export($opts, true));
                    error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE CREATION CONVENTION -- " . var_export($id, true));
                    //var_dump($id);
                    if (is_array($id))
                    {
                        $erreur = "La création de la convention dans eSignature a échoué => " . print_r($id,true);
                        error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                    }
                    elseif ("$id" < 0)
                    {
                        $erreur =  "La création de la convention dans eSignature a échoué (numéro demande eSignature négatif = $id) !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.";
                        error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                    }
                    elseif ("$id" <> "")
                    {
                        //echo "Id de la nouvelle demande = " . $id . "<br>";
                        $teletravail->esignatureid($id);
                        $teletravail->esignatureurl($eSignature_url . "/user/signrequests/".$id);
                        $teletravail->statut(teletravail::TELETRAVAIL_ATTENTE);
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
        }
    }
    
    $inputtypeconv = null;
    $inputdatedebut = null;
    $inputdatefin = null;
    $inputtabteletravail = null;
    
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

    if (is_null($agentid))
    {
        echo "<form name='demandeforagent'  method='post' action='gestion_teletravail.php'>";
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentteletravail'  method='post' >";
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='' class='agent' /> ";
?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
 <?php
        echo "<br>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    }
    elseif (!is_null($agentid))
    {
    	$erreur = $agent->synchroteletravail();
    	if ($erreur != "")
    	{
    	    echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible de synchroniser une ou plusieurs convention : $erreur");
    	    $disablesubmit = true;
    	}
    	$teletravailliste = $agent->teletravailliste('01/01/1900', '31/12/2100'); // On va récupérer toutes les demandes de télétravail de l'agent pour les afficher
    	if (count($teletravailliste) > 0)
    	{
    	    $nbrelignetableauconvention=count($teletravailliste);
    	    echo "<form name='form_teletravail_delete' id='form_teletravail_delete' method='post' >";
    	    echo "<table class='tableausimple' id='listeteletravail'>";
    	    echo "<tr><center><td class='titresimple'>Identifiant</td>
                      <td class='titresimple'>Date début</td>
                      <td class='titresimple'>Date fin</td>
                      <td class='titresimple' id ='convstatut'>Statut</td>
                      <td class='titresimple'>Répartition du télétravail</td>
                      <td class='titresimple'>Id. externe</td>
                      <td class='titresimple'>URL eSignature</td>
                 ";
            echo "<td class='titresimple'>Annuler</td><td class='titresimple'>Générer le PDF</td>";
            echo "</center></tr>";
    	    foreach($teletravailliste as $teletravailid)
    	    {
    	        $teletravail = new teletravail($dbcon);
    	        $teletravail->load($teletravailid);
    	        $datedebutteletravail = $fonctions->formatdate($teletravail->datedebut());
    	        $datefinteletravail = $fonctions->formatdate($teletravail->datefin());
    	        $calendrierid_deb = "date_debut_conv";
    	        $calendrierid_fin = "date_fin_conv";
    	            	        
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
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $teletravailid . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $teletravailid .']'?> size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
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
?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $teletravailid . ']' ?>
        	size=10
        	minperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()-1 . $fonctions->debutperiode()); ?>'
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
                    $openspan = "<span data-tip=" . chr(34) . $teletravail->commentaire() . chr(34) . ">";
                    $closespan = "</span>";
                }
                echo "    <td class='cellulesimple convstatut' ><span class='convstatutvalue' hidden>" .  $teletravail->statut() . "</span><center>$openspan" . $fonctions->teletravailstatutlibelle($teletravail->statut()) . "$closespan</center></td>";
    	        $somme = 0;
    	        $htmltext = "";
    	        echo "    <td class='cellulesimple'><center>";
    	        for ($index = 0 ; $index < strlen($teletravail->tabteletravail()) ; $index ++)
    	        {
    	            $demijrs = substr($teletravail->tabteletravail(),$index,1);
    	            if ($demijrs>0) // Si dans le tableau la valeur est > 0
    	            {
    	                if (($index % 2) == 0)  // Si c'est le matin => On ajoute 1 à la somme
    	                    $somme = $somme + 1;
    	                elseif (($index % 2) == 1)  // Si c'est l'après-midi => On ajoute 2 à la somme
    	                    $somme = $somme + 2;
    	            }
    	            if (($index % 2) == 1)
    	            {
    	                if ($somme > 0) // Si pas de télétravail => On affiche rien
    	                {
                            if ($somme == 1)  // Que le matin
                               $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment(fonctions::MOMENT_MATIN); // => intdiv($index,2)+1 car pour PHP 0 = dimanche et nous 0 = lundi
    	                    elseif ($somme == 2) // Que l'après-midi
    	                       $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $fonctions->nommoment(fonctions::MOMENT_APRESMIDI);
    	                    elseif ($somme == 3) // Toute la journée
    	                       $htmltext = $htmltext . $fonctions->nomjourparindex(intdiv($index,2)+1);
    	                    else // Là, on ne sait pas !!
    	                       $htmltext = $htmltext . "Problème => index = $index  demijrs = $demijrs   somme = $somme";
     	                    
    	                    $htmltext = $htmltext . ", ";
   	                    }
     	                $somme = 0;
   	                }
    	        }
    	        echo substr($htmltext, 0, strlen($htmltext)-2);
    	        echo "    </center></td>";
    	        echo "<td class='cellulesimple'>" . $teletravail->esignatureid() . "</td>";
    	        echo "<td class='cellulesimple'><a href='" . $teletravail->esignatureurl() . "' target='_blank'>".(($teletravail->statut() == teletravail::TELETRAVAIL_ANNULE) ? '':$teletravail->esignatureurl())."</a></td>";
    	        echo "<td class='cellulesimple'><center><input type='checkbox' value='" . $teletravail->teletravailid() .  "' id='" . $teletravail->teletravailid()  .  "' name='cancel[]' ";
    	        if ($mode=='gestrh' and $teletravail->statut()==teletravail::TELETRAVAIL_ANNULE)
    	        {
    	            echo " disabled='disabled' ";
    	        }
    	        elseif ($mode!='gestrh' and in_array($teletravail->statut(), array(teletravail::TELETRAVAIL_ANNULE,teletravail::TELETRAVAIL_REFUSE, teletravail::TELETRAVAIL_VALIDE)))
    	        {
    	            echo " disabled='disabled' ";
    	        }
    	        echo "></center></td>";

    	        echo "<td class='cellulesimple'><center><input type='submit' value='Générer le PDF' name='genererpdf[" . $teletravail->teletravailid() . "]' ";
    	        if (trim($teletravail->esignatureid())=='' or trim($teletravail->esignatureurl())=='' or $teletravail->statut() == teletravail::TELETRAVAIL_ANNULE)
    	        {
    	            echo " disabled='disabled' ";
    	        }
    	        echo "></center></td>";
                echo "</tr>";
    	    }
            echo "</table>";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    	    echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
    	    echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
    	    if (!$disablesubmit)
    	    {
    	       echo "<input type='submit' value='Soumettre' name='modification'/>";
    	    }
    	    echo "</form>";
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
    	echo "<br><br>";
        //echo "<br>inputtypeconv = $inputtypeconv <br>";
    	echo "Création d'une nouvelle convention de télétravail pour : " . $agent->identitecomplete()  . " <br>";
    	echo "<form name='form_teletravail_creation' id='form_teletravail_creation' method='post' >";
    	
    	echo "Type de convention télétravail : ";
    	echo "<select id='typeconv' name='typeconv'>";
    	echo "<option value=''>---- Sélectionnez le type de convention ----</option>";

    	if (count($teletravailliste)==0)
    	{
        	echo "<option value='" . teletravail::CODE_CONVENTION_INITIALE . "'";
        	if ($inputtypeconv == teletravail::CODE_CONVENTION_INITIALE) echo " selected ";
        	echo ">" . teletravail::TYPE_CONVENTION_INITIALE . "</option>";
    	}
    	else
    	{
    	    echo "<option value='" . teletravail::CODE_CONVENTION_RENOUVELLEMENT . "'";
    	    if ($inputtypeconv == teletravail::CODE_CONVENTION_RENOUVELLEMENT) echo " selected ";
        	echo ">" . teletravail::TYPE_CONVENTION_RENOUVELLEMENT . "</option>";
    	}
    	
    	echo "<option value='" . teletravail::CODE_CONVENTION_MEDICAL . "'";
    	if ($inputtypeconv == teletravail::CODE_CONVENTION_MEDICAL) echo " selected ";
    	echo ">" . teletravail::TYPE_CONVENTION_MEDICAL . "</option>";
    	
    	echo "</select>";
    	echo "<br>";
    	
    	$datedebutminconv = date('d/m/Y');
    	$datedebutmaxconv = date('d/m/') . (date('Y')+1);
    	$datefinminconv = date('d/m/Y');
    	$datefinmaxconv = date('d/m/') . (date('Y')+4);

    	echo "<span data-tip=" . chr(34) . "Ceci est la date de début souhaitée. La date d'effet de la convention ne pourra être antérieure à la date de signature par le responsable." . chr(34) . ">Date de début de la convention télétravail : ";   //La date d'effet de la convention ne peut-être antérieure à la date de signature par le responsable
    	if ($fonctions->verifiedate($inputdatedebut)) {
    	    $inputdatedebut = $fonctions->formatdate($inputdatedebut);
    	}
    ?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $agent->agentid() . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $agent->agentid() .']'?> size=10
        	minperiode='<?php echo $fonctions->formatdate($datedebutminconv); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($datedebutmaxconv); ?>'
        	value='<?php echo $inputdatedebut ?>'>
    <?php
        echo "</span>";
    	echo "<br>";
    	echo "Date de fin de la convention télétravail : ";
    	if ($fonctions->verifiedate($inputdatefin)) {
    	    $inputdatefin = $fonctions->formatdate($inputdatefin);
        }      
    ?>
        <input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $agent->agentid() . ']' ?>
        	size=10
        	minperiode='<?php echo $fonctions->formatdate($datefinminconv); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($datefinmaxconv); ?>'
        	value='<?php echo $inputdatefin ?>'>
    <?php
        $teletravail = new teletravail($dbcon);
        if (strlen($inputtabteletravail . "")>0)
        {
            $teletravail->statut(teletravail::TELETRAVAIL_VALIDE); // important car sinon le télétravail n'est pas actif et donc c'est jamais télétravaillé
            $teletravail->tabteletravail($inputtabteletravail);
        }
        echo "<br>";
        
        $affectation = null;
        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y'), true);
        if (count($affectationliste)==0)
        {
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous n'avez pas d'affectation actuellement. Impossible de déclarer une convention de télétravail.");
            $disablesubmit = true;
        }
        else
        {
            $affectation = current($affectationliste);
            
            if ($affectation->quotite()=='100%')
            {
                $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail;
                echo "Jours de télétravail : Vous êtes à temps complet. Vous avez jusqu'à " . $nbjoursmaxteletravailcalcule . " jour(s) de télétravail.";
                echo "<table class='tableausimple'>";
                echo "<tr><center>";
                
                for ($cpt=1 ; $cpt<6 ; $cpt++)
                {
                    echo "    <td class='cellulesimple'><input type='checkbox' value='$cpt' id='creation_$cpt' name='jours[]'";
                    if ($teletravail->estjourteletravaille($cpt)) echo " checked ";
                    echo ">" . $fonctions->nomjourparindex($cpt) . "</input></td>";
                    
                }
                echo "</center></tr>";
                echo "</table>";
            }
            else
            {
                $nbredemiTP = (10 - ($affectation->quotitevaleur() * 10));
                $nbjoursmaxteletravailcalcule = $nbjoursmaxteletravail-($nbredemiTP*0.5);
                if ($nbjoursmaxteletravailcalcule<0) $nbjoursmaxteletravailcalcule = 0;
                
                if ($nbjoursmaxteletravailcalcule==0)
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
                    }
                }
                // A ce niveau $declaration est soit NULL soit il vaut la declaration de TP active
                if (is_null($declaration))
                {
                    echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous n'avez pas de déclaration de temps partiel active.");
                }
                    
                echo "Jours de télétravail : Vous êtes à " . $affectation->quotite() . ". Vous avez jusqu'à " . $nbjoursmaxteletravailcalcule . " jour(s) de télétravail.";
                echo "<table class='tableausimple'>";
                echo "<tr><center>";
                
                $moment = fonctions::MOMENT_MATIN;
                for ($cpt=0 ; $cpt<10 ; $cpt ++)
                {
                    //var_dump ("cpt = $cpt");
                    $indexjour = intdiv($cpt,2)+1;
                    //var_dump ("indexjour = $indexjour");
                    if ($indexjour >= 6)
                        break;
                    $fermeinput = "";
                    $fermespan = '';
                    echo "    <td class='cellulesimple' ";
                    
                    if (!is_null($declaration))
                    {
                        // Si l'agent n'est jamais en TP (semaine paire/impaire et matin/après-midi pour le jour courant)
                        // On affiche le jour complet pour le télétravail
                        if (!$declaration->enTPindexjour($indexjour,fonctions::MOMENT_MATIN,true) and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_MATIN,false)
                        and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_APRESMIDI,true) and !$declaration->enTPindexjour($indexjour,fonctions::MOMENT_APRESMIDI,false))
                        {
                            echo "><input type='checkbox' value='$indexjour' id='creation_$indexjour' name='jours[]'";
                            if ($teletravail->estjourteletravaille($indexjour)) echo " checked ";
                            echo ">" . $fonctions->nomjourparindex($indexjour) . "</input></td>";
                            $cpt++;
                        }
                        else
                        {
                            if ($declaration->enTPindexjour($indexjour,$moment,true) or $declaration->enTPindexjour($indexjour,$moment,false))
                            {
                                $fermespan = "</span>";
                                echo " style='background: " . TABCOULEURPLANNINGELEMENT['tppar']['couleur']  . " ;' >";
                                echo "<span data-tip=" . chr(34) . TABCOULEURPLANNINGELEMENT['tppar']['libelle'] . chr(34);
                            }
                            else
                            {
                                $fermeinput  = "</input>";
                                echo "><input type='checkbox' value='" . ($cpt+1) . "' id='creation_" . ($cpt+1) . "' name='demijours[]'";
                                if ($teletravail->estjourteletravaille($indexjour,$moment)) echo " checked ";
                            }
                            echo ">" . $fonctions->nomjourparindex($indexjour) . " " . $fonctions->nommoment($moment) . " $fermeinput $fermespan</td>";
                        }
                    }
                    else  // On est a temps partiel mais on n'a pas de déclaration de TP ==> On affiche la 1/2 journée (matin/après) de la journée
                    {
                        echo "><input type='checkbox' value='" . ($cpt+1) . "' id='creation_" . ($cpt+1) . "' name='demijours[]'";
                        if ($teletravail->estjourteletravaille($indexjour,$moment)) echo " checked ";
                        echo ">" . $fonctions->nomjourparindex($indexjour) . " " . $fonctions->nommoment($moment) . " </input></td>";
                    }
                    if ($moment == fonctions::MOMENT_MATIN)
                    {
                        $moment = fonctions::MOMENT_APRESMIDI;
                    }
                    else
                    {
                        $moment = fonctions::MOMENT_MATIN;
                    }
                }
                echo "</center></tr>";
                echo "</table>";
            }
        }
	    echo "<br>";
	    echo "<input type='hidden' id='nbjoursmaxteletravailcalcule' name='nbjoursmaxteletravailcalcule' value='" . $nbjoursmaxteletravailcalcule . "'>";
	    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
	    echo "<input type='hidden' id='agentid' name='agentid' value='" . $agent->agentid() . "'>";
	    echo "<input type='hidden' id='nbrelignetableauconvention' name='nbrelignetableauconvention' value='". $nbrelignetableauconvention . "'>";
	    echo "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
	    echo "<input type='hidden' id='datedebutminconv' name='datedebutminconv' value='" . $datedebutminconv . "'>";
	    echo "<input type='hidden' id='datedebutmaxconv' name='datedebutmaxconv' value='" . $datedebutmaxconv . "'>";
	    echo "<input type='hidden' id='datefinminconv' name='datefinminconv' value='" . $datefinminconv . "'>";
	    echo "<input type='hidden' id='datefinmaxconv' name='datefinmaxconv' value='" . $datefinmaxconv . "'>";
	    
	    if (!$disablesubmit)
	    {
	       echo "<input type='submit' value='Soumettre'  name='creation'/>";
	    }
	    echo "</form>";
    }
?>
</body>
</html>