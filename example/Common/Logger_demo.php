<?php
use Wslim\Ioc;
use Wslim\Common\Logger;

include '../bootstrap.php';

function testInstance()
{
    $logger = new Logger();

    print_r($logger);
    $message = 'test error';
    $logger->error($message);

}
// testInstance(); exit;

function testWithFile()
{
    $logger = new Logger();
    
    $logger2 = $logger->withFile('user');
    //$logger2 = $logger->withFile('user', true);
    print_r($logger2);
    $logger2->error('logger2 log');
}
// testWithFile();exit;

function testDebug()
{
    $logger = new Logger();
    
    $logger = $logger->withFile('company');
    print_r($logger);
    $logger->debug('logger2');
}
// testDebug();exit;

function testIoc()
{
    $logger = Ioc::logger('db');
    print_r($logger);
    $logger->debug('db sql');
    $logger->setGroup('sql')->error('ok');
}
testIoc();














