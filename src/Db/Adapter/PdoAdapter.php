<?php
namespace Wslim\Db\Adapter;

use Wslim\Db\Exception;

class PdoAdapter extends AbstractAdapter
{
	/**
	 * @var \PDO
	 */
	protected $connection;
	
    protected $dbtype   = null;
    
    protected $dsn;
    
    protected $placeholder = '?';
    
    /**
     * Constructor
     *
     * Instantiate the PDO database connection object.
     *
     * @param  array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        // Default to localhost
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }
        if (!isset($options['dbtype'])) {
            $options['dbtype'] = 'mysql';
        }
        $this->dbtype = $options['dbtype'];
        
        $this->options = $options;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::connect()
     */
    public function connect()
    {
        $options = & $this->options;
        
        try {
            if (!$this->connection && !@$this->connection->sqlstate) {
                if (isset($options['dsn'])) {
                    $this->connection = new \PDO($options['dsn']);
                } else {
                    if ($options['dbtype'] === 'sqlite') {
                        $this->dsn = $options['dsn'] = $options['dbtype'] . ':' . $options['database'];
                        $this->connection = new \PDO($options['dsn']);
                    } else {
                        if (!isset($options['host']) || !isset($options['username']) || !isset($options['password'])) {
                            throw new Exception('Error: The proper database credentials were not passed.');
                        }
                        if ($options['dbtype'] === 'sqlsrv') {
                            $options['dsn'] = $options['dbtype'] . ':Server=' . $options['host'] . ';Database=' . $options['database'];
                        } else {
                            $options['dsn'] = $options['dbtype'] . ':host=' . $options['host'] . ';dbname=' . $options['database'];
                        }
                        $this->dsn = & $options['dsn'];
                        $this->connection = new \PDO($this->dsn, $options['username'], $options['password']);
                    }
                }
            }
        } catch (\PDOException $e) {
            throw new Exception('Error: Could not connect to database. ' . $e->getMessage());
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\Adapter\AbstractAdapter::showError()
     */
    public function showError($msg = null)
    {
        $errorMessage = null;
        $code = null;
        if ((null === $code) && (null === $msg)) {
            $errorCode = $this->connection->errorCode();
            $errorInfo = $this->connection->errorInfo();
        } else {
            $errorCode = $code;
            $errorInfo = $msg;
        }
    
        if (is_array($errorInfo)) {
            $errorMessage = null;
            if (isset($errorInfo[1])) {
                $errorMessage .= $errorInfo[1];
            }
            if (isset($errorInfo[2])) {
                $errorMessage .= ' : ' . $errorInfo[2];
            }
        } else {
            $errorMessage = $errorInfo;
        }
        throw new Exception('Error: ' . $errorCode . ' => ' . $errorMessage  . '.');
    }
    
    public function getErrorMessage()
    {
        return 'Db Error: ' . $this->connection->errorCode() . ' => ' . $this->connection->errorInfo();
    }
    
    public function prepare($sql, $attributes = null)
    {
        // 自动重连
        $this->connect();
        
        if (strpos($sql, '?') !== false) {
            $this->placeholder = '?';
        } else if (strpos($sql, ':') !== false) {
            $this->placeholder = ':';
        }
    
        if ((null !== $attributes) && is_array($attributes)) {
            $this->statement = $this->connection->prepare($sql, $attributes);
        } else {
            $this->statement = $this->connection->prepare($sql);
        }
        return $this;
    }
    
    public function bindParams($params)
    {
        if ($this->placeholder == '?') {
            $i = 1;
            foreach ($params as $dbColumnName => $dbColumnValue) {
                if (is_array($dbColumnValue)) {
                    foreach ($dbColumnValue as $dbColumnVal) {
                        ${$dbColumnName} = $dbColumnVal;
                        $this->statement->bindParam($i, ${$dbColumnName});
                        $i++;
                    }
                } else {
                    ${$dbColumnName} = $dbColumnValue;
                    $this->statement->bindParam($i, ${$dbColumnName});
                    $i++;
                }
            }
        } else if ($this->placeholder == ':') {
            foreach ($params as $dbColumnName => $dbColumnValue) {
                if (is_array($dbColumnValue)) {
                    $i = 1;
                    foreach ($dbColumnValue as $dbColumnVal) {
                        ${$dbColumnName . $i} = $dbColumnVal;
                        $this->statement->bindParam(':' . $dbColumnName . $i, ${$dbColumnName . $i});
                        $i++;
                    }
                } else {
                    ${$dbColumnName} = $dbColumnValue;
                    $this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName});
                }
            }
        }
    
        return $this;
    }
    
