<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InstallController {

    public function install(Request $request, Response $response, array $args) {
        $response = new \Symfony\Component\HttpFoundation\Response();
        $template = new \App\Template('install.twig');
        $template->title = 'Install';
        return \Symfony\Component\HttpFoundation\Response::create($template->render());
    }

    public function run(Request $request, Response $response, array $args) {
        $db = new \App\DB\Database();
        $db->install();
        return new RedirectResponse(\App\App::baseurl());
    }

}
