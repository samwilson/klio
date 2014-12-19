<?php

class SWFW
{

    private $baseUrl;

    private $baseDir;

    public static function name()
    {
        return 'Klio';
    }

    public static function version()
    {
        return '0.1.0';
    }

    function __construct($baseDir, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $this->baseDir = $baseDir;
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

        foreach (scandir(__DIR__ . '/SWFW/Controller') as $cl) {
            if (substr($cl, -4) == '.php') {
                $controllerName = pathinfo($cl, PATHINFO_FILENAME);
                $controllerClass = 'SWFW\Controller\\' . $controllerName;
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

//        $base_url_length = strlen($this->getBaseUrl()) + 1;
//        $uri = strtolower(substr($_SERVER['REQUEST_URI'], $base_url_length));
//        if (strpos($uri, '?') !== false) {
//            $uri = substr($uri, 0, strpos($uri, '?'));
//        }
//        $request = explode('/', $uri);
//        $controller_name = ucwords(array_shift($request));
//        if (empty($controller_name)) {
//            $controller_name = $this->default_controller_name;
//        }
//        $action_name = array_shift($request);
//        $controller_classname = 'SWFW\\Controller\\' . $controller_name;
//        //var_dump($controller_classname);
//        if (class_exists($controller_classname)) {
//            $controller = new $controller_classname($this, $action_name);
//            $action_name = $controller->currentAction();
//            if (method_exists($controller, $action_name)) {
//                call_user_func_array(array($controller, $action_name), $request);
//            }
//        } else {
//            header('HTTP/1.1 404 Not Found');
//            echo "Controller Not Found: $controller_name";
//            exit(1);
//        }
    }
}
