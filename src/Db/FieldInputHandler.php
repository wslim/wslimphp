<?php
namespace Wslim\Db;

use Wslim\Util\StringHelper;
use Wslim\Util\DataHelper;
use Wslim\Common\Config;

/**
 * FieldInputHandler 格式化字段的输入，用于保存数据前的数据转换. 
 * 注1：数据的`addslashes()`或`escape_string` 机制不在此处完成，而由`Db/Parser`来完成。 
 * 注2：基本原则，非editor的文本一律进行转义，以防止xss；editor 的进行 `xss` 过滤
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FieldInputHandler
{
    /**
     * 格式化字段值，按以下顺序尝试: data_format|field_name|base_type|data_type
     * @param mixed  $field
     * @param string $value
     * @param string
     */
    static public function formatValue($field, $value=null)
    {
        $methods = [];
        if (isset($field['data_format']) && $field['data_format']) {
            $methods[] = $field['data_format'];
        }
        $methods[] = $field['field_name'];
        if (isset($field['base_type']) && $field['base_type']) {
            $methods[] = $field['base_type'];
        }
        $methods[] = $field['data_type'];
        
        foreach ($methods as $m) {
            if (method_exists(get_called_class(), $m)) {
                return static::$m($value);
            }
        }
        
        $data_type = strtolower($field['data_type']);
        if (preg_match('/int/', $data_type)) {
            $value = intval($value);
        } elseif (preg_match('/decimal/', $data_type)) {
            $precision = isset($field['precision']) ? $field['precision'] : 6;
            $value = static::decimal($value, $precision);
        } elseif (preg_match('/text/', $data_type)) {
            $value = static::text($value);
        } else {
            $value = static::input($value);
        }
        
        return $value;
    }
    
    static public function decimal($value, $precision=6)
    {
        return round($value, $precision);;
    }
    
    static private function _common($value)
    {
        if (!is_scalar($value)) {
            $value = DataHelper::json_encode($value);
        }
        
        return trim($value);
    }
    
    /**
     * 默认格式化方法，默认字段值进行 html_encode从而使输出为原样输出（html输出为源代码），editor内容不做html_encode，允许有html标签
     * @param  mixed $value
     * @return mixed string|number|float
     */
    static public function input($value)
    {
        $value = DataHelper::html_entities($value);

        return static::_common($value);
    }
    
    /**
     * string
     * @param  string $value
     * @return string
     */
    static public function string($value=null)
    {
        return static::input($value);
    }
    
    /**
     * editor string, needn't html_entities
     * 
     * @param  string $value
     * @return string
     */
    static public function editor($value=null)
    {
        if ($value) {
            //$value = DataHelper::filter_xss($value);
            //$value = XssHelper::filter($value); // 对于xss只检测函数，会放过一些不安全语句
        }
        
        // replace 回车13 换行10
        $value = str_replace([chr(13).chr(10), chr(13), chr(10)], '<br>', $value);
        
        return static::_common($value);
    }
    
    /**
     * text, alias editor
     *
     * @param  string $value
     * @return string
     */
    static public function text($value=null)
    {
        return static::editor($value);
    }
    
    /**
     * format name type
     * @param  string $value
     * @return string
     */
    static public function name($value)
    {
        return DataHelper::formatCode($value);
    }
    
    /**
     * format code type 
     * @param  string $value
     * @return string
     */
    static public function code($value)
    {
        return DataHelper::formatCode($value);
    }
    
    /**
     * mobile
     * @param  string $value
     * @return string
     */
    static public function mobile($value)
    {
        $value = str_replace('+86', '', $value);
        return DataHelper::formatNumber($value);
    }
    
    /**
     * datetime 类型格式化方法，为空使用当前时间戳
     * @param  string $value, if string it must be '2017-01-02 12:00:00'
     * @return int
     */
    static public function datetime($value=null)
    {
        return static::create_time($value);
    }
    
    /**
     * datetime 类型格式化方法，为空使用当前时间戳
     * @param  string $value, if string it must be '2017-01-02 12:00:00'
     * @return int
     */
    static public function timestamp($value=null)
    {
        return static::create_time($value);
    }
    
    /**
     * create_time 类型格式化方法，为空使用当前时间戳
     * @param  string $value, must be '2017-01-02 12:00:00'
     * @return int
     */
    static public function create_time($value=null)
    {
        return (empty($value)) ? time() : (is_numeric($value) ? $value : strtotime($value));
    }
    
    /**
     * update_time 类型格式化方法，始终以当前时间戳更新
     * @param  string $value
     * @return number
     */
    static public function update_time($value=null)
    {
        return time();
    }
    
    /**
     * title handle
     * @param  string $value
     * @return string
     */
    static public function title($value=null)
    {
        $value = StringHelper::str_cut(strip_tags($value), 84);   // 84*3 < 254
        
        return static::input($value);
    }
    
    /**
     * title handle
     * @param  string $value
     * @return string
     */
    static public function textarea($value=null)
    {
        return static::summary($value);
    }
    
    /**
     * summary handle
     * @param  string $value
     * @return string
     */
    static public function summary($value=null)
    {
        $value = StringHelper::str_cut(strip_tags($value), 84);   // 84*3 < 254
        
        return static::input($value);
    }
    
    /**
     * keywords field input handle
     * @param  string $value
     * @return string
     */
    static public function keywords($value=null)
    {
        if ($value) {
            $value = StringHelper::str_cut(strip_tags($value), 100);
        }
        
        return static::input(trim($value));
    }
    
    /**
     * json input handle
     * @param  mixed $value
     * @return string
     */
    static public function json($value=null)
    {
        if (!is_scalar($value)) {
            $value = json_encode($value);
        }
        
        return $value;
    }
    
    /**
     * gender
     * @param  mixed $value
     * @return number
     */
    static public function gender($value=null)
    {
        if (!$value) return 0;
        
        if (is_numeric($value)) {
            return $value == 1 ? 1 : ($value == 2 ? 2 : 0);
        } else {
            return $value == '男' ? 1 : ($value == '女' ? 2 : 0);
        }
    }
    
    /**
     * image
     * @param  string $value
     * @return string
     */
    static public function image($value=null)
    {
        if ($value) {
            $value = Config::getUploadFileRelativePath($value);
            return DataHelper::formatPath($value);
        }
        
        return '';
    }
    
    /**
     * magic method, 未找到的静态方法使用 input 默认处理字段值
     * notice:
     *     1 calling the method directly is faster then call_user_func_array() !
     *     2 $params 是包装的数组，需要提取出来再传值
     * @param string $method
     * @param array  $params
     * @return mixed
     */
    static public function __callStatic($method, $params){
        if (count($params) == 1) {
            return self::input($params[0]);
        } elseif (count($params) == 2) {
            return self::input($params[0], $params[1]);
        } elseif (count($params) > 2) {
            return call_user_func_array(array(self, 'input'), $params);
        } else {
            throw new Exception('Field input handle method is not exist: ' . $method);
        }
    }
}

