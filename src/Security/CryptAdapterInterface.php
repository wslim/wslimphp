<?php
namespace Wslim\Security;

/**
 * 加密适配器接口
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface CryptAdapterInterface
{
    
    /**
     * 是否可解密
     * @return boolean
     */
    public function canDecrypt();
    
    /**
     * 加密函数
     * @param  string $input 需要加密字串
     * @param  string $key 密钥
     * @return string 返回加密结果
     */
    public function encrypt($input, $key = '');
    
    /**
     * 解密函数
     * @param  string $entxt 需要解密的字符串
     * @param  string $key 密钥
     * @param  int    $ttl 有效时长，单位为秒，默认0不限制时长
     * @return string 字符串类型的返回结果
     */
    public function decrypt($entxt, $key = '', $ttl = 0);
    
}