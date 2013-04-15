<?php

namespace App\Back\Datatable;

class Utilisateur extends \Slrfw\Datatable\Datatable {

    protected $_contentMailView;

    public function start() {
        parent::start();
    }

    protected function beforeRunAction() {
        $showButton = '<a href="' . $this->url . '&dt_action=sendMail&index=[#id#]" title="Envoyer identifiant par email" class="btn btn-primary send-info-ajax">
                            <img width="12" alt="Envoyer identifiant par email" src="app/back/img/white/mail_16x12.png">
                        </a>';
        array_unshift($this->_columnActionButtons, $showButton);
        parent::beforeRunAction();
    }

    public function sendMailAction() {
        $this->_contentMailView = "utilisateur_identifiant.phtml";
        $idClient = intval($_GET["index"]);
        $clientData = $this->_db->query("
            SELECT utilisateur.*
            FROM utilisateur
            WHERE utilisateur.id = $idClient")->fetch();
        $password = \Slrfw\Tools::random(10);
        require_once 'Zend/Mail.php';
        $mail = new \Zend_Mail("utf-8");



        $subject = "Informations de connexion à l'outil d'administration de votre site";
        $mailConfig = \Slrfw\Registry::get("email");
        $from = "contact@solire.fr";
        $to = $clientData["email"];

        $this->urlAcces = \Slrfw\Registry::get("basehref") . "back/";
        $this->clientData = $clientData;
        $this->clientData["pass"] = $password;

        $templateMail = $this->output(__DIR__ .  "/view/mail/main.phtml");
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

        $this->_db->update("utilisateur", $values, "id = $idClient");
        exit(json_encode(array("status" => 1)));
    }

    public function contentMail() {
        include __DIR__ .  "/view/mail/" . $this->_contentMailView;
    }


}

?>
