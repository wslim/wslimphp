<?php
namespace Wslim\Common;

use Wslim\Ioc;
use Wslim\Util\ArrayHelper;
use Wslim\Util\UriHelper;

/**
 * Config, support uniform config management. And support 'config-dev' dir, it can overwrite config, see load(). 
 * Methods: load(), get(), set() 
 * 
 * @author 28136957@qq.com
 * @date   2018-01-12
 * @link   wslim.cn
 */
class Config
{
    /**
     * config file ext
     * @var string
     */
    private static $encryptExt = '.encrypt.php';
    
    /**
     * config env: dev|product|...
     * @var string
     */
    private static $env = null;
    
    /**
     * config data
     * @var Collection
     */
    private static $data = null;
    
    /**
     * default range, can be common
     */
    private static $range = 'common';
    
    /**
     * default storage type: file|db|redis
     */ 
    private static $storage = 'file';
    
    /**
     * loaded ranges
     * @var array
     */
    private static $loadedRanges = [];
    
    /**
     * init
     * @return void
     */
    static private function _init()
    {
        if (is_null(static::$data)) {
            static::$data = new Collection();
        }
    }
    
    /**
     * init env, env can be dev|product|..., it affect config dir
     * @param  string $env
     * @return void
     */
    static public function env($env=null)
    {
        if (isset($env) && $env !== 'product') {
            static::$env = $env;
        }
        
        return static::$env;
    }
    
    /**
     * load file
     * @param  string $file
     * @return array|false
     */
    static private function _loadFile($file)
    {
        $value = Ioc::load($file, true);
        return $value;
    }
    
    /**
     * load config
     * 
     * @param  string $file  config filename, relative or absolute path, default 'config'
     * @param  string $name  key of loaded after
     * @param  string $range load dir, relative or absolute
     * @return mixed
     */
    public static function load($file='config', $name=null, $range=null)
    {
        // avoid repeated load
        $range = $range ?: static::$range;
        if (!isset(static::$loadedRanges[$range][$file])) {
            static::$loadedRanges[$range][$file] = 1;
        } else {
            return static::get($name ?: (($loadname = basename($file))=='config' ? null : $loadname), $range);
        }
        
        if (is_file($file)) {
            $type = pathinfo($file, PATHINFO_EXTENSION);
            if ('php' == $type) {
                $value = static::_loadFile($file);
            } elseif ('yaml' == $type && function_exists('yaml_parse_file')) {
                $value = yaml_parse_file($file);
            } else {
                $value = null;
            }
            if (!$name) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ($name === 'config') {
                    $name = null;
                }
            }
            return self::set($name, $value, $range);
        } else {
            if (!$range || $range == 'common') {
                $basePath = static::getCommonPath();
                $range = null;
            } else {
                if (!file_exists($range)) {
                    $basePath = Ioc::app()->getBasePath() . trim($range, '/');
                } else {
                    $basePath = rtrim($range, '/') . '/';
                }
                $range = explode('.', basename($basePath))[0];
                if ($range == basename(static::getCommonPath()) || $range == basename(Ioc::app()->getBasePath())) {
                    $range = null;
                }
            }
            
            if (!$name) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ($name === 'config') {
                    $name = null;
                }
            }
            
            $res = null;
            
            // 1. <app>/config/file
            $tmp = $basePath . 'config/' . $file;
            $tmpfile = $tmp . '.php';
            if (!file_exists($tmpfile)) {
                $tmpfile = $tmp . static::$encryptExt;
            }
            if (file_exists($tmpfile)) {
                $res = self::set($name, static::_loadFile($tmpfile), $range);
            }
            
            // 2. <app>/config-<env>/file
            if ($env = static::$env) {
                $tmp = $basePath . 'config-' . $env . '/' . $file;
                $tmpfile = $tmp . '.php';
                if (!file_exists($tmpfile)) {
                    $tmpfile = $tmp . static::$encryptExt;
                }
                if (file_exists($tmpfile)) {
                    $res = self::set($name, static::_loadFile($tmpfile), $range);
                }
            }
            
