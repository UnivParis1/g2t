#!/bin/bash
cd /webhome/g2t-dev/CRON
mydate=`date +%Y-%m-%d`
echo `date` debut traitement >>./log/trace_cron_$mydate.log
php import_agent.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php import_absence.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php import_structure.php  >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php import_affectation.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php calcul_solde.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log

php migration_v2_v3.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log

## php p1_specific_update.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
## php artt_batch_mail_autodcl.php  >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
## php artt_batch_mail_conge.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
echo `date`  fin de traitement >>./log/trace_cron_$mydate.log

