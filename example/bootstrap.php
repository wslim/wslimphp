<?php
$vendorPath = dirname(dirname(dirname(__DIR__))) . '/';

defined('ROOT_PATH') || define('ROOT_PATH', dirname($vendorPath));

$loader = require $vendorPath . 'autoload.php';

\Wslim\Ioc::setLoader($loader);
\Wslim\Ioc::initNamespace();

// config use dev env
\Wslim\Common\Config::env('dev');

// if use app, please use below
$config = [
    'basePath' => $vendorPath . '../app',
];
$app = new \Wslim\Web\App($config);
