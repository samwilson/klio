<?php

namespace SWFW\Controller;

class Record extends \SWFW\Controller
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
        $view->columns = $table->getColumns();
        $record = $table->getRecord($recordId);
        if (!$record) {
            $view->title = $table->getTitle() . ': new record';
            echo 'not found';
        } else {
            $view->title = $table->getTitle() . ': edit record #' . $record[$table->getPkColumn()->getName()];
            $view->record = $record;
        }
        $view->render();
    }
}
