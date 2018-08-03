<?php
namespace Wslim\Db\Parser;

use Wslim\Db\QueryParserInterface;
use Wslim\Db\Query;
use Wslim\Db\Exception as DbException;
use Wslim\Util\StringHelper;

class AbstractQueryParser implements QueryParserInterface
{
    /**
     * operator
     * @var array
     */
    static protected $operators = [
        'eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=',
        '=' => '=', '<>' => '<>', '>' => '>', '<' => '<', '>=' => '>=' , '<=' => '<=',
        'notlike'=>'NOT LIKE','like'=>'LIKE', 'not like'=>'NOT LIKE',
        'in'=>'IN','notin'=>'NOT IN','not in'=>'NOT IN',
        'between'=>'BETWEEN','not between'=>'NOT BETWEEN','notbetween'=>'NOT BETWEEN'
    ];
    
    /**
     * allow parse method
     */
    static protected $allowedParseMethods = [
        'select', 'distinct', 'fields', 'from', 'join', 'where', 'group', 'having', 'order', 'limit',
        'insert', 'fields', 'values',
        'update', 'data',
        'delete',
        'bind', 'table',
    ];
    
    /**
     * The SQL query (if a direct query string was provided).
     *
     * @var    string
     */
    protected $sql = null;
    
    /**
     * Property dateFormat.
     *
     * @var  string
     */
    protected $dateFormat = 'Y-m-d H:i:s';
    
    /**
     * The null or zero representation of a timestamp for the database driver.  This should be
     * defined in extends classes to hold the appropriate value for the engine.
     *
     * @var    string
     */
    protected $nullDate = '0000-00-00 00:00:00';
    
    /**
     * Property nameQuote.
     *
     * @var  string
     */
    protected $nameQuote = '"';
    
    /**
     * query
     * @var \Wslim\Db\Query
     */
    protected $query = null;
    
    /*******************************************************************************************
     * main implements methods
     *******************************************************************************************/
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\QueryParserInterface::parse()
     */
    public function parse($query=null, $isCheckSql=true)
    {
        if (! $query instanceof Query) $query = new Query($query);
        
        $this->query = $query;
        
        // guess query type
        $queryType = $this->query->type();
        
        // before parse
        $this->beforeParse();
        
        // preprocess: check and format basic options
        $preprocessMethod = 'preprocess' . ucfirst($queryType);
        $this->$preprocessMethod();
        
        // check sql
        if ($isCheckSql) {
        	$checkMethod = 'check' . ucfirst($queryType);
        	$this->$checkMethod();
        }
        
        /**
         * get query template, template is string placeholder by set key
         * example: %select% %from% %where%
         */
        $getTemplateMethod = 'get' . ucfirst($queryType) . 'Template';
        $sqlTemplateKeys = explode(' ', $this->$getTemplateMethod());
        $queryKeys = $this->query->keys();
        
        foreach ($sqlTemplateKeys as $k=>$v) {
            if (strpos($v, '%') !== false) {
                $vk = str_replace('%', '', $v);
                if (!in_array($vk, $queryKeys)) {
                    unset($sqlTemplateKeys[$k]);
                }
            }
        }
        
        $sql = implode(' ', $sqlTemplateKeys);
        
        /**
         * parse, replace template use set value, get sql
         */
        foreach ($sqlTemplateKeys as $key=>$val) {
            $valk = str_replace(['%', '$'], '', $val);
            $sql  = str_replace($val, $this->parseQueryItem($valk, $this->query[$valk]), $sql);
        }
        $sql = $this->parseBind($sql);
        
        $sql = trim(preg_replace('/\s+/', ' ', $sql), ' ');
        
        // clear 
        $this->query->clear();
        
        return $sql;
    }
    
