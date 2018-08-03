<?php
namespace Wslim\Util;

use Wslim\Util\File\FileComparatorInterface;
use Wslim\Util\File\RecursiveDirectoryIterator;

// need if version < php 5.4
if (!class_exists('CallbackFilterIterator')) {
	include_once __DIR__ . '/File/CallbackFilterIterator.php';
}

abstract class FileHelper
{
    const PATH_ABSOLUTE = 1;
    
    const PATH_RELATIVE = 2;
    
    const PATH_BASENAME = 4;
    
    /**
     * make dir
     * @param  string $path
     * @param  number $mode like 0755
     * @return boolean
     */
    static public function mkdir($path, $mode = 0755)
    {
        return Dir::create($path, $mode);
    }
    
	/**
	 * copy
	 *
	 * @param string  $src
	 * @param string  $dest
	 * @param bool    $force
	 *
	 * @return  bool
	 */
	public static function copy($src, $dest, $force = false)
	{
		if (is_dir($src)) {
			Dir::copy($src, $dest, $force);
		} elseif (is_file($src)) {
			File::copy($src, $dest, $force);
		}

		return true;
	}

	/**
	 * move
	 *
	 * @param string  $src
	 * @param string  $dest
	 * @param bool    $force
	 *
	 * @return  bool
	 */
	public static function move($src, $dest, $force = false)
	{
		if (is_dir($src))
		{
			Dir::move($src, $dest, $force);
		}
		elseif (is_file($src))
		{
			File::move($src, $dest, $force);
		}

		return true;
	}

	/**
	 * delete
	 *
	 * @param string $path
	 *
	 * @return  bool
	 */
	public static function delete($path)
	{
		if (is_dir($path)) {
			Dir::delete($path);
		} elseif (is_file($path)) {
			File::delete($path);
		}

		return true;
	}
	
	/**
	 * 
	 * @param  \CallbackFilterIterator $files
	 * @param  int $pathType
	 * @return string[]
	 */
	static protected function toArray($files, $pathType = self::PATH_ABSOLUTE, $path=null)
	{
	    $items = [];
	    /** @var $file \SplFileInfo */
	    foreach ($files as $file) {
	        switch ($pathType)
	        {
	            case ($pathType === self::PATH_BASENAME):
	                $name = $file->getBasename();
	                break;
	                
	            case ($pathType === static::PATH_RELATIVE):
	                if (!$path) {
	                    $name = $file->getFilename();
	                } else {
	                    $pathLength = strlen($path);
    	                $name = $file->getRealPath();
    	                $name = trim(substr($name, $pathLength), DIRECTORY_SEPARATOR);
	                }
	                break;
	                
	            case ($pathType === static::PATH_ABSOLUTE):
	            default:
	                $name = $file->getPathname();
	                break;
	        }
	        
	        $items[] = $name;
	    }
	    
	    return $items;
	}

	/**
	 * files
	 *
	 * @param   string  $path
	 * @param   bool    $recursive
	 * @param   bool    $toArray
	 * @param   int     $pathType
	 * @return  \CallbackFilterIterator|array
	 */
	public static function files($path, $recursive = false, $toArray = false, $pathType = self::PATH_ABSOLUTE)
	{
		/**
		 * Files callback
		 *
		 * @param \SplFileInfo                $current  Current item's value
		 * @param string                      $key      Current item's key
		 * @param \RecursiveDirectoryIterator $iterator Iterator being filtered
		 *
		 * @return boolean   TRUE to accept the current item, FALSE otherwise
		 */
		$callback = function ($current, $key, $iterator) {
			return $current->isFile();
		};

		$files = static::findByCallback($path, $callback, $recursive);
		
		if ($toArray) {
		    $files = static::toArray($files, $pathType);
		}
		
		return $files;
	}

