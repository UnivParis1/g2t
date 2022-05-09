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
    }
    //var_dump(TABCOULEURPLANNINGELEMENT);
    
?>