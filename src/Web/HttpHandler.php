<?php
namespace Wslim\Web;

use Psr\Http\Message\ServerRequestInterface;

class HttpHandler
{
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
     * @param ServerRequestInterface $request
     * @return string 'text/html' or other
     */
    static public function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
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
    
}