<?php
namespace Wslim\Session\Handler;

use Wslim\Session\HandlerInterface;

/**
 * Class NativeHandler
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class NativeHandler extends \SessionHandler implements HandlerInterface
{
    /**
     * register
     * 
     * @return  mixed
     */
    public function register()
    {
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            // param2 is $register_shutdown, if false please call register_shutdown_function('session_write_close') before session_start()
            session_set_save_handler($this, true);
        } else {
            session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc')
            );
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Session\HandlerInterface::getStorage()
     */
    public function getStorage()
    {
        return null;
    }
}

