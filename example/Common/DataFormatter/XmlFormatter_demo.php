<?php
use Wslim\Common\DataFormatter\XmlFormatter;

include '../../../bootstrap.php';

$o = new XmlFormatter();

$record = '<log><name>aaa</name><message>adfasdfsdf</message><info><title>测试文本</title></info></log>';
$str = "<?xml version='1.0' encoding='utf-8'?>" . $record ;

// test decode
$data = $o->decode($str);
echo 'xml decode:' . PHP_EOL;
print_r($data);
echo PHP_EOL;

// test encode
$str2 = $o->encode($data);
echo 'xml encode:' . PHP_EOL;
print_r($str2);
echo PHP_EOL;
echo PHP_EOL;

// test decode again
$data2 = $o->decode($str2);
echo 'xml decode again:' . PHP_EOL;
print_r($data2);
echo PHP_EOL;

// test root
$root = 'xml';
echo 'xml root:' . PHP_EOL;
print_r($o->root($root));
echo PHP_EOL;

// test root
$root = '<logs></logs>';
echo 'xml root 2:' . PHP_EOL;
print_r($o->root($root));
echo PHP_EOL;

// test append
$root = '<logs></logs>';
$element = '<log>1</log>';
$new = $o->append($root, $element);
echo 'xml append:' . PHP_EOL;
print_r($new);
echo PHP_EOL;












