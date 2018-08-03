<?php
namespace Wslim\Console;

use Wslim\Common\Controller as BaseController;

class Controller extends BaseController
{
    private $aliases = [];
    private $definition;
    private $help;
    private $description;
    private $ignoreValidationErrors          = false;
    private $consoleDefinitionMerged         = false;
    private $consoleDefinitionMergedWithArgs = false;
    private $code;
    private $synopsis = [];
    private $usages   = [];

    /** @var  Request */
    protected $request;

    /** @var  Response */
    protected $response;

    /**
     * __construct, name can be set by init()
     * 
     * @param string|null $name
     * @throws \LogicException
     */
    public function __construct($name = null)
    {
        $this->definition = new Definition();
        
        if (null !== $name) {
            $this->setName($name);
        }
        
        $this->init();

        if (!$this->name) {
            throw new \LogicException(sprintf('The controller defined in "%s" cannot have an empty name.', get_class($this)));
        }
    }
    
    /**
     * overwrite, do nothing
     */
    protected function init()
    {
        
    }
    
    /**
     * ignore validation errors
     */
    public function ignoreValidationErrors()
    {
        $this->ignoreValidationErrors = true;
    }

    /**
     * is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * execute
     * @param Request  $request
     * @param Response $response
     * @return null|int
     * @throws \LogicException
     * @see setCode()
     */
    protected function execute(Request $request, Response $response)
    {
        throw new \LogicException('You must override the execute() method in the concrete controller class.');
    }

    /**
     * interact
     * @param Request  $request
     * @param Response $response
     */
    protected function interact(Request $request, Response $response)
    {
        
    }

    /**
     * handle, call execute()
     * 
     * @param Request  $request
     * @param Response $response
     * @return int     $exitCode
     * @throws \Exception
     * @see setCode()
     * @see execute()
     */
    public function handle($request, $response)
    {
        $this->response = $response;
        
        //$this->getSynopsis(true);
        $this->getSynopsis(false);
        
        $this->mergeConsoleDefinition();
        
        try {
            $request->bind($this->definition);
        } catch (\Exception $e) {
            if (!$this->ignoreValidationErrors) {
                throw $e;
            }
        }
        
        $this->beforeExecute($request, $response);
        
        if ($request->isInteractive()) {
            $this->interact($request, $response);
        }
        
        try {
            $request->validate();
        } catch (\Exception $e) {
            throw $e;
        }
        
        
        if ($this->code) {
            $statusCode = call_user_func($this->code, $request, $response);
        } else {
            $statusCode = $this->execute($request, $response);
        }
        
        return is_numeric($statusCode) ? (int) $statusCode : 0;
    }

    /**
     * beforeExecute
     * @param Request  $request
     * @param Response $response
     */
    protected function beforeExecute(Request $request, Response $response)
    {
        
    }
    
    /**
     * set code
     * @param  callable $code callable(Request $request, Response $response)
     * @return Controller
     * @throws \InvalidArgumentException
     * @see execute()
     */
    public function setCode(callable $code)
    {
        if (!is_callable($code)) {
            throw new \InvalidArgumentException('Invalid callable provided to Controller::setCode.');
        }

        if (PHP_VERSION_ID >= 50400 && $code instanceof \Closure) {
            $r = new \ReflectionFunction($code);
            if (null === $r->getClosureThis()) {
                $code = \Closure::bind($code, $this);
            }
        }

        $this->code = $code;

        return $this;
    }

    /**
     * merge definition
     * @param bool $mergeArgs
     */
    public function mergeConsoleDefinition($mergeArgs = true)
    {
        
        if ((true === $this->consoleDefinitionMerged) && ($this->consoleDefinitionMergedWithArgs || !$mergeArgs)) {
            return;
        }
        
        if ($mergeArgs) {
            $currentArguments = $this->definition->getArguments();
            $this->definition->setArguments($this->getApp()->getDefinition()->getArguments());
            $this->definition->addArguments($currentArguments);
        }

        $this->definition->setOptions($this->getApp()->getDefinition()->getOptions());
        
        $this->consoleDefinitionMerged = true;
        if ($mergeArgs) {
            $this->consoleDefinitionMergedWithArgs = true;
        }
    }

    /**
     * set definition
     * @param array|Definition $definition
     * @return Controller
     * @api
     */
    public function setDefinition($definition)
    {
        if ($definition instanceof Definition) {
            $this->definition = $definition;
        } else {
            $this->definition->setDefinition($definition);
        }

        $this->consoleDefinitionMerged = false;

        return $this;
    }

    /**
     * get merged definition
     * @return Definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * get definition
     * @return Definition
     */
    public function getNativeDefinition()
    {
        return $this->getDefinition();
    }

    /**
     * set argument
     * @param string $name        名称
     * @param int    $mode        类型
     * @param string $description 描述
     * @param mixed  $default     默认值
     * @return Controller
     */
    public function setArgument($name, $mode = null, $description = '', $default = null)
    {
        $this->definition->setArgument(new Argument($name, $mode, $description, $default));

        return $this;
    }

    /**
     * setOption
     * @param string $name        选项名称
     * @param string $shortcut    别名
     * @param int    $mode        类型
     * @param string $description 描述
     * @param mixed  $default     默认值
     * @return Controller
     */
    public function setOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        $this->definition->setOption(new Option($name, $shortcut, $mode, $description, $default));

        return $this;
    }

    /**
     * set name
     * @param string $name
     * @return Controller
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        $this->validateName($name);

        if (!$this->name) {
            $this->name = $name;
        } elseif ($this->name !== $name) {
            $this->setAliases([$name]);
        }
        return $this;
    }
    
    /**
     * setDescription
     * @param string $description
     * @return Controller
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * getDescription
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * setHelp
     * @param  string $help
     * @return Controller
     */
    public function setHelp($help)
    {
        $this->help = $help;
        
        return $this;
    }

    /**
     * getHelp
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * getProcessedHelp
     * @return string
     */
    public function getProcessedHelp()
    {
        $name = $this->name;

        $placeholders = [
            '%controller.name%',
            '%controller.full_name%',
        ];
        $replacements = [
            $name,
            $_SERVER['PHP_SELF'] . ' ' . $name,
        ];

        return str_replace($placeholders, $replacements, $this->getHelp());
    }

    /**
     * setAliases
     * @param  string[] $aliases
     * @return Controller
     * @throws \InvalidArgumentException
     */
    public function setAliases($aliases)
    {
        if (!is_array($aliases) && !$aliases instanceof \Traversable) {
            throw new \InvalidArgumentException('$aliases must be an array or an instance of \Traversable');
        }

        foreach ($aliases as $alias) {
            $this->validateName($alias);
        }

        $this->aliases = $aliases;

        return $this;
    }

    /**
     * getAliases
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * getSynopsis
     * @param  bool $short 是否简单的
     * @return string
     */
    public function getSynopsis($short = false)
    {
        $key = $short ? 'short' : 'long';

        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis($short)));
        }

        return $this->synopsis[$key];
    }

    /**
     * addUsage
     * @param  string $usage
     * @return static
     */
    public function addUsage($usage)
    {
        if (0 !== strpos($usage, $this->name)) {
            $usage = sprintf('%s %s', $this->name, $usage);
        }

        $this->usages[] = $usage;

        return $this;
    }

    /**
     * getUsages
     * @return array
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * validateName
     * @param  string $name
     * @throws \InvalidArgumentException
     */
    private function validateName($name)
    {
        if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Controller name "%s" is invalid.', $name));
        }
    }
}
