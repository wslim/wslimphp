<?php
namespace Wslim\Db;

use Wslim\Util\DataHelper;
use Wslim\Common\Config;

/**
 * FieldOutputHandler
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FieldOutputHandler
{
    /**
     * 格式化字段值，按以下顺序尝试: data_format|field_name|base_type|data_type
     * @param mixed  $field
     * @param string $field_value
     */
    static public function formatValue($field, $field_value=null)
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
                return static::$m($field_value);
            }
        }
        
        $data_type = strtolower($field['data_type']);
        if (preg_match('/int/', $data_type)) {
            $field_value = intval($field_value);
        } else {
            $field_value = static::output($field_value);
        }
        
        return $field_value;
    }
    
    /**
     * default format output value
     * @param  string $value
     * @return string
     */
    static public function output($value)
    {
        return $value;
    }
    
    /**
     * editor
     * @param  string $value
     * @return string
     */
    static public function editor($value)
    {
        
        $value = str_replace([chr(13).chr(10), chr(13), chr(10)], '<br>', $value);
        
        return $value;
    }
    
    /**
     * output timestamp, from timestamp to '2017-12-01 10:01:04'
     * @param  int $value
     * @return string
     */
    static public function timestamp($value)
    {
        $value || $value = time();
        if(is_numeric($value)) {
            //$value = strftime('%Y-%m-%d %H:%M:%S', $value);
            $value = strftime('%Y-%m-%d %H:%M:%S', $value);
        }
        
        return $value;
    }
    
    /**
     * to datetime output, default '2010-01-02 12:01:00'
     * @param  mixed $value
     * @return string
     */
    static public function datetime($value)
    {
        return static::timestamp($value);
    }
    
    /**
     * output create_time
     * @param  int|string $value
     * @return string
     */
    static public function create_time($value)
    {
        return static::timestamp($value);
    }
    
    /**
     * output update_time
     * @param  int|string $value
     * @return string
     */
    static public function update_time($value)
    {
        return static::timestamp($value);
    }
    
    /**
     * json output
     * @param  string $value
     * @return array
     */
    static public function json($value=null)
    {
        return DataHelper::json_decode($value);
    }
    
    /**
     * gender
     * @param  mixed $value
     * @return number
     */
    static public function gender($value=null)
    {
        if (!$value) return '保密';
        
        if (is_numeric($value)) {
            return $value == 1 ? '男' : ($value == 2 ? '女' : '保密');
        } else {
            return $value == '男' ? '男' : ($value == '女' ? '女' : '保密');
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
            return Config::getUploadFileUrl($value);
        }
        return '';
    }
    
    /**
     * password output
     * @param  string $value
     * @return string
     */
    static public function password($value=null)
    {
        return $value;
    }
    
    /**
     * magic method, 未找到的静态方法使用 output 默认处理字段值
     * notice:
     *     1 calling the method directly is faster then call_user_func_array() !
     *     2 $params 是包装的数组，需要提取出来再传值
     * @param  string $method
     * @param  mixed  $params
     * @return mixed
     */
    static public function __callStatic($method, $params){
        if (method_exists(self, [self, $method])) {
            if (count($params) == 1) {
                return self::output($params[0]);
            } elseif (count($params) == 2) {
                return self::output($params[0], $params[1]);
            } elseif (count($params) > 2) {
                return call_user_func_array(array(self, $method), $params);
            }
        } else {
            // not found by DataHelper 
            return call_user_func_array(array('\Wslim\Util\DataHelper', $method), $params);
            
            throw new Exception('Field output handle method is not exist: ' . $method);
        }
    }
}