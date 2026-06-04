<?php

    // require_once ('CAS.php');
    //require_once('../vendor/autoload.php');

    include './includes/casconnection.php';
    require_once ("./includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
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
        
    // echo "Userid = " . $userid;
    if (is_null($userid) or ($userid == "")) {
        error_log(basename(__FILE__) . " : Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        echo "<script>alert('Votre session a expirée.\\nAucune donnée n\'est modifiée.\\nVous allez être redirigé vers l\'accueil de l\'application.'); window.location.replace('index.php');</script>";
//        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");

    // print_r($_POST);
    echo "<br>";
    echo "Cette page permet de réinjecter l'ICS lié à une demande dans l'agenda d'un agent.<br>";
    echo "<br>";
    $demandeid = '';

    if (isset($_POST['demandeid']) and trim(($_POST['demandeid'] . '')!=''))
    {
        $demandeid = $_POST['demandeid'];
        $demande = new demande($dbcon);
        $demande->load($demandeid);
        if (($demande->id() . '') != '')
        {
            // La demande est chargée
            $agent = $demande->agent();
            $ics = $demande->ics($agent->mail());

            if (!is_null($ics))
            {
                // Si le fichier ics existe ==> On met à jour le calendrier de l'agent

                // print_r($ics);
                echo "<br>";
                $errormsg = '';
                $errormsg = $agent->updatecalendar($ics);
                if ($errormsg != '')
                {
                    echo $fonctions->showmessage(fonctions::MSGERROR, "Erreur lors de la mise à jour de l'agenda pour l'agent " . $agent->identitecomplete() );
                }
                else
                {
                    echo $fonctions->showmessage(fonctions::MSGINFO, "La mise à jour de l'agenda s'est bien passée pour l'agent " . $agent->identitecomplete());
                }
            }
        }
        else
        {
            // La demande n'est pas chargée => Erreur
            echo $fonctions->showmessage(fonctions::MSGERROR, "Impossible de charger la demande $demandeid....");
        }
    }

?>
    <form name='resendics' id='resendics' method="POST">
        Numéro de la demande de congés :
        <input type="text" value='' name='demandeid' id='demandeid'/>
        <br>
        <input type='hidden' name='userid' id='userid' value='<?php echo $userid; ?>'>
        <br>
        <input type="submit">
        <br>
    </form>

</body>
</html>
