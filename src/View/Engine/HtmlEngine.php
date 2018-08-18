<?php
namespace Wslim\View\Engine;

use Wslim\View\EngineInterface;

/**
 * html template engine
 * 
 * @author 28136957@qq.com
 *
 */
class HtmlEngine implements EngineInterface
{
    /**
     * {@inheritDoc}
     */
    public function parse(& $content)
    {
        $content = $this->parse_var($content);
        return $content;
    }
    
    /**
     * <h3 data-var="title"></h3>
     * @param string $content
     */
    protected function parse_var($content)
    {
        $content = preg_replace ( "/\{php\s+([^\}]+)\}/", "<?php \\1?>", $content );
    }
    
    
    
    
    
    
}