<?php

namespace Klio\Users\DB;

class Installer extends \Klio\DB\BaseInstaller
{

    public function run()
    {
        if (!$this->db->getTable('roles', false)) {
            $this->db->query(
                "CREATE TABLE roles ("
                . " id INT(10) AUTO_INCREMENT PRIMARY KEY,"
                . " name VARCHAR(200) NOT NULL UNIQUE"
                . ");"
            );
        }
        if (!$this->db->getTable('permissions', false)) {
            $this->db->query(
                "CREATE TABLE permissions ("
                . " id INT(10) AUTO_INCREMENT PRIMARY KEY,"
                . " table_name VARCHAR(65) NULL DEFAULT NULL"
                . "     COMMENT 'A single table name, or * for all tables.',"
                . " column_name VARCHAR(65) NULL DEFAULT NULL"
                . "     COMMENT 'A single column name, or * for all columns.',"
                . " role_id INT(10) NULL COMMENT '',"
                . " activity VARCHAR(65) NULL DEFAULT NULL"
                . "     COMMENT 'The activity that will be permitted to the specified role.'"
                . ");"
            );
        }
    }
}
