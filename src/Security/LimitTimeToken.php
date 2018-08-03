<?php
namespace Wslim\Security;

/**
 * LimitTimeToken, limit expire and shared token, default 20 minutes.
 * @desc 共享token根据数据生成token，应用需确保数据唯一性且多个操作互不影响
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class LimitTimeToken extends Token
{
    /**
     * options
     * @var array
     */
    protected $options = [
        'name'      => 'limit_time_token',
        'expire'    => 20 * 60,
        'shared'    => 0
    ];
}
