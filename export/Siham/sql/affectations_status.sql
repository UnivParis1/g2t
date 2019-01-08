SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/affectations_status.txt

select
to_number(substr(ZY00.MATCLE, 4)) ||';'||
ZYFL.NULIGN ||';'||
CASE
  WHEN ZYFL.STATUT = 'C0101' THEN 'C0101_PER' -- C0101 (loi 84-16 art. 4.1)
  WHEN ZYFL.STATUT = 'C0102' THEN 'C0102_PER' -- C0102 (loi 84-16 art. 4.2)
  WHEN ZYFL.STATUT = 'C0136' THEN 'C0136_PER' -- C0136 (loi 84-16 art. 6 quinquies)
--  WHEN ZYFL.STATUT = 'C0101' THEN 'CONTRAT_PER' -- C0101 (loi 84-16 art. 4.1)
--  WHEN ZYFL.STATUT = 'C0102' THEN 'CONTRAT_PER' -- C0102 (loi 84-16 art. 4.2)
--  WHEN ZYFL.STATUT = 'C0136' THEN 'CONTRAT_PER' -- C0136 (loi 84-16 art. 6 quinquies)
  WHEN substr(ZYFL.STATUT,0,1) = 'C' THEN 'CONTRAT'
  ELSE ZYFL.STATUT
END ||';'||
to_char(ZYFL.DATEFF, 'YYYY-MM-DD') ||';'||
to_char(ZYFL.DATXXX-1, 'YYYY-MM-DD') ||';'
from ZY00, ZYFL, ZYYP
where
ZY00.NUDOSS = ZYFL.NUDOSS
and (ZYFL.STATUT not in ('00000','HB000','HB010','HB015')
     and ZYFL.STATUT not like 'HB%')
and to_char(ZYFL.DATXXX-1, 'YYYY-MM-DD') > '2015-12-31'
-- exclure population enseignant
and ZY00.nudoss = ZYYP.nudoss
and ((ZYYP.POPULA not like '41%' AND ZYYP.POPULA not like '21%' AND ZYYP.POPULA not like '11%')
  or (ZY00.MATCLE IN ('UP1000017827','UP1000081910','UP1000083686','UP1000015111','UP1000084187')))
and ZYYP.DTEF00 <= sysdate
and ZYYP.DATXXX >= sysdate
and ZYFL.STATUT NOT IN ('C2036', 'C0322' , 'C2053','C1204','C1202')
-- C0322 => Contrat Doctorant EPES ou EP Recherche
-- C2036 => Reprise données HARPEGE (un peu fourre-tout)
-- C2053 => Doctorant EPES/EP Recherche sans enseignement
;

spool off
exit;

