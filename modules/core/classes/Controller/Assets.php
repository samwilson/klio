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
                // @TODO add mime types.
                echo file_get_contents($filepath);
                exit(0);
            }
        }
        throw new \Exception("Asset file not found: $file");
    }
}
