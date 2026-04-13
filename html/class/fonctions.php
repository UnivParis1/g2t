<?php

class ttexception
{
    public const STATUT_VALIDE = 'v';
    public const STATUT_ENATTENTE = 'a';
    public const STATUT_REFUSE = 'r';

    public const ACTION_DESACTIVE = 'desactive';
    public const ACTION_REACTIVE = 'reactive';
    public const ACTION_ANNULE = 'annule';
    public const ACTION_SUPPRIME = 'supprime';
    public const ACTION_VALIDE = 'valide';
    public const ACTION_REFUSE = 'refuse';
    public const ACTION_DEPLACEMENT = 'deplacement';

    public $agentid = null;
    public $dateorigine = null;
    public $momentorigine = null;
    public $dateremplacement = null;
    public $momentremplacement = null;
    public $statut = null;
    public $motif = '';

    
    public function id() :string
    {
        global $fonctions;

        $idelement = $fonctions->formatdatedb($this->dateorigine);
        switch ($this->momentorigine)
        {
            case fonctions::MOMENT_MATIN :
                $idelement = $idelement . '1';
                break;
            case fonctions::MOMENT_APRESMIDI :
                $idelement = $idelement . '2';
                break;
            default :
                $idelement = $idelement . '0';
                break;
        }
        return $idelement;
    }

    public function infosfromid($id) :bool // l'Id a forcément un format YYYYMMDD + N° moment
    {
        global $fonctions;

        if (strlen($id . '') != 9)
        {
            $errlog = "ttexception::infosfromid : Le format de l'Id n'est pas correct => id = $id";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            return false;
        }
        $datedb = substr($id,0,8);
        $idmoment = substr($id,-1,1);
        $this->dateorigine = $fonctions->formatdatedb($datedb);
        if (is_null($this->dateorigine))
        {
            $errlog = "ttexception::infosfromid : La date n'est pas correcte => id = $id";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
            return false;
        }
        switch ($idmoment)
        {
            case 0 :
                $this->momentorigine = "";
                break;
            case 1 :
                $this->momentorigine = fonctions::MOMENT_MATIN;
                break;
            case 2 :
                $this->momentorigine = fonctions::MOMENT_APRESMIDI;
                break;
            default :
                $errlog = "ttexception::infosfromid : La date n'est pas correcte => id = $id";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                return false;
        }
        return true;
    }
}

