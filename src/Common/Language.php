<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * language class
 * ```
 * $language = new Language;
 * $language->setLocale('zh');          // zh treats as dir
 * $language->load('common');   // default load 'common.lang.php'
 * $language->load('login', 'modules-path');  // load '/admin/common.lang.php'
 * $language->translate('edit');
 * ```
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Language
{
    /**
     * supported lacales, ['zh', 'en']
     * @var array
     */
    static public $supportedLacales = ['zh', 'en'];
    
    /**
	 * @var array $data Language data
	 */
    private $data = array();

	/**
	 * @var string $locale
	 */
    private $locale = 'zh';
    
	/**
	 * return locale set
	 * 
	 * @return string
	 */
	public function getLocale()
	{
	    return $this->locale;
	}
	
	/**
	 * set locale
	 *
	 * @param string $locale, zh|en
	 * @return void
	 */
	public function setLocale($locale)
	{
	    if (in_array($locale, static::$supportedLacales))  {
	        $this->locale = $locale;
	    }
	}
	
	/**
	 * fetch language data
	 *
	 * @return array
	 */
	public function getData()
	{
	    return $this->data;
	}
	
	/**
	 * load language data, default load app basePath
	 * 
	 * @param string|array $filenames filenames with no suffix, suffix = '.lang.php'
	 * @param string $basePath module basePath or name
	 * @param string $locale
	 * 
	 * @return void
	 */
	public function load($filenames='common', $basePath=null, $locale=null)
	{
	    $locale = $locale ?: $this->locale;
	    
	    $filenames = (array) $filenames;
	    
	    if (!$basePath) {
	        $basePath = Config::getCommonPath();
	    } elseif (!file_exists($basePath)) {
	        $basePath = Config::getRootPath() . trim($basePath, '/');
	    }
	    
	    foreach ($filenames as $file) {
	        if (!is_file($file)) {
	            $file = rtrim($basePath, '/') . '/lang/' . $locale . '/'. $file . '.lang.php';
	        }
	        
	        $content = Ioc::load($file);
	        
	        if ($content && is_array($content)) {
	            $this->data = array_merge($this->data, $content);
	        }
	    }
	}
    
    /**
     * Translate and return the string
     *
     * @param  string       $key
     * @param  string|array $data
     * @return string
     */
	public function translate($key, $data = null)
    {
        $rs = isset($this->data[$key]) ? $this->data[$key] : $key;
        if ($rs && !empty($data)) {
            $names = array_keys($data);
            $names = array_map(function($pkey){ return '{' . $pkey . '}'; }, $names);
            $rs = str_replace($names, array_values($data), $rs);
        }
        return $rs;
    }
    
}