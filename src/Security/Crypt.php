<?php
namespace Wslim\Security;

use Exception;

class Crypt
{
    
    static private $instances;
    
    /**
     * crypt adapter
     * @var CryptAdapterInterface
     */
    private $adapter = null;
    
    /**
     * options
     * @var array
     */
    private $options = null;
    
    /**
     * default options
     * @var array
     */
    static private $defaultOptions = array(
        'type'      => 'default',
        'class'     => null,        // 自定义加密适配器的类名
        'key'       => 'wslimphp',  // 加解密的key
        'expire'    => 0,           // 过期时长，单位s，0不限制时长
    );
    
    /**
     * return instance
     * @return static
     */
    static public function instance($type=null)
    {
        $type = $type ?: 'default';
        if (!isset(static::$instances[$type])) {
            $options = ['type' => $type];
            static::$instances[$type] = new static($options);
        }
        return static::$instances[$type];
    }
    
    /**
     * construct
     */
    public function __construct($options=array())
    {
        $this->options = (!empty($options)) ? array_merge(self::$defaultOptions, $options) : self::$defaultOptions;
    }
    
    /**
     * get adapter object
     * @throws Exception
     * @return \Wslim\Security\CryptAdapterInterface
     */
    protected function getAdapter()
    {
        $type = strtolower($this->options['type']);
        if (!$this->adapter) {
            if ($this->options['class']) {
                $adapterClass = $this->options['class'];
            } else {
                $adapterClass = '\\Wslim\\Security\\Crypt\\' . ucfirst($type) . 'Adapter';
            }
            
            if (!class_exists($adapterClass)) {
                throw new Exception('crypt type is not exists: ' . $type . ' .');
            }
            $this->adapter = new $adapterClass();
        }
        return $this->adapter;
    }
    
    /**
     * 加密函数
     * @param  string $input 需要加密的字符串
     * @param  string $key 密钥
     * @return string 返回加密结果
     */
    public function encrypt($input, $key=null)
    {
        $key = $key ?: $this->options['key'];
        return $this->getAdapter()->encrypt($input, $key);
    }
    
    /**
     * 解密函数
     * @param  string $entxt 需要解密的字符串
     * @param  string $key 密钥
     * @param  int    $expire 有效时长，单位为秒，默认0不限制时长
     * @return string 字符串类型的返回结果
     */
    public function decrypt($entxt, $key=null, $expire=null)
    {
        $key = $key ?: $this->options['key'];
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        return $this->getAdapter()->decrypt($entxt, $key, $expire);
    }
    
}
