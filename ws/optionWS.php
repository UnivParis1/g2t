<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
    
    
    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));
    
    //$statutvalide = array('PREPA' => alimentationCET::STATUT_PREPARE, 'COURS' => alimentationCET::STATUT_EN_COURS, 'REFUS' => alimentationCET::STATUT_REFUSE, 'SIGNE' => alimentationCET::STATUT_VALIDE, 'ABAND' => alimentationCET::STATUT_ABANDONNE);
    
    if (count($_POST)==0 and count($_GET)==0)
    {
        $erreur = "Appel du WS sans paramètre => Rien à faire";
        error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
        $result_json = array('status' => 'Ok', 'description' => '');
    }
    else
    {
		switch ($_SERVER['REQUEST_METHOD'])
		{
			case 'POST': // Modifie le statut d'une demande d'alimentation
				$erreur = "Le mode POST n'est pas supporté dans ce WS";
				$result_json = array('status' => 'Error', 'description' => $erreur);
				error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
				break;
			case 'GET':
				if (array_key_exists("esignatureid", $_GET)) // Retourne les informations liées à un droit d'option CET
				{
					$esignatureid = $_GET["esignatureid"];
					error_log(basename(__FILE__) . $fonctions->stripAccents(" On va retourner les infos de le droit d'option " . $esignatureid));
					if ("$esignatureid" == "" )
					{
						$erreur = "Le paramètre esignatureid n'est pas renseigné.";
					}
					else
					{
						$optionCET = new optionCET($dbcon);
						$erreur = $optionCET->load($esignatureid);
					}
					if ($erreur != "")
					{
						error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
						$result_json = array('status' => 'Error', 'description' => $erreur);
					}
					else
					{
						// On crée la réponse JSon correspondant à l'option CET
						$result_json = $fonctions->optionCETjsonresponse($optionCET);
					}
				}
				elseif (array_key_exists("signRequestId", $_GET))  // Synchronisation d'une demande G2T avec le statut de eSignature
				{
					$esignatureid = $_GET["signRequestId"];
					if ("$esignatureid" == "")
					{
						$erreur = "Le paramètre esignature n'est pas renseigné.";
						$result_json = array('status' => 'Error', 'description' => $erreur);
						error_log(basename(__FILE__) . $fonctions->stripAccents(" ERROR => " . $erreur));
					}
					else
					{
                        $result_json = $fonctions->synchroniseoptionCET($esignatureid);
					}
				}
				else
				{
					$erreur = "Mauvais usage du WS mode GET => Les paramètres doivent être : signRequestId ou esignatureid";
					error_log(basename(__FILE__) . $fonctions->stripAccents(" $erreur"));
					$result_json = array('status' => 'Error', 'description' => $erreur);
				}
				break;
		}
	}
    
    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    echo json_encode($result_json);
    


?>