#!/bin/ksh

NLS_LANG=FRENCH_FRANCE.WE8ISO8859P1; export NLS_LANG

export CHEMIN=/home/dbpl304/extract_SI_P1/G2T
export RESULTATS=$CHEMIN/output
export SQL=$CHEMIN/sql
export DATE_JOUR=`date +%Y%m%d`
export ORACLE_HOME=/distrib/oracle/dbpl304/product/12.2.0.1.0/db_1
export PATH=$PATH:$ORACLE_HOME/bin
export ORACLE_SID=PL304

cd $CHEMIN

sqlplus HR/password @$SQL/structures.sql
sqlplus HR/password @$SQL/agents.sql
sqlplus HR/password @$SQL/affectations_modalité.sql
sqlplus HR/password @$SQL/affectations_status.sql
sqlplus HR/password @$SQL/affectations_structures.sql
sqlplus HR/password @$SQL/congés.sql
sqlplus HR/password @$SQL/fonctions.sql

cd $RESULTATS

scp $RESULTATS/structures.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_structures_$DATE_JOUR.dat
scp $RESULTATS/agents.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_agents_$DATE_JOUR.dat
scp $RESULTATS/conges.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_absence_$DATE_JOUR.dat
scp $RESULTATS/affectations_modalite.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_affectations_modalite_$DATE_JOUR.dat
scp $RESULTATS/affectations_status.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_affectations_status_$DATE_JOUR.dat
scp $RESULTATS/affectations_structures.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_affectations_structures_$DATE_JOUR.dat
scp $RESULTATS/fonctions.txt g2t@serveur.univ-paris1.fr:/var/www/g2t/INPUT_FILES_V3/siham_fonctions_$DATE_JOUR.dat

