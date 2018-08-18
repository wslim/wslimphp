<?php
namespace Wslim\Route;

interface RouteGroupInterface
{
    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern();
    
    public function getCallable();

}
