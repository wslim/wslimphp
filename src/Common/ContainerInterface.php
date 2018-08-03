<?php
namespace Wslim\Common;

use RuntimeException;

/**
 * Container Interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface ContainerInterface extends \ArrayAccess
{
	/**
	 * get an instance of the named key, if not found return null.
	 * 
	 * @param  string $key
	 * @param  array  $config
	 * @return object
	 */
	public function get($key, $config = []);
    
	/**
	 * Method to set the key and callback in the container.
	 *
	 * @param   string   $key        Name of key to set.
	 * @param   mixed    $definition the definition associated with `$key`. 
	 *
	 * @return  static   This object for chaining.
	 */
	public function set($key, $definition = []);
    
    /**
     * Convenience method for creating shared keys.
     *
	 * @param   string   $key        Name of key to set.
	 * @param   mixed    $definition the definition associated with `$class`. 
	 *
	 * @return  static   This object for chaining.
     */
	public function setShared($key, $definition = []);
    

    /**
     * Method to check if the container can return an entry for the given identifier.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($key);
    
	/**
	 * Remove an item from container.
	 *
	 * @param   string  $key  Name of the dataStore key to get.
	 *
	 * @return  static  This object for chaining.
	 */
    public function remove($key);
    
    /**
     * set alias of key
     * @param  string  $key
     * @param  string  $alias
     * @return static
     */
    public function alias($key, $alias);
    
    /**
     * get all key
     * @return array keys
     */
    public function keys();
    
    /**
     * resole callable
     * 
     * @param  mixed   $toResolve
     * @param  boolean $throw if true throw RuntimeException
     * @throws RuntimeException
     * @return callable
     */
    public function resolveCallable($toResolve, $throw=true);
    
}