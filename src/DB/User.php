<?php

namespace App\DB;

class User {

    const ADMIN_ID = 1;
    const ANON_ID = 2;

    private static $data;

    public function __construct() {
        if (isset(self::$data->id)) {
            return;
        }
        $db = new \App\DB\Database();
        $userId = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : self::ANON_ID;
        self::$data = $db->query('SELECT * FROM `users` WHERE `id`=?', [1 => $userId])->fetch();
    }

    public function isAnon() {
        return self::$data->id == self::ANON_ID;
    }

    public function isAdmin() {
        return self::$data->id == self::ADMIN_ID;
    }

    public function can($grant, $table) {
        
    }

}
