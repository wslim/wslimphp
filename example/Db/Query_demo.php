<?php
use Wslim\Db\Query;
use Wslim\Db\Parser\MysqlQueryParser;

include '../../../bootstrap.php';

$q = new Query();


//$q->select('id')->from('demo')->where('a=aaa')->pagesize(20)->page(3)->count('id');
//$q->select()->from('demo')->where('a=aaa')->pagesize(20)->page(3)->count();

//$q->insert('demo')->values(['id'=>2, 'title'=>'aaaaa']);
//$q->insert('demo')->values(['id'=>2, 'title'=>'aaaaa'])->values(['id'=>2, 'title'=>'aaaaa']);

// query有set(), 这里使用 data()替换
//$q->update('table')->data(['id'=>2, 'title'=>'aaaaa'])->where('id=2');

//$q->delete('demo')->where('a=aaa');
//$q->delete()->from('demo')->where(['id'=>2]);

print_r($q->all());

$parse = new MysqlQueryParser();
$sql = $parse->parse($q);
print_r($sql);



