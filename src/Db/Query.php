<?php
namespace Wslim\Db;

use Wslim\Common\Collection;

/**
 * Query, use to hold query options, an extended collection
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Query extends Collection
{
    /**
     * allowed set method
     * @var array
     */
    static public $allowedSetMethods   = array(
        'select', 'count', 'distinct', 'from', 'join', 'where', 'group', 'having', 'order', 'limit', 'page', 'pagesize', 'start',
        'insert', 'fields', 'values',
        'update', 'set',
        'delete',
        'data',
        'table',
        'binds', 'bind',
        'type',
        'where_connect'
    );
    
    /**
     * overwrite, can set the same key more times, so the item obtain the array value.
     * notice, if reset, can use remove()->set()
     * {@inheritDoc}
     * @see \Wslim\Common\Collection::set()
     */
    public function set($name, $value=null)
    {   
        if ($name instanceof Query) {
            parent::set($name);
            return $this;
        } elseif (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        } elseif (is_numeric($name)) {
            $this->set('where', $name);
            return $this;
        }
        
        if (!empty($value)) {
            if ($name == 'type') {
                $this->type($value);
            } elseif (in_array($name, array('distinct', 'group', 'having', 'order', 'limit', 'page', 'pagesize', 'start', 'result_key'))) {
                $this->data[$name] = is_array($value) ? $value : array($value);
            } elseif (in_array($name, ['set', 'values', 'data'])) { // data
                if (!isset($this->data[$name])) $this->data[$name] = [];
                
                if (is_array($value) && isset($value[0])) {
                    $this->data[$name] += $value;
                } else {
                    $this->data[$name][] = $value;
                }
            } elseif ($name == 'join' || $name == 'leftjoin') {
                $name = 'join';
                if (!isset($this->data[$name])) $this->data[$name] = [];
                if (is_array($value) && isset($value[0])) {
                    if (is_array($value[0]) || (isset($value[1]) && is_string($value[1]) && preg_match('/\s+as\s+/', $value[1])) ) {
                        $this->data[$name] += $value;
                    } else {
                        $this->data[$name][] = $value;
                    }
                } else {
                    $this->data[$name][] = $value;
                }
            } else {
                if ($name == 'select') { // select == fields
                    $this->type($name);
                    $name = 'fields';
                } elseif (in_array($name, ['insert', 'update', 'delete'])) { // table
                    $this->type($name);
                    $name = 'table';
                } elseif (in_array($name, ['table', 'from'])) {
                    $name = 'table';
                } elseif ($name == 'count') {
                    $this->data['count'] = 1;
                    $name = 'fields';
                    if (strpos($value, 'count') === false) $value = 'count(' . $value . ')';
                } 
                
                if (is_array($value)) {
                    $this->data[$name] = isset($this->data[$name]) ? array_merge($this->data[$name], $value) : $value;
                } else {
                    $this->data[$name][] = $value;
                }
            }
        } elseif (in_array($name, ['select', 'insert', 'update', 'delete'])) {
            $this->type($name);
        } elseif ($name == 'count') {
            $this->data['count'] = 1;
        }
        
        return $this;
    }
    
    /**
     * set directly value
     * @param  string $name
     * @param  array  $value
     * @return static
     */
    public function setRaw($name, $value)
    {
        $this->data[$name] = (array)$value;
        
        return $this;
    }
    
    /**
     * get or set query hasAlias
     * @return mixed bool|static
     */
    public function hasAlias($hasAlias=null)
    {
        if (is_null($hasAlias)) {
            return (bool) $this->hasAlias[0];
        }
        
        $this->hasAlias = $hasAlias;
        return $this;
    }
    
    /**
     * get or set type
     * @param  string|null $type
     * @return string|static
     */
    public function type($type=null)
    {
        if (isset($type)) {
            $this->data['type'] = $type;
            return $this;
        }
        
        return isset($this->data['type']) ? $this->data['type'] : 'select';
    }
    
    /**
     * where method, if params count is 2 then op be value
     * 
     * @param  mixed  $where
     * @param  string $op 
     * @param  mixed  $value
     * @return static
     */
    public function where($where, $op=null, $value=null)
    {
        if ($where) {
            if (is_array($where)) {
                $this->set('where', $where);
            } elseif (!is_null($op)) {
                if (!is_null($value)) {
                    $value = [$op, $value];
                } else {
                    $value  = $op;
                }
                
                $this->set('where', [$where => $value]);
            } else {
                $this->set('where', $where);
            }
        }
        
        return $this;
    }
    
    /**
     * get or set isFormatted, set true then donot format where and input data
     * 
     * @param  mixed $isFormatted if bool it will be set
     * 
     * @return boolean|\Wslim\Db\Query
     */
    public function formatted($isFormatted=null)
    {
        if (isset($isFormatted)) {
            $this->data['formatted'] = $isFormatted;
            return $this;
        }
        
        return isset($this->data['formatted']) ? $this->data['formatted'] : false;
    }
    
    /**
     * clone 
     * @return \Wslim\Db\Query
     */
    public function clone()
    {
        return clone $this;
    }
    
    /**
     * __call magic method, delegate to set() or throw exception
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        // calling the method directly is faster then call_user_func_array() !
        
        if (in_array($method, static::$allowedSetMethods)) {
            $count = count($params);
            if ($count == 1) {
                $this->set($method, $params[0]);
            } elseif ($count > 1) {
                $this->set($method, [$params[0], $params[1]]);
            } else {
                $this->set($method, null);
            }
            return $this;
        } else {
            throw new Exception('Query not allowed setting: ' . $method . '.');
        }
    }
    
}
