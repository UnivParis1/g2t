<?php

use Fpdf\Fpdf as FPDF;
    
class declarationTP
{

    public const DECLARATIONTP_VALIDE = "v";
    public const DECLARATIONTP_REFUSE = "r";
    public const DECLARATIONTP_ATTENTE = "a";
    
    private $declarationid = null;

    private $affectationid = null;
    
    private $agentid = null;
    
    private $numlignequotite = null;

    private $tabtpspartiel = null;

    private $datedemande = null;

    private $datedebut = null;

    private $datefin = null;

    private $datestatut = null;

    private $statut = null;

    private $fonctions = null;

    private $dbconnect = null;

    private $agent = null;

    private $ancienfin = null;

    private $anciendebut = null;
    
    private $forcee = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "DeclarationTP->construct : La connexion à la base de données est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($id = null)
    {
        if (is_null($id)) {
            $errlog = "DeclarationTP->Load : l'identifiant de la déclarationTP est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $sql = "SELECT DECLARATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT,AGENTID,FORCEE
                    FROM DECLARATIONTP
                    WHERE DECLARATIONID=?";
            $params = array($id);
            $query = $this->fonctions->prepared_select($sql, $params);

            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "DeclarationTP->Load : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "DeclarationTP->Load : DeclarationTP $id non trouve";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $this->declarationid = "$result[0]";
            $this->affectationid = "";
            $this->tabtpspartiel = "$result[1]";
            $this->datedemande = "$result[2]";
            $this->datedebut = "$result[3]";
            $this->datefin = "$result[4]";
            $this->datestatut = "$result[5]";
            $this->statut = "$result[6]";
            $this->agentid = "$result[7]";
            $this->forcee = "$result[8]";
            if ($this->forcee == '')
            {
                $this->forcee = 'N';
            }
            //echo "this->agentid = " . $this->agentid . " \n";
        }
    }

    function declarationTPid()
    {
        if (is_null($this->declarationid)) {
            $errlog = "DeclarationTP->id : L'Id n'est pas défini !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else
            return $this->declarationid;
    }

    /**
     * @deprecated
     */
    function affectationid($affectationid = null)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        
        if (is_null($affectationid)) {
            if (is_null($this->affectationid)) {
                $errlog = "DeclarationTP->affectationid : L'Id de l'affectation n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affectationid;
        } else
            $this->affectationid = $affectationid;
    }

    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            //echo "declarationTP->agentid retourne : " . $this->agentid . "\n";
            if (is_null($this->agentid)) {
                $errlog = "DeclarationTP->agentid : Le agentid n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->agentid;
        } else
            $this->agentid = $agentid;
    }
    
