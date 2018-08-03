<?php
namespace Wslim\Console\Controller\Build;

use Wslim\Console\Controller;
use Wslim\Console\Request;
use Wslim\Console\Response;
use Wslim\Console\Argument;
use Wslim\Builder;

class BaseController extends Controller
{
    protected $type = 'class';
    
    protected function init()
    {
        $this->setName('build:class')
            ->setDefinition([
                new Argument('name', Argument::REQUIRED, 'name')
            ])
            ->setDescription('Create a new class');
    }
    
    protected function execute(Request $request, Response $response)
    {
        $name = trim($request->getArgument('name'));
        
        Builder::build($this->type, null, $name);
        
        $response->writeln("Successed");
    }
}
