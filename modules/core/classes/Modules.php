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

    /**
     * Get a list of paths to modules, and module names.
     * @return array|string Keys are module names, values are their full filesystem paths.
     */
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
                $out[] = realpath("$modDir/$dir/$d");
            }
        }
        return $out;
    }

    /**
     * Get the paths of all files matching the given path and filename.
     * @param string The filename to search for.
     */
    public function listFiles($file)
    {
        $out = array();
        foreach ($this->listDir(dirname($file)) as $path) {
            if (strpos($path, $file)) {
                $out[] = $path;
            }
        }
        return $out;
    }
}
