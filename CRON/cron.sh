#!/bin/bash
cd /webhome/g2t-dev/CRON
mydate=`date +%Y-%m-%d`
echo `date` debut traitement >>./log/trace_cron_$mydate.log
php artt_import_harp.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_maj_solde_deb_periode.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_maj_solde_rtt_deb_periode.php  >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
#### PAS D'ATT => Inutile  : php g2t_calc_solde_att.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
#### PAS D'ATT => Inutile  : php ../html/g2t_gest_position_att.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_import_ens_resp.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_import_conge_harp.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php p1_specific_update.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_batch_mail_autodcl.php  >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
php artt_batch_mail_conge.php >>./log/trace_cron_$mydate.log 2>>./log/trace_cron_$mydate.log
echo `date`  fin de traitement >>./log/trace_cron_$mydate.log