/**
 * Fonctions
 * Library of usefull functions
 *
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class fonctions
{

    public const SIGNATAIRE_AGENT = "1";
    public const SIGNATAIRE_STRUCTURE = "2";
    public const SIGNATAIRE_RESPONSABLE = "3";
    public const SIGNATAIRE_SPECIAL = "4";
    public const SIGNATAIRE_RESPONSABLE_N2 = "5";
    //public const SIGNATAIRE_RESP_BRANCHE = "6";
    public const SIGNATAIRE_LIBELLE = array(fonctions::SIGNATAIRE_AGENT => "AGENT INDIVIDUEL", 
                                            fonctions::SIGNATAIRE_STRUCTURE => "TOUS LES AGENTS D'UNE STRUCTURE", 
                                            fonctions::SIGNATAIRE_RESPONSABLE => "RESPONSABLE DE STRUCTURE", 
                                            fonctions::SIGNATAIRE_SPECIAL => "UTILISATEUR SPECIAL", 
                                            fonctions::SIGNATAIRE_RESPONSABLE_N2 => "RESPONSABLE N+2",
                                            //fonctions::SIGNATAIRE_RESP_BRANCHE => "RESPONSABLE DE LA BRANCHE DE L'AGENT"
                                           );

    public const MSGERROR = 'error';
    public const MSGWARNING = 'warning';
    public const MSGINFO = 'info';

    public const MOMENT_MATIN = 'm';
    public const MOMENT_APRESMIDI = 'a';

    private $dbconnect = null;

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
            $errlog = "Fonctions->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
    }

    /**
     *
     * @param string $date La date à convertir 
     * @return string|null La date convertie au format YYYYMMDD si elle est valide ou null sinon
     */
    public function formatdatedb($date)
    {
        $tempdate = null;
        $jour = null;
        $mois = null;
        $annee = null;
        if (is_null($date)) 
        {
            $errlog = "Fonctions->formatdatedb : La date est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } 
        else 
        {
            // Si la date est au format JJ + séparateur non numérique + MM + séparateur non numérique + AAAA
            // => 15-03-2024 ou 15.03.2024 ou 15/03/2024 : Ok
            // => 1510382024 => KO
            if (preg_match("`^([0-9]{2})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{4})$`", $date)==1)
            {
                $jour = substr($date, 0, 2);
                $mois = substr($date, 3, 2);
                $annee = substr($date, 6, 4);
            } 
            // Si la date est au format AAAA + séparateur non numérique + MM + séparateur non numérique + JJ
            // => 2024-03-15 ou 2024.03.15 ou 2024/03/15 : Ok
            // => 2024103815 => KO
            elseif (preg_match("`^([0-9]{4})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{2})$`", $date)==1)
            {
                $annee = substr($date, 0, 4);
                $mois = substr($date, 5, 2);
                $jour = substr($date, 8, 2);
            }
            // Si la date est une série de 8 chiffres (sans doute au format AAAAMMJJ)
            elseif (preg_match("`^([0-9]{4})([0-9]{2})([0-9]{2})$`", $date)==1)
            {
                $annee = substr($date, 0, 4);
                $mois = substr($date, 4, 2);
                $jour = substr($date, 6, 2);
            }
            // Le format de la date n'est pas reconnu
            else 
            {
                $errlog = "Fonctions->formatdatedb : Le format de la date est inconnu [Date=$date] !!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            // Si la date convertie a un format connu (=> $jour != null), on vérifie que c'est une date valide
            if (!is_null($jour))
            {
                if (checkdate($mois,$jour,$annee))
                {
                    $tempdate = $annee . $mois . $jour;
                }
                else
                {
                    $errlog = "Fonctions->formatdatedb : La date convertie n'est pas valide [Date=$date / date convertie=" . $annee . $mois . $jour . "] !!";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
                }
            }
        }
        return $tempdate;
    }

    /**
     *
     * @param string $date La date à convertir
     * @return string|null La date convertie au format DD/MM/YYYY si elle est valide ou null sinon
     */
    public function formatdate($date)
    {
        $tempdate = null;
        $jour = null;
        $mois = null;
        $annee = null;
        if (is_null($date)) 
        {
            $errlog = "Fonctions->formatdate : La date est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } 
        else
        {
            // Si la date contient des HH:MM:SS ou des HH:MM => On supprime la partie horaire de la date
            if (preg_match("` ([0-9]{2})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{2})$`", $date)==1
             or preg_match("` ([0-9]{2})([^0-9]{1})([0-9]{2})$`", $date)==1)
            {
                $date = explode(' ', $date)[0];
            }

            // Si la date est une série de 8 chiffres (sans doute au format AAAAMMJJ)
            if (preg_match("`^([0-9]{4})([0-9]{2})([0-9]{2})$`", $date)==1)
            {
                $jour = substr($date, 6, 2) ;
                $mois = substr($date, 4, 2);
                $annee = substr($date, 0, 4);
            } 
            // Si la date est au format AAAA + un séparateur non numérique + MM + un séparateur non numérique + JJ
            // => 2024-03-15 ou 2024.03.15 ou 2024/03/15 : Ok
            // => 2024103815 => KO
            elseif (preg_match("`^([0-9]{4})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{2})$`", $date)==1)
            {
                $jour = substr($date, 8);
                $mois = substr($date, 5, 2);
                $annee = substr($date, 0, 4);
            }
            // Si la date est au format JJ + séparateur non numérique + MM + séparateur non numérique + AAAA
            elseif (preg_match("`^([0-9]{2})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{4})$`", $date)==1)
            {
                $jour = substr($date, 0, 2);
                $mois = substr($date, 3, 2);
                $annee = substr($date, 6, 4);
            } 
            // Le format de la date n'est pas reconnu
            else 
            {
                $errlog = "Fonctions->formatdate : Le format de la date est inconnu [Date=$date] !!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            // Si la date convertie a un format connu (=> $jour != null), on vérifie que c'est une date valide
            if (!is_null($jour))
            {
                if (checkdate($mois,$jour,$annee))
                {
                    $tempdate = $jour . "/" . $mois . "/" . $annee;
                }
                else
                {
                    $errlog = "Fonctions->formatdate : La date convertie n'est pas valide [Date=$date / date convertie=" . $jour . "/" . $mois . "/" . $annee . "] !!";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
                }
            }
        }
        return $tempdate;
    }

    /**
     *
     * @param string $anneeref (optionel) année de référence pour les jours fériés. Si null, l'ensemble des jours fériés est renvoyé.
     * @param int $amplitude nombre d'années à prendre en plus de l'année de référence (par défaut 1)
     * @return array liste des jours fériés répondant aux paramètres. Attention : C'est la clé du tableau qui contient la date du jour fériés.
     */
    public function joursferies($anneeref = null, $amplitude = 1)
    {
        static $tabferies = array();

        if (count($tabferies)==0)
        {
            // Chargement des jours fériés
            //echo "Je charge le tableau des jours féries <br>";
            $dbconstante='FERIE%';
            if ($this->testexistdbconstante($dbconstante))
            {
                $jrs_feries_liste = $this->liredbconstante($dbconstante);
                //var_dump($jrs_feries_liste);
                foreach ($jrs_feries_liste as $key => $liste)
                {
                    $annee = trim(str_replace('FERIE',"",$key));
                    // ATTENTION : explode génère un tableau avec comme clé un entier (<=> l'index)
                    // C'est pour cela qu'on ne peut pas concaténer les tableaux des années avec un array_merge
                    // car les index numériques d'un tableau sont renumérotés dans un array_merge => Utilisation de l'opérateur "+" pour la concaténation
                    $tmparray = explode(";", $liste);
                    // On inverse les clés et les valeurs => Recherche sur clé plus rapide
                    $tabferies[$annee] = array_flip($tmparray);
                }
                //echo "Le tableau des jours fériés est : <br>"; print_r($tabferies) ; echo "<br>";
            }
        }
        $tabferiesfinal = array();
        foreach($tabferies as $annee => $tabjrs)
        {
            //if (is_null($anneeref) or ($annee>=($anneeref-1) and $annee<=($anneeref+$amplitude)))
            if (is_null($anneeref) or ($annee>=$anneeref and $annee<=($anneeref+$amplitude)))
            {
                // Ne pas utiliser array_merge car cette fonction ne conserve pas les clés
                // On fait donc une concaténation de tableaux avec un opérateur +
                $tabferiesfinal = $tabferiesfinal + $tabjrs;
            }
        }
        return $tabferiesfinal;
    }

    /**
     *
     * @param string $date
     *            date
     * @return string the (french) month name corresponding to the date
     */
    public function nommois($date = null)
    {
        if (is_null($date))
        {
            $date = date("d/m/Y");
        }
        $nummonth = date("n", strtotime($this->formatdatedb($date)));
        $monthname = $this->nommoisparindex($nummonth);
        if (mb_detect_encoding(ucfirst($monthname), 'UTF-8', true)) 
        {
            return ucfirst($monthname);
        }
        else
        {
            return $this->utf8_encode(ucfirst($monthname));
        }
    }

    /**
     *
     * @param string $index
     *            index of the day (1=Monday 7=Sunday)
     * @return string the (french) day name corresponding to the index
     */
    public function nommoisparindex($index = null) // 1 = Janvier 12 = Décembre
    {
        if (is_null($index)) 
        {
            $errlog = "Fonctions->nommoisparindex : L'index du mois est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } 
        else 
        {
            $index = $index % 12;
            if ($index==0) 
            {
                $index = 12;
            }
            switch ($index)
            {
                case 1:
                    $monthname = "janvier";
                    break;
                case 2:
                    $monthname = "février";
                    break;
                case 3:
                    $monthname = "mars";
                    break;
                case 4:
                    $monthname = "avril";
                    break;
                case 5:
                    $monthname = "mai";
                    break;
                case 6:
                    $monthname = "juin";
                    break;
                case 7:
                    $monthname = "juillet";
                    break;
                case 8:
                    $monthname = "août";
                    break;
                case 9:
                    $monthname = "septembre";
                    break;
                case 10:
                    $monthname = "octobre";
                    break;
                case 11:
                    $monthname = "novembre";
                    break;
                case 12:
                    $monthname = "décembre";
                    break;
            }

            if (mb_detect_encoding(ucfirst($monthname), 'UTF-8', true)) 
            {
                return ucfirst($monthname);
            } 
            else 
            {
                return $this->utf8_encode(ucfirst($monthname));
            }
        }
    }

    /**
     *
     * @param string $date
     *            date
     * @return string the (french) day name corresponding to the date
     */
    public function nomjour($date = null, $length = null)
    {
        if (is_null($date))
        {
            $date = date("d/m/Y");
        }

        $numday = date("w", strtotime($this->formatdatedb($date)));
        $dayname = $this->nomjourparindex($numday, $length);
        if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) 
        {
            return ucfirst($dayname);
        } 
        else 
        {
            return $this->utf8_encode(ucfirst($dayname));
        }
    }

    /**
     *
     * @param string $index
     *            index of the day (1=Monday 7=Sunday)
     * @return string the (french) day name corresponding to the index
     */
    public function nomjourparindex($index = null, $length = null) // 1 = Lundi 7 = Dimanche
    {
        if (is_null($index)) 
        {
            $errlog = "Fonctions->nomjourparindex : L'index du jour est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } 
        else 
        {
            $index = $index % 7;
            switch ($index)
            {
                case 0:
                    $dayname = "Dimanche";
                    break;
                case 1:
                    $dayname = "lundi";
                    break;
                case 2:
                    $dayname = "mardi";
                    break;
                case 3:
                    $dayname = "mercredi";
                    break;
                case 4:
                    $dayname = "jeudi";
                    break;
                case 5:
                    $dayname = "vendredi";
                    break;
                case 6:
                    $dayname = "samedi";
                    break;
            }
            if (!is_null($length) and $length<strlen($dayname) and $length>0)
            {
                $dayname = substr($dayname,0,$length) . ".";
            }
            if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) 
            {
                return ucfirst($dayname);
            } 
            else 
            {
                return $this->utf8_encode(ucfirst($dayname));
            }
        }
    }

    /**
     *
     * @param string $categorie
     *            optional category. default is NULL
     * @return string the list of absence for the given category (or all if not set)
     */
    public function listeabsence($categorie = null)
    {
        if (is_null($categorie))
        {
            $sql = "SELECT TA.TYPEABSENCEID,TA.LIBELLE FROM TYPEABSENCE TA, TYPEABSENCE TA2 WHERE TA2.ABSENCEIDPARENT='abs' AND TA.ABSENCEIDPARENT=TA2.TYPEABSENCEID ORDER BY TA.ABSENCEIDPARENT";
            $params = array();
        }
        else
        {
            $sql = "SELECT TYPEABSENCEID,LIBELLE FROM TYPEABSENCE WHERE ABSENCEIDPARENT= ? ORDER BY LIBELLE";
            $params = array($categorie);
        }
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->listeabsence : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->listeabsence : Pas de type d'absences défini dans la base";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) {
            if ($result[1] . "" != "")
                $listeabs[$result[0]] = $result[1];
        }

        // print_r ($listeabs) ; echo "<br>";
        return $listeabs;
    }

    /**
     *
     * @param
     * @return string the list of absence category
     */
    public function listecategorieabsence()
    {
        $sql = "SELECT TYPEABSENCEID,LIBELLE FROM TYPEABSENCE WHERE ANNEEREF='' AND ABSENCEIDPARENT='abs'";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->listecategorieabsence : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->listecategorieabsence : Pas de catégorie définie dans la base";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) {
            if ($result[0] . "" != "")
                $listecategabs[$result[0]] = $result[1];
        }
        return $listecategabs;
    }


    // ALTER TABLE TYPEABSENCE ADD COLUMN `COMMENTOBLIG` VARCHAR(2) NOT NULL DEFAULT 'n' AFTER `ABSENCEIDPARENT`;
    // UPDATE TYPEABSENCE SET `COMMENTOBLIG` = 'o' WHERE (`TYPEABSENCEID` = 'spec');
    // UPDATE TYPEABSENCE SET `COMMENTOBLIG` = 'o' WHERE (`TYPEABSENCEID` = 'teleetab');
    public function absencecommentaireoblig($typeabsence)
    {
        if ($typeabsence . "" == "")
        {
            return false;
        }
        $sql = "SELECT COMMENTOBLIG FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
        $params = array($typeabsence);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->absencecommentaireoblig : " . $erreur . " ==> On passe dans le test en dur";
            // echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            if ($typeabsence == 'spec' or $typeabsence == 'teleetab')
            {
                return true;
            }
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->absencecommentaireoblig : Type d'absence inconnu ($typeabsence).";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $result = mysqli_fetch_row($query);
        //echo " COMMENTOBLIG => " . $result[0] . "<br>";
        if (strcasecmp((string)$result[0] . "",'o')==0) // Si la colonne vaut 'o' ou 'O'
        {
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $date
     *            the date to check
     * @return boolean TRUE if the date is correct. FALSE otherwise
     */
    public function verifiedate($date)
    {
        if (is_null($date))
        {
            //echo "function verifiedate -> La date est null <br>";
            return FALSE;
        }
        // On vérifie avec une REGExp si le format de la date est valide DD/MM/YYYY
        // if (!ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})",$date))

        //if (! preg_match("`^([0-9]{2})\/([0-9]{2})\/([0-9]{4})`", $date))
        if (!preg_match("`^([0-9]{2})([^0-9]{1})([0-9]{2})([^0-9]{1})([0-9]{4})$`", $date))
        {
            //echo "function verifiedate -> Pas le bon format <br>";
            return FALSE;
        }
        $jour = substr($date, 0, 2);
        $mois = substr($date, 3, 2);
        $annee = substr($date, 6);
        if (strlen($annee) != 4)
        {
            //echo "function verifiedate -> L'annnée n'est pas sur 4 chiffres <br>";
            return FALSE;
        }
        //echo "jour = $jour mois = $mois annee = $annee <br>";
        //echo "function verifiedate -> On checkdate <br>";
        return checkdate($mois, $jour, $annee);
    }

    /**
     *
     * @param
     * @return string the beginning of the period in format DDMM (typicaly = 0901 - 1 sept)
     */
    public function debutperiode()
    {
        static $debutperiode = null;

        $dbconstante='DEBUTPERIODE';

        if (is_null($debutperiode))
        {
            if ($this->testexistdbconstante($dbconstante))
            {
                $debutperiode = $this->liredbconstante($dbconstante);
            }
            else
            {
                $debutperiode = "0901";
            }
        }
        return $debutperiode;
    }

    /**
     *
     * @param
     * @return string the end of the period in format MMDD (typicaly = 0831 - 31 aug)
     */
    public function finperiode()
    {
        static $finperiode = null;

        $dbconstante='FINPERIODE';

        if (is_null($finperiode))
        {
            if ($this->testexistdbconstante($dbconstante))
            {
                $finperiode = $this->liredbconstante($dbconstante);
            }
            else
            {
                $finperiode = "0831";
            }
        }
        return $finperiode;
    }

    public function margesynchro($marge = null)
    {
        static $interval = null;

        $dbconstante='INTERVALSYNCHRO';

        if (!is_null($marge))
        {
            $this->enregistredbconstante($dbconstante,$marge);
            $interval = $marge;
        }
        elseif (is_null($interval))
        {
            if ($this->testexistdbconstante($dbconstante))
            {
                $interval = $this->liredbconstante($dbconstante);
            }
            else
            {
                $interval = "2"; // Par défaut on synchronise 3 ans en arrière
            }
        }

        return $interval;
    }

    /**
     *
     * @param string $date
     *            optional date to determin the reference year. If not set the current date is used.
     * @return string the reference year for the given date (format YYYY)
     */
    public function anneeref($date = null)
    {
        static $anneeref_datenull = null;

        // Si on a déjà calculé l'année de ref pour une date null => On la retourne
        if (!is_null($anneeref_datenull) and is_null($date))
        {
            //echo "Ma date de ref est déjà calculée : $anneeref_datenull <br> \n";
            return $anneeref_datenull;
        }

        $dateestnull = false;
        // echo "La date = " . $date . "<br>";
        if (is_null($date))
        {
            $date = date("d/m/Y");
            $dateestnull = true;
        }
        else
        {
            //echo "Date avant formatdate : $date <br>";
            $date = $this->formatdate($date);
            //echo "Date après formatdate : $date <br>";
            if (!$this->verifiedate($date)) 
            {
                $errlog = "Fonctions->anneeref : La date " . $date . " est invalide !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
                return '';
            }
        }
        // echo "La date = " . $date . "<br>";
        $finperiode = $this->finperiode();
        // echo "Fin periode = $finperiode <br>";
        // echo "date(m, date(Y) . finperiode)= " .date("m", date("Y") . $finperiode) . "<br>";
        $date = $this->formatdatedb($date);
        $annee = substr($date, 0, 4);
        $mois = substr($date, 4, 2);
        // echo "annee = $annee mois = $mois <br>";
        if ($mois <= date("m", date("Y") . $finperiode))
        {
            $annee--;
        }
        else
        {
            // L'année est la bonne => Pas de modification
        }

        // Si la date est null et que l'anneeref_datenull est null aussi => On mémorise l'année de référence pour les appels ultérieurs
        if (is_null($anneeref_datenull) and $dateestnull)
        {
            //echo "Je mémorise l'année de ref $annee <br> \n"; 
            $anneeref_datenull = $annee;
        }
        return $annee;
    }

    /**
     *
     * @param string $typeconge
     *            the type of vacation to test
     * @return boolean True if the type is a vacation (not an absence). False otherwise
     */
    public function estunconge($typeconge)
    {

        // Cas particulier du CET ==> Il n'est pas annuel mais on doit gérer le compteur de jours restant...
        if (strcasecmp((string)$typeconge, 'cet') == 0)
        {
            return TRUE;
        }
        // Cas particulier du WE ==> Comme ce n'est pas un congé, il n'est pas dans la base de données.....
        if (strcasecmp((string)$typeconge, "WE") == 0)
        {
            return false;
        }
        // Cas particulier de la période 'non déclarée' ==> Comme ce n'est pas un congé, il n'est pas dans la base de données.....
        if (strcasecmp((string)$typeconge, "nondec") == 0)
        {
            return false;
        }
        if (strcasecmp((string)$typeconge, "ferie") == 0)
        {
            return false;
        }
        if (strcasecmp((string)$typeconge, "teletrav") == 0)
        {
            return false;
        }
        // echo "Fonction->estunconge : typeconge = $typeconge <br>";
        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
        $params = array($typeconge);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->estunconge : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->estunconge : Pas de congé '" . $typeconge . "' défini dans la base.";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $result = mysqli_fetch_row($query);
        // Si il n'y a pas de référence à une année ==> Ce n'est pas un congé ==> C'est une absence car pas de gestion annuelle
        // echo "Fonctions->estunconge : Result = " . $result[0] . " <br>";
        if (($result[0] == "") or ($result[0] == 0) or (is_null($result))) {
            // echo "Fonctions->estunconge : Je retourne FALSE <br>";
            return FALSE;
        } else {
            // echo "Fonctions->estunconge : Je retourne TRUE <br>";
            return TRUE;
        }
    }

    /**
     *
     * @param string $typeconge
     *            the type of vacation to test
     * @return string Reference year for type of vacation
     */
    public function congesanneeref($typeconge)
    {
        if ($typeconge=='' or is_null($typeconge))
        {
            return "";
        }

        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
        $params = array($typeconge);
        $query = $this->prepared_select($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->congesanneeref : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->congesanneeref : Le type '" . $typeconge . "' n'est pas défini dans la base.";
            //echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $result = mysqli_fetch_row($query);
        if (($result[0] == "") or ($result[0] == 0) or (is_null($result))) {
            return "";
        } else {
            return $result[0];
        }
    }

    /**
     *
     * @param string $constante
     *            the constant identifier to read from the database
     * @return string|array the constant value readed from the database
     */
    public function liredbconstante($constante)
    {
        if (defined("$constante")) /* Si la constante est définie */
        {
            return constant($constante);
        }
        else
        {
            if (strpos($constante,'%')!==false)
            {
                $operateur = 'LIKE';
            }
            else
            {
                $operateur = '=';
            }
            $sql = "SELECT VALEUR,NOM FROM CONSTANTES WHERE NOM $operateur ?";
            $params = array($constante);
            $query = $this->prepared_select($sql, $params);

            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Fonctions->liredbconstante : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Fonctions->liredbconstante : La constante '" . $constante . "' n'est pas defini dans la base.";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            elseif (trim($operateur) == '=')
            {
                $result = mysqli_fetch_row($query);
                return $result[0];
            }
            else
            {
                $tabreturn = array();
                while ($result = mysqli_fetch_row($query))
                {
                    $tabreturn[$result[1]] = $result[0];
                }
                return $tabreturn;
            }
        }
    }

    public function enregistredbconstante($constante, $valeur)
    {
        if (defined("$constante")) /* Si la constante est définie */
        {
            $errlog = "Fonctions->enregistredbconstante : La constante '" . $constante . "' n'a pas pu être mise à jour : Elle est définie dans le fichier de configuration PHP.";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return $errlog;
        }
        else
        {
            if (!$this->testexistdbconstante($constante))
            {
                $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES(?,?)";
                $params = array($constante,$valeur);
                $query = $this->prepared_select($sql, $params);
            }
            else
            {
                $sql = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = ?";
                $params = array($valeur,$constante);
                $query = $this->prepared_select($sql, $params);
            }
            $erreur = mysqli_error($this->dbconnect);
            if (strlen($erreur)>0)
            {
                $errlog = "Fonctions->enregistredbconstante : La constante '" . $constante . "' n'a pas pu être mise à jour : $erreur.";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
                return $errlog;
            }
            return '';
        }
    }

    public function testexistdbconstante($constante)
    {
        if (defined("$constante")) /* Si la constante est définie */
        {
            return true;
        }
        else
        {
            if (strpos($constante,'%')!==false)
            {
                $operateur = 'LIKE';
            }
            else
            {
                $operateur = '=';
            }


            $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM $operateur ?";
            $params = array($constante);
            $query = $this->prepared_select($sql, $params);

            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Fonctions->testexistdbconstante : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            return (mysqli_num_rows($query) != 0);
        }
    }

    /**
     *
     * @param string $datedebut
     *            the beginning date
     * @param string $datefin
     *            the end date
     * @return integer the number of days between this two dates
     */
    public function nbjours_deux_dates($datedebut, $datefin)
    {
        // ////////////////////////////////////////////////////////////////////////////////////
        // ATTENTION AU CALCUL DE LA DIFFERENCE ENTRE LES 2 DATES !!!!
        // Au mois de mars avec le changement d'heure c'est ne marche pas bien
        // On ajoute des heures apres la date pour etre sur qu'avec le changement d'horaire
        // on reste bien dans la même journée
        $tempdatefin = strtotime($this->formatdatedb($datefin) . " 07:00:00");
        $tempdatedeb = strtotime($this->formatdatedb($datedebut) . " 07:00:00");
        $tempnbrejour = $tempdatefin - $tempdatedeb;
        return round($tempnbrejour / 86400) + 1;
    }

    /**
     *
     * @param string $mois
     *            the month number (1=january, 12=december)
     * @param string $annee
     *            the year
     * @return integer the number of days in this month/year
     */
    public function nbr_jours_dans_mois($mois, $annee)
    {
        // // fonction qui permet de retrouver le nombre de jours contenu dans chaque mois d'un année
        // // choisie , celle ci tien compte des années bisextiles.
        $nbr_jrs_mois = date("t", mktime(0, 0, 0, $mois, 1, $annee));
        return $nbr_jrs_mois;
    }

    /**
     *
     * @deprecated
     * @param
     *            $mois_dep
     * @param
     *            $mois_arriv
     * @return integer the number of month
     */
    function diff_mois($mois_dep, $mois_arriv)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        if ($mois_dep > $mois_arriv) {
            $nbr_mois = (13 - $mois_dep) + $mois_arriv;
        } else {
            $nbr_mois = ($mois_arriv + 1) - $mois_dep;
        }
        return $nbr_mois;
    }

    /**
     *
     * @deprecated
     * @param
     *            $jour_dep
     * @param
     *            $mois_dep
     * @param
     *            $annee
     * @return integer number of working day
     */
    function nbr_jrs_travail_mois_deb($jour_dep, $mois_dep, $annee)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        // nbr de jour ds le mois
        $nbr_jrs_mois = $this->nbr_jours_dans_mois($mois_dep, $annee);
        // nbr de jour ds le mois depuis le jour de début de l'affectation
        $nbr_jour_travail = ($nbr_jrs_mois + 1) - $jour_dep;

        return $nbr_jour_travail;
    }

    /**
     *
     * @param
     *  anneeref : Année de référence de la légende
     * @return array list of caption
     */
    public function legende($anneeref, $includeteletravail = false)
    {
/*
        $sql = "SELECT DISTINCT LIBELLE,COULEUR FROM TYPEABSENCE
 				WHERE (ANNEEREF=" . $this->anneeref() . " OR ANNEEREF=" . ($this->anneeref() - 1) . ")
 				   OR ANNEEREF IS NULL
 				ORDER BY LIBELLE";
*/
        $sql = "SELECT DISTINCT LIBELLE,COULEUR,TYPEABSENCEID FROM TYPEABSENCE
 				WHERE (ANNEEREF= ? OR ANNEEREF= ?)
 				   OR ANNEEREF IS NULL ";
        if ($includeteletravail)
        {
            $sql = $sql . " OR TYPEABSENCEID = 'teletrav' ";
        }
        $sql = $sql . " OR TYPEABSENCEID IN ('teletravHC','" . recuperation::RECUP_ID . "') ";
 		$sql = $sql . "		ORDER BY LIBELLE";
        // echo "sql = " . $sql . " <br>";
 		$params = array($anneeref,($anneeref - 1));
 		$query = $this->prepared_select($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonction->legende : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $tablegende = array();
        while ($result = mysqli_fetch_row($query)) {
            $libelle = "$result[0]";
            $couleur = "$result[1]";
            // $code_legende = "$result[2]";
            $tablegende["$result[2]"] = array(
                "libelle" => $libelle,
                "couleur" => $couleur
            );
        }

        // print_r($tablegende); echo "<br>";
        return $tablegende;
    }

    /**
     *
     * @param
     *  anneeref : Année de référence de la légende
     * @return string html text representing the list of caption
     */
    public function legendehtml($anneeref, $includeteletravail = FALSE, $listelegende = array())
    {
        $tablegende = $this->legende($anneeref,$includeteletravail);
        $htmltext = "";
        $idlegende = "legendehtml_" . rand(1,10000);
        $htmltext = $htmltext . "<table id='$idlegende' class='legendetableau' ><tbody><tr>";
        $index=0;
        foreach ($tablegende as $key => $legende)
        {
            if (count($listelegende)==0 or in_array($key, $listelegende))
            {
                if (($index % 5) == 0 and $index>0)
                {
                    $htmltext = $htmltext . "</tr><tr>"; 
                }
                $htmltext = $htmltext . "<td class='maincell'><table class='elementlegende'><tbody><tr><td><span class='legendecouleur' style='background-color:" . $legende["couleur"] . ";' ></span></td><td class='legendetexte' >" . $legende["libelle"] . "</td></tr></tbody></table></td>";
                // $htmltext = $htmltext . "<td class='maincell'><table class='elementlegende'><tbody><tr><td><span class='legendecouleur' style='background-color:" . $legende["couleur"] . ";' bgcolor=" . $legende["couleur"] . "></span></td><td class='legendetexte' >" . $legende["libelle"] . "</td></tr></tbody></table></td>";
                //$htmltext = $htmltext . "<td class='maincell'><span class='legendecouleur' style='background-color:" . $legende["couleur"] . ";' bgcolor=" . $legende["couleur"] . "></span><span class='legendetexte' >" . $legende["libelle"] . "</span></td>";
                //$htmltext = $htmltext . "<td class='maincell'><div class='legendecouleur' style='background-color:" . $legende["couleur"] . "; float:left;' bgcolor=" . $legende["couleur"] . "></div><div class='legendetexte' style='float:left;'>" . $legende["libelle"] . "</div></td>";
                $index++;
            }
        }
        $htmltext = $htmltext . "</tr></tbody>";
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "
<script>
    var currentlegende = document.getElementById('" . $idlegende .  "');
    if (currentlegende)
    {
        var div = currentlegende.previousSibling;
        //alert ('Le nom de la balise est : ' + div.tagName);
        if (div && div.tagName.toLowerCase()==='div')
        {
            var tableau = div.firstElementChild;
            if (tableau && tableau.tagName.toLowerCase()==='table')
            {
                var largeur = tableau.offsetWidth;
                var row = currentlegende.getElementsByTagName('tr');
                //alert ('row = ' + row.length);
                if (row && row.length>0)
                {
                    var legendemaincell = currentlegende.getElementsByClassName('maincell')
                    // On regarde combien il y a de cellule dans la 1ere ligne du tableau
                    var cellcount = row[0].getElementsByClassName('maincell').length;
                    //alert ('cellcount = ' + cellcount);
                    var cellwidth = Math.floor(largeur/cellcount);
                    for (var i = 0 ; i < legendemaincell.length ; i++)
                    {
                        legendemaincell[i].width = cellwidth;
                        //alert('Dans la case ' + i + '  width = ' + cellwidth);
                    }
                }
            }
        }
    }
</script>
";

        return $htmltext;
    }

    /**
     *
     * @param object $pd :  the pdf file
     *  anneeref : Année de référence de la légende
     * @return
     */
    public function legendepdf($pdf, $anneeref, $includeteletravail = FALSE,$listelegende = array())
    {
        $tablegende = $this->legende($anneeref,$includeteletravail);
        $long_chps = 0;
        foreach ($tablegende as $key => $legende)
        {
            if ($pdf->GetStringWidth($legende["libelle"]) > $long_chps)
            {
                $long_chps = $pdf->GetStringWidth($legende["libelle"]);
            }
        }
        $long_chps = $long_chps + 6;
        $index=0;
        foreach ($tablegende as $key => $legende)
        {
            if (count($listelegende)==0 or in_array($key, $listelegende))
            {
                if (($index % 5) == 0)
                {
                    $pdf->Ln(10);
                }
                // $LL_TYPE_CONGE = "$result[LL_TYPE_CONGE]";
                list ($col_leg1, $col_leg2, $col_leg3) = $this->html2rgb($legende["couleur"]);

                // $long_chps=strlen($legende["type_conge"])+10;
                // $long_chps=$pdf->GetStringWidth($legende["type_conge"])+6;
                $pdf->SetFillColor($col_leg1, $col_leg2, $col_leg3);
                $pdf->Cell(4, 5, $this->utf8_decode(""), 1, 0, 'C', 1);
                $pdf->Cell($long_chps, 4, $this->utf8_decode($legende["libelle"]), 0, 0, 'L');
                $index++;
            }
        }
    }

    /**
     *
     * @param string $color
     *            the html color (ex : #123456)
     * @return array of three value (R,G,B) corresponding to the html color
     */
    public function html2rgb($color)
    {
        // gestion du #...
        if (substr($color, 0, 1) == "#")
            $color = substr($color, 1, 6);

        $col1 = hexdec(substr($color, 0, 2));
        $col2 = hexdec(substr($color, 2, 2));
        $col3 = hexdec(substr($color, 4, 2));
        return array(
            $col1,
            $col2,
            $col3
        );
    }

    /**
     *
     * @param string $codemoment
     *            the moment identifier (m or a)
     * @return string the moment name if correct / error message otherwise
     */
    public function nommoment($codemoment = null)
    {
        if (is_null($codemoment))
            return "Le codemoment $codemoment est inconnu";
        switch ($codemoment) {
            case fonctions::MOMENT_MATIN:
                return "matin";
                break;
            case fonctions::MOMENT_APRESMIDI:
                return "après-midi";
                break;
            case '' :
                return "toute la journée";
                break;
        }
    }

    /**
     *
     * @param string $codeouinon
     *            Toute valeur pouvant être traduite en boolean 
     *              O, OUI, Y, YES, ON, 1
     *              N, NON, N, NO, OFF, 0
     * @param bool $anglais
     *            False => En français (valeur par défaut)
     *            True => En anglais  
     * @return string La chaine de caratère correspondant à la valeur $ouinon dans la langue sélectionnée (Fr/En)
     */
    public function ouinonlibelle($codeouinon = null, $anglais = false)
    {
        $ouiarray = array("Oui", "Yes");
        $nonarray = array("Non", "No");

        if (is_null($codeouinon))
        {
            return "Le codeouinon $codeouinon est inconnu";
        }
        // On utilise le fait que false = 0 et true = 1 pour récupérer le bon libellé dans le tableau
        if ($this->convertvaluetobool($codeouinon))
        {
            return $ouiarray[(int)$anglais];
        }
        else
        {
            return $nonarray[(int)$anglais];
        }
    }

    public function demandestatutlibelle($statut = null)
    {
        if (strcasecmp((string)$statut, demande::DEMANDE_VALIDE) == 0)
        {
            return "Validée";
        }
        elseif (strcmp((string)$statut, demande::DEMANDE_REFUSE) == 0)
        {
            return "Refusée";
        }
        elseif (strcmp((string)$statut, demande::DEMANDE_ANNULE) == 0)
        {
            return "Annulée";
        }
        elseif (strcasecmp((string)$statut, demande::DEMANDE_ATTENTE) == 0)
        {
            return "En attente";
        }
        elseif (strcasecmp((string)$statut, demande::DEMANDE_AVIS) == 0)
        {
            return "Demande d'avis";
        }
        elseif (strcasecmp((string)$statut, demande::DEMANDE_VALID_RH) == 0)
        {
            return "En attente de validation RH";
        }
        else
        {
            echo "Demandestatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
        }
    }

    public function demandeavislibelle($statut = null)
    {
        if (strcasecmp((string)$statut, demande::DEMANDE_VALIDE) == 0)
        {
            return "Favorable";
        }
        elseif (strcmp((string)$statut, demande::DEMANDE_REFUSE) == 0)
        {
            return "Défavorable";
        }
        elseif (strcasecmp((string)$statut, demande::DEMANDE_ATTENTE) == 0)
        {
            return "En attente";
        }
        elseif (strcasecmp((string)$statut, demande::DEMANDE_AVIS) == 0)
        {
            return "Demande d'avis";
        }
        else
        {
            echo "Demandeavislibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
        }
    }


    public function teletravailstatutlibelle($statut = null)
    {
        if (strcasecmp((string)$statut, teletravail::TELETRAVAIL_VALIDE) == 0)
        {
            return "Validée";
        }
        elseif (strcmp((string)$statut, teletravail::TELETRAVAIL_REFUSE) == 0)
        {
            return "Refusée";
        }
        elseif (strcmp((string)$statut, teletravail::TELETRAVAIL_ANNULE) == 0)
        {
            return "Annulée";
        }
        elseif (strcasecmp((string)$statut, teletravail::TELETRAVAIL_ATTENTE) == 0)
        {
            return "En attente";
        }
        else
        {
            echo "teletravailstatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
        }
    }

    /**
     *
     * @param string $statut
     *            status code (v,r,a) for part time
     * @return string the status label of the part time if correct / display error message otherwise
     */
    public function declarationTPstatutlibelle($statut = null)
    {
        if (strcasecmp((string)$statut, declarationTP::DECLARATIONTP_VALIDE) == 0)
            return "Validée";
        elseif (strcasecmp((string)$statut, declarationTP::DECLARATIONTP_REFUSE) == 0)
            return "Refusée";
        elseif (strcasecmp((string)$statut, declarationTP::DECLARATIONTP_ATTENTE) == 0)
            return "En attente";
        else
            echo "declarationTPstatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
    }

    /**
     *
     * @param string $texte
     * @return string the string without accents
     */
    public function stripAccents($texte)
    {
        $texte = mb_strtolower($texte, 'UTF-8');
        $texte = str_replace(array(
            'à',
            'â',
            'ä',
            'á',
            'ã',
            'å',
            'î',
            'ï',
            'ì',
            'í',
            'ô',
            'ö',
            'ò',
            'ó',
            'õ',
            'ø',
            'ù',
            'û',
            'ü',
            'ú',
            'é',
            'è',
            'ê',
            'ë',
            'ç',
            'ÿ',
            'ñ'
        ), array(
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'e',
            'e',
            'e',
            'e',
            'c',
            'y',
            'n'
        ), $texte);
        return $texte;
    }

    /**
     *
     * @param string $texte
     * @return string the string escaped and utf8-encoded
     */
    public function my_real_escape_utf8($texte)
    {
        $texte = $texte . '';
        //return mysqli_real_escape_string($this->dbconnect, $this->utf8_encode($texte));
        if (mb_detect_encoding($texte, 'UTF-8', true)===false) // Ce n'est pas de l'UTF-8
        {
            return mysqli_real_escape_string($this->dbconnect, iconv('ISO-8859-1', 'UTF-8', $texte));
        }
        else
        {   // C'est déjà de l'UTF-8 => On ne réencode pas le texte
            return mysqli_real_escape_string($this->dbconnect, $texte);            
        }
    }

    /**
     *
     * @param string $typeprofil
     *            optional Type de profil RH demandé => 1 = RHCET, 2 = RHCONGE, 3 = RHANOMALIE. Si null => tous les profils
     * @return array list of user with selected profiles.
     */
    function listeprofilrh($typeprofil = null)
    {
        $agentarray = array();
        $sql = "SELECT AGENTID FROM COMPLEMENT WHERE COMPLEMENTID IN (";
        if (is_null($typeprofil)) {
            $sql = $sql . "'" . agent::PROFIL_RHCET . "', '" . agent::PROFIL_RHCONGE . "', '" . agent::PROFIL_RHANOMALIE . "', '" . agent::PROFIL_RHTELETRAVAIL . "'";
        } elseif ($typeprofil == 1 or $typeprofil == agent::PROFIL_RHCET) {
            $sql = $sql . "'" . agent::PROFIL_RHCET . "'";
        } elseif ($typeprofil == 2 or $typeprofil == agent::PROFIL_RHCONGE) {
            $sql = $sql . "'" . agent::PROFIL_RHCONGE . "'";
        } elseif ($typeprofil == 3  or $typeprofil == agent::PROFIL_RHANOMALIE) {
            $sql = $sql . "'" . agent::PROFIL_RHANOMALIE . "'";
        } elseif ($typeprofil == agent::PROFIL_RHTELETRAVAIL) {
            $sql = $sql . "'" . agent::PROFIL_RHTELETRAVAIL . "'";
        } else {
            $errlog = "Agent->listeprofilrh (AGENT) : Type de profil demandé inconnu (typeprofil = $typeprofil)";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return $agentarray;
        }
        $sql = $sql . ") AND UPPER(VALEUR) = 'O'";
        // echo "sql = " . $sql . "<br>";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Agent->listeprofilrh (AGENT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return $agentarray;
        }
        while ($result = mysqli_fetch_row($query)) {
            $agentrh = new agent($this->dbconnect);
            if ($agentrh->load("$result[0]"))
            {
                $datecourante = date('d/m/Y');
                // Un agent sans affectation ne peut pas être agent RH sauf si son id < 0 (<=> utilisateurs spécifiques) (ticket GLPI 131031)
                if (count((array)$agentrh->affectationliste($datecourante, $datecourante))>0 or $agentrh->agentid()<0)
                {
                    $agentarray[$agentrh->agentid()] = $agentrh;
                }
            }
            unset($agentrh);
        }
        return $agentarray;
    }

//    /**
//     *
//     * @deprecated
//     *
//     * @param string $structid
//     *            Code de la structure à convertir
//     * @return string Code de la structure correspondante.
//     */
//    public function labo2ufr($structid)
//    {
//        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
//
//        $sql = "SELECT LABORATOIREID,UFRID FROM LABO_UFR WHERE LABORATOIREID = ?";
//        $params = array($structid);
//        $query = $this->prepared_select($sql, $params);
//        $erreur = mysqli_error($this->dbconnect);
//        if ($erreur != "") {
//            $errlog = "labo2ufr : " . $erreur;
//            echo $errlog . "<br/>";
//            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
//            return $structid;
//        }
//        if (mysqli_num_rows($query) == 0) {
//            return $structid;
//        }
//        $result = mysqli_fetch_row($query);
//        $querryresult = $result[1];
//        return $querryresult;
//    }


    public function CETaverifier($datedebut,$agentid = null)
    {
        $sql = "SELECT DISTINCT DEMANDEID,AGENTID, DATEDEBUT,DATESTATUT
    				FROM DEMANDE
    				WHERE TYPEABSENCEID = 'cet'
    				  AND (DATEDEBUT >= ?
    				    OR DATESTATUT >= ? ) ";
        if (!is_null($agentid))
        {
            $sql = $sql . " AND AGENTID = ? ";
            $params = array($this->formatdatedb($datedebut),$this->formatdatedb($datedebut),$agentid);
        }
        else
        {
            $params = array($this->formatdatedb($datedebut),$this->formatdatedb($datedebut));
        }
    	$sql = $sql . " ORDER BY AGENTID, DATEDEBUT, DATESTATUT";
        $query = $this->prepared_select($sql, $params);
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
            $complement->load($result[1], 'DEM_CET_' . $demandeid);

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

    public function demandesaverifier($datedebut, $agentid = null)
    {
        $sql = "SELECT DISTINCT DEMANDEID,AGENTID, DATEDEBUT,DATESTATUT
    				FROM DEMANDE
    				WHERE STATUT = '" . demande::DEMANDE_VALID_RH . "'
    				  AND DATEDEBUT >= ? ";
        if (!is_null($agentid))
        {
            $sql = $sql . " AND AGENTID = ? ";
            $params = array($this->formatdatedb($datedebut),$agentid);
        }
        else
        {
            $params = array($this->formatdatedb($datedebut));
        }
    	$sql = $sql . " ORDER BY AGENTID, DATEDEBUT, DATESTATUT";
        $query = $this->prepared_select($sql, $params);
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
            $demandeliste[] = $demande;
        }
        return $demandeliste;
    }

    public function congessuppaverifier($datedebut,$agentid = null)
    {
        $sql = "SELECT COMMENTAIRECONGE.COMMENTAIRECONGEID, 
                       COMMENTAIRECONGE.AGENTID, 
                       COMMENTAIRECONGE.TYPEABSENCEID, 
                       COMMENTAIRECONGE.DATEAJOUTCONGE,
                       COMMENTAIRECONGE.COMMENTAIRE,
                       COMMENTAIRECONGE.NBRJRSAJOUTE,
                       COMMENTAIRECONGE.AUTEURID,
                       TYPEABSENCE.LIBELLE
                FROM COMPLEMENT, COMMENTAIRECONGE , TYPEABSENCE
                WHERE COMPLEMENT.COMPLEMENTID LIKE '" . complement::AVISRH_CONGES_SUP_LABEL . "%'
                  AND COMMENTAIRECONGE.COMMENTAIRECONGEID = REPLACE(COMPLEMENT.COMPLEMENTID,'" . complement::AVISRH_CONGES_SUP_LABEL . "','')
    			  AND COMMENTAIRECONGE.DATEAJOUTCONGE >= ? 
                  AND COMMENTAIRECONGE.AGENTID = COMPLEMENT.AGENTID 
                  AND TYPEABSENCE.TYPEABSENCEID = COMMENTAIRECONGE.TYPEABSENCEID";
        if (!is_null($agentid))
        {
            $sql = $sql . " AND COMPLEMENT.AGENTID = ? ";
            $params = array($this->formatdatedb($datedebut),$agentid);
        }
        else
        {
            $params = array($this->formatdatedb($datedebut));
        }
    	$sql = $sql . " ORDER BY COMPLEMENT.AGENTID, COMMENTAIRECONGE.DATEAJOUTCONGE";
        //echo "<br>"; print_r($sql); echo "<br>";
        $query = $this->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        $congessuppliste = array();
        // Si pas de demande de CET, on retourne le tableau vide
        if (mysqli_num_rows($query) == 0) {
            return $congessuppliste;
        }
        while ($result = mysqli_fetch_row($query)) {
            $commentaireconge = new commentaireconge();

            // $commentaireconge->commentaireid = $result[0];
            // $commentaireconge->agentid = $result[1];
            // $commentaireconge->typeabsenceid = $result[2];
            // $commentaireconge->dateajout = $result[3];
            // $commentaireconge->commentaire = $result[4];
            // $commentaireconge->nbjoursajoute = $result[5];
            // $commentaireconge->auteurid = $result[6];
            // $commentaireconge->libelleabsence = $result[7];
            $commentaireconge = $this->lirecommentaire($result[0]);

            $congessuppliste[] = $commentaireconge;
        }
        return $congessuppliste;
    }


    public function savepdf($pdf, $filename)
    {
        $path = dirname("$filename");
        if (!file_exists($path))
        {
            mkdir("$path",0777,true);
            //mkdir("$path");
            chmod("$path", 0777);
        }
        $pdf->Output($filename, 'F');
    }

    /**
     *
     * @param string YYYYMMDD the beginning date to set
     * @return string the beginning of the cet alimentation period in format YYYYMMDD
     */
    public function debutalimcet($date=NULL)
    {
        $dbconstante = 'DEBUTALIMCET';
        if (!is_null($date))
        {
            $date = $this->formatdatedb($date);
            $this->enregistredbconstante($dbconstante, $date);
        }
        elseif ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return ($this->anneeref()+1).$this->finperiode();
        }

    }

    /**
     *
     * @param string YYYYMMDD the beginning date to set
     * @return string the end of the cet alimentation period in format YYYYMMDD
     */
    public function finalimcet($date=NULL)
    {
        $dbconstante = 'FINALIMCET';
        if (!is_null($date))
        {
            $date = $this->formatdatedb($date);
            $this->enregistredbconstante($dbconstante, $date);
        }
        elseif ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return ($this->anneeref()+1).$this->finperiode();
        }

/*
        if (!is_null($date))
    	{
    		$update = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = 'FINALIMCET'";
    		$params = array($this->formatdatedb($date));
    		$query = $this->prepared_query($update, $params);
    	}
    	$sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'FINALIMCET'";
    	$params = array();
    	$query = $this->prepared_select($sql, $params);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "") {
    		$errlog = "Fonctions->finalimcet : " . $erreur;
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    	}
    	if (mysqli_num_rows($query) == 0) {
    		$errlog = "Fonctions->finalimcet : Pas de fin de période définie dans la base. On force au 0831 de l'année univ de référence. ";
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    		return ($this->anneeref()+1).$this->finperiode();
    	}
    	$result = mysqli_fetch_row($query);
    	// echo "Fonctions->finperiode : fin de période ==> " . $result[0] . ".<br>";
    	return "$result[0]";
*/
    }

    /**
     *
     * @param string YYYYMMDD the beginning date to set
     * @return string the beginning of the cet option period in format YYYYMMDD
     */
    public function debutoptioncet($date=NULL)
    {
        $dbconstante = 'DEBUTOPTIONCET';
        if (!is_null($date))
        {
            $date = $this->formatdatedb($date);
            $this->enregistredbconstante($dbconstante, $date);
        }
        elseif ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return ($this->anneeref()+1).'0101';
        }

/*
        if (!is_null($date))
        {
            $update = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = 'DEBUTOPTIONCET'";
            $params = array($this->formatdatedb($date));
            $query = $this->prepared_query($update, $params);
        }
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'DEBUTOPTIONCET' AND VALEUR <> ''";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->debutoptioncet : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->debutoptioncet : Pas de début de période défini dans la base. On force au 0101 de l'année suivante. ";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return ($this->anneeref()+1).'0101';
        }
        $result = mysqli_fetch_row($query);
        // echo "Fonctions->debutoptioncet : Debut de période ==> " . $result[0] . ".<br>";
        return "$result[0]";
*/
    }

    /**
     *
     * @param string YYYYMMDD the end date to set
     * @return string the end of the cet option period in format YYYYMMDD
     */
    public function finoptioncet($date=NULL)
    {
        $dbconstante = 'FINOPTIONCET';
        if (!is_null($date))
        {
            $date = $this->formatdatedb($date);
            $this->enregistredbconstante($dbconstante, $date);
        }
        elseif ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return ($this->anneeref()+1).'0131';
        }

