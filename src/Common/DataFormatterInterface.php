<?php
namespace Wslim\Common;

/**
 * data formater interface, methods: encode, decode, append
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface DataFormatterInterface
{
	/**
	 * Encode data, from mixed to specified format
	 *
	 * @param   mixed  $data
	 * @return  mixed
	 */
	static public function encode($data);

	/**
	 * Decode data, from specified to corresponding data type
	 *
	 * @param   mixed  $data
	 * @return  mixed
	 */
	static public function decode($data);
	
	/**
	 * append new value into old value
	 * @param mixed $old
	 * @param mixed $new
	 */
	static public function append($old, $new);
	
}

