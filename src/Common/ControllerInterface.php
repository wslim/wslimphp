<?php
namespace Wslim\Common;

/**
 * controller interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface ControllerInterface
{
    /**
     * get current app
     * @return \Wslim\Common\App
     */
    public function getApp();
    
    /**
     * get module
     * @return \Wslim\Common\Module
     */
	public function getModule();
	
	/**
	 * set module
	 * @param  \Wslim\Common\Module $module
	 * @return static
	 */
	public function setModule($module);
	
	/**
	 * get name, not contain module name: /a/b/c
	 * @return string
	 */
	public function getName();
	
	/**
	 * set name, not contain module name
	 * @param  string $name
	 * @return static
	 */
	public function setName($name);
	
	/**
	 * get controller action /a/b/c
	 * @return string
	 */
	public function getAction();
	
	/**
	 * set action
	 * @param  string $action
	 * @return static
	 */
	public function setAction($action);
	
	/**
     * get long name, module and controller and action /module/controllerA/actionB
     * @return string
     */
	public function getLongName();
	
	/**
	 * get handle stack, return handle method array: ['init', 'beforeGet', ...]
	 * @param  RequestInterface  $request
	 * @return array
	 */
	public function getHandleStack($request=null);
	
	/**
	 * handle request and return response
	 * @param  RequestInterface  $request
	 * @param  ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function handle($request, $response);

}