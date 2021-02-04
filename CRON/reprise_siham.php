<?php
    require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    
    require_once ("../html/class/agent.php");
    require_once ("../html/class/structure.php");
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    // require_once("../html/class/autodeclaration.php");
    // require_once("../html/class/dossier.php");
    require_once ("../html/class/tcpdf/tcpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
    
    $fonctions = new fonctions($dbcon);
    
    // ///// VERIFICATION POUR NE PAS POUVOIR LANCER 2 FOIS LA REPRISE SUR LA BASE
    $sql = "SELECT AFFECTATIONID FROM AFFECTATION WHERE AFFECTATIONID LIKE '%\_%\_%'";
    $queryverif = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        error_log(basename(__FILE__) . " " . $erreur_requete);
    if (mysqli_num_rows($queryverif) > 0) // Si des affectationID au format %_%_% sont déja présents => On ne fait pas la reprise !! Elle est déja faite
    {
        echo "La reprise est déjà faite !!!!! \n";
        exit();
    }
    
    $date = date("Ymd");
    
    $modalitefile = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_affectations_modalite_$date.dat";
    $statutfile = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_affectations_status_$date.dat";
    $structurefile = dirname(__FILE__) . "/../INPUT_FILES_V3/siham_affectations_structures_$date.dat";
    
    $skipreadfile = false;
    if (isset($argv[1])) {
        if ($argv[1] == 'noimport')
            $skipreadfile = true;
    }
    
    if (! $skipreadfile) {
        $exit = false;
        echo "Vérification existance des fichiers....\n";
        if (! file_exists($modalitefile)) {
            echo "Le fichier $modalitefile n'existe pas !!! \n";
            $exit = true;
        }
        if (! file_exists($statutfile)) {
            echo "Le fichier $statutfile n'existe pas !!! \n";
            $exit = true;
        }
        if (! file_exists($structurefile)) {
            echo "Le fichier $structurefile n'existe pas !!! \n";
            $exit = true;
        }
        if ($exit == true) {
            echo "Il manque au moins un fichier => Aucune mise à jour réalisée !!! \n";
            exit();
        }
        
        echo "Import des MODALITES D'AFFECTATION \n";
        // Import des affectations-modalite.txt
        $sql = "DELETE FROM W_MODALITE";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE W_MODALITE => $erreur_requete \n";
        
        if (! file_exists($modalitefile)) {
            echo "Le fichier $modalitefile n'existe pas !!! \n";
        } else {
            $fp = fopen("$modalitefile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode(";", $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $quotite = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    echo "harpegeid = $harpegeid   numligne=$numligne   quotite=$quotite   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_MODALITE (HARPEGEID,NUMLIGNE,QUOTITE,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($quotite), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "INSERT W_MODALITE => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }
        
        echo "Import des STATUTS D'AFFECTATION \n";
        // Import des affectations-statut.txt
        $sql = "DELETE FROM W_STATUT";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE W_STATUT => $erreur_requete \n";
        
        // On charge la table des absences HARPEGE avec le fichier
        if (! file_exists($statutfile)) {
            echo "Le fichier $statutfile n'existe pas !!! \n";
        } else {
            $fp = fopen("$statutfile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode(";", $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $statut = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    echo "harpegeid = $harpegeid   numligne=$numligne   statut=$statut   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_STATUT (HARPEGEID,NUMLIGNE,TYPESTATUT,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($statut), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "INSERT W_STATUT => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }
        
        echo "Import des STRUCTURES D'AFFECTATION \n";
        // Import des affectations-structure.txt
        $sql = "DELETE FROM W_STRUCTURE";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "DELETE W_STRUCTURE => $erreur_requete \n";
        
        // On charge la table des absences HARPEGE avec le fichier
        if (! file_exists($structurefile)) {
            echo "Le fichier $structurefile n'existe pas !!! \n";
        } else {
            $fp = fopen("$structurefile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode(";", $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $idstruct = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    echo "harpegeid = $harpegeid   numligne=$numligne   structure=$idstruct   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_STRUCTURE (HARPEGEID,NUMLIGNE,IDSTRUCT,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($idstruct), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "INSERT W_STRUCTURE => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }
    }
    
    $agentprecedent = new agent($dbcon);
    $indicetabaffectation = 0;
    
    $sql = "SELECT AFFECTATION.AFFECTATIONID, AFFECTATION.HARPEGEID, AFFECTATION.DATEDEBUT, AFFECTATION.DATEFIN 
    			FROM AFFECTATION, AGENT 
    			WHERE DATEFIN > '2015-12-31' 
    			  AND OBSOLETE ='N'
    			  AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID
    			  ORDER BY AFFECTATION.HARPEGEID";
    // echo "SQL (SELECT 1) = " . $sql . "\n";
    $query = mysqli_query($dbcon, $sql);
    while ($affectation = mysqli_fetch_row($query)) {
        $agent = new agent($dbcon);
        $agent->load($affectation[1]);
        
        echo "Je travaille sur l'agent : " . $agent->harpegeid() . " => " . $agent->identitecomplete() . "\n";
        // Pour chaque affectation que l'on a récupéré => On met la date de fin à 31/12/2015
        $sql = "UPDATE AFFECTATION SET DATEFIN = '2015-12-31' WHERE AFFECTATIONID = '" . $affectation[0] . "'";
        // echo "SQL (UPDATE 1) = " . $sql . "\n";
        mysqli_query($dbcon, $sql);
        
        if ($agent->harpegeid() != $agentprecedent->harpegeid()) {
            /*
             * if (!is_null($agentprecedent->harpegeid()))
             * {
             * echo "Ancien agent => contrôle des demandes de congés / TP\n";
             * $agentprecedent->controlecongesTP('20140901', '20160831');
             * }
             */
            echo "Nouvel agent => creation de la timeline\n";
            // Création de la timeLine pour l'agent courant !!
            $tabaffectation = $agent->creertimeline();
            $indicetabaffectation = 0;
        } else {
            echo "On est dans le même agent " . $agentprecedent->harpegeid() . " et indicetabaffectation = $indicetabaffectation \n";
        }
        
        if ($agent->harpegeid() == '52257') {
            echo "Agent a surveiller => " . $agent->harpegeid() . "\n";
            print_r($tabaffectation);
            echo "\n";
        }
        
        if (! is_array($tabaffectation) or (! isset($tabaffectation[$indicetabaffectation]))) {
            echo "Oups !! Pas de nouvelle affectation pour cet agent (" . $agent->harpegeid() . " => " . $agent->identitecomplete() . ") \n";
        } else {
            echo "Recuperation de la ligne $indicetabaffectation du tableau de la timeline \n";
            $ligneaffectation = $tabaffectation[$indicetabaffectation];
            echo "ligneaffectation = $ligneaffectation \n";
            $ligne_element = explode(";", $ligneaffectation);
            $affectationid = $ligne_element[0];
            $harpegeid = $ligne_element[1];
            if ($ligne_element[2] != '') // Si c'est un contrat !!!!
            {
                $numcontrat = "1";
            } else {
                $numcontrat = $ligne_element[2];
            }
            $datedebut = $ligne_element[3];
            $datefin = $ligne_element[4];
            if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000")) {
                $datefin = "9999-12-31";
            }
            $datemodif = $ligne_element[5];
            $structureid = $ligne_element[6];
            $numquotite = $ligne_element[7];
            $denomquotite = $ligne_element[8];
            // Insertion de la nouvelle affectation SIHAM
            $sql = sprintf("INSERT INTO AFFECTATION(AFFECTATIONID,HARPEGEID,NUMCONTRAT,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE)
    							VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($affectationid), $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numcontrat), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($datemodif), $fonctions->my_real_escape_utf8($structureid), $fonctions->my_real_escape_utf8($numquotite), $fonctions->my_real_escape_utf8($denomquotite), 'N');
            echo "execution de l'insert AFFECTATION... => SQL=$sql" . "\n";
            mysqli_query($dbcon, $sql);
            $erreur_requete = mysqli_error($dbcon);
            if ($erreur_requete != "") {
                echo "INSERT AFFECTATION SIHAM (Id de l'agent precedent=" . $agentprecedent->harpegeid() . " et harpegeid=" . $agent->harpegeid() . ") => $erreur_requete \n";
            }
            
            // $affectation[0] = Identifiant de l'ancienne affectation HARPEGE
            $sql = "SELECT DECLARATIONID, AFFECTATIONID, TABTPSPARTIEL, DATEDEMANDE, DATEDEBUT, DATEFIN, DATESTATUT, STATUT FROM DECLARATIONTP WHERE AFFECTATIONID = '" . $affectation[0] . "' AND DATEFIN > '2015-12-31' AND (STATUT<>'R' OR STATUT<>'r') ";
            echo "SQL SELECT FROM DECLARATIONTP => " . $sql . "\n";
            $querydeclaration = mysqli_query($dbcon, $sql);
            while ($declarationtp = mysqli_fetch_row($querydeclaration)) {
                echo "Je suis dans la boucle pour la declarationTP pour mettre la date de fin a 31/12/2015 : " . $declarationtp[0] . "\n";
                // Pour chaque declarationTP que l'on a récupéré => On met la date de fin à 31/12/2015
                $sql = "UPDATE DECLARATIONTP SET DATEFIN = '2015-12-31' WHERE DECLARATIONID = '" . $declarationtp[0] . "'";
                mysqli_query($dbcon, $sql);
                
                // Si la déclarationTP commence avant le 01/01/2016 => On fixe à 01/01/2016 sinon on remet la même date de début
                if ($declarationtp[3] < '2016-01-01') {
                    $datedebutTP = "2016-01-01";
                } else {
                    $datedebutTP = $declarationtp[4];
                }
                // On regarde si c'est cohérent entre numquotite et le tableau des déclarations de TP
                $nombrededemiejrs = substr_count($declarationtp[2], '1');
                $nombretheoriquededemiejrs = (100 - $numquotite) / 5;
                echo "nombretheoriquededemiejrs = $nombretheoriquededemiejrs    nombrededemiejrs = $nombrededemiejrs  \n";
                
                if ($nombretheoriquededemiejrs != $nombrededemiejrs) {
                    echo "Detection incoherence : numquotite = $numquotite  declarationtp[2] = $declarationtp[2] => Pas de création de la déclaration de TP\n";
                } else {
                    // Création de la nouvelle délcaration de TP sur la nouvelle affectation SIHAM
                    $sql = sprintf("INSERT INTO DECLARATIONTP(AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT) 
    								VALUES('%s','%s','%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($affectationid), $fonctions->my_real_escape_utf8($declarationtp[2]), $fonctions->my_real_escape_utf8($declarationtp[3]), $fonctions->my_real_escape_utf8($datedebutTP), $fonctions->my_real_escape_utf8($declarationtp[5]), $fonctions->my_real_escape_utf8($declarationtp[6]), $fonctions->my_real_escape_utf8($declarationtp[7]));
                    echo "execution de l'insert DECLARATIONTP... => SQL=$sql" . "\n";
                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "INSERT DECLARATIONTP SIHAM (harpegeid=" . $agent->harpegeid() . ") => $erreur_requete \n";
                    }
                }
            }
        }
        echo "agentprecedent = " . $agentprecedent->harpegeid() . "   Maintenant il devient : " . $agent->harpegeid() . "\n";
        $agentprecedent = $agent;
        $indicetabaffectation = $indicetabaffectation + 1;
        echo "On est passe à l'indice suivant de la tabaffectation => " . $indicetabaffectation . "\n";
    }
    echo "Fin de l'import des affectations avec SIHAM \n";
?>
