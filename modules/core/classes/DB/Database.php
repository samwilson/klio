<?php

namespace Klio\DB;

class Database
{

    /**
     * This event is triggered when the list of table names is being requested from the database.
     */
    const GET_TABLE_NAMES_EVENT = 'db.database.get_table_names';

    /** @var array|string */
    protected $table_names;

    /** @var array|\Klio\DB\Table */
    protected $tables;

    /** @var \PDO */
    static protected $pdo;

    /** @var array|string */
    static protected $queries;

    public function __construct($config)
    {
        if (self::$pdo) {
            return;
        }
        $dsn = "mysql:host=" . $config['hostname'] . ";dbname=" . $config['database'];
        $attr = array(\PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new \PDO($dsn, $config['username'], $config['password'], $attr);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setFetchMode(\PDO::FETCH_OBJ);
    }

    public function install($baseDir)
    {
        $modules = new \Klio\Modules($baseDir);
        foreach ($modules->getPaths() as $mod => $modPath) {
            $installClass = 'Klio\\' . \Klio\Text::camelcase($mod) . '\\DB\\Installer';
            if (class_exists($installClass)) {
                $installer = new $installClass($this);
                $installer->run();
            }
        }

//        if (!$this->getTable('changesets')) {
//            $this->query(
//                "CREATE TABLE changesets ("
//                . " id INT(10) AUTO_INCREMENT PRIMARY KEY,"
//                . " date_and_time TIMESTAMP NOT NULL,"
//                . " user_id INT(5) NULL DEFAULT NULL,"
//                . " comments VARCHAR(140) NULL DEFAULT NULL"
//                . ");"
//            );
//        }
//        if (!$this->getTable('changes')) {
//            $this->query(
//                "CREATE TABLE changes ("
//                . " id INT(4) AUTO_INCREMENT PRIMARY KEY,"
//                . " changeset_id INT(10) NOT NULL,"
//                . " user_id INT(5) NULL DEFAULT NULL,"
//                . " comments VARCHAR(140) NULL DEFAULT NULL"
//                . ");"
//            );
//            $this->query(
//                "ALTER TABLE `changes`"
//                . " ADD FOREIGN KEY ( `changeset_id` )"
//                . " REFERENCES `changes` (`id`)"
//                . " ON DELETE CASCADE ON UPDATE CASCADE;"
//            );
//        }
//        $this->table_names = false;
//        $this->tables = array();
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

    /**
     * @param boolean $trigger Whether to trigger events.
     */
    public function getTableNames($trigger = true)
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
        $databaseEvent = new Event($this, $this->table_names);
        if ($trigger) {
            \Klio\App::dispatch(self::GET_TABLE_NAMES_EVENT, $databaseEvent);
        }
        return $databaseEvent->data;
    }

    public function getTables($grouped = false)
    {
        foreach ($this->getTableNames() as $tableName) {
            $this->getTable($tableName);
        }
        if (!$grouped) {
            return $this->tables;
        }

        // Group tables together by common prefixes.
        if (isset($_SESSION['grouped_table_names'])) {
            $prefixes = $_SESSION['grouped_table_names'];
        } else {
            $prefixes = \Klio\Arr::getPrefixGroups(array_keys($this->tables));
            $_SESSION['grouped_table_names'] = $prefixes;
        }
        $groups = array('Miscellaneous' => $this->tables);
        // Go through each table,
        foreach (array_keys($this->tables) as $table) {
            // and each LCP,
            foreach ($prefixes as $lcp) {
                // and, if the table name begins with this LCP, add the table
                // to the LCP group.
                if (strpos($table, $lcp) === 0) {
                    $groups[\Klio\Text::titlecase($lcp)][$table] = $this->tables[$table];
                    unset($groups['Miscellaneous'][$table]);
                }
            }
        }
        return $groups;
    }

    /**
     * Get a table object.
     * @param string $name
     * @param boolean $trigger Whether to trigger events.
     * @return Table
     */
    public function getTable($name, $trigger = true)
    {
        if (!in_array($name, $this->getTableNames($trigger))) {
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
