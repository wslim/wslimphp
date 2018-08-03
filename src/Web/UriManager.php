<?php
namespace Wslim\Web;

use Wslim\Common\Config;
use Wslim\Util\UriHelper;
use Wslim\Ioc;

class UriManager
{
    
    public function __construct($options)
    {
        //print_r($options); var_dump($this); echo '<br>';
    }
    
    /**
     * build url
     *
     * <br>1. '/a/b/c'      => 'rootUrl/a/b/c', with / parsed to rootUrl
     * <br>2. 'a/b/c'       => 'currentModule/a/b/c', not with / parsed to current module
     * <br>3. './a/b/c'     => 'currentUrl/a/b/c', with ./ parsed to current url
     * <br>4. ':a/b'        => 'rootUrl/a/b', root module
     * <br>5. 'upload:a/b'  => 'uploadUrl/a/b', parsed to option xxxUrl
     * <br>6. 'wx:a/b'      => 'wxModule/a/b', parsed to module xxx
     * 
     * @param  string $path
     * @param  mixed  $params string or array
     * @return string
     */
    static public function url($path, $params=[])
    {
        $path = str_replace('{$rootUrl}', Config::getRootUrl(), $path);
        
        if (strpos($path, 'http') === 0) {
            return UriHelper::buildUrl($path, $params);
        }
        
        // REQUEST_URI contain SCRIPT_NAME
        if (($pos = strpos($path, ':')) !== false) {    // 1. "x:a/b" module or xUrl
            
            $module = trim(substr($path, 0, $pos), '/');
            $path   = trim(substr($path, $pos + 1), '/');
            
            if (strpos($path, 'http') !== 0) {
                if (!$module) { // 1.1 ":a/b" root module
                    $baseUrl = Config::getRootUrl();
                } else {    // 1.2 "x:a/b" xUrl or module x
                    if (method_exists(get_called_class(), $module . 'Url')) { // 1.2.1 xUrl()
                        $method = $module . 'Url';
                        return static::$method($path, $params);
                    } elseif ($url = Config::get($module . 'Url')) {    // 1.2.2 config "xUrl"
                        $baseUrl = $url;
                    } elseif ($moduleInstance = Ioc::web()->getModule($module)) {   // 1.2.3 module x
                        $baseUrl = $moduleInstance->getBaseUrl(true);
                    } else {    // 1.2.5 dir x
                        $baseUrl = $module;
                    }
                    
                    $baseUrl = Config::getRootUrl($baseUrl);
                }
                
                $path = rtrim($baseUrl, '/') . ($path ? '/' . trim($path, '/') : '');
            }
            
        } else {
            if (!$path) {   // 2.1 "" => current url
                $path = UriHelper::getCurrentUrl();
            } else {
                if (strpos($path, '/') === 0) { // 2.2 "/a/b" => begin / parsed root module
                    $baseUrl = Config::getRootUrl();
                } else {    // 2.3 "a/b" => parsed current module
                    // if is default module, use web baseUrl. Otherwise use current module url
                    if (Ioc::web()->isDefaultModule()) {
                        $baseUrl = Ioc::web()->getBaseUrl();
                    } else {
                        $baseUrl = Ioc::web()->getCurrentModule()->getBaseUrl(true);
                    }
                    
                    if (strpos($path, './') === 0) { // 2.4 "./a/b" => begin ./ parsed current url
                        $currentPath = dirname(UriHelper::getCurrentPath()); 
                        if (strpos($currentPath, $baseUrl) !== false) {
                            $baseUrl = $currentPath;
                        }
                        
                        $path = str_replace('./', '', $path);
                    }
                }
                
                $path = rtrim($baseUrl, '/') . ($path ? '/' . trim($path, '/') : '');
            }
        }
        
        // query $params
        if ($params) {
            if (is_numeric($params)) {
                //$params = ['id' => $params];
                $path .= strpos($path, '?') !== false ? $params : '/' . $params;
            } else {
                $path = UriHelper::buildUrl($path, $params);
            }
        }
        
        $paths = explode('?', $path, 2);
        if (strlen($paths[0]) - strrpos($paths[0], '.') > 5) {
            $path = rtrim($paths[0], '/') . '.html' . (isset($paths[1]) ? '?' . $paths[1] : '');
        }
        
        return $path;
    }
    
    /**
     * get relative url
     * @param  string $url
     * @return string
     */
    static public function rUrl($url)
    {
        $module = null;
        if (strpos($url, 'http') !== 0 && ($pos = strpos($url, ':')) !== false) {    // 1. "x:a/b" module or xUrl
            $module = trim(substr($url, 0, $pos), '/');
            $url   = trim(substr($url, $pos + 1), '/');
        }
        
        if (strpos($url, 'http') === 0) {
            $url = str_replace(static::url($module . ':'), '', $url);
        }
        
        return trim($url, '/');
    }
    
    /**
     * get upload url
     * @param  string $path
     * @return string
     */
    static public function uploadUrl($path)
    {
        if (strpos($path, 'images') === 0) {
            return Config::getRootUrl($path);
        }
        
        return Config::getUploadFileUrl($path);
    }
    
}