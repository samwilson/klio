<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController {

    public function index(Request $request, Response $response, array $args) {
        $db = new \App\DB\Database();
        $template = new \App\Template('home.twig');
        $template->title = 'Home';
        $template->tables = $db->getTableNames();
        $template->user = new \App\DB\User();
        $response->setContent($template->render());
        return $response;
    }

    public function view(Request $request, Response $response, array $args) {
        return $response;
    }

}
