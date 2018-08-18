<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * event aware trait, so class has event design
 * 
 * methods: on, off, trigger, setEvents, setEventListeners
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait EventAwareTrait
{
    
	use ConfigurableTrait;
	
	/**
	 * @var array [$name, [$handler, $data]]
	 */
	private $_events = null;
	
	/**
	 * @var array [$listener1, ...]
	 */
	private $_eventListeners;
	
	/**
	 * eventListeners instances
	 * @var array ['ListenerClassName' => $listener]
	 */
	private $_listenerInstances;
	
	/**
	 * get all events
	 * @return array
	 */
	public function getEvents()
	{
		return $this->_events;
	}
	
	/**
	 * set events, so can set event->handler by config
	 * 
	 * @param array $events array($eventName => $handler)
	 * @return void
	 */
	public function setEvents(array $events)
	{
		if ($events) {
			$this->ensureEvents();
			
			foreach ($events as $name => $handler) {
				$this->on($name, $handler);
			}
		}
	}
	
	/**
	 * Makes sure that $this->events() all events bind $this
	 * @return void
	 */
	protected function ensureEvents()
	{
		if ($this->_events === null) {
			$this->_events = [];
			if (method_exists($this, 'events')) {
			    if ($events = $this->events()) foreach ($events as $name => $handler) {
					$this->on($name, $handler);
				}
			}
		}
	}
	
	/**
	 * bind only one event
	 * @param string   $name
	 * @param callable $handler
	 * @param array    $data
	 * @return void
	 */
	public function one($name, $handler, $data=array())
	{
	    $this->ensureEvents();
	    
	    $this->_events[$name] = [];
	    
	    $this->_events[$name][] = $data ? [$handler, $data] : [$handler];
	}
	
	/**
	 * bind event, if append=false then unshift the first
	 * @param string   $name
	 * @param callable $handler
	 * @param array    $data
	 * @param string   $append default true
	 * @return void
	 */
	public function on($name, $handler, $data=array(), $append=true)
	{
		$this->ensureEvents();
		
		if ($append || empty($this->_events[$name])) {
		    $this->_events[$name][] = $data ? [$handler, $data] : [$handler];
		} else {
		    array_unshift($this->_events[$name], $data ? [$handler, $data] : [$handler]);
		}
	}
	
	/**
	 * unbind event
	 * @param  string $name
	 * @param  callable $handler
	 * @return boolean if success return true, if event is not exist return false
	 */
	public function off($name, $handler = null)
	{
		$this->ensureEvents();
		
		if (empty($this->_events[$name])) {
			return false;
		}
		if ($handler === null) {
			unset($this->_events[$name]);
			return true;
		} else {
			$removed = false;
			foreach ($this->_events[$name] as $i => $event) {
				if ($event[0] === $handler) {
					unset($this->_events[$name][$i]);
					$removed = true;
				}
			}
			if ($removed) {
				$this->_events[$name] = array_values($this->_events[$name]);
			}
			
			return $removed;
		}
	}
	
	/**
	 * trigger named event 
	 * @param  string $name
	 * @param  Event|array $event
	 * @return void
	 */
	public function trigger($name, $event = null)
	{
		$this->ensureEvents();
		
		$this->ensureEventListeners();
		
		$this->bindEventListenersEvents();
		
		if (!empty($this->_events[$name])) {
		    // 增加事件处理的动态参数
		    $args = func_get_args();
		    array_shift($args);
		    
			if (!$event instanceof Event) {
				$event = new Event();
				array_unshift($args, $event);
			}
			if ($event->sender === null) {
				$event->sender = $this;
			}
			
			$event->name = $name;
			
			foreach ($this->_events[$name] as $handler) {
			    if (isset($handler[1]) && $handler[1]) {
			        $olddata = $handler[1];
			        $event->data = array_merge($olddata, $event->data);
			    }
			    
			    if (is_string($handler[0]) && strpos($handler[0], ':') === false) {
			        $callable = [$this, $handler[0]];
			    } else {
			        $callable = Ioc::resolveCallable($handler[0]);
			    }
			    
			    // call_user_func($callable, $event);
			    call_user_func_array($callable, $args);
			    
			    // stop further handling if the event is handled
			    if ($event->isPropagationStopped()) {
			        return;
			    }
			}
		}
		
		// invoke class-level attached handlers
		Event::trigger($this, $name, $event);
	}
	
	/**
	 * set an eventListener
	 * @param  EventListenerInterface $listener
	 * @return static
	 */
	public function setEventListener($listener)
	{
		$this->ensureEventListeners();
		
		$this->_eventListeners[Ioc::resolveClassName($listener)] =  $listener;
		
		return $this;
	}
	
	/**
	 * remove eventListener's events to this
	 * @param  EventListenerInterface|string $listener
	 * @return static
	 */
	public function removeEventListener($listener)
	{
		$this->ensureEventListeners();
		
		unset($this->_eventListeners[Ioc::resolveClassName($listener)]);
		
		return $this;
	}
	
	/**
	 * set property eventListeners
	 * @param  array $listeners
	 * @return static
	 */
	public function setEventListeners($listeners)
	{
		$this->ensureEventListeners();
		
		foreach ($listeners as $listener) {
			$this->_eventListeners[Ioc::resolveClassName($listener)] = $listener;
		}
		
		return $this;
	}
	
	/**
	 * ensure eventListeners() method have called
	 * @return void
	 */
	protected function ensureEventListeners()
	{
		if ($this->_eventListeners === null) {
			$this->_eventListeners = [];
			if (method_exists($this, 'eventListeners')) {
				$this->setEventListeners($this->eventListeners());
			}
		}
	}
	
	/**
	 * bind all eventListeners events on class, ensure do once
	 *
	 * @return void
	 */
	private function bindEventListenersEvents()
	{
		if ($this->_eventListeners && !$this->_listenerInstances) {
			foreach ($this->_eventListeners as $listener) {
                
				$listener = static::getListener($listener);
				
				$events = $listener->events();
				
				if ($events) foreach ($events as $name => $handler) {
					static::on($name, is_string($handler) ? [$listener, $handler] : $handler);
				}
			}
		}
	}
	/**
	 * get singleton instance of $listener
	 *
	 * @param mixed $listener
	 * @return EventListenerInterface
	 */
	private function getListener($listener)
	{
		$listenerClassName = Ioc::resolveClassName($listener);
		if (!isset($this->_listenerInstances[$listenerClassName])) {
			if (!($listener instanceof EventListenerInterface)) {
				$listener = Ioc::createObject($listener);
			}
			$this->_listenerInstances[$listenerClassName] = $listener;
		}
		
		return $this->_listenerInstances[$listenerClassName];
	}

}