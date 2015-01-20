<?php

namespace Klio;

class View
{

    /** @var array */
    private $data = array();

    /** @var string */
    private $template = false;

    /** @var string */
    protected $baseDir;

    public function __construct($baseDir, $template = null)
    {
        $this->baseDir = $baseDir;
        // Find the template file.
        $this->template = $this->resolveTemplateFile($template);
        if (!$this->template) {
            throw new \Exception("Template not found: $template");
        }

        $this->data['app_title'] = App::name() . ' ' . App::version();
        $this->data['semver'] = App::version();
        $this->data['site_title'] = Settings::get('site_title', $this->data['app_title']);
    }

    public function resolveTemplateFile($name)
    {
        $templateFilename = "templates/$name.html";
        $mods = new Modules($this->baseDir);
        foreach ($mods->listDir(dirname($templateFilename)) as $t) {
            if (substr($t, -strlen($templateFilename)) == $templateFilename) {
                return $t;
            }
        }
        return false;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function render($return = false)
    {
        $this->queries = DB\Database::getQueries();
        $loader = new \Twig_Loader_Filesystem(dirname($this->template));
        $twig = new \Twig_Environment($loader, array(
            'debug' => true,
            'strct_variables' => true
        ));
        $twig->addExtension(new \Twig_Extension_Debug());
        $templateName = strtolower(basename($this->template));
        if (!$loader->exists($templateName)) {
            throw new \Exception("Template not found: $templateName");
        }
        $string = $twig->render($templateName, $this->data);
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
            $_SESSION['messages'][] = $msg;
        } else {
            $this->data->messages[] = $msg;
        }
    }
}
