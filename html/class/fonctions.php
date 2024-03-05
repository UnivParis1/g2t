<?php

class ttexception
{
    public $agentid = null;
    public $dateorigine = null;
    public $momentorigine = null;
    public $dateremplacement = null;
    public $momentremplacement = null;
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
    public const SIGNATAIRE_LIBELLE = array(fonctions::SIGNATAIRE_AGENT => "AGENT INDIVIDUEL", fonctions::SIGNATAIRE_STRUCTURE => "TOUS LES AGENTS D'UNE STRUCTURE", fonctions::SIGNATAIRE_RESPONSABLE => "RESPONSABLE DE STRUCTURE", fonctions::SIGNATAIRE_SPECIAL => "UTILISATEUR SPECIAL", fonctions::SIGNATAIRE_RESPONSABLE_N2 => "RESPONSABLE N+2");

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
     * @param string $date
     *            the date to convert into DB format (YYYYMMDD)
     *            Allowed format : DD/MM/YYYY or YYYY-MM-DD or YYYYMMDD
     * @return string the converted date
     */
    public function formatdatedb($date)
    {
        if (is_null($date)) {
            $errlog = "Fonctions->formatdatedb : La date est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } else {
            if (strlen($date) == 10 and substr_count($date, "/") == 2) {
                // On converti la date DD/MM/YYYY en YYYYMMDD
                $tempdate = substr($date, 6, 4) . substr($date, 3, 2) . substr($date, 0, 2);
                return $tempdate;
            } elseif (strlen($date) == 10 and substr_count($date, "-") == 2) {
                // On converti la date YYYY-MM-DD en YYYYMMDD
                $tempdate = str_replace("-", "", $date);
                return $tempdate;
            } elseif (strlen($date) == 8 and substr_count($date, "/") == 0) {
                // On ne fait rien ==> c'est deja une date correcte YYYMMDD
                return $date;
            } else {
                $errlog = "Fonctions->formatdatedb : Le format de la date est inconnu [Date=$date] !!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
        }
    }

    /**
     *
     * @param string $date
     *            the date to convert into french format (DD/MM/YYYY)
     *            Allowed format : DD/MM/YYYY or YYYY-MM-DD or YYYYMMDD
     * @return string the converted date
     */
    public function formatdate($date)
    {
        if (is_null($date)) {
            $errlog = "Fonctions->formatdate : La date est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } else {
            if (strlen($date) == 8 and substr_count($date, "/") == 0) {
                // On converti la date YYYYMMDD en DD/MM/YYYY
                $tempdate = substr($date, 6, 2) . "/" . substr($date, 4, 2) . "/" . substr($date, 0, 4);
                return $tempdate;
            } elseif (strlen($date) == 10 and substr_count($date, "-") == 2) {
                // On converti la date YYYY-MM-DD en DD/MM/YYYY
                $tempdate = substr($date, 8, 2) . "/" . substr($date, 5, 2) . "/" . substr($date, 0, 4);
                return $tempdate;
            } elseif (strlen($date) == 10 and substr_count($date, "/") == 2) {
                // On ne fait rien ==> c'est deja une date correcte DD/MM/YYYY
                return $date;
            } else {
                $errlog = "Fonctions->formatdate : Le format de la date est inconnu [Date=$date] !!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
        }
    }

    /**
     *
     * @param
     * @return string list (comma separated) of unworked days
     */
    public function jourferier()
    {
        // Chargement des jours fériés
        $dbconstante='FERIE%';
        if ($this->testexistdbconstante($dbconstante))
        {
            $jrs_feries_liste = $this->liredbconstante($dbconstante);
            //var_dump($jrs_feries_liste);
            $jrs_feries = ";";
            foreach ($jrs_feries_liste as $key => $liste)
            {
                $jrs_feries = $jrs_feries . $liste . ";";
            }
            //var_dump($jrs_feries);
            return $jrs_feries;
        }
        else
        {
            return "";
        }


/*
        $sql = "SELECT NOM,VALEUR FROM CONSTANTES WHERE NOM LIKE 'FERIE%'";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->jourferier : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->jourferier : Pas de jour férié défini dans la base";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        $jrs_feries = ";";
        while ($result = mysqli_fetch_row($query)) {
            $jrs_feries = $jrs_feries . $result[1] . ";";
        }

        // echo "Jours fériés = " . $jrs_feries . "<br>";
        return $jrs_feries;
*/
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
            $date = date("d/m/Y");
//        if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
//            setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
//        $monthname = strftime("%B", strtotime($this->formatdatedb($date)));

        $nummonth = date("n", strtotime($this->formatdatedb($date)));
        switch ($nummonth)
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

        if (mb_detect_encoding(ucfirst($monthname), 'UTF-8', true)) {
            return ucfirst($monthname);
        } else {
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
        if (is_null($index)) {
            $errlog = "Fonctions->nommoisparindex : L'index du mois est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } else {
            $index = $index % 12;
            if ($index==0) $index = 12;
            $monthname = $this->nommois("01/" . str_pad($index,  2, "0",  STR_PAD_LEFT) . "/2012");

            if (mb_detect_encoding(ucfirst($monthname), 'UTF-8', true)) {
                return ucfirst($monthname);
            } else {
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
    public function nomjour($date = null)
    {
        if (is_null($date))
            $date = date("d/m/Y");

//        if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
//            setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
//        $dayname = strftime("%A", strtotime($this->formatdatedb($date)));

        $numday = date("w", strtotime($this->formatdatedb($date)));
        switch ($numday)
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

        if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) {
            return ucfirst($dayname);
        } else {
            return $this->utf8_encode(ucfirst($dayname));
        }
    }

    /**
     *
     * @param string $index
     *            index of the day (1=Monday 7=Sunday)
     * @return string the (french) day name corresponding to the index
     */
    public function nomjourparindex($index = null) // 1 = Lundi 7 = Dimanche
    {
        if (is_null($index)) {
            $errlog = "Fonctions->nomjourparindex : L'index du jour est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        } else {
            $index = $index % 7;
//            if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
//                setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
                                                      // Le 01/01/2012 est un dimanche
//            $dayname = strftime("%A", strtotime("20120101" + $index));

            // Le 01/01/2012 est un dimanche
            $dayname = $this->nomjour("20120101" + $index);

            if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) {
                return ucfirst($dayname);
            } else {
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
        if (strcasecmp($result[0] . "",'o')==0) // Si la colonne vaut 'o' ou 'O'
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
            return FALSE;

        // On vérifie avec une REGExp si le format de la date est valide DD/MM/YYYY
        // if (!ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})",$date))
        if (! preg_match("`^([0-9]{2})\/([0-9]{2})\/([0-9]{4})`", $date))
            return FALSE;
        $jour = substr($date, 0, 2);
        $mois = substr($date, 3, 2);
        $annee = substr($date, 6);
        if (strlen($annee) != 4)
            return FALSE;
        // echo "jour = $jour mois = $mois annee = $annee <br>";
        return checkdate($mois, $jour, $annee);
    }

    /**
     *
     * @param
     * @return string the beginning of the period in format DDMM (typicaly = 0901 - 1 sept)
     */
    public function debutperiode()
    {
        $dbconstante='DEBUTPERIODE';
        if ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return "0901";
        }
/*
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'DEBUTPERIODE'";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->debutperiode : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->debutperiode : Pas de début de période défini dans la base ==> On force à '0901' (1sept).";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return "0901";
        }
        $result = mysqli_fetch_row($query);
        // echo "Fonctions->debutperiode : Debut de période ==> " . $result[0] . ".<br>";
        return "$result[0]";
*/
    }

    /**
     *
     * @param
     * @return string the end of the period in format MMDD (typicaly = 0831 - 31 aug)
     */
    public function finperiode()
    {
        $dbconstante='FINPERIODE';
        if ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return "0831";
        }
/*
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'FINPERIODE'";
        $params = array();
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->finperiode : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->finperiode : Pas de fin de période définie dans la base ==> On force à '0831' (31aout).";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return "0831";
        }
        $result = mysqli_fetch_row($query);
        // echo "Fonctions->finperiode : fin de période ==> " . $result[0] . ".<br>";
        return "$result[0]";
*/
    }

    /**
     *
     * @param string $date
     *            optional date to determin the reference year. If not set the current date is used.
     * @return string the reference year for the given date (format YYYY)
     */
    public function anneeref($date = null)
    {
        // echo "La date = " . $date . "<br>";
        if (is_null($date))
            $date = date("d/m/Y");
        // echo "La date = " . $date . "<br>";
        if ($this->verifiedate($date)) {
            $finperiode = $this->finperiode();
            // echo "Fin periode = $finperiode <br>";
            // echo "date(m, date(Y) . finperiode)= " .date("m", date("Y") . $finperiode) . "<br>";
            $date = $this->formatdatedb($date);
            $annee = substr($date, 0, 4);
            $mois = substr($date, 4, 2);
            // echo "annee = $annee mois = $mois <br>";
            if ($mois <= date("m", date("Y") . $finperiode))
                return ($annee - 1);
            else
                return $annee;
        } else {
            $errlog = "Fonctions->anneeref : La date " . $date . " est invalide !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
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
        if (strcasecmp($typeconge, 'cet') == 0)
            return TRUE;
        // Cas particulier du WE ==> Comme ce n'est pas un congé, il n'est pas dans la base de données.....
        if (strcasecmp($typeconge, "WE") == 0)
            return false;
        // Cas particulier de la période 'non déclarée' ==> Comme ce n'est pas un congé, il n'est pas dans la base de données.....
        if (strcasecmp($typeconge, "nondec") == 0)
            return false;
        if (strcasecmp($typeconge, "ferie") == 0)
            return false;
        if (strcasecmp($typeconge, "teletrav") == 0)
            return false;
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
     * @return string the constant value readed from the database
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
        $sql = $sql . " OR TYPEABSENCEID = 'teletravHC' ";
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
                $htmltext = $htmltext . "<td class='maincell'><table class='elementlegende'><tbody><tr><td><span class='legendecouleur' style='background-color:" . $legende["couleur"] . ";' bgcolor=" . $legende["couleur"] . "></span></td><td class='legendetexte' >" . $legende["libelle"] . "</td></tr></tbody></table></td>";
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
                $long_chps = $pdf->GetStringWidth($legende["libelle"]);
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
        }
    }

    /**
     *
     * @param string $codeouinon
     *            code (o/n)
     * @return string the oui/non label if correct / error message otherwise
     */
    public function ouinonlibelle($codeouinon = null)
    {
        if (is_null($codeouinon))
            return "Le codeouinon $codeouinon est inconnu";
        switch ($codeouinon) {
            case "o":
            case "O":
                return "Oui";
                break;
            case "n":
            case "N":
                return "Non";
                break;
            default:
                return "Le codeouinon $codeouinon est inconnu";
        }
    }

    public function demandestatutlibelle($statut = null)
    {
        if (strcasecmp($statut, demande::DEMANDE_VALIDE) == 0)
            return "Validée";
        elseif (strcmp($statut, demande::DEMANDE_REFUSE) == 0)
            return "Refusée";
        elseif (strcmp($statut, demande::DEMANDE_ANNULE) == 0)
            return "Annulée";
        elseif (strcasecmp($statut, demande::DEMANDE_ATTENTE) == 0)
            return "En attente";
        else
            echo "Demandestatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
    }

    public function teletravailstatutlibelle($statut = null)
    {
        if (strcasecmp($statut, teletravail::TELETRAVAIL_VALIDE) == 0)
            return "Validée";
            elseif (strcmp($statut, teletravail::TELETRAVAIL_REFUSE) == 0)
            return "Refusée";
            elseif (strcmp($statut, teletravail::TELETRAVAIL_ANNULE) == 0)
            return "Annulée";
            elseif (strcasecmp($statut, teletravail::TELETRAVAIL_ATTENTE) == 0)
            return "En attente";
            else
                echo "teletravailstatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
    }

    /**
     *
     * @param string $statut
     *            status code (v,r,a) for part time
     * @return string the status label of the part time if correct / display error message otherwise
     */
    public function declarationTPstatutlibelle($statut = null)
    {
        if (strcasecmp($statut, declarationTP::DECLARATIONTP_VALIDE) == 0)
            return "Validée";
        elseif (strcasecmp($statut, declarationTP::DECLARATIONTP_REFUSE) == 0)
            return "Refusée";
        elseif (strcasecmp($statut, declarationTP::DECLARATIONTP_ATTENTE) == 0)
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


    public function CETaverifier($datedebut)
    {
        $sql = "SELECT DISTINCT DEMANDEID,AGENTID, DATEDEBUT,DATESTATUT
    				FROM DEMANDE
    				WHERE TYPEABSENCEID = 'cet'
    				  AND (DATEDEBUT >= ?
    				    OR DATESTATUT >= ? )
    			    ORDER BY AGENTID, DATEDEBUT,DATESTATUT";
        $params = array($this->formatdatedb($datedebut),$this->formatdatedb($datedebut));
        $query = $this->prepared_select($sql, $params);
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

    public function savepdf($pdf, $filename)
    {
        $path = dirname("$filename");
        if (!file_exists($path))
        {
            mkdir("$path");
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

/*
        if (!is_null($date))
    	{
    	    $update = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = 'DEBUTALIMCET'";
    		$params = array($this->formatdatedb($date));
    		$query = $this->prepared_query($update, $params);
    	}
    	$sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'DEBUTALIMCET' AND VALEUR <> ''";
    	$params = array();
    	$query = $this->prepared_select($sql, $params);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "") {
    		$errlog = "Fonctions->debutalimcet : " . $erreur;
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    	}
    	if (mysqli_num_rows($query) == 0) {
    		$errlog = "Fonctions->debutalimcet : Pas de début de période défini dans la base. On force au 0831 de l'année univ de référence. ";
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    		return ($this->anneeref()+1).$this->finperiode();
    	}
    	$result = mysqli_fetch_row($query);
    	// echo "Fonctions->debutperiode : Debut de période ==> " . $result[0] . ".<br>";
    	return "$result[0]";
*/
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
        $dbconstante='IDMODELALIMCET';
        if ($this->testexistdbconstante($dbconstante))
        {
            return $this->liredbconstante($dbconstante);
        }
        else
        {
            return "";
        }

/*
    	$sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = 'IDMODELALIMCET'";
    	$params = array();
    	$query = $this->prepared_select($sql, $params);
    	$erreur = mysqli_error($this->dbconnect);
    	if ($erreur != "") {
    		$errlog = "Fonctions->getidmodelalimcet : " . $erreur;
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    	}
    	if (mysqli_num_rows($query) == 0) {
    		$errlog = "Fonctions->getidmodelalimcet : Pas d'identifiant de modele défini dans la base. ";
    		echo $errlog . "<br/>";
    		error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
    		return "";
    	}
    	$result = mysqli_fetch_row($query);
    	return "$result[0]";
*/
    }

    public function getidmodelteletravail($maxniveau, $agent)
    {
        // echo "<br>On est dans le cas d'un niveau $maxniveau<br>";
        $resp_n2 = $agent->getsignataire_niveau2();

        if ($maxniveau == 4 and $resp_n2===false)
        {
            $dbconstante='IDMODELTELETRAVAIL';
        }
        elseif ($maxniveau == 5 and $resp_n2!==false)
        {
            $dbconstante='IDMODELTELETRAVAIL_EVOLUE';
        }
        else
        {
            echo $this->showmessage(fonctions::MSGERROR, "Incohérence entre le nombre de niveau et la situation de l'agent (nombre de niveau = $maxniveau et l'agent " . (($resp_n2===false)?" n'a pas de ":" a un ")  . "responsable).");
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
        curl_close($curl);
        if ($error != "")
        {
            echo "Erreur Curl (synchro_g2t_eSignature) = " . $error . "<br><br>";
            error_log(basename(__FILE__) . $this->stripAccents(" Impossible de synchroniser G2T avec eSignature (id eSignature = $id, URL WS G2T = $full_g2t_ws_url) => Erreur : " . $error ));
            return "Pas de réponse du webservice G2T.";
        }
        //echo "<br>Le json (synchro_g2t_eSignature) " . print_r($json,true) . "<br>";
        error_log(basename(__FILE__) . $this->stripAccents(" Le json (synchro_g2t_eSignature) " . print_r($json,true)));
        $response = json_decode($json, true);
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

    public function get_alimCET_liste($typeconges, $listStatuts = array(), $forcesynchro = true) // $typeconges de la forme annYY
    {
        $full_g2t_ws_url = $this->get_g2t_ws_url() . "/alimentationWS.php";
        $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
        $sql = "SELECT ESIGNATUREID FROM ALIMENTATIONCET WHERE TYPECONGES = ? ";
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
        while ($result = mysqli_fetch_row($query)) {
            $alimid = $result[0];
            $alimCETliste[] = $alimid;
            if ($forcesynchro)
            {
                $this->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
            }
        }
        return $alimCETliste;
    }

    public function get_optionCET_liste($anneeref, $listStatuts = array(), $forcesynchro = true)
    {
        $full_g2t_ws_url = $this->get_g2t_ws_url() . "/optionWS.php";
        $full_g2t_ws_url = preg_replace('/([^:])(\/{2,})/', '$1/', $full_g2t_ws_url);
        $sql = "SELECT ESIGNATUREID FROM OPTIONCET WHERE ANNEEREF = ? ";
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
            $optionid = $result[0];
            $optionCETliste[] = $optionid;
            if ($forcesynchro)
            {
                $this->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
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
        //error_log(basename(__FILE__) . $this->stripAccents(" CASuid = $CASuid"));

        $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $this->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_UID_AGENT_ATTR = $this->liredbconstante("LDAP_AGENT_UID_ATTR");
        $filtre = "($LDAP_UID_AGENT_ATTR=$CASuid)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array(
            "$LDAP_CODE_AGENT_ATTR"
        );
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        // error_log(basename(__FILE__) . $this->stripAccents(" Le numéro AGENT de l'utilisateur issu de LDAP est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0]));
        $user = new agent($this->dbconnect);
        if (!isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
        {
            $errlog = "useridfromCAS : L'agent $CASuid n'a pas pu être identifié dans LDAP.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        $userid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
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
        $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->liredbconstante("LDAP_ETAB_SEARCHBASE");    // 'dc=univ-paris1,dc=fr';
        $LDAP_AGENT_UID_ATTR = $this->liredbconstante("LDAP_AGENT_UID_ATTR");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_AGENT_MAIL_ATTR = $this->liredbconstante("LDAP_AGENT_MAIL_ATTR");
        $filtre = "($LDAP_AGENT_MAIL_ATTR=$adressemail)";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array("$LDAP_AGENT_UID_ATTR");
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        // error_log(basename(__FILE__) . $this->stripAccents(" Le numéro AGENT de l'utilisateur issu de LDAP est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0]));
        if (!isset($info[0]["$LDAP_AGENT_UID_ATTR"][0]))
        {
            $errlog = "mailexistedansldap : L'adresse mail $adressemail n'a pas pu être identifié dans LDAP.";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        return true;
    }
    
    function getcnfromldap($adressemail)
    {
        $LDAP_SERVER = $this->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $this->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $this->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $this->liredbconstante("LDAP_GROUP_SEARCHBASE");
        $LDAP_GROUP_CN_ATTR = $this->liredbconstante("LDAP_GROUP_CN_ATTR");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $LDAP_AGENT_MAIL_ATTR = $this->liredbconstante("LDAP_AGENT_MAIL_ATTR");
        $filtre = "($LDAP_AGENT_MAIL_ATTR=" . $adressemail . ")";
        $dn = $LDAP_SEARCH_BASE;
        $restriction = array("$LDAP_GROUP_CN_ATTR");
        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
        $info = ldap_get_entries($con_ldap, $sr);
        // error_log(basename(__FILE__) . $this->stripAccents(" Le numéro AGENT de l'utilisateur issu de LDAP est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0]));
        $cn = '';
        if (isset($info[0]["$LDAP_GROUP_CN_ATTR"][0]))
        {
            $cn = $info[0]["$LDAP_GROUP_CN_ATTR"][0];
            $errlog = "Le CN est trouvé dans LDAP => cn=" . $cn . ".";
            error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
        }
        return $cn;
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

    public function listejoursteletravailexclus($agentid, $datedebut, $datefin)
    {
        $datedebut = $this->formatdatedb($datedebut);
        $datefin = $this->formatdatedb($datefin);

        $listteletravail = array();
        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT
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
            //error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $errlog"));
            //echo $errlog;
        }
        else
        {
            while ($result = mysqli_fetch_row($query))
            {
                $exception = new ttexception();
                $exception->agentid = $agentid;
                $exception->dateorigine = $result[0];
                $exception->dateremplacement = '';
                $exception->momentremplacement = '';
                if ($result[1] == '') // On a exclu/déplacé la journée entière => On doit découper en 2 moment
                {
                    $exception->momentorigine = fonctions::MOMENT_MATIN;
                    $listteletravail[] = $exception;
                    $exception = new ttexception();
                    $exception->agentid = $agentid;
                    $exception->dateorigine = $result[0];
                    $exception->dateremplacement = '';
                    $exception->momentremplacement = '';
                    $exception->momentorigine = fonctions::MOMENT_APRESMIDI;
                    $listteletravail[] = $exception;
                }
                else
                {
                    $exception->momentorigine = $result[1];
                    $listteletravail[] = $exception;
                }
            }
        }
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

    public function estjourteletravailexclu($agentid, $date, $moment)
    {
        $date = $this->formatdatedb($date);

        $sql = "SELECT AGENTID
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND DATEORIGINE = ?
                  AND ( MOMENTORIGINE = ?
                     OR MOMENTORIGINE = '')";

        $params = array($agentid, $date, $moment);

        $query = $this->prepared_select($sql, $params);
        /*
        var_dump("SQL = $sql ");
        var_dump("agentid = $agentid ");
        var_dump("date = $date ");
        var_dump("moment = $moment ");
         */
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
            return true;
        }
    }

    public function estjourteletravaildeplace($agentid, $date, $moment)
    {
        if ($date . "" != "")
        {
            $date = $this->formatdatedb($date);
        }
        $sql = "SELECT DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT
                FROM TTEXCEPTION
                WHERE AGENTID = ?
                  AND DATEREMPLACEMENT = ?
                  AND ( MOMENTREMPLACEMENT = ?
                     OR MOMENTREMPLACEMENT = '')";

        $params = array($agentid,$date,$moment);

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
            return $exception;
        }
    }

    function ajoutjoursteletravailexclus($agentid, $dateorigine, $momentorigine, $dateremplacement = NULL, $momentremplacement = '')
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

        $sql = 'INSERT INTO TTEXCEPTION(AGENTID, DATEORIGINE, MOMENTORIGINE, DATEREMPLACEMENT, MOMENTREMPLACEMENT) VALUES(?, ?, ?, ?, ?)';
        $params = array($agentid,$dateorigine,$momentorigine,$dateremplacement,$momentremplacement);
        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "Problème SQL dans l'enregistrement de l'exclusion : " . $erreur;
            echo $errlog;
        }
        return $errlog;
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
        $typeimage = pathinfo($path, PATHINFO_EXTENSION);
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

    public function checksignatairecetliste(&$params, $agent)
    {

        $maxniveau = 0;
        $arraysignataire = array();
        $resp = $agent->getsignataire(null,$respstruct,$codeinterne);
        if (!is_null($resp) and ($resp!==false))
        {
            $arraysignataire[$resp->agentid()] = $resp;
        }
        if ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT or $codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT)
        {
            $respsiham = $respstruct->responsablesiham();
            if ($respsiham->mail() . "" != "")
            {
                $arraysignataire[$respsiham->agentid()] = $respsiham;                
            }
        }
        if (isset($arraysignataire[SPECIAL_USER_IDCRONUSER]))
        {
            unset($arraysignataire[SPECIAL_USER_IDCRONUSER]);
        }
        if (count($arraysignataire)==0)
        {
            $taberrorcheckmail['prob_resp'] = "Votre responsable n'est pas renseigné.";
        }
        else
        {
            $params['recipientEmails']["1*" . $agent->ldapmail()] = "1*" . $agent->ldapmail();
            foreach($arraysignataire as $resp)
            {
                $params['recipientEmails']["2*" . $resp->mail()] = "2*" . $resp->mail();
            }

            $constantename = 'CETSIGNATAIRE';
            $signataireliste = '';
            $tabsignataire = array();
            if ($this->testexistdbconstante($constantename))
            {
                $signataireliste = $this->liredbconstante($constantename);
            }
            if (strlen($signataireliste)>0)
            {
                $tabsignataire = $this->signatairetoarray($signataireliste);
                foreach ($tabsignataire as $niveau => $infosignataires)
                {
                    if ($maxniveau<$niveau) $maxniveau = $niveau;

                    foreach ($infosignataires as $idsignataire => $infosignataire)
                    {
                        if ($infosignataire[0]==fonctions::SIGNATAIRE_AGENT or $infosignataire[0]==fonctions::SIGNATAIRE_SPECIAL)
                        {
                            $agentsignataire = new agent($this->dbconnect);
                            if ($agentsignataire->load($infosignataire[1]))
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        elseif ($infosignataire[0]==fonctions::SIGNATAIRE_RESPONSABLE)
                        {
                            $structuresignataire = new structure($this->dbconnect);
                            $structuresignataire->load($infosignataire[1]);
                            $agentsignataire = $structuresignataire->responsable();
                            if ($agentsignataire->civilite()!='') // Si la civilité est vide => On a un problème de chargement du responsable
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        elseif ($infosignataire[0]==fonctions::SIGNATAIRE_STRUCTURE)
                        {
                            $structuresignataire = new structure($this->dbconnect);
                            $structuresignataire->load($infosignataire[1]);
                            $datedujour = date("d/m/Y");
                            foreach ($structuresignataire->agentlist($datedujour, $datedujour,'n') as $agentsignataire)
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        else
                        {
                            echo $this->showmessage(fonctions::MSGERROR,"TYPE DE SIGNATAIRE inconnu !");
                        }
                        unset($agentsignataire);
                    }
                }
            }

            // On passe le tableau en minuscule
            $params['recipientEmails'] = array_map('strtolower', $params['recipientEmails']);
            // On passe les clés en minuscules
            $params['recipientEmails'] = array_change_key_case($params['recipientEmails'], CASE_LOWER);
            // On fusionne le tableau applati avec le tableau d'origine pour récupérer les groupes qui ont été applatis
            $params['recipientEmails'] = array_merge($params['recipientEmails'],$this->explosemail($params['recipientEmails']));
            // On trie le tableau résultat par valeur de clé (donc par niveau)
            ksort($params['recipientEmails']);

            $taberrorcheckmail = array();
            $tabniveauok = array();
            foreach ($params['recipientEmails'] as $recipient)
            {
                $substr = explode('*',$recipient);
                $mailadress = $substr[1];
                $niveau = $substr[0];
                // var_dump("mailadress = $mailadress");
                if (!$this->mailexistedansldap($mailadress))
                {
                    $taberrorcheckmail[$mailadress] = "l'adresse mail $mailadress n'est pas connue de LDAP";
                }
                else
                {
                    $tabniveauok[$niveau] = "On a un agent Ok dans le niveau $niveau";
                }
            }

            // var_dump($tabniveauok);
            // var_dump("count(tabniveauok) = " . count($tabniveauok));
            // var_dump("maxniveau = " . $maxniveau);

            if (count($tabniveauok)!=$maxniveau)
            {
                $taberrorcheckmail['prob_niveau'] = "il y a au moins un niveau de signature qui n'est pas correctement renseigné";
            }
        }
        if (count($taberrorcheckmail)>0)
        {
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
        }
        return $taberrorcheckmail;

    }

    public function checksignataireteletravailliste(&$params, $agent, &$maxniveau)
    {

        $maxniveau = 0;
        $arraysignataire = array();
        $resp = $agent->getsignataire(null,$respstruct,$codeinterne);
        if (!is_null($resp) and ($resp!==false))
        {
            $arraysignataire[$resp->agentid()] = $resp;
        }
        if ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT or $codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT)
        {
            $respsiham = $respstruct->responsablesiham();
            if ($respsiham->mail() . "" != "")
            {
                $arraysignataire[$respsiham->agentid()] = $respsiham;                
            }
        }
        if (isset($arraysignataire[SPECIAL_USER_IDCRONUSER]))
        {
            unset($arraysignataire[SPECIAL_USER_IDCRONUSER]);
        }
        if (count($arraysignataire)==0)
        {
            $taberrorcheckmail['prob_resp'] = "Votre responsable n'est pas renseigné.";
        }
        else
        {
            $params['recipientEmails']["1*" . $agent->ldapmail()] = "1*" . $agent->ldapmail();
            foreach($arraysignataire as $resp)
            {
                $params['recipientEmails']["2*" . $resp->mail()] = "2*" . $resp->mail();
            }

            ////////////////////////////////////////////////////
            // On cherche le responsable n+2 de l'agent
            $arraysignataire_n2 = array();
            $responsable_n2 = $agent->getsignataire_niveau2($respdurespstruct,$codeinterne);
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
            if (count($arraysignataire_n2)==0)
            {
                // On n'a pas trouvé de responsable n+2
                $constantename = 'TELETRAVAILSIGNATAIRE';
            }
            else
            {
                // On n'a pas trouvé de responsable n+2
                $constantename = 'TELETRAVAILSIGNATAIRE_EVOLUE';
            }

            //var_dump($constantename);
            
            $signataireliste = '';
            $tabsignataire = array();
            if ($this->testexistdbconstante($constantename))
            {
                $signataireliste = $this->liredbconstante($constantename);
            }
            if (strlen($signataireliste)>0)
            {
                $tabsignataire = $this->signatairetoarray($signataireliste);
                foreach ($tabsignataire as $niveau => $infosignataires)
                {
                    if ($maxniveau<$niveau) 
                    { 
                        $maxniveau = $niveau; 
                    }

                    foreach ($infosignataires as $idsignataire => $infosignataire)
                    {
                        if ($infosignataire[0]==fonctions::SIGNATAIRE_AGENT or $infosignataire[0]==fonctions::SIGNATAIRE_SPECIAL)
                        {
                            $agentsignataire = new agent($this->dbconnect);
                            if ($agentsignataire->load($infosignataire[1]))
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        elseif ($infosignataire[0]==fonctions::SIGNATAIRE_RESPONSABLE)
                        {
                            $structuresignataire = new structure($this->dbconnect);
                            $structuresignataire->load($infosignataire[1]);
                            $agentsignataire = $structuresignataire->responsable();
                            if ($agentsignataire->civilite()!='') // Si la civilité est vide => On a un problème de chargement du responsable
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        elseif ($infosignataire[0]==fonctions::SIGNATAIRE_STRUCTURE)
                        {
                            $structuresignataire = new structure($this->dbconnect);
                            $structuresignataire->load($infosignataire[1]);
                            $datedujour = date("d/m/Y");
                            foreach ($structuresignataire->agentlist($datedujour, $datedujour,'n') as $agentsignataire)
                            {
                                $params['recipientEmails'][$niveau . "*" . $agentsignataire->mail()] = $niveau . "*" . $agentsignataire->mail();
                            }
                        }
                        elseif ($infosignataire[0]==fonctions::SIGNATAIRE_RESPONSABLE_N2)
                        {
                            ///////////////////////////////////////////////////
                            // Si il y a un responsable de niveau 2 défini
                            if (count($arraysignataire_n2)==0)
                            {
                                //echo "Pas possible de trouver le n+2 de " . $agent->identitecomplete() . "<br><br>";
                            }
                            else
                            {
                                foreach ($arraysignataire_n2 as $responsable_n2)
                                {
                                    //echo "Le responsable n+2 de " . $agent->identitecomplete() . " est " . $topresponsable->identitecomplete() . "<br><br>";
                                    $params['recipientEmails'][$niveau . "*" . $responsable_n2->mail()] = $niveau . "*" . $responsable_n2->mail();
                                }
                            }
                            ////////////////////////////////////////////////////
                        }
                        else
                        {
                            echo $this->showmessage(fonctions::MSGERROR,"TYPE DE SIGNATAIRE inconnu !");
                        }
                        unset($agentsignataire);
                    }
                }
            }
            
            //////////////////////////////////////////////////////////////
            //////////////////////////////////////////////////////////////
            /////  POUR TEST UNIQUEMENT //////////////////////////////////
            
//            echo $this->showmessage(fonctions::MSGERROR,"-- BLOC DE CODE A DESACTIVER -- UNIQUEMENT EN TEST --");
//            
//            $params['recipientEmails'] = array
//            (
//                "1*" . $agent->ldapmail() => "1*" . $agent->ldapmail(),
//                "2*pascal.comte@univ-paris1.fr" => "2*pascal.comte@univ-paris1.fr"
//            );
//            $tempstr = "3*canica.sar@univ-paris1.fr";
//            $params['recipientEmails'][$tempstr] = $tempstr;
//            $tempstr = "4*pascal.comte@univ-paris1.fr";
//            $params['recipientEmails'][$tempstr] = $tempstr;
//            $tempstr = "4*eSignature.test@univ-paris1.fr";
//            $params['recipientEmails'][$tempstr] = $tempstr;
//            $tempstr = "5*pascal.comte@univ-paris1.fr";
//            $params['recipientEmails'][$tempstr] = $tempstr;
            
            //var_dump($params);
            //////////////////////////////////////////////////////////////
            //////////////////////////////////////////////////////////////
                        
            // On passe le tableau en minuscule
            $params['recipientEmails'] = array_map('strtolower', $params['recipientEmails']);
            // On passe les clés en minuscules
            $params['recipientEmails'] = array_change_key_case($params['recipientEmails'], CASE_LOWER);
            // On fusionne le tableau applati avec le tableau d'origine pour récupérer les groupes qui ont été applatis
            $params['recipientEmails'] = array_merge($params['recipientEmails'],$this->explosemail($params['recipientEmails']));
            // On trie le tableau résultat par valeur de clé (donc par niveau)
            ksort($params['recipientEmails']);
            
            //var_dump($params['recipientEmails']);
            
            $taberrorcheckmail = array();
            $tabniveauok = array();
            $levelkeys = array_keys($params['recipientEmails']);
            foreach($levelkeys as $key)
            {
                $substr = explode('*',$key);
                $mailadress = $substr[1];
                $niveau = $substr[0];
                $tabniveauok[$niveau] = "On a un agent dans le niveau $niveau";                
            }

            //var_dump($tabniveauok);
            //var_dump("count(tabniveauok) = " . count($tabniveauok));
            //var_dump("maxniveau = " . $maxniveau);

            if (count($tabniveauok)!=$maxniveau)
            {
                $taberrorcheckmail['prob_niveau'] = "il y a au moins un niveau de signature qui n'est pas correctement renseigné";
            }
        }
        if (count($taberrorcheckmail)>0)
        {
            $taberrorcheckmail['info_contact_drh'] = "Contactez le service de la DRH pour faire vérifier le paramétrage de l'application.";
        }
        return $taberrorcheckmail;

    }
  
    
    // $maillist doit avoir des clé de la forme : niveau*adresse_mail
    // exemple : 5*jonh.doe@etab.fr
    public function explosemail($maillist)
    {
        //var_dump($maillist);
        
        $returnmail = array();
        $tabmailparniveau = array();
        foreach ($maillist as $recipient)
        {
            $substr = explode('*',$recipient);
            $mailadress = $substr[1];
            $niveau = $substr[0];
            
            $tabmailparniveau[$niveau][] = $mailadress;
        }
                    
        foreach($tabmailparniveau as $niveau => $tabmail)
        {
            $paramlist = '';
            foreach($tabmail as $mailadress)
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
            foreach ((array)$response as $agentinfo)
            {
                if (isset($agentinfo["member-all"]))
                {
                    // C'est un groupe qui est explosé => On récupère les mails des membres
                    foreach ($agentinfo["member-all"] as $agentinfo)
                    {
                        if ($this->testexistdbconstante($dbconstante))
                        {
                            $mail = trim($this->liredbconstante($dbconstante));
                            if (strlen($mail)>0)
                            {
                                $agentinfo["mail"] = $mail;
                            }
                        }
                        if (isset($agentinfo["mail"]))
                        {
                            $infoadresse = $niveau . "*" . strtolower($agentinfo["mail"]);
                            $returnmail[$infoadresse] = $infoadresse;
                        }
                        else
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
                    // On ne modifie pas l'adresse du demandeur de la convention de télétravail
                    // même si la constante FORCE_AGENT_MAIL est activée
                    if ($this->testexistdbconstante($dbconstante) and $niveau>1)
                    {
                        $mail = trim($this->liredbconstante($dbconstante));
                        if (strlen($mail)>0)
                        {
                            $agentinfo["mail"] = $mail;
                        }
                    }
                    // C'est un agent => On récupère l'adresse mail
                    if (isset($agentinfo["mail"]))
                    {
                        $infoadresse = $niveau . "*" . strtolower($agentinfo["mail"]);
                        $returnmail[$infoadresse] = $infoadresse;
                    }
                    else
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
            
        }    

        //var_dump($returnmail);
        
        return $returnmail;
    }


    public function listeagentsavecaffectation($namefirst = true)
    {
        $listeagent = array();
        $sql = "SELECT AGENTID,NOM,PRENOM FROM AGENT WHERE TRIM(STRUCTUREID) <> '' ORDER BY NOM,PRENOM,AGENTID";
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
                  AND SOLDE.TYPEABSENCEID='sup" . trim($anneeref) . "'
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


    public function listeagentsg2t($namefirst = true)
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

    public function deleteesignaturedocument($esignatureid)
    {
        $erreur = '';

        if (!preg_match ("/^[0-9]+/", $esignatureid))
        {
            //echo "Pas de chiffres<br>";
            $erreur = "Suppression du document impossible : L'identifiant eSignature n'est pas valide : " . $esignatureid;
            error_log(basename(__FILE__) . " " . $this->stripAccents(" $erreur"));
            return $erreur;
        }
        $eSignature_url = $this->liredbconstante("ESIGNATUREURL"); //"https://esignature-test.univ-paris1.fr";
        $url = $eSignature_url.'/ws/signrequests/'.$esignatureid;
/*
        $params = array('id' => $teletravail->esignatureid());
        $walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
            is_array( $item )
            ? array_walk( $item, $walk, $key )
            : $output[] = http_build_query( array( $parent_key ?: $key => $item ) );

        };
        array_walk( $params, $walk );
        $json = implode( '&', $output );
*/
        $json = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $json = curl_exec($ch);
        $result = json_decode($json);
        error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE SUPPRESSION DOCUMENT -- " . var_export($result, true));
        $error = curl_error ($ch);
        //var_dump($error);
        curl_close($ch);
        if ($error != "")
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Erreur dans la suppression du document : Erreur Curl " . $error;
            error_log(basename(__FILE__) . " " . $this->stripAccents($erreur));
        }
        elseif (!is_null($result))
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Erreur dans la suppression du document : " . var_export($result, true);
            error_log(basename(__FILE__) . " " . $this->stripAccents($erreur));
        }
        elseif (stristr(substr($json,0,20),'HTML') !== false) // On a trouvé HTML dans le json
        {
            if (strlen($erreur)>0) $erreur = $erreur . '<br>';
            $erreur = $erreur . "Erreur dans la suppression du document : " . var_export($json, true);
            error_log(basename(__FILE__) . " " . $this->stripAccents($erreur));
        }

        return $erreur;
    }

    public function listeconventionteletravailavecstatut($statut)
    {
        $tabconvention = array();
        $sql = "SELECT TELETRAVAILID
                FROM TELETRAVAIL
                WHERE STATUT = ? ";

        $params = array($statut);
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

    public function synchroniseconventionteletravail($esignatureid)
    {
        $eSignature_url = $this->liredbconstante("ESIGNATUREURL");

        $status = "";
        $reason = "";
        $datesignatureresponsable = '19000101';
        $sendmailtoresp = false;


        error_log(basename(__FILE__) . $this->stripAccents(" On va modifier le statut de la convention télétravail =>  " . $esignatureid));

        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/signrequests/status/' . $esignatureid,
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
            $erreur_curl = "Erreur dans eSignature (WS g2t) : ".$json;
            error_log(basename(__FILE__) . $this->stripAccents(" $erreur_curl"));
            // $result_json = array('status' => 'Error', 'description' => $erreur);
            $status = teletravail::TELETRAVAIL_ANNULE;
        }
        else
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Réponse du WS signrequests en json"));
            error_log(basename(__FILE__) . " " . var_export($json,true));
            $current_status = str_replace("'", "", $json);  // json_decode($json, true);

            error_log(basename(__FILE__) . $this->stripAccents(" Réponse du WS signrequests/status"));
            error_log(basename(__FILE__) . " " . $current_status); // var_export($current_status,true));

            switch (strtolower($current_status))
            {
                //uploading, draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
                //           draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned, fully-deleted
                case 'draft' :
                case 'pending' :
                case 'signed' :
                case 'checked' :
                    $status = teletravail::TELETRAVAIL_ATTENTE;
                    break;

                case 'refused':
                    $status = teletravail::TELETRAVAIL_REFUSE;
                    error_log(basename(__FILE__) . $this->stripAccents(" Le statut de la demande $esignatureid dans eSignature est '$current_status' => On va chercher le commentaire"));
                    // On interroge le WS eSignature /ws/signrequests/{id}
                    $curl = curl_init();
                    $params_string = "";
                    $opts = [
                        CURLOPT_URL => $eSignature_url . '/ws/signrequests/' . $esignatureid,
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
                        error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl (récup commentaire) =>  " . $error));
                    }
                    $response = json_decode($json, true);
                    if (isset($response['comments']))
                    {
                        $reason = '';
                        foreach ($response['comments'] as $comment)
                        {
                            $reason = $reason . " " . $comment['text'];
                        }
                        $reason = trim($reason);
                    }
                    break;
                case 'completed' :
                case 'exported' :
                case 'archived' :
                case 'cleaned' :
                    $status = teletravail::TELETRAVAIL_VALIDE;
                    break;
                case 'deleted' :
                case 'canceled' :
                case 'fully-deleted' :
                case '' :
                    $status = teletravail::TELETRAVAIL_ANNULE;
                    break;
                default :
                    $erreur = "";
                    $response = json_decode($current_status, true);
                    if (isset($response['error'])) $erreur = $response['error'];
                    $erreur = "Erreur dans la réponse de eSignature => eSignatureid = " . $esignatureid . " erreur => $erreur current_status => $current_status";
                    error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                    $status = "";
                    $result_json = array('status' => 'Error', 'description' => $erreur);

            }
        }
        if ($status <> '')
        {
            //$status = mb_strtolower("$status", 'UTF-8');
            $teletravail = new teletravail($this->dbconnect);
            $erreur = $teletravail->loadbyesignatureid($esignatureid);
            if ($erreur === false)
            {
                $erreur = "Erreur lors de la lecture des infos de la convention télétravail " . $esignatureid;
                error_log(basename(__FILE__) . $this->stripAccents(" " . $erreur));
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                error_log(basename(__FILE__) . $this->stripAccents(" statut de la convention dans eSignature = $status -> " . $this->teletravailstatutlibelle($status)));
                error_log(basename(__FILE__) . $this->stripAccents(" teletravail->statut() = " . $teletravail->statut() . " -> " . $this->teletravailstatutlibelle($teletravail->statut())));

                // Ajout d'un contrôle pour ne pas traiter les changements de statut pour le remplacer par le même
                if ($status == $teletravail->statut() and $reason==$teletravail->commentaire())
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" La convention a déjà un statut $status (" . $this->teletravailstatutlibelle($status) . "). On ne fait rien => Pas d'erreur"));
                    $erreur = '';
                    $result_json = array('status' => 'Ok', 'description' => $erreur);
                }
                else // if (in_array($statut, array(teletravail::TELETRAVAIL_REFUSE, teletravail::TELETRAVAIL_VALIDE))) // Si le statut dans eSignature est REFUSE ou VALIDE
                {
                    
                    // Si le status est VALIDE alors on va mettre la date du dernier signataire comme date de début de la convention
                    if ($status==teletravail::TELETRAVAIL_VALIDE)
                    {
                        $curl = curl_init();
                        $params_string = "";
                        $opts = [
                            CURLOPT_URL => $eSignature_url . '/ws/forms/get-datas/' . $esignatureid,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => $params_string,
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
                            error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl (récup info date dernier signataire) =>  " . $error));
                        }
                        $responsedata = json_decode($json, true);

                        if (isset($responsedata["sign_step_5_date"]))
                        {
                            // On récupère la date de signature du niveau 5
                            $splitdate = explode(" ",$responsedata["sign_step_5_date"]);
                            // On vérifie que le format de la date est ok
                            if (strtotime($splitdate[0])===false)
                            {
                                // Le format n'est pas ok
                                $datesignatureresponsable = '19000101';
                                error_log(basename(__FILE__) . $this->stripAccents(" Impossible de déterminer la date du signataire niveau 5 : Format incorrect => " . $splitdate[0]));
                            }
                            else
                            {
                                // C'est une date
                                $datesignatureresponsable = $splitdate[0];
                            }
                        }
                        else if (isset($responsedata["sign_step_4_date"]))
                        {
                            // On récupère la date de signature du niveau 4
                            $splitdate = explode(" ",$responsedata["sign_step_4_date"]);
                            if (strtotime($splitdate[0])===false)
                            {
                                // Le format n'est pas ok
                                $datesignatureresponsable = '19000101';
                                error_log(basename(__FILE__) . $this->stripAccents(" Impossible de déterminer la date du signataire niveau 4 : Format incorrect => " . $splitdate[0]));
                            }
                            else
                            {
                                // C'est une date
                                $datesignatureresponsable = $splitdate[0];
                            }
                        }
                        else
                        {
                            // On n'a aucune information sur la date de signature
                            $datesignatureresponsable = '19000101';
                            error_log(basename(__FILE__) . $this->stripAccents(" Impossible de déterminer la date du dernier signataire"));
                            error_log(basename(__FILE__) . $this->stripAccents(" La date de signature du dernier niveau est : $datesignatureresponsable"));
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
                        $liste = $agent->teletravailliste($datedebutteletravail, $datefinteletravail);
                        foreach ($liste as $conventionid)
                        {
                            if ($currentconventionid <> $conventionid) // On ignore la convention qu'on vient de traiter
                            {
                                $teletravailmodif = new teletravail($this->dbconnect);
                                $teletravailmodif->load($conventionid);
                                if (in_array($teletravailmodif->statut(),array(teletravail::TELETRAVAIL_VALIDE,teletravail::TELETRAVAIL_ATTENTE)))
                                {
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
                                            $return = "" . $this->deleteesignaturedocument($teletravailmodif->esignatureid());
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
                }
            }
        }
        error_log(basename(__FILE__) . $this->stripAccents(" result_json = " . print_r($result_json,true)));
        return $result_json;
    }

    function creation_ticketGLPI_materiel($esignatureid)
    {
        $constante = 'MAINTENANCE';
        $maintenance = $this->liredbconstante($constante);
        if (strcasecmp($maintenance, 'n') != 0)
        {
            // Si on est en mode maintenance => On ne fait rien
            error_log(basename(__FILE__) . $this->stripAccents(" Création du ticket GLPI => Mode maintenance activé. On ne fait rien."));
            return "";
        }
        
        $eSignature_url = $this->liredbconstante("ESIGNATUREURL");

        $curl = curl_init();
        $params_string = "";
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/forms/get-datas/' . $esignatureid,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $json = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if ($error != "")
        {
            error_log(basename(__FILE__) . $this->stripAccents(" Erreur Curl (récup info matériel télétravail) =>  " . $error));
        }
        $response = json_decode($json, true);
        //var_dump($response);

        //$key_materiel = "form_data_Equipement";
        $dbconstante = 'ESIGNATURE_MATERIEL_KEY';
        $key_materiel = "form_data_Equipement";
        if ($this->testexistdbconstante($dbconstante))
        {
            $key_materiel = $this->liredbconstante($dbconstante);
        }
        /*
            "form_data_EquipementOrdinateur": "on",
            "form_data_EquipementSouris": "off",
            "form_data_EquipementBase": "off",
            "form_data_EquipementSac": "off",
            "form_data_EquipementCasque": "off",
        */
        $tab_materiel = array_intersect_key($response, array_flip(preg_grep("/^$key_materiel/i", array_keys($response), 0)));
        if ($tab_materiel!==false and !is_null($tab_materiel))
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
                $besoin = "";
                $materieldemande = false;
                foreach($tab_materiel as $key => $value)
                {
                    $typemateriel = str_ireplace($key_materiel,'',$key);
                    $besoin = $besoin . "&nbsp;&nbsp;&bull; ";
                    if (strcasecmp($value,'on')==0) // L'agent a demandé => valeur ON
                    {
                        $besoin = $besoin . "J'ai demandé ";
                        $materieldemande = true;
                    }
                    else
                    {
                        $besoin = $besoin . "Je n'ai pas demandé ";
                    }
                    $besoin = $besoin . ": un(e) " . strtolower($typemateriel) . " \n";
                }

                // On construit le destinataire car il n'est pas dans la base
                //$destinataire = new agent($this->dbconnect);
                //$destinataire->nom('GLPI');
                //$destinataire->prenom('COLLECTEUR');
                //$destinataire->mail($mail_glpi);

                //$destinataire = "pascal.comte@univ-paris1.fr";
                $destinataire = $mail_glpi;
                if ($materieldemande==true)
                {
                    $destinataire = $mail_glpi;
                    //echo "Le destinataire : $destinataire <br>";

                    $objet = "Demande de matériel suite à validation de convention télétravail";
                    $corps = "Suite à la validation de ma demande de convention de télétravail numéro " . $teletravail->teletravailid() . ", je vous remercie de bien vouloir prendre note que : \n";
                    $corps = $corps . "\n" . $besoin . "\n Cordialement, \n" . $demandeur->identitecomplete() . " \n";
                    $demandeur->sendmail($destinataire, $objet, $corps);
                    error_log(basename(__FILE__) . $this->stripAccents(" Envoi du mail pour la demande de materiel : " . str_replace(array("\n","&nbsp;","&bull;"), ' ', $besoin) . " => $destinataire"));
                    //echo "Après l'envoie du mail \n";
                }
                else
                {
                    error_log(basename(__FILE__) . $this->stripAccents(" Pas de materiel demande pour la convention " . $teletravail->teletravailid() . " => Pas d'envoi de mail à $destinataire"));
                }
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

    function convert_int_to_on_off($valeur)
    {
        if (trim($valeur)=='1')
        {
            return 'on';
        }
        elseif (trim($valeur)=='0')
        {
            return 'off';
        }
        else
        {
            return "Valeur inconnue - $valeur";
        }
    }

    function affectation_continue($datefinprecedente,$datedebutsuivante,$nbre_jour_periode)
    {
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

//    function ajoute_crlf ($chaine, $lg_max)
//    {
//        //global $fonctions;
//        if (strlen($chaine) > $lg_max)
//        {
//            $chaineresultat = '';
//            while (strlen($chaine) > $lg_max)
//            {
//                $subchaine = substr($chaine, 0, $lg_max);
//                // On cherche le dernier CR (<=>chr(13)) et le dernier espace.
//                $last_space = strrpos($subchaine, " ");
//                $last_retrun = strrpos($subchaine, chr(13));
//                if ($last_space===false and $last_retrun===false)
//                {
//                    // S'il n'y a plus d'espace ou de CR, on ne tronque plus rien
//                    //break;
//                    $last_space = strlen($chaine);  // $lg_max;
//                }
//                elseif ($last_space===false and $last_retrun!==false)
//                {
//                    // S'il y a un CR et pas d'espace, on coupe sur le CR
//                    $last_space = $last_retrun;
//                }
//                elseif ($last_space!==false and $last_retrun!==false)
//                {
//                    // Si on a à la fois un CR et un espace, on prend le plus petit
//                    $last_space = min($last_space,$last_retrun);
//                }
//                $chaineresultat = $chaineresultat . trim(substr($chaine, 0, $last_space));
//                // ATTENTION : Bien faire $last_space+1 afin de "sauter" le caractère de découpe (espace ou CR)
//                $chaine = substr($chaine, $last_space+1);
//                if (strlen($chaineresultat)>0)
//                {
//                    $chaineresultat = $chaineresultat . chr(13);  // chr(13) <=> Carriage return
//                }
//            }
//            $chaine = $chaineresultat . trim($chaine);
//        }
//        return $chaine;
//    }

    function ajoute_crlf ($chaine, $lg_max)
    {
        //global $fonctions;
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
            echo "<center>";
            echo "<div class='periodeobligatoirebloc'><b>RAPPEL : </b>Les périodes de fermeture obligatoire de l'établissement sont les suivantes : <ul>";
            foreach ($liste as $element)
            {
                echo "<li class='leftaligntext' >Du " . $this->formatdate($element["datedebut"]) . " au " . $this->formatdate($element["datefin"]) . "</li>";
            }
            echo "</ul>";
            echo "Veuillez penser à poser vos congés en conséquence.";
            echo "</div></center>";
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
                    if (strcasecmp($racinestruct->respaffsoldesousstruct(), "o") == 0)
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
                    if (strcasecmp($racinestruct->respaffdemandesousstruct(), "o") == 0)
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
            if (strcasecmp($struct->sousstructure(), "o") == 0)
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
    
}

?>