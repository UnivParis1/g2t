set lines 500
set pages 0
set feed off;
set define off;


REM Extraction sur fichier
set termout off
spool har_agents.lst;
set lines 500
set pages 0
select 
 I.No_Individu||'#'||
 I.C_Civilite||'#'||
 REPLACE(I.Nom_Usuel,'''','\''')||'#'||
 REPLACE(I.Prenom,'''','\''')||'#'||
 m.No_E_Mail||'#'||
 '#'
from Individu I, UP1.g2t_e_mail m
where I.No_Individu = m.No_Individu (+)
;
spool off;
exit


