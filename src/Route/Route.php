<?php
namespace Wslim\Route;

use InvalidArgumentException;

/**
 * Route
 * 路由的 callable 的格式不限定, 可以是 string/array/callable, 具体处理由应用解析并执行.
 */
class Route extends Routable implements RouteInterface
{

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = array();

    /**
     * Route identifier
     *
     * @var string
     */
    protected $identifier;
    
    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;
    
    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = array();
    
    /**
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    /**
     * Create new route
     *
     * @param string[]     $methods The route HTTP methods
     * @param string       $pattern The route pattern
     * @param mixed        $callable The route callable
     * @param int          $identifier The route identifier
     * @param RouteGroup[] $groups The parent route groups
     */
    public function __construct($methods, $pattern, $callable, $groups = array(), $identifier = 0)
    {
        $this->methods  = $methods;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->groups   = $groups;
        $this->identifier = 'route' . $identifier;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }
    
    /**
     * Get parent route groups
     *
     * @return \Wslim\Route\RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return self
     *
     * @throws InvalidArgumentException if the route name is not a string
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
    	foreach ($arguments as $k => $v) {
    		$this->arguments[$k] = $v;
    	}
        
        return $this;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

}
