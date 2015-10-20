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
class agent {

   private $harpegeid = null;
   private $nom = null;
   private $prenom = null;
   private $dbconnect = null;
   private $civilite = null;
   private $adressemail = null;
   private $typepopulation = null;
   
   private $fonctions = null;
   
   /**
         * @param object $db the mysql connection
         * @return 
   */
   function __construct($db)
   {
      $this->dbconnect = $db;
      if (is_null($this->dbconnect))
      {
		$errlog = "Agent->construct : La connexion à la base de donnée est NULL !!!";
		echo $errlog."<br/>";
		error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
      }
      $this->fonctions = new fonctions($db);
   }

   /**
         * @param string $harpegeid the harpege identifier of the current agent
         * @return boolean TRUE if all correct, FALSE otherwise
   */
   function load($harpegeid)
   {
   	//echo "Debut Load";
      if (is_null($this->harpegeid))
      {

      	$sql = sprintf("SELECT HARPEGEID,CIVILITE,NOM,PRENOM,ADRESSEMAIL,TYPEPOPULATION FROM AGENT WHERE HARPEGEID='%s'",$this->fonctions->my_real_escape_utf8($harpegeid));
      	//echo "sql = " . $sql . "<br>";
         $query=mysql_query ($sql, $this->dbconnect);
         $erreur=mysql_error();
         if ($erreur != "")
         {
         	$errlog = "Agent->Load (AGENT) : " . $erreur;
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
            return false;
         }
         if (mysql_num_rows($query) == 0)
         {
         	$errlog = "Agent->Load (AGENT) : Agent $harpegeid non trouvé";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
            return false;
         }
         $result = mysql_fetch_row($query);
         $this->harpegeid = "$result[0]";
         $this->civilite = "$result[1]";
         $this->nom = "$result[2]";
         $this->prenom = "$result[3]";
         $this->adressemail = "$result[4]";
         $this->typepopulation = "$result[5]";
         return true; 
      }
      //echo "Fin...";
   }

   /**
         * @param 
         * @return string the harpege identifier of the current agent
   */
   function harpegeid()
   {
      return $this->harpegeid;
   }

   /**
         * @param string $name optional the name of the current agent
         * @return string name of the current agent if $name parameter not set. No return otherwise
   */
   function nom($name = null)
   {
      if (is_null($name))
      {
         if (is_null($this->nom)) {
         	$errlog = "Agent->nom : Le nom de l'agent n'est pas défini !!!";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
         }
         else
            return $this->nom;
      }
      else
         $this->nom = $name;
   }

   /**
         * @param string $firstname optional the firstname of the current agent
         * @return string firstname of the current agent if $firstname parameter not set. No return otherwise
   */
      function prenom($firstname = null)
   {
      if (is_null($firstname))
      {
         if (is_null($this->prenom)) {
         	$errlog = "Agent->prenom : Le prénom de l'agent n'est pas défini !!!";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
         }
         else
            return $this->prenom;
      }
      else
         $this->prenom = $firstname;
   }

   /**
         * @param string $civilite optional the civility of the current agent
         * @return string civility of the current agent if $civilite parameter not set. No return otherwise
   */
   function civilite($civilite = null)
   {
      if (is_null($civilite))
      {
         if (is_null($this->civilite)) {
         	$errlog = "Agent->civilite : La civilité de l'agent n'est pas définie !!!";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
         }
         else
            return $this->civilite;
      }
      else
         $this->civilite = $civilite;
   }
   
   /**
         * @param 
         * @return string the full name of the current agent (civility + firstname + name)
   */
   function identitecomplete()
   {
   	return $this->civilite() . " " . $this->prenom() . " " . $this->nom();
   }

   /**
         * @param string $mail optional the mail of the current agent
         * @return string mail of the current agent if $mail parameter not set. No return otherwise
   */
   function mail($mail = null)
   {
      if (is_null($mail))
      {
         if (is_null($this->adressemail)) {
         	$errlog = "Agent->mail : Le mail de l'agent n'est pas défini !!!";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
         }
         else
            return $this->adressemail;
      }
      else
         $this->adressemail = $mail;
   }

   /**
         * @param string $type optional the type of the current agent
         * @return string type of the current agent if $type parameter not set. No return otherwise
   */
   function typepopulation($type = null)
   {
      if (is_null($type))
      {
         if (is_null($this->typepopulation)) {
         	$errlog = "Agent->typepopulation : Le type de population de l'agent n'est pas défini !!!";
         	echo $errlog."<br/>";
         	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
         }
         else
            return $this->typepopulation;
      }
      else
         $this->codestructure = $type;
   }

   /**
         * @param 
         * @return boolean true if the current agent is responsable of a strucuture. false otherwise.
   */
   function estresponsable()
   {
      $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID='%s' AND DATECLOTURE>=DATE(NOW())",$this->fonctions->my_real_escape_utf8($this->harpegeid));
      //echo "sql = " . $sql . "<br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
		{
			$errlog = "Agent->estresponsable (AGENT) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			return FALSE;
      }
      return (mysql_num_rows($query) != 0);
	}

   /**
         * @param 
         * @return boolean true if the current agent is a manager of a strucuture. false otherwise.
   */
	function estgestionnaire()
   {
      $sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID='%s' AND DATECLOTURE>=DATE(NOW())",$this->fonctions->my_real_escape_utf8($this->harpegeid));
      //echo "sql = " . $sql . "<br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
		{
			$errlog = "Agent->estgestionnaire (AGENT) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			return FALSE;
      }
      return (mysql_num_rows($query) != 0);
	}

   /**
         * @param 
         * @return boolean true if the current agent is an administrator of the application. false otherwise.
   */
   function estadministrateur()
   {
      $sql = sprintf("SELECT VALEUR,STATUT,DATEDEBUT,DATEFIN FROM COMPLEMENT WHERE HARPEGEID='%s' AND COMPLEMENTID='ESTADMIN'",$this->fonctions->my_real_escape_utf8($this->harpegeid));
      //echo "sql = " . $sql . "<br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
		{
			$errlog = "Agent->estadministrateur (AGENT) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			return FALSE;
      }
      if (mysql_num_rows($query) == 0)
			return FALSE;
		$result = mysql_fetch_row($query);
		return (strcasecmp($result[0], "O")==0);
	}

   /**
         * @param string $nbrejrs optional Nombre de jours 'enfant malade' pour l'agent courant
         * @return string Nombre de jours 'enfant malade' si $nbrejrs est null. Pas de retour sinon
   */
   function nbjrsenfantmalade($nbrejrs = null)
   {
      $complement = new complement($this->dbconnect);
      if (is_null($nbrejrs))
      {
			$complement->load($this->harpegeid, 'ENFANTMALADE');
            return intval($complement->valeur());
      }
      elseif ((strcasecmp(intval($nbrejrs),$nbrejrs)==0) and (intval($nbrejrs)>=0))  // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
      {
      	    $complement->complementid('ENFANTMALADE');
			$complement->harpegeid($this->harpegeid);
			$complement->valeur(intval($enfantmaladevalue));
			$complement->store();
      }
      else {
      	$errlog = "Agent->nbjrsenfantmalade (AGENT) : Le nombre de jours 'enfant malade doit être un nombre positif ou nul'";
      	echo $errlog."<br/>";
      	error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
      }
      
   }
   
   /**
         * @param
         * @return string Nombre de jours 'enfant malade' pris sur la période courante
   */
   function nbjrsenfantmaladeutilise($debut_interval,$fin_interval)
   {
		$sql = "SELECT SUM(DEMANDE.NBREJRSDEMANDE) FROM AFFECTATION,DECLARATIONTP,DEMANDEDECLARATIONTP,DEMANDE
WHERE AFFECTATION.HARPEGEID='" . $this->harpegeid . "'
AND AFFECTATION.AFFECTATIONID=DECLARATIONTP.AFFECTATIONID
AND DECLARATIONTP.DECLARATIONID=DEMANDEDECLARATIONTP.DECLARATIONID
AND DEMANDE.DEMANDEID = DEMANDEDECLARATIONTP.DEMANDEID
AND DEMANDE.TYPEABSENCEID='enmal'
AND DEMANDE.DATEDEBUT>='" . $this->fonctions->formatdatedb($debut_interval)  . "'
AND DEMANDE.DATEFIN<='" . $this->fonctions->formatdatedb($fin_interval)   ."'
AND DEMANDE.STATUT='v'";

		// $this->fonctions->anneeref() . $this->fonctions->debutperiode()
		// ($this->fonctions->anneeref()  +1) . $this->fonctions->finperiode()
		//echo "SQL = $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "")
		{
			$errlog = "Agent->nbjrsenfantmaladeutilise (AGENT) : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			return NULL;
      }
      if (mysql_num_rows($query) == 0)
			return 0;
	  $result = mysql_fetch_row($query);
		  return (floatval($result[0]));
		
   }
	
