<?php
/*
CREATION DES TABLES TEMPORAIRES
CREATE TABLE `AFFECTATION_NEW` (
  `AFFECTATIONID` varchar(30) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Cette colonne contient le rowid Oracle',
  `HARPEGEID` varchar(10) NOT NULL,
  `NUMCONTRAT` int(11) DEFAULT '0',
  `DATEDEBUT` date NOT NULL,
  `DATEFIN` date DEFAULT '9999-12-31',
  `DATEMODIFICATION` date DEFAULT '0000-00-00',
  `STRUCTUREID` varchar(10) NOT NULL,
  `NUMQUOTITE` int(11) DEFAULT '0',
  `DENOMQUOTITE` int(11) DEFAULT '100',
  `OBSOLETE` varchar(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (`AFFECTATIONID`),
  KEY `HARPEGEID` (`HARPEGEID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `DECLARATIONTP_NEW` (
  `DECLARATIONID` int(11) NOT NULL AUTO_INCREMENT,
  `AFFECTATIONID` varchar(30) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Cette colonne contient le rowid Oracle',
  `TABTPSPARTIEL` varchar(20) DEFAULT NULL,
  `DATEDEMANDE` date NOT NULL,
  `DATEDEBUT` date NOT NULL,
  `DATEFIN` date DEFAULT '9999-12-31',
  `DATESTATUT` date NOT NULL,
  `STATUT` varchar(5) NOT NULL,
  PRIMARY KEY (`DECLARATIONID`)
) ENGINE=InnoDB AUTO_INCREMENT=13024 DEFAULT CHARSET=latin1;

*/

    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');
    $fonctions = new fonctions($dbcon);
    
    $declaration_table = 'DECLARATIONTP';   // DECLARATIONTP_NEW
    $affectation_table = 'AFFECTATION';     // AFFECTATION_NEW


    echo "DEBUT DE LA MODIFICATION DES AFFECTATIONID \n";
    echo "Début : " . date("d/m/Y H:i:s") . " \n";
    echo "Début de l'injection des TP avec le nouvel affectationid dans la table $declaration_table \n";
    
    $insert_declarationtp_new = "INSERT INTO " . $declaration_table . " (AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT)
                                    SELECT 
                                        -- DTP.DECLARATIONID,
                                        CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(DTP.AFFECTATIONID, '_', 1), '_', -1) , '_' , SUBSTRING_INDEX(SUBSTRING_INDEX(DTP.AFFECTATIONID, '_', 3), '_', -1) , '_' , SUBSTRING_INDEX(SUBSTRING_INDEX(DTP.AFFECTATIONID, '_', 4), '_', -1)) AS AFFECTATIONID,
                                        DTP.TABTPSPARTIEL,
                                        DTP.DATEDEMANDE,
                                        DTP.DATEDEBUT,
                                        DTP.DATEFIN,
                                        DTP.DATESTATUT,
                                        DTP.STATUT
                                    FROM
                                        DECLARATIONTP DTP
                                    WHERE 
                                        DTP.AFFECTATIONID IN (SELECT AFF.AFFECTATIONID 
                                                            FROM AFFECTATION AFF 
                                                            WHERE 
                                                                AFFECTATIONID LIKE '%\_%\_%\_%' 
                                                                AND OBSOLETE = 'N')
                                    ;";
    $query_decltp = mysqli_query($dbcon, $insert_declarationtp_new);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "ERREUR INSERT $declaration_table => $erreur_requete \n";
    echo "Fin de l'injection des TP avec le nouvel affectationid dans la table $declaration_table \n";
    echo "---------------------------------------------------------------------------------- \n";
    echo "Début de l'injection des affectations avec le nouvel affectationid dans la table $affectation_table \n";
    $req_affectation_new = "SELECT 
                                CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(AFF.AFFECTATIONID, '_', 1), '_', -1) , '_' , SUBSTRING_INDEX(SUBSTRING_INDEX(AFF.AFFECTATIONID, '_', 3), '_', -1) , '_' , SUBSTRING_INDEX(SUBSTRING_INDEX(AFF.AFFECTATIONID, '_', 4), '_', -1)) AS AFFECTATIONID,
                                AFF.HARPEGEID,
                                AFF.NUMCONTRAT,
                                AFF.DATEDEBUT,
                                AFF.DATEFIN,
                                AFF.DATEMODIFICATION,
                                AFF.STRUCTUREID,
                                AFF.NUMQUOTITE,
                                AFF.DENOMQUOTITE,
                                AFF.OBSOLETE
                            FROM
                                AFFECTATION AFF
                            WHERE 
                                AFFECTATIONID LIKE '%\_%\_%\_%'
                                AND OBSOLETE = 'N'
                            ORDER BY AFFECTATIONID, DATEFIN";
    $query_aff = mysqli_query($dbcon, $req_affectation_new);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "SELECT AFFECTATION => $erreur_requete \n";
    else
    {
        $num_aff = 0;
        while ($result = mysqli_fetch_row($query_aff))
        {
            if ($num_aff == $result[0])
            {
                var_dump($result);
                $datefin = $result[4];
                $datemodification = $result[5];
                $structureid = $result[6];
            }
            else
            {
                if ($num_aff != 0) 
                {
                    $insert_affectation_new = "INSERT INTO " . $affectation_table . " (AFFECTATIONID, HARPEGEID, NUMCONTRAT, DATEDEBUT, DATEFIN, DATEMODIFICATION, STRUCTUREID, NUMQUOTITE, DENOMQUOTITE, OBSOLETE) 
                                                 VALUES ('$num_aff', '$harpege_id', '$num_contrat', '$datedeb', '$datefin', '$datemodification', '$structureid', '$numquotite', '$denomquotite', '$obsolete')";
                    mysqli_query($dbcon, $insert_affectation_new);
                    $erreur_requete = mysqli_error($dbcon);
                    echo $insert_affectation_new."\n";
                    if ($erreur_requete != "")
                        echo "ERREUR INSERT AFFECTATION => $erreur_requete \n";
                }
                var_dump($result);
                $num_aff = $result[0];
                $harpege_id = $result[1];
                $num_contrat = $result[2];
                $datedeb = $result[3];
                $datefin = $result[4];
                $datemodification = $result[5];
                $structureid = $result[6];
                $numquotite = $result[7];
                $denomquotite = $result[8];
                $obsolete = $result[9];
            }
        }
        // DERNIER INSERT 
        if ($num_aff != 0) 
        {
            $insert_affectation_new = "INSERT INTO " . $affectation_table . " (AFFECTATIONID, HARPEGEID, NUMCONTRAT, DATEDEBUT, DATEFIN, DATEMODIFICATION, STRUCTUREID, NUMQUOTITE, DENOMQUOTITE, OBSOLETE) 
                                            VALUES ('$num_aff', '$harpege_id', '$num_contrat', '$datedeb', '$datefin', '$datemodification', '$structureid', '$numquotite', '$denomquotite', '$obsolete')";
            mysqli_query($dbcon, $insert_affectation_new);
            $erreur_requete = mysqli_error($dbcon);
            echo $insert_affectation_new."\n";
            if ($erreur_requete != "")
                echo "INSERT AFFECTATION => $erreur_requete \n";
        }
    }
    echo "Fin de l'injection des affectations avec le nouvel affectationid dans la table $affectation_table \n";
    echo "Fin : " . date("d/m/Y H:i:s") . " \n";
    echo "FIN DE LA MODIFICATION DES AFFECTATIONID \n";

?>