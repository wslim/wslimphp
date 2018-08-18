<?php

use Wslim\Common\ErrorInfo;
use Wslim\Common\Collection;

include '../bootstrap.php';

function testInfo()
{
    $result = ErrorInfo::instance(-1);
    print_r($result);
    
    $result = ErrorInfo::instance('OK');
    print_r($result);
    
    $result = ErrorInfo::instance(['id' => 2]);
    print_r($result);
    
    $result = ErrorInfo::instance(-2, ['id' => 5]);
    print_r($result);
    
    $result = ErrorInfo::instance('OK', ['id' => 3]);
    print_r($result);
    
    $result = ErrorInfo::instance(0, 'OK', ['id' => 7]);
    print_r($result);
    
    $result = ErrorInfo::instance(-2, 'OK', new Collection(['user' => 'xxx']));
    print_r($result);
    
    $result = ErrorInfo::instance(-2, new Collection(['user' => 'xxx']));
    print_r($result);
}
testInfo(); print_r('================================' . PHP_EOL); 


function testSuccess()
{
    $result = ErrorInfo::success();
    print_r($result);
    
    $result = ErrorInfo::success('OK');
    print_r($result);
    
    $result = ErrorInfo::success(['id' => 2]);
    print_r($result);
    
    $result = ErrorInfo::success('OK', ['id' => 3]);
    print_r($result);
    
    $result = ErrorInfo::success(new Collection(['user' => 'xxx']));
    print_r($result);
    
    
    $result = ErrorInfo::success('OK', new Collection(['user' => 'xxx']));
    print_r($result);
}
testSuccess(); print_r('================================' . PHP_EOL); //exit;

function testFail()
{
    $result = ErrorInfo::error(-1);
    print_r($result);
    
    $result = ErrorInfo::error('OK');
    print_r($result);
    
    $result = ErrorInfo::error(['id' => 2]);
    print_r($result);
    
    $result = ErrorInfo::error(-2, ['id' => 5]);
    print_r($result);
    
    $result = ErrorInfo::error('OK', ['id' => 3]);
    print_r($result);
    
    $result = ErrorInfo::error(1001, 'OK', ['id' => 7]);
    print_r($result);
    
    $result = ErrorInfo::error(0, 'OK', new Collection(['user' => 'xxx']));
    print_r($result);
}
testFail(); exit;





















