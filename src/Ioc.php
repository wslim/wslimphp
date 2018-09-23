<?php
namespace Wslim;

use Wslim\Util\StringHelper;
use Wslim\Util\ArrayHelper;
use Wslim\Common\Config;
use Wslim\Common\Container;
use Wslim\Common\ContainerInterface;
use Wslim\Common\InvalidConfigException;
use Wslim\Common\ErrorHandler;
use Wslim\Common\Logger;
use Wslim\Common\Cache;
use Wslim\Db\Db;
use Wslim\Db\Model;
use Wslim\Session\Session;
use RuntimeException;
use Wslim\Security\LimitTimeToken;
use Wslim\Security\Crypt;

/**
 * glabal helper class
 * 
 * @author 28136957@qq.com
 * @see    wslim.cn
 * 
 * @method \Wslim\Common\Language   language()
 * @method \Wslim\Web\UriManager    uriManager()
 */
class Ioc
{
    /**
     * version
     * @var string
     */
    const VERSION = '2.3.23';
    
	/**
	 * FormHelper
	 * @var \Wslim\Web\FormHelper
	 */
	static public $formHelper;
	
	/**
	 * HtmlHelper
	 * @var \Wslim\Web\HtmlHelper
	 */
	static public $htmlHelper;
	
	/**
	 * @var \Wslim\Common\ContainerInterface
	 * @access protected
	 */
    static protected $container;
	
	/**
	 * available namespaces
	 * @var array
	 * @access private
	 */
	static protected $namespaces = [];
	
	/**
	 * global default objects
	 * @var array
	 */
	static private $components = [
	    'language'     => '\\Wslim\\Common\\Language',
	    'dataHelper'   => '\\Wslim\\Util\\DataHelper',
	    'htmlHelper'   => '\\Wslim\\Web\\HtmlHelper',
	    'formHelper'   => '\\Wslim\\Web\\FormHelper',
	    'uriManager'   => '\\Wslim\\Web\\UriManager',
	];
	
	/**
	 * __callStatic
	 * @param  string $name
	 * @param  array  $params
	 * @throws \BadMethodCallException
	 * @return mixed
	 */
	static public function __callStatic($name, $params)
	{
	    if (!static::has($name)) {
	        if (isset(static::$components[$name])) {
	            static::setShared($name, static::$components[$name]);
	            
	            array_unshift($params, $name);
	            return call_user_func_array(get_called_class() . '::get', $params);
	        }
	    } else {
	        array_unshift($params, $name);
	        return call_user_func_array(get_called_class() . '::get', $params);
	    }
	    
	    throw new \BadMethodCallException('Undefined method:' . $name );
	}
	
	/**
	 * get container
	 * @return \Wslim\Common\ContainerInterface
	 */
	static public function container()
	{
	    if (!static::$container) {
	        static::$container = new Container();
	    }
	    
	    return static::$container;
	}
	
	/**
	 * set container
	 * @param \Wslim\Common\ContainerInterface $container
	 */
	static public function setContainer(ContainerInterface $container)
	{
	    static::$container = $container;
	}
	
	/**
	 * get object from container
	 * @param  string $name
	 * @param  array  $config
	 * @return object
	 */
	static public function get($name, $config=[])
	{
	    return static::container()->get($name, $config);
	}
	
	/**
	 * set object into container
	 * @param string $name
	 * @param mixed $component
	 */
	static public function set($name, $component)
	{
	    static::container()->set($name, $component);
	}
	
	/**
	 * set shared object into container
	 * @param  string $name
	 * @param  mixed  $component
	 * @return void
	 */
	static public function setShared($name, $component)
	{
	    static::container()->setShared($name, $component);
	}
	
	/**
	 * has component
	 * @param  string $name
	 * @return boolean
	 */
	static public function has($name)
	{
	    return static::container()->has($name);
	}
	
	/************************************************************
	 * contain object methods
	 ************************************************************/
	/**
	 * get current app
	 * @return \Wslim\Common\App
	 */
	static public function app()
	{
	    if (static::has('app')) {
	        return static::get('app');
	    }
        return null;
        //throw new \Exception('current app is not set.');
	}
	
	/**
	 * set current app
	 * @param  \Wslim\Common\App $app
	 * @return void
	 */
	static public function setApp($app)
	{
	    return static::setShared('app', $app);
	}
	