/*
        if (!is_null($date))
        {
            $update = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = 'FINOPTIONCET'";
            $params = array($this->formatdatedb($date));
            $query = $this->prepared_query($update, $params);
        }
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'FINOPTIONCET'";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->finoptioncet : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->finoptioncet : Pas de fin de période définie dans la base. On force au 0131 de l'année suivante. ";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return ($this->anneeref()+1).'0131';
        }
        $result = mysqli_fetch_row($query);
        // echo "Fonctions->finperiode : fin de période ==> " . $result[0] . ".<br>";
        return "$result[0]";
*/
    }


    public function getidmodelalimcet()
    {
        static $modelalimcet = null;

        $dbconstante='IDMODELALIMCET';
        if (is_null($modelalimcet))
        {
            if ($this->testexistdbconstante($dbconstante))
            {
                $modelalimcet = $this->liredbconstante($dbconstante);
            }
            else
            {
                $modelalimcet = "";
            }
        }
        return $modelalimcet;
    }

    public function getidmodelteletravail($maxniveau, $agent)
    {
//        // echo "<br>On est dans le cas d'un niveau $maxniveau<br>";
//        $resp_n2 = $agent->getsignataire_niveau2();
//
//        if ($maxniveau == 4 and $resp_n2===false)
//        {
//            $dbconstante='IDMODELTELETRAVAIL';
//        }
//        elseif ($maxniveau == 5 and $resp_n2!==false)
//        {
//            $dbconstante='IDMODELTELETRAVAIL_EVOLUE';
//        }
//        else
//        {
//            echo $this->showmessage(fonctions::MSGERROR, "Incohérence entre le nombre de niveau et la situation de l'agent (nombre de niveau = $maxniveau et l'agent " . (($resp_n2===false)?" n'a pas de ":" a un ")  . "responsable).");
//            return "";
//        }

        if ($maxniveau == 4)
        {
            $dbconstante='IDMODELTELETRAVAIL';
        }
        elseif ($maxniveau == 5)
        {
            $dbconstante='IDMODELTELETRAVAIL_EVOLUE';
        }
        else
        {
            echo $this->showmessage(fonctions::MSGERROR, "Impossible de déterminer le modèle de circuit de télétravail (nombre de niveau = $maxniveau)");
            return "";
        }

        if ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return "";
        }
    }


    /**
     *
     * @param array $tab
     * @return string the tab in string for IN clause db ('$tab[0]', '$tab[1], ...)
     */
    public function formatlistedb($tab)
    {
    	$chaine = '';
    	if (sizeof($tab) != 0)
    	{
    		foreach ($tab as $value)
    		{
    			if ($chaine == '')
    			{
    				$chaine .= "('".$value."'";
    			}
    			else {
    				$chaine .= ", '".$value."'";
    			}
    		}
    		$chaine .= ")";
    	}
    	return $chaine;
    }

    public function datesconsecutives($date1, $date2)
    {
    	$retour = FALSE;
    	$dbdate1 = $this->formatdatedb($date1);
    	$dbdate2 = $this->formatdatedb($date2);
//    	echo "date1 = $date1 <br>";
//    	echo "date1 + 1 = " . $this->formatdatedb(date("Y-m-d", strtotime("+1 day", strtotime($dbdate1)))) . "<br>";
//    	echo "date2 = $date2 <br>";
//    	echo "date2 + 1 = " . $this->formatdatedb(date("Y-m-d", strtotime("+1 day", strtotime($dbdate2)))) . "<br>";

    	if ($this->formatdatedb(date("Y-m-d", strtotime("+1 day", strtotime($dbdate1)))) == $dbdate2)
    	{
    		return TRUE;
    	}
    	elseif ($this->formatdatedb(date("Y-m-d", strtotime("+1 day", strtotime($dbdate2)))) == $dbdate1)
    	{
    		return TRUE;
    	}
    	return $retour;
    }

    public function synchro_g2t_eSignature($full_g2t_ws_url, $id)
    {
        // On appelle le WS G2T en GET pour demander à G2T de mettre à jour la demande
        if ($this->executionbatch())
        {
            error_log(basename(__FILE__) . $this->stripAccents("On va synchroniser le document esignature $id "));
        }
        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $full_g2t_ws_url . "?signRequestId=" . $id,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_PROXY, '');
        //echo "<br>CURLOPT_PROXY => " . curl_getinfo($curl,CURLOPT_PROXY) . "<br><br>";
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
            echo "Erreur Curl (synchro_g2t_eSignature) = " . $error . "<br><br>";
            error_log(basename(__FILE__) . $this->stripAccents(" Impossible de synchroniser G2T avec eSignature (id eSignature = $id, URL WS G2T = $full_g2t_ws_url) => Erreur : " . $error ));
            return "Pas de réponse du webservice G2T.";
        }
        //echo "<br>Le json (synchro_g2t_eSignature) " . print_r($json,true) . "<br>";
        error_log(basename(__FILE__) . $this->stripAccents(" Le json (synchro_g2t_eSignature) " . print_r($json,true)));
        $response = (array)json_decode($json, true);
        //echo "<br>La reponse (synchro_g2t_eSignature) " . print_r($response,true) . "<br>";
        error_log(basename(__FILE__) . $this->stripAccents(" Le response (synchro_g2t_eSignature) " . print_r($response,true)));
        if (isset($response['description']))
        {
            return $response['description'];
        }
        else
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Réponse du webservice G2T non conforme (id eSignature = $id, URL WS G2T = $full_g2t_ws_url) => Erreur : " . var_export($response, true) ));
            return "Réponse du webservice G2T non conforme.";
        }
        /*
         echo "<br>";
         echo '<pre>';
         var_dump($response);
         echo '</pre>';
         */
    }

    public function get_g2t_url()
    {
        if (defined('G2T_URL')) /* A partir de la version 7 de G2T, la constante est forcément déclarée ==> Donc on devrait passer systématiquement ici */
        {
            $g2t_url = G2T_URL;
            // error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base de G2T est récupérée de la constante => $g2t_url" ));
        }
        elseif ($this->testexistdbconstante('G2T_URL'))
        {
            $g2t_url = $this->liredbconstante('G2T_URL');
            // error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base de G2T est récupérée de la base de données => $g2t_url" ));
        }
        else
        {
            $g2t_url = '';
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base de G2T est inconnue !" ));
        }
        return $g2t_url;
    }

    public function get_g2t_ws_url()
    {
        if (defined('G2T_WS_URL')) /* A partir de la version 6 de G2T, la constante est forcément déclarée ==> Donc on devrait passer systématiquement ici */
        {
            $g2t_ws_url = G2T_WS_URL;
            // error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T est récupérée de la constante => $g2t_ws_url" ));
        }
        else if (!isset($_SERVER['SERVER_NAME'])) /* Si on passe là, on a un problème car la constante n'est pas défini et on n'a aucun moyen de calculer l'URL du WS!! */
        {
            $g2t_ws_url = "URL invalide !";
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T n'est pas dans la constante et impossible de calculer l'URL => $g2t_ws_url" ));
        }
        else
        {
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T va être calculée" ));
            // On récuère le nom du serveur G2T
            $servername = $_SERVER['SERVER_NAME'];


            // Si on passe par un proxy ==> HTTP_X_FORWARDED_PROTO est défini dans le header (protocole utilisé entre le client et le proxy)
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
            {
                $serverprotocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            }
            // Si la requète vient directement sur le serveur, on regarde si $_SERVER['HTTPS'] est défini
            else if (isset($_SERVER['HTTPS']))
            {
                 $serverprotocol = "https";
            }
            // Sinon c'est de l'HTTP
            else
            {
                $serverprotocol = "http";
            }

            //Si on passe par un proxy => HTTP_X_FORWARDED_PORT est défini dans le header (port utilisé entre le client et le proxy)
            if (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
            {
                $serverport = $_SERVER['HTTP_X_FORWARDED_PORT'];
            }
            // Si la requête vient directement sur le serveur, on regarde si $_SERVER['SERVER_PORT'] est défini
            else if (isset($_SERVER['SERVER_PORT']))
            {
                // Le port pour parler au serveur est contenu dans la variable
                $serverport = $_SERVER['SERVER_PORT'];
            }
            // Si le protocole est en https => Le port par défaut est 443
            else if ($serverprotocol == "https")
            {
                $serverport = "443";
            }
            // Si c'est de l'HTTP ou si on n'a aucune information => Le port par défaut est 80
            else
            {
                $serverport = "80";
            }

            //echo "serverprotocol  = $serverprotocol   servername = $servername   serverport = $serverport <br>";
            $g2t_ws_url = $serverprotocol . "://" . $servername . ":" . $serverport.'/ws';
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T est => $g2t_ws_url" ));
        }
        return $g2t_ws_url;
    }

    public function get_g2t_ws_public_url()
    {
        if (defined('G2T_WS_PUBLIC_URL')) /* A partir de la version 7.1.9 de G2T, la constante est forcément déclarée ==> Donc on devrait passer systématiquement ici */
        {
            $g2t_ws_url = G2T_WS_PUBLIC_URL;
            // error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T est récupérée de la constante => $g2t_ws_url" ));
        }
        else if (!isset($_SERVER['SERVER_NAME'])) /* Si on passe là, on a un problème car la constante n'est pas défini et on n'a aucun moyen de calculer l'URL du WS!! */
        {
            $g2t_ws_url = "URL invalide !";
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T n'est pas dans la constante et impossible de calculer l'URL => $g2t_ws_url" ));
        }
        else
        {
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T va être calculée" ));
            // On récuère le nom du serveur G2T
            $servername = $_SERVER['SERVER_NAME'];


            // Si on passe par un proxy ==> HTTP_X_FORWARDED_PROTO est défini dans le header (protocole utilisé entre le client et le proxy)
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
            {
                $serverprotocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            }
            // Si la requète vient directement sur le serveur, on regarde si $_SERVER['HTTPS'] est défini
            else if (isset($_SERVER['HTTPS']))
            {
                 $serverprotocol = "https";
            }
            // Sinon c'est de l'HTTP
            else
            {
                $serverprotocol = "http";
            }

            //Si on passe par un proxy => HTTP_X_FORWARDED_PORT est défini dans le header (port utilisé entre le client et le proxy)
            if (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
            {
                $serverport = $_SERVER['HTTP_X_FORWARDED_PORT'];
            }
            // Si la requête vient directement sur le serveur, on regarde si $_SERVER['SERVER_PORT'] est défini
            else if (isset($_SERVER['SERVER_PORT']))
            {
                // Le port pour parler au serveur est contenu dans la variable
                $serverport = $_SERVER['SERVER_PORT'];
            }
            // Si le protocole est en https => Le port par défaut est 443
            else if ($serverprotocol == "https")
            {
                $serverport = "443";
            }
            // Si c'est de l'HTTP ou si on n'a aucune information => Le port par défaut est 80
            else
            {
                $serverport = "80";
            }

            //echo "serverprotocol  = $serverprotocol   servername = $servername   serverport = $serverport <br>";
            $g2t_ws_url = $serverprotocol . "://" . $servername . ":" . $serverport.'/ws_public';
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T est => $g2t_ws_url" ));
        }
        return $g2t_ws_url;
    }

    public function get_alimCET_liste($typeconges, $listStatuts = array(), $forcesynchro = true) // $typeconges de la forme annYY
    {
        $sql = "SELECT ESIGNATUREID,ALIMENTATIONID FROM ALIMENTATIONCET WHERE TYPECONGES = ? ";
        if (sizeof($listStatuts) != 0)
        {
            $statuts = $this->formatlistedb($listStatuts);
            $sql .=  "AND STATUT IN $statuts";
        }
        $params = array($typeconges);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->get_alimCET_liste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $alimCETliste = array();
        // Si pas de demande d'alimentation de CET, on retourne le tableau vide
        if (mysqli_num_rows($query) == 0) {
            return $alimCETliste;
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $alimid = $result[0] . "";
            $alimCETliste[$result[1]] = $alimid;
            if ($forcesynchro)
            {
                $this->synchronisealimentationCET($alimid);
            }
        }
        return $alimCETliste;
    }

    public function get_optionCET_liste($anneeref, $listStatuts = array(), $forcesynchro = true)
    {
        $sql = "SELECT ESIGNATUREID,OPTIONID FROM OPTIONCET WHERE ANNEEREF = ? ";
        if (sizeof($listStatuts) != 0)
        {
            $statuts = $this->formatlistedb($listStatuts);
            $sql .=  "AND STATUT IN $statuts";
        }
        $params = array($anneeref);
        $query = $this->prepared_select($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->get_optionCET_liste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $optionCETliste = array();
        // Si pas de demande d'option de CET, on retourne le tableau vide
        if (mysqli_num_rows($query) == 0) {
            return $optionCETliste;
        }
        while ($result = mysqli_fetch_row($query)) {
            $optionid = $result[0] . "";
            $optionCETliste[$result[1]] = $optionid;
            if ($forcesynchro)
            {
                $this->synchroniseoptionCET($optionid);
            }
        }
        return $optionCETliste;
    }

    public function g2tbasepath()
    {
        //echo "<br>File = " .  __FILE__  . " <br>Basename = " . basename(__FILE__) . "  <br>Path name = " .  dirname(__FILE__)  . "<br>";
        // On retourne le chemin du fichier fonctions.php remonté de 2 dossiers (<=> <racine>\html\class\fonctions.php)
        // dirname(...., 1) <=> Le dossier courant du fichier
        // dirname(...., 2) <=> Le dossier parent du fichier (donc niveau - 1)
        // dirname(...., 3) <=> Le dossier parent du parent du fichier (donc niveau - 2)
        // ==> La fonction retourne donc le dossier <racine>
        return str_replace("\\", '/', dirname(__FILE__,3)) . "/";
    }

    public function imagepath()
    {
        $basepath = $this->g2tbasepath();
        return $basepath . '/images/';
    }

    public function etablissementimagepath()
    {
        return $this->imagepath() . '/etablissement/';
    }

    public function pdfpath()
    {
        // Constante non documentée pour enregistrer les PDF dans un dossier externe
        if (defined('CUSTOM_PDF_FILEPATH'))
        {
            return CUSTOM_PDF_FILEPATH;
        }
        $basepath = $this->g2tbasepath();
        return $basepath . '/pdf/';
    }

    public function documentpath()
    {
        $basepath = $this->g2tbasepath();
        return $basepath . '/documents/';
    }

    public function inputfilepath()
    {
        $basepath = $this->g2tbasepath();
        return $basepath . '/INPUT_FILES_V3/';
    }

    public function synchroGlobaleCETeSignature($typeconge, $anneeref)
    {
    	$this->get_alimCET_liste($typeconge);
    	$this->get_optionCET_liste($anneeref);
    }

    public function typeCongeAlimCET()
    {
    	return 'ann'.substr($this->anneeref() - 1,2, 2);
    }

    public function listeagentteletravail($datedebut,$datefin, $inclusansconvention = false)
    {
        $datedebut = $this->formatdatedb($datedebut);
        $datefin = $this->formatdatedb($datefin);

        $listeagentteletravail = array();
        $sql = "";
        if ($inclusansconvention)
        {
            $sql = $sql . "SELECT DISTINCT AGENTID, NOM, PRENOM FROM (";
        }

        $sql = $sql . "SELECT DISTINCT AGENT.AGENTID, AGENT.NOM, AGENT.PRENOM
                FROM TELETRAVAIL, AGENT
                WHERE AGENT.AGENTID = TELETRAVAIL.AGENTID
                  AND TELETRAVAIL.STATUT = '" . teletravail::TELETRAVAIL_VALIDE  . "'
                  AND ((TELETRAVAIL.DATEDEBUT <= ? AND TELETRAVAIL.DATEFIN >= ? )
                    OR (TELETRAVAIL.DATEFIN >= ? AND TELETRAVAIL.DATEDEBUT <= ? )
                    OR (TELETRAVAIL.DATEDEBUT >= ? AND TELETRAVAIL.DATEFIN <= ? ))";

        // Si on inclu les demandes de télétravail HC on doit les extraires à partir des demandes de télétravail
        if ($inclusansconvention)
        {
            $sql = $sql . "UNION
                SELECT DISTINCT AGENT.AGENTID, AGENT.NOM, AGENT.PRENOM
                FROM DEMANDE, AGENT
                WHERE AGENT.AGENTID = DEMANDE.AGENTID
                  AND DEMANDE.STATUT = '" . demande::DEMANDE_VALIDE  .  "'
                  AND DEMANDE.TYPEABSENCEID IN (SELECT TYPEABSENCEID FROM TYPEABSENCE WHERE ABSENCEIDPARENT = 'teletravHC')
                  AND ((DEMANDE.DATEDEBUT <= ? AND DEMANDE.DATEFIN >= ? )
                    OR (DEMANDE.DATEFIN >= ? AND DEMANDE.DATEDEBUT <= ? )
                    OR (DEMANDE.DATEDEBUT >= ? AND DEMANDE.DATEFIN <= ? ))
) LISTE_COMPLETE";
        }

        $sql = $sql . " ORDER BY NOM, PRENOM ";

        //echo "<br>SQL = $sql <br>";
        //var_dump($sql);
        if (!$inclusansconvention)
        {
            $params = array($datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
        }
        else
        {
            $params = array($datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin,$datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
        }
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des id agent : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Aucune demande de télétravail pour la période $datedebut -> $datefin <br>";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $listeagentteletravail[] = $result[0];
            }
        }
        return $listeagentteletravail;

    }

    public function enlevemois($date, $nbremois)
    {
        $date = $this->formatdatedb($date);
        $timestamp = strtotime($date);
        $mois = date("m", $timestamp);
        $annee = date("Y", $timestamp);
        $mois = $mois - $nbremois;
        if ($mois<1)
        {
            $mois = 12 - abs($mois);
            $annee = $annee - 1;
        }
        $mois = str_pad($mois, 2, '0',STR_PAD_LEFT);
        return array($annee,$mois);

    }

    public function listestructurenoninclue()
    {
        $listestruct = array();
        $sql = "SELECT STRUCTUREID
                FROM STRUCTURE
                WHERE DATECLOTURE > NOW()
                  AND ISINCLUDED = 0";

        //echo "<br>SQL = $sql <br>";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "fonctions->listestructurenoninclue : Problème SQL dans le chargement des id structure : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>fonctions->listestructurenoninclue => pas de ligne dans la base de données<br>";
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $listestruct[] = $result[0];
            }
        }
        return $listestruct;
    }

    public function CASuserisG2TAdmin($CASuid)
    {
        //error_log(basename(__FILE__) . $this->stripAccents(" CASuid = $CASuid"));
        $userid = $this->useridfromCAS($CASuid);
        if ($userid !== false)
        {
            $user = new agent($this->dbconnect);
            $user->load($userid);
            if ($user->estadministrateur())
            {
                error_log(basename(__FILE__) . $this->stripAccents(" L'utilisateur $userid (Casid = $CASuid) est un administrateur"));
                return $user->agentid();
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" L'utilisateur $userid (Casid = $CASuid) n'est pas un administrateur"));
                return false;
            }
         }
         else
         {
             return false;
         }
    }

    public function useridfromCAS($CASuid)
    {
        $user = new agent($this->dbconnect);
        $userid = $this->getagentidfromldapuid($CASuid);
        if ($userid===false)
        {
            $errlog = "useridfromCAS : L'agent $CASuid n'a pas pu être identifié dans LDAP.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        if (! $user->existe($userid))
        {
            $errlog = "useridfromCAS : L'agent $CASuid (id = " . $userid . " ) n'est pas dans la base de données.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        // error_log(basename(__FILE__) . $this->stripAccents(" L'agentid correspondant à $CASuid est " . $user->agentid()));
        return $userid;
    }

    public function mailexistedansldap($adressemail)
    {
        // On considère que si l'agent a un EPPN, c'est que son compte est disponible dans LDAP => Donc pas besoin d'interroger LDAP.
        // Si l'EPPN est vide => On interroge LDAP car il peut s'agir d'un groupe ou d'un utilisateur spécial (donc sans EPPN)
        $agent = new agent($this->dbconnect);
        if (!$agent->loadbyemail($adressemail))
        {
            $errlog = "mailexistedansldap : Chargement de l'agent par adresse mail $adressemail impossible.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        if (trim($agent->eppn()."")=="")
        {
            $errlog = "mailexistedansldap : L'EPPN de l'agent est vide => Donc on interroge LDAP.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));

            // La base de la recherche est l'ensemble du LDAP de l'établissement "LDAP_ETAB_SEARCHBASE" car l'adresse peut-être
            //  - un agent (ou=people)
            //  - un groupe (ou=group)
            //  - une liste (ou=mailingLists)
            //  .....
            $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
            $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
            $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
            $LDAP_SEARCH_BASE = $this->liredbconstante("LDAP_ETAB_SEARCHBASE");
            $LDAP_AGENT_UID_ATTR = $this->liredbconstante("LDAP_AGENT_UID_ATTR");
            $LDAP_AGENT_MAIL_ATTR = $this->liredbconstante("LDAP_AGENT_MAIL_ATTR");
            $con_ldap = ldap_connect($LDAP_SERVER);
            ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
            $filtre = "($LDAP_AGENT_MAIL_ATTR=$adressemail)";
            $dn = $LDAP_SEARCH_BASE;
            $restriction = array("$LDAP_AGENT_UID_ATTR");
            $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
            $info = ldap_get_entries($con_ldap, $sr);
            if (!isset($info[0]["$LDAP_AGENT_UID_ATTR"][0]))
            {
                $errlog = "mailexistedansldap : L'adresse mail $adressemail n'a pas pu être identifiée dans LDAP.";
                error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
                return false;
            }
        }
        return true;
    }
    
    public function prepared_query($sql, $params, $types = "")
    {
        //$stmt = $this->dbconnect->prepare($sql);
        $stmt = mysqli_prepare($this->dbconnect, $sql);
        if ($stmt === false)
        {
            var_dump ("Erreur dans le prepare de la reqête SQL $sql => " . mysqli_error($this->dbconnect));
        }
        if (count($params) > 0)
        {
            $types = $types ?: str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    public function prepared_select($sql, $params = [], $types = "")
    {
        return $this->prepared_query($sql, $params, $types)->get_result();
    }

    public function time_elapsed($text, $appelant, $reset = false)
    {
        static $last = array();
        $chiffresignificatif = 5;
        $indentation = 20;
        $numcpt = count($last);
        $now = microtime(true);
        if ($text == '')
        {
            $text = "Durée";
        }

        if ($appelant == '')
        {
            $appelant = 'Appelant inconnu';
        }

        if ($reset)
        {
            $last[$numcpt] = $now;
            echo "<b style='margin-left:" . ($indentation*$numcpt) . "px'>$appelant : $text : init (cpt $numcpt) </b><br>";
        }
        elseif (isset($last[$numcpt-1]))
        {
            $numcpt--;
            echo "<b style='margin-left:" . ($indentation*($numcpt)) . "px'>$appelant : $text => " .  number_format($now - $last[$numcpt],$chiffresignificatif, '.', '') . " secondes (cpt $numcpt) </b><br>";
            unset ($last[$numcpt]);
        }
        else
        {
            echo "<b class='redtext' >ERROR time_elapsed : On demande à afficher un compteur qui n'existe pas (cpt $numcpt) </b><br>";
        }
    }

    public function listejoursteletravailexclus($agentid, $datedebut, $momentdebut, $datefin, $momentfin, $explodettexception = true)
    {
        $datedebut = $this->formatdatedb($datedebut);
        $datefin = $this->formatdatedb($datefin);

        $listteletravail = array();
        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT, STATUT
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND DATEORIGINE >= ?
                  AND DATEORIGINE <= ?
                ORDER BY DATEORIGINE";
        $params = array($agentid,$datedebut,$datefin);

        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement des complement TT_EXCLU : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            //$errlog = "Aucun jour de télétravail n'est exclu pour l'agent " . $this->identitecomplete() . " dans la période $datedebut -> $datefin <br>";
            //error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                if ($explodettexception)
                {
                    $exception = new ttexception();
                    $exception->agentid = $agentid;
                    $exception->dateorigine = $result[0];
                    $exception->dateremplacement = $result[2];
                    $exception->momentremplacement = fonctions::MOMENT_MATIN;
                    $exception->statut = $result[4];
                    if ($result[1] == '') // On a exclu/déplacé la journée entière => On doit découper en 2 moment
                    {
                        $exception->momentorigine = ''; // fonctions::MOMENT_MATIN;
                        if ($this->formatdatedb($exception->dateorigine)==$datedebut and $momentdebut == fonctions::MOMENT_APRESMIDI)
                        {
                            // On skip cette exception car on ne doit prendre que l'après-midi du premier jour
                        }
                        else
                        {
                            $listteletravail[] = $exception;
                        }
                        $exception = new ttexception();
                        $exception->agentid = $agentid;
                        $exception->dateorigine = $result[0];
                        $exception->dateremplacement = $result[2];
                        $exception->momentremplacement = fonctions::MOMENT_APRESMIDI;
                        $exception->statut = $result[4];
                        $exception->momentorigine = ''; // fonctions::MOMENT_APRESMIDI;
                        if ($this->formatdatedb($exception->dateorigine)==$datefin and $momentfin == fonctions::MOMENT_MATIN)
                        {
                            // On skip cette exception car on ne doit prendre que le matin du dernier jour
                        }
                        else
                        {
                            $listteletravail[] = $exception;
                        }
                    }
                    else
                    {
                        $exception->momentorigine = $result[1];
                        $exception->momentremplacement = $result[3];
                        if (($this->formatdatedb($exception->dateorigine)==$datedebut and $momentdebut == fonctions::MOMENT_APRESMIDI and $exception->momentorigine == fonctions::MOMENT_MATIN)
                        or ($this->formatdatedb($exception->dateorigine)==$datefin and $momentfin == fonctions::MOMENT_MATIN and $exception->momentorigine == fonctions::MOMENT_APRESMIDI))
                        {
                            // On skip cette exception car elle n'est pas comprise dans l'interval datedébut/momentdebut -> datefin/momentfin
                        }
                        else
                        {
                            $listteletravail[] = $exception;
                        }
                    }
                }
                else  // On explose pas les ttexception 
                {
                    $exception = new ttexception();
                    $exception->agentid = $agentid;
                    $exception->dateorigine = $result[0];
                    $exception->momentorigine = $result[1] . '';
                    $exception->dateremplacement = $result[2] . '';
                    $exception->momentremplacement = $result[3] . '';
                    $exception->statut = $result[4];
                    $listteletravail[] = $exception;
                }
            }
        }
        // error_log(basename(__FILE__) . $this->stripAccents(" listejoursteletravailexclus : " . var_export($listteletravail,true)));

        return $listteletravail;
    }

    function supprjourteletravailexclu($agentid, $date, $moment)
    {
        $date = $this->formatdatedb($date);
        $errlog = '';

        $sql = "DELETE
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND DATEORIGINE = ?
                  AND ( MOMENTORIGINE = ?
                     OR MOMENTORIGINE ='') ";
        $params = array($agentid,$date, $moment);
        $query = $this->prepared_query($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans la suppression de l'exception télétravail " . $date . " - " . $moment . " : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_affected_rows($this->dbconnect) == 0)
        {
            $errlog = "Aucune exclusion de télétravail n'a été supprimée pour l'agent " . $agentid . " pour la date $date et le moment $moment";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
        }
        return $errlog;
    }

    public function estjourteletravailexclu($agentid, $date, $moment, &$statut)
    {
        $date = $this->formatdatedb($date);
        $statut = '';

        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT,STATUT
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND DATEORIGINE = ?
                  AND ( MOMENTORIGINE = ?
                     OR MOMENTORIGINE = '')";

        $params = array($agentid, $date, $moment);

        $query = $this->prepared_select($sql, $params);
        
        // error_log(basename(__FILE__) . $this->stripAccents(" SQL = $sql"));
        // error_log(basename(__FILE__) . $this->stripAccents(" Agentid = $agentid"));
        // error_log(basename(__FILE__) . $this->stripAccents(" Date = $date"));
        // error_log(basename(__FILE__) . $this->stripAccents(" Moment = $moment"));
        
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "estjourteletravailexclu => Problème SQL dans la recherche des exclusions : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            return false;
        }
        else
        {
            $result = mysqli_fetch_row($query);
            $ttexception = new ttexception;
            $ttexception->agentid = $agentid;
            $ttexception->dateorigine = $result[0];
            $ttexception->momentorigine = $result[1];
            $ttexception->dateremplacement = $result[2];
            $ttexception->momentremplacement = $result[3];
            $ttexception->statut = $result[4];
            $statut = $ttexception->statut;

            // error_log(basename(__FILE__) . $this->stripAccents(" estjourteletravailexclu : Agent = $agentid Date = $date Moment = $moment  Statut = $statut"));
            return $ttexception;
        }

        return false;
    }

    public function estjourteletravaildeplace($agentid, $date, $moment)
    {
        if ($date . "" != "")
        {
            $date = $this->formatdatedb($date);
        }
        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT, STATUT
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND STATUT = ?
                  AND DATEREMPLACEMENT = ?
                  AND ( MOMENTREMPLACEMENT = ?
                     OR MOMENTREMPLACEMENT = '')";

        $params = array($agentid,ttexception::STATUT_VALIDE,$date,$moment);

        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "estjourteletravaildeplace => Problème SQL dans le chargement de l'exception : " . $erreur;
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            return false;
        }
        else
        {
            $result = mysqli_fetch_row($query);
            $exception = new ttexception();
            $exception->agentid = $agentid;
            $exception->dateorigine = $result[0];
            $exception->momentorigine = $result[1];
            $exception->dateremplacement = $result[2];
            $exception->momentremplacement = $result[3];
            $exception->statut = $result[4];
            return $exception;
        }
    }

    function ajoutjoursteletravailexclus($agentid, $dateorigine, $momentorigine, $dateremplacement = NULL, $momentremplacement = '', $statut = ttexception::STATUT_VALIDE) :string|ttexception
    {
        $dateorigine = $this->formatdatedb($dateorigine);
        if ($dateremplacement . "" != "")
        {
            $dateremplacement = $this->formatdatedb($dateremplacement);
        }
        else
        {
            $dateremplacement = null;
        }
        $errlog = '';

        $this->supprjourteletravailexclu($agentid, $dateorigine, $momentorigine);

        $sql = 'INSERT INTO TTEXCEPTION(AGENTID, DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT, STATUT) VALUES(?, ?, ?, ?, ?, ?)';
        $params = array($agentid,$dateorigine,$momentorigine,$dateremplacement,$momentremplacement, $statut);
        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans l'enregistrement de l'exclusion : " . $erreur;
            echo $errlog;
            return $errlog;
        }
        $ttexception = new ttexception;
        $ttexception->agentid = $agentid;
        $ttexception->dateorigine = $dateorigine;
        $ttexception->momentorigine = $momentorigine;
        $ttexception->dateremplacement = $dateremplacement; 
        $ttexception->momentremplacement = $momentremplacement; 
        $ttexception->statut = $statut;
        return $ttexception;
    }

    public function typeabsencelistecomplete()
    {
        $sql = "SELECT LIBELLE,COULEUR,TYPEABSENCEID,ABSENCEIDPARENT FROM TYPEABSENCE";
        // echo "sql = " . $sql . " <br>";
        $params = array();
        $query = $this->prepared_select($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonction->typeabsenceliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $tableabsence = array();
        while ($result = mysqli_fetch_row($query)) {
            $libelle = "$result[0]";
            $couleur = "$result[1]";
            $parentid = "$result[3]";
            $tableabsence["$result[2]"] = array(
                "libelle" => $libelle,
                "couleur" => $couleur,
                "parentid" => $parentid
            );
        }

        // print_r($tablegende); echo "<br>";
        return $tableabsence;

    }

    public function showmessage($type, $message)
    {
        //var_dump($message);
        $message = preg_replace('/\s\s+/', ' ', $message);
        //var_dump($message);
        $message = preg_replace('/<br>\s*/i','<br>', $message);
        //var_dump($message);
        $oldmessage = "";
        while ($oldmessage != $message and $message != '')
        {
            $oldmessage = $message;
            $message = preg_replace('/<br><br>*/i','<br>', $message);
        }
        //var_dump($message);
        $message = preg_replace('/^<br>/i','', $message);
        $message = preg_replace('/<br>$/i','', $message);
        //var_dump($message);
        if (trim(str_ireplace('<br>', '', $message)) == '')
        {
       	    $message = '';
        }
        //var_dump($message);

        $html = '';
        if  ($message == '')
        {
            return $html;
        }
        $html = $html . "<p>";
        $html = $html . "<table class='tabmessage'><tbody>";
        $html = $html . "<tr>";
        $html = $html . "<td class='cel" . $type  . " celllogo'>";
        $path = $this->imagepath() . "/" . $type  . "_logo.png";
        list($width, $height, $imagetype) = getimagesize("$path");
        $typeimage = image_type_to_extension($imagetype,false);
        if ($typeimage===false) // Si on n'a pas pu déterminé le type d'image => On récupère l'extension du fichier
        {
            error_log(basename(__FILE__) . " " . $this->stripAccents("imagetype = $imagetype => extension non définie"));
            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
        }
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
        $html = $html . "<img class='img". $type ."' src='" . $base64 . "'>"; 
        $html = $html . "</td>";
        $html = $html . "<td class='cel" . $type  . " cellmsg'>";
        $html = $html . "$message";
        $html = $html . "</td>";
        $html = $html . "</tr>";
        $html = $html . "</tbody></table>";
        $html = $html . "</p>";
        return $html;
    }

    public function listeindemniteteletravail($datedebut, $datefin)
    {

       $datedebut = $this->formatdatedb($datedebut);
       $datefin = $this->formatdatedb($datefin);

       $listeindemteletravail = array();
       $constante = $this->liredbconstante("INDEMNITETELETRAVAIL");
       // La structure de la constante est : datedebut|datefin|montant;datedebut|datefin|montant;.....
       // IMPORTANT : Les pérodes doivent être classé par ordre de date croissant
       if (!is_null($constante))
       {
           $tabindem = explode(";",$constante);
           if (count($tabindem)>0)
           {
               foreach ($tabindem as $indemfull)
               {
                   $arrayvalue=explode("|",$indemfull);
                   if (count($arrayvalue)==3)
                   {
                       $arrayvalue[0] = $this->formatdatedb($arrayvalue[0]); // On converti la date de début en datedb
                       $arrayvalue[1] = $this->formatdatedb($arrayvalue[1]); // On converti la date de fin en datedb
                       // On ne prend que les indemnité qui sont dans l'interval $datedebut -> $datefin
                       if (($arrayvalue[0] <= $datedebut and $arrayvalue[1] >= $datedebut)
                           or ($arrayvalue[1] >= $datefin and $arrayvalue[0] <= $datefin)
                           or ($arrayvalue[0] >= $datedebut and $arrayvalue[1] <= $datefin))
                       {
                           $indemnite = array();
                           $indemnite["datedebut"] = $arrayvalue[0];
                           $indemnite["datefin"] = $arrayvalue[1];
                           $indemnite["montant"] = str_replace(',','.',$arrayvalue[2]);
                           $listeindemteletravail[] = $indemnite;
                       }
                   }
               }
           }
       }
//       if (count($listeindemteletravail)==0)
//       {
//           $indemnite["datedebut"] = '19000101';
//           $indemnite["datefin"] = '29991231';
//           $indemnite["montant"] = '0.0';
//       }
       return $listeindemteletravail;
    }

    public function recur_ksort(&$array) {
        foreach ($array as &$value) {
            if (is_array($value)) $this->recur_ksort($value);
        }
        return ksort($array);
    }

    public function synchronisationjoursferies($tabannees,&$tabferies)
    {
        $error = "";
        if (is_null($tabannees) or count($tabannees)==0)
        {
            $tabannees = array($this->anneeref());
        }
        $tabferies = array();

        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => 'https://calendrier.api.gouv.fr/jours-feries/metropole.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => 'http://proxy.univ-paris1.fr:3128/'
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
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl =>  " . $error));
            return $error;
        }
        //var_dump($json);
        $listeferies = json_decode($json, true);
        //var_dump($listeferies);
        if (is_null($listeferies))
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl =>  " . $json));
            return "Une erreur s'est produite lors de la synchronisation => la liste est vide";
        }
        $error = "";
        foreach($listeferies as $date => $nom)
        {
            $anneref = $this->anneeref($this->formatdate($date));
            if (in_array($anneref, $tabannees))
            {
                $tabferies[$anneref][$this->formatdatedb($date)] = $this->formatdate($date);
            }
        }
        // Tri récursif du tableau des jours fériés
        $this->recur_ksort($tabferies);

        foreach($tabferies as $anneeref => $tabferiesparannee)
        {
            $datestring = "";
            foreach($tabferiesparannee as $datedb => $date)
            {
                if (strlen($datestring)>0) $datestring = $datestring . ";";
                $datestring = $datestring . $datedb;
            }
            //var_dump($datestring);
            if (strlen($datestring)>0)
            {
                $constantename = 'FERIE' . $anneeref;
                $error = $this->enregistredbconstante($constantename,$datestring);
/*
                if (!$this->testexistdbconstante($constantename))
                {
                    $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES('$constantename','$datestring')";
                }
                else
                {
                    $sql = "UPDATE CONSTANTES SET VALEUR = '$datestring' WHERE NOM = '$constantename'";

                }
                //var_dump($sql);
                $return = mysqli_query($this->dbconnect, $sql);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $error = $error . "  " . $erreur;
                    error_log(basename(__FILE__) . " " . $this->stripAccents($erreur));
                }
*/
            }
        }
        return $error;
    }

    public function signatairetoarray($signatairestring)
    {
        $signatairearray = array();
        $signatairestring = trim($signatairestring);
        if (is_null($signatairestring) or strlen($signatairestring)==0)
        {
            return $signatairearray;
        }
        $tabsplit = explode(';', $signatairestring);
        foreach ($tabsplit as $infosignataire)
        {
            //var_dump($infosignataire);
            if (strlen($infosignataire)>0)
            {
                $infotab = explode('|',$infosignataire);
                // $infotab[0] = niveau du signataire
                // $infotab[1] = type de signataire
                // $infotab[2] = identifiant du signataire
                //var_dump($infotab);
                if (trim($infotab[0])=='' or trim($infotab[1])=='' or trim($infotab[2])=='')
                {
                    // Au moins un des champs est vide ! Donc on ignore
                }
                else
                {
                    $idsignataire = $infotab[1] . '_' . $infotab[2];
                    $signatairearray[$infotab[0]][$idsignataire] = array($infotab[1],$infotab[2]);
                }
            }
        }
        return $signatairearray;
    }

    public function cetsignataireaddtoarray($newlevelsignataire,$newtypesignataire,$newidsignataire,$tabsignataire)
    {
        $idsignataire = $newtypesignataire . '_' . $newidsignataire;
        $tabsignataire[$newlevelsignataire][$idsignataire] = array($newtypesignataire,$newidsignataire);
        return $tabsignataire;
    }

    public function signatairetostring($tabsignataire)
    {
        $signatairestring = '';
        if (!is_array($tabsignataire) or count($tabsignataire)==0)
        {
            return $signatairestring;
        }
        ksort($tabsignataire);
        foreach ($tabsignataire as $niveau => $tabinfos)
        {
            foreach ($tabinfos as $info)
            {
                $typesignataire = $info[0];
                $idsignataire = $info[1];
                if (strlen($signatairestring)>0) $signatairestring = $signatairestring . ";";
                if (trim($niveau)=='' or trim($typesignataire)=='' or trim($idsignataire)=='')
                {
                    // Au moins un des champs est vide ! Donc on ignore
                }
                else
                {
                    $signatairestring = $signatairestring . $niveau . '|' . $typesignataire . '|' . $idsignataire;
                }
            }
        }
        return $signatairestring;
    }
    
    public function listeadministrateursg2t()
    {
        $sql = "SELECT AGENTID FROM COMPLEMENT WHERE COMPLEMENTID = ? AND UPPER(VALEUR) = ?";
        $params = array('ESTADMIN','O');
        $adminlist = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);

        if ($erreur != "") {
            $errlog = "Fonctions->listeadministrateursg2t : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $agentid = trim($result[0]);
            $admin = new agent($this->dbconnect);
            if ($admin->load($agentid))
            {
                $adminlist[$agentid] = $admin;
            }
        }
        return $adminlist;
    }

    public function listeutilisateursspeciaux()
    {
        $tab_special_users = array();
        foreach(get_defined_constants() as $constname => $constvalue)
        {
            if (strpos($constname,'SPECIAL_USER_')===0)  // Si la constante commence par SPECIAL_USER_
            {
                $tab_special_users[$constname] = $constvalue; // Id de l'utilisateur special
            }
        }
        return $tab_special_users;
    }

    public function checksignatairecetliste(&$params, agent $agent)
    {
        if (!isset($params['xmlfilename']) or trim($params['xmlfilename'] . '') == '')
        {
            $taberrorcheckmail['prob_fichier'] = "Aucun nom de fichier XML n'est défini pour le CET (option et/ou alimentation).";
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
            return $taberrorcheckmail;
        }

        $maxniveau = 0;
        $esignature = new esignature($this->dbconnect);
        $extrainfostab = array();
        $tabsignataire = $esignature->createstep($agent,trim($params['xmlfilename'] . ''), $extrainfostab);

        if (count($tabsignataire)==0)
        {
            $taberrorcheckmail['prob_niveau'] = "Aucun niveau de signature n'a pu être déterminé.";
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
            return $taberrorcheckmail;
        }
        unset($tabsignataire);

        $params['levelextrainfos'] = $extrainfostab;
        //var_dump($params);

        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            $tempsignataires = array();
            if ($maxniveau<$niveau) $maxniveau = $niveau;

            foreach ($stepinfos->signataires as $idsignataire => $infosignataire)
            {
                if ($infosignataire[0]==fonctions::SIGNATAIRE_AGENT or $infosignataire[0]==fonctions::SIGNATAIRE_SPECIAL)
                {
                    $agentsignataire = new agent($this->dbconnect);
                    if ($agentsignataire->load($infosignataire[1]))
                    {
                        /////////
                        // ATTENTION : Si on est dans le niveau 1 => On doit mettre la vraie adresse (adresseldap) de l'agent pour que la demande soit bien à lui
                        //             même si on force l'adresse mail dans la configuration 
                        if ($niveau == '1')
                        {
                            $tempsignataires[strtolower($agentsignataire->ldapmail())] = strtolower($agentsignataire->ldapmail());
                        }
                        else
                        {
                            $tempsignataires[strtolower($agentsignataire->mail())] = strtolower($agentsignataire->mail());
                        }
                        //var_dump($stepinfos->signataires[$idsignataire]);
                        //var_dump("tempsignataires = "); var_dump($tempsignataires);
                    }
                }
                else
                {
                    echo $this->showmessage(fonctions::MSGERROR,"TYPE DE SIGNATAIRE inconnu !");
                }
                unset($stepinfos->signataires[$idsignataire]);
                unset($agentsignataire);
            }
            $stepinfos->signataires = $tempsignataires;
        }

        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            //var_dump("stepinfos->signataires (avant fusion) = "); var_dump($stepinfos->signataires);
            $stepinfos->signataires = array_merge($stepinfos->signataires,$this->explosemail($stepinfos->signataires));
            // On vérifie que chaque adresse mail existe pas dans LDAP
            foreach($stepinfos->signataires as $mailadress)
            {
                if (!$this->mailexistedansldap($mailadress))
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" L'adresse mail $mailadress n'est pas connue de LDAP => On l'ignore"));
                    unset($stepinfos->signataires[$mailadress]);
                }
            }
            //var_dump("stepinfos->signataires (après fusion) = "); var_dump($stepinfos->signataires);
        }

        // On vérifie qu'on a bien au moins un signataire dans chaque niveau
        $taberrorcheckmail = array();
        $tabniveauok = array();
        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            // S'il n'y a pas de signataire dans le niveau => Il y a un problème
            if (count($stepinfos->signataires) == 0)
            {
                $taberrorcheckmail["prob_niveau_$niveau"] = "Le niveau de signature $niveau n'est pas correctement renseigné";
            }
            else
            {
                // On a des signataires, mais on doit vérifier que toutes les adresses mail existent bien dans LDAP
                foreach($stepinfos->signataires as $mailadress)
                {
                    // Si l'adresse n'existe pas dans LDAP
                    if (!$this->mailexistedansldap($mailadress))
                    {
                        $taberrorcheckmail[$mailadress] = "l'adresse mail $mailadress n'est pas connue de LDAP";
                    }
                    else
                    {
                        $tabniveauok[$niveau] = "On a un agent Ok dans le niveau $niveau";
                    }
                }
            }
        }
        //var_dump($tabniveauok);
        //var_dump("count(tabniveauok) = " . count($tabniveauok));
        //var_dump("maxniveau = " . $maxniveau);

        if (count($tabniveauok)!=count($params['levelextrainfos']))
        {
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
        }
        $erreur = $this->setsignatureposition($params);
        if ($erreur != '')
        {
            $taberrorcheckmail['erreur_position_signature'] = $erreur;
        }
        return $taberrorcheckmail;
    }

    public function checksignataireteletravailliste(&$params, agent $agent)
    {
        if (!isset($params['xmlfilename']) or trim($params['xmlfilename'] . '') == '')
        {
            $taberrorcheckmail['prob_fichier'] = "Aucun nom de fichier XML n'est défini pour le télétravail.";
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
            return $taberrorcheckmail;
        }

        $maxniveau = 0;
        $esignature = new esignature($this->dbconnect);
        $extrainfostab = array();
        $tabsignataire = $esignature->createstep($agent,trim($params['xmlfilename'] . ''), $extrainfostab);
        if (count($tabsignataire)==0)
        {
            $taberrorcheckmail['prob_niveau'] = "Aucun niveau de signature n'a pu être déterminé.";
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
            return $taberrorcheckmail;
        }
        unset($tabsignataire);

        $params['levelextrainfos'] = $extrainfostab;
        //var_dump($params);

        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            $tempsignataires = array();
            if ($maxniveau<$niveau) $maxniveau = $niveau;

            foreach ($stepinfos->signataires as $idsignataire => $infosignataire)
            {
                //var_dump($idsignataire); var_dump($infosignataire);
                if ($infosignataire[0]==fonctions::SIGNATAIRE_AGENT or $infosignataire[0]==fonctions::SIGNATAIRE_SPECIAL)
                {
                    $agentsignataire = new agent($this->dbconnect);
                    if ($agentsignataire->load($infosignataire[1]))
                    {
                        /////////
                        // ATTENTION : Si on est dans le niveau 1 => On doit mettre la vraie adresse (adresseldap) de l'agent pour que la demande soit bien à lui
                        //             même si on force l'adresse mail dans la configuration 
                        if ($niveau == '1')
                        {
                            $tempsignataires[strtolower($agentsignataire->ldapmail())] = strtolower($agentsignataire->ldapmail());
                        }
                        else
                        {
                            $tempsignataires[strtolower($agentsignataire->mail())] = strtolower($agentsignataire->mail());
                        }
                        //var_dump($stepinfos->signataires[$idsignataire]);
                        //var_dump("tempsignataires = "); var_dump($tempsignataires);
                    }
                }
                else
                {
                    echo $this->showmessage(fonctions::MSGERROR,"TYPE DE SIGNATAIRE inconnu !");
                }
                unset($stepinfos->signataires[$idsignataire]);
                unset($agentsignataire);
            }
            $stepinfos->signataires = $tempsignataires;
        }

        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            //var_dump("stepinfos->signataires (avant fusion) = "); var_dump($stepinfos->signataires);
            $stepinfos->signataires = array_merge($stepinfos->signataires,$this->explosemail($stepinfos->signataires));
            // On vérifie que chaque adresse mail existe pas dans LDAP
            foreach($stepinfos->signataires as $mailadress)
            {
                if (!$this->mailexistedansldap($mailadress))
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" L'adresse mail $mailadress n'est pas connue de LDAP => On l'ignore"));
                    //var_dump("L'adresse mail $mailadress n'est pas connue de LDAP => On l'ignore");
                    unset($stepinfos->signataires[$mailadress]);
                }
            }
            //var_dump("stepinfos->signataires (après fusion) = "); var_dump($stepinfos->signataires);
        }

        /////////////////////////////////////////////////////////////
        // On va supprimer le demandeur de tous les niveaux de signature, sauf s'il est le seul signataire dans un niveau
        // On parcourt tous les niveaux
        // Pour le niveau 1 (demandeur) => On mémorise les demandeurs
        // Pour les niveaux suivants => On regarde si les demandeurs sont dans le niveau et s'il y a d'autres. Oui => On supprime les demandeurs. Non => On les laisse
        error_log(basename(__FILE__) . $this->stripAccents(" On va supprimer le demandeur de tous les niveaux (sauf s'il est tout seul dans un niveau)"));
        $tabmaildemandeur = array();
        // On cherche le premier niveau pour mémoriser les demandeurs
        if (isset($params['levelextrainfos']['1']->signataires))
        {
            // Le tableau des demandeurs est en fait le tableau des signataires du niveau 1
            $tabmaildemandeur = $params['levelextrainfos']['1']->signataires;
        }
        // On parcours ensuite l'ensemble des niveaux (en ignorant le 1er puisque déjà traité)
        // et on supprime l'ensemble des adresses mails contenues dans le tableau des demandeurs (en théorie 1 seule adresse mais....)
        // Sauf si le tableau des demandeurs est le même que le tableau des signataires du niveau
        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            // Si on est au premier niveau, on passe au suivant ca déjà traité 
            if ($niveau=='1')
            {
                continue;
            }
            // Si on est là c'est qu'on est dans un niveau > 1
            // On doit vérifier ques les deux tableaux sont différents sinon, il n'y aura plus personne dans le niveau
            // Attention : tableau1 == tableau2 => vérifie que les associations clés/valeurs sont bien identiques dans les deux tableaux, peu importe l'ordre des clés et les types de clés et valeurs
            //             tableau1 === tableau2 => vérifie que les associations clés/valeurs sont bien identiques dans les deux tableaux, dans le même ordre et du même type
            if ($tabmaildemandeur != $stepinfos->signataires)
            {
                foreach ($tabmaildemandeur as $demandeurmail)
                {
                    // Pas besoin de vérifier s'il existe ou pas car unset d'un élément inexistant ne fait rien
                    error_log(basename(__FILE__) . $this->stripAccents(" On va supprimer (s'il existe) le demandeur dand le niveau $niveau => clé = " . $demandeurmail));
                    // var_dump("On va supprimer (s'il existe) le demandeur dand le niveau $niveau => clé = " . $demandeurmail);
                    unset($stepinfos->signataires[$demandeurmail]);
                }
            }
        }
        ///////////////////////////////////////////////////////////////
        
        ///////////////////////////////////////////
        // Si la 3e étape est le même que la 2e et que la 3e étape est facultative => On supprime la 3e étape
        // Remarque : On pourrait le faire pour tous les niveaux >= 3 et qui sont facultatifs
        // A voir pour réaliser cette évolution
        $levelsource = '2';
        $leveltocheck = '3';
        // Si le 2e et le 3e niveau de signature existe
        if (isset($params['levelextrainfos'][$levelsource]) and isset($params['levelextrainfos'][$leveltocheck]))
        {
            // On vérifie que le niveau à controler est bien facultatif sinon on ne fait rien
            if ($params['levelextrainfos'][$leveltocheck]->obligatoire==false)
            {
                // On vérifie si les tableaux sont identiques
                // Attention : tableau1 == tableau2 => vérifie que les associations clés/valeurs sont bien identiques dans les deux tableaux, peu importe l'ordre des clés et les types de clés et valeurs
                //             tableau1 === tableau2 => vérifie que les associations clés/valeurs sont bien identiques dans les deux tableaux, dans le même ordre et du même type
                if ($params['levelextrainfos'][$levelsource]->signataires == $params['levelextrainfos'][$leveltocheck]->signataires)
                {
                    // Donc les tableaux contiennent les mêmes valeurs et sont identiques
                    error_log(basename(__FILE__) . $this->stripAccents(" Les signataires de la 3e étape sont les mêmes que la 2e => On va supprimer cette étape "));
                    unset($params['levelextrainfos'][$leveltocheck]);
                    // Il faut ensuite remonter toutes les étapes de signatures (à partir de du niveau $leveltocheck+1) d'un cran pour assurer la continuité des étapes
                    foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
                    {
                        // Si le niveau est avant ou égal au $leveltocheck => On ignore car on ne doit pas le remonter
                        if ($niveau <= $leveltocheck)
                        {
                            continue;
                        }
                        // On doit donc traiter le niveau en cours
                        $previouslevel = ($niveau-1) . '';
                        $params['levelextrainfos'][$previouslevel] = $params['levelextrainfos'][$niveau];
                        unset($params['levelextrainfos'][$niveau]);
                    }
                    $maxniveau--;
                    error_log(basename(__FILE__) . $this->stripAccents(" Le niveau maximal est maintenant : $maxniveau => On est dans un circuit télétravail sans N+2"));
                }
            }
        }
        //var_dump($params['levelextrainfos']);
        //////////////////////////////////////////
        
        // On vérifie qu'on a bien au moins un signataire dans chaque niveau
        $taberrorcheckmail = array();
        $tabniveauok = array();
        foreach ($params['levelextrainfos'] as $niveau => $stepinfos)
        {
            // S'il n'y a pas de signataire dans le niveau => Il y a un problème
            if (count($stepinfos->signataires) == 0)
            {
                $taberrorcheckmail["prob_niveau_$niveau"] = "Le niveau de signature $niveau n'est pas correctement renseigné";
            }
            else
            {
                // On a des signataires, mais on doit vérifier que toutes les adresses mail existent bien dans LDAP
                foreach($stepinfos->signataires as $mailadress)
                {
                    // Si l'adresse n'existe pas dans LDAP
                    if (!$this->mailexistedansldap($mailadress))
                    {
                        $taberrorcheckmail[$mailadress] = "L'adresse mail $mailadress n'est pas connue de LDAP";
                    }
                    else
                    {
                        $tabniveauok[$niveau] = "On a un agent Ok dans le niveau $niveau";
                    }
                }
            }
        }
        //var_dump($tabniveauok);
        //var_dump("count(tabniveauok) = " . count($tabniveauok));
        //var_dump("maxniveau = " . $maxniveau);

        if (count($tabniveauok)!=count($params['levelextrainfos']))
        {
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
        }
        $erreur = $this->setsignatureposition($params);
        if ($erreur != '')
        {
            $taberrorcheckmail['erreur_position_signature'] = $erreur;
        }
        return $taberrorcheckmail;
    }

    public function setsignatureposition(array &$params) :string
    {
        //////////////////////////////////////////////////
        // On défini la position de chacune des signatures
        $erreur = '';
        // if (false)
        if (true)
        {
            $signrequestparams = array();
            foreach ($params['levelextrainfos'] as $extrainfos)
            {
                if (!is_null($extrainfos->signatureposition))
                {
                    $signrequestparamsinfo = array();
                    $signrequestparamsinfo['xPos'] = $extrainfos->signatureposition->x;
                    $signrequestparamsinfo['yPos'] = $extrainfos->signatureposition->y;
                    $signrequestparamsinfo['signPageNumber'] = $extrainfos->signatureposition->page;
                    $signrequestparams[] = $signrequestparamsinfo;
                    // error_log(basename(__FILE__) . $this->stripAccents(" Création des positions à partir des extrainfos " . var_export($signrequestparamsinfo,true)));
                }
            }
            if (count($signrequestparams)>0)
            {
                $params['signRequestParamsJsonString'] = json_encode($signrequestparams); //,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE)
            }
            else
            {
                $erreur = "Aucune position de signature n'est définie.";
                error_log(basename(__FILE__) . $this->stripAccents(" $erreur"));
            }
        }
        // Fin de la position de chacune des signatures
        ///////////////////////////////////////////////////////
        return $erreur;
    }
  
    
    // $maillist est un tableau d'adresse mail avec l'adresse mail en clé et en valeur
    public function explosemail(array $maillist) : array
    {
        //var_dump($maillist);
        
        $paramlist = '';
        foreach ($maillist as $mailadress)
        {
            $paramlist = $paramlist . 'id[]=' . $mailadress . '&';
        }
        $wsgroupURL = $this->liredbconstante('WSGROUPURL');

        // On appelle WSGroups qui se charge de lister tous les mails correspondants au paramètres
        // https://wsgroups.etab.fr/searchUserTrusted?id[]=jonh.doe@etab.fr&id[]=mail_group@etab.fr&allowInvalidAccounts=all&allowRoles=true&attrs=member-all,mail
        $curl = curl_init();
        $params_string = "";
        $wsgroupsquery = "$wsgroupURL/searchUserTrusted?$paramlist&allowInvalidAccounts=all&allowRoles=true&attrs=member-all,mail";
        //var_dump("La reqète à WSGroups = $wsgroupsquery");
        $opts = [
            CURLOPT_URL => "$wsgroupsquery",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        $dbconstante = "WSGROUPS_SECRET_TOKEN";
        if ($this->testexistdbconstante($dbconstante))
        {
            $accessToken = trim($this->liredbconstante($dbconstante));
            if (strlen($accessToken)>0)
            {
                ///////////////////////////////////////////////////////////
                //// ATTENTION : TOKEN DE BYPASS A METTRE EN PARAMETRE DANS LE CONFIG
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer $accessToken"));
                ///////////////////////////////////////////////////////////
            }
        }
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
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl (récup searchUserTrusted agent " . $mailadress .  ") =>  " . $error));
        }
        $response = json_decode($json, true);

        //echo "Niveau = $niveau <br>";
        //echo print_r($response,true);
        
        //$response =  array_change_key_case((array)$response, CASE_LOWER); // array_map('strtolower', $response);
        
        $dbconstante = "FORCE_AGENT_MAIL";
        static $forcemail = null;
        if (is_null($forcemail))
        {
            if ($this->testexistdbconstante($dbconstante))
            {
                $forcemail = trim($this->liredbconstante($dbconstante));
            }
            else
            {
                $forcemail = '';
            }
        }
        foreach ((array)$response as $agentinfo)
        {
            if (isset($agentinfo["member-all"]))
            {
                // C'est un groupe qui est explosé => On récupère les mails des membres
                foreach ($agentinfo["member-all"] as $agentinfo)
                {
                    // Si une adresse de forçage est définie
                    if (strlen(trim($forcemail . ''))>0)
                    {
                        $infoadresse = strtolower($forcemail);
                        $returnmail[$infoadresse] = $infoadresse;
                    }
                    // Sinon on va récupérer l'adresse mail du membre (si l'adresse mail est définie)
                    elseif (isset($agentinfo["mail"]))
                    {
                        $infoadresse = strtolower($agentinfo["mail"]);
                        $returnmail[$infoadresse] = $infoadresse;
                    }
                    else // Impossible de réucpérer l'adresse mail du membre à partir de LDAP
                    {
                        if (isset($agentinfo["key"]))
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" Il n'y a pas d'adresse mail pour " . $agentinfo["key"] .  ""));
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" Il n'y a pas d'adresse mail pour " . print_r($agentinfo,true) .  ""));
                        }
                    }
                }
            }
            else
            {
                // On est dans le cas d'une adresse mail d'un agent => On ne modifie rien
                // C'est un agent => On récupère l'adresse mail
                if (isset($agentinfo["mail"]))
                {
                    // On remet l'adresse mail passée en paramètre
                    $returnmail[strtolower($mailadress)] = strtolower($mailadress);
                }
                else
                {
                    // LDAP n'a pas pu fournir d'adresse mail pour cet agent 
                    error_log(basename(__FILE__) . $this->stripAccents(" Il n'y a pas d'adresse mail dans LDAP pour " . $mailadress . ""));
                }
            }
        }
        //var_dump($returnmail);
        
        return $returnmail;
    }


    public function listeagentsavecaffectation($namefirst = true, $checkstructure = false)
    {
        $listeagent = array();
        $sql = "SELECT AGENT.AGENTID, AGENT.NOM, AGENT.PRENOM 
                FROM AGENT, AFFECTATION
                WHERE AGENT.AGENTID = AFFECTATION.AGENTID
                  AND CURDATE() BETWEEN AFFECTATION.DATEDEBUT AND AFFECTATION.DATEFIN
                  AND AFFECTATION.OBSOLETE = 'N' ";
        if ($checkstructure)
        {
            $sql = $sql . " AND TRIM(AGENT.STRUCTUREID) <> '' ";
        }
        $sql = $sql . " ORDER BY AGENT.NOM,AGENT.PRENOM,AGENT.AGENTID";
        $query_agent = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "fonctions->listeagentavecaffectation : Erreur SELECT FROM AGENT => $erreur_requete \n";
        }
        else
        {
            while ($result = mysqli_fetch_row($query_agent))
            {
                if ($namefirst)
                {
                    $listeagent[$result[0]] = $result[1] . " " . $result[2];
                }
                else
                {
                    $listeagent[$result[0]] = $result[1] . " " . $result[2];
                }
            }
        }
        return $listeagent;
    }

    public function listeagentsavecjourscomplementaires($anneeref)
    {
        $listeagent = array();
        $sql = "SELECT SOLDE.AGENTID,AGENT.NOM,AGENT.PRENOM
                FROM SOLDE,AGENT 
                WHERE SOLDE.AGENTID=AGENT.AGENTID 
                  AND SOLDE.TYPEABSENCEID='" . recuperation::SUPP_ID . trim($anneeref) . "'
                  AND SOLDE.DROITAQUIS>0";
        $query_agent = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "fonctions->listeagentsavecjourscomplementaires : Erreur SELECT FROM SOLDE,AGENT  => $erreur_requete \n";
        }
        else
        {
            while ($result = mysqli_fetch_row($query_agent))
            {
                $listeagent[$result[0]] = $result[1] . " " . $result[2];
            }
        }
        return $listeagent;
    }

    public function listeagentsavecrecuperation($date)
    {
        $anneeref = $this->anneeref($this->formatdate($date));
        $debutperiode = $anneeref . $this->debutperiode();
        $finperiode = ($anneeref+1) . $this->finperiode();
        $listeagent = array();
        $sql = "SELECT COMMENTAIRECONGE.AGENTID,AGENT.NOM,AGENT.PRENOM
                FROM COMMENTAIRECONGE, AGENT
                WHERE COMMENTAIRECONGE.AGENTID=AGENT.AGENTID 
                  AND COMMENTAIRECONGE.TYPEABSENCEID='" . recuperation::RECUP_ID . "'
                  AND COMMENTAIRECONGE.DATEAJOUTCONGE BETWEEN '$debutperiode' AND '$finperiode'";
        //var_dump('SQL = ' . $sql);
        $query_agent = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "fonctions->listeagentsavecrecuperation : Erreur SELECT FROM COMMENTAIRECONGE => $erreur_requete \n";
        }
        else
        {
            //var_dump("j'ai des résultats");
            while ($result = mysqli_fetch_row($query_agent))
            {
                //var_dump("Id = " . $result[0]);
                $listeagent[$result[0]] = $result[1] . " " . $result[2];
            }
        }
        return $listeagent;
    }


    public function listeagentsg2t($namefirst = true, $fulllist = true)
    {
        $listeagent = array();
        $sql = "SELECT AGENTID,NOM,PRENOM FROM AGENT ";
        $listspecialuser = $this->listeutilisateursspeciaux();
        if (count($listspecialuser)>0)
        {
            $sql = $sql . " WHERE AGENTID NOT IN (";
            $separateur = '';
            foreach ($listspecialuser as $idspecialuser)
            {
                $sql = $sql . $separateur . "'$idspecialuser'";
                $separateur = ",";
            }
            $sql = $sql . ") ";

        }
        if (!$fulllist)
        {
            // $sql = $sql . " AND AGENTID IN (SELECT AGENTID FROM AFFECTATION WHERE DATEFIN > CURDATE() - INTERVAL " . $this->margesynchro() . " YEAR)";
            // $sql = $sql . " AND AGENTID IN (SELECT AGENTID FROM AFFECTATION WHERE DATEFIN >= (" . $this->anneeref() .  $this->debutperiode() . " - INTERVAL " . $this->margesynchro() . " YEAR))";
            $sql = $sql . " AND AGENTID IN (SELECT AGENTID FROM AFFECTATION WHERE DATEFIN >= " . ($this->anneeref()-$this->margesynchro()) .  $this->debutperiode() . ")";
        }
        $sql = $sql . " ORDER BY NOM,PRENOM,AGENTID";
        //var_dump($sql);
        $query_agent = mysqli_query($this->dbconnect, $sql);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            echo "fonctions->listeagentsg2t : Erreur SELECT FROM AGENT => $erreur_requete \n";
        }
        else
        {
            while ($result = mysqli_fetch_row($query_agent))
            {
                if ($namefirst)
                {
                    $listeagent[$result[0]] = $result[1] . " " . $result[2] . " (" . $result[0] . ")";
                }
                else
                {
                    $listeagent[$result[0]] = $result[1] . " " . $result[2] . " (" . $result[0] . ")";
                }
            }
        }
        return $listeagent;
    }

    /**
     *
     * @deprecated
     * @param
     *            $esignatureid
     * @return string 
     */
    public function deleteesignaturedocument($esignatureid)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        $erreur = '';

        if (!preg_match ("/^[0-9]+/", $esignatureid))
        {
            //echo "Pas de chiffres<br>";
            $erreur = "Suppression du document impossible : L'identifiant eSignature n'est pas valide : " . $esignatureid;
            error_log(basename(__FILE__) . " " . $this->stripAccents(" $erreur"));
            return $erreur;
        }
        $eSignature_url = $this->liredbconstante("ESIGNATUREURL"); 

        $esignature = new esignature($this->dbconnect);
        $erreur = $esignature->delete_signrequest($esignatureid);

        return $erreur;
    }

    public function listeconventionteletravailavecstatut($statut, $apresdatefin = null)
    {
        $tabconvention = array();
        $sql = "SELECT TELETRAVAILID
                FROM TELETRAVAIL
                WHERE STATUT = ? ";

        if (!is_null($apresdatefin))
        {
            $apresdatefin = $this->formatdatedb($apresdatefin);
            $sql = $sql . " AND DATEFIN >= ?";
            $params = array($statut,$apresdatefin);
        }
        else
        {
            $params = array($statut);
        }

        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "listeconventionteletravailavecstatut => Problème SQL dans le chargement des conventions télétravail : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) > 0)
        {
            while ($result = mysqli_fetch_row($query))
            {
                $teletravail = new teletravail($this->dbconnect);
                $teletravail->load($result[0]);
                $tabconvention[$result[0]] = $teletravail;
            }
        }
        return $tabconvention;
    }

    public function listettexceptionavecstatut($statut, $apresdateorigine = null)
    {
        $listettexception = array();
        $sql = "SELECT AGENTID, DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT, STATUT
                FROM TTEXCEPTION
                WHERE STATUT = ?";

        if (!is_null($apresdateorigine))
        {
            $apresdateorigine = $this->formatdatedb($apresdateorigine);
            $sql = $sql . " AND DATEORIGINE >= ?";
            $params = array($statut,$apresdateorigine);
        }
        else
        {
            $params = array($statut);
        }

        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "listettexceptionavecstatut => Problème SQL dans le chargement des exceptions de télétravail : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) > 0)
        {
            while ($result = mysqli_fetch_row($query))
            {
                    $exception = new ttexception();
                    $exception->agentid = $result[0] . '';
                    $exception->dateorigine = $result[1] . '' ;
                    $exception->momentorigine = $result[2] . '';
                    $exception->dateremplacement = $result[3] . '';
                    $exception->momentremplacement = $result[4] . '';
                    $exception->statut = $result[5] . '';
                    $listettexception[$exception->agentid . "_" . $exception->id()] = $exception;
            }
        }
        return $listettexception;
    }

    public function synchronisealimentationCET($esignatureid)
    {
        $status = "";
        $reason = "";
        $esignature_status = '';
        error_log(basename(__FILE__) . $this->stripAccents(" On va modifier le statut de la demande =>  " . $esignatureid));

        if (trim($esignatureid . "")=="")
        {
            $error = "L'identifiant eSignature est vide => Pas de traitement";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $error));
            $result_json = array('status' => 'Ok', 'description' => '');
            error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
            return $result_json;
        }

        $alimentationCET = new alimentationCET($this->dbconnect);
        $erreur = $alimentationCET->load($esignatureid);
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos de la demande " . $esignatureid . " => Erreur = " . $erreur));
            $result_json = array('status' => 'Error', 'description' => $erreur);
            return $result_json;
        }

        $esignature = new esignature($this->dbconnect);
        // Si le statut est passé dans l'appel du WS (=> Appel automatique de eSignature)

        if (isset($_GET['status']))
        {
            $esignature_status = trim($_GET['status']);
            error_log(basename(__FILE__) . $this->stripAccents(" Le statut est dans le GET => $esignature_status"));
        }
        // On récupère le statut à partir de eSignature
        else
        {
            $esignature_status = $esignature->get_signrequest_status($esignatureid);
        }

        // Si la récupération du statut à partir de eSignature a échouée
        if ($esignature_status===false)
        {
            $error = "Erreur dans eSignature : Impossible de récupérer le statut du document";
            error_log(basename(__FILE__) . $this->stripAccents(" $error"));
            // Si j'ai une erreur dans mon appel CURL on ne doit rien faire => Statut = '' et on crée le $result_json
            $result_json = array('status' => 'Error', 'description' => $error);
            return $result_json;
        }

        $esignature_status = str_replace("'", "", $esignature_status);
        error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$esignature_status'"));

        // Si le statut de eSignature n'a pas pu être récupéré on ne fait aucune modification du statut de G2T
        if ($esignature_status == '')
        {
            $error = " Pas de statut récupéré de eSignature => On ne modifie pas le statut dans G2T";
            error_log(basename(__FILE__) . $this->stripAccents($error));
            $result_json = array('status' => 'Ok', 'description' => $error);
            return $result_json;
        }

        switch (strtolower($esignature_status))
        {
            //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
            case 'draft' :
            case 'pending' :
            case 'signed' :
            case 'checked' :
            case 'cleaned' :
                $status = alimentationCET::STATUT_EN_COURS;
                break;

            case 'refused':
                $status = alimentationCET::STATUT_REFUSE;
                error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$esignature_status' => On va chercher le commentaire"));
                // Récupération du commentaire d'esignature
                if (isset($_GET['comment']))
                {
                    $reason = trim($_GET['comment']);
                    error_log(basename(__FILE__) . $this->stripAccents(" Le motif est dans le GET => $reason"));
                }
                else
                {
                    $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                    if (is_array($comments))
                    {
                        $reason = end($comments); //implode(" ",$comments);
                        error_log(basename(__FILE__) . $this->stripAccents(" Le motif est dans un post-it : $reason"));
                    }
                }
                break;

            case 'completed' :
                $status = optionCET::STATUT_VALIDE;
                break;

            case 'deleted' : 
            case 'canceled' :
            case '' :
                $status = alimentationCET::STATUT_ABANDONNE;
                break;

            case 'fully-deleted' :
                if (!in_array($alimentationCET->statut(),array(alimentationCET::STATUT_ABANDONNE,alimentationCET::STATUT_REFUSE, alimentationCET::STATUT_VALIDE)))
                {
                    $status = alimentationCET::STATUT_ABANDONNE;
                }
                else
                {
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            case 'exported' :
            case 'archived' :
                // Si la demande est déjà refusé on va voir si le motif du refus a changé
                if ($alimentationCET->statut() == alimentationCET::STATUT_REFUSE)
                {
                    // On récupère les commentaires sur le refus
                    $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                    if (is_array($comments))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" On récupère les motifs de refus pour voir s'il a changé"));  
                        $reason = end($comments); //implode(" ",$comments);
                    }
                    if ($alimentationCET->motif() != $reason)
                    {
                        $alimentationCET->motif($reason);
                        $erreur = $alimentationCET->store();
                        if ($erreur != "")
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la modification du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" La modification du motif du droit d'option " . $esignatureid . " est Ok => Pas d'erreur"));
                            $result_json = array('status' => 'Ok', 'description' => $erreur);
                        }
                        return $result_json;
                    }
                    else
                    {
                        $erreur = '';
                        error_log(basename(__FILE__) . $this->stripAccents(" La demande est refusée et le motif inchangé. On ne fait rien => Pas d'erreur"));
                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                    }
                    return $result_json;
            }
                elseif (!in_array($alimentationCET->statut(),array(alimentationCET::STATUT_ABANDONNE,alimentationCET::STATUT_REFUSE, alimentationCET::STATUT_VALIDE)))
                {
                    $statutlist = $esignature->get_signrequest_stepstatus($esignatureid);
                    if (is_string($statutlist))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" $statutlist"));
                        $result_json = array('status' => 'Error', 'description' => $statutlist);
                        return $result_json;
                    }
                    foreach($statutlist as $stepindex => $stepstatut)
                    {
                        if (strcasecmp($stepstatut,'refused')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est refusée => On marque comme refusé"));  
                            $status = alimentationCET::STATUT_REFUSE;
                            // On récupère les commentaires sur le refus
                            $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                            if (is_array($comments))
                            {
                                error_log(basename(__FILE__) . $this->stripAccents(" On récupère les motifs de refus"));  
                                $reason = end($comments); //implode(" ",$comments);
                            }
                            // On a trouvé un refus de signature => On sort de la boucle
                            break;
                        }
                        elseif (strcasecmp($stepstatut,'signed')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est signée => On marque comme validé")); 
                            // Pour le moment, la demande est signée
                            $status = alimentationCET::STATUT_VALIDE;
                        }
                    }
                    // Si on n'a pas pu identifié l'ancien statut eSignature de la demande => On dit que tout est ok.
                    if ($status == "")
                    {
                        $erreur = "";
                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                        return $result_json;
                    }
                }
                // Le statut G2T est déjà dans un état final (<=> pas EN_COURS) => On n'a rien fait. Tout ok
                else
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" La convention de télétravail est déjà avec un statut correct => Pas de traitement")); 
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            default :
                $response = json_decode($esignature_status, true);
                if (isset($response['error'])) $erreur = $response['error']; else $erreur = '';
                $erreur = "Erreur dans la réponse de eSignature => eSignatureid = " . $esignatureid . " erreur => $erreur esignature_status => $esignature_status";
                error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
                return $result_json;
                break;
        }
        // En théorie, ici, on a forcément un statut G2T défini
        if ($status == "")
        {
            $erreur = "Impossible de déterminer le statut G2T de la demande => eSignatureid = " . $esignatureid . " esignature_status => $esignature_status";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
            $result_json = array('status' => 'Error', 'description' => $erreur);
            return $result_json;
        }

        // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
        if ($status == $alimentationCET->statut())
        {
            $erreur = '';
            error_log(basename(__FILE__) . $this->stripAccents(" La demande a déjà un statut $status. On ne fait rien => Pas d'erreur"));
            $result_json = array('status' => 'Ok', 'description' => $erreur);
            return $result_json;
        }

        // Ajout d'un contrôle qui interdit de modifier le statut de la demande, les informations de solde si la demande est déjà VALIDE, ABANDONNE ou REFUSE
        if (!in_array($alimentationCET->statut(), array(alimentationCET::STATUT_VALIDE, alimentationCET::STATUT_ABANDONNE, alimentationCET::STATUT_REFUSE )))
        {
            //if (($status == $alimentationCET::STATUT_VALIDE) and ($alimentationCET->statut() == $alimentationCET::STATUT_EN_COURS or $alimentationCET->statut() == $alimentationCET::STATUT_PREPARE))
            if (($status == alimentationCET::STATUT_VALIDE) and (in_array($alimentationCET->statut(), array(alimentationCET::STATUT_EN_COURS, alimentationCET::STATUT_PREPARE))))
            {
                $agent = new agent($this->dbconnect);
                $agentid = $alimentationCET->agentid();
                error_log(basename(__FILE__) . $this->stripAccents(" L'agent id =  " . $agentid ));
                $agent->load($agentid);
                $cet = new cet($this->dbconnect);
                $erreur = $cet->load($agentid);
                if ($erreur <> '')
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Pas de CET pour cet agent : " . $agent->identitecomplete() ." ! On le crée. "));
                    unset($cet);
                    $cet = new cet($this->dbconnect);
                    $cet->agentid($agentid);
                    $cet->cumultotal('0');
                    $cet->cumulannuel($this->anneeref(),'0');
                    $cet->datedebut('01/01/1900');   //date('d/m/Y'));
                    $erreur = $cet->store();
                    unset($cet);
                    $cet = new cet($this->dbconnect);
                    $cet->load($agentid);
                }
                $cet->cumultotal( $alimentationCET->valeur_f() + $cet->cumultotal()) ;
                error_log(basename(__FILE__) . $this->stripAccents(" Le solde du CET sera après enregistrement de " . $cet->cumultotal()));
                $cumulannuel = $cet->cumulannuel($this->anneeref());
                $cumulannuel = $cumulannuel + $alimentationCET->valeur_f();
                $cet->cumulannuel($this->anneeref(),$cumulannuel);
                $cet->store();

                $solde = new solde($this->dbconnect);
                //error_log(basename(__FILE__) . $this->stripAccents(" Le type de congés est " . $alimentationCET->typeconges()));
                $solde->load($agentid, $alimentationCET->typeconges());
                //error_log(basename(__FILE__) . $this->stripAccents(" Le solde droitpris est avant : " . $solde->droitpris() . " et valeur_f = " . $alimentationCET->valeur_f()));
                $new_solde = $solde->droitpris()+$alimentationCET->valeur_f();
                $solde->droitpris($new_solde);
                //error_log(basename(__FILE__) . $this->stripAccents(" Le solde droitpris est après : " . $solde->droitpris()));
                error_log(basename(__FILE__) . $this->stripAccents(" Le solde " . $solde->typelibelle() . " sera après enregistrement de " . ($solde->droitaquis() - $solde->droitpris())));
                $solde->store();

                // Ajouter dans la table des commentaires la trace de l'opération
                $agent->ajoutecommentaireconge($alimentationCET->typeconges(),($alimentationCET->valeur_f()*-1),"Retrait de jours pour alimentation CET");

                $erreur = $alimentationCET->storepdf();
                if ($erreur != '')
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la récupération du PDF de la demande " . $esignatureid . " => Erreur = " . $erreur));
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                }
            }
            else  // Le statut de la demande n'est pas signée
            {
                error_log(basename(__FILE__) . $this->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $alimentationCET->agentid()));
            }

            error_log(basename(__FILE__) . $this->stripAccents(" Mise à jour de la demande d'alimentation du CET $esignatureid de l'agent " . $alimentationCET->agentid()));
            $alimentationCET->statut($status);
            if ($status <> alimentationCET::STATUT_ABANDONNE)
            {
                $alimentationCET->motif($reason);
            }
            $erreur = $alimentationCET->store();
            if ($erreur != "")
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de l'enregistrement de la demande " . $esignatureid . " => Erreur = " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Traitement OK de la demande " . $esignatureid . " => Pas d'erreur"));
                $result_json = array('status' => 'Ok', 'description' => $erreur);
            }
        }
        else
        {
            $erreur = "Incohérence lors de la modification du statut de la demande : La demande est " . $alimentationCET->statut() . " et on veut la passer $status";
            error_log(basename(__FILE__) . $this->stripAccents(" $erreur"));
            $result_json = array('status' => 'Error', 'description' => $erreur);
        }
        error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
        return $result_json;
    }

    public function synchroniseoptionCET($esignatureid)
    {
        $status = "";
        $reason = "";
        $esignature_status = '';
        error_log(basename(__FILE__) . $this->stripAccents(" On va modifier le statut du droit d'option =>  " . $esignatureid));

        if (trim($esignatureid . "")=="")
        {
            $error = "L'identifiant eSignature est vide => Pas de traitement";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $error));
            $result_json = array('status' => 'Ok', 'description' => '');
            error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
            return $result_json;
        }

        $optionCET = new optionCET($this->dbconnect);
        $error = $optionCET->load($esignatureid);
        if ($error != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" $error"));
            $result_json = array('status' => 'Error', 'description' => $error);
            return $result_json;
        }

        $esignature = new esignature($this->dbconnect);
        // Si le statut est passé dans l'appel du WS (=> Appel automatique de eSignature)

        if (isset($_GET['status']))
        {
            $esignature_status = trim($_GET['status']);
            error_log(basename(__FILE__) . $this->stripAccents(" Le statut est dans le GET => $esignature_status"));
        }
        // On récupère le statut à partir de eSignature
        else
        {
            $esignature_status = $esignature->get_signrequest_status($esignatureid);
        }

        // Si la récupération du statut à partir de eSignature a échouée
        if ($esignature_status===false)
        {
            $error = "Erreur dans eSignature : Impossible de récupérer le statut du document";
            error_log(basename(__FILE__) . $this->stripAccents(" $error"));
            // Si j'ai une erreur dans mon appel CURL on ne doit rien faire => Statut = '' et on crée le $result_json
            $result_json = array('status' => 'Error', 'description' => $error);
            return $result_json;
        }

        $esignature_status = str_replace("'", "", $esignature_status);
        error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$esignature_status'"));

        // Si le statut de eSignature n'a pas pu être récupéré on ne fait aucune modification du statut de G2T
        if ($esignature_status == '')
        {
            $error = " Pas de statut récupéré de eSignature => On ne modifie pas le statut dans G2T";
            error_log(basename(__FILE__) . $this->stripAccents($error));
            $result_json = array('status' => 'Ok', 'description' => $error);
            return $result_json;
        }


        switch (strtolower($esignature_status))
        {
            //draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
            case 'draft' :
            case 'pending' :
            case 'signed' :
            case 'checked' :
            case 'cleaned' :
                $status = optionCET::STATUT_EN_COURS;
                break;

            case 'refused':
                $status = optionCET::STATUT_REFUSE;
                error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$esignature_status' => On va chercher le commentaire"));
                // Récupération du commentaire d'esignature
                if (isset($_GET['comment']))
                {
                    $reason = trim($_GET['comment']);
                    error_log(basename(__FILE__) . $this->stripAccents(" Le motif est dans le GET => $reason"));
                }
                else
                {
                    $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                    if (is_array($comments))
                    {
                        $reason = end($comments); //implode(" ",$comments);
                        error_log(basename(__FILE__) . $this->stripAccents(" Le motif est dans un post-it : $reason"));
                    }
                }
                break;

            case 'completed' :
                $status = optionCET::STATUT_VALIDE;
                break;

            case 'deleted' :
            case 'canceled' :
            case '' :
                $status = optionCET::STATUT_ABANDONNE;
                break;

            case 'fully-deleted' :
                if (!in_array($optionCET->statut(),array(optionCET::STATUT_ABANDONNE,optionCET::STATUT_REFUSE, optionCET::STATUT_VALIDE)))
                {
                    $status = optionCET::STATUT_ABANDONNE;
                }
                else
                {
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            case 'exported' :
            case 'archived' :

                // Si la demande est déjà refusé on va voir si le motif du refus a changé
                if ($optionCET->statut() == optionCET::STATUT_REFUSE)
                {
                    // On récupère les commentaires sur le refus
                    $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                    if (is_array($comments))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" On récupère les motifs de refus pour voir s'il a changé"));  
                        $reason = end($comments); //implode(" ",$comments);
                    }
                    if ($optionCET->motif() != $reason)
                    {
                        $optionCET->motif($reason);
                        $erreur = $optionCET->store();
                        if ($erreur != "")
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la modification du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
                            $result_json = array('status' => 'Error', 'description' => $erreur);
                        }
                        else
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" La modification du motif du droit d'option " . $esignatureid . " est Ok => Pas d'erreur"));
                            $result_json = array('status' => 'Ok', 'description' => $erreur);
                        }
                        return $result_json;
                    }
                    else
                    {
                        $erreur = '';
                        error_log(basename(__FILE__) . $this->stripAccents(" La demande est refusée et le motif inchangé. On ne fait rien => Pas d'erreur"));
                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                    }
                    return $result_json;
                }
                elseif (!in_array($optionCET->statut(),array(optionCET::STATUT_ABANDONNE,optionCET::STATUT_REFUSE, optionCET::STATUT_VALIDE)))
                {
                    $statutlist = $esignature->get_signrequest_stepstatus($esignatureid);
                    if (is_string($statutlist))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" $statutlist"));
                        $result_json = array('status' => 'Error', 'description' => $statutlist);
                        return $result_json;
                    }
                    foreach($statutlist as $stepindex => $stepstatut)
                    {
                        if (strcasecmp($stepstatut,'refused')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est refusée => On marque comme refusé"));  
                            $status = optionCET::STATUT_REFUSE;
                            // On récupère les commentaires sur le refus
                            $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                            if (is_array($comments))
                            {
                                error_log(basename(__FILE__) . $this->stripAccents(" On récupère les motifs de refus"));  
                                $reason = end($comments); //implode(" ",$comments);
                            }
                            // On a trouvé un refus de signature => On sort de la boucle
                            break;
                        }
                        elseif (strcasecmp($stepstatut,'signed')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est signée => On marque comme validé")); 
                            // Pour le moment, la demande est signée
                            $status = optionCET::STATUT_VALIDE;
                        }
                    }
                    // Si on n'a pas pu identifié l'ancien statut eSignature de la demande => On dit que tout est ok.
                    if ($status == "")
                    {
                        $erreur = "";
                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                        return $result_json;
                    }
                }
                // Le statut G2T est déjà dans un état final (<=> pas EN_COURS) => On n'a rien fait. Tout ok
                else
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" La convention de télétravail est déjà avec un statut correct => Pas de traitement")); 
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            default :
                $response = json_decode($esignature_status, true);
                if (isset($response['error'])) $erreur = $response['error']; else $erreur = '';
                $erreur = "Erreur dans la réponse de eSignature => eSignatureid = " . $esignatureid . " erreur => $erreur esignature_status => $esignature_status";
                error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
                return $result_json;
                break;
        }
        // En théorie, ici, on a forcément un statut G2T défini
        if ($status == "")
        {
            $erreur = "Impossible de déterminer le statut G2T de la demande => eSignatureid = " . $esignatureid . " esignature_status => $esignature_status";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
            $result_json = array('status' => 'Error', 'description' => $erreur);
            return $result_json;
        }

        // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
        if ($status == $optionCET->statut()) // and $reason==$optionCET->motif())
        {
            $erreur = '';
            error_log(basename(__FILE__) . $this->stripAccents(" La demande a déjà un statut $status. On ne fait rien => Pas d'erreur"));
            $result_json = array('status' => 'Ok', 'description' => $erreur);
            return $result_json;
        }
        // Ajout d'un contrôle qui interdit de modifier le statut de la demande, les informations de solde si la demande est déjà VALIDE, ABANDONNE ou REFUSE
        if (!in_array($optionCET->statut(), array(optionCET::STATUT_VALIDE, optionCET::STATUT_ABANDONNE, optionCET::STATUT_REFUSE )))
        {
            // if (($status == optionCET::STATUT_VALIDE) and ($optionCET->statut() == optionCET::STATUT_EN_COURS or $optionCET->statut() == optionCET::STATUT_PREPARE))
            if (($status == optionCET::STATUT_VALIDE) and (in_array($optionCET->statut(), array(optionCET::STATUT_EN_COURS, optionCET::STATUT_PREPARE))))
            {
                $agent = new agent($this->dbconnect);
                $agentid = $optionCET->agentid();
                error_log(basename(__FILE__) . $this->stripAccents(" L'agent id =  " . $agentid ));
                $agent->load($agentid);
                $cet = new cet($this->dbconnect);
                $erreur = $cet->load($agentid);
                if ($erreur <> '')
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Pas de CET pour cet agent : " . $agent->identitecomplete() ." ! Ce n'est pas possible. "));
                    $result_json = array('status' => 'Error', 'description' => 'Pas de CET pour cet agent :' . $erreur);
                    unset($cet);
                }
                else
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Le solde du CET est avant enregistrement de " . ($cet->cumultotal() - $cet->jrspris())));
                    // On ajuste le solde du CET et on marque dans l'historique 
                    // On retranche le nombre de jours pour la RAFP
                    if ($optionCET->valeur_i() > 0)
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" L'agent : " . $agent->identitecomplete() ." met " . $optionCET->valeur_i() . " jours en RAFP. "));
                        $cet->jrspris( $cet->jrspris() + $optionCET->valeur_i() ) ;
                        // Ajouter dans la table des commentaires la trace de l'opération
                        $agent->ajoutecommentaireconge('cet',($optionCET->valeur_i()*-1),"Prise en compte au titre de la RAFP");
                    }
                    
                    // On retranche le nombre de jours pour l'indemnisation
                    if ($optionCET->valeur_j() > 0)
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" L'agent : " . $agent->identitecomplete() ." met " . $optionCET->valeur_j() . " jours en indemnisation. "));
                        $cet->jrspris( $cet->jrspris() + $optionCET->valeur_j() ) ;
                        // Ajouter dans la table des commentaires la trace de l'opération
                        $agent->ajoutecommentaireconge('cet',($optionCET->valeur_j()*-1),"Prise en compte au titre de l'indemnistation");
                    }
                    
                    // Nombre de jours à conserver dans le CET -- Juste pour info car cela ne modifie pas le solde du CET
                    if ($optionCET->valeur_k() > 0)
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" L'agent : " . $agent->identitecomplete() ." conserve " . $optionCET->valeur_k() . " jours dans son CET. "));
                    }
                    
                    error_log(basename(__FILE__) . $this->stripAccents(" Le solde du CET sera après enregistrement de " . ($cet->cumultotal() - $cet->jrspris())));
                    $cet->store();
                    
                    $erreur = $optionCET->storepdf();
                    if ($erreur != '')
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la récupération du PDF de la demande " . $esignatureid . " => Erreur = " . $erreur));
                        $result_json = array('status' => 'Error', 'description' => $erreur);
                        return $result_json;
                    }
                }
            }
            else  // Le statut du droit d'option n'est pas validée
            {
                error_log(basename(__FILE__) . $this->stripAccents(" On ne met pas à jour les soldes de CET de l'agent " . $optionCET->agentid()));
            }

            error_log(basename(__FILE__) . $this->stripAccents(" Mise à jour du droit d'option $esignatureid de l'agent " . $optionCET->agentid()));
            $optionCET->statut($status);
            if ($status <> optionCET::STATUT_ABANDONNE)
            {
                $optionCET->motif($reason);
            }
            $erreur = $optionCET->store();
            if ($erreur != "")
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de l'enregistrement du droit d'option " . $esignatureid . " => Erreur = " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Traitement OK du droit d'option " . $esignatureid . " => Pas d'erreur"));
                $result_json = array('status' => 'Ok', 'description' => $erreur);
            }
        }
        else
        {
            $erreur = "Incohérence lors de la modification du statut de la demande : La demande est " . $optionCET->statut() . " et on veut la passer $status";
            error_log(basename(__FILE__) . $this->stripAccents(" $erreur"));
            $result_json = array('status' => 'Error', 'description' => $erreur);
        }
        error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
        return $result_json;
    }

    public function synchroniseconventionteletravail(string $esignatureid)
    {
        $status = "";
        $reason = "";
        $esignature_status = "";
        $datesignatureresponsable = '19000101';
        $sendmailtoresp = false;

        error_log(basename(__FILE__) . $this->stripAccents(" On va modifier le statut de la convention télétravail =>  " . $esignatureid));

        if (trim($esignatureid . "")=="")
        {
            $error = "Pas de synchronisation sur la convention " . $esignatureid . " => Pas dans eSignature";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $error));
            $result_json = array('status' => 'Ok', 'description' => '');
            error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
            return $result_json;
        }

        $teletravail = new teletravail($this->dbconnect);
        $erreur = $teletravail->loadbyesignatureid($esignatureid);
        // Si on a rencontré un problème lors du chargement de la convention télétravail dans G2T
        if ($erreur===false)
        {
            $error = "Erreur dans le chargement de la convention télétravail (identifiant eSignature = $esignatureid)";
            error_log(basename(__FILE__) . $this->stripAccents(" $error"));
            // Si j'ai une erreur dans mon appel CURL on ne doit rien faire => Statut = '' et on crée le $result_json
            $result_json = array('status' => 'Error', 'description' => $error);
            return $result_json;
        }

        $esignature = new esignature($this->dbconnect);
        // Si le statut est passé dans l'appel du WS (=> Appel automatique de eSignature)
        if (isset($_GET['status']))
        {
            $esignature_status = trim($_GET['status']);
            error_log(basename(__FILE__) . $this->stripAccents(" Le statut est dans le GET => $esignature_status"));
        }
        // On récupère le statut à partir de eSignature
        else
        {
            $esignature_status = $esignature->get_signrequest_status($esignatureid);
        }

        // Si la récupération du statut à partir de eSignature a échouée
        if ($esignature_status===false)
        {
            $error = "Erreur dans eSignature : Impossible de récupérer le statut du document";
            error_log(basename(__FILE__) . $this->stripAccents(" $error"));
            // Si j'ai une erreur dans mon appel CURL on ne doit rien faire => Statut = '' et on crée le $result_json
            $result_json = array('status' => 'Error', 'description' => $error);
            return $result_json;
        }

        $esignature_status = str_replace("'", "", $esignature_status);
        error_log(basename(__FILE__) . $this->stripAccents(" Le current status (eSignature) = $esignature_status  Le statut dans G2T = " . $teletravail->statut()));

        // Si le statut de eSignature n'a pas pu être récupéré on ne fait aucune modification du statut de G2T
        if ($esignature_status == '')
        {
            $error = " Pas de statut récupéré de eSignature => On ne modifie pas le statut dans G2T";
            error_log(basename(__FILE__) . $this->stripAccents($error));
            $result_json = array('status' => 'Ok', 'description' => $error);
            return $result_json;
        }

        switch (strtolower($esignature_status))
        {
            // draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned, fully-deleted

            case 'draft' :
            case 'pending' :
            case 'signed' :
            case 'checked' :
            case 'cleaned' :
                $status = teletravail::TELETRAVAIL_ATTENTE;
                break;

            case 'refused':
                $status = teletravail::TELETRAVAIL_REFUSE;
                error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$esignature_status' => On va chercher le commentaire"));

                $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                if (is_array($comments))
                {
                    $reason = end($comments); //implode(" ",$comments);
                }
                break;

            case 'completed' :
                $status = teletravail::TELETRAVAIL_VALIDE;
                break;

            case 'deleted' :
            case 'canceled' :
            case '' :
                $status = teletravail::TELETRAVAIL_ANNULE;
                break;

            case 'fully-deleted' :
                // Si le statut de la convention télétravail n'est pas TELETRAVAIL_ANNULE, TELETRAVAIL_VALIDE, TELETRAVAIL_REFUSE => On doit mettre à jour le statut
                // En théorie on ne peut pas supprimer une convention de télétravail si le circuit de validation est terminé
                if (!in_array($teletravail->statut(),array(teletravail::TELETRAVAIL_ANNULE,teletravail::TELETRAVAIL_VALIDE, teletravail::TELETRAVAIL_REFUSE)))
                {
                    $status = teletravail::TELETRAVAIL_ANNULE;
                }
                else
                {
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            case 'exported' :
            case 'archived' :
                // Si le statut de la convention télétravail n'est pas TELETRAVAIL_ANNULE, TELETRAVAIL_VALIDE, TELETRAVAIL_REFUSE => On doit mettre à jour le statut
                if (!in_array($teletravail->statut(),array(teletravail::TELETRAVAIL_ANNULE,teletravail::TELETRAVAIL_VALIDE, teletravail::TELETRAVAIL_REFUSE)))
                {
                    $statutlist = $esignature->get_signrequest_stepstatus($esignatureid);
                    if (is_string($statutlist))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" $statutlist"));
                        $result_json = array('status' => 'Error', 'description' => $statutlist);
                        return $result_json;
                    }
                    foreach($statutlist as $stepindex => $stepstatut)
                    {
                        if (strcasecmp($stepstatut,'refused')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est refusée => On marque comme refusé"));  
                            $status = teletravail::TELETRAVAIL_REFUSE;
                            // On récupère les commentaires sur le refus
                            $comments = $esignature->get_signrequest_stepcomment($esignatureid);
                            if (is_array($comments))
                            {
                                error_log(basename(__FILE__) . $this->stripAccents(" On récupère les motifs de refus"));  
                                $reason = end($comments); //implode(" ",$comments);
                            }
                            // On a trouvé un refus de signature => On sort de la boucle
                            break;
                        }
                        elseif (strcasecmp($stepstatut,'signed')==0)
                        {
                            error_log(basename(__FILE__) . $this->stripAccents(" L'action $stepindex est signée => On marque comme validé")); 
                            // Pour le moment, la demande est signée
                            $status = teletravail::TELETRAVAIL_VALIDE;
                        }
                    }
                    // Si on n'a pas pu identifié l'ancien statut eSignature de la demande => On dit que tout est ok.
                    if ($status == "")
                    {
                        $erreur = "";
                        $result_json = array('status' => 'Ok', 'description' => $erreur);
                        return $result_json;
                    }
                }
                // Le statut G2T est déjà dans un état final (<=> pas EN_COURS) => On n'a rien fait. Tout ok
                else
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" La convention de télétravail est déjà avec un statut correct => Pas de traitement")); 
                    $erreur = "";
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                    return $result_json;
                }
                break;

            default :
                $response = json_decode($esignature_status, true);
                if (isset($response['error'])) $erreur = $response['error']; else $erreur = '';
                $erreur = "Erreur dans la réponse de eSignature => eSignatureid = " . $esignatureid . " erreur => $erreur esignature_status => $esignature_status";
                error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
                return $result_json;
                break;

        }
        // En théorie, ici, on a forcément un statut G2T défini
        if ($status == "")
        {
            $erreur = "Impossible de déterminer le statut G2T de la demande => eSignatureid = " . $esignatureid . " esignature_status => $esignature_status";
            error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
            $result_json = array('status' => 'Error', 'description' => $erreur);
            return $result_json;
        }

        // error_log(basename(__FILE__) . $this->stripAccents(" statut de la convention dans eSignature = $status -> " . $this->teletravailstatutlibelle($status)));
        // error_log(basename(__FILE__) . $this->stripAccents(" teletravail->statut() = " . $teletravail->statut() . " -> " . $this->teletravailstatutlibelle($teletravail->statut())));

        // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
        if ($status == $teletravail->statut() and $reason==$teletravail->commentaire())
        {
            error_log(basename(__FILE__) . $this->stripAccents(" La convention a déjà un statut $status (" . $this->teletravailstatutlibelle($status) . "). On ne fait rien => Pas d'erreur"));
            $erreur = '';
            $result_json = array('status' => 'Ok', 'description' => $erreur);
            return $result_json;
        }
                    
        // Si le status est VALIDE alors on va mettre la date du dernier signataire comme date de début de la convention
        if ($status==teletravail::TELETRAVAIL_VALIDE)
        {
            $datesignatureresponsable = $esignature->get_signrequest_enddate($esignatureid);
            // Si ce n'est pas une date correcte 
            if (strtotime($datesignatureresponsable)===false)
            {
                $erreur = "Erreur dans la récupération de la date de fin => eSignatureid = " . $esignatureid . " => $datesignatureresponsable";
                error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
                return $result_json;
            }
            error_log(basename(__FILE__) . $this->stripAccents(" La date de signature du dernier niveau est : $datesignatureresponsable"));
        }
        
        if (($status == teletravail::TELETRAVAIL_ANNULE or $status == teletravail::TELETRAVAIL_REFUSE) and $teletravail->statut() == teletravail::TELETRAVAIL_ATTENTE)
        {
            $sendmailtoresp = true;
        }

        if ($this->formatdatedb($datesignatureresponsable)>$this->formatdatedb($teletravail->datedebut()))
        {
            error_log(basename(__FILE__) . $this->stripAccents(" On passe la date de début de la convention à $datesignatureresponsable - valeur actuelle : " . $teletravail->datedebut()));
            $teletravail->datedebut($datesignatureresponsable);
        }
        if ($this->formatdatedb($teletravail->datedebut())>$this->formatdatedb($teletravail->datefin()) and $status <> teletravail::TELETRAVAIL_ANNULE )
        {
            $status = teletravail::TELETRAVAIL_ANNULE;
            $reason = "Il y a une incohérence dans les dates de début et de fin => On force l'annulation de la convention.";
            error_log(basename(__FILE__) . $this->stripAccents(" $reason"));
        }
        error_log(basename(__FILE__) . $this->stripAccents(" On passe le statut de la convention " . $esignatureid . " à $status (" . $this->teletravailstatutlibelle($status) . ")"));
        $ancienstatut = $teletravail->statut();
        $teletravail->statut($status);
        $teletravail->commentaire($reason);
        $erreur = $teletravail->store();
        if ($erreur != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de l'enregistrement de la convention " . $esignatureid . " => Erreur = " . $erreur));
            $result_json = array('status' => 'Error', 'description' => $erreur);
        }
        else
        {
            // On va générer le PDF dans le cas ou le statut de la convention est VALIDEE ou REFUSEE
            if ($teletravail->statut()==teletravail::TELETRAVAIL_VALIDE or $teletravail->statut()==teletravail::TELETRAVAIL_REFUSE)
            {
                $teletravail->storepdf();
            }

            // On va récupérer les informations sur les demandes de matériel dans la convention
            if ($teletravail->statut()==teletravail::TELETRAVAIL_VALIDE)
            {
                $this->creation_ticketGLPI_materiel($esignatureid);
            }

            // On va regarder si d'autres conventions se chevauchent
            $agentid = $teletravail->agentid();
            $agent = new agent($this->dbconnect);
            $agent->load($agentid);

            if ($sendmailtoresp)
            {
                error_log(basename(__FILE__) . $this->stripAccents(" On va envoyer un mail au responsable car on a annulé/refusé une convention télétravail (id G2T = " . $teletravail->teletravailid() . ")"));
                $resp = $agent->getsignataire();
                if (is_null($resp) or $resp===false)
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Aucun mail au responsable car il n'est pas défini (id G2T = " . $teletravail->teletravailid() . ")"));
                }
                else
                {
                    $cronuser = new agent($this->dbconnect);
                    $cronuser->load(SPECIAL_USER_IDCRONUSER);
                    $cronuser->sendmail($resp,"Annulation/Refus d'une demande de télétravail - " . $agent->identitecomplete(), "Une demande de convention de télétravail pour " . $agent->identitecomplete() . " a été annulée/refusée.<br>"
                        . "Ceci est un message informatif. Vous n'avez aucune action à réaliser. <br>");
                    error_log(basename(__FILE__) . $this->stripAccents(" Le mail au responsable (" . $resp->identitecomplete() . " " . $resp->mail() . ") a été envoyé (id G2T = " . $teletravail->teletravailid() . ")"));
                }
            }

            $currentconventionid=$teletravail->teletravailid();
            $datedebutteletravail = $teletravail->datedebut();
            $datefinteletravail = $teletravail->datefin();
            $liste = array();
            // Si la demande de convention était déjà annulée ou refusée, cela n'a aucun impact sur les conventions actuelles
            if ($ancienstatut != teletravail::TELETRAVAIL_ANNULE and $ancienstatut != teletravail::TELETRAVAIL_REFUSE)
            {
                $liste = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
            }
            foreach ($liste as $conventionid)
            {
                if ($currentconventionid <> $conventionid) // On ignore la convention qu'on vient de traiter
                {
                    $teletravailmodif = new teletravail($this->dbconnect);
                    $teletravailmodif->load($conventionid);
                    if (in_array($teletravailmodif->statut(),array(teletravail::TELETRAVAIL_VALIDE,teletravail::TELETRAVAIL_ATTENTE)))
                    {
                        error_log(basename(__FILE__) . $this->stripAccents(" On va changer le statut de la convention G2T $conventionid qui a actuellement le statut => " . $teletravailmodif->statut()));
                        if ($teletravailmodif->datefin()>=$datedebutteletravail)
                        {
                            $veilledebut = date("d/m/Y", strtotime("-1 day", strtotime($this->formatdatedb($datedebutteletravail))));
                            //echo "datedebutteletravail = $datedebutteletravail <br>";
                            //echo "veilledebut = $veilledebut <br>";
                            $teletravailmodif->datefin($veilledebut);
                            $teletravailmodif->commentaire("Modification de la date de fin de la convention suite à création d'une nouvelle convention.");
                            //echo "date debut  = " . $this->formatdatedb($teletravail->datedebut()) . "<br>";
                            //echo "date fin  = " . $this->formatdatedb($teletravail->datefin()) . "<br>";
                            if ($this->formatdatedb($teletravailmodif->datefin()) < $this->formatdatedb($teletravailmodif->datedebut()))
                            {
                                $return = '';
                                if (trim($teletravailmodif->esignatureid().'')<>'')
                                {
                                    $esignature = new esignature($this->dbconnect);
                                    $return = $esignature->delete_signrequest($teletravailmodif->esignatureid());
                                    // $return = "" . $this->deleteesignaturedocument($teletravailmodif->esignatureid());
                                }
                                if (strlen($return)>0) // On a rencontré une erreur dans la suppression eSignature
                                {
                                    if (strlen($erreur)>0) $erreur = $erreur . '<br>';
                                    $erreur = $erreur . $return . "";
                                    error_log(basename(__FILE__) . " " . $this->stripAccents($return));
                                }
                                //echo "On passe la convetion à ANNULE<br>";
                                $teletravailmodif->statut(teletravail::TELETRAVAIL_ANNULE);
                                //deleteesignaturedocument($teletravail);
                            }
                            //echo "La convention télétravail " . $teletravail->teletravailid() . " a un statut " . $teletravail->statut() . " ( " . $this->teletravailstatutlibelle($teletravail->statut()) . " ) et une date de fin " . $teletravail->datefin() . "<br>";
                            $teletravailmodif->store();
                        }
                        /*
                            if (strlen($alerte)>0) $alerte = $alerte . '<br>';
                            $alerte = $alerte . "La nouvelle convention de télétravail a modifié une convention existante (id = $conventionid).";
                            */
                    }
                }
            }
            $erreur = $erreur . '';
            if ($erreur <> '')
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de l'adaptation des conventions => Erreur = " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Traitement ok de la modification du statut de la convention " . $currentconventionid . " => Pas d'erreur"));
                $result_json = array('status' => 'Ok', 'description' => $erreur . '');
            }
        }
        error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
        return $result_json;
    }

    function creation_ticketGLPI_materiel($esignatureid)
    {
        $dbconstante = 'GLPI_COLLECTEUR';
        $mail_glpi = "";
        if ($this->testexistdbconstante($dbconstante))
        {
            $mail_glpi = $this->liredbconstante($dbconstante);
        }
        if ($mail_glpi <> '')
        {
            $demandeur = new agent($this->dbconnect);
            $teletravail = new teletravail($this->dbconnect);
            $teletravail->loadbyesignatureid($esignatureid);
            $demandeur->load($teletravail->agentid());
            //var_dump($tab_materiel);
            $materieldemande = false;
            $besoin = "";

            $besoin = $besoin . "&nbsp;&nbsp;&bull; ";
            foreach (teletravail::MATERIEL_LIBELLE as $key => $libelle)
            {
                if ($teletravail->demande_materiel($key))
                {
                    $besoin = $besoin . "J'ai demandé ";
                    $materieldemande = true;
                }
                else
                {
                    $besoin = $besoin . "Je n'ai pas demandé ";
                }
                $besoin = $besoin . " : " . $libelle . "\n";
            }

            $destinataire = $mail_glpi;
            if ($materieldemande==true)
            {
                $objet = "Demande de matériel suite à validation de convention télétravail";
                $corps = "Suite à la validation de ma demande de convention de télétravail numéro " . $teletravail->teletravailid() . ", je vous remercie de bien vouloir prendre note que : <br>";
                $corps = $corps . "<br>" . $besoin . "<br> Cordialement, <br>" . $demandeur->identitecomplete() . " <br>";
                error_log(basename(__FILE__) . $this->stripAccents(" Les besoins en matériel sont : " . str_replace(array("\n","&nbsp;","&bull;"), '', $besoin) . " => $destinataire"));
                
                $constante = 'MAINTENANCE';
                $maintenance = $this->liredbconstante($constante);
                if (strcasecmp((string)$maintenance, 'n') != 0)
                {
                    // Si on est en mode maintenance => On ne fait rien
                    error_log(basename(__FILE__) . $this->stripAccents(" Création du ticket GLPI => Mode maintenance activé. On ne fait rien."));
                }
                else
                {
                    $demandeur->sendmail($destinataire, $objet, $corps);
                }
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Pas de materiel demande pour la convention " . $teletravail->teletravailid() . " => Pas d'envoi de mail à $destinataire"));
            }
        }
    }

    function logueurmaxcolonne($table, $colonne)
    {
        $longueurmax = 0;

        $sql = "SELECT character_maximum_length
FROM   information_schema.columns
WHERE  table_schema = Database()
       AND table_name = ?
       AND column_name = ?";

        $params = array($table,$colonne);
        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "logueurmaxcolonne => Problème SQL dans la récupération de la taille maximale : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            echo $errlog;
        }
        elseif (mysqli_num_rows($query) > 0)
        {
            $result = mysqli_fetch_row($query);
            $longueurmax = $result[0];
        }
        return $longueurmax;
    }

    /**
     * Affiche ou enregistre dans le fichier de trace, le texte en fonction des paramètres loginfo et displayinfo
     * 
     * @param boolean $loginfo
     * @param boolean $displayinfo
     * @param string  $texttolog
     * @return null
     */
    function log_traces($loginfo,$displayinfo,$texttolog)
    {
        if ($loginfo == true)
        {
            error_log(basename(__FILE__) . $this->stripAccents(" $texttolog"));
        }
        if ($displayinfo == true)
        {
            echo " $texttolog \n";
        }
    }

    function convert_value_to_on_off($valeur)
    {
        // if (trim($valeur)=='1')
        if ($this->convertvaluetobool($valeur))
        {
            return 'on';
        }
        else //if (trim($valeur)=='0')
        {
            return 'off';
        }
        // else
        // {
        //     return "Valeur inconnue - $valeur";
        // }
    }

    /**
     *
     * @deprecated
     * 
     * @param string $datefinprecedente
     * @param string $datedebutsuivante
     * @param integer $nbre_jour_periode
     * @return boolean 
     */
    function affectation_continue($datefinprecedente,$datedebutsuivante,$nbre_jour_periode)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        
        $NBREMOIS = 4;

        $this->log_traces(true, false, "datefinprecedente => $datefinprecedente");
        $datefincalculee = date("Ymd", strtotime($datefinprecedente . "+" . $NBREMOIS . " month"));
        $this->log_traces(true, false, "datefincalculee => $datefincalculee");
        $moisdepart = date("m", strtotime($datefinprecedente));
        $moisarrive = date("m", strtotime($datefincalculee));
        $anneearrive = date("Y", strtotime($datefincalculee));
        $this->log_traces(true, false, "moisdepart => $moisdepart moisarrive => $moisarrive  anneearrive => $anneearrive");
        if ($moisarrive > (($moisdepart+$NBREMOIS)%12))
        {
            $this->log_traces(true, false, "Le moisarrive est sur le mois suivant");
            $moisarrive = $moisdepart+$NBREMOIS;
            if ($moisarrive>12)
            {
                $moisarrive = $moisarrive - 12;
            }
            $nbrejoursmois = $this->nbr_jours_dans_mois($moisarrive, $anneearrive);
            $datefincalculee = $anneearrive . $moisarrive . $nbrejoursmois;
            $this->log_traces(true, false, "La nouvelle date de fin calculee est : $datefincalculee");

        }
        // S'il faut + de $NBREMOIS mois d'interruption, il faut donc ajouter 1 jour à la date de fin calculee
        // => Si la date de reprise est égale à 4 mois + 1 => L'interruption est juste de 4 mois => Il y a continuité
        // $datefincalculee = date("Ymd", strtotime($datefincalculee . "+1 day"));

        $this->log_traces(true, false, "datefincalculee : $datefincalculee   datedebutsuivante=$datedebutsuivante");
        // Si la date de fin de l'interruption est avant la date de début de la date suivante => Il y a rupture
        if ($datefincalculee < $datedebutsuivante)
        {
            $this->log_traces(true, false, "On retourne : FALSE => Il y a interruption");
            return false;
        }
        else
        {
            $this->log_traces(true, false, "On retourne : TRUE => C'est continu");
            return true;
        }


