<?php

use Fpdf\Fpdf as FPDF;

class periodeobligatoire
{
    
    private $dbconnect = null;
    
    private $fonctions = null;
    
    private $anneeref = null;
    
    private $listedate = array();
    
    private $pastrouve = false;
    
    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "periodeobligatoire->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }
    
    function load($anneeref)
    {
        if (! isset($this->$anneeref))
        {
            $this->anneeref = $anneeref;
            $constname  = "PERIODE_" . $anneeref;
            if ($this->fonctions->testexistdbconstante($constname))
            {
                $this->pastrouve = false;
                $result = $this->fonctions->liredbconstante($constname);
                $dateliste = explode("|", $result);
                //echo "<br>dateliste dans load avant for = " . print_r($dateliste,true)."<br>";
                foreach ((array)$dateliste as $periode)
                {
                    //echo "<br>periode dans le load = " . print_r($periode,true)."<br>";
                    if (strpos($periode,'-')!==false)
                    {
                        //echo "<br>J'ai trouvé le - dans periode $periode <br>";
                        $dateborne = explode("-", $periode);
                        //echo "<br>Apres le explode....<br>";
                        $this->ajouterperiode(trim($dateborne[0]),trim($dateborne[1]));
                        //$periode = array("datedebut" => $dateborne[0],"datefin" => $dateborne[1]);
                        //$this->listedate[$dateborne[0] . '-' . $dateborne[1]] = $periode;
                    }
                }
            }
            else
            {
                $this->listedate = array();
                $this->pastrouve = true;
            }
            ksort($this->listedate); // On les trie par ordre chronologique
            return $this->listedate;
            
/*            
            $sql = "SELECT VALEUR FROM CONSTANTES WHERE NOM = ?";
            // echo "PeriodeObligatoire load sql = $sql <br>";
            $params = array($constname);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") 
            {
                $errlog = "PeriodeObligatoire->Load : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }

            if (mysqli_num_rows($query) == 0) 
            {
                //echo "<br>load => pas de ligne dans la base de données<br>";
                $this->listedate = array();
                $this->pastrouve = true;
                return $this->listedate;
            }
            $this->pastrouve = false;
            $result = mysqli_fetch_row($query);
            $dateliste = explode("|", $result[0]);
            //echo "<br>dateliste dans load avant for = " . print_r($dateliste,true)."<br>";
            foreach ((array)$dateliste as $periode)
            {
                //echo "<br>periode dans le load = " . print_r($periode,true)."<br>";
                if (strpos($periode,'-')!==false)
                {
                    //echo "<br>J'ai trouvé le - dans periode $periode <br>";
                    $dateborne = explode("-", $periode);
                    //echo "<br>Apres le explode....<br>";
                    $this->ajouterperiode(trim($dateborne[0]),trim($dateborne[1]));
                    //$periode = array("datedebut" => $dateborne[0],"datefin" => $dateborne[1]);
                    //$this->listedate[$dateborne[0] . '-' . $dateborne[1]] = $periode;
                }
            }
            return $this->listedate;
*/            
        }
    }

    function anneeref()
    {
        return $this->anneeref;
    }
    
    function store($anneeref = null)
    {
        if (is_null($anneeref) and is_null($this->anneeref))
        {
            $errlog = "PeriodeObligatoire->Store : Aucune période n'est définie anneeref => null  this->anneeref = null";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return;
        }
        if (!is_null($anneeref) and !is_null($this->anneeref) and $this->anneeref<>$anneeref)
        {
            $errlog = "PeriodeObligatoire->Store : Impossible de sauvegarder une periode sur une année différente anneeref = $anneeref  this->anneeref = " . $this->anneeref;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return;
        }
        $valeur = "";
        //echo "<br>listedate dans le save = " . print_r($this->listedate,true)."<br>";
        foreach ($this->listedate as $periode)
        {
            $valeur = $valeur . $periode["datedebut"] . '-' . $periode["datefin"] . '|';
        }
        // Si on est en train de créer cette période <=> soit on ne l'a pas trouvé lors du chargement précédent
        if (!is_null($this->anneeref))
        {
            $constname  = "PERIODE_" . $this->anneeref;
        }
        else
        {
            $constname  = "PERIODE_" . $anneeref;
            $this->anneeref = $anneeref;
        }
        $erreur = $this->fonctions->enregistredbconstante($constname, $valeur);

        return $erreur;
    }

    function loadperiodefromid($periodeid)
    {
        $periode = array();
        if (strpos($periodeid,'-')!==false)
        {
            //echo "<br>J'ai trouvé le - dans periode $periode <br>";
            list($datedebut, $datefin) = explode("-", $periodeid);
            $periode = array("datedebut" => $datedebut, "datefin" => $datefin, "id" => $datedebut . '-' . $datefin);
        }
        return $periode;
    }
    
    function ajouterperiode($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        //$periode = array("datedebut" => $datedebut, "datefin" => $datefin, "id" => $datedebut . '-' . $datefin);
        $periode = $this->loadperiodefromid($datedebut . '-' . $datefin);

        $this->listedate[$periode["id"]] = $periode;
        ksort($this->listedate);
        //echo "<br>ajouter => listedate = " . print_r($this->listedate,true)."<br>";
    }
    
    function supprimerperiode($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        unset ($this->listedate[$datedebut . '-' . $datefin]);
        //echo "<br>supprimer => listedate = " . print_r($this->listedate,true)."<br>";
    }

    /**
     *
     * @param string $datedebut date de début de la période à tester
     * @param string $datefin date de fin de la période à tester
     * @return null|array null si les dates ne superposent aucune période, sinon la période uperposée
     */
    function testsuperposeperiode($datedebut, $datefin)
    {
        if (count($this->listedate)==0)
        {
            return null;
        }
        foreach($this->listedate as $periode)
        {
            $datedebut = $this->fonctions->formatdatedb($datedebut);
            $datefin = $this->fonctions->formatdatedb($datefin);
            if ($datedebut<=$periode["datefin"] and $datefin>=$periode["datedebut"])
            {
                return $periode;
            }
        }
        return null;
    }
}
