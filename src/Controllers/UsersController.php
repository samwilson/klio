<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DB\Database;
use App\DB\Grants;

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

        // Try to log in.
        
        if (Auth::attempt(['username' => $username, 'password' => $password])) {
            $this->alert('success', 'You are now logged in.', TRUE);
            return redirect()->intended();
        }

        // If that fails, try Adldap.
        $adldapConfig = config('adldap');
        if ($adldapConfig['enabled']) {
            $adldap = new \Adldap\Adldap($adldapConfig);
            if (empty($adldap->getConfiguration()->getAdminUsername())) {
                $adldap->getConfiguration()->setAdminUsername($username);
                $adldap->getConfiguration()->setAdminPassword($password);
            }
            try {
                $adldap->authenticate($username, $password);
                $user = \App\Model\User::firstOrCreate(['username' => $username]);
                $ldapUser = $adldap->users()->find($username);
                $user->name = $ldapUser->getDisplayName();
                $user->email = $ldapUser->getEmail();
                $user->save();
                Auth::login($user);
                $this->alert('success', 'You are now logged in.', TRUE);
                return redirect('/');
            } catch (\Adldap\Exceptions\AdldapException $ex) {
                // Invalid credentials.
            }
        }

        // If we're still here, authentication has failed.
        $this->alert('warning', 'Athentication failed.');
        return redirect()->back()->withInput();
    }

    public function logout() {
        Auth::logout();
        $this->alert('success', 'You have been logged out.');
        return redirect('/login');
    }

}
