<?php

namespace App\Back\Datatable;

/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Translate extends \Slrfw\Datatable\Datatable {

    public function start() {
        parent::start();
        $suf = $this->_db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="app/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

