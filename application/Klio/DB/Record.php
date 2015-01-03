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
        //echo "<li>Calling [$name] with ".print_r($args, TRUE);
        // Foreign key 'title' values.
        $useTitle = substr($name, -strlen(self::FKTITLE)) == self::FKTITLE;
        if ($useTitle) {
            $name = substr($name, 0, -strlen(self::FKTITLE));
            if ($this->table->getColumn($name)->isForeignKey()) {
                $referencedTable = $this->table->getColumn($name)->getReferencedTable();
                $fkTitleColName = $referencedTable->getTitleColumn()->getName();
                return $referencedTable->getRecord($this->data[$name])->$fkTitleColName();
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
