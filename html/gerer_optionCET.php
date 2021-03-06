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
    
            
    $esignatureid = null;
        
    $cree_option = null;
    if (isset($_POST["cree_option"]))
        $cree_option = $_POST["cree_option"];
        
    $drh_niveau = null;
    if (isset($_POST["drh_niveau"]))
        $drh_niveau = $_POST["drh_niveau"];
        
    $responsable = null;
    if (isset($_POST["responsable"]))
        $responsable = $_POST["responsable"];
                                                                                        
    $esignatureid_get_info = null;
    if (isset($_POST["esignatureid_get_info"]))
        $esignatureid_get_info = $_POST["esignatureid_get_info"];
    
    $esignature_info = null;
    if (isset($_POST["esignature_info"]))
        $esignature_info = $_POST["esignature_info"];
    
    require ("includes/menu.php");

    $id_model = "251701";
    $eSignature_url = "https://esignature-test.univ-paris1.fr";
    
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
    $full_g2t_ws_url = $g2t_ws_url . "/ws/optionWS.php";
    
    
    //echo "La base de l'URL du serveur eSignature est : " .$eSignature_url . "<br>";
    //echo "L'URL d'appel du WS G2T est : " . $full_g2t_ws_url;
    //echo "<br>" . print_r($_POST,true);
    //echo "<br><br><br>";
    
    
    $anneeref = $fonctions->anneeref();

    // Création d'un droit d'option
    if (!is_null($cree_option))
    {
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
        echo "EPPN de l'agent => " . $agent_eppn . ". <br>";
        $params = ['eppn' => "$agent_eppn"]; //, 'recipientEmails' => array("0*pacomte@univ-paris1.fr") , 'targetEmails' => array("pacomte@univ-paris1.fr", "pascal.comte@univ-paris1.fr")];  ///  exemple multi paramètre => $params = ['param1' => 'valeur1', 'param2' => 'valeur2', 'param3' => 'valeur3'];
        
        $params = array
        (
            'eppn' => "$agent_eppn",
            'targetEmails' => array
            (
                "$agent_mail"
            ),
            'targetUrl' => "$full_g2t_ws_url"
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
            $params['recipientEmails'][] = '3*' . $agent_mail;
        }

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
                echo "Id de la nouvelle demande = " . $id . "<br>";
                $optionCET->esignatureid($id);
                $optionCET->esignatureurl($eSignature_url . "/user/signrequests/".$id);
                $optionCET->statut($optionCET::STATUT_PREPARE);
                
                $erreur = $optionCET->store();
            }
            if ($erreur <> "")
            {
                echo "Erreur (création) = $erreur <br>";
            }
            else
            {
                //var_dump($optionCET);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" La sauvegarde (création) s'est bien passée => eSignatureid = " . $id ));
                //echo "La sauvegarde (création) s'est bien passée...<br><br>";
            }
        }
        else
        {
            echo "Oups, la création du droit d'option dans eSignature a échouée !!==> Pas de sauvegarde du droit d'option dans G2T.<br><br>";
        }
    }


    if (!is_null($esignature_info))
    {
        // On appelle le WS G2T en GET pour demander à G2T de mettre à jour la demande
        $optionCET = new optionCET($dbcon);
        $erreur = $optionCET->load($esignatureid_get_info);
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid_get_info . " => Erreur = " . $erreur));
            echo "Erreur lors du chargement du droit d'option $esignatureid_get_info avant la synchronisation.<br>";
        }
        echo "<br><br>Le statut de la demande avant la synchronisation est : " . $optionCET->statut() . "<br>";
        
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
        
        $optionCET = new optionCET($dbcon);
        $erreur = $optionCET->load($esignatureid_get_info);
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid_get_info . " => Erreur = " . $erreur));
            echo "Erreur lors du chargement du droit d'option $esignatureid_get_info après la synchronisation.<br>";
        }
        echo "<br>Le statut de la demande après la synchronisation est : " . $optionCET->statut() . "<br>";
        
    }
    
    
    
    echo "<br><hr size=3 align=center><br>";
    // Affichage des droits d'option CET dans la base G2T
    $optionCET = new optionCET($dbcon);
    $agent = new agent($dbcon);
    $agent->load($agentid);
    
    
    $sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE HARPEGEID = '" .  $agentid . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
        echo $errlog;
    }
    else
    {
        echo "Informations sur les droits d'options sur CET pour " . $agent->identitecomplete() . "<br>";
        echo "<div id='droit_option_cet'>";
        echo "<table class='tableausimple'>";
        echo "<tr><td class='titresimple'>Date création</td><td class='titresimple'>Année de référence</td><td class='titresimple'>Nombre de jours RAFP</td><td class='titresimple'>Nombre de jours indemnisation</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
        echo "</tr>";
        while ($result = mysqli_fetch_row($query))
        {
            $option = new optionCET($dbcon);
            $id = $result[0];
            $option->load($id);
            echo "<tr><td class='cellulesimple'>" . $fonctions->formatdate(substr($option->datecreation(), 0, 10)).' '.substr($option->datecreation(), 10) . "</td><td class='cellulesimple'>" . $option->anneeref() . "</td><td class='cellulesimple'>" . $option->valeur_i() . "</td><td class='cellulesimple'>" . $option->valeur_j() . "</td><td class='cellulesimple'>" . $option->statut() . "</td><td class='cellulesimple'>" . $fonctions->formatdate($option->datestatut()) . "</td><td class='cellulesimple'>" . $option->motif() . "</td><td class='cellulesimple'><a href='" . $option->esignatureurl() . "' target='_blank'>".$option->esignatureurl()."</a></td></tr>";
            unset ($option);
        }
        echo "</table><br>";
        
        echo "</div>";
    }
    
    echo "<br>";
    echo $agent->soldecongeshtml("$anneeref");
    echo "<br>";
    

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
    	
    	// On récupère la valeur A
    	document.getElementById("valeur_a").value = document.getElementById("valeur_a").value.replace(",",".");
        valeur_a = document.getElementById("valeur_a").value;
        valeur_a = parseInt(valeur_a);
        
        // On récupère la valeur G
    	document.getElementById("valeur_g").value = document.getElementById("valeur_g").value.replace(",",".");
       	valeur_g = document.getElementById("valeur_g").value;
        valeur_g = parseInt(valeur_g);
        
    	// On récupère la valeur H 
    	document.getElementById("valeur_h").value = document.getElementById("valeur_h").value.replace(",",".");
    	valeur_h = document.getElementById("valeur_h").value;
    	valeur_h  = parseInt(valeur_h);

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
    			label_i.innerHTML = "La valeur de la case I n'est pas un nombre.";
    		deactive_button = true;
    	}    	
    	else if (!isInt(valeur_i))
    	{
    		if (label_i !== null)
    			label_i.innerHTML = "La valeur de la case I doit être un entier.";
    		deactive_button = true;
    	}
    	else if (parseInt(valeur_i) < 0)
    	{
    		if (label_i !== null)
    			label_i.innerHTML = "La valeur de la case I doit être positive ou nulle.";
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
    		document.getElementById("label_j").innerHTML = "La valeur de la case J n'est pas un nombre.";
    		deactive_button = true;
    	}    	
    	else if (!isInt(valeur_j))
    	{
    		document.getElementById("label_j").innerHTML = "La valeur de la case J doit être un entier.";
    		deactive_button = true;
    	}
    	else if (parseInt(valeur_j) < 0)
    	{
     		document.getElementById("label_j").innerHTML = "La valeur de la case J doit être positive ou nulle.";
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
        	if (valeur_g > 60)
        	{
        		debordementCET = valeur_g - 60;   // 60 => Nbre maxi sur le CET
        	}
        	
        	// S'il y a plus de jours que les 60 maximum => On doit forcément répartir ce "surplus" dans les case I (RAFP) et J (Indemnistation) 
        	if ((valeur_i + valeur_j) < debordementCET)
        	{
	     		if (label_i !== null)
	     		{
    				label_i.innerHTML = "La somme des valeurs I, J doit être supérieure ou égale à " + debordementCET + ".";
	     			document.getElementById("label_j").innerHTML = "La somme des valeurs I, J doit être supérieure ou égale à " + debordementCET + ".";
	     		}
	     		else
	     		{
	     			document.getElementById("label_j").innerHTML = "La valeur J doit être supérieure ou égale à " + debordementCET + ".";
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
    				label_i.innerHTML = "La somme des valeurs I, J doit être inférieure ou égale à " + valeur_h + ".";
	     			document.getElementById("label_j").innerHTML = "La somme des valeurs I, J doit être inférieure ou égale à " + valeur_h + ".";
	     		}
	     		else
	     		{
	     			document.getElementById("label_j").innerHTML = "La valeur J doit être inférieure ou égale à " + valeur_h + ".";
	     		}
        		deactive_button = true;
        	}
        	

            valeur_l = valeur_k + 15;
        	if (valeur_l > 60)
        	{
         		document.getElementById("label_l").innerHTML = "La valeur de la case L doit être inférieure à 60.";
        		deactive_button = true;
	    	}
        	else if ((valeur_l > (valeur_a + 10)) && (valeur_a >= 15))
        	{
	     		document.getElementById("label_l").innerHTML = "Ancien solde = " + valeur_a + " / Nouveau solde = " + valeur_l  + " => Impossible d'accroite son CET de plus de 10 jours.";
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
    
    echo "<br><hr size=3 align=center><br>";
    echo "<form name='simulation_option'  method='post' >";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
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
    //echo "<option value='stag'";
    //if ($typeagent == 'stag')
    //    echo " selected ";
    //echo ">Stagiaire</option>";
    echo "</select>";
    echo "<br><br>";
    echo "<input type='submit' name='simul_option' id='simul_option' value='Soumettre' >";
    echo "</form>";
    
    echo "<br><hr size=3 align=center><br>";
    echo "Création d'une option sur CET + création du document correspondant dans eSignature.<br>";
    //echo 'Structure complète d\'affectation : '.$structure->nomcompletcet().'<br>';
    echo "<form name='creation_option'  method='post' >";
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
     
    $valeur_a = "";
    $valeur_g = "";

    if (!is_null($simul_option))
    {
        echo "<div style='color: red;font-weight: bold;'>";
        $simul_a = str_replace(",",".",$simul_a);
        $simul_g = str_replace(",",".",$simul_g);
        //echo "Division entière de simul_a = " . intdiv($simul_a*10,10) . "  ceil(simul_a) = " . ceil($simul_a)   . "   float(simul_a)  = " . (float)$simul_a . "<br>";
        //echo "Division entière de simul_g = " . intdiv($simul_g*10,10) . "  ceil(simul_g) = " . ceil($simul_g)   . "  float(simul_g)  = " . (float)$simul_g . "<br>";
        
        echo "Simul_A = $simul_a  Simul_G = $simul_g <br>";
        if (is_null($simul_a) or is_null($simul_g))
        {
            echo "Au moins une des cases A ou G est nulle !<br>Impossible de poursuivre le test<br>";
        }
        else if (!is_numeric($simul_a) or !is_numeric($simul_g))
        {
            echo "Au moins une des cases A ou G n'est pas un nombre !<br>Impossible de poursuivre le test<br>";
        }
        else if ((ceil($simul_a) <> (float)$simul_a) or (ceil($simul_g) <> (float)$simul_g))
        {
            echo "Au moins une des cases A ou G n'est pas un nombre entier!<br>Impossible de poursuivre le test<br>";
        }
        else if (($simul_a < 0) or ($simul_g < 0))
        {
            echo "Au moins une des cases A ou G est un nombre négatif !<br>Impossible de poursuivre le test<br>"; 
        }
        else if ($simul_g < $simul_a)
        {
            echo "La valeur de la case G doit être supéreure à la valeur de la case A !<br>Impossible de poursuivre le test<br>"; 
        }
        else
        {
            // On a forcer les valeurs de simulation
            $valeur_a = $simul_a;
            $valeur_g = $simul_g;
            $alimentation = $valeur_g - $valeur_a;
            echo "ATTENTION : Les valeurs des case A et G ont été forcées !!!";
        }
        echo "</div>";
    }
    else
    {
        $cet = new cet($dbcon);
        $erreur = $cet->load($agentid);
        if ($erreur <> "")
        {
            echo "Pas de CET pour l'agent " . $agent->identitecomplete() . " ==> Fin du chargement de la page <br>";
            die();
        }
        
        $alimentation = $cet->cumulannuel($anneeref); // ATTENTION : Il faudra mettre $anneref - 1 car le droit d'option se fait l'année suivante !!!! A VERIFIER !!!!!
        //echo "Alimentation = XXXX" . $alimentation . "XXXX<br><br>";
        $valeur_a = $cet->cumultotal()-$cet->jrspris()-$alimentation;
        $valeur_g = $cet->cumultotal()-$cet->jrspris();
        $affectation = new affectation($dbcon);
        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y'));
        if (count((array)$affectationliste) == 0)
        {
            echo "Pas d'affectation actuellement => Impossible de poursuivre.<br>";
            die();
        }
        $affectation = current($affectationliste);
        if ($affectation->numcontrat() <> 0)
           $typeagent = 'cont';
        else
           $typeagent = "titu";
    }
    if ($typeagent == 'titu')
        echo "L'agent est : Titulaire.<br>";
    else if ($typeagent == 'cont')
        echo "L'agent est : Contractuel.<br>";
    else
        echo "L'agent est : !!! AUTRE !!!!.<br>";
    echo "<br><br>";
    echo "Solde du CET avant versement (Case A) : <input type=text placeholder='Case A' name=valeur_a id=valeur_a value='$valeur_a' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
    echo "<br>";
    echo "Solde du CET après versement (Case G) : <input type=text placeholder='Case G' name=valeur_g id=valeur_g value='$valeur_g' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
    echo "<br>";

    $valeur_h = (float)$valeur_g - 15;
    if ($valeur_h <= 0)
    {
        echo "<br><br>";
        echo "Votre solde de CET est insuffisant pour pouvoir exercer un droit d'option.<br>";
        echo "Impossible de continuer<br>";
        die();
    }
    
    
    echo "Nombre de jours dépassant le seuil de 15 jours ==> Nombre de jours à répartir (Case H) : <input type=text placeholder='Case H' name=valeur_h id=valeur_h value='$valeur_h' size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
    echo "<br>";
    if ($typeagent == 'titu')
    {
        echo "Nombre de jours à prendre en compte au titre du RAFP (Case I) : <input type=text placeholder='Case I' name=valeur_i id=valeur_i size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_i style='color: red;font-weight: bold; margin-left:20px;'></label>";   //      <input type=text placeholder='Case I' name=valeur_i id=valeur_i value=$valeur_i size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
        echo "<br>";
    }
    else
    {
        echo "<input type='hidden' name=valeur_i id=valeur_i value='0' >"; //<label id=label_i style='color: red;font-weight: bold; margin-left:20px;'></label>";
    }
    echo "Nombre de jours à indemniser (Case J) : <input type=text placeholder='Case J' name=valeur_j id=valeur_j size=3 onchange='update_case()' onkeyup='update_case()' ><label id=label_j style='color: red;font-weight: bold; margin-left:20px;'></label>";   //     <input type=text placeholder='Case J' name=valeur_j id=valeur_j size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' >";
    echo "<br>";
    echo "Nombre de jours à maintenir sur le CET sous forme de congés (Case K) : <input type=text placeholder='Case K' name=valeur_k id=valeur_k size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' onchange='update_case()' onkeyup='update_case()' ><label id=label_k style='color: red;font-weight: bold; margin-left:20px;'></label>";
    echo "<br>";
    echo "Solde du CET après option (Case L) : <input type=text placeholder='Case L' name=valeur_l id=valeur_l size=3 readonly style = 'border-top-style: hidden; border-right-style: hidden; border-left-style: hidden; border-bottom-style: hidden;' ><label id=label_l style='color: red;font-weight: bold; margin-left:20px;'></label>";
    echo "<br><br>Choix du responsable :<br>";
    echo "<input type='radio' id='resp_demo' name='responsable' value='resp_demo' checked><label for='resp_demo'>Responsable de démo (Pascal+Elodie)</label>";
    echo "&nbsp;&nbsp;&nbsp;";
    $structid = $agent->structureid();
    $struct = new structure($dbcon);
    $struct->load($structid);
    $resp = $struct->responsable();
    echo "<input type='radio' id='resp_vrai' name='responsable' value='resp_vrai'><label for='resp_vrai'>Vrai responsable de l'agent (" . $resp->identitecomplete() .  " - " .  $resp->mail() . ")</label>";
    echo "<br><br>";
    echo "<input type='checkbox' id='drh_niveau' name='drh_niveau' checked><label for='drh_niveau'>Ajouter un 3e niveau dans le circuit de validation (Destinataire : " . $agent->identitecomplete()  .")</label><br>";
    echo "<br><br>";
    echo "<input type='submit' name='cree_option' id='cree_option' value='Soumettre' disabled>";
    echo "</form>";
    
    
    echo "<br><br>";

    echo "<br><hr size=3 align=center><br>";
    echo "<br>Synchronisation du statut du droit d'option G2T avec le statut des droits d'option eSignature.<br>";
    
    
    $sql = "SELECT ESIGNATUREID,STATUT FROM OPTIONCET WHERE HARPEGEID = '" .  $agentid . "'";
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
        $errlog = "Aucune demande de droit d'option pour l'agent " . $agent->identitecomplete() . "<br>";;
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
            echo "<option value='" . $result["0"]  . "'>" . $result["0"] . " => " . $result["1"] . "</option>";
        }
        echo "</select>";
        echo "<br><br>";
        echo "<input type='submit' name='esignature_info' id='esignature_info' value='Synchronisation de la demande'>";
        echo "</form>";
    }
    
    
    
    
    
