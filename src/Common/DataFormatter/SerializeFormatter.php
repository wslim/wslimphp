<?php
namespace Wslim\Common\DataFormatter;

use Wslim\Common\DataFormatterInterface;

class SerializeFormatter implements DataFormatterInterface
{
	/**
	 * Encode data
	 *
	 * @param   mixed  $data
	 *
	 * @return  string
	 */
	static public function encode($data)
	{
		return serialize($data);
	}

	/**
	 * Decode data
	 *
	 * @param   string  $data
	 *
	 * @return  mixed
	 */
	static public function decode($data)
	{
		return unserialize($data);
	}
	
	static public function append($old, $new)
	{
	    if (is_scalar($old)) {
	        $old = static::decode($old);
	    }
	    if (is_scalar($new)) {
	        $new = static::decode($new);
	    }
	    return static::encode(array_merge($old, $new));
	}
}

