<?php

   /*************************************
   * Ce script permet de modifier l'affectation d'un agent si les données issues du référentiel (SIHAM)
   * sont incorrectes ou dans des cas specifiques
   **************************************/

   require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
   require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

   echo "Début du script spécifique post_affectation " . date("d/m/Y H:i:s") . "\n";

   $fonctions = new fonctions($dbcon);

/*
   echo "Forcage de l'affectation de M. JONH DOE (Id Agent = ID_JONH_DOE) à la structure Nom_De_La_Structure_XXXX (Id Structure = XXXXX)";
   mysqli_query($dbcon, "UPDATE AGENT SET STRUCTUREID = 'XXXXX' WHERE AGENTID = 'ID_JONH_DOE'");
   echo " => Nbre lignes modifiees : " . mysqli_affected_rows($dbcon) . "\n";
   $erreur=mysqli_error($dbcon);
*/

   echo "Fin du script spécifique post_affectation " . date("d/m/Y H:i:s") . "\n";

?>

