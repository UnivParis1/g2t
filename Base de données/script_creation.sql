DROP TABLE AFFECTATION;
DROP TABLE AGENT;
DROP TABLE COMPLEMENT;
DROP TABLE DECLARATIONTP;
DROP TABLE DEMANDE;
DROP TABLE DEMANDEDECLARATIONTP;
DROP TABLE SOLDE;
DROP TABLE STRUCTURE;
DROP TABLE TYPEABSENCE;
DROP TABLE HARPABSENCE;
DROP TABLE CONSTANTES;
DROP TABLE COMMENTAIRECONGE;

CREATE  TABLE AGENT (
  HARPEGEID VARCHAR(10) NOT NULL ,
  CIVILITE VARCHAR(20) NOT NULL ,
  NOM VARCHAR(50) NOT NULL ,
  PRENOM VARCHAR(50) NOT NULL ,
  ADRESSEMAIL VARCHAR(50) NOT NULL ,
  TYPEPOPULATION VARCHAR(5) NULL DEFAULT '',
  PRIMARY KEY (HARPEGEID) );

CREATE  TABLE COMPLEMENT (
  HARPEGEID VARCHAR(10) NOT NULL ,
  COMPLEMENTID VARCHAR(20) NOT NULL ,
  VALEUR VARCHAR(50) NOT NULL ,
  STATUT VARCHAR(1) NOT NULL DEFAULT '',
  DATEDEBUT DATE NOT NULL DEFAULT '0000-00-00' ,
  DATEFIN DATE NULL ,
  PRIMARY KEY (HARPEGEID,COMPLEMENTID) );

CREATE  TABLE SOLDE (
  HARPEGEID VARCHAR(10) NOT NULL ,
  TYPEABSENCEID VARCHAR(10) NOT NULL ,
  DROITAQUIS DECIMAL(5,2) NOT NULL ,
  DROITPRIS DECIMAL(5,2) NOT NULL ,
  PRIMARY KEY (HARPEGEID,TYPEABSENCEID) );

CREATE  TABLE TYPEABSENCE (
  TYPEABSENCEID VARCHAR(10) NOT NULL ,
  LIBELLE VARCHAR(100) NOT NULL ,
  ANNEEREF VARCHAR(5) NULL ,
  COULEUR VARCHAR(10) NOT NULL ,
  ABSENCEIDPARENT VARCHAR(10) NULL DEFAULT '',
  PRIMARY KEY (TYPEABSENCEID) );

CREATE  TABLE STRUCTURE (
  STRUCTUREID VARCHAR(10) NOT NULL ,
  NOMLONG VARCHAR(50) NOT NULL ,
  NOMCOURT VARCHAR(20) NOT NULL ,
  STRUCTUREIDPARENT VARCHAR(10) NULL DEFAULT '',
  RESPONSABLEID VARCHAR(10) NULL DEFAULT '' ,
  GESTIONNAIREID VARCHAR(10) NULL DEFAULT '',
  AFFICHESOUSSTRUCT VARCHAR(1) NULL DEFAULT 'N',
  AFFICHEPLANNINGTOUTAGENT VARCHAR(1) NULL DEFAULT 'N',
  PRIMARY KEY (STRUCTUREID) );

