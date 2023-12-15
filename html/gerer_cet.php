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
    
    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["agentid"])) 
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        if (! is_numeric($agentid)) 
        {
            $agentid = null;
            $agent = null;
        }
        else 
        {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } 
    else 
    {
        $agentid = null;
        $agent = null;
    }

    $mode = null;
    if (isset($_POST["mode"]))
        $mode = $_POST["mode"];

    $nbr_jours_cet = null;
    if (isset($_POST["nbr_jours_cet"]))
        $nbr_jours_cet = str_ireplace(",", ".", $_POST["nbr_jours_cet"]);

    if (isset($_POST["nbrejoursdispo"]))
        $nbrejoursdispo = $_POST["nbrejoursdispo"];
    else
        $nbrejoursdispo = null;

    if (isset($_POST["typeretrait"]))
        $typeretrait = $_POST["typeretrait"];
    else
        $typeretrait = null;

    $ajoutcet = null;
    if (isset($_POST["ajoutcet"]))
        $ajoutcet = $_POST["ajoutcet"];

    $retraitcet = null;
    if (isset($_POST["retraitcet"]))
        $retraitcet = $_POST["retraitcet"];

    $nocheck = 'no';
    if (isset($_POST["nocheck"]))
        $nocheck = $_POST["nocheck"];


    $msg_erreur = "";

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    //print_r($_POST); echo "<br><br>";

    $path = $fonctions->imagepath() . "/chargement.gif";
    list($width, $height) = getimagesize("$path");
    $typeimage = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
    echo "<div id='waiting_div' class='waiting_div' ><img id='waiting_img' src='" . $base64 . "' height='$height' width='$width' ></div>";
    // On force l'affichage de l'image d'attente en vidant le cache PHP vers le navigateur
    if (ob_get_contents()!==false)
    {
        ob_end_flush();
        @ob_flush();
        flush();
        ob_start();
    }
    // Fin du forçage de l'affichage de l'image d'attente

    
    if (strcasecmp($mode, "gestrh") == 0) {
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentcet'  method='post' >";

        $agentsliste = $fonctions->listeagentsg2t();
        echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
        echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
        foreach ($agentsliste as $key => $identite)
        {
            echo "<option value='$key'>$identite</option>";
        }
        echo "</select>";
        
/*        
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
        if (isset($_POST["agent"]))
            echo $_POST["agent"];
        echo "' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='";
        if (isset($_POST["agentid"]))
            echo $_POST["agentid"];
        echo "' class='agent' /> ";
        ?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
<?php
*/
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' name='btnselectagent' id='btnselectagent' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
        echo "<br>";
        echo "<br>";
        echo "<form name='allagentcet'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' name='btnallagent' id='btnallagent' class='g2tbouton g2tsuivantbouton g2tboutonwidthauto' value='Tous les agents' >";
        echo "</form>";
        echo "<br>";
        echo "<br>";
    }
    
    $alimid = null;
    if (isset($_POST["alimid"]))
    {
        $alimid = $_POST["alimid"];
    }
    
    $optionid = null;
    if (isset($_POST["optionid"]))
    {
        $optionid = $_POST["optionid"];
    }
    
    if (!is_null($alimid))
    {
        $alimcet = new alimentationCET($dbcon);
        $alimcet->load($alimid);
        $alimcet->storepdf();
        $alimcet = null;
    }
    if (!is_null($optionid))
    {
        $optioncet = new optionCET($dbcon);
        $optioncet->load($optionid);
        $optioncet->storepdf();
        $optioncet = null;
    }
    
    $alimCETliste = array();
    echo "Liste des demandes d'alimentation de CET : <br>";
    if (isset($_POST["btnselectagent"]) or isset($_POST["btnallagent"]))
    {
        if (! is_null($agent)) 
        {
            $alimCETliste = $agent->getDemandesAlim('ann' . substr($fonctions->anneeref()-1,2,2));     //getDemandesOption
        }
        else
        {
            $alimCETliste = $fonctions->get_alimCET_liste('ann' . substr($fonctions->anneeref()-1,2,2));
        }
    }
    //var_dump($alimCETliste);
    $htmltext = '';
    foreach ($alimCETliste as $esignatureid)
    {
        if ($htmltext == '')
        {
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Agent</td><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Type de demande</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td><td class='titresimple'>PDF</td>";
            $htmltext = $htmltext . "</tr>";
        }
        
        $alimcet = new alimentationCET($dbcon);
        $alimcet->load($esignatureid);
        if (!is_null($agent))
        {
            $agentalim = $agent;
        }
        else
        {
            $agentalim = new agent($dbcon);
            $agentalim->load($alimcet->agentid());
        }
        
        if (($alimcet->statut() == alimentationCET::STATUT_EN_COURS) or ($alimcet->statut() == alimentationCET::STATUT_PREPARE))
        {
            $statut = $alimcet->statut() . '<br>';
            
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');

            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $alimcet->esignatureid(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $json = curl_exec($curl);
            $error = curl_error ($curl);
            curl_close($curl);
            if ($error != "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
            }
            $response = json_decode($json, true);
            $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
            $statut = $statut . "En attente de : ";
            foreach ((array)$currentstep['recipients'] as $recipient)
            {
                $statut = $statut . "<br>" . $recipient['user']['firstname'] . " " . $recipient['user']['name'];
            }
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentalim->identitecomplete() . "</td><td class='cellulesimple'>" . $alimcet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $statut . "</td><td class='cellulesimple'>" . $fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>";
        }
        else
        {
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentalim->identitecomplete() . "</td><td class='cellulesimple'>" . $alimcet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $alimcet->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>";
        }
        $htmltext = $htmltext . "<td class='cellulesimple'><form name='alim_" . $alimcet->esignatureid() . "'  method='post' >";
        $htmltext = $htmltext . "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='mode' value='" . $mode . "'>";
        if (isset($_POST["agentid"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $_POST["agentid"] . "'>";
        }
        if (isset($_POST["agent"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agent' value='" . $_POST["agent"] . "'>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='alimid' value='" . $alimcet->esignatureid() . "'>";
        $htmltext = $htmltext . "<input type='submit' name='alim_" . $alimcet->esignatureid()  . "' class='g2tbouton g2tdocumentbouton' value='Générer'";
        if ($alimcet->statut() != alimentationCET::STATUT_VALIDE and $alimcet->statut() != alimentationCET::STATUT_REFUSE)
        {
            $htmltext = $htmltext . " disabled='disabled' ";
        }
        $htmltext = $htmltext . ">";
        $htmltext = $htmltext . "</form></td>";
        $htmltext = $htmltext . "</tr>";
    }
    $htmltext = $htmltext . "</table><br>";
    echo $htmltext;
    //var_dump($alimCETliste);
    echo "<br><br>";

    $optionCETliste=array();
    echo "Liste des demandes d'option sur CET : <br>";
    if (isset($_POST["btnselectagent"]) or isset($_POST["btnallagent"]))
    {
        if (! is_null($agent))
        {
            $optionCETliste = $agent->getDemandesOption($fonctions->anneeref());
        }
        else
        {
            $optionCETliste = $fonctions->get_optionCET_liste($fonctions->anneeref());
        }
    }
    $htmltext = '';
    foreach ($optionCETliste as $esignatureid)
    {
        $optioncet = new optionCET($dbcon);
        $optioncet->load($esignatureid);
        if ($htmltext == '')
        {
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Agent</td><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Année de référence</td><td class='titresimple'>RAFP</td><td class='titresimple'>Indemnisation</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td><td class='titresimple'>PDF</td>";
            $htmltext = $htmltext . "</tr>";
        }
        
        if (!is_null($agent))
        {
            $agentoption = $agent;
        }
        else
        {
            $agentoption = new agent($dbcon);
            $agentoption->load($optioncet->agentid());
        }
        
        if (($optioncet->statut() == optionCET::STATUT_EN_COURS) or ($optioncet->statut() == optionCET::STATUT_PREPARE))
        {
            $statut = $optioncet->statut() . '<br>';
            
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
            
            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $optioncet->esignatureid(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $json = curl_exec($curl);
            $error = curl_error ($curl);
            curl_close($curl);
            if ($error != "")
            {
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur Curl =>  " . $error));
            }
            $response = json_decode($json, true);
            $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
            $statut = $statut . "En attente de : ";
            foreach ((array)$currentstep['recipients'] as $recipient)
            {
                $statut = $statut . "<br>" . $recipient['user']['firstname'] . " " . $recipient['user']['name'];
            }
            
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentoption->identitecomplete() . "</td><td class='cellulesimple'>" . $optioncet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $optioncet->anneeref() . "</td><td class='cellulesimple'>" . $optioncet->valeur_i() . "</td><td class='cellulesimple'>" . $optioncet->valeur_j() . "</td><td class='cellulesimple'>" . $statut . "</td><td class='cellulesimple'>" . $fonctions->formatdate($optioncet->datestatut()) . "</td><td class='cellulesimple'>" . $optioncet->motif() . "</td><td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td>";
        }
        else
        {
            $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $agentoption->identitecomplete() . "</td><td class='cellulesimple'>" . $optioncet->esignatureid() . "</td><td class='cellulesimple'>" . $fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $optioncet->anneeref() . "</td><td class='cellulesimple'>" . $optioncet->valeur_i() . "</td><td class='cellulesimple'>" . $optioncet->valeur_j() . "</td><td class='cellulesimple'>" . $optioncet->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($optioncet->datestatut()) . "</td><td class='cellulesimple'>" . $optioncet->motif() . "</td><td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td>";
        }
        $htmltext = $htmltext . "<td class='cellulesimple'><form name='option_" . $optioncet->esignatureid() . "'  method='post' >";
        $htmltext = $htmltext . "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='mode' value='" . $mode . "'>";
        if (isset($_POST["agentid"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $_POST["agentid"] . "'>";
        }
        if (isset($_POST["agent"]))
        {
            $htmltext = $htmltext . "<input type='hidden' name='agent' value='" . $_POST["agent"] . "'>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='optionid' value='" . $optioncet->esignatureid() . "'>";
        $htmltext = $htmltext . "<input type='submit' name='option_" . $optioncet->esignatureid()  . "' class='g2tbouton g2tdocumentbouton' value='Générer' ";
        if ($optioncet->statut() != optioncet::STATUT_VALIDE and $optioncet->statut() != optionCET::STATUT_REFUSE )
        {
            $htmltext = $htmltext . " disabled='disabled' ";
        }
        $htmltext = $htmltext . ">";
        $htmltext = $htmltext . "</form></td>";
        $htmltext = $htmltext . "</tr>";
    }
    $htmltext = $htmltext . "</table><br>";
    echo $htmltext;
 
    //var_dump($optionCETliste);
    echo "<br><br>";
    
 ?>
    <script>
        window.addEventListener("load", (event) => {
            var waiting_img = document.getElementById('waiting_img');
            if (waiting_img)
            {
                waiting_img.hidden=true;
            }
            var waiting_div = document.getElementById('waiting_div');
            if (waiting_div)
            {
                waiting_div.hidden=true;
            }
        });
    </script>
<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