	/**
	 * get web app
	 * @return \Wslim\Web\App
	 */
	static public function web()
	{
	    $key = 'app.web';
	    if (!static::has($key)) {
	        if ( !($app = static::app()) || $app->isCli()) {
	            static::setShared($key, new \Wslim\Web\App(Config::getWebAppPath()));
	        } else {
	            static::setShared($key, $app);
	        }
	    }
	    
	    return static::get($key);
	}
	
	/**
	 * get console app
	 * @return \Wslim\Console\App
	 */
	static public function cli()
	{
	    $key = 'app.cli';
	    
	    if (!static::has($key)) {
	        if ( !($app = static::app()) || !$app->isCli()) {
	            static::setShared($key, new \Wslim\Console\App(Config::getCliAppPath()));
	        }
	    }
	    
	    return static::get($key);
	}
	
	/**
	 * get config
	 * @param  string $name
	 * @return mixed
	 */
	static public function config($name=null)
	{
	    return Config::get($name);
	}
	
	/**
	 * get loader
	 * @return \Composer\Autoload\ClassLoader
	 */
	static public function loader()
	{
		$class = '\Composer\Autoload\ClassLoader';
		if (!static::container()->has($class)) {
			static::container()->setShared($class);
		}
		return static::container()->get($class);
	}
	
	/**
	 * set class loader
	 * @param  \Composer\Autoload\ClassLoader $loader
	 * @return void
	 */
	static public function setLoader($loader)
	{
		static::container()->setShared('\Composer\Autoload\ClassLoader', $loader);
	}
    
