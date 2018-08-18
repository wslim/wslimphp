<?php
include_once '../../bootstrap.php';

use Wslim\Common\Cache;

$options = array(
	'storage'	=> 'file', // null|runtime|staticRuntime|file|memcache|memcached|redis|xcache
	'path' 		=> './caches',
);
$options2 = array_merge($options, array('name' => 'session'));

$cache = new Cache();
//$cache2 = new Cache($options2);
//var_dump($cache);
//var_dump($cache2);

/*
$cache->set('flowerkey', array('flower' => 'sakura'));
$cache->set('flowerkey2', array('flower2' => 'sakura'));

$data = $cache->get('flowerkey');
$data2 = $cache->get('flowerkey2');
print_r($data);
print_r($data2);

$datas = $cache->mget(array('flowerkey', 'flowerkey3'));
print_r($datas);
*/

$cache->group('test')->set('a', 'aaa');
print_r($cache->get('a'));


