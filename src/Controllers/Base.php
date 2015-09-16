<?php

namespace App\Controllers;

abstract class Base {

    /** @var \App\DB\User */
    protected $user;

    public function __construct() {
        $this->user = new \App\DB\User();
    }

//    protected function getDb() {
//        $db = new \App\DB\Database();
//        if (!$db->getTable('users')) {
//            throw new \Exception('users table not found');
//        }
//        return $db;
//    }

}
