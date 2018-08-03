<?php
namespace Wslim\Common;

use Psr\Log\AbstractLogger; 

/**
 * Logger, has storage aware, __construct defined by Storage.
 * 
 * @method log(), debug(), info(), notice(), warning(), error(), critical(), alert(), emergency()
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Logger extends AbstractLogger
{
    // storage aware
    use \Wslim\Common\StorageAwareTrait;
    
    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    static protected $levels = array(
        'DEBUG'     => 100,
        'INFO'      => 200,
        'NOTICE'    => 250,
        'WARNING'   => 300,
        'ERROR'     => 400,
        'CRITICAL'  => 500,
        'ALERT'     => 600,
        'EMERGENCY' => 700
    );
    
    /**
     * overwrite default options, call by __construct(). 
     * if overwrite you need merge parent.
     * 
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            // enable
            'enabled'       => true,   
            // logger options
            'level'         => 'ERROR',     // 详细级别由高到低: DEBUG,INFO,NOTICE,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY
            // storage options
            'storage'       => 'file',
            'data_format'   => 'tsv', // 数据类型: csv, tsv, txt, xml, json, serialize
            'key_format'    => 'null',
            'path'          => 'logs',
            'file'          => '',
            'file_ext'      => 'log',
            // need if data_format=xml
            'entity_element'=> 'log',   //data_format=xml
        ];
    }
    
    /**
     * @var \DateTimeZone
     */
    static protected $timezone;
    
    /**
     * Adds a log record.
     *
     * @param  mixed   $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = array())
    {
        return $this->_log(null, $level, $message, $context);
    }
    
    /**
     * with specified relative file, 使用指定的相对日志文件名，相对于logger的path
     * @param  string  $file
     * @param  boolean $reset if reset log file
     * @return static
     */
    public function withFile($file, $reset=false)
    {
        $file = str_replace('\\', '/', $file);
        if ($pos = strripos($file, $this->getOption('file_ext'))) {
            $file = substr($file, 0, $pos-1);
        }
        $dir = dirname($file);
        if (!isset(static::$instances[$dir])) {
            $instance = new static($this->options);
            $instance->setOption('file', $file);
            static::$instances[$dir] = $instance;
        }
        
        if ($reset) {
            $key = static::$instances[$dir]->getOption('file');
            static::$instances[$dir]->remove($key);
            static::$instances[$dir]->remove($key . '.error');
        }
        
        return static::$instances[$dir];
    }
    
    /**
     * Adds a log record.
     * 
     * @param  string  $group
     * @param  mixed   $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    private function _log($group, $level, $message, array $context = array())
    {
        if (!$this->options['enabled']) return false;
        
        $initLevel = strtoupper($this->options['level']);
        $optionLevelNum = isset(static::$levels[$initLevel]) ? static::$levels[$initLevel] : 400;
        
        $currentLevelNum = static::$levels[strtoupper($level)];
        
        /**
         * 当写入级别大于配置级别时才进行记录
         * 比如，配置级别为 Error，则调用 warning(), notice() 等不会进行记录
         */
        if ($currentLevelNum >= $optionLevelNum) {
            if (!static::$timezone) {
                static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'PRC');
            }
            //$date = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone)->setTimezone(static::$timezone);
            //var_dump($date); exit;
            
            // 日志级别
            $level = strtolower($level);
            
            // 日志文件后缀，只对文件方式有效
            $file_ext = trim($this->getOption('file_ext'), '.');
            if (($pos = strrpos($file_ext, '.')) !== false) {
                $file_ext = substr($file_ext, $pos + 1);
            }
            $this->setOption('file_ext', $file_ext);
            
            // 日志内容
            $record = array(
                'datetime'  => date("Ymd H:i:s"),
                'level'     => $level,
                'message'   => $this->formatMessage($message),
                'context'   => $context
            );
            
            // key
            if (!($key = $this->getOption('file'))) {
                $key = date('Ymd');
            }
            
            // path: group_name/error/20161012.error.log
            $key = ($group ? $group . '/' : '') . $level . '/' . $key . '.' . $level;
            
            //$this->append($key, str_repeat('-', 50));
            return $this->append($key, $record);
        } else {
            return false;
        }
    }
    
    private function formatMessage($message)
    {
        if (!is_scalar($message)) {
            /*
            if (is_array($message)) {
                return ArrayHelper::toRaw($message, true, true);
            }
            */
            return print_r($message, true);
        }
        
        return $message;
    }
    
}