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

    ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");
    echo "<br>";
    
    $listeteletravail_attente = array();
    $listeteletravail_refusee = array();
    $listeteletravail_annulee = array();
    $listeteletravail_attente = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ATTENTE);
    //$listeteletravail_refusee = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_REFUSE);
    //$listeteletravail_annulee = $fonctions->listeconventionteletravailavecstatut(teletravail::TELETRAVAIL_ANNULE);
    
    $listeteletravail = array_merge($listeteletravail_attente,$listeteletravail_refusee,$listeteletravail_annulee);
    
    echo "Affichage des conventions de télétravail en attente de traitement (" . count($listeteletravail)  . " demande(s))<br>";
    $premiereligne = true;
    echo "<table class='tableausimple'>";
    foreach($listeteletravail as $teletravail)
    {
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents("La convention en cours de traitement est : " . $teletravail->teletravailid()));

        if ($premiereligne)
        {
            echo "<tr>";
            echo "   <td class='titresimple'>Agent</td>";
            echo "   <td class='titresimple'>Identifiant G2T</td>";
            echo "   <td class='titresimple'>Date création</td>";
            echo "   <td class='titresimple'>Date début<br>souhaitée</td>";
            echo "   <td class='titresimple'>Date fin</td>";
            echo "   <td class='titresimple'>Type de convention</td>";
            echo "   <td class='titresimple'>Répartition des jours</td>";
            echo "   <td class='titresimple'>Statut</td>";
            // echo "   <td class='titresimple'>Motif</td>";
            echo "   <td class='titresimple'>Consulter</td>";
            echo "</tr>";
            $premiereligne = false;
        }

        $agent = new agent($dbcon);
        $agent->load($teletravail->agentid());
        $enattente = "";
        $enattente = $enattente . " de : ";
        $extraclass = "";
        if ($teletravail->esignatureid()<>"")
        {
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $teletravail->esignatureid(),
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
            //var_dump($response);
            if (isset($response['parentSignBook']['liveWorkflow']['currentStep']))
            {
                $currentstep = $response['parentSignBook']['liveWorkflow']['currentStep'];
                foreach ((array)$currentstep['recipients'] as $recipient)
                {
                    $signataireidentite = $recipient['user']['firstname'] . " " . $recipient['user']['name'];
                    if (trim($signataireidentite) != "")
                    {
                        $enattente = $enattente . "<br>" . $signataireidentite;
                    }
                }
            }
            else
            {
                $enattente = $enattente . "Impossible de déterminer l'acteur";
            }
        }
        elseif($teletravail->statutresponsable()==teletravail::TELETRAVAIL_ATTENTE)
        {
            $structure = new structure($dbcon);
            $structure->load($agent->structureid());
            //var_dump($structure->nomlong());
            if (!is_null($structure->responsable()) and ($structure->responsable()->agentid() == $agent->agentid()))
            {
                $responsable = $structure->resp_envoyer_a($codeinterne);
            }
            else
            {
                $responsable = $structure->agent_envoyer_a($codeinterne);
            }
            if (is_null($responsable))
            {
                $responsable = new agent($dbcon);
                $responsable->nom("INCONNU");
                $responsable->prenom("INCONNU");
                $extraclass = " celerror ";
            }
            
            $enattente = $enattente . "<br>" . ucwords(strtolower($responsable->prenom() . " " . $responsable->nom()));
        }
        echo "<tr>";
        echo "    <td class='cellulesimple'>" . $agent->identitecomplete() . "</td>";
        echo "    <td class='cellulesimple'>" . $teletravail->teletravailid() . "</td>";
        echo "    <td class='cellulesimple'>" . (($teletravail->creationg2t()=="") ? 'non renseignée':$fonctions->formatdate($teletravail->creationg2t())) . "</td>";
        echo "    <td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datedebut()) . "</td>";
        echo "    <td class='cellulesimple'>" . $fonctions->formatdate($teletravail->datefin()) . "</td>";
        $openspan = "";
        $closespan = "";
        if ($teletravail->typeconvention()==teletravail::CODE_CONVENTION_MEDICAL)
        {
            $motifmedical = "";
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
            if (strlen(trim($motifmedical))>0)
            {
                $openspan = "<span data-tip=" . chr(34) . htmlentities(substr(trim($motifmedical),0,strlen($motifmedical)-1)) . chr(34) . ">";
                $closespan = "</span>";
            }
        }
        echo "    <td class='cellulesimple'> $openspan " . $teletravail->libelletypeconvention() . "$closespan</td>";
        echo "    <td class='cellulesimple'>" . $teletravail->libelletabteletravail() . "</td>";
        echo "    <td class='cellulesimple $extraclass'>" . $fonctions->teletravailstatutlibelle($teletravail->statut()) . $enattente . "</td>";
        // echo "    <td class='cellulesimple'>" . $teletravail->commentaire() . "</td>";
        echo "    <td class='cellulesimple'><a href='" . $teletravail->esignatureurl() . "' target='_blank'>". $teletravail->esignatureurl()."</a></td>";
        echo "</tr>";
    }
    echo "</table>";

?>
</body>
</html>