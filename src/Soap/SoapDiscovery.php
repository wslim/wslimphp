<?php
namespace Wslim\Soap;

use ReflectionClass;
use Exception;
use Wslim\Util\StringHelper;

/**
 * Copyright (c) 2005, Braulio Jos� Solano Rojas
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 * 
 * 	Redistributions of source code must retain the above copyright notice, this list of
 * 	conditions and the following disclaimer. 
 * 	Redistributions in binary form must reproduce the above copyright notice, this list of
 * 	conditions and the following disclaimer in the documentation and/or other materials
 * 	provided with the distribution. 
 * 	Neither the name of the Solsoft de Costa Rica S.A. nor the names of its contributors may
 * 	be used to endorse or promote products derived from this software without specific
 * 	prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 *
 * @version $Id$
 * @copyright 2005 
 */

/**
 * SoapDiscovery Class that provides Web Service Definition Language (WSDL).
 * Based this, modify the wsdl url and add the createWSDL() method.
 * 
 * @package SoapDiscovery
 * @author Braulio Jos� Solano Rojas
 * @copyright Copyright (c) 2005 Braulio Jos� Solano Rojas
 * @version $Id$
 * @access public
 **/
class SoapDiscovery {
	private $class_name = '';
	private $service_name = '';
	
	private $wsdl_path = null;
	private $wsdl_url = null;
	private $service_url = null;
	
	/**
	 * SoapDiscovery::__construct() SoapDiscovery class Constructor.
	 * 
	 * @param string $class_name
	 * @param string $service_name
	 **/
	public function __construct($class_name = '', $service_name = '') {
		$this->class_name = $class_name;
		$this->service_name = !empty($service_name) ? $service_name : StringHelper::toUnderscoreSeparated($class_name);
		
		if (isset($_SERVER['REQUEST_URI']) && $uri = $_SERVER['REQUEST_URI']) {
		    if (($pos = strpos($uri, '?')) !== false) {
		        $uri = substr($uri, 0, $pos);
		    }
		    
		    if (pathinfo($uri, PATHINFO_EXTENSION) === 'php') {
		        $sname = str_replace('.php', '', $uri);
		        $sname = str_replace('Service', '', $sname);
		        $sname = trim($sname, '/');
		        $service_url = $sname . 'Service.php';
		    } else {
		        $sname = trim($uri, '/');
		        $service_url = $sname;
		    }
		    
		    $sname = StringHelper::toUnderscoreVariable($sname);
		} else {
		    $sname = 'test';
		    $service_url = 'TestService.php';
		}
		
		$sname = preg_replace('/\//', '/wsdl/', $sname, 1);
		
		$this->wsdl_path = $_SERVER['DOCUMENT_ROOT'] . $sname . '.wsdl';
		$this->wsdl_url = 'http://' . $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']. '/' . $sname . '.wsdl';
		$this->service_url = 'http://' . $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'] . '/' . $service_url;
		//print_r($this->wsdl_url);exit;
	}
	
