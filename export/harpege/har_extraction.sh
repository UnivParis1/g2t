#!/bin/ksh

NLS_LANG=FRENCH_FRANCE.WE8ISO8859P1; export NLS_LANG
##NLS_LANG=FRENCH_FRANCE.UTF8; export NLS_LANG

export CHEMIN=/opt/harpege/g2t-v3
export RESULTATS=$CHEMIN/resultats
export DATE_JOUR=`date +%Y%m%d`
export ORACLE_HOME=/opt/oracle/product/11.2.0/db_home1
export PATH=$PATH:$ORACLE_HOME/bin
export ORACLE_SID=HARPRD11
echo Suppression des fichiers dans $RESULTATS
rm -f $RESULTATS/*
cd $CHEMIN
sqlplus harp_adm/xxx @$CHEMIN/har_agents.sql
sqlplus harp_adm/xxx @$CHEMIN/har_affectations.sql
sqlplus harp_adm/xxx @$CHEMIN/har_structures.sql
sqlplus harp_adm/xxx @$CHEMIN/har_absence.sql

echo "Avant suppr_espace..."
$CHEMIN/suppr_espaces_fin_ligne.sh $CHEMIN/har_agents.lst
$CHEMIN/suppr_espaces_fin_ligne.sh $CHEMIN/har_affectations.lst
$CHEMIN/suppr_espaces_fin_ligne.sh $CHEMIN/har_structures.lst
$CHEMIN/suppr_espaces_fin_ligne.sh $CHEMIN/har_absence.lst

echo "Apres...."
if [ -s $CHEMIN/har_agents.lst ] ; then
   cp $CHEMIN/har_agents.lst       $CHEMIN/har_agents_$DATE_JOUR.dat
   cp $CHEMIN/har_affectations.lst $CHEMIN/har_affectations_$DATE_JOUR.dat
   cp $CHEMIN/har_structures.lst   $CHEMIN/har_structures_$DATE_JOUR.dat
   cp $CHEMIN/har_absence.lst   $CHEMIN/har_absence_$DATE_JOUR.dat

   for FICHIER in `ls $CHEMIN/har*${DATE_JOUR}.dat 2>/dev/null`
   do
     echo deplacement $FICHIER vers $RESULTATS
     mv $FICHIER $RESULTATS
   done
   scp $RESULTATS/*.dat g2t@serveur-g2t:/var/www/g2t/INPUT_FILES_V3

else
   echo gestartt : fichier vide. Pas d\'extraction
fi
#
## fin du script
#
