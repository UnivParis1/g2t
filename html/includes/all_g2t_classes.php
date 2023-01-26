<?php
    require_once (dirname(__FILE__,2) . "/class/fonctions.php");
    require_once (dirname(__FILE__,2) . "/class/agent.php");
    require_once (dirname(__FILE__,2) . "/class/structure.php");
    require_once (dirname(__FILE__,2) . "/class/solde.php");
    require_once (dirname(__FILE__,2) . "/class/demande.php");
    require_once (dirname(__FILE__,2) . "/class/planning.php");
    require_once (dirname(__FILE__,2) . "/class/planningelement.php");
    require_once (dirname(__FILE__,2) . "/class/declarationTP.php");
    require_once (dirname(__FILE__,2) . "/class/fpdf/fpdf.php");
    require_once (dirname(__FILE__,2) . "/class/cet.php");
    require_once (dirname(__FILE__,2) . "/class/affectation.php");
    require_once (dirname(__FILE__,2) . "/class/complement.php");
    require_once (dirname(__FILE__,2) . "/class/periodeobligatoire.php");
    require_once (dirname(__FILE__,2) . "/class/alimentationCET.php");
    require_once (dirname(__FILE__,2) . "/class/optionCET.php");
    require_once (dirname(__FILE__,2) . "/class/teletravail.php");
    
    //echo "Le chemin parent = " . dirname(__FILE__,2) . "<br><br>"
    $fonctions = new fonctions($dbcon);
    
    // On va charger le tableau des couleurs de chaque élément du planning => Optimisation du tps
    // Voir la classe planningelement->couleur()
    if (!defined('TABCOULEURPLANNINGELEMENT'))
    {
        $tabcouleurelement = $fonctions->typeabsencelistecomplete();
        define('TABCOULEURPLANNINGELEMENT', $tabcouleurelement);
        //var_dump(TABCOULEURPLANNINGELEMENT);
    }

    
    $sql="SELECT COUNT(*) FROM TELETRAVAIL WHERE STATUT IN ('" . teletravail::OLD_STATUT_ACTIVE . "','" . teletravail::OLD_STATUT_INACTIVE . "')";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Erreur lors de la selection des anciens statuts du télétravail : " . $erreur;
        echo $errlog . "<br/>";
        error_log(basename(__FILE__) . " " . $errlog);
        exit();
    }
    $result = mysqli_fetch_row($query);
    if ($result[0] > 0)
    {
//        var_dump('Il y a des statuts de teletravail à modifier.');
        $sql = "UPDATE TELETRAVAIL SET STATUT = '" . teletravail::TELETRAVAIL_VALIDE . "' WHERE STATUT = '" . teletravail::OLD_STATUT_ACTIVE . "' ";
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "")
        {
            $errlog = "Erreur lors du changement de statut du télétravail " . teletravail::OLD_STATUT_ACTIVE . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $errlog);
            exit();
        }
        $sql = "UPDATE TELETRAVAIL SET STATUT = '" . teletravail::TELETRAVAIL_ANNULE . "' WHERE STATUT = '" . teletravail::OLD_STATUT_INACTIVE . "' ";
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "")
        {
            $errlog = "Erreur lors du changement de statut du télétravail " . teletravail::OLD_STATUT_INACTIVE . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $errlog);
            exit();
        }
    }
    else
    {
//        var_dump('Aucun statut de teletravail à modifier.');
    }
?>