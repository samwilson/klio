<?php

namespace Klio\DB;

class Table
{

    /** @var \Klio\DB\Database The database to which this table belongs. */
    protected $database;

    /** @var string The name of this table. */
    protected $name;

    /** @var string This table's comment. False until initialised. */
    protected $comment = false;

    /** @var string The SQL statement used to create this table. */
    protected $definingSql;

    /** @var string The SQL statement most recently saved by $this->getRows() */
    protected $saved_sql;

    /** @var string The statement parameters most recently saved by $this->getRows() */
    protected $savedParameters;

    /**
     * @var array|Table Array of tables referred to by columns in this one.
     */
    protected $referencedTables;

    /** @var array Each joined table gets a unique alias, based on this. */
    protected $aliasCount = 1;

    /**
     * @var array|Column Array of column names and objects for all of the
     * columns in this table.
     */
    protected $columns;

    /** @var array */
    protected $filters = array();

    /** @var array Permitted operators. */
    protected $operators = array(
        'like' => 'contains',
        'not like' => 'does not contain',
        '=' => 'is',
        '!=' => 'is not',
        'empty' => 'is empty',
        'not empty' => 'is not empty',
        '>=' => 'is greater than or equal to',
        '>' => 'is greater than',
        '<=' => 'is less than or equal to',
        '<' => 'is less than'
    );

    /**
     * @var integer|false The number of currently-filtered rows, or false if no
     * query has been made yet or the filters have been reset.
     */
    protected $recordCount = false;

    /** @var integer The current page number. */
    protected $currentPageNum = 1;

    /** @var integer The number of records to show on each page. */
    protected $recordsPerPage = 10;

    /**
     * Create a new database table object.
     *
     * @param Database The database to which this table belongs.
     * @param string $name The name of the table.
     */
    public function __construct($database, $name)
    {
        $this->database = $database;
        $this->name = $name;
        if (!isset($this->columns)) {
            $this->columns = array();
            $columns = $this->database->query("SHOW FULL COLUMNS FROM `$name`");
            foreach ($columns as $column_info) {
                $column = new Column($this->database, $this, $column_info);
                $this->columns[$column->getName()] = $column;
            }
        }
    }

    /**
     * Add a filter.
     * @param type $column
     * @param type $operator
     * @param type $value
     * @param boolean $force Whether to transform the value, for FKs.
     */
    public function addFilter($column, $operator, $value, $force = false)
    {
        $valid_columm = in_array($column, array_keys($this->columns));
        $valid_operator = in_array($operator, array_keys($this->operators));
        $emptyValueAllowed = (strpos($operator, 'empty') === false && !empty($value));
        $valid_value = (strpos($operator, 'empty') !== false) || $emptyValueAllowed;
        if ($valid_columm && $valid_operator && $valid_value) {
            $this->filters[] = array(
                'column' => $column,
                'operator' => $operator,
                'value' => trim($value),
                'force' => $force,
            );
        }
    }

    /**
     * Add multiple filters.
     */
    public function addFilters($filters)
    {
        foreach ($filters as $filter) {
            $column = \Klio\Arr::get($filter, 'column', false);
            $operator = \Klio\Arr::get($filter, 'operator', false);
            $value = \Klio\Arr::get($filter, 'value', false);
            $this->addFilter($column, $operator, $value);
        }
    }

    public function getFilters()
    {
        return $this->filters;
    }

    protected function getFkJoinClause($table, $alias, $column)
    {
        return 'LEFT OUTER JOIN `' . $table->getName() . '` AS f' . $alias
            . ' ON (`' . $this->getName() . '`.`' . $column->getName() . '` '
            . ' = `f' . $alias . '`.`' . $table->get_pk_column()->getName() . '`)';
    }

