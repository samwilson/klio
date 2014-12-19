<?php

namespace SWFW\Controller;

class Index extends \SWFW\Controller
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
