<?php

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
        if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
            setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
        $monthname = strftime("%B", strtotime($this->formatdatedb($date)));
        if (mb_detect_encoding(ucfirst($monthname), 'UTF-8', true)) {
            return ucfirst($monthname);
        } else {
            return utf8_encode(ucfirst($monthname));
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
        if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
            setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
        $dayname = strftime("%A", strtotime($this->formatdatedb($date)));
        if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) {
            return ucfirst($dayname);
        } else {
            return utf8_encode(ucfirst($dayname));
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
            if (setlocale(LC_TIME, 'fr_FR.UTF8') == '')
                setlocale(LC_TIME, 'FRA.UTF8', 'fra'); // correction problème pour windows
                                                      // Le 01/01/2012 est un dimanche
            $dayname = strftime("%A", strtotime("20120101" + $index));
            
            if (mb_detect_encoding(ucfirst($dayname), 'UTF-8', true)) {
                return ucfirst($dayname);
            } else {
                return utf8_encode(ucfirst($dayname));
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
    }

    /**
     *
     * @param
     * @return string the end of the period in format MMDD (typicaly = 0831 - 31 aug)
     */
    public function finperiode()
    {
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
        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
        $params = array($typeconge);
        $query = $this->prepared_select($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->anneeref : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Fonctions->anneeref : Le type '" . $typeconge . "' n'est pas défini dans la base.";
            echo $errlog . "<br/>";
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
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = ?";
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
        } else {
            $result = mysqli_fetch_row($query);
            return $result[0];
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
    public function legendehtml($anneeref, $includeteletravail = FALSE)
    {
        $tablegende = $this->legende($anneeref,$includeteletravail);
        $htmltext = "";
        $htmltext = $htmltext . "<table>";
        $htmltext = $htmltext . "<tr>";
        $index=0;
        foreach ($tablegende as $key => $legende) 
        {
            if (($index % 5) == 0)
            {
                $htmltext = $htmltext . "</tr><tr>";
            }
            $htmltext = $htmltext . "<td style='cursor:pointer; border-left:1px solid black;border-top:1px solid black;border-right:1px solid black; border-bottom:1px solid black;'  bgcolor=" . $legende["couleur"] . ">&nbsp;&nbsp;&nbsp;</td><td>&nbsp;</td><td align=left>" . $legende["libelle"] . "</td>";
            $index++;
        }
        $htmltext = $htmltext . "</tr>";
        $htmltext = $htmltext . "</table>";
        
        return $htmltext;
    }

    /**
     *
     * @param object $pd :  the pdf file
     *  anneeref : Année de référence de la légende
     * @return
     */
    public function legendepdf($pdf, $anneeref, $includeteletravail = FALSE)
    {
        $tablegende = $this->legende($anneeref,$includeteletravail);
        $long_chps = 0;
        foreach ($tablegende as $key => $legende) {
            if ($pdf->GetStringWidth($legende["libelle"]) > $long_chps)
                $long_chps = $pdf->GetStringWidth($legende["libelle"]);
        }
        $long_chps = $long_chps + 6;
        $index=0;
        foreach ($tablegende as $key => $legende) 
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
            $pdf->Cell(4, 5, utf8_decode(""), 1, 0, 'C', 1);
            $pdf->Cell($long_chps, 4, utf8_decode($legende["libelle"]), 0, 0, 'L');
            $index++;
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
            case "m":
                return "matin";
                break;
            case "a":
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
        return mysqli_real_escape_string($this->dbconnect, utf8_encode($texte));
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
        // $sql = "SELECT VALEUR,STATUT,DATEDEBUT,DATEFIN FROM COMPLEMENT WHERE AGENTID='%s' AND COMPLEMENTID IN (";
        if (is_null($typeprofil)) {
            $sql = $sql . "'RHCET', 'RHCONGE', 'RHANOMALIE'";
        } elseif ($typeprofil == 1) {
            $sql = $sql . "'RHCET'";
        } elseif ($typeprofil == 2) {
            $sql = $sql . "'RHCONGE'";
        } elseif ($typeprofil == 3) {
            $sql = $sql . "'RHANOMALIE'";
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
            if ($agentrh->load("$result[0]")) {
                $agentarray[$agentrh->agentid()] = $agentrh;
            }
            unset($agentrh);
        }
        return $agentarray;
    }

    /**
     *
     * @param string $structid
     *            Code de la structure à convertir
     * @return string Code de la structure correspondante.
     */
    public function labo2ufr($structid)
    {
        $sql = "SELECT LABORATOIREID,UFRID FROM LABO_UFR WHERE LABORATOIREID = ?";
        $params = array($structid);
        $query = $this->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "labo2ufr : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            return $structid;
        }
        if (mysqli_num_rows($query) == 0) {
            return $structid;
        }
        $result = mysqli_fetch_row($query);
        $querryresult = $result[1];
        return $querryresult;
    }


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
    }
    
    /**
     *
     * @param string YYYYMMDD the beginning date to set
     * @return string the end of the cet alimentation period in format YYYYMMDD 
     */
    public function finalimcet($date=NULL)
    {
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
    }

    /**
     *
     * @param string YYYYMMDD the beginning date to set
     * @return string the beginning of the cet option period in format YYYYMMDD
     */
    public function debutoptioncet($date=NULL)
    {
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
    }
    
    /**
     *
     * @param string YYYYMMDD the end date to set
     * @return string the end of the cet option period in format YYYYMMDD
     */
    public function finoptioncet($date=NULL)
    {
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
    }
    
    
    public function getidmodelalimcet()
    {
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
            echo "Erreur Curl = " . $error . "<br><br>";
            error_log(basename(__FILE__) . $this->stripAccents(" Impossible de synchroniser G2T avec eSignature (id eSignature = $id, URL WS G2T = $full_g2t_ws_url) => Erreur : " . $error ));
            return "Pas de réponse du webservice G2T.";
        }
        //echo "<br>" . print_r($json,true) . "<br>";
        $response = json_decode($json, true);
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
    
    public function get_g2t_ws_url()
    {
        if (defined('G2T_WS_URL')) /* A partir de la version 6 de G2T, la constante est forcément déclarée ==> Donc on devrait passer systématiquement ici */
        {
            $g2t_ws_url = G2T_WS_URL;
            error_log(basename(__FILE__) . $this->stripAccents(" L'URL de base des WS G2T est récupérée de la constante => $g2t_ws_url" ));
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
    
    public function get_alimCET_liste($typeconges, $listStatuts = array()) // $typeconges de la forme annYY
    {
        $full_g2t_ws_url = $this->get_g2t_ws_url() . "/alimentationWS.php";
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
            $this->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
        }
        return $alimCETliste;
    }

    public function get_optionCET_liste($anneeref, $listStatuts = array()) 
    {
        $full_g2t_ws_url = $this->get_g2t_ws_url() . "/optionWS.php";
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
            $this->synchro_g2t_eSignature($full_g2t_ws_url,$result[0]);
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
        //return $basepath . '/html/images/';
        return $basepath . '/images/';
    }
    
    public function pdfpath()
    {
        $basepath = $this->g2tbasepath();
        //return $basepath . '/html/pdf/';
        return $basepath . '/pdf/';
    }
    
    public function documentpath()
    {
        $basepath = $this->g2tbasepath();
        //return $basepath . '/html/documents/';
        return $basepath . '/documents/';
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
    
    public function listeagentteletravail($datedebut,$datefin)
    {
        $datedebut = $this->formatdatedb($datedebut);
        $datefin = $this->formatdatedb($datefin);
        
        $listeagentteletravail = array();
        $sql = "SELECT DISTINCT AGENTID
                FROM TELETRAVAIL
                WHERE STATUT = '" . teletravail::STATUT_ACTIVE  . "'
                  AND ((DATEDEBUT <= ? AND DATEFIN >= ? )
                    OR (DATEFIN >= ? AND DATEDEBUT <= ? )
                    OR (DATEDEBUT >= ? AND DATEFIN <= ? ))";
        
        //echo "<br>SQL = $sql <br>";
        $params = array($datedebut,$datedebut,$datefin,$datefin,$datedebut,$datefin);
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
        if (! $user->load($info[0]["$LDAP_CODE_AGENT_ATTR"][0]))
        {
            $errlog = "useridfromCAS : L'agent $CASuid (id = " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " ) n'est pas dans la base de données.";
            // error_log(basename(__FILE__) . $this->stripAccents(" $errlog"));
            return false;
        }
        // error_log(basename(__FILE__) . $this->stripAccents(" L'agentid correspondant à $CASuid est " . $user->agentid()));
        return $user->agentid();
    }
    
    public function prepared_query($sql, $params, $types = "")
    {
        $stmt = $this->dbconnect->prepare($sql);
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
    
    public function time_elapsed($text = "Durée", $numcpt = 0, $reset = false)
    {
        static $last = array();
        $chiffresignificatif = 5;
        
        if (!isset($last[$numcpt]))
        {
            $last[$numcpt] = null;
            $reset = false;
        }
            
        if ($reset)
        {
            $last[$numcpt] = null;
        }
        
        $now = microtime(true);
        
        if ($last[$numcpt] != null) 
        {
            echo "$text : " .  number_format($now - $last[$numcpt],$chiffresignificatif, '.', '') . " secondes (cpt $numcpt) <br>";
        }
        else
        {
            echo "$text : init -> compteur $numcpt <br>";
        }
        
        $last[$numcpt] = $now;
    }
    
    public function estjourteletravailexclu($agentid, $date)
    {
        $date = $this->formatdatedb($date);
        
        $sql = "SELECT VALEUR
                FROM COMPLEMENT
                WHERE AGENTID = ?
                  AND COMPLEMENTID = 'TT_EXCLU_" . $date . "'
                  AND VALEUR = ?";
        
        $params = array($agentid,$date);
        $query = $this->prepared_select($sql, $params);
        //echo "<br>SQL = $sql <br>";
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "estjourteletravailexclu => Problème SQL dans le chargement des complement TT_EXCLU : " . $erreur;
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
    
    public function typeabsencelistecomplete()
    {
        $sql = "SELECT LIBELLE,COULEUR,TYPEABSENCEID FROM TYPEABSENCE";
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
            // $code_legende = "$result[2]";
            $tableabsence["$result[2]"] = array(
                "libelle" => $libelle,
                "couleur" => $couleur
            );
        }
        
        // print_r($tablegende); echo "<br>";
        return $tableabsence;
        
    }
    
}

?>