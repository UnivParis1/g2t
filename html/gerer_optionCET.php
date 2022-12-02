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

    $valeur_a = null;
    if (isset($_POST["valeur_a"]))
        $valeur_a = $_POST["valeur_a"];
        
    $valeur_g = null;
    if (isset($_POST["valeur_g"]))
        $valeur_g = $_POST["valeur_g"];
        
    $valeur_h = null;
    if (isset($_POST["valeur_h"]))
        $valeur_h = $_POST["valeur_h"];
        
    $valeur_i = null;
    if (isset($_POST["valeur_i"]))
        $valeur_i = $_POST["valeur_i"];
        
    $valeur_j = null;
    if (isset($_POST["valeur_j"]))
        $valeur_j = $_POST["valeur_j"];
        
    $valeur_k = null;
    if (isset($_POST["valeur_k"]))
        $valeur_k = $_POST["valeur_k"];
        
    $valeur_l = null;
    if (isset($_POST["valeur_l"]))
        $valeur_l = $_POST["valeur_l"];
    
    $simul_a = null;
    if (isset($_POST["simul_a"]))
        $simul_a = $_POST["simul_a"];
        
    $simul_g = null;
    if (isset($_POST["simul_g"]))
        $simul_g = $_POST["simul_g"];
    
    $typeagent = null;
    if (isset($_POST["type_agent"]))
        $typeagent = $_POST["type_agent"];
    
    $simul_option = null;
    if (isset($_POST["simul_option"]))
        $simul_option = $_POST["simul_option"];
    
            
    // $esignatureid = null;
        
    $cree_option = null;
    if (isset($_POST["cree_option"]))
        $cree_option = $_POST["cree_option"];
        
    //$drh_niveau = null;
    //if (isset($_POST["drh_niveau"]))
    //    $drh_niveau = $_POST["drh_niveau"];
        
    //$responsable = null;
    //if (isset($_POST["responsable"]))
    //    $responsable = $_POST["responsable"];
                                                                                        
    //$esignatureid_get_info = null;
    //if (isset($_POST["esignatureid_get_info"]))
    //    $esignatureid_get_info = $_POST["esignatureid_get_info"];
    
    //$esignature_info = null;
    //if (isset($_POST["esignature_info"]))
    //    $esignature_info = $_POST["esignature_info"];
    

    $esignature_delete = null;
    if (isset($_POST["esignature_delete"]))
        $esignature_delete = $_POST["esignature_delete"];
        
    $esignatureid_delete = null;
    if (isset($_POST["esignatureid_delete"]))
        $esignatureid_delete = $_POST["esignatureid_delete"];
    
    $mode = "agent";
    if (isset($_POST["mode"]))
        $mode = $_POST["mode"];
    
        
    require ("includes/menu.php");

    $id_model = trim($fonctions->liredbconstante("IDMODELOPTIONCET"));  //    "251701";
    $eSignature_url = trim($fonctions->liredbconstante("ESIGNATUREURL"));  //   "https://esignature-test.univ-paris1.fr";

    $full_g2t_ws_url = trim($fonctions->get_g2t_ws_url()) . "/optionWS.php";
    //$sftpurl = $fonctions->liredbconstante('SFTPTARGETURL');
    $sftpurl = "";
    
    //echo "<br>sftpurl = $sftpurl <br><br>";
    
    //echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . "<br>";
    //echo "L'URL d'appel du WS G2T est : " . $full_g2t_ws_url;
