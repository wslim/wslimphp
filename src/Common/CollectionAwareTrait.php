<?php
namespace Wslim\Common;

/**
 * collection aware trait, implementation class need implements CollectionInterface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait CollectionAwareTrait 
{
    /**
     * The source data
     *
     * @var array
     */
    protected $data = array();
    
    /******************************************************
     * Collection interface
     ******************************************************/
    
    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\CollectionInterface::replace()
     */
    public function replace(array $values)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
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
        return array_key_exists($key, $this->data);
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
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->data = array();
        return $this;
    }
    
    /******************************************************
     * ArrayAccess interface, you can invoke like $obj[$key]
     ******************************************************/
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
    
    /******************************************************
     * IteratorAggregate interface
     ******************************************************/
    
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
    
}