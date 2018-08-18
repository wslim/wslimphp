<?php
use Wslim\Common\Container;

include '../bootstrap.php';

class Some
{
    private $name;
    
    public function __construct()
    {
        $this->name = time() . rand(1, 999);
    }
    
	public function __invoke2()
	{
		echo 'Some::__invoke.' . PHP_EOL;
	}
}
class SomeBuilder
{
	public function getObject()
	{
		return new Some;
	}
}

class SomeBuilder2
{
    private $builder;
    
    public function __construct(SomeBuilder $builder)
    {
        $this->builder = $builder;
    }
    
    public function getObject()
    {
        return $this->builder->getObject();
    }
}

$c = new Container();

// 测试 set
// $c->set('some1', 'Some');
// $c->set('some2', 'Some');
// $some1 = $c->get('some1', array('id' => 'some'));
// var_dump($some1);
// var_dump($c->get('some2'));

// 测试 setShared()
// $c->setShared('some2', 'Some');
// var_dump($c->get('some2'));
// var_dump($c->get('\Some'));

// 测试延迟 callable
// $c->set('some3', '\SomeBuilder:getObject');
// var_dump($c->get('some3'));

// 测试延迟 callable2
// $c->set('builder', '\SomeBuilder');
// $c->set('some4', 'builder:getObject');
// var_dump($c->get('some4'));

// 测试自动依赖
$c->set('builder2', '\SomeBuilder2');
$c->set('some5', 'builder2:getObject');
// var_dump($c->get('\SomeBuilder2'));
var_dump($c->get('some5'));


print_r($c);

