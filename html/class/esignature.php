<?php

use Fpdf\Fpdf as FPDF;

class esignaturelog
{
    private $dbconnect = null;
    private $fonctions = null;

    /**
     *
     * @param mysqli $db
     *            La connexion MySQL/MariaDB à la base de données
     */
    function __construct(mysqli $db)
    {
        $this->fonctions = new fonctions($db);
        $this->dbconnect = $db;
    }

    /**
     *
     * @param array $esignaturequery
     *            Le tableau des données soumisent à eSignature
     */
    function store(array $esignaturequery)
    {
        $sql = "INSERT INTO ESIGNATURELOG(ESIGNATUREID,DOCUMENTTYPE,CREATIONDATE,DOCUMENTDATA) VALUES (?, ?, SYSDATE(), ?)";

        $doctype = "Type inconnu";
        if (isset($esignaturequery['title']))
        {
            $doctype = $esignaturequery['title'];
        }
        $esignatureid = "";
        if (isset($esignaturequery['esignatureid']))
        {
            $esignatureid = $esignaturequery['esignatureid'];
        }

        $params = array($esignatureid, $doctype, print_r($esignaturequery,true));
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);

        if ($erreur != "") 
        {
            $errlog = __CLASS__ . "::" . __FUNCTION__ . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param array $query
     *            Le tableau des données soumisent à eSignature
     */
    function delete(string $esignatureid)
    {
        $sql = "DELETE FROM ESIGNATURELOG WHERE ESIGNATUREID = ?";

        $params = array($esignatureid . "");
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);

        if ($erreur != "") 
        {
            $errlog = __CLASS__ . "::" . __FUNCTION__ . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }
}

class stepinfos
{
    public const PDFSIGNATURE = 'pdfImageStamp';
    public const HIDDENVISA  = 'hiddenVisa';
    public const VISA = 'visa';

    public $stepNumber = '';
    public $signType = 'pdfImageStamp';   //// Type de signature de eSignature = hiddenVisa, visa, pdfImageStamp, certSign, nexuSign
    public $forceAllSign = false;
    public $allSignToComplete = false;
    public $comment = "";
    public $description = "";
    public $obligatoire = true;
    public $attachmentRequire = false;
    public $signataires = array();
    private $attachmentAlert = true;

