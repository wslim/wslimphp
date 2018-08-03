<?php
namespace Wslim\Security;

use Wslim\Security\Captcha\CaptchaImage;
use Wslim\Util\DataHelper;
use Wslim\Common\ErrorInfo;
use Wslim\Common\FactoryTrait;

/**
 * Captcha, 图形验证码类，根据传递的code生成图片内容，如同时传递了 token 则保存对应token的code以用于验证，否则生成一个token. 
 * 注，captcha 使用的是独立的 token 机制
 * 
 * @uses <p>
 * get($code, $token), 根据一个 code 获取图片base64内容, return ['base64'=>..., 'token'=>,]<br>
 * verify($token, $code), 验证 code    <br>
 * reset($token), 重置<br>
 * </p>
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Captcha
{
    use FactoryTrait;
    
    protected $options = [];
    
    protected $tokenInstance = null;
    
    protected $imageHandler = null;
    
    /**
     * get token instance
     * @return \Wslim\Security\Token
     */
    protected function getTokenInstance()
    {
        if (!$this->tokenInstance) {
            $this->tokenInstance = Token::instance('captcha')->setOptions(['title' => '图形码']);
        }
        
        return $this->tokenInstance;
    }
    
    /**
     * 根据一个 code 生成图片的base64内容，之后可调用 verify() 来验证.
     * 如果同时传递了一个 token 值，则使用它作为 token. 这个token可能是表单token。未传递则生成一个。
     * 
     * @param  string $code
     * @param  string $token
     * @return array  ['base64'=>..., 'token'=>,]
     */
    public function get($code=null, $token=null)
    {
        if (!$code || !DataHelper::verify_code($code)) {
            $code = DataHelper::random(4, false);
        }
        $code = strtoupper($code);
        
        if ($token && DataHelper::is_token($token)) {
            $this->getTokenInstance()->refreshData($token, ['code' => $code]);
        } else {
            $token = $this->getTokenInstance()->get(['code' => $code]);
        }
        
        return [
            //'code'  => $code, // 不要返回，仅用于测试
            'token'     => $token,
            'base64'    => base64_encode($this->getImageHandler()->getBody($code)),
        ];
    }
    
    /**
     * 验证验证码的正确性，如支持 session 模式可以不传递token
     * 
     * @param  string  $token
     * @param  string  $code
     * @return \Wslim\Common\ErrorInfo
     */
    public function verify($token, $code)
    {
        $code = strtoupper($code);
        
        if (!$code || !DataHelper::verify_code($code)) {
            return ErrorInfo::error('请输入图形码');
        }
        
        return $this->getTokenInstance()->verify($token, ['code' => $code]);
    }
    
    /**
     * 重置验证码，用于检验成功后调用
     * @param  string $token
     * @return void
     */
    public function reset($token=null)
    {
        if ($token) {
            $this->getTokenInstance()->reset($token);
        }
    }
    
    /**
     * set options
     * @param  array $options
     * @return \Wslim\Security\Captcha
     */
    public function setOptions(array $options)
    {
        if ($options) {
            foreach ($options as $k => $v) {
                if (in_array($k, ['session'])) {
                    $this->getTokenInstance()->setOptions($k, $v);
                }
            }
            
            $this->getImageHandler()->setOptions($options);
        }
        
        return $this;
    }
    
    /**
     * get image handler
     * @return \Wslim\Security\Captcha\CaptchaImage
     */
    public function getImageHandler()
    {
        if (!$this->imageHandler) {
            $this->imageHandler = new CaptchaImage();
            
            $object = $this->imageHandler;
            $object->width = 90;
            $object->height = 26;
            $object->background = 1;
            $object->adulterate = 1;
            $object->scatter = '';
            $object->color = 1;
            $object->size = 0;
            $object->shadow = 1;
            $object->animator = 0;
        }
        
        return $this->imageHandler;
    }

    
}