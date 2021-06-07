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
    
    $user = new agent($dbcon);
    $user->load($userid);
    
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
    $id_model = "239797";
    $eSignature_url = "https://esignature-test.univ-paris1.fr";
    echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . "<br>";
    
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
    echo "L'URL d'appel du WS G2T est : " . $g2t_ws_url . "/ws/alimentationWS.php";
?>
    <script type="text/javascript">
          //window.addEventListener("load", function(event) {
          //  window.open('http://esignature.univ-paris1.fr');
          //});
   	</script>	

    
<?php     
    echo "<br>" . print_r($_POST,true);
    echo "<br><br><br>";
    echo "<br><hr size=3 align=center><br>";
    
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
            // On récupère le "edupersonprincipalname" (EPPN) de l'agent en cours
            if (!is_null($agentid))
            {
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
            }
            
            
            // On appelle le WS eSignature pour créer le document
            $curl = curl_init();
            echo "EPPN de l'agent => " . $agent_eppn . ". <br>";
            //$params = ['eppn' => "$agent_eppn"]; //, 'recipientEmails' => array("0*pacomte@univ-paris1.fr") , 'targetEmails' => array("pacomte@univ-paris1.fr", "pascal.comte@univ-paris1.fr")];  ///  exemple multi paramètre => $params = ['param1' => 'valeur1', 'param2' => 'valeur2', 'param3' => 'valeur3'];
    
            $params = array
            (
                'eppn' => "$agent_eppn",
                'targetEmails' => array
                (
                    'pacomte@univ-paris1.fr',
                    'pascal.comte@univ-paris1.fr'
                )
            );
            if ($responsable == 'resp_demo')
            {
                $params['recipientEmails'] = array
                    (
                        "2*pascal.comte@univ-paris1.fr",
                        "2*elodie.briere@univ-paris1.fr"
                    );
            }
            else // On met le vrai responsable de l'agent
            {
                $structid = $agent->structureid();
                $struct = new structure($dbcon);
                $struct->load($structid);
                $resp = $struct->responsable();
                if (($resp->mail() . "") <> "")
                {
                    $params['recipientEmails'] = array
                    (
                        "2*" . $resp->mail()
                    );
                }
                else
                {
                    echo "<br><br><font color='red'><B>Il n'y a pas de responsable pour la structure " . $struct->nomlong()  ."</B></font><br>";
                }
            }
            
            if (!is_null($drh_niveau))
            {
                $params['recipientEmails'][] = '3*canica.sar@univ-paris1.fr';
            }
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
            echo "<br>Output = " . $params_string . '<br><br>';
            
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/forms/' . $id_model  . '/new',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ];
            curl_setopt_array($curl, $opts);
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
                    echo "Id de la nouvelle demande = " . $id . "<br>";
                    $alimentationCET->esignatureid($id);
                    $alimentationCET->esignatureurl($eSignature_url . "/user/signrequests/".$id);
                    $alimentationCET->statut("En préparation");
                    
                    $erreur = $alimentationCET->store();
                }
                if ($erreur <> "")
                {
                    echo "Erreur (création) = $erreur <br>";
                }
                else
                {
                    //var_dump($alimentationCET);
                    echo "La sauvegarde (création) s'est bien passée...<br><br>";
                }
            }
            else
            {
                echo "Oups, la création de la demande dans eSignature a échoué !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.<br><br>";
            }
        }
    }
    
    
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
    if ($erreur = "")    
        $valeur_a = $cet->cumultotal()-$cet->jrspris();
    else
        $valeur_a = 0;
    $valeur_b = $solde->droitaquis();
    $valeur_c = $solde->droitpris();
    $valeur_d = $valeur_b-$valeur_c;
    
    //////////////////////////////////////
    // Pour test !!!!
    $valeur_d = 3;
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
    	else if (parseInt(valeur_f) > parseInt(valeur_d))
    	{
    		document.getElementById("label_f").innerHTML = "La valeur de la case F doit être inférieure ou égale à la case D.";
    		button.disabled = true;
    	}
    	else
    	{
    		document.getElementById("label_f").innerHTML = "";
        	valeur_a = document.getElementById("valeur_a").value;
        	valeur_d = document.getElementById("valeur_d").value;
        	document.getElementById("valeur_e").value = parseInt(valeur_d,10)-parseInt(valeur_f,10);
        	document.getElementById("valeur_g").value = parseInt(valeur_a,10)+parseInt(valeur_f,10);
    		button.disabled = false;
        }
    }
	</script>
