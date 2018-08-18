<?php
namespace Wslim\Common;

/**
 * EventLinsener Interface, events method use to set event and handler
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface EventListenerInterface
{
	/**
	 * @return array   [$eventName => $callable]  
	 * 
	 * @desc
	 * $callable: function($event) { $event->sender->... }
	 */
	public function events();
	
}