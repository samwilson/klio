<?php

namespace App\DB;

class Record {

    /** @var Table */
    protected $table;

    /** @var \stdClass */
    protected $data;

    const FKTITLE = 'FKTITLE';

    /**
     * Create a new Record object.
     * @param Table $table The table object.
     * @param array $data The data of this record.
     */
    public function __construct($table, $data = array()) {
        $this->table = $table;
        $this->data = (object) $data;
    }

    public function __set($name, $value) {
        $this->data->$name = $value;
    }

    /**
     * Set multiple columns' values.
     * @param type $data
     */
    public function set_multiple($data) {
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $col => $datum) {
            $this->$col = $datum;
        }
    }

    /**
     * Get a column's value. If suffixed with 'FKTITLE', then get the title of
     * the foreign record (where applicable).
     * @param string $name The column name.
     * @param array $args [Parameter not used]
     * @return string|boolean
     */
    public function __call($name, $args) {

        // Foreign key 'title' values.
        $useTitle = substr($name, -strlen(self::FKTITLE)) == self::FKTITLE;
        if ($useTitle) {
            $name = substr($name, 0, -strlen(self::FKTITLE));
            $col = $this->get_col($name);
            if ($col->is_foreign_key() && !empty($this->data->$name)) {
                $referencedTable = $col->get_referenced_table();
                if (!$referencedTable) {
                    // May not have read permissions on the referenced table.
                    return $this->data->$name;
                }
                $fkRecord = $referencedTable->get_record($this->data->$name);
                $fkTitleCol = $referencedTable->get_title_column();
                $fkTitleColName = $fkTitleCol->get_name();
                if ($fkTitleCol->is_foreign_key()) {
                    // Use title if the FK's title column is also an FK.
                    $fkTitleColName .= self::FKTITLE;
                }
                return $fkRecord->$fkTitleColName();
            }
        }
        $col = $this->get_col($name);

        // Booleans
        if ($col->is_boolean()) {
            // Numbers are fetched from the DB as strings.
            if ($this->data->$name === '1') {
                return true;
            } elseif ($this->data->$name === '0') {
                return false;
            } else {
                return null;
            }
        }

        // Standard column values.
        if (isset($this->data->$name)) {
            return $this->data->$name;
        }
    }

    /**
     * Get a column of this record's table, optionally throwing an Exception if
     * it doesn't exist.
     * @param boolean $required True if this should throw an Exception.
     * @return \WordPress\Tabulate\DB\Column The column.
     * @throws \Exception If the column named doesn't exist.
     */
    protected function get_col($name, $required = true) {
        $col = $this->table->get_column($name);
        if ($required && $col === false) {
            throw new \Exception("Unable to get column $name on table " . $this->table->get_name());
        }
        return $col;
    }

    public function __toString() {
        return print_r($this->data, true);
    }

    /**
     * Get the value of this record's primary key, or false if it doesn't have
     * one.
     *
     * @return string|false
     */
    public function get_primary_key() {
        if ($this->table->get_pk_column()) {
            $pk_col_name = $this->table->get_pk_column()->get_name();
            if (isset($this->data->$pk_col_name)) {
                return $this->data->$pk_col_name;
            }
        }
        return false;
    }

    /**
     * Get the value of this Record's title column.
     * @return string
     */
    public function get_title() {
        $title_col = $this->table->get_title_column();
        if ($title_col !== $this->table->get_pk_column()) {
            $title_col_name = $title_col->get_name();
            return $this->data->$title_col_name;
        } else {
            $title_parts = array();
            foreach ($this->table->get_columns() as $col) {
                $col_name = $col->get_name() . self::FKTITLE;
                $title_parts[] = $this->$col_name();
            }
            return '[ ' . join(' | ', $title_parts) . ' ]';
        }
    }

    /**
     * Get the record that is referenced by this one from the column given.
     *
     * @param string $column_name
     * @return boolean|\WordPress\Tabulate\DB\Record
     */
    public function get_referenced_record($column_name) {
        if (!isset($this->data->$column_name)) {
            return false;
        }
        return $this->table
                        ->get_column($column_name)
                        ->get_referenced_table()
                        ->get_record($this->data->$column_name);
    }

    /**
     * Get a list of records that reference this record in one of their columns.
     *
     * @param string|\WordPress\Tabulate\DB\Table $foreign_table
     * @param string|\WordPress\Tabulate\DB\Column $foreign_column
     * @param boolean $with_pagination Whether to only return the top N records.
     * @return \WordPress\Tabulate\DB\Record[]
     */
    public function get_referencing_records($foreign_table, $foreign_column, $with_pagination = true) {
        $foreign_table->reset_filters();
        $foreign_table->add_filter($foreign_column, '=', $this->get_primary_key(), true);
        return $foreign_table->get_records($with_pagination);
    }

    /**
     * Get the URL (or part thereof) for this Record.
     * @param boolean $full Whether to include the Base URL or not. Defaults to not.
     * @return string
     */
    public function get_url($full = false) {
        $url = '/record/' . $this->table->get_name();
        if ($this->get_primary_key()) {
            $url .= '/' . $this->get_primary_key();
        }
        if ($full) {
            $url = \App\App::baseurl() . $url;
        }
        return $url;
    }

    /**
     * Get most recent changes.
     * @return string[]
     */
    public function get_changes() {
        $sql = "SELECT "
                . "   cs.id AS changeset_id, c.id AS change_id, date_and_time, "
                . "   username, table_name, record_ident, column_name, "
                . "   old_value, new_value, comment "
                . " FROM `changes` c "
                . "   JOIN `changesets` cs ON (c.changeset = cs.id) "
                . "   JOIN `users` u ON (u.ID = cs.user) "
                . " WHERE `table_name` = :table AND record_ident = :id "
                . " ORDER BY `date_and_time` DESC, cs.id DESC "
                . " LIMIT 15 ";
        $params = array(
            'table' => $this->table->get_name(),
            'id' => $this->get_primary_key(),
        );
        $db = $this->table->get_database();
        return $db->query($sql, $params);
    }

}
