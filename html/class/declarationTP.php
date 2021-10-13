<?php

class declarationTP
{

    private $declarationid = null;

    private $affectationid = null;

    private $tabtpspartiel = null;

    private $datedemande = null;

    private $datedebut = null;

    private $datefin = null;

    private $datestatut = null;

    private $statut = null;

    private $fonctions = null;

    private $dbconnect = null;

    private $agent = null;

    private $ancienfin = null;

    private $anciendebut = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "DeclarationTP->construct : La connexion à la base de données est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($id = null)
    {
        if (is_null($id)) {
            $errlog = "DeclarationTP->Load : l'identifiant de la déclarationTP est NULL";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $sql = "SELECT DECLARATIONID,AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT
FROM DECLARATIONTP
WHERE DECLARATIONID=" . $id;
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "DeclarationTP->Load : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "DeclarationTP->Load : DeclarationTP $id non trouve";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $this->declarationid = "$result[0]";
            $this->affectationid = "$result[1]";
            $this->tabtpspartiel = "$result[2]";
            $this->datedemande = "$result[3]";
            $this->datedebut = "$result[4]";
            $this->datefin = "$result[5]";
            $this->datestatut = "$result[6]";
            $this->statut = "$result[7]";
        }
    }

    function declarationTPid()
    {
        if (is_null($this->declarationid)) {
            $errlog = "DeclarationTP->id : L'Id n'est pas défini !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else
            return $this->declarationid;
    }

    function affectationid($affectationid = null)
    {
        if (is_null($affectationid)) {
            if (is_null($this->affectationid)) {
                $errlog = "DeclarationTP->affectationid : L'Id de l'affectation n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affectationid;
        } else
            $this->affectationid = $affectationid;
    }

    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "DeclarationTP->statut : Le statut n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->statut;
        } else
            $this->statut = $statut;
    }

