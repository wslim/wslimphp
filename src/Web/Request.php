<?php
namespace Wslim\Web;

use Wslim\Common\RequestInterface;
use Wslim\Common\DataFormatter\XmlFormatter;

/**
 * web request
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Request extends \Slim\Http\Request implements RequestInterface
{
    /**
     * post params
     * @var array
     */
    protected $postParams;
    
    /**
     * Known handled content types
     *
     * @var array
     */
    protected static $knownContentTypes = array(
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
        'text/plain',
    );
    
    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @return string 'text/html' or other
     */
    public function detectContentType()
    {
        $acceptHeader = $this->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), static::$knownContentTypes);
        
        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }
        
        // handle +json and +xml specially
        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, static::$knownContentTypes)) {
                return $mediaType;
            }
        }
        
        return 'text/html';
    }
    
    /**
     * get post input, return value when enctype is not multipart/form-data
     *
     * form enctype: application/x-www-form-urlencoded, multipart/form-data, text/plain
     * 
     * @return array
     */
    static public function getPostInputInfo()
    {
        if ($input = file_get_contents('php://input')) {
            if (strpos(trim($input), '{') === 0 || strpos(trim($input), '[') === 0) {
                $posts = json_decode($input, true);
            } elseif (strpos(trim($input), '<') === 0) {
                $posts = XmlFormatter::decodeElement($input, false);
            } else {
                parse_str($input, $posts);
            }
            
            return [$input, $posts];
        }
        return [];
    }
    
    /**
     * get post params, contain $_POST/php://input parsed array
     * @return array
     */
    public function getPostParams()
    {
        if (is_array($this->postParams)) {
            return $this->postParams;
        }
        
        //$posts = $this->getParsedBody();  // 可能是 xml 对象
        if (($input = static::getPostInputInfo())) {
            $this->postParams = $input[1];
        } else {
            $this->postParams = $_POST;
        }
        
        return $this->postParams;
    }
    
    /**
     * get request params, contain $_GET/$_POST/php://input
     * @return array
     */
    public function getRequestParams()
    {
        $gets = $this->getQueryParams() ?: [];
        if ($posts = $this->getPostParams()) {
            $gets = array_merge($gets, $posts);
        }
        return $gets;
    }
    
    /**
     * get request param, $key can be: name|get:name|post:name|get:*|post:*|*
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function input($name=null, $default=null)
    {
        if (!$name) {
            return $this->getRequestParams();
        } else {
            $method = $name2 = null;
            $name = str_replace('.', ':', $name);
            if (($pos = strpos($name, ':')) !== false) {
                $names = explode(':', $name);
                $method = strtoupper(trim($names[0]));
                if (in_array($method, ['GET', 'POST', 'OPTION', 'DELETE', 'PUT'])) {
                    $name = trim($names[1]);
                    if (isset($names[2])) {
                        $name2 = trim($names[2]);
                    }
                } else {
                    $method = null;
                    $name = trim($names[0]);
                    $name2 = trim($names[1]);
                }
            } elseif (strtoupper($name) == 'GET' || strtoupper($name) == 'POST') {
                $method = strtoupper($name);
                $name = null;
            }
            
            if ($name === '*') {
                $name = null;
            }
            
            switch ($method) {
                case 'GET':
                    if ($name2) {
                        return isset($this->getQueryParam()[$name][$name2]) ? $this->getQueryParam()[$name][$name2] : $default;
                    } else {
                        return $name ? $this->getQueryParam($name, $default) : $this->getQueryParams();
                    }
                    break;
                case 'POST':
                    if ($name2) {
                        return isset($this->getPostParams()[$name][$name2]) ? $this->getPostParams()[$name][$name2] : $default;
                    } else {
                        return $name ? (isset($this->getPostParams()[$name]) ? $this->getPostParams()[$name] : $default) : $this->getPostParams();
                    }
                    break;
                default:
                    if ($name2) {
                        return isset($this->getRequestParams()[$name][$name2]) ? $this->getRequestParams()[$name][$name2] : $default;
                    } else {
                        return $name ? (isset($this->getRequestParams()[$name]) ? $this->getRequestParams()[$name] : $default) : $this->getRequestParams();
                    }
                    break;
            }
        }
    }
    
}
