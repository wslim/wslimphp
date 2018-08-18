<?php
namespace Wslim\Common;

/**
 * common storage, can be use to cache, session, or other.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Storage implements StorageInterface
{
    // storage aware
    use \Wslim\Common\StorageAwareTrait;
    
}