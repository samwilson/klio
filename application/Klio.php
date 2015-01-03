<?php

class Klio
{

    private $baseUrl;

    private $baseDir;

    public static function name()
    {
        return 'Klio';
    }

    public static function version()
    {
        return '0.2.0';
    }

    function __construct($baseDir, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $this->baseDir = $baseDir;
        session_start();
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

        // Get URI
        $base_url_length = strlen($this->getBaseUrl());
        $uri = strtolower(substr($_SERVER['REQUEST_URI'], $base_url_length));
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $found = FALSE;

        foreach (scandir(__DIR__ . '/Klio/Controller') as $cl) {
            if (substr($cl, -4) == '.php') {
                $controllerName = pathinfo($cl, PATHINFO_FILENAME);
                $controllerClass = 'Klio\Controller\\' . $controllerName;
                $controller = new $controllerClass($this->getBaseDir(), $this->getBaseUrl());
                foreach ($controller->getRoutes() as $regex) {
                    $fullRegex = '^' . str_replace('/', '\/', $regex) . '\/?$'; // Optional trailing slash
                    if (preg_match("/$fullRegex/i", $uri, $matches)) {
                        $found = TRUE;
                        $method = strtoupper($_SERVER['REQUEST_METHOD']);
                        array_shift($matches);
                        call_user_func_array(array($controller, $method), $matches);
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

    public static function getControllerNames()
    {
        $out = array();
        foreach (scandir(__DIR__ . '/Klio/Controller') as $cl) {
            $controllerName = pathinfo($cl, PATHINFO_FILENAME);
            $controllerClass = 'Klio\Controller\\' . $controllerName;
            if (class_exists($controllerClass)) {
                $out[] = $controllerClass;
            }
        }
        return $out;
    }
}