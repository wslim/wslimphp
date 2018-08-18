<?php
use Wslim\Util\UriHelper;

include '../bootstrap.php';

function testBuildUrl()
{
    $path = UriHelper::getBaseUrl();
    
    $params = ['a'=>'a b c', 'b'=>'http://example.com/bb=/module/controller'];
    $o = UriHelper::buildQuery($params);
    var_dump($o . PHP_EOL);
    
    $o = http_build_query($params);
    print_r($o . PHP_EOL);
    
    $o = UriHelper::buildUrl($path, $params);
    print_r($o . PHP_EOL);
}
//testBuildUrl();exit;






















