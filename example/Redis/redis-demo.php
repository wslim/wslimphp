<?php
use Wslim\Redis\Redis;
use Wslim\Redis\RedisCluster;

include_once '../bootstrap.php';

$options = [
    'prefix' => 'wslim:'
];
$redis = new Redis($options);

//$redis->set('a', 'aaaaaa');
//$o = $redis->exists('a');
//$o = $redis->get('a');
//print_r($o);

$servers = [
    'server1' => $options
];
$rediscluster = new RedisCluster($servers);
//$o = $rediscluster->get('a');
//print_r($o);

$keys = $rediscluster->keys('*');
//print_r($keys);
if ($keys) {
    foreach ($keys as $key) {
        //print_r($key);
        //$key = str_replace('wslim:', '', $key);
        print_r(unserialize($rediscluster->get($key)));
    }
}

echo PHP_EOL;
$s = $redis->get('wslim:hbjkku0v17rs6dmsja8gcdrp57');
print_r($s);
echo PHP_EOL;
$o = unserialize($s);
print_r($o);


















