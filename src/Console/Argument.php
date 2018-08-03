<?php
namespace Wslim\Console;

class Argument
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const IS_ARRAY = 4;
    
    private $name;
    private $mode;
    private $description;
    private $default;

    /**
     * 构造方法
     * @param string $name        参数名
     * @param int    $mode        参数类型: self::REQUIRED 或者 self::OPTIONAL
     * @param string $description 描述
     * @param mixed  $default     默认值 (仅 self::OPTIONAL 类型有效)
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $mode = null, $description = '', $default = null)
    {
        if (null === $mode) {
            $mode = self::OPTIONAL;
        } elseif (!is_numeric($mode) || $mode > 7 || $mode < 1) {
            throw new \InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $mode));
        }

        $this->name        = $name;
        $this->mode        = $mode;
        $this->description = $description;

        $this->setDefault($default);
    }

    /**
     * 获取参数名
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * set name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * 是否必须
     * @return bool
     */
    public function isRequired()
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    /**
     * 该参数是否接受数组
     * @return bool
     */
    public function isArray()
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }

    /**
     * 设置默认值
     * @param mixed $default 默认值
     * @throws \LogicException
     */
    public function setDefault($default = null)
    {
        if (self::REQUIRED === $this->mode && null !== $default) {
            throw new \LogicException('Cannot set a default value except for Argument::OPTIONAL mode.');
        }

        if ($this->isArray()) {
            if (null === $default) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new \LogicException('A default value for an array argument must be an array.');
            }
        }

        $this->default = $default;
    }

    /**
     * 获取默认值
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * 获取描述
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}