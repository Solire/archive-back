<?php

namespace Slrfw;

header('Content-Type: text/html; charset=utf-8');

/* = Session PHP
  ------------------------------- */
session_name();
session_start();

/* = Affichage des erreurs
  ------------------------------- */
error_reporting(E_ALL);
ini_set('display_errors', 'On');

set_include_path(
    get_include_path()
    . PATH_SEPARATOR . realpath('../')
    . PATH_SEPARATOR . realpath('../config/')
    . PATH_SEPARATOR . realpath('../model/')
);
require_once 'library/path.php';
require_once 'library/front-controller.php';

/* = Autoload
  ------------------------------- */
/**
 * Chargement de classes dynamiquement
 * @package Library
 * @param string $name nom de la classe à charger
 */
function autoload($name)
{
    if (strpos($name, 'Slrfw') !== false) {
        $name = str_replace('Slrfw\\', '', $name);
        $name = str_replace('\\', DIRECTORY_SEPARATOR, $name);
        $name = strtolower($name) . '.php';
    }

    $path = new Library\Path($name, Library\Path::SILENT);
    $fullPath = $path->get();

    if ($fullPath) {
        require_once $path->get();
    }
}

spl_autoload_register('Slrfw\autoload');


/* = lancement du script
  ------------------------------- */
try {
    Library\FrontController::init();
    Library\FrontController::run();
} catch (Library\Exception\Marvin $exc) {
    Library\Error::report($exc);
} catch (Library\Exception\User $exc) {
    Library\Error::message($exc);
} catch (Library\Exception\HttpError $exc) {
    if (current($exc->getHttp()) == '404') {
        header('HTTP/1.0 404 Not Found');
        Library\FrontController::run('front', 'error', 'error404');
    }
} catch (\Exception $exc) {
    Library\Error::run();
}
