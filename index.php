<?php

require 'vendor/autoload.php';

//echo $_SERVER['SCRIPT_FILENAME']."\n";
//echo $_SERVER['PHP_SELF'];
//
//$filePath = explode('/', str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])));
//print_r($filePath);
//$tempPath2 = explode('/', __DIR__);
//print_r($tempPath2);
//$tempPath3 = explode('/', str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])));
//print_r($tempPath3);
//for ($i = count($tempPath2); $i < count($filePath); $i++) {
//    array_pop($tempPath3);
//}
////$urladdr = $_SERVER['HTTP_HOST'] . implode('/', $tempPath3);
//$baseurl = implode('/', $tempPath3);
//

//echo '<pre>';
$baseurl = '/';
//print_r(explode('/', $_SERVER['PHP_SELF']));echo "\n";
//print_r(basename(__FILE__));echo "\n";
foreach (explode('/', $_SERVER['PHP_SELF']) as $part) {
    //var_dump($part);echo "\n";
    if ($part == basename(__FILE__)) {
        break;
    }
    if (!empty($part)) {
        $baseurl .= $part.'/';
    }
}
//print_r($baseurl);echo "\n";
//exit();

$swfw = new SWFW(__DIR__, $baseurl);
//$swfw->setBaseDir(__DIR__);
//$swfw->setBaseUrl();
$swfw->run();
