<?php

namespace Klio\DB;

class Column
{

    const EVENT_HAS_PERMISSION = 'db.column.has_permission';
    const PERM_READ = 'read';
    const PERM_CREATE = 'create';
    const PERM_UPDATE = 'update';
    const PERM_DELETE = 'delete';

    /**
     * @var Webdb_DBMS_Table The table to which this column belongs.
     */
    private $table;

    /** @var string The name of this column. */
    private $name;

    /** @var string The type of this column. */
    private $type;

    /** @var integer The size, or length, of this column. */
    private $size;

    /** @var string This column's collation. */
    private $collation;

    /**
     * @var boolean Whether or not this column is required, i.e. is NULL = not
     * required = false; and NOT NULL = required = true.
     */
    private $required = false;

    /** @var boolean Whether or not this column is the Primary Key. */
    private $isPrimaryKey = false;

    /** @var boolean Whether or not this column is a Unique Key. */
    private $isUnique = false;

    /** @var mixed The default value for this column. */
    private $defaultValue;

    /** @var boolean Whether or not this column is auto-incrementing. */
    private $isAutoIncrement = false;

    /** @var boolean Whether NULL values are allowed for this column. */
    private $isNull;

    /**
     * @var string A comma-separated list of the privileges that the database
     * user has for this column.
     * For example: 'select,insert,update,references'
     */
    private $dbUserPrivileges;

    /** @var string The comment attached to this column. */
    private $comment;

    /**
     * @var Table|false The table that this column refers to, or
     * false if it is not a foreign key.
     */
    private $references = false;

    public function __construct(Database $database, Table $table, $info)
    {

        // Table
        $this->table = $table;

        // Name
        $this->name = $info->Field;

        // Type
        $this->parseType($info->Type);

        // Default
        $this->defaultValue = $info->Default;

        // Primary key
        if (strtoupper($info->Key) == 'PRI') {
            $this->isPrimaryKey = true;
            if ($info->Extra == 'auto_increment') {
                $this->isAutoIncrement = true;
            }
        }

        // Unique key
        if (strtoupper($info->Key) == 'UNI') {
            $this->isUnique = true;
        }

        // Comment
        $this->comment = $info->Comment;

        // Collation
        $this->collation = $info->Collation;

        // NULL?
        $this->isNull = ($info->Null == 'YES');

        // Is this a foreign key?
        if (in_array($this->name, $table->getForeignKeyNames())) {
            $referencedTables = $table->getReferencedTables(false);
            $this->references = $referencedTables[$this->name];
        }

        // DB user privileges
        $this->dbUserPrivileges = array();
        $privMap = array(
            'select' => self::PERM_READ,
            'insert' => self::PERM_CREATE,
            'update' => self::PERM_UPDATE,
            'delete' => self::PERM_DELETE,
        );
        foreach (explode(',', $info->Privileges) as $priv) {
            if (isset($privMap[$priv])) {
                $this->dbUserPrivileges[] = $privMap[$priv];
            }
        }
    }

    /**
     * Can the requested action be performed?
     *
     * @param string $action
     * @return boolean
     */
    public function hasPermission($action)
    {
        // If the database user can't do something, then it can't be done.
        $dbUserCan = $this->dbUserHasPermission($action);
        if (!$dbUserCan) {
            return false;
        }
        $event = new \Klio\Event();
        $event->column = $this;
        $event->action = $action;
        $event->permission = $dbUserCan;
        \Klio\App::dispatch(self::EVENT_HAS_PERMISSION, $event);
        return $event->permission;
    }

    /**
     * Find out whether the database user (as opposed to the application user)
     * has any of the given privileges on this column.
     *
     * @param $action string The permission action to check.
     * @return boolean
     */
    protected function dbUserHasPermission($action)
    {
        $db_privs = array('select', 'update', 'insert', 'delete');
        if (!in_array($action, $db_privs)) {
            // Don't check permissions that don't apply.
            return true;
        }
        return in_array($action, $this->dbUserPrivileges);
//        $has_priv = false;
//        $privs = explode(',', $action);
//        foreach ($privs as $priv) {
//            if (strpos($this->dbUserPrivileges, $priv) !== false) {
//                $has_priv = true;
//            }
//        }
//        return $has_priv;
    }

    /**
     * Get this column's name.
     *
     * @return string The name of this column.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the valid options for this column; only applies to ENUM and SET.
     *
     * @return array The available options.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get the human-readable title of this column.
     */
    public function getTitle()
    {
        return \Klio\Text::titlecase($this->getName());
    }

    /**
     * Get this column's type.
     *
     * @return string The type of this column.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the column's comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Get the default value for this column.
     *
     * @return mixed
     */
    public function getDefault()
    {
        if ($this->defaultValue == 'CURRENT_TIMESTAMP') {
            return date('Y-m-d h:i:s');
        }
        return $this->defaultValue;
    }

    /**
     * Get this column's size.
     *
     * @return integer The size of this column.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Whether or not a non-NULL value is required for this column.
     *
     * @return boolean True if this column is NOT NULL, false otherwise.
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Whether or not this column is the Primary Key for its table.
     *
     * @return boolean True if this is the PK, false otherwise.
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * Whether or not this column is a unique key.
     *
     * @return boolean True if this is a Unique Key, false otherwise.
     */
    public function isUniqueKey()
    {
        return $this->isUnique;
    }

