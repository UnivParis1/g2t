<?php    
    require_once (dirname(__FILE__,3) . "/vendor/autoload.php");
    require_once (dirname(__FILE__,2) . "/class/fonctions.php");
    require_once (dirname(__FILE__,2) . "/class/agent.php");
    require_once (dirname(__FILE__,2) . "/class/structure.php");
    require_once (dirname(__FILE__,2) . "/class/solde.php");
    require_once (dirname(__FILE__,2) . "/class/demande.php");
    require_once (dirname(__FILE__,2) . "/class/planning.php");
    require_once (dirname(__FILE__,2) . "/class/planningelement.php");
    require_once (dirname(__FILE__,2) . "/class/declarationTP.php");
    //require_once (dirname(__FILE__,2) . "/class/fpdf/fpdf.php");
    require_once (dirname(__FILE__,2) . "/class/cet.php");
    require_once (dirname(__FILE__,2) . "/class/recuperation.php");
    require_once (dirname(__FILE__,2) . "/class/affectation.php");
    require_once (dirname(__FILE__,2) . "/class/complement.php");
    require_once (dirname(__FILE__,2) . "/class/demandecomplement.php");
    require_once (dirname(__FILE__,2) . "/class/periodeobligatoire.php");
    require_once (dirname(__FILE__,2) . "/class/alimentationCET.php");
    require_once (dirname(__FILE__,2) . "/class/optionCET.php");
    require_once (dirname(__FILE__,2) . "/class/teletravail.php");
    require_once (dirname(__FILE__,2) . "/class/esignature.php");

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
    
    if (!defined('MODE_CONSULTANT'))
    {
        define('MODE_CONSULTANT', 'consult');
    }
    if (!defined('MODE_RH'))
    {
        define('MODE_RH', 'rh');
    }
    if (!defined('MODE_GESTION'))
    {
        define('MODE_GESTION', 'gest');
    }
    if (!defined('MODE_RESPONSALBE'))
    {
        define('MODE_RESPONSABLE', 'resp');
    }
    if (!defined('MODE_AGENT'))
    {
        define('MODE_AGENT', 'agent');
    }

    $sql="SELECT COUNT(*) FROM STRUCTURE WHERE DEST_MAIL_AGENT IN ('" . structure::OLD_MAIL_AGENT_ENVOI_RESP_COURANT . "','" . structure::OLD_MAIL_AGENT_ENVOI_GEST_COURANT . "')";
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Erreur lors de la selection des anciens statuts de STRUCTURE::DEST_MAIL_AGENT : " . $erreur;
        echo $errlog . "<br/>";
        error_log(basename(__FILE__) . " " . $errlog);
        exit();
    }
    $result = mysqli_fetch_row($query);
    if ($result[0] > 0)
    {
        $sql = "UPDATE STRUCTURE SET DEST_MAIL_AGENT = '" . structure::MAIL_AGENT_ENVOI_RESP_COURANT . "' WHERE DEST_MAIL_AGENT = '" . structure::OLD_MAIL_AGENT_ENVOI_RESP_COURANT . "' ";
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "")
        {
            $errlog = "Erreur lors du changement de STRUCTURE::DEST_MAIL_AGENT de " . structure::OLD_MAIL_AGENT_ENVOI_RESP_COURANT . " vers " . structure::MAIL_AGENT_ENVOI_RESP_COURANT . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $errlog);
            exit();
        }
        
        $sql = "UPDATE STRUCTURE SET DEST_MAIL_AGENT = '" . structure::MAIL_AGENT_ENVOI_GEST_COURANT . "' WHERE DEST_MAIL_AGENT = '" . structure::OLD_MAIL_AGENT_ENVOI_GEST_COURANT . "' ";
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "")
        {
            $errlog = "Erreur lors du changement de STRUCTURE::DEST_MAIL_AGENT de " . structure::OLD_MAIL_AGENT_ENVOI_GEST_COURANT . " vers " . structure::MAIL_AGENT_ENVOI_GEST_COURANT . " : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $errlog);
            exit();
        }
    }
?>