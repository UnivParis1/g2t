<?php

use Fpdf\Fpdf as FPDF;

class commentaireconge
{
    public $commentaireid;
    public $agentid;
    public $typeabsenceid;
    public $dateajout;
    public $commentaire;
    public $nbjoursajoute;
    public $nbjrspris;
    public $auteurid;
    public $libelleabsence;
}

class sihamaffectation
{
    public $debut;
    public $fin;
    public $numcontrat;
    public $quotite;
};
    


/**
 * Agent
 * Definition of the agent
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class agent
{
    const PROFIL_RHCET = 'RHCET';
    const PROFIL_RHCONGE = 'RHCONGE';
    const PROFIL_RHTELETRAVAIL = 'RHTELETRAVAIL';
    const PROFIL_RHANOMALIE = 'RHANOMALIE'; // OBSOLETE => NE PLUS UTILISER
    
    const FILTRE_DEMANDE = 'DEMANDE';
    const FILTRE_SOLDE  = 'SOLDE';

    const CHECK_PERIODE_COUVERTE = 'COUVERTE';
    const CHECK_PERIODE_NONCOUVERTE = 'NONCOUVERTE';
    const CHECK_PERIODE_EXCEPTION = 'EXCEPTION';
    const CHECK_PERIODE_AJOUTEE = 'AJOUTEE';
    const CHECK_PERIODE_ERREUR = 'ERREUR';
        
    const WS_METHODE_EXCEPTION_PERIODE = 'EXCEPTION_PERIODE';
    const WS_METHODE_SEND_MAIL = 'SEND_MAIL';
    const WS_METHODE_FORCE_PERIODE = 'FORCE_PERIODE';
    const WS_METHODE_ONOFF_ANIMATION = 'ONOFF_ANIMATION';
    
    private $agentid = null;
    private $eppn = null;
    private $uid = null;
    private $nom = null;
    private $prenom = null;
    private $dbconnect = null;
    private $civilite = null;
    private $adressemail = null;
    private $typepopulation = null;
    private $structureid = null;
    private $fonctions = null;

    /**
     *
     * @param object $db
     *            the mysql connection
     * @return
     */
    function __construct($db)
    {
        $this->dbconnect = $db;
        $this->fonctions = new fonctions($db);
        if (is_null($this->dbconnect)) {
            $errlog = "Agent->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param string $agentid
     *            the identifier of the current agent
     * @return boolean TRUE if all correct, FALSE otherwise
     */
    function load($agentid)
    {
        // echo "Debut Load";
        if (is_null($this->agentid)) {
            
            if (!$this->existe($agentid))
            {
                return false;
            }
            
            $sql = "SELECT AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION,STRUCTUREID,EPPN,UID FROM AGENT WHERE AGENTID= ? ";
            $params = array($this->fonctions->my_real_escape_utf8($agentid));
            $query = $this->fonctions->prepared_select($sql, $params);
            
            // echo "sql = " . $sql . "<br>";
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Agent->Load (AGENT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Agent->Load (AGENT) : Agent $agentid non trouvé";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
            $result = mysqli_fetch_row($query);
            $this->agentid = "$result[0]";
            $this->civilite = "$result[1]";
            $this->nom = "$result[2]";
            $this->prenom = "$result[3]";
            
            // On utilise la fonction "mail" car il y a dedans le test sur l'ID de l'agent pour l'impacter ou pas en fonction de la constante FORCE_AGENT_MAIL
            $this->mail("$result[4]");

            $this->typepopulation = "$result[5]";
            $this->structureid = "$result[6]";
            $this->eppn = "$result[7]";
            $this->uid = "$result[8]";
            return true;
        }
        // echo "Fin...";
    }
    
    function loadbyemail($email)
    {
        $sql = "SELECT AGENTID FROM AGENT WHERE LOWER(ADRESSEMAIL) = LOWER(?) ";
        $params = array($this->fonctions->my_real_escape_utf8($email));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->loadbyemail (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 0) 
        {
            $errlog = "Agent->loadbyemail (AGENT) : Aucune adresse mail ($email) trouvée.";
            //echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) > 1) 
        {
            $errlog = "Agent->loadbyemail (AGENT) : Plusieurs adresses mail ($email) trouvées.";
            //echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        $result = mysqli_fetch_row($query);
        return $this->load("$result[0]");
    }
    
    function existe($agentid)
    {
        $sql = "SELECT AGENTID FROM AGENT WHERE AGENTID= ? ";
        $params = array($this->fonctions->my_real_escape_utf8($agentid));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->existe (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 0) {
            return false;
        }
        return true;
    }
    
    function store($agentid)
    {
        //////////////////////////////////////////////////////////////////////
        // Lors de la sauvegarde, on "n'escape pas" le NOM et le PRENOM car //
        // c'est fait automatiquement lors de la construction de la requète //
        //////////////////////////////////////////////////////////////////////
        
        if ($this->estutilisateurspecial($agentid))
        {
            //echo "<br>On set les paramètres avec les valeurs par défaut";
            if (strlen(trim($this->civilite . ""))==0) $this->civilite('');
            if (strlen(trim($this->nom . ""))==0) $this->nom('SPECIAL_' . $agentid);
            if (strlen(trim($this->prenom . ""))==0) $this->prenom('UTILISATEUR_' . $agentid);
            if (strlen(trim($this->adressemail . ""))==0) $this->mail('noreply@no_domaine.fr');
            
            if ($this->existe($agentid))
            {
                // Mise à jour de l'agent
                $sql = "UPDATE AGENT SET CIVILITE = ?, NOM = ?, PRENOM = ?, ADRESSEMAIL = ? WHERE AGENTID = ?";
                $params = array(
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail,
                    $agentid
                );
            }
            else
            {
                // Ajout manuel de l'agent
                $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES(?,?,?,?,?,'')";
                $params = array(
                    $agentid,
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail
                );
            }
        }
        else
        {
            if (strlen(trim($this->civilite . ""))==0 or 
                strlen(trim($this->nom . ""))==0 or 
                strlen(trim($this->prenom . ""))==0 or
                strlen(trim($this->adressemail . ""))==0
               )
            {
                $errlog = "Agent->store (AGENT) : Au moins une des propriétés est vide (civilité, nom, prénom, adresse mail) => Sauvegarde impossible.";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
            
            // Si le type population est vide (null, vide, avec des espaces, ....) on le force à vide
            if (strlen(trim($this->typepopulation . ""))==0)  $this->typepopulation = "";
            if (strlen(trim($this->structureid . ""))==0)  $this->structureid = "";
                        
            if ($this->existe($agentid))
            {
                // Mise à jour de l'agent
                $sql = "UPDATE AGENT SET CIVILITE = ?, NOM = ?, PRENOM = ?, ADRESSEMAIL = ?, TYPEPOPULATION = ?, STRUCTUREID = ?, EPPN = ?, UID = ? WHERE AGENTID = ?";
                $params = array(
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail,
                    $this->typepopulation,
                    $this->structureid,
                    $this->eppn . "",
                    $this->uid . "",
                    $agentid
                );
            }
            else
            {
                // Ajout manuel de l'agent
                $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION,STRUCTUREID,EPPN,UID) VALUES(?,?,?,?,?,?,?,?,?)";
                $params = array(
                    $agentid,
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail,
                    $this->typepopulation,
                    $this->structureid,
                    $this->eppn . "",
                    $this->uid . ""
                );
            }
        }
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Agent->store (AGENT) : Error => " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        $this->agentid = $agentid;
        return true;
    }

    function estutilisateurspecial($agentid = null)
    {
        $tab_special_users = $this->fonctions->listeutilisateursspeciaux();
        // Si on ne spécifie pas le id de l'agent on prend celui de l'objet courant
        if (is_null($agentid))
            return in_array($this->agentid(),$tab_special_users);
        else
            return in_array($agentid,$tab_special_users);
    }

    /**
     *
     * @param
     * @return string the identifier of the current agent
     */
    function agentid()
    {
        return $this->agentid;
    }
    
    function sihamid()
    {
        return "UP1" . str_pad($this->agentid(),9,'0', STR_PAD_LEFT);
    }

    /**
     *
     * @param string $name
     *            optional the name of the current agent
     * @return string name of the current agent if $name parameter not set. No return otherwise
     */
    function nom($name = null)
    {
        if (is_null($name)) {
            if (is_null($this->nom)) {
                $errlog = "Agent->nom : Le nom de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {  
                return $this->nom;
            }
        } 
        else
        {
//            $this->nom = $name;
            if (mb_detect_encoding($name, 'UTF-8', true))
            {
                $this->nom = $name;
            }
            else
            {
                $this->nom = $this->fonctions->utf8_encode($name);
            }
        }
    }

    /**
     *
     * @param string $firstname
     *            optional the firstname of the current agent
     * @return string firstname of the current agent if $firstname parameter not set. No return otherwise
     */
    function prenom($firstname = null)
    {
        if (is_null($firstname)) {
            if (is_null($this->prenom)) {
                $errlog = "Agent->prenom : Le prénom de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->prenom;
            }
        } 
        else
        {
//            $this->prenom = $firstname;
            if (mb_detect_encoding($firstname, 'UTF-8', true))
            {
                $this->prenom = $firstname;
            }
            else
            {
                $this->prenom = $this->fonctions->utf8_encode($firstname);
            }
        }
    }

    /**
     *
     * @param string $civilite
     *            optional the civility of the current agent
     * @return string civility of the current agent if $civilite parameter not set. No return otherwise
     */
    function civilite($civilite = null)
    {
        if (is_null($civilite)) {
            if (is_null($this->civilite)) {
                $errlog = "Agent->civilite : La civilité de l'agent n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->civilite;
            }
        } 
        else
        {
//            $this->civilite = $civilite;
            if (mb_detect_encoding($civilite, 'UTF-8', true))
            {
                $this->civilite = $civilite;
            }
            else
            {
                $this->civilite = $this->fonctions->utf8_encode($civilite);
            }
        }
    }

    /**
     *
     * @param
     * @return string the full name of the current agent (civility + firstname + name)
     */
    function identitecomplete($namefirst = false)
    {
        if ($namefirst)
        {
            return $this->civilite . " " . $this->nom() . " " . $this->prenom();
        }
        else
        {
            return $this->civilite . " " . $this->prenom() . " " . $this->nom();
        }
    }

    /**
     *
     * @param string $mail
     *            optional the mail of the current agent
     * @return string mail of the current agent if $mail parameter not set. No return otherwise
     */
    function mail($mail = null)
    {
        if (is_null($mail)) 
        {
            // Si ce n'est pas un utilisateur spécial
            //var_dump ($this->agentid());
/*            
            if (!$this->estutilisateurspecial($this->agentid()))
            {
                $dbconstante = "FORCE_AGENT_MAIL";
                if ($this->fonctions->testexistdbconstante($dbconstante)) 
                {
                    $mail = trim($this->fonctions->liredbconstante($dbconstante));
                    if (strlen($mail)>0) 
                    {
                        return $mail;
                    }
                }
            }
*/
            $dbconstante = "FORCE_AGENT_MAIL";
            if ($this->fonctions->testexistdbconstante($dbconstante))
            {
                $mail = trim($this->fonctions->liredbconstante($dbconstante));
                if (strlen($mail)>0)
                {
                    return $mail;
                }
            }
            if (is_null($this->adressemail)) 
            {
                $errlog = "Agent->mail : Le mail de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->adressemail;
            }
        } 
        else
        {
//            $this->adressemail = $mail;
            if (mb_detect_encoding($mail, 'UTF-8', true))
            {
                $this->adressemail = $mail;
            }
            else
            {
                $this->adressemail = $this->fonctions->utf8_encode($mail);
            }
        }
    }

    /**
     *
     * @param 
     * @return string mail from database of the current agent 
     */
    function mailforspecialagent()
    {
        if ($this->estutilisateurspecial($this->agentid()))
        {
            if (is_null($this->adressemail))
            {
                $errlog = "Agent->mailforspecialagent : Le mail de l'agent special " . $this->agentid() . " n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                return $this->adressemail;
            }
        }
        else
        {
            $errlog = "Agent->mailforspecialagent : L'agent " . $this->agentid() . " n'est pas un utilisateur spécial !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }
    
    function eppn($eppn = null)
    {
        if (is_null($eppn)) {
            if (is_null($this->eppn)) {
                $errlog = "Agent->eppn : L'eppn de l'agent n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("L'EPPN de " . $this->agentid . " est " . $this->eppn));
                return $this->eppn;
            }
        } 
        else
        {
            if (mb_detect_encoding($eppn, 'UTF-8', true))
            {
                $this->eppn = $eppn;
            }
            else
            {
                $this->eppn = $this->fonctions->utf8_encode($eppn);
            }
        }
    }
    
    function uid($uid = null)
    {
        if (is_null($uid)) 
        {
            // Si pas d'UID défini pour l'agent => On va demander à LDAP
            if (trim($this->uid . "") == "")
            {
                $wsgroupURL = $this->fonctions->liredbconstante('WSGROUPURL');

                $curl = curl_init();
                $params_string = "";
                $opts = [
                    CURLOPT_URL => "$wsgroupURL/searchUserTrusted?token=" . $this->mail() . "&attrs=uid",
                    //CURLOPT_URL => "$wsgroupURL/searchUser?token=" . $this->mail() . "&attrs=uid",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_PROXY => ''
                ];
        
                $dbconstante = "WSGROUPS_SECRET_TOKEN";
                if ($this->fonctions->testexistdbconstante($dbconstante))
                {
                    $accessToken = trim($this->fonctions->liredbconstante($dbconstante));
                    if (strlen($accessToken)>0)
                    {
                        ///////////////////////////////////////////////////////////
                        //// ATTENTION : TOKEN DE BYPASS A METTRE EN PARAMETRE DANS LE CONFIG
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer $accessToken"));
                        ///////////////////////////////////////////////////////////
                    }
                }
        
                curl_setopt_array($curl, $opts);
                curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
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
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Erreur Curl (récup searchUserTrusted agent " . $this->agentid() .  ") =>  " . $error));
                    echo "Erreur : $error";
                }
                $response = json_decode($json, true);
                if (isset($response[0]['uid']))
                {
                    $this->uid= $response[0]['uid'];
                }
            } 
            return $this->uid . "";
        } 
        else
        {
            if (mb_detect_encoding($uid, 'UTF-8', true))
            {
                $this->uid = $uid;
            }
            else
            {
                $this->uid = $this->fonctions->utf8_encode($uid);
            }
        }

    }

    function fonctionRIFSEEP()
    {
        
        $wsgroupURL = $this->fonctions->liredbconstante('WSGROUPURL');

        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => "$wsgroupURL/searchUserTrusted?token=" . $this->mail(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
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
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Erreur Curl (récup searchUserTrusted agent " . $this->agentid() .  ") =>  " . $error));
        }
        $response = json_decode($json, true);
        // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La réponse (récup searchUserTrusted agent " . $this->agentid() .  ") => " . print_r($response,true)));
        if (isset($response[0]['supannActivite-all'][0]['name-gender']))
        {
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La fonction de l'agent (WS searchUserTrusted) est " . $response[0]['supannActivite-all'][0]['name-gender']));
            return $response[0]['supannActivite-all'][0]['name-gender'];
        }
        // On n'a pas trouvé la fonction dans le WS searchUserTrusted => On utilise le WS searchUser
        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Pas de fonction dans searchUserTrusted pour l'agent " . $this->agentid() . " => On cherche dans searchUser."));
        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => "$wsgroupURL/searchUser?token=" . $this->mail(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
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
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Erreur Curl (récup searchUser agent " . $this->agentid() .  ") =>  " . $error));
        }
        $response = json_decode($json, true);
        // error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La réponse (récup searchUser agent " . $this->agentid() .  ") => " . print_r($response,true)));
        if (isset($response[0]['supannActivite-all'][0]['name-gender']))
        {
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La fonction de l'agent (WS searchUser) est " . $response[0]['supannActivite-all'][0]['name-gender']));
            return $response[0]['supannActivite-all'][0]['name-gender'];
        }
        else
        {
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Pas de fonction dans searchUser pour l'agent " . $this->agentid() . " => On retourne vide."));
            return "";
        }    
    }
    
    function ldapmail()
    {
        $agent_mail = '';
        $LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_AGENT_MAIL_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_MAIL_ATTR");
        $LDAP_SUPANNEMPID_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $filtre = "($LDAP_SUPANNEMPID_ATTR=" . $this->agentid . ")";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array("$LDAP_AGENT_MAIL_ATTR");
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        //echo "Info = " . print_r($info,true) . "<br>";
        //echo "L'email de l'agent sélectionné est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . "<br>";
        if (isset($info[0]["$LDAP_AGENT_MAIL_ATTR"][0])) {
            $agent_mail = $info[0]["$LDAP_AGENT_MAIL_ATTR"][0];
            // echo "Agent eMail = $agent_mail <br>";
        }
        return $agent_mail;
    }
    
    /**
     *
     * @param string $type
     *            optional the type of the current agent
     * @return string type of the current agent if $type parameter not set. No return otherwise
     */
    function typepopulation($type = null)
    {
        if (is_null($type)) {
            if (is_null($this->typepopulation)) {
                $errlog = "Agent->typepopulation : Le type de population de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->typepopulation;
            }
        } 
        else
        {
            if (mb_detect_encoding($type, 'UTF-8', true)) 
            {
                $this->typepopulation = $type;
            }
            else
            {
                $this->typepopulation = $this->fonctions->utf8_encode($type);
            }
        }
    }
    
    /**
     *
     * @param
     * @return string the structure identifier for the current agent
     */
    function structureid($structureid = null)
    {
        if (is_null($structureid))
        {
        	if (is_null($this->structureid)) 
        	{
        		$errlog = "Agent->structureid : L'Id de la structure n'est pas défini !!!";
        		echo $errlog . "<br/>";
        		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        	} 
        	else
        	{
        		return $this->structureid;
        	}
        }
        else
        {
            $this->structureid = $structureid;
        }
    }
    
    function travailsamedi($date)
    {
        static $lastperiod = null;

        $datedb = $this->fonctions->formatdatedb($date);
        if (date("w",strtotime($datedb)) == 6) // 6 = samedi
        {
            // Si on n'a pas déjà chargé les période SAMEDI pour l'agent => On va les charger
            if (!isset($lastperiod[$this->agentid]))
            {
                $sql = "SELECT DATEDEBUT,DATEFIN,AGENTPERIODEID FROM AGENTPERIODE WHERE AGENTID = ? AND TYPEPERIODE = 'SAMEDI'";
                $params = array($this->agentid);
                $query = $this->fonctions->prepared_select($sql, $params);
                // echo "sql = " . $sql . "<br>";
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") 
                {
                    $errlog = "Agent->travailsamedi (AGENT) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    return FALSE;
                }
                // On vient de charger les périodes de l'agent => On initialise l'entrée du tableau à vide
                $lastperiod[$this->agentid] = array();
                // Pour toutes les lignes récupérées => On les charges dans le tableau
                while($result = mysqli_fetch_row($query))
                {
                    $lastperiod[$this->agentid][$result[2]]["datedebut"] = $this->fonctions->formatdatedb($result[0]);
                    $lastperiod[$this->agentid][$result[2]]["datefin"] = $this->fonctions->formatdatedb($result[1]);
                }
            }

            // On parcourt le tableau des périodes de l'agent
            foreach($lastperiod[$this->agentid] as $periode)
            {
                // Si la date courante est entre le début et la fin de la période => On a trouvé
                if ($periode["datedebut"] <= $datedb and $periode["datefin"] >= $datedb)
                {
                    return true;
                }
            }
            // On a parcouru toutes les périodes de l'agent => On n'a rien trouvé donc il ne travaille pas le SAMEDI
            return false;
        }
        else // La date n'est pas un SAMEDI => donc retourne FALSE
        {
            return false;
        }
    }

    function travaildimanche($date)
    {
        static $lastperiod = null;

        $datedb = $this->fonctions->formatdatedb($date);
        if (date("w",strtotime($datedb)) == 0) // 0 = dimanche
        {
            // Si on n'a pas déjà chargé les période DIMANCHE pour l'agent => On va les charger
            if (!isset($lastperiod[$this->agentid]))
            {
                $sql = "SELECT DATEDEBUT,DATEFIN,AGENTPERIODEID FROM AGENTPERIODE WHERE AGENTID = ? AND TYPEPERIODE = 'DIMANCHE'";
                $params = array($this->agentid);
                $query = $this->fonctions->prepared_select($sql, $params);
                // echo "sql = " . $sql . "<br>";
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") 
                {
                    $errlog = "Agent->travaildimanche (AGENT) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    return FALSE;
                }
                // On vient de charger les périodes de l'agent => On initialise l'entrée du tableau à vide
                $lastperiod[$this->agentid] = array();
                // Pour toutes les lignes récupérées => On les charges dans le tableau
                while($result = mysqli_fetch_row($query))
                {
                    $lastperiod[$this->agentid][$result[2]]["datedebut"] = $this->fonctions->formatdatedb($result[0]);
                    $lastperiod[$this->agentid][$result[2]]["datefin"] = $this->fonctions->formatdatedb($result[1]);
                }
            }

            // On parcourt le tableau des périodes de l'agent
            foreach($lastperiod[$this->agentid] as $periode)
            {
                // Si la date courante est entre le début et la fin de la période => On a trouvé
                if ($periode["datedebut"] <= $datedb and $periode["datefin"] >= $datedb)
                {
                    return true;
                }
            }
            // On a parcouru toutes les périodes de l'agent => On n'a rien trouvé donc il ne travaille pas le DIMANCHE
            return false;
        }
        else // La date n'est pas un DIMANCHE => donc retourne FALSE
        {
            return false;
        }
    }
    
    /**
     *
     * @param boolean $includedeleg
     *            optional if true delegated agent is responsable.
     * @return boolean true if the current agent is responsable of a strucuture. false otherwise.
     */
    function estresponsable($includedeleg = true)
    {
        
        // On regarde si l'agent est un vrai responsable
        $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID= ? AND DATECLOTURE>=DATE(NOW())";
        $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estresponsable (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        $resp_return = mysqli_num_rows($query);
        // echo "resp_return = $resp_return <br>" ;
        
        $deleg_return = 0;
        if ($includedeleg) {
            $deleg_return = $this->estdelegue();
        }
        // echo "deleg_return = $deleg_return<br>";
        
        return ($resp_return + $deleg_return > 0);
    }

    /**
     *
     * @param
     * @return boolean true if the current agent is a delagated of a strucuture. false otherwise.
     */
    function estdelegue()
    {
        $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE IDDELEG= ? AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG";
        $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estdelegue : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        return (mysqli_num_rows($query) > 0);
    }

    /**
     *
     * @param
     * @return boolean true if the current agent is a manager of a strucuture. false otherwise.
     */
    function estgestionnaire()
    {
        $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID= ? AND DATECLOTURE>=DATE(NOW())";
        $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estgestionnaire (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        return (mysqli_num_rows($query) != 0);
    }

    /**
     *
     * @param
     * @return boolean true if the current agent is an administrator of the application. false otherwise.
     */
    function estadministrateur()
    {
        $complement = new complement($this->dbconnect);
        $complement->load($this->agentid, "ESTADMIN");
        return (strcasecmp($complement->valeur(), "O") == 0);
    }
    
    function estconsultant()
    {
        $dbconstante = 'FONCTIONAVIS';
        $avisfonction = 'n';
        if ($this->fonctions->testexistdbconstante($dbconstante)) { $avisfonction = $this->fonctions->liredbconstante($dbconstante); }
        if (!$this->fonctions->convertvaluetobool($avisfonction))
        {
            // la fonction est désactivée => On retourne systématiquement false
            return false;
        }


        $sql = "SELECT AGENTID FROM COMPLEMENT WHERE COMPLEMENTID = ? AND VALEUR = ?";
        $params = array($this->fonctions->my_real_escape_utf8(complement::AVIS_CONGES_LABEL), $this->fonctions->my_real_escape_utf8($this->agentid));
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "sql = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estconsultant (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        return (mysqli_num_rows($query) != 0);
    }
    

    /**
     *
     * @param string $typeprofil
     *            optional Type de profil RH demandé => 1 = RHCET, 2 = RHCONGE. Si null => tous les profils
     * @return boolean true if the current agent has the selected profil. false otherwise.
     */
    function estprofilrh($typeprofil = null)
    {

        if (is_null($typeprofil)) 
        {
            // On charge les type de profil RH et dès qu'on en trouve un à 'O' => On retourne TRUE
            // Si aucun n'est 'O' on retourne FALSE
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHCET);
            if (strcasecmp($complement->valeur(), "O") == 0)
            {
                return true;
            }
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHCONGE);
            if (strcasecmp($complement->valeur(), "O") == 0)
            {
                return true;
            }
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHTELETRAVAIL);
            if (strcasecmp($complement->valeur(), "O") == 0)
            {
                return true;
            }
            return false;
        } 
        elseif ($typeprofil == 1 or $typeprofil == agent::PROFIL_RHCET) 
        {
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHCET);
            return (strcasecmp($complement->valeur(), "O") == 0);
        } 
        elseif ($typeprofil == 2 or $typeprofil == agent::PROFIL_RHCONGE) 
        {
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHCONGE);
            return (strcasecmp($complement->valeur(), "O") == 0);
        } 
        elseif ($typeprofil == agent::PROFIL_RHTELETRAVAIL) 
        {
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, agent::PROFIL_RHTELETRAVAIL);
            return (strcasecmp($complement->valeur(), "O") == 0);
        } 
        else 
        {
            $errlog = "Agent->estprofilrh (AGENT) : Type de profil demandé inconnu (typeprofil = $typeprofil)";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
    }
    
    function enregistreprofilrh($arrayprofil = array())
    {
        if (is_array($arrayprofil))
        {
            $complement = new complement($this->dbconnect);
            $complement->delete($this->agentid,agent::PROFIL_RHCET);
            if (in_array(agent::PROFIL_RHCET,$arrayprofil))
            {
                $complement->agentid($this->agentid);
                $complement->complementid(agent::PROFIL_RHCET);
                $complement->valeur('O');
                $complement->store();
            }
            $complement = new complement($this->dbconnect);
            $complement->delete($this->agentid,agent::PROFIL_RHCONGE);
            if (in_array(agent::PROFIL_RHCONGE,$arrayprofil))
            {
                $complement->agentid($this->agentid);
                $complement->complementid(agent::PROFIL_RHCONGE);
                $complement->valeur('O');
                $complement->store();
            }
            $complement = new complement($this->dbconnect);
            $complement->delete($this->agentid,agent::PROFIL_RHTELETRAVAIL);
            if (in_array(agent::PROFIL_RHTELETRAVAIL,$arrayprofil))
            {
                $complement->agentid($this->agentid);
                $complement->complementid(agent::PROFIL_RHTELETRAVAIL);
                $complement->valeur('O');
                $complement->store();
            }
 
        }
    }
    

    /**
     *
     * @param string $nbrejrs
     *            optional Nombre de jours 'enfant malade' pour l'agent courant
     * @return string Nombre de jours 'enfant malade' si $nbrejrs est null. Pas de retour sinon
     */
    function nbjrsenfantmalade($nbrejrs = null)
    {
        $complement = new complement($this->dbconnect);
        if (is_null($nbrejrs)) {
            $complement->load($this->agentid, 'ENFANTMALADE');
            return intval($complement->valeur());
        } 
        elseif ((strcasecmp(intval($nbrejrs), $nbrejrs) == 0) and (intval($nbrejrs) >= 0)) // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
        {
            $complement->complementid('ENFANTMALADE');
            $complement->agentid($this->agentid);
            $complement->valeur(intval($nbrejrs));
            $complement->store();
        } 
        else 
        {
            $errlog = "Agent->nbjrsenfantmalade (AGENT) : Le nombre de jours 'enfant malade doit être un nombre positif ou nul'";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param
     * @return string Nombre de jours 'enfant malade' pris sur la période courante
     */
    function nbjrsenfantmaladeutilise($debut_interval, $fin_interval)
    {
        $sql = "SELECT SUM(DEMANDE.NBREJRSDEMANDE) 
                FROM DEMANDE
                WHERE DEMANDE.AGENTID= ?
                AND DEMANDE.TYPEABSENCEID='enmal'
                AND DEMANDE.DATEDEBUT>= ?
                AND DEMANDE.DATEFIN<= ?
                AND DEMANDE.STATUT='" . demande::DEMANDE_VALIDE . "'";
        
        $params = array($this->agentid,$this->fonctions->formatdatedb($debut_interval),$this->fonctions->formatdatedb($fin_interval));
        $query = $this->fonctions->prepared_select($sql, $params);
        
        // $this->fonctions->anneeref() . $this->fonctions->debutperiode()
        // ($this->fonctions->anneeref() +1) . $this->fonctions->finperiode()
        // echo "SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->nbjrsenfantmaladeutilise (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return NULL;
        }
        if (mysqli_num_rows($query) == 0)
            return 0;
        $result = mysqli_fetch_row($query);
        return (floatval($result[0]));
    }

    /**
     *
     * @param date $debut_interval
     *            beginning date of the planning
     * @param date $fin_interval
     *            ending date of the planning
     * @return object the planning object.
     */
    function planning($debut_interval, $fin_interval, $incudeteletravail = false, $includecongeabsence = true)
    {
        $planning = new planning($this->dbconnect);
        $planning->load($this->agentid, $debut_interval, $fin_interval, $incudeteletravail, $includecongeabsence);
        return $planning;
    }

    /**
     *
     * @param date $debut_interval
     *            beginning date of the planning
     * @param date $fin_interval
     *            ending date of the planning
     * @param boolean $clickable
     *            optional true means that the planning allow click on elements. false otherwise
     * @param boolean $showpdflink
     *            optional true means that a link to display planning in pdf format is allowed. false means the link is hidden
     * @return string the planning html text.
     */
    function planninghtml($debut_interval, $fin_interval, $clickable = FALSE, $showpdflink = TRUE, $incudeteletravail = FALSE, $includecongeabsence = true)
    {
        $planning = new planning($this->dbconnect);
        $htmltext = $planning->planninghtml($this->agentid, $debut_interval, $fin_interval, $clickable, $showpdflink, false, $incudeteletravail, $includecongeabsence);
        return $htmltext;
    }

    /**
     *
     * @param string $ics
     *            the ics string content
     * @param boolean $deleteics
     *            true if ics must be deleted from calendar
     * @return string empty string if ok, error description if ko
     */
    function updatecalendar($ics = null, $deleteics = false)
    {
        $errlog = "";
        if (! is_null($ics)) {
            // echo "ICS n'est pas nul...<br>";
            // echo "Agent = " . $this->identitecomplete() . '<br>';
            if (is_null($this->adressemail) or $this->adressemail == "") {
                $errlog = "Agent->updatecalendar (AGENT) : L'adresse mail de l'agent " . $this->identitecomplete() . " est vide ==> Impossible de mettre à jour l'agenda.";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $url = $this->fonctions->liredbconstante("URLCALENDAR");
                if (is_null($url) or $url == "") {
                    $errlog = "Agent->updatecalendar (AGENT) : L'URL de l'agenda est vide ==> Impossible de mettre à jour l'agenda.";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                } else {
                    $url = $url . "user=" . $this->adressemail;
                    // $errlog = "Agent->updatecalendar (AGENT) : URL = " . $url;
                    // error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
                    
                    // echo "URL = $url <br>";
                    $ch = curl_init($url);
                    
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $ics);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    $bearertext = '';
                    $dbconstante = "WSGROUPS_SECRET_TOKEN";
                    if ($this->fonctions->testexistdbconstante($dbconstante))
                    {
                        $accessToken = trim($this->fonctions->liredbconstante($dbconstante));
                        if (strlen($accessToken)>0)
                        {
                            ///////////////////////////////////////////////////////////
                            //// ATTENTION : TOKEN DE BYPASS A METTRE EN PARAMETRE DANS LE CONFIG
                            $bearertext = "Authorization: Bearer $accessToken";
                            ///////////////////////////////////////////////////////////
                        }
                    }
                    // Set HTTP Header for POST request
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/calendar', $bearertext));
    
                    if ($deleteics)
                    {
                       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    }
                    
                    // Submit the POST request
                    $result = "";
                    //error_log(basename(__FILE__)." Curl de MAJ du calendrier : ".$this->fonctions->stripAccents(var_export($ch,true)));
                    $result = curl_exec($ch);
                    $error = curl_error ($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ((int) $httpcode !== 200 and $error=="")
                    {
                        $error = "Code retour HTTP => $httpcode";
                    }
                    if (curl_errno($ch)) {
                        $curlerror = 'Curl error: ' . $error . ' URL = ' . $url;
                        $errlog = "Agent->updatecalendar (AGENT) : " . $curlerror;
                        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    }
                    // $errlog = "Agent->updatecalendar (AGENT) : Résultat = " . $result;
                    // error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
                    // echo "Résultat = " . $result . "<br>";
                    // Close cURL session handle
                    curl_close($ch);
                }
            }
        }
        return $errlog;
    }

    /**
     *
     * @param object $destinataire
     *            the mail recipient
     * @param string $objet
     *            the subject of the mail
     * @param string $message
     *            the body of the mail
     * @param string $piecejointe
     *            the name of the document to join to the mail
     * @param string $ics
     *            the ICS string to join to the mail
     * @param boolean $checkgrouper
     *            true => check group member / false => don't check
     * @return
     */
    function sendmail($destinataire = null, $objet = null, $message = null, $piecejointe = null, $ics = null, $checkgrouper = false)
    {
    	if ($checkgrouper && is_object($destinataire) && !$destinataire->isG2tUser())
    	{
    		// le destinataire ne fait pas partie des utilisateurs G2T
    		$errorlog = "sendmail annulé car expéditeur absent des utilisateurs G2T (".$destinataire->identitecomplete().") \n";
    		$errorlog .= "objet du mail : ".$objet."\n";
    		$errorlog .= "contenu du mail : ".$message."\n";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errorlog));
    	}
    	else
    	{
	        // ----------------------------------
	        // Construction de l'entête
	        // ----------------------------------
	        $boundary = "-----=" . md5(uniqid(rand()));
	        $header = "Reply-to: " . $this->adressemail . "\r\n";
	        // $header .= "From: " . $this->adressemail . "\r\n";
	        $preferences = array("input-charset" => "UTF-8", "output-charset" => "UTF-8");
	        
	        //$iconv = mb_strtoupper($this->fonctions->stripAccents("HÉLÈNE OU ÉLODIE"), 'ASCII');
	        $iconv = mb_strtoupper($this->fonctions->stripAccents($this->prenom() . " " . $this->nom()), 'ASCII');
	        $header .= "From: " . $iconv . " <" . $this->adressemail . ">\r\n";
	        
	        //$header .= "From: " . $this->prenom() . " " . $this->nom() . " <" . $this->adressemail . ">\r\n";
	
	        $encoded_subject = iconv_mime_encode("G2T", $objet, $preferences);
	        $encoded_subject = str_replace("G2T: ", "", "$encoded_subject");
	        //$header .= $encoded_subject . "\r\n";
	        
	        $header .= "MIME-Version: 1.0\r\n";
	        $header .= "Content-Type: multipart/mixed; charset=\"utf-8\"; boundary=\"$boundary\"\r\n";
	        $header .= "\r\n";
	        // --------------------------------------------------
	        // Construction du message proprement dit
	        // --------------------------------------------------
	        $msg= '';
	        
	        //$msg = "Subject: " . mb_convert_encoding($objet,'HTML') . "\r\n";
	        //$msg = "Subject: " . nl2br(htmlentities("$objet", ENT_QUOTES, "UTF-8", false)) . "\r\n";
	        //$msg = "Subject: " . $objet . "\r\n";
	        
	        
	        //$msg = $encoded_subject. "\r\n";
	        
	        // ---------------------------------
	        // 1ère partie du message
	        // Le texte
	        // ---------------------------------
	        
	        $msg .= "--$boundary\r\n";
	        $msg .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
	        // $msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
	        $msg .= "Content-Transfer-Encoding:8bit\r\n";
	        $msg .= "\r\n";
                if (is_object($destinataire))
                {
                    $msg .= "Bonjour " . mb_convert_case($destinataire->identitecomplete(), MB_CASE_TITLE) . ",<br><br>"; // $this->fonctions->utf8_encode(ucwords(mb_strtolower($destinataire->identitecomplete(),'UTF-8'))) . ",<br><br>";
                }
                else
                {
                    // $msg .= "Bonjour " . $this->fonctions->utf8_encode(ucwords(mb_strtolower($destinataire,'UTF-8'))) . ",<br><br>";
                    $msg .= "Bonjour,<br><br>";
                }
	        $msg .= str_replace("&#039;", "'", str_replace("&gt;", ">", str_replace("&lt;", "<", nl2br(htmlentities("$message", ENT_QUOTES, "UTF-8", false))))) . "<br>";
                
                // Si l'adresse est donnée directement, on ne met pas le footer dans le message. 
                // En effet, le destinataire n'est pas forcément un utilisateur G2T (impossible de contrôler)
                // => Pas de référence à l'application
                if (is_object($destinataire))
                {
                    $msg .= "Cliquez sur le lien <a href='" . preg_replace('/([^:])(\/{2,})/', '$1/', $this->fonctions->get_g2t_url()) . "'>G2T</a><br><br>Cordialement<br><br>";
                    // Si l'expéditeur n'est pas le CRON de G2T
                    if (strcasecmp($this->agentid(), SPECIAL_USER_IDCRONUSER)!=0)
                    {
                        $msg .= mb_convert_case($this->prenom . " " . $this->nom, MB_CASE_TITLE); // ucwords(mb_strtolower($this->prenom . " " . $this->nom),'UTF-8');
                    }
                    else
                    {
                        $msg .= "<p style='font-size: 0.75em;'>Ce message est envoyé automatiquement par l'application G2T.<br>";
                        $msg .= "Merci de ne pas répondre à cet e-mail.<br>La boîte aux lettres qui a généré cet e-mail ne traite pas les réponses.</p><br>";
                    }
                    $msg .= "\r\n";
                }

	        // $msg .= htmlentities("$message",ENT_IGNORE,"ISO8859-15") ."<br><br>Cordialement<br><br>" . ucwords(strtolower("$PRENOM $NOM")) ."\r\n";
	        $msg .= "\r\n";
	        
	        if (! is_null($ics)) {
	            // Si le fichier ics existe ==> On met à jour le calendrier de l'agent
	            $errormsg = $destinataire->updatecalendar($ics);
	            // Si tout c'est bien passé, pas la peine de joindre l'ICS....
	            // echo "Error Msg = XXX" .$errormsg . "XXX<br>";
	            
	            // if ($errormsg <> "")
	            // {
	            $msg .= "<br><br><p style='font-size: 0.75em;'>La pièce jointe est un fichier iCalendar contenant plus d'informations concernant l'événement.<br>Si votre client de courrier supporte les requêtes iTip vous pouvez utiliser ce fichier pour mettre à jour votre copie locale de l'événement.</p>";
	            $msg .= "\r\n";
	            $msg .= "--$boundary\r\n";
	            $msg .= "Content-Type: text/calendar;name=\"conge.ics\";method=REQUEST;charset=\"utf-8\"\n";
	            $msg .= "Content-Transfer-Encoding: 8bit\n\n";
	            $msg .= preg_replace("#UID:(.*)#", "UID:EXTERNAL-$1", $ics);
	            $msg .= "\r\n\r\n";
	            // }
	        }
	        $msg .= "\r\n";
	        
	        if (! is_null($piecejointe)) {
	            if (is_string($piecejointe)) {
	                // ---------------------------------
	                // 2nde partie du message
	                // Le fichier (inline)
	                // ---------------------------------
	                $file = "$piecejointe";
	                $basename = basename($file);
	                // echo "basename = " . $basename . "<br>";
	                $fp = fopen($file, "rb");
	                $attachment = fread($fp, filesize($file));
	                fclose($fp);
	                $attachment = chunk_split(base64_encode($attachment));
	                
	                $msg .= "--$boundary\r\n";
	                // $msg .= "Content-Type: application/pdf; name=\"$file\"\r\n";
	                $msg .= "Content-Type: application/pdf; name=\"$basename\"\r\n";
	                $msg .= "Content-Transfer-Encoding: base64\r\n";
	                // $msg .= "Content-Disposition: attachment; filename=\"$file\"\r\n";
	                $msg .= "Content-Disposition: attachment; filename=\"$basename\"\r\n";
	                $msg .= "\r\n";
	                $msg .= $attachment . "\r\n";
	                $msg .= "\r\n\r\n";
	            } else // C'est un tableau
	            {
	                foreach ($piecejointe as $file) {
	                    // $file = "$piecejointe";
	                    $basename = basename($file);
	                    // echo "basename = " . $basename . "<br>";
	                    // echo "File = $file <br>";
	                    $fp = fopen($file, "rb");
	                    $attachment = fread($fp, filesize($file));
	                    fclose($fp);
	                    $attachment = chunk_split(base64_encode($attachment));
	                    
	                    $msg .= "--$boundary\r\n";
	                    // $msg .= "Content-Type: application/pdf; name=\"$file\"\r\n";
	                    $msg .= "Content-Type: application/pdf; name=\"$basename\"\r\n";
	                    $msg .= "Content-Transfer-Encoding: base64\r\n";
	                    // $msg .= "Content-Disposition: attachment; filename=\"$file\"\r\n";
	                    $msg .= "Content-Disposition: attachment; filename=\"$basename\"\r\n";
	                    $msg .= "\r\n";
	                    $msg .= $attachment . "\r\n";
	                    $msg .= "\r\n\r\n";
	                }
	            }
	        }
	        $msg .= "--$boundary--\r\n\r\n";
	        
	        if (strcasecmp($this->fonctions->liredbconstante('MAINTENANCE'), 'n') != 0) 
	        {   // On est en mode maintenance ==> Pas d'envoi de mail
	            $errlog = "Le mode MAINTENANCE est activé. Il n'y a pas d'envoi de mail (destinataire : ";
                if (is_object($destinataire))
                {
                    $errlog = $errlog . $destinataire->identitecomplete();
                }
                else
                {
                    $errlog = $errlog . $destinataire;
                }
                $errlog = $errlog . ")";
                // echo "$errlog ";
                // global $uid;
                // if (isset($uid) and $uid<>"")
                // {
                //     echo "<br>";
                // }
                // else
                // {
                //     echo "\n";                        
                // }
	            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog) . "");
	        }
	        else
	        {
                    // ini_set(sendmail_from,$this->adressemail);
                    ini_set('sendmail_from', $this->prenom() . " " . $this->nom() . " <" . $this->adressemail . ">");
                    ini_set('SMTP', $this->fonctions->liredbconstante("SMTPSERVER"));
                    // $objet .=" G2T";
                    /*
                    $errorlog = "sendmail ok : Destinataire = ".$destinataire->identitecomplete()." (mail = " . $destinataire->mail() . ")\n";
                    $errorlog .= "Expéditeur : " . $this->identitecomplete() .  " (mail = "   . $this->mail() . ") \n";
                    $errorlog .= "objet du mail : ".$objet."\n";
                    $errorlog .= "contenu du mail : ".$message."\n";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errorlog));
                    */
                    if (is_object($destinataire))
                    {
                        mail($destinataire->prenom() . " " . $destinataire->nom() . " <" . $destinataire->mail() . ">", "$encoded_subject", "$msg", "$header");
                    }
                    else
                    {
                        mail($destinataire . " <" . $destinataire . ">", "$encoded_subject", "$msg", "$header");
                    }
                        //  mail($destinataire->prenom() . " " . $destinataire->nom() . " <" . $destinataire->mail() . ">", "$objet", "$msg", "$header");
                        // mail($destinataire->prenom() . " " . $destinataire->nom() . " <" .$destinataire->mail() . ">", $this->fonctions->utf8_encode("$objet"), "$msg", "$header");
                    ini_restore('sendmail_from');
                    // On fait une pause de 1 sec pour eviter de se faire jeter par le serveur SMTP
                    if (defined('TYPE_ENVIRONNEMENT'))
                    {
                        if (strcasecmp(TYPE_ENVIRONNEMENT,'PROD')!=0)
                        {
                            // error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("Environnement de test/dev => On sleep après l'envoi du mail \n"));
                            sleep(2);
                        }
                    }
	        }
    	}

    }

    /**
     *
     * @param date $datedebut
     *            the beginning date of the interval to search affectations
     * @param date $datefin
     *            the ending date of the interval to search affectations
     * @param boolean $ignoremissingstruct
     *            allow the structure to be empty for affectation
     * @return array list of objects affectation
     */
    function affectationliste($datedebut, $datefin, $ignoremissingstruct  = false)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        
        $ignoremissingstruct  = true;
        $affectationliste = null;
        $sql = "SELECT SUBREQ.AFFECTATIONID FROM ((SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,AGENTID FROM AFFECTATION WHERE AGENTID = ? AND DATEDEBUT<= ? AND (? <=DATEFIN OR DATEFIN='0000-00-00'))";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,AGENTID FROM AFFECTATION WHERE AGENTID= ? AND DATEDEBUT>= ? AND ? >=DATEDEBUT)";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,AGENTID FROM AFFECTATION WHERE AGENTID= ? AND DATEFIN>= ? AND (? >=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
        $sql = $sql . ", AGENT ";
        $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N' ";
        $sql = $sql . "   AND AGENT.AGENTID = SUBREQ.AGENTID ";
        $sql = $sql . " ORDER BY SUBREQ.DATEDEBUT";
        
        
        $params = array($this->agentid,$datedebut, $datefin,
            $this->agentid,$datedebut, $datefin,
            $this->agentid,$datedebut, $datefin);
        $query = $this->fonctions->prepared_select($sql, $params);
        
        //echo "sql = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->affectationliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->affectationliste : L'agent $this->agentid n'a pas d'affectation entre $datedebut et $datefin <br>";
        }
        while ($result = mysqli_fetch_row($query)) {
            $affectation = new affectation($this->dbconnect);
            // echo "result[0] = $result[0] <br>";
            $affectation->load("$result[0]",$ignoremissingstruct);
            $affectationliste[$affectation->affectationid()] = $affectation;
            unset($affectation);
        }
        // print_r ($affectationliste) ; echo "<br>";
        return $affectationliste;
    }

    /**
     *
     * @param date $datedebut
     *            the beginning date to check
     * @param date $datefin
     *            the ending date to check
     * @return boolean true if the declaration of agent is correct. false otherwise
     */
    function dossiercomplet($datedebut, $datefin)
    {
        // Un dossier est complet si
        // - Il a une affectation durant toute la période
        // - Il a une déclaration de TP (validée) sur toute la période
        // => On charge le planning de l'agent pour la période
        // => On parcours le planning pour vérifier
        $planning = new planning($this->dbconnect);
        $planning->load($this->agentid, $datedebut, $datefin);
        if (! is_null($planning)) {
            // pour tous les elements du planning on vérifie...
            $listeelement = $planning->planning();
            foreach ($listeelement as $key => $element) {
                if (strcasecmp($element->type(), "nondec") == 0) {
                    // echo "Le premier element non declaré est : " . $key . "<br>";
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @param boolean $includedeleg
     *            optional if true delegated agent get responsable structure list.
     * @return array list of objects structure where the agent is responsable
     */
    function structrespliste($includedeleg = true)
    {
        $structliste = null;
        if ($this->estresponsable()) {
            // echo "Je suis responsable...<br>";
            $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID = ? AND DATECLOTURE>=DATE(NOW())";
            $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
            $query = $this->fonctions->prepared_select($sql, $params);
            // echo "sql = " . $sql . "<br>";
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Agent->structrespliste (RESPONSABLE) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            while ($result = mysqli_fetch_row($query)) {
                // On charge la structure
                $struct = new structure($this->dbconnect);
                $struct->load("$result[0]");
                $structliste[$struct->id()] = $struct;
                unset($struct);
            }
            
            if ($includedeleg) {
                $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE IDDELEG= ? AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG";
                $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
                $query = $this->fonctions->prepared_select($sql, $params);
                // echo "sql = " . $sql . "<br>";
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Agent->structrespliste (DELEGUE) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                while ($result = mysqli_fetch_row($query)) {
                    // On charge la structure
                    $struct = new structure($this->dbconnect);
                    $struct->load("$result[0]");
                    $structliste[$struct->id()] = $struct;
                    unset($struct);
                }
            }
        }
        
        return $structliste;
    }

    /**
     *
     * @param
     * @return array list of objects structure where the agent is manager
     */
    function structgestliste()
    {
        $structliste = null;
        if ($this->estgestionnaire()) {
            // echo "Je suis gestionnaire...<br>";
            $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID = ? AND DATECLOTURE>=DATE(NOW())";
            $params = array($this->fonctions->my_real_escape_utf8($this->agentid));
            $query = $this->fonctions->prepared_select($sql, $params);
            // echo "sql = " . $sql . "<br>";
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Agent->structgestliste : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            while ($result = mysqli_fetch_row($query)) {
                // echo "Je charge la structure " . $result[0] . " <br>";
                $struct = new structure($this->dbconnect);
                $struct->load("$result[0]");
                $structliste[$struct->id()] = $struct;
                unset($struct);
            }
        }
        return $structliste;
    }

    function agentconsultantliste() :array
    {
        $agentliste = array();
        if ($this->estconsultant()) {
            // echo "Je suis consultant...<br>";
            $sql = "SELECT AGENTID FROM COMPLEMENT WHERE COMPLEMENTID = ? AND VALEUR = ?";
            $params = array($this->fonctions->my_real_escape_utf8(complement::AVIS_CONGES_LABEL), $this->fonctions->my_real_escape_utf8($this->agentid));
            $query = $this->fonctions->prepared_select($sql, $params);
            // echo "sql = " . $sql . "<br>";
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Agent->agentconsultantliste (AGENT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            while ($result = mysqli_fetch_row($query)) {
                // echo "Je charge la structure " . $result[0] . " <br>";
                $agent = new agent($this->dbconnect);
                $agent->load("$result[0]");
                $agentliste[$agent->agentid()] = $agent;
                unset($agent);
            }
        }
        return $agentliste;
    }

    /**
     *
     * @param
     * @return Liste des structures où l'agent est membre du circuit de validation comme gestionnaire
     */
    function structgestcongeliste()
    {
        $structliste = array();
        if ($this->estgestionnaire()) 
        {
            //echo "Je suis gestionnaire...<br>";
            // Liste des structures où je suis gestionnaire
            $structgestliste = $this->structgestliste();
            if (is_array($structgestliste))
            {
                uasort($structgestliste,"triparprofondeurabsolue");
            }
            //echo "<br>structgestliste = "; print_r((array) $structgestliste) ; echo "<br>";
            //var_dump('Liste des structures où je suis gestionnaire : '); foreach((array)$structgestliste as $tmpstruct) { var_dump(__METHOD__ . ' ' . $tmpstruct->id() . ' ' . $tmpstruct->nomcourt()); }

            foreach ((array) $structgestliste as $structid => $structure) 
            {
                // Si le signataire des demandes des agents ou du responsable de la structure courante est le gestionnaire
                
                // Si le signataire des congés des agents est le gestionnaire
                $codeinterne = null;
                $agent = $structure->agent_envoyer_a($codeinterne);
                if (! is_null($agent) and $codeinterne==structure::MAIL_AGENT_ENVOI_GEST_COURANT)
                {
                    if ($agent->agentid() == $this->agentid)
                    {
                        $structliste[$structure->id()] = $structure;
                    }
                }
                // Si le signataire des congés du responsable est le gestionnaire
                $codeinterne = null;
                $agent = $structure->resp_envoyer_a($codeinterne);
                if (! is_null($agent) and $codeinterne==structure::MAIL_RESP_ENVOI_GEST_COURANT) 
                {
                    if ($agent->agentid() == $this->agentid) 
                    {
                        $structliste[$structure->id()] = $structure;
                    }
                }
                // Pour chaque structure fille, on regarde si le gestionnaire gère les demandes du responsable
                $structfilleliste = $structure->structurefille();
                foreach ((array) $structfilleliste as $structfilleid => $structfille) 
                {
                    // Si la structure est encore ouverte...
                    if ($this->fonctions->formatdatedb($structfille->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) 
                    {
                        $codeinterne = null;
                        $agent = $structfille->resp_envoyer_a($codeinterne);
                        if (! is_null($agent) and $codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT) 
                        {
                            if ($agent->agentid() == $this->agentid) 
                            {
                                $structliste[$structfilleid] = $structfille;
                            }
                        }
                    }
                }
            }
        }
        //var_dump('Liste des structures où je gère les congés (comme gestionnaire) : '); foreach((array)$structliste as $tmpstruct) { var_dump(__METHOD__ . ' ' . $tmpstruct->id() . ' ' . $tmpstruct->nomcourt()); }
        return $structliste;
    }

    /**
     *
     * @param string $anneeref
     *            optional year of reference (2012 => 2012/2013, 2013 => 2013/2014). If not set, the current year is used
     * @param string $erreurmsg
     *            concat the errors text with an existing string
     * @return array list of objects solde
     */
    function soldecongesliste($anneeref = null, &$erreurmsg = "", $includereport = false)
    {
        $soldeliste = null;
        if (is_null($anneeref)) {
            $anneeref = date("Y");
            $errlog = "Agent->soldecongesliste : L'année de référence est NULL ==> On fixe à l'année courante !!!! ATTENTION DANGER !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $erreurmsg = $erreurmsg . $errlog . "<br/>";
        }
        
        /*
         * if ($anneeref == $this->fonctions->anneeref())
         * {
         * if (is_null($this->dossieractif()))
         * return null;
         *
         * }
         */
        if (date("m") >= substr($this->fonctions->debutperiode(), 0, 2)) {
            $annee_recouvr = date("Y") + 1;
        } else {
            $annee_recouvr = date("Y");
        }
        // echo "date (Ymd) = " . date("Ymd") . " <br>";
        // echo "date (md)= " . date("md") . " <br>";
        // echo "anneeref = " . $anneeref . "<br>";
        // echo "annee_recouvr = " . $annee_recouvr. "<br>";
        // echo "this->fonctions->debutperiode() = " . $this->fonctions->debutperiode() . "<br>";
        // echo "this->fonctions->liredbconstante(FIN_REPORT) = " . $this->fonctions->liredbconstante("FIN_REPORT") . "<br>";
        
        // $reportactif = ($this->fonctions->liredbconstante("REPORTACTIF") == 'O');
        // if ($reportactif) echo "ReportActif = true<br>"; else echo "ReportActif = false<br>";
        
        $complement = new complement($this->dbconnect);
        $complement->load($this->agentid, "REPORTACTIF");
        // Si le complement n'est pas initialisé (NULL ou "") alors on active le report
        if (strcasecmp($complement->valeur(), "O") == 0) // or strlen($complement->valeur()) == 0)
            $reportactif = true;
        else
            $reportactif = FALSE;
        
        $subparams = array();
        if ((date("Ymd") >= $anneeref . $this->fonctions->debutperiode() && (date("Ymd") <= $annee_recouvr . $this->fonctions->liredbconstante("FIN_REPORT") or $includereport)) && $reportactif) 
        {
            //requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE '" . recuperation::SUPP_ID . "%') AND (ANNEEREF= ? OR ANNEEREF= ?))";
            //$subparams = array($anneeref,($anneeref - 1));
            /////////////////////////////
            // On limite la prise des congés complémtaires à l'année de reférence
            // On n'applique plus le report de congés sur les congés complémentaires
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' AND (ANNEEREF= ? OR ANNEEREF= ?)) OR (SOLDE.TYPEABSENCEID LIKE '" . recuperation::SUPP_ID . "%' AND ANNEEREF= ?)) ";
            $subparams = array($anneeref,($anneeref - 1),$anneeref);
        } 
        else 
        {
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE '" . recuperation::SUPP_ID . "%') AND ANNEEREF= ?)";
            $subparams = array($anneeref);
        }
        
        $sql = "SELECT SOLDE.TYPEABSENCEID FROM SOLDE,TYPEABSENCE WHERE AGENTID= ? AND SOLDE.TYPEABSENCEID=TYPEABSENCE.TYPEABSENCEID  AND " . $requ_sel_typ_conge;
        // echo "sql = " . $sql . "<br>";
        $params = array_merge(array($this->agentid),$subparams);
        $query = $this->fonctions->prepared_select($sql, $params);
        
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->soldecongesliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->soldecongesliste : L'agent $this->agentid n'a pas de solde de congés pour l'année de référence $anneeref. <br>";
            $errlog = " L'agent " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . " n'a pas de solde de congés pour l'année de référence $anneeref";
            $erreurmsg = $erreurmsg . $errlog;
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        $soldetodisplay = true;
        // Si on est dans une structure partiel le solde annuel n'est pas affiché
        if (isset($GLOBALS["structurepartielle"]))
        {
            if ($GLOBALS["structurepartielle"] == true)
            {
                $soldetodisplay = false;
            }
        }
        
        if ($soldetodisplay == true)
        {
            while ($result = mysqli_fetch_row($query)) {
                $solde = new solde($this->dbconnect);
                $solde->load($this->agentid, "$result[0]");
                $soldeliste[$solde->typeabsenceid()] = $solde;
                unset($solde);
            }
        }
        
        $recup = new recuperation($this->dbconnect);
        $recup->load($this->agentid, $anneeref . $this->fonctions->debutperiode());
        $solde = $recup->getsolde();
        $soldeliste[$solde->typeabsenceid()] = $solde;

        // echo "Avant le new.. <br>";
        $cet = new cet($this->dbconnect);
        // echo "Avant le load du CET <br>";
        $erreur = $cet->load($this->agentid);
        // echo "Erreur = " . $erreur . "<br>";
        if ($erreur == "") {
            // echo "Avant la comparaison date <br>";
            // echo "cet->datedebut() = " . $cet->datedebut() . "<br>";
            // echo "formatdatedb(cet->datedebut()) = " . $this->fonctions->formatdatedb($cet->datedebut()) . "<br>";
            // echo "this->fonctions->anneeref() = " . $this->fonctions->anneeref() . "<br>";
            // echo "anneeref+1 = " . ($anneeref+1) . "<br>";
            // echo "this->fontions->finperiode() = " . $this->fonctions->finperiode() . "<br>";
            if ($this->fonctions->formatdatedb($cet->datedebut()) <= ($anneeref + 1) . $this->fonctions->finperiode()) {
                $solde = new solde($this->dbconnect);
                // echo "Avant le load du solde <br>";
                $solde->load($this->agentid, $cet->idtotal());
                $soldeliste[$solde->typeabsenceid()] = $solde;
                unset($solde);
            }
        }

        return $soldeliste;
    }

    /**
     *
     * @param
     *            sting year of reference (2012 => 2012/2013, 2013 => 2013/2014)
     * @param boolean $infoagent
     *            optional display header of solde array if set to TRUE.
     * @param object $pdf
     *            optional pdf object representing the pdf file. if set, the array is append to the existing pdf. If not set a new pdf file is created
     * @param boolean $header
     *            optional if set to true, the header of the array if inserted in the pdf file. no header set in pdf file otherwise
     * @return
     */
    function soldecongespdf($anneeref, $infoagent = FALSE, $pdf = NULL, $header = TRUE)
    {

        $closeafter = FALSE;
        if (is_null($pdf)) {
            $pdf=new FPDF();
            //$pdf = new TCPDF();
            //define('FPDF_FONTPATH','font/');
            //$pdf->Open();
            //$pdf->SetHeaderData('', 0, '', '', array(
            //    0,
            //    0,
            //    0
            //), array(
            //    255,
            //    255,
            //    255
            //));
            $closeafter = TRUE;
        }
        // echo "Apres le addpage <br>";
        if ($header == TRUE) {
            $pdf->AddPage('L');
            //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 10, 5, 60, 20);
            $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
            $pdf->Ln(15);
            
            $old_structid = "";
            
            /*
             * $affectationliste = $this->affectationliste($this->fonctions->formatdate($anneeref . $this->fonctions->debutperiode()),$this->fonctions->formatdate(($anneeref+1) . $this->fonctions->finperiode()));
             *
             * foreach ((array)$affectationliste as $key => $affectation)
             * {
             * if ($old_structid != $affectation->structureid())
             * {
             * $structure = new structure($this->dbconnect);
             * $structure->load($affectation->structureid());
             * $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() .")";
             * $pdf->Cell(60,10,'Service : '. $nomstructure);
             * $pdf->Ln();
             * $old_structid = $affectation->structureid();
             * }
             * }
             */
            $affectationliste = $this->affectationliste(date('d/m/Y'), date('d/m/Y')); // On récupère l'affectation de l'agent à la date du jour
            if (is_array($affectationliste)) {
                // echo "affectationliste = " . print_r($affectationliste, true) . "<br>";
                $affectation = reset($affectationliste); // ATTENTION : Reset permet de récupérer le premier élément du tableau => On ne connait pas la clé
                $structure = new structure($this->dbconnect);
                $structure->load($affectation->structureid());
                $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
                $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $nomstructure));
            }
            
            // $pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('.$this->structure()->nomcourt() . ')' );
            $pdf->Ln(5);
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Historique des demandes de  : ' . $this->civilite() . " " . $this->nom() . " " . $this->prenom()));
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Edité le ' . date("d/m/Y")));
        }
        $pdf->SetFont('helvetica', '', 6, '', true);
        $pdf->Ln(10);
        
        if (! $infoagent) {
            $headertext = "Etat des soldes pour l'année $anneeref / " . ($anneeref + 1) . " du " . $this->fonctions->formatdate($anneeref . $this->fonctions->debutperiode()) . " au ";
            if (date("Ymd") > ($anneeref + 1) . $this->fonctions->finperiode())
                $headertext = $headertext . $this->fonctions->formatdate(($anneeref + 1) . $this->fonctions->finperiode());
            else
                $headertext = $headertext . date("d/m/Y");
                $pdf->Cell(215, 5, $this->fonctions->utf8_decode($headertext), 1, 0, 'C');
        } else
            $pdf->Cell(215, 5, $this->fonctions->utf8_decode("Etat des soldes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom()), 1, 0, 'C');
        $pdf->Ln(5);
        $pdf->Cell(75, 5, $this->fonctions->utf8_decode("Type de demande"), 1, 0, 'C');
        $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Droits acquis"), 1, 0, 'C');
        $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Droit pris"), 1, 0, 'C');
        $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Solde actuel"), 1, 0, 'C');
        $pdf->Cell(50, 5, $this->fonctions->utf8_decode("Demandes en attente"), 1, 0, 'C');
        $pdf->Ln(5);
        
        $totaldroitaquis = 0;
        $totaldroitpris = 0;
        $totaldroitrestant = 0;
        $totaldemandeattente = 0;
        $soldeliste = $this->soldecongesliste($anneeref);
        foreach ((array) $soldeliste as $key => $tempsolde) 
        {
            if ($tempsolde->droitaquis()>0)
            {
                $pdf->Cell(75, 5, $this->fonctions->utf8_decode($tempsolde->typelibelle()), 1, 0, 'C');
                if (strcmp($tempsolde->typeabsenceid(), 'cet') == 0) // Si c'est un CET, on n'affiche pas le droits acquis
                {
                    $textdroitaquis = "";
                }
                else
                {
                    $textdroitaquis = $tempsolde->droitaquis() . "";
                    if (strcmp(substr($tempsolde->typeabsenceid(), 0, 3), 'ann') == 0) // Si c'est un congé annuel
                    {
                        if ($demande = $this->aunedemandecongesbonifies('20' . substr($tempsolde->typeabsenceid(), 3, 2))) // On regarde si il y a une demande de congés bonifiés
                            $textdroitaquis = $textdroitaquis . " (C. BONIF.)";
                    }
                }
                $pdf->Cell(30, 5, $this->fonctions->utf8_decode($textdroitaquis), 1, 0, 'C');
                if (strcmp($tempsolde->typeabsenceid(), 'cet') == 0) // Si c'est un CET, on n'affiche pas les droits pris
                {
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode(""), 1, 0, 'C');
                }
                else
                {
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode($tempsolde->droitpris() . ""), 1, 0, 'C');
                }
                $pdf->Cell(30, 5, $this->fonctions->utf8_decode($tempsolde->solde() . ""), 1, 0, 'C');
                $pdf->Cell(50, 5, $this->fonctions->utf8_decode($tempsolde->demandeenattente() . ""), 1, 0, 'C');
                $totaldroitaquis = $totaldroitaquis + $tempsolde->droitaquis();
                $totaldroitpris = $totaldroitpris + $tempsolde->droitpris();
                $totaldroitrestant = $totaldroitrestant + $tempsolde->solde();
                $totaldemandeattente = $totaldemandeattente + $tempsolde->demandeenattente();
                $pdf->Ln(5);
            }
        }
        /*
         * $pdf->Cell(75,5,"Total",1,0,'C');
         * $pdf->Cell(30,5,$totaldroitaquis . "",1,0,'C');
         * $pdf->Cell(30,5,$totaldroitpris . "",1,0,'C');
         * $pdf->Cell(30,5,$totaldroitrestant . "",1,0,'C');
         * $pdf->Cell(50,5,$totaldemandeattente . "",1,0,'C');
         */
        // $pdf->Ln(8);
        $pdf->Cell(8, 5, $this->fonctions->utf8_decode("Soldes de congés donnés sous réserve du respect des règles de gestion"));
        $pdf->Ln(8);
        // ob_end_clean();
        if ($closeafter == TRUE)
            $pdf->Output("","solde_congés.pdf");
    }

    /**
     *
     * @param
     *            sting year of reference (2012 => 2012/2013, 2013 => 2013/2014)
     * @param boolean $infoagent
     *            optional display header of solde array if set to TRUE.
     * @return string the html text of the array
     */
    function soldecongeshtml($anneeref, $infoagent = FALSE)
    {
        // echo "anneeref = " . $anneeref . "<br>";
        $htmltext = "<br>";
        $htmltext = $htmltext . "<div id='soldeconges'>";
        $htmltext = $htmltext . "      <center>";
        $htmltext = $htmltext . "      <table class='tableau'>";
        if (! $infoagent)
            $htmltext = $htmltext . "      <tr class='titre'><td colspan=5>Etat des soldes pour l'année $anneeref / " . ($anneeref + 1) . "</td></tr>";
        else
            $htmltext = $htmltext . "      <tr class='titre'><td colspan=5>Etat des soldes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";
        
        $htmltext = $htmltext . "         <tr class='entete'><td>Type de demande</td><td>Droits acquis</td><td>Droit pris</td><td>Solde actuel</td><td>Demandes en attente</td></tr>";
        $totaldroitaquis = 0;
        $totaldroitpris = 0;
        $totaldroitrestant = 0;
        $totaldemandeattente = 0;
        // echo "soldecongeshtml => Avant solde Liste...<br>";
        $soldecongesliste = $this->soldecongesliste($anneeref);
        // echo "soldecongeshtml => Apres solde Liste...<br>";
        
        if (! is_null($soldecongesliste)) {
            foreach ($soldecongesliste as $key => $tempsolde) {
                if ($tempsolde->typeabsenceid()==recuperation::RECUP_ID)
                {
                    continue;
                }
                if ($tempsolde->droitaquis()==0)
                {
                    continue;
                }
                $htmltext = $htmltext . "      <tr class='element'>";
                $htmltext = $htmltext . "         <td>" . $tempsolde->typelibelle() . "</td>";
                if (strcmp($tempsolde->typeabsenceid(), 'cet') == 0) // Si c'est un CET, on n'affiche pas le droits acquis
                {
                    $htmltext = $htmltext . "         <td colspan='2' bgcolor='#E8E8E8' >"; // On fusionne les 2 colonnes "droit acquis" et "droit pris"
                }
                else
                {
                    $htmltext = $htmltext . "         <td>" . $tempsolde->droitaquis();
                    if (strcmp(substr($tempsolde->typeabsenceid(), 0, 3), 'ann') == 0) // Si c'est un congé annuel
                    {
                        if ($demande = $this->aunedemandecongesbonifies('20' . substr($tempsolde->typeabsenceid(), 3, 2))) // On regarde si il y a une demande de congés bonifiés
                            $htmltext = $htmltext . " (C. BONIF.)";
                    }
                }
                $htmltext = $htmltext . "             </td>";
                if (strcmp($tempsolde->typeabsenceid(), 'cet') == 0) // Si c'est un CET, on n'affiche pas les droits pris
                {
                    //$htmltext = $htmltext . "         <td></td>";
                    $htmltext = $htmltext . "";  // On a déjà fusionné les deux colonnes "droit acquis" et "droit pris" (colspan='2')
                }
                else
                {
                    $htmltext = $htmltext . "         <td>" . $tempsolde->droitpris() . "</td>";
                }
                $htmltext = $htmltext . "         <td>" . $tempsolde->solde() . "</td>";
                $htmltext = $htmltext . "         <td>" . $tempsolde->demandeenattente() . "</td>";
                $htmltext = $htmltext . "      </tr>";
                $totaldroitaquis = $totaldroitaquis + $tempsolde->droitaquis();
                $totaldroitpris = $totaldroitpris + $tempsolde->droitpris();
                $totaldroitrestant = $totaldroitrestant + $tempsolde->solde();
                $totaldemandeattente = $totaldemandeattente + $tempsolde->demandeenattente();
            }
        }
        /*
         * $htmltext = $htmltext . " <tr class='element'>";
         * $htmltext = $htmltext . " <td>Total</td>";
         * $htmltext = $htmltext . " <td>". $totaldroitaquis ."</td>"; //number_format($totaldroitaquis,1) ."</td>";
         * $htmltext = $htmltext . " <td>". $totaldroitpris ."</td>"; //number_format($totaldroitpris,1) ."</td>";
         * $htmltext = $htmltext . " <td>". $totaldroitrestant ."</td>"; //number_format($totaldroitrestant,1) ."</td>";
         * $htmltext = $htmltext . " <td>". $totaldemandeattente ."</td>";
         * $htmltext = $htmltext . " </tr>";
         */
        $htmltext = $htmltext . "      </table>";
        $htmltext = $htmltext . "<div class='reglegestiontextcolor'>Soldes de congés donnés sous réserve du respect des règles de gestion</div>";
        $htmltext = $htmltext . "      </center>";
        $htmltext = $htmltext . "</div>";
        $htmltext = $htmltext . "<br>";
        
        return $htmltext;
    }

    /**
     *
     * @param date $datedebut
     *            date of the beginning of the interval
     * @param date $datefin
     *            date of the ending of the interval
     * @return array list of query objects
     */
    function demandesliste($datedebut, $datefin)
    {
        $debut_interval = $this->fonctions->formatdatedb($datedebut);
        $fin_interval = $this->fonctions->formatdatedb($datefin);
        $demande_liste = array();
        
        $sql = "SELECT DISTINCT DEMANDE.DEMANDEID, DEMANDE.DATEDEBUT
				FROM DEMANDE 
				WHERE DEMANDE.AGENTID = ?
			       AND ((DEMANDE.DATEDEBUT <= ? AND DEMANDE.DATEFIN >= ? )
						OR (DEMANDE.DATEFIN >= ? AND DEMANDE.DATEDEBUT <= ? )
						OR (DEMANDE.DATEDEBUT >= ? AND DEMANDE.DATEFIN <= ? ))
				ORDER BY DEMANDE.DATEDEBUT";

        $params = array($this->agentid,$debut_interval,$debut_interval,$fin_interval,$fin_interval,$debut_interval,$fin_interval);
        $query = $this->fonctions->prepared_select($sql, $params);
        // echo "Agent->demandesliste SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->demandesliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->demandesliste : Il n'y a pas de demande de congé/absence pour cet agent " . $this->agentid() . " dans l'interval de temps " . $this->fonctions->formatdate($debut_interval) . " -> " . $this->fonctions->formatdate($debut_interval) . "<br>";
        }
        while ($result = mysqli_fetch_row($query)) {
            $demande = new demande($this->dbconnect);
            // echo "Agent->demandesliste : Avant le load " . $result[0] . "<br>";
            $demande->load("$result[0]");
            // echo "Agent->demandesliste : Apres le load <br>";
            $demande_liste[$demande->id()] = $demande;
            unset($demande);
        }
        // echo "declarationTP->demandesliste : demande_liste = "; print_r($demande_liste); echo "<br>";
        return $demande_liste;
    }

    /**
     *
     * @param date $datedebut
     *            date of the beginning of the interval
     * @param date $datefin
     *            date of the ending of the interval
     * @param string $structureid
     *            optional the structure identifier
     * @param boolean $showlink
     *            optional if true, display link to display array in pdf format. hide link otherwise
     * @return string the html text of the array
     */
    function demandeslistehtml($datedebut, $datefin, $structureid = null, $showlink = true)
    {
        $demandeliste = null;
        $synthesetab = array();
        /*
         * $affectationliste = $this->affectationliste($datedebut, $datefin);
         * $affectation = new affectation($this->dbconnect);
         * $declarationTP = new declarationTP($this->dbconnect);
         * $demande = new demande($this->dbconnect);
         *
         *
         * if (!is_null($affectationliste))
         * {
         * foreach ($affectationliste as $key => $affectation)
         * {
         * //echo "<br><br>Affectation (". $affectation->affectationid() .") date debut = " . $affectation->datedebut() . " Date fin = " . $affectation->datefin() . "<br>";
         * unset($declarationTPliste);
         * $declarationTPliste = $affectation->declarationTPliste($datedebut, $datefin);
         * if (!is_null($declarationTPliste))
         * {
         * foreach ($declarationTPliste as $key => $declarationTP)
         * {
         * //echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ") Debut = " . $declarationTP->datedebut() . " Fin = " . $declarationTP->datefin() . "<br>";
         * //echo "<br>Liste = "; print_r($declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin())); echo "<br>";
         * $demandeliste = array_merge((array)$demandeliste,(array)$declarationTP->demandesliste($datedebut, $datefin));
         * }
         * }
         * }
         * }
         * //echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         * // On enlève les doublons des demandes !!!
         * $uniquedemandeliste = array();
         * if (is_array($demandeliste))
         * {
         * foreach ($demandeliste as $key => $demande)
         * {
         * $uniquedemandeliste[$demande->id()] = $demande;
         * }
         * $demandeliste = $uniquedemandeliste;
         * unset($uniquedemandeliste);
         * }
         * //echo "#######demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         */
        
        $demandeliste = $this->demandesliste($datedebut, $datefin);
        $htmltext = "<br>";
        $htmltext = $htmltext . "<div id='demandeliste'>";
        $htmltext = $htmltext . "<center><table class='tableau' >";
        if (count($demandeliste) == 0)
        {
            $htmltext = $htmltext . "   <tr class='titre'><td>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
        }
        else {
            $htmltext = $htmltext . "   <tr class='titre'><td colspan=7>Tableau récapitulatif des demandes</td></tr>";
            $htmltext = $htmltext . "   <tr class='entete'>"
                    . "<td>Type de demande</td>"
                    . "<td>Date de dépot</td>"
                    . "<td>Date de début</td>"
                    . "<td>Date de fin</td>"
                    . "<td>Nbr de jours</td>"
                    . "<td>Etat de la demande</td>"
                    . "<td>Motif (obligatoire si le congé est annulé)</td>"
                    . "</tr>";
            foreach ($demandeliste as $key => $demande) {
                //if ($demande->motifrefus() != "" or strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) != 0) {
                if ($demande->motifrefus() != "" or (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) != 0 and strcasecmp($demande->statut(), demande::DEMANDE_ANNULE) != 0)) {
                    $htmltext = $htmltext . "<tr class='element bulleinfo'>";
                    $libelledemande = $this->fonctions->tronque_chaine($demande->typelibelle(),40, true);
/*                  
                    if (strlen($libelledemande) > 40) {
                        $libelledemande = mb_substr($demande->typelibelle(), 0, 40, 'UTF-8') . "...";
                    }
*/                    
                    $datatitle = '';
                    if (strlen($demande->typelibelle()) != strlen($libelledemande)) 
                    {
                        $datatitle = " data-title=" . chr(34) . htmlentities($demande->typelibelle()) . chr(34);  
                    }
                    $htmltext = $htmltext . "<td  $datatitle >";
                    $htmltext = $htmltext . $libelledemande; 
                    $htmltext = $htmltext . "</td>";   
                    $htmltext = $htmltext . "<td>";
                    $htmltext = $htmltext . $demande->date_demande() . " " . $demande->heure_demande();
                    $htmltext = $htmltext . "</td>";   
                    $htmltext = $htmltext . "<td>";
                    $htmltext = $htmltext . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut());
                    $htmltext = $htmltext . "</td>";   
                    $htmltext = $htmltext . "<td>";
                    $htmltext = $htmltext . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin());
                    $htmltext = $htmltext . "</td>";   
                    $datatitle = '';
                    $datatitleindicator = '';
                    if (strlen($demande->commentaire()) != 0) 
                    {
                        $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($demande->commentaire(),60)) . chr(34);
                        // $datatitleindicator = " &#11127;";
                        // $datatitleindicator = " &#128196;";
                        $datatitleindicator = " &#128195; ";

                    }
                    $htmltext = $htmltext . "<td $datatitle >";
                    $htmltext = $htmltext . $demande->nbrejrsdemande() . $datatitleindicator;
                    $htmltext = $htmltext . "</td>";   
                    $htmltext = $htmltext . "<td>";
                    $fullpdffilename = '';
                    if ($demande->statut()==demande::DEMANDE_VALIDE)
                    {
                        //var_dump($this->fonctions->formatdatedb($demande->datestatut()));
                        $pdfdossierdate = date('Y-m', strtotime($this->fonctions->formatdatedb($demande->datestatut())));
                        $pdfpath = $this->fonctions->pdfpath() . '/' . $pdfdossierdate . '/';
                        $filelist = array();
                        //var_dump("$pdfpath");
                        if (file_exists("$pdfpath"))
                        {
                            $filelist = scandir("$pdfpath");
                        }
                        //var_dump($filelist);
                        $pdffilenamelist = preg_grep('/^demande_num' . $demande->id() . '_/i',$filelist);
                        if (count($pdffilenamelist)>0)
                        {
                            $fullpdffilename = $pdfpath . reset($pdffilenamelist);
                            $htmltext = $htmltext . "<form name='form_demandePDF_" . $demande->id() . "' id='form_demandePDF_" . $demande->id() . "' method='post' action='affiche_pdf.php' target='_blank'>";
                            $htmltext = $htmltext . "<input type='hidden' name='nomdemandepdf' value='$fullpdffilename' />";  // nom du PDF à afficher
                            $htmltext = $htmltext . "</form>";
                            $htmltext = $htmltext . "<a href='javascript:document.form_demandePDF_" . $demande->id() . ".submit();'>" . $this->fonctions->demandestatutlibelle($demande->statut()) . "</a>";

                        }
                    }
                    if ($fullpdffilename == '')
                    {
                        $htmltext = $htmltext . $this->fonctions->demandestatutlibelle($demande->statut());
                    }
                
                    $htmltext = $htmltext . "</td>";  
                    $datatitle = '';
                    if (strlen($demande->motifrefus()) != 0) 
                    {
                        $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($demande->motifrefus(),60)) . chr(34);  
                    }
                    $htmltext = $htmltext . "<td class='cellulemultiligne' $datatitle >";
                    $htmltext = $htmltext . htmlentities($this->fonctions->tronque_chaine($demande->motifrefus(),50));
                    $htmltext = $htmltext . "</td>";   
                    
