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

    if (isset($_POST["agentid"])) {
        $agentid = $_POST["agentid"];
        if (! is_numeric($agentid)) 
        {
            $agentid = $fonctions->useridfromCAS($agentid);
            if ($agentid === false)
            {
                $agentid = null;
            }
        }
        if (! is_numeric($agentid)) 
        {
            $agentid = null;
            $agent = null;
        } 
        else 
        {
            $agent = new agent($dbcon);
            $agent->load($agentid);
        }
    } 
    else 
    {
        $agentid = null;
        $agent = null;
    }

    $anneeref = $fonctions->anneeref();
    if (isset($_POST["annee_ref"]))
    {
        $anneeref = $_POST["annee_ref"];
    }

    $msg_erreur = "";

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';
    echo "<br>";

    //print_r($_POST); echo "<br>";


    echo "Personne à rechercher : <br>";
    echo "<form name='selectagent'  method='post' >";
    
    $agentsliste = $fonctions->listeagentsg2t();
    echo "<select class='listeagentg2t' size='1' id='agentid' name='agentid'>";
    echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
    foreach ($agentsliste as $key => $identite)
    {
        echo "<option value='$key' ";
        if ($agentid == $key)
        {
            echo " selected ";
        }
        echo " >$identite</option>";
    }
    echo "</select>";
    
    echo "<input type='hidden' name='userid' value='" . $user->agentid() . "'>";
    echo "<br><br>";
    echo "Période d'affichage : ";
    echo "<select name='annee_ref' id='annee_ref'>";
    for ($annee=$fonctions->anneeref();$annee>=$fonctions->anneeref()-3;$annee--)
    {
        echo "<option value='$annee'";
        if ($annee==$anneeref)
            echo " selected ";
        echo ">Année " . $annee . "/" . ($annee+1) . "</option>";
    }
    echo "</select>";
    echo "<br><br>";
    echo "<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant' >";
    echo "</form>";

    if (!is_null($agent)) {
        echo "<br><br>Informations sur les congés de " . $agent->identitecomplete() . "<br>";
        echo $agent->soldecongeshtml($anneeref);
        echo $agent->demandeslistehtml($fonctions->formatdate($anneeref . $fonctions->debutperiode()), $fonctions->formatdate(($anneeref + 1) . $fonctions->finperiode()));
    }
    ?>

<!--
<a href=".">Retour à la page d'accueil</a>
-->
</body>
</html>