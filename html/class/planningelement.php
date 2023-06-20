<?php

class planningelement
{

    const COULEUR_VIDE = '#FFFFFF';

    const COULEUR_WE = '#999999';

//    const COULEUR_NON_DECL = '#775420';

    const COULEUR_NOIRE = '#424242';
 // /'#000000';
    const COULEUR_HACHURE = '#1C1C1C';
 // '#2E2E2E';
    
    const HTML_CLASS_EXCLUSION = ' exclusion ';
    const HTML_CLASS_TELETRAVAIL = ' teletravail ';
    const HTML_CLASS_TELETRAVAIL_HIDDEN = ' teletravail_cache ';
    const HTML_CLASS_DEPLACE = ' deplace ';

    const JAVA_CLASS_TELETRAVAIL_HIDDEN = 'teletravail_hidden';
    
    private $date = null;

    private $moment = null;
 // 'm' pour matin / 'a' pour après-midi
    private $typeelement = null;
 // WE, congé, absence, férié...
    private $info = null;

    private $couleur = null;

    private $dbconnect = null;

    private $statut = null;

    private $agentid = null;

    private $demandeid = null;
    
    private $demande = null;
    
    private $typeconvention = null;
    
    private $htmlextraclass = '';

    private $fonctions = null;

    function __construct($db)
    {
        $this->dbconnect = $db;
        if (is_null($this->dbconnect)) {
            $errlog = "PlanningElement->construct : La connexion à la base de donnée est NULL !!!";
            echo $errlog . "<br/>";
            error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
        }
        $this->fonctions = new fonctions($db);
    }

