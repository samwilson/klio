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
        if (!$table) {
            throw new \Exception("The '$tableName' table was not found.");
        }
        $view = $this->getView('delete.html');
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
            header("Location:" . $this->getBaseUrl() . "/record/$tableName/$recordId");
            exit(0);
        }
        $this->db = $this->getDatabase();
        $table = $this->db->getTable($tableName);
        $table->deleteRecord($recordId);
        header("Location:" . $this->getBaseUrl() . "/table/$tableName");
        exit(0);
    }
}
