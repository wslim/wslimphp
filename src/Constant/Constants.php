<?php
namespace Wslim\Constant;

/**
 * constants 
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Constants
{
    /**
     * get genders number=>title array
     * 
     * @return array
     */
    static public function getGenders()
    {
        return [
            '1' => '男',
            '2' => '女',
            '0' => '保密',
        ];
    }
    
    /**
     * get gender int value
     * @param  mixed $value
     * @return number
     */
    static public function getGenderValue($value=null)
    {
        return $value ? ($value == 1 || $value == '男' ? 1 : ($value == 2 || $value == '女' ? 2 : 0)) : 0;
    }
    
    /**
     * get data format key-value
     * @return string[]
     */
    static public function getDataFormats()
    {
        return [
            'text'      => 'text[不附加处理]',
            'json'      => 'json[转换为json字串]',
            'xml'       => 'xml[转换为xml]',
            'serialize' => 'serialize[转换为serialize字串]',
        ];
    }
    
    
    static public function getWatermarkPositions()
    {
        return [
            '1'     => '左上',
            '2'     => '上中',
            '3'     => '右上',
            '4'     => '中左',
            '5'     => '中中',
            '6'     => '中右',
            '7'     => '左下',
            '8'     => '下中',
            '9'     => '右下',
        ];
    }
    
    static public function getWatermarkTypes()
    {
        return array(
            'text'  => '文本',
            'image' => '图片'
        );
    }
    
    /**
     * simple form type
     * @return array
     */
    static public function getSimpleFormTypes()
    {
        return array(
            'text'      => '文本输入框',
            'checkbox'  => '开关状态型',
            'textarea'  => '多行文本框',
            'editor'    => 'html编辑器',
            'icon'      => '图标选择框',
            'image'     => '图片选择框',
        );
    }
    
    /**
     * selected form type
     * @return array
     */
    static public function getSelectedFormTypes()
    {
        return array(
            'select'    => '下拉框',
            'modal'     => '选择窗口',  //
            'radio_group'   => '单选框组',
            'checkbox_group'  => '复选框组',
        );
    }
    
    /**
     * form type
     * @return array
     */
    static public function getFormTypes()
    {
        return array(
            'text'      => '单行文本框',
            'textarea'  => '多行文本框',
            'radio'     => '单选框',
            'checkbox'  => '复选框',
            'hidden'    => '隐藏文本',
            'select'    => '下拉框',
            'modal'     => '选择窗口',  //
            'radio_group'   => '单选框组',
            'checkbox_group'=> '复选框组',
            'date'      => '日期选择',
            'datetime'  => '日期选择带时间',
            'editor'    => 'html编辑器',
            'keywords'  => '关键词-自动提取标题和内容关键词',
            'icon'      => '图标选择框',
            'image'     => '选择或上传图片',
            'position'  => '元素位置',
            'select_watermark_type' => '水印类型',
        );
    }
    
    /**
     * common regex
     * @return []
     */
    static function getRegexs()
    {
        $optionValues = array(
            '/^[0-9.-]+$/'      => '数字',
            '/^[0-9-]+$/'       => '整数',
            '/^[a-z]+$/i'       => '字母',
            '/^[0-9a-z]+$/i'    => '数字+字母',
            '/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/'   => 'Email',
            '^[0-9]{5,20}$/'    => 'QQ',
            '/^http[s]?:\/\//'  => '超链接',
            '/^(1)[0-9]{10}$/'  => '手机号码',
            '/^[0-9-]{6,13}$/'  => '电话',
            '/^[0-9]{6}$/'      => '邮编'
        );
        
        return $optionValues;
    }
    
    
    
}