<?php
use Wslim\Security\Token;

include_once '../bootstrap.php';

// 参数
function testToken1()
{
    $params = ['name' => 2];
    $token = Token::instance()->get($params);
    print_r('token:' . $token . PHP_EOL);
    
    $result = Token::instance()->verify($token, $params);
    print_r('token check:' . $result . PHP_EOL);
}

// 多个参数
function testToken3()
{
    $params1 = [
        'openid'   => 'asdfsadfsdfasdfsadf',
        'nickname' => '带中文的名称',
    ];
    $token = Token::instance()->get($params1);
    print_r('token:' . $token . PHP_EOL);
    
    $result = Token::instance()->verify($token, $params1);
    print_r('token check:' . $result . PHP_EOL);
}

function testClear()
{
    $storage = Token::instance('form_token');
    $keys = $storage->getTokens();
    
    $data = $storage->clearExpired();
    print_r($data);
    
}
testClear();














