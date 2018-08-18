<?php
namespace Wslim\Util;

class Tree
{
    const FORMAT_TREE   = 'tree';
    
    const FORMAT_FLAT   = 'flat';
    
    /**
     * 原始数据按 id 为键处理后的数组
     * @var array
     */
    private $data;
    
    /**
     * 默认字段结构 array('id', 'parent_id')
     * @var array
     */
    private $fields;
    
    /**
     * 根级的id值
     * @var int
     */
    private $root;
    
    /**
     * 中间处理结果，是所有非空叶的数组
     * @var array
     */
    private $stack;
    
    /**
     * 中间临时id结果集，用于每一次处理的临时存储ids
     * @var array
     */
    private $resultIds;
    
    /**
     * 构造函数，初始化类
     * 
     * @param array $data 2维数组，例如：
     * array(
     *      1 => array('id'=>'1','parent_id'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','parent_id'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','parent_id'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','parent_id'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','parent_id'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','parent_id'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','parent_id'=>3,'name'=>'三级栏目二')
     *      )
     * @param array|string $fields 按顺序指定id,parent_id的键名
     * 
     */
    public function __construct($data, $fields = ['id', 'parent_id'], $root = 0)
    {
        $this->data     = $data;
        $this->fields   = is_string($fields) ? explode(',', $fields) : $fields;
        foreach ($this->fields as $k=>$v) {
            $this->fields[$k] = trim($v);
        }
        $this->root     = $root;
        $this->init();
    }
    
    /**
     * 初始化数据，生成格式化的一维数组 data 和带 items 的树栈 stack
     * @param array $data
     */
    private function init($data=null)
    {
        if (!is_null($this->stack)) return $this;
        
        if (!is_null($data))  $this->data = $data;
        if (!is_array($this->data) || empty($this->data)) return $this;
        
        // 先转为 id 为键的数据
        $t_data = array();
        $pids   = array();
        foreach ($this->data as $k => $v) {
            if (!isset($v[$this->fields[0]])) {
                throw new \InvalidArgumentException('tree fields error:' . implode(',', $this->fields));
                break; // 如果数组结构不符合，跳过
            }
            $t_data[$v[$this->fields[0]]] = $v;
            $pids[$v[$this->fields[1]]] = $v[$this->fields[1]];
        }
        $this->data = & $t_data;
        
        // 修正root，当parent_id=root不存在时取最小的parent_id作为root
        if (!isset($pids[$this->root])) {
            ksort($pids);
            $this->root = array_keys($pids)[0];
        }
        
        // 先生成父子级的二维的数组
        foreach ($this->data as $node) {
            $this->stack[$node[$this->fields[1]]][$node[$this->fields[0]]] = $node;
        }
        
        
        // 递归遍历生成 完整的带有 items 的数组
        foreach ($this->stack as $k => $v) {
            $this->handleStackElement($k);
        }
        //print_r($this->stack);exit;
    }
    
    private function handleStackElement($parent_id)
    {
        if ($this->stack[$parent_id]) {
            foreach ($this->stack[$parent_id] as $sk => $sv) {
                if (isset($this->stack[$sk]) && $parent_id != $sk) {
                    $this->handleStackElement($sk);
                    $this->stack[$parent_id][$sk]['items'] = $this->stack[$sk];
                }
            }
        }
    }
    
    /**
     * 反向递归，取上级
     * @return array
     */
    private function recur_reverse($id, $if_recur=false)
    {
        if (!$id) return array();
        if (!$if_recur) $this->resultIds = array();
        foreach ($this->data as $v) {
            if ($v[$this->fields[0]] == $id && $v[$this->fields[1]] != $this->root) {
                $this->recur_reverse($v[$this->fields[1]], true); // 先递归获取父父级，再将父级加到列表
                $this->resultIds[] = $v[$this->fields[1]];
            }
        }
        return $this->resultIds;
    }
    
    /**
     * 正向递归，取下级
     * @return array
     */
    private function recur_forward($id, $if_recur=false)
    {
        if (!$id) return array();
        if (!$if_recur) $this->resultIds = array();
        
        if ($this->stack[$id]) {
            foreach ($this->stack[$id] as $v) {
                $this->resultIds[] = $v[$this->fields[0]];
                if ($v['items']) {
                    foreach($v['items'] as $v2) {
                        $this->resultIds[] = $v2[$this->fields[0]];  // 先将子级加到列表，再递归获取子子级
                        $this->recur_forward($v2[$this->fields[0]], true);
                    }
                }
                
            }
        }
        return $this->resultIds;
    }

    /**
     * 返回数据生成的树
     * @return array
     */
    public function tree($id = null)
    {
        $id = ($id == null) ? $this->root : $id;
        return $this->stack[$id];
    }
    
    /**
     * 返回所有后代ids
     * @param  int   $id
     * @return array
     */
    public function leafIds($id = null)
    {
        return $this->recur_forward($id);
    }
    
    /**
     * 返回所有父id
     * @param int $id
     * @return array
     */
    public function ancestorIds($id)
    {
        return $this->recur_reverse($id);
    }

    /**
     * 返回直接子级，一维数组
     * @param int $id
     * @return array|null
     */
    public function children($id = null)
    {
        $id = ($id == null) ? $this->root : $id;
        foreach ($this->data as $v) {
            if ($v[$this->fields[1]] == $id) {
                $result[$v[$this->fields[0]]] = $v;
            }
        }
        return isset($result) ? $result : null;
    }
    
    /**
     * 返回兄弟级，一维数组
     * @param int $id
     * @return array
     */
    public function siblings($id = null)
    {
        $pid = $id = ($id == null) ? $this->root : $id;
        foreach ($this->data as $v) {
            if ($v[$this->fields[0]] == $id) $pid = $v[$this->fields[1]];
        }
        foreach ($this->data as $v) {
            if ($v[$this->fields[1]] == $pid) {
                $result[$v[$this->fields[0]]] = $v;
            }
        }
        return isset($result) ? $result : null;
    }
    
    /**
     * 返回子树的一维数组，该结果是平面的
     * @param int $id
     * @param int $depth 
     * @return array
     */
    public function flat($id = null, $depth = 0) {
        $id = ($id == null) ? $this->root : $id;
        $result     = array();
        $depth      = $depth;
        if (isset($this->stack[$id]) && $this->stack[$id]) {
            foreach($this->stack[$id] as $k => $v) {
                if ($this->data[$k]) {
                    $result[$k]   = $this->data[$k];
                    $result[$k]['depth']   = $depth;
                }
                if (isset($v['items']) && $v['items']) {
                    foreach($v['items'] as $k2 => $v2) {
                        if ($this->data[$k2]) {
                            $result[$k2]   = $this->data[$k2];
                            $result[$k2]['depth']   = $depth + 1;
                        }
                        // 不要用 merge 会清除键名
                        $result = $result + $this->flat($v2[$this->fields[0]], $depth + 2);
                        
                    }
                }
            }
        } elseif (isset($this->data[$id]) && $this->data[$id]) {
            $result[$id]            = $this->data[$id];
            $result[$id]['depth']   = $depth;
        }
        return $result;
    }
}