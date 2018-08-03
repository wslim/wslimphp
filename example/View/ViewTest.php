<?php
use Wslim\View\View;
use Wslim\Common\Config;

include '../../../bootstrap.php';

$options = array(
	'suffix'	=> 'html',
    'templatePath'    => Config::getWebAppPath() . 'demo/view',
    'compiledPath'    => Config::getStoragePath() . 'demo/view',
	'htmlPath'        => Config::get('htmlPath'),
	'isCompiled'      => true
);
$data = array('title' => 'my template', 'banner'=>'1.jpg');

$theme = 'default';
$layout = 'layout';
$template = 'index';

print_r($data);exit;
$v = new View($options);
$v->setTheme($theme);
$v->setLayout($layout);
// print_r($v);exit;

echo $v->render($template, $data);
// $v->makeHtml($template, $data);

