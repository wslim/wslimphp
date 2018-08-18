<?php
namespace Wslim\Common;

/**
 * response interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface ResponseInterface
{
    /**
     * write content
     * @param  mixed $data
     * @return static
     */
    public function write($data);
    
}