<?php

class solde
{

    private $dbconnect = null;

    private $agentid = null;

    private $typeabsenceid = null;

    private $droitaquis = null;

    private $droitpris = null;

    private $typelibelle = null;

    private $fonctions = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "Solde->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    /*
     * function load($soldeid)
     * {
     * // Fonction qui ne sert plus !!!!
     *
     *
     * // if (is_null($this->$soldeid))
     * if (!isset($this->$soldeid))
     * {
     * $sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE TYPEABSENCEID='" . $soldeid . "'";
     * $query=mysqli_query ($this->dbconnect, $sql);
     * $erreur=mysqli_error($this->dbconnect);
     * if ($erreur != "")
     * echo "Solde->Load : " . $erreur . "<br>";
     * if (mysqli_num_rows($query) == 0)
     * {
     * //echo "Solde->Load : Solde $soldeidid non trouvé <br>";
     * return "Le solde $soldeidid n'est pas trouvé <br>";
     * }
     * $result = mysqli_fetch_row($query);
     * $this->soldeid = "$result[0]";
     * $this->droitaquis_demijrs = "$result[1]";
     * $this->droitpris_demijrs = "$result[2]";
     * $this->typecode = "$result[3]";
     * $this->agentid = "$result[4]";
     * }
     * }
     */
    function load($agentid = null, $typecongeid = null)
    {
        if (is_null($agentid) or is_null($typecongeid)) {
            $errlog = "Solde->loadbytypeagent : L'agent ou le type de congé est NULL...";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $sql = "SELECT HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE TYPEABSENCEID='" . $typecongeid . "' AND HARPEGEID='" . $agentid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Solde->load : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $agent = new agent($this->dbconnect);
                $agent->load($agentid);
                // echo "Solde->loadbytypeagent : Solde type = $typecongeid agent = $agentid non trouvé <br>";
                $errlog = "Le solde de congés pour le type $typecongeid n'est pas déclaré pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom();
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $errlog . "<br/>";
            }
            $result = mysqli_fetch_row($query);
            $this->agentid = "$result[0]";
            $this->typeabsenceid = "$result[1]";
            $this->droitaquis = "$result[2]";
            $this->droitpris = "$result[3]";
        }
    }

    function droitaquis($droitaquis = null)
    {
        if (is_null($droitaquis)) {
            if (is_null($this->droitaquis)) {
                $errlog = "Solde->droitaquis : Les droits aquis ne sont pas définis !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return (float) $this->droitaquis; // number_format($this->droitaquis,1);
        } else
            $this->droitaquis = $droitaquis;
    }

    function droitpris($droitpris = null)
    {
        if (is_null($droitpris)) {
            if (is_null($this->droitpris)) {
                $errlog = "Solde->droitpris : Les droits pris ne sont pas définis !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return (float) $this->droitpris; // number_format($this->droitpris,1);
        } else
            $this->droitpris = $droitpris;
    }

    function solde()
    {
        return (float) ($this->droitaquis - $this->droitpris); // number_format($this->droitaquis - $this->droitpris,1);
    }

    function typeabsenceid($typeid = null)
    {
        if (is_null($typeid)) {
            if (is_null($this->typeabsenceid)) {
                $errlog = "Solde->typeabsenceid : Le type de congés n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->typeabsenceid;
        } else
            $this->typeabsenceid = $typeid;
    }

    function typelibelle()
    {
        if (is_null($this->typeabsenceid)) {
            $errlog = "Solde->typelibelle : Le type de congés n'est pas défini !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $sql = "SELECT LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID='" . $this->typeabsenceid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Solde->typelibelle : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Solde->typelibelle : Libellé du solde $this->typeabsenceid non trouvé";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $this->typelibelle = "$result[0]";
        }
        return $this->typelibelle;
    }

    function agent()
    {
        if (is_null($this->agentid)) {
            $errlog = "Solde->agent : L'agent n'est pas défini !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $agent = new agent($this->dbconnect);
            $agent->load($this->agentid);
            return $agent;
        }
    }

    function demandeenattente()
    {
        $sql = "SELECT COUNT(DISTINCT DEMANDE.DEMANDEID) FROM DEMANDE, DECLARATIONTP, AFFECTATION, DEMANDEDECLARATIONTP
WHERE DEMANDE.TYPEABSENCEID='" . $this->typeabsenceid . "'
AND DEMANDE.DEMANDEID = DEMANDEDECLARATIONTP.DEMANDEID
AND DEMANDEDECLARATIONTP.DECLARATIONID = DECLARATIONTP.DECLARATIONID
AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID
AND AFFECTATION.HARPEGEID='" . $this->agentid . "'
AND DEMANDE.STATUT='a';";
        
        // echo "Solde->demandeenattente SQL : $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Solde->demandeenattente : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $result = mysqli_fetch_row($query);
            // echo "Nbre de demande en attente = " . $result[0] . "<br>";
            return "$result[0]";
        }
    }

    function creersolde($codeconge = null, $codeagent = null)
    {
        if (is_null($codeconge)) {
            $errlog = "Solde->creersolde : Le code de congé est NULL !!!";
            $msgerreur = $msgerreur . $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (is_null($codeagent)) {
            $errlog = "Solde->creersolde : Le code de l'agent est NULL !!!";
            $msgerreur = $msgerreur . $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (is_null($codeconge) or is_null($codeagent)) {
            $errlog = "Impossible de créer le solde pour l'agent !!!";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog . "<br/>" . $msgerreur;
        } else {
            $sql = "INSERT INTO SOLDE(HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES('" . $codeagent . "','" . $codeconge . "','0','0')";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Solde->creersolde : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            return $erreur;
        }
    }

    function store()
    {
        if (! is_null($this->agentid) and (! is_null($this->typeabsenceid))) {
            $sql = "UPDATE SOLDE SET DROITAQUIS='" . $this->droitaquis() . "',DROITPRIS='" . $this->droitpris() . "' WHERE HARPEGEID='" . $this->agentid . "' AND TYPEABSENCEID='" . $this->typeabsenceid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Solde->store : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            return $erreur;
        } else {
            $errlog = "Solde->store : La création d'un solde n'est pas possible ==> Utiliser la méthode 'creersolde'";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }
}

?>