<?php

namespace Klio\Controller;

class Admin extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/admin.*'
        );
    }

    public function GET()
    {
        $view = $this->getView('admin');
        $view->title = 'Administration';
        $view->skins = $view->skins();
        $view->site_title = \Klio\Settings::siteTitle();
        echo $view->render();
    }

    public function POST()
    {
        if (isset($_POST)) {
            \Klio\Settings::save('site_title', $_POST['site_title']);
        }
        header('Location:'.$this->getBaseUrl() . '/admin');
        exit(0);
    }
}
