<?php
namespace Wslim\Image;

class LocalImage implements ImageInterface
{
    /**
     * {@inheritDoc}
     * @see \Wslim\Image\ImageInterface::getDirs()
     */
    public function getDirs($dir)
    {
        $dirArr = array();
        if ($handler = opendir($dir)) {
            while ($file = readdir($handler)) {
                $path = realpath("{$dir}/{$file}");
                if (is_dir($path)) {
                    
                    if (in_array($file, array('.', '..'))) {
                        continue;
                    }
                    
                    if (preg_match('/^[a-zA-z0-9]+$/', $file)) {
                        $dirArr[] = $path;
                        $dirArr = array_merge($dirArr, self::getDirs($path));
                    }
                }
            }
            closedir($handler);
        }
        return $dirArr;
    }

    /**
     * {@inheritDoc}
     * @see \Wslim\Image\ImageInterface::moveImage()
     */
    public function moveImage($source, $dest)
    {
        $dir = dirname($dest);
        if(!file_exists($dir)){
            mkdir($dir, 0755, true);
        }
        return rename($source, $dest);
    }

    /**
     * {@inheritDoc}
     * @see \Wslim\Image\ImageInterface::getImages()
     */
    public function getImages($dir, $basedir)
    {
        $files = array();
        $dir = $basedir . $dir;
        if ($handler = opendir($dir)) {
            while ($file = readdir($handler)) {
                $path = realpath("{$dir}/{$file}");
                if (is_file($path)) {
                    if (preg_match('/^[a-zA-z0-9=&,]+\.(gif|png|jpg|jpeg)$/', $file)) {
                        array_push($files, array(str_replace(array($basedir, '\\'), array('', '/'), $path),filesize($path)));
                    }
                }
            }
            closedir($handler);
        }
        
        return $files;
    }
}