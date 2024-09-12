<?php

use Fpdf\Fpdf as FPDF;

/**
 * Récupération
 * Definition de l'objet recuperation
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class recuperation
{
    const RECUP_ID = 'recup';
    const SUPP_ID = 'sup';
    const COMPLEMENT_RECUP = "RECUP_";

    private $agentid = null;
    private $dbconnect = null;
    private $droitacquis = null;
    private $jrspris = null;
    private $dateref = null;
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
            $errlog = "Recuperation->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function agentid($agentid = null)
    {
        if (is_null($agentid)) 
        {
            if (is_null($this->agentid)) 
            {
                $errlog = "Recuperation->agentid : L'Id de l'agent n'est pas défini !!! ";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->agentid;
            }
        } 
        else
        {
            echo "ATTENTION : En théorie on ne peut pas forcer l'id agent => Il est fixé dans le load <br>";
            $this->agentid = $agentid;
        }
    }

    function droitacquis($droitacquis = null)
    {
        if (is_null($droitacquis)) 
        {
            if (is_null($this->droitacquis)) 
            {
                $errlog = "Recuperation->droitacquis : Le droit aquis de l'agent n'est pas défini !!! ";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->droitacquis;
            }
        } 
        else
        {
            echo "ATTENTION : En théorie on ne peut pas forcer les droitsacquis => Il est fixé dans le load  <br>";
            $this->droitacquis = $droitacquis;
        }
    }


    function jrspris($jrspris = null)
    {
        if (is_null($jrspris)) 
        {
            if (is_null($this->jrspris)) 
            {
                $errlog = "Recuperation->jrspris : Le nombre de jours pris de l'agent n'est pas défini !!! ";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->jrspris;
            }
        } 
        else
        {
            echo "ATTENTION : En théorie on ne peut pas forcer les jrspris => Il est fixé dans le load  <br>";
            $this->jrspris = $jrspris;
        }
    }

    /**
     *
     * @param string $agentid
     *            identifiant de l'agent
     * @param string $date
     *            date de référence pour calculer le nombre de jours disponible
     * @return string chaine vide si le chargement est ok. Le message d'erreur sinon
     */
    function load($agentid, $dateref) :string
    {
        $msgerreur = "";
        if (is_null($agentid))
        {
            return "Recuperation->load : Le code de l'agent est NULL <br>";
        }
        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        $this->agentid = $agentid;
        $this->dateref = $dateref;

        $dbconstante = 'VALIDRECUP';
        $validrecup = '2';
        if ($this->fonctions->testexistdbconstante($dbconstante)) { $validrecup = $this->fonctions->liredbconstante($dbconstante); }

        $sql = "SELECT SUM(COMMENTAIRECONGE.NBRJRSAJOUTE), SUM(COMMENTAIRECONGE.NBJRSPRIS) 
                FROM COMMENTAIRECONGE 
                WHERE COMMENTAIRECONGE.AGENTID = ? 
                  AND COMMENTAIRECONGE.TYPEABSENCEID = ? 
                  AND ADDDATE(COMMENTAIRECONGE.DATEAJOUTCONGE, INTERVAL $validrecup MONTH) >= ?
                  AND COMMENTAIRECONGE.COMMENTAIRECONGEID NOT IN ( 
                      SELECT REPLACE(COMPLEMENT.COMPLEMENTID,'" . complement::AVISRH_CONGES_SUP_LABEL  . "','')
                      FROM COMPLEMENT
                      WHERE COMPLEMENT.AGENTID = COMMENTAIRECONGE.AGENTID
                        AND COMPLEMENT.COMPLEMENTID LIKE '" . complement::AVISRH_CONGES_SUP_LABEL  . "%'
                  )";
        $params = array($agentid, recuperation::RECUP_ID, $this->fonctions->formatdatedb($dateref));
        
/*         $anneeref = $this->fonctions->anneeref($dateref);
        $datefinperiode = ($anneeref+1) . $this->fonctions->finperiode();
        $sql = "SELECT SUM(COMMENTAIRECONGE.NBRJRSAJOUTE), SUM(COMMENTAIRECONGE.NBJRSPRIS) 
                FROM COMMENTAIRECONGE 
                WHERE COMMENTAIRECONGE.AGENTID = ? 
                  AND COMMENTAIRECONGE.TYPEABSENCEID = ? 
                  AND ? <= LEAST(ADDDATE(COMMENTAIRECONGE.DATEAJOUTCONGE, INTERVAL $validrecup MONTH),STR_TO_DATE(?,'%Y%m%d'))
                  AND COMMENTAIRECONGE.COMMENTAIRECONGEID NOT IN ( 
                      SELECT REPLACE(COMPLEMENT.COMPLEMENTID,'" . complement::AVISRH_CONGES_SUP_LABEL  . "','')
                      FROM COMPLEMENT
                      WHERE COMPLEMENT.AGENTID = COMMENTAIRECONGE.AGENTID
                        AND COMPLEMENT.COMPLEMENTID LIKE '" . complement::AVISRH_CONGES_SUP_LABEL  . "%'
                  )";
        $params = array($agentid, recuperation::RECUP_ID, $this->fonctions->formatdatedb($dateref), $datefinperiode);
 */
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") 
        {
            $msgerreur = "Recuperation->Load : " . $erreur;
            echo $msgerreur . "<br/>";
            var_dump($msgerreur);
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($msgerreur));
        }
        // Attention : La requête est un SUM => ça retourne forcément une ligne 
        if (mysqli_num_rows($query) == 0) 
        {
            $errlog = "Aucune récupération pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pu être trouvé";
            $msgerreur = $msgerreur . $errlog . "<br/>";
            $this->droitacquis = 0;
            $this->jrspris = 0;
        }
        else
        {
            $result = mysqli_fetch_row($query); 
            // On ajoute un '0' devant la valeur pour convertir le NULL en valeur (cas où aucune ligne n'est trouvée)
            $this->droitacquis = (float) ("0" . $result[0]);
            $this->jrspris = (float) ("0" . $result[1]);
        }
        return $msgerreur;

    }

    function getsolde() : solde
    {
        $solde = new solde($this->dbconnect);
        $solde->droitaquis($this->droitacquis());
        $solde->droitpris($this->jrspris());
        $solde->typeabsenceid(recuperation::RECUP_ID);
        return $solde;
    }

}