	/**
	 * load file, consistent with composer
	 * 
	 * @param  string  $file
	 * @param  boolean $auto_decrypt if auto decrypt
	 * @return array|false false if file not exists
	 */
	static public function load($file, $auto_decrypt=false)
	{
		if (substr(trim($file), -4) !== '.php') {
		    return false;
		}
		
		$fileIdentifier = md5($file);
		if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier]) && file_exists($file)) {
			$GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
			
			if (!$auto_decrypt) {
			    return require $file;
			}
			
			$value = file_get_contents($file);
			if (strpos($value, '<') !== false) {
			    $value = require $file;
			} else {
			    $value = Crypt::instance()->decrypt($value);
			    
			    //创建了一个临时文件
			    $handle = tmpfile();
			    
			    //向里面写入了数据
			    $numbytes = fwrite($handle, $value);
			    
			    $value = require stream_get_meta_data($handle)['uri'];
			    
			    //关闭临时文件，文件即被删除
			    fclose($handle);
			}
			
			return $value;
		}
		
		return false;
	}
	
	/**
	 * encrypt file, origin file is remain.
	 * @param  string $filename
	 * @return boolean true for success
	 */
	static public function encryptFile($filename)
	{
	    $value = file_get_contents($filename);
	    
	    if (strpos($value, '<?php') !== false) {
	        $bfile = pathinfo($filename, PATHINFO_FILENAME);
	        $ext   = pathinfo($filename, PATHINFO_EXTENSION);
	        $bfile .= '.encrypt.' . $ext;
	        
	        $encrypt = Crypt::instance()->encrypt($value);
	        
	        return file_put_contents($bfile, $encrypt);
	    }
	    
	    return true;
	}
	
	/**
	 * load plugin 
	 * @param string $plugin
	 */
	static public function loadPlugin($plugin=null)
	{
	    $path = Config::getPluginPath();
	    if ($plugin) {
	        $pluginPath = $path . trim($plugin, '/') . '/autoload.php';
	        static::load($pluginPath);
	    }
	}
	
	/**
	 * get errorHandler
	 * @return \Wslim\Common\ErrorHandler
	 */
	static public function errorHandler()
	{
	    $id = 'errorHandler';
	    if (!static::has($id)) {
	        static::setShared($id, function () {
	            $errorHandler = new ErrorHandler();
	            if ($options = Config::getCommon('error')) {
	                $errorHandler->setOptions($options);
	            }
	            $errorHandler->register();
	            return $errorHandler;
	        });
	    }
	    return static::get($id);
	}
	
	/**
	 * lang translate
	 * @param  string $key
	 * @param  array  $data
	 * @return string
	 */
	static public function lang($key, array $data=null)
	{
	    return static::language()->translate($key, $data);
	}
	
	/**
	 * get logger
	 *
	 * @param  string $name independent instance name
	 * @return \Wslim\Common\Logger
	 */
	static public function logger($name=null)
	{
	    $id = 'logger' . ( isset($name) ? '.' . $name : '' );
	    if (!static::has($id)) {
	        static::setShared($id, function () use ($name) {
	            // common config
	            $options = Config::getCommon('log') ?: [];
	            
	            // compoment level config
	            if ($name) {
	                $name = str_replace(['\\', '//'], '/', $name);
	                
	                $name = explode('/', $name, 2);
	                if ($name[0] == 'db') {
	                    $name[0] = 'database';
	                }
	                $fname = $name[0];
	                $name = implode('/', $name);
	                
	                $m_options = Config::getByType('log', $fname);
	                
	                if (!isset($m_options['path']) && !isset($m_options['group'])) {
	                    $options['group'] = $name; // group取name, 支持目录式设置如 'db/sql'
	                }
	                if ($m_options) {
	                    $options = ArrayHelper::merge($options, $m_options);
	                }
	            }
	            
	            $options['path'] = isset($options['path']) ? ltrim($options['path'], '/') : 'logs';
	            $options['path'] = Config::getStoragePath() . $options['path'];
	            
	            return new Logger($options);
	        });
	    }
	    return static::get($id);
	}
	
	/**
	 * get cache instance
	 *
	 * @param  string $name independent instance name
	 * @return \Wslim\Common\Cache
	 */
	static public function cache($name=null)
	{
	    $id = 'cache' . ( isset($name) ? '.'.$name : '' );
	    if (!static::has($id)) {
	        static::setShared($id, function () use ($name) {
	            // common config
	            $options = Config::getCommon('cache') ?: [];
	            
	            // compoment level config
	            if ($name) {
	                if ($name == 'db') $name = 'database';
	                
	                $m_options = Config::getByType('cache', $name);
	                if (!isset($m_options['path']) && !isset($m_options['group'])) {
	                    $options['group'] = $name;
	                }
	                if ($m_options) {
	                    $options = ArrayHelper::merge($options, $m_options);
	                }
	            }
	            
	            $options['path'] = isset($options['path']) ? ltrim($options['path'], '/') : 'caches';
	            $options['path'] = Config::getStoragePath() . $options['path'];
	            return new Cache($options);
	        });
	    }
	    return static::get($id);
	}
	
	/**
	 * get db
	 * @param  string db config group key
	 * @param
	 * @return \Wslim\Db\Db
	 */
	static public function db($name=null)
	{
	    $id = 'db' . ($name ? '.' . $name : '');
	    if (!static::has($id)) {
	        static::setShared($id, function () use($name) {
	            Config::load('database');
	            $config = (array)Config::get('database');
	            if ($config) {
	                $config = ArrayHelper::getItemArray($config, $name);
	            }
	            
	            return new Db($config);
	        });
	    }
	    
	    return static::get($id);
	}
	
	/**
	 * get model by name, name can be [module:database.tablename].
	 *
	 * @param  string $name
	 * @return \Wslim\Db\Model
	 */
	static public function model($name=null)
	{
	    return Model::instance($name);
	}
	
	/**
	 * get session instance
	 * @return \Wslim\Session\Session
	 */
	static public function session($name=null)
	{
	    $id = 'session';
	    if (!static::has($id)) {
	        static::setShared($id, function () {
	            // app option
	            $options = Config::get('session');
	            
	            if ($options['storage'] == 'file') {
	                if (!isset($options['save_path']) && isset($options['path'])) {
	                    $options['save_path'] = $options['path'];
	                }
	                $options['save_path'] = isset($options['save_path']) ? ltrim($options['save_path'], '/') : 'sessions';
	                $options['save_path'] = Config::getStoragePath() . $options['save_path'];
	            }
	            $obj = new Session($options);
	            $obj->start();
	            return $obj;
	        });
	    }
	    
	    return static::get($id);
	}
	
	/**
	 * get module
	 * @param  string $name
	 * @return \Wslim\Common\Module|NULL
	 */
	static public function module($name)
	{
	    return static::app()->getModule($name);
	}
	
	/**
	 * get controller
	 * @param  string $name
	 * @return \Wslim\Common\ControllerInterface|NULL
	 */
	static public function controller($name)
	{
	    if ($class = static::app()->getController($name)) {
	        return $class;
	    } elseif ($class = static::findClass($name, 'controller')) {
	        return new $class;
	    }
	    return null;
	}
	
	/**
	 * get widget instance
	 * @param  string $name
	 * @return \Wslim\View\Widget|NULL
	 */
	static public function widget($name=null)
	{
	    if ($name = static::findClass($name, 'widget')) {
	        $id = 'widget' . ($name ? '.' . $name : '');
	        if (!static::has($id)) {
	            static::setShared($id, function () use ($name) {
	                if ($name instanceof \Wslim\View\Widget) {
	                    return $name::instance();
	                } else {
	                    return new $name;
	                }
	            });
	        }
	        return static::get($id);
	    }
	    return null;
	}
	
	/************************************************************
	 * parse object/class/namespace methods
	 ************************************************************/
	/**
	 * create object by container->get
	 * @param mixed $type
	 * @param array $config
	 * 
	 * @throws InvalidConfigException
	 * 
	 * @return object
	 */
	static public function createObject($type, array $config = [])
	{
		if (is_string($type)) {
			return static::container()->get($type, $config);
		} elseif (is_array($type) && isset($type['class'])) {
			$class = $type['class'];
			unset($type['class']);
			if ($config) $type = array_merge($type, $config); 
			return static::container()->get($class, $type);
		} elseif (is_callable($type, false)) {
			return call_user_func($type, $config);
		} elseif (is_object($type)) {
			return $type;
		} elseif (is_array($type)) {
			throw new InvalidConfigException('object configuration must be an array containing a "class" element.');
		} else {
			throw new InvalidConfigException("Unsupported configuration type: " . gettype($type));
		}
	}
	
	/**
	 * Resolve toResolve into a closure that that the router can dispatch.
	 * 
	 * If toResolve is of the format 'class:method', then try to extract 'class'
	 * from the container otherwise instantiate it and then dispatch 'method'.
	 * 
	 * @param  mixed   $toResolve
	 * @param  boolean $throw 
	 * @return callable
	 *
	 * @throws \RuntimeException if the callable is not resolvable
	 */
	static public function resolveCallable($toResolve, $throw=true)
	{
	    return static::container()->resolveCallable($toResolve, $throw);
	}
	
	/**
	 * resolve object full className or string converted / to \
	 * @param  mixed  $class object or className
	 * @return string $class class name
	 */
	static public function resolveClassName($class)
	{
	    if (is_object($class)) {
	        $class = get_class($class);
	    } else {
	        $class = ltrim($class, '\\');
	    }
	    return $class;
	}
	
	/**
	 * format namespace, from a\b to \\a\\b\\
	 * @param  string|array $namespace
	 * @return string|array
	 */
	static public function formatNamespace($namespace)
	{
	    if (is_array($namespace)) {
	        foreach ($namespace as $k => $v) {
	            $namespace[$k] =  static::formatNamespace($v);
	        }
	    } else {
	        $namespace = trim(str_replace('/', '\\', $namespace), '\\') . '\\';
	    }
	    
	    return $namespace;
	}

	/**
	 * init namespaces
	 * @return void
	 */
	static public function initNamespace()
	{
	    if (!static::$namespaces) {
	        $rnss = [];
	        
	        $nss = Config::get('namespaces');
	        
	        if ($nss) foreach ($nss as $ns => $path) {
	            if (!is_numeric($ns)) {
	                $ns = static::formatNamespace($ns);
	                static::loader()->addPsr4($ns, $path);
	            } else {
	                $ns = static::formatNamespace($path);
	            }
	            $rnss[] = $ns;
	        }
	        
	        $rnss[] = static::formatNamespace(Config::getCommon('commonNamespace', '\\Common'));
	        if (static::app()) {
	            $rnss[] = static::formatNamespace(static::app()->getNamespace());
	        }
	        
	        static::$namespaces = array_unique($rnss);
	        
	        foreach (static::$namespaces as $ns) {
	            $boot = $ns . 'Bootstrap';
	            if (class_exists($boot)) {
	                $boot::init();
	            }
	        }
	    }
	}
	
	/**
	 * set global usable and search namespaces
	 * @param  array $nss
	 * @return array
	 */
	static public function setNamespaces($nss)
	{
	    static::initNamespace();
	    
	    $nss = (array) $nss;
	    $rnss = [];
	    foreach ($nss as $ns => $path) {
	        if (!is_numeric($ns)) {
	            $ns = static::formatNamespace($ns);
	            static::loader()->setPsr4($ns, $path);
	        } else {
	            $ns = static::formatNamespace($path);
	        }
	        $rnss[] = $ns;
	    }
	    static::$namespaces = array_merge(static::$namespaces, $rnss);
	    return static::$namespaces = array_unique(static::$namespaces);
	}
	
	/**
	 * get usable and search namespaces
	 * @param  string $type model|controller and so on
	 * @return array
	 */
	static public function getNamespaces($type=null)
	{
	    static::initNamespace();
	    
	    if ($type) {
	        $nss = [];
	        foreach (static::$namespaces as $k => $v) {
	            $nss[$k] = $v . ucfirst($type) . '\\';
	        }
	        return $nss;
	    } else {
	        return static::$namespaces;
	    }
	}
	
	/**
	 * try find class by shortName, return full class_name
	 * @param string $name
	 * @param string $type
	 * @param string|array $namespaces
	 * 
	 * @return string|null
	 */
	static public function findClass($name, $type=null, $namespaces=null)
	{
	    $name = StringHelper::toClassName($name);
	    if (!$namespaces && strpos($name, '\\') !== false && class_exists($name)) {
	        return $name;
	    }
	    
	    $class_names = [];
	    if (!empty($type)) {
	        $type = StringHelper::toCamelCase($type);
	        if ($pos = strpos($name, '\\')) {
	            $class_names[] = $type . '\\' . $name . $type;
	            $class_names[] = $type . '\\' . $name;
	            
	            $name = substr($name, 0, $pos) . '\\' . $type . '\\' . substr($name, $pos+1);
	        } else {
	            $name = $type . '\\' . $name;
	        }
	        $class_names[] = $name;
	        $class_names[] = $name . $type;
		} else {
		    $class_names[] = $name;
		}
		
		if ($namespaces) {
            $namespaces = (array)static::formatNamespace($namespaces);
		} else {
            //$namespaces = array_unique(array_merge((array)$namespaces, static::getNamespaces()));
		    $namespaces = static::getNamespaces();
		}
		
		foreach ($namespaces as $nss) {
		    foreach ($class_names as $class) { 
		        if (class_exists($nss . $class)) {
		            return $nss . $class;
		        }
		    }
		}
		
		return null;
	}

	/************************************************************
	 * global convenient method
	 ************************************************************/
	/**
	 * request input
	 * @param  string $name
	 * @param  mixed  $default
	 * @return mixed
	 */
	static public function input($name, $default=null)
	{
	    return static::app()->getRequest()->input($name, $default);
	}
    /**
     * replace var
     * @example
     * $url = Ioc::var_replace('{$rootUrl}/a/b');
     * 
     * @param  string $value
     * @param  array  $data
     * @return string
     */
    static public function var_replace($value, $data=[])
    {
        $value = str_replace('{$rootUrl}', Config::getRootUrl(), $value);
        $value = str_replace('{$baseUrl}', static::app()->getCurrentModule()->getBaseUrl(true), $value);
        $value = str_replace('{$appName}', Config::getRootUrl(), $value);
        
        if ($data) {
            // replace {$name}
            preg_match_all('/\{\$([a-z\_\-0-9]+)\s*\}/i', $value, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $v) {
                if (isset($data[$v[1]])) {
                    $value = str_replace($v[0], $data[$v[1]], $value);
                }
            }
            
            // replace ${name}
            preg_match_all('/\$\{([a-z\_\-0-9]+)\s*\}/i', $value, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $v) {
                if (isset($data[$v[1]])) {
                    $value = str_replace($v[0], $data[$v[1]], $value);
                }
            }
        }
        
        return $value;
    }
    
    /**
     * get file absolute path
     * @param  string $path
     * @return string
     */
    static public function path($path)
    {
        return $path;
    }
    
    /**
     * build url
     * 
     * <br>1. '/a/b/c'      => '/rootUrl/a/b/c', with / parsed to rootUrl 
     * <br>2. 'a/b/c'       => '/current_module/a/b/c', not with / parsed to current module
     * <br>3. './a/b/c'     => '/current_url/a/b/c', with ./ parsed to current url
     * <br>4. ':a/b'        => '/a/b', root module
     * <br>5. 'upload:a/b'  => '/uploadUrl/a/b', parsed to option xxxUrl
     * <br>6. 'wx:a/b'      => '/wx_module/a/b', parsed to module xxx
     *
     * @param  string $path
     * @param  mixed  $params string or array
     * @return string
     */
    static public function url($path, $params=[])
    {
        return static::uriManager()->url($path, $params);
    }
    
    /**
     * build url with limit_time_token, like 'some.html?lt_token=sfacxvadfasdf'.
     * notice: $params 同时作为生成token的附加数据
     * 
     * @param  string $path
     * @param  array  $params
     * @return string
     */
    static public function urlWithLtToken($path, $params=[])
    {
        $token = LimitTimeToken::instance()->get($params);
        
        $url = static::url($path, $params);
        
        return static::url($url, ['lt_token' => $token]);
    }
    
    /**
     * relative url
     * @param  string $url
     * @return string
     */
    static public function rUrl($url)
    {
        return static::uriManager()->rUrl($url);
    }
    
}

