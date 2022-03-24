<?php

    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);
    define('K_PATH_IMAGES', $fonctions->imagepath());
    define('K_PATH_CACHE', $fonctions->g2tbasepath() . '/html/pdf/');

    // Recherche de tous les services avec un gestionnaire
    // Pour chaque service => Récupération des agents du service
    // Génération du PDF => Sauvegarde
    // Envoi par mail du fichier PDF

    $jour = date('j');
    $mois = date('m');
    $annee = date('Y');

    // $mois=9;

    $mois = ($mois - 1);
    if ($mois == 0) {
        $mois = 12;
        $annee = ($annee - 1);
    }
    $mois = str_pad($mois, 2, "0", STR_PAD_LEFT);

    $datedebut = "01/" . $mois . "/" . $annee;
    $datefin = $fonctions->nbr_jours_dans_mois($mois, $annee) . "/" . $mois . "/" . $annee;
    $anneeref = $fonctions->anneeref($datedebut);
    echo "Date debut = $datedebut   Date fin = $datefin  anneeref = $anneeref\n";

    // if ($jour == 1) // Premier jour du mois
    // {
    $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE GESTIONNAIREID!='' AND NOT ISNULL(GESTIONNAIREID) AND DATECLOTURE >='" . $fonctions->formatdatedb(date("Ymd")) . "'";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
        echo "generer_solde (SELECT) : " . $erreur . "<br>";
    while ($result = mysqli_fetch_row($query)) {
        $cronmail = new agent($dbcon);
        $cronmail->load("-1");

        $struct = new structure($dbcon);
        $struct->load("$result[0]");
        // Si la structure est encore ouverte...
        if ($fonctions->formatdatedb($struct->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) {

            echo "Génération du PDF pour la structure " . $struct->nomcourt() . "\n";
            $tablisteagent = $struct->agentlist($datedebut, $datefin, 'n');
            if (! is_null($tablisteagent)) {
                $pdf = new FPDF();
                //$pdf = new TCPDF();
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
                //$pdf->AddPage('L');
                ////$pdf->Image($fonctions->imagepath() . '/logo_papeterie.png', 70, 25, 60, 20);
                //$pdf->Image($fonctions->imagepath() . '/' . LOGO_FILENAME, 70, 25, 60, 20);
                foreach ($tablisteagent as $key => $agent) {
                    echo "Agent = " . $agent->identitecomplete() . "\n";
                    $agent->soldecongespdf($anneeref, FALSE, $pdf, TRUE);
                    $agent->demandeslistepdf($anneeref . $fonctions->debutperiode(), ($anneeref + 1) . $fonctions->finperiode(), $pdf, FALSE);
                }
                $filename = $fonctions->pdfpath() . '/' . date('Y-m') . '/solde_' . str_replace('/', '_', str_replace(' ', '-', $struct->nomcourt())) . '_' . date("YmdHis") . ".pdf";
                //$pdf->Output($filename, 'F'); // F = file
                $fonctions->savepdf($pdf, $filename);
                $gest = $struct->gestionnaire();
                $cronmail->sendmail($gest, 'Récapitulatif des congés pour la structure ' . $struct->nomcourt(), "Veuillez trouver ci-joint le récapitulatif des congés pour la structure " . $struct->nomcourt() . " à la date du " . date("d/m/Y") . ".\n", $filename, null, true);
            }
        }
    }
    echo "Fin de la génération .... \n";
    // }
    // else
    // {
    // echo "On est pas la bonne date...";
    // }

?>