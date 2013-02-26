<?php

namespace App\Back\Controller;


class Page extends Main
{
    /**
     *
     * @var \Slrfw\Gabarit\gabaritPage
     */
    private $_page = null;

    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();
    }

    /**
     * Liste les gabarits
     *
     * @return void
     */
    public function listeAction()
    {
        $this->_javascript->addLibrary('back/js/liste.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.ajaxqueue.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.scrollTo-min.js');

        $gabaritsList = array();
        $query = 'SELECT `gab_gabarit`.id, `gab_gabarit`.* '
               . 'FROM `gab_gabarit` '
               . 'WHERE `gab_gabarit`.`id_api` = ' . $this->_api['id'];

        //Si on a un fichier de conf
        $indexConfig = isset($_GET['c']) && intval($_GET['c']) ? intval($_GET['c']) : 0;
        $currentConfigPageModule = $this->_configPageModule[$indexConfig];

        $gabaritsList = $currentConfigPageModule['gabarits'];

        //Si on liste que certains gabarits
        if ($gabaritsList != '*' && count($gabaritsList) > 0) {
            $query .= ' AND id IN ( ' . implode(', ', $gabaritsList) . ')';
            //Permet de séparer les différents gabarits
            if (isset($_GET['gabaritByGroup'])) {
                $this->_view->gabaritByGroup = true;
                foreach ($gabaritsList as $gabariId) {
                    $this->_view->pagesGroup[$gabariId] = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], 0, $gabariId);
                }
            } else {
                $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], 0, $gabaritsList);
                $this->_view->pagesGroup[0] = 1;
            }
        } else {
            $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], 0);
            $this->_view->pagesGroup[0] = 1;
        }

        $this->_gabarits = $this->_db->query($query)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        $this->getButton($currentConfigPageModule);

        $this->_view->gabarits = $this->_db->query($query)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        $this->_view->pages = $this->_pages;

        $this->_view->breadCrumbs[] = array(
            'label' => $currentConfigPageModule['label'],
            'url'   => 'page/liste.html',
        );
    }

    /**
     *
     * @return void
     */
    public function childrenAction()
    {
        $this->_view->main(FALSE);
        $this->_pages = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], $_REQUEST['id_parent']);
        if (count($this->_pages) == 0) {
            exit();
        }
        $this->_view->pages = $this->_pages;

        $query  = 'SELECT `gab_gabarit`.id, `gab_gabarit`.*'
                . ' FROM `gab_gabarit`'
                . ' WHERE `gab_gabarit`.`id_api` = ' . $this->_api["id"];
        $this->_gabarits = $this->_db->query($query)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        $this->_view->gabarits = $this->_gabarits;
    }

    /**
     *
     * @return void
     */
    public function displayAction()
    {
        $this->_javascript->addLibrary('back/js/tiny_mce/tiny_mce.js');

        $this->_javascript->addLibrary('back/js/autocomplete.js');
        $this->_javascript->addLibrary('back/js/plupload/plupload.full.min.js');
        $this->_javascript->addLibrary('back/js/formgabarit.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.tipsy.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.qtip.min.js');
        $this->_javascript->addLibrary('back/js/affichegabarit.js');
        $this->_javascript->addLibrary('back/js/join-simple.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.autogrow.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.dataTables.min.js');
        $this->_javascript->addLibrary('back/js/jquery/jcrop/jquery.Jcrop.min.js');
        $this->_javascript->addLibrary('back/js/jquery/ui.spinner.min.js');
        $this->_javascript->addLibrary('back/js/autocomplete_multi/jquery.tokeninput.js');
        $this->_javascript->addLibrary('back/js/autocomplete_multi.js');
        $this->_javascript->addLibrary('back/js/compareversion.js');

        $this->_css->addLibrary('back/css/jcrop/jquery.Jcrop.min.css');
        $this->_css->addLibrary('back/css/ui.spinner.css');
        $this->_css->addLibrary('back/css/demo_table_jui.css');
        $this->_css->addLibrary('back/css/tipsy.css');
        $this->_css->addLibrary('back/css/jquery.qtip.min.css');
        $this->_css->addLibrary('back/css/autocomplete_multi/token-input.css');
        $this->_css->addLibrary('back/css/autocomplete_multi/token-input-facebook.css');

        $this->_css->addLibrary('back/css/affichegabarit.css');


        $id_gab_page = isset($_GET['id_gab_page']) ? $_GET['id_gab_page'] : 0;
        $id_gabarit = isset($_GET['id_gabarit']) ? $_GET['id_gabarit'] : 1;

        $this->_view->upload_path = $this->_mainConfig->get('path', 'upload');
        $this->_view->action = 'liste';

        $this->_form = '';
        $this->_pages = array();
        $this->_redirections = array();

        if ($id_gab_page) {
            $query = 'SELECT * FROM `version` WHERE `id_api` = ' . $this->_api['id'];
            $this->_versions = $this->_db->query($query)->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);

            foreach ($this->_versions as $id_version => $version) {
                $page = $this->_gabaritManager->getPage($id_version, BACK_ID_API, $id_gab_page);
                $this->_pages[$id_version] = $page;

                $path = $page->getMeta('rewriting') . $page->getGabarit()->getExtension();
                foreach ($page->getParents() as $parent) {
                    $path = $parent->getMeta('rewriting') . '/' . $path;
                }

                $query  = 'SELECT `old` FROM `redirection` WHERE `new` LIKE ' . $this->_db->quote($path);
                $this->_redirections[$id_version] = $this->_db->query($query)->fetchAll(\PDO::FETCH_COLUMN);
            }
        } else {
            $query = 'SELECT * FROM `version` WHERE `id` = ' . BACK_ID_VERSION;
            $this->_versions = $this->_db->query($query)->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);

            $page = $this->_gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, 0, $id_gabarit);
            $this->_pages[BACK_ID_VERSION] = $page;
            $this->_redirections[BACK_ID_VERSION] = array();
        }

        $this->_view->versions = $this->_versions;
        $this->_view->pages = $this->_pages;
        $this->_view->redirections = $this->_redirections;

        /**
         * On recupere la sous rubrique de page a laquelle il appartient
         * pour le breadCrumbs et le lien retour
         */
        $found = false;
        foreach ($this->_configPageModule as $index => $currentConfigPageModule) {
            /**
             * Si le gabarit courant appartien à un des groupes personnalisés
             */
            if ($currentConfigPageModule['gabarits'] == '*'
                || in_array($this->_page->getGabarit()->getId(), $currentConfigPageModule['gabarits'])
            ) {
                $indexPageList = $index;
                $found = true;
                break;
            }

            if ($found) {
                break;
            }
        }

        if ($found) {
            $this->_view->breadCrumbs[] = array(
                'label' => $this->_configPageModule[$indexPageList]['label'],
                'url'   => 'page/liste.html?c=' . $indexPageList,
            );
        } else {
            $this->_view->breadCrumbs[] = array(
                'label' => 'Liste des pages',
                'url'   => 'page/liste.html',
            );
        }

        $this->_view->breadCrumbs[] = array(
            'label' => 'Gestion des pages',
            'url'   => '',
        );

        $this->getButton($currentConfigPageModule);
    }

    /**
     *
     * @return void
     */
    public function saveAction()
    {
        $this->_view->main(FALSE);
        $this->_view->enable(FALSE);

        if (isset($_GET["edit-front"]) && $_GET["edit-front"] == 1) {

            $dataRaw = json_decode($_POST["content"], true);
            $data = array(
                "id_version"    =>  $dataRaw["id_version"]["value"],
                "id_gab_page"    =>  $dataRaw["id_gab_page"]["value"],
                "id_api"    =>  $dataRaw["id_api"]["value"],
            );
            $page = $this->_gabaritManager->getPage($dataRaw["id_version"]["value"], $dataRaw["id_api"]["value"], $dataRaw['id_gab_page']["value"], 0);
            $pageSave = false;

            foreach ($dataRaw as $k => $d) {
                $val = isset($d["value"]) ? $d["value"] : false;
                if ($val === false) {
                    if (isset($d["attributes"]["src"])) {
                        $filePathPart = explode("/", $d["attributes"]["src"]);
                        $val = $filePathPart[1];
                    }
                }

                if ($val !== false) {

                    if (strpos($k, "-") !== false) {
                        $fieldPart = explode('-', $k);
                        if (!isset($data[$fieldPart[0]])) {
                            $data[$fieldPart[0]] = array();
                        }


                        $blocTableName = $fieldPart[2];
                        $idBlocLine = $fieldPart[1];
                        $idChamp = substr($fieldPart[0], 5);

                        if (!isset($data['id_' . $blocTableName])) {
                            $data['id_' . $blocTableName] = array();
                        }
                        $data['id_' . $blocTableName][$idBlocLine] = $idBlocLine;

                        $data[$fieldPart[0]][] = $val;
                    } else {
                        if (substr($k, 0, 5) == "champ") {
                            $pageSave = true;
                            $data[$k] = array(
                                $val
                            );
                        }
                    }
                }
            }

            if ($pageSave) {
                $this->_gabaritManager->savePage($page, $data, true);
            }

            $blocs = $page->getBlocs();
            foreach ($blocs as $bloc) {
                $this->_gabaritManager->saveBloc($bloc, $dataRaw['id_gab_page']["value"], $dataRaw["id_version"]["value"], $data, true);
            }
            exit("1");
        }



        $this->_page = $this->_gabaritManager->save($_POST);

        $contenu    = '<a href="' . \Slrfw\Library\Registry::get('basehref')
                    . 'page/display.html?id_gab_page='
                    . $this->_page->getMeta('id') . '">'
                    . $this->_page->getMeta('titre') . '</a>';

        $headers    = 'From: ' . \Slrfw\Library\Registry::get('mail-contact') . "\r\n"
                    . 'Reply-To: ' . \Slrfw\Library\Registry::get('mail-contact') . "\r\n"
                    . 'Bcc: contact@solire.fr ' . "\r\n"
                    . 'X-Mailer: PHP/' . phpversion();

        $typeSave = $_POST['id_gab_page'] == 0 ? 'Création' : 'Modification';

        \Slrfw\Tools::mail_utf8('Modif site <modif@solire.fr>',
            $typeSave . ' de contenu sur ' . $this->_mainConfig->get('name', 'project'),
            $contenu, $headers, 'text/html');

        $json = array(
            'status'        => 'success',
            'search'        => '?id_gab_page=' . $this->_page->getMeta("id") . '&popup=more',
            'id_gab_page'   => $this->_page->getMeta("id"),
        );

        if (isset($_POST['id_temp']) && $_POST['id_temp']) {
            $upload_path = $this->_mainConfig->get('path', 'upload');

            $tempDir    = '../' . $upload_path . DIRECTORY_SEPARATOR . 'temp-' . $_POST['id_temp'];
            $targetDir  = '../' . $upload_path . DIRECTORY_SEPARATOR . $this->_page->getMeta("id");

            $succes = rename($tempDir, $targetDir);

            $query  = 'UPDATE `media_fichier` SET'
                    . ' `id_gab_page` = ' . $this->_page->getMeta('id') . ','
                    . ' `id_temp` = 0'
                    . ' WHERE `id_temp` = ' . $_POST['id_temp'];
            $this->_db->exec($query);
        }


        if($json["status"] == "error") {
            $this->_log->logThis(   "$typeSave de page échouée",
                                    $this->_utilisateur->get("id"),
                                    "<b>Id</b> : " . $this->_page->getMeta("id") . '<br /><img src="app/back/img/flags/png/' . strtolower($this->_versions[$_POST["id_version"]]['suf']) . '.png" alt="'
                                    . $this->_versions[$_POST["id_version"]]['nom'] . '" /></a><br /><span style="color:red;">Error</span>');
        } else {
            $this->_log->logThis(   "$typeSave de page réussie",
                                    $this->_utilisateur->get("id"),
                                    "<b>Id</b> : " . $this->_page->getMeta("id") . '<br /><img src="app/back/img/flags/png/' . strtolower($this->_versions[$_POST["id_version"]]['suf']) . '.png" alt="'
                                    . $this->_versions[$_POST["id_version"]]['nom'] . '" /></a>');
        }

        exit(json_encode($json));
    }

    /**
     *
     * @return void
     */
    public function autocompleteAction()
    {
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
    public function autocompleteJoinAction()
    {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $json = array();
        $term = $_REQUEST["term"];
        $idField = $_REQUEST["id_field"];
        $idGabPage = $_REQUEST["id_gab_page"];
        $queryFilter = str_replace("[ID]", $idGabPage, $_REQUEST["query_filter"]);
        $table = $_REQUEST["table"];
        $labelField = "";
        $lang = $_REQUEST["id_version"];
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

        $sql = "SELECT `$table`.$idField id, $labelField label"
                . " FROM `$table`"
                . " $gabPageJoin"
                . " WHERE $filterVersion "
                . ($queryFilter != "" ? "AND (" . $queryFilter . ")" : "")
                . " AND $labelField  LIKE '%$term%'";

        $json = $this->_db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        exit(json_encode($json));
    }

    /**
     *
     * @return void
     */
    public function autocompleteOldLinksAction()
    {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $json = array();
        $term = $_REQUEST["term"];
        $table = "old_link";
        $labelField = "`$table`.`link`";


        $sql = "SELECT $labelField label"
                . " FROM `$table`"
                . " WHERE $labelField  LIKE '%$term%'";

        $json = $this->_db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        exit(json_encode($json));
    }

    /**
     *
     * @return void
     */
    public function liveSearchAction()
    {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $pages = array();


        $qSearch = isset($_GET["term"]) ? $_GET["term"] : "";

        /*
         * Traitement de la chaine de recherche
         */

        $searchTab = array();

        //Variable qui contient la chaine de recherche
        $stringSearch = strip_tags(trim($qSearch));
        $this->filter->stringSearch = $stringSearch;

        //Si un seul mot
        if (strpos($stringSearch, " ") === false) {
            $searchTab[0] = $stringSearch;
        } else {
            //Si plusieurs  mots on recupere un tableau de mots
            $searchTab = preg_split("#[ ]+#", $stringSearch);
        }

        //Tableau de mot(s)
        $this->filter->words = $searchTab;

        //On teste si un mot est supérieurs à 3 caractères
        $this->filter->errors["len_words"] = true;
        for ($i = 0, $I = count($this->filter->words); $i < $I; $i++) {
            if (trim($this->filter->words[$i]) != "" && strlen(trim($this->filter->words[$i])) >= 2) {
                $this->filter->errors["len_words"] = false;
            }
        }

        if ($this->filter->errors["len_words"]) {
            echo json_encode(null);
            return;
        }

        //Pour chaque mot ou essaie de mettre au singulier ou pluriel
        // + Traitement de la chaine de recherche (elimine mot trop court
        $mode[] = "s";
        $mode[] = "p";
        $i = 0;
        foreach ($this->filter->words as $t1) {
            foreach ($mode as $m) {
                if (strlen($t1) >= 2) {
                    $this->filter->wordsAdvanced[$i++] = (($m == "s") ? $this->singulier($t1) : $this->pluriel($t1));
                }
            }
        }

        //Tri des mots par strlen
        if (is_array($this->filter->wordsAdvanced))
            usort($this->filter->wordsAdvanced, array($this, "length_cmp"));

        if ($qSearch != null) {
            $filterWords[] = 'CONCAT(" ", gab_page.titre, " ") LIKE ' . $this->_db->quote("%" . $this->filter->stringSearch . "%");

            if (isset($this->filter->wordsAdvanced) && is_array($this->filter->wordsAdvanced) && count($this->filter->wordsAdvanced) > 0)
                foreach ($this->filter->wordsAdvanced as $word) {
                    $filterWords[] = 'CONCAT(" ", gab_page.titre, " ") LIKE ' . $this->_db->quote("%" . $word . "%");
                }

            foreach ($filterWords as $filterWord) {
                $orderBy[] = "IF($filterWord , 0, 1)";
            }
        }

        $query  = "SELECT `gab_page`.`id` id, gab_page.titre label, gab_page.titre visible, gab_gabarit.label gabarit_label,  CONCAT('page/display.html?id_gab_page=', `gab_page`.`id`) url"
                . " FROM `gab_page`"
                . " LEFT JOIN `gab_gabarit`"
                . "     ON `gab_page`.id_gabarit = `gab_gabarit`.id"
                . "     AND `gab_gabarit`.editable = 1"
                . " WHERE `gab_page`.`id_version` = " . BACK_ID_VERSION
                . " AND `gab_gabarit`.`id_api` = " . $this->_api["id"]
                . " AND `gab_page`.`suppr` = 0 "
                . (isset($filterWords) ? " AND (" . implode(" OR ", $filterWords) . ")" : '')
                . " ORDER BY " . implode(",", $orderBy) . " LIMIT 10";

        $pagesFound = $this->_db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($pagesFound as $page) {
            $pages[] = array(
                "label" => \Slrfw\Tools::highlightedSearch($page["label"], $this->filter->wordsAdvanced, true),
                "id" => $page["id"],
                "gabarit_label" => $page["gabarit_label"],
                "url" => $page["url"],
            );
        }

        exit(json_encode($pages));
    }

    /**
     *
     * @return void
     */
    public function autocompleteLinkAction()
    {
        header('content-type: application/x-javascript; charset=UTF-8');
        $json = file_get_contents($this->_url . '../sitemap.xml?visible=0&json=1&onlylink=1');
        exit('var tinyMCELinkList = ' . $json . ';');
    }

    /**
     *
     * @return void
     */
    public function visibleAction()
    {
        $this->_view->enable(FALSE);

        $json = array('status' => 'error');

        $idVersion = BACK_ID_VERSION;

        if (isset($_POST['id_version']) && $_POST['id_version'] > 0) {
            $idVersion = intval($_POST['id_version']);
        }

        if (is_numeric($_POST['id_gab_page']) && is_numeric($_POST['visible'])) {
            $query = "UPDATE `gab_page` SET `visible` = " . $_POST['visible'] . " WHERE id_version =  " . $idVersion . " AND `id` = " . $_POST['id_gab_page'];
            if ($this->_db->query($query)) {
                $type = $_POST['visible'] == 1 ? "Page rendu visible" : "Page rendu invisible";
                $this->_log->logThis("$type avec succès", $this->_utilisateur->get("id"), "<b>Id</b> : " . $_POST['id_gab_page'] . '<br /><img src="app/back/img/flags/png/' . strtolower($this->_versions[$idVersion]['suf']) . '.png" alt="'
                        . $this->_versions[$idVersion]['nom'] . '" />');
                $json['status'] = "success";
                $json['debug'] = $query;
            } else {
                $this->_log->logThis("$type échouée", $this->_utilisateur->get("id"), "<b>Id</b> : " . $_POST['id_gab_page'] . '<br /><img src="app/back/img/flags/png/' . strtolower($this->_versions[$idVersion]['suf']) . '.png" alt="'
                        . $this->_versions[$idVersion]['nom'] . '" /><br /><span style="color:red;">Error</span>');
            }
        }


        exit(json_encode($json));
    }

    /**
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_view->enable(FALSE);

        $json = array('status' => "error");

        if (is_numeric($_POST['id_gab_page'])) {
            $query = "UPDATE `gab_page` SET `suppr` = 1, `date_modif` = NOW() WHERE `id` = " . $_POST['id_gab_page'];
            $json['query'] = $query;
            if ($this->_db->exec($query)) {
                $this->_log->logThis("Suppression de page réussie", $this->_utilisateur->get("id"), "<b>Id</b> : " . $_POST['id_gab_page']);
                $json['status'] = "success";
            } else {
                $this->_log->logThis("Suppression de page échouée", $this->_utilisateur->get("id"), "<b>Id</b> : " . $_POST['id_gab_page'] . '<br /><span style="color:red;">Error</span>');
            }
        }

        exit(json_encode($json));
    }

    /**
     *
     * @return void
     */
    public function orderAction()
    {
        $ok = true;

        $this->_view->main(FALSE);
        $this->_view->enable(FALSE);

        $prepStmt = $this->_db->prepare("UPDATE `gab_page` SET `ordre` = :ordre WHERE `id` = :id");
        foreach ($_POST['positions'] as $id => $ordre) {
            $prepStmt->bindValue(":ordre", $ordre, \PDO::PARAM_INT);
            $prepStmt->bindValue(":id", $id, \PDO::PARAM_INT);
            $tmp = $prepStmt->execute();
            if ($ok) {
                $ok = $tmp;
                $this->_log->logThis("Changement d'ordre réalisé avec succès", $this->_utilisateur->get("id"), "<b>Id</b> : " . $id . '<br />'
                        . "<b>Ordre</b> : " . $ordre . '<br />');
            } else {
                $this->_log->logThis("Changement d'ordre échoué", $this->_utilisateur->get("id"), "<b>Id</b> : " . $id
                        . "<b>Ordre</b> : " . $ordre . '<br />'
                        . '<br /><span style="color:red;">Error</span>');
            }
        }

        echo $ok ? 'Succès' : 'Echec';

        return FALSE;
    }

    protected function getButton($currentConfigPageModule)
    {
        //Liste des début de label à regrouper pour les boutons de création
        $groupIdentifications = array("Rubrique ", "Sous rubrique ", "Page ");
        foreach ($this->_gabarits as $gabarit) {
            $found = false;

            $gabaritsGroup = array(
                "label" => $gabarit["label"],
            );

            //Si utilisateur standart à le droit de créer ce type de gabarit ou si utilisateur solire
            if ($gabarit["creable"] || $this->_utilisateur->get("niveau") == "solire") {

                //Si on a un regroupement des boutons personnalisés dans le fichier de config
                if (isset($currentConfigPageModule["boutons"]) && isset($currentConfigPageModule["boutons"]["groups"])) {
                    foreach ($currentConfigPageModule["boutons"]["groups"] as $customGroup) {
                        //Si le gabarit courant appartien à un des groupes personnalisés
                        if (in_array($gabarit["id"], $customGroup["gabarits"])) {
                            $gabaritsGroup = array(
                                "label" => $customGroup["label"],
                            );
                            $found = true;
                            break;
                        }
                    }
                }

                //On parcourt les Début de label à regrouper
                if ($found == false) {
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
    }

    protected function singulier($mot)
    {
        return (substr($mot, -1) == "s") ? substr($mot, 0, -1) : $mot;
    }

    protected function pluriel($mot)
    {
        return (substr($mot, -1) == "s") ? $mot : ($mot . 's');
    }

    protected function length_cmp($a, $b)
    {
        return strlen($b) - strlen($a);
    }

}

