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
        if ($this->config->get('auth') == 'LDAP') {
            $config = array(
                'account_suffix' => '',
                'base_dn' => '',
                'domain_controllers' => array(),
                'admin_username' => '',
                'admin_password' => '',
                'real_primarygroup' => '',
                'use_ssl' => '',
                'use_tls' => '',
                'recursive_groups' => '',
                'ad_port' => '',
                'sso' => '',
            );
            $adldap = new \adLDAP\adLDAP($config);
            $authenticated = $adldap->authenticate($_POST['username'], $_POST['password']);
        }

        if ($authenticated) {
            header('Location:' . $this->getBaseUrl());
            exit(0);
        } else {
        }
    }
}