    public function query($sql)
    {
        // 自动重连
        $this->connect();
        
        if (!($this->statement = $this->connection->query($sql))) {
            $this->showError($sql);
        }
        return $this->statement;
    }
    
    public function execute()
    {
        if (null === $this->statement) {
            throw new Exception('Error: The database statement resource is not currently set.');
        }
        $bool = $this->statement->execute();
        return $bool;
    }
    
    public function fetch()
    {
        return $this->statement->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function fetchAll()
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function escape($value)
    {
        // 自动重连
        $this->connect();
        
        return substr($this->connection->quote($value), 1, -1);
    }
    
    public function lastId()
    {
        // 自动重连
        $this->connect();
        
        $id = null;
    
        // If the DB type is PostgreSQL
        if ($this->dbtype == 'pgsql') {
            $this->query("SELECT lastval();");
            if (isset($this->statement)) {
                $insert_row = $this->statement->fetch();
                $id = $insert_row[0];
            }
            // Else, if the Db type is SQLSrv
        } else if ($this->dbtype == 'sqlsrv') {
            $this->query('SELECT SCOPE_IDENTITY() as Current_Identity');
            $row = $this->fetch();
            $id = (isset($row['Current_Identity'])) ? $row['Current_Identity'] : null;
            // Else, just
        } else {
            $id = $this->connection->lastInsertId();
        }
    
        return $id;
    }
    
    
    public function numberOfRows()
    {
        if (!isset($this->statement)) {
            throw new Exception('Error: The database statement is not currently set.');
        }
        return $this->statement->rowCount();
    }
    
    public function numberOfFields()
    {
        // 自动重连
        $this->connect();
        
        if (!isset($this->statement)) {
            throw new Exception('Error: The database statement resource is not currently set.');
        }
        return $this->statement->columnCount();
    }
    
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->connection = null;
        }
    }
    
    public function version()
    {
        // 自动重连
        $this->connect();
        
        return 'PDO ' . substr($this->dbtype, 0, strpos($this->dbtype, ':')) . ' ' . $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
    
    
    public function getTables()
    {
        $tables = [];
        if (stripos($this->dbtype, 'sqlite') !== false) {
            $sql = "SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%' UNION ALL SELECT name FROM sqlite_temp_master WHERE type IN ('table', 'view') ORDER BY 1";
            $this->query($sql);
            while (($row = $this->fetchAll()) != false) {
                $tables[] = $row['name'];
            }
        } else {
            if (stripos($this->dbtype, 'pgsql') !== false) {
                $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
            } else if (stripos($this->dbtype, 'sqlsrv') !== false) {
                $sql = "SELECT name FROM " . $this->database . ".sysobjects WHERE xtype = 'U'";
            } else {
                $sql = 'SHOW TABLES';
            }
            $this->query($sql);
            while (($row = $this->fetchAll()) != false) {
                foreach($row as $value) {
                    $tables[] = $value;
                }
            }
        }
    
        return $tables;
    }
    
    /**
     * 获取表字段信息
     *
     * @param string $table
     * @return array
     */
    public function getColumns($tableName){
        $sql = 'SHOW COLUMNS FROM '. $this->formatKey($tableName);
        $result = $this->query($sql);
        if ($result === false) return array();
        $array = array();
        
        while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            // $tmp['Type'] 格式为 varchar(30), 可解析出 data_type, data_length
            preg_match('/([^\(]+)\(?(\d*)\)?/i', $tmp['Type'], $matches);
            $data_type = $matches[1];
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
        return $array;
    }
    
    public function getPrimaryKeys($table)
    {
        $pk = [];
        $cols = $this->getColumns($table);
        if ($cols) {
            foreach ($cols as $key => $val){
                if($val['is_primary']) {// 以数组形式，支持复合主键情况
                    $pk[] = $key;
                }
            }
        }
        return $pk;
    }
    
    public function beginTransaction() 
    {
        // 自动重连
        $this->connect();
        
        $this->isTransaction = true;
        $this->connection->beginTransaction();
        return ;
    }
    
    public function commit()
    {
        if ($this->isTransaction){
            $result = $this->connection->commit();
            if (!$result) {
                $this->showError();
                return false;
            }
            $this->isTransaction = false;
        }
        return true;
    }
    
    public function rollback()
    {
        if ($this->isTransaction){
            $result = $this->connection->rollBack();
            if (!$result) {
                $this->showError();
                return false;
            }
            $this->isTransaction = false;
        }
        return true;
    }
    

}