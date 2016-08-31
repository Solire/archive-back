<?php
/**
 *
 */

namespace App\Back\Controller;

/**
 *
 */
class Board extends Main
{
    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start() {
        parent::start();

        if (!$this->_appConfig->get('board', 'active')) {
            exit();
        }
    }

    /**
     * Affichage du tableau de bord
     *
     * @return void
     */
    public function startAction() {
        $this->_view->action = 'board';

        /** Chargement du datatable en fonction des droits **/
        $configPageModule = $this->_configPageModule[$this->_utilisateur->gabaritNiveau];
        $gabaritsListUser = $configPageModule['gabarits'];
        if ($gabaritsListUser == '*') {
            $this->boardDatatable();
        } else {
            $this->boardDatatable($this->_utilisateur->gabaritNiveau);
        }

        $this->_view->breadCrumbs[] = array(
            'label' => 'Tableau de bord',
            'url' => 'back/board/start.html',
        );
    }

    /**
     * Génération du datatable des pages crées / éditées / supprimées
     *
     * @var string $opt Option qui s'ajoute au nom du fichier de configuration
     *
     * @return void
     */
    private function boardDatatable($opt = null)
    {
        $configName = 'board';
        $gabarits = array();
        if (empty($opt)) {
            $gabarits = $this->_gabarits;
        } else {

            /** Récupération de la liste de la page et des droits utilisateurs **/
            $configPageModule = $this->_configPageModule[$this->_utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];
            foreach ($this->_gabarits as $keyId => $gabarit) {
                if(in_array($gabarit["id"], $gabaritsListUser))
                    $gabarits[$keyId] = $gabarit;
            }
            unset($configPageModule);
        }

        $configPath = \Slrfw\FrontController::search(
            'config/datatable/' . $configName . '.cfg.php'
        );

        $this->_gabarits = $gabarits;

        $datatableClassName = '\\App\\Back\\Datatable\\Board';
        /** @todo Chargement des fichiers des differentes app */
        try {
            $datatable = new $datatableClassName(
                $_GET, $configPath, $this->_db, './datatable/',
                './datatable/', 'img/datatable/');
        } catch (\Exception $exc) {
            $datatable = new \Slrfw\Datatable\Datatable(
                $_GET, $configPath, $this->_db, './datatable/',
                './datatable/', 'img/datatable/');
        }

        /** On cré notre object datatable */
        $datatable = new $datatableClassName($_GET, $configPath, $this->_db,
            '/back/css/datatable/', '/back/js/datatable/', 'img/datatable/');

        $datatable->setUtilisateur($this->_utilisateur);
        $datatable->setGabarits($this->_gabarits);
        $datatable->setVersions($this->_versions);

        /** On cré un filtre pour les gabarits de l'api courante */
        $idsGabarit = array();
        foreach ($this->_gabarits as $gabarit) {
            $idsGabarit[] = $gabarit['id'];
        }
        $aqqQuery = 'id_gabarit IN (' . implode(",", $idsGabarit) . ')';
        $datatable->additionalWhereQuery($aqqQuery);

        $datatable->start();
        $datatable->setDefaultNbItems($this->_appConfig->get('board',
            'nb-content-default'));

        if (isset($_GET['json']) || (isset($_GET['nomain'])
            && $_GET['nomain'] == 1)
        ) {
            echo $datatable;
            exit();
        }

        $this->_view->datatableRender = $datatable;
    }

    /**
     * Sauvegarde des widgets tableau de bord
     *
     * @deprecated since v2.0
     *
     * @return void
     */
    public function saveStateAction() {
        $this->_view->enable(false);
        $idUtilisateur = $this->_utilisateur->id;
        $cookieString = $this->_db->quote(urldecode($_POST["cookie"]));

        $query  = 'REPLACE INTO board_state SET'
                . ' id_utilisateur = ' . $idUtilisateur . ','
                . ' cookie=' . $cookieString . ','
                . ' `id_api` = ' . $this->_api['id'];
        $this->_db->exec($query);
    }

    /**
     * Suppression des widgets tableau de bord
     *
     * @deprecated since v2.0
     *
     * @return void
     */
    public function deleteStateAction() {
        $this->_view->enable(false);
        $idUtilisateur = $this->_utilisateur->id;

        $query  = 'DELETE FROM board_state'
                . ' WHERE id_utilisateur = ' . $idUtilisateur;
        $this->_db->exec($query);
        setcookie("inettuts-widget-preferences", null, 0);
    }
}

