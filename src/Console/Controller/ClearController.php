<?php
namespace Wslim\Console\Controller;

use Wslim\Console\Controller;
use Wslim\Console\Request;
use Wslim\Console\Response;
use Wslim\Console\Option;
use Wslim\Common\Config;

class ClearController extends Controller
{
    protected function init()
    {
        // 指令配置
        $this->setName('clear')
            ->setOption('path', 'd', Option::VALUE_OPTIONAL, 'path to clear', null)
            ->setDescription('Clear runtime file');
    }

    protected function execute(Request $request, Response $response)
    {
        $path  = $request->getOption('path') ?: Config::getStoragePath();
        $files = scandir($path);
        if ($files) {
            foreach ($files as $file) {
                if ('.' != $file && '..' != $file && is_dir($path . $file)) {
                    array_map('unlink', glob($path . $file . '/*.*'));
                } elseif (is_file($path . $file)) {
                    unlink($path . $file);
                }
            }
        }
        $response->writeln("<info>Clear Successed</info>");
    }
}
