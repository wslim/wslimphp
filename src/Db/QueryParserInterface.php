<?php
namespace Wslim\Db;

/**
 * query parse interface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
interface QueryParserInterface
{
    /**
     * parse options and return sql.
     * notice: after parse can auto call clear() by clearStatus, it default true.
     *         if run multi query on the same settings, can set clearStatus is false, after run set clearStatus true
     * 
     * @param  mixed  $query \Wslim\Db\Query|array
     * @return string $sql
     */
    public function parse($query=null);

    /**
     * format key
     * @access public
     * @param  string $key
     * @param  bool   $hasAlias default false
     * @return string
     */
    public function formatKey($key, $hasAlias=false);
    
    /**
     * format value, need consider date
     * @access public
     * @param  mixed   $value
     * @param  boolean $hasAlias
     * @return string
     */
    public function formatValue($value, $hasAlias=false);

}