    /**
     * Apply the stored filters to the supplied SQL.
     *
     * @param string $sql The SQL to modify
     * @return array Parameter values, in the order of their occurence in $sql
     */
    public function applyFilters(&$sql)
    {

        $params = array();
        $param_num = 1; // Incrementing parameter suffix, to permit duplicate columns.
        $where_clause = '';
        $join_clause = '';
        foreach ($this->filters as $filter) {
            $param_name = $filter['column'] . $param_num;

            // FOREIGN KEYS
            $column = $this->columns[$filter['column']];
            if ($column->isForeignKey() && !$filter['force']) {
                $join = $this->joinOn($column);
                $filter['column'] = $join['column_alias'];
                $join_clause .= $join['join_clause'];
            }

            // LIKE or NOT LIKE
            if ($filter['operator'] == 'like' || $filter['operator'] == 'not like') {
                $where_clause .= ' AND CONVERT(`' . $filter['column'] . '`, CHAR) '
                    . strtoupper($filter['operator']) . ' :' . $param_name . ' ';
                $params[$param_name] = '%' . $filter['value'] . '%';
            } // Equals or does-not-equal
            elseif ($filter['operator'] == '=' || $filter['operator'] == '!=') {
                $where_clause .= ' AND `' . $filter['column'] . '` '
                    . strtoupper($filter['operator']) . ' :' . $param_name . ' ';
                $params[$param_name] = $filter['value'];
            } // IS EMPTY
            elseif ($filter['operator'] == 'empty') {
                $where_clause .= ' AND (`' . $filter['column'] . '` IS NULL OR ' . $filter['column'] . ' = "")';
            } // IS NOT EMPTY
            elseif ($filter['operator'] == 'not empty') {
                $where_clause .= ' AND (`' . $filter['column'] . '` IS NOT NULL AND ' . $filter['column'] . ' != "")';
            } // Other operators. They're already validated in $this->addFilter()
            else {
                $where_clause .= ' AND (`' . $filter['column'] . '` ' . $filter['operator'] . ' :' . $param_name . ')';
                $params[$param_name] = $filter['value'];
            }

            $param_num++;
        } // end foreach filter
        // Add clauses into SQL
        if (!empty($where_clause)) {
            $where_clause_pattern = '/^(.* FROM .*?)((?:GROUP|HAVING|ORDER|LIMIT|$).*)$/m';
            $where_clause = substr($where_clause, 5); // Strip leading ' AND'.
            $where_clause = "$1 $join_clause WHERE $where_clause $2";
            $sql = preg_replace($where_clause_pattern, $where_clause, $sql);
        }

        return $params;
    }

    public function getOrderBy()
    {
        if (empty($this->orderby)) {
            $this->orderby = $this->getTitleColumn()->getName();
        }
        return $this->orderby;
    }

    public function setOrderBy($orderby)
    {
        if (in_array($orderby, array_keys($this->columns))) {
            $this->orderby = $orderby;
        }
    }

    public function getOrderDir()
    {
        if (empty($this->orderdir)) {
            $this->orderdir = 'ASC';
        }
        return $this->orderdir;
    }

    public function setOrderDir($orderdir)
    {
        if (in_array(strtoupper($orderdir), array('ASC', 'DESC'))) {
            $this->orderdir = $orderdir;
        }
    }

    /**
     * For a given foreign key column, get an alias and join clause for selecting
     * against that column's foreign values. If the column is not a foreign key,
     * the alias will just be the qualified column name, and the join clause will
     * be the empty string.
     *
     * @param \Klio\DB\Column $column
     * @return array Array with 'join_clause' and 'column_alias' keys
     */
    protected function joinOn($column)
    {
        $join_clause = '';
        $column_alias = $this->getName() . '.' . $column->getName();
        if ($column->isForeignKey()) {
            $fk1_table = $column->getReferencedTable();
            $fk1_title_column = $fk1_table->getTitleColumn();
            $join_clause .= ' LEFT OUTER JOIN `' . $fk1_table->getName() . '` AS f' . $this->aliasCount
                . ' ON (`' . $this->getName() . '`.`' . $column->getName() . '` '
                . ' = `f' . $this->aliasCount . '`.`' . $fk1_table->getPkColumn()->getName() . '`)';
            $column_alias = "f$this->aliasCount." . $fk1_title_column->getName();
            $this->joined_tables[] = $column_alias;
            // FK is also an FK?
            if ($fk1_title_column->isForeignKey()) {
                $fk2_table = $fk1_title_column->getReferencedTable();
                $fk2_title_column = $fk2_table->getTitleColumn();
                $join_clause .= ' LEFT OUTER JOIN `' . $fk2_table->getName() . '` AS ff' . $this->aliasCount
                    . ' ON (f' . $this->aliasCount . '.`' . $fk1_title_column->getName() . '` '
                    . ' = ff' . $this->aliasCount . '.`' . $fk1_table->getPkColumn()->getName() . '`)';
                $column_alias = "ff$this->aliasCount." . $fk2_title_column->getName();
                $this->joined_tables[] = $column_alias;
            }
            $this->aliasCount++;
        }
        return array('join_clause' => $join_clause, 'column_alias' => $column_alias);
    }

