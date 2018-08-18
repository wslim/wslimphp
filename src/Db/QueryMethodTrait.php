<?php
namespace Wslim\Db;

use Wslim\Util\StringHelper;
use Wslim\Util\Paginator;
use Wslim\Util\DataHelper;
use Wslim\Common\ErrorInfo;

/**
 * query method trait, db and model use it
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait QueryMethodTrait
{
    /**
     * query instance, hold query settings
     * @var \Wslim\Db\Query
     */
    protected $query    = null;
    
    /**
     * get query instance
     * @return \Wslim\Db\Query
     */
    public function getQuery()
    {
        return isset($this->query) && $this->query ? $this->query : $this->query = new Query();
    }
    
    /**
     * set query instance
     *
     * @param \Wslim\Db\Query $query
     * @return static
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
        
        return $this;
    }
    
    /*************************************************************
     * common methods
     *************************************************************/
    /**
     * is directly sql
     * @param  mixed $query
     * @return boolean
     */
    static public function isSql($query)
    {
        if (is_string($query) && !is_numeric($query)) {
            if (preg_match('/^\s*(select|insert|update|delete|show|create|alter|rename|drop|grant|revoke|exec|TRUNCATE|set|declare)\s+/i', $query)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * get current query model
     *
     * @param  mixed   $model string|\Wslim\Db\Model
     * @param  boolean $self  if not model return self
     * @return array|\Wslim\Db\Model
     */
    public function getQueryModel($model=null, $self=true)
    {
        if (!$model) {
            $model = static::getFirstTable();
        }
        
        if ($model && is_string($model)) {
            $model = preg_replace('/^' . $this->getTablePrefix() . '/', '', $model, 1);
            return static::getTableInfo($model);
        } else {
            return $model ? : ($self ? $this : null);
        }
    }
    
    /**
     * format query options
     * @param  mixed  $options can be int(id), string(where), array(where, select, ...)
     * @return array
     */
    static public function formatQueryOptions($options=null)
    {
        if (!isset($options)) {
            return [];
        }
        
        if (is_scalar($options)) {
            $options = ['where' => [$options]];
        } elseif (isset($options['where'])) {
            $options['where'] = (array) $options['where'];
        } else {
            $isWhere = true;
            foreach ($options as $k => $v) {
                if (is_numeric($k)) {
                    unset($options[$k]);
                    $options['where'][] = $v;
                } elseif (in_array($k, ['distinct', 'fields', 'from', 'table', 'order', 'pagesize', 'page', 'result_key'])) {
                    $isWhere = false;
                    break;
                }
            }
            if ($isWhere) {
                $options = ['where' => $options];
            } else {
                $options['where'] = [];
            }
        }
        
        return $options;
    }
    
    /**
     * format table alias for fields or where 
     * @param  mixed  $data      array|string
     * @param  string $table_alias table alias name
     * @param  array  $in_fields
     * @return mixed  array|string
     */
    static public function formatQueryTableAlias($data=null, $table_alias=null, $in_fields=null)
    {
        if ($data && isset($table_alias)) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (!is_numeric($k) && strpos($k, '.') === false) {
                        if(isset($in_fields) && !in_array($k, $in_fields)) continue;
                        
                        $data[$table_alias . '.' . $k] = $v;
                        unset($data[$k]);
                    }
                }
            } elseif (strpos($data, ',')) {
                $data = explode(',', $data);
                foreach ($data as $k => $v) {
                    $v = trim($v);
                    if(isset($in_fields) && !in_array($v, $in_fields)) continue;
                    
                    $data[$k] = $table_alias . '.' . $v;
                }
                $data = implode(',', $data);
            } elseif (strpos($data, '.') === false) {
                $data = trim($data);
                if(isset($in_fields) && in_array($data, $in_fields)) {
                    $data = $table_alias . '.' . $data;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * format query object, it called by query() before real query
     * @param  Query $query
     * @return \Wslim\Db\Query
     */
    protected function formatQuery(Query $query)
    {
        // handle numeric where, from 2 to ['id'=>2]
        if (!$query->formatted() && $where = $query->get('where')) {
            $table = static::getFirstTable($query['table']);
            $atable  = static::getFirstTableAlias($query['table']);
            $model  = static::getQueryModel();
            $pk     = isset($model['primary_key']) ? $model['primary_key'] : null;
            $spk    = $atable . '.' . $pk;
            foreach ($where as $k=>$v) {
                if (is_numeric($k)) {
                    if (is_numeric($v)) {
                        if (!$pk) {
                            throw new Exception('pk is not set:' . $table);
                        }
                        $where[$k] = [$spk => $v];
                    } elseif (is_array($v)) {
                        foreach ($v as $k2=>$v2) {
                            if (is_numeric($k2) && is_numeric($v2)) {
                                if (!$pk) {
                                    throw new Exception('pk is not set:' . $table);
                                }
                                $where[$k][$k2] = [$spk => $v2];
                            }
                        }
                    }
                }
            }
            
            $query->setRaw('where', $where);
        }
        
        return $query;
    }
    
    /**
     * get first table
     * @param  string|array $tables
     * @return string
     */
    public function getFirstTable($tables=null)
    {
        if (!$tables) {
            $tables = static::getQuery()->get('table');
        }
        if ($tables) {
            if (is_string($tables)) {
                return DataHelper::explode(',\s*', $tables)[0];
            } elseif (is_array($tables)) {
                return static::getFirstTable(current($tables));
            }
        }
        
        return $tables;
    }
    
    /**
     * get first table alias
     * @param  mixed $table
     * @return string
     */
    public function getFirstTableAlias($table=null)
    {
        if (!$table) {
            $table = static::getQuery()->get('table');
        }
        if ($table) {
            if (is_array($table)) {
                return static::getFirstTableAlias(current($table));
            }
            
            $table = preg_split('/(\,|(left|right)\s+join)/', $table)[0]; 
            $table = preg_split('/\s+(as\s+)?/', $table);
            $table = isset($table[1]) && $table[1] ? $table[1] : static::buildRealTableName($table[0], false); // 如果没有alias需要使用全名
            $table = trim($table);
        }
        
        return $table;
    }
    
    /**
     * build real table name, like database.tablePrefix + tableName
     * @param  string  $table_name if begin '#' or '.' don't add prefix
     * @param  boolean $include_dbname
     * @return string
     */
    public function buildRealTableName($table, $include_dbname=true)
    {
        $table = explode(' ', $table)[0];
        $table = str_replace('#', '.', $table);
        if (($pos = strpos($table, '.')) !== false) {
            $database = substr($table, 0, $pos);
            $table = substr($table, $pos+1);
        } elseif ($prefix = $this->getTablePrefix()) {
            $database = $this->getDatabase();
            $table = strpos($table, $prefix) === 0 ? $table : $prefix . $table;
        }
        
        return $include_dbname && $database ? $database . '.' . $table : $table;
    }
    
    /*************************************************************
     * data validate and token validate methods
     *************************************************************/
    /**
     * verify unique
     * 
     * @param  array $data
     * @param  array $where
     * @return \Wslim\Common\ErrorInfo
     */
    protected function verifyUnique($data, $where=null)
    {
        $model = static::getQueryModel();
        
        if ($data && isset($model['primary_key']) && isset($model['unique_key'])) {
            if (isset($data[0]) && is_array($data[0])) {
                foreach ($data as $k => $v) {
                    $res = static::verifyUnique($v, $where);
                    if ($res->isError()) {
                        return $res;
                    }
                }
                return ErrorInfo::success();
            } else {
                $uks = StringHelper::toArray($model['unique_key']);
                $pk  = $model['primary_key'];
                $check = true;
                $where = is_numeric($where) ? [$pk => $where] : (array) $where;
                foreach ($uks as $v) {
                    if (!isset($data[$v]) || !$data[$v]) {
                        $check = false;
                        break;
                    }
                    $where[$v] = $data[$v];
                }
                if ($check) {
                    if (isset($data[$pk])) {
                        $where[$pk] = ['<>', intval($data[$pk])];
                    }
                    $exists = static::table($model['table_name'])->exists($where);
                    
                    if ($exists) {
                        return ErrorInfo::error(-101, '请检查记录唯一性');
                    }
                }
                
                return ErrorInfo::success();
            }
        } else {
            return ErrorInfo::success('do not need verify unique.');
        }
    }
    
    /**
     * verify data
     *
     * @param  array    $data
     * @param  array    $excluded_fields 排除不检查的字段
     * @param  boolean  $strict_mode     是否严格模式，会检查是否包含不存在的字段
     *
     * @return \Wslim\Common\ErrorInfo
     */
    public function verifyData($data, $excluded_fields=null, $strict_mode=false)
    {
        $model = static::getQueryModel();
        
        if (!isset($model['fields']) || !isset($model['primary_key'])) {
            return ErrorInfo::error('table has not any fields.');
        }
        
        $errinfo = [];
        if ($data) {
            if (isset($data[0]) && is_array($data[0])) {
                foreach ($data as $k => $v) {
                    $res = static::verifyData($v);
                    if ($res['errcode']) {
                        return $res;
                    }
                }
            } else {
                foreach ($data as $k => $v) {
                    if (isset($model['fields'][$k])) {
                        $field = $model['fields'][$k];
                        
                        $v = FieldInputHandler::formatValue($model['fields'][$k], $v);    // don't modify $data
                        
                        $field_title = isset($model['fields'][$k]['field_title']) ? $model['fields'][$k]['field_title'] : $model['fields'][$k]['field_name'];
                        
                        if (empty($excluded_fields) || !in_array($k, $excluded_fields)) {
                            
                            // check is_nullable and min_length
                            $is_nullable = isset($model['fields'][$k]['is_nullable']) && $model['fields'][$k]['is_nullable'] == 0 ? false : true;
                            $min_length = isset($model['fields'][$k]['min_length']) ? intval($model['fields'][$k]['min_length']) : 0;
                            $min_length = (!$is_nullable) ? $min_length : 0;
                            $is_primary = isset($field['is_primary']) && $field['is_primary'] ? : 0;
                            if (!$is_primary && $min_length && strlen($v) < $min_length) {
                                $errinfo['errcode'] = -201;
                                $errinfo['errmsg'] = $field_title . '不允许为空';    //'至少' . $min_length . '位';
                                break;
                            }
                            
                            // check code type
                            if (isset($model['fields'][$k]['base_type'])) {
                                $base_type = $model['fields'][$k]['base_type'];
                                if (in_array($base_type, ['name', 'code']) && strlen($v) > 0 && !DataHelper::verify_code($v)) {
                                    $errinfo['errcode']  = -202;
                                    $errinfo['errmsg']   = $field_title . '只能取数字字母下划线';
                                    break;
                                }
                            }
                        }
                        
                        // check data_length
                        if (!empty($v) && !empty($model['fields'][$k]['data_length']) && (strlen($v) > $model['fields'][$k]['data_length']) ) {
                            $errinfo['errcode'] = -221;
                            $errinfo['errmsg'] = $field_title . '最大长度为: ' . $model['fields'][$k]['data_length'];
                            break;
                        }
                    } elseif ($strict_mode) {
                        $errinfo['errcode'] = -1;
                        $errinfo['errmsg'] = '数据不正确，不存在字段' . $k;
                        break;
                    }
                }
            }
        }
        
        return ErrorInfo::instance($errinfo);
    }
    
    /**
     * format one or more record input for insert or update, it will remove filed which not in master_fields
     * 
     * @param  array $data
     * @return array
     */
    public function formatInput($data)
    {
        if ($data) {
            if (is_numeric(key($data))) {
                foreach ($data as $k => $v){
                    if (is_numeric($k) && is_array($v)) {
                        $data[$k] = static::formatInputRecord($v);
                    }
                }
            } else {
                $data = static::formatInputRecord($data);
            }
        }
        return $data;
    }
    
    /**
     * format one record input for insert or update, it will remove filed which not in master_fields
     * 
     * @param  array $data
     * @return array
     */
    public function formatInputRecord($data)
    {
        $model = static::getQueryModel();
        
        if (!$data || !isset($model['primary_key']) || !isset($model['fields'])) {
            return $data;
        }
        
        $fdata = [];
        $fields_names = isset($model['master_fields']) ? $model['master_fields'] : array_keys($model['fields']);;
        foreach ($data as $k => $v){
            if (is_numeric($k)) {
                $fdata[$k] = $v;
            } elseif (in_array($k, $fields_names)) {
                $fdata[$k] = FieldInputHandler::formatValue($model['fields'][$k], $v);
            }
        }
        
        // check other autoset field
        if (in_array('update_time', $fields_names) && !isset($fdata['update_time'])) {
            $fdata['update_time'] = time();
        }
        return $fdata;
    }
    
    /**
     * format one or more record output
     *
     * @param  array $data
     * @param  array|\Wslim\Db\Model $model
     * @return array
     */
    public function formatOutput($data, $model=null)
    {
        if ($data) {
            if (is_numeric(key($data))) {
                foreach ($data as $k => $v){
                    if (is_numeric($k) && is_array($v)) {
                        $data[$k] = static::formatOutputRecord($v, $model);
                    }
                }
            } else {
                $data = static::formatOutputRecord($data, $model);
            }
        }
        
        return $data;
    }
    
    /**
     * [overwrite] format one record output
     *
     * @param  array $data
     * @param  array|\Wslim\Db\Model $model
     * @return array
     */
    public function formatOutputRecord($data, $model=null)
    {
        $model = $model ? : $this;
        
        if (!$data || !isset($model['primary_key']) || !isset($model['fields'])) {
            return $data;
        }
        
        $fields_names = array_keys($model['fields']);
        foreach ($data as $k => $v){
            if (in_array($k, $fields_names)) {
                $data[$k] = FieldOutputHandler::formatValue($model['fields'][$k], $v);
            }
        }
        
        return $data;
    }
    
    /*******************************************************************************************
     * advanced execute methods
     * # options can be: select, distinct, from, where, group, having, limit, order, page
     * #                 insert, values, update, set, delete
     * #                 data 是key-value数组，对insert|update|delete 适用，解析成对应的字段和值
     *******************************************************************************************/
    
    /**
     * get rows num
     * @param  string count field
     * @return int
     */
    public function count($field='*')
    {
        $this->getQuery()->type('select')->remove('fields')->set('count', $field);
        
        $result = $this->query();
        
        if ($result) {// get first row's value
            $result = current($result[0]);
        } else {
            $result = 0;
        }
        
        return $result;
    }
    
    /**
     * is exists record
     * @param  mixed   $where string|array
     * @return boolean
     */
    public function exists($where=null)
    {
        if ($where) {
            $this->getQuery()->where($where);
        }
        
        return $this->count() > 0;
    }
    
    /**
     * fetch one
     * @param  mixed $options string|array|int, auto guess where clause
     * @param  bool  $format
     * @return array|null
     */
    public function find($options=null, $format=false)
    {
        if (isset($options)) {
            if (is_numeric($options)) {
                return $this->findById($options);
            } else {
                $this->getQuery()->set($this->formatQueryOptions($options));
            }
        }
        
        $model = static::getQueryModel();
        
        $this->getQuery()->type('select')->set('limit', 1);
        
        $data = $this->query();
        
        if ($data && count($data) > 0) {
            $data = $data[0];
            if ($format) {
                $data = static::formatOutput($data, $model);
            }
        } else {
            $data = null;
        }
        
        return $data;
    }
    
    /**
     * find one by pk, if pk is multi, param is value array, param order must the pk order.
     *
     * @param  int|array $id
     * @param  bool  $format
     * @return array
     */
    public function findById($id, $format=false)
    {
        $query = $this->getQuery();
        
        if (isset($query['table'])) {
            $pk = $this->getPrimaryKey($query['table'][0]);
        } else {
            $pk = $this->getPrimarykey();
        }
        
        if (!isset($pk)) {
            throw new Exception('Can\'t get primary key of table:' . $this->getTableName());
        }
        
        if (!is_array($id)) {
            $pk = StringHelper::toArray($pk)[0];
            $where = [ $pk => intval($id)];
        } else {
            foreach ($pk as $num => $field) {
                $where[] = array( $field => intval($id[$num]) );
            }
        }
        
        return $this->where($where)->find(null, $format);
    }
    
    /**
     * find one field of one record
     * @param  string $field string|array
     * @param  mixed  $options  auto guess where clause
     * @return mixed
     */
    public function findField($field=null, $options=null)
    {
        if ($field) {
            $this->select($field);
        }
        $ret = $this->find($options);
        
        return $ret ? array_values($ret)[0] : null;
    }
    
    /**
     * select all
     * @param  mixed $options string|array, auto guess where clause
     * @param  bool  $format  if format output
     * @return array|null
     */
    public function fetchAll($options=null, $format=false)
    {
        if ($options) {
            $this->getQuery()->set($this->formatQueryOptions($options));
        }
        $this->getQuery()->type('select');
        
        $model = static::getQueryModel();
        
        $data = $this->query();
        if ($format && $data) {
            $data = static::formatOutput($data, $model);
        }
        
        return $data;
    }
    
    /**
     * select one filed values
     * @param  string $field
     * @param  mixed  $options
     * @return array
     */
    public function fetchField($field=null, $options=null)
    {
        $this->getQuery()->type('select');
        if ($options) {
            $this->getQuery()->set(static::formatQueryOptions($options));
        }
        if ($field) {
            $this->getQuery()->remove('fields')->set('fields', $field);
        }
        $fields = StringHelper::explode(',', $this->getQuery()['fields']);
        $fields = array_map(function ($v) {
            $vs = preg_split('/(as)?\s+/', trim($v));
            return $vs[count($vs)-1];
        }, $fields);
        
        $rows = $this->query();
        
        $result = [];
        if ($rows) foreach ($rows as $v) {
            $result[] = $v[$fields[0]];
        }
        return $result;
    }
    
    /**
     * fetch key value array, param fields like ['key_field', 'value_field'] or 'key_field, value_field'
     * @param  mixed $fields string or array
     * @param  array $options
     * @return array
     */
    public function fetchKeyValues($fields=null, $options=null)
    {
        $this->getQuery()->type('select');
        if ($options) {
            $this->getQuery()->set(static::formatQueryOptions($options));
        }
        if ($fields) {
            $this->getQuery()->remove('fields')->set('fields', $fields);
        }
        $fields = StringHelper::explode(',', $this->getQuery()['fields']);
        $fields = array_map(function ($v) {
            $vs = preg_split('/(as)?\s+/', trim($v));
            return $vs[count($vs)-1];
        }, $fields);
        
        $rows = $this->query();
        
        if ($rows) foreach ($rows as $v) {
            if (count($fields) > 2) {
                $result[$v[$fields[0]]][$v[$fields[1]]] = $v[$fields[2]];
            } else {
                $result[$v[$fields[0]]] = $v[$fields[1]];
            }
        }
        
        return $rows ? $result : null;
    }
    
    /**
     * fetch pager, return pager class, 其[data]包含了查询结果数据
     * 
     * @param  mixed $options if string it as where, if array it as [where|table|pagesize|page...]
     * @param  bool  $format  if format output
     * @param  array $pagerOptions
     * $pagerOptions = [ <br>
            'url_rule'          => self::STATIC_URL, // <br>
            'show_style'        => '111',   // 是否显示首尾页、上下一页、中间页 <br>
            'show_pages'        => 5,       // 显示多少页码 <br>
            'first_title'       => '首页',    // 第一页标题 <br>
            'last_title'        => '末页',    // 最后一页标题 <br>
            'prev_title'        => '上一页',   // 前一页标题 <br>
            'next_title'        => '下一页',   // 后一页标题 <br>
        ] // <br>
     *
     * @return \Wslim\Util\Paginator
     */
    public function fetchPager($options=null, $format=false, $pagerOptions=null)
    {
        if ($pagerOptions === true || $pagerOptions === false) {
            $temp = $format;
            $format = $pagerOptions;
            $pagerOptions = $format;
        }
        
        $query = static::getQuery();
        if ($options) {
            $query->set(static::formatQueryOptions($options));
            unset($options);
        }
        
        if (is_numeric($pagerOptions) && $pagerOptions) {
            $pagerOptions = ['page' => $pagerOptions];
        } else {
            $pagerOptions = (array) $pagerOptions;
        }
        
        if (isset($pagerOptions['page'])) {
            $page = $pagerOptions['page'];
        } elseif (isset($query['page'])) {
            $page = $query['page'][0];
        } else {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        }
        $page = max($page, 1);
        
        if (isset($pagerOptions['pagesize'])) {
            $pagesize = $pagerOptions['pagesize'];
        } elseif (isset($query['pagesize'])) {
            $pagesize = $query['pagesize'][0];
        } else {
            $pagesize = $this->options['pagesize'];
        }
        $pagerOptions['page'] = $page;
        $pagerOptions['pagesize'] = $pagesize;
        
        $query->pagesize($pagesize)->page($page);
        
        $model = static::getQueryModel();
        
        // 使用 query clone
        $clone = $query->clone();
        $data = $this->setQuery($clone)->query();
        
        if ($format && $data) {
            $data = static::formatOutput($data, $model);
        }
        
        $count = $data ? $this->setQuery($query)->count() : 0;
        $paginator = Paginator::create($count, $pagesize, $pagerOptions);
        if ($data) {
            $paginator->setData($data);
        }
        
        return $paginator;
    }
    
    /**
     * add record, if where then add not exists, if new return 'is_new'
     * 
     * @param  array $data
     * @param  array $where
     * @return \Wslim\Common\ErrorInfo ['id'=> $db->lastId(), 'is_new'=>1]
     */
    public function add($data=null, $where=null)
    {
        $this->set($data, $where);
        $query = static::getQuery();
        
        // format data
        $mdata = $query->get('data');
        $mwhere = $query->get('where');
        
        // check pk
        if (isset($query['table'])) {
            $pk = $this->getPrimaryKey($query['table'][0]);
        } else {
            $pk = $this->getPrimaryKey();
        }
        if (!$mwhere) {
            if ($pk && isset($data[$pk])) {
                $query->where($pk, $data[$pk]);
                $mwhere = $query->get('where');
            }
        }
        
        // verify data
        if (!$query->formatted()) {
            $mdata = static::formatInput($mdata);
            $query->remove('data')->setRaw('data', $mdata);
            
            $res = static::verifyData($mdata);
            if ($res->isError()) {
                return $res;
            }
        }
        
        if (static::isEmptyData($mdata)) {
            return ErrorInfo::success('没有更新的数据');
        }
        
        $node = null;
        if (!empty($mwhere)) {
            // clone query
            $clone = $query->clone();
            $node = $this->setQuery($clone)->find();    // 判断是否存在记录，存在则更新
        }
        if ($node) {
            if ($pk) {
                $rdata = ['id' => $node[$pk]];
            }
            return ErrorInfo::success('记录已存在', $rdata);
        } else {
            if ($id = $this->setQuery($query)->insert()->query()) {
                return ErrorInfo::success(['id' => $id, 'is_new' => 1]);
            } else {
                return ErrorInfo::error('操作未获取 insertId');
            }
        }
    }
    
    /**
     * update only, return false if update
     * It is different of save, save can be insert or update
     *
     * @param  array $data
     * @param  array|string $where
     * @return \Wslim\Common\ErrorInfo
     */
    public function modify($data=null, $where=null)
    {
        $this->set($data, $where);
        $query = static::getQuery();
        
        // verify data
        $mdata = $query->get('data');
        $mwhere = $query->get('where');
        
        if (!$query->formatted()) {
            $mdata = static::formatInput($mdata);
            $query->remove('data')->setRaw('data', $mdata);
            
            $res = static::verifyData($mdata);
            if ($res['errcode']) {
                return $res;
            }
        }
        
        if (static::isEmptyData($mdata)) {
            return ErrorInfo::success('donot need modify.');
        }
        
        if (!$mwhere) {
            // clone query
            $clone = $query->clone();
            $res = $this->setQuery($clone)->verifyUnique($mdata, $mwhere);
            if ($res->isError()) {
                return $res;
            }
        }
        
        $ret = $this->setQuery($query)->update()->query();
        
        return ErrorInfo::instance($ret === false ? -1 : 0);
    }
    
    /**
     * modify boolean field value 0/1, can be one or more id
     * 
     * @param  string $field
     * @param  mixed  $ids int or array
     * @return \Wslim\Common\ErrorInfo
     */
    public function modifyBooleanField($field, $ids, $status=null)
    {
        $ids = !is_numeric($ids) ? StringHelper::toIntArray($ids) : [$ids];
        
        $ret = 0;
        if ($ids) {
            if (is_null($status)) {
                $set = "{$field} = ({$field} + 1) % 2";
            } else {
                $set = "{$field} = " . intval($status);
            }
            
            $query = $this->getQuery();
            
            if (isset($query['table'])) {
                $pk = $this->getPrimaryKey($query['table'][0]);
            } else {
                $pk = $this->getPrimaryKey();
            }
            
            if (!isset($pk)) {
                throw new Exception('Can\'t get primary key of table:' . $this->getTableName());
            }
            
            $ret = $this->formatted(true)->set($set)->where([$pk => ['in', $ids]])->modify();
        }
        
        return ErrorInfo::instance($ret);
    }
    
    /**
     * insert or update, if update return false or effect number, if insert return insertId
     * It is different of update, update only update the record.
     *
     * @param  array $data
     * @param  mixed $where array|string
     * 
     * @return \Wslim\Common\ErrorInfo ['id'=> $db->lastId()]
     */
    public function save($data=null, $where=null)
    {
        $this->set($data, $where);
        $query = $this->getQuery();
        
        if (isset($query['table'])) {
            $table = $query['table'][0];
            $pk = $this->getPrimaryKey($table);
        } else {
            $table = $this->getTableName();
            $pk = $this->getPrimaryKey();
        }
        if (!isset($pk)) {
            throw new Exception('Can\'t get primary key of table:' . $table);
        }
        if ($data && isset($data[$pk]) && $data[$pk]) {
            $this->where([$pk => $data[$pk]]);
        }
        
        // verify data
        $mdata = $query->get('data');
        $mwhere = $query->get('where');
        
        if (!$query->formatted()) {
            $mdata = static::formatInput($mdata);
            $query->remove('data')->setRaw('data', $mdata);
            
            $res = static::verifyData($mdata);
            if ($res['errcode']) {
                return $res;
            }
        }
        
        if (static::isEmptyData($mdata)) {
            return ErrorInfo::error('没有更新的数据');
        }
        
        if (!$mwhere) {
            // clone query
            $clone = $query->clone();
            $res = static::setQuery($clone)->verifyUnique($mdata, $mwhere);
            if ($res['errcode']) {
                return $res;
            }
        }
        
        $row = null;
        if (!empty($mwhere)) {
            // clone query
            $this->setQuery($query->clone());
            $row = $this->find();
        }
        
        if ($row) {
            $this->setQuery($query)->update()->query();
            
            $id = $row[$pk];    // first row id
        } else {
            $id = $this->setQuery($query)->insert()->query(); // insert id
            
            if (!$id) {
                return ErrorInfo::error('操作未获取 insertId');
            }
        }
        
        return ErrorInfo::success(['id' => $id]);
    }
    
    /**
     * delete record
     * @param  mixed $where
     * @return \Wslim\Common\ErrorInfo
     */
    public function remove($where=null)
    {
        $this->getQuery()->type('delete')->set('where', $where);
        
        $ret = $this->query();
        
        return $ret === false ? ErrorInfo::error('操作出错了') : ErrorInfo::success('删除成功', ['numberOfRows' => $ret]);
    }
    
    /**
     * disable, modify deleted field, auto flush cache
     *
     * @param  mixed $where
     * @return \Wslim\Common\ErrorInfo
     */
    public function disable($where=null)
    {
        $model = static::getQueryModel();
        
        $deletedField = isset($model['deleted_field']) ? $model['deleted_field'] : (isset($model['fields']['deleted']) ? 'deleted' : null);
        if (!$deletedField) {
            return ErrorInfo::error('deleted filed is not exists.');
        }
        
        $data[] = "$deletedField = ($deletedField + 1) % 2";
        $deleteTimeField = isset($model['fields']['delete_time']) ? 'delete_time' : null;
        if ($deleteTimeField) {
            $data[$deleteTimeField] = time();
        }
        if ($where) {
            $this->getQuery()->where($where);
        }
        $mwhere = $this->getQuery()->get('where');
        
        $res = $this->update()->set($data)->query();
        
        // cache flush current model, 当实际表的操作类不是当前类或和当前类是同一个表的类时才更新，避免了 getQueryModel()和this 是同一个类但实例化的表不一样
        if ($this['table_name'] == $model['table_name']) {
            if (is_numeric($where)) {
                $model->cacheFlushByRecord([$model['primary_key'] => $where]);
            } elseif ($mwhere) {
                $model->cacheFlushByRecord($mwhere);
            }
        }
        
        return ErrorInfo::instance($res === false ? -1 : 0);
    }
    
    /*********************************************************************************************
     * chained set method, don't real run query, only assistant to build sql statement
     * 
     * $this->select('id, name')->from('table')->where(['id'=>1])->query()
     *********************************************************************************************/
    
    /**
     * get or set query is formatted, if set true do not format where and input data.
     * 
     * @param  boolean $isFormatted
     * @return static
     */
    public function formatted($isFormatted=null)
    {
        $this->getQuery()->formatted($isFormatted);
        return $this;
    }
    
    /**
     * select fields
     * @param  string|array $fields
     * @return static
     */
    public function select($fields=null)
    {
        if (func_num_args() > 1) {
            $fields = func_get_args();
        }
        $this->getQuery()->type('select')->fields($fields);
        return $this;
    }
    
    /**
     * select distinct fields
     * @param  string|array $fields
     * @return static
     */
    public function distinct($fields)
    {
        $this->getQuery()->set('distinct', $fields);
        return $this;
    }
    
    /**
     * from table
     * @param  mixed  $table string|array if '#table' or '.table' is absolute tablename
     * @param  mixed  $as    string|array as alias
     * @return static
     */
    public function from($table, $as=null)
    {
        $this->table($table, $as);
        return $this;
    }
    
    /**
     * equal from(), only set query table option, it can not modify model tableName
     * 
     * @param  mixed  $table string|array if '#table' or '.table' is absolute tablename
     * @param  mixed  $as    string|array as alias
     * @return static
     */
    public function table($table, $as=null)
    {
        if ($as) {
            if (is_array($table)) {
                foreach ($table as $k => $v) {
                    if (isset($as[$k])) $table[$k] .= ' AS ' . $as[$k];
                }
            } else {
                $table .= ' AS ' . $as;
            }
        }
        $this->getQuery()->set('table', $table);    // 设置query对象的table，用于后边生成查询
        return $this;
    }
    
    /**
     * join 只有两个参数时，第二个参数认为是 on
     * example:   
     *      join('tbl_b as t2 on t1.id=t2.id')
     *      join('tbl_b as t2', ['t1.id=t2.id', 't1.name=t2.name'])
     * 
     * 
     * @param  string       $table
     * @param  string|array $as
     * @param  string|array $on
     */
    public function join($table, $as=null, $on=null)
    {
        if (is_array($table) && isset($table[1]) && is_string($table[1]) && preg_match('/\s+as\s+/', $table[1])) {
            foreach ($table as $v) {
                $this->getQuery()->set('join', $v);
            }
            return $this;
        }
        
        if ($as || $on) {
            $table = [$table, $as, $on];
        }
        $this->getQuery()->set('join', $table);
        return $this;
    }
    
    /**
     * set where of query
     * @param  mixed  $where string|array
     * @param  mixed  $op if not set $value then op is value
     * @param  mixed  $value
     * @return static
     */
    public function where($where, $op=null, $value=null)
    {
        $this->getQuery()->where($where, $op, $value);
        return $this;
    }
    
    /**
     * group, example 'id' or ['name', 'id']
     * @param  mixed  $group string|array
     * @return static
     */
    public function group($group)
    {
        $this->getQuery()->set('group', $group);
        return $this;
    }
    
    /**
     * order, example 'id desc' or ['name', 'id desc']
     * @param  mixed  $order string|array
     * @return static
     */
    public function order($order)
    {
        $this->getQuery()->set('order', $order);
        return $this;
    }
    
    /**
     * current page num, use pager result
     * @param  int $page
     * @return static
     */
    public function page($page)
    {
        $this->getQuery()->set('page', $page);
        return $this;
    }
    
    /**
     * pagesize num, use pager result
     * @param  int $page
     * @return static
     */
    public function pagesize($pagesize)
    {
        $this->getQuery()->set('pagesize', $pagesize);
        return $this;
    }
    
    /**
     * limit, example: 10 or '2,20'
     * @param  mixed  $limit string|int
     * @return static
     */
    public function limit($limit)
    {
        $this->getQuery()->set('limit', $limit);
        return $this;
    }
    
    /**
     * set result key, for select it take as array key
     * @param  mixed  $fields string|array
     * @return static
     */
    public function result_key($fields)
    {
        $this->getQuery()->set('result_key', $fields);
        return $this;
    }
    
    /**
     * set insert table
     * @param  string $table
     * @return static
     */
    public function insert($table=null)
    {
        $this->getQuery()->type('insert')->set('table', $table);
        return $this;
    }
    
    /**
     * set select or insert into fields
     * @param  mixed  $fields string|array
     * @return static
     */
    public function fields($fields)
    {
        $this->getQuery()->set('fields', $fields);
        return $this;
    }
    
    /**
     * fields alias, select or insert into fields
     * @param  mixed  $fields string|array
     * @return static
     */
    public function field($fields)
    {
        $this->getQuery()->set('fields', $fields);
        return $this;
    }
    
    /**
     * set insert values
     * @param  mixed  $data string|array
     * @return static
     */
    public function values($data)
    {
        $this->getQuery()->set('data', $data);
        return $this;
    }
    
    /**
     * set update table
     * @param  string $table
     * @return static
     */
    public function update($table=null)
    {
        $this->getQuery()->type('update')->set('table', $table);
        return $this;
    }
    
    /**
     * set update data
     * @param  string|array $data
     * @param  string|array $where
     * @return static
     */
    public function set($data=null, $where=null)
    {
        if ($data)  {
            $this->getQuery()->set('data', $data);
        }
        if ($where) {
            $this->where($where);
        }
        
        return $this;
    }
    
    /**
     * set delete table
     * @param  mixed  $table string|array
     * @return static
     */
    public function delete($table=null)
    {
        $this->getQuery()->type('delete')->set('table', $table);
        return $this;
    }
    
    /**
     * lock table
     * @return static
     */
    public function lock()
    {
        $this->getQuery()->set('lock', true);
        return $this;
    }
    
    
    protected function isEmptyData($data)
    {
        if (!$data) {
            return true;
        }
        if (is_array($data)) {
            if (isset($data[0]) && !$data[0]) {
                return true;
            } elseif (is_numeric(key($data)) && !current($data)) {
                return true;
            }
        } elseif (is_string($data)) {
            $data = trim($data);
            if (!$data) {
                return true;
            }
        }
        
        return false;
    }
    
}