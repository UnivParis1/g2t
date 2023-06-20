<?php

/*
 * 
CREATE TABLE `TELETRAVAIL` (
  `TELETRAVAILID` INT(11) NOT NULL AUTO_INCREMENT,
  `AGENTID` VARCHAR(10) NOT NULL,
  `DATEDEBUT` DATE NOT NULL,
  `DATEFIN` DATE NOT NULL,
  `TABTELETRAVAIL` VARCHAR(14) NOT NULL,
  `STATUT` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`TELETRAVAILID`));
ALTER TABLE `TELETRAVAIL` 
  ADD COLUMN `TYPECONVENTION` VARCHAR(45) NOT NULL AFTER `STATUT`,
  ADD COLUMN `ESIGNATUREID` VARCHAR(30) NULL AFTER `TYPECONVENTION`,
  ADD COLUMN `ESIGNATUREURL` VARCHAR(200) NULL AFTER `ESIGNATUREID`,
  ADD COLUMN `COMMENTAIRE` VARCHAR(300) NULL AFTER `ESIGNATUREURL`,
  ADD COLUMN `MOTIFMEDICALSANTE` VARCHAR(3) NOT NULL DEFAULT '0' AFTER `COMMENTAIRE`,
  ADD COLUMN `MOTIFMEDICALGROSSESSE` VARCHAR(3) NOT NULL DEFAULT '0' AFTER `MOTIFMEDICALSANTE`,
  ADD COLUMN `MOTIFMEDICALAIDANT` VARCHAR(3) NOT NULL DEFAULT '0' AFTER `MOTIFMEDICALGROSSESSE`;

 CREATE TABLE `TTEXCEPTION` (
  `AGENTID` VARCHAR(10) NOT NULL,
  `DATEORIGINE` DATE NOT NULL,
  `MOMENTORIGINE` VARCHAR(2) NOT NULL,
  `DATEREMPLACEMENT` DATE NULL,
  `MOMENTREMPLACEMENT` VARCHAR(2) NULL,
  PRIMARY KEY (`AGENTID`, `DATEORIGINE`, `MOMENTORIGINE`));
ALTER TABLE `TTEXCEPTION` 
  ADD INDEX `REPLACE_INDX` (`DATEREMPLACEMENT` ASC, `MOMENTREMPLACEMENT` ASC);

ALTER TABLE `TELETRAVAIL` 
  ADD COLUMN `ACTIVITETELETRAVAIL` VARCHAR(1000) NOT NULL DEFAULT '' AFTER `MOTIFMEDICALAIDANT`,
  ADD COLUMN `PERIODEEXCLUSION` VARCHAR(300) NOT NULL DEFAULT '' AFTER `ACTIVITETELETRAVAIL`,
  ADD COLUMN `PERIODEADAPTATION` VARCHAR(100) NOT NULL DEFAULT '' AFTER `PERIODEEXCLUSION`,
  ADD COLUMN `STATUTRESPONSABLE` VARCHAR(10) NOT NULL DEFAULT '' AFTER `PERIODEADAPTATION`;

ALTER TABLE TELETRAVAIL
ADD COLUMN `CREATIONG2T` DATE NULL DEFAULT NULL AFTER `AGENTID`,
ADD COLUMN `CREATIONESIGNATURE` DATE 19000101 DEFAULT NULL AFTER `CREATIONG2T`;

 * 
 */

class teletravail
{
    public const OLD_STATUT_ACTIVE = "Active";
    public const OLD_STATUT_INACTIVE = "Inactive";
    public const TELETRAVAIL_VALIDE = "v";
    public const TELETRAVAIL_REFUSE = "r";
    public const TELETRAVAIL_ATTENTE = "a";
    public const TELETRAVAIL_ANNULE = "x";
    
    
    public const TYPE_CONVENTION_INITIALE = "Demande initiale";
    public const TYPE_CONVENTION_RENOUVELLEMENT = "Demande de renouvellement";
    public const TYPE_CONVENTION_MEDICAL = "Demande ou renouvellement sur prescription médicale";
    
    public const CODE_CONVENTION_INITIALE = "1";
    public const CODE_CONVENTION_RENOUVELLEMENT = "2";
    public const CODE_CONVENTION_MEDICAL = "3";

