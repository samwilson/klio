<?php

namespace Klio\Controller;

class RecordDelete extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/record/([^/]+)/([^/]+)/delete',
        );
    }

    public function get($tableName, $recordId = null)
    {
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);
        $view = $this->getView('delete');
        $view->table = $table;
        $row = $table->getRecord($recordId);
        if (!$row) {
            echo "Not found.";
            exit(1);
        } else {
            $view->title = $table->getTitle();
            $view->subtitle = 'Deleting record: ' . $row->getPrimaryKey();
            $view->record = $row;
        }
        $view->render();
    }

    public function post($tableName, $recordId = null)
    {
        if (!\Klio\Arr::get($_POST, 'confirm')) {
            header("Location:$this->baseUrl/record/$tableName/$recordId");
            exit(0);
        }
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);
        $table->deleteRecord($recordId);
        header("Location:$this->baseUrl/table/$tableName");
        exit(0);
    }
}
