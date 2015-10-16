<?php

namespace App\DB;

use App\DB\Database;
use App\DB\Table;
use App\DB\Record;

class ChangeTracker {

    /** @var \App\DB\Database */
    protected $db;
    private static $current_changeset_id = false;
    private $current_changeset_comment = null;

    /** @var \App\DB\Record|false */
    private $old_record = false;

    /** @var boolean Whether the changeset should be closed after the first after_save() call. */
    private static $keep_changeset_open = false;

    public function __construct(Database $db, $comment = null) {
        $this->db = $db;
        $this->current_changeset_comment = $comment;
    }

    /**
     * Open a new changeset. If one is already open, this does nothing.
     * @global \WP_User $current_user
     * @param string $comment
     * @param boolean $keep_open Whether the changeset should be kept open (and manually closed) after after_save() is called.
     */
    public function open_changeset($comment, $keep_open = null) {
        if (!is_null($keep_open)) {
            self::$keep_changeset_open = $keep_open;
        }
        if (self::$current_changeset_id === false) {
            $user = new User();
            $data = array(
                'user' => $user->getId(),
                'comment' => $comment,
            );
            $sql = "INSERT INTO changesets SET date_and_time=NOW(), user=:user, comment=:comment;";
            $this->db->query($sql, $data);
            self::$current_changeset_id = $this->db->lastInsertId();
        }
    }

    /**
     * Close the current changeset.
     * @return void
     */
    public function close_changeset() {
        self::$current_changeset_id = false;
        self::$keep_changeset_open = false;
        $this->current_changeset_comment = null;
    }

    public function before_save(Table $table, $data, $pk_value) {
        // Don't save changes to the changes tables.
        if (in_array($table->get_name(), $this->table_names())) {
            return false;
        }

        // Open a changeset if required.
        $this->open_changeset($this->current_changeset_comment);

        // Get the current (i.e. soon-to-be-old) data for later use.
        $this->old_record = $table->get_record($pk_value);
    }

    public function after_save(Table $table, Record $new_record) {
        if (self::$current_changeset_id === false) {
            throw new \Exception("No changeset is open (after save for ".$table->get_name());
        }
        // Don't save changes to the changes tables.
        if (in_array($table->get_name(), self::table_names())) {
            return false;
        }

        // Save a change for each changed column.
        foreach ($table->get_columns() as $column) {
            $col_name = ( $column->is_foreign_key() ) ? $column->get_name() . Record::FKTITLE : $column->get_name();
            $old_val = ( is_callable(array($this->old_record, $col_name)) ) ? $this->old_record->$col_name() : null;
            $new_val = $new_record->$col_name();
            if ($new_val == $old_val) {
                // Ignore unchanged columns.
                continue;
            }
            $data = array(
                'changeset' => self::$current_changeset_id,
                'table_name' => $table->get_name(),
                'column_name' => $column->get_name(),
                'record_ident' => $new_record->get_primary_key(),
                'old_value' => $old_val,
                'new_value' => $new_val,
            );
            $sql = "INSERT INTO changes SET "
                    . " `changeset`    = :changeset,"
                    . " `change_type`  = 'field',"
                    . " `table_name`   = :table_name,"
                    . " `column_name`  = :column_name,"
                    . " `record_ident` = :record_ident,"
                    . " `old_value`    = :old_value,"
                    . " `new_value`    = :new_value"
                    . ";";
            $this->db->query($sql, $data);
        }

        // Close the changeset if required.
        if (!self::$keep_changeset_open) {
            $this->close_changeset();
        }
    }

    /**
     * Get a list of the names used by the change-tracking subsystem.
     * @global wpdb $wpdb
     * @return array|string
     */
    public static function table_names() {
        return array('changesets', 'changes');
    }

}
