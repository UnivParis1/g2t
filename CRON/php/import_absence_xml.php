<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);
    
    $date = date("Ymd");
    
    echo "\nDébut de l'import des absences de l'application RH " . date("d/m/Y H:i:s") . "\n";
    
    // On charge la table des absences avec le fichier
    $filename = $fonctions->inputfilepath() . "/siham_absence_$date.xml";
    if (! file_exists($filename) or ($xml = @simplexml_load_file("$filename"))===false) {
        echo "Le fichier " . basename($filename) . " n'existe pas ou n'est pas un fichier XML valide. \n";
        exit();
    } 
    else 
    {
        echo "Le fichier " . basename($filename) . " est présent. \n";
        // On vide la table des absences pour la recharger complètement
        $sql = "DELETE FROM ABSENCERH";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
        {
            echo "DELETE ABSENCERH => $erreur_requete \n";
        }
        
        $agentnode = $xml->xpath('ABSENCE');
        echo "Import en masse de toutes les absences RH dans G2T " . date("d/m/Y H:i:s") . "\n";
        foreach ($agentnode as $node)
        {
            $agentid = trim($node->xpath('AGENTID')[0]);
            $datedebut = trim($node->xpath('DATEDEBUT')[0]);
            $datefin = trim($node->xpath('DATEFIN')[0]);
            $libelle = trim($node->xpath('LIBELLE')[0]);
            $typeabsence = trim($node->xpath('TYPEABSENCE')[0]);
            $codeabsence = "";
            if (isset($node->xpath('CODEABSENCE')[0]))
            {
                $codeabsence = trim($node->xpath('CODEABSENCE')[0]);
            }
            $datedebutformate = $fonctions->formatdatedb(str_replace('/','-',$datedebut));
            $datefinformate = $fonctions->formatdatedb(str_replace('/','-',$datefin));
            
            //echo "agentid = $agentid   datedebut=$datedebut   datefin=$datefin   typeabsence=$libelle  datedebutformate = $datedebutformate \n";

            $agent = new agent($dbcon);
            if (!$agent->existe($agentid))
            {
                // L'agent n'est pas dans la base => On n'intègre pas ses absences
                echo "L'agent $agentid n'existe pas dans la base. On ne charge pas ses absences \n";
                continue;
            }

            // Si c'est un congés bonifié et que la date de début est supérieure au 19/11/2021 ==> On ignore les congés bonifiés car "nouvelle version" (ticket GLPI 135729)
            if (stripos($fonctions->my_real_escape_utf8($libelle)," Bonifié ")!==false and ($datedebutformate >= "20211119"))
            {
                echo "La demande de $libelle pour l'agent $agentid est un conge bonifie 'nouvelle version'. On ne charge pas cette absence \n";
                continue;
            }

            $sql = sprintf("INSERT INTO ABSENCERH (AGENTID,DATEDEBUT,DATEFIN,LIBELLE,TYPEABSENCE,CODEABSENCE) "
                         . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')", 
                         $fonctions->my_real_escape_utf8($agentid), 
                         $fonctions->my_real_escape_utf8($datedebut), 
                         $fonctions->my_real_escape_utf8($datefin), 
                         $fonctions->my_real_escape_utf8($libelle),
                         $fonctions->my_real_escape_utf8($typeabsence),
                         $fonctions->my_real_escape_utf8($codeabsence));

            mysqli_query($dbcon, $sql);
            $erreur_requete = mysqli_error($dbcon);
            if ($erreur_requete != "") {
                echo "INSERT ABSENCERH => $erreur_requete \n";
                echo "sql = $sql \n";
            }
        }
        echo "Fin de l'Import en masse de toutes les absences RH dans G2T " . date("d/m/Y H:i:s") . "\n";

        // On reparcourt l'ensemble des noeux après avoir intégré les absences RH
        // On ne fait pas le contrôle des demande lors du premier passage, car le planning de l'agent n'est pas à jour entièrement
        // => Cela peut avoir des conséquences sur le calcul du nombre de jours des demandes
        echo "Début de traitements des demandes suite à l'intégration des absences RH " . date("d/m/Y H:i:s") . "\n";
        $debut_periode_marge = ($fonctions->anneeref()-$fonctions->margesynchro()) . $fonctions->debutperiode();
        foreach ($agentnode as $node)
        {
            $agentid = trim($node->xpath('AGENTID')[0]);
            $datedebut = trim($node->xpath('DATEDEBUT')[0]);
            $datefin = trim($node->xpath('DATEFIN')[0]);
            $libelle = trim($node->xpath('LIBELLE')[0]);
            $typeabsence = trim($node->xpath('TYPEABSENCE')[0]);
            $codeabsence = "";
            if (isset($node->xpath('CODEABSENCE')[0]))
            {
                $codeabsence = trim($node->xpath('CODEABSENCE')[0]);
            }
            $datedebut = $fonctions->formatdate($datedebut);
            $datefin = $fonctions->formatdate($datefin);
            // $datedebutformate = $fonctions->formatdatedb(str_replace('/','-',$datedebut));
            // $datefinformate = $fonctions->formatdatedb(str_replace('/','-',$datefin));
            $datedebutformate = $fonctions->formatdatedb($datedebut);
            $datefinformate = $fonctions->formatdatedb($datefin);
            
            //echo "agentid = $agentid   datedebut=$datedebut   datefin=$datefin   typeabsence=$libelle  datedebutformate = $datedebutformate \n";

            $agent = new agent($dbcon);
            if (!$agent->existe($agentid))
            {
                // L'agent n'est pas dans la base => On n'intègre pas ses absences
                echo "L'agent $agentid n'existe pas dans la base. On ne charge pas ses absences \n";
                continue;
            }

            //echo "datefinformate = $datefinformate   Date J - 2 ans = " . (date('Y')-2) . date("md") . " typeabsence = $typeabsence \n";
            // Si l'arret s'est terminé il y a moins de 2 ans et que c'est un type d'absence M (<=> maladie)
            if ($datefinformate >= $debut_periode_marge and strcasecmp((string)$typeabsence,'M')==0)
            {
                echo "On a un arret qui est postérieur à " . $fonctions->formatdate($debut_periode_marge) . " et c'est une maladie (typeabsence = $typeabsence) : " . $fonctions->formatdate($datedebut) . " -> " . $fonctions->formatdate($datefin) . "\n";
                $agent = new agent($dbcon);
                if ($agent->load($agentid))
                {
                    echo "On va demander la liste des demandes de congés/d'absences de l'agent " . $agent->identitecomplete() . " - id agent : $agentid \n";
                    $demandeliste = $agent->demandesliste($datedebutformate, $datefinformate);
                    echo "Nbre de demandes de cet agent sur la période $datedebut -> $datefin : " . count($demandeliste) . " \n";
                    foreach ((array)$demandeliste as $demande)
                    {
                        echo "On traite la demande : " . $demande->id() . " \n";
                        // Si la demande est un congé => Il peut y avoir un impact sur le solde de congés
                        if ($fonctions->estunconge($demande->type()))
                        {
                            $nbrejrscalcule = 0;
                            // Inutile de faire le test sur le statut de la demande car seules les demandes demande::DEMANDE_VALIDE et demande::DEMANDE_ATTENTE sont controlées
                            // Les autres statuts des demandes retournent automatiquement TRUE (=> C'est ok)
                            if (!$demande->controlenbrejrs($nbrejrscalcule))
                            {
                                // $nbrejrscalcule = Nombre de jours que l'agent aurait dû poser
                                echo "INFO " . $agent->identitecomplete() . " : On a une incohérence : nbrejrscalcule = $nbrejrscalcule  Nb jrs de la demande = " . $demande->nbrejrsdemande() . " (type congés : " . $demande->typelibelle() . ")\n";
                                $olddemandestatut = $demande->statut();
                                // On annule la demande de congés précédemment saisie
                                // => Le solde de congés est automatiquement impacté
                                $demande->statut(demande::DEMANDE_ANNULE);
                                $demande->motifrefus("Annulation suite à un arrêt de travail impactant le nombre de jours de congés");
                                $demande->store();
                                // On refait une nouvelle demande de congés avec les mêmes paramètres => On va recalculer le nombre de jours
                                $newdemande = new demande($dbcon);
                                $newdemande->datedebut($demande->datedebut());
                                $newdemande->moment_debut($demande->moment_debut());
                                $newdemande->datefin($demande->datefin());
                                $newdemande->moment_fin($demande->moment_fin());
                                $newdemande->agentid($demande->agentid());
                                $newdemande->type($demande->type());
                                $newdemandecommentaire = "Demande créée automatiquement suite saisie d'un arrêt de travail.";
                                // On cherche si le commentaire contient déjà le texte au dessus
                                // Si il n'existe pas => On l'ajoute
                                // Sinon on garde le même commentaire
                                if (strpos($demande->commentaire(),$newdemandecommentaire)===false)
                                {
                                    if (trim($demande->commentaire()) != '')
                                    {
                                        $newdemandecommentaire = "\n" . $newdemandecommentaire;
                                    }
                                    $newdemandecommentaire = trim($demande->commentaire()) . $newdemandecommentaire;
                                }
                                else
                                {
                                    $newdemandecommentaire = $demande->commentaire();
                                }
                                $newdemande->commentaire($newdemandecommentaire);

                                $ignoreabsenceautodecla = true;
                                $ignoresoldeinsuffisant = false;
                                // Sauvegarde de la nouvelle demande
                                $resultat = $newdemande->store(null, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                                echo "Le nombre de jours calculé est $nbrejrscalcule et nouvelle demande nb jours demande = " . $newdemande->nbrejrsdemande() . " \n";
                                // Si la sauvegarde s'est bien passée et que le nombre de jours posé dans la nouvelle demande est strictement positif
                                if ($resultat == "" and $nbrejrscalcule>0) 
                                {
                                    // On remet l'ancien statut de la demande dans la nouvelle
                                    $newdemandeid = $newdemande->id();
                                    unset($newdemande);
                                    $newdemande = new demande($dbcon);
                                    $newdemande->load($newdemandeid);
                                    $newdemande->statut($olddemandestatut);
                                    // On enregistre le changement du statut
                                    $resultat = $newdemande->store();
                                    // Si tout s'est bien passé
                                    if ($resultat == "")
                                    {
                                        echo "INFO : " . $agent->identitecomplete() . " => La demande " . $demande->id() . " a été remplacée par la demande " . $newdemande->id() . " car un arrêt de travail a été saisi.\n";
                                    }
                                    // On a eu un problème lors de la sauvegarde du statut
                                    else
                                    {
                                        echo "ERROR : " . $agent->identitecomplete() . " => Le statut de la demande " . $demande->id() . " n'a pu être modifié : $resultat \n";
                                    }
                                }
                                // Si le nombre de jour calculé dans la nouvelle demande est nul => On ne doit pas reposer la nouvelle demande 
                                elseif ($nbrejrscalcule==0)
                                {
                                    echo "INFO : " . $agent->identitecomplete() . " => La demande " . $demande->id() . " est annulée mais aucune demande de remplacement enregistrée car nbrejrscalcule = $nbrejrscalcule \n";
                                }
                                // On a eu un problème lors de l'enregistrement de la nouvelle demande
                                else
                                {
                                    echo "ERROR : " . $agent->identitecomplete() . " => La demande " . $demande->id() . " a été supprimée mais la demande de remplacement n'a pas pu être enregistrée : $resultat \n";
                                }
                            }
                            else
                            {
                                echo "INFO : " . $agent->identitecomplete() . " => La demande " . $demande->id() . " est cohérente => On l'ignore \n";
                            }
                        }
                        // La demande n'est pas un congé => Aucun impact sur le solde de congés. On l'ignore
                        else
                        {
                            echo "INFO : " . $agent->identitecomplete() . " => La demande " . $demande->id() . " n'est pas un congé : " . $demande->type() . " => On l'ignore \n";
                        }
                    }
                }
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