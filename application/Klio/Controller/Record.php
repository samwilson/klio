<?php

namespace Klio\Controller;

class Record extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/record/([^/]*)/?([^/]*)',
        );
    }

    public function GET($tableName, $recordId = null)
    {
        $view = $this->getView('record');
        $table = $this->db->getTable($tableName);
        $view->table = $table;
        $view->columns = $table->getColumns();
        foreach ($table->getColumns() as $col) {
            if ($rt = $col->getReferencedTable()) {
                $this->view->referencedTables[$col->getName()] = $rt;
            }
        }
        $record = $table->getRecord($recordId);
        if ($recordId && !$record) {
            throw new \Exception("Record not found: $recordId");
        }
        $view->title = $table->getTitle();
        if (!$record) {
            $view->subtitle = 'New record';
        } else {
            $view->subtitle = 'Edit record: ' . $record->getPrimaryKey();
            $view->record = $record;
        }
        $view->render();
    }

    public function POST($tableName, $recordId = FALSE)
    {
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);

        $existing = $table->getRecord($_POST[$table->getPkColumn()->getName()]);
        // Make sure we're not saving over an already-existing record.
        if (!$recordId && $existing) {
            echo "Already exists; not updating.";
        } else {
            // Otherwise, create a new one.
            $pkVal = $table->saveRecord($_POST, $recordId);
            header("Location:$this->baseUrl/record/$tableName/$pkVal");
        }
    }
}