    function date($date = null)
    {
        if (is_null($date)) {
            if (is_null($this->date)) {
                $errlog = "PlanningElement->date : La date n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->fonctions->formatdate($this->date);
        } else
            $this->date = $this->fonctions->formatdatedb($date);
    }

    function moment($moment = null)
    {
        if (is_null($moment)) {
            if (is_null($this->moment)) {
                $errlog = "PlanningElement->moment : Le moment n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->moment;
        } else
            $this->moment = $moment;
    }

    function type($type = null)
    {
        if (is_null($type)) {
            if (is_null($this->typeelement)) {
                $errlog = "PlanningElement->type : Le type n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->typeelement;
        } else
            $this->typeelement = $type;
    }

    function info($info = null)
    {
        if (is_null($info)) {
            if (is_null($this->info)) {
                $errlog = "PlanningElement->info : L'info n'est pas définie !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->info;
        } elseif (strcasecmp((string)$this->statut, demande::DEMANDE_ATTENTE) != 0)
            $this->info = $info;
            elseif (strcasecmp((string)$this->statut, demande::DEMANDE_ATTENTE) == 0)
            $this->info = $this->info . "  " . $info;
        else {
            // echo "PlanningElement->info : Le statut est '" . demande::DEMANDE_ATTENTE . "' ==> On ne modifie pas l'info <br>";
        }
    }

    function agentid($id = null)
    {
        if (is_null($id)) {
            if (is_null($this->agentid)) {
                $errlog = "PlanningElement->agentid : L'Id de l'agent n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->agentid;
        } else
            $this->agentid = $id;
    }

    function demandeid($id = null)
    {
        if (is_null($id)) {
            return $this->demandeid;
        } else {
            $this->demandeid = $id;
        }
    }
    
    
    /**
    *
    * @param string|object $demandeobj
    *            L'objet demande associé à l'élément
    *            Chaine vide pour supprimer l'objet demande associé l'élément
    * @return mixed
    *            Si le paramètre $demandeobj est null et que l'objet demande est défini, retourne l'objet demande
    *            Si le paramètre $demandeobj est null et que l'objet demande n'est pas défini, retourne null
    *            Si le paramètre $demandeobj n'est pas null, aucune valeur n'est retournée
    */
    function demande($demandeobj = null)
    {
        if (is_null($demandeobj)) 
        {
            return $this->demande;
        } 
        elseif (is_string($demandeobj) and $demandeobj == '')
        {
            $this->demande = null;
        }
        else
        {
            $this->demande = $demandeobj;
        }
    }

    function typeconvention($typeconvention = null)
    {
        if (is_null($typeconvention)) {
            return $this->typeconvention;
        } else {
            $this->typeconvention = $typeconvention;
        }
    }
    
    function htmlextraclass($htmlextraclass = null)
    {
        if (is_null($htmlextraclass)) {
            return $this->htmlextraclass;
        } else {
            $this->htmlextraclass = $htmlextraclass;
        }
    }
    
    function parenttype()
    {
        if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid']))
        {
            //$errlog = "PlanningElement->parenttype : Le parent pour le type de congé " . $this->typeelement . " est dans le tableau => " . TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid'];
            //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            return TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid'];
        }
        else
        {
            return "";
        }

/*
            //$errlog = "PlanningElement->parenttype : Le parent pour le type de congé " . $this->typeelement . " n'est pas dans le tableau";
            //error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            $parenttype = "";
            $sql = "SELECT ABSENCEIDPARENT FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
            // echo "sql = " . $sql . " <br>";
            $params = array($this->typeelement);
            $query = $this->fonctions->prepared_select($sql, $params);
            
            $erreur = mysqli_error($this->dbconnect);
            if ($erreur != "") {
                $errlog = "PlanningElement->parenttype : " . $erreur;
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->stripAccents($errlog));
            }
            else if (mysqli_num_rows($query) == 0)
            {
                $errlog = "PlanningElement->parenttype : Le parent pour le type de congé " . $this->typeelement . " non trouvé";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            }
            else
            {
                $result = mysqli_fetch_row($query);
                $parenttype = "$result[0]";
            }
            return $parenttype;
        }
*/                
    }

    function couleur($noiretblanc = false)
    {
        // Si la date se situe dans le passé et qu'on affiche en noir et blanc sauf si c'est un WE ou un jour férier alors met la case achurée
        // ==> Evite que les agents "surveillent" si un autre agent à bien posé des congés dans le passé....
        if (is_null($this->date) == false) {
            if (($this->fonctions->formatdatedb($this->date()) < date('Ymd')) and ($noiretblanc == true) and ($this->typeelement != 'ferie') and (strcasecmp($this->typeelement, "WE") != 0)) // $this->typeelement == "" or '
            {
                // return self::COULEUR_NOIRE;
                return self::COULEUR_HACHURE;
            }
        }
        if ($this->typeelement == "")
        {
            return self::COULEUR_VIDE;
        }
//        elseif (strcasecmp($this->typeelement, "nondec") == 0)
//        {
//            return self::COULEUR_NON_DECL;
//        }
        elseif (strcasecmp($this->typeelement, "WE") == 0)
        {
            return self::COULEUR_WE;
        }
        // if ($this->typeelement != 'ferie' and $this->typeelement != 'teletrav' )  // and $this->typeelement != 'tppar')
        if (strcasecmp($this->typeelement, "ferie") != 0 and strcasecmp($this->typeelement, "teletrav") != 0 and strcasecmp($this->typeelement, "nondec") != 0)
        {
            if (strcasecmp($this->parenttype(),'teletravHC')==0) // Si le type du parent de l'element est teletravHC
            {
                // Même si on doit afficher l'élément en N&B, les élémenet dont le parent est 'teletravHC' doivent être affiché en couleur
                $noiretblanc = false; 
            }
            // Si on est en N&B et si la demande en attente et qu'on dispose de quoi identifier la demande d'origine => On va vérifier le type de la demande
            elseif (strcasecmp($this->type(),'atten')==0 and ($this->demandeid().'' != '' or !is_null($this->demande)) and $noiretblanc)
            {
                if (!is_null($this->demande))
                {
                    $demande = $this->demande;
                    //var_dump("La demande existe => " . $demande->id());
                }
                else
                {
                    $demande = new demande($this->dbconnect);
                    $demande->load($this->demandeid());
                    $this->demande = $demande;
                    //var_dump("La demande est chargée à partir de l'id  => " . $demande->id());
                }
                $demandeparenttype = TABCOULEURPLANNINGELEMENT[$demande->type()]['parentid'];
                //  Si la demande est de type télétravail HC, on ne l'affiche pas car l'agent travaille
                if (strcasecmp($demandeparenttype,'teletravHC')==0)
                {
                    return self::COULEUR_VIDE;
                }
            }
            
            if ($noiretblanc == true)
                return self::COULEUR_NOIRE;
        }
        if (is_null($this->couleur))
        {
            // Si le tableau des couleurs des elements du planning est défini et que le type de l'élément existe
            if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$this->typeelement]['couleur']))
            {
                // On prend la couleur définie dans le tableau TABCOULEURPLANNINGELEMENT
                $this->couleur = TABCOULEURPLANNINGELEMENT[$this->typeelement]['couleur'];
            }
            else   // On n'a pas trouvé la couleur de l'élément dans le tableu => Donc on la charge depuis la base de données
            {   
                //echo "<br>Couleur de l'element est NULL !! <br>";
                $sql = "SELECT TYPEABSENCEID,COULEUR FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
                $params = array($this->typeelement);
                $query = $this->fonctions->prepared_select($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "PlanningElement->couleur : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                if (mysqli_num_rows($query) == 0) {
                    $errlog = "PlanningElement->couleur : La couleur pour le type de congé " . $this->typeelement . " non trouvée";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                $result = mysqli_fetch_row($query);
                $this->couleur = $result[1];
            }
        }
        else
        {
            //echo "<br>Couleur de l'element n'est pas NULL !! <br>";
        }
        return $this->couleur;
    }

    function statut($statut = null)
    {
        if (is_null($statut)) {
            if (is_null($this->statut)) {
                $errlog = "PlanningElement->statut : Le statut n'est pas défini !!!";
                echo $errlog . "<br/>";
                error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
            } else
                return $this->statut;
        } else {
            $this->statut = $statut;
            if (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) == 0) {
                $this->type("atten");
                $sql = "SELECT TYPEABSENCEID,LIBELLE FROM TYPEABSENCE WHERE TYPEABSENCEID = ?";
                $params = array($this->typeelement);
                $query = $this->fonctions->prepared_select($sql, $params);
                $erreur = mysqli_error($this->dbconnect);
                if ($erreur != "") {
                    $errlog = "PlanningElement->statut : " . $erreur;
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                if (mysqli_num_rows($query) == 0) {
                    $errlog = "PlanningElement->statut : Le libellé pour le type de congé " . $this->typeelement . " non trouvé";
                    echo $errlog . "<br/>";
                    error_log(basename(__FILE__) . " " . $this->fonctions->stripAccents($errlog));
                }
                $result = mysqli_fetch_row($query);
                $this->info = $result[1] . " : " . $this->info;
            }
        }
    }

    function html($clickable = FALSE, $checkboxname = null, $noiretblanc = false, $dbclickable = FALSE)
    {
        $htmltext = "";
        $datetext = "";
        $datadatefr = "";
        if (!is_null($this->date) or strlen($this->date)>6)
        {
            $datetext = $this->fonctions->nomjour($this->date) . " " . $this->fonctions->formatdate($this->date) . " : ";
            $datadatefr = " data-datefr='" .  $this->fonctions->formatdate($this->date) . "' ";
        }
        if ($clickable)
            $clickabletext = "oncontextmenu=\"planning_rclick('" . $this->date() . "','" . $this->moment() . "');return false;\" onclick=\"planning_lclick('" . $this->date() . "','" . $this->moment() . "')\" ";
        else
            $clickabletext = "";
            
        if (! is_null($checkboxname))
            $checkboxtext = "<input type='checkbox' name='elmtcheckbox[" . $checkboxname . "]' value='1'>";
        else
            $checkboxtext = "";
        
        // Si on est en affichage N&B et que c'est une convention médicale alors on modifie l'info à afficher avec juste 'Teletravail'
        if ($this->typeconvention()===teletravail::CODE_CONVENTION_MEDICAL and $noiretblanc)
        {
            //$this->info('Télétravail');
            if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$this->typeelement]['libelle']))
            {
                $this->info(TABCOULEURPLANNINGELEMENT[$this->typeelement]['libelle']);
            }
        }
        
        //$extraclass = '';        
        $extraclass = $this->htmlextraclass();
        $exclusion = (stripos($this->htmlextraclass(), planningelement::HTML_CLASS_EXCLUSION)!==false);
        $deplace = (stripos($this->htmlextraclass(), planningelement::HTML_CLASS_DEPLACE)!==false);
        if ((strcasecmp($this->type(),'teletrav')==0 or $exclusion) and !$noiretblanc)  // On permet le double click si on est pas en N&B et (c'est du télétravail ou c'est une date exclue du télétravail)
        {
            
            // Le fait que se soit une convention 'médicale' est pris en charge par le script de déplacement/annulation
            // Donc on ne traite pas ici le test " and $this->typeconvention()!==teletravail::CODE_CONVENTION_MEDICAL"
            // Si l'élément est déplacé on ne permet pas le dbclick 
            if ($dbclickable and !$deplace)
            {
                $clickabletext = $clickabletext . " id='" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "' ondblclick=\"dbclick_element('" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "','" . $this->agentid()  . "','" . $this->date() . "','" . $this->moment() . "','" . $this->typeconvention() . "');\" ";
            }
            else
            {
                $clickabletext = $clickabletext . "";
            }
        }
        
        if ($this->moment == fonctions::MOMENT_MATIN) {
            //echo "this->date = " . $this->date . "   date du jour : " . date("Ymd") . "<br>";
            // $htmltext = $htmltext ."<td class='planningelement_matin' " . $clickabletext . " bgcolor='" . $this->couleur() . "' title=\"" . $this->info() . "\" >" . $checkboxtext ."</td>";
            if ($this->date == date("Ymd")) {
                //echo "Le matin du jour " . $this->date . " <br>";
                $htmltext = $htmltext . "<td class='planningelement_jour_matin $extraclass' " . $clickabletext . " $datadatefr bgcolor='" . $this->couleur($noiretblanc) . "' >";
            } else {
                $htmlbackcolor = $this->couleur($noiretblanc);
                if ($htmlbackcolor == self::COULEUR_HACHURE) 
                {
                    $htmltext = $htmltext . "<td class='planningelement_matin rayureplanning' " . $clickabletext . " $datadatefr >";
                } 
                else 
                {
                    $htmltext = $htmltext . "<td class='planningelement_matin $extraclass' " . $clickabletext . " $datadatefr bgcolor='" . $htmlbackcolor . "' >";
                }
            }
            $spanactive = false;
/*
            if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid']) and strlen($this->info()) != 0 )
            {
                if (TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid'] == 'teletravHC' and $noiretblanc)
                {
                    //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT['teletravHC']['libelle'] . "<br>";
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . TABCOULEURPLANNINGELEMENT['teletravHC']['libelle'] . chr(34) . ">";
                    $spanactive = true;
                }
            }
*/
            // S'il y a une info lié à l'élément, que le type du parent de l'element est teletravHC et que l'affichage est en N&B
            // ==> On affiche le type du parent 'Teletravail hors convention'
            $demandeparenttype = '';
/*            
            if (strcasecmp($this->type(),'atten')==0 and ($this->demandeid().'' != '' or !is_null($this->demande)))
            {
                if (!is_null($this->demande))
                {
                    $demande = $this->demande;
                }
                else
                {
                    $demande = new demande($this->dbconnect);
                    $demande->load($this->demandeid());
                    $this->demande = $demande;
                }
                $demandeparenttype = TABCOULEURPLANNINGELEMENT[$demande->type()]['parentid'];
                //var_dump($demandeparenttype);
            }
*/            
            if (strlen($this->info()) != 0 and (strcasecmp($this->parenttype(),'teletravHC')==0 or strcasecmp($demandeparenttype,'teletravHC')==0) and $noiretblanc) 
            {
                //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . "<br>";
                if ($demandeparenttype != '')
                {
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$demandeparenttype]['libelle'] . chr(34) . ">";                    
                }
                else
                {
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . chr(34) . ">";
                }
                $spanactive = true;                
            }
            elseif (strlen($this->info()) != 0 
                and $noiretblanc == false 
                or ($noiretblanc == true 
                    and !in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE)) and !$spanactive
                   )
               ) 
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . $this->info() . chr(34) . ">";
                $spanactive = true;
            }
            elseif ((strcasecmp($this->type(),'')==0 or in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE))) and !$spanactive and $datetext<>'') // Si on a une case vide => On affiche juste la date
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . str_replace(" : ","",$datetext)  . chr(34) . ">";
                $spanactive = true;
            }
            
            if (strlen($checkboxtext) != 0)
                $htmltext = $htmltext . $checkboxtext;
            else
                $htmltext = $htmltext . "&nbsp;";
            
            if ($spanactive) 
            {
                $htmltext = $htmltext . "</span>";
            }
            $htmltext = $htmltext . "</td>";
        } else {
            // $htmltext = $htmltext ."<td class='planningelement_aprem' " . $clickabletext . " bgcolor='" . $this->couleur() . "' title=\"" . $this->info() . "\" >" . $checkboxtext ."</td>";
            if ($this->date == date("Ymd")) {
                // echo "Le soir du jour " . $this->date . " <br>";
                $htmltext = $htmltext . "<td class='planningelement_jour_aprem $extraclass' " . $clickabletext . " $datadatefr bgcolor='" . $this->couleur($noiretblanc) . "' >";
            } else {
                $htmlbackcolor = $this->couleur($noiretblanc);
                if ($htmlbackcolor == self::COULEUR_HACHURE) {
                    $htmltext = $htmltext . "<td class='planningelement_aprem rayureplanning' " . $clickabletext . " $datadatefr >";
                } else {
                    $htmltext = $htmltext . "<td class='planningelement_aprem $extraclass' " . $clickabletext . " $datadatefr bgcolor='" . $this->couleur($noiretblanc) . "' >";
                }
            }
            $spanactive = false;
/*            
            if (defined('TABCOULEURPLANNINGELEMENT') and isset(TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid']) and strlen($this->info()) != 0 )
            {
                if (TABCOULEURPLANNINGELEMENT[$this->typeelement]['parentid'] == 'teletravHC' and $noiretblanc)
                {
                    //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT['teletravHC']['libelle'] . "<br>";
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . TABCOULEURPLANNINGELEMENT['teletravHC']['libelle'] . chr(34) . ">";
                    $spanactive = true;
                }
            }
*/
            $demandeparenttype = '';
/*            
            if (strcasecmp($this->type(),'atten')==0 and ($this->demandeid().'' != '' or !is_null($this->demande)))
            {
                if (!is_null($this->demande))
                {
                    $demande = $this->demande;
                }
                else
                {
                    $demande = new demande($this->dbconnect);
                    $demande->load($this->demandeid());
                    $this->demande = $demande;
                }
                $demandeparenttype = TABCOULEURPLANNINGELEMENT[$demande->type()]['parentid'];
                // var_dump($demandeparenttype);
            }
*/            
            // S'il y a une info lié à l'élément, que le type du parent de l'element est teletravHC et que l'affichage est en N&B
            // ==> On affiche le type du parent 'Teletravail hors convention'
            if (strlen($this->info()) != 0 and (strcasecmp($this->parenttype(),'teletravHC')==0 or strcasecmp($demandeparenttype,'teletravHC')==0) and $noiretblanc) 
            {
                //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . "<br>";
                if ($demandeparenttype != '')
                {
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$demandeparenttype]['libelle'] . chr(34) . ">";                    
                }
                else
                {
                    $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . chr(34) . ">";
                }
                $spanactive = true;
            }
            elseif (strlen($this->info()) != 0
                and $noiretblanc == false
                or ($noiretblanc == true
                    and !in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE)) and !$spanactive
                    )
                )
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . $this->info() . chr(34) . ">";
                $spanactive = true;
            }
            elseif ((strcasecmp($this->type(),'')==0 or in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE))) and !$spanactive and $datetext<>'') // Si on a une case vide => On affiche juste la date
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . str_replace(" : ","",$datetext)  . chr(34) . ">";
                $spanactive = true;
            }
            
            if (strlen($checkboxtext) != 0)
                $htmltext = $htmltext . $checkboxtext;
            else
                $htmltext = $htmltext . "&nbsp;";
            if ($spanactive)
            {
                $htmltext = $htmltext . "</span>";
            }
            $htmltext = $htmltext . "</td>";
        }
         return $htmltext;
    }
}

?>