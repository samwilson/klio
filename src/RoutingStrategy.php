<?php

namespace App;

class RoutingStrategy implements \League\Route\Strategy\StrategyInterface {

    public function dispatch($controllerDetails, array $vars) {
        $controllerName = $controllerDetails[0];
        $controller = new $controllerName;
        $controller->setAction($controllerDetails[1]);
        var_dump($controller);
        $controller->$
//        if (is_array($controller)) {
//            $controller = [
//                $this->container->get($controller[0]),
//                $controller[1]
//            ];
//        }
//
//        $response = $this->container->call($controller, $vars);
//
//        return $this->determineResponse($response);
    }

}