	/**
	 * folders
	 *
	 * @param   string  $path
	 * @param   bool    $recursive
	 * @param   boolean $toArray
	 * @param   int     $pathType
	 *
	 * @return  \CallbackFilterIterator
	 */
	public static function dirs($path, $recursive = false, $toArray = false, $pathType = self::PATH_ABSOLUTE)
	{
		/**
		 * Files callback
		 *
		 * @param \SplFileInfo                $current  Current item's value
		 * @param string                      $key      Current item's key
		 * @param \RecursiveDirectoryIterator $iterator Iterator being filtered
		 *
		 * @return boolean   TRUE to accept the current item, FALSE otherwise
		 */
		$callback = function ($current, $key, $iterator) use ($path, $recursive)
		{
			if ($recursive)
			{
				// Ignore self
				if ($iterator->getRealPath() == static::cleanPath($path))
				{
					return false;
				}

				// If set to recursive, every returned folder name will include a dot (.),
				// so we can't using isDot() to detect folder.
				return $iterator->isDir() && ($iterator->getBasename() != '..');
			}
			else
			{
				return $iterator->isDir() && !$iterator->isDot();
			}
		};

		$files = static::findByCallback($path, $callback, $recursive);
		
		if ($toArray) {
		    $files = static::toArray($files, $pathType);
		}
	    
	    return $files;
	}

	/**
	 * items
	 *
	 * @param   string  $path
	 * @param   bool    $recursive
	 * @param   boolean $toArray
	 * @param   int     $pathType
	 * 
	 * @return  \CallbackFilterIterator
	 */
	public static function items($path, $recursive = false, $toArray = false, $pathType = self::PATH_ABSOLUTE)
	{
		/**
		 * Files callback
		 *
		 * @param \SplFileInfo                $current  Current item's value
		 * @param string                      $key      Current item's key
		 * @param \RecursiveDirectoryIterator $iterator Iterator being filtered
		 *
		 * @return boolean   TRUE to accept the current item, FALSE otherwise
		 */
		$callback = function ($current, $key, $iterator) use ($path, $recursive)
		{
			if ($recursive)
			{
				// Ignore self
			    if ($iterator->getRealPath() == static::cleanPath($path))
				{
					return false;
				}

				// If set to recursive, every returned folder name will include a dot (.),
				// so we can't using isDot() to detect folder.
				return ($iterator->getBasename() != '..');
			}
			else
			{
				return !$iterator->isDot();
			}
		};
        
		$files = static::findByCallback($path, $callback, $recursive);
		
		if ($toArray) {
		    $files = static::toArray($files, $pathType = self::PATH_ABSOLUTE);
		}
		
		return $files;
	}
	
	/**
	 * Lists folder in format suitable for tree display.
	 *
	 * @param   string   $path      The path of the folder to read.
	 * @param   integer  $maxLevel  The maximum number of levels to recursively read, defaults to three.
	 * @param   integer  $level     The current level, optional.
	 * @param   integer  $parent    Unique identifier of the parent folder, if any.
	 *
	 * @return  array  Folders in the given folder.
	 */
	public static function dirTree($path, $maxLevel = 3, $level = 0, $parent = 0)
	{
	    $dirs = array();
	    
	    static $index;
	    static $base;
	    
	    if ($level == 0) {
	        $index = 0;
	        $base = static::cleanPath($path);
	    }
	    
	    if ($level < $maxLevel)
	    {
	        $folders = static::dirs($path, false, false);
	        
	        sort($folders);
	        
	        // First path, index foldernames
	        foreach ($folders as $name) {
	            $id = ++$index;
	            $fullName = static::cleanPath($path . '/' . $name);
	            
	            $dirs[] = array(
	                'id' => $id,
	                'parent' => $parent,
	                'name' => $name,
	                'fullname' => $fullName,
	                'relative' => trim(str_replace($base, '', $fullName), DIRECTORY_SEPARATOR)
	            );
	            
	            $dirs2 = self::dirTree($fullName, $maxLevel, $level + 1, $id);
	            
	            $dirs = array_merge($dirs, $dirs2);
	        }
	    }
	    
	    return $dirs;
	}

