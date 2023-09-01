# Description
G2T est une application PHP de gestion de cong�s, du CET (alimentation et droit d'option) et de d�claration de t�l�travail.
Elle est d�velopp�e par l'Universit� Paris 1 Panth�on-Sorbonne.
Consultez le fichier LICENSE pour plus d'informations. 

# N�cessite
* PHP >=7.x
* Esup-Signature
* Composer

# Base de donn�es
* MySQL >=5.7.x

# Installation
* T�l�chargez la version de G2T souhait�e ([Releases G2T](https://github.com/UnivParis1/g2t/releases)).
* D�compressez le code source � partir du fichier .zip ou .tar.
* A partir de la racine de l'application, r�cup�rez les librairies PHPCas (>=v1.6.x) et fpdf (>=1.82) : `composer install` ou `composer update`
* Dans le dossier config, cr�ez un fichier config.php � partir du fichier config_exemple.php et personnalisez le.
* Dans le dossier images, d�posez le fichier du logo de votre �tablissement avec le nom d�fini dans le config.php
* Cr�ez les fichiers post_affectation.php, post_solde.php et post_structure.php � partir des fichiers exemples respectifs et adaptez les en fonction de votre besoin.
* G2T est pr�t � �tre utilis�.

