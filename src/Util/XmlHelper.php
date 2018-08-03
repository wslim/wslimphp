<?php 
namespace Wslim\Util;

use SimpleXMLElement;
use Traversable;

class XmlHelper
{
    /**
     * root element
     * @var string
     */
    static public $rootElement = 'xml';
    
    /**
     * set root element key
     * @param string $root
     */
    static public function setRoot($root)
    {
        static::$rootElement = $root;
    }
    
    /**
     * The main function for converting to an XML document.
     *
     * @param  array|Traversable|string $data
     * @return string            XML
     */
    static public function encode($data)
    {
        return static::encodeElement($data);
    }
    
    /**
     * The main function for converting to an XML document.
     *
     * @param  array|Traversable|string $data
     * @param  string   $root   if string then add "<?xml version='1.0' encoding='utf-8'?>" . <$root>
     * @return string   XML
     */
    static public function encodeElement($data, $root='xml')
    {
        if (!$data) {
            $data = [];
        } elseif (is_string($data)) {
            return $data;
        }
        
        assert('is_array($data) || $data instanceof Traversable');
        
        $output = '';
        if ($data) foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                // Convert the key to a valid string
                $key = "unknownNode_". (string) $key;
            }
            
            // Delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);
            
            if (is_array($val)) {
                $output .= '<' . $key . '>';
                $output .= self::encodeElement($val, null);
                $output .= '</' . $key . '>';
            } else {
                
                if (is_numeric($val)){
                    $output .= "<".$key.">".$val."</".$key.">";
                }else{
                    $val = str_replace('&', '&amp;', print_r($val, true));
                    $output .= "<". $key . "><![CDATA[".$val."]]></" . $key . ">";
                }
            }
        }
        if ($output) {
            $output = PHP_EOL . $output . PHP_EOL;
        }
        if ($root) {
            $output = "<?xml version='1.0' encoding='utf-8'?>" . PHP_EOL . "<$root>$output</$root>";
        }
        
        return $output;
    }
    
    /**
     * from xml string to array, use single entity parse
     * @param  string  $str
     * @return array
     */
    static public function decode($str)
    {
        return static::decodeElement($str, true);
    }
    
    /**
     * from xml string to array, use single entity parse
     * @param  string  $str
     * @param  boolean $hasRoot str is contain root element
     * @return array
     */
    static public function decodeElement($str, $hasRoot=true)
    {
        if (is_array($str)) {
            return $str;
        }
        
        if (!$hasRoot && strpos($str, '<?xml') !== 0 && strpos($str, '<xml') !== 0) {
            $str = "<?xml version='1.0' encoding='utf-8'?><xml>" . $str . "</xml>";
        }
        
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        // 将xml转为array
        return json_decode(json_encode(simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
    
    /**
     * @param  SimpleXMLElement  $node Node to append data to, will be modified in place
     * @param  array|Traversable $data
     * @return SimpleXMLElement  The modified node, for chaining
     */
    static private function addDataToNode(\SimpleXMLElement $node, $data)
    {
        assert('is_array($data) || $node instanceof Traversable');
        
        if (is_string($data)) {
            $data = static::decodeElement($data, false);
        }
        
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                // Convert the key to a valid string
                $key = "unknownNode_". (string) $key;
            }
    
            // Delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);
    
            if (is_array($value)) {
                $child = $node->addChild($key);
                self::addDataToNode($child, $value);
            } else {
                $value = str_replace('&', '&amp;', print_r($value, true));
                $node->addChild($key, $value);
            }
        }
    
        return $node;
    }

    /**
     * append data to rootString
     * @param  string|array $root
     * @param  string|array $data
     * @param  array        $options
     * @return string
     */
    static public function append($root, $data, $options=[])
    {
        $rootString = static::root($root);
        $rootNode = simplexml_load_string($rootString);
        
        return static::addDataToNode($rootNode, $data)->asXML();
    }
    
    /**
     * return root string
     * @param  mixed  $data
     * @return string
     */
    static public function root($data=null)
    {
        if (!$data) {
            $data = static::encodeElement([], 'xml');
        } elseif (is_array($data)) {
            $data = static::encodeElement($data, null);
        } elseif (strpos($data, '<') === false) {
            $data = static::encodeElement("<$data></$data>", null);
        }
        $node = simplexml_load_string($data);
        return $node->asXML();
    }
    
    /**
     * wrap element
     * @param string|array $data
     * @param string       $wrap
     * @return mixed
     */
    static public function wrap($data, $wrap='xml')
    {
        if (is_string($data)) {
            return '<' . $wrap . '>' . $data . '</' . $wrap . '>';
        } elseif (is_array($data)) {
            return [$wrap => $data];
        }
        return $data;
    }
    
    /**
     * from string to SimpleXMLElement
     * @param  string|array $data
     * @return SimpleXMLElement
     */
    static public function toXml($data)
    {
        if (is_array($data)) {
            $data = static::encode($data);
        }
        return simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
    }
}

