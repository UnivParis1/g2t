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
        $query = mysqli_query($this->dbconnect, $sql);
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
            $sql = "SELECT TA.TYPEABSENCEID,TA.LIBELLE FROM TYPEABSENCE TA, TYPEABSENCE TA2 WHERE TA2.ABSENCEIDPARENT='abs' AND TA.ABSENCEIDPARENT=TA2.TYPEABSENCEID ORDER BY TA.ABSENCEIDPARENT";
        else
            $sql = "SELECT TYPEABSENCEID,LIBELLE FROM TYPEABSENCE WHERE ABSENCEIDPARENT='" . $categorie . "' ORDER BY LIBELLE";
        
        $query = mysqli_query($this->dbconnect, $sql);
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
        $query = mysqli_query($this->dbconnect, $sql);
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
        $query = mysqli_query($this->dbconnect, $sql);
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
        $query = mysqli_query($this->dbconnect, $sql);
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
        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = '" . $typeconge . "'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->estunconge : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
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
        $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = '" . $typeconge . "'";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonctions->anneeref : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
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
        $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = '" . $constante . "'";
        $query = mysqli_query($this->dbconnect, $sql);
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
     * @return list of caption
     */
    public function legende($anneeref)
    {
/*
        $sql = "SELECT DISTINCT LIBELLE,COULEUR FROM TYPEABSENCE
 				WHERE (ANNEEREF=" . $this->anneeref() . " OR ANNEEREF=" . ($this->anneeref() - 1) . ")
 				   OR ANNEEREF IS NULL
 				ORDER BY LIBELLE";
*/
        $sql = "SELECT DISTINCT LIBELLE,COULEUR FROM TYPEABSENCE
 				WHERE (ANNEEREF=" . $anneeref . " OR ANNEEREF=" . ($anneeref - 1) . ")
 				   OR ANNEEREF IS NULL
 				ORDER BY LIBELLE";
        // echo "sql = " . $sql . " <br>";
        
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Fonction->legende : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
        }
        while ($result = mysqli_fetch_row($query)) {
            $libelle = "$result[0]";
            $couleur = "$result[1]";
            // $code_legende = "$result[2]";
            $tablegende[] = array(
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
     * @return html text representing the list of caption
     */
    public function legendehtml($anneeref)
    {
        $tablegende = $this->legende($anneeref);
        $htmltext = "";
        $htmltext = $htmltext . "<table>";
        $htmltext = $htmltext . "<tr>";
        foreach ($tablegende as $key => $legende) {
            if (($key % 5) == 0)
                $htmltext = $htmltext . "</tr><tr>";
            $htmltext = $htmltext . "<td style='cursor:pointer; border-left:1px solid black;border-top:1px solid black;border-right:1px solid black; border-bottom:1px solid black;'  bgcolor=" . $legende["couleur"] . ">&nbsp;&nbsp;&nbsp;</td><td>&nbsp;</td><td align=left>" . $legende["libelle"] . "</td>";
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
    public function legendepdf($pdf, $anneeref)
    {
        $tablegende = $this->legende($anneeref);
        $long_chps = 0;
        foreach ($tablegende as $key => $legende) {
            if ($pdf->GetStringWidth($legende["libelle"]) > $long_chps)
                $long_chps = $pdf->GetStringWidth($legende["libelle"]);
        }
        $long_chps = $long_chps + 6;
        
        foreach ($tablegende as $key => $legende) {
            if (($key % 5) == 0)
                $pdf->Ln(10);
            
            // $LL_TYPE_CONGE = "$result[LL_TYPE_CONGE]";
            list ($col_leg1, $col_leg2, $col_leg3) = $this->html2rgb($legende["couleur"]);
            
            // $long_chps=strlen($legende["type_conge"])+10;
            // $long_chps=$pdf->GetStringWidth($legende["type_conge"])+6;
            $pdf->SetFillColor($col_leg1, $col_leg2, $col_leg3);
            $pdf->Cell(4, 5, utf8_decode(""), 1, 0, 'C', 1);
            $pdf->Cell($long_chps, 4, utf8_decode($legende["libelle"]), 0, 0, 'L');
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
     * @return the moment name if correct / error message otherwise
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
     * @return the oui/non label if correct / error message otherwise
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
        if (strcasecmp($statut, 'v') == 0)
            return "Validée";
        elseif (strcmp($statut, 'r') == 0)
            return "Refusée";
        elseif (strcmp($statut, 'R') == 0)
            return "Annulée";
        elseif (strcasecmp($statut, 'a') == 0)
            return "En attente";
        else
            echo "Demandestatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
    }

    /**
     *
     * @param string $statut
     *            status code (v,r,a) for part time
     * @return the status label of the part time if correct / display error message otherwise
     */
    public function declarationTPstatutlibelle($statut = null)
    {
        if (strcasecmp($statut, 'v') == 0)
            return "Validée";
        elseif (strcasecmp($statut, 'r') == 0)
            return "Refusée";
        elseif (strcasecmp($statut, 'a') == 0)
            return "En attente";
        else
            echo "declarationTPstatutlibelle : le statut n'est pas connu [statut = $statut] !!! <br>";
    }

    /**
     *
     * @param string $texte
     * @return the string without accents
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
     * @return the string escaped and utf8-encoded
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
        $sql = "SELECT HARPEGEID FROM COMPLEMENT WHERE COMPLEMENTID IN (";
        // $sql = "SELECT VALEUR,STATUT,DATEDEBUT,DATEFIN FROM COMPLEMENT WHERE HARPEGEID='%s' AND COMPLEMENTID IN (";
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
        $sql = $sql . ")";
        // echo "sql = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
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
                $agentarray[$agentrh->harpegeid()] = $agentrh;
            }
            unset($agentrh);
        }
        return $agentarray;
    }

    /**
     *
     * @param string $structid
     *            Code de la structure à convertir
     * @return Code de la structure correspondante.
     */
    public function labo2ufr($structid)
    {
        $sql = "SELECT LABORATOIREID,UFRID FROM LABO_UFR WHERE LABORATOIREID = '" . $structid . "'";
        $query = mysqli_query($this->dbconnect, $sql);
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
        $sql = "SELECT DISTINCT DEMANDE.DEMANDEID,AGENT.HARPEGEID
    				FROM DEMANDE,DEMANDEDECLARATIONTP,DECLARATIONTP,AFFECTATION,AGENT
    				WHERE AFFECTATION.AFFECTATIONID = DECLARATIONTP.AFFECTATIONID
    				  AND DECLARATIONTP.DECLARATIONID = DEMANDEDECLARATIONTP.DECLARATIONID
    				  AND DEMANDEDECLARATIONTP.DEMANDEID = DEMANDE.DEMANDEID
    				  AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID
    				  AND DEMANDE.TYPEABSENCEID = 'cet'
    				  AND (DEMANDE.DATEDEBUT >= '" . $this->formatdatedb($datedebut) . "'
    				    OR DEMANDE.DATESTATUT >= '" . $this->formatdatedb($datedebut) . "' )
    			    ORDER BY AGENT.HARPEGEID, DEMANDE.DATEDEBUT,DEMANDE.DATESTATUT";
        $query = mysql_query($sql, $this->dbconnect);
        $erreur_requete = mysql_error();
        if ($erreur_requete != "")
            error_log(basename(__FILE__) . " " . $erreur_requete);
        $demandeliste = array();
        // Si pas de demande de CET, on retourne le tableau vide
        if (mysql_num_rows($query) == 0) {
            return $demandeliste;
        }
        while ($result = mysql_fetch_row($query)) {
            $demandeid = $result[0];
            $demande = new demande($this->dbconnect);
            $demande->load($demandeid);
            
            $complement = new complement($this->dbconnect);
            $complement->load($result[1], 'DEM_CET_' . $demandeid);
            
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

}
?>