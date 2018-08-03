<?php
namespace Wslim\Util;

use Wslim\Util\SnowFlake\SnowFlake;
use Wslim\Util\SnowFlake\ShortSnowFlake;

/**
 * id maker, bigid(), uuid(), snowflake()->nextId()
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class IdMaker
{
    
    /**
     * snowflake instance, can build 19 length dec.
     * 
     * @example IdMaker::snowflace()->nextId();
     * 
     * @param  integer $machineId 0-1023
     * @return SnowFlake
     */
    static public function snowflake($machineId=0)
    {
        return new SnowFlake($machineId);
    }
    
    /**
     * 16 length dec id, use
     * @return integer
     */
    static public function bigId()
    {
        return (new ShortSnowFlake(0))->nextId();
    }
    
    /**
     * get timestamp from bigid
     * @param  integer $bigid
     * @return integer
     */
    static public function toTimestamp($bigid)
    {
        return (new ShortSnowFlake(0))->toTimestamp($id);
    }
    
    /**
     * get uuid '315B817C-11F5-718F-F7BD-95E0E95519D0'
     *
     * @param  boolean $separator if true then contain '-'
     * @return string
     */
    static public function uuid($separator=true)
    {
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);    //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(mt_rand(), true)));
            $hyphen = $separator ? chr(45) : '';  // "-"
            $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
            //$uuid = chr(123) . $uuid .chr(125);  // "{" "}"
            return $uuid;
        }
    }
}


