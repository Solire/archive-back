<?php

namespace App\Back\Datatable;


/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Board extends \Slrfw\Datatable\Datatable {

    /**
     * Liste des gabarits
     *
     * @var array
     * @access protected
     */
    protected $_gabarits;
    
    /**
     * Liste des versions
     *
     * @var array
     * @access protected
     */
    protected $_versions;
    
    /**
     * versions courantes
     *
     * @var array
     * @access protected
     */
    protected $_currentVersion;

    /**
     * Utilisateur courant
     *
     * @var utilisateur
     * @access protected
     */
    protected $_utilisateur;
    
    public function start() {
        
        foreach ($this->_versions as $version) {
            if (BACK_ID_VERSION == $version["id"]) {
                $this->_currentVersion = $version;
                break;
            }
        }
        parent::start();
    }
    
    protected function beforeRunAction() {
        parent::beforeRunAction();
        if (count($this->_versions) == 1) {
            unset($this->config["columns"][count($this->config["columns"]) - 2]);
        }
    }

    public function datatableAction() {
        $fieldGabaritTypeKey = \Slrfw\Tools::multidimensional_search($this->config["columns"], array("name" => "id_gabarit", "filter_field" => "select"));
        foreach ($this->_gabarits as $gabarit) {
            $idsGabarit[] = $gabarit["id"];
        }
        $this->config["columns"][$fieldGabaritTypeKey]["filter_field_where"] = "id IN  (" . implode(",", $idsGabarit) . ")";

        parent::datatableAction();
    }

    /**
     * Défini l'utilisateur
     *
     * @param utilisateur $utilisateur Utilisateur courant
     * @return void
     */
    public function setUtilisateur($utilisateur) {
        $this->_utilisateur = $utilisateur;
    }
    
    /**
     * Défini les versions
     *
     * @param array $versions versions disponibles
     * @return void
     */
    public function setVersions($versions) {
        $this->_versions = $versions;
    }

    // --------------------------------------------------------------------

    /**
     * Défini les gabarits
     *
     * @param array $gabarits tableau des gabarits
     * @return void
     */
    public function setGabarits($gabarits) {
        $this->_gabarits = $gabarits;
    }

    // --------------------------------------------------------------------

    /**
     * Construit la colonne d'action
     *
     * @param array $data Ligne courante de donnée
     * @return string Html des actions
     */
    public function buildAction(&$data) {
        $actionHtml = '<div class="btn-group">';

        if (($this->_utilisateur != null 
                && $this->_utilisateur->get("niveau") == "solire") 
                || ($this->_gabarits != null 
                && $this->_gabarits[$data["id_gabarit"]]["editable"])) {
            $actionHtml .= '<a href="back/page/display.html?id_gab_page=' . $data["id"] . '" class="btn btn-small btn-info" title="Modifier en version : ' . $this->_currentVersion["nom"] .  '">
                                <i class="icon-pencil"></i>
                            </a>';
        }
        if (($this->_utilisateur->get("niveau") == "solire" 
                || $this->_gabarits[$data["id_gabarit"]]["make_hidden"] 
                || $data["visible"] == 0) 
                && $data["rewriting"] != "") {
            $actionHtml .= '<a class="btn btn-small btn-info visible-lang"  title="Rendre \'' . $data["titre"] . '\' ' . ($data["visible"] > 0 ? 'invisible' : 'visible' ) . ' sur le site">
                                <input type="checkbox" value="' . $data["id"] . '|' . $data["id_version"] . '" style="display:none;" class="visible-lang-' . $data["id"] . '-' . $data["id_version"] . '" ' . ($data["visible"] > 0 ? ' checked="checked"' : '') . '/>
                                <i class="' . ($data["visible"] > 0 ? 'icon-eye-open' : 'icon-eye-close translucide') . '"></i>
                            </a>';
        }

        if($data["suppr"] == 1) {
            $actionHtml = '<a href="#" class="btn btn-small btn-warning supprimer" title="Supprimer">
                                <i class="icon-eye-open"></i>
                           </a>';
        }

        $actionHtml .= '</div>';
        return $actionHtml;
    }

    // --------------------------------------------------------------------

    /**
     * Construit la colonne de traduction
     *
     * @param array $data Ligne courante de donnée
     * @return string Html de traduction
     */
    public function buildTraduit(&$data) {
        if($data["suppr"] == 1) {
            return "";
        }
        $actionHtml = '<div style="width:110px">';
        
        $pages = $this->_db->query(""
                . "SELECT id_version, rewriting "
                . "FROM gab_page "
                . "WHERE id = " . $data["id"])->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE);

        foreach ($this->_versions as $version) {
            if ($pages[$version["id"]] == "") {
                continue;
            }
            $actionHtml .= '<img ' . ($pages[$version["id"]] == "" ? 'title="' . $version['nom'] . ' : Non traduit"  class="grayscale"' : 'title="' . $version['nom'] . ' : Traduit"') . ' src="app/back/img/flags/png/' . strtolower($version['suf']) . '.png" alt="' . $version['nom'] . '" />';
            $actionHtml .= '&nbsp;' ;
        }
        
        $actionHtml .= '</div>';
        

          
        
        return $actionHtml;
    }


    // --------------------------------------------------------------------

    /**
     * Permet de gérer les pages supprimer (Visuel + action)
     *
     * @param array $aRow Ligne courante de toutes les données (ASSOC)
     * @param array $rowAssoc Ligne courante des données affiché (ASSOC)
     * @param array $row Ligne courante de donnée affiché (NUM)
     * @return void
     */
    public function disallowDeleted($aRow, $rowAssoc, &$row) {
        $row["DT_RowClass"] = "";
        if ($aRow["suppr"] == 1) {
            $keyAction = array_search("visible_1", array_keys($rowAssoc));
            $row[$keyAction] = '<div class="btn btn-small btn-danger disabled" >Supprimée</div>';
            $row["DT_RowClass"] = "translucide";
        }
    }
}

