<?php
namespace Wslim\Web;

use Wslim\Ioc;
use Wslim\Util\DataHelper;
use Wslim\Security\Captcha;
use Wslim\Security\FormToken;
use Wslim\Common\Config;
use Wslim\Util\StringHelper;
use Wslim\Constant\Constants;
use Wslim\Help\Help;

/**
 * FormHelper
 * 提供表单输入的各种快捷方法和封装方法，统一参数为 (name, value, settings)
 * 
 * settings keys:
 * -- name, value, id, class 等 html属性
 * -- form_type     使用的表单方式
 * -- data_source   为表单输入提供数据源，用于 select/radio_group/checkbox_group/modal
 * -- with_desc     是否带文字描述，此时输入项表单分为两项[name][description] [name][value]    
 * -- with_input    是否同时支持输入，用于选择框
 * -- with_multi    是否多个输入项
 * -- media_type    要选择的输入资源类型，用于select/modal中指定
 * -- base_url      对于image/attachment等类型，可指定资源的基路径，默认 'upload'
 * -- modal_url     弹出窗口选择内容时的url地址 modal表单类型时必须
 * -- url_params    modal_url 的附加参数，因为模板可能是动态生成的，这是除直接自己生成modal_url参数外的另一种方式
 * -- no_wrap       不使用div包裹生成的内容
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FormHelper
{
    /**
     * 是否已加载表单组件的依赖，如果手动加载了依赖，页面开始设置该项为true
     * @var array
     */
    static public $isLoaded = [
        'simditor'  => false,
        'datetime'  => false,
        
    ];
    
    static protected $defaultSettings = [
        'name'      => '',
        'value'     => '',
        'id'        => '',
        'class'     => '',
    ];
    
    /**
     * format settings, can parse data source
     * @param  array $settings
     * @return array
     */
    static public function formatSettings($settings=null)
    {
        if (isset($settings['data_source'])) {
            if (is_string($settings['data_source'])) {
                $data = explode(',', $settings['data_source']);
                $tdata = [];
                foreach ($data as $k => $v) {
                    if (strpos($v, '|')) {
                        $tmp = explode('|', $v);
                        $tdata[trim($tmp[0])] = trim($tmp[1]);
                    } else {
                        $tdata[trim($v)] = trim($v);
                    }
                }
                $settings['data_source'] = $tdata;
            }
            
            if (!isset($settings['fields'])) {
                $settings['fields'] = ['id', 'title', 'depth'];
            } else {
                $settings['fields'] = DataHelper::explode('\,\|', $settings['fields']);
            }
        }
        
        if (isset($settings['id'])) {
            $settings['id'] = static::formatId($settings['id']);
        }
        
        // settings readonly
        if (isset($settings['readonly']) && $settings['readonly']) {
            $settings['readonly'] = "readonly";
        }
        
        return $settings;
    }
    
    static public function formatId($id)
    {
        return str_replace(['][', '[', ']'], ['_', '_', ''], $id);
    }
    
    static public function formatValueToArray($value)
    {
        if (!is_array($value)) {
            if (is_string($value)) {
                if (strpos($value, '{') !== false) {
                    $value = json_decode($value, true);
                } else {
                    $value = explode(',', $value);
                }
            } else {
                $value = (array) $value;
            }
        }
        return $value;
    }
    
    /**
     * auto build form element by form type
     * @param  string $name
     * @param  mixed  $value
     * @param  array  $settings
     * @return string
     */
    static public function auto($name, $value=null, $settings=[])
    {
        !isset($settings['form_type']) && $settings['form_type'] = 'text';
        if (isset($settings['with_multi']) && $settings['with_multi']) {
            $method = 'multi';
        } else {
            $method = $settings['form_type'];
        }
        
        return call_user_func_array(get_called_class() . '::' . $method, [$name, $value, $settings]);
    }
    
    /**
     * 多个输入表单，会递归调用相应的表单类型
     * @param  string $name
     * @param  mixed  $value
     * @param  array  $settings
     * @return string
     */
    static public function multi($name, $value=null, $settings=[])
    {
        // settings 处理
        !isset($settings['form_type']) && $settings['form_type'] = 'text';
        unset($settings['with_multi']);
        $settings['no_wrap'] = 1; // 不让子调用项包裹div
        
        $value      = static::formatValueToArray($value);
        $count      = isset($settings['count']) ? intval($settings['count']) : (count($value) > 4 ? 4 : 4);
        $form_id    = isset($settings['id']) ? $settings['id'] : static::formatId($name);
        if (substr($name, strlen($name) - 2) == '[]') {
            $name = substr($name, 0, strlen($name) - 2);
        }
        
        // 是否带文字描述输入，注意此种情况保存数据时应该为 json
        $with_desc = isset($settings['with_desc']) && $settings['with_desc'] ? true : false;
        $str = '<div class="width:100%;overflow:hidden;">' . PHP_EOL;
        for ($i=0; $i<$count; $i++) {
            $settings['id'] = $form_id . '_' . $i;
            $str .= '<div class="form-inline" style="margin-top:10px;">' . PHP_EOL;
            $item_name = $name . '[' . $i . ']';
            if ($with_desc) {
                $item_value = isset($value[$i]['description']) ? $value[$i]['description'] : '';
                $str .= '描述: ' . static::text($item_name . '[description]', $item_value);
                
                $item_name = $item_name . '[value]';
                $item_value = isset($value[$i]['value']) ? $value[$i]['value'] : '';    // 如value 可能为pic_url
            } else {
                $item_value = isset($value[$i]) ? $value[$i] : '';
            }
            
            $str .= static::$form_type($item_name, $item_value, $settings);
            
            $str .= '</div>' . PHP_EOL;
        }
        $str .= '</div>';
        return $str;
    }
    
    /**
     * return select or modal html
     * <p>
     * settings can be: <br>
     * 'fields'         =>'array,   // 定义data_sources的字段顺序value|title|depth',
     * 'data_source'    => 'array,  // 定义数据源',
     * 'with_input'     => 'boolean,// 既可输入又可选择',
     * </p>
     * 
     * @param  string $name
     * @param  string $value
     * @param  array  $settings 
     * 
     * @return string
     */
    static public function select($name, $value=null, $settings=[])
    {
        if (isset($settings['with_multi']) && $settings['with_multi']) {
            if (!isset($settings['form_type'])) {
                $settings['form_type'] = __FUNCTION__;
            }
            $settings['no_wrap'] = 1;
            unset($settings['with_multi']);
            return static::multi($settings);
        }
        
        $str = $info = $prestr = '';
        
        $value = static::formatValueToArray($value);
        $first_value = current($value);
        $settings = static::formatSettings($settings);
        
        // settings id
        $original_id = isset($settings['id']) ? $settings['id'] : static::formatId($name);
        
        // 选择的资源类型，对于media_type 为 image或资源，需要增加加预览区
        $media_type = isset($settings['media_type']) ? $settings['media_type'] : (isset($settings['modal_url']) ? 'common' : false);
        $as_media_type = ($media_type ? : 'common' ) . '-value';
        $display = $first_value ? '' : "display:none;";
        
        // 是否同时支持自定义输入
        $with_input = isset($settings['with_input']) && $settings['with_input'] ? $settings['with_input'] : false;
        if ($media_type && !$with_input) {
            $with_input = 'hidden';
        }
        if ($with_input) {
            if (!isset(static::$isLoaded['counts']['select_with_input'])) {
                static::$isLoaded['counts']['select_with_input'] = 1;
            } else {
                static::$isLoaded['counts']['select_with_input'] += 1;
            }
            if ($media_type === 'location') {
                $lng = isset($value['lng']) ? $value['lng'] : 0;
                $lat = isset($value['lat']) ? $value['lat'] : 0;
                $info .= '<label class="control-label">经度：</label>
                         <input type="text" class="form-control" placeholder="经度" id="lng" name="row[lng]" value="'.$lng.'">
                         <label class="control-label">纬度：</label>
                         <input type="text" class="form-control" placeholder="纬度" id="lat" name="row[lat]" value="'.$lat.'">';
            } else {
                $input_type = $with_input !== 'hidden' ? 'text' : 'hidden';
                $prestr .= ' ' . static::$input_type($name, $first_value, ['class'=>'form_inline', 'id' => $original_id]) . ' ';
            }
            
            $name           = 'select_' . $original_id;
            $settings['id'] = 'select_' . $original_id;
        }
        
        if ($media_type) {
            // base_url 用于解析值路径
            $base_url = isset($settings['base_url']) ? $settings['base_url'] : (stripos($first_value, '/')===0 ? '' : 'upload:');
            
            if ($media_type == 'attachment') {
                $info .= " <span class='fa fa-file-archive-o file-icon file-zip'>";
                $info .= " <a id='media_" . $original_id . "' href='" . Ioc::url($base_url. $first_value) . "' target='_blank' style=" . $display . ">浏览资源</a></span>";
            } elseif ($media_type == 'image') {
                $_src = $first_value ? "src='" . Ioc::url($base_url. $first_value) . "'" : '';
                $info .= " <img id='media_" . $original_id . "'{$_src} style='max-height:34px;padding:1px;border:#ccc 1px solid;" . $display . "' />";
            } elseif ($media_type == 'icon') {
                $info .= ' <i class="fa ' . $first_value . '" id="media_' . $original_id . '" style="max-width:34px;"> </i> ';
            } elseif (!isset($settings['to']) && !isset($settings['to_select'])) {
                $_title = isset($settings['title']) ? $settings['title'] : '';
                $info .= ' <label id="media_' . $original_id . '">'.$_title.'</label>';
            }
        }
        
        // modal 窗口处理，指定为空路径时则不显示选择按钮，也不显示select
        if (isset($settings['modal_url'])) {
            if ($settings['modal_url']) {
                $url_params = isset($settings['url_params']) ? $settings['url_params'] : null;
                $settings['modal_url'] = Ioc::url($settings['modal_url'], $url_params);
                if (isset($settings['button'])) {
                    $button_v = $settings['button'];
                } elseif (isset($settings['default'])) {
                    $button_v = $settings['default'];
                } elseif ($media_type === 'location') {
                    $button_v = '打开地图';
                } elseif ($media_type === 'image') {
                    $button_v = "打开图库";
                } else {
                    $button_v = "进行选择";
                }
                $str .= ' <input type="button" class="btn btn-primary select-' . $as_media_type . '" '
                    . 'data-id="' . $original_id . '" value="'.$button_v.'" media-type="' . $media_type . '" data-url="' . $settings['modal_url'] . '"> ';
            }
        } elseif ((isset($settings['data_source']) && $settings['data_source']) || isset($settings['default']))  {// select 元素
            // settings class
            $settings['class']  = isset($settings['class']) ? $settings['class'] : 'form-control';
            
            $str .= ' <select class="' . $settings['class'] . '" name="' . $name . '" data-id="' . $original_id . '"';
            if (isset($settings['disabled']) && $settings['disabled']) $str .= ' disabled="disabled"';
            foreach($settings as $k => $v) {
                if (in_array($k, array('onchange', 'id', 'readonly'))) {
                    $str .= " $k=\"$v\"";
                }
            }
            $str .= '>' . PHP_EOL;
            
            // option default
            $default_value = isset($settings['default_value']) ? $settings['default_value'] : '';
            if (isset($settings['default'])) $str .= '<option value="' . $default_value . '">' . $settings['default'] . '</option>' . PHP_EOL;
            
            // options data
            if (isset($settings['data_source']) && $settings['data_source']) foreach ($settings['data_source'] as $k => $v) {
                if (isset($settings['value_is_text']) && $settings['value_is_text']) {
                    $str .= static::option($v, $v, $value, 0, $settings['fields']);
                } else {
                    $str .= static::option($k, $v, $value, 0, $settings['fields']);
                }
            }
            $str .= '</select> ' . PHP_EOL;
        }
        
        if ($media_type && $media_type != 'location') {
            // 清除按钮
            $str .= ' <input type="button" class="btn btn-warning delete-' . $as_media_type . '" '. 'data-id="' . $original_id . '" value="清除" media-type="' . $media_type . '"> ';
            
            // 预览按钮，如果设置了preview_url
            if (isset($settings['preview_url']) && $settings['preview_url']) {
                $str .= ' <input type="button" class="btn btn-warning preview-' . $as_media_type .'" '
                    . 'data-id="' . $original_id . '" value="预览" media-type="' . $media_type . '" data-url="' . $settings['preview_url'] . '"> ';
            }
        }
        
        $str = $prestr . $str . $info;
        
        if ($media_type && !isset(static::$isLoaded['runtime'][$media_type])) {
            $upload_url = Config::getUploadUrl(true);
            $to_select  = isset($settings['to_select']) ? $settings['to_select'] : '';
            $innerJs = <<<EOF
            <script>
            require(["util"], function() {
            	$("body").on("click", ".delete-{$as_media_type}", function() {
                    var tid = $(this).attr('data-id');
                    var media_type = $(this).attr("media-type");
                    if ($("#" + tid).val()) {
                        util.dialog.show({
                            title: '提示',
                            content: "<span>确定要清空吗</span>",
                            width: '200',
                            height: '50',
                            ok: function () {
                				if ($("#" + tid)) $("#" + tid).val('').change();
                				if ($("#media_" + tid)) {
                                    if (media_type == "image" || media_type == "attachment") {
                                        $("#media_" + tid).attr('src', '').hide();
                                    } else if (media_type == "icon") {
                                        $("#media_" + tid).attr('class', '');
                                    } else {
                                        $("#media_" + tid).hide();
                                    }
                                }
                				return true;
                			},
                            cancelDisplay: false
                        });
                    }
                });

                $("body").on("click", ".select-{$as_media_type}", function() {
                    var tid = $(this).attr('data-id');
                    var media_type = $(this).attr("media-type");
                    var modal_url  = $(this).attr("data-url");
                    var to_select  = "{$to_select}";
            		util.dialog.show({
                        title: '选择内容',
                        url: modal_url,
                        width: '800',
                        height: 450,
                        ok: function (dialog) {
                            var data = null;
                            if (data = dialog.data()) {
                                if (media_type == "location") {
                                    if (data.lng) $("#lng").val(data.lng);
                                    if (data.lat) $("#lat").val(data.lat);
                                    if (data.address) {
                                        if ($("#address").length > 0 && $("#address").val() == "") $("#address").val(data.address);
                                        if ($("#row_address").length > 0 && $("#row_address").val() == "") $("#row_address").val(data.address);
                                    }
                                    if (data.area_id) {
                                        var area_id_input;
                                        if ($("#row_area_id").length > 0) {
                                            if (!$("#row_area_id").val() || $("#row_area_id").val() == "0") $("#row_area_id").val(data.area_id);
                                            area_id_input = $("#row_area_id");console.log(area_id_input);
                                        } else if ($("#area_id").length > 0) {
                                            if (!$("#area_id").val()) $("#area_id").val(data.area_id);
                                            area_id_input = $("#area_id");
                                        }
                                        if (data.area_title && area_id_input) area_id_input.next("label").text(data.area_title + ' ' + (data.address||''));
                                    }
                                    return false;
                                }
                                
                                var value = "", title="";
                                
                                // this is problem, data.constructor==Array return false ???
                                //if ((typeof data == "object") && (data.constructor==Array)) { 
                                if ((typeof data == "object")) {
                                    if (data[0]) { data = data[0]; }

                                    if ((typeof data == "object")) {
                                        if (data[0]) { value = data[0]; }
                                        if (data['value']) { value = data['value']; }
                                        if (data['title']) { title = data['title']; }
                                    } else {
                                        value = data;
                                    }
                                } else {
                                    value = data;
                                }

                                if (value) {
                                    if (media_type == "image" || media_type == "attachment") {
                                        var value2 = value.replace("{$upload_url}/", "");
                                        value = (value2.substr(0, 7)==='/images') ? value2 : "{$upload_url}/" + value2;
                                        if ($("#media_" + tid)) $("#media_" + tid).attr('src', value).show();
                                        if ($("#" + tid)) $("#" + tid).val(value2).change();
                                    } else if (media_type == "icon") {
                                        $("#media_" + tid).attr('class', 'fa ' + value);
                                        if ($("#" + tid)) $("#" + tid).val(value).change();
                                    } else {
                                        $("#media_" + tid).text(title).show();
                                        if ($("#" + tid)) $("#" + tid).val(value).change();
                                    }
                                    
                                    if (to_select && $(to_select) && value) {
                                        if (!title) title = value;
                                        $(to_select).append("<option value='"+value+"'>"+title+"</option>").val(value);
                                    }
                                }
                            }
                    	}
                    });
            	});
                $("body").on("click", ".preview-{$as_media_type}", function() {
                    var tid = $(this).attr('data-id');
                    var media_type = $(this).attr("media-type");
                    var modal_url = $(this).attr("data-url");
                    if ($("#" + tid).val()) {
                        var url = changeUrlParam(modal_url, 'value', $("#" + tid).val());
                        util.tabpage.open({title: "preview", url: url});
                    }
                });
            });
            </script>
EOF;
            $str .= $innerJs;
            static::$isLoaded['runtime'][$media_type] = true;
        }
        
        if ($with_input) {
            $innerJs = <<<EOF
            <script>
                require(['jquery'], function() {
                    if ($("select[name=${name}]").length < 2) {
                        $("body").on("change", "select[name=${name}]", function (e){
                            $('#' + $(this).attr('data-id')).val($(this).val());
                    	});
                    }
                });
            </script>
EOF;
            $str .= $innerJs;
        }
        
        return $str;
    }
    
    /**
     * select option
     * @param  string $value option value
     * @param  mixed  $title string|array, option show text
     * @param  string $selected_value selected option value
     * @param  int    $depth
     * @param  array  $fields
     * @return string
     */
    static public function option($value='', $title, $selected_value=null, $depth=0, $fields=array('id', 'title', 'depth'))
    {
        $selected_values = (array) $selected_value;
        $selected_values = array_map(function ($v) {
            return $v . '$';    // suffix, trans 0 == '' to '0$' == '$'
        }, $selected_values);
        $str = '';
        if (is_scalar($title)) {// 如果值是字串，当作 $name=$value 处理
            $str .= '<option value="' . $value . '"';
            if (in_array($value . '$', $selected_values)) $str .= ' selected="selected"';
            $str   .= '>';
            if ($depth) {
                $str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $depth) . '|-';
            }
            $str .= $title . '</option>' . PHP_EOL;
        } elseif(is_array($title) && isset($title[$fields[0]])) {    // 如果是数组，当作 ['title'=>$title, 'value'=>$value, ...] 处理
            $str    = '<option value="' . $title[$fields[0]] . '"';
            if (in_array($title[$fields[0]] . '$', $selected_values)) {
                $str .= ' selected="selected"';
            }
            $str   .= '>';
            $depth = isset($fields[2]) && isset($title[$fields[2]]) ? $title[$fields[2]] : $depth;
            if ($depth) {
                $str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $depth) . '|-';
            }
            if (strpos($fields[1], '|') !== false) {
                $tfields = array_map(function ($v) { return trim($v); }, explode('|', $fields[1]));
                $show_text = isset($title[$tfields[0]]) ? $title[$tfields[0]] : $title[$fields[0]];
                if (isset($title[$tfields[1]])) {
                    $show_text .= '(' . $title[$tfields[1]] . ')';
                }
            } else {
                $show_text = isset($title[$fields[1]]) ? $title[$fields[1]] : $title[$fields[0]];
            }
            $str .= $show_text . '</option>' . PHP_EOL;
    
            // 如果有子级进行递归调用
            if (isset($title['items']) && is_array($title['items'])) {
                foreach ($title['items'] as $k => $v) {
                    $str .= static::option($k, $v, $selected_value, $depth+1, $fields);
                }
            }
        }
        
        return $str;
    }
    
    /**
     * return modal html
     * <p>
     * settings can be: <br>
     * 'fields'         => array,   // data_sources fields: value|title|depth',
     * 'data_source'    => array,  // data_source',
     * </p>
     *
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     *
     * @return string
     */
    static public function modal($name, $value=null, $settings=[])
    {
        return static::select($name, $value, $settings);
    }
    
    /**
     * radio group
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function radio_group($name, $value=null, $settings=[])
    {
        return static::checkbox_group($name, $value, $settings, true);
    }
    
    /**
     * checkbox group
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function checkbox_group($name, $value=null, $settings=[], $is_radio=false)
    {
        $form_type  = $is_radio ? 'radio' : 'checkbox';
        
        if (!$is_radio && strpos($name, '[]') === false) {
            $name .= '[]';
        }
        $settings = static::formatSettings($settings);
        
        $form_id = isset($settings['id']) ? $settings['id'] : static::formatId($name);
        unset($settings['id']);
        
        $classes = isset($settings['class']) ? explode(' ', $settings['class']) : [];
        $css_inline = (isset($settings['form-inline']) && $settings['form-inline'])|| in_array('inline', $classes) || in_array('list-inline', $classes) ? : false;
        $classes = array_diff($classes, ['inline', 'list-inline']);
        array_push($classes, 'ace');
        $settings['class'] = implode(' ', $classes);
        
        $str = sprintf('<ul class="control-group list-unstyled%s">', $css_inline ? ' list-inline' : '') . PHP_EOL;
        
        if ($settings['data_source']) {
            $fields     = $settings['fields'];
            foreach ($settings['data_source'] as $k => $v) {
                if (is_scalar($v)) {
                    $_value = $k;
                    $_title = $v;
                } else {
                    $_value = isset($v[$fields[0]]) ? $v[$fields[0]] : 0;
                    $_title = isset($v[$fields[1]]) ? $v[$fields[1]] : (isset($v['name']) ? $v['name'] : '无法解析名称');
                }
                // checked, if set value-bit(value like 1,2,4) then use bit type
                if (isset($settings['value-bit']) && $settings['value-bit']) {
                    $checked = (intval($value) & $_value) ? ' checked="checked"' : '';
                } else {
                    $checked_values = isset($settings['checked']) ? $settings['checked'] : $value;
                    $checked_values = is_null($checked_values) || $checked_values === '' ? [] : (array) $checked_values;
                    $checked = in_array($_value, $checked_values) ? ' checked="checked"' : '';
                }
                
                $str .= sprintf('<li class="%s">', $css_inline ? '' : 'list-group-item') . PHP_EOL;
                if (isset($v['depth'])) $str .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $v['depth']);
                $str .= '   <input type="'.$form_type.'" name="'. $name .'" value="'.$_value.'" class="' . $settings['class'] . '" data-group="'.$_title.'" '.$checked.' > ' . PHP_EOL;
                $str .= '   <span class="lbl"> '.$_title.' </span> ' . PHP_EOL;
                $str .= '</li>' . PHP_EOL;
            }
        } else {
            $str .= '<li>' . PHP_EOL;
            $str .= static::$form_type($name, $value, $settings);
            $str .= '</li>' . PHP_EOL;
        }
        
        $str .= '</ul>' . PHP_EOL;
        return $str;
    }
    
    /**
     * textarea
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function textarea($name, $value=null, $settings=[])
    {
        $settings = static::formatSettings($settings);
        $settings['class'] = isset($settings['class']) ? $settings['class'] . ' form-control' : ' form-control';
        $settings['id'] = isset($settings['id']) ? static::formatId($settings['id']) : static::formatId($name);
        
        $str = '<textarea name="' . $name . '" style="min-height:100px;"';
        foreach($settings as $k => $v) {
            if (in_array($k, array('class', 'id', 'readonly', 'rows', 'style', 'placeholder'))) {
                $str .= " $k=\"$v\"";
            }
        }
        $str .= '>';
        $str .= (isset($value) ? trim($value) : '') . '</textarea>' . PHP_EOL;
        return $str;
    }
    
    /**
     * radio
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function radio($name, $value=null, $settings=[])
    {
        return static::input('radio', $name, $value, $settings);
    }
    
    /**
     * checkbox
     * @param  string $name
     * @param  string $value, 可传入 1/0, true/false
     * @param  array  $settings
     * @return string
     */
    static function checkbox($name, $value=null, $settings=[])
    {
        return static::input('checkbox', $name, $value, $settings);
    }
    
    /**
     * hidden
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function hidden($name, $value=null, $settings=[])
    {
        return static::input('hidden', $name, $value, $settings);
    }
    
    /**
     * text
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function text($name, $value=null, $settings=[])
    {
        return static::input('text', $name, $value, $settings);
    }
    
    /**
     * password
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function password($name, $value=null, $settings=[])
    {
        return static::input('password', $name, $value, $settings);
    }
    
    /**
     * datetime
     * @param  string $name
     * @param  string $value, timestamp format
     * @param  array  $settings
     * @return string
     */
    static function datetime($name, $value=null, $settings=[])
    {
        return static::input('datetime', $name, $value, $settings);
    }
    
    /**
     * timestamp, alias datetime
     * @param  string $name
     * @param  string $value, timestamp format
     * @param  array  $settings
     * @return string
     */
    static function timestamp($name, $value=null, $settings=[])
    {
        return static::input('datetime', $name, $value, $settings);
    }
    
    /**
     * date, not contain time
     * @param  string $name
     * @param  string $value, date format
     * @param  array  $settings
     * @return string
     */
    static function date($name, $value=null, $settings=[])
    {
        return static::input('date', $name, $value, $settings);
    }
    
    /**
     * position, can be 'top|center' or '100|300', it is 'x|y'
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function position($name, $value=null, $settings=[])
    {
        if (!isset($settings['description'])) {
            $settings['description'] = '位置可设为：1到9的数字表示九宫格的位置, 或100|30, 或 center|center, 或100|left';
        }
        
        return static::input('text', $name, $value, $settings);
    }
    
    /**
     * input
     *
     * @param  string $input_type checkbox|text|hidden|datetime
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static function input($input_type, $name, $value=null, $settings=[])
    {
        $settings = static::formatSettings($settings);
        is_array($value) && $value = current($value);
        $str = '';
        $pre_input_type = $input_type;
        switch ($input_type) {
            case 'checkbox':
            case 'radio':
                $settings['class'] = isset($settings['class']) ? $settings['class'] : 'ace ace-switch ace-switch-5';
                $str .= '<label>' . PHP_EOL ;
                break;
            case 'text':
            case 'password':
                $settings['id'] = isset($settings['id']) ? static::formatId($settings['id']) : static::formatId($name);
                $settings['class'] = isset($settings['class']) ? 'form-control ' . $settings['class'] : 'form-control';
                break;
            case 'datetime':
            case 'date':
                $input_type = 'text';
                $value = $pre_input_type == 'datetime' ? DataHelper::fromUnixtime($value) : DataHelper::toDate($value);
                $settings['id'] = isset($settings['id']) ? static::formatId($settings['id']) : static::formatId($name);
                $settings['class']  = isset($settings['class']) ? $settings['class'] . ' form-control date-picker' : ' form-control date-picker';
                break;
            default:
                $settings['class'] = isset($settings['class']) ? $settings['class'] : '';
                break;
        }
        
        $str .= '<input type="' . $input_type . '" name="' . $name . '"';
        
        if ($input_type == 'checkbox' || $input_type == 'radio') {
            // 处理 value, checked
            $o_form_value = !is_null($value) || $value !== '' ? $value : '1';
            $value   = $value ? : '1';
            if (isset($settings['checked'])) {
                if (is_array($settings['checked'])) {
                    $_checked = in_array($value, $settings['checked']) ? true : false;
                } elseif (in_array($settings['checked'], ['', 1,0,true,false])) {
                    $_checked = $settings['checked'];
                } else {
                    $_checked = $value == $settings['checked'] ? true : false;
                }
                $str .= $_checked ? ' checked="checked"' : '';
            } elseif (is_numeric($o_form_value) || is_bool($o_form_value)) {
                if (intval($o_form_value) == 1) {
                    $str .= ' checked="checked"';
                }
            }
        }
        
        $str .= ' value="' . $value . '"';
        
        foreach($settings as $k => $v) {
            if (in_array($k, array('class', 'onchange', 'id', 'readonly', 'style', 'placeholder'))) {
                $str .= " $k=\"$v\"";
            } elseif($k == 'checked' && ($input_type !== 'checkbox' && $input_type !== 'radio')) {
                $str .= " $k=\"$v\"";
            }
        }
        $str .= '> ';
        
        if ($input_type == 'checkbox' || $input_type == 'radio') {
            $title = isset($settings['title']) ? $settings['title'] : '';
            $str .= ' <span class="lbl"> '.$title.' </span> ' . PHP_EOL;
            $str .= '</label>' . PHP_EOL ;
        }
        
        /**
         * 处理日期输入控件，依赖于 jquery.js, jquery-ui.datetime.js, datetimepicker.js, datetimepicker.css
         * <script src='{$rootUrl}/static/jquery/jquery-1.10.2.min.js'></script>
         * <link type="text/css" rel="stylesheet" href="{$rootUrl}/static/datetimepicker-2.5.3/jquery.datetimepicker.css"/>
         * <script src="{$rootUrl}/static/datetimepicker-2.5.3/jquery.datetimepicker.full.min.js"></script>
         */
        if ($pre_input_type == 'datetime' || $pre_input_type == 'date') {
            $innerJs = "";
            $dformat = $pre_input_type == 'datetime' ? 'Y-m-d H:i:s' : 'Y-m-d';
            if (!static::$isLoaded['datetime']) {
                $web_url = Config::getRootUrl();
                $innerJs .= <<<EOF
                    <link type="text/css" rel="stylesheet" href="${web_url}/static/datetimepicker-2.5.3/jquery.datetimepicker.css"/>
EOF;
                static::$isLoaded['datetime'] = true;
            }
            $innerJs .= <<<EOF
                <script>
                    require(['jquery.datetime'], function() {
                        $("#${settings['id']}").datetimepicker({
                    		format:	"{$dformat}",
                    	});
                    });
                </script>
EOF;
            $dt_str = '<div class="input-group input-group-sm">' . PHP_EOL;
            $dt_str .= $str . PHP_EOL;
            $dt_str .= '<span class="input-group-addon">' . PHP_EOL;
            $dt_str .= '    <i class="fa fa-clock-o bigger-110"></i>' . PHP_EOL;
            $dt_str .= '</span>' . PHP_EOL;
            $dt_str .= '</div>' . PHP_EOL;
            $dt_str .= $innerJs;
            	
            $str = & $dt_str;
        }
         
        return $str;
    }
    
    /**
     * icon select form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function icon($name, $value=null, $settings=[])
    {
        $settings['media_type'] = 'icon';
        $settings['with_input'] = 'hidden';
        if (!isset($settings['readonly'])) $settings['readonly'] = "readonly";
        if (!isset($settings['modal_url'])) $settings['modal_url'] = Ioc::url('common/data/select/icon');
        return static::select($name, $value, $settings);
    }
    
    /**
     * 上传资源的表单html，依赖配置js：使用 require.js,util.js，或页面直接加载util.js
     * @param  string $name
     * @param  string $value
     * @param  array  $settings [modal_url, preview_url, readonly]
     * @return string
     */
    static public function image($name, $value=null, $settings=[])
    {
        $settings['media_type'] = 'image';
        $settings['with_input'] = isset($settings['with_input']) ? $settings['with_input'] : 'hidden';
        if (!isset($settings['readonly'])) $settings['readonly'] = "readonly";
        if (!isset($settings['modal_url'])) $settings['modal_url'] = Ioc::url('common/file/select');
        if (!isset($settings['preview_url'])) $settings['preview_url'] = Ioc::url('preview?media_type=image');
        return static::select($name, $value, $settings);
    }
    
    /**
     * attachment form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function attachment($name, $value=null, $settings=[])
    {
        $settings['media_type'] = 'attachment';
        if (!isset($settings['preview_url'])) $settings['preview_url'] = Ioc::url('preview?metia_type=attachment');
        return static::image($name, $value, $settings);
    }
    
    /**
     * url input form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function url($name, $value=null, $settings=[])
    {
        $settings['media_type'] = 'url';
        $settings['with_input'] = isset($settings['with_input']) ? $settings['with_input'] : 'text';
        return static::select($name, $value, $settings);
    }
    
    /**
     * select area form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function area($name, $value=null, $settings=[])
    {
        $name || $name = 'row[area_id]';
        if (!isset($settings['modal_url'])) $settings['modal_url'] = Ioc::url('common/data/select/area');
        
        return static::select($name, $value, $settings);
    }
    
    /**
     * keywords form, depend js: require.js and util.js
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function keywords($name, $value=null, $settings=[])
    {
        $settings['id'] = isset($settings['id']) ? $settings['id'] : static::formatId($name);

        $post_data = '';
        if (isset($settings['from_fields']) && $settings['from_fields']) {
            $settings['from_fields'] = DataHelper::explode([',', '|'], $settings['from_fields']);
            foreach ($settings['from_fields'] as $k => $v) {
                $settings['from_fields'][$k] = "row_" . trim($v);
                $data[] = "$('#row_" . trim($v) . "').val()";
            }
            $post_data = implode('+', $data);
        }
        $str = static::text($name, $value, $settings);
        
        if ($post_data) {
            $str .= " <input type='button' class='btn btn-primary keywords-build " . $settings['id'] . "' value='从内容提取'> ";
    
            $modal_url = Ioc::url('api:/common/get_keywords');
            $innerJs = <<<EOF
            <script>
            <!--
			require(['jquery'], function(){
                $(function (){
                    $(".keywords-build.${settings['id']}").click(function(){
                        var token = $("input[name=_form_token]").val(); 
    				    var kele = $("#${settings['id']}");
    				    var data = {$post_data};
    					if (kele.val() == "" && data != "") {
    						$.get("{$modal_url}"
								, {_form_token: token, data: encodeURI(data)}
								, function(response){
									if(response && response.errcode==0 && kele.val()=='') kele.val(response.data);
								}
                                , 'json'
							);
    					}
    				});
                });
			});
    			-->
            </script>
EOF;
            $str .= $innerJs;
        }
        return $str;
    }
    
    /**
     * summary html
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function summary($name, $value=null, $settings=[])
    {
        return static::textarea($name, $value, $settings);
    }
    
    /**
     * editor form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function editor($name, $value=null, $settings=[])
    {
        return static::ueditor($name, $value, $settings);
    }
    
    /**
     * simditor form
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function simditor($name, $value=null, $settings=[])
    {
        $settings = static::formatSettings($settings);
        $form_id    = isset($settings['id']) ? static::formatId($settings['id']) : static::formatId($name);
        $web_url = Config::getRootUrl() . '/lib';
        if(empty($value)) {
            $value = '<p>Thanks for Simditor.</p>';
        }

        // 此处动态加载有问题，不能直接运行脚本
        if (!static::$isLoaded['simditor']) {
            $inner .= <<<EOF
                <link rel="stylesheet" href="${web_url}/simditor-2.3.6/assets/simditor.css" />
                <script type="text/javascript" src="${web_url}/simditor-2.3.6/assets/simditor-2.3.6.full.js"></script>
EOF;
            static::$isLoaded['simditor'] = true;
        }
    
        $inner .= <<<EOF
            <section id="${form_id}wrap">
                <textarea id="${form_id}" name="${name}" data-autosave="${form_id}-content" autofocus required>
                    ${value}
                </textarea>
            </section>
EOF;
        $innerJs = <<<EOF
        <script>
            (function() {
    
                $(function() {
                    var preview, editor, mobileToolbar, toolbar;
                    Simditor.locale = 'zh-CN';	// en-US
                    toolbar = ['title', 'bold', 'italic', 'underline', 'strikethrough', 'fontScale', 'color', '|', 'ol', 'ul', 'blockquote', 'code', 'table', '|', 'link', 'image', 'hr', '|', 'indent', 'outdent', 'alignment'];
                    mobileToolbar = ["bold", "underline", "strikethrough", "color", "ul", "ol"];
                    if (mobilecheck()) {
                      toolbar = mobileToolbar;
                    }
                    editor = new Simditor({
                      textarea: $('#${form_id}'),
                      placeholder: '这里输入文字...',
                      toolbar: toolbar,
                      pasteImage: true,
                      defaultImage: '${web_url}/simditor-2.3.6/assets/images/image.png',
                      upload: location.search === '?upload' ? {
                        url: '/upload'
                      } : false
                    });
                    preview = $('#preview');
                    if (preview.length > 0) {
                      return editor.on('valuechanged', function(e) {
                        return preview.html(editor.getValue());
                      });
                    }
                });
            }).call(this);
        </script>
EOF;
        return $inner . PHP_EOL . $innerJs;
    }
    
    /**
     * ueditor form
     *
     * @access    public
     * @param  string $name
     * @param  string $value
     * @param  array  $settings [width,height,type,js_mode]
     * @return string
     */
    static public function ueditor($name, $value=null, $settings=array())
    {
        if (empty($value)) {
            $value = "编辑内容";
        }
        
        $defultSettings = array(
            'width'     => "100%",
            'height'    => "400",
            'type'      => "simple",
            'js_mode'   => false
        );
        $settings = array_merge($defultSettings, $settings);
        
        $pluginFile = rtrim(Config::getWebRootPath(), '/') . '/lib/ueditor-1.4.3/use/ueditor.php';
        require_once($pluginFile);
        $UEditor = new \UEditor();
        $UEditor->basePath = Config::getRootUrl() . '/lib/ueditor-1.4.3/';
        
        $config = $events = array();
        if (isset($GLOBALS['editor_toolbar'])) {
            $config['toolbars'] = $GLOBALS['editor_toolbar'][$settings['type']];
        }
        $config['minFrameHeight']       = $settings['height'];
        $config['initialFrameHeight']   = $settings['height'];
        $config['initialFrameWidth']    = $settings['width'];
        $config['autoHeightEnabled']    = false;
        
        if (!$settings['js_mode']) {
            $code = $UEditor->editor($name, $value, $config, $events);
        } else {
            $code = $UEditor->jseditor($name, $value, $config, $events);
        }
        return $code;
    }
    
    /**
     * tui md editor form. 仍有问题，require 加载css有问题
     * 
     * @access    public
     * @param  string $name
     * @param  string $value
     * @param  array  $settings [width,height,type]
     * @return string
     */
    static public function tuieditor($name, $value=null, $settings=array())
    {
        $form_id    = isset($settings['id']) ? static::formatId($settings['id']) : static::formatId($name);
        if (empty($value)) {
            $value = "### 编辑内容";
        }
        
        $defultSettings = array(
            'width'     => "100%",
            'height'    => "400",
        );
        $settings = array_merge($defultSettings, $settings);
        
        $str = "";
        
        $str .= <<<EOF
        <div id="tuieditor_box">

        </div>
EOF;
        // 此处动态加载有问题，不能直接运行脚本
        if (!isset(static::$isLoaded['tuieditor']) || !static::$isLoaded['tuieditor']) {
            $base_url = Config::getRootUrl() . '/static/tui-editor';
            $str .= <<<EOF
<link rel="stylesheet" type="text/css" href="${base_url}/dist/tui-editor.min.css" />
<link rel="stylesheet" type="text/css" href="${base_url}/dist/tui-editor-contents.min.css" />
<script type="text/javascript">
require(["tui-editor"], function () {
    var editor = new Editor({
        el: document.querySelector('#tuieditor_box'),
        initialEditType: 'markdown',
        previewStyle: 'vertical',
        height: '400px'
    });
});

/*
$(function () {
    var editor = new tui.Editor({
        el: document.querySelector('#tuieditor_box'),
        initialEditType: 'markdown',
        previewStyle:    'vertical',
        height:          '400px'
    });
});
*/
</script>
EOF;
            static::$isLoaded['tuieditor'] = true;
        }
        
        return $str;
    }
    
    /**
     * checkbox gender
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function radio_gender($name, $value=null, $settings=[])
    {
        if (!isset($settings['data_source'])) {
            $settings['data_source'] = Constants::getGenders();
        }
        $settings['class'] = (isset($settings['class']) ? $settings['class'] : '' ) . ' list-inline';
        $value = Constants::getGenderValue($value);
        
        return static::radio_group($name, $value, $settings);
    }
    
    /**
     * field form, detect by form type
     * @param  array  $field field info array
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string $html
     */
    static public function fieldForm($field, $name, $value=null, $settings=[])
    {
        // format field
        $field = \Wslim\Cms\FieldHelper::formatField($field);
        
        if (!$name) {
            $name  = 'row[' . $field['field_name'] . ']';
        }
        
        // value 此处进行数据解码和反转义，当值不为空
        
        // settings 处理，合并加入 field 的一些选项
        foreach ($field as $k => $v) {
            if (strpos($k, 'form_') === 0) {
                $sk = $k == 'form_type' ? $k : str_replace('form_', '', $k);
                if (!isset($settings[$sk])) $settings[$sk] = $v;
            }
        }
        
        return static::auto($name, $value, $settings);
    }
    
    /**
     * kind form, need settings[modal_url]
     * @param  string $name
     * @param  string $value
     * @param  array  $settings
     * @return string
     */
    static public function kind($name, $value=null, $settings=[])
    {
        if (strpos($name, '[') === false) {
            $kind_type = $name;
            $name = '_kinds[' . $name . '][]';
        } else {
            $kind_type = preg_replace('/_(kinds\[)?(\w+)\]?\[\]/', '${2}', $name);
        }
        $settings = static::formatSettings($settings);
        $form_type = isset($settings['form_type']) ? trim($settings['form_type']) : 'checkbox_group';
        
        if ($form_type === 'modal') {
            $modal_url = isset($setting['modal_url']) ? $setting['modal_url'] : 'common/data/select/kind?kind_type=' . $kind_type;
            $settings['modal_url'] = Ioc::url($modal_url);
            $str = static::select($name, $value, $settings);
        } elseif ($form_type === 'select') {
            $str = static::select($name, $value, $settings);
        } else {
            $str = static::checkbox_group($name, $value, $settings);
        }
        
        return $str;
    }
    
    /**
     * help html
     * @param  string $name
     * @param  string $form_type
     * @return string
     */
    static public function help($name, $form_type='button', $align='')
    {
        $text  = Help::get($name);
        
        $value = '';
        if ($text) {
            $align = $align == 'left' ? 'pull-left' : ($align == 'right' ? 'pull-right' : '');
            switch ($form_type) {
                case 'button':
                case 'btn':
                    $value = '<a class="btn btn-white open-help '.$align.'" data-name="'.$name.'" title="帮助"><i class="fa fa-question-circle"></i> 帮助</a>';
                    break;
                case 'icon';
                    $value = '<i class="fa fa-2x fa-question-circle open-help '.$align.'" data-name="'.$name.'" style="cursor:pointer;"></i>';
                default:
            }
            
            $value .= '<div style="display:none;" data-name="'.$name.'">'.$text.'</div>';
            $value .= <<<EOF
            <script>require(["util"], function (){
                $(".open-help").click(function () {
                    var name = $(this).attr("data-name");
                    var content = $("div[data-name=${name}]").html();
                    util.dialog.show({
                        title:   "操作帮助",
                        content: content,
                        height: 90,
                        width:  500
                    });
                });
            });</script>
EOF;
            
        }
        
        return $value;
    }
    
    /******************************************************
     * token and verify method
     ******************************************************/
    
    /**
     * get form_token html
     * @param  string $name
     * @param  array  $data 
     * @return string
     */
    static public function token($name=null, $data=null)
    {
        if (!is_null($name) && is_null($data) && (is_numeric($name) || is_array($name) || $name=='')) {
            $data = $name;
            $name = null;
        }
        
        return (new FormToken())->form($name, $data);
    }
    
    /**
     * get captcha image html, depend token-form-name and captcha-image-api-url
     * @param  string $token_name
     * @param  string $url if string is apiurl, if array is settings
     * @param  array  $settings
     * @return string
     */
    static public function captcha($token_name='_form_token', $apiurl=null, $settings=[])
    {
        if (is_array($token_name)) {
            $settings = $token_name;
            $token_name = null;
        }
        $token_name || $token_name = '_form_token';
        
        if (is_array($apiurl)) {
            $settings = $apiurl;
            $apiurl = null;
        }
        $apiurl || $apiurl = Ioc::url('api:common/getCaptchaImage');
        
        $str = '';
        $class = isset($settings['class']) ? $settings['class'] : 'captcha-image';
        $settings['style'] = isset($settings['style']) ? $settings['style'] : '';
        $settings['style'] .= ';cursor:pointer;';
        foreach ($settings as $k => $v) {
            if (in_array($k, ['width', 'height', 'id', 'style'])) {
                $str .= $k . '="' . $v . '" ';
            }
        }
        $str = '<img src="" class="' . $class . '" title="点击刷新" ' . $str . '/>';
        $isLoad = isset(static::$isLoaded['captcha']) && static::$isLoaded['captcha'] ? true : false;
        if (!$isLoad) {
            $str .= <<<EOF
            <script>
            $(function (){
                var captcha_image_load = function(){
                    var self = $(".${class}");
                    var data = {};
                    var token = self.parents("form").find("input[name=${token_name}]");
                    if (token.length) {
                    	data['_form_token'] = token.val();
                    }
                    
        	    	$.get("{$apiurl}", data, function(data){
                        if (data) {
                            self.attr("src", "data:image/jpg;base64," + data.base64);
                        }
                    });
        	    };
                $(".${class}").on("click", captcha_image_load);
                setTimeout(function () {
                    $(".${class}").first().click();
                }, 1000);
            });
            </script>
EOF;
        }
        
        return $str;
    }
    
    /**
     * verify captcha
     * 
     * @param  string $token
     * @param  string $code
     * @return array  ['errcode'=>..., 'errmsg'=>...]
     */
    static public function verifyCaptcha($token=null, $code)
    {
        if (!$token) {
            $token = isset($_POST['_form_token']) ? $_POST['_form_token'] : null;
        }
        
        return Captcha::instance()->verify($token, $code);
    }
    
    /**
     * reset captcha
     * @param  string $token
     * @return void
     */
    static public function resetCaptcha($token=null)
    {
        if (!$token) {
            $token = isset($_POST['_form_token']) ? $_POST['_form_token'] : null;
        }
        
        return Captcha::instance()->reset($token);
    }
    
    /**
     * signature
     * @param  string $name
     * @param  string $value image url, new image src="data:image/png;base64,xxx" 
     * @param  array  $settings
     * @return string
     */
    static public function signature($name=null, $value=null, $settings=[])
    {
        $name || $name = 'row[signature]';
        $nts_name = explode(']', $name);
        $nts_name[0] .= '_nts';
        $nts_name = count($nts_name)>1 ? implode(']', $nts_name) : $nts_name[0];
        $value && $value = Ioc::url('upload:'.$value);
        
        $width = isset($settings['width']) ? intval($settings['width']) : 400;
        $height = isset($settings['height']) ? intval($settings['height']) : 100;
        if ($width < 300) {
            $width = 300;
        }
        if ($height < 100) {
            $height = 100;
        }
        $str = <<<EOF
        <style>.signature-result {border: 1px solid #333;background:#fff;} .signature-result img {}</style>
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div class="js-signature" style="width:${width}px;height:${height}px;" data-width="${width}" data-height="${height}" data-border="1px solid black" data-line-color="#bc0000" data-auto-fit="true"></div>
                <p style="margin-top:15px;text-align:center;">
                    <button type="button" class="btn btn-primary btn-sm signature-default" style="display:none;">默认签名</button>&nbsp;
                    <button type="button" class="btn btn-primary btn-sm signature-clear" >清空重签</button>&nbsp;
                    <button type="button" class="btn btn-success btn-sm signature-build" disabled>生成签名</button>
                </p>
            </div>
            <div class="col-sm-12 col-md-6">
                <input name="audit[default_signature]" value="${value}" type="hidden" id="default_signature">
                <input name="${name}" value="${value}" type="hidden" class="signature-text">
                <input name="${nts_name}" value="${value}" type="hidden" class="signature-nts-text">
                <div class="signature-result" style="width:${width}px;height:${height}px;" ><p><em>this is signature image</em></p></div>
                <p style="margin-top:15px;text-align:center;">
                    <label>
                    <input name="audit[save_signature]" value="1" class="ace ace-switch ace-switch-5" type="checkbox">  <span class="lbl"> 保存为我的默认签名 </span> 
                    </label>
                </p>
            </div>
        </div>
        <script>
        require(["jquery", "jquery.signature"], function (){
            var src = "${value}";

            if ($('.js-signature').length) {
                $('.js-signature').jqSignature({autoFit: true});

                $('.signature-clear').on("click", clearCanvas);
                $('.signature-build').on("click", buildSignature);
                
                if (src) {
                    $(".signature-default").show().on("click", function () {
                        $('.js-signature').eq(0).jqSignature('drawImage', src);
                        buildSignature();
                        $("#default_signature").val(src);
                    });
                    setTimeout(function(){
                        $('.js-signature').eq(0).jqSignature('drawImage', src);
                        buildSignature();
                        $("#default_signature").val(src);
                    }, 1000);
                }
            }
            
            function clearCanvas() {
                $('.signature-result').html('<p><em>签名图</em></p>');
                $('.js-signature').eq(0).jqSignature('clearCanvas');
                $('.signature-build').attr('disabled', true);
            }
            
            function buildSignature() {
                $("#default_signature").val("");
                
                $('.signature-result').empty();
                
                var ntsDataUrl = $('.js-signature').eq(0).jqSignature('getNotimeDataURL');
                $('.signature-result').prevAll("input.signature-nts-text").val(ntsDataUrl);
                
                var dataUrl = $('.js-signature').eq(0).jqSignature('getDataURL'); 
                var img = $('<img>').attr('src', dataUrl);
                $('.signature-result').append(img);
                $('.signature-result').prevAll("input.signature-text").val(dataUrl);
            }
            
            $('.js-signature').eq(0).on('jq.signature.changed', function() {
                $('.signature-build').attr('disabled', false);
            });
        });
        </script>
EOF;
        return $str;
    }
    
    /**
     * lng/lat form
     * @param  string $name
     * @param  array  $value
     * @param  array  $settings
     * @return string
     */
    static public function location($name=null, $value=null, $settings=[])
    {
        $settings['media_type'] = 'location';
        if (!isset($settings['modal_url'])) {
            $settings['modal_url'] = Ioc::url('common/data/select/location');
        }
        
        $data = [
            'lng'  => isset($value['lng']) ? $value['lng'] : '',
            'lat'  => isset($value['lat']) ? $value['lat'] : '',
            'addr' => isset($value['addr']) ? $value['addr'] : (isset($value['address']) ? $value['address'] : ''),
        ];
        $settings['modal_url'] = Ioc::url($settings['modal_url'], $data);
        
        return static::select($name, $value, $settings);
    }
    
    /**
     * magic method, 未找到的静态方法使用 input 默认处理字段值
     * notice:
     *     1 calling the method directly is faster then call_user_func_array() !
     *     2 $params 是包装的数组，需要提取出来再传值
     * @param string $method
     * @param array  $params
     */
    static public function __callStatic($method, $params){
        if (strpos($method, 'select_') === 0) {
            if (!isset($params[2]['date_source'])) {
                $name = str_replace('select_', '', $method);
                if (!isset($params[0])) $params[0] = $name;
                if (!isset($params[1])) $params[1] = null;
                $dataMethod = 'get' . StringHelper::toCamelCase($name . 's');
                if (method_exists('\\Wslim\\Constant\\Constants', $dataMethod)) {
                    $params[2]['data_source'] = \Wslim\Constant\Constants::$dataMethod();
                }
            }
            $method = 'select';
            
            return call_user_func_array(array(get_called_class(), $method), $params);
        }
        
        return call_user_func_array(array('\Wslim\Db\FieldOutputHandler', $method), $params);
    }
    
}