<?php
namespace Wslim\Soap;

use \SoapServer;

/**
 * Soap webservice
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Service
{
    protected $options = [
        'soap_version' => SOAP_1_2
    ];
    
    /**
     * webservice handle class
     *
     * @param string|object $class_name
     * @param boolean  $force
     * @throws \Exception
     * @return string|void if ?wsdl then return xml string, else return handle void
     */
    public function handleClass($class_name, $force=false)
    {
        if (is_string($class_name)) {
            $entity_class = str_replace('Service', '', $class_name);
        } elseif (is_object($class_name)) {
            $entity_class = get_class($class_name);
        } else {
            throw new \Exception('param $class_name is not classname or object');
        }
        
        $disco = new SoapDiscovery($entity_class);
        
        if (isset($_SERVER['QUERY_STRING']) && strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
            header("Content-type: text/xml");
            echo $disco->getWSDL();
        } else {
            $options = [
                'soap_version' => $this->options['soap_version'],
            ];
            $wsdl_url = $disco->createWSDL();
            
            $servidorSoap = new SoapServer($wsdl_url, $options);
            $servidorSoap->setClass($entity_class);
            $servidorSoap->handle();
        }
    }
    
    /**
     * webservice handle
     * 
     * @param boolean $force
     * @throws \Exception
     * @return string|void if ?wsdl then return xml string, else return handle void
     */
    public function handle($force=false)
    {
        return $this->handleClass(get_called_class(), $force);
    }
    
}
