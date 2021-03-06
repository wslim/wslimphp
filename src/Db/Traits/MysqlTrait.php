<?php
namespace Wslim\Db\Traits;

trait MysqlTrait
{
    /**
     * format field or table
     * @access protected
     * @param  string $key
     * @return string
     */
    public function formatKey($key, $hasAlias=false) {
        //echo '[format before:]'.$key . $hasAlias . PHP_EOL;
        $key   =  trim($key);
        if (empty($key)) return $key;
        if (strpos($key, ',') > 0 ) {   // 含逗号分隔的多个元素
            $arr = explode(',', $key);
            foreach ($arr as $k=>$v) {
                $arr[$k] = $this->formatKey($v, $hasAlias);
            }
            //array_map($this->formatKey($key), $arr);  // 这个会引起 fetal error, memory ?
            $key = implode(',', $arr);
            unset($arr);
        } elseif (preg_match('/([\s\+\-\*\/\%])+/', $key, $matches)) { // 含空格分隔
            $arr = explode($matches[1], $key, 2);
            $key = $this->formatKey($arr[0], $hasAlias) . $matches[1] . $arr[1];
            unset($arr, $matches);
        } elseif (strpos($key, '.') > 0 ) {
            $arr = explode('.', $key);
            $key = static::formatKey($arr[0], true) . '.' . static::formatKey($arr[1], false);
            unset($arr);
        } elseif (!$hasAlias && !preg_match('/[,\'\"\*\(\)`\s]/', $key)) {
            $key = '`'.$key.'`';
        }
        //echo '[format result:]'.$key . PHP_EOL;
        return $key;
    }
    
    protected function & formatColumns($columns)
    {
        $fields = array();
        foreach ($columns as $k => $v) {
            $fields[$k]['field_name']   = isset($v['field_name']) ? $v['field_name'] : $v['name'];
            $fields[$k]['data_type']    = isset($v['data_type']) ? $v['data_type'] : $v['type'];
            $fields[$k]['data_length']  = isset($v['data_length']) ? $v['data_length'] : $v['length'];
            $fields[$k]['is_nullable']  = (strtolower($v['null']) == 'no') ? 0 : 1;
            $fields[$k]['is_primary']   = isset($v['is_primary']) ? $v['is_primary'] : $v['primary'];
            $fields[$k]['default']      = isset($v['default']) ? $v['default'] : null;
        }
        return $fields;
    }
    
}