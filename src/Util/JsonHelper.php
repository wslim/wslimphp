<?php
namespace Wslim\Util;

class JsonHelper 
{
	/**
	 * Encode data
	 *
	 * @param  mixed  $data
	 * @return string
	 */
	static public function encode($data)
	{
	    if (is_string($data)) {
	        $data = str_replace(array("\r\n"), "", $data);
	        if (strpos($data, '{') ===0 || strpos($data, '[') ===0) {
	            $data = json_decode($data, true);
	        }
	    }
	    
		return json_encode($data);
	}
    
	/**
	 * Decode data
	 *
	 * @param  string  $data
	 * @param  boolean $assoc
	 * @return mixed
	 */
	static public function decode($data, $assoc=false)
	{
	    if (is_array($data) && $assoc === true) {
	        return $data;
	    }
	    if (!is_string($data)) {
	        $data = json_encode($data);
	    }
	    return json_decode($data, $assoc);
	}
	
	/**
	 * to array
	 * @param  mixed $data
	 * @return array
	 */
	static public function toArray($data)
	{
	    return static::decode($data, true);
	}
	
	/**
	 * append new value into old value
	 * @param  mixed $old
	 * @param  mixed $new
	 * @return string
	 */
	static public function append($old, $new)
	{
	    if (is_scalar($old)) {
	        $old = static::decode($old, true);
	    }
	    if (is_scalar($new)) {
	        $new = static::decode($new, true);
	    }
	    return static::encode(array_merge($old, $new));
	}
	
	/**
	 * dump pretty json
	 * @param  mixed $data
	 * @param  bool  $line
	 * @return string
	 */
	static public function dump($data, $line=true)
	{
	    if (is_string($data)) {
	        $data = json_decode(str_replace(["\r\n"], "", $data), true);
	    }
	    
	    if (is_array($data)) {
	        $isPureArray = false;
	        $keys = array_keys($data);
	        foreach ($keys as $k) {
	            if (!is_numeric($k)) {
	                $isPureArray = false;
	                break;
	            }
	        }
	        
	        $line      = $line ? PHP_EOL : '';
	        $str       = "";
	        $s_data    = [];
	        
	        foreach ($data as $name => $value) {
	            if (is_scalar($value)) {
	                $s_data[] = (!$isPureArray ? '"' . $name . '": ' : '') . (is_string($value) ? '"' . $value . '"' : $value);
	            } else {
	                $s_data[] = (!$isPureArray ? '"' . $name . '": ' : '') . static::dump($value, $line);
	            }
	        }
	        $str .= implode(',' . $line , $s_data);
	        
	        $str = $isPureArray ? '[' . $line . $str . $line . ']' : '{' . $line . $str . $line . '}';
	        return $str;
	    }
	    
	    return $data;
	}
}

