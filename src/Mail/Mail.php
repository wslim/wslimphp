<?php
namespace Wslim\Mail;

use Wslim\Common\Config;

/**
 * mail proxy class
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Mail 
{
    /**
     * Constructor - Store connection information
     * 
     * @param  *string  $host The SMTP host
     * @param  string   $user The mailbox user name
     * @param  string   $pass The mailbox password
     * @param  int|null $port The SMTP port
     * @param  bool     $ssl  Whether to use SSL
     * @param  bool     $tls  Whether to use TLS
     * 
     * @return \Wslim\Mail\Smtp
     */
    static public function smtp(
        $host = null,
        $user = null,
        $pass = null,
        $port = null,
        $ssl = false,
        $tls = false
    ) {
        if (!$host) {
            $host = Config::get('email');
        }
        
        $debug = false;
        
        if (is_array($host)) {
            $user = $host['user'];
            $pass = isset($host['password']) ? $host['password'] : $host['pass'];
            $port = isset($host['port']) && $host['port'] ? $host['port'] : null;
            $ssl  = isset($host['ssl']) && $host['ssl'] ? $host['ssl'] : null;
            $tls  = isset($host['tls']) && $host['tls'] ? $host['tls'] : null;
            
            $host = $host['host'];
            
            $debug = isset($host['debugging']) ? $host['debugging'] : (isset($host['debug']) ? $host['debug'] : false);
        }
        
        $instance = Smtp::instance($host, $user, $pass, $port, $ssl, $tls);
        $instance->setDebugging($debug);
        return $instance;
    }
    
    /**
     * Returns Mail IMAP
     *
     * @param *string  $host The IMAP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The IMAP port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return \Wslim\Mail\Imap
     */
    static public function imap($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        return Imap::instance($host, $user, $pass, $port, $ssl, $tls);
    }
    
    /**
     * Returns Mail POP3
     *
     * @param *string  $host The POP3 host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The POP3 port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return \Wslim\Mail\Pop3
     */
    static public function pop3($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        return Pop3::instance($host, $user, $pass, $port, $ssl, $tls);
    }
}
