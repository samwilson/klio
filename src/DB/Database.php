<?php

namespace App\DB;

use App\App;
use App\DB\Tables\Permissions;

class Database {

    /** @var \PDO */
    static protected $pdo;

    /** @var array|string */
    static protected $queries;

    /** @var string[] */
    protected $tableNames;

    public function __construct() {
        if (self::$pdo) {
            return;
        }
        $host = App::env('DB_HOST', 'localhost');
        $dbname = App::env('DB_NAME', 'klio');
        $dsn = "mysql:host=$host;dbname=$dbname";
        $attr = array(\PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new \PDO($dsn, App::env('DB_USER', 'root'), App::env('DB_PASS'), $attr);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setFetchMode(\PDO::FETCH_OBJ);
    }

    public static function getQueries() {
        return self::$queries;
    }

    /**
     * Wrapper for \PDO::lastInsertId().
     * @return string
     */
    public function lastInsertId() {
        return self::$pdo->lastInsertId();
    }

    public function setFetchMode($fetchMode) {
        return self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }

    /**
     * Get a result statement for a given query. Handles errors.
     *
     * @param string $sql The SQL statement to execute.
     * @param array $params Array of param => value pairs.
     * @return \PDOStatement Resulting PDOStatement.
     */
    public function query($sql, $params = false, $class = false, $classArgs = false) {
        if (!empty($class) && !class_exists($class)) {
            throw new \Exception("Class not found: $class");
        }
        if (is_array($params) && count($params) > 0) {
            $stmt = self::$pdo->prepare($sql);
            foreach ($params as $placeholder => $value) {
                if (is_bool($value)) {
                    $type = \PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $type = \PDO::PARAM_NULL;
                } elseif (is_int($value)) {
                    $type = \PDO::PARAM_INT;
                } else {
                    $type = \PDO::PARAM_STR;
                }
                $stmt->bindValue($placeholder, $value, $type);
            }
            if ($class) {
                $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class, $classArgs);
            } else {
                $stmt->setFetchMode(\PDO::FETCH_OBJ);
            }
            $errorMessage = 'Unable to execute parameterised SQL: <code>' . $sql . '</code> data was: '.  print_r($params, true);
            try {
                $result = $stmt->execute();
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage()." -- $errorMessage");
            }
            if (!$result) {
                throw new \PDOException($errorMessage);
            }
        } else {
            try {
                if ($class) {
                    $stmt = self::$pdo->query($sql, \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class, $classArgs);
                } else {
                    $stmt = self::$pdo->query($sql);
                }
            } catch (\PDOException $e) {
                throw new \Exception($e->getMessage() . ' -- Unable to execute SQL: <code>' . $sql . '</code>');
            }
        }

