<?php

namespace SWFW\DB;

class Database
{

    /** @var array|string */
    protected $table_names;

    /** @var array|\SWFW\DB\Table */
    protected $tables;

    /** @var PDO */
    static protected $pdo;

    /** @var array|string */
    static protected $queries;

    public function __construct()
    {
        require dirname($_SERVER['SCRIPT_FILENAME']) . '/config.php';
        $dsn = 'mysql:host=' . \SWFW\Arr::get($database_config, 'hostname', 'localhost') . ';dbname=' . $database_config['database'];
        $attr = array(\PDO::ATTR_TIMEOUT => 10);
        self::$pdo = new \PDO($dsn, $database_config['username'], $database_config['password'], $attr);
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setFetchMode(\PDO::FETCH_OBJ);
        return true;
    }

    public static function getQueries()
    {
        return self::$queries;
    }

    public function setFetchMode($fetchMode)
    {
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }

    /**
     * Get a result statement for a given query. Handles errors.
     * 
     * @param string $sql The SQL statement to execute.
     * @param array $params Array of param => value pairs.
     * @return \PDOStatement Resulting PDOStatement.
     */
    public function query($sql, $params = false)
    {
        if (is_array($params) && count($params) > 0) {
            $stmt = self::$pdo->prepare($sql);
            foreach ($params as $placeholder => $value) {
                $stmt->bindValue($placeholder, $value);
            }
            $result = $stmt->execute();
            if (!$result) {
                throw new PDOException('Unable to execute: ' . $sql);
            } else {
                //echo '<p>Executed: '.$sql.'<br />with '.  print_r($params, true).'</p>';
            }
        } else {
            try {
                $stmt = self::$pdo->query($sql);
            } catch (PDOException $e) {
                throw new Exception('Unable to execute: ' . $sql);
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
        if (!isset($this->tables[$name])) {
            $specificTableClass = '\SWFW\DB\Table\\' . \SWFW\Str::camelcase($name);
            $tableClassName = (class_exists($specificTableClass)) ? $specificTableClass : '\SWFW\DB\Table';
            $table = new $tableClassName($this, $name);
            if ($table->can('read')) {
                $this->tables[$name] = $table;
            }
        }
        return $this->tables[$name];
    }
}
