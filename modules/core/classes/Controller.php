<?php

namespace Klio;

class Controller
{

    const EVENT_BEFORE = 'controller.before';
    const EVENT_AFTER = 'controller.after';
    const EVENT_GET_VIEW = 'controller.get_view';

    /** @var string The session variable under which to store the list of grouped table names. */
    const SESSION_GROUPED_TABLES = 'controller.grouped_tables';

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

    public function before()
    {
        \Klio\App::dispatch(self::EVENT_BEFORE, new Event(['controller' => $this]));
    }

    public function after()
    {
        \Klio\App::dispatch(self::EVENT_AFTER, new Event(['controller' => $this]));
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
            $tables = App::session()->get(self::SESSION_GROUPED_TABLES);
            if (!$tables) {
                App::session()->set(self::SESSION_GROUPED_TABLES, $this->db->getTables(true));
            }
            $view->tables = App::session()->get(self::SESSION_GROUPED_TABLES);
        }
        $mods = new Modules($this->baseDir);
        $view->modulePaths = $mods->getPaths();
        \Klio\App::dispatch(self::EVENT_GET_VIEW, new Event(['controller' => $this, 'view' => $view]));
        return $view;
    }

    public function getRoutes()
    {
        return array();
    }
}