    /*******************************************************************************************
     * format method
     *******************************************************************************************/
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\QueryParserInterface::formatKey()
     */
    public function formatKey($key, $hasAlias=false)
    {
        $key   =  trim($key);
        //TODO
        return $key;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\QueryParserInterface::formatValue()
     */
    public function formatValue($value, $hasAlias=false)
    {
        if (is_array($value)) {
            if (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
                $value =  $this->escapeString($value[1]);
            } else {
                $value =  array_map(array($this, 'formatValue'),$value);
            }
        } elseif (is_string($value) ) {
            if (strpos($value, '\'') !== 0) { // 字符串两边加引号，如已经调用过一次，则不重复加
                $value =  '\'' . $this->escapeString($value) . '\'';
            }
        } elseif(is_bool($value)){
            $value =  $value ? 1 : 0;
        } elseif(is_null($value)){
            $value =  'NULL';
        }
        return $value;
    }
    
    /**
     * format expression
     * @param  string $expression
     * @return mixed
     */
    public function formatExpression($expression)
    {
        return $expression;
    }
    
    /**
     * format table, add dbname and table_prefix, instead of db
     * @param  string $params
     * @return string
     */
    public function formatTableName($params)
    {
        $params = trim($params);
        if (strpos($params, ',') !== false){
            $tables = explode(',', $params);
            foreach ($tables as $k=>$v) {
                $tables[$k] = $this->formatTableName($v);
            }
            $params = implode(',', $tables);
        } elseif (preg_match('/(\s(as\s)?)/i', $params, $matches)) { // 含空格或as分隔
            $arr = explode($matches[1], $params, 2);
            $params = $this->formatTableName($arr[0]) . $matches[1] . $arr[1];
            unset($arr, $matches);
        } else {
            $params = str_replace('#', '.', $params);
            $pos = strpos($params, '.');
            if ($pos === 0) {
                return ltrim($params, '.');
            } elseif ($pos === false && (isset($this->query['table_prefix'][0])|| isset($this->query['database'][0]))) {
                // 名称不含 . 且表前缀不为空 且名称不是前缀的 进行加库名加前缀处理
                $table_prefix = isset($this->query['table_prefix'][0]) ? $this->query['table_prefix'][0] : '';
                $database = isset($this->query['database'][0]) ? $this->query['database'][0] . '.' : '';
                if($table_prefix && !StringHelper::startsWith($params, $table_prefix, false)) {
                    $params = $this->formatKey($table_prefix . $params);
                }
                $params = $this->formatKey($database) . $params;
            }
        }
        return $params;
    }
    
    /**
     * sql security slashes
     * @access public
     * @param  string $str
     * @return string
     */
    public function escapeString($str) 
    {
        return addslashes($str);
    }

    /*******************************************************************************************
     * protected methods: preprocess options 
     *******************************************************************************************/
    /**
     * handle somethind before parse, common handle for every query
     */
    protected function beforeParse()
    {
        // has alias
        if( isset($this->query['table'])) {
            $tables_str = implode(',', $this->query['table']);
            if (preg_match('/((?:as|\,|join)\w+)/i', $tables_str) > 0) {
                $this->query->hasAlias(true);
            }
        }
    }
    
    /**
     * preprocess select
     */
    protected function preprocessSelect()
    {
        // handle fields
        if (!isset($this->query['fields']) && !isset($this->query['distinct'])) {
            if (isset($this->query['count'])) {
                $this->query['fields'] = 'count(*)';
            } else {
                $this->query['fields'] = '*';
            }
        }
        
        if (isset($this->query['count']) || (is_string($this->query['fields'][0]) && strpos('count(', $this->query['fields'][0]) !== false)) {
            return ;
        }
        
        /**
         * 检查分页
         * start 作为初始偏移量
         * 
         * -- 解析出 pagesize
         * 1 如果设置了 limit, 从中解析出 offset|pagesize, offset 可能没有值
         * 2 如果未设置 limit 则检查 pagesize 或 num 选项, 得出 pagesize
         * 
         * -- 解析出 offset, 并得出最终 limit
         * 1 如果设置了 limit 且已包含offset, 则优先使用
         * 2 否则检查 page, 解析出 offset, 结合 pagesize 生成最终 limit
         * 3 否则使用已 解析的 start pagesize, 生成最终 limit
         */
        $start = isset($this->query['start']) && intval($this->query['start'][0])>0 ? intval($this->query['start'][0]) : 0;
        if (isset($this->query['limit'])) {     // 优先使用 limit
            if (is_numeric($this->query['limit'][0])) {
                $pagesize = $this->query['limit'][0];
            } else {
                if (!is_array($this->query['limit'][0])) $limitArr = explode(',', $this->query['limit'][0]);
                if (is_numeric($limitArr[0]) && is_numeric($limitArr[1])) {
                    $offset = $limitArr[0];
                    $pagesize = $limitArr[1];
                }
            }
        }
        if (!isset($pagesize) || ! $pagesize) {                  // limit 不存在则依次判断 pagesize, num
            if (isset($this->query['pagesize']) && intval($this->query['pagesize'][0])>0 ) {
                $pagesize   = intval($this->query['pagesize'][0]);
            } elseif (isset($this->query['num']) && intval($this->query['num'][0])>0 ) {
                $pagesize   = intval($this->query['num'][0]);
            }
        }
        
        if (isset($offset)) {
            $offset += $start;
            $limit      =  $offset . ',' . $pagesize;
        } elseif (isset($this->query['page'])) {
            $page       = intval($this->query['page'][0]) > 0 ? intval($this->query['page'][0]) : 1;
            // notice: $pagesize may be still null. 此时 pagesize 仍然可能为空，因为未设置或解析不正确
            $pagesize   = !empty($pagesize) ? $pagesize : 10;
            $offset     =  $start + $pagesize * ($page - 1);
            $limit      =  $offset . ',' . $pagesize;
        } elseif (isset($pagesize)) {
            $limit = $start . ',' . $pagesize;
        }
        if (isset($limit)) {
            $this->query->remove('limit')->set('limit', $limit);     // 强制重设
        }
    }
    /**
     * get select type query keywords, $xxx$ is must have item
     * 
     * @return string
     */
    protected function getSelectTemplate()
    {
        if (isset($this->query['count'])) {
            return '$select$ %fields% $from$ %table% %join% %where% %group% %having%';
        }
        return '$select$ %distinct% %fields% $from$ %table% %join% %where% %group% %having% %order% %limit%';
    }
    
    /**
     * check select 
     * @return boolean
     */
    protected function checkSelect()
    {
    	return true;
    }
    
    /**
     * preprocess insert
     */
    protected function preprocessInsert()
    {
        /**
         * 合并设置项 data 和 values, 并解析为 field,values
         * $this->query['data'] = array(array('id'=>3, 'name'=>'ccccc'), array('id'=>3, 'name'=>'ccccc'))
         */
        if ( isset($this->query['data']) ) {
            if (!isset($this->query['values'])) {
                $this->query->setRaw('values', $this->query['data']);
            } else {
                $this->query->setRaw('values', array_merge($this->query['data'], $this->query['values']));
            }
        }
        if ($this->query['values']) {
            $set = $this->_parseDataItemToExtractValues($this->query['values']);
            if (count($set) > 0) {
                
                if (isset($set['fields'])) {
                    $this->query->remove('fields')->set('fields', $set['fields']);
                }
                
                $this->query->remove('values')->set('values', $set['values']);
            }
        }
    }
    
    
    protected function getInsertTemplate()
    {
        return '$insert$ %table% %fields% %values%';
    }
    
    protected function checkInsert()
    {
    	if (!isset($this->query['table'])) {
    		throw new DbException('query error: must set insert table.');
    	}
    	
    	if (!isset($this->query['fields'])) {
    		//throw new DbException('query error: must set insert fields.');
    	}
    	
    	if (!isset($this->query['values'])) {
    		throw new DbException('query error: must set insert values.');
    	}
    }
    
    protected function preprocessUpdate()
    {
        /**
         * check where, not allowed null
         */
        if (!isset($this->query['where']) || empty($this->query['where'])) {
            $value = '1=0';
            $this->query->set('where', $value);
        }
    }
    
    protected function getUpdateTemplate()
    {
        return '$update$ %table% %data% %where%';
    }
    
    protected function checkUpdate()
    {
    	if (!isset($this->query['where'])) {
    		throw new DbException('query error: must set update where.');
    	}
    }
    
    protected function preprocessDelete()
    {
        /**
         * check where, not allowed null
         */
        if (!isset($this->query['where']) || empty($this->query['where'])) {
            $value = '1=0';
            $this->query->set('where', $value);
        }
    }
    
    protected function getDeleteTemplate()
    {
        return '$delete$ from %table% %where%';
    }
    
    protected function checkDelete()
    {
    	if (!isset($this->query['where'])) {
    		throw new DbException('query error: must set delete where.');
    	}
    }
    
    /*******************************************************************************************
     * protected methods: parse options item method
     *******************************************************************************************/
    /**
     * parse query item
     * @param  string $name
     * @param  mixed  $value
     * 
     * @return string
     */
    protected function parseQueryItem($name, $value)
    {
        if (!in_array($name, static::$allowedParseMethods)) {
            throw new DbException(sprintf('Error: Not allowed mothod on the query parse options: %s.', $name));
        }
        $method = 'parse' . ucfirst($name);
        
        return $this->$method($value);
    }
    
    /**
     * select
     * @param  mixed $data
     * @return string
     */
    protected function parseSelect()
    {
        return 'SELECT';
    }
    
    /**
     * distinct
     * @param  mixed  $data can be 'id,name'|['id','name']
     * @return string
     */
    protected function parseDistinct($data)
    {
        $set = $this->_parseDataItemToKeys($data, $this->query->hasAlias());
        return empty($set) ? '' : 'DISTINCT ' . implode(',', array_unique($set));
    }
    
    /**
     * fields
     * @param  mixed  $data can be 'id,name'|['id','name']
     * @return string
     */
    protected function parseFields($data)
    {
        $set = $this->_parseDataItemToKeys($data, $this->query->hasAlias());
        $str = empty($set) ? '' : implode(',', array_unique($set));
        if ($this->query->type() === 'insert' && !empty($str)) {
            $str = '(' . $str . ')';
        } elseif ($this->query->type() === 'select' && empty($str)) {
            if (isset($this->query['count'])) {
                $str = 'count(*)';
            } else {
                $str = '*';
            }
        }
        return $str;
    }
    
    /**
     * from
     * @param  mixed $data
     * @return string
     */
    protected function parseFrom()
    {
        return 'FROM';
    }
    
    /**
     * table
     * @param  mixed $data
     * @return string
     */
    protected function parseTable($data)
    {
        $set = $this->_parseDataToTable($data);

        return empty($set) ? '' : implode(',', array_unique($set));
    }
    
    /**
     * table, use parse to from|update|delete|other table name 
     * @param  array $data
     * @return array
     */
    protected function _parseDataToTable($data)
    {   
        
        $values = array();
        if (is_scalar($data)) {             // 1 scalar
            preg_match('/(.*)(\,|left\sjoin|inner\sjoin)+(.*)/i', $data, $matches);
            if ($matches) {
                $temp1 = $this->_parseDataToTable($matches[1]);
                $temp2 = $this->_parseDataToTable($matches[3]);
                $values[] = $temp1[0] . ' ' . $matches[2] . ' ' . $temp2[0];
            } else {
                $values[] = $this->formatTableName($data);
            }
        } elseif (is_array($data)) {        // 2 array
            foreach ($data as $key => $value) {
                
                if ((is_numeric($key) || empty($key)) && $value) {
                    $temp = $this->_parseDataToTable($value);
                    $values = array_merge($values, $temp);
                }
            }
        }
        
        return $values;
    }
    /**
     * join, join can be ['table', 'as', 'on'], ['tbl_x', 't2', 't1.id=t2.id']
     * @param  array $data
     * @return string
     */
    protected function parseJoin($data)
    {
        $values = array();
        if (!empty($this->query['join'])) { 
            foreach ($this->query['join'] as $k => $v) {
                if (is_array($v)) {
                    $table = $this->formatTableName($v[0]);
                    $as = $on = null;
                    if (isset($v[1])) {
                        if (!isset($v[2])) {
                            $on = $v[1];
                        } else {
                            $as = $v[1];
                            $on = $v[2];
                        }
                    }
                    if ($as) {
                        if (is_array($table)) {
                            foreach ($table as $k => $v) {
                                if (isset($as[$k])) $table[$k] .= ' as ' . $as[$k];
                            }
                        } else {
                            $table .= ' AS ' . $as;
                        }
                    }
                    if ($on) {
                        if (is_array($on)) {
                            $on = implode(' AND ', $on);
                        }
                        $table .= ' ON ' . $on;
                    }
                    $values[] = $table;
                } else {
                    $values[] = $this->formatTableName($v);
                }
            }
        }
        return $values ? 'LEFT JOIN ' . implode(' LEFT JOIN ', $values) : '';
    }
    /**
     * where
     * @access protected
     * @param  mixed $data
     * @return string
     */
    protected function parseWhere($data)
    {
        $result = '';
        $set = $this->_parseDataGroup($data, '_parseDataItemToWheres');
        $group = [];
        foreach ($set as $v) {
            if (!empty($v)) {
                $glue = 'AND';
                if (in_array(strtoupper($v[0]), ['AND', 'OR'])) {
                    $glue = strtoupper($v[0]);
                    array_shift($v);
                } elseif (in_array(strtoupper($v[count($v)-1]), ['AND', 'OR'])) {
                    $glue = strtoupper($v[count($v)-1]);
                    array_pop($v);
                } 
                
                $group[] = '(' . implode(' ' . $glue . ' ', $v) . ')';
            }
        }
        if (!empty($group))    {
            $result = 'WHERE ' . implode(' AND ', $group);
        }

        return $result;
    }
    
    private function _parseDataItemToWheres($data)
    {
        if (empty($data)) {
            return null;      // 为 null 不进行解析
        }
        
        $values     = array();
        if (is_scalar($data)) { // 直接值是标量
            $values[]   = $data;
        } elseif (is_array($data)) {
            foreach ($data as $key => $value){
                if (is_numeric($key) || empty($key)) {  // 如果是数字索引或索引为空的
                    if (is_array($value)) {             // 为数组，进行递归解析
                        foreach ($value as $svalue){
                            $temp = $this->_parseDataItemToWheres($svalue);
                            $values = array_merge($values, $temp);
                        }
                    } else {
                        $values[] = $value;
                    }
                } else {
                    $key    = trim($key);
                    if (strpos($key, '|')) {                            // 如果条件键包含|，支持 name|title|nickname 方式进行 OR 查询
                        $str   =  array();
                        foreach (explode('|', $key) as $m=>$k) {
                            $str[]   = $this->parseWhereItem($k, $value);
                        }
                        $values[] = implode(' OR ', $str);
                    } elseif (strpos($key, '&')) {                       // 如果条件键包含&，支持 name&title&nickname 方式进行AND 查询
                        $str   =  array();
                        foreach (explode('&', $key) as $m=>$k){
                            $str[]   = $this->parseWhereItem($k, $value);
                        }
                        $values[] = implode(' AND ', $str);
                    } else {
                        $values[] = $this->parseWhereItem($key, $value);
                    }
                }
            }
        }
        
        return $values;
    }
    
    /**
     * where item
     * @param  string $key
     * @param  mixed  $val
     * @throws \Wslim\Db\Exception
     * @return string
     */
    protected function parseWhereItem($key, $val)
    {
        $hasAlias = $this->query->hasAlias();
        
        $key = $this->formatKey($key, $hasAlias);
        $whereStr = '';
        
        if(is_array($val)) {                                    // 1 值是数组形式 'name'=>[ ]
            if (isset($val[1]) && is_string($val[0])) {                  // 1.1 值是数组，且第一个元素为字串，则解析为操作符 'name'=>['operator', 'name']
                $exp	=	strtolower($val[0]);
                if (preg_match('/^(eq|neq|gt|egt|lt|elt|<>|>|<|>=|<=|=)$/', $exp)) {          // 比较运算 'name'=>['>=', 'value']
                    $_val = $this->formatValue($val[1]);
                    if (is_null($_val)) {
                        $whereStr .= $key . (($exp === '=' || $exp === 'eq') ? ' IS NULL' : ' IS NOT NULL');
                    } else {
                        $whereStr .= $key . ' '. static::$operators[$exp] . ' ' . $_val;
                    }
                } elseif (preg_match('/^(not\s*like|like)$/', $exp)){               // 模糊查找 'name'=>['like', 'value'], value 支持空格分隔的OR查询
                    
                    if (is_string($val[1])) {
                        $val[1] = preg_split('/\s+/', $val[1]);
                    }
                    $likeLogic  =   isset($val[2]) ? strtoupper($val[2]) : 'OR';
                    if(!in_array($likeLogic, array('AND','OR','XOR'))){
                        $likeLogic = 'OR';
                    }
                    $like       =   array();
                    foreach ($val[1] as $item){
                        if (!$item) continue;
                        $like[] = $key . ' ' . static::$operators[$exp] . ' ' . $this->formatValue('%' . $item . '%');
                    }
                    if ($like) {
                        $whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
                    }
                } elseif ('bind' == $exp ){                                         // 使用绑定     'name' => ['bind', 'value']
                    $whereStr .= $key . ' = :' . $val[1];
                } elseif ('exp' == $exp ){                                          // 使用表达式 'name' => ['exp', 'value']
                    $whereStr .= $key . ' ' . $val[1];
                } elseif (preg_match('/^(notin|not in|in)$/', $exp)){               // IN 运算    'name' => ['in', 'value']
                    if (isset($val[2]) && 'exp' == $val[2]) {                       // IN 运算1: 'name' => ['in', 'value', 'exp']
                    	$whereStr .= $key.' '. static::$operators[$exp].' '.$val[1];
                    } elseif (is_string($val[1]) && strpos($val[1], 'select')) {    // IN 运算2: 'name' => ['in', 'select....'] 子查询
                        if (strpos($val[1], '(') === false) {
                            $val[1] = '(' . $val[1] . ')';
                        }
                        $whereStr .= $key . ' ' . static::$operators[$exp] . $val[1];
                    } else {                                                        // IN 运算2: 'name' => ['in', 'value']
                        if (is_string($val[1])) {
                            $val[1] =  explode(',', $val[1]);
                        }
                        $zone      =   implode(',', $this->formatValue($val[1]));
                        $whereStr .= $key . ' ' . static::$operators[$exp] . ' (' . $zone.')';
                    }
                } elseif (preg_match('/^(notbetween|not between|between)$/', $exp)){ // BETWEEN运算 'name'=>['between', 'value1,value2']
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $whereStr .=  $key.' '. static::$operators[$exp].' '.$this->formatValue($data[0]).' AND '.$this->formatValue($data[1]);
                }else{
                    throw new DbException(sprintf('database query parse error, not allowed expression : \'%s\' .', $val[0]));
                }
            } else {                                        // 1.2 值是数组，且第一个元素不是字串 'name' => [['like', 'aaa], ['=', 'bbb'], 'and']
                $count = count($val);
                $rule  = isset($val[$count-1]) ? (is_array($val[$count-1]) ? strtoupper($val[$count-1][0]) : strtoupper($val[$count-1]) ) : '' ;
                if($rule && in_array($rule, array('AND','OR','XOR'))) {
                    $count  = $count -1;
                }else{
                    $rule   = 'AND';
                }
                for($i=0; $i<$count; $i++) {
                    $whereStr .= $this->parseWhereItem($key, $val[$i]).' '.$rule.' ';
                }
                $whereStr = '(' . substr($whereStr,0,-4) . ')';
            }
        } else {                                         // 2 值是字串，['name'=>'xxxx'] 进行 Like 匹配或 =,或进行参数绑定
            $likeFields   =   array();   //$this->config['db_like_fields'];  // 限定可进行like的字段，暂时不取
            if($likeFields && preg_match('/^('.$likeFields.')$/i',$key)) {        // 键为已配置的可查询字段组
                $whereStr   = $key.' LIKE '.$this->formatValue('%'.$val.'%');
            }else {
                $_val = $this->formatValue($val);
                if (is_null($_val)) {
                    $whereStr .= $key . ' IS NULL';
                } else {
                    $whereStr   = $key . ' = ' . $_val;
                }
            }
        }
        
        return $whereStr;
    }
    
    /**
     * GROUP
     * @param  mixed  $values 'id,name' | array('id','name')
     * @return string
     */
    protected function parseGroup($data)
    {
        $set = $this->_parseDataItemToKeys($data);
        return empty($set) ? '' : 'GROUP BY ' . implode(',', array_unique($set));
    }
    
    /**
     * HAVING
     * @param  mixed $data 'id,name' | array('id','name')
     * @return string
     */
    protected function parseHaving($data)
    {
        $set = $this->_parseDataItemToValues($data);
        return empty($set) ? '' : 'HAVING ' . implode(' AND ', $set);
    }
    
    /**
     * order
     * @param  mixed $data
     * @return string
     */
    protected function parseOrder($data)
    {
        $set = $this->_parseDataItemToKeys($data);
        return empty($set) ? '' : 'ORDER BY ' . implode(',', array_unique($set));
    }
    
    /**
     * limit 
     * @param  mixed $data
     * @return string
     */
    protected function parseLimit($data)
    {
        $set = $this->_parseDataItemToValues($data); //var_dump($set[0]);
        return (empty($set) || empty($set[0]))? '' : 'LIMIT ' . trim(implode(',', $set), '\'');
    }
    
    /**
     * lock
     * @access protected
     * @return string
     */
    protected function parseLock($lock=false) {
        return '';
    }
    
    protected function parseComment($distinct)
    {
        return '';
    }
    
    protected function parseForce($distinct)
    {
        return '';
    }
    
    /**
     * insert
     * @param mixed $data
     * @return string
     */
    protected function parseInsert()
    {
        return 'INSERT INTO';
    }
    
    /**
     * insert values
     * @param  mixed $data
     * @return string
     */
    protected function parseValues($data)
    {
    	if (isset($data[0])) {
    		$set = $this->_parseDataItemToValues($data);
    	} else {
    		$set = $this->parse($data);
    	}
        
        foreach ($set as $v) {
            $group[] = '(' . implode(',', $v) . ')';
        }
        return empty($set) ? '' : 'VALUES '. implode(',', $group);
    }
    
    /**
     * update
     * @param mixed $data
     * @return string
     */
    protected function parseUpdate()
    {
        return 'UPDATE';
    }
    /**
     * update set, because query has set(), use data name
     * @access protected
     * @param  array  $data
     * @return string
     */
    protected function parseData($data)
    {
        $set = $this->_parseDataItemToKeyValues($data);
        foreach ($set as $v) {
            $group[] = is_array($v) ? implode(',', $v) : $v;
        }
        return empty($set) ? '' : 'SET '. implode(',', $group);
    }
    
    /**
     * delete
     * @param mixed $data
     * @return string
     */
    protected function parseDelete()
    {
        return 'DELETE';
    }
    
    /**
     * bind
     * @param  string $sql
     * @return string
     */
    protected function parseBind($sql)
    {
        //$sql = str_replace('%'.$key.'%', $this->parseOptionsItem($key, $this->query[$key]), $sql);
        if (!empty($this->query['bind'])) {
            $count = 1;
            foreach ($this->query['bind'] as $v) {
                if (is_array($v)) {
                    $sql = str_replace($v[0], $this->formatValue($v[1]), $sql, $count);
                } else {
                    $sql = str_replace('?', $this->formatValue($v), $sql, $count);
                }
            }
        }
        return $sql;
    }
    
    /*******************************************************************************************
     * private methods: support methods
     *******************************************************************************************/
    /**
     * data group parse, array( array('id' => 4, 'name' = 'a'), array('id' => 4, 'name' = 'a'))
     * notice: 使用这个性能有影响，但可获得嵌套数组的设置方式支持，需要再优化下。
     * @param  array $data
     * @return array
     */
    private function _parseDataGroup($data, $item_callback)
    {   
        // 不要定义为 static, 否则最后多次调用会重复值
        $groups = array();
        if (is_array($data)) {              // 数组形式
            foreach ($data as $key => $value) {
                // 进行元素分析，元素的键为数字而值为数组，且其值的元素键为数字值为数组的进行递归
                if (is_numeric($key) && is_array($value)) {
                    // 方式一：分解数组为子项
                    //$temp = $this->_parseDataGroup($value, $item_callback); // 先临时存之
                    //$groups = array_merge($groups, $temp);
                    
                    // 方式二：不分解，在解析子项时处理数组格式
                    $groups[]   = call_user_func(array($this, $item_callback), $value);
                } elseif (!is_numeric($key) || !empty($value)) {    // 值不为空或键不是数字
                    $groups[]   = call_user_func(array($this, $item_callback), array($key => $value));
                }
            }
        } else {                            // 标量类型/null/其他，直接作为一个解析组
            $groups[]   = call_user_func(array($this, $item_callback), $data);
        }
        
        return $groups;
    }
    
    /**
     * data item, item can be 'id=4' or ['id' => 4, 'name' = 'a'] or ['name'=>['=', 'aaaa']]
     * @param  mixed  $data
     * @param  string $itemType: keys|values|keyvalues
     * @param  bool   $extractValues: if extract values[], use to from data['key'=>'value'] extract values[]
     * @return array
     */
    private function _parseDataItem($data, $itemType, $extractValues=false)
    {   
        
        if (empty($data)) return '';
        $fields     = array();
        $values     = array();
        $formatValue = $formatExpression = 'formatValue';
        
        if ($itemType == 'keys' || $itemType == 'aliaskeys') {
            $formatValue = $formatExpression = 'formatKey';
        }
        if ($itemType == 'keyvalues') {
            $formatExpression = 'formatExpression';
        }
        $hasAlias = false;
        if ($itemType == 'aliaskeys') {
            $hasAlias = true;
        }
        
        if (is_scalar($data)) {                       // 1 值是标量或 null
            $values[]   = $this->$formatExpression($data, $hasAlias);
        } elseif (is_array($data)) {                                    // 2 值是数组
            foreach ($data as $key => $value) {
                if (is_numeric($key) && is_scalar($value)) {                // 2.1 键是数字，值是标量
                    $values[]   = $this->$formatExpression($value, $hasAlias);
                } elseif (is_numeric($key) && is_array($value)) {           // 2.2 键是数字，值是数组，需要递归
                    // 递归调用时，对于 keyvalue $extractValues=true 时，只合并 values部分
                    $temp = $this->_parseDataItem($value, $itemType, $extractValues);
                    if ($itemType == 'keyvalues' && $extractValues) {
                        $fields = $temp['fields'];
                        $values[] = $temp['values'];
                        //$values = array_merge($values, $temp['values']);
                    } else {
                        //$values = array_merge($values, $temp);
                        $values[] = $temp;
                    }
                    unset($temp);
                } else {                                                // 2.3 键不是数字，即是 key-value 式
                    if ( is_scalar($value) ) {          // 2.3.1 元素值为标量
                        $val        = $this->$formatValue($value, $hasAlias);
                    } elseif (is_array($value) ) {      // 2.3.2 元素值为数组
                        if (isset($value[0]) && 'exp' == $value[0]) {       // 2.3.2.1 符合  ['id' => ['exp', 'id2']] 格式
                            $val        = $value[1];
                        } else {                        // 2.3.2.2 其它情况，需要继续递归
                            /*
                            // 递归调用时，对于 keyvalue $extractValues=true 时，只合并 values部分
                            $temp = $this->_parseDataItem($value, $itemType, $extractValues);
                            if ($itemType == 'keyvalues' && $extractValues) {
                                $fields = $temp['fields'];
                                $values = array_merge($values, $temp['values']);
                            } else {
                                $values = array_merge($values, $temp);
                            }
                            unset($temp);
                            */
                            $val = $this->$formatValue(implode(',', $value));
                        }
                    }
                    if ($itemType == 'keyvalues' && $extractValues) {
                        $fields      = array_merge($fields, array($this->formatKey($key, $hasAlias)) );
                        $values[]   = $val;
                    } elseif ($itemType == 'keyvalues') {
                        $values[]   = $this->formatKey($key, $hasAlias) . '=' . $val;
                    } else {
                        $values[]   = $val;
                    }
                }
            }
        }
        if ($itemType == 'keyvalues' && $extractValues) {
            $set = array('fields' => $fields, 'values' => $values);
            //unset($fields, $values);
        } else {
            $set = & $values;
        }
        
        return $set;
    }
    
    private function _parseDataItemToKeys($data, $hasAlias=false)
    {
        if ($hasAlias) {
            return $this->_parseDataItem($data, 'aliaskeys', false);
        }
        return $this->_parseDataItem($data, 'keys', false);
    }
    private function _parseDataItemToValues($data)
    {
        return $this->_parseDataItem($data, 'values', false);
    }
    private function _parseDataItemToKeyValues($data)
    {
        return $this->_parseDataItem($data, 'keyvalues', false);
    }
    private function _parseDataItemToExtractValues($data)
    {
        return $this->_parseDataItem($data, 'keyvalues', true);
    }
    
}