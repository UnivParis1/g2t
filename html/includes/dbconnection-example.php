<?php

	// Connexion à la base de données
	$db_host='localhost';
	$db_user='pacomte';
	$db_pwd='xxx';
	$dbcon = mysql_connect($db_host,$db_user,$db_pwd);
	if (!$dbcon)
	{
		echo "Impossible d'effectuer la connexion au serveur";
		exit;
	}
	mysql_select_db ("G2T-v3",$dbcon) or die ("La sélection de la base a échoué");


?>