<?php

use Fpdf\Fpdf as FPDF;

class structure
{

    public const MAIL_RESP_ENVOI_RESP_PARENT = "1";
    public const MAIL_RESP_ENVOI_GEST_PARENT = "2";
    public const MAIL_RESP_ENVOI_GEST_COURANT = "3";
    
    public const OLD_MAIL_AGENT_ENVOI_RESP_COURANT = "1";
    public const OLD_MAIL_AGENT_ENVOI_GEST_COURANT = "2";
    public const MAIL_AGENT_ENVOI_RESP_COURANT = "4";
    public const MAIL_AGENT_ENVOI_GEST_COURANT = "5";

    private $dbconnect = null;

    private $structureid = null;

    private $nomlong = null;

    private $nomcourt = null;

    private $parentid = null;

    private $responsableid = null;

    private $gestionnaireid = null;

    private $affichesousstruct = null; // permet d'afficher les agents des sous structures

    private $affichetoutagent = null; // permet d'afficher le planning de la structure pour tous les agents de la stucture

    // private $afficherespsousstruct = null; // permet d'afficher le planning des responsables des sous structures -- OBSOLETE --

    // private $respvalidsousstruct = null; // permet au responsable de la structure de valider les demandes des agents d'une structure fille -- OBSOLETE --

    private $gestvalidagent = null; // autorise le gestionnaire à valider les demandes des congés des agents

    private $respaffsoldesousstruct = null; // le responsable de la structure courante visualise le solde des agents des structures inclues 
    
    private $respaffdemandesousstruct = null; // le responsable de la structure courante valide les congés des agents des structures inclues 
    
    private $datecloture = null;

    private $delegueid = null;

    private $fonctions = null;
    
    private $typestruct = null;
    
    private $isincluded = null;
    
