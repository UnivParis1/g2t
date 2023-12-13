<?php

    // require_once ('CAS.php');
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
    
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";
    
    if (isset($_POST["structureid"]))
        $structureid = $_POST["structureid"];
    else
        $structureid = null;
            
    $arraydelegation = null;
    if (isset($_POST["delegation"]))
        $arraydelegation = $_POST["delegation"];
    
    $arrayinfodelegation = null;
    if (isset($_POST["infodelegation"]))
        $arrayinfodelegation = $_POST["infodelegation"];
            
    $arraydatedebut = null;
    if (isset($_POST["date_debut"]))
        $arraydatedebut = $_POST["date_debut"];
                
    $arraydatefin = null;
    if (isset($_POST["date_fin"]))
        $arraydatefin = $_POST["date_fin"];
    
    $showall = false;
            
    
    //print_r ($_POST); echo "<br>";
    
    
    if (is_array($arraydelegation)) {
        // ATTENTION : La $valeur est soit le AGENTID soit le UID si on vient de le modifier !!
        foreach ($arraydelegation as $structureid => $valeur) {
            $resp_est_delegue = false;
            // echo "dans le foreach <br>";
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfodelegation[$structureid]) == "") 
            {
                // echo "On supprime la personne déléguée....<br>";
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->setdelegation("", "1900-01-01", "1900-01-01", $userid);
            } 
            else 
            {
                //echo "\$valeur est soit un uid soit un numéro agent : $valeur <br>";
                if (! is_numeric($valeur)) 
                {
                    // On va chercher dans le LDAP la correspondance UID => AGENTID
                    $agentid = $fonctions->useridfromCAS($valeur);
                    if ($agentid === false)
                    {
                        $agentid = null;
                    }
                }
                else
                {
                    $agentid = $valeur;
                }

                //echo "agentid = $agentid <br>";
                // Si le agentid n'est pas vide ou null
                if ($agentid != '' and (! is_null($agentid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    $resp = $structure->responsablesiham();
                    // On ne peut pas mettre le responsable de la structure comme délégué
                    if ($agentid == $resp->agentid()) {
                        // On récupère la liste des structures ou l'utilisateur est responsable (sens strict)
                        $structrespliste = $resp->structrespliste(false);
                        // Si la structure courante est définie dans le tableau des structures
                        // On ne peut pas le mettre délégué
                        if (isset($structrespliste[$structureid])) {
                            $resp_est_delegue = true;
                        }
                    }
                    
                    if ($resp_est_delegue) 
                    {
                        $error = "Vous ne pouvez pas saisir le responsable (" . $resp->identitecomplete() . ") de la structure '" . $structure->nomlong() . "' comme délégué.<br>La délégation n'est pas enregistrée.";
                        echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                    } else {
                        $datedebutdeleg = "";
                        if (isset($arraydatedebut[$structure->id()]))
                            $datedebutdeleg = $arraydatedebut[$structure->id()];
                        $datefindeleg = "";
                        if (isset($arraydatefin[$structure->id()]))
                            $datefindeleg = $arraydatefin[$structure->id()];
                                
                        // echo "datedebutdeleg = $datedebutdeleg datefindeleg = $datefindeleg <br>";
                        if ($datedebutdeleg == "" or $datefindeleg == "") 
                        {
                            $error = "Un agent délégué est saisi, mais la date de début ou la date de fin de la période est vide.<br>La délégation n'est pas enregistrée.";
                            echo $fonctions->showmessage(fonctions::MSGERROR, $error);
                        } else {
                            // echo "On enregistre la delegation.... <br>";
                            $structure->setdelegation($agentid, $datedebutdeleg, $datefindeleg, $userid);
                            $errlog = "Enregistrement d'une délégation sur " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $agentid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                            echo $fonctions->showmessage(fonctions::MSGINFO, $errlog);
                            $errlog = $resp->identitecomplete() . " : " . $errlog;
                            // echo $errlog."<br/>";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                    }
                }
            }
        }
    }
    
    $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT = '' OR STRUCTUREIDPARENT NOT IN (SELECT DISTINCT STRUCTUREID FROM STRUCTURE) ORDER BY STRUCTUREIDPARENT"; // NOMLONG
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "") {
        $errlog = "Gestion Structure Chargement des structures parentes : " . $erreur;
        echo $errlog . "<br/>";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    echo "<form name='selectstructure'  method='post' >";
    echo "<select size='1' id='structureid' name='structureid'>";
    while ($result = mysqli_fetch_row($query)) {
        $struct = new structure($dbcon);
        $struct->load($result[0]);
        affichestructureliste($struct, 0);
    }
    echo "</select>";
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br>";
    echo " <input type='submit' name= 'Valid_struct' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";
    echo "<br>";
        
    $delegationuserid = "";
    $datedebutdeleg = "";
    $datefindeleg = "";
    
    if (!is_null($structureid))
    {
        $structure = new structure($dbcon);
        $structure->load($structureid);
        $structure->getdelegation($delegationuserid, $datedebutdeleg, $datefindeleg);
        $resp = $structure->responsablesiham();
        
        echo 'Le responsable de la structure "' . $structure->nomlong() . ' (' . $structure->nomcourt()  . ')" est ' . $resp->identitecomplete() . '<br><br>';
        // echo "delegationuserid = $delegationuserid, datedebutdeleg = $datedebutdeleg, datefindeleg = $datefindeleg <br>";
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
        echo "<form name='frm_dossier'  method='post' >";
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
        echo "' size=40 $style/>$extrainfo";
    
        echo "<input type='hidden' id='delegation[" . $structure->id() . "]' name='delegation[" . $structure->id() . "]' value='";
        if (! is_null($delegationuser))
            echo $delegationuser->agentid();
        echo "' class='infodelegation[" . $structure->id() . "]' /> ";
?>
	    <script>
        	$('[id="<?php echo "infodelegation[". $structure->id() ."]" ?>"]').autocompleteUser(
      	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
      	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
    	</script>
<?php
        
        //echo "</td></tr>";
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
        echo "<p class='delegpaddingleft'>";
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
        echo "</p>";
    
        echo "<input type='hidden' name='userid' value=" . $user->agentid() . ">";
        echo "<input type='hidden' name='structureid' value=" . $structureid . ">";
        echo "<br><br>";
        echo "<input type='submit' class='g2tbouton g2tvalidebouton' value='Enregistrer' />";
        echo "</form>";
    }
?>

</body>
</html>

