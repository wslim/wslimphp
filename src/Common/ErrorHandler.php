<?php
namespace Wslim\Common;

use Exception;
use ErrorException;
use Wslim\Ioc;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 * 
 * 1. exception
 * 2. error
 * 3. register_shutdown_function
 * 
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class ErrorHandler
{
    /**
     * the exception that is being handled currently.
     * @var Exception 
     */
    public $exception;

    /**
     * Used to reserve memory for fatal error handler.
     * @var string 
     */
    private $_memoryReserve;
    
    /**
     * is registered 
     * @var boolean 
     */
    private $isRegistered = false;
    
    /**
     * output Content-Type
     * @var string
     */
    protected $contentType = 'text/plain';
    
    /**
     * options
     * @var array
     */
    protected $options = [
        // whether to display error details
        'display_details'   => true,
        // whether to discard any existing page output before error display.
        'discard_output'    => true,
        /**
         * @var integer the size of the reserved memory. A portion of memory is pre-allocated so that
         * when an out-of-memory issue occurs, the error handler is able to handle the error with
         * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
         * Defaults to 256KB.
         */
        'memory_reserve_size' => 262144,
    ];
    
    public function __construct($options=[])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }
    
    /**
     * set options
     * @param  array $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, (array) $options);
    }
    
    /**
     * set output content-type
     * @param  string $contentType
     * @return static
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        
        return $this;
    }
    
    /**
     * get output Content-Type
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
    
    /**
     * Register this error handler
     * @return static
     */
    public function register($errorLevel = \E_WARNING)
    {
        if (!$this->isRegistered) {
    //      ini_set('display_errors', false);
    
            /**
             * 注册自定义异常处理
             */
            set_exception_handler([$this, 'handleException']);
            
            /**
             * error_types 里指定的错误类型都会绕过 PHP 标准错误处理程序， 除非回调函数返回了 FALSE。
             * error_reporting() 设置将不会起到作用而你的错误处理函数继续会被调用 —— 不过你仍然可以获取 error_reporting 的当前值，并做适当处理。 
             * 需要特别注意的是带 @ error-control operator 前缀的语句发生错误时，这个值会是 0。
             */
            if (!isset($errorLevel)) $errorLevel = \E_WARNING;
            set_error_handler([$this, 'handleError'], $errorLevel);
            
            if ($this->options['memory_reserve_size'] > 0) {
                $this->_memoryReserve = str_repeat('x', $this->options['memory_reserve_size']);
            }
            
            /**
             * 注册一个 callback ，会在脚本执行完成或者 exit() 后被调用。
             */
            register_shutdown_function([$this, 'handleFatalError']);
            
            $this->isRegistered = true;
        }
        
        return $this;
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     * @return boolean
     */
    public function unregister()
    {
        restore_error_handler();
        return restore_exception_handler();
    }
    
    /**
     * Handles PHP execution errors such as warnings and notices.
     * 处理截获的错误
     * 
     * This method is used as a PHP error handler. It will simply raise an [[ErrorException]].
     *
     * @param  integer $code the level of the error raised.
     * @param  string  $message the error message.
     * @param  string  $file the filename that the error was raised in.
     * @param  integer $line the line number the error was raised at.
     * 
     * @return boolean whether the normal error handler continues.
     *
     * @throws ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        // 此处只记录严重的错误 对于各种WARNING NOTICE不作处理
        if ((error_reporting() & $code) && !in_array($code, array(E_NOTICE, E_WARNING, E_USER_NOTICE, E_USER_WARNING, E_DEPRECATED))) {
            $exception = new ErrorException($message, $code, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] == '__toString') {
                    
                    $this->handleException($exception);
                    
                    exit(1);
                }
            }
            
            throw $exception;
        }
        return false; // 返回 FALSE，标准错误处理处理程序将会继续调用。
    }

    /**
     * Handles fatal PHP errors
     * 截获致命性错误，注册到 register_shutdown_function 中，由脚本完成时或exit时调用以作日志
     * 
     * @return void
     */
    public function handleFatalError()
    {
        unset($this->_memoryReserve);
        
        $error = error_get_last();
        
        if (static::isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;

            $this->logException($exception);
            
            /*
            if ($this->options['discard_output']) {
                $this->clearOutput();
            }
            
            $this->renderException($exception);
            */
            //debug_print_backtrace();
            
            exit(1);
        }
    }

    /**
     * is fatal error
     * @param  array $error
     * @return boolean
     */
    public static function isFatalError($error)
    {
    	return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
    }
    
    /**
     * Handles uncaught PHP exceptions.
     * 处理截获的未捕获的异常
     * 
     * This method is implemented as a PHP exception handler.
     *
     * @param Exception $exception the exception that is not caught
     * @return void
     */
    public function handleException($exception)
    {
    	if ($exception instanceof ExitException) {
    		return;
    	}
    	
    	$this->exception = $exception;
    	
    	// disable error capturing to avoid recursive errors while handling exceptions
    	$this->unregister();
    	
    	try {
    		// 记录日志
    		$this->logException($exception);
    		
    		if ($this->options['discard_output']) {
    			$this->clearOutput();
    		}
    		
    		// 显示错误信息
    		$this->renderException($exception);
    		
    	} catch (\Exception $e) {
    		// an other exception could be thrown while displaying the exception
    		$msg = (string) $e;
    		$msg .= "\nPrevious exception:\n";
    		$msg .= (string) $exception;
    		$msg .= "\n\$_SERVER = " . var_export($_SERVER, true);
    		
    		error_log($msg);
    		exit(1);
    	}
    	
    	$this->exception = null;
    }
    
    /**
     * Renders the exception.
     * @param  Exception $exception the exception to be rendered.
     * @return string
     */
    protected function renderException($exception)
    {
        $contentType = static::getContentType();
        $dataType = explode('/', $contentType);
        $dataType = isset($dataType[1]) ? $dataType[1] : 'text';

        switch ($dataType) {
            case 'json':
                $output = $this->getJsonMessage($exception);
                break;
                
            case 'xml':
                $output = $this->getXmlMessage($exception);
                break;
            
            case 'html':
                $output = $this->getHtmlMessage($exception);
                break;
                
            case 'text':
            default:
                $output = $this->getTextMessage($exception);
        }
        
        echo $output;
    }

    /**
     * Removes all output echoed before calling this method.
     * @return void
     */
    public function clearOutput()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }
    
    /**
     * log exception message
     * @param \Exception $exception
     * @return void
     */
    public function logException($exception)
    {
        $message = static::getItemTextMessage($exception);
        Ioc::logger()->error($message);
	}
	
	/**
	 * getexception text content
	 *
	 * @param  Exception $exception
	 *
	 * @return string
	 */
	protected function getTextMessage($exception)
	{
	    $message = 'Application Exception' . PHP_EOL;
	    $message .= static::getItemTextMessage($exception);
	    while ($exception= $exception->getPrevious()) {
	        $message .= PHP_EOL . 'Previous exception:' . PHP_EOL;
	        $message .= static::getItemTextMessage($exception);
	    }
	    return $message;
	}
	
    /**
     * get each exception text content
     *
     * @param  Exception $exception
     *
     * @return string
     */
    protected function getItemTextMessage($exception)
    {
    	$text = sprintf('Type: %s' . PHP_EOL, get_class($exception));
    	
    	if (($code = $exception->getCode())) {
    		$text .= sprintf('Code: %s' . PHP_EOL, $code);
    	}
    	
    	if (($message = $exception->getMessage())) {
    		$text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
    	}
    	
    	if (($file = $exception->getFile())) {
    		$text .= sprintf('File: %s' . PHP_EOL, $file);
    	}
    	
    	if (($line = $exception->getLine())) {
    		$text .= sprintf('Line: %s' . PHP_EOL, $line);
    	}
    	
    	if (($trace = $exception->getTraceAsString())) {
    		$text .= sprintf('Trace: ' . PHP_EOL. '%s', $trace);
    	}
    	
    	return $text;
    }
    
    /**
     * get html format of exception
     *
     * @param  Exception $exception
     * @return string
     */
    protected function getHtmlMessage($exception)
    {
        $title = 'Application Error';
        
        if ($this->options['display_details']) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->getItemHtmlMessage($exception);
            
            while ($exception = $exception->getPrevious()) {
                $html .= '<h2>Previous exception</h2>';
                $html .= $this->getItemHtmlMessage($exception);
            }
        } else {
            $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
        }
        
        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:14px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
            );
        
        return $output;
    }
    
    /**
     * get each exception html content
     *
     * @param Exception $exception
     *
     * @return string
     */
    protected function getItemHtmlMessage($exception)
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));
        
        if (($code = $exception->getCode())) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }
        
        if (($message = $exception->getMessage())) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }
        
        if (($file = $exception->getFile())) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        
        if (($line = $exception->getLine())) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }
        
        if (($trace = $exception->getTraceAsString())) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }
        
        return $html;
    }
    
    /**
     * get JSON error
     *
     * @param  Exception $exception
     * @return string
     */
    protected function getJsonMessage($exception)
    {
        $error = array(
            'message' => 'Application Error',
        );
        
        if ($this->options['display_details']) {
            $error['exception'] = array();
            
            do {
                $error['exception'][] = array(
                    'type' => get_class($exception),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString()),
                );
            } while ($exception = $exception->getPrevious());
        }
        
        return json_encode($error, JSON_PRETTY_PRINT);
    }
    
    /**
     * get exception xml content
     *
     * @param  Exception $exception
     * @return string
     */
    protected function getXmlMessage($exception)
    {
        $xml = "<error>\n  <message>Application Error</message>\n";
        if ($this->options['display_details']) {
            do {
                $xml .= "  <exception>\n";
                $xml .= "    <type>" . get_class($exception) . "</type>\n";
                $xml .= "    <code>" . $exception->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($exception->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $exception->getFile() . "</file>\n";
                $xml .= "    <line>" . $exception->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($exception->getTraceAsString()) . "</trace>\n";
                $xml .= "  </exception>\n";
            } while ($exception = $exception->getPrevious());
        }
        $xml .= "</error>";
        
        return $xml;
    }
    
    /**
     * Returns a CDATA section with the given content.
     *
     * @param  string $content
     * @return string
     */
    protected function createCdataSection($content)
    {
        return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
    }
    
}
