<?php
namespace Wslim\Db\Adapter;

use Wslim\Db\Exception;

class PdoMysqlAdapter extends PdoAdapter
{
    // mysql trait
    use \Wslim\Db\Traits\MysqlTrait;
    
    /**
     * Constructor
     * Instantiate the PDO database connection object.
     *
     * @param  array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        $options['dbtype'] = 'mysql';
        
        parent::__construct($options);
    }
    
    /**
     * 获取表字段信息
     *
     * @param  string $table
     * @return array
     */
    public function getColumns($table)
    {
        $sql = 'SHOW COLUMNS FROM '. $this->formatKey($table);
        
        $result = $this->query($sql);
        if ($result === false) {
            return array();
        }
        
        $array = array();
        while ($tmp = $result->fetch(\PDO::FETCH_ASSOC)){
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
        
        return $this->formatColumns($array);
    }
    
    

}