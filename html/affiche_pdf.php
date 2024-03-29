<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    
    
    header('Content-type: application/pdf; charset=utf-8');

    // header('Content-type: application/pdf');
    // header('Content-Type=application/octet-stream;charset=UTF-8');
    // header('Content-Disposition: attachment; filename="downloaded.pdf"');

    require_once ('./includes/dbconnection.php');
    require_once ("./includes/all_g2t_classes.php");
    
    use Fpdf\Fpdf as FPDF;

    // print_r($_POST); echo "<br>";
    // $agent = new agent($dbcon);

    $fonctions = new fonctions($dbcon);
    ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

    $previous = 0;
    if (isset($_POST["previous"]))
        if (strcasecmp($_POST["previous"], "yes") == 0)
            $previous = 1;
        else
            $previous = 0;

    $anneeref = ($fonctions->anneeref() - $previous);
    if (isset($_POST["anneeref"]))
        if ($_POST["anneeref"] != "")
            $anneeref = $_POST["anneeref"];

    $listeagent = "";
    if (isset($_POST["listeagent"]))
        $listeagent = $_POST["listeagent"];
    $typepdf = "";
    if (isset($_POST["typepdf"]))
        $typepdf = $_POST["typepdf"];

    if ($typepdf == 'listedemande') {
        //header('Content-Disposition: attachment; filename="liste_demandes.pdf"');
        if ($listeagent != "") {
            // echo "Avant le split <br>";
            $tablisteagent = preg_split("/,/", $listeagent);
            // print_r($tablisteagent); echo "<br>";
            $pdf = new FPDF();
            //$pdf = new TCPDF();
            // define('FPDF_FONTPATH','fpdffont/');
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
            foreach ($tablisteagent as $key => $agentid) {
                if ($agentid != "") {
                    $agent = new agent($dbcon);
                    // echo "Agentid = " . $agentid . "<br>";
                    $agent->load($agentid);
                    // echo "Apres le load ...<br>";
                    $agent->soldecongespdf($anneeref, FALSE, $pdf, TRUE);
                    $agent->demandeslistepdf($anneeref . $fonctions->debutperiode(), ($anneeref + 1) . $fonctions->finperiode(), $pdf, FALSE);
                }
            }
            ob_end_clean();
            $pdf->Output("","liste_demandes.pdf");
        } else {
            // echo "Dans liste demande <br>";
            $agentid = $_POST["agentid"];
            $agent = new agent($dbcon);
            // echo "Agentid = " . $agentid . "<br>";
            $agent->load($agentid);
            // echo "Apres le load ...<br>";
            $agent->demandeslistepdf($anneeref . $fonctions->debutperiode(), ($anneeref + 1) . $fonctions->finperiode());
        }
    }

    if (isset($_POST["userpdf"])) {
        //header('Content-Disposition: attachment; filename="planning_agent.pdf"');
        if (strcasecmp($_POST["userpdf"], "yes") == 0) {
            $agentid = $_POST["agentid"];
            $includeteletravail = $_POST["includeteletravail"];
            $checkbox = 'off';
            if (isset($_POST["hide_teletravail_". $agentid ]))
                $checkbox = $_POST["hide_teletravail_". $agentid ];
            //error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Checkbox = $checkbox"));
            if (strcasecmp($includeteletravail, "yes") == 0 and strcasecmp($checkbox, "off") == 0)
                $includeteletravail = true;
            else
                $includeteletravail = false;
            $planning = new planning($dbcon);
            $planning->pdf($agentid, $fonctions->formatdate($anneeref . $fonctions->debutperiode()), $fonctions->formatdate(($anneeref + 1) . $fonctions->finperiode()),false,$includeteletravail);
        }
    }

    if (isset($_POST["structpdf"])) {
        //header('Content-Disposition: attachment; filename="planning_structure.pdf"');
        if (strcasecmp($_POST["structpdf"], "yes") == 0) {
            $structid = $_POST["structid"];
            $mois_annee = $_POST["mois_annee"];
            $noiretblanc = $_POST["noiretblanc"];
            if (strcasecmp($noiretblanc, "yes") == 0)
                $noiretblanc = true;
            else
                $noiretblanc = false;
            
            $includeteletravail = $_POST["includeteletravail"];
            $checkbox = 'off';
            if (isset($_POST["hide_teletravail_". $structid ]))
                $checkbox = $_POST["hide_teletravail_". $structid ];
            if (strcasecmp($includeteletravail, "yes") == 0 and strcasecmp($checkbox, "off") == 0)
                $includeteletravail = true;
            else
                $includeteletravail = false;
                
            $structure = new structure($dbcon);
            $structure->load($structid);

            // On décompose la date mois_annee en mois et année pour éventuellement soustraire un an
            // Puis on la reformate
            // Le format de la variable mois_annee est MM/YYYY (voir fonction structure::planninghtml)
            $mois_annee = substr($mois_annee, 0, 3) . (substr($mois_annee, 3) - $previous);
            $structure->pdf(($mois_annee), $noiretblanc,$includeteletravail);
        }
    }
    

    if (isset($_POST["teletravailPDF"]))
    {
        //header('Content-Disposition: attachment; filename="agent_télétravail.pdf"');
        // On va éditer le document PDF de télétravail

        $structureid = null;
        if (isset($_POST['structureid']))
           $structureid = $_POST['structureid'];

       if (isset($_POST['datedebut']))
           $datedebut = $_POST['datedebut'];
       if (isset($_POST['datefin']))
           $datefin = $_POST['datefin'];


        //echo "<br>L'id de la structure est : " . $structureid . "<br>";
        $structure = new structure($dbcon);
        $structure->load($structureid);
        $structure->teletravailpdf($datedebut,$datefin);
    }
     
    if (isset($_POST["esignaturePDF"]))
    {
        if (isset($_POST["esignatureid"]))
        {
            $esignatureid = trim($_POST["esignatureid"]);
            $eSignature_url = $fonctions->liredbconstante('ESIGNATUREURL');
            $error = '';

            // On appelle le WS eSignature pour récupérer le document final
            $curl = curl_init();
            $opts = [
                CURLOPT_URL => $eSignature_url . '/ws/signrequests/get-last-file/' . $esignatureid,
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
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $error"));
                $pdfdoc = new FPDF();
                $pdfdoc->AddPage('L');
                $pdfdoc->SetFont('helvetica', 'B', 14, '', true);
                $pdfdoc->Text(10, 10, $fonctions->utf8_decode($error));
                $pdfdoc->Output("", "PDF_Error.pdf");
                //echo $error . "<br>";
                //echo "Erreur Curl (récup PDF) =>  " . $error . '<br><br>';
            }
            else if (stristr(substr($pdf,0,10),'PDF') === false)
            {
                $error = "Erreur Curl (récup PDF) =>  Le document retourné n'est pas un PDF";
                error_log(basename(__FILE__) . $fonctions->stripAccents(" $error"));
                $pdfdoc = new FPDF();
                $pdfdoc->AddPage('L');
                $pdfdoc->SetFont('helvetica', 'B', 14, '', true);
                $pdfdoc->Text(10, 10, $fonctions->utf8_decode($error));
                $pdfdoc->Ln();
                $pdfdoc->Text(10,30, $fonctions->utf8_decode($pdf));
                $pdfdoc->Output("", "PDF_Error.pdf");
                //echo $error . "<br>";
            }
            else
            {
                //error_log(basename(__FILE__) . $fonctions->stripAccents(" $pdf"));
                echo $pdf;
            }
        }
    }

    
?>