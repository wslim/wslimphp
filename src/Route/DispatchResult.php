<?php
namespace Wslim\Route;

/**
 * router dispatch result, contain below:
 * 
 * flag:  0 not found|1 found|2 not allowed.  <br>
 * route: when flag = 1, route instance.  <br>
 * info:  ext info, when flag =2 it is http method.  <br>
 * 
 * @author 28136957@qq.com
 *
 */
class DispatchResult
{
	const NOT_FOUND = 0;
	const FOUND = 1;
	const METHOD_NOT_ALLOWED = 2;
	
	/**
	 * dispatch result flat
	 * @var int
	 * @access public
	 */
	public $flag;
	
	/**
	 * @var RouteInterface
	 * @access public
	 */
	public $route;
	
	/**
	 * @var array
	 * @access public
	 */
	public $info;
}