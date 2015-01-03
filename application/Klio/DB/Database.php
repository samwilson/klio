<?php

namespace Klio\DB;

class Database
{

    /** @var array|string */
    protected $table_names;

    /** @var array|\Klio\DB\Table */
    protected $tables;

    /** @var \PDO */
    static protected $pdo;

    /** @var array|string */
    static protected $queries;

    public function __construct($test = false)
    {
        if (self::$pdo) {
            return;
        }
        // Try to find the config file.
        $dirs = array(
            dirname(__DIR__ . '/../..'),
            dirname(\Klio\Arr::get($_SERVER, 'SCRIPT_FILENAME')),
            \Klio\Arr::get($_SERVER, 'PWD'),
        );
        foreach ($dirs as $dir) {
            $configFile = $dir . '/config.php';
            if (file_exists($configFile)) {
                require $configFile;
            }
            if (isset($database_config)) {
                break;
            }
        }
        // Connect to the database.
        $host = \Klio\Arr::get($database_config, 'hostname', 'localhost');
        $dbname = $database_config['database'] . ($test ? '_test' : '');
        $dsn = "mysql:host=$host;dbname=$dbname";
        $attr = array(\PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new \PDO($dsn, $database_config['username'], $database_config['password'], $attr);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setFetchMode(\PDO::FETCH_OBJ);
    }

    public function install()
    {
        if (!$this->getTable('settings')) {
            $this->query(
                "CREATE TABLE settings ("
                . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
                . " name VARCHAR(65) NOT NULL UNIQUE,"
                . " value TEXT NOT NULL"
                . ");"
            );
        }
        if (!$this->getTable('changesets')) {
            $this->query(
                "CREATE TABLE changesets ("
                . " id INT(10) AUTO_INCREMENT PRIMARY KEY,"
                . " date_and_time TIMESTAMP NOT NULL,"
                . " user_id INT(5) NULL DEFAULT NULL,"
                . " comments VARCHAR(140) NULL DEFAULT NULL"
                . ");"
            );
        }
        if (!$this->getTable('changes')) {
            $this->query(
                "CREATE TABLE changes ("
                . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
                . " changeset_id INT(10) NOT NULL,"
                . " user_id INT(5) NULL DEFAULT NULL,"
                . " comments VARCHAR(140) NULL DEFAULT NULL"
                . ");"
            );
            $this->query(
                "ALTER TABLE `changes`"
                . " ADD FOREIGN KEY ( `changeset_id` )"
                . " REFERENCES `changes` (`id`)"
                . " ON DELETE CASCADE ON UPDATE CASCADE;"
            );
        }
        $this->table_names = false;
        $this->tables = array();
    }

    public static function getQueries()
    {
        return self::$queries;
    }

    /**
     * Wrapper for \PDO::lastInsertId().
     * @return string
     */
    public function lastInsertId()
    {
        return self::$pdo->lastInsertId();
    }

    public function setFetchMode($fetchMode)
    {
        return self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }

    /**
     * Get a result statement for a given query. Handles errors.
     *
     * @param string $sql The SQL statement to execute.
     * @param array $params Array of param => value pairs.
     * @return \PDOStatement Resulting PDOStatement.
     */
    public function query($sql, $params = false, $class = false, $classArgs = false)
    {
        if (!empty($class) && !class_exists($class)) {
            throw new \Exception("Class not found: $class");
        }
        if (is_array($params) && count($params) > 0) {
            $stmt = self::$pdo->prepare($sql);
            foreach ($params as $placeholder => $value) {
                $stmt->bindValue($placeholder, $value);
            }
            if ($class) {
                $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class, $classArgs);
            } else {
                $stmt->setFetchMode(\PDO::FETCH_OBJ);
            }
            $result = $stmt->execute();
            if (!$result) {
                throw new \PDOException('Unable to execute: ' . $sql);
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
                throw new \Exception($e->getMessage() . 'Unable to execute: ' . $sql);
            }
        }

        self::$queries[] = $sql;
        return $stmt;
    }

    public function getTableNames()
    {
        if (!is_array($this->table_names)) {
            $this->table_names = array();
            self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NUM);
            $tables = $this->query('SHOW TABLES');
            self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            foreach ($tables as $table) {
                $this->table_names[] = $table[0];
            }
            //sort($this->table_names);
        }
        return $this->table_names;
    }

    public function getTables()
    {
        foreach ($this->getTableNames() as $tableName) {
            $this->getTable($tableName);
        }
        return $this->tables;
    }

    /**
     * Get a table object.
     * @param string $name
     * @return Table
     */
    public function getTable($name)
    {
        if (!in_array($name, $this->getTableNames())) {
            return false;
        }
        if (!isset($this->tables[$name])) {
            $specificTableClass = '\Klio\DB\Table\\' . \Klio\Text::camelcase($name);
            $tableClassName = (class_exists($specificTableClass)) ? $specificTableClass : '\Klio\DB\Table';
            $table = new $tableClassName($this, $name);
            if ($table->can('read')) {
                $this->tables[$name] = $table;
            }
        }
        return $this->tables[$name];
    }
}
