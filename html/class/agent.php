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
        
    
    private $agentid = null;

    private $nom = null;

    private $prenom = null;

    private $dbconnect = null;

    private $civilite = null;

    private $adressemail = null;

    private $typepopulation = null;
    
    private $structureid = null;

    private $fonctions = null;
    
    private $travailsamedi = null;
    
    private $travaildimanche = null;

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
            
            $sql = "SELECT AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION, STRUCTUREID FROM AGENT WHERE AGENTID= ? ";
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
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) > 1) 
        {
            $errlog = "Agent->loadbyemail (AGENT) : Plusieurs adresses mail ($email) trouvées.";
            echo $errlog . "<br/>";
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
                $sql = "UPDATE AGENT SET CIVILITE = ?, NOM = ?, PRENOM = ?, ADRESSEMAIL = ?, TYPEPOPULATION = ?, STRUCTUREID = ? WHERE AGENTID = ?";
                $params = array(
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail,
                    $this->typepopulation,
                    $this->structureid,
                    $agentid
                );
            }
            else
            {
                // Ajout manuel de l'agent
                $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION,STRUCTUREID) VALUES(?,?,?,?,?,?,?)";
                $params = array(
                    $agentid,
                    $this->civilite,
                    $this->nom,
                    $this->prenom,
                    $this->adressemail,
                    $this->typepopulation,
                    $this->structureid
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


//     /**
//      *
//      * @deprecated
//      */
//     function storeutilisateurspecial($agentid)
//     {
//         trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
//         //echo "<br>Avant le if estutilisateurspecial";
//         if ($this->estutilisateurspecial($agentid))
//         {
//             //echo "<br>On set les paramètres avec les valeurs par défaut";
//             if (strlen(trim($this->civilite . ""))==0) $this->civilite('');
//             if (strlen(trim($this->nom . ""))==0) $this->nom('SPECIAL_' . $agentid);
//             if (strlen(trim($this->prenom . ""))==0) $this->prenom('UTILISATEUR_' . $agentid);
//             if (strlen(trim($this->adressemail . ""))==0) $this->mail('noreply@no_domaine.fr');
//             if ($this->existe($agentid))
//             {
//                 // Ajout manuel de l'agent
//                 $sql = "UPDATE AGENT SET CIVILITE = ?, NOM = ?, PRENOM = ?, ADRESSEMAIL = ? WHERE AGENTID = ?";
//                 $params = array(
//                     $this->fonctions->my_real_escape_utf8($this->civilite),
//                     $this->fonctions->my_real_escape_utf8($this->nom),
//                     $this->fonctions->my_real_escape_utf8($this->prenom),
//                     $this->fonctions->my_real_escape_utf8($this->adressemail),
//                     $this->fonctions->my_real_escape_utf8($agentid)
//                 );
//             }
//             else
//             {
//                 // Ajout manuel de l'agent
//                 $sql = "INSERT INTO AGENT(AGENTID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION) VALUES(?,?,?,?,?,'')";
//                 $params = array(
//                     $this->fonctions->my_real_escape_utf8($agentid),
//                     $this->fonctions->my_real_escape_utf8($this->civilite),
//                     $this->fonctions->my_real_escape_utf8($this->nom),
//                     $this->fonctions->my_real_escape_utf8($this->prenom),
//                     $this->fonctions->my_real_escape_utf8($this->adressemail)
//                     );
//             }
//             $query = $this->fonctions->prepared_select($sql, $params);
//             //echo "sql = " . $sql . "<br>";
//             $erreur = mysqli_error($this->dbconnect);
//             if ($erreur != "") {
//                 $errlog = "Agent->storeutilisateurspecial (AGENT) : " . $erreur;
//                 echo $errlog . "<br/>";
//                 error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
//                 return false;
//             }
//             $this->agentid = $agentid;
//             return true;
//         }
//         else
//         {
//             echo "<br> $agentid n'est pas dans la liste des utilisateurs speciaux => Impossible de le sauvegarder<br>";
//             return false;
//         }
//     }

    
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
                $errlog = "Agent->mailforspecialagent : Le mail de l'agent special " . $this-agentid() . " n'est pas défini !!!";
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
            $errlog = "Agent->mailforspecialagent : L'agent " . $this-agentid() . " n'est pas un utilisateur spécial !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }
    
    /**
     *
     * @param 
     * @return string eppn of the current agent 
     */
    function eppn()
    {
        $agent_eppn = "";
        $LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_EPPN_ATTR");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_SUPANNEMPID_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
        $filtre = "($LDAP_SUPANNEMPID_ATTR=" . $this->agentid . ")";
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
        return $agent_eppn;
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
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_SUPANNEMPID_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
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
    
    function travailsamedi()
    {
        if (is_null($this->travailsamedi))
        {
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, "TRAVAILSAMEDI");
            $this->travailsamedi = (strcasecmp($complement->valeur(), "O") == 0);
        }
        return $this->travailsamedi;
    }

    function travaildimanche()
    {
        if (is_null($this->travaildimanche))
        {
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid, "TRAVAILDIMANCHE");
            $this->travaildimanche = (strcasecmp($complement->valeur(), "O") == 0);
        }
        return $this->travaildimanche;
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
                    if ($deleteics)
                    {
                       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    }
                    
                    // Set HTTP Header for POST request
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: text/calendar'
                    ));
                    
                    // Submit the POST request
                    $result = "";
                    //error_log(basename(__FILE__)." Curl de MAJ du calendrier : ".$this->fonctions->stripAccents(var_export($ch,true)));
                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        $curlerror = 'Curl error: ' . curl_error($ch) . ' URL = ' . $url;
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
                    $msg .= "Bonjour " . $this->fonctions->utf8_encode(ucwords(mb_strtolower($destinataire->identitecomplete(),'UTF-8'))) . ",<br><br>";
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
                    $msg .= "Cliquez sur le lien <a href='" . preg_replace('/([^:])(\/{2,})/', '$1/', $this->fonctions->get_g2t_url()) . "'>G2T</a><br><br>Cordialement<br><br>" . ucwords(mb_strtolower($this->prenom . " " . $this->nom),'UTF-8') . "\r\n";
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
	            $msg .= "<br><br><p class='fontsize15'>La pièce jointe est un fichier iCalendar contenant plus d'informations concernant l'événement.<br>Si votre client de courrier supporte les requêtes iTip vous pouvez utiliser ce fichier pour mettre à jour votre copie locale de l'événement.</p>";
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
	            $errlog = "Le mode MAINTENANCE est activé. Il n'y a pas d'envoi de mail";
                    echo "$errlog ";
                    global $uid;
                    if (isset($uid) and $uid<>"")
                    {
                        echo "<br>";
                    }
                    else
                    {
                        echo "\n";                        
                    }
	            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog) . "\n");
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

    /**
     *
     * @param
     * @return array list of objects structure where the agent manage the lower structure query
     */
    function structgestcongeliste()
    {
        $structliste = null;
        if ($this->estgestionnaire()) {
            // echo "Je suis gestionnaire...<br>";
            // Liste des structures donc je suis gestionnaire
            $structgestliste = $this->structgestliste();
            if (is_array($structgestliste))
            {
                uasort($structgestliste,"triparprofondeurabsolue");
            }
            // echo "<br>structgestliste = "; print_r((array) $structgestliste) ; echo "<br>";
            foreach ((array) $structgestliste as $structid => $structure) {
                // Pour chaque structure fille, on regarde si je gère les demandes du responsable
                $structfilleliste = $structure->structurefille();
                // echo "<br>structfilleliste = "; print_r((array) $structfilleliste) ; echo "<br>";
                foreach ((array) $structfilleliste as $structfilleid => $structfille) {
                    // Si la structure est encore ouverte...
                    if ($this->fonctions->formatdatedb($structfille->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                        // echo "<br>structfilleid = " . $structfilleid . "<br>";
                        // echo "structfille->resp_envoyer_a() = "; print_r($structfille->resp_envoyer_a()); echo "<br>";
                        $agent = $structfille->resp_envoyer_a();
                        if (! is_null($agent)) {
                            if ($agent->agentid() == $this->agentid) {
                                $structliste[$structfilleid] = $structfille;
                            }
                        }
                    }
                }
            }
        }
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
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND (ANNEEREF= ? OR ANNEEREF= ?))";
            $subparams = array($anneeref,($anneeref - 1));
        } 
        else 
        {
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND ANNEEREF= ?)";
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
        foreach ((array) $soldeliste as $key => $tempsolde) {
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
                    $htmltext = $htmltext . $this->fonctions->demandestatutlibelle($demande->statut());
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
    function demandeslistehtmlpourgestion($debut_interval, $fin_interval, $agentid = null, $mode = "agent", $cleelement = null)
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
                // if (($demande->statut() == "a" and $mode == "agent") or ($demande->statut() == "v" and $mode == "resp"))
                if (((strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 or strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0) and strcasecmp($mode, "agent") == 0) 
                  or (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, "resp") == 0)) 
                {
                    if ($premieredemande) {
                        $htmltext = $htmltext . "<table id='tabledemande_" . $this->agentid() . "' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        if ($mode=='agent')
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
                        if (strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 and strcasecmp($mode, "agent") == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Commentaire</td>";
                        $htmltext = $htmltext . "<td class='cellulesimple'>Annuler</td>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, "resp") == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td>";
                        $htmltext = $htmltext . "</tr>";
*/                                
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple cursorpointer'>Date de demande <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Date de début <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Date de fin <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Type de demande <span class='sortindicator'> </span></th>
                                                      <th class='cellulesimple cursorpointer'>Nbre jours <span class='sortindicator'> </span></th>";
                        if (strcasecmp($mode, "agent") == 0)
                        {
                            $htmltext = $htmltext . "<th class='cellulesimple cursorpointer'>Statut<span class='sortindicator'> </span></th>";
                            $htmltext = $htmltext . "<th class='cellulesimple'>Commentaire</th>";
                        }
                        $htmltext = $htmltext . "<th class='cellulesimple'>Annuler</th>";
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, "resp") == 0)
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
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>";
                        if (strcasecmp($mode, "agent") == 0)
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
                        if ((strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, "agent") == 0))
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
                        elseif ((strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0 and strcasecmp($mode, "agent") == 0))
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
                        if (strcasecmp($demande->statut(), demande::DEMANDE_VALIDE) == 0 and strcasecmp($mode, "resp") == 0)
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
/*******************************************
******* Déclarations déplacées dans menu.php
const getCellValue = (tr, idx) => 
{
    if (tr.children[idx].querySelector('time')!==null) // Si on a un time dans le td, alors on trie sur l'attribut datetime
    {
        return tr.children[idx].querySelector('time').getAttribute('datetime');
    }
    else
    {
        return tr.children[idx].innerText || tr.children[idx].textContent;
    }
}
                    
const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
    v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
*******************************************
*/

                    
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
     * @param string $structureid
     *            optional deprecated parameter => not used in code
     * @param string $cleelement
     *            optional deprecated parameter => not used in code
     * @return string the html text of the array
     */
    function demandeslistehtmlpourvalidation($debut_interval, $fin_interval, $agentid = null, $structureid = null, $cleelement = null)
    {
        $longueurmaxmotif = $this->fonctions->logueurmaxcolonne('DEMANDE','MOTIFREFUS');

        $liste = null;
        $liste = $this->demandesliste($debut_interval, $fin_interval);
        $debut_interval = $this->fonctions->formatdatedb($debut_interval);
        $fin_interval = $this->fonctions->formatdatedb($fin_interval);
        
        // $liste=$this->demandesliste($debut_interval,$fin_interval);
        // foreach ($this->structure()->structurefille() as $key => $value)
        // {
        // echo "Structure fille = " . $value->nomlong() . "<br>";
        // $listerespsousstruct = $value->responsable()->demandesliste($debut_interval,$fin_interval);
        // $liste = array_merge($liste,$listerespsousstruct);
        // }
        
        // echo "#######liste (Count=" . count($liste) .") = "; print_r($liste); echo "<br>";
        
        $statutliste = array();
        if (isset($_POST["statut"]))
        {
           $statutliste = $_POST['statut'];
        }
        
        
        $htmltext = "";
        // $htmltext = "<br>";
        if (count($liste) == 0) {
            // $htmltext = $htmltext . " <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
        } else {
            $premieredemande = TRUE;
            foreach ($liste as $key => $demande) {
                if (strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0) 
                {
                    $todisplay = true;
                    // On n'affiche pas les demandes du responsable !!!!
                    if ($agentid == $this->agentid) {
                        $todisplay = false;
                    }
                    // echo "todisplay = $todisplay <br>";
                    if ($todisplay) {
                        if ($premieredemande) {
                            $htmltext = $htmltext . "<table class='tableausimple' width=100%>";
                            $htmltext = $htmltext . "   <tr><td class=titresimple colspan=7 align=center >Tableau des demandes à valider pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";
                            $htmltext = $htmltext . "   <tr align=center>
                                                            <td class='cellulesimple'>Date de demande</td>
                                                            <td class='cellulesimple'>Date de début</td>
                                                            <td class='cellulesimple'>Date de fin</td>
                                                            <td class='cellulesimple'>Type de demande</td>
                                                            <td class='cellulesimple'>Nbre jours</td>
                                                            <td class='cellulesimple'>Etat de la demande</td>
                                                            <td class='cellulesimple'>Motif (obligatoire si le congé est refusé) - maximum $longueurmaxmotif caractères</td>
                                                        </tr>";
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
/*                        
*                        elseif ($this->fonctions->absencecommentaireoblig($demande->type()))
*                        {
*                            $datatitle = '';
*                            if (strlen($demande->commentaire()) != 0) 
*                            {
*                                $datatitle = " data-title=" . chr(34) . htmlentities($this->fonctions->ajoute_crlf($demande->commentaire(),60)) . chr(34);  
*                            }
*                            $htmltext = $htmltext . "   <td class='cellulesimple'>";
*                            $htmltext = $htmltext . $demande->typelibelle();
*                            $htmltext = $htmltext . "</td>";
*                        } 
*/
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
                        $htmltext = $htmltext . "   <td class='cellulesimple'>";
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
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_VALIDE . "'>" . $this->fonctions->demandestatutlibelle(demande::DEMANDE_VALIDE) . "</option>";
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
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_REFUSE . "'>" . $this->fonctions->demandestatutlibelle(demande::DEMANDE_REFUSE) . "</option>";
                        $htmltext = $htmltext . "         <option ";
                        if (isset($statutliste[$demande->id()]) and $statutliste[$demande->id()] == demande::DEMANDE_ATTENTE)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        elseif (!isset($statutliste[$demande->id()]) and strcasecmp($demande->statut(), demande::DEMANDE_ATTENTE) == 0)
                        {
                            $htmltext = $htmltext . " selected ";
                        }
                        $htmltext = $htmltext . " value='" . demande::DEMANDE_ATTENTE ."'>" . $this->fonctions->demandestatutlibelle(demande::DEMANDE_ATTENTE) . "</option>";
                        $htmltext = $htmltext . "      <select>";
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
                                . "<textarea name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' rows='2' cols='80' $textareastyle oninput='checktextlength(this,$longueurmaxmotif); validdemandemotif(this," . $demande->id() . ");' $disabletext>" . $demande->motifrefus() . "</textarea>"
                                . "</td>";
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
        $query = $this->fonctions->prepared_select($sql, $params);
        //echo "SQL = " . $sql . "<br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            echo "Agent->listecommentaireconge : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Agent->listecommentaireconge : " . $erreur);
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $commentaireconge = new commentaireconge();
            $commentaireconge->commentaireid = $result[0];
            $commentaireconge->agentid = $result[1];
            $commentaireconge->typeabsenceid = $result[2];
            $commentaireconge->dateajout = $result[3];
            $commentaireconge->commentaire = $result[4];
            $commentaireconge->nbjoursajoute = $result[5];
            $commentaireconge->auteurid = $result[6] . "";
            $commentaireconge->libelleabsence = $result[7];
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
        if (is_null($anneeref))
        {
            $sql = "SELECT AGENTID,LIBELLE,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,TYPEABSENCE.TYPEABSENCEID,COMMENTAIRECONGE.COMMENTAIRECONGEID,COMMENTAIRECONGE.AUTEURID
    FROM COMMENTAIRECONGE,TYPEABSENCE 
    WHERE AGENTID= ? AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($this->fonctions->anneeref(), 2, 2) . "' 
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($this->fonctions->anneeref() - 1), 2, 2) . "' 
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID='cet') 
                                               AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID";
        }
        else
        {
            $sql = "SELECT AGENTID,LIBELLE,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,TYPEABSENCE.TYPEABSENCEID,COMMENTAIRECONGE.COMMENTAIRECONGEID,COMMENTAIRECONGE.AUTEURID
    FROM COMMENTAIRECONGE,TYPEABSENCE
    WHERE AGENTID= ? AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($anneeref, 2, 2) . "'
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($anneeref + 1), 2, 2) . "'
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID='cet')
                                               AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID";
            
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
            if (($showonlycomplement and (strcasecmp(substr($result[5], 0, 3), "sup")) == 0) or ($showonlycomplement == false)) {
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
                
                $htmltext = $htmltext . "<tr align=center>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $result[1] . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $this->fonctions->formatdate($result[2]) . "</td>";
                if ($result[4] > 0)
                {
                    $htmltext = $htmltext . "<td class='cellulesimple'>+" . (float) ($result[4]) . "</td>";
                }
                else
                {
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . (float) ($result[4]) . "</td>";
                }
                $commentaire = trim($result[3]);
                if (trim($result[7])=='')
                {
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . htmlentities($commentaire) . "</td>";
                }
                else
                {
                    $auteur = new agent($this->dbconnect);
                    $auteur->load(trim($result[7]));
                    $htmltext = $htmltext . "<td class='cellulesimple cellulemultiligne' >" . htmlentities($commentaire) . " (par " .  $auteur->identitecomplete()  .   ")</td>";
                }
                if ($allowremove)
                {
                    $htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' id='" . $result[6] . "' name='remove_compl_id[" . $result[6] . "]'></td>";
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
     *
     * @param string $typeconge
     *            optional type of vacation. default is null
     * @param string $nbrejours
     *            optional number of day of the vacation. default is null
     * @param string $commentaire
     *            optional comment for the vacation. default is null
     * @return
     */
    function ajoutecommentaireconge($typeconge = null, $nbrejours = null, $commentaire = null, $auteur = null)
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
        
        $date = date("d/m/Y");
        if (is_null($auteurid))
        {
            $sql = "INSERT INTO COMMENTAIRECONGE(AGENTID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE)
                            VALUES (?,?,?,?,?)";
            $params = array($this->agentid, $typeconge, $this->fonctions->formatdatedb($date),$commentaire,$nbrejours);
        }
        else
        {
            $sql = "INSERT INTO COMMENTAIRECONGE(AGENTID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,AUTEURID)
                            VALUES (?,?,?,?,?,?)";
            $params = array($this->agentid, $typeconge, $this->fonctions->formatdatedb($date),$commentaire,$nbrejours,$auteurid);            
        }
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $message = "$erreur";
            error_log(basename(__FILE__) . " " . $erreur);
        }
    }

    /**
     *
     * @param string $congessuppid
     * @param string $demandeur
     *            object agent representing the applicant
     * @return string result if errors eccurded. Empty if all ok
     * 
     */
    function supprcongesupplementaire($congessuppid, $demandeur)
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
            $message = "Impossible de trouver la demande d'ajout de comgés complémentaires $congessuppid";
            error_log(basename(__FILE__) . " " . $message);
            return $message;
        }
        $result = mysqli_fetch_row($query);
        $nbrejoursajoutes = $result[0];

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
            $message = "Nombre de jours complémentaires insuffisant pour annuler la demande $congessuppid => Nbre de jours restant = $solderestant / Nbre de jours à annuler : $nbrejoursajoutes";
            error_log(basename(__FILE__) . " " . $message);
            return $message;
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
        $message = "Suppression de l'ajout de jours complémentaire $congessuppid pour " . $this->agentid . " par " . $demandeur->identitecomplete();
        error_log(basename(__FILE__) . " " . $message);
        return "";
    }
    
    function aunedemandecongesbonifies($anneeref)
    {
        $demande = null;
        $debutperiode = $this->fonctions->formatdatedb($anneeref . $this->fonctions->debutperiode());
        $finperiode = $this->fonctions->formatdatedb(($anneeref + 1) . $this->fonctions->finperiode());
        // $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN FROM ABSENCERH WHERE AGENTID='" . $this->agentid ."' AND TYPEABSENCE='CONGE_BONIFIE' AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN FROM ABSENCERH WHERE AGENTID= ? AND (TYPEABSENCE='CONGE_BONIFIE' OR TYPEABSENCE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
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
            if (! $demande->controlenbrejrs($nbrejrscalcule)) {
                $analyse[$demande->id()] = "Incohérence détectée : Nombre de jours de la demande = " . $demande->nbrejrsdemande() . " / Nombre de jours recalculé = $nbrejrscalcule (demande Id = " . $demande->id() . ")";
            }            // La fonction retourne vrai mais avec un nombre de jour nul => La demande est annulée ou refusée
            elseif ($nbrejrscalcule == 0) {
                $analyse[$demande->id()] = "Aucune vérification faite car la demande " . $demande->id() . " est annulée ou refusée...";
            }
        }
        
        return $analyse;
    }

    function CETaverifier($datedebut)
    {
        $sql = "SELECT DISTINCT DEMANDEID ,DATEDEBUT,DATESTATUT
				FROM DEMANDE 
				WHERE AGENTID = ? 
				  AND TYPEABSENCEID = 'cet' 
				  AND (DATEDEBUT >= ?
				    OR DATESTATUT >= ? )
			    ORDER BY DATEDEBUT,DATESTATUT";
        $params = array($this->agentid,$this->fonctions->formatdatedb($datedebut),$this->fonctions->formatdatedb($datedebut));
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        $demandeliste = array();
        // Si pas de demande de CET, on retourne le tableau vide
        if (mysqli_num_rows($query) == 0) {
            return $demandeliste;
        }
        while ($result = mysqli_fetch_row($query)) {
            $demandeid = $result[0];
            $demande = new demande($this->dbconnect);
            $demande->load($demandeid);
            
            $complement = new complement($this->dbconnect);
            $complement->load($this->agentid(), 'DEM_CET_' . $demandeid);
            
            if ($demande->statut() == demande::DEMANDE_VALIDE and $complement->agentid() == '') // Si la demande est validée mais que le complément n'existe pas => On doit le controler
            {
                $demandeliste[] = $demande;
            }
            if ($demande->statut() == demande::DEMANDE_ANNULE and $complement->valeur() == demande::DEMANDE_VALIDE) // Si la demande est annulée mais que le complément est toujours valide => On doit le contrôler
            {
                $demandeliste[] = $demande;
            }
        }
        return $demandeliste;
    }
    
    function isG2tUser()
    {
    	// On verifie que la personne est dans le groupe G2T du LDAP
    	$LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
    	$LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
    	$LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
    	$LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
    	$LDAP_MEMBER_ATTR = $this->fonctions->liredbconstante("LDAPMEMBERATTR");
    	$LDAP_GROUP_NAME = $this->fonctions->liredbconstante("LDAPGROUPNAME");
    	$LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
    	$retour = FALSE;
    	// Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
    	if ((trim("$LDAP_MEMBER_ATTR") != "" and trim("$LDAP_GROUP_NAME") != "")) {
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "(&(".$LDAP_CODE_AGENT_ATTR."=".$this->agentid().")(".$LDAP_MEMBER_ATTR."=".$LDAP_GROUP_NAME."))";
            $dn = $LDAP_SEARCH_BASE;
            // 1.1 => ldap ne demande aucun attribut
            $restriction = array(
                            "1.1"
            );
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);

            if (!$r || !$sr || !$info) // La connexion, l'interrogation ou la lecture des résultat LDAP a échoué
                    $retour = TRUE;
            // Si l'utilisateur est dans le groupe 
            if (isset($info["count"]) && $info["count"] > 0) 
            {
                    $retour = TRUE;
            }
            else
            {
                $errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->agentid() . ") ne fait parti du groupe LDAP : $LDAP_GROUP_NAME";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
    	}
    	return $retour;
    }
    
    function getInfoDocCet()
    {
    	// On récupère les infos pour la demande d'alimentation du CET
    	// adresse postale
    	$LDAP_SERVER = $this->fonctions->liredbconstante("LDAPSERVER");
    	$LDAP_BIND_LOGIN = $this->fonctions->liredbconstante("LDAPLOGIN");
    	$LDAP_BIND_PASS = $this->fonctions->liredbconstante("LDAPPASSWD");
    	$LDAP_SEARCH_BASE = $this->fonctions->liredbconstante("LDAPSEARCHBASE");
    	$LDAP_MEMBER_ATTR = $this->fonctions->liredbconstante("LDAPMEMBERATTR");
    	$LDAP_GROUP_NAME = $this->fonctions->liredbconstante("LDAPGROUPNAME");
    	$LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
    	$LDAP_POSTAL_ADDRESS_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_ADDRESS_ATTR");
    	$retour = array();
    	// Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
    	if ((trim("$LDAP_MEMBER_ATTR") != "" and trim("$LDAP_GROUP_NAME") != "")) {
    		$con_ldap = ldap_connect($LDAP_SERVER);
    		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    		$r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
    		$filtre = "(".$LDAP_CODE_AGENT_ATTR."=".$this->agentid().")";
    		$dn = $LDAP_SEARCH_BASE;
    		$restriction = array(
    				"$LDAP_POSTAL_ADDRESS_ATTR"
    		);
    		$sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
    		$info = ldap_get_entries($con_ldap, $sr); 
    		
    		if (isset($info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]))
    		{
    		    $retour[LDAP_AGENT_ADDRESS_ATTR] = str_replace('$', ', ',$info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]);
    		}
    		else
    		{
    			$errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->agentid() . ") n'a pas de postalAddress....";
    			error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    		}
    	}
    	return $retour;
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
        $LDAP_MEMBER_ATTR = $this->fonctions->liredbconstante("LDAPMEMBERATTR");
        $LDAP_GROUP_NAME = $this->fonctions->liredbconstante("LDAPGROUPNAME");
        $LDAP_CODE_AGENT_ATTR = $this->fonctions->liredbconstante("LDAPATTRIBUTE");
        $LDAP_AGENT_PERSO_ADDRESS_ATTR = $this->fonctions->liredbconstante("LDAP_AGENT_PERSO_ADDRESS_ATTR");
        $retour = array();
        // Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
        if ((trim("$LDAP_MEMBER_ATTR") != "" and trim("$LDAP_GROUP_NAME") != "")) {
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
                $retour[LDAP_AGENT_PERSO_ADDRESS_ATTR] = str_replace('$', ', ',$info[0]["$LDAP_AGENT_PERSO_ADDRESS_ATTR"][0]);
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
            $htmltext = $htmltext . "<center>";
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr class='titresimple'><td colspan=8>Informations sur les demandes d'alimentation de CET pour " . $this->identitecomplete() . "</td></tr>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Type de demande</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
            $htmltext = $htmltext . "</tr>";
            foreach ($listid as $id)
            {
                $alimcet->load($id);
                $htmltext = $htmltext . "<tr>
                                    <td class='cellulesimple'>" . $id . "</td>
                                    <td class='cellulesimple'>" . $this->fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td>
                                    <td class='cellulesimple'>" . $alimcet->typeconges() . "</td>
                                    <td class='cellulesimple'>" . $alimcet->valeur_f() . "</td>
                                    <td class='cellulesimple'>" . $alimcet->statut() . "</td>
                                    <td class='cellulesimple'>" . $this->fonctions->formatdate($alimcet->datestatut()) . "</td>
                                    <td class='cellulesimple'>" . $alimcet->motif() . "</td>
                                    <td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td>
                                 </tr>";
            }
            $htmltext = $htmltext . "</table><br>";
            $htmltext = $htmltext . "</center>";

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
            $htmltext = "Informations sur les droits d'options sur CET pour " . $this->identitecomplete() . "<br>";
            $htmltext = $htmltext . "<div id='option_alim_cet'>";
            $htmltext = $htmltext . "<table class='tableausimple'>";
            $htmltext = $htmltext . "<tr><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>Année de référence</td><td class='titresimple'>RAFP</td><td class='titresimple'>Indemnisation</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
            $htmltext = $htmltext . "</tr>";
            foreach ($listid as $id)
            {
                $optioncet->load($id);
                //$htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $this->fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $alimcet->statut() . "</td><td class='cellulesimple'>" . $this->fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".$alimcet->esignatureurl()."</a></td></tr>";
                $htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $id . "</td><td class='cellulesimple'>" . $this->fonctions->formatdate(substr($optioncet->datecreation(), 0, 10)).' '.substr($optioncet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $optioncet->anneeref() . "</td><td class='cellulesimple'>" . $optioncet->valeur_i() . "</td><td class='cellulesimple'>" . $optioncet->valeur_j() . "</td><td class='cellulesimple'>" . $optioncet->statut() . "</td><td class='cellulesimple'>" . $this->fonctions->formatdate($optioncet->datestatut()) . "</td><td class='cellulesimple'>" . $optioncet->motif() . "</td><td class='cellulesimple'><a href='" . $optioncet->esignatureurl() . "' target='_blank'>".(($optioncet->statut() == $optioncet::STATUT_ABANDONNE) ? '':$optioncet->esignatureurl())."</a></td></tr>";
            }
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
    	$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE AGENTID = ? ";
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
    			$listdemandes[] = $result[0];
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
    			$list_demandes = $this->demandesliste($date_element, $date_element + 1);
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
    		foreach ($list_id_alim as $id_alim)
    		{
    			$alimentationCET->load($id_alim);
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
        $sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE AGENTID = ? ";

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
                $listdemandes[] = $result[0];
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
        $sql = "SELECT ESIGNATUREID FROM TELETRAVAIL WHERE AGENTID = ? AND ESIGNATUREID <> '' AND ESIGNATUREURL <> '' "; // AND STATUT NOT IN ('" . teletravail::TELETRAVAIL_ANNULE . "') " ;
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
                $erreur = $this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
                //echo "<br>synchroteletravail => $erreur <br>";
                if ($erreur != "")
                {
                    return $erreur;
                }
            }
        }
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
                    // L'agent est présent depuis plus d'un an à la fin de son affectation, donc on va calculer son solde avec les régles standards
                    // Attention cependant, il faut calculer le solde pour la période avant les 365 jours
                    if ($NbreJoursTotalAff > $nbre_jour_periode) 
                    {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent a plus de 365 jours de présence en continue depuis le $DatePremAff jusqu'au $datefinaff.... "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " L'agent a plus de 365 jours de présence en continue depuis le $DatePremAff jusqu'au $datefinaff.... \n";
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
                        $NbreJours = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period) - $NbreJours;
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
                    else  // Le nombre de jours est < à 365 jours (donc l'agent n'est pas présent depuis plus d'un an)
                    {
                        if ($loginfo == true) { 
                            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent n'a pas atteint les 365 jours consécutifs => On calcule à 2,5 jours par mois "));
                        }
                        if ($displayinfo == true)
                        {
                            echo " L'agent n'a pas atteint les 365 jours consécutifs => On calcule à 2,5 jours par mois \n";
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
/*        
        // On vérifie si une demande de congé bonifié débute dans la période
        $debutperiode = $anneeref . $this->fonctions->debutperiode();
        $finperiode = ($anneeref + 1) . $this->fonctions->finperiode();
        $sql = "SELECT AGENTID,DATEDEBUT,DATEFIN FROM ABSENCERH WHERE AGENTID='$agentid' AND (TYPEABSENCE='CONGE_BONIFIE' OR TYPEABSENCE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
           echo "SELECT AGENTID,DATEDEBUT,DATEFIN FROM ABSENCERH => $erreur_requete <br>";
        }
        if (mysqli_num_rows($query) != 0) // Il existe un congé bonifié pour la période => On le solde des congés à 0
        {
            $resultcongbonif = mysqli_fetch_row($query);
            $solde_agent = 0;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent $agentid ($agentinfo) a une demande de congés bonifiés (du " . $resultcongbonif[1] . " au " . $resultcongbonif[2] . ") => Solde à 0 "));
        }
*/        
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
    	if (($resp->mail() . "") <> "")
    	{
            // Si le responsable de la structure est l'agent courant => L'agent est responsable
            if ($resp->agentid() == $this->agentid())
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
        function log_traces($loginfo,$displayinfo,$texttolog)
        {
            global $fonctions;
            
            if ($loginfo == true) 
            { 
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $texttolog"));
            }
            if ($displayinfo == true)
            {
                echo " $texttolog \n";
            }
        }

        function affectation_continue($datefinprecedente,$datedebutaff,$nbre_jour_periode)
        {
            global $fonctions;
            
            log_traces(true, false, "nbre_jour_periode => $nbre_jour_periode");            
            $nbrejrsmoyenparmois = ( $nbre_jour_periode / 12 );
            // Sur 4 mois, on a donc
            $nbrejrsinterval = intval($nbrejrsmoyenparmois * 4);
            log_traces(true, false, "Nombre de jours dans 4 mois => $nbrejrsinterval jours");            
            log_traces(true, false, "datefinprecedente = $datefinprecedente   datedebutaff = $datedebutaff");
            $datefinprecedente = date("Ymd", strtotime($datefinprecedente . "+1 day"));
            log_traces(true, false, "Le jour suivant la date de fin précédente = $datefinprecedente");      
            $nbrejrscalcule = $fonctions->nbjours_deux_dates($datefinprecedente, $datedebutaff)-1; // -1 => On doit exclure les deux dates extrèmes
            log_traces(true, false, "Il y a $nbrejrscalcule jours d'interruption entre les deux dates");
            if ($nbrejrscalcule > $nbrejrsinterval)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        
        function calcul_date_anniversaire($dateDebAff,$NbreJoursTotalAff,$nbre_jour_periode)
        {

            // On enlève le nombre de jours que l'agent à déjà effectué à la date de début de l'affectation
            $datedebuttheorique = date('Ymd',strtotime($dateDebAff. " - $NbreJoursTotalAff days"));
            // Ensuite on ajoute la durée minimum que l'agent doit avoir travaillé
            // Si l'agent doit avoir travaillé 10 mois on divise le nombre de jours de la période par 12 et on multiplie par 10
            $nbrejrsmoyenparmois = ( $nbre_jour_periode / 12 );
            // Sur 10 mois, on a donc
            $nbrejrsinterval = (floor($nbrejrsmoyenparmois * 10)-1); // On fait -1 car il faut exclure le jour extrème
            
            $dateanniv = date('Ymd',strtotime($datedebuttheorique . " + $nbrejrsinterval days"));
            return $dateanniv;
        }

        log_traces($loginfo,$displayinfo,"###########################################");
        log_traces($loginfo,$displayinfo,"Calcul solde de l'agent : " . $this->identitecomplete() . " - id : " . $this->agentid());
        log_traces($loginfo,$displayinfo,"###########################################");


        $datefinaff = '19000101'; // On initialise la date de fin du contrat précédent au 01/01/1900 (=> très loin dans le passé)
        $solde_agent = 0;
        
        if (is_null($anneeref))
        {
            $anneeref = $this->fonctions->anneeref();
        }
        // Construction des date de début et de fin de période (typiquement : 01/09/YYYY et 31/08/YYYY+1)
        $date_deb_period = $anneeref . $this->fonctions->debutperiode();
        $date_fin_period = ($anneeref + 1) . $this->fonctions->finperiode();
        log_traces($loginfo,$displayinfo,"date_deb_period = $date_deb_period");
        log_traces($loginfo,$displayinfo,"date_fin_period = $date_fin_period");

        // Calcul du nombre de jours dans la période => Typiquement 365 ou 366 jours.
        $nbre_jour_periode = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
        log_traces($loginfo,$displayinfo,"nbre_jour_periode = $nbre_jour_periode");

        // On charge le nombre de jours auquel un agent à droit sur l'année
        $nbr_jrs_offert = $this->fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
        log_traces($loginfo,$displayinfo,"Pour un temps complet sur toute la période, un agent a droit à $nbr_jrs_offert jours");

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
            $NbreJoursTotalAff = 0;
            $numcontratprecedent = 0;
            while ($result_aff = mysqli_fetch_row($query_aff)) 
            {
                log_traces($loginfo,$displayinfo,"----------------------------------");
                $datedebutaff = $this->fonctions->formatdatedb($result_aff[1]);
                $datefinprecedente = $datefinaff;
                $datefinaff = $this->fonctions->formatdatedb($result_aff[2]);

                if (($datefinaff == '00000000') or ($datefinaff > $date_fin_period))
                {
                    $datefinaff = $date_fin_period;
                }
                log_traces($loginfo,$displayinfo,"datedebutaff = $datedebutaff  datefinaff = $datefinaff  datefinprecedente = $datefinprecedente");

                // Calcul de la quotité de l'agent sur cette affectation
                $quotite = $result_aff[3] / $result_aff[4];
                $numcontrat = intval('0' .$result_aff[5]);
                log_traces($loginfo,$displayinfo,"quotite = $quotite  numcontrat = $numcontrat");
                
                // Ce n'est pas un contrat ==> On calcule normalement
                if ($numcontrat == "0")
                {
                    log_traces($loginfo,$displayinfo,"Ce n'est pas un contrat => Mode de calcul 'titulaire'");
                    // Si la date de fin de l'affectation est avant la période, on l'ignore
                    if ($datefinaff < $date_deb_period)
                    {
                        log_traces($loginfo,$displayinfo,"La date de fin de l'affectation est avant la période, on l'ignore");
                        continue;
                    }
                    // Si la date de début est avant le début de la période et que la date de fin est après le début de la période 
                    // on la fixe au début de la période <=> Les dates avant le début de la période sont ignorées
                    if ($datedebutaff < $date_deb_period and $datefinaff >= $date_deb_period)
                    {
                        $datedebutaff = $date_deb_period;
                    }
                    // La date de fin est déja limitée à la fin de la période si cela était nécessaire => On ne touche pas à la date de fin d'affectation

                    // On calcule le nombre de jours dans l'affectation dans la période
                    $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($datedebutaff, $datefinaff);
                    log_traces($loginfo,$displayinfo,"datedebutaff = $datedebutaff   datefinaff = $datefinaff  => L'agent est affecté $nbre_jour_aff_periode jours");
                    
                    // On calcule le nombre de jours que l'agent a acquis
                    $solde_aff = (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                    // Le solde de l'agent est modfié
                    $solde_agent = $solde_agent + $solde_aff;
                    log_traces($loginfo,$displayinfo,"Solde calculé => $solde_aff    nouveau solde de l'agent = $solde_agent");
                }
                // C'est un contrat => $numcontrat > 0
                else
                {
                    log_traces($loginfo,$displayinfo,"C'est un contrat => Mode de calcul à déterminer");
                    // On teste si le numéro de contrat est le même que le précédent => C'est le même contrat donc pas de test de discontinuité
                    // => Les deux affectations sont forcément continues car le contrat est le même
                    if ($numcontratprecedent==$numcontrat)
                    {
                        log_traces($loginfo,$displayinfo,"Le numéro du contrat ($numcontrat) est le même que l'affectation précédente => Il y a forcément continuité");                        
                    }
                    // On va regarder si les affectations sont continue <=> est-ce qu'il y a un 'trou' par rapport à la date de fin de l'affectation précédente
                    // C'est pour cela qu'on a initialisé $datefinprecedente à une valeur très loin dans le passé pour forcer un 'trou' si c'est la première affectation
                    elseif (!affectation_continue($datefinprecedente,$datedebutaff,$nbre_jour_periode))
                    {
                        log_traces($loginfo,$displayinfo,"L'affectation n'est pas continue");
                        // Les affectations ne sont pas continues => il y a un 'trou'
                        // 
                        // Il n'a donc plus de jours cumulés d'affectation => On repart à 0
                        $NbreJoursTotalAff = 0;
                        $datefinprecedente = $datefinaff;                        
                        log_traces($loginfo,$displayinfo,"NbreJoursTotalAff = $NbreJoursTotalAff    datefinprecedente = $datefinprecedente");
                    }
                    else
                    {
                        log_traces($loginfo,$displayinfo,"L'affectation est continue => pas de rupture");
                    }
                    
                    
                    // On calcule le nombre de jours dans l'affectation dans la période
                    $nbre_jour_aff = $this->fonctions->nbjours_deux_dates($datedebutaff, $datefinaff);  
                    log_traces($loginfo,$displayinfo,"Dans son affectation, l'agent travaille $nbre_jour_aff jours en continu ($datedebutaff -> $datefinaff)");
                    
                    // On calcule la date d'anniversaire à laquelle l'agent aura droit à un calcul de droit 'comme les titulaires'
                    $dateanniv = calcul_date_anniversaire($datedebutaff,$NbreJoursTotalAff,$nbre_jour_periode);
                    log_traces($loginfo,$displayinfo,"La date anniversaire pour obtenir un mode de calcule 'comme les titulaires' est $dateanniv (datedebutaff = $datedebutaff  NbreJoursTotalAff = $NbreJoursTotalAff)");
                    
                    // Si la date anniversaire est avant la date de début de l'affectation => Toute la période est 'comme les titulaires'
                    if ($dateanniv <= $datedebutaff)
                    {
                        log_traces($loginfo,$displayinfo,"Toute la période est 'comme les titulaires'");
                        
                        // Si la date de début est avant le début de la période et que la date de fin est après le début de la période 
                        // on la fixe au début de la période <=> Les dates avant le début de la période sont ignorées
                        if ($datedebutaff < $date_deb_period and $datefinaff >= $date_deb_period)
                        {
                            $datedebutaff = $date_deb_period;
                        }
                        // On calcule le nombre de jours dans l'affectation dans la période
                        $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($datedebutaff, $datefinaff);  
                        log_traces($loginfo,$displayinfo,"L'agent est affecté $nbre_jour_aff_periode jours entre le $datedebutaff et le $datefinaff");
                    
                        if ($datefinaff >= $date_deb_period)
                        {
                            // On calcule le nombre de jours que l'agent a acquis
                            $solde_aff = (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                            // Le solde de l'agent est modfié
                            $solde_agent = $solde_agent + $solde_aff;
                            log_traces($loginfo,$displayinfo,"Solde calculé => $solde_aff    nouveau solde de l'agent = $solde_agent");
                        }
                    }
                    // Si la date anniversaire est après la date de fin de l'affectation => Toute la période est à 2,5 jours/mois
                    elseif ($dateanniv > $datefinaff)
                    {
                        log_traces($loginfo,$displayinfo,"Toute la période est 'comme les contractuels'");
                        
                        // Si la date de début est avant le début de la période et que la date de fin est après le début de la période 
                        // on la fixe au début de la période <=> Les dates avant le début de la période sont ignorées
                        if ($datedebutaff < $date_deb_period and $datefinaff >= $date_deb_period)
                        {
                            $datedebutaff = $date_deb_period;
                        }
                        
                        // On calcule le nombre de jours dans l'affectation dans la période
                        $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($datedebutaff, $datefinaff);  
                        log_traces($loginfo,$displayinfo,"L'agent est affecté $nbre_jour_aff_periode jours entre le $datedebutaff et le $datefinaff");
                    
                        if ($datefinaff >= $date_deb_period)
                        {
                            // On calcule le nombre de jours que l'agent a acquis
                            $solde_aff = (((2.5 * 12) * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                            // Le solde de l'agent est modfié
                            $solde_agent = $solde_agent + $solde_aff;
                            log_traces($loginfo,$displayinfo,"Solde calculé => $solde_aff    nouveau solde de l'agent = $solde_agent");
                        }
                    }
                    // Si la date anniversaire est entre la date de début et la date de fin => On doit faire les deux calculs
                    // 2,5 jrs/mois sur la première partie de la période (entre date début et date anniversaire)
                    // "comme les titulaires" sur la deuxième partie de la période (entre date anniversaire et date de fin)
                    else
                    {
                        log_traces($loginfo,$displayinfo,"La période est a cheval 'comme les contractuels' et 'comme les titulaires'");
                        
                        // Si la date de début est avant le début de la période et que la date de fin est après le début de la période 
                        // on la fixe au début de la période <=> Les dates avant le début de la période sont ignorées
                        if ($datedebutaff < $date_deb_period and $datefinaff >= $date_deb_period)
                        {
                            $datedebutaff = $date_deb_period;
                        }
                        
                        // On calcule le nombre de jours dans l'affectation entre date début et la veille de la date anniversaire
                        $veilledateanniv = date("Ymd", strtotime($dateanniv . "-1 day"));
                        $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($datedebutaff, $veilledateanniv);  
                    
                        if ($datefinaff >= $date_deb_period)
                        {
                            // On calcule le nombre de jours que l'agent a acquis à 2,5 jrs/mois
                            $solde_aff = (((2.5 * 12) * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                            // Le solde de l'agent est modfié
                            $solde_agent = $solde_agent + $solde_aff;
                            log_traces($loginfo,$displayinfo,"Solde calculé en 'contractuel' (du $datedebutaff au $veilledateanniv) => $solde_aff    nouveau solde de l'agent = $solde_agent");
                        }
                        // On calcule le nombre de jours dans l'affectation entre date anniversaire et la date de fin
                        $nbre_jour_aff_periode = $this->fonctions->nbjours_deux_dates($dateanniv,$datefinaff);  
                        
                        if ($datefinaff >= $date_deb_period)
                        {
                            // On calcule le nombre de jours que l'agent a acquis "comme les titulaires"
                            $solde_aff = (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                            // Le solde de l'agent est modfié
                            $solde_agent = $solde_agent + $solde_aff;
                            log_traces($loginfo,$displayinfo,"Solde calculé en 'titulaire' (du $dateanniv au $datefinaff) => $solde_aff    nouveau solde de l'agent = $solde_agent");
                        }
                    }
                    // On ajoute le nombre de jours de l'affectation au nombre cumulé de jours déjà travaillé
                    $NbreJoursTotalAff = $NbreJoursTotalAff + $nbre_jour_aff;
                    log_traces($loginfo,$displayinfo,"L'agent a donc travaillé $NbreJoursTotalAff jours en continue");
                }
                $numcontratprecedent = $numcontrat;
            }
        }

        log_traces($loginfo,$displayinfo,"Le solde calculé est : $solde_agent");
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
        log_traces($loginfo,$displayinfo,"Le solde final est : $solde_agent");

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
        return ($solde_agent);
    }

    // L'autre version :
    //
    // On fait un tableau avec toutes les affectations
    // Tant qu'il y a des enregistrement
    //      tabaff[] = array(debut,fin, quotite, numcontrat)
    // Fin tantque
    // 
    // Tant qu'on n'est pas à la fin du tableau tabaff
    //      Lire l'élément courant
    //      Datedebutcontrat = DateDebutaff
    //      Si c'est un contrat
    //          Tant que le contrat de l'élement courant = le contrant de l'élement suivant
    //              Si datefinaff > datefinperiode
    //                  datefinaff = datefinperiode
    //              Finsi
    //              Si datedébutaff<datedebutpériode et datefinaff>=datedebutpériode
    //                  datedebutaff = datedebutpériode
    //              Finsi
    //              Si datedebutaff >= datedebutpériode
    //                  Nombrejoursaff_periode = Nombrejoursaff_periode + nbrejours_entre_date(datedebutaff,datefinaff);
    //              Finsi
    //              Lire l'elément suivant (next)
    //          Fin tantque
    //      finSi
    //      Datefincontrat = Datefinaff
    //      DureeContrat = nbrejours_entre_date(Datedebutcontrat,Datefincontrat)
    //      Si (pas un contrat)
    //          Nombrejoursaff_periode = DureeContrat
    //      FinSi
    //      Si (pas un contrat) ou (DureeContrat > 10 mois)
    //          solde_affectation = (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite
    //      Sinon
    //          solde_affectation = (((2.5 * 12) * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite
    //      finSi
    //      Passer à l'élément suivant du tabaff
    // Fin tantque
    //
    // Si solde>0
    //      On l'arrondi
    // FinSi
    //
    // Si maj_solde
    //      On enregistre les données
    // FinSi

    /**
     *
     * @param string $anneeref
     * @param boolean $maj_solde
     * @param boolean $loginfo
     * @param boolean $displayinfo
     * @return number of days
     */
    function newcalculsoldeannuel2($anneeref = null, $maj_solde = true, $loginfo = false, $displayinfo = false)
    {
        
        $this->fonctions->log_traces($loginfo,$displayinfo,"###########################################");
        $this->fonctions->log_traces($loginfo,$displayinfo,"Calcul solde de l'agent : " . $this->identitecomplete() . " - id : " . $this->agentid());
        $this->fonctions->log_traces($loginfo,$displayinfo,"###########################################");


        $datefinaff = '19000101'; // On initialise la date de fin du contrat précédent au 01/01/1900 (=> très loin dans le passé)
        $solde_agent = 0;
        
        if (is_null($anneeref))
        {
            $anneeref = $this->fonctions->anneeref();
        }
        // Construction des date de début et de fin de période (typiquement : 01/09/YYYY et 31/08/YYYY+1)
        $date_deb_period = $anneeref . $this->fonctions->debutperiode();
        $date_fin_period = ($anneeref + 1) . $this->fonctions->finperiode();
        $this->fonctions->log_traces($loginfo,$displayinfo,"date_deb_period = $date_deb_period");
        $this->fonctions->log_traces($loginfo,$displayinfo,"date_fin_period = $date_fin_period");

        // Calcul du nombre de jours dans la période => Typiquement 365 ou 366 jours.
        $nbre_jour_periode = $this->fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
        $this->fonctions->log_traces($loginfo,$displayinfo,"nbre_jour_periode = $nbre_jour_periode");

        // On charge le nombre de jours auquel un agent à droit sur l'année
        $nbr_jrs_offert = $this->fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
        $this->fonctions->log_traces($loginfo,$displayinfo,"Pour un temps complet sur toute la période, un agent a droit à $nbr_jrs_offert jours");

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
        $tabaff = array();
//        if (mysqli_num_rows($query_aff) != 0) // On a des d'affectations
        while ($result_aff = mysqli_fetch_row($query_aff)) 
        {
            //log_traces($loginfo,$displayinfo,"Les valeurs de la requete sont : " . var_export($result_aff, true));
            $affectation = new sihamaffectation;
            $affectation->debut = $this->fonctions->formatdatedb($result_aff[1]);
            //log_traces($loginfo,$displayinfo,"L'objet affectation : " . var_export($affectation, true));
            $affectation->fin = $this->fonctions->formatdatedb($result_aff[2]);
            if ($affectation->fin == '00000000')
            {
                $affectation->fin = '20991231';
            }
            //log_traces($loginfo,$displayinfo,"L'objet affectation : " . var_export($affectation, true));
            $affectation->quotite = $result_aff[3] / $result_aff[4];
            //log_traces($loginfo,$displayinfo,"L'objet affectation : " . var_export($affectation, true));
            $affectation->numcontrat = intval('0' .$result_aff[5]);
            //log_traces($loginfo,$displayinfo,"L'objet affectation : " . var_export($affectation, true));
            $tabaff[] = $affectation;
        }
        $this->fonctions->log_traces($loginfo,$displayinfo,"Le tableau des affectations de l'agent est créé : " . var_export($tabaff, true));

        $currentaff = current($tabaff);
        $datefinprecedente = '19000101';  // On fixe la date loin dans le passée <=> 01/01/1900
        $nbrejourtravailletotal = 0;
        while ($currentaff !== false)
        {
            $datedebutstatut = $currentaff->debut;
            $datefinstatut = $currentaff->fin;
            $numcontrat = $currentaff->numcontrat;
            $quotite = $currentaff->quotite;
            $nbrejourtravaillestatut = 0;
            $nbrejoursaff = 0;
            $nbrejourtravailletotalperiode = 0;
            if ($numcontrat != "0")
            {
                if (!$this->fonctions->affectation_continue($datefinprecedente,$datedebutstatut,$nbre_jour_periode))
                {
                    $nbrejoursaff = 0;
                    $nbrejourtravaillestatut = 0;
                    $nbrejourtravailletotal = 0;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Il y a une rupture de contrat car datefinprecedente = $datefinprecedente => nbrejoursaff = $nbrejoursaff et nbrejourtravaillestatut = $nbrejourtravaillestatut");
                }
                
                while ($currentaff!==false and $currentaff->numcontrat == $numcontrat)
                {
                    // On calcule le nombre de jours sans travail dans le statut => différence entre la fin du statut précédent et la date début de l'actuel
                    $this->fonctions->log_traces($loginfo,$displayinfo,"datefinstatut = $datefinstatut   currentaff->debut = " . $currentaff->debut);
                    $nbrejourssanstravailaff = $this->fonctions->nbjours_deux_dates($datefinstatut, $currentaff->debut) - 2; // On doit exclure les 2 dates limites
                    // On fixe la fin du statut à la fin de l'affectation en cours
                    $datefinstatut = $currentaff->fin;
                    // On calcule le nombre de jours où l'agent à été affecté sur l'affectation courante
                    $nbrejoursaff = $this->fonctions->nbjours_deux_dates($currentaff->debut, $currentaff->fin);
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Entre le " . $currentaff->debut . " et le " . $currentaff->fin . " l'agent est affecté $nbrejoursaff jours");
                    // On ajoute ce nombre de jours au total du statut
                    $nbrejourtravaillestatut = $nbrejourtravaillestatut + $nbrejoursaff;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"L'agent a cumulé sur son statut nbrejourtravaillestatut = $nbrejourtravaillestatut jours de travail");
                    // On a joute ce nombre de jours au total de jours travaillés en continu
                    $nbrejourtravailletotal = $nbrejourtravailletotal + $nbrejoursaff;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Depuis sa dernière interruption l'agent a cumulé $nbrejourtravailletotal jours de travail");
                    
                    if ($currentaff->debut < $date_deb_period and $currentaff->fin >= $date_deb_period)
                    {
                        $currentaff->debut = $date_deb_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de début du statut à la date de début de la peride : $date_deb_period");
                    }
                    if ($currentaff->fin > $date_fin_period)
                    {
                        $currentaff->fin = $date_fin_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de fin du statut à la date de fin de la peride : $date_fin_period");
                    }
                    if ($currentaff->debut >= $date_deb_period)
                    {
                        // On calcule le nombre de jours ou l'agent a travaillé dans la période
                        $nbrejourtravailleperiode = $this->fonctions->nbjours_deux_dates($currentaff->debut, $currentaff->fin);
                        $this->fonctions->log_traces($loginfo,$displayinfo,"L'agent a travaillé sur la péride : nbrejourtravailleperiode = $nbrejourtravailleperiode jours");
                        // On a joute ce nombre de jours au total de jours travaillés en continu
                        $nbrejourtravailletotalperiode = $nbrejourtravailletotalperiode + $nbrejourtravailleperiode;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Sur la période $date_deb_period -> $date_fin_period, l'agent a cumulé $nbrejourtravailletotalperiode jours de travail");
                    }
                    
                    $currentaff = next($tabaff);
                }
                $currentaff = prev($tabaff);
            }
            else
            {
                $nbrejourtravaillestatut = $this->fonctions->nbjours_deux_dates($datedebutstatut,$datefinstatut);
                $this->fonctions->log_traces($loginfo,$displayinfo,"L'agent titulaire a cumulé sur son statut (entre $datedebutstatut et $datefinstatut) le nbrejourtravaillestatut = $nbrejourtravaillestatut jours de travail");
                if ($currentaff->debut < $date_deb_period and $currentaff->fin >= $date_deb_period)
                {
                    $currentaff->debut = $date_deb_period;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de début du statut à la date de début de la peride : $date_deb_period");
                }
                if ($currentaff->fin > $date_fin_period)
                {
                    $currentaff->fin = $date_fin_period;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de fin du statut à la date de fin de la peride : $date_fin_period");
                }
                $nbrejourtravailletotalperiode = $this->fonctions->nbjours_deux_dates($currentaff->debut, $currentaff->fin);
                $this->fonctions->log_traces($loginfo,$displayinfo,"Sur la période " . $currentaff->debut . " -> " . $currentaff->fin . ", l'agent a cumulé $nbrejourtravailletotalperiode jours de travail");
                
            }
            
            $nbrejourstatuttotal = $this->fonctions->nbjours_deux_dates($datedebutstatut,$datefinstatut);
            $this->fonctions->log_traces($loginfo,$displayinfo,"Entre le $datedebutstatut et le $datefinstatut => Le statut dure $nbrejourstatuttotal jours");
            
            // Si la date de fin du statut est après la date de début de la période courante, on calcule le nombre de jours congés
            // Sinon on l'ignore car hors période
            if ($datefinstatut >= $date_deb_period)
            {
                
                // Si c'est un titulaire (numérocontrat = 0) 
                //     ou si l'agent a une date de fin de statut > la date anniversaire des 10 mois d'ancienneté 
                //     ou si l'agent a une date de fin de statut > la date anniversaire des 10 mois de statut
                // On calcule avec les droits de titulaires
                $dateanniv_statut = $this->fonctions->calcul_date_anniversaire($datefinstatut,$nbrejourtravaillestatut,$nbre_jour_periode);
                $dateanniv_anciennete = $this->fonctions->calcul_date_anniversaire($datefinstatut,$nbrejourtravailletotal,$nbre_jour_periode);
                $this->fonctions->log_traces($loginfo,$displayinfo,"Avant de déterminer si on est en mode titulaire ou contractuel : numcontrat=$numcontrat  datefinstatut=$datefinstatut  dateanniv_statut=$dateanniv_statut  dateanniv_anciennete=$dateanniv_anciennete");
                if ($numcontrat=='0' 
                 or $datefinstatut >= $dateanniv_statut
                 or $datefinstatut >= $dateanniv_anciennete)
                {
                    $this->fonctions->log_traces($loginfo,$displayinfo,"On est en mode titulaire");
                    if ($datedebutstatut < $date_deb_period and $datefinstatut >= $date_deb_period)
                    {
                        $datedebutstatut = $date_deb_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de début du statut à la date de début de la peride : $date_deb_period");
                    }
                    if ($datefinstatut > $date_fin_period)
                    {
                        $datefinstatut = $date_fin_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de fin du statut à la date de fin de la peride : $date_fin_period");
                    }

                    //$this->fonctions->log_traces($loginfo,$displayinfo,"Avant le calcul nbrejours_periode_statut : datedebutstatut=$datedebutstatut  datefinstatut=$datefinstatut");
                    //$nbrejours_periode_statut = $this->fonctions->nbjours_deux_dates($datedebutstatut,$datefinstatut);
                    
                    $this->fonctions->log_traces($loginfo,$displayinfo,"Les données pour calculer le solde_statut sont : nbrejourtravailletotalperiode=$nbrejourtravailletotalperiode  nbre_jour_periode=$nbre_jour_periode  quotite=$quotite");                    
                    $solde_statut = (($nbr_jrs_offert * $nbrejourtravailletotalperiode) / $nbre_jour_periode) * $quotite;
                    $this->fonctions->log_traces($loginfo,$displayinfo,"C'est un mode de calcul titulaire. On a donc le solde de son statut solde_statut =  $solde_statut");
                    // Le solde de l'agent est modfié
                    $solde_agent = $solde_agent + $solde_statut;                
                    $this->fonctions->log_traces($loginfo,$displayinfo,"C'est un mode de calcul titulaire. On a donc le solde de l'agent solde_agent = $solde_agent");
                }
                
                // Pas un titulaire
                else
                {
                    $this->fonctions->log_traces($loginfo,$displayinfo,"On est en mode contractuel");
                    $this->fonctions->log_traces($loginfo,$displayinfo,"nbrejourtravailletotal = $nbrejourtravailletotal");
                    $this->fonctions->log_traces($loginfo,$displayinfo,"nbrejourtravaillestatut = $nbrejourtravaillestatut");
                    $this->fonctions->log_traces($loginfo,$displayinfo,"nbrejourtravailletotalperiode = $nbrejourtravailletotalperiode");
                    
                    // On calcule la date anniversaire des 10 mois sur le statut courant
                    $dateanniv = $this->fonctions->calcul_date_anniversaire($datefinstatut,$nbrejourtravailletotalperiode,$nbre_jour_periode);
                    $this->fonctions->log_traces($loginfo,$displayinfo,"La date anniversaire est : $dateanniv");                        
                    
                    // Si la date d'anniversaire est postérieur à la date de fin de période => On dit que la date d'anniversaire est la date de début de la période de l'année suivante
                    if ($dateanniv > $date_fin_period)
                    {
                        $dateanniv = ($anneeref+1) . $this->fonctions->debutperiode();
                        $this->fonctions->log_traces($loginfo,$displayinfo,"La date anniversaire est plus loin que la fin de la période => forcée à : $dateanniv");                        
                    }
                    if ($dateanniv > $datefinstatut)
                    {
                        $dateanniv = date("Ymd", strtotime($datefinstatut . "+1 day"));
                        $this->fonctions->log_traces($loginfo,$displayinfo,"La date anniversaire est plus loin que la date de fin de statut => forcée à : $dateanniv");                        
                    }
                    
                    if ($datedebutstatut < $date_deb_period and $datefinstatut >=$date_deb_period)
                    {
                        $datedebutstatut = $date_deb_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de début du statut à la date de début de la peride : $date_deb_period");
                    }
                    if ($datefinstatut > $date_fin_period)
                    {
                        $datefinstatut = $date_fin_period;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"On force la date de fin du statut à la date de fin de la peride : $date_fin_period");
                    }

                    // On calcule le nombre de jours entre date début du statut et la veille de la date anniversaire
                    $veilledateanniv = date("Ymd", strtotime($dateanniv . "-1 day"));
                    $nbre_jour_statut_avant_anniv = $this->fonctions->nbjours_deux_dates($datedebutstatut, $veilledateanniv);  
                    if ($nbre_jour_statut_avant_anniv < 0)
                    {
                        $nbre_jour_statut_avant_anniv = 0;
                    }
                    if ($datefinstatut >= $date_deb_period)
                    {
                        // On calcule le nombre de jours que l'agent a acquis à 2,5 jrs/mois
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Avant le calcul solde 'contractuel' => nbre_jour_statut_avant_anniv=$nbre_jour_statut_avant_anniv  nbre_jour_periode=$nbre_jour_periode  quotite=$quotite");                        
                        $solde_statut_avant_anniv = (((2.5 * 12) * $nbre_jour_statut_avant_anniv) / $nbre_jour_periode) * $quotite;
                        // Le solde de l'agent est modfié
                        $solde_agent = $solde_agent + $solde_statut_avant_anniv;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Solde calculé en 'contractuel' (du $datedebutstatut au $veilledateanniv) => $solde_statut_avant_anniv    nouveau solde de l'agent = $solde_agent");
                    }
                    
                    // Si la date d'anniversaire est avant la période on la fixe à la période
                    if ($dateanniv < $date_deb_period)
                    {
                        $dateanniv = $date_deb_period;
                    }

                    // On calcule le nombre de jours dans le statut entre date anniversaire et la date de fin du statut
                    $nbre_jour_statut_apres_anniv = $this->fonctions->nbjours_deux_dates($dateanniv,$datefinstatut);  

                    if ($datefinstatut >= $date_deb_period)
                    {
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Avant le calcul solde 'titulaire' => nbre_jour_statut_apres_anniv=$nbre_jour_statut_apres_anniv  nbre_jour_periode=$nbre_jour_periode  quotite=$quotite");                        
                        // On calcule le nombre de jours que l'agent a acquis "comme les titulaires"
                        $solde_statut_apres_anniv = (($nbr_jrs_offert * $nbre_jour_statut_apres_anniv) / $nbre_jour_periode) * $quotite;
                        // Le solde de l'agent est modfié
                        $solde_agent = $solde_agent + $solde_statut_apres_anniv;
                        $this->fonctions->log_traces($loginfo,$displayinfo,"Solde calculé en 'titulaire' (du $dateanniv au $datefinstatut) => $solde_statut_apres_anniv    nouveau solde de l'agent = $solde_agent");
                    }
                }
            }
            else
            {
                $this->fonctions->log_traces($loginfo,$displayinfo,"Le statut n'est pas dans la période => On ne calcule pas les congés de l'agent");
            }
            $datefinprecedente = $datefinstatut;
            // On passe à l'affectation suivante
            $currentaff = next($tabaff);
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
        $this->fonctions->log_traces($loginfo,$displayinfo,"Le solde final est : $solde_agent");

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
        
}

?> 