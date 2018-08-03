<?php
namespace Wslim\Util;

use Exception;
use UnexpectedValueException;

/**
 * file operate class
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class File
{
	/**
	 * Makes the file name safe to use
	 *
	 * @param   string  $file        The name of the file [not full path]
	 * @param   array   $stripChars  Array of regex (by default will remove any leading periods)
	 *
	 * @return  string  The sanitised string
	 */
	public static function makeSafe($file, array $stripChars = array('#^\.#'))
	{
		$regex = array_merge(array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#'), $stripChars);

		$file = preg_replace($regex, '', $file);

		// Remove any trailing dots, as those aren't ever valid file names.
		$file = rtrim($file, '.');

		return $file;
	}

	/**
	 * Copies a file
	 *
	 * @param   string $src   The path to the source file
	 * @param   string $dest  The path to the destination file
	 * @param   bool   $force Force copy.
	 *
	 * @throws \UnexpectedValueException
	 * @throws  \Exception
	 * @return  boolean  True on success
	 */
	public static function copy($src, $dest, $force = false)
	{
		// Check src path
		if (!is_readable($src))
		{
			throw new \UnexpectedValueException(__METHOD__ . ': Cannot find or read file: ' . $src);
		}

		// Check folder exists
		$dir = dirname($dest);

		if (!is_dir($dir))
		{
			Dir::create($dir);
		}

		// Check is a folder or file
		if (file_exists($dest))
		{
			if ($force)
			{
				FileHelper::delete($dest);
			}
			else
			{
				throw new Exception($dest . ' has exists, copy faieed.');
			}
		}

		if (!@ copy($src, $dest))
		{
			throw new Exception(__METHOD__ . ': Copy failed.');
		}

		return true;
	}

	/**
	 * Delete a file or array of files
	 *
	 * @param   mixed  $file  The file name or an array of file names
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  Exception
	 */
	public static function delete($file)
	{
		$files = (array) $file;

		foreach ($files as $file) {
			$file = FileHelper::cleanPath($file);

			// Try making the file writable first. If it's read-only, it can't be deleted
			// on Windows, even if the parent folder is writable
			@chmod($file, 0777);

			// In case of restricted permissions we zap it one way or the other
			// as long as the owner is either the webserver or the ftp
			if (!@ unlink($file)) {
				throw new Exception(__METHOD__ . ': Failed deleting ' . basename($file));
			}
		}

		return true;
	}

	/**
	 * Moves a file
	 *
	 * @param   string $src   The path to the source file
	 * @param   string $dest  The path to the destination file
	 * @param   bool   $force Force move it.
	 *
	 * @throws  Exception
	 * @return  boolean  True on success
	 */
	public static function move($src, $dest, $force = false)
	{
		// Check src path
		if (!is_readable($src))
		{
			return 'Cannot find source file.';
		}

		// Delete first if exists
		if (file_exists($dest))
		{
			if ($force)
			{
				FileHelper::delete($dest);
			}
			else
			{
				throw new Exception('File: ' . $dest . ' exists, move failed.');
			}
		}

		// Check folder exists
		$dir = dirname($dest);

		if (!is_dir($dir))
		{
			Dir::create($dir);
		}

		if (!@ rename($src, $dest))
		{
			throw new Exception(__METHOD__ . ': Rename failed.');
		}

		return true;
	}

	/**
	 * Write contents to a file
	 *
	 * @param   string   $file    The full file path
	 * @param   string   $buffer  The buffer to write
	 * @param   int      $flag    FILE_USE_INCLUDE_PATH | FILE_APPEND | LOCK_EX
	 * @return  boolean  True on success
	 *
	 * @throws  Exception
	 */
	public static function write($file, $buffer, $flag=0)
	{
		@set_time_limit(ini_get('max_execution_time'));

		// If the destination directory doesn't exist we need to create it
		if (!is_dir(dirname($file)))
		{
			Dir::create(dirname($file));
		}

		$file = FileHelper::cleanPath($file);
		$ret = is_numeric(file_put_contents($file, $buffer, $flag)) ? true : false;

		return $ret;
	}
	
	/**
	 * touch file
	 * @param  string $filename
	 * @return boolean
	 */
	static public function touch($filename)
	{
	    $ret = true;
	    if ($filename && !file_exists($filename)) {
	        $ret = static::write($filename, '');
	    }
	    return $ret;
	}

	/**
	 * Moves an uploaded file to a destination folder
	 *
	 * @param   string   $src          The name of the php (temporary) uploaded file
	 * @param   string   $dest         The path (including filename) to move the uploaded file to
	 *
	 * @return  boolean  True on success
	 * @throws  Exception
	 */
	public static function upload($src, $dest)
	{
		// Ensure that the path is valid and clean
		$dest = FileHelper::cleanPath($dest);

		// Create the destination directory if it does not exist
		$baseDir = dirname($dest);

		if (!file_exists($baseDir))
		{
			Dir::create($baseDir);
		}

		if (is_writeable($baseDir) && move_uploaded_file($src, $dest))
		{
			// Short circuit to prevent file permission errors
			if (FileHelper::setPermissions($dest))
			{
				return true;
			}
			else
			{
				throw new Exception(__METHOD__ . ': Failed to change file permissions.');
			}
		}

		throw new Exception(__METHOD__ . ': Failed to move file.');
	}

}
