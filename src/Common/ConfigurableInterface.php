<?php
namespace Wslim\Common;

/**
 * the implemented class can set or get property by config array.
 * Configurable class is implements of interface.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface ConfigurableInterface
{
	/**
	 * set propertys
	 * 
	 * @param array $config
	 */
	public function configure(array $config=null);
}
