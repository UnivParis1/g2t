<?php
    define('K_PATH_IMAGES', dirname(dirname(__FILE__)) . '/html/images/');
    define('K_PATH_CACHE', dirname(dirname(__FILE__)) . '/html/pdf/');

    require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');

    $fonctions = new fonctions($dbcon);

    require_once ("../html/class/agent.php");
    require_once ("../html/class/structure.php");
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    require_once ("../html/class/fpdf/fpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");

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
                //$pdf->Image('../html/images/logo_papeterie.png', 70, 25, 60, 20);
                foreach ($tablisteagent as $key => $agent) {
                    echo "Agent = " . $agent->identitecomplete() . "\n";
                    $agent->soldecongespdf($anneeref, FALSE, $pdf, TRUE);
                    $agent->demandeslistepdf($anneeref . $fonctions->debutperiode(), ($anneeref + 1) . $fonctions->finperiode(), $pdf, FALSE);
                }
                $filename = dirname(dirname(__FILE__)) . '/html/pdf/' . date('Y-m') . '/solde_' . str_replace('/', '_', $struct->nomcourt()) . '_' . date("YmdHis") . ".pdf";
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