set PHPDIR=d:\php\bin

del .\log\trace_cron.log
del .\log\trace_cron_error.log

%PHPDIR%\php import_agent.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
%PHPDIR%\php import_absence.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
%PHPDIR%\php import_structure.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
%PHPDIR%\php import_affectation.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
%PHPDIR%\php calcul_solde.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log

REM ----------------------------------------
REM -- SCRIPT DE MIGRATION V2 - V3
%PHPDIR%\php migration_v2_v3.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log

REM %PHPDIR%\php mail_conges.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
REM %PHPDIR%\php mail_declarationTP.php -d error_log=./log/PHP_Log.log >>./log/trace_cron.log 2>>./log/trace_cron_error.log
