<?php
/**
 * Formulaire de connection à l'admin
 *
 * @package    Controller
 * @subpackage Back
 * @author     Dev <dev@solire.fr>
 * @license    Solire http://www.solire.fr/
 */

namespace App\Back\Controller;

/**
 * Formulaire de connection à l'admin
 *
 * @package    Controller
 * @subpackage Back
 * @author     Dev <dev@solire.fr>
 * @license    Solire http://www.solire.fr/
 */
class Sign extends Main
{
    /**
     * Empêche la redirection en cas de non connexion
     *
     * @var boolean
     */
    protected $noRedirect = true;

    /**
     * Affichage du formulaire de connection
     *
     * @return void
     */
    public function startAction()
    {
        $this->_javascript->addLibrary('form.js');

        $this->_view->main(false);

        $this->_view->action = $this->_appConfig->get('page-default', 'general');

        if ($this->_utilisateur->isConnected()) {
            $this->simpleRedirect ($this->_appConfig->get('page-default', 'general'), true);
        }
    }

    /**
     * déconnection de l'utilisateur
     *
     * @return void
     */
    public function signoutAction()
    {
        $this->_view->enable(false);

        $this->_utilisateur->disconnect();
        $this->simpleRedirect ('sign/start.html', true);
    }
}

