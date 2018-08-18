<?php
namespace Wslim\Db;

/**
 * db adapter interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface AdapterInterface
{
    
    /**
     * connect or reconnect
     * @return void
     */
    public function connect();
    
    /**
     * Get the connection resource
     *
     * @return resource
     */
    public function getConnection();
    
    /**
     * Throw an exception upon a database error.
     *
     * @throws Exception
     * @return void
     */
    public function showError($msg=null);

    /**
     * get last error message
     * @return string 
     */
    public function getErrorMessage();
    
    /**
     * Prepare a SQL query.
     *
     * @param  string $sql
     * @return static
     */
    public function prepare($sql, $attributes = null);
    
    /**
     * Bind parameters to a prepared SQL query.
     *
     * @param  array $params
     * @return static
     */
    public function bindParams($params);
    
    /**
     * Execute the SQL query and create a result resource, or display the SQL error.
     *
     * @param  string $sql
     * @return void
     */
    public function query($sql);

    /**
     * Execute the prepared SQL query.
     *
     * @throws Exception
     * @return boolean
     */
    public function execute();
    
    /**
     * Return one record result array from the results resource.
     *
     * @throws Exception
     * @return array
     */
    public function fetch();
    
    /**
     * Return the results array from the results resource.
     *
     * @throws Exception
     * @return array
     */
    public function fetchAll();

    /**
     * Return the escaped string value.
     *
     * @param  string $value
     * @return string
     */
    public function escape($value);

    /**
     * Return the auto-increment ID of the last query.
     *
     * @return int
     */
    public function lastId();

    /**
     * Return the number of rows in the result.
     *
     * @throws Exception
     * @return int
     */
    public function numberOfRows();

    /**
     * Return the number of fields in the result.
     *
     * @throws Exception
     * @return int
     */
    public function numberOfFields();

    /**
     * Determine whether or not an result resource exists
     *
     * @return boolean
     */
    public function hasResult();

    /**
     * Get the result resource
     *
     * @return resource
     */
    public function getResult();

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect();

    /**
     * Return the database version.
     *
     * @return string
     */
    public function version();

    /**
     * Return if the driver is a PDO driver
     *
     * @return boolean
     */
    public function isPdo();

    /**
     * Return if the driver is available
     * @return boolean
     */
    public function isAvailable();
    
    /**
     * Get an array of the tables of the database.
     *
     * @return array
     */
    public function getTables($database=null);
    
    /**
     * Get an array of the tables of the database.
     * 
     * @param  string $table
     * @return array
     */
    public function getColumns($table);
    
    /**
     * get primary keys
     * @param  string $table
     * @return array
     */
    public function getPrimaryKeys($table);
    
    /**
     * get first primary key
     * @param  string $table
     * @return string
     */
    public function getPrimaryKey($table);
    
    /**
     * beginTransaction
     * 
     * @return static
     */
    public function beginTransaction();
    
    /**
     * commit transaction
     * 
     * @return boolean
     */
    public function commit();
    
    /**
     * rollback transaction
     *
     * @return static
     */
    public function rollback();
    
}
