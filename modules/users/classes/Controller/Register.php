<?php

namespace Klio\Controller;

use Hautelook\Phpass\PasswordHash;

class Register extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/register/?.*',
        );
    }

    public function get()
    {
        $view = $this->getView('register.html');
        $view->title = 'Register';
        $view->render();
    }

    public function post()
    {
        $passwordHasher = new PasswordHash(8, false);
        $password = $passwordHasher->HashPassword($_POST['password']);
        $userTable = new \Klio\DB\Tables\Users($this->db);
        $userTable->saveRecord([
            'username' => $_POST['username'],
            'password' => $password,
        ]);
    }
}
