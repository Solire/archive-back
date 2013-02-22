<?php

namespace App\Back\Controller;


class Media extends Main {

    /**
     *
     * @var page
     */
    private $_page = null;

    public function start() {
        parent::start();

        $upload = $this->_mainConfig->get("upload");
        $this->_upload_path = $upload['path'];
        $this->_upload_temp = $upload['temp'];
        $this->_upload_vignette = $upload['vignette'];
        $this->_upload_apercu = $upload['apercu'];
    }

    /**
     * Affichage du gestionnaire de fichiers
     *
     * @return void
     */
    public function startAction()
    {

        $extensionsImage = array('jpeg', 'jpg', 'png', 'gif');

        $this->_javascript->addLibrary('back/js/jquery/jquery.hotkeys.js');
        $this->_javascript->addLibrary('back/js/jstree/jquery.jstree.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.dataTables.min.js');
        $this->_javascript->addLibrary('back/js/plupload/plupload.full.min.js');
        $this->_javascript->addLibrary('back/js/listefichiers.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.scroller-1.0.min.js');

        $this->_css->addLibrary('back/css/demo_table_jui.css');
        $this->_css->addLibrary('back/css/jquery.scroller.css');

        $this->_view->breadCrumbs[] = array(
            'label' => 'Gestion des fichiers',
            'url' => '',
        );
    }

    public function listAction() {
        $this->_view->main(FALSE);
        $this->_files = array();

        $id_gab_page = isset($_REQUEST['id_gab_page']) && $_REQUEST['id_gab_page'] ? $_REQUEST['id_gab_page'] : 0;

        if ($id_gab_page) {
            $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
            $orderby = isset($_REQUEST['orderby']['champ']) ? $_REQUEST['orderby']['champ'] : '';
            $sens = isset($_REQUEST['orderby']['sens']) ? $_REQUEST['orderby']['sens'] : '';

            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $id_gab_page);

            $this->_files = $this->_fileManager->getList($this->_page->getMeta("id"), 0, $search, $orderby, $sens);
        }

        foreach ($this->_files as &$file) {
            $ext = strtolower(array_pop(explode(".", $file['rewriting'])));
            $prefixPath = $this->_api["id"] == 1 ? ".." . DIRECTORY_SEPARATOR : ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
            $file['path'] = $prefixPath . $this->_upload_path . DIRECTORY_SEPARATOR
                    . $file['id_gab_page'] . DIRECTORY_SEPARATOR
                    . $file['rewriting'];

            $serverpath = ".." . DIRECTORY_SEPARATOR . $this->_upload_path . DIRECTORY_SEPARATOR
                    . $file['id_gab_page'] . DIRECTORY_SEPARATOR
                    . $file['rewriting'];

            $file['class'] = 'hoverprevisu vignette';

            if (array_key_exists($ext, \Slrfw\Gabarit\fileManager::$_extensions['image'])) {
                $file['path_mini'] = $prefixPath . $this->_upload_path . DIRECTORY_SEPARATOR
                        . $file['id_gab_page'] . DIRECTORY_SEPARATOR
                        . $this->_upload_vignette . DIRECTORY_SEPARATOR
                        . $file['rewriting'];

                $sizes = getimagesize($serverpath);
                $file['class'] .= '  img-polaroid';
                $file['width'] = $sizes[0];
                $file['height'] = $sizes[1];
            } else {
                $file['class'] = 'vignette';
                $file['path_mini'] = "img/filetype/$ext.png";
            }

//            $file['poids'] = (round((100 * $file['taille']) / (8 * 1024)) / 100) . " Ko";
            $file['poids'] = \Slrfw\Library\Tools::format_taille($file['taille']);
        }

