<?php
namespace Wslim\Common;

use ReflectionClass;
use RuntimeException;

/**
 * Container implements a [dependency injection] container.
 * See (http://en.wikipedia.org/wiki/Dependency_injection) .  
 * 
 * @property array $definitions The list of the object definitions or the loaded shared objects 
 * 
 */
class Container implements ContainerInterface
{
    /**
     * @var array singleton objects indexed by key or type
     */
    private $_singletons = [];
    /**
     * @var array object definitions indexed by key or type
     */
    private $_definitions = [];

    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $_reflections = [];
    
    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $_dependencies = [];
    
    /**
     * Holds the key aliases.
     *
     * @var    array  $aliases
     */
    private $_aliases = [];

    /**
     * {@inheritDoc}
     * @see \Wslim\Common\ContainerInterface::has()
     */
    public function has($class)
    {
        $class = $this->resolveAlias($class);
        
        return isset($this->_definitions[$class]);
    }
    
    /**
     * Search the aliases property for a matching alias key.
     *
     * @param   string  $key  The key to search for.
     *
     * @return  string
     */
    protected function resolveAlias($key)
    {
        if (isset($this->_aliases[$key])) {
            return $this->_aliases[$key];
        }
        return $key;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\ContainerInterface::alias()
     */
    public function alias($alias, $key)
    {
        if ($key !== $alias && $this->has($key)) {
            $this->_aliases[$alias] = $key;
        }
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\ContainerInterface::resolveCallable()
     */
    public function resolveCallable($toResolve, $throw=true)
    {
        $resolved = $toResolve;
        
        if (!is_callable($toResolve, false) && is_string($toResolve)) {
            $class = $toResolve;
            $method = '__invoke';
            
            // check for callable as "class:method"
            $callablePattern = '!^([^\:]+)\:+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }
            
            if ($this->has($class)) {
                $resolved = [$this->get($class), $method];
            } else {
                if (!class_exists($class) && $throw) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                } else {
                    $resolved = [new $class($this), $method];
                }
            }
        }
        
        if (!is_callable($resolved) && $throw) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable to callable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
                ));
        }
        
        if ($resolved instanceof \Closure) {
            $resolved = $resolved->bindTo($this);
        }
        
        return $resolved;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\ContainerInterface::get()
     */
    public function get($key, $config = [])
    {
    	$key= $this->resolveAlias($key);
    	
        if (isset($this->_singletons[$key])) {  // singleton
            return $this->_singletons[$key];
        } elseif (!isset($this->_definitions[$key])) {  // not set, try build, key tread as className 
            $tryDef = static::formatDefinition($key, $config);
            if (!isset($tryDef['class']) || !class_exists($tryDef['class'])) {
                return null;
            }
            $key = $tryDef['class'];
            return $this->build($key, $config);
        }
        
        $definition = $this->_definitions[$key];
        
        if (is_callable($definition, true)) {// not strict callable, true 参数会使用 'className:method' 也是 callable
            if (!is_callable($definition, false) && is_string($definition)) {
                $definition = $this->resolveCallable($definition); 
            }
            // callable: function($container, $config)
            $object = call_user_func($definition, $this, $config); 
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);
			
            if ($config) {
                $definition = array_merge($definition, $config);
            }
            
            $object = $this->build($concrete, $definition);
        } elseif (is_object($definition)) {
            return $this->_singletons[$key] = $definition;
        } else {
            throw new InvalidConfigException("Unexpected object definition type: " . gettype($definition));
        }
		
        // 判断是否存在 _singletons 的 key，不能使用 isset()
        if (array_key_exists($key, $this->_singletons)) {
            // singleton
            $this->_singletons[$key] = $object;
            // set alias
            $this->alias('\\' . get_class($object), $key);
        }

        return $object;
    }

    /**
     * set a key-callback with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('namespace\SomeClass');
     *
     * // register an interface
     * $container->set('mail\MailInterface', 'swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * $container->set('foo', 'namespace\SomeClass');
     *
     * // register a class with configuration. 
     * $container->set('Wslim\Db\Db', [
     *     'dsn' => 'mysql:host=127.0.0.1;database=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a class with alias name with configuration
     * $container->set('db', [
     *     'class' => 'Wslim\Db\Db',
     *     'dsn' => 'mysql:host=127.0.0.1;database=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a callable
     * $container->set('db', function ($container, $config) {
     *     return new Wslim\Db\Db($config);
     * });
     * ```
     *
     * @param string $key   key or class name or interface name
     * @param mixed  $definition the definition associated with `$class`. 
     * 
     * @return static the container itself
     */
    public function set($key, $definition = [])
    {
        $this->_definitions[$key] = $this->formatDefinition($key, $definition);
        unset($this->_singletons[$key]);
        return $this;
    }

    /**
     * set singleton callback or object
     *
     * @param string $key key or class name or interface name 
     * @param mixed  $definition 
     * 
     * @return static
     * @see set()
     */
    public function setShared($key, $definition = [])
    {
        $this->_definitions[$key] = $this->formatDefinition($key, $definition);
        $this->_singletons[$key] = null;
        return $this;
    }

    /**
     * Removes by key
     * @param string $key key or class name or interface name
     */
    public function remove($key)
    {
        unset($this->_definitions[$key], $this->_singletons[$key]);
        foreach ($this->_aliases as $alias => $akey) {
        	if ($akey === $key) {
        		unset($this->_aliases[$alias]); 
        		break;
        	}
        }
    }
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\ContainerInterface::keys()
     */
    public function keys()
    {
    	return array_merge(array_keys($this->_aliases), array_keys($this->_definitions));
    }
    
    public function singletonKeys()
    {
        return array_keys($this->_singletons);
    }

    /**
     * format the class definition.
     * 
     * @param  string $class class name
     * @param  string|array|callable $definition the class definition
     * @return array the formated class definition
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function formatDefinition($class, $definition)
    {
        if (empty($definition)) {
            return ['class' => $class];
        } elseif (is_callable($definition, false) || is_object($definition)) {
            return $definition;
        } elseif (is_string($definition)) {
        	if (strpos($definition, ':') !== false) {
        		return $definition;
        	}
        	
            return ['class' => $definition];
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } elseif (isset($definition[0]) && is_string($definition[0]) && strpos($definition[0], '\\') !== false) {
                    $definition['class'] = $definition[0];
                    unset($definition[0]);
                } else {
                    throw new InvalidConfigException("A class definition requires a \"class\" member.");
                }
            }
            return $definition;
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }

    /**
     * Creates an instance of the specified class.
     * This method will resolve dependencies of the specified class, instantiate them, and inject
     * them into the new instance of the specified class.
     * @param  string $class the class name
     * @param  array  $config constructor parameters
     * @return object the newly created instance of the specified class
     */
    protected function build($class, $config)
    {	
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);
        
        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }
        
        if (!empty($dependencies) && $reflection->implementsInterface('Wslim\Common\ConfigurableInterface')) {
            
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $method = 'set' . ucfirst($name);
                if (method_exists($object, $method)) {
                    $object->$method($value);
                } else {
                    $object->$name = $value;
                }
            }
            
            return $object;
        }
    }

    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }
        
        $dependencies = [];
        $reflection = new ReflectionClass($class);
        
        $constructor = $reflection->getConstructor(); 
        if ($constructor !== null) { 
        	foreach ($constructor->getParameters() as $param) {
        	            	    
        		if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = ($c === null ? null : Instance::of($c->getName()));
                }
            }
        }
        
        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;
        
        return [$reflection, $dependencies];
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * @param array $dependencies the dependencies
     * @param ReflectionClass $reflection the class reflection associated with the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }
        return $dependencies;
    }
    
    /**********************************************************************************************
     * Methods to satisfy ArrayAccess, you can invoke like $object[$key]
     **********************************************************************************************/
    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }
    
    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }
    
    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
