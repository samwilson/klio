<?php

namespace Klio;

class App
{

    private $baseUrl;
    private $baseDir;

    /** @var Modules */
    private $modules;

    public static function name()
    {
        return 'Klio';
    }

    /**
     * Get the application's version.
     *
     * Conforms to Semantic Versioning guidelines.
     * @link http://semver.org
     * @return string
     */
    public static function version()
    {
        return '0.3.0';
    }

    public function __construct($baseDir, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $this->setBaseDir($baseDir);
        session_start();
        $this->modules = new Modules($this->getBaseDir());

        // Add module paths. A hack, certainly -- but it works.
        $autoload_functions = spl_autoload_functions();
        $loader = $autoload_functions[0][0];
        foreach ($this->modules->getPaths() as $mod => $dir) {
            $loader->addPsr4('Klio\\', realpath($dir . '/classes'));
        }
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = '/' . trim($baseUrl, '/');
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function run()
    {
        // Get URI.
        $base_url_length = strlen($this->getBaseUrl());
        $uri = strtolower(substr($_SERVER['REQUEST_URI'], $base_url_length));
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $found = false;

        // Find the right controller.
        foreach ($this->modules->listDir('classes/Controller') as $cl) {
            if (substr($cl, -4) == '.php') {
                $controllerName = pathinfo($cl, PATHINFO_FILENAME);
                $controllerClass = 'Klio\Controller\\' . $controllerName;
                $controller = new $controllerClass($this->getBaseDir(), $this->getBaseUrl());
                foreach ($controller->getRoutes() as $regex) {
                    $fullRegex = '^' . str_replace('/', '\/', $regex) . '\/?$'; // Optional trailing slash
                    if (preg_match("/$fullRegex/i", $uri, $matches)) {
                        $found = true;
                        $method = strtolower($_SERVER['REQUEST_METHOD']);
                        array_shift($matches);
                        $this->callControllerMethod($controller, $method, $matches);
                        exit(0);
                    }
                }
            }
        }

        if (!$found) {
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain');
            echo "Resource not found: $uri";
            exit(1);
        }
    }

    protected function callControllerMethod($controller, $method, $params)
    {
        try {
            call_user_func_array(array($controller, $method), $params);
        } catch (\Exception $e) {
            $installUrl = $controller->getBaseUrl() . '/install';
            $errorView = $controller->getView('error');
            $errorView->title = 'Error';
            $errorView->message = '<p>' . $e->getMessage() . '</p>'
                    . '<p><a href="' . $installUrl . '" class="button radius">Install or upgrade</a></p>';
            $errorView->baseurl = $controller->getBaseUrl();
            $errorView->render();
            exit(1);
        }
    }
}
