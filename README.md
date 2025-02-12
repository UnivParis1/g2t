# Description
G2T est une application PHP de gestion de congés, du CET (alimentation et droit d'option) et du télétravail.
Elle utilise une base de données MySQL/MariaDB 
Elle est développée par l'Université Paris 1 Panthéon-Sorbonne.
Consultez le fichier LICENSE pour plus d'informations. 

# Nécessite
* PHP >= 8.2.x, >= 8.3.X - Ne fonctionne pas en PHP 8.4.X (librairie php-cas non compatible)
* Modules PHP : php-curl, php-mysqli, php-dom, php-ldap, php-mysql, php-pear, php-cli, php-curl, php-intl, php-zip, php-xml, php-dev
* pdftk pour l'alimentation des champs PDF
* Composer pour installer les extensions apereo/php-cas, fpdf
* Esup-Signature (https://www.esup-portail.org/wiki/display/SIGN)
* les services wsgroups (https://github.com/UnivParis1/wsgroups) - /searchUserCAS, /searchUserTrusted, /searchUser, /web-widget

# Base de données
* MySQL >= 5.7.x
* MariaDB >= 10.11.x

# Installation
* Téléchargez la version de G2T souhaitée ([Releases G2T](https://github.com/UnivParis1/g2t/releases)).
* Décompressez le code source à partir du fichier .zip ou .tar.
* A partir de la racine de l'application, récupérez les librairies PHPCas (>=v1.6.x) et fpdf (>=1.82) : `composer install` ou `composer update`
* Dans le dossier config, créez un fichier config.php à partir du fichier config_exemple.php et personnalisez le.
* Dans le dossier images, déposez le fichier du logo de votre établissement avec le nom défini dans le config.php
* Créez les fichiers post_affectation.php, post_solde.php et post_structure.php à partir des fichiers exemples respectifs et adaptez les en fonction de votre besoin.
* Sur le serveur MySQL, créez une base de données et alimentez la avec les fichiers d'interface
* G2T est prêt à être utilisé.