    public const MOTIF_MEDICAL_SANTE = 0;
    public const MOTIF_MEDICAL_GROSSESSE = 1;
    public const MOTIF_MEDICAL_AIDANT = 2;
    
    private $teletravailid = null;
    private $agentid = null;
    private $datedebut = null;
    private $datefin = null;
    private $tabteletravail = null;
    private $statut = null;
    private $typeconvention = null;
    private $esignatureid = null;
    private $esignatureurl = null;
    private $commentaire = null;
    private $motifmedicalsante = null;
    private $motifmedicalgrossesse = null;
    private $motifmedicalaidant = null;
    private $activiteteletravail = null;
    private $periodeexclusion = null;
    private $periodeadaptation = null;
    private $statutresponsable = null;
    private $creationg2t = null;
    private $creationesignature = '19000101';
    
    private $dbconnect = null;
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
            $errlog = "Teletravail->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }
    
    function load($teletravailid)
    {
        $sql = "SELECT TELETRAVAILID,
                       AGENTID, 
                       DATEDEBUT, 
                       DATEFIN, 
                       TABTELETRAVAIL, 
                       STATUT, 
                       TYPECONVENTION, 
                       ESIGNATUREID, 
                       ESIGNATUREURL, 
                       COMMENTAIRE, 
                       MOTIFMEDICALSANTE, 
                       MOTIFMEDICALGROSSESSE, 
                       MOTIFMEDICALAIDANT,
                       ACTIVITETELETRAVAIL,
                       PERIODEEXCLUSION,
                       PERIODEADAPTATION,
                       STATUTRESPONSABLE,
                       CREATIONG2T,
                       CREATIONESIGNATURE
                FROM TELETRAVAIL
                WHERE TELETRAVAILID = ? ";
        $params = array($teletravailid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Teletravail->Load (TELETRAVAIL) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Teletravail->Load (TELETRAVAIL) : Teletravail $teletravailid non trouvé";
            //echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        $result = mysqli_fetch_row($query);
        $this->teletravailid = "$result[0]";
        $this->agentid = "$result[1]";
        $this->datedebut = "$result[2]";
        $this->datefin = "$result[3]";
        $this->tabteletravail = "$result[4]";
        $this->statut = "$result[5]";
        $this->typeconvention = $result[6] . '';
        $this->esignatureid = $result[7] . '';
        $this->esignatureurl = $result[8] . '';
        $this->commentaire = $result[9] . '';
        $this->motifmedicalsante = $result[10]  .'';
        $this->motifmedicalgrossesse = $result[11]  .'';
        $this->motifmedicalaidant = $result[12]  .'';
        $this->activiteteletravail = $result[13]  .'';
        $this->periodeexclusion = $result[14]  .'';
        $this->periodeadaptation = $result[15]  .'';
        $this->statutresponsable = $result[16]  .'';
        $this->creationg2t = $result[17]  .'';
        $this->creationesignature = $result[18]  .'';
        if ($this->creationesignature == '')
        {
            $this->creationesignature = '19000101';
        }
        
        if (trim($this->esignatureurl.'')!='')
        {
            // On remplace éventuellement le nom du serveur par celui paramétré
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (load) => Avant transformation l'URL est : " . $this->esignatureurl));
            $eSignature_url = $this->fonctions->liredbconstante('ESIGNATUREURL');
            $urlpath = parse_url($this->esignatureurl,PHP_URL_PATH);
            // $this->esignatureurl = $eSignature_url . $urlpath;
            $this->esignatureurl = preg_replace('/([^:])(\/{2,})/', '$1/', $eSignature_url . $urlpath);
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (load) => Après transformation l'URL est : " . $this->esignatureurl));
        }
        return true;
    }
    
    function loadbyesignatureid($esignatureid)
    {
        $sql = "SELECT TELETRAVAILID FROM TELETRAVAIL WHERE ESIGNATUREID = ? ";
        $params = array($esignatureid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Teletravail->loadbyesignatureid (TELETRAVAIL) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Teletravail->loadbyesignatureid (TELETRAVAIL) : Teletravail $esignatureid non trouvé";
            //echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        $result = mysqli_fetch_row($query);
        $teletravailid = $result[0];
        return $this->load($teletravailid);
    }
    

    function teletravailid()
    {
        return $this->teletravailid;
    }
    
    function creationg2t()
    {
        return $this->creationg2t . "";
    }
    
    function creationesignature($datecreation = null)
    {
        if (is_null($datecreation)) 
        {
            return $this->creationesignature;
        }
        else
        {
            $this->creationesignature = $this->fonctions->formatdatedb($datecreation);
        }
    }
    
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->agentid)) {
                $errlog = "teletravail->agentid : Le numéro agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->agentid;
        }
        else
        {
            if (!is_null($this->agentid))
            {
                $errlog = "teletravail->agentid : Impossible de modifier le numéro de l'agent !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->agentid = $agentid;
        }
    }
    
