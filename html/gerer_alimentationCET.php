<?php // TODO : Contrôler présence d'un droit d'option avec statut différent de abandonné/refusé
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

	$user->supprimeDemandeAlimentation();
	/*echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . " id du modele " .$id_model. "<br>";

    echo "L'URL d'appel du WS G2T est : " . $full_g2t_ws_url;
    echo "<br>" . print_r($_POST,true);*/
    //echo "<br><br><br>";

    
    if (is_null($agentid))
    {
        $msg_erreur =  "Impossible de déterminer l'id de l'agent en cours !<br>";
        echo "<P style='color: red'><B><FONT SIZE='5pt'>";
        echo $msg_erreur . " </B></FONT></P>";
        error_log(basename(__FILE__) . " uid : " . $agentid . " : " . $fonctions->stripAccents($msg_erreur));
        exit;
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
    
    echo "<br><hr size=3 align=center><br>";
    // Affichage des demandes d'alimentation dans la base G2T
    $alimentationCET = new alimentationCET($dbcon);
    $agent = new agent($dbcon);
    $agent->load($agentid);
    echo $agent->afficheAlimCetHtml();
    
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
    			$agent->afficheAlimCetHtmlPourSuppr('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE, $alimentationCET::STATUT_VALIDE));
	    	else 
	    		echo "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats est dépassé. (".$fonctions->formatdate($limitereliq).")<br>";
    	}
    	else {
    		echo "Annulation de demande d'alimentation impossible car la date limite d'utilisation des reliquats est invalide. <br>";
    	}
    }
    else echo "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats n'est pas défini.<br>";
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
    if (count(array($affectationliste)) > 0)
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
    		document.getElementById("label_f").innerHTML = "La valeur de la case F n'est pas un nombre.";
    		button.disabled = true;
    	}    	
    	else if (!isInt(valeur_f))
    	{
    		document.getElementById("label_f").innerHTML = "La valeur de la case F doit être un entier.";
    		button.disabled = true;
    	}
    	else if (parseInt(valeur_f) <= 0)
    	{
     		document.getElementById("label_f").innerHTML = "La valeur de la case F doit être positive.";
    		button.disabled = true;
    	}
    	else if (parseInt(valeur_f) > parseInt(plafond))
    	{
    		document.getElementById("label_f").innerHTML = "La valeur de la case F doit être inférieure ou égale au plafond.";
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
if ($today < $fonctions->debutalimcet() || $today > $fonctions->finalimcet())
{
	echo "La campagne d'alimentation du CET est fermée actuellement.<br>";
}
else {
	if (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
	{
		echo "Vous avez une demande d'alimentation en cours. Vous pourrez en effectuer une nouvelle lorsque celle-ci sera terminée ou annulée. <br>";
		/*echo "Souhaitez-vous annuler la demande ? <br>";
		echo "<form name='annuler_alimentation'  method='post' >";
		echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
		echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
		echo "<input type='submit' name='annule_demande' id='annule_demande' value='Annuler' onclick=\"return confirm('Annuler la demande d\'alimentation du CET ?')\">";
		echo "</form>";*/
		
	}
	elseif (sizeof($agent->getDemandesOption('', array($optionCET::STATUT_EN_COURS, $optionCET::STATUT_PREPARE))) != 0)
	{
		echo "Vous avez une demande de droit d'option en cours. Vous pourrez effectuer une nouvelle demande d'alimentation lorsque celle-ci sera terminée ou annulée. <br>";
	}
	elseif ($hasInterruptionAff && $valeur_a == 0)
	{
		echo "Votre ancienneté n'est pas suffisante pour alimenter votre CET (ancienneté d'au minimum un an sans interruption requise). <br>";
	}
	else 
	{
		$pr = $agent->getPlafondRefCet();
		echo "Plafond de référence pour l'agent : $pr <br>";
		// Consommation des congés au début de la période (case C)
		$consodeb = $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()-2).'0101', ($fonctions->anneeref()).$fonctions->finperiode());
		echo "Congés ".($fonctions->anneeref() - 1)."/".$fonctions->anneeref()." consommés au ".$fonctions->formatdate(($fonctions->anneeref()-1).$fonctions->finperiode())." : $consodeb<br>";
		
		// Consommation des congés entre le debut de la période et la demande
		$consoadd = $agent->getNbJoursConsommés($fonctions->anneeref() - 1, ($fonctions->anneeref()).$fonctions->debutperiode(), ($fonctions->anneeref()+1).$fonctions->finperiode());
		echo "Congés ".($fonctions->anneeref() - 1)."/".$fonctions->anneeref()." consommés depuis le ".$fonctions->formatdate(($fonctions->anneeref()).$fonctions->debutperiode())." : ".$consoadd." <br>";
		
		$nbjoursmax = floor($pr - $consodeb - $consoadd);
		if ($nbjoursmax < 0)
			$nbjoursmax = 0;
		echo "Nombre de jours déposables sur le CET : ".$nbjoursmax." <br><br>";
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
		echo $agent->soldecongeshtml($anneeref + 1);
	    
	    echo "<br><hr size=3 align=center><br>";
	    
	    echo "Création d'une demande d'alimentation de CET + création du document correspondant dans eSignature.<br>";
	    //echo 'Structure complète d\'affectation : '.$structure->nomcompletcet().'<br>';
	    echo "<form name='creation_alimentation'  method='post' >";
	    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
	    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
	    echo "Plafond de jours déposables : <input type='text' name='plafond' readonly id='plafond' value='" . $nbjoursmax . "' style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Solde du CET avant versement (Case A) : <input type=text placeholder='Case A' name=valeur_a id=valeur_a value=$valeur_a size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Droits à congés (en jours) au titre de l’année de référence (Case B) : <input type=text placeholder='Case B' name=valeur_b id=valeur_b value=$valeur_b size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Nombre de jours de congés utilisés au titre de l’année de référence (Case C) : <input type=text placeholder='Case C' name=valeur_c id=valeur_c value=$valeur_c size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Solde de jours de congés non pris au titre de l’année de référence (Case D) : <input type=text placeholder='Case D' name=valeur_d id=valeur_d value=$valeur_d size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Nombre de jours de congés reportés sur l’année suivante (Case E) : <input type=text placeholder='Case E' name=valeur_e id=valeur_e size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    echo "<br>";
	    echo "Alimentation du CET (Case F) : <input type=text placeholder='Case F' name=valeur_f id=valeur_f size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_f style='color: red;font-weight: bold; margin-left:20px;'></label>";
	    echo "<br>";
	    echo "Solde du CET après versement (Case G) : <input type=text placeholder='Case G' name=valeur_g id=valeur_g size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
	    //echo "<br><br>Choix du responsable :<br>";
	    //echo "<input type='radio' id='resp_demo' name='responsable' value='resp_demo' checked><label for='resp_demo'>Responsable de démo (Pascal+Elodie)</label>";
	    echo "&nbsp;&nbsp;&nbsp;";       
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
	    echo "Responsable de l'agent (" . $resp->identitecomplete() .  " - " .  $resp->mail() . ")";
	    echo "<br><br>";
	    //echo "<input type='checkbox' id='drh_niveau' name='drh_niveau' checked><label for='drh_niveau'>Ajouter un 3e niveau dans le circuit de validation (Destinataire : " . $agent->identitecomplete()  .")</label><br>";
	   // echo "<br><br>";
	    echo "<input type='submit' name='cree_demande' id='cree_demande' value='Soumettre' disabled>";
	    echo "</form>";
	}
}



