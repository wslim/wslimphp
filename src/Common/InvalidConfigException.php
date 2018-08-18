<?php
namespace Wslim\Common;

/**
 * invalid config exception
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class InvalidConfigException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}