    function numlignequotite($numlignequotite = null)
    {
        if (is_null($numlignequotite)) {
            if (is_null($this->numlignequotite)) {
                $errlog = "DeclarationTP->numlignequotite : Le numlignequotite n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->numlignequotite;
        } else
            $this->numlignequotite = $numlignequotite;
    }
    
    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "DeclarationTP->statut : Le statut n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->statut;
        } else
            $this->statut = $statut;
    }

    function datedebut($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datedebut)) {
                $errlog = "DeclarationTP->datedebut : La date de début n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datedebut);
        } else {
            if (is_null($this->anciendebut))
                $this->anciendebut = $this->datedebut;
            $this->datedebut = $this->fonctions->formatdatedb($date);
        }
    }

    function datefin($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datefin)) {
                $errlog = "DeclarationTP->datefin : La date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datefin);
        } else {
            if (is_null($this->ancienfin))
                $this->ancienfin = $this->datefin;
            $this->datefin = $this->fonctions->formatdatedb($date);
        }
    }

    function datedemande($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datedemande)) {
                $errlog = "DeclarationTP->datedemande : La date de la demande n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datedemande);
        } else
            $this->datedemande = $this->fonctions->formatdatedb($date);
    }

    function datestatut($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datestatut)) {
                $errlog = "DeclarationTP->datestatut : La date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datestatut);
        } else
            $this->datestatut = $this->fonctions->formatdatedb($date);
    }

    function tabtpspartiel($tableauTP = null)
    {
        if (is_null($tableauTP)) {
            if (is_null($this->tabtpspartiel)) {
                $errlog = "DeclarationTP->tabtpspartiel : Le tableau des temps partiels n'est pas défini (NULL) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->tabtpspartiel;
        } else {
            $this->tabtpspartiel = $tableauTP;
        }
        // echo "DeclarationTP->initTP : "; print_r($this->tabrtt); echo "<br>";
    }

    function tabtpspartielhtml($pour_modif = false)
    {
        $htmltext = "";
        $htmltext = $htmltext . "<tr class='entete'>";
        $htmltext = $htmltext . "<td></td>";
        for ($indexjrs = 1; $indexjrs < 6; $indexjrs ++) {
            // echo "indexjrs = $indexjrs <br>";
            $htmltext = $htmltext . "<td colspan='2' class='widthtd50'>" . $this->fonctions->nomjourparindex($indexjrs) . "</td>";
        }
        $htmltext = $htmltext . "</tr>";
        $checkboxname = null;
        for ($semaine = 0; $semaine < 2; $semaine ++) {
            $htmltext = $htmltext . "<tr class='ligneplanning'><td>Semaine ";
            if ($semaine == 0)
                $htmltext = $htmltext . "paire</td>";
            else
                $htmltext = $htmltext . "impaire</td>";
            
            for ($indexelement = 0; $indexelement < 10; $indexelement ++) {
                unset($element);
                $element = new planningelement($this->dbconnect);
                if ($indexelement % 2 == 0)
                    $element->moment(fonctions::MOMENT_MATIN);
                else
                    $element->moment(fonctions::MOMENT_APRESMIDI);
                if ($pour_modif)
                    $checkboxname = $indexelement + ($semaine * 10); // $this->fonctions->nomjourparindex(((int)($indexelement/2))+1) . "_" . $element->moment() . "_" . $semaine;
                if (substr($this->tabtpspartiel(), $indexelement + ($semaine * 10), 1) == 1) {
                    $element->type("tppar");
                    $element->info("Temps partiel");
                } else {
                    $element->type("");
                    $element->info("");
                }
                //echo "<b><br>Avant le element->html<br><br></b>";
                $htmltext = $htmltext . $element->html(false, $checkboxname);
                //echo "<b><br>Apres le element->html<br><br></b>";
                unset($element);
            }
            $htmltext = $htmltext . "</tr>";
        }
        return $htmltext;
    }
    
    function forcee($forced = null)
    {
        if (is_null($forced))
        {
            if (!is_null($this->forcee))
            {
                return $this->forcee;
            }
            else
            {
                $errlog = "DeclarationTP->forcee : Le forçage de la déclaration n'est pas défini (NULL) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        else
        {
            if (strcasecmp($forced, 'N')==0 or strcasecmp($forced, 'O')==0) //Si c'est O ou N (case insensitive)
            {
                $this->forcee = strtoupper($forced);
            }
            else
            {
                $errlog = "DeclarationTP->forcee : La valeur passée pour le forçage est inconnue => $forced !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function enTP($date = null, $moment = null)
    {
        if (is_null($date) or is_null($moment)) {
            $errlog = "DeclarationTP->enTP : Au moins un des paramètres n'est pas défini (NULL) !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } elseif (is_null($this->tabtpspartiel)) {
            $errlog = "DeclarationTP->enTP : Le tableau des TP n'est pas initialisé !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (strlen($this->tabtpspartiel) < 20) {
            $errlog = "DeclarationTP->enTP : Le tableau ne contient pas le nombre d'élément requis !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        $datedb = $this->fonctions->formatdatedb($date);
        // recupération du numéro du jour ==> 0 dimanche ... 6 Samedi
        $numerojour = date("w", strtotime($datedb));
        if ($numerojour == 0) {
            // On force le dimanche a 7
            $numerojour = 7;
        }
        // echo "Numero jour = $numerojour <br>";
        if ($numerojour >= 6) {
            // echo "Samedi ou Dimanche => Donc pas de TP <br>";
            return false;
        }
        // recupération du numéro de la semaine
        $numsemaine = date("W", strtotime($datedb));
        // echo "Numero de la semaine = $numsemaine <br>";
        $semainepaire = ! (bool) ($numsemaine % 2);
        if ($semainepaire) {
            // echo "Semaine paire <br>";
            $semaineindex = 0;
        } else {
            // echo "Semaine impaire <br>";
            $semaineindex = 1;
        }
        
        if (strcasecmp($moment, fonctions::MOMENT_MATIN) == 0)
            $momentindex = 0;
        else
            $momentindex = 1;
        
        $index = (($numerojour - 1) * 2) + ($momentindex) + (10 * $semaineindex);
        // echo "date = $date moment = $moment index = $index this->tabtpspartiel = " . $this->tabtpspartiel . "<br>";
        // echo "Le caractère= " . substr($this->tabtpspartiel, $index,1) . "<br>";
        if (substr($this->tabtpspartiel, $index, 1) == "1") {
            // echo "Je return TRUE <br>";
            return true;
        } else {
            // echo "Je return FALSE <br>";
            return false;
        }
    }

    function enTPindexjour($indexjour, $moment, $semainepaire = true)
    {
        if (is_null($this->tabtpspartiel)) 
        {
            $errlog = "DeclarationTP->enTP : Le tableau des TP n'est pas initialisé !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        elseif (strlen($this->tabtpspartiel) < 20) 
        {
            $errlog = "DeclarationTP->enTP : Le tableau ne contient pas le nombre d'élément requis !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        // numéro du jour ==> 0 dimanche ... 6 Samedi
        if ($indexjour == 0) 
        {
            // On force le dimanche a 7
            $indexjour = 7;
        }
        // echo "Numero jour = $numerojour <br>";
        if ($indexjour >= 6) 
        {
            // echo "Samedi ou Dimanche => Donc pas de TP <br>";
            return false;
        }
        if ($semainepaire) 
        {
            // echo "Semaine paire <br>";
            $semaineindex = 0;
        } 
        else 
        {
            // echo "Semaine impaire <br>";
            $semaineindex = 1;
        }
        
        if (strcasecmp($moment, fonctions::MOMENT_MATIN) == 0)
            $momentindex = 0;
        else
            $momentindex = 1;
                
        $index = (($indexjour - 1) * 2) + ($momentindex) + (10 * $semaineindex);
        // echo "date = $date moment = $moment index = $index this->tabtpspartiel = " . $this->tabtpspartiel . "<br>";
        // echo "Le caractère= " . substr($this->tabtpspartiel, $index,1) . "<br>";
        if (substr($this->tabtpspartiel, $index, 1) == "1") 
        {
            // echo "Je return TRUE <br>";
            return true;
        } 
        else 
        {
            // echo "Je return FALSE <br>";
            return false;
        }
    }
    
    
    function agent()
    {
        if (is_null($this->agent))
        {
            $agent = new agent($this->dbconnect);
            $agent->load($this->agentid);
            $this->agent = $agent;
        }
        return $this->agent;
    }

    function store()
    {
        
        // echo "DeclarationTP->store : non refaite !!!! <br>";
        // return false;
        $errlog = '';
        // echo "On teste le nbre de tabrtt = " . count($this->tabrtt) . "<br>";
        if (strlen($this->tabtpspartiel) != 20) {
            $errlog = "Le tableau des temps partiels n'est pas initialisé. L'enregistrement est impossible.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog . "<br/>";
        }
        if (is_null($this->forcee))
        {
            $this->forcee = 'N';
        }
        
        // echo "id est null ==> " . $this->id . "<br>";
        if (is_null($this->declarationid)) {
            $this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));
            
            $sql = "LOCK TABLES DECLARATIONTP WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO DECLARATIONTP (AGENTID,NUMLIGNEQUOTITE,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT,FORCEE) ";
            $sql = $sql . " VALUES ('" . $this->agentid . "','" . $this->numlignequotite . "','" . $this->tabtpspartiel . "',";
            $sql = $sql . "'" . $this->datedemande . "','" . $this->fonctions->formatdatedb($this->datedebut) . "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->datedemande . "','" . $this->statut . "','" . $this->forcee . "')";
            // echo "SQL = $sql <br>";
            $params = array();
            $query = $this->fonctions->prepared_query($sql, $params);

            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "")
            {
                $errlog = "DeclarationTP->store (new) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            // $sql = "SELECT LAST_INSERT_ID()";
            // $this->id = mysqli_query($this->dbconnect, $sql);
            $this->declarationid = mysqli_insert_id($this->dbconnect);
            $sql = "COMMIT";
            mysqli_query($this->dbconnect, $sql);
            $sql = "UNLOCK TABLES";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 1";
            mysqli_query($this->dbconnect, $sql);
        } else {
            $this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
            // c'est une modification ...
            $sql = "UPDATE DECLARATIONTP SET ";
            $sql = $sql . " STATUT='" . $this->statut . "', DATEDEBUT='" . $this->datedebut . "', DATEFIN='" . $this->datefin . "', DATESTATUT='" . $this->datestatut . "', TABTPSPARTIEL = '" . $this->tabtpspartiel . "' ";
            $sql = $sql . "WHERE DECLARATIONID='" . $this->declarationid . "'";
            // echo "SQL = $sql <br>";
            $params = array();
            $query = $this->fonctions->prepared_query($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "DeclarationTP->store (update) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
        return "$errlog";
    }

    function html($pourmodif = FALSE, $structid = NULL)
    {
        // echo "DeclarationTP->html : non refaite !!!! <br>";
        // return false;
        $htmltext = "";
        $htmltext = $htmltext . "<tr>";
        
        if ($pourmodif)
            $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->agent()->identitecomplete() . "</td>";
        
        // $htmltext = $htmltext . "<input type='hidden' name='" . $structid. "_" . $this->agent()->agentid() . "_autodeclaid_" . $this->declarationTPid() . "' value='" . $this->declarationTPid() ."'>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedemande() . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedebut() . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
        if ($this->fonctions->formatdatedb($this->datefin()) >= date("Ymd")) // Si la date de fin est postérieur à aujourd'hui
            $htmltext = $htmltext . $this->datefin();
        else // Si la date est inférieur à aujourd'hui, on modifie la tipographie (couleur, gras....)
            $htmltext = $htmltext . "<B><div class='redtext'>" . $this->datefin() . "</div></B>";
        $htmltext = $htmltext . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
        if ($pourmodif and strcasecmp($this->statut(), declarationTP::DECLARATIONTP_ATTENTE) == 0) {
            // Affichager les selections !!!!
            $htmltext = $htmltext . "<select name='statut[" . $this->declarationTPid() . "]'>";
            $htmltext = $htmltext . "<option value='" . declarationTP::DECLARATIONTP_ATTENTE . "'";
            if (strcasecmp($this->statut(), declarationTP::DECLARATIONTP_ATTENTE) == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle(declarationTP::DECLARATIONTP_ATTENTE) . "</option>";
            $htmltext = $htmltext . "<option value='" . declarationTP::DECLARATIONTP_VALIDE ."'";
            if (strcasecmp($this->statut(), declarationTP::DECLARATIONTP_VALIDE) == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle(declarationTP::DECLARATIONTP_VALIDE) . "</option>";
            $htmltext = $htmltext . "<option value='" . declarationTP::DECLARATIONTP_REFUSE ."";
            if (strcasecmp($this->statut(), declarationTP::DECLARATIONTP_REFUSE) == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . "'>" . $this->fonctions->declarationTPstatutlibelle(declarationTP::DECLARATIONTP_REFUSE) . "</option>";
            $htmltext = $htmltext . "</select>";
        } else {
            $htmltext = $htmltext . $this->fonctions->declarationTPstatutlibelle($this->statut());
        }
        $htmltext = $htmltext . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple'>";
        
        $elementliste = array(
            10
        );
        
        // echo "Le tableau des TP = " . $this->tabtpspartiel . "<br>";
        $htmltext = $htmltext . "<div id='planning'>";
        $htmltext = $htmltext . "<table class='tableau'>";
        $htmltext = $htmltext . $this->tabtpspartielhtml();
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "</div>";
        
        $htmltext = $htmltext . "</td>";
        /*
         * if ($pourmodif)
         * {
         * $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
         * $htmltext = $htmltext . "<input type='checkbox' name='declaannule[" . $this->declarationTPid() ."]' value='1'>";
         * $htmltext = $htmltext . "</td>";
         * }
         */
        $htmltext = $htmltext . "</tr>";
        return $htmltext;
    }

    function pdf($valideurid)
    {
        // echo "DeclarationTP->pdf : non refaite !!!! <br>";
        // return false;
        
        // echo "Avant le new <br>";
        $pdf = new FPDF();
        //$pdf = new TCPDF();
        //$pdf->SetHeaderData('', 0, '', '', array(
        //    0,
        //    0,
        //    0
        //), array(
        //    255,
        //    255,
        //    255
        //));
        // echo "Avant le define <br>";
        //if (!defined('FPDF_FONTPATH'))
        //    define('FPDF_FONTPATH','font/');
        //$pdf->Open();
        $pdf->AddPage();
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 70, 25, 60, 20);
        $pdf->Image($this->fonctions->imagepath() . '/' . LOGO_FILENAME, 70, 25, 60, 20);
        // echo "Apres l'image... <br>";
        $pdf->SetFont('helvetica', 'B', 14, '', true);
        $pdf->Ln(50);
        // $pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
        $pdf->Ln(10);
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Demande de temps partiel N°' . $this->declarationTPid() . ' de ' . $this->agent()->identitecomplete()));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 10, '', true);
        // echo "Avant le test statut <br>";
        $decision = mb_strtolower($this->fonctions->declarationTPstatutlibelle($this->statut()),'UTF-8');
        // if($this->statut()==declarationTP::DECLARATIONTP_VALIDE)
        // $decision='validée';
        // else
        // $decision='refusée';
        
        $pdf->Cell(40, 10, $this->fonctions->utf8_decode("La demande de temps partiel que vous avez déposée le " . $this->datedemande() . ' a été ' . $decision . ' le ' . $this->datestatut()));
        $pdf->Ln(10);
        // echo "Avant test quotité <br>";
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Récapitulatif de votre demande de temps partiel pour la période du ' . $this->datedebut() . ' au ' . $this->datefin() . '.'));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        
        $cellheight = 5;
        $pdf->Cell(20, $cellheight, $this->fonctions->utf8_decode(''), 1, 0, 'L', false);
        // On affiche les 5 jours de la semaine
        for ($cpt = 1; $cpt < 6; $cpt ++) {
            $pdf->Cell(20, $cellheight, $this->fonctions->utf8_decode($this->fonctions->nomjourparindex($cpt)), 1, 0, 'C', false);
        }
        $element = new planningelement($this->dbconnect);
        $element->type("tppar");
        $webcolor = $element->couleur();
        $rgbarray = $this->fonctions->html2rgb($webcolor);
        $pdf->SetFillColor($rgbarray[0], $rgbarray[1], $rgbarray[2]);
        
        $pdf->Ln();
        $pdf->Cell(20, $cellheight, $this->fonctions->utf8_decode('Semaine paire'), 1, 0, 'L', false);
        for ($cpt = 0; $cpt < 10; $cpt ++) {
            if ($this->tabtpspartiel[$cpt] == 1)
                $fillcel = true;
            else
                $fillcel = false;
            
            if ($cpt % 2 == 0)
                $pdf->Cell(10, $cellheight, $this->fonctions->utf8_decode(''), 'LTB', 0, 'C', $fillcel);
            else
                $pdf->Cell(10, $cellheight, $this->fonctions->utf8_decode(''), 'RTB', 0, 'C', $fillcel);
        }
        $pdf->Ln();
        $pdf->Cell(20, $cellheight, $this->fonctions->utf8_decode('Semaine impaire'), 1, 0, 'L', false);
        for ($cpt = 10; $cpt < 20; $cpt ++) {
            if ($this->tabtpspartiel[$cpt] == 1)
                $fillcel = true;
            else
                $fillcel = false;
            
            if ($cpt % 2 == 0)
                $pdf->Cell(10, $cellheight, $this->fonctions->utf8_decode(''), 'LTB', 0, 'C', $fillcel);
            else
                $pdf->Cell(10, $cellheight, $this->fonctions->utf8_decode(''), 'RTB', 0, 'C', $fillcel);
        }
        
        $pdf->Ln(15);
        // $pdf->Cell(25,5,'TP:Demi-journée non travaillée pour un temps partiel WE:Week end');
        $pdf->Ln(10);
        
        $pdfname = $this->fonctions->pdfpath() . '/' . date('Y-m') . '/declarationTP_num' . $this->declarationTPid() . '_' . date("YmdHis") . '.pdf';
        // $pdfname = './pdf/declarationTP_num'.$this->declarationTPid().'.pdf';
        // $pdfname = sys_get_temp_dir() . '/autodeclaration_num'.$this->id().'.pdf';
        // echo "Nom du PDF = " . $pdfname . "<br>";
        //$pdf->Output($pdfname, 'F');
        $this->fonctions->savepdf($pdf, $pdfname);
        return $pdfname;
    }

    /**
     *
     * @deprecated
     */
    function demandesliste($debut_interval, $fin_interval)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        
        $agent = new agent($this->dbconnect);
        $agent->load($this->agent);
        $demande_liste = $agent->demandesliste($debut_interval, $fin_interval);
        return $demande_liste;
    }
    
    function tabtptoquotite()
    {
        // On compte le nombre de 1 dans le tableau des temps partiels
        $nbtp = substr_count($this->tabtpspartiel, '1');
        //echo "Nbre de 1 dans le tableau " .  print_r($this->tabtpspartiel,true) . " = $nbtp \n";
        return (100-(intdiv($nbtp,2)*10));
    }
}

?>