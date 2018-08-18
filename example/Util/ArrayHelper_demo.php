<?php
use Wslim\Util\ArrayHelper;
use Wslim\Common\Collection;

include '../bootstrap.php';

function testToArray()
{
    $data = 'abc';
    print_r(ArrayHelper::toArray($data));
    
    $data = new Collection(['a' => 1, 'b'=> ['b2' => 222]]);
    print_r(ArrayHelper::toArray($data));
    
    $data = new stdClass();
    $data->aaa = 'aaa';
    print_r(ArrayHelper::toArray($data));
    
}
testToArray();




