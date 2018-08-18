# Cache

Cache package provides an simple class to easily store and fetch cache files.  

## Getting Started

Create a cache object and store data.

``` php
use Wslim\Cache\Cache;

$data = array('sakura');

$cache = new Cache;

$cache->set('flower', $data);
```

Then we can get this data by same key.

``` php
$data = $cache->get('flower');
```

### Auto Fetch Data By Closure

Using call method to auto detect is cache exists or not. 

``` php
$data = $cache->call('flower', function() {
    return array('sakura');
});
```

It is same as this code:

``` php
if (!$cache->has('flower')) {
    $cache->set('flower', array('sakura'));
}

$data = $cache->get('flower');
```

## Storage Type

### RuntimeStorage

The default cache storage is `RuntimeStorage`, it means our data only keep in runtime but will not save as files.

### FileStorage

Create a cache with `FileStorage` and set a path to store files.

``` php
use Wslim\Cache\Cache;

$config = [
    'storage' = 'file',
    'path => 'your/cache/path'
];

$cache = new Cache($config);

$cache->set('flower', array('sakura'));
```

The file will store at `your/cache/path/flower.php`, and the data will be serialized string.

```
a:1:{i:0;s:6:"sakura";}
```

### Available Storage

- RuntimeStorage
- FileStorage
- MemcachedStorage
- RedisStorage
- XcacheStorage
- NullStorage


## Storage Options

### format
Setting data format, it can be: null, string, json, serialize, csv, tsv, xml.

``` php
use Wslim\Cache\Cache;

$config = [
    'storage'   => 'file',
    'path       => 'your/cache/path',
    'format'    => 'json'
];

$cache = new Cache($config);

$cache->set('flower', array('sakura'));
```

The stored cache file is:

```
{"flower":"sakura"}
```

### other option

If your cache folder are exposure on web environment, we have to make our cache files unable to access. The argument 3 
 of `FileStorage` is use to deny access.
  
``` php
$config = [
    'storage'   => 'file',
    'path       => 'your/cache/path',
    'format'    => 'serialize',
    'prefix'    => 'mygroup/',
    'file_ext'  => 'php',
    'deny_access'    => true,
];

$cache = new Cache();

$cache->set('flower', array('sakura'));
```

The stored file will be a PHP file with code to deny access:

`your/cache/path/mygroup/flower.php`

``` php
<?php die("Access Deny"); ?>a:1:{i:0;s:6:"sakura";}
```



### Full Page Cache

Sometimes we want to store whole html as static page cache.
 
``` php
$config = [
    'storage'   => 'file',
    'path       => 'your/cache/path',
    'format'    => 'string',
];
$cache = new Cache();

$url = 'http://mysite.com/foo/bar/baz';

if ($cache->has($url))
{
    $html = $cache->get($url);
    
    exit();
}

$view = new View;

$html = $view->render();

$cache->set($url, $html);

echo $html;
```


## TODO

Waiting for [PSR-6](https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md) Compatible and will rewrite for it. 
