#!/bin/bash
chemin=`dirname $0`
cd $chemin
chemin=`pwd`

#cd /var/www/g2t/CRON

mydate=`date +%Y-%m-%d`
echo `date` debut traitement >>./log/trace_cron_$mydate.log

php php/switch_maintenance.php >>./log/trace_cron_$mydate.log

numjour=`date +%w`
## Si le numero du jour est different de 0 ou 6 alors on traite les fichiers d'import
## 0 = Dimanche
## 6 = Samedi
if [ $numjour -ne 6 -a $numjour -ne 0 ]
then
   php php/import_agent.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/import_absence.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/import_structure.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php specific/p1_specific_update.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/import_affectation_siham.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php specific/p1_post_affectation.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/calcul_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php specific/p1_post_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/mail_conges.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/mail_declarationTP.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/synchro_demandes_cet.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   php php/synchro_conventions_teletravail.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
fi

numjour=`date +%d`

##Si on est le premier jour du mois
if [ $numjour -eq 1 ]
then
   echo "Avant generation solde" >>./log/trace_cron_$mydate.log
   php php/generer_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   echo "Avant controles post MAJ" >>./log/trace_cron_$mydate.log
   php php/ctrl_post_maj.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
   echo "Avant generation de l'historique des CET" >>./log/trace_cron_$mydate.log
   php php/demande_cet.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
fi
php php/mail_alerte_reliquats.php >>./log/trace_cron_$mydate.log
php php/mail_alerte_teletravail.php >>./log/trace_cron_$mydate.log

php php/switch_maintenance.php >>./log/trace_cron_$mydate.log

echo `date`  fin de traitement >>./log/trace_cron_$mydate.log

