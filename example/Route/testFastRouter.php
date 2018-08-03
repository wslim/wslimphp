<?php
use Wslim\Ioc;
use Wslim\Route\FastRouter;
use Slim\Http\Environment;
use Slim\Http\Request;

include '../../../bootstrap.php';

$router = new FastRouter();
$router->get('/demo', function(){
	echo 'hello world.'. PHP_EOL;
})->setName('demo');

$router->map('get', '/{controller:\w+}[/{params}]', function(){});

Ioc::setShared('request', Request::createFromGlobals(Environment::mock(
		['REQUEST_URI' => '/demo2/user']
)));
$request = Ioc::get('request');
//print_r($request);
$routeInfo = $router->dispatch($request);
print_r($routeInfo);







