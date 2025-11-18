<?php
    include '../html/includes/casconnection.php';
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/includes/all_g2t_classes.php');

    $fonctions = new fonctions($dbcon);

    function structure_planning()
    {
        global $dbcon;
        global $fonctions;
        global $user;

        // error_log(basename(__FILE__) . $fonctions->stripAccents(" Debut du WS structure_planning"));

        $structureid = null;
        $mois_annee_debut = null;
        // Valeurs optionnelles => Initialisation des valeurs par défaut
        $showsousstruct = null;
        $noiretblanc = false;
        $includeteletravail = false;
        $dbclickable = false;
        $includecongeabsence = true;
        $agentlist = null;

        if (isset($_POST) and count($_POST)>0)
        {
            if (array_key_exists("structureid", $_POST)) // Identifiant de la structure
            {
                $structureid = $_POST["structureid"];
            }
            if (array_key_exists("mois_annee_debut", $_POST)) // Mois + Année (format MM/AAAA)
            {
                $mois_annee_debut = $_POST["mois_annee_debut"];
            }

            // On récupère les valeurs optionnelles 
            if (array_key_exists("showsousstruct", $_POST)) // Affichage ou pas des sous-structures
            {
                $showsousstruct = $fonctions->convertvaluetobool($_POST["showsousstruct"]);
            }
            if (array_key_exists("noiretblanc", $_POST)) // Planning affichage en N&B
            {
                $noiretblanc = $fonctions->convertvaluetobool($_POST["noiretblanc"]);
            }
            if (array_key_exists("includeteletravail", $_POST)) // Affichage du télétravail ou pas
            {
                $includeteletravail = $fonctions->convertvaluetobool($_POST["includeteletravail"]);
            }
            if (array_key_exists("dbclickable", $_POST)) // Planning double-clickable
            {
                $dbclickable = $fonctions->convertvaluetobool($_POST["dbclickable"]);
            }
            if (array_key_exists("includecongeabsence", $_POST)) // Inclure ou pas les congés/absences
            {
                $includecongeabsence = $fonctions->convertvaluetobool($_POST["includecongeabsence"]);
            }
            if (array_key_exists("agentlist", $_POST)) // Liste des agents à afficher
            {
                // Agentlist est un json représentant un tableau d'id d'agent à afficher => Initialisé avec json_encode(array('9328','24606','644'));
                $agentlist = json_decode($_POST["agentlist"]);
                // error_log(basename(__FILE__) . $fonctions->stripAccents(" AgentList => " . implode(',',$agentlist)));
            }
        }
        else
        {
            if (array_key_exists("structureid", $_GET)) // Identifiant de la structure
            {
                $structureid = $_GET["structureid"];
            }
            if (array_key_exists("mois_annee_debut", $_GET)) // Mois + Année (format MM/AAAA)
            {
                $mois_annee_debut = $_GET["mois_annee_debut"];
            }

            // On récupère les valeurs optionnelles 
            if (array_key_exists("showsousstruct", $_GET)) // Affichage ou pas des sous-structures
            {
                $showsousstruct = $fonctions->convertvaluetobool($_GET["showsousstruct"]);
            }
            if (array_key_exists("noiretblanc", $_GET)) // Planning affichage en N&B
            {
                $noiretblanc = $fonctions->convertvaluetobool($_GET["noiretblanc"]);
            }
            if (array_key_exists("includeteletravail", $_GET)) // Affichage du télétravail ou pas
            {
                $includeteletravail = $fonctions->convertvaluetobool($_GET["includeteletravail"]);
            }
            if (array_key_exists("dbclickable", $_GET)) // Planning double-clickable
            {
                $dbclickable = $fonctions->convertvaluetobool($_GET["dbclickable"]);
            }
            if (array_key_exists("includecongeabsence", $_GET)) // Inclure ou pas les congés/absences
            {
                $includecongeabsence = $fonctions->convertvaluetobool($_GET["includecongeabsence"]);
            }
            if (array_key_exists("agentlist", $_GET)) // Liste des agents à afficher
            {
                // Agentlist est un json représentant un tableau d'id d'agent à afficher => Initialisé avec json_encode(array('9328','24606','644'));
                $agentlist = json_decode($_GET["agentlist"]);
                // error_log(basename(__FILE__) . $fonctions->stripAccents(" AgentList => " . implode(',',$agentlist)));
            }
        }

        if (is_null($structureid) or is_null($mois_annee_debut))
        {
            $erreur = "Impossible de créer le planning de la structure (structureid = $structureid mois_annee_debut = $mois_annee_debut)";
            $result_json = array('status' => 'Error', 'description' => $erreur, 'html' => $erreur);
            error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
        }
        else
        {
            $structure = new structure($dbcon);
            if (!$structure->load($structureid))
            {
                $erreur = "Impossible de récupérer la structure $structureid";
                $result_json = array('status' => 'Error', 'description' => $erreur, 'html' => $erreur);
                error_log(basename(__FILE__) . $fonctions->stripAccents(" Appel du WS en mode POST => Erreur = " . $erreur));
            }
            else
            {
                $html_texte = $structure->planninghtml($mois_annee_debut, $showsousstruct, $noiretblanc, $includeteletravail, $dbclickable, $includecongeabsence, $agentlist);
                $result_json = array('status' => 'Ok', 'description' => '', 'html' => $html_texte);
            }
        }
        return $result_json;
    }

    function checkuserallowed($uid, &$user)
    {
        global $dbcon;
        global $fonctions;

        $authproblem = false;
        if (!isset($uid))
        {
            $authproblem = true;
            error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID n'est pas défini !"));
        }
        else
        {
            $userid = $fonctions->CASuserisG2TAdmin($uid);
            if ($userid!==false)
            {
                // C'est un administrateur => C'est Ok
                error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID est un administrateur => C'est ok !"));
                $user = new agent($dbcon);
                if (!$user->load($userid))
                {
                    // On a eu un problème lors du chargement de l'utilisateur
                    $authproblem = true;
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" Impossible de charger l'utilisateur $userid !"));
                }
            }
            else
            {
                // Ce n'est pas un administrateur donc on regarde si c'est un utilisateur G2T !
                $userid = $fonctions->useridfromCAS($uid);
                if ($userid === false)
                {
                    // Ce n'est pas un utilisateur G2T donc erreur !
                    $authproblem = true;
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" L'UID n'est pas un utilisateur G2T !"));
                }
                else
                {   // On va charger l'utilisateur
                    $user = new agent($dbcon);
                    if (!$user->load($userid))
                    {
                        // On a eu un problème lors du chargement de l'utilisateur
                        $authproblem = true;
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" Impossible de charger l'utilisateur $userid !"));
                    }
                    else
                    {
                        error_log(basename(__FILE__) . $fonctions->stripAccents(" L'utilisateur $userid a été chargé => C'est ok !"));
                    }
                }
            }
    
        }
        return !$authproblem;
    }


    $result_json = array();
    $errlog = '';
    $erreur = '';
    $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');

    if (count($_POST) == 0)
    {
        // Autre façon de récupérer les variables $_POST
        $_POST = json_decode(file_get_contents('php://input'), true);
    }

    error_log(basename(__FILE__) . " POST = " . str_replace("\n","",var_export($_POST,true)));
    error_log(basename(__FILE__) . " GET = " . str_replace("\n","",var_export($_GET,true)));

    $user = new agent($dbcon);
    if (!checkuserallowed($uid, $user))
    {
        $erreur = "Vous n'êtes pas autorisé à utiliser ce service.";
        $result_json = array('status' => 'Error', 'description' => $erreur);
        error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Contrôle autorisation du WS => Erreur = " . $erreur)));
    }
    else
    {
        switch ($_SERVER['REQUEST_METHOD'])
        {
            case 'POST': 
                $methode = null;
                if (array_key_exists("methode", $_POST)) 
                {
                    $methode = $_POST["methode"];
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La méthode du WS structureWS est : $methode"));
                    switch ($methode)
                    {
                        case structure::WS_METHODE_PLANNING : 
                            error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel de la méthode " . structure::WS_METHODE_PLANNING . " du WS en mode POST")));
                            $result_json = structure_planning();
                            error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour de la création du planning => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                            // $erreur = "La méthode du WS " . structure::WS_METHODE_PLANNING  . " n'est pas disponible en POST.";
                            // $result_json = array('status' => 'Error', 'description' => $erreur);
                            // error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel du WS en mode POST => Erreur = " . $erreur)));
                            break;
                        default:
                            $erreur = "La méthode du WS n'est pas définie ou mal définie.";
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                            error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel du WS en mode POST => Erreur = " . $erreur)));
                            break;
                    }
                }
                break;
            case 'GET':
                $methode = null;
                if (array_key_exists("methode", $_GET)) 
                {
                    $methode = $_GET["methode"];
                    error_log(basename(__FILE__) . $fonctions->stripAccents(" La méthode du WS structureWS est : $methode"));
                    switch ($methode)
                    {
                        case structure::WS_METHODE_PLANNING : 
                            $result_json = structure_planning();
                            error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Retour de la création du planning => Statut = " . $result_json["status"] . " Description = " . $result_json["description"])));
                            break;
                        default:
                            $erreur = "La méthode du WS n'est pas définie ou mal définie.";
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                            error_log(basename(__FILE__) . $fonctions->stripAccents(preg_replace('~[[:cntrl:]]~', ''," Appel du WS en mode GET => Erreur = " . $erreur)));
                            break;
                    }
                }
                break;
        }
    }
    
    
    if (!isset($result_json['status']) or !isset($result_json['description']))
    {
        $erreur = "La structure du json n'est pas correcte => On retourne une erreur";
        $result_json['status'] = 'Error';
        $result_json['description'] = $erreur;
        error_log(basename(__FILE__) . $fonctions->stripAccents(" Erreur dans le WS => Erreur = " . $erreur));
    }
    if (!isset($result_json['html']))
    {
        $result_json['html'] = $result_json['description'];
    }
   
    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    echo json_encode($result_json);
    
?>