/*                    
                    $htmltext = $htmltext . "   <td>";                   
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $libelledemande; 
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";               
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->date_demande() . " " . $demande->heure_demande();
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->nbrejrsdemande();
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . htmlentities($demande->commentaire()) . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $this->fonctions->demandestatutlibelle($demande->statut());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>" . htmlentities($demande->motifrefus()) . "</td>";
 */
                    $htmltext = $htmltext . "</tr>";
                }
            }
        }
        $htmltext = $htmltext . "</table></center>";
        $htmltext = $htmltext . "</div>";
        
        $planning = $this->planning($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin));
        
        //echo "<br><br>" . print_r($planning,true) . "<br><br>";
        
        foreach ($planning->planning() as $key => $element)
        {
            if (!in_array($element->type(), array("","nondec","WE","ferie","tppar", "harp")))
            {
                //echo "<br>Element Type = " . $element->type() . "<br>";
                if (isset($synthesetab[$element->info()]))
                {
                    $synthesetab[$element->info()] = $synthesetab[$element->info()] + 0.5;
                }
                else
                {
                    $synthesetab[$element->info()] = 0.5;
                }
            }
        }
        
        if (count($synthesetab) > 0) {
            $htmltext = $htmltext . "<br>";
            // $htmltext = $htmltext . print_r($synthesetab,true);
            $htmltext = $htmltext . "<div id='demandeliste'>";
            $htmltext = $htmltext . "<center><table class='tableau' >";
            $htmltext = $htmltext . "   <tr class='titre'><td colspan=2>Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " au " . $this->fonctions->formatdate($datefin) . "</td></tr>";
            $htmltext = $htmltext . "   <tr class='entete'><td>Type de demande</td><td>Droit pris</td></tr>";
            ksort($synthesetab);
            foreach ($synthesetab as $key => $nbrejrs) {
                $htmltext = $htmltext . "<tr class='element'>";
                $htmltext = $htmltext . "<td>" . $key . "</td>";
                $htmltext = $htmltext . "<td>" . $nbrejrs . "</td>";
                $htmltext = $htmltext . "</tr>";
            }
            $htmltext = $htmltext . "</table></center>";
            $htmltext = $htmltext . "</div>";
        }
        if ($showlink == TRUE) {
            // $htmltext = $htmltext . "<br>";
            $tempannee = substr($this->fonctions->formatdatedb($datedebut), 0, 4);
            $htmltext = $htmltext . "<form name='userlistedemandepdf_" . $this->agentid() . "_" . $structureid . "_" . $tempannee . "'  method='post' action='affiche_pdf.php' target='_blank'>";
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->agentid() . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='no'>";
            // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . $tempannee . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='typepdf' value='listedemande'>";
            $htmltext = $htmltext . "</form>";
            $htmltext = $htmltext . "<a href='javascript:document.userlistedemandepdf_" . $this->agentid() . "_" . $structureid . "_" . $tempannee . ".submit();'>Liste des demandes en PDF</a>";
            
            $htmltext = $htmltext . "<br>";
            // Année précédente
            $tempannee = substr($this->fonctions->formatdatedb($datedebut), 0, 4) - 1;
            $htmltext = $htmltext . "<form name='userlistedemandepdf_" . $this->agentid() . "_" . $structureid . "_" . $tempannee . "'  method='post' action='affiche_pdf.php' target='_blank'>";
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->agentid() . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='no'>";
            // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . $tempannee . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='typepdf' value='listedemande'>";
            $htmltext = $htmltext . "</form>";
            $htmltext = $htmltext . "<a href='javascript:document.userlistedemandepdf_" . $this->agentid() . "_" . $structureid . "_" . $tempannee . ".submit();'>Liste des demandes en PDF de l'année précédente</a>";
        }
        $htmltext = $htmltext . "<br><br>";
        return $htmltext;
    }

    /**
     *
     * @param date $datedebut
     *            date of the beginning of the interval
     * @param date $datefin
     *            date of the ending of the interval
     * @param object $pdf
     *            optional the pdf object. if $pdf is set, the array is append to the existing pdf. Otherwise, a new pdf file is created
     * @param boolean $header
     *            optional if set to true, the header of the array if inserted in the pdf file. no header set in pdf file otherwise
     * @return
     */
    function demandeslistepdf($datedebut, $datefin, $pdf = NULL, $header = TRUE)
    {
        $demandeliste = null;
        $synthesetab = array();
        
        /*
         * $affectationliste = $this->affectationliste($datedebut, $datefin);
         * $affectation = new affectation($this->dbconnect);
         * $declarationTP = new declarationTP($this->dbconnect);
         * $demande = new demande($this->dbconnect);
         * if (!is_null($affectationliste))
         * {
         * foreach ($affectationliste as $key => $affectation)
         * {
         * $declarationTPliste = $affectation->declarationTPliste($datedebut, $datefin);
         * if (!is_null($declarationTPliste))
         * {
         * foreach ($declarationTPliste as $key => $declarationTP)
         * {
         * $demandeliste = array_merge((array)$demandeliste,(array)$declarationTP->demandesliste($datedebut, $datefin));
         * }
         * }
         * }
         * }
         * // On enlève les doublons des demandes !!!
         * $uniquedemandeliste = array();
         * if (is_array($demandeliste))
         * {
         * foreach ($demandeliste as $key => $demande)
         * {
         * $uniquedemandeliste[$demande->id()] = $demande;
         * }
         * $demandeliste = $uniquedemandeliste;
         * unset($uniquedemandeliste);
         * }
         * //echo "#######demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         */
        
        $demandeliste = $this->demandesliste($datedebut, $datefin);
        $closeafter = FALSE;
        if (is_null($pdf)) {
            $pdf=new FPDF();
            //$pdf = new TCPDF();
            //define('FPDF_FONTPATH','font/');
            //$pdf->Open();
            //$pdf->SetHeaderData('', 0, '', '', array(
            //    0,
            //    0,
            //    0
            //), array(
            //    255,
            //    255,
            //    255
            //));
            $closeafter = TRUE;
        }
        if ($header == TRUE) {
            $pdf->AddPage('L');
            // echo "Apres le addpage <br>";
            //$pdf->SetHeaderData('', 0, '', '', array(
            //    0,
            //    0,
            //    0
            //), array(
            //    255,
            //    255,
            //    255
            //));
            //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 10, 5, 60, 20);
            $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
            $pdf->Ln(15);
            /*
             * foreach ($affectationliste as $key => $affectation)
             * {
             * $structure = new structure($this->dbconnect);
             * $structure->load($affectation->structureid());
             * $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() .")";
             * $pdf->Cell(60,10,'Service : '. $nomstructure);
             * $pdf->Ln();
             * }
             */
            $affectationliste = $this->affectationliste(date('d/m/Y'), date('d/m/Y')); // On récupère l'affectation courante
            if (is_array($affectationliste)) {
                // echo "affectationliste = " . print_r($affectationliste, true) . "<br>";
                $affectation = reset($affectationliste); // ATTENTION : Reset permet de récupérer le premier élément du tableau => On ne connait pas la clé
                $structure = new structure($this->dbconnect);
                $structure->load($affectation->structureid());
                $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
                $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $nomstructure));
                $pdf->Ln();
            }
            
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Historique des demandes de  : ' . $this->civilite() . " " . $this->nom() . " " . $this->prenom()));
            $pdf->Ln(5);
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode("Période du " . $this->fonctions->formatdate($datedebut) . " au " . $this->fonctions->formatdate($datefin)));
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 6, '', true);
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Edité le ' . date("d/m/Y")));
            $pdf->Ln(10);
        }
        $pdf->SetFont('helvetica', '', 6, '', true);
        
        $headertext = "Tableau récapitulatif des demandes - Congés pris entre " . $this->fonctions->formatdate($datedebut) . " et ";
        if (date("Ymd") > $datefin)
        {
            $headertext = $headertext . $this->fonctions->formatdate($datefin);
        }
        else
        {
            $headertext = $headertext . date("d/m/Y");
        }
        
            $pdf->Cell(275, 5, $this->fonctions->utf8_decode($headertext), 1, 0, 'C');
        $pdf->Ln(5);
        
        if (count($demandeliste) == 0)
        {
            $pdf->Cell(275, 5, $this->fonctions->utf8_decode("L'agent n'a aucun congé posé pour la période de référence en cours."), 1, 0, 'C');
        }
        else 
        {
            $pdf->Cell(60, 5, $this->fonctions->utf8_decode("Type de demande"), 1, 0, 'C');
            $pdf->Cell(25, 5, $this->fonctions->utf8_decode("Date de dépot"), 1, 0, 'C');
            $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Date de début"), 1, 0, 'C');
            $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Date de fin"), 1, 0, 'C');
            $pdf->Cell(20, 5, $this->fonctions->utf8_decode("Nbr de jours"), 1, 0, 'C');
            $pdf->Cell(30, 5, $this->fonctions->utf8_decode("Etat de la demande"), 1, 0, 'C');
            $pdf->Cell(80, 5, $this->fonctions->utf8_decode("Motif (obligatoire si le congé est annulé)"), 1, 0, 'C');
            $pdf->ln(5);
            foreach ($demandeliste as $key => $demande) {
                //if ($demande->motifrefus() != "" or strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) != 0) {
                if ($demande->motifrefus() != "" or (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) != 0 and strcasecmp($demande->statut(), demande::DEMANDE_ANNULE) != 0)) {
                    $libelledemande = $this->fonctions->tronque_chaine($demande->typelibelle(),40, true);
/*                    
                    $libelledemande = $demande->typelibelle();
                    if (strlen($libelledemande) > 40) 
                    {
                        $libelledemande = substr($demande->typelibelle(), 0, 40) . "...";
                    }
 */
                    $pdf->Cell(60, 5, $this->fonctions->utf8_decode($libelledemande), 1, 0, 'C');
                    $pdf->Cell(25, 5, $this->fonctions->utf8_decode($demande->date_demande()), 1, 0, 'C');
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode($demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut())), 1, 0, 'C');
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode($demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin())), 1, 0, 'C');
                    $pdf->Cell(20, 5, $this->fonctions->utf8_decode($demande->nbrejrsdemande()), 1, 0, 'C');
                    $pdf->Cell(30, 5, $this->fonctions->utf8_decode($this->fonctions->demandestatutlibelle($demande->statut())), 1, 0, 'C');
                    $pdf->Cell(80, 5, $this->fonctions->utf8_decode($demande->motifrefus()), 1, 0, 'C');
                    $pdf->ln(5);
                }
            }
        }
        
        $planning = $this->planning($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin));
        
        //echo "<br><br>" . print_r($planning,true) . "<br><br>";
        
        foreach ($planning->planning() as $key => $element)
        {
            if (!in_array($element->type(), array("","nondec","WE","ferie","tppar", "harp")))
            {
                //echo "<br>Element Type = " . $element->type() . "<br>";
                if (isset($synthesetab[$element->info()]))
                {
                   $synthesetab[$element->info()] = $synthesetab[$element->info()] + 0.5;
                }
                else
                {
                   $synthesetab[$element->info()] = 0.5;
                }
            }
        }
        
        if (count($synthesetab) > 0) {
//        if (count($demandeliste) > 0) {
            $pdf->Ln(8);
            $headertext = "Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " et ";
            if (date("Ymd") > $datefin)
            {
                $headertext = $headertext . $this->fonctions->formatdate($datefin);
            }
            else
            {
                $headertext = $headertext . date("d/m/Y");
            }
            $pdf->Cell(100, 5, $this->fonctions->utf8_decode($headertext), 1, 0, 'C');
            $pdf->Ln(5);
            $pdf->Cell(80, 5, $this->fonctions->utf8_decode("Type de demande"), 1, 0, 'C');
            $pdf->Cell(20, 5, $this->fonctions->utf8_decode("Droit pris"), 1, 0, 'C');
            $pdf->ln(5);
            ksort($synthesetab);
            foreach ($synthesetab as $key => $nbrejrs) {
                $libelledemande = $key;
                if (strlen($key) > 40) {
                    $libelledemande = $this->fonctions->tronque_chaine($key,40,true); // substr($key, 0, 40) . "...";
                }
                $pdf->Cell(80, 5, $this->fonctions->utf8_decode($libelledemande), 1, 0, 'C');
                $pdf->Cell(20, 5, $this->fonctions->utf8_decode($nbrejrs), 1, 0, 'C');
                $pdf->ln(5);
            }
        }
        
        $pdf->Ln(8);
        
        // ob_end_clean();
        if ($closeafter == TRUE) {
            ob_end_clean();
            $pdf->Output("","liste_demandes.pdf");
        }
    }

    /**
     *
     * @param date $debut_interval
     *            date of the beginning of the interval
     * @param date $fin_interval
     *            date of the ending of the interval
     * @param string $agentid
     *            optional deprecated parameter => not used in code
     * @param string $mode
     *            optional responsable mode or agent mode. default is agent
     * @param string $cleelement
     *            optional type de demande à gérer (cet, ann20, ....)
     * @return string the html text of the array
     */
    function demandeslistehtmlpourgestion($debut_interval, $fin_interval, $agentid = null, $mode = MODE_AGENT, $cleelement = null)
    {
        $longueurmaxmotif = $this->fonctions->logueurmaxcolonne('DEMANDE','MOTIFREFUS');

        $liste = null;
        $liste = $this->demandesliste($debut_interval, $fin_interval);
        $debut_interval = $this->fonctions->formatdatedb($debut_interval);
        $fin_interval = $this->fonctions->formatdatedb($fin_interval);
        
        $htmltext = "";
        $htmltext = $htmltext . "
<script>
const backcolormotif = (checkbox, checkid) =>
{
    const motifinput = document.getElementById('motif[' +  checkid + ']');
    modifymotif(motifinput,checkid);
}
            
const modifymotif = (motif, motifid) =>
{
    const checkbox = document.getElementById('cancel[' +  motifid + ']');
    //alert(checkbox.id);
    if (checkbox.checked)
    {
        motif.disabled = false;
        //alert ('checked');
        if (motif.value == '')
        {
            motif.classList.add('commentobligatoirebackground');
        }
        else
        {
            motif.classList.remove('commentobligatoirebackground');
        }
    }
    else
    {
        motif.disabled = true;
        //alert ('no checked');
        motif.classList.remove('commentobligatoirebackground');
    }
}
</script>";
        
        // $htmltext = "<br>";
        if (count($liste) == 0) {
            // $htmltext = $htmltext . " <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
            $htmltext = "";
            return $htmltext;
        } else {
            $premieredemande = TRUE;
            foreach ($liste as $key => $demande) 
            {
                // echo "demandeslistehtmlpourgestion => debut du for " . $demande->id() . "<br>";
                // if (($demande->statut() == "a" and $mode == MODE_AGENT) or ($demande->statut() == "v" and $mode == MODE_RESPONSABLE))
                if (((strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 or strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0) and strcasecmp($mode, MODE_AGENT) == 0) 
                  or (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_RESPONSABLE) == 0)) 
                {
                    if ($premieredemande) {
                        $htmltext = $htmltext . "<table id='tabledemande_" . $this->agentid() . "' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        if ($mode==MODE_AGENT)
                        {
                            $nbcolonne = 8;
                        }
                        else
                        {
                            $nbcolonne = 7;
                        }
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=$nbcolonne align=center >Gestion des demandes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";
/*
                        $htmltext = $htmltext . "   <tr align=center><td class='cellulesimple'>Date de demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Type de demande</td><td class='cellulesimple'>Nbre jours</td>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 and strcasecmp($mode, MODE_AGENT) == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Commentaire</td>";
                        $htmltext = $htmltext . "<td class='cellulesimple'>Annuler</td>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_RESPONSABLE) == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td>";
                        $htmltext = $htmltext . "</tr>";
*/                                
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple cursorpointer'>Date de demande <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Date de début <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Date de fin <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Type de demande <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Nbre jours <span class='sortindicator'> </span></th>";
                        if (strcasecmp($mode, MODE_AGENT) == 0)
                        {
                            $htmltext = $htmltext . "<th class='cellulesimple cursorpointer'>Statut<span class='sortindicator'> </span></th>";
                            $htmltext = $htmltext . "<th class='cellulesimple'>Commentaire</th>";
                        }
                        $htmltext = $htmltext . "<th class='cellulesimple'>Annuler</th>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_RESPONSABLE) == 0)
                        {
                            $htmltext = $htmltext . "<th class='cellulesimple'>Motif (obligatoire si le congé est annulé) - maximum $longueurmaxmotif caractères</th>";
                        }
                        $htmltext = $htmltext . "</tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premieredemande = FALSE;
                    }
                    
                    if (is_null($cleelement) or (strtoupper($demande->type())==strtoupper($cleelement)))
                    {
                        $htmltext = $htmltext . "<tr align=center >";
                        // $htmltext = $htmltext . " <td>" . $this->nom() . " " . $this->prenom() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'><time datetime='" . $this->fonctions->formatdatedb($demande->date_demande()) . "_" . str_replace(':','',$demande->heure_demande()) . "'>" . $demande->date_demande() . " " . $demande->heure_demande() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'><time datetime='" . $this->fonctions->formatdatedb($demande->datedebut()) . "_" . (($demande->moment_debut()==fonctions::MOMENT_MATIN)?'AM':'PM') . "'>" . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'><time datetime='" . $this->fonctions->formatdatedb($demande->datefin()) . "_" . (($demande->moment_fin()==fonctions::MOMENT_MATIN)?'AM':'PM') . "'>" . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
                        $datatitle = '';
                        $datatitleindicator = '';
                        $datatitletext  = '';
                        $extraclass = '';
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_RESPONSABLE) == 0)
                        {
                            $compldemande = new demandecomplement($this->dbconnect);
                            $compldemande->load($demande->id(), demandecomplement::PERIODE_OBLIG_AUTOMATIQUE);

                            if ($compldemande->demandeid() == $demande->id())
                            {
                                if (strlen($demande->commentaire()) != 0) 
                                {
                                    $datatitletext = $demande->commentaire();
                                    $datatitleindicator = " &#128195; ";
                                }
                            }
                            if ($this->fonctions->formatdatedb($demande->datemailannulation())>='19500101')
                            {
                                if (trim($datatitletext) != '') { $datatitletext = $datatitletext . chr(10) . chr(13); }
                                $datatitletext = $datatitletext . "Une demande d'annulation vous a été envoyée le " . $this->fonctions->formatdate($demande->datemailannulation()); 
                                $datatitleindicator = $datatitleindicator . " &#x2709; ";
                            }
                            if (trim($datatitletext)!= '')
                            {
                                $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($datatitletext,60)) . chr(34);  
                                $extraclass = ' cursorpointer ';
                            }
                        }
                        $htmltext = $htmltext . "<td class='cellulesimple cellulemultiligne $extraclass ' $datatitle >" . $demande->nbrejrsdemande() . " " . $datatitleindicator;
                        $htmltext = $htmltext . "</td>";
                        if (strcasecmp($mode, MODE_AGENT) == 0)
                        {
                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->demandestatutlibelle($demande->statut()) . "</td>";

                            $datatitle = '';
                            if (strlen($demande->commentaire()) != 0) 
                            {
                                $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($demande->commentaire(),60)) . chr(34);  
                            }
                            $htmltext = $htmltext . "<td class='cellulesimple cellulemultiligne' $datatitle >";
                            $htmltext = $htmltext . htmlentities($this->fonctions->tronque_chaine($demande->commentaire(),50));
                            $htmltext = $htmltext . "</td>";   
/*                            
                            $htmltext = $htmltext . "   <td class='cellulesimple cellulemultiligne'>" . $demande->commentaire() . "</td>";
*/                           
                        }
                        $spanend = '';
                        if ((strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_AGENT) == 0))
                        {
                            $disable = "";
                            $datetorepostmail = date('Y-m-d', strtotime($this->fonctions->formatdatedb($demande->datemailannulation()). ' + 7 days'));
                            //var_dump($datetorepostmail);
/***********************                            
                            if (isset($_POST["cancelbutton"]) and isset($_POST["cancelbutton"][$demande->id()]))
                            {
                                $disable = " disabled ";
                            }
 ************************/
                            if ($datetorepostmail > date('Y-m-d'))
                            {
                                $disable = " disabled ";
                                $datatitletxt = "Votre demande est validée et vous avez déjà solicité votre responsable.\nVous ne pourrez lui renvoyer un mail qu'à partir du " . $this->fonctions->formatdate($datetorepostmail)  . ".";                                
                            }
                            else
                            {
                                $datatitletxt = "Votre demande est validée.\nVous devez demander à votre responsable d'annuler votre demande.\nCette solicitation sera faite automatiquement par mail.";
                            }
                            $htmltext = $htmltext . "<td class='cellulesimple' "
                                . " data-title=" . chr(34) . $datatitletxt . chr(34) . ">"
                                . "<input type='submit' $disable name=cancelbutton[" . $demande->id() . "] id=cancelbutton[" . $demande->id() . "] class='cancelbutton g2tbouton g2tenvoibouton' value='Envoyer' onclick='if (this.tagname!=\"OK\") {click_element(\"cancelbutton[" . $demande->id() . "]\"); return false; }'";
                            //$spanend = "</span>";
                        }
                        elseif ((strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 and strcasecmp($mode, MODE_AGENT) == 0))
                        {
                            $htmltext = $htmltext . "<td class='cellulesimple' " 
                                . " data-title=" . chr(34) . "Votre demande n'est pas validée. Vous pouvez annuler votre demande." . chr(34) . ">"
                                . "<input type='submit' name=cancel[" . $demande->id() . "] id=cancel[" . $demande->id() . "] class='cancel g2tbouton g2tsupprbouton' value='Supprimer' onclick='if (this.tagname!=\"OK\") {click_element(\"cancel[" . $demande->id() . "]\"); return false; }'";
                        }
                        else // On est en mode responsable
                        {
                            $htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=cancel[" . $demande->id() . "] id=cancel[" . $demande->id() . "] value='yes' ";
                            $arraycancel = null;
                            if (isset($_POST["cancel"]))
                            {
                                $arraycancel = $_POST["cancel"];
                                if (isset($arraycancel[$demande->id()]))
                                {
                                    $htmltext = $htmltext . " checked='' ";
                                }
                            }
                        }
                        $htmltext = $htmltext . " onclick='backcolormotif(this," . $demande->id() . ");' ></input> $spanend </td>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, MODE_RESPONSABLE) == 0)
                        {
                            $textareastyle = " class='commenttextarea";
                            $disabletext = " disabled ";
                            if (isset($arraycancel[$demande->id()]))
                            {
                                $textareastyle = $textareastyle . " commentobligatoirebackground";
                                $disabletext = "";
                            }
                            $textareastyle = $textareastyle . "'";
                            
                            $htmltext = $htmltext . "   <td class='cellulesimple'>"
                                    //. "<input type=text name=motif[" . $demande->id() . "] id=motif[" . $demande->id() . "] value='" . $demande->motifrefus() . "' $backgroundtext size=80 oninput='checktextlength(this,$longueurmaxmotif); modifymotif(this," . $demande->id() . ");' $disabletext>"
                                    . "<textarea name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' rows='2' cols='80' $textareastyle oninput='checktextlength(this,$longueurmaxmotif); modifymotif(this," . $demande->id() . ");' $disabletext>" . $demande->motifrefus() . "</textarea>"
                                    . "</td>";
                        }
                        $htmltext = $htmltext . "</tr>";
                    }
                }
                // echo "demandeslistehtmlpourgestion => On passe au suivant <br>";
            }
            // $htmltext = $htmltext . "<br>";
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
                $htmltext = $htmltext . "
