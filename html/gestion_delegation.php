<?php

    require_once ('CAS.php');
    include './includes/casconnection.php';
    
    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
    
    require_once ("./includes/all_g2t_classes.php");
/*
    require_once ("./class/agent.php");
    require_once ("./class/structure.php");
    require_once ("./class/solde.php");
    require_once ("./class/demande.php");
    require_once ("./class/planning.php");
    require_once ("./class/planningelement.php");
    require_once ("./class/declarationTP.php");
    // require_once("./class/autodeclaration.php");
    // require_once("./class/dossier.php");
    require_once ("./class/fpdf/fpdf.php");
    require_once ("./class/cet.php");
    require_once ("./class/affectation.php");
    require_once ("./class/complement.php");
    require_once ("./class/alimentationCET.php");
    require_once ("./class/optionCET.php");
*/
    
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
         // Initialisation des infos LDAP
        $LDAP_SERVER = $fonctions->liredbconstante("LDAPSERVER");
        $LDAP_BIND_LOGIN = $fonctions->liredbconstante("LDAPLOGIN");
        $LDAP_BIND_PASS = $fonctions->liredbconstante("LDAPPASSWD");
        $LDAP_SEARCH_BASE = $fonctions->liredbconstante("LDAPSEARCHBASE");
        $LDAP_CODE_AGENT_ATTR = $fonctions->liredbconstante("LDAPATTRIBUTE");
        $con_ldap = ldap_connect($LDAP_SERVER);
        ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($con_ldap, $LDAP_BIND_LOGIN, $LDAP_BIND_PASS);
        
        // ATTENTION : La $valeur est soit le HARPEGEID soit le UID si on vient de le modifier !!
        foreach ($arraydelegation as $structureid => $valeur) {
            $resp_est_delegue = false;
            // echo "dans le foreach <br>";
            // Si on n'a pas de nom dans la zone de saisie du gestionnaire => On doit effacer le gestionnaire
            if (trim($arrayinfodelegation[$structureid]) == "") {
                // echo "On supprime la personne déléguée....<br>";
                $structure = new structure($dbcon);
                $structure->load($structureid);
                $structure->setdelegation("", "", "", $userid);
            } else {
                // echo "Dans le else avant le filtre LDAP <br>";
                // On va chercher dans le LDAP la correspondance UID => HARPEGEID
                $filtre = "(uid=" . $valeur . ")";
                $dn = $LDAP_SEARCH_BASE;
                $restriction = array(
                    "$LDAP_CODE_AGENT_ATTR"
                );
                $sr = ldap_search($con_ldap, $dn, $filtre, $restriction);
                $info = ldap_get_entries($con_ldap, $sr);
                // echo "Le numéro HARPEGE du responsable est : " . $info[0]["$LDAP_CODE_AGENT_ATTR"][0] . " pour la structure " . $structure->nomlong() . "<br>";
                if (isset($info[0]["$LDAP_CODE_AGENT_ATTR"][0])) {
                    $harpegeid = $info[0]["$LDAP_CODE_AGENT_ATTR"][0];
                } else {
                    $harpegeid = $valeur;
                }
                // echo "Harpegeid = $harpegeid <br>";
                // Si le harpegeid n'est pas vide ou null
                if ($harpegeid != '' and (! is_null($harpegeid))) {
                    // $structureid = str_replace("'", "", $structureid);
                    $structure = new structure($dbcon);
                    $structure->load($structureid);
                    $resp = $structure->responsablesiham();
                    // On ne peut pas mettre le responsable de la structure comme délégué
                    if ($harpegeid == $resp->harpegeid()) {
                        // On récupère la liste des structures ou l'utilisateur est responsable (sens strict)
                        $structrespliste = $resp->structrespliste(false);
                        // Si la structure courante est définie dans le tableau des structures
                        // On ne peut pas le mettre délégué
                        if (isset($structrespliste[$structureid])) {
                            $resp_est_delegue = true;
                        }
                    }
                    
                    if ($resp_est_delegue) {
                        echo "<FONT SIZE='2pt' COLOR='#FF0000'><B>Vous ne pouvez pas saisir le responsable (" . $resp->identitecomplete() . ") de la structure '" . $structure->nomlong() . "' comme délégué.</B><br>La délégation n'est pas enregistrée.</FONT><br>";
                    } else {
                        $datedebutdeleg = "";
                        if (isset($arraydatedebut[$structure->id()]))
                            $datedebutdeleg = $arraydatedebut[$structure->id()];
                        $datefindeleg = "";
                        if (isset($arraydatefin[$structure->id()]))
                            $datefindeleg = $arraydatefin[$structure->id()];
                                
                        // echo "datedebutdeleg = $datedebutdeleg datefindeleg = $datefindeleg <br>";
                        if ($datedebutdeleg == "" or $datefindeleg == "") {
                            echo "<FONT SIZE='5pt' COLOR='#FF0000'><B>Un agent délégué est saisi, mais la date de début ou la date de fin de la période est vide !!!</B><br>La délégation n'est pas enregistrée.</FONT><br>";
                        } else {
                            // echo "On enregistre la delegation.... <br>";
                            $structure->setdelegation($harpegeid, $datedebutdeleg, $datefindeleg, $userid);
                            $errlog = $resp->identitecomplete() . " : Enregistrement d'une délégation sur " . $structure->nomlong() . " (" . $structure->nomcourt() . ") : Agent délégué => $harpegeid   Date de début => $datedebutdeleg   Date de fin => $datefindeleg";
                            // echo $errlog."<br/>";
                            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                        }
                    }
                }
            }
        }
    }
    
    
    function affichestructureliste($structure, $niveau = 0)
    {
        global $dbcon;
        global $structureid;
        global $fonctions;
        global $showall;
        // $fonctions = new fonctions($dbcon);
        if ($showall or ($fonctions->formatdatedb($structure->datecloture()) >= $fonctions->formatdatedb(date("Ymd")))) {
            echo "<option value='" . $structure->id() . "'";
            if ($structure->id() == $structureid) {
                echo " selected ";
            }
            if ($fonctions->formatdatedb($structure->datecloture()) < $fonctions->formatdatedb(date("Ymd"))) {
                echo " style='color:red;' ";
            }
            echo ">";
            for ($cpt = 0; $cpt < $niveau; $cpt ++) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            echo " - " . $structure->nomlong() . " (" . $structure->nomcourt() . ")";
            echo "</option>";
            
            $sousstruclist = $structure->structurefille();
            foreach ((array) $sousstruclist as $keystruct => $soustruct) {
                affichestructureliste($soustruct, $niveau + 1);
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
    echo "<input type='hidden' name='userid' value='" . $user->harpegeid() . "'>";
    echo "<br>";
    echo " <input type='submit' name= 'Valid_struct' value='Soumettre' >";
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
            $delegationuser->load($delegationuserid);
        }
        echo "<form name='frm_dossier'  method='post' >";
        echo "Délégation de responsabilité à ";
        echo "<input id='infodelegation[" . $structure->id() . "]' name='infodelegation[" . $structure->id() . "]' placeholder='Nom et/ou prenom' value='";
        if (! is_null($delegationuser))
            echo $delegationuser->identitecomplete();
        echo "' size=40 />";
            
        echo "<input type='hidden' id='delegation[" . $structure->id() . "]' name='delegation[" . $structure->id() . "]' value='";
        if (! is_null($delegationuser))
            echo $delegationuser->harpegeid();
        echo "' class='infodelegation[" . $structure->id() . "]' /> ";
?>
    <script>
        	$('[id="<?php echo "infodelegation[". $structure->id() ."]" ?>"]').autocompleteUser(
      	       '<?php echo "$WSGROUPURL"?>/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: "uid",
      	                          wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: "employee" } });
    </script>
