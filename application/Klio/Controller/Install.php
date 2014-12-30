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

    public function GET($stage = 1)
    {
        $view = $this->getView('install');
        $view->title = 'Install';
        $view->render();
    }

    public function POST()
    {
        $db = $this->getDatabase();
        if (!$db->getTable('settings')) {
            $db->query("CREATE TABLE settings ("
                    . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
                    . " name VARCHAR(65) NOT NULL UNIQUE,"
                    . " value TEXT NOT NULL"
                    . ");");
        }
        if (!$db->getTable('changesets')) {
            $db->query("CREATE TABLE changesets ("
                    . " id INT(10) AUTO_INCREMENT PRIMARY KEY,"
                    . " date_and_time TIMESTAMP NOT NULL,"
                    . " user_id INT(5) NULL DEFAULT NULL,"
                    . " comments VARCHAR(140) NULL DEFAULT NULL"
                    . ");");
        }
        if (!$db->getTable('changes')) {
            $db->query("CREATE TABLE changes ("
                    . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
                    . " changeset_id INT(10) NOT NULL,"
                    . " user_id INT(5) NULL DEFAULT NULL,"
                    . " comments VARCHAR(140) NULL DEFAULT NULL"
                    . ");");
            $db->query("ALTER TABLE `changes`"
                    . " ADD FOREIGN KEY ( `changeset_id` )"
                    . " REFERENCES `klio`.`changes` (`id`)"
                    . " ON DELETE CASCADE ON UPDATE CASCADE;");
        }
        header("Location:" . $this->getBaseUrl());
    }
}
