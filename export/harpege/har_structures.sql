SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF
SET LINESIZE 200
set lines 500
set termout off

SPOOL har_structures.lst

SELECT S.C_Structure ||';'||
 S.LL_Structure||';'||
 S.LC_Structure ||';'||
 S.C_Structure_Pere ||';'||
 ';' || --Responsable
 TO_CHAR(S.Date_Fermeture,'YYYY-MM-DD')  ||   -- Date de fermeture de la structure
 ';' || -- Gestionnaire
 ';' || -- AfficheSousStruct
 ';' || -- Affiche planning tout agent
 ';'
 -- ,FST.TEM_RESP_PRINC
 FROM Structure S
 WHERE S.C_Structure = 'DGAD'
--  AND (TRUNC(S.Date_Fermeture) >= TRUNC(SYSDATE) OR (S.Date_Fermeture IS NULL))
;



-- structures ouvertes aujourd'hui avec un responsable en activite
-- +
-- structure fermees avec un responsable
SELECT S.C_Structure ||';'||
 S.LL_Structure||';'||
 S.LC_Structure ||';'||
 S.C_Structure_Pere ||';'||
 IFS.No_Dossier_Pers ||';' || --Responsable
 TO_CHAR(S.Date_Fermeture,'YYYY-MM-DD')  ||   -- Date de fermeture de la structure 
 ';' || -- Gestionnaire
 ';' || -- AfficheSousStruct
 ';' || -- Affiche planning tout agent
 ';'
 -- ,FST.TEM_RESP_PRINC
 FROM Structure S,Individu_Fonct_Struct IFS,fonction_structurelle fst
 WHERE S.C_Structure = IFS.C_Structure
 AND (
 (
 (IFS.DT_Fin_Exerc_Resp>=SYSDATE OR IFS.DT_Fin_Exerc_Resp IS NULL)
 AND
 (S.Date_Fermeture IS NULL OR S.Date_Fermeture>= SYSDATE)
 )
 OR
 (TRUNC(S.Date_Fermeture) <= TRUNC(SYSDATE))
 )
 AND FST.C_FONCTION = IFS.C_FONCTION
 AND FST.TEM_RESP_PRINC != 'O';
-- UNION ALL
 SELECT S.C_Structure ||';'||
 S.LL_Structure||';'||
 S.LC_Structure ||';'||
 S.C_Structure_Pere ||';'||
 IFS.No_Dossier_Pers ||';' || --Responsable
 TO_CHAR(S.Date_Fermeture,'YYYY-MM-DD')  ||   -- Date de fermeture de la structure
 ';' || -- Gestionnaire
 ';' || -- AfficheSousStruct
 ';' || -- Affiche planning tout agent
 ';'
-- ,FST.TEM_RESP_PRINC
 FROM Structure S,Individu_Fonct_Struct IFS,fonction_structurelle fst
 WHERE S.C_Structure = IFS.C_Structure
 AND (
 (
 (IFS.DT_Fin_Exerc_Resp>=SYSDATE OR IFS.DT_Fin_Exerc_Resp IS NULL)
 AND
 (S.Date_Fermeture IS NULL OR S.Date_Fermeture>= SYSDATE)
 )
 OR
 (TRUNC(S.Date_Fermeture) <= TRUNC(SYSDATE))
 )
 AND FST.C_FONCTION = IFS.C_FONCTION
 AND FST.TEM_RESP_PRINC = 'O'
 AND UPPER(FST.LL_FONCTION) NOT LIKE 'DIRECT%'
 -- ORDER BY S.C_Structure
;

 SELECT S.C_Structure ||';'||
 S.LL_Structure||';'||
 S.LC_Structure ||';'||
 S.C_Structure_Pere ||';'||
 IFS.No_Dossier_Pers ||';' || --Responsable
 TO_CHAR(S.Date_Fermeture,'YYYY-MM-DD')  ||   -- Date de fermeture de la structure
 ';' || -- Gestionnaire
 ';' || -- AfficheSousStruct
 ';' || -- Affiche planning tout agent
 ';'
-- ,FST.TEM_RESP_PRINC
 FROM Structure S,Individu_Fonct_Struct IFS,fonction_structurelle fst
 WHERE S.C_Structure = IFS.C_Structure
 AND (
 (
 (IFS.DT_Fin_Exerc_Resp>=SYSDATE OR IFS.DT_Fin_Exerc_Resp IS NULL)
 AND
 (S.Date_Fermeture IS NULL OR S.Date_Fermeture>= SYSDATE)
 )
 OR
 (TRUNC(S.Date_Fermeture) <= TRUNC(SYSDATE))
 )
 AND FST.C_FONCTION = IFS.C_FONCTION
 AND FST.TEM_RESP_PRINC = 'O'
 AND UPPER(FST.LL_FONCTION) LIKE 'DIRECT%'
 -- ORDER BY S.C_Structure
;

SPOOL OFF

EXIT

REM Fin du script