<?php
        
        echo "</td>";
        echo "<td>";
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
    
        echo "Début de la période :";
        if ($fonctions->verifiedate($datedebutdeleg)) {
            $datedebutdeleg = $fonctions->formatdate($datedebutdeleg);
        }
?>
        <td width=1px><input class="calendrier" type=text
        	name=<?php echo $calendrierid_deb . '[' . $structure->id() . ']'?>
        	id=<?php echo $calendrierid_deb . '[' . $structure->id() .']'?> size=10
        	minperiode='<?php echo date("d/m/Y"); // $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datedebutdeleg ?>'></td>
<?php
        echo "</td>";
        echo "<td>";
        echo "Fin de la période :";
        if ($fonctions->verifiedate($datefindeleg)) {
            $datefindeleg = $fonctions->formatdate($datefindeleg);
        }
        
?>
        <td width=1px><input class="calendrier" type=text
        	name=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
        	id=<?php echo $calendrierid_fin . '[' . $structure->id() . ']' ?>
        	size=10
        	minperiode='<?php echo date("d/m/Y"); //$fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()); ?>'
        	maxperiode='<?php echo $fonctions->formatdate($fonctions->anneeref()+1 . $fonctions->finperiode()); ?>'
        	value='<?php echo $datefindeleg ?>'></td>
<?php
        echo "</td>";
        echo "</tr>";
        echo "</table>";
    
        echo "<input type='hidden' name='userid' value=" . $user->harpegeid() . ">";
        echo "<input type='hidden' name='structureid' value=" . $structureid . ">";
        echo "<br><br>";
        echo "<input type='submit' value='Soumettre' />";
        echo "</form>";
    }
?>

</body>
</html>

