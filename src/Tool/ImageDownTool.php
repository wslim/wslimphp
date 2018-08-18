<?php
namespace Wslim\Tool;

use Wslim\Util\HttpRequest;
use Wslim\Util\UriHelper;
use Wslim\Util\DataHelper;
use Wslim\Ioc;
use Wslim\Common\Config;

class ImageDownTool
{
    static public function parseUrl($urls, $replace=null)
    {
        return UriHelper::pregReplaceUrl($urls);
    }
    
    static public function getImages($urls, $options=null, $filter=null)
    {
        // replace: id="1-3,5" name="xxx"
        $replace = $options && is_string($options) ? $options : (isset($options['replace']) && $options['replace'] ? $options['replace'] : null);
        // 处理url
        $newUrls = UriHelper::pregReplaceUrl($urls, $replace);
        
        $imgs = [];
        foreach ($newUrls as $url) {
            $http = HttpRequest::instance($url); 
            $str = $http->getResponseText();
            
            // error
            if ($errmsg = $http->getErrorString()){
                static::errorHandle($errmsg);
                continue;
            }
            
            if ($str) {
                $t = static::parseImageHtml($str, isset($filter['include']) ? $filter['include'] : null); 
                
                // format img src
                if ($t) foreach ($t as $k=>$v) {
                    $t[$k]['src'] = UriHelper::formatUrl($v['src'], explode(':', $url)[0]);
                }
                
                $imgs = array_merge($imgs, $t);
            }
        }
        
        if ($imgs) foreach ($imgs as $k => $v) {
            
            // 获取文件尺寸
            //$info = static::getImageInfo($v['src']);
            //if ($info) $imgs[$k] = array_merge($imgs[$k], $info);
        }
        
        if ($filter) {
            $imgs = static::filterImages($imgs, $filter);
        }
        
        return $imgs;
    }
    
    static public function filterImages($imgs, $filter)
    {
        if (!$imgs) return $imgs;
        
        foreach ($imgs as $k=>$v) {
            if (isset($filter['width']) && isset($v['width'])) {
                if ($v['width'] < intval($filter['width'])) {
                    unset($imgs[$k]);
                }
            }
            
            if (isset($filter['height']) && isset($v['height'])) {
                if ($v['height'] < intval($filter['height'])) {
                    unset($imgs[$k]);
                }
            }
            
            if (isset($filter['size']) && isset($v['size'])) {
                if (isset($v['size']) && $v['size'] && $v['size'] < intval($filter['size'])) {
                    unset($imgs[$k]);
                }
            }
            
            if (isset($filter['include']) && $filter['include']) {
                $filter['include'] = str_replace("\"", "\'", $filter['include']);
                $str = "";
                foreach ($v as $name=>$value) {
                    if ($name == 'page_title') continue;
                    
                    $str .= "$name=\'$value\' ";
                }
                if (strpos($str, $filter['include']) === false) {
                    unset($imgs[$k]);
                }
            }
        }
        
        return $imgs;
    }
    
    
    
    static public function parseImageHtml($str, $filter=null)
    {
        // 先对内容转码
        $str = static::autoConvertEncoding($str);
        
        $imgs = [];
        
        $str = str_replace("\r\n", ' ', $str);
        $str = str_replace("\n", ' ', $str);
        
        $pattern = '/\<title[^\>]*\>([^\<]+)/i'; 
        preg_match($pattern, $str, $matches); 
        $page_title = isset($matches[1]) ? preg_split('/[\-\s]+/', $matches[1], 2)[0] : null;
        
        $pattern = '/\<img\s+([^\>]+)+\>/i';
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
        
        // filter include text
        $include = $filter && is_string($filter) ? $filter : (isset($filter['include']) && $filter['include'] ? $filter['include'] : null);
        
        if ($matches) foreach ($matches as $k => $item) {
            // 使用包含文本过滤
            if ($include && strpos($item[0], $filter['include']) === false) {
                continue;
            }
            
            if (isset($item[1]) && $item[1]) {
                $pattern2 = '/(\w+)\=[\"\']?([^\"\']+)[\"\']?/';
                preg_match_all($pattern2, $item[1], $matches2, PREG_SET_ORDER);
                
                if ($matches2) foreach ($matches2 as $props) {
                    if (!isset($props[1])) continue;
                    
                    $imgs[$k][strtolower($props[1])] = isset($props[2]) ? trim($props[2]) : '';
                }
            }
        }
        
        // 做些基本处理
        if ($imgs) foreach ($imgs as $k => $v) {
            if (!isset($v['src'])) {
                unset($imgs[$k]);
                continue;
            }
            
            $names = explode('/', $v['src']);
            $imgs[$k]['filename'] = $names[count($names) - 1];
            
            if (!isset($v['title'])) {
                $imgs[$k]['title'] = isset($v['alt']) ? $v['alt'] : $imgs[$k]['filename'];
            }
            
            if ($page_title) {
                $imgs[$k]['page_title'] = $page_title;
            }
            
        }
        
        return $imgs;
    }
    
