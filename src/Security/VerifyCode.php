<?php
namespace Wslim\Security;

use Wslim\Ioc;
use Wslim\Common\FactoryTrait;
use Wslim\Common\ErrorInfo;
use Wslim\Util\DataHelper;

/**
 * verify code, 手机或邮箱验证码，只用于生成验证码、保存到缓存、对比输入与缓存是否一致。请自行进行token等其他验证。
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class VerifyCode
{
    // factory class mixin
    use FactoryTrait;
    
    /**
     * options
     * @var array
     */
    protected $options = [
        'interval' => 300,  // 同一个号发送间隔，未超过这个不重新发送
        'day_limit'=> 30,
    ];
    
    /**
     * get cache instance
     * @return \Wslim\Common\Cache
     */
    public function getCache()
    {
        return Ioc::cache('verify_code');
    }
    
    /**
     * check before get code
     * @param  string $send_to
     * @return \Wslim\Common\ErrorInfo
     */
    public function check($send_to)
    {
        if (!DataHelper::verify_mobile($send_to) && !DataHelper::verify_email($send_to)) {
            return ErrorInfo::error('手机号或邮箱不正确');
        }
        
        // check if already send
        $sdata = static::getCache()->get($send_to);
        
        if ($sdata && $sdata['expire_time'] > (time() - $this->options['interval'])) {
            return ErrorInfo::error('信息已发送注意查收[1]');
        }
        
        return ErrorInfo::success();
    }
    
    /**
     * get code, if success return ['code'=>'1234']
     * @param  string $send_to
     * @return \Wslim\Common\ErrorInfo
     */
    public function get($send_to)
    {
        $res = static::check($send_to);
        if ($res['errcode']) {
            return $res;
        }
        
        $code = DataHelper::randomNumber(4);
        
        static::getCache()->set($send_to, [
            'send_to' => $send_to,
            'code'    => $code,
            'expire_time' => time() + $this->options['interval'],
        ]);
        
        return ErrorInfo::success(['code' => $code]);
    }
    
    /**
     * verify code
     * @param  string $send_to
     * @param  string $code
     * @return \Wslim\Common\ErrorInfo
     */
    public function verify($send_to, $code)
    {
        $sdata = static::getCache()->get($send_to);
        
        if (!$sdata) {
            return ErrorInfo::error('验证码过期请重新获取');
        } elseif (!isset($sdata['code']) || $sdata['code'] != $code) {
            return ErrorInfo::error('验证码不一致');
        }
        
        return ErrorInfo::success('验证码一致');
    }
    
    /**
     * reset code
     * @param  string $send_to
     * @return void
     */
    public function reset($send_to)
    {
        static::getCache()->remove($send_to);
    }
    
}