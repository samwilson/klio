<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UsersController extends Base {

    public function login(Request $request, Response $response, array $args) {
        $template = new \App\Template('login.twig');
        $template->title = 'Log in';
        $template->user = $this->user;
        $template->username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
        unset($_SESSION['username']);
        if (\App\App::env('ADLDAP_ENABLED')) {
            $template->ldap_suffix = \App\App::env('ADLDAP_SUFFIX');
        }
        $response->setContent($template->render());
        return $response;
    }

    public function loginPost(Request $request, Response $response, array $args) {
        $username = $request->get('username');
        $password = $request->get('password');
        if (!$username || !$password) {
            return RedirectResponse::create(\App\App::url('/login'));
        }
        $loggedIn = $this->user->login($username, $password);
        $template = new \App\Template('login.twig');
        if (!$loggedIn) {
            $template->message(\App\Template::WARNING, 'Log in failed.', true);
            $_SESSION['username'] = $username;
            return RedirectResponse::create(\App\App::url('/login'));
        } else {
            $template->message(\App\Template::INFO, 'Logged in.', true);
            return RedirectResponse::create(\App\App::url('/'));
        }
    }

    public function logout() {
        $this->user->logout();
        $template = new \App\Template('login.twig');
        $template->message(\App\Template::INFO, 'You have been logged out.', true);
        return RedirectResponse::create(\App\App::url('/login'));
    }

    public function reset(Request $request, Response $response, array $args) {
        $template = new \App\Template('reset.twig');
        if (\App\App::env('ADLDAP_ENABLED')) {
            $template->message('info', "Passwords managed in LDAP. Sorry, you can't change your password here.");
        }
        $template->title = 'Password reset';
        $template->user = $this->user;
        $response->setContent($template->render());
        return $response;
    }

    public function resetPost(Request $request, Response $response, array $args) {
        $username = $request->get('username');
        if (!$username) {
            return RedirectResponse::create(\App\App::url('/reset'));
        }
        $db = new \App\DB\Database();
        $sql = "SELECT `email` FROM `users` WHERE `username` = ?";
        $user = $db->query($sql, [1=>$username])->fetch();
        if ($user) {
        }
        $template = new \App\Template('reset.twig');
        $template->message('info', 'Please check your email.');
        return RedirectResponse::create(\App\App::url('/login'));
    }

}
