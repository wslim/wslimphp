<?php
namespace Wslim\Util\File;

interface FileComparatorInterface
{
	public function compare($current, $key, $iterator);
}