	/**
	 * SoapDiscovery::getWSDL() Returns the WSDL of a class if the class is instantiable.
	 * 
	 * @return string
	 **/
	public function getWSDL() 
	{
		if (empty($this->service_name)) {
			throw new Exception('No service name.');
		}
		$headerWSDL = "<?xml version=\"1.0\" ?>\n";
		$headerWSDL.= "<definitions name=\"$this->service_name\" targetNamespace=\"urn:$this->service_name\" xmlns:wsdl=\"http://schemas.xmlsoap.org/wsdl/\" xmlns:soap=\"http://schemas.xmlsoap.org/wsdl/soap/\" xmlns:tns=\"urn:$this->service_name\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns=\"http://schemas.xmlsoap.org/wsdl/\">\n";
		$headerWSDL.= "<types xmlns=\"http://schemas.xmlsoap.org/wsdl/\" />\n";
        
		if (empty($this->class_name)) {
			throw new Exception('No class name.');
		}
		
		$class = new ReflectionClass($this->class_name);
		
		if (!$class->isInstantiable()) {
			throw new Exception('Class is not instantiable.');
		}
		
		$methods = $class->getMethods();
		
		$portTypeWSDL = '<portType name="'.$this->service_name.'Port">';
		$bindingWSDL = '<binding name="'.$this->service_name.'Binding" type="tns:'.$this->service_name."Port\">\n<soap:binding style=\"rpc\" transport=\"http://schemas.xmlsoap.org/soap/http\" />\n";
		//$serviceWSDL = '<service name="'.$this->service_name."\">\n<documentation />\n<port name=\"".$this->service_name.'Port" binding="tns:'.$this->service_name."Binding\"><soap:address location=\"http://".$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF']."\" />\n</port>\n</service>\n";
		$address_location = $this->service_url;
		$serviceWSDL = '<service name="'.$this->service_name."\">\n<documentation />\n<port name=\"".$this->service_name.'Port" binding="tns:'.$this->service_name."Binding\"><soap:address location=\"".$address_location."\" />\n</port>\n</service>\n";
		
		$messageWSDL = '';
		foreach ($methods as $method) {
			if ($method->isPublic() && !$method->isConstructor()) {
				$portTypeWSDL.= '<operation name="'.$method->getName()."\">\n".'<input message="tns:'.$method->getName()."Request\" />\n<output message=\"tns:".$method->getName()."Response\" />\n</operation>\n";
				$bindingWSDL.= '<operation name="'.$method->getName()."\">\n".'<soap:operation soapAction="urn:'.$this->service_name.'#'.$this->class_name.'#'.$method->getName()."\" />\n<input><soap:body use=\"encoded\" namespace=\"urn:$this->service_name\" encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" />\n</input>\n<output>\n<soap:body use=\"encoded\" namespace=\"urn:$this->service_name\" encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" />\n</output>\n</operation>\n";
			    $messageWSDL.= '<message name="'.$method->getName()."Request\">\n";
				$parameters = $method->getParameters();
				foreach ($parameters as $parameter) {
					$messageWSDL.= '<part name="'.$parameter->getName()."\" type=\"xsd:string\" />\n";
				}
				$messageWSDL.= "</message>\n";
				$messageWSDL.= '<message name="'.$method->getName()."Response\">\n";
				$messageWSDL.= '<part name="'.$method->getName()."\" type=\"xsd:string\" />\n";
				$messageWSDL.= "</message>\n";
			}
		}
		$portTypeWSDL.= "</portType>\n";
		$bindingWSDL.= "</binding>\n";
		return sprintf('%s%s%s%s%s%s', $headerWSDL, $portTypeWSDL, $bindingWSDL, $serviceWSDL, $messageWSDL, '</definitions>');
	}
	
	/**
	 * SoapDiscovery::getDiscovery() Returns discovery of WSDL.
	 * 
	 * @return string
	 **/
	public function getDiscovery() {
		return "<?xml version=\"1.0\" ?>\n<disco:discovery xmlns:disco=\"http://schemas.xmlsoap.org/disco/\" xmlns:scl=\"http://schemas.xmlsoap.org/disco/scl/\">\n<scl:contractRef ref=\"http://".$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF']."?wsdl\" />\n</disco:discovery>";
	}
	
	/**
	 * create wsdl file
	 * 
	 * @param boolean $force
	 * @return boolean|string if true then return wsdl real url, not php url
	 */
	public function createWSDL($force=false)
	{
	    $filename = $this->wsdl_path;
	    if (!file_exists($filename) || $force) {
	        $dir = dirname($filename);
	        if (!file_exists($dir)) {
	            $res = mkdir($dir, '0755', true);
	            if (!$res) {
	                echo 'create dir failure:' . $dir;
	                exit;
	            }
	        }
	        
	        $str = $this->getWSDL();
	        if (!$len = file_put_contents($filename, $str)) {
	            return false;
	        }
	    }
	    
	    return $this->wsdl_url;
	}
	
	public function getUrl()
	{
	    return $this->wsdl_url;
	}
	
	
}

?>