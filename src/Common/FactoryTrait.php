<?php
namespace Wslim\Common;

/**
 * FactoryTrait
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait FactoryTrait
{
    /**
     * instances
     * @var array
     */
    static protected $instances = [];
    
    /**
     * instance
     * @param  mixed $arg1
     * @param  mixed $arg2
     * @return static
     */
    static public function instance($args=null)
    {
        $args = func_get_args();
        
        $id = get_called_class() . '.' . md5(serialize($args));
        if (isset(static::$instances[$id])) {
            return static::$instances[$id];
        }
        
        if ($args) {
            switch (count($args)) {
                case 1:
                    $obj = new static($args[0]);
                    break;
                case 2:
                    $obj = new static($args[0], $args[1]);
                    break;
                case 3:
                    $obj = new static($args[0], $args[1], $args[2]);
                    break;
                case 4:
                    $obj = new static($args[0], $args[1], $args[2], $args[3]);
                    break;
                case 5:
                    $obj = new static($args[0], $args[1], $args[2], $args[3], $args[4]);
                    break;
                case 6:
                    $obj = new static($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                    break;
                default:
                    $obj = new static($args);
                    break;
            }
        } else {
            $obj = new static();
        }
        
        static::$instances[$id] = $obj;
        return static::$instances[$id];
    }
    
    
    /**
     * get all instance
     *
     * @return array instances array
     */
    static public function instances()
    {
        return static::$instances;
    }
    
    
    
    
}