	/**
	 * Find one file and return.
	 *
	 * @param  string   $path         The directory path.
	 * @param  mixed    $condition    Finding condition, that can be a string, a regex or a callback function.
	 *                                Callback example:
	 *                                <code>
	 *                                function($current, $key, $iterator)
	 *                                {
	 *                                return @preg_match('^Foo', $current->getFilename())  && ! $iterator->isDot();
	 *                                }
	 *                                </code>
	 * @param  boolean  $recursive    True to resursive.
	 *
	 * @return  \SplFileInfo  Finded file info object.
	 *
	 */
	public static function findOne($path, $condition, $recursive = false)
	{
		$iterator = new \LimitIterator(static::find($path, $condition, $recursive), 0, 1);

		$iterator->rewind();

		return $iterator->current();
	}

	/**
	 * Find all files which matches condition.
	 *
	 * @param  string   $path       The directory path.
	 * @param  mixed    $condition  Finding condition, that can be a string, a regex or a callback function.
	 *                              Callback example:
	 *                              <code>
	 *                              function($current, $key, $iterator)
	 *                              {
	 *                              return @preg_match('^Foo', $current->getFilename())  && ! $iterator->isDot();
	 *                              }
	 *                              </code>
	 * @param  boolean  $recursive  True to resursive.
	 * @param  boolean  $toArray    True to convert iterator to array.
	 *
	 * @return  \CallbackFilterIterator  Found files or paths iterator.
	 *
	 */
	public static function find($path, $condition, $recursive = false, $toArray = false)
	{
		// If conditions is string or array, we make it to regex.
		if (!($condition instanceof \Closure) && !($condition instanceof FileComparatorInterface))
		{
			if (is_array($condition))
			{
				$condition = '/(' . implode('|', $condition) . ')/';
			}
			else
			{
				$condition = '/' . (string) $condition . '/';
			}

			/**
			 * Files callback
			 *
			 * @param \SplFileInfo                $current  Current item's value
			 * @param string                      $key      Current item's key
			 * @param \RecursiveDirectoryIterator $iterator Iterator being filtered
			 *
			 * @return boolean   TRUE to accept the current item, FALSE otherwise
			 */
			$condition = function ($current, $key, $iterator) use ($condition)
			{
				return @preg_match($condition, $iterator->getFilename()) && !$iterator->isDot();
			};
		}
		// If condition is compare object, wrap it with callback.
		elseif ($condition instanceof FileComparatorInterface)
		{
			/**
			 * Files callback
			 *
			 * @param \SplFileInfo                $current  Current item's value
			 * @param string                      $key      Current item's key
			 * @param \RecursiveDirectoryIterator $iterator Iterator being filtered
			 *
			 * @return boolean   TRUE to accept the current item, FALSE otherwise
			 */
			$condition = function ($current, $key, $iterator) use ($condition)
			{
				return $condition->compare($current, $key, $iterator);
			};
		}

		return static::findByCallback($path, $condition, $recursive, $toArray);
	}

	/**
	 * Using a closure function to filter file.
	 *
	 * Reference: http://www.php.net/manual/en/class.callbackfilteriterator.php
	 *
	 * @param  string   $path      The directory path.
	 * @param  \Closure $callback  A callback function to filter file.
	 * @param  boolean  $recursive True to recursive.
	 * @param  boolean  $toArray   True to convert iterator to array.
	 *
	 * @return  \CallbackFilterIterator  Filtered file or path iteator.
	 *
	 */
	public static function findByCallback($path, \Closure $callback, $recursive = false, $toArray = false)
	{
		$itarator = new \CallbackFilterIterator(static::createIterator($path, $recursive), $callback);

		if ($toArray) {
			return static::iteratorToArray($itarator);
		}

		return $itarator;
	}

