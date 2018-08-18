<?php
use Wslim\Ioc;

include 'bootstrap.php';

function testUrl()
{
    $url = "https://wslim.cn/upload/a.jpg";
    echo Ioc::rUrl('upload:' . $url);
}

function testObject()
{
    $obj = Ioc::get('test');
    print_r($obj);
}
testObject(); exit;

// 测试 logger
function testLogger()
{
    $logger = Ioc::logger();
    $logger2 = Ioc::logger();
    print_r(Ioc::container());
    print_r('has logger:' . Ioc::container()->has('Wslim\Common\Logger') . PHP_EOL);
    print_r($logger === $logger2);
    $logger->error('error');
}

// 测试 loader
/*
Ioc::container()->setShared('\Composer\Autoload\ClassLoader');
Ioc::container()->setShared('loader', '\Composer\Autoload\ClassLoader');
print_r(Ioc::container());
//$loader1 = Ioc::loader();
//$loader2 = Ioc::container()['loader'];
$loader1 = Ioc::container()->get('loader');
$loader2 = Ioc::container()->get('\Composer\Autoload\ClassLoader');
print_r(Ioc::container());
print_r($loader1 === $loader2);
*/

// 测试resoveCallable




