    /**
     * 如果是http文件，不能返回文件大小
     * @param  string $path 为图像路径
     * @return array
     */
    static public function getImageInfo($path)
    {
        // getimagesize 返回一个具有四个单元的数组。
        // 索引 0 包含图像宽度的像素值，索引 1 包含图像高度的像素值。
        // 索引 2 是图像类型的标记：1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
        $img_info = @getimagesize($path);
        
        if (!$img_info) {
            return null;
        }
        
        $types = [1=>'GIF', 2=>'JPG', 3=>'PNG', 4=>'SWF', 5=>'PSD', 6=>'BMP', 7=>'TIFF', 8=>'TIFF', 9=>'JPC',10=>'JP2',11=>'JPX',12=>'JB2',13=>'SWC',14=>'IFF',15=>'WBMP',];
        
        return array(
            "width"     => $img_info[0],
            "height"    => $img_info[1],
            "type"      => isset($types[$img_info[2]]) ? $types[$img_info[2]] : '',
            "size"      => file_exists($path) ? filesize($path) : 0
        );
    }
    
    
    static private function autoConvertEncoding($str)
    {
        if (strpos($str, 'html')) {
            $pattern = '/\<meta[^\>]*(charset[^\>]+(GBK|UTF-8|GB2312|ISO-8849-1)[^\>]*)\>/i';
            preg_match($pattern, $str, $matches);
            $encoding = isset($matches[2]) ? strtoupper($matches[2]) : null;
        }
        
        if (!isset($encoding)) $encoding = mb_detect_encoding($str); 
        if ($encoding !== 'UTF-8') {
            $str = iconv($encoding, 'UTF-8', $str);
        }
        return $str;
    }
    
    static public function saveImages($imgs, $save_dir, $options=null)
    {
        $save_dir = static::getSaveDir($save_dir);
        $save_name = isset($options['save_name']) ? $options['save_name'] : 'title+filename';
        
        foreach ($imgs as $v) {
            if (isset($f['filename'])) {
                $filename = $v['filename'];
            } else {
                $names = explode('/', $v['src']);
                $filename = $names[count($names) - 1];
            }
            if ($save_name == 'title+filename' && isset($v['title']) && $v['title'] !== $filename) {
                $filename = preg_replace('/\s+/u', '_', $v['title']) . '-' . $filename;
            }
            
            if ($save_name == 'page_title+filename' && isset($v['page_title']) && $v['page_title']) {
                $filename = DataHelper::formatPath($v['page_title']) . '-' . $filename;
            }
            
            if (!$filename) {
                return ['errcode'=>-1, 'errmsg'=>'文件命名方式不正确'];
            }
            
            $filename = $save_dir . '/' . $filename;
            $res = HttpRequest::getArray($v['src'], null, ['savePath' => $filename]);
            if (isset($res['errcode']) && $res['errcode']) {
                return $res;
            }
        }
        
        return ['errcode'=>0, 'errmsg'=>'保存成功，共' . count($imgs) . '个文件'];
    }
    
    static protected function getSaveDir($save_dir)
    {
        return Config::getUploadPath() . DataHelper::formatPath($save_dir);
    }
    
    static protected function errorHandle($errmsg)
    {
        Ioc::logger('imagedown')->error($errmsg);
    }
    
}



