<?php

namespace Slrfw\Datatable;


/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Redirection extends \Slrfw\Datatable\Datatable {

    public function start() {
        parent::start();
        $api = $this->_db->query("SELECT name FROM gab_api WHERE id = " . BACK_ID_API)->fetchColumn();
        $this->config["table"]["title"] .= ' <img width="16" src="app/back/img/back/api/' . strtolower($api) . '.png" />';
        $suf = $this->_db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="app/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

