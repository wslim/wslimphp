<?php

use Wslim\Common\Event;
use Wslim\Common\EventListenerInterface;

include '../bootstrap.php';

class SomeClass
{
	public function handle()
	{
		Event::trigger($this, 'before_handle');
		
		echo __METHOD__ . PHP_EOL;
		
		Event::trigger($this, 'after_handle');
	}
}

class Handler
{
	public function beforeHandle()
	{
	    echo __METHOD__ . PHP_EOL;
	}
	public function afterHandle()
	{
	    echo __METHOD__ . PHP_EOL;
	}
}

class TestEventListener implements EventListenerInterface
{
	public function events()
	{
		return [
				'before_handle'	=> 'beforeHandle',
				'after_handle'	=> 'afterHandle',
		];
	}
	
	public function beforeHandle()
	{
		echo __METHOD__ . PHP_EOL;
	}
	
	/**
	 * 
	 * @param Event $event
	 */
	public function afterHandle($event)
	{
	    echo __METHOD__ . PHP_EOL;
		
// 		print_r(get_class_methods($event->sender));
	}
}

$class = '\SomeClass';
$name = 'before_handle';

// 1. 全局的 on()
Event::on($class, $name, function(){
	echo 'anonymous function before_handle.' . PHP_EOL;
});
Event::on($class, $name, [new Handler, 'beforeHandle']);
Event::on($class, $name, 'Handler:beforeHandle');

// 2. 全局的 setEventListener()
$listener = 'TestEventListener';
Event::setEventListener($class, $listener);
// Event::removeEventListener($class, $listener);

$o = new SomeClass();
$o->handle();
// print_r(Event::dump());