    function datedebut($datedebut = null)
    {
        if (is_null($datedebut)) {
            if (is_null($this->datedebut)) {
                $errlog = "teletravail->datedebut : La valeur de la date de début n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->datedebut;
        }
        else
        {
            $this->datedebut = $this->fonctions->formatdatedb($datedebut);
        }
    }
    
    function datefin($datefin = null)
    {
        if (is_null($datefin)) {
            if (is_null($this->datefin)) {
                $errlog = "teletravail->datefin : La valeur de la date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->datefin;
        }
        else
        {
            $this->datefin = $this->fonctions->formatdatedb($datefin);
        }
    }
    
    
    function typeconvention($typeconvention = null)
    {
        if (is_null($typeconvention)) {
            if (is_null($this->typeconvention)) {
                $errlog = "teletravail->typeconvention : La valeur de typeconvention n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->typeconvention;
        }
        else
        {
            $this->typeconvention = $typeconvention;
        }
    }
    
    function libelletypeconvention($codetypeconvention = null)
    {
        if (is_null($codetypeconvention))
        {
            $codetypeconvention = $this->typeconvention;
        }
        
        if (!preg_match ("/^[0-9]+/", $codetypeconvention))
        {
            // Pas de chiffres => On retourne le texte
            if ($codetypeconvention.''=='')
            {
                return 'Type inconnu';
            }
            else
            {
                return $codetypeconvention;
            }
        }
        
        $libelleconvention = "";
        switch ($codetypeconvention)
        {
            case teletravail::CODE_CONVENTION_INITIALE :
                $libelleconvention = teletravail::TYPE_CONVENTION_INITIALE;
                break;
            case teletravail::CODE_CONVENTION_RENOUVELLEMENT :
                $libelleconvention = teletravail::TYPE_CONVENTION_RENOUVELLEMENT;
                break;
            case teletravail::CODE_CONVENTION_MEDICAL :
                $libelleconvention = teletravail::TYPE_CONVENTION_MEDICAL;
                break;
            default :
                $libelleconvention = "Type inconnu";
                break;
        }
        return $libelleconvention;
    }
    
    function esignatureid($esignatureid = null)
    {
        if (is_null($esignatureid)) {
            if (is_null($this->esignatureid)) {
                $errlog = "teletravail->esignatureid : La valeur de esignatureid n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->esignatureid;
        }
        else
        {
            $this->esignatureid = $esignatureid;
        }
    }
    
    function esignatureurl($esignatureurl = null)
    {
        if (is_null($esignatureurl)) {
            if (is_null($this->esignatureurl)) {
                $errlog = "teletravail->esignatureurl : La valeur de esignatureurl n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->esignatureurl;
            }
        }
        else
        {
            if ($this->esignatureurl . '' != '')
            {
                $errlog = "teletravail->esignatureurl : Impossible de modifier l'URL eSignature !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                if (trim($esignatureurl)!='')
                {
                    // On remplace éventuellement le nom du serveur par celui paramétré
                    //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (esignatureurl) => Avant transformation l'URL est : " . $esignatureurl));
                    $eSignature_url = trim($this->fonctions->liredbconstante('ESIGNATUREURL'));
                    $urlpath = parse_url($esignatureurl,PHP_URL_PATH);
                    $this->esignatureurl = preg_replace('/([^:])(\/{2,})/', '$1/', $eSignature_url . $urlpath);
                    //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (esignatureurl) => Après transformation l'URL est : " . $this->esignatureurl));
                }
                else
                {
                    $this->esignatureurl = trim($esignatureurl);
                }
            }
        }
    }
    
    function commentaire($commentaire = null)
    {
        if (is_null($commentaire)) {
            if (is_null($this->commentaire)) {
                $errlog = "teletravail->commentaire : La valeur du commentaire n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->commentaire;
        }
        else
        {
            $this->commentaire = $commentaire;
        }
    }
    
    function motifmedicalsante($motifmedicalsante = null)
    {
        if (is_null($motifmedicalsante)) {
            if (is_null($this->motifmedicalsante)) {
                $errlog = "teletravail->motifmedicalsantee : La valeur du motifmedicalsante n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                return intval('0'.$this->motifmedicalsante);
            }
        }
        else
        {
            $this->motifmedicalsante = $motifmedicalsante;
        }
    }

    function motifmedicalgrossesse($motifmedicalgrossesse = null)
    {
        if (is_null($motifmedicalgrossesse)) {
            if (is_null($this->motifmedicalgrossesse)) {
                $errlog = "teletravail->motifmedicalgrossesse : La valeur du motifmedicalgrossesse n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                return intval('0'.$this->motifmedicalgrossesse);
            }
        }
        else
        {
            $this->motifmedicalgrossesse = $motifmedicalgrossesse;
        }
    }

    function motifmedicalaidant($motifmedicalaidant = null)
    {
        if (is_null($motifmedicalaidant)) {
            if (is_null($this->motifmedicalaidant)) {
                $errlog = "teletravail->motifmedicalaidant : La valeur du motifmedicalaidant n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                return intval('0'.$this->motifmedicalaidant);
            }
        }
        else
        {
            $this->motifmedicalaidant = $motifmedicalaidant;
        }
    }
    
    function tabteletravail($tableau = null)
    {
        if (is_null($tableau)) {
            if (is_null($this->tabteletravail)) {
                $errlog = "teletravail->tabteletravail : Le tableau du teletravail n'est pas défini (NULL) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->tabteletravail;
        } 
        elseif (strlen($tableau) <> 14)
        {
            $errlog = "teletravail->tabteletravail : Le tableau du teletravail n'est pas au bon format (Pas 14 caractères) !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        else
        {
            $this->tabteletravail = $tableau;
        }
    }
    
    function libelletabteletravail()
    {
        $htmltext = '';
        $somme = 0;
        for ($index = 0 ; $index < strlen($this->tabteletravail()) ; $index ++)
        {
            $demijrs = substr($this->tabteletravail(),$index,1);
            if ($demijrs>0) // Si dans le tableau la valeur est > 0
            {
                if (($index % 2) == 0)  // Si c'est le matin => On ajoute 1 à la somme
                {
                    $somme = $somme + 1;
                }
                elseif (($index % 2) == 1)  // Si c'est l'après-midi => On ajoute 2 à la somme
                {
                    $somme = $somme + 2;
                }
            }
            if (($index % 2) == 1)
            {
                if ($somme > 0) // Si pas de télétravail => On affiche rien
                {
                    if ($somme == 1)  // Que le matin
                    {
                       $htmltext = $htmltext . $this->fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $this->fonctions->nommoment(fonctions::MOMENT_MATIN); // => intdiv($index,2)+1 car pour PHP 0 = dimanche et nous 0 = lundi
                    }
                    elseif ($somme == 2) // Que l'après-midi
                    {
                       $htmltext = $htmltext . $this->fonctions->nomjourparindex(intdiv($index,2)+1) . " " . $this->fonctions->nommoment(fonctions::MOMENT_APRESMIDI);
                    }
                    elseif ($somme == 3) // Toute la journée
                    {
                       $htmltext = $htmltext . $this->fonctions->nomjourparindex(intdiv($index,2)+1);
                    }
                    else // Là, on ne sait pas !!
                    {
                       $htmltext = $htmltext . "Problème => index = $index  demijrs = $demijrs   somme = $somme";
                    }
                    $htmltext = $htmltext . ", ";
                }
                $somme = 0;
            }
        }
        // On enlève le ', ' en fin de chaine
        return substr($htmltext, 0, strlen($htmltext)-2);
    }
    
    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "teletravail->statut : La valeur du statut n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->statut;
        }
        else
        {
            $this->statut = $statut;
        }
    }
    
    function activiteteletravail($activiteteletravail = null)
    {
        if (is_null($activiteteletravail)) 
        {
            return ($this->activiteteletravail . "");
        }
        else
        {
            $this->activiteteletravail = $activiteteletravail;
        }
    }

    function periodeexclusion($periodeexclusion = null)
    {
        if (is_null($periodeexclusion)) 
        {
            return ($this->periodeexclusion . "");
        }
        else
        {
            $this->periodeexclusion = $periodeexclusion;
        }
    }

    function periodeadaptation($periodeadaptation = null)
    {
        if (is_null($periodeadaptation)) 
        {
            return ($this->periodeadaptation . "");
        }
        else
        {
            $this->periodeadaptation = $periodeadaptation;
        }
    }

    function statutresponsable($statutresponsable = null)
    {
        if (is_null($statutresponsable)) 
        {
            return $this->statutresponsable;
        }
        else
        {
            $this->statutresponsable = $statutresponsable;
        }
    }
    
    
    function store()
    {
        $erreur = '';
        // Si on est en train de créer un teletravail
        if (is_null($this->teletravailid))
        {
            // On doit vérifier que les éléments olbigatoires sont bien renseignés : agentid, tabteletravail, datefin, datedebut
            if (is_null($this->agentid)
                or is_null($this->tabteletravail)
                or is_null($this->datefin)
                or is_null($this->datedebut))
            {
                // Au moins un des éléments obligatoires est null => pas de sauvegarde possible
                $erreur = "Au moins un des éléments obligatoires est null => Pas de création possible";
                $errlog = "teletravail->Store : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $erreur;
            }

            if (is_null($this->statut))
            {
                $this->statut = teletravail::TELETRAVAIL_ATTENTE;
            }
            $this->creationg2t = date('Ymd');
            
            /*
            if (is_null($this->statutresponsable))
            {
                $this->statutresponsable = teletravail::TELETRAVAIL_ATTENTE;
            }
            */
            //var_dump($this->motifmedicalsante);
            //var_dump($this->motifmedicalgrossesse);
            //var_dump($this->motifmedicalaidant);
            
            $sql = "LOCK TABLES TELETRAVAIL WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO TELETRAVAIL(AGENTID,
                                            CREATIONG2T,
                                            CREATIONESIGNATURE,
                                            DATEDEBUT,
                                            DATEFIN,
                                            TABTELETRAVAIL,
                                            STATUT,
                                            TYPECONVENTION,
                                            ESIGNATUREID,
                                            ESIGNATUREURL,
                                            COMMENTAIRE,
                                            MOTIFMEDICALSANTE,
                                            MOTIFMEDICALGROSSESSE,
                                            MOTIFMEDICALAIDANT,
                                            ACTIVITETELETRAVAIL,
                                            PERIODEEXCLUSION,
                                            PERIODEADAPTATION,
                                            STATUTRESPONSABLE)
                       VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array($this->agentid, 
                            $this->fonctions->formatdatedb($this->creationg2t),
                            $this->fonctions->formatdatedb($this->creationesignature . ""),
                            $this->fonctions->formatdatedb($this->datedebut), 
                            $this->fonctions->formatdatedb($this->datefin), 
                            $this->tabteletravail,
                            $this->statut,
                            $this->typeconvention,
                            $this->esignatureid,
                            $this->esignatureurl,
                            $this->commentaire,
                            $this->motifmedicalsante,
                            $this->motifmedicalgrossesse,
                            $this->motifmedicalaidant,
                            $this->activiteteletravail . "",
                            $this->periodeexclusion . "",
                            $this->periodeadaptation . "",
                            $this->statutresponsable
                );

/*
                    VALUES('" . $this->agentid  ."',
                           '" . $this->fonctions->formatdatedb($this->datedebut)  ."',
                           '" . $this->fonctions->formatdatedb($this->datefin)  ."',
                           '" . $this->tabteletravail  ."',
                           '" . $this->statut . "',
                           '" . $this->typeconvention . "',
                           '" . $this->esignatureid . "',
                           '" . $this->esignatureurl . "',
                           '" . $this->commentaire . "')";
            $params = array();
*/
            //echo "SQL teletravail->Store (NEW) : $sql <br>";
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "teletravail->Store (INSERT) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $this->teletravailid = mysqli_insert_id($this->dbconnect);
            $sql = "COMMIT";
            mysqli_query($this->dbconnect, $sql);
            $sql = "UNLOCK TABLES";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 1";
            mysqli_query($this->dbconnect, $sql);
        }
        // Sinon on modifie un enregistrement
        else
        {
            $sql = "UPDATE TELETRAVAIL
                    SET DATEDEBUT = ?,
                        DATEFIN = ?,
                        TABTELETRAVAIL = ?,
                        STATUT = ?,
                        ESIGNATUREID = ?,
                        ESIGNATUREURL = ?,
                        COMMENTAIRE = ?,
                        MOTIFMEDICALSANTE = ?,
                        MOTIFMEDICALGROSSESSE = ?,
                        MOTIFMEDICALAIDANT = ?,
                        ACTIVITETELETRAVAIL = ?,
                        PERIODEEXCLUSION = ?,
                        PERIODEADAPTATION = ?,
                        STATUTRESPONSABLE = ?,
                        CREATIONESIGNATURE = ?
                    WHERE TELETRAVAILID = ?";
            //echo "SQL teletravail->Store (UPDATE) : $sql <br>";
            $params = array($this->fonctions->formatdatedb($this->datedebut), 
                            $this->fonctions->formatdatedb($this->datefin), 
                            $this->tabteletravail, 
                            $this->statut, 
                            $this->esignatureid,
                            $this->esignatureurl,
                            $this->commentaire,
                            $this->motifmedicalsante,
                            $this->motifmedicalgrossesse,
                            $this->motifmedicalaidant,
                            $this->activiteteletravail . "",
                            $this->periodeexclusion . "",
                            $this->periodeadaptation . "",
                            $this->statutresponsable,
                            $this->fonctions->formatdatedb($this->creationesignature . ""),
                            $this->teletravailid
                );
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            
            if ($erreur != "") {
                $errlog = "teletravail->Store (UPDATE) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        return $erreur;
    }
    
    
    /**
     *
     * @param int $numerojour numéro du jour (0 = dimanche, 5 = samedi, 6 = dimanche)
     * @param string $moment optionnel m = matin, a = après-midi
     * @return bool vrai si le jour/moment est télétravaillé
     */
    
    function estjourteletravaille($numerojour, $moment = null)
    {
        if ($this->statut != teletravail::TELETRAVAIL_VALIDE) // Si la convention de teletravail n'est plus active ==> Forcément ce n'est pas télétravaillé
        {
            return false;
        }
        elseif (is_null($this->tabteletravail)) {
            $errlog = "teletravail->estjourteletravaille : Le tableau du teletravail n'est pas défini (NULL) !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        else
        {
            if ($numerojour == 0)   // Lundi = 1, Mardi = 2, ..... Dimanche = 0
                $numerojour = 6;
            else
                $numerojour = $numerojour - 1;
            
            // $numerojour => Représente maintenant le numéro de l'index du jour dans le tableau
            if (is_null($moment))
            {
                return ($this->tabteletravail[($numerojour*2)] + $this->tabteletravail[(($numerojour*2)+1)] == 2);
            }
            elseif ($moment == fonctions::MOMENT_MATIN)
            {
                return ($this->tabteletravail[($numerojour*2)] == 1);
            }
            elseif ($moment == fonctions::MOMENT_APRESMIDI)
            {
                return ($this->tabteletravail[(($numerojour*2)+1)] == 1);
            }
            else
            {
                $errlog = "teletravail->estjourteletravaille : Le moment n'est pas connu (moment = $moment) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return false;
            }
        }
    }
    
    
    
    function estteletravaille($date, $moment = null)
    {
        if ($this->statut != teletravail::TELETRAVAIL_VALIDE) // Si la convention de teletravail n'est plus active ==> Forcément ce n'est pas télétravaillé
        {
            return false;
        }
        elseif (is_null($this->tabteletravail)) {
            $errlog = "teletravail->estteletravaille : Le tableau du teletravail n'est pas défini (NULL) !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        else
        {
            $date = $this->fonctions->formatdatedb($date);
            // Si la date est en dehors de la période télétravaillée => on retourne false
            if ($date < $this->fonctions->formatdatedb($this->datedebut()) or $date > $this->fonctions->formatdatedb($this->datefin()))
            {
                return false;
            }
            $numerojour = date("w", strtotime("$date"));
/*            
            if ($numerojour == 0)   // Lundi = 1, Mardi = 2, ..... Dimanche = 0
                $numerojour = 6;
            else
                $numerojour = $numerojour - 1;
            // $numerojour => Représente maintenant le numéro de l'index du jour dans le tableau
*/            
            return $this->estjourteletravaille($numerojour,$moment);
        }
    }
    
     function datetheorique($datedebut, $datefin)
     {
         $datetheorique = array();

         $date = $this->fonctions->formatdatedb($datedebut);
         $datefin = $this->fonctions->formatdatedb($datefin);
         while ($date<=$datefin)
         {
             $moment = fonctions::MOMENT_MATIN;
             if ($this->estteletravaille($date,"$moment"))
             {
                 $datetheorique[$date . $moment] = array($date, $moment, $this->typeconvention());
             }
             $moment = fonctions::MOMENT_APRESMIDI;
             if ($this->estteletravaille($date,"$moment"))
             {
                 $datetheorique[$date . $moment] = array($date, $moment, $this->typeconvention());
             }
             $timestamp = strtotime($date);
             $date = date("Ymd", strtotime("+1 day", $timestamp)); // On passe au lendemain
         }
         return $datetheorique;
     }
     
     function storepdf()
     {
         error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On va demander le PDF à eSignature (convetion = " .  $this->esignatureid .  ")"));
         
         $eSignature_url = $this->fonctions->liredbconstante('ESIGNATUREURL');
         $error = '';
         
         // On appelle le WS eSignature pour récupérer le document final
         $curl = curl_init();
         $opts = [
             CURLOPT_URL => $eSignature_url . '/ws/signrequests/get-last-file/' . $this->esignatureid,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_PROXY => ''
         ];
         curl_setopt_array($curl, $opts);
         curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
         $pdf = curl_exec($curl);
         $error = curl_error ($curl);
         curl_close($curl);
         if ($error != "")
         {
             $error = "Erreur Curl (récup PDF) =>  " . $error;
             error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
             return $error;
             //echo "Erreur Curl (récup PDF) =>  " . $error . '<br><br>';
         }
         if (stristr(substr($pdf,0,10),'PDF') === false)
         {
             $error = "Erreur Curl (récup PDF) =>  " . $error;
             error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
             return $error;
         }
         //echo "<br>" . print_r($json,true) . "<br>";
         //$response = json_decode($json, true);
         
         $agent = new agent($this->dbconnect);
         $agent->load($this->agentid());
         $basename = str_replace(' ', '_', "Convention_Teletravail_" . $agent->nom() . "_" . $agent->prenom() . "_num_" . $this->esignatureid . ".pdf");
         $pdffilename = $this->fonctions->pdfpath() . '/teletravail/conventions/' . $basename;
         //echo "<br>pdffilename = $pdffilename <br><br>";
         
         // création du fichier
         //$pdffilename = '/tmp/mon_fichier_test.pdf';
         $path = dirname("$pdffilename");
         if (!file_exists($path))
         {
             mkdir("$path");
             chmod("$path", 0777);
         }
         
         $f = fopen($pdffilename, "w");
         if ($f === false)
         {
             $error = "Erreur enregistrement : Le fichier $pdffilename n'a pas pu être créé.";
             error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
             return $error;
         }
         // écriture
         fputs($f, $pdf );
         // fermeture
         fclose($f);
         error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le PDF est ok (demande = " .  $this->esignatureid .  ")"));
         return '';
         
     }
}

?>