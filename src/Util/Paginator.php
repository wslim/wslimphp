<?php
namespace Wslim\Util;

/**
 * -----------------------------------------------------------------------------
 * Paginator
 * -----------------------------------------------------------------------------
 *
 * About $pattern (:num)
 * -----------
 * (:num) is our regex pattern to capture the page number and pass it to generate the pagination.
 * It is require to catch the page number properly
 *
 *  /page/(:num) , will capture page in this pattern http://xyz.com/page/252
 *
 *  page=(:num) , will capture the pattern http://xyz.com/?page=252
 *
 *  Any other regexp pattern will work also
 *
 * When a query url is set without the page number, automatically based on the page pattern, the page number will be added
 * i.e:
 *     $url = http://xyz.com/?q=boom
 *     $pattern = page=(:num)
 *
 *     the page number will be added as so at the end of the query
 *     http://xyz.com/?q=boom&page=2
 *
 *
 *
 * Example
 * With friendly url:
 * ------------------
 *      $url = "http://www.givemebeats.net/buy-beats/Hip-Hop-Rap/page/4/";
 *      $pattern = "/page/(:num)";
 *      $count = 225;
 *      $Paginator = new Paginator($url,$pattern);
 *      $Pagination = $Paginator($count);
 *      print($Pagination);
 *
 *
 * With non friendly url:
 * ------------------
 *      $url = "http://www.givemebeats.net/buy-beats/?genre=Hip-Hop-Rap&page=4";
 *      $pattern = "page=(:num)";
 *      $count = 225;
 *      $Paginator = new Paginator($url,$pattern);
 *      $Pagination = $Paginator($count);
 *      print($Pagination);
 *
 *
 * Quick way:
 * ---------
 *      Paginator::create($pattern,$count);
 *
 *
 * Major Methods
 * -----------------
 *
 * - __construct()          : Instantiate the class
 * - count($count)          : Set the count items. It is required so it create the proper page count etc
 * - pagesize($pagesize)    : count items to display in your results page. This count will allow it to properly count pages
 * - current()              : Get or Set the current page number
 * - setUrl($url,$pattern)  : Set the url that will be used to create the pagination. $pattern is a regex to catch the page number in the $url
 * - setShowStyle($style)   : Set show style, 111, 依次表示是否显示首尾页、上下一页、中间页
 * - setShowPages($pages)   : Set show pages, default 5
 * - setTitles(Prev,Next)   : Set the previous and next title
 * - toArray($count)        : Return the pagination in array. Use it if you want to use your own template to generate the pagination in HTML
 * - render($count)         : Return the pagination in HTML format
 *
 *
 *
 * Other methods to access and update data before rendering
 *
 * - pages()                : Return the count pages
 * - startCount()           : The start count.
 * - endCount()             : The end count
 * - sqlOffset()            : When using SQL query, you can use this method to give you the limit count like: 119,10 which will be used in "LIMIT 119,10"
 * - currentUrl()           : Return the full url of the current page including the page number
 * - prevUrl()              : Return the full url of the previous page including the page number
 * - nextUrl()              : Return the full url of the next page including the page number
 *
 */

/**
 * Paginator
 * functional usage for generate Paginator HTML code
 *
 * @package Pagon
 */
class Paginator
{
    const STATIC_URL               = '/page/(:num)';
    const DYNAMIC_URL              = 'page=(:num)';
    const NULL_URL                 = '#';
    
    /**
     * instances
     * @var static[]
     */
    static protected $instances = null;
    
    /**
     * Holds the template url
     *
     * @var string
     */
    protected $templateUrl = "";

