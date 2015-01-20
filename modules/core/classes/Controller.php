<?php

namespace Klio;

class Controller
{

    /** @var string */
    protected $baseDir;

    /** @var string */
    protected $baseUrl;

    /** @var \Klio\DB\Database The database. */
    protected $db;

    public function __construct($baseDir, $baseUrl)
    {
        $this->baseDir = $baseDir;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get the database.
     * @return \Klio\DB\Database The database object
     */
    public function getDatabase()
    {
        return new DB\Database();
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

//    public function setBaseDir($baseDir)
//    {
//        $this->baseDir = $baseDir;
//    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

//    public function setBaseUrl($baseUrl)
//    {
//        $this->baseUrl = $baseUrl;
//    }

    public function getView($template)
    {
        $view = new View($this->getBaseDir(), $template);
        $view->baseurl = $this->getBaseUrl();
        $view->title = Settings::siteTitle();
        if ($this->db) {
            $view->tables = $this->db->getTables(true);
        }
        return $view;
    }

    public function getRoutes()
    {
        return array();
    }
}
