<?php

class planningelement
{

    const COULEUR_VIDE = '#FFFFFF';

    const COULEUR_WE = '#999999';

    const COULEUR_NON_DECL = '#775420';

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
        } elseif (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) != 0)
            $this->info = $info;
        elseif (strcasecmp($this->statut, demande::DEMANDE_ATTENTE) == 0)
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
            return self::COULEUR_VIDE;
        elseif (strcasecmp($this->typeelement, "nondec") == 0)
            return self::COULEUR_NON_DECL;
        elseif (strcasecmp($this->typeelement, "WE") == 0)
            return self::COULEUR_WE;
        if ($this->typeelement != 'ferie') // $this->typeelement <> 'tppar' and
        {
            if ($noiretblanc == true)
                return self::COULEUR_NOIRE;
        }
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
        return $result[1];
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
        //$this->fonctions->time_elapsed("Début de la fonction html",0,true);
        $htmltext = "";
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
            //$this->fonctions->time_elapsed("Avant le load agent - bloc complet (" . $this->agentid() . ")",1,true);
            //$this->fonctions->time_elapsed("Avant le load agent (" . $this->agentid() . ")",2,true);
          
/*            
            $agent = new agent($this->dbconnect);
            $agent->load($this->agentid());
            $listeexclusion = $agent->listejoursteletravailexclus($this->date(), $this->date());
*/
            //$this->fonctions->time_elapsed("Après le load agent (" . $this->agentid() . ")",2);
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
            
            //$this->fonctions->time_elapsed("Après le load agent - bloc complet (" . $this->agentid() . ")",1);
        }
        if (($this->type() == 'teletrav' or $exclusion) and !$noiretblanc)  // On permet le double click si on est pas en N&B et (c'est du télétravail ou c'est une date exclue du télétravail)
        {
            $extraclass = ' teletravail '; 
            if ($dbclickable)
                $clickabletext = $clickabletext . " id='" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "' ondblclick=\"dbclick_element('" . $this->agentid() . "_" . $this->fonctions->formatdatedb($this->date()) . "_" . $this->moment()  . "','" . $this->agentid()  . "','" . $this->date() . "','" . $this->moment() . "');\" ";
            else
                $clickabletext = $clickabletext . "";
        }
        else
        {
            $extraclass = '';
        }
        
        if ($this->moment == 'm') {
            // $htmltext = $htmltext ."<td class='planningelement_matin' " . $clickabletext . " bgcolor='" . $this->couleur() . "' title=\"" . $this->info() . "\" >" . $checkboxtext ."</td>";
            if ($this->date == date("Ymd")) {
                // echo "Le matin du jour " . $this->date . " <br>";
                $htmltext = $htmltext . "<td class='planningelement_jour_matin $extraclass' " . $clickabletext . "  bgcolor='" . $this->couleur($noiretblanc) . "' >";
            } else {
                $htmlbackcolor = $this->couleur($noiretblanc);
                if ($htmlbackcolor == self::COULEUR_HACHURE) {
                    $htmltext = $htmltext . "<td class='planningelement_matin rayureplanning' " . $clickabletext . " >";
                } else {
                    $htmltext = $htmltext . "<td class='planningelement_matin $extraclass' " . $clickabletext . "  bgcolor='" . $htmlbackcolor . "' >";
                }
            }
            if (strlen($this->info()) != 0 and $noiretblanc == false) {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $this->info() . chr(34) . ">";
            }
            if (strlen($checkboxtext) != 0)
                $htmltext = $htmltext . $checkboxtext;
            else
                $htmltext = $htmltext . "&nbsp;";
            if (strlen($this->info()) != 0) {
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
            if (strlen($this->info()) != 0 and $noiretblanc == false) {
                $htmltext = $htmltext . "<span data-tip=" . chr(34) . $this->info() . chr(34) . ">";
            }
            if (strlen($checkboxtext) != 0)
                $htmltext = $htmltext . $checkboxtext;
            else
                $htmltext = $htmltext . "&nbsp;";
            if (strlen($this->info()) != 0) {
                $htmltext = $htmltext . "</span>";
            }
            $htmltext = $htmltext . "</td>";
        }
        /*
         * if ($this->typeelement == "atten")
         * $htmltext = $htmltext ."</a>";
         * $htmltext = $htmltext . "</form>";
         */
         //$this->fonctions->time_elapsed("Fin de la fonction html",0);
         return $htmltext;
    }
}

?>