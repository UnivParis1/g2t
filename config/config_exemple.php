<?php

// URL d'accès aux WS G2T
define('G2T_WS_URL', "http://host_name:port/webservice_folder");
// URL d'accès à G2T
define('G2T_URL', 'http://host_name:port/appli_folder');
// Défini le type d'environnement :
//  Valeurs autorisées :
//      * prod => environnement de production
//      * n'importe quelle autre valeur pour les autres environnements (demo, test, recette, ...)
// G2T teste toujours par rapport à la valeur 'PROD'
define('TYPE_ENVIRONNEMENT', 'test');

//------------------------------------------------------
// Force les adresses mails de agents à la valeur saisie ==> Utilisation recommandée UNIQUEMENT sur environnements de TEST ou de DEVELOPPEMENT
// Pour désactiver le paramètre => Le mettre en commentaire ou initialiser la valeur à chaine vide (valeur par défaut)
define('FORCE_AGENT_MAIL', '');
//define('FORCE_AGENT_MAIL', 'noreply@etab.fr');  // ATTENTION : Tous les agents auront la même adresse mail

// Connexion à la base de données
define('DB_HOST', 'host_name:port');
define('DB_USER', 'db_user');
define('DB_PWD', 'db_password');
define('DB_NAME', 'g2t_db');

// Nom du fichier logo établissement (dans le dossier <racine>/images/etablissement)
define('LOGO_FILENAME', 'logo_exemple.png');

/*
 * -------------------------------------------------------------------------
 * Tableau de représentation des différentes animations inclues dans G2T
 * La valeur par défaut de la CSS est définie dans la clé DEFAULT_CSS
 * Une animation est définie par une clé libre (FIN_ANNEE, ESTIVAL, RENTREE, ....)
 * différente de DEFAULT_CSS et par la structure suivante :
 *      DEBUT : Date de début de l'affichage de l'annimation au format Jour/Mois.
 *      FIN : Date de fin de l'affichage de l'annimation au format Jour/Mois.
 *      SCRIPT : Structure représentant l'image utilisée dans le JS script à afficher pour l'occasion
 *          FICHIER : Nom du fichier de la bibliothèque d'images établissement de G2T à afficher => dossier <racine>/images/etablissement.
 *          HAUTEUR : Hauteur de l'image à afficher
 *          LARGEUR : Largeur de l'image à afficher
 *          PLEINECRAN : Affiche le script en plein écran ou pas (valeurs : O/N - défaut : N)
 *          NBELEMENTS : Nombre d'éléments (<=> fichiers) à afficher dans le script (défaut : 10)
 *          DELAI : Délai (en millisecondes) entre deux mouvemens des éléments (Si délai grand => vitesse faible - défaut : 10)
 *          DIRECTION : Direction du mouvement de l'animation (valeurs : H/V - défaut : V)
 *      IMAGE : Structure représentant l'image à afficher pour l'occasion
 *          FICHIER : Nom du fichier de la bibliothèque d'images établissement de G2T à afficher => dossier <racine>/images/etablissement.
 *          HAUTEUR : Hauteur de l'image à afficher
 *          LARGEUR : Largeur de l'image à afficher
 *      TEXTE : Structure représentant le texte à afficher pour l'occasion
 *          CHAINE : Chaine de caractères à afficher
 *          CSS_STRING : Mise en forme spécifique du texte au format CSS
 *
 * Remarques :
 *      * Tous les champs sont facultatifs. Ils peuvent être absents ou vide => Ils sont ignorés
 *      * Si l'un des champs DEBUT ou FIN est absent ou vide, l'animation ne sera pas activée (car pas de date d'activation)
 *      * Dans SCRIPT et IMAGE, si la largeur ou la hauteur de l'image est vide ou 0, la dimension réelle de l'image est utilisée
 *      * Si une chaine contient les caractères YYYY, ils sont remplacés par l'année courante
 *      * Si plusieurs SCRIPT sont actifs à la date du jour, seul le premier sera activé
 *      * Si un script n'est pas en plein écran, il est incrusté sur l'image. S'il n'y a pas d'image, le script est désactivé.
* ------------------------------------------------------------------------
 */
define('TAB_ANIMATION',array(
    'DEFAULT_CSS' => 'color:#00AA00;font-size:30px;font-family:Arial',
    'FIN_ANNEE' => array(
            'DEBUT' => '01/12',
            'FIN' => '15/01',
            'SCRIPT' => array(
                'FICHIER' => 'image_flocon_exemple.png',
                'HAUTEUR' => 0,
                'LARGEUR' => 0,
                'PLEINECRAN' => 'O',
                'NBELEMENTS' => 40,
                'DELAI' => 20,
                'DIRECTION' => 'V'
            ),
            'IMAGE' => array(
                'FICHIER' => 'deco_exemple.jpg',
                'HAUTEUR' => 0,
                'LARGEUR' => 0
            ),
            'TEXTE' => array(
                'CHAINE' => 'Bonnes fêtes',
                'CSS_STRING' => 'color:#ffb3ff;font-size:80px;font-family:Brush Script MT;transform: translate(0, -120px);'
            )
        ),
    'ESTIVAL' => array(
            'DEBUT' => '01/07',
            'FIN' => '31/08',
            'SCRIPT' => array(
                'FICHIER' => '',
                'HAUTEUR' => 0,
                'LARGEUR' => 0
            ),
            'IMAGE' => array(
                'FICHIER' => 'palmier_exemple.webp',
                'HAUTEUR' => 0,
                'LARGEUR' => 0
            )
        ),
    'RENTREE' => array(
            'DEBUT' => '01/09',
            'FIN' => '15/09',
            'IMAGE' => array(
                'FICHIER' => '',
                'HAUTEUR' => 0,
                'LARGEUR' => 0
            ),
            'TEXTE' => array(
                'CHAINE' => 'Bonne rentée YYYY',
                'CSS_STRING' => ''
            )
        )
    )
);

