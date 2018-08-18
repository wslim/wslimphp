<?php
namespace Wslim\Route;

/**
 * A routable
 */
abstract class Routable
{
	/**
	 * Route pattern
	 *
	 * @var string
	 */
	protected $pattern;
	
	/**
	 * Route callable
	 *
	 * @var mixed
	 */
	protected $callable;
	
	/**
	 * Get the route pattern
	 *
	 * @return string
	 */
	public function getPattern()
	{
		return $this->pattern;
	}
	
	/**
	 * Set the route pattern
	 *
	 * @param string $newPattern
	 */
	public function setPattern($newPattern)
	{
		$this->pattern = $newPattern;
	}
	
	/**
	 * get callable
	 * 
	 * @return mixed
	 */
	public function getCallable()
	{
		return $this->callable;
	}

}
