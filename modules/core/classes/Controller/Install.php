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
        $view = $this->getView('install.html');
        $view->title = 'Install';
        $view->render();
    }

    public function post()
    {
        $this->getDatabase()->install($this->getBaseDir());
        header("Location:" . $this->getBaseUrl());
    }
}
