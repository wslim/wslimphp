<?php
namespace Wslim\Util;

/**
 * DataHelper, data handle function
 * 
 * 1. filterXXX()   filter methods
 * 2. addslashes(), html_entities()
 * 3. verify_xxx    verify methods
 * 4. formatXxx     format and normarlize methods
 * 5. uuid, random, serial, hash, password  build data
 * 6. toXml, fromXml
 * 7. toTree() toFlat(), tree methods
 * 8. outputXXX
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class DataHelper
{
    
    /***************************************************************************
     * filter methods
     ***************************************************************************/
    
    /**
     * 过滤不安全字符，适用于用户名, url, image, 限制级标题, 关键词等通用型字串安全过滤.
     *
     * @param  string $value
     * @return string
     */
    static public function filter_unsafe_chars($value)
    {
        $value = str_replace('%20','',$value);
        $value = str_replace('%27','',$value);
        $value = str_replace('%2527','',$value);
        $value = str_replace('*','',$value);
        $value = str_replace('"','&quot;',$value);
        $value = str_replace("'",'',$value);
        $value = str_replace('"','',$value);
        $value = str_replace(';','',$value);
        $value = str_replace('<','&lt;',$value);
        $value = str_replace('>','&gt;',$value);
        $value = str_replace("{",'',$value);
        $value = str_replace('}','',$value);
        $value = str_replace('\\','',$value);
        return $value;
    }
    
    /**
     * 过滤ASCII码从0-28的控制字符，适用于通用型字串过滤.
     * @return String
     */
    static public function filter_control_chars($value) {
        $rule = '/[' . chr ( 1 ) . '-' . chr ( 8 ) . chr ( 11 ) . '-' . chr ( 12 ) . chr ( 14 ) . '-' . chr ( 31 ) . ']*/';
        return str_replace ( chr ( 0 ), '', preg_replace ( $rule, '', $value ) );
    }
    
    /**
     * xss过滤函数，过滤掉脚本等相关的代码，适用于允许html内容但不支持脚本运行的类型.
     * 应用场合：长文本的过滤
     *
     * @param  string $value
     * @return string
     */
    static public function filter_xss($value) 
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $value);
        // title
        $parm1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'base'); 
        $parm2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $parm = array_merge($parm1, $parm2);
        
        for ($i = 0; $i < sizeof($parm); $i++) {
            $pattern = '';
            for ($j = 0; $j < strlen($parm[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                    $pattern .= '|(&#0([9][10][13]);?)?';
                    $pattern .= ')?';
                }
                $pattern .= $parm[$i][$j];
            }
            $pattern = '/\<[^\>]*' . $pattern . '[^\>]*\>/i';
            $value = preg_replace($pattern, ' ', $value);
        }
        
        return $value;
    }
    
    /**
     * filter html
     * @param  string $value
     * @return string
     */
    static public function filter_html($value)
    {
        $value = strip_tags(static::html_entity_decode($value));
        
        return $value;
    }
    
    /***************************************************************************
     * 转义相关方法
     ***************************************************************************/
    /**
     * 转义 javascript 代码标记，适用于不允许html内容的类型.
     * 应用场合：对于url,callback等类型值使用
     *
     * @param  string|array $value
     * @return mixed
     */
    static public function escape_script($value) 
    {
        if(is_array($value)){
            foreach ($value as $key => $val){
                $value[$key] = escape_script($val);
            }
        }else{
            $value = preg_replace ( '/\<([\/]?)script([^\>]*?)\>/si', '&lt;\\1script\\2&gt;', $value );
            $value = preg_replace ( '/\<([\/]?)iframe([^\>]*?)\>/si', '&lt;\\1iframe\\2&gt;', $value );
            $value = preg_replace ( '/\<([\/]?)frame([^\>]*?)\>/si', '&lt;\\1frame\\2&gt;', $value );
            $value = str_replace ( 'javascript:', 'javascript：', $value );
        }
        return $value;
    }
    
    /**
     * 转义引号，支持数组
     * @param  string|array $value
     * @return mixed
     */
    static public function addslashes($value, $force = 0)
    {
        if(!get_magic_quotes_gpc() || $force) {
            if(is_array($value)) {
                foreach($value as $key => $val) {
                    $value[$key] = static::addslashes($val, $force);
                }
            } elseif (is_string($value)) {
                $value = addslashes($value);
            }
        }
        return $value;
    }
    
    /**
     * 去除转义引号，支持数组
     * @param string|array $value
     * @return mixed
     */
    static public function stripslashes($value) 
    {
        if(!is_array($value)) {
            return stripslashes($value);
        }
        foreach($value as $key => $val) {
            $value[$key] = static::stripslashes($val);
        }
        return $value;
    }
    
    /**
     * html entities
     * @param  string $value
     * @return string
     */
    static public function html_entities($value) 
    {
        $encoding = 'utf-8';
        //if(strtolower(CHARSET)=='gbk') $encoding = 'ISO-8859-15';
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::html_entities($v);
            }
        } else {
            // 第二个参数 ENT_QUOTES 表示单双引号都转义
            $value = htmlspecialchars($value, ENT_QUOTES, $encoding);
        }
        return $value;
    }
    
    /**
     * 反转义html字符
     * @param  string $value
     * @return string
     */
    static public function html_entity_decode($value) {
        $encoding = 'utf-8';
        //return html_entity_decode($value, ENT_QUOTES, $encoding);
        return htmlspecialchars_decode($value, ENT_QUOTES);
    }
    
    /**
     * sql string encode
     * @param  string $value
     * @return string
     */
    static public function sql_encode($value)
    {
        $value = str_replace("_", "\_", $value); // 把 '_'过滤掉
        $value = str_replace("%", "\%", $value); // 把 '%'过滤掉
        
        return $value;
    }
    
    /***************************************************************************
     * 验证类相关方法 verify_xxx()
     ***************************************************************************/
    /**
     * 进行regex验证
     * @param string $regex
     * @param string $value
     * @param string $option
     * @return boolean
     */
    static public function verify_regex($regex, $value, $option='')
    {
        return (bool) preg_match('/' . $regex . '/' . $option, $value);
    }
    
    /**
     * 验证标识符，仅允许字母数字下划线和反斜线
     * @param  string $value
     * @return boolean
     */
    static public function verify_identifier($value)
    {
        $regex = '^[a-z0-9_\/]+$';
        return static::verify_regex($regex, $value, 'i');
    }
    
    /**
     * verify dirname, [a-z0-9_-]
     * @param  string $value
     * @return boolean
     */
    static public function verify_dirname($value)
    {
        $regex = '^[a-z0-9_\-]+$';
        return static::verify_regex($regex, $value, 'i');
    }
    
    /**
     * 验证标识名称，仅允许字母、数字、下划线、横线、反斜线、冒号
     * @param  string $value
     * @return boolean
     */
    static public function verify_code($value)
    {
        $regex = '^[a-z0-9_\/\-\:]+$';
        return static::verify_regex($regex, $value, 'i');
    }
    
    /**
     * is token format, only english character
     * @param  string $value
     * @return boolean
     */
    static public function is_token($value)
    {
        $regex = '^[a-z0-9_\-\:\.]+$';
        return static::verify_regex($regex, $value, 'i');
    }
    
    /**
     * verify mobile, format ok return true
     * @param  string $value
     * @return boolean
     */
    static public function verify_mobile($value)
    {
        $regex = '^(\+(86)?)?[0-9]+$';
        $regex = '^[1][3-9]([0-9]{9})';
        return static::verify_regex($regex, $value, 'i');
    }
    
    /**
     * is mobile, alias verify_mobile
     * @param  string $value
     * @return boolean
     */
    static public function is_mobile($value)
    {
        return static::verify_mobile($value);
    }
    
    /**
     * verify email, format ok return true
     * @param  string $value
     * @return boolean
     */
    static public function verify_email($value)
    {
        //$regex = '^[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$';
        $regex = '^[a-zA-Z0-9.!#$%&_~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$';
        return static::verify_regex($regex, $value);
    }
    
    /**
     * is email, alias verify_email
     * @param  string $value
     * @return boolean
     */
    static public function is_email($value)
    {
        return static::verify_email($value);
    }
    
    /**
     * verify username, [a-z0-9_-@], format ok return true
     * @param  string $value
     * @return boolean
     */
    static public function verify_username($value)
    {
        if (is_numeric($value)) {
            return true;
        } elseif (static::verify_email($value) ) {
            return true;
        } else {
            $regex = "^[a-z0-9\!\@\#\$\%\&\*\_\-\=]+$";
            return static::verify_regex($regex, $value, 'i');
        }
    }
    
    /**
     * 进行sql验证
     * @param  string $value
     * @return boolean
     */
    static public function verify_sql_inject($value)
    {
        $regex = 'select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile';
        return ! static::verify_regex($regex, $value);
    }
    
    /***************************************************************************
     * format methods
     ***************************************************************************/
    /**
     * format number
     * @param  string $value
     * @return string
     */
    static public function formatNumber($value)
    {
        return preg_replace('/[^0-9]+/S', '', $value);
    }
    
    /**
     * format code, only allow a-zA-Z0-9_\/\-\:
     * @param  string $value
     * @return string
     */
    static public function formatCode($value)
    {
        return preg_replace('/[^a-zA-Z0-9_\/\-\:]+/S', '', $value);
    }
    
    /**
     * format dirname[a-zA-Z0-9_\-]
     * @param  string $value
     * @return string
     */
    static public function formatDirname($value)
    {
        return preg_replace('/[^a-zA-Z0-9_\-]+/S', '', $value);
    }
    
    /**
     * format url, do urldecode and filter unsafe chars
     * @param  string  $value
     * @return string
     */
    static public function formatUrl($value)
    {
        $value = urldecode($value);
        return static::escape_script(static::filter_unsafe_chars($value));
    }
    
    /**
     * format filesystem path
     * @param  string $value
     * @return string
     */
    static public function formatPath($value)
    {
        $value = preg_replace('/\s+/u', '_', $value);
        $value = preg_replace('/[^a-z0-9\.\:\-\_\s\/\\\\\x{4e00}-\x{9fa5}]/iu', '', $value);
        $value = preg_replace('/\/{2,}/', '/', $value);
        $value = preg_replace('/(\.\/?){2,}/', '', $value);
        return $value;
    }
    
    /**
     * format search text, do filter xss and sql encode
     * @param  string $value
     * @return string
     */
    static public function formatSearch($value)
    {
        $value = static::filter_xss($value);
        $value = static::html_entities($value);
        $value = str_replace('&lt;', '', $value);
        $value = str_replace('&gt;', '', $value);
        $value = static::sql_encode($value);
        $value = trim($value);
        
        return $value;
    }
    
    /**
     * format text
     * @param  string $value
     * @return string
     */
    static public function formatText($value)
    {
        $value = static::filter_xss($value);
        $value = static::sql_encode($value);
        $value = static::filter_html($value);
        
        return $value;
    }
    
    /**
     * serialize
     * @param  mixed  $value
     * @return string
     */
    static public function serialize($value)
    {
        return isset($value) && !is_string($value) ? serialize($value) : $value;
    }
    
    /**
     * unserialize
     * @param  string $value
     * @return mixed
     */
    static public function unserialize($value=null)
    {
        return unserialize($value);
    }
    
    /**
     * json_encode
     * @param  mixed  $value
     * @return string
     */
    static public function json_encode($value)
    {
        return isset($value) ? json_encode($value) : $value;
    }
    
    /**
     * json_decode
     * @param  string  $value
     * @return array
     */
    static public function json_decode($value)
    {
        return $value ? (json_decode($value, true) ? : $value ): null;
    }
    
    /**
     * urlencode 编码处理
     * @param  array|string $value
     * @return array|string
     */
    static public function urlencode($value)
    {
        if (is_string($value)) {
            return urlencode($value);
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::urlencode($v);
            }
        }
        return $value;
    }
    
    /**
     * urldecode 解码处理
     * @param  array|string $value
     * @return array|string
     */
    static public function urldecode($value)
    {
        if (is_string($value)) {
            return urldecode($value);
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::urldecode($v);
            }
        }
        return $value;
    }
    
    /**
     * from str to unicode format \uAAAA
     * @param  string $name
     * @param  string $prefix
     * @param  string $parse_alpha
     * @param  string $exclude_str
     * @return string
     */
    function unicode_encode($value, $prefix='\u', $parse_alpha=true, $exclude_str=null)
    {
        if ($prefix) {
            if (strpos($prefix, '\\') !== 0) {
                $prefix = '\\' . $prefix;
            }
            
            if ($prefix !== '\\u' && $prefix !== '\\x') {
                $prefix = '\\u';
            }
        }
        $value = iconv('UTF-8', 'UCS-2', $value);
        $len = strlen($value);
        $newStr = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2)
        {
            $c  = $value[$i];
            $c2 = $value[$i + 1];
            if (ord($c) > 0)
            {    // 两个字节的文字
                $newStr .= '\\u' . strtoupper(base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16));
            }
            else
            {
                if ($exclude_str && strpos($exclude_str, $c2) !== false) {
                    $newStr .= $c2;
                } else {
                    if ($prefix == '\\u') {
                        $newStr .= $parse_alpha ? '\\u00' . strtoupper(bin2hex($c2)) : $c2;
                    } else {
                        $newStr .= $parse_alpha ? '\\x' . strtoupper(bin2hex($c2)) : $c2;
                    }
                }
            }
        }
        return $newStr;
    }
    
    /**
     * 转换编码，将Unicode编码转换成可以浏览的utf-8编码
     * @param  string $value
     * @return string
     */
    function unicode_decode($value)
    {
        
        $pattern = '/(?:\\\u(?:[\w]{4}))|(?:\\\x(?:[\w]{2}))|./i';
        preg_match_all($pattern, $value, $matches);
        if (!empty($matches))
        {
            $value = '';
            for ($j = 0; $j < count($matches[0]); $j++)
            {
                $unistr = $matches[0][$j];
                if (strpos($unistr, '\\u') === 0)
                {
                    $code = base_convert(substr($unistr, 2, 2), 16, 10);
                    $code2 = base_convert(substr($unistr, 4), 16, 10);
                    $c = chr($code).chr($code2);
                    $c = iconv('UCS-2', 'UTF-8', $c);
                    $value .= $c;
                }
                elseif (strpos($unistr, '\\x') === 0)
                {
                    $code = base_convert(substr($unistr, 2, 1), 16, 10);
                    $code2 = base_convert(substr($unistr, 3), 16, 10);
                    if ($code > 7) {
                        $c = chr($code).chr($code2);
                        $c = iconv('UCS-2', 'UTF-8', $c);
                    } else {
                        $c = chr(hexdec(substr($unistr, 2, 2)));
                    }
                    $value .= $c;
                    /**/
                    
                    /*
                     $code = hexdec(substr($value, 2, 2));
                     $c = chr($code);
                     $name .= $c;
                     */
                }
                else
                {
                    $value .= $unistr;
                }
            }
        }
        return $value;
    }
    
    /**
     * auto detect encoding and transform UTF-8, 自动判断文字转换为 UTF-8格式
     * @param  string $value
     * @return string
     */
    static public function toUtf8($value)
    {
        $encoding = mb_detect_encoding($value);
        if ($encoding !== 'UTF-8') {
            return iconv($encoding, 'UTF-8', $value);
        }
        
        return $value;
    }
    
    /***************************************************************************
     * uuid, random, serial, hash, password
     ***************************************************************************/
    /**
     * get uuid '315B817C-11F5-718F-F7BD-95E0E95519D0'
     * 
     * @param  boolean $separator if true then contain '-'
     * @return string
     */
    static public function uuid($separator=true) 
    {
        return IdMaker::uuid($separator);
    }
    
    /**
     * get 16 length bitint id
     * @return number
     */
    static public function bigId()
    {
        return IdMaker::bigId();
    }
    
    /**
     * get random
     * @param  int     $length
     * @param  boolean $is_numeric 是否仅取数字
     * @param  boolean $case 区分大小写
     * @return string
     */
    static public function random($length=32, $is_numeric = false, $case=false)
    {
        $mask = "012346789ABCDEFGHIGKLMNOPQRSTUVWXYZ";
        $chars = $is_numeric ? "0123456789" : ($case ? "abcdefghijklmnopqrstuvwxyz" . $mask : $mask);
        $value = "";
        for ( $i = 0; $i < $length; $i++ )  {
            $value .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $value;
    }
    
    /**
     * random number
     * @param  int    $length
     * @return string
     */
    static public function randomNumber($length=32)
    {
        return static::random($length, 1);
    }
    
    /**
     * random string
     * @param  int    $length
     * @return string
     */
    static public function randomString($length=32)
    {
        return static::random($length, 0, false);
    }
    
    /**
     * random 128 string
     * @param  int    $length
     * @return string
     */
    static public function randomString128()
    {
        return static::random(128, 0, false);
    }
    
    /**
     * random case string 区分大小写
     * @param  int    $length
     * @return string
     */
    static public function randomCaseString($length=32)
    {
        $pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        
        return substr(str_shuffle(str_repeat($pool, ceil($length / strlen($pool)))), 0, $length);
        
        return static::random($length, 0, true);
    }
    
    /**
     * serial no with date format, 生成日期格式的流水号，最少位数为20
     * @param  int    $length
     * @param  string $prefix
     * @return string
     */
    static public function serial($length=20, $prefix=null)
    {
        if ($length < 20) $length = 20;
        $mtime = floor(microtime(true) * 1000);
        $value = date('YmdHis') . substr($mtime, strlen($mtime)-3); // 14bit + 3bit micortime
        $value .= static::randomNumber($length-17);
        if ($prefix) $value = $prefix . $value;
        
        return $value;
    }
    
    /**
     * serial no with timestamp format, 生成时间戳格式的流水号，最少位数为16
     * @param  int    $length
     * @param  string $prefix
     * @return string
     */
    static public function serialTimestamp($length=16, $prefix=null)
    {
        if ($length < 16) $length = 16;
        $value = floor(microtime(true) * 1000); // 13bit
        $value .= static::randomNumber($length-13);
        if ($prefix) $value = $prefix . $value;
        
        return $value;
    }
    
    /**
     * return password from input, salt
     * @param string $input
     * @param string $salt
     * @return string
     */
    static public function password($input, $salt = '')
    {
        return strtolower(substr(md5(md5($input) . $salt), 0, 32));
    }
    
    /**
     * to object
     * @param  mixed $data
     * @return \stdClass
     */
    static public function toObject($data)
    {
        if (is_object($data)) {
            return $data;
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return json_decode($json);
    }
    
    /**
     * from xml str to array
     * @param  string $value
     * @return array
     */
    static public function fromXml($value)
    {
        return XmlHelper::decode($value);
    }
    
    /**
     * from string to SimpleXMLElement
     * @param  string|array $data
     * @return \SimpleXMLElement
     */
    static public function toXml($data)
    {
        return XmlHelper::toXml($data);
    }
    
    /**
     * to xml string
     * @param  string|array $data
     * @return string
     */
    static public function toXmlString($data)
    {
        return XmlHelper::encode($data);
    }
    
    /**
     * to tree 多维层次树
     * @param  array $data
     * @param  array $fields
     * @return array
     */
    static public function toTree($data, $fields=['id', 'parent_id'])
    {
        $treeHandler = new Tree($data, $fields);
        return $treeHandler->tree();
    }
    
    /**
     * to tree flat 一维平面树
     * @param  array $data
     * @param  array $fields
     * @param  int   $root_id
     * @return array
     */
    static public function toFlat($data, $fields=['id', 'parent_id'], $root_id=null)
    {
        $treeHandler = new Tree($data, $fields);
        return $treeHandler->flat($root_id);
    }
    
    /**
     * from array to js raw 转换数组为js 格式输出, 可以复制或写入到js文件中
     * 
     * @param array $data
     * @param bool  $stripslashes 是否 stripslashes 处理
     * 
     * @return string
     */
    static public function toJsRaw($data, $stripslashes=true, $level=0) 
    {
        $str = $data;
        
        if (is_array($data)) {
            $space = $level ? str_pad(' ', $level * 4) : '';
            $bstr  = $space . '{';
            $estr  = $space . '}';
            $str   = '';
            
            if($stripslashes) {
                $data = static::stripslashes($data);
            }
            
            foreach ($data as $key=>$val) {
                if (is_numeric($key)) {
                    $bstr = $space . '[';
                    $estr = $space . ']';
                } else {
                    $str .= $space . "    \"$key\":";
                }
                
                if (is_numeric($val)) {
                    $str .= $val;
                } elseif (is_array($val)) {
                    $str .= static::toJsRaw($val, false, $level+1);
                } else {
                    $str .= "\"$val\"";
                }
                
                $str .= ',' . PHP_EOL;
            }
            
            $str = $bstr . PHP_EOL . rtrim($str, ',' . PHP_EOL) . PHP_EOL . $estr;
        }
        
        return $str;
    }
    
    /**
     * getTimestamp
     * @return int
     */
    static public function getTimestamp()
    {
        return (string) time();
    }
    
    /**
     * to date like '2010-01-01'
     * @param  mixed  $value
     * @return string
     */
    static public function toDate($value=null)
    {
        if (!$value) {
            return date('Y-m-d');
        } else {
            if(is_numeric($value)) {
                // strftime format is different from date()
                //$value = strftime('%Y-%m-%d %H:%M:%S', $value);
                $value = strftime('%Y-%m-%d', $value);
            }
            
            return $value;
        }
    }
    
    /**
     * to unixtime
     * @param  string|int $value
     * @return int        $timestamp
     */
    static public function toUnixtime($value=null)
    {
        return (empty($value)) ? time() : (is_numeric($value) ? $value : strtotime($value));
    }
    
    /**
     * from unixtime to string '2017-01-01 12:00:00'
     * @param  string|int $timestamp
     * @return int        $timestamp
     */
    static public function fromUnixtime($timestamp=null)
    {
        $timestamp || $timestamp = time();
        if(is_numeric($timestamp)) {
            // strftime format is different from date()
            //$value = strftime('%Y-%m-%d %H:%M:%S', $value);
            $timestamp = strftime('%Y-%m-%d %H:%M:%S', $timestamp);
        }
        return $timestamp;
    }
    
    /**
     * to datetime, use strftime(), not support chinese.
     * @param  string|int $value
     * @param  string $format '%Y-%m-%d %H:%M:%S'
     * @return string
     */
    static public function datetime($value=null, $format=null)
    {
        $value = static::toUnixtime($value);
        if (!$format) {
            $value = strftime('%Y-%m-%d %H:%M:%S', $value);
        } else {
            if (strpos($format, '%') === false) {
                $format = preg_replace('/([a-z])/i', '%\\1', $format);
            }
            $value = strftime($format, $value);
        }
        return $value;
    }
    
    /**
     * implode
     * @param  string $glue
     * @param  string|array $data
     * @return string
     */
    static public function implode($glue, $data)
    {
        return StringHelper::implode($glue, $data);
    }
    
    /**
     * explode use preg_split, same as StringHelper::explode()
     * 
     * @param  string $glue
     * @param  mixed  $data string|array
     * @param  int    $limit
     * @return array
     */
    static public function explode($glue, $data, $limit=null)
    {
        return StringHelper::explode($glue, $data, $limit);
    }
    
    /**
     * extract array by keys
     * @param  array $data
     * @param  mixed $keys array or string
     * @return array
     */
    static public function extract($data, $keys=null)
    {
        if ($data) {
            if ($keys) {
                $list = [];
                $keys = static::explode('\,\|', $keys);
                foreach ($keys as $k) {
                    $list[] = isset($data[$k]) ? $data[$k] : null;
                }
                return $list;
            }
        }
        return $data;
    }
    
    /**
     * mask mobile, from '18012340000' to '180****0000'
     * @param  string $mobile
     * @return string
     */
    static public function maskMobile($mobile)
    {
        if ($mobile) {
            $mobile = substr($mobile, 0, 3) . '****' . substr($mobile, 7, 4);
        }
        return $mobile;
    }
    
    static public function toKeywordsArray($keywords)
    {
        if (is_scalar($keywords)) {
            $keywords = static::explode('\s', $keywords);
        }
        
        foreach ($keywords as $k => $v) {
            $v = static::filter_html($v);
            if (!$v) {
                unset($keywords[$k]);
            }
        }
        return $keywords;
    }
    
}