<?php
namespace Wslim\Db;

/**
 * schema interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface SchemaInterface
{    
    
    public function getCreateDatabaseSql($database);
    
    /**
     * get create table sql from fields
     * @param  string $tableName
     * @param  array  $options ['fields'=>.., 'table_name'=>.., 'comment'=>..]
     * @return string sql
     */
    public function getCreateTableSql($tableName, array $options=null);
    
    /**
     * get alter table info from options, result is array, keys has summary and sql
     * @param string $tableName
     * @param array  $options ['fields'=>.., 'table_name'=>.., 'comment'=>..]
     * @return array ['summary'=>'...', 'sql' => []]
     */
    public function getAlterTableInfo($tableName, array $options);
    
    /**
     * get alter table sql from fields, result is sql string
     * @param  string $tableName
     * @param  array  $options
     * @return string 'sql; sql2; ...'
     */
    public function getAlterTableSql($tableName, array $options);
    
    /**
     * get drop table sql
     * @param string $tableName
     */
    public function getDropTableSql($tableName);

}