/*
    echo "<br>" . print_r($_POST,true);
    echo "<br><br><br>";
*/    
    $anneeref = $fonctions->anneeref();
    
    // Si on est en mode 'rh' et qu'on n'a pas encore choisi l'agent, on affiche la zone de sélection.
    if (is_null($agentid) and $mode == 'rh')
    {
        echo "<form name='demandeforagent'  method='post' action='gerer_optionCET.php'>";
        echo "Personne à rechercher : <br>";
        echo "<form name='selectagentcet'  method='post' >";
        
        echo "<input id='agent' name='agent' placeholder='Nom et/ou prenom' value='";
        echo "' size=40 />";
        echo "<input type='hidden' id='agentid' name='agentid' value='";
        echo "' class='agent' /> ";
        ?>
        <script>
                $("#agent").autocompleteUser(
                        '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
                     	   wsParams: { allowInvalidAccounts: 1, showExtendedInfo: 1, filter_supannEmpId: '*'  } });
  	    </script>
    	<?php
        echo "<br>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
    }

    if (!is_null($agentid))
    {
    	$agent = new agent($dbcon);
    	$agent->load($agentid);
    	$agent->synchroCET();
    }
    
    $sauvegardeok = false;
    // Création d'un droit d'option
    if (!is_null($cree_option))
    {
        $agent = new agent($dbcon);
        $agent->load($agentid);
        if ((sizeof($agent->getDemandesAlim('', array(alimentationCET::STATUT_EN_COURS, alimentationCET::STATUT_PREPARE))) == 0)
            and (sizeof($agent->getDemandesOption('', array(optionCET::STATUT_EN_COURS, optionCET::STATUT_PREPARE)))== 0)
            and (sizeof($agent->getDemandesOption($fonctions->anneeref(), array(optionCET::STATUT_VALIDE)))== 0))
        {       // On vérifie au moment de traiter la demande d'option s'il n'y a pas de demande en cours (cas du F5 dans le navigateur ou du double onglet dans le navigateur)
                // et qu'il n'y a pas déjà eu une demande VALIDEE pour l'année en cours
        
            $optionCET = new optionCET($dbcon);
            $optionCET->agentid($agentid);
            $optionCET->anneeref($anneeref);
            $optionCET->valeur_a($valeur_a);
            $optionCET->valeur_g($valeur_g);
            $optionCET->valeur_h($valeur_h);
            $optionCET->valeur_i($valeur_i);
            $optionCET->valeur_j($valeur_j);
            $optionCET->valeur_k($valeur_k);
            $optionCET->valeur_l($valeur_l);
            
            error_log(basename(__FILE__) . " " . var_export($optionCET,true));
            
            if (!is_null($agentid))
            {
                // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $agent_eppn = $agent->eppn();
                
                // On récupère le mail LDAP de l'agent en cours
                $agent_mail = $agent->ldapmail();
            }

            // On appelle le WS eSignature pour créer le document
            $curl = curl_init();
            // echo "EPPN de l'agent => " . $agent_eppn . ". <br>";
            //$params = ['eppn' => "$agent_eppn"]; //, 'recipientEmails' => array("0*pacomte@univ-paris1.fr") , 'targetEmails' => array("pacomte@univ-paris1.fr", "pascal.comte@univ-paris1.fr")];  ///  exemple multi paramètre => $params = ['param1' => 'valeur1', 'param2' => 'valeur2', 'param3' => 'valeur3'];
            
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
                //'targetUrls' => array("$full_g2t_ws_url")
                //'targetUrls' => array($sftpurl . "/" . $agent->nom(). "_" . $agent->prenom(),"$full_g2t_ws_url")
                'targetUrl' => "$full_g2t_ws_url",
                'targetUrls' => array("$full_g2t_ws_url"),
                'formDatas' => "{}" 
                );
    
            // On récupère le responsable de la structure de l'agent - Niveau 2
            
            $pasresptrouve = false;
            $structid = $agent->structureid();
            $struct = new structure($dbcon);
            $struct->load($structid);
            $code = null;
            if ($struct->responsable()->agentid() == $agent->agentid())
            {
                $resp = $struct->resp_envoyer_a($code);
            }
            else
            {
                $resp = $struct->agent_envoyer_a($code);
            }
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" Le responsable de " . $agent->identitecomplete() . " est "  . $resp->identitecomplete()));
            if ($resp->agentid() != '-1')
            {
	            $params['recipientEmails'] = array
	            (
	                "1*" . $agent_mail,
	                "2*" . $resp->mail()
	            );
	    ////////////////////////////////////////////////////////
	    ////////// ATTENTION : POUR TEST UNIQUEMENT   //////////
	    ////////////////////////////////////////////////////////
	    //        $params['recipientEmails'] = array
	    //        (
	    //            "1*" . $agent_mail,
	    //            "2*elodie.briere@univ-paris1.fr"
	    //        );
	    ////////////////////////////////////////////////////////

	            $constantename = 'CETSIGNATAIRE';
	            $signataireliste = '';
	            $tabsignataire = array();
	            if ($fonctions->testexistdbconstante($constantename))
	            {
	                $signataireliste = $fonctions->liredbconstante($constantename);
	            }
	            if (strlen($signataireliste)>0)
	            {
	                $tabsignataire = $fonctions->cetsignatairetoarray($signataireliste);
	                if (!isset($tabsignataire['3']['1_-2'])) // Si gestion de tps n'est pas défini dans le niveau 3
	                {
	                    // On ajoute gestion de temps (utilisateur -2) dans le niveau 3
	                    $agentsignataire = new agent($dbcon);
	                    if ($agentsignataire->load(-2))
	                    {
	                        $params['recipientEmails'][] = "3*" . $agentsignataire->mail();
	                    }
	                    unset($agentsignataire);
	                }
	                
	                foreach ($tabsignataire as $niveau => $infosignataires)
	                {
	                    foreach ($infosignataires as $idsignataire => $infosignataire)
	                    {
	                        if ($infosignataire[0]==cet::SIGNATAIRE_AGENT)
	                        {
	                            $agentsignataire = new agent($dbcon);
	                            if ($agentsignataire->load($infosignataire[1]))
	                            {
	                                $params['recipientEmails'][] = $niveau . "*" . $agentsignataire->mail();
	                            }
	                        }
	                        elseif ($infosignataire[0]==cet::SIGNATAIRE_RESPONSABLE)
	                        {
	                            $structuresignataire = new structure($dbcon);
	                            $structuresignataire->load($infosignataire[1]);
	                            $agentsignataire = $structuresignataire->responsable();
	                            if ($agentsignataire->civilite()!='') // Si la civilité est vide => On a un problème de chargement du responsable
	                            {
	                                $params['recipientEmails'][] = $niveau . "*" . $agentsignataire->mail();
	                            }
	                        }
	                        elseif ($infosignataire[0]==cet::SIGNATAIRE_STRUCTURE)
	                        {
	                            $structuresignataire = new structure($dbcon);
	                            $structuresignataire->load($infosignataire[1]);
	                            $datedujour = date("d/m/Y");
	                            foreach ($structuresignataire->agentlist($datedujour, $datedujour,'n') as $agentsignataire)
	                            {
	                                $params['recipientEmails'][] = $niveau . "*" . $agentsignataire->mail();
	                            }
	                        }
	                        else
	                        {
	                            $fonctions->showmessage("TYPE DE SIGNATAIRE inconnu !",fonctions::MSGERROR);
	                        }
	                    }
	                }
	            }
	            
	            
/*	            
	            $resp_agent = null;
	            // On récupère tous les agents avec le profil RHCET - Niveau 3
	            foreach ( (array)$fonctions->listeprofilrh("1") as $qvt_agent) // RHCET
	            {
	                $params['recipientEmails'][] = '3*' . $qvt_agent->mail();
	                if (count((array)$qvt_agent->structrespliste())>0)
	                {
	                    $resp_agent = $qvt_agent;
	                }
	            }
	            
	            // On récupère le responsable du service QVT (Qualité de vie au travail) si on n'a pas identifié le responsable des agents RHCET - Niveau 4
	            $qvt_id = 'DGEE_4';  // Id = DGEE_4	    Nom long = Service santé, handicap, action culturelle et sociale        Nom court = DRH-SSHACS
	            if (is_null($resp_agent))
	            {
	                $struct = new structure($dbcon);
	                $struct->load($qvt_id);
	                $resp_agent = $struct->responsable();
	            }
	            $params['recipientEmails'][] = '4*' . $resp_agent->mail();
	            
	            // On récupère le responsable du service DRH et DGS - Niveau 5
	            $struct = new structure($dbcon);
	            $drh_id = 'DGE_3';  // Id = DGE_3     Nom long = Direction des ressources humaines        Nom court = DRH
	            $struct->load($drh_id);
	            $drh_agent = $struct->responsable();
	            $params['recipientEmails'][] = '5*' . $drh_agent->mail();
	            $struct = new structure($dbcon);
	            $dgs_id = 'DG_2';  // Id = DG_2     Nom long = Direction générale des services        Nom court = DGS
	            $struct->load($dgs_id);
	            $dgs_agent = $struct->responsable();
	            $params['recipientEmails'][] = '5*' . $dgs_agent->mail();
	            
	            // Ajout de Mme Emilie Ganné
	            $eganneid=91790;
	            $eganne = new agent($dbcon);
	            $eganne->load($eganneid);
	            $params['recipientEmails'][] = '5*' . $eganne->mail();
*/
	            
	            $walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
	                    is_array( $item )
	                    ? array_walk( $item, $walk, $key )
	                    : $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
	                    
	            };
	            array_walk( $params, $walk );
	            $params_string = implode( '&', $output );
	            //echo "<br>Output = " . $params_string . '<br><br>';
	            
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
	                echo "Erreur Curl = " . $error . "<br><br>";
	            }
	            //echo "<br>" . print_r($json,true) . "<br>";
	            $id = json_decode($json, true);
	            
	            //var_dump($id);
	            if (is_array($id))
	            {
	                $erreur = $id['error'];  
	            }
	            elseif ("$id" < 0)
	            {
	                $erreur =  "La création du droit d'option dans eSignature a échoué (numéro demande eSignature négatif = $id) !!==> Pas de sauvegarde du droit d'option dans G2T.<br><br>";
	            }
	            elseif ("$id" <> "")
	            {
	                //echo "Id de la nouvelle demande = " . $id . "<br>";
	                $optionCET->esignatureid($id);
	                $optionCET->esignatureurl($eSignature_url . "/user/signrequests/".$id);
	                $optionCET->statut($optionCET::STATUT_PREPARE);
	                
	                $erreur = $optionCET->store();
	                $agent->synchroCET();
	                $sauvegardeok = true;
	                
	            }
	            else
	            {
	                $erreur =  "La création du droit d'option dans eSignature a échoué !!==> Pas de sauvegarde du droit d'option dans G2T.<br><br>";
	            }
	            if ($erreur <> "")
	            {
	                if (is_array($id))
	                {
	                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur (création) = " . print_r($id,true)));
	                }
	                else
	                {
	                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur (création) = " . $erreur));
	                }
	                echo $fonctions->showmessage(fonctions::MSGERROR, "Erreur (création) = $erreur");
	                //echo "<b><p style='color:red';>Erreur (création) = $erreur <br></p></b>";
	            }
	            else
	            {
	                //var_dump($optionCET);
	                error_log(basename(__FILE__) . $fonctions->stripAccents(" La sauvegarde (création) s'est bien passée => eSignatureid = " . $id ));
	                //echo "La sauvegarde (création) s'est bien passée...<br><br>";
	            }
            }
            else // Le responsable est g2t cron
            {
                echo $fonctions->showmessage(fonctions::MSGWARNING, "Votre responsable n'est pas renseigné, veuillez contacter la DRH.");
            }
        }
        else // Il y a une demande d'alim ou d'option en cours
        {
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande d'alimentation ou de droit d'option sur CET en cours. Il n'est pas possible d'en avoir plusieurs en même temps.");
            //echo "Vous avez une demande d'alimentation ou de droit d'option sur CET en cours. Il n'est pas possible d'en avoir plusieurs en même temps.<br><br>";
        }
    }
    
    if (!is_null($esignature_delete))
    {
        // On appelle le WS de eSignature pour annuler la demande 
        // On synchronise ensuite le statut avec le WS G2T et on ajoute le commentaire via l'objet optionCET
        //echo "On va supprimer la demande " . $esignatureid_delete . '.<br>';

        // On resynchronise la demande au cas où ça aurait changé depuis l'affichage
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Synchronisation de la demande $esignatureid_delete avec eSIgnature avant suppression."));
        $fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$esignatureid_delete);
        
        $optionCET = new optionCET($dbcon);
        $optionCET->load($esignatureid_delete);
        
        if ($optionCET->statut() == OPTIONCET::STATUT_ABANDONNE or $optionCET->statut() == OPTIONCET::STATUT_VALIDE)
        {
            $error = "Le statut de la demande de droit d'option $esignatureid_delete est " . $optionCET->statut() . " ==> impossible de supprimer la demande.";
            echo "$error <br>";
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la suppression d'une demande de droit d'option ==> $error"));
        }
        else
        {
            $curl = curl_init();
            $params_string = "";
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $esignatureid_delete,
                CURLOPT_POSTFIELDS => $params_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ];
            //curl_setopt($curl, CURLOPT_PROXY, '');
    
            curl_setopt_array($curl, $opts);
            //echo "Les options CURL sont : " . print_r($opts,true) . "<BR><BR>";
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            $json = curl_exec($curl);
            $error = curl_error ($curl);
            curl_close($curl);
            if ($error != "")
            {
                echo "Erreur Curl = " . $error . "<br><br>";
                $error_suppr = "Erreur Curl = " . $error . "<br><br>";
            }
            //echo "<br>" . print_r($json,true) . "<br>";
            $response = json_decode($json, true);
            echo "<br>";
