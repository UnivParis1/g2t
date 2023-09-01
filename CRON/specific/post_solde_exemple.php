<?php

   /*************************************
   * Ce script permet de modifier le solde de conges des agents
   * dans l'application pour les cas non pris en charge (arret maladie, ....)
   **************************************/

   require_once(dirname(__FILE__,3) . "/html/includes/dbconnection.php");
   require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

   echo "Début du script spécifique post_solde " . date("d/m/Y H:i:s") . "\n" ;

   $fonctions = new fonctions($dbcon);

/*
   echo "Forcage du solde de Mme Jane Doe (Id Agent = ID_JANE_DOE) a 25 jours pour ann23 (annuel 2023/2024) - La mise à jour est forcée jusqu'au changement d'année universitaire (31/08/2024)";
   mysqli_query($dbcon, "UPDATE SOLDE SET DROITAQUIS = '25.00' WHERE AGENTID = 'ID_JANE_DOE' AND TYPEABSENCEID = 'ann23' AND DATE_FORMAT(CURDATE(),'%Y-%m-%e') <= '2024-08-31'");
   echo " => Nbre lignes modifiees : " . mysqli_affected_rows($dbcon) .  "\n";
   $erreur=mysqli_error($dbcon);
*/

   echo "Fin du script spécifique post_solde " . date("d/m/Y H:i:s") . "\n" ;

?>

