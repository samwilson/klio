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

    public function GET()
    {
        $this->db = $this->getDatabase();
        $view = $this->getView('index');
        $view->render();
    }
}
