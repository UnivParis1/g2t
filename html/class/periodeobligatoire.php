<?php

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
        }
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
        $constname  = "PERIODE_" . $anneeref;
        if ($this->pastrouve)
        {
            //echo "PeriodeObligatoire->Store : Pas trouve <br>";
            $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES(?,?)";
            $params = array($constname,$valeur);    
        }
        // Sinon si l'anneeref interne est null
        elseif (is_null($this->anneeref))
        {
            //echo "PeriodeObligatoire->Store : Dans le insert sql <br>";
            $sql = "INSERT INTO CONSTANTES(NOM,VALEUR) VALUES(?,?)";
            $params = array($constname,$valeur);
            $this->anneeref = $anneeref;
        }
        else
        {
            //echo "PeriodeObligatoire->Store : Dans le update sql <br>";
            $sql = "UPDATE CONSTANTES SET VALEUR = ? WHERE NOM = ?";
            $params = array($valeur,$constname);
        }
        //echo "SQL Complement->Store : $sql <br>";
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "PeriodeObligatoire->Store (INSERT/UPDATE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        return $erreur;
    }
    
    function ajouterperiode($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        $periode = array("datedebut" => $datedebut,"datefin" => $datefin);
        $this->listedate[$datedebut . '-' . $datefin] = $periode;
        //echo "<br>ajouter => listedate = " . print_r($this->listedate,true)."<br>";
    }
    
    function supprimerperiode($datedebut,$datefin)
    {
        $datedebut = $this->fonctions->formatdatedb($datedebut);
        $datefin = $this->fonctions->formatdatedb($datefin);
        unset ($this->listedate[$datedebut . '-' . $datefin]);
        //echo "<br>supprimer => listedate = " . print_r($this->listedate,true)."<br>";
    }
}
