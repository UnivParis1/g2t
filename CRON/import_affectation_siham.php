<?php
    
    // //////////////////////////////////////////////////////////////
    // / ATTENTION : Debug = True ne traite pas tous les agents ////
    // / ni toutes les parties du scripts ////
    // / A MANIPULER AVEC PRUDENCE !!!! ////
    // //////////////////////////////////////////////////////////////
    // $debug=true;
    // //////////////////////////////////////////////////////////////
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
    
    $date = date("Ymd");
    
    echo "Début de l'import des affectations " . date("d/m/Y H:i:s") . "\n";
    
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
        
        // On verifie que tous les fichiers ont un bon format !!!!
        $separateur = ';';
        // Vérification que le fichier d'entree est bien conforme
        // => On le lit en entier et on vérifie qu'un séparateur est bien présent sur chaque ligne non vide...
        $fp = fopen("$modalitefile", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                if (count($ligne_element) == 0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
                {
                    // On doit arréter tout !!!!
                    echo "#######################################################";
                    echo "ALERTE : Le format du fichier $modalitefile n'est pas correct !!! => Erreur dans la ligne $ligne \n";
                    echo "#######################################################";
                    fclose($fp);
                    exit();
                }
            }
        }
        fclose($fp);
        
        // Vérification que le fichier d'entree est bien conforme
        // => On le lit en entier et on vérifie qu'un séparateur est bien présent sur chaque ligne non vide...
        $fp = fopen("$statutfile", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                if (count($ligne_element) == 0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
                {
                    // On doit arréter tout !!!!
                    echo "#######################################################";
                    echo "ALERTE : Le format du fichier $statutfile n'est pas correct !!! => Erreur dans la ligne $ligne \n";
                    echo "#######################################################";
                    fclose($fp);
                    exit();
                }
            }
        }
        fclose($fp);
        
        // Vérification que le fichier d'entree est bien conforme
        // => On le lit en entier et on vérifie qu'un séparateur est bien présent sur chaque ligne non vide...
        $fp = fopen("$structurefile", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                if (count($ligne_element) == 0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
                {
                    // On doit arréter tout !!!!
                    echo "#######################################################";
                    echo "ALERTE : Le format du fichier $structurefile n'est pas correct !!! => Erreur dans la ligne $ligne \n";
                    echo "#######################################################";
                    fclose($fp);
                    exit();
                }
            }
        }
        fclose($fp);
        
        echo "Import des MODALITES D'AFFECTATION \n";
        // Import des affectations-modalite.txt
        $sql = "DELETE FROM W_MODALITE";
        mysql_query($sql);
        $erreur_requete = mysql_error();
        if ($erreur_requete != "")
            echo "DELETE W_MODALITE => $erreur_requete \n";
        
        if (! file_exists($modalitefile)) {
            echo "Le fichier $modalitefile n'existe pas !!! \n";
        } else {
            $fp = fopen("$modalitefile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode($separateur, $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $quotite = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    if (! isset($debug) or $debug == false)
                        echo "harpegeid = $harpegeid   numligne=$numligne   quotite=$quotite   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_MODALITE (HARPEGEID,NUMLIGNE,QUOTITE,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($quotite), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysql_query($sql);
                    $erreur_requete = mysql_error();
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
        mysql_query($sql);
        $erreur_requete = mysql_error();
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
                    $ligne_element = explode($separateur, $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $statut = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    if (! isset($debug) or $debug == false)
                        echo "harpegeid = $harpegeid   numligne=$numligne   statut=$statut   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_STATUT (HARPEGEID,NUMLIGNE,TYPESTATUT,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($statut), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysql_query($sql);
                    $erreur_requete = mysql_error();
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
        mysql_query($sql);
        $erreur_requete = mysql_error();
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
                    $ligne_element = explode($separateur, $ligne);
                    $harpegeid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $idstruct = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    if (! isset($debug) or $debug == false)
                        echo "harpegeid = $harpegeid   numligne=$numligne   structure=$idstruct   datedebut=$datedebut   datefin=$datefin\n";
                    $sql = sprintf("INSERT INTO W_STRUCTURE (HARPEGEID,NUMLIGNE,IDSTRUCT,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($idstruct), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                    
                    mysql_query($sql);
                    $erreur_requete = mysql_error();
                    if ($erreur_requete != "") {
                        echo "INSERT W_STRUCTURE => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }
    }
    
    $sql = sprintf("UPDATE AFFECTATION SET OBSOLETE='O' WHERE DATEDEBUT>='20160101'");
    $query_aff = mysql_query($sql);
    $erreur_requete = mysql_error();
    if ($erreur_requete != "")
        echo "UPDATE OBSOLETE AFFECTATION => $erreur_requete \n";
    
    // $sql = sprintf("SELECT DISTINCT HARPEGEID FROM W_MODALITE WHERE HARPEGEID IN (SELECT DISTINCT HARPEGEID FROM W_STATUT) AND HARPEGEID IN (SELECT DISTINCT HARPEGEID FROM W_STRUCTURE)");
    $sql = sprintf("SELECT DISTINCT HARPEGEID FROM W_MODALITE WHERE HARPEGEID IN (SELECT DISTINCT HARPEGEID FROM W_STATUT) AND HARPEGEID IN (SELECT DISTINCT HARPEGEID FROM W_STRUCTURE) AND HARPEGEID IN (SELECT DISTINCT HARPEGEID FROM AGENT)");
    $query_harpegeid = mysql_query($sql);
    $erreur_requete = mysql_error();
    if ($erreur_requete != "")
        echo "SELECT HARPEGEID FROM W.... => $erreur_requete \n";
    
    while ($harpid = mysql_fetch_row($query_harpegeid)) {
        // ///////////////////////////////////////////////////
        // ///////////////////////////////////////////////////
        // -- PERMET DE NE TESTER QU'UN SEUL DOSSIER !! -- //
        if (isset($debug)) {
            if ($debug == true) {
                if ($harpid[0] != '83940' and $harpid[0] != '9328') {
                    continue;
                }
            }
        }
        // ///////////////////////////////////////////////////
        // ///////////////////////////////////////////////////
        
        echo "-----------------------------------------\n";
        echo "On travaille sur l'agent : " . $harpid[0] . "\n";
        $agent = new agent($dbcon);
        if (! $agent->load($harpid[0])) {
            $tabaffectation = null;
        } else {
            $tabaffectation = $agent->creertimeline();
            echo "Timeline de l'agent " . $agent->harpegeid() . " => " . print_r($tabaffectation, true) . "\n";
        }
        
        foreach ((array) $tabaffectation as $ligneaffectation) {
            $affectation = null;
            $ligne_element = explode(";", $ligneaffectation);
            $affectationid = $ligne_element[0];
            $harpegeid = $ligne_element[1];
            if ($ligne_element[2] != '') // Si c'est un contrat !!!!
            {
                $numcontrat = "1";
            } else {
                // Si le numéro du contrat est vide alors on le force à 0 ==> Ce n'est pas une contrat
                $numcontrat = '0'; // $ligne_element[2]; // Pourrait être remplacé par $numcontrat = 0 car lors de l'insertion SQL, si $numcontrat = '' => SQL prend la valeur par défaut = 0
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
            
            echo "affectationid = $affectationid   harpegeid=$harpegeid   numcontrat=$numcontrat   datemodif=$datemodif datedebut=$datedebut  datefin=$datefin\n";
            if ($fonctions->formatdatedb($datedebut) < '20160101')
                continue;
            
            $sql = sprintf("SELECT DATEMODIFICATION,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE AFFECTATIONID='%s'", $fonctions->my_real_escape_utf8($affectationid));
            // if ($harpegeid == '9328')
            // echo "sql (SELECT) = $sql \n";
            $query_aff = mysql_query($sql);
            $erreur_requete = mysql_error();
            if ($erreur_requete != "")
                echo "SELECT AFFECTATION => $erreur_requete \n";
            
            // -------------------------------
            // Affectation manquante
            // -------------------------------
            if (mysql_num_rows($query_aff) == 0) {
                echo "On est dans le cas ou l'affectation est manquante : $affectationid \n";
                // echo "Date de fin de l'affectation => $datefin \n";
                if (("$datefin" == "") or ("$datefin" == "0000-00-00") or ("$datefin" == "00000000") or ("$datefin" == "00/00/0000"))
                    $datefin = "9999-12-31";
                $sql = sprintf("INSERT INTO AFFECTATION(AFFECTATIONID,HARPEGEID,NUMCONTRAT,DATEDEBUT,DATEFIN,DATEMODIFICATION,STRUCTUREID,NUMQUOTITE,DENOMQUOTITE,OBSOLETE)
    										VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($affectationid), $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numcontrat), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($datemodif), $fonctions->my_real_escape_utf8($structureid), $fonctions->my_real_escape_utf8($numquotite), $fonctions->my_real_escape_utf8($denomquotite), 'N');
                mysql_query($sql);
                $erreur_requete = mysql_error();
                if ($erreur_requete != "")
                    echo "INSERT AFFECTATION => $erreur_requete \n";
                
                // echo "Import_affectation => numquotite = $numquotite denomquotite = $denomquotite \n";
                if ($numquotite == $denomquotite) {
                    $declarationTP = new declarationTP($dbcon);
                    $declarationTP->affectationid($affectationid);
                    $declarationTP->tabtpspartiel(str_repeat("0", 20));
                    // echo "datedebut = $datedebut \n";
                    $declarationTP->datedebut($datedebut);
                    // echo "Datefin de la declaration TP = $datefin \n";
                    if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000"))
                        $datefin = "9999-12-31";
                    // echo "datefin = $datefin \n";
                    $declarationTP->datefin($datefin);
                    $declarationTP->statut("v");
                    $erreur = $declarationTP->store();
                    if ($erreur != "")
                        echo "Erreur dans la declarationTP->store : " . $erreur . "\n";
                }
            }        // -------------------------------------------
            // L'affectation existe déjà
            // -------------------------------------------
            else {
                echo "On est dans le cas ou l'affectation existe : $affectationid \n";
                $affectation = new affectation($dbcon);
                $affectation->load($fonctions->my_real_escape_utf8($affectationid));
                
                // Si tout est pareil.... => On réactive la ligne d'affectation
                if (($fonctions->formatdatedb($affectation->datedebut()) == $fonctions->formatdatedb($datedebut)) and ($fonctions->formatdatedb($affectation->datefin()) == $fonctions->formatdatedb($datefin)) and ($affectation->numquotite() == $numquotite) and ($affectation->structureid() == $structureid) and ($affectation->numcontrat() == $numcontrat)) {
                    echo "Reactivation de la ligne d'affectation car tout est pareil \n";
                    $sql = sprintf("UPDATE AFFECTATION SET OBSOLETE='N' WHERE AFFECTATIONID='%s'", $fonctions->my_real_escape_utf8($affectationid));
                    // if ($harpegeid == '9328')
                    // echo "sql (Statut seul) = $sql \n";
                    mysql_query($sql);
                    $erreur_requete = mysql_error();
                    if ($erreur_requete != "")
                        echo "UPDATE AFFECTATION (Statut seul)=> $erreur_requete \n";
                }            // Il y a au moins une différence
                else {
                    // On réactive la ligne d'affectation (puisqu'elle existe toujours) mais on update avec les bonnes valeurs du fichier
                    echo "On update l'affectation (identifiant = " . $affectationid . ")\n";
                    $sql = sprintf("UPDATE AFFECTATION SET HARPEGEID='%s',NUMCONTRAT='%s',DATEDEBUT='%s',DATEFIN='%s',DATEMODIFICATION='%s',STRUCTUREID='%s',NUMQUOTITE='%s',DENOMQUOTITE='%s',OBSOLETE='%s' WHERE AFFECTATIONID='%s'", $fonctions->my_real_escape_utf8($harpegeid), $fonctions->my_real_escape_utf8($numcontrat), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($datemodif), $fonctions->my_real_escape_utf8($structureid), $fonctions->my_real_escape_utf8($numquotite), $fonctions->my_real_escape_utf8($denomquotite), 'N', $fonctions->my_real_escape_utf8($affectationid));
                    
                    // if ($harpegeid == '9328')
                    // {
                    // echo "sql = $sql \n";
                    // }
                    
                    mysql_query($sql);
                    $erreur_requete = mysql_error();
                    if ($erreur_requete != "")
                        echo "UPDATE AFFECTATION => $erreur_requete \n";
                    
                    // ------------------------------------------------
                    // Cas ou la structure est modifiée
                    // On ne modifie rien car le changement de structure n'a aucun impact sur les autres informations
                    // ------------------------------------------------
                    if ($affectation->structureid() != $structureid) {
                        echo "Changement de structure d'affectation : Ancienne structure = " . $affectation->structureid() . "  Nouvelle structure = " . $structureid . "\n";
                    }
                    
                    // ------------------------------------------------
                    // Cas ou le num contrat est modifié
                    // On ne modifie rien car le changement de numcontrat n'a aucun impact sur les autres informations
                    // Ca n'impacte que le nombre de jour calculé
                    // ------------------------------------------------
                    if ($affectation->numcontrat() != $numcontrat) {
                        echo "Changement de numero de contrat : Ancien numcontrat = " . $affectation->numcontrat() . "  Nouveau numcontrat = " . $numcontrat . "\n";
                    }
                    
                    // ------------------------------------------------
                    // Cas ou la quotité est modifiée
                    // On annule la déclaration de TP correspondante
                    // Si la nouvelle quotité est 100% => on crée la déclaration de TP sur la période d'affectation
                    // ------------------------------------------------
                    if ($affectation->numquotite() != $numquotite) {
                        echo "Cas ou la quotite est modifiee \n";
                        $declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()), $fonctions->formatdate($affectation->datefin()));
                        if (! is_null($declarationliste)) {
                            // Pour chaque declaration => On les annule
                            foreach ($declarationliste as $declaration) {
                                if (strcasecmp($declaration->statut(), "r") != 0) {
                                    
                                    $sql = sprintf("UPDATE DECLARATIONTP SET STATUT='r' WHERE DECLARATIONID='%s'", $fonctions->my_real_escape_utf8($declaration->declarationTPid()));
                                    mysql_query($sql);
                                    $erreur_requete = mysql_error();
                                    if ($erreur_requete != "")
                                        echo "UPDATE DECLARATIONTP (Statut seul)=> $erreur_requete \n";
                                }
                            }
                        }
                        // Si la quotité est à 100% on crée une déclaration de TP
                        if ($numquotite == $denomquotite) {
                            echo "La nouvelle quotité est à 100% \n";
                            $declarationTP = new declarationTP($dbcon);
                            $declarationTP->affectationid($affectationid);
                            $declarationTP->tabtpspartiel(str_repeat("0", 20));
                            // echo "datedebut = $datedebut \n";
                            $declarationTP->datedebut($datedebut);
                            // echo "Datefin de la declaration TP = $datefin \n";
                            if (("$datefin" == "") or ($datefin == "0000-00-00") or ($datefin == "00000000") or ($datefin == "00/00/0000"))
                                $datefin = "9999-12-31";
                            // echo "datefin = $datefin \n";
                            $declarationTP->datefin($datefin);
                            $declarationTP->statut("v");
                            $erreur = $declarationTP->store();
                            if ($erreur != "")
                                echo "Erreur dans la déclarationTP->store : " . $erreur . "\n";
                        } else {
                            // Quotité != 100% donc on ne crée pas de declaration TP
                            echo "La nouvelle quotité n'est pas 100% => On laisse l'agent déclarer son TP\n";
                        }
                    }                // ----------------------------------------------------
                    // Cas ou la date de début est avancée ou la date de fin est reculée
                    // -----------------------------------------------------
                    elseif (($fonctions->formatdatedb($datedebut) < $fonctions->formatdatedb($affectation->datedebut())) or ($fonctions->formatdatedb($datefin) > $fonctions->formatdatedb($affectation->datefin()))) {
                        echo "Cas ou la date de debut est avancee ou de fin reculee \n";
                        // Si on est à 100% => On modifie la date de début et/ou de fin de la déclaration de TP
                        if ($numquotite == $denomquotite) {
                            $declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()), $fonctions->formatdate($affectation->datefin()));
                            if (! is_null($declarationliste)) {
                                foreach ($declarationliste as $declarationTP) {
                                    if (strcasecmp($declarationTP->statut(), "r") != 0) {
                                        $sql = sprintf("UPDATE DECLARATIONTP SET DATEDEBUT='%s', DATEFIN='%s' WHERE DECLARATIONID='%s'", $fonctions->my_real_escape_utf8($fonctions->formatdatedb($datedebut)), $fonctions->my_real_escape_utf8($fonctions->formatdatedb($datefin)), $fonctions->my_real_escape_utf8($declarationTP->declarationTPid()));
                                        mysql_query($sql);
                                        $erreur_requete = mysql_error();
                                        if ($erreur_requete != "") {
                                            echo "UPDATE DECLARATIONTP (Date debut avancee ou date fin reculee)=> $erreur_requete \n";
                                        }
                                    }
                                }
                            }
                        }
                    }                // ----------------------------------------------------
                    // Cas ou la date de début est reculée ou la date de fin est avancée
                    // -----------------------------------------------------
                    elseif (($fonctions->formatdatedb($datedebut) > $fonctions->formatdatedb($affectation->datedebut())) or ($fonctions->formatdatedb($datefin) < $fonctions->formatdatedb($affectation->datefin()))) {
                        echo "Cas ou la date de debut est reculee ou de fin avancee \n";
                        // Quelque soit la quotité => On réduit la période de la déclaration de TP
                        $declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()), $fonctions->formatdate($affectation->datefin()));
                        if (! is_null($declarationliste)) {
                            foreach ($declarationliste as $declarationTP) {
                                if (strcasecmp($declarationTP->statut(), "r") != 0) {
                                    echo "datedebut = $datedebut    datefin = $datefin   DECLARATIONID = " . $declarationTP->declarationTPid() . "\n";
                                    $sql = sprintf("UPDATE DECLARATIONTP SET DATEDEBUT='%s', DATEFIN='%s' WHERE DECLARATIONID='%s'", $fonctions->my_real_escape_utf8($fonctions->formatdatedb($datedebut)), $fonctions->my_real_escape_utf8($fonctions->formatdatedb($datefin)), $fonctions->my_real_escape_utf8($declarationTP->declarationTPid()));
                                    echo "SQL UPDATE DECLARATIONTP => $sql \n";
                                    mysql_query($sql);
                                    $erreur_requete = mysql_error();
                                    if ($erreur_requete != "") {
                                        echo "UPDATE DECLARATIONTP (Date debut reculee ou date fin avancee)=> $erreur_requete \n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Pour toutes les affectations obsolètes
    // qui ont des déclarations non supprimées
    // On doit supprimer les déclarations de TP ===> ON NE TOUCHE PAS AUX CONGES !!!!!!
    $sql = "SELECT AFFECTATION.AFFECTATIONID,AFFECTATION.HARPEGEID,DECLARATIONTP.DECLARATIONID FROM AFFECTATION,DECLARATIONTP ";
    $sql = $sql . " WHERE AFFECTATION.OBSOLETE='O'";
    $sql = $sql . "   AND AFFECTATION.AFFECTATIONID=DECLARATIONTP.AFFECTATIONID ";
    $sql = $sql . "   AND DECLARATIONTP.STATUT != 'r'";
    // echo "$sql (obsolete) = $sql \n";
    $query = mysql_query($sql);
    $erreur_requete = mysql_error();
    if ($erreur_requete != "")
        echo "SELECT AFFECTATION OBSOLETE => $erreur_requete \n";
    if (mysql_num_rows($query) > 0) // Il y a des affectations obsoletes
    {
        if (! isset($debug) or ($debug == false)) {
            echo "ATTENTION : Il y a des affectations obsoletes \n";
            while ($result = mysql_fetch_row($query)) {
                $sql = sprintf("UPDATE AFFECTATION SET DATEMODIFICATION='%s' WHERE AFFECTATIONID = '%s'", $fonctions->my_real_escape_utf8(date("Ymd")), $fonctions->my_real_escape_utf8($result[0]));
                echo "SQL (UPDATE DATEMODIF) => $sql \n";
                mysql_query($sql);
                $erreur_requete = mysql_error();
                if ($erreur_requete != "") {
                    echo "UPDATE AFFECTATION (Mise du date de modification)=> $erreur_requete \n";
                }
                $sql = sprintf("UPDATE DECLARATIONTP SET STATUT='r' WHERE DECLARATIONID='%s'", $fonctions->my_real_escape_utf8($result[2]));
                echo "SQL (UPDATE STATUT) => $sql \n";
                mysql_query($sql);
                $erreur_requete = mysql_error();
                if ($erreur_requete != "") {
                    echo "UPDATE DECLARATIONTP (Mise du Statut à 'r')=> $erreur_requete \n";
                }
            }
        }
    }// Pas d'affectation obsolete !!!
    else {
        echo "Pas d'affectation obsolete.... \n";
    }
    echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";
    
    exit();
    
    // ///////////////////////////////////////////////////////////////////////////
    // ///////////////////////////////////////////////////////////////////////////
    // ///////////////////////////////////////////////////////////////////////////
    // ///////////////// A VOIR CE QUE L'ON FAIT DE CA !!!! //////////////////////
    // ///////////////////////////////////////////////////////////////////////////
    // ///////////////////////////////////////////////////////////////////////////
    
    // on doit supprimer les déclarations de temps partiels => suppression des demandes
    $sql = "SELECT AFFECTATION.AFFECTATIONID,AFFECTATION.HARPEGEID FROM AFFECTATION,DECLARATIONTP ";
    $sql = $sql . " WHERE AFFECTATION.OBSOLETE='O'";
    $sql = $sql . "   AND AFFECTATION.AFFECTATIONID=DECLARATIONTP.AFFECTATIONID ";
    $sql = $sql . "   AND DECLARATIONTP.STATUT != 'r'";
    // echo "$sql (obsolete) = $sql \n";
    $query = mysql_query($sql);
    $erreur_requete = mysql_error();
    if ($erreur_requete != "")
        echo "SELECT AFFECTATION OBSOLETE => $erreur_requete \n";
    if (mysql_num_rows($query) > 0) // Il y a des affectation obsoletes
    {
        echo "ATTENTION : Il y a des affectations obsoletes \n";
        while ($result = mysql_fetch_row($query)) {
            // On recherche si une affectation avec les mêmes critères existe
            echo "On regarde s'il y a une affectation identique pour l'ancienne affectation " . $result[0] . " (HarpegeId = " . $result[1] . ") : ";
            $sql = "SELECT AFFNEW.AFFECTATIONID ";
            $sql = $sql . " FROM AFFECTATION AFFNEW, AFFECTATION AFFOLD ";
            $sql = $sql . " WHERE AFFNEW.DATEDEBUT = AFFOLD.DATEDEBUT ";
            $sql = $sql . "   AND (AFFNEW.DATEFIN = AFFOLD.DATEFIN OR AFFOLD.DATEFIN >= '" . date('Y-m-d') . "') "; // AFFOLD.DATEFIN = '9999-12-31') ";
            $sql = $sql . "   AND AFFNEW.STRUCTUREID = AFFOLD.STRUCTUREID ";
            $sql = $sql . "   AND AFFNEW.NUMQUOTITE = AFFOLD.NUMQUOTITE ";
            $sql = $sql . "   AND AFFNEW.DENOMQUOTITE = AFFOLD.DENOMQUOTITE ";
            $sql = $sql . "   AND AFFNEW.OBSOLETE = 'N' ";
            $sql = $sql . "   AND AFFNEW.AFFECTATIONID != AFFOLD.AFFECTATIONID ";
            $sql = $sql . "   AND AFFNEW.HARPEGEID = AFFOLD.HARPEGEID ";
            $sql = $sql . "   AND AFFOLD.AFFECTATIONID = " . $result[0];
            $query2 = mysql_query($sql, $dbcon);
            // echo "SQL = " . $sql . "\n";
            $erreur_requete = mysql_error();
            if ($erreur_requete != "")
                echo "SELECT AFFECTATION OBSOLETE => $erreur_requete \n";
            if (mysql_num_rows($query2) > 0) // Il y a une affectations nouvelles avec les mêmes critères qu'une ancienne
            {
                $result2 = mysql_fetch_row($query2);
                echo "OUI => nouvelle affectation = " . $result2[0] . "\n";
                $affnew = new affectation($dbcon);
                $affold = new affectation($dbcon);
                $affnew->load($result2[0]); // On charge la nouvelle affectation
                $affold->load($result[0]); // On charge l'ancienne affectation
                                           // echo "Avant le test 100% quotité \n";
                if ($affold->numquotite() != $affold->denumquotite()) // Si ce n'est pas une affectation à 100%, on va cloner les demandes de TP associées à l'ancienne Affectation
                {
                    // echo "On charge les declarationTP Old \n ";
                    $declarationliste = $affold->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
                    $oldTP = new $declarationTP($dbcon);
                    foreach ($declarationliste as $oldTP) {
                        // echo "On va cloner les nouvelle declarationTP \n";
                        $newTP = new declarationTP($dbcon);
                        $newTP->affectationid($affnew->affectationid());
                        $newTP->tabtpspartiel($oldTP->tabtpspartiel());
                        $newTP->datedebut($oldTP->datedebut());
                        $newTP->datefin($oldTP->datefin());
                        $newTP->statut($oldTP->statut());
                        // $newTP->datedemande($oldTP->datedemande()); => Initialisé dans la fonction STORE
                        // $newTP->datestatut($oldTP->datestatut()); => Initialisé dans la fonction STORE
                        // echo "Avant le store ... \n";
                        $erreur = $newTP->store();
                        if ($erreur != "") {
                            echo "ERREUR DANS LE STORE (CLONE DU TP " . $oldTP->declarationTPid() . ") => $erreur \n";
                        }
                    }
                }
                // On a maintenant les TP qui sont déclarés comme dans l'ancienne affectation
                // On va les recharger
                $affnew = new affectation($dbcon);
                $affold = new affectation($dbcon);
                // echo "Avant le load affnew ..... \n";
                $affnew->load($result2[0]); // On charge la nouvelle affectation
                                            // echo "Avant le load affold ..... \n";
                $affold->load($result[0]); // On charge l'ancienne affectation
                                           // echo "Avant le declartationTP pour Old \n";
                $olddeclarationliste = $affold->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
                // echo "Avant le declartationTP pour New \n";
                $newdeclarationliste = $affnew->declarationTPliste($fonctions->formatdate($affold->datedebut()), $fonctions->formatdate($affold->datefin()));
                // echo "olddeclarationliste = "; print_r($olddeclarationliste); echo "\n";
                // echo "newdeclarationliste = "; print_r($newdeclarationliste); echo "\n";
                if (! is_null($olddeclarationliste)) {
                    $indexnewTP = 0;
                    foreach ($olddeclarationliste as $oldTP) {
                        
                        $newTP = $newdeclarationliste[$indexnewTP];
                        // echo "newTP->declarationTPid() = " . $newTP->declarationTPid() . " oldTP->declarationTPid() = " . $oldTP->declarationTPid() . "\n";
                        // On va maintenant raccrocher les anciennes demandes de congés à la nouvelle declarationTP
                        $sql = "UPDATE DEMANDEDECLARATIONTP SET DECLARATIONID = " . $newTP->declarationTPid() . " WHERE DECLARATIONID = " . $oldTP->declarationTPid() . "  ";
                        $sql = $sql . " AND DEMANDEID IN (SELECT DEMANDEID FROM DEMANDE WHERE DATEFIN <= '" . $fonctions->formatdatedb($newTP->datefin()) . "')";
                        // echo "SQL (UPDATE DEMANDEDECLARATIONTP....) = " . $sql . "\n";
                        $result_update = mysql_query($sql, $dbcon);
                        $erreur_requete = mysql_error();
                        $nbreligne = mysql_affected_rows(); // => Savoir combien de lignes ont été modifiées
                        echo "\tIl y a $nbreligne demandes de congés qui ont été déplacées. \n";
                        if ($erreur_requete != "") {
                            echo "ERREUR DANS LE DEPLACEMENT DES DEMANDE => Ancien TP.ID=" . $oldTP->declarationTPid() . "  Nouveau TP.ID=" . $newTP->declarationTPid() . "\n";
                        }
                        $indexnewTP = $indexnewTP + 1;
                    }
                }
            } else {
                echo "NON \n";
            }
            
            unset($affectation);
            $affectation = new affectation($dbcon);
            $affectation->load($result[0]);
            $declarationliste = $affectation->declarationTPliste($fonctions->formatdate($affectation->datedebut()), $fonctions->formatdate($affectation->datefin()));
            if (! is_null($declarationliste)) {
                foreach ($declarationliste as $declaration) {
                    $declaration->statut("r");
                    $msg = $declaration->store();
                    if ($msg != "")
                        echo "Problème lors de la suppression de la déclaration " . $declaration->declarationTPid() . " : " . $msg . " \n";
                }
            }
        }
    } else {
        echo "Pas d'affectation obsolete.... \n";
    }
    
    echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";

?>