CREATE  TABLE AFFECTATION (
  AFFECTATIONID VARCHAR(30) BINARY NOT NULL COMMENT 'Cette colonne contient le rowid Oracle' ,
  HARPEGEID VARCHAR(10) NOT NULL ,
  NUMCONTRAT INT NULL DEFAULT 0, 
  DATEDEBUT DATE NOT NULL ,
  DATEFIN DATE NULL DEFAULT '9999-12-31',
  DATEMODIFICATION DATE NULL DEFAULT '0000-00-00',
  STRUCTUREID VARCHAR(10) NOT NULL ,
  NUMQUOTITE INT NULL DEFAULT 0,
  DENOMQUOTITE INT NULL DEFAULT 100,
  OBSOLETE VARCHAR(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (AFFECTATIONID) );

CREATE  TABLE DECLARATIONTP (
  DECLARATIONID INT NOT NULL AUTO_INCREMENT ,
  AFFECTATIONID VARCHAR(30) BINARY NOT NULL COMMENT 'Cette colonne contient le rowid Oracle' ,
  TABTPSPARTIEL VARCHAR(20) NULL ,
  DATEDEMANDE DATE NOT NULL ,
  DATEDEBUT DATE NOT NULL ,
  DATEFIN DATE NULL DEFAULT '9999-12-31',
  DATESTATUT DATE NOT NULL ,
  STATUT VARCHAR(5) NOT NULL ,
  PRIMARY KEY (DECLARATIONID) );

CREATE  TABLE DEMANDEDECLARATIONTP (
  DEMANDEID INT NOT NULL ,
  DECLARATIONID INT NOT NULL ,
  PRIMARY KEY (DEMANDEID, DECLARATIONID) );

CREATE  TABLE DEMANDE (
  DEMANDEID INT NOT NULL AUTO_INCREMENT ,
  TYPEABSENCEID VARCHAR(10) NOT NULL ,
  DATEDEBUT DATE NOT NULL ,
  MOMENTDEBUT VARCHAR(2) NOT NULL ,
  DATEFIN DATE NOT NULL ,
  MOMENTFIN VARCHAR(2) NOT NULL ,
  COMMENTAIRE VARCHAR(50) NULL ,
  NBREJRSDEMANDE DECIMAL(5,2) NOT NULL ,
  DATEDEMANDE DATE NOT NULL ,
  DATESTATUT DATE NOT NULL ,
  STATUT VARCHAR(5) NOT NULL ,
  MOTIFREFUS VARCHAR(250) NULL ,
  PRIMARY KEY (DEMANDEID) );

CREATE  TABLE HARPABSENCE (
  HARPEGEID VARCHAR(10) NOT NULL ,
  DATEDEBUT DATE NOT NULL ,
  DATEFIN DATE NOT NULL ,
  HARPTYPE VARCHAR(30) NOT NULL ,
  PRIMARY KEY (HARPEGEID, DATEDEBUT, HARPTYPE) );

CREATE TABLE CONSTANTES (
  ID_CONSTANTES INT(11) NOT NULL AUTO_INCREMENT,
  NOM VARCHAR(45) NOT NULL,
  VALEUR VARCHAR(250) DEFAULT NULL,
  PRIMARY KEY  (ID_CONSTANTES) ) ;
  
CREATE TABLE COMMENTAIRECONGE (
  COMMENTAIRECONGEID INT(11) NOT NULL AUTO_INCREMENT,
  HARPEGEID VARCHAR(8) NOT NULL,
  TYPEABSENCEID VARCHAR(5) NOT NULL,
  DATEAJOUTCONGE DATE NOT NULL,
  COMMENTAIRE VARCHAR(100) NOT NULL,
  NBRJRSAJOUTE DECIMAL(5,2) NOT NULL,
  PRIMARY KEY  (COMMENTAIRECONGEID)) ;

 

INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('1', 'FERIE2011', '20111101;20111111;20111225;20120101;20120409;20120501;20120508;20120517;20120528;20120714;20120815');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('2', 'FERIE2012', '20121101;20121111;20121225;20130101;20130401;20130501;20130508;20130509;20130520;20130714;20130815');

INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('3', 'FINPERIODE', '0831');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('4', 'DEBUTPERIODE', '0901');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('5', 'SMTPSERVER', 'smtp.univ-paris1.fr');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('6', 'LDAPSERVER', 'ldap.univ-paris1.fr');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('7', 'LDAPLOGIN', 'cn=sigadm,ou=admin,dc=univ-paris1,dc=fr');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('8', 'LDAPPASSWD', '@g@6Gadm');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('9', 'LDAPSEARCHBASE', 'ou=people,dc=univ-paris1,dc=fr');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('10', 'LDAPATTRIBUTE', 'supannempid');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('11', 'CASSERVER', 'cas.univ-paris1.fr');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('12', 'CASPATH', '/cas');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('13', 'FIN_REPORT', '0331');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('14', 'LIMITE_CONGE_PERIODE', 'n');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('15', 'NBJOURS2011', '53');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('16', 'NBJOURS2012', '51');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('17', 'NBJOURS2013', '50');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('18', 'FERIE2013', '20131101;20131111;20131225;20140101;20140412;20140501;20140508;20140529;20140609;20140714;20140815');
INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('19', 'G2TURL', 'http:\\g2t.univ-paris1.fr');
-- INSERT INTO CONSTANTES(ID_CONSTANTES,NOM,VALEUR) VALUES('17', 'REPORTACTIF', 'O');


INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('ann11', 'Annuel 2011/2012', '2011', '#03B525', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('ann12', 'Annuel 2012/2013', '2012', '#2E8B57', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('ann13', 'Annuel 2013/2014', '2013', '#00FF7F', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('atten', 'Demandes en attente', NULL, '#006699', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('cet', 'CET', NULL, '#FF00FF', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('ferie', 'Jour férié', NULL, '#E7926D', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('harp', 'Congé Harpège', NULL, '#6826EE', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('tppar', 'Temps partiel', NULL, '#FFFF33', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES ('sup11', 'Congés complémentaires 2011/2012', '2011', '#AABE2', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES ('sup12', 'Congés complémentaires 2012/2013', '2012', '#48D1CC', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES ('sup13', 'Congés complémentaires 2013/2014', '2013', '#4682B4', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES ('abs', 'Absence', NULL, '#FF0000', '');

INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('evtfam', 'Evènement familial', '', '#FF0000','abs');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('divers', 'Absences diverses', '', '#FF0000','abs');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('syndic', 'Syndical', '', '#FF0000','abs');

INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('conc', 'Concours', '', '#FF0000','divers');
-- INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece', 'Décès (conjoint,parents,enfants)', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece1', 'Décès (conjoint,parents,enfants)', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece2', 'Décès du conjoint, des parents, des enfants dans un département limitrophe.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece3', 'Décès du conjoint, des parents, des enfants dans un autre département.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece4', 'Décès du frère, de la soeur, du beau frère ou de la belle soeur, des beaux parents', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece7', 'Décès des grands parents dans le département.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece8', 'Décès des grands parents dans un département limitrophe.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dece9', 'Décès des grands parents dans un autre département.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('deme', 'Déménagement', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dive1', 'Réunion à l''extérieur (--Obsolète--)', '', '#FF0000', '');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dive2', 'Jury de concours', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dive3', 'Récupérations diverses', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('dive4', 'Formation', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('enmal', 'Garde d''enfant malade', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('mari1', 'Mariage de l''intéressé', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('mari2', 'Mariage du fils ou de la fille dans le département', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('mari3', 'Mariage du fils ou de la fille dans un département limitrophe.', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('mater', 'Maternité (examens médicaux obligatoires antérieurs ou postérieurs à  l''accouchement)', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('nais', 'Naissance (autorisation accordée au père)', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('pacs', 'PACS', '', '#FF0000', 'evtfam');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('pconc', 'Préparation aux concours', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('spec', 'Autorisation d''absence', '', '#FF0000', 'divers');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('synd1', 'A titre syndical (participation d''élus mandatés)', '', '#FF0000', 'syndic');
INSERT INTO TYPEABSENCE(TYPEABSENCEID,LIBELLE,ANNEEREF,COULEUR,ABSENCEIDPARENT) VALUES('synd2', 'A titre syndical (convocation à  différentes instances)', '', '#FF0000', 'syndic');

INSERT INTO COMPLEMENT(COMPLEMENTID,VALEUR,HARPEGEID) VALUES('ESTADMIN','O',9328);