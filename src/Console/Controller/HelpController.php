<?php
namespace Wslim\Console\Controller;

use Wslim\Console\Controller;
use Wslim\Console\Request;
use Wslim\Console\Response;
use Wslim\Console\Option;
use Wslim\Console\Argument;

class HelpController extends Controller
{

    private $controller;

    protected function init()
    {
        $this->ignoreValidationErrors();

        $this->setName('help')
            ->setDefinition([
                new Argument('controller_name', Argument::OPTIONAL, 'The controller name', 'help'),
                new Option('raw', null, Option::VALUE_NONE, 'To output raw controller help'),
            ])
            ->setDescription('Displays help for a controller')
            ->setHelp(<<<EOF
The <info>%controller.name%</info> controller displays help for a given controller:

  <info>php %controller.full_name% list</info>

To display the list of available controllers, please use the <info>list</info> controller.
EOF
        );
    }

    /**
     * Sets the controller.
     * @param Controller $controller The controller to set
     */
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Request $request, Response $response)
    {
        if (null === $this->controller) {
            $this->controller = $this->getApp()->findController($request->getArgument('controller_name'));
        }
        
        $response->describe($this->controller, [
            'raw_text' => $request->getOption('raw'),
        ]);

        $this->controller = null;
    }
}
