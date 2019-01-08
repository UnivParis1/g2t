SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/conges.txt

select to_number(substr(ZY00.MATCLE, 4)) || ';' ||
to_char(ZYAG.DATDEB, 'YYYY/MM/DD') || ';' ||
to_char(ZYAG.DATFIN, 'YYYY/MM/DD') || ';' ||
LIB_CONGE.LIBABR || ';'
from ZYAG, ZY00,
-- alias pour les jointures sur ZD00, attention a ajouter des contraintes sur le répertoire (CDSTCO) et la réglementation (CDREGL)
ZD00 CONGE,
ZD01 LIB_CONGE
where ZYAG.nudoss = ZY00.nudoss
and to_char(ZYAG.DATDEB, 'YYYY/MM/DD') > '2011/01/01'
-- intitulés congés
and ZYAG.MOTIFA = CONGE.CDCODE
and CONGE.NUDOSS = LIB_CONGE.NUDOSS
and CONGE.CDREGL = 'FR6'
and CONGE.CDSTCO = 'DSJ'
;

spool off
exit;

