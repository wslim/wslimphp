<?php

use Wslim\Common\Config;

include '../bootstrap.php';

function testLoad()
{
    $config = Config::load('routes');
    $config = Config::load('routes');
    print_r($config);
    
    
    $config = Config::load('config');
    $config = Config::load('config');
    print_r($config);
    
    
    $config = Config::load('config', null, 'app');
    $config = Config::load('config', null, 'app');
    print_r($config);
}
testLoad();exit;




















