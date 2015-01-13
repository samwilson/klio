<?php

namespace Klio\DB;

class Record
{

    /**
     *
     * @var Table
     */
    protected $table;

    protected $data = array();

    const FKTITLE = 'FKTITLE';

    /**
     * Create a new Record object.
     * @param Table $table The table object.
     * @param array $data The data of this record.
     */
    public function __construct($table)
    {
        $this->table = $table;
        //$this->data = array();
    }

    public function __set($name, $value)
    {
        //echo "<li>Setting $name to $value";
        $this->data[$name] = $value;
    }

    public function __call($name, $args)
    {
        // Foreign key 'title' values.
        $useTitle = substr($name, -strlen(self::FKTITLE)) == self::FKTITLE;
        if ($useTitle) {
            $name = substr($name, 0, -strlen(self::FKTITLE));
            if ($this->table->getColumn($name)->isForeignKey() && !empty($this->data[$name])) {
                $referencedTable = $this->table->getColumn($name)->getReferencedTable();
                $fkRecord = $referencedTable->getRecord($this->data[$name]);
                $fkTitleCol = $referencedTable->getTitleColumn();
                $fkTitleColName = $fkTitleCol->getName();
                if ($fkTitleCol->isForeignKey()) {
                    // Use title if the FK's title column is also an FK.
                    $fkTitleColName .= self::FKTITLE;
                }
                return $fkRecord->$fkTitleColName();
            }
        }
        // Booleans
        if ($this->table->getColumn($name)->isBoolean()) {
            // Numbers are fetched from the DB as strings.
            if ($this->data[$name] === '1') {
                return true;
            } elseif ($this->data[$name] === '0') {
                return false;
            } else {
                return null;
            }
        }
        // Standard column values.
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
    }

    public function __toString()
    {
        return print_r($this->data, true);
    }

    public function getPrimaryKey()
    {
        return $this->data[$this->table->getPkColumn()->getName()];
    }

    public function getTitle()
    {
        $titleCol = $this->table->getTitleColumn()->getName();
        return $this->data[$titleCol];
    }

    public function getReferencedRow($columnName)
    {
        $this->table->getColumn($columnName)->getReferencedTable()->getRow($this->data[$columnName]);
    }
}
