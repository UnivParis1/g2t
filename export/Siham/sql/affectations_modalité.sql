SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/affectations_modalite.txt

select
to_number(substr(ZY00.MATCLE, 4)) ||';'||
ZYTL.NULIGN ||';'||
ZYTL.RTSTHR ||';'||
to_char(ZYTL.DATEFF, 'YYYY-MM-DD') ||';'||
-- to_char(ZYTL.DATXXX-1, 'YYYY-MM-DD')  ||';'
-- Si pas de date de fin prevue ==> la date est 0001-01-01. On retourne la date ZYTL.DATXXX-1.
DECODE(to_char(ZYTL.FINPRE, 'YYYY-MM-DD'),'0001-01-01',to_char(ZYTL.DATXXX-1, 'YYYY-MM-DD'),to_char(ZYTL.FINPRE, 'YYYY-MM-DD'))  ||';'
from ZY00, ZYTL, ZYYP
where
ZY00.NUDOSS = ZYTL.NUDOSS
and to_char(ZYTL.DATXXX-1, 'YYYY-MM-DD') > '2015-12-31'
-- exclure population enseignant
and ZY00.nudoss = ZYYP.nudoss
and ((ZYYP.POPULA not like '41%' AND ZYYP.POPULA not like '21%' AND ZYYP.POPULA not like '11%')
  or (ZY00.MATCLE IN ('UP1000017827','UP1000081910','UP1000083686','UP1000015111','UP1000084187')))
and ZYYP.DTEF00 <= sysdate
and ZYYP.DATXXX >= sysdate
-- order by to_number(substr(ZY00.MATCLE, 4))
;

spool off
exit;

