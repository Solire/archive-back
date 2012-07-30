<?php

require_once 'main-controller.php';

class BoardController extends MainController {

    private $_cache = null;
    private $_config = null;

    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start() {
        parent::start();

        if (!$this->_appConfig->get("active", "board")) {
            exit();
        }
    }

    /**
     * 
     * @return void
     */
    public function startAction() {
        $this->_javascript->addLibrary("back/jquery/inettuts.js");
        $this->_css->addLibrary("back/inettuts.css");
        $this->_view->action = "board";
        
        $this->_boardDatatable();

        $idUtilisateur = $this->_utilisateur->get("id");
        $query = "SELECT board_state.cookie FROM `board_state` WHERE `board_state`.id_utilisateur = $idUtilisateur";
        $boardStateCookie = $this->_db->query($query)->fetchColumn();

        if ($boardStateCookie) {
            setcookie("inettuts-widget-preferences", urldecode($boardStateCookie), time() + 60 * 60 * 24 * 30);
        }



        $query = "SELECT `gab_gabarit`.id, count(DISTINCT gab_page.id) nbpages, `gab_gabarit`.* FROM `gab_gabarit` LEFT JOIN gab_page ON gab_page.id_gabarit = gab_gabarit.id AND gab_page.suppr = 0 WHERE `gab_gabarit`.id NOT IN (1,2) GROUP BY gab_gabarit.id ORDER BY gab_gabarit.id";
        $this->_gabarits2 = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        $pages = array();

        $colorWidget = array(
            "color-yellow",
            "color-red",
            "color-blue",
            "color-white",
            "color-orange",
            "color-green",
        );
        $indexColor = 0;
        $lastGabaritId = -1;

        foreach ($this->_gabarits2 as $gabarit) {


            $pagesMeta = $this->_gabaritManager->getList(BACK_ID_VERSION, false, $gabarit["id"], false, "date_crea", "desc", 0, 3);
            if (count($pagesMeta) == 0)
                continue;
            $pages[$gabarit["id"]]["gabarit"] = $gabarit;
            foreach ($pagesMeta as $pageMeta) {
                $pages[$gabarit["id"]]["pages"][] = $page = $this->_gabaritManager->getPage(BACK_ID_VERSION, $pageMeta->getMeta("id"));
            }

            $pagesMeta = $this->_gabaritManager->getList(BACK_ID_VERSION, false, $gabarit["id"], false, "date_modif", "desc", 0, 3);
            if (count($pagesMeta) == 0)
                continue;

            if ($gabarit["id_parent"] == $lastGabaritId)
                $indexColor--;
            $lastGabaritId = $gabarit["id"];

            $pages[$gabarit["id"]]["gabarit"] = $gabarit;
            if (!isset($colorWidget[$indexColor]))
                $indexColor = 0;
            $pages[$gabarit["id"]]["color"] = $colorWidget[$indexColor];

            $indexColor++;
            foreach ($pagesMeta as $pageMeta) {
                $pages[$gabarit["id"]]["pages_mod"][] = $page = $this->_gabaritManager->getPage(BACK_ID_VERSION, $pageMeta->getMeta("id"));
            }
        }
        $this->_view->pages = $pages;

        $this->_view->breadCrumbs[] = array(
            "label" => "Tableau de bord",
            "url" => "board/start.html",
        );
    }

    private function _boardDatatable() {
        $nameConfig = "board";
        if (file_exists("../app/datatable/" . ucfirst($nameConfig) . "Datatable.php")) {
            require_once "../app/datatable/" . ucfirst($nameConfig) . "Datatable.php";
            $datatableClassName = ucfirst($nameConfig) . "Datatable";
        } else {
            $datatableClassName = "Datatable";
        }
        $datatable = new $datatableClassName($_GET, $nameConfig, $this->_db, "./datatable/", "./datatable/", "images/datatable/");
        $datatable->setUtilisateur($this->_utilisateur);
        $datatable->setGabarits($this->_gabarits);
        
        $datatable->start();

        if (isset($_GET["json"]) || (isset($_GET["nomain"]) && $_GET["nomain"] == 1)) {
            echo $datatable;
            exit();
        }
        $this->_view->datatableRender = $datatable;
    }

    public function saveStateAction() {
        $this->_view->enable(false);
        $idUtilisateur = $this->_utilisateur->get("id");
        $cookieString = $this->_db->quote(urldecode($_POST["cookie"]));

        $this->_db->exec("REPLACE INTO board_state SET id_utilisateur=$idUtilisateur, cookie=$cookieString");
    }

    public function deleteStateAction() {
        $this->_view->enable(false);
        $idUtilisateur = $this->_utilisateur->get("id");

        $this->_db->exec("DELETE FROM board_state WHERE id_utilisateur=$idUtilisateur");
        setcookie("inettuts-widget-preferences", null, 0);
    }

}

//end class