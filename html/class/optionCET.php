<?php
class optionCET
{
    
/*    
 * Script de création de la table optioncet
 * 
    CREATE TABLE `OPTIONCET` (
        `OPTIONID` int(11) NOT NULL AUTO_INCREMENT,
        `HARPEGEID` varchar(10) NOT NULL,
        `DATECREATION` datetime NOT NULL,
        `ESIGNATUREID` varchar(30) DEFAULT NULL,
        `ESIGNATUREURL` varchar(200) DEFAULT NULL,
        `ANNEEREF` varchar(10) NOT NULL,
        `VALEUR_A` decimal(5,2) NOT NULL,
        `VALEUR_G` decimal(5,2) NOT NULL,
        `VALEUR_H` decimal(5,2) NOT NULL,
        `VALEUR_I` decimal(5,2) NOT NULL,
        `VALEUR_J` decimal(5,2) NOT NULL,
        `VALEUR_K` decimal(5,2) NOT NULL,
        `VALEUR_L` decimal(5,2) NOT NULL,
        `STATUT` varchar(15) DEFAULT NULL,
        `DATESTATUT` date DEFAULT NULL,
        `MOTIF` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`OPTIONID`)
        );
*/    
    
    
    
    public const STATUT_VALIDE = "Validée";
    public const STATUT_REFUSE = "Refusée";
    public const STATUT_EN_COURS = "En cours";
    public const STATUT_ABANDONNE = "Abandonnée";
    public const STATUT_PREPARE = "Préparée";
    public const STATUT_INCONNU = "Inconnu";
    
    
    private $dbconnect = null;
    private $fonctions = null;
    
    private $optionid = null;
    private $harpegeid = null;
    private $datecreation = null;
    private $esignatureid = null;
    private $esignatureurl = null;
    private $anneeref = null;
    private $valeur_a = null;
    private $valeur_g = null;
    private $valeur_h = null;
    private $valeur_i = null;
    private $valeur_j = null;
    private $valeur_k = null;
    private $valeur_l = null;
    private $statut = null;
    private $datestatut = null;
    private $motif = null;
    
    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "optionCET->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }
    