        $this->_view->files = $this->_files;
    }

    public function popuplistefichiersAction() {
        $this->listAction();
    }

    public function folderlistAction() {
        $this->_view->main(FALSE);
        $this->_view->enable(FALSE);

        $res = array();

        if ($_REQUEST['id'] === "") {
            $res[] = array(
                "attr" => array(
                    "id" => "node_0",
                    "rel" => "root"
                ),
                "data" => array(
                    "title" => "Ressources"
                ),
                "state" => "closed"
            );
        } elseif ($_REQUEST['id'] === "0") {
            $rubriques = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], 0);
            foreach ($rubriques as $rubrique)
                $res[] = array(
                    "attr" => array(
                        "id" => "node_" . $rubrique->getMeta('id'),
                        "rel" => "page"
                    ),
                    "data" => array(
                        "title" => '<div class="horizontal_scroller" style="width:150px;height: 17px; cursor: pointer;"><div class="scrollingtext" style="left: 0px;">' . $rubrique->getMeta('titre') . '</div></div>'
                    ),
                    "state" => "closed"
                );
        } else {
            $sous_rubriques = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api["id"], $_REQUEST['id']);

            foreach ($sous_rubriques as $sous_rubrique) {
                $nbre = $this->_db->query("SELECT COUNT(*) FROM `media_fichier` WHERE `suppr` = 0 AND `id_gab_page` = " . $sous_rubrique->getMeta('id'))->fetchColumn();

                $res[] = array(
                    "attr" => array(
                        "id" => "node_" . $sous_rubrique->getMeta('id'),
                        "rel" => "page"
                    ),
                    "data" => array(
                        "title" => '<div class="horizontal_scroller" style="width:100px;height: 17px; cursor: pointer;"><div class="scrollingtext" style="left: 0px;">' . ( strlen($sous_rubrique->getMeta('titre')) > 16 ? mb_substr($sous_rubrique->getMeta('titre'), 0, 16, "utf-8") . "&hellip;" : $sous_rubrique->getMeta('titre') ) . " (<i>$nbre</i>)" . '</div></div>',
                        "attr" => array(
                            "title" => $sous_rubrique->getMeta('titre')
                        )
                    ),
                    "state" => "closed"
                );
            }
        }

        echo json_encode($res);
    }

    public function uploadAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $id_gab_page = isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] ? $_COOKIE['id_gab_page'] : 0;

        $prefixPath = $this->_api["id"] == 1 ? "" : ".." . DIRECTORY_SEPARATOR;

        if ($id_gab_page) {
//            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $id_gab_page);
//            if ($this->_page) {
            $targetTmp = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $this->_upload_temp;
            $targetDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page;
            $vignetteDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page . DIRECTORY_SEPARATOR . $this->_upload_vignette;
            $apercuDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page . DIRECTORY_SEPARATOR . $this->_upload_apercu;

            $json = $this->_fileManager->uploadGabPage($id_gab_page, 0, $targetTmp, $targetDir, $vignetteDir, $apercuDir);
            if (isset($json["minipath"])) {
                $json["minipath"] = $prefixPath . $json["minipath"];
                $json["path"] = $prefixPath . $json["path"];
                $json["size"] = \Slrfw\Library\Tools::format_taille($json["size"]);
            }
//            }
//            else {
//                $json = array(
//                    "jsonrpc" => "2.0",
//                    "status" => "error",
//                    "error" => array(
//                        "code" => 110,
//                        "message" => "Pas de page parente",
//                    ),
//                    "id" => "id",
//                );
//            }
        } else {
            if (isset($_COOKIE['id_temp']) && $_COOKIE['id_temp'] && is_numeric($_COOKIE['id_temp'])) {
                $id_temp = (int) $_COOKIE['id_temp'];
                $target = "temp-$id_temp";
            } else {
                $id_temp = 1;
                $target = "temp-$id_temp";
                while (file_exists("../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target)) {
                    $id_temp++;
                    $target = "temp-$id_temp";
                }
            }

            $targetTmp = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $this->_upload_temp;
            $targetDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target;
            $vignetteDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $this->_upload_vignette;
            $apercuDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $this->_upload_apercu;

            $json = $this->_fileManager->uploadGabPage(0, $id_temp, $targetTmp, $targetDir, $vignetteDir, $apercuDir);
            if (isset($json["minipath"])) {
                $json["minipath"] = $prefixPath . $json["minipath"];
                $json["path"] = $prefixPath . $json["path"];
                $json["size"] = \Slrfw\Library\Tools::format_taille($json["size"]);
                $json["id_temp"] = $id_temp;
            }
        }


        if ($json["status"] == "error") {
            $this->_log->logThis("Upload échoué", $this->_utilisateur->get("id"), "<b>Nom</b> : " . $_REQUEST["name"] . "<br /><b>Page</b> : " . $id_gab_page . '<br /><span style="color:red;">Error ' . $json["error"]["code"] . " : " . $json["error"]["message"] . '</span>');
        } else {
            $this->_log->logThis("Upload réussi", $this->_utilisateur->get("id"), "<b>Nom</b> : " . $_REQUEST["name"] . "<br /><b>Page</b> : " . $id_gab_page);
        }

        exit(json_encode($json));
    }

    public function cropAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $id_gab_page = isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] ? $_COOKIE['id_gab_page'] : 0;


        $newImageName = \Slrfw\Library\Tools::friendlyURL($_POST["image-name"]);

        /* Dimensions de recadrage */
        $x = $_POST["x"];
        $y = $_POST["y"];
        $w = $_POST["w"];
        $h = $_POST["h"];

        /* Information sur le fichier */
        $filepath = $_POST["filepath"];
        $filename = array_pop(explode("/", $filepath));
        $ext = strtolower(array_pop(explode(".", $filename)));
        $filenameWithoutExtension = array_shift(explode(".", $filename));
        $prefixPath = "";
        $newFilename = $newImageName . "." . $ext;
        $newFilenameWithoutExtension = $newImageName;

        /* Cas d'une édition de page */
        if ($id_gab_page) {
            $targetDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page;
            $prefixPath = $this->_api["id"] == 1 ? "" : ".." . DIRECTORY_SEPARATOR;
            $vignetteDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page . DIRECTORY_SEPARATOR . $this->_upload_vignette;
            $apercuDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $id_gab_page . DIRECTORY_SEPARATOR . $this->_upload_apercu;
        } else {
            /* Cas d'une création de page */
            if (isset($_COOKIE['id_temp']) && $_COOKIE['id_temp'] && is_numeric($_COOKIE['id_temp'])) {
                $id_temp = (int) $_COOKIE['id_temp'];
                $target = "temp-$id_temp";
            }

            $targetTmp = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $this->_upload_temp;
            $targetDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target;
            $vignetteDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $this->_upload_vignette;
            $apercuDir = "../" . $this->_upload_path . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . $this->_upload_apercu;
        }

        $id_temp = 1;
        $target = "$newFilenameWithoutExtension.$ext";
        while (file_exists($targetDir . DIRECTORY_SEPARATOR . $target)) {
            $id_temp++;
            $target = "$newFilenameWithoutExtension-$id_temp.$ext";
        }





            switch ($_POST["force-width"]) {
                case "width":
                    $tw = $_POST["minwidth"];
                    $th = ($_POST["minwidth"] / $w) * $h;
                    break;
                case "height":
                    $th = $_POST["minheight"];
                    $tw = ($_POST["minheight"] / $h) * $w;

                    break;
                case "width-height":
                    $tw = $_POST["minwidth"];
                    $th = $_POST["minheight"];
                    break;

                default:
                    $tw = false;
                    $th = false;
                    break;
            }


            if(intval($tw) <= 0) {
                $tw = false;
            }

            if(intval($th) <= 0) {
                $th = false;
            }




        if ($id_gab_page) {
            $this->_fileManager->crop($filepath, $ext, $targetDir, $target, $id_gab_page, 0, $vignetteDir, $apercuDir, $x, $y, $w, $h, $tw, $th);
        } else {
            $json = $this->_fileManager->crop($filepath, $ext, $targetDir, $target, 0, $id_temp, $vignetteDir, $apercuDir, $x, $y, $w, $h, $tw, $th);
            if (isset($json["minipath"])) {
                $json["minipath"] = $prefixPath . $json["minipath"];
                $json["path"] = $prefixPath . $json["path"];
                $json["size"] = \Slrfw\Library\Tools::format_taille($json["size"]);
                $json["id_temp"] = $id_temp;
            }
        }

        $json = array();

        $json["path"] = $prefixPath . $targetDir . DIRECTORY_SEPARATOR . $target;
        $json["filename"] = $target;


        exit(json_encode($json));
    }

    public function deleteAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $id_media_fichier = isset($_COOKIE['id_media_fichier']) && $_COOKIE['id_media_fichier'] ? $_COOKIE['id_media_fichier'] : (isset($_REQUEST['id_media_fichier']) && $_REQUEST['id_media_fichier'] ? $_REQUEST['id_media_fichier'] : 0);
        $query = "UPDATE `media_fichier` SET `suppr` = NOW() WHERE `id` = " . $id_media_fichier;
        $status = $this->_db->query($query) ? "success" : "error";
        $json = array("status" => $status);
        if (!$json["status"]) {
            $this->_log->logThis("Suppression de fichier échouée", $this->_utilisateur->get("id"), "<b>Id</b> : " . $id_media_fichier . '<br /><span style="color:red;">Error</span>');
        } else {
            $this->_log->logThis("Suppression de fichier réussie", $this->_utilisateur->get("id"), "<b>Id</b> : " . $id_media_fichier);
        }
        exit(json_encode($json));
    }

    public function autocompleteAction() {
        $this->_view->enable(FALSE);
        $this->_view->main(FALSE);

        $prefixPath = $this->_api["id"] == 1 ? ".." . DIRECTORY_SEPARATOR : ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

        $id_gab_page = isset($_GET['id_gab_page']) && $_GET['id_gab_page'] ? $_GET['id_gab_page'] : (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] ? $_COOKIE['id_gab_page'] : 0);

        $id_temp = isset($_GET['id_temp']) && $_GET['id_temp'] ? $_GET['id_temp'] : (isset($_COOKIE['id_temp']) && $_COOKIE['id_temp'] ? $_COOKIE['id_temp'] : 0);

        if (isset($_REQUEST['extensions']) && $_REQUEST['extensions'] != "") {
            $extensions = explode(";", $_REQUEST['extensions']);
        } else {
            $extensions = FALSE;
        }

