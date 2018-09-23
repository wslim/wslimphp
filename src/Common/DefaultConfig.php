<?php
namespace Wslim\Common;

/**
 * app default config
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class DefaultConfig
{
    /**
     * common config
     * @return array
     */
    static public function common()
    {
        $rootPath = Config::getRootPath();
        
        return [
            // debug
            //'debug'     => 0,
            
            // common path, optional
            'commonPath'    => $rootPath . 'common',
            // storage path, optional
            'storagePath'   => $rootPath . 'storage',
            // web root path, optional
            'webRootPath'   => $rootPath . 'webroot',
            // web app path, optional
            'webAppPath'    => $rootPath . 'app',
            // cli app path, optional
            'cliAppPath'    => $rootPath . 'cli',
            
            // rootUrl, 可为 http 格式或相对路径; 当使用二级域名或根路径不为/时需设置
            'rootUrl'       => '/',
            
            // extra namespaces, it will be loaded by loader, except for Wslim or App or Common
            'namespaces'    => [
                
            ],
            
            // errorHandler
            'error'     => [
                'display_details' => 1
            ],
            
            // router
            'router'    => [
                'url_mode'      => 0,   // 0 动态路径/index.php/article/show, 1 静态路径/article/show 必需项，会继承父级设置
                'cache_file'    => $rootPath . 'storage/routes.cache.php',  // routes 缓存文件
                'module_level'  => 1,   // module_level, 1|2, 模块的深度，如果只支持一级设为1可提高解析性能
            ],
            
            // log
            'log'    => [
                'storage'   => 'file',
                'path'      => 'logs',       //
                'data_format'   => 'tsv',   // 数据类型: json, serialize, csv, tsv, xml
                'level'     => 'ERROR',     // 详细级别由高到低: DEBUG,INFO,NOTICE,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY
            ],
            
            // cache
            'cache'     => [
                'storage'     => 'file',
                'data_format' => 'json',
            ],
            
            // session
            'session' => [
                'enable'        => 1,
                'handler'       => 'native',    // native|storage 后者为使用storage类进行存储
                'storage'       => 'file',      // file|redis|wslim_redis|memcache|memcached
                // 对于文件为相对路径，对于memcache扩展需要加'tcp://'，其他如memcached,redis扩展不需要加
                'save_path'     => 'sessions',
                'session_name'  => 'default',   // session_name() 调用此名称
                'session_ttl'   => 1440,        // default 1440
                'cookie_domain' => '',          // Cookie 作用域
                'cookie_path'   => '/',         // Cookie 作用路径
                'cookie_ttl'    => 0,           // Cookie 生命周期，0 表示随浏览器进程
                'group'         => '',          // 分组,用于区分不同的session,同时作为 cookie_prefix
                'timeout'       => 3,           // 用于缓存服务器连接超时，对于memcache/redis建议小一点，官方的timeout为1s
            ],
            
            // model setting, 优先级高于db
            'model'         => array(
                //'database'        => 'database',        // 数据库名称
                //'table_prefix'    => 'prefix',        // 数据库表前缀
                //'table_name'      => 'table_name',    // 数据库表名称-不含前缀
                //'real_table_name  => 'real_table_name',  // 实际表名-包含表前缀
                //'primary_key'     => 'id',            // primary key
            ),
        ];
    }
    
    /**
     * get default web config
     * @return array
     */
    static public function web()
    {
        $rootPath = Config::getRootPath();
        $webRootPath = Config::getWebRootPath();
        
        return [
            'http'  => [
                'version' => '1.1',
            ],
            
            // uploadUrl, 上传的url, 可为 http 格式或相对根路径
            'uploadUrl'   => '/upload',
            'uploadPath'  => $webRootPath . 'upload',
            
            // need overwrite htmlPath/compiledPath/templatePath
            'views'	=> [
                'pc'    => [
                    //'htmlPath'	=> $webRootPath . 'html',
                    'theme'		=> 'default',
                ],
            ],
            /*
            'widget'  => [
                // cache options
                'cache' => [
                    'path'          => 'caches/widgets',
                    'key_format'    => 'null',
                    'data_format'   => 'serialize',
                ]
            ],
            */
        ];
    }
    
    /**
     * get default console config
     * @return array
     */
    static public function console()
    {
        $rootPath = Config::getRootPath();
        
        return [
            
        ];
    }
    
    
    
}