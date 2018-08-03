<?php
use Wslim\Mail\Mail;

include_once '../bootstrap.php';

$config = [
    'host' => 'smtp.aliyun.com',    //'smtp.aliyun.com',
    'user' => 'sweeper@aliyun.com',
    'pass' => 'passwrod',
];
$tos = [
    'sweeper@aliyun.com'
];

$pop3_config = [
    'host' => 'pop3.aliyun.com',    //'smtp.aliyun.com',
    'user' => 'sweeper@aliyun.com',
    'pass' => 'passwrod',
];

function testSmtp()
{
    global $config, $tos;
    $mail  = Mail::smtp($config['host'], $config['user'], $config['pass']);
    $result = $mail->setSubject('Hello you!')->setBody('<p>Hello you!</p>', true)
        ->addTo($tos[0])
        ->addAttachment('demo.png', __DIR__ . '/demo.png')
        ->send();
    print_r($result);
}
//testSmtp();

function testPop3()
{
    global $pop3_config;
    $mail = Mail::pop3($pop3_config['host'], $pop3_config['user'], $pop3_config['pass']);
    $result = $mail->getEmails();
    $total = $mail->getEmailTotal();
    
    print_r($total);
    print_r($result);
}
testPop3();


