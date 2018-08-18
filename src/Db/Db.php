<?php
namespace Wslim\Db;

use Wslim\Ioc;
use Wslim\Util\ArrayHelper;
use Wslim\Util\StringHelper;
use Wslim\Db\Exception as DbException;

/**
 * Db 数据操作类，将操作委托给实际驱动类进行处理.
 * 
 * notice: 
 * 1.实例，需要一个配置 options，实例持有 adapter 驱动类
 * 2.主要方法:    query() 可执行查询和更新操作
 * 3.支持链式方法: $db->select('id, name')->table('demo')->where('id', 2)->query();
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Db
{
    /**
     * query method trait
     */
    use QueryMethodTrait;
    
    /**
     * the adapter object
     * 
     * @var \Wslim\Db\AdapterInterface
     */
    private $adapter        = null;
    
    /**
     * property parser, hold the QueryParser object
     * 
     * @var \Wslim\Db\QueryParserInterface
     */
    private $parser         = null;
    
    /**
     * schema
     * @var \Wslim\Db\SchemaInterface
     */
    private $schema         = null;
    
    /**
     * db config options
     * @var array
     */
    private $options        = array();
    
    /**
     * binds, 注意，这里设置的 binds 参数，是为了传递到 adapter 里的
     * notice, 由于 parser 也有 bind 机制且是辅助生成 sql 的，因此这里 db 的方法命名为 bindParams()
     * @var array
     */
    private $binds      = array();
    
    /**
     * last sql
     * @var string
     */
    private $lastSql = null;
    
    /**
     * default config options
     * @var array
     */
    static private $defaultOptions = [
        'adapter'   => 'mysql',
        'host'      => 'localhost',
        'port'      => '3306',
        'username'  => 'test',
        'password'  => 'test',
        'database'  => 'test',
        'charset'   => 'utf8',
        'table_prefix'  => '',
        //'dsn'         => 'mysqli://test:test@localhost:3306/phalcon?table_prefix=master_#utf8',
        'auto_replace'  => '@',  //自动替换表前缀 ，不使用的话注释掉
        'pagesize'      => 15,
    ];
    
    /**
     * construct method
     * @param  array $options
     * @throws \RuntimeException
     * @throws Exception
     */
    public function __construct(array $options=null)
    {
        $options = $options ? static::_parseOptions($options) : [];
        
        // merge options
        $options = ArrayHelper::merge($this->options, $options);
        $this->options = ArrayHelper::merge(static::$defaultOptions, $options);
    }
    
    /**
     * format options
     * @param  array $options
     * @return array
     */
    private function _parseOptions($options=null)
    {
        if (!$options) return [];
        
        // 如果配置为多组式配置，取出其中一组
        $item = current($options);
        if (!isset($options['adapter']) && is_array($item) && isset($item['username'])) {
            $item = ArrayHelper::getItemArray($options);
            $options = array_values($item)[0];
        }
        
        // first check if dsn
        if (isset($options['dsn'])) {
            $options = static::parseDsn($options['dsn']);
        }
        
        // type is alias of adapter
        if (isset($options['type'])) {
            $options['adapter'] = $options['type'];
        }
        if (isset($options['adapter'])) {
            $options['adapter'] = preg_replace('/[^A-Z0-9_\.-]/i', '', $options['adapter']);
            $options['adapter'] = StringHelper::toCamelCase($options['adapter']);
            if ($options['adapter'] == 'Mysqli') {
                $options['adapter'] = 'Mysql';
            }
        }
        
        // dbname is alias of database
        if (isset($options['dbname'])) {
            $options['database'] = $options['dbname'];
            unset($options['dbname']);
        }
        
        // check database
        if (isset($options['database'])) {
            $options['database'] = preg_replace('/[^A-Z0-9_\.-]/i', '', $options['database']);
        }
        
        return $options;
    }
    
    /**
     * get an option
     * @param string $key
     * @return mixed
     */
    public function getOption($key=null)
    {
        if ($key) {
            return isset($this->options[$key]) ? $this->options[$key] : null;
        } else {
            return $this->options;
        }
    }
    
    /**
     * set option
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setOption($key, $value=null)
    {
        if (is_array($key)) {
            $opts = static::_parseOptions($key);
            $this->options = array_merge($this->options, $opts);
        } else {
            $options = static::_parseOptions([$key => $value]);
            $this->options = array_merge($this->options, $opts);
        }
        
        return $this;
    }
    
    /**
     * fetch the db adapter instance
     * 
     * @return \Wslim\Db\AdapterInterface
     */
    public function getAdapter()
    {
        if (is_null($this->adapter) || ! $this->adapter instanceof AdapterInterface) {
            $class = '\\Wslim\\Db\Adapter\\' . $this->options['adapter'] . 'Adapter';
            if (!class_exists($class)) {
                throw new DbException('database adapter is not exists:' . $class);
            }
            
            $this->adapter  =   new $class($this->options);
        }
        
        return $this->adapter;
    }
    
    /**
     * fetch the db QueryParser instance
     * @return \Wslim\Db\QueryParserInterface
     */
    public function getParser()
    {
        if (!isset($this->parser)) {
            // 初始化一个查询解析对象用于辅助生成或链式构造查询语句
            $class = '\\Wslim\\Db\\Parser\\' . $this->options['adapter'] . 'QueryParser';
            if (!class_exists($class)) {
                throw new DbException('QueryParser is not exists:' . $class);
            }
            $this->parser = new $class();
        }
        return $this->parser;
    }
    
    /**
     * get schema instance
     * @throws \Wslim\Db\Exception
     * @return \Wslim\Db\SchemaInterface
     */
    public function getSchema()
    {
        if (!isset($this->schema)) {
            $class = '\\Wslim\\Db\\Schema\\' . $this->options['adapter'] . 'Schema';
            if (class_exists($class)) {
                $this->schema = new $class($this->getAdapter());
            } else {
                throw new DbException('Schema is not exist:' . $class);
            }
        }
        return $this->schema;
    }

    /**
     * fetch the database of the db adapter instance
     * 
     * @return string|null
     */
    public function getDatabase()
    {
        return $this->options['database'];
    }

    /**
     * fetch the instance database table prefix
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->options['table_prefix'];
    }
    
    /**
     * get cache, use it to cache table schema, data cache please use model
     * @return \Wslim\Common\Cache
     */
    public function getCache()
    {
        return Ioc::cache('db');
    }
    
    /**
     * flush cache
     * @return void
     */
    public function flushCache()
    {
        $database = $this->getDatabase();
        
        $tables = static::getTables($database);
        
        $key  = 'db/tables/' . $database;
        static::getCache()->remove($key);
        
        if ($tables) {
            foreach ($tables as $table) {
                $key  = 'db/tableinfo/' . $table;
                static::getCache()->remove($key);
            }
        }
    }
    
    /*********************************************************************
     * DDL
     *********************************************************************/
    /**
     * Get an array of the tables of the database.
     * 
     * @return array
     */
    public function getTables($database=null)
    {
        if (empty($database)) $database = $this->getDatabase();
        
        $key  = 'db/tables/' . $database;
        $data = static::getCache()->get($key);
        if (!$data) {
            $data = $this->getAdapter()->getTables($database);
            static::getCache()->set($key, $data);
        }
        return $data;
    }
    
    /**
     * table exists
     * @param  string $table can be table|prefix_table|dbname.table
     * @return boolean
     */
    public function tableExists($table)
    {
        $database = null;
        if (($pos = strpos($table, '.')) !== false) {
            $database = substr($table, 0, $pos);
            $table = substr($table, $pos + 1);
        } else {
            $table = $this->buildRealTableName($table, false);
        }
        
        $tables = $this->getTables($database);
        
        return in_array($table, $tables);
    }
    
    /**
     * Get an array of the tables of the database.
     *
     * @param string $table
     * @return array
     */
    public function getColumns($table)
    {
        return $this->getAdapter()->getColumns($this->buildRealTableName($table));
    }
    
    /**
     * get table info
     * @param  string $table
     * @return array|null  ['table_name'=>.., 'primary_key'=>'id', 'fields'=>[...]]
     */
    public function getTableInfo($table)
    {
        if (!$this->tableExists($table)) {
            return null;
        }
        
        $rtable = static::buildRealTableName($table, true);
        $key = 'db/tableinfo/' . $rtable;
        $data = static::getCache()->get($key);
        
        if (!$data) {
            $data = ['table_name' => $table];
            $data['fields'] = $this->getColumns($table);
            $pk = [];
            foreach ($data['fields'] as $key => $val) {
                if($val['is_primary']) {
                    $pk[] = $key;
                }
            }
            $data['primary_key'] = $pk ? $pk[0] : null;
            
            static::getCache()->set($key, $data);
        }
        
        return $data;
    }
    
    /**
     * get primary key of table
     * @param  string $table
     * @return string
     */
    public function getPrimaryKey($table)
    {
        $info = static::getTableInfo($table);
        return $info ? $info['primary_key'] : null;
    }
    
    /**
     * create database
     * @return boolean true for success
     */
    public function createDatabase($database=null)
    {
        if (!$database) $database = $this->getDatabase();
        $sql = $this->getSchema()->getCreateDatabaseSql($database);
        return $this->query($sql);
    }
        
    /**
     * create table
     * @param  string $table
     * @param  array  $options
     * @return boolean true for success
     */
    public function createTable($table, $options)
    {
        $table = $this->buildRealTableName($table);
        $sql = $this->getSchema()->getCreateTableSql($table, $options);
        $ret = $this->query($sql);
        return $ret;
    }
    
    /**
     * alter table
     * @param  string $table
     * @param  array  $options
     * @return boolean true for success
     */
    public function alterTable($table, $options)
    {
        $table = $this->buildRealTableName($table);
        $sql = $this->getSchema()->getAlterTableSql($table, $options);
        if ($sql) {
            $ret = $this->query($sql);
        }
        return isset($ret) ? $ret : false;
    }
    
    /**
     * drop table
     * @param  string $table
     * @return boolean true for success
     */
    public function dropTable($table)
    {
        $table = $this->buildRealTableName($table);
        $sql = $this->getSchema()->getDropTableSql($table);
        $ret = $this->query($sql);
        return $ret;
    }
    
    /**
     * get create table sql
     * @param  string $table
     * @return string
     */
    public function getCreateTableSql($table)
    {
        $table = $this->buildRealTableName($table);
        return $this->getSchema()->getCreateTableSql($table);
    }
    
    /**
     * get alter table info from options, result is array, keys has summary and sql
     * @param  string $table
     * @param  array  $options
     * @return array  ['summary'=>'...', 'sql' => []]
     */
    public function getAlterTableInfo($table, $options)
    {
        $table = $this->buildRealTableName($table);
        return $this->getSchema()->getAlterTableInfo($table, $options);
    }
    
    /**
     * get alter table sql from fields, result is sql string
     * @param  string $table
     * @param  array  $options
     * @return string 'sql; sql2; ...'
     */
    public function getAlterTableSql($table, array $options)
    {
        $table = $this->buildRealTableName($table);
        return $this->getSchema()->getAlterTableSql($table, $options);
    }
    
    /**
     * get drop table sql
     * @param  string $table
     * @return string 
     */
    public function getDropTableSql($table)
    {
        $table = $this->buildRealTableName($table);
        return $this->getSchema()->getDropTableSql($table);
    }
    
    /*******************************************************************************************
     * bind methods which use adapter bind, notice parser has it's bind methods
     *******************************************************************************************/
    /**
     * bind param
     * @param  mixed  $name string|array
     * @param  mixed  $value
     * @return static
     */
    public function bindParams($name, $value=null) {
        
        if (is_array($name)) {
            foreach ($name as $k=>$v) {
                $this->binds[':'.$k]  =   $v;
            }
        } elseif ($value) {
            $this->binds[':'.$name] = $value;
        }
        
        return $this;
    }
    
    /*******************************************************************************************
     * parse query and execute query methods
     *
     *******************************************************************************************/
    /**
     * [overwrite] check Query
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
        
        if (!isset($query['database'])) {
            $query['database'] = $this->options['database'];
        }
        
        if (!isset($query['table_prefix'])) {
            $query['table_prefix'] = $this->options['table_prefix'];
        }
        
        // update, delete 不允许空条件执行，为避免误操作
        if (isset($query['type']) && ($query['type'] == 'update' || $query['type'] == 'delete')) {
            if (!isset($query['where']) || empty($query['where']) ) {
                $query['where'] = '1=0';
            }
        }
        
        if (!isset($query['table'])) {
            throw new DbException('query is not set table');
        }
        
        $query = static::formatQuery($query);
        
        return $query;
    }
    
    /**
     * execute query, for update return false or 0|n(affected rows, maybe 0), for insert return id
     * 
     * @param  mixed $query
     * @param  array $params
     * @return mixed false for failure, for update return 0|n(affected rows, maybe 0), for insert return id
     */
    public function query($query=null, $params = [])
    {
        $result = false;
        $result_key = [];
        
        if ($query && static::isSql($query)) {
            $sql = $query;
        } else {
            $result_key = $this->getQuery()->set($query)->get('result_key');
            
            $queryType = $this->getQuery()->type();
            
            // limit select count
            if ((!$queryType || $queryType === 'select') 
                && !isset($this->getQuery()['count']) 
                && !isset($this->getQuery()['limit'])
                && !isset($this->getQuery()['pagesize'])) 
            {
                $this->getQuery()->set('limit', 1000);
            }
            
            // update must set where
            if (($queryType === 'update' || $queryType === 'delete') && !$this->getQuery()->get('where')) {
                Ioc::logger('db')->error('query no set where: ' . json_encode($this->getQuery()->all()));
                return false;
            }
            
            $sql = $this->parse();
        }
        
        if ($params) {
            $this->bindParams($params);
        }
        
        $this->lastSql = $this->formatSql($sql);
        
        // sql log
        Ioc::logger('db')->debug($sql);
        
        try {
            $this->getAdapter()->prepare($sql);
            if (!empty($this->binds)) {
                $this->getAdapter()->bindParams($this->binds);
            }
            $bool = $this->getAdapter()->execute();
            
            if (!$bool) {
                throw new Exception($this->getAdapter()->getErrorMessage());
            }
        } catch (Exception $e) {
            
            $this->getAdapter()->rollback();
            throw new Exception('Excute sql error: ' . $e->getMessage() . ', sql:' . $sql);
        }
        
        $rawStatement = explode(" ", $sql);
        $statement = strtolower(trim($rawStatement[0]));
        
        if ($statement === 'show') {
            $result = $this->getAdapter()->fetchAll($fetchmode);
        } elseif ($statement === 'select') {
            $rkeys = $result_key ? StringHelper::toArray($result_key, ',\s') : null;
            
            while($rs = $this->getAdapter()->fetch()) {
                // 如果指定返回结果集的key，则按key重组数据
                if ($rkeys) {
                    if (count($rkeys) == 1) {
                        $rk1 = trim($rkeys[0]);
                        if (isset($rs[$rk1])) {
                            $result[$rs[$rk1]] = $rs;
                        } else {
                            $result[] = $rs;
                        }
                    } elseif (count($rkeys) == 2) {
                        $rk1 = trim($rkeys[0]);
                        $rk2 = trim($rkeys[1]);
                        if (!empty($rk2)) {
                            $result[$rs[$rk1]][$rs[$rk2]] = $rs;
                        } else {
                            $result[$rs[$rk1]][] = $rs;
                        }
                    }
                } else {
                    $result[] = $rs;
                }
            }
        } elseif ($statement === 'update' || $statement === 'delete') {
            $result = $this->getAdapter()->numberOfRows();
        } elseif ($statement === 'insert') {
            $result = $this->getAdapter()->lastId();
        } else {
            return null;
        }
        
        return $result;
    }
    
    /**
     * execute query, alias of query()
     *
     * @param  mixed $query
     * @param  array $params
     * 
     * @return mixed for update sql return false or 0|n
     */
    public function execute($query=null, $params = [])
    {
        return static::query($query, $params);
    }
    
    /**
     * parse query option to sql
     * @param  mixed  $query \Wslim\Db\Query|array
     * @return string $sql
     */
    public function parse($query=null)
    {
        $query = $this->checkQuery($query);
        
        // sql log
        Ioc::logger('db')->debug($query->all());
        
        return $this->getParser()->parse($query);
    }
    
    /**
     * format sql
     * @param  string $sql
     * @return string
     */
    private function formatSql($sql)
    {
        if (isset($this->options['auto_replace']) && !empty(trim($this->options['auto_replace']))) {
            $sql = str_replace($this->options['auto_replace'], $this->getDatabase() .'.'. $this->getTablePrefix(), $sql);
        }
        return $sql;
    }
    
    /**
     * reset execute data
     * @return static;
     */
    private function resetExecute()
    {
        $this->binds = [];
        
        return $this;
    }
    
    /**
     * close connection.
     * @return mixed
     */
    public function close()
    {
        $method = 'disconnect';
        return call_user_func_array(array($this->getAdapter(), $method), array());
    }
    
    public function beginTransaction()
    {
        $this->getAdapter()->beginTransaction();
    }
    
    public function startTrans()
    {
        $this->getAdapter()->beginTransaction();
    }
    
    public function rollback()
    {
        $this->getAdapter()->rollback();
    }
    
    public function commit()
    {
        $this->getAdapter()->commit();
    }
    
    /**
     * run trans, param is callback, callback() { return bool;}
     * @param  callable $callback
     * @return boolean
     */
    public function trans($callback)
    {
        if (is_callable($callback))
        {
            $this->getAdapter()->beginTransaction();
            
            try {
                $result = $callback($this);
                
                if ($result === false) {
                    $this->getAdapter()->rollback();
                } else {
                    $this->getAdapter()->commit();
                }
            } catch (\Exception $e) {
                $this->getAdapter()->rollback();
                throw $e;
            }
        }
        else
        {
            return false;
        }
    }
    
    /**
     * magic method
     * note 建议实现 __call() 使用实例方法 $db->someMethod() 以调用对应驱动类的实例方法
     */
    /*
     static public function __callStatic($method, $params){
     	return call_user_func_array(array(current(self::$instances), $method), $params);
     }
     */
    
    /**
     * magic method
     * notice: calling the method directly is faster then call_user_func_array() !
     * 
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (method_exists($this->getAdapter(), $method)) {
            return call_user_func_array(array($this->getAdapter(), $method), $params);
            //return $this->getAdapter()->$method($params);
        } else {
            throw new DbException('Db method [' . $method . '] is not exists.');
        }
    }
    
    /****************************************************
     * static methods
     ****************************************************/
    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/database?param1=val1&param2=val2#utf8
     * @static
     * @access private
     * @param string $dsnStr
     * @return array
     */
    static public function parseDsn($dsnStr)
    {
        if( empty($dsnStr) ){
            return false;
        }
        
        $info = parse_url($dsnStr);
        if(!$info) {
            return false;
        }
        $dsn = array(
            'adapter'    =>  $info['scheme'],
            'host'      =>  isset($info['host']) ? $info['host'] : '',
            'port'  =>  isset($info['port']) ? $info['port'] : '',
            'username'  =>  isset($info['user']) ? $info['user'] : '',
            'password'  =>  isset($info['pass']) ? $info['pass'] : '',
            'database'  =>  isset($info['path']) ? substr($info['path'],1) : '',
            'charset'   =>  isset($info['fragment'])?$info['fragment']:'utf8',
        );
        
        if(isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        }else{
            $dsn['params']  =   array();
        }
        foreach ($dsn['params'] as $k=>$v) {
            $dsn[$k] = $v;
        }
        return $dsn;
    }
    
    /**
     * Install the database schema
     *
     * @param  array  $options
     * @param  string $sql
     * @param  string $adapterNamespace
     * @throws \Wslim\Db\Exception
     * @return void
     */
    static public function install($options, $sql, $adapterNamespace)
    {
        $adapterNamespace = !empty($adapterNamespace) ? $adapterNamespace : self::$adapterNamespace;
        $options['adapter']   = preg_replace('/[^A-Z0-9_\.-]/i', '', $options['adapter']);
        $class = self::$adapterNamespace . ucfirst(strtolower($options['adapter'])) . 'Adapter';
        
        if (!class_exists($class)) {
            throw new DbException('The database adapter ' . $class . ' is not valid.');
        }
        // If Sqlite
        if ( strtolower($options['adapter']) == 'sqlite' || strtolower($options['adapter']) == 'pdo') {
            if (!file_exists($options['database'])) {
                touch($options['database']);
                chmod($options['database'], 0777);
            }
            if (!file_exists($options['database'])) {
                throw new DbException('Could not create the database file.');
            }
        }
        
        $conn  = new $class($options);
        $lines = file($sql);
        
        // Remove comments, execute queries
        if (count($lines) > 0) {
            $insideComment = false;
            foreach ($lines as $i => $line) {
                if ($insideComment) {
                    if (substr($line, 0, 2) == '*/') {
                        $insideComment = false;
                    }
                    unset($lines[$i]);
                } else {
                    if ((substr($line, 0, 1) == '-') || (substr($line, 0, 1) == '#')) {
                        unset($lines[$i]);
                    } else if (substr($line, 0, 2) == '/*') {
                        $insideComment = true;
                        unset($lines[$i]);
                    }
                }
            }
            
            $sqlString  = trim(implode('', $lines));
            $newLine    = (strpos($sqlString, ";\r\n") !== false) ? ";\r\n" : ";\n";
            $statements = explode($newLine, $sqlString);
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    if (isset($options['table_prefix'])) {
                        $statement = str_replace('[{table_prefix}]', $options['table_prefix'], trim($statement));
                    }
                    $conn->query($statement);
                }
            }
        }
    }
    
    /**
     * Check the database
     *
     * @param  array  $options
     *
     * @return string if success return null, failure return error message
     */
    static public function check($options)
    {
        $error = ini_get('error_reporting');
        error_reporting(E_ERROR);
        
        try {
            // Test the db connection
            $options['adapter']   = preg_replace('/[^A-Z0-9_\.-]/i', '', $options['adapter']);
            $class = self::$adapterNamespace . ucfirst(strtolower($options['adapter'])) . 'Adapter';
            
            if (!class_exists($class)) {
                return 'db adapter ' . $class . ' is not valid.';
            } else {
                $conn = new $class($db);
            }
            error_reporting($error);
            return null;
        } catch (Exception $e) {
            error_reporting($error);
            return $e->getMessage();
        }
    }
    
}
