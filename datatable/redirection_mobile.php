<?php

namespace Slrfw\Datatable;


/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Redirection_mobile extends \Slrfw\Library\Datatable\Datatable {
/** @todo Changer le nom de Redirection_mobile pour qu'il respect la notation camel */
    public function start() {
        parent::start();
        $suf = $this->_db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="app/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

?>
