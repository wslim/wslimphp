<?php
namespace Wslim\Tool\Baidu;

class PushUri
{
    /**
     * uri push
     * 
     * result:
        {
            "remain":4999998,
            "success":2,
            "not_same_site":[],
            "not_valid":[]
        }

     * @param  mixed $urls array|string
     * @param  array $site_options ['site' => ..., 'token' => ...]
     * 
     * @return string json string
     */
    static public function push($urls, $site_options=null)
    {
        /*
        $urls = array(
            'http://www.example.com/1.html',
            'http://www.example.com/2.html',
        );
        */
        if (is_string($urls)) {
            $urls = str_replace(["\r\n", '<br>'], "\n", $urls);
        } else {
            $urls = implode("\n", $urls);
        }
        
        $api = sprintf('https://data.zz.baidu.com/urls?site=%s&token=%s', $site_options['site'], $site_options['token']);
        
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL     => $api,
            CURLOPT_POST    => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POSTFIELDS      => $urls,
            CURLOPT_HTTPHEADER      => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        echo $result;
    }
    
    
    
    
    
}