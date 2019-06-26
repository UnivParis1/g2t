set PHPDIR=d:\php

del .\log\trace_cron.log
del .\log\trace_cron_error.log

REM %PHPDIR%\php import_agent.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php import_absence.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php import_structure.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php p1_specific_update.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php import_affectation_siham.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php p1_post_affectation.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php calcul_solde.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log




REM ----------------------------------------
REM -- SCRIPT DE MIGRATION V2 - V3
REM %PHPDIR%\php migration_v2_v3.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log


%PHPDIR%\php mail_conges.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php mail_declarationTP.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php generer_solde.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php demande_cet.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
