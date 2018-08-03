<?php 
namespace Wslim\Image;

use Wslim\Common\FactoryTrait;
use Wslim\Common\ErrorInfo;
use Wslim\Constant\Position;

/**
 * ImageHelper
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class ImageHelper
{
    
    use FactoryTrait;
    
    /**
     * allowed file exts
     * @var string
     */
    private $allowedExts = 'jpg,gif,png,jpeg,zip,rar,mp3,mp4,wmv,avi,doc';
    
    /**
     * allowed file size
     * @var string
     */
    private $allowedSize = '10240000';  // 10M
    
    /**
     * image handle instance
     * @var ImageInterface
     */
    private $obj;
    
    /**
     * config
     * @var array
     */
    private $config = array (
        'upload_path'   => 'upload',
        'remote_image'  => false,
        'remote_upload' => '',
        'img_domain'    => '',
        'ftp_host'      => '',
        'ftp_port'      => '',
        'ftp_user'      => '',
        'ftp_pwd'       => '',
        'watermark'     => array (
            'enabled'   => '0',
            'readonly'  => 1,
            'type'      => 'text',  // text/image
            'text'      => 'wslim.cn',
            'image'     => 'path/mark.png',
            'position'      => null, // 1) 1-9,9宫格的取值之一, 2) 20|50, x|y坐标, 3) left|bottom x|y位置
            'font_size'     => '16',
            'font_color'    => 'rgb(0,0,0)',
            'font_path'     => 'font/msvistahei.ttf',
            'diaphaneity'   => NULL,
        ),
    );

    /**
     * construct 
     * 
     * @param mixed $config string|array, if string it is upload_path
     */
    public function __construct($config=null)
    {
        if (isset($config)) {
            if (is_array($config)) {
                $this->config = array_merge($this->config, $config);
            } elseif (is_string($config) && !empty($config)) {
                $this->config['upload_path'] = $config;
            }
        }
        
        if ($this->config['remote_image']) {
            $this->obj = new RemoteImage($this->config['ftp_host'], $this->config['ftp_user'], $this->config['ftp_pwd'], $this->config['ftp_port']);
        } else {
            $this->obj = new LocalImage();
        }
    }

    /**
     * get all sub dirs
     * 
     * @param  string $path
     * @return array [['path'=>, 'val'=>]]
     */
    public function getDirs($path='')
    {
        $dirpath = str_replace('\\', '/', $this->config['upload_path'] . '/' . str_replace($this->config['upload_path'], '', $path));
        $data = $this->obj->getDirs($dirpath);
        
        foreach ($data as $k => $v) {
            $v = str_replace(array($this->config['upload_path'], '\\'), array('', '/'), $v);
            $data[$k] = array('path' => $v, 'val' => $v);
        }
        
        if(empty($data)){
          $data[0] = array('path'=>$path, 'val'=>$path);
        }
        
        return $data;
    }

    /**
     * scan path and get images
     * @param  $dir
     * @return array
     */
    public function getImages($path)
    {
        return $this->obj->getImages(rtrim($path, '/'), $this->config['upload_path']);
    }

    /**
     * move iamge
     * @param  string $source
     * @param  string $dest
     * @return boolean
     */
    public function moveImage($source, $dest)
    {
        $dest = str_replace('//', '/', $this->config['upload_path'] . '/' . str_replace($this->config['upload_path'], '', $dest));
        return $this->obj->moveImage($source, $dest);
    }
    
    /**
     * watermark
     * 
     * @param  string $imgSrc
     * @param  array  $options
     * 
     * @return \Wslim\Common\ErrorInfo ['errcode'=>.., 'errmsg'=>.., 'file'=>..]
     */
    public function setWatermark($imgSrc, $options=null)
    {
        if (!file_exists($imgSrc)) {
            return ErrorInfo::error(['errcode' => -1, 'errmsg'=>'目标图片不存在']);
        }
        
        if ($options) {
            $options = (array) $options;
            foreach ($options as $k => $v) {
                if (strpos($k, '-')) {
                    $k2 = str_replace('-', '_', $k);
                    $options[$k2] = $v;
                    unset($options[$k]);
                }
            }
            
            $options = array_merge($this->config['watermark'], $options);
        } else {
            $options = $this->config['watermark'];
        }
        
        $markType   = isset($options['type']) ? $options['type'] : 'text';    // image/text
        if (in_array($markType, ['image', 'img'])) {
            $markType = 'image';
        } else {
            $markType = 'text';
        }
        $markText   = $options['text'] ? : 'wslim.cn';
        $markImg    = $options['image'];
        if ($markType === 'image' && !file_exists($markImg)) {
            return ErrorInfo::error(['errcode' => -1, 'errmsg'=>'水印图片不存在']);
        }
        
        $TextColor  = $options['font_color'] ? : $this->config['watermark']['font_color'];
        $markPos    = isset($options['position']) ? Position::parsePosition($options['position']) : ['right', 'bottom'];
        
        $fontSize   = $options['font_size'] ? : $this->config['watermark']['font_size'];;
        $markDiaphaneity = $options['diaphaneity']; 
        
        $srcInfo = @getimagesize($imgSrc);
        $srcImg_w = $srcInfo[0];
        $srcImg_h = $srcInfo[1];
        
        // for small image return success
        if ($srcImg_w < 250) {
            return ErrorInfo::success('目标图片较小不需要水印');
        }

        switch ($srcInfo[2]) {
            case 1:
                $srcim = imagecreatefromgif($imgSrc);
                break;
            case 2:
                $srcim = imagecreatefromjpeg($imgSrc);
                break;
            case 3:
                $srcim = imagecreatefrompng($imgSrc);
                break;
            default:
                return ErrorInfo::error(['errcode' => -2, 'errmsg'=>'目标图片不是图片类型']);
        }
        
        if (!strcmp($markType, "image")) //使用图片加水印.
        {
            $markImgInfo = getimagesize($markImg);
            $markImg_w = $markImgInfo[0];
            $markImg_h = $markImgInfo[1];
            switch ($markImgInfo[2]) {
                case 1:
                    $markim = imagecreatefromgif($markImg);
                    break;
                case 2:
                    $markim = imagecreatefromjpeg($markImg);
                    break;
                case 3:
                    $markim = imagecreatefrompng($markImg);
                    break;
                default:
                    return ErrorInfo::error(['errcode' => -3, 'errmsg'=>'水印图片类型不支持']);
            }
            $logow = $markImg_w;
            $logoh = $markImg_h;
        }
        if (!strcmp($markType, "text")) {
            $fontType = __DIR__ . '/'. $options['font_path'];
            if (!file_exists($fontType)) {
                return ErrorInfo::error(['errcode' => -4, 'errmsg'=>'字体文件不存在']);
            }
    
            $box = @imagettfbbox($fontSize, 0, $fontType, $markText);
    
            $logow = max($box[2], $box[4]) - min($box[0], $box[6]);
            $logoh = max($box[1], $box[3]) - min($box[5], $box[7]);
        }
    
        $margin = 20;
        
        $posx = $markPos[0]; 
        $posy = $markPos[1];
        if ($posx && is_numeric($posx)) {
            $x = intval($posx);
        } else {
            if ($posx == 'center' || $posx == 'middle') {
                $x = ($srcImg_w - $logow) / 2;
            } elseif ($posx == 'right') {
                $x = $srcImg_w - $logow - $margin;
            } else {    // left
                $x = +20;
            }
        }
        
        // y 设置为35实际出来大约为 20
        if ($posy && is_numeric($posy)) {
            $y = intval($posy);
        } else {
            if ($posy == 'center' || $posy == 'middle') {
                $y = ($srcImg_h - $logoh) / 2;
            } elseif ($posy == 'bottom') {
                $y = $srcImg_h - $logoh - $margin;
            } else {    // top
                $y = +35;
            }
        }
        
        $dst_img = @imagecreatetruecolor($srcImg_w, $srcImg_h);
        imagecopy($dst_img, $srcim, 0, 0, 0, 0, $srcImg_w, $srcImg_h);
        
        if (!strcmp($markType, "image")) {
            imagecopy($dst_img, $markim, $x, $y, 0, 0, $logow, $logoh);
            imagedestroy($markim);
        }
        
        if (!strcmp($markType, "text")) {
            $TextColor = str_replace('rgb(', '', $TextColor);
            $TextColor = str_replace(')', '', $TextColor);
            $rgb = explode(',', $TextColor);
    
            $color = imagecolorallocate($dst_img, intval($rgb[0]), intval($rgb[1]), intval($rgb[2]));
    
            imagettftext($dst_img, $fontSize, 0, $x, $y, $color, $fontType, $markText);
        }
        
        // 如果图片是只读的，使用拷贝方式
        $readonly = isset($options['readonly']) && $options['readonly'] ? $options['readonly'] : false;
        if ($readonly) {
            @unlink($imgSrc);
        }
        
        switch ($srcInfo[2]) {
            case 1:
                imagegif($dst_img, $imgSrc);
                break;
            case 2:
                imagejpeg($dst_img, $imgSrc);
                break;
            case 3:
                imagepng($dst_img, $imgSrc);
                break;
            default:
                return ErrorInfo::error(['errcode' => -5, 'errmsg'=>'目标图片不是图片类型']);
        }
        
        imagedestroy($dst_img);
        imagedestroy($srcim);
        
        return ErrorInfo::success(['errcode'=>0, 'errmsg'=>'添加水印完成', 'file' => $imgSrc]);
    }
    
    /**
     * check ext
     * @param  string $ext
     * @return boolean
     */
    public function checkExt($ext)
    {
        if (strpos($ext, '.')) {
            $ext = pathinfo($ext, PATHINFO_EXTENSION);
        }
        if (strpos($this->allowedExts, $ext) === false) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * check size
     * @param  string|int $filesize
     * @return boolean
     */
    public function checkSize($filesize)
    {
        if (is_string($filesize)) {
            $filesize = filesize($filesize);
        }
        return $filesize <= $this->allowedSize;
    }
    
    /**
     * is image type, example image/jpeg or abc.jpg
     * @param  string $contentType
     * @return boolean
     */
    public function isImageType($contentType)
    {
        if (strpos($contentType, '.') !== false) {
            $ext = pathinfo($contentType, PATHINFO_EXTENSION);
            if (in_array($ext, ['gif', 'jpe', 'jpeg', 'jpg'])) {
                return true;
            }
        } else {
            if ($pos = strpos($contentType, '/')) {
                $contentType = substr($contentType, 0, $pos);
            }
            if ($contentType === 'image') {
                return true;
            }
        }
        return false;
    }
   
}