/*
    echo "Appel de CURL (Méthode GET) -- Recupération des infos d'une option CET....<br>";
    $curl = curl_init();
    $esignatureid = 249779;
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
*/    
 
/*    
        
    $option = new optionCET($dbcon);
    $option->agentid($userid);
    $option->anneeref($fonctions->anneeref());
    $option->esignatureid('eSignatureid');
    $option->esignatureurl('http://esignature_url/WS/to/call/');
    $option->motif('Ceci est un motif de démo');
    $option->statut($option::STATUT_PREPARE);
    $option->valeur_a('1');
    $option->valeur_g('2');
    $option->valeur_h('3');
    $option->valeur_i('4');
    $option->valeur_j('5');
    $option->valeur_k('6');
    $option->valeur_l('7');
    
    $erreur = $option->store();
    echo "Erreur (store) = $erreur <br><br>";
    
    $optionid = $option->optionid();
    echo "optionid = " . $optionid . "<br>";
    
    unset($option);
    $option = new optionCET($dbcon);
    $erreur  = $option->load(null,$optionid);
    echo "Erreur (load) = $erreur <br><br>";
    echo "<pre>";
    var_dump($option);
    echo "</pre>";
    
    $option->esignatureid('eSignatureid_modifie');
    $option->esignatureurl('http://esignature_url/WS/to/call/modifie');
    $option->motif('Ceci est un motif de démo modifié');
    $option->statut($option::STATUT_EN_COURS);
    
    $erreur = $option->store();
    echo "Erreur (store2) = $erreur <br><br>";
    

    unset($option);
    $option = new optionCET($dbcon);
    $erreur  = $option->load(null,$optionid);
    echo "Erreur (load2) = $erreur <br><br>";
    echo "<pre>";
    var_dump($option);
    echo "</pre>";
*/
?>