    function optionid()
    {
        return $this->optionid;
    }
    
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->harpegeid)) {
                $errlog = "optionCET->agentid : Le numéro agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->harpegeid;
        }
        else
        {
            if (!is_null($this->harpegeid))
            {
                $errlog = "optionCET->agentid : Impossible de modifier le numéro de l'agent !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->harpegeid = $agentid;
        }
    }
    
    function datecreation()
    {
        if (!is_null($this->datecreation))
        {
            $info = explode(' ',$this->datecreation);
            //echo "info = " . print_r($info, true) . "<br>";
            return $this->fonctions->formatdate($info[0]) . " " . $info[1];
        }
        else
            return $this->datecreation;
    }
    
    function esignatureid($esignatureid = null)
    {
        if (is_null($esignatureid)) {
            if (is_null($this->esignatureid)) {
                $errlog = "optionCET->esignatureid : L'identifiant eSignature n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->esignatureid;
        }
        else
        {
            if ($this->esignatureid . '' != '')
            {
                $errlog = "optionCET->esignatureid : Impossible de modifier l'identifiant eSignature !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                $this->esignatureid = $esignatureid;
                //echo "esignatureid = " . $this->esignatureid . "<br>";;
            }
        }
    }
    
    function esignatureurl($esignatureurl = null)
    {
        if (is_null($esignatureurl)) {
            if (is_null($this->esignatureurl)) {
                $errlog = "optionCET->esignatureurl : L'URL eSignature n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->esignatureurl;
        }
        else
        {
            if ($this->esignatureurl . '' != '')
            {
                $errlog = "optionCET->esignatureurl : Impossible de modifier l'URL eSignature !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                $this->esignatureurl = $esignatureurl;
                //echo "esignatureurl = " . $this->esignatureurl . "<br>";;
            }
        }
    }
    
    function anneeref($anneeref = null)
    {
        if (is_null($anneeref)) {
            if (is_null($this->anneeref)) {
                $errlog = "optionCET->anneeref : L'année de référence de l'optionCET n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->anneeref;
        }
        else
        {
            if (!is_null($this->anneeref))
            {
                $errlog = "optionCET->anneeref : Impossible de modifier l'année de référence d'une optionCET !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->anneeref = $anneeref;
        }
    }
    
    function valeur_a($valeur_a = null)
    {
        if (is_null($valeur_a)) {
            if (is_null($this->valeur_a)) {
                $errlog = "optionCET->valeur_a : La valeur de la case A n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_a;
        }
        else
        {
            if (!is_null($this->valeur_a))
            {
                $errlog = "optionCET->valeur_a : Impossible de modifier la valeur de la case A !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_a = $valeur_a;
        }
    }

    function valeur_g($valeur_g = null)
    {
        if (is_null($valeur_g)) {
            if (is_null($this->valeur_g)) {
                $errlog = "optionCET->valeur_g : La valeur de la case G n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_g;
        }
        else
        {
            if (!is_null($this->valeur_g))
            {
                $errlog = "optionCET->valeur_g : Impossible de modifier la valeur de la case G !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_g = $valeur_g;
        }
    }
    
    function valeur_h($valeur_h = null)
    {
        if (is_null($valeur_h)) {
            if (is_null($this->valeur_h)) {
                $errlog = "optionCET->valeur_h : La valeur de la case H n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_h;
        }
        else
        {
            if (!is_null($this->valeur_h))
            {
                $errlog = "optionCET->valeur_h : Impossible de modifier la valeur de la case H !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_h = $valeur_h;
        }
    }
    
    function valeur_i($valeur_i = null)
    {
        if (is_null($valeur_i)) {
            if (is_null($this->valeur_i)) {
                $errlog = "optionCET->valeur_i : La valeur de la case I n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_i;
        }
        else
        {
            if (!is_null($this->valeur_i))
            {
                $errlog = "optionCET->valeur_i : Impossible de modifier la valeur de la case I !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_i = $valeur_i;
        }
    }
    
    function valeur_j($valeur_j = null)
    {
        if (is_null($valeur_j)) {
            if (is_null($this->valeur_j)) {
                $errlog = "optionCET->valeur_j : La valeur de la case J n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_j;
        }
        else
        {
            if (!is_null($this->valeur_j))
            {
                $errlog = "optionCET->valeur_j : Impossible de modifier la valeur de la case J !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_j = $valeur_j;
        }
    }
    
    function valeur_k($valeur_k = null)
    {
        if (is_null($valeur_k)) {
            if (is_null($this->valeur_k)) {
                $errlog = "optionCET->valeur_k : La valeur de la case K n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_k;
        }
        else
        {
            if (!is_null($this->valeur_k))
            {
                $errlog = "optionCET->valeur_k : Impossible de modifier la valeur de la case K !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_k = $valeur_k;
        }
    }
    
    function valeur_l($valeur_l = null)
    {
        if (is_null($valeur_l)) {
            if (is_null($this->valeur_l)) {
                $errlog = "optionCET->valeur_l : La valeur de la case L n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_l;
        }
        else
        {
            if (!is_null($this->valeur_l))
            {
                $errlog = "optionCET->valeur_l : Impossible de modifier la valeur de la case L !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_l = $valeur_l;
        }
    }
        
    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "optionCET->statut : La valeur du statut n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->statut;
        }
        else
        {
            if ($this->statut <> $statut)
            {
                $this->statut = $statut;
                $this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
            }
        }
    }
    
    function motif($motif = null)
    {
        if (is_null($motif)) {
            if (is_null($this->motif)) {
                $errlog = "optionCET->motif : La valeur du motif n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->motif;
        }
        else
        {
            $this->motif = $motif;
        }
    }
    
    function datestatut()
    {
        return $this->datestatut;
    }

    function load($esignatureid = null, $optionid = null )
    {
        $errlog = '';
        $sql = "SELECT OPTIONID,HARPEGEID,DATECREATION,ESIGNATUREID,ESIGNATUREURL,ANNEEREF,VALEUR_A,VALEUR_G,VALEUR_H,VALEUR_I,VALEUR_J,VALEUR_K,VALEUR_L,STATUT,DATESTATUT,MOTIF FROM OPTIONCET WHERE ";
        if (!is_null($esignatureid))
        {
            $sql = $sql . "ESIGNATUREID = '" . str_replace("'","''",$esignatureid) . "'";
        }
        elseif (!is_null($optionid))
        {
            $sql = $sql . "OPTIONID = '$optionid'";
        }
        else
        {
            $errlog = "optionCET->Load : Tous les paramètres sont vides !!!";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "optionCET->Load : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        if (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "optionCET->Load : Aucune ligne dans la base de données correspondante";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        $result = mysqli_fetch_row($query);
        $this->optionid   = "$result[0]";
        $this->harpegeid        = "$result[1]";
        $this->datecreation     = "$result[2]";
        $this->esignatureid     = "$result[3]";
        $this->esignatureurl    = "$result[4]";
        $this->anneeref         = "$result[5]";
        $this->valeur_a         = "$result[6]";
        $this->valeur_g         = "$result[7]";
        $this->valeur_h         = "$result[8]";
        $this->valeur_i         = "$result[9]";
        $this->valeur_j         = "$result[10]";
        $this->valeur_k         = "$result[11]";
        $this->valeur_l         = "$result[12]";
        $this->statut           = "$result[13]";
        $this->datestatut       = "$result[14]";
        $this->motif            = "$result[15]";
        
        return $errlog;
    }
    
    function store()
    {
        $erreur = '';
        // Si on est en train de créer une option sur CET
        if (is_null($this->optionid))
        {
            //echo "optionCET->Store : Création d'une nouvelle option CET <br>";
            // On doit vérifier que les éléments olbigatoires sont bien renseignés : HarpegeId, anneeref, valeur_a, valeur_g, valeur_h, valeur_i, valeur_j, valeur_k, valeur_l
            if (is_null($this->harpegeid)
                or is_null($this->anneeref)
                or is_null($this->valeur_a)
                or is_null($this->valeur_g)
                or is_null($this->valeur_h)
                or is_null($this->valeur_i)
                or is_null($this->valeur_j)
                or is_null($this->valeur_k)
                or is_null($this->valeur_l))
            {
                // Au moins un des éléments obligatoires est null => pas de sauvegarde possible
                $erreur = "Au moins un des éléments obligatoires est null => Pas de création possible";
                $errlog = "optionCET->Store : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $erreur;
            }
            
            $this->datecreation = $this->fonctions->formatdatedb(date("d/m/Y"));
            $this->datestatut  = $this->datecreation;
            $sql = "LOCK TABLES OPTIONCET WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO OPTIONCET(HARPEGEID,DATECREATION,ESIGNATUREID,ESIGNATUREURL,ANNEEREF,VALEUR_A,VALEUR_G,VALEUR_H,VALEUR_I,VALEUR_J,VALEUR_K,VALEUR_L,STATUT,DATESTATUT,MOTIF)
                    VALUES('". $this->harpegeid . "',
                           now(),
                           '" . str_replace("'","''",$this->esignatureid) . "',
                           '" . str_replace("'","''",$this->esignatureurl) . "',
                           '" . $this->anneeref . "',
                           '" . $this->valeur_a . "',
                           '" . $this->valeur_g . "',
                           '" . $this->valeur_h . "',
                           '" . $this->valeur_i . "',
                           '" . $this->valeur_j . "',
                           '" . $this->valeur_k . "',
                           '" . $this->valeur_l . "',
                           '" . str_replace("'","''",$this->statut) . "',
                           '" . $this->datestatut . "',
                           '" . str_replace("'","''",$this->motif) . "')";
            //echo "SQL = " . $sql . "<br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "optionCET->Store (INSERT) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $this->optionid = mysqli_insert_id($this->dbconnect);
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
            //echo "optionCET->Store : Mise à jour d'une option CET <br>";
            $sql = "UPDATE OPTIONCET
                    SET ESIGNATUREID = '" . str_replace("'","''",$this->esignatureid) . "',
                        ESIGNATUREURL = '" . str_replace("'","''",$this->esignatureurl) . "',
                        STATUT = '" . str_replace("'","''",$this->statut) . "',
                        DATESTATUT = '" . $this->datestatut . "',
                        MOTIF = '" . str_replace("'", "''", $this->motif) . "'
                    WHERE OPTIONID = '" . $this->optionid . "'";
            //echo "SQL optionCET->Store : $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "optionCET->Store (UPDATE) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        return $erreur;
    }
    
    public function storepdf()
    {
        
        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" On va demander le PDF à eSignature (demande = " .  $this->esignatureid .  ")"));

        $eSignature_url = $this->fonctions->liredbconstante('ESIGNATUREURL');
        $error = '';
        
        // On appelle le WS eSignature pour récupérer le document final
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $eSignature_url . '/ws/signrequests/get-last-file/' . $this->esignatureid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY => ''
        ];
        curl_setopt_array($curl, $opts);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $pdf = curl_exec($curl);
        $error = curl_error ($curl);
        curl_close($curl);
        if ($error != "")
        {
            $error = "Erreur Curl (récup PDF) =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
            //echo "Erreur Curl (récup PDF) =>  " . $error . '<br><br>';
        }
        if (stristr(substr($pdf,0,10),'PDF') === false)
        {
            $error = "Erreur Curl (récup PDF) =>  " . $error;
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }
        //echo "<br>" . print_r($json,true) . "<br>";
        //$response = json_decode($json, true);
        
        $agent = new agent($this->dbconnect);
        $agent->load($this->agentid());
        $basename = "Option_CET_" . $agent->nom() . "_" . $agent->prenom() . "_num_" . $this->esignatureid . ".pdf";
        $pdffilename = $this->fonctions->g2tbasepath() . '/html/pdf/cet/' . $basename;
        //echo "<br>pdffilename = $pdffilename <br><br>";
        
        // création du fichier
        //$pdffilename = '/tmp/mon_fichier_test.pdf';
        $path = dirname("$pdffilename");
        if (!file_exists($path))
        {
            mkdir("$path");
            chmod("$path", 0777);
        }
        
        $f = fopen($pdffilename, "w");
        if ($f === false)
        {
            $error = "Erreur enregistrement : Le fichier $pdffilename n'a pas pu être créé.";
            error_log(basename(__FILE__) . $this->fonctions->stripAccents(" $error"));
            return $error;
        }
        // écriture
        fputs($f, $pdf );
        // fermeture
        fclose($f);
        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" Le PDF est ok (demande = " .  $this->esignatureid .  ")"));
        return '';
        
    }
    
    
}