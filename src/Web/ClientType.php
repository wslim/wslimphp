<?php
namespace Wslim\Web;

use Wslim\Util\HttpHelper;

/**
 * ClientType, pc|mobile|app|wx|wxapp|alipay|alipayapp
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class ClientType
{
    const PC        = 'pc';
    const MOBILE    = 'mobile';
    const APP       = 'app';
    /**
     * wx public
     * @var string
     */
    const WX        = 'wx';
    const WXAPP     = 'wxapp';
    
    const ALIPAY    = 'alipay';
    const ALIPAYAPP = 'alipayapp';
    
    /**
     * get client types
     * @return array [name=>title]
     */
    static public function getClientTypes()
    {
        return [
            static::PC      => 'PC端',
            static::MOBILE  => '手机端',
            static::APP     => 'APP',
            static::WX      => '微信公众号',
            static::WXAPP   => '微信小程序',
        ];
    }
    
    /**
     * format 
     * @param  string $clientType
     * @return string
     */
    static public function formatClientType($clientType)   {
        if (!in_array($clientType, array_keys(ClientType::getClientTypes()))) {
            $clientType = 'pc';
        }
        return $clientType;
    }
    
    /**
     * detect 
     * @return string
     */
    static public function detectClientType()
    {
        $clientType       = static::PC;
        if (HttpHelper::isMobile()) {
            if (HttpHelper::isWechat()) {
                $clientType = static::WX;
            } elseif (HttpHelper::isAlipay()) {
                $clientType = static::ALIPAY;
            } else {
                $clientType = static::MOBILE;
            }
        }
        
        return $clientType;
    }
    
}