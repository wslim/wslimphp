<?php
namespace Wslim\Common\Storage;

class NullStorage extends AbstractStorage
{
	public function exists($key)
	{
		return false;
	}

	public function get($key)
	{
		return null;
	}

	public function set($key, $value, $ttl = null)
	{
		return true;
	}

	public function remove($key)
	{
		return true;
	}

	public function clear()
	{
		return true;
	}
}
