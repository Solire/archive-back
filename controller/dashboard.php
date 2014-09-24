<?php

namespace App\Back\Controller;

class Dashboard extends Main
{
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
     *
     *
     * @return void
     */
    public function startAction()
    {
        if (isset($_GET['name'])) {
            if (!is_array($_GET['name'])) {
                $configsName[] = $_GET['name'];
            } else {
                $configsName = $_GET['name'];
            }

            $this->_view->datatableRender = '';

            foreach ($configsName as $configKey => $configName) {
                $datatableClassName = '\\App\\Back\\Datatable\\' . $configName;

                $configPath = \Slrfw\FrontController::search(
                    'config/datatable/' . $configName . '.cfg.php'
                );

                if (!$configPath) {
                    $this->pageNotFound();
                }

                $datatableClassName = 'Back\\Datatable\\' . $configName;
                $datatableClassName = \Slrfw\FrontController::searchClass(
                    $datatableClassName
                );

                if ($datatableClassName === false) {
                    $datatable = new \Slrfw\Datatable\Datatable(
                        $_GET, $configPath, $this->_db, '/back/css/datatable/',
                        '/back/js/datatable/', 'app/back/img/datatable/'
                    );
                } else {
                    $datatable = new $datatableClassName(
                        $_GET, $configPath, $this->_db, '/back/css/datatable/',
                        '/back/js/datatable/', 'app/back/img/datatable/'
                    );
                }

                $datatable->start();
                $datatableString = $datatable;
                $data = $datatableString;

                if ($configKey == 0 &&
                    (!isset($_GET['nomain']) || $_GET['nomain'] == 0)
                ) {
                    /**
                     * On ajoute le chemin de fer
                     */

                    $sBreadCrumbs = $this->_buildBreadCrumbs(
                        $datatable->getBreadCrumbs()
                    );
                    $datatable->beforeHtml($sBreadCrumbs);
                }

                if (isset($_GET['json'])
                    || (isset($_GET['nomain'])
                    && $_GET['nomain'] == 1)
                ) {
                    echo $data;
                    exit();
                }

                $datatable = $data;
                $this->_view->datatableRender .= $datatable;
                if (count($configsName) > 1) {
                    $this->_view->datatableRender .= '<hr />';
                }
            }
        }
    }

    /**
     * Construction du fil d'ariane
     *
     * @param array $additionnalBreadCrumbs
     *
     * @return string Fil d'ariane au format HTML
     */
    private function _buildBreadCrumbs($additionnalBreadCrumbs)
    {
        $this->_view->breadCrumbs = array_merge(
            $this->_view->breadCrumbs, $additionnalBreadCrumbs
        );
        ob_start();
        $this->_view->add('breadcrumbs');
        $sBreadCrumbs = ob_get_clean();
        return $sBreadCrumbs;
    }
}

