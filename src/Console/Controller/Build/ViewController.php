<?php
namespace Wslim\Console\Controller\Build;

class ViewController extends BaseController
{
    protected $type = 'view';
    
    protected function init()
    {
        parent::init();
        
        $this->setName('build:model')
            ->setDescription('Create a new model class');
    }
    
}
