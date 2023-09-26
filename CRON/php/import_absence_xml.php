<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);
    
    $date = date("Ymd");
    
    echo "Début de l'import des absences de l'application RH " . date("d/m/Y H:i:s") . "\n";
    
    // On charge la table des absences avec le fichier
    $filename = $fonctions->inputfilepath() . "/siham_absence_$date.xml";
    if (! file_exists($filename)) {
        echo "Le fichier $filename n'existe pas !!! \n";
        exit();
    } 
    else 
    {
        echo "Le fichier $filename est présent. \n";
        // On vide la table des absences pour la recharger complètement
        $sql = "DELETE FROM ABSENCERH";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
        {
            echo "DELETE ABSENCERH => $erreur_requete \n";
        }
        
	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('ABSENCE');
	foreach ($agentnode as $node)
	{
            $agentid = trim($node->xpath('AGENTID')[0]);
            $datedebut = trim($node->xpath('DATEDEBUT')[0]);
            $datefin = trim($node->xpath('DATEFIN')[0]);
            $typeabsence = trim($node->xpath('LIBELLE')[0]);
            $datedebutformate = $fonctions->formatdatedb(str_replace('/','-',$datedebut));
            echo "agentid = $agentid   datedebut=$datedebut   datefin=$datefin   typeabsence=$typeabsence  datedebutformate = $datedebutformate \n";

            $agent = new agent($dbcon);
            if (!$agent->existe($agentid))
            {
                // L'agent n'est pas dans la base => On n'intègre pas ses absences
                echo "L'agent $agentid n'existe pas dans la base. On ne charge pas ses absences \n";
                continue;
            }

            // Si c'est un congés bonifié et que la date de début est supérieure au 19/11/2021 ==> On ignore les congés bonifiés car "nouvelle version" (ticket GLPI 135729)
            if (stripos($fonctions->my_real_escape_utf8($typeabsence)," Bonifié ")!==false and ($datedebutformate >= "20211119"))
            {
                echo "La demande de $typeabsence pour l'agent $agentid est un conge bonifie 'nouvelle version'. On ne charge pas cette absence \n";
                continue;
            }

            $sql = sprintf("INSERT INTO ABSENCERH (AGENTID,DATEDEBUT,DATEFIN,TYPEABSENCE) VALUES('%s','%s	','%s','%s')", $fonctions->my_real_escape_utf8($agentid), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($typeabsence));

            mysqli_query($dbcon, $sql);
            $erreur_requete = mysqli_error($dbcon);
            if ($erreur_requete != "") {
                echo "INSERT ABSENCERH => $erreur_requete \n";
                echo "sql = $sql \n";
            }
        }
    }
    
    $tabannees = array($fonctions->anneeref(),$fonctions->anneeref()+1);
    $stringannee = "";
    $separateur = "";
    foreach($tabannees as $key => $annee)
    {
        if (strlen($stringannee)>0 and $key==count($tabannees)-1) $separateur = ' et '; elseif (strlen($stringannee)>0) $separateur = ', ';
        $stringannee = $stringannee . $separateur . $annee . '/' . ($annee+1);
    }
    $tabferies = array();
    echo "On importe les jours féries sur " . trim($stringannee) . "\n";
    $erreur = $fonctions->synchronisationjoursferies($tabannees, $tabferies);
    if ($erreur!='')
    {
        echo "Erreur lors de la synchronisation : $erreur.\n";
    }
    else
    {
        echo "La synchronisation s'est bien passee sur " . trim($stringannee) . "\n";
    }
    
    echo "Fin de l'import des absences de l'application RH " . date("d/m/Y H:i:s") . "\n";

?>