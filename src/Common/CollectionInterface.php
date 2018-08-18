<?php
namespace Wslim\Common;

/**
 * Collection Interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface CollectionInterface extends \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Set collection item, it can be append
     *
     * @param  mixed  $key   string|array, The data key or array data
     * @param  mixed  $value The data value
     * 
     * @return static
     */
    public function set($key, $value=null);
    
    /**
     * Get collection item for key
     * 
     * @param  string $key     The data key
     * @param  mixed  $default The default value to return if data key does not exist
     *
     * @return mixed The key's value, or the default value
     */
    public function get($key, $default = null);

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all();

    /**
     * Get collection keys
     *
     * @return array The collection's source data keys
     */
    public function keys();    

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Remove item from collection
     *
     * @param  string $key The data key
     * @return static
     */
    public function remove($key);

    /**
     * Remove all items from collection.
     * @return static
     */
    public function clear();
}
