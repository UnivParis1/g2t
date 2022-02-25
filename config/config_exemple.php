<?php

// URL d'accès aux WS G2T
define('G2T_WS_URL', "http://host_name:port/webservice_folder");
define('TYPE_ENVIRONNEMENT', 'test');   // test => environnement de test ou de développement   // prod => environnement de production

// Connexion à la base de données
define('DB_HOST', 'host_name:port');
define('DB_USER', 'db_user');
define('DB_PWD', 'db_password');
define('DB_NAME', 'g2t_db');

// Nom du fichier logo établissement (dans le dossier <racine>/images)
define('LOGO_FILENAME', 'logo_etab.png');

// Nom du fichier à joindre lors de l'utilisaton du CET en congés (dans le dossier <racine>/documents)
define('DOC_USAGE_CET', 'Utilisation_CET_Conges.pdf');

?>