/*
        $this->log_traces(true, false, "nbre_jour_periode => $nbre_jour_periode");
        $nbrejrsmoyenparmois = ( $nbre_jour_periode / 12 );
        // Sur 4 mois, on a donc
        $nbrejrsinterval = intval($nbrejrsmoyenparmois * 4);
        $this->log_traces(true, false, "Nombre de jours dans 4 mois => $nbrejrsinterval jours");
        $this->log_traces(true, false, "datefinprecedente = $datefinprecedente   datedebutaff = $datedebutsuivante");
        //$datefinprecedente = date("Ymd", strtotime($datefinprecedente . "+1 day"));
        //$this->log_traces(true, false, "Le jour suivant la date de fin précédente = $datefinprecedente");
        $nbrejrscalcule = $this->nbjours_deux_dates($datefinprecedente, $datedebutsuivante)-2; // -2 => On doit exclure les deux dates extrèmes
        $this->log_traces(true, false, "Il y a $nbrejrscalcule jours d'interruption entre les deux dates");
        if ($nbrejrscalcule > $nbrejrsinterval)
        {
            return false;
        }
        else
        {
            return true;
        }
 */
    }

    function calcul_date_anniversaire($dateref,$nbrejrstravailtotal,$nbre_jour_periode)
    {
        $NBREMOIS = 10;

        // On enlève le nombre de jours que l'agent à déjà effectué à la date de début de l'affectation
        $datedebuttheorique = date('Ymd',strtotime($dateref . " - $nbrejrstravailtotal days"));
        $moisdebut = date("m", strtotime($datedebuttheorique));
        $this->log_traces(true, false, "datedebuttheorique => $datedebuttheorique  moisdebut => $moisdebut");

        // On ajoute $NBREMOIS mois à cette date de début théorique
        $dateanniv = date("Ymd", strtotime($datedebuttheorique . "+" . $NBREMOIS . " month"));
        $moisdateanniv = date("m", strtotime($dateanniv));
        $anneedateanniv = date("Y", strtotime($dateanniv));
        $this->log_traces(true, false, "dateanniv => $dateanniv  moisdateanniv => $moisdateanniv  anneedateanniv => $anneedateanniv");

        if (($moisdateanniv > (($moisdebut+$NBREMOIS)%12)) and ($moisdebut+$NBREMOIS)!=12)
        {
            $this->log_traces(true, false, "Le moisdateanniv est sur le mois suivant");
            $dateanniv = $anneedateanniv . $moisdateanniv . '01';
            $this->log_traces(true, false, "La nouvelle date anniversaire dateanniv => $dateanniv");
        }
        return $dateanniv;

/*
        // On enlève le nombre de jours que l'agent à déjà effectué à la date de début de l'affectation
        $datedebuttheorique = date('Ymd',strtotime($dateref . " - $nbrejrstravailtotal days"));
        // Ensuite on ajoute la durée minimum que l'agent doit avoir travaillé
        // Si l'agent doit avoir travaillé 10 mois on divise le nombre de jours de la période par 12 et on multiplie par 10
        $nbrejrsmoyenparmois = ( $nbre_jour_periode / 12 );
        // Sur 10 mois, on a donc
        $nbrejrsinterval = (floor($nbrejrsmoyenparmois * 10)-1); // On fait -1 car il faut exclure le jour extrème

        $dateanniv = date('Ymd',strtotime($datedebuttheorique . " + $nbrejrsinterval days"));
        return $dateanniv;
 */
    }

