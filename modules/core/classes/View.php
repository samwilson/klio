<?php

namespace Klio;

class View
{

    /** @var array */
    private $data = array();

    /** @var string */
    private $template = false;

    /** @var Modules */
    protected $modules;

    public function __construct($baseDir, $template = null)
    {
        $this->template = $template;

        $this->modules = new Modules($baseDir);
        $this->data['modules'] = array_keys($this->modules->getPaths());

        $this->data['app_title'] = App::name() . ' ' . App::version();
        $this->data['semver'] = App::version();

        $settings = new Settings($baseDir);
        $this->data['site_title'] = $settings->get('site_title', $this->data['app_title']);

        $this->data['alerts'] = Arr::get($_SESSION, 'alerts', array());
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function render($return = false)
    {
        $this->queries = DB\Database::getQueries();
        $loader = new \Twig_Loader_Filesystem();
        foreach ($this->modules->getPaths() as $path) {
            $loader->addPath($path . '/templates');
        }
        $twig = new \Twig_Environment($loader, array(
            'debug' => true,
            'strct_variables' => true
        ));
        $twig->addExtension(new \Twig_Extension_Debug());
        $string = $twig->render($this->template, $this->data);
        if (!$return) {
            echo $string;
        } else {
            return $string;
        }
    }

    /**
     * Display a message to the user.
     *
     * @param string $type One of 'success', 'warning', 'info', or 'alert'.
     * @param string $message The text of the message.
     * @param boolean $delayed Whether to delay the message until the next request.
     */
    public function message($type, $message, $delayed = false)
    {
        $msg = array(
            'type' => $type,
            'message' => $message,
        );
        if ($delayed) {
            $_SESSION['alerts'][] = $msg;
        } else {
            $this->data['alerts'][] = $msg;
        }
    }
}
