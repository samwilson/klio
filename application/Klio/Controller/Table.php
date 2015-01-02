<?php

namespace Klio\Controller;

class Table extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/table/(.*)',
        );
    }

    public function GET($tableName)
    {
        $this->db = $this->getDatabase();
        $view = $this->getView('table');
        $table = $this->db->getTable($tableName);
        $view->table = $table;
        $view->columns = $table->getColumns();
        $view->title = $table->getTitle();
        $view->subtitle = $table->getComment();
        $view->row_count = $table->countRecords();
        $view->rows = $table->getRecords();
        $view->render();
    }

    public function POST()
    {
        
    }
}
