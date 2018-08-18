<?php
namespace Wslim\Drdb;

use Wslim\Ioc;

class Drdb
{
    protected $options = [
        'instance_count' => 2,
    ];
    
    public function getOption($key=null)
    {
        if ($key) {
            return isset($this->options[$key]) ? $this->options[$key] : null;
        }
        return $this->options;
    }
    
    public function find($table, $id, $options=null)
    {
        $db = static::getMappingDb($table, $id);
        
        return $db->table($table)->where([$db->getPrimaryKey($table) => $id ])->query($options);
    }
    
    public function getMappingDb($table, $id)
    {
        $name = 'db_instance_' . ($id % static::getOption('instance_count'));
        
        return Ioc::db($name);
    }
}