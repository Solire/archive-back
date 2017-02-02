<?php

namespace App\Back\Controller;


class Media extends Main {

    /**
     *
     * @var page
     */
    private $_page = null;

    protected $mediaTableName = 'media_fichier';

    /**
     *
     */
    public function start()
    {
        parent::start();

        $upload = $this->_mainConfig->get('upload');
        $this->_upload_path     = $upload['path'];
        $this->_upload_temp     = $upload['temp'];
        $this->_upload_vignette = $upload['vignette'];
        $this->_upload_apercu   = $upload['apercu'];
    }

    /**
     * Affichage du gestionnaire de fichiers
     *
     * @return void
     */
    public function startAction()
    {

        $this->fileDatatable();
        $this->_javascript->addLibrary('back/js/jquery/jquery.hotkeys.js');
        $this->_javascript->addLibrary('back/js/jstree/jquery.jstree.js');
        //$this->_javascript->addLibrary('back/js/jquery/jquery.dataTables.min.js');
        $this->_javascript->addLibrary('back/js/plupload/plupload.full.js');
        $this->_javascript->addLibrary('back/js/plupload/jquery.pluploader.min.js');
        $this->_javascript->addLibrary('back/js/listefichiers.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.scroller-1.0.min.js');

        //$this->_css->addLibrary('back/css/demo_table_jui.css');
        $this->_css->addLibrary('back/css/jquery.scroller.css');

        $this->_view->breadCrumbs[] = array(
            'label' => 'Gestion des fichiers',
            'url' => '',
        );
    }

    /**
     *
     *
     * @return void
     */
    public function listAction()
    {
        $this->_view->unsetMain();
        $this->_files = array();

        /** Permet plusieurs liste de fichier dans la meme page **/
        $this->_view->idFilesList = null;
        if(isset($_REQUEST['id'])) {
            $this->_view->idFilesList = '_' . $_REQUEST['id'];
        }

        $this->_view->prefixFileUrl = null;
        if(isset($_REQUEST['prefix_url'])) {
            $this->_view->prefixFileUrl = $_REQUEST['prefix_url'] . DIRECTORY_SEPARATOR;
        }

        $id_gab_page = isset($_GET['id_gab_page']) && $_GET['id_gab_page'] ? $_GET['id_gab_page'] : 0;

        if ($id_gab_page) {
            $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
            $orderby = isset($_REQUEST['orderby']['champ']) ? $_REQUEST['orderby']['champ'] : '';
            $sens = isset($_REQUEST['orderby']['sens']) ? $_REQUEST['orderby']['sens'] : '';

            $this->_page = $this->_gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $id_gab_page);

            $this->_files = $this->_fileManager->getList($this->_page->getMeta('id'), 0, $search, $orderby, $sens);
        }

