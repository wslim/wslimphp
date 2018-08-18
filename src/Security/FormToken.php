<?php
namespace Wslim\Security;

use Wslim\Common\ErrorInfo;

/**
 * FormToken, 表单token。
 *
 * get($data)   获取token
 * verify($token, $data) 验证token
 * reset($token)
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FormToken extends Token
{
    /**
     * options
     * @var array
     */
    protected $options = [
        'name'      => 'form_token',
        'expire'    => 30 * 60, // 不要太小，发文等有些操作可能时间较长
        'form_name' => '_form_token',
    ];
    
    /**
     * get form name
     * @return string
     */
    public function getDefaultFormName()
    {
        return isset($this->options['form_name']) ? $this->options['form_name'] : '_form_token';
    }
    
    /**
     * form html
     * @param  string $form_name
     * @param  mixed  $data
     * @return string
     */
    public function form($form_name=null, $data=null)
    {
        $form_name || $form_name = $this->getDefaultFormName();
        
        $token = $this->get($data);
        
        return '<input type="hidden" name="' . $form_name . '" value="'. $token . '" />';
    }
    
    /**
     * verify token, after it please call reset()
     * @param  string $token
     * @param  mixed  $data  verify data
     * @return \Wslim\Common\ErrorInfo
     */
    public function verify($token, $data=null)
    {
        $form_name = $this->getDefaultFormName();
        $token || $token = isset($_POST[$form_name]) ? $_POST[$form_name] : (isset($_GET[$form_name]) ? $_GET[$form_name] : null);
        
        if (!$token) {
            return ErrorInfo::error('需要 form_token 参数');
        }
        
        return parent::verify($token, $data);
    }
    
    /**
     * reset, need call after handle business successfully.
     * @param  string $token
     * @param  mixed  $data
     * @return void
     */
    public function reset($token=null, $data=null)
    {
        $form_name = $this->getDefaultFormName();
        $token || $token = isset($_POST[$form_name]) ? $_POST[$form_name] : (isset($_GET[$form_name]) ? $_GET[$form_name] : null);
        
        parent::reset($token, $data);
    }
    
}
