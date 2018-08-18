<?php
namespace Wslim\Util;

/**
 * The StringHelper class.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
abstract class StringHelper
{
	const INCREMENT_STYLE_DASH = 'dash';
	const INCREMENT_STYLE_DEFAULT = 'default';

	/**
	 * Increment styles.
	 *
	 * @var    array
	 */
	protected static $incrementStyles = array(
		self::INCREMENT_STYLE_DASH => array(
			'#-(\d+)$#',
			'-%d'
		),
		self::INCREMENT_STYLE_DEFAULT => array(
			array('#\((\d+)\)$#', '#\(\d+\)$#'),
			array(' (%d)', '(%d)'),
		),
	);

	/**
	 * isEmptyString
	 *
	 * @param string $string
	 *
	 * @return  boolean
	 */
	public static function isEmpty($string)
	{
		if (is_array($string) || is_object($string))
		{
			return empty($string);
		}

		$string = (string) $string;

		return !(boolean) strlen($string);
	}

	/**
	 * isZero
	 *
	 * @param string $string
	 *
	 * @return  boolean
	 */
	public static function isZero($string)
	{
		return $string === '0' || $string === 0;
	}

	/**
	 * Quote a string.
	 *
	 * @param   string $string The string to quote.
	 * @param   array  $quote  The quote symbol.
	 *
	 * @return  string Quoted string.
	 */
	public static function quote($string, $quote = array('"', '"'))
	{
		$quote = (array) $quote;

		if (empty($quote[1]))
		{
			$quote[1] = $quote[0];
		}

		return strpos($string, $quote[0])!== 0 ? ($quote[0] . $string . $quote[1]) : $string;
	}

	/**
	 * Back quote a string.
	 *
	 * @param   string $string The string to quote.
	 *
	 * @return  string Quoted string.
	 */
	public static function backquote($string)
	{
		return static::quote($string, '`');
	}

	/**
	 * Parse variable and replace it. This method is a simple template engine.
	 *
	 * Example: The {{ foo.bar.yoo }} will be replace to value of `$data['foo']['bar']['yoo']`
	 *
	 * @param   string $string The template to replace.
	 * @param   array  $data   The data to find.
	 * @param   array  $tags   The variable tags.
	 *
	 * @return  string Replaced template.
	 */
	public static function parseVariable($string, $data = array(), $tags = array('{{', '}}'))
	{
		$defaultTags = array('{{', '}}');

		$tags = (array) $tags + $defaultTags;

		list($begin, $end) = $tags;

		$regex = preg_quote($begin) . '\s*(.+?)\s*' . preg_quote($end);

		return preg_replace_callback(
			chr(1) . $regex . chr(1),
			function($match) use ($data)
			{
				$return = ArrayHelper::getByPath($data, $match[1]);

				if (is_array($return) || is_object($return))
				{
					return print_r($return, 1);
				}
				else
				{
					return $return;
				}
			},
			$string
		);
	}

	/**
	 * Increments a trailing number in a string.
	 *
	 * Used to easily create distinct labels when copying objects. The method has the following styles:
	 *
	 * default: "Label" becomes "Label (2)"
	 * dash:    "Label" becomes "Label-2"
	 *
	 * @param   string   $string  The source string.
	 * @param   string   $style   The the style (default|dash).
	 * @param   integer  $n       If supplied, this number is used for the copy, otherwise it is the 'next' number.
	 *
	 * @return  string  The incremented string.
	 *
	 */
	public static function increment($string, $style = self::INCREMENT_STYLE_DEFAULT, $n = 0)
	{
		$styleSpec = isset(self::$incrementStyles[$style]) ? self::$incrementStyles[$style] : self::$incrementStyles['default'];

		// Regular expression search and replace patterns.
		if (is_array($styleSpec[0]))
		{
			$rxSearch = $styleSpec[0][0];
			$rxReplace = $styleSpec[0][1];
		}
		else
		{
			$rxSearch = $rxReplace = $styleSpec[0];
		}

		// New and old (existing) sprintf formats.
		if (is_array($styleSpec[1]))
		{
			$newFormat = $styleSpec[1][0];
			$oldFormat = $styleSpec[1][1];
		}
		else
		{
			$newFormat = $oldFormat = $styleSpec[1];
		}

		// Check if we are incrementing an existing pattern, or appending a new one.
		if (preg_match($rxSearch, $string, $matches))
		{
			$n = empty($n) ? ($matches[1] + 1) : $n;
			$string = preg_replace($rxReplace, sprintf($oldFormat, $n), $string);
		}
		else
		{
			$n = empty($n) ? 2 : $n;
			$string .= sprintf($newFormat, $n);
		}

		return $string;
	}

	/**
	 * at
	 *
	 * @param string $string
	 * @param int    $num
	 *
	 * @return  string
	 */
	public static function at($string, $num)
	{
		$num = (int) $num;

		if (Utf8String::strlen($string) < $num)
		{
			return null;
		}

		return Utf8String::substr($string, $num, 1);
	}

	/**
	 * remove spaces
	 *
	 * See: http://stackoverflow.com/questions/3760816/remove-new-lines-from-string
	 * And: http://stackoverflow.com/questions/9558110/php-remove-line-break-or-cr-lf-with-no-success
	 *
	 * @param string $string
	 *
	 * @return  string
	 */
	public static function collapseWhitespace($string)
	{
		$string = preg_replace('/\s\s+/', ' ', $string);

		return trim(preg_replace('/\s+/', ' ', $string));
	}

	/**
	 * string endsWith target
	 *
	 * @param string  $string
	 * @param string  $target
	 * @param boolean $caseSensitive
	 *
	 * @return  boolean
	 */
	public static function endsWith($string, $target, $caseSensitive = true)
	{
		$stringLength = Utf8String::strlen($string);
		$targetLength = Utf8String::strlen($target);

		if ($stringLength < $targetLength)
		{
			return false;
		}

		if (!$caseSensitive)
		{
			$string = strtolower($string);
			$target = strtolower($target);
		}

		$end = Utf8String::substr($string, -$targetLength);

		return $end === $target;
	}

	/**
	 * startsWith
	 *
	 * @param string  $string
	 * @param string  $target
	 * @param boolean $caseSensitive, default true
	 *
	 * @return  boolean
	 */
	public static function startsWith($string, $target, $caseSensitive = true)
	{
		if (!$caseSensitive)
		{
			$string = strtolower($string);
			$target = strtolower($target);
		}

		return strpos($string, $target) === 0;
	}
	
	/**
	 * Method to convert a string from camel case.
	 *
	 * This method offers two modes. Grouped allows for splitting on groups of uppercase characters as follows:
	 *
	 * "FooBarABCDef"            becomes  array("Foo", "Bar", "ABC", "Def")
	 * "JFooBar"                 becomes  array("J", "Foo", "Bar")
	 * "J001FooBar002"           becomes  array("J001", "Foo", "Bar002")
	 * "abcDef"                  becomes  array("abc", "Def")
	 * "abc_defGhi_Jkl"          becomes  array("abc_def", "Ghi_Jkl")
	 * "ThisIsA_NASAAstronaut"   becomes  array("This", "Is", "A_NASA", "Astronaut"))
	 * "JohnFitzgerald_Kennedy"  becomes  array("John", "Fitzgerald_Kennedy"))
	 *
	 * Non-grouped will split strings at each uppercase character.
	 *
	 * @param   string   $input    The string input (ASCII only).
	 * @param   boolean  $grouped  Optionally allows splitting on groups of uppercase characters.
	 *
	 * @return  string  The space separated string.
	 *
	 */
	public static function fromCamelCase($input, $grouped = false)
	{
	    return $grouped
	    ? preg_split('/(?<=[^A-Z_])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][^A-Z_])/x', $input)
	    : trim(preg_replace('#([A-Z])#', ' $1', $input));
	}
	
	/**
	 * implode
	 * @param  string $glue
	 * @param  string|array $data
	 * @return string
	 */
	static public function implode($glue, $data)
	{
	    if (is_array($data)) {
	        if (is_array(current($data))) {
	            foreach ($data as $k => $v) {
	                $data[$k] = implode($glue, $v);
	            }
	        }
	        
	        return implode($glue, $data);
	    }
	    return $data;
	}
	
	/**
	 * explode use preg_split
	 * 
	 * @param  mixed  $glue  string|array
	 * @param  mixed  $data  string|array
	 * @param  int    $limit
	 * @return array
	 */
	public static function explode($glue, $data, $limit = null)
	{
	    if (is_array($data)) {
	        $result = [];
	        foreach ($data as $k => $v) {
	            $result = array_merge($result, static::explode($glue, $v, $limit));
	        }
	        return $result;
	    } elseif (is_string($data)) {
	        if (is_array($glue)) {
	            $glue = implode('', $glue);
	        }
	        $data = preg_split('/[' . $glue . ']+/', $data, $limit);
	        $data = (array) $data;
	        $data = array_map(function ($v) { return trim($v); }, $data);
	    }
	    
	    return $data;
	}
	
	/**
	 * explode use preg_split and padding to array, make array is length.
	 * 
	 * @param  mixed  $glue  string|array
	 * @param  mixed  $value string|array
	 * @param  int    $length array length
	 * @return array
	 */
	public static function explodeWithPadding($glue, $data, $length = null, $padding=null)
	{
	    $array = static::explode($glue, $data, $length);
	    
	    if (count($array) < $length)
	    {
	        foreach (range(1, $length - count($array)) as $i)
	        {
	            array_push($array, $padding);
	        }
	    }
	    
	    return $array;
	}
	
	/**
	 * Separate a string by custom separator.
	 *
	 * @param   string  $input      The string input (ASCII only).
	 * @param   string  $separator  The separator to want to separate it.
	 *
	 * @return  string  The string be converted.
	 *
	 */
	public static function separate($input, $separator = '_')
	{
	    return $input = preg_replace('#[\s\-_\\\/]+#', $separator, $input);
	}
	
	/**
	 * Method to convert a string into dash separated form. from 'Aa_Bb Cc' to 'Aa-Bb-Cc'
	 *
	 * @param   string  $input  The string input (ASCII only).
	 *
	 * @return  string  The dash separated string.
	 *
	 */
	public static function toDashSeparated($input)
	{
	    // Convert spaces and underscores to dashes.
	    return static::separate($input, '-');
	}
	
	/**
	 * Method to convert a string into space separated form. from 'Aa_Bb Cc' to 'Aa Bb Cc'
	 *
	 * @param   string  $input  The string input (ASCII only).
	 *
	 * @return  string  The space separated string.
	 *
	 */
	public static function toSpaceSeparated($input)
	{
	    // Convert underscores and dashes to spaces.
	    return static::separate($input, ' ');
	}
	
	/**
	 * Method to convert a string into dot separated form. from 'Aa_Bb Cc' to 'Aa.Bb.Cc'
	 *
	 * @param   string  $input  The string input (ASCII only).
	 *
	 * @return  string  The dot separated string.
	 *
	 */
	public static function toDotSeparated($input)
	{
	    // Convert underscores and dashes to dots.
	    return static::separate($input, '.');
	}
	
	/**
	 * Method to convert a string into underscore separated form, from 'Aa-Bb Cc' to 'Aa_Bb_Cc'
	 *
	 * @param   string  $input  The string input (ASCII only).
	 * @return  string  The underscore separated string.
	 *
	 */
	public static function toUnderscoreSeparated($input)
	{
	    // Convert spaces and dashes to underscores.
	    return static::separate($input, '_');
	}
	
	/**
	 * Method to convert a string into camel case, example: aa_bb => AaBb
	 * 
	 * @param   string  $input  The string input (ASCII only).
	 *
	 * @return  string  The camel case string.
	 */
	public static function toCamelCase($input)
	{
	    // Convert words to uppercase and then remove spaces.
	    $input = self::toSpaceSeparated($input);
	    $input = ucwords($input);
	    $input = str_ireplace(' ', '', $input);
	    
	    return $input;
	}
	
	/**
	 * Method to convert a string into little camel variable form. example: from 'Aaa_Bbb' to 'aaaBbb'
	 *
	 * @param   string  $input  The string input (ASCII only).
	 *
	 * @return  string  The variable string.
	 */
	public static function toLittleCamelCase($input)
	{
	    // Remove dashes and underscores, then convert to camel case.
	    $input = self::toCamelCase($input);

	    // Remove leading digits.
	    $input = preg_replace('#^[0-9]+.*$#', '', $input);
	
	    $input = lcfirst($input);
	    
	    return $input;
	}
	
	/**
	 * convert into underscore variable, from [AaaBbb] to [aaa_bbb]
	 * @param  string $input
	 * @return string
	 */
	public static function toUnderscoreVariable($input)
	{
	    // from camel case to the underscores separated lower string.
	    $input = self::fromCamelCase($input, false);
	    $input = strtolower(self::toUnderscoreSeparated($input));
	    $input = str_replace('\\_', '\\', str_replace('/_', '/', $input));
	    return $input;
	}
	
	/**
	 * convert to underscore path, from '\path\AaaDir\BbbFile' to '/path/aaa_dir/bbb_file'
	 * 
	 * @param  string $input
	 * @return string
	 */
	public static function toUnderscorePath($input)
	{
	    $input = self::fromCamelCase($input, false);
	    $input = str_replace('\\', '/', $input);
	    $input = explode('/', $input);
	    $input = array_map(function ($v) {return str_replace(' ', '_', $v);}, $input);
	    $input = strtolower(implode('/', $input));
	    return $input;
	}
    	
	/**
	 * Convert to standard PSR-0/PSR-4 class name, example: aa_bb/cc_dd => AaBb\CcDd
	 *
	 * @param   string $class The class name string.
	 *
	 * @return  string Normalised class name.
	 *
	 */
	public static function toClassName($class)
	{
	    $class = trim($class, '\\/');
	
	    $class = str_replace(array('\\', '/', ':'), ' ', $class);
        
	    $class = str_replace(' ', '\\', ucwords($class));
	    
	    $class = str_replace('_', ' ', $class);
	    
	    $class = str_replace(' ', '', ucwords($class));
        
	    return $class;
	}
	
	/**
	 * get class short name, from '\\Controller\\User\\Login' to 'User/Login'. 
	 * separate by type, if not set, return last part
	 * 
	 * @param  string $class
	 * @param  string $type
	 * @return string
	 */
	static public function toClassShortName($class, $type='\\')
	{
	    if ($type !== '\\') {
	        $type = ucfirst(strtolower($type)) . '\\';
	    }
	    $class = str_replace('/', '\\', trim($class, '\\'));
	    $parts = explode($type, $class);
	    $item = array_pop($parts);
	    
	    $type = str_replace('\\', '', $type);
	    if ( $type && ($pos = strrpos($item, $type)) && ((strlen($item) - $pos) == strlen($type)) ) {
	        $item = substr($item, 0, $pos);
	    }
	    
	    return str_replace('\\', '/', $item);
	}
	
	/**
	 * get class last part name, from '\A\B\C' to 'C'
	 * @param  string $class
	 * @param  string $type
	 * @return string
	 */
	static public function toClassLastName($class, $type='')
	{
	    if ($type) {
	        $type = ucfirst(strtolower($type));
	    }
	    $class = str_replace('/', '\\', trim($class, '\\'));
	    $parts = explode('\\', $class);
	    $item = array_pop($parts);
	    
	    if ( $type && ($pos = strrpos($item, $type)) && ((strlen($item) - $pos) == strlen($type)) ) {
	        $item = substr($item, 0, $pos);
	    }
	    
	    return $item;
	}
	
	/**
	 * to array, from 'a, b, c' to ['a', 'b', 'c']
	 * @param  mixed  $input string|array
	 * @param  string $glue
	 * @param  string $dataType int|null
	 * @return array
	 */
	static public function toArray($input, $glue = ',', $dataType=null)
	{
	    $data = [];
	    if (is_array($input)) {
	        foreach ($input as $k => $v) {
	            if (is_numeric($v)) {
	                $data[] = $v;
	            } else {
	                if (is_array($v)) {
	                    $vv = static::toArray($v, $glue);
	                } else {
	                    $vv = static::explode($glue, $v);
	                }
	                if ($vv && is_array($vv)) {
	                    $data = array_merge($data, $vv);
	                }
	            }
	        }
	    } else {
	        $data = static::explode($glue, $input);
	        $data = (array) $data;
	    }
        
	    if ($data) {
	        if ($dataType === 'int' || $dataType === 'integer' ) {
	            $data = array_map(function ($v) { return intval($v); }, $data);
	        } else {
	            $data = array_map(function ($v) { return trim($v); }, $data);
	        }
	    }
        
        return $data;
	}
	
	/**
	 * to int array
	 * @param  mixed  $input
	 * @param  mixed  $glue
	 * @return array
	 */
	static public function toIntArray($input, $glue = ',')
	{
	    return static::toArray($input, $glue, 'int');
	}

	/**
	 * explode string to array, return all prefix array. 'a:b:c' => ['a', 'a:b']
	 * @param  string $name
	 * @param  string $separator
	 * @return string[]
	 */
	static public function explodeAllPrefixs($name, $separator=':')
	{
	    $parts = explode($separator, $name, -1);
	    $items = [];
	    
	    foreach ($parts as $part) {
	        if (count($items)) {
	            $items[] = end($items) . ':' . $part;
	        } else {
	            $items[] = $part;
	        }
	    }
	    
	    return $items;
	}
	
	/**
	 * 字符截取 支持UTF8/GBK
	 * @param  string $string
	 * @param  int    $length
	 * @param  string $dot
	 * @return string
	 */
	static public function str_cut($string, $length=4, $dot = '', $charset='utf-8')
	{
	    if(strtolower($charset) == 'utf-8') {
	        $length = $length * 2;   // 取其2倍为最长值
	    }
	    $strlen = strlen($string);
	    if($strlen <= $length) return $string;
	    $string = str_replace(array(' ','&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵',' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
	    $strcut = '';
	    if(strtolower($charset) == 'utf-8') {
	        //$length = intval($length-strlen($dot)-$length/3);
	        $n = $tn = $noc = 0;
	        while($n < strlen($string)) {
	            $t = ord($string[$n]);
	            if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
	                $tn = 1; $n++; $noc++;
	            } elseif(194 <= $t && $t <= 223) {
	                $tn = 2; $n += 2; $noc += 2;
	            } elseif(224 <= $t && $t <= 239) {
	                $tn = 3; $n += 3; $noc += 2;
	            } elseif(240 <= $t && $t <= 247) {
	                $tn = 4; $n += 4; $noc += 2;
	            } elseif(248 <= $t && $t <= 251) {
	                $tn = 5; $n += 5; $noc += 2;
	            } elseif($t == 252 || $t == 253) {
	                $tn = 6; $n += 6; $noc += 2;
	            } else {
	                $n++;
	            }
	            if($noc >= $length) {
	                break;
	            }
	        }
	        if($noc > $length) {
	            $n -= $tn;
	        }
	        $strcut = substr($string, 0, $n);
	        $strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
	    } else {
	        $dotlen = strlen($dot);
	        $maxi = $length - $dotlen - 1;
	        $current_str = '';
	        $search_arr = array('&',' ', '"', "'", '“', '”', '—', '<', '>', '·', '…','∵');
	        $replace_arr = array('&amp;','&nbsp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;',' ');
	        $search_flip = array_flip($search_arr);
	        for ($i = 0; $i < $maxi; $i++) {
	            $current_str = ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
	            if (in_array($current_str, $search_arr)) {
	                $key = $search_flip[$current_str];
	                $current_str = str_replace($search_arr[$key], $replace_arr[$key], $current_str);
	            }
	            $strcut .= $current_str;
	        }
	    }
	    return $strcut.$dot;
	}
	


}
