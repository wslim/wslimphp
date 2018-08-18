<?php
namespace Wslim\Tool;

use Wslim\Mail\Mail;
use Wslim\Util\StringHelper;
use Wslim\Common\ErrorInfo;

/**
 * mail sender, first set options, then send.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class MailSender
{
    /**
     * options 
     * @var array
     */
    protected $options;
    
    
    public function __construct($options=null)
    {
        if ($options) {
            $this->options = (array) $options;
        }
    }
    
    /**
     * get sender
     * @return \Wslim\Mail\Smtp
     */
    public function getSender()
    {
        return Mail::smtp($this->options);
    }
    
    /**
     * is enabled
     * @return boolean
     */
    public function isEnabled()
    {
        // not set or set true return ture.
        return !isset($this->options['enabled']) || $this->options['enabled'];
    }
    
    /**
     * send one or more 
     * 
     * @param  mixed  $send_to string|array
     * @param  string $body
     * @param  string $title
     * 
     * @return \Wslim\Common\ErrorInfo
     */
    public function send($send_to, $body, $title=null)
    {
        $sender = static::getSender();
        
        $tos = StringHelper::toArray($send_to);
        foreach ($tos as $email) {
            $sender->addTo($email);
        }
        
        $subject = $title;
        if ($title) {
            if (strlen($title) > strlen($body)) {
                $subject = $body;
                $body = $title;
            }
        } else {
            $subject = StringHelper::str_cut($body, 15);
        }
        
        $res = $sender->setSubject($subject)->setBody($body, true)->send();
        
        if (!$res) {
            return ErrorInfo::error('mail send error, check error log');
        }
        
        return ErrorInfo::success('mail send success');
    }
    
}