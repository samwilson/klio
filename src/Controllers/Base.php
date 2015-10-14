<?php

namespace App\Controllers;

abstract class Base {

    /** @var \App\DB\User */
    protected $user;

    public function __construct() {
        $this->user = new \App\DB\User();
    }

    protected function redirect($url) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse(\App\App::baseurl() . '/' . ltrim($url, '/'));
    }

}
