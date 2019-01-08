SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/affectations_structures.txt

select
to_number(substr(ZY00.MATCLE, 4)) ||';'||
ZY3C.nulign ||';'||
trim(ZY3C.IDOU00) ||';'||
to_char(ZY3C.DTEF00, 'YYYY-MM-DD') ||';'||
to_char(ZY3C.DTEN00, 'YYYY-MM-DD') ||';'
from ZY3C, ZY00, ZYYP
where
ZY00.NUDOSS = ZY3C.NUDOSS
-- and ZY3C.FLINHE = 0
and ZY3C.TYTRST = 'FUN'
and ZY3C.IDOU00 != 'UO_REP'
and ZY3C.IDOU00 != '0000000000'
and to_char(ZY3C.DTEN00, 'YYYY-MM-DD') > '2015-12-31'
-- exclure population enseignant
and ZY00.nudoss = ZYYP.nudoss
and ((ZYYP.POPULA not like '41%' AND ZYYP.POPULA not like '21%' AND ZYYP.POPULA not like '11%')
  or (ZY00.MATCLE IN ('UP1000017827','UP1000081910','UP1000083686','UP1000015111','UP1000084187')))
and ZYYP.DTEF00 <= sysdate
and ZYYP.DATXXX >= sysdate
;

spool off
exit;

