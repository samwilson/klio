<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UsersController extends Base {

    public function login(Request $request, Response $response, array $args) {
        $template = new \App\Template('login.twig');
        $template->title = 'Log in';
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
            return RedirectResponse::create(\App\App::url('/login'));
        } else {
            $template->message(\App\Template::INFO, 'Logged in.', true);
            return RedirectResponse::create(\App\App::url('/'));
        }
    }

    public function logout() {
        Auth::logout();
        $this->alert('success', 'You have been logged out.');
        return redirect('/login');
    }

}
