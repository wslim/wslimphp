<?php
namespace Wslim\Mail;

class Exception extends \Exception
{
    use \Wslim\Mail\InstanceTrait;
    
    /**
     * @const string SERVER_ERROR Error template
     */
    const SERVER_ERROR = 'Problem connecting to %s. Check server, port or ssl settings for your email server.';

    /**
     * @const string LOGIN_ERROR Error template
     */
    const LOGIN_ERROR = 'Your email provider has rejected your login information. Verify your email and/or password is correct.';

    /**
     * @const string TLS_ERROR Error template
     */
    const TLS_ERROR = 'Problem connecting to %s with TLS on.';

    /**
     * @const string SMTP_ADD_EMAIL Error template
     */
    const SMTP_ADD_EMAIL = 'Adding %s to email failed.';

    /**
     * @const string SMTP_DATA Error template
     */
    const SMTP_DATA = 'Server did not allow data to be added.';
    
    /**
     * set message
     * @param  string $message
     * @param  string $param
     * @return static
     */
    public function setMessage($message, $param=null)
    {
        if ($param) {
            $message = sprintf($message, $param);
        }
        $this->message = $message;
        
        return $this;
    }
    
    /**
     * 
     * @param  string $param
     * @return static
     */
    public function addVariable($param)
    {
        $this->message = sprintf($this->message, $param);
        
        return $this;
    }
    
    /**
     * throw error
     * @throws static
     */
    public function trigger()
    {
        throw $this;
    }
}
