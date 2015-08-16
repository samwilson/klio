<?php

namespace Klio;

class Controller
{

    /** @var string */
    private $baseDir;

    /** @var string */
    private $baseUrl;

    /** @var \Klio\DB\Database The database. */
    protected $db;

    /** @var Settings */
    protected $settings;

    public function __construct($baseDir, $baseUrl)
    {
        $this->baseDir = $baseDir;
        $this->baseUrl = $baseUrl;
        $this->settings = new \Klio\Settings($this->getBaseDir());
    }

    /**
     * Get the database.
     * @return \Klio\DB\Database The database object
     */
    public function getDatabase()
    {
        return new DB\Database($this->settings->get('database'));
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Get a View object for a given template.
     * @param string $template The filename of the template.
     * @return \Klio\View
     */
    public function getView($template)
    {
        $view = new View($this->getBaseDir(), $template);
        $view->baseurl = $this->getBaseUrl();
        $view->site_title = $this->settings->get('site_title');
        $view->app_version = App::version();
        $view->app_name = App::name();
        if ($this->db) {
            $view->tables = $this->db->getTables(true);
        }
        $mods = new Modules($this->baseDir);
        $view->modulePaths = $mods->getPaths();
        return $view;
    }

    public function getRoutes()
    {
        return array();
    }
}