<script>
                    
// do the work...
document.getElementById('tabledemande_" . $this->agentid() . "').querySelectorAll('th').forEach(th => th.addEventListener('click', (() => {

    const currentsortindicator = th.querySelector('.sortindicator')

    if (currentsortindicator!==null)
    {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        //alert (table.id);
    
        if (currentsortindicator.innerText.trim().length>0)
        {
            th.asc = !th.asc
        }
    
        Array.from(tbody.querySelectorAll('tr'))
            .sort(comparer(Array.from(th.parentNode.children).indexOf(th), th.asc))
            .forEach(tr => tbody.appendChild(tr) );
        theader = table.querySelector('theader');
    
        //alert(Array.from(th.parentNode.querySelectorAll('th')));    
    
        for (var thindex = 0 ; thindex < document.getElementById('tabledemande_" . $this->agentid() . "').querySelectorAll('th').length; thindex++)
        {
            //alert (thindex);
            if (th.parentNode.children[thindex]!==null)
            {
                //alert (th.parentNode.children[thindex].innerHTML);
                var thsortindicator = th.parentNode.children[thindex].querySelector('.sortindicator');
                if (thsortindicator!==null)
                {
                    //alert (thsortindicator.innerText);
                    thsortindicator.innerText = ' ';
                    //alert (thsortindicator.innerText);
                }
            }
        }
    
        if (currentsortindicator!==null)
        {
            if (th.asc)
            {
                //alert ('plouf');
                currentsortindicator.innerHTML = '&darr;'; // flêhe qui descend
            }
            else
            {
                //alert ('ploc');
                currentsortindicator.innerHTML = '&uarr;'; // flêche qui monte
            }
        }
    }
})));

