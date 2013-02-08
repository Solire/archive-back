<?php

require_once 'main-controller.php';


class ErrorController extends MainController
{

    public function start()
    {
        
    }

    public function startAction()
    {
        $this->_view->enable(false);

        switch ($_GET['code']) {
            case '400':
                echo 'Échec de l\'analyse HTTP.';
                break;
            case '401':
                echo 'Le pseudo ou le mot de passe n\'est pas correct !';
                break;
            case '402':
                echo 'Le client doit reformuler sa demande avec les bonnes données de paiement.';
                break;
            case '403':
                echo 'Requête interdite !';
                break;
            case '404':
                header("HTTP/1.0 404 Not Found");
                echo 'La page n\'existe pas ou plus !';
                break;
            case '405':
                echo 'Méthode non autorisée.';
                break;
            case '500':
                echo 'Erreur interne au serveur ou serveur saturé.';
                break;
            case '501':
                echo 'Le serveur ne supporte pas le service demandé.';
                break;
            case '502':
                echo 'Mauvaise passerelle.';
                break;
            case '503':
                echo ' Service indisponible.';
                break;
            case '504':
                echo 'Trop de temps à la réponse.';
                break;
            case '505':
                echo 'Version HTTP non supportée.';
                break;
            default:
                echo 'Erreur !';
        }
    }

}
