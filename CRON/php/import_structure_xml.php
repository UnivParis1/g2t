<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);
    $date = date("Ymd");

    class XMLfonction
    {
        public $code = '';
        public $priorite = '';
        public $fctnormale = '';
        public $fctinterim = '';
        public $fctpedagogie = '';
    }
    
    class XMLstructure
    {
        public $code_struct = '';
        public $nom_long_struct = '';
        public $nom_court_struct = '';
        public $parent_struct = '';
        public $type_struct = '';
        public $statut_struct = '';
        public $date_cloture = '';
        public $responsableid = '';
        // On met NULL car la valeur peut-être vide dans le fichier XML
        public $externalid = null;
        // On met NULL car la valeur peut-être vide dans le fichier XML
        public $isincluded = null;
        // On met NULL car la valeur peut-être vide dans le fichier XML
        public $businesscategory = null;
        public $islibrary = '';
        public $isRAstruct = '';
    }

    echo "Début de l'import des structures " . date("d/m/Y H:i:s") . "\n";

    $tab_infos_fonct = array();
    $tab_fonctions_RA = array();
    // On regarde si le fichier des priorites de fonctions est present
    $filename = $fonctions->inputfilepath() . "/infos_fonctions_$date.xml";
    if (!file_exists($filename))
    {
        echo "Le fichier des priorites de fonctions $filename n'existe pas ....\n";
    }
    else
    {
        echo "Le fichier $filename est présent. \n";
        $xml = simplexml_load_file("$filename");
        $fctpriorite = $xml->xpath('FCT_PRIORITE');
        foreach ($fctpriorite as $node)
        {
            //$code_fonction = "#" . intval(trim($node->xpath('CODEFONCT')[0]));
            //$priorite = trim($node->xpath('PRIORITE')[0]);
            //$tab_infos_fonct["$code_fonction"] = $priorite;

            $XMLfonction = new XMLfonction();
            $XMLfonction->code = "#" . intval(trim($node->xpath('CODEFONCT')[0]));
            $XMLfonction->priorite = trim($node->xpath('PRIORITE')[0]);
            if (isset($node->xpath('FCTNORMALE')[0]))
            {
                $XMLfonction->fctnormale = trim($node->xpath('FCTNORMALE')[0]);
            }
            if (isset($node->xpath('FCTINTERIM')[0]))
            {
                $XMLfonction->fctinterim = trim($node->xpath('FCTINTERIM')[0]);
            }
            if (isset($node->xpath('FCTPEDAGOGIE')[0]))
            {
                $XMLfonction->fctpedagogie = trim($node->xpath('FCTPEDAGOGIE')[0]);
            }
            //echo "XMLfonction = " . print_r($XMLfonction,true) . " \n";

            $tab_infos_fonct[$XMLfonction->code] = $XMLfonction;

            ///////////////////////////////////////////////////////////////////
            // On construit le tableau des fonctions speciales PEDAGOGIE
            if (strcasecmp($XMLfonction->fctpedagogie,'O')==0)
            {
	            $tab_fonctions_RA[$XMLfonction->code] = $XMLfonction;
            }
            // On construit le tableau des fonctions interim
            if (trim($XMLfonction->fctinterim) != '')
            {
                $tab_fonctions_interim[trim($XMLfonction->code)] = $XMLfonction;
            }
            
            if (trim($XMLfonction->fctnormale) != '')
            {
            	$arrayfct_normale = explode(",",trim($XMLfonction->fctnormale));
            	foreach ((array)$arrayfct_normale as $fct)
            	{
                    $tab_fonctions_interim["#" . intval($fct)] = $XMLfonction;
                }
            }
            //////////////////////////////////////////////////////////////////
        }
    }

    //echo "tab_infos_fonct = " . print_r($tab_infos_fonct, true) . " \n";

    // On regarde si le fichier des fonctions est present
    $filename = $fonctions->inputfilepath() . "/siham_fonctions_$date.xml";
    if (!file_exists($filename))
    {
        echo "Le fichier des fonctions $filename n'existe pas ....\n";
        $tab_struct_fonctions = array();
    }
    else
    {
        echo "Le fichier $filename est présent. \n";

	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('FONCTION');
	foreach ($agentnode as $node)
	{
            $agentid = trim($node->xpath('AGENTID')[0]);
            if (isset($node->xpath('CONDEFONCT')[0]))
            {
                $code_fonction = trim($node->xpath('CONDEFONCT')[0]);
            }
            if (isset($node->xpath('CODEFONCT')[0]))
            {
                $code_fonction = trim($node->xpath('CODEFONCT')[0]);
            }
            $libelle_fctn_cours = trim($node->xpath('NOMCOURT')[0]);
            $libelle_fctn_long = trim($node->xpath('NOMLONG')[0]);
            if (count($node->xpath('STRUCTID'))>0)
            {
                $code_struct = trim($node->xpath('STRUCTID')[0]);
                if ($code_struct != "")
                {
                    $tab_struct_fonctions[$code_struct]["#" . intval("$code_fonction")] = $agentid;
                    // Si on ne connait pas le poids de la fonction => On n'en tient pas compte => suppression du tableau tab_struct_fonctions
                    if (!isset($tab_infos_fonct["#" . intval("$code_fonction")]))
                    {
                        echo "La priorite de la fonction " . intval("$code_fonction") . " est manquante => On la supprime du tableau tab_struct_fonctions \n"; 
                        unset($tab_struct_fonctions[$code_struct]["#" . intval("$code_fonction")]);
                    }
                }
            }
        }
    }

    echo "tab_struct_fonctions = " . print_r($tab_struct_fonctions, true) . " \n";
    echo "tab_infos_fonct = " . print_r($tab_infos_fonct, true) . " \n";
    echo "tab_fonctions_interim = " . print_r($tab_fonctions_interim, true) . " \n";
    echo "tab_fonctions_RA = " . print_r($tab_fonctions_RA, true) . " \n";

    // On parcourt le fichier des structures
    // Si la structure n'existe pas
    // on insert la structure
    // Sinon
    // on update les infos

    $filename = $fonctions->inputfilepath() . "/siham_structures_$date.xml";
    if (! file_exists($filename))
    {
        echo "Le fichier $filename n'existe pas !!! \n";
        exit();
    }
    else
    {
        echo "Le fichier $filename est présent. \n";

	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('STRUCTURE');
	foreach ($agentnode as $node)
	{
            echo "---------------------------------------------------\n";
            $XMLstructure = new XMLstructure();
            $XMLstructure->code_struct = trim($node->xpath('STRUCTID')[0]);
            $XMLstructure->nom_long_struct = trim($node->xpath('NOMLONG')[0]);
            $XMLstructure->nom_court_struct = trim($node->xpath('NOMCOURT')[0]);
            $XMLstructure->parent_struct = trim($node->xpath('PARENTID')[0]);
            $XMLstructure->type_struct = trim($node->xpath('TYPESTRUCT')[0]);
            $XMLstructure->statut_struct = trim($node->xpath('STATUT')[0]);
            $XMLstructure->date_cloture = trim($node->xpath('FINVALID')[0]);
            $XMLstructure->responsableid = '';
            if (count($node->xpath('RESPID'))>0)
            {
                $XMLstructure->responsableid = trim($node->xpath('RESPID')[0]);
            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $XMLstructure->externalid = null;
            if (count($node->xpath('EXTERNALID'))>0)
            {
                $XMLstructure->externalid = trim($node->xpath('EXTERNALID')[0]);
                echo "externalid est trouve dans le fichier d'interface : $XMLstructure->externalid \n";

            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $XMLstructure->isincluded = null;
            if (count($node->xpath('ISINCLUDED'))>0)
            {
                $XMLstructure->isincluded = trim($node->xpath('ISINCLUDED')[0]);
                echo "isincluded est trouve dans le fichier d'interface : $XMLstructure->isincluded \n";
            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $XMLstructure->businesscategory = null;
            if (count($node->xpath('BUSINESSCATEG'))>0)
            {
                $XMLstructure->businesscategory = trim($node->xpath('BUSINESSCATEG')[0]);
                echo "businesscategory est trouve dans le fichier d'interface : $XMLstructure->businesscategory \n";
            }
            $XMLstructure->islibrary = '';
            if (count($node->xpath('ISLIBRARY'))>0)
            {
                $XMLstructure->islibrary = trim($node->xpath('ISLIBRARY')[0]);
                echo "islibrary est trouve dans le fichier d'interface : $XMLstructure->islibrary \n";
            }
            $XMLstructure->isRAstruct = '';
            if (count($node->xpath('ISRASTRUCT'))>0)
            {
                $XMLstructure->isRAstruct = trim($node->xpath('ISRASTRUCT')[0]);
                echo "isRAstruct est trouve dans le fichier d'interface : $XMLstructure->isRAstruct \n";
            }

            echo "La structure $XMLstructure->code_struct est inclue dans la structure parente (1 = true, 0 = false) : $XMLstructure->isincluded \n";
            echo "L'identifiant de l'ancienne structure est : " . $XMLstructure->externalid . " correspondant à la nouvelle structure : $XMLstructure->code_struct \n";
            echo "La categorie de la structure est $XMLstructure->businesscategory \n";
            
            $XMLstructure->isincluded = $fonctions->convertvaluetobool($XMLstructure->isincluded);
            $XMLstructure->islibrary = $fonctions->convertvaluetobool($XMLstructure->islibrary);
            $XMLstructure->isRAstruct = $fonctions->convertvaluetobool($XMLstructure->isRAstruct);

            $codefonction = "";
            $resp_struct = "";
            // Si la structure est active on cherche le responsable.
            if (strcasecmp($XMLstructure->statut_struct, 'ACT') == 0)
            {
                if (strcasecmp($XMLstructure->code_struct,'UP1_1') == 0)
                {
                    echo "On ignore les fonctions définies pour la structure $XMLstructure->code_struct \n";
                }
                else
                {
                    if (array_key_exists($XMLstructure->code_struct, (array) $tab_struct_fonctions))
                    {
                        $code_fonct = "";
                        ///////////////////////////
                        // Si la structure est une structure de type RA, on cherche dans un premier temps dans les fonctions RA
                        if ($XMLstructure->isRAstruct)
                        {
                            // On est dans une structure RA, donc on cherche la fonction de poids le plus faible
                            // On charche les fonctions RA qui sont définies pour la structure
                            $fonctions_speciales = array_intersect_key((array) $tab_struct_fonctions[$XMLstructure->code_struct], $tab_fonctions_RA);
                            // Pour chaque fonction de type RA définie pour la structure, on cherche celle qui a le poids le plus faible
                            // ATTENTION : $value est une classe XMLfonction
                            foreach ($fonctions_speciales as $key => $value)
                            {
                                if ($code_fonct == "")
                                {
                                    $code_fonct = $key;
                                    echo "Structure de type RA => la fonction $code_fonct est definie (initialisation) - priorite = " . $tab_infos_fonct[$code_fonct]->priorite . "\n";
                                }
                                elseif (isset($tab_infos_fonct[$key]) and $tab_infos_fonct[$key]->priorite < $tab_infos_fonct[$code_fonct]->priorite)
                                {
                                    $code_fonct = $key;
                                    echo "Structure de type RA => la fonction $code_fonct est definie (poids inférieur) - priorite = " . $tab_infos_fonct[$code_fonct]->priorite . "\n";
                                }
                                else
                                {
                                    echo "Structure de type RA => La priorité de la fonction $key (" . $tab_infos_fonct[$key]->priorite . ") est plus élevée que la fonction $code_fonct (" . $tab_infos_fonct[$code_fonct]->priorite . ") => Aucun changement \n";
                                }
                            }
                            if ($code_fonct != "")
                            {
                                $resp_struct = $tab_struct_fonctions[$XMLstructure->code_struct][$code_fonct];
                                echo "Bilan structure de type RA => la fonction $code_fonct est definie comme responsable\n";
                            }
                        }

                        // Soit ce n'est pas une structure RA, soit on n'a pas trouvé de fonctions RA pour une structure RA
                        if ($code_fonct == "")
                        {
                            // On cherche dans tout le tableau des poids de fonction, les fonctions définies pour la structure
	                        $fonctions_normales = array_intersect_key((array) $tab_struct_fonctions[$XMLstructure->code_struct], $tab_infos_fonct);
	                        // Pour chaque fonction définie pour la structure, on cherche celle qui a le poids le plus faible
	                        // ATTENTION : $value est une classe XMLfonction
                            foreach ($fonctions_normales as $key => $value)
                            {
                                if ($code_fonct == "")
                                {
                                    $code_fonct = $key;
                                    echo "Structure non RA => la fonction $code_fonct est definie (initialisation) - priorite = " . $tab_infos_fonct[$code_fonct]->priorite . "\n";
                                }
                                elseif (isset($tab_infos_fonct[$key]) and $tab_infos_fonct[$key]->priorite < $tab_infos_fonct[$code_fonct]->priorite)
                                {
                                    $code_fonct = $key;
                                    echo "Structure non RA => la fonction $code_fonct est definie (poids inférieur) - priorite = " . $tab_infos_fonct[$code_fonct]->priorite . "\n";
                                }
                                else
                                {
                                    echo "Structure non RA => La priorité de la fonction $key (" . $tab_infos_fonct[$key]->priorite . ") est plus élevée que la fonction $code_fonct (" . $tab_infos_fonct[$code_fonct]->priorite . ") => Aucun changement \n";
                                }
                            }
                            if ($code_fonct != "")
                            {
                                $resp_struct = $tab_struct_fonctions[$XMLstructure->code_struct][$code_fonct];
                                echo "Bilan structure non RA => la fonction $code_fonct est definie comme responsable\n";
                            }
                        }

			// On a trouvé une fonction (soit via un RA, soit via la voie normale)
                        //echo "code_fonct = $code_fonct (avant le test des interim) \n";
                        if ($code_fonct != '')
                        {
                            /// On cherche les interims de la structure
                            // $code_fonct contient la fonction 'normale'
                            // $tab_fonctions_interim["$code_fonct"]->fctinterim contient le code de la fonction interim correspondant à une fonction
                            //echo "On test sur le code fonction $code_fonct est dans les fonctions interim \n";
                            if (isset($tab_fonctions_interim["$code_fonct"]))
                            {
                                //echo "tab_fonctions_interim[$code_fonct]->fctinterim = " . $tab_fonctions_interim["$code_fonct"]->fctinterim . " \n";
                                if (isset($tab_struct_fonctions[$XMLstructure->code_struct]["#" . $tab_fonctions_interim["$code_fonct"]->fctinterim]))
                                {
                                    $old_code_fonct = $code_fonct;
                                    $code_fonct = $tab_fonctions_interim["$code_fonct"]->fctinterim;
                                    $resp_struct = $tab_struct_fonctions[$XMLstructure->code_struct]["#" . $code_fonct];
                                    echo "Structure avec un interim correspondant à la fonction normale $old_code_fonct => la fonction interim $code_fonct est definie comme responsable\n";
                                }
                                else
                                {
                                    echo "Structure sans fonction interim correspondant à la fonction $code_fonct => On ne change pas le responsable\n";
                                }
                            }
                            else
                            {
                                echo "La fonction $code_fonct n'a pas de fonction 'interim' associée  => On ne change pas le responsable\n";
                            }
                        }
                    }
                }
                if ($resp_struct != "")
                {
                    echo "On a un responsable pour la structure $XMLstructure->nom_long_struct / $XMLstructure->nom_court_struct ($XMLstructure->code_struct) => $resp_struct \n";
                }
                else
                {
                    // On récupère le responsable de l'UO (soit grace au poste soit grace directmenet au matricule de l'agent)
                    echo "Pas de fonction pour la structure $XMLstructure->nom_long_struct / $XMLstructure->nom_court_struct  ($XMLstructure->code_struct) dans le fichier des fonctions\n";
                    echo "On recupere le responsable de la structure s'il est defini dans le fichier des structures\n";
                    $resp_struct = $XMLstructure->responsableid;
                    // Si pas de responsable défini dans le fichier de structure
                    if ($resp_struct == "")
                    {
                        // Si on arrive ici, c'est vraiment qu'on n'a aucune information nulle part !!!
                        echo "Aucune information recuperee => On fixe le responsable a " . constant('SPECIAL_USER_IDCRONUSER')  . " (CRON G2T) pour la structure $XMLstructure->nom_long_struct / $XMLstructure->nom_court_struct  ($XMLstructure->code_struct) \n";
                        $resp_struct = SPECIAL_USER_IDCRONUSER;
                    }
                    else
                    {
                        echo "On a trouve le code du responsable dans le fichier des structures => $resp_struct \n";
                    }
                }
                $agent = new agent($dbcon);
                if (!$agent->load($resp_struct)) // Si erreur au chargement => Agent inexistant dans la base
                {
                    echo "Le code du responsable est : $resp_struct mais il n'est pas encore dans la base G2T. \n";
                }
                else
                {
                    echo "Le code du responsable est : $resp_struct (" . $agent->identitecomplete() . ") \n";
                }
            }
            else
            {
                echo "La structure $XMLstructure->nom_long_struct / $XMLstructure->nom_court_struct  ($XMLstructure->code_struct) n'est pas active => On ne cherche pas le responsable. \n";
            }
            echo "Le code SIHAM du statut de la structure est : $XMLstructure->statut_struct \n";
            // Si la structure est active 'ACT'
            if (strcasecmp($XMLstructure->statut_struct, 'ACT') == 0)
            {
                if (is_null($XMLstructure->date_cloture) or $XMLstructure->date_cloture == "")
                {
                    $XMLstructure->date_cloture = '2999-12-31';
                }

                if ($XMLstructure->islibrary)
                {
                    $estbibliotheque = 1;
                }
                else
                {
                    $estbibliotheque = 0;
                }

                if ($XMLstructure->isincluded)
                {
                    $estinclue = 1;
                }
                else
                {
                    $estinclue = 0;
                }

                $sql = "SELECT * FROM STRUCTURE WHERE STRUCTUREID='" . $XMLstructure->code_struct . "'";
                $query = mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "")
                {
                    echo "SELECT STRUCTURE => $erreur_requete \n";
                }
                if (mysqli_num_rows($query) == 0) // Structure manquante
                {
                    echo "Creation d'une nouvelle structure : $XMLstructure->nom_long_struct (Id = $XMLstructure->code_struct) \n";
                    $sql = sprintf("INSERT INTO STRUCTURE(STRUCTUREID,
                                                          NOMLONG,
                                                          NOMCOURT,
                                                          STRUCTUREIDPARENT,
                                                          RESPONSABLEID,
                                                          DATECLOTURE,
                                                          TYPESTRUCT,
                                                          ISINCLUDED,
                                                          ESTBIBLIOTHEQUE,
                                                          EXTERNALID)
                                    VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                                        $fonctions->my_real_escape_utf8($XMLstructure->code_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->nom_long_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->nom_court_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->parent_struct),
                                        $fonctions->my_real_escape_utf8($resp_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->date_cloture),
                                        $fonctions->my_real_escape_utf8($XMLstructure->type_struct),
                                        $estinclue,
                                        $estbibliotheque,
                                        $fonctions->my_real_escape_utf8($XMLstructure->externalid)
                            );
                }
                else
                {
                    echo "Mise a jour d'une structure : $XMLstructure->nom_long_struct (Id = $XMLstructure->code_struct) \n";
                    $sql = sprintf("UPDATE STRUCTURE SET NOMLONG='%s',
                                                         NOMCOURT='%s',
                                                         STRUCTUREIDPARENT='%s',
                                                         RESPONSABLEID='%s',
                                                         DATECLOTURE='%s',
                                                         TYPESTRUCT='%s',
                                                         ISINCLUDED='%s',
                                                         ESTBIBLIOTHEQUE='%s',
                                                         EXTERNALID='%s'
                                    WHERE STRUCTUREID='%s'",
                                        $fonctions->my_real_escape_utf8($XMLstructure->nom_long_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->nom_court_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->parent_struct),
                                        $fonctions->my_real_escape_utf8($resp_struct),
                                        $fonctions->my_real_escape_utf8($XMLstructure->date_cloture),
                                        $fonctions->my_real_escape_utf8($XMLstructure->type_struct),
                                        $estinclue,
                                        $estbibliotheque,
                                        $fonctions->my_real_escape_utf8($XMLstructure->externalid),
                                        $fonctions->my_real_escape_utf8($XMLstructure->code_struct)
                            );
                    // echo $sql."\n";
                }
                mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "") {
                    echo "INSERT/UPDATE STRUCTURE => $erreur_requete \n";
                    echo "sql = $sql \n";
                }

                if ($XMLstructure->externalid == $XMLstructure->code_struct)
                {
                    echo "On detecte une boucle ancienne struct = nouvelle struct => On ne ferme pas la structure....\n";
                }
                else
                {
                    $oldsql = "SELECT STRUCTUREID,
                                      NOMLONG,
                                      NOMCOURT,
                                      STRUCTUREIDPARENT,
                                      RESPONSABLEID,
                                      GESTIONNAIREID,
                                      AFFICHESOUSSTRUCT,
                                      AFFICHEPLANNINGTOUTAGENT,
                                      DEST_MAIL_RESPONSABLE,
                                      DEST_MAIL_AGENT,
                                      DATECLOTURE
                               FROM STRUCTURE
                               WHERE STRUCTUREID = '$XMLstructure->externalid' ";
                    $oldquery = mysqli_query($dbcon, $oldsql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "")
                    {
                        echo "SELECT OLD STRUCTURE => $erreur_requete \n";
                    }
                    if (mysqli_num_rows($oldquery) == 0) // Structure manquante
                    {
                        echo "Pas de correspondance avec l'ancienne structure $XMLstructure->externalid \n";
                    }
                    else
                    {
                        $result = mysqli_fetch_row($oldquery);
                        if (is_null($result[10]))
                        {
                            $datecloture = "01/01/1900";
                        }
                        else
                        {
                            $datecloture = $result[10];
                        }
                        if ($fonctions->formatdatedb($datecloture) > "20151231") // Si l'ancienne structuture n'est pas fermée
                        {
                            $sql = "UPDATE STRUCTURE
                                    SET GESTIONNAIREID ='$result[5]',
                                        AFFICHESOUSSTRUCT = '$result[6]',
                                        AFFICHEPLANNINGTOUTAGENT = '$result[7]',
                                        DEST_MAIL_RESPONSABLE = '$result[8]',
                                        DEST_MAIL_AGENT = '$result[9]',
                                        TYPESTRUCT = '$XMLstructure->type_struct',
                                        ISINCLUDED = '$estinclue'
                                    WHERE STRUCTUREID = '$XMLstructure->code_struct'";
                            
                            mysqli_query($dbcon, $sql);
                            $erreur_requete = mysqli_error($dbcon);
                            if ($erreur_requete != "")
                            {
                                echo "UPDATE STRUCTURE (migration) => $erreur_requete \n";
                                echo "sql = $sql \n";
                            }
                            else
                            {
                                $sql = "UPDATE STRUCTURE SET DATECLOTURE = '20151231' WHERE STRUCTUREID = '$XMLstructure->externalid'";
                                mysqli_query($dbcon, $sql);
                                $erreur_requete = mysqli_error($dbcon);
                                if ($erreur_requete != "")
                                {
                                    echo "UPDATE STRUCTURE (cloture) => $erreur_requete \n";
                                    echo "sql = $sql \n";
                                }
                                else
                                {
                                    echo "==> Fermeture de l'ancienne structure '$XMLstructure->externalid' a la date du 31/12/2015\n";
                                }
                            }
                        }
                        else
                        {
                            echo "L'ancienne structure $XMLstructure->externalid est deja fermee => Pas de recuperation de donnees \n";
                        }
                    }
                }
            }
            elseif (strcasecmp($XMLstructure->statut_struct, 'INA') == 0)
            // La structure est inactive ==> On doit la fermer si ce n'est pas déjà fait
            {
                $sql = "SELECT DATECLOTURE FROM STRUCTURE WHERE STRUCTUREID='" . $XMLstructure->code_struct . "'";
                $query = mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "")
                {
                    echo "SELECT STRUCTURE (inactif) => $erreur_requete \n";
                }
                if (mysqli_num_rows($query) == 0) // Structure manquante
                {
                    echo "La structure : $XMLstructure->nom_long_struct (Id = $XMLstructure->code_struct) est inactive dans SIHAM mais n'existe pas dans G2T ! On l'ignore...\n";
                }
                else
                {
                    $result = mysqli_fetch_row($query);
                    $date_cloture_g2t = $result[0];
                    // Si la date de cloture dans G2T est postérieure à la date du jour, alors on met la date de la veille en cloture
                    if ($fonctions->formatdatedb($date_cloture_g2t) >= date("Ymd"))
                    {
                        echo "Mise a jour de la date de cloture d'une structure pour la rendre inactive : $XMLstructure->nom_long_struct (Id = $XMLstructure->code_struct) \n";
                        //$date_veille = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('y')));
                        $date_veille = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d') - 1, date('y')));
                        echo "Date de la veille = " . $fonctions->formatdatedb($date_veille) . " \n";
                        $sql = "UPDATE STRUCTURE SET DATECLOTURE='" . $fonctions->formatdatedb($date_veille) . "'  WHERE STRUCTUREID = '$XMLstructure->code_struct'";
                        mysqli_query($dbcon, $sql);
                        $erreur_requete = mysqli_error($dbcon);
                        if ($erreur_requete != "") {
                            echo "INSERT/UPDATE STRUCTURE (inactif) => $erreur_requete \n";
                            echo "sql = $sql \n";
                        }
                    }
                    else
                    {
                        echo "La structure est deja close (date de fermeture = $date_cloture_g2t) => On ne fait rien\n";
                    }
                }
            }
            else
            {
                echo "La structure : $XMLstructure->nom_long_struct (Id = $XMLstructure->code_struct) a un statut dans SIHAM non reconnu par G2T (statut = $XMLstructure->statut_struct)...\n";
            }
        }

        ///////////////////////////////////////////////////
        // On ajoute les responsables / les gestionnaires à partir de LDAP s'il n'existent pas dans la base
        // On supprime les agents qui ont comme typepopulation => 'Import automatique LDAP'
        echo "------------------------------------------------------------ \n";
        echo "On complete les agents avec les infos LDAP \n";
        $typepopulation = "Import automatique LDAP";
        $sql = "DELETE FROM AGENT WHERE TYPEPOPULATION = '$typepopulation'";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
        {
            echo "Error : DELETE AGENT sur TYPEPOPULATION => $erreur_requete \n";
            echo "sql = $sql \n";
        }
        else
        {
            $sql = "SELECT DISTINCT SUBREQ.AGENTID FROM (
                        SELECT DISTINCT RESPONSABLEID AGENTID, DATECLOTURE FROM STRUCTURE WHERE RESPONSABLEID NOT IN (SELECT AGENTID FROM AGENT)
                        UNION
                        SELECT DISTINCT GESTIONNAIREID AGENTID, DATECLOTURE FROM STRUCTURE WHERE GESTIONNAIREID NOT IN (SELECT AGENTID FROM AGENT)
                    ) AS SUBREQ
                    WHERE SUBREQ.DATECLOTURE > '" . $fonctions->formatdatedb(date('d/m/Y')) .  "' AND SUBREQ.AGENTID != '' ";
            //echo "SQL = $sql \n";
            $query = mysqli_query($dbcon, $sql);
            $erreur_requete = mysqli_error($dbcon);
            if ($erreur_requete != "")
            {
                echo "Error : SELECT DISTINCT SUBREQ.AGENTID => $erreur_requete \n";
            }
            while ($result = mysqli_fetch_row($query))
            {
                $agentid = $result[0];

                if (is_numeric($agentid))
                {
                    if ($fonctions->createldapagentfromagentid($agentid)===false)
                    {
                        echo "Impossible d'ajouter l'agent $agentid dans la base \n";
                    }
                }
                else
                {
                    echo "Probleme : agentid n'est pas numerique => agentid = $agentid \n";
                }
            }
        }
    }
    echo "Fin de l'import des structures " . date("d/m/Y H:i:s") . "\n";

?>