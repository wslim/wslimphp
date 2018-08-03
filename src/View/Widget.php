<?php
namespace Wslim\View;

use Wslim\Ioc;
use Wslim\Util\ArrayHelper;
use Wslim\Common\DataFormatter\XmlFormatter;

class Widget
{
    use \Wslim\Common\CacheAwareTrait;
    
    /**
     * options, overwrite
     * 
     * @var array
     */
    protected $options = [];

    /**
     * Holds class aliases.
     *
     * @var array static[]
     */
    protected static $instances = [];

    /**
     * get instance
     * 
     * @return static
     */
    static public function instance()
    {
        $key = get_called_class();
        if (!isset(static::$instances[$key])) {
            $object = new static();
            $object->setCache(Ioc::cache('widget'));
            static::$instances[$key] = $object;
        }
        return static::$instances[$key];
    }

    /**
     * construct, can rewrite
     */
    public function __construct($options = null)
    {
        if ($options) {
            $this->options = ArrayHelper::merge($this->options, $options);
        }
    }

    /**
     * json
     * for PhtmlEngine: {widget action="json" url="..."}
     * for PhpEngine : $widget->json(['url'=>...])
     *
     * @param array $data
     * @return string|null
     */
    public function json($data)
    {
        if (isset($data['url']) && ! empty($data['url'])) {
            $result = @file_get_contents($data['url']);
            $result = json_decode($result, true);
        }
        return isset($result) ? $result : null;
    }

    /**
     * xml
     * for PhtmlEngine: {widget action="xml" url=""}
     * for PhpEngine :  $widget->xml(['url'=>...])
     *
     * @param array $data
     * @return string|null
     */
    public function xml($data)
    {
        if (isset($data['url']) && ! empty($data['url'])) {
            $result = @file_get_contents($data['url']);
            $result = XmlFormatter::encode($result);
        }
        return isset($result) ? $result : null;
    }

    /**
     * get
     * for PhtmlEngine: {widget action="get" url="..."}
     * for PhpEngine :  $widget->get(['url'=>...])
     *
     * @param  array $data
     * @return string|null
     */
    public function get($data)
    {
        if (isset($data['url']) && ! empty($data['url'])) {
            $result = @file_get_contents($data['url']);
        }
        
        return isset($result) ? $result : null;
    }

}