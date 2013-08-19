<?php
/**
 * Controller principal du back
 *
 * @package    Controller
 * @subpackage Back
 * @author     Stéphane <smonnot@solire.fr>
 * @license    Solire http://www.solire.fr/
 */

namespace App\Back\Controller;

/**
 * Controller principal du back
 *
 * @package    Controller
 * @subpackage Back
 * @author     Stéphane <smonnot@solire.fr>
 * @license    Solire http://www.solire.fr/
 */
class Main extends \Slrfw\Controller
{

    /**
     * Session en cours
     *
     * @var \Slrfw\Session
     */
    protected $_utilisateur;

    /**
     * Api en cours
     *
     * @var array
     */
    protected $_api;

    /**
     * Manager des requetes liées aux pages
     *
     * @var \Slrfw\Model\gabaritManager
     */
    protected $_gabaritManager = null;

    /**
     * Always execute before other method in controller
     *
     * @return void
     * @hook back/ start Ajouter facilement des traitements au start du back
     */
    public function start()
    {
        parent::start();

        if (isset($_COOKIE['api'])) {
            $nameApi = $_COOKIE['api'];
        } else {
            $nameApi = 'main';
        }

        $query = 'SELECT id '
               . 'FROM gab_api '
               . 'WHERE name = ' . $this->_db->quote($nameApi) . ' ';

        $idApi = $this->_db->query($query)->fetch(\PDO::FETCH_COLUMN);

        if (intval($idApi) == 0) {
            $idApi = 1;
        }

        $this->_log = new \Slrfw\Log($this->_db, '', 0, 'back_log');

        $query = 'SELECT * '
               . 'FROM gab_api '
               . 'WHERE id = ' . $idApi . ' ';
        $this->_api = $this->_db->query($query)->fetch(\PDO::FETCH_ASSOC);

        $query = 'SELECT * '
               . 'FROM gab_api ';
        $this->_apis = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        if (!defined('BACK_ID_API')) {
            define('BACK_ID_API', $this->_api['id']);
        }

        $this->_javascript->addLibrary('back/js/jquery/jquery-1.8.0.min.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery-ui-1.8.23.custom.min.js');
        $this->_javascript->addLibrary('back/js/main.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.cookie.js');
        $this->_javascript->addLibrary('back/js/jquery/sticky.js');
        $this->_javascript->addLibrary('back/js/jquery/jquery.livequery.min.js');

        $this->_javascript->addLibrary('back/js/jquery/jquery.stickyPanel.min.js');

        $this->_javascript->addLibrary('back/js/newstyle.js');
        $this->_css->addLibrary('back/css/jquery-ui-1.8.7.custom.css');

        $this->_css->addLibrary('back/css/jquery-ui/custom-theme/jquery-ui-1.8.22.custom.css');

        /** Inclusion Bootstrap twitter */
        $this->_javascript->addLibrary('back/js/bootstrap/bootstrap.min.js');
        $this->_css->addLibrary('back/css/bootstrap/bootstrap.min.css');
        $this->_css->addLibrary('back/css/bootstrap/bootstrap-responsive.min.css');

        $this->_css->addLibrary('back/css/newstyle-1.3.css');
        $this->_css->addLibrary('back/css/sticky.css');

        $this->_view->site = \Slrfw\Registry::get('project-name');

        if (isset($_GET['controller'])) {
            $this->_view->controller = $_GET['controller'];
        } else {
            $this->_view->controller = '';
        }

        if (isset($_GET['action'])) {
            $this->_view->action = $_GET['action'];
        } else {
            $this->_view->action = '';
        }

        $this->_gabaritManager = new \Slrfw\Model\gabaritManager();
        $this->_fileManager = new \Slrfw\Model\fileManager();

        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.`id_api` = ' . $this->_api['id'] . ' ';

        $this->_versions = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );


        if ($_POST) {
            $this->_post = $_POST;
        }