    private $islibrary = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "Structure->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($structureid)
    {
        if (is_null($this->structureid)) {
//            $sql = "SELECT STRUCTUREID,
//                           NOMLONG,
//                           NOMCOURT,
//                           STRUCTUREIDPARENT,
//                           RESPONSABLEID,
//                           GESTIONNAIREID,
//                           AFFICHESOUSSTRUCT,
//                           AFFICHEPLANNINGTOUTAGENT,
//                           DATECLOTURE,
//                           AFFICHERESPSOUSSTRUCT,
//                           RESPVALIDSOUSSTRUCT,
//                           GESTVALIDAGENT, 
//                           TYPESTRUCT, 
//                           ISINCLUDED,
//                           ESTBIBLIOTHEQUE,
//                           RESPAFFSOLDESOUSSTRUCT,
//                           RESPAFFDEMANDESOUSSTRUCT
//                    FROM STRUCTURE 
//                    WHERE STRUCTUREID=?";
            $sql = "SELECT STRUCTUREID,
                           NOMLONG,
                           NOMCOURT,
                           STRUCTUREIDPARENT,
                           RESPONSABLEID,
                           GESTIONNAIREID,
                           AFFICHESOUSSTRUCT,
                           AFFICHEPLANNINGTOUTAGENT,
                           DATECLOTURE,
                           NULL,
                           NULL,
                           GESTVALIDAGENT, 
                           TYPESTRUCT, 
                           ISINCLUDED,
                           ESTBIBLIOTHEQUE,
                           RESPAFFSOLDESOUSSTRUCT,
                           RESPAFFDEMANDESOUSSTRUCT
                    FROM STRUCTURE 
                    WHERE STRUCTUREID=?";
            $params = array($structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->Load (STRUCTURE) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->Load (STRUCTURE) : Structure $structureid non trouvé <br>";
                $this->nomcourt = "$structureid";
                $this->nomlong = "Structure inconnue";
                return false;
            }
            $result = mysqli_fetch_row($query);
            $this->structureid = "$result[0]";
            $this->nomlong = "$result[1]";
            $this->nomcourt = "$result[2]";
            $this->parentid = "$result[3]";
            $this->responsableid = "$result[4]";
            $this->gestionnaireid = "$result[5]";
            $this->affichesousstruct = "$result[6]";
            $this->affichetoutagent = "$result[7]";
            if (trim($result[8]) != '')
            {
                $this->datecloture = "$result[8]";
            }
            else // En théorie on ne doit jamais passer par là, car la date de cloture est forcée lors de l'import....
            {
                $this->datecloture = '31/12/9999';
            }
            // $this->afficherespsousstruct = "$result[9]";
            // $this->respvalidsousstruct = "$result[10]";
            $this->gestvalidagent = "$result[11]";
            $this->typestruct = "$result[12]";
            $this->isincluded = "$result[13]";
            $this->islibrary = "$result[14]";
            $this->respaffsoldesousstruct = "$result[15]";
            $this->respaffdemandesousstruct = "$result[16]";
            
            // Prise en compte du cas de la délégation
            $sql = "SELECT IDDELEG,DATEDEBUTDELEG,DATEFINDELEG FROM STRUCTURE WHERE STRUCTUREID=? AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG ";
            $params = array($structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->Load (STRUCTURE DELEGUE) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) != 0) {
                $result = mysqli_fetch_row($query);
                if ("$result[0]" != "") {
                    $this->delegueid = "$result[0]";
                }
            }
        }
        return true;
    }

    function id()
    {
        return $this->structureid;
    }

    function nomlong($name = null)
    {
        if (is_null($name)) {
            if (is_null($this->nomlong)) {
                $errlog = "Structure->nomlong : Le nom de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->nomlong;
        } else
            $this->nomlong = $name;
    }

    function nomcourt($name = null)
    {
        if (is_null($name)) {
            if (is_null($this->nomcourt)) {
                $errlog = "Structure->nomcourt : Le nom de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->nomcourt;
        } else
            $this->nomcourt = $name;
    }
    
    function nomcompletcet($fullpath = false, $fullname = false, $codeonly = false)
    {
        if ($fullpath)
        {
            $type_struct_final = array();
        }
        else
        {
    	   $type_struct_final = array('UO', 'DIR');
        }
        if ($fullname)
        {
            $nameStructComplete = $this->nomlong() . ' (' . $this->nomcourt() . ')';
        }
        elseif ($codeonly)
        {
            $nameStructComplete = $this->id();
        }
        else
        {
    	   $nameStructComplete = $this->nomcourt();  
        }
    	$struct_tmp = $this;
    	while (! is_null($struct_tmp) && ! in_array($struct_tmp->typestruct(), $type_struct_final))
    	{
            $struct_tmp = $struct_tmp->parentstructure();
            if (! is_null($struct_tmp))
            {
                if ($fullname)
                {
                    $nameStructComplete = $struct_tmp->nomlong() . ' (' . $struct_tmp->nomcourt() . ')' . ' / '.$nameStructComplete;
                }
                elseif ($codeonly)
                {
                    $nameStructComplete =  $struct_tmp->id().'/'.$nameStructComplete;
                }
                else
                {
                    $nameStructComplete =  $struct_tmp->nomcourt().' / '.$nameStructComplete;
                }
            }
    	}
    	return $nameStructComplete;
    }
    
    function typestruct()
    {
    	return $this->typestruct;
    }
    
    function isincluded()
    {
        if ($this->isincluded == 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    function estbibliotheque()
    {
        if ($this->islibrary == 0)
        {
            return false;
        }
        else
        {
            $dbconstante = "BRANCHE_BIB";
            $branche_bib = '';
            if ($this->fonctions->testexistdbconstante($dbconstante)) 
            {
                $branche_bib = trim($this->fonctions->liredbconstante($dbconstante));
            }
            
            // S'il n'y a pas de branche speciale "BIB/Centre doc" configuré => On retourne que c'est une bibliothèque (car $this->islibrary=1)
            if ($branche_bib=='')
            {
                return true;
            }
            
            // On ajoute un '/' devant et derrière pour la recherche
            $idcomplet = "/" . $this->nomcompletcet(true, false, true) . "/";
            //$idcomplet = str_replace(" ", "", $idcomplet);
            // Si la structure n'est pas dans la branche 'Bib/Centre doc' => Ce n'est pas une bib/centre doc
            if (stristr($idcomplet, '/' . $branche_bib . '/')===false)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
    }

    function affichetoutagent($affiche = null)
    {
        if (is_null($affiche)) {
            if (is_null($this->affichetoutagent)) {
                $errlog = "Structure->affichetoutagent : Le paramètre affichetoutagent de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affichetoutagent;
        } else
            $this->affichetoutagent = $affiche;
    }
    
    function respaffsoldesousstruct($valide = null)
    {
        if (is_null($valide)) {
            if (is_null($this->respaffsoldesousstruct)) {
                $errlog = "Structure->respaffsoldesousstruct : Le paramètre respaffsoldesousstruct de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->respaffsoldesousstruct;
            }
        } 
        else
        {
            $this->respaffsoldesousstruct = $valide;
        }
    }

    function respaffdemandesousstruct($valide = null)
    {
        if (is_null($valide)) {
            if (is_null($this->respaffdemandesousstruct)) {
                $errlog = "Structure->respaffdemandesousstruct : Le paramètre respaffdemandesousstruct de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->respaffdemandesousstruct;
            }
        } 
        else
        {
            $this->respaffdemandesousstruct = $valide;
        }
    }
    
    /****************************
     * 
     * @deprecated
     * 
     ****************************/
//    function respvalidsousstruct($valide = null)
//    {
//        trigger_error('Method ' . __METHOD__ . ' is deprecated => use respaffsoldesousstruct method instead', E_USER_DEPRECATED);
//        
//        if (is_null($valide)) {
//            if (is_null($this->respvalidsousstruct)) {
//                $errlog = "Structure->respvalidsousstruct : Le paramètre respvalidsousstruct de la structure n'est pas défini !!!";
//                echo $errlog . "<br/>";
//                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
//            }
//            else
//            { 
//                return $this->respvalidsousstruct;
//            }
//        } 
//        else
//        {
//            $this->respvalidsousstruct = $valide;
//        }
//    }

    /****************************
     * 
     * @deprecated
     * 
     ****************************/
//    function afficherespsousstruct($respsousstruct = null)
//    {
//        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
//
//        if (is_null($respsousstruct)) {
//            if (is_null($this->afficherespsousstruct)) {
//                $errlog = "Structure->afficherespsousstruct : Le paramètre afficherespsousstruct de la structure n'est pas défini !!!";
//                echo $errlog . "<br/>";
//                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
//            } else
//                return $this->afficherespsousstruct;
//        } else
//            $this->afficherespsousstruct = $respsousstruct;
//    }

    function gestvalidagent($valide = null)
    {
        if (is_null($valide)) {
            if (is_null($this->gestvalidagent)) {
                $errlog = "Structure->gestvalidagent : Le paramètre gestvalidagent de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->gestvalidagent;
        } else
            $this->gestvalidagent = $valide;
    }

    function sousstructure($sousstruct = null)
    {
        if (is_null($sousstruct)) {
            if (is_null($this->affichesousstruct)) {
                $errlog = "Structure->sousstructure : Le paramètre sousstructure de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affichesousstruct;
        } else
            $this->affichesousstruct = $sousstruct;
    }

    function structurefille()
    {
        $structureliste = null;
        if (! is_null($this->structureid)) {
            $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT=?";
            $params = array($this->structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->structurefille : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->structurefille : La structure $this->structureid n'a pas de structure fille<br>";
            }
            while ($result = mysqli_fetch_row($query)) {
                $structure = new structure($this->dbconnect);
                $structure->load("$result[0]");
                $structureliste[$structure->id()] = $structure;
                unset($structure);
            }
            return $structureliste;
        }
    }

    function agentlist($datedebut, $datefin, $sousstrucuture = null)
    {
        $agentliste = null;
        if ((strcasecmp($this->sousstructure(), 'o') == 0 and strcasecmp($sousstrucuture, 'n') != 0) or (strcasecmp($sousstrucuture, 'o') == 0)) {
            //$structliste = $this->structurefille();
            $structliste = $this->structureinclue();
            if (! is_null($structliste)) {
                foreach ($structliste as $key => $structure) {
                    if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                        $agentliste = array_merge((array) $agentliste, (array) $structure->agentlist($datedebut, $datefin, 'o'));
                    }
                }
            }
        }
        
        // echo "Liste finale des agents : <br>"; print_r($agentliste); echo "<br>";
        
        // $sql = "SELECT SUBREQ.AGENTID FROM ((SELECT AGENTID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID = '" . $this->structureid . "' AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN OR DATEFIN='0000-00-00'))";
        // $sql = $sql . " UNION ";
        // $sql = $sql . "(SELECT AGENTID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
        // $sql = $sql . " UNION ";
        // $sql = $sql . "(SELECT AGENTID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
        // $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";
        
        $sql = "SELECT SUBREQ.AGENTID FROM ((SELECT AFFECTATION.AGENTID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID = ? AND AGENT.AGENTID = AFFECTATION.AGENTID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND (DATEFIN>='" . $this->fonctions->formatdatedb($datefin) . "' OR DATEFIN='0000-00-00'))";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.AGENTID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID=? AND AGENT.AGENTID = AFFECTATION.AGENTID AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN<='" . $this->fonctions->formatdatedb($datefin) . "')";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.AGENTID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID=? AND AGENT.AGENTID = AFFECTATION.AGENTID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "')";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.AGENTID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID=? AND AGENT.AGENTID = AFFECTATION.AGENTID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datefin) . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datefin) . "')";
        $sql = $sql . ") AS SUBREQ";
        $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";
        
        // echo "Structure->agentlist : SQL (agentlist) = $sql <br>";
        $params = array($this->structureid,$this->structureid,$this->structureid,$this->structureid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->agentlist : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        // echo "Avant le while...<br>";
        while ($result = mysqli_fetch_row($query)) {
            $agent = new agent($this->dbconnect);
            // echo "Apres le new et avant le load =" . $result[0] . "<br>";
            if ($agent->load("$result[0]")) {
                // echo "Apres le load...<br>";
                // La clé est NOM + PRENOM + AGENTID => permet de trier les tableaux par ordre alphabétique
                $agentliste[$agent->nom() . " " . $agent->prenom() . " " . $agent->agentid()] = $agent;
                // / $agentliste[$agent->agentid()] = $agent;
                // echo "Apres la mise dans le tableau <br>";
                unset($agent);
            }
        }
        // echo "<br>agentliste = "; print_r((array)$agentliste); echo "<br>";
        if (is_array($agentliste))
            ksort($agentliste);
        
        if (count((array) $agentliste) == 0) {
            // echo "Structure->agentlist : La structure $this->nomcourt (Identifiant $this->structureid) n'a pas d'agent<br>";
            $errlog = "La structure $this->nomcourt (Identifiant $this->structureid) n'a pas d'agent";
            // echo $errlog."<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        // echo "<br>agentliste = "; print_r((array)$agentliste); echo "<br>";
        return $agentliste;
    }

    function resp_envoyer_a(&$codeinterne = NULL, $update = false)
    {
        // La fonction retourne le code de l'agent à qui envoyer le mail
        // Le paramètre $codeinterne retourne les valeurs 1,2,3... => Nécessaire pour initialisation de la liste
        // dans les pages gestion_structure.php par exemple si $update=false
        // Si $update = true => On met à jour la valeur du champs
        if ($update == true) {
            if (is_null($codeinterne)) {
                $errlog = "Structure->resp_envoyer_a : Le codeinterne est NULL";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $sql = "UPDATE STRUCTURE SET DEST_MAIL_RESPONSABLE=? WHERE STRUCTUREID = ?";
                $params = array($codeinterne,$this->structureid);
                $query = $this->fonctions->prepared_query($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Structure->resp_envoyer_a (UPDATE) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            // echo "Structure->resp_envoyer_a (SELECT) : Avant le select DEST_MAIL_RESPONSABLE <br>";
            $sql = "SELECT DEST_MAIL_RESPONSABLE FROM STRUCTURE WHERE STRUCTUREID = ?";
            $params = array($this->structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->resp_envoyer_a (SELECT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            if (count((array)$result)==0)
            {
                // Si on ne trouve pas la structure dans la base
                $codeinterne = structure::MAIL_RESP_ENVOI_RESP_PARENT;
                return null;
            }
            $codeinterne = $result[0];
            //echo "codeinterne = $codeinterne <br>";
            //error_log(basename(__FILE__) . " " . "codeinterne = $codeinterne ");
            $agent = new agent($this->dbconnect);
            switch ($codeinterne) {
                case structure::MAIL_RESP_ENVOI_GEST_PARENT : // Envoi au gestionnaire du service parent
                    $parentstruct = $this->parentstructure();
                    if (! is_null($parentstruct))
                    {
                        //if ($parentstruct->gestionnaireid . "" != "")
                        if ($agent->existe($parentstruct->gestionnaireid))
                        {
                            return $parentstruct->gestionnaire();
                        }
                        else
                        {
                            return null;
                        }
                    }
                    break;
                case structure::MAIL_RESP_ENVOI_GEST_COURANT: // Envoi au gestionnaire du service courant
                    //if ($this->gestionnaireid . "" != "")
                    if ($agent->existe($this->gestionnaireid))
                    {
                        return $this->gestionnaire();
                    }
                    else
                    {
                        return null;
                    }
                    break;
                default: // $codeinterne = 1 ou $codeinterne non initialisé
                    $codeinterne = structure::MAIL_RESP_ENVOI_RESP_PARENT; // Envoi au responsable du service parent
                    $parentstruct = $this->parentstructure();
                    // error_log(basename(__FILE__) . " " . $this->nomlong);
                    // error_log(basename(__FILE__) . " " . $parentstruct->nomlong);
                    if (! is_null($parentstruct))
                    {
                        //if ($parentstruct->responsableid . "" != "")
                        if ($agent->existe($parentstruct->responsableid))
                        {
                            return $parentstruct->responsable();
                        }
                        else
                        {
                            return null;
                        }
                    }
            }
        }
    }

    function agent_envoyer_a(&$codeinterne = NULL, $update = false)
    {
        // La fonction retourne le code de l'agent à qui envoyer le mail
        // Le paramètre $codeinterne retourne les valeurs 1,2,3... => Nécessaire pour initialisation de la liste
        // dans les pages gestion_structure.php par exemple si $update=false
        // Si $update = true => On met à jour la valeur du champs
        if ($update == true) {
            if (is_null($codeinterne)) {
                $errlog = "Structure->agent_envoyer_a : Le codeinterne est NULL";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else 
            {
                // On vérifie qu'il n'y a pas d'ancienne valeur à écrire
                // Si oui, on les translate vers les nouvelles constantes
                switch ($codeinterne)
                {
                    case structure::OLD_MAIL_AGENT_ENVOI_GEST_COURANT :
                        $codeinterne = structure::MAIL_AGENT_ENVOI_GEST_COURANT;
                        break;
                    case structure::OLD_MAIL_AGENT_ENVOI_RESP_COURANT :
                        $codeinterne = structure::MAIL_AGENT_ENVOI_RESP_COURANT;
                        break;
                }
                $sql = "UPDATE STRUCTURE SET DEST_MAIL_AGENT=? WHERE STRUCTUREID = ?";
                $params = array($codeinterne,$this->structureid);
                $query = $this->fonctions->prepared_query($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") 
                {
                    $errlog = "Structure->agent_envoyer_a (UPDATE) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            $sql = "SELECT DEST_MAIL_AGENT FROM STRUCTURE WHERE STRUCTUREID = ?";
            $params = array($this->structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->agent_envoyer_a (SELECT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            if (count((array)$result)==0)
            {
                // Si on ne trouve pas la structure dans la base
                $codeinterne = structure::MAIL_AGENT_ENVOI_RESP_COURANT;
                return null;
            }
            $codeinterne = $result[0];
            $agent = new agent($this->dbconnect);
            switch ($codeinterne) 
            {
                case structure::MAIL_AGENT_ENVOI_GEST_COURANT: // Envoi au gestionnaire du service courant
                    //if ($this->gestionnaireid . "" != "")
                    if ($agent->existe($this->gestionnaireid))
                    {
                        return $this->gestionnaire();
                    }
                    else
                    {
                        return null;
                    }
                    break;
                default: // $codeinterne = 1 ou $codeinterne non initialisé
                    $codeinterne = structure::MAIL_AGENT_ENVOI_RESP_COURANT; // Envoi au responsable du service courant
                    //if ($this->responsableid . "" != "")
                    if ($agent->existe($this->responsableid))
                    {
                        return $this->responsable();
                    }
                    else
                    {
                        return null;
                    }
            }
        }
    }

    function parentstructure()
    {
        $parentstruct = null;
        if (is_null($this->parentid)) {
            $errlog = "Structure->parentstructure : La structure parente n'est pas définie !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $parentstruct = new structure($this->dbconnect);
            if (! $parentstruct->load("$this->parentid")) {
                // Si on ne peut pas charger la structure parente => On retourne null
                $parentstruct = null;
            }
        }
        return $parentstruct;
    }

    function datecloture()
    {
        return $this->fonctions->formatdate($this->datecloture);
    }

    function responsablesiham()
    {
        if (is_null($this->responsableid) or ($this->responsableid == '')) {
            $errlog = "<B><div class='redtext'>Structure->Responsablesiham : Le responsable de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini !!! </div></B>";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $responsable = new agent($this->dbconnect);
            if ($responsable->load("$this->responsableid")) {
                return $responsable;
            } else {
                $responsable->civilite('');
                $responsable->nom('INCONNU');
                $responsable->prenom('INCONNU');
                return $responsable;
            }
        }
    }

    function responsable($respid = null)
    {
        if (is_null($respid)) {
            if (is_null($this->responsableid) or ($this->responsableid == '')) {
                $errlog = "<B><div class='redtext'>Structure->Responsable : Le responsable de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini !!! </div></B>";
                //echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $responsable = new agent($this->dbconnect);
                // Si le délégué est renseigné ==> On retourne le délégué comme responsable
                if (! is_null($this->delegueid) and ($this->delegueid != "")) {
                    if ($responsable->load("$this->delegueid")) 
                    {
                        // error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" La délégation est active => L'agent délégué est " . $responsable->identitecomplete()));
                        return $responsable;
                    } else {
                        $responsable->civilite('');
                        $responsable->nom('INCONNU');
                        $responsable->prenom('INCONNU');
                        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" La délégation est active mais problème dans le chargement (id délégué = " . $this->delegueid . ")"));
                        return $responsable;
                    }
                }                // Sinon c'est le responsable SIHAM qu'il faut retourner
                else {
                    // error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents(" Le responsable de " . $this->nomlong . " est " . $this->responsablesiham()->identitecomplete()));
                    return $this->responsablesiham();
                }
            }
        } else
            $this->responsableid = $respid;
    }

    function gestionnaire($gestid = null)
    {
        if (is_null($gestid)) {
            if (is_null($this->gestionnaireid) or ($this->gestionnaireid == '')) {
                $errlog = "Structure->Gestionnaire : Le gestionnaire de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini.";
                //echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $gestionnaire = new agent($this->dbconnect);
                // echo "Structure->gestionnaire : XXX" . $this->gestionnaireid . "XXXX <br>";
                if ($gestionnaire->load("$this->gestionnaireid")) {
                    // echo "Structure->gestionnaire : Apres le load XXX" . $this->gestionnaireid . "XXXX <br>";
                    return $gestionnaire;
                } else {
                    $gestionnaire->civilite('');
                    $gestionnaire->nom('INCONNU');
                    $gestionnaire->prenom('INCONNU');
                    return $gestionnaire;
                }
            }
        } else
            $this->gestionnaireid = $gestid;
    }

    function planning($mois_annee_debut, $mois_annee_fin, $showsousstruct = null, $includeteletravail = false, $includecongeabsence = true)
    {
        $planningservice = null;
        if (is_null($mois_annee_debut) or is_null($mois_annee_fin)) {
            $errlog = "Structure->planning : Au moins un des paramètres est non defini (null)";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        $fulldatedebut = "01/" . $mois_annee_debut;
        $tempfulldatefindb = $this->fonctions->formatdatedb("01/" . $mois_annee_fin);
        $timestampfin = strtotime($tempfulldatefindb);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("Ym", strtotime("+1month", $timestampfin)) . "01";
        // echo "fulldatefin = $fulldatefin <br>";
        $timestampfin = strtotime($fulldatefin);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("d/m/Y", strtotime("-1day", $timestampfin));
        // echo "fulldatefin (en lisible)= $fulldatefin <br>";
        
        $listeagent = $this->agentlist($fulldatedebut, $fulldatefin, $showsousstruct);
        if (is_array($listeagent)) {
            foreach ($listeagent as $key => $agent) {
                // echo "structure -> planning : Interval du planning a charger pour l'agent : " . $agent->nom() . " " . $agent->prenom() ." = " . $fulldatedebut . " --> " . $fulldatefin . "<br>";
                $planningservice[$agent->agentid()] = $agent->planning($fulldatedebut, $fulldatefin,$includeteletravail,$includecongeabsence);
                // echo "structure -> planning : Apres planning de ". $agent->nom() . " " . $agent->prenom() . "<br>";
            }
        }
        return $planningservice;
    }

    function planninghtml($mois_annee_debut, $showsousstruct = null, $noiretblanc = false, $includeteletravail = false, $dbclickable = false, $includecongeabsence = true) // mois_annee_debut => Le format doit être MM/YYYY
    {
        // echo "Je debute planninghtml <br>";
        //list ($jour, $indexmois, $annee) = split('[/.-]', '01/' . $mois_annee_debut);
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        if (($annee . $indexmois <= date('Ym')) and ($noiretblanc == true)) 
        {
            echo $this->fonctions->showmessage(fonctions::MSGWARNING, "Les informations antérieures à la date du jour ont été masquées.");
        }
        
        $planningservice = $this->planning($mois_annee_debut, $mois_annee_debut, $showsousstruct,$includeteletravail,$includecongeabsence);
        
        if (! is_array($planningservice)) {
            return ""; // Si aucun élément du planning => On retourne vide
        }
        
        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ($listecateg as $keycateg => $nomcateg) 
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
        //var_dump($listeabs); 
        
        $showstructcolonne = false;
        $searchstruct = $this->id();
        foreach ($planningservice as $agentid => $planning)
        {
            if ($planning->agent()->structureid()<>$searchstruct) // Si il y a des agents affecté dans une autre structure que celle courante
            {
                $showstructcolonne = true;
                break;
            }
        }
        
        
        // echo "Apres le chargement du planning du service <br>";
        $htmltext = "";
        $htmltext = $htmltext . "<div id='structplanning'>";
        $htmltext = $htmltext . "<table class='tableau' id='struct_plan_" . $this->id() . "'>";
        
        $titre_a_ajouter = TRUE;
        $elementlegende = array();
        $tabstruct = array();
        foreach ($planningservice as $agentid => $planning) 
        {
            if ($titre_a_ajouter) 
            {
                if ($showstructcolonne)
                {
                    $nbcolonneaajouter = 2;
                }
                else
                {
                    $nbcolonneaajouter = 1;
                }
                $htmltext = $htmltext . "<thead>";
                $htmltext = $htmltext . "<tr class='entete_mois'><td class='titresimple' colspan=" . (count($planningservice[$agentid]->planning()) + $nbcolonneaajouter) . " align=center >Gestion des dossiers pour la structure " . $this->nomlong() . " (" . $this->nomcourt() . ")</td></tr>";
                $monthname = $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . date("Y", strtotime($this->fonctions->formatdatedb("01/" . $mois_annee_debut)));
                // echo "Nom du mois = " . $monthname . "<br>";
                $htmltext = $htmltext . "<tr class='entete_mois'><td colspan='" . (count($planningservice[$agentid]->planning()) + $nbcolonneaajouter) . "'>" . $monthname . "</td></tr>";
                // echo "Nbre de jour = " . count($planningservice[$agentid]->planning()) . "<br>";
                $htmltext = $htmltext . "<tr class='entete'><th class='cellulesimple cellulemultiligne cursorpointer'>Agent<span class='sortindicator'> </span></th>";
                if ($showstructcolonne)
                {
                    $htmltext = $htmltext . "<th class='cellulesimple cursorpointer'>Structure<span class='sortindicator'> </span></th>";
                }
                for ($indexjrs = 0; $indexjrs < (count($planningservice[$agentid]->planning()) / 2); $indexjrs ++) {
                    // echo "indexjrs = $indexjrs <br>";
                    $nomjour = $this->fonctions->nomjour(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut);
                    $titre = $nomjour . " " . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . " " . $monthname;
                    $htmltext = $htmltext . "<th class='cellulesimple' colspan='2' title='" . $titre . "'";
                    // echo "Date case = " . $this->fonctions->formatdatedb(str_pad(($indexjrs + 1),2,"0",STR_PAD_LEFT) . "/" . $mois_annee_debut) . " Date jour = " . date("Ymd") . "<br>";
                    if ($this->fonctions->formatdatedb(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut) == date("Ymd")) {
                        $htmltext = $htmltext . " bgcolor='#3FC6FF'";
                    }
                    $htmltext = $htmltext . ">" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</th>";
                }
                $htmltext = $htmltext . "</tr>";
                $htmltext = $htmltext . "</thead>";
                $htmltext = $htmltext . "<tbody>";
                $titre_a_ajouter = FALSE;
            }
            
            // echo "Je charge l'agent $agentid <br>";
            //$agent = new agent($this->dbconnect);
            //$agent->load($agentid);
            $agent = $planning->agent();
            // echo "l'agent $agentid est chargé ... <br>";
            $htmltext = $htmltext . "<tr class='ligneplanning'>";
//            $htmltext = $htmltext . "<td>" . $agent->nom() . " " . $agent->prenom() . "</td>";
            $htmltext = $htmltext . "<td class='cellulemultiligne'>" . $agent->civilite() . " <span class='agentidentite'>" . $agent->nom() . " " . $agent->prenom() . "</span></td>"; //$agent->identitecomplete(true)
            if ($showstructcolonne)
            {
                if (!isset($tabstruct[$agent->structureid()]))
                {
                    $struct = new structure($this->dbconnect);
                    $struct->load($agent->structureid());
                    $tabstruct[$agent->structureid()] = $struct;
                }
                else
                {
                    $struct = $tabstruct[$agent->structureid()];
                }
                $htmltext = $htmltext . "<td>" . $struct->nomcourt() . "</td>";
            }
            // echo "Avant chargement des elements <br>";
            $listeelement = $planning->planning();
            // echo "Apres chargement des elements <br>";
            foreach ($listeelement as $keyelement => $element) {
                // echo "Boucle sur l'element <br>";
                $htmltext = $htmltext . $element->html(false, null, $noiretblanc, $dbclickable);
                
                if (!in_array($element->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
                {
                    if (array_key_exists($element->type(),$listeabs))
                    {
                        // Si c'est une absence dans la catégorie "télétravail hors convention"
                        if (strcmp($element->parenttype(),'teletravHC')==0)
                        {
                            $elementlegende[$element->parenttype()] = $element->parenttype();
                        }
                        else // C'est une absence d'un autre type => Donc de type absence
                        {
                            //echo "Le type de l'élément = " . $element->type() . "<br>";
                            $elementlegende['abs'] = 'abs';
                        }
                    }
                    else
                    {
                        $elementlegende[$element->type()] = $element->type();
                    }
                }
                //var_dump($elementlegende);
            }
            // echo "Fin boucle sur les elements <br>";
            $htmltext = $htmltext . "</tr>";
        }
        $htmltext = $htmltext . "</tbody>";
        $htmltext = $htmltext . "</table>";

        $htmltext = $htmltext . "
<script>
/*******************************************
******* Déclarations déplacées dans menu.php
const getCellValue = (tr, idx) =>
{
    if (tr.children[idx].querySelector('time')!==null) // Si on a un time dans le td, alors on trie sur l'attribut datetime
    {
        return tr.children[idx].querySelector('time').getAttribute('datetime');
    }
    else if (tr.children[idx].querySelector('span')!==null) // Si on a un span dans le td, alors on trie sur l'attribut span
    {
        //alert ('InnerText = ' + tr.children[idx].querySelector('span').innerText);
        return tr.children[idx].querySelector('span').innerText;
    }
    else
    {
        return tr.children[idx].innerText || tr.children[idx].textContent;
    }
}
            
const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
    v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
*******************************************
*/
 
         
// do the work...
document.getElementById('struct_plan_" . $this->id() . "').querySelectorAll('th').forEach(th => th.addEventListener('click', (() => {

    const currentsortindicator = th.querySelector('.sortindicator')

    if (currentsortindicator!==null)
    {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        //alert (table.id);

        if (currentsortindicator.innerText.trim().length>0)
        {
            th.asc = !th.asc
        }

        Array.from(tbody.querySelectorAll('tr'))
            .sort(comparer(Array.from(th.parentNode.children).indexOf(th), th.asc))
            .forEach(tr => tbody.appendChild(tr) );
        theader = table.querySelector('theader');
        
        //alert(Array.from(th.parentNode.querySelectorAll('th')));
        
        for (var thindex = 0 ; thindex < document.getElementById('struct_plan_" . $this->id() . "').querySelectorAll('th').length; thindex++)
        {
            //alert (thindex);
            if (th.parentNode.children[thindex]!==null)
            {
                //alert (th.parentNode.children[thindex].innerHTML);
                var thsortindicator = th.parentNode.children[thindex].querySelector('.sortindicator');
                if (thsortindicator!==null)
                {
                    //alert (thsortindicator.innerText);
                    thsortindicator.innerText = ' ';
                    //alert (thsortindicator.innerText);
                }
            }
        }
    
        if (currentsortindicator!==null)
        {
            if (th.asc)
            {
                //alert ('plouf');
                currentsortindicator.innerHTML = '&darr;'; // flêhe qui descend
            }
            else
            {
                //alert ('ploc');
                currentsortindicator.innerHTML = '&uarr;'; // flêche qui monte
            }
        }
    }
})));

document.getElementById('struct_plan_" . $this->id() . "').querySelectorAll('th').forEach(element => element.asc = true); //  On initialise le tri des colonnes en ascendant
document.getElementById('struct_plan_" . $this->id() . "').querySelectorAll('th')[0].click(); // On simule le clic sur la 1e colonne pour faire afficher la flêche
    
    
</script>";
        
        
        
        
        $htmltext = $htmltext . "</div>";
        //var_dump($elementlegende);
        if ($noiretblanc == false) {
            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période 
                $annee = $annee - 1;
            }
            $htmltext = $htmltext . $this->fonctions->legendehtml($annee,$includeteletravail,$elementlegende);
        }
        
        $htmltext = $htmltext . "<br>";
        $htmltext = $htmltext . "<form name='structplanningpdf_" . $this->structureid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
        if ($includeteletravail and !$noiretblanc)
        {
            $htmltext = $htmltext . "<input type='checkbox' id='hide_teletravail_". $this->id() . "' name='hide_teletravail_". $this->id() . "' onclick='hide_teletravail(\"struct_plan_" . $this->id() . "\");' >Masquer le télétravail</input>";
            $htmltext = $htmltext . "<br><br>";
        }
        $htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='previous' value='no'>";
        if ($noiretblanc)
            $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='yes'>";
        else
            $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='no'>";
        $htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut . "'>";
        if ($includeteletravail)
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='yes'>";
        else
            $htmltext = $htmltext . "<input type='hidden' name='includeteletravail' value='no'>";
            
        $htmltext = $htmltext . "<a href='javascript:document.structplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF</a>";
        $htmltext = $htmltext . "</form>";
        
        // $htmltext = $htmltext . "<form name='structpreviousplanningpdf_" . $this->structureid . "' method='post' action='affiche_pdf.php' target='_blank'>";
        // $htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid ."'>";
        // $htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
        // $htmltext = $htmltext . "<input type='hidden' name='previous' value='yes'>";
        // if ($noiretblanc)
        // $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='yes'>";
        // else
        // $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='no'>";
        // $htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut . "'>";
        // $htmltext = $htmltext . "<a href='javascript:document.structpreviousplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF (année précédente)</a>";
        // $htmltext = $htmltext . "</form>";
        return $htmltext;
    }

    function planningresponsablesousstructhtml($mois_annee_debut, $includeteletravail = false, $dbclickable = false) // Le format doit être MM/YYYY
    {
        $fulldatedebut = "01/" . $mois_annee_debut;
        $tempfulldatefindb = $this->fonctions->formatdatedb("01/" . $mois_annee_debut);
        $timestampfin = strtotime($tempfulldatefindb);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("Ym", strtotime("+1month", $timestampfin)) . "01";
        // echo "fulldatefin = $fulldatefin <br>";
        $timestampfin = strtotime($fulldatefin);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("d/m/Y", strtotime("-1day", $timestampfin));
        // echo "fulldatefin (en lisible)= $fulldatefin <br>";
        //list ($jour, $indexmois, $annee) = split('[/.-]', '01/' . $mois_annee_debut);
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        
        
        $structure = new structure($this->dbconnect);
        $structfilleliste = $this->structurefille();
        $resplist = null;
        if (! is_array($structfilleliste)) { // Si pas de strcuture fille => On sort
            return "";
        }
        
        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ($listecateg as $keycateg => $nomcateg)
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
        //var_dump($listeabs);
        
        
        foreach ($structfilleliste as $structkey => $structure) {
            // Si la structure n'est pas fermée on cherche le responsable
            if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                if (! is_null($structure->responsable())) {
                    $resplist[$structure->responsable()->agentid()] = $structure->responsable();
                }
            }
        }
        if (is_array($resplist)) {
            $htmltext = "";
            $htmltext = $htmltext . "<div id='structplanning'>";
            $htmltext = $htmltext . "<table class='tableau'>";
            
            $elementlegende = array();
            $titre_a_ajouter = TRUE;
            foreach ($resplist as $agentid => $responsable) 
            {
                $elementlegende = array();
                $planning = $responsable->planning($fulldatedebut, $fulldatefin,$includeteletravail)->planning();
                /*
                 * echo "Planning = ";
                 * print_r($planning);
                 * echo "<br>";
                 */
                // echo 'count(planning) = ' .count($planning) . '<br>';
                if ($titre_a_ajouter) {
                    $htmltext = $htmltext . "<tr class='entete_mois'><td class='titresimple' colspan=" . (count($planning) + 1) . " align=center >Planning des responsables des sous-structures " . $this->nomlong() . " (" . $this->nomcourt() . ")</td></tr>";
                    $monthname = $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . date("Y", strtotime($this->fonctions->formatdatedb("01/" . $mois_annee_debut)));
                    // echo "Nom du mois = " . $monthname . "<br>";
                    $htmltext = $htmltext . "<tr class='entete_mois'><td colspan='" . (count($planning) + 1) . "'>" . $monthname . "</td></tr>";
                    // echo "Nbre de jour = " . count($planningservice[$agentid]->planning()) . "<br>";
                    $htmltext = $htmltext . "<tr class='entete'><td>Agent</td>";
                    for ($indexjrs = 0; $indexjrs < (count($planning) / 2); $indexjrs ++) {
                        // echo "indexjrs = $indexjrs <br>";
                        $nomjour = $this->fonctions->nomjour(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut);
                        $titre = $nomjour . " " . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . " " . $monthname;
                        $htmltext = $htmltext . "<td colspan='2' title='" . $titre . "'>" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</td>";
                    }
                    $htmltext = $htmltext . "</tr>";
                    $titre_a_ajouter = FALSE;
                }
                $htmltext = $htmltext . "<tr class='ligneplanning'>";
//                $htmltext = $htmltext . "<td>" . $responsable->nom() . " " . $responsable->prenom() . "</td>";
                $htmltext = $htmltext . "<td>" . $responsable->identitecomplete() . "</td>";
                // echo "Avant chargement des elements <br>";
                $listeelement = $responsable->planning($fulldatedebut, $fulldatefin, $includeteletravail)->planning();
                // echo "Apres chargement des elements <br>";
                foreach ($listeelement as $keyelement => $element) 
                {
                    // echo "Boucle sur l'element <br>";
                    $htmltext = $htmltext . $element->html(false, null, false, $dbclickable);
                    if (!in_array($element->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
                    {
                        if (array_key_exists($element->type(),$listeabs))
                        {
                            // Si c'est une absence dans la catégorie "télétravail hors convention"
                            if (strcmp($element->parenttype(),'teletravHC')==0)
                            {
                                $elementlegende[$element->parenttype()] = $element->parenttype();
                            }
                            else // C'est une absence d'un autre type => Donc de type absence
                            {
                                //echo "Le type de l'élément = " . $element->type() . "<br>";
                                $elementlegende['abs'] = 'abs';
                            }
                        }
                        else
                        {
                            $elementlegende[$element->type()] = $element->type();
                        }
                    }
                }
                // echo "Fin boucle sur les elements <br>";
                $htmltext = $htmltext . "</tr>";
            }
            $htmltext = $htmltext . "</table>";
            $htmltext = $htmltext . "</div>";

            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période
                $annee = $annee - 1;
            }
            $htmltext = $htmltext . $this->fonctions->legendehtml($annee, $includeteletravail,$elementlegende);
            $htmltext = $htmltext . "<br>";
        }
        return $htmltext;
    }

    function dossierhtml($pourmodif = FALSE, $responsableid = NULL)
    {
        
        // echo "strucutre->dossierhtml : Non refaite !!!!! <br>";
        // return null;
        $htmltext = "<br>";
        $htmltext = "<table class='tableausimple'>";
        $htmltext = $htmltext . "<tr><td class='titresimple' colspan=4 align=center >Gestion des dossiers pour la structure " . $this->nomlong() . " (" . $this->nomcourt() . ")</td></tr>";
        $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Agent</td><td class='cellulesimple'>Report des congés</td><td class='cellulesimple'>Nbre jours 'Garde d'enfant'</td><td class='cellulesimple'>Convention de télétravail</td></tr>";
        $agentliste = $this->agentlist(date('d/m/Y'), date('d/m/Y'), 'n');
        
        // Si on est en mode 'responsable' <=> le code du responsable de la structure est passé en paramètre
        if (! is_null($responsableid)) {
            // On ajoute les responsables de structures filles
            $structureliste = $this->structurefille();
            $responsableliste = array();
            if (is_array($structureliste)) {
                foreach ($structureliste as $key => $structure) {
                    if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                        $responsable = $structure->responsable();
                        if ($responsable->agentid() != SPECIAL_USER_IDCRONUSER) {
                            // La clé NOM + PRENOM + AGENTID permet de trier les éléments par ordre alphabétique
                            $responsableliste[$responsable->nom() . " " . $responsable->prenom() . " " . $responsable->agentid()] = $responsable;
                            // /$responsableliste[$responsable->agentid()] = $responsable;
                        }
                    }
                }
            }
            $agentliste = array_merge((array) $agentliste, (array) $responsableliste);
            ksort($agentliste);
        }
        if (is_array($agentliste)) {
            foreach ($agentliste as $key => $membre) {
                // echo "Structure->dossierhtml : Je suis dans l'agent " . $membre->nom() . "<br>";
                if ($membre->agentid() != $responsableid) {
                    $htmltext = $htmltext . "<tr>";
                    $htmltext = $htmltext . "<center><td class='cellulesimple centeraligntext' >" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</td></center>";
                    
                    $complement = new complement($this->dbconnect);
                    $complement->load($membre->agentid(), "REPORTACTIF");
                    if ($complement->valeur() == "")
                        $complement->valeur("n"); // Si le complement n'est pas saisi, alors la valeur est "N" (non)
                    $htmltext = $htmltext . "<td class='cellulesimple centeraligntext'>";
                    if ($pourmodif) {
                        $htmltext = $htmltext . "<select name=report[" . $membre->agentid() . "]>";
                        $htmltext = $htmltext . "<option value='n'";
                        if (strcasecmp($complement->valeur(), "n") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . ">Non</option>";
                        $htmltext = $htmltext . "<option value='o'";
                        if (strcasecmp($complement->valeur(), "o") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . ">Oui</option>";
                        $htmltext = $htmltext . "</select>";
                    } else {
                        $htmltext = $htmltext . $this->fonctions->ouinonlibelle($complement->valeur());
                    }
                    $htmltext = $htmltext . "</td></center>";
                    unset($complement);
                    
                    // Ajout du nombre de jours "enfant malade"
                    $complement = new complement($this->dbconnect);
                    $complement->load($membre->agentid(), "ENFANTMALADE");
                    $htmltext = $htmltext . "<td class='cellulesimple' >";
                    if ($pourmodif)
                    {
                        $htmltext = $htmltext . "<input type='text' class='centeraligntext' name=enfantmalade[" . $membre->agentid() . "] value='" . intval($complement->valeur()) . "'/>";
                    }
                    else
                    {
                        $htmltext = $htmltext . "<center>" . intval($complement->valeur()) . "</center>";
                    }
                    $htmltext = $htmltext . "</td>";
                            
                    // Ajout des conventions "télétravail"
                    $htmltext = $htmltext . "<td class='cellulesimple' >";
                    // calcul de la date de début de l'interval qui nous interresse
                    $dateinferieure = date("Y-m-d");
                    $dateinferieure = strtotime($dateinferieure."- 6 months");
                    $dateinferieure = date("d/m/Y",$dateinferieure);
                    $teletravailliste = $membre->teletravailliste($dateinferieure, "31/12/2099");
                    $tabconventionactive = array();
                    foreach ($teletravailliste as $teletravailid) // On ne garde que les conventions actives
                    {
                        $teletravail = new teletravail($this->dbconnect);
                        $teletravail->load($teletravailid);
                        if ($teletravail->statut() == teletravail::TELETRAVAIL_VALIDE)
                        {
                            $tabconventionactive[] = $teletravail;
                        }
                    }
                    if (count($tabconventionactive)==0)
                    {
                        $htmltext = $htmltext . "Aucune convention de télétravail";
                    }
                    else
                    {
                        $teletravailstring = "";
                        foreach ($tabconventionactive as $convention)
                        {
                            if (strlen($teletravailstring)>0) $teletravailstring = $teletravailstring . "<br>";
                            
                            $styletexte = '';
                            $extrainfo = "";
                            if (key($tabconventionactive)==(count($tabconventionactive)-1)) // Si on est sur le dernier élément du tableau
                            {
                                $datefinalerte = strtotime($this->fonctions->formatdatedb($convention->datefin())."- 1 months");
                                $datefinalerte = date("Ymd",$datefinalerte);
                                
                                $datefinerreur = strtotime($this->fonctions->formatdatedb($convention->datefin())."+ 6 months");
                                $datefinerreur = date("Ymd",$datefinerreur);
                                if (($this->fonctions->formatdatedb($convention->datefin()) > date('Ymd')) and ($datefinalerte < date('Ymd')))
                                {
                                    // On est à moins d'un mois de la fin de la convention ===> On met un marqueur 
                                    $styletexte = "class='warningfinconvention' ";
                                    $extrainfo = "<span data-tip=" . chr(34) . "Cette convention se termine dans moins d'un mois et aucune prolongation n'est enregistrée." . chr(34) . ">";
                                }
                                elseif (($this->fonctions->formatdatedb($convention->datefin()) < date('Ymd')) and ($datefinerreur > date('YMd')))
                                {
                                    // On est à moins d'un mois de la fin de la convention ===> On met un marqueur
                                    $styletexte = "class='alertfinconvention' ";
                                    $extrainfo = "<span data-tip=" . chr(34) . "Cette convention est terminée depuis moins de six mois et aucune prolongation n'est enregistrée." . chr(34) . ">";
                                }
                            }
                            $teletravailstring = $teletravailstring . "<span $styletexte>$extrainfo" . $this->fonctions->formatdate($convention->datedebut()) . " -> " . $this->fonctions->formatdate($convention->datefin()) . "</span>";
                            if (strlen($extrainfo)>0)
                            {
                                $teletravailstring = $teletravailstring . "</span>";
                            }
                        }
                        $htmltext = $htmltext . $teletravailstring;
                    }
                    $htmltext = $htmltext . "</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
        }
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "<br>";
        
        return $htmltext;
    }

    function store()
    {
        // echo "structure->store : Non refaite !!!!! <br>";
        // return false;
        $msgerreur = null;
//        $sql = "UPDATE STRUCTURE 
//                SET AFFICHESOUSSTRUCT=?, 
//                    AFFICHEPLANNINGTOUTAGENT=?, 
//                    AFFICHERESPSOUSSTRUCT=?, 
//                    RESPVALIDSOUSSTRUCT=?, 
//                    GESTVALIDAGENT=?,
//                    RESPAFFSOLDESOUSSTRUCT=?,
//                    RESPAFFDEMANDESOUSSTRUCT=?
//                WHERE STRUCTUREID=?";
//        // echo "SQL = " . $sql . "<br>";
//        $params = array($this->sousstructure(),
//                        $this->affichetoutagent(),
//                        $this->afficherespsousstruct,
//                        $this->respvalidsousstruct,
//                        $this->gestvalidagent(),
//                        $this->respaffsoldesousstruct(),
//                        $this->respaffdemandesousstruct(),
//                        $this->id());
        $sql = "UPDATE STRUCTURE 
                SET AFFICHESOUSSTRUCT=?, 
                    AFFICHEPLANNINGTOUTAGENT=?, 
                    GESTVALIDAGENT=?,
                    RESPAFFSOLDESOUSSTRUCT=?,
                    RESPAFFDEMANDESOUSSTRUCT=?
                WHERE STRUCTUREID=?";
        // echo "SQL = " . $sql . "<br>";
        $params = array($this->sousstructure(),
                        $this->affichetoutagent(),
                        $this->gestvalidagent(),
                        $this->respaffsoldesousstruct(),
                        $this->respaffdemandesousstruct(),
                        $this->id());
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (STRUCTURE - Sous struct + Affiche) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        
        $sql = "UPDATE STRUCTURE SET GESTIONNAIREID=? WHERE STRUCTUREID=?";
        // echo "SQL = " . $sql . "<br>";
        $params = array($this->gestionnaireid,$this->id());
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (STRUCTURE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        
        $sql = "UPDATE STRUCTURE SET RESPONSABLEID=? WHERE STRUCTUREID=?";
        // echo "SQL = " . $sql . "<br>";
        $params = array($this->responsableid,$this->id());
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (STRUCTURE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        return $msgerreur;
    }

    function pdf($mois_annee_debut, $noiretblanc = false,$includeteletravail = false) // Le format doit être MM/YYYY
    {
        // echo "Avant le new PDF <br>";
        $pdf=new FPDF();
        //$pdf = new TCPDF();
        //define('FPDF_FONTPATH','font/');
        //$pdf->Open();
        //$pdf->SetHeaderData('', 0, '', '', array(
        //    0,
        //    0,
        //    0
        //), array(
        //    255,
        //    255,
        //    255
        //));
        $pdf->AddPage('L');
        // echo "Apres le addpage <br>";
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 10, 5, 60, 20);
        $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
        $pdf->SetFont('helvetica', 'B', 15, '', true);
        $pdf->Ln(15);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $this->nomlong() . ' (' . $this->nomcourt() . ')'));
        $pdf->Ln(10);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Planning du mois de : ' . $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . substr($mois_annee_debut, 3)));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 11, '', true);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Edité le ' . date("d/m/Y")));
        $pdf->Ln(10);
        
        // echo "Avant le planning <br>";
        $planningservice = $this->planning($mois_annee_debut, $mois_annee_debut,false,$includeteletravail);
        
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        if (($annee . $indexmois <= date('Ym')) and ($noiretblanc == true)) {
            $pdf->SetTextColor(204, 0, 0);
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode("Attention : Les informations antérieures à la date du jour ont été masquées."));
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(10);
        }

        // On charge toutes les absences dans un tableau
        $listecateg = $this->fonctions->listecategorieabsence();
        $listeabs = array();
        foreach ($listecateg as $keycateg => $nomcateg)
        {
            $listeabs = array_merge((array)$this->fonctions->listeabsence($keycateg),$listeabs);
        }
        
        // ///création du planning suivant le tableau généré
        // /Création des entetes de colones contenant les 31 jours/////
        $titre_a_ajouter = TRUE;
        $elementlegende = array();
        foreach ($planningservice as $agentid => $planning) {
            if ($titre_a_ajouter) 
            {
                $pdf->SetFont('helvetica', 'B', 8, '', true);
                $pdf->Cell(60, 5, $this->fonctions->utf8_decode(""), 1, 0, 'C');
                for ($index = 1; $index <= count($planningservice[$agentid]->planning()) / 2; $index ++) {
                    $pdf->Cell(6, 5, $this->fonctions->utf8_decode($index), 1, 0, 'C');
                }
                $pdf->Ln(5);
                $pdf->Cell(60, 5, $this->fonctions->utf8_decode(""), 1, 0, 'C');
                for ($index = 1; $index <= count($planningservice[$agentid]->planning()) / 2; $index ++) {
                    $pdf->Cell(6, 5, $this->fonctions->utf8_decode(substr($this->fonctions->nomjour(str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut), 0, 2)), 1, 0, 'C');
                }
                $titre_a_ajouter = FALSE;
            }
            
            // echo "Je charge l'agent $agentid <br>";
            $agent = new agent($this->dbconnect);
            $agent->load($agentid);
            // echo "l'agent $agentid est chargé ... <br>";
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
//            $pdf->Cell(60, 5, $this->fonctions->utf8_decode($agent->nom() . " " . $agent->prenom()), 1, 0, 'C');
            $pdf->Cell(60, 5, $this->fonctions->utf8_decode($agent->identitecomplete()), 1, 0, 'C');
            // echo "Avant chargement des elements <br>";
            $listeelement = $planning->planning();
            // echo "Apres chargement des elements <br>";
            foreach ($listeelement as $keyelement => $element) 
            {
                list ($col_part1, $col_part2, $col_part3) = $this->fonctions->html2rgb($element->couleur($noiretblanc));
                $pdf->SetFillColor($col_part1, $col_part2, $col_part3);
                if (strcasecmp($element->moment(), fonctions::MOMENT_MATIN) != 0)
                    $pdf->Cell(3, 5, $this->fonctions->utf8_decode(""), 'TBR', 0, 'C', 1);
                else
                    $pdf->Cell(3, 5, $this->fonctions->utf8_decode(""), 'TBL', 0, 'C', 1);

                if (!in_array($element->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_WE, planningelement::COULEUR_VIDE)))
                {
                    if (array_key_exists($element->type(),$listeabs))
                    {
                        // Si c'est une absence dans la catégorie "télétravail hors convention"
                        if (strcmp($element->parenttype(),'teletravHC')==0)
                        {
                            $elementlegende[$element->parenttype()] = $element->parenttype();
                        }
                        else // C'est une absence d'un autre type => Donc de type absence
                        {
                            //echo "Le type de l'élément = " . $element->type() . "<br>";
                            $elementlegende['abs'] = 'abs';
                        }
                    }
                    else
                    {
                        $elementlegende[$element->type()] = $element->type();
                    }
                }
            }
        }
        
        // ///MISE EN PLACE DES LEGENDES DU PLANNING
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 7, '', true);
        $pdf->SetTextColor(0);
        // ////Mise en place de la légende couleurs pour les congés
        
        // echo "Avant legende <br>";
        if ($noiretblanc == false)
        {
            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période
                $annee = $annee - 1;
            }
            $this->fonctions->legendepdf($pdf,$annee,$includeteletravail,$elementlegende);
        }
        // echo "Apres legende <br>";
        
        $pdf->Ln(8);
        ob_end_clean();
        $pdf->Output("","planning_structure.pdf");
        // $pdf->Output('demande_pdf/autodeclaration_num'.$ID_AUTODECLARATION.'.pdf');
    }

    function getdelegation(&$delegationuserid, &$datedebutdeleg, &$datefindeleg)
    {
        $delegationuserid = "";
        $datedebutdeleg = "";
        $datefindeleg = "";
        
        $sql = "SELECT IDDELEG,DATEDEBUTDELEG,DATEFINDELEG FROM STRUCTURE WHERE STRUCTUREID = ? AND IDDELEG <> ''";
        $params = array($this->id());
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->getdelegation : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) > 0) {
            $result = mysqli_fetch_row($query);
            $delegationuserid = "$result[0]";
            if ("$result[1]" != "") {
                $datedebutdeleg = $this->fonctions->formatdate("$result[1]");
            }
            if ("$result[2]" != "") {
                $datefindeleg = $this->fonctions->formatdate("$result[2]");
            }
        }
    }

    function setdelegation($delegationuserid, $datedebutdeleg, $datefindeleg, $idmodifdeleg="")
    {
        if ($datedebutdeleg != "") {
            $datedebutdeleg = $this->fonctions->formatdatedb($datedebutdeleg);
        }
        if ($datefindeleg != "") {
            $datefindeleg = $this->fonctions->formatdatedb($datefindeleg);
        }
        $datemodifdeleg = NULL;
        if ($idmodifdeleg != "") {
        	$datemodifdeleg = $this->fonctions->formatdatedb(date("d/m/Y"));
        }
        $sql = "UPDATE STRUCTURE 
                SET IDDELEG=?, 
                    DATEDEBUTDELEG=?,
                    DATEFINDELEG=?,
                    DATEMODIFDELEG=?,
                    IDMODIFDELEG=?  
                WHERE STRUCTUREID=?";
        // echo "SQL = " . $sql . "<br>";
        $params = array($delegationuserid,$datedebutdeleg,$datefindeleg,$datemodifdeleg,$idmodifdeleg,$this->id());
        $query = $this->fonctions->prepared_query($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        $msgerreur = '';
        if ($erreur != "") {
            $errlog = "Structure->setdelegation : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        $this->delegueid = $delegationuserid;
        return $msgerreur;
    }
        
    function teletravailpdf($datedebut, $datefin, $savepdf = false)
    {
        $agenttrouve = false;
        
        $tableaudate = array();
        $datefininterval = $this->fonctions->formatdatedb($datefin);
        $datedebutinterval = $this->fonctions->formatdatedb($datedebut);
        $month = substr($datedebutinterval,4,2);
        $year = substr($datedebutinterval,0,4);
        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("month = " . $month . "  year = " . $year));
        if ($month >= 10)
            $libelle = "4e trimestre ";
        elseif ($month >= 7)
            $libelle = "3e trimestre ";
        elseif ($month >= 4)
            $libelle = "2e trimestre ";
        else
            $libelle = "1er trimestre ";
        $tableaudate[3] = array($datedebutinterval,$datefininterval, $libelle . $year);
        for ($cpt=2; $cpt >= 0 ; $cpt --)
        {
            // Caclul du début du nouvel interval => 3 mois avant
            $result = $this->fonctions->enlevemois($datedebutinterval, 3);
            $datedebutinterval = $result[0] . $result[1] . '01';
            // Caclul de la fin du nouvel interval => 3 mois avant
            $result = $this->fonctions->enlevemois($datefininterval, 3);
            $datefininterval = $result[0] . $result[1] . $this->fonctions->nbr_jours_dans_mois($result[1],$result[0]);
            if ($result[1] >= 10)
                $libelle = "4e trimestre ";
            elseif ($result[1] >= 7)
                $libelle = "3e trimestre ";
            elseif ($result[1] >= 4)
                $libelle = "2e trimestre ";
            else
                $libelle = "1er trimestre ";
            
            $tableaudate[$cpt] = array($datedebutinterval,$datefininterval,$libelle . $result[0]);
        }
        // On trie le tableau dans l'ordre croissant des clés (=> date plus vieille en premier)
        ksort($tableaudate);
        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("tableaudate = " . print_r($tableaudate,true)));
        
        $pdf=new FPDF();
        $pdf->AddPage('L');
        // echo "Apres le addpage <br>";
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 10, 5, 60, 20);
        $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 10, 5, 60, 20);
        $pdf->SetFont('helvetica', 'B', 15, '', true);
        $pdf->Ln(15);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $this->nomlong() . ' (' . $this->nomcourt() . ')'));
        $pdf->Ln(10);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Liste des agents en télétravail pour la période du ' . $this->fonctions->formatdate($tableaudate[0][0]) . " au " . $this->fonctions->formatdate($tableaudate[count($tableaudate)-1][1])));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 11, '', true);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Edité le ' . date("d/m/Y")));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 8, '', true);
        
        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("fin complet = " . $tableaudate[0][1] . "  debut complet = " . $tableaudate[count($tableaudate)-1][0]));
        $agentteletravail = $this->fonctions->listeagentteletravail($tableaudate[0][0],$tableaudate[count($tableaudate)-1][1] );
        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("agentteletravail = " . print_r($agentteletravail,true)));
        $agentliste = array();
        $agentliste = $this->agentlist($tableaudate[0][0],$tableaudate[count($tableaudate)-1][1],'n');
        $includedstructliste = $this->structureinclue();
        foreach ($includedstructliste as $includedstruct)
        {
            $agentliste = array_merge($agentliste, $includedstruct->agentlist($tableaudate[0][0],$tableaudate[count($tableaudate)-1][1],'n'));
        }
        ksort($agentliste);
        
        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("agentliste = " . print_r($agentliste,true)));
        foreach ($agentliste as $agent)
        {
            if (array_search($agent->agentid(),(array)$agentteletravail)!==false)
            {
                if (!$agenttrouve)
                {
                    // C'est le premier agent qu'on trouve ==> On crée l'entête
                    $pdf->Cell(60, 5, $this->fonctions->utf8_decode("Nom de l'agent"), 1, 0, 'C');
                    foreach ($tableaudate as $tabdebutfin)
                    {
                        $pdf->cell(40, 5, $this->fonctions->utf8_decode("$tabdebutfin[2]"), 1, 0, 'C');
                    }
                }
                // L'agent est dans la structure et est en télétravail durant la période
                $pdf->Ln(5);
                $pdf->Cell(60, 5, $this->fonctions->utf8_decode($agent->identitecomplete()), 1, 0, 'C');
                foreach ($tableaudate as $tabdebutfin)
                {
                    //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("debut = " . $tabdebutfin[0] . " fin = " . $tabdebutfin[1]));
                    $nombrejoursteletravail = $agent->nbjoursteletravail($tabdebutfin[0], $tabdebutfin[1]);
                    $pdf->cell(40, 5, $this->fonctions->utf8_decode($nombrejoursteletravail), 1, 0, 'C');
                }
                $agenttrouve = true;
            }
            else
            {
                // On va regarder dans le cas d'un agent hors convention
                $nombrejoursteletravail = array();
                $nombrejoursteletravailcumul = 0;
                foreach ($tableaudate as $tabdebutfin)
                {
                    //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("debut = " . $tabdebutfin[0] . " fin = " . $tabdebutfin[1]));
                    $nombrejrs = $agent->nbjoursteletravail($tabdebutfin[0], $tabdebutfin[1]);
                    $nombrejoursteletravail[] = $nombrejrs;
                    $nombrejoursteletravailcumul = $nombrejoursteletravailcumul + $nombrejrs;
                }
                if ($nombrejoursteletravailcumul > 0)
                {
                    if (!$agenttrouve)
                    {
                        // C'est le premier agent qu'on trouve ==> On crée l'entête
                        $pdf->Cell(60, 5, $this->fonctions->utf8_decode("Nom de l'agent"), 1, 0, 'C');
                        foreach ($tableaudate as $tabdebutfin)
                        {
                            $pdf->cell(40, 5, $this->fonctions->utf8_decode("$tabdebutfin[2]"), 1, 0, 'C');
                        }
                    }
                    // L'agent est dans la structure et est en télétravail durant la période
                    $pdf->Ln(5);
                    $pdf->Cell(60, 5, $this->fonctions->utf8_decode($agent->identitecomplete()), 1, 0, 'C');
                    foreach ($nombrejoursteletravail as $nombrejrs)
                    {
                        //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("debut = " . $tabdebutfin[0] . " fin = " . $tabdebutfin[1]));
                        $pdf->cell(40, 5, $this->fonctions->utf8_decode($nombrejrs), 1, 0, 'C');
                    }
                    $agenttrouve = true;
                    
                }
            }
            
        }
        if (!$agenttrouve)
        {
            $pdf->Cell(60, 5, $this->fonctions->utf8_decode("Aucun agent n'est en télétravail durant cette période."));
        }
        $responsable = $this->responsable();
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 8, '', true);
        $pdf->Cell(60, 5, $this->fonctions->utf8_decode("Signature du responsable : " . $responsable->identitecomplete()));
        
        $pdf->Ln(8);
        
        if ($savepdf==true)
        {
            $pdfname = $this->fonctions->pdfpath() . '/teletravail/' . $this->nomcourt() . '_' . date("YmdHis") . '.pdf';
            $this->fonctions->savepdf($pdf, $pdfname);
            return $pdfname;
        }
        else
        {
            ob_end_clean();
            $pdf->Output("","agent_teletravail.pdf");
        }
    }
    
    function structureenglobante()
    {
        if (!$this->isincluded()) // Si la structure courante n'est pas inclue ==> On s'arrète
        {
            return $this;
        }
        else
        {
            $structureparent = $this->parentstructure();
            while ($structureparent->isincluded())
            {
                // La structure courante est incluse dans celle du dessus donc on récupère la structure parente
                $structureparent = $structureparent->parentstructure();
            }
            $errlog = "La structure incluante de " . $this->nomlong() . " (" . $this->nomcourt() . " - " . $this->structureid . ") est " . $structureparent->nomlong() . " (" . $structureparent->nomcourt() . " - " . $structureparent->structureid . ")";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $structureparent;
        }
    }
    
    function structureinclue()
    {
        $structureliste = array();
        if (! is_null($this->structureid)) {
            $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT=? AND ISINCLUDED <> 0";
            $params = array($this->structureid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->structureinclue : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->structureinclue : La structure $this->structureid n'a pas de structure inclue<br>";
            }
            while ($result = mysqli_fetch_row($query)) {
                $structure = new structure($this->dbconnect);
                $structure->load("$result[0]");
                $structureliste[$structure->id()] = $structure;
                // On fait le parcours récursif pour remonter toutes les structures filles 
                $structureliste = array_merge($structureliste, (array) $structure->structureinclue());
                unset($structure);
            }
            return $structureliste;
        }
    }
    
    function listedemandeteletravailenattente()
    {
        $tabteletravail = array();
        if (! is_null($this->structureid)) 
        {
            $sql = "SELECT TELETRAVAIL.TELETRAVAILID 
                    FROM TELETRAVAIL,AGENT,STRUCTURE
                    WHERE STRUCTURE.STRUCTUREID = ?
                      AND AGENT.STRUCTUREID = STRUCTURE.STRUCTUREID
                      AND TELETRAVAIL.AGENTID = AGENT.AGENTID
                      AND TELETRAVAIL.STATUTRESPONSABLE = ?
                      AND TELETRAVAIL.STATUT = ?";
            $params = array($this->structureid,teletravail::TELETRAVAIL_ATTENTE,teletravail::TELETRAVAIL_ATTENTE);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->listedemandeteletravailenattente : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->listedemandeteletravailenattente : Pas de demande de télétravail en attente pour la structure " . $this->structureid . "<br>";
            }
            while ($result = mysqli_fetch_row($query)) 
            {
                $teletravail = new teletravail($this->dbconnect);
                $teletravail->load($result[0]);
                if ($teletravail->agentid()!=$this->responsable()->agentid())
                {
                    $tabteletravail["" . $result[0]] = $teletravail;
                }
                unset($teletravail);
            }
            $tabsousstruct = $this->structurefille();
            if (!is_null($tabsousstruct))
            {
                foreach ($tabsousstruct as $stucture)
                {
                    $sql = "SELECT TELETRAVAIL.TELETRAVAILID 
                            FROM TELETRAVAIL
                            WHERE TELETRAVAIL.AGENTID = ?
                              AND TELETRAVAIL.STATUTRESPONSABLE = ?
                              AND TELETRAVAIL.STATUT = ?";
                    $params = array($stucture->responsable()->agentid(),teletravail::TELETRAVAIL_ATTENTE,teletravail::TELETRAVAIL_ATTENTE);
                    $query = $this->fonctions->prepared_select($sql, $params);
                    $erreur = mysqli_error($this->dbconnect);
                    if ($erreur != "") {
                        $errlog = "Structure (fille) ->listedemandeteletravailenattente : " . $erreur;
                        echo $errlog . "<br/>";
                        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    }
                    if (mysqli_num_rows($query) == 0) {
                        // echo "Structure->listedemandeteletravailenattente : Pas de demande de télétravail en attente pour la structure " . $this->structureid . "<br>";
                    }
                    while ($result = mysqli_fetch_row($query)) 
                    {
                        $teletravail = new teletravail($this->dbconnect);
                        $teletravail->load($result[0]);
                        $tabteletravail["" . $result[0]] = $teletravail;
                        unset($teletravail);
                    }
                }
            }
            return $tabteletravail;
        }
       
    }
}

?>