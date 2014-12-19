<?php

namespace SWFW;

class View
{

    /** @var array */
    private $data = array();

    /** @var string */
    private $template;

    /** @var string */
    private $baseDir;

    public function __construct($baseDir, $template = null)
    {
        $this->baseDir = $baseDir;
        $this->template = $template;
        $this->site_title = Settings::get('site_title', 'SWFW');
        $this->app_title = \SWFW::name().' '.\SWFW::version();
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function render()
    {
        $this->queries = DB\Database::getQueries();
        $skinName = Settings::get('skin', 'default');
        $skindir = $this->baseDir . '/skins/' . $skinName;
        $loader = new \Twig_Loader_Filesystem($skindir . '/html');
        $twig = new \Twig_Environment($loader);
        $templateName = strtolower($this->template . '.html');
        if (!$loader->exists($templateName)) {
            exit("Template not found: $skindir/$templateName");
        }
        echo $twig->render($templateName, $this->data);
    }

    public function skins()
    {
        $skins = scandir($this->baseDir . '/skins');
        return preg_grep('/^\./', $skins, PREG_GREP_INVERT);
    }
}
