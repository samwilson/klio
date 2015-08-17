<?php

namespace Klio\Controller;

class Logout extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/logout.*',
        );
    }

    public function get()
    {
        setcookie(session_name(), '', time() - 3600, '/');
        session_destroy();
        header("Location:" . $this->getBaseUrl());
        exit(0);
    }
}
