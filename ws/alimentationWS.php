<?php
    require_once ('../html/includes/dbconnection.php');
    require_once ('../html/class/fonctions.php');
    require_once ('../html/class/agent.php');
    require_once ('../html/class/structure.php');
    require_once ("../html/class/solde.php");
    require_once ("../html/class/demande.php");
    require_once ("../html/class/planning.php");
    require_once ("../html/class/planningelement.php");
    require_once ("../html/class/declarationTP.php");
    require_once ("../html/class/fpdf/fpdf.php");
    require_once ("../html/class/cet.php");
    require_once ("../html/class/affectation.php");
    require_once ("../html/class/complement.php");
    require_once ("../html/class/periodeobligatoire.php");
    require_once ("../html/class/alimentationCET.php");

    $fonctions = new fonctions($dbcon);
    $errlog = '';
    $erreur = '';

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST': // Modifie le statut d'une demande d'alimentation
            
            $esignatureid = "";
            $status = "";
            $reason = "";
            $statutvalide = array('PREPA' => 'en préparation', 'COURS' => 'en cours', 'REFUS' => 'refusée', 'SIGNE' => 'signée', 'ABAND' => 'abandonnée');
            if (isset($_POST["esignatureid"]))
                $esignatureid = $_POST["esignatureid"];
            if (isset($_POST["status"]))
                $status = $_POST["status"];
            if (isset($_POST["reason"]))
                $reason = $_POST["reason"];
            
            $status = mb_strtolower("$status", 'UTF-8');
            if ("$esignatureid" == "")
            {
                $erreur = "Le paramètre esignature n'est pas renseigné.";
            }
            else if ("$status" == "")
            {
                $erreur = "Le paramètre status n'est pas renseigné.";
            }
            else if (!in_array($status, $statutvalide))
            {
                $erreur = "Statut invalide => $status. Liste des statuts valides => " . implode(", ", $statutvalide);
            }
            else if ((array_search($status,$statutvalide ) == 'REFUS') and ($reason == ""))
            {
                $erreur = "La demande doit passer au statut '$status' mais le motif est vide";
            }
/*            
            if ($status == 'Terminee')
            {
                $cet = new cet($dbcon);
                $cet->load();
                $cet->cumultotal($nbjrs + $cet->cumultotal()) ; 
                //$cet->store;
                
                $solde = new solde($dbcon);
                $solde->load($agentid,'ann' . $anneref);
                $solde->droitpris($solde->droitpris() + $nbjrs);
                //$solde->store;
                
                // Ajouter dans la table des commentaires la trace de l'opération
            }

*/
            if ($erreur == "")
            {
                $alimentationCET = new alimentationCET($dbcon);
                $erreur = $alimentationCET->load($esignatureid);
            }
            if ($erreur == "")
            {
                $alimentationCET->statut($status);
                $alimentationCET->motif($reason);
                $erreur = $alimentationCET->store();
            }
            if ($erreur != "")
            {
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                $result_json = array('status' => 'Ok', 'description' => $erreur);
            }

            break;
        case 'GET': // Retourne les informations liées à une demande d'alimentation
            $esignatureid = $_GET["esignatureid"];
            if ("$esignatureid" == "" )
            {
                $erreur = "Le paramètre esignatureid n'est pas renseigné.";
            }
            else
            {
                $alimentationCET = new alimentationCET($dbcon);
                $erreur = $alimentationCET->load($esignatureid);
            }
            if ($erreur != "")
            {
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            else
            {
                $valeur_a = $alimentationCET->valeur_a();
                $valeur_b = $alimentationCET->valeur_b();
                $valeur_c = $alimentationCET->valeur_c();
                $valeur_d = $alimentationCET->valeur_d();
                $valeur_e = $alimentationCET->valeur_e();
                $valeur_f = $alimentationCET->valeur_f();
                $valeur_g = $alimentationCET->valeur_g();
                $information_A = array('name' => "A", 'description' => "Solde du CET avant versement", 'value' => $valeur_a);
                $information_B = array('name' => "B", 'description' => "Droits à congés (en jours) au titre de l’année de référence", 'value' => $valeur_b);
                $information_C = array('name' => "C", 'description' => "Nombre de jours de congés utilisés au titre de l’année de référence", 'value' => $valeur_c);
                $information_D = array('name' => "D", 'description' => "Solde de jours de congés non pris au titre de l’année de référence", 'value' => $valeur_d);
                $information_E = array('name' => "E", 'description' => "Nombre de jours de congés reportés sur l’année suivante", 'value' => $valeur_e);
                $information_F = array('name' => "F", 'description' => "Alimentation du CET", 'value' => $valeur_f);
                $information_G = array('name' => "G", 'description' => "Solde du CET après versement", 'value' => $valeur_g);
                
                $agent = new agent($dbcon);
                $agent->load($alimentationCET->agentid());
                $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                if (count(array($affectationliste)) > 0)
                {
                    $affectation = current($affectationliste);
                    $structure = new structure($dbcon);
                    $structure->load($affectation->structureid());
                }
                
                $sql = "SELECT ANNEEREF FROM TYPEABSENCE WHERE TYPEABSENCEID = '" .  $alimentationCET->typeconges()  . "'";
                $query = mysqli_query($dbcon, $sql);
                $erreur = mysqli_error($dbcon);
                if ($erreur != "")
                {
                    $errlog = "Problème SQL dans le chargement de l'année de reférence : " . $erreur;
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                }
                elseif (mysqli_num_rows($query) == 0)
                {
                    //echo "<br>load => pas de ligne dans la base de données<br>";
                    $errlog = "Impossible de déterminer l'année de référence pour le type " . $alimentationCET->typeconges();
                    error_log(basename(__FILE__) . " " . $fonctions->stripAccents($errlog));
                }
                else
                {
                    $result = mysqli_fetch_row($query);
                    $anneeref = "Année universitaire " . $result["0"] . "/" . ($result["0"]+1);
                }
                
                
                if ($errlog != "")
                {
                    $result_json = array('status' => 'Error', 'description' => $errlog);
                }
                else
                {
                    $affectationliste = $agent->affectationliste(date('Ymd'), date('Ymd'));
                    if (count(array($affectationliste)) > 0)
                    {
                        $affectation = new affectation($dbcon);
                        $affectation = current($affectationliste);
                        $affectation->quotite();
                        $agent = array('uid' => $agent->harpegeid(),
                            'email' => $agent->mail(),
                            'name' => $agent->nom(),
                            'firstname' => $agent->prenom(),
                            'service' => array('name' => $structure->nomlong(),
                            'id' => $structure->id()),
                            'ref_year' => $anneeref,
                            'activity' => $affectation->quotite()
                            );
                        $result_json = array('agent' => $agent, 'informations' => array($information_A, $information_B, $information_C, $information_D, $information_E, $information_F, $information_G));
                    }
                    else
                    {
                        $result_json = array('status' => 'Error', 'description' => "Impossible de déterminer la quotité de travail de l'agent.");
                    }
                    
                    
                    
                }
            }
            break;
    }
    
   
    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    echo json_encode($result_json);
    
?>
