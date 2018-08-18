<?php
namespace Wslim\Common;

/**
 * request interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface RequestInterface
{
    /**
     * request method
     * @return string
     */
    public function getMethod();
    
    /**
     * detect Content-Type: application/json, application/xml, text/xml, text/html, text/plain
     * @return string
     */
    public function detectContentType();
    
    /**
     * get request params, contain get and post
     * @return array
     */
    public function getRequestParams();
    
    /**
     * convenient method for get param(s)
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function input($name=null, $default=null);
    
}