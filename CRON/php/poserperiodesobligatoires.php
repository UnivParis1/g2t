<?php

    require_once (dirname(__FILE__,3) . "/html/includes/dbconnection.php");
    require_once (dirname(__FILE__,3) . "/html/includes/all_g2t_classes.php");

    $fonctions = new fonctions($dbcon);

    echo "\nDébut de la saisie des congés en période obligatoire " . date("d/m/Y H:i:s") . "\n";

    $cronuser = new agent($dbcon);
    $cronuser->load(SPECIAL_USER_IDCRONUSER);
    $periode = new periodeobligatoire($dbcon);
    $listeperiode = $periode->load($fonctions->anneeref());

    // Si on a des périodes obligatoires déclarées
    if (count((array)$listeperiode)>0)
    {
        $typeabsenceid = 'ann' . substr($fonctions->anneeref(), -2 , 2);
        $typeabsenceanticipeid = 'ann' . (substr($fonctions->anneeref()+1, -2 , 2));
        $listeagent = $fonctions->listeagentsavecaffectation();
        //////////////////////////////////////
        //$listeagent = array('9328' => "Pascal COMTE", "13825" => "YAEL KTORZA");
        //////////////////////////////////////
        foreach($listeagent as $agentid => $identite)
        {
            $logtexte = "###############################################";
            //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
            echo $logtexte . " \n";
            $logtexte = "Traitement de l'agent $agentid ($identite) ";
            echo $logtexte . " \n";

            $agent = new agent($dbcon);
            if (!$agent->load($agentid))
            {
                $logtexte = "Erreur lors du chargement de l'agent => On l'ignore ";
                echo $logtexte . " \n";
                continue;
            }

            foreach((array)$listeperiode as $periode)
            {
                $returndesc = '';
                $agent->forceperiodeobligatoire($periode,false,$returndesc);

                echo $returndesc;

                // //$idperiode = $periode["datedebut"] . '-' . $periode["datefin"];
                // $idperiode = $periode["id"];
                // // Si l'agent n'a pas d'exception pour la période
                // $complement = new complement($dbcon);
                // // -----------------------------------
                // // ATTENTION : Il faut trouver une clé de période pas trop longue !!!
                // // -----------------------------------
                // $complement->load($agentid, "EXCEPT_PER_$idperiode");
                // // Le complément n'existe pas => On doit alors creer la demande pour compléter la période obligatoire
                // if ($complement->agentid() != $agentid)
                // {
                //     $planning = new planning($dbcon);
                //     $planning->load($agentid, $periode["datedebut"], $periode["datefin"], false, true, false);
                //     $listedispo = $planning->listeperiodedispo($agentid, $periode["datedebut"], fonctions::MOMENT_MATIN, $periode["datefin"],fonctions::MOMENT_APRESMIDI,false);
                //     if (count($listedispo)==0)
                //     {
                //         $logtexte = "La période obligatoire du " . $fonctions->formatdate($periode["datedebut"]) . " au " . $fonctions->formatdate($periode["datefin"]) . " est déjà couverte.";
                //         //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //         echo $logtexte . " \n";                
                //     }
                //     else
                //     {
                //         // On passe par un for ($ctp ; $cpt < count ; $cpt++) et non pas un foreach 
                //         // car le tableau listedispo peut être modifié durant le traitement
                //         for ($indexdispo = 0 ; $indexdispo < count($listedispo) ; $indexdispo++)
                //         {
                //             unset ($dispo);
                //             $dispo = $listedispo[$indexdispo];

                //             $logtexte = "=> Traitement de la période du " . $dispo->elementdebut->date() . " " . $fonctions->nommoment($dispo->elementdebut->moment()) . " au " . $dispo->elementfin->date() . " " . $fonctions->nommoment($dispo->elementfin->moment());
                //             //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //             echo $logtexte . " \n";                

                //             // On ne vérifie pas si le solde de congés est suffisant car vérifié dans le store
                //             unset($demande);
                //             $demande = new demande($dbcon);
                //             $demande->agentid($agentid);
                //             $demande->type($typeabsenceid);
                //             $demande->datedebut($dispo->elementdebut->date());
                //             $demande->datefin($dispo->elementfin->date());
                //             $demande->moment_debut($dispo->elementdebut->moment());
                //             $demande->moment_fin($dispo->elementfin->moment());
                //             $demande->commentaire("Période de fermeture obligatoire");
                //             $ignoreabsenceautodecla = false; //// ??? A vérifier 
                //             $ignoresoldeinsuffisant = false;
                //             $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                //             $soldeinsuffisantinfos = $demande->soldeinsuffisantinfos();
                //             if (($resultat . "") != "" and !is_null($soldeinsuffisantinfos))
                //             {
                //                 $logtexte = "Période du " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . " -> " . $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . " : " . strip_tags($resultat);
                //                 //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //                 echo $logtexte . " \n";                

                //                 $solderestant = $soldeinsuffisantinfos->solderestant;
                //                 // S'il n'y a pas de solde restant => Ce n'est pas la peine de réessayer de poser la demande
                //                 if ($solderestant>0)
                //                 {
                //                     // On a une erreur sur solde insuffisant => On peut sauvegarder juste le solde restant
                //                     echo "Le solde " . $demande->typelibelle() . " est insuffisant => On va calculer la date de fin pour $solderestant jours et début " . $dispo->elementdebut->date() . " " . $fonctions->nommoment($dispo->elementdebut->moment()) . " \n";
                //                     // Il faut donc calculer la date de fin de la demande à partir de la date de début et du planning de l'agent
                //                     $resultat = $planning->calculdatefindemande($dispo->elementdebut->date(),$dispo->elementdebut->moment(),$solderestant);

                //                     if (is_object($resultat)) // L'objet est l'élément du planning de fin correspondant à la durée demandée
                //                     {
                //                         $elementfin = $resultat;
                //                         $logtexte = "La date de fin calculée est " . $elementfin->date() . " " . $fonctions->nommoment($elementfin->moment());
                //                         //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //                         echo $logtexte . " \n";                

                //                         // On recrée la demande
                //                         $demande = new demande($dbcon);
                //                         $demande->agentid($agentid);
                //                         $demande->type($typeabsenceid);
                //                         $demande->datedebut($dispo->elementdebut->date());
                //                         $demande->datefin($elementfin->date());
                //                         $demande->moment_debut($dispo->elementdebut->moment());
                //                         $demande->moment_fin($elementfin->moment());
                //                         $demande->commentaire("Période de fermeture obligatoire");
                //                         $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                //                         if (($resultat . "") == "")
                //                         {
                //                             // On a posé une partie de la dispo avec le reste des congés.
                //                             // On doit maintenant déclaré une nouvelle dispo qui va de l'élément suivant dans le planning jusqu'à l'élément de fin de la dispo
                //                             $listeelement = $planning->planning();
                //                             // On récupère l'élément suivant l'élément de fin
                //                             if (isset($listeelement[$elementfin->idelementsuivant()]))
                //                             {
                //                                 // error_log(basename(__FILE__) . " " . $fonctions->stripAccents("indexdispo courante = $indexdispo"));
                //                                 // foreach($listedispo as $tmpdispo)
                //                                 // {
                //                                 //     error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Dispo début = " . $tmpdispo->elementdebut->date() . " " . $tmpdispo->elementdebut->moment() . " -> " . $tmpdispo->elementfin->date() . " " . $tmpdispo->elementfin->moment()));
                //                                 // }

                //                                 // On crée une nouvelle dispo qui démarre à l'élément suivant du planning et qui se fini à la date de fin de la dispo
                //                                 $newdispo = new disponibilite;
                //                                 $newdispo->elementdebut = $listeelement[$elementfin->idelementsuivant()];
                //                                 $newdispo->elementfin = $dispo->elementfin;

                //                                 // On injecte après la dispo courante une nouvelle dispo à partir du 
                //                                 $subtab1 = array_slice ($listedispo, 0, $indexdispo+1);
                //                                 $subtab2 = array_slice ($listedispo, $indexdispo+1);
                //                                 $subtab1[] = $newdispo;
                //                                 $listedispo = array_merge ($subtab1, $subtab2);

                //                                 // On met la fin de la disponibilité qu'on vient de traiter (de manière incomplète car solde insuffisant) à l'élément 
                //                                 // représentant la date de fin qu'on a calculé précédemment.
                //                                 $dispo->elementfin = $elementfin;

                //                                 // error_log(basename(__FILE__) . " " . $fonctions->stripAccents("--------------------------"));
                //                                 // foreach($listedispo as $tmpdispo)
                //                                 // {
                //                                 //     error_log(basename(__FILE__) . " " . $fonctions->stripAccents("Dispo début = " . $tmpdispo->elementdebut->date() . " " . $tmpdispo->elementdebut->moment() . " -> " . $tmpdispo->elementfin->date() . " " . $tmpdispo->elementfin->moment()));
                //                                 // }

                //                             }
                //                         }
                //                     }
                //                     elseif (($resultat . "") != "")
                //                     {
                //                         // On a eu un message d'erreur en retour => Donc il y a eu un problème sur le calcul de la date de fin
                //                         echo "Le calcul de la date de fin a échoué => " . $resultat . " \n";                
                //                     }
                //                 }
                //                 // Le solde restant pour l'année de référence est nul => On doit prendre sur des congés par anticipation
                //                 else
                //                 {
                //                     // On charge le solde de congés anticipé pour vérifier qu'il existe
                //                     $solde = new solde($dbcon);
                //                     $resultat = $solde->load($agentid, $typeabsenceanticipeid);
                //                     if ($resultat != "") 
                //                     {
                //                         // Il n'existe pas => On le crée
                //                         echo "On crée le solde de l'année suivante ($typeabsenceanticipeid) \n";
                //                         $resultat = $solde->creersolde($typeabsenceanticipeid, $agentid);
                //                     }
                //                     if (($resultat . "") == "")
                //                     {
                //                         $demande = new demande($dbcon);
                //                         $demande->agentid($agentid);
                //                         // congés par anticipation
                //                         $demande->type($typeabsenceanticipeid);
                //                         $demande->datedebut($dispo->elementdebut->date());
                //                         $demande->datefin($dispo->elementfin->date());
                //                         $demande->moment_debut($dispo->elementdebut->moment());
                //                         $demande->moment_fin($dispo->elementfin->moment());
                //                         $demande->commentaire("Période de fermeture obligatoire");
                //                         $ignoreabsenceautodecla = false; //// ??? A vérifier 
                //                         $ignoresoldeinsuffisant = true;
                //                         $resultat = $demande->store(NULL, $ignoreabsenceautodecla, $ignoresoldeinsuffisant);
                //                     }
                //                     else
                //                     {
                //                         // La création du solde de l'année suivante ne s'est pas bien passé => On fait remonter l'erreur
                //                         $resultat = "Echec lors de la création du solde annuel $typeabsenceanticipeid => $resultat";
                //                     }
                //                 }
                //             }
                //             if (($resultat . "") == "") 
                //             {
                //                 $demandeid = $demande->id();
                                
                //                 $compldemande = new demandecomplement($dbcon);
                //                 $compldemande->demandeid($demandeid);
                //                 $compldemande->complementid(demandecomplement::PERIODE_OBLIG_AUTOMATIQUE);
                //                 $compldemande->valeur('O');
                //                 $resultat = $compldemande->store();
                //                 //echo "Je viens de store le compldemande => " . $resultat . " \n";
                //                 if (($resultat . "") == "")
                //                 {
                //                     unset($demande);
                //                     $demande = new demande($dbcon);
                //                     $demande->load($demandeid);
                //                     //echo "Je viens de recharger la demande \n";
                //                     $demande->statut(demande::DEMANDE_VALIDE);

                //                     $resultat = $demande->store();

                //                     if (($resultat . "") == "")
                //                     {
                //                         // On génère le PDF correspondant à la demande si tout s'est bien passé
                //                         $pdfname = $demande->pdf($cronuser->agentid());
                //                         $logtexte = "Le fichier $pdfname a été généré.";
                //                         echo $logtexte . " \n";
                //                     }
                //                     //echo "Je viens de changer le statut en validé => " . $resultat . " \n";
                //                 }
                //             }
                //             // Si on a eu un problème lors de la sauvegarde
                //             if (($resultat . "") != "")
                //             {
                //                 $logtexte = "Erreur sur la période du " . $demande->datedebut() . " " . $demande->moment_debut() . " -> " . $demande->datefin() . " " . $demande->moment_fin();
                //                 //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //                 echo $logtexte . " \n";
                //                 $logtexte = "Problème lors de la sauvegarde des congés en période obligatoire : " . $resultat;
                //                 //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //                 echo $logtexte . " \n";
                //                 /// => ??? Mail à la DRH pour signaler qu'on a eu un problème lors de la sauvegarde
                //             }
                //             else
                //             {
                //                 $logtexte = "Tout s'est bien passé => Ajout d'un congé (" . $demande->nbrejrsdemande() . " jours) du " . $demande->datedebut() . " " . $fonctions->nommoment($demande->moment_debut()) . " au " .  $demande->datefin() . " " . $fonctions->nommoment($demande->moment_fin()) . " => type : " . $demande->typelibelle();
                //                 //error_log(basename(__FILE__) . " " . $fonctions->stripAccents($logtexte));
                //                 echo $logtexte . " \n";
                //             }
                //         }
                //         unset ($dispo);
                //     }
                // }
            }
        }
    }

    echo "\nFin de la saisie des congés en période obligatoire " . date("d/m/Y H:i:s") . "\n";


?>