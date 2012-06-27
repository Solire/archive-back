<?php

require_once 'main-controller.php';

class PageController extends MainController {

    private $_cache = null;

    /**
     *
     * @var gabarit_page
     */
    private $_page = null;

    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start() {
        parent::start();
    }

//end start()

    /**
     * 
     * @return void
     */
    public function listeAction() {
        $this->_javascript->addLibrary("back/liste.js");

        $gabaritsList = array();

        if ($this->_utilisateur->get("niveau") == "solire")
            $query = "SELECT `gab_gabarit`.id, `gab_gabarit`.* FROM `gab_gabarit`";
        else
            $query = "SELECT `gab_gabarit`.id, `gab_gabarit`.* FROM `gab_gabarit`";


        //Si on a un fichier de conf
        if (isset($_GET["config"])) {
            $config = Registry::get('mainconfig');
            require_once($config->get('back', 'dirs') . "gabarit/" . $_GET["config"] . ".cfg.php");
            $gabConfig = $config;
            $gabaritsList = $gabConfig["gabarit"];
        }

        //Si on a une liste de gabarit dans l'url
        if (isset($_GET["gabarit"])) {
            $gabaritsList = $_GET["gabarit"];
        }




        //Si on liste que certains gabarits
        if (count($gabaritsList) > 0) {
            $query .= " WHERE id IN ( " . implode(", ", $gabaritsList) . ")";
            //Permet de séparer les différents gabarits
            if (isset($_GET["gabaritByGroup"])) {
                $this->_view->gabaritByGroup = true;
                foreach ($gabaritsList as $gabariId) {
                    $this->_view->pagesGroup[$gabariId] = $this->_gabaritManager->getList(BACK_ID_VERSION, 0, $gabariId);
                }
            } else {
                $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, 0, $gabaritsList);
                $this->_view->pagesGroup[0] = 1;
            }
        } else {
            $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, 0);
            $this->_view->pagesGroup[0] = 1;
        }



        $this->_gabarits = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        //Liste des début de label à regrouper pour les boutons de création
        $groupIdentifications = array("Rubrique ", "Sous rubrique ", "Page ");
        foreach ($this->_gabarits as $gabarit) {
            $gabaritsGroup = array(
                "label" => $gabarit["label"],
            );

            //Si utilisateur standart à le droit de créer ce type de gabarit ou si utilisateur solire
            if ($gabarit["creable"] || $this->_utilisateur->get("niveau") == "solire") {
                //On parcourt les Début de label à regrouper
                $found = false;
                foreach ($groupIdentifications as $groupIdentification) {
                    if (preg_match("/^$groupIdentification/", $gabarit["label"])) {
                        $gabaritsGroup = array(
                            "label" => $groupIdentification,
                        );
                        $gabarit["label"] = ucfirst(trim(preg_replace("#^" . $groupIdentification . "#", "", $gabarit["label"])));
                        $found = true;
                        break;
                    }
                }
                $gabaritsGroup["gabarit"][] = $gabarit;
                if (!$found) {
                    $gabaritsGroup["label"] = "";
                    $this->_view->gabaritsBtn[] = $gabaritsGroup;
                } else {

                    if (isset($this->_view->gabaritsBtn[md5($gabaritsGroup["label"])]))
                        $this->_view->gabaritsBtn[md5($gabaritsGroup["label"])]["gabarit"][] = $gabarit;
                    else
                        $this->_view->gabaritsBtn[md5($gabaritsGroup["label"])] = $gabaritsGroup;
                }
            }
        }


        $this->_view->gabarits = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        $this->_view->pages = $this->_pages;

        $this->_view->breadCrumbs[] = array(
            "label" => "Liste des pages",
            "url" => "page/liste.html",
        );
    }

    /**
     * 
     * @return void
     */
    public function childrenAction() {
        $this->_view->main(FALSE);
        $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, $_REQUEST['id_parent']);
        if (count($this->_pages) == 0)
            exit();
        $this->_view->pages = $this->_pages;

        $query = "SELECT `gab_gabarit`.id, `gab_gabarit`.* FROM `gab_gabarit`";

        $this->_gabarits = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        $this->_view->gabarits = $this->_gabarits;
    }

    /**
     * 
     * @return void
     */
    public function displayAction() {
        $upload_path = $this->_mainConfig->get("path", "upload");

        $id_gab_page = isset($_GET['id_gab_page']) ? $_GET['id_gab_page'] : 0;
        $id_gabarit = isset($_GET['id_gabarit']) ? $_GET['id_gabarit'] : 1;

        $this->_view->action = "liste";

        $this->_javascript->addLibrary("back/tiny_mce/tiny_mce.js");
        $this->_javascript->addLibrary("back/jquery/jquery.livequery.min.js");
        $this->_javascript->addLibrary("back/autocomplete.js");
        $this->_javascript->addLibrary("back/plupload/plupload.full.min.js");
        $this->_javascript->addLibrary("back/formgabarit.js");
        $this->_javascript->addLibrary("back/jquery/jquery.tipsy.js");
        $this->_javascript->addLibrary("back/jquery/jquery.qtip.min.js");
        $this->_javascript->addLibrary("back/affichegabarit.js");

        $this->_javascript->addLibrary("back/autocomplete_multi/jquery.tokeninput.js");
        $this->_javascript->addLibrary("back/autocomplete_multi.js");

        $this->_css->addLibrary("back/tipsy.css");
        $this->_css->addLibrary("back/jquery.qtip.min.css");
        $this->_css->addLibrary("back/autocomplete_multi/token-input.css");
        $this->_css->addLibrary("back/autocomplete_multi/token-input-facebook.css");

        $this->_form = '';

        if ($id_gab_page) {
            $versions = $this->_db->query("SELECT * FROM `version`")->fetchAll(PDO::FETCH_ASSOC);
            $form = '';
            $devant = '';
            foreach ($versions as $version) {
                $page = $this->_gabaritManager->getPage($version['id'], $id_gab_page);



                $devant .= '<div style="height: 50px;float: left;">'
                        . '<div class="btn gradient-blue" style="margin-bottom: 5px;display:block;"><a title="' . $version['nom'] . '" class="openlang' . ($version['id'] == BACK_ID_VERSION ? ' active' : ' translucide') . '">Langue : <img src="img/flags/png/' . strtolower($version['suf']) . '.png" alt="'
                        . $version['nom'] . '" /></a></div>';

                if ($page->getMeta("rewriting") != "") {
                    if ($page->getGabarit()->getMake_hidden()
                            || $this->_utilisateur->get("niveau") == "solire"
                            || !$page->getMeta("visible")
                    ) {
                        $devant .= '<div style="margin-left: 6px;margin-top: -4px;"><label style="color:#A1A1A1;text-shadow:none;margin-left:10px;" for="visible-'
                                . $version['id'] . '">Visible : </label><input class="visible-lang" value="'
                                . $page->getMeta("id") . '|' . $version['id'] . '" id="visible-' . $version['id'] . '" style="" '
                                . ($page->getMeta("visible") ? 'checked="checked"' : '') . ' type="checkbox" /></div>';
                    }
                } else {
                    $devant .= '<span class="notification gradient-red" style="margin-left: 6px;">Non traduit</span>';
                }

                $devant .= '</a></div>';






                $form .= '<div class="langue" style="clear:both;' . ($version['id'] == BACK_ID_VERSION ? '' : ' display:none;')
                        . '"><div class="clearin"></div>'
                        . $page->getForm("page/save.html", "page/liste.html", $upload_path, FALSE, $page->getGabarit()->getMeta())
                        . '</div>';
            }

            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, $id_gab_page);

            $this->_form .= '<div>' . $devant . '</div>' . $form;
        } else {
            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, 0, $id_gabarit);

            $form = $this->_page->getForm("page/save.html", "page/liste.html", $upload_path, FALSE, $this->_page->getGabarit()->getMeta());
            $this->_form = $form;
        }

        $this->_view->page = $this->_page;
        $this->_view->form = $this->_form;

        $this->_view->breadCrumbs[] = array(
            "label" => "Liste des pages",
            "url" => "page/liste.html",
        );

        $this->_view->breadCrumbs[] = array(
            "label" => "Gestion des pages",
            "url" => "",
        );
    }

    /**
     * 
     * @return void
     */
    public function saveAction() {
        $this->_view->main(FALSE);
        $this->_view->enable(FALSE);

        $this->_page = $this->_gabaritManager->save($_POST);

        $contenu = '<a href="' . Registry::get("basehref") . 'page/display.html?id_gab_page='
                . $this->_page->getMeta("id") . '">'
                . $this->_page->getMeta("titre") . '</a>';

        $headers = "From: " . Registry::get("mail-contact") . "\r\n"
                . "Reply-To: " . Registry::get("mail-contact") . "\r\n"
                . "Bcc: contact@solire.fr \r\n"
                . "X-Mailer: PHP/" . phpversion();

        Tools::mail_utf8("Modif site <modif@solire.fr>", "Modification de contenu sur " . Registry::get("site"), $contenu, $headers);

        $json = array(
            "status" => $this->_page ? "success" : "error",
            "search" => "?id_gab_page=" . $this->_page->getMeta("id"),
            "id_gab_page" => $this->_page->getMeta("id"),
        );

        exit(json_encode($json));
    }

    /**
     * 
     * @return void
     */
    public function autocompleteAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $json = array();
        $dejaLiees = is_array($_REQUEST['deja']) ? $_REQUEST['deja'] : array();

        if (!isset($_REQUEST['id_gabarit']) || !is_numeric($_REQUEST['id_gabarit']))
            exit(json_encode($json));

        $pages = $this->_gabaritManager->getSearch(BACK_ID_VERSION, $_GET['term'], $_REQUEST['id_gabarit']);
        foreach ($pages as $page) {
            if (!in_array($page->getMeta('id'), $dejaLiees))
                $json[] = array("value" => $page->getMeta('id'), "label" => $page->getMeta('titre'), "visible" => $page->getMeta('titre'));
        }

        exit(json_encode($json));
    }

    /**
     * 
     * @return void
     */
    public function autocompleteJoinAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $json = array();
        $term = $_REQUEST["term"];
        $idField = $_REQUEST["id_field"];
        $idGabPage = $_REQUEST["id_gab_page"];
        $queryFilter = str_replace("[ID]", $idGabPage, $_REQUEST["query_filter"]);
        $table = $_REQUEST["table"];
        $labelField = "";
        $lang = BACK_ID_VERSION;
        $gabPageJoin = "";


        $filterVersion = "`$table`.id_version = $lang";
        if (isset($_REQUEST["no_version"]) && $_REQUEST["no_version"] == 1) {
            $filterVersion = 1;
        } else {
            $gabPageJoin = "INNER JOIN gab_page ON visible = 1 AND suppr = 0 AND gab_page.id = `$table`.$idField " . ($filterVersion != 1 ? "AND gab_page.id_version = $lang" : "");
        }



        if (substr($_REQUEST["label_field"], 0, 9) == "gab_page.") {
            $labelField = $_REQUEST["label_field"];
        } else {
            $labelField = "`$table`.`" . $_REQUEST["label_field"] . "`";
        }

        $sql = "SELECT `$table`.$idField id, $labelField label
                    FROM `$table` 
                    $gabPageJoin
                    WHERE $filterVersion " . ($queryFilter != "" ? "AND (" . $queryFilter . ")" : "") . " AND $labelField  LIKE '%$term%'";

        $json = $this->_db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        exit(json_encode($json));
    }

    public function autocompleteLinkAction() {
        header("content-type: application/x-javascript; charset=UTF-8");
        $json = file_get_contents($this->_url . "../sitemap.xml?visible=0&json=1&onlylink=1");
        exit("var tinyMCELinkList = " . $json . ";");
    }

    /**
     * 
     * @return void
     */
    public function visibleAction() {
        $this->_view->enable(FALSE);

        $json = array('status' => "error");

        $idVersion = BACK_ID_VERSION;

        if (isset($_POST["id_version"]) && $_POST["id_version"] > 0) {
            $idVersion = intval($_POST["id_version"]);
        }

        if (is_numeric($_POST['id_gab_page']) && is_numeric($_POST['visible'])) {
            $query = "UPDATE `gab_page` SET `visible` = " . $_POST['visible'] . " WHERE id_version =  " . $idVersion . " AND `id` = " . $_POST['id_gab_page'];
            if ($this->_db->query($query)) {
                $json['status'] = "success";
                $json['debug'] = $query;
            }
        }


        exit(json_encode($json));
    }

    /**
     * 
     * @return void
     */
    public function deleteAction() {
        $this->_view->enable(FALSE);

        $json = array('status' => "error");

        if (is_numeric($_POST['id_gab_page'])) {
            $query = "UPDATE `gab_page` SET `suppr` = 1, `date_modif` = NOW() WHERE `id` = " . $_POST['id_gab_page'];
            $json['query'] = $query;
            if ($this->_db->exec($query)) {
                $json['status'] = "success";
            }
        }

        exit(json_encode($json));
    }

    /**
     * 
     * @return void
     */
    public function orderAction() {
        $ok = true;

        $this->_view->main(FALSE);
        $this->_view->enable(FALSE);

        $prepStmt = $this->_db->prepare("UPDATE `gab_page` SET `ordre` = :ordre WHERE `id` = :id");
        foreach ($_POST['positions'] as $id => $ordre) {
            $prepStmt->bindValue(":ordre", $ordre, PDO::PARAM_INT);
            $prepStmt->bindValue(":id", $id, PDO::PARAM_INT);
            $tmp = $prepStmt->execute();
            if ($ok)
                $ok = $tmp;
        }

        echo $ok ? 'Succès' : 'Echec';

        return FALSE;
    }

}

//end class