    /**
     * Get rows, with pagination.
     *
     * Note that rows are returned as arrays and not objects, because MySQL
     * allows column names to begin with a number, but PHP does not variables to
     * do so.
     *
     * @return array|Record The row data
     */
    public function getRecords($withPagination = true, $save_sql = false)
    {
        $columns = array();
        foreach (array_keys($this->columns) as $col) {
            $columns[] = "`$this->name`.`$col`";
        }

        // Ordering
        $orderByJoin = $this->joinOn($this->getColumn($this->getOrderBy()));

        // Build basic SELECT statement
        $sql = 'SELECT ' . join(',', $columns) . ' '
            . 'FROM `' . $this->getName() . '` ' . $orderByJoin['join_clause'] . ' '
            . 'ORDER BY ' . $orderByJoin['column_alias'] . ' ' . $this->getOrderDir();

        $params = $this->applyFilters($sql);

        // Then limit to the ones on the current page.
        if ($withPagination) {
            $recordsPerPage = $this->getRecordsPerPage();
            $sql .= ' LIMIT ' . $recordsPerPage;
            if ($this->page() > 1) {
                $sql .= ' OFFSET ' . ($recordsPerPage * ($this->getCurrentPageNum() - 1));
            }
        }

        // Run query and save SQL
        //$this->database->setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $this->database->query($sql, $params, '\Klio\DB\Record', array($this));
        //$this->database->setFetchMode(\PDO::FETCH_OBJ);
        if ($save_sql) {
            $this->saved_sql = $sql;
            $this->savedParameters = $params;
        }
        return $rows;
    }

    public function getCurrentPageNum()
    {
        return $this->currentPageNum;
    }

    public function setCurrentPageNum($currentPageNum)
    {
        $this->currentPageNum = $currentPageNum;
    }

    public function getRecordsPerPage()
    {
        return $this->recordsPerPage;
    }

    public function setRecordsPerPage($recordsPerPage)
    {
        $this->recordsPerPage = $recordsPerPage;
    }

    public function getSavedQuery()
    {
        return array(
            'sql' => $this->saved_sql,
            'parameters' => $this->savedParameters
        );
    }

    /**
     * Get a single record as an associative array.
     *
     * @param integer $id The ID of the record to get.
     * @return \Klio\DB\Record The record object.
     */
    public function getRecord($id)
    {
        $pk_column = $this->getPkColumn();
        $pk_name = (!$pk_column) ? 'id' : $pk_column->getName();
        $sql = "SELECT `" . join('`, `', array_keys($this->getColumns())) . "` "
            . "FROM `" . $this->getName() . "` "
            . "WHERE `$pk_name` = :$pk_name "
            . "LIMIT 1";
        //$this->database->setFetchMode(\PDO::FETCH_ASSOC);
        $record = $this->database->query($sql, array($pk_name => $id), '\Klio\DB\Record', array($this));
        return $record->fetch();
        //$this->database->setFetchMode(\PDO::FETCH_OBJ);
        //return new Row($row->fetch());
    }

    public function getDefaultRow()
    {
        $row = array();
        foreach ($this->getColumns() as $col) {
            $row[$col->getName()] = $col->get_default();
        }
        return $row;
    }

    /**
     * Get this table's name.
     *
     * @return string The name of this table.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get this table's title. This is the title-cased name, if not otherwise
     * defined.
     *
     * @return string The title
     */
    public function getTitle()
    {
        return \Klio\Text::titlecase($this->getName());
    }

    /**
     * Get a list of permitted operators.
     *
     * @return array[string]=>string List of operators.
     */
    public function getOperators()
    {
        return $this->operators;
    }

    public function getPageCount()
    {
        return ceil($this->countRecords() / $this->getRecordsPerPage());
    }

    /**
     * Get or set the current page.
     *
     * @param integer $page
     * @return integer Current page
     */
    public function page($page = false)
    {
        if ($page !== false) {
            $this->currentPageNum = $page;
        } else {
            return $this->currentPageNum;
        }
    }

