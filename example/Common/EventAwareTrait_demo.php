<?php

use Wslim\Common\Event;
use Wslim\Common\EventListenerInterface;

include '../../../test_boot.php';

class SomeClass 
{
	use \Wslim\Common\EventAwareTrait;	
	
	public function events()
	{
		return [
// 				'before_handle'	=> [$this, 'beforeHandle'],
// 				'after_handle' 	=> 'Handler:afterHandle'
		];
	}
	
	public function eventListeners()
	{
		return [
// 				'TestEventListener'
		];
	}
	
	/**
	 * @param Event $event
	 */
	public function beforeHandle($event)
	{
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
	}
	
	/**
	 * @param Event $event
	 */
	public function afterHandle($event)
	{
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
	}
	
	public function handle()
	{
		$this->trigger('before_handle', array('name' => 'begin data'));
		
		echo 'handle().' . PHP_EOL;
		
		$this->trigger('after_handle', array('name' => 'end data'));
	}
	
	protected $name = 'someClass';
}

class Handler
{
	public function beforeHandle()
	{
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
	}
	public function afterHandle()
	{
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
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
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
	}
	
	/**
	 *
	 * @param Event $event
	 */
	public function afterHandle($event)
	{
		echo get_called_class() . ':' . __FUNCTION__ . PHP_EOL;
		
		// 		print_r(get_class_methods($event->sender));
	}
}

$config = array(
		'events' => array(
				'before_handle' => 'Handler:beforeHandle'
		),
		'eventListeners' => array(
				'TestEventListener'
		),
);
// 方式1：类定义中在 event(), eventListener() 方法配置
// $o = new SomeClass();
// $o->removeEventListener('TestEventListener');
// $o->off('after_handle');

// 方式2：类实例化时注入配置
// 为清楚，可先注释掉类定义中的  event(), eventListener() 方法或内容
// $o = new SomeClass($config);
// $o->removeEventListener('TestEventListener');
// $o->off('after_handle');

// 方式3：类实例化后使用 events, eventListener 属性设置, 这个等同于上边
// $o = new SomeClass();
// $o->events = $config['events'];
// $o->eventListeners = $config['eventListeners'];

// 方式4：类实例化后使用 on()注册事件，使用 setEventListener() 注册监听者
// 此种方式可为同一个事件注册多个处理，而使用配置方式时由于数组的key唯一性只能注册一个
// $o = new SomeClass();
// $o->on('after_handle', [$o, 'afterHandle']);
// $o->on('after_handle', 'Handler:afterHandle');
// $o->setEventListener('TestEventListener');

// 方式5：使用 Event::on() 注册事件, 使用 EventListener::set() 注册监听者
Event::on('SomeClass', 'before_handle', 'Handler:beforeHandle');
Event::off('SomeClass', 'before_handle', 'Handler:beforeHandle');
Event::setEventListener('SomeClass', 'TestEventListener');
Event::removeEventListener('SomeClass', 'TestEventListener');
$o = new SomeClass();

$o->handle();

