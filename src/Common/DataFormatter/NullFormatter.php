<?php
namespace Wslim\Common\DataFormatter;

use Wslim\Common\DataFormatterInterface;

class NullFormatter implements DataFormatterInterface
{
	/**
	 * Encode data
	 *
	 * @param   mixed $data
	 *
	 * @return  string
	 */
	static public function encode($data)
	{
		return $data;
	}

	/**
	 * Decode data
	 *
	 * @param   string $data
	 *
	 * @return  mixed
	 */
	static public function decode($data)
	{
		return $data;
	}
	
	static public function append($old, $new)
	{
	    return $new;
	}
}
