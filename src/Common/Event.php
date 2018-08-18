<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * Event is the base class for all event classes.
 * Implement two purpose:
 * 1. Event base class. 
 * 2. Implement static methods, on(), off(), trigger() .
 */
class Event 
{
    /**
     * @var string the event name.
     * Event handlers may use this property to check what event it is handling.
     */
    public $name;
    /**
     * @var object the sender of this event. If not set, this property will be
     * set as the object whose "trigger()" method is called.
     * This property may also be a `null` when this event is a
     * class-level event which is triggered in a static context.
     */
    public $sender;

    /**
     * @var mixed the data that is passed to [[Component::on()]] when attaching an event handler.
     * Note that this varies according to which event handler is currently executing.
     */
    public $data;
    
    /**
     * @var bool Whether no further event listeners should be triggered
     */
    private $propagationStopped = false;
    
    /**
     * Returns whether further event listeners should be triggered.
     *
     * @see Event::stopPropagation()
     *
     * @return bool Whether propagation was already stopped for this event
     */
    public function isPropagationStopped()
    {
    	return $this->propagationStopped;
    }
    
    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation()
    {
    	$this->propagationStopped = true;
    }
    
    /**
     * construct
     * @param array $data
     */
    public function __construct($data=null)
    {
        if ($data) {
            $this->data = (array) $data;
        }
    }

    /*******************************************************
     * static properties and methods 
     *******************************************************/
    /**
     * events
     * @var array
     */
    static private $_events = [];
    
    /**
     * eventListeners
     * @var array
     */
    static private $_eventListeners;
    
    /**
	 * eventListeners instances
	 * @var array ['ListenerClassName' => $listener]
	 */
    static private $_elInstances;
    
    /**
     * bind a class-level event.
     *
     * When a class-level event is triggered, event handlers attached
     * to that class and all parent classes will be invoked.
     *
     * ~~~
     * Event::on('Ns\Test', 'before_insert', function ($event) {
     *     log(get_class($event->sender) . ' is inserted.');
     * });
     * ~~~
     * 
     * @param mixed    $class object or fully qualified class name
     * @param string   $name the event name.
     * @param callable $handler the event handler.
     * @param mixed    $data can access $event->data() when the event is triggered.
     * @param boolean  $append append or prepend default append
     */
    public static function on($class, $name, $handler, $data = null, $append = true)
    {
    	$class = Ioc::resolveClassName($class);
    	
        if ($append || empty(self::$_events[$name][$class])) {
            self::$_events[$name][$class][] = [$handler, $data];
        } else {
            array_unshift(self::$_events[$name][$class], [$handler, $data]);
        }
    }

    /**
     * Detaches an event handler from a class-level event.
     *
     * This method is the opposite of [[on()]].
     *
     * @param mixed $class object or fully class name
     * @param string $name the event name.
     * @param callable $handler the event handler to be removed.
     * If it is null, all handlers attached to the named event will be removed.
     * @return boolean whether a handler is found and detached.
     * @see on()
     */
    public static function off($class, $name, $handler = null)
    {
        $class = Ioc::resolveClassName($class);
    	
        if (empty(self::$_events[$name][$class])) {
            return false;
        }
        if ($handler === null) {
            unset(self::$_events[$name][$class]);
            return true;
        } else {
            $removed = false;
            foreach (self::$_events[$name][$class] as $i => $event) {
                if ($event[0] === $handler) {
                    unset(self::$_events[$name][$class][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                self::$_events[$name][$class] = array_values(self::$_events[$name][$class]);
            }

            return $removed;
        }
    }

    /**
     * object or className has the named event
     * 
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @return boolean whether there is any event handler attached to the event.
     */
    public static function has($class, $name)
    {
        if (empty(self::$_events[$name])) {
            return false;
        }
        $class = Ioc::resolveClassName($class);
        do {
            if (!empty(self::$_events[$name][$class])) {
                return true;
            }
        } while (($class = get_parent_class($class)) !== false);

        return false;
    }

    /**
     * Triggers a class-level event.
     * This method will cause invocation of event handlers that are attached to the named event
     * for the specified class and all its parent classes.
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     */
    public static function trigger($class, $name, $event = null)
    {
    	self::bindEventListenersEvents();
    	
        if (empty(self::$_events[$name])) {
            return;
        }
        
        if (!$event instanceof Event) {
        	$oldevent = $event;
        	$event = new static();
        	if (is_array($oldevent)) foreach ($oldevent as $key => $value) {
        		$event->data[$key] = $value;
        	}
        }
        
        $event->name = $name;

        if (is_object($class)) {
            if ($event->sender === null) {
                $event->sender = $class;
            }
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
        do {
            if (!empty(self::$_events[$name][$class])) {
                foreach (self::$_events[$name][$class] as $handler) {
                	$olddata = $handler[1];
                	if ($olddata) $event->data = array_merge($olddata, $event->data);
                	
                	$callable = Ioc::resolveCallable($handler[0]);
                	call_user_func($callable, $event);
                	
                    if ($event->isPropagationStopped()) {
                        return;
                    }
                }
            }
        } while (($class = get_parent_class($class)) !== false);
    }
    
    /**
     * set eventListener on some class
     *
     * @param mixed $class object or classname
     * @param EventListenerInterface|string $listener
     */
    static public function setEventListener($class, $listener)
    {
    	self::$_eventListeners[Ioc::resolveClassName($class)][Ioc::resolveClassName($listener)] = $listener;
    }
    
    /**
     * remove eventListener on some class
     *
     * @param mixed $class object or classname
     * @param EventListenerInterface|string $listener
     */
    static public function removeEventListener($class, $listener)
    {
        unset(self::$_eventListeners[Ioc::resolveClassName($class)][Ioc::resolveClassName($listener)]);
    }
    /**
     * bind all eventListeners events on class, ensure do once
     * 
     * @return void
     */
    static private function bindEventListenersEvents()
    {
    	if (self::$_eventListeners && !self::$_elInstances) {
    		foreach (self::$_eventListeners as $class => $listeners) {
    			if ($listeners) foreach ($listeners as $listener) {
    				
    				$listener = self::getListener($listener);
    				
    				$events = $listener->events();
    				
    				if ($events) foreach ($events as $name => $handler) {
    					Event::on($class, $name, is_string($handler) ? [$listener, $handler] : $handler);
    				}
    			}
    		}
    	}
    }
    
    /**
     * get singleton instance of $listener
     * 
     * @param  mixed $listener
     * @return EventListenerInterface
     */
    static private function getListener($listener)
    {
        $listenerClassName = Ioc::resolveClassName($listener);
    	if (!isset(self::$_elInstances[$listenerClassName])) {
    		if (!($listener instanceof EventListenerInterface)) {
    			$listener = Ioc::createObject($listener);
    		}
    		self::$_elInstances[$listenerClassName] = $listener;
    	}
    	
    	return self::$_elInstances[$listenerClassName];
    }
    
    static public function dump()
    {
    	print_r(self::$_events);
    	print_r(self::$_eventListeners);
//     	print_r(self::$_elInstances);
    }
}
