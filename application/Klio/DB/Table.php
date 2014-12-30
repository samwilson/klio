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
    protected $_definingSql;

    /** @var string The SQL statement most recently saved by $this->getRows() */
    protected $saved_sql;

    /** @var string The statement parameters most recently saved by $this->getRows() */
    protected $saved_parameters;

    /**
     * @var array|Table Array of tables referred to by columns in this one.
     */
    protected $_referenced_tables;

    /** @var array Each joined table gets a unique alias, based on this. */
    protected $alias_count = 1;

    /**
     * @var array|Column Array of column names and objects for all of the
     * columns in this table.
     */
    protected $columns;

    /** @var Pagination */
    protected $_pagination;

    /** @var array */
    protected $_filters = array();

    /** @var array Permitted operators. */
    protected $_operators = array(
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
    protected $_row_count = FALSE;

    /** @var integer The current page number. */
    protected $_page = 1;

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
     * 
     * @param type $column
     * @param type $operator
     * @param type $value
     * @param boolean $force Whether to transform the value, for FKs.
     */
    public function addFilter($column, $operator, $value, $force = FALSE)
    {
        $valid_columm = in_array($column, array_keys($this->columns));
        $valid_operator = in_array($operator, array_keys($this->_operators));
        $valid_value = (strpos($operator, 'empty') !== false) || (strpos($operator, 'empty') === false && !empty($value));
        if ($valid_columm && $valid_operator && $valid_value) {
            $this->_filters[] = array(
                'column' => $column,
                'operator' => $operator,
                'value' => trim($value),
                'force' => $force,
            );
        }
    }

    /**
     * Add all of the filters given in $_GET['filters'].  This is used in both
     * the [index](api/Controller_WebDB#action_index)
     * and [export](api/Controller_WebDB#action_export) actions.
     *
     * @return void
     */
    public function addGetFilters()
    {
        $filters = Arr::get($_GET, 'filters', array());
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $column = arr::get($filter, 'column', FALSE);
                $operator = arr::get($filter, 'operator', FALSE);
                $value = arr::get($filter, 'value', FALSE);
                $this->add_filter($column, $operator, $value);
            }
        }
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
        foreach ($this->_filters as $filter) {
            $param_name = $filter['column'] . $param_num;

            // FOREIGN KEYS
            $column = $this->columns[$filter['column']];
            if ($column->is_foreign_key() && !$filter['force']) {
                $join = $this->joinOn($column);
                $filter['column'] = $join['column_alias'];
                $join_clause .= $join['join_clause'];
            }

            // LIKE or NOT LIKE
            if ($filter['operator'] == 'like' || $filter['operator'] == 'not like') {
                $where_clause .= ' AND CONVERT(' . $filter['column'] . ', CHAR) ' . strtoupper($filter['operator']) . ' :' . $param_name . ' ';
                $params[$param_name] = '%' . $filter['value'] . '%';
            }

            // Equals or does-not-equal
            elseif ($filter['operator'] == '=' || $filter['operator'] == '!=') {
                $where_clause .= ' AND ' . $filter['column'] . ' ' . strtoupper($filter['operator']) . ' :' . $param_name . ' ';
                $params[$param_name] = $filter['value'];
            }

            // IS EMPTY
            elseif ($filter['operator'] == 'empty') {
                $where_clause .= ' AND (' . $filter['column'] . ' IS NULL OR ' . $filter['column'] . ' = "")';
            }

            // IS NOT EMPTY
            elseif ($filter['operator'] == 'not empty') {
                $where_clause .= ' AND (' . $filter['column'] . ' IS NOT NULL AND ' . $filter['column'] . ' != "")';
            }

            // Other operators. They're already validated in $this->addFilter()
            else {
                $where_clause .= ' AND (' . $filter['column'] . ' ' . $filter['operator'] . ' :' . $param_name . ')';
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
            $join_clause .= ' LEFT OUTER JOIN `' . $fk1_table->getName() . '` AS f' . $this->alias_count
                    . ' ON (`' . $this->getName() . '`.`' . $column->getName() . '` '
                    . ' = `f' . $this->alias_count . '`.`' . $fk1_table->getPkColumn()->getName() . '`)';
            $column_alias = "f$this->alias_count." . $fk1_title_column->getName();
            $this->joined_tables[] = $column_alias;
            // FK is also an FK?
            if ($fk1_title_column->isForeignKey()) {
                $fk2_table = $fk1_title_column->getReferencedTable();
                $fk2_title_column = $fk2_table->getTitleColumn();
                $join_clause .= ' LEFT OUTER JOIN `' . $fk2_table->getName() . '` AS ff' . $this->alias_count
                        . ' ON (f' . $this->alias_count . '.`' . $fk1_title_column->getName() . '` '
                        . ' = ff' . $this->alias_count . '.`' . $fk1_table->getPkColumn()->getName() . '`)';
                $column_alias = "ff$this->alias_count." . $fk2_title_column->getName();
                $this->joined_tables[] = $column_alias;
            }
            $this->alias_count++;
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
     * @return array[array[string=>string]] The row data
     */
    public function getRows($with_pagination = true, $save_sql = false)
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
        if ($with_pagination) {
            $rowsPerPage = \Klio\Settings::get('rows_per_page', 30);
            $sql .= ' LIMIT ' . $rowsPerPage;
            if ($this->page() > 1) {
                $sql .= ' OFFSET ' . ($rowsPerPage * ($this->page() - 1));
            }
        }

        // Run query and save SQL
        //$this->database->setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $this->database->query($sql, $params, '\Klio\DB\Row', array($this));
        //$this->database->setFetchMode(\PDO::FETCH_OBJ);
        if ($save_sql) {
            $this->saved_sql = $sql;
            $this->saved_parameters = $params;
        }
        return $rows;
    }

    public function getSavedQuery()
    {
        return array(
            'sql' => $this->saved_sql,
            'parameters' => $this->saved_parameters
        );
    }

    /**
     * Get a single record as an associative array.
     *
     * @param integer $id The ID of the record to get.
     * @return \Klio\DB\Record The record object.
     */
    public function getRow($id)
    {
        $pk_column = $this->getPkColumn();
        $pk_name = (!$pk_column) ? 'id' : $pk_column->getName();
        $sql = "SELECT `" . join('`, `', array_keys($this->getColumns())) . "` "
                . "FROM `" . $this->getName() . "` "
                . "WHERE `$pk_name` = :$pk_name "
                . "LIMIT 1";
        //$this->database->setFetchMode(\PDO::FETCH_ASSOC);
        $row = $this->database->query($sql, array($pk_name => $id), '\Klio\DB\Row', array($this));
        //print_r($row);
        return $row->fetch();
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
        return $this->_operators;
    }

    /**
     * Get the pagination object for this table.
     *
     * @return Pager_Sliding
     */
    public function getPagination()
    {
        if (!isset($this->_pagination)) {
            $total_row_count = $this->countRecords();
            $this->_pagination = array(
                'total_count' => $total_row_count,
                'rows_per_page' => 10,
                'pages' => ceil($total_row_count / 10),
                'starting_row' => 1,
                'page' => $this->page(),
            );
        }
        return $this->_pagination;
    }

    public function getPageCount()
    {
        return ceil($this->countRecords() / ROWS_PER_PAGE);
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
            $this->_page = $page;
        } else {
            return $this->_page;
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
        if (!$this->_row_count) {
            $pk = $this->getPkColumn()->getName();
            $sql = 'SELECT COUNT(`' . $pk . '`) as `count` FROM `' . $this->getName() . '`';
            $params = $this->applyFilters($sql);
            $result = $this->database->query($sql, $params);
            $this->_row_count = $result->fetchColumn();
        }
        return $this->_row_count;
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
        if (file_exists($filename))
            unlink($filename);
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
     * @return array|\Klio\DB\Column This table's columns.
     */
    public function getColumns()
    {
        return $this->columns;
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
     * Get the title text for a given row.  This is the value of the 'title
     * column' for that row.  If the title column is a foreign key, then the
     * title of the foreign row is used (this is recursive, to allow FKs to
     * reference FKs to an arbitrary depth).
     *
     * @param integer $id The row ID.
     * @return string The title of this row.
     */
    public function getRowTitle($id)
    {
        $row = $this->get_row($id);
        $title_column = $this->getTitleColumn();
        // If the title column is  FK, pass the title request through.
        if ($title_column->isForeignKey() && !empty($row[$title_column->getName()])) {
            $foreign_title_column = $title_column->getReferencedTable()->getTitleColumn();
            if ($foreign_title_column->isForeignKey()) {
                $title_column = $foreign_title_column;
            }
            $fk_row_id = $row[$title_column->getName()];
            return $title_column->getReferencedTable()->getTitle($fk_row_id);
        }
        // Otherwise, get the text.
        if (isset($row[$title_column->getName()])) {
            return $row[$title_column->getName()];
        } else {
            return '[ ' . join(', ', $row) . ' ]'; // This is ridiculous.
        }
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
            if ($column->isUniqueKey() && !$column->isPrimaryKey())
                return $column;
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
        if (!isset($this->_definingSql)) {
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
            $this->_definingSql = $defining_sql;
        }
        return $this->_definingSql;
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
        return FALSE;
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
        if (!isset($this->_referenced_tables)) {
            $definingSql = $this->getDefiningSql();
            $foreignKeyPattern = '|FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`|';
            preg_match_all($foreignKeyPattern, $definingSql, $matches);
            if (isset($matches[1]) && count($matches[1]) > 0) {
                $this->_referenced_tables = array_combine($matches[1], $matches[2]);
            } else {
                $this->_referenced_tables = array();
            }
        }
        return $this->_referenced_tables;
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

    public function getFilters()
    {
        return $this->_filters;
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
                return TRUE;
            }
        }
        return FALSE;
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
        $this->_filters = array();
        $this->_row_count = FALSE;
    }

    /**
     * Save data to this table.  If the 'id' key of the data array is numeric,
     * the row with that ID will be updated; otherwise, a new row will be
     * inserted.
     *
     * @param array  $data  The data to insert; if 'id' is set, update.
     * @return int          The ID of the updated or inserted row.
     */
    public function saveRow($data)
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

            /*
             * Booleans
             */
            if ($column->getType() == 'int' && $column->getSize() == 1) {
                if (($value == NULL || $value == '') && !$column->isRequired()) {
                    $data[$field] = NULL;
                } elseif ($value === '0' || $value === 0 || strcasecmp($value, 'false') === 0 || strcasecmp($value, 'off') === 0 || strcasecmp($value, 'no') === 0) {
                    $data[$field] = 0;
                } else {
                    $data[$field] = 1;
                }
            }

            /*
             * Nullable empty fields should be NULL.
             */ elseif (!$column->isRequired() && empty($value)) {
                $data[$field] = NULL;
            }

            /*
             * Foreign keys
             */ elseif ($column->isForeignKey() && ($value <= 0 || $value == '')) {
                $data[$field] = NULL;
            }

            /*
             * Numbers
             */ elseif (!is_numeric($value) && (
                    substr($column->getType(), 0, 3) == 'int' || substr($column->getType(), 0, 7) == 'decimal' || substr($column->getType(), 0, 5) == 'float')
            ) {
                $data[$field] = NULL; // Stops empty strings being turned into 0s.
            }

            /*
             * Dates & times
             */ elseif (($column->getType() == 'date' || $column->getType() == 'datetime' || $column->getType() == 'time') && $value == '') {
                $data[$field] = null;
            }
        }

        // Update?
        $pk_name = $this->getPkColumn()->getName();
        if (isset($data[$pk_name]) && is_numeric($data[$pk_name])) {
            $pk_val = $data[$pk_name];
            //unset($data[$pk_name]);
            $sql = "UPDATE " . $this->getName() . " SET ";
            $pairs = array();
            foreach ($data as $col => $val) {
                if ($col == $pk_name) {
                    continue;
                }
                $pairs[] = "`$col` = :$col";
            }
            $sql .= join(', ', $pairs) . " WHERE `$pk_name` = :$pk_name";
            $this->database->query($sql, $data);
        }
        // Or insert?
        else {
            // Prevent PK from being empty.
            if (empty($data[$pk_name])) {
                unset($data[$pk_name]);
            }
            $sql = "INSERT INTO " . $this->getName()
                    . "\n( `" . join("`, `", array_keys($data)) . "` ) VALUES "
                    . "\n( :" . join(", :", array_keys($data)) . " )";
            $this->database->query($sql, $data);
            $pk_val = $this->database->lastInsertId();
        }
        return $pk_val;
    }
}
