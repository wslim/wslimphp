<?php
namespace Wslim\Console\Controller;

use Wslim\Console\Controller;
use Wslim\Console\Request;
use Wslim\Console\Response;
use Wslim\Console\Argument;
use Wslim\Console\Option;
use Wslim\Builder;

class BuildController extends Controller
{
    /**
     * build type
     * @var string
     */
    protected $type;
    
    protected function init()
    {
        $this->setName('build')
        ->setDefinition([
            new Argument('type', Argument::REQUIRED, 'make type: module|controller|model|view'),
            new Argument('name', Argument::OPTIONAL, "make type name"),
            new Option('config', null, Option::VALUE_OPTIONAL, "build.php path"),
        ])
        ->setDescription('Build module|controller|model|view')
        ->setHelp(<<<EOF
<info>build module home</info> build home module
<info>build controller home:index</info> build controller index, it's belong module home
<info>build model home:demo</info> build model demo, it's belong module home
<info>build view home:index</info> build view index, it's belong module home
EOF
            );
    }
    
    protected function execute(Request $request, Response $response)
    {
        $type = trim($request->getArgument('type'));
        $name = trim($request->getArgument('name'));
        
        $module = '';
        if ($type === 'app' || $type === 'module') {
            $list = [];
            
            if ($request->hasOption('config')) {
                $filename = $request->getOption('config');
                
                if (!file_exists($filename)) {
                    $response->writeln("Build Config Is Empty");
                    return;
                }
                
                $list = include $filename;
            }
            
            if ($type === 'module') {
                $name = $name ? : 'home';
                Builder::buildModule($name, $list);
            } else {
                $namespace = $name;
                Builder::buildApp($name, $namespace);
            }
        } else {
            if ($pos = strpos($name, ':')) {
                $names = explode(':', $name, 2);
                $module = $names[0];
                $name   = $names[1];
            }
            Builder::build($type, $module, $name);
        }
        
        $response->writeln("Successed");
        
    }

}
