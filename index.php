<?php

if (php_sapi_name() == 'cli') {
    echo 'Please use cli.php instead.';
    exit(1);
}

require 'vendor/autoload.php';

$baseurl = '/';
foreach (explode('/', $_SERVER['PHP_SELF']) as $part) {
    if ($part == basename(__FILE__)) {
        break;
    }
    if (!empty($part)) {
        $baseurl .= $part . '/';
    }
}
$klio = new \Klio\App(__DIR__, $baseurl);
$klio->run();
