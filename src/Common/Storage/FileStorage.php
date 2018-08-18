<?php
namespace Wslim\Common\Storage;

use Wslim\Common\DataFormatter\XmlFormatter;

/**
 * File Storage
 * 
 * Supported options:
 * - path                   : The path for storage dir.
 * - ttl (integer)          : The default expire interval of seconds for the storage life.
 * - file_locking (boolean) :
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FileStorage extends AbstractStorage
{
	/**
	 * Property defaultOptions.
	 * 
	 * @var  array
	 */
	static protected $defaultOptions = [
	    'key_format'   => 'md5',
	    'data_format'  => 'json',      // null, string, json, serialize, csv, tsv, xml
	    'path'         => 'storage',   // storage=file 时必需
	    'ttl'          => 0,           // 0 do not expired forever, need remove yourself
	    'file_ext'     => 'php',       // 文件后缀
	    'file_locking' => true,
	    'deny_access'  => false,       // file_ext=php 时适用
	    'deny_code'    => '<?php die("Access Deny"); ?>',  // file_ext=php 时适用
	];

	/**
	 * check options, extend class must call parent::checkOptions()
	 * @return void
	 */
	protected function checkOptions()
	{
	    parent::checkOptions();
	    
	    $this->options['file_ext'] = ltrim($this->options['file_ext'], '.');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::exists()
	 */
	public function exists($key)
	{
	    if (is_file($this->fetchStreamUri($key))) {
	        // if set ttl then auto check and remove
	        if ($this->isExpired($key) && $this->options['ttl']) {
	            try {
	                $this->remove($key);
	            } catch (\RuntimeException $e) {
	                throw new \RuntimeException(sprintf('Unable to clean expired cache entry for %s.', $key), null, $e);
	            }
	            
	            return false;
	        }
	        
	        return true;
	    }
	    
	    return false;
	}
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::get()
	 */
	public function get($key)
	{
	    $value = static::getRaw($key);
	    
	    return $this->decodeValue($value);
	}
	
	public function getRaw($key)
	{
	    if (!$this->exists($key)) {
	        return null;
	    }
	    
	    $resource = @fopen(static::fetchStreamUri($key), 'rb');
	    
	    if (!$resource) {
	        throw new \RuntimeException(sprintf('Unable to fetch data entry for %s, because cannot open the resource.', $key));
	    }
	    
	    // If locking is enabled get a shared lock for reading on the resource.
	    if ($this->options['file_locking'] && !flock($resource, LOCK_SH)) {
	        throw new \RuntimeException(sprintf('Unable to fetch cache entry for %s, because cannot obtain a lock.', $key));
	    }
	    
	    $data = stream_get_contents($resource);
	    
	    // If locking is enabled release the lock on the resource.
	    if ($this->options['file_locking'] && !flock($resource, LOCK_UN)) {
	        throw new \RuntimeException(sprintf('Unable to fetch data entry for %s, because cannot release the lock.', $key));
	    }
	    
	    fclose($resource);
	    
	    if ( (strpos($this->options['file_ext'], 'php') !== false) && $this->options['deny_access']) {
	        $data = substr($data, strlen($this->options['deny_code']));
	    }
	    
	    return $data;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::set()
	 */
	public function set($key, $value, $ttl = null)
	{
		return $this->_set($key, $value, $ttl, false);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\Storage\AbstractStorage::append()
	 */
	public function append($key, $value, $ttl = null)
	{
	    return $this->_set($key, $value, $ttl, true);
	}
	
	protected function _set($key, $value, $ttl, $append=false)
	{
	    $fileName = $this->fetchStreamUri($key); 
	    //print_r('file key:' . $key . PHP_EOL); print_r('file path:' . $fileName . PHP_EOL);
		
	    $filePath = pathinfo($fileName, PATHINFO_DIRNAME);
		$this->checkFilePath($filePath);
		
		$value = $this->encodeValue($value);
		if ($append) $value .= PHP_EOL;
		
		// xml special handle
		switch ($this->options['data_format']) {
		    case 'xml':
		        if ($append) {
		            $output = file_get_contents($fileName);
		            $value = XmlFormatter::append($output, $value);
		            $append = false;
		        }
		        break;
		    default:
		        		        
		}
		
		// file_put_contents flag
		$flag = $append ? FILE_APPEND : null;
		if ($this->options['file_locking']) {
			$flag = $flag | LOCK_EX;
		}
		
		if (!$append && (strpos($this->options['file_ext'], 'php') !== false) && $this->options['deny_access']) {
		    $value = $this->options['deny_code'] . $value;
		}
		
		$success = (bool) file_put_contents(
				$fileName,
				$value,
				$flag);
		
		return $success;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::remove()
	 */
	public function remove($key)
	{
	    return (bool) @unlink($this->fetchStreamUri($key));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\Storage\AbstractStorage::clear()
	 */
	public function clear()
	{
	    return static::_clear(false);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::clearExpired()
	 */
	public function clearExpired()
	{
	    return static::_clear(true);
	}
	
	protected function _clear($onlyExpired=true)
	{
	    if ($onlyExpired) {
	        // do not remove
	        if (!$this->options['ttl']) {
	            return true;;
	        }
	    }
	    
	    $filePath = rtrim($this->options['path'], '/') . ($this->options['group'] ? '/' . trim($this->options['group'], '/') : '');
	    $this->checkFilePath($filePath);
	    
	    // 调用 unlink，不要直接使用 delete()
	    $iterator = new \RegexIterator(
	        new \RecursiveIteratorIterator(
	            new \RecursiveDirectoryIterator($filePath)
	            ),
	        '/' . preg_quote($this->options['file_ext']) . '$/i'
	        );
	    
	    /* @var  \RecursiveDirectoryIterator  $file */
	    foreach ($iterator as $file)
	    {
	        if ($file->isFile()) {
	            // if not expired and ttl, check
	            if ($onlyExpired) {
	                // do not remove
	                if (filemtime($file->getRealPath()) >= (time() - $this->options['ttl'])) {
	                    continue;
	                }
	            }
	            
	            @unlink($file->getRealPath());
	        }
	    }
	    
	    // rmdir
	    if (!$onlyExpired) {
	        foreach ($iterator as $file)
	        {
	            if ($file->isDir()) {
	                @rmdir($file->getRealPath());
	            }
	        }
	    }
	    
	    return true;
	}
	
	/**
	 * Check that the file path is a directory and writable.
	 *
	 * @param   string   $filePath  A file path.
	 *
	 * @return  boolean  The method will always return true, if it returns.
	 * @throws  \RuntimeException if the file path is invalid.
	 */
	protected function checkFilePath($filePath)
	{
	    $filePath = rtrim($filePath, '/\\');
	    if (!is_dir($filePath)) {
			mkdir($filePath, 0755, true);
		}
        
		if (!is_writable($filePath)) {
			throw new \RuntimeException(sprintf('The base cache path `%s` is not writable.', $filePath));
		}

		return true;
	}

	/**
	 * Get the full stream URI for the cache entry.
	 *
	 * @param   string  $key  The storage entry identifier.
	 *
	 * @return  string  The full stream URI for the cache entry.
	 * 
	 * @throws  \RuntimeException if the cache path is invalid.
	 */
	protected function fetchStreamUri($key)
	{
	    $key = $this->formatKey($key);
	    
	    // 20160312, parse key to path/nkey parts, 以支持带路径的 key 设置
	    $nPath = str_replace(['../', '..\\', './', '.\\'], '', dirname($key));
	    $nPath = trim($nPath, '/');
	    if ($nPath == '.') $nPath = '';
	    
	    $key  = basename($key);
        
	    $filePath = rtrim($this->options['path'], '/') . ($nPath ? '/' . $nPath : '');

		//$this->checkFilePath($filePath);
        
		return sprintf(
			'%s/%s' . '.' . $this->options['file_ext'], // 作为后缀
			$filePath,
			$key
		);
	}

	/**
	 * Check whether or not the data by id has expired.
	 *
	 * @param   string  $key  The storage entry identifier.
	 * @return  boolean True if the data has expired.
	 */
	public function isExpired($key)
	{
		// Check to see if the cached data has expired.
		if (filemtime($this->fetchStreamUri($key)) < (time() - $this->options['ttl'])) {
			return true;
		}
        
		return false;
	}

	/**
	 * getDenyAccess
	 *
	 * @param boolean $bool
	 *
	 * @return  boolean
	 */
	public function denyAccess($bool = null)
	{
		if ($bool === null) {
			return $this->options['deny_access'];
		}

		return $this->options['deny_access'] = $bool;
	}
	
	protected function getKeysByGroup($group)
	{
	    $filePath = rtrim($this->options['path'], '/') . ($this->options['group'] ? '/' . trim($this->options['group'], '/') : '');
	    $filePath .= '/' . $group;
	    $this->checkFilePath($filePath);
	    
	    $keys = [];
	    
	    $iterator = new \RegexIterator(
	        new \RecursiveIteratorIterator(
	            new \RecursiveDirectoryIterator($filePath)
	            ),
	        '/' . preg_quote($this->options['file_ext']) . '$/i'
	        );
	    
	    /* @var  \RecursiveDirectoryIterator  $file */
	    foreach ($iterator as $file)
	    {
	        if ($file->isFile()) {
	            $key = static::decodeValue(file_get_contents($file->getRealPath()));
	            if (is_array($key)) {
	                $key = current($key);
	            }
	            
	            $keys[] = $key;
	        }
	    }
	    
	    return $keys;
	}
}

