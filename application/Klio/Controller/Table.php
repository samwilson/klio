<?php

namespace Klio\Controller;

class Table extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/table/([^/]*)/?p?(\d*)',
        );
    }

    public function GET($tableName, $pageNum = 1)
    {
        if (empty($pageNum)) {
            $pageNum = 1;
        }
        $view = $this->getView('table');
        $table = $this->db->getTable($tableName);
        $table->setCurrentPageNum($pageNum);
        $view->table = $table;
        $view->columns = $table->getColumns();
        $view->title = $table->getTitle();
        $view->subtitle = $table->getComment();
        $view->row_count = $table->countRecords();
        $view->records = $table->getRecords();
        $view->render();
    }

    public function POST()
    {
        
    }
}
