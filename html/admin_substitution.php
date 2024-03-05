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

    require ("includes/menu.php");
    // echo '<html><body class="bodyhtml">';

    // echo "POST = "; print_r($_POST); echo "<br>";

    echo "<br>";
    echo "<form name='subst_agent' method='post' action='index.php'>";

    $agentsliste = $fonctions->listeagentsg2t();
    echo "<select class='listeagentg2t' size='1' id='userid' name='userid'>";
    echo "<option value=''>----- Veuillez sélectionner un agent -----</option>";
    foreach ($agentsliste as $key => $identite)
    {
        echo "<option value='$key'>$identite</option>";
    }
    echo "</select>";
?>
	<br>
	<!--  <input type='text' name='userid' >
 -->
	<input type='submit' class='g2tbouton g2tsuivantbouton' value='Suivant'>
</form>

</body>
</html>

