<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Base {

    public function index(Request $request, Response $response, array $args) {
        $db = new \App\DB\Database();
        $template = new \App\Template('home.twig');
        $template->title = 'Home';
        $template->tables = $db->getTableNames();
        if (empty($template->tables)) {
            $template->message(\App\Template::INFO, 'No tables found.', true);
            return $this->redirect('/login');
        }
        $template->user = new \App\DB\User();
        $response->setContent($template->render());
        return $response;
    }

}
