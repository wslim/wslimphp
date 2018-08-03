<?php
namespace Wslim\Console\Controller\Build;

use Wslim\Console\Option;

class ControllerController extends BaseController
{

    protected $type = "Controller";

    protected function init()
    {
        parent::init();
        
        $this->setName('build:controller')
            ->setOption('plain', null, Option::VALUE_NONE, 'Generate an empty controller class.')
            ->setDescription('Create a new controller class');
    }
    
}
