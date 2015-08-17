<?php

namespace Klio\Users\DB;

class Installer extends \Klio\Installer
{

    public function run()
    {
        if (!$this->db->getTable('users', false)) {
            $this->db->query(
                "CREATE TABLE users ("
                . " id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " username VARCHAR(200) NOT NULL UNIQUE,"
                . " password VARCHAR(200) NULL DEFAULT NULL"
                . ");"
            );
        }
        // If no users exist, create 'admin'.
        if ($this->db->query("SELECT COUNT(*) as tot FROM `users`")->fetch()->tot < 1) {
            $passwordHasher = new \Hautelook\Phpass\PasswordHash(8, false);
            $sql = 'INSERT INTO users SET username = :user, password = :pass';
            $this->db->query($sql, [':user' => 'admin', ':pass' => $passwordHasher->HashPassword('admin')]);
        }
        // Create groups table.
        if (!$this->db->getTable('groups', false)) {
            $this->db->query(
                "CREATE TABLE groups ("
                . " id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " name VARCHAR(200) NOT NULL UNIQUE"
                . ");"
            );
        }
        // If no groups exist, create 'Administrators'.
        if ($this->db->query("SELECT COUNT(*) as tot FROM `groups`")->fetch()->tot < 1) {
            $sql = 'INSERT INTO `groups` SET name = :name';
            $this->db->query($sql, [':name' => 'Administrators']);
        }
        // Link users to groups.
        if (!$this->db->getTable('users_to_groups', false)) {
            $this->db->query(
                "CREATE TABLE `users_to_groups` ("
                . " `user` INT(10) UNSIGNED NOT NULL, "
                . " FOREIGN KEY (`user`) REFERENCES `users` (`id`), "
                . " `group` INT(10) UNSIGNED NOT NULL, "
                . " FOREIGN KEY (`group`) REFERENCES `groups` (`id`), "
                . " PRIMARY KEY (`user`, `group`) "
                . ");"
            );
        }
        // If no one is in any group, add user #1 to group #1.
        if ($this->db->query("SELECT COUNT(*) as tot FROM users_to_groups")->fetch()->tot < 1) {
            $sql = 'INSERT INTO users_to_groups SET `user`=1, `group`=1';
            $this->db->query($sql);
        }
        // Create permissions table.
        if (!$this->db->getTable('permissions', false)) {
            $this->db->query(
                "CREATE TABLE permissions ("
                . " `id` INT(10) AUTO_INCREMENT PRIMARY KEY, "
                . " `table_name` VARCHAR(65) NULL DEFAULT NULL "
                . "     COMMENT 'A single table name, or * for all tables.', "
                . " `column_name` VARCHAR(65) NULL DEFAULT NULL "
                . "     COMMENT 'A single column name, or * for all columns.', "
                . " `group` INT(10) UNSIGNED NULL COMMENT 'The group that this permission applies to.', "
                . " FOREIGN KEY (`group`) REFERENCES `groups` (`id`), "
                . " `activity` VARCHAR(65) NULL DEFAULT NULL "
                . "     COMMENT 'The activity that will be permitted to the specified group.' "
                . ");"
            );
        }
    }
}
