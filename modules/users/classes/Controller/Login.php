<?php

namespace Klio\Controller;

class Login extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/login/?.*',
            '/logout/?.*',
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
        if (strtoupper($this->settings->get('auth')) == 'LDAP') {
            $adldap = new \adLDAP\adLDAP($this->settings->get('ldap'));
            $authenticated = $adldap->authenticate($_POST['username'], $_POST['password']);
        }

        if ($authenticated) {
            header('Location:' . $this->getBaseUrl());
            exit(0);
        } else {
        }
    }
}