            return $res;
        }
    }

    /**
     * has some config
     * @param  string $name  support split by '.', example 'cache.storage'
     * @param  string $range 
     * @return bool
     */
    public static function has($name, $range = null)
    {
        static::_init();
        
        $range = $range ?: self::$range;
        
        $exists = isset(self::$data[$range][$name]) ? true : false;
        
        if (!$exists) {
            $names = explode('.', $name, 3);
            $count = count($names);
            if ($count >= 1) {
                $exists = array_key_exists($names[0], array_keys(self::$data[$range]));
                
                if ($count >= 2 && $exists) {
                    $exists = isset(self::$data[$range][$names[0]]) && array_key_exists($names[1], array_keys(self::$data[$range][$names[0]]));
                }
                
                if ($count >= 3 && $exists) {
                    $exists = isset(self::$data[$range][$names[0]][$names[1]]) && array_key_exists($names[2], array_keys(self::$data[$range][$names[0]][$names[1]]));
                }
            }
        }
        
        return $exists;
    }
    
    /**
     * get config, if no params it get all config
     * @param  string   $name    support split by '.' max 2, example 'views.mobile.theme'
     * @param  string   $range   range or loaded dir
     * @param  boolean  $inherit if not exists get inherit value
     * @return mixed
     */
    public static function get($name = null, $range = null, $inherit=true)
    {
        $range = $range ?: self::$range;
        
        // load default
        static::autoload($range);
        
        $value = null;
        if (empty($name)) {// get all
            $value = isset(self::$data[$range]) ? self::$data[$range] : null;
        } elseif (isset(self::$data[$range][$name])){
            $value = self::$data[$range][$name];
        } elseif (strpos($name, '.')) { // has '.'
            $names    = explode('.', $name, 3);
            if (count($names) > 2) {
                $value = isset(self::$data[$range][$names[0]][$names[1]][$names[2]]) ? self::$data[$range][$names[0]][$names[1]][$names[2]] : null;
            } else {
                $value = isset(self::$data[$range][$names[0]][$names[1]]) ? self::$data[$range][$names[0]][$names[1]] : null;
            }
        }
        
        // if inherit get default range config  && 
        if ($range !== self::$range && $inherit) {
            if ($value === null) {
                $value = self::get($name, self::$range, false);
            } elseif (is_array($value)) {
                $vd = self::get($name, self::$range, false);
                $value = ArrayHelper::merge($vd, $value);
            }
        }
        
        return $value;
    }
    
    /**
     * get only common range
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    static public function getCommon($name = null, $default=null)
    {
        $value = self::get($name, self::$range);
        return $value === null ? $default : $value;
    }
    
    /**
     * get by type, example get db log, try search logs.db|db.log|<db.config>log
     * @param  string  $type
     * @param  string  $name
     * @param  boolean $inherit if not exists get inherit value
     * @return mixed
     */
    static public function getByType($type, $name = null, $inherit=false)
    {
        // first get [logs.xxx], then [xxx.log]
        if (!($value = static::get($type . 's.' . $name))) {
            if (!($value = static::get($name . '.' . $type))) {
                $value = static::get($type, $name, $inherit);
            }
        }
        
        return $value;
    }

    /**
     * set config value
     * @param  string|array  $name
     * @param  mixed         $value
     * @param  string        $range
     * @param  boolean       $overwrite if overwrite
     * @return mixed
     */
    public static function set($name, $value = null, $range = null, $overwrite=true)
    {
        static::_init();
        
        $range = $range ?: self::$range;
        if (!isset(self::$data[$range])) {
            self::$data->set($range, []);
        }
        
        if (!$name && is_array($value)) {
            foreach ($value as $k => $v) {
                self::set($k, $v, $range, $overwrite);
            }
            return self::$data[$range];
        } elseif (is_string($name)) {
            $old = static::$data->get($range);
            if (!strpos($name, '.')) {
                $temp = [$name => $value];
                
                if ($overwrite || !isset($old[$name])) {
                    $new = ArrayHelper::merge($old, $temp);
                    static::$data->set($range, $new);
                }
                
                return self::$data[$range][$name];
            } else {
                // has '.'
                $names = explode('.', $name, 2);
                $temp = [$names[0] => [$names[1] => $value]];
                
                if ($overwrite || !isset( $old[$names[0]][$names[1]] )) {
                    $new = ArrayHelper::merge($old, $temp);
                    static::$data->set($range, $new);
                }
                
                return self::$data[$range][$names[0]][$names[1]];
            }
        } elseif (is_array($name)) {
            // batch set
            if (!empty($value) && is_string($value)) {
                return static::set($value, $name, null, $overwrite);
            } else {
                foreach ($name as $k => $v){
                    self::set($k, $v, $range, $overwrite);
                }
                return self::$data[$range];
            }
        } else {
            // if no name return config
            return self::$data[$range];
        }
    }
    
    /**
     * set default value, don't overwrite exists key
     * @param  string|array  $nam
     * @param  mixed         $value
     * @param  string        $range
     * @return mixed
     */
    public static function setDefault($name, $value = null, $range = null)
    {
        return static::set($name, $value, $range, false);
    }
    
    /**
     * reset, can clear all
     * @param  string $range
     * @return void
     */
    public static function reset($range = null)
    {
        static::_init();
        
        $range = $range ?: self::$range;
        if (true === $range) {
            self::$data->clear();
        } else {
            self::$data->set($range, []);
        }
    }
    
    /**
     * autoload
     * @param string $range
     */
    private static function autoload($range=null)
    {
        static::_init();
        
        $range = $range ?: static::$range;
        
        if ($range == static::$range && !isset(self::$data[$range])) {
            //print_r(__METHOD__ . ':' . $range . PHP_EOL);
            self::$data->set($range, []);
            static::load('config', null, $range);
        }
    }
    
    /**
     * all config data
     * @return \Wslim\Common\Collection
     */
    static public function all()
    {
        return static::$data;
    }
    
    /************************************************************
     * path methods
     ************************************************************/
    /**
     * get root path, end with /
     * @throws \Exception
     * @return string
     */
    static public function getRootPath()
    {
        if (!defined('ROOT_PATH')) {
            throw new \Exception('ROOT_PATH is not defined');
        }
        
        return rtrim(str_replace('\\', '/', ROOT_PATH), '/') . '/';
    }
    
    /**
     * get common path, end with /
     * @return string
     */
    static public function getCommonPath()
    {
        $value = static::getCommon('commonPath', static::getRootPath() . 'common');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get storage path, end with /
     *
     * @return string
     */
    static public function getStoragePath()
    {
        $value = static::getCommon('storagePath', static::getRootPath() . 'storage');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get plugin path, default '<ROOT_PATH>plugin/', end with /
     * @return string
     */
    static public function getPluginPath()
    {
        $value = static::getCommon('pluginPath', static::getRootPath() . 'plugin');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get web root path, end with /, default '<ROOT_PATH>webroot/'
     *
     * @return string
     */
    static public function getWebRootPath()
    {
        $value = static::getCommon('webRootPath', static::getRootPath() . 'webroot');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get web app path, default '<ROOT_PATH>app/'
     * 
     * @return string
     */
    static public function getWebAppPath()
    {
        $value = static::getCommon('webAppPath', static::getRootPath() . 'app');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get cli app path, default '<ROOT_PATH>cli/'
     *
     * @return string
     */
    static public function getCliAppPath()
    {
        $value = static::getCommon('cliAppPath', static::getRootPath() . 'cli');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get web root url, if $path then return 'rooUtl/$path'
     * 
     * @return string
     */
    static public function getRootUrl($path=null)
    {
        if ($path && strpos($path, 'http') === 0) {
            return $path;
        }
        
        $url = trim(static::getCommon('rootUrl'), '/');
        
        if (!$url) {
            $url = UriHelper::getRootUrl();
        } elseif (strpos($url, 'http') !== 0) {
            $url = UriHelper::getRootUrl() . '/' . $url;
        }
        
        if ($path) {
            $url .= '/' . trim($path, '/');
        }
        
        return $url;
    }
    
    /**
     * get upload base path, end with /, module view need this path
     *
     * @return string
     */
    static public function getUploadPath()
    {
        $value = static::getCommon('uploadPath', static::getWebRootPath() . 'upload');
        
        return rtrim(str_replace('\\', '/', $value), '/') . '/';
    }
    
    /**
     * get uplaod base url
     * @param  boolean $isAbsolute
     * @return string
     */
    static public function getUploadUrl($isAbsolute=true)
    {
        $url = static::getCommon('uploadUrl');
        if ($isAbsolute && strpos($url, 'http') !== 0) {
            $baseUrl = static::getRootUrl();
            $url = rtrim($baseUrl, '/') . '/' . trim($url, '/');
        }
        
        return $url;
    }
    
    /**
     * is root images file
     * @param  string $filename
     * @return boolean
     */
    static public function isRootImageFile($filename)
    {
        return strpos(ltrim($filename, '/'), 'images/') === 0;
    }
    
    /**
     * get uplaod file full url
     * @param  string $filename
     * @return string
     */
    static public function getUploadFileUrl($filename)
    {
        if ($filename && strpos($filename, 'http') !== 0) {
            
            $filename = static::getUploadFileRelativePath($filename);
            
            if (static::isRootImageFile($filename)) {
                
                return static::getRootUrl() . '/' . $filename;
            }
            
            $filename = static::getUploadUrl(true) . '/' . $filename;
        }
        
        return $filename;
    }
    
    /**
     * get upload file absolute path
     * @param  string $filename
     * @return string
     */
    static public function getUploadFilePath($filename)
    {
        if ($filename) {
            $filename = static::getUploadFileRelativePath($filename);
            
            if (static::isRootImageFile($filename)) {
                
                return static::getWebRootPath() . $filename;
            }
            
            $filename = static::getUploadPath() . $filename;
        }
        
        return $filename;
    }
    
    /**
     * get upload file relative path
     * @param  string $filename
     * @return string
     */
    static public function getUploadFileRelativePath($filename)
    {
        if ($filename) {
            $filename = str_replace('\\', '/', $filename);
            
            $filename = str_replace(static::getUploadUrl(), '', $filename);
            
            $filename = trim(str_replace(static::getUploadPath(), '', $filename), '/');
            
            if (strpos($filename, 'http') === 0) {
                $filename = str_replace(static::getRootUrl(), '', $filename);
            }
        }
        
        return $filename;
    }
    
}