        $this->_view->files = array();
        foreach ($this->_files as $file) {
            $ext = strtolower(array_pop(explode('.', $file['rewriting'])));
            $prefixPath = $this->_api['id'] == 1 ? '' : '..' . DS;
            $file['path'] = $this->_view->prefixFileUrl . $file['id_gab_page'] . DS . $file['rewriting'];

            $serverpath = $this->_upload_path . DS . $file['id_gab_page']
                        . DS . $file['rewriting'];

            if (!file_exists($serverpath)) {
                continue;
            }

            $file['class'] = 'hoverprevisu vignette';

            if (array_key_exists($ext, \Slrfw\Model\fileManager::$_extensions['image'])) {
                $file['path_mini']  = $this->_view->prefixFileUrl
                                    . $file['id_gab_page'] . DS
                                    . $this->_upload_vignette . DS
                                    . $file['rewriting'];

                $sizes = getimagesize($serverpath);
                $file['class'] .= '  img-polaroid';
                $file['width']  = $sizes[0];
                $file['height'] = $sizes[1];
            } else {
                $file['class']      = 'vignette';
                $file['path_mini']  = 'app/back/img/filetype/' . $ext . '.png';
            }
            $file['poids'] = \Slrfw\Tools::format_taille($file['taille']);

            $this->_view->files[] = $file;
        }
    }

    /**
     * Génération du datatable des fichiers
     *
     * @return void
     */
    private function fileDatatable()
    {
        $configName = 'file';
        $gabarits = array();

        $configPath = \Slrfw\FrontController::search(
            'config/datatable/' . $configName . '.cfg.php'
        );

        $datatableClassName = '\\App\\Back\\Datatable\\File';

        $datatable = null;

        foreach (\Slrfw\FrontController::getAppDirs() as $appDir) {
            $datatableClassName = '\\' . $appDir["name"] . "\\Back\\Datatable\\" . $configName;
            if (class_exists($datatableClassName)) {
                $datatable = new $datatableClassName(
                        $_GET, $configPath, $this->_db, '/back/css/datatable/', '/back/js/datatable/', 'app/back/img/datatable/'
                );

                break;
            }
        }

        if ($datatable == null) {
            $datatable = new \Slrfw\Datatable\Datatable(
                    $_GET, $configPath, $this->_db, '/back/css/datatable/', '/back/js/datatable/', 'app/back/img/datatable/'
            );
        }


        $datatable->start();

        if (isset($_GET['json']) || (isset($_GET['nomain'])
            && $_GET['nomain'] == 1)
        ) {
            echo $datatable;
            exit();
        }

        $this->_view->datatableRender = $datatable;
    }

    /**
     *
     *
     * @return void
     */
    public function popuplistefichiersAction()
    {
        $this->listAction();
    }

    /**
     *
     *
     * @return void
     */
    public function folderlistAction()
    {
        $this->_view->unsetMain();
        $this->_view->enable(false);

        $res = array();

        if ($_REQUEST['id'] === '') {
            $res[] = array(
                'attr' => array(
                    'id' => 'node_0',
                    'rel' => 'root'
                ),
                'data' => array(
                    'title' => 'Ressources'
                ),
                'state' => 'closed'
            );
        } elseif ($_REQUEST['id'] === '0') {
            $rubriques = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api['id'], 0);
            $configPageModule = $this->_configPageModule[$this->_utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];
            foreach ($rubriques as $rubrique) {
                /** On exclu les gabarits qui ne sont pas dans les droits **/
                if ($gabaritsListUser != '*') {
                    if (!in_array($rubrique->getMeta('id_gabarit'), $gabaritsListUser)) {
                        continue;
                    }
                }
                $res[] = array(
                    'attr' => array(
                        'id' => 'node_' . $rubrique->getMeta('id'),
                        'rel' => 'page'
                    ),
                    'data' => array(
                        'title' => '<div class="horizontal_scroller" style="width:150px;height: 17px; cursor: pointer;"><div class="scrollingtext" style="left: 0px;">' . $rubrique->getMeta('titre') . '</div></div>'
                    ),
                    'state' => 'closed'
                );
            }
        } else {
            $sous_rubriques = $this->_gabaritManager->getList(BACK_ID_VERSION, $this->_api['id'], $_REQUEST['id']);

            $configPageModule = $this->_configPageModule[$this->_utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];

            foreach ($sous_rubriques as $sous_rubrique) {
                /** On exclu les gabarits qui ne sont pas dans les droits **/
                if ($gabaritsListUser != '*') {
                    if (!in_array($sous_rubrique->getMeta('id_gabarit'), $gabaritsListUser)) {
                        continue;
                    }
                }

                $nbre = $this->_db->query('SELECT COUNT(*) FROM `' . $this->mediaTableName . '` WHERE `suppr` = 0 AND `id_gab_page` = ' . $sous_rubrique->getMeta('id'))->fetchColumn();

                $res[] = array(
                    'attr' => array(
                        'id' => 'node_' . $sous_rubrique->getMeta('id'),
                        'rel' => 'page'
                    ),
                    'data' => array(
                        'title' => '<div class="horizontal_scroller" style="width:100px;height: 17px; cursor: pointer;"><div class="scrollingtext" style="left: 0px;">' . ( strlen($sous_rubrique->getMeta('titre')) > 16 ? mb_substr($sous_rubrique->getMeta('titre'), 0, 16, "utf-8") . "&hellip;" : $sous_rubrique->getMeta('titre') ) . " (<i>$nbre</i>)" . '</div></div>',
                        'attr' => array(
                            'title' => $sous_rubrique->getMeta('titre')
                        )
                    ),
                    'state' => 'closed'
                );
            }
        }

        echo json_encode($res);
    }

    /**
     *
     *
     * @return void
     */
    public function uploadAction()
    {
        $this->_view->enable(false);
        $this->_view->unsetMain();

        /** Permet plusieurs liste de fichier dans la meme page **/
        $this->_view->idFilesList = null;
        if(isset($_REQUEST['id'])) {
            $this->_view->idFilesList = '_' . $_REQUEST['id'];
        }

        $this->_view->prefixFileUrl = null;
        if(isset($_REQUEST['prefix_url'])) {
            $this->_view->prefixFileUrl = $_REQUEST['prefix_url'] . DIRECTORY_SEPARATOR;
        }

        $id_gab_page = 0;
        if (isset($_GET['id_gab_page']) && $_GET['id_gab_page']) {
            $id_gab_page = $_GET['id_gab_page'];
        } elseif (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page']) {
            $id_gab_page = $_COOKIE['id_gab_page'];
        }

        $gabaritId = 0;
        if(isset($_REQUEST['gabaritId'])) {
            $gabaritId = (int)$_REQUEST['gabaritId'];
        }

        if ($id_gab_page) {
            $targetTmp      = $this->_upload_temp;
            $targetDir      = $id_gab_page;
            $vignetteDir    = $id_gab_page . DS . $this->_upload_vignette;
            $apercuDir      = $id_gab_page . DS . $this->_upload_apercu;

            $json = $this->_fileManager->uploadGabPage($this->_upload_path,
                $id_gab_page, 0, $targetTmp, $targetDir, $vignetteDir,
                $apercuDir);

            if ($json['status'] == 'error') {
                echo json_encode($json);
                exit();
            }

            $json['size'] = \Slrfw\Tools::format_taille($json['size']);

            if (isset($json['mini_path'])) {
                $json['mini_path']   = $this->_view->prefixFileUrl . $json['mini_path'];
                $json['mini_url']   = $this->_view->prefixFileUrl . $json['mini_url'];
                $json['image'] = array(
                    'url'   =>  $this->_view->prefixFileUrl . $id_gab_page . DS . $json['filename']
                );

                // Génération de miniatures additionnelles
                $filePath = $this->_view->prefixFileUrl . $json['path'];
                $this->miniatureProcess($gabaritId, $filePath);
            }

            $json['url']       = $this->_view->prefixFileUrl . $json['url'];

            if (isset($json['minipath'])) {
                $json['minipath'] = $this->_view->prefixFileUrl . $json['minipath'];
                $json['image'] = array(
                    'url'   =>  $this->_view->prefixFileUrl . $id_gab_page . DS . $json['filename']
                );
                $json['path'] = $this->_view->prefixFileUrl . $json['path'];
            }
        } else {
            if (isset($_COOKIE['id_temp'])
                && is_numeric($_COOKIE['id_temp'])
                && $_COOKIE['id_temp'] > 0
            ) {
                $id_temp = (int) $_COOKIE['id_temp'];
                $target = 'temp-' . $id_temp;
            } else {
                $id_temp = 1;
                $target = 'temp-' . $id_temp;
                while (file_exists($this->_upload_path . DS . $target)) {
                    $id_temp++;
                    $target = 'temp-' . $id_temp;
                }
            }

            $targetTmp      = $this->_upload_temp;
            $targetDir      = $target;
            $vignetteDir    = $target . DS . $this->_upload_vignette;
            $apercuDir      = $target . DS . $this->_upload_apercu;

            $json = $this->_fileManager->uploadGabPage($this->_upload_path, 0,
                $id_temp, $targetTmp, $targetDir, $vignetteDir, $apercuDir);


            if ($json['status'] == 'success') {
                if (isset($json['mini_path'])) {
                    $json['mini_path']   = $this->_view->prefixFileUrl . $json['mini_path'];
                    $json['mini_url']   = $this->_view->prefixFileUrl . $json['mini_url'];
                    $json['image'] = array(
                        'url'   =>  $this->_view->prefixFileUrl . $id_gab_page . DS . $json['filename']
                    );

                    // Génération de miniatures additionnelles
                    $filePath = $this->_view->prefixFileUrl . $json['path'];
                    $this->miniatureProcess($gabaritId, $filePath);

                }
                $json['path']       = $this->_view->prefixFileUrl . $json['path'];
                $json['url']        = $this->_view->prefixFileUrl . $json['url'];
                $json['size']       = \Slrfw\Tools::format_taille($json['size']);
                $json['id_temp']    = $id_temp;
            }
        }

        if ($json['status'] == 'error') {
            $logTxt = '<b>Nom</b> : ' . $_REQUEST['name'] . '<br /><b>Page</b> : '
                    . $id_gab_page . '<br /><span style="color:red;">Error '
                    . $json['error']['code'] . ' : ' . $json['error']['message']
                    . '</span>';
            $this->_log->logThis('Upload échoué', $this->_utilisateur->get('id'),
                $logTxt);
        } else {
            $logTxt = '<b>Nom</b> : ' . $_REQUEST['name']. '<br /><b>Page</b> : '
                    . $id_gab_page;
            $this->_log->logThis('Upload réussi', $this->_utilisateur->get('id'),
                $logTxt);
        }

        exit(json_encode($json));
    }

    /**
     *
     *
     * @return void
     */
    public function cropAction()
    {
        $this->_view->enable(false);
        $this->_view->unsetMain();

        $gabaritId = 0;
        if(isset($_REQUEST['gabaritId'])) {
            $gabaritId = (int)$_REQUEST['gabaritId'];
        }

        $this->_prefixFileUrl = null;
        if(isset($_REQUEST['prefix_url'])) {
            $this->_prefixFileUrl = $_REQUEST['prefix_url'] . DIRECTORY_SEPARATOR;
        }

        if (isset($_GET['id_gab_page']) && $_GET['id_gab_page'] > 0) {
            $id_gab_page = $_GET['id_gab_page'];
        } elseif (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] > 0) {
            $id_gab_page = $_COOKIE['id_gab_page'];
        } else {
            $id_gab_page = 0;
        }

        /* Dimensions de recadrage */
        $x = $_POST['x'];
        $y = $_POST['y'];
        $w = $_POST['w'];
        $h = $_POST['h'];

        /* Information sur le fichier */
        $newImageName   = \Slrfw\Format\String::urlSlug(
            $_POST['image-name'],
            '-',
            255
        );
        $filepath       = $_POST['filepath'];
        $filename       = pathinfo($filepath, PATHINFO_BASENAME);
        $ext            = pathinfo($filename, PATHINFO_EXTENSION);

        if ($id_gab_page) {
            /** Cas d'une édition de page */

            $targetDir      = $id_gab_page;
            $vignetteDir    = $id_gab_page . DS . $this->_upload_vignette;
            $apercuDir      = $id_gab_page . DS . $this->_upload_apercu;
        } elseif (isset($_COOKIE['id_temp'])
            && $_COOKIE['id_temp']
            && is_numeric($_COOKIE['id_temp'])
        ) {
            /** Cas d'une création de page */

            $id_temp = (int) $_COOKIE['id_temp'];
            $target = 'temp-' . $id_temp;

//            $targetTmp      = $this->_upload_temp;
            $targetDir      = $target;
            $vignetteDir    = $target . DS . $this->_upload_vignette;
            $apercuDir      = $target . DS . $this->_upload_apercu;
        } else {
            exit();
        }

        $count_temp = 1;
        $target     = $newImageName . '.' . $ext;
        while (file_exists($this->_upload_path . DS . $targetDir . DS . $target)) {
            $count_temp++;
            $target = $newImageName . '-' . $count_temp . '.' . $ext;
        }

        switch ($_POST['force-width']) {
            case 'width' :
                $tw = $_POST['minwidth'];
                $th = ($_POST['minwidth'] / $w) * $h;
                break;
            case 'height' :
                $th = $_POST['minheight'];
                $tw = ($_POST['minheight'] / $h) * $w;
                break;

            case 'width-height' :
                $tw = $_POST['minwidth'];
                $th = $_POST['minheight'];
                break;

            default:
                $tw = false;
                $th = false;
                break;
        }

        if (intval($tw) <= 0) {
            $tw = false;
        }

        if (intval($th) <= 0) {
            $th = false;
        }

        if ($id_gab_page) {
            $this->_fileManager->crop($this->_upload_path, $filepath, $ext,
                $targetDir, $target, $id_gab_page, 0, $vignetteDir, $apercuDir,
                $x, $y, $w, $h, $tw, $th);
        } else {
            $json = $this->_fileManager->crop($this->_upload_path, $filepath,
                $ext, $targetDir, $target, 0, $id_temp, $vignetteDir,
                $apercuDir, $x, $y, $w, $h, $tw, $th);

            if (isset($json['minipath'])) {
                $json['minipath'] = $json['minipath'];
                $json['path'] = $json['path'];
                $json['size'] = \Slrfw\Tools::format_taille($json['size']);
                $json['id_temp'] = $id_temp;
            }
        }

        $json = array();
        $json['path']           = $targetDir . DS . $target;
        $json['filename']       = $target;
        $json['filename_front'] = $targetDir . '/' . $target;

        if (\Slrfw\Model\fileManager::isImage($json['filename'])) {
            $path       = $json['path'];
            $vignette   = $targetDir . DS
                        . $this->_upload_vignette . DS
                        . $json['filename'];
            $serverpath = $this->_upload_path . DS
                        . $targetDir . DS
                        . $json['filename'];

                $sizes = getimagesize($serverpath);
                $size = $sizes[0] . ' x ' . $sizes[1];
                $json['vignette'] = $vignette;
                $json['label'] = $json['filename'];
                $json['size'] = $size;
                $json['value'] = $json['filename'];
                $json['utilise'] = 1;

                // Génération de miniatures additionnelles
                $filePath = $this->_prefixFileUrl . $this->_upload_path . DS . $json['path'];
                $this->miniatureProcess($gabaritId, $filePath);
        }

        exit(json_encode($json));
    }

    /**
     *
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_view->enable(false);
        $this->_view->unsetMain();

        $id_media_fichier = isset($_COOKIE['id_media_fichier']) && $_COOKIE['id_media_fichier'] ? $_COOKIE['id_media_fichier'] : (isset($_REQUEST['id_media_fichier']) && $_REQUEST['id_media_fichier'] ? $_REQUEST['id_media_fichier'] : 0);
        $query = 'UPDATE `' . $this->mediaTableName . '` SET `suppr` = NOW() WHERE `id` = ' . $id_media_fichier;
        $status = $this->_db->query($query) ? 'success' : 'error';
        $json = array('status' => $status);
        if (!$json['status']) {
            $this->_log->logThis('Suppression de fichier échouée', $this->_utilisateur->get('id'), '<b>Id</b> : ' . $id_media_fichier . ' | <b>Table</b>' . $this->mediaTableName . '<br /><span style="color:red;">Error</span>');
        } else {
            $this->_log->logThis('Suppression de fichier réussie', $this->_utilisateur->get('id'), '<b>Id</b> : ' . $id_media_fichier . ' | <b>Table</b>' . $this->mediaTableName . '');
        }
        exit(json_encode($json));
    }

    /**
     *
     *
     * @return void
     */
    public function autocompleteAction()
    {
        $this->_view->enable(false);
        $this->_view->unsetMain();

        $prefixPath = '';

        $id_gab_page = isset($_GET['id_gab_page']) && $_GET['id_gab_page'] ? $_GET['id_gab_page'] : (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] ? $_COOKIE['id_gab_page'] : 0);

        $id_temp = isset($_GET['id_temp']) && $_GET['id_temp'] ? $_GET['id_temp'] : (isset($_COOKIE['id_temp']) && $_COOKIE['id_temp'] ? $_COOKIE['id_temp'] : 0);

        if (isset($_REQUEST['extensions']) && $_REQUEST['extensions'] != '') {
            $extensions = explode(';', $_REQUEST['extensions']);
        } else {
            $extensions = FALSE;
        }

        $json = array();

        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $tinyMCE = isset($_GET['tinyMCE']);

        if ($id_gab_page || $id_temp) {
            $files = $this->_fileManager->getSearch($term, $id_gab_page, $id_temp, $extensions);

            $dir = $id_gab_page ? $id_gab_page : 'temp-' . $id_temp;

            foreach ($files as $file) {
                if (!$tinyMCE || \Slrfw\Model\fileManager::isImage($file['rewriting'])) {
                    $path       = $dir . DS
                                . $file['rewriting'];
                    $vignette   = $dir . DS
                                . $this->_upload_vignette . DS
                                . $file['rewriting'];
                    $serverpath = $this->_upload_path . DS
                                . $dir . DS
                                . $file['rewriting'];

                    if (!file_exists($serverpath)) {
                        continue;
                    }

                    $realpath = \Slrfw\Registry::get('basehref') . $dir . '/' . $file['rewriting'];
                    if (\Slrfw\Model\fileManager::isImage($file['rewriting'])) {
                        $sizes = getimagesize($serverpath);
                        $size = $sizes[0] . ' x ' . $sizes[1];
                    } else {
                        $size = '';
                    }

                    if ($tinyMCE) {
                        $json[] = array(
                            'title' => $file['rewriting'] . ($size ? ' (' . $size . ')' : ''),
                            'value' => $realpath,
                        );
                    } else {
                        $json[] = array(
                            'path' => $path,
                            'vignette' => $vignette,
                            'label' => $file['rewriting'],
                            'utilise' => $file['utilise'],
                            'size' => ($size ? $size : ''),
                            'value' => $file['rewriting'],
                        );
                    }
                }
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    public function setMediaTableName($mediaTableName) {
        $this->mediaTableName = $mediaTableName;
    }

    /**
     * Génération de miniatures en fonction des paramètres des champs d'un
     * gabarit
     *
     * @param int $gabaritId Id du gabarit
     *
     * @return void
     */
    protected function miniatureProcess($gabaritId, $filePath)
    {
        if ($gabaritId) {
            $gabarit        = $this->_gabaritManager->getGabarit($gabaritId);
            $gabaritBlocs   = $this->_gabaritManager->getBlocs($gabarit);
            $ext            = pathinfo($filePath, PATHINFO_EXTENSION);
            $miniatureDir   = pathinfo($filePath, PATHINFO_DIRNAME);
            $miniatureName  = pathinfo($filePath, PATHINFO_BASENAME);
            $miniatureSizes = array();

            // Parcours des champs du gabarit
            foreach ($gabarit->getChamps() as $champsGroupe) {
                foreach ($champsGroupe as $champ) {
                    if ($champ['type'] == 'FILE'
                        && isset($champ['params']['MINIATURE'])
                        && $champ['params']['MINIATURE'] != ''
                    ) {
                        $miniatureSizes = array_merge(
                            $miniatureSizes,
                            explode(';', $champ['params']['MINIATURE'])
                        );
                    }
                }
            }

            // Parcours des champs des blocs du gabarit
            foreach ($gabaritBlocs as $gabaritBloc) {
                foreach ($gabaritBloc->getGabarit()->getChamps() as $champ) {
                    if ($champ['type'] == 'FILE'
                        && isset($champ['params']['MINIATURE'])
                        && $champ['params']['MINIATURE'] != ''
                    ) {
                        $miniatureSizes = array_merge(
                            $miniatureSizes,
                            explode(';', $champ['params']['MINIATURE'])
                        );
                    }
                }
            }

            $miniatureSizes = array_unique($miniatureSizes);

            foreach ($miniatureSizes as $size) {
                list($maxWidth, $maxHeight) = explode('x', $size);

                $sizeDirectory = str_replace('*', '', $size);
                if (!file_exists($miniatureDir . DS . $sizeDirectory)) {
                    $this->_fileManager->createFolder($miniatureDir . DS . $sizeDirectory);
                }

                $miniaturePath  = $miniatureDir . DS . $sizeDirectory . DS
                                . $miniatureName;

                $this->_fileManager->vignette(
                    $filePath,
                    $ext,
                    $miniaturePath,
                    $maxWidth, $maxHeight
                );
            }
        }
    }

}

