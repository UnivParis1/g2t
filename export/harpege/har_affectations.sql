SET PAGES 0
SET HEAD OFF
SET FEED OFF
SET ECHO OFF

SET SERVEROUTPUT ON
SET TERMOUT OFF
set linesize 150
set lines 500
SPOOL har_affectations.lst

DECLARE
   CURSOR CU_Toutes_Aff IS
      -- fonctionnaires ou assimilés, non enseignants
      SELECT A.No_Seq_Affectation,
             O.No_Dossier_Pers,
             O.No_Seq_Carriere,
             NULL No_Contrat_Travail,
             A.C_Structure,
             A.D_Deb_Affectation,
             A.D_Fin_Affectation,
             A.Num_Quot_Affectation,
             A.Den_Quot_Affectation,
             A.D_Creation,
             A.D_Modification,
             A.RowId
         FROM Individu I,
              Carriere C,
              Type_Population TP,
              Occupation O,
              Affectation A
         WHERE O.No_Dossier_Pers = A.No_Dossier_Pers
           AND O.No_Occupation = A.No_Occupation
           AND O.No_Dossier_Pers = C.No_Dossier_Pers
           AND O.No_Seq_Carriere = C.No_Seq_Carriere
           AND C.C_Type_Population = TP.C_Type_Population
           -- AND TP.Tem_Enseignant = 'N'
           AND (TP.Tem_Enseignant = 'N'  
                OR (TP.Tem_Enseignant = 'O' 
                    AND (A.C_Structure LIKE 'DGH%' -- DGH% = DSIUN et sous service
                         OR A.C_Structure = 'PR5'  -- Service TICE
                         -- OR A.C_Structure = 'SC3'  -- Service UEFAPS
                        )
                   )
               )   
           AND O.No_Dossier_Pers = I.No_Individu
           AND I.D_Deces IS NULL
	   AND (A.D_FIN_AFFECTATION>='01/01/2011' OR A.D_FIN_AFFECTATION IS NULL)
--and o.no_dossier_pers in (65878, 3025)

      UNION
-- contractuels non enseignants (hors vacataires)
      SELECT A.No_Seq_Affectation,
             O.No_Dossier_Pers,
             NULL No_Seq_Carriere,
             O.No_Contrat_Travail,
             A.C_Structure,
             A.D_Deb_Affectation,
             A.D_Fin_Affectation,
             A.Num_Quot_Affectation,
             A.Den_Quot_Affectation,
             A.D_Creation,
             A.D_Modification,
             A.RowId
         FROM Individu I,
              Contrat_Travail CT,
              Type_Contrat_Travail TCT,
              Occupation O,
              Affectation A
         WHERE O.No_Dossier_Pers = A.No_Dossier_Pers
           AND O.No_Occupation = A.No_Occupation
           AND O.No_Dossier_Pers = CT.No_Dossier_Pers
           AND O.No_Contrat_Travail = CT.No_Contrat_Travail
           AND CT.C_Type_Contrat_Trav = TCT.C_Type_Contrat_Trav
           AND CT.C_Type_Contrat_Trav NOT IN ('VF','VN')
           AND (TCT.Tem_Enseignant = 'N' OR TCT.Tem_Enseignant IS NULL)
           AND O.No_Dossier_Pers = I.No_Individu
           AND I.D_Deces IS NULL
	   AND (A.D_FIN_AFFECTATION>='01/01/2011' OR A.D_FIN_AFFECTATION IS NULL)
--and o.no_dossier_pers in (65878, 3025)
      ORDER BY 2, 6 DESC;
   r_Toutes_Aff CU_Toutes_Aff%ROWTYPE;

   v_No_Dossier_Pers_Aff_Preced Personnel.No_Dossier_Pers%TYPE := 0;
BEGIN
   Dbms_Output.Enable(2000000);

   -- pour toutes les affectations en cours
   FOR r_Toutes_Aff IN CU_Toutes_Aff LOOP

      -- teste si on change d'agent ; si tel est le cas, on evalue la date de fin de la premier affectation fetchee
      -- si cete date est echue, on la remplace par une date nulle
      -- IF r_Toutes_Aff.No_Dossier_Pers <> v_No_Dossier_Pers_Aff_Preced THEN
      --   IF r_Toutes_Aff.D_Fin_Affectation < TRUNC(SYSDATE) THEN
      --      r_Toutes_Aff.D_Fin_Affectation := NULL;
      --   END IF;
      --   v_No_Dossier_Pers_Aff_Preced := r_Toutes_Aff.No_Dossier_Pers;
      -- END IF;

      -- mise en spool
      Dbms_Output.Put_Line (r_Toutes_Aff.No_Seq_Affectation ||';'|| --  r_Toutes_Aff.RowId ||';'||
                            r_Toutes_Aff.No_Dossier_Pers ||';'||
                            r_Toutes_Aff.No_Contrat_Travail ||';'||
                            TO_CHAR(r_Toutes_Aff.D_Deb_Affectation,'YYYY-MM-DD') ||';'||
                            TO_CHAR(r_Toutes_Aff.D_Fin_Affectation,'YYYY-MM-DD') ||';'||
                            TO_CHAR(r_Toutes_Aff.D_Modification,'YYYY-MM-DD') ||';'||
                            r_Toutes_Aff.C_Structure ||';'||
                            r_Toutes_Aff.Num_Quot_Affectation ||';'||
                            r_Toutes_Aff.Den_Quot_Affectation ||';');

   END LOOP;
END;
/

SPOOL OFF

EXIT

REM Fin du script

