<?php

    // require_once ('CAS.php');
    //require_once('../vendor/autoload.php');

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
    }
    $user = new agent($dbcon);
    if (is_null($userid) or $userid == "")
    {
        $userid = $fonctions->useridfromCAS($uid);
        if ($userid === false)
        {
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<link rel="stylesheet" type="text/css" href="css-g2t/g2t.css?' . filemtime('css-g2t/g2t.css') .'" media="screen"></link>';
            echo '</head>';

            $errlog = "<body class='bodyhtml'>Vous n'êtes pas autorisé à vous connecter à cette application.";
            $errlog = $errlog . "<br>";
            $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";

            echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

            $techlog = "Informations techniques :";
            $techlog = $techlog . "<br><ul>";
            $techlog = $techlog . "<li>Identité de l'utilisateur : " . $uid . " (identifiant = " . $userid . ")</li>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($techlog)));

            $errlog = "<h3>Plusieurs raisons peuvent être à l'origine de cette limitation d'accès :";
            $errlog = $errlog . "<br><ul>";
            $errlog = $errlog . "<li>Vous êtes affecté à une structure qui n'est pas encore paramétrée pour utiliser G2T.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent BIATSS qui n'a pas/plus d'affectation fonctionnelle dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent contractuel dont le contrat n'est pas saisi ou renouvelé dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent hébergé et votre situation administrative n'est plus valide dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous n'êtes pas/plus personnel de Paris 1 Panthéon-Sorbonne.</li>";
            $errlog = $errlog . "</ul></h3><br>";
            $errlog = $errlog . "<hr>";
            echo $errlog;
            echo $techlog;
            echo "</body>";
            exit();
        }
        // Si on est là, on est sûr que l'agent existe
        $user->load($userid);
    }
    else
    {
        // Si le userid est défini => On essaie de charger l'agent
        if (! $user->load($userid))
        {
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<link rel="stylesheet" type="text/css" href="css-g2t/g2t.css?' . filemtime('css-g2t/g2t.css') .'" media="screen"></link>';
            echo '</head>';

            $errlog = "<body class='bodyhtml'>Vous n'êtes pas autorisé à vous connecter à cette application.";
            $errlog = $errlog . "<br>";
            $errlog = $errlog . "Veuillez vous rapprocher de votre gestionnaire RH ou de la DIRVAL";

            echo $fonctions->showmessage(fonctions::MSGERROR,$errlog);
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($errlog)));

            $techlog = "Informations techniques :";
            $techlog = $techlog . "<br><ul>";
            $techlog = $techlog . "<li>Identité de l'utilisateur : " . $uid . " (identifiant = " . $userid . ")</li>";
            error_log(basename(__FILE__) . " " . $fonctions->stripAccents(strip_tags($techlog)));

            $errlog = "<h3>Plusieurs raisons peuvent être à l'origine de cette limitation d'accès :";
            $errlog = $errlog . "<br><ul>";
            $errlog = $errlog . "<li>Vous êtes affecté à une structure qui n'est pas encore paramétrée pour utiliser G2T.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent BIATSS qui n'a pas/plus d'affectation fonctionnelle dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent contractuel dont le contrat n'est pas saisi ou renouvelé dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous êtes un agent hébergé et votre situation administrative n'est plus valide dans SIHAM.</li>";
            $errlog = $errlog . "<li>Vous n'êtes pas/plus personnel de Paris 1 Panthéon-Sorbonne.</li>";
            $errlog = $errlog . "</ul></h3><br>";
            $errlog = $errlog . "<hr>";
            echo $errlog;
            echo $techlog;
            echo "</body>";
            exit();

        }
    }

    require ("includes/menu.php");

    $casversion = phpCAS::getVersion();
    $errlog = "Index.php => Version de CAS.php utilisée  : " . $casversion;
    //echo "<br><br>" . $errlog . "<br><br>";
    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));

