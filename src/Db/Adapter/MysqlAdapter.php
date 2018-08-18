<?php
namespace Wslim\Db\Adapter;

use Wslim\Db\Exception;

class MysqlAdapter extends AbstractAdapter
{
    // mysql trait
	use \Wslim\Db\Traits\MysqlTrait;
    
	/**
	 * @var \mysqli
	 */
	protected $connection;
	
	protected $stmt_results;
	
	protected $stmt_cols;
	
    /**
     * Constructor
     *
     * Instantiate the Mysql database connection object using mysqli
     *
     * @param  array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {    
        $options['database'] = isset($options['database']) ? $options['database'] : $options['dbname'];
        if (!isset($options['database']) || !isset($options['host']) || !isset($options['username']) || !isset($options['password'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
        }
        if (!isset($options['port'])) $options['port'] = '3306';
        
        $this->options = $options;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::connect()
     */
    public function connect()
    {
        $options = & $this->options;
        
        if (!$this->connection && !@$this->connection->sqlstate) {
            $this->connection = @ new \mysqli($options['host'], $options['username'], $options['password'], $options['database'], $options['port']);
            // error
            if (mysqli_connect_errno()) {
                $msg = mysqli_connect_error();  // 这是中文编码？
                throw new Exception('Db connect error.');
            }
            
            if ($this->connection->connect_error != '') {
                $msg = 'Db Error: Could not connect to database. Connection Error #' . $this->connection->connect_errno . ': ' . $this->connection->connect_error;
                throw new Exception('Db connect error.');
            }
            
            //set charset
            $options['charset'] = isset($options['charset']) ? trim($options['charset']) : 'utf8';
            if ($this->getCharset() != $options['charset']) {
                $this->setCharset($options['charset']);
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::showError()
     */
    public function showError($msg=null)
    {
        if (is_null($msg)) $msg = $this->getErrorMessage();
        throw new Exception($msg);
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::getErrorMessage()
     */
    public function getErrorMessage()
    {
        return 'Mysql Error: ' . $this->connection->errno . ' => ' . $this->connection->error;
    }
    
    /**
     * Execute the SQL query and create a result resource, or display the SQL error.
     *
     * @param  string $sql
     * @return mixed  \mysqli_result|boolean 
     */
    public function query($sql)
    {
        // auto reconnect
        $this->connect();
        
        //echo $sql;
        $this->statement = null; //reset 
        //echo 'adapter:'; var_dump($sql);exit;
        if (!($this->result = $this->connection->query($sql))) {
            $this->showError();
        }
        return $this->result;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::prepare()
     */
    public function prepare($sql, $attributes = null)
    {   
        //echo 'adapter:'; var_dump($sql);exit;
        $this->result = null;   //reset result
        
        // auto reconnect
        $this->connect();
        
        $this->statement = $this->connection->stmt_init();
        $result = @ $this->statement->prepare($sql);  // 语句无误返 true, 否则为 false
        if (!$result) {
            $this->showError();
            return false;
        }
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::bindParams()
     */
    public function bindParams($params)
    {
        $bindParams = [''];
        
        foreach ($params as $dbColumnName => $dbColumnValue) {
            $dbColumnValueAry = (!is_array($dbColumnValue)) ? [$dbColumnValue] : $dbColumnValue;
            
            $i = 1;
            foreach ($dbColumnValueAry as $dbColumnValueAryValue) {
                ${$dbColumnName . $i} = $dbColumnValueAryValue;
                
                if (is_numeric($dbColumnValueAryValue)) {
                    $bindParams[0] .= 'i';
                } else if (is_double($dbColumnValueAryValue)) {
                    $bindParams[0] .= 'd';
                } else if (is_string($dbColumnValueAryValue)) {
                    $bindParams[0] .= 's';
                } else if (is_null($dbColumnValueAryValue)) {
                    $bindParams[0] .= 's';
                } else {
                    $bindParams[0] .= 'b';
                }
                
                $bindParams[] = &${$dbColumnName . $i};
                $i++;
            }
        }
        
        call_user_func_array([$this->statement, 'bind_param'], $bindParams);
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::execute()
     */
    public function execute()
    {
        
        if (null === $this->statement) {
            throw new Exception('The database statement resource is not currently set.');
        }
        if ($this->statement->execute()) {
            
            if (($metaData = $this->statement->result_metadata()) !== false) {
                foreach ($metaData->fetch_fields() as $col) {
                    ${$col->name}   = null;
                    $results[]      = &${$col->name};
                    $cols[]         = $col->name;
                }
                
                call_user_func_array([$this->statement, 'bind_result'], $results);
                
                $this->stmt_results = $results;
                $this->stmt_cols = $cols;
            }
            return true;
        }
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::hasResult()
     */
    public function hasResult()
    {
        return isset($this->statement) || isset($this->result);
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::fetch()
     */
    public function fetch()
    {
        
        if ((null !== $this->statement) && $this->statement->fetch()) {
            $row = [];
            foreach ($this->stmt_results as $dbColumnName => $dbColumnValue) {
                $row[$this->stmt_cols[$dbColumnName]] = $dbColumnValue;
            }
            return $row;
        } elseif ($this->result instanceof \mysqli_result) {
            return mysqli_fetch_array($this->result, MYSQLI_ASSOC);
        } else {
            return null;
            
            if (!isset($this->result)) {
                throw new Exception('The database result resource is not currently set.');
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::fetchAll()
     */
    public function fetchAll()
    {
        $rows       = [];
        while ($row = $this->fetch()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::escape()
     */
    public function escape($value)
    {
        // auto reconnect
        $this->connect();
        
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::lastId()
     */
    public function lastId()
    {
        // auto reconnect
        $this->connect();
        
        return $this->connection->insert_id;
    }
    
    /**
     * mysqli execute() for update affected_rows return 0
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::numberOfRows()
     */
    public function numberOfRows()
    {
        if (isset($this->statement)) {
            $this->statement->store_result();
            return $this->statement->num_rows ?: $this->statement->affected_rows;
        } else if (isset($this->result)) {
            return $this->result->num_rows;
        } else {
            throw new Exception('Error: The database result resource is not currently set.');
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::numberOfFields()
     */
    public function numberOfFields()
    {
        // auto reconnect
        $this->connect();
        
        if (isset($this->statement)) {
            $this->statement->store_result();
            return $this->statement->field_count;
        } else if (isset($this->result)) {
            return $this->connection->field_count;
        } else {
            throw new Exception('Error: The database result resource is not currently set.');
        }
    }

    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::isConnected()
     */
    public function isConnected()
    {
        if ($this->connection && @$this->connection->sqlstate) {
            return true;
        }
        
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::disconnect()
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->connection->close();
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::version()
     */
    public function version()
    {
        // auto reconnect
        $this->connect();
        
        return 'MySQL ' . $this->connection->server_info;
    }

    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::getTables()
     */
    public function getTables($database=null)
    {
        if (is_null($this->tables)) {
            $sql    = !empty($database) ? 'SHOW TABLES FROM '.$database : 'SHOW TABLES';
            $this->query($sql);
            $result = $this->fetchAll();
            foreach ($result as $v) {
                $this->tables[] = array_values($v)[0];
            }
        }
        return $this->tables;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::getColumns()
     */
    public function getColumns($table)
    {
        $sql = 'SHOW COLUMNS FROM '. $this->formatKey($table);
        $result = $this->query($sql);
        
        if ($result === false) {
            return array();
        }
        
        $array = array();
    
        while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            // $tmp['Type'] 格式为 varchar(30), 可解析出 data_type, data_length
            preg_match('/([^\(]+)\(?(\d*)\)?/i', $tmp['Type'], $matches);
            $data_type   = $matches[1];
            $data_length = $matches[2]; 
            $array[$tmp['Field']] = array(
                'name'      => $tmp['Field'],
                'type'      => $data_type, 
                'length'    => $data_length,
                'null'      => $tmp['Null'],
                'primary'   => (strtolower($tmp['Key']) == 'pri'),
                'default'   => $tmp['Default'],
                'auto_increment' => (strtolower($tmp['Extra']) == 'auto_increment'),
            );
        }
        
        return $this->formatColumns($array);
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\AdapterInterface::getPrimaryKeys()
     */
    public function getPrimaryKeys($table)
    {
        $pk = [];
        $cols = $this->getColumns($table);
        if ($cols) {
            foreach ($cols as $key => $val){
                if(isset($val['is_primary']) && $val['is_primary']) {
                    $pk[] = $key;
                }
            }
        }
        
        return $pk;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::beginTransaction()
     */
    public function beginTransaction()
    {
        // auto reconnect
        $this->connect();
        
        $this->isTransaction = true;
        $this->connection->autocommit(false);//关闭自动提交
        return ;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::commit()
     */
    public function commit()
    {
        if ($this->isTransaction){
            $result = $this->connection->commit();
            $this->connection->autocommit(true);//开启自动提交
            if (!$result) {
                $this->showError();
                return false;
            }
            $this->isTransaction = false;
        }
        return true;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::rollback()
     */
    public function rollback()
    {
        if ($this->isTransaction){
            $result = $this->connection->rollback();
            $this->connection->autocommit(true);
            if (!$result) {
                $this->showError();
                return false;
            }
            $this->isTransaction = false;
        }
        return true;
    }
    
    /*****************************************************************************************
     * extend methods
     *****************************************************************************************/
    /**
     * @return string $charset: utf8
     */
    public function getCharset()
    {
        $this->connect();
        
        return $this->connection->character_set_name();
    }
    /**
     * set charset: utf8|gbk
     * @param string $charset
     */
    public function setCharset($charset)
    {   
        $this->connect();
        
        $this->connection->set_charset($charset);
    }
    
}
