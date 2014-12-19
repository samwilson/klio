<?php

namespace SWFW\Controller;

class Table extends \SWFW\Controller
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
        $view->row_count = $table->count_records();
        $view->rows = $table->getRows();
        $view->render();
    }

    public function POST()
    {
        
    }
}