/*
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Affichage d'une demande dans un nouvel onglet.<br>";
    
    $sql = "SELECT ESIGNATUREID,ESIGNATUREURL FROM ALIMENTATIONCET WHERE HARPEGEID = '" .  $agentid . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
        echo $errlog;
    }
    elseif (mysqli_num_rows($query) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Aucune demande d'alimentation pour l'agent " . $agent->identitecomplete() . "<br>";;
        echo $errlog;
    }
    else
    {
        echo "<form name='form_aff_demande'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<select name='esignatureid_aff' id='esignatureid_aff'>";
        while ($result = mysqli_fetch_row($query))
        {
            echo "<option value='" . $result["1"]  . "'>" . $result["0"]  . "</option>";
            
        }
        echo "</select>";
        echo "<br><br>";
        echo "<input type='submit' name='aff_demande' id='aff_demande' value='Afficher la demande' onclick='opendemande(); return false;'>";
        echo "</form>";
    }
*/    
/*
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Simulation d'appel des WS G2T par eSignature => mode GET : Récupération des informations d'une demande d'alimentation.<br>";
    
    if (! is_null($get_g2t_info))
    {
        // Appel du WS avec Curl
        echo "Appel de CURL (Méthode GET) -- Recupération des infos d'une demande....<br>";
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $full_g2t_ws_url . "?esignatureid=" . $esignatureid,
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
    }

    $sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE HARPEGEID = '" .  $agentid . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
        echo $errlog;
    }
    elseif (mysqli_num_rows($query) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Aucune demande d'alimentation pour l'agent " . $agent->identitecomplete() . "<br>";;
        echo $errlog;
    }
    else
    {
        echo "<form name='form_esignatureid_get'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<select name='esignatureid_get' id='esignatureid_get'>";
        while ($result = mysqli_fetch_row($query))
        {
            echo "<option value='" . $result["0"]  . "'>" . $result["0"]  . "</option>";

        }
        echo "</select>";
        echo "<br><br>";
        echo "<input type='submit' name='get_g2t_info' id='get_g2t_info'  value='Soumettre'>";
        echo "</form>";
    }
*/   
   echo "<br><hr size=3 align=center><br>";
   /*  echo "<br>Synchronisation du statut de la demande G2T avec le statut de la demande eSignature.<br>";
     
    
    $sql = "SELECT ESIGNATUREID,STATUT FROM ALIMENTATIONCET WHERE HARPEGEID = '" .  $agentid . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
        echo $errlog;
    }
    elseif (mysqli_num_rows($query) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Aucune demande d'alimentation pour l'agent " . $agent->identitecomplete() . "<br>";;
        echo $errlog;
    }
    else
    {
        echo "<form name='form_esignature_info'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<select name='esignatureid_get_info' id='esignatureid_get_info'>";
        while ($result = mysqli_fetch_row($query))
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Synchronisation de la demande $esignatureid_get_info avec eSignature avant affichage."));
            $fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result["0"]);
            echo "<option value='" . $result["0"]  . "'>" . $result["0"] . " => " . $result["1"] . "</option>";
        }
        echo "</select>";
        echo "<br><br>";
        echo "<input type='submit' name='esignature_info' id='esignature_info' value='Synchronisation de la demande'>";
        echo "</form>";
    }
    
    
    echo "<br><hr size=3 align=center><br>";*/
    
    // TEST DRH
    
    if (isset($_POST['controler']))
    {
    	$erreur_test = "";
    	if (isset($_POST['plafond_ref_test']))
    	{
    		$plafond_ref = $_POST['plafond_ref_test'];
    		if ($plafond_ref == '')
    			$erreur_test .= "Le plafond de référence n'est pas renseigné.<br>";
    			elseif (!is_numeric($plafond_ref) || !is_int($plafond_ref+0) || $plafond_ref < 0)
    			$erreur_test .= "Le plafond de référence doit être un entier positif. <br>";
    	}
    	if (isset($_POST['quotite_test']))
    	{
    		$quotite = $_POST['quotite_test'];
    		if ($quotite == '')
    			$erreur_test .= "La quotité n'est pas renseignée.<br>";
    			elseif (!is_numeric($quotite) || !is_int($quotite+0) || $quotite < 0)
    			$erreur_test .= "La quotité doit être un entier positif.<br>";
    	}
    	if (isset($_POST['valeur_a_test']))
    	{
    		$valeur_a_test = $_POST['valeur_a_test'];
    		if ($valeur_a_test == '')
    			$erreur_test .= "Le solde du CET n'est pas renseigné.<br>";
    			elseif (!is_numeric($valeur_a_test) || !is_int($valeur_a_test+0) || $valeur_a_test < 0)
    			$erreur_test .= "Le solde du CET doit être un entier positif.<br>";
    	}
    	if (isset($_POST['valeur_b_test']))
    	{
    		$valeur_b_test = $_POST['valeur_b_test'];
    		if ($valeur_b_test == '')
    			$erreur_test .= "Le droit à congés n'est pas renseigné.<br>";
    			elseif (!is_numeric($valeur_b_test) || !is_int($valeur_b_test+0) || $valeur_b_test < 0)
    			$erreur_test .= "Le droit à congés doit être un entier positif.<br>";
    	}
    	if (isset($_POST['valeur_c_test']))
    	{
    		$valeur_c_test = $_POST['valeur_c_test'];
    		if ($valeur_c_test == '')
    			$erreur_test .= "Le nombre de jours utilisés avant le 01/09 n'est pas renseigné.<br>";
    			elseif (!is_numeric($valeur_c_test) || $valeur_c_test < 0)
    			$erreur_test .= "Le nombre de jours utilisés avant le 01/09 doit être positif.<br>";
    	}
    	if (isset($_POST['conge_supp']))
    	{
    		$conge_supp = $_POST['conge_supp'];
    		if ($conge_supp == '')
    			$erreur_test .= "Le nombre de jours utilisés depuis le 01/09 n'est pas renseigné.<br>";
    			elseif (!is_numeric($conge_supp) || $conge_supp < 0)
    			$erreur_test .= "Le nombre de jours utilisés depuis le 01/09 doit être positif.<br>";
    	}
    	if (isset($_POST['valeur_f_test']))
    	{
    		$valeur_f_test = $_POST['valeur_f_test'];
    		if ($valeur_f_test == '')
    			$erreur_test .= "Le nombre de jours à déposer n'est pas renseigné.<br>";
    			elseif (!is_numeric($valeur_f_test) || !is_int($valeur_f_test+0) || $valeur_f_test < 0)
    			$erreur_test .= "Le nombre de jours à déposer doit être un entier positif.<br>";
    	}
    	if ($erreur_test == '')
    	{
    		// tout est ok
    		unset($erreur_test);
    		$plafond_quot_test = $plafond_ref * $quotite / 100;
    		$valeur_d_test = $valeur_b_test - $valeur_c_test;
    		$nbjmax = floor($plafond_quot_test - $valeur_c_test - $conge_supp);
    		$nbjmax = ($nbjmax < 0) ? 0 : $nbjmax;
    		$valeur_e_test = $valeur_d_test - $valeur_f_test;
    		if ($valeur_e_test < 0)
    		{
    			$erreur_test = "Le nombre de jours déposés est supérieur au nombre de jours disponibles.<br>";
    			$valeur_e_test = 'erreur';
    			$valeur_g_test = 'erreur';
    		}
    		elseif ($valeur_f_test > $nbjmax)
    		{
    			$erreur_test = "Le nombre de jours déposés est supérieur au nombre de jours maximum possible.<br>";
    			$valeur_e_test = 'erreur';
    			$valeur_g_test = 'erreur';
    		}
    		else
    		{
    			$valeur_g_test = $valeur_f_test + $valeur_a_test;
    		}
    		
    	}
    	else {
    		$plafond_quot_test = 0;
    		$valeur_d_test = 0;
    		$nbjmax = 0;
    		$valeur_e_test = 0;
    		$valeur_g_test = 0;
    	}
    }
    else {
    	$plafond_quot_test = 0;
    	$valeur_d_test = 0;
    	$nbjmax = 0;
    	$valeur_e_test = 0;
    	$valeur_g_test = 0;
    	$plafond_ref = NULL;
    	$quotite = NULL;
    	$valeur_a_test = NULL;
    	$valeur_b_test = NULL;
    	$valeur_c_test = NULL;
    	$valeur_f_test = NULL;
    	$conge_supp = NULL;
    }
    
    echo "Test du calcul DRH.<br>";
    echo "<form name='test_calcul'  method='post' >";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    echo "Plafond de référence : <input type=text placeholder='Plafond réf' name=plafond_ref_test id=plafond_ref_test size=3 value=$plafond_ref >";
    echo "<br>";
    echo "Quotité moyenne : <input type=text placeholder='quotité' name=quotite_test id=quotite_ref_test size=3 value=$quotite>";
    echo "<br>";
    echo "Plafond modifié : <input type=text placeholder='Plafond quotite' name=plafond_quot_test id=plafond_quot_test value=$plafond_quot_test size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;'>";
    echo "<br>";
    echo "Solde du CET avant versement (Case A) : <input type=text placeholder='Case A' name=valeur_a_test id=valeur_a_test size=3 value=$valeur_a_test>";
    echo "<br>";
    echo "Droits à congés (en jours) au titre de l’année de référence (Case B) : <input type=text placeholder='Case B' name=valeur_b_test id=valeur_b_test size=3 value=$valeur_b_test>";
    echo "<br>";
    echo "Nombre de jours de congés utilisés au titre de l’année de référence (au 01/09) (Case C) : <input type=text placeholder='Case C' name=valeur_c_test id=valeur_c_test size=3 value=$valeur_c_test>";
    echo "<br>";
    echo "Nombre de jours de congés utilisés au titre de l’année de référence (DEPUIS LE 01/09) : <input type=text placeholder='Congés supp' name=conge_supp id=conge_supp size=3 value=$conge_supp>";
    echo "<br>";
    echo "Solde de jours de congés non pris au titre de l’année de référence (Case D) : <input type=text placeholder='Case D' name=valeur_d_test id=valeur_d_test size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' value=$valeur_d_test >";
    echo "<br>";
    echo "Nombre de jours max déposables : <input type=text placeholder='Plafond' name=plafond_test id=plafond_test size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' value=$nbjmax >";
    echo "<br>";
    echo "Nombre de jours de congés reportés sur l’année suivante (Case E) : <input type=text placeholder='Case E' name=valeur_e_test id=valeur_e_test size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' value=$valeur_e_test >";
    echo "<br>";
    echo "Alimentation du CET (Case F) : <input type=text placeholder='Case F' name=valeur_f_test id=valeur_f_test size=3 onchange='update_case_test()' onkeyup='update_case_test()' value=$valeur_f_test ><label id=label_f style='color: red;font-weight: bold; margin-left:20px;' ></label>";
    echo "<br>";
    echo "Solde du CET après versement (Case G) : <input type=text placeholder='Case G' name=valeur_g_test id=valeur_g_test size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' value=$valeur_g_test ><br>";
    if (isset($erreur_test))
    	echo "<p style='color:red;'>".$erreur_test."</p>";
    	echo "<input type='submit' name='controler' id='controler' value='Soumettre'>";
    	echo "</form>";
    	
    	
