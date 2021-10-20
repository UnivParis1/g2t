<?php
    //require_once ("../html/class/fonctions.php");
    require_once ('../html/includes/dbconnection.php');
    //require_once ('../html/class/agent.php');
    require_once ('../html/includes/g2t_ws_url.php');
    require_once ('../html/includes/all_g2t_classes.php');
    
    $fonctions = new fonctions($dbcon);
    
    echo "Début du calcul des soldes " . date("d/m/Y H:i:s") . "\n";
    
    $sql = "SELECT HARPEGEID,NOM,PRENOM FROM AGENT ORDER BY HARPEGEID";
    $query_agent = mysqli_query($dbcon, $sql);
    $erreur_requete = mysqli_error($dbcon);
    if ($erreur_requete != "")
        echo "SELECT FROM AGENT => $erreur_requete \n";
    
    // echo "Avant deb / fin periode \n";
    $date_deb_period = $fonctions->anneeref() . $fonctions->debutperiode();
    $date_fin_period = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
    
    // echo "Avant Nbre jours periode... \n";
    $nbre_jour_periode = $fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
    // echo "Avant Nbre jours offert... \n";
    $nbr_jrs_offert = $fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
    
    // echo "Avant le 1er while \n";
    while ($result = mysqli_fetch_row($query_agent)) {
        // !!!!!!! ATTENTION : Les 2 lignes suivantes permettent de ne tester qu'un seul dossier !!!!
        // if ($result[0]!='82992')
        // continue;
        // !!!!!!! FIN du test d'un seul dossier !!!!
        
        $agentid = $result[0];
        $agentinfo = $result[1] . " " . $result[2];
        
        /*
         *
         * $solde_agent = 0;
         *
         * $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid' AND OBSOLETE='N' ORDER BY DATEDEBUT";
         *
         * $query_aff = mysqli_query ( $dbcon, $sql );
         * $erreur_requete = mysqli_error ($dbcon);
         * if ($erreur_requete != "")
         * echo "SELECT FROM AFFECTATION (Full) => $erreur_requete \n";
         *
         * $cas_general = true;
         * $nbre_total_jours = 0;
         * if (mysqli_num_rows ( $query_aff ) != 0) // On a des d'affectations
         * {
         * $datedebaffprec = date ( 'Y-m-d', 0 );
         * $datefinaffprec = date ( 'Y-m-d', 0 );
         * $duree_aff_ante_periode = 0;
         * while ( $result_aff = mysqli_fetch_row ( $query_aff ) )
         * {
         * if ($result_aff [5] != "0") // Si c'est un contrat
         * {
         * $datedebutaff = $result_aff [1];
         * $datearray = date_parse ( $result_aff [2] );
         * $year = $datearray ["year"];
         * // echo "year = $year \n";
         * // // Si la fin du contrat est dans plus de 2 ans, alors on raccourci la fin de contrat pour calculer le nombre de jour
         * // if (($result_aff[2]=='0000-00-00') or ($year > ($fonctions->anneeref() +2)))
         * // $datefinaff = date("Y-m-d", strtotime("+1 year"));
         * // Si la fin du contrat est vide (0000-00-00) ou si la fin du contrat est postérieur à la fin de période => On force à la fin de période
         * // echo "Convertion date fin affectation : " . $fonctions->formatdatedb($result_aff[2]) . "\n";
         * // echo "Calcul fin période = " .($fonctions->anneeref()+1) . $fonctions->finperiode() . "\n";
         * if (($result_aff [2] == '0000-00-00') or ($fonctions->formatdatedb ( $result_aff [2] ) > ($fonctions->anneeref () + 2) . $fonctions->finperiode ()))
         * $datefinaff = ($fonctions->anneeref () + 2) . $fonctions->finperiode ();
         * else
         * $datefinaff = $result_aff [2];
         * $duree_aff = $fonctions->nbjours_deux_dates ( $datedebutaff, $datefinaff );
         * // echo "datedebutaff = $datedebutaff datefinaff = $datefinaff\n";
         * // echo "Numéro de contrat pour $agentid ($agentinfo) = $result_aff[5] Durée (en jours) = " . $fonctions->nbjours_deux_dates($datedebutaff,$datefinaff) ."\n";
         * // echo "datedebutaff = $datedebutaff datefinaff = $datefinaff\n";
         * if (($datedebutaff != $datedebaffprec) && ($datefinaff != $datefinaffprec)) {
         * if ($datedebutaff == date ( "Y-m-d", strtotime ( "+1 day", strtotime ( $datefinaffprec ) ) )) {
         * $nbre_total_jours += $duree_aff;
         * if ($fonctions->formatdatedb ( $datedebutaff ) < $date_deb_period)
         * if ($fonctions->formatdatedb ( $datefinaff ) < $date_deb_period)
         * $duree_aff_ante_periode += $fonctions->nbjours_deux_dates ( $datedebutaff, $datefinaff );
         * else
         * $duree_aff_ante_periode += $fonctions->nbjours_deux_dates ( $datedebutaff, $date_deb_period ) - 1;
         * } else {
         * $nbre_total_jours = $duree_aff;
         * $duree_aff_ante_periode = 0;
         * if ($fonctions->formatdatedb ( $datedebutaff ) < $date_deb_period)
         * if ($fonctions->formatdatedb ( $datefinaff ) < $date_deb_period)
         * $duree_aff_ante_periode = $fonctions->nbjours_deux_dates ( $datedebutaff, $datefinaff );
         * else
         * $duree_aff_ante_periode = $fonctions->nbjours_deux_dates ( $datedebutaff, $date_deb_period ) - 1;
         * if ($nbre_total_jours >= 365)
         * $nbre_jours_manquants = 0;
         * else if (($fonctions->formatdatedb ( $datefinaff ) >= $date_fin_period))
         * $nbre_jours_manquants = 365;
         * }
         * }
         * // echo "nbre_total_jours = $nbre_total_jours duree_aff = $duree_aff duree_aff_ante_periode = $duree_aff_ante_periode pour $agentid ($agentinfo)\n";
         * if ($fonctions->formatdatedb ( $datedebutaff ) < $date_fin_period && $fonctions->formatdatedb ( $datefinaff ) > $date_deb_period && $cas_general) {
         * if ($duree_aff >= 365 || $duree_aff_ante_periode >= 365 || $nbre_total_jours - $duree_aff >= 365) {
         * $cas_general = true;
         * } else {
         * $cas_general = false;
         * // calcul du nombre de jours manquants pour obtenir une ancienneté d'1 an à partir de la date de début de période
         * $nbre_jours_manquants = 365 - ($nbre_total_jours - $duree_aff) - $fonctions->nbjours_deux_dates ( $datedebutaff, $date_deb_period ) + 1;
         * if ($nbre_jours_manquants < 0)
         * $nbre_jours_manquants = 0;
         * // echo "nbre_jours_manquants = $nbre_jours_manquants \n";
         * }
         * }
         * }
         * else // Si on trouve une affectation sans contrat alors on est dans le cas général
         * {
         * // On vérifie qu'il n'y a pas de contrats sur la période avec ancienneté totale consécutive < 1 an
         * if ($cas_general)
         * $cas_general = true;
         * // echo "CARRIERE datedebutaff = $result_aff[1] datefinaff = $result_aff[2] \n";
         * }
         * $datefinaffprec = $datefinaff;
         * $datedebaffprec = $datedebutaff;
         * }
         * }
         *
         * // echo "nbre_total_jours = $nbre_total_jours pour $agentid ($agentinfo)\n";
         * $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid'
         * AND OBSOLETE='N'
         * AND DATEDEBUT < '$date_fin_period'
         * AND DATEFIN > '$date_deb_period'
         * ORDER BY DATEDEBUT";
         *
         * $query_aff = mysqli_query ( $dbcon, $sql );
         * $erreur_requete = mysqli_error ($dbcon);
         * if ($erreur_requete != "")
         * echo "SELECT FROM AFFECTATION => $erreur_requete \n";
         *
         * while ( $result = mysqli_fetch_row ( $query_aff ) )
         * {
         * if (is_null ( $result [1] ))
         * $datedebutaff = $date_deb_period;
         * else
         * $datedebutaff = $result [1];
         * // echo "datedebutaff = $datedebutaff date_deb_period=$date_deb_period \n";
         * if ($fonctions->formatdatedb ( $datedebutaff ) < $fonctions->formatdatedb ( $date_deb_period ))
         * $datedebutaff = $date_deb_period;
         *
         * if ($result [2] == '0000-00-00') {
         * $datefinaff = $date_fin_period;
         * // echo "La date de fin est null \n";
         * } else
         * $datefinaff = $result [2];
         * // echo "datefinaff = $datefinaff date_fin_period=$date_fin_period \n";
         * if (($fonctions->formatdatedb ( $datefinaff ) > $fonctions->formatdatedb ( $date_fin_period )) || ($fonctions->formatdatedb ( $datefinaff ) == '00000000'))
         * $datefinaff = $date_fin_period;
         *
         * $quotite = $result [3] / $result [4];
         * $nbre_jour_aff = $fonctions->nbjours_deux_dates ( $datedebutaff, $datefinaff );
         * if ($cas_general == false) {
         * // 2.5j x 12 mois / 365 j = 0,082j de congés
         * if ($result [5] == "0") {
         * $nbr_jour_cont = 0;
         * $nbre_jours_equ_titu = $nbre_jour_aff;
         * } else {
         * $nbr_jour_cont = min ( array (
         * $nbre_jour_aff,
         * $nbre_jours_manquants
         * ) );
         * $nbre_jours_equ_titu = 0;
         * if ($nbr_jour_cont < $nbre_jour_aff)
         * $nbre_jours_equ_titu = $nbre_jour_aff - $nbr_jour_cont;
         * }
         * $solde_agent = $solde_agent + ((((2.5 * 12) / 365) * $nbr_jour_cont) + (($nbr_jrs_offert * $nbre_jours_equ_titu) / $nbre_jour_periode)) * $quotite;
         * echo "Pas dans le cas général pour $agentid ($agentinfo) \n";
         * // echo " nbre_jours_manquants = $nbre_jours_manquants \n";
         * if ($nbre_jours_equ_titu > 0 || $nbre_jours_manquants == $nbre_jour_aff)
         * $nbre_jours_manquants = 0;
         * else if ($nbre_jours_manquants > $nbre_jour_aff)
         * $nbre_jours_manquants -= $nbre_jour_aff;
         * } else
         * $solde_agent = $solde_agent + (($nbr_jrs_offert * $nbre_jour_aff) / $nbre_jour_periode) * $quotite;
         * }
         *
         */

         
/*
        // Au départ l'agent à droit à 0 jours
        $solde_agent = 0;
        $DatePremAff = null;
        $cas_general = true;
        // Nombre de jours où l'agent a travaillé en continu
        $nbre_total_jours = 0;
        
        // La date de la précédente fin d'affectation est mise à null
        $datefinprecedenteaff = null;
        $datefinaff = null;
        echo "###############################################################\n";
        echo "On est sur l'agent : $agentid \n";
        
        // Construction des date de début et de fin de période (typiquement : 01/09/YYYY et 31/08/YYYY+1)
        $date_deb_period = $fonctions->anneeref() . $fonctions->debutperiode();
        $date_fin_period = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        echo "date_deb_period = $date_deb_period   date_fin_period = $date_fin_period \n";
        
        // Calcul du nombre de jours dans la période => Typiquement 365 ou 366 jours.
        $nbre_jour_periode = $fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period);
        echo "nbre_jour_periode = $nbre_jour_periode \n";
        
        // On charge le nombre de jours auquel un agent à droit sur l'année
        $nbr_jrs_offert = $fonctions->liredbconstante("NBJOURS" . substr($date_deb_period, 0, 4));
        echo "nbr_jrs_offert = $nbr_jrs_offert \n";
        
        // On prend toutes les affectations actives d'un agent, dont la date de début est inférieur à la fin de la période
        // Les affectations futures ne sont pas prises en compte dans le calcul du solde
        $sql = "SELECT AFFECTATIONID,DATEDEBUT,DATEFIN,NUMQUOTITE,DENOMQUOTITE,NUMCONTRAT FROM AFFECTATION WHERE HARPEGEID = '$agentid' AND OBSOLETE='N' AND DATEDEBUT < " . ($fonctions->anneeref() + 1) . $fonctions->finperiode() . " ORDER BY DATEDEBUT";
        $query_aff = mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "SELECT FROM AFFECTATION (Full) => $erreur_requete \n";
        
        if (mysqli_num_rows($query_aff) != 0) // On a des d'affectations
        {
            while ($result_aff = mysqli_fetch_row($query_aff)) {
                echo "-----------------------------------------\n";
                
                // Début de l'affectation courante
                $dateDebAff = $result_aff[1];
                echo "dateDebAff = $dateDebAff \n";
                
                // On mémorise la fin de cette affectation précédente avant qu'elle ne soit modifiée pour pouvoir tester la continuité des affectations avec l'affectation courante
                $datefinprecedenteaff = $datefinaff;
                echo "datefinprecedenteaff = $datefinprecedenteaff \n";
                
                // On parse la date de fin pour limiter la fin de la période si la date de fin n'est pas définie ou si elle est au dela de la période
                $datearray = date_parse($fonctions->formatdatedb($result_aff[2]));
                $year = $datearray["year"];
                if (($result_aff[2] == '0000-00-00') or ($fonctions->formatdatedb($result_aff[2]) > ($fonctions->anneeref() + 1) . $fonctions->finperiode())) {
                    echo "La date de fin de l'affectation est " . $result_aff[2] . " ==> On la force à ";
                    $datefinaff = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
                    echo "$datefinaff \n";
                } else
                    $datefinaff = $result_aff[2];
                echo "datefinaff = $datefinaff \n";
                
                // Calcul de la quotité de l'agent sur cette affectation
                $quotite = $result_aff[3] / $result_aff[4];
                echo "quotite = $quotite \n";
                
                // Si c'est la première affectation, on mémorise sa date de début
                if (is_null($DatePremAff)) {
                    $DatePremAff = $result_aff[1];
                    echo "La date de première affectation est nulle => Maintenant elle vaut : $DatePremAff \n";
                }
                
                // Ce n'est pas un contrat ==> On calcule normalement
                if ($result_aff[5] == "0") {
                    echo "L'affectation n'est pas un contrat ==> numcontrat = " . $result_aff[5] . " \n";
                    
                    // // On calcule le nombre de jours dans l'affectation dans le cas ou l'agent est en contrat pérenne puis repasse sur un contrat non pérenne
                    // $nbre_jour_aff = $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                    // echo "nbre_jour_aff = $nbre_jour_aff \n";
                    
                    // Si la date de fin < date debut de la période, on ne s'en occupe pas car dans ce cas, seule les affectations de la période nous interressent
                    if ($fonctions->formatdatedb($datefinaff) < $fonctions->formatdatedb($date_deb_period)) {
                        echo "Fin de l'affectation avant le début de la période ==> On ignore \n";
                        Continue;
                    }
                    
                    // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                    if ($fonctions->formatdatedb($dateDebAff) < $fonctions->formatdatedb($date_deb_period)) {
                        $dateDebAff = $date_deb_period;
                        echo "le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                    }
                    
                    // On calcule le nombre de jours dans l'affectation sur la période
                    $nbre_jour_aff_periode = $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                    echo "nbre_jour_aff_periode = $nbre_jour_aff_periode \n";
                    
                    $solde_agent = $solde_agent + (($nbr_jrs_offert * $nbre_jour_aff_periode) / $nbre_jour_periode) * $quotite;
                    echo "Le solde de l'agent est de : $solde_agent \n";
                }            // On est dans le cas d'un contrat
                else {
                    echo "On est dans le cas d'un contrat \n";
                    if (! is_null($datefinprecedenteaff)) {
                        // Si il y a un trou entre la fin de l'affectation précédente et le début de l'actuelle, on mémorise sa date de début
                        if (date("Y-m-d", strtotime("+1 day", strtotime($datefinprecedenteaff))) != $result_aff[1]) {
                            echo "La date de début de la nouvelle affectation est : " . $result_aff[1] . " \n";
                            echo "La date de fin de la précédente affectation est : $datefinprecedenteaff \n";
                            echo "Date du lendemain de la fin de la précédente affectation est : " . date("Y-m-d", strtotime("+1 day", strtotime($datefinprecedenteaff))) . " \n";
                            $DatePremAff = $result_aff[1];
                            echo "Il y a rupture dans la suite des affectations => On force la date de premiere affectation à $DatePremAff \n";
                        } else {
                            echo "Il y a continuité entre les affectations \n";
                        }
                    }
                    
                    // On calcule le nombre de jour écoulé depuis le début de la première affectation et la date de fin de cette affectation
                    $NbreJoursTotalAff = $fonctions->nbjours_deux_dates($DatePremAff, $datefinaff);
                    echo "L'agent est affecté depuis $NbreJoursTotalAff jours en continue depuis le $DatePremAff jusqu'au $datefinaff... \n";
                    
                    // Si la date de fin < date debut de la période, on ne s'en occupe pas car dans ce cas, seule les affectations de la période nous interressent
                    if ($fonctions->formatdatedb($datefinaff) < $fonctions->formatdatedb($date_deb_period)) {
                        echo "Fin de l'affectation avant le début de la période ==> On ignore \n";
                        Continue;
                    }
                    
                    echo "RAPPEL : Le solde de l'agent actuellement est : $solde_agent \n";
                    // L'agent est présent depuis plus d'un an à la fin de son affectation, donc on va calculer son solde avec les régles standards
                    // Attention cependant, il faut calculer le solde pour la période avant les 365 jours
                    if ($NbreJoursTotalAff > $nbre_jour_periode) {
                        echo "L'agent a plus de 365 jours de présence en continue depuis le $DatePremAff jusqu'au $datefinaff.... \n";
                        
                        // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                        if ($fonctions->formatdatedb($dateDebAff) < $fonctions->formatdatedb($date_deb_period)) {
                            $dateDebAff = $date_deb_period;
                            echo "le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                        }
                        
                        // Calcul du nombre de jours qui doivent être comptés à 2,5 jours
                        $NbreJours = $NbreJoursTotalAff - $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                        echo "A la date de début de l'affectation " . $fonctions->formatdate($dateDebAff) . ", l'agent avait cumulé $NbreJours consécutifs \n";
                        // $NbreJours = $nbre_jour_periode - $NbreJours;
                        // echo "dateDebAff = $dateDebAff datefinaff = $datefinaff dif_date = " . $fonctions->nbjours_deux_dates ($dateDebAff, $datefinaff ) . " NbreJours = $NbreJours \n";
                        // $NbreJours = $fonctions->nbjours_deux_dates ($dateDebAff, $datefinaff ) - $NbreJours;
                        $NbreJours = $fonctions->nbjours_deux_dates($date_deb_period, $date_fin_period) - $NbreJours;
                        if ($NbreJours < 0)
                            $NbreJours = 0;
                        echo "Il y a $NbreJours jours à compter à 2,5 jours par mois soit : " . ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite) . " jours \n";
                        if ($NbreJours > 0) {
                            $solde_agent = $solde_agent + ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite);
                            echo "solde_agent = $solde_agent \n";
                        }
                        
                        // Calcul du nombre de jours qui doivent être comptés comme un "non contrat"
                        // $NbreJours = $nbre_jour_periode - $NbreJours;
                        $NbreJours = $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff) - $NbreJours;
                        if ($NbreJours < 0)
                            $NbreJours = 0;
                        echo "Il y a $NbreJours jours à compter à $nbr_jrs_offert jours par an soit : " . ((($nbr_jrs_offert * $NbreJours) / $nbre_jour_periode) * $quotite) . " jours \n";
                        if ($NbreJours > 0) {
                            $solde_agent = $solde_agent + ((($nbr_jrs_offert * $NbreJours) / $nbre_jour_periode) * $quotite);
                            echo "solde_agent = $solde_agent \n";
                        }
                    }                // Le nombre de jours est < à 365 jours (donc l'agent n'est pas présent depuis plus d'un an)
                    else {
                        echo "L'agent n'a pas atteint les 365 jours consécutifs => On calcule à 2,5 jours par mois \n";
                        // Si le début de l'affectation est avant le début de la période, on la force au début de la période
                        if ($fonctions->formatdatedb($dateDebAff) < $fonctions->formatdatedb($date_deb_period)) {
                            $dateDebAff = $date_deb_period;
                            echo "le début de l'affectation est avant le début de la période, on la force au début de la période => dateDebAff = $dateDebAff \n";
                        }
                        // Calcul du nombre de jours qui doivent être comptés à 2,5 jours sur la période de l'affectation
                        $NbreJours = $fonctions->nbjours_deux_dates($dateDebAff, $datefinaff);
                        $solde_agent = $solde_agent + ((((2.5 * 12) / $nbre_jour_periode) * $NbreJours) * $quotite);
                        echo "solde_agent = $solde_agent \n";
                    }
                }
            }
        }
        if ($solde_agent > 0) {
            $partie_decimale = $solde_agent - floor($solde_agent);
            echo "Code Agent = $agentid ($agentinfo)    solde_agent = $solde_agent     partie_decimale =  $partie_decimale     entiere = " . floor($solde_agent) . "          ";
            if ((float) $partie_decimale < (float) 0.25)
                $solde_agent = floor($solde_agent);
            elseif ((float) ($partie_decimale >= (float) 0.25) && ((float) $partie_decimale < (float) 0.75))
                $solde_agent = floor($solde_agent) + (float) 0.5;
            else
                $solde_agent = floor($solde_agent) + (float) 1;
            
            echo "apres traitement : $solde_agent \n";
        }
        echo "Le solde final est donc : $solde_agent \n";
        
        // On vérifie si une demande de congé bonifié débute dans la période
        $debutperiode = $fonctions->anneeref() . $fonctions->debutperiode();
        $finperiode = ($fonctions->anneeref() + 1) . $fonctions->finperiode();
        $sql = "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE WHERE HARPEGEID='$agentid' AND (HARPTYPE='CONGE_BONIFIE' OR HARPTYPE LIKE 'Cg% Bonifi% (FPS)') AND DATEDEBUT BETWEEN '$debutperiode' AND '$finperiode'";
        $query = mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "SELECT HARPEGEID,DATEDEBUT,DATEFIN FROM HARPABSENCE => $erreur_requete \n";
        if (mysqli_num_rows($query) != 0) // Il existe un congé bonifié pour la période => On le solde des congés à 0
        {
            $resultcongbonif = mysqli_fetch_row($query);
            $solde_agent = 0;
            echo "L'agent $agentid ($agentinfo) a une demande de congés bonifiés (du " . $resultcongbonif[1] . " au " . $resultcongbonif[2] . ") => Solde à 0 \n";
        }
        
        $typeabsenceid = "ann" . substr($fonctions->anneeref(), 2, 2);
        $sql = "SELECT HARPEGEID,TYPEABSENCEID FROM SOLDE WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
        $query = mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "SELECT HARPEGEID,TYPEABSENCEID FROM CONGE => $erreur_requete \n";
        if (mysqli_num_rows($query) != 0) // le type annXX existe déja => On le met à jour
            $sql = "UPDATE SOLDE SET DROITAQUIS='$solde_agent' WHERE HARPEGEID='$agentid' AND TYPEABSENCEID='$typeabsenceid'";
        else
            $sql = "INSERT INTO SOLDE(HARPEGEID,TYPEABSENCEID,DROITAQUIS,DROITPRIS) VALUES('" . $agentid . "','" . $typeabsenceid . "','$solde_agent','0')";
        mysqli_query($dbcon, $sql);
        $erreur_requete = mysqli_error($dbcon);
        if ($erreur_requete != "")
            echo "INSERT ou UPDATE CONGE => $erreur_requete \n";
*/
        $agent = new agent($dbcon);
        $agent->load($agentid);
        echo "###############################################################\n";
        echo "On est sur l'agent : " . $agent->identitecomplete() . " (id = $agentid) \n";
        $solde = $agent->calculsoldeannuel($fonctions->anneeref(),true, false, true); // On calcule le solde de l'année courante + on met à jour le solde en base + on n'ecrit pas les traces d'exécution + on les affiche
        echo "Le solde annuel de l'agent " . $agent->identitecomplete() . " (id = " . $agent->harpegeid() . ") pour l'annee " .  $fonctions->anneeref() . "-" . ($fonctions->anneeref()+1)  . " est de $solde jours.\n";
    }
    echo "Fin du calcul des soldes " . date("d/m/Y H:i:s") . "\n";

?>