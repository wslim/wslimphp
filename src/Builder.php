<?php
namespace Wslim;

use Wslim\Common\Config;
use Wslim\Util\FileHelper;
use Wslim\Common\ErrorInfo;

/**
 * builder
 * 
 * build app(web), module, controller, model, view
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Builder
{
    static protected $appPath;
    
    static public function setAppPath($appPath)
    {
        static::$appPath = rtrim(Config::getRootPath() . $appPath, '/\\') . '/';
    }
    
    protected static function getAppPath()
    {
        return static::$appPath ? : Config::getWebAppPath();
    }
    
    /**
     * default module config
     * @return string[]
     */
    public static function defaultModuleConfig()
    {
        return [
            '__dir__'  => ['config', 'src/Controller', 'src/Model', 'view'],
            '__file__' => [],
            'controller'    => ['index'],
            'model'         => ['demo'],
            'view'          => ['index'],
        ];
    }
    
    static public function build($type, $module=null, $name=null)
    {
        if ($pos = strpos($name, ':')) {
            $names = explode(':', $name, 2);
            $module = $names[0];
            $name   = $names[1];
        }
        
        switch (strtolower($type)) {
            case 'app':
                static::buildApp($module);
                break;
            case 'module':
                static::buildModule($module);
                break;
            case 'controller':
                static::buildController($module, $name);
                break;
            case 'model':
                static::buildModel($module, $name);
                break;
            case 'view':
                static::buildView($module, $name);
                break;
            default:
                static::buildClass($type, $module, $name);
                break;
        }
    }

    /**
     * build dir
     * @access protected
     * @param  array $list
     * @return void
     */
    static public function buildDir($dirlist)
    {
        $appPath = static::getAppPath();
        
        $dirlist = (array) $dirlist;
        foreach ($dirlist as $dir) {
            $path = rtrim($appPath, '\\/') . '/' . trim($dir, '\\/');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * build file
     * @access protected
     * @param  string|array $list
     * @return void
     */
    static public function buildFile($filelist)
    {
        $appPath = static::getAppPath();
        
        $filelist = (array) $filelist;
        foreach ($filelist as $file) {
            
            $dir = rtrim($appPath, '\\/') . '/' . trim(dirname($file), '\\/');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $path = rtrim($appPath, '\\/') . '/' . trim($file, '\\/');
            if (!is_file($path)) {
                file_put_contents($path, 'php' == pathinfo($file, PATHINFO_EXTENSION) ? "<?php\n" : '');
            }
        }
    }

    /**
     * build web app
     * @param string $appPath
     */
    static public function buildApp($appPath='app', $namespace = 'App')
    {
        static::setAppPath($appPath);
        return static::buildModule(null, null, ucfirst($namespace));
    }
    
    /**
     * build module
     * @access public
     * @param  string $module
     * @param  array  $list ['controller'=>..., 'model'=>..., ]
     * @param  string $namespace
     * @param  bool   $suffix
     * @return void
     */
    static public function buildModule($module = '', $list = [], $namespace = 'App', $suffix = false)
    {
        $appPath = static::getAppPath();
        
        $module = $module ? $module : '';
        if (!$list) $list = static::defaultModuleConfig();
        
        // module dir
        if (!is_dir($appPath . $module)) {
            mkdir($appPath . strtolower($module));
        }
        
        // controller, model, view
        foreach ($list as $path => $file) {
            $file = (array) $file;
            
            $modulePath = $appPath . $module . '/';
            if ('__dir__' === $path) {
                // dir
                foreach ($file as $dir) {
                    if (!is_dir($modulePath . $dir)) {
                        mkdir($modulePath . $dir, 0755, true);
                    }
                }
            } elseif ('__file__' === $path) {
                // dummy file
                foreach ($file as $name) {
                    if (!is_file($modulePath . $name)) {
                        file_put_contents($modulePath . $name, 'php' == pathinfo($name, PATHINFO_EXTENSION) ? "<?php\n" : '');
                    }
                }
            } else {
                // mvc part
                foreach ($file as $name) {
                    $name   = trim($name);
                    switch ($path) {
                        case 'controller': 
                            static::buildController($module, $name, $namespace);
                            break;
                        case 'model': 
                            static::buildModel($module, $name, $namespace);
                            break;
                        case 'view': 
                            static::buildView($module, $name);
                            break;
                        default:    
                            static::buildClass($type, $module, $name, $namespace);
                    }
                }
            }
        }
        
        if (basename(Config::getStoragePath()) != $module) {
            // common moduel
            self::buildCommon($module, $namespace);
        }
    }

    /**
     * 创建控制器
     * @access public
     * @param  string $module 模块名
     * @return void
     */
    static public function buildController($module, $name = 'Index', $namespace = 'App')
    {
        $appPath = static::getAppPath();
        
        $name   = $name ? ucfirst($name) : 'Index';
        $suffix = 'Controller';
        $filename = $appPath . ($module ? strtolower($module) . '/' : '') . 'src/Controller/' . $name . $suffix . '.php';
        
        if (!is_file($filename)) {
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            $content = file_get_contents(__DIR__ . '/_stubs/src/Controller/controller.stub');
            $namespace = $namespace . ($module ? '\\' . ucfirst($module) : '');
            $module    = strtolower($module);
            $content = str_replace(['{namespace}', '{module}', '{name}', '{suffix}'], [$namespace, $module, $name, $suffix], $content);
            file_put_contents($filename, $content);
        }
    }
    
    /**
     * 创建模型
     * @access public
     * @param  string $module 模块名
     * @return void
     */
    static public function buildModel($module, $name = 'demo', $namespace = 'App')
    {
        $appPath    = static::getAppPath();
        
        $name       = $name ? ucfirst($name) : 'Demo';
        $suffix     = 'Model';
        $filename   = $appPath . ($module ? strtolower($module) . '/' : '') . 'src/Model/' . $name . $suffix . '.php';
        
        if (!is_file($filename)) {
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            $content = file_get_contents(__DIR__ . '/_stubs/src/Model/model.stub');
            $namespace = $namespace . ($module ? '\\' . ucfirst($module) : '');
            $module    = strtolower($module);
            $content = str_replace(['{namespace}', '{module}', '{name}', '{suffix}'], [$namespace, $module, $name, $suffix], $content);
            file_put_contents($filename, $content);
        }
    }
    
    /**
     * 创建view
     * @access public
     * @param  string $module 模块名
     * @return void
     */
    static public function buildView($module, $name = 'Index')
    {
        $appPath    = static::getAppPath();
        
        $name       = $name ? strtolower($name) : 'index';
        $suffix     = '';
        $filename   = $appPath . ($module ? strtolower($module) . '/' : '') . 'view/' . $name . '.html';
        
        if (!is_file($filename)) {
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            $content = file_get_contents(__DIR__ . '/_stubs/view/index.html');
            $namespace = 'App' . ($module ? '\\' . ucfirst($module) : '');
            $module    = strtolower($module);
            $content = str_replace(['{namespace}', '{module}', '{suffix}'], [$namespace, $module, $suffix], $content);
            file_put_contents($filename, $content);
        }
    }
    
    /**
     * 创建类文件
     * @access public
     * @param  string $module 模块名
     * @return void
     */
    static public function buildClass($type, $module, $name, $namespace = 'App')
    {
        $appPath    = static::getAppPath();
        
        $type       = ucfirst($type);
        $name       = ucfirst($name);
        $suffix     = $type;
        $filename   = $appPath . ($module ? strtolower($module) . '/' : '') . 'src/' . $type . '/' . $name . $suffix . '.php';
        
        if (!is_file($filename)) {
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            $content = '<?php\n namespace {namespace};\n class {name}{suffix}\n {\n}';
            $namespace = $namespace . ($module ? '\\' . ucfirst($module) : '') . '\\' . $type;
            $module    = strtolower($module);
            $content = str_replace(['{namespace}', '{module}', '{name}', '{suffix}'], [$namespace, $module, $name, $suffix], $content);
            file_put_contents($filename, $content);
        }
    }
    
    /**
     * 创建模块的公共文件
     * @access public
     * @param  string $module 模块名
     * @return void
     */
    static public function buildCommon($module, $namespace = 'App')
    {
        $appPath = static::getAppPath();
        
        $filename = $appPath . ($module ? strtolower($module) . '/' : '') . 'config/config.php';
        
        if (!is_file($filename)) {
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            $content = file_get_contents(__DIR__ . '/_stubs/config/config.stub.php');
            $namespace = $namespace . ($module ? '\\' . ucfirst($module) : '');
            $module    = strtolower($module);
            $content = str_replace(['{namespace}', '{module}'], [$namespace, $module], $content);
            file_put_contents($filename, $content);
        }
    }
    
    /**
     * flush routes cache
     * @return \Wslim\Common\ErrorInfo
     */
    static public function flushRoutesCache()
    {
        $cachePath = Config::getCommon('router.cache_file');
        if (FileHelper::delete($cachePath)) {
            return ErrorInfo::success('flush routes cache success');
        } else {
            return ErrorInfo::error('flush routes cache fail, please check config[router.cache_file]');
        }
    }
    
}