   /**
         * @param date $debut_interval beginning date of the planning
         * @param date $fin_interval ending date of the planning
         * @return object the planning object.
   */
	function planning($debut_interval,$fin_interval)
	{
		$planning = new planning($this->dbconnect);
		$planning->load($this->harpegeid, $debut_interval, $fin_interval);
		return $planning; 
	}
	
   /**
         * @param date $debut_interval beginning date of the planning
         * @param date $fin_interval ending date of the planning
         * @param boolean $clickable optional true means that the planning allow click on elements. false otherwise
         * @param boolean $showpdflink optional true means that a link to display planning in pdf format is allowed. false means the link is hidden
         * @return string the planning html text.
   */
	function planninghtml($debut_interval,$fin_interval,$clickable = FALSE, $showpdflink = TRUE)
	{
		$planning = new planning($this->dbconnect);
		$htmltext = $planning->planninghtml($this->harpegeid,$debut_interval, $fin_interval,$clickable,$showpdflink);
		return $htmltext;
	}
	
   /**
         * @param object $destinataire the mail recipient
         * @param string $objet the subject of the mail
         * @param string $message the body of the mail
         * @param string $piecejointe the name of the document to join to the mail
         * @return 
   */
	function sendmail($destinataire = null, $objet = null, $message = null, $piecejointe = null, $ics = null)
	{
		//----------------------------------
		// Construction de l'entête
		//----------------------------------
		$boundary = "-----=".md5(uniqid(rand()));
		$header  = "Reply-to: " . $this->adressemail . "\r\n";
//		$header  .= "From: " . $this->adressemail . "\r\n";
		$header  .= "From: " . $this->prenom() . " " . $this->nom() . "<" . $this->adressemail  . ">\r\n";
		$header  .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; charset=\"utf-8\"; boundary=\"$boundary\"\r\n";
		$header .= "\r\n";
		//--------------------------------------------------
		// Construction du message proprement dit
		//--------------------------------------------------
		$msg = "$objet\r\n";
		
		//---------------------------------
		// 1ère partie du message
		// Le texte
		//---------------------------------
		
		$msg .= "--$boundary\r\n";
		$msg .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
		//$msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
		$msg .= "Content-Transfer-Encoding:8bit\r\n";
		$msg .= "\r\n";
		$msg .= "Bonjour,<br><br>";
		$msg .= nl2br(htmlentities("$message",ENT_QUOTES,"UTF-8",false)) ."<br>Cliquez sur le lien <a href='" . $this->fonctions->liredbconstante('G2TURL') . "'>G2T</a><br><br>Cordialement<br><br>" . ucwords(strtolower($this->prenom . "  " . $this->nom)) ."\r\n";
		
		//$msg .= htmlentities("$message",ENT_IGNORE,"ISO8859-15") ."<br><br>Cordialement<br><br>" . ucwords(strtolower("$PRENOM $NOM")) ."\r\n";
		$msg .= "\r\n";
		
                if (!is_null($ics)) 
                {
                    $msg .= "<br><br><p><font size=\"2\">La pièce jointe est un fichier iCalendar contenant plus d'informations concernant l'événement. Si votre client de courrier supporte les requêtes iTip vous pouvez utiliser ce fichier pour mettre à jour votre copie locale de l'événement.</font></p>" ;
                    $msg .= "\r\n";
                    $msg .= "--$boundary\r\n";
                    $msg .= "Content-Type: text/calendar;name=\"conge.ics\";method=REQUEST;charset=\"utf-8\"\n";
                    $msg .= "Content-Transfer-Encoding: 8bit\n\n";
                    $msg .= $ics;
                    $msg .= "\r\n\r\n";
                }
		$msg .= "\r\n";
                
		if (!is_null($piecejointe ))
		{
			//---------------------------------
			// 2nde partie du message
			// Le fichier (inline)
			//---------------------------------
			$file = "$piecejointe";
			$basename = basename($file);
			//echo "basename = " . $basename . "<br>";
			$fp   = fopen($file, "rb");
			$attachment = fread($fp, filesize($file));
			fclose($fp);
			$attachment = chunk_split(base64_encode($attachment));
			
			$msg .= "--$boundary\r\n";
//			$msg .= "Content-Type: application/pdf; name=\"$file\"\r\n";
			$msg .= "Content-Type: application/pdf; name=\"$basename\"\r\n";
			$msg .= "Content-Transfer-Encoding: base64\r\n";
//			$msg .= "Content-Disposition: attachment; filename=\"$file\"\r\n";
			$msg .= "Content-Disposition: attachment; filename=\"$basename\"\r\n";
			$msg .= "\r\n";
			$msg .= $attachment . "\r\n";
			$msg .= "\r\n\r\n";
		}		
		$msg .= "--$boundary--\r\n\r\n";
		
		//ini_set(sendmail_from,$this->adressemail);
		ini_set('sendmail_from', $this->prenom() . " " . $this->nom() . "<" . $this->adressemail  . ">");
		ini_set('SMTP',$this->fonctions->liredbconstante("SMTPSERVER") );
		//$objet .=" G2T";
		mail($destinataire->prenom() . " " . $destinataire->nom() . " <" .$destinataire->mail() . ">", "$objet", "$msg",	"$header");
//		mail($destinataire->prenom() . " " . $destinataire->nom() . " <" .$destinataire->mail() . ">", utf8_encode("$objet"), "$msg",	"$header");
		ini_restore('sendmail_from');
		
	}
	
   /**
         * @param date $datedebut the beginning date of the interval to search affectations
         * @param date $datefin the ending date of the interval to search affectations
         * @return array list of objects affectation
   */
	function affectationliste($datedebut,$datefin)
	{
		$affectationliste = null;
		$sql = "SELECT SUBREQ.AFFECTATIONID FROM ((SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE FROM AFFECTATION WHERE HARPEGEID = '" . $this->harpegeid . "' AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN OR DATEFIN='0000-00-00'))";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE FROM AFFECTATION WHERE HARPEGEID='" . $this->harpegeid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
		$sql = $sql . " UNION ";
		$sql = $sql . "(SELECT AFFECTATIONID,DATEDEBUT,OBSOLETE FROM AFFECTATION WHERE HARPEGEID='" . $this->harpegeid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
		$sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";
		$sql = $sql . " ORDER BY SUBREQ.DATEDEBUT";
		//echo "sql = $sql <br>";
		$query=mysql_query ($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "") {
			$errlog = "Agent->affectationliste : " . $erreur;
			echo $errlog."<br/>";
			error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
		}
		if (mysql_num_rows($query) == 0)
		{
			//echo "Agent->affectationliste : L'agent $this->harpegeid n'a pas d'affectation entre $datedebut et $datefin <br>";
		}
		while ($result = mysql_fetch_row($query))
		{
			$affectation = new affectation($this->dbconnect);
			//echo "result[0] = $result[0] <br>";
			$affectation->load("$result[0]");
			$affectationliste[$affectation->affectationid()] = $affectation;
			unset($affectation);
		}
		//print_r ($affectationliste) ; echo "<br>";
		return $affectationliste;
	}
	
   /**
         * @param date $datedebut the beginning date to check
         * @param date $datefin the ending date to check
         * @return boolean true if the declaration of agent is correct. false otherwise
   */
	function dossiercomplet($datedebut,$datefin)
	{
		// Un dossier est complet si
		//		- Il a une affectation durant toute la période
		//		- Il a une déclaration de TP (validée) sur toute la période
		// => On charge le planning de l'agent pour la période
		// => On parcours le planning pour vérifier
		$planning = new planning($this->dbconnect);
		$planning->load($this->harpegeid, $datedebut,$datefin);
		if (!is_null($planning))
		{
			// pour tous les elements du planning on vérifie...
			$listeelement = $planning->planning();
			foreach ($listeelement as $key => $element)
			{
				if (strcasecmp($element->type(),"nondec")==0)
				{
					//echo "Le premier element non declaré est : " . $key . "<br>";
					return false;
				}
			}
			return true;
		}
		return false;
	}
	