/*          
            echo '<pre>';
            var_dump($response);
            echo '</pre>';
            */
            if (!is_null($response))
            {
            	error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la suppression de la demande de droit d'option dans Esignature : ".var_export($response, true)));
            	$error_suppr = "Erreur lors de la suppression de la demande de droit d'option dans Esignature !!==> Pas de suppression dans G2T.<br><br>";
            }
            else
            {
            	if (stristr(substr($json,0,20),'HTML') === false)
            	{
		            $optionCET->motif("Annulation à la demande de " . $user->identitecomplete());
		            $optionCET->store();
		
		            error_log(basename(__FILE__) . $fonctions->stripAccents(" Synchronisation de la demande $esignatureid_delete après appel du WS eSignature de suppression."));
		            $fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$esignatureid_delete);
            	}
            	else 
            	{
            		$error_suppr = "Erreur lors de la suppression de la demande de droit d'option dans Esignature !!==> Pas de suppression dans G2T.<br><br>";
            		error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur de connexion à Esignature lors de la suppression de la demande de droit d'option dans Esignature : ".var_export($json, true)));
            	}
            }
        }
    }

    if (! is_null($agentid))
    {
        //echo "<br><hr size=3 align=center><br>";
        // Affichage des droits d'option CET dans la base G2T
        $optionCET = new optionCET($dbcon);
        $agent = new agent($dbcon);
        $agent->load($agentid);   
    
?>
        <script type="text/javascript">
/*
        function opendemande() {
        	demandeliste = document.getElementById("esignatureid_aff")
        	urldemande = demandeliste.value;
        	//alert("opendemande est activé : " + urldemande );
        	window.open(urldemande);
        	return false;
        }
*/
        
        function isInt(value) {
    		return !isNaN(value) && (function(x) { return (x | 0) === x; })(parseFloat(value))
    	}
        
        function update_case()
        {
        	//alert("Update Case est activé");
        	
        	// On récupère la valeur A
        	document.getElementById("valeur_a").value = document.getElementById("valeur_a").value.replace(",",".");
            valeur_a = document.getElementById("valeur_a").value;
            valeur_a = parseFloat(valeur_a);
            
            // On récupère la valeur G
        	document.getElementById("valeur_g").value = document.getElementById("valeur_g").value.replace(",",".");
           	valeur_g = document.getElementById("valeur_g").value;
            valeur_g = parseFloat(valeur_g);
            
        	// On récupère la valeur H 
        	document.getElementById("valeur_h").value = document.getElementById("valeur_h").value.replace(",",".");
        	valeur_h = document.getElementById("valeur_h").value;
        	valeur_h  = parseFloat(valeur_h);
    
            // On récupère la valeur I et on efface le label correspondant
        	document.getElementById("valeur_i").value = document.getElementById("valeur_i").value.replace(",",".");
           	valeur_i = document.getElementById("valeur_i").value;
        	label_i = document.getElementById("label_i");
        	if (label_i !== null)
        		label_i.innerHTML = "";
           	
           	// On récupère la valeur J et on efface le label correspondant
        	document.getElementById("valeur_j").value = document.getElementById("valeur_j").value.replace(",",".");
           	valeur_j = document.getElementById("valeur_j").value;
        	document.getElementById("label_j").innerHTML = "";
           	
           	// Les valeurs calculées K et L sont effacées et les labels correspondant également
     		document.getElementById("valeur_k").value = "";
           	document.getElementById("label_k").innerHTML = "";
     		document.getElementById("valeur_l").value = "";
     		document.getElementById("label_l").innerHTML = "";
           	
        	const button = document.getElementById('cree_option')
        	deactive_button = false;
    
        	//////////////////////////////////////////////////////
        	// Traitement de la valeur de la case I
        	//////////////////////////////////////////////////////
    		if (valeur_i == "")
    		{
        		deactive_button = true;
       		}
        	else if (isNaN(valeur_i))
        	{
        		//alert("La valeur de la case I n'est pas un nombre.");
        		if (label_i !== null)
        			label_i.innerHTML = "Le nombre de jours à prendre en compte au titre de la RAFP n'est pas un nombre valide.";
        		deactive_button = true;
        	}    	
        	else if (!isInt(valeur_i))
        	{
        		if (label_i !== null)
        			label_i.innerHTML = "Le nombre de jours à prendre en compte au titre de la RAFP doit être un entier.";
        		deactive_button = true;
        	}
        	else if (parseInt(valeur_i) < 0)
        	{
        		if (label_i !== null)
        			label_i.innerHTML = "Le nombre de jours à prendre en compte au titre de la RAFP doit être positif ou nul.";
        		deactive_button = true;
        	}
    
        	//////////////////////////////////////////////////////
        	// Traitement de la valeur de la case J
        	//////////////////////////////////////////////////////
    		if (valeur_j == "")
    		{
        		deactive_button = true;
       		}
        	else if (isNaN(valeur_j))
        	{
        		//alert("La valeur de la case J n'est pas un nombre.");
        		document.getElementById("label_j").innerHTML = "Le nombre de jours à indemniser n'est pas un nombre valide.";
        		deactive_button = true;
        	}    	
        	else if (!isInt(valeur_j))
        	{
        		document.getElementById("label_j").innerHTML = "Le nombre de jours à indemniser doit être un entier.";
        		deactive_button = true;
        	}
        	else if (parseInt(valeur_j) < 0)
        	{
         		document.getElementById("label_j").innerHTML = "Le nombre de jours à indemniser doit être positif ou nul.";
        		deactive_button = true;
        	}
    
     
            if (!deactive_button) // Le bouton est encore activé => Les valeurs saisies sont des nombres entiers positifs ou nuls
            {   
            	// On vérifie les contraintes de répartition
            
            	// On sait que I et J sont des nombres => On récupère leurs valeurs
                valeur_i = parseInt(valeur_i);
                valeur_j = parseInt(valeur_j);
                
            	debordementCET = 0;
            	// nbre de jours à indemniser ou à mettte sur RAFP
            	if (valeur_g > <?php echo $fonctions->liredbconstante('PLAFONDCET') ?>)
            	{
            		debordementCET = valeur_g - <?php echo $fonctions->liredbconstante('PLAFONDCET') ?>;   // <?php echo $fonctions->liredbconstante('PLAFONDCET') ?> => Nbre maxi sur le CET
            	}
            	
            	// S'il y a plus de jours que les 60 maximum => On doit forcément répartir ce "surplus" dans les case I (RAFP) et J (Indemnistation) 
            	if ((valeur_i + valeur_j) < debordementCET)
            	{
    	     		if (label_i !== null)
    	     		{
        				label_i.innerHTML = "La somme du nombre de jours à prendre en compte au titre de la RAFP et du nombre de jours à indemniser doit être supérieure ou égale à " + debordementCET + ".";
    	     			document.getElementById("label_j").innerHTML = label_i.innerHTML;
    	     		}
    	     		else
    	     		{
    	     			document.getElementById("label_j").innerHTML = "Le nombre de jours à indemniser doit être supérieur ou égal à " + debordementCET + ".";
    	     		}
            		deactive_button = true;
            	}
            }
            
            if (!deactive_button) // Le bouton est encore activé => Les répartitions sont bonnes
            {
    
            	// On calcule le nombre de jours à maintenir dans le CET (au dessus des 15 jours)
            	valeur_k = valeur_h - valeur_i - valeur_j;
    	       	
            	if (valeur_k < 0)  // On a demandé trop d'indemnisation ou trop de RAFP
            	{
        			if (label_i !== null)
        			{
        				label_i.innerHTML = "La somme du nombre de jours à prendre en compte au titre de la RAFP et du nombre de jours à indemniser doit être inférieure ou égale à " + valeur_h + ".";
    	     			document.getElementById("label_j").innerHTML = label_i.innerHTML;
    	     		}
    	     		else
    	     		{
    	     			document.getElementById("label_j").innerHTML = "Le nombre de jours à indemniser doit être inférieur ou égal à " + valeur_h + ".";
    	     		}
            		deactive_button = true;
            	}
            	
    
                valeur_l = valeur_k + 15;
            	if (valeur_l > <?php echo $fonctions->liredbconstante('PLAFONDCET') ?>)
            	{
             		document.getElementById("label_l").innerHTML = "La valeur de solde du CET après option doit être inférieure à <?php echo $fonctions->liredbconstante('PLAFONDCET') ?>.";
            		deactive_button = true;
    	    	}
            	else if ((valeur_l > (valeur_a + 10)) && (valeur_a >= 15))
            	{
    	     		document.getElementById("label_l").innerHTML = "Il n'est pas possible d'augmenter le solde du CET de plus de 10 jours."; // "Ancien solde = " + valeur_a + " / Nouveau solde = " + valeur_l  + " => Impossible d'accroite son CET de plus de 10 jours.";
            		deactive_button = true;
            	}
            }
            
            if (!deactive_button) // Le bouton est encore activé => tous les contrôles sont ok, on peut afficher les résultats des calculs
            {
    	    	document.getElementById("valeur_k").value = valeur_k;
    	        document.getElementById("valeur_l").value = valeur_l;
            }
            
        	button.disabled = deactive_button;
    
    
        }
    	</script>
<?php     
/*        
        echo "<br><hr size=3 align=center><br>";
        echo "<form name='simulation_option'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<div style='color: red;font-weight: bold;'>ATTENTION : A n'utiliser que pour des tests de vérification des specifications.</div>";
        
    
        echo "Saisir le solde du CET avant alimentation : <input type=text placeholder='Case A' name=simul_a id=simul_a size=3 value='$simul_a'><br>";
        echo "Saisir le solde du CET après alimentation : <input type=text placeholder='Case G' name=simul_g id=simul_g size=3 value='$simul_g'><br>";
        echo "Type d'agent : ";
        echo "<select name='type_agent' id='type_agent'>";
        echo "  <option value='titu'";
        if ($typeagent == 'titu')
            echo " selected ";
        echo ">Titulaire</option>";
        echo "  <option value='cont'";
        if ($typeagent == 'cont')
            echo " selected ";
        echo ">Contractuel</option>";
        echo "</select>";
        echo "<br><br>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' name='simul_option' id='simul_option' value='Soumettre' >";
        echo "</form>";
        
        echo "<br><br>";
        
        echo "<br><hr size=3 align=center><br>";
*/
        echo "Création d'une demande d'option sur CET pour " . $agent->identitecomplete() . "<br>";
        //echo 'Structure complète d\'affectation : '.$structure->nomcompletcet().'<br>';
        echo "<form name='creation_option'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
         
        $valeur_a = "";
        $valeur_g = "";
        $controleok = true;
        $errorcontroltxt = "";
        
        if (!is_null($simul_option))
        {
            $simul_a = str_replace(",",".",$simul_a);
            $simul_g = str_replace(",",".",$simul_g);
            //echo "Division entière de simul_a = " . intdiv($simul_a*10,10) . "  ceil(simul_a) = " . ceil($simul_a)   . "   float(simul_a)  = " . (float)$simul_a . "<br>";
            //echo "Division entière de simul_g = " . intdiv($simul_g*10,10) . "  ceil(simul_g) = " . ceil($simul_g)   . "  float(simul_g)  = " . (float)$simul_g . "<br>";
            
            //echo "Simul_A = $simul_a  Simul_G = $simul_g <br>";
            if (is_null($simul_a) or is_null($simul_g))
            {
                $errorcontroltxt = $errorcontroltxt . "Au moins une des cases A (solde CET avant alimentation) ou G (Solde CET après alimentation) est nulle !<br>Impossible de poursuivre le test<br>";
            }
            else if (!is_numeric($simul_a) or !is_numeric($simul_g))
            {
                $errorcontroltxt =  $errorcontroltxt ."Au moins une des cases A (solde CET avant alimentation) ou G (Solde CET après alimentation) n'est pas un nombre !<br>Impossible de poursuivre le test<br>";
            }
            else if ((ceil($simul_a) <> (float)$simul_a) or (ceil($simul_g) <> (float)$simul_g))
            {
                $errorcontroltxt =  $errorcontroltxt ."Au moins une des cases A (solde CET avant alimentation) ou G (Solde CET après alimentation) n'est pas un nombre entier!<br>Impossible de poursuivre le test<br>";
            }
            else if (($simul_a < 0) or ($simul_g < 0))
            {
                $errorcontroltxt =  $errorcontroltxt ."Au moins une des cases A (solde CET avant alimentation) ou G (Solde CET après alimentation) est un nombre négatif !<br>Impossible de poursuivre le test<br>"; 
            }
            else if ($simul_g < $simul_a)
            {
                $errorcontroltxt =  $errorcontroltxt ."La valeur de la case G (Solde CET après alimentation) doit être supéreure à la valeur de la case A (solde CET avant alimentation) !<br>Impossible de poursuivre le test<br>"; 
            }
            else
            {
                // On a forcer les valeurs de simulation
                $valeur_a = $simul_a;
                $valeur_g = $simul_g;
                $alimentation = $valeur_g - $valeur_a;
                $errorcontroltxt = $errorcontroltxt . "--------------------------------------------------------------------------<br>" .
                                                      "ATTENTION : Les valeurs des case A (solde CET avant alimentation) et G (Solde CET après alimentation) ont été forcées !!!<br>" .
                                                      "Certaines règles de gestion ne seront pas vérifiées.<br>" .
                                                      "--------------------------------------------------------------------------<br>";
            }
        }
        else
        {
            // ----------------------------------------------------------------------------------------
            // On fait tous les contrôles pour voir si l'agent peut deposer une demande d'option sur CET
            // ----------------------------------------------------------------------------------------
            
            // 1) Est-ce que la période de demande de droit d'option est ouverte
            // Si campagne en cours et pas de demande en cours
            $today = date('Ymd');
            $debutperiode = $fonctions->debutoptioncet();
            $finperiode = $fonctions->finoptioncet();
            //echo "La période afin d'exercer un droit d'option sur CET est comprise entre le " . $fonctions->formatdate($debutperiode) . " et le " . $fonctions->formatdate($finperiode)  . ".<br>";
            if ($today < $debutperiode || $today > $finperiode)
            {
                $errorcontroltxt = $errorcontroltxt . "La campagne de droit d'option du CET est fermée actuellement.<br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
                $controleok = false;
            }
            
            
            // 2) Est-ce que l'agent à un CET ==> Sinon pas de droit d'option possible
            $cet = new cet($dbcon);
            $erreur = $cet->load($agentid);
            if ($erreur <> "")
            {
                $errorcontroltxt = $errorcontroltxt . "Il n'y a pas de CET pour l'agent " . $agent->identitecomplete() . " <br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
                $controleok = false;
                $cet = null;
            }
            
            // 3) Est-ce qu'il a déja une demande d'option en cours pour l'annee de référence ==> On ne peut pas faire 2 demandes d'option en même tps
            $listid = $agent->getDemandesOption($fonctions->anneeref(),array(OPTIONCET::STATUT_EN_COURS, OPTIONCET::STATUT_PREPARE, OPTIONCET::STATUT_INCONNU));
            if (count($listid)>0)
            {
                $errorcontroltxt = $errorcontroltxt . "Il y a au moins une demande d'option sur CET pour l'agent " . $agent->identitecomplete() . " qui est en cours de traitement.<br>Il n'est donc pas possible d'établir une nouvelle demande de droit d'option.<br>";                
                $controleok = false;
            }
            // Si une dmeande est déjà validée pour la campagne en cours => Pas possible de refaire une demande.
            $listid = $agent->getDemandesOption($fonctions->anneeref(),array(OPTIONCET::STATUT_VALIDE));
            if (count($listid)>0)
            {
                $errorcontroltxt = $errorcontroltxt . "Il y a au moins une demande d'option sur CET pour l'agent " . $agent->identitecomplete() . " qui est validée pour cette campagne.<br>Il n'est donc pas possible d'établir une nouvelle demande de droit d'option.<br>";                
                $controleok = false;
            }
            
            
            // 4) Est-ce qu'une demande de droit d’alimentation de CET est en l’état “En cours” ou “En préparation”
            $typeconge = 'ann' . substr(($fonctions->anneeref()-1),2,2);
            //echo "Type annuel de congés  = $typeconge <br><br>";
            $listid = $agent->getDemandesAlim($typeconge,array(ALIMENTATIONCET::STATUT_EN_COURS, ALIMENTATIONCET::STATUT_PREPARE)); //, ALIMENTATIONCET::STATUT_INCONNU));
            if (count($listid)>0)
            {
                $errorcontroltxt = $errorcontroltxt . "Il y a au moins une demande d'alimentation sur CET pour l'agent " . $agent->identitecomplete() . " qui est en cours de traitement <br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
                $controleok = false;
            }
            
            // 5) Est-ce qu'il a une demande de congés sur CET en cours ==> Son solde sur CET n'est pas à jour.
            //echo "Date interval = " . ($fonctions->anneeref()-1) . $fonctions->debutperiode() . "<br><br>";
            $debutinterval = ($fonctions->anneeref()-1) . $fonctions->debutperiode();
            $fininterval = ($fonctions->anneeref()+1) . $fonctions->finperiode();
            $demandeliste = $agent->demandesliste($debutinterval, $fininterval);
            $demande = new demande($dbcon);
            foreach ((array)$demandeliste as $demande)
            {
                $statut = $demande->statut();
                if ((strcasecmp($statut, demande::DEMANDE_ATTENTE) == 0) and (strcasecmp($demande->type(),'cet')==0)) // Une demande de congés sur CET est en attente de validation ==> On ne peut pas saisir d'option sur CET
                {
                    $errorcontroltxt = $errorcontroltxt . "Il y a des demandes de congés sur CET pour l'agent " . $agent->identitecomplete() . " qui ne sont en cours de traitement <br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";                    
                    $controleok = false;
                }
            }
            if (count((array)$agent->CETaverifier($debutinterval))>0)
            {
                // Il y a des demandes de congés sur CET qui ne sont pas à jour => On s'arrète là
                $errorcontroltxt = $errorcontroltxt . "Il y a des demandes de congés sur CET pour l'agent " . $agent->identitecomplete() . " qui ne sont pas validées par le service de la DRH. Votre solde CET n'est donc pas correct <br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
                $controleok = false;
            }
            
            
            if (!is_null($cet))
            {
                $alimentation = $cet->cumulannuel($anneeref); // ATTENTION : Il faudra mettre $anneref - 1 car le droit d'option se fait l'année suivante !!!! A VERIFIER !!!!!
                //echo "Alimentation = XXXX" . $alimentation . "XXXX<br><br>";
                $valeur_a = $cet->cumultotal()-$cet->jrspris()-$alimentation;
                $valeur_g = $cet->cumultotal()-$cet->jrspris();
            }
            
            
            $affectation = new affectation($dbcon);
            $typeagent = '';
            $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y'));
            if (count((array)$affectationliste) == 0)
            {
                $errorcontroltxt = $errorcontroltxt . "L'agent " . $agent->identitecomplete() . " n'a d'affectation actuellement.<br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
                $controleok = false;
            }
            else
            {
                $affectation = current((array)$affectationliste);
                if ($affectation->numcontrat() <> 0)
                   $typeagent = 'cont';
                else
                   $typeagent = "titu";
            }
        }
        
        
        $valeur_h = (float)$valeur_g - 15;
        if ($valeur_h <= 0)
        {
            $errorcontroltxt = $errorcontroltxt . "Le solde de CET est insuffisant pour pouvoir exercer un droit d'option.<br>Il n'est donc pas possible d'établir une demande de droit d'option.<br>";
            $controleok = false;
        }

        if ($sauvegardeok)
        {
            echo $fonctions->showmessage(fonctions::MSGINFO, "Votre demande de droit d'option a été correctement enregistrée.");
        }
        else
        {
            // Si on a rencontré une anomalie dans les contrôles => On affiche le message d'alerte
            echo $fonctions->showmessage(fonctions::MSGERROR, $errorcontroltxt);
        }
        
        $listid = $agent->getDemandesOption($fonctions->anneeref(),array(OPTIONCET::STATUT_EN_COURS, OPTIONCET::STATUT_PREPARE, OPTIONCET::STATUT_INCONNU));
        if (count($listid)>0)
        {
            echo "<br>Suppression d'une demande de droit d'option.<br>";
            echo "<form name='form_esignature_delete'  method='post' >";
            echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
            echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
            echo "<select name='esignatureid_delete' id='esignatureid_delete'>";
            foreach ($listid as $id)
            {
                $optionCET = new optionCET($dbcon);
                $optionCET->load($id);
                if ($optionCET->statut() <> OPTIONCET::STATUT_ABANDONNE)
                {
                    echo "<option value='" . $id  . "'>" . $id  . "=> " .  $optionCET->statut() . "</option>";
                }
                unset($optionCET);
            }
            echo "</select>";
            echo "<br><br>";
            echo "<input type='hidden' name='mode' value='" . $mode . "'>";
            echo "<input type='submit' name='esignature_delete' id='esignature_delete' value='Suppression de la demande' onclick=\"return confirm('Annuler la demande ?')\">";
            echo "</form>";
            if (isset($error_suppr))
            {
                echo $fonctions->showmessage(fonctions::MSGERROR, $error_suppr);
            }
        }
               

        if ($controleok == true)
        {
            echo "<input type=hidden placeholder='Case A' name=valeur_a id=valeur_a value='$valeur_a' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
            echo "<input type=hidden placeholder='Case G' name=valeur_g id=valeur_g value='$valeur_g' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
            echo "<input type=hidden placeholder='Case H' name=valeur_h id=valeur_h value='$valeur_h' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
            
            if ($mode == 'rh')
            {
                echo "L'agent " . $agent->identitecomplete() . " a $valeur_h jour(s) à répartir.";
            }
            else
            {
                echo "Vous avez $valeur_h jour(s) à répartir.";
            }
            echo "<br>";
            if ($typeagent == 'titu')
            {
                echo "Nombre de jours à prendre en compte au titre de la RAFP : <input type=text placeholder='Case I' name=valeur_i id=valeur_i size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_i style='color: red;font-weight: bold; margin-left:20px;'></label>";   //      <input type=text placeholder='Case I' name=valeur_i id=valeur_i value=$valeur_i size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
                echo "<br>";
            }
            else
            {
                echo "<input type='hidden' name=valeur_i id=valeur_i value='0' >"; //<label id=label_i style='color: red;font-weight: bold; margin-left:20px;'></label>";
            }
            echo "Nombre de jours à indemniser : <input type=text placeholder='Case J' name=valeur_j id=valeur_j size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_j style='color: red;font-weight: bold; margin-left:20px;'></label>";   //     <input type=text placeholder='Case J' name=valeur_j id=valeur_j size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
            echo "<br>";
            echo "Nombre de jours à maintenir sur le CET sous forme de congés : <input type=text placeholder='Case K' name=valeur_k id=valeur_k size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' onchange='update_case()' onkeyup='update_case()' ><label id=label_k style='color: red;font-weight: bold; margin-left:20px;'></label>";
            echo "<br>";
            echo "Solde du CET après option : <input type=text placeholder='Case L' name=valeur_l id=valeur_l size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' ><label id=label_l style='color: red;font-weight: bold; margin-left:20px;'></label>";
        
            // On récupère le responsable du service de l'agent - Niveau 2
            //echo "<br>------------------------------------------------------------- <br>";
            $structid = $agent->structureid();
            $struct = new structure($dbcon);
            $struct->load($structid);
            $code = null;
            if ($struct->responsable()->agentid() == $agent->agentid())
            {
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" passage dans resp_envoyer_a"));
                $resp = $struct->resp_envoyer_a($code);
            }
            else
            {
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" passage dans agent_envoyer_a"));
                $resp = $struct->agent_envoyer_a($code);
            }
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" Le responsable de " . $agent->identitecomplete() . " est "  . $resp->identitecomplete()));
            //echo "Le responsable de l'agent est " . $resp->identitecomplete() .  " (" .  $resp->mail() . ") <br>";
        
            //echo "<br>------------------------------------------------------------- <br>";
            $qvt_id = 'DGEE_4';  // Id = DGEE_4	    Nom long = Service santé, handicap, action culturelle et sociale        Nom court = DRH-SSHACS
            $resp_agent = null;
            // On récupère tous les agents avec le profil RHCET
            foreach ( (array)$fonctions->listeprofilrh("1") as $qvt_agent) // RHCET
            {
                //echo $qvt_agent->identitecomplete() . " (" . $qvt_agent->mail() . ") est gestionnaire CET <br>";
                if (count((array)$qvt_agent->structrespliste())>0)
                {
                    $resp_agent = $qvt_agent;
                    //echo "J'ai trouvé le responsable : " . $resp_agent->identitecomplete() . " (" . $resp_agent->mail() . ") <br>";
               }
            }
        
            //echo "<br>------------------------------------------------------------- <br>";
            // On récupère le responsable du service QVT (Qualité de vie au travail) - Niveau 4
            if (is_null($resp_agent))
            {
                $struct = new structure($dbcon);
                $struct->load($qvt_id);
                $resp_agent = $struct->responsable();
            }
            //echo $resp_agent->identitecomplete() . " (" . $resp_agent->mail() . ") est le responsable du service QVT <br>";
            
            //echo "<br>------------------------------------------------------------- <br>";
            // On récupère le responsable du service DRH et DGS - Niveau 5
            $struct = new structure($dbcon);
            $drh_id = 'DGE_3';  // Id = DGE_3     Nom long = Direction des ressources humaines        Nom court = DRH
            $struct->load($drh_id);
            $drh_agent = $struct->responsable();
            //echo $drh_agent->identitecomplete() . " (" . $drh_agent->mail() . ") est le responsable du service DRH <br>";
            $struct = new structure($dbcon);
            $dgs_id = 'DG_2';  // Id = DG_2     Nom long = Direction générale des services        Nom court = DGS
            $struct->load($dgs_id);
            $dgs_agent = $struct->responsable();
            //echo $dgs_agent->identitecomplete() . " (" . $dgs_agent->mail() . ") est le responsable du service DGS <br>";
            
            echo "<br><br>";
            echo "<input type='hidden' name='mode' value='" . $mode . "'>";
            echo "<input type='submit' name='cree_option' id='cree_option' value='Soumettre' disabled>";
            echo "</form>";
        }
        echo "<br><br>";
        echo $agent->afficheOptionCetHtml(); //$fonctions->anneeref());
        
        
        echo "<br>";
        echo $agent->soldecongeshtml("$anneeref");
        echo "<br>";
    }
    
?>

</body>
</html>


