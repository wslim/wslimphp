<?php
namespace Wslim\Console;

class Uri
{
	private $_path;
	
	private $_query;
	
	/**
	 * 
	 * @param string $uri
	 * @return static
	 */
	public static function createFromString($uri)
	{
		$parts = parse_url($uri);
		$path = isset($parts['path']) ? $parts['path'] : '';
		$query = isset($parts['query']) ? $parts['query'] : '';
		
		return new static($path, $query);
	}
	
	public function __construct($path, $query=null)
	{
		$this->_path = $path;
		$this->_query = $query;
	}
	
	/**
	 * uri path part
	 * 
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}
	/**
	 * uri query part
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->_query;
	}
	
	public function __toString()
	{
		return $this->_path . (!empty($this->_query) ? '?' . $this->_query : '');
	}
}
