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
    $optionCET = new optionCET($dbcon);

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
    
    $valeur_b = null;
    if (isset($_POST["valeur_b"]))
        $valeur_b = $_POST["valeur_b"];
            
    $valeur_c = null;
    if (isset($_POST["valeur_c"]))
        $valeur_c = $_POST["valeur_c"];
        
    $valeur_d = null;
    if (isset($_POST["valeur_d"]))
        $valeur_d = $_POST["valeur_d"];
        
    $valeur_e = null;
    if (isset($_POST["valeur_e"]))
        $valeur_e = $_POST["valeur_e"];
        
    $valeur_f = null;
    if (isset($_POST["valeur_f"]))
        $valeur_f = $_POST["valeur_f"];
                        
    $valeur_g = null;
    if (isset($_POST["valeur_g"]))
        $valeur_g = $_POST["valeur_g"];

    $esignatureid = null;
    if (isset($_POST["esignatureid_get"]))
        $esignatureid = $_POST["esignatureid_get"];

    if (isset($_POST["esignatureid_post"]))
        $esignatureid = $_POST["esignatureid_post"];

    $statut = null;
    if (isset($_POST["statut"]))
        $statut = $_POST["statut"];
        
    $description = null;
    if (isset($_POST["description"]))
        $description = $_POST["description"];
    
    $cree_demande = null;
    if (isset($_POST["cree_demande"]))
    	$cree_demande = $_POST["cree_demande"];
    	
  /*  $annule_demande = null;
    if (isset($_POST["annule_demande"]))
    	$annule_demande = $_POST["annule_demande"];*/
        
    $modif_statut = null;
    if (isset($_POST["modif_statut"]))
        $modif_statut = $_POST["modif_statut"];
    
    $esignatureid_get = null;
    if (isset($_POST["esignatureid_get"]))
        $esignatureid_get = $_POST["esignatureid_get"];
    
    $aff_demande = null;
    if (isset($_POST["aff_demande"]))
        $aff_demande = $_POST["aff_demande"];
    
    $esignature_info = null;
    if (isset($_POST["esignature_info"]))
        $esignature_info = $_POST["esignature_info"];
    
    $esignatureid_get_info = null;
    if (isset($_POST["esignatureid_get_info"]))
        $esignatureid_get_info = $_POST["esignatureid_get_info"];

    $get_g2t_info = null;
    if (isset($_POST["get_g2t_info"]))
        $get_g2t_info = $_POST["get_g2t_info"];
    
    $drh_niveau = null;
    if (isset($_POST["drh_niveau"]))
        $drh_niveau = $_POST["drh_niveau"];
    
    $responsable = null;
    if (isset($_POST["responsable"]))
        $responsable = $_POST["responsable"];
    
    $mode = "agent";
    if (isset($_POST["mode"]))
    	$mode = $_POST["mode"];
    
    $no_verify = false;
    if (isset($_POST["no_verify"]))
    {
        if ($_POST["no_verify"] == 'on')
            $no_verify = true;
    }
        
/*        
    $send_mail = null;
    if (isset($_POST["send_mail"]))
        $send_mail = $_POST["send_mail"];
*/
    
    
      
    require ("includes/menu.php");
    
/*
    echo "<br>Server info = ";
    var_dump($_SERVER);
    echo "<br><br>";
*/
    $id_model = trim($fonctions->getidmodelalimcet());
    $eSignature_url = trim($fonctions->liredbconstante('ESIGNATUREURL'));
    //$sftpurl = $fonctions->liredbconstante('SFTPTARGETURL');
    $sftpurl = "";
    
    
/*
    $servername = $_SERVER['SERVER_NAME'];
    $serverport = $_SERVER['SERVER_PORT'];
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
    {
       $serverprotocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
       $serverport = $_SERVER['HTTP_X_FORWARDED_PORT'];
    }
    else
    {
       $serverprotocol = "http";
    }
     
    //echo "serverprotocol  = $serverprotocol   servername = $servername   serverport = $serverport <br>";
    $g2t_ws_url = $serverprotocol . "://" . $servername . ":" . $serverport;
    $full_g2t_ws_url = $g2t_ws_url . "/ws/alimentationWS.php";
*/
    $full_g2t_ws_url = trim($fonctions->get_g2t_ws_url()) . "/alimentationWS.php";
?>
    <script type="text/javascript">
          //window.addEventListener("load", function(event) {
          //  window.open('http://esignature.univ-paris1.fr');
          //});
   	</script>	

    
<?php 

	//$user->supprimeDemandeAlimentation();	
