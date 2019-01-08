SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/fonctions.txt


select trim(LEADING '0' FROM  substr(ZY00.MATCLE, 4)) ||'|'||
trim(ZYV1.FONCTI) ||'|'||
trim(ZD01.LIBABR) ||'|'||
trim(ZD01.LIBLON) ||'|'||
trim(ZYV1.UOLIEE) ||'|'
from ZY00, ZYV1, ZD00, ZD01
where DATDEB <= sysdate
and DATFIN >=sysdate
and ZYV1.FONCTI = ZD00.CDCODE
and ZD00.NUDOSS = ZD01.nudoss
and ZD00.CDSTCO = 'ITG'
and ZD00.CDREGL = 'FR4'
and ZY00.nudoss = ZYV1.NUDOSS
;

spool off
exit;


