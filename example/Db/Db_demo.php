<?php
use Wslim\Ioc;

include '../bootstrap.php';

$db = Ioc::db();

// 1. sql string
function testStringQuery()
{
    global $db;
    
    //$sql = 'select * from ##demo';
    $sql = 'select id, name as id from @demo';
    $sql = 'select * from @demo';
    $sql = 'select * from ws_demo';
    $sql = 'insert into @demo (id, name) value (3, "cccc")';
    $sql = 'update @demo set name="xxx" where id=2';
    $result = $db->query($sql);
}


// 2. array query
function testArrayQuery()
{
    global $db;

    $options = [
        'table' => '.ws_demo',  // .后表名不会加前缀
        'result_key' => 'id'
    ];
    $result = $db->query($options);
    
    print_r($result);
}
//testArrayQuery();exit;

// 3. chain basic query
function testBasicChain()
{
    global $db;
    $data = [];
    
    /*
    $data[] = $db->table('demo')->count();
    $data[] = $db->table('demo')->where('id', 2)->exists();
    $data[] = $db->table('demo')->where('id=3')->find();
    $data[] = $db->table('demo')->findById(2);
    $data[] = $db->table('demo')->where('id', 3)->findField('title');
    $data[] = $db->table('demo')->fetchAll();
    */
    $data[] = $db->table('article')->find(3);
    
    print_r($data);
    
}
testBasicChain(); exit;

function testAdvancedChain()
{
    global $db;
    //$result = $db->table('demo')->add(['title' => 'xxxxxx']);
    //$result = $db->table('demo')->modify(['title' => '我是大灰狼'], 'id=1');
    //$result = $db->table('demo')->where('1=1')->modify(['title' => '我是大灰狼']);
    //$result = $db->table('demo')->save(['title' => '我是小狼'], ['id' => 8]);
    //$result = $db->table('demo')->save(['title' => '我是小狼'], ['id' => 1]);
    //$result = $db->table('demo')->remove(['id' => 1]);

    print_r($result);
}







