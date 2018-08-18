<?php
namespace Wslim\Tool;

abstract class SmsSender
{
    /**
     * is enabled
     * @return boolean
     */
    public function isEnabled()
    {
        return true;
    }
    
    /**
     * send, one or more mobile
     * @param  mixed  $send_to int|array
     * @param  string $content
     * @return \Wslim\Common\ErrorInfo
     */
    abstract public function send($send_to, $content);
    
    /**
     * send use template, one or more mobile
     * @param  mixed  $send_to int|array
     * @param  string $tplname
     * @return \Wslim\Common\ErrorInfo
     */
    abstract public function sendWithTemplate($send_to, $tplname);
}