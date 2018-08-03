<?php

use Wslim\Util\DataHelper;
use Wslim\Util\IdMaker;

include '../bootstrap.php';

include 'Test.php';

$res = [];
function testId()
{
    $res = [];
    for ($i=0; $i<10; $i++) {
        $id = IdMaker::snowflake()->nextId();
        $res[] = $id . '|' . strlen(decbin($id)) . '|' . IdMaker::snowflake()->toTimestamp($id) . '|' . DataHelper::fromUnixtime(IdMaker::snowflake()->toTimestamp($id));
    }
    for ($i=0; $i<10; $i++) {
        $id = IdMaker::shortSnow()->nextId();
        $res[] = $id . '|' . strlen(decbin($id)) . '|' . IdMaker::shortSnow()->toTimestamp($id) . '|' . DataHelper::fromUnixtime(IdMaker::shortSnow()->toTimestamp($id));
    }
    print_r($res);
}
testId(); exit;

function testMultiProcessId()
{
    if (!function_exists("pcntl_fork")) {
        die("pcntl extention is need!");
    }
    
    $pid = pcntl_fork();
    if($pid == -1){
        //创建失败
        die('could not fork');
    } elseif($pid){
        //从这里开始写的代码是父进程的
        echo "parent:", getmypid(), "\t" , date( 'H:i:s', time()), "\n" ;
        //sleep(1);
        
        exit("parent exit!\n");
    } else {
        echo "child start, pid ", getmypid(), "\n" ;
        
        //子进程代码
        for ($k=0; $k<1; ++$k){
            echo "C", ": ";
            testId();
        }
        
        //为防止不停的启用子进程造成系统资源被耗尽的情况，一般子进程代码运行完成后，加入exit来确保子进程正常退出。
        exit("child exit\n");
    }
}
testMultiProcessId();



