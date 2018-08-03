<?php
namespace Wslim\Help;

use Wslim\Util\StringHelper;

class Help
{
    /**
     * get help text
     * @param  string $name
     * @return array
     */
    static function get($name)
    {
        $name = StringHelper::toLittleCamelCase($name);
        
        $value = null;
        
        if (method_exists(get_called_class(), $name)) {
            $value = static::$name();
        }
        
        if (is_array($value)) {
            $value = implode('<br>', $value);
        }
        
        return $value;
    }
    
    /**
     * role help
     * @return string[]
     */
    static function role()
    {
        return [
            '每个角色可设多个权限，一个用户可设置多个角色。',
            '编辑角色后，点击右上角【缓存清理】，以使数据生效。',
        ];
    }
    
    /**
     * role help
     * @return string[]
     */
    static function modelKind()
    {
        return [
            '模型分类扩展，选择要使用的分类属性，设置字段名和标题。',
            '在编辑文章内容时，会对该分类进行选择。',
        ];
    }
}