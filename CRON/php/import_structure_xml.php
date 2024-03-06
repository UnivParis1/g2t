<?php
    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);
    $date = date("Ymd");

    class agentfct
    {
        public $code = '';
        public $priorite = '';
        public $fctnormale = '';
        public $fctinterim = '';
        public $fctpedagogie = '';
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

            $agentfct = new agentfct();
            $agentfct->code = "#" . intval(trim($node->xpath('CODEFONCT')[0]));
            $agentfct->priorite = trim($node->xpath('PRIORITE')[0]);
            if (isset($node->xpath('FCTNORMALE')[0]))
            {
                $agentfct->fctnormale = trim($node->xpath('FCTNORMALE')[0]);
            }
            if (isset($node->xpath('FCTINTERIM')[0]))
            {
                $agentfct->fctinterim = trim($node->xpath('FCTINTERIM')[0]);
            }
            if (isset($node->xpath('FCTPEDAGOGIE')[0]))
            {
                $agentfct->fctpedagogie = trim($node->xpath('FCTPEDAGOGIE')[0]);
            }
            //echo "agentfct = " . print_r($agentfct,true) . " \n";

            $tab_infos_fonct[$agentfct->code] = $agentfct;

            ///////////////////////////////////////////////////////////////////
            // On construit le tableau des fonctions speciales PEDAGOGIE
            if (strcasecmp($agentfct->fctpedagogie,'O')==0)
            {
	            $tab_fonctions_RA[$agentfct->code] = $agentfct;
            }
            // On construit le tableau des fonctions interim
            if (trim($agentfct->fctinterim) != '')
            {
                $tab_fonctions_interim[trim($agentfct->code)] = $agentfct;
            }
            
            if (trim($agentfct->fctnormale) != '')
            {
            	$arrayfct_normale = explode(",",trim($agentfct->fctnormale));
            	foreach ((array)$arrayfct_normale as $fct)
            	{
                    $tab_fonctions_interim["#" . intval($fct)] = $agentfct;
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

        // Initialisation du LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAP_FONCTION_SEARCH_BASE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        $dn = $LDAP_SEARCH_BASE;
        $LDAP_FONCTION_POIDS_ATTR = $fonctions->liredbconstante("LDAP_FONCTION_POIDS_ATTR");
        $restriction = array("$LDAP_FONCTION_POIDS_ATTR");

	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('FONCTION');
	foreach ($agentnode as $node)
	{
            $agentid = trim($node->xpath('AGENTID')[0]);
            $code_fonction = trim($node->xpath('CONDEFONCT')[0]);
            $libelle_fctn_cours = trim($node->xpath('NOMCOURT')[0]);
            $libelle_fctn_long = trim($node->xpath('NOMLONG')[0]);
            if (count($node->xpath('STRUCTID'))>0)
            {
                $code_struct = trim($node->xpath('STRUCTID')[0]);
                if ($code_struct != "")
                {
                    $tab_struct_fonctions[$code_struct]["#" . intval("$code_fonction")] = $agentid;
                    // Pour chaque fonction dans le tableau des fonctions on va chercher son poids dans LDAP
                    if (!isset($tab_infos_fonct["#" . intval("$code_fonction")]))
                    {
                        echo "La priorite de la fonction " . intval("$code_fonction") . " est manquante => On va voir LDAP \n";
                        $filtre = "supannRefId={SIHAM:FCT}" . $code_fonction;
                        $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                        $info = ldap_get_entries($con_ldap, $sr);
                        //echo "Info = " . print_r($info,true) . "\n";

                        if (isset($info[0]["$LDAP_FONCTION_POIDS_ATTR"][0]))
                        {
                            //$tab_infos_fonct["#" . intval("$code_fonction")] = $info[0]["$LDAP_FONCTION_POIDS_ATTR"][0];

                            $agentfct = new agentfct();
                            $agentfct->code = "#" . intval("$code_fonction");
                            $agentfct->priorite = $info[0]["$LDAP_FONCTION_POIDS_ATTR"][0];
                            $tab_infos_fonct[$agentfct->code] = $agentfct;
                        }
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

        // Initialisation du LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAP_STRUCT_SEARCH_BASE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);

	$xml = simplexml_load_file("$filename");
	$agentnode = $xml->xpath('STRUCTURE');
	foreach ($agentnode as $node)
	{
            echo "---------------------------------------------------\n";
            $code_struct = trim($node->xpath('STRUCTID')[0]);
            $nom_long_struct = trim($node->xpath('NOMLONG')[0]);
            $nom_court_struct = trim($node->xpath('NOMCOURT')[0]);
            $parent_struct = trim($node->xpath('PARENTID')[0]);
            $type_struct = trim($node->xpath('TYPESTRUCT')[0]);
            $statut_struct = trim($node->xpath('STATUT')[0]);
            $date_cloture = trim($node->xpath('FINVALID')[0]);
            $responsableid = '';
            if (count($node->xpath('RESPID'))>0)
            {
                $responsableid = trim($node->xpath('RESPID')[0]);
            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $externalid = null;
            if (count($node->xpath('EXTERNALID'))>0)
            {
                $externalid = trim($node->xpath('EXTERNALID')[0]);
                echo "externalid est trouve dans le fichier d'interface : $externalid \n";

            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $isincluded = null;
            if (count($node->xpath('ISINCLUDED'))>0)
            {
                $isincluded = trim($node->xpath('ISINCLUDED')[0]);
                echo "isincluded est trouve dans le fichier d'interface : $isincluded \n";
            }
            // On met NULL car la valeur peut-être vide dans le fichier XML
            $businesscategory = null;
            if (count($node->xpath('BUSINESSCATEG'))>0)
            {
                $businesscategory = trim($node->xpath('BUSINESSCATEG')[0]);
                echo "businesscategory est trouve dans le fichier d'interface : $businesscategory \n";
            }


            // On Prépare la requête LDAP pour récupérer les extra-infos si absente du fichier d'interface
            $filtre = "supannRefId={SIHAM.UO}" . $code_struct;
            $dn = $LDAP_SEARCH_BASE;

            // Si la valeur est NULL (donc non récupérée du fichier XML) => récupération du code LDAP de la structure
            if (is_null($externalid))
            {
                $externalid = '';
                $LDAP_CODE_STRUCT_ATTR = $fonctions->liredbconstante("LDAP_STRUCT_CODE_ENTITE_ATTR");
                $restriction = array("$LDAP_CODE_STRUCT_ATTR");
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                if (isset($info[0]["$LDAP_CODE_STRUCT_ATTR"][0]))
                {
                    $externalid = $info[0]["$LDAP_CODE_STRUCT_ATTR"][0];
                    echo "externalid est trouve dans LDAP : $externalid \n";
                }
            }
            // Si la valeur est NULL (donc non récupérée du fichier XML) => récupération du code d'inclusion de la structure dans la parente
            if (is_null($isincluded))
            {
                $isincluded = 0;
                $LDAP_IS_INCLUDED_ATTR = $fonctions->liredbconstante("LDAP_STRUCT_IS_INCLUDED_ATTR");
                $restriction = array("$LDAP_IS_INCLUDED_ATTR");
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                if (isset($info[0]["$LDAP_IS_INCLUDED_ATTR"][0]))
                {
                    $isincluded = (int)(in_array("included",$info[0]["$LDAP_IS_INCLUDED_ATTR"])); // On regarde si 'included' est dans la tableau des valeurs
                    echo "isincluded est trouve dans LDAP : $isincluded \n";
                }
            }
            // Si la valeur est NULL (donc non récupérée du fichier XML) => récupération du type de structure
            if (is_null($businesscategory))
            {
                $businesscategory = '';
                $LDAP_BUSINESSCATE_ATTR = $fonctions->liredbconstante("LDAP_STRUCT_BUSINESSCATE_ATTR");
                $restriction = array("$LDAP_BUSINESSCATE_ATTR");
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                if (isset($info[0]["$LDAP_BUSINESSCATE_ATTR"][0]))
                {
                    $businesscategory = $info[0]["$LDAP_BUSINESSCATE_ATTR"][0];
                    echo "businesscategory est trouve dans LDAP : $businesscategory \n";
                }
            }

            echo "La structure $code_struct est inclue dans la structure parente (1 = true, 0 = false) : $isincluded \n";
            echo "L'identifiant de l'ancienne structure est : " . $externalid . " correspondant à la nouvelle structure : $code_struct \n";
            echo "La categorie de la structure est $businesscategory \n";

            // -----------------------------
            //$type_struct_RA = array('');
            // -----------------------------
            // UFR = Les UFR
            // EDO = Les Ecoles Doctorales
            // SCO = Service commun
            // UFO = Les Unités de formation
            // UNR = Les Unités de recherche ??
            // INT = Les instituts
            // SEG = Les services généraux
            // DFEDS = Départements (formation) de l'EDS (ticket GLPI 142772)
            $type_struct_RA = array(
                'UFR',
                'EDO',
                'DPT',
                'SCO',
                'UFO',
                'UNR',
                'INT',
                'SEG',
                'DFEDS'
            );

            $codefonction = "";
            $resp_struct = "";
            // Si la structure est active on cherche le responsable.
            if (strcasecmp($statut_struct, 'ACT') == 0)
            {
                if (strcasecmp($code_struct,'UP1_1') == 0)
                {
/*
                    if (array_key_exists($code_struct, (array) $tab_struct_fonctions))
                    {
                        if (array_key_exists("#1", (array) $tab_struct_fonctions[$code_struct]))
                        {
                            $resp_struct = $tab_struct_fonctions[$code_struct]["#1"]; // Si le président est défini comme responsable de la structure => C'est cette fonction qu'on choisi
                            echo "On force la fonction #1 (président) pour la structure $code_struct \n";
                        }
                        elseif (array_key_exists("#1447", (array) $tab_struct_fonctions[$code_struct]))
                        {
                            $resp_struct = $tab_struct_fonctions[$code_struct]["#1447"]; // Si le DGS est défini comme responsable de la structure => C'est cette fonction qu'on choisi
                            echo "On force la fonction #1447 (DGS) pour la structure $code_struct \n";
                        }
                    }
*/
                    echo "On ignore les fonctions définies pour la structure $code_struct \n";
                }
                else
                {
                    if (array_key_exists($code_struct, (array) $tab_struct_fonctions))
                    {
//                        if (array_key_exists("#1087", (array) $tab_struct_fonctions[$code_struct]) and in_array($type_struct, $type_struct_RA))
                        // 1087 => Responsable administratif de composante
                        // 2212 => Responsable administratif par intérim
                        // 2213 => Adjoint au responsable administratif
                        // 2214 => Responsable administratif général
                        // 2215 => Adjoint au responsable administratif général
                        // ATTENTION : Mettre la valeur à non-vide ("Prioritaire" ou autre) si la fonction doit surpasser toutes les fonctions d'exceptions
                        //             La première fonction avec une valeur non vide est prioritaire sur les autres => arret du parcours
                        //////////////////////////////////////////////////////////////////
                        // Le tableau est déjà créé avec le parcours des fonctions
                        //$tab_fonctions_RA = array("#1087" => "", "#2212" => "", "#2213" => "", "#2214" => "", "#2215" => "");
                        //////////////////////////////////////////////////////////////////

                        $code_fonct = "";
                        ///////////////////////////
                        // Si la structure est une structure de type RA, on cherche dans un premier temps dans les fonctions RA
                        if (in_array($type_struct, $type_struct_RA))
                        {
                            // On est dans une structure RA, donc on cherche la fonction de poids le plus faible
                            // On charche les fonctions RA qui sont définies pour la structure
                            $fonctions_speciales = array_intersect_key((array) $tab_struct_fonctions[$code_struct], $tab_fonctions_RA);
                            // Pour chaque fonction de type RA définie pour la structure, on cherche celle qui a le poids le plus faible
                            // ATTENTION : $value est une classe agentfct
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
                                $resp_struct = $tab_struct_fonctions[$code_struct][$code_fonct];
                                echo "Bilan structure de type RA => la fonction $code_fonct est definie comme responsable\n";
                            }
                        }

                        // Soit ce n'est pas une structure RA, soit on n'a pas trouvé de fonctions RA pour une structure RA
                        if ($code_fonct == "")
                        {
                            // On cherche dans tout le tableau des poids de fonction, les fonctions définies pour la structure
	                        $fonctions_normales = array_intersect_key((array) $tab_struct_fonctions[$code_struct], $tab_infos_fonct);
	                        // Pour chaque fonction définie pour la structure, on cherche celle qui a le poids le plus faible
	                        // ATTENTION : $value est une classe agentfct
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
                                $resp_struct = $tab_struct_fonctions[$code_struct][$code_fonct];
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
                                if (isset($tab_struct_fonctions[$code_struct]["#" . $tab_fonctions_interim["$code_fonct"]->fctinterim]))
                                {
                                    $old_code_fonct = $code_fonct;
                                    $code_fonct = $tab_fonctions_interim["$code_fonct"]->fctinterim;
                                    $resp_struct = $tab_struct_fonctions[$code_struct]["#" . $code_fonct];
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


                        if ($resp_struct == "")
                        {
                            echo "On passe dans l'ancienne methode pour trouver le responsable. \n";
                            if (array_key_exists("#1", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1"]; // Président d'université
                            }
                            elseif (array_key_exists("#1447", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1447"]; // Directeur général des services
                            }
                            elseif (array_key_exists("#1044", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1044"]; // Agent comptable
                            }
                            elseif (array_key_exists("#2002", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#2002"]; // Responsable
                            }
                            elseif (array_key_exists("#1521", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1521"]; // Chef de service
                            }
                            elseif (array_key_exists("#1522", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1522"]; // Directeur(ice)
                            }
                            elseif (array_key_exists("#1615", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1615"]; // Chef de département
                            }
                            elseif (array_key_exists("#1860", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1860"]; // Chef d'atelier
                            }
                            elseif (array_key_exists("#1087", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1087"]; // Responsable Administratif de Composante
                            }
                            elseif (array_key_exists("#41", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#41"]; // Dir. de services communs d'universités
                            }
                            elseif (array_key_exists("#1016", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1016"]; // Dir. éco. Inst. Uni - Hors arrêté 13/9/90
                            }
                            elseif (array_key_exists("#1529", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1529"]; // Directeur(ice) d'institut
                            }
                            elseif (array_key_exists("#1530", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1530"]; // Directeur(ice) d'UMR
                            }
                            elseif (array_key_exists("#1043", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1043"]; // Secrétaire général
                            }
                            elseif (array_key_exists("#1532", (array) $tab_struct_fonctions[$code_struct]))
                            {
                               $resp_struct = $tab_struct_fonctions[$code_struct]["#1532"]; // Directeur(ice) de laboratoire
                            }
                            elseif (array_key_exists("#2038", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#2038"]; // Administrateur
                            }
                            elseif (array_key_exists("#38", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#38"]; // Dir. d'UFR
                            }
                            elseif (array_key_exists("#1525", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1525"]; // Directeur adjoint
                            }
                            elseif (array_key_exists("#2039", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#2039"]; // Adjoint à l'administrateur
                            }
                            elseif (array_key_exists("#1523", (array) $tab_struct_fonctions[$code_struct]))
                            {
                                $resp_struct = $tab_struct_fonctions[$code_struct]["#1523"]; // Adjoint(e)
                            }
                            else
                            {
                                $resp_struct = "";
                            }
                        }
                    }
                }
                if ($resp_struct != "")
                {
                    echo "On a un responsable pour la structure $nom_long_struct / $nom_court_struct ($code_struct) => $resp_struct \n";
                }
                else
                {
                    // On récupère le responsable de l'UO (soit grace au poste soit grace directmenet au matricule de l'agent)
                    echo "Pas de fonction pour la structure $nom_long_struct / $nom_court_struct  ($code_struct) dans le fichier des fonctions\n";
                    echo "On recupere le responsable de la structure s'il est defini dans le fichier des structures\n";
                    $resp_struct = $responsableid;
                    // Si pas de responsable défini dans le fichier de structure
                    if ($resp_struct == "")
                    {
                        // Si on arrive ici, c'est vraiment qu'on n'a aucune information nulle part !!!
                        echo "Aucune information recuperee => On fixe le responsable a " . constant('SPECIAL_USER_IDCRONUSER')  . " (CRON G2T) pour la structure $nom_long_struct / $nom_court_struct  ($code_struct) \n";
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
                echo "La structure $nom_long_struct / $nom_court_struct  ($code_struct) n'est pas active => On ne cherche pas le responsable. \n";
            }
            echo "Le code SIHAM du statut de la structure est : $statut_struct \n";
            // Si la structure est active 'ACT'
            if (strcasecmp($statut_struct, 'ACT') == 0)
            {
                if (is_null($date_cloture) or $date_cloture == "")
                {
                    $date_cloture = '2999-12-31';
                }
                // echo "code_struct = $code_struct nom_long_struct=$nom_long_struct nom_court_struct=$nom_court_struct parent_struct=$parent_struct resp_struct=$resp_struct date_cloture=$date_cloture\n";

                if (strcasecmp($businesscategory,"library")==0) // Si c'est une library/bibliotheque => On mémorise la valeur
                {
                    $estbibliotheque = 1;
                }
                else
                {
                    $estbibliotheque = 0;
                }


                $sql = "SELECT * FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
                $query = mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "")
                {
                    echo "SELECT STRUCTURE => $erreur_requete \n";
                }
                if (mysqli_num_rows($query) == 0) // Structure manquante
                {
                    echo "Creation d'une nouvelle structure : $nom_long_struct (Id = $code_struct) \n";
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
                                        $fonctions->my_real_escape_utf8($code_struct),
                                        $fonctions->my_real_escape_utf8($nom_long_struct),
                                        $fonctions->my_real_escape_utf8($nom_court_struct),
                                        $fonctions->my_real_escape_utf8($parent_struct),
                                        $fonctions->my_real_escape_utf8($resp_struct),
                                        $fonctions->my_real_escape_utf8($date_cloture),
                                        $fonctions->my_real_escape_utf8($type_struct),
                                        $isincluded,
                                        $estbibliotheque,
                                        $fonctions->my_real_escape_utf8($externalid)
                            );
                }
                else
                {
                    echo "Mise a jour d'une structure : $nom_long_struct (Id = $code_struct) \n";
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
                                        $fonctions->my_real_escape_utf8($nom_long_struct),
                                        $fonctions->my_real_escape_utf8($nom_court_struct),
                                        $fonctions->my_real_escape_utf8($parent_struct),
                                        $fonctions->my_real_escape_utf8($resp_struct),
                                        $fonctions->my_real_escape_utf8($date_cloture),
                                        $fonctions->my_real_escape_utf8($type_struct),
                                        $isincluded,
                                        $estbibliotheque,
                                        $fonctions->my_real_escape_utf8($externalid),
                                        $fonctions->my_real_escape_utf8($code_struct)
                            );
                    // echo $sql."\n";
                }
                mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "") {
                    echo "INSERT/UPDATE STRUCTURE => $erreur_requete \n";
                    echo "sql = $sql \n";
                }
                // On regarde dans le LDAP s'il y a une correspondance avec une vieille strcture
                // On cherche le code de l'ancienne structure avec le filtre supannRefId={SIHAM.UO} + Code Nvelle (par exemple : supannRefId={SIHAM.UO}DGHA_4)
                // Si une correspondance existe :
                // On charge la vieille structure de G2T => supannCodeEntite: DGHA
                // Si la structure est ouverte => date de cloture > '20151231'
                // On recopie les informations structurantes dans la nouvelle structure :
                // - GESTIONNAIREID varchar(10)
                // - AFFICHESOUSSTRUCT varchar(1)
                // - AFFICHEPLANNINGTOUTAGENT varchar(1)
                // - DEST_MAIL_RESPONSABLE varchar(1)
                // - DEST_MAIL_AGENT varchar(1)
                // - AFFICHERESPSOUSSTRUCT varchar(1)
                // On sauvegarde la nouvelle structure
                // On ferme l'ancienne avec la date du '20151231'
                // On sauvegarde l'ancienne structure
                // Fin Si (structure ouverte)
                // Fin Si (Correspondance existe)

                if ($externalid == $code_struct)
                {
                    echo "On detecte une boucle ancienne struct = nouvelle struct => On ne ferme pas la structure....\n";
                }
                else
                {
//                    $oldsql = "SELECT STRUCTUREID,
//                                      NOMLONG,
//                                      NOMCOURT,
//                                      STRUCTUREIDPARENT,
//                                      RESPONSABLEID,
//                                      GESTIONNAIREID,
//                                      AFFICHESOUSSTRUCT,
//                                      AFFICHEPLANNINGTOUTAGENT,
//                                      DEST_MAIL_RESPONSABLE,
//                                      DEST_MAIL_AGENT,
//                                      DATECLOTURE,
//                                      AFFICHERESPSOUSSTRUCT
//                               FROM STRUCTURE
//                               WHERE STRUCTUREID = '$externalid' ";
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
                               WHERE STRUCTUREID = '$externalid' ";
                    $oldquery = mysqli_query($dbcon, $oldsql);
                    $erreur_requete = mysqli_error($dbcon);
                    if ($erreur_requete != "")
                    {
                        echo "SELECT OLD STRUCTURE => $erreur_requete \n";
                    }
                    if (mysqli_num_rows($oldquery) == 0) // Structure manquante
                    {
                        echo "Pas de correspondance avec l'ancienne structure $externalid \n";
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
//                            $sql = "UPDATE STRUCTURE
//                                    SET GESTIONNAIREID ='$result[5]',
//                                        AFFICHESOUSSTRUCT = '$result[6]',
//                                        AFFICHEPLANNINGTOUTAGENT = '$result[7]',
//                                        DEST_MAIL_RESPONSABLE = '$result[8]',
//                                        DEST_MAIL_AGENT = '$result[9]',
//                                        AFFICHERESPSOUSSTRUCT = '$result[11]' ,
//                                        TYPESTRUCT = '$type_struct',
//                                        ISINCLUDED = '$isincluded'
//                                    WHERE STRUCTUREID = '$code_struct'";
                            $sql = "UPDATE STRUCTURE
                                    SET GESTIONNAIREID ='$result[5]',
                                        AFFICHESOUSSTRUCT = '$result[6]',
                                        AFFICHEPLANNINGTOUTAGENT = '$result[7]',
                                        DEST_MAIL_RESPONSABLE = '$result[8]',
                                        DEST_MAIL_AGENT = '$result[9]',
                                        TYPESTRUCT = '$type_struct',
                                        ISINCLUDED = '$isincluded'
                                    WHERE STRUCTUREID = '$code_struct'";
                            if (substr($code_struct, 0, 3) == 'DGH')
                            {
                                // echo "SQL complement new struct = $sql \n";
                            }
                            mysqli_query($dbcon, $sql);
                            $erreur_requete = mysqli_error($dbcon);
                            if ($erreur_requete != "")
                            {
                                echo "UPDATE STRUCTURE (migration) => $erreur_requete \n";
                                echo "sql = $sql \n";
                            }
                            else
                            {
                                $sql = "UPDATE STRUCTURE SET DATECLOTURE = '20151231' WHERE STRUCTUREID = '$externalid'";
                                mysqli_query($dbcon, $sql);
                                $erreur_requete = mysqli_error($dbcon);
                                if ($erreur_requete != "")
                                {
                                    echo "UPDATE STRUCTURE (cloture) => $erreur_requete \n";
                                    echo "sql = $sql \n";
                                }
                                else
                                {
                                    echo "==> Fermeture de l'ancienne structure '$externalid' a la date du 31/12/2015\n";
                                }
                            }
                        }
                        else
                        {
                            echo "L'ancienne structure $externalid est deja fermee => Pas de recuperation de donnees \n";
                        }
                    }
                }
            }
            elseif (strcasecmp($statut_struct, 'INA') == 0)
            // La structure est inactive ==> On doit la fermer si ce n'est pas déjà fait
            {
                $sql = "SELECT DATECLOTURE FROM STRUCTURE WHERE STRUCTUREID='" . $code_struct . "'";
                $query = mysqli_query($dbcon, $sql);
                $erreur_requete = mysqli_error($dbcon);
                if ($erreur_requete != "")
                {
                    echo "SELECT STRUCTURE (inactif) => $erreur_requete \n";
                }
                if (mysqli_num_rows($query) == 0) // Structure manquante
                {
                    echo "La structure : $nom_long_struct (Id = $code_struct) est inactive dans SIHAM mais n'existe pas dans G2T ! On l'ignore...\n";
                }
                else
                {
                    $result = mysqli_fetch_row($query);
                    $date_cloture_g2t = $result[0];
                    // Si la date de cloture dans G2T est postérieure à la date du jour, alors on met la date de la veille en cloture
                    if ($fonctions->formatdatedb($date_cloture_g2t) >= date("Ymd"))
                    {
                        echo "Mise a jour de la date de cloture d'une structure pour la rendre inactive : $nom_long_struct (Id = $code_struct) \n";
                        //$date_veille = strftime("%Y-%m-%d", mktime(0, 0, 0, date('m'), date('d') - 1, date('y')));
                        $date_veille = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d') - 1, date('y')));
                        echo "Date de la veille = " . $fonctions->formatdatedb($date_veille) . " \n";
                        $sql = "UPDATE STRUCTURE SET DATECLOTURE='" . $fonctions->formatdatedb($date_veille) . "'  WHERE STRUCTUREID = '$code_struct'";
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
                echo "La structure : $nom_long_struct (Id = $code_struct) a un statut dans SIHAM non reconnu par G2T (statut = $statut_struct)...\n";
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