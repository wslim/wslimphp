<?php
include '../bootstrap.php';

use Wslim\Db\FieldInputHandler;
use Wslim\Util\DataHelper;
use Wslim\Db\Model;
use Wslim\Util\XssHelper;

$v = '<img onerror="" sdfasf">onerror';
//$v = "a=concat('a', id, 'b')";
//$v = "http://a.com/b.php?url=http://c.com/d.php<script>alert(1)</script>";
//$v = json_encode(['a' => 'b']);

print_r(XssHelper::filter($v));
exit;

foreach ($vals as $val) 
{
    $data = ['a' => 'aaaa"\"\'bbb', 'b' => 'bbb'];
    $vals = [
        $data,
        'aaaa"\"\'bbb',
    ];
    
    $v = FieldInputHandler::editor($val);
    $sql = 'select * from ws.demo where title = \'' . DataHelper::addslashes($v) . '\'';
    
    echo $v . '|' . strpos($v, '\"') . PHP_EOL;
    echo $sql . PHP_EOL . PHP_EOL;
    
    $sql = Model::instance('demo')
    ->select()
    ->where('title', $v)
    ->parse();
    
    echo $v . '|' . strpos($v, '\"') . PHP_EOL;
    echo $sql . PHP_EOL . PHP_EOL;
}


