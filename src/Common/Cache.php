<?php
namespace Wslim\Common;

/**
 * Class Cache
 * 实例化时可以传入 group 选项作为分组；
 * 实例化后可以通过 $this->setGroup('xxx') 
 */
class Cache extends Storage implements \ArrayAccess
{
    /**
     * not cache
     * @var string
     */
    const CACHE_NOT     = 'CACHE_NOT';
    
    /**
     * auto flush cache
     */
    const CACHE_AUTO    = 'CACHE_AUTO';
    
    /**
     * force flush cache and get value
     */
    const CACHE_FLUSH   = 'CACHE_FLUSH';
    
    /**
     * force flush cache and don't get value
     */
    const CACHE_FLUSH_ONLY    = 'CACHE_FLUSH_ONLY';
    
    /**
     * overwrite default options.
     * if overwrite you need merge parent.
     * 
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'storage'       => 'file',  // null|file|memcache|memcached|redis|wslim_redis|xcache
            'path'          => 'caches',
            'key_format'    => 'md5',
            'data_format'   => 'json',
            'group'         => '',
            'ttl'           => 7200
        ];
    }
    
    /**
     * check is cache enum
     * @param  string $enum
     * @return boolean
     */
    static public function isCacheEnum($enum)
    {
        if ($enum && in_array($enum, [static::CACHE_NOT, static::CACHE_AUTO, static::CACHE_FLUSH, static::CACHE_FLUSH_ONLY])) {
            return true;
        }
        return false;
    }
    
}
