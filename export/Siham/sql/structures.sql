SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/structures.txt

select
trim(ZE00.IDOU00)  ||';'||
trim(ZE01.LBOULG) ||';'||
trim(ZE01.LBOUSH) ||';'||
trim(ZE2E.IDOU00) ||';'||
';'||
to_char(ZE0A.DATXXX, 'YYYY-MM-DD') ||';'||
trim(ZE00.TYOU00) ||';'||
trim(ZE0A.STOU01)
from ZE00 left join ZE2E on (ZE00.NUDOSS = ZE2E.NUDOSS and ZE2E.TYTRST = 'HIE') ,
ZE01,
ZE0A
where
-- libellés
ZE00.NUDOSS = ZE01.NUDOSS
-- statut
and ZE00.NUDOSS = ZE0A.NUDOSS
-- pour les UO actives uniquement
AND (
(ZE0A.DATXXX >= sysdate and ZE0A.STOU01 = 'ACT') 
OR 
ZE0A.STOU01 = 'INA'
)
-- on enlève les lieux de travail
-- and TRIM(ZE00.TYOU00) not in('LDT','PGR','CON','POL','STH','UNR')
-- and TRIM(ZE00.TYOU00) not in('LDT','PGR','CON','STH','UNR','DES','SDE')
-- and TRIM(ZE00.TYOU00) not in('LDT','PGR','CON','STH','DES','SDE')
and TRIM(ZE00.TYOU00) not in('LDT','CON','STH','DES','SDE')
;

spool off
exit;

