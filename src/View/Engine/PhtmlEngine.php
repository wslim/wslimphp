<?php
namespace Wslim\View\Engine;

use Wslim\Ioc;
use Wslim\View\EngineInterface;
use Wslim\Util\ArrayHelper;

class PhtmlEngine implements EngineInterface
{
    /**
     * {@inheritDoc}
     * @see \Wslim\View\EngineInterface::parse()
     */
    public function parse(&$content)
    {
    	return $this->parse_all($content);
    }
    
    /**
     * parse content to php code
     * @param string $str
     * @return string parsed content
     */
    private function parse_all(&$str)
    {
        // php include
//         $str = preg_replace ( "/\{include\s+(.+)\}/", "<?php include \\1;?\>", $str );
        
        // php code, 内容分析到 '}', 可能会有问题？
        $str = preg_replace ( "/\{php\s+([^\}]+)\}/", "<?php \\1?>", $str );
        
        // if else
        $str = preg_replace ( "/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str );
        $str = preg_replace ( "/\{else\}/", "<?php } else { ?>", $str );
        $str = preg_replace ( "/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str );
        $str = preg_replace ( "/\{\/if\}/", "<?php } ?>", $str );
        
        // for : {for($i=1; $i<10; $i++)}
        $str = preg_replace("/\{for\s+\(?(.+?)\)?\}/","<?php for(\\1) { ?>", $str);
        $str = preg_replace("/\{\/for\}/", "<?php } ?>", $str);
        
        // foreach : 
        // 1. {foreach($array as [$key=>] $value)}
        // 2. {foreach $array $value}
        // 3. {foreach $array $key $value}
        $str = preg_replace("/\{(foreach|loop)\s+\(((\S+).+?)\)\}/","<?php if(isset(\\3) && is_array(\\3)) foreach(\\2) { ?>", $str);
        $str = preg_replace("/\{(foreach|loop)\s+(\S+)\s+(?:as\s+)*(\S+)\}/", "<?php if(isset(\\2) && is_array(\\2)) foreach(\\2 as \\3) {?>", $str );
        $str = preg_replace("/\{(foreach|loop)\s+(\S+)\s+(?:as\s+)*(\S+)\s+(\S+)\}/", "<?php if(isset(\\2) && is_array(\\2)) foreach(\\2 as \\3 => \\4){?>", $str );
        $str = preg_replace("/\{\/(foreach|loop)\}/", "<?php } ?>", $str );
        
        // ++ --
        $str = preg_replace("/\{\+\+(.+?)\}/", "<?php ++\\1; ?>", $str);
        $str = preg_replace("/\{\-\-(.+?)\}/", "<?php ++\\1; ?>", $str);
        $str = preg_replace("/\{(.+?)\+\+\}/", "<?php \\1++; ?>", $str);
        $str = preg_replace("/\{(.+?)\-\-\}/", "<?php \\1--; ?>", $str);
        
        // {widget:name action='method'...}  调用标签方法
        $str = preg_replace_callback("/\{widget:?([\w\:]+)?\s+([^}]+)\}/i", array($this,'widget_callback'), $str);
        $str = preg_replace_callback("/\{\/widget[^}]*\}/i", array($this, 'end_widget_callback'), $str);
        
        // include sub template: {include 'header'}
        $str = preg_replace_callback( "/\{(template|include)\s+(.+)\}/", array($this, 'parse_callback'), $str );
        
        // function or var: {function(...)} or {$data}
        $str = $this->parse_fun_var($str);
        
        // add permission
        
        return $str;
    }
    
    /**
     * 解析其他未处理的回调
     * @param array $matches
     * @return string
     */
    private function parse_callback($matches)
    {
    	$method = $matches[1];
    	//$params = str_replace(array('"', '\''), '', $matches[2]);
    	$params = $this->addquote($matches[2]);
    	switch ($method) {
    	    case 'template':
    		case 'include':
    			$content = 'include $this->template(' . $params . ')';
    			break;
    	}
    	
    	return isset($content) ? '<?php /* include: ' . $params . '*/ ' . $this->addquote($content) . "; ?>" : '';
    }
    
    /**
     * 解析函数和变量
     *   
     * @param string $str
     */
    private function parse_fun_var(& $str)
    {
        // 变量： 点号变量转为数组式变量  $a.b.c   =>  $a[b][c] 
        $str = preg_replace('/\$(\w+)\.(\w+)\.(\w+)\.(\w+)/is','$\\1[\'\\2\'][\'\\3\'][\'\\4\']',$str);
        $str = preg_replace('/\$(\w+)\.(\w+)\.(\w+)/is','$\\1[\'\\2\'][\'\\3\']',$str);
        $str = preg_replace('/\$(\w+)\.(\w+)/is','$\\1[\'\\2\']',$str);
        
        // 方法: {function()}}
        $str = preg_replace("/\{(\!?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:\\-\>$]*\(([^{}]*)\))\s*\}/", "<?php print_r(\\1); ?>", $str );
        
        // 方法: {function a=a1 b=b1}
        $str = \preg_replace_callback("/\{(\w+)\s+([^}]+)\}/i", array($this,'func_arr_params_callback'), $str);
        
        // 常量: {XXXX}
        $str = preg_replace ( "/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php print_r(\\1); ?>", $str );
        
        // 变量: {$varname[]}, {$varname}
        //$str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"]*)\}/", "<?php print_r(isset(\\1) ? \\1 : '');\?\>", $str );
        
        // 其他: {$var...} to directly output, {$var||'abd'} or {$var}
        $str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*(?:[^{}]*))\|\|\s*([^}]+)\s*\}/s", "<?php print_r(isset(\\1) && \\1 ? \\1 : \\2); ?>", $str );
        $str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*(?:[^{}]*))\}/s", "<?php print_r(isset(\\1) ? \\1 : ''); ?>", $str );

        // 转义变量，放在后边
        /*
         * $str = preg_replace("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/es", "\$this->addquote('<?php echo \\1; ?>')", $str);
         */
        $str = preg_replace_callback ("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", array($this,'addquote_callback'), $str);
        
        return $str;
    }
    
    /**
     * function array params
     * @param array $matches
     * @return string
     */
    private function func_arr_params_callback($matches) {
    	$func = $matches[1];
    	$params_str = $matches[2];
        $in_matches = [];
    	preg_match_all("/([a-z_]+)\=[\"\']?([^\"\']+)[\"\']?/i", stripslashes($params_str), $in_matches, PREG_SET_ORDER);
    	foreach($in_matches as $v) {
    		$params[$v[1]] = $v[2];
    	}
    	return "<?php print_r( " . $func . "(" . ArrayHelper::toRaw($params) . ') ); ?>';
    }
    
    /**
     * add quote to matches
     * @param array $matches
     * @return string
     */
    private function addquote_callback($matches) {
        return "<?php print_r(" . $this->addquote($matches[1]) . ");?>";
    }
    
    /**
     * convert widget matches to php code
     * @param array $matches
     * @return string
     */
    
    private function widget_callback($matches){
        return $this->widget($matches[1], $matches[2], $matches[0]);
    }
    
    /**
     * end widget
     * @param array $matches
     * @return string
     */
    private function end_widget_callback($matches){
        return $this->end_widget($matches[0]);
    }
    
    /**
     * convert '//' to '/'
     *
     * @param  string $var
     * @return string
     */
    private function addquote($var)
    {
        return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var ) );
    }
    
    /**
     * parse widget tag
     * {widget:menu action="tree" cache="1|0" return="data" urlrule="..." page="$page" num="10" ...}
     * * op 为方法分类，一种是系统内置工具类 json|xml|block|get, 一种是模块名，定位到模块的widget类
     * * action 为方法名称, cache 是否缓存, return 返回数据结果的变量名, rlrule url规则, page 分页的当前页码, num 返回数据条数即pagesize  
     * * other 其他均作为非处理参数 传入widget方法中去处理
     * 
     * @param string $widget_name   标签所属模块
     * @param string $data          标签的参数，字串，需要组合为数组作为查询的设置选项
     * @param string $html          匹配到的所有的HTML代码
     */
    private function widget($widget_name, $data, $html) 
    {
        // widget class
        $action = 'index';
        if (empty($widget_name)) {
            $widget_name = 'base';
            $widgetClassName = '\\Wslim\\View\\Widget';
        } else {
            // widget:common:xxx, name is common:xxx
            if (stripos($widget_name, ':')) {
                $names = explode(':', $widget_name);
                $widget_name = $names[0];
                $action = $names[1];
            }
            
            $widgetClassName = Ioc::findClass($widget_name, 'Widget');
        }
        
        preg_match_all("/([a-zA-Z_]+)(?:\s+|(?:\=[\"]?(\d+|[^\"]+)[\"]?)?)/", stripslashes($data), $matches, PREG_SET_ORDER);
        $needHandleArr = array('action', 'return', 'cache', 'echo');     // 其他的 key=value 参数全部传入widget方法中去处理
        $str_edit = 'widget_name=' . $widget_name . '&widget_md5=' . md5(stripslashes($html)); //可视化条件
        $params = array();   // 全部参数数组
        
        // 解析 widget 的参数
        foreach($matches as $v) {
            if ($v[1] === 'num') $v[1] = 'pagesize';
            
            // 非模板处理的参数全部加入 $params[] 作为标签方法的参数去处理
            if (!isset($v[2])) {
                $params[$v[1]] = $v[2] = $v[1];
            } else {
                $params[$v[1]] = $v[2];
            }
            
            $str_edit .= $str_edit ? "&$v[1]=" . ( $widget_name == 'block' && strpos($v[2], '$') === 0 ? $v[2] : urlencode($v[2]) ) : "$v[1]=" . (strpos($v[2], '$') === 0 ? $v[2] : urlencode($v[2]));
        }
        
        // 需要处理的参数, action/return/cache 等作特殊处理
        if (isset($params['action'])) {
            $action = trim($params['action']); // action
        }
        $cache  = isset($params['cache']) ? intval($params['cache']) : 0;  // 是否使用缓存
        $return = isset($params['return']) && trim($params['return']) ? '$' . trim($params['return']) : '$data'; // 返回数据的变量名
        unset($params['action'], $params['cache'], $params['return']);
        
        $space  = '    ';
        $str    = '';
        if ($widgetClassName) {
            // action
            $str .= "if (method_exists('{$widgetClassName}', '{$action}')) {" . PHP_EOL;
            
            // cache
            if (!empty($cache) && method_exists($widgetClassName, 'cacheCall')) {
                $params['_module'] = Ioc::app()->getCurrentModule()->getName();
                $str .= $space . "{$return} = {$widgetClassName}::instance()->cacheCall(null, '{$action}', [". ArrayHelper::toRaw($params) . "]);" . PHP_EOL;
            } else {
                $str .= $space . '$_params = ' . ArrayHelper::toRaw($params) . ';' . PHP_EOL;
                $str .= $space . "{$return} = \Wslim\Ioc::widget('{$widgetClassName}')->{$action}(\$_params);" . PHP_EOL;
            }
            
            // echo 是否直接echo输出
            if (isset($params['echo'])) {
                $str .= $space . "if (is_scalar({$return})) echo {$return};" . PHP_EOL;
            }
            
            // with page, 由应用返回 Paginator 对象即可
            if (isset($params['page'])) {
                $str .= $space . "if ({$return} instanceof \Wslim\Util\Paginator) {" . PHP_EOL;
                $str .= $space . $space . "{$return} = ['paginator' => {$return}->toArray(), 'data' => {$return}->getData()];" . PHP_EOL;
                $str .= $space . "}" . PHP_EOL;
            }
            
            // 模板片段的话进行内容解析
            if ($action == 'fragment') {
                $str .= $space . "include \$this->getFragmentFile({$return}['file'], {$return}['content']);" . PHP_EOL;
            }
            
            $str .= '}' . PHP_EOL;
        } else {
            $message = sprintf('widget[%s/%s] is not found.', $widget_name, $action);
            $str = "echo '<div>{$message}</div>'";
        }
        
        // output widget code
        $str = '<?php ' . PHP_EOL . "// widget: $widget_name/$action \n" 
                //. 'if(defined("IN_ADMIN") && !defined("HTML")) {' . PHP_EOL
                //. '    echo \'<div class="admin_piao" data="'.$str_edit.'">\';' . PHP_EOL
                //. '    echo \'    <a href="javascript:void(0)" class="admin_piao_edit">'.($widgetname=='block' ? Ioc::lang('block_add') : lang('edit')) . '</a>\';' . PHP_EOL
                //. '}' . PHP_EOL
                . $str . '?>';
        
        return $str;
    }
    
    /**
     * 标签结束
     */
    private function end_widget() 
    {
        return '';
        
        return '<?php ' . PHP_EOL . 'if(defined("IN_ADMIN") && !defined("HTML")) { echo "</div>"; } ' . PHP_EOL
                . '// end widget' . PHP_EOL . '?>';
    }
    
}

