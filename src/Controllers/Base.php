<?php

namespace App\Controllers;

abstract class Base {

    public function __construct() {
        $db = new \App\DB\Database();
        if (!$db->getTable('users')) {
            new \Symfony\Component\HttpFoundation\RedirectResponse('/install');
        }
    }

}
