<?php
namespace Wslim\Session;

/**
 * Interface HandlerInterface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface HandlerInterface extends \SessionHandlerInterface
{
	/**
	 * register
	 *
	 * @return  mixed
	 */
	public function register();
	
	/**
	 * get storage
	 * @return \Wslim\Common\StorageInterface
	 */
	public function getStorage();
	
}