    // function statutlibelle()
    // {
    // if (is_null($this->declarationid))
    // echo "DeclarationTP->statutlibelle : La déclaration de TP n'est pas enregistrée, donc pas de statut !!! <br>";
    // else
    // {
    // if (strcasecmp($this->statut,'v') == 0)
    // return "Validée";
    // elseif (strcasecmp($this->statut,'r') == 0)
    // return "Refusée";
    // elseif (strcasecmp($this->statut,'a') == 0)
    // return "En attente";
    // else
    // echo "DeclarationTP->statutlibelle : le statut n'est pas connu [statut = $this->statut] !!! <br>";
    // }
    // }
    function datedebut($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datedebut)) {
                $errlog = "DeclarationTP->datedebut : La date de début n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datedebut);
        } else {
            if (is_null($this->anciendebut))
                $this->anciendebut = $this->datedebut;
            $this->datedebut = $this->fonctions->formatdatedb($date);
        }
    }

    function datefin($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datefin)) {
                $errlog = "DeclarationTP->datefin : La date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datefin);
        } else {
            if (is_null($this->ancienfin))
                $this->ancienfin = $this->datefin;
            $this->datefin = $this->fonctions->formatdatedb($date);
        }
    }

    function datedemande($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datedemande)) {
                $errlog = "DeclarationTP->datedemande : La date de la demande n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datedemande);
        } else
            $this->datedemande = $this->fonctions->formatdatedb($date);
    }

    function datestatut($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->datestatut)) {
                $errlog = "DeclarationTP->datestatut : La date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->datestatut);
        } else
            $this->datestatut = $this->fonctions->formatdatedb($date);
    }

    function tabtpspartiel($tableauTP = null)
    {
        if (is_null($tableauTP)) {
            if (is_null($this->tabtpspartiel)) {
                $errlog = "DeclarationTP->tabtpspartiel : Le tableau des temps partiels n'est pas défini (NULL) !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->tabtpspartiel;
        } else {
            $this->tabtpspartiel = $tableauTP;
        }
        // echo "DeclarationTP->initTP : "; print_r($this->tabrtt); echo "<br>";
    }

    function tabtpspartielhtml($pour_modif = false)
    {
        $htmltext = "";
        $htmltext = $htmltext . "<tr class='entete'>";
        $htmltext = $htmltext . "<td></td>";
        for ($indexjrs = 1; $indexjrs < 6; $indexjrs ++) {
            // echo "indexjrs = $indexjrs <br>";
            $htmltext = $htmltext . "<td colspan='2' style='width:50px'>" . $this->fonctions->nomjourparindex($indexjrs) . "</td>";
        }
        $htmltext = $htmltext . "</tr>";
        $checkboxname = null;
        for ($semaine = 0; $semaine < 2; $semaine ++) {
            $htmltext = $htmltext . "<tr class='ligneplanning'><td>Semaine ";
            if ($semaine == 0)
                $htmltext = $htmltext . "paire</td>";
            else
                $htmltext = $htmltext . "impaire</td>";
            
            for ($indexelement = 0; $indexelement < 10; $indexelement ++) {
                unset($element);
                $element = new planningelement($this->dbconnect);
                if ($indexelement % 2 == 0)
                    $element->moment("m");
                else
                    $element->moment("a");
                if ($pour_modif)
                    $checkboxname = $indexelement + ($semaine * 10); // $this->fonctions->nomjourparindex(((int)($indexelement/2))+1) . "_" . $element->moment() . "_" . $semaine;
                if (substr($this->tabtpspartiel(), $indexelement + ($semaine * 10), 1) == 1) {
                    $element->type("tppar");
                    $element->info("Temps partiel");
                } else {
                    $element->type("");
                    $element->info("");
                }
                $htmltext = $htmltext . $element->html(false, $checkboxname);
                unset($element);
            }
            $htmltext = $htmltext . "</tr>";
        }
        return $htmltext;
    }

    function enTP($date = null, $moment = null)
    {
        if (is_null($date) or is_null($moment)) {
            $errlog = "DeclarationTP->enTP : Au moins un des paramètres n'est pas défini (NULL) !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } elseif (is_null($this->tabtpspartiel)) {
            $errlog = "DeclarationTP->enTP : Le tableau des TP n'est pas initialisé !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (strlen($this->tabtpspartiel) < 20) {
            $errlog = "DeclarationTP->enTP : Le tableau ne contient pas le nombre d'élément requis !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        $datedb = $this->fonctions->formatdatedb($date);
        // recupération du numéro du jour ==> 0 dimanche ... 6 Samedi
        $numerojour = date("w", strtotime($datedb));
        if ($numerojour == 0) {
            // On force le dimanche a 7
            $numerojour = 7;
        }
        // echo "Numero jour = $numerojour <br>";
        if ($numerojour >= 6) {
            // echo "Samedi ou Dimanche => Donc pas de TP <br>";
            return false;
        }
        // recupération du numéro de la semaine
        $numsemaine = date("W", strtotime($datedb));
        // echo "Numero de la semaine = $numsemaine <br>";
        $semainepaire = ! (bool) ($numsemaine % 2);
        if ($semainepaire) {
            // echo "Semaine paire <br>";
            $semaineindex = 0;
        } else {
            // echo "Semaine impaire <br>";
            $semaineindex = 1;
        }
        
        if (strcasecmp($moment, "m") == 0)
            $momentindex = 0;
        else
            $momentindex = 1;
        
        $index = (($numerojour - 1) * 2) + ($momentindex) + (10 * $semaineindex);
        // echo "date = $date moment = $moment index = $index this->tabtpspartiel = " . $this->tabtpspartiel . "<br>";
        // echo "Le caractère= " . substr($this->tabtpspartiel, $index,1) . "<br>";
        if (substr($this->tabtpspartiel, $index, 1) == "1") {
            // echo "Je return TRUE <br>";
            return true;
        } else {
            // echo "Je return FALSE <br>";
            return false;
        }
    }

    function agent()
    {
        if (is_null($this->agent)) {
            $sql = "SELECT HARPEGEID FROM AFFECTATION,DECLARATIONTP WHERE DECLARATIONTP.DECLARATIONID='" . $this->declarationTPid() . "'";
            $sql = $sql . " AND DECLARATIONTP.AFFECTATIONID = AFFECTATION.AFFECTATIONID";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Demande->agent : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Demande->agent : Pas d'agent trouvé pour la déclaration de TP " . $this->declarationTPid();
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $agent = new agent($this->dbconnect);
            $agent->load("$result[0]");
            $this->agent = $agent;
        }
        return $this->agent;
    }

    function store()
    {
        
        // echo "DeclarationTP->store : non refaite !!!! <br>";
        // return false;
        
        // echo "On teste le nbre de tabrtt = " . count($this->tabrtt) . "<br>";
        if (strlen($this->tabtpspartiel) != 20) {
            $errlog = "Le tableau des temps partiels n'est pas initialisé. L'enregistrement est impossible.";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return $errlog . "<br/>";
        }
        
        // echo "id est null ==> " . $this->id . "<br>";
        if (is_null($this->declarationid)) {
            $this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));
            
            $sql = "LOCK TABLES DECLARATIONTP WRITE";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 0";
            mysqli_query($this->dbconnect, $sql);
            $sql = "INSERT INTO DECLARATIONTP (AFFECTATIONID,TABTPSPARTIEL,DATEDEMANDE,DATEDEBUT,DATEFIN,DATESTATUT,STATUT) ";
            $sql = $sql . " VALUES ('" . $this->affectationid . "','" . $this->tabtpspartiel . "',";
            $sql = $sql . "'" . $this->datedemande . "','" . $this->fonctions->formatdatedb($this->datedebut) . "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->datedemande . "','" . $this->statut . "')";
            // echo "SQL = $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "")
                echo "DeclarationTP->store : " . $erreur . "<br>";
            // $sql = "SELECT LAST_INSERT_ID()";
            // $this->id = mysqli_query($this->dbconnect, $sql);
            $this->declarationid = mysqli_insert_id($this->dbconnect);
            $sql = "COMMIT";
            mysqli_query($this->dbconnect, $sql);
            $sql = "UNLOCK TABLES";
            mysqli_query($this->dbconnect, $sql);
            $sql = "SET AUTOCOMMIT = 1";
            mysqli_query($this->dbconnect, $sql);
        } else {
            $user = new agent($this->dbconnect);
            if (isset($_SESSION['phpCAS']['harpegeid']))
                $user->load($_SESSION['phpCAS']['harpegeid']);
            else
                $user->load("-1"); // L'utilisateur -1 est l'utilisateur CRON
            $this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
            // c'est une modification ...
            $sql = "UPDATE DECLARATIONTP SET ";
            $sql = $sql . " STATUT='" . $this->statut . "', DATEDEBUT='" . $this->datedebut . "', DATEFIN='" . $this->datefin . "', DATESTATUT='" . $this->datestatut . "' ";
            $sql = $sql . "WHERE DECLARATIONID='" . $this->declarationid . "'";
            // echo "SQL = $sql <br>";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "DeclarationTP->store : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (! is_null($this->anciendebut) or (! is_null($this->ancienfin))) {
                /*
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * /////// ON NE TOUCHE PLUS AUX DEMANDES QUI SONT EN DEHORS DE LA DECLARATION DE TP ///////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * // echo "###############################<br>";
                 * // echo "###### WARNING !!!!! Il faut penser a supprimer les demandes qui ne sont plus dans la période de TP #######<br>";
                 * // echo "########################################<br>";
                 * // echo "#### CA NE MARCHE PAS !!!!!!! A VERIFIER !!!!<br>";
                 * // echo "###############################<br>";
                 * $demandelistefin = null;
                 * $demandelistedebut = null;
                 * //echo "anciendebut " . $this->anciendebut . " datedebut = " . $this->datedebut() . "\n";
                 * if (is_null($this->anciendebut) || (strcasecmp($this->fonctions->formatdate($this->anciendebut),$this->datedebut())==0))
                 * $debut= $this->datedebut();
                 * else
                 * {
                 * $debut = $this->anciendebut;
                 * //echo "debut = " . $this->fonctions->formatdate($debut) . " datedebut = " . $this->datedebut() . "<br>";
                 * $demandelistedebut = $this->demandesliste($this->fonctions->formatdate($debut),$this->datedebut());
                 * }
                 * if (is_null($this->ancienfin) || (strcasecmp($this->fonctions->formatdate($this->ancienfin),$this->datefin())==0))
                 * $fin= $this->datefin();
                 * else
                 * {
                 * $fin = $this->ancienfin;
                 * $timestamp = strtotime($this->fonctions->formatdatedb($this->datefin()));
                 * $nvlledatefin = date("Ymd", strtotime("+1days", $timestamp )); // On passe au jour d'après (donc le lendemain)
                 * //echo "fin = " . $this->fonctions->formatdate($fin) . " datefin = " . $this->datefin() . " nvlledatefin = $nvlledatefin <br>";
                 * $demandelistefin = $this->demandesliste($nvlledatefin,$this->fonctions->formatdate($fin));
                 * }
                 * //echo "debut = " . $this->fonctions->formatdate($debut) . " datedebut = " . $this->datedebut() . "<br>";
                 * //echo "fin = " . $this->fonctions->formatdate($fin) . " datefin = " . $this->datefin() . "<br>";
                 * $demandeliste = array_merge((array)$demandelistedebut,(array)$demandelistefin);
                 * //echo "demandeliste = "; print_r($demandeliste); echo "<br>";
                 * if (is_array($demandeliste))
                 * {
                 * foreach ($demandeliste as $key => $demande)
                 * {
                 * if (strcasecmp($demande->statut(),"r")!=0)
                 * {
                 * $demande->statut("R");
                 * $demande->motifrefus("Modification de la déclaration de temps partiel ou d'affectation - " . $this->datedebut() . "->" . $this->datefin());
                 * $demande->datestatut($this->fonctions->formatdatedb(date("d/m/Y")));
                 * $msg = $demande->store();
                 * if ($msg != "" ) {
                 * $errlog = "STORE de la demande apres modification d'une déclaration TP : " . $msg;
                 * echo $errlog."<br/>";
                 * error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
                 * }
                 * // #############################################################################
                 * // ENVOYER UN MAIL A : L'AGENT + RESPONSABLE DE L'ANCIENNE AFFECTATION + ??????
                 * // #############################################################################
                 * $pdffilename = $demande->pdf($user->harpegeid());
                 * $agent = $demande->agent();
                 * echo "Avant l'envoi du mail à l'agent " . $agent->identitecomplete() . " pour annulation demande (Id=". $demande->id() . ")\n";
                 * //echo "Demande -> Statut = " . $demande->statut() ." \n";
                 * $user->sendmail($agent,"Annulation d'une demande de congés ou d'absence","Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . strtolower($this->fonctions->demandestatutlibelle($demande->statut())) . ".\nLe motif de l'annulation est : " . $demande->motifrefus() . "." , $pdffilename);
                 * unset($agent);
                 * $affectation = new affectation($this->dbconnect);
                 * if (!$affectation->load($this->affectationid))
                 * {
                 * error_log(basename(__FILE__)." Modif de TP => Impossible de charger l'affectation " . $this->affectationid);
                 * continue;
                 * }
                 * //echo "Apres chargement de l'affectation\n";
                 * $structure = new structure($this->dbconnect);
                 * if (!$structure->load($affectation->structureid()))
                 * {
                 * error_log(basename(__FILE__)." Modif de TP => Impossible de charger la structure " . $affectation->structureid() ." dans l'affectation " . $this->affectationid);
                 * continue;
                 * }
                 * //echo "Apres chargement de la structure\n";
                 * $agent = $structure->responsable();
                 * echo "Avant l'envoi du mail au responsable de la structure " . $agent->identitecomplete() . " pour annulation demande (id=". $demande->id() . ")\n";
                 * //echo "Demande -> Statut = " . $demande->statut() ." \n";
                 * $user->sendmail($agent,"Annulation d'une demande de congés ou d'absence","La demande de " . $demande->agent()->identitecomplete() . " du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . strtolower($this->fonctions->demandestatutlibelle($demande->statut())) . ".\nLe motif de l'annulation est : " . $demande->motifrefus() . "." , $pdffilename);
                 * unset($affectation);
                 * unset($structure);
                 * unset($agent);
                 *
                 * error_log("Modif de TP => Sauvegarde la demande " . $demande->id() . " avec le statut " . $this->fonctions->demandestatutlibelle($demande->statut()));
                 * }
                 * }
                 * }
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 */
            }
            if (strcasecmp($this->statut, "r") == 0) {
                /*
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * /////// ON NE TOUCHE PLUS AUX DEMANDES QUI SONT EN DEHORS DE LA DECLARATION DE TP ///////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 *
                 * //echo "###############################<br>";
                 * //echo "###### WARNING !!!!! Il faut penser a supprimer les demandes qui sont associées à cette déclaration de TP #######<br>";
                 * //echo "###############################<br>";
                 * $demandeliste = $this->demandesliste($this->datedebut(),$this->datefin());
                 * if (!is_null($demandeliste))
                 * {
                 * foreach ($demandeliste as $key => $demande)
                 * {
                 * if (strcasecmp($demande->statut(),"r")!=0)
                 * {
                 * $demande->statut("R");
                 * $demande->motifrefus("Annulation de la déclaration de temps partiel - " . $this->datedebut() . "->" . $this->datefin());
                 * $demande->datestatut($this->fonctions->formatdatedb(date("d/m/Y")));
                 * $msg = $demande->store();
                 * if ($msg != "" ) {
                 * $errlog = "STORE de la demande après suppression d'une déclaration TP : " . $msg;
                 * echo $errlog."<br/>";
                 * error_log(basename(__FILE__)." ".$this->fonctions->stripAccents($errlog));
                 * }
                 * // #############################################################################
                 * // ENVOYER UN MAIL A : L'AGENT + RESPONSABLE DE L'ANCIENNE AFFECTATION + ??????
                 * // #############################################################################
                 * $pdffilename = $demande->pdf($user->harpegeid());
                 * $agent = $demande->agent();
                 * echo "Avant l'envoi du mail a l'agent " . $agent->identitecomplete() . " pour annulation demande (Id=". $demande->id() . ")\n";
                 * //echo "Demande -> Statut = " . $demande->statut() ." \n";
                 * $user->sendmail($agent,"Annulation d'une demande de congés ou d'absence","Votre demande du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . strtolower($this->fonctions->demandestatutlibelle($demande->statut())) . ".\nLe motif de l'annulation est : " . $demande->motifrefus() . "." , $pdffilename);
                 * unset($agent);
                 * $affectation = new affectation($this->dbconnect);
                 * if (!$affectation->load($this->affectationid))
                 * {
                 * error_log(basename(__FILE__)." Suppr de TP => Impossible de charger l'affectation " . $this->affectationid);
                 * continue;
                 * }
                 * //echo "Apres chargement de l'affectation\n";
                 * $structure = new structure($this->dbconnect);
                 * if (!$structure->load($affectation->structureid()))
                 * {
                 * error_log(basename(__FILE__)." Suppr de TP => Impossible de charger la structure " . $affectation->structureid() ." dans l'affectation " . $this->affectationid);
                 * continue;
                 * }
                 * //echo "Apres chargement de la structure\n";
                 * $agent = $structure->responsable();
                 * echo "Avant l'envoi du mail au responsable de la structure " . $agent->identitecomplete() . " pour annulation demande (id=". $demande->id() . ")\n";
                 * //echo "Demande -> Statut = " . $demande->statut() ." \n";
                 * $user->sendmail($agent,"Annulation d'une demande de congés ou d'absence","La demande de " . $demande->agent()->identitecomplete() . " du " . $demande->datedebut() . " au " . $demande->datefin() . " est " . strtolower($this->fonctions->demandestatutlibelle($demande->statut())) . ".\nLe motif de l'annulation est : " . $demande->motifrefus() . "." , $pdffilename);
                 * unset($affectation);
                 * unset($structure);
                 * unset($agent);
                 *
                 * error_log(basename(__FILE__)." Suppr de TP => Sauvegarde la demande " . $demande->id() . " avec le statut " . $this->fonctions->demandestatutlibelle($demande->statut()));
                 * }
                 * }
                 *
                 * }
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 * ///////////////////////////////////////////////////////////////////////////////////////////////
                 */
            }
        }
        return "";
    }

    function html($pourmodif = FALSE, $structid = NULL)
    {
        // echo "DeclarationTP->html : non refaite !!!! <br>";
        // return false;
        $htmltext = "";
        $htmltext = $htmltext . "<tr>";
        
        if ($pourmodif)
            $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->agent()->identitecomplete() . "</td>";
        
        // $htmltext = $htmltext . "<input type='hidden' name='" . $structid. "_" . $this->agent()->harpegeid() . "_autodeclaid_" . $this->declarationTPid() . "' value='" . $this->declarationTPid() ."'>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedemande() . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >" . $this->datedebut() . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
        if ($this->fonctions->formatdatedb($this->datefin()) >= date("Ymd")) // Si la date de fin est postérieur à aujourd'hui
            $htmltext = $htmltext . $this->datefin();
        else // Si la date est inférieur à aujourd'hui, on modifie la tipographie (couleur, gras....)
            $htmltext = $htmltext . "<B><FONT COLOR='#FF0000'>" . $this->datefin() . "</FONT></B>";
        $htmltext = $htmltext . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
        if ($pourmodif and strcasecmp($this->statut(), "a") == 0) {
            // Affichager les selections !!!!
            $htmltext = $htmltext . "<select name='statut[" . $this->declarationTPid() . "]'>";
            $htmltext = $htmltext . "<option value='a'";
            if (strcasecmp($this->statut(), "a") == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle('a') . "</option>";
            $htmltext = $htmltext . "<option value='v'";
            if (strcasecmp($this->statut(), "v") == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . ">" . $this->fonctions->declarationTPstatutlibelle('v') . "</option>";
            $htmltext = $htmltext . "<option value='r";
            if (strcasecmp($this->statut(), "r") == 0)
                $htmltext = $htmltext . " selected ";
            $htmltext = $htmltext . "'>" . $this->fonctions->declarationTPstatutlibelle('r') . "</option>";
            $htmltext = $htmltext . "</select>";
        } else {
            $htmltext = $htmltext . $this->fonctions->declarationTPstatutlibelle($this->statut());
            // switch ($this->statut())
            // {
            // case "v":
            // $htmltext = $htmltext . "Validé";
            // break;
            // case "a":
            // $htmltext = $htmltext . "En attente";
            // break;
            // case "r":
            // $htmltext = $htmltext . "Refusé";
            // break;
            // }
        }
        $htmltext = $htmltext . "</td>";
        $htmltext = $htmltext . "<td class='cellulesimple'>";
        
        $elementliste = array(
            10
        );
        
        // echo "Le tableau des TP = " . $this->tabtpspartiel . "<br>";
        $htmltext = $htmltext . "<div id='planning'>";
        $htmltext = $htmltext . "<table class='tableau'>";
        $htmltext = $htmltext . $this->tabtpspartielhtml();
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "</div>";
        
        $htmltext = $htmltext . "</td>";
        /*
         * if ($pourmodif)
         * {
         * $htmltext = $htmltext . "<td class='cellulesimple' align=center >";
         * $htmltext = $htmltext . "<input type='checkbox' name='declaannule[" . $this->declarationTPid() ."]' value='1'>";
         * $htmltext = $htmltext . "</td>";
         * }
         */
        $htmltext = $htmltext . "</tr>";
        return $htmltext;
    }

    function pdf($valideurid)
    {
        // echo "DeclarationTP->pdf : non refaite !!!! <br>";
        // return false;
        
        // echo "Avant le new <br>";
        $pdf = new FPDF();
        //$pdf = new TCPDF();
        //$pdf->SetHeaderData('', 0, '', '', array(
        //    0,
        //    0,
        //    0
        //), array(
        //    255,
        //    255,
        //    255
        //));
        // echo "Avant le define <br>";
        //if (!defined('FPDF_FONTPATH'))
        //    define('FPDF_FONTPATH','font/');
        //$pdf->Open();
        $pdf->AddPage();
        $pdf->Image('../html/images/logo_papeterie.png', 70, 25, 60, 20);
        // echo "Apres l'image... <br>";
        $pdf->SetFont('helvetica', 'B', 14, '', true);
        $pdf->Ln(50);
        // $pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
        $pdf->Ln(10);
        $pdf->Cell(60, 10, utf8_decode('Demande de temps partiel N°' . $this->declarationTPid() . ' de ' . $this->agent()->identitecomplete()));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 10, '', true);
        // echo "Avant le test statut <br>";
        $decision = mb_strtolower($this->fonctions->declarationTPstatutlibelle($this->statut()),'UTF-8');
        // if($this->statut()=='v')
        // $decision='validée';
        // else
        // $decision='refusée';
        
        $pdf->Cell(40, 10, utf8_decode("La demande de temps partiel que vous avez déposée le " . $this->datedemande() . ' a été ' . $decision . ' le ' . $this->datestatut()));
        $pdf->Ln(10);
        // echo "Avant test quotité <br>";
        $pdf->Cell(60, 10, utf8_decode('Récapitulatif de votre demande de temps partiel pour la période du ' . $this->datedebut() . ' au ' . $this->datefin() . '.'));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        
        $cellheight = 5;
        $pdf->Cell(20, $cellheight, utf8_decode(''), 1, 0, 'L', false);
        // On affiche les 5 jours de la semaine
        for ($cpt = 1; $cpt < 6; $cpt ++) {
            $pdf->Cell(20, $cellheight, utf8_decode($this->fonctions->nomjourparindex($cpt)), 1, 0, 'C', false);
        }
        $element = new planningelement($this->dbconnect);
        $element->type("tppar");
        $webcolor = $element->couleur();
        $rgbarray = $this->fonctions->html2rgb($webcolor);
        $pdf->SetFillColor($rgbarray[0], $rgbarray[1], $rgbarray[2]);
        
        $pdf->Ln();
        $pdf->Cell(20, $cellheight, utf8_decode('Semaine paire'), 1, 0, 'L', false);
        for ($cpt = 0; $cpt < 10; $cpt ++) {
            if ($this->tabtpspartiel[$cpt] == 1)
                $fillcel = true;
            else
                $fillcel = false;
            
            if ($cpt % 2 == 0)
                $pdf->Cell(10, $cellheight, utf8_decode(''), 'LTB', 0, 'C', $fillcel);
            else
                $pdf->Cell(10, $cellheight, utf8_decode(''), 'RTB', 0, 'C', $fillcel);
        }
        $pdf->Ln();
        $pdf->Cell(20, $cellheight, utf8_decode('Semaine impaire'), 1, 0, 'L', false);
        for ($cpt = 10; $cpt < 20; $cpt ++) {
            if ($this->tabtpspartiel[$cpt] == 1)
                $fillcel = true;
            else
                $fillcel = false;
            
            if ($cpt % 2 == 0)
                $pdf->Cell(10, $cellheight, utf8_decode(''), 'LTB', 0, 'C', $fillcel);
            else
                $pdf->Cell(10, $cellheight, utf8_decode(''), 'RTB', 0, 'C', $fillcel);
        }
        
        $pdf->Ln(15);
        // $pdf->Cell(25,5,'TP:Demi-journée non travaillée pour un temps partiel WE:Week end');
        $pdf->Ln(10);
        
        $pdfname = $this->fonctions->g2tbasepath() . '/html/pdf/' . date('Y-m') . '/declarationTP_num' . $this->declarationTPid() . '_' . date("YmdHis") . '.pdf';
        // $pdfname = './pdf/declarationTP_num'.$this->declarationTPid().'.pdf';
        // $pdfname = sys_get_temp_dir() . '/autodeclaration_num'.$this->id().'.pdf';
        // echo "Nom du PDF = " . $pdfname . "<br>";
        //$pdf->Output($pdfname, 'F');
        $this->fonctions->savepdf($pdf, $pdfname);
        return $pdfname;
    }

    function demandesliste($debut_interval, $fin_interval)
    {
        $debut_interval = $this->fonctions->formatdatedb($debut_interval);
        $fin_interval = $this->fonctions->formatdatedb($fin_interval);
        $demande_liste = null;
        
        $sql = "SELECT DISTINCT DEMANDE.DEMANDEID FROM DEMANDEDECLARATIONTP,DEMANDE WHERE DEMANDEDECLARATIONTP.DECLARATIONID = '" . $this->declarationid . "'
  				 AND DEMANDEDECLARATIONTP.DEMANDEID = DEMANDE.DEMANDEID
		       AND ((DATEDEBUT <= '" . $this->fonctions->formatdatedb($debut_interval) . "' AND DATEFIN >='" . $this->fonctions->formatdatedb($debut_interval) . "')
					OR (DATEFIN >= '" . $this->fonctions->formatdatedb($fin_interval) . "' AND DATEDEBUT <='" . $this->fonctions->formatdatedb($fin_interval) . "')
					OR (DATEDEBUT >= '" . $this->fonctions->formatdatedb($debut_interval) . "' AND DATEFIN <= '" . $this->fonctions->formatdatedb($fin_interval) . "'))
		ORDER BY DATEDEBUT";
        // echo "declarationTP->demandeliste SQL = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "DeclarationTP->demandesliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            // echo "declarationTP->demandesliste : Il n'y a pas de demande de congé/absence pour ce TP " . $this->declarationid . "<br>";
        }
        while ($result = mysqli_fetch_row($query)) {
            $demande = new demande($this->dbconnect);
            // echo "Agent->demandesliste : Avant le load " . $result[0] . "<br>";
            $demande->load("$result[0]");
            // echo "Agent->demandesliste : Apres le load <br>";
            $demande_liste[$demande->id()] = $demande;
            unset($demande);
        }
        // echo "declarationTP->demandesliste : demande_liste = "; print_r($demande_liste); echo "<br>";
        return $demande_liste;
    }
}

?>