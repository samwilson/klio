<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetsController extends \App\Controllers\Base {

    public function asset(Request $request, Response $response, array $args) {
        $filename = 'assets/' . $args['file'] . '.' . $args['ext'];
        $response->setContent(file_get_contents($filename));
        $mime = ($args['ext'] == 'css') ? 'css' : 'javascript';
        $response->headers->set('Content-Type', 'text/' . $mime);
        return $response;
    }

}
