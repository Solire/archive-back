<?php

namespace App\Back\Controller;


class Seo extends Main
{
    /**
     * Empêche la redirection en cas de non connexion
     *
     * @var boolean
     */
    protected $noRedirect = true;
    
    
    public function start()
    {
        parent::start();
    }

    public function get301Action() {
        $this->_view->enable(false);
        $url301 = $this->_db->select("redirection", false, array("*"));
        echo json_encode($url301);
    }

}
