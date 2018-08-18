<?php
namespace Wslim\Security;

/**
 * shared token, based on some unique array data.
 * @desc 共享token根据数据生成token，应用需确保数据唯一性且多个操作互不影响
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class SharedToken extends Token
{
    /**
     * options
     * @var array
     */
    protected $options = [
        'name'      => 'shared_token',
        'shared'    => 1
    ];
}