// Nom du fichier à joindre lors de l'utilisation du CET en congés (dans le dossier <racine>/documents)
define('DOC_USAGE_CET', 'Utilisation_CET_Conges.pdf');

// Nom du serveur SMTP
define('SMTPSERVER', 'smtp.etab.fr');

// Informations concernant les infos LDAP (serveurs, attributs, ...)
define('LDAPSERVER', 'ldap://ldap1.etab.fr ldap://ldap2.etab.fr ldap://ldap3.etab.fr');
define('LDAPLOGIN', 'cn=user_id,ou=admin,dc=etab,dc=fr');
define('LDAPPASSWD', 'ldap_password');
define('LDAPSEARCHBASE', 'ou=people,dc=etab,dc=fr');
define('LDAPATTRIBUTE', 'supannempid');
define('LDAP_AGENT_CIVILITE_ATTR', 'supanncivilite');
define('LDAP_AGENT_NOM_ATTR', 'sn');
define('LDAP_AGENT_PRENOM_ATTR', 'givenname');
define('LDAP_AGENT_MAIL_ATTR', 'mail');
define('LDAP_AGENT_ADDRESS_ATTR', 'postaladdress');
define('LDAP_AGENT_PERSO_ADDRESS_ATTR', 'homepostaladdress');
define('LDAP_AGENT_EPPN_ATTR', 'edupersonprincipalname');
define('LDAP_AGENT_UID_ATTR', 'uid');
define('LDAP_AGENT_RIFSEEP_ATTR', 'supannactivite');
define('LDAPMEMBERATTR', 'memberof');
define('LDAPGROUPNAME', 'cn=applications.g2t.users,ou=groups,dc=etab,dc=fr');
define('LDAP_STRUCT_SEARCH_BASE', 'ou=structures,dc=etab,dc=fr');
define('LDAP_STRUCT_CODE_ENTITE_ATTR', 'supanncodeentite');
define('LDAP_STRUCT_IS_INCLUDED_ATTR', 'up1flags');
define('LDAP_STRUCT_BUSINESSCATE_ATTR', 'businesscategory');
define('LDAP_FONCTION_SEARCH_BASE', 'ou=supannrolegenerique,ou=tables,dc=etab,dc=fr');
define('LDAP_FONCTION_POIDS_ATTR', 'up1flags');
define('LDAP_RIFSEEP_SEARCH_BASE','ou=supannActivite,ou=tables,dc=etab,dc=fr');
define('LDAP_RIFSEEP_NAME_ATTR','up1tablekey');
define('LDAP_RIFSEEP_LIBELLE_ATTR','displayname');
define('LDAP_GROUP_SEARCHBASE','ou=groups,dc=etab,dc=fr');
define('LDAP_GROUP_CN_ATTR','cn');
define('LDAP_ETAB_SEARCHBASE','dc=etab,dc=fr');

// Connexion au serveur CAS
define('CASSERVER', 'cas.etab.fr');
define('CASPATH', '/cas');

// URL d'accès au serveur WSGROUPS
define('WSGROUPURL', 'https://wsgroups.etab.fr/');
// Défini le token secret permettant de bypasser la propriété TRUSTED dans les appels : wsgroups/searchUserTrusted
define('WSGROUPS_SECRET_TOKEN', '');

// URL d'accès à l'agenda
define('URLCALENDAR', 'https://courrier-test.etab.fr/kronolith/lib/import-icals.php?');

// URL d'accès au serveur eSignature
define('ESIGNATUREURL', 'https://esignature-ppd.etab.fr');

// Adresse mail du collecteur GLPI pour la création d'un ticket suite validation de la convention télétravail
define('GLPI_COLLECTEUR', 'glpi-collecteur-ppd@etab.fr');

// Identifiant de la branche 'Bibliothèque et documentation'
// Permet de traiter les membres de cette branche comme des bibliothèques
// Les structures typées comme bibliothèques mais non inclues dans cette branche ne sont pas considérées des bibliothèques
// Cas d'un centre de documentation d'une UFR par exemple
// Laisser à chaine vide si pas de branche particulière pour les bibliothèques et les centres de documentation
define('BRANCHE_BIB','');

////////////////////////////////////////////////////////////////////////////
// ATTENTION : LES NOMS DES CONSTANTES DES UTILISATEURS SPECIAUX DOIVENT
// ----------  OBLIGATOIREMENT COMMENCER PAR 'SPECIAL_USER_' POUR ETRE
//             TRAITE COMME TEL DANS LA METHODE AGENT->estutilisateurspecial()
///////////////////////////////////////////////////////////////////////////
// Identifiant de l'utilisateur CRON de G2T
define('SPECIAL_USER_IDCRONUSER', '-1');
// Identifiant de l'utilisateur LISTE-RH / GESTION TEMPS
define('SPECIAL_USER_IDLISTERHUSER', '-2');
// Identifiant de l'utilisateur LISTE RH TELETRAVAIL
define('SPECIAL_USER_IDLISTERHTELETRAVAIL', '-3');

?>