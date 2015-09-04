<?php

namespace App\Tests;

class Base extends \PHPUnit_TestCase {

    public function setUp() {
        parent::setUp();

        $db = new App\DB\Database();

        // Install.
        $db->install();

        // Create some testing tables and link them together.
        $db->query('DROP TABLE IF EXISTS `test_types`');
        $db->query('CREATE TABLE `test_types` ('
                . ' id INT(10) AUTO_INCREMENT PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL UNIQUE'
                . ');'
        );
        $db->query('DROP TABLE IF EXISTS `test_table`');
        $db->query('CREATE TABLE `test_table` ('
                . ' id INT(10) AUTO_INCREMENT PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL,'
                . ' description TEXT NULL,'
                . ' active BOOLEAN NULL DEFAULT TRUE,'
                . ' a_date DATE NULL,'
                . ' a_year YEAR NULL,'
                . ' type_id INT(10) NULL DEFAULT NULL,'
                . '         FOREIGN KEY (type_id) REFERENCES test_types (id),'
                . ' widget_size DECIMAL(10,2) NOT NULL DEFAULT 5.6,'
                . ' ranking INT(3) NULL DEFAULT NULL'
                . ');'
        );
    }

}
