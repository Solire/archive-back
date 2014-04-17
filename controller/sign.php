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

    public function start() {
        parent::start();

        $this->_view->unsetMain();
    }

    /**
     * Affichage du formulaire de connection
     *
     * @return void
     */
    public function startAction()
    {
        $this->_javascript->addLibrary('back/js/form.js');
        $this->_javascript->addLibrary('back/js/jquery/vibrate.js');

        $this->_view->action = 'back/' . $this->_appConfig->get('general', 'page-default');

        if ($this->_utilisateur->isConnected()) {
            $this->simpleRedirect(
                'back/' . $this->_appConfig->get('general', 'page-default'), true
            );
        }
    }

    public function asknewpasswordAction()
    {
        $this->_view->emailSent = false;
        $this->_view->error = false;

        if (isset($_POST['log']) && is_string($_POST['log'])) {
            $cle = $this->_utilisateur->genKey($_POST['log']);

            if ($cle !== false) {
                $email = new \Slrfw\Mail('newpassword');
                $email->url     = 'back/sign/newpassword.html?e=' . $_POST['log'] . '&amp;c=' . $cle;
                $email->to      = $_POST['log'];
                $email->from    = 'noreply@' . $_SERVER['SERVER_NAME'];
                $email->subject = 'Générer un nouveau mot de passe';
                $email->setMainUse();
                $email->send();

                $this->_view->emailSent = true;
            } else {
                $this->_view->error = true;
            }
        }
    }

    public function newpasswordAction()
    {
        if (isset($_GET['e'])
            && is_string($_GET['e'])
            && isset($_GET['c'])
            && is_string($_GET['c'])
        ) {
            $mdp = $this->_utilisateur->newPassword($_GET['c'], $_GET['e']);
            if ($mdp !== false) {
                $this->_view->mdp = $mdp;
            }
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
        $this->simpleRedirect('back/sign/start.html', true);
    }
}

