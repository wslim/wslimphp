<?php
use Wslim\Util\StringHelper;

include '../bootstrap.php';

function test()
{
    $str = 'A/testName/testAccount';
    echo StringHelper::toClassName($str);
}
test();exit;

function testToArray()
{
    $fields = 'id, title, content short_title';
    print_r(StringHelper::toArray($fields, ',\s'));
    
}
testToArray();





