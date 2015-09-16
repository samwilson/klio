<?php

namespace App\DB;

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
        $host = getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost';
        $dsn = "mysql:host=$host;dbname=" . getenv('DB_NAME');
        $attr = array(\PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new \PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), $attr);
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
                //echo '<li>';var_dump($value, $type);
                $stmt->bindValue($placeholder, $value, $type);
            }
            if ($class) {
                $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class, $classArgs);
            } else {
                $stmt->setFetchMode(\PDO::FETCH_OBJ);
            }
            $result = $stmt->execute();
            if (!$result) {
                throw new \PDOException('Unable to execute parameterised SQL: <code>' . $sql . '</code>');
            } else {
                //echo '<p>Executed: '.$sql.'<br />with '.  print_r($params, true).'</p>';
            }
            //exit();
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

    public function getTableNames($checkGrants = true) {
        if (!is_array($this->tableNames)) {
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
            if ($user->can(Grants::READ, $tableName) || !$checkGrants) {
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
        foreach ($this->get_table_names() as $name) {
            $table = $this->get_table($name);
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
                . " (" . User::ADMIN_GROUP_ID . ",'Administrators'),"
                . " (" . User::PUBLIC_GROUP_ID . ",'General public');");
        ;
        $this->query("CREATE TABLE IF NOT EXISTS users ("
                . " `id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `username` VARCHAR(100) NOT NULL UNIQUE,"
                . " `password` VARCHAR(255) NOT NULL,"
                . " `group` INT(2) UNSIGNED NOT NULL DEFAULT 0,"
                . "         FOREIGN KEY (`group`) REFERENCES `groups` (`id`) "
                . ")");
        $this->query("INSERT IGNORE INTO users (`id`,`username`,`password`,`group`) VALUES"
                . "(1,'admin','" . password_hash('admin', PASSWORD_BCRYPT) . "'," . User::ADMIN_GROUP_ID . "),"
                . "(2,'anonymous','" . password_hash('anon', PASSWORD_BCRYPT) . "'," . User::PUBLIC_GROUP_ID . ");");
        $this->query("CREATE TABLE IF NOT EXISTS grants ("
                . " `id` INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . " `group` INT(2) UNSIGNED NOT NULL DEFAULT 0,"
                . "         FOREIGN KEY (`group`) REFERENCES `groups` (`id`),"
                . " `grant` VARCHAR(50) NOT NULL,"
                . " `table_name` VARCHAR(65) NULL "
                . ")");
        $this->query("INSERT IGNORE INTO `grants` (`group`, `grant`, `table_name`) VALUES"
                . " (" . User::ADMIN_GROUP_ID . ",'" . Grants::READ . "','grants'),"
                . " (" . User::ADMIN_GROUP_ID . ",'" . Grants::UPDATE . "','grants'),"
                . " (" . User::ADMIN_GROUP_ID . ",'" . Grants::CREATE . "','grants');");
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
