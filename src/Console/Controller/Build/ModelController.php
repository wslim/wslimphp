<?php
namespace Wslim\Console\Controller\Build;

class ModelController extends BaseController
{
    protected $type = 'model';
    
    protected function init()
    {
        parent::init();
        
        $this->setName('build:model')
            ->setDescription('Create a new model class');
    }
    
}
