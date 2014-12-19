<?php

namespace SWFW\Controller;

class Install extends \SWFW\Controller
{

    public function getRoutes()
    {
        return array(
            '/install',
            '/upgrade',
        );
    }

    public function GET($stage = 1)
    {
        $view = $this->getView('install');
        $view->title = 'Install';
        $view->render();
    }

    public function POST()
    {
        $db = $this->getDatabase();
        $db->query("CREATE TABLE settings ("
                . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
                . " name VARCHAR(65) NOT NULL UNIQUE,"
                . " value TEXT NOT NULL"
                . ");");
        //header("Location:".$this->getBaseUrl());
    }
}
