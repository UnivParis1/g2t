<?php

use Fpdf\Fpdf as FPDF;

class demande
{

    public const DEMANDE_VALIDE = "v";
    public const DEMANDE_REFUSE = "r";
    public const DEMANDE_ATTENTE = "a";
    public const DEMANDE_ANNULE = "x";

    private $demandeid = null;

    private $typeabsenceid = null;

    private $datedebut = null;

    private $datefin = null;

    private $momentdebut = null;

    private $momentfin = null;

    private $commentaire = null;

    private $nbrejrsdemande = null;

    private $datedemande = null;

    private $datestatut = null;

    private $statut = null;

    private $motifrefus = null;

    private $dbconnect = null;

    private $heuredemande = null;
    
    private $datemailannulation = '1900-01-01';  // On met par défaut une date très loin dans le passé

    // Utilisé lors de la sauvegarde !!
    private $ancienstatut = null;

    private $agentid = null;
    private $agent = null;

    private $fonctions = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "Demande->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function load($demandeid)
    {
        // if (is_null($this->$demandeid))
        if (! isset($this->$demandeid)) 
        {
            $sql = "SELECT DEMANDEID,AGENTID,TYPEABSENCEID,DATEDEBUT,MOMENTDEBUT,DATEFIN,MOMENTFIN,COMMENTAIRE,NBREJRSDEMANDE,
                           DATE(DATEDEMANDE),DATESTATUT,STATUT,MOTIFREFUS,TIME(DATEDEMANDE),DATEMAILANNULATION 
                    FROM DEMANDE 
                    WHERE DEMANDEID= ?";
            // echo "Demande load sql = $sql <br>";
            $params = array($demandeid);
            $query = $this->fonctions->prepared_select($sql, $params);
            
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Demande->Load : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Demande->Load : Demande $demandeid non trouvée";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            $this->demandeid = "$result[0]";
            $this->agentid = "$result[1]";
            $this->typeabsenceid = "$result[2]";
            $this->datedebut = "$result[3]";
            $this->momentdebut = "$result[4]";
            $this->datefin = "$result[5]";
            $this->momentfin = "$result[6]";
            $this->commentaire = str_replace("'", "''", $result[7]);
            $this->nbrejrsdemande = "$result[8]";
            $this->datedemande = "$result[9]";
            $this->datestatut = "$result[10]";
            $this->statut = "$result[11]";
            $this->motifrefus = str_replace("'", "''", $result[12]);
            $this->heuredemande = "$result[13]";
            $this->datemailannulation = "$result[14]";
            
            $this->ancienstatut = $this->statut;
        }
    }

    function id()
    {
        return $this->demandeid;
    }

    function type($typeid = null)
    {
        if (is_null($typeid)) {
            if (is_null($this->typeabsenceid)) {
                $errlog = "Demande->type : Le type de demande n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return $this->typeabsenceid;
            }
        } 
        else
        {
            $this->typeabsenceid = $typeid;
        }
    }

    function typelibelle()
    {
        if (is_null($this->typeabsenceid)) {
            $errlog = "Demande->typelibelle : Le type de demande n'est pas défini !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            $sql = "SELECT LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID=?";
            $params = array($this->typeabsenceid);
            $query = $this->fonctions->prepared_select($sql, $params);
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "Demande->typdemande : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            if (mysqli_num_rows($query) == 0) {
                $errlog = "Demande->typdemande : Libellé du type de demande $this->typeabsenceid non trouvé";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            $result = mysqli_fetch_row($query);
            return "$result[0]";
        }
    }

