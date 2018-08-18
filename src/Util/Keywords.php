<?php
namespace Wslim\Util;

use Wslim\Ioc;

/**
 * 关键词分词类，根据内容返回关键词，使用 phpanalysis 类
 * 
 * @see http://www.phpbone.com/phpanalysis/
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Keywords
{
    /**
     * 根据内容返回空格分隔的关键词
     * @param  string  $data
     * @param  integer $number return keyword num
     * @return string
     */
    static public function get_keywords($data, $number = 4)
    {
        $number = intval($number);
        $number = $number > 0 ? $number - 1 : 3;	//作减1处理，因为类库本身会返回下标数的 number 个数
    
        //先处理html标签及特殊字符、过滤字符
        $data = html_entity_decode($data);
        $data = trim(strip_tags($data));
        $filter_data = array(
            'nbsp','rdquo','ldquo',
            '查看',
            '公司 ',
            '坚持',
            '客户',
            '留言',
            '难以',
            '已读',
            '之后',
        );
        $data = str_replace($filter_data, '',$data);
    
        if(empty($data)) return '';
        
        $loader = Ioc::loader();
        $loader->addClassMap(array(
            'PhpAnalysis'   => __DIR__ . '/../../plugin/phpanalysis/phpanalysis.class.php'
        ));
        //$loader->register(true);
        //$loader->loadClass('PhpAnalysis');
    
        //设置参数
        $pri_dict = false;	//是否预载全部词条
        $do_multi = true;	//多元切分
        $do_fork  = true;	//岐义处理
        $do_unit  = true;	//新词识别
        $do_prop  = false;	//词性标注
    
        //初始化类
        \PhpAnalysis::$loadInit = false;
        $pa = new \PhpAnalysis('utf-8', 'utf-8', $pri_dict);
        //print_memory('初始化对象', $memory_info);
    
        //载入词典
        $pa->LoadDict();
        //print_memory('载入基本词典', $memory_info);
    
        //设置结果类型
        $pa->SetResultType(2);
    
        //执行分词
        $pa->SetSource($data);
        $pa->differMax = $do_multi;
        $pa->unitWord = $do_unit;
    
        $pa->StartAnalysis( $do_fork );
        //print_memory('执行分词', $memory_info);
    
        //返回分词结果
        $okresult = $pa->GetFinallyResult(' ', $do_prop);
        //print_memory('输出分词结果', $memory_info);
    
        //返回关键词
        $keyresult = $pa->GetFinallyKeywords($number);
    
        $keyresult = trim(str_replace(',', ' ', $keyresult));
        
        return $keyresult;
    }
}