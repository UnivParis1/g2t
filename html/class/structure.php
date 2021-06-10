<?php

class structure
{

    private $dbconnect = null;

    private $structureid = null;

    private $nomlong = null;

    private $nomcourt = null;

    private $parentid = null;

    private $responsableid = null;

    private $gestionnaireid = null;

    private $affichesousstruct = null;
 // permet d'afficher les agents des sous structures
    private $affichetoutagent = null;
 // permet d'afficher le planning de la structure pour tous les agents de la stucture
    private $afficherespsousstruct = null;
 // permet d'afficher le planning des responsables des sous structures
    private $respvalidsousstruct = null;
 // permet au responsable de la structure de valider les demandes des agents d'une structure fille
    private $gestvalidagent = null;
 // autorise le gestionnaire à valider les demandes des congés des agents
    private $datecloture = null;

    private $delegueid = null;

    private $fonctions = null;
    
    private $typestruct = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "Structure->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($structureid)
    {
        if (is_null($this->structureid)) {
            $sql = "SELECT STRUCTUREID,NOMLONG,NOMCOURT,STRUCTUREIDPARENT,RESPONSABLEID,GESTIONNAIREID,AFFICHESOUSSTRUCT,AFFICHEPLANNINGTOUTAGENT,DATECLOTURE,AFFICHERESPSOUSSTRUCT,RESPVALIDSOUSSTRUCT,GESTVALIDAGENT, TYPESTRUCT FROM STRUCTURE WHERE STRUCTUREID='" . $structureid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->Load (STRUCTURE) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->Load (STRUCTURE) : Structure $structureid non trouvé <br>";
                $this->nomcourt = "$structureid";
                $this->nomlong = "Structure inconnue";
                return false;
            }
            $result = mysqli_fetch_row($query);
            $this->structureid = "$result[0]";
            $this->nomlong = "$result[1]";
            $this->nomcourt = "$result[2]";
            $this->parentid = "$result[3]";
            $this->responsableid = "$result[4]";
            $this->gestionnaireid = "$result[5]";
            $this->affichesousstruct = "$result[6]";
            $this->affichetoutagent = "$result[7]";
            if (trim($result[8]) != '')
                $this->datecloture = "$result[8]";
            else // En théorie on ne doit jamais passer par là, car la date de cloture est forcée lors de l'import....
                $this->datecloture = '31/12/9999';
            $this->afficherespsousstruct = "$result[9]";
            $this->respvalidsousstruct = "$result[10]";
            $this->gestvalidagent = "$result[11]";
            $this->typestruct = "$result[12]";
            
            // Prise en compte du cas de la délégation
            $sql = "SELECT IDDELEG,DATEDEBUTDELEG,DATEFINDELEG FROM STRUCTURE WHERE STRUCTUREID='" . $structureid . "' AND CURDATE() BETWEEN DATEDEBUTDELEG AND DATEFINDELEG ";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->Load (STRUCTURE DELEGUE) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) != 0) {
                $result = mysqli_fetch_row($query);
                if ("$result[0]" != "") {
                    $this->delegueid = "$result[0]";
                }
            }
        }
        return true;
    }

    function id()
    {
        return $this->structureid;
    }

    function nomlong($name = null)
    {
        if (is_null($name)) {
            if (is_null($this->nomlong)) {
                $errlog = "Structure->nomlong : Le nom de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->nomlong;
        } else
            $this->nomlong = $name;
    }

    function nomcourt($name = null)
    {
        if (is_null($name)) {
            if (is_null($this->nomcourt)) {
                $errlog = "Structure->nomcourt : Le nom de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->nomcourt;
        } else
            $this->nomcourt = $name;
    }
    
    function nomcompletcet()
    {
    	$type_struct_final = array('UO', 'DIR');
    	$nameStructComplete = $this->nomcourt();
    	$struct_tmp = $this;
    	while (! is_null($struct_tmp) && ! in_array($struct_tmp->typestruct(), $type_struct_final))
    	{
    		$struct_tmp = $struct_tmp->parentstructure();
    		if (! is_null($struct_tmp))
    			$nameStructComplete =  $struct_tmp->nomcourt().' / '.$nameStructComplete;
    	}
    	return $nameStructComplete;
    }
    
    function typestruct()
    {
    	return $this->typestruct;
    }

    function affichetoutagent($affiche = null)
    {
        if (is_null($affiche)) {
            if (is_null($this->affichetoutagent)) {
                $errlog = "Structure->affichetoutagent : Le paramètre affichetoutagent de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affichetoutagent;
        } else
            $this->affichetoutagent = $affiche;
    }

    function respvalidsousstruct($valide = null)
    {
        if (is_null($valide)) {
            if (is_null($this->respvalidsousstruct)) {
                $errlog = "Structure->respvalidsousstruct : Le paramètre respvalidsousstruct de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->respvalidsousstruct;
        } else
            $this->respvalidsousstruct = $valide;
    }

    function gestvalidagent($valide = null)
    {
        if (is_null($valide)) {
            if (is_null($this->gestvalidagent)) {
                $errlog = "Structure->gestvalidagent : Le paramètre gestvalidagent de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->gestvalidagent;
        } else
            $this->gestvalidagent = $valide;
    }

    function sousstructure($sousstruct = null)
    {
        if (is_null($sousstruct)) {
            if (is_null($this->affichesousstruct)) {
                $errlog = "Structure->sousstructure : Le paramètre sousstructure de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->affichesousstruct;
        } else
            $this->affichesousstruct = $sousstruct;
    }

    function afficherespsousstruct($respsousstruct = null)
    {
        if (is_null($respsousstruct)) {
            if (is_null($this->afficherespsousstruct)) {
                $errlog = "Structure->afficherespsousstruct : Le paramètre afficherespsousstruct de la structure n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->afficherespsousstruct;
        } else
            $this->afficherespsousstruct = $respsousstruct;
    }

    function structurefille()
    {
        $structureliste = null;
        if (! is_null($this->structureid)) {
            $sql = "SELECT STRUCTUREID FROM STRUCTURE WHERE STRUCTUREIDPARENT='" . $this->structureid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->structurefille : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                // echo "Structure->structurefille : La structure $this->structureid n'a pas de structure fille<br>";
            }
            while ($result = mysqli_fetch_row($query)) {
                $structure = new structure($this->dbconnect);
                $structure->load("$result[0]");
                $structureliste[$structure->id()] = $structure;
                unset($structure);
            }
            return $structureliste;
        }
    }

    function agentlist($datedebut, $datefin, $sousstrucuture = null)
    {
        $agentliste = null;
        if ((strcasecmp($this->affichesousstruct, 'o') == 0 and strcasecmp($sousstrucuture, 'n') != 0) or (strcasecmp($sousstrucuture, 'o') == 0)) {
            $structliste = $this->structurefille();
            if (! is_null($structliste)) {
                foreach ($structliste as $key => $structure) {
                    if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                        $agentliste = array_merge((array) $agentliste, (array) $structure->agentlist($datedebut, $datefin, 'o'));
                    }
                }
            }
        }
        
        // echo "Liste finale des agents : <br>"; print_r($agentliste); echo "<br>";
        
        // $sql = "SELECT SUBREQ.HARPEGEID FROM ((SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID = '" . $this->structureid . "' AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'<=DATEFIN OR DATEFIN='0000-00-00'))";
        // $sql = $sql . " UNION ";
        // $sql = $sql . "(SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND '" . $this->fonctions->formatdatedb($datefin) . "'>=DATEDEBUT)";
        // $sql = $sql . " UNION ";
        // $sql = $sql . "(SELECT HARPEGEID,OBSOLETE FROM AFFECTATION WHERE STRUCTUREID='" . $this->structureid . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "' AND ('" . $this->fonctions->formatdatedb($datefin) . "'>=DATEFIN OR DATEFIN='0000-00-00'))) AS SUBREQ";
        // $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";
        
        $sql = "SELECT SUBREQ.HARPEGEID FROM ((SELECT AFFECTATION.HARPEGEID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID = '" . $this->structureid . "' AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND (DATEFIN>='" . $this->fonctions->formatdatedb($datefin) . "' OR DATEFIN='0000-00-00'))";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.HARPEGEID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID='" . $this->structureid . "' AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID AND DATEDEBUT>='" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN<='" . $this->fonctions->formatdatedb($datefin) . "')";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.HARPEGEID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID='" . $this->structureid . "' AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datedebut) . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datedebut) . "')";
        $sql = $sql . " UNION ";
        $sql = $sql . "(SELECT AFFECTATION.HARPEGEID,OBSOLETE FROM AFFECTATION,AGENT WHERE AGENT.STRUCTUREID='" . $this->structureid . "' AND AGENT.HARPEGEID = AFFECTATION.HARPEGEID AND DATEDEBUT<='" . $this->fonctions->formatdatedb($datefin) . "' AND DATEFIN>='" . $this->fonctions->formatdatedb($datefin) . "')";
        $sql = $sql . ") AS SUBREQ";
        $sql = $sql . " WHERE SUBREQ.OBSOLETE = 'N'";
        
        // echo "Structure->agentlist : SQL (agentlist) = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->agentlist : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        // echo "Avant le while...<br>";
        while ($result = mysqli_fetch_row($query)) {
            $agent = new agent($this->dbconnect);
            // echo "Apres le new et avant le load =" . $result[0] . "<br>";
            if ($agent->load("$result[0]")) {
                // echo "Apres le load...<br>";
                // La clé est NOM + PRENOM + HARPEGEID => permet de trier les tableaux par ordre alphabétique
                $agentliste[$agent->nom() . " " . $agent->prenom() . " " . $agent->harpegeid()] = $agent;
                // / $agentliste[$agent->harpegeid()] = $agent;
                // echo "Apres la mise dans le tableau <br>";
                unset($agent);
            }
        }
        // echo "<br>agentliste = "; print_r((array)$agentliste); echo "<br>";
        if (is_array($agentliste))
            ksort($agentliste);
        
        if (count((array) $agentliste) == 0) {
            // echo "Structure->agentlist : La structure $this->nomcourt (Identifiant $this->structureid) n'a pas d'agent<br>";
            $errlog = "La structure $this->nomcourt (Identifiant $this->structureid) n'a pas d'agent";
            // echo $errlog."<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        // echo "<br>agentliste = "; print_r((array)$agentliste); echo "<br>";
        return $agentliste;
    }

    function resp_envoyer_a(&$codeinterne = NULL, $update = false)
    {
        // La fonction retourne le code de l'agent à qui envoyer le mail
        // Le paramètre $codeinterne retourne les valeurs 1,2,3... => Nécessaire pour initialisation de la liste
        // dans les pages gestion_structure.php par exemple si $update=false
        // Si $update = true => On met à jour la valeur du champs
        if ($update == true) {
            if (is_null($codeinterne)) {
                $errlog = "Structure->resp_envoyer_a : Le codeinterne est NULL";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $sql = "UPDATE STRUCTURE SET DEST_MAIL_RESPONSABLE='" . $codeinterne . "' WHERE STRUCTUREID = '" . $this->structureid . "'";
                $query = mysqli_query($this->dbconnect, $sql);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Structure->resp_envoyer_a (UPDATE) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            // echo "Structure->resp_envoyer_a (SELECT) : Avant le select DEST_MAIL_RESPONSABLE <br>";
            $sql = "SELECT DEST_MAIL_RESPONSABLE FROM STRUCTURE WHERE STRUCTUREID = '" . $this->structureid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->resp_envoyer_a (SELECT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $codeinterne = $result[0];
            // echo "codeinterne = $codeinterne <br>";
            switch ($codeinterne) {
                case 2: // Envoi au gestionnaire du service parent
                    $parentstruct = $this->parentstructure();
                    if (! is_null($parentstruct))
                        return $parentstruct->gestionnaire();
                    break;
                case 3: // Envoi au gestionnaire du service courant
                    return $this->gestionnaire();
                    break;
                default: // $codeinterne = 1 ou $codeinterne non initialisé
                    $codeinterne = 1; // Envoi au responsable du service parent
                    $parentstruct = $this->parentstructure();
                    if (! is_null($parentstruct))
                        return $parentstruct->responsable();
            }
        }
    }

    function agent_envoyer_a(&$codeinterne = NULL, $update = false)
    {
        // La fonction retourne le code de l'agent à qui envoyer le mail
        // Le paramètre $codeinterne retourne les valeurs 1,2,3... => Nécessaire pour initialisation de la liste
        // dans les pages gestion_structure.php par exemple si $update=false
        // Si $update = true => On met à jour la valeur du champs
        if ($update == true) {
            if (is_null($codeinterne)) {
                $errlog = "Structure->agent_envoyer_a : Le codeinterne est NULL";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $sql = "UPDATE STRUCTURE SET DEST_MAIL_AGENT='" . $codeinterne . "' WHERE STRUCTUREID = '" . $this->structureid . "'";
                $query = mysqli_query($this->dbconnect, $sql);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Structure->agent_envoyer_a (UPDATE) : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            $sql = "SELECT DEST_MAIL_AGENT FROM STRUCTURE WHERE STRUCTUREID = '" . $this->structureid . "'";
            $query = mysqli_query($this->dbconnect, $sql);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Structure->agent_envoyer_a (SELECT) : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $codeinterne = $result[0];
            switch ($codeinterne) {
                case 2: // Envoi au gestionnaire du service courant
                    return $this->gestionnaire();
                    break;
                default: // $codeinterne = 1 ou $codeinterne non initialisé
                    $codeinterne = 1; // Envoi au responsable du service courant
                    return $this->responsable();
            }
        }
    }

    function parentstructure()
    {
        $parentstruct = null;
        if (is_null($this->parentid)) {
            $errlog = "Structure->parentstructure : La structure parente n'est pas définie !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $parentstruct = new structure($this->dbconnect);
            if (! $parentstruct->load("$this->parentid")) {
                // Si on ne peut pas charger la structure parente => On retourne null
                $parentstruct = null;
            }
        }
        return $parentstruct;
    }

    function datecloture()
    {
        return $this->fonctions->formatdate($this->datecloture);
    }

    function responsablesiham()
    {
        if (is_null($this->responsableid) or ($this->responsableid == '')) {
            $errlog = "<B><FONT COLOR='#FF0000'>Structure->Responsablesiham : Le responsable de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini !!! </FONT></B>";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $responsable = new agent($this->dbconnect);
            if ($responsable->load("$this->responsableid")) {
                return $responsable;
            } else {
                $responsable->civilite('');
                $responsable->nom('INCONNU');
                $responsable->prenom('INCONNU');
                return $responsable;
            }
        }
    }

    function responsable($respid = null)
    {
        if (is_null($respid)) {
            if (is_null($this->responsableid) or ($this->responsableid == '')) {
                $errlog = "<B><FONT COLOR='#FF0000'>Structure->Responsable : Le responsable de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini !!! </FONT></B>";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $responsable = new agent($this->dbconnect);
                // Si le délégué est renseigné ==> On retourne le délégué comme responsable
                if (! is_null($this->delegueid) and ($this->delegueid != "")) {
                    if ($responsable->load("$this->delegueid")) {
                        return $responsable;
                    } else {
                        $responsable->civilite('');
                        $responsable->nom('INCONNU');
                        $responsable->prenom('INCONNU');
                        return $responsable;
                    }
                }                // Sinon c'est le responsable SIHAM qu'il faut retourner
                else {
                    return $this->responsablesiham();
                }
            }
        } else
            $this->responsableid = $respid;
    }

    function gestionnaire($gestid = null)
    {
        if (is_null($gestid)) {
            if (is_null($this->gestionnaireid) or ($this->gestionnaireid == '')) {
                $errlog = "<br><B><FONT COLOR='#FF0000'>Structure->Gestionnaire : Le gestionnaire de la structure $this->nomcourt (Identifiant $this->structureid) n'est pas défini !!! </FONT></B>";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                $gestionnaire = new agent($this->dbconnect);
                // echo "Structure->gestionnaire : XXX" . $this->gestionnaireid . "XXXX <br>";
                if ($gestionnaire->load("$this->gestionnaireid")) {
                    // echo "Structure->gestionnaire : Apres le load XXX" . $this->gestionnaireid . "XXXX <br>";
                    return $gestionnaire;
                } else {
                    $gestionnaire->civilite('');
                    $gestionnaire->nom('INCONNU');
                    $gestionnaire->prenom('INCONNU');
                    return $gestionnaire;
                }
            }
        } else
            $this->gestionnaireid = $gestid;
    }

    function planning($mois_annee_debut, $mois_annee_fin, $showsousstruct = null)
    {
        $planningservice = null;
        if (is_null($mois_annee_debut) or is_null($mois_annee_fin)) {
            $errlog = "Structure->planning : Au moins un des paramètres est non defini (null)";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        
        $fulldatedebut = "01/" . $mois_annee_debut;
        $tempfulldatefindb = $this->fonctions->formatdatedb("01/" . $mois_annee_fin);
        $timestampfin = strtotime($tempfulldatefindb);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("Ym", strtotime("+1month", $timestampfin)) . "01";
        // echo "fulldatefin = $fulldatefin <br>";
        $timestampfin = strtotime($fulldatefin);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("d/m/Y", strtotime("-1day", $timestampfin));
        // echo "fulldatefin (en lisible)= $fulldatefin <br>";
        
        $listeagent = $this->agentlist($fulldatedebut, $fulldatefin, $showsousstruct);
        if (is_array($listeagent)) {
            foreach ($listeagent as $key => $agent) {
                // echo "structure -> planning : Interval du planning a charger pour l'agent : " . $agent->nom() . " " . $agent->prenom() ." = " . $fulldatedebut . " --> " . $fulldatefin . "<br>";
                $planningservice[$agent->harpegeid()] = $agent->planning($fulldatedebut, $fulldatefin);
                // echo "structure -> planning : Apres planning de ". $agent->nom() . " " . $agent->prenom() . "<br>";
            }
        }
        return $planningservice;
    }

    function planninghtml($mois_annee_debut, $showsousstruct = null, $noiretblanc = false) // Le format doit être MM/YYYY
    {
        // echo "Je debute planninghtml <br>";
        //list ($jour, $indexmois, $annee) = split('[/.-]', '01/' . $mois_annee_debut);
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        if (($annee . $indexmois <= date('Ym')) and ($noiretblanc == true)) {
            echo "<br><B><font SIZE='3pt' color=#FF0000>Attention : Les informations antérieures à la date du jour ont été masquées.</font></B><br>";
            // echo "<font color=#FF0000></font><br>";
        }
        
        $planningservice = $this->planning($mois_annee_debut, $mois_annee_debut, $showsousstruct);
        
        if (! is_array($planningservice)) {
            return ""; // Si aucun élément du planning => On retourne vide
        }
        // echo "Apres le chargement du planning du service <br>";
        $htmltext = "";
        $htmltext = $htmltext . "<div id='structplanning'>";
        $htmltext = $htmltext . "<table class='tableau'>";
        
        $titre_a_ajouter = TRUE;
        foreach ($planningservice as $agentid => $planning) {
            if ($titre_a_ajouter) {
                $htmltext = $htmltext . "<tr class='entete_mois'><td class='titresimple' colspan=" . (count($planningservice[$agentid]->planning()) + 1) . " align=center ><font color=#BF3021>Gestion des dossiers pour la structure " . $this->nomlong() . " (" . $this->nomcourt() . ")</font></td></tr>";
                $monthname = $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . date("Y", strtotime($this->fonctions->formatdatedb("01/" . $mois_annee_debut)));
                // echo "Nom du mois = " . $monthname . "<br>";
                $htmltext = $htmltext . "<tr class='entete_mois'><td colspan='" . (count($planningservice[$agentid]->planning()) + 1) . "'>" . $monthname . "</td></tr>";
                // echo "Nbre de jour = " . count($planningservice[$agentid]->planning()) . "<br>";
                $htmltext = $htmltext . "<tr class='entete'><td>Agent</td>";
                for ($indexjrs = 0; $indexjrs < (count($planningservice[$agentid]->planning()) / 2); $indexjrs ++) {
                    // echo "indexjrs = $indexjrs <br>";
                    $nomjour = $this->fonctions->nomjour(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut);
                    $titre = $nomjour . " " . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . " " . $monthname;
                    $htmltext = $htmltext . "<td colspan='2' title='" . $titre . "'";
                    // echo "Date case = " . $this->fonctions->formatdatedb(str_pad(($indexjrs + 1),2,"0",STR_PAD_LEFT) . "/" . $mois_annee_debut) . " Date jour = " . date("Ymd") . "<br>";
                    if ($this->fonctions->formatdatedb(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut) == date("Ymd")) {
                        $htmltext = $htmltext . " bgcolor='#3FC6FF'";
                    }
                    $htmltext = $htmltext . ">" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</td>";
                }
                $htmltext = $htmltext . "</tr>";
                $titre_a_ajouter = FALSE;
            }
            
            // echo "Je charge l'agent $agentid <br>";
            $agent = new agent($this->dbconnect);
            $agent->load($agentid);
            // echo "l'agent $agentid est chargé ... <br>";
            $htmltext = $htmltext . "<tr class='ligneplanning'>";
            $htmltext = $htmltext . "<td>" . $agent->nom() . " " . $agent->prenom() . "</td>";
            // echo "Avant chargement des elements <br>";
            $listeelement = $planning->planning();
            // echo "Apres chargement des elements <br>";
            foreach ($listeelement as $keyelement => $element) {
                // echo "Boucle sur l'element <br>";
                $htmltext = $htmltext . $element->html(false, null, $noiretblanc);
            }
            // echo "Fin boucle sur les elements <br>";
            $htmltext = $htmltext . "</tr>";
        }
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "</div>";
        
        if ($noiretblanc == false) {
            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période 
                $annee = $annee - 1;
            }
            $htmltext = $htmltext . $this->fonctions->legendehtml($annee);
        }
        
        $htmltext = $htmltext . "<br>";
        $htmltext = $htmltext . "<form name='structplanningpdf_" . $this->structureid . "'  method='post' action='affiche_pdf.php' target='_blank'>";
        $htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid . "'>";
        $htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
        $htmltext = $htmltext . "<input type='hidden' name='previous' value='no'>";
        if ($noiretblanc)
            $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='yes'>";
        else
            $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='no'>";
        $htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut . "'>";
        $htmltext = $htmltext . "<a href='javascript:document.structplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF</a>";
        $htmltext = $htmltext . "</form>";
        
        // $htmltext = $htmltext . "<form name='structpreviousplanningpdf_" . $this->structureid . "' method='post' action='affiche_pdf.php' target='_blank'>";
        // $htmltext = $htmltext . "<input type='hidden' name='structid' value='" . $this->structureid ."'>";
        // $htmltext = $htmltext . "<input type='hidden' name='structpdf' value='yes'>";
        // $htmltext = $htmltext . "<input type='hidden' name='previous' value='yes'>";
        // if ($noiretblanc)
        // $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='yes'>";
        // else
        // $htmltext = $htmltext . "<input type='hidden' name='noiretblanc' value='no'>";
        // $htmltext = $htmltext . "<input type='hidden' name='mois_annee' value='" . $mois_annee_debut . "'>";
        // $htmltext = $htmltext . "<a href='javascript:document.structpreviousplanningpdf_" . $this->structureid . ".submit();'>Planning en PDF (année précédente)</a>";
        // $htmltext = $htmltext . "</form>";
        return $htmltext;
    }

    function planningresponsablesousstructhtml($mois_annee_debut) // Le format doit être MM/YYYY
    {
        $fulldatedebut = "01/" . $mois_annee_debut;
        $tempfulldatefindb = $this->fonctions->formatdatedb("01/" . $mois_annee_debut);
        $timestampfin = strtotime($tempfulldatefindb);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("Ym", strtotime("+1month", $timestampfin)) . "01";
        // echo "fulldatefin = $fulldatefin <br>";
        $timestampfin = strtotime($fulldatefin);
        // echo "timestampfin = $timestampfin <br>";
        $fulldatefin = date("d/m/Y", strtotime("-1day", $timestampfin));
        // echo "fulldatefin (en lisible)= $fulldatefin <br>";
        //list ($jour, $indexmois, $annee) = split('[/.-]', '01/' . $mois_annee_debut);
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        
        
        $structure = new structure($this->dbconnect);
        $structfilleliste = $this->structurefille();
        $resplist = null;
        if (! is_array($structfilleliste)) { // Si pas de strcuture fille => On sort
            return "";
        }
        foreach ($structfilleliste as $structkey => $structure) {
            // Si la structure n'est pas fermée on cherche le responsable
            if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                if (! is_null($structure->responsable())) {
                    $resplist[$structure->responsable()->harpegeid()] = $structure->responsable();
                }
            }
        }
        if (is_array($resplist)) {
            $htmltext = "";
            $htmltext = $htmltext . "<div id='structplanning'>";
            $htmltext = $htmltext . "<table class='tableau'>";
            
            $titre_a_ajouter = TRUE;
            foreach ($resplist as $agentid => $responsable) {
                $planning = $responsable->planning($fulldatedebut, $fulldatefin)->planning();
                /*
                 * echo "Planning = ";
                 * print_r($planning);
                 * echo "<br>";
                 */
                // echo 'count(planning) = ' .count($planning) . '<br>';
                if ($titre_a_ajouter) {
                    $htmltext = $htmltext . "<tr class='entete_mois'><td class='titresimple' colspan=" . (count($planning) + 1) . " align=center ><font color=#BF3021>Planning des responsables des sous-structures " . $this->nomlong() . " (" . $this->nomcourt() . ")</font></td></tr>";
                    $monthname = $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . date("Y", strtotime($this->fonctions->formatdatedb("01/" . $mois_annee_debut)));
                    // echo "Nom du mois = " . $monthname . "<br>";
                    $htmltext = $htmltext . "<tr class='entete_mois'><td colspan='" . (count($planning) + 1) . "'>" . $monthname . "</td></tr>";
                    // echo "Nbre de jour = " . count($planningservice[$agentid]->planning()) . "<br>";
                    $htmltext = $htmltext . "<tr class='entete'><td>Agent</td>";
                    for ($indexjrs = 0; $indexjrs < (count($planning) / 2); $indexjrs ++) {
                        // echo "indexjrs = $indexjrs <br>";
                        $nomjour = $this->fonctions->nomjour(str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut);
                        $titre = $nomjour . " " . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . " " . $monthname;
                        $htmltext = $htmltext . "<td colspan='2' title='" . $titre . "'>" . str_pad(($indexjrs + 1), 2, "0", STR_PAD_LEFT) . "</td>";
                    }
                    $htmltext = $htmltext . "</tr>";
                    $titre_a_ajouter = FALSE;
                }
                $htmltext = $htmltext . "<tr class='ligneplanning'>";
                $htmltext = $htmltext . "<td>" . $responsable->nom() . " " . $responsable->prenom() . "</td>";
                // echo "Avant chargement des elements <br>";
                $listeelement = $responsable->planning($fulldatedebut, $fulldatefin)->planning();
                // echo "Apres chargement des elements <br>";
                foreach ($listeelement as $keyelement => $element) {
                    // echo "Boucle sur l'element <br>";
                    $htmltext = $htmltext . $element->html();
                }
                // echo "Fin boucle sur les elements <br>";
                $htmltext = $htmltext . "</tr>";
            }
            $htmltext = $htmltext . "</table>";
            $htmltext = $htmltext . "</div>";

            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période
                $annee = $annee - 1;
            }
            $htmltext = $htmltext . $this->fonctions->legendehtml($annee);
            $htmltext = $htmltext . "<br>";
        }
        return $htmltext;
    }

    function dossierhtml($pourmodif = FALSE, $responsableid = NULL)
    {
        
        // echo "strucutre->dossierhtml : Non refaite !!!!! <br>";
        // return null;
        $htmltext = "<br>";
        $htmltext = "<table class='tableausimple'>";
        // // --- Masquage des infos sur le CET
        // $htmltext = $htmltext . "<tr><td class='titresimple' colspan=5 align=center ><font color=#BF3021>Gestion des dossiers pour la structure " . $this->nomlong() . " (" . $this->nomcourt() . ")</font></td></tr>";
        // $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Agent</td><td class='cellulesimple'>Report des congés</td><td class='cellulesimple'>Nbre jours 'enfant malade'</td><td class='cellulesimple'>Nbre jours initial CET</td><td class='cellulesimple'>Date de début du CET</td></tr>";
        // // --- Fin masquage des infos sur le CET
        $htmltext = $htmltext . "<tr><td class='titresimple' colspan=3 align=center ><font color=#BF3021>Gestion des dossiers pour la structure " . $this->nomlong() . " (" . $this->nomcourt() . ")</font></td></tr>";
        $htmltext = $htmltext . "<tr align=center><td class='cellulesimple'>Agent</td><td class='cellulesimple'>Report des congés</td><td class='cellulesimple'>Nbre jours 'Garde d'enfant'</td></tr>";
        $agentliste = $this->agentlist(date('d/m/Y'), date('d/m/Y'), 'n');
        
        // Si on est en mode 'responsable' <=> le code du responsable de la structure est passé en paramètre
        if (! is_null($responsableid)) {
            // On ajoute les responsables de structures filles
            $structureliste = $this->structurefille();
            $responsableliste = array();
            if (is_array($structureliste)) {
                foreach ($structureliste as $key => $structure) {
                    if ($this->fonctions->formatdatedb($structure->datecloture()) >= $this->fonctions->formatdatedb(date("Ymd"))) {
                        $responsable = $structure->responsable();
                        if ($responsable->harpegeid() != '-1') {
                            // La clé NOM + PRENOM + HARPEGEID permet de trier les éléments par ordre alphabétique
                            $responsableliste[$responsable->nom() . " " . $responsable->prenom() . " " . $responsable->harpegeid()] = $responsable;
                            // /$responsableliste[$responsable->harpegeid()] = $responsable;
                        }
                    }
                }
            }
            $agentliste = array_merge((array) $agentliste, (array) $responsableliste);
            ksort($agentliste);
        }
        if (is_array($agentliste)) {
            foreach ($agentliste as $key => $membre) {
                // echo "Structure->dossierhtml : Je suis dans l'agent " . $membre->nom() . "<br>";
                if ($membre->harpegeid() != $responsableid) {
                    $htmltext = $htmltext . "<tr>";
                    $htmltext = $htmltext . "<center><td class='cellulesimple' style='text-align:center;'>" . $membre->civilite() . " " . $membre->nom() . " " . $membre->prenom() . "</td></center>";
                    
                    $complement = new complement($this->dbconnect);
                    $complement->load($membre->harpegeid(), "REPORTACTIF");
                    if ($complement->valeur() == "")
                        $complement->valeur("n"); // Si le complement n'est pas saisi, alors la valeur est "N" (non)
                    $htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>";
                    if ($pourmodif) {
                        $htmltext = $htmltext . "<select name=report[" . $membre->harpegeid() . "]>";
                        $htmltext = $htmltext . "<option value='n'";
                        if (strcasecmp($complement->valeur(), "n") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . ">Non</option>";
                        $htmltext = $htmltext . "<option value='o'";
                        if (strcasecmp($complement->valeur(), "o") == 0)
                            $htmltext = $htmltext . " selected ";
                        $htmltext = $htmltext . ">Oui</option>";
                        $htmltext = $htmltext . "</select>";
                    } else {
                        $htmltext = $htmltext . $this->fonctions->ouinonlibelle($complement->valeur());
                    }
                    $htmltext = $htmltext . "</td></center>";
                    unset($complement);
                    
                    // Ajout du nombre de jours "enfant malade"
                    $complement = new complement($this->dbconnect);
                    $complement->load($membre->harpegeid(), "ENFANTMALADE");
                    $htmltext = $htmltext . "<td class='cellulesimple' >";
                    if ($pourmodif)
                        $htmltext = $htmltext . "<input type='text' style='text-align:center;' name=enfantmalade[" . $membre->harpegeid() . "] value='" . intval($complement->valeur()) . "'/>";
                    else
                        $htmltext = $htmltext . "<center>" . intval($complement->valeur()) . "</center>";
                    $htmltext = $htmltext . "</td>";
                    
                    // // --- Masquage des infos sur le CET
                    // $cet = new cet($this->dbconnect);
                    // $msg = $cet->load($membre->harpegeid());
                    // $cumultotal = "";
                    // $datedebut = "";
                    // if ($msg == "")
                    // {
                    // $cumultotal = $cet->cumultotal();
                    // $datedebut = $cet->datedebut();
                    // }
                    // unset($cet);
                    // // Si on ne modifie rien ou si il y a déja un CET => On affiche en mode lecture seule
                    // if (($msg == "") or (!$pourmodif))
                    // {
                    // $htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $cumultotal . "</td></center>";
                    // $htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'>" . $datedebut . "</td></center>";
                    // }
                    // else
                    // {
                    // $htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'><input type='text' name=cumultotal[" . $membre->harpegeid() ."] value=''/></td></center>";
                    // $htmltext = $htmltext . "<td class='cellulesimple' style='text-align:center;'><input class='calendrier' type='text' name=datedebutcet[" . $membre->harpegeid() ."] value=''/></td></center>";
                    // }
                    // // --- Fin masquage des infos sur le CET
                    $htmltext = $htmltext . "</tr>";
                }
            }
        }
        $htmltext = $htmltext . "</table>";
        $htmltext = $htmltext . "<br>";
        
        return $htmltext;
    }

    function store()
    {
        // echo "structure->store : Non refaite !!!!! <br>";
        // return false;
        $msgerreur = null;
        $sql = "UPDATE STRUCTURE SET AFFICHESOUSSTRUCT='" . $this->sousstructure() . "', AFFICHEPLANNINGTOUTAGENT='" . $this->affichetoutagent() . "' , AFFICHERESPSOUSSTRUCT='" . $this->afficherespsousstruct() . "' , RESPVALIDSOUSSTRUCT='" . $this->respvalidsousstruct() . "', GESTVALIDAGENT='" . $this->gestvalidagent() . "' WHERE STRUCTUREID='" . $this->id() . "'";
        // echo "SQL = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (STRUCTURE - Sous struct + Affiche) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        
        $sql = "UPDATE STRUCTURE SET GESTIONNAIREID='" . $this->gestionnaireid . "' WHERE STRUCTUREID='" . $this->id() . "'";
        // echo "SQL = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (HARP_STRUCTURE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        
        $sql = "UPDATE STRUCTURE SET RESPONSABLEID='" . $this->responsableid . "' WHERE STRUCTUREID='" . $this->id() . "'";
        // echo "SQL = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->store (HARP_STRUCTURE) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        return $msgerreur;
    }

    function pdf($mois_annee_debut, $noiretblanc = false) // Le format doit être MM/YYYY
    {
        // echo "Avant le new PDF <br>";
        $pdf=new FPDF();
        //$pdf = new TCPDF();
        //define('FPDF_FONTPATH','font/');
        //$pdf->Open();
        //$pdf->SetHeaderData('', 0, '', '', array(
        //    0,
        //    0,
        //    0
        //), array(
        //    255,
        //    255,
        //    255
        //));
        $pdf->AddPage('L');
        // echo "Apres le addpage <br>";
        $pdf->Image('../html/images/logo_papeterie.png', 10, 5, 60, 20);
        $pdf->SetFont('helvetica', 'B', 15, '', true);
        $pdf->Ln(15);
        $pdf->Cell(60, 10, utf8_decode('Service : ' . $this->nomlong() . ' (' . $this->nomcourt() . ')'));
        $pdf->Ln(10);
        $pdf->Cell(60, 10, utf8_decode('Planning du mois de : ' . $this->fonctions->nommois("01/" . $mois_annee_debut) . " " . substr($mois_annee_debut, 3)));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 11, '', true);
        $pdf->Cell(60, 10, utf8_decode('Edité le ' . date("d/m/Y")));
        $pdf->Ln(10);
        
        // echo "Avant le planning <br>";
        $planningservice = $this->planning($mois_annee_debut, $mois_annee_debut);
        
        list ($jour, $indexmois, $annee) = explode('/', '01/' . $mois_annee_debut);
        if (($annee . $indexmois <= date('Ym')) and ($noiretblanc == true)) {
            $pdf->SetTextColor(204, 0, 0);
            $pdf->Cell(60, 10, utf8_decode("Attention : Les informations antérieures à la date du jour ont été masquées."));
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(10);
        }
        
        // ///création du planning suivant le tableau généré
        // /Création des entetes de colones contenant les 31 jours/////
        $titre_a_ajouter = TRUE;
        foreach ($planningservice as $agentid => $planning) {
            if ($titre_a_ajouter) {
                $pdf->SetFont('helvetica', 'B', 8, '', true);
                $pdf->Cell(60, 5, utf8_decode(""), 1, 0, 'C');
                for ($index = 1; $index <= count($planningservice[$agentid]->planning()) / 2; $index ++) {
                    $pdf->Cell(6, 5, utf8_decode($index), 1, 0, 'C');
                }
                $pdf->Ln(5);
                $pdf->Cell(60, 5, utf8_decode(""), 1, 0, 'C');
                for ($index = 1; $index <= count($planningservice[$agentid]->planning()) / 2; $index ++) {
                    $pdf->Cell(6, 5, utf8_decode(substr($this->fonctions->nomjour(str_pad($index, 2, "0", STR_PAD_LEFT) . "/" . $mois_annee_debut), 0, 2)), 1, 0, 'C');
                }
                $titre_a_ajouter = FALSE;
            }
            
            // echo "Je charge l'agent $agentid <br>";
            $agent = new agent($this->dbconnect);
            $agent->load($agentid);
            // echo "l'agent $agentid est chargé ... <br>";
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 8, '', true);
            $pdf->Cell(60, 5, utf8_decode($agent->nom() . " " . $agent->prenom()), 1, 0, 'C');
            // echo "Avant chargement des elements <br>";
            $listeelement = $planning->planning();
            // echo "Apres chargement des elements <br>";
            foreach ($listeelement as $keyelement => $element) {
                list ($col_part1, $col_part2, $col_part3) = $this->fonctions->html2rgb($element->couleur($noiretblanc));
                $pdf->SetFillColor($col_part1, $col_part2, $col_part3);
                if (strcasecmp($element->moment(), "m") != 0)
                    $pdf->Cell(3, 5, utf8_decode(""), 'TBR', 0, 'C', 1);
                else
                    $pdf->Cell(3, 5, utf8_decode(""), 'TBL', 0, 'C', 1);
            }
        }
        
        // ///MISE EN PLACE DES LEGENDES DU PLANNING
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 7, '', true);
        $pdf->SetTextColor(0);
        // ////Mise en place de la légende couleurs pour les congés
        
        // echo "Avant legende <br>";
        if ($noiretblanc == false)
            $mois_finperiode = substr($this->fonctions->finperiode(),0,2);
            $mois_debutperiode = substr($this->fonctions->debutperiode(),0,2);
            if ($indexmois<=$mois_finperiode and $indexmois<$mois_debutperiode)
            {
                // Si on est entre janvier et la fin de période
                $annee = $annee - 1;
            }
            $this->fonctions->legendepdf($pdf,$annee);
        // echo "Apres legende <br>";
        
        $pdf->Ln(8);
        ob_end_clean();
        $pdf->Output();
        // $pdf->Output('demande_pdf/autodeclaration_num'.$ID_AUTODECLARATION.'.pdf');
    }

    function getdelegation(&$delegationuserid, &$datedebutdeleg, &$datefindeleg)
    {
        // echo "<FONT SIZE='5pt' COLOR='#FF0000'><B>La récupération de la délégation pour une structure n'est pas implémentée.<BR> Les valeurs retournée sont fixes !!!!</B></FONT> <br>";
        $delegationuserid = "";
        $datedebutdeleg = "";
        $datefindeleg = "";
        
        $sql = "SELECT IDDELEG,DATEDEBUTDELEG,DATEFINDELEG FROM STRUCTURE WHERE STRUCTUREID = '" . $this->id() . "' AND IDDELEG <> ''";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Structure->getdelegation : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) > 0) {
            $result = mysqli_fetch_row($query);
            $delegationuserid = "$result[0]";
            if ("$result[1]" != "") {
                $datedebutdeleg = $this->fonctions->formatdate("$result[1]");
            }
            if ("$result[2]" != "") {
                $datefindeleg = $this->fonctions->formatdate("$result[2]");
            }
        }
    }

    function setdelegation($delegationuserid, $datedebutdeleg, $datefindeleg)
    {
        // echo "<FONT SIZE='5pt' COLOR='#FF0000'><B>Il manque l'enregistrement de la délégation..... </B></FONT><br> Harpege Id = $delegationuserid Début = $datedebutdeleg fin = $datefindeleg <br>";
        if ($datedebutdeleg != "") {
            $datedebutdeleg = $this->fonctions->formatdatedb($datedebutdeleg);
        }
        if ($datefindeleg != "") {
            $datefindeleg = $this->fonctions->formatdatedb($datefindeleg);
        }
        $sql = "UPDATE STRUCTURE SET IDDELEG='" . $delegationuserid . "', DATEDEBUTDELEG='" . $datedebutdeleg . "',DATEFINDELEG='" . $datefindeleg . "'  WHERE STRUCTUREID='" . $this->id() . "'";
        // echo "SQL = " . $sql . "<br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        $msgerreur = '';
        if ($erreur != "") {
            $errlog = "Structure->setdelegation : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $msgerreur = $msgerreur . $erreur;
        }
        $this->delegueid = $delegationuserid;
        return $msgerreur;
    }
}

?>