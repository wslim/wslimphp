<?php
use Wslim\Util\HttpRequest;

include '../bootstrap.php';

function testHeader()
{
    $url = "http://203.205.158.80/vweixinp.tc.qq.com/1007_64f2e45c97b34608be3cb69044e455b1.f10.mp4?vkey=0DFC7EF37F04C04C2956AF606AE5B84E42AA86CA3E7644137CAF2FD4DDCA5506F60A28F3CC50FADD1CB37C7A50AC8F0D68765477569B2E2C47EA34B96CA7ECC67B30CCD542958024919C074FE11FD7D51F1EDFA6EC3800CF&sha=0&save=1";
    //$url = 'http://ws.cn/demo/test';
    //$url = "http://mmbiz.qpic.cn/mmbiz_jpg/eWEHvUEE0UgdGNZfg9sIcHpRRZgibeawGH1ias7tpD6mxNXhaXygxShZpVFQtbL2ZbAibbmsNN07U1m3RaRIk2s6w/0?wx_fmt=jpeg";
    $res = HttpRequest::instance('GET', $url, null, ['verbose'=>1])->getVerboseResponse();
    print_r(substr($res['body'], 0, 10));
}
testHeader();exit;

function testRequest()
{
    $url = 'http://ws.cn/';
    //$o = HttpRequest::instance('GET', $url)->toArray();
    $o = HttpRequest::instance($url, ['a'=>'aaa'])->type('file')->data(['a'=>'aaa'])->getResponse();
    //$o = HttpRequest::instance($url)->method('GET')->data(['a'=>'aaa'])->getResponse();
    print_r($o);
}
//testRequest();exit;

function testGet()
{
    $url = 'http://ws.cn/article/2';
    //$o = HttpRequest::get($url, ['a'=>'aaa']);
    $o = HttpRequest::instance($url)->type('Socket')->method('GET')->data(['a'=>'aaa'])->getResponse();
    print_r($o);
}
//testGet();exit;



function testPost()
{
    $url = 'http://ws.cn/demo/test';
    //$o = HttpRequest::post($url, 'a=aaa&b=bbb', ['cookies' => 'wx=sdfasdfsadfas']);
    $o = HttpRequest::instance('POST', $url, 'a=aaa&b=bbb', ['cookies' => 'wx=sdfasdfsadfas'])->toArray();
    print_r($o); 
}
// testPost();exit;

function testPost2()
{
    $url = 'http://ws.cn/';
    $o = HttpRequest::instance('POST', $url, 'a=aaa&b=bbb', ['cookies' => 'wx=sdfasdfsadfas']);
    print_r($o); 
}
//testPost2();exit;

function testOther()
{
    //$o = HttpRequest::instance('http://ws.cn/images/logo2.png')->getVerboseResponse();
    // = HttpRequest::instance('http://ws.cn/test.php')->getVerboseResponse();
    //$o = HttpRequest::instance('http://ws.cn/images/logo.png')->save('./');
    //$o = HttpRequest::getJson('http://ws.cn/images/logo.png', null, ['savePath' => './']);
    
    $o = HttpRequest::getJson('http://ws.cn/images/logo.png', null, ['verbose'=>1]);
    print_r($o);
    
}
testOther(); exit;

















