<?php
namespace Wslim\Image;

interface ImageInterface
{
    /**
     * get all sub dirs
     * @param  string $path
     * @return array
     */
    public function getDirs($path);

    /**
     * scan path and get images array, strip basePath
     * @param  string $path
     * @param  string $basePath
     * @return array
     */
    public function getImages($path, $basePath);

    /**
     * move iamge
     * @param  string $source
     * @param  string $dest
     * @return boolean
     */
    public function moveImage($source, $dest);
    
}