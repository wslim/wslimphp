<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * Component
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Component extends Configurable
{
    // event aware
	use EventAwareTrait;
	
	/**
	 * format the name, use for format config key
	 * @param mixed $definition
	 * @throws InvalidConfigException
	 * @return string
	 */
	static public function formatName($definition)
	{
	    if (is_string($definition)) {
	        return $definition;
	    } elseif (is_object($definition)) {
	        return get_class($definition);
	    } elseif (is_array($definition)) {
	        if (!isset($definition['class'])) {
	            $name = $definition[0];
	        } else {
	            $name = $definition['class'];
	        }
	    } else {
	        throw new InvalidConfigException("Unsupported definition for :" . gettype($definition));
	    }
	}
	
	/**
	 * format the definition.
	 * @param  string $name class name
	 * @param  string|array|callable $definition the class definition
	 * @return array the formated class definition
	 * @throws InvalidConfigException if the definition is invalid.
	 */
	static public function formatDefinition($name, $definition)
	{
	    if (empty($definition)) {
	        return ['class' => $name];
	    } elseif (is_callable($definition, false) || is_object($definition)) {
	        return $definition;
	    } elseif (is_string($definition)) {
	        if (strpos($definition, ':') !== false) {
	            return $definition;
	        }
	        return ['class' => $definition];
	    } elseif (is_array($definition)) {
	        if (!isset($definition['class'])) {
	            if (strpos($name, '\\') !== false) {
	                $definition['class'] = $name;
	            } else {
	                throw new InvalidConfigException("A class definition requires a \"class\" member.");
	            }
	        }
	        return $definition;
	    } else {
	        throw new InvalidConfigException("Unsupported definition type for \"$name\": " . gettype($definition));
	    }
	}
	
	/**
	 * contained components
	 * @var array
	 */
	protected $_components;
	
	/**
	 * contained components definitions
	 * @var array
	 */
	protected $_definitions;
	
	/**
	 * default components, 性能没有直接调用 $this->set() 好.
	 *
	 * @return array
	 */
	protected function defaultComponents()
	{
	    return [];
	}
	
	/**
	 * get components
	 *
	 * @return array
	 */
	public function getComponents()
	{
	    return $this->_components;
	}
	
	/**
	 * set components
	 *
	 * @param array $components component definitions or instances <br>
	 *
	 * $components example:<br>
	 * ```
	 * [
	 *     'db' => [
	 *         'class' => 'namespace\Db',
	 *         'dsn' => 'sqlite:path/to/file.db',
	 *     ],
	 *     'cache' => [
	 *         'class' => 'namespace\Cache',
	 *     ],
	 * ]
	 * ```
	 */
	public function setComponents($components)
	{
	    if ($components) foreach ($components as $k => $v) {
	        $v = static::formatDefinition($k, $v);
	        if (is_numeric($k)) {
	            $k = static::formatName($v);
	        }
	        $this->set($k, $v);
	    }
	}
	
	/**
	 * get component by id, id is a brief name
	 *
	 * @param string $id
	 * @return object
	 */
	public function get($id)
	{
	    if (isset($this->_definitions[$id]) && is_object($this->_definitions[$id]) && !is_callable($this->_definitions[$id])) {
	        $this->_components[$id] = $this->_definitions[$id];
	    } elseif (!isset($this->_components[$id])) {
	        if (!isset($this->_definitions[$id])) {
	            throw new \RuntimeException('Must set components before use: ' . $id);
	        }
	        
	        $this->_components[$id] = Ioc::createObject($this->_definitions[$id]);
	    }
	    
	    return $this->_components[$id];
	}
	
	/**
	 * set component definition with id, delay create instance
	 * @param string $id
	 * @param mixed  $definition
	 * @return static
	 */
	public function set($id, $definition)
	{
	    if (is_numeric($id)) {
	        $id = is_string($definition) ? $definition : $definition['class'];
	    }
	    $this->_definitions[$id] = $definition;
	    return $this;
	}
	
	/**
	 * has compoment with id
	 * @param string $id
	 * @return boolean
	 */
	public function has($id)
	{
	    return isset($this->_definitions[$id]);
	}
	
}