<?php

namespace Klio\Controller;

class Index extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/',
        );
    }

    public function get()
    {
        $this->db = $this->getDatabase();
        $view = $this->getView('index.html');
        $view->title = 'Home';
        $view->render();
    }
}
