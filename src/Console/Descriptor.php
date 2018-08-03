<?php
namespace Wslim\Console;


class Descriptor
{
    /**
     * @var Response 
     */
    protected $response;

    public function describe(Response  $response, $object, array $options = [])
    {
        $this->response = $response;
        
        switch (true) {
            case $object instanceof Argument:
                $this->describeArgument($object, $options);
                break;
            case $object instanceof Option:
                $this->describeOption($object, $options);
                break;
            case $object instanceof Definition:
                $this->describeDefinition($object, $options);
                break;
            case $object instanceof Controller:
                $this->describeController($object, $options);
                break;
            case $object instanceof App:
                $this->describeConsole($object, $options);
                break;
            default:
                throw new \InvalidArgumentException(__METHOD__ . ':' . sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }

    /**
     * 输出内容
     * @param string $content
     * @param bool   $decorated
     */
    protected function write($content, $decorated = false)
    {
        $this->response->write($content, false, $decorated ? Response ::OUTPUT_NORMAL : Response ::OUTPUT_RAW);
    }

    /**
     * 描述参数
     * @param  Argument $argument
     * @param  array    $options
     * @return string|mixed
     */
    protected function describeArgument(Argument $argument, array $options = [])
    {
        if (null !== $argument->getDefault()
            && (!is_array($argument->getDefault())
                || count($argument->getDefault()))
        ) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($argument->getDefault()));
        } else {
            $default = '';
        }

        $totalWidth   = isset($options['total_width']) ? $options['total_width'] : strlen($argument->getName());
        $spacingWidth = $totalWidth - strlen($argument->getName()) + 2;

        $this->writeText(sprintf("  <info>%s</info>%s%s%s", $argument->getName(), str_repeat(' ', $spacingWidth), // + 17 = 2 spaces + <info> + </info> + 2 spaces
            preg_replace('/\s*\R\s*/', PHP_EOL . str_repeat(' ', $totalWidth + 17), $argument->getDescription()), $default), $options);
    }

    /**
     * 描述选项
     * @param Option $option
     * @param array       $options
     * @return string|mixed
     */
    protected function describeOption(Option $option, array $options = [])
    {
        if ($option->acceptValue() && null !== $option->getDefault()
            && (!is_array($option->getDefault())
                || count($option->getDefault()))
        ) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
        } else {
            $default = '';
        }

        $value = '';
        if ($option->acceptValue()) {
            $value = '=' . strtoupper($option->getName());

            if ($option->isValueOptional()) {
                $value = '[' . $value . ']';
            }
        }

        $totalWidth = isset($options['total_width']) ? $options['total_width'] : $this->calculateTotalWidthForOptions([$option]);
        $synopsis   = sprintf('%s%s', $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ', sprintf('--%s%s', $option->getName(), $value));

        $spacingWidth = $totalWidth - strlen($synopsis) + 2;

        $this->writeText(sprintf("  <info>%s</info>%s%s%s%s", $synopsis, str_repeat(' ', $spacingWidth), // + 17 = 2 spaces + <info> + </info> + 2 spaces
            preg_replace('/\s*\R\s*/', "\n" . str_repeat(' ', $totalWidth + 17), $option->getDescription()), $default, $option->isArray() ? '<comment> (multiple values allowed)</comment>' : ''), $options);
    }

    /**
     * 描述输入
     * @param Definition      $definition
     * @param array           $options
     * @return string|mixed
     */
    protected function describeDefinition(Definition $definition, array $options = [])
    {
        $totalWidth = $this->calculateTotalWidthForOptions($definition->getOptions());
        foreach ($definition->getArguments() as $argument) {
            $totalWidth = max($totalWidth, strlen($argument->getName()));
        }

        if ($definition->getArguments()) {
            $this->writeText('<comment>Arguments:</comment>', $options);
            $this->writeText("\n");
            foreach ($definition->getArguments() as $argument) {
                $this->describeArgument($argument, array_merge($options, ['total_width' => $totalWidth]));
                $this->writeText("\n");
            }
        }

        if ($definition->getArguments() && $definition->getOptions()) {
            $this->writeText("\n");
        }

        if ($definition->getOptions()) {
            $laterOptions = [];

            $this->writeText('<comment>Options:</comment>', $options);
            foreach ($definition->getOptions() as $option) {
                if (strlen($option->getShortcut()) > 1) {
                    $laterOptions[] = $option;
                    continue;
                }
                $this->writeText("\n");
                $this->describeOption($option, array_merge($options, ['total_width' => $totalWidth]));
            }
            foreach ($laterOptions as $option) {
                $this->writeText("\n");
                $this->describeOption($option, array_merge($options, ['total_width' => $totalWidth]));
            }
        }
    }

