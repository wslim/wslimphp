<?php
namespace Wslim\Redis;

use Exception;

define('CRLF', sprintf('%s%s', chr(13), chr(10)));

/**
 * Wraps native Redis errors in friendlier PHP exceptions
 * Only declared if class doesn't already exist to ensure compatibility with php-redis
 */
if (! class_exists('RedisException', false)) {
    class RedisException extends Exception {
    }
}


/**
 * Extended Redisent class used by Resque for all communication with
 * redis. Essentially adds namespace support to Redisent.
 *
 * @package		Resque/Redis
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 * 
 * @method exists()
 * @method del()
 * @method type()
 * @method keys()
 * @method expire()
 * @method ttl()
 * @method move()
 * @method set()
 * @method get()
 */
class Redis
{
    
    /**
     * instance option, must contain host|port
     * @var array
     */
    protected $options = [
        'host'      => '127.0.0.1',
        'port'      => '6379',
        'database'  => '0', // 默认数据库为0，名称取数字
        'prefix'    => '',
        'throw_error' => 1
    ];
    
    /**
     * Socket connection to the Redis server
     * @var resource
     * @access private
     */
    private $__sock;
    
	/**
	 * @var array List of all commands in Redis that supply a key as their
	 *	first argument. Used to prefix keys with the Resque namespace.
	 */
	static private $keyCommands = array(
		'exists',
		'del',
		'type',
		'keys',
		'expire',
		'ttl',
		'move',
		'set',
		'get',
		'getset',
		'setnx',
		'incr',
		'incrby',
		'decr',
		'decrby',
		'rpush',
		'lpush',
		'llen',
		'lrange',
		'ltrim',
		'lindex',
		'lset',
		'lrem',
		'lpop',
		'rpop',
		'sadd',
		'srem',
		'spop',
		'scard',
		'sismember',
		'smembers',
		'srandmember',
		'zadd',
		'zrem',
		'zrange',
		'zrevrange',
		'zrangebyscore',
		'zcard',
		'zscore',
		'zremrangebyscore',
		'sort'
	);
	// sinterstore
	// sunion
	// sunionstore
	// sdiff
	// sdiffstore
	// sinter
	// smove
	// rename
	// rpoplpush
	// mget
	// msetnx
	// mset
	// renamenx
	
	/**
	 * Creates a Redisent connection to the Redis server on host {@link $host} and port {@link $port}.
	 * @param string $host The hostname of the Redis server
	 * @param int    $port The port number of the Redis server
	 */
	public function __construct(array $options=null) 
	{
	    if ($options) {
	        $this->options = array_merge($this->options, $options);
	    }
	    $this->establishConnection();
	    $this->select($this->options['database']);
	}
	
	protected function establishConnection() 
	{
	    $this->__sock = @fsockopen($this->options['host'], $this->options['port'], $errno, $errstr);
	    
	    if (!$this->__sock && $this->options['throw_error']) {
	        throw new RedisException("{$errno} - {$errstr}");
	    }
	}
	
	/**
	 * get key commands
	 * @return array
	 */
	static public function getKeyCommands()
	{
	    return static::$keyCommands;
	}
	
	/**
	 * return connection status
	 * @return boolean
	 */
	public function connectionStatus()
	{
	    return $this->__sock ? true : false;
	}
	
	/**
	 * Set prefix, default: wslim
	 * @param string $prefix
	 */
	public function prefix($prefix)
	{
	    if (strpos($prefix, ':') === false) {
	        $prefix .= ':';
	    }
	    $this->options['prefix'] = $prefix;
	}
	
	/**
	 * destruct
	 */
	public function __destruct() 
	{
	    if ($this->__sock) {
	        fclose($this->__sock);
	    }
	}
	
	/**
	 * megic method
	 * @param  string $name
	 * @param  mixed $args
	 * @return mixed
	 */
	public function __call($name, $args) 
	{
	    return $this->execute($name, $args);
	}
	
	/**
	 * execute command 
	 * @param  string $name
	 * @param  mixed  $args
	 * @throws Exception
	 * @throws RedisException
	 * @return mixed
	 */
	public function execute($name, $args)
	{
	    if (!$this->__sock) {
	        return null;
	    }
	    
	    $args = (array) $args;
	    if(in_array($name, static::$keyCommands)) {
	        if ($this->options['prefix'] && strpos($args[0], $this->options['prefix']) === false) {
	            $args[0] = $this->options['prefix'] . $args[0];
	        }
	    }
	    
	    /* Build the Redis unified protocol command */
	    array_unshift($args, strtoupper($name));
	    $command = sprintf('*%d%s%s%s', count($args), CRLF, implode(array_map(array($this, 'formatArgument'), $args), CRLF), CRLF);
	    
	    /* Open a Redis connection and execute the command */
	    for ($written = 0; $written < strlen($command); $written += $fwrite) {
	        $fwrite = fwrite($this->__sock, substr($command, $written));
	        if ($fwrite === FALSE) {
	            throw new Exception('Failed to write entire command to stream');
	        }
	    }
	    
	    /* Parse the response based on the reply identifier */
	    $reply = trim(fgets($this->__sock, 512));
	    switch (substr($reply, 0, 1)) {
	        /* Error reply */
	        case '-':
	            throw new RedisException(substr(trim($reply), 4));
	            break;
	            /* Inline reply */
	        case '+':
	            $response = substr(trim($reply), 1);
	            break;
	            /* Bulk reply */
	        case '$':
	            $response = null;
	            if ($reply == '$-1') {
	                break;
	            }
	            $read = 0;
	            $size = substr($reply, 1);
	            do {
	                $block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
	                $response .= fread($this->__sock, $block_size);
	                $read += $block_size;
	            } while ($read < $size);
	            fread($this->__sock, 2); /* discard crlf */
	            break;
	            /* Multi-bulk reply */
	        case '*':
	            $count = substr($reply, 1);
	            if ($count == '-1') {
	                return null;
	            }
	            $response = array();
	            for ($i = 0; $i < $count; $i++) {
	                $bulk_head = trim(fgets($this->__sock, 512));
	                $size = substr($bulk_head, 1);
	                if ($size == '-1') {
	                    $response[] = null;
	                }
	                else {
	                    $read = 0;
	                    $block = "";
	                    do {
	                        $block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
	                        $block .= fread($this->__sock, $block_size);
	                        $read += $block_size;
	                    } while ($read < $size);
	                    fread($this->__sock, 2); /* discard crlf */
	                    $response[] = $block;
	                }
	            }
	            break;
	            /* Integer reply */
	        case ':':
	            $response = intval(substr(trim($reply), 1));
	            break;
	        default:
	            throw new RedisException("invalid server response: {$reply}");
	            break;
	    }
	    /* Party on */
	    return $response;
	}
	
	private function formatArgument($arg) 
	{
	    return sprintf('$%d%s%s', strlen($arg), CRLF, $arg);
	}
	
	/**
	 * batch get
	 * @param  array $keys
	 * @return array
	 */
	public function mget($keys)
	{
	    $res = [];
	    if (is_array($keys)) {
	        foreach ($keys as $k) {
	            $res[$k] = $this->get($k);
	        }
	    } else {
	        $res[$keys] = $this->get($keys);
	    }
	    
	    return $res;
	}

}
?>