<?php

/*
 * 
CREATE TABLE `TELETRAVAIL` (
  `TELETRAVAILID` INT(11) NOT NULL AUTO_INCREMENT,
  `HARPEGEID` VARCHAR(10) NOT NULL,
  `DATEDEBUT` DATE NOT NULL,
  `DATEFIN` DATE NOT NULL,
  `TABTELETRAVAIL` VARCHAR(14) NOT NULL,
  `STATUT` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`TELETRAVAILID`));
 * 
 */

class teletravail
{
    public const STATUT_ACTIVE = "Active";
    public const STATUT_INACTIVE = "Inactive";
    
    private $teletravailid = null;
    private $harpegeid = null;
    private $datedebut = null;
    private $datefin = null;
    private $tabteletravail = null;
    private $statut = null;
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
        $sql = "SELECT TELETRAVAILID, HARPEGEID, DATEDEBUT, DATEFIN, TABTELETRAVAIL, STATUT 
                FROM TELETRAVAIL
                WHERE TELETRAVAILID = '" . $teletravailid  . "' ";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Teletravail->Load (TELETRAVAIL) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Teletravail->Load (TELETRAVAIL) : Teletravail $teletravailid non trouvé";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return false;
        }
        $result = mysqli_fetch_row($query);
        $this->teletravailid = "$result[0]";
        $this->harpegeid = "$result[1]";
        $this->datedebut = "$result[2]";
        $this->datefin = "$result[3]";
        $this->tabteletravail = "$result[4]";
        $this->statut = "$result[5]";
        return true;
        
    }

    function teletravailid()
    {
        return $this->teletravailid;
    }
    
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->harpegeid)) {
                $errlog = "teletravail->agentid : Le numéro agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->harpegeid;
        }
        else
        {
            if (!is_null($this->harpegeid))
            {
                $errlog = "teletravail->agentid : Impossible de modifier le numéro de l'agent !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->harpegeid = $agentid;
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
    
    function store()
    {
        $erreur = '';
        // Si on est en train de créer un teletravail
        if (is_null($this->teletravailid))
        {
            // On doit vérifier que les éléments olbigatoires sont bien renseignés : HarpegeId, tabteletravail, datefin, datedebut
            if (is_null($this->harpegeid)
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
                $this->statut = teletravail::STATUT_ACTIVE;
            
            $sql = "LOCK TABLES TELETRAVAIL WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO TELETRAVAIL(HARPEGEID,DATEDEBUT,DATEFIN,TABTELETRAVAIL,STATUT)
                    VALUES('" . $this->harpegeid  ."',
                           '" . $this->fonctions->formatdatedb($this->datedebut)  ."',
                           '" . $this->fonctions->formatdatedb($this->datefin)  ."',
                           '" . $this->tabteletravail  ."',
                           '" . $this->statut . "')";
            //echo "SQL teletravail->Store (NEW) : $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
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
                    SET DATEDEBUT = '" . $this->fonctions->formatdatedb($this->datedebut) . "',
                        DATEFIN = '" . $this->fonctions->formatdatedb($this->datefin) . "',
                        TABTELETRAVAIL = '" . $this->tabteletravail . "',
                        STATUT = '" . $this->statut . "'
                    WHERE TELETRAVAILID = '" . $this->teletravailid . "'";
            //echo "SQL teletravail->Store (UPDATE) : $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "teletravail->Store (UPDATE) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        return $erreur;
    }
    
    function estteletravaille($date, $moment = null)
    {
        if ($this->statut != teletravail::STATUT_ACTIVE) // Si la convention de teletravail n'est plus active ==> Forcément ce n'est pas télétravaillé
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
            $numerojour = date("w", strtotime("$date"));
            if ($numerojour == 0)   // Lundi = 1, Mardi = 2, ..... Dimanche = 0
                $numerojour = 6;
            else
                $numerojour = $numerojour - 1;
            // $numerojour => Représente maintenant le numéro de l'index du jour dans le tableau
            if (is_null($moment))
            {
                return ($this->tabteletravail[($numerojour*2)] + $this->tabteletravail[(($numerojour*2)+1)] == 2);
            }
            elseif ($moment == 'm')
            {
                return ($this->tabteletravail[($numerojour*2)] == 1);
            }
            elseif ($moment == 'a')
            {
                return ($this->tabteletravail[(($numerojour*2)+1)] == 1);
            }
            else
            {
                $errlog = "teletravail->estteletravaille : Le moment n'est pas connu (moment = $moment) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }
    
    
}

?>