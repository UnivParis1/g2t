<?php
    ini_set('max_execution_time', '1200'); //300 seconds = 5 minutes  1200 seconds = 20 minutes
    header('X-Accel-Buffering: no'); // pour nginx
    header("Content-Type: text/html");
    
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
        
    // On regarde si l'utilisateur CAS est un admin G2T (retourne l'agentid si admin sinon false)
    $CASuserId = $fonctions->CASuserisG2TAdmin($uid);
    if ($CASuserId===false)
    {
        // Ce n'est pas un administrateur
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ") => Pas administrateur");
        echo "<script>alert('Accès réservé aux administrateurs de l\'application !'); window.location.replace('index.php');</script>";
        //        header('Location: index.php');
        exit();
    }
    
    $user = new agent($dbcon);
    $user->load($userid);
    
    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";
    
    $anneeref = $fonctions->anneeref();
    $annee_courante = substr($anneeref,2,2); // On ne garde que les 2 derniers chiffres de l'année
    $annee_precedente = $annee_courante - 1;
    $annee_future = $annee_courante + 1;
    
    echo "<br>L'année de référence est : $anneeref.<br>";
    
    //$sql = "SELECT DISTINCT SOLDE.AGENTID, NOM, PRENOM FROM SOLDE, AGENT WHERE TYPEABSENCEID IN ('ann20', 'ann21') AND SOLDE.AGENTID = AGENT.AGENTID";
    //$sql = "SELECT DISTINCT SOLDE.AGENTID, NOM, PRENOM FROM SOLDE, AGENT WHERE TYPEABSENCEID IN ('ann20', 'ann21') AND SOLDE.AGENTID = AGENT.AGENTID AND AGENT.AGENTID IN ('9328','3715','19803', '24606', '13825','90223')";

    $sql = "SELECT DISTINCT SUB1.AGENTID, NOM, PRENOM
            FROM AGENT,((SELECT AGENTID 
                           FROM SOLDE S1 
                           WHERE S1.TYPEABSENCEID = 'ann$annee_precedente'
                             AND S1.DROITAQUIS <> 0
                        )
                        UNION
                        (SELECT AGENTID 
                           FROM SOLDE S2
                           WHERE S2.TYPEABSENCEID = 'ann$annee_courante'
                             AND S2.DROITAQUIS <> 0
                        )) SUB1
            WHERE SUB1.AGENTID = AGENT.AGENTID
              -- AND NOM BETWEEN 'A' AND 'G'
            ORDER BY NOM, PRENOM";
    
    $query = mysqli_query($dbcon, $sql);
    $erreur = mysqli_error($dbcon);
    if ($erreur != "")
    {
        $errlog = "Affiche_Info_conges : Erreur dans la lecture en base => " . $erreur;
        echo "$errlog \n";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    ob_start();
    if (($numligne = mysqli_num_rows($query)) == 0)
    {
        //echo "<br>load => pas de ligne dans la base de données<br>";
        $errlog = "Affiche_Info_conges : Aucune ligne dans la base de données correspondante";
        echo "$errlog \n";
        error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
    }
    else
    {
        echo "Il va y avoir $numligne ligne(s) dans le tableau <br>";
    }

    $count = 0;
    echo "<table class='tableausimple' >";
    echo "<tr><td class='titresimple'>Matricule</td><td class='titresimple'>Identité agent</td><td class='titresimple'>Droit 20$annee_precedente/20$annee_courante</td><td class='titresimple'>Solde 20$annee_precedente/20$annee_courante au 31/08/20$annee_courante</td><td class='titresimple'>Congés 20$annee_precedente/20$annee_courante entre le 01/09 et le 31/12</td><td class='titresimple'>Solde 20$annee_courante/20$annee_future</td><td class='titresimple'>Droit 20$annee_courante/20$annee_future pris</td></tr>";
    ob_flush();
    flush();
    $htmlstring = "";
    while ($result = mysqli_fetch_row($query)) 
    {
        
//        if ($result[0] != '9328')
//        {
//            continue;
//        }
        
        $count ++;
        $agent = new agent($dbcon);
        $agent->load($result[0]);
        //echo "Identité = " . $agent->identitecomplete() ." <br>";
        $solde_precedent = new solde($dbcon); 
        $solde_courant = new solde($dbcon);
        $error = $solde_precedent->load($agent->agentid(),"ann$annee_precedente");
        //echo "error = XXXX" . $error . "XXXX <br>";
        if ($error != "")
        {
            $solde_precedent->droitaquis(0);
        }
        $error = $solde_courant->load($agent->agentid(),"ann$annee_courante");
        //echo "error = YYYY" . $error . "YYYY <br>";
        if ( $error != "")
        {
            $solde2021->droitaquis(0);
        }
        
        //echo "Aquis 2020 = " . $solde2020->droitaquis() . "   Aquis 2021 = " . $solde2021->droitaquis() . "<br>";
        if (floatval($solde_precedent->droitaquis()) == 0 and floatval($solde_courant->droitaquis()) == 0)
        {
            continue;
        }
        
        $htmlstring = $htmlstring . "<tr class='element'>";
        $htmlstring = $htmlstring . "<td class='cellulesimple'>UP1" . str_pad($agent->agentid(),9,'0', STR_PAD_LEFT) . "</td><td class='cellulesimple'>" . $agent->identitecomplete() . "</td><td class='cellulesimple'>" . $solde_precedent->droitaquis()  ."</td>"; 
        $nbjrsconsommes = $agent->getNbJoursConsommés("20$annee_precedente", "20" . ($annee_precedente-1) . "0901", "20" . $annee_courante . "0831");
//        echo "nbjrsconsommes 2020 = $nbjrsconsommes <br>";
        $htmlstring = $htmlstring . "<td class='cellulesimple'>" . ($solde_precedent->droitaquis() - $nbjrsconsommes) . "</td>";
        $nbjrsconsommes = $agent->getNbJoursConsommés("20$annee_precedente", "20" . $annee_courante . "0901", "20" . $annee_courante . "1231");
//        echo "nbjrsconsommes 2020 (post 01/09) = $nbjrsconsommes <br>";
        $htmlstring = $htmlstring . "<td class='cellulesimple'>" . $nbjrsconsommes . "</td>";
        $htmlstring = $htmlstring . "<td class='cellulesimple'>" . $solde_courant->droitaquis() . "</td>";
        $nbjrsconsommes = $agent->getNbJoursConsommés("20$annee_courante", "20" . $annee_courante . "0101", "20" .$annee_courante . "1231");
//        echo "nbjrsconsommes 2021 = $nbjrsconsommes <br>";
        $htmlstring = $htmlstring . "<td class='cellulesimple'>" . $nbjrsconsommes . "</td>";
        $htmlstring = $htmlstring . "</tr>";
        unset($solde_precedent);
        unset($solde_courant);
        unset($agent);
        
        if (($count % 100)==0)  // Affichage toutes les 50 lignes
        {
            echo $htmlstring;
            ob_flush();
            flush();
            $htmlstring = '';
        }
    }
    $htmlstring = $htmlstring . "</table>";
    echo $htmlstring;
    echo "<br>";
    ob_flush();
    flush();
    ob_end_flush();
    
    
?>

</body>
</html>

