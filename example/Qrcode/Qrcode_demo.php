<?php 
use Wslim\Qrcode\Qrcode;

include_once '../bootstrap.php';

$qrcode = new Qrcode();
$qrcode->setText('http://ws.cn');
$qrcode->render('demo');

?>