//    function tronque_chaine ($chaine, $lg_max, $strict = false)
//    {
//        if (strlen($chaine) > $lg_max)
//        {
//            if ($strict)
//            {
//                $chaine = substr($chaine, 0, $lg_max) . "...";
//            }
//            else
//            {
//                $chaine = substr($chaine, 0, $lg_max);
//                $last_space = strrpos($chaine, " ");
//                if ($last_space===false)
//                {
//                    $last_space=strlen($chaine);
//                }
//                $chaine = substr($chaine, 0, $last_space)."...";
//            }
//        }
//        return $chaine;
//    }
    
    
    function tronque_chaine ($chaine, $lg_max, $strict = false)
    {
        if (mb_strlen($chaine) > $lg_max)
        {
            if ($strict)
            {
                $chaine = mb_substr($chaine, 0, $lg_max) . "...";
            }
            else
            {
                $chaine = mb_substr($chaine, 0, $lg_max);
                $last_space = mb_strrpos($chaine, " ");
                if ($last_space===false)
                {
                    $last_space=mb_strlen($chaine);
                }
                $chaine = mb_substr($chaine, 0, $last_space)."...";
            }
        }
        return $chaine;
    }

    function ajoute_crlf ($chaine, $lg_max)
    {
        if (mb_strlen($chaine) > $lg_max)
        {
            $chaineresultat = '';
            while (mb_strlen($chaine) > $lg_max)
            {
                $subchaine = mb_substr($chaine, 0, $lg_max);
                // On cherche le dernier CR (<=>chr(13)) et le dernier espace.
                $last_space = mb_strrpos($subchaine, " ");
                $last_retrun = mb_strrpos($subchaine, chr(13));
                if ($last_space===false and $last_retrun===false)
                {
                    // S'il n'y a plus d'espace ou de CR, on ne tronque plus rien
                    //break;
                    $last_space = mb_strlen($chaine);  // $lg_max;
                }
                elseif ($last_space===false and $last_retrun!==false)
                {
                    // S'il y a un CR et pas d'espace, on coupe sur le CR
                    $last_space = $last_retrun;
                }
                elseif ($last_space!==false and $last_retrun!==false)
                {
                    // Si on a à la fois un CR et un espace, on prend le plus petit
                    $last_space = min($last_space,$last_retrun);
                }
                $chaineresultat = $chaineresultat . trim(mb_substr($chaine, 0, $last_space));
                // ATTENTION : Bien faire $last_space+1 afin de "sauter" le caractère de découpe (espace ou CR)
                $chaine = mb_substr($chaine, $last_space+1);
                if (mb_strlen($chaineresultat)>0)
                {
                    $chaineresultat = $chaineresultat . chr(13);  // chr(13) <=> Carriage return
                }
            }
            $chaine = $chaineresultat . trim($chaine);
        }
        return $chaine;
    }
    
    function utf8_decode($texte)
    {
        return mb_convert_encoding($texte, 'ISO-8859-1','UTF-8' );
    }

    
    function utf8_encode($texte)
    {
        if (mb_detect_encoding($texte, 'UTF-8', true)===false) // Ce n'est pas de l'UTF-8
        {
            return mb_convert_encoding($texte, 'UTF-8', 'ISO-8859-1');
            //return iconv('ISO-8859-1', 'UTF-8', $texte);
        }
        else
        {
            return $texte;
        }
    }
    
    function afficheperiodesobligatoires()
    {
        $periode = new periodeobligatoire($this->dbconnect);
        $liste = $periode->load($this->anneeref());
        if (count($liste) > 0)
        {
            echo "<div class='periodeobligatoirebloc centeraligntext'><b>RAPPEL : </b>Les périodes de fermeture obligatoire de l'établissement sont les suivantes : <ul>";
            foreach ($liste as $element)
            {
                echo "<li class='periodeobligatoireliste leftaligntext' >Du " . $this->formatdate($element["datedebut"]) . " (inclus) au " . $this->formatdate($element["datefin"]) . " (inclus)</li>";
            }
            echo "</ul>";
            echo "Veuillez penser à poser vos congés en conséquence.";
            echo "</div>";
            echo "<br><br>";
        }
    }
    
    /**
     * Recherche dans la liste des structures passées en paramètre si elles sont inclues les unes dans les autres
     * et si pour la structure englobante, le responsable a accès aux soldes de tous les agents des sous-structures
     * 
     * @param array $structarray
     *            Liste des structures à simplifier
     * @return array liste des structures simplifiée
     */
    function enleverstructuresinclues_soldes($structarray)
    {
        foreach ($structarray as $structkey => $struct)
        {
            $racinestruct = $struct->structureenglobante();
            //var_dump("La structure englobante de " . $struct->nomcourt() . " est " . $racinestruct->nomcourt());
            // Si la structure racine n'est pas la structure qu'on est en train d'analyser
            if ($racinestruct->id() != $struct->id())
            {
                //var_dump("La structure englobante n'est pas la strucuture courante");
                // Si la structure racine est définie dans liste des structures
                if (isset($structarray[$racinestruct->id()]))
                {
                    //var_dump("La structure englobante est définie dans la liste des structures en parametre");
                    // Le responsable peut afficher tous les soldes des sous-structures
                    if (strcasecmp((string)$racinestruct->respaffsoldesousstruct(), "o") == 0)
                    {
                        // On peut enlever la structure inclue en cours
                        //var_dump("On enleve la structure " . $struct->nomcourt() . " de la liste.");
                        unset($structarray[$structkey]);
                    }
                }
            }
        }
        //var_dump($structarray);
        return $structarray;
    }

    /**
     * Recherche dans la liste des structures passées en paramètre si elles sont inclues les unes dans les autres
     * et si pour la structure englobante, le responsable a accès aux demandes de tous les agents des sous-structures
     * 
     * @param array $structarray
     *            Liste des structures à simplifier
     * @return array liste des structures simplifiée
     */
    function enleverstructuresinclues_demandes($structarray)
    {
        foreach ($structarray as $structkey => $struct)
        {
            $racinestruct = $struct->structureenglobante();
            //var_dump("La structure englobante de " . $struct->nomcourt() . " est " . $racinestruct->nomcourt());
            // Si la structure racine n'est pas la structure qu'on est en train d'analyser
            if ($racinestruct->id() != $struct->id())
            {
                //var_dump("La structure englobante n'est pas la strucuture courante");
                // Si la structure racine est définie dans liste des structures
                if (isset($structarray[$racinestruct->id()]))
                {
                    //var_dump("La structure englobante est définie dans la liste des structures en parametre");
                    // Le responsable peut afficher toutes les demandes des sous-structures
                    if (strcasecmp((string)$racinestruct->respaffdemandesousstruct(), "o") == 0)
                    {
                        // On peut enlever la structure inclue en cours
                        //var_dump("On enleve la structure " . $struct->nomcourt() . " de la liste.");
                        unset($structarray[$structkey]);
                    }
                }
            }
        }
        //var_dump($structarray);
        return $structarray;
    }

    /**
     * Recherche dans la liste des structures passées en paramètre si elles sont inclues les unes dans les autres
     * et si pour la structure englobante, le responsable a accès aux demandes de tous les agents des sous-structures
     * 
     * @param array $structarray
     *            Liste des structures à simplifier
     * @return array liste des structures simplifiée
     */
    function enleverstructuresinclues_planning($structarray)
    {
        foreach ($structarray as $struct)
        {
            // Si on autorise l'affichage du planning des sous-structures
            if (strcasecmp((string)$struct->sousstructure(), "o") == 0)
            {
                // On récupère les structures inclues
                $structincluesliste = $struct->structureinclue(true);
                // On parcourt toutes les structures inclues et on l'enlève de la liste (même si elle n'existe pas)
                foreach ($structincluesliste as $structkey => $structinclue)
                {
                    // On peut enlever la structure inclue car on affiche déjà les agents dans une structure parente
                    //var_dump("On enleve la structure " . $struct->nomcourt() . " de la liste.");
                    unset($structarray[$structkey]);
                }
            }
        }
        //var_dump($structarray);
        return $structarray;
    }
    
    function afficherlistestructureindentee($structarray, $showclosedstruct = false, $selectedstructid = null)
    {
        foreach($structarray as $structure)
        {
            if ($showclosedstruct or ($this->formatdatedb($structure->datecloture()) >= $this->formatdatedb(date("Ymd")))) 
            {
                echo "<option value='" . $structure->id() . "' ";
                if ($structure->id() == $selectedstructid) {
                    echo " selected ";
                }
                if ($this->formatdatedb($structure->datecloture()) < $this->formatdatedb(date("Ymd"))) {
                    echo " class='redtext' ";
                }
                echo ">";
                echo str_pad('', strlen('&nbsp;')*4*$structure->profondeurrelative(), '&nbsp;', STR_PAD_LEFT);
                if ($structure->profondeurrelative()>0)
                {
                    echo " &#x21AA; "; // &#x21B3; ";
                }
                echo $structure->nomlong() . " (" . $structure->nomcourt() . ")";
                echo "</option>";
            }
        }
        
    }
    
    function createldapagentfromuid($uid)
    {
        $agentid = $this->getagentidfromldapuid($uid);
        if ($agentid===false)
        {
            $errlog = "createldapagentfromuid : L'agent $uid n'a pas pu être identifié dans LDAP. \n";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            if ($this->executionbatch())
            {
                echo "$errlog";
            }
            return false;            
        }
        $agent=$this->createldapagentfromagentid($agentid);
        if ($agent===false)
        {
            $errlog = "createldapagentfromuid : L'agent $uid n'a pas pu être créé dans la base de données. \n";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            if ($this->executionbatch())
            {
                echo "$errlog";
            }
            return false;            
        }
        else
        {
            return $agent;
        }
    }
    
    function createldapagentfromagentid($agentid, $store = true )
    {
        $typepopulation = "Import automatique LDAP";
        $newagent = new agent($this->dbconnect);
        if ($newagent->existe($agentid))
        {
            if ($newagent->load($agentid))
            {
                $errlog = "createldapagentfromagentid : L'agent $agentid existe et a été chargé depuis la base de données. \n";
                error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
                if ($this->executionbatch())
                {
                    echo "$errlog";
                }
                return $newagent;
            }
            else
            {
                $errlog = "createldapagentfromagentid : L'agent $agentid existe mais n'a pas pu être chargé depuis la base de données. \n";
                error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
                if ($this->executionbatch())
                {
                    echo "$errlog";
                }
                return false;
            }
        }
        // L'agent n'existe pas => On interroge LDAP et on crée l'agent avec un minimum d'informations
        // On interroge LDAP pour récupérer le nom, le prénom, l'adrese mail
        $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->liredbconstante("LDAPSEARCHBASE");
        $LDAP_AGENT_NOM = $this->liredbconstante("LDAP_AGENT_NOM_ATTR");
        $LDAP_AGENT_PRENOM = $this->liredbconstante("LDAP_AGENT_PRENOM_ATTR");
        $LDAP_AGENT_MAIL = $this->liredbconstante("LDAP_AGENT_MAIL_ATTR");
        $LDAP_AGENT_CIVILITE = $this->liredbconstante("LDAP_AGENT_CIVILITE_ATTR");
        $LDAP_AGENT_UID_ATTR = $this->liredbconstante("LDAP_AGENT_UID_ATTR");
        $LDAP_CODE_AGENT_ATTR = $this->liredbconstante("LDAPATTRIBUTE");

        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $filtre = "($LDAP_CODE_AGENT_ATTR=" . $agentid . ")";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array("$LDAP_AGENT_NOM","$LDAP_AGENT_PRENOM","$LDAP_AGENT_MAIL", "$LDAP_AGENT_CIVILITE", "$LDAP_AGENT_UID_ATTR");
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        $nomagent = null;
        if (isset($info[0]["$LDAP_AGENT_NOM"][0])) 
        {
            $nomagent = $info[0]["$LDAP_AGENT_NOM"][0];
        }
        $prenomagent = null;
        if (isset($info[0]["$LDAP_AGENT_PRENOM"][0])) 
        {
            $prenomagent = $info[0]["$LDAP_AGENT_PRENOM"][0];
        }
        $mailagent = null;
        if (isset($info[0]["$LDAP_AGENT_MAIL"][0])) 
        {
            $mailagent = $info[0]["$LDAP_AGENT_MAIL"][0];
        }
        $civiliteagent = null;
        if (isset($info[0]["$LDAP_AGENT_CIVILITE"][0])) 
        {
            $civiliteagent = $info[0]["$LDAP_AGENT_CIVILITE"][0];
        }
        if (isset($info[0]["$LDAP_AGENT_UID_ATTR"][0])) 
        {
            $uid = $info[0]["$LDAP_AGENT_UID_ATTR"][0];
        }

        if (!is_null($nomagent) and !is_null($prenomagent) and !is_null($mailagent) and !is_null($civiliteagent))
        {
            $newagent = new agent($this->dbconnect);
            $newagent->civilite($civiliteagent);
            $newagent->nom(strtoupper($nomagent));
            $newagent->prenom(strtoupper($prenomagent));
            $newagent->mail($mailagent);
            $newagent->typepopulation($typepopulation);
            $newagent->uid($uid);
            $newagent->structureid('');  // On force sa structure à 'vide'
            if ($store)
            {
                if (!$newagent->store($agentid)) 
                {
                    $errlog = "createldapagentfromagentid : L'agent $agentid ($civiliteagent $nomagent $prenomagent) => mail = $mailagent n'a pas pu être créé dans la base de données. \n";
                    error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
                    if ($this->executionbatch())
                    {
                        echo "$errlog";
                    }
                    return false;
                }
                else
                {
                    $errlog = "createldapagentfromagentid : L'agent $agentid ($civiliteagent $nomagent $prenomagent) a été ajouté (mail = $mailagent) \n";
                    error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
                    if ($this->executionbatch())
                    {
                        echo "$errlog";
                    }
                    return $newagent;
                }
            }
            else
            {
                return $newagent;
            }
        }
        else
        {
            $errlog = "createldapagentfromagentid : Au moins une information obligatoire manquante dans LDAP => agentid = $agentid  civiliteagent = $civiliteagent  nomagent = $nomagent  prenomagent = $prenomagent  mail = $mailagent \n";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            if ($this->executionbatch())
            {
                echo "$errlog";
            }
            return false;
        }
        
    }
    
    function executionbatch()
    {
        global $uid;
        // Si un useragent est défini => sans doute qu'il y a un appel depuis un navigateur/browser.
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            //error_log(basename(__FILE__) . $this->stripAccents(" Le useragent = " . $_SERVER['HTTP_USER_AGENT'] . "."));

            // Si le useragent n'est pas vide => C'est sûr qu'il y a un navigateur => Pas mode batch
            if (trim($_SERVER['HTTP_USER_AGENT'] . "")!='')
            {
                return false;
            }
            // Le useragent est vide => On considère qu'il n'y a pas de navigateur/browser => Mode batch
            else
            {
                return true;
            }
        }
        // Le useragent n'est pas défini => Ce n'est pas un appel depuis un navigateur/browser => Mode batch
        else
        {
            //error_log(basename(__FILE__) . $this->stripAccents(" Le useragent n'est pas défini."));
            return true;
        }

        // if (!isset($uid) or $uid == "")
        // {
        //     return true;
        // }
        // else
        // {
        //     return false;
        // }
    }
    
    function getagentidfromldapuid($uid)
    {
        $agentid = '';
        $sql = "SELECT AGENT.AGENTID FROM AGENT WHERE AGENT.UID = ?";
        $params = array($uid);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->getagentidfromldapuid : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 1) 
        {
            $result = mysqli_fetch_row($query);
            $agentid = trim($result[0] . "");
            if ($agentid != "")
            {
                return $agentid;
            }
        }
        // Si on est là c'est :
        //   * Soit il n'y a pas l'uid dans la base de données
        //   * Soit il y en a plusieurs => ?? ça serait étrange
        //   * Soit l'agentid est vide dans la base de données => en théorie impossible

        $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $this->liredbconstante("LDAPATTRIBUTE");
        $LDAP_UID_AGENT_ATTR = $this->liredbconstante("LDAP_AGENT_UID_ATTR");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $filtre = "($LDAP_UID_AGENT_ATTR=$uid)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array("$LDAP_CODE_AGENT_ATTR");
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        // error_log(basename(__FILE__) . $this->stripAccents(" Le numéro AGENT de l'utilisateur issu de LDAP est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0]));
        if (!isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
        {
            $errlog = "getagentidfromldapuid : L'agent $uid n'a pas pu être identifié dans LDAP. \n";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            if ($this->executionbatch())
            {
                // On est en mode batch
                echo "$errlog";
            }
            return false;
        }
        $agentid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];       
        return $agentid;
    }

    function convertvaluetobool($value)
    {
        if (is_bool($value))
        {
            return $value;
        }

        switch (trim(strtoupper($value . '')))
        {
            case '':
                return false;
            case 'Y':
            case 'YES':
            case 'O':
            case 'OUI':
            case '1':
            case 'ON':
                return true;
                break;
            case 'N':
            case 'NO':
            case 'NON':
            case '0':
            case 'OFF':
                return false;
                break;
            default:
                echo "Valeur non reconnue ($value) => On retourne FALSE";
                return false;
        }        
    }

    function createesignaturestepsJson($tabparam)
    {
        $stepsJsonArray = array();
        $currentsteps = array();
        $previousstepnumber = null;

        //var_dump($tabparam);
        if (!isset($tabparam['levelextrainfos']))
        {
            return $tabparam;
        }
        
        //$tabmaildemandeur = array();
        foreach ($tabparam['levelextrainfos'] as $niveau => $stepinfos)
        {
            foreach ($stepinfos->signataires as $idsignataire => $adressemail)
            {
                // Si on a changé d'étape de signature
                if ($niveau !== $previousstepnumber)
                {
                    //var_dump("changement niveau $niveau");
                    // On réinitialise le currentstep à un tableau vide car on change de niveau de signature
                    $currentsteps = array();

                    // Si des extras-infos sont disponibles pour l'étape courante, on les ajoute
                    if (isset($tabparam['levelextrainfos'][$niveau]))
                    {
                        $temparray = $tabparam['levelextrainfos'][$niveau]->converttoarray();
                        foreach((array)$temparray as $extrainfoskey => $extrainfosvalue)
                        {
                            $currentsteps[$extrainfoskey] = $extrainfosvalue;
                        }
                    }
                    // Si le "stepNumber" n'est pas défini dans les extrainfos => On le défini manuellement
                    if (!isset($currentsteps["stepNumber"]))
                    {
                        $currentsteps["stepNumber"] = $niveau;
                    }
                }
                // On ajoute dans les recipients de l'étape en cours, l'email de recipient
                $currentsteps["recipients"][] = array("email" => $adressemail);
                // On ajoute l'étape dans le tableau JSON => S'il existe déjà il sera remplacé
                // Attention : Il faut commencer à l'index 0 (en entier et non chaine de caractères) pour que le JSON le convertisse bien.
                $stepsJsonArray[intval($niveau)-1] = $currentsteps;
                // On mémorise le niveau courant de l'étape pour détecter un changement lors de la prochaine boucle
                $previousstepnumber = $niveau;
            }
        }

        if (count($stepsJsonArray)>0)
        {
            // var_dump($stepsJsonArray);
            $tabparam["stepsJsonString"] = json_encode($stepsJsonArray);
            unset($tabparam['levelextrainfos']);
        }

        //var_dump("tabparam = "); var_dump($tabparam);
        return $tabparam;
    }
    
    function teletravailjsonresponse($teletravail, $verifinit = true)
    {
        if (!($teletravail instanceof teletravail))
        {
            $errlog = "L'objet passé en paramètre n'est pas un teletravail";
            error_log(basename(__FILE__) . $this->stripAccents(" teletravailjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }
        if ($verifinit and trim($teletravail->teletravailid().'') == '')
        {
            $errlog = "L'objet teletravail passé en paramètre n'est pas initialisé";
            error_log(basename(__FILE__) . $this->stripAccents(" teletravailjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }
        $somme = 0;
        $indexjour = 0;
        $errlog = "";
        $esignatureid = $teletravail->esignatureid();
        $nbjoursdemande = (substr_count($teletravail->tabteletravail(),1)/2);  // On compte le nombre de 1 dans le tableau de télétravail et on divise par 2 (1 = 1/2 journée)
        for ($index = 0 ; $index < strlen($teletravail->tabteletravail()) ; $index ++)
        {
            $demijrs = substr($teletravail->tabteletravail(),$index,1);
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
                        $infojour = $this->nomjourparindex(intdiv($index,2)+1) . " " . $this->nommoment(fonctions::MOMENT_MATIN); // => intdiv($index,2)+1 car pour PHP 0 = dimanche et nous 0 = lundi
                    }
                    elseif ($somme == 2) // Que l'après-midi
                    {
                        $infojour = $this->nomjourparindex(intdiv($index,2)+1) . " " . $this->nommoment(fonctions::MOMENT_APRESMIDI);
                    }
                    elseif ($somme == 3) // Toute la journée
                    {
                        $infojour = $this->nomjourparindex(intdiv($index,2)+1) . " toute la journée";
                    }
                    else // Là, on ne sait pas !!
                    {
                        $infojour = "Problème => index = $index  demijrs = $demijrs   somme = $somme";
                    }

                    $indexjour++;
                    $information_jourteletravail[$indexjour] = array('name' => "jour" . $indexjour, 'description' => "Jour $indexjour de télétravail", 'value' => $infojour);
                }
                $somme = 0;
            }
        }

        $agent = new agent($this->dbconnect);
        if (!$agent->load($teletravail->agentid()))
        {
            $errlog = 'Agent inconnu';
        }
        else
        {
            // On calcule le nombre de jours de télétravail auquel l'agent à droit :
            $affectation = null;
            $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y'));
            if (count(array($affectationliste)) == 0)
            {
                $errlog = "Pas d'affectation pour cet agent";
            }
            else
            {
                $affectation = current($affectationliste);
                $information_typeconvention = array(
                    'name' => "typeconvention", 
                    'description' => "Type de convention de télétravail", 
                    'value' => $teletravail->libelletypeconvention($teletravail->typeconvention()), 
                    'code' => $teletravail->typeconvention(),
                    "sante" => "" . $this->convert_value_to_on_off($teletravail->motifmedicalsante()),
                    "grossesse" => "" . $this->convert_value_to_on_off($teletravail->motifmedicalgrossesse()), 
                    "aidant" => "" . $this->convert_value_to_on_off($teletravail->motifmedicalaidant()),
                    "equipementcasque" => "" . $this->convert_value_to_on_off($teletravail->demande_materiel(teletravail::MATERIEL_CASQUE)),
                    "equipementsac" => "" . $this->convert_value_to_on_off($teletravail->demande_materiel(teletravail::MATERIEL_SAC)),
                    "equipementsouris" => "" . $this->convert_value_to_on_off($teletravail->demande_materiel(teletravail::MATERIEL_SOURIS)),
                    "equipementbase" => "" . $this->convert_value_to_on_off($teletravail->demande_materiel(teletravail::MATERIEL_STATION)),
                    "equipementordinateur" => "" . $this->convert_value_to_on_off($teletravail->demande_materiel(teletravail::MATERIEL_PORTABLE)),
                    "activiteteletravail" => "" . $teletravail->activiteteletravail(),
                    "periodeexclusion" => "" . $teletravail->periodeexclusion(),
                    "periodeadaptation" => "" . $teletravail->periodeadaptation(),
                    "creationg2t" => "" . $teletravail->creationg2t(),
                    "creationesignature" => "" . $teletravail->creationesignature()
                );
                $information_nombrejours = array(
                    'name' => "nombrejours", 
                    'description' => "Nombre de jours de télétravail demandé", 
                    'value' => "$nbjoursdemande"
                );
                $information_datedebut = array(
                    'name' => "datedebut", 
                    'description' => "Date de début de la convention télétravail", 
                    'value' => $this->formatdate($teletravail->datedebut())
                );
                $information_datefin = array(
                    'name' => "datefin", 
                    'description' => "Date de fin de la convention télétravail", 
                    'value' => $this->formatdate($teletravail->datefin())
                );
                $structure = new structure($this->dbconnect);
                if (!$structure->load($affectation->structureid()))
                {
                    $errorlog = "Structure introuvable";
                }
            }
        }
        $anneeref = "";
        if ($errlog != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos de la convention télétravail " . $esignatureid . " => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
        }
        else
        {
            $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
            if (count(array($affectationliste)) > 0)
            {
                $affectation = current($affectationliste);
                // On enlève les retours chariots
                //$agentadresse = trim(preg_replace('/\s+/', ', ',$teletravail->adresseteletravail())); //   $teletravail->adresseteletravail(); // $agent->getpersonnaladdress();
                //$agentadresse = trim(preg_replace('/\W+/', ', ',$teletravail->adresseteletravail()));   // $teletravail->adresseteletravail(); 
                //$agentadresse = trim(preg_replace('/[[:^print:]]/', ', ', $teletravail->adresseteletravail()));
                //$agentadresse = trim(preg_replace('/(\n)+(\r\n)+(\n\r)+(\r)+/', ', ', $teletravail->adresseteletravail()));
                $agentadresse = trim(str_replace(["\n","\r","\t"], ', ', $teletravail->adresseteletravail()));
                // On supprime les ', ' qui se suivent pour les remplacer par un seul
                $agentadresse = trim(preg_replace("/(, )+/", ', ', $agentadresse));

                $nameStructComplete = $structure->nomcompletcet();
                // quotité sur la période 01/09/N-1 - 31/08/N
                $quotite = $affectation->quotite();

                $agent = array('uid' => $agent->agentid(),
                    'email' => $agent->mail(),
                    'name' => $agent->nom(),
                    'firstname' => $agent->prenom(),
                    'service' => array('name' => $nameStructComplete,
                        'id' => $structure->id(),
                        'addr' => strtoupper($agentadresse), //$infosLdap[LDAP_AGENT_PERSO_ADDRESS_ATTR].""),
                        'type' => $structure->typestruct()),
                    'ref_year' => $anneeref,
                    'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                    'corps' => $agent->typepopulation(),
                    'rifseep' => $agent->fonctionRIFSEEP()
                );
                error_log(basename(__FILE__) . $this->stripAccents(" Lecture OK des infos de convention télétravail " . $esignatureid . " => Pas d'erreur"));
//                            $result_json = array('agent' => $agent, 'infosconvention' => $information_typeconvention, 'informations' => array('nbjours' => $information_nombrejours, 'infosjours' => $information_jourteletravail));
                $result_json = array(
                    'agent' => $agent, 
                    'infosconvention' => $information_typeconvention, 
                    'informations' => array_merge(
                            array($information_nombrejours), 
                            array($information_datedebut), 
                            array($information_datefin), 
                            $information_jourteletravail
                            )
                );
                //error_log(basename(__FILE__) . $this->stripAccents(" Le json resutat => " . print_r($result_json,true)));
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos de convention télétravail " . $esignatureid . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
                $result_json = array('status' => 'Error', 'description' => "Impossible de déterminer la quotité de travail de l'agent.");
            }
        }
        return $result_json;
    }
    
    function alimentationCETjsonresponse($alimentationCET, $verifinit = true)
    {
        if (!($alimentationCET instanceof alimentationCET))
        {
            $errlog = "L'objet passé en paramètre n'est pas une alimentation de CET";
            error_log(basename(__FILE__) . $this->stripAccents(" alimentationCETjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }
        if ($verifinit and trim($alimentationCET->alimentationid().'') == '')
        {
            $errlog = "L'objet alimentationCET passé en paramètre n'est pas initialisé";
            error_log(basename(__FILE__) . $this->stripAccents(" alimentationCETjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }
        
        $errlog = "";
        
        $valeur_a = $alimentationCET->valeur_a();
        $valeur_b = $alimentationCET->valeur_b();
        $valeur_c = $alimentationCET->valeur_c();
        $valeur_d = $alimentationCET->valeur_d();
        $valeur_e = $alimentationCET->valeur_e();
        $valeur_f = $alimentationCET->valeur_f();
        $valeur_g = $alimentationCET->valeur_g();
        $information_A = array('name' => "A", 'description' => "Solde du CET avant versement", 'value' => $valeur_a);
        $information_B = array('name' => "B", 'description' => "Droits à congés (en jours) au titre de l’année de référence", 'value' => $valeur_b);
        $information_C = array('name' => "C", 'description' => "Nombre de jours de congés utilisés au titre de l’année de référence", 'value' => $valeur_c);
        $information_D = array('name' => "D", 'description' => "Solde de jours de congés non pris au titre de l’année de référence", 'value' => $valeur_d);
        $information_E = array('name' => "E", 'description' => "Nombre de jours de congés reportés sur l’année suivante", 'value' => $valeur_e);
        $information_F = array('name' => "F", 'description' => "Alimentation du CET", 'value' => $valeur_f);
        $information_G = array('name' => "G", 'description' => "Solde du CET après versement", 'value' => $valeur_g);

        $agent = new agent($this->dbconnect);
        $agent->load($alimentationCET->agentid());
        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
        if (count(array($affectationliste)) > 0)
        {
            $affectation = current($affectationliste);
            $structure = new structure($this->dbconnect);
            $structure->load($affectation->structureid());
        }

        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = '" .  $alimentationCET->typeconges()  . "'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans le chargement de l'année de reférence : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        elseif (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "Impossible de déterminer l'année de référence pour le type " . $alimentationCET->typeconges();
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        else
        {
            $result = mysqli_fetch_row($query);
            $anneeref = "Année universitaire " . $result["0"] . "/" . ($result["0"]+1);
        }


        if ($errlog != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos de la demande d'alimentation " . $alimentationCET->alimentationid() . " => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
        }
        else
        {
            $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
            if (count(array($affectationliste)) > 0)
            {
                $affectation = new affectation($this->dbconnect);
                $affectation = current($affectationliste);
                $agentadresse = $agent->getprofessionaladdress(); //$agent->getInfoDocCet();
                $nameStructComplete = $structure->nomcompletcet();
                // quotité sur la période 01/09/N-1 - 31/08/N
                $datedebut = ($this->anneeref() - 1).$this->debutperiode();
                $datefin = $this->anneeref().$this->finperiode();
                $quotite = round($agent->getQuotiteMoyPeriode($datedebut, $datefin), 0, PHP_ROUND_HALF_EVEN).'%';
                $agent = array('uid' => $agent->agentid(),
                    'email' => $agent->mail(),
                    'name' => $agent->nom(),
                    'firstname' => $agent->prenom(),
                    'service' => array(
                                       'name' => $nameStructComplete,
                                       'id' => $structure->id(),
                                       'addr' => $agentadresse, //$infosLdap[LDAP_AGENT_ADDRESS_ATTR]."",
                                       'type' => $structure->typestruct()
                                      ),
                    'ref_year' => $anneeref,
                    'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                    'corps' => $agent->typepopulation()
                );
                error_log(basename(__FILE__) . $this->stripAccents(" Lecture OK des infos de la demande d'alimentation " . $alimentationCET->alimentationid() . " => Pas d'erreur"));
                $result_json = array('agent' => $agent, 'informations' => array($information_A, $information_B, $information_C, $information_D, $information_E, $information_F, $information_G));
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos de la demande d'alimentation " . $alimentationCET->alimentationid() . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
                $result_json = array('status' => 'Error', 'description' => "Impossible de déterminer la quotité de travail de l'agent.");
            }
        }
        return $result_json;
    }
    
    function optionCETjsonresponse($optionCET, $verifinit = true)
    {
        if (!($optionCET instanceof optionCET))
        {
            $errlog = "L'objet passé en paramètre n'est pas une option de CET";
            error_log(basename(__FILE__) . $this->stripAccents(" optionCETjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }
        if ($verifinit and trim($optionCET->optionid().'') == '')
        {
            $errlog = "L'objet optionCET passé en paramètre n'est pas initialisé";
            error_log(basename(__FILE__) . $this->stripAccents(" optionCETjsonresponse error => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
            return $result_json;
        }

        $errlog = "";
        
        $valeur_a = $optionCET->valeur_a();
        $valeur_g = $optionCET->valeur_g();
        $valeur_h = $optionCET->valeur_h();
        $valeur_i = $optionCET->valeur_i();
        $valeur_j = $optionCET->valeur_j();
        $valeur_k = $optionCET->valeur_k();
        $valeur_l = $optionCET->valeur_l();
        $information_A = array('name' => "A", 'description' => "Solde du CET avant versement", 'value' => $valeur_a);
        $information_G = array('name' => "G", 'description' => "Solde du CET après versement", 'value' => $valeur_g);
        $information_H = array('name' => "H", 'description' => "Nombre de jours dépassant le seuil de 15 jours", 'value' => $valeur_h);
        $information_I = array('name' => "I", 'description' => "Nombre de jours à prendre en compte au titre du RAFP", 'value' => $valeur_i);
        $information_J = array('name' => "J", 'description' => "Nombre de jours à indemniser", 'value' => $valeur_j);
        $information_K = array('name' => "K", 'description' => "Nombre de jours à maintenir sur le CET sous forme de congés", 'value' => $valeur_k);
        $information_L = array('name' => "L", 'description' => "Solde du CET après option", 'value' => $valeur_l);

        $agent = new agent($this->dbconnect);
        $agent->load($optionCET->agentid());
        $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
        if (count(array($affectationliste)) > 0)
        {
            $affectation = current($affectationliste);
            $structure = new structure($this->dbconnect);
            $structure->load($affectation->structureid());
        }

        $anneeref = "Année universitaire " . $optionCET->anneeref() . "/" . ($optionCET->anneeref()+1);

        if ($errlog != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $optionCET->optionid() . " => Erreur = " . $errlog));
            $result_json = array('status' => 'Error', 'description' => $errlog);
        }
        else
        {
            $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
            if (count(array($affectationliste)) > 0)
            {
                $affectation = new affectation($this->dbconnect);
                $affectation = current($affectationliste);
                $agentadresse = $agent->getprofessionaladdress(); // $agent->getInfoDocCet();
                $nameStructComplete = $structure->nomcompletcet();
                // quotité sur la période 01/09/N-1 - 31/08/N
                $datedebut = ($this->anneeref() - 1).$this->debutperiode();
                $datefin = $this->anneeref().$this->finperiode();
                $quotite = round($agent->getQuotiteMoyPeriode($datedebut, $datefin), 0, PHP_ROUND_HALF_EVEN).'%';

                $agent = array('uid' => $agent->agentid(),
                    'email' => $agent->mail(),
                    'name' => $agent->nom(),
                    'firstname' => $agent->prenom(),
                    'service' => array(
                                       'name' => $nameStructComplete,
                                       'id' => $structure->id(),
                                       'addr' => $agentadresse, //$infosLdap[LDAP_AGENT_ADDRESS_ATTR]."",
                                       'type' => $structure->typestruct()
                                      ),
                    'ref_year' => $anneeref,
                    'activity' => $quotite == '100%' ? 'Temps complet' : $quotite,
                    'corps' => $agent->typepopulation()
                );
                error_log(basename(__FILE__) . $this->stripAccents(" Lecture OK des infos du droit d'option " . $optionCET->optionid() . " => Pas d'erreur"));
                $result_json = array('agent' => $agent, 'informations' => array($information_A, $information_G, $information_H, $information_I, $information_J, $information_K, $information_L));
                //error_log(basename(__FILE__) . $this->stripAccents(" Le json resutat => " . print_r($result_json,true)));
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" Erreur lors de la lecture des infos du droit d'option " . $optionCET->optionid() . " => Erreur = Impossible de déterminer la quotité de travail de l'agent."));
                $result_json = array('status' => 'Error', 'description' => "Impossible de déterminer la quotité de travail de l'agent.");
            }
        }
        return $result_json;
    }
    
    function listeg2tuser($restriction = null)
    {
        $returnlist = array();
        $sql = "
            SELECT DISTINCT AGENT.AGENTID, AGENT.ADRESSEMAIL 
            FROM AGENT,STRUCTURE, AFFECTATION
            WHERE ((TRIM(IFNULL(AGENT.STRUCTUREID,'')) != '') 
                AND AGENT.AGENTID > 0 
                AND TRIM(IFNULL(AGENT.STRUCTUREID,'')) = STRUCTURE.STRUCTUREID
                AND STRUCTURE.ISDEPLOYED = 'O' 
                AND AGENT.AGENTID = AFFECTATION.AGENTID
                AND AFFECTATION.DATEFIN >= CURDATE())
                ###RESTRICTION###
            UNION
            SELECT DISTINCT AGENT.AGENTID, AGENT.ADRESSEMAIL 
            FROM AGENT,STRUCTURE
            WHERE (
                    (AGENT.AGENTID = TRIM(IFNULL(STRUCTURE.RESPONSABLEID,'')) AND AGENT.AGENTID > 0 AND STRUCTURE.ISDEPLOYED = 'O')
                 OR (AGENT.AGENTID = TRIM(IFNULL(STRUCTURE.GESTIONNAIREID,'')) AND AGENT.AGENTID > 0 AND STRUCTURE.ISDEPLOYED = 'O')
                 OR (AGENT.AGENTID = TRIM(IFNULL(STRUCTURE.IDDELEG,'')) AND DATEFINDELEG > CURDATE() AND AGENT.AGENTID > 0 AND STRUCTURE.ISDEPLOYED = 'O')
                  )
                ###RESTRICTION###
        ";
        
        if (is_null($restriction))
        {
            $sql = str_replace("###RESTRICTION###","",$sql);
        }
        elseif (is_string($restriction) or is_numeric($restriction))
        {
            $sql = str_replace("###RESTRICTION###","AND AGENT.AGENTID = '$restriction' ",$sql);
        }
        elseif (is_array($restriction))
        {
            $sql = str_replace("###RESTRICTION###","AND AGENT.AGENTID IN ('" . implode("','",$restriction) . "')",$sql);
        }
        //var_export($sql);
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur_requete = mysqli_error($this->dbconnect);
        if ($erreur_requete != "")
        {
            error_log(basename(__FILE__) . " " . $erreur_requete);
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $returnlist["$result[0]"] = "$result[1]";
        }
        return $returnlist;

    }
    
    function premierjourdumoissuivant($date)
    {
        $date = strtotime($this->formatdatedb($date));
        $mois = date("m",$date);
        $annee = date("Y",$date);
        
        return date("Ymd",strtotime($annee.$mois."01" . " + 1 month"));
    }
    
    function dernierjourmoisprecedent($date)
    {
        $date = strtotime($this->formatdatedb($date));
        $mois = date("m",$date);
        $annee = date("Y",$date);
        
        return date("Ymd",strtotime($annee.$mois."01" . " - 1 day"));
    }
    
    function mailbody_avis(demande $demande) : string
    {
        $corpmail = "";
        $agent = $demande->agent();
        $resp = $agent->getsignataire();
        if (!is_null($resp) and ($resp!==false))
        {
            $corpmail = "Une demande";
            if ($this->estunconge($demande->type()))
            {
                $corpmail = $corpmail . " de congés ";
            }
            else
            {
                $corpmail = $corpmail . " d'absence ";
            }
            $corpmail = $corpmail . "(" . $demande->typelibelle() . ") de  " . mb_convert_case($agent->identitecomplete(), MB_CASE_TITLE) . " du "  . $demande->datedebut() . " au " . $demande->datefin() . " (" . $demande->nbrejrsdemande();
            if ($demande->nbrejrsdemande()>1)
            {
                $corpmail = $corpmail . " jours";
            }
            else
            {
                $corpmail = $corpmail . " jour";
            }
            $corpmail = $corpmail . ") nécessite votre attention.";
            $corpmail = $corpmail . "\n\nMerci d'indiquer à " . mb_convert_case($resp->identitecomplete(), MB_CASE_TITLE) . " votre avis.\n"; 
        }
        return $corpmail;
    }

    //function lirecommentaire(string $commentaireid): commentaireconge|null // ATTENTION PHP 8.0 seulement
    function lirecommentaire(string $commentaireid)
    {
        $commentaireconge = null;
        $sql = "SELECT COMMENTAIRECONGE.COMMENTAIRECONGEID,
                       COMMENTAIRECONGE.AGENTID,
                       COMMENTAIRECONGE.TYPEABSENCEID,
                       COMMENTAIRECONGE.DATEAJOUTCONGE,
                       COMMENTAIRECONGE.COMMENTAIRE,
                       COMMENTAIRECONGE.NBRJRSAJOUTE,
                       COMMENTAIRECONGE.AUTEURID,
                       TYPEABSENCE.LIBELLE,
                       COMMENTAIRECONGE.NBJRSPRIS
                FROM COMMENTAIRECONGE, TYPEABSENCE
                WHERE COMMENTAIRECONGE.COMMENTAIRECONGEID= ? 
                AND TYPEABSENCE.TYPEABSENCEID = COMMENTAIRECONGE.TYPEABSENCEID";

        $params = array($commentaireid);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            echo "Fonctions->lirecommentaire : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Fonctions->lirecommentaire : " . $erreur);
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
            $commentaireconge->nbjrspris = $result[8];
        }
        return $commentaireconge;
    }

    function finvaliditerecuperation(string $dateref, string $typeabsence)
    {
        $congesanneeref = trim($this->congesanneeref($typeabsence));
        if ($congesanneeref=="" or $congesanneeref>2100) // Si l'année est référence est dans le futur ou vide => on calcule par rapport à la durée des récups.
        {
            $dbconstante = 'VALIDRECUP';
            $validrecup = '2';
            if ($this->testexistdbconstante($dbconstante)) { $validrecup = $this->liredbconstante($dbconstante); }

            $dateref = $this->formatdatedb($dateref);
            $datefin = date('Ymd',strtotime('+' . $validrecup . ' month',strtotime($dateref)));
        }
        else
        {
            // Bloc pour limiter la validité des récupérations à la date de fin de période => Donc fin de l'année universitaire
            $datefin = ($congesanneeref+1) . $this->finperiode();
        } 
        return $datefin;
    }

    function demandelistepartypeabsence($typeabsenceid, $anneeref)
    {
        $demandeliste = array();
        $datedebut = $anneeref . $this->debutperiode();
        $datefin = ($anneeref+1) . $this->finperiode();

        $sql = "SELECT DEMANDE.DEMANDEID
                FROM DEMANDE
                WHERE DEMANDE.TYPEABSENCEID = ? 
                  AND ((DEMANDE.DATEDEBUT <= ? AND DEMANDE.DATEFIN >= ? )
                      OR (DEMANDE.DATEFIN >= ? AND DEMANDE.DATEDEBUT <= ? )
                      OR (DEMANDE.DATEDEBUT >= ? AND DEMANDE.DATEFIN <= ? ))";

        $params = array($typeabsenceid, $datedebut, $datedebut, $datefin, $datefin, $datedebut, $datefin);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            echo "Fonctions->demandelistepartypeabsence : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Fonctions->demandelistepartypeabsence : " . $erreur);
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $demande = new demande($this->dbconnect);
            $demande->load($result[0]);
            $demandeliste[] = $demande;

        }
        return $demandeliste;
        
    }

    function ajoutesignatureheader(&$curl)
    {
        static $token = '';   //'457a2879-530a-45f0-a2cc-54c54032b923';

        $dbconstante = 'ESIGNATURETOKEN';
        if ($this->testexistdbconstante($dbconstante))
        {
            $token = $this->liredbconstante($dbconstante);
        }

        $header = array();
        if (strlen($token)>0)
        {
            $header[] = "X-API-Key: $token";
        }

        if (count($header)>0)
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
    }

    function listestructureteletravailasigner($agentid)
    {
        $listestruct = array();
        $sql = "SELECT AGENT.STRUCTUREID
                FROM TELETRAVAIL, AGENT 
                WHERE TELETRAVAIL.STATUTRESPONSABLE = 'a'
                  AND TELETRAVAIL.STATUT = 'a'
                  AND INSTR(CONCAT(',',TELETRAVAIL.LISTEIDRESPONSABLE,','),CONCAT(',',?, ','))>0
                  AND AGENT.AGENTID = TELETRAVAIL.AGENTID";

        $params = array($agentid);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            echo "Fonctions->listestructureteletravailasigner : " . $erreur . "<br>";
            error_log(basename(__FILE__) . " Fonctions->listestructureteletravailasigner : " . $erreur);
        }
        while ($result = mysqli_fetch_row($query)) 
        {
            $structure = new structure($this->dbconnect);
            $structure->load($result[0]);
            $listestruct[$structure->id()] = $structure;
        }
        return $listestruct;

    }

    function clean_ms($texz) {
        // $texz = stripslashes(stripslashes($texz));
        $find = array();
        $replace = array();
        $find[] = "\342\200\176";
        $find[] = "\342\200\177";
        $find[] = "\342\200\230";
        $find[] = "\342\200\231";
        $find[] = "\342\200\232";
        $find[] = "\342\200\233";
        $find[] = "\342\200\234";
        $find[] = "\342\200\235";
        $find[] = "\342\200\041";
        $find[] = "\342\200\174";
        $find[] = "\342\200\220";
        $find[] = "\342\200\223";
        $find[] = "\342\200\224";
        $find[] = "\342\200\225";
        $find[] = "\342\200\042";
        $find[] = "\342\200\246";

        $replace[] = "'";
        $replace[] = "'";
        $replace[] = "'";
        $replace[] = "'";
        $replace[] = ',';
        $replace[] = "'";
        $replace[] = '"';
        $replace[] = '"';
        $replace[] = '-';
        $replace[] = '-';
        $replace[] = '-';
        $replace[] = '-';
        $replace[] = '--';
        $replace[] = '--';
        $replace[] = '--';
        $replace[] = '...';

        $texz = str_replace($find, $replace,$texz);
        return $texz;
    } 
    
    function triparprofondeurabsolue($struct1, $struct2)
    {
        if ($struct1->profondeurabsolue()==$struct2->profondeurabsolue())
        {
            return 0;
        }
        return ($struct1->profondeurabsolue() < $struct2->profondeurabsolue()) ? -1 : 1;
    }

    
}

?>