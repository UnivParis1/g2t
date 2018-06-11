SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500

SPOOL har_absence.lst

-- conges et modalites
SELECT No_Dossier_Pers ||';'||                                             -- No_Dossier_Pers
       TO_CHAR(D_Deb_Cge_Mod,'YYYY-MM-DD') ||';'||                         -- Debut
       TO_CHAR(NVL(D_Fin_Exe_Cge_Mod,D_Fin_Cge_Mod),'YYYY-MM-DD') ||';'||  -- Fin
       C_Cge_Mod ||';'                                                   -- Code conge/modalite
   FROM Annee_Universitaire AU,
        Cge_Mod_Agt CMA
   WHERE TRUNC(SYSDATE) BETWEEN D_Deb_Annee_Univ AND D_Fin_Annee_Univ
     AND NVL(D_Fin_Exe_Cge_Mod,D_Fin_Cge_Mod) >= ADD_MONTHS(D_Deb_Annee_Univ, -12)
     AND CMA.C_Cge_Mod NOT IN ('TEMPS_PARTIEL','CESS_PROG_ACTIVITE','MI_TPS_THERAP')  -- redondant / quotite d'affectation
     AND CMA.C_Cge_Mod NOT IN ('MAINTIEN_FONCTION','RECUL_AGE','SURNOMBRE')           -- prolongations d'activite
     AND CMA.C_Cge_Mod NOT IN ('CRCT','DELEGATION','MISSION','CONGE_AL3','CONGE_AL4','CONGE_AL5','CONGE_AL6') -- conges enseignants
UNION
-- positions d'absence
SELECT No_Dossier_Pers ||';'||                       -- No_Dossier_Pers
       TO_CHAR(D_Deb_Position,'YYYY-MM-DD') ||';'||  -- Debut
       TO_CHAR(D_Fin_Position,'YYYY-MM-DD') ||';'||  -- Fin
       P.C_Position ||';'                          -- Code position
   FROM Annee_Universitaire AU,
        Position P,
        Structure S,
        Changement_Position CP
   WHERE TRUNC(SYSDATE) BETWEEN D_Deb_Annee_Univ AND D_Fin_Annee_Univ
     AND D_Fin_Position >= ADD_MONTHS(D_Deb_Annee_Univ, -12)
     AND P.C_Position = CP.C_Position
     AND Tem_Activite = 'N'
     AND (Tem_Detachement = 'N'
          OR
          Tem_Detachement = 'O' AND (CP.C_RNE <> S.C_RNE OR CP.C_RNE IS NULL))
     AND S.C_Structure_Pere IS NULL
ORDER BY 1;

SPOOL OFF

EXIT

REM Fin du script

