<?php

namespace \App\Tests;

class Base extends \PHPUnit_Framework_TestCase {

    /** @var \App\DB\Database */
    protected $db;

    public function setUp() {
        parent::setUp();

        $this->db = new \App\DB\Database();

        // Install.
        $this->db->install();

        // Create some testing tables and link them together.
        $this->db->query('DROP TABLE IF EXISTS `test_table`');
        $this->db->query('DROP TABLE IF EXISTS `test_types`');
        $this->db->query('CREATE TABLE `test_types` ('
                . ' id INT(10) AUTO_INCREMENT PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL UNIQUE'
                . ');'
        );
        $this->db->query('CREATE TABLE `test_table` ('
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

        // Grant the current user access to everything.
        foreach ($this->db->getTableNames(false, true) as $tbl) {
            $sql = "INSERT INTO grants (`permission`, `group`, `table_name`) VALUES "
                    . "(" . \App\DB\Tables\Permissions::READ . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", :tbl),"
                    . "(" . \App\DB\Tables\Permissions::CREATE . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", :tbl),"
                    . "(" . \App\DB\Tables\Permissions::UPDATE . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", :tbl),"
                    . "(" . \App\DB\Tables\Permissions::DELETE . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", :tbl)"
                    . ";";
            $this->db->query($sql, ['tbl' => $tbl]);
        }
        $this->db->getTableNames(true, true);
    }

    public function tearDown() {
        $this->db->query("SET foreign_key_checks = 0;");
        foreach ($this->db->getTableNames(false, true) as $tbl) {
            $this->db->query("DROP TABLE IF EXISTS `$tbl`;");
        }
        $this->db->query("SET foreign_key_checks = 1;");
        parent::tearDown();
    }

}