	/**
	 * Create file iterator of current dir.
	 *
	 * @param  string  $path      The directory path.
	 * @param  boolean $recursive True to recursive.
	 * @param  integer $options   FilesystemIterator Flags provides which will affect the behavior of some methods.
	 *
	 * @throws \InvalidArgumentException
	 * @return  \FilesystemIterator|\RecursiveIteratorIterator  File & dir iterator.
	 */
	public static function createIterator($path, $recursive = false, $options = null)
	{
	    $path = static::cleanPath($path);

		if ($recursive)
		{
			$options = $options ? : (\FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
		}
		else
		{
			$options = $options ? : (\FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
		}

		try
		{
			$iterator = new RecursiveDirectoryIterator($path, $options);
		}
		catch (\UnexpectedValueException $exception)
		{
			throw new \InvalidArgumentException(sprintf('Dir: %s not found.', (string) $path), null, $exception);
		}

		// If rescurive set to true, use RecursiveIteratorIterator
		return $recursive ? new \RecursiveIteratorIterator($iterator) : $iterator;
	}

	/**
	 * iteratorToArray
	 *
	 * @param \Traversable $iterator
	 *
	 * @return  array
	 */
	public static function iteratorToArray(\Traversable $iterator)
	{
		$array = array();

		foreach ($iterator as $key => $file)
		{
			$array[] = (string) $file;
		}

		return $array;
	}
	
	/**
	 * Strips the last extension off of a file name
	 *
	 * @param   string  $file  The file name
	 *
	 * @return  string  The file name without the extension
	 */
	static public function stripExtension($file)
	{
	    return preg_replace('#\.[^.]*$#', '', $file);
	}
	
	/**
	 * getExtension
	 *
	 * @param   string  $file  The file path to get extension.
	 *
	 * @return  string  The ext of file path.
	 */
	public static function getExtension($file)
	{
	    return pathinfo($file, PATHINFO_EXTENSION);
	}
	
	/**
	 * Get file name from a path.
	 *
	 * @param   string  $path  The file path to get basename.
	 *
	 * @return  string  The file name.
	 */
	public static function getFilename($path)
	{
	    $name = pathinfo($path, PATHINFO_FILENAME);
	    
	    $ext = pathinfo($path, PATHINFO_EXTENSION);
	    
	    if ($ext) {
	        $name .= '.' . $ext;
	    }
	    
	    return $name;
	}
	
	/*********************************************************
	 * permission methods
	 ********************************************************/
	
	/**
	 * Checks if a path's permissions can be changed.
	 *
	 * @param   string  $path  Path to check.
	 *
	 * @return  boolean  True if path can have mode changed.
	 *
	 */
	static public function canChmod($path)
	{
	    $perms = fileperms($path);
	    
	    if ($perms !== false)
	    {
	        if (@chmod($path, $perms ^ 0001))
	        {
	            @chmod($path, $perms);
	            
	            return true;
	        }
	    }
	    
	    return false;
	}
	
	/**
	 * Chmods files and directories recursively to given permissions.
	 *
	 * @param   string  $path        Root path to begin changing mode [without trailing slash].
	 * @param   string  $filemode    Octal representation of the value to change file mode to [null = no change].
	 * @param   string  $foldermode  Octal representation of the value to change folder mode to [null = no change].
	 *
	 * @return  boolean  True if successful [one fail means the whole operation failed].
	 *
	 */
	static public function setPermissions($path, $filemode = '0644', $foldermode = '0755')
	{
	    // Initialise return value
	    $ret = true;
	    
	    if (is_dir($path))
	    {
	        $dh = opendir($path);
	        
	        while ($file = readdir($dh))
	        {
	            if ($file != '.' && $file != '..')
	            {
	                $fullpath = $path . '/' . $file;
	                
	                if (is_dir($fullpath))
	                {
	                    if (!self::setPermissions($fullpath, $filemode, $foldermode))
	                    {
	                        $ret = false;
	                    }
	                }
	                else
	                {
	                    if (isset($filemode))
	                    {
	                        if (!@ chmod($fullpath, octdec($filemode)))
	                        {
	                            $ret = false;
	                        }
	                    }
	                }
	            }
	        }
	        
	        closedir($dh);
	        
	        if (isset($foldermode))
	        {
	            if (!@ chmod($path, octdec($foldermode)))
	            {
	                $ret = false;
	            }
	        }
	    }
	    else
	    {
	        if (isset($filemode))
	        {
	            $ret = @ chmod($path, octdec($filemode));
	        }
	    }
	    
	    return $ret;
	}
	
	/**
	 * Get the permissions of the file/folder at a give path.
	 *
	 * @param   string   $path      The path of a file/folder.
	 * @param   boolean  $toString  Convert permission number to string.
	 *
	 * @return  string  Filesystem permissions.
	 *
	 */
	static public function getPermissions($path, $toString = false)
	{
	    $path = self::clean($path);
	    $mode = @ decoct(@ fileperms($path) & 0777);
	    
	    if (!$toString)
	    {
	        return $mode;
	    }
	    
	    if (strlen($mode) < 3)
	    {
	        return '---------';
	    }
	    
	    $parsedMode = '';
	    
	    for ($i = 0; $i < 3; $i++)
	    {
	        // Read
	        $parsedMode .= ($mode{$i} & 04) ? "r" : "-";
	        
	        // Write
	        $parsedMode .= ($mode{$i} & 02) ? "w" : "-";
	        
	        // Execute
	        $parsedMode .= ($mode{$i} & 01) ? "x" : "-";
	    }
	    
	    return $parsedMode;
	}
	

	
	/**
	 * Function to strip additional / or \ in a path name.
	 *
	 * @param   string  $path  The path to clean.
	 * @param   string  $ds    Directory separator (optional).
	 *
	 * @return  string  The cleaned path.
	 *
	 * @throws  \UnexpectedValueException If $path is not a string.
	 */
	static public function cleanPath($path, $ds = DIRECTORY_SEPARATOR)
	{
	    if (!is_string($path)) {
	        throw new \UnexpectedValueException(__CLASS__ . '::clean $path is not a string.');
	    }
	    
	    $path = trim($path);
	    
	    if (($ds == '\\') && ($path[0] == '\\' ) && ( $path[1] == '\\' ))
	    // Remove double slashes and backslashes and convert all slashes and backslashes to DIRECTORY_SEPARATOR
	    // If dealing with a UNC path don't forget to prepend the path with a backslash.
	    {
	        $path = "\\" . preg_replace('#[/\\\\]+#', $ds, $path);
	    }
	    else
	    {
	        $path = preg_replace('#[/\\\\]+#', $ds, $path);
	    }
	    
	    return $path;
	}
	
	/**
	 * format a path. This method will do clean() first to replace slashes and remove '..' to create a
	 * Clean path. Unlike realpath(), if this path not exists, normalise() will still return this path.
	 *
	 * @param   string  $path
	 * @param   string  $ds    Directory separator (optional).
	 * 
	 * @return  string  The formated path.
	 *
	 * @throws  \UnexpectedValueException If $path is not a string.
	 */
	static public function formatPath($path, $ds = DIRECTORY_SEPARATOR)
	{
	    $parts    = array();
	    $path     = static::cleanPath($path, $ds);
	    $segments = explode($ds, $path);
	    
	    foreach ($segments as $segment)
	    {
	        if ($segment != '.')
	        {
	            $test = array_pop($parts);
	            
	            if (is_null($test))
	            {
	                $parts[] = $segment;
	            }
	            elseif ($segment == '..')
	            {
	                if ($test == '..')
	                {
	                    $parts[] = $test;
	                }
	                
	                if ($test == '..' || $test == '')
	                {
	                    $parts[] = $segment;
	                }
	            }
	            else
	            {
	                $parts[] = $test;
	                $parts[] = $segment;
	            }
	        }
	    }
	    
	    return implode($ds, $parts);
	}
	
	
	
}

