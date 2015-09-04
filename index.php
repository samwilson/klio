<?php

require __DIR__ . '/vendor/autoload.php';

/**
 * Environment.
 */
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASS']);

/**
 * Routes.
 */
$router = new League\Route\RouteCollection;

$router->addRoute('GET', '/{file:.*\.(?:css|js)}', 'App\Controllers\AssetsController::css');
$router->addRoute('GET', '/install', 'App\Controllers\InstallController::install');
$router->addRoute('POST', '/install', 'App\Controllers\InstallController::run');
$router->addRoute('GET', '/', 'App\Controllers\HomeController::index');

$router->addRoute('GET', '/login', 'App\Controllers\UsersController::login');
$router->addRoute('POST', '/login', 'App\Controllers\UsersController::loginPost');
$router->addRoute('GET', '/logout', 'App\Controllers\UsersController::logout');

$router->addRoute('GET', '/{id:number}', 'App\Controllers\HomeController::view');
$router->addRoute('GET', '/table/{table}', 'App\Controllers\TableController::view');
$router->addRoute('GET', '/record/{table}/{id}', 'App\Controllers\RecordController::view');
$router->addRoute('GET', '/record/{table}/{id}/edit', 'App\Controllers\RecordController::edit');
$router->addRoute('GET', '/record/{table}/{id}/delete', 'App\Controllers\RecordController::delete');

$dispatcher = $router->getDispatcher();
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
try {
    $response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
} catch (\League\Route\Http\Exception\NotFoundException $notFound) {
    $response = new \Symfony\Component\HttpFoundation\Response('Not Found', 404);
} catch (\Exception $e) {
    $template = new \App\Template('base.twig');
    $template->title = 'Error';
    $template->message('error', $e->getMessage());
    $response = new \Symfony\Component\HttpFoundation\Response($template->render());
}
$response->send();
