<?php
namespace Wslim\Console;

use Wslim\Console\Question\Choice;
use Wslim\Console\Question\Confirmation;

/**
 * Console Response
 * 
 * @see    \Wslim\Console\ResponseOutput::setDecorated()
 * @method void setDecorated($decorated)
 *
 * @method void info($message)
 * @method void error($message)
 * @method void comment($message)
 * @method void warning($message)
 * @method void highlight($message)
 * @method void question($message)
 */
class Response
{
    const VERBOSITY_QUIET        = 0;
    const VERBOSITY_NORMAL       = 1;
    const VERBOSITY_VERBOSE      = 2;
    const VERBOSITY_VERY_VERBOSE = 3;
    const VERBOSITY_DEBUG        = 4;
    
    const OUTPUT_NORMAL = 0;
    const OUTPUT_RAW    = 1;
    const OUTPUT_PLAIN  = 2;
    
    /** @var int */
    private $verbosity = self::VERBOSITY_NORMAL;
    
    /** @var \Wslim\Console\ResponseOutput */
    private $output = null;
    
    /** @var \Wslim\Console\Descriptor */
    private $descriptor;
    
    /** @var array */
    protected $styles = [
        'info',
        'error',
        'comment',
        'question',
        'highlight',
        'warning'
    ];
    
    /**
     * body
     * @var string
     */
	protected $body;
	
	public function __construct()
	{
	    $this->output = new ResponseOutput();
	}
	
	public function ask(Request $request, $question, $default = null, $validator = null)
	{
	    $question = new Question($question, $default);
	    $question->setValidator($validator);
	    
	    return $this->askQuestion($request, $question);
	}
	
	public function askHidden(Request $request, $question, $validator = null)
	{
	    $question = new Question($question);
	    
	    $question->setHidden(true);
	    $question->setValidator($validator);
	    
	    return $this->askQuestion($request, $question);
	}
	
	public function confirm(Request $request, $question, $default = true)
	{
	    return $this->askQuestion($request, new Confirmation($question, $default));
	}
	
	public function choice(Request $request, $question, array $choices, $default = null)
	{
	    if (null !== $default) {
	        $values  = array_flip($choices);
	        $default = $values[$default];
	    }
	    
	    return $this->askQuestion($request, new Choice($question, $choices, $default));
	}
	
	protected function askQuestion(Request $request, Question $question)
	{
	    $ask    = new Ask($request, $this, $question);
	    $answer = $ask->run();
	    
	    if ($request->isInteractive()) {
	        $this->newLine();
	    }
	    
	    return $answer;
	}
	
	protected function block($style, $message)
	{
	    $this->writeln("<{$style}>{$message}</$style>");
	}
	
	/**
	 * 输出空行
	 * @param int $count
	 * @return static
	 */
	public function newLine($count = 1)
	{
	    $this->write(str_repeat(PHP_EOL, $count));
	    return $this;
	}
	
	/**
	 * 输出信息并换行
	 * @param string $messages
	 * @param int    $type
	 * @return static
	 */
	public function writeln($messages, $type = self::OUTPUT_NORMAL)
	{
	    return $this->write($messages, true, $type);
	}
	
	/**
	 * 输出信息
	 * @param string $messages
	 * @param bool   $newline
	 * @param int    $type
	 * @return static
	 */
	public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
	{
	    $this->output->write($messages, $newline, $type);
	    return $this;
	}
	
	/**
	 * 
	 * @param \Exception $e
	 * @return static
	 */
	public function renderException(\Exception $e)
	{
	    $this->output->renderException($e);
	    return $this;
	}
	
	public function setVerbosity($level)
	{
	    $this->verbosity = (int) $level;
	    
	    $this->output->setVerbosity($this->verbosity);
	}
	
	public function getVerbosity()
	{
	    return $this->verbosity;
	}
	
	public function isQuiet()
	{
	    return self::VERBOSITY_QUIET === $this->verbosity;
	}
	
	public function isVerbose()
	{
	    return self::VERBOSITY_VERBOSE <= $this->verbosity;
	}
	
	public function isVeryVerbose()
	{
	    return self::VERBOSITY_VERY_VERBOSE <= $this->verbosity;
	}
	
	public function isDebug()
	{
	    return self::VERBOSITY_DEBUG <= $this->verbosity;
	}
	
	public function describe($object, array $options = [])
	{
	    if (!$this->descriptor) {
	        $this->descriptor = new Descriptor();
	    }
	    
	    $options    = array_merge([
	        'raw_text' => false,
	    ], $options);
	    
	    $this->descriptor->describe($this, $object, $options);
	}
	
	public function __call($method, $args)
	{
	    if (in_array($method, $this->styles)) {
	        array_unshift($args, $method);
	        return call_user_func_array([$this, 'block'], $args);
	    }
	    
	    if ($this->output && method_exists($this->output, $method)) {
	        return call_user_func_array([$this->output, $method], $args);
	    } else {
	        throw new \Exception('method not exists:' . __CLASS__ . '->' . $method);
	    }
	}
	
}