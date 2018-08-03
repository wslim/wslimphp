<?php
namespace Wslim\Util\File;

/**
 * A Directory iterator extends from SPL RecursiveDirectoryIterator.
 * 
 */
class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{
	/**
	 * Get file information of the current element.
	 *
	 * We remove . and .. when fetching folders' path.
	 *
	 * @return  \SplFileInfo  The filename, file information, or $this depending on the set flags.
	 *          See the: http://www.php.net/manual/en/class.filesystemiterator.php#filesystemiterator.constants
	 *
	 */
	public function current()
	{
		$name = $this->getPathname();

		$endletters = DIRECTORY_SEPARATOR . '.';

		if (substr($name, -2) == $endletters)
		{
			$name = substr($name, 0, -2);
		}

		$file = new \SplFileInfo($name);

		return $file;
	}
}