// FIN TEST DRH
    

/*
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Simulation d'appel des WS G2T par eSignature => mode POST : Changement de statut d'une demande d'alimentation.<br>";
    
    if (!is_null($modif_statut))
    {
        echo "Appel de CURL (Méthode POST) => Modification du statut de la demande $esignatureid en <b> $statut </b><br>";
        if (is_null($statut) or is_null($description))
        {
            echo "ATTENTION : Le statut ou la description du statut sont null!<br>";
        }
        else
        {
            $curl = curl_init();
            $params = ['esignatureid' => "$esignatureid", 'status' => "$statut" , 'reason' => "$description"];  ///  exemple multi paramètre => $params = ['param1' => 'valeur1', 'param2' => 'valeur2', 'param3' => 'valeur3'];
            $params_string = http_build_query($params);
            //echo "<br>Param = " . $params_string . "<br><br>";
            $opts = [
            CURLOPT_URL => $full_g2t_ws_url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
                ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_PROXY, '');
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
            echo "<br>";
        }
    }
    
    $sql = "SELECT ESIGNATUREID,STATUT FROM ALIMENTATIONCET WHERE HARPEGEID = '" .  $agentid . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
        echo $errlog;
    }
    elseif (mysqli_num_rows($query) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Aucune demande d'alimentation pour l'agent " . $agent->identitecomplete() . "<br>";;
        echo $errlog;
    }
    else
    {
        echo "<form name='form_esignatureid_post'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<select name='esignatureid_post' id='esignatureid_post'>";
        while ($result = mysqli_fetch_row($query))
        {
            echo "<option value='" . $result["0"]  . "'>" . $result["0"] . " => " . $result["1"] . "</option>";
            
        }
        echo "</select>";
        echo "<br><br>";
        echo "Statut : <select name='statut' id='statut'>";
        echo "<option value='En préparation'>En préparation</option>";
        echo "<option value='En cours'>En cours</option>";
        echo "<option value='Refusée'>Refusée</option>";
        echo "<option value='Signée'>Signée</option>";
        echo "<option value='Abandonnée'>Abandonnée</option>";
        echo "<option value='Plouf test'>Plouf test</option>";
        echo "</select>";
        echo "<br>";
        echo "Motif changement de statut : <input type='text' name='description' value='' size=60>";
        echo "<br><br>";
        echo "<input type='submit' name='modif_statut' value='Modifier statut'>";
        echo "</form>";
    }
*/
        
/*
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Récupération de toutes les informations d'une demande d'alimentation dans eSignature.<br>";
    echo "<form name='form_esignatureid_post'  method='post' >";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
    echo "<br>";
    echo "<input type='submit' name='send_mail' value='Envoyer mail'>";
    echo "</form>";
    
    if (!is_null($send_mail))
    {
        $user->sendmail($user,"Le titre est accentué.", "Bonjour, c'est le mail accentué",null,null);
        echo "Message envoyé à " . $user->identitecomplete() . "\n";
    }
*/    
    
?>
