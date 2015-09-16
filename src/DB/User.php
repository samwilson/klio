<?php

namespace App\DB;

class User {

    const ADMIN_GROUP_ID = 1;
    const ADMIN_USER_ID = 1;
    const PUBLIC_GROUP_ID = 2;
    const ANON_USER_ID = 2;

    private static $data;

    /** @var string[] */
    private static $grants;

    public function __construct() {
        if (isset(self::$data->id)) {
            return;
        }
        $this->loadData();
    }

    private function loadData() {
        $db = new \App\DB\Database();
        $userId = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : self::ANON_USER_ID;
        self::$data = $db->query('SELECT `id`, `username`, `group` FROM `users` WHERE `id`=?', [1 => $userId])->fetch();
        if (!self::$data) {
            throw new \Exception("Unable to load user $userId");
        }
    }

    public function login($username, $password) {
        $db = new Database();

        // Try to log in with local credentials.
        $sql = "SELECT id, username, password FROM users WHERE username = ? LIMIT 1;";
        $userData = $db->query($sql, [1 => $username])->fetch();
        if ($userData && password_verify($password, $userData->password)) {
            self::$data = $userData;
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
                $sql1 = 'INSERT IGNORE INTO `users` (`username`, `group`) VALUES(:username, :group);';
                $db->query($sql1, ['username' => $username, 'group' => self::PUBLIC_GROUP_ID]);
                // Re-fetch data.
                $sql2 = "SELECT id, username FROM users WHERE username = :username LIMIT 1;";
                self::$data = $db->query($sql2, ['username' => $username])->fetch();
                $_SESSION['user_id'] = self::$data->id;
                return true;
            }
        }
        return false;
    }

    public function logout() {
        $_SESSION['user_id'] = self::ANON_USER_ID;
        $this->loadData();
    }

    public function getId() {
        return self::$data->id;
    }

    public function getUsername() {
        return self::$data->username;
    }

    public function isAnon() {
        return self::$data->id == self::ANON_USER_ID;
    }

    public function inGroup($groupId) {
        $db = new Database();
        $sql = 'SELECT 1 FROM users u JOIN groups g ON u.group=g.id WHERE u.id=? AND g.id=?';
        return $db->query($sql, [1 => self::$data->id, 2 => $groupId])->fetchColumn();
    }

    public function isAdmin() {
        return $this->inGroup(self::ADMIN_GROUP_ID);
    }

    public function can($grant, $tableName) {
        if (!is_array(self::$grants)) {
            $db = new Database();
            $sql = 'SELECT `grant`, `table_name`'
                    . ' FROM `grants`'
                    . '   JOIN `groups` ON `grants`.`group` = `groups`.`id`'
                    . '   JOIN `users` ON `groups`.id = `users`.`group`'
                    . ' WHERE `users`.`id` = :user_id';
            $params = ['user_id' => self::$data->id];
            self::$grants = [];
            $grants = $db->query($sql, $params)->fetchAll();
            foreach ($grants as $g) {
                self::$grants[$g->table_name][$g->grant] = true;
            }
        }
        return isset(self::$grants[$tableName][$grant]);
    }

}
