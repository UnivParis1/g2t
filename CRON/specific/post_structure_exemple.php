<?PHP

   /*************************************
   * Ce script permet de modifier les responsables de structures si les données issues du référentiel (SIHAM)
   * sont incorrectes ou dans des cas specifiques
   **************************************/

   require_once(dirname(__FILE__,3) . "/html/includes/dbconnection.php");
   require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

   echo "Début du script spécifique post_structure " . date("d/m/Y H:i:s") . "\n" ;

   $fonctions = new fonctions($dbcon);

/*
   $hostname = mysqli_fetch_row(mysqli_query($dbcon, "select @@hostname"))[0];
   echo "Nom du serveur de BD = $hostname \n";
   $dbname = mysqli_fetch_row(mysqli_query($dbcon, "SELECT DATABASE()"))[0];
   echo "DBName = $dbname \n";
*/
/*
   echo "Forcage du responsable de structure Nom_De_La_Structure_XXXX (Id Structure = XXXXX) a M. AAAAAAA (Id Agent = ID_AAA) si le responsable défini dans SIHAM est MME ZZZZZZ (Id Agent = ID_ZZZ)";
   mysqli_query($dbcon, "UPDATE STRUCTURE SET RESPONSABLEID = 'ID_AAA' WHERE STRUCTUREID = 'XXXXX' AND RESPONSABLEID = 'ID_ZZZ'");
   echo " => Nbre lignes modifiees : " . mysqli_affected_rows($dbcon) .  "\n";
   $erreur=mysqli_error($dbcon);
*/
/*
   echo "Forcage du responsable de structure Nom_De_La_Structure_YYYY (Id Structure = YYYYY) a M. BBBBBBB (Id Agent = ID_BBB) si le responsable est CRON G2T (Id = " . constant('SPECIAL_USER_IDCRONUSER') . ")";
   mysqli_query($dbcon, "UPDATE STRUCTURE SET RESPONSABLEID = 'ID_BBB' WHERE STRUCTUREID = 'YYYYY' AND RESPONSABLEID = '" . constant('SPECIAL_USER_IDCRONUSER') . "'");
   echo " => Nbre lignes modifiees : " . mysqli_affected_rows($dbcon) .  "\n";
   $erreur=mysqli_error($dbcon);
*/

   echo "Fin du script spécifique post_structure " . date("d/m/Y H:i:s") . "\n" ;

?>