    /**
     * describe controller
     * @param Controller $controller
     * @param array   $options
     * @return string|mixed
     */
    protected function describeController(Controller $controller, array $options = [])
    {
        $controller->getSynopsis(true);
        //$controller->getSynopsis(false);
        $controller->mergeConsoleDefinition(false);

        $this->writeText('<comment>Usage:</comment>', $options);
        foreach (array_merge([$controller->getSynopsis(true)], $controller->getAliases(), $controller->getUsages()) as $usage) {
            $this->writeText("\n");
            $this->writeText('  ' . $usage, $options);
        }
        $this->writeText("\n");

        $definition = $controller->getNativeDefinition();
        if ($definition->getOptions() || $definition->getArguments()) {
            $this->writeText("\n");
            $this->describeDefinition($definition, $options);
            $this->writeText("\n");
        }

        if ($help = $controller->getProcessedHelp()) {
            $this->writeText("\n");
            $this->writeText('<comment>Example:</comment>', $options);
            $this->writeText("\n");
            $this->writeText(' ' . str_replace("\n", "\n ", $help), $options);
            $this->writeText("\n");
        }
    }

    /**
     * 描述控制台
     * @param  App      $console
     * @param  array    $options
     * @return string|mixed
     */
    protected function describeConsole(App $console, array $options = [])
    {
        $describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
        $description        = new ConsoleDescriptor($console, $describedNamespace);
        
        if (isset($options['raw_text']) && $options['raw_text']) {
            $width = $this->getColumnWidth($description->getControllers());

            foreach ($description->getControllers() as $controller) {
                $this->writeText(sprintf("%-${width}s %s", $controller->getName(), $controller->getDescription()), $options);
                $this->writeText("\n");
            }
        } else {
            if ('' != ($help = $console->getHelp())) {
                $this->writeText("$help\n\n", $options);
            }
            
            $this->writeText("<comment>Usage:</comment>\n", $options);
            $this->writeText("  controller [options] [arguments]\n\n", $options);

            $this->describeDefinition(new Definition($console->getDefinition()->getOptions()), $options);

            $this->writeText("\n");
            $this->writeText("\n");
            
            $width = $this->getColumnWidth($description->getControllers());
            
            if ($describedNamespace) {
                $this->writeText(sprintf('<comment>Available controllers for the "%s" namespace:</comment>', $describedNamespace), $options);
            } else {
                $this->writeText('<comment>Available controllers:</comment>', $options);
            }
            
            // add controllers by namespace
            foreach ($description->getNamespaces() as $namespace) {
                if (!$describedNamespace && ConsoleDescriptor::GLOBAL_NAMESPACE !== $namespace['id']) {
                    $this->writeText("\n");
                    $this->writeText('  <comment><' . $namespace['id'] . '></comment>', $options);
                }
                
                foreach ($namespace['controllers'] as $name) {
                    $this->writeText("\n");
                    $spacingWidth = $width - strlen($name);
                    if ($spacingWidth < 1) {
                        $spacingWidth = 1;
                    }
                    $this->writeText(sprintf("  <info>%s</info>%s%s", $name, str_repeat(' ', $spacingWidth), $description->getController($name)
                            ->getDescription()), $options);
                }
            }

            $this->writeText("\n");
        }
    }

    /**
     * {@inheritdoc}
     */
    private function writeText($content, array $options = [])
    {
        $this->write(isset($options['raw_text'])
            && $options['raw_text'] ? strip_tags($content) : $content, isset($options['raw_output']) ? !$options['raw_output'] : true);
    }

    /**
     * 格式化
     * @param mixed $default
     * @return string
     */
    private function formatDefaultValue($default)
    {
        return json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Controller[] $controllers
     * @return int
     */
    private function getColumnWidth(array $controllers)
    {
        $width = 0;
        foreach ($controllers as $controller) {
            $width = strlen($controller->getName()) > $width ? strlen($controller->getName()) : $width;
        }

        return $width + 2;
    }

    /**
     * @param Option[] $options
     * @return int
     */
    private function calculateTotalWidthForOptions($options)
    {
        $totalWidth = 0;
        foreach ($options as $option) {
            $nameLength = 4 + strlen($option->getName()) + 2; // - + shortcut + , + whitespace + name + --

            if ($option->acceptValue()) {
                $valueLength = 1 + strlen($option->getName()); // = + value
                $valueLength += $option->isValueOptional() ? 2 : 0; // [ + ]

                $nameLength += $valueLength;
            }
            $totalWidth = max($totalWidth, $nameLength);
        }

        return $totalWidth;
    }
}
