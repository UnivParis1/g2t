@ECHO OFF
SET LOG_PATH=%USERPROFILE%\Download\g2t_log
@ECHO LOG_PATH= %LOG_PATH%

mkdir %LOG_PATH%

del %LOG_PATH%\trace_cron.log
del %LOG_PATH%\trace_cron_error.log

for /f "delims=" %%a in (' powershell "Split-Path -Path (Get-Item C:\wamp64\bin\apache\apache2.4.46\bin\php.ini).Target" ') do set "PHPDIR=%%a"
@ECHO PHPDIR = %PHPDIR% >>%LOG_PATH%\trace_cron.log

if not defined PHPDIR (
   echo PHPDIR is NOT defined >>%LOG_PATH%\trace_cron.log
   exit /B
)

REM set PHPDIR=d:\php
REM set PHPDIR=C:\wamp64\bin\php\php7.3.21
REM set PHPDIR=C:\wamp64\bin\php\php7.4.9

%PHPDIR%\php --version >> %LOG_PATH%\trace_cron.log

REM %PHPDIR%\php import_agent.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php import_absence.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php import_structure.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php p1_specific_update.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM%PHPDIR%\php import_affectation_siham.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php p1_post_affectation.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php calcul_solde.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
%PHPDIR%\php synchro_demandes_cet.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log

REM ----------------------------------------
REM -- SCRIPT DE MIGRATION V2 - V3
REM %PHPDIR%\php migration_v2_v3.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log

REM %PHPDIR%\php mail_alerte_reliquats.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php mail_conges.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php mail_declarationTP.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php generer_solde.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
REM %PHPDIR%\php demande_cet.php -d error_log=%LOG_PATH%\PHP_Log.log >>%LOG_PATH%\trace_cron.log 2>>%LOG_PATH%\trace_cron_error.log
