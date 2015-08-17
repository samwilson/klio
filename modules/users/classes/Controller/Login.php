<?php

namespace Klio\Controller;

use Hautelook\Phpass\PasswordHash;

class Login extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/login/?.*',
        );
    }

    public function get()
    {
        $view = $this->getView('login.html');
        $view->title = 'Login';
        $view->auth = $this->settings->get('auth');
        $view->ldap = $this->settings->get('ldap');
        $view->render();
    }

    public function post()
    {
        $authenticated = false;

        // Try LDAP first.
        if (strtoupper($this->settings->get('auth')) === 'LDAP') {
            $adldap = new \Adldap\Adldap($this->settings->get('ldap'));
            $authenticated = $adldap->authenticate($_POST['username'], $_POST['password']);
            if ($authenticated) {
                $passwordHasher = new \Hautelook\Phpass\PasswordHash(8, false);
                $sql = 'INSERT IGNORE INTO users SET username = :user, password = :pass';
                $this->db->query($sql, [':user' => $_POST['username'], ':pass' => $passwordHasher->HashPassword($_POST['password'])]);
            }
        }

        // Then try DB.
        $db = $this->getDatabase();
        $sql = 'SELECT * FROM users WHERE username = :username';
        $user = $db->query($sql, [':username' => $_POST['username']])->fetch();
        if ($user) {
            $passwordHasher = new PasswordHash(8, false);
            $authenticated = $passwordHasher->CheckPassword($_POST['password'], $user->password);
        }

        // Save the user ID to the session.
        if ($authenticated) {
            \Klio\App::session()->set('user_id', $user->id);
            $this->getView('login.html')->message('success', 'You are now logged in.', true);
            header('Location:' . $this->getBaseUrl());
            exit(0);
        } else {
            $this->getView('login.html')->message('info', 'Unable to authenticate.', true);
            $this->get();
        }
    }
}