/*	
	echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . " id du modele " .$id_model. "<br>";

    echo "L'URL d'appel du WS G2T est : " . $full_g2t_ws_url;
    echo "<br>" . print_r($_POST,true);
    //echo "<br><br><br>";
*/
    
	// Si on est en mode 'rh' et qu'on n'a pas encore choisi l'agent, on affiche la zone de sélection.
	if (is_null($agentid) and $mode == 'rh')
	{
		echo "<form name='demandeforagent'  method='post' action='gerer_alimentationCET.php'>";
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
    
    if (isset($_POST['annuler_demande']))
    {
    	$esignatureid_annule = $_POST['esignatureid_annule'];
    	$alimentationCET = new alimentationCET($dbcon);
    	$alimentationCET->load($esignatureid_annule);
    	// récupérer statut si validée réalimenter le reliquat, déduire du CET et alerter la DRH
    	$statut_actuel = $alimentationCET->statut();
    	if (!is_null($agentid))
    	{
    		$agent = new agent($dbcon);
    		$agent->load($agentid);
    		
    		// purger esignature
    		
    		$eSignature_url = $fonctions->liredbconstante("ESIGNATUREURL"); //"https://esignature-test.univ-paris1.fr";
    		$url = $eSignature_url.'/ws/signrequests/'.$esignatureid_annule;
    		$params = array('id' => $esignatureid_annule);
    		$walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
    			is_array( $item )
    			? array_walk( $item, $walk, $key )
    			: $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
    			
    		};
    		array_walk( $params, $walk );
    		$json = implode( '&', $output );
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		$json = curl_exec($ch);
    		$result = json_decode($json);
    		error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE SUPPRESSION ALIM -- " . var_export($result, true));
    		$error = curl_error ($ch);
    		curl_close($ch);
    		$errlog = '';
    		if ($error != "")
    		{
    			$errlog .= "Erreur Curl = " . $error . "<br><br>";
    		}
    		if (!is_null($result)) 
    		{
    			$errlog .= " Erreur lors de la suppression de la demande d'alimentation dans Esignature : ".var_export($result, true);
    		}
    		else
    		{
    		    if (stristr(substr($json,0,20),'HTML') === false) // On n'a pas trouvé HTML dans le json
    		    {
    		    	if ($statut_actuel == alimentationCET::STATUT_VALIDE)
    		    	{
    		    		// réattribution des reliquats
    		    		$solde = new solde($dbcon);
    		    		//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le type de congés est " . $alimentationCET->typeconges()));
    		    		$solde->load($agent->agentid(), $alimentationCET->typeconges());
    		    		//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est avant : " . $solde->droitpris() . " et valeur_f = " . $alimentationCET->valeur_f()));
    		    		$new_solde = $solde->droitpris()-$alimentationCET->valeur_f();
    		    		$solde->droitpris($new_solde);
    		    		//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est après : " . $solde->droitpris()));
    		    		error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde " . $solde->typelibelle() . " sera après enregistrement de " . ($solde->droitaquis() - $solde->droitpris())));
    		    		$solde->store();
    		    		
    		    		// Ajouter dans la table des commentaires la trace de l'opération
    		    		$agent->ajoutecommentaireconge($alimentationCET->typeconges(),($alimentationCET->valeur_f()),"Annulation de demande d'alimentation CET");
    		    		
    		    		// déduction du CET
    		    		
    		    		$cet = new cet($dbcon);
    		    		$erreur = $cet->load($agent->agentid());
    		    		if ($erreur == "") {
    		    			$cet->cumultotal($cet->cumultotal() - $alimentationCET->valeur_f());
    		    			$cumulannuel = $cet->cumulannuel($fonctions->anneeref());
    		    			$cumulannuel = $cumulannuel - $alimentationCET->valeur_f();
    		    			$cet->cumulannuel($fonctions->anneeref(),$cumulannuel);
    		    			$cet->store();
    		    		}
    		    		
    		    		// alerter la DRH
    		    		
    		    		$arrayagentrh = $fonctions->listeprofilrh("1"); // Profil = 1 ==> GESTIONNAIRE RH DE CET
    		    		foreach ($arrayagentrh as $gestrh) {
    		    			error_log(basename(__FILE__) . " envoi de mail Annulation d'une demande d'alimentation de CET validée a " . $gestrh->identitecomplete());
    		    			$agent->sendmail($gestrh, "Annulation d'une demande d'alimentation de CET validée", "L'agent " .$user->identitecomplete()." a demandé l'annulation de la demande d'alimentation de " .$agent->identitecomplete(). " n ". $esignatureid_annule . ".\n");
    		    		}
    		    	}
    		    	
    		    	// Abandon dans G2T
    		    	$alimentationCET->statut($alimentationCET::STATUT_ABANDONNE);
    		    	$alimentationCET->motif("Annulation à la demande de ".$user->identitecomplete());
    		    	$alimentationCET->store();
    		    	$errlog .= "L'utilisateur " . $user->identitecomplete() . " (identifiant = " . $user->agentid() . ") a supprimé la demande d'alimentation du CET de ".$agent->identitecomplete()." (esignatureid = ".$esignatureid_annule.")";
    		    }
    		    else // On a trouvé HTML dans le json
    		    {
    		        $error_suppr = "Erreur lors de la suppression de la demande d'alimentation dans Esignature !!==> Pas de suppression dans G2T.<br><br>";
    		        $errlog .= " Erreur de connexion à Esignature lors de la suppression de la demande d'alimentation dans Esignature : " .var_export($json, true);
    		    }

    		}
    		error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    	}
    }
    
    $anneeref = $fonctions->anneeref()-1;


    // Création d'une alimentation
    if (!is_null($cree_demande))
    {
        $alimentationCET = new alimentationCET($dbcon);
        $alimentationCET->agentid($agentid);
        $alimentationCET->typeconges('ann' . substr($anneeref,2,2));
        $alimentationCET->valeur_a($valeur_a);
        $alimentationCET->valeur_b($valeur_b);
        $alimentationCET->valeur_c($valeur_c);
        $alimentationCET->valeur_d($valeur_d);
        $alimentationCET->valeur_e($valeur_e);
        $alimentationCET->valeur_f($valeur_f);
        $alimentationCET->valeur_g($valeur_g);
        
        error_log(basename(__FILE__) . " " . var_export($alimentationCET,true));
        

        if (((float)$valeur_f+0)==0)
        {
            $error = "La valeur de la case F est vide ou égale à 0... On ne peut pas sauvegarder la demande d'alimentation.";
            echo $fonctions->showmessage(fonctions::MSGERROR, $error);
        }
        else
        {
            if (!is_null($agentid))
            {
                // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $agent_eppn = $agent->eppn();
                
                // On récupère le mail de l'agent en cours
                $agent_mail = $agent->ldapmail();
            }
            
            if ((sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) == 0)
            		and (sizeof($agent->getDemandesOption('', array(optionCET::STATUT_EN_COURS, optionCET::STATUT_PREPARE)))== 0))
            {
	            // On appelle le WS eSignature pour créer le document
	            $curl = curl_init();
	            //echo "EPPN de l'agent => " . $agent_eppn . ". <br>";
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
	            /*if ($responsable == 'resp_demo')
	            {
	                $params['recipientEmails'] = array
	                    (
	                        "2*pascal.comte@univ-paris1.fr",
	                        "2*elodie.briere@univ-paris1.fr"
	                    );
	            }
	            else // On met le vrai responsable de l'agent
	            {*/
	            	// On récupère le responsable de la structure de l'agent - Niveau 2
	            	$code = null;
	            	$structid = $agent->structureid();
	            	$structure = new structure($dbcon);
	            	$structure->load($structid);
	            	if ($structure->responsable()->agentid() == $agent->agentid())
	            	{
	            		error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" passage dans resp_envoyer_a"));
	            		$resp = $structure->resp_envoyer_a($code);
	            	}
	            	else
	            	{
	            		error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" passage dans agent_envoyer_a"));
	            		$resp = $structure->agent_envoyer_a($code);
	            	}
	            	error_log(basename(__FILE__) . " " . $fonctions->stripAccents(" Le responsable de " . $agent->identitecomplete() . " est "  . $resp->identitecomplete()));
	            	if ($resp->agentid() != '-1')
	            	{
		            	$params['recipientEmails'] = array
		            	(
		            	    "1*" . $agent_mail,
		            	    "2*" . $resp->mail()
		            	);
		             // }
		             
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
		            //if (!is_null($drh_niveau))
		            //{
		                //$params['recipientEmails'][] = '3*' . $agent_mail;
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
		                $qvt_id = 'DGEE_4';  // Id = DGEE_4        Nom long = Service santé, handicap, action culturelle et sociale        Nom court = DRH-SSHACS
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
		           // }
		    /*        
		            $params_string = http_build_query($params);
		            echo "<br>Param = " . $params_string . "<br><br>";
		            
		            Voir la réponse : https://stackoverflow.com/questions/26563952/php-multidimensional-array-to-query-string/26565074
		            
		            $array = array('order_source' => array('google','facebook'),'order_medium' => 'google-text');
		            
		            //Array
		            //(
		            //    [order_source] => Array
		            //    (
		            //        [0] => google
		            //        [1] => facebook
		            //    )
		            //    [order_medium] => google-text
		            //)
		            
		            $walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
		                is_array( $item ) 
		                    ? array_walk( $item, $walk, $key ) 
		                    : $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
		    
		            };
		    
		            array_walk( $array, $walk );
		    
		            echo implode( '&', $output );  // order_source=google&order_source=facebook&order_medium=google-text 
		    
		            
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
		            //echo "<br>"; var_dump($json); echo "<br>";
		            $id = json_decode($json, true);
		            error_log(basename(__FILE__) . " " . var_export($opts, true));
		            error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE CREATION ALIM -- " . var_export($id, true));
		            //var_dump($id);
		            if (is_array($id))
		            {
		            	$erreur = "La création de la demande d'alimentation dans eSignature a échoué => " . print_r($id,true);
		            	error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
		            	echo "$erreur <br><br>";
		            }
		            else
		            {
		                if ("$id" < 0)
		                {
		                    $erreur =  "La création de la demande d'alimentation dans eSignature a échoué (numéro demande eSignature négatif = $id) !!==> Pas de sauvegarde du droit d'option dans G2T.";
		                    error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
		                    echo "$erreur <br><br>";
		                }
		                elseif ("$id" <> "")
			            {
		
		                    //echo "Id de la nouvelle demande = " . $id . "<br>";
		                    $alimentationCET->esignatureid($id);
		                    $alimentationCET->esignatureurl($eSignature_url . "/user/signrequests/".$id);
		                    $alimentationCET->statut($alimentationCET::STATUT_PREPARE);
		                    
		                    $erreur = $alimentationCET->store();
		                    $agent->synchroCET();
			                if ($erreur <> "")
			                {
			                	echo "Erreur (création) = $erreur <br>";
			                	error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur (création) = " . $erreur ));
			                }
			                else
			                {
			                    //var_dump($alimentationCET);
			                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La sauvegarde (création) s'est bien passée => eSignatureid = " . $id ));
			                    //echo "La sauvegarde (création) s'est bien passée...<br><br>";
			                }
			            }
			            else
			            {
			                $erreur  = "La création de la demande d'alimentation dans eSignature a échoué !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.";
			                error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
			                echo "$erreur <br><br>";
			            }
		            }
            	}
            	else // Le responsable est g2t cron
            	{
            		echo $fonctions->showmessage(fonctions::MSGWARNING, "Votre responsable n'est pas renseigné, veuillez contacter la DRH.");
            	}
	        }
        }
    }
    
 /*   // annulation de la demande
    if (!is_null($annule_demande))
    { 
    	if (!is_null($agentid))
    	{
    		$agent = new agent($dbcon);
    		$agent->load($agentid);
    		$alimentationCET = new alimentationCET($dbcon);
    		$list_alim_en_cours = $agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE));
    		if (sizeof($list_alim_en_cours) > 0)
    		{
    			echo "Une alimentation en cours : $list_alim_en_cours[0] <br>";
    			$alimentationCET->load($list_alim_en_cours[0]);
    			$alimentationCET->statut($alimentationCET::STATUT_ABANDONNE);
    			$alimentationCET->store();
    		}
    		else
    		{
    			echo "Liste d'alimentation en cours est vide <br>";
    		}
    	}
    }*/
    
/*    if (!is_null($esignature_info))
    {
        // On appelle le WS G2T en GET pour demander à G2T de mettre à jour la demande
        $alimentationCET = new alimentationCET($dbcon);
        $erreur = $alimentationCET->load($esignatureid_get_info);
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid_get_info . " => Erreur = " . $erreur));
            echo "Erreur lors du chargement de la demande $esignatureid_get_info avant la synchronisation.<br>";
        }
        echo "<br><br>Le statut de la demande avant la synchronisation est : " . $alimentationCET->statut() . "<br>";
        
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Synchronisation de la demande $esignatureid_get_info avec eSignature (synchro manuelle)."));
        $fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$esignatureid_get_info);
        //error_log(basename(__FILE__) . $fonctions->stripAccents(" Après synchronisation de la demande $esignatureid_get_info avec eSignature (synchro manuelle)."));
  */      
/*
        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $full_g2t_ws_url . "?signRequestId=" . $esignatureid_get_info,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_PROXY, '');
        //echo "<br>CURLOPT_PROXY => " . curl_getinfo($curl,CURLOPT_PROXY) . "<br><br>";
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if ($error != "")
        {
            echo "Erreur Curl = " . $error . "<br><br>";
        }
        //echo "<br>" . print_r($json,true) . "<br>";
        $response = json_decode($json, true);
        echo "<br>";
        echo '<pre>';
        var_dump($response);
        echo '</pre>';
*/     
/*        
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Avant chargement de la demande $esignatureid_get_info."));
        $alimentationCET = new alimentationCET($dbcon);
        $erreur = $alimentationCET->load($esignatureid_get_info);
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid_get_info . " => Erreur = " . $erreur));
            echo "Erreur lors du chargement de la demande $esignatureid_get_info après la synchronisation.<br>";
        }
        else
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Après chargement de la demande $esignatureid_get_info => Erreur est vide."));
        
        echo "<br>Le statut de la demande après la synchronisation est : " . $alimentationCET->statut() . "<br>";
        
    }*/
    if (! is_null($agentid))
    {
	    // Affichage des demandes d'alimentation dans la base G2T
	    $alimentationCET = new alimentationCET($dbcon);
	    
	    
	    // EXEMPLE D'USAGE echo $agent->afficheAlimCetHtml('ann19', array($alimentationCET::STATUT_PREPARE, $alimentationCET::STATUT_EN_COURS));
	    /*$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE AGENTID = '" .  $agentid . "'";
	    $query = mysqli_query($dbcon, $sql);
	    $erreur = mysqli_error($dbcon);
	    if ($erreur != "")
	    {
	        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
	        echo $errlog;
	    }
	    else
	    {
	        echo "Informations sur les demandes d'alimentation de CET pour " . $agent->identitecomplete() . "<br>";
	        echo "<div id='demandes_alim_cet'>";
	        echo "<table class='tableausimple'>";
	        echo "<tr><td class='titresimple'>Date création</td><td class='titresimple'>Type de demande</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
	        echo "</tr>";
	        while ($result = mysqli_fetch_row($query))
	        {
	            $alimcet = new alimentationCET($dbcon);
	            $id = $result[0];
	            $alimcet->load($id);
	            echo "<tr><td class='cellulesimple'>" . $fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $alimcet->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".$alimcet->esignatureurl()."</a></td></tr>";
	            unset ($alimcet);
	        }
	        echo "</table><br>";
	        
	        echo "</div>";
	    }
	    */
	    
	    // On récupère les soldes de l'agent
	    $agent = new agent($dbcon);
	    $agent->load($agentid);
	    $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
	    if ($affectationliste != NULL)
	    {
	        $affectation = current($affectationliste);
	        $structure = new structure($dbcon);
	        $structure->load($affectation->structureid());
	    }
	    
	    $solde = new solde($dbcon);
	    $solde->load($agentid, 'ann' . substr($anneeref,2,2));
	    //echo "<br>Solde = " . print_r($solde,true) . "<br>";
	    
	    $cet = new cet($dbcon);
	    $erreur = $cet->load($agentid);
	    if ($erreur == "")    
	        $valeur_a = $cet->cumultotal()-$cet->jrspris();
	    else
	        $valeur_a = 0;
	    $valeur_b = $solde->droitaquis();
	    // Consommation des congés au début de la période (case C)
	    $valeur_c = $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()-2).'0101', ($fonctions->anneeref()).$fonctions->finperiode());
	    if ($valeur_c == 0 and $no_verify==true)
	    {
	        $valeur_c = $solde->droitpris() - $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()).$fonctions->debutperiode(), ($fonctions->anneeref()+1).$fonctions->finperiode());
	    }
	    $valeur_d = $valeur_b-$valeur_c;

	    //////////////////////////////////////
	    // Pour test !!!!
	    //$valeur_d = 3;
	    /////////////////////////////////////
	    
	?>
	    <script type="text/javascript">
	    function opendemande() {
	    	demandeliste = document.getElementById("esignatureid_aff")
	    	urldemande = demandeliste.value;
	    	//alert("opendemande est activé : " + urldemande );
	    	window.open(urldemande);
	    	return false;
	    }
	    
	    function isInt(value) {
			return !isNaN(value) && (function(x) { return (x | 0) === x; })(parseFloat(value))
		}
	    
	    function update_case(elem)
	    {
	    	//alert("Update Case est activé");
		    const elem_no_verify = document.getElementById('no_verify'); 
	    	//alert("Apres le elem_no_verify = ");
	    	check_plafond = true;
	    	if (elem_no_verify === null)
	    	{
		    	//alert("elem_no_verify est NULL");
		    }
		    
	    	if (elem_no_verify !== null)
	    	{
	    		//alert("elem_no_verify n'est pas null " + elem_no_verify.id);
	    		document.getElementById('check_plafond').style.color = "initial";
	    		document.getElementById('check_plafond').style.fontWeight = "normal";
	    		document.getElementById('label_plafond').innerHTML = "";
	    		if (elem_no_verify.checked)
	    		{
	    		    //alert("Il est checked");
	    		    document.getElementById('valeur_c').value = "<?php echo $solde->droitpris() - $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()).$fonctions->debutperiode(), ($fonctions->anneeref()+1).$fonctions->finperiode());?>";
	    		    document.getElementById('valeur_d').value = document.getElementById("valeur_b").value - document.getElementById('valeur_c').value;
	    			check_plafond = false;
	    			document.getElementById('check_plafond').style.color = "red";
	    			document.getElementById('check_plafond').style.fontWeight = "bold";
	    			document.getElementById('label_plafond').innerHTML = " &larr; ATTENTION : Il n'y a pas de vérification par rapport à cette valeur ni le solde <?php echo $solde->typelibelle()  ?>! "; //- Valeur C = " + document.getElementById('valeur_c').value + " Valeur D = " + document.getElementById('valeur_d').value;
	    			//alert("no_verify est checked");
		    		//const button = document.getElementById('cree_demande');
	    			//button.disabled = false;
	    			//return;
	    	    }
	    	    else
	    	    {
	    		    document.getElementById('valeur_c').value = "<?php echo $valeur_c ?>";
	    		    document.getElementById('valeur_d').value = "<?php echo $valeur_d ?>";
	    			//document.getElementById('label_plafond').innerHTML = " Valeur initiale ==> Valeur C = " + document.getElementById('valeur_c').value + " Valeur D = " + document.getElementById('valeur_d').value;
	    	    }
	    	}
	    

	    	document.getElementById("valeur_f").value = document.getElementById("valeur_f").value.replace(",",".");
	       	valeur_f = document.getElementById("valeur_f").value;
	    	const button = document.getElementById('cree_demande')
	    	//alert ("valeur D = " + valeur_d + "  valeur F = " + valeur_f);
			if (valeur_f == "")
			{
	        	document.getElementById("valeur_e").value = "";
	        	document.getElementById("valeur_g").value = "";
	    		document.getElementById("label_f").innerHTML = "";
	    		button.disabled = true;
	   		}
	    	else if (isNaN(valeur_f))
	    	{
	    		//alert("La valeur de la case F n'est pas un nombre.");
	    		document.getElementById("label_f").innerHTML = "La valeur n'est pas un nombre. Vous devez saisir un entier positif.";
	    		button.disabled = true;
	    	}    	
	    	else if (!isInt(valeur_f))
	    	{
	    		document.getElementById("label_f").innerHTML = "La valeur n'est pas un entier. Vous devez saisir un entier positif.";
	    		button.disabled = true;
	    	}
	    	else if (parseInt(valeur_f) <= 0)
	    	{
	     		document.getElementById("label_f").innerHTML = "La valeur est négative. Vous devez saisir un entier positif.";
	    		button.disabled = true;
	    	}
	    	else if ((parseInt(valeur_f) > parseInt(plafond)) && check_plafond)
	    	{
	    		document.getElementById("label_f").innerHTML = "Le nombre de jours doit être inférieur ou égal au dépôt maximum.";
	    		button.disabled = true;
	    	}
	    	else
	    	{
	    		document.getElementById("label_f").innerHTML = "";
	        	valeur_a = document.getElementById("valeur_a").value;
	        	valeur_d = document.getElementById("valeur_d").value;
	        	plafond = document.getElementById("plafond").value;
	        	document.getElementById("valeur_e").value = parseInt(valeur_d,10)-parseInt(valeur_f,10);
	        	document.getElementById("valeur_g").value = parseFloat(valeur_a,10)+parseInt(valeur_f,10);
	    		button.disabled = false;
	        }
	    }
		</script>
	<?php
	// Si campagne en cours, pas d'interruption d'affectation avec solde CET non nul et pas de demande en cours
	$today = date('Ymd'); 
	$ayearbefore = new DateTime(); 
	$ayearbefore->sub(new DateInterval('P1Y')); 
	$ayearbefore = $ayearbefore->format('Ymd');
	$hasInterruptionAff = $agent->hasInterruptionAffectation($ayearbefore, $today);
	$hasOption = FALSE;
	echo "Alimentation du CET pour " . $agent->identitecomplete() . "<br><br>";
	if ($today < $fonctions->debutalimcet() || $today > $fonctions->finalimcet())
	{
        echo $fonctions->showmessage(fonctions::MSGWARNING, "La campagne d'alimentation du CET est fermée actuellement.");
	}
	else 
	{
		if (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
		{
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande d'alimentation en cours. Vous pourrez en effectuer une nouvelle lorsque celle-ci sera terminée ou annulée.");
			/*echo "Souhaitez-vous annuler la demande ? <br>";
			echo "<form name='annuler_alimentation'  method='post' >";
			echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
			echo "<input type='submit' name='annule_demande' id='annule_demande' value='Annuler' onclick=\"return confirm('Annuler la demande d\'alimentation du CET ?')\">";
			echo "</form>";*/
			
		}
		elseif (sizeof($agent->getDemandesOption($fonctions->anneeref(), array($optionCET::STATUT_EN_COURS, $optionCET::STATUT_PREPARE))) != 0)
		{
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande de droit d'option en cours. Vous ne pourrez effectuer une nouvelle demande d'alimentation que si celle-ci est refusée ou annulée.");
			$hasOption = TRUE;
		}
		elseif (sizeof($agent->getDemandesOption($fonctions->anneeref(), array($optionCET::STATUT_VALIDE))) != 0)
		{
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande de droit d'option validée. Vous ne pourrez pas effectuer de nouvelle demande d'alimentation cette année.");
			$hasOption = TRUE;
		}
		elseif ($hasInterruptionAff && $valeur_a == 0)
		{
            echo $fonctions->showmessage(fonctions::MSGWARNING, "Votre ancienneté n'est pas suffisante pour alimenter votre CET (ancienneté d'au minimum un an sans interruption requise).");
		}
		else 
		{
			$pr = $agent->getPlafondRefCet();
			//echo "Plafond de référence pour l'agent : $pr <br>";
			// Consommation des congés au début de la période (case C)
			$consodeb = $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()-2).'0101', ($fonctions->anneeref()).$fonctions->finperiode());
			//echo "Congés ".($fonctions->anneeref() - 1)."/".$fonctions->anneeref()." consommés au ".$fonctions->formatdate(($fonctions->anneeref()-1).$fonctions->finperiode())." : $consodeb<br>";
			
			// Consommation des congés entre le debut de la période et la demande
			$consoadd = $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()).$fonctions->debutperiode(), ($fonctions->anneeref()+1).$fonctions->finperiode());
			//echo "Congés ".($fonctions->anneeref() - 1)."/".$fonctions->anneeref()." consommés depuis le ".$fonctions->formatdate(($fonctions->anneeref()).$fonctions->debutperiode())." : ".$consoadd." <br>";
			
			// Nombre de jours déposés sur le CET au titre de l'année de ref
			$joursCET = 0;
			$alimentationCET = new alimentationCET($dbcon);
			$list_id_alim = $agent->getDemandesAlim('ann'.substr($fonctions->anneeref() - 1,2, 2), array($alimentationCET::STATUT_VALIDE));
			foreach ($list_id_alim as $id_alim)
			{
				$alimentationCET->load($id_alim);
				$joursCET += $alimentationCET->valeur_f();
			}
			$nbjoursobli = (20 * $agent->getQuotiteMoyPeriode(($fonctions->anneeref() - 1).$fonctions->debutperiode(), $fonctions->anneeref().$fonctions->finperiode()) /100);
			if ($nbjoursobli - floor($nbjoursobli) != 0)
			{
				if ($nbjoursobli - floor($nbjoursobli) <= 0.5)
				{
					$nbjoursobli = floor($nbjoursobli) + 0.5;
				}
				else 
				{
					$nbjoursobli = floor($nbjoursobli) + 1;
				}
			}
			if ($valeur_c < $nbjoursobli)
			{
                echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous n'avez pas posé les $nbjoursobli jours de congés \"" . $solde->typelibelle() . "\" obligatoires (sur la période de référence du " . $fonctions->formatdate(($fonctions->anneeref()-1).$fonctions->debutperiode()) . " au " . $fonctions->formatdate($fonctions->anneeref().$fonctions->finperiode()) . "). Vous ne pouvez donc pas alimenter votre CET.");
				$nbjoursmax = 0;
			}
			else {
				$nbjoursmax = floor($pr - $consodeb);
				if ($nbjoursmax < 0)
					$nbjoursmax = 0;
				else 
				{
					$nbjoursrestants = $valeur_b - $consodeb - $consoadd;
					if ($nbjoursmax > $nbjoursrestants)
					{
						$nbjoursmax = floor($nbjoursrestants);
					}
					if ($nbjoursmax > $joursCET)
					{
						$nbjoursmax = $nbjoursmax - $joursCET;
					}
					else
					{
						$nbjoursmax = 0;
					}
				}
			}
			// echo "Nombre de jours déposables sur le CET : ".$nbjoursmax." <br><br>";
			/*echo "Nombre de jours à déposer sur le CET <br>";
			echo "<form name='form_esignature_new_alim'  method='post' >";
			echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
			echo "<select name='esignature_nbjour_new_alim' id='esignature_nbjour_new_alim'>";
			for ($nbjours = 1; $nbjours <= $nbjoursmax; $nbjours++)
			{
				echo "<option value='" . $nbjours  . "'>" . $nbjours . " jour(s)</option>";
			}
			echo "</select>";
			echo "<br><br>";
			echo "<input type='submit' name='esignature_new_alim' id='esignature_new_alim' value='Déposer les jours'>";
			echo "</form>";*/
			
			//echo "Anneref = $anneeref <br>";
			
			//echo 'Structure complète d\'affectation : '.$structure->nomcompletcet().'<br>';
			echo "<form name='creation_alimentation'  method='post' >";
			echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
			echo "Solde actuel de votre CET : $valeur_a jour(s)";
			echo "<br>";
			echo "Dépôt maximum : $nbjoursmax jour(s) <label id=label_plafond style='color: red;font-weight: bold; margin-left:20px;'></label>";
			echo "<br>";
			echo "Combien de jours souhaitez-vous ajouter à votre CET ? <input type=text placeholder='Case F' name=valeur_f id=valeur_f size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_f style='color: red;font-weight: bold; margin-left:20px;'></label>";
			echo "<br>";
			echo "Solde de votre CET après versement <input type=text placeholder='Case G' name=valeur_g id=valeur_g size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' > jour(s).";
			echo "<input type='hidden' name='plafond' readonly id='plafond' value='" . $nbjoursmax . "' style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			echo "<input type='hidden' placeholder='Case A' name=valeur_a id=valeur_a value=$valeur_a size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			echo "<input type='hidden' placeholder='Case B' name=valeur_b id=valeur_b value=$valeur_b size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			echo "<input type='hidden' placeholder='Case C' name=valeur_c id=valeur_c value=$valeur_c size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";;
			echo "<input type='hidden' placeholder='Case D' name=valeur_d id=valeur_d value=$valeur_d size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			echo "<input type='hidden' placeholder='Case E' name=valeur_e id=valeur_e size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			// echo "Alimentation du CET (Case F) : <input type=text placeholder='Case F' name=valeur_f id=valeur_f size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_f style='color: red;font-weight: bold; margin-left:20px;'></label>";
			// echo "<br>";
			// echo "Solde du CET après versement (Case G) : <input type=text placeholder='Case G' name=valeur_g id=valeur_g size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
			//echo "<br><br>Choix du responsable :<br>";
			//echo "<input type='radio' id='resp_demo' name='responsable' value='resp_demo' checked><label for='resp_demo'>Responsable de démo (Pascal+Elodie)</label>";
			// echo "&nbsp;&nbsp;&nbsp;";
			$code = null;
			if ($structure->responsable()->agentid() == $agent->agentid())
			{
				$resp = $structure->resp_envoyer_a($code);
			}
			else
			{
				$resp = $structure->agent_envoyer_a($code);
			}
			echo "<br><br>";
			if ($mode == 'rh')
			{
			    echo "<p id='check_plafond'><input type='checkbox' id='no_verify' name='no_verify' value='on'>Ne pas contrôler le plafond d'alimentation CET.</p><br><br>";
?>

				 <script type="text/javascript">const no_verify = document.getElementById('no_verify'); no_verify.addEventListener('input', update_case);</script>

<?php 
			}
			//echo "Responsable de l'agent (" . $resp->identitecomplete() .  " - " .  $resp->mail() . ")";
			// echo "<br><br>";
			//echo "<input type='checkbox' id='drh_niveau' name='drh_niveau' checked><label for='drh_niveau'>Ajouter un 3e niveau dans le circuit de validation (Destinataire : " . $agent->identitecomplete()  .")</label><br>";
			// echo "<br><br>";
			echo "<input type='hidden' name='mode' value='" . $mode . "'>";
			echo "<input type='submit' name='cree_demande' id='cree_demande' value='Soumettre' disabled>";
			echo "</form>";
			echo "<br>";
		}
	}
	// Si une demande d'option est en cours ou validée, pas de suppression possible de demande d'alimentation
	if (!$hasOption)
	{
		// contrôle de la date de fin d'utilisation des reliquats
		$sqldatereliq = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'FIN_REPORT'";
		$queryreliq = mysqli_query($dbcon, $sqldatereliq);
		$erreur = mysqli_error($dbcon);
		if ($erreur != "")
		{
			$errlog = "Problème SQL dans le chargement de la date limite d'utilisation du reliquat : " . $erreur;
			echo $errlog;
		}
		elseif ($res = mysqli_fetch_row($queryreliq))
		{
			$limitereliq = ($fonctions->anneeref()+1).$res[0];
			if ($fonctions->verifiedate($fonctions->formatdate($limitereliq)))
			{
				if (date('Ymd') <= $limitereliq)
				{
					//$agent->afficheAlimCetHtmlPourSuppr('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE, $alimentationCET::STATUT_VALIDE), $mode, $userid);
					$alimcet = new alimentationCET($dbcon);
					$listid = $agent->getDemandesAlim("ann" . substr(($fonctions->anneeref()-1),2,2), array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE, $alimentationCET::STATUT_VALIDE));
					$htmltext = '';
					if (sizeof($listid) != 0)
					{
						echo "Annulation d'une demande d'alimentation.<br>";
						echo "<form name='form_esignature_annule'  method='post' >";
						echo "<input type='hidden' name='userid' value='" . $userid . "'>";
						echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
						echo "<select name='esignatureid_annule' id='esignatureid_annule'>";
						foreach ($listid as $id)
						{
    				        $alimcet->load($id);
//    				        echo "alimcet->typeconges() => " . $alimcet->typeconges() . "  AnneeRef = " . substr($fonctions->anneeref(),2,2) . "<br>";
//    				        if ($alimcet->typeconges() == "ann" . substr(($fonctions->anneeref()-1),2,2))
//				            {
    				            echo "<option value='" . $id  . "'>" . $id ." => ".$alimcet->statut()."</option>";
//                          }
						}
						
						echo "</select>";
						echo "<br><br>";
						echo "<input type='hidden' name='mode' value='" . $mode . "'>";
						echo "<input type='submit' name='annuler_demande' id='annuler_demande' value='Annuler la demande' onclick=\"return confirm('Annuler la demande ?')\">";
						echo "</form>";
						if (isset($error_suppr))
						{
                            echo $fonctions->showmessage(fonctions::MSGERROR, $error_suppr);
						}
						echo "<br>";
					}
				}
				else
					echo "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats est dépassé. (".$fonctions->formatdate($limitereliq).")<br>";
			}
			else {
				echo "Annulation de demande d'alimentation impossible car la date limite d'utilisation des reliquats est invalide. <br>";
			}
		}
		else echo "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats n'est pas défini.<br>";
	}
	else 
	{
        echo $fonctions->showmessage(fonctions::MSGWARNING, "Annulation de demande d'alimentation impossible car une demande de droit d'option est en cours ou validée.");
	}
	echo $agent->afficheAlimCetHtml();
	echo $agent->soldecongeshtml($anneeref + 1);
}
    
?>

</body>
</html>

