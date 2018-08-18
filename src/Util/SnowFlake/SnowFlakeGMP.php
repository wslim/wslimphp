<?php
namespace Wslim\Util\SnowFlake;

use Exception;

/**
 * snowflake use gmp object method
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class SnowFlakeGMP
{
    /**
     * 
     * @var integer
     */
    private $epochTimeStamp = 1293840000000;
    
    /**
     * maxTimeStamp 41 bit (2^41)
     *
     * @var integer 
     */
    private $maxTimeStamp = 2199023255551;
    
    /**
     * machineId 10 bit
     * 
     * @var integer
     */
    private $machineId = 0;
    
    static public function instance($machineId=0)
    {
        return new static($machineId);
    }
    
    /**
     * construct
     * @param  int $machineId 0-1023
     */
    public function __construct($machineId=0)
    {
        $this->machineId = $machineId;
    }
    
    /**
     * nextId
     * @return string
     */
    public function nextId()
    {
        $timestamp = floor(microtime(true) * 1000) - $this->epochTimeStamp;
        if($timestamp > $this->maxTimeStamp) {
            throw new Exception('Snowflake: TimeStamp overflow. Unable to generate any more IDs');
        }
        
        $machine = $this->machineId;
        if($machine < 0 || $machine > 1023) {
            throw new Exception('Snowflake: Machine ID out of range');
        }
        $sequence = mt_rand(0, 4095);
        if(PHP_INT_SIZE == 4) {
            return $this->makeId32($timestamp, $machine, $sequence);
        } else {
            return $this->makeId64($timestamp, $machine, $sequence);
        }
    }
    private function makeId32($timestamp, $machine, $sequence)
    {
        $timestamp = gmp_mul((string)$timestamp, gmp_pow(2, 22));
        $machine = gmp_mul((string)$machine, gmp_pow(2, 12));
        $sequence = gmp_init((string)$sequence, 10);
        $value = gmp_or(gmp_or($timestamp, $machine), $sequence);
        return gmp_strval($value, 10);
    }
    private function makeId64($timestamp, $machine, $sequence)
    {
        // 22bit timestamp (64bit-1bit-41bit=22bit) (64bit-1bit-41bit-10bit=12bit)
        $value = ((int)$timestamp << 22) | ($machine << 12) | $sequence;
        return (string)$value;
    }
}