<?php

namespace Klio\Controller;

class Assets extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/skin/(.*)',
        );
    }

    public function GET($file)
    {
        header('Content-Type:text/css');
        $skin = \Klio\Settings::get('skin', 'default');
        $file = $this->getBaseDir() . "/skins/$skin/$file";
        //echo realpath($file);
        echo file_get_contents($file);
    }
}