<?php

    echo "Création d'une demande d'alimentation de CET + création du document correspondant dans eSignature.<br>";
    echo "<form name='creation_alimentation'  method='post' >";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
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
    echo "<br><br>Choix du responsable :<br>";
    echo "<input type='radio' id='resp_demo' name='responsable' value='resp_demo' checked><label for='resp_demo'>Responsable de démo (Pascal+Elodie)</label>";
    echo "&nbsp;&nbsp;&nbsp;";
    $structid = $agent->structureid();
    $struct = new structure($dbcon);
    $struct->load($structid);
    $resp = $struct->responsable();
    echo "<input type='radio' id='resp_vrai' name='responsable' value='resp_vrai'><label for='resp_vrai'>Vrai responsable de l'agent (" . $resp->identitecomplete() .  " - " .  $resp->mail() . ")</label>";
    echo "<br><br>";
    echo "<input type='checkbox' id='drh_niveau' name='drh_niveau'><label for='drh_niveau'>Ajouter un 3e niveau dans le circuit de validation (ATTENTION : en cours de test => Destinataire : Canica)</label><br>";
    echo "<br><br>";
    echo "<input type='submit' name='cree_demande' id='cree_demande' value='Soumettre' disabled>";
    echo "</form>";
    
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
    
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Simulation d'appel des WS G2T par eSignature => mode GET : Récupération des informations d'une demande d'alimentation.<br>";
    
    if (! is_null($get_g2t_info))
    {
        // Appel du WS avec Curl
        echo "Appel de CURL (Méthode GET) -- Recupération des infos d'une demande....<br>";
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $g2t_ws_url . "/ws/alimentationWS.php?esignatureid=" . $esignatureid,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
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
    
    echo "<br><hr size=3 align=center><br>";
    echo "<br>Récupération de toutes les informations d'une demande d'alimentation dans eSignature + MAJ statut de la demande dans G2T.<br>";
    
    if (!is_null($esignature_info))
    {
        // On appelle le WS eSignature pour voir les infos du document
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $esignatureid_get_info,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
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
        $statut = $response['status'];
        echo "Le statut eSignature de la demande est : $statut. <br>";
        echo "On va mettre à jour le statut G2T de la demande en fonction du statut eSignature : ";
        $alimentationCET = new alimentationCET($dbcon);
        $erreur = $alimentationCET->load($esignatureid_get_info);
        if ($error <> "")
        {
            echo "Erreur lors du chargement de la demande d'alimentation (esignatureid_get_info = $esignatureid_get_info) à partir de G2T : $erreur.<br>";
        }
        else
        {
            switch (strtolower($statut))
            {
                //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                case 'draft' :
                case 'pending' :
                case 'signed' :
                case 'checked' :
                    $statut = "En cours";
                    break;
                case 'refused':
                    $statut = "Refusée";
                    break;
                case 'completed' :
                case 'exported' :
                case 'archived' :
                case 'cleaned' :
                    $statut = "Signée";
                    break;
                case 'deleted' :
                case 'canceled' :
                    $statut = "Abandonnée";
                    break;
                default :
                    $statut = "Statut inconnu";
            }
            echo "<b>L'ancien statut de la demande $esignatureid_get_info est : " . $alimentationCET->statut() . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Le nouveau statut sera : $statut. </b><br>";
            $alimentationCET->statut($statut);
            $alimentationCET->store();
        }
        echo "<br><br>";
        
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
        echo "<form name='form_esignature_info'  method='post' >";
        echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
        echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
        echo "<select name='esignatureid_get_info' id='esignatureid_get_info'>";
        while ($result = mysqli_fetch_row($query))
        {
            echo "<option value='" . $result["0"]  . "'>" . $result["0"] . "</option>";
            
        }
        echo "</select>";
        echo "<br><br>";
        echo "<input type='submit' name='esignature_info' id='esignature_info' value='Info de la demande'>";
        echo "</form>";
    }
    
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
                CURLOPT_URL => $g2t_ws_url . "/ws/alimentationWS.php",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXY => ''
                ];
            curl_setopt_array($curl, $opts);
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
