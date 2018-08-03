<?php
include '../../../bootstrap.php';

use Wslim\Session\Session;

$config = array(
		'session_name'  => 'default',
		'handler'       => 'native',    // native|cache 为 cache 时使用 cache 机制进行存储，需设置相应配置 cache_name
		'cache_name'    => 'session',   // handler 为 cache 时，取caches.php 配置文件中的某个 cache_name
		'save_path'     => 'sessions', // 基于storage的相对目录
		'session_ttl'   => 1440,          // default 1440
		'cookie_domain' => '',    //Cookie 作用域
		'cookie_path'   => '/',         //Cookie 作用路径
		'cookie_prefix' => 'mp_',   //Cookie 前缀，同一域名下安装多套系统时，请修改Cookie前缀
		'cookie_ttl'    => 0,           //Cookie 生命周期，0 表示随浏览器进程
);
$session = new Session($config);
$session->start();

$session->set('name', 'abc');
$session['password'] = '123456';

print_r($session->all());

print_r(ini_get('session.gc_maxlifetime')); exit;



