<?php
    // require_once ('CAS.php');
    include './includes/casconnection.php';
    require_once ('./includes/dbconnection.php');
    require_once ("./includes/all_g2t_classes.php");

    global $dbcon;
    global $uid;
    
    $contents = '';
    $minetype = '';

    if (isset($_GET['filename']) and isset($_GET['signature']))
    {
        $filename = $_GET['filename'];
        $signature = $_GET['signature'];

        if (!isset($_SESSION['g2t']['filesecret']))
        {
            header('Content-type: text/html; charset=utf-8');
            echo "Problème interne : Impossible de vérifier les paramètres de l'URL !";
            exit();  
        }
        $verifsignature = hash_hmac('sha256', $filename, $_SESSION['g2t']['filesecret']);

        if ($verifsignature !== $signature)
        {
            header('Content-type: text/html; charset=utf-8');
            echo "La signature du fichier ne correspond pas !";
            exit();
        }

        $fullfilename = $fonctions->justificatifpath() . '/' . $filename;
        if (!file_exists($fullfilename))
        {
            header('Content-type: text/html; charset=utf-8');
            echo "Le fichier n'existe pas/plus !";
            exit();
        }
        $minetype = mime_content_type($fullfilename);
        $handle = fopen($fullfilename, "r");
        $contents = fread($handle, filesize($fullfilename));
        fclose($handle);
        $contents = base64_encode($contents);
    }
    else
    {
        if (!isset($_POST['minetype']))
        {
            header('Content-type: text/html; charset=utf-8');
            echo "Type mine inconnu !";
            exit();
        }
        if (!isset($_POST['contents']))
        {
            header('Content-type: text/html; charset=utf-8');
            echo "Rien à afficher !";
            exit();
        }
        $contents = $_POST['contents'];
        $minetype = $_POST['minetype'];
    }

    if ($minetype == '' or $contents == '')
    {
            header('Content-type: text/html; charset=utf-8');
            echo "Problème technique lors de l'appel !";
            exit();
    }
    if (!in_array($minetype, ALLOWED_FILE_TYPES)) 
    {
        // Pas le bon type MINE
        header('Content-type: text/html; charset=utf-8');
        echo "Le fichier n'est pas dans un format autorisé !";
        exit();
    }
    
    // Si c'est un fichier PDF => On l'affiche dans une iframe
    if (stripos($minetype,'pdf') !== false)
    {
        header('Content-type: text/html; charset=utf-8');
        echo "<html>";
        echo "<head><title>Justificatif d'absence</title></head>";
        echo "<iframe width='100%' height='100%' src='data:$minetype;base64,$contents'></iframe>";
        echo "</html>";
    }
    // C'est une image => On l'affiche dans une balise html img
    else
    {
        header('Content-type: text/html; charset=utf-8');
        echo "<html>";
        echo "<head><title>Justificatif d'absence</title></head>";
        echo '<img src="data:' . $minetype . ';base64, ' . $contents . '">';
        echo '</html>';
    }

?>