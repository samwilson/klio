<?php

namespace Klio;

class Controller
{

    protected $baseDir;

    protected $baseUrl;

    /** @var \Klio\DB\Database The database. */
    protected $db;

    public function __construct($baseDir, $baseUrl)
    {
        $this->baseDir = $baseDir;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get the database, or display an error and die.
     * @return \Klio\DB\Database The database object
     */
    public function getDatabase()
    {
        try {
            return new DB\Database();
        } catch (\PDOException $e) {
            $installUrl = $this->getBaseUrl() . '/install';
            $errorView = new View('error');
            $errorView->title = 'Error';
            $errorView->message = 'Unable to get the database.<br /><a href="' . $installUrl . '" class="button radius">Install or upgrade</a>';
            $errorView->baseurl = $this->getBaseUrl();
            $errorView->render();
            exit(1);
        }
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getView($template)
    {
        $view = new View($this->getBaseDir(), $template);
        $view->title = Settings::siteTitle();
        $view->baseurl = $this->getBaseUrl();
        if ($this->db) {
            $view->tables = $this->db->getTables();
        }
        return $view;
    }

    public function getRoutes()
    {
        return array();
    }

    public function message($type, $message, $delayed = FALSE)
    {
        
    }
}
