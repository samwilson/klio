<?php

namespace Klio\Controller;

class Assets extends \Klio\Controller
{

    public function getRoutes()
    {
        return array(
            '/assets/\d+\.\d+\.\d+/(.*)',
        );
    }

    public function get($file)
    {
        header('Content-Type:text/css');
        $mods = new \Klio\Modules($this->getBaseDir());
        foreach ($mods->listDir('assets') as $a) {
            $filepath = dirname($a) . '/' . $file;
            if (file_exists($filepath)) {
                echo file_get_contents($filepath);
                exit(0);
            }
        }
        throw new \Exception("Asset file not found: $file");
        //$skin = \Klio\Settings::get('skin', 'default');
        //$file = $this->getBaseDir() . "/skins/$skin/$file";
        //echo realpath($file);
        //echo file_get_contents($file);
    }
}
