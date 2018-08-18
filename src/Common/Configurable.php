<?php
namespace Wslim\Common;

/**
 * Configurable class, implements ArrayAccess, class can set property and options by config
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Configurable implements \ArrayAccess, ConfigurableInterface
{
    use ConfigurableTrait;
}