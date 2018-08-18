<?php
namespace Wslim\Common;

use BadMethodCallException;
use LogicException;
use Wslim\Util\StringHelper;

/**
 * trait can auto set properties and options by config array 
 * The trait equal class Congigurable.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
Trait ConfigurableTrait 
{
    /**
     * options
     * @var array
     */
    protected $options = [];
    
    /**
     * allowed options keys
     * @return array
     */
    public function allowedOptionsKeys()
    {
        return [];
    }
    
	/**
	 * construct
	 * @param array $config config = options + properties
	 */
	public function __construct($config=null)
	{
		// load default options
	    if ($this->defaultOptions()) {
	        $this->setOption($this->defaultOptions(), false);
	    }
		
	    if ($config) {
	        $this->configure($config);
	    }
	}
	
	/**
	 * default options. 
	 * if overwrite you need merge parent.
	 * 
	 * @return array
	 */
	protected function defaultOptions()
	{
	    return [];
	}
	
	/**
	 * set propertys by config
	 *
	 * @param array   $config
	 * 
	 * @return static
	 */
	public function configure(array $config=null)
	{
		if ($config) foreach($config as $key => $value) {
		    $this->$key = $value;
		}
		return $this;
	}
	
	/**
	 * get options or named option
	 *
	 * @param  string $key
	 * @param  mixed  $default if named key not exist return $default value
	 * @return mixed
	 */
	public function getOption($key=null, $default=null)
	{
	    if (!$key) {
	        return $this->options;
	    } elseif (isset($this->options[$key])) {
	        return $this->options[$key];
		} elseif (strpos($key, '.') !== false) {
			$value = $this->getByPath($key);
			return isset($value) ? $value : (isset($default) ? $default : null);
		}
	}
	
	/**
	 * get by path, $path 'foo.bar.yoo' equals to $array['foo']['bar']['yoo']
	 * @param  string $path
	 * @param  string $separator Separator of paths.
	 * @return mixed
	 */
	private function getByPath($path, $separator = '.')
	{
	    $nodes = array_values(array_filter(explode($separator, $path), 'strlen'));
	    
	    if (empty($nodes)) {
	        return null;
	    }
	    
	    $dataTmp = $this->options;
	    
	    foreach ($nodes as $arg) {
	        if (is_object($dataTmp) && isset($dataTmp->$arg)) {
	            $dataTmp = $dataTmp->$arg;
	        } elseif ($dataTmp instanceof \ArrayAccess && isset($dataTmp[$arg])) {
	            $dataTmp = $dataTmp[$arg];
	        } elseif (is_array($dataTmp) && isset($dataTmp[$arg])) {
	            $dataTmp = $dataTmp[$arg];
	        } else {
	            return null;
	        }
	    }
	    
	    return $dataTmp;
	}
	
	/**
	 * set option, for key from ['a.b'] to ['a']['b']
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * 
	 * @return static
	 */
	public function setOption($key, $value=null, $overwriting=TRUE)
	{
	    if (is_array($key)) {
	        foreach ($key as $k => $v) {
	            $this->setOption($k, $v, $overwriting);
	        }
	    } elseif (strpos($key, '.') !== false) {
			$ks = explode('.', $key, 2);
			if ($overwriting || !isset($this->options[$ks[0]][$ks[1]]))  {
			    $this->options[$ks[0]][$ks[1]] = $value;
			}
		} elseif (is_array($value)) {
		    if (!isset($this->options[$key])) {
		        $this->options[$key] = $value;
		    } else {
		        if (!is_array($this->options[$key])) {
		            $this->options[$key] = (array) $this->options[$key];
		        }
		        foreach ($value as $k2=>$v2) { 
		            if ($overwriting || !isset($this->options[$key][$k2]))  {
    		            $this->options[$key][$k2] = $v2;
        		    }
    		    }
		    }
		    
		} else {
		    if ($overwriting || !isset($this->options[$key]))  $this->options[$key] = $value;
		}
		
		return $this;
	}
	
	/**
	 * option is exists
	 * @param  string $key
	 * @return boolean
	 */
	public function hasOption($key)
	{
	    if (strpos($key, '.')) {
	        $ks = explode('.', $key);
	        $count = count($ks);
	        if ($count >= 3) {
	            return isset($this->options[$ks[0]][$ks[1]][$ks[2]]);
	        } else {
	            return isset($this->options[$ks[0]][$ks[1]]);
	        }
	    } else {
	       return isset($this->options[$key]);
	    }
	}
	
	/**
	 * unset option
	 * @param  string $key
	 * @return static
	 */
	public function removeOption($key)
	{
	    if (strpos($key, '.')) {
	        $ks = explode('.', $key);
	        $count = count($ks);
	        if ($count >= 3) {
	            unset($this->options[$ks[0]][$ks[1]][$ks[2]]);
	        } else {
	            unset($this->options[$ks[0]][$ks[1]]);
	        }
	    } else {
	        unset($this->options[$key]);
	    }
	    return $this;
	}
	
	/********************************************************************************
	 * ArrayAccess interface, you can invoke like $obj[$key]
	 *******************************************************************************/
	/**
	 * Does this collection have a given key?
	 *
	 * @param  string $key The data key
	 *
	 * @return bool
	 */
	public function offsetExists($key)
	{
	    return $this->hasOption($key);
	}
	
	/**
	 * Get collection item for key
	 *
	 * @param string $key The data key
	 *
	 * @return mixed The key's value, or the default value
	 */
	public function offsetGet($key)
	{
	    return $this->getOption($key);
	}
	
	/**
	 * Set collection item
	 *
	 * @param string $key   The data key
	 * @param mixed  $value The data value
	 */
	public function offsetSet($key, $value)
	{
	    $this->setOption($key, $value);
	}
	
	/**
	 * Remove item from collection
	 *
	 * @param string $key The data key
	 */
	public function offsetUnset($key)
	{
	    $this->removeOption($key);
	}
	
	/***************************************************
	 * Magic methods: $this->someKey or $this->someKey = ...
	 ***************************************************/
	/**
	 * get named property
	 *
	 * @param  string $name
	 * @throws LogicException
	 * @return mixed
	 */
	public function __get($name)
	{
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			return $this->$getter();
		} elseif (($value = $this->getOption($name)) !== null) {
		    return $value;
		} else {
		    return $this->getOption($name);
		    
		    throw new LogicException('Undefined Property: ' . get_class($this) . '::' . $name);
		}
	}
	
	/**
	 * set property, first detect setXxx(), then setOption() if name in options keys
	 * 
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws LogicException
	 * @return static
	 */
	public function __set($name, $value)
	{
	    $setter = 'set' . StringHelper::toClassName($name);
	    
		if (method_exists($this, $setter)) {
			return $this->$setter($value);
		} else {
		    return $this->setOption($name, $value);
		    
		    throw new LogicException('Undefined Property: ' . get_class($this) . '::' . $name);
		}
	}
	
	/**
	 * isset property
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			return $this->$getter() !== null;
		} else {
		    $this->hasOption($name);
		}
	}
	/**
	 * unset property
	 *
	 * @param  string $name
	 * @throws BadMethodCallException
	 * @return static
	 */
	public function __unset($name)
	{
		$setter = 'set' . $name;
		if (method_exists($this, $setter)) {
			$this->$setter(null);
		} elseif (method_exists($this, 'get' . $name)) {
			throw new BadMethodCallException('Unset read-only property: ' . get_class($this) . '::' . $name);
		} else {
		    return $this->removeOption($name);
		}
	}
	
}