    /**
     * options
     * @var array
     */
    protected $options = array(
        'url'               => null,
        // 页码url规则 a regex pattern that will match the url and extract the page number
        'url_rule'          => self::DYNAMIC_URL,
        
        // 显示设置
        'show_style'        => '111',   // 风格, 第一位表示显示首页尾页 第二位表示显示上一页下一页 第三位表示显示中间页码
        'show_pages'        => 5,       // 显示多少页码
        
        // 特殊链接的标题文字
        'first_title'       => '首页',    // 第一页标题
        'last_title'        => '末页',    // 最后一页标题
        'prev_title'        => '上一页',   // 前一页标题
        'next_title'        => '下一页',   // 后一页标题
        
        // 容器标签
        'container_tag'         => 'div',
        'container_properties'  => [
            'class'     => 'dataTables_paginate paging_bootstrap'   // bootstrap
        ],
        
        // 列表包围标签
        'list_tag'          => 'ul',
        'list_properties'   => [
            'class'     => 'pagination'
        ],
        // 列表项标签
        'item_tag'          => 'li',
    );
    
    /**
     * Create the Paginator with the Url::current. It's a shortcut to quickly build it with the request URI
     *
     * @param int    $count         - count items found
     * @param int    $pagesize      - count items per page
     * @param array  $options       - The options
     * 
     * $options = array( // <br>
     *      'url_rule'          => self::STATIC_URL, // <br>
            'show_style'        => '111',   // 风格, 第一位表示显示首页尾页 第二位表示只显示上一页下一页 第三位表示显示中间页码 <br>
            'show_pages'        => 5,       // 显示多少页码<br>
            'first_title'       => '首页',    // 第一页标题 <br>
            'last_title'        => '末页',    // 最后一页标题 <br>
            'prev_title'        => '上一页',   // 前一页标题 <br>
            'next_title'        => '下一页',   // 后一页标题 <br>
     * ) // <br>
     * 
     * @return static
     */
    static public function create($count = 0, $pagesize = 10, $options=array())
    {
        $key = md5(serialize($options));
        if (!isset(self::$instances[$key]) || !self::$instances[$key]) {
            self::$instances[$key] = new self($count, $pagesize, $options);
        }
        return self::$instances[$key];
    }
    
    /**
     * extract pager options from array
     * @param  array $data
     * @return array
     */
    static public function extractPagerOptions($data)
    {
        $options = [];
        if (is_array($data)) foreach ($data as $k=>$v) {
            if (in_array($k, ['show_style', 'show_pages', 'prev_title', 'next_title'])) {
                $options[$k] = $v;
            }
        }
        return $options;
    }
    
