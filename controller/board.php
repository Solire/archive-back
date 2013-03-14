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

        $this->_boardDatatable();

        $idUtilisateur = $this->_utilisateur->id;
        $query  = 'SELECT board_state.cookie'
                . ' FROM `board_state`'
                . ' WHERE `board_state`.id_utilisateur = ' . $idUtilisateur
                . ' AND `id_api` = ' . $this->_api['id'];
        $boardStateCookie = $this->_db->query($query)->fetchColumn();

        if ($boardStateCookie) {
            setcookie('inettuts-widget-preferences',
                urldecode($boardStateCookie), time() + 60 * 60 * 24 * 30);
        }



        $query  = 'SELECT `gab_gabarit`.id, count(DISTINCT gab_page.id) nbpages,'
                . ' `gab_gabarit`.*'
                . ' FROM `gab_gabarit`'
                . ' LEFT JOIN gab_page ON gab_page.id_gabarit = gab_gabarit.id'
                . ' AND gab_page.suppr = 0'
                . ' WHERE `gab_gabarit`.`id_api` = ' . $this->_api['id']
                . ' AND `gab_gabarit`.id NOT IN (1,2)'
                . ' GROUP BY gab_gabarit.id'
                . ' ORDER BY gab_gabarit.id';
        $this->_gabarits2 = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
        $pages = array();

        $colorWidget = array(
            'color-yellow',
            'color-red',
            'color-blue',
            'color-white',
            'color-orange',
            'color-green',
        );
        $indexColor = 0;
        $lastGabaritId = -1;

        foreach ($this->_gabarits2 as $gabarit) {
            $pagesMeta = $this->_gabaritManager->getList(BACK_ID_VERSION,
                $this->_api['id'], false, $gabarit['id'], false, 'date_crea',
                'desc', 0, 3);
            
            if (count($pagesMeta) == 0) {
                continue;
            }
            
            $pages[$gabarit['id']]['gabarit'] = $gabarit;
            foreach ($pagesMeta as $pageMeta) {
                $page = $this->_gabaritManager->getPage(BACK_ID_VERSION,
                    BACK_ID_API, $pageMeta->getMeta('id'));
                $pages[$gabarit['id']]['pages'][] = $page;
            }

            $pagesMeta = $this->_gabaritManager->getList(BACK_ID_VERSION,
                $this->_api['id'], false, $gabarit['id'], false, 'date_modif',
                'desc', 0, 3);
            
            if (count($pagesMeta) == 0) {
                continue;
            }
            
            if ($gabarit['id_parent'] == $lastGabaritId) {
                $indexColor--;
            }
            $lastGabaritId = $gabarit['id'];

            $pages[$gabarit['id']]['gabarit'] = $gabarit;
            if (!isset($colorWidget[$indexColor])) {
                $indexColor = 0;
            }
            
            $pages[$gabarit['id']]['color'] = $colorWidget[$indexColor];

            $indexColor++;
            foreach ($pagesMeta as $pageMeta) {
                $page = $this->_gabaritManager->getPage(BACK_ID_VERSION,
                    BACK_ID_API, $pageMeta->getMeta('id'));
                $pages[$gabarit['id']]['pages_mod'][] = $page;
            }
        }
        $this->_view->pages = $pages;

        $this->_view->breadCrumbs[] = array(
            'label' => 'Tableau de bord',
            'url' => 'back/board/start.html',
        );
    }

    /**
     * Génération du datatable des pages crées / éditées / supprimées
     * 
     * @return void
     */
    private function _boardDatatable() {
        $nameConfig = "board";

        $datatableClassName = '\\App\\Back\\Datatable\\' . $nameConfig;
        /** @todo Chargement des fichiers des differentes app */
        try {
            $datatable = new $datatableClassName(
                $_GET, $nameConfig, $this->_db, './datatable/',
                './datatable/', 'img/datatable/');
        } catch (\Exception $exc) {
            $datatable = new \Slrfw\Datatable\Datatable(
                $_GET, $nameConfig, $this->_db, './datatable/',
                './datatable/', 'img/datatable/');
        }

        /** On cré notre object datatable */
        $datatable = new $datatableClassName($_GET, $nameConfig, $this->_db,
            '/back/css/datatable/', '/back/js/datatable/', 'img/datatable/');

        $datatable->setUtilisateur($this->_utilisateur);
        $datatable->setGabarits($this->_gabarits);

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