document.getElementById('tabledemande_" . $this->agentid() . "').querySelectorAll('th').forEach(element => element.asc = true); //  On initialise le tri des colonnes en ascendant
document.getElementById('tabledemande_" . $this->agentid() . "').querySelectorAll('th')[1].click(); // On simule le clic sur la 2e colonne pour faire afficher la flêche

</script>";
            }
        }
        if ($premieredemande)
        {
            $htmltext = '';
        }
        return $htmltext;
    }

    /**
     *
     * @param date $debut_interval
     *            date of the beginning of the interval
     * @param date $fin_interval
     *            date of the ending of the interval
     * @param string $agentid
     *            optional the structure's responsable identifier
     * @param string $mode
     *            optional deprecated parameter => not used in code
     * @return string the html text of the array
     */
    function demandeslistehtmlpourvalidation($debut_interval, $fin_interval, $agentid = null, $mode = MODE_RESPONSABLE)
    {

        $statutliste = array();
        if (isset($_POST["statut"]))
        {
           $statutliste = $_POST['statut'];
        }
        
        if (isset($_POST["mode"]))
        {
           $mode = $_POST['mode'];
        }

        $dbconstante = 'FONCTIONAVIS';
        $avisfonction = 'n';
        if ($this->fonctions->testexistdbconstante($dbconstante)) { $avisfonction = $this->fonctions->liredbconstante($dbconstante); }

        
        // Si on est en mode MODE_CONSULTANT => Le motif est enregistré dans la colonne VALEUR de la table COMPLEMENTDEMANDE
        if (strcasecmp($mode,MODE_CONSULTANT)==0)
        {
            $longueurmaxmotif = $this->fonctions->logueurmaxcolonne('DEMANDECOMPLEMENT','VALEUR');
        }
        // SInon on demande la taille de la colonne MOTIFREFUS de la table DEMANDE
        else
        {
            $longueurmaxmotif = $this->fonctions->logueurmaxcolonne('DEMANDE','MOTIFREFUS');
        }
        
        $liste = null;
        $liste = $this->demandesliste($debut_interval, $fin_interval);
        $debut_interval = $this->fonctions->formatdatedb($debut_interval);
        $fin_interval = $this->fonctions->formatdatedb($fin_interval);
        
        $htmltext = "";
        // $htmltext = "<br>";
        if (count($liste) == 0) 
        {
            // $htmltext = $htmltext . " <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
        } 
        else 
        {
            $premieredemande = TRUE;
            foreach ($liste as $key => $demande) {
                if (strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0) 
                {
                    $todisplay = true;
                    // Si on est en mode MODE_CONSULTANT
                    if (strcasecmp($mode,MODE_CONSULTANT)==0)
                    {
                        $demandecomplement  = new demandecomplement($this->dbconnect);
                        $demandecomplement->load($demande->id(),demandecomplement::DEMANDE_AVIS_STATUT_LABEL);
                        // Si on a déjà un avis du consultant => On n'affiche pas la demande
                        if ($demandecomplement->demandeid()==$demande->id())
                        {
                            $todisplay = false;
                        }
                    }
                    // On n'affiche pas les demandes du responsable !!!!
                    elseif ($agentid == $this->agentid) {
                        $todisplay = false;
                    }
                    // echo "todisplay = $todisplay <br>";
                    if ($todisplay) {
                        if ($premieredemande) {
                            $htmltext = $htmltext . "<table class='tableausimple' width=100%>";
                            // Si on est en mode MODE_CONSULTANT
                            if (strcasecmp($mode,MODE_CONSULTANT)==0)
                            {
                                $htmltext = $htmltext . "   <tr><td class=titresimple colspan=7 align=center >Avis à donner pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";
                            }
                            else
                            {
                                $htmltext = $htmltext . "   <tr><td class=titresimple colspan=7 align=center >Demandes à valider pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";
                            }
                            $htmltext = $htmltext . "   <tr align=center>
                                                            <td class='cellulesimple'>Date de demande</td>
                                                            <td class='cellulesimple'>Date de début</td>
                                                            <td class='cellulesimple'>Date de fin</td>
                                                            <td class='cellulesimple'>Type de demande</td>
                                                            <td class='cellulesimple'>Nbre jours</td>
                                                            <td class='cellulesimple'>Etat de la demande</td>";
                            // Si on est en mode MODE_CONSULTANT
                            if (strcasecmp($mode,MODE_CONSULTANT)==0)
                            {
                                $htmltext = $htmltext . "   <td class='cellulesimple'>Motif (obligatoire si l'avis est défavorable) - maximum $longueurmaxmotif caractères</td>";
                            }
                            else
                            {
                                $htmltext = $htmltext . "   <td class='cellulesimple'>Motif (obligatoire si la demande est refusée) - maximum $longueurmaxmotif caractères</td>";
                            }
                            $htmltext = $htmltext . "   </tr>";
                            $premieredemande = FALSE;
                        }
                        
                        $htmltext = $htmltext . "<tr align=center class='bulleinfo'>";
                        // $htmltext = $htmltext . " <td>" . $this->nom() . " " . $this->prenom() . "</td>";
                                                
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->date_demande() . " " . $demande->heure_demande() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datedebut()) . " " . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datefin()) . " " . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
                        if ($demande->type() == 'enmal') {
                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "  (" . $this->nbjrsenfantmaladeutilise($debut_interval, $fin_interval) . "/" . $this->nbjrsenfantmalade() . ")</td>";
                        }
                        else 
                        {
                            $libelledemande = $this->fonctions->tronque_chaine($demande->typelibelle(),40, true);
                            $datatitle = '';
                            if (strlen($demande->typelibelle()) != strlen($libelledemande)) 
                            {
                                $datatitle = " data-title=" . chr(34) . htmlentities($demande->typelibelle()) . chr(34);  
                            }
                            $htmltext = $htmltext . "<td class='cellulesimple' $datatitle >";
                            $htmltext = $htmltext . $libelledemande; 
                            $htmltext = $htmltext . "</td>";   
//                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
                        }
                        
                        $datatitle = '';
                        $datatitleindicator = '';
                        // S'il y a un commentaire et que celui-ci est obligatoire
                        if (strlen($demande->commentaire()) != 0 and $this->fonctions->absencecommentaireoblig($demande->type())) 
                        {
                            $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($demande->commentaire(),60)) . chr(34); 
                            // $datatitleindicator = " &#11127;";
                            $datatitleindicator = " &#128195;";
                        }
                        $htmltext = $htmltext . "   <td class='cellulesimple' $datatitle>" . $demande->nbrejrsdemande() . $datatitleindicator . "</td>";
                        
                        $datatitle = '';
                        if (strcasecmp($mode,MODE_CONSULTANT)!=0 and $this->fonctions->convertvaluetobool($avisfonction))
                        {
                            $texteavis = '';
                            // On cherche le statut de l'avis
                            $demandecomplement  = new demandecomplement($this->dbconnect);
                            $demandecomplement->load($demande->id(),demandecomplement::DEMANDE_AVIS_STATUT_LABEL);
                            if ($demandecomplement->demandeid()==$demande->id())
                            {
                                if (strcasecmp($demandecomplement->valeur(),demande::DEMANDE_REFUSE)==0)
                                {
                                    $texteavis = $texteavis . "Avis défavorable : ";
                                    $demandecomplement  = new demandecomplement($this->dbconnect);
                                    $demandecomplement->load($demande->id(),demandecomplement::DEMANDE_AVIS_MOTIF_LABEL);
                                    if ($demandecomplement->demandeid()==$demande->id())
                                    {
                                        $texteavis = $texteavis . $demandecomplement->valeur();
                                    }
                                }
                                else
                                {
                                    $texteavis = $texteavis . "Avis favorable";                                    
                                }
                            }
                            if (trim($texteavis) != '')
                            {
                                $datatitle = " data-title=" . chr(34) . htmlentities($texteavis) . chr(34);
                            }
                        }

                        $htmltext = $htmltext . "   <td class='cellulesimple' $datatitle >";
                        
                        // Si on a demandé l'avis => On force le statut à "En attente" pour éviter de reposter la demande d'avis 
                        // en cas d'enregistrement d'une modification sur une autre demande
                        // En théorie déjà fait dans la page 'valider_demande.php' mais par sécurité on le remet ici
                        if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_AVIS)
                        {
                            $statutliste[$demande->id()] = demande::DEMANDE_ATTENTE;
                        }
                        
                        $htmltext = $htmltext . "      <select name='statut[" . $demande->id() . "]' id='statut[" . $demande->id() . "]' onchange='demandestatutchange(this," . $demande->id() . ");'>";
                        $htmltext = $htmltext . "         <option ";
                        
                        if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_VALIDE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        elseif (!isset($statutliste[$demande->id()]) and strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_VALIDE . "'>";
                        // Si on est en mode MODE_CONSULTANT
                        if (strcasecmp($mode,MODE_CONSULTANT)==0)
                        {
                            $htmltext = $htmltext . $this->fonctions->demandeavislibelle(demande::DEMANDE_VALIDE);
                        }
                        else
                        {
                            $htmltext = $htmltext . $this->fonctions->demandestatutlibelle(demande::DEMANDE_VALIDE);
                        }
                        $htmltext = $htmltext . "</option>";
                        $htmltext = $htmltext . "         <option ";
                        //if (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) == 0)
                        if (isset($statutliste[$demande->id()]) and ($statutliste[$demande->id()] == demande::DEMANDE_REFUSE or $statutliste[$demande->id()] == demande::DEMANDE_ANNULE))
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        elseif (!isset($statutliste[$demande->id()]) and (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) == 0 or strcasecmp($demande->statut(), demande::DEMANDE_ANNULE) == 0))
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_REFUSE . "'>";
                        // Si on est en mode MODE_CONSULTANT
                        if (strcasecmp($mode,MODE_CONSULTANT)==0)
                        {
                            $htmltext = $htmltext . $this->fonctions->demandeavislibelle(demande::DEMANDE_REFUSE);
                        }
                        else
                        {
                            $htmltext = $htmltext . $this->fonctions->demandestatutlibelle(demande::DEMANDE_REFUSE);
                        }
                        $htmltext = $htmltext . "</option>";

                        if ($this->fonctions->convertvaluetobool($avisfonction))
                        {
                            $complement = new complement($this->dbconnect);
                            $complement->load($this->agentid,complement::AVIS_CONGES_LABEL);
                            if ($complement->agentid()==$this->agentid and $complement->valeur()!="" and strcasecmp($mode,MODE_CONSULTANT)!=0)
                            {
                                // Si le statut de l'avis n'existe pas => On demande l'avis. Sinon l'avis est déjà donné, donc on ne le redemande pas.
                                $demandecomplement  = new demandecomplement($this->dbconnect);
                                $demandecomplement->load($demande->id(),demandecomplement::DEMANDE_AVIS_STATUT_LABEL);
                                if ($demandecomplement->demandeid()!=$demande->id())
                                {
                                    $htmltext = $htmltext . "         <option ";
                                    if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_AVIS)
                                    {
                                        $htmltext = $htmltext . " selected ";
                                    }
                                    elseif (!isset($statutliste[$demande->id()]) and strcasecmp($demande->statut(), demande::DEMANDE_AVIS) == 0)
                                    {
                                        $htmltext = $htmltext . " selected ";
                                    }
                                    $htmltext = $htmltext . " value='" . demande::DEMANDE_AVIS ."'>";
                                    // On sait qu'on n'est pas en mode MODE_CONSULTANT => donc c'est forcément le libellé du statut 
                                    $htmltext = $htmltext . $this->fonctions->demandestatutlibelle(demande::DEMANDE_AVIS);
                                    $htmltext = $htmltext . "</option>";

                                }
                            }
                        }
                        
                        $htmltext = $htmltext . "         <option ";
                        if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_ATTENTE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        elseif (!isset($statutliste[$demande->id()]) and strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_ATTENTE ."'>";
                        // Si on est en mode MODE_CONSULTANT
                        if (strcasecmp($mode,MODE_CONSULTANT)==0)
                        {
                            $htmltext = $htmltext . $this->fonctions->demandeavislibelle(demande::DEMANDE_ATTENTE);
                        }
                        else
                        {
                            $htmltext = $htmltext . $this->fonctions->demandestatutlibelle(demande::DEMANDE_ATTENTE);
                        }
                        $htmltext = $htmltext . "</option>";
                        $htmltext = $htmltext . "      </select>";
                        $htmltext = $htmltext . "</td>";
                        
                        $textareastyle = " class='commenttextarea";
                        $disabletext = " disabled ";
                        if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_REFUSE)
                        {
                            $textareastyle = $textareastyle . " commentobligatoirebackground";
                            $disabletext = "";
                        }
                        $textareastyle = $textareastyle . "'";
                        
