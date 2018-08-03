<?php
namespace Wslim\Common\Storage;
/**
 * Runtime Storage.
 *
 */
class RuntimeStorage extends AbstractStorage
{
	/**
	 * Property storage.
	 *
	 * @var  array
	 */
	protected $store = array();

	public function exists($key)
	{
	    $key = $this->formatKey($key);
		return isset($this->store[$key]);
	}

	public function get($key)
	{
	    $key = $this->formatKey($key);
		if (isset($this->store[$key])) {
			return $this->store[$key];
		}

		return null;
	}

	public function set($key, $value, $ttl = null)
	{
	    $key = $this->formatKey($key);
		$this->store[$key] = $value;

		return true;
	}

	public function remove($key)
	{
	    $key = $this->formatKey($key);
		if (array_key_exists($key, $this->store)) {
			unset($this->store[$key]);
		}

		return true;
	}

	public function clear()
	{
		$this->store = array();

		return true;
	}
}
