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
        if (!is_null($this->date) or strlen($this->date)>6)
        {
            $datetext = $this->fonctions->nomjour($this->date) . " " . $this->fonctions->formatdate($this->date) . " : ";
        }
        // $htmltext = $htmltext ."<td class=celplanning style='border:1px solid black' bgcolor='" . $this->couleur() . "' title=\"" . $this->info() . "\" ></td>";
        
        /*
         * $htmltext = $htmltext . "<form name='frm_" . $this->date . "_" . $this->moment . "' method='post' >";
         * $htmltext = $htmltext . "<input type='hidden' name='agentid' value='" . $this->agentid . "'>";
         * $htmltext = $htmltext . "<input type='hidden' name='date' value='" . $this->date . "'>";
         * $htmltext = $htmltext . "<input type='hidden' name='moment' value='" . $this->moment . "'>";
         * foreach ($_POST as $keypost => $valeurpost)
         * $htmltext = $htmltext . "<input type='hidden' name='" . $keypost ."' value='" . $valeurpost . "'>";
         * if ($this->typeelement == "atten")
         * $htmltext = $htmltext ."<a href='javascript:frm_" . $this->date . "_" . $this->moment . ".submit();'>";
         *
         * '" . $this->date() . "'
         */
        if ($clickable)
            $clickabletext = "oncontextmenu=\"planning_rclick('" . $this->date() . "','" . $this->moment() . "');return false;\" onclick=\"planning_lclick('" . $this->date() . "','" . $this->moment() . "')\" ";
        else
            $clickabletext = "";
            
        if (! is_null($checkboxname))
            $checkboxtext = "<input type='checkbox' name='elmtcheckbox[" . $checkboxname . "]' value='1'>";
        else
            $checkboxtext = "";
        
        // ATTENTION : Ce cas arrive lorsque l'on veut déclarer un TP dans l'écran saisir_tpspartiel.php
        if (is_null($this->agentid))
        {
            $listeexclusion = array();
            $exclusion = false;
        }
        else
        {
          
/*            
            $agent = new agent($this->dbconnect);
            $agent->load($this->agentid());
            $listeexclusion = $agent->listejoursteletravailexclus($this->date(), $this->date());
*/
/*            
            if (array_search($this->fonctions->formatdatedb($this->date()),(array)$listeexclusion)===false)
            {   // On n'a pas trouvé la date dans la liste 
                $exclusion = false;
            }
            else
            {   // La date est dans liste des exclusions
                $exclusion = true;
            }
*/
            $exclusion = $this->fonctions->estjourteletravailexclu($this->agentid(), $this->date());
            
        }
        
        if ((strcasecmp($this->type(),'teletrav')==0 or $exclusion) and !$noiretblanc)  // On permet le double click si on est pas en N&B et (c'est du télétravail ou c'est une date exclue du télétravail)
        {
            $extraclass = ' teletravail ';
            if ($exclusion)
            {
                $extraclass = $extraclass . ' exclusion ';
            }
            if ($dbclickable)
                $clickabletext = $clickabletext . " id='" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "' ondblclick=\"dbclick_element('" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "','" . $this->agentid()  . "','" . $this->date() . "','" . $this->moment() . "');\" ";
            else
                $clickabletext = $clickabletext . "";
        }
        else
        {
            $extraclass = '';
        }
        
        if ($this->moment == fonctions::MOMENT_MATIN) {
            //echo "this->date = " . $this->date . "   date du jour : " . date("Ymd") . "<br>";
            // $htmltext = $htmltext ."<td class='planningelement_matin' " . $clickabletext . " bgcolor='" . $this->couleur() . "' title=\"" . $this->info() . "\" >" . $checkboxtext ."</td>";
            if ($this->date == date("Ymd")) {
                //echo "Le matin du jour " . $this->date . " <br>";
                $htmltext = $htmltext . "<td class='planningelement_jour_matin $extraclass' " . $clickabletext . "  bgcolor='" . $this->couleur($noiretblanc) . "' >";
            } else {
                $htmlbackcolor = $this->couleur($noiretblanc);
                if ($htmlbackcolor == self::COULEUR_HACHURE) 
                {
                    $htmltext = $htmltext . "<td class='planningelement_matin rayureplanning' " . $clickabletext . " >";
                } 
                else 
                {
                    $htmltext = $htmltext . "<td class='planningelement_matin $extraclass' " . $clickabletext . "  bgcolor='" . $htmlbackcolor . "' >";
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
            if (strlen($this->info()) != 0 and strcasecmp($this->parenttype(),'teletravHC')==0 and $noiretblanc) 
            {
                //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . "<br>";
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . chr(34) . ">";
                $spanactive = true;
            }
            
            if (strlen($this->info()) != 0 
                and $noiretblanc == false 
                or ($noiretblanc == true 
                    and !in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE)) and !$spanactive
                   )
               ) 
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . $this->info() . chr(34) . ">";
                $spanactive = true;
            }

            if ((strcasecmp($this->type(),'')==0 or in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE))) and !$spanactive and $datetext<>'') // Si on a une case vide => On affiche juste la date
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
                $htmltext = $htmltext . "<td class='planningelement_jour_aprem $extraclass' " . $clickabletext . "  bgcolor='" . $this->couleur($noiretblanc) . "' >";
            } else {
                $htmlbackcolor = $this->couleur($noiretblanc);
                if ($htmlbackcolor == self::COULEUR_HACHURE) {
                    $htmltext = $htmltext . "<td class='planningelement_aprem rayureplanning' " . $clickabletext . "  >";
                } else {
                    $htmltext = $htmltext . "<td class='planningelement_aprem $extraclass' " . $clickabletext . "  bgcolor='" . $this->couleur($noiretblanc) . "' >";
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
            if (strlen($this->info()) != 0 and strcasecmp($this->parenttype(),'teletravHC')==0 and $noiretblanc)
            {
                //echo "On va mettre le libellé du parent : " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . "<br>";
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . TABCOULEURPLANNINGELEMENT[$this->parenttype()]['libelle'] . chr(34) . ">";
                $spanactive = true;
            }
            
            if (strlen($this->info()) != 0
                and $noiretblanc == false
                or ($noiretblanc == true
                    and !in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE)) and !$spanactive
                    )
                )
            {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $datetext . " " . $this->info() . chr(34) . ">";
                $spanactive = true;
            }
            
            if ((strcasecmp($this->type(),'')==0 or in_array($this->couleur($noiretblanc), array(planningelement::COULEUR_HACHURE,planningelement::COULEUR_NOIRE, planningelement::COULEUR_VIDE))) and !$spanactive and $datetext<>'') // Si on a une case vide => On affiche juste la date
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
        /*
         * if ($this->typeelement == "atten")
         * $htmltext = $htmltext ."</a>";
         * $htmltext = $htmltext . "</form>";
         */
         return $htmltext;
    }
}

?>