//        $extensionsImage = array("jpeg", "jpg", "png", "gif");


        $json = array();

        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $tinyMCE = isset($_GET['tinyMCE']);

        if ($id_gab_page || $id_temp) {
//            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $id_gab_page, 0);

            $files = $this->_fileManager->getSearch($term, $id_gab_page, $id_temp, $extensions);

            $dir = $id_gab_page ? $id_gab_page : "temp-$id_temp";

            foreach ($files as $file) {
                if (!$tinyMCE || \Slrfw\Gabarit\fileManager::isImage($file['rewriting'])) {
                    $path = $prefixPath . DIRECTORY_SEPARATOR . $this->_upload_path . DIRECTORY_SEPARATOR
                            . $dir . DIRECTORY_SEPARATOR
                            . $file['rewriting'];
                    $vignette = $prefixPath . DIRECTORY_SEPARATOR . $this->_upload_path . DIRECTORY_SEPARATOR
                            . $dir . DIRECTORY_SEPARATOR
                            . $this->_upload_vignette . DIRECTORY_SEPARATOR
                            . $file['rewriting'];
                    $serverpath = ".." . DIRECTORY_SEPARATOR . $this->_upload_path . DIRECTORY_SEPARATOR
                            . $dir . DIRECTORY_SEPARATOR
                            . $file['rewriting'];

//                    $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, $file['id_gab_page']);
                    $realpath = Slrfw\Registry::get("base") . $dir . '/' . $file['rewriting'];

//                    $ext = array_pop(explode(".", $file['rewriting']));
                    if (\Slrfw\Model\fileManager::isImage($file['rewriting'])) {
                        $sizes = getimagesize($serverpath);
                        $size = $sizes[0] . " x " . $sizes[1];
                    }
                    else
                        $size = "";

                    if ($tinyMCE) {
                        $json[] = array(
                            $file['rewriting'] . ($size ? " ($size)" : ""),
                            $realpath,
                        );
                    } else {
                        $json[] = array(
                            "path" => $path,
                            "vignette" => $vignette,
                            "label" => $file['rewriting'],
                            "size" => ($size ? "$size" : ""),
                            "value" => $file['rewriting'],
                        );
                    }
                }
            }
        }

        if ($tinyMCE) {
            header("content-type: application/x-javascript; charset=UTF-8");
            exit("var tinyMCEImageList = " . json_encode($json) . ";");
        }

        exit(json_encode($json));
    }

}