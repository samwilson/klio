<?php

namespace App\DB;

class User {

    const ADMIN_GROUP_ID = 1;
    const ADMIN_USER_ID = 1;
    const ANON_GROUP_ID = 2;
    const ANON_USER_ID = 2;

    private static $data;

    public function __construct() {
        if (isset(self::$data->id)) {
            return;
        }
        $db = new \App\DB\Database();
        $userId = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : self::ANON_GROUP_ID;
        self::$data = $db->query('SELECT * FROM `users` WHERE `id`=?', [1 => $userId])->fetch();
    }

    public function login($username, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $db = new Database();

        // Try to log in with local credentials.
        $sql = "SELECT id, username FROM users WHERE username = ? AND password = ? LIMIT 1;";
        self::$data = $db->query($sql, [1 => $username, 2 => $hashedPassword])->fetch();
        if (self::$data) {
            $_SESSION['user_id'] = self::$data->id;
            return true;
        }

        // If that fails, try Adldap.
        if (\App\App::env('ADLDAP_ENABLED')) {
            $adldapConfig = [
                'account_suffix' => \App\App::env('ADLDAP_SUFFIX'),
                'domain_controllers' => [\App\App::env('ADLDAP_DC1')],
                'base_dn' => \App\App::env('ADLDAP_BASEDN'),
                'admin_username' => \App\App::env('ADLDAP_ADMINUSER'),
                'admin_password' => \App\App::env('ADLDAP_ADMINPASS'),
            ];
            $adldap = new \Adldap\Adldap($adldapConfig);
            $loggedIn = $adldap->authenticate($username, $password, true);
            if ($loggedIn) {
                $sql = 'INSERT IGNORE INTO `users` (`username`, `group`) VALUES(:username, :group);';
                $db->query($sql, ['username' => $username, 'group' => self::ANON_GROUP_ID]);
                // Re-fetch data.
                $sql = "SELECT id, username FROM users WHERE username = :username LIMIT 1;";
                self::$data = $db->query($sql, ['username' => $username])->fetch();
                $_SESSION['user_id'] = self::$data->id;
                return true;
            }
        }
        return false;
    }

    public function isAnon() {
        return $this->inGroup(self::ANON_GROUP_ID);
    }

    public function inGroup($groupId) {
        $db = new Database();
        $sql = 'SELECT 1 FROM users u JOIN groups g ON u.group=g.id WHERE u.id=?';
        return $db->query($sql, [1 => self::$data->id])->fetchColumn();
    }

    public function isAdmin() {
        return self::$data->id == self::ADMIN_ID;
    }

    public function can($grant, $table) {
        $db = new Database();
        $sql = 'SELECT 1'
                . ' FROM `grants`'
                . '   JOIN `groups` ON `grants`.`group` = `groups`.`id`'
                . '   JOIN `users` ON `groups`.id = `users`.`group`'
                . ' WHERE `users`.`id` = :user_id AND `grants`.`grant` = :grant';
        return $db->query($sql, ['user_id' => self::$data->id, 'grant' => $grant])->fetchColumn();
    }

}
