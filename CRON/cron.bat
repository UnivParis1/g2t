del .\log\trace_cron.log
del .\log\trace_cron_error.log

php import_agent.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
php import_absence.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
php import_structure.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
php import_affectation.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM php calcul_solde.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log

REM php artt_maj_solde_rtt_deb_periode.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM php p1_specific_update.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM php artt_batch_mail_autodcl.php  -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM php artt_batch_mail_conge.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
