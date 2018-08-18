<?php
require '../../bootstrap.php';

function testStorage()
{
    //$storage = new \Wslim\Common\Storage\FileStorage();
    $storage = new \Wslim\Common\Storage\WslimRedisStorage();
    $key = 'test/data';
    $value= '1234567890';
    
    var_dump($storage->exists($key));
    if (!$storage->exists($key)) {
        $storage->set($key, $value);
    }
    print_r($storage->get($key));
    echo PHP_EOL;
    
    $keys = [$key];
    print_r($storage->mget($keys));
    
    //$storage->clear();
    //var_dump($storage->get($key));
}




