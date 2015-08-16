<?php

namespace Klio;

class Settings
{

    /** @var string */
    private $baseDir;

    /** @var array */
    private $settings = array();

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function get($name)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }
        $tried = array();
        $modules = new Modules($this->baseDir);
        $settingsFiles = $modules->listFiles('settings.php');
        $localSettings = realpath($this->baseDir . '/settings.php');
        if ($localSettings) {
            $settingsFiles = array_merge(array($localSettings), $settingsFiles);
        }
        foreach ($settingsFiles as $path) {
            $tried[] = $path;
            include $path;
            if (isset($$name)) {
                $this->settings[$name] = $$name;
                return $$name;
            }
        }
        throw new \Exception("Setting not found: '$name' in:<br />" . join('<br />', $tried));
    }
}
