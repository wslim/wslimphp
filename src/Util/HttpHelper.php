<?php
namespace Wslim\Util;

/**
 * HttpHelper
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class HttpHelper
{
    
    /************************************************************
     * cookie
     ************************************************************/
    /**
     * cookie prefix, in session cookie use another prefix
     * @var string
     */
    static public $COOKIE_PREFIX = 'WS_';
    
    /**
     * cookie domain
     * @var string
     */
    static public $COOKIE_DOMAIN   = '';
    static public $COOKIE_EXPIRE   = 3600;
    static public $COOKIE_SECURE   = false;
    static public $COOKIE_HTTP_ONLY= true;
    
    /**
     * set cookie, need think cross domain.
     * @param  string  $name        cookie 名
     * @param  string  $value       cookie 值
     * @param  int     $expire      cookie 有效期，单位秒
     * @param  string  $path        cookie 服务器路径 默认为 /
     * @param  string  $domain      cookie 域名
     * @param  boolean $secure      是否通过安全的 HTTPS 连接来传输 cookie,默认为false
     * @param  boolean $httponly    是否仅用于http
     * @return void
     */
    static public function setCookie($name, $value, $expire='3600', $path=null, $domain=null, $secure=null, $httponly=null)
    {
        $expire = $expire ? time() + (is_numeric($expire) ? intval($expire) : intval(self::$COOKIE_EXPIRE)) : 0;
        if (empty($path))   $path = '/';
        if (empty($domain)) $domain = self::$COOKIE_DOMAIN ? self::$COOKIE_DOMAIN : UriHelper::GetRootDomain();
        $secure   = is_null($secure) ? static::$COOKIE_SECURE : (bool) $secure;
        $httponly = is_null($httponly) ? static::$COOKIE_HTTP_ONLY : (bool) $httponly;
        
        $name = static::$COOKIE_PREFIX . $name;
        $result = setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        $_COOKIE[$name] = $value; 
    }
    
    /**
     * get cookie, need think cross domain.
     * @param  string $name
     * @return mixed
     */
    static public function getCookie($name= null)
    {
        if ($name) $name = static::$COOKIE_PREFIX . $name;
        return empty($name) ? $_COOKIE : (isset($_COOKIE[$name]) ? $_COOKIE[$name] : '');
    }
    
    /**
     * if http is mobile
     * @return boolean
     */
    static public function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA'])
            || isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])
            || isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])
            // Check whether the browser/gateway says it accepts WML.
            || isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML") > 0 )
        {
            return true;
        }
        
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-'
            , 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu',
            'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini',
            'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
        );
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", $browser) && strpos($browser, 'ipad') === false) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * is wechat client
     * 
     * @return boolean
     */
    static public function isWechat()
    {
        return (false !== stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'));
    }
    
    /**
     * is alipay client
     * 
     * @return boolean
     */
    static public function isAlipay()
    {
        return (false !== stripos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient'));
    }
    
    /***********************************************************
     * ip
     ***********************************************************/
    /**
     * get client ip
     *
     * @return string
     */
    static public function getClientIp()
    {
        /*
         $headers = function_exists('apache_request_headers')
         ? apache_request_headers()
         : $_SERVER;
         
         return isset($headers['REMOTE_ADDR']) ? $headers['REMOTE_ADDR'] : '0.0.0.0';
         */
        
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return '0.0.0.0';
        }
        
        return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '0.0.0.0';
    }
    
    /**
     * check client ip is legal.
     * @param  array   $allowed_ips
     * @return boolean
     */
    static public function checkClientIp(array $allowed_ips = [])
    {
        array_unshift($allowed_ips, '127.0.0.1');
        $ip = static::getClientIp(); 
        $check_ip_arr= explode('.',$ip);    //要检测的ip拆分成数组
        
        if(!in_array($ip, $allowed_ips)) {
            foreach ($allowed_ips as $val) {
                if(strpos($val,'*')!==false){//发现有*号替代符
                    $arr = explode('.', $val);
                    $bl = true;//用于记录循环检测中是否有匹配成功的
                    for($i=0;$i<4;$i++){
                        if($arr[$i]!='*'){//不等于*  就要进来检测，如果为*符号替代符就不检查
                            if($arr[$i]!=$check_ip_arr[$i]){
                                $bl=false;
                                break;//终止检查本个ip 继续检查下一个ip
                            }
                        }
                    }//end for
                    if($bl){//如果是true则找到有一个匹配成功的就返回
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * send 403 page
     */
    static public function send403()
    {
        header('HTTP/1.1 403 Forbidden');
        echo "Access forbidden";
        exit;
    }
    
}