    function datedebut($date_debut = null)
    {
        if (is_null($date_debut)) {
            if (is_null($this->datedebut)) {
                $errlog = "Demande->datedebut : La date de début n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                return $this->fonctions->formatdate($this->datedebut);
            }
        } else {
            if (is_null($this->demandeid))
            {
                $this->datedebut = $this->fonctions->formatdatedb($date_debut);
            }
            else {
                $errlog = "Demande->datedebut : Impossible de modifier une date si la demande est enregistrée !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function datefin($date_fin = null)
    {
        if (is_null($date_fin)) {
            if (is_null($this->datefin)) {
                $errlog = "Demande->datefin : La date de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                return $this->fonctions->formatdate($this->datefin);
            }
        } else {
            if (is_null($this->demandeid))
            {
                $this->datefin = $this->fonctions->formatdatedb($date_fin);
            }
            else {
                $errlog = "Demande->datefin : Impossible de modifier une date si la demande est enregistrée !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function moment_debut($moment_deb = null)
    {
        if (is_null($moment_deb)) {
            if (is_null($this->momentdebut)) {
                $errlog = "Demande->moment_debut : La demie-journée de début n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                if ($this->momentdebut == fonctions::MOMENT_MATIN)
                {
                    return fonctions::MOMENT_MATIN;
                }
                elseif ($this->momentdebut == fonctions::MOMENT_APRESMIDI)
                {
                    return fonctions::MOMENT_APRESMIDI;
                }
                else {
                    $errlog = "Demande->moment_debut : le moment de début n'est pas connu [momentdebut = " . $this->momentdebut . "] !!!";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            if (is_null($this->demandeid))
            {
                $this->momentdebut = $moment_deb;
            }
            else {
                $errlog = "Demande->moment_debut : Impossible de modifier la demie-journée de début si la demande est enregistrée !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function moment_fin($moment_fin = null)
    {
        if (is_null($moment_fin)) {
            if (is_null($this->momentfin)) {
                $errlog = "Demande->moment_fin : La demie-journée de fin n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                if ($this->momentfin == fonctions::MOMENT_MATIN)
                {
                    return fonctions::MOMENT_MATIN;
                }
                elseif ($this->momentfin == fonctions::MOMENT_APRESMIDI)
                {
                    return fonctions::MOMENT_APRESMIDI;
                }
                else 
                {
                    $errlog = "Demande->moment_fin : la demie-journée n'est pas connue [momentfin = $this->momentfin] !!!";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            if (is_null($this->demandeid))
            {
                $this->momentfin = $moment_fin;
            }
            else 
            {
                $errlog = "Demande->moment_fin : Impossible de modifier la demie-journée de fin si la demande est enregistrée !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function commentaire($comment = null)
    {
        if (is_null($comment))
        {
            return str_replace("''", "'", $this->commentaire);
        }
        else
        {
            $this->commentaire = str_replace("'", "''", $comment);
        }
    }

    function nbrejrsdemande($nbrejrs = null)
    {
        if (is_null($nbrejrs)) {
            if (is_null($this->nbrejrsdemande)) {
                $errlog = "Demande->nbrejrsdemande : Le nombre de jours demandés n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                return (float) $this->nbrejrsdemande; // number_format($this->nbrejrsdemande,1);
            }
        } else {
            if (is_null($this->demandeid))
                $this->nbrejrsdemande = $nbrejrs;
            else {
                $errlog = "Demande->nbrejrsdemande : Impossible de modifier le nombre de jours si la demande est enregistrée !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function date_demande()
    {
        if (is_null($this->demandeid)) {
            $errlog = "Demande->date_demande : La demande n'est pas enregistrée, donc pas de date de demande !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } 
        else
        {
            return $this->fonctions->formatdate($this->datedemande);
        }
    }

    function heure_demande()
    {
        if (is_null($this->demandeid)) {
            $errlog = "Demande->heure_demande : La demande n'est pas enregistrée, donc pas d'heure de demande !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } else {
            if ($this->heuredemande == "00:00:00")
            {
                return "";
            }
            else
            {
                return $this->heuredemande;
            }
        }
    }

    function datestatut()
    {
        if (is_null($this->demandeid)) {
            $errlog = "Demande->datestatut : La demande n'est pas enregistrée, donc pas de date de statut !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        } 
        else
        {
            return $this->fonctions->formatdate($this->datestatut);
        }
    }

    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->demandeid)) {
                $errlog = "Demande->statut : La demande n'est pas enregistrée, donc pas de statut !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else {
                //if (strcasecmp($this->statut, 'v') == 0 or (strcasecmp($this->statut, 'a') == 0 or strcasecmp($this->statut, 'r') == 0))
                //if (strcmp($this->statut, demande::DEMANDE_VALIDE) == 0 or strcmp($this->statut, demande::DEMANDE_ATTENTE) == 0 or strcasecmp($this->statut, demande::DEMANDE_REFUSE) == 0)
                if (strcmp($this->statut, demande::DEMANDE_VALIDE) == 0 or strcmp($this->statut, demande::DEMANDE_ATTENTE) == 0 or strcasecmp($this->statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($this->statut, demande::DEMANDE_ANNULE) == 0)
                   return $this->statut;
                else {
                    $errlog = "Demande->statut : le statut n'est pas connu [statut = $this->statut] !!!";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
            }
        } else {
            //if (strcasecmp($this->statut, 'a') == 0 or (strcasecmp($this->statut, 'v') == 0 and strcasecmp($statut, 'r') == 0)) {
            //if (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) == 0 or (strcasecmp($this->statut, demande::DEMANDE_VALIDE) == 0 and strcasecmp($statut, demande::DEMANDE_REFUSE) == 0)) {
            if (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) == 0 or (strcasecmp($this->statut, demande::DEMANDE_VALIDE) == 0 and (strcasecmp($statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($statut, demande::DEMANDE_ANNULE) == 0))) {
                $this->datestatut = $this->fonctions->formatdatedb(date("d/m/Y"));
                $this->statut = $statut;
            } else {
                $errlog = "Le statut actuel est : " . $this->statut . " ===> Impossible de le passer au statut : " . $statut;
                //echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
        }
    }

    function motifrefus($motif = null)
    {
        if (is_null($motif)) {
            if (is_null($this->demandeid)) {
                $errlog = "Demande->motifrefus : La demande n'est pas enregistrée, donc pas de motif de refus !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } 
            else
            {
                return str_replace("''", "'", $this->motifrefus);
            }
        } 
        else
        {
            $this->motifrefus = str_replace("'", "''", $motif);
        }
    }
    
    function agentid($agentid = null)
    {
        if (is_null($agentid)) 
        {
            return $this->agentid;
        } 
        else
        {
            $this->agentid = $agentid;
        }
    }
    
    function datemailannulation($datemailannulation = null)
    {
        if (is_null($datemailannulation)) 
        {
            return $this->fonctions->formatdate($this->datemailannulation);
        } 
        else
        {
            $this->datemailannulation = $this->fonctions->formatdatedb($datemailannulation);
        }       
    }

    /**
     *
     * @deprecated
     */
    function declarationTPliste()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        
        $sql = "SELECT DECLARATIONID FROM DEMANDEDECLARATIONTP WHERE DEMANDEID= '" . $this->demandeid . "'";
        // echo "Demande declarationTPListe sql = $sql <br>";
        $query = mysqli_query($this->dbconnect, $sql);
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Demande->declarationTPliste : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) == 0) {
            $errlog = "Demande->declarationTPliste : Pas de déclaration de TP pour la demande " . $this->demandeid;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $declaliste = null;
        while ($result = mysqli_fetch_row($query)) {
            $declaration = new declarationTP($this->dbconnect);
            $declaration->load($result[0]);
            $declaliste[] = $declaration;
            unset($declaration);
        }
        return $declaliste;
    }

    function agent()
    {
        if (is_null($this->agent)) 
        {
            if (!is_null($this->agentid))
            {
                $agent = new agent($this->dbconnect);
                $agent->load($this->agentid);
                $this->agent = $agent;
            }
            else
            {
                $sql = "SELECT AGENTID FROM DEMANDE WHERE DEMANDEID=?";
                $params = array($this->demandeid);
                $query = $this->fonctions->prepared_select($sql, $params);
    
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Demande->agent : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                if (mysqli_num_rows($query) == 0) {
                    $errlog = "Demande->agent : Pas d'agent trouvé pour la demande " . $this->demandeid;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                $result = mysqli_fetch_row($query);
                $agent = new agent($this->dbconnect);
                $agent->load("$result[0]");
                $this->agent = $agent;
                $this->agentid = $agent->agentid();
            }
        }
        return $this->agent;
    }

    function controlenbrejrs(&$nbrejrscalcule)
    {
        // echo "\n\n<br><br>On est sur la demande : Datedebut = " . $this->datedebut() . " date fin = " . $this->datefin() . "\n<br>";
        $nbredemiejrs = 0;
        $nbrejrscalcule = 0;
        $agent = $this->agent();
        // echo "identite de l'agent => " . $agent->identitecomplete() . "<br>";
        if (($this->statut() == demande::DEMANDE_VALIDE) or ($this->statut() == demande::DEMANDE_ATTENTE)) {
            $planning = new planning($this->dbconnect);
            $planning->load($agent->agentid(), $this->datedebut(), $this->datefin());
            $listelement = $planning->planning();
            // echo "<br>Liste des elements => " . print_r($listelement,true) . "\n<br>";
            foreach ((array) $listelement as $element) {
                // echo "Dans la boucle .... Id de la demande courante = ". $this->demandeid . " L'element Id = " . $element->demandeid() . "\n<br>";
                if ($element->demandeid() == $this->demandeid) {
                    // echo "Yes !!! +1 \n<br>";
                    $nbredemiejrs = $nbredemiejrs + 1;
                }
            }
            
            $nbrejrscalcule = $nbredemiejrs / 2;
            // echo "Fin de la boucle nbrejrscalcules = $nbrejrscalcules nbrejrsdemande = " . $this->nbrejrsdemande() . "\n<br>";
            if ($nbrejrscalcule != $this->nbrejrsdemande()) {
                return false;
            } else {
                return true;
            }
        }        // Pas de vérification car la demande est annulée ou refusée !
        else {
            return true;
        }
    }

    function store($declarationTPListe = null, $ignoreabsenceautodecla = FALSE, $ignoresoldeinsuffisant = FALSE)
    {
        // echo "Demande->store : En cours de réécriture !!!!! <br>";
        if (is_null($this->demandeid)) {
            // On vérifie que le nombre de jour demandé est >= Nbre de jour restant (si c'est un conge !!)
            // echo "Demande->Store : typdemande=". $this->typdemande . "<br>";
            if ($this->fonctions->estunconge($this->typeabsenceid)) {
                // echo "C'est un congé... <br>";
                unset($solde);
                $solde = new solde($this->dbconnect);
                $solde->load($this->agentid(), $this->typeabsenceid);
            }
            
            // echo "datedemande = " . $this->datedemande;
            if (is_null($this->nbrejrsdemande)) {
                // echo "Le nbre jour est nul ==> On demande le nombre de jour <br>";
                $planning = new planning($this->dbconnect);
                // echo "this->agentid" . $this->agentid . "<br>";
                // echo "this->fonctions->formatdate($this->datedebut) " . $this->fonctions->formatdate($this->datedebut) . "<br>";
                // echo "this->demijrs_debut " . $this->demijrs_debut . "<br>";
                // echo "this->fonctions->formatdate($this->datefin) " . $this->fonctions->formatdate($this->datefin) . "<br>";
                // echo "this->demijrs_fin " . $this->demijrs_fin . "<br>";
                // echo "ignoreabsenceautodecla " . $ignoreabsenceautodecla . "<br>";
                
                $this->nbrejrsdemande = $planning->nbrejourtravaille($this->agentid(), $this->fonctions->formatdate($this->datedebut), $this->momentdebut, $this->fonctions->formatdate($this->datefin), $this->momentfin, $ignoreabsenceautodecla);
                // echo "nbredemijrs_demande = " . $this->nbredemijrs_demande . "<br>";
            }
            
            $nbjrrestant = 0;
            if ($this->fonctions->estunconge($this->typeabsenceid)) {
                if (is_null($solde)) {
                    $errlog = "Demande->Store : Pas de solde pour le type de demande " . $this->typeabsenceid . " et l'agent " . $this->agentid();
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                } else {
                    $nbjrrestant = $solde->droitaquis() - $solde->droitpris();
                    // echo "solde->droitaquis_demijrs() - solde->droitpris_demijrs() ==> " . $solde->droitaquis_demijrs() . " - " . $solde->droitpris_demijrs() . "<br>";
                }
            }
            
            // echo "Nombre de jours restant = " . $nbjrrestant . " nbredemijrs_demande = " . $this->nbredemijrs_demande . " <br>";
            if (($nbjrrestant >= $this->nbrejrsdemande) or (! $this->fonctions->estunconge($this->typeabsenceid)) or ($ignoresoldeinsuffisant == TRUE)) {
                if ($this->nbrejrsdemande == 0) {
                    $errlog = "Le nombre de jour demandé est égal à 0.";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    return $errlog . "<br/>";
                }
                // On est dans le cas d'une création de demande
                $this->datedemande = $this->fonctions->formatdatedb(date("d/m/Y"));
                $this->heuredemande = date("H:i:s");
                
                $sql = "LOCK TABLES DEMANDE WRITE";
                mysqli_query($this->dbconnect, $sql);
                $sql = "SET AUTOCOMMIT = 0";
                mysqli_query($this->dbconnect, $sql);
                $sql = "INSERT INTO DEMANDE(AGENTID,TYPEABSENCEID,DATEDEBUT,MOMENTDEBUT,DATEFIN,MOMENTFIN,
				        COMMENTAIRE,NBREJRSDEMANDE,DATEDEMANDE,DATESTATUT,STATUT,MOTIFREFUS,DATEMAILANNULATION) ";
                $sql = $sql . "VALUES('" . $this->agentid . "','" . $this->typeabsenceid . "','" . $this->fonctions->formatdatedb($this->datedebut) . "',";
                $sql = $sql . "'" . $this->momentdebut . "','" . $this->fonctions->formatdatedb($this->datefin) . "','" . $this->momentfin . "',";
                $sql = $sql . "'" . $this->commentaire . "',";
                $sql = $sql . "'" . $this->nbrejrsdemande . "', now(), '1900-01-01','" . demande::DEMANDE_ATTENTE . "', '', '" . $this->datemailannulation  . "')";
                // echo "SQL = " . $sql . "<br>";
                $params = array();
                $query = $this->fonctions->prepared_query($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Demande->store : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                // $sql = "SELECT LAST_INSERT_ID()";
                // $toto = mysqli_query($this->dbconnect, $sql);
                // echo "toto = "; print_r($toto); echo " toto[1] = " . $toto[1] . "<br>";
                // echo "toto(2) = $toto <br>";
                // echo "Dernier indice = " . mysqli_insert_id($this->dbconnect) . "<br>";
                $this->demandeid = mysqli_insert_id($this->dbconnect);
                // $this->demandeid
                $sql = "COMMIT";
                mysqli_query($this->dbconnect, $sql);
                $sql = "UNLOCK TABLES";
                mysqli_query($this->dbconnect, $sql);
                $sql = "SET AUTOCOMMIT = 1";
                mysqli_query($this->dbconnect, $sql);
                                
                // On decompte le nombre de jours que l'on vient de poser sauf si c'est un CET
                if ($this->fonctions->estunconge($this->typeabsenceid) and (strcasecmp($this->typeabsenceid, 'cet') != 0)) {
                    $sql = "UPDATE SOLDE
                            SET DROITPRIS = DROITPRIS + " . $this->nbrejrsdemande . "
                            WHERE TYPEABSENCEID='" . $this->typeabsenceid . "' AND AGENTID = '" . $this->agentid() . "'";
                    // echo "SQL = $sql <br>";
                    $params = array();
                    $query = $this->fonctions->prepared_query($sql, $params);
                    $erreur = mysqli_error($this->dbconnect);
                    if ($erreur != "") {
                        $errlog = "Demande->store (SOLDE) : " . $erreur;
                        echo $errlog . "<br/>";
                        error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                    }
                }
                $this->ancienstatut = demande::DEMANDE_ATTENTE;
            } else {
                $errlog = "Nombre de jours insuffisants (demandé : " . ($this->nbrejrsdemande) . " solde restant : " . ($nbjrrestant) . ").";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $errlog . "<br/>";
            }
        } else {
            // Si le statut de la demande était déja annulé/refusé => On ne fait rien
            //if (strcasecmp($this->ancienstatut, demande::DEMANDE_REFUSE) == 0) {
            if ((strcasecmp($this->ancienstatut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($this->ancienstatut, demande::DEMANDE_ANNULE) == 0)) {
                $errlog = "Impossible de changer le statut d'une demande 'refusée'.";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                return $errlog . "<br/>";
                ;
            } else {
                // Si la date est null ou si elle est < 1900-01-01 (<=> Non initialisée)
                if (is_null($this->datestatut) or $this->fonctions->formatdatedb($this->datestatut)<'19000101') 
                {
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents("demande::store => La date de changement de statut de la demande n'est pas valide => On la force à la date du jour."));
                    $this->datestatut = date('d/m/Y');
                }
                // On est dans le cas d'une modification de demande
                $sql = "UPDATE DEMANDE
                        SET DATESTATUT='" . $this->fonctions->formatdatedb($this->datestatut) . "',
                            STATUT='" . $this->statut . "',
                            MOTIFREFUS='" . $this->motifrefus . "',
                            DATEMAILANNULATION='" . $this->datemailannulation  . "'
                         WHERE DEMANDEID=" . $this->demandeid;
                // echo "SQL = $sql <br>";
                $params = array();
                $query = $this->fonctions->prepared_query($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "Demande->store : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                // Si le nouveau statut est annulé => On doit recréditer le nombre de jour....
                //if (strcasecmp($this->ancienstatut, demande::DEMANDE_REFUSE) != 0 and strcasecmp($this->statut, demande::DEMANDE_REFUSE) == 0) {
                /*
                *if ((strcasecmp($this->ancienstatut, demande::DEMANDE_REFUSE) != 0 and strcasecmp($this->ancienstatut, demande::DEMANDE_ANNULE) != 0)
                *   and (strcasecmp($this->statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($this->statut, demande::DEMANDE_ANNULE) == 0)) {
                */
                if ((strcasecmp($this->ancienstatut, demande::DEMANDE_VALIDE) == 0 or strcasecmp($this->ancienstatut, demande::DEMANDE_ATTENTE) == 0)
                   and (strcasecmp($this->statut, demande::DEMANDE_REFUSE) == 0 or strcasecmp($this->statut, demande::DEMANDE_ANNULE) == 0)) {
                       // Si ce n'est pas un CET on doit recréditer le nombre de jour
                    if (strcasecmp($this->typeabsenceid, 'cet') != 0) {
                        // On recrédite le nombre de jours dans les congés....
                        $sql = "UPDATE SOLDE
                                SET DROITPRIS = DROITPRIS - " . $this->nbrejrsdemande . "
                                WHERE TYPEABSENCEID='" . $this->typeabsenceid . "' AND AGENTID = '" . $this->agentid() . "'";
                        // echo "SQL = $sql <br>";
                        $params = array();
                        $query = $this->fonctions->prepared_query($sql, $params);
                        $erreur = mysqli_error($this->dbconnect);
                        if ($erreur != "") {
                            $errlog = "Demande->store (Modif SOLDE_CMPTE) : " . $erreur;
                            echo $errlog . "<br/>";
                            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                        }
                    }
                }
            }
        }
        return "";
    }

    function pdf($valideurid)
    {
        // echo "Debut du PDF <br>";
        $pdf=new FPDF();
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
        // echo "Apres le new <br>";
        //if (!defined('FPDF_FONTPATH'))
        //  define('FPDF_FONTPATH','font/');
        //$pdf->Open();
        $pdf->AddPage();
        //$pdf->Image($this->fonctions->imagepath() . '/logo_papeterie.png', 70, 25, 60, 20);
        $pdf->Image($this->fonctions->etablissementimagepath() . '/' . LOGO_FILENAME, 70, 25, 60, 20);
        
        // if (is_null($this->structureid) or $this->structureid=="")
        // {
        // //echo "Le code de la structure est vide...<br>";
        // $agent=new agent($this->dbconnect);
        // $agent->load($this->agentid);
        // $this->structure($agent->structure()->id());
        // //echo "Apres le load de la structure du responsable... <br>";
        // }
        
        $pdf->SetFont('helvetica', 'B', 16, '', true);
        $pdf->Ln(70);
        // $pdf->Cell(60,10,'Composante : '. $this->structure()->parentstructure()->nomlong() .' ('. $this->structure()->parentstructure()->nomcourt() .')' );
        // $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        
        $agent = $this->agent();
        $affectationliste = $agent->affectationliste($this->datedebut, $this->datefin);
        if (is_array($affectationliste))
        {
            foreach ($affectationliste as $key => $affectation) {
                $structure = new structure($this->dbconnect);
                $structure->load($affectation->structureid());
                $nomstructure = $structure->nomlong() . " (" . $structure->nomcourt() . ")";
                $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Service : ' . $nomstructure));
                $pdf->Ln();
            }
        }
        else {
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Aucune affectation trouvée pour cette demande.'));
            $pdf->Ln();
        }
        
        // $pdf->Cell(60,10,'Service : '. $this->structure()->nomlong().' ('. $this->structure()->nomcourt() .')' );
        // $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        if ($this->fonctions->estunconge($this->typeabsenceid))
            $typelib = " de congé ";
        else
            $typelib = " d'autorisation d'absence ";
            $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Demande' . $typelib . 'N°' . $this->id() . ' de ' . $this->agent()
            ->civilite() . " " . $this->agent()
            ->nom() . " " . $this->agent()
            ->prenom()));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        
        $decision = $this->fonctions->demandestatutlibelle($this->statut());
        //$pdf->Ln(10);
        //$pdf->Cell(40, 10, $this->fonctions->utf8_decode(' décision 1 => ' . $decision . ' par :'));
        $decision = mb_strtolower($decision,'UTF-8');   // Ne pas utiliser strtolower car la convertion des caractères accentués n'est pas prise en compte (dépendant de la localisation)
        //$pdf->Ln(10);
        //$pdf->Cell(40, 10, $this->fonctions->utf8_decode(' décision 2 => ' . $decision . ' par :'));
        //$pdf->Ln(10);
        
        // if($this->statut()==demande::DEMANDE_VALIDE)
        // $decision='validée';
        // else
        // $decision='refusée';
        
        $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Votre demande ' . $typelib . 'du ' . $this->datedebut() . ' ' . $this->fonctions->nommoment($this->momentdebut) . ' au ' . $this->datefin() . ' ' . $this->fonctions->nommoment($this->momentfin) . ' '));
        $pdf->Ln(10);
        $pdf->Cell(40, 10, $this->fonctions->utf8_decode(' a été ' . $decision . ' par :'));
        
        $pdf->Ln(10);
        
        $valideur = new agent($this->dbconnect);
        $valideur->load($valideurid);
        
        $pdf->Cell(40, 10, $this->fonctions->utf8_decode(' - ' . $valideur->civilite() . " " . $valideur->nom() . " " . $valideur->prenom()));
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Date de dépot : ' . $this->date_demande()));
        $pdf->Ln(10);
        //if (strcasecmp($this->statut(), 'r') == 0)
        //if (strcasecmp($this->statut(), demande::DEMANDE_REFUSE) == 0)
        if (strcasecmp($this->statut(), demande::DEMANDE_REFUSE) == 0 or strcasecmp($this->statut(), demande::DEMANDE_ANNULE) == 0)
            $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Date du refus/de l\'annulation : ' . $this->datestatut()));
        else
            $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Date de validation : ' . $this->datestatut()));
        $pdf->Ln(10);
        //if ($this->statut() == 'v') {
        if ($this->statut() == demande::DEMANDE_VALIDE) {
            if ($this->fonctions->estunconge($this->type()))
                $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Nombre de jour(s) comptabilisé(s) : ' . ($this->nbrejrsdemande())));
        } else {
            // echo "Motif refus = " .$this->motifrefus() . "<br>";
            // echo "Motif refus (avec strreplace) = ". str_replace("''", "'", $this->motifrefus()) . "<br>";
            
            $pdf->Cell(40, 10, $this->fonctions->utf8_decode('Motif du refus : ' . str_replace("''", "'", $this->motifrefus())));
        }
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        $pdf->Ln(10);
        $pdf->Cell(25, 10, $this->fonctions->utf8_decode(''));
        $pdf->Cell(60, 10, $this->fonctions->utf8_decode('Solde en cours'));
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 6, '', true);
        $pdf->Cell(25, 10, $this->fonctions->utf8_decode(''));
        $pdf->Cell(70, 7, $this->fonctions->utf8_decode('Type de congé'), 1);
        $pdf->Cell(25, 7, $this->fonctions->utf8_decode('Droit acquis'), 1);
        $pdf->Cell(25, 7, $this->fonctions->utf8_decode('Droit pris'), 1);
        $pdf->Cell(25, 7, $this->fonctions->utf8_decode('Solde actuel'), 1);
        $pdf->Ln();
        $pdf->SetFont('helvetica', 'B', 6, '', true);
        $pdf->Cell(25, 10, $this->fonctions->utf8_decode(''));
        
        $tabsolde = $agent->soldecongesliste($this->fonctions->anneeref());
        if (is_array($tabsolde)) {
            foreach ($tabsolde as $key => $solde) {
                $pdf->Cell(70, 7, $this->fonctions->utf8_decode($solde->typelibelle()), 1);
                $pdf->Cell(25, 7, $this->fonctions->utf8_decode((string) ($solde->droitaquis())), 1);
                $pdf->Cell(25, 7, $this->fonctions->utf8_decode((string) ($solde->droitpris())), 1);
                $pdf->Cell(25, 7, $this->fonctions->utf8_decode((string) ($solde->solde())), 1);
                $pdf->Ln();
                $pdf->SetFont('helvetica', 'B', 6, '', true);
                $pdf->Cell(25, 10, $this->fonctions->utf8_decode(''));
            }
        }
        
        // //Positionnement à 1,5 cm du bas
        // $pdf->SetY(-40);
        // //Police Arial italique 8
        // $pdf->SetFont('Arial','B',7);
        // $pdf->Cell(190,1,'Université Panthéon-Sorbonne - Paris 1, 12 place du Panthéon, 75005 PARIS',0,0,'C');
        
        // $pdf->Output();
        $pdfname = $this->fonctions->pdfpath() . '/' . date('Y-m') . '/demande_num' . $this->id() . '_' . date("YmdHis") . '.pdf';
        // $pdfname = sys_get_temp_dir() . '/demande_num'.$this->id().'.pdf';
        // echo "Nom du PDF = " . $pdfname . "<br>";
        //$pdf->Output($pdfname, 'F');
        $this->fonctions->savepdf($pdf, $pdfname);
        return $pdfname;
    }

    function ics($mail)
    {
        if (strcasecmp($this->typeabsenceid, 'teletrav') == 0  or strcasecmp($this->typeabsenceid, 'travdist') == 0)
        {
            // L'agent travaille donc il ne doit pas être mis en 'absence' ou en 'congés' dans l'agenda
            // Pas de mise à jour de l'agenda ==> Pas de création d'un ICS
            return null;
        }
        
        $absenceidparent = '';
        $libelleabsence = "";
        $libelleabsenceparent = "";
        
        //$sql = 'SELECT ABSENCEIDPARENT,LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID = ?';
        // On récupère le libellé de l'absence parent pour le mettre dans l'ics sur c'est du télétravail hors convention
        $sql = 'SELECT T1.ABSENCEIDPARENT, T1.LIBELLE, T2.LIBELLE FROM TYPEABSENCE T1 LEFT JOIN TYPEABSENCE T2 ON T1.ABSENCEIDPARENT  = T2.TYPEABSENCEID WHERE T1.TYPEABSENCEID = ?';
        $params = array($this->typeabsenceid);
        $query = $this->fonctions->prepared_select($sql, $params);
        
        $erreur = mysqli_error($this->dbconnect);
        if ($erreur != "") {
            $errlog = "Demande->ics (recup absenceparentid) : " . $erreur;
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        if (mysqli_num_rows($query) != 0) {
            $result = mysqli_fetch_row($query);
            $absenceidparent = $result[0];
            $libelleabsenceparent = $result[2];
        }
        
        $dtstart = str_replace('-', '', $this->datedebut) . 'T';
        if ($this->moment_debut() == fonctions::MOMENT_MATIN) {
            $dtstart .= '090000';
        } else {
            $dtstart .= '133000';
        }
        $dtend = str_replace('-', '', $this->datefin) . 'T';
        if ($this->moment_fin() == fonctions::MOMENT_MATIN) {
            $dtend .= '123000';
        } else {
            $dtend .= '170000';
        }
        $cur_agent = $this->agent();
        $cal_uid = 'G2T' . '-' . $cur_agent->agentid() . '-' . $this->demandeid; // ."@echange.univ-paris1.fr" ; /// date('md').'T'.date('His')."-".rand()."@echange.univ-paris1.fr";
                                                                                   // $todaystamp = date("Ymd\THis\Z");
        if ($this->fonctions->estunconge($this->typeabsenceid)) {
            $meeting_description = 'Congé';
            $subject = 'Congé';
        } elseif (strcasecmp($absenceidparent,'teletravHC') == 0) {
            $meeting_description = $libelleabsenceparent;
            $subject = $libelleabsenceparent;
        } else {
            $meeting_description = 'Absence';
            $subject = 'Absence';
        }
        
        //echo "<br>Le statut de la demande est : " . $this->statut . " <br>";
        
        $disponibilite = 'OPAQUE';
        //if (strcasecmp($this->statut, 'v') == 0) 
        if (strcasecmp($this->statut, demande::DEMANDE_VALIDE) == 0)
        // La demande est validée
        {
            if (strcasecmp($absenceidparent,'teletravHC') == 0)  // Si c'est un télétravail HC => Le statut est FREE
            {
                $ics_status = 'FREE';
                $disponibilite = 'TRANSPARENT';
            }
            else
            {
                $ics_status = 'CONFIRMED';
            }
        //} elseif (strcasecmp($this->statut, 'R') == 0) 
        } elseif (strcmp($this->statut, demande::DEMANDE_ANNULE) == 0 or strcmp($this->statut, demande::DEMANDE_REFUSE) == 0) 
        // La demande est refusée ou annulée
        {
            $ics_status = 'CANCELLED';
            $disponibilite = 'TRANSPARENT';
        //} elseif (strcasecmp($this->statut, 'a') == 0)
        } elseif (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) == 0) 
        // La demande est en attente
        {
            $ics_status = 'TENTATIVE';
        }
        
        $ics = "BEGIN:VCALENDAR
PRODID:-//The Horde Project//Horde Application Framework 3.1//EN
VERSION:2.0
METHOD:REQUEST
BEGIN:VEVENT
UID:$cal_uid
DTSTART:$dtstart
DTEND:$dtend
TRANSP:$disponibilite
SEQUENCE:0
ATTENDEE:
STATUS:$ics_status
DESCRIPTION:$meeting_description
SUMMARY:$subject
ORGANIZER;MAILTO:$mail
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR";
        return $ics;
    }
}

?>