<?php

use Fpdf\Fpdf as FPDF;

/**
 * Complement
 * Definition of a complement
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class complement
{
    public const TT_EXCLU_LABEL = 'TT_EXCLU_';

    private $agentid = null;

    private $complementid = null;

    private $valeur = null;

    private $statut = null;

    private $datedebut = null;

    private $datefin = null;

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
            $errlog = "Complement->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    /**
     *
     * @param string $agentid
     *            identifier of the agent
     * @param string $complementid
     *            identifier of the complement
     * @return
     */
    function load($agentid, $complementid)
    {
        $sql = "SELECT AGENTID,COMPLEMENTID,VALEUR FROM COMPLEMENT WHERE AGENTID=? AND COMPLEMENTID=?";
        $params = array($agentid,$complementid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Complement->Load : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) != 0) {
            $result = mysqli_fetch_row($query);
            $this->agentid = "$result[0]";
            $this->complementid = "$result[1]";
            $this->valeur = "$result[2]";
        } else {
            $this->agentid = "";
            $this->complementid = "";
            $this->valeur = "";
            // echo "CET->Load : CET pour agent $agentid et complement $complementid non trouvé <br>";
        }
    }

    /**
     *
     * @param
     * @return
     */
    function store()
    {
        if (strlen($this->agentid) == 0 or strlen($this->complementid) == 0) {
            $errlog = "Complement->Store : Le numéro AGENTID (" . $this->agentid . ") ou le code du complément (" . $this->complementid . ") n'est pas initialisé";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return;
        }
        $sql = "DELETE FROM COMPLEMENT WHERE AGENTID=? AND COMPLEMENTID=?";
        $params = array($this->agentid,$this->complementid);
        $query = $this->fonctions->prepared_query($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Complement->Store (DELETE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $sql = "INSERT INTO COMPLEMENT(AGENTID,COMPLEMENTID,VALEUR) VALUES(?,?,?)";
        $params = array($this->agentid,$this->complementid,$this->valeur);
        $query = $this->fonctions->prepared_query($sql, $params);
        // echo "SQL Complement->Store : $sql <br>";

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Complement->Store (INSERT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        return $erreur;
    }

    /**
     *
     * @param string $agentid
     *            identifier of the agent
     * @return string the identifier of the agent if $agentid is not set
     */
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->agentid)) {
                $errlog = "Complement->agentid : L'Id de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->agentid;
        } else
            $this->agentid = $agentid;
    }

    /**
     *
     * @param string $complementid
     *            identifier of the complement
     * @return string the identifier of the complement if $complementid is not set
     */
    function complementid($complementid = null)
    {
        if (is_null($complementid)) {
            if (is_null($this->complementid)) {
                $errlog = "Complement->complementid : L'Id du complément n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->complementid;
        } else
            $this->complementid = $complementid;
    }

    /**
     *
     * @param string $valeur
     *            value of the complement
     * @return string the value of the complement if $valeur is not set
     */
    function valeur($valeur = null)
    {
        if (is_null($valeur)) {
            if (is_null($this->valeur)) {
                $errlog = "Complement->valeur : La valeur du complément n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur;
        } else
            $this->valeur = $valeur;
    }
}

?>