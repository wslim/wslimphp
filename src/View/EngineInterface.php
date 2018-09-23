<?php
namespace Wslim\View;

/**
 * The interface that every template engine driver must implement.
 *
 * @package Wslim\View
 * @link    wslim.cn
 */

interface EngineInterface
{ 
    /**
     * parse the string content to the php code and return.
     *
     * @param string $content   the template content, not the path.
     *
     * @return string Parsed content
     */
    public function parse(& $content);
    
    /**
     * get layout from template content
     * @param  string $content
     * @return string|NULL
     */
    public function getLayout(& $content);
    
}