    /**
     * Constructor
     *
     * @param int    $count         - items count
     * @param int    $pagesize      - items number per page
     * @param array  $options       - options
     */
    public function __construct($count = 0, $pagesize = 10, $options=array())
    {
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
        if (!isset($this->options['url'])) {
            $this->options['url'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }
        $this->setUrl($this->options['url'], $this->options['url_rule']);
        $this->count($count);
        $this->pagesize($pagesize);
        if (isset($this->options['page']) && intval($this->options['page']) > 0) {
            $this->current(intval($this->options['page']));
        }
    }

    /**
     * Set the URL, automatically it will parse every thing to it
     *
     * @param string $url
     * @param string $pattern
     * @return static
     */
    public function setUrl($url, $pattern = "/page/(:num)")
    {
        $_pattern = str_replace("(:num)", "([0-9]+)", $pattern);
        preg_match("~$_pattern~i", $url, $m);
        /**
         * No match found.
         * We'll add the pagination in the url, so this way it can be ready for next pages.
         * This way a url http://xyz.com/?q=boom , becomes http://xyz.com/?q=boom&page=2
         */
        if (count($m) == 0) {
            $pag_ = str_replace("(:num)", 0, $pattern);

            // page pattern contain the equal sign, we'll add it to the query ?page=123
            if (strpos($pattern, "=") !== false) {
                if (strpos($url, "?") !== false) {
                    $url .= "&" . $pag_;
                } else {
                    $url .= "?" . $pag_;
                }
                return $this->setUrl($url, $pattern);
            } else if (strpos($pattern, "/") !== false) { //Friendly url : /page/123
                $url_arr = explode("?", $url, 2);
                $segment = $url_arr[0];
                $query   = isset($url_arr[1]) ? $url_arr[1] : '';
                if (strpos($url, "?") !== false) {
                    if (preg_match("/\/$/", $segment)) {
                        $url = $segment . (preg_replace("/^\//", "", $pag_));
                        $url .= ((preg_match("/\/$/", $pag_)) ? "" : "/") . "?{$query}";
                    } else {
                        $url = $segment . $pag_;
                        $url .= ((preg_match("/\/$/", $pag_)) ? "" : "/") . "?{$query}";
                    }
                } else {
                    if (preg_match("/\/$/", $segment)) {
                        $url .= (preg_replace("/^\//", "", $pag_));
                    } else {
                        $url .= $pag_;
                    }
                }
                return $this->setUrl($url, $pattern);
            }
        }
        $match = current($m);
        $last = end($m);
        $page = $last ? $last : 1;

        // TemplateUrl will be used to create all the page numbers 
        $this->templateUrl = str_replace($match, preg_replace("/[0-9]+/", "(#pageNumber)", $match), $url);
        $this->current($page);
        return $this;
    }

    /**
     * get or set the items count. It will be used to determined the size of the pagination set
     * 
     * @param  int $count
     * @return static
     */
    public function count($count = null)
    {
        if ($count === null) {
            return isset($this->options['count']) ? $this->options['count'] : 0;
        } else {
            $this->options['count'] = $count;
            return $this;
        }
    }

    /**
     * Set the items pagesize
     *
     * @param  int $pagesize
     * @return Paginator|int
     */
    public function pagesize($pagesize = null)
    {
        if ($pagesize === null) {
            return $this->options['pagesize'];
        } else {
            $this->options['pagesize'] = intval($pagesize);
            return $this;
        }
    }

    /**
     * Set the current page
     *
     * @param  int $page
     * @return static
     */
    public function current($page = null)
    {
        if ($page === null) {
            return $this->options['page'];
        } else {
            $this->options['page'] = $page;
            return $this;
        }
    }

    /**
     * Get the pagination start count
     *
     * @return int
     */
    public function startCount()
    {
        return (int)($this->pagesize() * ($this->current() - 1));
    }

    /**
     * Get the pagination end count
     *
     * @return int
     */
    public function endCount()
    {
        return (int)((($this->pagesize() - 1) * $this->current()) + $this->current());
    }

    /**
     * Return the offset for sql queries, specially
     *
     * @return string START,LIMIT
     *
     * @tip  : SQL tip. It's best to do two queries one with SELECT COUNT(*) FROM table WHERE X
     *       set the count()
     */
    public function sqlOffset()
    {
        return $this->startCount() . "," . $this->pagesize();
    }

    /**
     * Get the pages
     *
     * @return int
     */
    public function pages()
    {
        return @ceil($this->count() / $this->pagesize());
    }

    /**
     * Get the current page url
     *
     * @return string
     */
    public function currentUrl()
    {
        return $this->parseUrl($this->current());
    }

    /**
     * Get the previous page url if it exists
     *
     * @return string
     */
    public function prevUrl()
    {
        $prev = $this->current() - 1;
        return ($prev > 0 && $prev < $this->pages()) ? $this->parseUrl($prev) : "";
    }

    /**
     * Get the next page url if it exists
     *
     * @return string
     */
    public function nextUrl()
    {
        $next = $this->current() + 1;
        return ($next <= $this->pages()) ? $this->parseUrl($next) : "";
    }
    
    /**
     * set the navigation size
     *
     * @param  int $show_pages
     * @return static
     */
    public function setShowPages($show_pages = 5)
    {
        $this->options['show_pages'] = $show_pages;
        return $this;
    }
    
    /**
     * set the navigation style
     *
     * @param  string $show_style
     * @return static
     */
    public function setShowStyle($show_style = '111')
    {
        $this->options['show_style'] = $show_style;
        return $this;
    }
    
    /**
     * To set the previous and next title
     *
     * @param  string $prev : Prev | &laquo; | &larr;
     * @param  string $next : Next | &raquo; | &rarr;
     * @return static
     */
    public function setTitles($prev = "Prev", $next = "Next")
    {
        $this->options['prev_title'] = $prev;
        $this->options['next_title'] = $next;
        return $this;
    }
    
    /**
     * set option
     * @param string|array $name
     * @param mixed $value
     */
    public function setOption($name, $value=null)
    {
        if (is_array($name)) {
            $this->options = array_merge($this->options, $name);
        } else {
            $this->options[$name] = $value;
        }
    }
    
    /*******************************************************************************/
    /**
     * 携带的数据
     * @var array
     */
    private $data;
    
    /**
     * set rel data
     * @param array $data
     */
    public function setData(& $data)
    {
        $this->data = $data;
    }
    
    /**
     * get rel data, need first set
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * toArray() export the pagination into an array. This array can be used for your own template or for other usafe
     *
     * @param  int $count - the count Items found
     * @return array [
                'navigation'=> [...], 
                'count'     => ..,
                'pages'     => .., 
                'pagesize'  => .., 
                'current'   => ..,
                'summary'   => ..., // html summary
                'pagination'=> ..., // html li pages
            ]
     */
    public function toArray($count=0)
    {
        return [
            'navigation'=> $this->navigation($count),
            'count'     => $this->count(),
            'pages'     => $this->pages(),
            'pagesize'  => $this->pagesize(),
            'current'   => $this->current(),
            'summary'   => $this->summary(),
            'pagination'=> $this->render(),
        ];
    }
    
    /**
     * 
     * @param  integer $count
     * @return array   navigation items
     * 
     * @example 
     *     array(
     *          array(
     *                "page", // the page number
     *                "label", // the label for the page number
     *                "url", // the url
     *                "current" // bool  set if page is current or not
     *          ),
     *          ...
     *     )
     */
    public function navigation($count = 0)
    {
        $navigation = array();
        if ($count) {
            $this->count($count);
        }
        
        $pages      = $this->pages();
        $current    = $this->current();
        
        $options = & $this->options;
        if ($pages) {
            $show_pages   = isset($options['show_style'][2]) && $options['show_style'][2] ? (int)$options['show_pages'] : 0;
            
            $halfSet = @ceil($show_pages / 2);
            $start = 1;
            $end = $show_pages > 0 ? (($pages < $show_pages) ? $pages : $show_pages) : $pages;
            
            $showFirstLastNav = isset($options['show_style'][0]) ? (bool) $options['show_style'][0] : false;
            $showPrevNextNav  = isset($options['show_style'][1]) ? (bool) $options['show_style'][1] : false;
            $showPrevNextNav = ($pages > $show_pages) ? true : false;
            
            if ($show_pages > 0 && $current >= $show_pages) {
                $start = $current - $show_pages + $halfSet + 1;
                $end = $current + $halfSet - 1;
            }
            
            if ($end > $pages) {
                $s = $pages - $show_pages;
                $start = $s ? $s : 1;
                $end = $pages;
            }
            
            // first
            if ($showFirstLastNav) {
                $navigation[] = array(
                    "page"      => 1,
                    "label"     => $this->options['first_title'],
                    "url"       => $this->parseUrl(1),
                    "current"   => false,
                    "class"     => 'first'
                );
            }
            
            // Previous   
            $prev = $current - 1;
            if ($current >= $show_pages && $prev > 0 && $showPrevNextNav) {
                $navigation[] = array(
                    "page"      => $prev,
                    "label"     => $this->options['prev_title'],
                    "url"       => $this->parseUrl($prev),
                    "current"   => false,
                    "class"     => 'prev'
                );
            }
            
            // display pages
            if ($show_pages > 0) {
                for ($i = $start; $i <= $end; $i++) {
                    $navigation[] = array(
                        "page"      => $i,
                        "label"     => $i,
                        "url"       => $this->parseUrl($i),
                        "current"   => ($i == $current) ? true : false,
                        "class"     => ''
                    );
                }
            }
            
            // Next 
            $next = $current + 1;
            if ($next <= $pages && $showPrevNextNav) {
                $navigation[] = array(
                    "page"      => $next,
                    "label"     => $this->options['next_title'],
                    "url"       => $this->parseUrl($next),
                    "current"   => false,
                    "class"     => 'next'
                );
            }
            
            // last
            if ($showFirstLastNav) {
                $navigation[] = array(
                    "page"      => $pages,
                    "label"     => $this->options['last_title'],
                    "url"       => $this->parseUrl($pages),
                    "current"   => false,
                    "class"     => 'last'
                );
            }
        }
        
        return $navigation;
    }
    
    /**
     * 获取分页摘要信息
     * @return string
     */
    public function summary()
    {
        return '第' . $this->current() . '页/共' . $this->pages() . '页';
    }

    /**
     * Render the paginator in HTML format
     *
     * @param  int    $count             - The count Items
     * @return string
     * <code>
     * <div class="pagination">
     *      <ul>
     *          <li>1</li>
     *          <li class="active">2</li>
     *          <li>3</li>
     *      <ul>
     * </div>
     * </code>
     */
    public function render($count = 0)
    {
        $pagination = "";
        $items = $this->navigation($count);
        if ($items) foreach ($items as $page) {
            $pagination .= $this->item($page['page'], $page['url'], $page['label'], $page['class'], $page['current'], false);
        }
        
        $listPropertiesHtml = "";
        foreach ($this->options['list_properties'] as $k => $v) {
            $listPropertiesHtml .= " $k=\"$v\"";
        }
        $html = "<{$this->options['list_tag']}$listPropertiesHtml>{$pagination}</{$this->options['list_tag']}>";
        
        if ($this->options['container_tag']) {
            $containerPropertiesHtml = "";
            foreach ($this->options['container_properties'] as $k => $v) {
                $containerPropertiesHtml .= " $k=\"$v\"";
            }
            $html = "<div$containerPropertiesHtml>
                    $html
                    </div>";
        }
        
        return $html;
    }

    /**
     * Parse a page number in the template url
     *
     * @param int $pageNumber
     * @return string
     */
    protected function parseUrl($pageNumber)
    {
        return str_replace("(#pageNumber)", $pageNumber, $this->templateUrl);
    }

    /**
     * To create an <a href> link
     *
     * @param string $url
     * @param string $txt
     * @return string
     */
    protected function link($page, $url, $txt, $classHtml, $isActive)
    {
        if ($isActive) {
            $url = "javascript:void(0);";
        }
        return '<a href="' . $url . '"' . $classHtml . ' data-id="' . $page . '">' . $txt . '</a>';
    }

    /**
     * Create a wrap list, ie: <li></li>
     *
     * @param string $html
     * @param string $className html class
     * @param bool   $isActive   - To set the active class in this element
     * @param bool   $isDisabled - To set the disabled class in this element
     * @return string
     */
    protected function item($page, $url, $txt, $className, $isActive = false, $isDisabled = false)
    {
        $class = !empty($className) ? array($className) : array();
        $isActive && $class[] = 'active';
        $isDisabled && $class[] = 'disabled';
        $classHtml = $class ? ' class="' . join(' ', $class) . '"' : "";

        if ($this->options['item_tag'] &&  $this->options['item_tag'] != 'a') {
            $str = "<{$this->options['item_tag']}" . $classHtml . ">" . $this->link($page, $url, $txt, '' , $isActive) . "</{$this->options['item_tag']}>\n";
        } else {
            $str = $this->link($page, $url, $txt, $classHtml, $isActive);
        }
        return $str;
    }

    /**
     * Render to HTML
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}