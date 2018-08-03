<?php
namespace Wslim\Db;

use Wslim\Util\StringHelper;
use Wslim\Util\ArrayHelper;
use Wslim\Common\Config;
use Wslim\Common\Configurable;
use Wslim\Ioc;

/**
 * ### 示例代码
 * 初始化一个model或继承类后，然后可以,调用相关方法query()、链式方法、高级查询方法.  <br>
 * 
 * ```
 *  $model = new Model('products');
 *  
 *  $result = $model->select('id, title')->from('othertable')->where(['id' => 2])->query();
 *  
 *  if ($result !== false) { 
 *      echo '[success]'; 
 *  } else {
 *      echo '[fail]';
 *  }
 *  
 *  $res = $model->add($data);
 * ```
 * 
 * ### 关于模型的 database tablePrefix tableName realTableName <br>
 * 继承类可设置property options['database'=> ..., ...] <br>
 * 即时实例化类可注入构造参数,  new Model('database.tableName', $options=[]) <br>
 * 而查询时又会使用具体的 db, db 会传递 database, tablePrefix 参数 <br>
 * 优先级： 优先使用model设置的；未设置则使用 db 传递的；否则 throw error <br>
 * <br>
 * ### 关于查询语句的生成和使用的几种方式 <br>
 * 1.使用 query($options) <br>
 *      $options['where'] = ['id3'=>3, 'id4'=>4];
 *      $options['group'] = ['id,name'];    //'id', 'id,name', [1, 'name'];
 *      $options['having'] = 'count(id)>3';
 *      $options['order'] = ['a description', 'b asc'];    // '1' | 'a description'|['a description', 'b asc']|['a'=>'description']
 *      $options['page'] = 4; //
 *      $options['limit'] = 20; //
 *      $sql = $model->parse($options);   // 仅返回查询sql
 *      $result = $model->query($options);
 * 
 * 2.使用链式设置方法 + query() <br>
 *      $sql = $model->select()->distinct('id,name')->from('table')->where(['id'=>3])->parse();     // 仅返回查询sql
 *      $res = $model->select('id, title')->from('table')->where(['id'=>3])->query();
 *      
 *      $res = $model->insert('demo')->values(['id'=>3, 'name'=>'aaa'])->query();
 *      $res = $model->update('demo')->set(['name'=>'aaa'])->where(['id'=>3])->query();
 *      $res = $model->delete('demo')->where(...)->query();
 * 
 * 3.使用高级执行方法 find/findById/fetchKeyValues/fetchPager/add/save/remove <br>
 *      $model->add(['id'=>1, 'title'=>'aaa']);
 *      $model->save(['name'=>'na', 'title'=>'aaa'], ['id'=>1]);
 *      $model->table('table')->save(['name'=>'na', 'title'=>'aaa'], ['id'=>1]);
 *      $model->find();
 *      $model->fetchKeyValues('id, title', ['where' => ...]);
 *      $model->fetchPager()  返回分页列表，具体看方法参数
 * 
 * 4.链式设置方法和高级执行方法混用 <br>
 *      $model->where(['id'=>1])->save(['name'=>'na', 'title'=>'aaa']);
 *      $model->select('id, title')->where(['id'=>1])->getKeyValues();
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Model extends Configurable
{
    /**
     * use query method trait
     */
    use QueryMethodTrait;
    
    /**
     * use cache trait
     */
    use \Wslim\Common\CacheAwareTrait;
    
    /**
     * object options, extends can config options
     * @var array
     */
    //protected $options = []; // Configurable has this property
    
    /**
     * @var Db
     */
    protected $db = null;
    
    /**
     * static default options
     * @var array
     */
    static protected $defaultOptions = [
        //'database'        => 'database',          // 数据库名称
        //'table_prefix'    => 'prefix',            // 数据库表前缀
        //'table_name'      => 'table_name',        // 数据库表名称-不含前缀
        //'real_table_name  => 'real_table_name',   // 实际表名-包含表前缀
        //'primary_key'     => 'id',                // primary key
        //'unique_key'      => 'name',              // unique_key
        'pagesize'          => 12,
        'deleted_field'     => 'deleted'
    ];
    
    /**
     * Holds model instances.
     * 
     * @var static[]
     */
    static protected $instances = [];
    
    /**
     * all instances
     * @return static[]
     */
    static public function instances()
    {
        return static::$instances;
    }
    
    /**
     * get instance
     * @param  mixed  $key
     * @param  array  $options
     * 
     * @return static
     */
    static public function instance($key=null, $options=null)
    {
        $options = (array) $options;
        
        if ($key) {
            if ($key instanceof \Wslim\Db\Model) {
                $obj = $key;
                if ($options) {
                    $obj->setOption($options);
                }
                return $obj;
            }
            
            if (is_array($key)) {
                $options = ArrayHelper::merge($key, $options);
            } else {
                $options['model_name'] = $key;
            }
        }
        
        // uniform model_name
        $model_name = isset($options['model_name']) ? $options['model_name'] : (isset($options['table_name']) ? $options['table_name'] : null);
        
        // if is created
        $inkey = $model_name ? : get_called_class();
        if (isset(static::$instances[$inkey])) {
            $obj = static::$instances[$inkey];
            if ($options) {
                $obj->setOption($options);
            }
            return $obj;
        }
        
        if ($model_name) {
            // 当为查找类时，一定要 unset model_name, 否则当前类初始化时，执行查询实例化其他模型不能完成
            if (!is_numeric($model_name) && $class = Ioc::findClass($model_name, 'Model')) {
                unset($options['model_name']);
                $obj = new $class($options);
            }
            // 当使用本类传参实例化时，如 UserModel::instance('demo') 当传参的类不存在时，会实例化一个Model基类表名使用的是 demo
            else {
                $options['table_name'] = $model_name;
                unset($options['model_name']);
                $obj = static::createInstance($options);
            }
        } else {
            $obj = new static($options);
        }
        
        // add into static instances, do not add class_name, could be use same class for multi table.
        if (isset($obj['model_name'])) {
            static::$instances[$obj['model_name']] = $obj;
        } elseif (isset($obj['table_name']) && (!isset($obj['base_model']) || $obj['base_model'] != $obj['table_name'])) {
            static::$instances[$obj['table_name']] = $obj;
        }
        
        if (isset($obj['model_id'])) {
            static::$instances[$obj['model_id']] = $obj;
        }
        
        $classkey = get_class($obj);
        if (!isset(static::$instances[$classkey])) {
            static::$instances[$classkey] = $obj;
        }
        
        return $obj;
    }
    
    /**
     * create instance
     * @return static
     */
    static protected function createInstance($options)
    {
        return new static($options);
    }
    
    /**
     * dump, use to debug
     * @return void
     */
    static public function dumpInstances()
    {
        foreach (static::$instances as $k => $v) {
            echo $k . ':' . get_class($v) . ':'; print_r($v->getOption()); PHP_EOL;
        }
    }
    
    
    /**
     * construct, try parse param $name to database.tableName, not contain tablePrefix
     * 
     * @param mixed $options string or array
     */
    public function __construct($options=null)
    {
        if ($options) {
            if (is_string($options)) {  // string, as table name
                $this->options['table_name'] = $options;
            } elseif (is_array($options)) {
                $this->options = ArrayHelper::merge($this->options, $options);
            }
        }
        
        if ($coptions = (array) Config::get('model')) {
            $this->options = ArrayHelper::merge($coptions, $this->options);
        }
        
        $this->options = ArrayHelper::merge(static::$defaultOptions, $this->options);
        
        // uniform model_name and table_name
        if (isset($this->options['model_name']) && !is_numeric($this->options['model_name']) && !isset($this->options['base_model'])) {
            $this->options['table_name'] = $this->options['model_name'];
        }
        
        // uniform table_name
        if (isset($this->options['table_name']) && $this->options['table_name']) {
            $this->setTableName($this->options['table_name']);
        }
        
        // options value to lower
        array_walk($this->options, function(&$v, $k){
            if (is_string($v)) $v = strtolower($v);
        });
        
        // set cache
        $this->setCache(Ioc::cache('model'));
        
        // derived class init
        $this->init();
    }
    
    /**
     * derived class init, use to load table/model info
     * 
     * @return void
     */
    protected function init()
    {
        if (!isset($this['fields'])) {
            $model = $base = [];
            if (isset($this->options['base_model']) && $this->options['base_model']) {
                $base = static::getTableInfo($this->options['base_model']) ? : [];
            }
            if (isset($this->options['table_name']) && (!$base || $this->options['table_name'] != $base['table_name'])) {
                $model = $this->getTableInfo($this->options['table_name']) ? : [];
            }
            
            if ($model && $base) {
                $model['ext_table']  = $model['table_name'];
                $model['ext_fields'] = isset($model['master_fields']) ? $model['master_fields'] : array_keys($model['fields']);
                
                $model['master_table'] = $base['table_name'];
                $model['master_fields'] = isset($base['master_fields']) ? $base['master_fields'] : array_keys($base['fields']);
            }
            
            // merge
            $model = ArrayHelper::merge($base, $model);
            $this->setOption($model);
        }
    }
    
    /**
     * derived class init data, do only once
     * @return \Wslim\Common\ErrorInfo
     */
    public function initData() {}
    
    /**
     * get db instance
     * @return Db
     */
    public function getDb()
    {
        return $this->db ? $this->db : $this->db = Ioc::db();
    }
    
    /**
     * set db instance
     * @param  Db $db
     * @return static
     */
    public function setDb(Db $db)
    {
        $this->db = $db;
        return $this;
    }
    
    /**
     * get the database
     * @return string
     */
    public function getDatabase()
    {
        if (isset($this->options['database'])) {
            return $this->options['database'];
        } else {
            return $this->getDb()->getDatabase();
        }
    }
    
    /**
     * get table prefix
     * @return string
     */
    public function getTablePrefix()
    {
        if (isset($this->options['table_prefix'])) {
            $tablePrefix = $this->options['table_prefix'];
        } else {
        	$tablePrefix = $this->getDb()->getTablePrefix();
        }
        
        return $tablePrefix;
    }
    
    /**
     * get real table name, contain database
     * 
     * @access public
     * @return string
     */
    public function getTableName($includeDbname=false)
    {
    	if (!isset($this->options['real_table_name']) || empty($this->options['real_table_name'])) {
            if(!isset($this->options['table_name']) || empty($this->options['table_name'])) {
                throw new Exception('table is not exist:' . get_called_class());
            }
            
            $realTableName = $this->options['table_name'];
            if (strpos($realTableName, '.') === false && stripos($realTableName, $this->getTablePrefix()) === false) {
                $realTableName = $this->getTablePrefix() . $realTableName;
            }
        } else {
            $realTableName = $this->options['real_table_name'];
        }
        if (isset($realTableName) && strpos($realTableName, '.') === false && $includeDbname) {
            $database = $this->getDatabase();
            $realTableName = (empty($database) ? '' : $database . '.') . $realTableName;
        }
        return $realTableName;
    }
    
    /**
     * set model name.
     * if options database or table_name is not set, try to set by name.
     * @param  string $table
     * @return static
     */
    public function setTableName($table)
    {
        // 1. 尝试解析为 [数据库名.表名]
        if(strpos($table, '.') !== false) {
            list($database, $table) = explode('.', $table);
            if ($database)  {
                $this->options['database'] = $database;
                $this->options['real_table_name'] = $table;
            }
        }
        // 2. 尝试分离出命名空间和表名
        else {
            $table = StringHelper::toClassLastName($table, 'model');
        }
        
        $table = StringHelper::toUnderscoreVariable($table);
        if (substr($table, 0, 1) === '#') {
            $this->options['real_table_name'] = $this->options['table_name'] = str_replace('#', '', $table);
        } else {
            $this->options['table_name'] = preg_replace('/^' . static::getTablePrefix() . '/i', '', $table);
        }
        
        return $this;
    }
    
    /**
     * get primary key
     * @access public
     * @param  string $table
     * @return string
     */
    public function getPrimarykey($table=null) 
    {
        if ($table) {
            return $this->getDb()->getPrimaryKey($table);
        } else {
            if (!isset($this->options['primary_key'])) {
                $pk = $this->getDb()->getPrimaryKey($this->getTableName());
                $this->options['primary_key'] = $pk;
            }
            
            return $this->options['primary_key'];
        }
    }
    
    /**
     * get unique key
     * @return array
     */
    public function getUniqueKey()
    {
        if (!isset($this->options['unique_key'])) {
            $this->options['unique_key'] = null;
        }
        if ($this->options['unique_key'] && is_string($this->options['unique_key'])) {
            $this->options['unique_key'] = StringHelper::toArray($this->options['unique_key'], ',');
        }
        
        return $this->options['unique_key'];
    }
    
    /**
     * [can overwrite]get table info
     * 
     * @param  string     $table
     * @return array|null ['table_name'=>.., 'primary_key'=>[id, ..], 'fields'=>[...]]
     */
    public function getTableInfo($table=null)
    {
        if (!$table) {
            if (!isset($this['fields'])) {
                $table = $this->getTableName();
                $info = $this->getDb()->getTableInfo($table);
                if ($info) $this->setOption($info);
            }
            return $this->options;
        } else {
            return $this->getDb()->getTableInfo($table);
        }
    }
    
    /**
     * find field name by base_type
     * @param  string $base_type
     * @return string
     */
    public function getFieldNameByBaseType($base_type)
    {
        $this->getTableInfo();
        $field = null;
        if (isset($this['fields'])) {
            if (isset($this['fields'][$base_type])) {
                $field = $base_type;
            }
            foreach ($this['fields'] as $v) {
                if (isset($v['base_type']) && $v['base_type'] == $base_type) {
                    $field = $v['field_name'];
                    break;
                }
            }
        }
        
        return $field;
    }
    
    /*************************************************************
     * cache methods
     *************************************************************/
    /**
     * [overwrite] flush cache by record array
     * @param  array $row
     * @return void
     */
    public function cacheFlushByRecord($row)
    {
        
    }
    
    
    /*************************************************************
     * data validate and token validate methods
     *************************************************************/
    /**
     * ensure remain public data
     * @param  array $data
     * @return array
     */
    public function remainPublicData($data)
    {
        if ($data && isset($this['fields']) && isset($this['public_fields']) && $this['public_fields']) {
            if (isset($data[0]) || is_numeric(key($data))) {
                foreach ($data as $k => $v) {
                    $data[$k] = static::remainPublicData($v);
                }
            } else {
                $fields = array_keys($this['fields']);
                $pfields = StringHelper::toArray($this['public_fields']);
                foreach ($data as $k => $v) {
                    if (in_array($k, $fields) && !in_array($k, $pfields)) {
                        unset($data[$k]);
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * ensure remain public data
     * @param  array $data
     * @return array
     */
    public function filterProtectedData($data)
    {
        if ($data && isset($this['fields']) && isset($this['protected_fields']) && $this['protected_fields']) {
            if (isset($data[0]) || is_numeric(key($data))) {
                foreach ($data as $k => $v) {
                    $data[$k] = static::filterProtectedData($v);
                }
            } else {
                $fields = array_keys($this['fields']);
                $pfields = StringHelper::toArray($this['protected_fields']);
                foreach ($data as $k => $v) {
                    if (in_array($k, $fields) && in_array($k, $pfields)) {
                        unset($data[$k]);
                    }
                }
            }
        }
        
        return $data;
    }
    
    /*************************************************************
     * query methods:
     * 
     * basic execute method: query() or execute()
     *************************************************************/
    /**
     * check Query
     * @param  mixed $query
     * @return \Wslim\Db\Query
     */
    protected function checkQuery($query=null)
    {
        if ($query) {
            $this->getQuery()->set($query);
            unset($query);
        }
        
        $query = $this->getQuery();
        
        // if no table then set table
        if (!isset($query['table'])) {
            $query['table'] = $this->getTableName();
        }
        
        // use model database.table_prefix
        if (!isset($query['database']) && isset($this['database'])) {
            $query['database'] = $this['database'];
        }
        
        if (!isset($query['table_prefix']) && isset($this['table_prefix'])) {
            $query['table_prefix'] = $this['table_prefix'];
        }
        
        return static::formatQuery($query);
    }
    
    /**
     * query 
     * 
     * @param  mixed $query string|array|null
     * 
     * @return mixed for select return array, for update return false or 0|n
     */
    public function query($query=null)
    {
        if (!static::isSql($query)) {
            $this->checkQuery($query);
            $query = null;
        }
        
        return $this->getDb()->setQuery($this->getQuery())->query($query);
    }
    
    /**
     * execute, alias of query()
     *
     * @param  mixed $query string|array|null
     *
     * @return mixed for select return array, for update return false or 0|n
     */
    public function execute($query=null)
    {
        return static::query($query);
    }
    
    /**
     * parse query to sql, not execute sql
     * 
     * @param  array  $query
     * @return string $sql
     */
    public function parse($query=null)
    {
        $this->checkQuery($query);
        
        return $this->getDb()->setQuery($this->getQuery())->parse();
    }
    
    /**
     * get empty record
     * @return array
     */
    public function getEmptyRecord()
    {
        $data = [];
        if ($this->fields) foreach ($this->fields as $k => $v) {
            $data[$k] = '';
        }
        
        return static::formatOutput($data);
    }
    
    /**
     * magic method, call query intstance method or db method
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params){
        // calling the method directly is faster then call_user_func_array() !
        if (in_array($method, Query::$allowedSetMethods)) {
            $this->getQuery()->set($method, $params);
            return $this;
        } else {
            return call_user_func_array(array($this->getDb(), $method), $params);
        }
    }
    
}