    public function converttoarray() : array
    {
        $extrainfos = array();

        $extrainfos['signType'] = $this->signType;
        $extrainfos['forceAllSign'] = $this->forceAllSign;
        $extrainfos['allSignToComplete'] = $this->allSignToComplete;
        if (trim($this->stepNumber) != '')
        {
            $extrainfos['stepNumber'] = $this->stepNumber;
        }
        $extrainfos['comment'] = $this->comment;
        $extrainfos['description'] = $this->description;
        $extrainfos['obligatoire'] = $this->obligatoire;
        $extrainfos['attachmentRequire'] = $this->attachmentRequire;
        // Si la pièce jointe est obligatoire alors on doit forcer l'affichage d'une alerte si le doc est absent
        $extrainfos['attachmentAlert'] = $this->attachmentAlert;

        return $extrainfos;
    }
}

class esignaturerecipient
{
    public $nom = '';
    public $prenom = '';
    public $eppn = '';
    public $mail = '';
    public $hassigned = false;
    public $action = '';
    public $actiondate = '';
}

/**
 * eSignature
 * Definition des méthodes accessibles sur eSignature
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class esignature
{
    private $fonctions = null;
    private $eSignature_url = null;
    private $dbconnect = null;
    private $signrequestinfo = null;

    public const TYPESIGNATAIRE_DEMANDEUR = 'DEMANDEUR';
    public const TYPESIGNATAIRE_RESPONSABLE = 'RESPONSABLE';
    public const TYPESIGNATAIRE_RESPONSABLE2  = 'RESPONSABLE_N2';
    public const TYPESIGNATAIRE_DIRECTEUR = 'DIRECTEUR_RACINE';
    public const TYPESIGNATAIRE_AGENT = 'AGENT';
    public const TYPESIGNATAIRE_RESP_STRUCT = 'RESPONSABLE_STRUCT';
    
    /**
     *
     * @param mysqli $db
     *            La connexion MySQL/MariaDB à la base de données
     */
    function __construct(mysqli $db)
    {
        $this->fonctions = new fonctions($db);
        $this->dbconnect = $db;

        if (is_null($this->eSignature_url))
        {
            $dbconstante = 'ESIGNATUREURL';
            if ($this->fonctions->testexistdbconstante($dbconstante))
            {
                $this->eSignature_url = trim($this->fonctions->liredbconstante($dbconstante));
            }
            if ($this->eSignature_url . "" == "")
            {
                $error = "eSignature non configuré => Pas d'accès à eSignature";
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" " . $error));    
            }
        }

    }

    /**
     *
     * @param CurlHandle &$curl 
     *          Connexion Curl
     * @param string $content_type 
     *          Définition du type de contenu dans la requête Curl
     */
    private function set_curl_header(CurlHandle &$curl, string $content_type = "")
    {
        static $token = null;

        $header = array();
        if (is_null($token))
        {
            $token = "";
            $dbconstante = 'ESIGNATURETOKEN';
            if ($this->fonctions->testexistdbconstante($dbconstante))
            {
                $token = $this->fonctions->liredbconstante($dbconstante);
            }
        }
        if ($token . "" != "")
        {
            $header[] = "X-API-Key: $token";
        }
        if ($content_type != "")
        {
            $header[] = "Content-Type: $content_type"; //   multipart/form-data";
        }
        if (count($header) > 0)
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
    }

    /**
     *
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return bool
     *          True si la syntaxe de l'identifiant est correcte, false dans le cas contraire
     */
    private function check_esignatureid(string $esignatureid) : bool
    {
        // L'identifiant ne doit être composé que de chiffres
        if (!preg_match ("/^[0-9]+/", $esignatureid))
        {
            $erreur = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $erreur"));
            return false;
        }
        return true;
    }

    /**
     *
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return array|string
     *          Le tableau des données d'un formulaire eSignature ou la chaine explicative de l'erreur en cas de problème 
     */
    public function get_signrequest_data(string $esignatureid) :array|string
    {

        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/forms/get-datas/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl);
        $json = curl_exec($curl);
        //////////////////////////////////////////////////////
        // PATCH JSON GET-DATAS DE ESIGNATURE
        if (($count=substr_count(strtolower($json),'"recipient":'))==substr_count(strtolower($json),',"action"') and $count>1)
        {
            $json=str_ireplace('"recipient":', '',$json);
            $json=str_ireplace(',"action"', '',$json);
        }
        /////////////////////////////////////////////////////
        $response = (array)json_decode($json, true);

        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" " . $error));
            return $error;
        }

        return $response;

    }

    /**
     *
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return array|string
     *          Le tableau des données issu d'une demande de signature (SignRequest) ou la chaine explicative de l'erreur en cas de problème 
     */
    public function get_signrequest(string $esignatureid) :array|string
    {
        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/signrequests/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl);
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" " . $error));
            return $error;
        }
        else
        {
            $response = (array)json_decode($json, true);
            $this->signrequestinfo = $response;
        }

        if (count($response)==0)
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Aucune information disponible dans eSignature pour le document $esignatureid";
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" " . $error));
            return $error;
        }

        return $response;

    }

    /**
     *
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @param string &$pdf
     *          Le code PDF du fichier si tout s'est bien passé. chaine vide sinon.
     * @return string
     *          Le détail de l'erreur en cas de problème ou chaine vide si tout s'est bien passé
     */
    public function get_signrequest_document(string $esignatureid, string &$pdf) : string
    // public function get_document(string $esignatureid, string &$pdf) : string
    {
        $pdf = '';
        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/signrequests/get-last-file/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl);
        $pdf = curl_exec($curl);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        // Si le PDF ne contient pas la chaine %PDF- => Ce n'est pas du PDF
        if (stristr(substr($pdf,0,200),'%PDF-') === false)
        {
            // On decode le JSON récupéré
            $pdf = (array)json_decode($pdf);
            if (is_array($pdf) and isset($pdf['error']))
            {
                $error = $error . " : " . $pdf['error'];
            }
            if ($error != '')
            {
                $error = __CLASS__ . "::" . __FUNCTION__ . " : Le WS n'a pas retourné un fichier PDF => $error";
            }
            else
            {
                $error = __CLASS__ . "::" . __FUNCTION__ . " : Le WS n'a pas retourné un fichier PDF";
               
            }
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            $pdf = '';
            return $error;
        }
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            $pdf = '';
            return $error;
        }

        // Tout s'est bien passé
        return "";
    }


    /**
     * Récupération du statut d'une instance d'un formulaire
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return string|false
     *          Le statut du formulaire ou false en cas de problème 
     */
    public function get_signrequest_status(string $esignatureid) : string|false
    // public function get_status(string $esignatureid) : string|false
    {
        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/signrequests/status/' . $esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl);
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return false;
        }
        else
        {
            $current_status = str_replace("'", "", $json);
            return $current_status;
        }
    }


    /**
     * Création d'une nouvelle instance d'un formulaire
     * @param string $id_model 
     *          Identifiant du modèle de formulaire eSignature
     * @param array $params
     *          Le tableau des paramètres du formulaire 
     * @return int|string
     *          L'identifiant de la nouvelle instance du formulaire ou la description de l'erreur en cas de problème 
     */
    public function create_existing_signrequest(string $id_model, array $params) : int|string
    {

        $params_string = implode( '&', $params );
        // Réencode les URL et autres caractères dans un format lisible 
        // Exemple : targetEmails=aaaa.bbbbb%40etab.fr&targetUrl=http%3A%2F%2Fserver_name%2Fws%2Fpage.php
        //       ==> targetEmails=aaaa.bbbb@etab.fr&targetUrl=http://server_name/ws/page.php
        //$params_string = urldecode($params_string);
        //var_dump($params_string);

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/forms/' . trim($id_model)  . '/new',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); 
        $this->set_curl_header($curl);
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }
        $id = json_decode($json, true);
        if (is_array($id))
        {
            $error = $id['error'];  
        }
        elseif ($id == "" or intval($id) <= 0)
        {
            $error = "La création de l'instance du formulaire a échoué.";
            if (intval($id) <= 0) 
            {
                $error = $error . " Code erreur = $id";
            }
        }
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }
        else
        {
            return intval($id);
        }

    }

    /**
     * Création d'un nouveau formulaire personnalisé
     * @param array|string $params
     *          Le tableau des paramètres du formulaire
     * @return int|string
     *          L'identifiant (int) de la nouvelle instance du formulaire ou la description de l'erreur en cas de problème 
     */
    public function create_custom_signrequest(array $params) : int|string
    {
        //var_dump($params);
        // On sauvegarde dans la base de données les données passées à eSignature
        $esignaturelog = new esignaturelog($this->dbconnect);

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->eSignature_url . '/ws/signrequests/new',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl, "multipart/form-data");
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            //$esignaturelog->store($params);
            return $error;
        }
        $id = json_decode($json, true);
        if (is_array($id))
        {
            $error = $id['error'];  
        }
        elseif ($id == "" or intval($id) <= 0)
        {
            $error = "La création de l'instance du formulaire a échoué.";
            if (intval($id) <= 0) 
            {
                $error = $error . " Code erreur = $id";
            }
        }
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            //var_dump($error);
            //$esignaturelog->store($params);
            return $error;
        }
        else
        {
            $esignatureid = intval($id);
            $params['esignatureid'] = $esignatureid;
            $esignaturelog->store($params);
            return $esignatureid;
        }

    }

    /**
     * Modification des signataires pour une étape d'un document eSignature
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @param array $params_string
     *          Le tableau des paramètres des signataires
     * @param int $stepnumber 
     *          Optionnel - Numéro de l'étape à modifier. Si absent doit être défini dans le tableau des paramètres
     * @return string
     *          La description de l'erreur en cas de problème ou chaine vide si tout s'est bien passé
     */
    /* Usage example :
     $params_string = array();
     $params_string['recipientWsDtosString'] = '[{"email" : "john.doe@etab.fr"}]';
     $params_string['stepNumber'] = 2;
     $error = $esignature->modify_recipient($esignatureid, $params_string);

     $params_string = array();
     $params_string['recipientWsDtosString'] = '[{"email" : "john.doe@etab.fr"},{"email" : "jane.doe@etab.fr"}]';
     $error = $esignature->modify_recipient($esignatureid, $params_string, 3);
    */
    public function modify_recipient(string $esignatureid, array $params_string, int $stepnumber = 0) : string
    {
        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $url = $this->eSignature_url . '/ws/signrequests/update-recipients/' . $esignatureid;
        if ($stepnumber > 0)
        {
            $url = $url . "?stepNumber=$stepnumber";
        }
        elseif (!isset($params_string['stepNumber']))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur de syntaxe => Vous devez préciser le stepnumber ou le définir dans le tableau de paramètre.";
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params_string,
            CURLOPT_PROXY => ''
        ];

        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $this->set_curl_header($curl, "multipart/form-data");
        $json = curl_exec($curl);
        //var_dump($json);
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        curl_close($curl);
        if ($error != "")
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }
        $response = json_decode($json, true);
        return ($response . "");
    }

    /**
     * Modification des signataires pour une étape d'un document eSignature
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return string
     *          La description de l'erreur en cas de problème ou chaine vide si tout s'est bien passé
     */
    public function delete_signrequest(string $esignatureid) : string
    {
        $error = '';

        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        //$url = $eSignature_url.'/ws/signrequests/'.$esignatureid;         ==> Suppression complète sans passer par la corbeille
        //$url = $eSignature_url.'/ws/signrequests/soft/'.$esignatureid;    ==> Dépot du document dans corbeille pour purge ultérieure
        /// ATTENTION : Bug dans le WS /ws/signrequests/status/{id} si le document est dans la corbeille. Il retourne 'pending'
        $url = $this->eSignature_url . "/ws/signrequests/" . $esignatureid;
        $json = '';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $this->set_curl_header($curl);

        $json = curl_exec($curl);
        $result = (array)json_decode($json);
        // // Convertion du résultat en type tableau/array
        // if (!is_null($result))
        // {
        //     $result = (array)$result;
        // }
        $error = curl_error ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int) $httpcode !== 200 and $error=="")
        {
            $error = "Code retour HTTP => $httpcode";
        }
        if (is_array($result) and isset($result['error']))
        {
            $error = $error . " : " . $result['error'];
        }

        curl_close($curl);
        if ($error != "")
        {
            $error =  __CLASS__ . "::" . __FUNCTION__ . " : Erreur Curl " . $error;
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($error));
            return $error;
        }

        // On cherche la chaine HTML dans les premiers caractères du json => Le retour n'est pas du JSON
        if (stristr(substr($json,0,20),'HTML') !== false) 
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : Réponse en HTML => " . var_export($json, true);
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($error));
            return $error;
        }
        else
        {
            // Tout s'est bien passé => On sort
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " : Suppression OK"));
            $error = "";
            $esignaturelog = new esignaturelog($this->dbconnect);
            $esignaturelog->delete($esignatureid);
        }
        return $error . "";


    }

    /**
     * Complète le modèle de document PDF avec le tableau de paramètres.
     * @param string $pdf_filename 
     *          Chemin complet du fichier PDF modèle
     * @param array $param
     *          Le tableau des paramètres à injecter dans le modèle PDF
     * @param string $result_pdffilename 
     *          Chemin complet du fichier PDF résultat
     * @return bool
     *          Retourne TRUE si tout c'est bien passé et FALSE dans le cas contraire
     */
    public function populate_pdfmodel(string $pdf_filename, array $params, string &$result_pdffilename) :bool
    {
        // FDF header section
        $fdf_header = <<<FDF
        %FDF-1.2
        %,,oe"
        1 0 obj
        <<
        /FDF << /Fields [
        FDF;

        // FDF footer section
        $fdf_footer = <<<FDF
        ] >> >>
        endobj
        trailer
        <</Root 1 0 R>>
        %%EOF;
        FDF;

        // On génère un nombre aléatoire pour augmenter les chances d'avoir un fichier unique en plus de la date et l'heure
        $random = random_int(10000, 99999);
        // Creating a temporary file for our FDF file.
        $FDFfile = $this->fonctions->pdfpath() . "/input_data_" . date("Ymd-His") . "_" . $random . ".fdf";
        $result_pdffilename = $this->fonctions->pdfpath() . "/populated_" . date("Ymd-His") . "_" . $random  . "_" . basename($pdf_filename);
        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " FDFfile : $FDFfile"));
        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " result_pdffilename : $result_pdffilename"));

        // FDF content section
        $fdf_content = "\n";

        foreach($params as $key => $value)
        {
            $fdf_content .= "<</T($key)/V(" . $this->fonctions->utf8_decode($value) . ")>>" . "\n";
        }
        $content = $fdf_header . $fdf_content . $fdf_footer;

        file_put_contents($FDFfile, $content);

        // Merging the FDF file with the raw PDF form
        $commandline = 'pdftk "' . $pdf_filename . '" fill_form "' . $FDFfile . '" output "' . $result_pdffilename . '"';
        $return = exec($commandline, $output, $resultcode); 

        //var_dump($return);
        //var_dump($output);
        //var_dump($resultcode);
        
        if (file_exists($FDFfile))
        {
            //unlink($FDFfile);
        }
        // Exec return false en cas d'erreur => On ne doit tester que si c'est false ou pas
        return ($return!==false);
    }

    /**
     * Création d'un nouveau circuit de signature à partir du fichier XML 
     * @param agent $demandeur 
     *          Objet agent du demandeur (cas où le type de signataire est DEMANDEUR ou RESPONSABLE)
     * @param string $nomcircuit
     *          Nom du fichier XML représentant le circuit à initialiser
     * @param array $stepinfos
     *          Données complémentaires relatives aux différentes étapes (type signature,....)
     * @return array
     *          Retourne le tableau des signataires 
     */
    public function createstep(agent $demandeur, string $nomcircuit, array &$stepinfos) :array
    {
        $signatairearray = array();
        $stepinfos = array();
        $xmldom = new DOMDocument();

        // On cherche le fichier XML représentant le circuit
        $filename = $this->fonctions->documentpath() . "/" . $nomcircuit; 
        if (!file_exists($filename))
        {
            echo $this->fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'existe pas.");
            return array();
        }
        
        // On charge le document XML
        $xmldom->validateOnParse = true;
        $valid = @$xmldom->load($filename);
        if (!$valid)
        {
            echo $this->fonctions->showmessage(fonctions::MSGERROR, "La syntaxe du fichier " . basename($filename) . " n'est pas correcte => Vérifiez la DTD.");
            return array();
        }

        // On valide la syntaxe du fichier XML avec la DTD 
        $valid = @$xmldom->validate();
        if (!$valid)
        {
            echo $this->fonctions->showmessage(fonctions::MSGERROR, "Le fichier " . basename($filename) . " n'est pas un fichier XML valide => Vérifiez la DTD.");
            return array();
        }

        // Attention : Le DOM doit être chargé au moment de la création du DOMXPath
        // Sinon, il ne trouve aucun noeux
        $xmlpath = new DOMXPath($xmldom);

        // On récupère la structure racine de l'affectation actuelle de l'agent
        $structid = $demandeur->structureid();
        $struct = new structure($this->dbconnect);
        $struct->load($structid);
        $structracineid = $struct->structureenglobante()->id();


        // On récupère le premier noeux 'CIRCUITS' (puisque d'après la DTD, il est présent et qu'il doit y avoir qu'un seul)
        $circuitsrootnode = $xmlpath->query('CIRCUITS')[0];
        //var_dump($circuitsrootnode->nodeName);
         // On récupère toutes les noeux 'CIRCUIT' (=> descriptions de chaque circuit)
        $circuitlist  = $xmlpath->query('CIRCUIT',$circuitsrootnode);
        $selectedcircuit = null;
        // On parcourt tous les circuits (donc les objets XML CIRCUIT)
        foreach ($circuitlist as $circuit)
        {
            // Si l'attribut STRUCTURE_RACINE n'est pas défini ou s'il est vide => Il devient le circuit par défaut
            if (is_null($circuit->attributes->getNamedItem('STRUCTURE_RACINE')) or trim($circuit->attributes->getNamedItem('STRUCTURE_RACINE')->nodeValue . '')=='')
            {
                // Si plusieurs circuits par défaut sont trouvés, on ne garde que le premier
                if (is_null($selectedcircuit))
                {
                    $selectedcircuit = $circuit;
                }
                else
                {
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " Attention : Un circuit par défaut a été trouvé alors qu'il est déjà configuré."));                    
                }
            }
            else // l'attribut STRUCTURE_RACINE existe et n'est pas vide
            {
                // On découpe les structures racine contenues dans l'attribut
                $structracinetab = explode(" ",$circuit->attributes->getNamedItem('STRUCTURE_RACINE')->nodeValue);
                // Si la structure racine est dans le tableau des attributs STRUCTURE_RACINE du circuit => On traite ces étapes
                if (in_array($structracineid,(array)$structracinetab))
                {
                    $selectedcircuit = $circuit;
                    // On sort de la boucle foreach => On ne parcourt pas les autres noeux 'CIRCUIT'
                    continue;
                }
            }
        }
        // Si aucun circuit n'a pu être déterminé, il y a un problème
        if (is_null($selectedcircuit))
        {
            echo $this->fonctions->showmessage(fonctions::MSGERROR, "Impossible de déterminer la liste des étapes pour la structure " . $struct->nomcourt() . " (id = $structid).");
            return array();
        }
        $descriptioncircuit = trim($selectedcircuit->attributes->getNamedItem('DESCRIPTION')->nodeValue);
        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " Le circuit sélectionné est : $descriptioncircuit."));

        // On charge les étapes du circuit sélectionné
        $etapelist = $xmlpath->query('ETAPE',$selectedcircuit);
        $etapelist_trie = array();
        // On va trier les étapes par ordre croissant
        foreach ($etapelist as $etape)
        {
            $numero = $etape->attributes->getNamedItem('NUMERO')->nodeValue;
            $description = trim($etape->attributes->getNamedItem('DESCRIPTION')->nodeValue);
            // Si le numéro de l'étape représente un nombre (is_numeric) entier (float = int)
            if (is_numeric($numero) and ((float)$numero)==(int)$numero)
            {
                $numero = intval($etape->attributes->getNamedItem('NUMERO')->nodeValue);
            }
            else
            {
                echo $this->fonctions->showmessage(fonctions::MSGERROR, "Le numéro de l'étape " . $etape->attributes->getNamedItem('NUMERO')->nodeValue . " dans le circuit de la structure $structid n'est pas un nombre strictement positif.");
                return array();    
            }
            if (!is_null($etape->attributes->getNamedItem('EXCLUDE_STRUCT_RACINE')) and trim($etape->attributes->getNamedItem('EXCLUDE_STRUCT_RACINE')->nodeValue . '')!='')
            {
                $structracinetab = explode(" ",$etape->attributes->getNamedItem('EXCLUDE_STRUCT_RACINE')->nodeValue);
                // Si la structure racine est dans le tableau des attributs EXCLUDE_STRUCT_RACINE de l'étape => On l'ignore car pas concerné
                if (in_array($structracineid,(array)$structracinetab))
                {
                    //var_dump("Je ne suis pas concerné par l'étape '$description'.");
                    // On passe à l'étape suivante
                    continue;
                }
                else
                {
                    //var_dump("Je suis concerné par l'étape '$description'.");
                }
            }
            elseif (!is_null($etape->attributes->getNamedItem('INCLUDE_STRUCT_RACINE')) and trim($etape->attributes->getNamedItem('INCLUDE_STRUCT_RACINE')->nodeValue . '')!='')

            {
                $structracinetab = explode(" ",$etape->attributes->getNamedItem('INCLUDE_STRUCT_RACINE')->nodeValue);

                // Si la structure racine n'est pas dans le tableau des attributs STRUCT_RACINE de l'étape => On l'ignore car pas concerné
                if (!in_array($structracineid,(array)$structracinetab))
                {
                    //var_dump("Je ne suis pas concerné par l'étape '$description'.");
                    // On passe à l'étape suivante
                    continue;
                }
                else
                {
                    //var_dump("Je suis concerné par l'étape '$description'.");
                }
            }

            // Si on trouve plusieurs étapes avec le même numéro, on ne conserve que la première
            if (isset($etapelist_trie[$numero]))
            {
                echo $this->fonctions->showmessage(fonctions::MSGERROR, "L'étape $numero est déjà définie dans le fichier " . basename($filename). ". Elle est donc ignorée.");
            }
            else
            {
                $etapelist_trie[$numero] = $etape;
            }
        }
        /////////////////////////////////////////////
        // IMPORTANT : On trie le tableau des étapes par ordre d'étape => Donc par NUMERO d'étape
        // Dans la suite du processus, on part du principe que le tableau des étapes est trié
        ksort($etapelist_trie);
        /////////////////////////////////////////////

        $nbstepskip = 0;
        foreach ($etapelist_trie as $etape)
        {
            // Le numéro de l'étape est le numéro de l'étape définie dans le fichier XML auquel on retranche le nombre d'étapes facultatives qu'on a ignoré (=>$nbstepskip)
            // On ignore les étapes qui n'ont pas de signataires et qui ne sont pas obligatoires (voir plus bas)
            $numero = (intval($etape->attributes->getNamedItem('NUMERO')->nodeValue) - $nbstepskip) . "";
            $description = trim($etape->attributes->getNamedItem('DESCRIPTION')->nodeValue);
            $typesignature = trim($etape->attributes->getNamedItem('TYPESIGNATURE')->nodeValue);
            $toutesignature = trim($etape->attributes->getNamedItem('TOUTESIGNATURE')->nodeValue);
            $obligatoire = trim($etape->attributes->getNamedItem('OBLIGATOIRE')->nodeValue);
            $piecejointeoblig = trim($etape->attributes->getNamedItem('PIECEJOINTEOBLIGATOIRE')->nodeValue);

            // On fixe le noeux de départ comme étant celui de l'étape
            $refnode = $etape;
            // On cherche si le noeux 'SIGNATAIRES' existe dans l'étape (il est facultatif)
            $signataireslist = $xmlpath->query('SIGNATAIRES',$etape);
            if (count($signataireslist)!=0)
            {
                // S'il existe on défini le noeux de référence comme étant le noeux 'SIGNATAIRES'
                $refnode = $signataireslist[0];
            }
            // Dans le noeux de référence 'SIGANATAIRES' ou 'ETAPE', on récupère la liste des noeux 'SIGNATAIRE'
            $signatairelist = $xmlpath->query('SIGNATAIRE',$refnode);
            //var_dump($signatairelist);
            foreach ($signatairelist as $signataire)
            {
                $typesignataire = trim($signataire->attributes->getNamedItem('TYPESIGNATAIRE')->nodeValue);
                $idsignataire = trim($signataire->nodeValue);
                if (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_DEMANDEUR)
                {
                    // On doit mettre le demandeur dans le tableau
                    $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $demandeur->agentid();
                    $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$demandeur->agentid());
                }
                elseif (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_AGENT)
                {
                    // On doit mettre l'ID de l'agent dans le tableau
                    $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $idsignataire;
                    $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$idsignataire);
                }
                elseif (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_RESPONSABLE)
                {
                    // On doit mettre le responsable du demandeur dans le tableau
                    $resp = $demandeur->getsignataire(null,$respstruct,$codeinterne);
                    if (!is_null($resp) and ($resp!==false))
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $resp->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$resp->agentid());
                    }
                    if ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT or $codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT)
                    {
                        $respsiham = $respstruct->responsablesiham();
                        if ($respsiham->mail() . "" != "")
                        {
                            $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $respsiham->agentid();
                            $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$respsiham->agentid());
                        }
                    }
                }
                elseif (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_RESPONSABLE2)
                {
                    // On doit mettre le responsable N+2 du demandeur dans le tableau
                    $arraysignataire_n2 = array();
                    $codeinterne = null;
                    $respdurespstruct = new structure($this->dbconnect);
                    $responsable_n2 = $demandeur->getsignataire_niveau2($respdurespstruct,$codeinterne);
                    //var_dump($responsable_n2);
                    if (!is_null($responsable_n2) and ($responsable_n2!==false))
                    {
                        $arraysignataire_n2[$responsable_n2->agentid()] = $responsable_n2;
                    }
                    if ($responsable_n2!==false and ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT or $codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT))
                    {
                        $respsiham_n2 = $respdurespstruct->responsablesiham();
                        if ($respsiham_n2->mail() . "" != "")
                        {
                            $arraysignataire_n2[$respsiham_n2->agentid()] = $respsiham_n2;                
                        }
                    }
        
                    //var_dump($responsable_n2);
                    // On n'a pas trouvé de responsable n+2
                    foreach((array)$arraysignataire_n2 as $signataire)
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $signataire->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$signataire->agentid());                        
                    }
                }
                elseif (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_RESP_STRUCT)
                {
                    // On met le responsable de la structure dans le tableau
                    $structuresignataire = new structure($this->dbconnect);
                    $structuresignataire->load($idsignataire);
                    $agentsignataire = $structuresignataire->responsable();
                    if ($agentsignataire->mail() . "" !="")
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $agentsignataire->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$agentsignataire->agentid());
                    }
                    $agentsignataire = $structuresignataire->responsablesiham();
                    if ($agentsignataire->mail() . "" !="")
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $agentsignataire->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$agentsignataire->agentid());
                    }
                }
                elseif (strtoupper($typesignataire)==esignature::TYPESIGNATAIRE_DIRECTEUR)
                {
                    //var_dump("Je suis dans le cas d'un directeur");
                    $structracine = $struct->structureenglobante();
                    // //var_dump($structracine->responsable()->agentid());

                    $resp = $structracine->responsable();
                    if ($resp->agentid()!='')
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $resp->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$resp->agentid());
                        //var_dump($signatairearray[$numero][$tempid]);
                    }
                    $resp = $structracine->responsablesiham();
                    if ($resp->agentid()!='')
                    {
                        $tempid = fonctions::SIGNATAIRE_AGENT . '_' . $resp->agentid();
                        $signatairearray[$numero][$tempid] = array(fonctions::SIGNATAIRE_AGENT,$resp->agentid());
                        //var_dump($signatairearray[$numero][$tempid]);
                    }
                }
                else
                {
                    if ($numero!='' and $typesignataire!='' and $idsignataire!='')
                    {
                        $tempid = $typesignataire . '_' . $idsignataire;
                        $signatairearray[$numero][$tempid] = array($typesignataire,$idsignataire);
                    }
                }
            }

            if (isset($signatairearray[$numero]))
            {
                foreach ((array)$signatairearray[$numero] as $key => $signataire)
                {
                    // Si le type de signataire est un AGENT et que c'est l'id de l'utilisateur CRON
                    if ($signataire[0]==fonctions::SIGNATAIRE_AGENT and $signataire[1]==SPECIAL_USER_IDCRONUSER)
                    {
                        // On le supprime
                        unset($signatairearray[$numero][$key]);
                    }
                }
            }

            // Si on a des signataires dans l'étape courante
            if (isset($signatairearray[$numero]) and count($signatairearray[$numero])>0)
            {
                if (!isset($stepinfos[$numero]))
                {
                    $extrainfos = new stepinfos();
                    $extrainfos->stepNumber = $numero;
                    if (!in_array($typesignature, array(stepinfos::PDFSIGNATURE,stepinfos::HIDDENVISA, stepinfos::VISA)))
                    {
                        echo $this->fonctions->showmessage(fonctions::MSGERROR, "Le type de signature " . $typesignature . " n'est pas reconnu.");
                        return array();
                    }
                    $extrainfos->description = $description;
                    // Cette ligne génère un post-it !
                    //$extrainfos->comment = "Ceci est un commentaire";
                    $extrainfos->signType = $typesignature;
                    $extrainfos->allSignToComplete = $this->fonctions->convertvaluetobool($toutesignature);
                    $extrainfos->forceAllSign = $extrainfos->allSignToComplete;
                    $extrainfos->obligatoire = $this->fonctions->convertvaluetobool($obligatoire);
                    $extrainfos->attachmentRequire = $this->fonctions->convertvaluetobool($piecejointeoblig);
                    $extrainfos->signataires = $signatairearray[$numero];
                    $stepinfos[$numero] = $extrainfos;
                }
            }
            // Aucun signataire n'est défini => on va regarder si l'étape est obligatoire ou pas
            // Si elle n'est pas obligatoire c'est qu'on peut l'ignorer donc on augmente le nombre d'étape ignorée
            elseif (!$this->fonctions->convertvaluetobool($obligatoire))
            {
                $nbstepskip++;
                //var_dump("Je skip l'étape $numero");
            }
            else
            {
                echo $this->fonctions->showmessage(fonctions::MSGERROR, "L'étape '$description' du circuit de signature " . basename($filename). " est obligatoire mais est vide.");
            }

        }

        //var_dump("signatairearray = "); var_dump($signatairearray);
        return $signatairearray;
    }

    /**
     * Récupération du statut de chaque étape de signature
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @param string $stepnumber
     *          Numéro de l'étape dont il faut retourner l'état (vide => tous les niveaux). Le premier niveau = 1
     *          La valeur doit être comprise entre 1 et le nombre d'étape maximum
     * @return array|string
     *          Retourne un tableau des statuts de chaque niveau ou une chaine de caractère en cas de problème 
     */
    public function get_signrequest_stepstatus(string $esignatureid, string $stepnumber = "") :array|string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }
        if (strlen($stepnumber)>0)
        {
            if (!is_numeric($stepnumber) or intval($stepnumber)<1)
            {
                return "Le paramètre stepnumber doit être un entier supérieur ou égal à 1.";
            }
            $stepnumber = intval($stepnumber)-1; // Dans eSignature les niveaux sont numérotés à partir de 0
        }

        $recipienttab = $this->get_signrequest_recipients($esignatureid);
        if (is_string($recipienttab))
        {
            return $recipienttab;
        }

        $status = array();
        foreach ($recipienttab as $stepindex => $step)
        {
            // Si l'étape à vérifier est celle demandée ou si on n'a pas précisé d'étape
            if ($stepnumber == $stepindex or $stepnumber=="")
            {
                $stepstatus = "";
                foreach ($step as $recipient)
                {
                    switch (strtolower($recipient->action))
                    {
                        case 'refused' :
                            $stepstatus = $recipient->action;
                            // On a trouvé un 'refused => On sort de toutes les boucles (=> break 2)
                            break 2 ;
                        case 'signed' :
                            $stepstatus = $recipient->action;
                            break;
                        default: 
                            break;
                    }
                }
                $status["$stepindex"] = $stepstatus;
            }
        }
        return $status;
    }

    /**
     * Récupération des commentaires (les post-it) liés à un document
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return array|string
     *          Retourne un tableau des commentaires ou une chaine de caractère en cas de problème 
     */
    public function get_signrequest_stepcomment(string $esignatureid) :array|string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }
        $comments = array();
        if (isset($this->signrequestinfo['comments']))
        {
            foreach ($this->signrequestinfo['comments'] as $commentobj)
            {
                $comments[] = $commentobj['text'];
            }
        }
        return $comments;
    }

    /**
     * Récupération de la date de fin du circuit
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return string
     *          Retourne la date de fin du circuit au format YYYY-MM-DD), 1900-01-01 si la date n'a pas pu être trouvée ou le descriptif de l'erreur en cas de problème
     */
    public function get_signrequest_enddate(string $esignatureid) :string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }

        $enddate = "1900-01-01";
        if (isset($this->signrequestinfo['parentSignBook']['endDate']))
        {
            $enddate = strtoupper($this->signrequestinfo['parentSignBook']['endDate']);
            /// Exemple de valeur : "2025-03-05T14:35:14.339+00:00"
            $enddate_array = explode("T", $enddate);
            if (count($enddate_array)==2)
            {
                // La date est dans le premier élément du tableau
                $enddate = reset($enddate_array);
            }
            else
            {
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " Le format de la date de fin n'est pas conforme : $enddate."));
            }
        }
        else
        {
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " La date de fin n'est pas définie : ['parentSignBook']['endDate'] n'existe pas."));     
        }
        return $enddate;
    }

    /**
     * Récupération des destinataires du circuit par étape
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return array|string
     *          Retourne le tableau des destinataires par étape de signature ou le descriptif de l'erreur en cas de problème
     */
    public function get_signrequest_recipients(string $esignatureid) :array|string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }

        $hassignedarray = array();
        if (isset($this->signrequestinfo['recipientHasSigned']))
        {
            $recipienthassigned = $this->signrequestinfo['recipientHasSigned'];
            foreach ($recipienthassigned as $key => $currentrecipient)
            {
                if (str_starts_with(strtolower($key),'recipient-'))
                {
                    $stepid = intval(str_ireplace("recipient-","",$key));

                    //var_dump($currentrecipient);

                    $recipientuser = $currentrecipient['user'];
                    if ($currentrecipient['signed'] and  isset($this->signrequestinfo['recipientHasSigned']["action-$stepid"]))
                    {
                        $currentaction = $this->signrequestinfo['recipientHasSigned']["action-$stepid"];
                        $date = new datetime($currentaction['date'], new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone('Europe/Paris'));
                        $hassignedarray[$recipientuser['email']][$date->format('Y-m-d H:i:s')] = $currentaction['actionType'] . '#' . $date->format('d-m-Y H:i:s');
                    }
                }
            }
            //var_dump($hassignedarray);
            foreach ($hassignedarray as $key => $recipientlist)
            {
                //var_dump($recipientlist);
                ksort($recipientlist,SORT_STRING);
                //var_dump($recipientlist);
                $hassignedarray[$key] = $recipientlist;
            }
            ksort($hassignedarray);
            //var_dump($hassignedarray);
        }

        $recipientlist = array();
        if (isset($this->signrequestinfo['parentSignBook']['liveWorkflow']['liveWorkflowSteps']))
        {
            $liveworkflowsteps = $this->signrequestinfo['parentSignBook']['liveWorkflow']['liveWorkflowSteps'];

            $indexstep=0;
            foreach ($liveworkflowsteps as $currentstep)
            {
                foreach($currentstep['recipients'] as $recipient)
                {
                    $recipientuser = $recipient['user'];
                    $esignaturerecipient = new esignaturerecipient();
                    $esignaturerecipient->nom = $recipientuser['name'] . "";
                    $esignaturerecipient->prenom = $recipientuser['firstname'] . "";
                    $esignaturerecipient->eppn = $recipientuser['eppn'] . "";
                    $esignaturerecipient->mail = $recipientuser['email'] . "";
                    $esignaturerecipient->hassigned = $recipient['signed'];
                    // var_dump($esignaturerecipient->mail);
                    if (isset($hassignedarray[$esignaturerecipient->mail]))
                    {
                        if (is_array($hassignedarray[$esignaturerecipient->mail]) and count($hassignedarray[$esignaturerecipient->mail])>0)
                        {
                            $actioninfos = array_shift($hassignedarray[$esignaturerecipient->mail]);
                            // var_dump($actioninfos, $hassignedarray[$esignaturerecipient->mail]);
                            $infos = explode('#',$actioninfos);
                            $esignaturerecipient->action = $infos[0];
                            $esignaturerecipient->actiondate = $infos[1];
                        }
                        else
                        {
                            // var_dump("Le tableau est vide pour " . $esignaturerecipient->mail);
                        }
                    }
                    else
                    {
                        // var_dump("Pas d'action pour " . $esignaturerecipient->mail);
                    }
                    $recipientlist[$indexstep][] = $esignaturerecipient;
                }
                $indexstep++;
            }
        }
        else // On a rencontré une erreur dans la récupération du currentstep
        {
            return "Impossible de déterminer les destinataires des étapes du circuit $esignatureid";
        }

        // var_dump($recipientlist);
        return ($recipientlist);
    }


    /**
     * Récupération du numéro de l'étape en cours
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @return int|string
     *          Retourne le numéro de l'étape courante (à partir de 0) ou le descriptif de l'erreur en cas de problème
     */
    public function get_signrequest_currentstep(string $esignatureid) :int|string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }

        if (isset($this->signrequestinfo['parentSignBook']['liveWorkflow']['currentStepNumber']))
        {
            $currentstepnumber = $this->signrequestinfo['parentSignBook']['liveWorkflow']['currentStepNumber'];
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(__CLASS__ . "::" . __FUNCTION__ . " Le currentstepnumber = $currentstepnumber dans la convention $esignatureid "));     
            return intval($currentstepnumber-1);
        }
        else
        {
            return "Impossible de déterminer l'étape courante du circuit $esignatureid.";
        }
    }

    /**
     * Récupération des informations concernant la création d'une demande
     * @param string $esignatureid 
     *          Identifiant du document eSignature
     * @param string $username
     *          Contient l'identité du créateur du circuit
     * @param string $date
     *          Contient la date de création du circuit
     * @return string
     *          Chaine vide si tout s'est bien passé ou le descriptif de l'erreur dans le cas contraire
     */
    public function get_signrequest_creationinfo(string $esignatureid, string &$username, string &$date ) :string
    {
        if (is_null($this->signrequestinfo))
        {
            $response = $this->get_signrequest($esignatureid);
            if (is_string($response))
            {
                return $response;
            }
        }

        $username = "";
        $date = "";
        if (isset($this->signrequestinfo["parentSignBook"]["createBy"]["firstname"]) and isset($this->signrequestinfo["parentSignBook"]["createBy"]["name"]))
        {
            $username = $this->signrequestinfo["parentSignBook"]["createBy"]["firstname"] . " " . $this->signrequestinfo["parentSignBook"]["createBy"]["name"] ;
        }
        else
        {
            $username = "";
            $date = "";
            return "Impossible de déterminer le créateur du circuit $esignatureid.";
        }

        if (isset($this->signrequestinfo["parentSignBook"]["createDate"]))
        {
            $datetime = new datetime($this->signrequestinfo["parentSignBook"]["createDate"], new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Europe/Paris'));
            $date = $datetime->format('d/m/Y H:i:s') ;
        }
        else
        {
            $username = "";
            $date = "";
            return "Impossible de déterminer la date de création du circuit $esignatureid.";
        }
        return '';
    }

}