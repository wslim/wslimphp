<?php
namespace Wslim\Db\Adapter;

use Wslim\Db\AdapterInterface;
use Wslim\Db\Exception;

abstract class AbstractAdapter implements AdapterInterface
{
    
    /**
     * options
     * @var array
     */
    protected $options;
    
    /**
     * Database results
     * @var resource
     */
    protected $result;

    /**
     * Default database connection
     * @var resource
     */
    protected $connection;

    /**
     * Prepared statement
     * @var mixed
     */
    protected $statement = null;
    
    /**
     * if transaction
     * @var boolean
     */
    protected $isTransaction = false;
    
    /**
     * database tables
     * @var array
     */
    protected $tables = null;

    /**
     * Get the available database adapters
     *
     * @return array
     */
    static public function getAvailableAdapters()
    {
        $pdoAdapters = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : array();

        return array(
            'mysql'  => (class_exists('mysqli', false)),
            'oracle' => (function_exists('oci_connect')),
            'pdo'    => array(
                'mysql'  => (in_array('mysql', $pdoAdapters)),
                'pgsql'  => (in_array('pgsql', $pdoAdapters)),
                'sqlite' => (in_array('sqlite', $pdoAdapters)),
                'sqlsrv' => (in_array('sqlsrv', $pdoAdapters))
            ),
            'pgsql'  => (function_exists('pg_connect')),
            'sqlite' => (class_exists('Sqlite3', false)),
            'sqlsrv' => (function_exists('sqlsrv_connect'))
        );
    }

    /**
     * Get the available image library adapters
     *
     * @param  string $adapter, adapter type: mysql|oracle|pdo|...
     * @return boolean
     */
    static public function isAvailableAdapter($adapter)
    {
        $adapter = strtolower($adapter);
        $result  = false;
        $type    = null;

        $pdoAdapters = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : array();
        if (strpos($adapter, 'pdo_') !== false) {
            $type    = substr($adapter, 4);
            $adapter = 'pdo';
        }

        switch ($adapter) {
            case 'mysql':
            case 'mysqli':
                $result = (class_exists('mysqli', false));
                break;
            case 'oci':
            case 'oracle':
                $result = (function_exists('oci_connect'));
                break;
            case 'pdo':
                $result = (in_array($type, $pdoAdapters));
                break;
            case 'pgsql':
                $result = (function_exists('pg_connect'));
                break;
            case 'sqlite':
                $result = (class_exists('Sqlite3', false));
                break;
            case 'sqlsrv':
                $result = (function_exists('sqlsrv_connect'));
                break;
        }

        return $result;
    }

    /**
     * Constructor
     *
     * Instantiate the database adapter object.
     *
     * @param  array $options
     * @return static
     */
    abstract public function __construct(array $options);
    
    /**
     * connect or reconnect
     * @return void
     */
    abstract public function connect();
    
    /**
     * Get the connection resource
     *
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * {@inheritDoc}
     */
    abstract public function showError($msg=null);

    /**
     * {@inheritDoc}
     * @see \Wslim\Db\AdapterInterface::getErrorMessage()
     */
    abstract public function getErrorMessage();
    
    /**
     * Prepare a SQL query.
     *
     * @param  string $sql
     * @return static
     */
    abstract public function prepare($sql, $attributes = null);

    /**
     * Bind parameters to a prepared SQL query.
     * 
     * $sql = "insert into `vol_msg`(mid,content) values(?,?)"
     * $db->prepare((string)$sql)
     *      ->bindParams(['mid' => 1000, 'content'=>'xxxxx'])
     *      ->execute();
     *      
     * @param  array $params
     * @return static
     */
    abstract public function bindParams($params);

    /**
     * Execute the SQL query and create a result resource, or display the SQL error.
     *
     * @param  string $sql
     * @return void
     */
    abstract public function query($sql);
    
    /**
     * Execute the prepared SQL query.
     *
     * @throws Exception
     * @return boolean
     */
    abstract public function execute();

    /**
     * {@inheritDoc}
     */
    abstract public function fetch();
    
    /**
     * Return the results array from the results resource.
     *
     * @throws Exception
     * @return array
     */
    abstract public function fetchAll();

    /**
     * Return the escaped string value.
     *
     * @param  string $value
     * @return string
     */
    abstract public function escape($value);

    /**
     * Return the auto-increment ID of the last query.
     *
     * @return int
     */
    abstract public function lastId();

    /**
     * Return the number of rows in the result.
     *
     * @throws Exception
     * @return int
     */
    abstract public function numberOfRows();

    /**
     * Return the number of fields in the result.
     *
     * @throws Exception
     * @return int
     */
    abstract public function numberOfFields();

    /**
     * Determine whether or not an result resource exists
     *
     * @return boolean
     */
    public function hasResult()
    {
        return $this->result !== null;
    }

    /**
     * Get the result resource
     *
     * @return resource
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return is_resource($this->connection);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function disconnect();

    /**
     * Return if the driver is a PDO driver
     *
     * @return boolean
     */
    abstract public function version();

    /**
     * Return if the driver is a PDO driver
     *
     * @return boolean
     */
    public function isPdo() {
        return (stripos(get_class($this), 'pdo') !== false);
    }
    
    /**
     * Return if the driver is available
     * @return boolean
     */
    public function isAvailable()
    {
        return (static::isAvailableAdapter(str_replace('adapter', '', strtolower(get_class($this)))) );
    }

    /**
     * Get an array of the tables of the database.
     *
     * @return array
     */
    abstract public function getTables($database='');
    
    /**
     * Get an array of the tables of the database.
     * 
     * @param string $table_name
     * @return array
     */
    abstract public function getColumns($tableName);
    
    public function getPrimaryKey($table)
    {
        $pks = static::getPrimaryKeys($table);
        
        return $pks ? $pks[0] : null;
    }
    
    /**
     * begin transaction
     * 
     * @return static
     * @see \Wslim\Db\AdapterInterface::beginTransaction()
     */
    abstract public function beginTransaction();
    
    /**
     * commit transaction
     *
     * @return boolean
     * @see \Wslim\Db\AdapterInterface::commit()
     */
    abstract public function commit();
    
    /**
     * rollback transaction
     *
     * @return static
     * @see \Wslim\Db\AdapterInterface::rollback()
     */
    abstract public function rollback();
    
}