    /**
     * Get the number of rows in the current filtered set.  This leaves the
     * actual counting up to `$this->get_rows()`, rather than doing the query
     * itself, because filtering is applied in that method, and I didn't want to
     * duplicate that here (or anywhere else).
     *
     * @todo Rename this to `row_count()`.
     * @return integer
     */
    public function countRecords()
    {
        if (!$this->recordCount) {
            $pk = $this->getPkColumn()->getName();
            $sql = 'SELECT COUNT(`' . $this->getName() . '`.`' . $pk . '`) as `count` FROM `' . $this->getName() . '`';
            $params = $this->applyFilters($sql);
            $result = $this->database->query($sql, $params);
            $this->recordCount = $result->fetchColumn();
        }
        return $this->recordCount;
    }

    /**
     * @return string Full filesystem path to resulting temporary file.
     */
    public function export()
    {

        $columns = array();
        $join_clause = '';
        foreach ($this->columns as $col_name => $col) {
            if ($col->isForeignKey()) {
                $colJoin = $this->joinOn($col);
                $column_name = $colJoin['column_alias'];
                $join_clause .= $colJoin['join_clause'];
            } else {
                $column_name = "`$this->name`.`$col_name`";
            }
            $columns[] = "REPLACE(IFNULL($column_name, ''),'\r\n', '\n')";
        }
        $orderByJoin = $this->joinOn($this->getColumn($this->getOrderBy()));
        $join_clause .= $orderByJoin['join_clause'];

        // Build basic SELECT statement
        $sql = 'SELECT ' . join(',', $columns) . ' '
            . 'FROM `' . $this->getName() . '` ' . $join_clause . ' '
            . 'ORDER BY ' . $orderByJoin['column_alias'] . ' ' . $this->getOrderDir();

        $params = $this->applyFilters($sql);

        $tmpdir = KLIO_CACHE_DIR . DIRECTORY_SEPARATOR;
        if (!file_exists($tmpdir)) {
            throw new Exception("Cache directory doesn't exist: $tmpdir");
        }
        $tmpdir = realpath($tmpdir) . DIRECTORY_SEPARATOR;
        $filename = $tmpdir . uniqid('export') . '.csv';
        if (DIRECTORY_SEPARATOR == '\\') {
            $filename = str_replace('\\', '/', $filename);
        }
        if (file_exists($filename)) {
            unlink($filename);
        }
        $sql .= " INTO OUTFILE '$filename' "
            . ' FIELDS TERMINATED BY ","'
            . ' ENCLOSED BY \'"\''
            . ' ESCAPED BY \'"\''
            . ' LINES TERMINATED BY "\r\n"';

        $this->query($sql, $params);
        if (!file_exists($filename)) {
            echo '<pre>' . $sql . '</pre>';
            throw new Exception("Failed to create $filename");
        }

        return $filename;
    }

    /**
     * Get one of this table's columns.
     *
     * @return \Klio\DB\Column The column.
     */
    public function getColumn($name)
    {
        return $this->columns[$name];
    }

    /**
     * Get a list of this table's columns.
     *
     * @return array|Column This table's columns.
     */
    public function getColumns($type = null)
    {
        if (is_null($type)) {
            return $this->columns;
        } else {
            $out = array();
            foreach ($this->getColumns() as $col) {
                if ($col->getType() == $type) {
                    $out[$col->getName()] = $col;
                }
            }
            return $out;
        }
    }

    /**
     * Get the table comment text.
     *
     * @return string
     */
    public function getComment()
    {
        if (!$this->comment) {
            $sql = $this->getDefiningSql();
            $comment_pattern = '/.*\)(?:.*COMMENT.*\'(.*)\')?/si';
            preg_match($comment_pattern, $sql, $matches);
            $this->comment = (isset($matches[1])) ? $matches[1] : '';
        }
        return $this->comment;
    }

    /**
     * Get the first unique-keyed column, or if there is no unique non-ID column
     * then use the second column (because this is often a good thing to do).
     * Unless there's only one column; then, just use that.
     *
     * @return Column
     */
    public function getTitleColumn()
    {
        // Try to get the first non-PK unique key
        foreach ($this->getColumns() as $column) {
            if ($column->isUniqueKey() && !$column->isPrimaryKey()) {
                return $column;
            }
        }
        // But if that fails, just use the second (or the first) column.
        $columnIndices = array_keys($this->columns);
        if (isset($columnIndices[1])) {
            $titleColName = $columnIndices[1];
        } else {
            $titleColName = $columnIndices[0];
        }
        //$titleColName = Arr::get($columnIndices, 1, Arr::get($columnIndices, 0, 'id'));
        return $this->columns[$titleColName];
    }

