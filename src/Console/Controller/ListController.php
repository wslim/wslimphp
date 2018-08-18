<?php
namespace Wslim\Console\Controller;

use Wslim\Console\Controller;
use Wslim\Console\Request;
use Wslim\Console\Response;
use Wslim\Console\Definition;
use Wslim\Console\Argument;
use Wslim\Console\Option;

class ListController extends Controller
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->setName('list')->setDefinition($this->createDefinition())->setDescription('Lists controllers')->setHelp(<<<EOF
The <info>%controller.name%</info> controller lists all controllers:

  <info>php %controller.full_name%</info>

You can also display the controllers for a specific namespace:

  <info>php %controller.full_name% test</info>

It's also possible to get raw list of controllers (useful for embedding controller runner):

  <info>php %controller.full_name% --raw</info>
EOF
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Request $request, Response $response)
    {
        $response->describe($this->getApp(), [
            'raw_text'  => $request->getOption('raw'),
            'namespace' => $request->getArgument('namespace'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new Definition([
            new Argument('namespace', Argument::OPTIONAL, 'The namespace name'),
            new Option('raw', null, Option::VALUE_NONE, 'To output raw controller list')
        ]);
    }
}
