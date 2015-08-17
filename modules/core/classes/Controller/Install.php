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

        // Run each module's installer.
//        $modules = new \Klio\Modules($this->getBaseDir());
//        foreach ($modules->getPaths() as $mod => $modPath) {
//            $installClass = 'Klio\\' . \Klio\Text::camelcase($mod) . '\\Installer';
//            if (class_exists($installClass)) {
//                $installer = new $installClass($this->getDatabase());
//                $installer->run();
//            }
//        }

        // Finish.
        $view = $this->getView('install.html');
        $view->message('success', 'Installation/upgrade complete', true);
        header("Location:" . $this->getBaseUrl());
        exit(0);
    }
}
