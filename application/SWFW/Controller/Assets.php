<?php

namespace SWFW\Controller;

class Assets extends \SWFW\Controller
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
        $skin = \SWFW\Settings::get('skin', 'default');
        $file = $this->getBaseDir() . "/skins/$skin/$file";
        //echo realpath($file);
        echo file_get_contents($file);
    }
}