    /**
     * Whether or not this column is an auto-incrementing integer.
     *
     * @return boolean True if this column has AUTO_INCREMENT set, false otherwise.
     */
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement;
    }

    /**
     * Whether or not this column is allowed to have NULL values.
     * @return boolean
     */
    public function isNull()
    {
        return $this->isNull;
    }

    /**
     * Only NOT NULL text fields are allowed to have empty strings.
     * @return boolean
     */
    public function allowsEmptyString()
    {
        $textTypes = array('text', 'varchar', 'char');
        return $this->isNull && in_array($this->getType(), $textTypes);
    }

    /**
     * Is this a boolean field?
     *
     * This method deals with the silliness that is MySQL's boolean datatype. Or, rather, it will do when it's finished.
     * For now, it just reports true when this is a TINYINT(1) column.
     *
     * @return boolean
     */
    public function isBoolean()
    {
        return $this->getType() == 'tinyint' && $this->getSize() === 1;
    }

    /**
     * Whether or not this column is an integer, float, or decimal column.
     */
    public function isNumeric()
    {
        $isInt = substr($this->getType(), 0, 3) == 'int';
        $isDecimal = substr($this->getType(), 0, 7) == 'decimal';
        $isFloat = substr($this->getType(), 0, 5) == 'float';
        return $isInt || $isDecimal || $isFloat;
    }

    /**
     * Whether or not this column is a foreign key.
     *
     * @return boolean True if $this->_references is not empty, otherwise false.
     */
    public function isForeignKey()
    {
        return !empty($this->references);
    }

    /**
     * Get an array of attributes
     */
    public function getHtmlAttrs()
    {
        $attrs = array();
    }

    /**
     * Get the table object of the referenced table, if this column is a foreign
     * key.
     *
     * @return Table The referenced table.
     */
    public function getReferencedTable()
    {
        return $this->table->getDatabase()->getTable($this->references);
    }
    /**
     * @return string|false The name of the referenced table or false if this is
     * not a foreign key.
     */
    /* public function get_referenced_table_name()
      {
      return $this->_references;
      } */

    /**
     * Get the table that this column belongs to.
     *
     * @return Webdb_DBMS_Table The table object.
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     *
     * @param <type> $typeString
     */
    private function parseType($typeString)
    {
        //echo '<pre>Start: '.kohana::dump($type_string).'</pre>';
        //exit();
        if (preg_match('/unsigned/', $typeString)) {
            $this->_unsigned = true;
        }

        $varchar_pattern = '/^((?:var)?char)\((\d+)\)/';
        $decimal_pattern = '/^decimal\((\d+),(\d+)\)/';
        $float_pattern = '/^float\((\d+),(\d+)\)/';
        $integer_pattern = '/^((?:big|medium|small|tiny)?int|year)\(?(\d+)\)?/';
        //$integer_pattern = '/.*?(int|year)\(+(\d+)\)/';
        $enum_pattern = '/^(enum|set)\(\'(.*?)\'\)/';

        if (preg_match($varchar_pattern, $typeString, $matches)) {
            $this->type = $matches[1];
            $this->size = (int) $matches[2];
        } elseif (preg_match($decimal_pattern, $typeString, $matches)) {
            $this->type = 'decimal';
            //$colData['precision'] = $matches[1];
            //$colData['scale'] = $matches[2];
        } elseif (preg_match($float_pattern, $typeString, $matches)) {
            $this->type = 'float';
            //$colData['precision'] = $matches[1];
            //$colData['scale'] = $matches[2];
        } elseif (preg_match($integer_pattern, $typeString, $matches)) {
            $this->type = $matches[1];
            $this->size = (int) $matches[2];
        } elseif (preg_match($enum_pattern, $typeString, $matches)) {
            $this->type = $matches[1];
            $values = explode("','", $matches[2]);
            $this->_options = array_combine($values, $values);
        } else {
            $this->type = $typeString;
        }
    }

    public function __toString()
    {
        $pk = ($this->isPrimaryKey) ? ' PK' : '';
        $auto = ($this->isAutoIncrement) ? ' AI' : '';
        if ($this->references) {
            $ref = ' References ' . $this->references . '.';
        } else {
            $ref = '';
        }
        $size = ($this->size > 0) ? "($this->size)" : '';
        return $this->name . ' ' . strtoupper($this->type) . $size . $pk . $auto . $ref;
    }

    /**
     * Get an XML representation of the structure of this column.
     *
     * @return DOMElement The XML 'column' node.
     */
    public function toXml()
    {
        // Set up
        $dom = new DOMDocument('1.0', 'UTF-8');
        $table = $dom->createElement('column');
        $dom->appendChild($table);

        // name
        $name = $dom->createElement('name');
        $name->appendChild($dom->createTextNode($this->get_name()));
        $table->appendChild($name);

        // references
        $references = $dom->createElement('references');
        $references->appendChild($dom->createTextNode($this->getReferencedTableName()));
        $table->appendChild($references);

        // size
        $size = $dom->createElement('size');
        $size->appendChild($dom->createTextNode($this->get_size()));
        $table->appendChild($size);

        // type
        $type = $dom->createElement('type');
        $type->appendChild($dom->createTextNode($this->get_type()));
        $table->appendChild($type);

        // primarykey
        $primarykey = $dom->createElement('primarykey');
        $primarykey->appendChild($dom->createTextNode($this->isPrimaryKey()));
        $table->appendChild($primarykey);

        // type
        $required = $dom->createElement('required');
        $required->appendChild($dom->createTextNode($this->isRequired()));
        $table->appendChild($required);

        // Finish
        return $table;
    }
}
