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

    public function get($tableName, $pageNum = 1)
    {
        if (empty($pageNum)) {
            $pageNum = 1;
        }
        $this->db = $this->getDatabase();
        $view = $this->getView('table');
        $table = $this->db->getTable($tableName);
        $table->setCurrentPageNum($pageNum);
        $view->table = $table;
        $view->columns = $table->getColumns();
        $view->title = $table->getTitle();
        $view->subtitle = $table->getComment();

        // Filters.
        $view->operators = $table->getOperators();
        $table->addFilters(\Klio\Arr::get($_GET, 'filter', array()));
        $filters = $table->getFilters();
        $filters[] = array(
            'column' => $table->getTitleColumn()->getName(),
            'operator' => 'like',
            'value' => ''
        );
        $view->filters = $filters;
        $view->filterCount = count($filters);

        $view->row_count = $table->countRecords();
        $view->records = $table->getRecords();
        $view->render();
    }

    public function post()
    {
        
    }

}