/*
    echo "POST => " . print_r($_POST,true) . "<br>";
*/

    $animationaffichee = false;
    // echo "Date du jour = " . date("d/m/Y") . "<br>";
    $affectationliste = $user->affectationliste(date("d/m/Y"), date("d/m/Y"));

    echo "<br>Bonjour " . $user->identitecomplete() . " : <br>";
    if (! is_null($affectationliste)) {
        $affectation = reset($affectationliste);
        // $affectation = $affectationliste[0];
        $structure = new structure($dbcon);
        $structure->load($affectation->structureid());
        echo $structure->nomlong();
        
        // On affiche les animations si elles sont définies et si elles sont dans la période d'activation
//        if (defined('TAB_ANIMATION') and stripos($structure->id(),'DGH')===0)  // Ne fonctionne que pour la DSIUN et ses sous-structures dont le code commence par DGH
        if (defined('TAB_ANIMATION'))
        {
            $currentyear = date('Y');
            $currentdate = date('md');
            $script_actif = false;
            $scriptfilename = '';
            $scripthauteur = '';
            $scriptlargeur = '';
            $script_cleanimation = '';
            $pleinecran = false;
            $direction = 'V';
            $nbimages = 10;
            $delai = 10; // 10ms par défaut
            
            $defaultcssstring = '';
            if (isset(TAB_ANIMATION['DEFAULT_CSS']))
            {
                $defaultcssstring = trim(TAB_ANIMATION['DEFAULT_CSS']);
            }
            
            foreach (TAB_ANIMATION as $nom_animation => $infos_animation)
            {
                $imgfilename = '';
                $animationtext = '';
                $debutanim = '';
                $finanim = '';
                $imghauteur = '';
                $imglargeur = '';
                
                $cssstring = $defaultcssstring;
                if (isset($infos_animation['DEBUT']) and isset($infos_animation['FIN']))
                {
                    $debutanim = trim($infos_animation['DEBUT']);
                    $finanim = trim($infos_animation['FIN']);
                }
                if ($debutanim != '' and $finanim != '')
                {
                    $fulldatedebut = $debutanim . "/$currentyear";
                    $fulldatefin = $finanim . "/$currentyear";
                    if ($fonctions->verifiedate($fulldatedebut) and $fonctions->verifiedate($fulldatefin))
                    {
                        $debutanim = substr($fonctions->formatdatedb($fulldatedebut),-4); // On récupère la date au format MMDD
                        $finanim = substr($fonctions->formatdatedb($fulldatefin),-4); // On récupère la date au format MMDD
                        if (($currentdate >= $debutanim and $currentdate <= $finanim) or 
                           ($debutanim > $finanim and ($currentdate >= $debutanim or $currentdate <= $finanim)))
                        {
                            if (isset($infos_animation['SCRIPT']) and $script_actif===false)
                            {
                                $scriptinfos = $infos_animation['SCRIPT'];
                                if (isset($scriptinfos['FICHIER']))
                                {
                                    $scriptfilename = trim($scriptinfos['FICHIER']);
                                    $path = $fonctions->etablissementimagepath() . "/" . $scriptfilename;
                                    if ($scriptfilename != '' and file_exists($path))
                                    {
                                        $script_actif=true;
                                        $script_cleanimation = $nom_animation;
                                    }
                                }
                                if (isset($scriptinfos['HAUTEUR']))
                                {
                                    $scripthauteur = trim($scriptinfos['HAUTEUR']);
                                }
                                if (isset($scriptinfos['LARGEUR']))
                                {
                                    $scriptlargeur = trim($scriptinfos['LARGEUR']);
                                }
                                if (isset($scriptinfos['PLEINECRAN']) and strcasecmp(trim($scriptinfos['PLEINECRAN']),'O')==0)
                                {
                                    $pleinecran = true;
                                }
                                if (isset($scriptinfos['NBELEMENTS']) and trim($scriptinfos['NBELEMENTS'])!='')
                                {
                                    $nbimages = intval(trim($scriptinfos['NBELEMENTS']));
                                }
                                if (isset($scriptinfos['DELAI']) and trim($scriptinfos['DELAI'])!='')
                                {
                                    $delai = intval(trim($scriptinfos['DELAI']));
                                }
                                if (isset($scriptinfos['DIRECTION']) and trim($scriptinfos['DIRECTION'])!='')
                                {
                                    $direction = strtoupper(trim($scriptinfos['DIRECTION']));
                                    if (!in_array($direction, array('V','H')))
                                    {
                                        $direction = 'V';
                                    }
                                }
                            }
                            if (isset($infos_animation['IMAGE']))
                            {
                                $imginfos = $infos_animation['IMAGE'];
                                if (isset($imginfos['FICHIER']))
                                {
                                    $imgfilename = trim($imginfos['FICHIER']);
                                }
                                if (isset($imginfos['HAUTEUR']))
                                {
                                    $imghauteur = trim($imginfos['HAUTEUR']);
                                }
                                if (isset($imginfos['LARGEUR']))
                                {
                                    $imglargeur = trim($imginfos['LARGEUR']);
                                }
                            }
                            if (isset($infos_animation['TEXTE']))
                            {
                                $textinfos = $infos_animation['TEXTE'];
                                if (isset($textinfos['CHAINE']))
                                {
                                    $animationtext = str_replace('YYYY',$currentyear,trim($textinfos['CHAINE']));                                
                                }
                                if (isset($textinfos['CSS_STRING']) and trim($textinfos['CSS_STRING'])!= '')
                                {
                                    $cssstring = trim($textinfos['CSS_STRING']);                                
                                }
                            }
                        }
                    }
                }
                if ($imgfilename!= '' or $animationtext != '')
                {
                    $animationaffichee = true;
                    echo "<div id='$nom_animation' class='centeraligntext animation' title='Double-clic pour masquer les animations'>";
                    if ($imgfilename!= '')
                    {
                        $path = $fonctions->etablissementimagepath() . "/" . $imgfilename;
                        if (file_exists($path))
                        {
                            if (intval($imghauteur) != 0)
                            {
                                $imghauteur = "height='$imghauteur'";
                            }
                            else
                            {
                                $imghauteur = '';
                            }
                            if (intval($imglargeur) != 0)
                            {
                                $imglargeur = "width='$imglargeur'";
                            }
                            else
                            {
                                $imglargeur = '';
                            }
                            
                            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
                            $data = file_get_contents($path);
                            $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);
                            echo "<img id='img_$nom_animation' src='" . $base64 . "' $imghauteur $imglargeur >"; 
                        }
                    }
                    if ($animationtext != '')
                    {
                        echo "<div id='text_$nom_animation' class='centeraligntext' style='$cssstring'>$animationtext</div>";
                    }
                    echo "</div>";
                }
            }
        }
    } 
    else
    {
        echo "Pas d'affectation actuellement => Pas de structure";
    }

    // $tempstructid = $user->structure()->id();
    if (!$animationaffichee)
    {
        echo "<br><br>";
    }


    $affectationliste = $user->affectationliste($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()),$fonctions->formatdate(($fonctions->anneeref()+1) . $fonctions->finperiode()));

    // L'agent a-t-il des affectation ?
    if (count((array)$affectationliste) >0)
    {
        // Pour chaque affectation
        foreach ((array) $affectationliste as $affectation)
        {
            // Si c'est un temps partiel, on verifie que le temps partiel est bien saisi et validé
            if ($affectation->quotitevaleur() < 1)
            {
                $datedebut = "29991231";  // La date de début est dans le futur
                $datefin = "19000101";    // La date de fin est dans le passé
                if ($fonctions->formatdatedb($affectation->datedebut()) < $datedebut)
                {
                    $datedebut = $fonctions->formatdatedb($affectation->datedebut());
                }
                if ($datefin < $fonctions->formatdatedb($affectation->datefin()))
                {
                    $datefin = $fonctions->formatdatedb($affectation->datefin());
                }
                if ($datedebut < $fonctions->anneeref() . $fonctions->debutperiode())
                {
                    $datedebut = $fonctions->anneeref() . $fonctions->debutperiode();
                }
                if ($datefin > ($fonctions->anneeref()+1) . $fonctions->finperiode())
                {
                    $datefin = ($fonctions->anneeref()+1) . $fonctions->finperiode();
                }

                //echo "datedebut = $datedebut    datefin = $datefin <br>";
                // On verifie que sur l'affectation en cours, il n'y a pas de période non déclaré.
                if (!$user->dossiercomplet($datedebut,$datefin))
                {
                    $msgerror = "";
                    $msgerror = $msgerror . "Il existe au moins une affection à temps partiel pour laquelle vous n'avez pas de déclaration validée.<br>";
                    $msgerror = $msgerror . "Vos déclarations de temps partiel doivent obligatoirement être validées afin de pouvoir poser des congés durant la  période correspondante.<br>";
                    $msgerror = $msgerror . "Votre planning contiendra donc des cases \"Période non déclarée\" lors de son affichage.<br>";
                    echo $fonctions->showmessage(fonctions::MSGWARNING, $msgerror);
                }
            }
        }
    }

    $fonctions->afficheperiodesobligatoires();
    echo $user->soldecongeshtml($fonctions->anneeref());

    echo $user->affichecommentairecongehtml();
    echo $user->demandeslistehtml($fonctions->formatdate($fonctions->anneeref() . $fonctions->debutperiode()), $fonctions->formatdate(($fonctions->anneeref() + 1) . $fonctions->finperiode()));


