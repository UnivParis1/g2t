<?php

use Fpdf\Fpdf as FPDF;

/**
 * CET
 * Definition of a CET (Compte épargne temps)
 * 
 * @package     G2T
 * @category    classes
 * @author     Pascal COMTE
 * @version    none
 */
class cet
{
    
    private $idannuel = null;

    private $idtotal = null;

    private $agentid = null;

    private $datedebut = null;

    private $cumulannuel = null;

    private $dbconnect = null;

    private $cumultotal = null;

    private $jrspris = null;

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
            $errlog = "Cet->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    /**
     *
     * @param
     * @return string id of annual CET
     */
    function idannuel()
    {
        if (! is_null($this->idannuel))
            return $this->idannuel;
        else {
            $errlog = "Cet->idannuel : L'identifiant du CET annuel n'est pas initialisé !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param
     * @return string id of total CET
     */
    function idtotal()
    {
        if (! is_null($this->idtotal))
            return $this->idtotal;
        else {
            $errlog = "Cet->idtotal : L'identifiant du CET total n'est pas initialisé !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param string $agentid
     *            optional identifier of the agent
     * @return string the identifier of the agent if $agentid is not set
     */
    function agentid($agentid = null)
    {
        if (is_null($agentid)) {
            if (is_null($this->agentid)) {
                $errlog = "Cet->agentid : L'Id de l'agent n'est pas défini !!! ";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->agentid;
        } else
            $this->agentid = $agentid;
    }

    /**
     *
     * @param string $date
     *            optional starting date of the CET
     * @return string the starting date of the CET if $date is not set
     */
    function datedebut($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datedebut)) {
                $errlog = "Cet->datedebutcet : La date du début du CET de l'agent n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datedebut);
        } else
            $this->datedebut = $this->fonctions->formatdatedb($date);
    }

    /**
     *
     * @param string $annee
     *            year to get the annual cumul
     * @param string $cumulannee
     *            number of day piled up for the year
     * @return string number of day piled up for the year if $cumulannee is not set
     */
    function cumulannuel($annee, $cumulannee = null)
    {
        if (is_null($cumulannee)) {
            if (is_null($this->cumulannuel)) {
                $errlog = "Cet->cumulannuel : Le cumul annuel du CET de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } elseif (! isset($this->cumulannuel['cet' . $annee]))
                return 0;
            else
                return $this->cumulannuel['cet' . $annee];
        } elseif (intval($cumulannee) == $cumulannee)
            $this->cumulannuel['cet' . $annee] = $cumulannee;
        else {
            $errlog = "Cet->cumulannuel : Le cumul annuel du CET de l'agent doit être un nombre entier";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param string $cumultot
     *            number of days piled up into CET
     * @return string number of days piled up if $cumultot is not set
     */
    function cumultotal($cumultot = null)
    {
        if (is_null($cumultot)) {
            if (is_null($this->cumultotal)) {
                $errlog = "Cet->cumultotal : Le cumul total du CET de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->cumultotal;
        } elseif ((intval($cumultot) <> $cumultot) and (intval($this->cumultotal) <> $this->cumultotal)) {
            //echo "Dans le cas ou le solde est deja avec des virgule et on ajoute un nombre entier ==> On a forcément des virgules <br>";
            $this->cumultotal = $cumultot;
        } elseif (intval($cumultot) == $cumultot) {
            //echo "Dans le cas ou le solde n'a pas deja avec des virgule et on ajoute un nombre entier ==> On a forcément des virgules <br>";
            $this->cumultotal = $cumultot;
        } else {
            $errlog = "Cet->cumultotal : Le cumul total du CET de l'agent doit être un nombre entier";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
    }

    /**
     *
     * @param string $nbrejrspris
     *            number of days used from CET
     * @return string number of days used if $nbrejrspris is not set
     */
    function jrspris($nbrejrspris = null)
    {
        if (is_null($nbrejrspris)) {
            if (is_null($this->jrspris)) {
                $errlog = "Cet->jrspris : Le nombre de jours pris dans le CET de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->jrspris;
        } /* elseif (intval($nbrejrspris) == $nbrejrspris)
            $this->jrspris = $nbrejrspris;
        else {
            $errlog = "Cet->jrspris : Le nombre de jours pris dans le CET de l'agent doit être un nombre entier";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } */
        else {
             $this->jrspris = $nbrejrspris;
        }
    }

    /**
     *
     * @param string $agentid
     *            identifier of the agent
     * @return string empty string if all correct. An error message otherwise
     */
    function load($agentid)
    {
        $msgerreur = "";
        if (is_null($agentid))
            return "Cet->load : Le code de l'agent est NULL <br>";
        
        $agent = new agent($this->dbconnect);
        $agent->load($agentid);
        // On charge le cumul annuel => tous les 'cet%' mais pas 'cet'
        $sql = "SELECT AGENTID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE AGENTID = ? AND TYPEABSENCEID LIKE 'cet%' AND TYPEABSENCEID != 'cet'";
        $params = array($agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Cet->Load (cet%): " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Cet->Load (cet%) : Aucun cumul annuel pour l'agent $agentid trouvé <br>";
            $errlog = "Aucun cumul annuel pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pu être trouvé";
            // Suppression de la trace car pas une erreur
            // error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $errlog . "<br/>";
        }
        
        while ($result = mysqli_fetch_row($query)) {
            $this->agentid = $result[0];
            $this->idannuel = $result[1];
            $this->cumulannuel[$this->idannuel] = $result[2];
        }
        
        // On charge le solde du CET
        $sql = "SELECT AGENTID,TYPEABSENCEID,DROITAQUIS,DROITPRIS FROM SOLDE WHERE AGENTID = ? AND TYPEABSENCEID ='cet'";
        $params = array($agentid);
        $query = $this->fonctions->prepared_select($sql, $params);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "(cet): " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "Cet->Load (cet) : Le CET pour l'agent $agentid non trouvé <br>";
            $errlog = "Le CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pu être trouvé";
            $msgerreur = $msgerreur . $errlog . "<br/>";
            // Suppression de la trace car pas une erreur
            // error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
        }
        else
        {
            $result = mysqli_fetch_row($query);
            $this->cumultotal = $result[2];
            $this->idtotal = $result[1];
            $this->jrspris = $result[3];
        }
        // On charge la date de début du CET
        $complement = new complement($this->dbconnect);
        $complement->load($agentid, "DEBUTCET");
        if ($complement->agentid() == "") {
            // echo "Cet->Load (date début) : La date de début du CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " non trouvée <br>";
            $errlog = "La date de début du CET pour l'agent " . $agent->civilite() . " " . $agent->nom() . " " . $agent->prenom() . " n'a pas pu être trouvée";
            // Suppression de la trace car pas une erreur
            // error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $errlog . "<br/>";
            $this->datedebut = "";
        } else
            $this->datedebut = $this->fonctions->formatdate($complement->valeur());
        // echo "Cet->Load : msgerreur = $msgerreur <br>";
        return $msgerreur;
    }

    /**
     *
     * @param
     * @return string empty string if all correct. An error message otherwise
     */
    function store()
    {
        // echo "cet->store : Pas encore fait !!!! <br>";
        // return;
        // echo "Avant le test idannuel <br>";
        // echo "this->idannuel = " . $this->idannuel . "<br>";
        // echo "is_null(this->idannuel) = " . is_null($this->idannuel) . "<br>";
        $msgerreur = "";
        $solde = new solde($this->dbconnect);
        if ($solde->load($this->agentid, 'cet' . $this->fonctions->anneeref()) != "") {
            // echo "On va creer le solde <br>";
            // echo "'cet'.this->fonctions->anneeref() = " . 'cet'.$this->fonctions->anneeref(). "<br>";
            $solde->creersolde('cet' . $this->fonctions->anneeref(), $this->agentid);
            // On recrée un nouvel objet pour eviter les effets de bord eventuels
            unset($solde);
            $solde = new solde($this->dbconnect);
        }
        // echo "On va charger le solde... <br>";
        $solde->load($this->agentid, 'cet' . $this->fonctions->anneeref());
        if (!isset($this->cumulannuel['cet' . $this->fonctions->anneeref()]))
        {
            $this->cumulannuel['cet' . $this->fonctions->anneeref()] = 0;
        }
        $solde->droitaquis($this->cumulannuel['cet' . $this->fonctions->anneeref()]);
        // echo "On va store le solde <br>";
        $msgerreur = $msgerreur . $solde->store();
        
        unset($solde);
        $solde = new solde($this->dbconnect);
        // echo "Avant le test idtotal <br>";
        // echo "this->idtotal = " . $this->idtotal . "<br>";
        if (is_null($this->idtotal)) {
            // echo "On va creer le solde <br>";
            $solde->creersolde('cet', $this->agentid);
            // echo "On va recharger le solde... <br>";
            
            $complement = new complement($this->dbconnect);
            $complement->agentid($this->agentid);
            $complement->complementid('DEBUTCET');
            if (! $this->fonctions->verifiedate($this->datedebut)) {
                // echo "CET->Store : Date début n'est pas une date => " . $this->datedebut() . "<br>";
                $this->datedebut = date("Ymd");
            }
            // echo "this->datedebut() = " . $this->datedebut() . "<br>";
            $complement->valeur($this->datedebut());
            $complement->store();
        }
        $solde->load($this->agentid, 'cet');
        
        $solde->droitaquis($this->cumultotal());
        $solde->droitpris($this->jrspris);
        // echo "On va store le solde <br>";
        $msgerreur = $msgerreur . $solde->store();
        return $msgerreur;
    }

    /**
     *
     * @param string $responsableid
     *            the reponsable identifier
     * @param boolean $ajoutmode
     *            optional True if just add days into CET. False if just remove days from CET
     * @param string $detail
     *            optional Text to be added in the PDF document
     * @return string PDF file name
     */
    function pdf($responsableid, $ajoutmode = TRUE, $detail = null)
    {
        
        // echo "Avant le new agent <br>";
        $responsable = new agent($this->dbconnect);
        $responsable->load($responsableid);
        // echo "Apres le load...<br>";
        
        $pdf=new FPDF();
        //$pdf = new TCPDF();
        // echo "Apres le new <br>";
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
        $pdf->AddPage();
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 70, 25, 60, 20);
        $pdf->Image($this->fonctions->imagepath() . '/' . LOGO_FILENAME, 70, 25, 60, 20);
        
        // echo "Apres image <br>";
        $pdf->SetFont('helvetica', 'B', 16, '', true);
        $pdf->Ln(70);
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        
        $agent = new agent($this->dbconnect);
        $agent->load($this->agentid);
        /*
         * $affectationliste = $agent->affectationliste($this->fonctions->anneeref() . $this->fonctions->debutperiode(), ($this->fonctions->anneeref()+1) . $this->fonctions->finperiode());
         * foreach ($affectationliste as $key => $affectation)
         * {
         * $structure = new structure($this->dbconnect);
         * $structure->load($affectation->structureid());
         * $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() .")";
         * $pdf->Cell(60,10,'Service : '. $nomstructure);
         * $pdf->Ln();
         * }
         */
        $affectationliste = $agent->affectationliste(date('d/m/Y'), date('d/m/Y')); // On récupère l'affectation de l'agent à la date du jour
        if (is_array($affectationliste)) {
            // echo "affectationliste = " . print_r($affectationliste, true) . "<br>";
            $affectation = reset($affectationliste); // ATTENTION : Reset permet de récupérer le premier élément du tableau => On ne connait pas la clé
            $structure = new structure($this->dbconnect);
            $structure->load($affectation->structureid());
            $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
            $pdf->Cell(60, 10, utf8_decode('Service : ' . $nomstructure));
            $pdf->Ln(10);
        }
        
        /*
         * $pdf->Cell(60,10,'Composante : '. $responsable->structure()->parentstructure()->nomlong() .' ('. $responsable->structure()->parentstructure()->nomcourt() .')' );
         * $pdf->Ln(10);
         * $pdf->Cell(60,10,'Service : '. $responsable->structure()->nomlong().' ('. $responsable->structure()->nomcourt() .')' );
         * $pdf->Ln(10);
         */
        if ($ajoutmode) {
            // echo "Apres le nom du service <br>";
            $pdf->Cell(40, 10, utf8_decode('Votre "Compte Epargne Temps" (CET) vient d\'être alimenté.'));
            $pdf->Ln(10);
            // $pdf->Cell(40,10,'La date d\'ouverture de votre CET est : ' . $this->datedebut());
            $pdf->Ln(10);
            $pdf->Cell(40, 10, utf8_decode('Le solde actuel de votre CET est : ' . (($this->cumultotal() - $this->jrspris())) . ' jour(s).'));
            $pdf->Ln(10);
            $pdf->Cell(40, 10, utf8_decode('Cette année, vous avez ajouté ' . ($this->cumulannuel($this->fonctions->anneeref())) . ' jour(s).'));
        } else {
            $pdf->Cell(40, 10, utf8_decode('Votre "Compte Epargne Temps" (CET) vient d\'être modifié.'));
            $pdf->Ln(10);
            $pdf->Cell(40, 10, utf8_decode($detail));
            $pdf->Ln(10);
            $pdf->Cell(40, 10, utf8_decode('Le solde actuel de votre CET est : ' . ($this->cumultotal() - $this->jrspris()) . ' jour(s).'));
        }
        // echo "Apres les textes <br>";
        $pdf->Ln(10);
        
        $pdf->Ln(10);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, utf8_decode("Pour le service de la Direction des ressources humaines"));
        $pdf->Ln(10);
        $pdf->Cell(40, 10, utf8_decode($responsable->identitecomplete()));
        $pdf->Ln(10);
        
        // echo "Nom du fichier....<br>";
        $pdfname = $this->fonctions->pdfpath() . '/' . date('Y-m') . '/modification_cet_' . $agent->agentid() . '_' . date("YmdHis") . '.pdf';
        // echo "Avant le output... pdfname = $pdfname <br>";
        
        //$pdf->Output($pdfname, 'F');
        $this->fonctions->savepdf($pdf, $pdfname);
        // echo "Avant le return. <br>";
        return $pdfname;
    }
}

?>