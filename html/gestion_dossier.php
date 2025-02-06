<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    // Initialisation de l'utilisateur
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

    $action = $_POST["action"]; // Action = lecture ou modif
    if (is_null($action) or $action == "")
    {
        $action = 'lecture';
    }
    $mode = $_POST["mode"]; // Action = gestion ou resp
    if (is_null($action) or $action == "")
    {
        $action = MODE_RESPONSABLE;
    }

    // echo "Apres le chargement du user !!! <br>";
    require ("includes/menu.php");

    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    //print_r ( $_POST); echo "<br>"; echo "action = $action <br>";

    $reportlist = null;
    if (isset($_POST['report']))
    {
        $reportlist = $_POST['report'];
    }

    $enfantmaladelist = null;
    if (isset($_POST['enfantmalade']))
    {
        $enfantmaladelist = $_POST['enfantmalade'];
    }

    $cumultotallist = null;
    if (isset($_POST['cumultotal']))
    {
        $cumultotallist = $_POST['cumultotal'];
    }

    $array_agent_mail = null;
    if (isset($_POST['agent_mail']))
    {
        $array_agent_mail = $_POST['agent_mail'];
    }
    $array_resp_mail = null;
    if (isset($_POST['resp_mail']))
    {
        $array_resp_mail = $_POST['resp_mail'];
    }

    $datedebutcetlist = null;
    if (isset($_POST['datedebutcet']))
    {
        $datedebutcetlist = $_POST['datedebutcet'];
    }
    
    if (is_array($reportlist)) {
        foreach ($reportlist as $agentid => $reportvalue) {
            $complement = new complement($dbcon);
            $complement->complementid('REPORTACTIF');
            $complement->agentid($agentid);
            $complement->valeur($reportvalue);
            $complement->store();
            unset($complement);
        }
    }

    $msgerreur = "";
    if (is_array($enfantmaladelist)) {
        foreach ($enfantmaladelist as $agentid => $enfantmaladevalue) {
            // echo "intval >=0 => " . (intval($enfantmaladevalue)>=0) . "<br>";
            if ((strcasecmp(intval($enfantmaladevalue), $enfantmaladevalue) == 0) and (intval($enfantmaladevalue) >= 0)) // Ce n'est pas un nombre à virgule, ni une chaine et la valeur est positive
            {
                $complement = new complement($dbcon);
                $complement->complementid('ENFANTMALADE');
                $complement->agentid($agentid);
                $complement->valeur(intval($enfantmaladevalue));
                $complement->store();
                unset($complement);
            } else {
                $agent = new agent($dbcon);
                $agent->load($agentid);
                $msgerreur = $msgerreur . "Le nombre de jour 'Garde d'enfant' saisi n'est pas correct pour l'agent " . $agent->identitecomplete() . " <br>";
                unset($agent);
            }
        }
    }

    if (is_array($cumultotallist)) {
        foreach ($cumultotallist as $agentid => $cumultotal) {
            if (isset($datedebutcetlist[$agentid])) {
                if ($fonctions->verifiedate($datedebutcetlist[$agentid])) {
                    $cet = new cet($dbcon);
                    $cet->cumultotal($cumultotal);
                    $cet->agentid($agentid);
                    $cet->datedebut($datedebutcetlist[$agentid]);
                    $cet->store();
                }
            }
        }
    }

    $displaysousstructlist = null;
    if (isset($_POST["displaysousstruct"]))
    {
        $displaysousstructlist = $_POST["displaysousstruct"];
    }
    if (is_array($displaysousstructlist)) {
        foreach ($displaysousstructlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->sousstructure($valeur);
            $structure->store();
        }
    }

    $displayallagentlist = null;
    if (isset($_POST["displayallagent"]))
    {
        $displayallagentlist = $_POST["displayallagent"];
    }
    if (is_array($displayallagentlist)) {
        foreach ($displayallagentlist as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->affichetoutagent($valeur);
            $structure->store();
        }
    }

    $gestvalidagent = null;
    if (isset($_POST["gestvalidagent"]))
    {
        $gestvalidagent = $_POST["gestvalidagent"];
    }
    if (is_array($gestvalidagent)) {
        foreach ($gestvalidagent as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->gestvalidagent($valeur);
            $structure->store();
        }
    }
    
    $gestvalidrespstructfille = null;
    if (isset($_POST["gestvalidrespstructfille"]))
    {
        $gestvalidrespstructfille = $_POST["gestvalidrespstructfille"];
    }
    if (is_array($gestvalidrespstructfille)) {
        foreach ($gestvalidrespstructfille as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->gestvalidrespstructfille($valeur);
            $structure->store();
        }
    }

    $respaffsoldesousstruct = null;
    if (isset($_POST["respaffsoldesousstruct"]))
    {
        $respaffsoldesousstruct = $_POST["respaffsoldesousstruct"];
    }
    if (is_array($respaffsoldesousstruct)) {
        foreach ($respaffsoldesousstruct as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->respaffsoldesousstruct($valeur);
            $structure->store();
        }
    }
    
    $respaffdemandesousstruct = null;
    if (isset($_POST["respaffdemandesousstruct"]))
    {
        $respaffdemandesousstruct = $_POST["respaffdemandesousstruct"];
    }
    if (is_array($respaffdemandesousstruct)) {
        foreach ($respaffdemandesousstruct as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->respaffdemandesousstruct($valeur);
            $structure->store();
        }
    }
    
    $agentaffplanningdirection = null;
    if (isset($_POST["agentaffplanningdirection"]))
    {
        $agentaffplanningdirection = $_POST["agentaffplanningdirection"];
    }
    if (is_array($agentaffplanningdirection)) {
        foreach ($agentaffplanningdirection as $structureid => $valeur) {
            $structureid = str_replace("'", "", $structureid);
            $structure = new structure($dbcon);
            $structure->load($structureid);
            $structure->agentaffplanningdirection($valeur);
            $structure->store();
        }
    }

    $arraygestionnaire = null;
    if (isset($_POST["gestion"]))
    {
        $arraygestionnaire = $_POST["gestion"];
    }

    $arrayinfouser = null;
    if (isset($_POST["infouser"]))
    {
        $arrayinfouser = $_POST["infouser"];
    }

    if (is_array($arraygestionnaire)) {
        // ATTENTION : La $valeur est soit le AGENTID soit le UID si on vient de le modifier !!
        foreach ($arraygestionnaire as $structureid => $valeur) 
        {
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfouser[$structureid]) == "") {
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->gestionnaire("");
                $structure->store();
            } 
            else 
            {
                //echo "\$valeur est soit un uid soit un numéro agent : $valeur <br>";
                if (! is_numeric($valeur))
                {
                    // Cas de l'UID
                    $agentgest = $fonctions->createldapagentfromuid($valeur);
                    if ($agentgest===false)
                    {
                        $agentid = null;
                    }
                    else
                    {
                        $agentid = $agentgest->agentid();
                    }
                }
                else
                {
                    //$agentid = $valeur;
                    $agentgest = $fonctions->createldapagentfromagentid($valeur);
                    if ($agentgest===false)
                    {
                        $agentid = null;
                    }
                    else
                    {
                        $agentid = $agentgest->agentid();
                    }
                }
                //echo "Agentid pour le gestionnaire vaut : $agentid <br>";
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $responsable = $structure->responsable();
                if ($agentid == $responsable->agentid())
                {
                    $errlog = "Le responsable de la structure " . $structure->nomlong() . " (" . $structure->nomcourt() . ") ne peut être défini comme gestionnaire.";
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                    echo $fonctions->showmessage(fonctions::MSGERROR, "$errlog");
                    $agentid = "";
                    $structure->gestionnaire("");
                    $structure->store();
                }

                // Si le agentid n'est pas vide ou null
                elseif ($agentid != '' and (! is_null($agentid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure->gestionnaire($agentid);
                    $structure->store();
                }
            }
        }
    }
    
    $arrayinputavisuser = null;
    if (isset($_POST["inputavisuser"]))
    {
        $arrayinputavisuser = $_POST["inputavisuser"];
    }
    $arrayavisuser = null;
    if (isset($_POST["avisuser"]))
    {
        $arrayavisuser = $_POST["avisuser"];
    }
    if (is_array($arrayavisuser))
    {
        foreach ($arrayavisuser as $membreid => $valeur) 
        {
            // Si on n'a pas de nom dans la zone de saisie du consultant => On doit effacer le consultant
            if (trim($arrayinputavisuser[$membreid] . "") == "") {
                $complement = new complement($dbcon);
                $complement->delete($membreid, complement::AVIS_CONGES_LABEL);
            }
            else 
            {
                //var_dump("\$valeur est soit un uid soit un numéro agent : $valeur");
                if (! is_numeric($valeur))
                {
                    $agentavis = $fonctions->createldapagentfromuid($valeur);
                    if ($agentavis===false)
                    {
                        $agentavisid = null;
                    }
                    else
                    {
                        $agentavisid = $agentavis->agentid();
                    }
                }
                else
                {
                    //$agentid = $valeur;
                    $agentavis = $fonctions->createldapagentfromagentid($valeur);
                    if ($agentavis===false)
                    {
                        $agentavisid = null;
                    }
                    else
                    {
                        $agentavisid = $agentavis->agentid();
                    }
                }
                // Si le $agentavisid n'est pas vide ou null
                if ($agentavisid != '' and (! is_null($agentavisid))) 
                {
                    $membre = new agent($dbcon);
                    $membre->load($membreid);
                    $signataire = $membre->getsignataire();
                    if ($signataire===false)
                    {
                        $signataire = new agent($dbcon);
                        $signataire->load(SPECIAL_USER_IDCRONUSER);
                    }
                    if ($agentavisid == $membreid)
                    {
                        if (strlen(trim($msgerreur))>0) { $msgerreur = $msgerreur . "<br>"; }
                        $msgerreur = $msgerreur . $membre->identitecomplete() . " : Vous ne pouvez pas demander la consultation de l'agent lui-même.";
                    }
                    elseif ($agentavisid == $signataire->agentid())
                    {
                        if (strlen(trim($msgerreur))>0) { $msgerreur = $msgerreur . "<br>"; }
                        $msgerreur = $msgerreur . $membre->identitecomplete() . " : " . $signataire->identitecomplete() . " est le responsable de cette personne, il ne peut pas demander son propre avis.";
                    }
                    elseif ($agentavisid == $userid)
                    {
                        if (strlen(trim($msgerreur))>0) { $msgerreur = $msgerreur . "<br>"; }
                        $msgerreur = $msgerreur . $membre->identitecomplete() . " : Vous ne pouvez pas vous octroyer ce droit.";
                    }
                    else
                    {
                        $complement = new complement($dbcon);
                        $complement->agentid($membreid);
                        $complement->complementid(complement::AVIS_CONGES_LABEL);
                        $complement->valeur($agentavisid);
                        $complement->store();
                    }
                }
            }
        }
    }
    

    // ///////////////////////////////////////////////////
    // ---- PARTIE GESTION DE LA DELEGATION -------- //
    // ///////////////////////////////////////////////////
    $arraydelegation = null;
    if (isset($_POST["delegation"]))
    {
        $arraydelegation = $_POST["delegation"];
    }

    $arrayinfodelegation = null;
    if (isset($_POST["infodelegation"]))
    {
        $arrayinfodelegation = $_POST["infodelegation"];
    }

    $arraydatedebut = null;
    if (isset($_POST["date_debut"]))
    {
        $arraydatedebut = $_POST["date_debut"];
    }

    $arraydatefin = null;
    if (isset($_POST["date_fin"]))
    {
        $arraydatefin = $_POST["date_fin"];
    }

    $arraycontinuesendtoresp = null;
    if (isset($_POST['continuesendtoresp']))
    {
        $arraycontinuesendtoresp = $_POST['continuesendtoresp'];
    }

    $hiddeninput = null;
    if (isset($_POST['hiddeninput']))
    {
        $hiddeninput = $_POST['hiddeninput'];
    }
    
    

    if (is_array($arraydelegation)) 
    {
        // ATTENTION : La $valeur est soit le AGENTID soit le UID si on vient de le modifier !!
        foreach ($arraydelegation as $structureid => $valeur) {
            $resp_est_delegue = false;
            // echo "dans le foreach <br>";
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfodelegation[$structureid]) == "") {
                // echo "On supprime la personne déléguée....<br>";
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $delegation = new delegation;
                $delegation->delegationuserid = "";
                $delegation->datedebutdeleg = "1900-01-01";
                $delegation->datefindeleg = "1900-01-01";
                $delegation->continuesendtoresp = "n";
                $delegation->auteurmodifdeleg = $userid;
                $structure->setdelegation($delegation);
            } else {
                // echo "Dans le else avant le filtre LDAP <br>";
                //echo "\$valeur est soit un uid soit un numéro agent : $valeur <br>";
                if (! is_numeric($valeur))
                {
                    // On va chercher dans le LDAP la correspondance UID => AGENTID
                    $delegagent = $fonctions->createldapagentfromuid($valeur);
                    if ($delegagent === false)
                    {
                        $agentid = null;
                    }
                    else
                    {
                        $agentid = $delegagent->agentid();
                    }
                }
                else
                {
                    $agentid = $valeur;
                }
                //echo "agentid = $agentid <br>";
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) 
                {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    // On ne peut pas mettre le responsable de la structure comme délégué
                    if ($agentid == $user->agentid()) {
                        // On récupère la liste des structures ou l'utilisateur est responsable (sens strict)
                        $structrespliste = $user->structrespliste(false);
                        // Si la structure courante est définie dans le tableau des structures
                        // On ne peut pas le mettre délégué
                        if (isset($structrespliste[$structureid])) 
                        {
                            $resp_est_delegue = true;
                        }
                    }
                    

                    if ($resp_est_delegue) 
                    {
                        $error = "Vous ne pouvez pas saisir le responsable (" . $user->identitecomplete() . ") de la structure '" . $structure->nomlong() . "' comme délégué.<br>La délégation n'est pas enregistrée.";
                        echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                    } else {
                        $datedebutdeleg = "";
                        if (isset($arraydatedebut[$structure->id()]))
                        {
                            $datedebutdeleg = $arraydatedebut[$structure->id()];
                        }
                        $datefindeleg = "";
                        if (isset($arraydatefin[$structure->id()]))
                        {
                            $datefindeleg = $arraydatefin[$structure->id()];
                        }

                        // Si la case à cocher est masquée => On désactive automatiquement la fonction "continuesendtoresptostore"
                        if (isset($hiddeninput[$structure->id()]))
                        {
                            $continuesendtoresptostore = 'n';
                        }
                        else
                        {
                            $continuesendtoresptostore = 'n';
                            if (isset($arraycontinuesendtoresp[$structure->id()]))
                            {
                                //var_dump("Le flag arraycontinuesendtoresp pour la structure " . $structure->id() . " est présent " );
                                $continuesendtoresptostore = 'o';
                            }
                        }
                        //var_dump($continuesendtoresptostore);
                        
                        $delegation = $structure->getdelegation();
                        $delegationuserid = $delegation->delegationuserid;
                        $datedebutdelegbd = $delegation->datedebutdeleg;
                        $datefindelegbd = $delegation->datefindeleg;
                        $continuesendtoresp = $delegation->continuesendtoresp;

                        //var_dump("J'ai récup la délégation => $continuesendtoresp");
                        if ($datedebutdelegbd == "")
                        {
                            $datedebutdelegbd = "01/01/1900";
                        }
                        if ($datefindelegbd == "")
                        {
                            $datefindelegbd = "01/01/1900";
                        }
                        // echo "datedebutdeleg = $datedebutdeleg datefindeleg = $datefindeleg <br>";
                        if ($datedebutdeleg == "" or $datefindeleg == "") 
                        {
                            $error = "Un agent délégué est saisi, mais la date de début ou la date de fin de la période est vide.<br>La délégation n'est pas enregistrée.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                        } 
                        elseif (($delegationuserid == $agentid)
                            and ($fonctions->formatdate($datedebutdelegbd) == $fonctions->formatdate($datedebutdeleg))
                            and $fonctions->formatdate($datefindelegbd) == $fonctions->formatdate($datefindeleg)
                            and $continuesendtoresp == $continuesendtoresptostore)
                        {
                            // On a donné les mêmes paramétres que ceux de la base de données => On ne fait rien
                            $errlog = "Pas d'enregistrement de la délégation car les données sont identiques à celles en base de données pour " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $agentid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                        else 
                        {
                            
                            // echo "On enregistre la delegation.... <br>";
                            $delegation = new delegation;
                            $delegation->delegationuserid = $agentid;
                            $delegation->datedebutdeleg = $datedebutdeleg;
                            $delegation->datefindeleg = $datefindeleg;
                            $delegation->continuesendtoresp = $continuesendtoresptostore;
                            $delegation->auteurmodifdeleg = $userid;
                            $structure->setdelegation($delegation);
                            $errlog = "Enregistrement d'une délégation sur " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $agentid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                            echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
                            $errlog = $user->identitecomplete() . " : " . $errlog;
                            // echo $errlog."<br/>";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                    }
                }
            }
        }
    }

    if (isset($array_agent_mail)) {
        // On modifie les codes des envois de mail pour les agents et les responsables
        foreach ($array_agent_mail as $structkey => $codeinterne) {
            $structure = new structure($dbcon);
            $structure->load($structkey);
            $structure->agent_envoyer_a($codeinterne, true);
        }
    }
    if (isset($array_resp_mail)) {
        // On modifie les codes des envois de mail pour les agents et les responsables
        foreach ($array_resp_mail as $structkey => $codeinterne) {
            $structure = new structure($dbcon);
            $structure->load($structkey);
            $structure->resp_envoyer_a($codeinterne, true);
        }
    }

    echo "<br>";
    if ($msgerreur != "") {
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($msgerreur));
        echo $fonctions->showmessage(fonctions::MSGERROR, "$msgerreur");
    }

    $full_formulairetexte_openpdf = '';

    echo "<form name='frm_dossier'  method='post' >";
    if ($mode == MODE_RESPONSABLE) {
        $structliste = $user->structrespliste();
        $structrespliste = $user->structrespliste(false);
    }
    if ($mode == MODE_GESTION) {
        $structliste = $user->structgestliste();
        $structrespliste = array();
    }
    if (is_array($structliste))
    {
        uasort($structliste,"triparprofondeurabsolue");
    }
    // echo "Structure liste = "; print_r($structliste); echo "<br>";
    foreach ($structliste as $key => $structure) {
        $responsableliste = array();
        // On ajoute les responsables de structures filles
        if ($mode == MODE_RESPONSABLE)
        {
            $structurefilleliste = $structure->structurefille();
            if (is_array($structurefilleliste)) 
            {
                foreach ($structurefilleliste as $key => $structurefille) 
                {
                    if ($fonctions->formatdatedb($structurefille->datecloture()) >= $fonctions->formatdatedb(date("Ymd"))) 
                    {
                        $respstructfille = $structurefille->responsable();
                        if ($respstructfille->agentid() != SPECIAL_USER_IDCRONUSER) 
                        {
                            // La clé NOM + PRENOM + AGENTID permet de trier les éléments par ordre alphabétique
                            $responsableliste[$respstructfille->nom() . " " . $respstructfille->prenom() . " " . $respstructfille->agentid()] = $respstructfille;
                            // /$responsableliste[$responsable->agentid()] = $responsable;
                        }
                    }
                }
            }
        }
        
        $arrayagent = $structure->agentlist(date('d/m/Y'), date('d/m/Y'), 'n');
        $arrayagentid = array();
        foreach ((array)$arrayagent as $agent)
        {
            $arrayagentid[$agent->agentid()] = $agent;
        }

        if (count((array)$arrayagent) or count($responsableliste)>0) 
        {
            $formulairetext_openpdf = '';
            if ($mode == MODE_RESPONSABLE)
            {
                echo $structure->dossierhtml(($action == 'modif'), $userid, $formulairetext_openpdf);
            }
            else
            {
                echo $structure->dossierhtml(($action == 'modif'), null);
            }

            $full_formulairetexte_openpdf = $full_formulairetexte_openpdf . " " . $formulairetext_openpdf;
            // var_dump($full_formulairetexte_openpdf);
            echo "<table>";
            echo "<tr>";
            echo "<td>";
            echo "Voir le planning des agents des sous-structures dans le planning de la structure <b>" . $structure->nomcourt()  . "</b> (responsable G2T/gestionnaire G2T) : ";
            echo "</td><td>";
            if ($action == 'modif') 
            {
                echo "<select name=displaysousstruct['" . $structure->id() . "']>";
                echo "<option value='o'";
                if ($fonctions->convertvaluetobool($structure->sousstructure()))
                {
                    echo " selected ";
                }
                echo ">Oui</option>";
                echo "<option value='n'";
                if (!$fonctions->convertvaluetobool($structure->sousstructure()))
                {
                    echo " selected ";
                }
                echo ">Non</option>";
                echo "</select>";
            } 
            else
            {
                echo $fonctions->ouinonlibelle($structure->sousstructure());
            }
            echo "</td>";
            echo "</tr>";
            
            if ($mode == MODE_RESPONSABLE) 
            {
                // La possibilité de gérer tous les agents des structures inclues n'est offerte que si on est dans la strcuture "racine" (<=> non inclue)
                if (!$structure->isincluded())
                {
//                    echo "<br>";
                    echo "<tr>";
                    echo "<td>";
                    echo "Afficher le solde de tous les agents des sous-structures (responsable G2T uniquement) : ";
                    echo "</td><td>";
                    if ($action == 'modif') {
                        echo "<select name=respaffsoldesousstruct['" . $structure->id() . "']>";
                        echo "<option value='o'";
                        if ($fonctions->convertvaluetobool($structure->respaffsoldesousstruct()))
                        {
                            echo " selected ";
                        }
                        echo ">Oui</option>";
                        echo "<option value='n'";
                        if (!$fonctions->convertvaluetobool($structure->respaffsoldesousstruct()))
                        {
                            echo " selected ";
                        }
                        echo ">Non</option>";
                        echo "</select>";
                    } 
                    else
                    {
                        echo $fonctions->ouinonlibelle($structure->respaffsoldesousstruct());
                    }
                    echo "</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td>";
                    echo "Gérer les demandes de congés de tous les agents des sous-structures (responsable G2T uniquement) : ";
                    echo "</td><td>";
                    if ($action == 'modif') {
                        echo "<select name=respaffdemandesousstruct['" . $structure->id() . "']>";
                        echo "<option value='o'";
                        if ($fonctions->convertvaluetobool($structure->respaffdemandesousstruct()))
                        {
                            echo " selected ";
                        }
                        echo ">Oui</option>";
                        echo "<option value='n'";
                        if (!$fonctions->convertvaluetobool($structure->respaffdemandesousstruct()))
                        {
                            echo " selected ";
                        }
                        echo ">Non</option>";
                        echo "</select>";
                    } 
                    else
                    {
                        echo $fonctions->ouinonlibelle($structure->respaffdemandesousstruct());
                    }
                    echo "</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td>";
                    echo "Dans le planning de la structure (menu Agent), permettre de voir le planning des agents de la structure <b>" . $structure->nomcourt()  . "</b> : ";
                    echo "</td><td>";
                    if ($action == 'modif') {
                        echo "<select name=agentaffplanningdirection['" . $structure->id() . "']>";
                        echo "<option value='o'";
                        if ($fonctions->convertvaluetobool($structure->agentaffplanningdirection()))
                        {
                            echo " selected ";
                        }
                        echo ">Oui</option>";
                        echo "<option value='n'";
                        if (!$fonctions->convertvaluetobool($structure->agentaffplanningdirection()))
                        {
                            echo " selected ";
                        }
                        echo ">Non</option>";
                        echo "</select>";
                    } 
                    else
                    {
                        echo $fonctions->ouinonlibelle($structure->agentaffplanningdirection());
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            }

            echo "<tr>";
            echo "<td>";
            echo "Autoriser la consultation du planning de la structure <b>" . $structure->nomcourt() . "</b> par tous les agents de celle-ci : ";
            echo "</td><td>";
            if ($action == 'modif') 
            {
                echo "<select name=displayallagent['" . $structure->id() . "']>";
                echo "<option value='o'";
                if ($fonctions->convertvaluetobool($structure->affichetoutagent()))
                {
                    echo " selected ";
                }
                echo ">Oui</option>";
                echo "<option value='n'";
                if (!$fonctions->convertvaluetobool($structure->affichetoutagent()))
                {
                    echo " selected ";
                }
                echo ">Non</option>";
                echo "</select>";
            } 
            else
            {
                echo $fonctions->ouinonlibelle($structure->affichetoutagent());
            }
            echo "</td>";
            echo "</tr>";

            if ($mode == MODE_RESPONSABLE) 
            {
//                echo "<br>";
                echo "<tr>";
                echo "<td>";
                echo "Autoriser la validation des demandes des agents de la structure <b>" . $structure->nomcourt() . "</b> par le gestionnaire G2T : ";
                echo "</td><td>";
                if ($action == 'modif') {
                    echo "<select name=gestvalidagent['" . $structure->id() . "']>";
                    echo "<option value='o'";
                    if ($fonctions->convertvaluetobool($structure->gestvalidagent()))
                    {
                        echo " selected ";
                    }
                    echo ">Oui</option>";
                    echo "<option value='n'";
                    if (!$fonctions->convertvaluetobool($structure->gestvalidagent()))
                    {
                        echo " selected ";
                    }
                    echo ">Non</option>";
                    echo "</select>";
                } 
                else
                {
                    echo $fonctions->ouinonlibelle($structure->gestvalidagent());
                }
                echo "</td>";
                echo "</tr>";

                // Si la structure n'a pas de structures filles => On n'affiche pas ce paramétrage
                $structlistefille = $structure->structurefille();
                //if (count((array)$structlistefille)>0 and false)
                if (count((array)$structlistefille)>0 and date('Ymd') >= '20240901')
                {
                    echo "<tr>";
                    echo "<td>";
                    echo "Autoriser la validation des demandes du responsable des structures filles par le gestionnaire G2T de <b>" . $structure->nomcourt() . "</b> :";
                    echo "</td><td>";
                    if ($action == 'modif') {
                        echo "<select name=gestvalidrespstructfille['" . $structure->id() . "']>";
                        echo "<option value='o'";
                        if ($fonctions->convertvaluetobool($structure->gestvalidrespstructfille()))
                        {
                            echo " selected ";
                        }
                        echo ">Oui</option>";
                        echo "<option value='n'";
                        if (!$fonctions->convertvaluetobool($structure->gestvalidrespstructfille()))
                        {
                            echo " selected ";
                        }
                        echo ">Non</option>";
                        echo "</select>";
                    } 
                    else
                    {
                        echo $fonctions->ouinonlibelle($structure->gestvalidrespstructfille());
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            }

            echo "</table>";
            echo "<br>";


            if ($mode == MODE_RESPONSABLE) 
            {
                // $arrayagent = $structure->agentlist(date('d/m/Y'), date('d/m/Y'), 'n');
                // $arrayagentid = array();
                // foreach ((array)$arrayagent as $agent)
                // {
                //     $arrayagentid[$agent->agentid()] = $agent;
                // }

                echo "<table>";

                // Si aucun agent n'est dans la structure, on ne doit pas afficher le paramétrage des signatures des agents
                if (count((array)$arrayagentid)>0)
                {
                    $structure->agent_envoyer_a($codeinterne);
                    echo "<tr>";
                    echo "<td>";
                    echo "Envoyer les demandes des agents au : ";
                    echo "<SELECT id='agent_mail[" . $structure->id() . "]' name='agent_mail[" . $structure->id() . "]' size='1' onchange='user_mode_change_" . $structure->id() . "()'>";
                    echo "<OPTION value=" . structure::MAIL_AGENT_ENVOI_RESP_COURANT;
                    if ($codeinterne == structure::MAIL_AGENT_ENVOI_RESP_COURANT)
                    {
                        echo " selected='selected' ";
                    }
                    echo ">Responsable G2T du service " . $structure->nomcourt() . "</OPTION>";
                    echo "<OPTION value=" . structure::MAIL_AGENT_ENVOI_GEST_COURANT;
                    if ($codeinterne == structure::MAIL_AGENT_ENVOI_GEST_COURANT)
                    {
                        echo " selected='selected' ";
                    }
                    echo ">Gestionnaire G2T du service " . $structure->nomcourt() . "</OPTION>";
                    echo "</SELECT>";
                    echo "</td>";
                    echo "<td>";
                    echo "<label id='agent_send_identity[" . $structure->id() . "]' ></label>";
                    echo "</td>";
                    echo "</tr>";
                }

                $parentstruct = null;
                $parentstruct = $structure->parentstructure();

                //var_dump($arrayagentid);
                //var_dump($struct->responsable()->agentid());
                //var_dump($struct->responsablesiham()->agentid());

                // Si le responsable n'est pas affecté dans la structure, on ne doit pas proposer de gérer les demandes du responsable
                if (key_exists($structure->responsable()->agentid(),$arrayagentid) or (key_exists($structure->responsablesiham()->agentid(),$arrayagentid)))
                {
                    $structure->resp_envoyer_a($codeinterne);
                    echo "<tr>";
                    echo "<td>";
                    echo "Envoyer les demandes du responsable G2T au : ";
                    echo "<SELECT id='resp_mail[" . $structure->id() . "]'  name='resp_mail[" . $structure->id() . "]' size='1' onchange='resp_mode_change_" . $structure->id() . "()'>";
                    if (! is_null($parentstruct)) {
    //                    echo "<OPTION value=1";
                        echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_RESP_PARENT;
                        if ($codeinterne == structure::MAIL_RESP_ENVOI_RESP_PARENT)
                        {
                            echo " selected='selected' ";
                        }
                        echo ">Responsable G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
    //                    echo "<OPTION value=2";
                        echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_GEST_PARENT;
                        if ($codeinterne == structure::MAIL_RESP_ENVOI_GEST_PARENT)
                        {
                            echo " selected='selected' ";
                        }
                        echo ">Gestionnaire G2T du service " . $parentstruct->nomcourt() . "</OPTION>";
                    }
    //                echo "<OPTION value=3";
                    echo "<OPTION value=" . structure::MAIL_RESP_ENVOI_GEST_COURANT;
                    if ($codeinterne == structure::MAIL_RESP_ENVOI_GEST_COURANT)
                    {
                        echo " selected='selected' ";
                    }
                    echo ">Gestionnaire G2T du service " . $structure->nomcourt() . "</OPTION>";
                    echo "</SELECT>";
                    echo "</td>";
                    echo "<td>";
                    echo "<label id='resp_send_identity[" . $structure->id() . "]' ></label>";
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
?>
<script>
    
    function user_mode_change_<?php echo $structure->id(); ?>()
    {
        //alert('User mode change');
        var select_tag = document.getElementById('agent_mail[<?php echo $structure->id()?>]');
        var agent_send_identity = document.getElementById('agent_send_identity[<?php echo $structure->id(); ?>]');
        //var currentvalue = select_tag.selectedIndex+1;
        //if (currentvalue===1)
        if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_AGENT_ENVOI_RESP_COURANT; ?>)
        {
            agent_send_identity.innerHTML = '<?php echo $structure->responsable()->identitecomplete();  ?>';
        }
        //else if (currentvalue===2)
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_AGENT_ENVOI_GEST_COURANT; ?>)
        {
            agent_send_identity.innerHTML = '<?php if (!is_null($structure->gestionnaire())) { echo $structure->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        else
        {
            alert ('Index inconnu !!');
        }
    }

    function resp_mode_change_<?php echo $structure->id(); ?>()
    {
        //alert('Resp mode change');
        var select_tag = document.getElementById('resp_mail[<?php echo $structure->id()?>]');
        var resp_send_identity = document.getElementById('resp_send_identity[<?php echo $structure->id(); ?>]');
        //var currentvalue = select_tag.selectedIndex+1;
        //if (currentvalue===1)
        if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_RESP_PARENT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($parentstruct) and !is_null($parentstruct->responsable())) { echo $parentstruct->responsable()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        //else if (currentvalue===2)
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_GEST_PARENT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($parentstruct) and !is_null($parentstruct->gestionnaire())) { echo $parentstruct->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        //else if (currentvalue===3)
        else if (select_tag.options[select_tag.selectedIndex].value==<?php echo structure::MAIL_RESP_ENVOI_GEST_COURANT; ?>)
        {
            resp_send_identity.innerHTML = '<?php if (!is_null($structure->gestionnaire())) { echo $structure->gestionnaire()->identitecomplete(); } else { echo 'Non défini'; } ?>';
        }
        else
        {
            alert ('Index inconnu !!');
        }
    }
    var select_tag = document.getElementById('agent_mail[<?php echo $structure->id()?>]');
    select_tag.addEventListener('change', () =>
        user_mode_change_<?php echo $structure->id(); ?>()
        );
    var e = new Event("change");
    select_tag.dispatchEvent(e);

    var select_tag = document.getElementById('resp_mail[<?php echo $structure->id()?>]');
    select_tag.addEventListener('change', () =>
        resp_mode_change_<?php echo $structure->id(); ?>()
        );
    var e = new Event("change");
    select_tag.dispatchEvent(e);
</script>
<?php
                echo "<table>";
                $gestionnaire = $structure->gestionnaire();
                echo "\n<tr>";
                echo "<td>Nom du gestionnaire G2T : ";
                echo "<input id='infouser[" . $structure->id() . "]' name='infouser[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                $style = '';
                $extrainfo = '';
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->identitecomplete();
                    if (!$gestionnaire->isG2tUser())
                    {
                        $style = " class='kobackgroundtext' ";
                        $extrainfo = "<b><span class='redtext'> &#x1F828; Le gestionnaire défini n'a pas accès à l'application G2T. Veuillez le modifier ou contacter la DRH.</span></b>";
                    }
                }
                echo "' size=40 $style/>$extrainfo";
                //
                echo "<input type='hidden' id='gestion[" . $structure->id() . "]' name='gestion[" . $structure->id() . "]' value='";
                if (! is_null($gestionnaire))
                {
                    echo $gestionnaire->agentid();
                }
                echo "' class='infouser[" . $structure->id() . "]' /> ";
?>
		    <script>
    		    	$('[id="<?php echo "infouser[". $structure->id() ."]" ?>"]').autocompleteUser(
    		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
    		  	                          wsParams: { filter_eduPersonAffiliation: "employee|researcher" } });
                    </script>
<?php
                echo "</td></tr>";

                // si la structure est dans la liste des structures ou l'agent est responsable (au sens strict)
                if (isset($structrespliste[$structure->id()])) {
                    echo "<tr><td>";

                    // $delegationuserid = "";
                    // $datedebutdeleg = "";
                    // $datefindeleg = "";
                    $delegation = $structure->getdelegation();
                    $delegationuserid = $delegation->delegationuserid;
                    $datedebutdeleg = $delegation->datedebutdeleg;
                    $datefindeleg = $delegation->datefindeleg;
                    $continuesendtoresp = $delegation->continuesendtoresp;

                    //var_dump ("delegationuserid = $delegationuserid, datedebutdeleg = $datedebutdeleg, datefindeleg = $datefindeleg continuesendtoresp = $continuesendtoresp");
                    $delegationuser = null;
                    if ($delegationuserid != "") {
                        $delegationuser = new agent($dbcon);
                        if (! $delegationuser->load($delegationuserid))
                        {
                            $erreur = "Impossible de charger la personne déléguée.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $erreur);
                            $delegationuser = null;
                        }
                    }
                    echo "Délégation de responsabilité à ";
                    echo "<input id='infodelegation[" . $structure->id() . "]' name='infodelegation[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
                    $style = '';
                    $extrainfo = '';
                    if (! is_null($delegationuser))
                    {
                        echo $delegationuser->identitecomplete();
                        if (!$delegationuser->isG2tUser())
                        {
                            $style = " class='kobackgroundtext' ";
                            $extrainfo = "<b><span class='redtext'> &#x1F828; Le délégué défini n'a pas accès à l'application G2T. Veuillez le modifier ou contacter la DRH.</span></b>";
                        }
                    }
                    echo "' size=40 $style />$extrainfo";

                    //echo "<div>";
                    echo "<input type='hidden' id='delegation[" . $structure->id() . "]' name='delegation[" . $structure->id() . "]' value='";
                    if (! is_null($delegationuser))
                    {
                        echo $delegationuser->agentid();
                    }
                    echo "' class='infodelegation[" . $structure->id() . "]' />";
                    //echo "</div>";
                    echo "<input type='checkbox' id='hiddeninput[" . $structure->id() . "]' name='hiddeninput[" . $structure->id() . "]' hidden ";
                    if (isset($hiddeninput[$structure->id()]))
                    {
                        echo " checked ";
                    }
                    echo " />";

?>
                    <script>
                        ///////////////////////////////////////////////////////
                        // C'est la fonction de callback lorsqu'on sélectionne un agent dans l'autocomplete
                        function updatedelegue_<?php echo $structure->id()?>(event, ui)
                        {
                            // NB: this event is called before the selected value is set in the "input"
                            //console.log("Fired updatedelegue !" + Date.now());
                            var form = $(this).closest("form");
                            var selectedInput = document.activeElement;
                            if (ui)
                            {
                                form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
                                form.find("[class='" + selectedInput.name + "']").val (ui.item.value);
                            }

                            var structid = "<?php echo $structure->id()?>";
                            var listeagent = "#<?php 
                                $listeagent = $structure->agentlist(date("d/m/Y"), date("d/m/Y"), 'n'); 
                                foreach((array)$listeagent as $agent) 
                                { 
                                    echo $agent->agentid() . "#" . strtoupper($agent->uid()) . "#";
                                }
                            ?>";
                            var agentidinput = document.getElementById("delegation[" + structid + "]");
                            var divcontinue = document.getElementById("divcontinuesendtoresp[" + structid + "]");
                            var hiddeninput = document.getElementById("hiddeninput[" + structid + "]");
                            var infodelegation = document.getElementById("infodelegation[" + structid + "]");
                            var nomdeleguelabel = document.getElementById("nomdeleguelabel[" + structid + "]");
                            if (agentidinput && divcontinue && hiddeninput && nomdeleguelabel && infodelegation)
                            {
                                if (listeagent.toUpperCase().includes(agentidinput.value.toUpperCase()))
                                {
                                    divcontinue.hidden = false;
                                    hiddeninput.checked = false;
                                    if ((infodelegation.value.trim() + "") != '')
                                    {
                                        nomdeleguelabel.innerText = infodelegation.value;
                                    }
                                }
                                else
                                {
                                    divcontinue.hidden = true;
                                    hiddeninput.checked = true;
                                }
                            }
                            if ((infodelegation.value.trim() + "") == '')
                            {
                                divcontinue.hidden = true;
                            }
                            return false;
                        }

                        function cleardelegue_<?php echo $structure->id()?>(event, ui)
                        {
                            //console.log ("fired cleardelegue " + Date.now());

                            var structid = "<?php echo $structure->id()?>";
                            var divcontinue = document.getElementById("divcontinuesendtoresp[" + structid + "]");
                            if (divcontinue)
                            {
                                    divcontinue.hidden = true;
                            }
                            return false;
                        }
                    </script>
				    <script>
        		    	$('[id="<?php echo "infodelegation[". $structure->id() ."]" ?>"]').autocompleteUser(
        		  	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { minLength : 4 , noFetch : cleardelegue_<?php echo $structure->id(); ?> , disableEnterKey: true, select: updatedelegue_<?php echo $structure->id(); ?>, wantedAttr: "uid",
        		  	                          wsParams: { filter_eduPersonAffiliation: "employee" } });
        	   		</script>
<?php

                    echo "</td></tr>";
                    // Définition des ID des calendriers puis génération des scripts "personnalisés" pour l'affichage (mindate, maxdate...)
                    $calendrierid_deb = "date_debut";
                    $calendrierid_fin = "date_fin";
                    ?>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker("getDate"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	});
        });
        </script>
    <script>
        $(function()
        {
        	$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("maxperiode")});
        	$('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').change(function () {
        			$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker("destroy");
        			$('[id="<?php echo $calendrierid_deb . '[' . $structure->id() . "]" ?>"]').datepicker({minDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').attr("minperiode"), maxDate: $('[id="<?php echo $calendrierid_fin . '[' . $structure->id() . "]" ?>"]').datepicker("getDate")});
        	});
        });
        </script>
    <?php
                    echo "<tr><td class='delegpaddingleft'>";
                    echo "Début de la période de délégation :";
                    if ($fonctions->verifiedate($datedebutdeleg)) {
                        $datedebutdeleg = $fonctions->formatdate($datedebutdeleg);
                    }
                    ?>
    <input class="calendrier" type=text
    	name=<?php echo $calendrierid_deb . '[' . $structure->id() . ']'?>
    	id=<?php echo $calendrierid_deb . '[' . $structure->id() .']'?> size=10
    	minperiode='<?php echo date("d/m/Y"); // $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datedebutdeleg ?>'>
    <?php
                    echo "Fin de la période de délégation :";
                    if ($fonctions->verifiedate($datefindeleg)) {
                        $datefindeleg = $fonctions->formatdate($datefindeleg);
                    }

                    ?>
    <input class="calendrier" type=text
    	name=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	id=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
    	size=10
    	minperiode='<?php echo date("d/m/Y"); //$fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
    	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
    	value='<?php echo $datefindeleg ?>'>
    <?php
                    echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td class='delegpaddingleft'>";
                    echo "<div id='divcontinuesendtoresp[" . $structure->id() . "]'>";
                    $checked = '';
                    if ($fonctions->convertvaluetobool($continuesendtoresp))
                    {
                        $checked = ' checked ';
                    }
                    echo "<input type='checkbox' $checked name='continuesendtoresp[" . $structure->id() . "]' id='continuesendtoresp[" . $structure->id() . "]'>";
                    //echo "En cochant cette case, " . $structure->responsablesiham()->identitecomplete()  . " continue de recevoir les notifications des demandes de congés/d'absences et de télétravail par mail durant la délégation.</input>";
                    //echo "En cochant cette case, " . $structure->responsablesiham()->identitecomplete()  . " continue de gérer les demandes de congés/d'absences de <label id=nomdeleguelabel[" . $structure->id() . "]></label> durant cette période.</input>";

                    $tiptext = "En cochant cette case, le délégué peut agir comme un responsable, mais reste sous la reponsabilité de celui-ci.\n";
                    $tiptext = $tiptext . "Le responsable :\n";
                    $tiptext = $tiptext . "\t&#x2022; reçoit les notifications des demandes des agents. Le délégué les reçoit également. \n";
                    $tiptext = $tiptext . "\t&#x2022; peut valider les demandes de congés/d'absences des agents et du délégué. \n";
                    $tiptext = $tiptext . "\t&#x2022; est intégré dans le circuit de validation du CET et du télétravail des agents avec le délégué. \n";
                    $tiptext = $tiptext . "ATTENTION : Les demandes du délégué sont validées par le responsable de la structure courante. \n";
                    $tiptext = $tiptext . "\n";
                    $tiptext = $tiptext . "En ne cochant pas cette case, le délégué se substitue au responsable (poste vacant, absence longue durée).\n";
                    $tiptext = $tiptext . "Le responsable :\n";
                    $tiptext = $tiptext . "\t&#x2022; ne reçoit pas les notifications des demandes des agents. Seul le délégué est notifié. \n";
                    $tiptext = $tiptext . "\t&#x2022; peut valider les demandes de congés/d'absences des agents et du délégué. \n";
                    $tiptext = $tiptext . "\t&#x2022; est intégré dans le circuit de validation du CET et du télétravail des agents avec le délégué. \n";
                    $tiptext = $tiptext . "ATTENTION : Les demandes du délégué peuvent être validées par le responsable de la structure courante ou parente. \n";
            
                    echo "En cochant cette case, le délégué (<label id=nomdeleguelabel[" . $structure->id() . "]></label>) reste sous la responsabilité hiérarchique de " . $structure->responsablesiham()->identitecomplete()  . " <span class='cursorpointer redtext fontsize25' data-title=\"$tiptext\">&#x1F6C8;</span>.</input>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                    // On appelle la fonction pour afficher/masquer la case à cocher pour le responsable du délégué
                    echo "<script>updatedelegue_" . $structure->id() ."();</script>";
                }
//                echo "<tr><td height=15></td></tr>";
                echo "</table>";
            }
            //echo "<br><br><br>";
            echo "<br><br>";
        }
    }

    echo "<input type='hidden' name='userid' value=" . $user->agentid() . ">";
    echo "<input type='hidden' name='action' value=" . $action . ">";
    echo "<input type='hidden' name='mode' value='" . $mode . "'>";

//    echo "<br><br>action = $action <br><br>";
    if ($action == 'modif')
    {
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
    }
    echo "</form>";
    echo "$full_formulairetexte_openpdf";

?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>

