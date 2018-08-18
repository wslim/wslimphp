<?php
namespace Wslim\Common\Storage;

/**
 * StaticRuntime Storage.
 *
 */
class StaticRuntimeStorage extends AbstractStorage
{
	/**
	 * Property storage.
	 *
	 * @var  array
	 */
	static protected $store = array();

	public function exists($key)
	{
	    $key = $this->formatKey($key);
		return isset(static::$store[$key]);
	}

	public function get($key)
	{
	    $key = $this->formatKey($key);
		if (isset(static::$store[$key])) {
			return static::$store[$key];
		}

		return null;
	}

	public function set($key, $value, $ttl = null)
	{
	    $key = $this->formatKey($key);
		static::$store[$key] = $value;

		return true;
	}

	public function remove($key)
	{
	    $key = $this->formatKey($key);
		if (array_key_exists($key, static::$store)) {
			unset(static::$store[$key]);
		}

		return true;
	}

	public function clear()
	{
		static::$store = array();

		return true;
	}
}
