<?php

use Fpdf\Fpdf as FPDF;

class etapeextrainfo
{
    public const PDFSIGNATURE = 'pdfImageStamp';
    public const HIDDENVISA  = 'hiddenVisa';
    public const VISA = 'visa';

    public $stepNumber = '';
    public $signType = 'pdfImageStamp';   //// Type de signature de eSignature = hiddenVisa, visa, pdfImageStamp, certSign, nexuSign
    public $forceAllSign = false;
    public $allSignToComplete = false;

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

        return $extrainfos;
    }
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


    /**
     *
     * @param mysqli $db
     *            La connexion MySQL/MariaDB à la base de données
     */
    function __construct(mysqli $db)
    {
        $this->fonctions = new fonctions($db);

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
    public function get_data(string $esignatureid) :array|string
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
    public function get_signrequests(string $esignatureid) :array|string
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
    public function get_document(string $esignatureid, string &$pdf) : string
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
    public function get_status(string $esignatureid) : string|false
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
            return $error;
        }
        else
        {
            //var_dump("ID = $id");
            return intval($id);
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
    public function modify_recipient(string $esignatureid, array $params_string, int $stepnumber = 0) : string
    {
        if (!$this->check_esignatureid($esignatureid))
        {
            $error = __CLASS__ . "::" . __FUNCTION__ . " : L'identifiant eSignature $esignatureid n'est pas valide.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" $error"));
            return $error;
        }

        //$params_string['recipientWsDtosString'] = '[{"email" : "aaaa.bbbb@etab.fr"},{"email" : "cccc.dddd@univ-paris1.fr"}]';
        //$params_string['stepNumber'] = 2;

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
        var_dump($json);
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
        }
        return $error . "";


    }

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
            unlink($FDFfile);
        }
        // Exec return false en cas d'erreur => On ne doit tester que si c'est false ou pas
        return ($return!==false);
    }



}