        self::$queries[] = $sql;
        return $stmt;
    }

    public function getTableNames($checkGrants = true, $reload = false) {
        if (!is_array($this->tableNames) || $reload) {
            $this->tableNames = array();
            self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NUM);
            $tables = $this->query('SHOW FULL TABLES');
            self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            foreach ($tables as $table) {
                if ($table[1] !== 'BASE TABLE') {
                    continue;
                }
                $this->tableNames[] = $table[0];
            }
        }
        $out = array();
        $user = new User();
        foreach ($this->tableNames as $tableName) {
            if ($user->can(Tables\Permissions::READ, $tableName, $reload) || !$checkGrants) {
                $out[] = $tableName;
            }
        }
        return $out;
    }

    /**
     * Get a table from the database.
     *
     * @param string $name
     * @return Table|false The table, or false if it's not available.
     */
    public function getTable($name, $checkGrants = true) {
        if (!in_array($name, $this->getTableNames($checkGrants))) {
            return false;
        }
        if (!isset($this->tables[$name])) {
            $this->tables[$name] = new Table($this, $name);
        }
        return $this->tables[$name];
    }

    /**
     * Get all tables in this database.
     *
     * @return Table[] An array of all Tables.
     */
    public function get_tables($exclude_views = true) {
        $out = array();
        foreach ($this->getTableNames() as $name) {
            $table = $this->getTable($name);
            // If this table is not available, skip it.
            if (!$table) {
                continue;
            }
            if ($exclude_views && $table->get_type() == Table::TYPE_VIEW) {
                continue;
            }
            $out[] = $table;
        }
        return $out;
    }

    public function install() {
        $this->query("CREATE TABLE IF NOT EXISTS `groups` ("
                . " `id` INT(2) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `title` VARCHAR(100) NOT NULL UNIQUE"
                . ");");
        $this->query("INSERT IGNORE INTO `groups` (`id`,`title`) VALUES"
                . " (" . User::ADMIN_GROUP_ID . ", 'Administrators'),"
                . " (" . User::PUBLIC_GROUP_ID . ", 'General public');");
        $this->query("CREATE TABLE IF NOT EXISTS users ("
                . " `id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `username` VARCHAR(100) NOT NULL UNIQUE,"
                . " `password` VARCHAR(255) NOT NULL,"
                . " `email` VARCHAR(255) NULL DEFAULT NULL,"
                . " `group` INT(2) UNSIGNED NOT NULL DEFAULT 0,"
                . "         FOREIGN KEY (`group`) REFERENCES `groups` (`id`) "
                . ")");
        $this->query("INSERT IGNORE INTO users (`id`,`username`,`password`,`group`) VALUES"
                . "(1,'admin','" . password_hash('admin', PASSWORD_BCRYPT) . "'," . User::ADMIN_GROUP_ID . "),"
                . "(2,'anonymous','" . password_hash('anon', PASSWORD_BCRYPT) . "'," . User::PUBLIC_GROUP_ID . ");");
        $this->query("CREATE TABLE IF NOT EXISTS `permissions` ("
                . " `id` INT(2) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `title` VARCHAR(100) NOT NULL UNIQUE"
                . ");");
        $this->query("INSERT IGNORE INTO `permissions` (`id`,`title`) VALUES"
                . " (" . Permissions::CREATE . ", 'Create'),"
                . " (" . Permissions::READ . ", 'Read'),"
                . " (" . Permissions::UPDATE . ", 'Update'),"
                . " (" . Permissions::DELETE . ", 'Delete'),"
                . " (" . Permissions::IMPORT . ", 'Import');");
        $this->query("CREATE TABLE IF NOT EXISTS grants ("
                . " `id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `group` INT(2) UNSIGNED NOT NULL DEFAULT 0,"
                . "         FOREIGN KEY (`group`) REFERENCES `groups` (`id`),"
                . " `permission` INT(2) UNSIGNED NOT NULL DEFAULT 0,"
                . "         FOREIGN KEY (`permission`) REFERENCES `permissions` (`id`),"
                . " `table_name` VARCHAR(65) NULL,"
                . " UNIQUE KEY (`group`,`permission`,`table_name`) "
                . ")");
        $this->query("INSERT IGNORE INTO `grants` (`group`, `permission`, `table_name`) VALUES"
                . " (" . User::ADMIN_GROUP_ID . ",'" . Permissions::READ . "','*'),"
                . " (" . User::ADMIN_GROUP_ID . ",'" . Permissions::CREATE . "','grants')");
        $this->query("CREATE TABLE IF NOT EXISTS `changesets` ("
                . " `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
                . " `date_and_time` DATETIME NOT NULL,"
                . " `user` INT(5) UNSIGNED NOT NULL,"
                . "        FOREIGN KEY (`user`) REFERENCES `users` (`id`),"
                . " `comment` TEXT NULL DEFAULT NULL"
                . " );");
        $this->query("CREATE TABLE IF NOT EXISTS `changes` ("
                . " `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,"
                . " `changeset` INT(10) UNSIGNED NOT NULL,"
                . "             FOREIGN KEY (`changeset`) REFERENCES `changesets` (`id`),"
                . " `change_type` ENUM('field', 'file', 'foreign_key') NOT NULL DEFAULT 'field',"
                . " `table_name` TEXT(65) NOT NULL,"
                . " `record_ident` TEXT(65) NOT NULL,"
                . " `column_name` TEXT(65) NOT NULL,"
                . " `old_value` LONGTEXT NULL DEFAULT NULL,"
                . " `new_value` LONGTEXT NULL DEFAULT NULL"
                . ");");
    }

}
