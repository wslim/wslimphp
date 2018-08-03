<?php
use Wslim\Util\DataHelper;

include '../bootstrap.php';

function testExplode()
{
    $arr = [
        ['*'],
        ['a as a| a as a2, apple', ['aaa', 'bbb']],
        '3' => ['user as userid']
    ];
    
    print_r(DataHelper::explode('|,', $arr));
}
//testExplode(); exit;

//echo DataHelper::guid() . PHP_EOL . PHP_INT_SIZE;
//echo DataHelper::guid() . PHP_EOL;


function testSerial()
{
    $arr = [];
    for($i=0; $i<10000; $i++) {
        //$arr[] = DataHelper::uuid(false);
        $arr[] = DataHelper::serial(false);
    }
    print_r(array_unique($arr));
}
//testSerial();

function testVerify()
{
    $data = [
        '18288889999',
        '+18288889999',
        '8618288889999',
        '+8618288889999',
        '(+86)18288889999',
    ];
    
    $res = [];
    foreach ($data as $v) {
        $res[$v] = DataHelper::verify_mobile($v);
    }
    print_r($res);
}
//testVerify();

function testDate()
{
    $date = time();
    $res = DataHelper::datetime($date, '%Y year');
    
    print_r($res);
}
testDate();


