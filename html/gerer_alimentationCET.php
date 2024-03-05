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
        
    require ("includes/menu.php");
    
    $id_model = trim($fonctions->getidmodelalimcet());
    $eSignature_url = trim($fonctions->liredbconstante('ESIGNATUREURL'));
    //$sftpurl = $fonctions->liredbconstante('SFTPTARGETURL');
    $sftpurl = "";
    
    $full_g2t_ws_url = trim($fonctions->get_g2t_ws_url()) . "/alimentationWS.php";
    $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
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
 */
//    echo "<br>" . print_r($_POST,true);
//    echo "<br><br><br>";

    
    // Si on est en mode 'rh' et qu'on n'a pas encore choisi l'agent, on affiche la zone de sélection.
    if (is_null($agentid) and $mode == 'rh')
    {
        echo "<form name='demandeforagent'  method='post' action='gerer_alimentationCET.php'>";
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

        echo "<br>";
        
        echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
        echo "<input type='hidden' name='mode' value='" . $mode . "'>";
        echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
        echo "</form>";
    }

    if (!is_null($agentid))
    {
    	$agent = new agent($dbcon);
    	$agent->load($agentid);
    	$agent->synchroCET();
    }
    
//    if (isset($_POST['annuler_demande']))
    if (isset($_POST['esignatureid_annule']))
    {
        $errlog = '';
        $esignatureid_annule = $_POST['esignatureid_annule'];
    	$alimentationCET = new alimentationCET($dbcon);
    	$alimentationCET->load($esignatureid_annule);
    	// récupérer statut si validée réalimenter le reliquat, déduire du CET et alerter la DRH
    	$statut_actuel = $alimentationCET->statut();
    	if (!is_null($agentid))
    	{
            $agent = new agent($dbcon);
            $agent->load($agentid);

            $return = $fonctions->deleteesignaturedocument($esignatureid_annule);
            if (strlen($return)>0) // On a rencontré une erreur dans la suppression eSignature
            {
                if (strlen($errlog)>0) $errlog = $errlog . '<br>';
                $errlog = $errlog . "Impossible d'annuler la demande d'alimentation $esignatureid_annule : $return";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($return));
            }
            else
            {
                // purger esignature
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
                    if ($erreur == "") 
                    {
                        $cet->cumultotal($cet->cumultotal() - $alimentationCET->valeur_f());
                        $cumulannuel = $cet->cumulannuel($fonctions->anneeref());
                        $cumulannuel = $cumulannuel - $alimentationCET->valeur_f();
                        $cet->cumulannuel($fonctions->anneeref(),$cumulannuel);
                        $cet->store();
                    }

                    // alerter la DRH

                    $arrayagentrh = $fonctions->listeprofilrh(agent::PROFIL_RHCET); // Profil = 1 ==> GESTIONNAIRE RH DE CET
                    foreach ($arrayagentrh as $gestrh) 
                    {
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
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    	}
    }
    
    $anneeref = $fonctions->anneeref()-1;
    $sauvegardeok = false;
    

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
                $agent_mail = $agent->mail(); // $agent->ldapmail();
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
	            	            	
                $taberrorcheckmail = $fonctions->checksignatairecetliste($params,$agent);

            	if (count($taberrorcheckmail) > 0)
            	{
            	    // var_dump("errorcheckmail = $errorcheckmail");
            	    $errorcheckmailstr = '';
            	    foreach ($taberrorcheckmail as $errorcheckmail)
            	    {
            	        if (strlen($errorcheckmailstr)>0) $errorcheckmailstr = $errorcheckmailstr . '<br>';
            	        $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
            	    }
            	    echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible d'enregistrer la demande d'alimentation CET car <br>$errorcheckmailstr");
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
    	                //echo "Erreur Curl = " . $error . "<br><br>";
                        echo $fonctions->showmessage(fonctions::MSGERROR, "Erreur Curl = " . $error);
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
    	            	// echo "$erreur <br><br>";
                        echo $fonctions->showmessage(fonctions::MSGERROR, "$erreur");

    	            }
    	            else
    	            {
    	                if ("$id" < 0)
    	                {
    	                    $erreur =  "La création de la demande d'alimentation dans eSignature a échoué (numéro demande eSignature négatif = $id) !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.";
    	                    error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
    	                    //echo "$erreur <br><br>";
                            echo $fonctions->showmessage(fonctions::MSGERROR, "$erreur");
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
                                $sauvegardeok = true;
                            }
                        }
                        else
                        {
                            $erreur  = "La création de la demande d'alimentation dans eSignature a échoué !!==> Pas de sauvegarde de la demande d'alimentation dans G2T.";
                            error_log(basename(__FILE__) . $fonctions->stripAccents("$erreur"));
                            //echo "$erreur <br><br>";
                            echo $fonctions->showmessage(fonctions::MSGERROR, "$erreur");
                        }
    	            }
            	}
            }
        }
    }
    
    if (! is_null($agentid))
    {
        // Affichage des demandes d'alimentation dans la base G2T
        $alimentationCET = new alimentationCET($dbcon);


        // On récupère les soldes de l'agent
        $agent = new agent($dbcon);
        $agent->load($agentid);
        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
        if ($affectationliste != NULL)
        {
            $affectation = current($affectationliste);
            
            //$structure = new structure($dbcon);
            //$structure->load($affectation->structureid());
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
                    //alert('plouf');
                    document.getElementById("label_f").innerHTML = "Le nombre de jours doit être inférieur ou égal au dépôt maximum.";
                    button.disabled = true;
	    	}
	    	else
	    	{
                    document.getElementById("label_f").innerHTML = "";
                    valeur_a = document.getElementById("valeur_a").value;
                    valeur_d = document.getElementById("valeur_d").value;
                    plafond = document.getElementById("plafond").value;
                    document.getElementById("valeur_e").value = parseFloat(valeur_d,10)-parseInt(valeur_f,10);
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
	    if ($sauvegardeok)
	    {
	        echo $fonctions->showmessage(fonctions::MSGINFO, "Votre demande d'alimentation a été correctement enregistrée.");
	    }
            elseif (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
            {
                echo $fonctions->showmessage(fonctions::MSGWARNING, "Vous avez une demande d'alimentation en cours. Vous pourrez en effectuer une nouvelle lorsque celle-ci sera terminée ou annulée.");
                /*
//                echo "Souhaitez-vous annuler la demande ? <br>";
//                echo "<form name='annuler_alimentation'  method='post' >";
//                echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
//                echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
//                echo "<input type='submit' name='annule_demande' id='annule_demande' value='Annuler' onclick=\"return confirm('Annuler la demande d\'alimentation du CET ?')\">";
//                echo "</form>";
                */
			
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
                else 
                {
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

                if (is_null($cree_demande))
                {
                    $taberrorcheckmail = $fonctions->checksignatairecetliste($params,$agent);
                    if (count($taberrorcheckmail) > 0)
                    {
                        // var_dump("errorcheckmail = $errorcheckmail");
                        $errorcheckmailstr = '';
                        foreach ($taberrorcheckmail as $errorcheckmail)
                        {
                            if (strlen($errorcheckmailstr)>0) $errorcheckmailstr = $errorcheckmailstr . '<br>';
                            $errorcheckmailstr = $errorcheckmailstr . $errorcheckmail;
                        }
                        echo $fonctions->showmessage(fonctions::MSGERROR, "Il n'est pas possible de faire une demande d'alimentation CET car <br>$errorcheckmailstr");
                    }
                }

                //echo 'Structure complète d\'affectation : '.$structure->nomcompletcet().'<br>';
                echo "<form name='creation_alimentation'  method='post' >";
                echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
                echo "<input type='hidden' name='agentid' value='" . $agentid . "'>";
                echo "Solde actuel de votre CET : $valeur_a jour(s)";
                echo "<br>";
                echo "Dépôt maximum : $nbjoursmax jour(s) <label id=label_plafond class='erroralimCETlabel'></label>";
                echo "<br>";
                echo "Combien de jours souhaitez-vous ajouter à votre CET ? ";
                echo "<input type=text placeholder='Case F' name=valeur_f id=valeur_f size=4 onchange='update_case()' onkeyup='update_case()' onfocusout='update_case()' ><label id=label_f  class='erroralimCETlabel'></label>";
                echo "<br>";
                echo "Solde de votre CET après versement <input type=text placeholder='Case G' name=valeur_g id=valeur_g size=4 readonly class='inputdataalimCET' > jour(s).";
                echo "<input type='hidden' name='plafond' readonly id='plafond' value='" . $nbjoursmax . "' class='inputdataalimCET' >";
                echo "<input type='hidden' placeholder='Case A' name=valeur_a id=valeur_a value=$valeur_a size=4 readonly class='inputdataalimCET' >";
                echo "<input type='hidden' placeholder='Case B' name=valeur_b id=valeur_b value=$valeur_b size=4 readonly class='inputdataalimCET' >";
                echo "<input type='hidden' placeholder='Case C' name=valeur_c id=valeur_c value=$valeur_c size=4 readonly class='inputdataalimCET' >";
                echo "<input type='hidden' placeholder='Case D' name=valeur_d id=valeur_d value=$valeur_d size=4 readonly class='inputdataalimCET' >";
                echo "<input type='hidden' placeholder='Case E' name=valeur_e id=valeur_e size=4 readonly class='inputdataalimCET' >";
/*                
                //$code = null;
                //if ($structure->responsable()->agentid() == $agent->agentid())
                //{
                //    $resp = $structure->resp_envoyer_a($code);
                //}
                //else
                //{
                //    $resp = $structure->agent_envoyer_a($code);
                //}
 */
                echo "<br><br>";
                if ($mode == 'rh')
                {
                    echo "<p id='check_plafond'><input type='checkbox' id='no_verify' name='no_verify' value='on'>Ne pas contrôler le plafond d'alimentation CET.</p><br><br>";
?>
                    <script type="text/javascript">const no_verify = document.getElementById('no_verify'); no_verify.addEventListener('input', update_case);</script>
<?php 
                }
                echo "<input type='hidden' name='mode' value='" . $mode . "'>";
                echo "<input type='submit' name='cree_demande' id='cree_demande' class='g2tbouton g2tvalidebouton' value='Enregistrer' disabled>";
                echo "</form>";
                echo "<br>";
            }
	}
	// Si une demande d'option est en cours ou validée, pas de suppression possible de demande d'alimentation
	if (!$hasOption)
	{
            // contrôle de la date de fin d'utilisation des reliquats
            $dbconstante = 'FIN_REPORT';
            if (!$fonctions->testexistdbconstante($dbconstante))
            {
                $erreur = "La date limite du report n'est pas définie.";
                $errlog = "Problème SQL dans le chargement de la date limite d'utilisation du reliquat : " . $erreur;
                echo $errlog;   
            }
            elseif ($res = $fonctions->liredbconstante($dbconstante))
            {
                $limitereliq = ($fonctions->anneeref()+1).$res;
                //echo "<br>limitereliq = $limitereliq <br>";
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
                            echo "Suppression d'une demande d'alimentation.<br>";
                            echo "<form name='form_esignature_annule' id='form_esignature_annule' method='post' >";
                            echo "<input type='hidden' name='userid' value='" . $userid . "'>";
                            echo "<input type='hidden' name='agentid' value='" . $agent->agentid() . "'>";
                            echo "<select name='esignatureid_annule' id='esignatureid_annule'>";
                            foreach ($listid as $id)
                            {
                                $alimcet->load($id);
    //                            echo "alimcet->typeconges() => " . $alimcet->typeconges() . "  AnneeRef = " . substr($fonctions->anneeref(),2,2) . "<br>";
    //                            if ($alimcet->typeconges() == "ann" . substr(($fonctions->anneeref()-1),2,2))
    //                            {
                                echo "<option value='" . $id  . "'>" . $id ." => ".$alimcet->statut()."</option>";
    //                            }
                            }

                            echo "</select>";
                            echo "<br><br>";
                            echo "<input type='hidden' name='mode' value='" . $mode . "'>";
//                            echo "<input type='submit' class='cancel' name='annuler_demande' id='annuler_demande' value='Annuler la demande' onclick=\"return confirm('Annuler la demande ?')\">";
                            echo "<input type='submit' name='annuler_demande' id='annuler_demande' class='cancel g2tbouton g2tsupprbouton' value='Supprimer' onclick=\"click_element('annuler_demande'); return false; \">";
//                            echo "<button class='cancel' name='annuler_demande' id='annuler_demande' onclick='alert(\"plouf\"); if (this.tagname!=\"OK\") {alert(\"truc\"); click_element(\"annuler_demande\"); alert(\"toto\"); return false; } alert(\"zozo\");'>Annuler la demande</button>";
                            if (isset($error_suppr))
                            {
                                echo $fonctions->showmessage(fonctions::MSGERROR, $error_suppr);
                            }
                            echo "<br><br>";
                            echo "</form>";
                        }
                    }
                    else
                    {
                        echo $fonctions->showmessage(fonctions::MSGWARNING, "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats est dépassé. (".$fonctions->formatdate($limitereliq).")<br>");
                    }
                }
                else 
                {
                    echo $fonctions->showmessage(fonctions::MSGWARNING, "Annulation de demande d'alimentation impossible car la date limite d'utilisation des reliquats est invalide. <br>");
                }
            }
            else
            {
                echo $fonctions->showmessage(fonctions::MSGWARNING, "Annulation de demande d'alimentation impossible car le délai d'utilisation des reliquats n'est pas défini.<br>");
            }
        }
        else 
        {
            if (sizeof($agent->getDemandesAlim('', array($alimentationCET::STATUT_EN_COURS, $alimentationCET::STATUT_PREPARE))) != 0)
            {
                echo $fonctions->showmessage(fonctions::MSGWARNING, "Annulation de demande d'alimentation impossible car une demande de droit d'option est en cours ou validée.");
            }
        }

        echo $agent->afficheAlimCetHtml();
        echo $agent->soldecongeshtml($anneeref + 1);
    }

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
                    var submit_form = document.getElementById('form_esignature_annule');
                    submit_form.submit();
                }
            });

            var click_element = function(elementid)
            {
                if (typeof confirmdialog.showModal === "function") {
                    var submit_button = document.getElementById(elementid);
                    if (submit_button.classList.contains("cancel"))
                    {
                        labeltext.innerHTML = 'Confirmez vous la suppresion de cette demande ? ';
                    }
                    else
                    {
                        labeltext.innerHTML = 'Confirmez vous cette action ? ';
                    }
                    cancelBtn.textContent = "Non";
                    cancelBtn.hidden = false;
                    confirmBtn.textContent = "Oui";
                    confirmBtn.hidden = false;
                    confirmdialog.showModal();
                }        
                else {
                    console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
                }
            };
        </script>
<?php
    
?>

</body>
</html>