?>
<script>
    window.onload = (event) => {

        var animationliste = document.getElementsByClassName('animation');
        if (animationliste.length > 0 )
        {
            var body = document.getElementsByTagName('body')[0];
            var menu = document.getElementById('mainmenu');
            var rect = menu.getBoundingClientRect();
            var animbuttonoff = document.createElement("button");
            animbuttonoff.setAttribute("id","animbuttonoff");
            animbuttonoff.setAttribute("class","g2tbouton g2tboutonwidthauto");
            animbuttonoff.style.padding = "8px 8px 8px 8px"; 
            animbuttonoff.style.position = "absolute";
            animbuttonoff.style.top = rect.top + "px";
            // animbuttonoff.style.left = (body.offsetWidth -100) + "px";
            animbuttonoff.style.right = "20px"; 
            animbuttonoff.textContent = 'Désactiver les animations';
            animbuttonoff.addEventListener('click', masqueanimation);
            body.appendChild(animbuttonoff);    
        }
        
        /*
        * Script pour définir la hauteur de l'animation en fonction du placement des différents éléments de celle-ci
        */
        var animationliste = document.getElementsByClassName('animation');
        for (const animation of animationliste) 
        {

            // Pour chaque animation, si on double-clic dessus, on la cache et on désactive le script s'il est actif
            animation.addEventListener("dblclick", masqueanimation);

            var rect = animation.getBoundingClientRect();
            var animationtop = Math.round(rect.top);
            // Le point le plus haut c'est le top de l'animation
            var toppixelmin = animationtop;
            // Le point le plus bas c'est le top de l'animation car on se connait pas la position du buttom le plus bas des éléments composants l'animation
            var bottompixelmax = animationtop;
            //console.log ('animation.id = ' + animation.id + '   animation.top = ' + Math.round(rect.top) + ' animation.bottom = ' + Math.round(rect.bottom));

            var elementsliste = animation.children;
            for (const element of elementsliste) 
            {
                var rect = element.getBoundingClientRect();
                var elementtop = Math.round(rect.top);
                var elementbuttom = Math.round(rect.bottom);
                //console.log ('element.id = ' + element.id + '   rect.top = ' + elementtop + ' rect.bottom = ' + elementbuttom);
                //console.log ('toppixelmin = ' + toppixelmin  +'  bottompixelmax = ' + bottompixelmax );

                // On ne peut pas aller plus haut que le haut de l'animation 
                if (elementtop < animationtop)
                {
                    toppixelmin = animationtop;
                }
                else if (toppixelmin > elementtop)
                {
                    toppixelmin = elementtop;
                }
                if (bottompixelmax < elementbuttom)
                {
                    bottompixelmax = elementbuttom;
                }
                //console.log ('Le haut le plus haut (toppixelmin) est : ' + toppixelmin);
                //console.log ('Le bas le plus bas (bottompixelmax) est : ' + bottompixelmax);
            }
            // On calcule la hauteur de l'animation => différence entre le point le plus bas et le point le plus haut
            var animationheight = bottompixelmax - toppixelmin;
            //console.log ('La hauteur est : ' + animationheight);
            // En théorie, cette valeur ne devrait jamais être négative, mais par sécurité...
            if (animationheight >= 0)
            {
                // On ajoute une marge de 5px à la hauteur de l'animation
                animation.setAttribute("style","height:" + (animationheight+5) + "px");
            }
        }

        function masqueanimation()
        {
            for (const anim of animationliste) 
            {
                // console.log ("desactivation de " + anim.id);
                anim.style.display='none';
            }
            if ( typeof(no) !== 'undefined' )
            {
                for (i = 0; i < no; i++) 
                {
                    if (document.getElementById("dot"+i))
                    {
                        document.getElementById("dot"+i).style.display = 'none';
                    }
                }
            }
            document.getElementById("animbuttonoff").style.display = 'none';
        }
        
<?php
        if ($script_actif)
        {
            $paraheightstring = '';
            $parawidthstring = '';
            $path =  $fonctions->etablissementimagepath() . "/$scriptfilename"; 
            list($filelargeur, $filehauteur) = getimagesize("$path");

            if (intval($scripthauteur) != 0)
            {
                $paraheightstring = "para.setAttribute('height','$scripthauteur');";
            }
            else
            {
                $scripthauteur = $filehauteur;
            }
            if (intval($scriptlargeur) != 0)
            {
                $parawidthstring = "para.setAttribute('width','$scriptlargeur');";
            }
            else
            {
                $scriptlargeur = $filelargeur;
            }
            $imagelargeur = $scriptlargeur;
            $imagehauteur = $scripthauteur;
            $typeimage = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $typeimage . ';base64,' . base64_encode($data);

?>
            /*
            * Script pour afficher des flocons qui tombent sur l'écran
            */
            var no = <?php echo "$nbimages"; ?>;
            var delai = <?php echo "$delai"; ?>;
            var dx = new Array(); 
            var xp = new Array();
            var yp = new Array();
            var am = new Array();
            var stx = new Array();
            var sty = new Array();
            var i;

<?php            
            if ($pleinecran===false)
            {
?>
                //var animation = document.getElementById('<?php echo "$script_cleanimation"; ?>');
                var animation = document.getElementById('img_<?php echo "$script_cleanimation"; ?>');
                //if (!animation)
                //{
                //    var animation = document.getElementById('<?php echo "$script_cleanimation"; ?>');
                //}
                if (animation)
                {
                    var rect = animation.getBoundingClientRect();
                    var width_fenetre = rect.width;
                    var height_fenetre = rect.height;
                    var left_fenetre = animation.offsetLeft;
                    // console.log ("left_fenetre = " + left_fenetre);
                    var top_fenetre = animation.offsetTop;
                    // console.log ("top_fenetre = " + top_fenetre);
                }
                else // S'il n'y a pas d'image, il n'y a pas d'objets à afficher => Désactivation du script
                {
                    no = 0;
                }
<?php
            }
            else
            {
?>
                var width_fenetre = ((document.body.offsetWidth<window.innerWidth)? window.innerWidth:document.body.offsetWidth)-20;
                var height_fenetre = ((document.body.offsetHeight<window.innerHeight)? window.innerHeight:document.body.offsetHeight)-100;
                var left_fenetre = 0; 
                // console.log ("left_fenetre = " + left_fenetre);
                var top_fenetre = 0; 
                // console.log ("top_fenetre = " + top_fenetre);
<?php
            }
?>
            for (i = 0; i < no; i++) { 
                dx[i] = 0;
                xp[i] = Math.random()*(width_fenetre-<?php echo $imagelargeur; ?>) + left_fenetre;
                yp[i] = Math.random()*(height_fenetre-<?php echo $imagehauteur; ?>) + top_fenetre;
                am[i] = Math.random()*20;
                stx[i] = 0.02 + Math.random()/10;
                sty[i] = 0.7 + Math.random();

                obj = document.getElementsByTagName('body')[0];
                para = document.createElement("img");
<?php
                if (trim($paraheightstring)!='')
                {
                    echo "$paraheightstring \n";
                }
                if (trim($parawidthstring)!='')
                {
                    echo "$parawidthstring \n";
                }
?>
                para.setAttribute("src","<?php echo $base64;?>");
                para.setAttribute("id","dot" + i);
                para.style.position = "absolute";
                para.style.zIndex = "2";
                obj.appendChild(para);
            }

            function start_animation() 
            {
                for (i = 0; i < no; i++) {
                    dx[i] += stx[i];
                    yp[i] += sty[i];
                    if (yp[i] > (top_fenetre+height_fenetre-<?php echo $imagehauteur; ?>)) 
                    {
                        // console.log ("L'image " + i + " est en bas de la zone => On la remonte");
                        xp[i] = Math.random()*(width_fenetre-am[i]-<?php echo $imagelargeur; ?>) + left_fenetre;
                        yp[i] = top_fenetre;
                    }
                    document.getElementById("dot"+i).style.top = yp[i] + "px";
                    document.getElementById("dot"+i).style.left = xp[i] + am[i]*Math.sin(dx[i]) + "px";
                }
            }

            function start_animation_horizontale() 
            {
                for (i = 0; i < no; i++) 
                {
                    xp[i] += 5;
                    if (xp[i] > (left_fenetre+width_fenetre-<?php echo $imagelargeur; ?>)) 
                    {
                        xp[i] = left_fenetre;
                        yp[i] = Math.random()*(height_fenetre-am[i]-<?php echo $imagehauteur; ?>) + top_fenetre;
                    }
                    
                    // document.getElementById("dot"+i).style.top = yp[i] + "px"; // + am[i]*Math.sin(dx[i]) +
                    document.getElementById("dot"+i).style.top = yp[i] + am[i] * Math.sin(10 * (xp[i] - left_fenetre) + (3,14 / 4)) + "px";
                    document.getElementById("dot"+i).style.left = xp[i] + "px";
                }
            }

            if (no > 0)
            {
<?php
                if ($direction == 'V')
                {
                    echo "setInterval(start_animation, delai); \n";
                }
                else
                {
                    echo "setInterval(start_animation_horizontale, delai); \n";
                }
 ?>
           }

<?php
        }
?>
    };
</script>
</body>
</html>