//                        $htmltext = $htmltext . "   <td class='cellulesimple'><input type=text name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' value='" . $demande->motifrefus() . "' $backgroundtext size='80' oninput='checktextlength(this,$longueurmaxmotif); validdemandemotif(this," . $demande->id() . ");' $disabletext></td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>"
                                              . "      <textarea name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' rows='2' cols='80' $textareastyle oninput='checktextlength(this,$longueurmaxmotif); validdemandemotif(this," . $demande->id() . ");' $disabletext>";
                        $htmltext = $htmltext . $demande->motifrefus();
                        $htmltext = $htmltext . trim(" </textarea>")
                                              . "   </td>";
                        $htmltext = $htmltext . "</tr>";
                    }
                }
            }
            if (! $premieredemande)
            {
                $htmltext = $htmltext . "</table>";
            }
            // $htmltext = $htmltext . "<br>";
        }
        return $htmltext;
    }

    function listecommentaireconge($typeabsenceid = null)
    {
        $listecommentaire = array();
        if (is_null($typeabsenceid))
        {
            $sql = "SELECT COMMENTAIRECONGE.COMMENTAIRECONGEID,
                           COMMENTAIRECONGE.AGENTID,
                           COMMENTAIRECONGE.TYPEABSENCEID,
                           COMMENTAIRECONGE.DATEAJOUTCONGE,
                           COMMENTAIRECONGE.COMMENTAIRE,
                           COMMENTAIRECONGE.NBRJRSAJOUTE,
                           COMMENTAIRECONGE.AUTEURID,
                           TYPEABSENCE.LIBELLE
                    FROM COMMENTAIRECONGE, TYPEABSENCE
                    WHERE COMMENTAIRECONGE.AGENTID = ? 
                      AND TYPEABSENCE.TYPEABSENCEID = COMMENTAIRECONGE.TYPEABSENCEID";
            $params = array($this->agentid);
        }
        else
        {
            $sql = "SELECT COMMENTAIRECONGE.COMMENTAIRECONGEID,
                           COMMENTAIRECONGE.AGENTID,
                           COMMENTAIRECONGE.TYPEABSENCEID,
                           COMMENTAIRECONGE.DATEAJOUTCONGE,
                           COMMENTAIRECONGE.COMMENTAIRE,
                           COMMENTAIRECONGE.NBRJRSAJOUTE,
                           COMMENTAIRECONGE.AUTEURID,
                           TYPEABSENCE.LIBELLE
                    FROM COMMENTAIRECONGE, TYPEABSENCE
                    WHERE COMMENTAIRECONGE.AGENTID= ? 
                      AND COMMENTAIRECONGE.TYPEABSENCEID = ?
                      AND TYPEABSENCE.TYPEABSENCEID = COMMENTAIRECONGE.TYPEABSENCEID";
            $params = array($this->agentid,$typeabsenceid);
        }
        $sql = $sql . " ORDER BY COMMENTAIRECONGE.DATEAJOUTCONGE";
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "SQL = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            echo "Agent->listecommentaireconge : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Agent->listecommentaireconge : " . $erreur);
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $commentaireconge = $this->fonctions->lirecommentaire($result[0]);
            $listecommentaire[] = $commentaireconge;
        }
        return $listecommentaire;
    }
    
    /**
     *
     * @param
     * @return string the html text of the array
     */
    function affichecommentairecongehtml($showonlycomplement = false, $anneeref = null, $allowremove = false)
    {
        //echo "<br>anneeref = XXX" . $anneeref  . "XXX<br>";
        $jointuresupp = "";
        if (!$allowremove)
        {
            $jointuresupp = " AND COMMENTAIRECONGE.AGENTID NOT IN (
                SELECT COMPLEMENT.AGENTID 
                FROM COMPLEMENT 
                WHERE COMPLEMENT.AGENTID = COMMENTAIRECONGE.AGENTID
                  AND COMPLEMENT.COMPLEMENTID = CONCAT('" . complement::AVISRH_CONGES_SUP_LABEL . "',COMMENTAIRECONGE.COMMENTAIRECONGEID)
                )";
        }


        if (is_null($anneeref))
        {
            $sql = "SELECT COMMENTAIRECONGE.AGENTID,
                           TYPEABSENCE.LIBELLE,
                           COMMENTAIRECONGE.DATEAJOUTCONGE,
                           COMMENTAIRECONGE.COMMENTAIRE,
                           COMMENTAIRECONGE.NBRJRSAJOUTE,
                           TYPEABSENCE.TYPEABSENCEID,
                           COMMENTAIRECONGE.COMMENTAIRECONGEID,
                           COMMENTAIRECONGE.AUTEURID,
                           COMMENTAIRECONGE.NBJRSPRIS
                    FROM COMMENTAIRECONGE,TYPEABSENCE 
                    WHERE COMMENTAIRECONGE.AGENTID= ? 
                      AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID
                      AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($this->fonctions->anneeref(), 2, 2) . "' 
                        OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($this->fonctions->anneeref() - 1), 2, 2) . "' 
                        OR COMMENTAIRECONGE.TYPEABSENCEID = '" . recuperation::RECUP_ID . "'
                        OR COMMENTAIRECONGE.TYPEABSENCEID='cet') $jointuresupp";
        }
        else
        {
            $sql = "SELECT COMMENTAIRECONGE.AGENTID,
                           TYPEABSENCE.LIBELLE,
                           COMMENTAIRECONGE.DATEAJOUTCONGE,
                           COMMENTAIRECONGE.COMMENTAIRE,
                           COMMENTAIRECONGE.NBRJRSAJOUTE,
                           TYPEABSENCE.TYPEABSENCEID,
                           COMMENTAIRECONGE.COMMENTAIRECONGEID,
                           COMMENTAIRECONGE.AUTEURID,
                           COMMENTAIRECONGE.NBJRSPRIS
                    FROM COMMENTAIRECONGE,TYPEABSENCE
                    WHERE COMMENTAIRECONGE.AGENTID= ? 
                      AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID
                      AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($anneeref, 2, 2) . "'
                        OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($anneeref + 1), 2, 2) . "'
                        OR COMMENTAIRECONGE.TYPEABSENCEID = '" . recuperation::RECUP_ID  . "'
                        OR COMMENTAIRECONGE.TYPEABSENCEID='cet') $jointuresupp";
            
        }
        $params = array($this->agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "SQL = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            echo "Agent->affichecommentairecongehtml : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Agent->affichecommentairecongehtml : " . $erreur);
        }
        $htmltext = "";
        $premiercomment = TRUE;
        while ($result = mysqli_fetch_row($query)) {
            if (($showonlycomplement and ((strcasecmp(substr($result[5], 0, 3), recuperation::SUPP_ID)) == 0 or $result[5]==recuperation::RECUP_ID)) or ($showonlycomplement == false)) {
                if ($premiercomment) 
                {
                    if (!$allowremove)
                    {
                        $htmltext = $htmltext . "<center>";
                    }
                    $htmltext = $htmltext . "<table class='tableausimple'>";
                    $nbcolonne = 4;
                    if ($allowremove)
                    {
                        $nbcolonne++;
                    }
                    $htmltext = $htmltext . "<tr><td class='titresimple' colspan=$nbcolonne align=center>Commentaires sur les modifications de congés</td></tr>";
                    $htmltext = $htmltext . "<tr align=center>"
                            . "<td class='cellulesimple'>Type de demande</td>"
                            . "<td class='cellulesimple'>Date modification</td>"
                            . "<td class='cellulesimple'>Jours</td>"
                            . "<td class='cellulesimple'>Commentaire</td>";
                    if ($allowremove)
                    {
                        $htmltext = $htmltext . "<td class='cellulesimple'>Annulation</td>";
                    }
                    $htmltext = $htmltext . "</tr>";
                    $premiercomment = FALSE;
                }
                
                $extraclass = "";
                $spantext = '';
                $complement = new complement($this->dbconnect);
                $complement->load($this->agentid,complement::REFUSRH_CONGES_SUP_LABEL . $result[6]);
                // Si le complement REFUSRH_CONGES_SUP_LABEL existe (<=> agentid != '') => On doit indiquer qu'il a été refusé par RH
                if ($complement->agentid()!="")
                {
                    $extraclass = " textstrike ";
                    $spantext = '<span class="textstrike" data-tip="Refus par la Direction des Ressources Humaines : ' . htmlentities($complement->valeur()) . '">';
                }
                elseif ($allowremove)
                {
                    $complement = new complement($this->dbconnect);
                    $complement->load($this->agentid,complement::AVISRH_CONGES_SUP_LABEL . $result[6]);
                    // Si le complement AVISRH_CONGES_SUP_LABEL existe (<=> agentid != '') => On doit indiquer qu'il est en attente de validation par RH
                    if ($complement->agentid()!="")
                    {
                        $extraclass = " attentevalid ";
                        $spantext = '<span data-tip="L\'ajout de congés est en attente de validation par la Direction des Ressources Humaines.">';

                    }
                }
                $spanend = '';
                if ($spantext != '')
                {
                    $spanend = '</span>';
                }

                $htmltext = $htmltext . "<tr align=center>";
                $htmltext = $htmltext . "<td class='cellulesimple $extraclass'>" . $result[1] . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple $extraclass'>" . $this->fonctions->formatdate($result[2]) . "</td>";
                if ($result[4] > 0)
                {
                    $htmltext = $htmltext . "<td class='cellulesimple $extraclass'>+" . (float) ($result[4]) . "</td>";
                }
                else
                {
                    $htmltext = $htmltext . "<td class='cellulesimple $extraclass'>" . (float) ($result[4]) . "</td>";
                }
                $commentaire = trim($result[3]);
                if (trim($result[7])=='')
                {
                    $htmltext = $htmltext . "<td class='cellulesimple $extraclass'>" . htmlentities($commentaire) . "</td>";
                }
                else
                {
                    $auteur = new agent($this->dbconnect);
                    $auteur->load(trim($result[7]));
                    $htmltext = $htmltext . "<td class='cellulesimple cellulemultiligne $extraclass' >$spantext " . htmlentities($commentaire) . " (par " .  $auteur->identitecomplete()  .   ") $spanend</td>";
                }
                if ($allowremove)
                {
                    $disabled = "";
                    $spantext = '';

                    if ($complement->complementid()==complement::REFUSRH_CONGES_SUP_LABEL . $result[6])
                    {
                        $disabled = " hidden ";
                        //$spantext = '<span data-tip="Suppression impossible : Cet ajout de jours de récupération a déjà refusée par la Direction des Ressources Humaines.">';
                    }
                    elseif ($result[8]>0)
                    {
                        $disabled = " disabled ";
                        $spantext = '<span data-tip="Suppression impossible : L\'agent a déjà utilisé une partie/la totalité de ces jours de récupération.">';
                    }
                    $htmltext = $htmltext . "<td class='cellulesimple'>$spantext<input type='checkbox' $disabled id='" . $result[6] . "' name='remove_compl_id[" . $result[6] . "]'></td>";
                }
                $htmltext = $htmltext . "</tr>";
            }
        }
        if (!$premiercomment)
        {
            $htmltext = $htmltext . "</table>";
        }
        if (!$allowremove)
        {
            $htmltext = $htmltext . "</center>";
        }
        if (!$premiercomment)
        {
            $htmltext = $htmltext . "<br>";
        }
        return $htmltext;
    }

    /**
     * Fonction permettant de modifier la date d'ajout du commentaire et le commentaire.
     * ATTENTION : Ne modifie que le commentaire, la date du commentaire et le nombre de jours pris. Les autres propriétés ne sont pas modifiées.
     * 
     * @param commentaireconge $commentaireconge
     *            commentaire sur le congé à modifier
     * @return string Chaine vide si tout est correct. Sinon la description du problème.
     *
     */
    function modifiercommentaireconge(commentaireconge $commentaireconge) : bool
    {
        $erreur = "";
        if (trim($commentaireconge->commentaireid . "") == "")
        {
            $erreur = "Le commentaire n'est pas sauvegardé. Impossible de le modifier";
            error_log(basename(__FILE__) . " Agent-> modifiercommentaireconge : " . $erreur);
        }
        else
        {
            $sql = "UPDATE COMMENTAIRECONGE SET DATEAJOUTCONGE = ?, COMMENTAIRE = ? , NBJRSPRIS = ? WHERE COMMENTAIRECONGEID = ? ";
            $params = array($this->fonctions->formatdatedb($commentaireconge->dateajout), 
                            $commentaireconge->commentaire, 
                            $commentaireconge->nbjrspris,
                            $commentaireconge->commentaireid);
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") 
            {
                error_log(basename(__FILE__) . " Agent-> modifiercommentaireconge : " . $erreur);
            }
        }
        return $erreur;
    }


    /**
     *
     * @param string $typeconge
     *            optional type of vacation. default is null
     * @param string $nbrejours
     *            optional number of day of the vacation. default is null
     * @param string $commentaire
     *            optional comment for the vacation. default is null
     * @return
     */
    function ajoutecommentaireconge($typeconge = null, $nbrejours = null, $commentaire = null, $auteur = null, &$commentaireid = null)
    {
        $auteurid = null;
        if (!is_null($auteur))
        {
            if (is_object($auteur))
            {
                $auteurid = $auteur->agentid();
            }
            else
            {
                $auteurid = $auteur;
            }
        }

        $sql = "LOCK TABLES COMMENTAIRECONGE WRITE";
        mysqli_query($this->dbconnect, $sql);
        $sql = "SET AUTOCOMMIT = 0";
        mysqli_query($this->dbconnect, $sql);

        $date = date("d/m/Y");
        if (is_null($auteurid))
        {
            $sql = "INSERT INTO COMMENTAIRECONGE(AGENTID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,NBJRSPRIS)
                            VALUES (?,?,?,?,?,0)";
            $params = array($this->agentid, $typeconge, $this->fonctions->formatdatedb($date),$commentaire,$nbrejours);
        }
        else
        {
            $sql = "INSERT INTO COMMENTAIRECONGE(AGENTID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,AUTEURID,NBJRSPRIS)
                            VALUES (?,?,?,?,?,?,0)";
            $params = array($this->agentid, $typeconge, $this->fonctions->formatdatedb($date),$commentaire,$nbrejours,$auteurid);            
        }
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $message = "$erreur";
            error_log(basename(__FILE__) . " " . $erreur);
        }
        $commentaireid = mysqli_insert_id($this->dbconnect);
        // $this->demandeid
        $sql = "COMMIT";
        mysqli_query($this->dbconnect, $sql);
        $sql = "UNLOCK TABLES";
        mysqli_query($this->dbconnect, $sql);
        $sql = "SET AUTOCOMMIT = 1";
        mysqli_query($this->dbconnect, $sql);
}

    /**
     *
     * @param string $congessuppid
     * @param agent $demandeur
     *            object agent representing the applicant
     * @return string result if errors eccurded. Empty if all ok
     * 
     */
    function supprcongesupplementaire($congessuppid, agent $demandeur)
    {
        $marqueur_suppr = '_del';
        
        
        $sql = "SELECT NBRJRSAJOUTE,TYPEABSENCEID FROM COMMENTAIRECONGE WHERE COMMENTAIRECONGEID = ? AND TYPEABSENCEID NOT LIKE '%$marqueur_suppr'";
        $params = array($congessuppid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $message = "$erreur";
            error_log(basename(__FILE__) . " " . $message);
            return $message;
        }
        if (mysqli_num_rows($query) == 0) 
        {
            $message = "Impossible de trouver la demande d'ajout de comgés complémentaires/récupération $congessuppid";
            error_log(basename(__FILE__) . " " . $message);
            return $message;
        }
        $result = mysqli_fetch_row($query);
        $nbrejoursajoutes = $result[0];

        $complement = new complement($this->dbconnect);
        $complement->load($this->agentid,complement::AVISRH_CONGES_SUP_LABEL . $congessuppid);
        // Si le complement n'existe pas (<=> agentid == '') => On doit impacter le solde de l'agent
        // Si non, l'ajout de congés complémentaires n'a pas été validé par la DRH et le solde n'est pas impacté
        // ATTENTION Si c'est une recupération => Le solde ne doit pas être impacté puisqu'il n'existe pas.
        if ($complement->agentid()=="" and $result[1] != recuperation::RECUP_ID)
        {
            $solde = new solde($this->dbconnect);
            $erreur = $solde->load($this->agentid,$result[1]);
            if ($erreur != "") 
            {
                $message = "$erreur";
                error_log(basename(__FILE__) . " " . $message);
                return $message;
            }
            $solderestant = $solde->droitaquis()-$solde->droitpris();
            //echo "solderestant = $solderestant   nbrejoursajoutes = $nbrejoursajoutes <br>";
            if ($solderestant >= $nbrejoursajoutes)
            {
                // Il reste suffisament de jours pour annuler les jours complémentaires
                $acquis = $solde->droitaquis()-$nbrejoursajoutes;
                $solde->droitaquis($acquis);
                $erreur = $solde->store();
                if ($erreur != "")
                {
                    $message = "$erreur";
                    error_log(basename(__FILE__) . " " . $message);
                    return $message;
                }
            }
            else
            {
                //$message = "Nombre de jours complémentaires insuffisant pour annuler la demande $congessuppid => Nbre de jours restant = $solderestant / Nbre de jours à annuler : $nbrejoursajoutes";
                $message = "Nombre de jours de récupération insuffisant pour annuler la demande $congessuppid => Nbre de jours restant = $solderestant / Nbre de jours à annuler : $nbrejoursajoutes";
                error_log(basename(__FILE__) . " " . $message);
                return $message;
            }
        }
/*
        $sql = "DELETE FROM COMMENTAIRECONGE WHERE COMMENTAIRECONGEID = ?";
        $params = array($congessuppid); 
*/
        $sql = "UPDATE COMMENTAIRECONGE SET COMMENTAIRE = CONCAT(COMMENTAIRE, ' (Suppr. par " . $demandeur->agentid()  . ")'), TYPEABSENCEID = CONCAT(TYPEABSENCEID,'$marqueur_suppr')  WHERE COMMENTAIRECONGEID = ?";
        $params = array($congessuppid);
        // echo "SQL = $sql <br>";
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            $message = "$erreur";
            error_log(basename(__FILE__) . " " . $message);
            return $message;
        }
        //$message = "Suppression de l'ajout de jours complémentaire $congessuppid pour " . $this->agentid . " par " . $demandeur->identitecomplete();
        $message = "Suppression de l'ajout de jours de récupération $congessuppid pour " . $this->agentid . " par " . $demandeur->identitecomplete();
        error_log(basename(__FILE__) . " " . $message);
        return "";
    }
    
    function aunedemandecongesbonifies($anneeref)
    {
        $demande = null;
        $debutperiode = $this->fonctions->formatdatedb($anneeref . $this->fonctions->debutperiode());
        $finperiode = $this->fonctions->formatdatedb(($anneeref + 1) . $this->fonctions->finperiode());
        $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN FROM ABSENCERH WHERE AGENTID= ? AND (LIBELLE='CONGE_BONIFIE' OR LIBELLE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $params = array($this->agentid);
        $query = $this->fonctions->prepared_select($sql, $params);

        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        if (mysqli_num_rows($query) != 0) // Il existe un congé bonifié pour la période => On le solde des congés à 0
        {
            $resultcongbonif = mysqli_fetch_row($query);
            $demande = new demande($this->dbconnect);
            $demande->datedebut($resultcongbonif[1]);
            $demande->datefin($resultcongbonif[2]);
            $demande->type('harp');
        }
        return $demande;
    }

    function creertimeline()
    {
        $sql = "SELECT AGENTID, NUMLIGNE, CODECONTRAT, DATEDEBUT, DATEFIN FROM STATUT WHERE AGENTID = ? ORDER BY DATEDEBUT";
        $params = array($this->agentid);
        $querystatut = $this->fonctions->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        if (mysqli_num_rows($querystatut) == 0) // Il n'y a pas de STATUT pour cet agent => On sort
        {
            echo "<br>Pas de statut pour cet agent " . $this->agentid . "!!!<br>";
            return "<br>Pas de statut pour cet agent " . $this->agentid . "!!!<br>";
        }
        
        $sql = "SELECT AGENTID, NUMLIGNE, QUOTITE, DATEDEBUT, DATEFIN FROM QUOTITE WHERE AGENTID = ? ORDER BY DATEDEBUT";
        $params = array($this->agentid);
        $queryquotite = $this->fonctions->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        if (mysqli_num_rows($queryquotite) == 0) // Il n'y a pas de QUOTITE pour cet agent => On sort
        {
            echo "<br>Pas de quotité pour cet agent " . $this->agentid . "!!!<br>";
            return "<br>Pas de quotité pour cet agent " . $this->agentid . "!!!<br>";
        }
        
        $sql = "SELECT AGENTID, NUMLIGNE, POSITIONADMIN, DATEDEBUT, DATEFIN FROM SITUATIONADMIN WHERE AGENTID = ? ORDER BY DATEDEBUT";
        $params = array($this->agentid);
        $querysituation = $this->fonctions->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        if (mysqli_num_rows($querysituation) == 0) // Il n'y a pas de SITUATIONADMIN pour cet agent => On sort
        {
            echo "<br>Pas de situation pour cet agent " . $this->agentid . "!!!<br>";
            return "<br>Pas de situation pour cet agent " . $this->agentid . "!!!<br>";
        }
        $curentstatut = mysqli_fetch_row($querystatut);
        $curentquotite = mysqli_fetch_row($queryquotite);
        $currentsituation = mysqli_fetch_row($querysituation);
        
        $strresultat = '';
        $tabresult = array();
        
        
        while ($curentstatut and $curentquotite and $currentsituation) {            
            $statutagentid = $curentstatut[0];
            $statutnumligne = $curentstatut[1];
            $codecontrat = trim($curentstatut[2]);
            $statutdatedebut = $curentstatut[3];
            $statutdatefin = $curentstatut[4];
            
            $quotiteagentid = $curentquotite[0];
            $quotitenumligne = $curentquotite[1];
            $quotitevalue = trim($curentquotite[2]);
            $quotitedatedebut = $curentquotite[3];
            $quotitedatefin = $curentquotite[4];
            
            $situationagentid = $currentsituation[0];
            $situationnumligne = $currentsituation[1];
            $situationposition = trim($currentsituation[2]);
            $situationdatedebut = $currentsituation[3];
            $situationdatefin = $currentsituation[4];
            
            //echo "statutagentid = $statutagentid  statutnumligne = $statutnumligne   codecontrat = $codecontrat  statutdatedebut = $statutdatedebut  statutdatefin = $statutdatefin \n";
            //echo "quotiteagentid = $quotiteagentid  quotitenumligne = $quotitenumligne  quotitevalue = $quotitevalue  quotitedatedebut = $quotitedatedebut  quotitedatefin = $quotitedatefin \n";
            //echo "situationagentid = $situationagentid  situationnumligne = $situationnumligne  situationposition = $situationposition  situationdatedebut = $situationdatedebut  situationdatefin = $situationdatefin \n";
            
            
            $datedebut = '1899-12-31';
            $datefin = '9999-12-31';
            
            if ($statutdatedebut > $datedebut)
            {
                $datedebut = $statutdatedebut;
            }
            if ($quotitedatedebut > $datedebut)
            {
                $datedebut = $quotitedatedebut;
            }
            if ($situationdatedebut > $datedebut)
            {
                $datedebut = $situationdatedebut;
            }
                    
            if ($statutdatefin < $datefin)
            {
                $datefin = $statutdatefin;
            }
            if ($quotitedatefin < $datefin)
            {
                $datefin = $quotitedatefin;
            }
            if ($situationdatefin < $datefin)
            {
                $datefin = $situationdatefin;
            }
            
            if ($datefin < $datedebut) {
                //echo "Detection de datefin ($datefin) < datedebut ($datedebut) => On ignore pour agent " . $this->agentid . "!!!<br>\n";
            } else {
                $strresultat = $this->agentid . '_' . $statutnumligne . '_' . $quotitenumligne . '_' . $situationnumligne;
                $strresultat = $strresultat . ';' . $this->agentid;
                if (substr($codecontrat, 0, 5) != 'CONTR')
                {
                    $strresultat = $strresultat . ';' . '0'; // Si ce n'est pas un contrat, le numéro de la ligne doit être vide ou égal à 0
                }
                else
                {
                    // On ne met pas le code contrat mais le numéro de la ligne du contrat car il est nécessaire pour calculer
                    // le solde de congés des agents
                    $strresultat = $strresultat . ';' . $statutnumligne; // $codecontrat;
                }
                $strresultat = $strresultat . ';' . $datedebut;
                $strresultat = $strresultat . ';' . $datefin;
                $strresultat = $strresultat . ';' . date("Ymd");
                $strresultat = $strresultat . ';'; // structureid
                $strresultat = $strresultat . ';' . $quotitevalue;
                $strresultat = $strresultat . ';' . '100';
                $strresultat = $strresultat . ';';
                
                //echo $strresultat . '<br>' . $situationposition . '<br>';
                // Si la postion administrative de l'agent est "En activité" (les 3 premiers caractères de situationposition = 'ACI') 
                // ou "Détachement entrant"  (les 3 premiers caractères de situationposition = 'DEE%') on enregistre l'info
                // Sinon on crée un 'trou' dans son activité

                // if ($this->agentid == 10946)
                // {
                //     echo "<br>\nOn est sur l'agent : " . $this->agentid;
                //     echo "<br>\nstrresultat = $strresultat";
                //     echo "<br>\nsituationposition = $situationposition";
                //     echo "<br>\n";
                // }

                $situationposition = strtoupper(trim($situationposition));
                if (substr($situationposition,0,3) == 'ACI' or substr($situationposition,0,3) == 'DEE' )
                {
                    $tabresult[] = $strresultat;
                }
            }
            if ($datefin == $statutdatefin)
            {
                $curentstatut = mysqli_fetch_row($querystatut);
            }
            if ($datefin == $quotitedatefin)
            {
                $curentquotite = mysqli_fetch_row($queryquotite);
            }
            if ($datefin == $situationdatefin)
            {
                $currentsituation = mysqli_fetch_row($querysituation);
            }
        }
        return $tabresult;
    }

    function controlecongesTP($datedebut, $datefin)
    {
        $analyse = array();
        $demandeliste = $this->demandesliste($datedebut, $datefin);
        
        foreach ($demandeliste as $demande) {
            $nbrejrscalcule = 0;
            if (! $demande->controlenbrejrs($nbrejrscalcule)) {
                $analyse[$demande->id()] = "Incohérence détectée : Nombre de jours de la demande = " . $demande->nbrejrsdemande() . " / Nombre de jours recalculé = $nbrejrscalcule (demande Id = " . $demande->id() . ")";
            }            // La fonction retourne vrai mais avec un nombre de jour nul => La demande est annulée ou refusée
            elseif ($nbrejrscalcule < 0) {
                $analyse[$demande->id()] = "Aucune vérification faite car la demande " . $demande->id() . " est annulée ou refusée...";
            }
        }
        
        return $analyse;
    }

    function CETaverifier($datedebut)
    {
        return $this->fonctions->CETaverifier($datedebut, $this->agentid);
    }
    
    function isG2tUser()
    {
        $arrayresult = $this->fonctions->listeg2tuser($this->agentid);
        if (count($arrayresult)==1)
        {
            return true;
        }
        return false;
    }
    
    function getprofessionaladdress()
    {
    	// On récupère les infos pour la demande d'alimentation du CET
    	// adresse postale
    	$LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
    	$LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
    	$LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
    	$LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
    	$LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
    	$LDAP_POSTAL_ADDRESS_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_ADDRESS_ATTR");
    	$retour = "";
    	// Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
    	if (trim("$LDAP_POSTAL_ADDRESS_ATTR") != "")
        {
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "(".$LDAP_CODE_AGENT_ATTR."=".$this->agentid().")";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array("$LDAP_POSTAL_ADDRESS_ATTR");
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr); 

            if (isset($info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]))
            {
                $retour = str_replace('$', ', ',$info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]);
            }
            else
            {
                $errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->agentid() . ") n'a pas de postalAddress....";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
    	}
    	return $retour;
    }

    function getInfoDocCet()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use getprofessionaladdress instead.', E_USER_DEPRECATED);
        
        return $this->getprofessionaladdress();
    }
    
    function getpersonnaladdress()
    {
//        $retour[LDAP_AGENT_PERSO_ADDRESS_ATTR] = "Adresse personnelle de test !! Ne pas prendre en compte";
//        return $retour;

        // adresse postale
        $LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
        $LDAP_AGENT_PERSO_ADDRESS_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_PERSO_ADDRESS_ATTR");
        $retour = "";
        // Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
        if (trim("$LDAP_AGENT_PERSO_ADDRESS_ATTR") != "")
        {
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "(".$LDAP_CODE_AGENT_ATTR."=".$this->agentid().")";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array("$LDAP_AGENT_PERSO_ADDRESS_ATTR");
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            
            if (isset($info[0]["$LDAP_AGENT_PERSO_ADDRESS_ATTR"][0]))
            {
                $retour = str_replace('$', ', ',$info[0]["$LDAP_AGENT_PERSO_ADDRESS_ATTR"][0]);
            }
            else
            {
                $errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->agentid() . ") n'a pas de personnalpostaladdress....";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        return $retour;
    }
    
    function afficheAlimCetHtml($typeconge = '', $statuts = array())
    {
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
        $g2t_ws_url = $serverprotocol . "://" . $servername . ":" . $serverport;
        $full_g2t_ws_url = $g2t_ws_url . "/ws/alimentationWS.php";
*/
        
        $alimcet = new alimentationCET($this->dbconnect);
    	$listid = $this->getDemandesAlim($typeconge, $statuts);
    	$htmltext = '';
    	if (sizeof($listid) != 0)
    	{
            $htmltext = $htmltext . "<div id='demandes_alim_cet'>";
            //$htmltext = $htmltext . "<center>";
            $htmltext = $htmltext . "<table class='tableausimple tabsynthesealim centertable'>";
            $htmltext = $htmltext . "<thead>";
            $htmltext = $htmltext . "<tr class='titresimple'>";
            $htmltext = $htmltext . "   <th colspan=8>Informations sur les demandes d'alimentation de CET pour " . $this->identitecomplete() . "</th>";
            $htmltext = $htmltext . "</tr>";
            $htmltext = $htmltext . "<tr>";
            $htmltext = $htmltext . "   <th class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Type de demande</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</th>";
            $htmltext = $htmltext . "</tr>";
            $htmltext = $htmltext . "</thead>";
            $htmltext = $htmltext . "<tbody>";
            foreach ($listid as $alimid => $id)
            {
                $alimcet->load(null,$alimid);
                $htmltext = $htmltext . "<tr>
                                    <td class='cellulesimple'>" . $alimid . "</td>
                                    <td class='cellulesimple'>" . $this->fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td>
                                    <td class='cellulesimple typeannee " . $alimcet->typeconges() . "'>" . $alimcet->typelibelle() . "</td>
                                    <td class='cellulesimple'>" . $alimcet->valeur_f() . "</td>
                                    <td class='cellulesimple statutalim'>" . $alimcet->statut() . "</td>
                                    <td class='cellulesimple'>" . $this->fonctions->formatdate($alimcet->datestatut()) . "</td>
                                    <td class='cellulesimple'>" . $alimcet->motif() . "</td>
                                    <td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>
                                 </tr>";
            }
            $htmltext = $htmltext . "</tbody>";
            $htmltext = $htmltext . "</table><br>";
            //$htmltext = $htmltext . "</center>";

            $htmltext = $htmltext . "</div>";
    	}
    	else
    	{
    	    $htmltext = $htmltext . "Aucune demande d'alimentation pour l'agent " . $this->identitecomplete() . "<br>";
    	}
    	return $htmltext;
    }
    
    function afficheOptionCetHtml($anneeref = '', $statuts = array())
    {
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
        $g2t_ws_url = $serverprotocol . "://" . $servername . ":" . $serverport;
        $full_g2t_ws_url = $g2t_ws_url . "/ws/optionWS.php";
*/
        
        
        $optioncet = new optionCET($this->dbconnect);
        $listid = $this->getDemandesOption($anneeref, $statuts);
        $htmltext = '';
        if (sizeof($listid) != 0)
        {
            $htmltext = $htmltext . "<div id='option_alim_cet'>";
            $htmltext = $htmltext . "<table class='tableausimple tabsyntheseoption centertable' >";
            $htmltext = $htmltext . "<thead>";
            $htmltext = $htmltext . "<tr>";
            $htmltext = $htmltext . "<th class='titresimple' colspan='9'>Informations sur les droits d'options sur CET pour " . $this->identitecomplete() . "</th></tr>";
            $htmltext = $htmltext . "<tr>"; 
            $htmltext = $htmltext . "   <th class='titresimple'>Identifiant</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Date création</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Année de référence</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>RAFP</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Indemnisation</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Statut</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Date Statut</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Motif</th>";
            $htmltext = $htmltext . "   <th class='titresimple'>Consulter</th>";
            $htmltext = $htmltext . "</tr>";
            $htmltext = $htmltext . "</thead>";
            $htmltext = $htmltext . "<tbody>";
            foreach ($listid as $optionid => $id)
            {
                $optioncet->load(null, $optionid);
                $htmltext = $htmltext . "<tr>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $optionid . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple typeannee annee_" . $optioncet->anneeref() . "'>" . $optioncet->anneeref() . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $optioncet->valeur_i() . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $optioncet->valeur_j() . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple statutoption'>" . $optioncet->statut() . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->formatdate($optioncet->datestatut()) . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'>" . $optioncet->motif() . "</td>";
                $htmltext = $htmltext . "   <td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td>";
                $htmltext = $htmltext . "</tr>";
            }
            $htmltext = $htmltext . "</tbody>";
            $htmltext = $htmltext . "</table><br>";
            
            $htmltext = $htmltext . "</div>";
        }
        else
        {
            $htmltext = $htmltext . "Aucune demande de droit d'option pour l'agent " . $this->identitecomplete() . "<br>";
        }
        return $htmltext;
    }
    
    /**
     * 
     * @param string $typeconge
     * @param array $listStatuts
     * @return array of esignatureid 
     */
    function getDemandesAlim($typeconge = '', $listStatuts = array())
    {
    	$listdemandes = array();
    	$statuts = '';
    	$sql = "SELECT ESIGNATUREID,ALIMENTATIONID FROM ALIMENTATIONCET WHERE AGENTID = ? ";
    	if ($typeconge != '') 
    	{
    		$sql .= " AND TYPECONGES = '$typeconge' " ;
    	}
    	if (sizeof($listStatuts) != 0)
    	{
    		$statuts = $this->fonctions->formatlistedb($listStatuts);
    		$sql .=  " AND STATUT IN $statuts";
    	}
    	$params = array($this->agentid);
    	$query = $this->fonctions->prepared_select($sql, $params);

    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
    	{
    		$errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande d'alimentation pour l'agent " . $this->identitecomplete() . ".";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	else 
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/alimentationWS.php";
    		$full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
    		while ($result = mysqli_fetch_row($query)) 
    		{
    			$listdemandes[$result[1]] = $result[0];
    		}
    	}
    	return $listdemandes;
    }
    
    function getPlafondRefCet()
    {
    	// calcul du plafond de référence pour l'agent
    	$pr = $this->fonctions->liredbconstante('PLAFONDREFERENCECET');
    	// récupérer les affectations/quotités sur la période 01/09/N-1 - 31/08/N
    	$datedeb = ($this->fonctions->anneeref() - 1).$this->fonctions->debutperiode();
    	$datefin = $this->fonctions->anneeref().$this->fonctions->finperiode();
    	//echo "Date début affectations ($datedeb) <br> Date fin affectations ($datefin) <br>";
    	$quotitemoy = $this->getQuotiteMoyPeriode($datedeb, $datefin);
    	$errlog ="Plafond de référence paramétré : $pr. Quotité moyenne de l'agent pour la période (".$this->fonctions->formatdate($datedeb)." - ".$this->fonctions->formatdate($datefin).") : $quotitemoy % ";
    	error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	return (($pr * $quotitemoy) / 100);
    }
    
    function getQuotiteMoyPeriode($datedebut, $datefin)
    {
    	$retour = 0;    	
    	$liste_affectations = $this->affectationliste($datedebut, $datefin);
    	$nbaff = 0;
    	$nbjourstot = 0;
    	$errlog = '';
    	if (sizeof($liste_affectations) >= 1)
    	{
            $debutaffprec = null;
            $finaffprec = null;
            $tab = array();
            foreach($liste_affectations as $affectation)
            {	
                $nbaff ++;
                $debutaffectation = $this->fonctions->formatdatedb($affectation->datedebut());

                if (is_null($debutaffprec) && $debutaffectation > $datedebut)
                {
                    // quotite 0 entre $datedebut et débutaffectation
                    $nbjoursnoaff = $this->fonctions->nbjours_deux_dates($datedebut, $debutaffectation) - 1; // le jour de début de l'affectation sera compté lors du calcul de la durée d'affectation
                    $tab[$nbaff] = array('duree' => $nbjoursnoaff, 'quotite' => 0);
                    $nbaff++;
                    $errlog .= "1ere affectation ($debutaffectation) commence après le début de période $datedebut";
                    $nbjourstot += $nbjoursnoaff;
                }
                $debutaffprec = $debutaffectation;
                if ($debutaffectation <= $datedebut)
                {
                    $debutaffectation = $datedebut;
                }
                $finaffectation = $this->fonctions->formatdatedb($affectation->datefin());
                if ($finaffectation >= $datefin)
                {
                    $finaffectation = $datefin;
                }
                if (!is_null($finaffprec))
                {
                    // nombre de jours entre la fin de la dernière affectation et le début de la courante
                    if (!$this->fonctions->datesconsecutives($finaffprec, $debutaffectation))
                    {
                        $daysbetaff = $this->fonctions->nbjours_deux_dates($finaffprec, $debutaffectation) - 2; // le jour de la fin de l'affectation a déjà été compté et début de la suivante sera comptée ensuite
                        $tab[$nbaff] = array('duree' => $daysbetaff, 'quotite' => 0);
                        $nbaff++;
                        $errlog .= "affectation suivante $debutaffectation commence après fin affectation précédente $finaffprec. $daysbetaff jours entre les 2.";
                        $nbjourstot += $daysbetaff;
                    }
                }
                $finaffprec = $finaffectation;
                $nbjoursaff = $this->fonctions->nbjours_deux_dates($debutaffectation, $finaffectation);
                $nbjourstot += $nbjoursaff;
                $errlog .= "date deb $debutaffectation date fin $finaffectation nb jours $nbjoursaff ";
                $quotiteaff = $affectation->numquotite();
                $tab[$nbaff] = array('duree' => $nbjoursaff, 'quotite' => $quotiteaff);
                $retour += ($quotiteaff * $nbjoursaff);
            }
            $retour = $retour / $nbjourstot;
            $errlog .= "quotite $retour";
	    	
    	}
	    else 
	    {
	    	$errlog .= "Pas d'affectation : quotité 0 ";
	    }
	    if ($errlog != '')
	    	error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	return $retour;
    }
    
    function hasInterruptionAffectation($datedebut, $datefin)
    {
    	$retour = FALSE;
    	$errlog = '';
//    	echo "datedebut = $datedebut   datefin = $datefin <br>";
    	$liste_affectations = $this->affectationliste($datedebut, $datefin);
//    	echo "Liste_affectation = ";
//    	var_dump($liste_affectations);
//    	echo "<br>";
    	if (sizeof((array)$liste_affectations) >= 1)
    	{
            $debutaffprec = null;
            $finaffprec = null;
            foreach($liste_affectations as $affectation)
            {
                $debutaffectation = $this->fonctions->formatdatedb($affectation->datedebut());
//    		echo "debutaffectation = $debutaffectation <br>";
                if (is_null($debutaffprec) && $debutaffectation > $datedebut)
                {
                    $errlog .= "Pas d'affectation entre  le ".$this->fonctions->formatdate($datedebut)." et le ".$this->fonctions->formatdate($debutaffectation).". En cas d'erreur, contactez la DRH. ";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    return TRUE;
                }
                $debutaffprec = $debutaffectation;
                $finaffectation = $this->fonctions->formatdatedb($affectation->datefin());
//    		echo "debutaffprec = $debutaffprec <br>";
//    		echo "finaffectation = $finaffectation <br>";
                if (!is_null($finaffprec))
                {
                        // nombre de jours entre la fin de la dernière affectation et le début de la courante
//    			echo "Avant dateconsecutive => $finaffprec   $debutaffectation <br>";
                        if (!$this->fonctions->datesconsecutives($finaffprec, $debutaffectation))
                        {
                            $errlog .= "Pas d'affectation entre le ".$this->fonctions->formatdate($finaffprec)." et le ".$this->fonctions->formatdate($debutaffectation).". En cas d'erreur, contactez la DRH. ";
                            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                            return TRUE;
                        }
                }
                $finaffprec = $finaffectation;
//    		echo "finaffprec = $finaffprec";
            }    		
    	}
    	else
    	{
    		$errlog .= "Aucune affectation entre le ".$this->fonctions->formatdate($datedebut)." et le ".$this->fonctions->formatdate($datefin).". En cas d'erreur, contactez la DRH. ";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    		return TRUE;
    	}    	
    	if ($errlog != '')
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
//    	echo "Avant le return...<br>";
    	return $retour;
    }
    
    function getNbJoursConsommés($anneeref, $datedeb, $datefin)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated - Use method agent::getnbjoursconsommes instead', E_USER_DEPRECATED);
        return $this->getnbjoursconsommes($anneeref, $datedeb, $datefin);
    }

    function getnbjoursconsommes($anneeref, $datedeb, $datefin)
    {
    	$type_conge = 'ann'.substr($anneeref,2, 2);
    	$planning = $this->planning($this->fonctions->formatdate($datedeb), $this->fonctions->formatdate($datefin));
    	$errlog = "Type de demande $type_conge. date planning debut : ".$this->fonctions->formatdate($datedeb)." fin : ".$this->fonctions->formatdate($datefin);
    	//echo "<br><br>" . print_r($planning,true) . "<br><br>";
    	
    	$nbjours = 0;
    	foreach ($planning->planning() as $key => $element)
    	{
    		if ($element->type() == $type_conge)
    		{
    			$nbjours += 0.5;
    		}
    		elseif ($element->type() == 'atten')
    		{
    			$date_element = $this->fonctions->formatdatedb($element->date());
                //$list_demandes = $this->demandesliste($date_element, $date_element + 1);
                $timestamp = strtotime($date_element);
                $lendemain = date("Ymd", strtotime("+1 day", $timestamp)); // On passe au lendemain de la date
                $list_demandes = $this->demandesliste($date_element, $lendemain);
    			foreach($list_demandes as $demande)
    			{
    			    //if (($demande->type() == $type_conge) and (strcasecmp($demande->statut(), 'r')!=0) )
    			    //if (($demande->type() == $type_conge) and (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE)!=0))
    			    if (($demande->type() == $type_conge) and (strcasecmp($demande->statut(), demande::DEMANDE_REFUSE) != 0 and strcasecmp($demande->statut(), demande::DEMANDE_ANNULE) != 0))
    				{
    					$nbjours += 0.5;
    				}
    			}
    		}
    	}
    	// On ajoute le nombre de jours déposés sur le CET au titre de l'année de référence
    	$alimentationCET = new alimentationCET($this->dbconnect);
    	$list_id_alim = $this->getDemandesAlim($type_conge, array($alimentationCET::STATUT_VALIDE));
    	if (sizeof($list_id_alim) > 0)
    	{
    		$datedeb_db = $this->fonctions->formatdatedb($datedeb);
    		$datefin_db = $this->fonctions->formatdatedb($datefin);
    		foreach ($list_id_alim as $alimdid => $id_alim)
    		{
    			$alimentationCET->load(null,$alimdid);
    			$date_alim = $this->fonctions->formatdatedb($alimentationCET->datestatut());
    			if ($date_alim >= $datedeb_db && $date_alim <= $datefin_db)
    				$nbjours += $alimentationCET->valeur_f();
    		}
    	}
    	$errlog .= " $nbjours jours utilisés";
    	error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	return $nbjours;
    }
    
    /**
     *
     * @deprecated
    */
    function getResponsableForCET()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        
    	$pasresptrouve = false;
    	$structid = $this->structureid();
    	$struct = new structure($this->dbconnect);
    	$struct->load($structid);
    	$resp = $struct->responsable();
    	if (($resp->mail() . "") <> "")
    	{
    		if ($resp->agentid() == $this->agentid())
    		{
    			$structparent = $struct->parentstructure();
    			$resp = $structparent->responsable();
    			if (($resp->mail() . "") == "")
    			{
    				$pasresptrouve = true;
    			}
    		}
    	}
    	else
    	{
    		$pasresptrouve = true;
    	}
    	if ($pasresptrouve)
    	{
    		error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Il n'y a pas de responsable pour la structure " . $struct->nomlong()));
    	}
    	return $resp;
    }

    /**
     *
     * @param string $anneeref
     * @param array $listStatuts
     * @return array of esignatureid
     */
    function getDemandesOption($anneeref = '', $listStatuts = array())
    {
        $listdemandes = array();
        $optionCET = new optionCET($this->dbconnect);
        $sql = "SELECT ESIGNATUREID, OPTIONID FROM OPTIONCET WHERE AGENTID = ? ";

        if ($anneeref != '')
            $sql .= " AND ANNEEREF = '$anneeref' " ;
        if (sizeof($listStatuts) != 0)
        {
            $statuts = $this->fonctions->formatlistedb($listStatuts);
            $sql .=  " AND STATUT IN $statuts";
        }
        $params = array($this->agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id eSignature (droit d'option) : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Aucune demande de droit d'option pour l'agent " . $this->identitecomplete() . ".";
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $listdemandes[$result[1]] = $result[0];
            }
        }
        return $listdemandes;

    }
    
    // Synchronisation avec eSignature de l'ensemble des demandes d'alimentation et droit d'option sur CET de l'agent
    function synchroCET($typeconge = '', $anneeref = '')
    {
    	// Synchronisation des demande d'alimentation
    	$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE AGENTID = ? ";
    	if ($typeconge != '')
    	{
    		$sql .= " AND TYPECONGES = '$typeconge' " ;
    	}
    	$params = array($this->agentid);
    	$query = $this->fonctions->prepared_select($sql, $params);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
    	{
    		$errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande d'alimentation pour l'agent " . $this->identitecomplete() . ".";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	else
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/alimentationWS.php";
    		$full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
    		while ($result = mysqli_fetch_row($query))
    		{
    			$this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
    		}
    	}
    	
    	// Synchronisation des demandes d'option
    	$sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE AGENTID = ? ";
    	
    	if ($anneeref != '')
    	{
    		$sql .= " AND ANNEEREF = '$anneeref' " ;
    	}
    	$params = array($this->agentid);
    	$query = $this->fonctions->prepared_select($sql, $params);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
   		{
   			$errlog = "Problème SQL dans le chargement des id eSignature (droit d'option) : " . $erreur;
   			echo $errlog;
   		}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande de droit d'option pour l'agent " . $this->identitecomplete() . ".";
    		error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
    		//echo $errlog;
    	}
    	else
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/optionWS.php";
    		$full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
    		while ($result = mysqli_fetch_row($query))
    		{
    			$this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
    		}
    	}
    }

    // Synchronisation avec eSignature des conventions de télétravail de l'agent
    function synchroteletravail()
    {
        // Synchronisation des conventions de télétravail /// (sauf ANNULE)
        $sql = "SELECT ESIGNATUREID,TELETRAVAILID,STATUT FROM TELETRAVAIL WHERE AGENTID = ? AND ESIGNATUREID <> '' AND ESIGNATUREURL <> '' "; // AND STATUT NOT IN ('" . teletravail::TELETRAVAIL_ANNULE . "') " ;
        $params = array($this->agentid);
        $query = $this->fonctions->prepared_select($sql, $params, "s");
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Aucune convention de télétravail à synchroniser pour l'agent " . $this->identitecomplete() . ".";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return "";
        }
        else
        {
            $full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/teletravailWS.php";
            $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
            while ($result = mysqli_fetch_row($query))
            {
                $erreur = '';
                // On prend en compte le cas du null
                $esignatureid = trim($result[0] . "");
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" : On va traiter la demande id G2T =  " . $result[1] . " eSignature $esignatureid => statut actuel : " . $result[2]));
                if ($esignatureid != '')
                {
                    $erreur = $this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$esignatureid);
                    //echo "<br>synchroteletravail => $erreur <br>";
                    if ($erreur != "")
                    {
                        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" : On a rencontré une erreur sur la convention de télétravail id G2T = " . $result[1]  . " eSignature $esignatureid"));
                        return $erreur;
                    }
                }
            }
        }
        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" : On sort normalement de la synchroteletravail"));
        return "";
    }
    
    
    /**
     *
     * @param string $anneeref
     * @param boolean $maj_solde
     * @param boolean $loginfo
     * @param boolean $displayinfo
     * @return number of days
     */
    function calculsoldeannuel($anneeref = null, $maj_solde = true, $loginfo = false, $displayinfo = false)
    {

        if (date('Ymd') >= '20240901')
        {
            return $this->newcalculsoldeannuel($anneeref,$maj_solde,$loginfo,$displayinfo);
        }

        if ($loginfo == true) {
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" ###############################################################"));
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On est sur l'agent : " . $this->identitecomplete() . " (id = " . $this->agentid . ")"));
        }
        if ($displayinfo == true)
        {
            echo " ###############################################################\n";
            echo " On est sur l'agent : " . $this->identitecomplete() . " (id = " . $this->agentid . ")\n";
        }
        // Au départ l'agent à droit à 0 jours
        $solde_agent = 0;
        $DatePremAff = null;
        $cas_general = true;
        // Nombre de jours où l'agent a travaillé en continu
        $nbre_total_jours = 0;
        
        // La date de la précédente fin d'affectation est mise à null
        $datefinprecedenteaff = null;
        $datefinaff = null;
        $agentid = $this->agentid;
        
        if (is_null($anneeref))
        {
            $anneeref = $this->fonctions->anneeref();
        }
        
        $agentcomplement = new complement($this->dbconnect);
        $agentcomplement->load($this->agentid(),complement::FORCE_SOLDE_LABEL . $anneeref);
        // Si le complément existe et qu'on a réussi à le charger
        if ($agentcomplement->agentid()==$this->agentid())
        {
            if ($loginfo == true) 
            { 
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le solde $anneeref de l'agent " . $this->identitecomplete() . " est forcé => On ne fait pas de calcul."));
            }
            $solde = new solde($this->dbconnect);
            $msg_erreur = $solde->load($this->agentid(),"ann" . substr($anneeref,-2,2));
            if ($msg_erreur <> "")
            {
                echo $msg_erreur;
            }
            if ($loginfo == true) 
            { 
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le solde acquis est conservé à " . $solde->droitaquis() . " jours"));
            }
            return $solde->droitaquis();
        }

        // Construction des date de début et de fin de période (typiquement : 01/09/YYYY et 31/08/YYYY+1)
        $date_deb_period = $anneeref . $this->fonctions->debutperiode();
        $date_fin_period = ($anneeref + 1) . $this->fonctions->finperiode();
        if ($loginfo == true) { 
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" date_deb_period = $date_deb_period   date_fin_period = $date_fin_period"));
        }
        
        // Calcul du nombre de jours dans la période => Typiquement 365 ou 366 jours.
        $nbre_jour_periode = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
        if ($loginfo == true) { 
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" nbre_jour_periode = $nbre_jour_periode"));
        }
        
        // On charge le nombre de jours auquel un agent à droit sur l'année
        $nbr_jrs_offert = $this->fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
        if ($loginfo == true) { 
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" nbr_jrs_offert = $nbr_jrs_offert"));
        }
        
        // On prend toutes les affectations actives d'un agent, dont la date de début est inférieur à la fin de la période
        // Les affectations futures ne sont pas prises en compte dans le calcul du solde
        $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE AGENTID = ? AND OBSOLETE='N' AND DATEDEBUT < ? ORDER BY DATEDEBUT";
        $params = array($this->agentid,($anneeref + 1) . $this->fonctions->finperiode());
        $query_aff = $this->fonctions->prepared_select($sql, $params);

        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "SELECT FROM AFFECTATION (Full) => $erreur_requete <br>";
        }
        if (mysqli_num_rows($query_aff) != 0) // On a des d'affectations
        {
            while ($result_aff = mysqli_fetch_row($query_aff)) {
                if ($loginfo == true) { 
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" -----------------------------------------"));
                }
                if ($displayinfo == true)
                {
                    echo " -----------------------------------------\n";
                }
                    
                
                // Début de l'affectation courante
                $dateDebAff = $result_aff[1];
                if ($loginfo == true) { 
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" dateDebAff = $dateDebAff "));
                }
                if ($displayinfo == true)
                {
                    echo " dateDebAff = $dateDebAff \n";
                }
                
                // On mémorise la fin de cette affectation précédente avant qu'elle ne soit modifiée pour pouvoir tester la continuité des affectations avec l'affectation courante
                $datefinprecedenteaff = $datefinaff;
                if ($loginfo == true) { 
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" datefinprecedenteaff = $datefinprecedenteaff "));
                }
                if ($displayinfo == true)
                {
                    echo " datefinprecedenteaff = $datefinprecedenteaff \n";
                }
                
                // On parse la date de fin pour limiter la fin de la période si la date de fin n'est pas définie ou si elle est au dela de la période
                $datearray = date_parse($this->fonctions->formatdatedb($result_aff[2]));
                $year = $datearray["year"];
                if (($result_aff[2] == '0000-00-00') or ($this->fonctions->formatdatedb($result_aff[2]) > ($anneeref + 1) . $this->fonctions->finperiode())) 
                {
                    $datefinaff = ($anneeref + 1) . $this->fonctions->finperiode();
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La date de fin de l'affectation est " . $result_aff[2] . " ==> On la force à $datefinaff"));
                    }
                    if ($displayinfo == true)
                    {
                       echo " La date de fin de l'affectation est " . $result_aff[2] . " ==> On la force à $datefinaff \n";
                    }
                }
                else
                {
                    $datefinaff = $result_aff[2];
                }
                if ($loginfo == true) { 
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" datefinaff = $datefinaff"));
                }
                if ($displayinfo == true)
                {
                    echo " datefinaff = $datefinaff \n";
                }
                
                // Calcul de la quotité de l'agent sur cette affectation
                $quotite = $result_aff[3] / $result_aff[4];
                if ($loginfo == true) { 
                    error_log(basename(__FILE__) . $this->fonctions->stripAccents(" quotite = $quotite "));
                }
                if ($displayinfo == true)
                {
                    echo " quotite = $quotite \n";
                }
                
                // Si c'est la première affectation, on mémorise sa date de début
                if (is_null($DatePremAff)) 
                {
                    $DatePremAff = $result_aff[1];
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La date de première affectation est nulle => Maintenant elle vaut : $DatePremAff "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " La date de première affectation est nulle => Maintenant elle vaut : $DatePremAff \n";
                    }
                }
                    
                // Ce n'est pas un contrat ==> On calcule normalement
                if ($result_aff[5] == "0")
                {
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'affectation n'est pas un contrat ==> numcontrat = " . $result_aff[5] . " "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " L'affectation n'est pas un contrat ==> numcontrat = " . $result_aff[5] . " \n";
                    }
                    
                    // // On calcule le nombre de jours dans l'affectation dans le cas ou l'agent est en contrat pérenne puis repasse sur un contrat non pérenne
                    // $nbre_jour_aff = $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                    // echo "nbre_jour_aff = $nbre_jour_aff <br>";
                    
                    // Si la date de fin < date debut de la période, on ne s'en occupe pas car dans ce cas, seule les affectations de la période nous interressent
                    if ($this->fonctions->formatdatedb($datefinaff) < $this->fonctions->formatdatedb($date_deb_period))
                    {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Fin de l'affectation avant le début de la période ==> On ignore "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " Fin de l'affectation avant le début de la période ==> On ignore \n";
                        }
                        Continue;
                    }
                    
                    // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                    if ($this->fonctions->formatdatedb($dateDebAff) < $this->fonctions->formatdatedb($date_deb_period)) {
                        $dateDebAff = $date_deb_period;
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                        }
                    }
                    
                    // On calcule le nombre de jours dans l'affectation sur la période
                    $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" nbre_jour_aff_periode = $nbre_jour_aff_periode "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " nbre_jour_aff_periode = $nbre_jour_aff_periode \n";
                    }
                    
                    $solde_agent = $solde_agent + (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le solde de l'agent est de : $solde_agent "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " Le solde de l'agent est de : $solde_agent \n";
                    }
                }            // On est dans le cas d'un contrat
                else
                {
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On est dans le cas d'un contrat"));
                    }
                    if ($displayinfo == true)
                    {
                        echo " On est dans le cas d'un contrat \n";
                    }
                    // Si ce n'est pas la première affectation
                    if (! is_null($datefinprecedenteaff)) 
                    {
                        // Si il y a un trou entre la fin de l'affectation précédente et le début de l'actuelle, on mémorise sa date de début
                        // <=> La date de début de l'affectation courante correspond au lendemain de la fin de l'affectation précédente
                        if (date("Y-m-d", strtotime("+1 day", strtotime($datefinprecedenteaff))) != $result_aff[1]) 
                        {
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La date de début de la nouvelle affectation est : " . $result_aff[1] . ""));
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" La date de fin de la précédente affectation est : $datefinprecedenteaff "));
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Date du lendemain de la fin de la précédente affectation est : " . date("Y-m-d", strtotime("+1 day", strtotime($datefinprecedenteaff))) . " "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " La date de début de la nouvelle affectation est : " . $result_aff[1] . " \n";
                                echo " La date de fin de la précédente affectation est : $datefinprecedenteaff \n";
                                echo " Date du lendemain de la fin de la précédente affectation est : " . date("Y-m-d", strtotime("+1 day", strtotime($datefinprecedenteaff))) . " \n";
                            }
                            $DatePremAff = $result_aff[1];
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Il y a rupture dans la suite des affectations => On force la date de premiere affectation à $DatePremAff"));
                            }
                            if ($displayinfo == true)
                            {
                                echo " Il y a rupture dans la suite des affectations => On force la date de premiere affectation à $DatePremAff \n";
                            }
                        }
                        else
                        {
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Il y a continuité entre les affectations "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " Il y a continuité entre les affectations \n";
                            }
                        }
                    }
                    
                    // On calcule le nombre de jour écoulé depuis le début de la première affectation et la date de fin de cette affectation
                    $NbreJoursTotalAff = $this->fonctions->nbjours_deux_dates($DatePremAff, $datefinaff);
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent est affecté depuis $NbreJoursTotalAff jours en continue depuis le $DatePremAff jusqu'au $datefinaff... "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " L'agent est affecté depuis $NbreJoursTotalAff jours en continue depuis le $DatePremAff jusqu'au $datefinaff... \n";
                    }
                    
                    // Si la date de fin < date debut de la période, on ne s'en occupe pas car dans ce cas, seule les affectations de la période nous interressent
                    if ($this->fonctions->formatdatedb($datefinaff) < $this->fonctions->formatdatedb($date_deb_period)) {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Fin de l'affectation avant le début de la période ==> On ignore "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " Fin de l'affectation avant le début de la période ==> On ignore \n";
                        }
                        Continue;
                    }
                    
                    if ($loginfo == true) { 
                        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" RAPPEL : Le solde de l'agent actuellement est : $solde_agent "));
                    }
                    if ($displayinfo == true)
                    {
                        echo " RAPPEL : Le solde de l'agent actuellement est : $solde_agent \n";
                    }
                    
                    ///////
                    // On défini que 10 mois converti en jours => 305 jours <=> (365*10) / 12
                    $dixmoisenjours = 305;
                    
                    // L'agent est présent depuis plus d'un an à la fin de son affectation, donc on va calculer son solde avec les régles standards
                    // Attention cependant, il faut calculer le solde pour la période avant les 365 jours
                    if ($NbreJoursTotalAff > $dixmoisenjours) 
                    {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent a plus de $dixmoisenjours jours de présence en continue depuis le $DatePremAff jusqu'au $datefinaff.... "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " L'agent a plus de $dixmoisenjours jours de présence en continue depuis le $DatePremAff jusqu'au $datefinaff.... \n";
                        }
                        
                        // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                        if ($this->fonctions->formatdatedb($dateDebAff) < $this->fonctions->formatdatedb($date_deb_period))
                        {
                            $dateDebAff = $date_deb_period;
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                            }
                        }
                        
                        // Calcul du nombre de jours qui doivent être comptés à 2,5 jours
                        $NbreJours = $NbreJoursTotalAff - $this->fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" A la date de début de l'affectation " . $this->fonctions->formatdate($dateDebAff) . ", l'agent avait cumulé $NbreJours consécutifs "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " A la date de début de l'affectation " . $this->fonctions->formatdate($dateDebAff) . ", l'agent avait cumulé $NbreJours consécutifs \n";
                        }
                        // $NbreJours = $nbre_jour_periode - $NbreJours;
                        // echo "dateDebAff = $dateDebAff datefinaff = $datefinaff dif_date = " . $fonctions->nbjours_deux_dates ($dateDebAff, $datefinaff ) . " NbreJours = $NbreJours <br>";
                        // $NbreJours = $fonctions->nbjours_deux_dates ($dateDebAff, $datefinaff ) - $NbreJours;
                        //$NbreJours = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period) - $NbreJours;
                        $NbreJours = $dixmoisenjours - $NbreJours;
                        if ($NbreJours < 0)
                        {
                            $NbreJours = 0;
                        }
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Il y a $NbreJours jours à compter à 2,5 jours par mois soit : " . ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite) . " jours "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " Il y a $NbreJours jours à compter à 2,5 jours par mois soit : " . ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite) . " jours \n";
                        }
                        if ($NbreJours > 0)
                        {
                            $solde_agent = $solde_agent + ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite);
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" solde_agent = $solde_agent "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " solde_agent = $solde_agent \n";
                            }
                        }
                        
                        // Calcul du nombre de jours qui doivent être comptés comme un "non contrat"
                        // $NbreJours = $nbre_jour_periode - $NbreJours;
                        $NbreJours = $this->fonctions->nbjours_deux_dates($dateDebAff, $datefinaff) - $NbreJours;
                        if ($NbreJours < 0)
                        {
                            $NbreJours = 0;
                        }
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Il y a $NbreJours jours à compter à $nbr_jrs_offert jours par an soit : " . ((($nbr_jrs_offert * $NbreJours) / $nbre_jour_periode) * $quotite) . " jours "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " Il y a $NbreJours jours à compter à $nbr_jrs_offert jours par an soit : " . ((($nbr_jrs_offert * $NbreJours) / $nbre_jour_periode) * $quotite) . " jours \n";
                        }
                        if ($NbreJours > 0) 
                        {
                            $solde_agent = $solde_agent + ((($nbr_jrs_offert * $NbreJours) / $nbre_jour_periode) * $quotite);
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" solde_agent = $solde_agent "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " solde_agent = $solde_agent \n";
                            }
                        }
                    }
                    else  // Le nombre de jours est < à $dixmoisenjours (donc l'agent n'est pas présent depuis plus de 10 mois)
                    {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent n'a pas atteint les $dixmoisenjours jours consécutifs => On calcule à 2,5 jours par mois "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " L'agent n'a pas atteint les $dixmoisenjours jours consécutifs => On calcule à 2,5 jours par mois \n";
                        }
                        // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                        if ($this->fonctions->formatdatedb($dateDebAff) < $this->fonctions->formatdatedb($date_deb_period)) 
                        {
                            $dateDebAff = $date_deb_period;
                            if ($loginfo == true) { 
                                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff "));
                            }
                            if ($displayinfo == true)
                            {
                                echo " le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                            }
                        }
                        // Calcul du nombre de jours qui doivent être comptés à 2,5 jours sur la période de l'affectation
                        $NbreJours = $this->fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                        $solde_agent = $solde_agent + ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite);
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" solde_agent = $solde_agent "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " solde_agent = $solde_agent \n";
                        }
                    }
                }
            }
        }
        if ($solde_agent > 0) 
        {
            $partie_decimale = $solde_agent - floor($solde_agent);
            $agentinfo = $this->identitecomplete();
            if ($loginfo == true) { 
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Code Agent = $agentid ($agentinfo)    solde_agent = $solde_agent     partie_decimale =  $partie_decimale     entiere = " . floor($solde_agent) . "          "));
            }
            if ($displayinfo == true)
            {
                echo " Code Agent = $agentid ($agentinfo)    solde_agent = $solde_agent     partie_decimale =  $partie_decimale     entiere = " . floor($solde_agent) . "          \n";
            }
            if ((float) $partie_decimale < (float) 0.25)
            {
               $solde_agent = floor($solde_agent);
            }
            elseif ((float) ($partie_decimale >= (float) 0.25) && ((float) $partie_decimale < (float) 0.75))
            {
               $solde_agent = floor($solde_agent) + (float) 0.5;
            }
            else
            {
               $solde_agent = floor($solde_agent) + (float) 1;
            }
            if ($loginfo == true) { 
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" apres traitement : $solde_agent "));
            }
            if ($displayinfo == true)
            {
                echo " apres traitement : $solde_agent \n";
            }
        }
        if ($loginfo == true) { 
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le solde final est donc : $solde_agent "));
        }
        if ($displayinfo == true)
        {
            echo " Le solde final est donc : $solde_agent \n";
        }
        if ($maj_solde == true)
        {
            if ($loginfo == true) {
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On met à jour le solde de l'agent dans la base de données"));
            }
            $typeabsenceid = "ann" . substr($anneeref, 2, 2);
            $sql = "SELECT AGENTID,TYPEABSENCEID FROM SOLDE WHERE AGENTID= ? AND TYPEABSENCEID= ? ";
            $params = array($this->agentid,$typeabsenceid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur_requete = mysqli_error($this->dbconnect);
            if ($erreur_requete != "")
            {
                echo "SELECT AGENTID,TYPEABSENCEID FROM CONGE => $erreur_requete <br>";
            }
            if (mysqli_num_rows($query) != 0) // le type annXX existe déja => On le met à jour
            {
                $sql = "UPDATE SOLDE SET DROITAQUIS= ? WHERE AGENTID= ? AND TYPEABSENCEID= ?";
                $params = array($solde_agent, $this->agentid, $typeabsenceid);
            }
            else
            {
                $sql = "INSERT INTO SOLDE(AGENTID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES(?,?,?,'0')";
                $params = array($this->agentid,$typeabsenceid,$solde_agent);

            }
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur_requete = mysqli_error($this->dbconnect);
            if ($erreur_requete != "")
            {
                echo "INSERT ou UPDATE CONGE => $erreur_requete <br>";
            }
        }
        return ($solde_agent);
        
    }

    function teletravailliste($datedebut, $datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        
        $listteletravail = array();
        $sql = "SELECT TELETRAVAILID 
                FROM TELETRAVAIL 
                WHERE AGENTID = ? 
                  AND ((DATEDEBUT <= ? AND DATEFIN >= ? )
                    OR (DATEFIN >= ? AND DATEDEBUT <= ? )
                    OR (DATEDEBUT >= ? AND DATEFIN <= ? ))
                ORDER BY DATEDEBUT,DATEFIN";
        
        $params = array($this->agentid,$datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id teletravail : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            //$errlog = "Aucune demande de télétravail pour l'agent " . $this->identitecomplete() . "<br>";
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $listteletravail[] = $result[0];
            }
        }
        return $listteletravail;    
    }
    
    function estenteletravail($date, $moment = null, $teletravailliste = null)
    {
        $date = $this->fonctions->formatdatedb($date);
        if (is_null($teletravailliste))
        {
            $liste = $this->teletravailliste($date, $date);
        }
        else
        {
            $liste = $teletravailliste;
        }
        $reponse = false;
        $exclusion  = $this->estjourteletravailexclu($date,$moment);  //listejoursteletravailexclus($date, $date);
        foreach ($liste as $teletravailid)
        {
            $teletravail = new teletravail($this->dbconnect);
            $teletravail->load($teletravailid);
            if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE)
            {
                if ($teletravail->estteletravaille($date,$moment) and  !$exclusion) // (array_search($date,(array)$exclusion)===false))  // Si c'est un jour de télétravail et qu'il n'est pas exclu
                {
                    $reponse = true;
                }
            }
        }
        return $reponse;
    }
    
    function nbjoursteletravail($datedebut, $datefin, $reel = true)
    {
        $planning = new planning($this->dbconnect);
        return $planning->nbjoursteletravail($this->agentid, $datedebut, $datefin, $reel);
    }
    
    function ajoutjoursteletravailexclus($dateorigine, $momentorigine, $dateremplacement = '', $momentremplacement = '')
    {
        return $this->fonctions->ajoutjoursteletravailexclus($this->agentid, $dateorigine, $momentorigine, $dateremplacement ,$momentremplacement);
    }
    
    function listejoursteletravailexclus($datedebut,$datefin)
    {
        return $this->fonctions->listejoursteletravailexclus($this->agentid, $datedebut,$datefin);
    }

    function supprjourteletravailexclu($date, $moment)
    {
        return $this->fonctions->supprjourteletravailexclu($this->agentid,$date, $moment );
    }
    
    function estjourteletravailexclu($date, $moment)
    {
        return $this->fonctions->estjourteletravailexclu($this->agentid,$date, $moment);
    }
    
    function historiqueaffectation($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        
        $listhistorique = array();
        $sql = "SELECT STRUCTUREID,DATEDEBUT,DATEFIN
                FROM HISTORIQUEAFFECTATION
                WHERE AGENTID = ?
                  AND ((DATEDEBUT <= ? AND DATEFIN >= ? )
                    OR (DATEFIN >= ? AND DATEDEBUT <= ? )
                    OR (DATEDEBUT >= ? AND DATEFIN <= ? ))
                ORDER BY DATEDEBUT,DATEFIN";
        
        $params = array($this->agentid,$datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement de l'historique d'affectation : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>historiqueaffectation => pas de ligne dans la base de données<br>";
            //$errlog = "Aucun historique d'affectation n'existe pour l'agent " . $this->identitecomplete() . " dans la période $datedebut -> $datefin <br>";
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $histo = array();
                $histo['structureid'] = $result[0];
                $histo['datedebut'] = $result[1];
                $histo['datefin'] = $result[2];
                $listhistorique[] = $histo;
            }
        }
        return $listhistorique;
    }
        
    function historiquesituationadmin($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        
        $listhistorique = array();
        $sql = "SELECT POSITIONADMIN,DATEDEBUT,DATEFIN
                FROM SITUATIONADMIN
                WHERE AGENTID = ?
                  AND ((DATEDEBUT <= ? AND DATEFIN >= ? )
                    OR (DATEFIN >= ? AND DATEDEBUT <= ? )
                    OR (DATEDEBUT >= ? AND DATEFIN <= ? ))
                ORDER BY DATEDEBUT,DATEFIN";
        
        $params = array($this->agentid,$datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement de l'historique des situation : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>historiqueaffectation => pas de ligne dans la base de données<br>";
            //$errlog = "Aucun historique d'affectation n'existe pour l'agent " . $this->identitecomplete() . " dans la période $datedebut -> $datefin <br>";
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $histo = array();
                $histo['positionadmin'] = $result[0];
                $histo['datedebut'] = $result[1];
                $histo['datefin'] = $result[2];
                $listhistorique[] = $histo;
            }
        }
        return $listhistorique;
    }
    
    /**
     * $fromstruct permet de spécifier à partir de quelle structure on doit chercher le responsable<br>
     * C'est nécessaire lorsqu'on cherche un responsable d'un responsable (donc N+2) car le responsable<br>
     * n'est pas forcément affecté ou affecté dans la bonne structure<br>
     * Pour un agent (non responsable) on peut passer la structure de l'agent ou null<br>
     * Si null => initialisé à partir de la structureid de l'agent<br>
     * 
     * @param structure $fromstruct
     * @param structure $structresp
     * @param number $codeinterne
     * @return agent responsable ou false
     */
    function getsignataire($fromstruct = null, &$structresp = null, &$codeinterne = null)
    {
        error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("On cherche le N+1 de " . $this->identitecomplete()));

        $pasresptrouve = false;
        $codeinterne = null;
        $structresp = null;
        if (!is_null($fromstruct) and is_a($fromstruct, 'structure'))
        {
            $struct = $fromstruct;
            $structid = $fromstruct->id();
        }
        else
        {
            $structid = $this->structureid();
            $struct = new structure($this->dbconnect);
            if (!$struct->load($structid))
            {
                // Si on ne peut pas charger la structure => On ne peut pas définir le responsable de l'agent
                error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Impossible de charger la structure (id = $structid) => " . $struct->nomlong()));
                return false;
            }
        }
    	$resp = $struct->responsable();
        // Si on n'a pas récupérer de responsable de la structure
        // l'adresse mail du responsable est vide

        $respsiham = $struct->responsablesiham();
        //var_dump("Le responsable est : " . $resp->mail() . "<br>");
        //var_dump("Le responsable SIHAM est : " . $respsiham->mail() . "<br>");

        // Si l'agent est responsable mais qu'il n'est pas le responsable SIHAM => Il y a délégation
        if ($resp->agentid() == $this->agentid() and $resp->agentid() != $respsiham->agentid())
        {
            // On va regarder si le responsable SIHAM doit continuer d'être notifié des demandes de congés/CET/....
            // Donc on charge la délégation de la structure
            //var_dump("On est dans le cas ou l'agent est responsable mais n'est pas le responsable SIHAM");
            $delegation = $struct->getdelegation(true);
            //var_dump("Le flag de la délégation indique : " . $delegation->continuesendtoresp);
            if ($this->fonctions->convertvaluetobool($delegation->continuesendtoresp))
            {
                // Le responsable continue d'être notifié donc on dit que le responsable est le responsable SIHAM
                $resp = $respsiham;
                //var_dump("Le responsable devient le responsable SIHAM => " . $resp->identitecomplete());
            }
        }

    	if (($resp->mail() . "") <> "")
    	{
            // Si le responsable de la structure est l'agent courant => L'agent est responsable
            if ($resp->agentid() == $this->agentid() or $respsiham->agentid() == $this->agentid())
            {
                $resp = $struct->resp_envoyer_a($codeinterne,false);
                if ($codeinterne==structure::MAIL_RESP_ENVOI_GEST_COURANT)
                {
                    // Le responsable (qui est le gestionnaire de la structure courante) est dans la structure courante
                    $structresp = $struct;
                }
                else if ($codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT or $codeinterne==structure::MAIL_RESP_ENVOI_RESP_PARENT)
                {
                    // Le responsable est dans la structure parente si la structure de l'agent est inclue dans la structure parente
                    $structresp = $struct->parentstructure();
                }
            }
            // L'agent est n'est pas le responsable de la structure
            else
            {

                // Dans le cas ou on veut déterminer le responsable d'un agent, ce responsable est forcément dans la structure de l'agent
                $resp = $struct->agent_envoyer_a($codeinterne,false);
                // Attention : Dans le cas de la délégation avec l'option continuesendtoresp activée 
                // => Le responsable est le délégué (à cause de struct->responable() dans la fonction struct::agent_envoyer_a)
                // Or on fait tout pour celui ci ne soit pas considéré comme responsable => On reforce le responsable au responsable SIHAM
                if (!is_null($resp) and $resp->agentid()==$this->agentid())
                {
                    $resp = $respsiham;
                }
                $structresp = $struct;
            }
            // Si le responsable est null ou si c'est le CRON ou si le mail est vide
            if (is_null($resp) or $resp->agentid()==SPECIAL_USER_IDCRONUSER or $resp->mail()."" == "")
            {
                $pasresptrouve = true;
            }
    	}
    	else
    	{
            $pasresptrouve = true;
    	}
        
    	if ($pasresptrouve)
    	{
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Il n'y a pas de responsable pour la structure " . $struct->nomlong()));
            $resp = false;
    	}
        if ($resp!==false)
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Le N+1 de " . $this->identitecomplete() . " est " . $resp->identitecomplete()));
        }
        //var_dump("Resp = " . $resp->identitecomplete() . " <br>");
    	return $resp;
    }


    
    function getsignataire_niveau2(&$respdurespstruct = null, &$codeinterne = null)
    {
        $MODE_AGENT=1;
        $MODE_RESP=2;

        error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("On cherche le N+2 de " . $this->identitecomplete()));
        
    	$structid = $this->structureid();
    	$struct = new structure($this->dbconnect);
    	if (!$struct->load($structid))
        {
            // Si on ne peut pas charger la structure => On ne peut pas définir le responsable de l'agent
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Impossible de charger la structure (id = $structid) => " . $struct->nomlong()));
            return false;
        }
    	$struct_resp = $struct->responsable();
        if ($struct_resp->agentid() == $this->agentid())
        {
            $mode = $MODE_RESP;
        }
        else
        {
            $mode = $MODE_AGENT;
        }

        // Le N+2 d'un agent est le responsable de son responsable
        $respstruct = null;
        $codeinterne = null;
        $resp = $this->getsignataire(null, $respstruct, $codeinterne);
        
        if ($resp===false or is_null($resp))
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Impossible de déterminer le responsable+2 de l'agent car impossible de déterminer le responsable+1"));
            return false;
        }
        if ($mode==$MODE_AGENT and $codeinterne==structure::MAIL_AGENT_ENVOI_GEST_COURANT)
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("En mode AGENT, on renvoit les demandes vers le gestionnaire => Pas de responsable+2"));
            return false;
        }
        if ($mode==$MODE_RESP and ($codeinterne==structure::MAIL_RESP_ENVOI_GEST_COURANT or $codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT))
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("En mode RESPONSABLE, on renvoit les demandes vers un gestionnaire => Pas de responsable+2"));
            return false;
        }
        // On sait que le responsable n'est pas un gestionnaire
        // Donc on va chercher son responsable
        $respduresp=$resp->getsignataire($respstruct, $respdurespstruct, $codeinterne);
        if ($respduresp===false or is_null($respduresp))
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Impossible de déterminer le responsable du responsable " . $resp->agentid() . " => Donc pas de N+2"));
            return false;
        }
        // On récupère les strucutures inclues dans la structure du responsable du responsable
        $tabstructure = $respdurespstruct->structureinclue();
        // On regarde si la structure du reponsable est dans la liste
        if (!isset($tabstructure[$respstruct->id()]) and $respstruct->id()!=$respdurespstruct->id())
        {
            error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("La structure " . $respstruct->id() . " du responsable " . $resp->identitecomplete() . " n'est pas inclue dans la structure parente " . $respdurespstruct->id() . " => Donc pas de N+2"));
            return false;
        }
        error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("La structure " . $respstruct->id() . " du responsable " . $resp->identitecomplete() . " est inclue dans la structure parente " . $respdurespstruct->id() . " ou c'est la même => On a un N+2"));
        error_log( basename(__FILE__) . " " . $this->fonctions->stripAccents("Le N+2 de l'agent " . $this->agentid() . " est " . $respduresp->agentid()));
        return $respduresp;
    }


    /**
     *
     * @param string $anneeref
     * @param boolean $maj_solde
     * @param boolean $loginfo
     * @param boolean $displayinfo
     * @return number of days
     */
    function newcalculsoldeannuel($anneeref = null, $maj_solde = true, $loginfo = false, $displayinfo = false)
    {
        $this->fonctions->log_traces($loginfo,$displayinfo,"###########################################");
        $this->fonctions->log_traces($loginfo,$displayinfo,"Calcul solde (fonction " . __METHOD__ .") de l'agent : " . $this->identitecomplete() . " - id : " . $this->agentid());

        $datefinaff = '19000101'; // On initialise la date de fin du contrat précédent au 01/01/1900 (=> très loin dans le passé)
        $solde_agent = 0;
        
        if (is_null($anneeref))
        {
            $anneeref = $this->fonctions->anneeref();
        }
        
        $agentcomplement = new complement($this->dbconnect);
        $agentcomplement->load($this->agentid(),complement::FORCE_SOLDE_LABEL . $anneeref);
        // Si le complément existe et qu'on a réussi à le charger
        if ($agentcomplement->agentid()==$this->agentid())
        {
            $this->fonctions->log_traces($loginfo,$displayinfo,"Le solde $anneeref de l'agent " . $this->identitecomplete() . " est forcé => On ne fait pas de calcul.");
            $solde = new solde($this->dbconnect);
            $msg_erreur = $solde->load($this->agentid(),"ann" . substr($anneeref,-2,2));
            if ($msg_erreur <> "")
            {
                echo $msg_erreur;
            }
            $this->fonctions->log_traces($loginfo,$displayinfo,"Le solde acquis est conservé à " . $solde->droitaquis() . " jours");
            $this->fonctions->log_traces($loginfo,$displayinfo,"###########################################");
            return $solde->droitaquis();
        }
        
        
        // Construction des date de début et de fin de période (typiquement : 01/09/YYYY et 31/08/YYYY+1)
        $date_deb_period = $anneeref . $this->fonctions->debutperiode();
        $date_fin_period = ($anneeref + 1) . $this->fonctions->finperiode();
        $this->fonctions->log_traces($loginfo,$displayinfo,"date_deb_period = $date_deb_period   date_fin_period = $date_fin_period");

        // Calcul du nombre de jours dans la période => Typiquement 365 ou 366 jours.
        $nbre_jour_periode = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
        $this->fonctions->log_traces($loginfo,$displayinfo,"nbre_jour_periode = $nbre_jour_periode");

        // On prend toutes les affectations actives d'un agent, dont la date de début est inférieur à la fin de la période
        // Les affectations futures ne sont pas prises en compte dans le calcul du solde
        //$sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE AGENTID = ? AND OBSOLETE='N' AND DATEDEBUT < ? ORDER BY DATEDEBUT";
        //$params = array($this->agentid,($anneeref + 1) . $this->fonctions->finperiode());
        
        // On prend toutes les affectations actives d'un agent comprises dans la période de référence
        // => On exclu celles dont la date de début est après la fin de période ou la date de fin est avant le début de la période
        $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT "
             . "FROM AFFECTATION "
             . "WHERE AGENTID = ? "
               . "AND OBSOLETE='N' "
               . "AND AFFECTATIONID NOT IN ( "
                   . "SELECT AFF2.AFFECTATIONID "
                   . "FROM AFFECTATION AFF2 "
                   . "WHERE AFF2.AGENTID = AFFECTATION.AGENTID "
                     . "AND (AFF2.DATEDEBUT > ? OR AFF2.DATEFIN < ?) "
               . ") "
             . "ORDER BY DATEDEBUT";
        $params = array($this->agentid,($anneeref + 1) . $this->fonctions->finperiode(),$anneeref . $this->fonctions->debutperiode());
        
        $query_aff = $this->fonctions->prepared_select($sql, $params);

        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "SELECT FROM AFFECTATION (Full) => $erreur_requete <br>";
        }
        
        if (mysqli_num_rows($query_aff) == 0) // On n'a pas d'affectations sur la période de référence
        {
            $this->fonctions->log_traces($loginfo,$displayinfo,"Aucune affectation sur la période => Aucun calcul de solde pour " . $this->identitecomplete());
        }
        else
        {
            while ($result_aff = mysqli_fetch_row($query_aff)) 
            {
                $this->fonctions->log_traces($loginfo,$displayinfo,"----------------------------------");
                $datedebutaff = $this->fonctions->formatdatedb($result_aff[1]);
                $datefinaff = $this->fonctions->formatdatedb($result_aff[2]);
                $this->fonctions->log_traces($loginfo,$displayinfo,"Valeurs initiales de l'affectation : datedebutaff = " . $this->fonctions->formatdate($datedebutaff) . "  datefinaff = " . $this->fonctions->formatdate($datefinaff));

                if (($datefinaff == '00000000') or ($datefinaff > $date_fin_period))
                {
                    $datefinaff = $date_fin_period;
                }
                // Si la date de début est avant le début de la période et que la date de fin est après le début de la période 
                // on la fixe au début de la période <=> Les dates avant le début de la période sont ignorées
                if ($datedebutaff < $date_deb_period and $datefinaff >= $date_deb_period)
                {
                    $datedebutaff = $date_deb_period;
                }
                $this->fonctions->log_traces($loginfo,$displayinfo,"Valeurs finales de l'affectation : datedebutaff = " . $this->fonctions->formatdate($datedebutaff) . "  datefinaff = " . $this->fonctions->formatdate($datefinaff));
                
                // Si la date de fin de l'affectation est avant la période, on l'ignore
                if ($datefinaff < $date_deb_period)
                {
                    // Comme on a filtré dans la requête pour ne prendre que les affectations inclues dans la période, en théorie, on ne passe jamais ici
                    $this->fonctions->log_traces($loginfo,$displayinfo,"La date de fin de l'affectation est avant la période, on l'ignore");
                    continue;
                }

                // On calcule le nombre de jours dans l'affectation dans la période
                $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($datedebutaff, $datefinaff);
                $this->fonctions->log_traces($loginfo,$displayinfo,"datedebutaff = " . $this->fonctions->formatdate($datedebutaff) . "   datefinaff = " . $this->fonctions->formatdate($datefinaff) . " => L'agent est affecté $nbre_jour_aff_periode jours");
                
                // Calcul de la quotité de l'agent sur cette affectation
                $quotite = $result_aff[3] / $result_aff[4];
                $numcontrat = intval('0' .$result_aff[5]);
                $this->fonctions->log_traces($loginfo,$displayinfo,"quotite = $quotite  numcontrat = $numcontrat");
                
                // Ce n'est pas un contrat ==> On calcule comme les titulaires
                if ($numcontrat == "0")
                {
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Mode de calcul 'titulaire'");
                    
                    // On charge le nombre de jours auquel un agent à droit sur l'année
                    $nbr_jrs_offert = $this->fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Pour un temps complet sur toute la période, un agent a droit à $nbr_jrs_offert jours");

                    // On calcule le nombre de jours que l'agent a acquis
                    $solde_aff = (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                    // Le solde de l'agent est modfié
                    $solde_agent = $solde_agent + $solde_aff;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Solde calculé pour cette affectation => $solde_aff    nouveau solde de l'agent = $solde_agent");
                }
                // C'est un contrat => $numcontrat > 0
                else
                {
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Mode de calcul 'contractuel'");
                    
                    if (date("Ym",strtotime($datedebutaff)) == date("Ym",strtotime($datefinaff)))
                    {
                        // En mode contractuel => Si le couple mois/année est le même au début et à la fin de l'affectation
                        // => On prend le nombre de jours entre les deux dates ($nbre_jour_aff_periode calculé précédement) et on fait le prorata avec
                        //    le nombre de jours du mois concerné
                        $moiscourant = date("m",strtotime($datedebutaff));
                        $anneecourante = date("Y",strtotime($datedebutaff));
                        $nbjoursmois = $this->fonctions->nbr_jours_dans_mois($moiscourant,$anneecourante);
                        $solde_aff = (($nbre_jour_aff_periode * 2.5) / $nbjoursmois) * $quotite;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Mois de début et de fin égaux => Solde calculé avec le nombre de jours (nbjoursmois = $nbjoursmois)=> $solde_aff ");
                    }
                    else
                    {
                        // On a un couple mois/année différent entre la date de début et de fin d'affectation. Donc l'affectation se déroule sur plusieurs mois
                        
                        // On calcule le nombre de jours que l'agent a fait sur le premier mois d'affectation
                        // On détermine le dernier jour du mois de début d'affectation (concaténation YYYY + MM + nbre de jours dans le mois)
                        $moiscourant = date("m",strtotime($datedebutaff));
                        $anneecourante = date("Y",strtotime($datedebutaff));
                        // On détermine le nombre de jours qu'il y a dans le mois de début d'affectation
                        $nbjoursmois = $this->fonctions->nbr_jours_dans_mois($moiscourant,$anneecourante);
                        $dernierjourmois = $anneecourante . $moiscourant . $nbjoursmois;
                        
                        // On calcule le nombre de jours qu'il y a entre la date de début d'affectation et le dernier jour du mois
                        $nbjours = $this->fonctions->nbjours_deux_dates($datedebutaff, $dernierjourmois);
                        // On calcule le nombre de jours de congés que l'agent à acquis sur le premier mois
                        $droitacquis = (($nbjours * 2.5) / $nbjoursmois) * $quotite;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"nbjours (premier mois) = $nbjours  nbjoursmois = $nbjoursmois   Solde calculé pour le premier mois => $droitacquis ");
                        $solde_aff = $droitacquis;


                        // On calcule combien il y a de mois complets dans cette affectation entre 
                        //    le premier jour du mois suivant de début de l'affectation 
                        //    et le dernier jour du mois précédent la fin de l'affectation
                        $premierjourdumoissuivant = $this->fonctions->premierjourdumoissuivant($datedebutaff);
                        $moisdebut = date("m",strtotime($premierjourdumoissuivant));
                        $anneedebut = date("Y",strtotime($premierjourdumoissuivant));
                        $dernierjourmoisprecedent = $this->fonctions->dernierjourmoisprecedent($datefinaff);
                        $moisfin = date("m",strtotime($dernierjourmoisprecedent));
                        $anneefin = date("Y",strtotime($dernierjourmoisprecedent));
                        // On doit prendre en compte le fait qu'on ait commencé une nouvelle année ou pas
                        if ($moisdebut < $moisfin)
                        {
                            $nbmoiscomplet = (12 * ($anneefin - $anneedebut)) + ($moisfin - $moisdebut) + 1;
                        }
                        else
                        {
                            $nbmoiscomplet = (12 * ($anneefin - $anneedebut - 1)) + (12 - $moisdebut + $moisfin) + 1;
                        }
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Il y a $nbmoiscomplet mois complets entre " . $this->fonctions->formatdate($this->fonctions->premierjourdumoissuivant($datedebutaff)) . " et " . $this->fonctions->formatdate($this->fonctions->dernierjourmoisprecedent($datefinaff)));
                        
                        if ($nbmoiscomplet > 0)
                        {
                            $droitacquis = ($nbmoiscomplet * 2.5) * $quotite;
                            $this->fonctions->log_traces($loginfo,$displayinfo,"Solde calculé pour les mois complets de cette affectation => $droitacquis ");
                            $solde_aff = $solde_aff + $droitacquis;
                        }

                        // On calcule le nombre de jours que l'agent a fait sur le dernier mois
                        // On détermine le premier jours du mois de fin d'affectation (concaténation YYYY + MM + '01')
                        $moiscourant = date("m",strtotime($datefinaff));
                        $anneecourante = date("Y",strtotime($datefinaff));
                        $premierjourmois = $anneecourante . $moiscourant . '01';
                        
                        // On calcule le nombre de jours qu'il y a entre la date de début du mois et le dernier jour d'affectation
                        $nbjours = $this->fonctions->nbjours_deux_dates($premierjourmois,$datefinaff);
                        // On détermine le nombre de jours qu'il y a dans le mois de fin d'affectation
                        $nbjoursmois = $this->fonctions->nbr_jours_dans_mois($moiscourant,$anneecourante);
                        // On calcule le nombre de jours de congés que l'agent à acquis sur le dernier mois
                        $droitacquis = (($nbjours * 2.5) / $nbjoursmois) * $quotite;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"nbjours (dernier mois) = $nbjours  nbjoursmois = $nbjoursmois   Solde calculé sur le dernier mois => $droitacquis ");
                        $solde_aff = $solde_aff + $droitacquis;
                    }
                    
                    // Le solde de l'agent est modfié
                    $solde_agent = $solde_agent + $solde_aff;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Solde calculé pour cette affectation => $solde_aff  ancien solde de l'agent = " . ($solde_agent - $solde_aff)  . "  nouveau solde de l'agent = $solde_agent");
                }
            }
        }

        $this->fonctions->log_traces($loginfo,$displayinfo,"Le solde calculé est : $solde_agent");
        if ($solde_agent > 0) 
        {
            $partie_decimale = $solde_agent - floor($solde_agent);
            if ((float) $partie_decimale < (float) 0.25)
            {
               $solde_agent = floor($solde_agent);
            }
            elseif ((float) ($partie_decimale >= (float) 0.25) && ((float) $partie_decimale < (float) 0.75))
            {
               $solde_agent = floor($solde_agent) + (float) 0.5;
            }
            else
            {
               $solde_agent = floor($solde_agent) + (float) 1;
            }
        }
        $this->fonctions->log_traces($loginfo,$displayinfo,"Pour l'agent " . $this->identitecomplete() . " le solde arrondi est de : $solde_agent jours" );

        if ($maj_solde == true)
        {
            $typeabsenceid = "ann" . substr($anneeref, 2, 2);
            $sql = "SELECT AGENTID,TYPEABSENCEID FROM SOLDE WHERE AGENTID= ? AND TYPEABSENCEID= ? ";
            $params = array($this->agentid,$typeabsenceid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur_requete = mysqli_error($this->dbconnect);
            if ($erreur_requete != "")
            {
                echo "SELECT AGENTID,TYPEABSENCEID FROM CONGE => $erreur_requete <br>";
            }
            if (mysqli_num_rows($query) != 0) // le type annXX existe déja => On le met à jour
            {
                $sql = "UPDATE SOLDE SET DROITAQUIS= ? WHERE AGENTID= ? AND TYPEABSENCEID= ?";
                $params = array($solde_agent, $this->agentid, $typeabsenceid);
            }
            else
            {
                $sql = "INSERT INTO SOLDE(AGENTID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES(?,?,?,'0')";
                $params = array($this->agentid,$typeabsenceid,$solde_agent);

            }
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur_requete = mysqli_error($this->dbconnect);
            if ($erreur_requete != "")
            {
                echo "INSERT ou UPDATE CONGE => $erreur_requete <br>";
            }
        }
        $this->fonctions->log_traces($loginfo,$displayinfo,"###########################################");
        return ($solde_agent);
    }

    function listedemandeteletravailenattente()
    {
        $tabteletravail = array();
        $sql = "SELECT TELETRAVAIL.TELETRAVAILID 
                FROM TELETRAVAIL
                WHERE TELETRAVAIL.AGENTID = ?
                  AND TELETRAVAIL.STATUTRESPONSABLE = ?
                  AND TELETRAVAIL.STATUT = ?";
        $params = array($this->agentid(),teletravail::TELETRAVAIL_ATTENTE,teletravail::TELETRAVAIL_ATTENTE);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->listedemandeteletravailenattente : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $teletravail = new teletravail($this->dbconnect);
            $teletravail->load($result[0]);
            $tabteletravail["" . $result[0]] = $teletravail;
            unset($teletravail);
        }
        return $tabteletravail;
    }
    
    function listeagentengestion($datedebut,$datefin, $structure = null)
    {
        $agentlistefull = array();
        if ($this->estgestionnaire())
        {
            if (is_null($structure))
            {
                // On récupère la liste des structures en gestion
                $structureliste = $this->structgestliste();
                // On récupère la liste des structures où l'agent (donc le gestionnaire) gère les congés (des agents et/ou du responsable)
                $listegeststruct = $this->structgestcongeliste();
                //foreach((array)$listegeststruct as $tmpstruct) { var_dump(__METHOD__ . ' ' . $tmpstruct->id() . ' ' . $tmpstruct->nomcourt()); }
                $structureliste = array_merge((array)$structureliste,(array)$listegeststruct);
                if (is_array($structureliste))
                {
                    uasort($structureliste,"triparprofondeurabsolue");
                }
            }
            else
            {
                // On ne traite que la structure passée en paramètre
                $structureliste[] = $structure;
                // On récupère la liste des structures où l'agent (donc le gestionnaire) gère les congés (des agents et/ou du responsable)
                $listegeststruct = $this->structgestcongeliste();
            }
            foreach ($structureliste as $structure) 
            {
                // Si on ne doit pas gérer les demandes des agents de cette structure
                if (strcasecmp($structure->gestvalidagent(),'n')==0
                        and array_key_exists($structure->id(),(array)$listegeststruct)===false)
                {
                    // On passe à la structure suivante
                    //var_dump("On passe à la structure suivante => Struct courante : " . $structure->nomcourt());
                    continue;
                }
                $gestionnaire = $structure->gestionnaire();
                if (is_null($gestionnaire))
                {
                    // Si on n'a pas de gestionnaire => On charge l'utilisateur CRON comme gestionnaire
                    $gestionnaire = new agent($this->dbconnect);
                    $gestionnaire->load(SPECIAL_USER_IDCRONUSER);
                }
                $codeinterne = null;
                $destinataire = $structure->agent_envoyer_a($codeinterne);
                if (is_null($destinataire))
                {
                    // Si on n'a pas de destinataire => On charge l'utilisateur CRON comme destinataire
                    $destinataire = new agent($this->dbconnect);
                    $destinataire->load(SPECIAL_USER_IDCRONUSER);
                }
                // Si le gestionnaire courant gère les agents de la structure (gestvalidagent=O) => On charge tous les agents de la structure et on enlève les responsables (SIHAM + responsable)
                // En effet, un gestionnaire ne peut pas valider les demandes de son responsable - Ticket GLPI 147328 et 166498 (sauf s'il est dans le circuit => voir test suivant)
                // Il peut aussi gérer le circuit des agents de la structure courante => C'est la même façon d'alimenter les agents
                if ((strcasecmp($structure->gestvalidagent(),'o')==0 and $gestionnaire->agentid()==$this->agentid()) or 
                    (array_key_exists($structure->id(),(array)$listegeststruct)===true 
                     and $codeinterne==structure::MAIL_AGENT_ENVOI_GEST_COURANT)
                     and $destinataire->agentid()==$this->agentid())

                {
                    $agentliste = $structure->agentlist($this->fonctions->formatdate($datedebut), $this->fonctions->formatdate($datefin),'n');
                    // On doit enlever les responsables (SIHAM + responsable) car par défaut ils ne sont pas gérés
                    $resp = $structure->responsable();
                    unset($agentliste[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()]);
                    $resp = $structure->responsablesiham();
                    unset($agentliste[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()]);
                    $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                }
                
                //////////////////////////////////////////////////
                // On va ajouter les responsables des structures filles si le gestionnaire peut valider les demandes ds responsables filles
                // Attention : On doit vérifier que le responsable de la structure fille est bien affecté dans la structure
                // Sinon, ce n'est pas à lui de gérer les congés.
                // Attention 2 : Si le gestionnaire de la structure parente est dans le circuit de validation du responsable de la structure fille
                //      on doit ajouter ce responsable dans la liste des agents à traiter

                $structfilleliste = $structure->structurefille();
                foreach ((array)$structfilleliste as $fille)
                {
                    if ($this->fonctions->formatdatedb($fille->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) 
                    {
                        //var_dump("Je suis dans la structure " . $fille->nomlong());
                        $codeinterne = null;
                        $destinataire = $fille->resp_envoyer_a($codeinterne);
                        //var_dump("Code interne = $codeinterne ");
                        if ($this->fonctions->convertvaluetobool($structure->gestvalidrespstructfille()) or $codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT)
                        {
                            // Le test sur structure::MAIL_RESP_ENVOI_RESP_PARENT => Le gestionnaire peut gérer les demandes des responsables des structures filles
                            // Le test sur structure::MAIL_RESP_ENVOI_GEST_PARENT => Le gestionnaire fait partie du circuit de validation du responsable de la structure fille
                            if ($codeinterne==structure::MAIL_RESP_ENVOI_RESP_PARENT or $codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT)
                            {
                                //var_dump("On va ajouter le responsable de la structure " . $fille->nomlong());
                                $resp = $fille->responsable();
                                if (trim($resp->civilite())!='' and !$resp->estutilisateurspecial())
                                {
                                    if ($resp->structureid()==$fille->id())
                                    {
                                        $agentlistefull[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                                    }
                                }
                                //var_dump("On va ajouter le responsable SIHAM de la structure " . $fille->nomlong());
                                $resp = $fille->responsablesiham();
                                if (trim($resp->civilite())!='' and !$resp->estutilisateurspecial())
                                {
                                    if ($resp->structureid()==$fille->id())
                                    {
                                        $agentlistefull[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                                    }
                                }
                            }
                        }
                    }
                }

                //var_dump($agentlistefull);

                
                $codeinterne = null;
                $destinataire = $structure->resp_envoyer_a($codeinterne);
                if (is_null($destinataire))
                {
                    // Si on n'a pas de destinataire => On charge l'utilisateur CRON comme destinataire
                    $destinataire = new agent($this->dbconnect);
                    $destinataire->load(SPECIAL_USER_IDCRONUSER);
                }
                // Si la structure est dans le tableau des structures gérées par le gestionnaire et qu'il doit gérer le responsable de la structure courante
                if (array_key_exists($structure->id(),(array)$listegeststruct)===true
                    and $destinataire->agentid()==$this->agentid()
                    and ($codeinterne==structure::MAIL_RESP_ENVOI_GEST_COURANT or $codeinterne==structure::MAIL_RESP_ENVOI_GEST_PARENT))
                {
                    // On récupère les responsables de la structure (titulaire + délégué) si le gestionnaire doit gérer ce circuit
                    $resp = $structure->responsable();
                    // ATTENTION : Le gestionnaire ne gère le responsable que s'il est affecté à la structure courante => Sinon ce n'est pas lui qui valide les congés
                    if ($resp->structureid()==$structure->id())
                    {
                        $agentlistefull[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                    }
                    $resp = $structure->responsablesiham();
                    // ATTENTION : Le gestionnaire ne gère le responsable que s'il est affecté à la structure courante => Sinon ce n'est pas lui qui valide les congés
                    if ($resp->structureid()==$structure->id())
                    {
                        $agentlistefull[$resp->nom() . " " . $resp->prenom() . " " . $resp->agentid()] = $resp;
                    }
                }
            }
        }
        unset($agentlistefull[$this->nom() . " " . $this->prenom() . " " . $this->agentid()]);
        return $agentlistefull;
    }
 
    function listeagentenresponsabilite($datedebut,$datefin, $structure = null, $criterefiltre = agent::FILTRE_DEMANDE)
    {

        $agentlistefull = array();
        if ($this->estresponsable())
        {
            if (is_null($structure))
            {
                // On récupère la liste des structures en responsabilité
                $structureliste = $this->structrespliste();
                if (is_array($structureliste))
                {
                    uasort($structureliste,"triparprofondeurabsolue");
                }
            }
            else
            {
                // On ne traite que la structure passée en paramètre
                $structureliste[] = $structure;
            }

            // echo "Liste de structure = "; print_r($structureliste); echo "<br>";
            foreach ($structureliste as $structure) 
            {
                if ($criterefiltre == agent::FILTRE_DEMANDE)
                {
                    $agentliste = $structure->agentlist($datedebut,$datefin,$structure->respaffdemandesousstruct());
                }
                else if ($criterefiltre == agent::FILTRE_SOLDE)
                {
                    $agentliste = $structure->agentlist($datedebut,$datefin,$structure->respaffsoldesousstruct());
                }
                else
                {
                    echo $this->fonctions->showmessage(fonctions::MSGERROR, "Fonction agent::listeagentenresponsabilite => Le type de filtre n'est pas connu : $criterefiltre");
                }
                
                //foreach((array)$agentliste as $key => $tmpagent) { var_dump("Liste de agents pour la structure " . $structure->nomcourt() . " = Key : '$key' " . $tmpagent->identitecomplete()); }

                $delegation = $structure->getdelegation(true);
                //var_dump("Le flag de la délégation indique : " . $delegation->continuesendtoresp);
                if ($this->fonctions->convertvaluetobool($delegation->continuesendtoresp))
                {
                    // Le responsable continue d'être notifié donc on dit que le responsable est le responsable SIHAM
                    // => On ne l'ajoute pas dans le tableau en mettant le responsable à null
                    $respsihamstruct = $structure->responsablesiham();
                    unset($agentliste[$respsihamstruct->nom() . " " . $respsihamstruct->prenom() . " " . $respsihamstruct->agentid()]);
                    //var_dump("On va supprimer le responsable SIHAM de la liste => " . $respsihamstruct->identitecomplete());
                    //foreach((array)$agentliste as $key => $tmpagent) { var_dump("Liste de agents pour la structure " . $structure->nomcourt() . " = Key : '$key' " . $tmpagent->identitecomplete()); }
                }

                
                $agentlistefull = array_merge((array) $agentlistefull, (array) $agentliste);
                //var_dump("fin du select");
                $structfille = $structure->structurefille();
                if (! is_null($structfille)) 
                {
                    foreach ($structfille as $fille) 
                    {
                        //var_dump("Je suis sur la structure " . $fille->nomlong());
                        if ($this->fonctions->formatdatedb($fille->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) 
                        {
                            $respstructfille = $fille->responsable();
                            $respsihamstructfille = $fille->responsablesiham();

                            // Si l'agent est responsable mais qu'il n'est pas le responsable SIHAM => Il y a délégation
                            //var_dump("Avant le test if.....");
                            if ($respstructfille->agentid() != $respsihamstructfille->agentid())
                            {
                                //var_dump("Je suis dans le if....");
                                // On va regarder si le responsable SIHAM doit continuer d'être notifié des demandes de congés/CET/....
                                // Donc on charge la délégation de la structure
                                //var_dump("On est dans le cas ou l'agent est responsable mais n'est pas le responsable SIHAM");
                                $delegation = $fille->getdelegation(true);
                                //var_dump("Le flag de la délégation indique : " . $delegation->continuesendtoresp);
                                if ($this->fonctions->convertvaluetobool($delegation->continuesendtoresp))
                                {
                                    // Le responsable continue d'être notifié donc on dit que le responsable est le responsable SIHAM
                                    // => On ne l'ajoute pas dans le tableau en mettant le responsable à null
                                    $respstructfille = null;
                                    //var_dump("Je viens de mettre à NULL le responsable de la structure");
                                }
                            }

                            // On verifie que le responsable est bien défini et que son affectation est bien la strucuture fille courante
                            if (!is_null($respstructfille) and $respstructfille->agentid()!=SPECIAL_USER_IDCRONUSER and $respstructfille->structureid()==$fille->id()) 
                            {
                                // La clé NOM + PRENOM + AGENTID permet de trier les éléments par ordre alphabétique
                                $agentlistefull[$respstructfille->nom() . " " . $respstructfille->prenom() . " " . $respstructfille->agentid()] = $respstructfille;
                            }
                            // On verifie que le responsable est bien défini et que son affectation est bien la strucuture fille courante
                            if ($respsihamstructfille->agentid()!=SPECIAL_USER_IDCRONUSER and $respsihamstructfille->structureid()==$fille->id()) 
                            {
                                // La clé NOM + PRENOM + AGENTID permet de trier les éléments par ordre alphabétique
                                $agentlistefull[$respsihamstructfille->nom() . " " . $respsihamstructfille->prenom() . " " . $respsihamstructfille->agentid()] = $respsihamstructfille;
                            }
                        }
                    }
                }
            }
        }
        // On doit enlever l'utilisateur courant (qui est un responsable au sens large => SIHAM ou délégué)
        unset($agentlistefull[$this->nom() . " " . $this->prenom() . " " . $this->agentid()]);
        $respsiham = $structure->responsablesiham();
        // On doit aussi enlever le responsable SIHAM de la structure, car le délégué ne peut pas valider les congés du responsable SIHAM
        if ($respsiham->agentid() != SPECIAL_USER_IDCRONUSER) 
        {
            unset($agentliste[$respsiham->nom() . " " . $respsiham->prenom() . " " . $respsiham->agentid()]);
        }

        return $agentlistefull;

    }

    function congessuppaverifier($datedebut)
    {
        return $this->fonctions->congessuppaverifier($datedebut,$this->agentid());
    }

    function absencerhliste($datedebut, $datefin)
    {
        $absencerhliste = array();
        $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN,LIBELLE
                FROM ABSENCERH
                WHERE AGENTID = ?
                  AND ((DATEDEBUT <= '" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN >='" . $this->fonctions->formatdatedb($datedebut) . "')
                    OR (DATEFIN >= '" . $this->fonctions->formatdatedb($datefin) . "' AND DATEDEBUT <='" . $this->fonctions->formatdatedb($datefin) . "')
                    OR (DATEDEBUT >= '" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN <= '" . $this->fonctions->formatdatedb($datefin) . "'))";
        // echo "SQL = $sql <br>";
        $params = array($this->agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            $errlog = "Agent->absencerhliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $demande = new demande($this->dbconnect);
            $demande->agentid($this->agentid);
            $demande->datedebut($result[1]);
            $demande->moment_debut(fonctions::MOMENT_MATIN);
            $demande->datefin($result[2]);
            $demande->moment_fin(fonctions::MOMENT_APRESMIDI);
            $demande->type('harp');
            $demande->statut(demande::DEMANDE_VALIDE);
            $demande->commentaire($result[3]);
            $absencerhliste[] = $demande;
        }
        return $absencerhliste;
    }

    function teletravaildeplaceliste($datedebut, $datefin)
    {
        $ttliste = array();
        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                AND DATEREMPLACEMENT BETWEEN ? AND ?";

        $params = array($this->agentid,$this->fonctions->formatdatedb($datedebut),$this->fonctions->formatdatedb($datefin));

        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Agent->teletravaildeplaceliste => Problème SQL dans le chargement de l'exception : " . $erreur;
            echo $errlog;
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $exception = new ttexception();
            $exception->agentid = $this->agentid;
            $exception->dateorigine = $result[0];
            $exception->momentorigine = $result[1];
            $exception->dateremplacement = $result[2];
            $exception->momentremplacement = $result[3];
            $ttliste[] = $exception;
        }
        return $ttliste;

    }

    function forceperiodeobligatoire($periode, $checkonly = false, &$returndesc = "")
    {
        $returndesc = "";
        $returncode = "";

        static $cronuser = null;
        if (is_null($cronuser))
        {
            $cronuser = new agent($this->dbconnect);
            $cronuser->load(SPECIAL_USER_IDCRONUSER);    
        } 

        $typeabsenceid = 'ann' . substr($this->fonctions->anneeref($periode["datedebut"]), -2 , 2);
        $typeabsenceanticipeid = 'ann' . (substr($this->fonctions->anneeref($periode["datedebut"])+1, -2 , 2));


        //$idperiode = $periode["datedebut"] . '-' . $periode["datefin"];
        $idperiode = $periode["id"];
        // Si l'agent n'a pas d'exception pour la période
        $complement = new complement($this->dbconnect);
        // -----------------------------------
        // ATTENTION : Il faut trouver une clé de période pas trop longue !!!
        // -----------------------------------
        $complement->load($this->agentid, "EXCEPT_PER_$idperiode");
        // Le complément existe => La période est couverte car on ne vérifie rien
        if ($complement->agentid() == $this->agentid)
        {
            $returndesc = "La période obligatoire du " . $this->fonctions->formatdate($periode["datedebut"]) . " au " . $this->fonctions->formatdate($periode["datefin"]) . " a une exception.";
            //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
            $returncode = agent::CHECK_PERIODE_EXCEPTION; // agent::CHECK_PERIODE_COUVERTE;
        }
        // Le complément n'existe pas => On doit alors creer la demande pour compléter la période obligatoire
        else
        {
            $planning = new planning($this->dbconnect);
            $planning->load($this->agentid, $periode["datedebut"], $periode["datefin"], false, true, false);
            $listedispo = $planning->listeperiodedispo($this->agentid, $periode["datedebut"], fonctions::MOMENT_MATIN, $periode["datefin"],fonctions::MOMENT_APRESMIDI,false);
            if (count($listedispo)==0)
            {
                $returndesc = "La période obligatoire du " . $this->fonctions->formatdate($periode["datedebut"]) . " au " . $this->fonctions->formatdate($periode["datefin"]) . " est déjà couverte.";
                //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
                $returncode = agent::CHECK_PERIODE_COUVERTE;
            }
            elseif ($checkonly)
            {
                $returndesc = "La période obligatoire du " . $this->fonctions->formatdate($periode["datedebut"]) . " au " . $this->fonctions->formatdate($periode["datefin"]) . " n'est pas couverte.";
                //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
                $returncode = agent::CHECK_PERIODE_NONCOUVERTE;
            }
            else
            {
                // On passe par un for ($ctp ; $cpt < count ; $cpt++) et non pas un foreach 
                // car le tableau listedispo peut être modifié durant le traitement
                for ($indexdispo = 0 ; $indexdispo < count($listedispo) ; $indexdispo++)
                {
                    unset ($dispo);
                    $dispo = $listedispo[$indexdispo];

                    $returndesc = "=> Traitement de la période du " . $dispo->elementdebut->date() . " " . $this->fonctions->nommoment($dispo->elementdebut->moment()) . " au " . $dispo->elementfin->date() . " " . $this->fonctions->nommoment($dispo->elementfin->moment());
                    //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));

                    // On ne vérifie pas si le solde de congés est suffisant car vérifié dans le store
                    unset($demande);
                    $demande = new demande($this->dbconnect);
                    $demande->agentid($this->agentid);
                    $demande->type($typeabsenceid);
                    $demande->datedebut($dispo->elementdebut->date());
                    $demande->datefin($dispo->elementfin->date());
                    $demande->moment_debut($dispo->elementdebut->moment());
                    $demande->moment_fin($dispo->elementfin->moment());
                    $demande->commentaire("Période de fermeture obligatoire");
                    $ignoreabsenceautodecla = false; //// ??? A vérifier 
                    $ignoresoldeinsuffisant = false;
                    $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                    $soldeinsuffisantinfos = $demande->soldeinsuffisantinfos();
                    if (($resultat . "") != "" and !is_null($soldeinsuffisantinfos))
                    {
                        $returndesc = $returndesc . "\nPériode du " . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . " -> " . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . " : " . strip_tags($resultat);
                        //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));

                        $solderestant = $soldeinsuffisantinfos->solderestant;
                        // S'il n'y a pas de solde restant => Ce n'est pas la peine de réessayer de poser la demande
                        if ($solderestant>0)
                        {
                            // On a une erreur sur solde insuffisant => On peut sauvegarder juste le solde restant
                            $returndesc = $returndesc . "\nLe solde " . $demande->typelibelle() . " est insuffisant => On va calculer la date de fin pour $solderestant jours et début " . $dispo->elementdebut->date() . " " . $this->fonctions->nommoment($dispo->elementdebut->moment()) . " \n";
                            // Il faut donc calculer la date de fin de la demande à partir de la date de début et du planning de l'agent
                            $resultat = $planning->calculdatefindemande($dispo->elementdebut->date(),$dispo->elementdebut->moment(),$solderestant);

                            if (is_object($resultat)) // L'objet est l'élément du planning de fin correspondant à la durée demandée
                            {
                                $elementfin = $resultat;
                                $returndesc = $returndesc . "\nLa date de fin calculée est " . $elementfin->date() . " " . $this->fonctions->nommoment($elementfin->moment());
                                //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));

                                // On recrée la demande
                                $demande = new demande($this->dbconnect);
                                $demande->agentid($this->agentid);
                                $demande->type($typeabsenceid);
                                $demande->datedebut($dispo->elementdebut->date());
                                $demande->datefin($elementfin->date());
                                $demande->moment_debut($dispo->elementdebut->moment());
                                $demande->moment_fin($elementfin->moment());
                                $demande->commentaire("Période de fermeture obligatoire");
                                $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                                if (($resultat . "") == "")
                                {
                                    // On a posé une partie de la dispo avec le reste des congés.
                                    // On doit maintenant déclaré une nouvelle dispo qui va de l'élément suivant dans le planning jusqu'à l'élément de fin de la dispo
                                    $listeelement = $planning->planning();
                                    // On récupère l'élément suivant l'élément de fin
                                    if (isset($listeelement[$elementfin->idelementsuivant()]))
                                    {
                                        // error_log(basename(__FILE__) . " " . $fonctions->stripAccents("indexdispo courante = $indexdispo"));
                                        // foreach($listedispo as $tmpdispo)
                                        // {
                                        //     error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Dispo début = " . $tmpdispo->elementdebut->date() . " " . $tmpdispo->elementdebut->moment() . " -> " . $tmpdispo->elementfin->date() . " " . $tmpdispo->elementfin->moment()));
                                        // }

                                        // On crée une nouvelle dispo qui démarre à l'élément suivant du planning et qui se fini à la date de fin de la dispo
                                        $newdispo = new disponibilite;
                                        $newdispo->elementdebut = $listeelement[$elementfin->idelementsuivant()];
                                        $newdispo->elementfin = $dispo->elementfin;

                                        // On injecte après la dispo courante une nouvelle dispo à partir du 
                                        $subtab1 = array_slice ($listedispo, 0, $indexdispo+1);
                                        $subtab2 = array_slice ($listedispo, $indexdispo+1);
                                        $subtab1[] = $newdispo;
                                        $listedispo = array_merge ($subtab1, $subtab2);

                                        // On met la fin de la disponibilité qu'on vient de traiter (de manière incomplète car solde insuffisant) à l'élément 
                                        // représentant la date de fin qu'on a calculé précédemment.
                                        $dispo->elementfin = $elementfin;

                                        // error_log(basename(__FILE__) . " " . $fonctions->stripAccents("--------------------------"));
                                        // foreach($listedispo as $tmpdispo)
                                        // {
                                        //     error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Dispo début = " . $tmpdispo->elementdebut->date() . " " . $tmpdispo->elementdebut->moment() . " -> " . $tmpdispo->elementfin->date() . " " . $tmpdispo->elementfin->moment()));
                                        // }

                                    }
                                }
                            }
                            elseif (($resultat . "") != "")
                            {
                                // On a eu un message d'erreur en retour => Donc il y a eu un problème sur le calcul de la date de fin
                                $returndesc = $returndesc . "\nLe calcul de la date de fin a échoué => " . $resultat;  
                                $returncode = agent::CHECK_PERIODE_ERREUR;              
                            }
                        }
                        // Le solde restant pour l'année de référence est nul => On doit prendre sur des congés par anticipation
                        else
                        {
                            // On charge le solde de congés anticipé pour vérifier qu'il existe
                            $solde = new solde($this->dbconnect);
                            $resultat = $solde->load($this->agentid, $typeabsenceanticipeid);
                            if ($resultat != "") 
                            {
                                // Il n'existe pas => On le crée
                                $returndesc = $returndesc . "\nOn crée le solde de l'année suivante ($typeabsenceanticipeid)";
                                $resultat = $solde->creersolde($typeabsenceanticipeid, $this->agentid);
                            }
                            if (($resultat . "") == "")
                            {
                                $demande = new demande($this->dbconnect);
                                $demande->agentid($this->agentid);
                                // congés par anticipation
                                $demande->type($typeabsenceanticipeid);
                                $demande->datedebut($dispo->elementdebut->date());
                                $demande->datefin($dispo->elementfin->date());
                                $demande->moment_debut($dispo->elementdebut->moment());
                                $demande->moment_fin($dispo->elementfin->moment());
                                $demande->commentaire("Période de fermeture obligatoire");
                                $ignoreabsenceautodecla = false; //// ??? A vérifier 
                                $ignoresoldeinsuffisant = true;
                                $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                            }
                            else
                            {
                                // La création du solde de l'année suivante ne s'est pas bien passé => On fait remonter l'erreur
                                $returndesc = $returndesc . "\nEchec lors de la création du solde annuel $typeabsenceanticipeid => $resultat";
                                $returncode = agent::CHECK_PERIODE_ERREUR;
                            }
                        }
                    }
                    if (($resultat . "") == "") 
                    {
                        $demandeid = $demande->id();
                        
                        $compldemande = new demandecomplement($this->dbconnect);
                        $compldemande->demandeid($demandeid);
                        $compldemande->complementid(demandecomplement::PERIODE_OBLIG_AUTOMATIQUE);
                        $compldemande->valeur('O');
                        $resultat = $compldemande->store();
                        //echo "Je viens de store le compldemande => " . $resultat . " \n";
                        if (($resultat . "") == "")
                        {
                            unset($demande);
                            $demande = new demande($this->dbconnect);
                            $demande->load($demandeid);
                            //echo "Je viens de recharger la demande \n";
                            $demande->statut(demande::DEMANDE_VALIDE);

                            $resultat = $demande->store();

                            if (($resultat . "") == "")
                            {
                                // On génère le PDF correspondant à la demande si tout s'est bien passé
                                $pdfname = $demande->pdf($cronuser->agentid());
                                $returndesc = $returndesc . "\nLe fichier $pdfname a été généré.";
                            }
                            //echo "Je viens de changer le statut en validé => " . $resultat . " \n";
                        }
                    }
                    // Si on a eu un problème lors de la sauvegarde
                    if (($resultat . "") != "")
                    {
                        $returndesc = $returndesc ."\nErreur sur la période du " . $demande->datedebut() . " " . $demande->moment_debut() . " -> " . $demande->datefin() . " " . $demande->moment_fin();
                        //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
                        $returndesc = $returndesc . "\nProblème lors de la sauvegarde des congés en période obligatoire : " . $resultat;
                        //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
                        $returncode = agent::CHECK_PERIODE_ERREUR;
                        /// => ??? Mail à la DRH pour signaler qu'on a eu un problème lors de la sauvegarde
                    }
                    else
                    {
                        $returndesc = $returndesc . "\nTout s'est bien passé => Ajout d'un congé (" . $demande->nbrejrsdemande() . " jours) du " . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . " au " .  $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . " => type : " . $demande->typelibelle();
                        //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($returndesc));
                        $returncode = agent::CHECK_PERIODE_AJOUTEE;
                    }
                }
                unset ($dispo);
            }
        }
        $returndesc = $returndesc . "\n";
        return $returncode;
    }
    
}

?> 