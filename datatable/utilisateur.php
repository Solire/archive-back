<?php

namespace App\Back\Datatable;

class Utilisateur extends \Slrfw\Datatable\Datatable
{
    /**
     * Utilisateur courant
     *
     * @var \Slrfw\Session
     */
    protected $_utilisateur;

    /**
     * Template de la vue de l'email
     *
     * @var string
     */
    protected $_contentMailView;

    public function start()
    {
        parent::start();
    }

    /**
     * Défini l'utilisateur
     *
     * @param utilisateur $utilisateur Utilisateur courant
     *
     * @return void
     */
    public function setUtilisateur($utilisateur)
    {
        $this->_utilisateur = $utilisateur;
    }

    protected function beforeRunAction()
    {
        $showButton = '<a'
                    . ' href="' . $this->url . '&amp;dt_action=sendMail&amp;index=[#id#]"'
                    . ' title="Envoyer identifiant par email"'
                    . ' class="btn btn-success btn-small send-info-ajax">'
                    . '<img'
                    . ' width="12"'
                    . ' alt="Envoyer identifiant par email"'
                    . ' src="app/back/img/white/mail_16x12.png">'
                    . '</a>';
        array_unshift($this->_columnActionButtons, $showButton);
        parent::beforeRunAction();
    }

    public function sendMailAction()
    {
        $this->_contentMailView = 'utilisateur_identifiant.phtml';
        $idClient = intval($_GET['index']);
        $clientData = $this->_db->query('
            SELECT utilisateur.*
            FROM utilisateur
            WHERE utilisateur.id = ' . $idClient)->fetch();
        $password = \Slrfw\Tools::random(10);
        require_once 'Zend/Mail.php';
        $mail = new \Zend_Mail('utf-8');

        $subject = 'Informations de connexion à l\'outil d\'administration de votre site';
        $mailConfig = \Slrfw\Registry::get('email');
        $from = 'contact@solire.fr';
        $to = $clientData['email'];

        $this->urlAcces = \Slrfw\Registry::get("basehref") . 'back/';
        $this->clientData = $clientData;
        $this->clientData['pass'] = $password;

        $templateMail = $this->output(__DIR__ .  '/view/mail/main.phtml');
        $body = $templateMail;

        $mail->setBodyHtml($body)
                ->setFrom($from)
                ->addTo($to)
                ->setSubject($subject);

        $mail->send();

        $passwordCrypt = \Slrfw\Session::prepareMdp($password);

        $values = array(
            "pass" => $passwordCrypt,
        );

        $this->_db->update('utilisateur', $values, 'id = ' . $idClient);
        exit(json_encode(array('status' => 1)));
    }

    public function contentMail()
    {
        include __DIR__ .  '/view/mail/' . $this->_contentMailView;
    }

    public function afterAddAction($insertId)
    {
        $niveau = 'redacteur';
        if ($this->_utilisateur->getUser('niveau') == 'solire') {
            $niveau = 'admin';
        }

        $query  = 'UPDATE utilisateur SET'
                . ' niveau = ' . $this->_db->quote($niveau)
                . ' WHERE id = ' . $insertId;
        $this->_db->exec($query);
    }
}

