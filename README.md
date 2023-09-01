# Description
G2T est une application PHP de gestion de congés, du CET (alimentation et droit d'option) et de déclaration de télétravail.
Elle est développée par l'Université Paris 1 Panthéon-Sorbonne.
Consultez le fichier LICENSE pour plus d'informations. 

# Nécessite
* PHP >=7.x
* Esup-Signature
* Composer

# Base de données
* MySQL >=5.7.x

# Installation
* Téléchargez la version de G2T souhaitée ([Releases G2T](https://github.com/UnivParis1/g2t/releases)).
* Décompressez le code source à partir du fichier .zip ou .tar.
* A partir de la racine de l'application, récupérez les librairies PHPCas (>=v1.6.x) et fpdf (>=1.82) : `composer install` ou `composer update`
* Dans le dossier config, créez un fichier config.php à partir du fichier config_exemple.php et personnalisez le.
* Dans le dossier images, déposez le fichier du logo de votre établissement avec le nom défini dans le config.php
* Créez les fichiers post_affectation.php, post_solde.php et post_structure.php à partir des fichiers exemples respectifs et adaptez les en fonction de votre besoin.
* G2T est prêt à être utilisé.

