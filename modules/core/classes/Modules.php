<?php

namespace Klio;

class Modules
{

    /** @var string */
    private $baseDir;

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function getPaths()
    {
        $out = array();
        $pluginsDir = $this->baseDir . '/modules';
        foreach (scandir($pluginsDir) as $f) {
            if ($f[0] != '.' && is_dir($pluginsDir . '/' . $f)) {
                $out[$f] = realpath($pluginsDir . '/' . $f);
            }
        }
        return $out;
    }

    public function listDir($dir)
    {
        $out = array();
        foreach ($this->getPaths() as $modDir) {
            if (!is_dir($modDir . '/' . $dir)) {
                continue;
            }
            foreach (scandir($modDir . '/' . $dir) as $d) {
                if ($d[0] == '.') {
                    continue;
                }
                $out[$d] = "$modDir/$dir/$d";
            }
        }
        return $out;
    }

    public function register($event, $callback)
    {
        
    }

    public function fire($event)
    {
        foreach ($this->getPaths() as $mod) {
            
        }
    }
}
