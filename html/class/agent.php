<?php

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

    private $harpegeid = null;

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
        if (is_null($this->dbconnect)) {
            $errlog = "Agent->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    /**
     *
     * @param string $harpegeid
     *            the harpege identifier of the current agent
     * @return boolean TRUE if all correct, FALSE otherwise
     */
    function load($harpegeid)
    {
        // echo "Debut Load";
        if (is_null($this->harpegeid)) {
            
            $sql = sprintf("SELECT HARPEGEID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION, STRUCTUREID FROM AGENT WHERE HARPEGEID='%s'", $this->fonctions->my_real_escape_utf8($harpegeid));
            // echo "sql = " . $sql . "<br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Agent->Load (AGENT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Agent->Load (AGENT) : Agent $harpegeid non trouvé";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
            $result = mysqli_fetch_row($query);
            $this->harpegeid = "$result[0]";
            $this->civilite = "$result[1]";
            $this->nom = "$result[2]";
            $this->prenom = "$result[3]";
            $this->adressemail = "$result[4]";
            $this->typepopulation = "$result[5]";
            $this->structureid = "$result[6]";
            return true;
        }
        // echo "Fin...";
    }

    /**
     *
     * @param
     * @return string the harpege identifier of the current agent
     */
    function harpegeid()
    {
        return $this->harpegeid;
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
            } else
                return $this->nom;
        } else
            $this->nom = $name;
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
            } else
                return $this->prenom;
        } else
            $this->prenom = $firstname;
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
            } else
                return $this->civilite;
        } else
            $this->civilite = $civilite;
    }

    /**
     *
     * @param
     * @return string the full name of the current agent (civility + firstname + name)
     */
    function identitecomplete()
    {
        return $this->civilite() . " " . $this->prenom() . " " . $this->nom();
    }

    /**
     *
     * @param string $mail
     *            optional the mail of the current agent
     * @return string mail of the current agent if $mail parameter not set. No return otherwise
     */
    function mail($mail = null)
    {
        if (is_null($mail)) {
            if (is_null($this->adressemail)) {
                $errlog = "Agent->mail : Le mail de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->adressemail;
        } else
            $this->adressemail = $mail;
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
            } else
                return $this->typepopulation;
        } else
            $this->codestructure = $type;
    }
    
    /**
     *
     * @param
     * @return string the structure identifier for the current agent
     */
    function structureid()
    {
    	if (is_null($this->structureid)) {
    		$errlog = "Agent->structureid : L'Id de la structure n'est pas défini !!!";
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	} else
    		return $this->structureid;
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
        $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID='%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
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
        $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE IDDELEG='%s' AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG", $this->fonctions->my_real_escape_utf8($this->harpegeid));
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
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
        $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID='%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
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
        $sql = sprintf("SELECT VALEUR,STATUT,DATEDEBUT,DATEFIN FROM COMPLEMENT WHERE HARPEGEID='%s' AND COMPLEMENTID='ESTADMIN'", $this->fonctions->my_real_escape_utf8($this->harpegeid));
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estadministrateur (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        if (mysqli_num_rows($query) == 0)
            return FALSE;
        $result = mysqli_fetch_row($query);
        return (strcasecmp($result[0], "O") == 0);
    }

    /**
     *
     * @param string $typeprofil
     *            optional Type de profil RH demandé => 1 = RHCET, 2 = RHCONGE. Si null => tous les profils
     * @return boolean true if the current agent has the selected profil. false otherwise.
     */
    function estprofilrh($typeprofil = null)
    {
        $sql = "SELECT VALEUR,STATUT,DATEDEBUT,DATEFIN FROM COMPLEMENT WHERE HARPEGEID='%s' AND COMPLEMENTID IN (";
        if (is_null($typeprofil)) {
            $sql = $sql . "'RHCET', 'RHCONGE'";
        } elseif ($typeprofil == 1) {
            $sql = $sql . "'RHCET'";
        } elseif ($typeprofil == 2) {
            $sql = $sql . "'RHCONGE'";
        } else {
            $errlog = "Agent->estprofilrh (AGENT) : Type de profil demandé inconnu (typeprofil = $typeprofil)";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        $sql = $sql . ")";
        $sql = sprintf($sql, $this->fonctions->my_real_escape_utf8($this->harpegeid));
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->estprofilrh (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return FALSE;
        }
        if (mysqli_num_rows($query) == 0)
            return FALSE;
        $result = mysqli_fetch_row($query);
        return (strcasecmp($result[0], "O") == 0);
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
            $complement->load($this->harpegeid, 'ENFANTMALADE');
            return intval($complement->valeur());
        } elseif ((strcasecmp(intval($nbrejrs), $nbrejrs) == 0) and (intval($nbrejrs) >= 0)) // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
        {
            $complement->complementid('ENFANTMALADE');
            $complement->harpegeid($this->harpegeid);
            $complement->valeur(intval($enfantmaladevalue));
            $complement->store();
        } else {
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
        $sql = "SELECT SUM(DEMANDE.NBREJRSDEMANDE) FROM AFFECTATION,DECLARATIONTP,DEMANDEDECLARATIONTP,DEMANDE
WHERE AFFECTATION.HARPEGEID='" . $this->harpegeid . "'
AND AFFECTATION.AFFECTATIONID=DECLARATIONTP.AFFECTATIONID
AND DECLARATIONTP.DECLARATIONID=DEMANDEDECLARATIONTP.DECLARATIONID
AND DEMANDE.DEMANDEID = DEMANDEDECLARATIONTP.DEMANDEID
AND DEMANDE.TYPEABSENCEID='enmal'
AND DEMANDE.DATEDEBUT>='" . $this->fonctions->formatdatedb($debut_interval) . "'
AND DEMANDE.DATEFIN<='" . $this->fonctions->formatdatedb($fin_interval) . "'
AND DEMANDE.STATUT='v'";
        
        // $this->fonctions->anneeref() . $this->fonctions->debutperiode()
        // ($this->fonctions->anneeref() +1) . $this->fonctions->finperiode()
        // echo "SQL = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
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
    function planning($debut_interval, $fin_interval, $incudeteletravail = false)
    {
        $planning = new planning($this->dbconnect);
        $planning->load($this->harpegeid, $debut_interval, $fin_interval, $incudeteletravail);
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
    function planninghtml($debut_interval, $fin_interval, $clickable = FALSE, $showpdflink = TRUE, $incudeteletravail = FALSE)
    {
        $planning = new planning($this->dbconnect);
        $htmltext = $planning->planninghtml($this->harpegeid, $debut_interval, $fin_interval, $clickable, $showpdflink, false, $incudeteletravail);
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
     * @return
     */
    function sendmail($destinataire = null, $objet = null, $message = null, $piecejointe = null, $ics = null, $checkgrouper = false)
    {
    	if ($checkgrouper && !$destinataire->isG2tUser())
    	{
    		// le destinataire ne fait pas partie des utilisateurs G2T
    		$errorlog = "sendmail annulé car expéditeur absent des utilisateurs G2T (".$destinataire->identitecomplete().") \n";
    		$errorlog .= "objet du mail : ".$objet."\n";
    		$errorlog .= "contenu du mail : ".$message."\n";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
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
	        $msg .= "Bonjour " . utf8_encode(ucwords(mb_strtolower($destinataire->identitecomplete(),'UTF-8'))) . ",<br><br>";
	        $msg .= nl2br(htmlentities("$message", ENT_QUOTES, "UTF-8", false)) . "<br>Cliquez sur le lien <a href='" . $this->fonctions->liredbconstante('G2TURL') . "'>G2T</a><br><br>Cordialement<br><br>" . ucwords(mb_strtolower($this->prenom . " " . $this->nom),'UTF-8') . "\r\n";
	        
	        // $msg .= htmlentities("$message",ENT_IGNORE,"ISO8859-15") ."<br><br>Cordialement<br><br>" . ucwords(strtolower("$PRENOM $NOM")) ."\r\n";
	        $msg .= "\r\n";
	        
	        if (! is_null($ics)) {
	            // Si le fichier ics existe ==> On met à jour le calendrier de l'agent
	            $errormsg = $destinataire->updatecalendar($ics);
	            // Si tout c'est bien passé, pas la peine de joindre l'ICS....
	            // echo "Error Msg = XXX" .$errormsg . "XXX<br>";
	            
	            // if ($errormsg <> "")
	            // {
	            $msg .= "<br><br><p><font size=\"2\">La pièce jointe est un fichier iCalendar contenant plus d'informations concernant l'événement.<br>Si votre client de courrier supporte les requêtes iTip vous pouvez utiliser ce fichier pour mettre à jour votre copie locale de l'événement.</font></p>";
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
	        
	        // ini_set(sendmail_from,$this->adressemail);
	        ini_set('sendmail_from', $this->prenom() . " " . $this->nom() . " <" . $this->adressemail . ">");
	        ini_set('SMTP', $this->fonctions->liredbconstante("SMTPSERVER"));
	        // $objet .=" G2T";
	        mail($destinataire->prenom() . " " . $destinataire->nom() . " <" . $destinataire->mail() . ">", "$encoded_subject", "$msg", "$header");
	//        mail($destinataire->prenom() . " " . $destinataire->nom() . " <" . $destinataire->mail() . ">", "$objet", "$msg", "$header");
	        // mail($destinataire->prenom() . " " . $destinataire->nom() . " <" .$destinataire->mail() . ">", utf8_encode("$objet"), "$msg", "$header");
	        ini_restore('sendmail_from');
	    }

    }

    /**
     *
     * @param date $datedebut
     *            the beginning date of the interval to search affectations
     * @param date $datefin
     *            the ending date of the interval to search affectations
     * @return array list of objects affectation
     */
    function affectationliste($datedebut, $datefin)
    {
        $affectationliste = null;
        $sql = "SELECT SUBREQ.AFFECTATIONID FROM ((SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,HARPEGEID FROM AFFECTATION WHERE HARPEGEID = '" . $this->harpegeid . "' AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN OR DATEFIN='0000-00-00'))";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,HARPEGEID FROM AFFECTATION WHERE HARPEGEID='" . $this->harpegeid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE,HARPEGEID FROM AFFECTATION WHERE HARPEGEID='" . $this->harpegeid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
        $sql = $sql . ", AGENT ";
        $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N' ";
        $sql = $sql . "   AND AGENT.HARPEGEID = SUBREQ.HARPEGEID ";
        $sql = $sql . "   AND AGENT.STRUCTUREID <> '' ";
        $sql = $sql . " ORDER BY SUBREQ.DATEDEBUT";
        // echo "sql = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->affectationliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->affectationliste : L'agent $this->harpegeid n'a pas d'affectation entre $datedebut et $datefin <br>";
        }
        while ($result = mysqli_fetch_row($query)) {
            $affectation = new affectation($this->dbconnect);
            // echo "result[0] = $result[0] <br>";
            $affectation->load("$result[0]");
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
        $planning->load($this->harpegeid, $datedebut, $datefin);
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
            $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID = '%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
            // echo "sql = " . $sql . "<br>";
            $query = mysqli_query($this->dbconnect, $sql);
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
                $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE IDDELEG='%s' AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG", $this->fonctions->my_real_escape_utf8($this->harpegeid));
                // echo "sql = " . $sql . "<br>";
                $query = mysqli_query($this->dbconnect, $sql);
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
            $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID = '%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
            // echo "sql = " . $sql . "<br>";
            $query = mysqli_query($this->dbconnect, $sql);
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
                            if ($agent->harpegeid() == $this->harpegeid) {
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
    function soldecongesliste($anneeref = null, &$erreurmsg = "")
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
        $complement->load($this->harpegeid, "REPORTACTIF");
        // Si le complement n'est pas initialisé (NULL ou "") alors on active le report
        if (strcasecmp($complement->valeur(), "O") == 0) // or strlen($complement->valeur()) == 0)
            $reportactif = true;
        else
            $reportactif = FALSE;
        
        if ((date("Ymd") >= $anneeref . $this->fonctions->debutperiode() && date("Ymd") <= $annee_recouvr . $this->fonctions->liredbconstante("FIN_REPORT")) && $reportactif) {
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND (ANNEEREF= '" . $anneeref . "' OR ANNEEREF= '" . ($anneeref - 1) . "'))";
        } else {
            $requ_sel_typ_conge = "((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND ANNEEREF= '" . $anneeref . "')";
        }
        
        $sql = "SELECT SOLDE.TYPEABSENCEID FROM SOLDE,TYPEABSENCE WHERE HARPEGEID='" . $this->harpegeid . "' AND SOLDE.TYPEABSENCEID=TYPEABSENCE.TYPEABSENCEID  AND " . $requ_sel_typ_conge;
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->soldecongesliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->soldecongesliste : L'agent $this->harpegeid n'a pas de solde de congés pour l'année de référence $anneeref. <br>";
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
                $solde->load($this->harpegeid, "$result[0]");
                $soldeliste[$solde->typeabsenceid()] = $solde;
                unset($solde);
            }
        }
        
        // echo "Avant le new.. <br>";
        $cet = new cet($this->dbconnect);
        // echo "Avant le load du CET <br>";
        $erreur = $cet->load($this->harpegeid);
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
                $solde->load($this->harpegeid, $cet->idtotal());
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
            $pdf->Image($this->fonctions->g2tbasepath() . '/html/images/logo_papeterie.png', 10, 5, 60, 20);
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
                $pdf->Cell(60, 10, utf8_decode('Service : ' . $nomstructure));
            }
            
            // $pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('.$this->structure()->nomcourt() . ')' );
            $pdf->Ln(5);
            $pdf->Cell(60, 10, utf8_decode('Historique des demandes de  : ' . $this->civilite() . " " . $this->nom() . " " . $this->prenom()));
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
            $pdf->Cell(60, 10, utf8_decode('Edité le ' . date("d/m/Y")));
        }
        $pdf->SetFont('helvetica', '', 6, '', true);
        $pdf->Ln(10);
        
        if (! $infoagent) {
            $headertext = "Etat des soldes pour l'année $anneeref / " . ($anneeref + 1) . " du " . $this->fonctions->formatdate($anneeref . $this->fonctions->debutperiode()) . " au ";
            if (date("Ymd") > ($anneeref + 1) . $this->fonctions->finperiode())
                $headertext = $headertext . $this->fonctions->formatdate(($anneeref + 1) . $this->fonctions->finperiode());
            else
                $headertext = $headertext . date("d/m/Y");
                $pdf->Cell(215, 5, utf8_decode($headertext), 1, 0, 'C');
        } else
            $pdf->Cell(215, 5, utf8_decode("Etat des soldes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom()), 1, 0, 'C');
        $pdf->Ln(5);
        $pdf->Cell(75, 5, utf8_decode("Type congé"), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode("Droits acquis"), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode("Droit pris"), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode("Solde actuel"), 1, 0, 'C');
        $pdf->Cell(50, 5, utf8_decode("Demandes en attente"), 1, 0, 'C');
        $pdf->Ln(5);
        
        $totaldroitaquis = 0;
        $totaldroitpris = 0;
        $totaldroitrestant = 0;
        $totaldemandeattente = 0;
        $soldeliste = $this->soldecongesliste($anneeref);
        foreach ((array) $soldeliste as $key => $tempsolde) {
            $pdf->Cell(75, 5, utf8_decode($tempsolde->typelibelle()), 1, 0, 'C');
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
            $pdf->Cell(30, 5, utf8_decode($textdroitaquis), 1, 0, 'C');
            if (strcmp($tempsolde->typeabsenceid(), 'cet') == 0) // Si c'est un CET, on n'affiche pas les droits pris
            {
                $pdf->Cell(30, 5, utf8_decode(""), 1, 0, 'C');
            }
            else
            {
                $pdf->Cell(30, 5, utf8_decode($tempsolde->droitpris() . ""), 1, 0, 'C');
            }
            $pdf->Cell(30, 5, utf8_decode($tempsolde->solde() . ""), 1, 0, 'C');
            $pdf->Cell(50, 5, utf8_decode($tempsolde->demandeenattente() . ""), 1, 0, 'C');
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
        $pdf->Cell(8, 5, utf8_decode("Soldes de congés donnés sous réserve du respect des règles de gestion"));
        $pdf->Ln(8);
        // ob_end_clean();
        if ($closeafter == TRUE)
            $pdf->Output();
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
        
        $htmltext = $htmltext . "         <tr class='entete'><td>Type congé</td><td>Droits acquis</td><td>Droit pris</td><td>Solde actuel</td><td>Demandes en attente</td></tr>";
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
        $htmltext = $htmltext . "<font color='#EF4001'>Soldes de congés donnés sous réserve du respect des règles de gestion</font>";
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
				FROM DEMANDE,AFFECTATION,DECLARATIONTP,DEMANDEDECLARATIONTP 
				WHERE DEMANDEDECLARATIONTP.DEMANDEID= DEMANDE.DEMANDEID
				   AND DEMANDEDECLARATIONTP.DECLARATIONID = DECLARATIONTP.DECLARATIONID
				   AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID
				   AND AFFECTATION.HARPEGEID = '" . $this->harpegeid() . "'
			       AND ((DEMANDE.DATEDEBUT <= '" . $debut_interval . "' AND DEMANDE.DATEFIN >='" . $debut_interval . "')
						OR (DEMANDE.DATEFIN >= '" . $fin_interval . "' AND DEMANDE.DATEDEBUT <='" . $fin_interval . "')
						OR (DEMANDE.DATEDEBUT >= '" . $debut_interval . "' AND DEMANDE.DATEFIN <= '" . $fin_interval . "'))
				ORDER BY DEMANDE.DATEDEBUT";
        
        // echo "Agent->demandesliste SQL = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->demandesliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Agent->demandesliste : Il n'y a pas de demande de congé/absence pour cet agent " . $this->harpegeid() . " dans l'interval de temps " . $this->fonctions->formatdate($debut_interval) . " -> " . $this->fonctions->formatdate($debut_interval) . "<br>";
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
            $htmltext = $htmltext . "   <tr class='titre'><td>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
        else {
            $htmltext = $htmltext . "   <tr class='titre'><td colspan=7>Tableau récapitulatif des demandes</td></tr>";
            $htmltext = $htmltext . "   <tr class='entete'><td>Type de congé</td><td>Date de dépot</td><td>Date de début</td><td>Date de fin</td><td>Nbr de jours</td><td>Etat de la demande</td><td>Motif (obligatoire si le congé est annulé)</td></tr>";
            foreach ($demandeliste as $key => $demande) {
                if ($demande->motifrefus() != "" or strcasecmp($demande->statut(), "r") != 0) {
                    $htmltext = $htmltext . "<tr class='element'>";
                    $libelledemande = $demande->typelibelle();
                    if (strlen($libelledemande) > 40) {
                        $libelledemande = mb_substr($demande->typelibelle(), 0, 40, 'UTF-8') . "...";
                    }
                    
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $libelledemande;
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->date_demande() . " " . $demande->heure_demande();
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $demande->nbrejrsdemande();
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>";
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                    }
                    $htmltext = $htmltext . $this->fonctions->demandestatutlibelle($demande->statut());
                    if (strlen($demande->commentaire()) != 0) {
                        $htmltext = $htmltext . "</span>";
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "   <td>" . $demande->motifrefus() . "</td>";
                    $htmltext = $htmltext . "</tr>";
/*
                    if (strcasecmp($demande->statut(), "r") != 0) // Si la demande n'est pas annulée ou refusée
                    {
                        if (isset($synthesetab[$demande->typelibelle()]))
                            $synthesetab[$demande->typelibelle()] = $synthesetab[$demande->typelibelle()] + $demande->nbrejrsdemande();
                        else
                            $synthesetab[$demande->typelibelle()] = $demande->nbrejrsdemande();
                    }
*/
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
                    $synthesetab[$element->info()] = $synthesetab[$element->info()] + 0.5;
                 else
                    $synthesetab[$element->info()] = 0.5;
            }
        }
        
        if (count($synthesetab) > 0) {
            $htmltext = $htmltext . "<br>";
            // $htmltext = $htmltext . print_r($synthesetab,true);
            $htmltext = $htmltext . "<div id='demandeliste'>";
            $htmltext = $htmltext . "<center><table class='tableau' >";
            $htmltext = $htmltext . "   <tr class='titre'><td colspan=2>Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " au " . $this->fonctions->formatdate($datefin) . "</td></tr>";
            $htmltext = $htmltext . "   <tr class='entete'><td>Type de congé</td><td>Droit pris</td></tr>";
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
            $htmltext = $htmltext . "<form name='userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . "_" . $tempannee . "'  method='post' action='affiche_pdf.php' target='_blank'>";
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->harpegeid() . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='no'>";
            // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . $tempannee . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='typepdf' value='listedemande'>";
            $htmltext = $htmltext . "</form>";
            $htmltext = $htmltext . "<a href='javascript:document.userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . "_" . $tempannee . ".submit();'>Liste des demandes en PDF</a>";
            
            $htmltext = $htmltext . "<br>";
            // Année précédente
            $tempannee = substr($this->fonctions->formatdatedb($datedebut), 0, 4) - 1;
            $htmltext = $htmltext . "<form name='userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . "_" . $tempannee . "'  method='post' action='affiche_pdf.php' target='_blank'>";
            $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->harpegeid() . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='userpdf' value='no'>";
            // $htmltext = $htmltext . "<input type='hidden' name='previous' value='" . $_POST["previous"] . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='anneeref' value='" . $tempannee . "'>";
            $htmltext = $htmltext . "<input type='hidden' name='typepdf' value='listedemande'>";
            $htmltext = $htmltext . "</form>";
            $htmltext = $htmltext . "<a href='javascript:document.userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . "_" . $tempannee . ".submit();'>Liste des demandes en PDF de l'année précédente</a>";
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
            $pdf->Image('../html/images/logo_papeterie.png', 10, 5, 60, 20);
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
                $pdf->Cell(60, 10, utf8_decode('Service : ' . $nomstructure));
                $pdf->Ln();
            }
            
            $pdf->Cell(60, 10, utf8_decode('Historique des demandes de  : ' . $this->civilite() . " " . $this->nom() . " " . $this->prenom()));
            $pdf->Ln(5);
            $pdf->Cell(60, 10, utf8_decode("Période du " . $this->fonctions->formatdate($datedebut) . " au " . $this->fonctions->formatdate($datefin)));
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 6, '', true);
            $pdf->Cell(60, 10, utf8_decode('Edité le ' . date("d/m/Y")));
            $pdf->Ln(10);
        }
        $pdf->SetFont('helvetica', '', 6, '', true);
        
        $headertext = "Tableau récapitulatif des demandes - Congés pris entre " . $this->fonctions->formatdate($datedebut) . " et ";
        if (date("Ymd") > $datefin)
            $headertext = $headertext . $this->fonctions->formatdate($datefin);
        else
            $headertext = $headertext . date("d/m/Y");
        
            $pdf->Cell(275, 5, utf8_decode($headertext), 1, 0, 'C');
        $pdf->Ln(5);
        
        if (count($demandeliste) == 0)
            $pdf->Cell(275, 5, utf8_decode("L'agent n'a aucun congé posé pour la période de référence en cours."), 1, 0, 'C');
        else {
            $pdf->Cell(60, 5, utf8_decode("Type de congé"), 1, 0, 'C');
            $pdf->Cell(25, 5, utf8_decode("Date de dépot"), 1, 0, 'C');
            $pdf->Cell(30, 5, utf8_decode("Date de début"), 1, 0, 'C');
            $pdf->Cell(30, 5, utf8_decode("Date de fin"), 1, 0, 'C');
            $pdf->Cell(20, 5, utf8_decode("Nbr de jours"), 1, 0, 'C');
            $pdf->Cell(30, 5, utf8_decode("Etat de la demande"), 1, 0, 'C');
            $pdf->Cell(80, 5, utf8_decode("Motif (obligatoire si le congé est annulé)"), 1, 0, 'C');
            $pdf->ln(5);
            foreach ($demandeliste as $key => $demande) {
                if ($demande->motifrefus() != "" or strcasecmp($demande->statut(), "r") != 0) {
                    $libelledemande = $demande->typelibelle();
                    if (strlen($libelledemande) > 40) {
                        $libelledemande = substr($demande->typelibelle(), 0, 40) . "...";
                    }
                    $pdf->Cell(60, 5, utf8_decode($libelledemande), 1, 0, 'C');
                    $pdf->Cell(25, 5, utf8_decode($demande->date_demande()), 1, 0, 'C');
                    $pdf->Cell(30, 5, utf8_decode($demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut())), 1, 0, 'C');
                    $pdf->Cell(30, 5, utf8_decode($demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin())), 1, 0, 'C');
                    $pdf->Cell(20, 5, utf8_decode($demande->nbrejrsdemande()), 1, 0, 'C');
                    $pdf->Cell(30, 5, utf8_decode($this->fonctions->demandestatutlibelle($demande->statut())), 1, 0, 'C');
                    $pdf->Cell(80, 5, utf8_decode($demande->motifrefus()), 1, 0, 'C');
                    $pdf->ln(5);
/*                    
                    if (strcasecmp($demande->statut(), "r") != 0) // Si la demande n'est pas annulée ou refusée
                    {
                        if (isset($synthesetab[$demande->typelibelle()]))
                            $synthesetab[$demande->typelibelle()] = $synthesetab[$demande->typelibelle()] + $demande->nbrejrsdemande();
                        else
                            $synthesetab[$demande->typelibelle()] = $demande->nbrejrsdemande();
                    }
*/
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
                   $synthesetab[$element->info()] = $synthesetab[$element->info()] + 0.5;
                else
                   $synthesetab[$element->info()] = 0.5;
            }
        }
        
        if (count($synthesetab) > 0) {
//        if (count($demandeliste) > 0) {
            $pdf->Ln(8);
            $headertext = "Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " et ";
            if (date("Ymd") > $datefin)
                $headertext = $headertext . $this->fonctions->formatdate($datefin);
            else
                $headertext = $headertext . date("d/m/Y");
            $pdf->Cell(100, 5, utf8_decode($headertext), 1, 0, 'C');
            $pdf->Ln(5);
            $pdf->Cell(80, 5, utf8_decode("Type de congé"), 1, 0, 'C');
            $pdf->Cell(20, 5, utf8_decode("Droit pris"), 1, 0, 'C');
            $pdf->ln(5);
            ksort($synthesetab);
            foreach ($synthesetab as $key => $nbrejrs) {
                $libelledemande = $key;
                if (strlen($key) > 40) {
                    $libelledemande = substr($key, 0, 40) . "...";
                }
                $pdf->Cell(80, 5, utf8_decode($libelledemande), 1, 0, 'C');
                $pdf->Cell(20, 5, utf8_decode($nbrejrs), 1, 0, 'C');
                $pdf->ln(5);
            }
        }
        
        $pdf->Ln(8);
        
        // ob_end_clean();
        if ($closeafter == TRUE) {
            ob_end_clean();
            $pdf->Output();
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
        $liste = null;
        
        /*
         * $affectationliste = $this->affectationliste($debut_interval, $fin_interval);
         * $affectation = new affectation($this->dbconnect);
         * $declarationTP = new declarationTP($this->dbconnect);
         * $demande = new demande($this->dbconnect);
         * if (!is_null($affectationliste))
         * {
         * foreach ($affectationliste as $key => $affectation)
         * {
         * //echo "<br><br>Affectation (". $affectation->affectationid() .") date debut = " . $affectation->datedebut() . " Date fin = " . $affectation->datefin() . "<br>";
         * unset($declarationTPliste);
         * $declarationTPliste = $affectation->declarationTPliste($debut_interval, $fin_interval);
         * if (!is_null($declarationTPliste))
         * {
         * foreach ($declarationTPliste as $key => $declarationTP)
         * {
         * //echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ") Debut = " . $declarationTP->datedebut() . " Fin = " . $declarationTP->datefin() . "<br>";
         * //echo "<br>Liste = "; print_r($declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin())); echo "<br>";
         * //$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin()));
         * $liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($debut_interval, $fin_interval));
         * }
         * }
         * }
         * }
         * //echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         * // On enlève les doublons des demandes !!!
         * $uniquedemandeliste = array();
         * if (is_array($liste))
         * {
         * foreach ($liste as $key => $demande)
         * {
         * $uniquedemandeliste[$demande->id()] = $demande;
         * }
         * $liste = $uniquedemandeliste;
         * unset($uniquedemandeliste);
         * }
         * //echo "#######demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         */
         
        //echo "<br>debut_interval = $debut_interval <br>fin_interval = $fin_interval<br>agentid = $agentid<br>mode = $mode<br>cleelement = $cleelement<br>";
        
        $liste = $this->demandesliste($debut_interval, $fin_interval);
        $debut_interval = $this->fonctions->formatdatedb($debut_interval);
        $fin_interval = $this->fonctions->formatdatedb($fin_interval);
        
        $htmltext = "";

        // $htmltext = "<br>";
        if (count($liste) == 0) {
            // $htmltext = $htmltext . " <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
            $htmltext = "";
        } else {
            $premieredemande = TRUE;
            foreach ($liste as $key => $demande) 
            {
                // echo "demandeslistehtmlpourgestion => debut du for " . $demande->id() . "<br>";
                // if (($demande->statut() == "a" and $mode == "agent") or ($demande->statut() == "v" and $mode == "resp"))
                if ((strcasecmp($demande->statut(), "a") == 0 and strcasecmp($mode, "agent") == 0) or (strcasecmp($demande->statut(), "v") == 0 and strcasecmp($mode, "resp") == 0)) {
                    if ($premieredemande) {
                        $htmltext = $htmltext . "<table id='tabledemande_" . $this->harpegeid() . "' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=7 align=center ><font color=#BF3021>Gestion des demandes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</font></td></tr>";
/*
                        $htmltext = $htmltext . "   <tr align=center><td class='cellulesimple'>Date de demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Nbre jours</td>";
                        if (strcasecmp($demande->statut(), "a") == 0 and strcasecmp($mode, "agent") == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Commentaire</td>";
                        $htmltext = $htmltext . "<td class='cellulesimple'>Annuler</td>";
                        if (strcasecmp($demande->statut(), "v") == 0 and strcasecmp($mode, "resp") == 0)
                            $htmltext = $htmltext . "<td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td>";
                        $htmltext = $htmltext . "</tr>";
*/                                
                        $htmltext = $htmltext . "   <tr align=center><th class='cellulesimple' style='cursor: pointer;'>Date de demande <font></font></th><th class='cellulesimple' style='cursor: pointer;'>Date de début <font></font></th><th class='cellulesimple' style='cursor: pointer;'>Date de fin <font></font></th><th class='cellulesimple' style='cursor: pointer;'>Type congé <font></font></th><th class='cellulesimple' style='cursor: pointer;'>Nbre jours <font></font></th>";
                        if (strcasecmp($demande->statut(), "a") == 0 and strcasecmp($mode, "agent") == 0)
                            $htmltext = $htmltext . "<th class='cellulesimple'>Commentaire</th>";
                        $htmltext = $htmltext . "<th class='cellulesimple'>Annuler</th>";
                        if (strcasecmp($demande->statut(), "v") == 0 and strcasecmp($mode, "resp") == 0)
                            $htmltext = $htmltext . "<th class='cellulesimple'>Motif (obligatoire si le congé est annulé)</th>";
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
                        $htmltext = $htmltext . "   <td class='cellulesimple'><time datetime='" . $this->fonctions->formatdatedb($demande->datedebut()) . "_" . (($demande->moment_debut()=='m')?'AM':'PM') . "'>" . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'><time datetime='" . $this->fonctions->formatdatedb($demande->datefin()) . "_" . (($demande->moment_fin()=='m')?'AM':'PM') . "'>" . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>";
                        if (strcasecmp($demande->statut(), "a") == 0 and strcasecmp($mode, "agent") == 0)
                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->commentaire() . "</td>";
                        $htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=cancel[" . $demande->id() . "] value='yes' /></td>";
                        if (strcasecmp($demande->statut(), "v") == 0 and strcasecmp($mode, "resp") == 0)
                            $htmltext = $htmltext . "   <td class='cellulesimple'><input type=text name=motif[" . $demande->id() . "] id=motif[" . $demande->id() . "] value='" . $demande->motifrefus() . "'  size=40></td>";
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
                    
// do the work...
document.getElementById('tabledemande_" . $this->harpegeid() . "').querySelectorAll('th').forEach(th => th.addEventListener('click', (() => {
    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    //alert (table.id);
    Array.from(tbody.querySelectorAll('tr'))
        .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
        .forEach(tr => tbody.appendChild(tr) );
    theader = table.querySelector('theader');

    //alert(Array.from(th.parentNode.querySelectorAll('th')));    

//    for (var thindex in document.getElementById('tabledemande_" . $this->harpegeid() . "').querySelectorAll('th'))
    for (var thindex = 0 ; thindex < document.getElementById('tabledemande_" . $this->harpegeid() . "').querySelectorAll('th').length; thindex++)
    {
        //alert (thindex);
        if (th.parentNode.children[thindex]!==null)
        {
            //alert (th.parentNode.children[thindex].innerHTML);
            if (th.parentNode.children[thindex].querySelector('font')!==null)
            {
                //alert (th.parentNode.children[thindex].querySelector('font').innerText);
                th.parentNode.children[thindex].querySelector('font').innerText = ' ';
                //alert (th.parentNode.children[thindex].querySelector('font').innerText);
            }
        }
    }

    if (this.asc)
    {
        //alert ('plouf');
        th.querySelector('font').innerHTML = '&darr;'; // flêhe qui descend
    }
    else
    {
        //alert ('ploc');
        th.querySelector('font').innerHTML = '&uarr;'; // flêche qui monte
    }
        
})));

document.getElementById('tabledemande_" . $this->harpegeid() . "').querySelectorAll('th')[1].click(); // On simule le clic sur la 2e colonne pour faire afficher la flêche et initialiser le asc


</script>";
            }
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
     *            optional the structure's responsable identifier (harpege ident)
     * @param string $structureid
     *            optional deprecated parameter => not used in code
     * @param string $cleelement
     *            optional deprecated parameter => not used in code
     * @return string the html text of the array
     */
    function demandeslistehtmlpourvalidation($debut_interval, $fin_interval, $agentid = null, $structureid = null, $cleelement = null)
    {
        $liste = null;
        /*
         * $affectationliste = $this->affectationliste($debut_interval, $fin_interval);
         * $affectation = new affectation($this->dbconnect);
         * $declarationTP = new declarationTP($this->dbconnect);
         * $demande = new demande($this->dbconnect);
         * if (!is_null($affectationliste))
         * {
         * foreach ($affectationliste as $key => $affectation)
         * {
         * //echo "<br><br>Affectation (". $affectation->affectationid() .") date debut = " . $affectation->datedebut() . " Date fin = " . $affectation->datefin() . "<br>";
         * unset($declarationTPliste);
         * $declarationTPliste = $affectation->declarationTPliste($debut_interval, $fin_interval);
         * if (!is_null($declarationTPliste))
         * {
         * foreach ($declarationTPliste as $key => $declarationTP)
         * {
         * //echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ") Debut = " . $declarationTP->datedebut() . " Fin = " . $declarationTP->datefin() . "<br>";
         * //echo "<br>Liste = "; print_r($declarationTP->demandesliste($debut_interval, $fin_interval)); echo "<br>";
         * //$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin()));
         * $liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($debut_interval, $fin_interval));
         * }
         * }
         * }
         * }
         * //echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         * // On enlève les doublons des demandes !!!
         * $uniquedemandeliste = array();
         * if (is_array($liste))
         * {
         * foreach ($liste as $key => $demande)
         * {
         * $uniquedemandeliste[$demande->id()] = $demande;
         * }
         * $liste = $uniquedemandeliste;
         * unset($uniquedemandeliste);
         * }
         * //echo "#######demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
         */
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
        
        $htmltext = "";
        // $htmltext = "<br>";
        if (count($liste) == 0) {
            // $htmltext = $htmltext . " <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
        } else {
            $premieredemande = TRUE;
            foreach ($liste as $key => $demande) {
                if (strcasecmp($demande->statut(), "a") == 0) {
                    $todisplay = true;
                    // On n'affiche pas les demandes du responsable !!!!
                    if ($agentid == $this->harpegeid) {
                        $todisplay = false;
                    }
                    // echo "todisplay = $todisplay <br>";
                    if ($todisplay) {
                        if ($premieredemande) {
                            $htmltext = $htmltext . "<table class='tableausimple' width=100%>";
                            $htmltext = $htmltext . "   <tr><td class=titresimple colspan=7 align=center ><font color=#BF3021>Tableau des demandes à valider pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</font></td></tr>";
                            $htmltext = $htmltext . "   <tr align=center><td class='cellulesimple'>Date de demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Nbre jours</td><td class='cellulesimple'>Etat de la demande</td><td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td></tr>";
                            $premieredemande = FALSE;
                        }
                        
                        $htmltext = $htmltext . "<tr align=center >";
                        // $htmltext = $htmltext . " <td>" . $this->nom() . " " . $this->prenom() . "</td>";
                        
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->date_demande() . " " . $demande->heure_demande() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datedebut()) . " " . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datefin()) . " " . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
                        if ($demande->type() == 'enmal') {
                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "  (" . $this->nbjrsenfantmaladeutilise($debut_interval, $fin_interval) . "/" . $this->nbjrsenfantmalade() . ")</td>";
                        }
                        elseif ($demande->type() == 'spec')
                        {
                            $htmltext = $htmltext . "   <td class='cellulesimple'>";
                            if (strlen($demande->commentaire()) != 0)
                            {
                                $htmltext = $htmltext . " <span data-tip=" . chr(34) . $demande->commentaire() . chr(34) . ">";
                            }
                            $htmltext = $htmltext. $demande->typelibelle();
                            if (strlen($demande->commentaire()) != 0)
                            {
                                $htmltext = $htmltext . "</span>";
                            }
                            $htmltext = $htmltext . "</td>";
                        } else {
                            $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
                        }
                        $htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'>";
                        $htmltext = $htmltext . "      <select name='statut[" . $demande->id() . "]'>";
                        $htmltext = $htmltext . "         <option ";
                        if (strcasecmp($demande->statut(), "v") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . " value='v'>" . $this->fonctions->demandestatutlibelle("v") . "</option>";
                        $htmltext = $htmltext . "         <option ";
                        if (strcasecmp($demande->statut(), "r") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . " value='r'>" . $this->fonctions->demandestatutlibelle("r") . "</option>";
                        $htmltext = $htmltext . "         <option ";
                        if (strcasecmp($demande->statut(), "a") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . " value='a'>" . $this->fonctions->demandestatutlibelle("a") . "</option>";
                        $htmltext = $htmltext . "      <select>";
                        $htmltext = $htmltext . "</td>";
                        $htmltext = $htmltext . "   <td class='cellulesimple'><input type=text name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' value='" . $demande->motifrefus() . "' size=40 ></td>";
                        $htmltext = $htmltext . "</tr>";
                    }
                }
            }
            if (! $premieredemande)
                $htmltext = $htmltext . "</table>";
            // $htmltext = $htmltext . "<br>";
        }
        return $htmltext;
    }

    /**
     *
     * @param
     * @return string the html text of the array
     */
    function affichecommentairecongehtml($showonlycomplement = false, $anneeref = null)
    {
        //echo "<br>anneeref = XXX" . $anneeref  . "XXX<br>";
        if (is_null($anneeref))
        {
            $sql = "SELECT HARPEGEID,LIBELLE,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,TYPEABSENCE.TYPEABSENCEID 
    FROM COMMENTAIRECONGE,TYPEABSENCE 
    WHERE HARPEGEID='" . $this->harpegeid . "' AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($this->fonctions->anneeref(), 2, 2) . "' 
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($this->fonctions->anneeref() - 1), 2, 2) . "' 
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID='cet') 
                                               AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID";
        }
        else
        {
            $sql = "SELECT HARPEGEID,LIBELLE,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE,TYPEABSENCE.TYPEABSENCEID
    FROM COMMENTAIRECONGE,TYPEABSENCE
    WHERE HARPEGEID='" . $this->harpegeid . "' AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($anneeref, 2, 2) . "'
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($anneeref + 1), 2, 2) . "'
                                                 OR COMMENTAIRECONGE.TYPEABSENCEID='cet')
                                               AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID";
            
        }
        //echo "SQL = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            echo "Agent->affichecommentairecongehtml : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Agent->affichecommentairecongehtml : " . $erreur);
        }
        $htmltext = "";
        $premiercomment = TRUE;
        $htmltext = $htmltext . "<center><table class='tableausimple'>";
        while ($result = mysqli_fetch_row($query)) {
            if (($showonlycomplement and (strcasecmp(substr($result[5], 0, 3), "sup")) == 0) or ($showonlycomplement == false)) {
                if ($premiercomment) {
                    $htmltext = $htmltext . "<tr><td class='titresimple' colspan=4 align=center>Commentaires sur les modifications de congés</td></tr>";
                    $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Date modification</td><td class='cellulesimple'>Jours</td><td class='cellulesimple'>Commentaire</td></tr>";
                    $premiercomment = FALSE;
                }
                
                $htmltext = $htmltext . "<tr align=center>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $result[1] . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $this->fonctions->formatdate($result[2]) . "</td>";
                if ($result[4] > 0)
                    $htmltext = $htmltext . "<td class='cellulesimple'>+" . (float) ($result[4]) . "</td>";
                else
                    $htmltext = $htmltext . "<td class='cellulesimple'>" . (float) ($result[4]) . "</td>";
                $htmltext = $htmltext . "<td class='cellulesimple'>" . $result[3] . "</td>";
                $htmltext = $htmltext . "</tr>";
            }
        }
        $htmltext = $htmltext . "</table></center>";
        $htmltext = $htmltext . "<br>";
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
    function ajoutecommentaireconge($typeconge = null, $nbrejours = null, $commentaire = null)
    {
        $date = date("d/m/Y");
        $sql = "INSERT INTO COMMENTAIRECONGE(HARPEGEID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE)
		        VALUES ('" . $this->harpegeid . "','" . $typeconge . "','" . $this->fonctions->formatdatedb($date) . "','" . str_replace("'", "''", $commentaire) . "','" . $nbrejours . "')";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $message = "$erreur";
            error_log(basename(__FILE__) . " " . $erreur);
        }
    }

    function aunedemandecongesbonifies($anneeref)
    {
        $demande = null;
        $debutperiode = $this->fonctions->formatdatedb($anneeref . $this->fonctions->debutperiode());
        $finperiode = $this->fonctions->formatdatedb(($anneeref + 1) . $this->fonctions->finperiode());
        // $sql = "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE WHERE HARPEGEID='" . $this->harpegeid ."' AND HARPTYPE='CONGE_BONIFIE' AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $sql = "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE WHERE HARPEGEID='" . $this->harpegeid . "' AND (HARPTYPE='CONGE_BONIFIE' OR HARPTYPE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
            error_log(basename(__FILE__) . " " . $erreur_requete);
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
        $sql = "SELECT HARPEGEID, NUMLIGNE, TYPESTATUT,DATEDEBUT,DATEFIN FROM W_STATUT WHERE HARPEGEID = '" . $this->harpegeid . "' ORDER BY DATEDEBUT";
        $querystatut = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
            error_log(basename(__FILE__) . " " . $erreur_requete);
        if (mysqli_num_rows($querystatut) == 0) // Il n'y a pas de STATUT pour cet agent => On sort
        {
            echo "<br>Pas de statut pour cet agent " . $this->harpegeid . "!!!<br>";
            return "<br>Pas de statut pour cet agent " . $this->harpegeid . "!!!<br>";
        }
        $sql = "SELECT HARPEGEID, NUMLIGNE, QUOTITE, DATEDEBUT, DATEFIN FROM W_MODALITE WHERE HARPEGEID = '" . $this->harpegeid . "' ORDER BY DATEDEBUT";
        $queryquotite = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
            error_log(basename(__FILE__) . " " . $erreur_requete);
        if (mysqli_num_rows($queryquotite) == 0) // Il n'y a pas de QUOTITE pour cet agent => On sort
        {
            echo "<br>Pas de quotité pour cet agent " . $this->harpegeid . "!!!<br>";
            return "<br>Pas de quotité pour cet agent " . $this->harpegeid . "!!!<br>";
        }
        
        $curentstatut = mysqli_fetch_row($querystatut);
        $curentquotite = mysqli_fetch_row($queryquotite);
        
        $strresultat = '';
        $tabresult = array();
        
        while ($curentstatut and $curentquotite) {            
            $statutharpegeid = $curentstatut[0];
            $statutnumligne = $curentstatut[1];
            $statutid = trim($curentstatut[2]);
            $statutdatedebut = $curentstatut[3];
            $statutdatefin = $curentstatut[4];
            
            $quotiteharpegeid = $curentquotite[0];
            $quotitenumligne = $curentquotite[1];
            $quotitevalue = trim($curentquotite[2]);
            $quotitedatedebut = $curentquotite[3];
            $quotitedatefin = $curentquotite[4];
            
            $datedebut = '1899-12-31';
            $datefin = '9999-12-31';
            
            if ($statutdatedebut > $datedebut)
                $datedebut = $statutdatedebut;
            if ($quotitedatedebut > $datedebut)
                $datedebut = $quotitedatedebut;
            
            if ($statutdatefin < $datefin)
                $datefin = $statutdatefin;
            if ($quotitedatefin < $datefin)
                $datefin = $quotitedatefin;
            
            if ($datefin < $datedebut) {
                echo "Detection de datefin ($datefin) < datedebut ($datedebut) => On ignore pour agent " . $this->harpegeid . "!!!<br>\n";
            } else {
                $strresultat = $this->harpegeid . '_' . $statutnumligne . '_' . $quotitenumligne;
                $strresultat = $strresultat . ';' . $this->harpegeid;
                if (substr($statutid, 0, 5) != 'CONTR')
                    $statutid = '';
                $strresultat = $strresultat . ';' . $statutid;
                $strresultat = $strresultat . ';' . $datedebut;
                $strresultat = $strresultat . ';' . $datefin;
                $strresultat = $strresultat . ';' . date("Ymd");
                $strresultat = $strresultat . ';'; // structureid
                $strresultat = $strresultat . ';' . $quotitevalue;
                $strresultat = $strresultat . ';' . '100';
                $strresultat = $strresultat . ';';
                
                // echo $strresultat . '<br>';
                $tabresult[] = $strresultat;
            }
            if ($datefin == $statutdatefin)
                $curentstatut = mysqli_fetch_row($querystatut);
            if ($datefin == $quotitedatefin)
                $curentquotite = mysqli_fetch_row($queryquotite);
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
        $sql = "SELECT DISTINCT DEMANDE.DEMANDEID ,DEMANDE.DATEDEBUT,DEMANDE.DATESTATUT
				FROM DEMANDE,DEMANDEDECLARATIONTP,DECLARATIONTP,AFFECTATION,AGENT 
				WHERE AFFECTATION.AFFECTATIONID = DECLARATIONTP.AFFECTATIONID 
				  AND DECLARATIONTP.DECLARATIONID = DEMANDEDECLARATIONTP.DECLARATIONID 
				  AND DEMANDEDECLARATIONTP.DEMANDEID = DEMANDE.DEMANDEID 
				  AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID
				  AND AGENT.HARPEGEID = '" . $this->harpegeid() . "' 
				  AND DEMANDE.TYPEABSENCEID = 'cet' 
				  AND (DEMANDE.DATEDEBUT >= '" . $this->fonctions->formatdatedb($datedebut) . "'
				    OR DEMANDE.DATESTATUT >= '" . $this->fonctions->formatdatedb($datedebut) . "' )
			    ORDER BY DEMANDE.DATEDEBUT,DEMANDE.DATESTATUT";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
            error_log(basename(__FILE__) . " " . $erreur_requete);
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
            $complement->load($this->harpegeid(), 'DEM_CET_' . $demandeid);
            
            if ($demande->statut() == 'v' and $complement->harpegeid() == '') // Si la demande est validée mais que le complément n'existe pas => On doit le controler
            {
                $demandeliste[] = $demande;
            }
            if ($demande->statut() == 'R' and $complement->valeur() == 'v') // Si la demande est annulée mais que le complément est toujours valide => On doit le contrôler
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
    		$filtre = "(&(".$LDAP_CODE_AGENT_ATTR."=".$this->harpegeid().")(".$LDAP_MEMBER_ATTR."=".$LDAP_GROUP_NAME."))";
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
    			$errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->harpegeid() . ") ne fait parti d'aucun groupe LDAP....";
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
    	$LDAP_POSTAL_ADDRESS_ATTR = 'postaladdress';
    	$retour = array();
    	// Si les constantes sont définies et non vides on regarde si l'utilisateur est dans le groupe
    	if ((trim("$LDAP_MEMBER_ATTR") != "" and trim("$LDAP_GROUP_NAME") != "")) {
    		$con_ldap = ldap_connect($LDAP_SERVER);
    		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    		$r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
    		$filtre = "(".$LDAP_CODE_AGENT_ATTR."=".$this->harpegeid().")";
    		$dn = $LDAP_SEARCH_BASE;
    		$restriction = array(
    				"$LDAP_POSTAL_ADDRESS_ATTR"
    		);
    		$sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
    		$info = ldap_get_entries($con_ldap, $sr); 
    		
    		if (isset($info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]))
    		{
    			$retour['postaladdress'] = str_replace('$', ' ',$info[0]["$LDAP_POSTAL_ADDRESS_ATTR"][0]);
    		}
    		else
    		{
    			$errlog = "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->harpegeid() . ") n'a pas de postalAddress....";
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
	    	$htmltext = $htmltext . "<tr><td class='titresimple'>Identifiant</td><td class='titresimple'>Date création</td><td class='titresimple'>type congé</td><td class='titresimple'>Nombre de jours</td><td class='titresimple'>Statut</td><td class='titresimple'>Date Statut</td><td class='titresimple'>Motif</td><td class='titresimple'>Consulter</td>";
	    	$htmltext = $htmltext . "</tr>";
	    	foreach ($listid as $id)
	    	{
	    		$alimcet->load($id);
	    		$htmltext = $htmltext . "<tr><td class='cellulesimple'>" . $id . "</td><td class='cellulesimple'>" . $this->fonctions->formatdate(substr($alimcet->datecreation(), 0, 10)).' '.substr($alimcet->datecreation(), 10) . "</td><td class='cellulesimple'>" . $alimcet->typeconges() . "</td><td class='cellulesimple'>" . $alimcet->valeur_f() . "</td><td class='cellulesimple'>" . $alimcet->statut() . "</td><td class='cellulesimple'>" . $this->fonctions->formatdate($alimcet->datestatut()) . "</td><td class='cellulesimple'>" . $alimcet->motif() . "</td><td class='cellulesimple'><a href='" . $alimcet->esignatureurl() . "' target='_blank'>".(($alimcet->statut() == $alimcet::STATUT_ABANDONNE) ? '':$alimcet->esignatureurl())."</a></td></tr>";
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
    
   /* function afficheAlimCetHtmlPourSuppr($anneeref = '', $statuts = array(), $mode = 'agent', $userid = '')
    {
    	$alimcet = new alimentationCET($this->dbconnect);
    	$listid = $this->getDemandesAlim($anneeref, $statuts);
    	$htmltext = '';
    	if ($userid == '')
    	{
    		$userid = $this->harpegeid();
    	}
    	if (sizeof($listid) != 0)
    	{    		
    		echo "<br>Annulation d'une demande d'alimentation.<br>";
    		echo "<form name='form_esignature_annule'  method='post' >";
    		echo "<input type='hidden' name='userid' value='" . $userid . "'>";
    		echo "<input type='hidden' name='agentid' value='" . $this->harpegeid() . "'>";
    		echo "<select name='esignatureid_annule' id='esignatureid_annule'>";
    		foreach ($listid as $id)
    		{
    			$alimcet->load($id);
    			echo "<option value='" . $id  . "'>" . $id ." => ".$alimcet->statut()."</option>";
    		}
    		
    		echo "</select>";
    		echo "<br><br>";
    		echo "<input type='hidden' name='mode' value='" . $mode . "'>";
    		echo "<input type='submit' name='annuler_demande' id='annuler_demande' value='Annuler la demande'>";
    		echo "</form>";
    		echo "<br>";
    	}
    	return $htmltext;
    }
    
    function supprimeDemandeAlimentation()
    {
    	if (isset($_POST['annuler_demande']))
    	{
    		$esignatureid_annule = $_POST['esignatureid_annule'];
    		$alimentationCET = new alimentationCET($this->dbconnect);
    		$alimentationCET->load($esignatureid_annule);
    		// récupérer statut si validée réalimenter le reliquat, déduire du CET et alerter la DRH
    		$statut_actuel = $alimentationCET->statut();
    		if ($statut_actuel == alimentationCET::STATUT_VALIDE)
    		{
    			// réattribution des reliquats
    			$solde = new solde($this->dbconnect);
    			//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le type de congés est " . $alimentationCET->typeconges()));
    			$solde->load($this->harpegeid(), $alimentationCET->typeconges());
    			//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est avant : " . $solde->droitpris() . " et valeur_f = " . $alimentationCET->valeur_f()));
    			$new_solde = $solde->droitpris()-$alimentationCET->valeur_f();
    			$solde->droitpris($new_solde);
    			//error_log(basename(__FILE__) . $fonctions->stripAccents(" Le solde droitpris est après : " . $solde->droitpris()));
    			error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le solde " . $solde->typelibelle() . " sera après enregistrement de " . ($solde->droitaquis() - $solde->droitpris())));
    			$solde->store();
    			
    			// Ajouter dans la table des commentaires la trace de l'opération
    			$this->ajoutecommentaireconge($alimentationCET->typeconges(),($alimentationCET->valeur_f()),"Annulation de demande d'alimentation CET");
    			
    			// déduction du CET
    			
    			$cet = new cet($this->dbconnect);
    			$erreur = $cet->load($this->harpegeid);
    			if ($erreur == "") {
    				$cet->cumultotal($cet->cumultotal() - $alimentationCET->valeur_f());
    				$cumulannuel = $cet->cumulannuel($this->fonctions->anneeref());
    				$cumulannuel = $cumulannuel - $alimentationCET->valeur_f();
    				$cet->cumulannuel($this->fonctions->anneeref(),$cumulannuel);
    				$cet->store();
    			}
    			
    			// alerter la DRH
    			
    			$arrayagentrh = $this->fonctions->listeprofilrh("1"); // Profil = 1 ==> GESTIONNAIRE RH DE CET
    			foreach ($arrayagentrh as $gestrh) {
    				$this->sendmail($gestrh, "Annulation d'une demande d'alimentation de CET validée", "L'agent " .$this->nom()." ".$this->prenom()." a demandé l'annulation de sa demande d'alimentation n°". $esignatureid_annule . ".\n");
    			}
    		}
    		
    		// purger esignature
    		
    		$eSignature_url = "https://esignature-test.univ-paris1.fr";
    		$url = $eSignature_url.'/ws/signrequests/'.$esignatureid_annule;
    		$params = array('id' => $esignatureid_annule);
    		$walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
    			is_array( $item )
    			? array_walk( $item, $walk, $key )
    			: $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
    			
    		};
    		array_walk( $params, $walk );
    		$json = implode( '&', $output );
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		$result = curl_exec($ch);
    		$result = json_decode($result);
    		$error = curl_error ($ch);
    		curl_close($ch);
    		$errlog = '';
    		if ($error != "")
    		{
    			$errlog = "Erreur Curl = " . $error . "<br><br>";
    		}
    		
    		// Abandon dans G2T
    		$alimentationCET->statut($alimentationCET::STATUT_ABANDONNE);
    		$alimentationCET->motif("Annulation à la demande de l'agent");
    		$alimentationCET->store();
    		$errlog .= "L'utilisateur " . $this->identitecomplete() . " (identifiant = " . $this->harpegeid() . ") a supprimé la demande d'alimentation du CET (esignatureid = ".$esignatureid_annule.")";
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    }*/

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
     * @param string $anneeref
     * @param array $listStatuts
     * @return array of esignatureid 
     */
    function getDemandesAlim($typeconge = '', $listStatuts = array())
    {
    	$listdemandes = array();
    	$statuts = '';
    	$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE HARPEGEID = '".$this->harpegeid()."' ";
    	if ($typeconge != '') 
    	{
    		$sql .= " AND TYPECONGES = '$typeconge' " ;
    	}
    	if (sizeof($listStatuts) != 0)
    	{
    		$statuts = $this->fonctions->formatlistedb($listStatuts);
    		$sql .=  " AND STATUT IN $statuts";
    	}
    	$query = mysqli_query($this->dbconnect, $sql);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
    	{
    		$errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande d'alimentation pour l'agent " . $this->identitecomplete() . "<br>";;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	else 
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/alimentationWS.php";
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
    	if (sizeof($liste_affectations) >= 1)
    	{
    		$debutaffprec = null;
    		$finaffprec = null;
    		foreach($liste_affectations as $affectation)
    		{
    			$debutaffectation = $this->fonctions->formatdatedb($affectation->datedebut());
//    			echo "debutaffectation = $debutaffectation <br>";
    			if (is_null($debutaffprec) && $debutaffectation > $datedebut)
    			{
    				$errlog .= "Pas d'affectation entre  le ".$this->fonctions->formatdate($datedebut)." et le ".$this->fonctions->formatdate($debutaffectation).". En cas d'erreur, contactez la DRH. ";
    				error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    				return TRUE;
    			}
    			$debutaffprec = $debutaffectation;
    			$finaffectation = $this->fonctions->formatdatedb($affectation->datefin());
//    			echo "debutaffprec = $debutaffprec <br>";
//    			echo "finaffectation = $finaffectation <br>";
    			if (!is_null($finaffprec))
    			{
    				// nombre de jours entre la fin de la dernière affectation et le début de la courante
//    				echo "Avant dateconsecutive => $finaffprec   $debutaffectation <br>";
    				if (!$this->fonctions->datesconsecutives($finaffprec, $debutaffectation))
    				{
    					$errlog .= "Pas d'affectation entre le ".$this->fonctions->formatdate($finaffprec)." et le ".$this->fonctions->formatdate($debutaffectation).". En cas d'erreur, contactez la DRH. ";
    					error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    					return TRUE;
    				}
    			}
    			$finaffprec = $finaffectation;
//    			echo "finaffprec = $finaffprec";
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
    	$errlog = "type congé $type_conge. date planning debut : ".$this->fonctions->formatdate($datedeb)." fin : ".$this->fonctions->formatdate($datefin);
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
    				if (($demande->type() == $type_conge) and (strcasecmp($demande->statut(), 'r')!=0) )
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
    
    function getResponsableForCET()
    {
    	$pasresptrouve = false;
    	$structid = $this->structureid();
    	$struct = new structure($this->dbconnect);
    	$struct->load($structid);
    	$resp = $struct->responsable();
    	if (($resp->mail() . "") <> "")
    	{
    		if ($resp->harpegeid() == $this->harpegeid())
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
        $sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE HARPEGEID = '" .  $this->harpegeid() . "'";

        if ($anneeref != '')
            $sql .= " AND ANNEEREF = '$anneeref' " ;
        if (sizeof($listStatuts) != 0)
        {
            $statuts = $this->fonctions->formatlistedb($listStatuts);
            $sql .=  "AND STATUT IN $statuts";
        }
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id eSignature (droit d'option) : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Aucune demande de droit d'option pour l'agent " . $this->identitecomplete() . "<br>";
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
    	$sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE HARPEGEID = '".$this->harpegeid()."' ";
    	if ($typeconge != '')
    	{
    		$sql .= " AND TYPECONGES = '$typeconge' " ;
    	}
    	$query = mysqli_query($this->dbconnect, $sql);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
    	{
    		$errlog = "Problème SQL dans le chargement des id eSignature : " . $erreur;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande d'alimentation pour l'agent " . $this->identitecomplete() . "<br>";;
    		error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
    	}
    	else
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/alimentationWS.php";
    		while ($result = mysqli_fetch_row($query))
    		{
    			$this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
    		}
    	}
    	
    	// Synchronisation des demandes d'option
    	$sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE HARPEGEID = '" .  $this->harpegeid() . "'";
    	
    	if ($anneeref != '')
    	{
    		$sql .= " AND ANNEEREF = '$anneeref' " ;
    	}
    	$query = mysqli_query($this->dbconnect, $sql);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "")
   		{
   			$errlog = "Problème SQL dans le chargement des id eSignature (droit d'option) : " . $erreur;
   			echo $errlog;
   		}
    	elseif (mysqli_num_rows($query) == 0)
    	{
    		//echo "<br>load => pas de ligne dans la base de données<br>";
    		$errlog = "Aucune demande de droit d'option pour l'agent " . $this->identitecomplete() . "<br>";
    		error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
    		//echo $errlog;
    	}
    	else
    	{
    		$full_g2t_ws_url = $this->fonctions->get_g2t_ws_url() . "/optionWS.php";
    		while ($result = mysqli_fetch_row($query))
    		{
    			$this->fonctions->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
    		}
    	}
    }
    
    /**
     *
     * @param string $anneeref
     * @return number of days
     */
    function calculsoldeannuel($anneeref = null, $maj_solde = true, $loginfo = false, $displayinfo = false)
    {

        
        if ($loginfo == true) {
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" ###############################################################"));
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On est sur l'agent : " . $this->identitecomplete() . " (id = " . $this->harpegeid . ")"));
        }
        if ($displayinfo == true)
        {
            echo " ###############################################################\n";
            echo " On est sur l'agent : " . $this->identitecomplete() . " (id = " . $this->harpegeid . ")\n";
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
        $agentid = $this->harpegeid;
        
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
        $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid' AND OBSOLETE='N' AND DATEDEBUT < " . ($anneeref + 1) . $this->fonctions->finperiode() . " ORDER BY DATEDEBUT";
        $query_aff = mysqli_query($this->dbconnect, $sql);
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
        
        // On vérifie si une demande de congé bonifié débute dans la période
        $debutperiode = $anneeref . $this->fonctions->debutperiode();
        $finperiode = ($anneeref + 1) . $this->fonctions->finperiode();
        $sql = "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE WHERE HARPEGEID='$agentid' AND (HARPTYPE='CONGE_BONIFIE' OR HARPTYPE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
           echo "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE => $erreur_requete <br>";
        }
        if (mysqli_num_rows($query) != 0) // Il existe un congé bonifié pour la période => On le solde des congés à 0
        {
            $resultcongbonif = mysqli_fetch_row($query);
            $solde_agent = 0;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" L'agent $agentid ($agentinfo) a une demande de congés bonifiés (du " . $resultcongbonif[1] . " au " . $resultcongbonif[2] . ") => Solde à 0 "));
        }
        
        if ($maj_solde == true)
        {
            if ($loginfo == true) {
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On met à jour le solde de l'agent dans la base de données"));
            }
            $typeabsenceid = "ann" . substr($anneeref, 2, 2);
            $sql = "SELECT HARPEGEID,TYPEABSENCEID FROM SOLDE WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur_requete = mysqli_error($this->dbconnect);
            if ($erreur_requete != "")
            {
                echo "SELECT HARPEGEID,TYPEABSENCEID FROM CONGE => $erreur_requete <br>";
            }
            if (mysqli_num_rows($query) != 0) // le type annXX existe déja => On le met à jour
            {
                $sql = "UPDATE SOLDE SET DROITAQUIS='$solde_agent' WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
            }
            else
            {
                $sql = "INSERT INTO SOLDE(HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES('" . $agentid . "','" . $typeabsenceid . "','$solde_agent','0')";
            }
            mysqli_query($this->dbconnect, $sql);
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
                WHERE HARPEGEID = '" .  $this->harpegeid() . "' 
                  AND ((DATEDEBUT <= '" . $datedebut . "' AND DATEFIN >='" . $datedebut . "')
                    OR (DATEFIN >= '" . $datefin . "' AND DATEDEBUT <='" . $datefin . "')
                    OR (DATEDEBUT >= '" . $datedebut . "' AND DATEFIN <= '" . $datefin . "'))
                ORDER BY DATEDEBUT,DATEFIN";
        
        //echo "<br>SQL = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id teletravail : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Aucune demande de télétravail pour l'agent " . $this->identitecomplete() . "<br>";
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
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
    
    function estenteletravail($date, $moment = null)
    {
        $date = $this->fonctions->formatdatedb($date);
        $liste = $this->teletravailliste($date, $date);
        $reponse = false;
        foreach ($liste as $teletravailid)
        {
            $teletravail = new teletravail($this->dbconnect);
            $teletravail->load($teletravailid);
            if ($teletravail->statut() == teletravail::STATUT_ACTIVE)
            {
                if ($teletravail->estteletravaille($date,$moment))
                {
                    $reponse = true;
                }
            }
        }
        return $reponse;
    }
    
    function nbjoursteletravail($datedebut, $datefin)
    {
        $planning = new planning($this->dbconnect);
        return $planning->nbjoursteletravail($this->harpegeid, $datedebut, $datefin);
    }
    
}

?> 