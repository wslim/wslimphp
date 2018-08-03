<?php
namespace Wslim\Util;

/**
 * 问题：对于xss的script，只检测函数，会放过一些不安全语句，目前使用 DataHelper::filter_xss()
 * 
 * 来源：
 * 云体检通用漏洞防护补丁v1.1
 * 更新时间：2013-05-25
 * 功能说明：防护XSS,SQL,代码执行，文件包含等多种高危漏洞
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class XssHelper
{
    static protected $url_arr = array(
        'xss' => "\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
    );
    
    static protected $args_arr = array(
        'xss' =>"[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
        'sql' =>"[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
        'other' =>"\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]");
    
    /**
     * check all query data, if safe return empty array, if unsafe return array contain get/post/query_string/cookie/referer
     * 
     * @return array ['get'=>unsafe_str, 'post'=>, 'query_string'=>, 'cookie'=>, 'referer'=>] 
     */
    static public function checkAll()
    {
        $referer = !isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
        $query_string = !isset($_SERVER["QUERY_STRING"]) || empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);
        
        $data = [];
        
        if ($res = static::_checkData($query_string, static::$url_arr)) {
            $data['query_string'] = $res;
        }
        
        if ($res = static::_checkData($_GET, static::$args_arr)) {
            $data['get'] = $res;
        }
        
        if ($res = static::_checkData($_POST, static::$args_arr)) {
            $data['post'] = $res;
        }
        
        if ($res = static::_checkData($_COOKIE, static::$args_arr)) {
            $data['cookie'] = $res;
        }
        
        if ($res = static::_checkData($referer, static::$args_arr)) {
            $data['referer'] = $res;
        }
        
        return $data;
    }
    
    /**
     * check data, if safe return empty array, if unsafe return unsafe value
     * 
     * @return string unsafe value
     */
    static public function check($data)
    {
        return static::_checkData($data, static::$args_arr);
    }
    
    static public function filter($data)
    {
        if (is_array($data)) {
            $res = [];
            foreach ($data as $k => $v) {
                $tk = static::_filterXssString($k, static::$args_arr);
                $tv = static::filter($v);
                $res[$tk] = $tv;
            }
            
            return $res;
        } else {
            return static::_filterXssString($data, static::$args_arr);
        }
    }
    
    /**
     * check data, if safe return null, if has unsafe string return unsafe value
     * 
     * @param  mixed $arr array|string
     * @param  array $v
     * 
     * @return string
     */
    static private function _checkData($arr, $v) 
    {
        foreach($arr as $key=>$value)
        {
            if(!is_array($key)) { 
                if ($res = static::_checkString($key, $v)) {
                    return $res;
                }
            } else { 
                if ($res = static::_checkData($key, $v)) {
                    return $res;
                }
            }
            
            if(!is_array($value)) { 
                if ($res = static::_checkString($value, $v)) {
                    return $res;
                }
            } else { 
                if ($res = static::_checkData($value, $v)) {
                    return $res;
                }
            }
        }
        
        return null;
    }
    
    /**
     * check string, if safe return null, if has unsafe string return $str
     * 
     * @param  string $str
     * @param  array  $v
     * 
     * @return string $str
     */
    static private function _checkString($str,$v)
    {
        foreach($v as $key=>$value)
        {
            if (preg_match("/".$value."/is", $str) == 1 || preg_match("/".$value."/is", urlencode($str)) == 1)
            {
                //W_log("<br>IP: ".$_SERVER["REMOTE_ADDR"]."<br>时间: ".strftime("%Y-%m-%d %H:%M:%S")."<br>页面:".$_SERVER["PHP_SELF"]."<br>提交方式: ".$_SERVER["REQUEST_METHOD"]."<br>提交数据: ".$str);
                return $str;
            }
        }
        
        return null;
    }
    
    static private function _filterXssString($str, $regrex)
    {
        $regrex = (array) $regrex;
        
        foreach($regrex as $key => $value)
        {
            $str = preg_replace("/".$value."/is", '', $str);
            
            if (preg_match("/".$value."/is", urlencode($str)) == 1) {
                $str = preg_replace("/".$value."/is", '', urlencode($str));
            }
        }
        
        return $str;
    }
    
    function W_log($log)
    {
        $logpath=$_SERVER["DOCUMENT_ROOT"]."/log.txt";
        $log_f=fopen($logpath,"a+");
        fputs($log_f,$log."\r\n");
        fclose($log_f);
    }
}

