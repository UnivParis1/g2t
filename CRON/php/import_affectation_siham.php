<?php

    // //////////////////////////////////////////////////////////////
    // / ATTENTION : Debug = True ne traite pas tous les agents ////
    // / ni toutes les parties du scripts ////
    // / A MANIPULER AVEC PRUDENCE !!!! ////
    // //////////////////////////////////////////////////////////////
    // $debug=true;
    // //////////////////////////////////////////////////////////////
    
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");
    
    $fonctions = new fonctions($dbcon);

    $date = date("Ymd");

    echo "Début de la création des affectations " . date("d/m/Y H:i:s") . "\n";

    $modalitefile = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_affectations_modalite_$date.dat";
    $statutfile = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_affectations_status_$date.dat";
    $situationfile = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_affectations_situation_$date.dat";
    $structurefile = $fonctions->g2tbasepath() . "/INPUT_FILES_V3/siham_affectations_structures_$date.dat";

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
        if (! file_exists($situationfile)) {
            echo "Le fichier $situationfile n'existe pas !!! \n";
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
        $fp = fopen("$situationfile", "r");
        while (! feof($fp)) {
            $ligne = fgets($fp); // lecture du contenu de la ligne
            if (trim($ligne) != "") {
                $ligne_element = explode($separateur, $ligne);
                if (count($ligne_element) == 0) // Si la ligne (qui n'est pas vide) ne contient aucun caractère separateur => la structure du fichier n'est pas bonne
                {
                    // On doit arréter tout !!!!
                    echo "#######################################################";
                    echo "ALERTE : Le format du fichier $situationfile n'est pas correct !!! => Erreur dans la ligne $ligne \n";
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

        echo "Import des MODALITES D'AFFECTATION (QUOTITE)\n";
        // Import des affectations-modalite.txt
        $sql = "DELETE FROM QUOTITE";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "Error : DELETE QUOTITE => $erreur_requete \n";
                
        if (! file_exists($modalitefile)) {
            echo "Le fichier $modalitefile n'existe pas !!! \n";
        } else {
            
            $agent = new agent($dbcon);
            $currentagent = null;
            $fp = fopen("$modalitefile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode($separateur, $ligne);
                    $agentid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $numquotite = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);

                    if ($agent->agentid() != $agentid) 
                    //if ($currentagent != $agentid )
                    {
                        $currentagent = $agentid;
                        $agent = new agent($dbcon);
                        if (!$agent->load($agentid))
                        {
                            echo "L'agent $agentid n'existe pas dans la base. On ne charge pas sa quotité \n";
                            continue;
                        }
                        //echo "Le load est ok pour l'agent " . $agent->agentid() . "   agentid = $agentid \n";
                    }
                     
                    
                    ////////////////////////////////////////////////////////////////////////////
                    // Les déclarations de TP qui se terminent avant 2016 ne sont pas créées.
                    if ($fonctions->formatdatedb($datefin) < $fonctions->formatdatedb('01/01/2016'))
                    {
                        //echo "La date de fin est avant le 01/01/2016 \n";
                        continue;
                    }
                    ////////////////////////////////////////////////////////////////////////////
                    
                    if (! isset($debug) or $debug == false)
                    {
                        echo "agentid = $agentid   numligne=$numligne   quotite=$numquotite   datedebut=$datedebut   datefin=$datefin\n";
                    }
                    $sql = sprintf("INSERT INTO QUOTITE (AGENTID,NUMLIGNE,QUOTITE,DATEDEBUT,DATEFIN) 
                                    VALUES('%s','%s','%s','%s','%s')", 
                           $fonctions->my_real_escape_utf8($agentid), 
                           $fonctions->my_real_escape_utf8($numligne), 
                           $fonctions->my_real_escape_utf8($numquotite), 
                           $fonctions->my_real_escape_utf8($datedebut), 
                           $fonctions->my_real_escape_utf8($datefin));

                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "Error : INSERT QUOTITE => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                    
                    // On traite ICI le changement de quotité
                    // Si la quotité est à 100% on crée une déclaration de TP
                    if ($numquotite == '100') 
                    {
                        echo "La quotité est à 100% \n";

                        // On regarde si une déclarationTP existe déjà pour cet agent/numligne
                        $declarationTP = new declarationTP($dbcon);
                        $sql = sprintf("SELECT DECLARATIONID 
                                        FROM DECLARATIONTP 
                                        WHERE AGENTID = '%s' AND NUMLIGNEQUOTITE = '%s'",
                               $fonctions->my_real_escape_utf8($agentid), 
                               $fonctions->my_real_escape_utf8($numligne));
                        $query_decla  = mysqli_query($dbcon, $sql);
                        $erreur_requete = mysqli_error($dbcon);
                        if ($erreur_requete != "")
                        {
                            echo "Error : SELECT DECLARATIONTP A 100% => $erreur_requete \n";
                        }
                        
                        if (mysqli_num_rows($query_decla) == 0) {
                            echo "Pas de declarationTP pour l'agent $agentid et numligne $numligne dans la table DECLARATIONTP => On la crée \n";
                            $declarationTP->agentid($agentid);
                            $declarationTP->numlignequotite($numligne);
                            $declarationTP->tabtpspartiel(str_repeat("0", 20));
                            $declarationTP->datedebut($datedebut);
                            $declarationTP->datefin($datefin);
                            $declarationTP->statut(declarationTP::DECLARATIONTP_VALIDE);
                            $erreur = $declarationTP->store();
                            if ($erreur != "")
                                echo "Error : Erreur dans la déclarationTP->store : " . $erreur . "\n";
                        }
                        else // Il y a une déclarationTP pour cet agent/numligne
                        {
                            echo "Il y a une declarationTP pour l'agent $agentid et numligne $numligne dans la table DECLARATIONTP => On la charge \n";
                            // On sait qu'il y a en qu'une seul !!!
                            $result = mysqli_fetch_row($query_decla);
                            $declarationid = "$result[0]";
                            $declarationTP->load($declarationid);
                            $declarationTP->tabtpspartiel(str_repeat("0", 20));
                            $declarationTP->datedebut($datedebut);
                            $declarationTP->datefin($datefin);
                            $declarationTP->statut(declarationTP::DECLARATIONTP_VALIDE);
                            $erreur = $declarationTP->store();
                            if ($erreur != "")
                                echo "Error : Erreur dans la déclarationTP->store : " . $erreur . "\n";
                        }
                    } else {
                        echo "La quotité n'est pas à 100% \n";
                        
                        // Quotité != 100% donc on ne crée pas de declaration TP
                        // On cherche si une declarationTP existe 
                        $declarationTP = new declarationTP($dbcon);
                        $sql = sprintf("SELECT DECLARATIONID 
                                        FROM DECLARATIONTP 
                                        WHERE AGENTID = '%s' 
                                          AND NUMLIGNEQUOTITE = '%s' 
                                          AND STATUT IN ('%s','%s')",
                                $fonctions->my_real_escape_utf8($agentid), 
                                $fonctions->my_real_escape_utf8($numligne),
                                $fonctions->my_real_escape_utf8(declarationTP::DECLARATIONTP_VALIDE),$fonctions->my_real_escape_utf8(declarationTP::DECLARATIONTP_ATTENTE));
                        $query_decla  = mysqli_query($dbcon, $sql);
                        $erreur_requete = mysqli_error($dbcon);
                        if ($erreur_requete != "")
                        {
                            echo "Error : SELECT DECLARATIONTP NON 100% => $erreur_requete \n";
                        }
                        if (mysqli_num_rows($query_decla) == 0) {
                            echo "Pas de declarationTP pour l'agent $agentid et numligne $numligne dans la table DECLARATIONTP => On laisse l'agent déclarer son TP \n";
                        }
                        else {
                            while  ($result = mysqli_fetch_row($query_decla))
                            {
                                $declarationid = "$result[0]";
                                //echo "On va charger la déclarationTP id = $declarationid \n";
                                $declarationTP->load($declarationid);
                                echo "L'ancienne quotité (dans le tableau) = " . $declarationTP->tabtptoquotite() . " et la nouvelle = $numquotite \n";
                                if ($declarationTP->tabtptoquotite() == $numquotite)
                                {
                                    // La nouvelle quotité est la même que l'ancienne => On ne touche rien
                                }
                                else 
                                {
                                    // L'ancienne quantité est != de la nouvelle => On annule la déclarationTP
                                    $declarationTP->statut(declarationTP::DECLARATIONTP_REFUSE);
                                    $erreur = $declarationTP->store();
                                    if ($erreur != "")
                                    {
                                        echo "Error : Erreur dans la déclarationTP->store : " . $erreur . "\n";
                                    }
                                }
                            }
                        }
                    }
                    
                    ///////////////////////////////////////////////////////////////////////
                    // On va traiter ici le changement de date début et/ou de date de fin
                    ///////////////////////////////////////////////////////////////////////
                    $sql = sprintf("SELECT DECLARATIONID 
                                    FROM DECLARATIONTP 
                                    WHERE AGENTID = '%s' 
                                      AND NUMLIGNEQUOTITE = '%s' 
                                      AND STATUT IN ('%s','%s')
                                      AND (DATEDEBUT < '%s'
                                      OR DATEFIN > '%s')",
                           $fonctions->my_real_escape_utf8($agentid), 
                           $fonctions->my_real_escape_utf8($numligne),
                           $fonctions->my_real_escape_utf8(declarationTP::DECLARATIONTP_VALIDE),$fonctions->my_real_escape_utf8(declarationTP::DECLARATIONTP_ATTENTE),
                           $datedebut,
                           $datefin);
                    $query_decla  = mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "")
                    {
                        echo "Error : SELECT DECLARATIONTP MODIF DEBUT FIN => $erreur_requete \n";
                    }
                    if (mysqli_num_rows($query_decla) == 0) {
                        echo "Pas de declarationTP pour l'agent $agentid et numligne $numligne avec une date début et fin différente \n";
                    }
                    else {
                        while  ($result = mysqli_fetch_row($query_decla))
                        {
                            unset($declarationTP);
                            $declarationTP = new declarationTP($dbcon);
                            $declarationTP->load($result[0]);
                            echo "DeclarationTP " . $result[0] . " chargée pour l'agent " . $declarationTP->agentid() .  " pour déplacer la date début ou fin \n";
                            // On va bouger la date de début de la déclarationTP
                            if ($fonctions->formatdatedb($declarationTP->datedebut()) < $datedebut)
                            {
                                echo "On déplace la date de début \n";
                                $declarationTP->datedebut($datedebut);
                            }
                            if ($fonctions->formatdatedb($declarationTP->datefin()) > $datefin)
                            {
                                echo "On déplace la date de fin \n";
                                $declarationTP->datefin($datefin);
                            }
                            $declarationTP->store();
                            echo "Déplacement terminé ! \n";
                        }
                    } 
                }
            }
            fclose($fp);
            
        }

        echo "Import des STATUTS D'AFFECTATION (NUMERO DE CONTRAT/TITULAIRE)\n";
        // Import des affectations-statut.txt
        $sql = "DELETE FROM STATUT";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "Error : DELETE STATUT => $erreur_requete \n";

        // On charge la table des statut avec le fichier
        if (! file_exists($statutfile)) {
            echo "Le fichier $statutfile n'existe pas !!! \n";
        } else {
            $fp = fopen("$statutfile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode($separateur, $ligne);
                    $agentid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $codecontrat = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    if (! isset($debug) or $debug == false)
                    {
                        echo "agentid = $agentid   numligne=$numligne   codecontrat=$codecontrat   datedebut=$datedebut   datefin=$datefin\n";
                    }
                    
                    $agent = new agent($dbcon);
                    if (!$agent->load($agentid))
                    {
                        echo "L'agent $agentid n'existe pas dans la base. On ne charge pas son statut  \n";
                        continue;
                    }

                    $sql = sprintf("INSERT INTO STATUT (AGENTID,NUMLIGNE,CODECONTRAT,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($agentid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($codecontrat), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));

                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "Error : INSERT STATUT => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }

        echo "Import des situations administratives \n";
        // Import des affectations-statut.txt
        $sql = "DELETE FROM SITUATIONADMIN";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "Error : DELETE SITUATIONADMIN => $erreur_requete \n";
            
        // On charge la table des statut avec le fichier
        if (! file_exists($situationfile)) {
            echo "Le fichier $situationfile n'existe pas !!! \n";
        } else {
            $fp = fopen("$situationfile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode($separateur, $ligne);
                    $agentid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $codesituation = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    if (! isset($debug) or $debug == false)
                    {
                        echo "agentid = $agentid   numligne=$numligne   codesituation=$codesituation   datedebut=$datedebut   datefin=$datefin\n";
                    }
                    
                    $agent = new agent($dbcon);
                    if (!$agent->load($agentid))
                    {
                        echo "L'agent $agentid n'existe pas dans la base. On ne charge pas sa situation administrative  \n";
                        continue;
                    }
                    
                    $sql = sprintf("INSERT INTO SITUATIONADMIN (AGENTID,NUMLIGNE,POSITIONADMIN,DATEDEBUT,DATEFIN) VALUES('%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($agentid), $fonctions->my_real_escape_utf8($numligne), $fonctions->my_real_escape_utf8($codesituation), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin));
                        
                    mysqli_query($dbcon, $sql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "") {
                        echo "Error : INSERT SITUATIONADMIN => $erreur_requete \n";
                        echo "sql = $sql \n";
                    }
                }
            }
            fclose($fp);
        }
            
        echo "Import des STRUCTURES D'AFFECTATION \n";
        // Import des affectations-structure.txt
        
        // On charge la table des structures avec le fichier
        if (! file_exists($structurefile)) {
            echo "Le fichier $structurefile n'existe pas !!! \n";
        } else {
            $fp = fopen("$structurefile", "r");
            while (! feof($fp)) {
                $ligne = fgets($fp); // lecture du contenu de la ligne
                if (trim($ligne) != "") {
                    $ligne_element = explode($separateur, $ligne);
                    $agentid = trim($ligne_element[0]);
                    $numligne = trim($ligne_element[1]);
                    $idstruct = trim($ligne_element[2]);
                    $datedebut = trim($ligne_element[3]);
                    $datefin = trim($ligne_element[4]);
                    
                    if (! isset($debug) or $debug == false)
                    {
                        echo "agentid = $agentid   numligne=$numligne   structure=$idstruct   datedebut=$datedebut   datefin=$datefin\n";
                    }
                    
                    $agent = new agent($dbcon);
                    if (!$agent->load($agentid))
                    {
                        echo "L'agent $agentid n'existe pas dans la base. On ne charge pas sa structure d'affectation  \n";
                        continue;
                    }
                    
                    if ($fonctions->formatdatedb($datedebut) <= date('Ymd') and $fonctions->formatdatedb($datefin) >= date('Ymd'))
                    {
                        $sql = sprintf("UPDATE AGENT SET STRUCTUREID = '%s' WHERE AGENTID = '%s'", $fonctions->my_real_escape_utf8($idstruct), $fonctions->my_real_escape_utf8($agentid));
                        mysqli_query($dbcon, $sql);
                        $erreur_requete = mysqli_error($dbcon);
                        if ($erreur_requete != "") {
                            echo "Error : UPDATE STRUCTUREID dans AGENT=> $erreur_requete \n";
                            echo "sql = $sql \n";
                        }
                    }
                }
            }
            fclose($fp);
        }
  
    }
    
    $sql = "DELETE FROM AFFECTATION";
    mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
    {
        echo "Error : DELETE AFFECTATION => $erreur_requete \n";
    }
    $sql = sprintf("SELECT DISTINCT AGENTID FROM STATUT WHERE AGENTID IN (SELECT DISTINCT AGENTID FROM QUOTITE) AND AGENTID IN (SELECT DISTINCT AGENTID FROM SITUATIONADMIN) AND AGENTID IN (SELECT DISTINCT AGENTID FROM AGENT)");
    $query_agentid = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
    {
        echo "Error : SELECT AGENTID FROM STATUT QUOTITE SITUATIONADMIN AGENT => $erreur_requete \n";
    }
    while ($agentid = mysqli_fetch_row($query_agentid)) 
    {
        $agent = new agent($dbcon);
        $agent->load($agentid[0]);
        $tabaffectation = $agent->creertimeline();
        //echo "Timeline de l'agent " . $agent->agentid() . " => " . print_r($tabaffectation, true) . "\n";
        
        foreach ($tabaffectation as $ligne_element)
        {
            $ligne_element = explode(";", $ligne_element);
            $affectationid = $ligne_element[0];
            $agentid = $ligne_element[1];
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
         
            //echo "affectationid = $affectationid   agentid=$agentid   numcontrat=$numcontrat   datemodif=$datemodif datedebut=$datedebut  datefin=$datefin\n";

            $sql = sprintf("INSERT INTO AFFECTATION(AFFECTATIONID,AGENTID,NUMCONTRAT,DATEDEBUT,DATEFIN,DATEMODIFICATION,NUMQUOTITE,DENOMQUOTITE,OBSOLETE)
        	  		VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s')", $fonctions->my_real_escape_utf8($affectationid), $fonctions->my_real_escape_utf8($agentid), $fonctions->my_real_escape_utf8($numcontrat), $fonctions->my_real_escape_utf8($datedebut), $fonctions->my_real_escape_utf8($datefin), $fonctions->my_real_escape_utf8($datemodif), $fonctions->my_real_escape_utf8($numquotite), $fonctions->my_real_escape_utf8($denomquotite), 'N');
            mysqli_query($dbcon, $sql);
            $erreur_requete = mysqli_error($dbcon);
            if ($erreur_requete != "")
            {
               echo "Error : INSERT AFFECTATION => $erreur_requete \n";
            }
        }
    }

    echo "Fin de l'import des affectations " . date("d/m/Y H:i:s") . "\n";

?>