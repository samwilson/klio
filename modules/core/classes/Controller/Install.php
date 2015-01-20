<?php

namespace Klio\Controller;

class Install extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/install',
            '/upgrade',
        );
    }

    public function get($stage = 1)
    {
        $view = $this->getView('install');
        $view->title = 'Install';
        $view->render();
    }

    public function post()
    {
        $db = $this->getDatabase();
        $db->install();
        header("Location:" . $this->getBaseUrl());
    }
}
