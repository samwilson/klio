<?php

namespace Klio\Controller;

class Calendar extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/calendar/(.*)',
        );
    }

    public function get($tableName, $year = null, $month = null)
    {
        $this->db = $this->getDatabase();
        $view = $this->getView('calendar');

        $table = $this->db->getTable($tableName);
        if (!$table) {
            throw new \Exception("The '$tableName' table was not found.");
        }
        $view->table = $table;
        $view->title = $table->getTitle();
        $view->subtitle = $table->getComment();

        $factory = new \CalendR\Calendar();
        $view->weekdays = $factory->getWeek(new \DateTime('Monday this week'));
        $month = $factory->getMonth(new \DateTime('First day of this month'));
        $view->month = $month;
        $records = array();
        foreach ($table->getColumns('date') as $dateCol) {
            $colName = $dateCol->getName();
            $table->addFilter($colName, '>=', $month->getBegin()->format('Y-m-d'));
            $table->addFilter($colName, '<=', $month->getEnd()->format('Y-m-d'));
            foreach ($table->getRecords() as $rec) {
                $records[$rec->$colName()] = $rec;
            }
        }
        $view->records = $records;

        $view->render();
    }
}
