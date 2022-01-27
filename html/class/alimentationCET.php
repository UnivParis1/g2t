<?php
class alimentationCET
{
    
    public const STATUT_VALIDE = "Validée";
    public const STATUT_REFUSE = "Refusée";
    public const STATUT_EN_COURS = "En cours";
    public const STATUT_ABANDONNE = "Abandonnée";
    public const STATUT_PREPARE = "Préparée";
    public const STATUT_INCONNU = "Inconnu";
    
    
    private $dbconnect = null;
    private $fonctions = null;
    
    private $alimentationid = null;
    private $harpegeid = null;
    private $datecreation = null;
    private $esignatureid = null;
    private $esignatureurl = null;
    private $typeconges = null;
    private $valeur_a = null;
    private $valeur_b = null;
    private $valeur_c = null;
    private $valeur_d = null;
    private $valeur_e = null;
    private $valeur_f = null;
    private $valeur_g = null;
    private $statut = null;
    private $datestatut = null;
    private $motif = null;
    
    
    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "alimentationCET->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }
    
    function alimentationid()
    {
        return $this->alimentationid;
    }
    
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->harpegeid)) {
                $errlog = "alimentationCET->agentid : Le numéro agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->harpegeid;
        } 
        else
        {
            if (!is_null($this->harpegeid))
            {
                $errlog = "alimentationCET->agentid : Impossible de modifier le numéro de l'agent !!!";
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
                $errlog = "alimentationCET->esignatureid : L'identifiant eSignature n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->esignatureid;
        }
        else
        {
            if ($this->esignatureid . '' != '')
            {
                $errlog = "alimentationCET->esignatureid : Impossible de modifier lidentifiant eSignature !!!";
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
                $errlog = "alimentationCET->esignatureurl : L'URL eSignature n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->esignatureurl;
        }
        else
        {
            if ($this->esignatureurl . '' != '')
            {
                $errlog = "alimentationCET->esignatureurl : Impossible de modifier l'URL eSignature !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                // On remplace éventuellement le nom du serveur par celui paramétré
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (esignatureurl) => Avant transformation l'URL est : " . $esignatureurl));
                $eSignature_url = $this->fonctions->liredbconstante('ESIGNATUREURL');
                $urlpath = parse_url($esignatureurl,PHP_URL_PATH);
                $this->esignatureurl = $eSignature_url . $urlpath;
                error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (esignatureurl) => Après transformation l'URL est : " . $this->esignatureurl));
                
                //$this->esignatureurl = $esignatureurl;
                //echo "esignatureurl = " . $this->esignatureurl . "<br>";;
            }
        }
    }
     
    function typeconges($typeconges = null)
    {
        if (is_null($typeconges)) {
            if (is_null($this->typeconges)) {
                $errlog = "alimentationCET->typeconges : La valeur du type de conges n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->typeconges;
        }
        else
        {
            if (!is_null($this->typeconges))
            {
                $errlog = "alimentationCET->typeconges : Impossible de modifier la valeur du type de congés !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->typeconges = $typeconges;
        }
    }
    
    function valeur_a($valeur_a = null)
    {
        if (is_null($valeur_a)) {
            if (is_null($this->valeur_a)) {
                $errlog = "alimentationCET->valeur_a : La valeur de la case A n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_a;
        }
        else
        {
            if (!is_null($this->valeur_a))
            {
                $errlog = "alimentationCET->valeur_a : Impossible de modifier la valeur de la case A !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_a = $valeur_a;
        }
    }
    
    function valeur_b($valeur_b = null)
    {
        if (is_null($valeur_b)) {
            if (is_null($this->valeur_b)) {
                $errlog = "alimentationCET->valeur_b : La valeur de la case B n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_b;
        }
        else
        {
            if (!is_null($this->valeur_b))
            {
                $errlog = "alimentationCET->valeur_b : Impossible de modifier la valeur de la case B !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_b = $valeur_b;
        }
    }
    
    function valeur_c($valeur_c = null)
    {
        if (is_null($valeur_c)) {
            if (is_null($this->valeur_c)) {
                $errlog = "alimentationCET->valeur_c : La valeur de la case C n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_c;
        }
        else
        {
            if (!is_null($this->valeur_c))
            {
                $errlog = "alimentationCET->valeur_c : Impossible de modifier la valeur de la case C !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_c = $valeur_c;
        }
    }
    
    function valeur_d($valeur_d = null)
    {
        if (is_null($valeur_d)) {
            if (is_null($this->valeur_d)) {
                $errlog = "alimentationCET->valeur_d : La valeur de la case D n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_d;
        }
        else
        {
            if (!is_null($this->valeur_d))
            {
                $errlog = "alimentationCET->valeur_d : Impossible de modifier la valeur de la case D !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_d = $valeur_d;
        }
    }
    
    function valeur_e($valeur_e = null)
    {
        if (is_null($valeur_e)) {
            if (is_null($this->valeur_e)) {
                $errlog = "alimentationCET->valeur_e : La valeur de la case E n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_e;
        }
        else
        {
            if (!is_null($this->valeur_e))
            {
                $errlog = "alimentationCET->valeur_e : Impossible de modifier la valeur de la case E !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_e = $valeur_e;
        }
    }
    
    function valeur_f($valeur_f = null)
    {
        if (is_null($valeur_f)) {
            if (is_null($this->valeur_f)) {
                $errlog = "alimentationCET->valeur_f : La valeur de la case F n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_f;
        }
        else
        {
            if (!is_null($this->valeur_f))
            {
                $errlog = "alimentationCET->valeur_f : Impossible de modifier la valeur de la case F !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_f = $valeur_f;
        }
    }
    
    function valeur_g($valeur_g = null)
    {
        if (is_null($valeur_g)) {
            if (is_null($this->valeur_g)) {
                $errlog = "alimentationCET->valeur_g : La valeur de la case G n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->valeur_g;
        }
        else
        {
            if (!is_null($this->valeur_g))
            {
                $errlog = "alimentationCET->valeur_g : Impossible de modifier la valeur de la case G !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
                $this->valeur_g = $valeur_g;
        }
    }
    
    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "alimentationCET->statut : La valeur du statut n'est pas défini !!!";
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
                $errlog = "alimentationCET->motif : La valeur du motif n'est pas défini !!!";
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
    
    
    function load($esignatureid = null, $alimentationid = null )
    {
        $errlog = '';
        $sql = "SELECT ALIMENTATIONID,HARPEGEID,DATECREATION,ESIGNATUREID,ESIGNATUREURL,TYPECONGES,VALEUR_A,VALEUR_B,VALEUR_C,VALEUR_D,VALEUR_E,VALEUR_F,VALEUR_G,STATUT,DATESTATUT,MOTIF FROM ALIMENTATIONCET WHERE ";
        if (!is_null($esignatureid))
        {
            $sql = $sql . "ESIGNATUREID = '" . str_replace("'","''",$esignatureid) . "'";
        }
        elseif (!is_null($alimentationid))
        {
            $sql = $sql . "ALIMENTATIONID = '$alimentationid'";
        }
        else
        {
            $errlog = "alimentationCET->Load : Tous les paramètres sont vides !!!";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "")
        {
            $errlog = "alimentationCET->Load : " . $erreur;
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        if (mysqli_num_rows($query) == 0)
        {
            //echo "<br>load => pas de ligne dans la base de données<br>";
            $errlog = "alimentationCET->Load : Aucune ligne dans la base de données correspondante";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog;
        }
        
        $result = mysqli_fetch_row($query);
        $this->alimentationid   = "$result[0]";
        $this->harpegeid        = "$result[1]";
        $this->datecreation     = "$result[2]";
        $this->esignatureid     = "$result[3]";
        $this->esignatureurl    = "$result[4]";
        $this->typeconges       = "$result[5]";
        $this->valeur_a         = "$result[6]";
        $this->valeur_b         = "$result[7]";
        $this->valeur_c         = "$result[8]";
        $this->valeur_d         = "$result[9]";
        $this->valeur_e         = "$result[10]";
        $this->valeur_f         = "$result[11]";
        $this->valeur_g         = "$result[12]";
        $this->statut           = "$result[13]";
        $this->datestatut       = "$result[14]";
        $this->motif            = "$result[15]";
        
        // On remplace éventuellement le nom du serveur par celui paramétré
        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (load) => Avant transformation l'URL est : " . $this->esignatureurl));
        $eSignature_url = $this->fonctions->liredbconstante('ESIGNATUREURL');
        $urlpath = parse_url($this->esignatureurl,PHP_URL_PATH);
        $this->esignatureurl = $eSignature_url . $urlpath;
        error_log(basename(__FILE__) . $this->fonctions->stripAccents(" (load) => Après transformation l'URL est : " . $this->esignatureurl));
        
        
        return $errlog;
    }

    function store()
    {
        $erreur = '';
        // Si on est en train de créer une demande d'alimentation
        if (is_null($this->alimentationid))
        {
            //echo "alimentationCET->Store : Création d'une nouvelle alimentation <br>";
            // On doit vérifier que les éléments olbigatoires sont bien renseignés : HarpegeId, typeconges, valeur_a, valeur_b, valeur_c, valeur_d, valeur_e,, valeur_f, valeur_g
            if (is_null($this->harpegeid) 
             or is_null($this->typeconges) 
             or is_null($this->valeur_a) 
             or is_null($this->valeur_b) 
             or is_null($this->valeur_c) 
             or is_null($this->valeur_d) 
             or is_null($this->valeur_e) 
             or is_null($this->valeur_f)
             or is_null($this->valeur_g))
            {
                // Au moins un des éléments obligatoires est null => pas de sauvegarde possible
                $erreur = "Au moins un des éléments obligatoires est null => Pas de création possible";
                $errlog = "alimentationCET->Store : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $erreur;
            }

            $this->datecreation = $this->fonctions->formatdatedb(date("d/m/Y"));
            $this->datestatut  = $this->datecreation;
            $sql = "LOCK TABLES ALIMENTATIONCET WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO ALIMENTATIONCET(HARPEGEID,DATECREATION,ESIGNATUREID,ESIGNATUREURL,TYPECONGES,VALEUR_A,VALEUR_B,VALEUR_C,VALEUR_D,VALEUR_E,VALEUR_F,VALEUR_G,STATUT,DATESTATUT,MOTIF) 
                    VALUES('". $this->harpegeid . "',
                           now(),
                           '" . str_replace("'","''",$this->esignatureid) . "',
                           '" . str_replace("'","''",$this->esignatureurl) . "',
                           '" . $this->typeconges . "',
                           '" . $this->valeur_a . "',
                           '" . $this->valeur_b . "',
                           '" . $this->valeur_c . "',
                           '" . $this->valeur_d . "',
                           '" . $this->valeur_e . "',
                           '" . $this->valeur_f . "',
                           '" . $this->valeur_g . "',
                           '" . str_replace("'","''",$this->statut) . "',
                           '" . $this->datestatut . "',
                           '" . str_replace("'","''",$this->motif) . "')";
            //echo "SQL = " . $sql . "<br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "alimentationCET->Store (INSERT) : " . $erreur;
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $this->alimentationid = mysqli_insert_id($this->dbconnect);
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
            //echo "alimentationCET->Store : Mise à jour d'une nouvelle alimentation <br>";
            $sql = "UPDATE ALIMENTATIONCET 
                    SET ESIGNATUREID = '" . str_replace("'","''",$this->esignatureid) . "',
                        ESIGNATUREURL = '" . str_replace("'","''",$this->esignatureurl) . "',
                        STATUT = '" . str_replace("'","''",$this->statut) . "',
                        DATESTATUT = '" . $this->datestatut . "',
                        MOTIF = '" . str_replace("'", "''", $this->motif) . "'
                    WHERE ALIMENTATIONID = '" . $this->alimentationid . "'";
            //echo "SQL alimentationCET->Store : $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "alimentationCET->Store (UPDATE) : " . $erreur;
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
        $basename = str_replace(' ', '_', "Alimentation_CET_" . $agent->nom() . "_" . $agent->prenom() . "_num_" . $this->esignatureid . ".pdf");
        $pdffilename = $this->fonctions->pdfpath() . '/cet/' . $basename;
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
?>