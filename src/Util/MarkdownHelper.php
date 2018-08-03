<?php
namespace Wslim\Util;

use Wslim\Util\Markdown\Parser;

class MarkdownHelper
{
    /**
     * Parser
     * @var \Wslim\Util\Markdown\Parser
     */
    static private $parse;
    
    /**
     * to html
     * @param  string $text
     * @return string
     */
    static public function toHtml($text)
    {
        // 先处理下混合的html
        $text = str_replace(['<br>', '<br/>', '<br />'], "\r\n", $text);
        $text = str_replace(['<p>', '</p>'], ["", "\r\n"], $text);
        
        /**/
        if (!static::$parse) {
            static::$parse = new Parser();
        }
        $text = static::$parse->makeHtml($text);
        
        return $text;
    }
}