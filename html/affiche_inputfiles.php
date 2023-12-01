<?php

    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    $userid = null;
    if (isset($_POST["userid"]))
    {
        // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
        $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
        if ($CASuserId!==false)
        {
            // On a l'agentid de l'agent => C'est un administrateur donc on peut forcer le userid avec la valeur du POST
            $userid = $_POST["userid"];
        }
        else
        {
            $userid = $fonctions->useridfromCAS($uid);
            if ($userid === false)
            {
                $userid = null;
            }
        }
    }
    
        
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
        //header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    if (isset($_POST["agentid"]) and $_POST["agentid"]!='') 
    {
        $agentid = $_POST["agentid"];
        $agent = new agent($dbcon);
        $agent->load($agentid);
    } 
    else
    {
        $agentid = null;
    }
    
    $date = date("Ymd");
    if (isset($_POST["date"]))
    {
        $date = $_POST["date"];
    }
    
    require ("includes/menu.php");

    //echo "POST = "; print_r($_POST); echo "<br>";

    
    $inputfilepath = $fonctions->inputfilepath();
    $scandir = scandir("$inputfilepath" . '/');
    //var_dump($scandir);

    $datearray = array();
    foreach($scandir as $fichier)
    {
        // On cherche le nombre de fichier siham_agents_*.xml
        if(preg_match("#siham_agents_[0-9]+\.xml$#",strtolower($fichier)))
        {
            // On va supprimer le début et la fin du nom du fichier pour ne conserver que la date du fichier
            // Donc on décrit ici le format du nom du fichier => ALPHA_ALPHA_NUM.xml
            $pattern = '/[a-z]+_[a-z]+_([0-9]+)\.xml/i';
            // On ne garde que le 1er bloc entre () du nom du fichier => back reference \1 
            $replacement = '\1';
            $datestr = preg_replace($pattern, $replacement, $fichier);
            $datearray[] = $datestr;
        }
    }
    // On trie (descending sort) le tableau sur les valeurs (date) 
    rsort($datearray);
    
    echo "<br>";
    echo "<form name='infos_agent' method='post'>";

    echo "Sélectionnez la date du fichier :<br>";
    echo "<select size='1'  id='date' name='date'>";
    
    foreach ($datearray as $currentdate)
    {
        $selected = '';
        if ($currentdate == $date)
        {
            $selected = 'selected';
        }
        echo "<option value='$currentdate' $selected >" . $fonctions->formatdate($currentdate) . "</option>";
    }
    echo "</select>";
    echo "<br>";
    echo "<br>";
    
    echo "Liste des agents : <br>";
    $agentsliste = $fonctions->listeagentsg2t();
    echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
    echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
    foreach ($agentsliste as $key => $identite)
    {
        $selected = '';
        if ($agentid == $key)
        {
            $selected = 'selected';
        }
        echo "<option value='$key' $selected >$identite</option>";
    }
    echo "</select>";
    echo "<br>";
    echo "<br>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<input type='submit' value='Soumettre'>";
    echo "</form>";
    echo "<br>";
    echo "<br>";
    
    //$date = date("Ymd");
    $bgcolor_ok = "#c5efbd"; // "#87d478";
    $bgcolor_erreur = "#f1948a";
    
    $filemissing = false;
    $identite_trouve = false;
    $aff_situation_trouve = false;
    $aff_modalite_trouve = false;
    $aff_statut_trouve = false;
    $aff_structure_trouve = false;

    if (!is_null($agentid))
    {
        
        echo "<hr>";
        $filename = $inputfilepath . "/siham_agents_$date.xml";
        if (! file_exists($filename)) {
            echo "Le fichier $filename n'existe pas !!! <br>";
            $filemissing = true;
        } 
        else 
        {
            $xml = simplexml_load_file("$filename");
            echo "<B>Affichage de l'identité de l'agent <label style='background-color : $bgcolor_erreur'>OBLIGATOIRE</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//AGENTS/AGENT[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $civilite = trim($node->xpath('CIVIL')[0]);
                $nom = trim($node->xpath('NOM')[0]);
                $prenom = trim($node->xpath('PRENOM')[0]);
                $adressemail = trim($node->xpath('MAIL')[0]);
                $typepop = trim($node->xpath('CATEGORIE')[0]);

                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=6 align=center ><div style='color:#BF3021'>Affichage de l'identité de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Civilité</th>
                                                      <th class='cellulesimple' >Nom</th>
                                                      <th class='cellulesimple' >Prénom</th>
                                                      <th class='cellulesimple' >Mail</th>
                                                      <th class='cellulesimple' >Catégorie/Type population</th>";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $extracolor = " style='background-color : $bgcolor_ok' ";
                    $identite_trouve = true;
                    $htmltext = $htmltext . "<tr align=center $extracolor >";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$civilite</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$nom</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$prenom</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$adressemail</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$typepop</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }
        
        echo "<hr>";
        $filename = $inputfilepath . "/siham_absence_$date.xml";
        if (! file_exists($filename)) {
            echo "Le fichier $filename n'existe pas !!! <br>";
            $filemissing = true;
        } 
        else 
        {
            $xml = simplexml_load_file("$filename");
            echo "<B>Affichage des absences de l'agent <label style='background-color : $bgcolor_ok'>FACULTATIF</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//ABSENCES/ABSENCE[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $datedebut = trim($node->xpath('DATEDEBUT')[0]);
                $datefin = trim($node->xpath('DATEFIN')[0]);
                $typeabsence = trim($node->xpath('LIBELLE')[0]);
                $datedebutformate = $fonctions->formatdate(str_replace('/','-',$datedebut));
                $datefinformate = $fonctions->formatdate(str_replace('/','-',$datefin));
                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=4 align=center ><div style='color:#BF3021'>Affichage des absences de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Date début</th>
                                                      <th class='cellulesimple' >Date fin</th>
                                                      <th class='cellulesimple' >Type d'absence (SIHAM)</th>";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $htmltext = $htmltext . "<tr align=center >";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datedebutformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datefinformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$typeabsence</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }
        
        echo "<hr>";
        $filename = $inputfilepath . "/siham_fonctions_$date.xml";
        if (! file_exists($filename)) {
            echo "Le fichier $filename n'existe pas !!! <br>";
            $filemissing = true;
        } 
        else 
        {
            $xml = simplexml_load_file("$filename");
            echo "<B>Affichage des fonctions de l'agent (niveau dossier agent) <label style='background-color : $bgcolor_ok'>FACULTATIF</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//FONCTIONS/FONCTION[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $code_fonction = trim($node->xpath('CONDEFONCT')[0]);
                $libelle_fctn_cours = trim($node->xpath('NOMCOURT')[0]);
                $libelle_fctn_long = trim($node->xpath('NOMLONG')[0]);
                $code_struct = '';
                if (count($node->xpath('STRUCTID'))>0)
                {
                    $code_struct = trim($node->xpath('STRUCTID')[0]);
                }    
                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=5 align=center ><div style='color:#BF3021'>Affichage des fonctions de l'agent " . $agent->identitecomplete() . " (niveau dossier agent)</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Code la fonction</th>
                                                      <th class='cellulesimple' >Libellé court</th>
                                                      <th class='cellulesimple' >Libellé long</th>
                                                      <th class='cellulesimple' >Code de la structure</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $htmltext = $htmltext . "<tr align=center >";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$code_fonction</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$libelle_fctn_cours</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$libelle_fctn_long</td>";
                    $structure = new structure($dbcon);
                    $structure->load($code_struct);
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$code_struct (" . $structure->nomcourt() . ")</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }

        echo "<hr>";
        $filename = $inputfilepath . "/siham_structures_$date.xml";
        if (! file_exists($filename)) 
        {
            echo "Le fichier $filename n'existe pas !!! <br>";
            $filemissing = true;
        } 
        else 
        {        
            $xml = simplexml_load_file("$filename");
            echo "<B>Affichage des structures où l'agent est responsable (niveau UO SIHAM) <label style='background-color : $bgcolor_ok'>FACULTATIF</label></B><br>";
            $htmltext = '';
            $premiereligne = true;
            
            $nodelist = $xml->xpath('//STRUCTURES/STRUCTURE[RESPID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
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
                $dateclotureformate = $fonctions->formatdate(str_replace('/','-',$date_cloture));

                if ($responsableid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=8 align=center ><div style='color:#BF3021'>Affichage des structures où l'agent " . $agent->identitecomplete() . " est responsable (niveau UO SIHAM)</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Code de la structure</th>
                                                      <th class='cellulesimple' >Nom long de la structure</th>
                                                      <th class='cellulesimple' >Nom court de la structure</th>
                                                      <th class='cellulesimple' >Code de la structure parente</th>
                                                      <th class='cellulesimple' >Type de la structure</th>
                                                      <th class='cellulesimple' >Statut de la structure</th>
                                                      <th class='cellulesimple' >Date de cloture</th>
                                                      <th class='cellulesimple' >Responsable de la structure (Agent ID)</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $htmltext = $htmltext . "<tr align=center >";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$code_struct</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$nom_long_struct</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$nom_court_struct</td>";
                    $structure = new structure($dbcon);
                    $structure->load($parent_struct);
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$parent_struct (" . $structure->nomcourt() . ")</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$type_struct</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$statut_struct</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$dateclotureformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$responsableid</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }
        
        echo "<hr>";
        $situationfile = $inputfilepath . "/siham_affectations_situation_$date.xml";
        if (! file_exists($situationfile)) 
        {
            echo "Le fichier $situationfile n'existe pas !!! <br>";
            $filemissing = true;
        }
        else
        {
            $xml = simplexml_load_file("$situationfile");
            echo "<B>Affichage des situations/activités de l'agent <label style='background-color : $bgcolor_erreur'>OBLIGATOIRE</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//SITUATIONS/SITUATION[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $numligne = trim($node->xpath('NUMLIGNE')[0]);
                $codesituation = trim($node->xpath('CODE')[0]);
                $datedebut = trim($node->xpath('DATEDEBUT')[0]);
                $datefin = trim($node->xpath('DATEFIN')[0]);
                $datedebutformate = $fonctions->formatdate(str_replace('/','-',$datedebut));
                $datefinformate = $fonctions->formatdate(str_replace('/','-',$datefin));
                
                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=5 align=center ><div style='color:#BF3021'>Affichage des situations/activités de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Numéro de ligne SIHAM</th>
                                                      <th class='cellulesimple' >Code situation</th>
                                                      <th class='cellulesimple' >Date de début</th>
                                                      <th class='cellulesimple' >Date de fin</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $extracolor = '';
                    if ($fonctions->formatdatedb($datefinformate)>=date('Ymd') and $fonctions->formatdatedb($datedebutformate)<=date('Ymd'))
                    {
                        $extracolor = " style='background-color : $bgcolor_ok' ";
                        $aff_situation_trouve = true;
                    }
                    $htmltext = $htmltext . "<tr align=center $extracolor>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$numligne</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$codesituation</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datedebutformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datefinformate</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }

        echo "<hr>";
        $modalitefile = $inputfilepath . "/siham_affectations_modalite_$date.xml";
        if (! file_exists($modalitefile)) 
        {
            echo "Le fichier $modalitefile n'existe pas !!! <br>";
            $filemissing = true;
        }
        else
        {
            $xml = simplexml_load_file("$modalitefile");
            echo "<B>Affichage des modalités de travail de l'agent (quotité) <label style='background-color : $bgcolor_erreur'>OBLIGATOIRE</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//MODALITES/MODALITE[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $numligne = trim($node->xpath('NUMLIGNE')[0]);
                $numquotite = trim($node->xpath('QUOTITE')[0]);
                $datedebut = trim($node->xpath('DATEDEBUT')[0]);
                $datefin = trim($node->xpath('DATEFIN')[0]);
                $datedebutformate = $fonctions->formatdate(str_replace('/','-',$datedebut));
                $datefinformate = $fonctions->formatdate(str_replace('/','-',$datefin));
                
                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=5 align=center ><div style='color:#BF3021'>Affichage des modalités de travail (quotité) de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Numéro de ligne SIHAM</th>
                                                      <th class='cellulesimple' >Quotité de travail</th>
                                                      <th class='cellulesimple' >Date de début</th>
                                                      <th class='cellulesimple' >Date de fin</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $extracolor = '';
                    if ($fonctions->formatdatedb($datefinformate)>=date('Ymd') and $fonctions->formatdatedb($datedebutformate)<=date('Ymd'))
                    {
                        $extracolor = " style='background-color : $bgcolor_ok' ";
                        $aff_modalite_trouve = true;
                    }
                    $htmltext = $htmltext . "<tr align=center $extracolor>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$numligne</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$numquotite %</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datedebutformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datefinformate</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }

        echo "<hr>";
        $statutfile = $inputfilepath . "/siham_affectations_status_$date.xml";
        if (! file_exists($statutfile)) 
        {
            echo "Le fichier $statutfile n'existe pas !!! \n";
            $filemissing = true;
        }
        else
        {
            $xml = simplexml_load_file("$statutfile");
            echo "<B>Affichage des statuts de l'agent <label style='background-color : $bgcolor_erreur'>OBLIGATOIRE</label></B><br>";
            $htmltext = '';
            $premiereligne = true;
            
            $nodelist = $xml->xpath('//STATUTS/STATUT[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $numligne = trim($node->xpath('NUMLINGE')[0]);
                $codecontrat = trim($node->xpath('TYPECONTRAT')[0]);
                $datedebut = trim($node->xpath('DATEDEBUT')[0]);
                $datefin = trim($node->xpath('DATEFIN')[0]);
                $datedebutformate = $fonctions->formatdate(str_replace('/','-',$datedebut));
                $datefinformate = $fonctions->formatdate(str_replace('/','-',$datefin));
                
                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=5 align=center ><div style='color:#BF3021'>Affichage des statuts de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Numéro de ligne SIHAM</th>
                                                      <th class='cellulesimple' >Statut</th>
                                                      <th class='cellulesimple' >Date de début</th>
                                                      <th class='cellulesimple' >Date de fin</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $extracolor = '';
                    if ($fonctions->formatdatedb($datefinformate)>=date('Ymd') and $fonctions->formatdatedb($datedebutformate)<=date('Ymd'))
                    {
                        $extracolor = " style='background-color : $bgcolor_ok' ";
                        $aff_statut_trouve = true;
                    }
                    $htmltext = $htmltext . "<tr align=center $extracolor>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$numligne</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$codecontrat</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datedebutformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datefinformate</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }
        
        echo "<hr>";
        $structurefile = $inputfilepath . "/siham_affectations_structures_$date.xml";
        if (! file_exists($structurefile)) 
        {
            echo "Le fichier $structurefile n'existe pas !!! <br>";
            $filemissing = true;
        }
        else
        {
            $xml = simplexml_load_file("$structurefile");
            echo "<B>Affichage des affectations fonctionnelles de l'agent <label style='background-color : $bgcolor_erreur'>OBLIGATOIRE</label></B><br>";
            $htmltext = '';
            $premiereligne = true;

            $nodelist = $xml->xpath('//AFF_STRUCTURES/AFF_STRUCTURE[AGENTID = ' . $agentid  .']');
            foreach ($nodelist as $node)
            {
                $inputagentid = trim($node->xpath('AGENTID')[0]);
                $numligne = trim($node->xpath('NUMLIGNE')[0]);
                $idstruct = trim($node->xpath('STRUCTID')[0]);
                $datedebut = trim($node->xpath('DATEDEBUT')[0]);
                $datefin = trim($node->xpath('DATEFIN')[0]);
                $datedebutformate = $fonctions->formatdate(str_replace('/','-',$datedebut));
                $datefinformate = $fonctions->formatdate(str_replace('/','-',$datefin));

                if ($inputagentid == $agentid)
                {
                    // Si c'est le premier noeud
                    if ($premiereligne)
                    {
                        $htmltext = $htmltext . "<table id='table_identite' class='tableausimple'>";
                        $htmltext = $htmltext . "<thead>";
                        $htmltext = $htmltext . "   <tr ><td class='titresimple' colspan=5 align=center ><div style='color:#BF3021'>Affichage des affectations fonctionnelles de l'agent " . $agent->identitecomplete() . "</div></td></tr>";
                        $htmltext = $htmltext . "   <tr align=center>
                                                      <th class='cellulesimple' >Id de l'agent</th>
                                                      <th class='cellulesimple' >Numéro de ligne SIHAM</th>
                                                      <th class='cellulesimple' >Code structure</th>
                                                      <th class='cellulesimple' >Date de début</th>
                                                      <th class='cellulesimple' >Date de fin</th>
                                                      ";
                        $htmltext = $htmltext . "   </tr>";
                        $htmltext = $htmltext . "</thead>";
                        $htmltext = $htmltext . "<tbody>";
                        $premiereligne = false;
                    }
                    $extracolor = '';
                    if ($fonctions->formatdatedb($datefinformate)>=date('Ymd') and $fonctions->formatdatedb($datedebutformate)<=date('Ymd'))
                    {
                        $extracolor = " style='background-color : $bgcolor_ok' ";
                        $aff_structure_trouve = true;
                    }
                    $htmltext = $htmltext . "<tr align=center $extracolor>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$inputagentid</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$numligne</td>";
                    $structure = new structure($dbcon);
                    $structure->load($idstruct);
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$idstruct (" . $structure->nomcourt() . ")</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datedebutformate</td>";
                    $htmltext = $htmltext . "   <td class='cellulesimple'>$datefinformate</td>";
                    $htmltext = $htmltext . "</tr>";
                }
            }
            if ($htmltext != "")
            {
                $htmltext = $htmltext . "</tbody>";
                $htmltext = $htmltext . "</table>";
            }
            else
            {
                $htmltext = "Aucune information trouvée dans le fichier d'interface.<br>";
            }
            echo $htmltext;
        }
        echo "<hr>";
    }
    
    if (!is_null($agentid))
    {
        echo "<B>Vérification que l'agent est dans le groupe G2T :</B><br>";
        if ($agent->isG2tUser())
        {
            echo "<label style='background-color : $bgcolor_ok'>L'agent " . $agent->identitecomplete() . " est dans le groupe G2T => Il peut se connecter.</label><br>";
        }
        else
        {
            echo "<label style='background-color : $bgcolor_erreur'>L'utilisateur " . $agent->identitecomplete() . " n'est pas dans le groupe G2T => Il ne peut pas se connecter.</label><br>";
        }
        echo "<br>";
    }
    if (!is_null($agentid))
    {
        echo "<B>Bilan de l'intégration de l'agent dans G2T</B><br>";
        if (!$filemissing)
        {
            if (!$identite_trouve or !$aff_situation_trouve or !$aff_modalite_trouve or !$aff_statut_trouve or !$aff_structure_trouve)
            {
                echo "<B><label style='background-color: $bgcolor_erreur; font-size: large;'>Le dossier de l'agent " . $agent->identitecomplete() . " semble incomplet </label></B></br><br>";
            }
            else
            {
                echo "<B><label style='background-color:$bgcolor_ok; font-size: large;'>Le dossier de l'agent " . $agent->identitecomplete() . " semble complet </label></B></br><br>";
            }
        }
        else
        {
            echo "<B><label style='background-color: $bgcolor_erreur; font-size: large;'>Au moins un fichier est manquant. Impossible de faire une analyse du dossier.</label></B></br><br>";
        }
    }
?>
</body>
</html>

