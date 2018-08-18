<?php
namespace Wslim\Util\SnowFlake;

use \Exception;

/**
 * snowflake id maker, nextId() build id
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class SnowFlake
{
    /**
     * maxTimestamp 41 bit (2^41)
     * -1 ^ (-1 << 41) or bindec(str_pad('1', 41, '1'))
     *
     * @var integer
     */
    static public $maxTimestamp = 2199023255551;
    
    /**
     * begin timestamp
     * 1262275200000    strtotime('2010/1/1 0:0:0')
     * 
     * @var integer
     */
    static public $epochTimestamp =  1262275200000;   
    
    /**
     * machineIdBits default 10 bit
     *
     * @var integer
     */
    protected $machineIdBits = 10;
    
    /**
     * sequenceBits default 12 bit
     * @var integer
     */
    protected $sequenceBits = 12;
    
    static private $lastTimestamp = -1;
    
    static private $sequence = 0;
    
    /**
     * current machineId
     *
     * @var integer
     */
    private $machineId = 0;
    
    /**
     * construct
     * @param  int $machineId 0-1023
     * @throws \Exception
     */
    public function __construct($machineId)
    {
        $maxMachineId = -1 ^ (-1 << $this->machineIdBits); 
        if($machineId > $maxMachineId || $machineId < 0){
            throw new Exception("machineId can't be greater than ". $maxMachineId ." or less than 0");
        }
        $this->machineId = $machineId;
    }
    
    /**
     * nextId
     * @return string
     */
    public function nextId(){
        $timestamp = $this->timeGen();
        $lastTimestamp = self::$lastTimestamp;
        
        // check is correct
        if ($timestamp < $lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds", ($lastTimestamp - $timestamp));
        }
        
        // build sequence, same process id can build more sequence
        if ($lastTimestamp == $timestamp) {
            $sequenceMask = -1 ^ (-1 << $this->sequenceBits);
            self::$sequence = (self::$sequence + 1) & $sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
            }
        } else {
            //self::$sequence = 0;
            self::$sequence = mt_rand(0, 4095);
        }
        self::$lastTimestamp = $timestamp;
        
        //时间毫秒/数据中心ID/机器ID,要左移的位数
        $timestampLeftShift = $this->machineIdBits + $this->sequenceBits;
        //组合3段数据返回: 时间戳.工作机器.序列
        
        if ($this->machineIdBits) {
            $nextId = (($timestamp - self::$epochTimestamp) << $timestampLeftShift) | ($this->machineId << $this->sequenceBits) | self::$sequence;
        } else {
            $nextId = (($timestamp - self::$epochTimestamp) << $timestampLeftShift) | self::$sequence;
        }
        return $nextId;
    }
    
    // get micro time
    protected function timeGen(){
        //$timestramp = sprintf("%.0f", microtime(true) * 1000);
        $timestramp = floor(microtime(true) * 1000);
        return  $timestramp;
    }
    
    // get next micro time
    protected function tilNextMillis($lastTimestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }
    
    public function toTimestamp($id)
    {
        $bin  = decbin($id);
        $time = bindec(substr($bin, 0, strlen($bin) - ($this->machineIdBits + $this->sequenceBits)));
        $time += static::$epochTimestamp;
        return floor($time/1000);
    }
}