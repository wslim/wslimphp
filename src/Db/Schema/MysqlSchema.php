<?php
namespace Wslim\Db\Schema;


class MysqlSchema extends AbstractSchema
{
    private $charset        = 'utf8';
    private $tableEngine    = 'INNODB';
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\SchemaInterface::getCreateDatabaseSql()
     */
    public function getCreateDatabaseSql($database)
    {
        return 'CREATE DATABASE IF NOT EXISTS ' . $database . ' DEFAULT CHARACTER SET utf8';
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Db\SchemaInterface::getCreateTableSql()
     */
    public function getCreateTableSql($tableName, array $options=null)
    {
        $sql = '';
        if (!isset($options['fields'])) {
            $sql = 'SHOW CREATE TABLE ' . $tableName;
            $this->adapter->query($sql);
            $result = $this->adapter->fetchAll();
            if (empty($result)) {
                return '';
            }
            return $result['Create Table'];
        } elseif ($fields = $options['fields']) {
            $fieldsSql = array();
            $primaryKeys = array();
            foreach ($fields as $v) {
                if (empty($v)) continue;
                
                $fieldsSql[] = $this->getColumnSubSql($v);
                
                if (isset($v['is_primary']) && $v['is_primary']) $primaryKeys[] = $v['field_name'];
            }
            
            if (!empty($fieldsSql)) {
                $sql = 'CREATE TABLE IF NOT EXISTS ' . $tableName . ' (' . PHP_EOL;
                $sql .= implode(',' . PHP_EOL, $fieldsSql) . PHP_EOL ;
                
                // 主键
                //$sql .= (!empty($primaryKeys)) ? ',' . PHP_EOL . 'PRIMARY KEY ('. implode('`,`', $primaryKeys) . ')'. PHP_EOL : '';
                
                // 索引
                $indexesSql = array();
                foreach ($fields as $v) {
                    $temp = $this->getColumnIndexSql($v);
                    if ($temp) $indexesSql[] = $temp;
                }
                if ($indexesSql) {
                    $sql .= ',' . implode(',' . PHP_EOL, $indexesSql);
                }
                
                $sql .= ') ENGINE=' . $this->tableEngine . ' DEFAULT CHARSET=' . $this->charset;
                $sql .= (isset($options['comment']) && !empty($options['comment'])) ? ' COMMENT=\'' . $options['comment'] . '\'' : '';
            }
        }
        return $sql;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Wslim\Db\SchemaInterface::getAlterTableInfo()
     */
    public function getAlterTableInfo($tableName, array $options)
    {
        if (is_array($tableName)) {
            $options = $tableName;
            $tableName = isset($options['table_name']) ? $options['table_name'] : null;
        }
        if (!isset($options['fields']) || empty($options['fields'])) {
            return [];
        }
        
        $sql = array();
        $summary = '';
        $tableBaseName = (strpos($tableName, '.') !== false) ? substr($tableName, strrpos($tableName, '.') + 1) : $tableName;
        
        $tables = $this->adapter->getTables();
        if ($tables && in_array($tableBaseName, $this->adapter->getTables())) {

            $exists = $this->adapter->getColumns($tableName);  // 已存在的字段
            $oldFields = array();                   // 传入的字段修改字段名的
            $fields = & $options['fields'];
            
            foreach($fields as $v) {
                $temp = '';
                if (!isset($exists[$v['field_name']])) {        // 不存在该字段，如存在旧名称字段则 change，否则就 add
                    if (isset($v['old_name']) && !empty($v['old_name']) && isset($exists[$v['old_name']]) ) {
                        $oldFields[] = $v['old_name'];
                        $temp = ' CHANGE `' . $v['old_name'] . '` ' . $this->getColumnSubSql($v); 
                    } else {
                        $temp = ' ADD ' . $this->getColumnSubSql($v);
                    }
                    // 如果为主键时，需要使用字段内嵌式指定
                    if (isset($v['is_primary']) && $v['is_primary']) {
                        $temp .= ' PRIMARY KEY';
                    }
                    $sql[] = 'ALTER TABLE ' . $tableName . $temp;
                } else {   // 存在该字段，如数据类型/长度/注释有一个不一致则进行 change
                    $if_modify = false;
                    
                    if (strcmp($v['data_type'], $exists[$v['field_name']]['data_type']) != 0) {
                        $if_modify = true;
                    } elseif (in_array($v['data_type'], array('varchar', 'char', 'int', 'tinyint')) 
                        && isset($v['data_length'])
                        && intval($v['data_length']) !== intval($exists[$v['field_name']]['data_length'])) {
                        $if_modify = true;
                    } elseif (isset($v['is_nullable']) && (bool)$v['is_nullable'] !== (bool)$exists[$v['field_name']]['is_nullable']) {
                        $if_modify = true; 
                    } elseif (isset($v['default']) && strcmp($v['default'], $exists[$v['field_name']]['default']) != 0) {
                        $if_modify = true; 
                    }
                    //if ($v['field_title'] !== '') $if_modify = true;
                    
                    if ($if_modify) {
                        $sql[] = 'ALTER TABLE ' . $tableName . ' MODIFY ' . $this->getColumnSubSql($v); 
                    }
                }
            }
            foreach ($exists as $v) {
                if (!isset($fields[$v['field_name']]) && !isset($oldFields[$v['field_name']])) {
                    $sql[] = 'ALTER TABLE ' . $tableName . ' DROP `' . $v['field_name'] . '`';
                }
            }
            
            $summary = '[' . $tableName . '] : Table is exists .';
            $summary .= (!empty($sql)) ? ' Alter info is below: ' : ' no alter info.';
        } else {
            $sql[] = $this->getCreateTableSql($tableName, $options);
            
            if (!empty($sql)) {
                $summary = '[' . $tableName . '] : Table is not exists. Create info is below: ';
            }
        }
        $result = !empty($summary) ? array('summary' => $summary, 'sql' => $sql) : array();
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Wslim\Db\SchemaInterface::getAlterTableSql()
     */
    public function getAlterTableSql($tableName, array $options)
    {
        $sqls = '';
        $infos = & $this->getAlterTableInfo($tableName, $options);
        if (isset($infos['sql'])) {
            if (is_array($infos['sql'])) foreach ($infos['sql'] as $sql) {
                $sqls .= $sql . ';' . PHP_EOL;
            }
        }
        return $sqls;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getDropTableSql($tableName)
    {
        return 'DROP TABLE IF EXISTS ' . $tableName;
    }
    
    private function getCheckedColumnDataLength(& $field)
    {
        switch ($field['data_type']) {
            case 'int':
            case 'integer':
                $result = isset($field['data_length']) ? $field['data_length'] : 11;
                break;
            case 'tinyint':
                $result = isset($field['data_length']) ? $field['data_length'] : 1;
                break;
            case 'smallint':
                $result = isset($field['data_length']) ? $field['data_length'] : 6;
                break;
            case 'mediumint':
                $result = isset($field['data_length']) ? $field['data_length'] : 8;
                break;
            case 'bigint':
                $result = isset($field['data_length']) ? $field['data_length'] : 20;
                break;
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
                $result = '';
                break;
            default:
                $result = isset($field['data_length']) ? $field['data_length'] : null;
                break;
        }
        return $result;
    }
    
    protected function getColumnSubSql($column)
    {
        $temp = '`' . $column['field_name'] . '` ';
        $temp .= $column['data_type'];
        $length = $this->getCheckedColumnDataLength($column);
        $precisionStr = isset($column['precision']) ? ',' . intval($column['precision']) : '';
        $temp .= empty($length) ? '' : '('. $length . $precisionStr . ')';
        $temp .= (isset($column['unsigned']) && $column['unsigned'] && empty($precisionStr)) ? ' UNSIGNED' : '';
        
        // [NOT] NULL DEFAULT [default]
        if (isset($column['auto_increment']) && (bool)$column['auto_increment'] ) {
            $temp .= ' NOT NULL AUTO_INCREMENT';
        } else {
            $is_nullable = (isset($column['is_nullable']) && $column['is_nullable'] == 0) ? false : true;
            
            if (preg_match('/int|real|double|float|decimal|numeric|year|timestamp/', $column['data_type'])) {
                $temp .= ' NOT NULL';
                $default = isset($column['default']) ? intval($column['default']) : '0';
            } else {
                $temp .= $is_nullable ? ' NULL' : ' NOT NULL';
                $default = isset($column['default']) ? $column['default'] : null;
                if (!$default && preg_match('/char/', $column['data_type'])) {
                    $default = '';
                }
            }
            $temp .= is_null($default) ? '' : ' DEFAULT \'' . $default . '\'';
        }
        // COMMET
        $desc  = (isset($column['comment']) && !empty($column['comment'])) ? $column['comment'] : (isset($column['field_title']) && !empty($column['field_title']) ? $column['field_title'] : null);
        if ($desc) {
            $temp .= ' COMMENT \'' . $desc . '\'';
        }
        return $temp;
    }
    
    protected function getColumnIndexSql($column)
    {
        $temp = '';
        if (isset($column['is_primary']) && $column['is_primary']) {
            $temp = 'PRIMARY KEY (`' . $column['field_name'] . '`)';
        } elseif (isset($column['is_unique']) && $column['is_unique']) {
            $temp = 'UNIQUE KEY `' . $column['field_name'] . '` (`' . $column['field_name'] . '`)';
        } elseif (isset($column['is_indexable']) && $column['is_indexable']) {
            $temp = 'KEY `' . $column['field_name'] . '` (`' . $column['field_name'] . '`)';
        }
        return $temp;
    }
}