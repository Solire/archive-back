<?php

namespace Slrfw\Datatable;


/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Board extends \Slrfw\Library\Datatable\Datatable {

    /**
     * Liste des gabarits
     *
     * @var array
     * @access protected
     */
    protected $_gabarits;

    /**
     * Utilisateur courant
     *
     * @var utilisateur
     * @access protected
     */
    protected $_utilisateur;

    public function datatableAction() {
        $fieldGabaritTypeKey = \Slrfw\Library\Tools::multidimensional_search($this->config["columns"], array("name" => "id_gabarit", "filter_field" => "select"));
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

    // --------------------------------------------------------------------

    /**
     * Défini l'utilisateur
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
    public function buildAction($data) {
        $actionHtml = '<div style="width:110px">';

        if (($this->_utilisateur != null && $this->_utilisateur->get("niveau") == "solire") || ($this->_gabarits != null && $this->_gabarits[$data["id_gabarit"]]["editable"])) {
            $actionHtml .= '<div class="btn-a btn-mini gradient-blue fl" ><a title="Modifier" href="page/display.html?id_gab_page=' . $data["id"] . '"><img alt="Modifier" src="img/back/white/pen_alt_stroke_12x12.png" /></a></div>';
        }
        if (($this->_utilisateur->get("niveau") == "solire" || $this->_gabarits[$data["id_gabarit"]]["make_hidden"] || $data["visible"] == 0) && $data["rewriting"] != "") {
            $actionHtml .= '<div class="btn-a btn-mini gradient-blue fl" ><a title="Rendre visible \'' . $data["titre"] . '\'" style="padding: 3px 7px 3px;"><input type="checkbox" value="' . $data["id"] . '-' . $data["id_version"] . '" class="visible-lang visible-lang-' . $data["id"] . '-' . $data["id_version"] . '" ' . ($data["visible"] > 0 ? ' checked="checked"' : '') . '/></a></div>';
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
    public function buildTraduit($data) {
        $actionHtml = '<div style="width:110px">';


        if ($data["rewriting"] == "") {
            $actionHtml .= '<div class="btn-a btn-mini gradient-red"><a style="color:white;line-height: 12px;" href="page/display.html?id_gab_page=' . $data["id"] . '">Non traduit</a></div>';
        } else {
            $actionHtml .= '<div class="btn-a btn-mini gradient-green"><a style="color:white;line-height: 12px;" href="page/display.html?id_gab_page=' . $data["id"] . '">Traduit</a></div>';
        }
        $actionHtml .= '</div>';
        return $actionHtml;
    }

}

?>