        if (isset($_GET['id_version'])) {
            $id_version = $_GET['id_version'];
            $url = '/' . \Slrfw\Registry::get('baseroot');
            setcookie('id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_POST['id_version'])) {
            $id_version = $_POST['id_version'];
            $url = '/' . \Slrfw\Registry::get('baseroot');
            setcookie('back_id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_COOKIE['back_id_version'])
            && isset($this->_versions[$_COOKIE['back_id_version']])
        ) {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $_COOKIE['back_id_version']);
            }
        } else {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', 1);
            }
        }

        if (isset($this->_post['log']) && isset($this->_post['pwd'])
            && ($this->_post['log'] == '' || $this->_post['pwd'] == '')
        ) {
            $retour = array(
                'success' => false,
                'message' => 'Veuillez renseigner l\'identifiant et le mot de passe'
            );
            exit(json_encode($retour));
        }

        $this->_utilisateur = new \Slrfw\Session('back');

        if (isset($this->_post['log']) && isset($this->_post['pwd'])
            && !empty($this->_post['log']) && !empty($this->_post['pwd'])
        ) {
            try {
                $this->_utilisateur->connect($this->_post['log'],
                    $this->_post['pwd']);
            } catch (\Exception $exc) {
                $log = 'Identifiant : ' . $this->_post['log'];
                $this->_log->logThis('Connexion échouée', 0, $log);
                throw $exc;
            }

            $this->_log->logThis('Connexion réussie', $this->_utilisateur->id);

            $message = 'Connexion réussie, vous allez être redirigé';

            exit(json_encode(array('success' => true, 'message' => $message)));
        }

        if (!$this->_utilisateur->isConnected()
            && (!isset($this->noRedirect) || $this->noRedirect === false)
        ) {
            $this->simpleRedirect('back/sign/start.html', true);
        }

        /**
         * Si l'utilisateur a juste le droit de prévisualisation du site
         *  = possibilité de voir le site sans tenir compte de la visibilité
         * Alors On le redirige vers le front
         */
        if ($this->_utilisateur->get('niveau') == 'voyeur') {
            if ($_GET['controller'] . '/' . $_GET['action'] != 'back/sign/signout') {
                $this->simpleRedirect('../', true);
            }
        }

        $this->_view->utilisateur = $this->_utilisateur;
        $this->_view->apis = $this->_apis;
        $this->_view->api = $this->_api;
        $this->_view->javascript = $this->_javascript;
        $this->_view->css = $this->_css;
        $this->_view->mainVersions = $this->_versions;
        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.id_api = ' . $this->_api['id'] . ' ';
        $this->_view->mainVersions = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $this->_view->breadCrumbs = array();
        $this->_view->breadCrumbs[] = array(
            'label' => '<img src="app/back/img/gray_dark/home_12x12.png"> '
                    . $this->_view->site,
        );

        /** On indique que l'on est dans une autre api **/
        if ($this->_api['id'] != 1) {
            $this->_view->breadCrumbs[] = array(
                    'label' => $this->_api['label'],
            );
        }

        $this->_view->appConfig = $this->_appConfig;

        /**
         * On recupere la configuration du module pages (Menu + liste)
         */
        $path = \Slrfw\FrontController::search('config/page.cfg.php');
        $completConfig = array();
        $appList = \Slrfw\FrontController::getAppDirs();
        foreach ($appList as $app) {
            $path = new \Slrfw\Path(
                $app['dir'] . DS . 'back/config/page.cfg.php', \Slrfw\Path::SILENT
            );

            if ($path->get() == false) {
                continue;
            }
            include $path->get();

            foreach ($config as $key => $value) {
                $completConfig[$key] = $value;
            }
            unset($config, $key, $value);
        }

        $this->_configPageModule = $completConfig;
        unset($path, $config);
        $this->_view->menuPage = array();
        foreach ($this->_configPageModule as $configPage) {
            $this->_view->menuPage[] = array(
                'label' => $configPage['label'],
                'display' => $configPage['display'],
            );
        }

        $query = 'SELECT gab_gabarit.id, gab_gabarit.* '
               . 'FROM gab_gabarit '
               . 'WHERE gab_gabarit.id_api = ' . $this->_api['id'] . ' ';
        $this->_gabarits = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        $query = 'SELECT * '
               . 'FROM gab_page gp '
               . 'WHERE rewriting = "" '
               . ' AND gp.suppr = 0 '
               . ' AND id_version = ' . BACK_ID_VERSION . ' ';

        $this->_view->pagesNonTraduites = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_ASSOC);

        $hook = new \Slrfw\Hook();
        $hook->setSubdirName('back');

        $hook->ctrl = $this;

        $hook->exec('start');
    }


    /**
     *
     * @param array $data Données à passer en plus
     *
     * @return void
     */
    protected function sendSuccess(array $data = array())
    {
        $return = array(
            'status' => 'success',
        );

        $return += $data;

        echo json_encode($return);
    }

    /**
     *
     * @param array $data Données à passer en plus
     *
     * @return void
     */
    protected function sendError(array $data = array())
    {
        $return = array(
            'status' => 'error',
        );

        $return += $data;

        echo json_encode($return);
    }
}

