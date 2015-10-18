<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Environment.
 */
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASS']);
session_start();
session_regenerate_id(true);

/**
 * Routes.
 */
$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/', 'App\Controllers\HomeController::index');
//$router->addRoute('GET', '/{file:[^/]*}.{ext:(?:css|js)}', 'App\Controllers\AssetsController::asset');
$router->addRoute('GET', '/install', 'App\Controllers\InstallController::install');
$router->addRoute('POST', '/install', 'App\Controllers\InstallController::run');
$router->addRoute('GET', '/login', 'App\Controllers\UsersController::login');
$router->addRoute('POST', '/login', 'App\Controllers\UsersController::loginPost');
$router->addRoute('GET', '/logout', 'App\Controllers\UsersController::logout');
$router->addRoute('GET', '/reset', 'App\Controllers\UsersController::reset');
$router->addRoute('POST', '/reset', 'App\Controllers\UsersController::resetPost');

/**
 * Tables.
 */
$router->addRoute('GET', '/table/{table}', 'tableDispatcher');
$router->addRoute('GET', '/table/{table}/{action}', 'tableDispatcher');
$router->addRoute('POST', '/table/{table}/{action}', 'tableDispatcher');

function tableDispatcher(Request $request, Response $response, $args) {
    $controllerClassname = '\\App\\Controllers\\Tables\\' . \App\App::camelcase($args['table']) . 'Controller';
    $controller = (class_exists($controllerClassname)) ? new $controllerClassname() : new App\Controllers\TableController();
    $action = (isset($args['action']) && is_callable([$controller, $args['action']])) ? $args['action'] : 'index';
    return $controller->$action($request, $response, $args);
}

/**
 * Records.
 */
$router->addRoute('GET', '/record/{table}', 'App\Controllers\RecordController::edit');
$router->addRoute('POST', '/record/{table}', 'App\Controllers\RecordController::save');
$router->addRoute('GET', '/record/{table}/{id}', 'App\Controllers\RecordController::view');
$router->addRoute('GET', '/record/{table}/{id}/edit', 'App\Controllers\RecordController::edit');
$router->addRoute('GET', '/record/{table}/{id}/delete', 'App\Controllers\RecordController::delete');

/**
 * Dispatch the request.
 */
$dispatcher = $router->getDispatcher();
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
try {
    $response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
} catch (\League\Route\Http\Exception\NotFoundException $notFound) {
    $response = new \Symfony\Component\HttpFoundation\Response($notFound->getMessage(), 404);
} catch (\Exception $e) {
    $template = new \App\Template('error.twig');
    $template->title = 'Error';
    $template->message('error', $e->getMessage());
    $template->e = $e;
    $response = new \Symfony\Component\HttpFoundation\Response($template->render());
}
$response->send();
