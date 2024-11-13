<?php

use Fpdf\Fpdf as FPDF;

/**
 * DemandeComplement
 * Definition of a demandecomplement
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class demandecomplement
{
    public const DEMANDE_AVIS_STATUT_LABEL = 'AVISSTATUT';
    public const DEMANDE_AVIS_MOTIF_LABEL = 'AVISMOTIF';
    public const PERIODE_OBLIG_AUTOMATIQUE = 'PERIODE_AUTO';

    private $demandeid = null;

    private $complementid = null;

    private $valeur = null;

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
            $errlog = "DemandeComplement->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    /**
     *
     * @param string $demandeid
     *            identifier of the demande
     * @param string $complementid
     *            identifier of the complement
     * @return
     */
    function load($demandeid, $complementid)
    {
        $sql = "SELECT DEMANDEID,COMPLEMENTID,VALEUR FROM DEMANDECOMPLEMENT WHERE DEMANDEID=? AND COMPLEMENTID=?";
        $params = array($demandeid,$complementid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "DemandeComplement->Load : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) != 0) {
            $result = mysqli_fetch_row($query);
            $this->demandeid = "$result[0]";
            $this->complementid = "$result[1]";
            $this->valeur = "$result[2]";
        } else {
            $this->demandeid = "";
            $this->complementid = "";
            $this->valeur = "";
        }
    }

    /**
     *
     * @param
     * @return string Message d'erreur ou texte vide
     */
    function store()
    {
        if (strlen($this->demandeid) == 0 or strlen($this->complementid) == 0) {
            $errlog = "DemandeComplement->Store : Le numéro DEMANDEID (" . $this->demandeid . ") ou le code du complément (" . $this->complementid . ") n'est pas initialisé";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        $this->delete($this->demandeid,$this->complementid);
        
        $sql = "INSERT INTO DEMANDECOMPLEMENT(DEMANDEID,COMPLEMENTID,VALEUR) VALUES(?,?,?)";
        $params = array($this->demandeid,$this->complementid,$this->valeur);
        $query = $this->fonctions->prepared_query($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "DemandeComplement->Store (INSERT) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        return trim($erreur);
    }

    /**
     *
     * @param
     * @return
     */
    function delete($demandeid, $complementid)
    {
        $sql = "DELETE FROM DEMANDECOMPLEMENT WHERE DEMANDEID=? AND COMPLEMENTID=?";
        $params = array($demandeid,$complementid);
        $query = $this->fonctions->prepared_query($sql, $params);

        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "DemandeComplement->delete : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        return $erreur;
    }
    
    
    /**
     *
     * @param string $demandeid
     *            identifier of the demande
     * @return string the identifier of the demande if $demandeid is not set
     */
    function demandeid($demandeid = null)
    {
        if (is_null($demandeid)) {
            if (is_null($this->demandeid)) {
                $errlog = "DemandeComplement->demandeid : L'Id de la demande n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->demandeid;
            }
        } 
        else
        {
            $this->demandeid = $demandeid;
        }
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
                $errlog = "DemandeComplement->complementid : L'Id du complément n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->complementid;
            }
        } 
        else
        {
            $this->complementid = $complementid;
        }
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
                $errlog = "DemandeComplement->valeur : La valeur du complément n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->valeur;
            }
        } 
        else
        {
            $this->valeur = $valeur;
        }
    }
}

?>