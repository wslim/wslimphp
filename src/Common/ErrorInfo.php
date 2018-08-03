<?php
namespace Wslim\Common;

/**
 * ErrorInfo, ['errcode'=>.., 'errmsg'=>.., 'datakey'=>..,]
 * errcode=0 success, <>0 is error
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class ErrorInfo extends Collection
{
    // common
    const SUCCESS               = 0;
    const ERROR                 = -1;
    
    // system level 100-200
    const ERROR_SYSTEM          = -101;       //系统错误
    
    // data -3001~3999
    const ERROR_INPUT_DATA      = -3001; //输入有误，请重新输入
    const ERROR_UNKNOW_TYPE     = -3002; //未知类型错误
    const ERROR_CAPTCHA_ERROR   = -3003; //验证码错误
    const ERROR_REQUIRED_FIELDS = -3004; //必填项未填写全
    
    /**
     * token invalid
     * @var int -20001
     */
    const ERR_TOKEN_INVALID     = -20001;
    /**
     * token expired
     * @var int -20002
     */
    const ERR_TOKEN_EXPIRED     = -20002;
    /**
     * token data error
     * @var int -20003
     */
    const ERR_TOKEN_DATA        = -20003;
    
    /**
     * @var int
     */
    const ERROR_HAS_CHILD       = -3005;
    
    /**
     * parent is self
     * @var int
     */
    const ERROR_RECURSIVE_SELF    = -3006;
    const ERROR_NOT_SELECT_DATA   = -3007;
    const ERROR_NOT_SET_DATA      = -3008;
    const ERROR_NOT_FOUND_PK      = -3009;
    
    
    static public $messages = [
        0       => 'SUCCESS',
        -1      => 'FAIL',
        -2      => '输入数据不正确',
        -101    => '系统错误',
        -3001   => '输入有误，请重新输入',
        -3002   => '未知类型错误',
        -3003   => '验证码错误',
        -3004   => '必填项未填写全',
        -3005   => '不允许删除，请先删除子条目',
        -3006   => '上级条目不能是自身',
        -3007   => '未选择所属的条目',
        -3008   => '未设置相关数据',
        -3009   => '未找到主键',
    ];
    
    /**
     * get instance, can call toArray() or toJson() to needed data type
     * @param  mixed  $errcode
     * @param  mixed  $errmsg if int as errcode, if array as data 不设置该参数则使用内置信息
     * @param  array  $data
     * @return static
     */
    static public function instance($errcode=0, $errmsg=null, $data=null)
    {
        if ($errcode instanceof ErrorInfo) {
            return $errcode->setErrorInfo(null, $errmsg, $data);
        } else {
            return new static($errcode, $errmsg, $data);
        }
    }
    
    /**
     * success instance
     * 
     * @param  mixed  $errmsg string|array if array as data
     * @param  array  $data
     * @return static
     */
    static public function success($errmsg=null, $data=null)
    {
        if (is_array($errmsg) || $errmsg instanceof Collection ) {
            $object = static::instance(0, null, $errmsg);
            $data && $object->set($data);
        } else {
            $object = static::instance(0, $errmsg, $data);
        }
        
        return $object->set('errcode', 0);
    }
    
    /**
     * error instance
     * 
     * @param  mixed  $errcode
     * @param  mixed  $errmsg if number as errcode, if array as data 不设置该参数则使用内置的信息
     * @param  array  $data
     * @return static
     */
    static public function error($errcode = -1, $errmsg=null, $data=null)
    {
        $object = static::instance($errcode, $errmsg, $data);
        if (!$object->get('errcode')) {
            $object->set('errcode', -1);
        }
        return $object;
    }
    
    /**
     * construct
     * 
     * @param int    $errcode
     * @param string $errmsg
     * @param array  $data
     */
    public function __construct($errcode=null, $errmsg=null, $data=null)
    {
        $this->setErrorInfo($errcode, $errmsg, $data);
    }
    
    /**
     * 
     * @param int    $errcode
     * @param string $errmsg
     * @param array  $data
     * 
     * @return static
     */
    public function setErrorInfo($errcode=null, $errmsg=null, $data=null)
    {
        if ($data) {
            $this->set($data);
        }
        
        if ($errmsg) {
            if (!is_scalar($errmsg)) {
                $this->set($errmsg);
            } elseif (is_numeric($errmsg) || is_bool($errmsg)) {
                $this->set('errcode', $errmsg);
            } else {
                $this->set('errmsg', $errmsg);
            }
        }
        
        if ($errcode) {
            if (!is_scalar($errcode)) {
                $this->set($errcode);
            } elseif (is_numeric($errcode)) {
                $this->set('errcode', $errcode);
            } elseif (is_bool($errcode)) {
                $this->set('errcode', $errcode === true ? 0 : -1);
            } else {
                $this->set('errmsg', $errcode);
            }
        }
        
        if (!$this->has('errcode')) {
            $this->set('errcode', 0);
        }
        if (!$this->has('errmsg')) {
            $this->set('errmsg', isset(static::$messages[static::get('errcode')]) ? static::$messages[static::get('errcode')] : 'Unknown error.');
        }
        
        return $this;
    }
    
    /**
     * get errmsg 
     * @return string
     */
    public function getErrmsg()
    {
        return $this['errmsg'] ? : 'undefined error.';
    }
    
    /**
     * is success
     * @return bool
     */
    public function isSuccess()
    {
        return $this['errcode'] == 0;
    }
    
    /**
     * is error
     * @return bool
     */
    public function isError()
    {
        return $this['errcode'] != 0;
    }
    
    /**
     * return ErrerInfo array 
     * @return array
     */
    public function toArray()
    {
        return static::all();
    }
    
    /**
     * return ErrerInfo json string
     * @return string
     */
    public function toJson()
    {
        return json_encode(static::all());
    }
    
    /**
     * to string
     * @return string
     */
    public function toString()
    {
        return 'errcode:' . $this['errcode'] . ', errmsg:' . $this['errmsg'];
    }
    
}
