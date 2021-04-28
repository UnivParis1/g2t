@ECHO OFF 
SET LOG_PATH=%USERPROFILE%\Download\g2t_log
@ECHO LOG_PATH= %LOG_PATH%

mkdir %LOG_PATH%

del %LOG_PATH%\changement_affectationid_log.log
del %LOG_PATH%\changement_affectationid_error.log

for /f "delims=" %%a in (' powershell "Split-Path -Path (Get-Item C:\wamp64\bin\apache\apache2.4.46\bin\php.ini).Target" ') do set "PHPDIR=%%a"
@ECHO PHPDIR = %PHPDIR% >>%LOG_PATH%\changement_affectationid_log.log

if not defined PHPDIR (
   echo PHPDIR is NOT defined >>%LOG_PATH%\changement_affectationid_log.log
   exit /B
)

REM set PHPDIR=d:\php
REM set PHPDIR=C:\wamp64\bin\php\php7.3.21
REM set PHPDIR=C:\wamp64\bin\php\php7.4.9

%PHPDIR%\php --version >> %LOG_PATH%\changement_affectationid_log.log

%PHPDIR%\php changement_affectationid.php -d error_log=%LOG_PATH%\changement_affectationid_PHP.log >>%LOG_PATH%\changement_affectationid_log.log 2>>%LOG_PATH%\changement_affectationid_error.log
