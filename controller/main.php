<?php
/**
 * Controller principal du back
 *
 * @package    Controller
 * @subpackage Back
 * @author     Stéphane <smonnot@solire.fr>
 * @license    Solire http://www.solire.fr/
 */

namespace Slrfw\App\Back\Controller;

use Slrfw\Library\Registry;


/**
 * Controller principal du back
 *
 * @package    Controller
 * @subpackage Back
 * @author     Stéphane <smonnot@solire.fr>
 * @license    Solire http://www.solire.fr/
 */
class Main extends \Slrfw\Library\Controller
{

    /**
     * Session en cours
     *
     * @var \Slrfw\Library\Session
     */
    protected $_utilisateur;

    /**
     *
     * @var array
     */
    protected $_api;

    /**
     * Always execute before other method in controller
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        $suffixApi = '';
        if (isset($_GET['api'])) {
            $nameApi = $_GET['api'];
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

        $this->_log = new \Slrfw\Library\Log($this->_db, '', 0, 'back_log');


        $query = 'SELECT * '
               . 'FROM gab_api '
               . 'WHERE id = ' . $idApi . ' ';
        $this->_api = $this->_db->query($query)->fetch(\PDO::FETCH_ASSOC);

        $query = 'SELECT * '
               . 'FROM gab_api ';
        $this->_apis = $this->_db->query($query)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);

        if ($this->_api['id'] != 1) {
            $suffixApi = $this->_api['name'] . '/';
            Registry::set('basehref', Registry::get('basehref') . $suffixApi);
            $this->_url = Registry::get('basehref');
            $this->_view->url = Registry::get('basehref');
        }
        define('BACK_ID_API', $this->_api['id']);

        $this->_javascript->addLibrary('jquery/jquery-1.8.0.min.js');
        $this->_javascript->addLibrary('jquery/jquery-ui-1.8.23.custom.min.js');
        $this->_javascript->addLibrary('main.js');
        $this->_javascript->addLibrary('jquery/jquery.cookie.js');
        $this->_javascript->addLibrary('jquery/sticky.js');
        $this->_javascript->addLibrary("jquery/jquery.livequery.min.js");

        $this->_javascript->addLibrary('jquery/jquery.stickyPanel.min.js');

        $this->_javascript->addLibrary('newstyle.js');
        $this->_css->addLibrary('jquery-ui-1.8.7.custom.css');

        $this->_css->addLibrary('jquery-ui/custom-theme/jquery-ui-1.8.22.custom.css');

        //Inclusion Bootstrap twitter
        $this->_javascript->addLibrary('bootstrap/bootstrap.min.js');
        $this->_css->addLibrary('bootstrap/bootstrap.min.css');
        $this->_css->addLibrary('bootstrap/bootstrap-responsive.min.css');

        $this->_css->addLibrary('http://www.solire.fr/style_solire_fw/css/back/newstyle-1.3.css');
        $this->_css->addLibrary('sticky.css');

        $this->_view->site = Registry::get('project-name');

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
            $url = '/' . Registry::get('baseroot') . '' . $suffixApi;
            setcookie('id_version', $id_version, 0, $url);
            define('BACK_ID_VERSION', $id_version);
        } elseif (isset($_COOKIE['id_version'])
            && isset($this->_versions[$_COOKIE['id_version']])
        ) {
            define('BACK_ID_VERSION', $_COOKIE['id_version']);
        } else {
            define('BACK_ID_VERSION', 1);
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

        $this->_utilisateur = new \Slrfw\Library\Session('back');

        if (isset($this->_post['log']) && isset($this->_post['pwd'])
            && !empty($this->_post['log']) && !empty($this->_post['pwd'])
        ) {
            try {
                $this->_utilisateur->connect($this->_post['log'], $this->_post['pwd']);
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
            && isset($this->noRedirect)
            && $this->noRedirect === true
        ) {
            $this->simpleRedirect('sign/start.html', true);
        }

        /**
         * Si l'utilisateur a juste le droit de prévisualisation du site
         *  = possibilité de voir le site sans tenir compte de la visibilité
         * Alors On le redirige vers le front
         */
        if ($this->_utilisateur->get("niveau") == "voyeur") {
            if($_GET["controller"] . "/" . $_GET["action"] != "sign/signout")
                $this->simpleRedirect('../', true);
        }

        $this->_view->utilisateur = $this->_utilisateur;
        $this->_view->apis = $this->_apis;
        $this->_view->api = $this->_api;
        $this->_view->javascript = $this->_javascript;
        $this->_view->css = $this->_css;
        $this->_view->versions = $this->_versions;
        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.id_api = ' . $this->_api['id'] . ' ';
        $this->_view->versions = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $this->_view->breadCrumbs = array();
        $this->_view->breadCrumbs[] = array(
            'label' => '<img src="img/gray_dark/home_12x12.png"> '
                    . $this->_view->site,
            'url' => './',
        );

        $this->_view->appConfig = $this->_appConfig;

        //On recupere la configuration du module pages (Menu + liste)
        $configMain = Registry::get('mainconfig');
        include_once $configMain->get('back', 'dirs') . 'page.cfg.php';
        $this->_configPageModule = $config;
        $this->_view->menuPage = array();
        foreach ($this->_configPageModule as $configPage) {
            $this->_view->menuPage[] = array(
                'label' => $configPage['label'],
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

        $this->_view->pagesNonTraduites = $this->_db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }
}

