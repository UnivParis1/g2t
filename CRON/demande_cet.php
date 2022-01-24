<?php
    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');
    
    $fonctions = new fonctions($dbcon);
    define('K_PATH_IMAGES', $fonctions->imagepath());
    define('K_PATH_CACHE', $fonctions->g2tbasepath() . '/html/pdf/');

/*    
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
*/
    
    // Recherche de tous les services avec un gestionnaire
    // Pour chaque service => Récupération des agents du service
    // Génération du PDF => Sauvegarde
    // Envoi par mail du fichier PDF

    $jour = date('j');
    $mois = date('m');
    $annee = date('Y');

    // $mois=9;

    $mois = ($mois - 6);
    // echo "Moi premier = $mois \n";
    if ($mois <= 0) {
        $mois = 12 + $mois; // ATTENTION : Mois est négatif donc on doit additionner
                            // echo "Mois apres soustraction = $mois \n";
        $annee = ($annee - 1);
    }
    $mois = str_pad($mois, 2, "0", STR_PAD_LEFT);

    $datedebut = "01/" . $mois . "/" . $annee;
    echo "Date debut = $datedebut \n";

    $sql = "SELECT AGENT.HARPEGEID, DEMANDE.DEMANDEID
    			FROM DEMANDE,DEMANDEDECLARATIONTP,DECLARATIONTP,AFFECTATION,AGENT
    			WHERE AFFECTATION.AFFECTATIONID = DECLARATIONTP.AFFECTATIONID
    			  AND DECLARATIONTP.DECLARATIONID = DEMANDEDECLARATIONTP.DECLARATIONID
    			  AND DEMANDEDECLARATIONTP.DEMANDEID = DEMANDE.DEMANDEID
    			  AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID
    			  AND DEMANDE.TYPEABSENCEID = 'cet'
    			  AND (DEMANDE.DATEDEBUT >= '" . $fonctions->formatdatedb($datedebut) . "'
    			    OR DEMANDE.DATESTATUT >= '" . $fonctions->formatdatedb($datedebut) . "' )
    		    ORDER BY DEMANDE.DATEDEBUT,DEMANDE.DATESTATUT";

    // AND DEMANDE.STATUT = 'v'

    // echo "SQL = $sql \n"; 
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
        echo "demande_cet (SELECT) : " . $erreur . "<br>";
    
    if (mysqli_num_rows($query) == 0) {
        echo "demande_cet : Aucune demande de CET sur la période demandée<br>";
    } else {
        echo "Génération du PDF pour les demandes de CET \n";
        $cronmail = new agent($dbcon);
        $cronmail->load("-1");

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
        $pdf->AddPage('L');
        //$pdf->Image($fonctions->imagepath() . '/logo_papeterie.png', 70, 25, 60, 20);
        $pdf->Image($fonctions->imagepath() . '/' . LOGO_FILENAME, 70, 25, 60, 20);
        $pdf->Ln(40);
        $pdf->SetFont('helvetica', 'B', 15, '', true);
        $pdf->Cell(60, 10, utf8_decode("Historique des demandes de congés de CET depuis le $datedebut -- Edité le " . date("d/m/Y")));
        $pdf->Ln(15);

        $pdf->SetFont('helvetica', 'B', 11, '', true);
        $pdf->Cell(100, 5, utf8_decode("Identité de l'agent"), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode("Date de début"), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode("Date de fin"), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode("Nbre de jours"), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode("Statut"), 1, 0, 'C');
        $pdf->Ln();

        while ($result = mysqli_fetch_row($query)) {
            $agent = new agent($dbcon);
            $agent->load("$result[0]");

            $demande = new demande($dbcon);
            $demande->load("$result[1]");

            $complement = new complement($dbcon);
            $complement->load($agent->harpegeid(), 'DEM_CET_' . $demande->id());
            echo "Demande : Identifiant = " . $demande->id() . " Statut = " . $demande->statut() . "  Complement Valeur  = " . $complement->valeur() . " ==> ";

            // Si la demande est validée mais que la valeur du complément n'est pas identique
            // Si la demande est annulée mais que la valeur du complement n'est pas identique et n'est pas vide (si vide => Jamais pris en compte par la RH, donc on ignore)
            //if (($demande->statut() == "v" and $complement->valeur() != $demande->statut()) or ($demande->statut() == "R" and $complement->valeur() != $demande->statut() and $complement->valeur() != "")) {
            if (($demande->statut() == demande::DEMANDE_VALIDE and $complement->valeur() != $demande->statut()) or ($demande->statut() == demande::DEMANDE_ANNULE and $complement->valeur() != $demande->statut() and $complement->valeur() != "")) {
                $pdf->SetFont('helvetica', '', 11, '', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(100, 5, utf8_decode($agent->identitecomplete()), 1, 0, 'C');
                $pdf->Cell(60, 5, utf8_decode($demande->datedebut() . ' ' . $fonctions->nommoment($demande->moment_debut())), 1, 0, 'C');
                $pdf->Cell(60, 5, utf8_decode($demande->datefin() . ' ' . $fonctions->nommoment($demande->moment_fin())), 1, 0, 'C');
                $pdf->Cell(30, 5, utf8_decode($demande->nbrejrsdemande() . "jour(s)"), 1, 0, 'C');
//                if (strcasecmp($demande->statut(), 'R') == 0) {
                if (strcmp($demande->statut(), demande::DEMANDE_ANNULE) == 0 or strcmp($demande->statut(), demande::DEMANDE_REFUSE) == 0) { // Si la demande est annulée ou refusée
                    $pdf->SetFont('helvetica', 'B', 11, '', true);
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 5, utf8_decode($fonctions->demandestatutlibelle($demande->statut())), 1, 0, 'C');
                $pdf->SetFont('helvetica', '', 11, '', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln();
                echo "TRAITE";
            } else {
                echo "IGNORE";
            }
            echo "\n";
            /*
             * if (($demande->statut() == 'R' and strlen($demande->motifrefus()) == 0 ) or ($demande->statut() == 'r') or (strcasecmp($demande->statut(),'a') == 0))
             * {
             * // Si c'est une annulation de l'utilisateur (Statut = R et pas de motif)
             * // si c'est un refus du responsable (statut = r)
             * // Si la demande est en attente de validation (statut = a)
             * continue;
             * }
             *
             * $pdf->SetFont('helvetica', '', 11, '', true);
             * $pdf->SetTextColor(0,0,0);
             * $pdf->Cell(100,5,$agent->identitecomplete(),1,0,'C');
             * $pdf->Cell(60,5,$demande->datedebut() . ' ' . $fonctions->nommoment($demande->moment_debut()),1,0,'C');
             * $pdf->Cell(60,5,$demande->datefin() . ' ' . $fonctions->nommoment($demande->moment_fin()),1,0,'C');
             * $pdf->Cell(30,5,$demande->nbrejrsdemande() . "jour(s)",1,0,'C');
             * if (strcasecmp($demande->statut(),'R') == 0)
             * {
             * $pdf->SetFont('helvetica', 'B', 11, '', true);
             * $pdf->SetTextColor(255,0,0);
             * }
             * $pdf->Cell(30,5,$fonctions->demandestatutlibelle($demande->statut()),1,0,'C');
             * $pdf->SetFont('helvetica', '', 11, '', true);
             * $pdf->SetTextColor(0,0,0);
             * $pdf->Ln();
             */
        }
        $filename = $fonctions->pdfpath() . '/' . date('Y-m') . '/historique_demande_cet_' . date("YmdHis") . ".pdf";
        //ob_end_clean();
        //$pdf->Output($filename, 'F'); // F = file
        $fonctions->savepdf($pdf, $filename);

        $arrayagentrh = $fonctions->listeprofilrh("1"); // Profil = 1 ==> GESTIONNAIRE RH DE CET
        foreach ($arrayagentrh as $gestrh) {
            $cronmail->sendmail($gestrh, 'Historique des demandes de congés de CET', "Veuillez trouver ci-joint le récapitulatif des demandes de CET depuis $datedebut à la date du " . date("d/m/Y") . ".\n", $filename);
        }
    }
    echo "Fin de la génération .... \n";

?>