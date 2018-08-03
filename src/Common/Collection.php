<?php
namespace Wslim\Common;

use ArrayIterator;

/**
 * Collection
 *
 * This class provides a common interface used by many other
 * classes in a Slim application that manage "collections"
 * of data that must be inspected and/or manipulated
 * 
 */
class Collection implements CollectionInterface
{
    /**
     * The source data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Create new collection
     *
     * @param array $data Pre-populate collection with this key-value array
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /********************************************************************************
     * Collection interface
     *******************************************************************************/

    /**
     * {@inheritDoc}
     * @see \Wslim\Common\CollectionInterface::set()
     */
    public function set($key, $value=null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } elseif ($key instanceof Collection) {
            $this->set($key->all());
        } elseif (is_scalar($key)) {
            $this->data[$key] = $value;
        }
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Wslim\Common\CollectionInterface::get()
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }
    
    /**
     * {@inheritDoc}
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {   
        return $key && array_key_exists($key, $this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Wslim\Common\CollectionInterface::clear()
     */
    public function clear()
    {
        $this->data = array();
        return $this;
    }
    
    public function count()
    {
        return count($this->data);
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
        return $this->has($key);
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
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**********************************************************************************************
     * Magic methods for convenience, invoke way: $this->someKey or $this->someKey = ...
     *********************************************************************************************/
    /**
     * Get method, when invoke like $this->$key.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
    /**
     * Set method, when invoke like $this->$key=$value
     *
     * @param  string $key
     * @param  mixed $value
     * @return $this
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }
    /**
     * Return the isset $key, when invoke like isset($this->$key)
     *
     * @param  string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
    /**
     * Unset the item of $key, when invoke like unset($this->$key)
     *
     * @param  string $key
     * @return $this
     */
    public function __unset($key)
    {
        return $this->remove($key);
    }
    
    /********************************************************************************
     * IteratorAggregate interface, used to foreach
     *******************************************************************************/
    
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
    
}
