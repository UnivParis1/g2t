<?php

//include_once 'mysql_compat.php';
require_once (dirname(__FILE__,3) . "/config/config.php");


// Connexion à la base de données
//$dbcon = mysqli_connect($db_host, $db_user, $db_pwd);
$dbcon = mysqli_connect(DB_HOST, DB_USER, DB_PWD);
if (! $dbcon) {
    //echo "Impossible d'effectuer la connexion au serveur";
?>
    <div class="WordSection1">
    <p><img src="https://ent-data.univ-paris1.fr/esup/canal/maintenance/maintenance.gif" alt="Maintenance" v:shapes="_x0000_s1026" width="144" hspace="12" height="79" align="left">
    <span style="font-family:'Calibri','sans-serif';mso-ascii-theme-font:minor-latin;mso-hansi-theme-font:minor-latin">L'application des gestion des congés est momentanément indisponible.<br>
En cas d'urgence, merci de contacter le 01 44 07 89 65 ou assistance-dsiun@univ-paris1.fr<br>
Veuillez nous excuser pour la gene occasionnee.</span></p> <p style:="" '="">
<span style="font-family:'Calibri','sans-serif';mso-ascii-theme-font:
minor-latin;mso-hansi-theme-font:minor-latin">DSIUN Universite Paris 1
Pantheon-Sorbonne</span></p>
</div>
<?php
    exit();
}

//mysqli_query($dbcon, "SET sql_mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'");
//mysqli_select_db($dbcon, $db_name) or die("La sélection de la base a échoué");
mysqli_select_db($dbcon, DB_NAME) or die("La sélection de la base a échoué");
mysqli_query($dbcon, "set names utf8;");

$sql = "SELECT @@SESSION.sql_mode session";
$query = mysqli_query($dbcon, $sql);
$erreur = mysqli_error($dbcon);
if ($erreur != "")
{
    $errlog = "Erreur lors du chargement du sql_mode de session : " . $erreur;
    echo $errlog . "<br/>";
    error_log(basename(__FILE__) . " " . $errlog);
    mysqli_query($dbcon, "SET sql_mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'");
}
if (mysqli_num_rows($query) == 0)
{
    $errlog = "Impossible de charger le sql_mode de session : " . $erreur;
    echo $errlog . "<br/>";
    error_log(basename(__FILE__) . " " . $errlog);
    mysqli_query($dbcon, "SET sql_mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'");
}
else
{
    $result = mysqli_fetch_row($query);
    $sql_mode = $result[0];
    $sql_mode= str_replace('STRICT_TRANS_TABLES', '', $sql_mode);
    mysqli_query($dbcon, "SET sql_mode='$sql_mode'");
    //echo "Le sql_mode est : $sql_mode <br><br>";
}

?>