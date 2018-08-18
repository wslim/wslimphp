<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * debugger
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Debugger
{
    /**
     * get caller
     * 
     * @return array ['class'=>, 'object'=>, 'file'=>, 'function'=>]
     */
    static public function getCaller()
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $current = array_shift($backtrace);
        $caller = isset($backtrace[0]) ? $backtrace[0] : $current;
        if (!isset($caller['class']))  $caller['class'] = null;
        if (!isset($caller['object'])) $caller['object'] = null;
        return $caller;
    }
    
    /**
     * trace memory usage
     * @return void
     */
    static public function traceMemory($context=null)
    {
        $caller = static::getCaller();
        $memory_usage = memory_get_usage();
        $message = sprintf('%30s::%-15s: %s', $caller['class'], $caller['function'], $memory_usage);
        if ($context) {
            $message .= ' ' . print_r($context, true);
        }
        
        Ioc::logger('trace')->debug(sprintf('[%s]%s', 'memory', $message));
    }
    
    /**
     * trace files
     * @return void
     */
    static public function traceFiles()
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $message = 'load files:' . PHP_EOL;
        if ($backtrace) foreach ($backtrace as $v) {
            $message .= sprintf('[%s] %s: %s', filesize($v['file']), $v['file'], $v['function']) . PHP_EOL;
        }
        Ioc::logger('trace')->debug(sprintf('[%s]%s', 'file', $message));
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}