   /**
         * @param 
         * @return array list of objects structure where the agent is responsable
   */
	function structrespliste()
	{
		$structliste = null;
		if ($this->estresponsable())
		{
			//echo "Je suis responsable...<br>";
			$sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE RESPONSABLEID = '%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
			//echo "sql = " . $sql . "<br>";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "") {
				$errlog = "Agent->structrespliste : " . $erreur;
				echo $errlog."<br/>";
				error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			}
			while ($result = mysql_fetch_row($query))
			{
				//On charge la structure
				$struct = new structure($this->dbconnect);
				$struct->load("$result[0]");
				$structliste[$struct->id()] = $struct;
				unset($struct);
			}
		}
		return $structliste;
	}
	
   /**
         * @param 
         * @return array list of objects structure where the agent is manager
   */
	function structgestliste()
	{
		$structliste = null;
		if ($this->estgestionnaire())
		{
			//echo "Je suis gestionnaire...<br>";
			$sql = sprintf("SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID = '%s' AND DATECLOTURE>=DATE(NOW())", $this->fonctions->my_real_escape_utf8($this->harpegeid));
			//echo "sql = " . $sql . "<br>";
			$query=mysql_query ($sql, $this->dbconnect);
			$erreur=mysql_error();
			if ($erreur != "") {
				$errlog = "Agent->structgestliste : " . $erreur;
				echo $errlog."<br/>";
				error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
			}
			while ($result = mysql_fetch_row($query))
			{
				//echo "Je charge la structure "  . $result[0] . " <br>";
				$struct = new structure($this->dbconnect);
				$struct->load("$result[0]");
				$structliste[$struct->id()] = $struct;
				unset($struct);
			}
		}
		return $structliste;
	}

   /**
         * @param 
         * @return array list of objects structure where the agent manage the lower structure query 
   */
	function structgestcongeliste()
	{
		$structliste = null;
		if ($this->estgestionnaire())
		{
			//echo "Je suis gestionnaire...<br>";
			// Liste des structures donc je suis gestionnaire
			$structgestliste = $this->structgestliste();
			//echo "<br>structgestliste = "; print_r((array) $structgestliste) ; echo "<br>";
			foreach ((array)$structgestliste as $structid => $structure)
			{
				// Pour chaque structure fille, on regarde si je gère les demandes du responsable
				$structfilleliste = $structure->structurefille();
				//echo "<br>structfilleliste = "; print_r((array) $structfilleliste) ; echo "<br>";
				foreach ((array)$structfilleliste as $structfilleid => $structfille)
				{
					//echo "<br>structfilleid = " . $structfilleid . "<br>";
					//echo "structfille->resp_envoyer_a() = "; print_r($structfille->resp_envoyer_a()); echo "<br>";
					$agent = $structfille->resp_envoyer_a();
					if (!is_null($agent))
					{
						if ($agent->harpegeid() == $this->harpegeid)
						{
							$structliste[$structfilleid] = $structfille;
						}
					}
				}
			}
		}
		return $structliste;
	}
	
   /**
         * @param sting $anneeref optional year of reference (2012 => 2012/2013, 2013 => 2013/2014). If not set, the current year is used
         * @param string $erreurmsg concat the errors text with an existing string 
         * @return array list of objects solde
   */
   function soldecongesliste($anneeref = null, &$erreurmsg = "")
   {
   	$soldeliste = null;
   	if (is_null($anneeref))
   	{
   		$anneeref = date("Y");
   		$errlog = "Agent->soldecongesliste : L'année de référence est NULL ==> On fixe à l'année courante !!!! ATTENTION DANGER !!!";
   		echo $errlog."<br/>";
   		error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
		$erreurmsg = $erreurmsg . $errlog . "<br/>";
   	}
   	
 /*
   	if ($anneeref == $this->fonctions->anneeref())
   	{
	   	if (is_null($this->dossieractif()))
	   			return null;

   	}
*/
   	if (date("m") >= substr($this->fonctions->debutperiode(), 0, 2))
   	{
   		$annee_recouvr=date("Y")+1;
   	}
   	else
   	{
   		$annee_recouvr=date("Y");
   	}
		// echo "date (Ymd) = " . date("Ymd") . " <br>";
		// echo "date (md)= " . date("md") . " <br>";
		// echo "anneeref = " . $anneeref . "<br>";
		// echo "annee_recouvr = " . $annee_recouvr. "<br>";
		// echo "this->fonctions->debutperiode() = " . $this->fonctions->debutperiode() . "<br>";
		// echo "this->fonctions->liredbconstante(FIN_REPORT) = " . $this->fonctions->liredbconstante("FIN_REPORT") . "<br>";

   	//$reportactif = ($this->fonctions->liredbconstante("REPORTACTIF") == 'O');
   	//if ($reportactif) echo "ReportActif = true<br>"; else echo "ReportActif = false<br>";
   	
   	$complement = new complement($this->dbconnect);
   	$complement->load($this->harpegeid,"REPORTACTIF");
   	// Si le complement n'est pas initialisé (NULL ou "") alors on active le report
   	if (strcasecmp($complement->valeur(),"O")==0 or strlen($complement->valeur()) == 0)
   		$reportactif = true;
   	else
			$reportactif = FALSE;
	
		
   	if((date("Ymd")>=$anneeref . $this->fonctions->debutperiode() && date("Ymd")<=$annee_recouvr . $this->fonctions->liredbconstante("FIN_REPORT")) && $reportactif)
   	{
   		$requ_sel_typ_conge="((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND (ANNEEREF= '" . $anneeref . "' OR ANNEEREF= '" . ($anneeref-1) . "'))";
   	}
   	else
   	{
   		$requ_sel_typ_conge="((SOLDE.TYPEABSENCEID LIKE 'ann%' OR SOLDE.TYPEABSENCEID LIKE 'sup%') AND ANNEEREF= '" . $anneeref . "')";
   	}
   	
   	$sql = "SELECT SOLDE.TYPEABSENCEID FROM SOLDE,TYPEABSENCE WHERE HARPEGEID='". $this->harpegeid ."' AND SOLDE.TYPEABSENCEID=TYPEABSENCE.TYPEABSENCEID  AND " . $requ_sel_typ_conge ;
   	//echo "sql = " . $sql . "<br>";
   	$query=mysql_query ($sql, $this->dbconnect);
   	$erreur=mysql_error();
   	if ($erreur != "") {
   		$errlog = "Agent->soldecongesliste : " . $erreur;
   		echo $errlog."<br/>";
   		error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
   	}
   	if (mysql_num_rows($query) == 0)
   	{
   		//echo "Agent->soldecongesliste : L'agent $this->harpegeid n'a pas de solde de congés pour l'année de référence $anneeref. <br>";
   		$errlog = " L'agent ". $this->civilite() . " " . $this->nom() . " " . $this->prenom()  . " n'a pas de solde de congés pour l'année de référence $anneeref";
   		$erreurmsg = $erreurmsg.$errlog ;
   		error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
   	}
		while ($result = mysql_fetch_row($query))
		{
			$solde = new solde($this->dbconnect);
			$solde->load($this->harpegeid,"$result[0]");
			$soldeliste[$solde->typeabsenceid()] = $solde;
			unset($solde);
		}
		
		//echo "Avant le new.. <br>";
		$cet = new cet($this->dbconnect);
		//echo "Avant le load du CET <br>";
		$erreur = $cet->load($this->harpegeid);
		//echo "Erreur = " . $erreur . "<br>";
		if ($erreur == "")
		{
			//echo "Avant la comparaison date <br>";
			//echo "cet->datedebut() = " . $cet->datedebut() . "<br>";
			///echo "formatdatedb(cet->datedebut()) = " . $this->fonctions->formatdatedb($cet->datedebut()) . "<br>";
			//echo "this->fonctions->anneeref() = " . $this->fonctions->anneeref() . "<br>";
			// "anneeref+1 = " . ($anneeref+1) . "<br>";
			//echo "this->fontions->finperiode() = " . $this->fonctions->finperiode() . "<br>";
			if ($this->fonctions->formatdatedb($cet->datedebut()) <= ($anneeref+1) . $this->fonctions->finperiode())
			{
				$solde = new solde($this->dbconnect);
				//echo "Avant le load du solde <br>";
				$solde->load($this->harpegeid,$cet->idtotal());
				$soldeliste[$solde->typeabsenceid()] = $solde;
				unset($solde);
			} 
		}
		
		return $soldeliste;
	}

   /**
         * @param sting year of reference (2012 => 2012/2013, 2013 => 2013/2014)
         * @param boolean $infoagent optional display header of solde array if set to TRUE.
         * @param object $pdf optional pdf object representing the pdf file. if set, the array is append to the existing pdf. If not set a new pdf file is created
         * @param boolean $header optional if set to true, the header of the array if inserted in the pdf file. no header set in pdf file otherwise
         * @return 
   */
	function soldecongespdf($anneeref, $infoagent = FALSE, $pdf = NULL, $header = TRUE)
	{
		$closeafter = FALSE;
		if (is_null($pdf))
		{
			//$pdf=new FPDF();
			$pdf=new TCPDF();
			//define('FPDF_FONTPATH','fpdffont/');
			$pdf->Open();
			$pdf->SetHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
			$closeafter = TRUE;
		}
		//echo "Apres le addpage <br>";
		if ($header == TRUE)
		{
			$pdf->AddPage('L');
			$pdf->Image(dirname(dirname(__FILE__)).'/images/logo_papeterie.png',10,5,60,20);
			$pdf->SetFont('helvetica', 'B', 15, '', true);
			$pdf->Ln(15);

			$old_structid="";
			$affectationliste = $this->affectationliste($this->fonctions->formatdate($anneeref . $this->fonctions->debutperiode()),$this->fonctions->formatdate(($anneeref+1) . $this->fonctions->finperiode()));
			
			foreach ((array)$affectationliste as $key => $affectation)
			{
				if ($old_structid != $affectation->structureid())
				{
					$structure = new structure($this->dbconnect);
					$structure->load($affectation->structureid());
					$nomstructure = $structure->nomlong() . " (" . $structure->nomcourt()  .")";
					$pdf->Cell(60,10,'Service : '. $nomstructure);
					$pdf->Ln();
					$old_structid = $affectation->structureid();
				}
			}
			
			
//			$pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('.$this->structure()->nomcourt() . ')' );
			$pdf->Ln(5);
			$pdf->Cell(60,10,'Historique des demandes de  : '. $this->civilite() . " " . $this->nom() . " " . $this->prenom());
			$pdf->Ln(5);
			$pdf->SetFont('helvetica', 'B', 11, '', true);
			$pdf->Cell(60,10,'Edité le '. date("d/m/Y"));
			$pdf->Ln(10);
		}
		$pdf->SetFont('helvetica', '', 8, '', true);
		$pdf->Ln(5);
		
		if (!$infoagent)
		{
			$headertext = "Etat des soldes pour l'année $anneeref / " . ($anneeref+1) . " du " . $this->fonctions->formatdate($anneeref . $this->fonctions->debutperiode()) . " au ";
			if (date("Ymd")>($anneeref+1) . $this->fonctions->finperiode())
				 $headertext = $headertext. $this->fonctions->formatdate(($anneeref+1) . $this->fonctions->finperiode());
			else 
				$headertext = $headertext . date("d/m/Y");
			$pdf->Cell(215,5,$headertext ,1,0,'C');
		}
		else
			$pdf->Cell(215,5,"Etat des soldes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() ,1,0,'C');
		$pdf->Ln(5);
		$pdf->Cell(75,5,"Type congé",1,0,'C');
		$pdf->Cell(30,5,"Droits acquis",1,0,'C');
		$pdf->Cell(30,5,"Droit pris",1,0,'C');
		$pdf->Cell(30,5,"Solde actuel",1,0,'C');
		$pdf->Cell(50,5,"Demandes en attente",1,0,'C');
		$pdf->Ln(5);

		$totaldroitaquis=0;
		$totaldroitpris=0;
		$totaldroitrestant=0;
		$totaldemandeattente=0;
		$soldeliste = $this->soldecongesliste($anneeref);
		foreach ((array)$soldeliste as $key => $tempsolde)
		{
			$pdf->Cell(75,5,$tempsolde->typelibelle(),1,0,'C');

			$textdroitaquis = $tempsolde->droitaquis() . "";
			if (strcmp(substr($tempsolde->typeabsenceid(), 0,3), 'ann')==0) // Si c'est un congé annuel
			{
				if ($demande = $this->aunedemandecongesbonifies('20' . substr($tempsolde->typeabsenceid(), 3,2))) // On regarde si il y a une demande de congés bonifiés
					$textdroitaquis = $textdroitaquis . " (C. BONIF.)";
			}
			$pdf->Cell(30,5,$textdroitaquis,1,0,'C');
			$pdf->Cell(30,5,$tempsolde->droitpris() . "",1,0,'C');
			$pdf->Cell(30,5,$tempsolde->solde() . "",1,0,'C');
			$pdf->Cell(50,5,$tempsolde->demandeenattente() . "",1,0,'C');
			$totaldroitaquis = $totaldroitaquis + $tempsolde->droitaquis();
			$totaldroitpris = $totaldroitpris + $tempsolde->droitpris();
			$totaldroitrestant = $totaldroitrestant + $tempsolde->solde();
			$totaldemandeattente = $totaldemandeattente + $tempsolde->demandeenattente();
			$pdf->Ln(5);
		}
/*
		$pdf->Cell(75,5,"Total",1,0,'C');
		$pdf->Cell(30,5,$totaldroitaquis . "",1,0,'C');
		$pdf->Cell(30,5,$totaldroitpris . "",1,0,'C');
		$pdf->Cell(30,5,$totaldroitrestant . "",1,0,'C');
		$pdf->Cell(50,5,$totaldemandeattente . "",1,0,'C');
*/		
		// $pdf->Ln(8);
		$pdf->Cell(8,5,"Soldes de congés donnés sous réserve du respect des règles de gestion");
		$pdf->Ln(8);
		//ob_end_clean();
		if ($closeafter == TRUE) 
			$pdf->Output();
	}
	
   /**
         * @param sting year of reference (2012 => 2012/2013, 2013 => 2013/2014)
         * @param boolean $infoagent optional display header of solde array if set to TRUE.
         * @return string the html text of the array
   */
	function soldecongeshtml($anneeref, $infoagent = FALSE)
	{
		//echo "anneeref = " . $anneeref . "<br>";
		
		$htmltext =             "<br>";
		$htmltext = $htmltext . "<div id='soldeconges'>";
  		$htmltext = $htmltext . "      <center>";
		$htmltext = $htmltext . "      <table class='tableau'>";
		if (!$infoagent)
	  		$htmltext = $htmltext . "      <tr class='titre'><td colspan=5>Etat des soldes pour l'année $anneeref / " . ($anneeref+1) . "</td></tr>";
		else 
			$htmltext = $htmltext . "      <tr class='titre'><td colspan=5>Etat des soldes pour " . $this->civilite() . " " . $this->nom() . " " . $this->prenom() . "</td></tr>";

  		$htmltext = $htmltext . "         <tr class='entete'><td>Type congé</td><td>Droits acquis</td><td>Droit pris</td><td>Solde actuel</td><td>Demandes en attente</td></tr>";
  		$totaldroitaquis=0;
  		$totaldroitpris=0;
  		$totaldroitrestant=0;
  		$totaldemandeattente=0;
  		//echo "soldecongeshtml => Avant solde Liste...<br>";
  		$soldecongesliste = $this->soldecongesliste($anneeref);
  		//echo "soldecongeshtml => Apres solde Liste...<br>";

  		if (!is_null($soldecongesliste))
  		{
			foreach ($soldecongesliste as $key => $tempsolde)
			{
				$htmltext = $htmltext . "      <tr class='element'>";
	      		$htmltext = $htmltext . "         <td>" . $tempsolde->typelibelle() . "</td>";
				$htmltext = $htmltext . "         <td>" . $tempsolde->droitaquis();
				if (strcmp(substr($tempsolde->typeabsenceid(), 0,3), 'ann')==0) // Si c'est un congé annuel
				{
					if ($demande = $this->aunedemandecongesbonifies('20' . substr($tempsolde->typeabsenceid(), 3,2)))  // On regarde si il y a une demande de congés bonifiés
						$htmltext = $htmltext . " (C. BONIF.)";
				}
				$htmltext = $htmltext ."</td>";
				$htmltext = $htmltext . "         <td>" . $tempsolde->droitpris() . "</td>";
				$htmltext = $htmltext . "         <td>" . $tempsolde->solde() ."</td>";
				$htmltext = $htmltext . "         <td>" . $tempsolde->demandeenattente() ."</td>";
				$htmltext = $htmltext . "      </tr>";
				$totaldroitaquis = $totaldroitaquis + $tempsolde->droitaquis();
				$totaldroitpris = $totaldroitpris + $tempsolde->droitpris();
				$totaldroitrestant = $totaldroitrestant + $tempsolde->solde();
				$totaldemandeattente = $totaldemandeattente + $tempsolde->demandeenattente();
			}
		}
/*
		$htmltext = $htmltext . "         <tr class='element'>";
		$htmltext = $htmltext . "	          <td>Total</td>";
		$htmltext = $htmltext . "	          <td>". $totaldroitaquis ."</td>"; //number_format($totaldroitaquis,1) ."</td>";
		$htmltext = $htmltext . "	          <td>". $totaldroitpris ."</td>"; //number_format($totaldroitpris,1) ."</td>";
		$htmltext = $htmltext . "	          <td>". $totaldroitrestant ."</td>"; //number_format($totaldroitrestant,1) ."</td>";
		$htmltext = $htmltext . "	          <td>". $totaldemandeattente ."</td>";
		$htmltext = $htmltext . "	       </tr>";
*/
		$htmltext = $htmltext . "      </table>";
		$htmltext = $htmltext . "<font color='#EF4001'>Soldes de congés donnés sous réserve du respect des règles de gestion</font>";
		$htmltext = $htmltext . "      </center>";
		$htmltext = $htmltext . "</div>";
		$htmltext = $htmltext . "<br>";
		
		return $htmltext;
	}
	
   /**
         * @param date $datedebut date of the beginning of the interval
         * @param date $datefin date of the ending of the interval
         * @param string $structureid optional the structure identifier 
         * @param boolean $showlink optional if true, display link to display array in pdf format. hide link otherwise
         * @return string the html text of the array
   */
	function demandeslistehtml($datedebut,$datefin, $structureid = null, $showlink = true)
	{
		$demandeliste = null;
		$synthesetab = array();
		$affectationliste = $this->affectationliste($datedebut, $datefin);
		$affectation = new affectation($this->dbconnect);
		$declarationTP = new declarationTP($this->dbconnect);
		$demande = new demande($this->dbconnect);
		if (!is_null($affectationliste))
		{
			foreach ($affectationliste as $key => $affectation)
			{
				//echo "<br><br>Affectation (".  $affectation->affectationid()  .")  date debut = " . $affectation->datedebut() . "  Date fin = " . $affectation->datefin() . "<br>";
				unset($declarationTPliste);
				$declarationTPliste = $affectation->declarationTPliste($datedebut, $datefin);
				if (!is_null($declarationTPliste))
				{
					foreach ($declarationTPliste as $key => $declarationTP) 
					{
						//echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ")  Debut = " . $declarationTP->datedebut() . "   Fin = " . $declarationTP->datefin() . "<br>";
						//echo "<br>Liste = "; print_r($declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin())); echo "<br>";
						$demandeliste = array_merge((array)$demandeliste,(array)$declarationTP->demandesliste($datedebut, $datefin));
					}
				}
			}
		}
		//echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
		// On enlève les doublons des demandes !!!
		$uniquedemandeliste = array();
		if (is_array($demandeliste))
		{
			foreach ($demandeliste as $key => $demande)
			{
				$uniquedemandeliste[$demande->id()] = $demande;
			}
			$demandeliste = $uniquedemandeliste;
			unset($uniquedemandeliste);
		}
		//echo "#######demandeliste (Count=" . count($demandeliste) .")  = "; print_r($demandeliste); echo "<br>";
		
		$htmltext =                   "<br>";
		$htmltext = $htmltext .       "<div id='demandeliste'>";
		$htmltext = $htmltext .       "<center><table class='tableau' >";
		if (count($demandeliste) == 0)
			$htmltext = $htmltext .    "   <tr class='titre'><td>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
		else
		{
			$htmltext = $htmltext .    "   <tr class='titre'><td colspan=7>Tableau récapitulatif des demandes</td></tr>";
			$htmltext = $htmltext .    "   <tr class='entete'><td>Type de congé</td><td>Date de dépot</td><td>Date de début</td><td>Date de fin</td><td>Nbr de jours</td><td>Etat de la demande</td><td>Motif (obligatoire si le congé est annulé)</td></tr>";
			foreach ($demandeliste as $key => $demande)
			{
				if ($demande->motifrefus() != "" or strcasecmp($demande->statut(),"r")!=0)
				{
					$htmltext = $htmltext . "<tr class='element'>";
					$libelledemande = $demande->typelibelle();
					if (strlen($libelledemande)>40)
					{
						$libelledemande = substr($demande->typelibelle(),0,40) . "...";
					}
					
 					$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $libelledemande;
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext . "</td>";
 					$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $demande->date_demande();
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext ."</td>";
 					$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut());
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext . "</td>";
 					$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin());
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext . "</td>";
 					$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $demande->nbrejrsdemande();
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext . "</td>";
	 				$htmltext = $htmltext . "   <td>";
 					if (strlen($demande->commentaire())!=0) 
 					{
 					   $htmltext = $htmltext . "<span data-tip=" . chr(34) . $demande->commentaire() . chr(34) .">"; 
 					} 
 					$htmltext = $htmltext . $this->fonctions->demandestatutlibelle($demande->statut());
 					if (strlen($demande->commentaire())!=0)  
 					{
 					   $htmltext = $htmltext . "</span>"; 
 					} 
 					$htmltext = $htmltext . "</td>";
 					$htmltext = $htmltext . "   <td>" . $demande->motifrefus() . "</td>";
					$htmltext = $htmltext . "</tr>";
					if (strcasecmp($demande->statut(),"r")!=0) // Si la demande n'est pas annulée ou refusée
					{
						if (isset($synthesetab[$demande->typelibelle()]))
							$synthesetab[$demande->typelibelle()]=$synthesetab[$demande->typelibelle()]+$demande->nbrejrsdemande();
						else 
							$synthesetab[$demande->typelibelle()]=$demande->nbrejrsdemande();
					}
				}
			}
		}
		$htmltext = $htmltext .    "</table></center>";
		$htmltext = $htmltext .    "</div>";
		if (count($demandeliste) > 0)
		{
			$htmltext = $htmltext .    "<br>";
//			$htmltext = $htmltext .    print_r($synthesetab,true);
			$htmltext = $htmltext .       "<div id='demandeliste'>";
			$htmltext = $htmltext .       "<center><table class='tableau' >";
			$htmltext = $htmltext .    "   <tr class='titre'><td colspan=2>Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " au " . $this->fonctions->formatdate($datefin) ."</td></tr>";
			$htmltext = $htmltext .    "   <tr class='entete'><td>Type de congé</td><td>Droit pris</td></tr>";
			ksort($synthesetab);
			foreach ($synthesetab as $key => $nbrejrs)
			{
				$htmltext = $htmltext . "<tr class='element'>";
				$htmltext = $htmltext . "<td>" . $key . "</td>";
				$htmltext = $htmltext . "<td>" .$nbrejrs . "</td>" ;
				$htmltext = $htmltext . "</tr>";
			}
			$htmltext = $htmltext . "</table></center>";
			$htmltext = $htmltext . "</div>";
		}
		if ($showlink == TRUE)
		{
	//		$htmltext = $htmltext .    "<br>";
			$htmltext = $htmltext .    "<form name='userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
			$htmltext = $htmltext .    "<input type='hidden' name='agentid' value='" . $this->harpegeid()   ."'>";
			$htmltext = $htmltext .    "<input type='hidden' name='userpdf' value='no'>";
	//		$htmltext = $htmltext .    "<input type='hidden' name='previous' value='" . $_POST["previous"]  . "'>";
			$tempannee = substr($this->fonctions->formatdatedb($datedebut),0,4) ;
			$htmltext = $htmltext .    "<input type='hidden' name='anneeref' value='" . $tempannee ."'>";
			$htmltext = $htmltext .    "<input type='hidden' name='typepdf' value='listedemande'>";
			$htmltext = $htmltext .    "</form>";
			$htmltext = $htmltext .    "<a href='javascript:document.userlistedemandepdf_" . $this->harpegeid() . "_" . $structureid . ".submit();'>Liste des demandes en PDF</a>";
		}
		$htmltext = $htmltext .    "<br><br>";
		return $htmltext;
		
	}
	
   /**
         * @param date $datedebut date of the beginning of the interval
         * @param date $datefin date of the ending of the interval
         * @param object $pdf optional the pdf object. if $pdf is set, the array is append to the existing pdf. Otherwise, a new pdf file is created 
         * @param boolean $header optional if set to true, the header of the array if inserted in the pdf file. no header set in pdf file otherwise
         * @return 
   */
	function demandeslistepdf($datedebut,$datefin, $pdf = NULL, $header = TRUE)
	{
		$demandeliste = null;
		$synthesetab = array();
		$affectationliste = $this->affectationliste($datedebut, $datefin);
		$affectation = new affectation($this->dbconnect);
		$declarationTP = new declarationTP($this->dbconnect);
		$demande = new demande($this->dbconnect);
		if (!is_null($affectationliste))
		{
			foreach ($affectationliste as $key => $affectation)
			{
				$declarationTPliste = $affectation->declarationTPliste($datedebut, $datefin);
				if (!is_null($declarationTPliste))
				{
					foreach ($declarationTPliste as $key => $declarationTP) 
					{
						$demandeliste = array_merge((array)$demandeliste,(array)$declarationTP->demandesliste($datedebut, $datefin));
					}
				}
			}
		}
		// On enlève les doublons des demandes !!!
		$uniquedemandeliste = array();
		if (is_array($demandeliste))
		{
			foreach ($demandeliste as $key => $demande)
			{
				$uniquedemandeliste[$demande->id()] = $demande;
			}
			$demandeliste = $uniquedemandeliste;
			unset($uniquedemandeliste);
		}
		//echo "#######demandeliste (Count=" . count($demandeliste) .")  = "; print_r($demandeliste); echo "<br>";
				
		$closeafter = FALSE;
		if (is_null($pdf))
		{
			//$pdf=new FPDF();
			$pdf=new TCPDF();
			//define('FPDF_FONTPATH','fpdffont/');
			$pdf->Open();
			$pdf->SetHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
			$closeafter = TRUE;
		}
		if ($header == TRUE)
		{
			$pdf->AddPage('L');
			//echo "Apres le addpage <br>";
			$pdf->SetHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
			$pdf->Image('../html/images/logo_papeterie.png',10,5,60,20);
			$pdf->SetFont('helvetica', 'B', 15, '', true);
			$pdf->Ln(15);
			foreach ($affectationliste as $key => $affectation)
			{
				$structure = new structure($this->dbconnect);
				$structure->load($affectation->structureid());
				$nomstructure = $structure->nomlong() . " (" . $structure->nomcourt()  .")";
				$pdf->Cell(60,10,'Service : '. $nomstructure);
				$pdf->Ln();
			}
			$pdf->Cell(60,10,'Historique des demandes de  : '. $this->civilite() . " " . $this->nom() . " " . $this->prenom());
			$pdf->Ln(5);
			$pdf->Cell(60,10,"Période du " . $this->fonctions->formatdate($datedebut) ." au " . $this->fonctions->formatdate($datefin));
			$pdf->Ln(10);
			$pdf->SetFont('helvetica', 'B', 11, '', true);
			$pdf->Cell(60,10,'Edité le '. date("d/m/Y"));
			$pdf->Ln(10);
		}
		$pdf->SetFont('helvetica', '', 8, '', true);
		
		$headertext = "Tableau récapitulatif des demandes - Congés pris entre " . $this->fonctions->formatdate($datedebut) . " et ";
		if (date("Ymd")>$datefin)
			$headertext = $headertext. $this->fonctions->formatdate($datefin);
		else
			$headertext = $headertext . date("d/m/Y");
		
		$pdf->Cell(275,5,$headertext,1,0,'C');
		$pdf->Ln(5);
		
		if (count($demandeliste) == 0)
			$pdf->Cell(275,5,"L'agent n'a aucun congé posé pour la période de référence en cours.",1,0,'C');
		else
		{
			$pdf->Cell(60,5,"Type de congé",1,0,'C');
			$pdf->Cell(25,5,"Date de dépot",1,0,'C');
			$pdf->Cell(30,5,"Date de début",1,0,'C');
			$pdf->Cell(30,5,"Date de fin",1,0,'C');
			$pdf->Cell(20,5,"Nbr de jours",1,0,'C');
			$pdf->Cell(30,5,"Etat de la demande",1,0,'C');
			$pdf->Cell(80,5,"Motif (obligatoire si le congé est annulé)",1,0,'C');
			$pdf->ln(5);
			foreach ($demandeliste as $key => $demande)
			{
				if ($demande->motifrefus() != "" or strcasecmp($demande->statut(),"r")!=0)
				{
					$libelledemande = $demande->typelibelle();
					if (strlen($libelledemande)>40)
					{
						$libelledemande = substr($demande->typelibelle(),0,40) . "...";
					}
					$pdf->Cell(60,5,$libelledemande,1,0,'C');
					$pdf->Cell(25,5,$demande->date_demande(),1,0,'C');
					$pdf->Cell(30,5,$demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()),1,0,'C');
					$pdf->Cell(30,5,$demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()),1,0,'C');
					$pdf->Cell(20,5,$demande->nbrejrsdemande(),1,0,'C');
					$pdf->Cell(30,5,$this->fonctions->demandestatutlibelle($demande->statut()),1,0,'C');
					$pdf->Cell(80,5,$demande->motifrefus(),1,0,'C');
					$pdf->ln(5);

					if (strcasecmp($demande->statut(),"r")!=0) // Si la demande n'est pas annulée ou refusée
					{
						if (isset($synthesetab[$demande->typelibelle()]))
							$synthesetab[$demande->typelibelle()]=$synthesetab[$demande->typelibelle()]+$demande->nbrejrsdemande();
						else
							$synthesetab[$demande->typelibelle()]=$demande->nbrejrsdemande();
					}
				}
			}
		}

		if (count($demandeliste) > 0)
		{
			$pdf->Ln(8);
			$headertext = "Synthèse des types de demandes du " . $this->fonctions->formatdate($datedebut) . " et ";
			if (date("Ymd")>$datefin)
				$headertext = $headertext. $this->fonctions->formatdate($datefin);
			else
				$headertext = $headertext . date("d/m/Y");
			$pdf->Cell(100,5,$headertext,1,0,'C');
			$pdf->Ln(5);
			$pdf->Cell(80,5,"Type de congé",1,0,'C');
			$pdf->Cell(20,5,"Droit pris",1,0,'C');
			$pdf->ln(5);
			ksort($synthesetab);
			foreach ($synthesetab as $key => $nbrejrs)
			{
				$libelledemande = $key;
				if (strlen($key)>40)
				{
					$libelledemande = substr($key,0,40) . "...";
				}
				$pdf->Cell(80,5,$libelledemande,1,0,'C');
				$pdf->Cell(20,5,$nbrejrs,1,0,'C');
				$pdf->ln(5);
			}
		}
		
		$pdf->Ln(8);
		
		
		//ob_end_clean();
		if ($closeafter == TRUE)
		{
			ob_end_clean();
			$pdf->Output();
		}
	}
	
   /**
         * @param date $debut_interval date of the beginning of the interval
         * @param date $fin_interval date of the ending of the interval
         * @param string $agentid optional deprecated parameter => not used in code
         * @param string $mode optional responsable mode or agent mode. default is agent
         * @param string $cleelement optional deprecated parameter => not used in code
         * @return string the html text of the array
   */
	function demandeslistehtmlpourgestion($debut_interval,$fin_interval, $agentid = null, $mode = "agent", $cleelement = null)
	{

		$liste = null;
		$affectationliste = $this->affectationliste($debut_interval, $fin_interval);
		$affectation = new affectation($this->dbconnect);
		$declarationTP = new declarationTP($this->dbconnect);
		$demande = new demande($this->dbconnect);
		if (!is_null($affectationliste))
		{
			foreach ($affectationliste as $key => $affectation)
			{
				//echo "<br><br>Affectation (".  $affectation->affectationid()  .")  date debut = " . $affectation->datedebut() . "  Date fin = " . $affectation->datefin() . "<br>";
				unset($declarationTPliste);
				$declarationTPliste = $affectation->declarationTPliste($debut_interval, $fin_interval);
				if (!is_null($declarationTPliste))
				{
					foreach ($declarationTPliste as $key => $declarationTP) 
					{
						//echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ")  Debut = " . $declarationTP->datedebut() . "   Fin = " . $declarationTP->datefin() . "<br>";
						//echo "<br>Liste = "; print_r($declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin())); echo "<br>";
						//$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin()));
						$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($debut_interval, $fin_interval));
					}
				}
			}
		}
		//echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
		// On enlève les doublons des demandes !!!
		$uniquedemandeliste = array();
		if (is_array($liste))
		{
			foreach ($liste as $key => $demande)
			{
				$uniquedemandeliste[$demande->id()] = $demande;
			}
			$liste = $uniquedemandeliste;
			unset($uniquedemandeliste);
		}
		//echo "#######demandeliste (Count=" . count($demandeliste) .")  = "; print_r($demandeliste); echo "<br>";
		
		$debut_interval = $this->fonctions->formatdatedb($debut_interval);
		$fin_interval = $this->fonctions->formatdatedb($fin_interval);

		$htmltext = "";
		//$htmltext =                   "<br>";
		if (count($liste) == 0)
		{
			//$htmltext = $htmltext .    "   <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
			$htmltext = "";
		}
		else
		{
			$premieredemande = TRUE;
			foreach ($liste as $key => $demande)
			{
				//echo "demandeslistehtmlpourgestion => debut du for " . $demande->id() . "<br>";
				//if (($demande->statut() == "a" and $mode == "agent") or ($demande->statut() == "v" and $mode == "resp"))
				if ((strcasecmp($demande->statut(),"a")==0 and strcasecmp($mode,"agent")==0) or (strcasecmp($demande->statut(),"v")==0 and strcasecmp($mode,"resp")==0))
				{
					if ($premieredemande)
					{
						$htmltext = $htmltext .       "<table class='tableausimple'>";
						$htmltext = $htmltext .    "   <tr ><td class='titresimple' colspan=7 align=center ><font color=#BF3021>Gestion des demandes pour " . $this->civilite() . " " .  $this->nom() . " " . $this->prenom() .  "</font></td></tr>";
						$htmltext = $htmltext .    "   <tr align=center><td class='cellulesimple'>Date de demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Nbre jours</td>";
						if (strcasecmp($demande->statut(),"a")==0 and strcasecmp($mode,"agent")==0)
							$htmltext = $htmltext . "<td class='cellulesimple'>Commentaire</td>";
						$htmltext = $htmltext . "<td class='cellulesimple'>Annuler</td>";
						if (strcasecmp($demande->statut(),"v")==0 and strcasecmp($mode,"resp")==0)
							$htmltext = $htmltext . "<td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td>";
						$htmltext = $htmltext . "</tr>";
						$premieredemande = FALSE;
					}

					$htmltext = $htmltext . "<tr align=center >";
					//					$htmltext = $htmltext . "   <td>" . $this->nom() . " " . $this->prenom() . "</td>";
					$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->date_demande() ."</td>";
					$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
					$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
					$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
					$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>";
					if (strcasecmp($demande->statut(),"a")==0 and strcasecmp($mode,"agent")==0)
						$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->commentaire() . "</td>";
					$htmltext = $htmltext . "<td class='cellulesimple'><input type='checkbox' name=cancel[" . $demande->id() . "] value='yes' /></td>";
					if (strcasecmp($demande->statut(),"v")==0 and strcasecmp($mode,"resp")==0)
						$htmltext = $htmltext . "   <td class='cellulesimple'><input type=text name=motif[" . $demande->id() . "] id=motif[" . $demande->id() . "] value='" . $demande->motifrefus()  . "'  size=40></td>";
					$htmltext = $htmltext . "</tr>";
				}
				//echo "demandeslistehtmlpourgestion => On passe au suivant <br>";
			}
			//$htmltext = $htmltext .    "<br>";
			if ($htmltext != "")
				$htmltext = $htmltext .    "</table>";
		}
		return $htmltext;
	}
	
   /**
         * @param date $debut_interval date of the beginning of the interval
         * @param date $fin_interval date of the ending of the interval
         * @param string $agentid optional the structure's responsable identifier (harpege ident)
         * @param string $structureid optional deprecated parameter => not used in code
         * @param string $cleelement optional deprecated parameter => not used in code
         * @return string the html text of the array
   */
	function demandeslistehtmlpourvalidation($debut_interval,$fin_interval, $agentid = null, $structureid = null, $cleelement = null)
	{
		$liste = null;
		$affectationliste = $this->affectationliste($debut_interval, $fin_interval);
		$affectation = new affectation($this->dbconnect);
		$declarationTP = new declarationTP($this->dbconnect);
		$demande = new demande($this->dbconnect);
		if (!is_null($affectationliste))
		{
			foreach ($affectationliste as $key => $affectation)
			{
				//echo "<br><br>Affectation (".  $affectation->affectationid()  .")  date debut = " . $affectation->datedebut() . "  Date fin = " . $affectation->datefin() . "<br>";
				unset($declarationTPliste);
				$declarationTPliste = $affectation->declarationTPliste($debut_interval, $fin_interval);
				if (!is_null($declarationTPliste))
				{
					foreach ($declarationTPliste as $key => $declarationTP) 
					{
						//echo "<br>DeclarationTP (" . $declarationTP->declarationTPid() . ")  Debut = " . $declarationTP->datedebut() . "   Fin = " . $declarationTP->datefin() . "<br>";
						//echo "<br>Liste = "; print_r($declarationTP->demandesliste($debut_interval, $fin_interval)); echo "<br>";
						//$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($declarationTP->datedebut(), $declarationTP->datefin()));
						$liste = array_merge((array)$liste,(array)$declarationTP->demandesliste($debut_interval, $fin_interval));
					}
				}
			}
		}
		//echo "####### demandeliste (Count=" . count($demandeliste) .") = "; print_r($demandeliste); echo "<br>";
		// On enlève les doublons des demandes !!!
		$uniquedemandeliste = array();
		if (is_array($liste))
		{
			foreach ($liste as $key => $demande)
			{
				$uniquedemandeliste[$demande->id()] = $demande;
			}
			$liste = $uniquedemandeliste;
			unset($uniquedemandeliste);
		}
		//echo "#######demandeliste (Count=" . count($demandeliste) .")  = "; print_r($demandeliste); echo "<br>";
		
		$debut_interval = $this->fonctions->formatdatedb($debut_interval);
		$fin_interval = $this->fonctions->formatdatedb($fin_interval);
			
//		$liste=$this->demandesliste($debut_interval,$fin_interval);
// 		foreach ($this->structure()->structurefille() as $key => $value)
// 		{
// 			echo "Structure fille = " . $value->nomlong() . "<br>";
// 			$listerespsousstruct = $value->responsable()->demandesliste($debut_interval,$fin_interval);
// 			$liste = array_merge($liste,$listerespsousstruct);
// 		}
		
		$htmltext = "";
		//$htmltext =                   "<br>";
		if (count($liste) == 0)
		{
			//$htmltext = $htmltext .    "   <tr><td class=titre1 align=center>L'agent n'a aucun congé posé pour la période de référence en cours.</td></tr>";
		}
		else
		{
			$premieredemande = TRUE;
			foreach ($liste as $key => $demande)
			{
				if (strcasecmp($demande->statut(),"a")==0)
				{
					$todisplay = true;
					// On n'affiche pas les demandes du responsable !!!!
					if ($agentid == $this->harpegeid)
					{
						$todisplay = false;
					}
					//echo "todisplay = $todisplay <br>";
					if ($todisplay)
					{
						if ($premieredemande)
						{
							$htmltext = $htmltext .       "<table class='tableausimple' width=100%>";
							$htmltext = $htmltext .    "   <tr><td class=titresimple colspan=7 align=center ><font color=#BF3021>Tableau des demandes à valider pour " . $this->civilite() . " " .  $this->nom() . " " . $this->prenom() .  "</font></td></tr>";
							$htmltext = $htmltext .    "   <tr align=center><td class='cellulesimple'>Date de demande</td><td class='cellulesimple'>Date de début</td><td class='cellulesimple'>Date de fin</td><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Nbre jours</td><td class='cellulesimple'>Etat de la demande</td><td class='cellulesimple'>Motif (obligatoire si le congé est annulé)</td></tr>";
							$premieredemande = FALSE;
						}
						
						$htmltext = $htmltext . "<tr align=center >";
	//					$htmltext = $htmltext . "   <td>" . $this->nom() . " " . $this->prenom() . "</td>";

						$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->date_demande() ."</td>";
						$htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datedebut()) . " " . $demande->datedebut() . " " . $this->fonctions->nommoment($demande->moment_debut()) . "</td>";
						$htmltext = $htmltext . "   <td class='cellulesimple'>" . $this->fonctions->nomjour($demande->datefin()) . " " . $demande->datefin() . " " . $this->fonctions->nommoment($demande->moment_fin()) . "</td>";
						if ($demande->type()=='enmal')
						{
							$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "  (" . $this->nbjrsenfantmaladeutilise($debut_interval, $fin_interval) . "/" . $this->nbjrsenfantmalade() . ")</td>";
						}
						else 
						{
							$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->typelibelle() . "</td>";
						}
						$htmltext = $htmltext . "   <td class='cellulesimple'>" . $demande->nbrejrsdemande() . "</td>";
						$htmltext = $htmltext . "   <td class='cellulesimple'>";
						$htmltext = $htmltext . "      <select name='statut[" . $demande->id() . "]'>";
						$htmltext = $htmltext . "         <option ";
						if (strcasecmp($demande->statut(),"v") == 0)
							$htmltext=$htmltext  . " selected ";
						$htmltext = $htmltext . " value='v'>" . $this->fonctions->demandestatutlibelle("v") . "</option>";
						$htmltext = $htmltext . "         <option ";
						if (strcasecmp($demande->statut(),"r") == 0)
							$htmltext=$htmltext  . " selected ";
						$htmltext = $htmltext . " value='r'>" . $this->fonctions->demandestatutlibelle("r") . "</option>";
						$htmltext = $htmltext . "         <option ";
						if (strcasecmp($demande->statut(),"a") == 0)
							$htmltext=$htmltext  . " selected ";
						$htmltext = $htmltext . " value='a'>" . $this->fonctions->demandestatutlibelle("a") . "</option>";
						$htmltext = $htmltext . "      <select>";
						$htmltext = $htmltext . "</td>";
						$htmltext = $htmltext . "   <td class='cellulesimple'><input type=text name='motif[" . $demande->id() . "]' id='motif[" . $demande->id() . "]' value='" . $demande->motifrefus()  . "' size=40 ></td>";
						$htmltext = $htmltext . "</tr>";
					}
				}
			}
			if (!$premieredemande)
				$htmltext = $htmltext .    "</table>";
			//$htmltext = $htmltext .    "<br>";
		}
		return $htmltext;
	}
	

   /**
         * @param 
         * @return string the html text of the array
   */
	function affichecommentairecongehtml()
	{
		$sql = "SELECT HARPEGEID,LIBELLE,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE 
FROM COMMENTAIRECONGE,TYPEABSENCE 
WHERE HARPEGEID='" . $this->harpegeid . "' AND (COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr($this->fonctions->anneeref(),2,2) . "' 
                                             OR COMMENTAIRECONGE.TYPEABSENCEID LIKE '%" . substr(($this->fonctions->anneeref()-1),2,2)  ."' 
                                             OR COMMENTAIRECONGE.TYPEABSENCEID='cet') 
                                           AND COMMENTAIRECONGE.TYPEABSENCEID = TYPEABSENCE.TYPEABSENCEID";
		//echo "SQL = " . $sql . "<br>";
		$query=mysql_query($sql, $this->dbconnect);
		$erreur=mysql_error();
		if ($erreur != "") {
			echo "Agent->affichecommentairecongehtml : " . $erreur . "<br>";
			error_log(basename(__FILE__)." Agent->affichecommentairecongehtml : " . $erreur);
		}
		$htmltext = "";
		$premiercomment = TRUE;
		$htmltext = $htmltext . "<center><table class='tableausimple'>";
		while ($result = mysql_fetch_row($query))
		{
			if ($premiercomment)
			{
				$htmltext = $htmltext . "<tr><td class='titresimple' colspan=4 align=center>Commentaires sur les modifications de congés</td></tr>";
				$htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Type congé</td><td class='cellulesimple'>Date modification</td><td class='cellulesimple'>Jours</td><td class='cellulesimple'>Commentaire</td></tr>";
				$premiercomment = FALSE;
			}				
			
			$htmltext = $htmltext . "<tr align=center>";
			$htmltext = $htmltext . "<td class='cellulesimple'>" . $result[1]  ."</td>";
			$htmltext = $htmltext . "<td class='cellulesimple'>" . $this->fonctions->formatdate($result[2])  ."</td>";
			if ($result[4] > 0)
				$htmltext = $htmltext . "<td class='cellulesimple'>+" . (real)($result[4])  ."</td>";
			else
				$htmltext = $htmltext . "<td class='cellulesimple'>" . (real)($result[4])  ."</td>";
			$htmltext = $htmltext . "<td class='cellulesimple'>" . $result[3]  ."</td>";
			$htmltext = $htmltext . "</tr>";
		}
		$htmltext = $htmltext . "</table></center>";
		$htmltext = $htmltext . "<br>";
		return $htmltext;
	}
	
   /**
         * @param string $typeconge optional type of vacation. default is null
         * @param string $nbrejours optional number of day of the vacation. default is null
         * @param string $commentaire optional comment for the vacation. default is null
         * @return 
   */
	function ajoutecommentaireconge($typeconge = null, $nbrejours = null, $commentaire = null)
	{
		$date =date("d/m/Y");
		$sql = "INSERT INTO COMMENTAIRECONGE(HARPEGEID,TYPEABSENCEID,DATEAJOUTCONGE,COMMENTAIRE,NBRJRSAJOUTE)
		        VALUES ('" . $this->harpegeid  . "','" . $typeconge . "','" . $this->fonctions->formatdatedb($date) . "','" . str_replace("'","''",$commentaire) . "','" . $nbrejours . "')";
		$query = mysql_query($sql, $this->dbconnect);
		$erreur = mysql_error();
		if ($erreur != "")
		{
			$message = "$erreur";
			error_log(basename(__FILE__)." ".$erreur);
		}
		
	}
	
	function aunedemandecongesbonifies($anneeref)
	{
		$demande=null;
		$debutperiode = $this->fonctions->formatdatedb($anneeref . $this->fonctions->debutperiode());
		$finperiode = $this->fonctions->formatdatedb(($anneeref+1) . $this->fonctions->finperiode());
		$sql = "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE WHERE HARPEGEID='" . $this->harpegeid ."' AND HARPTYPE='CONGE_BONIFIE' AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
		$query = mysql_query($sql, $this->dbconnect);
		$erreur_requete=mysql_error();
		if ($erreur_requete!="")
			error_log(basename(__FILE__)." ".$erreur_requete);
		if (mysql_num_rows($query) != 0) // Il existe un congé bonifié pour la période => On le solde des congés à 0
		{
			$resultcongbonif = mysql_fetch_row($query);
			$demande = new demande($this->dbconnect);
			$demande->datedebut($resultcongbonif[1]);
			$demande->datefin($resultcongbonif[2]);
			$demande->type('harp');
		}
		return $demande;
		
	}
	
	
}

?> 