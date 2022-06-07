<?php
    require_once ('CAS.php');
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
//        header('Location: index.php');
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

    if (isset($_POST["maintenance"])) {
        if ($_POST["maintenance"] == "on") {
            // Update Mode maintenance à 'o'
            $sql = "UPDATE CONSTANTES SET VALEUR = 'o' WHERE NOM = 'MAINTENANCE'";
        } else {
            // Update Mode maintenance à 'n'
            $sql = "UPDATE CONSTANTES SET VALEUR = 'n' WHERE NOM = 'MAINTENANCE'";
        }
        $query = mysqli_query($dbcon, $sql);
        $erreur = mysqli_error($dbcon);
        if ($erreur != "") {
            $errlog = "Erreur activation/desactivation mode maintenance : " . $erreur;
            echo $fonctions->showmessage(fonctions::MSGERROR, $errlog);
//            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
        }
    }

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';

    // echo "POST = "; print_r($_POST); echo "<br>";
    $etat = $fonctions->liredbconstante("MAINTENANCE");
    

?>

<br>
Gestion du mode maintenance...
<br>
<br>
<form name='maintenance_mode' method='post'>
    <input type='hidden' name='userid' value='<?php echo $user->agentid(); ?>'>
    <INPUT type="radio" name="maintenance" value="on" <?php if (strcasecmp($etat,'n')==0) echo 'checked ' ?>> Activer le mode maintenance <br> 
    <INPUT type="radio" name="maintenance" value="off" <?php if (strcasecmp($etat,'o')==0) echo 'checked ' ?>> Désactiver le mode maintenance <br> 
    <br> 
    <input type='submit' value='Soumettre'>
</form>

</body>
</html>
