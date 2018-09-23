<?php
namespace Wslim\View;

use Wslim\View\Exception as ViewException;
use Wslim\Util\Dir;
use Wslim\Util\File;
use Wslim\Common\Component;
use Wslim\Common\Collection;
use Wslim\Common\Config;
use Wslim\Util\ArrayHelper;

/**
 * View
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class View extends Component
{
	/**
	 * engines
	 * @var array [$key=>$engineClassName]
	 */
	static protected $engines = array(
			'phtml' => '\\Wslim\\View\\Engine\\PhtmlEngine', // first use
	);
	
	/**
	 * engine instance
	 * @var \Wslim\View\EngineInterface
	 */
	private $_engineInstance;
	
	/**
	 * options. 
	 * if overwrite you need merge parent.
	 * 
	 * @var array
	 */
	protected function defaultOptions()
	{
	    return array(
	        'engine'       => 'phtml',
	        'suffix'       => 'html',
	        'templatePath' => '',
	        'compiledPath' => 'view',
	        'htmlPath'     => 'html',
	        'theme'        => 'theme1',
	        'layout'       => '',
	        'nolayout'     => [],  // no layout template list
	        'isCompiled'   => true,
	        'begin_content'=>'',
	    );
	}
	
	/**
	 * data
	 * @var \Wslim\Common\Collection
	 */
	protected $data;
	
	/**
	 * construct
	 * @param array $options
	 */
	public function __construct(array $options=null)
	{
		if ($options) foreach($options as $key=>$value) {
		    $this->options[$key] = $value;
		}
		
		foreach (static::defaultOptions() as $key => $value) {
		    if (!isset($this->options[$key])) {
		        $this->options[$key] = $value;
		    }
		}
		
		$this->initViewConfig();
		
		$this->data = new Collection();
	}
	
	/**
	 * register template engine class 
	 * @param string $name example: smart, blade
	 * @param string $class example: \namespace\classname
	 */
	public function registerEngine($name, $class)
	{
		static::$engines[$name] = $class;
	}
	
	/**
	 * engine type
	 * @param  string $engine
	 * @return static
	 */
	public function setEngine($engine)
	{
		$this->options['engine'] = $engine;
		return $this;
	}
	
	/**
	 * get engine type
	 * @return string
	 */
	public function getEngine()
	{
		return $this->options['engine'];
	}
	
	/**
	 * set template suffix
	 * @param string $suffix
	 * @return static
	 */
	public function setSuffix($suffix)
	{
		$this->options['suffix'] = $suffix;
		return $this;
	}
	
	/**
	 * get suffix
	 * @return string
	 */
	public function getSuffix()
	{
		return '.' . ltrim($this->options['suffix'], '.');
	}
	
	/**
	 * template path
	 * @param  string $templatePath
	 * @return static
	 */
	public function setTemplatePath($templatePath)
	{
	    $this->options['templatePath'] = $templatePath;
		return $this;
	}
	
	/**
	 * get templatePath
	 * @return string
	 */
	public function getTemplatePath()
	{
		return rtrim($this->options['templatePath'], '/');
	}
	
	/**
	 * compiledPath
	 * @param  string $compiledPath
	 * @return static
	 */
	public function setCompiledPath($compiledPath)
	{
	    $this->options['compiledPath'] = $compiledPath;
		return $this;		
	}
	
	/**
	 * compiled path
	 * @return string
	 */
	public function getCompiledPath()
	{
	    return rtrim($this->options['compiledPath'], '/');
	}
	
	/**
	 * html Path
	 * @param  string $htmlPath
	 * @return static
	 */
	public function setHtmlPath($htmlPath)
	{
	    $this->options['htmlPath'] = $htmlPath;
		return $this;
	}
	
	/**
	 * get html full path
	 * @return string
	 */
	public function getHtmlPath()
	{
	    return rtrim($this->options['htmlPath'], '/');
	}
	
	/**
	 * set layout template
	 * @param string $layout
	 * @return static
	 */
	public function setLayout($layout)
	{
		$this->options['layout'] = $layout;
		return $this;
	}
	
	/**
	 * get layout template
	 * @return string
	 */
	public function getLayout()
	{
		return $this->options['layout'];
	}
	
	/**
	 * set theme dir
	 * @param string $theme
	 * @return static
	 */
	public function setTheme($theme)
	{
	    $otheme = $this->options['theme'];
	    
		$this->options['theme'] = $theme;
		
		if ($otheme != $theme) {
		    $this->initViewConfig();
		}
		
		return $this;
	}
	/**
	 * get theme dir
	 * @return string
	 */
	public function getTheme()
	{
		return !empty($this->options['theme']) ? trim($this->options['theme'], '/') : '';
	}
	
	public function initViewConfig()
	{
	    $cfile = $this->getTemplatePath() . '/' . ($this->getTheme() ? $this->getTheme() . '/' : '') . '_config.php';
	    if ($cfile && is_file($cfile)) {
	        $config = require $cfile;
	        $this->options = ArrayHelper::merge($this->options, $config);
	    }
	}
	
	/**
	 * get template engine instance
	 * @return \Wslim\View\EngineInterface
	 */
	public function getEngineInstance()
	{
		if (!isset($this->_engineInstance)) {
			$this->_engineInstance = new static::$engines[$this->options['engine']];
		}
		return $this->_engineInstance;
	}
	
	/**
	 * get begin view content
	 * @return string
	 */
	public function getBeginContent()
	{
	    return isset($this->options['begin_content']) ? $this->options['begin_content'] : null;
	}
	
	/**
	 * set begin content 
	 * @param string $content
	 * @return static
	 */
	public function setBeginContent($content)
	{
	    $this->options['begin_content'] = $content;
	    return $this;
	}
	
	/**
	 * get view data, if key then value, else return all data
	 * @param  string $key
	 * @return mixed|\Wslim\Common\Collection
	 */
	public function getData($key=null)
	{
	    if ($key) {
	        return $this->data->get($key);
	    }
	    return $this->data;
	}
	
	/**
	 * set view data
	 * @param string|array $key
	 * @param mixed $value
	 *
	 * @return static
	 */
	public function setData($key, $value=null)
	{
	    $this->data->set($key, $value);
	    return $this;
	}
	
	/**
	 * set view data, same as setData()
	 * @param string|array $key
	 * @param mixed $value
	 * 
	 * @return static
	 */
	public function assign($key, $value=null)
	{
	    $this->data->set($key, $value);
	    return $this;
	}
	
	/**
	 * render template file to string
	 * 
	 * @param string $template
	 * @param array|Collection  $vdata
	 * @return string
	 */
	public function render($template, $vdata=array())
	{
	    if (!$this->templateExists($template)) {
	        return 'Template is not exists:' . $this->getTemplateRelativePath($template);
	    }
	    
		// view vars
	    if ($this->getData()->keys()) {
	        extract($this->getData()->all(), EXTR_SKIP);
		}
		if ($vdata instanceof Collection) {
		    extract($vdata->all(), EXTR_SKIP);
		} elseif ($vdata) {
		    extract($vdata, EXTR_SKIP);
		}
		unset($vdata);
		
		// layout
		$_layout = static::getLayoutFromTemplate($template);
		if (is_null($_layout)) {
		    if ($this->getLayout() && $template !== $this->getLayout() && ($this->options['nolayout'] || !in_array($template, $this->options['nolayout']))) {
		        // layout template
		        // 对于 phtml 模板: {include $__content_template__}
		        // 对于 php 模板:   <?php include $this->include($__content_template__); ?\>
		        $__content_template__ = $template;
		        $template = $this->getLayout();
		    }
		} elseif ($_layout) {
		    $__content_template__ = $template;
		    $template = $_layout;
		}
		
		ob_start();
		require $this->getCompiledFile($template);
		
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	/**
	 * parse content
	 * @param  string $content
	 * @return string
	 */
	public function parse(&$content)
	{
	    $str = $this->getEngineInstance()->parse($content);
	    return $this->getBeginContent() . $str;
	}
	
	/**
	 * get layout from template
	 * @param  string $template
	 * @return string|NULL
	 */
	public function getLayoutFromTemplate($template)
	{
	    $content = file_get_contents($this->getTemplateFile($template));
	    
	    return $this->getEngineInstance()->getLayout($content);
	}
	
	/**
	 * 供模板引擎调用，用来包含子模板
	 * @param  string $template
	 * @return string
	 */
	public function template($template)
	{
		return $this->getCompiledFile($template);
	}
	
	/**
	 * templateExists
	 * @param  string $template
	 * @return boolean
	 */
	public function templateExists($template)
	{
	    return is_file($this->getTemplateFile($template));
	}
	
	/**
	 * get relative template
	 * @param  string $template
	 * @return string
	 */
	protected function _getRelativeTemplate($template)
	{
	    return ($this->getTheme() ? '/' . $this->getTheme() : '') . '/' . trim($template, '/');
	}
	
	/**
	 * get template absolute path
	 * @return string
	 */
	public function getTemplateFile($template)
	{
	    if (!is_file($template)) {
	        $template = $this->getTemplatePath() . $this->_getRelativeTemplate($template) . $this->getSuffix();
	    }
	    
	    return $template;
	}
	
	/**
	 * get template relative path
	 * @param  string $template
	 * @return string
	 */
	public function getTemplateRelativePath($template)
	{
	    $rootPath = Config::getRootPath();
	    return str_replace($rootPath, '', $this->getTemplatePath()) . $this->_getRelativeTemplate($template) . $this->getSuffix();
	}
	
	/**
	 * get compiled template full path
	 * @param  string $template
	 * @throws \Wslim\View\Exception
	 * @return string
	 */
	public function getCompiledFile($template)
	{	
		$tplFile = static::getTemplateFile($template);
		if ($this->options['engine'] === 'php'){
			return $tplFile;
		}
		
		if(file_exists($tplFile)) {
		    if (is_file($template)) {
		        $tplCompiledFile = static::getCompiledPath() . '/_file/' . md5($template) . '.php';
		    } else {
                $tplCompiledFile = static::getCompiledPath() . $this->_getRelativeTemplate($template) . '.php';
		    }
		    
			if (!$this->options['isCompiled'] || !file_exists($tplCompiledFile) || (@filemtime($tplFile) > @filemtime($tplCompiledFile)) ) {
				$tplCompiledPath = dirname($tplCompiledFile);
				if (!file_exists($tplCompiledPath)) {
					Dir::create($tplCompiledPath, 0755);
				}
				
				$content = file_get_contents($tplFile);
				$content = $this->parse($content);
				
				$strlen = file_put_contents($tplCompiledFile, $content);
				chmod ($tplCompiledFile, 0755);
			}
			
			return $tplCompiledFile;
		} else {
			throw new ViewException('template: ' . $tplFile. ' is not exists.');
		}
	}
	
	/**
	 * get html file
	 * @param  string $template
	 * @return string
	 */
	public function getHtmlFile($template)
	{
	    return $this->getHtmlPath() . '/' . trim($template, '/') . '.html';
	}
	
	/**
	 * make  html
	 * @param  string  $template
	 * @param  array   $data
	 * @return boolean true if success
	 */
	public function makeHtml($template, $data=null)
	{
		$content = $this->render($template, $data);
		return File::write($this->getHtmlFile($template), $content); 
	}
	
	/**
	 * fragment 
	 * @param  string $template
	 * @param  string $content
	 * @return string
	 */
	public function getFragmentFile($template, $content)
	{
        $tplCompiledFile = static::getCompiledPath() . '/fragment/' . ltrim($template, '/') . '.php';
        
        if (!$this->options['isCompiled'] || !file_exists($tplCompiledFile) ) {
            $tplCompiledPath = dirname($tplCompiledFile);
            if (!file_exists($tplCompiledPath)) {
                Dir::create($tplCompiledPath, 0755);
            }
            
            $content = $this->parse($content);
            $strlen = file_put_contents($tplCompiledFile, $content);
            chmod ($tplCompiledFile, 0755);
        }
        
        return $tplCompiledFile;
	}
	
	/**
	 * clear template cache
	 * @return boolean true for success
	 */
	public function clearCache()
	{
	    $path = $this->getCompiledPath();
	    if (is_dir($path)) {
	        return Dir::delete($path);
	    }
	    return true;
	}
	
	/**
	 * render messageBox template
	 * 
	 * @param  array  $data
	 * @return string
	 */
	public function renderMessageBox($data=null)
	{
	    $needData = [
	        'url'      => '_stop',
	        'rootUrl'  => Config::getRootUrl(),
	        'errtype'  => '提示'
	    ];
	    
	    $data = $data ? : [];
	    $data = array_merge($needData, $data);
	    $template = $this->getMessageBoxTemplate();
	    return $this->setLayout(null)->render($template, $data);
	}
	
	/**
	 * get messageBox template
	 * @return string
	 */
	public function getMessageBoxTemplate()
	{
	    if ($this->templateExists('message')) {
	        return $this->getTemplateFile('message');
	    } else {
	        return __DIR__ . '/message.html';
	    }
	}

}
