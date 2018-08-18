<?php
namespace Wslim\Db\Schema;

use Wslim\Db\SchemaInterface;

abstract class AbstractSchema implements SchemaInterface
{
    /**
     * 
     * @var \Wslim\Db\AdapterInterface
     */
    protected $adapter;
    
    public function __construct($adapter=null)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\SchemaInterface::getDropTableSql()
     */
    public function getDropTableSql($tableName)
    {
        return 'DROP TABLE ' . $tableName;
    }
 
}
