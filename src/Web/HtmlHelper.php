<?php
namespace Wslim\Web;

use Wslim\Util\StringHelper;
use Wslim\Ioc;
use Wslim\Common\Config;
use Wslim\Util\DataHelper;

/**
 * html helper
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class HtmlHelper
{
    /**
     * charset
     * @var string
     */
    static public $charset = 'utf-8';

    /**
     * 获取广告显示类型的代码与名称键值对数组，包括 image,slide,fixed,float,couplet,pop_right_bottom,pop_left_bottom',
     */
    static public function getAdDisplayTypes()
    {
        return array(
            'image'     => '单个图片',
            'slide'     => '轮播图',
            'fixed'     => '固定位置',
            'float'     => '飘窗式',
            'couplet'   => '对联式',
            'popup'     => '默认弹出',
            'pop_right_bottom'  => '右下角弹出',
            'pop_left_bottom'   => '左下角弹出式'
        );
    }
    
    /**
     * format options
     * @param  array $settings
     * @return array
     */
    static public function formatSettings($settings)
    {
        if (isset($settings) && is_string($settings)) {
            $settings['class'] = $settings;
        }
        
        $settings['style'] = isset($settings['style']) ? $settings['style'] : '';
        if (isset($settings['width'])) {
            $settings['style'] .= ';width:' . $settings['width'] . ';max-width:' . $settings['width'] . ';';
        }
        if (isset($settings['height'])) {
            $settings['style'] .= ';height:' . $settings['height'] . ';';
        }
        $settings['style'] = trim(str_replace(';;', ';', $settings['style']), ';');
        
        return $settings;
    }
    
    /**
     * sub str, UTF8/GBK
     * @param  string $string
     * @param  int    $length
     * @param  string $dot
     * @return string
     */
    static public function str_cut($string, $length=4, $dot = '', $charset='utf-8')
    {
        return StringHelper::str_cut(DataHelper::filter_html($string), $length, $dot, $charset);
    }
    
    /**
     * summary 
     * @param  string $string
     * @param  number $length
     * @param  string $dot
     * @param  string $charset
     * @return string
     */
    static public function summary($string, $length=100, $dot = '...', $charset='utf-8')
    {
        return StringHelper::str_cut(DataHelper::filter_html($string), $length, $dot, $charset);
    }
    
    /**
     * multi line, line break replace to <br>
     * @param  string $value
     * @return string
     */
    static public function multiline($value)
    {
        return str_replace(PHP_EOL, '<br>', $value);
    }
    
    /**
     * content html
     * @param  string $value
     * @return string
     */
    static public function content($value)
    {
        return $value;
    }
    
    /**
     * datetime format
     * @param  mixed  $timestamp int or string
     * @param  string $format '%Y-%m-%d %H:%M:%S'
     * @return string
     */
    static public function datetime($timestamp, $format=null)
    {
        return DataHelper::datetime($timestamp, $format);
    }
    
    static public function date($timestamp, $format=null)
    {
        $format = $format ? : '%Y-%m-%d';
        return DataHelper::datetime($timestamp, $format);
    }
    
    /**
     * short date 
     * @param  mixed $timestamp int or string
     * @return string
     */
    static public function shortdate($timestamp)
    {
        return DataHelper::datetime($timestamp, '%m-%d');
    }
    
    /**
     * short time
     * @param  mixed $timestamp int or string
     * @return string
     */
    static public function shorttime($timestamp)
    {
        return DataHelper::datetime($timestamp, '%H:%M');
    }
    
    /**
     * short time
     * @param  mixed $timestamp int or string
     * @return string
     */
    static public function shortdatetime($timestamp)
    {
        return DataHelper::datetime($timestamp, '%m-%d %H:%M');
    }
    
    static public function price($value)
    {
        if (!is_numeric($value)) {
            $value = floatval($value);
        }
        
        $value = round($value, 2);
        
        return $value;
    }
    
    /**
     * image html
     * @param  string       $src can be more 'imgsrc1, imgsrc2'
     * @param  string|array $settings if string trait as class
     * 
     * @return string
     */
    static public function image($src, $settings=null)
    {
        $isBase64 = $src && strpos($src, 'data:image') !== false;
        if (!empty($src)) {
            if (!$isBase64 && strpos($src, ',') !== false) {
                $srcs = StringHelper::toArray($src, ',');
                $src = empty($srcs[0]) ? $srcs[1] : $srcs[0];
            }
        }
        if (!$src) {
            $src = Config::getRootUrl(). '/images/nopic.gif';
        } else {
            if (strpos($src, 'http:') === false && !$isBase64 ) {
                if (strpos($src, '/images') === 0) {
                    $src = Config::getRootUrl() . $src;
                } elseif (strpos($src, ':') === false) {
                    $src = Config::getUploadFileUrl($src);
                }
            }
        }
        
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        if (!$isBase64 && !in_array($ext, ['gif', 'jpe', 'jpeg', 'jpg', 'png'])) {
            return '<a href="' . $src . '" target="_blank">查看</a>';
        }
        
        $settings = static::formatSettings($settings);
        $class = isset($settings['class']) ? $settings['class'] : ''; // img-responsive
        $class && $class = ' class="' . $class . '"';
        $style = isset($settings['style']) && $settings['style'] ? $settings['style'] : ($class ? '' : 'max-width:100%;max-height:100%');
        $style && $style = ' style="' . $style . '"';
        
        $idstr = isset($settings['id']) && $settings['id'] ? ' id="' . $settings['id'] . '"' : '';
        
        $str = '<img src="' . $src . '"' . $idstr . $class . $style . '>';
        if (isset($settings['url'])) {
            $str = '<a href="' . $settings['url'] . '">' . $str . '</a>';
        }
        
        return $str;
    }
    
    /**
     * 
     * @param  string       $src
     * @param  string|array $settings
     * @return string
     */
    static public function lazy_image($src, $settings=null)
    {
        $settings = static::formatSettings($settings);
        $settings['class'] = isset($settings['class']) ? $settings['class'] . ' lazy' : 'lazy';
        $str = static::image($src, $settings);
        return str_replace('src=', 'data-original=', $str);
    }
    
    /**
     * create html attributes
     * @param array $attributes
     * @return string
     */
    static public function attributes($attributes)
    {
        $compiled = '';
        foreach($attributes as $key => $value) {
            if ($value === NULL) {
                // Skip attributes that have NULL values
                continue;
            }
            if (is_numeric($key)) {
                // Assume non-associative keys are mirrored attributes
                $key = $value;
            }
            // Add the attribute value
            $compiled .= ' '.$key.'="' . htmlspecialchars( (string) $value, ENT_QUOTES, self::$charset, true) . '"';
        }
        return $compiled;
    }
    
    /**
     * html style link 
     * @param string $file
     * @param array  $attributes
     * @param string $protocol
     * @param string $index
     * @return string
     */
    public static function style($file, array $attributes = NULL, $protocol = NULL, $index = FALSE)
    {
        if (strpos($file, '://') === FALSE)
        {
            // Add the base URL
            $file = Ioc::url($file);
        }
    
        // Set the stylesheet link
        $attributes['href'] = $file;
    
        // Set the stylesheet rel
        $attributes['rel'] = 'stylesheet';
    
        // Set the stylesheet type
        $attributes['type'] = 'text/css';
        
        return '<link' . self::attributes($attributes) .' />';
    }
    
    /**
     * html script
     * @param string $file
     * @param array  $attributes
     * @param string $protocol
     * @param string $index
     * @return string
     */
    static public function script($file, array $attributes = NULL, $protocol = NULL, $index = FALSE)
    {
        if (strpos($file, '://') === FALSE)
        {
            // Add the base URL
            $file = Ioc::url($file);
        }
    
        // Set the script link
        $attributes['src'] = $file;
    
        // Set the script type
        $attributes['type'] = 'text/javascript';
    
        return '<script'. self::attributes($attributes).'></script>';
    }
    
    /**
     * 返回批量读取css文件的 link 标签html，支持min机制
     * @param array  $filelist  加载的css文件列表
     * @param string $relative_dir 相对目录，默认为空，直接从 static 目录加载
     * @param bool $min 是否使用min合并生成机制
     * @return string link html
     * @desc 加载css文件.
     */
    static public function css($filelist, $relative_dir = '', $min = true)
    {
        return static::fileElement('css', $filelist, $relative_dir, $min);
    }
    
    /**
     * 返回批量读取js文件的 script 标签html，支持min机制
     * @param array  $filelist 要加载的js文件列表
     * @param string $relative_dir 相对目录，默认为空，直接从 static 目录加载
     * @param bool   $minjs 是否使用min合并生成机制
     * @return string
     */
    public static function js($filelist, $relative_dir = '', $min = true)
    {
        return static::fileElement('js', $filelist, $relative_dir, $min);
    }

    /**
     * 返回批量读取文件的标签，支持 css/js/html，支持min机制
     * @param string $filetype
     * @param array  $filelist
     * @param string $relative_dir
     * @param bool $min
     * @return string
     */
    static private function fileElement($filetype, $filelist, $relative_dir = '', $min = true)
    {
        if ($relative_dir) $relative_dir = rtrim($relative_dir, '/');
        $filearr = explode(',', $filelist);
        $filelist = array();
        $out = '';
        if (strpos($relative_dir, 'http') === 0) {  // 'https://path'
            $basePath = '';
            $baseUrl  = '';
        } elseif (strpos($relative_dir, '/') === 0) {   // '/path' 以/开头
            $basePath = Config::getWebRootPath() . $relative_dir  . '/';
            $baseUrl  = $relative_dir . '/';
        } else {    // 'dir/path' 不是以/开头
            $name = Ioc::web()->getCurrentModule()->getName();
            $basePath = Config::getWebRootPath() . '/' . $name . $relative_dir  . '/';
            $baseUrl  = $name . '/' . $relative_dir . '/';
            $baseUrl = str_replace('//', '/', $baseUrl);
        }
        
        foreach ($filearr as $file) {
            $file = trim($file);
            $absfile = $basePath . "$file"; // 文件绝对路径
            $file = $baseUrl . "$file";     // 相对app的url，
            
            if (file_exists($absfile)) {
                $filelist[] = $file;
            } else {
                Ioc::logger()->error('file is not exists: ' . $absfile);
            }
        }
        
        if (!empty($filelist)) {
            // 如果开启合并，使用minify机制加载
            if ($min) {
                $f = implode(',', $filelist);
                $src = Config::getRootUrl() . '/minify/?f='.$f;
                if ($filetype == 'css') {
                    $out = '<link type="text/css" href="' . $src . '" rel="stylesheet"  />' . "\r\n";
                } elseif ($filetype == 'js') {
                    $out = '<script type="text/javascript" src="' . $src . '"></script>' . "\r\n";
                } else {
                    $out = '';
                }
            } elseif ($filetype == 'css' || $filetype == 'js') {
                $method = $filetype == 'js' ? 'script' : $filetype;
                
                foreach ($filelist as $file) {
                    $out .= self::$method($css) . "\r\n";
                }
            }
        }
        return $out;
    }
    
    /**
     * html list
     * @param array $data data-item: ['url'=>, 'title'=>, 'items'=>, ]
     * @param array $settings        ['type'=>'list|tree|menu', 'ul_css'=>, 'li_css'=>, 'sub_ul_css'=>, 'sub_li_css'=>, 'fields'=>['id', 'parent_id', 'title'], 'checked'=>[]]
     * @return string
     */
    static public function list($data, $settings=array())
    {
        if (!$data) return '';
        
        if (!isset($settings['checked']))   $settings['checked']     = array();
        if (!isset($settings['title_len'])) $settings['title_len']   = 15;
        if (!isset($settings['type']))      $settings['type'] = 'list';
        
        $out = $pre_out = $suf_out = '';
        switch ($settings['type']) {
            case 'tree':
                if (!isset($settings['ul_css']))     $settings['ul_css']        = "nav nav-list";
                if (!isset($settings['sub_ul_css'])) $settings['sub_ul_css']    = "submenu nav-show";
                
                $pre_out = '<ul class="' . $settings['ul_css'] . '">' . PHP_EOL;
                foreach ($data as $v) {
                    $title = isset($v['title']) ? StringHelper::str_cut($v['title'], $settings['title_len']) : '标题';
                    $url   = isset($v['url']) ? $v['url'] : 'javascript:;';
                    
                    $li_css = '';
                    if ($settings['checked'] && in_array($v[$settings['fields'][0]], $settings['checked'])) {
                        $li_css = ' class="checked"';
                    }
                    
                    $out .= '<li' . $li_css . '>' . PHP_EOL;
                    $out .= '    <a class="dropdown-toggle" data-toggle="dropdown" href="' . $url . '">' . PHP_EOL;
                    $out .= '        <i class="menu-icon fa fa-caret-right"></i>' . PHP_EOL;
                    $out .= '        ' . $title . PHP_EOL;
                    $out .= '        <b class="arrow fa fa-angle-down"></b>' . PHP_EOL;
                    $out .= '    </a>' . PHP_EOL;
                    
                    if (isset($v['items']) && $v['items']) {
                        $settings['ul_css'] = $settings['sub_ul_css'];
                        $out .= static::list($v['items'], $settings);
                    }
                    
                    $out .= '</li>' . PHP_EOL;
                }
                $suf_out .= '</ul>' . PHP_EOL;
                break;
            case 'menu':
                if (!isset($settings['ul_css']))     $settings['ul_css']      = "nav navbar-nav";
                if (!isset($settings['li_css']))     $settings['li_css']      = "dropdown";
                if (!isset($settings['sub_ul_css'])) $settings['sub_ul_css']  = "dropdown-menu";
                if (!isset($settings['sub_li_css'])) $settings['sub_li_css']  = "dropdown-submenu";
                
                $pre_out = '<ul class="' . $settings['ul_css'] . '">' . PHP_EOL;
                foreach ($data as $v) {
                    $title = isset($v['title']) ? StringHelper::str_cut($v['title'], $settings['title_len']) : '标题';
                    $url   = isset($v['url']) ? $v['url'] : 'javascript:;';
                    
                    $out  .= '<li class="' . $settings['li_css'] . '">' . PHP_EOL;
                    if (isset($v['items']) && $v['items']) {
                        $url   = 'javascript:;';  
                        $out  .= '    <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" href="' . $url . '">' . $title . '<span class="caret"></span></a>' . PHP_EOL;
                        
                        $coptions = $settings;
                        $coptions['ul_css'] = $settings['sub_ul_css'];
                        $coptions['li_css'] = $settings['sub_li_css'];
                        $out .= static::list($v['items'], $coptions);
                    } else {
                        $out  .= '    <a href="' . $url . '">' . $title . '</a>' . PHP_EOL;
                    }
                    $out .= '</li>' . PHP_EOL;
                }
                $suf_out .= '</ul>' . PHP_EOL;
                break;
            case 'list':
            default:
                foreach ($data as $v) {
                    $title = isset($v['title']) ? StringHelper::str_cut($v['title'], $settings['title_len']) : '标题';
                    $url   = isset($v['url']) ? $v['url'] : 'javascript:;';
                    
                    $out  .= '<a href="' . $url . '">' . $title . '</a>' . PHP_EOL;
                }
                break;
        }
        
        return $pre_out . $out . $suf_out;
    }
    
    /**
     * html menu
     * @param array $data data-item: ['url'=>, 'title'=>, 'items'=>, ]
     * @param array $settings        ['type'=>'list|tree|menu', 'ul_css'=>, 'li_css'=>, 'sub_ul_css'=>, 'sub_li_css'=>, 'fields'=>['id', 'parent_id', 'title'], 'checked'=>[]]
     * @return string
     */
    static public function menu($data, $settings=array())
    {
        if(!isset($settings['type'])) $settings['type'] = 'menu';
        return static::list($data, $settings);
    }
    
    /**
     * html tree
     * @param array $data data-item: ['url'=>, 'title'=>, 'items'=>[]]
     * @param array $settings
     * @return string
     */
    static public function tree($data, $settings=array())
    {
        if(!isset($settings['type'])) $settings['type'] = 'tree';
        return static::list($data, $settings);
    }
    
    /**
     * html slide
     * @param  array $data
     * @param  array $settings ['fields'=>['thumb', 'title', 'url'], 'css'=>'..', 'style'=>'...']
     * @return string
     */
    static public function slide($data, $settings=array())
    {
        if (empty($data)) return '';
        
        if (!isset($settings['id'])) {
            $settings['id'] = 'slide_' . mt_rand(100000,999999);
        }
        if (!isset($settings['fields'])) {
            $settings['fields'] = array('thumb', 'title', 'url');
        } elseif (is_string($settings['fields'])) {
            $settings['fields'] = explode(',', $settings['fields']);
        }
        $thumb_key = isset($settings['fields'][0]) ? $settings['fields'][0] : 0;
        $title_key = isset($settings['fields'][1]) ? $settings['fields'][1] : null;
        $url_key = isset($settings['fields'][2]) ? $settings['fields'][2] : null;
        $css_class = isset($settings['css']) ? 'slidebox ' . $settings['css'] : 'slidebox';
        $style = isset($settings['style']) ? $settings['style'] . ';' : '';
        $style .= isset($settings['height']) ? 'height:' . $settings['height'] . ';' : 'heigth:auto;';
        
        // format data
        if (!is_array($data)) {
            $data = explode(',', $data);
        }
        if ($data) foreach ($data as $k => $v) {
            if (!$v || !isset($v[$thumb_key]) || !$v[$thumb_key]) {
                unset($data[$k]);
                continue;
            }
            if (is_string($v)) {
                $data[$k] = (array) trim($v);
            }
        }
        
        $content = '<div id="' . $settings['id'] . '" class="' . $css_class . '" style="' . $style . '">' . PHP_EOL;
        if (count($data) == 1) {
            $v = current($data);
            $img_src = Ioc::url('upload:' . $v[$thumb_key]);
            $title = $title_key && isset($v[$title_key]) ? $v[$title_key] : '';
            $content .= '    <ul>' . PHP_EOL;
            if ($url_key && isset($v[$url_key]) && !empty($v[$url_key])){
                $content .= '        <li><a href="' . $v[$url_key] . '" target="_blank"><img data-original="'. $img_src . '" src="' . $img_src . '" alt="'. $title .'"></a>';
            }else{
                $content .= '        <li><a href="javascript:void(0)"><img data-original="' . $img_src . '" src="' . $img_src . '" alt="'. $title .'"></a>';
            }
            if ($title) $content .= '        <span class="emerge_bottom">' . StringHelper::str_cut($title, 15) . '</span>' . PHP_EOL;
            $content .= '            </li></ul>' . PHP_EOL;
        } else {
            $content .= '    <div class="hd">' . PHP_EOL;
            $content .= '        <ul>' . PHP_EOL;
            if ($data) foreach ($data as $k => $v) {
                $content .= '        <li></li>' . PHP_EOL;
            }
            $content .= '        </ul>' . PHP_EOL;
            $content .= '    </div>' . PHP_EOL;
            
            $content .= '    <div class="bd">' . PHP_EOL;
            $content .= '        <ul>' . PHP_EOL;
            if ($data) foreach ($data as $k => $v) {
                $img_src = Config::getUploadFileUrl($v[$thumb_key]);
                $title = $title_key && isset($v[$title_key]) ? $v[$title_key] : '';
                if ($url_key && isset($v[$url_key]) && !empty($v[$url_key])){
                    $content .= '        <li><a href="' . $v[$url_key] . '" target="_blank"><img data-original="' . $img_src . '" src="' . $img_src . '" alt="'. $title .'"></a></li>' . PHP_EOL;
                }else{
                    $content .= '        <li><a href="javascript:void(0)"><img data-original="' . $img_src . '" src="' . $img_src . '" alt="'. $title .'"></a></li>' . PHP_EOL;
                }
            }
            $content .= '        </ul>' . PHP_EOL;
            $content .= '    </div>' . PHP_EOL;
            $content .= '    <div class="ifocus_opdiv"></div>' . PHP_EOL;
            
            if ($title_key) {
                $content .= '    <div class="ifocus_tx">' . PHP_EOL;
                $content .= '        <ul>' . PHP_EOL;
                if ($data) foreach ($data as $k => $v) {
                    if (isset($v[$url_key]) && !empty($v[$url_key])){
                        $content .= '        <li><a href="' . $v[$url_key] . '" target="_blank">' . StringHelper::str_cut($v[$settings['fields'][1]], 32) . '</a></li>' . PHP_EOL;
                    } else {
                        $content .= '        <li><a href="javascript:void(0)">' . StringHelper::str_cut($v[$settings['fields'][1]], 32) . '</a></li>' . PHP_EOL;
                    }
                }
                $content .= '        </ul>' . PHP_EOL;
                $content .= '    </div>' . PHP_EOL;
            }
            
            $content .= '    <a class="prev" href="javascript:void(0)"></a>' . PHP_EOL;
            $content .= '    <a class="next" href="javascript:void(0)"></a>' . PHP_EOL;
        }
        $content .= '</div>' . PHP_EOL;
        return $content;
    }
    
    /**
     * html media list
     * @param  array $data     item: [thumb/title/description/url/id]
     * @param  array $settings keys: class, li_class
     * @return string
     */
    static public function media_list($data, $settings=null)
    {
        if ($data instanceof \Wslim\Util\Paginator) {
            $paginator = $data->toArray();
            $data = $data->getData();
        }
        
        $settings['class'] = isset($settings['class']) ? 'media-list ' . $settings['class'] : 'media-list list-group';
        $li_class = isset($settings['li_class']) ? "media " . $settings['li_class'] : "media list-group-item";
        $width = isset($settings['width']) ? $settings['width'] : '100px';
        $index = 0;
        $template = <<<INPUT
<li class="%s">
    <div class="media-left">
        <img style="width:%s" src="%s">
    </div>
    <div class="media-body">
        <h5 class="media-heading">%s</h5>
        <div class="content-description">%s</div>
    </div>
    <div class="content-op" style="display:none;">
        <input type="button" class="btn btn-primary btn-sm edit-content" value="编辑">
        <input type="button" class="btn btn-primary btn-sm delete-content" value="删除">
    </div>
</li>
INPUT;
        $noimg_tpl = <<<INPUT
<li class="%s">
    <div class="media-body">
        <h5 class="media-heading">%s</h5>
        <div class="content-description">%s</div>
    </div>
    <div class="content-op" style="display:none;">
        <input type="button" class="btn btn-primary btn-sm edit-content" value="编辑">
        <input type="button" class="btn btn-primary btn-sm delete-content" value="删除">
    </div>
</li>
INPUT;
        if (!isset($settings['fields'])) {
            $settings['fields'] = array('thumb', 'title', 'description', 'url');
        } elseif (is_string($settings['fields'])) {
            $settings['fields'] = explode(',', $settings['fields']);
        }
        $thumb_key = isset($settings['fields'][0]) ? $settings['fields'][0] : 'thumb';
        $title_key = isset($settings['fields'][1]) ? $settings['fields'][1] : 'title';
        $desc_key = isset($settings['fields'][2]) ? $settings['fields'][2] : 'description';
        $url_key = isset($settings['fields'][3]) ? $settings['fields'][3] : 'url';
        
        $str = '<ul class="' . $settings['class'] . '">' . PHP_EOL;
        if (isset($data) && is_array($data)) foreach ($data as $k => $v) {
            $img_src = isset($v[$thumb_key]) ? $v[$thumb_key] : '';
            $img_src && $img_src = Config::getUploadFileUrl($img_src);
            
            $title = isset($v[$title_key]) ? $v[$title_key] : '';
            $description = isset($v[$desc_key]) ? $v[$desc_key] : '';
            $url = isset($v[$url_key]) ? $v[$url_key] : '';
            $id  = isset($v['id']) ? $v['id'] : 0;
            
            if ($img_src) {
                $str .= sprintf($template, $li_class, $width, $img_src, $title, $description);
            } else {
                $str .= sprintf($noimg_tpl, $li_class, $title, $description);
            }
            
            $index += 1;
        }
        $str .= '</ul>' . PHP_EOL;
        
        $str .= '<style>.media.list-group-item {margin-top:0;}</style>' . PHP_EOL;
        
        return $str;
    }
    
    /**
     * 内容列表输入表单，用于生成内容图文列表
     * @param string $form_name
     * @param mixed  array|null $form_value array(0=>array('index'=>, 'title'=>, 'description'=>, 'pic_url'=>, 'url'=>))
     * @param array  $settings
     * @return string
     */
    static public function wx_contents_input($form_name, $form_value=null, $settings=null)
    {
        $settings['id']     = isset($settings['id']) ? $settings['id'] : str_replace(array('[', ']'), array('_'), $form_name);
        $index = 0;
        $template = <<<INPUT
<li style="%s" class="%s">
    <img src="%s" class="content-img">
    <div class="content-body">
        <span class="content-title">%s</span>
        <span class="content-description">%s</span>
        <input type="hidden" name="%s" value="%s" class="content-title-input">
        <input type="hidden" name="%s" value="%s" class="content-description-input">
        <input type="hidden" name="%s" value="%s" class="content-pic-url-input">
        <input type="hidden" name="%s" value="%s" class="content-url-input">
        <input type="hidden" name="%s" value="%s" class="content-id-input">
    </div>
    <div class="content-op">
        <input type="button" class="btn btn-primary btn-sm edit-content" value="编辑">
        <input type="button" class="btn btn-primary btn-sm delete-content" value="删除">
    </div>
</li>
INPUT;
        $str = '<ul class="list-unstyled contents-wrap">' . PHP_EOL;
        if (isset($form_value) && is_array($form_value)) foreach ($form_value as $k => $v) {
            $img_src = isset($v['pic_url']) ? $v['pic_url'] : '';
            $title = isset($v['title']) ? $v['title'] : '';
            $description = isset($v['description']) ? $v['description'] : '';
            $url = isset($v['url']) ? $v['url'] : '';
            $id  = isset($v['id']) ? $v['id'] : 0;
            $str .= sprintf($template
                , '', $settings['id'] . '-content-wrap-' . $index
                , $img_src, $title, $description
                , $settings['id'] . '['. $index . '][title]', $title
                , $settings['id'] . '['. $index . '][description]', $description
                , $settings['id'] . '['. $index . '][pic_url]', $img_src
                , $settings['id'] . '['. $index . '][url]', $url
                , $settings['id'] . '['. $index . '][id]', $id
            );
            $index += 1;
        }
        $str .= sprintf($template
            , 'display:none;', $settings['id'] . '-content-wrap-' . 'x'
            , '', '', ''
            , $settings['id'] . '['. 'x' . '][title]', ''
            , $settings['id'] . '['. 'x' . '][description]', ''
            , $settings['id'] . '['. 'x' . '][pic_url]', ''
            , $settings['id'] . '['. 'x' . '][url]', ''
            , $settings['id'] . '['. 'x' . '][id]', 0
        );
        $str .= '</ul>' . PHP_EOL;
        
        $str .= '<div style="margin-top:15px;">'. PHP_EOL;
        $str .= '    <input type="button" class="btn btn-warning btn-sm select-content" data-id="' . $settings['id'] . '" value="选择图文列表">';
        $str .= '    <input type="button" class="btn btn-warning btn-sm add-content" data-id="' . $settings['id'] . '" value="自定义图文列表">';
        $str .= '</div>'. PHP_EOL;
        
        //if (!static::$isLoadedImageText) {
        $select_url = isset($settings['modal_url']) ? $settings['modal_url'] : Ioc::url('common/data/select_content');
            $innerJs = <<<EOF
                <script>
                require(["util"], function(){
                    var index = "{$index}";
                	$(".contents-wrap").on("click", ".delete-content", function() {
                	    //console.log($(this));
                        $(this).parent().parent("li").remove();
                        return false;
                    });
                    $(".select-content").on("click", function() {
                        var tid = $(this).attr('data-id');
                        
                        var dlg = util.dialog.show({
                            width: '800px',
                    		title: '选择图文内容',
                    		url: "{$select_url}",
                    		okValue: '确 定',
                    		ok: false,
                    		cancelValue: '取消',
                    		cancel: null,
                    		cancelDisplay: true
                    	});
                    	
                        dlg.addEventListener('close', function () {
                            if (dlg.returnValue.data) {
                                console.log(dlg.returnValue);
                                data = dlg.returnValue.data;
                                if (data.length > 0) {
                    		        for (dindex in data) {
                    		            var item_index = Number(dindex) + Number(index);
                                        var tpl_ele = $("." + tid + '-content-wrap-x');
                                        var html = tpl_ele.html().replace(/\[x\]/g, '[' + item_index + ']');
                                        $('<li style="text-align:center;" class="' + tid + '-content-wrap-' + item_index + '">').insertBefore(tpl_ele).html(html);
                                        var ele = $("." + tid + '-content-wrap-' + item_index);
                                        ele.find(".content-img").attr('src', data[dindex]["pic_url"]).show();
                    		            ele.find(".content-title").html(data[dindex]["title"]);
                    		            ele.find(".content-description").html(data[dindex]["description"]);
                    		    
                                        ele.find(".content-title-input").val(data[dindex]["title"]);
                    		            ele.find(".content-description-input").val(data[dindex]["description"]);
                    		            ele.find(".content-pic-url-input").val(data[dindex]["pic_url"]);
                    		            ele.find(".content-url-input").val(data[dindex]["url"]);
                                    }
                    		        index = Number(index) + data.length;
                                }
                            }
                    	});
                        
                    });
                });
                </script>
EOF;
            $str .= $innerJs;
            // 动态的添加该输入框时脚本不能作用，暂时先重复脚本生成
            //static::$isLoadedImageText = true;
        //}
        return $str;
    }
    
    /**
     * magic method, 未找到的静态方法使用 input 默认处理字段值
     * notice:
     *     1 calling the method directly is faster then call_user_func_array() !
     *     2 $params 是包装的数组，需要提取出来再传值
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    static public function __callStatic($method, $params)
    {
        return call_user_func_array(array('\Wslim\Db\FieldOutputHandler', $method), $params);
    }
}