    /**
     * Get the SQL statement used to create this table, as given by the 'SHOW
     * CREATE TABLE' command.
     *
     * @return string The SQL statement used to create this table.
     */
    public function getDefiningSql()
    {
        if (!isset($this->definingSql)) {
            $defining_sql = $this->database->query("SHOW CREATE TABLE `$this->name`");
            //exit(var_dump($defining_sql));
            if ($defining_sql->columnCount() > 0) {
                $defining_sql = $defining_sql->fetch();
                //$defining_sql->next();
                //$defining_sql = $defining_sql->as_array();
                //$defining_sql = $defining_sql[0];
                if (isset($defining_sql->{'Create Table'})) {
                    $defining_sql = $defining_sql->{'Create Table'};
                } elseif (isset($defining_sql->{'Create View'})) {
                    $defining_sql = $defining_sql->{'Create View'};
                }
            } else {
                throw new Exception('Table not found: ' . $this->name);
            }
            $this->definingSql = $defining_sql;
        }
        return $this->definingSql;
    }

    /**
     * Get this table's Primary Key column.
     *
     * @return Column The PK column.
     */
    public function getPkColumn()
    {
        foreach ($this->getColumns() as $column) {
            if ($column->isPrimaryKey()) {
                return $column;
            }
        }
        return false;
    }

    /**
     * Get a list of a table's foreign keys and the tables to which they refer.
     * This does <em>not</em> take into account a user's permissions (i.e. the
     * name of a table which the user is not allowed to read may be returned).
     *
     * @return array[string => string] The list of <code>column_name => table_name</code> pairs.
     */
    public function getReferencedTables()
    {
        if (!isset($this->referencedTables)) {
            $definingSql = $this->getDefiningSql();
            $foreignKeyPattern = '|FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`|';
            preg_match_all($foreignKeyPattern, $definingSql, $matches);
            if (isset($matches[1]) && count($matches[1]) > 0) {
                $this->referencedTables = array_combine($matches[1], $matches[2]);
            } else {
                $this->referencedTables = array();
            }
        }
        return $this->referencedTables;
    }

    /**
     * Get tables with foreign keys referring here.
     *
     * @return array|Table Of the format: `array('table' => Table, 'column' => string)`
     */
    public function getReferencingTables()
    {
        $out = array();
        foreach ($this->database->getTables() as $table) {
            $foreign_tables = $table->getReferencedTables();
            foreach ($foreign_tables as $foreign_column => $foreign_table) {
                if ($foreign_table == $this->name) {
                    $out[] = array('table' => $table, 'column' => $foreign_column);
                }
            }
        }
        return $out;
    }

    /**
     * Get a list of the names of the foreign keys in this table.
     *
     * @return array[string] Names of foreign key columns in this table.
     */
    public function getForeignKeyNames()
    {
        return array_keys($this->getReferencedTables());
    }

