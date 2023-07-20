<?php

// URL d'accès aux WS G2T
define('G2T_WS_URL', "http://host_name:port/webservice_folder");
// URL d'accès à G2T
define('G2T_URL', 'http://host_name:port/appli_folder');
define('TYPE_ENVIRONNEMENT', 'test');   // test => environnement de test ou de développement   // prod => environnement de production

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

// Nom du fichier logo établissement (dans le dossier <racine>/images)
define('LOGO_FILENAME', 'logo_etab.png');

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

// URL d'accès à l'agenda
define('URLCALENDAR', 'https://courrier-test.etab.fr/kronolith/lib/import-icals.php?');

// URL d'accès au serveur eSignature
define('ESIGNATUREURL', 'https://esignature-ppd.etab.fr');

// Adresse mail du collecteur GLPI pour la création d'un ticket suite validation de la convention télétravail
define('GLPI_COLLECTEUR', 'glpi-collecteur-ppd@univ-paris1.fr');

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