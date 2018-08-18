<?php
namespace Wslim\Util\SnowFlake;

/**
 * single machineid snowflake id maker, nextId() build id
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class ShortSnowFlake extends SnowFlake
{
    /**
     * machineIdBits, don't use
     * 
     * @var integer
     */
    protected $machineIdBits = 0;
    
    /**
     * sequenceBits default 12 bit
     * @var integer
     */
    protected $sequenceBits = 12;
    
    
}