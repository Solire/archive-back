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
        $idClient = intval($_GET['index']);
        $clientData = $this->_db->query('
            SELECT utilisateur.*
            FROM utilisateur
            WHERE utilisateur.id = ' . $idClient)->fetch();
        $password = \Slrfw\Tools::random(10);

        $mail = new \Slrfw\Mail('utilisateur_identifiant');
        $mail->setMainUse();
        $mail->to      = $clientData['email'];
        $mail->from    = 'contact@solire.fr';
        $mail->subject = 'Informations de connexion à l\'outil d\'administration'
                       . ' de votre site';

        $mail->urlAcces = \Slrfw\Registry::get("basehref") . 'back/';

        $clientData['pass'] = $password;
        $mail->clientData = $clientData;
        $mail->send();

        $passwordCrypt = \Slrfw\Session::prepareMdp($password);
        $values = array(
            "pass" => $passwordCrypt,
        );
        $this->_db->update('utilisateur', $values, 'id = ' . $idClient);

        exit(json_encode(array('status' => 1)));
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

