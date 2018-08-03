<?php
namespace Wslim\Security\Crypt;

use Wslim\Security\CryptAdapterInterface;

/**
 * default crypt adapter
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class DefaultAdapter implements CryptAdapterInterface
{
    /**
     * 默认加解密使用的 key
     * @var string
     */
    private $key = 'wslimphp';
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Security\CryptAdapterInterface::canDecrypt()
     */
    public function canDecrypt()
    {
        return true;
    }
    
    /**
     * 加密函数
     * @param string $input 需要加密的字符串
     * @param string $key 密钥
     * @return string 返回加密结果
     */
    public function encrypt($input, $key = '')
    {
        if (empty($input)) return $input;
        if (empty($key)) $key = md5($this->key);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $nh1 = rand(0,64);
        $nh2 = rand(0,64);
        $nh3 = rand(0,64);
        $ch1 = $chars{$nh1};
        $ch2 = $chars{$nh2};
        $ch3 = $chars{$nh3};
        $nhnum = $nh1 + $nh2 + $nh3;
        $knum = 0;$i = 0;
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum%8,$knum%8 + 16);
        $input = base64_encode(time().'_'.$input);
        $input = str_replace(array('+','/','='),array('-','_','.'),$input);
        $tmp = '';
        $j=0;$k = 0;
        $tlen = strlen($input);
        $klen = strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = ($nhnum+strpos($chars,$input{$i})+ord($mdKey{$k++}))%64;
            $tmp .= $chars{$j};
        }
        $tmplen = strlen($tmp);
        $tmp = substr_replace($tmp,$ch3,$nh2 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch2,$nh1 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch1,$knum % ++$tmplen,0);
        return $tmp;
    }
    
    /**
     * 解密函数
     * @param  string $ciphertxt 需要解密的字符串
     * @param  string $key 密钥
     * @param  int    $ttl 有效时长，单位为秒，默认0不限制时长
     * @return string 字符串类型的返回结果
     */
    public function decrypt($ciphertxt, $key = '', $ttl = 0)
    {
        if (empty($ciphertxt)) return $ciphertxt;
        if (empty($key)) $key = md5($this->key);
    
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $knum = 0;$i = 0;
        $tlen = @strlen($ciphertxt);
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $ch1 = @$ciphertxt{$knum % $tlen};
        $nh1 = strpos($chars,$ch1);
        $ciphertxt = @substr_replace($ciphertxt,'',$knum % $tlen--,1);
        $ch2 = @$ciphertxt{$nh1 % $tlen};
        $nh2 = @strpos($chars,$ch2);
        $ciphertxt = @substr_replace($ciphertxt,'',$nh1 % $tlen--,1);
        $ch3 = @$ciphertxt{$nh2 % $tlen};
        $nh3 = @strpos($chars,$ch3);
        $ciphertxt = @substr_replace($ciphertxt,'',$nh2 % $tlen--,1);
        $nhnum = $nh1 + $nh2 + $nh3;
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum % 8,$knum % 8 + 16);
        $tmp = '';
        $j=0; $k = 0;
        $tlen = @strlen($ciphertxt);
        $klen = @strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = strpos($chars,$ciphertxt{$i})-$nhnum - ord($mdKey{$k++});
            while ($j<0) $j+=64;
            $tmp .= $chars{$j};
        }
        $tmp = str_replace(array('-','_','.'), array('+','/','='), $tmp);
        $tmp = trim(base64_decode($tmp));
        
        if (preg_match("/\d{10}_/s", substr($tmp,0,11))){
            if ($ttl > 0 && (time() - substr($tmp,0,11) > $ttl)) {
                $tmp = null;
            }else{
                $tmp = substr($tmp, 11);
            }
        }
        return $tmp;
    }
}