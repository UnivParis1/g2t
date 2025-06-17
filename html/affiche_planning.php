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
//        header('Location: index.php');
        exit();
    }

    $user = new agent($dbcon);
    $user->load($userid);

    require ("includes/menu.php");

    addwaitingimgdiv();


    echo "<br>Planning de l'agent " . $user->civilite() . " " . $user->nom() . " " . $user->prenom() . " <br>";

    $datedebut = $fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode());
    if (strcasecmp((string)$fonctions->liredbconstante("LIMITE_CONGE_PERIODE"), "n") == 0) {
        $datefin = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("+1month", $timestamp)); // On passe au mois suivant
        $timestamp = strtotime($datefin);
        $datefin = date("Ymd", strtotime("-1days", $timestamp)); // On passe Ã  la veille
    } else {
        $datefin = $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode());
    }

    //echo $user->planninghtml($datedebut, $datefin,false,true,true);
    //var_dump ("$datedebut, $datefin");
    // Div qui sera mis à jour avec le retour HTML du WS
    echo "<div id='planningagent_" . $user->agentid() . "' class='divtocomplete'></div>";
    echo "<br>";
    echo "<br>";

?>
<script>
    var fullWSURL = "<?php echo $fonctions->get_g2t_ws_public_url() ?>/agentWS.php";
    $.post(fullWSURL , { methode : "<?php echo agent::WS_METHODE_PLANNING; ?>", 
                         agentid : "<?php echo $user->agentid(); ?>", 
                         datedebut : "<?php echo $fonctions->formatdatedb($datedebut); ?>" , 
                         datefin : "<?php echo $fonctions->formatdatedb($datefin); ?>",
                         clickable : 'N',
                         showpdflink : 'O',
                         includeteletravail : 'O'
                        })
                .done(function( data ) {
                    if (data.status.toUpperCase()=='OK')
                    {
                        var statutinfo = "OK";
                    }
                    else
                    {
                        var statutinfo = "KO => " + data.description;
                    }
                    // console.log("Retour du WS => " + statutinfo);
                    let div = document.getElementById('planningagent_<?php echo $user->agentid(); ?>');
                    div.innerHTML = data.html;
                })
                .fail(function( xhr ) {
                    var statutinfo = "Erreur WS - méthode : <?php echo agent::WS_METHODE_PLANNING; ?> - " + xhr.status + " " + xhr.statusText;
                    console.log(statutinfo);
                })
                .always(function() {
                    hiddewaitingimg();
                });

</script>


</body>
</html>