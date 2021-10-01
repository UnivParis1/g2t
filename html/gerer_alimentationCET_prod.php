<?php 
    require_once ('CAS.php');
    include './includes/casconnection.php';

    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
        
    require_once ('./includes/dbconnection.php');
    require_once ('./class/fonctions.php');
    require_once ('./class/agent.php');
    require_once ('./class/structure.php');
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
    require_once ("./class/periodeobligatoire.php");
    require_once ("./class/alimentationCET.php");
    require_once ("./class/optionCET.php");
    
    $user = new agent($dbcon);
    $user->load($userid);
    $optionCET = new optionCET($dbcon);

    if (isset($_POST["agentid"]))
    {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) {
            $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
            $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
            $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
            $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
            $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "(uid=" . $agentid . ")";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array(
                "$LDAP_CODE_AGENT_ATTR"
            );
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            // echo "Le numéro HARPEGE de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
            if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
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
    $id_model = $fonctions->getidmodelalimcet();
    $eSignature_url = "https://esignature-test.univ-paris1.fr";
    
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
    $full_g2t_ws_url = $fonctions->get_g2t_ws_url() . "/ws/alimentationWS.php";
?>
    <script type="text/javascript">
          //window.addEventListener("load", function(event) {
          //  window.open('http://esignature.univ-paris1.fr');
          //});
   	</script>	

    
<?php 

	//$user->supprimeDemandeAlimentation();	
	
	/*echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . " id du modele " .$id_model. "<br>";

    echo "L'URL d'appel du WS G2T est : " . $full_g2t_ws_url;
    echo "<br>" . print_r($_POST,true);*/
    //echo "<br><br><br>";

    
	// Si on est en mode 'rh' et qu'on n'a pas encore choisi l'agent, on affiche la zone de sélection.
	if (is_null($agentid) and $mode == 'rh')
	{
		echo "<form name='demandeforagent'  method='post' action='gerer_alimentationCET_prod.php'>";
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
        
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' value='Soumettre' >";
        echo "</form>";
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
	    	if ($statut_actuel == alimentationCET::STATUT_VALIDE)
	    	{
	    		// réattribution des reliquats
	    		$solde = new solde($dbcon);
	    		//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le type de congés est " . $alimentationCET->typeconges()));
	    		$solde->load($agent->harpegeid(), $alimentationCET->typeconges());
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
	    		$erreur = $cet->load($agent->harpegeid());
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
	    	
	    	// purger esignature
	    	
	    	$eSignature_url = "https://esignature-test.univ-paris1.fr";
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
	    	$result = curl_exec($ch);
	    	$result = json_decode($result);
	    	$error = curl_error ($ch);
	    	curl_close($ch);
	    	$errlog = '';
	    	if ($error != "")
	    	{
	    		$errlog = "Erreur Curl = " . $error . "<br><br>";
	    	}
	    	
	    	// Abandon dans G2T
	    	$alimentationCET->statut($alimentationCET::STATUT_ABANDONNE);
	    	$alimentationCET->motif("Annulation à la demande de ".$user->identitecomplete());
	    	$alimentationCET->store();
	    	$errlog = "L'utilisateur " . $user->identitecomplete() . " (identifiant = " . $user->harpegeid() . ") a supprimé la demande d'alimentation du CET de ".$agent->identitecomplete()." (esignatureid = ".$esignatureid_annule.")";
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
        
        if (((float)$valeur_f+0)==0)
        {
            echo "<br><br><font color='red'><B>La valeur de la case F est vide ou égale à 0... On ne peut pas sauvegarder la demande d'alimentation.</B></font><br>";
        }
        else
        {
            if (!is_null($agentid))
            {
                // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
                $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
                $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
                $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
                $LDAP_CODE_AGENT_ATTR = "edupersonprincipalname";
                $con_ldap = ldap_connect($LDAP_SERVER);
                ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
                $filtre = "(supannEmpId=" . $agentid . ")";
                //echo "Filtre = $filtre <br>";
                $dn = $LDAP_SEARCH_BASE;
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                //echo "Info = " . print_r($info,true) . "<br>";
                //echo "L'EPPN de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                    $agent_eppn = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                    //echo "Agent EPPN = $agent_eppn <br>";
                }
                
                
                // On récupère le mail de l'agent en cours
                $LDAP_CODE_AGENT_ATTR = "mail";
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                //echo "Info = " . print_r($info,true) . "<br>";
                //echo "L'email de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                    $agent_mail = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                    // echo "Agent eMail = $agent_mail <br>";
                }
            }
            
            
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
                'targetUrl' => "$full_g2t_ws_url"
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
            	if ($agent->estresponsable())
            	{
            		$resp = $structure->resp_envoyer_a($code);
            	}
            	else
            	{
            		$resp = $structure->agent_envoyer_a($code);
            	}
            	$params['recipientEmails'] = array
            	(
            	    "1*" . $agent_mail,
            	    "2*" . $resp->mail()
            	);
             // }
            
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
                CURLOPT_URL => $eSignature_url . '/ws/forms/' . $id_model  . '/new',
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
            if ("$id" <> "")
            {
                if (is_array($id))
                {
                    $erreur = print_r($id,true);
                }
                else
                {
                    //echo "Id de la nouvelle demande = " . $id . "<br>";
                    $alimentationCET->esignatureid($id);
                    $alimentationCET->esignatureurl($eSignature_url . "/user/signrequests/".$id);
                    $alimentationCET->statut($alimentationCET::STATUT_PREPARE);
                    
                    $erreur = $alimentationCET->store();
                }
                if ($erreur <> "")
                {
                    echo "Erreur (création) = $erreur <br>";
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
                echo "Oups, la création de la demande dans eSignature a échoué !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.<br><br>";
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
	    /*$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE HARPEGEID = '" .  $agentid . "'";
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
	        echo "<tr><td class='titresimple'>Date création</td><td class='titresimple'>type congé</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
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
	    $valeur_c = $solde->droitpris();
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
	    
	    function update_case()
	    {
	    	//alert("Update Case est activé");
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
	    	else if (parseInt(valeur_f) > parseInt(plafond))
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
	        	document.getElementById("valeur_g").value = parseInt(valeur_a,10)+parseInt(valeur_f,10);
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
	$hasInterruptionAff = $user->hasInterruptionAffectation($ayearbefore, $today);
	echo "Alimentation du CET pour " . $agent->identitecomplete() . "<br><br>";
	if ($today < $fonctions->debutalimcet() || $today > $fonctions->finalimcet())
	{
		echo "<font color='#EF4001'>La campagne d'alimentation du CET est fermée actuellement. </font><br>";
		echo "<br>";
	}
	else 
	{
		if (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
		{
			echo "<font color='#EF4001'>Vous avez une demande d'alimentation en cours. Vous pourrez en effectuer une nouvelle lorsque celle-ci sera terminée ou annulée. </font><br>";
			echo "<br>";
			/*echo "Souhaitez-vous annuler la demande ? <br>";
			echo "<form name='annuler_alimentation'  method='post' >";
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
			echo "<input type='submit' name='annule_demande' id='annule_demande' value='Annuler' onclick=\"return confirm('Annuler la demande d\'alimentation du CET ?')\">";
			echo "</form>";*/
			
		}
		elseif (sizeof($agent->getDemandesOption('', array($optionCET::STATUT_EN_COURS, $optionCET::STATUT_PREPARE))) != 0)
		{
			echo "<font color='#EF4001'>Vous avez une demande de droit d'option en cours. Vous pourrez effectuer une nouvelle demande d'alimentation lorsque celle-ci sera terminée ou annulée. </font><br>";
			echo "<br>";
		}
		elseif (sizeof($agent->getDemandesOption('', array($optionCET::STATUT_VALIDE))) != 0)
		{
			echo "<font color='#EF4001'>Vous avez une demande de droit d'option validée. Vous ne pourrez pas effectuer de nouvelle demande d'alimentation cette année. </font><br>";
			echo "<br>";
		}
		elseif ($hasInterruptionAff && $valeur_a == 0)
		{
			echo "<font color='#EF4001'>Votre ancienneté n'est pas suffisante pour alimenter votre CET (ancienneté d'au minimum un an sans interruption requise). </font><br>";
			echo "<br>";
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
			
			$nbjoursmax = floor($pr - $consodeb);
			if ($nbjoursmax < 0)
				$nbjoursmax = 0;
			else 
			{
				$nbjoursrestants = $valeur_b - $consodeb - $consoadd;
				if ($nbjoursmax > $nbjoursrestants)
					$nbjoursmax = floor($nbjoursrestants);
			}
			// echo "Nombre de jours déposables sur le CET : ".$nbjoursmax." <br><br>";
			/*echo "Nombre de jours à déposer sur le CET <br>";
			echo "<form name='form_esignature_new_alim'  method='post' >";
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
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
			echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
			echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
			echo "Solde actuel de votre CET : $valeur_a jour(s)";
			echo "<br>";
			echo "Dépôt maximum : $nbjoursmax jour(s)";
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
			if ($agent->estresponsable())
			{
				$resp = $structure->resp_envoyer_a($code);
			}
			else
			{
				$resp = $structure->agent_envoyer_a($code);
			}
			echo "<br><br>";
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
				$listid = $agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE, $alimentationCET::STATUT_VALIDE));
				$htmltext = '';
				if (sizeof($listid) != 0)
				{
					echo "Annulation d'une demande d'alimentation.<br>";
					echo "<form name='form_esignature_annule'  method='post' >";
					echo "<input type='hidden' name='userid' value='" . $userid . "'>";
					echo "<input type='hidden' name='agentid' value='" . $agent->harpegeid() . "'>";
					echo "<select name='esignatureid_annule' id='esignatureid_annule'>";
					foreach ($listid as $id)
					{
						$alimcet->load($id);
						echo "<option value='" . $id  . "'>" . $id ." => ".$alimcet->statut()."</option>";
					}
					
					echo "</select>";
					echo "<br><br>";
					echo "<input type='hidden' name='mode' value='" . $mode . "'>";
					echo "<input type='submit' name='annuler_demande' id='annuler_demande' value='Annuler la demande'>";
					echo "</form>";
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
	echo $agent->afficheAlimCetHtml();
	echo $agent->soldecongeshtml($anneeref + 1);
}
    
?>
