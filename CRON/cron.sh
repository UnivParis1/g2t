#!/bin/bash
cd /webhome/g2t-demo/CRON
mydate=`date +%Y-%m-%d`
echo `date` debut traitement >>./log/trace_cron_$mydate.log

numjour=`date +%w`
## Si le numero du jour est different de 0 ou 6 alors on traite les fichiers d'import
## 0 = Dimanche
## 6 = Samedi
if [ $numjour -ne 6 -a $numjour -ne 0 ]
then
   php import_agent.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php import_absence.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php import_structure.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php p1_specific_update.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php import_affectation_siham.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php p1_post_affectation.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php calcul_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php mail_conges.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php mail_declarationTP.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log


###########################################################
## VERSION HARPEGE DE LA SYNCHRONISATION
###########################################################
##    php import_agent.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php import_absence.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php import_structure.php  >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php import_affectation.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php calcul_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log

############ php migration_v2_v3.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log

##   php p1_specific_update.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php mail_conges.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
##    php mail_declarationTP.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
fi

numjour=`date +%d`

##Si on est le premier jour du mois
if [ $numjour -eq 1 ]
then
   echo "Avant generation solde" >>./log/trace_cron_$mydate.log
   php generer_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
fi

echo `date`  fin de traitement >>./log/trace_cron_$mydate.log

