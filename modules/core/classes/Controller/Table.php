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

    public function getNavItems()
    {
        return array(
            'Search'
        );
    }

    public function get($tableName, $pageNum = 1)
    {
        if (empty($pageNum)) {
            $pageNum = 1;
        }
        $this->db = $this->getDatabase();
        $view = $this->getView('table.html');
        $table = $this->db->getTable($tableName);
        if (!$table) {
            throw new \Exception("The '$tableName' table was not found.");
        }
        $table->setCurrentPageNum($pageNum);
        $table->setRecordsPerPage($this->settings->get('records_per_page'));
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
