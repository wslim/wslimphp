<?php
namespace Wslim\Util;

use Exception;

/**
 * Directory operate class.
 * 
 * 1 根据一个目录名实例化，然后可进行拷贝、移动、取文件、取树等操作 . 
 * 2 提供静态的操作方法.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Dir
{

    /**
     * The directory path
     * @var string
     */
    protected $path = null;

    /**
     * The files within the directory
     * @var array
     */
    protected $files = [];

    /**
     * The file info objects within the directory
     * @var array
     */
    protected $objects = [];

    /**
     * The nested tree map of the directory and its files
     * @var array
     */
    protected $tree = [];

    /**
     * Flag to store the absolute path.
     * @var boolean
     */
    protected $absolute = false;

    /**
     * Flag to store the relative path.
     * @var boolean
     */
    protected $relative = false;

    /**
     * Flag to dig recursively.
     * @var boolean
     */
    protected $recursive = false;

    /**
     * Flag to include only files and no directories
     * @var boolean
     */
    protected $filesOnly = false;

    /**
     * Constructor
     *
     * Instantiate a directory object
     *
     * @param  string  $dir
     * @param  array   $options
     * @throws Exception
     * @return Dir
     */
    public function __construct($dir, array $options = [])
    {
        // Check to see if the directory exists.
        if (!file_exists($dir)) {
            throw new Exception('Error: The directory does not exist.');
        }

        // Set the directory path.
        if ((strpos($dir, '/') !== false) && (DIRECTORY_SEPARATOR != '/')) {
            $this->path = str_replace('/', "\\", $dir);
        } else if ((strpos($dir, "\\") !== false) && (DIRECTORY_SEPARATOR != "\\")) {
            $this->path = str_replace("\\", '/', $dir);
        } else {
            $this->path = $dir;
        }

        // Trim the trailing slash.
        if (strrpos($this->path, DIRECTORY_SEPARATOR) == (strlen($this->path) - 1)) {
            $this->path = substr($this->path, 0, -1);
        }

        if (isset($options['absolute'])) {
            $this->setAbsolute($options['absolute']);
        }
        if (isset($options['relative'])) {
            $this->setRelative($options['relative']);
        }
        if (isset($options['recursive'])) {
            $this->setRecursive($options['recursive']);
        }
        if (isset($options['filesOnly'])) {
            $this->setFilesOnly($options['filesOnly']);
        }

        $this->tree[realpath($this->path)] = $this->buildTree(new \DirectoryIterator($this->path));
        $this->traverse();
    }

    /**
     * Set absolute
     *
     * @param  boolean $absolute
     * @return Dir
     */
    public function setAbsolute($absolute)
    {
        $this->absolute = (bool)$absolute;
        if (($this->absolute) && ($this->isRelative())) {
            $this->setRelative(false);
        }
        return $this;
    }

    /**
     * Set relative
     *
     * @param  boolean $relative
     * @return Dir
     */
    public function setRelative($relative)
    {
        $this->relative = (bool)$relative;
        if (($this->relative) && ($this->isAbsolute())) {
            $this->setAbsolute(false);
        }
        return $this;
    }

    /**
     * Set recursive
     *
     * @param  boolean $recursive
     * @return Dir
     */
    public function setRecursive($recursive)
    {
        $this->recursive = (bool)$recursive;
        return $this;
    }

    /**
     * Set files only
     *
     * @param  boolean $filesOnly
     * @return Dir
     */
    public function setFilesOnly($filesOnly)
    {
        $this->filesOnly = (bool)$filesOnly;
        return $this;
    }

    /**
     * Is absolute
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return $this->absolute;
    }

    /**
     * Is relative
     *
     * @return boolean
     */
    public function isRelative()
    {
        return $this->relative;
    }

    /**
     * Is recursive
     *
     * @return boolean
     */
    public function isRecursive()
    {
        return $this->recursive;
    }

    /**
     * Is files only
     *
     * @return boolean
     */
    public function isFilesOnly()
    {
        return $this->filesOnly;
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the files
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the objects
     *
     * @return array
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Get the tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Copy an entire directory recursively
     *
     * @param  string  $dest
     * @param  boolean $full
     * @return void
     */
    public function copyTo($dest, $full = true)
    {
        if ($full) {
            if (strpos($this->path, DIRECTORY_SEPARATOR) !== false) {
                $folder = substr($this->path, (strrpos($this->path, DIRECTORY_SEPARATOR) + 1));
            } else {
                $folder = $this->path;
            }

            if (!file_exists($dest . DIRECTORY_SEPARATOR . $folder)) {
                mkdir($dest . DIRECTORY_SEPARATOR . $folder);
            }
            $dest = $dest . DIRECTORY_SEPARATOR . $folder;
        }

        return static::copy($this->path, $dest, true);
    }

    /**
     * Empty an entire directory
     *
     * @param  boolean $remove
     * @param  string  $path
     * @return void
     */
    public function emptyDir($remove = false, $path = null)
    {
        if (null === $path) {
            $path = $this->path;
        }
        // Get a directory handle.
        if (!$dh = @opendir($path)) {
            return;
        }

        // Recursively dig through the directory, deleting files where applicable.
        while (false !== ($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') {
                continue;
            }
            if (!@unlink($path . DIRECTORY_SEPARATOR . $obj)) {
                $this->emptyDir(true, $path . DIRECTORY_SEPARATOR . $obj);
            }
        }

        // Close the directory handle.
        closedir($dh);

        // If the delete flag was passed, remove the top level directory.
        if ($remove) {
            @rmdir($path);
        }
    }

    /**
     * Traverse the directory
     *
     * @return void
     */
    public function traverse()
    {
        // If the recursive flag is passed, traverse recursively.
        if ($this->recursive) {
            $objects = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->path), \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($objects as $fileInfo) {
                if (($fileInfo->getFilename() != '.') && ($fileInfo->getFilename() != '..')) {
                    $this->objects[] = $fileInfo;
                    // If absolute path flag was passed, store the absolute path.
                    if ($this->absolute) {
                        $f = null;
                        if (!$this->filesOnly) {
                            $f = ($fileInfo->isDir()) ? (realpath($fileInfo->getPathname())) : realpath($fileInfo->getPathname());
                        } else if (!$fileInfo->isDir()) {
                            $f = realpath($fileInfo->getPathname());
                        }
                        if (($f !== false) && (null !== $f)) {
                            $this->files[] = $f;
                        }
                    // If relative path flag was passed, store the relative path.
                    } else if ($this->relative) {
                        $f = null;
                        if (!$this->filesOnly) {
                            $f = ($fileInfo->isDir()) ? (realpath($fileInfo->getPathname())) : realpath($fileInfo->getPathname());
                        } else if (!$fileInfo->isDir()) {
                            $f = realpath($fileInfo->getPathname());
                        }
                        if (($f !== false) && (null !== $f)) {
                            $this->files[] = substr($f, (strlen(realpath($this->path)) + 1));
                        }
                    // Else, store only the directory or file name.
                    } else {
                        if (!$this->filesOnly) {
                            $this->files[] = ($fileInfo->isDir()) ? ($fileInfo->getFilename()) : $fileInfo->getFilename();
                        } else if (!$fileInfo->isDir()) {
                            $this->files[] = $fileInfo->getFilename();
                        }
                    }
                }
            }
            // Else, only traverse the single directory that was passed.
        } else {
            foreach (new \DirectoryIterator($this->path) as $fileInfo) {
                if(!$fileInfo->isDot()) {
                    $this->objects[] = $fileInfo;
                    // If absolute path flag was passed, store the absolute path.
                    if ($this->absolute) {
                        $f = null;
                        if (!$this->filesOnly) {
                            $f = ($fileInfo->isDir()) ?
                                ($this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename() . DIRECTORY_SEPARATOR) :
                                ($this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
                        } else if (!$fileInfo->isDir()) {
                            $f = $this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
                        }
                        if (($f !== false) && (null !== $f)) {
                            $this->files[] = $f;
                        }
                    // If relative path flag was passed, store the relative path.
                    } else if ($this->relative) {
                        $f = null;
                        if (!$this->filesOnly) {
                            $f = ($fileInfo->isDir()) ?
                                ($this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename() . DIRECTORY_SEPARATOR) :
                                ($this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
                        } else if (!$fileInfo->isDir()) {
                            $f = $this->path . DIRECTORY_SEPARATOR . $fileInfo->getFilename();
                        }
                        if (($f !== false) && (null !== $f)) {
                            $this->files[] = substr($f, (strlen(realpath($this->path)) + 1));
                        }
                    // Else, store only the directory or file name.
                    } else {
                        if (!$this->filesOnly) {
                            $this->files[] = ($fileInfo->isDir()) ? ($fileInfo->getFilename()) : $fileInfo->getFilename();
                        } else if (!$fileInfo->isDir()) {
                            $this->files[] = $fileInfo->getFilename();
                        }
                    }
                }
            }
        }
    }

    /**
     * Build the directory tree
     *
     * @param  \DirectoryIterator $it
     * @return array
     */
    protected function buildTree(\DirectoryIterator $it)
    {
        $result = [];

        foreach ($it as $key => $child) {
            if ($child->isDot()) {
                continue;
            }

            $name = $child->getBasename();

            if ($child->isDir()) {
                $subdir = new \DirectoryIterator($child->getPathname());
                $result[DIRECTORY_SEPARATOR . $name] = $this->buildTree($subdir);
            } else {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**************************************************************************************************
     * 静态操作方法
     **************************************************************************************************/
    /**
     * 拷贝源目录下所有文件到目标目录下
     * @param string $src
     * @param string $dest
     * @param string $force
     * @throws Exception
     * @return boolean
     */
    public static function copy($src, $dest, $force = false)
    {
        @set_time_limit(ini_get('max_execution_time'));
    
        // Eliminate trailing directory separators, if any
        $src = rtrim($src, '/\\');
        $dest = rtrim($dest, '/\\');
    
        if (!is_dir($src)) {
            throw new Exception('Source folder not found', -1);
        }
    
        if (is_dir($dest) && !$force)
        {
            throw new Exception('Destination folder exists', -1);
        }
    
        // Make sure the destination exists
        if (!static::create($dest)) {
            throw new Exception('Cannot create destination folder', -1);
        }
    
        $sources = FileHelper::items($src, true, true, static::PATH_RELATIVE);
    
        // Walk through the directory copying files and recursing into folders.
        foreach ($sources as $file)
        {
            $srcFile = $src . '/' . $file;
            $destFile = $dest . '/' . $file;
    
            if (is_dir($srcFile)) {
                static::create($destFile);
            } elseif (is_file($srcFile)) {
                File::copy($srcFile, $destFile);
            }
        }
    
        return true;
    }
    
    /**
     * 
     * @param string $path
     * @param int    $mode
     */
    static public function create($path = '', $mode = 0755)
    {
        // Check to make sure the path valid and clean
        $path = FileHelper::cleanPath($path);
    
        // Check if dir already exists
        if (is_dir($path)) {
            return true;
        }
    
        // We need to get and explode the open_basedir paths
        $obd = ini_get('open_basedir');
    
        // If open_basedir is set we need to get the open_basedir that the path is in
        if ($obd != null)
        {
            $obdSeparator = defined('PHP_WINDOWS_VERSION_MAJOR') ? ";" : ":";
    
            // Create the array of open_basedir paths
            $obdArray = explode($obdSeparator, $obd);
            $inBaseDir = false;
    
            // Iterate through open_basedir paths looking for a match
            foreach ($obdArray as $test)
            {
                $test = FileHelper::cleanPath($test);
    
                if (strpos($path, $test) === 0)
                {
                    $inBaseDir = true;
                    break;
                }
            }
    
            if ($inBaseDir == false)
            {
                // Throw a Exception because the path to be created is not in open_basedir
                throw new Exception(__METHOD__ . ': Path not in open_basedir paths');
            }
        }
    
        $path = explode(DIRECTORY_SEPARATOR, $path);
    
        $dir = array_shift($path);
    
        foreach ($path as $folder)
        {
            $dir .= DIRECTORY_SEPARATOR . $folder;
    
            if (is_dir($dir))
            {
                continue;
            }
    
            // First set umask
            $origmask = @umask(0);
    
            // Create the path
            if (!@mkdir($dir, $mode))
            {
                @umask($origmask);
    
                throw new Exception(__METHOD__ . ': Could not create directory.  Path: ' . $dir);
            }
    
            // Reset umask
            @umask($origmask);
        }
    
        return true;
    }
    
    /**
     * Delete a folder.
     *
     * @param   string  $path  The path to the folder to delete.
     *
     * @return  boolean  True on success.
     *
     * @throws  \Exception
     * @throws  \UnexpectedValueException
     */
    public static function delete($path)
    {
        @set_time_limit(ini_get('max_execution_time'));
    
        // Sanity check
        if (!rtrim($path, '/\\'))
        {
            // Bad programmer! Bad Bad programmer!
            throw new \Exception(__METHOD__ . ': You can not delete a base directory.');
        }
    
        // Check to make sure the path valid and clean
        $path = FileHelper::cleanPath($path);
    
        // Is this really a folder?
        if (!is_dir($path))
        {
            throw new \UnexpectedValueException(sprintf('%1$s: Path is not a folder. Path: %2$s', __METHOD__, $path));
        }
    
        // Remove all the files in folder if they exist; disable all filtering
        $files = FileHelper::files($path, true, true);
    
        if (!empty($files)) {
            File::delete($files);
        }
    
        // Remove sub-folders of folder; disable all filtering
        $folders = FileHelper::dirs($path, false, true);
        
        foreach ($folders as $folder) 
        {
            if (is_link($folder))
            {
                // Don't descend into linked directories, just delete the link.
                File::delete($folder);
            }
            else
            {
                static::delete($folder);
            }
        }
    
        // In case of restricted permissions we zap it one way or the other
        // as long as the owner is either the webserver or the ftp.
        if (@rmdir($path))
        {
            return true;
        }
        else
        {
            throw new \Exception(sprintf('%1$s: Could not delete folder. Path: %2$s', __METHOD__, $path));
        }
    }
    
    /**
     * Moves a folder.
     *
     * @param   string $src       The path to the source folder.
     * @param   string $dest      The path to the destination folder.
     * @param   bool   $override  Override files.
     *
     * @throws  Exception
     * @return  mixed  Error message on false or boolean true on success.
     */
    static public function move($src, $dest, $override = false)
    {
        if (!is_dir($src)) {
            throw new Exception('Cannot find source folder');
        }
    
        if (is_dir($dest))
        {
            if (!$override) {
                throw new Exception('Folder already exists');
            }
    
            foreach (FileHelper::items($src, true, true, static::PATH_RELATIVE) as $item)
            {
                if (is_file($src . '/' . $item)) {
                    File::move($src . '/' . $item, $dest . '/' . $item, true);
                }
                elseif (is_dir($src . '/' . $item))
                {
                    static::create($dest . '/' . $item);
                }
            }
    
            static::delete($src);
    
            return true;
        }
    
        if (!@rename($src, $dest)) 
        {
            throw new Exception('Rename failed');
        }
    
        return true;
    }
    
    

    
    /**
     * Makes path name safe to use.
     *
     * @param   string  $path  The full path to sanitise.
     *
     * @return  string  The sanitised string.
     *
     */
    public static function makeSafe($path)
    {
        $regex = array('#[^A-Za-z0-9_\\\/\(\)\[\]\{\}\#\$\^\+\.\'~`!@&=;,-]#');
    
        return preg_replace($regex, '', $path);
    }
    
    
    
    
}
