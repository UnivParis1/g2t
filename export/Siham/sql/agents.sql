SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SET trims ON
spool ./output/agents.txt

select trim(LEADING '0' FROM  substr(MATCLE, 4)) ||'#'||
DECODE(QUALIT, 1, 'M.', 2, 'MME', 3, 'MLLE') ||'#'||
trim(NOMUSE) ||'#'||
trim(PRENOM) ||'#'||
trim(ZY0H.NUMTEL)  ||'##'
from ZY00, ZY0H, ZYFL
where ZY00.nudoss = ZY0H.nudoss
and ZY0H.FLPHON = 0
and ZY0H.TYPTEL = 'MPR'
and ZY00.NUDOSS = ZYFL.NUDOSS
and (ZYFL.STATUT not in ('C2038', 'C2052', 'C2036', 'C0322' , 'C2053', 'C2001', 'C0301', 'HB103','C2043','C2042','C1202','C0323')
      and ZYFL.STATUT not like 'HB%')
-- C0322 => Contrat Doctorant EPES ou EP Recherche
-- C2036 => Reprise données HARPEGE (un peu fourre-tout)
-- C2038 => Charge d''enseignement
-- C2053 => Doctorant EPES/EP Recherche sans enseignement
-- C2052 => Charge d''enseignement vacataire fonctionnaire
-- C2001 => ATER Mi-temps
-- C0301 => ATER
-- HB103 => Invite
-- C2043 => Maître tit perso
-- C2042 => Lecteur tit perso
-- C1202 => Jury conc exam
-- C0323 => Etudiant ctr EPES
and ZYFL.DATEFF <= sysdate
and ZYFL.DATXXX >= sysdate
;

spool off
exit;

