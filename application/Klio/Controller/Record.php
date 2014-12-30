<?php

namespace Klio\Controller;

class Record extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/record/([^/]*)/?(.*)',
        );
    }

    public function GET($tableName, $recordId = null)
    {
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);
        $view = $this->getView('record');
        $view->table = $table;
        $view->columns = $table->getColumns();
        foreach ($table->getColumns() as $col) {
            if ($rt = $col->getReferencedTable()) {
                $this->view->referencedTables[$col->getName()] = $rt;
            }
        }
        $row = $table->getRow($recordId);
        if (!$row) {
            $view->title = $table->getTitle() . ': new record';
        } else {
            $pkColName = $table->getPkColumn()->getName();
            $view->title = $table->getTitle() . ': edit record #' . $row->$pkColName();
            $view->record = $row;
        }
        $view->render();
    }

    public function POST($tableName, $recordId = null)
    {
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);
        $pkVal = $table->saveRow($_POST);
        header("Location:$this->baseUrl/record/$tableName/$pkVal");
    }
}
