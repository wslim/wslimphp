<?php
namespace Wslim\Common\DataFormatter;

use Wslim\Common\DataFormatterInterface;

class CsvFormatter implements DataFormatterInterface
{
	/**
	 * Encode data
	 *
	 * @param   mixed  $data
	 *
	 * @throws \InvalidArgumentException
	 * @return  string
	 */
	static public function encode($data)
	{
	    if (is_array($data)) {
	        foreach ($data as $k=>$v) {
	            if (is_array($v)) {
	                if (empty($v)) {
	                    $data[$k] = null;
	                } else {
	                    $data[$k] = implode('|', $v);
	                }
	            }
	        }
	        $data = implode(",", $data);
	    } elseif ((is_object($data) && !method_exists($data, '_toString'))) {
	        throw new \InvalidArgumentException(__CLASS__ . ' can not handle an array or non-stringable object.');
	    }

		return $data;
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
	    $data = explode(",", $data);
		return $data;
	}
	
	static public function append($old, $new)
	{
	    if (!is_scalar($old)) {
	        $old = static::encode($old);
	    }
	    return $old . static::encode($new);
	}
	
}

