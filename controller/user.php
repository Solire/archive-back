<?php
/**
 * Gestion du profile utilisateur
 *
 * @package    Controller
 * @subpackage Back
 * @author     Dev <dev@solire.fr>
 * @license    Solire http://www.solire.fr/
 */

namespace App\Back\Controller;

/**
 * Gestion du profile utilisateur
 *
 * @package    Controller
 * @subpackage Back
 * @author     Dev <dev@solire.fr>
 * @license    Solire http://www.solire.fr/
 */
class User extends Main
{

    /**
     * Affichage du formulaire d'édition du profile
     *
     * @return void
     */
    public function startAction()
    {
        $this->_javascript->addLibrary('back/js/formgabarit.js');

        $this->_view->breadCrumbs[] = array(
            'label' => 'Mon profil',
            'url' => '',
        );
    }

    /**
     * Change le mot de passe de l'utilisateur
     *
     * @return void
     */
    public function changePasswordAction()
    {
        $this->_view->enable(false);

        $errors = array();

        $response = array(
            'status' => false
        );

        /** Nouveau mot de passe et sa confirmation différent */
        if ($_POST['new_password'] != $_POST['new_password_c']) {
            $errors[] = 'Le nouveau mot de passe et sa confirmation sont différents';
        }


         /** Test longueur password */
        if (count($errors) == 0 && strlen($_POST['new_password']) < 6) {
            $errors[] = 'Votre nouveau mot de passe doit contenir au moins 6 caractères';
        }

        //Si aucune erreur on essaie de modifier le mot de passe
        if (count($errors) == 0) {

            $query = 'SELECT pass '
                   . 'FROM utilisateur '
                   . 'WHERE id = ' . $this->_utilisateur->id . ' ';
            $oldPass = $this->_db->query($query)->fetchColumn();

            $oldSaisi = $this->_utilisateur->prepareMdp($_POST['old_password']);


            $newPass = $this->_utilisateur->prepareMdp($_POST['new_password']);

            $query = 'UPDATE utilisateur SET '
                   . ' pass = ' . $this->_db->quote($newPass) . ' '
                   . 'WHERE `id` = ' . $this->_utilisateur->id . ' ';

            if ($oldPass == $oldSaisi) {
                $response['status'] = true;
                $this->_db->exec($query);
            } else {
                $errors[] = 'Mot de passe actuel incorrect';
            }
        }

        if ($response['status']) {
            $response['status'] = 'success';
            $response['message'] = 'Votre mot de passe a été mis à jour';
            $response['javascript'] = 'window.location.reload()';
        } else {
            $response['message'] = implode('<br />', $errors);
        }

        echo json_encode($response);
    }

    public function listeAction()
    {
        $configName = 'utilisateur';
        $configPath = \Slrfw\FrontController::search(
            'config/datatable/' . $configName . '.cfg.php'
        );

        $datatableClassName = '\\App\\Back\\Datatable\\Utilisateur';
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
}

