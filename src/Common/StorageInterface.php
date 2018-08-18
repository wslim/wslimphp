<?php
namespace Wslim\Common;

/**
 * StorageInterface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface StorageInterface
{
    /**
     * storage class allowed option key
     * @var array
     */
    const AllowOptions = [
        'data_format', 
        'key_format', 
        'group', 
        'ttl',
        'throw_error',
        
        // for file
        'path', 
        'file_ext', 
        'file_locking', 
        'deny_access', 
        'deny_code',
        
        // for redis/memcached server
        'servers',
        'host',
        'port', 
        'pconnect',
        'timeout',
        
        // for xml
        'root_element', 
        'entity_element'
    ];
    
    /**
     * connect storage drive, return connection status, for redis and memcache need it
     * 
     * @return boolean
     */
    public function connect();
    
    /**
     * close storage drive.
     * @return boolean
     */
    public function close();
    
    /**
     * get real key
     * @param  mixed $key
     * @return string $realKey
     */
    public function formatKey($key);
    
	/**
	 * Method to determine whether a storage entry has been set for a key.
	 *
	 * @param   string  $key  The storage entry identifier.
	 * 
	 * @return  boolean
	 */
	public function exists($key);

	/**
	 * Here we pass in a data key to be fetched from the storage.
	 * A DataItem object will be constructed and returned to us
	 *
	 * @param  string $key The unique key of this item
	 *
	 * @return mixed  $value
	 */
	public function get($key);
	
	public function getRaw($key);

	/**
	 * Persisting our data in the storage, uniquely referenced by a key with an optional expiration TTL time.
	 * 
	 * @param  string $key
	 * @param  mixed  $value
	 * @param  int|\DateInterval|\DateTime $ttl  The Time To Live of an item.
	 *
	 * @return boolean True on success
	 */
	public function set($key, $value, $ttl = null);
	
	/**
	 * append value for assigned key
	 * @param  string   $key
	 * @param  mixed    $val
	 * @param  null|int $ttl
	 *
	 * @return boolean
	 */
	public function append($key, $val, $ttl=null);
	
	/**
	 * Remove an item from the storage by its unique key
	 *
	 * @param  string $key The unique cache key of the item to remove
	 *
	 * @return boolean True on success
	 */
	public function remove($key);
	
	/**
	 * warning: This will wipe out all the storage items.
	 * 
	 * @return boolean True on success
	 */
	public function clear();
	
	/**
	 * clear expired items
	 * 
	 * @return boolean True on success
	 */
	public function clearExpired();
	
	/**
	 * get multiple items
	 *
	 * @param   array $keys
	 *
	 * @return  \Traversable A traversable collection of Cache Items in the same order as the $keys
	 *                       parameter, keyed by the cache keys of each item. If no items are found
	 *                       an empty Traversable collection will be returned.
	 */
	public function mget(array $keys);

	/**
	 * set multiple items
	 *
	 * @param   array $items
	 *
	 * @return  boolean True on success
	 */
	public function mset(array $items, $ttl = null);

	/**
	 * Removes multiple items from the pool.
	 *
	 * @param  array $keys An array of keys that should be removed from the pool.
	 *
	 * @return boolean True on success
	 */
	public function mremove(array $keys);
	
	/**
	 * call
	 *
	 * @param string   $key
	 * @param callable $callable
	 * @param array    $args
	 *
	 * @throws \InvalidArgumentException
	 * @return  mixed
	 */
	public function call($key, $callable, $args = []);
	
	/**
	 * set group option
	 * @param  string $group
	 * @return static
	 */
	public function setGroup($group);
	
	/**
	 * get option
	 * @param  string|null $key
	 * @return mixed
	 */
	public function getOption($key=null);
	
	/**
	 * set option
	 * @param  string|array $key
	 * @param  mixed $value
	 * @return static
	 */
	public function setOption($key, $value=null);
	
}

