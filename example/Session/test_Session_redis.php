<?php
include '../../../bootstrap.php';

use Wslim\Session\Session;

$config = [
	'session_name'  => 'default',
	'handler'       => 'storage',    // native|cache 为 cache 时使用 cache 机制进行存储，需设置相应配置 cache_name
	'save_path'     => 'sessions', // 基于storage的相对目录
	'session_ttl'   => 1440,          // default 1440
	'cookie_domain' => '',    //Cookie 作用域
	'cookie_path'   => '/',         //Cookie 作用路径
	'cookie_prefix' => 'mp_',   //Cookie 前缀，同一域名下安装多套系统时，请修改Cookie前缀
	'cookie_ttl'    => 0,           //Cookie 生命周期，0 表示随浏览器进程
	
    // storage type need
    'storage'   => 'wslim_redis',   // file|redis|wslim_redis|memcache
    'data_format'=> 'serialize',
    'ttl'       => 1800,
    'host'      => '127.0.0.1',
    'port'      => '6379',
];

$session = new Session($config);
$session->start();

$session->set('name', 'session_cache_redis');
$session['password'] = '123456';

print_r($session->all());

// 查询下数据
$redis = new \Wslim\Redis\Redis();
$keys = $redis->keys('*');
print_r($keys);

print_r($redis->mget($keys));