    /**
     * Find out whether or not the current user has the given permission for any
     * of the records in this table.
     *
     * @return boolean
     */
    public function can($perm)
    {
        foreach ($this->getColumns() as $column) {
            if ($column->can($perm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the database to which this table belongs.
     *
     * @return Database The database object.
     */
    public function getDatabase()
    {
        return $this->database;
    }

    public function getOneLineSummary()
    {
        $colCount = count($this->getColumns());
        return $this->name . " ($colCount columns)";
    }

    /**
     * Get a string representation of this table; a succinct summary of its
     * columns and their types, keys, etc.
     *
     * @return string A summary of this table.
     */
    public function __toString()
    {
        $colCount = count($this->getColumns());
        $out = "\n+-----------------------------------------+\n";
        $out .= "| " . $this->name . " ($colCount columns)\n";
        $out .= "+-----------------------------------------+\n";
        foreach ($this->getColumns() as $column) {
            $out .= "| $column \n";
        }
        $out .= "+-----------------------------------------+\n\n";
        return $out;
    }

    /**
     * Get an XML representation of the structure of this table.
     *
     * @return DOMElement The XML 'table' node.
     */
    public function toXml()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $table = $dom->createElement('table');
        $dom->appendChild($table);
        $name = $dom->createElement('name');
        $name->appendChild($dom->createTextNode($this->name));
        $table->appendChild($name);
        foreach ($this->get_columns() as $column) {
            $table->appendChild($dom->importNode($column->toXml(), true));
        }
        return $table;
    }

    /**
     *
     * @return <type>
     */
    public function toJson()
    {
        $json = new Services_JSON();
        $metadata = array();
        foreach ($this->getColumns() as $column) {
            $metadata[] = array(
                'name' => $column->getName()
            );
        }
        return $json->encode($metadata);
    }

    /**
     * Remove all filters.
     *
     * @return void
     */
    public function resetFilters()
    {
        $this->filters = array();
        $this->recordCount = false;
    }

    public function deleteRecord($primaryKeyValue)
    {
        $sql = "DELETE FROM `" . $this->getName() . "` "
            . "WHERE `" . $this->getPkColumn()->getName() . "` = :primaryKeyValue";
        $data = array('primaryKeyValue' => $primaryKeyValue);
        return $this->database->query($sql, $data);
    }

    /**
     * Save data to this table.  If the 'id' key of the data array is numeric,
     * the row with that ID will be updated; otherwise, a new row will be
     * inserted.
     *
     * @param array  $data  The data to insert; if 'id' is set, update.
     * @return int          The ID of the updated or inserted row.
     */
    public function saveRecord($data, $primaryKeyValue = null)
    {

        $columns = $this->getColumns();

        /*
         * Check permissions on each column.
         */
//        foreach ($columns as $column_name => $column) {
//            if (!isset($data[$column_name])) {
//                continue;
//            }
//            $can_update = $column->can('update');
//            $can_insert = $column->can('insert');
//            if ($column_name != 'id' && (
//                    (!$can_update && isset($data['id'])) || (!$can_insert && !isset($data['id']))
//                    )) {
//                unset($data[$column_name]);
//            }
//        }

        /*
         * Go through all data and clean it up before saving.
         */
        foreach ($data as $field => $value) {
            // Make sure this column exists in the DB.
            if (!isset($columns[$field])) {
                unset($data[$field]);
                continue;
            }
            $column = $columns[$field];

            // Boolean values.
            if ($column->isBoolean()) {
                $zeroValues = array(0, '0', false, 'false', 'FALSE', 'off', 'OFF', 'no', 'NO');
                if (($value === null || $value === '') && $column->isNull()) {
                    $data[$field] = null;
                } elseif (in_array($value, $zeroValues, true)) {
                    $data[$field] = false;
                } else {
                    $data[$field] = true;
                }
            }

            // Empty strings.
            if (!$column->allowsEmptyString() && $value === '' && $column->isNull()) {
                $data[$field] = null;
            }
        }
        //echo '<pre>'; var_dump($data); exit();
        // Update?
        $primaryKeyName = $this->getPkColumn()->getName();
        if ($primaryKeyValue) {
            $pairs = array();
            foreach ($data as $col => $val) {
//                var_dump($val);
//                if (is_bool($val)) {
//                    $pairs[] = "`$col` = ".(($val) ? 'TRUE' : 'FALSE');
//                    unset($data[$col]);
//                } elseif (is_null($val)) {
//                    $pairs[] = "`$col` = NULL";
//                    unset($data[$col]);
//                } else {
                $pairs[] = "`$col` = :$col";
                //}
            }
            $sql = "UPDATE " . $this->getName() . " SET " . join(', ', $pairs)
                . " WHERE `$primaryKeyName` = :primaryKeyValue";
            $data['primaryKeyValue'] = $primaryKeyValue;
            $this->database->query($sql, $data);
            $newPkValue = \Klio\Arr::get($data, $primaryKeyName, $primaryKeyValue);
        } // Or insert?
        else {
            // Prevent PK from being empty.
            if (empty($data[$primaryKeyName])) {
                unset($data[$primaryKeyName]);
            }
            $sql = "INSERT INTO " . $this->getName()
                . "\n( `" . join("`, `", array_keys($data)) . "` ) VALUES "
                . "\n( :" . join(", :", array_keys($data)) . " )";
            $this->database->query($sql, $data);
            $newPkValue = $this->database->lastInsertId();
            if (!$newPkValue) {
                $row = $this->getRecord($data[$primaryKeyName]);
                $newPkValue = $row->$primaryKeyName();
            }
        }
        return $newPkValue;
    }
}
